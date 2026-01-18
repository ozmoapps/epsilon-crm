<?php

use App\Models\User;
use App\Models\Tenant;
use App\Models\SupportSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- Verify PR5e: Platform Admin Privacy Pack ---\n";

// Setup
$suffix = uniqid();
$tenant = Tenant::firstOrCreate(['domain' => 'privacy-test.test'], ['name' => 'Privacy Test Tenant', 'is_active' => true]);
$adminUser = User::factory()->create(['is_admin' => true, 'email' => 'admin-priv-'.$suffix.'@test.com']);
auth()->login($adminUser);

echo "[OK] Setup Tenant (ID: {$tenant->id}) and Admin User (ID: {$adminUser->id})\n";

// Middleware Setup
$middleware = app(\App\Http\Middleware\SetTenant::class);

// Helper to run middleware
function runMiddleware($middleware, $request) {
    try {
        $response = $middleware->handle($request, function ($req) {
            return new \Symfony\Component\HttpFoundation\Response('OK');
        });
        return ['status' => $response->getStatusCode(), 'content' => $response->getContent()];
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        return ['status' => $e->getStatusCode(), 'exception' => $e->getMessage()];
    } catch (\Exception $e) {
        return ['status' => 500, 'exception' => $e->getMessage()];
    }
}

// 1. No Session -> Tenant Area -> 403
echo "\n--- Test 1: No Support Session ---\n";
$request = Request::create('/payments', 'GET'); // Tenant Area
$request->setLaravelSession(session()->driver());
$request->session()->start();
$request->session()->forget(['support_session_id', 'support_tenant_id']);

$res = runMiddleware($middleware, $request);
if ($res['status'] === 403) {
    echo "[PASS] No Session -> 403 Forbidden\n";
} else {
    echo "[FAIL] No Session -> Status " . $res['status'] . "\n";
    exit(1);
}

// 2. Invalid Session -> Tenant Area -> 403 + Clean Keys
echo "\n--- Test 2: Invalid Support Session ---\n";
// Create expired session
$session = SupportSession::create([
    'tenant_id' => $tenant->id,
    'requested_by_user_id' => $adminUser->id,
    'token_hash' => hash('sha256', 'invalid-' . $suffix),
    'expires_at' => now()->subHour(), // Expired
    'approved_at' => now()->subDay()
]);

$request->session()->put('support_session_id', $session->id);
$request->session()->put('support_tenant_id', $tenant->id);

$res = runMiddleware($middleware, $request);
if ($res['status'] === 403) {
    echo "[PASS] Invalid Session -> 403 Forbidden\n";
    if (!$request->session()->has('support_session_id')) {
        echo "[PASS] Session keys cleared.\n";
    } else {
        echo "[FAIL] Session keys NOT cleared.\n";
        exit(1);
    }
} else {
    echo "[FAIL] Invalid Session -> Status " . $res['status'] . "\n";
    exit(1);
}

// 3. Valid Session -> Tenant Area -> OK (or Context Set attempt)
echo "\n--- Test 3: Valid Support Session ---\n";
$session->update(['expires_at' => now()->addHour()]); // Valid
$request->session()->put('support_session_id', $session->id);
$request->session()->put('support_tenant_id', $tenant->id);

// Note: If SetTenant succeeds, it might call next() or set context.
// Ideally it sets context and calls next().
$res = runMiddleware($middleware, $request);

// Check if context is set
$currentTenant = app(\App\Services\TenantContext::class)->getTenant();

if ($res['status'] === 200) {
    echo "[PASS] Valid Session -> Access Allowed.\n";
    // PR5d logic: If support session is valid, it sets context?
    // Actually PR5d logic says if valid -> return false in shouldSkip -> proceed to SetTenant -> setTenant().
    
    // Check if session keys persisted
    if ($request->session()->has('support_session_id')) {
         echo "[PASS] Session keys persisted.\n";
    } else {
         echo "[FAIL] Session keys wiped unexpectedly.\n";
    }

} else {
    echo "[FAIL] Valid Session -> Status " . $res['status'] . " (Ex: " . ($res['exception'] ?? '') . ")\n";
    exit(1);
}

echo "\nVERIFY RESULT: PASS\n";
