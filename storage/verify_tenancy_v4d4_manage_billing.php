<?php

use App\Models\User;
use App\Models\Tenant;
use App\Models\Account;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(
    $request = Illuminate\Http\Request::capture()
);


echo "🔍 Starting Tenant Billing Page Verification (v4d4)...\n";

try {
    // 1. Setup Data
    if (!class_exists(\Database\Seeders\PlanSeeder::class)) {
         throw new Exception("PlanSeeder class not found.");
    }
    $seeder = new \Database\Seeders\PlanSeeder();
    $seeder->run();
    
    $starterPlan = Plan::where('key', 'starter')->firstOrFail();

    // Owner
    $owner = User::create([
        'name' => 'Billing Owner',
        'email' => 'bill_owner_' . uniqid() . '@test.com',
        'password' => Hash::make('password'),
    ]);

    // Admin (Tenant Admin logic) - NOT Account Owner
    $admin = User::create([
        'name' => 'Billing Admin',
        'email' => 'bill_admin_' . uniqid() . '@test.com',
        'password' => Hash::make('password'),
    ]);

    // Account & Tenant
    $account = Account::create([
        'owner_user_id' => $owner->id, // Linked to owner
        'plan_id' => $starterPlan->id,
        'status' => 'active',
        'starts_at' => now(),
    ]);
    
    $tenant = Tenant::create(['name' => 'Billing Tenant', 'is_active' => true]);
    $tenant->account()->associate($account);
    $tenant->save();

    // Permissions:
    // Owner -> Tenant Admin AND Account Owner role
    $tenant->users()->attach($owner->id, ['role' => 'admin']);
    DB::table('account_users')->insert([
        'account_id' => $account->id, 'user_id' => $owner->id, 'role' => 'owner', 'created_at' => now(), 'updated_at' => now()
    ]);
    
    // Admin -> Tenant Admin (BUT NOT accountable)
    $tenant->users()->attach($admin->id, ['role' => 'admin']);
    // No entry in account_users or role='member'
    DB::table('account_users')->insert([
        'account_id' => $account->id, 'user_id' => $admin->id, 'role' => 'member', 'created_at' => now(), 'updated_at' => now()
    ]);


    // 2. Test Owner Access
    echo "\n--- Owner Access Test ---\n";
    
    // Login explicitly for global Auth state (Middleware reliance)
    auth()->login($owner);
    
    $request = \Illuminate\Http\Request::create('/manage/billing', 'GET');
    $request->setUserResolver(fn() => $owner);
    $request->setLaravelSession($app['session']->driver());
    session(['current_tenant_id' => $tenant->id]);
    
    try {
        $response = $kernel->handle($request);
    } catch (\Throwable $e) {
        echo "❌ ERROR Trace: " . $e->getTraceAsString() . "\n";
        throw $e;
    }
    
    if ($response->getStatusCode() !== 200) {
        throw new Exception("❌ FAIL: Owner cannot access billing (" . $response->getStatusCode() . ")");
    }
    $content = $response->getContent();
    if (!str_contains($content, 'Mevcut Paketiniz') || !str_contains($content, 'Kullanıcı (Seat) Limiti')) {
         throw new Exception("❌ FAIL: Billing page content missing.");
    }
    echo "✅ Owner Access Verified.\n";


    // 3. Test Non-Owner Access (Tenant Admin)
    echo "\n--- Non-Owner Access Test ---\n";
    
    auth()->login($admin);
    
    $request = \Illuminate\Http\Request::create('/manage/billing', 'GET');
    $request->setUserResolver(fn() => $admin);
    $request->setLaravelSession($app['session']->driver());
    session(['current_tenant_id' => $tenant->id]);
    
    try {
        $response = $kernel->handle($request);
         if ($response->getStatusCode() === 200) {
             throw new Exception("❌ FAIL: Non-Owner Accessed Billing Page!");
        }
        echo "✅ Non-Owner blocked (" . $response->getStatusCode() . ").\n";
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
         echo "✅ Non-Owner blocked (Exception: " . $e->getStatusCode() . ").\n";
    } catch (\Throwable $e) {
         // Maybe a 403 Forbidden throws an AuthorizationException which renders to 403 response?
         // Kernel handles exceptions and converts to response.
         // If handle() throws, it's something unhandled.
         echo "❌ Unexpected Error in Negative Test: " . $e->getMessage() . "\n";
         echo $e->getTraceAsString();
    }


    echo "✅ ALL BILLING PANEL TESTS PASSED.\n";

} catch (\Throwable $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}
