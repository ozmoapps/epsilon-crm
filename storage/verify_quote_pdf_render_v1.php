<?php

use App\Models\Tenant;
use App\Models\User;
use App\Models\Quote;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "\n=============================================\n";
echo "   VERIFY QUOTE PDF/PRINT RENDER (PR5)\n";
echo "=============================================\n";

// GUARD: Safety First
if (!app()->environment(['local', 'testing'])) {
    echo "[FAIL] This script can only run in local or testing environments.\n";
    exit(1);
}
if (config('database.default') !== 'sqlite') {
    echo "[FAIL] This script requires SQLite database connection for safety.\n";
    exit(1);
}

function assertStatus($testName, $response, $expectedStatus, $message = '')
{
    $status = $response->getStatusCode();
    if ($status === $expectedStatus) {
        echo "[PASS] {$testName} -> got {$status}\n";
    } else {
        echo "[FAIL] {$testName} -> expected {$expectedStatus}, got {$status}. {$message}\n";
        exit(1);
    }
}

// 1. Setup
echo "\n[SETUP] Creating Tenants and Users...\n";

function createTenantStack($prefix) {
    Tenant::whereNull('account_id')->delete(); // Cleanup dirty tenants
    $user = User::factory()->create(['email' => $prefix . '_user_' . uniqid() . '@test.com']);
    
    // Create Account
    $plan = \App\Models\Plan::where('key', 'starter')->first();
    $account = \App\Models\Account::create([
        'owner_user_id' => $user->id,
        'plan_key' => 'starter',
        'plan_id' => $plan?->id,
        'status' => 'active',
        'name' => $prefix . ' Account',
    ]);
    
    $tenant = Tenant::create([
        'account_id' => $account->id,
        'name' => $prefix . ' Tenant',
        'domain' => $prefix . '-' . uniqid() . '.test',
        'is_active' => true
    ]);
    
    $tenant->users()->attach($user, ['role' => 'user']);
    
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
    $vessel = \App\Models\Vessel::factory()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id]);
    
    return [$tenant, $user, $customer, $vessel];
}

[$tenantA, $userA, $customerA, $vesselA] = createTenantStack('tenantA');
[$tenantB, $userB, $customerB, $vesselB] = createTenantStack('tenantB');

$quoteA = Quote::create([
    'tenant_id' => $tenantA->id,
    'customer_id' => $customerA->id,
    'vessel_id' => $vesselA->id,
    'title' => 'Test Quote A',
    'status' => 'draft',
    'quote_no' => 'Q-A-001',
    'currency' => 'USD',
    'created_by' => $userA->id,
]);

$quoteB = Quote::create([
    'tenant_id' => $tenantB->id,
    'customer_id' => $customerB->id,
    'vessel_id' => $vesselB->id,
    'title' => 'Test Quote B',
    'status' => 'draft',
    'quote_no' => 'Q-B-001',
    'currency' => 'USD',
    'created_by' => $userB->id,
]);

// 2. Test Tenant A Access (Own Quote)
echo "\n[TEST] Tenant A Access (Own Quote)...\n";
Auth::login($userA);
app(\App\Services\TenantContext::class)->setTenant($tenantA);

$reqPrint = Illuminate\Http\Request::create("/quotes/{$quoteA->id}/print", 'GET');
$respPrint = $kernel->handle($reqPrint);
assertStatus('User A GET /quotes/A/print', $respPrint, 200);

// Preview
$reqPreview = Illuminate\Http\Request::create("/quotes/{$quoteA->id}/preview", 'GET');
$respPreview = $kernel->handle($reqPreview);
assertStatus('User A GET /quotes/A/preview', $respPreview, 200);


// 3. Test Cross Tenant Access
echo "\n[TEST] Cross Tenant Access (User A -> Quote B)...\n";
$reqCross = Illuminate\Http\Request::create("/quotes/{$quoteB->id}/print", 'GET');
$respCross = $kernel->handle($reqCross);

if (in_array($respCross->getStatusCode(), [404, 403])) {
    echo "[PASS] Cross Tenant Access Denied ({$respCross->getStatusCode()})\n";
} else {
    echo "[FAIL] Cross Tenant Access Allowed? Status: " . $respCross->getStatusCode() . "\n";
    exit(1);
}

echo "\n[SUCCESS] Verify Quote Render Script Passed.\n";
