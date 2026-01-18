<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\EntitlementsService;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index(EntitlementsService $entitlements)
    {
        // Fix for 404: Use generic TenantContext which is guaranteed by EnsureTenantAdmin middleware
        // session('current_tenant_id') might be null if resolved via fallback logic
        $tenant = app(\App\Services\TenantContext::class)->getTenant();
        
        if (!$tenant && session('current_tenant_id')) {
             $tenant = Tenant::find(session('current_tenant_id'));
        }

        if (!$tenant) {
            // Should be caught by middleware, but safe fallback
            return redirect()->route('manage.tenants.select')->with('error', 'Firma seçimi yapılmadı.');
        }

        $account = $tenant->account;

        if (!$account) {
            return back()->with('error', 'Bu firmanın bağlı olduğu bir ana hesap (Account) bulunamadı.');
        }

        $tenantLimit = $entitlements->accountTenantLimit($account);
        $tenantUsage = $entitlements->accountTenantUsage($account);
        $seatLimit = $entitlements->accountSeatLimit($account);
        $seatUsage = $entitlements->accountSeatUsage($account);

        return view('manage.plan.index', compact(
            'account',
            'tenantLimit',
            'tenantUsage',
            'seatLimit',
            'seatUsage'
        ));
    }
}
