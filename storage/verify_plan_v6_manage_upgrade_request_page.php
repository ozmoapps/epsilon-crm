<?php

use App\Models\Tenant;
use App\Models\User;
use App\Models\Account;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;
use App\Services\TenantContext;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "[INFO] Verifying Manage Upgrade Request Page...\n";

// 1. Setup Data (Isolated)
$suffix = uniqid();
$plan = Plan::firstOrCreate(['key' => 'starter'], ['name' => 'Starter', 'price' => 0, 'currency' => 'TRY']);
$entPlan = Plan::firstOrCreate(['key' => 'enterprise'], ['name' => 'Enterprise', 'price' => 100, 'currency' => 'TRY']);

// Create Account
$user = User::create([
    'name' => 'Upgrade Tester ' . $suffix,
    'email' => 'upgrade-' . $suffix . '@test.com',
    'password' => bcrypt('password')
]);

$account = Account::create([
    'name' => 'Upgrade Account ' . $suffix,
    'owner_user_id' => $user->id,
    'plan_key' => 'starter',
    'plan_id' => $plan->id,
    'status' => 'active'
]);
$account->users()->attach($user->id, ['role' => 'owner']);

// Create Tenant
$tenant = Tenant::create([
    'name' => 'Upgrade Tenant ' . $suffix,
    'domain' => 'upgrade-' . $suffix . '.test',
    'is_active' => true,
    'account_id' => $account->id
]);
$tenant->users()->attach($user->id, ['role' => 'admin']);

// 2. Simulate Request
echo "[INFO] Testing GET /manage/plan/upgrade-request...\n";

try {
    $request = \Illuminate\Http\Request::create('/manage/plan/upgrade-request', 'GET');
    $request->setLaravelSession(session()->driver());
    $request->session()->start();
    
    // Set Context
    app(TenantContext::class)->setTenant($tenant);
    
    // Build Session for Context (if middleware relies on it)
    $request->session()->put('current_tenant_id', $tenant->id);
    
    auth()->login($user);

    $response = app()->handle($request);
    
    if ($response->getStatusCode() === 200) {
        $content = $response->getContent();
        // Check for specific plan options based on 'starter' current plan
        if (str_contains($content, 'value="team"') && str_contains($content, 'value="enterprise"')) {
             echo "[PASS] Upgrade Request Page Loaded (200 OK) with Correct Options (Team, Enterprise)\n";
        } else {
             echo "[FAIL] Dropdown options missing! Content snippet:\n" . substr($content, 0, 500) . "\n...\n";
             exit(1);
        }
    } else {
        echo "[FAIL] Status Code: " . $response->getStatusCode() . "\n";
        if ($response->getStatusCode() === 500) {
             echo "Error Content: " . substr($response->getContent(), 0, 1000) . "\n";
        }
        exit(1);
    }

} catch (\Exception $e) {
    echo "[FAIL] Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}

// 3. Test Requests List Page (Regression Check)
echo "[INFO] Testing GET /manage/plan/requests...\n";
try {
    $reqIndex = \Illuminate\Http\Request::create('/manage/plan/requests', 'GET');
    $reqIndex->setLaravelSession(session()->driver());
    $reqIndex->session()->start();
    $reqIndex->session()->put('current_tenant_id', $tenant->id);
    auth()->login($user);

    $resIndex = app()->handle($reqIndex);

    if ($resIndex->getStatusCode() === 200) {
        echo "[PASS] Requests List Page Loaded (200 OK)\n";
    } else {
        echo "[FAIL] Requests List Status: " . $resIndex->getStatusCode() . "\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "[FAIL] Requests List Exception: " . $e->getMessage() . "\n";
    exit(1);
}

// Cleanup
$tenant->delete();
// Account/User cleanup optional for sqlite/local
echo "[SUCCESS] Hotfix Verified.\n";
exit(0);
