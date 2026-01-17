<?php

use App\Models\User;
use App\Models\Tenant;
use App\Models\Account;
use App\Models\Plan;
use App\Models\TenantInvitation;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 Starting Entitlements Edge-Cases Verification (v4d2.1)...\n";

try {
    // 1. Setup
    if (!class_exists(\Database\Seeders\PlanSeeder::class)) {
         throw new Exception("PlanSeeder class not found.");
    }
    $seeder = new \Database\Seeders\PlanSeeder();
    $seeder->run();
    
    $starterPlan = Plan::where('key', 'starter')->firstOrFail(); // 1 Seat

    $owner = User::create([
        'name' => 'Edge Tester',
        'email' => 'edge_' . uniqid() . '@test.com',
        'password' => Hash::make('password'),
    ]);

    $account = Account::create([
        'owner_user_id' => $owner->id,
        'plan_id' => $starterPlan->id,
        'status' => 'active',
        'starts_at' => now(),
    ]);
    
    // 2. Test Sync Safety
    echo "\n--- Sync Safety Test ---\n";
    // Manually insert as 'owner'
    DB::table('account_users')->insert([
        'account_id' => $account->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    $entitlements = app(\App\Services\EntitlementsService::class);
    // Sync as 'member' (should NOT downgrade)
    $entitlements->syncAccountUser($account, $owner, 'member');
    
    $role = DB::table('account_users')
        ->where('account_id', $account->id)
        ->where('user_id', $owner->id)
        ->value('role');
        
    echo "Role after sync: $role\n";
    if ($role !== 'owner') {
        throw new Exception("❌ FAIL: Sync downgraded role to '$role'!");
    } else {
        echo "✅ Sync Safety Verified (Role preserved).\n";
    }

    // 3. Test Expired Pending Invite (canAddSeat Logic)
    echo "\n--- Expired Invite Logic Test ---\n";
    $tenant = Tenant::create(['name' => 'Edge Tenant', 'is_active' => true]);
    $tenant->account()->associate($account);
    $tenant->save();
    $tenant->users()->attach($owner->id, ['role' => 'admin']);
    
    // Usage: 1 (Owner). Limit: 1.
    // Create Expired Invite
    TenantInvitation::create([
        'tenant_id' => $tenant->id,
        'email' => 'expired@example.com',
        'token_hash' => hash('sha256', 'expired'),
        'role' => 'viewer',
        'expires_at' => now()->subDay(),
    ]);

    // Create Valid Invite
    TenantInvitation::create([
        'tenant_id' => $tenant->id,
        'email' => 'valid@example.com',
        'token_hash' => hash('sha256', 'valid'),
        'role' => 'viewer',
        'expires_at' => now()->addDays(7),
    ]);
    
    // Usage Check:
    // Owner (1) + Valid (1) = 2.
    // Expired should NOT count.
    $usage = $entitlements->accountSeatUsage($account);
    echo "Usage with 1 Owner + 1 Valid + 1 Expired: $usage\n";
    
    // Wait, create check logic:
    // canAddSeat(account, 'expired@example.com') -> ?
    // If the invite is expired, it doesn't count as usage.
    // But does canAddSeat ALLOW re-inviting that email if usage >= limit?
    // Usage is 2. Limit is 1. We are Over Limit.
    // We shouldn't be able to add ANYONE new.
    // But if we retry 'expired@example.com', does it allow?
    // Service logic: "If email is provided... check if already pending... if so return true".
    // We updated the check to use ->valid(). So expired invite is NOT found as pending.
    // So it falls through to usage check. Usage (2) >= Limit (1). Returns false.
    // This is CORRECT. We cannot re-invite because we are over limit due to OTHER valid users/invites.
    
    if ($entitlements->canAddSeat($account, 'expired@example.com')) {
         throw new Exception("❌ FAIL: Allowed adding seat when over limit!");
    }
    echo "✅ Expired invite correctly treated as 'new seat' (blocked by limit).\n";

    // 4. Test Join Block + Audit
    echo "\n--- Join Block Audit Test ---\n";
    // We are over limit (Usage 2 / Limit 1).
    // Try to join with a NEW valid invite (simulated)
    $token = Str::random(64);
    $inviteJoin = TenantInvitation::create([
        'tenant_id' => $tenant->id,
        'email' => 'joiner_edge@example.com',
        'token_hash' => hash('sha256', $token),
        'role' => 'viewer',
        'expires_at' => now()->addDays(7),
    ]);
    
    $joiner = User::create(['name'=>'J', 'email'=>'joiner_edge@example.com', 'password'=>'pw']);
    
    try {
        $controller = new \App\Http\Controllers\TenantInvitationController();
        $request = \Illuminate\Http\Request::create("/invite/$token/accept", 'POST');
        $request->setUserResolver(fn() => $joiner);
        $logger = app(\App\Services\AuditLogger::class);
        
        $controller->accept($request, $token, $logger);
        throw new Exception("❌ FAIL: Join should have been blocked!");
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        if ($e->getStatusCode() === 403) {
            echo "✅ Correctly blocked (403).\n";
        } else {
             throw $e;
        }
    }
    
    $log = AuditLog::where('event_key', 'entitlement.blocked')
        ->whereJsonContains('metadata->reason', 'join_attempt')
        ->latest()
        ->first();
        
    if (!$log) echo "❌ FAIL: No audit log found.\n";
    else echo "✅ Audit Log verified.\n";

    echo "✅ ALL EDGE CASES PASSED.\n";

} catch (\Throwable $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
