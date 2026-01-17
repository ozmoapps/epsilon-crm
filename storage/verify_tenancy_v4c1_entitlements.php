<?php

use App\Models\Tenant;
use App\Models\Plan;
use App\Models\Account;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Load app
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$failed = false;

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

echo "--- Verifying Entitlements (PR4C1) ---\n";

// 1. Schema Checks
test("Table 'plans' exists", fn() => Schema::hasTable('plans'));
test("Table 'accounts' exists", fn() => Schema::hasTable('accounts'));
test("Table 'account_users' exists", fn() => Schema::hasTable('account_users'));
test("Table 'tenants' has 'account_id'", fn() => Schema::hasColumn('tenants', 'account_id'));

// 2. Seeding Checks
test("Plans seeded (Starter, Pro, Master)", function() {
    return Plan::where('key', 'starter')->exists() &&
           Plan::where('key', 'pro')->exists() &&
           Plan::where('key', 'master')->exists();
});

// 3. Backfill Checks
test("All tenants have account_id (Backfill)", function() {
    return Tenant::whereNull('account_id')->count() === 0;
});

// 4. Integrity Checks
test("Every Tenant Account has an Owner", function() {
    $tenants = Tenant::with('account.owner')->get();
    foreach ($tenants as $tenant) {
        if (!$tenant->account) return false;
        if (!$tenant->account->owner) return false;
        
        // Ensure owner is in account_users
        $hasPivot = DB::table('account_users')
            ->where('account_id', $tenant->account->id)
            ->where('user_id', $tenant->account->owner_user_id)
            ->where('role', 'owner')
            ->exists();
        if (!$hasPivot) return false;
    }
    return true;
});

test("AccountUser Unique Constraint (account_id, user_id)", function() {
    $account = Account::first();
    if (!$account) return true; // Skip if no accounts

    $user = \App\Models\User::first();
    if (!$user) return true;

    // Ensure user is in account
    DB::table('account_users')->updateOrInsert(
        ['account_id' => $account->id, 'user_id' => $user->id],
        ['role' => 'member']
    );

    try {
        // Try to insert duplicate
        DB::table('account_users')->insert([
            'account_id' => $account->id,
            'user_id' => $user->id,
            'role' => 'billing_admin'
        ]);
        return false; // Should have failed
    } catch (\Illuminate\Database\UniqueConstraintViolationException $e) { // Or PDOException
        return true; 
    } catch (\Exception $e) {
        // Catch generic info if unique constraint triggers a general query error
        if (str_contains($e->getMessage(), 'UNIQUE constraint failed') || str_contains($e->getMessage(), 'Duplicate entry')) {
            return true;
        }
        throw $e;
    }
});

// 5. Helper Checks
test("Helper: Effective Limits", function() {
    $starter = Plan::where('key', 'starter')->first();
    $account = new Account();
    $account->plan_id = $starter->id;
    $account->setRelation('plan', $starter);
    $account->extra_seats_purchased = 2;

    $seatLimitOK = $account->effectiveSeatLimit() === ($starter->seat_limit + 2);
    $tenantLimitOK = $account->effectiveTenantLimit() === $starter->tenant_limit;

    return $seatLimitOK && $tenantLimitOK;
});

exit($failed ? 1 : 0);
