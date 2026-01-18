<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\PlanChangeRequest;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlanChangeRequestController extends Controller
{
    public function index()
    {
        $tenant = app(TenantContext::class)->getTenant();
        if (!$tenant) {
            abort(403, 'Firma bağlamı bulunamadı.');
        }

        $account = $tenant->account;
        if (!$account) {
            abort(404, 'Hesap bulunamadı.');
        }

        $requests = PlanChangeRequest::where('account_id', $account->id)
            ->where('requested_by_user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('manage.plan.requests.index', compact('requests'));
    }
    public function create()
    {
        $tenant = app(TenantContext::class)->getTenant();
        
        // Ensure tenant context (Guard against direct access without context)
        if (!$tenant) {
            abort(403, 'Firma bağlamı bulunamadı.');
        }

        $account = $tenant->account;
        if (!$account) {
            abort(404, 'Hesap bulunamadı.');
        }

        $currentPlanKey = $account->plan_key ?? 'starter';
        
        // Filter plans: Only show upgrades (higher or equal sort order? Or just different?)
        // Assuming plans are defined in config/plans.php
        $allPlans = config('plans', []);
        
        // We want to show plans that are "better" or just all except current?
        // Requirement: "Sadece yükseltme talebi".
        // Let's filter out current plan.
        // Determining "Higher" might be tricky without a sort_order.
        // For now, let's show all EXCEPT current. Validation will block lower if we had a hierarchy.
        // User requested: "requested_plan_key daha düşükse (downgrade) bu PR7d scope değil (biz sadece upgrade isteyeceğiz)"
        // Since we verify manually, let's just exclude current for now, or define hierarchy logic if strictly needed.
        // Simplicity: Exclude current.
        
        // Defined hierarchy for upgrade logic
        $planOrder = [
            'starter' => 1,
            'team' => 2,
            'enterprise' => 3,
        ];

        $currentOrder = $planOrder[$currentPlanKey] ?? 0;
        
        $upgradeablePlans = collect($allPlans)->filter(function ($plan, $key) use ($currentOrder, $planOrder) {
            $order = $planOrder[$key] ?? 0;
            return $order > $currentOrder;
        })->toArray();

        // Optional: Check if there is already a pending request?
        $pendingRequest = PlanChangeRequest::where('account_id', $account->id)
            ->where('status', 'pending')
            ->first();

        // If pending exists, maybe show a status page or warning?
        // For minimal scope, pass it to view to disable form.

        return view('manage.plan.requests.create', [
            'tenant' => $tenant,
            'account' => $account,
            'currentPlanKey' => $currentPlanKey,
            'plans' => $upgradeablePlans,
            'pendingRequest' => $pendingRequest
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'requested_plan_key' => 'required|string',
            'reason' => 'nullable|string|max:1000',
        ]);

        $tenant = app(TenantContext::class)->getTenant();
        if (!$tenant) {
            abort(403, 'Firma bağlamı bulunamadı.');
        }

        $account = $tenant->account;
        if (!$account) {
            abort(404, 'Hesap bulunamadı.');
        }

        $currentPlanKey = $account->plan_key ?? 'starter';
        $requestedPlanKey = $request->requested_plan_key;

        // Validation 1: Same plan
        if ($currentPlanKey === $requestedPlanKey) {
            return back()->with('error', 'Mevcut paketiniz zaten bu.');
        }

        // Validation 2: Plan existence
        $allPlans = config('plans', []);
        if (!array_key_exists($requestedPlanKey, $allPlans)) {
            return back()->with('error', 'Geçersiz paket seçimi.');
        }

        // Validation 3: Pending Check
        $exists = PlanChangeRequest::where('account_id', $account->id)
            ->where('status', 'pending')
            ->exists();
        
        if ($exists) {
            return back()->with('error', 'Zaten bekleyen bir talebiniz var.');
        }

        // Create Request
        PlanChangeRequest::create([
            'account_id' => $account->id,
            'tenant_id' => $tenant->id,
            'requested_by_user_id' => auth()->id(),
            'current_plan_key' => $currentPlanKey,
            'requested_plan_key' => $requestedPlanKey,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return redirect()->route('manage.plan.index')->with('success', 'Paket yükseltme talebiniz alındı. Platform yöneticisi inceleyecek.');
    }
}
