<?php

use App\Models\Tenant;
use App\Models\Plan;
use App\Models\Account;
use App\Models\User;
use App\Models\TenantInvitation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Services\EntitlementsService;

// Load app
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$failed = false;
$entitlements = new EntitlementsService();

function test($description, $callback) {
    global $failed;
    try {
        if ($callback()) {
            echo "PASS: $description\n";
        } else {
            echo "FAIL: $description\n";
            $failed = true;
        }
    } catch (Exception $e) {
        echo "FAIL: $description - Exception: " . $e->getMessage() . "\n";
        $failed = true;
    }
}

// Ensure plans exist
if (Plan::count() === 0) {
    echo "Requires PR4C1 seeds. Run migration first.\n";
    exit(1);
}

echo "--- Verifying Enforcement (PR4C2) ---\n";

// --- 1. Tenant Limit Tests ---

// Setup: Create fresh users for tests to avoid collisions
$starterUser = User::create([
    'name' => 'Starter Test',
    'email' => 'starter_test_'.uniqid().'@test.com',
    'password' => bcrypt('password'),
    'is_admin' => false
]);
$proUser = User::create([
    'name' => 'Pro Test',
    'email' => 'pro_test_'.uniqid().'@test.com',
    'password' => bcrypt('password'),
    'is_admin' => false
]);

// Helper: Delete old accounts for these users
Account::where('owner_user_id', $starterUser->id)->delete();
Account::where('owner_user_id', $proUser->id)->delete();

// Setup Accounts
$starterPlan = Plan::where('key', 'starter')->first();
$proPlan = Plan::where('key', 'pro')->first();

$starterAccount = Account::create([
    'owner_user_id' => $starterUser->id,
    'plan_id' => $starterPlan->id,
    'status' => 'active'
]);

$proAccount = Account::create([
    'owner_user_id' => $proUser->id,
    'plan_id' => $proPlan->id,
    'status' => 'active'
]);

test("EntitlementsService: canCreateTenant (Starter - 0 usage)", function() use ($entitlements, $starterAccount) {
    return $entitlements->canCreateTenant($starterAccount) === true;
});

// Create 1st tenant for Starter (Should be OK)
$t1 = Tenant::create(['name' => 'T1 Starter', 'is_active' => true]);
$t1->account()->associate($starterAccount);
$t1->save();
$t1->users()->attach($starterUser->id, ['role' => 'admin']);

test("EntitlementsService: canCreateTenant (Starter - 1 usage -> Full)", function() use ($entitlements, $starterAccount) {
    return $entitlements->canCreateTenant($starterAccount) === false; // Limit is 1
});

// Pro Account: Add 4 tenants
for($i=1; $i<=4; $i++) {
    $t = Tenant::create(['name' => "T$i Pro", 'is_active' => true]);
    $t->account()->associate($proAccount);
    $t->save();
    $t->users()->attach($proUser->id, ['role' => 'admin']);
}

test("EntitlementsService: canCreateTenant (Pro - 4 usage -> Full)", function() use ($entitlements, $proAccount) {
    return $entitlements->canCreateTenant($proAccount) === false; // Limit is 4
});

// --- 2. Seat Limit Tests ---

// Starter Account: Currently 1 user (Owner) in 1 tenant.
// Starter limit is 1. So it should be FULL.
test("EntitlementsService: canAddSeat (Starter - 1 User -> Full)", function() use ($entitlements, $starterAccount) {
    return $entitlements->canAddSeat($starterAccount) === false;
});

test("EntitlementsService: canAddSeat (Starter - Same User Bypass)", function() use ($entitlements, $starterAccount, $starterUser) {
    // Adding the specific user's email again should be ALLOWED because they already have a seat
    return $entitlements->canAddSeat($starterAccount, $starterUser->email) === true;
});

test("EntitlementsService: canAddSeat (Starter - New User -> Block)", function() use ($entitlements, $starterAccount) {
    return $entitlements->canAddSeat($starterAccount, 'newuser@test.com') === false;
});


// --- 3. Pending Invites Counting ---
// Enable config manually for test just in case
config(['entitlements.count_pending_invites_as_seats' => true]);

// Clean up invites for this tenant
TenantInvitation::where('tenant_id', $t1->id)->delete();

// We need a slot to test pending invite counting. 
// Starter has 1 slot, filled by Owner. 
// Let's INCREASE limit temporarily to 2 to test "1 Owner + 1 Pending = Full"
$starterPlan->update(['seat_limit' => 2]);
$starterAccount->load('plan'); // Refresh

test("EntitlementsService: Seat Limit increased to 2, Usage 1 -> Can Add", function() use ($entitlements, $starterAccount) {
    return $entitlements->canAddSeat($starterAccount, 'pending@test.com') === true;
});

// Create Pending Invite
TenantInvitation::create([
    'tenant_id' => $t1->id,
    'email' => 'pending@test.com',
    'token_hash' => 'hash_pending_'.uniqid(),
    'role' => 'staff',
    'expires_at' => now()->addDays(7)
]);

test("EntitlementsService: Pending Invite counts as seat", function() use ($entitlements, $starterAccount) {
    // Usage: 1 Owner + 1 Pending = 2. Limit 2. Full.
    return $entitlements->accountSeatUsage($starterAccount) === 2;
});

test("EntitlementsService: Can Add NEW Seat now? (Should be NO)", function() use ($entitlements, $starterAccount) {
    return $entitlements->canAddSeat($starterAccount, 'third@test.com') === false;
});

test("EntitlementsService: Double Count Guard (Invite existing user)", function() use ($entitlements, $starterAccount, $starterUser, $t1) {
    // Create invite for the OWNER (who is already a user)
    TenantInvitation::create([
        'tenant_id' => $t1->id,
        'email' => $starterUser->email,
        'token_hash' => 'hash_owner_'.uniqid(),
        'role' => 'staff',
        'expires_at' => now()->addDays(7)
    ]);
    
    // Usage should still be 2 (1 Owner + 1 Pending unique). 
    // The invite for owner should NOT add +1.
    return $entitlements->accountSeatUsage($starterAccount) === 2;
});

test("EntitlementsService: Double Count Guard (Duplicate Pending Email)", function() use ($entitlements, $starterAccount, $t1) {
    // Invite same 'pending@test.com' again (maybe logic allows duplicates in table, but entitlement should distinct)
    TenantInvitation::create([
        'tenant_id' => $t1->id,
        'email' => 'pending@test.com',
        'token_hash' => 'hash_dup_'.uniqid(),
        'role' => 'staff',
        'expires_at' => now()->addDays(7)
    ]);
    
    // Usage should still be 2
    return $entitlements->accountSeatUsage($starterAccount) === 2;
});

// Revert limit
$starterPlan->update(['seat_limit' => 1]);


// --- 4. Idempotency Check ---
// Simulate Accept Logic
test("Idempotency: Re-Adding Existing User is OK", function() use ($entitlements, $starterAccount, $starterUser) {
    // Sync account user again
    $entitlements->syncAccountUser($starterAccount, $starterUser);
    return true; // No error means pass
});

// --- 5. Mini-Check (PR4C3 Prep) ---
// Verify T1 (created via code Tenant::create + attach)
// Verify Platform Admin detachment logic (simulated)
// The TenantAdminController@store logic was:
// $tenant->users()->syncWithoutDetaching([$ownerUser->id => ['role' => 'admin']]);
// It did NOT attach auth()->user() unless auth()->user() IS the owner.
// Let's create a new tenant via controller logic simulation to be sure.

// Create a platform admin user
$adminUser = User::where('is_admin', true)->first();
if (!$adminUser) {
    $adminUser = User::create([
        'name' => 'Platform Admin', 
        'email' => 'admin_mini_'.uniqid().'@test.com', 
        'password' => bcrypt('password'),
        'is_admin' => true
    ]);
}

// Simulate Tenant Creation by Platform Admin for Pro User
$startUsage = $entitlements->accountTenantUsage($proAccount);
// Create T_Mini
$tMini = Tenant::create(['name' => 'T_Mini', 'is_active' => true]);
$tMini->account()->associate($proAccount);
$tMini->save();
// Attach Owner (Simulate Controller)
$tMini->users()->syncWithoutDetaching([$proUser->id => ['role' => 'admin']]);
// Ensure Admin NOT attached (unless same)
// $adminUser is not $proUser.

test("Mini-Check: Owner (ProUser) is attached as admin", function() use ($tMini, $proUser) {
    return $tMini->users()->where('user_id', $proUser->id)->wherePivot('role', 'admin')->exists();
});

test("Mini-Check: Platform Admin is NOT attached", function() use ($tMini, $adminUser) {
    return !$tMini->users()->where('user_id', $adminUser->id)->exists();
});

test("Mini-Check: Account Users contains Owner", function() use ($entitlements, $proAccount, $proUser) {
    // Sync logic in controller calls syncAccountUser
    $entitlements->syncAccountUser($proAccount, $proUser, 'member');
    
    return DB::table('account_users')
        ->where('account_id', $proAccount->id)
        ->where('user_id', $proUser->id)
        ->exists();
});

// Cleanup Mini Check
$tMini->users()->detach();
$tMini->delete();
if (str_contains($adminUser->email, 'admin_mini_')) {
    $adminUser->delete();
}


// Cleanup
// Delete child records first to avoid FK issues
TenantInvitation::whereIn('tenant_id', [$t1->id])->delete();

// Detach users from tenants
DB::table('tenant_user')
    ->whereIn('tenant_id', $starterAccount->tenants()->pluck('id'))
    ->orWhereIn('tenant_id', $proAccount->tenants()->pluck('id'))
    ->delete();

// Delete Tenants
Tenant::whereIn('id', $starterAccount->tenants()->pluck('id'))->delete();
Tenant::whereIn('id', $proAccount->tenants()->pluck('id'))->delete();

// Delete Accounts
$starterAccount->delete();
$proAccount->delete();

// Finally delete Users
$starterUser->delete(); 
$proUser->delete();

exit($failed ? 1 : 0);
