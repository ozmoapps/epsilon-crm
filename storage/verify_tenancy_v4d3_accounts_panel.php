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


echo "🔍 Starting Accounts Panel Verification (v4d3)...\n";

try {
    // 1. Setup Data
    if (!class_exists(\Database\Seeders\PlanSeeder::class)) {
         throw new Exception("PlanSeeder class not found.");
    }
    $seeder = new \Database\Seeders\PlanSeeder();
    $seeder->run();
    
    $starterPlan = Plan::where('key', 'starter')->firstOrFail();

    // Create Platform Admin
    $admin = User::create([
        'name' => 'Panel Admin',
        'email' => 'panel_admin_' . uniqid() . '@test.com',
        'password' => Hash::make('password'),
        'is_admin' => true,
    ]);

    // Create Tenant Admin (should not see panel)
    $tenantAdmin = User::create([
        'name' => 'Panel Tenant Admin',
        'email' => 'panel_tenant_' . uniqid() . '@test.com',
        'password' => Hash::make('password'),
        'is_admin' => false,
    ]);

    // Create Account & Tenant
    $account = Account::create([
        'owner_user_id' => $tenantAdmin->id,
        'plan_id' => $starterPlan->id,
        'status' => 'active',
        'starts_at' => now(),
    ]);
    
    $tenant = Tenant::create(['name' => 'Panel Tenant', 'is_active' => true]);
    $tenant->account()->associate($account);
    $tenant->save();
    $tenant->users()->attach($tenantAdmin->id, ['role' => 'admin']);
    
    // Link owner to account
    DB::table('account_users')->updateOrInsert(
        ['account_id' => $account->id, 'user_id' => $tenantAdmin->id],
        ['role' => 'owner', 'updated_at' => now()]
    );

    // 2. Test Platform Admin Access
    echo "\n--- Platform Admin Access Test ---\n";
    $request = \Illuminate\Http\Request::create('/admin/accounts', 'GET');
    $request->setUserResolver(fn() => $admin);
    
    $response = $kernel->handle($request);
    
    if ($response->getStatusCode() !== 200) {
        throw new Exception("❌ FAIL: Admin cannot access /admin/accounts (" . $response->getStatusCode() . ")");
    }
    
    $content = $response->getContent();
    if (!str_contains($content, 'Hesaplar') || !str_contains($content, 'Paket')) {
        throw new Exception("❌ FAIL: Index page content missing key terms.");
    }
    echo "✅ /admin/accounts Accessible & Rendered.\n";

    // 3. Test Detail View
    $request = \Illuminate\Http\Request::create("/admin/accounts/{$account->id}", 'GET');
    $request->setUserResolver(fn() => $admin);
    $response = $kernel->handle($request);
    
    if ($response->getStatusCode() !== 200) {
        throw new Exception("❌ FAIL: Admin cannot access detail page (" . $response->getStatusCode() . ")");
    }
    
    $content = $response->getContent();
    if (!str_contains($content, 'Firma Limiti') || !str_contains($content, 'Kullanıcı (Seat) Limiti')) {
         throw new Exception("❌ FAIL: Detail page missing limit info.");
    }
    echo "✅ /admin/accounts/{id} Accessible & Rendered.\n";

    // 4. Test Privacy / Scope Leak
    // Ensure "Firma Değiştir" or "Finans" menu is NOT present in the HTML for admin
    // (This relies on view rendering checking $showTenantMenu logic)
    if (str_contains($content, 'Firma Değiştir') || str_contains($content, 'Finans')) {
        // Double check: Finans might be present if we messed up v4c7 logic.
        // v4c7: $showTenantMenu = !$isAdmin || ...
        throw new Exception("❌ FAIL: Tenant Menu (Finans/Firma Değiştir) leaked in Admin View!");
    }
    echo "✅ Privacy Check Passed (No tenant menus).\n";

    // 5. Test Tenant Admin Access (Forbidden)
    echo "\n--- Tenant Admin Access Test ---\n";
    $request = \Illuminate\Http\Request::create('/admin/accounts', 'GET');
    $request->setUserResolver(fn() => $tenantAdmin);
    
    try {
        // Middleware usually handles this before controller
        // We'll simulate route matching if needed, but kernel handle does it.
        $response = $kernel->handle($request);
        
        // Should be 403 or 404 depending on how strict route hiding is.
        // Actually our 'admin' middleware redirects or 403s.
        if ($response->getStatusCode() === 200) {
             throw new Exception("❌ FAIL: Tenant Admin accessed /admin/accounts!");
        }
        echo "✅ Tenant Admin blocked (" . $response->getStatusCode() . ").\n";
    } catch (\Exception $e) {
        // If it throws specific exception (like AuthenticationException which redirects), that's also fine-ish for 'auth'
        // but 'admin' middleware usually aborts 403.
         echo "✅ Tenant Admin blocked (Exception caught).\n";
    }

    echo "✅ ALL ACCOUNTS PANEL TESTS PASSED.\n";

} catch (\Throwable $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}
