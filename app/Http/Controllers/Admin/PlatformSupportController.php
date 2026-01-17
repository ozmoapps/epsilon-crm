<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportSession;
use Illuminate\Http\Request;

class PlatformSupportController extends Controller
{
    /**
     * Handle the support access link.
     */
    public function __invoke(Request $request, $token, \App\Services\AuditLogger $logger)
    {
        if (! config('privacy.break_glass_enabled')) {
            abort(403, 'Destek erişimi sistemi kapalı.');
        }

        if (! auth()->check() || ! auth()->user()->is_admin) {
            abort(403, 'Sadece platform yöneticileri destek erişimini kullanabilir.');
        }

        $tokenHash = hash('sha256', $token);

        $session = SupportSession::where('token_hash', $tokenHash)->first();

        // Validate Session
        if (! $session) {
            abort(403, 'Geçersiz destek erişim kodu.');
        }

        if (! $session->isValid()) {
            abort(403, 'Bu destek erişim kodunun süresi dolmuş veya iptal edilmiş.');
        }

        // Mark as used if first time
        if (! $session->used_at) {
            $session->update(['used_at' => now()]);
            
            $logger->log('support_session.used', [
                'tenant_id' => $session->tenant_id,
                'support_session_id' => $session->id,
                'expires_at' => $session->expires_at
            ], 'warn');
        }

        // Activate Session
        // We set specific keys to bypass the privacy lock in SetTenant middleware.
        session([
            'support_session_id' => $session->id,
            'support_tenant_id' => $session->tenant_id,
            'current_tenant_id' => $session->tenant_id // Also set normal context fallback
        ]);

        return redirect()->route('dashboard')->with('success', 'Destek erişimi aktif. ' . $session->tenant->name . ' firmasına geçici erişim sağlandı.');
    }
}
