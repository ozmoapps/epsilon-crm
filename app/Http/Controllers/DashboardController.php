<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\SalesOrder;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $since = now()->subDays(7);

        $customersCount = Customer::count();
        $salesOrdersCount = SalesOrder::count();
        $contractsCount = Contract::count();

        $customersRecentCount = Customer::where('created_at', '>=', $since)->count();
        $salesOrdersRecentCount = SalesOrder::where('created_at', '>=', $since)->count();
        $contractsRecentCount = Contract::where('created_at', '>=', $since)->count();

        $recentSalesOrders = SalesOrder::with('customer')
            ->latest()
            ->limit(6)
            ->get();

        $recentContracts = Contract::latest()
            ->limit(6)
            ->get();

        return view('dashboard', [
            'customersCount' => $customersCount,
            'salesOrdersCount' => $salesOrdersCount,
            'contractsCount' => $contractsCount,
            'customersRecentCount' => $customersRecentCount,
            'salesOrdersRecentCount' => $salesOrdersRecentCount,
            'contractsRecentCount' => $contractsRecentCount,
            'recentSalesOrders' => $recentSalesOrders,
            'recentContracts' => $recentContracts,
        ]);
    }
}
