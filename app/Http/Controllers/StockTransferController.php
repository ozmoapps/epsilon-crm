<?php

namespace App\Http\Controllers;

use App\Models\StockTransfer;
use App\Models\StockTransferLine;
use App\Models\Warehouse;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    public function index()
    {
        $transfers = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'creator'])
            ->latest()
            ->paginate(20);

        return view('stock_transfers.index', compact('transfers'));
    }

    public function create()
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('track_stock', true)->orderBy('name')->get(['id', 'name', 'sku']);

        return view('stock_transfers.create', compact('warehouses', 'products'));
    }

    public function store(Request $request, StockService $stockService)
    {
        $validated = $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.01',
        ]);

        $transfer = DB::transaction(function () use ($validated, $request, $stockService) {
            $transfer = StockTransfer::create([
                'from_warehouse_id' => $validated['from_warehouse_id'],
                'to_warehouse_id' => $validated['to_warehouse_id'],
                'note' => $validated['note'],
                'created_by' => $request->user()->id,
                'status' => 'draft',
            ]);

            foreach ($validated['items'] as $index => $item) {
                $transfer->lines()->create([
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'sort_order' => $index,
                ]);
            }

            // Immediately post? User said "Save and Post" button (single step)
            // But also optional draft. Let's look at action.
            if ($request->has('post_now')) {
                 $stockService->postTransfer($transfer->id);
            }

            return $transfer;
        });

        if ($request->has('post_now')) {
            return redirect()->route('stock-transfers.show', $transfer)
                ->with('success', 'Transfer oluşturuldu ve stoktan düşüldü.');
        }

        return redirect()->route('stock-transfers.show', $transfer)
            ->with('success', 'Transfer taslağı oluşturuldu.');
    }

    public function show(StockTransfer $stockTransfer)
    {
        $stockTransfer->load(['fromWarehouse', 'toWarehouse', 'lines.product', 'creator', 'stockMovements']);
        return view('stock_transfers.show', compact('stockTransfer'));
    }

    public function post(StockTransfer $stockTransfer, StockService $stockService)
    {
        if ($stockTransfer->status !== 'draft') {
            return redirect()->back()->with('error', 'Bu transfer zaten işlenmiş.');
        }

        $stockService->postTransfer($stockTransfer->id);

        return redirect()->back()->with('success', 'Transfer stoktan düşüldü.');
    }
}
