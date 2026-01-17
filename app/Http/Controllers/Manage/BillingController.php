<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Services\EntitlementsService;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillingController extends Controller
{
    public function index(Request $request, EntitlementsService $entitlements)
    {
        $tenantId = session('current_tenant_id');
        if (!$tenantId) {
            abort(403, 'Tenant context missing.');
        }

        $tenant = Tenant::findOrFail($tenantId);
        $account = $tenant->account;

        if (!$account) {
             abort(404, 'Account not found.');
        }

        // Authorization: Check if user is Owner or Billing Admin for this Account
        $userRole = DB::table('account_users')
            ->where('account_id', $account->id)
            ->where('user_id', $request->user()->id)
            ->value('role');

        if (!in_array($userRole, ['owner', 'billing_admin'])) {
            // Check if user is the direct owner (fallback)
            if ($account->owner_user_id !== $request->user()->id) {
                 abort(403, 'Bu sayfayı görüntüleme yetkiniz yok. Sadece hesap sahibi erişebilir.');
            }
        }

        $account->load('plan');

        $tenantUsage = $entitlements->accountTenantUsage($account);
        $tenantLimit = $entitlements->accountTenantLimit($account);
        $seatUsage = $entitlements->accountSeatUsage($account);
        $seatLimit = $entitlements->accountSeatLimit($account);

        return view('manage.billing.index', compact(
            'account',
            'tenantUsage', 'tenantLimit',
            'seatUsage', 'seatLimit'
        ));
    }
}
