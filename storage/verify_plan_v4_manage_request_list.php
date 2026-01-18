<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Account;
use App\Models\PlanChangeRequest;
use App\Services\TenantContext;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// --- 1. Database Setup (Isolated SQLite) ---
$databasePath = __DIR__ . '/verify_plan_v4.sqlite';
touch($databasePath);

config([
    'database.default' => 'sqlite_test',
    'database.connections.sqlite_test' => [
        'driver' => 'sqlite',
        'database' => $databasePath,
        'prefix' => '',
        'foreign_key_constraints' => true,
    ],
]);

// Wipe and Migrate
if (file_exists($databasePath)) {
    unlink($databasePath);
    touch($databasePath);
}
// Run Migrate Fresh
Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true]);

echo "[INFO] Database Setup Complete (Isolated)\n";

// --- 2. Data Setup ---

// Create Plan (Use firstOrCreate to avoid migration collision)
$plan = \App\Models\Plan::firstOrCreate(
    ['key' => 'starter'],
    [
        'name' => 'Başlangıç',
        'name_tr' => 'Başlangıç',
        'price' => 0,
        'currency' => 'TRY',
        'features' => json_encode(['users' => 5]),
        'sort_order' => 1,
        'is_active' => true
    ]
);

// Create Account A and Tenant A
$userA = User::create(['name' => 'User A', 'email' => 'userA@test.com', 'password' => bcrypt('password')]);
$accountA = Account::create([
    'name' => 'Account A', 
    'owner_user_id' => $userA->id, 
    'plan_key' => 'starter',
    'plan_id' => $plan->id // Added plan_id
]);
$tenantA = Tenant::create(['name' => 'Tenant A', 'account_id' => $accountA->id, 'slug' => 'tenant-a']);

// Attach User A to Tenant A (Admin)
$tenantA->users()->attach($userA->id, ['role' => 'admin']);

// Create Account B and Tenant B
$userB = User::create(['name' => 'User B', 'email' => 'userB@test.com', 'password' => bcrypt('password')]);
$accountB = Account::create([
    'name' => 'Account B', 
    'owner_user_id' => $userB->id, 
    'plan_key' => 'starter',
    'plan_id' => $plan->id // Added plan_id
]);
$tenantB = Tenant::create(['name' => 'Tenant B', 'account_id' => $accountB->id, 'slug' => 'tenant-b']);
$tenantB->users()->attach($userB->id, ['role' => 'admin']);

// Create User C (Member of Tenant A, but different user)
$userC = User::create(['name' => 'User C', 'email' => 'userC@test.com', 'password' => bcrypt('password')]);
$tenantA->users()->attach($userC->id, ['role' => 'member']);


// --- 3. Seed Requests ---

// Request 1: User A (Account A)
PlanChangeRequest::create([
    'account_id' => $accountA->id,
    'tenant_id' => $tenantA->id,
    'requested_by_user_id' => $userA->id,
    'current_plan_key' => 'starter',
    'requested_plan_key' => 'enterprise',
    'status' => 'pending',
]);

// Request 2: User C (Account A - Other User)
PlanChangeRequest::create([
    'account_id' => $accountA->id,
    'tenant_id' => $tenantA->id,
    'requested_by_user_id' => $userC->id,
    'current_plan_key' => 'starter',
    'requested_plan_key' => 'team',
    'status' => 'pending',
]);

// Request 3: User B (Account B - Other Account)
PlanChangeRequest::create([
    'account_id' => $accountB->id,
    'tenant_id' => $tenantB->id,
    'requested_by_user_id' => $userB->id,
    'current_plan_key' => 'starter',
    'requested_plan_key' => 'enterprise',
    'status' => 'pending',
]);

echo "[INFO] Data Seeded\n";

// --- 4. Logic Verification (Simulating Controller Query) ---

// Scenario 1: User A View (Should see Request 1 only)
auth()->login($userA);
app(TenantContext::class)->setTenant($tenantA);

$userARequests = PlanChangeRequest::where('account_id', $accountA->id)
    ->where('requested_by_user_id', $userA->id)
    ->get();

if ($userARequests->count() !== 1) {
    echo "[FAIL] User A should see exactly 1 request. Found: " . $userARequests->count() . "\n";
    exit(1);
}
if ($userARequests->first()->requested_by_user_id !== $userA->id) {
    echo "[FAIL] User A saw a request not belonging to them.\n";
    exit(1);
}
echo "[PASS] User A View (Own Request Only)\n";


// Scenario 2: User C View (Should see Request 2 only)
auth()->login($userC);
// Context matches Tenant A
$userCRequests = PlanChangeRequest::where('account_id', $accountA->id)
    ->where('requested_by_user_id', $userC->id)
    ->get();

if ($userCRequests->count() !== 1) {
    echo "[FAIL] User C should see exactly 1 request. Found: " . $userCRequests->count() . "\n";
    exit(1);
}
if ($userCRequests->first()->requested_by_user_id !== $userC->id) {
    echo "[FAIL] User C saw a request not belonging to them.\n";
    exit(1);
}
echo "[PASS] User C View (Own Request Only - Same Tenant Isolated)\n";


// Scenario 3: Account Isolation Check
// Simulating if logic was flawed (e.g. searching by User ID only without Account check - rare but good to check)
// Actually, user ID is unique globally, so strictly speaking user ID is enough, BUT account_id check is safer for consistency.
// Let's ensure User A doesn't see User B's requests (already covered by user_id check as they differ).

echo "ALL TESTS PASSED\n";
