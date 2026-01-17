<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\SupportSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        // 1. General Metrics (Counts)
        // Using optimized counts for performance
        $metrics = [
            'total_accounts' => Schema::hasTable('accounts') ? Account::count() : 0,
            'total_tenants' => Schema::hasTable('tenants') ? Tenant::count() : 0,
            'active_tenants' => Schema::hasTable('tenants') && Schema::hasColumn('tenants', 'is_active') 
                ? Tenant::where('is_active', true)->count() 
                : (Schema::hasTable('tenants') ? Tenant::count() : 0),
            'inactive_tenants' => Schema::hasTable('tenants') && Schema::hasColumn('tenants', 'is_active') 
                ? Tenant::where('is_active', false)->count() 
                : 0,
            
            // Seat Metrics (Account Users - Simplest/Fastest source of truth for "Registered Humans")
            'total_users' => Schema::hasTable('account_users') ? DB::table('account_users')->count() : 0,
            
            // Pending Invites
            'pending_invites' => Schema::hasTable('tenant_invitations') 
                ? DB::table('tenant_invitations')
                    ->whereNull('accepted_at')
                    ->where('expires_at', '>', now())
                    ->count()
                : 0,

            // Active Support Sessions
            'active_support_sessions' => Schema::hasTable('support_sessions')
                ? SupportSession::whereNull('revoked_at')
                    ->where('expires_at', '>', now())
                    ->count()
                : 0
        ];

        // 2. Plan Breakdown (Accounts by Plan)
        // Group by plan name (from plans table joined via plan_id)
        $planBreakdown = (Schema::hasTable('accounts') && Schema::hasTable('plans')) 
            ? DB::table('accounts')
                ->leftJoin('plans', 'accounts.plan_id', '=', 'plans.id')
                ->select(
                    DB::raw("COALESCE(plans.name_tr, 'Plan Yok') as plan_name"),
                    DB::raw('count(accounts.id) as count')
                )
                ->groupBy(DB::raw("COALESCE(plans.name_tr, 'Plan Yok')"))
                ->orderBy('count', 'desc')
                ->get()
            : collect();

        // 3. Audit Summary (Last 24h)
        $auditSummary = Schema::hasTable('audit_logs')
            ? AuditLog::where('occurred_at', '>=', now()->subDay())
                ->select('event_key', DB::raw('count(*) as count'))
                ->groupBy('event_key')
                ->orderBy('count', 'desc')
                ->get()
            : collect();

        // 4. Last Privacy Violation
        $lastPrivacyViolation = Schema::hasTable('audit_logs')
            ? AuditLog::where('event_key', 'privacy.violation')
                ->with('tenant:id,name') // Only eager load safe fields
                ->latest()
                ->first()
            : null;

        // Event Map for Turkish localization (Shared with AuditLogController logic)
        $eventMap = [
            'user.login' => 'Kullanıcı Girişi',
            'support_session.created' => 'Destek Erişimi Oluşturuldu',
            'support_session.revoked' => 'Destek Erişimi İptal Edildi',
            'support_session.used' => 'Destek Erişimi Kullanıldı',
            'privacy.violation' => 'Gizlilik İhlali Engellendi',
            'tenant.toggled_active' => 'Firma Durumu Değişti',
            'entitlement.blocked' => 'Limit Engeli',
        ];

        return view('admin.dashboard.index', compact(
            'metrics', 
            'planBreakdown', 
            'auditSummary', 
            'lastPrivacyViolation',
            'eventMap'
        ));
    }
}
