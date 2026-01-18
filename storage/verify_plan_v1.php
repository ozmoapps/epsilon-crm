<?php

echo "--- Verify PR7a: Plan & Limits ---\n";

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Account;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

// 1. Setup Isolated SQLite DB
$tmpDbPath = storage_path('app/tmp/plan-verify-' . uniqid() . '.sqlite');
$tmpDir = dirname($tmpDbPath);
if (!is_dir($tmpDir)) {
    mkdir($tmpDir, 0755, true);
}
touch($tmpDbPath);

echo "[SETUP] Created temporary DB: $tmpDbPath\n";

// Configure DB to use this new file
Config::set('database.default', 'sqlite');
Config::set('database.connections.sqlite.database', $tmpDbPath);
DB::purge('sqlite');
DB::reconnect('sqlite');

try {
    // 2. Run Migrations on empty DB
    echo "[SETUP] Running migrations...\n";
    Artisan::call('migrate', ['--force' => true]);

    // 3. Verification Logic
    
    // Create Plan using existing key if seeded, or create new.
    // Since it's a fresh DB, we MUST create it if not seeded.
    // Migration might seed data? Assuming empty unless seeder run.
    
    echo "--- Creating Starter Plan ---\n";
    $planId = DB::table('plans')->where('key', 'starter')->value('id');

    if (!$planId) {
        $planId = DB::table('plans')->insertGetId([
            'key' => 'starter',
            'name_tr' => 'Başlangıç',
            'tenant_limit' => 1,
            'seat_limit' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "[SETUP] Created Starter Plan (ID: $planId)\n";
    } else {
        echo "[SETUP] Using existing Starter Plan (ID: $planId)\n";
    }

    // Create User & Account
    echo "--- Creating User & Account ---\n";
    $user = User::create([
        'name' => 'Plan Tester',
        'email' => 'verify_plan@test.com',
        'password' => bcrypt('password'),
    ]);

    $account = Account::create([
        'owner_user_id' => $user->id,
        'status' => 'active',
        'plan_id' => $planId, // Legacy FK
        'plan_key' => 'starter',
    ]);

    // Helper
    function checkLimits($acc, $expectedTenantLim, $expectedSeatLim) {
        if ($acc->effectiveTenantLimit() !== $expectedTenantLim) {
            echo "[FAIL] Tenant Limit mismatch. Expected $expectedTenantLim, got " . $acc->effectiveTenantLimit() . "\n";
            exit(1);
        }
        if ($acc->effectiveSeatLimit() !== $expectedSeatLim) {
            echo "[FAIL] Seat Limit mismatch. Expected $expectedSeatLim, got " . $acc->effectiveSeatLimit() . "\n";
            exit(1);
        }
        echo "[PASS] Limits OK ({$expectedTenantLim}, {$expectedSeatLim})\n";
    }

    // Verify Starter Limits
    echo "--- Checking Starter Limits ---\n";
    checkLimits($account, 1, 1);

    // Create Tenant
    echo "--- Creating Tenant 1 ---\n";
    $tenant1 = Tenant::create([
        'name' => 'Verify Plan T1',
        'account_id' => $account->id,
        'is_active' => true
    ]);
    // Add user to tenant (consumes seat)
    $tenant1->users()->attach($user, ['role' => 'admin']);

    $entitlements = app(\App\Services\EntitlementsService::class);
    if ($entitlements->accountTenantUsage($account) !== 1) {
        throw new Exception("Tenant Usage mismatch. Expected 1.");
    }
    if ($entitlements->accountSeatUsage($account) !== 1) {
        throw new Exception("Seat Usage mismatch. Expected 1.");
    }
    echo "[PASS] Tenant 1 usage logic OK.\n";

    // Try Create Tenant 2 (Should Fail logic check)
    if ($entitlements->canCreateTenant($account)) {
        throw new Exception("Should NOT be able to create tenant 2 on starter plan.");
    }
    echo "[PASS] Tenant creation blocked correctly.\n";

    // Upgrade Plan to 'team'
    echo "--- Upgrading to Team ---\n";
    $account->update(['plan_key' => 'team']);
    $account = $account->fresh();

    checkLimits($account, 4, 4);

    if (!$entitlements->canCreateTenant($account)) {
        throw new Exception("Should be able to create tenant 2 on Team plan.");
    }
    echo "[PASS] Upgrade OK.\n";

    // Config Accessor Check
    if ($account->plan_name !== 'Ekip') {
        throw new Exception("Plan Name mismatch. Expected 'Ekip', got " . $account->plan_name);
    }

    echo "\nVERIFY RESULT: PASS\n";

} catch (Exception $e) {
    echo "\n[CRITICAL ERROR] " . $e->getMessage() . "\n";
    if ($e instanceof \Illuminate\Database\QueryException) {
         echo "SQL: " . $e->getSql() . "\n";
    }
    exit(1);
} finally {
    // 4. Cleanup Tmp DB
    if (file_exists($tmpDbPath)) {
        DB::disconnect('sqlite'); // Ensure it's closed
        unlink($tmpDbPath);
        echo "[CLEANUP] Deleted temporary DB.\n";
    }
}
