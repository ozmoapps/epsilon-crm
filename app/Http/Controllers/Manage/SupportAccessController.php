<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\SupportSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SupportAccessController extends Controller
{
    /**
     * Store (Generate) a new support access token.
     */
    public function store(Request $request, \App\Services\AuditLogger $logger)
    {
        if (! config('privacy.break_glass_enabled')) {
            return back()->with('error', 'Destek erişimi konfigürasyon tarafından devre dışı bırakılmış.');
        }

        // Check active session count? Maybe limit to 1 active per tenant to avoid clutter?
        // Let's just create a new one.

        $token = Str::random(64);
        $ttl = config('privacy.break_glass_ttl_minutes', 60);

        $session = SupportSession::create([
            'tenant_id' => session('current_tenant_id'), // middleware ensured
            'requested_by_user_id' => auth()->id(),
            'token_hash' => hash('sha256', $token),
            'approved_at' => now(),
            'expires_at' => now()->addMinutes($ttl),
        ]);

        $logger->log('support_session.created', [
            'ttl_minutes' => $ttl,
            'expires_at' => $session->expires_at,
            'support_session_id' => $session->id
        ], 'info');

        // Return the raw token to the user ONCE (via session flash)

        // Return the raw token to the user ONCE (via session flash)
        // Link format: /support/access/{token}
        $link = route('support.access', ['token' => $token]);

        return back()->with('support_link', $link)->with('success', 'Destek erişim kodu oluşturuldu. Bu linki platform yöneticisi ile paylaşın.');
    }

    /**
     * Revoke a support session.
     */
    public function destroy(SupportSession $session, \App\Services\AuditLogger $logger)
    {
        if ($session->tenant_id != session('current_tenant_id')) {
            abort(403);
        }

        $session->update(['revoked_at' => now()]);

        $logger->log('support_session.revoked', [
            'support_session_id' => $session->id
        ], 'info');

        return back()->with('success', 'Destek erişimi iptal edildi.');
    }
}
