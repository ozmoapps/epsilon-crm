<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AuditLog;
use App\Services\EntitlementsService;
use Illuminate\Http\Request;

class AccountAdminController extends Controller
{
    public function index(EntitlementsService $entitlements)
    {
        $accounts = Account::with(['plan', 'owner', 'tenants'])->latest()->paginate(20);

        // Calculate usage for list (n+1 optimization needed ideally, but keeping it simple for v4d3)
        // We can loop locally since paginated.
        
        return view('admin.accounts.index', [
            'accounts' => $accounts,
            'entitlements' => $entitlements
        ]);
    }

    public function show(Account $account, EntitlementsService $entitlements)
    {
        $account->load(['plan', 'owner', 'tenants']);

        // Usage Metrics
        $tenantUsage = $entitlements->accountTenantUsage($account);
        $tenantLimit = $entitlements->accountTenantLimit($account);
        $seatUsage = $entitlements->accountSeatUsage($account);
        $seatLimit = $entitlements->accountSeatLimit($account);

        // Audit Summary (Last 24h Blocked Events)
        // We gather tenant IDs to filter logs if we want specific account logs.
        // AuditLogs for 'entitlement.blocked' might have tenant_id in metadata or column.
        // Check AuditLog structure. Usually 'tenant_id' column is nullable.
        // If the blockage happened at account level (tenant creation), it might not have tenant_id?
        // Actually TenantAdminController creation block happens BEFORE tenant is created. 
        // But the audit log logic usually logs 'tenant_id' if available. 
        // For 'tenant_limit' block, we assume 'tenant_id' might be null in log?
        // Let's look at EntitlementsService usage in TenantAdminController (previous PR).
        // Wait, EntitlementsService usually just returns bool. The CONTROLLER logs.
        // In TenantAdminController verification (v4d1), we saw it logs.
        
        // Let's filter by the tenants of this account for seat limits.
        // For tenant limits, it might be tricky if no tenant exists. 
        // But generally we can filter by 'metadata->account_id' if we logged it?
        // Or if we logged 'tenant_id' for seat blocks.
        
        $tenantIds = $account->tenants->pluck('id');
        
        $blockedEvents = AuditLog::where('event_key', 'entitlement.blocked')
            ->where('created_at', '>=', now()->subDay())
            ->where(function($q) use ($tenantIds) {
                // If log has tenant_id column
                $q->whereIn('tenant_id', $tenantIds);
                // Or maybe future logs will have account_id. For now stick to tenant_id.
            })
            ->latest()
            ->get();
            
        // Group reason counts
        $stats = $blockedEvents->groupBy(fn($i) => $i->metadata['type'] ?? 'unknown')
            ->map->count();

        return view('admin.accounts.show', compact(
            'account', 
            'tenantUsage', 'tenantLimit', 
            'seatUsage', 'seatLimit',
            'blockedEvents',
            'stats'
        ));
    }
    public function update(Request $request, Account $account, EntitlementsService $entitlements, \App\Services\AuditLogger $logger)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'extra_seats_purchased' => 'required|integer|min:0',
        ]);

        $newPlanId = $validated['plan_id'];
        $newExtraSeats = $validated['extra_seats_purchased'];

        // Downgrade / Limit Check logic
        // We must fetch the target plan to know its limits
        $targetPlan = \App\Models\Plan::find($newPlanId);
        
        // 1. Check Tenant Limit
        $targetTenantLimit = $targetPlan->tenant_limit; // No extra tenant purchase logic yet, just plan
        $currentTenantUsage = $entitlements->accountTenantUsage($account);
        
        if ($targetTenantLimit !== null && $currentTenantUsage > $targetTenantLimit) {
             $logger->log(
                'entitlement.blocked',
                [
                    'account_id' => $account->id,
                    'type' => 'downgrade_blocked',
                    'reason' => 'tenant_limit_exceeded',
                    'current' => $currentTenantUsage,
                    'target' => $targetTenantLimit,
                    'message' => 'Firma limiti yetersiz, paket değişikliği engellendi.'
                ],
                'warning'
            );
            return back()->with('error', "Paket değişikliği yapılamıyor. Mevcut firma sayısı ($currentTenantUsage) yeni paketin limitini ($targetTenantLimit) aşıyor.");
        }

        // 2. Check Seat Limit
        $targetSeatLimit = $targetPlan->seat_limit;
        if ($targetSeatLimit !== null) {
            $targetSeatLimit += $newExtraSeats;
        }
        
        $currentSeatUsage = $entitlements->accountSeatUsage($account);
        
        if ($targetSeatLimit !== null && $currentSeatUsage > $targetSeatLimit) {
             $logger->log(
                'entitlement.blocked',
                [
                    'account_id' => $account->id,
                    'type' => 'downgrade_blocked',
                    'reason' => 'seat_limit_exceeded',
                    'current' => $currentSeatUsage,
                    'target' => $targetSeatLimit,
                    'message' => 'Kullanıcı (seat) limiti yetersiz, paket değişikliği engellendi.'
                ],
                'warning'
            );
            return back()->with('error', "Paket değişikliği yapılamıyor. Mevcut kullanıcı sayısı ($currentSeatUsage) yeni limiti ($targetSeatLimit) aşıyor.");
        }

        // Proceed with Update
        $oldPlanId = $account->plan_id;
        $oldExtraSeats = $account->extra_seats_purchased;

        $account->update([
            'plan_id' => $newPlanId,
            'extra_seats_purchased' => $newExtraSeats
        ]);

        // Audit Logs for Changes
        if ($oldPlanId != $newPlanId) {
             $logger->log('account.plan.changed', ['account_id' => $account->id, 'old' => $oldPlanId, 'new' => $newPlanId], 'info');
        }
        if ($oldExtraSeats != $newExtraSeats) {
             $logger->log('account.seats.changed', ['account_id' => $account->id, 'old' => $oldExtraSeats, 'new' => $newExtraSeats], 'info');
        }


        return back()->with('success', 'Hesap güncellendi.');
    }
}

