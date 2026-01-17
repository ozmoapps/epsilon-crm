<?php

namespace App\Http\Controllers\Manage;

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
        $logs = AuditLog::where('tenant_id', session('current_tenant_id'))
            ->with(['actor'])
            ->latest('occurred_at')
            ->paginate(50);

        return view('manage.audit.index', [
            'logs' => $logs,
            'eventMap' => self::EVENT_MAP
        ]);
    }
}
