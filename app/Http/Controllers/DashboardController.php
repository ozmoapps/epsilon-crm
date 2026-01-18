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
        
        // PR8a: Safe Admin Detection
        $tenantId = app(\App\Services\TenantContext::class)->id();

        // PR14: Onboarding Guard (No Tenant)
        if (! $tenantId) {
             return view('dashboard', [
                'is_onboarding' => true,
                'openInvoices' => collect(),
                'overdueInvoices' => collect(),
                'advances' => collect(),
                'bankBalances' => collect(),
                'stockAlertsCount' => 0,
                'quoteStats' => (object)['total' => 0, 'draft' => 0, 'sent' => 0, 'accepted' => 0],
                'salesOrderStats' => (object)['total' => 0, 'active' => 0, 'completed' => 0],
                'workOrderStats' => (object)['total' => 0, 'planned' => 0, 'in_progress' => 0, 'completed' => 0],
                'financeStats' => ['invoiced' => collect(), 'collected' => collect()],
                'recentActivity' => collect(),
                'upcomingFollowUps' => collect(),
                'criticalOverdueInvoices' => collect(),
                'todaysWorkOrders' => collect(),
                'overdueWorkOrders' => collect(),
                'onHoldWorkOrders' => collect(),
                'opOpenSalesOrders' => 0,
                'opOpenWorkOrders' => 0,
                'opMissingPhotos' => 0,
                'opPendingDelivery' => 0,
                'recentOperations' => collect(),
                'canSeeFinance' => false
             ]);
        }

        $membership = $user->tenants()->where('tenants.id', $tenantId)->first();
        // Null-safe check: defaults to false if no membership or pivot data
        $isTenantAdmin = $membership?->pivot?->role === 'admin'; 

        // PR12: Finance Visibility Contract (Defense in Depth)
        $isPlatformAdmin = $user->is_admin;
        $hasSupportSession = session('support_session_id');
        $showTenantMenu = !$isPlatformAdmin || ($isPlatformAdmin && $hasSupportSession);
        
        $canSeeFinance = $showTenantMenu && ($isTenantAdmin || ($isPlatformAdmin && $hasSupportSession));

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

        // STRICT GATING: Financial queries only run if authorized
        if ($canSeeFinance) {
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
            ->where('tenant_id', $tenantId)
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

        // --- 5. Operations Summary (PR8) ---
        // Tenant Scoped by default trait, but adding defense-in-depth matches
        
        // 1) Açık Satış Siparişi
        $opOpenSalesOrders = SalesOrder::query()
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->count();

        // 2) Açık İş Emri
        $opOpenWorkOrders = WorkOrder::query()
            ->whereIn('status', ['planned', 'started', 'in_progress'])
            ->count();
            
        // 3) Foto Eksik (SalesOrder -> workOrder var ama photos yok)
        $opMissingPhotos = SalesOrder::query()
            ->whereHas('workOrder', function ($q) {
                $q->whereDoesntHave('photos');
            })
            ->count();

        // 4) Teslim Bekleyen (SalesOrder -> workOrder var, status != delivered)
        $opPendingDelivery = SalesOrder::query()
            ->whereHas('workOrder', function ($q) {
                $q->whereNotIn('status', ['delivered', 'completed', 'cancelled']);
            })
            ->count();
            
        // --- 6. Recent Operations Table (Last 10 Sales Orders) ---
        // Eager load customer, vessel, contract, workOrder (with photos count)
        // PR8a Fix: N+1
        $recentOperations = SalesOrder::query()
            ->with(['customer', 'vessel', 'contract', 'workOrder'])
            ->with(['workOrder' => function($q) {
                $q->withCount('photos');
            }])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($order) {
                // Calculate Next Step Logic
                $stepLabel = 'Bilinmiyor';
                $stepVariant = 'neutral';
                
                // N+1 Safe check (eager loaded)
                $hasContract = (bool) $order->contract;
                $hasWorkOrder = $order->workOrder;
                
                if (!$hasContract) {
                    $stepLabel = 'Sözleşme bekleniyor';
                    $stepVariant = 'neutral';
                } elseif (!$hasWorkOrder) {
                    $stepLabel = 'İş emri oluştur';
                    $stepVariant = 'info';
                } elseif ($hasWorkOrder && $order->workOrder->photos_count == 0) {
                    $stepLabel = 'Fotoğraflar eksik';
                    $stepVariant = 'info';
                } elseif ($hasWorkOrder && !in_array($order->workOrder->status, ['delivered', 'completed'])) {
                    $stepLabel = 'Teslimat bekleniyor';
                    $stepVariant = 'info';
                } else {
                    $stepLabel = 'Tamamlandı';
                    $stepVariant = 'success';
                }
                
                $order->next_step_label = $stepLabel;
                $order->next_step_variant = $stepVariant;
                
                return $order;
            });

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
            'onHoldWorkOrders',
            'opOpenSalesOrders',
            'opOpenWorkOrders',
            'opMissingPhotos',
            'opPendingDelivery',
            'recentOperations',
            'canSeeFinance'
        ));
    }
}
