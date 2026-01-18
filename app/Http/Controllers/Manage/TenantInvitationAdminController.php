<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\TenantInvitation;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TenantInvitationAdminController extends Controller
{
    public function index()
    {
        $tenantId = session('current_tenant_id');
        
        $invitations = TenantInvitation::where('tenant_id', $tenantId)
            ->with(['acceptedBy'])
            ->latest()
            ->get();

        return view('manage.invitations.index', compact('invitations'));
    }

    public function store(Request $request, \App\Services\AuditLogger $logger)
    {
        $tenantId = session('current_tenant_id');
        $tenant = Tenant::findOrFail($tenantId);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::in(['admin', 'staff', 'viewer'])],
        ]);

        // Check for pending invitations for this email in this tenant
        $existing = TenantInvitation::where('tenant_id', $tenantId)
            ->where('email', $validated['email'])
            ->pending()
            ->first();
            
        if ($existing) {
            return back()->with('error', 'Bu e-posta adresi için zaten bekleyen bir davet var.');
        }

        // Check if user is already a member
        $isMember = $tenant->users()->where('email', $validated['email'])->exists();
        if ($isMember) {
            return back()->with('error', 'Bu kullanıcı zaten firmanın üyesi.');
        }

        // Limit Enforcement (PR4C2)
        $entitlements = app(\App\Services\EntitlementsService::class);
        $account = $tenant->account;
        
        if ($account && !$entitlements->canAddSeat($account, $validated['email'])) {
             // Pass email to check "duplicate seat" bypass
             $logger->log('entitlement.blocked', [
                 'type' => 'seat_limit',
                 'limit' => $entitlements->accountSeatLimit($account),
                 'usage' => $entitlements->accountSeatUsage($account),
                 'pending_invites_counted' => true // assuming service logic
             ], 'warn');

             return back()->with('error', 'Plan limitine ulaşıldı. Paketi yükseltmeniz gerekiyor.');
        }

        $token = Str::random(64);
        
        $invitation = TenantInvitation::create([
            'tenant_id' => $tenant->id,
            'email' => $validated['email'],
            'token_hash' => hash('sha256', $token),
            'role' => $validated['role'],
            'expires_at' => now()->addDays(7),
        ]);

        $link = TenantInvitation::generateLink($tenant, $token);

        return back()->with('success', 'Davet oluşturuldu.')
                     ->with('invite_link', $link);
    }

    public function regenerate(TenantInvitation $invitation)
    {
        // Scope Check
        if ($invitation->tenant_id != session('current_tenant_id')) {
            abort(404);
        }

        if ($invitation->accepted_at) {
            return back()->with('error', 'Kabul edilmiş davet yenilenemez.');
        }

        $token = Str::random(64);
      
        $invitation->update([
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(7),
        ]);
        
        $link = TenantInvitation::generateLink($invitation->tenant, $token);

        return back()->with('success', 'Davet yenilendi.')
                     ->with('invite_link', $link);
    }

    public function destroy(TenantInvitation $invitation)
    {
        // Scope Check
        if ($invitation->tenant_id != session('current_tenant_id')) {
            abort(404);
        }

        if ($invitation->accepted_at) {
            return back()->with('error', 'Kabul edilmiş davet silinemez.');
        }

        $invitation->delete();

        return back()->with('success', 'Davet iptal edildi.');
    }
}
