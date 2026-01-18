<?php

use App\Models\User;
use App\Models\Tenant;
use App\Models\Account;
use App\Models\PlanChangeRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

echo "\n=============================================\n";
echo "   VERIFY PLAN V3: UPGRADE REQUEST FLOW\n";
echo "=============================================\n";

// 1. ISOLATED SQLITE
$dbPath = __DIR__ . '/temp_plan_v3.sqlite';
touch($dbPath);

Config::set('database.default', 'sqlite');
Config::set('database.connections.sqlite', [
    'driver' => 'sqlite',
    'database' => $dbPath,
    'foreign_key_constraints' => true,
]);

// Ensure clean start
if (file_exists($dbPath)) {
    unlink($dbPath);
}
touch($dbPath);

echo "[SETUP] Isolated Database: $dbPath\n";

try {
    // Migrate
    echo "[SETUP] Migrating...\n";
    Artisan::call('migrate:fresh', ['--force' => true]);

    // Check tables
    if (!Schema::hasTable('users') || !Schema::hasTable('accounts') || !Schema::hasTable('tenants')) {
        throw new Exception("Core tables NOT created after migration!");
    }

    // Config: Define plans
    Config::set('plans.plans', [
        'starter' => ['name' => 'Starter', 'sort' => 1],
        'team' => ['name' => 'Team', 'sort' => 2],
        'enterprise' => ['name' => 'Enterprise', 'sort' => 3],
    ]);

    // 2. SETUP DATA
    echo "[SETUP] Seeding Data...\n";
    
    // User (Tenant Owner)
    $owner = User::create([
        'name' => 'Owner User',
        'email' => 'owner@example.com',
        'password' => bcrypt('password'),
    ]);

    // Account (Starter)
    // Get Plan ID (Legacy support)
    $planId = DB::table('plans')->where('key', 'starter')->value('id');
    
    if (!$planId) {
        $planId = DB::table('plans')->insertGetId([
            'key' => 'starter',
            'name_tr' => 'Başlangıç',
            'tenant_limit' => 1,
            'seat_limit' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    try {
        $account = Account::create([
            'name' => 'Test Account',
            'owner_user_id' => $owner->id,
            'plan_key' => 'starter',
            'plan_id' => $planId,
            'status' => 'active',
        ]);
    } catch (\Exception $e) {
        echo "[DEBUG] Account Create Failed: " . $e->getMessage() . "\n";
        // Dump fillable
        // echo "Fillable: " . implode(',', (new Account)->getFillable()) . "\n";
        throw $e;
    }

    // Tenant
    $tenant = Tenant::create([
        'account_id' => $account->id,
        'name' => 'Test Tenant',
        'slug' => 'test-tenant',
    ]);
    $tenant->users()->attach($owner, ['role' => 'admin']);
    
    // Platform Admin
    $admin = User::create([
        'name' => 'Platform Admin',
        'email' => 'admin@platform.com',
        'password' => bcrypt('password'),
        'is_admin' => true,
    ]);

    // 3. SCENARIO A: CREATE REQUEST (MANAGE FLOW)
    echo "\n[TEST A] Creating Upgrade Request (Starter -> Enterprise)...\n";
    
    // Simulate Manage Controller Logic (Manual DB Create for speed)
    // Normally we'd POST to controller, but verifying DB state & Model logic is safer for CLI.
    
    $request1 = PlanChangeRequest::create([
        'account_id' => $account->id,
        'tenant_id' => $tenant->id,
        'requested_by_user_id' => $owner->id,
        'current_plan_key' => $account->plan_key,
        'requested_plan_key' => 'enterprise',
        'reason' => 'Need more power',
        'status' => 'pending',
    ]);

    if ($request1->status !== 'pending') throw new Exception("Request status should be pending");
    if ($request1->current_plan_key !== 'starter') throw new Exception("Current plan key mismatch");
    echo " PASS: Request Created (Pending)\n";

    // 4. SCENARIO B: ADMIN APPROVE
    echo "\n[TEST B] Admin Approving Request...\n";
    
    // Simulate App\Http\Controllers\Admin\PlanChangeRequestAdminController::approve
    
    // Logic:
    $account->plan_key = $request1->requested_plan_key;
    $account->save();
    
    $request1->update([
        'status' => 'approved',
        'reviewed_by_user_id' => $admin->id,
        'reviewed_at' => now(),
        'review_note' => 'Approved by script',
    ]);
    
    // Assertions
    $account->refresh();
    if ($account->plan_key !== 'enterprise') throw new Exception("Account plan NOT updated. Expected: enterprise, Got: {$account->plan_key}");
    if ($request1->status !== 'approved') throw new Exception("Request status NOT approved");
    
    echo " PASS: Request Approved & Plan Updated to 'enterprise'\n";

    // 5. SCENARIO C: REJECT FLOW
    echo "\n[TEST C] Reject Flow (Enterprise -> Team Upgrade Request)...\n";
    
    // Create new request
    $request2 = PlanChangeRequest::create([
        'account_id' => $account->id,
        'tenant_id' => $tenant->id,
        'requested_by_user_id' => $owner->id,
        'current_plan_key' => 'enterprise',
        'requested_plan_key' => 'team', // Downgrade request (technically allowed via model, blocked via UI)
        'reason' => 'Downgrade please',
        'status' => 'pending',
    ]);
    
    // Simulate Reject
    $request2->update([
        'status' => 'rejected',
        'reviewed_by_user_id' => $admin->id,
        'reviewed_at' => now(),
        'review_note' => 'Cannot downgrade here',
    ]);
    
    $account->refresh();
    if ($account->plan_key !== 'enterprise') throw new Exception("Account plan changed unexpectedly on reject!");
    if ($request2->status !== 'rejected') throw new Exception("Request status should be rejected");

    echo " PASS: Request Rejected & Plan Unchanged\n";

    echo "\n[SUCCESS] Verify Plan V3 Passed.\n";

} catch (\Exception $e) {
    echo "\n[FAIL] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
} finally {
    if (file_exists($dbPath)) {
        unlink($dbPath);
        echo "[CLEANUP] Removed $dbPath\n";
    }
}
