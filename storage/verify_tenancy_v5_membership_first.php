<?php

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

echo "--- Verify PR5a: Membership-first Tenancy ---" . PHP_EOL;

$errors = [];

function assertRedirect($response, $route, $description) {
    global $errors;
    if ($response->getStatusCode() !== 302) {
        $errors[] = "[FAIL] $description: Expected 302 Redirect, got " . $response->getStatusCode();
        return;
    }
    $location = $response->headers->get('Location');
    if (!str_contains($location, route($route))) {
        $errors[] = "[FAIL] $description: Expected redirect to " . route($route) . ", got " . $location;
    } else {
        echo "[PASS] $description" . PHP_EOL;
    }
}

function assertContextSet($response, $tenantId, $description) {
    global $errors;
    $contextId = app(\App\Services\TenantContext::class)->id();
    if ($contextId != $tenantId) {
        $errors[] = "[FAIL] $description: Expected TenantContext ID $tenantId, got " . ($contextId ?? 'null');
    } else {
        echo "[PASS] $description" . PHP_EOL;
    }
}

function assertContextNotSet($description) {
    global $errors;
    $contextId = app(\App\Services\TenantContext::class)->id();
    if ($contextId) {
        $errors[] = "[FAIL] $description: Expected NO TenantContext, got " . $contextId;
    } else {
        echo "[PASS] $description" . PHP_EOL;
    }
}

// Reset Context
app(\App\Services\TenantContext::class)->setTenant(null);

try {
    // Setup Test Data
    $tenant1 = Tenant::firstOrCreate(['domain' => 'verify1.test'], ['name' => 'Verify Tenant 1', 'is_active' => true]);
    $tenant2 = Tenant::firstOrCreate(['domain' => 'verify2.test'], ['name' => 'Verify Tenant 2', 'is_active' => true]);

    // 1. User with 0 Memberships
    $user0 = User::factory()->create(['is_admin' => false]);
    $user0->tenants()->detach();
    
    // Fix: Refresh application state/session
    Session::flush();
    $request = Illuminate\Http\Request::create('/dashboard', 'GET');
    $request->setLaravelSession($app['session']->driver());
    $app->instance('request', $request);
    
    Auth::login($user0);
    
    $response = $kernel->handle($request);
    
    assertRedirect($response, 'manage.tenants.join', '0 Membership User -> Join Screen');
    assertContextNotSet('0 Membership User Context');

    // 2. User with 1 Membership
    $user1 = User::factory()->create(['is_admin' => false]);
    $user1->tenants()->sync([$tenant1->id]);
    
    Session::flush();
    $request = Illuminate\Http\Request::create('/dashboard', 'GET');
    $request->setLaravelSession($app['session']->driver());
    $app->instance('request', $request);
    
    auth()->login($user1);
    
    $response = $kernel->handle($request);

    // Should NOT redirect to select, should let through (200) or redirect to dashboard if logic allows
    // Dashboard controller returns 200.
    if ($response->getStatusCode() === 200) {
        echo "[PASS] 1 Membership User -> Dashboard (200 OK)" . PHP_EOL;
        assertContextSet($response, $tenant1->id, '1 Membership User Context Auto-Set');
    } else {
        $errors[] = "[FAIL] 1 Membership User -> Expected 200, got " . $response->getStatusCode();
    }

    // 3. User with 2+ Memberships
    $user2 = User::factory()->create(['is_admin' => false]);
    $user2->tenants()->sync([$tenant1->id, $tenant2->id]);
    
    Session::flush();
    $request = Illuminate\Http\Request::create('/dashboard', 'GET');
    $request->setLaravelSession($app['session']->driver());
    $app->instance('request', $request);

    // Reset Context for clean test
    app(\App\Services\TenantContext::class)->setTenant(null);

    auth()->login($user2);

    $response = $kernel->handle($request);
    
    assertRedirect($response, 'manage.tenants.select', '2+ Membership User -> Select Screen');
    assertContextNotSet('2+ Membership User Context (Before Selection)');

    // 4. Admin User (No Support Session)
    $admin = User::where('email', 'admin@epsilon.com')->first();
    if (!$admin) {
        $admin = User::limit(1)->where('is_admin', true)->first(); // Fallback
    }

    if ($admin) {
        Session::flush();
        $request = Illuminate\Http\Request::create('/dashboard', 'GET');
        $request->setLaravelSession($app['session']->driver());
        $app->instance('request', $request);
        
        // Reset Context for clean test
        app(\App\Services\TenantContext::class)->setTenant(null);

        auth()->login($admin);
        Session::forget('support_session_id');
        Session::forget('current_tenant_id');
        
        // Admin accessing Dashboard (Platform) -> Should NOT have Tenant Context
        
        $response = $kernel->handle($request);
        if ($response->getStatusCode() === 200) {
             echo "[PASS] Admin User (No Support) -> Dashboard (200 OK)" . PHP_EOL;
             assertContextNotSet('Admin User (No Support) Context');
        } else {
             $errors[] = "[FAIL] Admin User (No Support) -> Expected 200, got " . $response->getStatusCode();
        }
    }


} catch (\Throwable $e) {
    $errors[] = "[CRITICAL] Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine();
}

if (empty($errors)) {
    echo "VERIFY RESULT: PASS" . PHP_EOL;
    exit(0);
} else {
    echo "VERIFY RESULT: FAIL" . PHP_EOL;
    foreach ($errors as $err) {
        echo $err . PHP_EOL;
    }
    exit(1);
}
