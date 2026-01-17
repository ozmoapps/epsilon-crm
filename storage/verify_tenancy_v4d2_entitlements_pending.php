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

echo "🔍 Starting Entitlements Hardening Verification (v4d2)...\n";

try {
    // 1. Setup Data: Plan Seeder
    if (!class_exists(\Database\Seeders\PlanSeeder::class)) {
         throw new Exception("PlanSeeder class not found.");
    }
    $seeder = new \Database\Seeders\PlanSeeder();
    $seeder->run();
    
    $starterPlan = Plan::where('key', 'starter')->firstOrFail(); // 1 Seat Limit
    
    // 2. Setup Test User & Account
    $ownerEmail = 'pending_tester_' . uniqid() . '@test.com';
    $owner = User::create([
        'name' => 'Pending Tester',
        'email' => $ownerEmail,
        'password' => Hash::make('password'),
        'is_admin' => true,
    ]);

    $account = Account::create([
        'owner_user_id' => $owner->id,
        'plan_id' => $starterPlan->id,
        'status' => 'active',
        'starts_at' => now(),
    ]);
    
    // Link owner
    DB::table('account_users')->insert([
        'account_id' => $account->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tenant = Tenant::create(['name' => 'Pending Tenant', 'is_active' => true]);
    $tenant->account()->associate($account);
    $tenant->save();
    $tenant->users()->attach($owner->id, ['role' => 'admin']);

    $entitlements = app(\App\Services\EntitlementsService::class);
    
    // 3. Verify Initial State
    $usage = $entitlements->accountSeatUsage($account);
    echo "Initial Usage: $usage (Expect 1)\n";
    if ($usage !== 1) throw new Exception("Initial usage wrong");

    // 4. Create Pending Invite (Valid)
    // Starter plan limit is 1. We have 1 user.
    // If we add a pending invite, usage should go to 2 (if pending counts).
    // Wait, limit is 1. If pending counts, can we even create it?
    // The previous PR check should block this creation if usage >= limit.
    // So let's upgrade to Professional (4 seats) first to test counting logic.
    
    $proPlan = Plan::where('key', 'professional')->firstOrFail(); // Limit 4
    $account->update(['plan_id' => $proPlan->id]);
    $account->refresh();
    echo "Upgraded to Professional (Limit 4)\n";

    TenantInvitation::create([
        'tenant_id' => $tenant->id,
        'email' => 'invite1@example.com',
        'token_hash' => hash('sha256', Str::random(10)),
        'role' => 'viewer',
        'expires_at' => now()->addDays(7),
    ]);
    
    $usage = $entitlements->accountSeatUsage($account);
    echo "Usage with 1 Pending: $usage (Expect 2)\n";
    if ($usage !== 2) throw new Exception("Pending invite not counted correctly");

    // 5. Expire the Invite (Should NOT count)
    TenantInvitation::where('email', 'invite1@example.com')->update(['expires_at' => now()->subDay()]);
    
    $usage = $entitlements->accountSeatUsage($account);
    echo "Usage with Expired Invite: $usage (Expect 1)\n";
    if ($usage !== 1) throw new Exception("Expired invite still counted!");
    
    echo "✅ Pending Invite Logic (Validity Check) Verified.\n";

    // 6. Join Enforcement Test
    // Downgrade back to Starter (Limit 1)
    // We have 1 active user ($owner). Seat is full.
    $account->update(['plan_id' => $starterPlan->id]);
    $account->refresh();
    
    // Create a valid invitation (injecting directly to bypass create check)
    // Since usage=1 and limit=1, we can't create via UI/Controller, but we can seed it.
    // Wait, if it exists as pending, it counts towards usage (if valid).
    // If usage=2 (1 user + 1 pending) and limit=1, we are OVER limit.
    // If we try to join, it should block.
    
    $token = Str::random(64);
    $encodedToken = $token; 
    $tokenHash = hash('sha256', $token);
    
    $invite2 = TenantInvitation::create([
        'tenant_id' => $tenant->id,
        'email' => 'joiner@example.com',
        'token_hash' => $tokenHash,
        'role' => 'viewer',
        'expires_at' => now()->addDays(7),
    ]);
    
    // Usage is now 2 / 1.
    // Create User attempting to join
    $joiner = User::create([
        'name' => 'Joiner',
        'email' => 'joiner@example.com',
        'password' => Hash::make('password'),
    ]);
    
    echo "Attempting Join with Limit Exceeded (1 User + 1 Pending / Limit 1)...\n";
    
    try {
        // Simulate Controller Accept
        // We need to call the controller method or simulate the logic.
        // Calling controller action directly is best to test the `abort` and `log`.
        
        $controller = new \App\Http\Controllers\TenantInvitationController();
        $request = \Illuminate\Http\Request::create("/invite/$token/accept", 'POST');
        $request->setUserResolver(fn() => $joiner);
        
        // Mock AuditLogger? No, let's use real one to verify DB log.
        $logger = app(\App\Services\AuditLogger::class);
        
        $controller->accept($request, $token, $logger);
        
        throw new Exception("❌ FAIL: Join should have been blocked!");
        
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        if ($e->getStatusCode() === 403) {
            echo "✅ Correctly aborted with 403: " . $e->getMessage() . "\n";
        } else {
             throw $e;
        }
    }
    
    // Verify Audit Log
    $log = AuditLog::where('event_key', 'entitlement.blocked')
        ->whereJsonContains('metadata->reason', 'join_attempt')
        ->latest()
        ->first();
        
    if (!$log) {
        throw new Exception("❌ FAIL: No audit log found for join block.");
    }
    
    echo "✅ Audit Log for Join Block Verified.\n";

    echo "✅ ALL ENTITLEMENT HARDENING TESTS PASSED.\n";

} catch (\Throwable $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}
