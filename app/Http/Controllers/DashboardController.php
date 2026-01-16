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
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = request()->user();
        $isAdmin = true; // PR68: Remove Gating - All users are admins for now

        // --- 1. KPI Cards Data ---
        $openInvoices = collect();
        $overdueInvoices = collect();
        $advances = collect();
        $bankBalances = collect();
        $stockAlertsCount = 0;
        
        $quoteStats = (object)['total' => 0, 'draft' => 0, 'sent' => 0, 'accepted' => 0];
        $salesOrderStats = (object)['total' => 0, 'active' => 0, 'completed' => 0];
        $financeStats = ['invoiced' => collect(), 'collected' => collect()];
        $criticalOverdueInvoices = collect();

        if ($isAdmin) {
            // Open Invoices (Issued/Sent but not Paid)
            $openInvoices = Invoice::query()
                ->whereIn('status', ['issued', 'sent'])
                ->where('payment_status', '!=', 'paid')
                ->select('currency', DB::raw('count(*) as count'), DB::raw('sum(total) as total_amount'))
                ->groupBy('currency')
                ->get();

            // Overdue Invoices
            $overdueInvoices = Invoice::query()
                ->whereIn('status', ['issued', 'sent'])
                ->where('payment_status', '!=', 'paid')
                ->where('due_date', '<', now()->startOfDay())
                ->select('currency', DB::raw('count(*) as count'), DB::raw('sum(total) as total_amount'))
                ->groupBy('currency')
                ->get();

            // Advances
            $advances = Payment::query()
                ->whereNull('invoice_id')
                ->selectRaw('COALESCE(original_currency, "EUR") as currency, count(*) as count, sum(COALESCE(original_amount, amount)) as total_amount')
                ->groupBy('currency')
                ->get();

            // Bank Balances
            $bankAccounts = BankAccount::with(['currency'])->get();
            $bankBalances = $bankAccounts->groupBy(fn ($acc) => $acc->currency->code ?? 'EUR')
                ->map(function ($accounts, $currency) {
                    return [
                        'currency' => $currency,
                        'total_balance' => $accounts->sum(fn ($acc) => $acc->balance),
                        'count' => $accounts->count(),
                    ];
                })->values();

            // Stock Alerts
            $stockAlertsCount = Product::query()
                ->where('track_stock', true)
                ->whereRaw('(SELECT COALESCE(SUM(qty_on_hand), 0) FROM inventory_balances WHERE product_id = products.id) <= critical_stock_level')
                ->count();

            // Sales Funnel
            $quoteStats = Quote::selectRaw('
                count(*) as total,
                sum(case when status = "draft" then 1 else 0 end) as draft,
                sum(case when status = "sent" then 1 else 0 end) as sent,
                sum(case when status = "accepted" then 1 else 0 end) as accepted
            ')->first();

            $salesOrderStats = SalesOrder::selectRaw('
                count(*) as total,
                sum(case when status not in ("completed", "cancelled") then 1 else 0 end) as active,
                sum(case when status = "completed" then 1 else 0 end) as completed
            ')->first();

            // Finance (Last 30 Days)
            $financeStats = [
                'invoiced' => Invoice::where('issue_date', '>=', now()->subDays(30))
                    ->selectRaw('currency, sum(total) as total')
                    ->groupBy('currency')
                    ->pluck('total', 'currency'),
                'collected' => Payment::where('payment_date', '>=', now()->subDays(30))
                    ->selectRaw('COALESCE(original_currency, "EUR") as currency, sum(COALESCE(original_amount, amount)) as total')
                    ->groupBy('currency')
                    ->pluck('total', 'currency'),
            ];

             // Critical Alerts (Specific Items)
            $criticalOverdueInvoices = Invoice::query()
                ->with('customer')
                ->where('status', 'issued') // Only issued can be overdue in strict sense
                ->where('payment_status', '!=', 'paid')
                ->where('due_date', '<', now()->subDays(1)) // Grace period 1 day
                ->orderBy('due_date')
                ->limit(5)
                ->get();
        }

        // --- 2. Shared Data (Staff + Admin) ---

        // Workload (Operational Stats) - Fixed Statuses
        // Statuses: draft, planned, started, in_progress, on_hold, completed, delivered, cancelled
        $workOrderStats = WorkOrder::selectRaw('
            count(*) as total,
            sum(case when status in ("planned") then 1 else 0 end) as planned,
            sum(case when status in ("started", "in_progress") then 1 else 0 end) as in_progress,
            sum(case when status in ("completed", "delivered") then 1 else 0 end) as completed
        ')->first();

        // Recent Activity
        $recentActivity = ActivityLog::with(['actor', 'subject'])
            ->latest('created_at')
            ->limit(8)
            ->get();

        // Follow Ups
        $upcomingFollowUps = FollowUp::query()
            ->open()
            ->where('next_at', '<=', now()->addDays(7))
            ->with(['subject', 'creator'])
            ->orderBy('next_at')
            ->limit(5)
            ->get();

        // --- 4. Operations Dashboard (Sprint O4.2) ---
        
        $todaysWorkOrders = WorkOrder::query()
            ->with(['customer', 'vessel'])
            ->whereIn('status', ['planned', 'started', 'in_progress'])
            ->whereDate('planned_start_at', now())
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $overdueWorkOrders = WorkOrder::query()
            ->with(['customer', 'vessel'])
            ->whereDate('planned_end_at', '<', now())
            ->whereNotIn('status', ['completed', 'delivered', 'cancelled'])
            ->orderBy('planned_end_at')
            ->limit(8)
            ->get();

        $onHoldWorkOrders = WorkOrder::query()
            ->with(['customer', 'vessel'])
            ->where('status', 'on_hold')
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get();

        return view('dashboard', compact(
            'openInvoices',
            'overdueInvoices',
            'advances',
            'bankBalances',
            'stockAlertsCount',
            'quoteStats',
            'salesOrderStats',
            'workOrderStats',
            'financeStats',
            'recentActivity',
            'upcomingFollowUps',
            'criticalOverdueInvoices',
            'todaysWorkOrders',
            'overdueWorkOrders',
            'onHoldWorkOrders'
        ));
    }
}
