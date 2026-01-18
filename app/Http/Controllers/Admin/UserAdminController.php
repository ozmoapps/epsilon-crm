<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class UserAdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::orderBy('name')->with('tenants')->paginate(50);
        // UI form icin aktif olanlari veya tümünü çekip view'da isleyebiliriz.
        // Talimat: Tüm tenantlari cek, pasifleri disabled yap.
        $tenants = \App\Models\Tenant::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'tenants'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'is_admin' => ['nullable', 'boolean'],
            'tenant_ids' => ['required', 'array', 'min:1'],
            'tenant_ids.*' => ['exists:tenants,id'], // Pasif kontrolünü loop icinde veya Rule ile yapabiliriz
        ]);

        // Pasif tenant eklenemez (Validation)
        $activeTenantCount = \App\Models\Tenant::whereIn('id', $validated['tenant_ids'])
            ->where('is_active', true)
            ->count();
        
        if ($activeTenantCount !== count($validated['tenant_ids'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['tenant_ids' => 'Pasif durumdaki firmalar seçilemez.']);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => $validated['is_admin'] ?? false,
        ]);

        // Sync Tenants (Default Role: admin for now)
        $user->tenants()->syncWithPivotValues($validated['tenant_ids'], ['role' => 'admin']);

        return redirect()->back()->with('success', 'Kullanıcı başarıyla oluşturuldu.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        // Check if this is a role/admin update or membership update?
        // Form could be sending tenant_ids OR is_admin toggle.
        // We will separate logic based on input presence or route usage in UI.
        // Current UI uses separate forms. The 'is_admin' form sends PATCH.
        // We should support tenant_ids update here too.

        $rules = [];
        if ($request->has('is_admin')) {
            $rules['is_admin'] = ['required', 'boolean'];
        }
        if ($request->has('tenant_ids')) {
            $rules['tenant_ids'] = ['required', 'array', 'min:1'];
            $rules['tenant_ids.*'] = ['exists:tenants,id'];
        }

        $validated = $request->validate($rules);

        // --- Logic: is_admin toggle ---
        if ($request->has('is_admin')) {
            if (!$validated['is_admin'] && $user->is_admin) {
                 if ($user->id === auth()->id()) {
                     $otherAdminsCount = User::where('is_admin', true)->where('id', '!=', $user->id)->count();
                     if ($otherAdminsCount === 0) {
                         return redirect()->back()->with('error', 'Sistemdeki son yönetici yetkilerini kaldıramazsınız.');
                     }
                 }
            }
            $user->update(['is_admin' => $validated['is_admin']]);
            return redirect()->back()->with('success', 'Kullanıcı yetkisi güncellendi.');
        }

        // --- Logic: Membership Update ---
        if ($request->has('tenant_ids')) {
            $newTenantIds = $validated['tenant_ids'];

            // 1. Guard: Self-removal from Active Tenant
            if ($user->id === auth()->id()) {
                $currentTenantId = session('current_tenant_id');
                // Fallback logical check (if session missing, but logic implies one is active)
                if (!$currentTenantId && auth()->user()->tenant_id) {
                     $currentTenantId = auth()->user()->tenant_id;
                }

                if ($currentTenantId && !in_array($currentTenantId, $newTenantIds)) {
                    // Check if current tenant is actually active/valid? Assuming yes if in session.
                    throw \Illuminate\Validation\ValidationException::withMessages(['tenant_ids' => 'Aktif olarak kullandığınız firmadan kendinizi çıkaramazsınız.']);
                }
            }

            // 2. Preserve Passive Tenants (if not in input because disabled)
            // Get user's current passive tenants
            $files = $user->tenants()->where('is_active', false)->pluck('tenants.id')->toArray();
            
            // Merge request IDs with existing passive IDs
            $finalIds = array_unique(array_merge($newTenantIds, $files));
            
            // Check if user tried to ADD a passive tenant that wasn't there (security)
            // If any ID in $newTenantIds corresponds to a passive tenant THAT WAS NOT in $files
            $passiveRequested = \App\Models\Tenant::whereIn('id', $newTenantIds)->where('is_active', false)->pluck('id')->toArray();
            foreach ($passiveRequested as $pid) {
                if (!in_array($pid, $files)) {
                     throw \Illuminate\Validation\ValidationException::withMessages(['tenant_ids' => 'Pasif durumdaki bir firma yeni üye olarak eklenemez.']);
                }
            }

            $user->tenants()->syncWithPivotValues($finalIds, ['role' => 'admin']);
            return redirect()->back()->with('success', 'Firma üyelikleri güncellendi.');
        }

        return redirect()->back()->with('success', 'Kullanıcı güncellendi.');
    }

    /**
     * Update the user's password.
     */
    public function password(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->back()->with('success', 'Şifre güncellendi.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Kendinizi silemezsiniz.');
        }

        // 1. Check for Critical Ownership
        if ($user->ownedAccounts()->exists()) {
             return redirect()->back()->with('error', 'Kullanıcı bir veya daha fazla hesabın sahibi olduğu için silinemez. Lütfen önce sahipliği devredin.');
        }

        try {
            // Detach known pivot relations if not cascaded automatically
            $user->tenants()->detach();
            $user->accounts()->detach();
            
            $user->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == 23000) {
                 return redirect()->back()->with('error', 'Kullanıcı silinemedi: İlişkili kayıtlar (örneğin Denetim Günlüğü, Destek Oturumları) mevcut. Kullanıcıyı pasife almayı deneyin.');
            }
            throw $e;
        }

        return redirect()->back()->with('success', 'Kullanıcı silindi.');
    }

    public function storeInvitation(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'lowercase'],
            'tenant_id' => ['required', 'exists:tenants,id'],
        ]);

        $tenant = \App\Models\Tenant::findOrFail($validated['tenant_id']);
        if (! $tenant->is_active) {
            return back()->with('error', 'Pasif firmaya davet gönderilemez.');
        }

        // Revoke existing pending invites for this email+tenant
        \App\Models\TenantInvitation::pending()
            ->where('tenant_id', $tenant->id)
            ->where('email', $validated['email'])
            ->delete(); // Soft delete if we had it, but hard delete is fine for "revoking" per spec

        // Create new token
        $token = \Illuminate\Support\Str::random(64);
        
        \App\Models\TenantInvitation::create([
            'tenant_id' => $tenant->id,
            'email' => $validated['email'],
            'token_hash' => hash('sha256', $token),
            'role' => 'staff', // Default role for now
            'expires_at' => now()->addDays(7),
        ]);

        // Generate Link
        $link = \App\Models\TenantInvitation::generateLink($tenant, $token);

        return back()->with('success', 'Davet oluşturuldu.')
                     ->with('invite_link', $link); // Flash for UI copy
    }
}
