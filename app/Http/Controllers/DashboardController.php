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
        // --- 1. KPI Cards Data ---

        // Open Invoices (Issued/Sent but not Paid) - Grouped by Currency
        $openInvoices = Invoice::query()
            ->whereIn('status', ['issued', 'sent'])
            ->where('payment_status', '!=', 'paid')
            ->select('currency', DB::raw('count(*) as count'), DB::raw('sum(total) as total_amount'))
            ->groupBy('currency')
            ->get();

        // Overdue Invoices - Grouped by Currency
        $overdueInvoices = Invoice::query()
            ->whereIn('status', ['issued', 'sent'])
            ->where('payment_status', '!=', 'paid')
            ->where('due_date', '<', now()->startOfDay())
            ->select('currency', DB::raw('count(*) as count'), DB::raw('sum(total) as total_amount'))
            ->groupBy('currency')
            ->get();

        // Advances (Payments without Invoice) - Approximate "Available"
        // For V2, we strictly look at Payments with NO Invoice ID associated.
        // We sum 'original_amount' and group by 'original_currency' (or fallback to currency).
        // Advances (Payments without Invoice) - Approximate "Available"
        // For V2, we strictly look at Payments with NO Invoice ID associated.
        // We sum 'original_amount' and group by 'original_currency' (or fallback to currency).
        // Note: Ideally we should subtract allocations, but for V2 "Unallocated" logic might be heavy.
        // We will assume "Independent Payments" are advances.
        $advances = Payment::query()
            ->whereNull('invoice_id')
            ->selectRaw('COALESCE(original_currency, "EUR") as currency, count(*) as count, sum(COALESCE(original_amount, amount)) as total_amount')
            ->groupBy('currency')
            ->get();

        // Bank Balances
        // Since getBalanceAttribute is heavy (N+1), we do a simpler aggregation if possible
        // or just iterate if account count is low. We'll iterate as accounts are usually few.
        $bankAccounts = BankAccount::with(['currency'])->get();
        $bankBalances = $bankAccounts->groupBy(fn ($acc) => $acc->currency->code ?? 'EUR')
            ->map(function ($accounts, $currency) {
                return [
                    'currency' => $currency,
                    'total_balance' => $accounts->sum(fn ($acc) => $acc->balance),
                    'count' => $accounts->count(),
                ];
            })->values();

        // Stock Alerts (Products below critical level)
        // We compare sum of inventory_balances.qty_on_hand vs product.critical_stock_level
        $stockAlertsCount = Product::query()
            ->where('track_stock', true)
            ->whereRaw('(SELECT COALESCE(SUM(qty_on_hand), 0) FROM inventory_balances WHERE product_id = products.id) <= critical_stock_level')
            ->count();


        // --- 2. Main Panel Modals ---

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

        // Workload
        $workOrderStats = WorkOrder::selectRaw('
            count(*) as total,
            sum(case when status = "open" then 1 else 0 end) as open,
            sum(case when status = "in_progress" then 1 else 0 end) as in_progress,
            sum(case when status = "planned" then 1 else 0 end) as planned
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


        // --- 3. Sidebar / Feeds ---

        // Recent Activity (Use ActivityLog)
        $recentActivity = ActivityLog::with(['actor', 'subject'])
            ->latest('created_at')
            ->limit(8)
            ->get();

        // Follow Ups (Today + Overdue + Next 7 Days)
        $upcomingFollowUps = FollowUp::query()
            ->open()
            ->where('next_at', '<=', now()->addDays(7))
            ->with(['subject', 'creator'])
            ->orderBy('next_at')
            ->limit(5)
            ->get();

        // Critical Alerts (Specific Items)
        $criticalOverdueInvoices = Invoice::query()
            ->with('customer')
            ->where('status', 'issued') // Only issued can be overdue in strict sense
            ->where('payment_status', '!=', 'paid')
            ->where('due_date', '<', now()->subDays(1)) // Grace period 1 day
            ->orderBy('due_date')
            ->limit(5)
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
            'criticalOverdueInvoices'
        ));
    }
}
