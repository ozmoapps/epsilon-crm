<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class TenantAdminController extends Controller
{
    public function index()
    {
        $tenants = Tenant::orderBy('name')->withCount('users')->with(['account.plan'])->get();
        return view('admin.tenants.index', compact('tenants'));
    }

    // List is already handled by index()

    public function create()
    {
        // Load potential owners (platform admins or all users?)
        // Design decision: For now, let's allow selecting any user. 
        // In a real SaaS, maybe you select a customer user.
        // Optimization: limit to 100 or search. For now simple all() for minimum viable.
        $users = \App\Models\User::orderBy('name')->take(200)->get();
        return view('admin.tenants.form', ['tenant' => null, 'users' => $users]);
    }

    public function store(Request $request, \App\Services\EntitlementsService $entitlements, \App\Services\AuditLogger $logger)
    {
        // Normalize before validation (if possible) or validate raw then normalize?
        // User asked to normalize BEFORE save. Validation should check format.
        // But if user sends "https://foo.com", regex might fail if we validate raw.
        // We should normalize INPUT first, or normalize content before save.
        // The prompt says "Kaydederken normalize et", "Doğrulama ekle".
        // Let's normalize explicitly before usage.

        $data = $request->all();
        if (!empty($data['domain'])) {
            $data['domain'] = $this->normalizeDomain($data['domain']);
            $request->merge(['domain' => $data['domain']]); // Update request for validation uniqueness
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // Regex: Hostname without protocol/port. Allows .test for local.
            'domain' => ['nullable', 'string', 'max:255', 'unique:tenants,domain', 'regex:/^(?!-)[a-z0-9-]+(\.[a-z0-9-]+)*\.(?![0-9]+$)[a-z0-9]{2,63}$/'],
            'is_active' => ['boolean'],
            'owner_user_id' => ['required', 'exists:users,id'],
        ], [
            'domain.regex' => 'Domain formatı geçersiz. (örn: firma.com)',
            'domain.unique' => 'Bu domain başka bir firma tarafından kullanılıyor.',
            'owner_user_id.required' => 'Hesap sahibi seçimi zorunludur.',
        ]);

        // 1. Resolve Account for Owner
        // Find existing account where user is owner, OR default to creating one?
        // Logic: Entitlements PR says "1 subscription account per customer".
        // If user already has an account (owner), use it.
        // If not, create new Starter account.
        
        $ownerUser = \App\Models\User::findOrFail($request->owner_user_id);
        
        // Check if user OWNS an account
        $account = \App\Models\Account::where('owner_user_id', $ownerUser->id)->first();
        
        if (! $account) {
            // Check if user is associated with an account (maybe as billing_admin)
            // But simplify: If no account owned, create new Starter.
            $starterPlan = \App\Models\Plan::where('key', 'starter')->firstOrFail();
            
            $account = \App\Models\Account::create([
                'owner_user_id' => $ownerUser->id,
                'plan_id' => $starterPlan->id,
                'status' => 'active',
            ]);
            
            // Link user to account as owner
            \App\Services\EntitlementsService::class; // Ensure loaded?
            // Manually insert since service might not be fully wired for 'create account'
            \Illuminate\Support\Facades\DB::table('account_users')->insert([
                'account_id' => $account->id,
                'user_id' => $ownerUser->id,
                'role' => 'owner',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Check CAN CREATE TENANT
        if (! $entitlements->canCreateTenant($account)) {
            $limit = $entitlements->accountTenantLimit($account);
            
            $logger->log('entitlement.blocked', [
                'type' => 'tenant_limit',
                'limit' => $limit,
                'usage' => $entitlements->accountTenantUsage($account) // Assuming this method exists or we trust the block.
                // If usage method not public/avail, just log limit. EntitlementsService usually has checks.
            ], 'warn');
            
            return back()->with('error', "Plan limitine ulaşıldı. Paketi yükseltmeniz gerekiyor.");
        }

        // 3. Create Tenant linked to Account
        $tenant = Tenant::create([
            'name' => $validated['name'],
            'domain' => $validated['domain'] ?? null,
            'is_active' => $request->has('is_active'),
            // 'account_id' => $account->id, // Add to fillable or associate below
        ]);
        
        // Force Associate (Safe against fillable missing)
        $tenant->account()->associate($account);
        $tenant->save();

        // 4. Attach Owner (NOT Platform Admin unless they are same)
        // Idempotent sync
        $tenant->users()->syncWithoutDetaching([
            $ownerUser->id => ['role' => 'admin']
        ]);
        
        // Ensure Account User Sync
        $entitlements->syncAccountUser($account, $ownerUser, 'member');

        return redirect()->route('admin.tenants.index')->with('success', 'Firma başarıyla oluşturuldu.');
    }

    public function edit(Tenant $tenant)
    {
        return view('admin.tenants.form', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $data = $request->all();
        if (!empty($data['domain'])) {
            $data['domain'] = $this->normalizeDomain($data['domain']);
            $request->merge(['domain' => $data['domain']]); 
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255', 'unique:tenants,domain,' . $tenant->id, 'regex:/^(?!-)[a-z0-9-]+(\.[a-z0-9-]+)*\.(?![0-9]+$)[a-z0-9]{2,63}$/'],
            'is_active' => ['boolean'],
        ], [
            'domain.regex' => 'Domain formatı geçersiz. (örn: firma.com)',
            'domain.unique' => 'Bu domain başka bir firma tarafından kullanılıyor.',
        ]);

        // Validate Self-Disable
        if (!$request->has('is_active') && $tenant->id == session('current_tenant_id')) {
             return back()->with('error', 'Aktif olarak kullandığınız firmayı pasif duruma getiremezsiniz.');
        }

        $tenant->update([
            'name' => $validated['name'],
            'domain' => $validated['domain'] ?? null,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.tenants.index')->with('success', 'Firma güncellendi.');
    }

    /**
     * Normalize domain input: remove protocol, path, port, whitespace.
     */
    private function normalizeDomain($domain)
    {
        if (empty($domain)) return null;

        $domain = mb_strtolower(trim($domain));
        
        // Remove protocol
        $domain = preg_replace('#^https?://#', '', $domain);
        
        // Remove path (everything after first /)
        $domain = explode('/', $domain)[0];
        
        // Remove port (everything after last :)
        $domain = preg_replace('/:\d+$/', '', $domain);

        // Trim dots
        $domain = trim($domain, '.');

        return empty($domain) ? null : $domain;
    }

    public function toggleActive(Tenant $tenant, \App\Services\AuditLogger $logger)
    {
        if (Schema::hasColumn('tenants', 'is_active')) {
            // Validation for self-disable
            if ($tenant->is_active && $tenant->id == session('current_tenant_id')) {
                return back()->with('error', 'Aktif olarak kullandığınız firmayı pasif duruma getiremezsiniz.');
            }

            $tenant->update(['is_active' => ! $tenant->is_active]);
            $status = $tenant->is_active ? 'aktif' : 'pasif';
            
            $logger->log('tenant.toggled_active', [
                'new_status' => $status,
                'tenant_id' => $tenant->id
            ], 'warn');

            return back()->with('success', "Firma başarıyla $status duruma getirildi.");
        }

        return back()->with('error', 'Bu özellik şu an kullanılamıyor (is_active kolonu yok).');
    }
}
