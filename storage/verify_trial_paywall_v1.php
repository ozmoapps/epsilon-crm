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

echo "--- VERIFICATION START: Trial & Paywall ---\n";

try {
    DB::beginTransaction();

    // 1. Setup: Ensure Plan Exists
    $plan = \App\Models\Plan::where('key', 'starter')->first();
    if (!$plan) {
         throw new Exception("Starter plan not found in DB! Seed is required.");
    }

    // 2. Create User (Expired Trial Scenario)
    $email = 'paywall_test_' . Str::random(6) . '@example.com';
    $user = User::create([
        'name' => 'Paywall User',
        'email' => $email,
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]);

    // 3. Create Account (Expired)
    $account = Account::create([
        'owner_user_id' => $user->id,
        'plan_id' => $plan->id,
        'plan_key' => 'starter',
        'status' => 'active',
        'starts_at' => now()->subDays(10),
        'ends_at' => now()->subDay(), // EXPIRED 1 day ago
        'billing_subscription_id' => null, // Not Paid
    ]);

    // 4. Create Tenant + Pivots (To ensure they have membership and aren't caught by Onboarding flow)
    $tenant = Tenant::create([
        'account_id' => $account->id,
        'name' => 'Expired Corp',
        'slug' => 'expired-corp-' . Str::random(4),
        'domain' => 'expired-' . Str::random(4) . '.test',
        'is_active' => true,
    ]);

    DB::table('account_users')->insert([
        'account_id' => $account->id,
        'user_id' => $user->id,
        'role' => 'owner',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('tenant_user')->insert([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role' => 'admin', // Admin of tenant, but not Platform Admin
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Set Session Context (Simulate logged in user with context)
    $sessionStore = session()->driver();
    $sessionStore->put('current_tenant_id', $tenant->id);
    $sessionStore->save();

    echo "[PASS] Created Expired User & Account ({$user->email})\n";

    // 5. GET /dashboard -> Should Redirect to Paywall
    $response = test_request('GET', route('dashboard'), $user, [], ['current_tenant_id' => $tenant->id]);
    
    if ($response->status() !== 302) {
        throw new Exception("GET /dashboard (Expired) returned {$response->status()} (Expected 302)");
    }
    if ($response->headers->get('Location') !== route('billing.paywall')) {
        throw new Exception("Redirect location mismatch. Got: " . $response->headers->get('Location') . " Expected: " . route('billing.paywall'));
    }
    echo "[PASS] GET /dashboard redirected to Paywall.\n";

    // 6. GET /billing -> Should be 200 OK
    $response = test_request('GET', route('billing.paywall'), $user);
    
    if ($response->status() !== 200) {
        throw new Exception("GET /billing (Paywall) returned {$response->status()} (Expected 200)");
    }
    if (!str_contains($response->content(), 'Deneme Süreniz Doldu')) {
        throw new Exception("GET /billing content mismatch (missing 'Deneme Süreniz Doldu')");
    }
    echo "[PASS] GET /billing accessible and shows correct content.\n";

    // 7. Test Bypass Attempt: Tenant Route (/sales-orders)
    $response = test_request('GET', route('sales-orders.index'), $user, [], ['current_tenant_id' => $tenant->id]);
    if ($response->status() !== 302) {
         throw new Exception("GET /sales-orders (Expired) returned {$response->status()} (Expected 302)");
    }
    if ($response->headers->get('Location') !== route('billing.paywall')) {
         throw new Exception("Tenant Route Redirect mismatch. Got: " . $response->headers->get('Location'));
    }
    echo "[PASS] Tenant Route access blocked and redirected to Paywall.\n";


    // 8. Test ACTIVE User (Future/Paid) - Should NOT access paywall logic (Dashboard OK)
    $account->update(['ends_at' => now()->addDays(5)]); // Future
    
    $response = test_request('GET', route('dashboard'), $user, [], ['current_tenant_id' => $tenant->id]);
    if ($response->status() !== 200) {
        throw new Exception("GET /dashboard (Active) returned {$response->status()} (Expected 200)");
    }
    echo "[PASS] Active User can access Dashboard.\n";

    // 9. Test Member Access (Hotfix Verify: Owner Expired + Member Active)
    // Create new User 2 (Member)
    $memberUser = User::create([
        'name' => 'Member User',
        'email' => 'member_' . Str::random(6) . '@example.com',
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]);

    // Scenario: User OWNS an expired account (e.g. failed trial)
    $expiredOwnAccount = Account::create([
        'owner_user_id' => $memberUser->id,
        'plan_id' => $plan->id,
        'plan_key' => 'starter',
        'status' => 'active',
        'ends_at' => now()->subDays(10), // EXPIRED
    ]);
    // Link to dummy tenant to ensure "HasAnyAccount" check sees it?
    // Actually the check is on Account model owner_user_id directly. This is enough.

    // Scenario: User is ALSO a Member of an ACTIVE account (invited)
    // Scenario: User is ALSO a Member of an ACTIVE account (invited)
    $ownerUser = User::create(['name' => 'Owner', 'email' => 'owner_'.Str::random(5).'@example.com', 'password' => Hash::make('password')]);
    $activeAccount = Account::create([
        'owner_user_id' => $ownerUser->id,
        'plan_id' => $plan->id,
        'plan_key' => 'starter',
        'status' => 'active',
        'ends_at' => now()->addYear(), // ACTIVE
    ]); 
    $activeTenant = Tenant::create([
        'account_id' => $activeAccount->id,
        'name' => 'Active Member Corp',
        'slug' => 'active-mem-' . Str::random(4),
        'domain' => 'active-mem-' . Str::random(4) . '.test',
        'is_active' => true,
    ]);
    
    // Link Member User to Active Tenant
    DB::table('tenant_user')->insert([
        'tenant_id' => $activeTenant->id,
        'user_id' => $memberUser->id,
        'role' => 'staff',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    echo "[PASS] Created User (Owner Expired + Member Active).\n";

    // Expectation: Should be allowed (200) because they have Access to an Active Tenant.
    // Current Buggy Logic: Will Block because "Owned Active" is false, and "Owned Any" is true.
    
    $response = test_request('GET', route('dashboard'), $memberUser, [], ['current_tenant_id' => $activeTenant->id]);
    
    if ($response->status() !== 200) {
        $loc = $response->headers->get('Location');
        // If current buggy logic redirects to paywall, this will fail.
        throw new Exception("GET /dashboard (Owner Expired + Member Active) returned {$response->status()} (Bug Reproduced) - Redirect to: $loc");
    }
    echo "[PASS] Member of Active Tenant (who owns expired) can access Dashboard.\n";

    // 10. Test Trial Abuse Prevention (PR14d)
    // User owns Expired Account, No active associations (using our first user 'user')
    // They should NOT be able to create a new company.
    
    // Ensure 'user' (from step 2) is still in state: Owns Expired Account.
    // NOTE: Step 8 made this account ACTIVE. We must revert it to EXPIRED for this test.
    $account->update(['ends_at' => now()->subDays(5)]);

    $companyName2 = 'Abuse Test ' . Str::random(4);
    echo "Testing Trial Abuse Prevention for user: {$user->email}...\n";
    
    // Try POST /onboarding/company (Create new company)
    // Expected: 302 Redirect to billing.paywall
    $response = test_request('POST', route('onboarding.company.store'), $user, ['name' => $companyName2]);
    
    if ($response->status() !== 302) {
         throw new Exception("POST /onboarding/company (Expired User) returned {$response->status()} (Expected 302)");
    }
    
    $loc = $response->headers->get('Location');
    if ($loc !== route('billing.paywall')) {
         throw new Exception("Redirect location mismatch for Abuse Prevention. Got: $loc Expected: " . route('billing.paywall'));
    }
    echo "[PASS] Expired User BLOCKED from creating new company (Redirected to Paywall).\n";
    
    // 11. Test Fresh User (Should be allowed)
    $freshUser = User::create(['name'=>'Fresh', 'email'=>'fresh_'.Str::random(5).'@example.com', 'password'=>Hash::make('x')]);
    // No accounts.
    echo "Testing Fresh User creation...\n";
    $companyName3 = 'Fresh Company ' . Str::random(4);
    $response = test_request('POST', route('onboarding.company.store'), $freshUser, ['name' => $companyName3]);
    
    if ($response->status() !== 302) {
        throw new Exception("POST /onboarding/company (Fresh User) returned {$response->status()} (Expected 302 - likely to dashboard)");
    }
    $loc = $response->headers->get('Location');
    if ($loc !== route('dashboard')) {
        throw new Exception("Fresh User Redirect mismatch. Got: $loc Expected: " . route('dashboard'));
    }
    echo "[PASS] Fresh User ALLOWED to create new company.\n";



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
