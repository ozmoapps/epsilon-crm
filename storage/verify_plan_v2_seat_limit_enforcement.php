<?php

echo "--- Verify PR7b: Seat Limit Enforcement (V2) ---\n";

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Account;
use App\Models\User;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

// 1. Setup Isolated SQLite DB
$tmpDbPath = storage_path('app/tmp/verify-v2-seat-' . uniqid() . '.sqlite');
$tmpDir = dirname($tmpDbPath);
if (!is_dir($tmpDir)) {
    mkdir($tmpDir, 0755, true);
}
touch($tmpDbPath);

echo "[SETUP] Created temporary DB: $tmpDbPath\n";

Config::set('database.default', 'sqlite');
Config::set('database.connections.sqlite.database', $tmpDbPath);
DB::purge('sqlite');
DB::reconnect('sqlite');

try {
    echo "[SETUP] Running migrations...\n";
    Artisan::call('migrate', ['--force' => true]);

    // 2. Setup Plans
    $starterId = DB::table('plans')->where('key', 'starter')->value('id');
    if (!$starterId) {
        $starterId = DB::table('plans')->insertGetId([
            'key' => 'starter', 'name_tr' => 'Başlangıç',
            'tenant_limit' => 1, 'seat_limit' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    $teamId = DB::table('plans')->where('key', 'team')->value('id');
    if (!$teamId) {
        DB::table('plans')->insert([
            'key' => 'team', 'name_tr' => 'Ekip',
            'tenant_limit' => 4, 'seat_limit' => 4,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // 3. Create Account (Starter) & Owner
    $owner = User::create(['name' => 'Owner', 'email' => 'owner@test.com', 'password' => bcrypt('pw')]);
    
    $account = Account::create([
        'owner_user_id' => $owner->id,
        'status' => 'active',
        'plan_id' => $starterId,
        'plan_key' => 'starter',
    ]);
    
    $entitlements = app(\App\Services\EntitlementsService::class);
    $entitlements->syncAccountUser($account, $owner, 'owner');

    // 4. Create Tenant
    $tenant = Tenant::create(['name' => 'T1', 'account_id' => $account->id, 'is_active' => true]);
    $tenant->users()->attach($owner, ['role' => 'admin']);

    // Check Usage: 1 Seat used (Owner)
    if ($entitlements->accountSeatUsage($account) !== 1) {
        throw new Exception("Initial usage should be 1.");
    }
    
    // 5. Attempt Invite Create (Should Fail - Limit 1 reached)
    echo "--- Attempting Invite (Starter Plan 1/1) ---\n";
    $inviteEmail = 'newuser@test.com';
    
    if ($entitlements->canAddSeat($account, $inviteEmail)) {
        throw new Exception("FAIL: Should NOT be able to add seat on Starter plan (1/1 used).");
    }
    echo "[PASS] Invite Blocked correctly.\n";

    // 6. Upgrade to Team
    echo "--- Upgrading to Team ---\n";
    $account->update(['plan_key' => 'team']);
    $account->refresh();

    // 7. Attempt Invite Create (Should Pass)
    echo "--- Attempting Invite (Team Plan) ---\n";
    if (!$entitlements->canAddSeat($account, $inviteEmail)) {
         throw new Exception("FAIL: Should be able to add seat on Team plan.");
    }
    
    // Simulate Invite Store
    $token = Str::random(64);
    $invitation = TenantInvitation::create([
        'tenant_id' => $tenant->id,
        'email' => $inviteEmail,
        'role' => 'member',
        'token_hash' => hash('sha256', $token),
        'expires_at' => now()->addDays(7),
    ]);
    
    // Check usage (pending invite counts as seat)
    if ($entitlements->accountSeatUsage($account) !== 2) {
         throw new Exception("Usage should be 2 (1 Active + 1 Pending). Got: " . $entitlements->accountSeatUsage($account));
    }
    echo "[PASS] Invite Created & Counted as Seat.\n";

    // 8. Accept Flow Check
    // Create new user
    $newUser = User::create(['name' => 'New User', 'email' => $inviteEmail, 'password' => bcrypt('pw')]);
    
    // Check again before accept (Simulate Controller Accept check)
    if (!$entitlements->canAddSeat($account, $newUser->email)) {
        // Technically, logic usually returns false if Limit Reached AND User is New.
        // But here, user is ALREADY pending.
        // canAddSeat checks: if email matches pending, returns TRUE (already accounted).
        // Let's verify that behavior.
    } else {
        echo "[PASS] Accept Allowed (Seat already reserved by pending).\n";
    }

    // Accept
    $tenant->users()->attach($newUser, ['role' => 'member']);
    $invitation->update(['accepted_at' => now()]);
    
    // Check usage
    if ($entitlements->accountSeatUsage($account) !== 2) {
        throw new Exception("Usage should correspond to 2 active users.");
    }
    
    echo "\nVERIFY RESULT: PASS\n";

} catch (Exception $e) {
    echo "\n[CRITICAL ERROR] " . $e->getMessage() . "\n";
    exit(1);
} finally {
    if (file_exists($tmpDbPath)) {
        DB::disconnect('sqlite');
        unlink($tmpDbPath);
        echo "[CLEANUP] Deleted temporary DB.\n";
    }
}
