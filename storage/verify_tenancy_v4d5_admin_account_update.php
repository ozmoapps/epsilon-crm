<?php

use App\Models\User;
use App\Models\Tenant;
use App\Models\Account;
use App\Models\Plan;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(
    $request = Illuminate\Http\Request::capture()
);


echo "🔍 Starting Admin Account Update Verification (v4d5)...\n";

try {
    // 1. Setup Data
    if (!class_exists(\Database\Seeders\PlanSeeder::class)) {
         throw new Exception("PlanSeeder class not found.");
    }
    $seeder = new \Database\Seeders\PlanSeeder();
    $seeder->run();
    
    $starterPlan = Plan::where('key', 'starter')->firstOrFail(); // 1/1
    $proPlan = Plan::where('key', 'professional')->firstOrFail(); // 5/5

    // Platform Admin
    $admin = User::create([
        'name' => 'Update Admin',
        'email' => 'update_admin_' . uniqid() . '@test.com',
        'password' => Hash::make('password'),
        'is_admin' => true,
    ]);

    // Account (Starter)
    $account = Account::create([
        'owner_user_id' => $admin->id, 
        'plan_id' => $starterPlan->id,
        'status' => 'active',
        'starts_at' => now(),
    ]);

    echo "✅ Setup Complete. Account ID: {$account->id} (Starter).\n";

    // 2. Test Success Upgrade
    echo "\n--- Test 1: Upgrade to Professional ---\n";
    
    auth()->login($admin);
    
    // CSRF Prep
    $session = $app['session']->driver();
    $session->start();
    $token = $session->token();
    
    $request = \Illuminate\Http\Request::create("/admin/accounts/{$account->id}", 'PATCH', [
        'plan_id' => $proPlan->id,
        'extra_seats_purchased' => 0,
        '_token' => $token,
    ]);
    $request->setUserResolver(fn() => $admin);
    $request->setLaravelSession($session);
    
    try {
        $response = $kernel->handle($request);
    } catch (\Throwable $e) {

        echo "❌ ERROR Trace: " . $e->getTraceAsString() . "\n";
        throw $e;
    }
    
    if ($response->getStatusCode() !== 302) {

        throw new Exception("❌ FAIL: Update request failed (" . $response->getStatusCode() . ")");
    }
    
    $account->refresh();
    if ($account->plan_id !== $proPlan->id) {
         throw new Exception("❌ FAIL: Plan ID not updated in DB.");
    }
    
    // Check Audit
    $log = AuditLog::where('event_key', 'account.plan.changed')->where('account_id', $account->id)->latest()->first();
    if (!$log) {
        throw new Exception("❌ FAIL: Audit log missing for plan change.");
    }
    echo "✅ Upgrade Verified (Plan: Professional, Audit Logged).\n";


    // 3. Test Blocked Downgrade (Force Usage)
    echo "\n--- Test 2: Block Downgrade ---\n";
    
    // Manually create Usage > Starter Limit (1)
    // Professional allows 5 seats. Let's create 2 seats.
    $tenant = Tenant::create(['name' => ' Downgrade Test', 'is_active' => true]);
    $tenant->account()->associate($account);
    $tenant->save();
    
    // User 1
    DB::table('tenant_user')->insert(['tenant_id' => $tenant->id, 'user_id' => $admin->id, 'role' => 'admin']);
    // User 2
    $user2 = User::factory()->create();
    DB::table('tenant_user')->insert(['tenant_id' => $tenant->id, 'user_id' => $user2->id, 'role' => 'member']);
    
    // Now Usage = 2. Starter Limit = 1.
    // Try to downgrade to Starter.
    
    $request = \Illuminate\Http\Request::create("/admin/accounts/{$account->id}", 'PATCH', [
        'plan_id' => $starterPlan->id,
        'extra_seats_purchased' => 0,
        '_token' => $token,
    ]);
    $request->setUserResolver(fn() => $admin);
    $request->setLaravelSession($session); // For flash messages
    
    $response = $kernel->handle($request);

    
    // Should stay Professional
    $account->refresh();
    if ($account->plan_id === $starterPlan->id) {
         throw new Exception("❌ FAIL: System allowed downgrade despite usage > limit!");
    }
    
    // Check Audit
    $blockLog = AuditLog::where('event_key', 'entitlement.blocked')
        ->where('account_id', $account->id)
        ->where('metadata->reason', 'seat_limit_exceeded')
        ->latest()
        ->first();
    
    if (!$blockLog) {
         throw new Exception("❌ FAIL: Blockage audit log missing.");
    }
    echo "✅ Downgrade Block Verified (Audit Logged).\n";


    // 4. Test Extra Seat Purchase (Allow Downgrade with Extra)
    echo "\n--- Test 3: Downgrade WITH Extra Seats ---\n";
    // Usage 2. Starter (1) + Extra (1) = 2. Should Pass.
    
    $request = \Illuminate\Http\Request::create("/admin/accounts/{$account->id}", 'PATCH', [
        'plan_id' => $starterPlan->id,
        'extra_seats_purchased' => 1,
        '_token' => $token,
    ]);
    $request->setUserResolver(fn() => $admin);
    $request->setLaravelSession($session);
    
    $response = $kernel->handle($request);
    
    $account->refresh();
    if ($account->plan_id !== $starterPlan->id || $account->extra_seats_purchased !== 1) {
         throw new Exception("❌ FAIL: Downgrade with extra seats failed.");
    }
    echo "✅ Downgrade + Extra Seat Verified.\n";

    echo "✅ ALL ADMIN ACCOUNT UPDATE TESTS PASSED.\n";

} catch (\Throwable $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}
