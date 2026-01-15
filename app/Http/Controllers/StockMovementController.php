<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $warehouseId = $request->input('warehouse_id');
        $productId = $request->input('product_id');
        $type = $request->input('type');
        $direction = $request->input('direction');
        
        $movements = StockMovement::query()
            ->with(['warehouse', 'product', 'creator', 'reference'])
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->when($productId, fn($q) => $q->where('product_id', $productId))
            ->when($type, fn($q) => $q->where('type', $type))
            ->when($direction, fn($q) => $q->where('direction', $direction))
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $warehouses = Warehouse::orderBy('name')->get();
        // For filter, maybe load all products? Or AJAX? 
        // Trying simple all products for now as user didn't specify AJAX.
        $products = Product::where('track_stock', true)->orderBy('name')->get(); 

        return view('stock_movements.index', compact('movements', 'warehouses', 'products'));
    }
}
