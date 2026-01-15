<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\Product;
use App\Services\StockService;
use App\Models\InventoryBalance;
use Illuminate\Http\Request;

class StockOperationController extends Controller
{
    public function create()
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        // Maybe improve performance later with AJAX for products
        $products = Product::where('track_stock', true)->orderBy('name')->get(['id', 'name', 'sku']);

        return view('stock_operations.create', compact('warehouses', 'products'));
    }

    public function store(Request $request, StockService $stockService)
    {
        $validated = $request->validate([
            'operation_type' => 'required|in:manual_in,manual_out,adjust',
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|exists:products,id',
            'qty' => 'required_if:operation_type,manual_in,manual_out|numeric|min:0.01|nullable',
            'counted_qty' => 'required_if:operation_type,adjust|numeric|min:0|nullable',
            'note' => 'nullable|string',
        ]);

        $warehouseId = $validated['warehouse_id'];
        $productId = $validated['product_id'];
        $note = $validated['note'];

        if ($validated['operation_type'] === 'adjust') {
            // Logic for Adjustment
            $counted = $validated['counted_qty'];
            
            // Get current quantity
            $balance = InventoryBalance::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->value('qty_on_hand') ?? 0;

            $diff = $counted - $balance;

            if ($diff == 0) {
                return redirect()->back()->with('info', 'Sayım miktarı sistemle aynı, işlem yapılmadı.');
            }

            $direction = $diff > 0 ? 'in' : 'out';
            $qty = abs($diff);
            $type = $diff > 0 ? 'adjust_in' : 'adjust_out';
            
            $note = ($note ? $note . " - " : "") . "Sayım Düzeltmesi (Sistem: $balance, Sayım: $counted)";

            $stockService->createMovement(
                warehouseId: $warehouseId,
                productId: $productId,
                qty: $qty,
                direction: $direction,
                type: $type,
                note: $note,
                userId: $request->user()->id
            );

            return redirect()->route('stock-movements.index')
                ->with('success', 'Stok sayım düzeltmesi yapıldı.');

        } else {
            // Logic for Manual In/Out
            $direction = $validated['operation_type'] === 'manual_in' ? 'in' : 'out';
            $type = $validated['operation_type'];
            $qty = $validated['qty'];

            $stockService->createMovement(
                warehouseId: $warehouseId,
                productId: $productId,
                qty: $qty,
                direction: $direction,
                type: $type,
                note: $note,
                userId: $request->user()->id
            );

            return redirect()->route('stock-movements.index')
                ->with('success', 'Manuel stok hareketi oluşturuldu.');
        }
    }
}
