<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TenantInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    public function index()
    {
        $invitations = TenantInvitation::with(['tenant', 'acceptedBy'])
            ->latest()
            ->get();

        return view('admin.invitations.index', compact('invitations'));
    }

    public function destroy(TenantInvitation $invitation)
    {
        // Don't allow destroying accepted invitations to preserve history/audit
        if ($invitation->accepted_at) {
            return back()->with('error', 'Kabul edilmiş davet silinemez.');
        }

        $invitation->delete();

        return back()->with('success', 'Davet iptal edildi (silindi).');
    }

    public function regenerate(TenantInvitation $invitation)
    {
        if ($invitation->accepted_at) {
            return back()->with('error', 'Kabul edilmiş davet yenilenemez.');
        }

        $token = Str::random(64);
        $link = TenantInvitation::generateLink($invitation->tenant, $token);

        $invitation->update([
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(7),
        ]);

        return back()->with('success', 'Davet yenilendi. Yeni Link: ' . $link)
                     ->with('invite_link', $link);
    }
}
