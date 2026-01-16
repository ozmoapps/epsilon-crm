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


        // PR68: Remove Admin Gating - Bypass check
        // if (!auth()->user()->is_admin) {
        //     return redirect()->route('dashboard')
        //         ->with('error', 'Bu sayfaya erişim yetkiniz yok. Operasyon ekranına yönlendirildiniz.');
        // }

        return $next($request);
    }
}
