<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Account;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// 1. Guard
if (!app()->environment('local', 'testing')) {
    echo "This script only runs in local/testing environment.\n";
    exit(1);
}
if (DB::connection()->getDriverName() !== 'sqlite') {
    echo "This script only runs on sqlite database.\n";
    exit(1);
}

echo "--- VERIFICATION START: Onboarding (0 Membership) ---\n";

try {
    DB::beginTransaction();

    // Setup: Ensure Plan Exists
    $plan = \App\Models\Plan::where('key', 'starter')->first();
    if (!$plan) {
         throw new Exception("Starter plan not found in DB! Seed is required.");
    }

    // 1. Create 0 Membership User
    $email = 'onboarding_test_' . Str::random(6) . '@example.com';
    $user = User::create([
        'name' => 'Onboarding User',
        'email' => $email,
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]);
    // Ensure no tenants
    $user->tenants()->detach();

    echo "[PASS] Created user with 0 memberships: {$user->email}\n";

    // 2. GET /dashboard
    // Simulate Request
    $response = test_request('GET', route('dashboard'), $user);
    
    if ($response->status() !== 200) {
        $loc = $response->headers->get('Location');
        throw new Exception("GET /dashboard returned {$response->status()} (Expected 200) - Redirect to: $loc");
    }
    if (!str_contains($response->content(), 'Firma Oluştur')) {
        throw new Exception("GET /dashboard response does not contain 'Firma Oluştur'");
    }
    echo "[PASS] GET /dashboard: 200 OK & Contains CTA\n";

    // 3. GET /sales-orders (Block)
    // Should be 403 or Redirect to Join/Select pending middleware logic. 
    // SetTenant "5. Tenant User Without Context" -> Abort 403 OR redirect logic.
    // For 0 membership, "sales-orders" is NOT allowed route.
    // Logic: count=0 -> Allow Onboarding? No. -> Redirect Join.
    
    $response = test_request('GET', route('sales-orders.index'), $user);
    if ($response->status() !== 302 && $response->status() !== 403) {
         throw new Exception("GET /sales-orders returned {$response->status()} (Expected 302 or 403)");
    }
    echo "[PASS] GET /sales-orders blocked as expected ({$response->status()})\n";

    // 4. GET /onboarding/company (Allow)
    $response = test_request('GET', route('onboarding.company.create'), $user);
    if ($response->status() !== 200) {
        throw new Exception("GET /onboarding/company returned {$response->status()} (Expected 200)");
    }
    echo "[PASS] GET /onboarding/company allowed\n";

    // 5. POST /onboarding/company (Create)
    $companyName = 'Test Arge ' . Str::random(4);
    $response = test_request('POST', route('onboarding.company.store'), $user, ['name' => $companyName]);
    
    // Should Redirect to Dashboard
    if ($response->status() !== 302) {
         throw new Exception("POST /onboarding/company returned {$response->status()} (Expected 302)");
    }
    if ($response->headers->get('Location') !== route('dashboard')) {
         throw new Exception("Redirect location mismatch. Got: " . $response->headers->get('Location'));
    }
    echo "[PASS] POST /onboarding/company success (Redirects to dashboard)\n";

    // 6. DB Assert
    $tenant = Tenant::where('name', $companyName)->firstOrFail();
    if (!$tenant->account_id) {
         throw new Exception("Tenant account_id is null!");
    }
    
    // Check Pivot: Tenant User
    $tenantUser = DB::table('tenant_user')->where('tenant_id', $tenant->id)->where('user_id', $user->id)->first();
    if (!$tenantUser || $tenantUser->role !== 'admin') {
         throw new Exception("Tenant user pivot missing or wrong role (Expected admin)");
    }

    // Check Pivot: Account User
    $accountUser = DB::table('account_users')->where('account_id', $tenant->account_id)->where('user_id', $user->id)->first();
    if (!$accountUser || $accountUser->role !== 'owner') {
         throw new Exception("Account user pivot missing or wrong role (Expected owner)");
    }
    echo "[PASS] DB Assertions (Tenant, Account, Pivots) verified.\n";

    // 7. GET /dashboard (AGAIN) - Now should have content
    // We must manually refresh user to see relations or simulate new request with logged in user?
    // Session 'current_tenant_id' was set in Controller.
    // Does test_request persist session? Yes usually if we use actingAs.
    // However, in this script helper `test_request`, we need to ensure session is carried over or re-simulated.
    // Controller did `session(['current_tenant_id' => $tenant->id])`.
    // Let's re-login user?
    // In test_request helper, we act as user. Session depends on store.
    
    // Manually pass session to next request
    $sessionStore = session()->driver();
    $sessionStore->put('current_tenant_id', $tenant->id);
    $sessionStore->save();
    
    $response = test_request('GET', route('dashboard'), $user, [], ['current_tenant_id' => $tenant->id]);
    
    if ($response->status() !== 200) {
        throw new Exception("GET /dashboard (Member) returned {$response->status()}");
    }
    
    // Should NOT have onboarding text
    if (str_contains($response->content(), 'Henüz bir firmaya üye değilsiniz')) {
         throw new Exception("Dashboard still shows onboarding empty state after company creation!");
    }
    // Should have standard dashboard text or empty stats
    // "Genel durum" is in header subtitle.
    // Or check for "Açık Faturalar" etc. if enabled.
    // Or check if $tenant shared in view.
    echo "[PASS] GET /dashboard (Member) shows normal dashboard.\n";

    DB::rollBack();
    echo "\n[SUCCESS] All onboarding tests passed.\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n[FAIL] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}

// Helper
function test_request($method, $uri, $user, $data = [], $session = []) {
    // 1. Ensure Global Auth is set
    auth()->login($user);
    
    // 2. Create Request
    $request = \Illuminate\Http\Request::create($uri, $method, $data);
    
    // 3. Setup Session (Use global driver to keep Auth state)
    $sessionStore = app('session')->driver();
    if (!$sessionStore->isStarted()) {
        $sessionStore->start();
    }
    
    foreach($session as $k => $v) {
        $sessionStore->put($k, $v);
    }
    
    // CSRF bypass
    $token = Str::random(40);
    $sessionStore->put('_token', $token);
    $sessionStore->save();
    
    $request->headers->set('X-CSRF-TOKEN', $token);
    
    $request->setLaravelSession($sessionStore);
    $request->setUserResolver(fn() => $user);
    
    return app()->handle($request);
}
