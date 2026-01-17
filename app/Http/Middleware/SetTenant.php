<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Services\TenantContext;
use App\Models\Tenant;

use App\Services\AuditLogger;

class SetTenant
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        $domainTenant = null;

        // CRITICAL FIX A & B: Bypass Tenant Resolution for Platform Admin & Support Routes
        // 1. Platform Admin Panel (/admin/*) -> Should NOT have tenant context (unless explicitly switched? No, context is global/null)
        // 2. Break-Glass Entry (/support/access/*) -> Must process without context first to validate token
        if ($request->is('admin') || $request->is('admin/*') || $request->is('support/access/*')) {
            return $next($request);
        }

        // 1. Domain/Host Based Resolution (Feature Flagged)
        if (config('tenancy.resolve_by_domain')) {
            $host = $request->getHost();
            
            // Exact Match
            $query = Tenant::where('domain', $host);
            if (Schema::hasColumn('tenants', 'is_active')) {
                $query->where('is_active', true);
            }
            $domainTenant = $query->first();

            // Subdomain Match (Optional)
            if (! $domainTenant && $baseDomain = config('tenancy.base_domain')) {
                if (str_ends_with($host, '.' . $baseDomain)) {
                    $slug = substr($host, 0, -strlen('.' . $baseDomain));
                    if ($slug && $slug !== 'www') {
                        $subQuery = Tenant::where('slug', $slug);
                        if (Schema::hasColumn('tenants', 'is_active')) {
                            $subQuery->where('is_active', true);
                        }
                        $domainTenant = $subQuery->first();
                    }
                }
            }

            if ($domainTenant) {
                // If found by domain, we MUST use this tenant or fail.
                $request->attributes->set('is_tenant_domain', true);
                
                // If user is logged in, check membership
                if ($user) {

                    // Check if user is member (Optimized Query)
                    $isMember = $user->tenants()->where('tenants.id', $domainTenant->id)->exists();
                    if (! $isMember) {
                        // PR3C6B: Domain Invite Exception
                        // If checking an invite on a tenant domain, allow it IF the invite matches this tenant and is valid.
                        // Strict Check: Route checks + Token DB validation.
                        $bypass = false;
                        
                        // 1. Token Extraction (Regex for robustness)
                        // Matches 'invite/' followed by non-slash characters
                        if (preg_match('~invite/([^/]+)~', $request->path(), $matches)) {
                            $token = $matches[1];

                            if ($token) {
                                $tokenHash = hash('sha256', $token);
                                // Check if this token exists for THIS tenant and is pending
                                $validInvite = \App\Models\TenantInvitation::where('tenant_id', $domainTenant->id)
                                    ->where('token_hash', $tokenHash)
                                    ->whereNull('accepted_at')
                                    ->where('expires_at', '>', now())
                                    ->exists();
                                
                                if ($validInvite) {
                                    $bypass = true;
                                }
                            }
                        }

                        if (! $bypass) {
                            abort(403, 'Bu firmaya erişim yetkiniz yok.');
                        }
                    }
                }

                // Privacy Guard (Domain Branch)
                if ($this->shouldSkipContextForPlatformAdmin($request, $domainTenant)) {
                     return $next($request);
                }

                // Initial Set (Context)
                app(\App\Services\TenantContext::class)->setTenant($domainTenant);
                
                // Share with View only for Web Requests
                if (! $request->expectsJson()) {
                    view()->share('currentTenant', $domainTenant);
                }

                // Session Alignment Logic (Refined per PR3C6C1)
                // Only align session if:
                // 1. User is actually a member (so they can switch back here easily)
                // 2. OR Access is granted via Bypass (VALID Invite flow)
                
                $isMember = false;
                if ($user) {
                    $isMember = $user->tenants()->where('tenants.id', $domainTenant->id)->exists();
                }

                // $bypass is calculated above based on Valid Invite Check.
                // We use that to determine if this is a "valid invite visit".
                // Note: $bypass is true ONLY if invite is valid (token hash matches, not accepted, not expired).

                if ($isMember || (isset($bypass) && $bypass)) {
                    session(['current_tenant_id' => $domainTenant->id]);
                }

                return $next($request);
            }
        }

        // 2. Session / User Default Logic (Existing)
        $tenant = null;
        // Optimization: Cache active check to avoid multiple schema queries per request
        $hasIsActive = \Illuminate\Support\Facades\Schema::hasColumn('tenants', 'is_active');

        // 1.5 Support Access Resolution (PR4C3)
        // If Platform Admin has a valid support session, resolve that tenant explicitly.
        // This bypasses the "Membership Check" required by standard session resolution.
        if (! $domainTenant && $user && $user->is_admin) {
            $supportTenantId = $request->session()->get('support_tenant_id');
            if ($supportTenantId && $request->session()->get('support_session_id')) {
                $supportTenant = Tenant::find($supportTenantId);
                // Active check?
                if ($supportTenant && (!$hasIsActive || $supportTenant->is_active)) {
                    $tenant = $supportTenant;
                }
            }
        }

        // 2. Session / User Default Logic (Existing)
        // Only run if we haven't already resolved a tenant (e.g. via support)
        if (! $tenant && session()->has('current_tenant_id')) {
            $candidateId = session('current_tenant_id');
            
            $tenantModel = Tenant::find($candidateId);
            
            $isMember = Auth::check() && Auth::user()->tenants()->where('tenants.id', $candidateId)->exists();
            $isActive = $tenantModel && (!$hasIsActive || $tenantModel->is_active);

            if ($isMember && $isActive) {
                $tenant = $tenantModel;
            } else {
                // Invalid (Not member OR Passive) -> Forget session
                session()->forget('current_tenant_id');
            }
        }

        // 2. Auth user fallback (user->tenant_id)
        if (! $tenant && Auth::check() && Auth::user()->tenant_id) {
            $fallbackId = Auth::user()->tenant_id;
            // Membership + Active Check
            $userTenant = Auth::user()->tenants()
                ->where('tenants.id', $fallbackId)
                ->when($hasIsActive, function ($q) {
                    $q->where('is_active', true);
                })
                ->first();
            
            if ($userTenant) {
                 $tenant = $userTenant;
            }
        }

        // 3. Auth user first tenant fallback
        if (! $tenant && Auth::check()) {
            $tenant = Auth::user()->tenants()
                ->when($hasIsActive, function ($q) {
                    $q->where('is_active', true);
                })
                ->orderBy('name')
                ->first();
        }

        // 4. Default System Fallback
        if (! $tenant) {
            $tenant = Tenant::where('name', 'Varsayılan Firma')
                ->when($hasIsActive, function ($q) {
                    $q->where('is_active', true);
                })
                ->first();
            
            if (! $tenant) {
                $tenant = Tenant::when($hasIsActive, function ($q) {
                    $q->where('is_active', true);
                })->first();
            }
        }

        // Context'e set et
        if ($tenant) {
            // PR4C3: Privacy by Default (Platform Lock)
            // If user is Platform Admin (is_admin=true), they CANNOT access tenant context
            // UNLESS a valid break-glass session exists.
            
            // PR4C3: Privacy by Default (Platform Lock)
            if ($this->shouldSkipContextForPlatformAdmin($request, $tenant)) {
                 return $next($request);
            }

            $this->tenantContext->setTenant($tenant);
            
            // Web isteklerinde view ile paylaş (Global)
            if (! $request->expectsJson()) {
                View::share('currentTenant', $tenant);
            }
        }

        return $next($request);
    }

    protected function shouldSkipContextForPlatformAdmin(Request $request, ?Tenant $tenant): bool
    {
        if (! $tenant) {
            return false;
        }

        $user = auth()->user();
        if (! $user || ! $user->is_admin) {
            return false;
        }

        // Break-Glass Session Check
        $activeSessionId = $request->session()->get('support_session_id');
        $activeTenantId = $request->session()->get('support_tenant_id');
        
        if ($activeSessionId && $activeTenantId == $tenant->id) {
             return false; // Valid access, proceed to set context
        }

        // Allow Platform Admin Routes (/admin/*)
        // If request is explicitly for admin panel, we skip setting tenant context.
        // Allow Platform Admin Routes (/admin/*)
        // If request is explicitly for admin panel, we skip setting tenant context.
        if ($request->is('admin') || $request->is('admin/*')) {
             return true; // Skip context
        }
        
        // CRITICAL FIX B: Break-Glass Entry logic
        // If we are hitting the entry point, we must allow it to proceed to controller which sets up the session.
        if ($request->is('support/access/*')) {
            return true;
        }

        // VIOLATION: Platform Admin trying to access Tenant Route without Break-Glass
        
        // Log violation (Dedupe)
        if (! $request->attributes->get('audit_privacy_violation_logged')) {
            $this->auditLogger->log('privacy.violation', [
                'reason' => 'platform_admin_without_support_session',
                'path' => $request->path(),
                'candidate_tenant_id' => $tenant->id
            ], 'warn');
            $request->attributes->set('audit_privacy_violation_logged', true);
        }

        abort(403, 'Gizlilik nedeniyle platform yöneticileri firma verilerine doğrudan erişemez. Firma yöneticisinin onayı ile ‘Destek Erişimi’ açılabilir.');
    }
}
