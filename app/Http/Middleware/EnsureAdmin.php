<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
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


        if (! auth()->user()->is_admin) {
            abort(403, 'Bu sayfaya erişim yetkiniz yok.');
        }

        // PR3C6A: Admin Surface Lockdown
        // Platform admin access uses the ROOT domain.
        // If we are on a tenant domain, block access to /admin routes.
        if ($request->attributes->get('is_tenant_domain')) {
             abort(403, 'Platform yönetimine sadece ana domain üzerinden erişilebilir.');
        }

        return $next($request);
    }
}
