<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlanChangeRequest;
use App\Models\Account;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class PlanChangeRequestAdminController extends Controller
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function index(Request $request)
    {
        // Status filter (default: pending)
        $status = $request->input('status', 'pending');
        
        $requests = PlanChangeRequest::with(['account', 'tenant', 'requester'])
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->latest()
            ->paginate(20);
            
        return view('admin.plan-change-requests.index', [
            'requests' => $requests,
            'currentStatus' => $status,
        ]);
    }

    public function show(PlanChangeRequest $planChangeRequest)
    {
        $planChangeRequest->load(['account', 'tenant', 'requester', 'reviewer']);
        
        return view('admin.plan-change-requests.show', [
            'request' => $planChangeRequest,
            'plans' => config('plans.plans', [])
        ]);
    }

    public function approve(Request $request, PlanChangeRequest $planChangeRequest)
    {
        if ($planChangeRequest->status !== 'pending') {
            return back()->with('error', 'Bu talep zaten işlem görmüş.');
        }

        $request->validate([
            'review_note' => 'nullable|string|max:1000',
        ]);

        $account = $planChangeRequest->account;
        $oldPlan = $account->plan_key;
        $newPlan = $planChangeRequest->requested_plan_key;

        // Apply Plan Change
        $account->plan_key = $newPlan;
        $account->save();

        // Update Request
        $planChangeRequest->update([
            'status' => 'approved',
            'reviewed_by_user_id' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => $request->review_note,
        ]);

        // Audit Log
        $this->auditLogger->log('plan.request.approved', [
            'request_id' => $planChangeRequest->id,
            'account_id' => $account->id,
            'tenant_id' => $planChangeRequest->tenant_id,
            'from' => $oldPlan,
            'to' => $newPlan,
            'reviewer_id' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.plan_requests.index')
            ->with('success', 'Talep onaylandı ve paket güncellendi.');
    }

    public function reject(Request $request, PlanChangeRequest $planChangeRequest)
    {
        if ($planChangeRequest->status !== 'pending') {
            return back()->with('error', 'Bu talep zaten işlem görmüş.');
        }

        $request->validate([
            'review_note' => 'nullable|string|max:1000',
        ]);

        // Update Request
        $planChangeRequest->update([
            'status' => 'rejected',
            'reviewed_by_user_id' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => $request->review_note,
        ]);

        // Audit Log
        $this->auditLogger->log('plan.request.rejected', [
            'request_id' => $planChangeRequest->id,
            'account_id' => $planChangeRequest->account_id,
            'tenant_id' => $planChangeRequest->tenant_id,
            'requested_plan' => $planChangeRequest->requested_plan_key,
            'reviewer_id' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.plan_requests.index')
            ->with('success', 'Talep reddedildi.');
    }
}
