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

        // 1. NEUTRAL ROUTES ALLOWLIST (TR: "Neutral routes allowlist")
        // These routes DO NOT require tenant resolution or context.
        $routeName = $request->route()?->getName();
        $isNeutral = in_array($routeName, [
            'login', 'logout', 'register', 
            'password.request', 'password.email', 'password.reset', 'password.update', 'password.confirm', 'password.store',
            'verification.notice', 'verification.verify', 'verification.send',
            'invitations.accept',
            // PR5a: Membership-first Tenancy Routes
            'manage.tenants.join', 'manage.tenants.select',
            // PR14c: Paywall
            'billing.paywall'
        ]) || $request->is('/') || $request->is('login') || $request->is('register') || $request->is('logout') || 
              $request->is('forgot-password') || $request->is('reset-password/*') || $request->is('verify-email/*') ||
              $request->is('invite/*');

        if ($isNeutral) {
            // Special Case: Logout Cleanup
            if (($routeName === 'logout' || $request->is('logout')) && $request->method() === 'POST') {
                session()->forget(['current_tenant_id', 'support_session_id', 'support_tenant_id']);
            }
            return $next($request);
        }
        
        // PR14c: Global Paywall / Trial Expiration Check (Hotfix Membership-Aware)
        // If user is logged in, NOT admin
        if ($user && ! $user->is_admin) {
             // 1. Gather all Account IDs the user is associated with
             // A. Via Tenant Membership (Primary Access) - Direct DB to avoid Scopes
             $tenantAccountIds = \Illuminate\Support\Facades\DB::table('tenant_user')
                  ->join('tenants', 'tenants.id', '=', 'tenant_user.tenant_id')
                  ->where('tenant_user.user_id', $user->id)
                  ->pluck('tenants.account_id');
             
             // B. Via Direct Ownership (Backup/Legacy)
             $ownedAccountIds = \App\Models\Account::where('owner_user_id', $user->id)->pluck('id');
             
             // Merge & Unique
             $allAccountIds = $tenantAccountIds->concat($ownedAccountIds)->unique()->filter();
             
             // 2. If user has any account association, check for AT LEAST ONE Active Account
             if ($allAccountIds->isNotEmpty()) {
                 $hasActiveAccount = \App\Models\Account::whereIn('id', $allAccountIds)
                    ->where(function($query) {
                        $query->whereNotNull('billing_subscription_id') // Paid
                              ->orWhereNull('ends_at') // Indefinite
                              ->orWhere('ends_at', '>', now()); // Future
                    })
                    ->exists();
                 
                 // 3. Enforcement
                 if (! $hasActiveAccount) {
                      // Associated with accounts, but ALL are expired -> Paywall
                      if ($routeName !== 'billing.paywall') {
                          return redirect()->route('billing.paywall');
                      }
                 }
             } else {
                 // No Account Associations (0 Membership, 0 Owned)
                 // Allow user to proceed (Will likely hit Onboarding/Join flow below)
             }
        }

        // 2. PLATFORM ROUTES (TR: "Platform routes")
        // Admin panel and Support Access Entry -> Skip tenant resolution
        if ($request->is('admin') || $request->is('admin/*') || $request->is('support/access/*')) {
             return $next($request);
        }

        // PR5d1 HOTFIX: Platform Admin Guard for Tenant Areas
        // If we are here, it is NOT a Neutral route and NOT a Platform route.
        // It SHOULD be a tenant route. Platform Admins must have a valid support session.
        if ($user && $user->is_admin) {
             $sessId = $request->session()->get('support_session_id');
             $sessTenantId = $request->session()->get('support_tenant_id');

             if (! $sessId || ! $sessTenantId) {
                 abort(403, 'Bu sayfaya erişmek için Destek Erişimi açmanız gerekiyor.');
             }

             // Validate explicitly here to catch invalid sessions early
             $validation = $this->validateSupportSession($sessId, $sessTenantId, $user->id);
             if (! $validation['is_valid']) {
                 $request->session()->forget(['support_session_id', 'support_tenant_id']);
                 abort(403, 'Bu sayfaya erişmek için Destek Erişimi açmanız gerekiyor. (Sebep: ' . $validation['reason'] . ')');
             }
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


        // 4. Fallback Logic (Membership First)
        if (! $tenant) {
            
            // A. Check for user membership count
            if ($user && ! $user->is_admin) {
                // Determine tenants (Active only if schema checks out)
                $userTenants = $user->tenants()
                    ->when($hasIsActive, function ($q) {
                        $q->where('is_active', true);
                    })
                    ->get();
                
                $count = $userTenants->count();

                if ($count === 0) {
                    // 0 Memberships -> Redirect to Join (if not already there)
                    
                    // PR14: Allow onboarding routes and dashboard for 0 membership users
                    if (in_array($routeName, ['dashboard', 'onboarding.company.create', 'onboarding.company.store'])) {
                         return $next($request);
                    }

                    if ($routeName !== 'manage.tenants.join') {
                         // Use redirect away to ensure we break the middleware chain properly if needed, but standard redirect is fine
                         return redirect()->route('manage.tenants.join'); 
                    }
                    // If already on join page, proceed (no context set)
                    return $next($request);

                } elseif ($count === 1) {
                    // 1 Membership -> Deterministic Auto-Set
                    $tenant = $userTenants->first();
                    // We found one, proceed to Set Context below
                } else {
                    // 2+ Memberships -> Redirect to Select (if not already there)
                    if ($routeName !== 'manage.tenants.select' && $routeName !== 'tenants.switch') { // Allow switch action
                         return redirect()->route('manage.tenants.select');
                    }
                     // If already on select page, proceed (no context set)
                     return $next($request);
                }
            } else {
                // Admin or Guest without context
                // If it's admin, they are already handled by Platform Admin skip logic, so they might end up here with no tenant.
                // That is desired for Platform Dashboard.
            }

            // OLD "Default Fallback" REMOVED.
        }

        // Context'e set et
        if ($tenant) {
            // PR4C3: Privacy by Default (Platform Lock)
            // If user is Platform Admin (is_admin=true), they CANNOT access tenant context
            // UNLESS a valid break-glass session exists.
            if ($this->shouldSkipContextForPlatformAdmin($request, $tenant)) {
                 return $next($request);
            }

            $this->tenantContext->setTenant($tenant);
            
            // Web isteklerinde view ile paylaş (Global)
            if (! $request->expectsJson()) {
                View::share('currentTenant', $tenant);
            }
        } else {
             // 5. TENANT USER WITHOUT CONTEXT -> FORBIDDEN
             // If not neutral, not platform, and no tenant -> Block tenant user.
             if ($user && !$user->is_admin) {
                 abort(403, 'Erişmeye çalıştığınız sayfa için firma bağlamı bulunamadı (No Tenant Context).');
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
        
        // Strict DB Verification (PR5d)
        if ($activeSessionId && $activeTenantId == $tenant->id) {
             $validation = $this->validateSupportSession($activeSessionId, $tenant->id, $user->id);

             if ($validation['is_valid']) {
                 return false; // Valid access, proceed to set context (Skip=False)
             }

             // Invalid Session (Expired, Revoked, or Tampered)
             // Clear keys to prevent loop and enforce security
             $request->session()->forget(['support_session_id', 'support_tenant_id']);
        }

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

        // VIOLATION: Platform Admin trying to access Tenant Route without Valid Break-Glass
        
        // Log violation (Dedupe)
        if (! $request->attributes->get('audit_privacy_violation_logged')) {
            $this->auditLogger->log('privacy.violation', [
                'reason' => 'platform_admin_invalid_support_session',
                'path' => $request->path(),
                'candidate_tenant_id' => $tenant->id
            ], 'warn');
            $request->attributes->set('audit_privacy_violation_logged', true);
        }

        $message = 'Gizlilik nedeniyle platform yöneticileri firma verilerine doğrudan erişemez. Firma yöneticisinin onayı ile ‘Destek Erişimi’ açılabilir.';
        if (isset($validation) && ! $validation['is_valid']) {
             $message .= ' (Sebep: ' . $validation['reason'] . ')';
        }

        abort(403, $message);
    }

    /**
     * Verify support session against Database (PR5d)
     * @return array{is_valid: bool, reason: string|null}
     */
    protected function validateSupportSession($sessionId, $tenantId, $userId): array
    {
        // Use Model query to ensure all guards are respected
        $session = \App\Models\SupportSession::where('id', $sessionId)
            ->where('tenant_id', $tenantId)
            ->where('requested_by_user_id', $userId) // Privacy: Ensure it belongs to this admin
            ->first();

        if (! $session) {
            return ['is_valid' => false, 'reason' => 'Oturum bulunamadı'];
        }

        if ($session->revoked_at !== null) {
            return ['is_valid' => false, 'reason' => 'Oturum iptal edilmiş'];
        }

        if ($session->expires_at && $session->expires_at->isPast()) {
            return ['is_valid' => false, 'reason' => 'Oturum süresi dolmuş'];
        }

        return ['is_valid' => true, 'reason' => null];
    }
}
