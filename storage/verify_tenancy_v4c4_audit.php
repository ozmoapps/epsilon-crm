<?php

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Models\SupportSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$startTime = now()->subSeconds(5); // Buffer

echo "🔍 Starting Audit Log Verification...\n";

// 1. Schema Check
if (!\Illuminate\Support\Facades\Schema::hasTable('audit_logs')) {
    echo "❌ FAIL: Table audit_logs not found.\n";
    exit(1);
}
echo "✅ Schema check passed.\n";

// 2. Clear previous test data (optional, but safer to rely on timestamp)
// Actually, let's just rely on new entries.

// 3. User Resolution
$platformAdmin = User::where('is_admin', true)->firstOrFail();
$tenant = Tenant::firstOrFail(); // Assuming seeded
if (\Illuminate\Support\Facades\Schema::hasColumn('tenants', 'is_active')) {
    $tenant->update(['is_active' => true]);
}
$tenantAdmin = $tenant->users()->first();
if (!$tenantAdmin) {
    // Attach one if missing
    $tenantAdmin = User::where('is_admin', false)->firstOrFail();
    // Attach or Update to Admin
    if ($tenant->users()->where('user_id', $tenantAdmin->id)->exists()) {
        $tenant->users()->updateExistingPivot($tenantAdmin->id, ['role' => 'admin']);
    } else {
        $tenant->users()->attach($tenantAdmin->id, ['role' => 'admin']);
    }
}

echo "Actor 1: Platform Admin ({$platformAdmin->email})\n";
echo "Actor 2: Tenant Admin ({$tenantAdmin->email}) - Tenant: {$tenant->name}\n";

// TEST 1: Tenant Admin creates Support Session -> support_session.created
try {
    auth()->login($tenantAdmin);
    session(['current_tenant_id' => $tenant->id]);
    
    $request = \Illuminate\Http\Request::create(route('manage.support-access.store'), 'POST');
    $request->setLaravelSession(session()->driver());
    
    // Config Mock
    config(['privacy.break_glass_enabled' => true]);
    
    $logger = app(\App\Services\AuditLogger::class);
    $controller = new \App\Http\Controllers\Manage\SupportAccessController();
    $response = $controller->store($request, $logger);
    
    if ($response->getStatusCode() !== 302) {
        echo "⚠️ Warning: Support store returned " . $response->getStatusCode() . "\n";
    }

    // Verify Log
    $log = AuditLog::where('event_key', 'support_session.created')
        ->where('occurred_at', '>=', $startTime)
        ->latest()
        ->first();
        
    if (!$log) {
        echo "❌ FAIL: support_session.created log not found.\n";
        exit(1);
    }
    
    // PII Check
    if (str_contains(json_encode($log->metadata), '@') && !str_contains(json_encode($log->metadata), '***')) {
        echo "❌ FAIL: Raw email found in metadata!\n";
        exit(1);
    }
    
    echo "✅ support_session.created verified.\n";
    
} catch (\Throwable $e) {
    echo "❌ FAIL: Test 1 Exception: " . $e->getMessage() . "\n";
    exit(1);
}

// TEST 2: Platform Admin uses Support Session -> support_session.used
try {
    $rawToken = Illuminate\Support\Str::random(64);
    $testSession = SupportSession::create([
        'tenant_id' => $tenant->id,
        'requested_by_user_id' => $tenantAdmin->id,
        'token_hash' => hash('sha256', $rawToken),
        'approved_at' => now(),
        'expires_at' => now()->addHour(),
    ]);
    
    auth()->login($platformAdmin);
    
    $request = \Illuminate\Http\Request::create(route('support.access', $rawToken), 'GET');
    $request->setLaravelSession(session()->driver());
    
    // Mock Config
    config(['privacy.break_glass_enabled' => true]);

    $logger = app(\App\Services\AuditLogger::class);
    $controller = new \App\Http\Controllers\Admin\PlatformSupportController();
    $response = $controller->__invoke($request, $rawToken, $logger);
    
    // Verify Log
    $log = AuditLog::where('event_key', 'support_session.used')
        ->where('occurred_at', '>=', $startTime)
        ->latest()
        ->first();
        
    if (!$log) {
        echo "❌ FAIL: support_session.used log not found.\n";
        exit(1);
    }
    
    echo "✅ support_session.used verified.\n";
    
} catch (\Throwable $e) {
    echo "❌ FAIL: Test 2 Exception: " . $e->getMessage() . "\n";
    exit(1);
}

// TEST 3: Privacy Violation
try {
    // Reset session (logout break-glass)
    session()->forget(['support_session_id', 'support_tenant_id', 'current_tenant_id']);
    auth()->login($platformAdmin);
    
    // Try to access a tenant business route directly (e.g. customers.index)
    // We need to pick a route that uses SetTenant and is NOT /admin
    // Route::resource('customers') is perfect.
    // But we need to simulate tenant resolution.
    // SetTenant middleware looks for session('current_tenant_id') which we don't have.
    // OR domain.
    // Let's force session('current_tenant_id') = tenant->id but NO break-glass keys.
    session(['current_tenant_id' => $tenant->id]);
    
    $request = \Illuminate\Http\Request::create(route('customers.index'), 'GET');
    $request->setLaravelSession(session()->driver());
    
    try {
        $response = app()->handle($request);
        // Should have aborted 403.
        if ($response->getStatusCode() !== 403) {
             echo "❌ FAIL: Expected 403 for privacy violation, got " . $response->getStatusCode() . "\n";
             exit(1);
        }
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        if ($e->getStatusCode() !== 403) {
             echo "❌ FAIL: Expected 403, got " . $e->getStatusCode() . "\n";
             exit(1);
        }
    }

    // Verify Log
    $log = AuditLog::where('event_key', 'privacy.violation')
        ->where('occurred_at', '>=', $startTime)
        ->latest()
        ->first();
        
    if (!$log) {
        echo "❌ FAIL: privacy.violation log not found.\n";
        exit(1);
    }
    
    if ($log->metadata['reason'] !== 'platform_admin_without_support_session') {
        echo "❌ FAIL: Incorrect reason in privacy log.\n";
    }

    echo "✅ privacy.violation verified.\n";

} catch (\Throwable $e) {
    echo "❌ FAIL: Test 3 Exception: " . $e->getMessage() . "\n";
    exit(1);
}

// TEST 4: Domain Mode Bypass (Admin Route)
// config('tenancy.resolve_by_domain') = true
// Platform Admin accesses /admin/tenants on a tenant domain
// Should bypass SetTenant context setting -> No Abort -> 200 OK (or reachable)
try {
    config(['tenancy.resolve_by_domain' => true]);
    auth()->login($platformAdmin);
    
    // Simulate domain request
    $request = \Illuminate\Http\Request::create(route('admin.tenants.index'), 'GET');
    // Set host to tenant domain (mocking)
    $request->headers->set('HOST', $tenant->domain ?? 'foo.com');
    $request->setLaravelSession(session()->driver());
    
    // We expect SetTenant to return $next($request) WITHOUT setting context.
    // If it sets context, guardPlatformAdminPrivacy would kick in (or logic inside) and might abort or log violation.
    // But since we skipped it, no abort.
    
    // We can't easily mock the middleware pipeline in a simple script without building it.
    // But we can check if App\Http\Middleware\SetTenant::handle returns.
    
    $middleware = app(\App\Http\Middleware\SetTenant::class);
    $response = $middleware->handle($request, function ($req) {
        return new \Illuminate\Http\Response('OK', 200);
    });
    
    if ($response->getStatusCode() !== 200) {
        echo "❌ FAIL: Admin route on tenant domain blocked! Code: " . $response->getStatusCode() . "\n";
        exit(1);
    }
    
    // Verify NO tenant context set
    if (app(\App\Services\TenantContext::class)->getTenant()) {
        echo "❌ FAIL: Tenant Context WAS set for admin route! Should be null.\n";
        exit(1);
    }
    
    echo "✅ Domain Mode Admin Bypass verified.\n";

} catch (\Throwable $e) {
    echo "❌ FAIL: Test 4 Exception: " . $e->getMessage() . "\n";
    exit(1);
}

// TEST 5: Break-Glass Entry Bypass
// Accessing /support/access/{token} on tenant domain should NOT abort.
try {
    $rawToken = Illuminate\Support\Str::random(64);
    $request = \Illuminate\Http\Request::create(route('support.access', $rawToken), 'GET');
    $request->headers->set('HOST', $tenant->domain ?? 'foo.com');
    $request->setLaravelSession(session()->driver());
    
    $middleware = app(\App\Http\Middleware\SetTenant::class);
    $response = $middleware->handle($request, function ($req) {
         return new \Illuminate\Http\Response('OK', 200);
    });

    if ($response->getStatusCode() !== 200) {
        echo "❌ FAIL: Break-glass route on tenant domain blocked! Code: " . $response->getStatusCode() . "\n";
        exit(1);
    }
    
    echo "✅ Break-Glass Domain Bypass verified.\n";

} catch (\Throwable $e) {
    echo "❌ FAIL: Test 5 Exception: " . $e->getMessage() . "\n";
    exit(1);
}

echo "✅ ALL AUDIT TESTS PASSED.\n";

// TEST 6: View Rendering Check (Admin Audit)
try {
    auth()->login($platformAdmin);
    $request = \Illuminate\Http\Request::create(route('admin.audit.index'), 'GET');
    $request->setLaravelSession(session()->driver());
    
    // Create Audit Log entry to ensure loop runs
    AuditLog::create([
        'event_key' => 'test.render',
        'tenant_id' => null,
        'actor_type' => User::class,
        'actor_id' => $platformAdmin->id,
        'severity' => 'info',
        'occurred_at' => now(),
    ]);

    $controller = new \App\Http\Controllers\Admin\AuditLogController();
    $response = $controller->index(); // Should return View
    
    $html = $response->render();
    if (strpos($html, 'Platform Denetim Günlüğü') === false) {
         echo "❌ FAIL: Admin Audit View Render missing content.\n";
         exit(1);
    }
    echo "✅ Admin Audit View Rendered Successfully.\n";
    
} catch (\Throwable $e) {
    echo "❌ FAIL: Admin Audit View Render Exception: " . $e->getMessage() . "\n";
    exit(1);
}

// TEST 7: View Rendering Check (Manage Audit)
try {
    auth()->login($tenantAdmin);
    session(['current_tenant_id' => $tenant->id]);
    $request = \Illuminate\Http\Request::create(route('manage.audit.index'), 'GET');
    $request->setLaravelSession(session()->driver());
    
    // Set context
    app(\App\Services\TenantContext::class)->setTenant($tenant);

    $controller = new \App\Http\Controllers\Manage\AuditLogController();
    $response = $controller->index(); 
    
    $html = $response->render();
    if (strpos($html, 'Denetim Günlüğü') === false) {
         echo "❌ FAIL: Manage Audit View Render missing content.\n";
         exit(1);
    }
    echo "✅ Manage Audit View Rendered Successfully.\n";

} catch (\Throwable $e) {
    echo "❌ FAIL: Manage Audit View Render Exception: " . $e->getMessage() . "\n";
    exit(1);
}

exit(0);
