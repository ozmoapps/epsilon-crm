<?php

namespace App\Http\Controllers;

use App\Models\SalesOrderReturn;
use App\Models\SalesOrderShipment;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesOrderReturnController extends Controller
{
    public function create(SalesOrderShipment $shipment)
    {
        if ($shipment->status !== 'posted') {
             return back()->with('error', 'Sadece işlenmiş (posted) sevkiyatlar iade edilebilir.');
        }

        $shipment->load(['lines.product', 'salesOrder']);
        
        // Calculate returnable limits: Shipped - (Posted Returns)
        $returnedLines = DB::table('sales_order_return_lines')
            ->join('sales_order_returns', 'sales_order_returns.id', '=', 'sales_order_return_lines.sales_order_return_id')
            ->where('sales_order_returns.sales_order_shipment_id', $shipment->id)
            ->where('sales_order_returns.status', 'posted')
            ->select('sales_order_return_lines.sales_order_shipment_line_id', DB::raw('SUM(qty) as returned_qty'))
            ->groupBy('sales_order_shipment_line_id')
            ->pluck('returned_qty', 'sales_order_shipment_line_id');

        $returnList = $shipment->lines->map(function ($line) use ($returnedLines) {
            $returned = $returnedLines[$line->id] ?? 0;
            $remaining = max(0, $line->qty - $returned);

            return [
                'line_id' => $line->id,
                'product_id' => $line->product_id,
                'product_name' => $line->product?->name ?? $line->description,
                'description' => $line->description,
                'unit' => $line->unit,
                'shipped_qty' => $line->qty,
                'returned_qty' => $returned,
                'remaining_returnable_qty' => $remaining,
            ];
        })->filter(fn($i) => $i['remaining_returnable_qty'] > 0);

        // Pre-select shipment warehouse but allow change
        $warehouses = Warehouse::orderBy('name')->get();

        return view('sales_orders.returns.create', compact('shipment', 'warehouses', 'returnList'));
    }

    public function store(Request $request, SalesOrderShipment $shipment)
    {
        if ($shipment->status !== 'posted') {
            return back()->with('error', 'Sevkiyat uygun değil.');
        }

        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'note' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.line_id' => 'required|exists:sales_order_shipment_lines,id',
            'lines.*.qty' => 'required|numeric|min:0.01',
        ]);

        return DB::transaction(function () use ($request, $shipment) {
            // Re-calc strictly
            $returnedLines = DB::table('sales_order_return_lines')
              ->join('sales_order_returns', 'sales_order_returns.id', '=', 'sales_order_return_lines.sales_order_return_id')
              ->where('sales_order_returns.sales_order_shipment_id', $shipment->id)
              ->where('sales_order_returns.status', 'posted') // Only count POSTED
              ->select('sales_order_return_lines.sales_order_shipment_line_id', DB::raw('SUM(qty) as returned_qty'))
              ->groupBy('sales_order_shipment_line_id')
              ->pluck('returned_qty', 'sales_order_shipment_line_id');

            $return = SalesOrderReturn::create([
                'sales_order_id' => $shipment->sales_order_id,
                'sales_order_shipment_id' => $shipment->id,
                'warehouse_id' => $request->warehouse_id,
                'status' => 'draft',
                'note' => $request->note,
                'created_by' => $request->user()->id,
            ]);

            foreach ($request->lines as $lineData) {
                // Ignore lines not selected/checked if we implemented checkbox UI (assuming only selected passed OR all passed)
                // For simplicity assuming only valid lines passed or filtered. 
                // Let's assume passed lines are intended to be creating.
                
                $shipLine = $shipment->lines->firstWhere('id', $lineData['line_id']);
                if (!$shipLine) continue;

                $returned = $returnedLines[$shipLine->id] ?? 0;
                $remaining = max(0, $shipLine->qty - $returned);

                if ($lineData['qty'] > $remaining) {
                     throw new \Illuminate\Validation\ValidationException(\Illuminate\Support\Facades\Validator::make([], []), [
                           'qty' => "İade miktarı sevk edilen miktarı aşıyor. En fazla {$remaining} iade alabilirsiniz."
                    ]);
                }

                $return->lines()->create([
                    'sales_order_shipment_line_id' => $shipLine->id,
                    'product_id' => $shipLine->product_id,
                    'qty' => $lineData['qty'],
                    'description' => $shipLine->description,
                    'unit' => $shipLine->unit,
                ]);
            }
            
            if ($request->has('post_now')) {
                 app(StockService::class)->postSalesOrderReturn($return);
                 return redirect()->route('sales-orders.show', $shipment->sales_order_id)->with('success', 'İade oluşturuldu ve işlendi.');
            }

            return redirect()->route('sales-orders.show', $shipment->sales_order_id)->with('success', 'Taslak iade oluşturuldu.');
        });
    }

    public function show(SalesOrderReturn $return)
    {
        $return->load(['lines', 'warehouse', 'creator', 'shipment']);
        $salesOrder = $return->salesOrder;
        return view('sales_orders.returns.show', compact('salesOrder', 'return'));
    }

    public function post(SalesOrderReturn $return, StockService $stockService)
    {
        if ($return->status !== 'draft') {
            return back();
        }

        $stockService->postSalesOrderReturn($return);

        return back()->with('success', 'İade stok hareketi işlendi.');
    }

    public function destroy(SalesOrderReturn $return)
    {
        if ($return->status !== 'draft') {
            return back()->with('error', 'Sadece taslak iadeler silinebilir.');
        }

        $return->lines()->delete();
        $return->delete();

        return back()->with('success', 'İade silindi.');
    }
}
