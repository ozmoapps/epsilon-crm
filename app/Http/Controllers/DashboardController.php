<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\ActivityLog;
use App\Models\Quote;
use App\Models\SalesOrder;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $since = now()->subDays(7);

        $customersCount = Customer::count();
        $quotesCount = Quote::count();
        $salesOrdersCount = SalesOrder::count();
        $contractsCount = Contract::count();
        $workOrdersCount = WorkOrder::count();

        $customersRecentCount = Customer::where('created_at', '>=', $since)->count();
        $salesOrdersRecentCount = SalesOrder::where('created_at', '>=', $since)->count();
        $contractsRecentCount = Contract::where('created_at', '>=', $since)->count();

        $quoteStatusCounts = Quote::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $salesOrderStatusCounts = SalesOrder::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $contractStatusCounts = Contract::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $workOrderStatusCounts = WorkOrder::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentSalesOrders = SalesOrder::with('customer')
            ->latest()
            ->limit(6)
            ->get();

        $recentContracts = Contract::latest()
            ->limit(6)
            ->get();

        $recentActivity = ActivityLog::with(['actor', 'subject'])
            ->latest('created_at')
            ->limit(20)
            ->get();

        return view('dashboard', [
            'customersCount' => $customersCount,
            'quotesCount' => $quotesCount,
            'salesOrdersCount' => $salesOrdersCount,
            'contractsCount' => $contractsCount,
            'workOrdersCount' => $workOrdersCount,
            'customersRecentCount' => $customersRecentCount,
            'salesOrdersRecentCount' => $salesOrdersRecentCount,
            'contractsRecentCount' => $contractsRecentCount,
            'recentSalesOrders' => $recentSalesOrders,
            'recentContracts' => $recentContracts,
            'quoteStatusCounts' => $quoteStatusCounts,
            'salesOrderStatusCounts' => $salesOrderStatusCounts,
            'contractStatusCounts' => $contractStatusCounts,
            'workOrderStatusCounts' => $workOrderStatusCounts,
            'recentActivity' => $recentActivity,
        ]);
    }
}
