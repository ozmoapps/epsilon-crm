<?php

namespace App\Http\Controllers;

use App\Models\InventoryBalance;
use App\Models\Product;
use App\Models\SalesOrderReturn;
use App\Models\SalesOrderShipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockDashboardController extends Controller
{
    public function index()
    {
        // 1. KPI Cards
        
        // Negative Stock Products (Count of Unique Products with any negative balance)
        $negativeStockCount = InventoryBalance::where('qty_on_hand', '<', 0)->distinct('product_id')->count();

        // Critical Stock Products (Count)
        // Logic: Product track_stock=true AND qty_on_hand < critical_stock_level
        // We aggregate balances per product across all warehouses for a global check, 
        // OR check per warehouse. User request implies "Product based" generally, but DB has warehouse balances.
        // Let's check Global Stock vs Critical Level for simplicity and high-level view, 
        // as critical level is usually on Product model, not per warehouse balance (unless we added that).
        // Based on Product model analysis: critical_stock_level is on Product table.
        // So we sum balances per product.
        
        $criticalStockCount = Product::where('track_stock', true)
            ->whereNotNull('critical_stock_level')
            ->where('critical_stock_level', '>', 0)
            ->whereRaw('(SELECT COALESCE(SUM(qty_on_hand), 0) FROM inventory_balances WHERE product_id = products.id) < critical_stock_level')
            ->count();


        // Today's Logistics
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $shippedToday = DB::table('sales_order_shipment_lines')
            ->join('sales_order_shipments', 'sales_order_shipments.id', '=', 'sales_order_shipment_lines.sales_order_shipment_id')
            ->where('sales_order_shipments.status', 'posted')
            ->whereBetween('sales_order_shipments.posted_at', [$todayStart, $todayEnd])
            ->sum('sales_order_shipment_lines.qty');

        $returnedToday = DB::table('sales_order_return_lines')
            ->join('sales_order_returns', 'sales_order_returns.id', '=', 'sales_order_return_lines.sales_order_return_id')
            ->where('sales_order_returns.status', 'posted')
            ->whereBetween('sales_order_returns.posted_at', [$todayStart, $todayEnd])
            ->sum('sales_order_return_lines.qty');

        $pendingShipments = SalesOrderShipment::where('status', 'draft')->count();
        $pendingReturns = SalesOrderReturn::where('status', 'draft')->count();

        // 2. Tables

        // Negative Stock List (Top 10)
        $negativeBalances = InventoryBalance::with(['product', 'warehouse'])
            ->where('qty_on_hand', '<', 0)
            ->orderBy('qty_on_hand', 'asc')
            ->limit(10)
            ->get();

        // Critical Stock List (Top 10)
        // We need to fetch products where Sum(Balance) < Critical. 
        // This is a bit heavy, let's optimise.
        $criticalProducts = Product::where('track_stock', true)
            ->whereNotNull('critical_stock_level')
            ->where('critical_stock_level', '>', 0)
            ->select('products.*')
            ->selectSub(function ($query) {
                $query->from('inventory_balances')
                    ->selectRaw('COALESCE(SUM(qty_on_hand), 0)')
                    ->whereColumn('product_id', 'products.id');
            }, 'total_stock')
            ->whereRaw('(SELECT COALESCE(SUM(qty_on_hand), 0) FROM inventory_balances WHERE product_id = products.id) < critical_stock_level')
            ->orderByRaw('total_stock ASC') // Lowest stock first
            ->limit(10)
            ->get();


        return view('stock.dashboard', compact(
            'negativeStockCount',
            'criticalStockCount',
            'shippedToday',
            'returnedToday',
            'pendingShipments',
            'pendingReturns',
            'negativeBalances',
            'criticalProducts'
        ));
    }
}
