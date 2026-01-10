<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\ActivityLog;
use App\Models\Quote;
use App\Models\SalesOrder;
use App\Models\WorkOrder;
use App\Models\CompanyProfile;
use App\Models\BankAccount;
use App\Models\Currency;
use App\Models\ContractTemplate;
use App\Models\FollowUp;
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

        $recentQuotes = Quote::with(['customer', 'vessel'])
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

        // System Setup Checks
        $hasCompanyProfile = CompanyProfile::query()->exists();
        $bankAccountsCount = BankAccount::query()->count();
        $activeCurrenciesCount = Currency::query()->where('is_active', true)->count();
        $hasDefaultContractTemplate = ContractTemplate::query()->where('is_active', true)->where('is_default', true)->exists();

        $upcomingFollowUps = FollowUp::query()
            ->open()
            ->has('subject')
            ->with(['subject', 'creator'])
            ->orderBy('next_at')
            ->limit(12)
            ->get();

        $staleSentQuotes = Quote::query()
            ->where('status', 'sent')
            ->whereNotNull('issued_at')
            ->where('issued_at', '<=', now()->subDays(3))
            ->latest('issued_at')
            ->limit(8)
            ->get();

        $staleSentContracts = Contract::query()
            ->where('status', 'sent')
            ->whereNotNull('issued_at')
            ->where('issued_at', '<=', now()->subDays(3))
            ->latest('issued_at')
            ->limit(8)
            ->get();

        $upcomingPlannedWorkOrders = WorkOrder::query()
            ->where('status', 'planned')
            ->whereNotNull('planned_start_at')
            ->whereBetween('planned_start_at', [now(), now()->addDays(7)])
            ->orderBy('planned_start_at')
            ->limit(8)
            ->get();

        return view('dashboard', [
            'upcomingFollowUps' => $upcomingFollowUps,
            'staleSentQuotes' => $staleSentQuotes,
            'staleSentContracts' => $staleSentContracts,
            'upcomingPlannedWorkOrders' => $upcomingPlannedWorkOrders,
            'customersCount' => $customersCount,
            'quotesCount' => $quotesCount,
            'salesOrdersCount' => $salesOrdersCount,
            'contractsCount' => $contractsCount,
            'workOrdersCount' => $workOrdersCount,
            'customersRecentCount' => $customersRecentCount,
            'salesOrdersRecentCount' => $salesOrdersRecentCount,
            'contractsRecentCount' => $contractsRecentCount,
            'recentSalesOrders' => $recentSalesOrders,
            'recentQuotes' => $recentQuotes,
            'recentContracts' => $recentContracts,
            'quoteStatusCounts' => $quoteStatusCounts,
            'salesOrderStatusCounts' => $salesOrderStatusCounts,
            'contractStatusCounts' => $contractStatusCounts,
            'workOrderStatusCounts' => $workOrderStatusCounts,
            'recentActivity' => $recentActivity,
            'hasCompanyProfile' => $hasCompanyProfile,
            'bankAccountsCount' => $bankAccountsCount,
            'activeCurrenciesCount' => $activeCurrenciesCount,
            'hasDefaultContractTemplate' => $hasDefaultContractTemplate,
        ]);
    }
}
