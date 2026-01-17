<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    protected const EVENT_MAP = [
        'support_session.created' => 'Destek Erişimi Oluşturuldu',
        'support_session.used' => 'Destek Erişimi Kullanıldı',
        'support_session.revoked' => 'Destek Erişimi İptal Edildi',
        'privacy.violation' => 'Gizlilik İhlali',
        'tenant.created' => 'Firma Oluşturuldu',
        'tenant.toggled_active' => 'Firma Durumu Değiştirildi',
        'entitlement.blocked' => 'Limit Engeli',
        'invitation.created' => 'Davet Oluşturuldu',
        'invitation.accepted' => 'Davet Kabul Edildi',
        'invitation.revoked' => 'Davet İptal Edildi',
    ];

    public function index()
    {
        // Platform admin sees all.
        // Optional: Filter by tenant_id if requested, but for now simple list.
        $logs = AuditLog::with(['tenant', 'actor'])
            ->latest('occurred_at')
            ->paginate(50);

        return view('admin.audit.index', [
            'logs' => $logs,
            'eventMap' => self::EVENT_MAP
        ]);
    }
}
