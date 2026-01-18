<?php

use App\Models\Tenant;
use App\Models\User;
use App\Models\SalesOrder;
use App\Models\Customer;
use App\Models\Vessel;
use Illuminate\Support\Facades\Auth;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "\n=============================================\n";
echo "   VERIFY SALES ORDER PRINT RENDER (PR6)\n";
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
        if ($status >= 500) {
             echo "RESPONSE BODY: " . substr($response->getContent(), 0, 500) . "\n";
        }
        exit(1);
    }
}

// 1. Setup
echo "\n[SETUP] Creating Tenants and Users...\n";

function createTenantStack($prefix) {
    Tenant::whereNull('account_id')->delete(); // Cleanup dirty tenants
    $user = User::factory()->create(['email' => $prefix . '_so_user_' . uniqid() . '@test.com']);
    
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
    $vessel = Vessel::factory()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id]);
    
    return [$tenant, $user, $customer, $vessel];
}

[$tenantA, $userA, $customerA, $vesselA] = createTenantStack('tenantA');
[$tenantB, $userB, $customerB, $vesselB] = createTenantStack('tenantB');

$soA = SalesOrder::create([
    'tenant_id' => $tenantA->id,
    'customer_id' => $customerA->id,
    'vessel_id' => $vesselA->id,
    'order_no' => 'SO-A-001',
    'title' => 'Test Order A',
    'status' => 'confirmed',
    'currency' => 'USD',
    'created_by' => $userA->id,
    'order_date' => now(),
]);

// Add items to SO
$soA->items()->create([
    'description' => 'Item 1',
    'qty' => 2,
    'unit' => 'pcs',
    'unit_price' => 100,
    'item_type' => 'service',
    'sort_order' => 1
]);

$soB = SalesOrder::create([
    'tenant_id' => $tenantB->id,
    'customer_id' => $customerB->id,
    'vessel_id' => $vesselB->id,
    'order_no' => 'SO-B-001',
    'title' => 'Test Order B',
    'status' => 'confirmed',
    'currency' => 'USD',
    'created_by' => $userB->id,
    'order_date' => now(),
]);

// 2. Test Tenant A Access (Own Order)
echo "\n[TEST] Tenant A Access (Own Order)...\n";
Auth::login($userA);
app(\App\Services\TenantContext::class)->setTenant($tenantA);

$reqPrint = Illuminate\Http\Request::create("/sales-orders/{$soA->id}/print", 'GET');
try {
    $respPrint = $kernel->handle($reqPrint);
    assertStatus('User A GET /sales-orders/A/print', $respPrint, 200);
} catch (\Throwable $e) {
    echo "[FAIL] Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

$reqPdf = Illuminate\Http\Request::create("/sales-orders/{$soA->id}/pdf", 'GET');
$respPdf = $kernel->handle($reqPdf);
assertStatus('User A GET /sales-orders/A/pdf', $respPdf, 200);

// 3. Test Cross Tenant Access
echo "\n[TEST] Cross Tenant Access (User A -> Order B)...\n";
$reqCross = Illuminate\Http\Request::create("/sales-orders/{$soB->id}/print", 'GET');
$respCross = $kernel->handle($reqCross);

if (in_array($respCross->getStatusCode(), [404, 403])) {
    echo "[PASS] Cross Tenant Access Denied ({$respCross->getStatusCode()})\n";
} else {
    echo "[FAIL] Cross Tenant Access Allowed? Status: " . $respCross->getStatusCode() . "\n";
    exit(1);
}

echo "\n[SUCCESS] Verify Sales Order Render Script Passed.\n";
