<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSupportSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply check for admins. Non-admins are handled by other middlewares (e.g. they can't access /admin routes anyway usually)
        // But specifically for this requirement: "If user is_admin true and session('support_session_id') missing"
        
        $user = $request->user();

        if ($user && $user->is_admin && ! $request->session()->has('support_session_id')) {
            // Check if we should abort or redirect. The requirement says:
            // "abort(403) veya redirect back with error"
            
            // Let's use redirect back with error for better UX
            return redirect()->back()->with('error', 'Bu sayfaya erişmek için Destek Oturumu açmanız gerekir.');
        }

        return $next($request);
    }
}
