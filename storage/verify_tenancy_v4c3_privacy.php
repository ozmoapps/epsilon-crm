<?php

use App\Models\User;
use App\Models\Tenant;
use App\Models\SupportSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n--- Verifying Privacy & Break-Glass (PR4C3) ---\n";

$failed = false;

function test($name, $callback) {
    global $failed;
    try {
        if ($callback()) {
            echo "PASS: $name\n";
        } else {
            echo "FAIL: $name\n";
            $failed = true;
        }
    } catch (Throwable $e) {
        echo "FAIL: $name - Exception: " . $e->getMessage() . "\n";
        $failed = true;
    }
}

// Setup
$adminUser = User::where('email', 'platform_admin_test@test.com')->first();
if (!$adminUser) {
    $adminUser = User::create([
        'name' => 'Platform Admin Privacy',
        'email' => 'platform_admin_test@test.com',
        'password' => bcrypt('password'),
        'is_admin' => true,
    ]);
}

$tenantAdmin = User::where('email', 'tenant_admin_test@test.com')->first();
if (!$tenantAdmin) {
    $tenantAdmin = User::create([
        'name' => 'Tenant Admin Privacy',
        'email' => 'tenant_admin_test@test.com',
        'password' => bcrypt('password'),
    ]);
}

$tenant = Tenant::where('domain', 'privacy-test.test')->first();
if (!$tenant) {
    $tenant = Tenant::create([
        'name' => 'Privacy Test Corp',
        'domain' => 'privacy-test.test',
        'is_active' => true,
    ]);
    $tenant->users()->sync([$tenantAdmin->id => ['role' => 'admin']]);
}

// --- Test 1: Platform Lock (Default Privacy) ---
test("Platform Admin cannot access Tenant Business Route", function() use ($adminUser, $tenant) {
    // Simulate Request
    auth()->login($adminUser);
    session(['current_tenant_id' => $tenant->id]); // Try to force it
    
    // We can't easily simulate middleware stack in raw PHP script without full HTTP test,
    // but we can instantiate the middleware and run handle.
    
    $request = Illuminate\Http\Request::create('/customers', 'GET');
    $request->setLaravelSession(app('session')->driver());
    $request->setUserResolver(fn() => $adminUser);
    
    // SetTenant Middleware
    $middleware = app(\App\Http\Middleware\SetTenant::class);
    
    try {
        $middleware->handle($request, function($req) {
            return new \Symfony\Component\HttpFoundation\Response('OK');
        });
        return false; // Should have aborted
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        return $e->getStatusCode() === 403;
    }
});

test("Platform Admin CAN access /admin route", function() use ($adminUser, $tenant) {
    auth()->login($adminUser);
    // Explicitly set session to ensure SetTenant clears it or ignores it for context
    session(['current_tenant_id' => $tenant->id]); 
    
    $request = Illuminate\Http\Request::create('/admin/tenants', 'GET');
    $request->setLaravelSession(app('session')->driver());
    $request->setUserResolver(fn() => $adminUser);
    
    $middleware = app(\App\Http\Middleware\SetTenant::class);
    
    $response = $middleware->handle($request, function($req) {
        return new \Symfony\Component\HttpFoundation\Response('OK');
    });
    
    // Should be OK (200) and context should be NULL?
    // Middleware returns $next($request) which is response 'OK'.
    // Important: Did it set tenant context?
    $context = app(\App\Services\TenantContext::class)->getTenant();
    
    return $response->getContent() === 'OK' && $context === null;
});


// --- Test 2: Break-Glass Flow ---

// Enable Config
Config::set('privacy.break_glass_enabled', true);

test("Tenant Admin can generate token", function() use ($tenantAdmin, $tenant) {
    auth()->login($tenantAdmin);
    session(['current_tenant_id' => $tenant->id]);
    
    // Call Controller
    $controller = new \App\Http\Controllers\Manage\SupportAccessController();
    $request = Illuminate\Http\Request::create('/manage/members', 'POST'); // Route doesn't matter much for method call
    $request->setLaravelSession(app('session')->driver());
    
    $response = $controller->store($request);
    
    return $response->getSession()->has('support_link');
});

// Get the latest session
$supportSession = SupportSession::latest()->first();
$rawToken = null;
// We can't retrieve raw token from DB hash. 
// In a real test we'd capture the response. 
// For this script, let's manually creating one to know the token.

$knownToken = 'test-token-123';
$supportSession = SupportSession::create([
    'tenant_id' => $tenant->id,
    'requested_by_user_id' => $tenantAdmin->id,
    'token_hash' => hash('sha256', $knownToken),
    'approved_at' => now(),
    'expires_at' => now()->addHour(),
]);

test("Platform Admin can consume token", function() use ($adminUser, $knownToken, $supportSession) {
    auth()->login($adminUser);
    
    $controller = new \App\Http\Controllers\Admin\PlatformSupportController();
    $request = Illuminate\Http\Request::create('/support/access/'.$knownToken, 'GET');
    $request->setLaravelSession(app('session')->driver());
    
    $response = $controller->__invoke($request, $knownToken);
    
    // Should pass (redirect)
    // And Session should have keys
    $sess = session()->all();
    
    return $response->isRedirect() 
        && ($sess['support_session_id'] ?? null) == $supportSession->id
        && ($sess['support_tenant_id'] ?? null) == $supportSession->tenant_id;
});

test("Platform Admin WITH Break-Glass Session CAN access Tenant Route", function() use ($adminUser, $tenant, $supportSession) {
    auth()->login($adminUser);
    // Ensure session is set (mimic consume)
    session([
        'support_session_id' => $supportSession->id,
        'support_tenant_id' => $tenant->id,
        'current_tenant_id' => $tenant->id
    ]);
    
    // Debug
    $all = app('session')->driver()->all();
    echo "   [Debug] Session in Test: support_tenant_id=" . ($all['support_tenant_id'] ?? 'NULL') . ", support_session_id=" . ($all['support_session_id'] ?? 'NULL') . "\n";
    echo "   [Debug] Tenant Expected ID: " . $tenant->id . "\n";
    
    $request = Illuminate\Http\Request::create('/customers', 'GET');
    $request->setLaravelSession(app('session')->driver());
    $request->setUserResolver(fn() => $adminUser);
    
    $middleware = app(\App\Http\Middleware\SetTenant::class);
    
    try {
        $response = $middleware->handle($request, function($req) {
            return new \Symfony\Component\HttpFoundation\Response('OK');
        });
        
        // Should PASS
        $context = app(\App\Services\TenantContext::class)->getTenant();
        return $response->getContent() === 'OK' && $context && $context->id === $tenant->id;
    } catch (Throwable $e) {
        echo "   Error: " . $e->getMessage() . "\n";
        return false;
    }
});

// Cleanup
$supportSession->delete();
Tenant::where('id', $tenant->id)->delete();
$adminUser->delete();
$tenantAdmin->delete();

exit($failed ? 1 : 0);
