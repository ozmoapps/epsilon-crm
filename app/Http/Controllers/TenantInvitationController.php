<?php

namespace App\Http\Controllers;

use App\Models\TenantInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Tenant;

class TenantInvitationController extends Controller
{
    public function show(Request $request, $token)
    {
        // 1. Validate Token (without db-heavy check first if possible, but hash needs db)
        // Hash comparison
        $tokenHash = hash('sha256', $token);
        
        $invitation = TenantInvitation::where('token_hash', $tokenHash)
            ->with('tenant')
            ->first();

        // 2. Check Validity
        if (! $invitation) {
            abort(404, 'Davet bulunamadı.');
        }

        if ($invitation->accepted_at) {
             return redirect()->route('dashboard')->with('info', 'Bu davet zaten kabul edilmiş.');
        }

        if ($invitation->expires_at->isPast()) {
             abort(403, 'Davet süresi dolmuş.');
        }

        // 3. Guest Logic
        if (! Auth::check()) {
            // Store token in session to handle after login
            session(['invite_token' => $token]);
            return redirect()->route('login')->with('status', 'Daveti kabul etmek için lütfen giriş yapın veya kayıt olun.');
        }

        // 4. Auth Logic
        // Show Acceptance View
        return view('invitations.show', ['invitation' => $invitation, 'token' => $token]);
    }

    public function accept(Request $request, $token, \App\Services\AuditLogger $logger)
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $tokenHash = hash('sha256', $token);
        $invitation = TenantInvitation::valid()
            ->where('token_hash', $tokenHash)
            ->firstOrFail();

        // Email Match Check (Strict, Case-Insensitive)
        if (strtolower($user->email) !== strtolower($invitation->email)) {
             $maskedInviteEmail = preg_replace('/^(.{1})(.*)(@.{1})(.*)(\..{2,})/', '$1***$3***$5', $invitation->email);
             $maskedUserEmail = preg_replace('/^(.{1})(.*)(@.{1})(.*)(\..{2,})/', '$1***$3***$5', $user->email);
             
             abort(403, 'Bu davet ' . $maskedInviteEmail . ' adresi için geçerlidir. Mevcut hesabınız: ' . $maskedUserEmail);
        }

        $tenant = $invitation->tenant;

        // Limit Enforcement (PR4C2)
        $entitlements = app(\App\Services\EntitlementsService::class);
        $account = $tenant->account;

        if ($account && !$entitlements->canAddSeat($account, $user->email)) {
             $logger->log('entitlement.blocked', [
                 'type' => 'seat_limit',
                 'limit' => $entitlements->accountSeatLimit($account),
                 'usage' => $entitlements->accountSeatUsage($account),
                 'tenant_id' => $tenant->id,
                 'reason' => 'join_attempt'
             ], 'warn');

             abort(403, 'Plan limitine ulaşıldı. Paketi yükseltmeniz gerekiyor.');
        }

        // Idempotent Attach
        $user->tenants()->syncWithoutDetaching([
            $invitation->tenant_id => ['role' => $invitation->role]
        ]);
        
        // Sync Account User
        if ($account) {
            $entitlements->syncAccountUser($account, $user, 'member');
        }

        // Mark Accepted
        $invitation->update([
            'accepted_at' => now(),
            'accepted_by_user_id' => $user->id
        ]);

        // Redirect Logic
        // $tenant loaded earlier
        
        // 1. Domain Redirect Check
        if (config('tenancy.resolve_by_domain') && $tenant->domain && $tenant->domain !== $request->getHost()) {
            $scheme = $request->getScheme();
             // Token/Session isn't carried over x-domain usually without generic login, 
             // but user is already member now. So redirecting to login on that domain 
             // will prompt login, OR if session driver is central (implied by setup), it works.
             // If local test (test host), might be tricky, but assuming valid domain setup.
            return redirect()->away($scheme . '://' . $tenant->domain . '/dashboard')
                ->with('success', 'Davet kabul edildi ve firmaya yönlendirildiniz.');
        }

        // 2. Normal Redirect (Same Domain / Subdomain or No Domain Mode)
        // Explicitly set session for context
        session(['current_tenant_id' => $tenant->id]);
        
        return redirect()->route('dashboard')->with('success', 'Davet kabul edildi: ' . $tenant->name);
    }
}
