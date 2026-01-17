<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // PR3C10: Platform Owner Privacy Lock
        // Platform Admins (is_admin=true) are NOT automatically allowed access to tenant operations.
        // They must be explicitly invited as a member (Role: Admin) to the tenant to access these routes.
        // if ($user->is_admin) { return $next($request); } -> REMOVED


        // Get Current Tenant Context
        $tenant = app(\App\Services\TenantContext::class)->getTenant();
        $tenantId = $tenant ? $tenant->id : session('current_tenant_id');
        
        if (!$tenantId) {
            abort(403, 'Firma bağlamı bulunamadı.');
        }

        // Check if user has 'admin' role in this tenant
        $isTenantAdmin = $user->tenants()
            ->where('tenants.id', $tenantId)
            ->wherePivot('role', 'admin')
            ->exists();

        if (!$isTenantAdmin) {
            abort(403, 'Bu işlem için firma yöneticisi olmanız gerekiyor.');
        }

        return $next($request);
    }
}
