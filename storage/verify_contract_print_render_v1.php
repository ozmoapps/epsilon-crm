<?php

use App\Models\Tenant;
use App\Models\User;
use App\Models\SalesOrder;
use App\Models\Contract;
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
echo "   VERIFY CONTRACT PRINT RENDER (PR7)\n";
echo "=============================================\n";

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
    // Tenant::whereNull('account_id')->delete(); // REMOVED for non-destructive verification
    $user = User::factory()->create(['email' => $prefix . '_con_user_' . uniqid() . '@test.com']);
    
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

// Mock Data Creation
function createContract($tenant, $user, $customer, $vessel) {
    // Need Sales Order first
    $so = SalesOrder::create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'vessel_id' => $vessel->id,
        'order_no' => 'SO-' . uniqid(),
        'title' => 'Test SO',
        'status' => 'confirmed',
        'currency' => 'EUR',
        'created_by' => $user->id,
        'order_date' => now(),
    ]);

    // Add items for table rendering
    $so->items()->create([
        'description' => 'Test Service',
        'qty' => 1,
        'unit' => 'ls',
        'unit_price' => 500,
        'item_type' => 'service',
        'sort_order' => 1
    ]);

    // Create Contract
    return Contract::create([
        'tenant_id' => $tenant->id,
        'sales_order_id' => $so->id,
        'contract_no' => 'CNT-' . uniqid(),
        'customer_name' => $customer->name,
        'issued_at' => now(),
        'locale' => 'tr',
        'currency' => 'EUR',
        'status' => 'draft',
        'created_by' => $user->id,
        'rendered_body' => '<p>This is a test contract body.</p>',
        'subtotal' => 500,
        'grand_total' => 590, // assuming vat
        'tax_total' => 90,
    ]);
}

$contractA = createContract($tenantA, $userA, $customerA, $vesselA);
$contractB = createContract($tenantB, $userB, $customerB, $vesselB);

// 2. Test Tenant A Access (Own Contract)
echo "\n[TEST] Tenant A Access (Own Contract)...\n";
Auth::login($userA);
app(\App\Services\TenantContext::class)->setTenant($tenantA);

$reqPrint = Illuminate\Http\Request::create("/contracts/{$contractA->id}/print", 'GET');
try {
    $respPrint = $kernel->handle($reqPrint);
    assertStatus('User A GET /contracts/A/print', $respPrint, 200);
} catch (\Throwable $e) {
    echo "[FAIL] Print Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}

$reqPdf = Illuminate\Http\Request::create("/contracts/{$contractA->id}/pdf", 'GET');
try {
    $respPdf = $kernel->handle($reqPdf);
    assertStatus('User A GET /contracts/A/pdf', $respPdf, 200);
} catch (\Throwable $e) {
    echo "[FAIL] PDF Exception: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Test Cross Tenant Access
echo "\n[TEST] Cross Tenant Access (User A -> Contract B)...\n";
$reqCross = Illuminate\Http\Request::create("/contracts/{$contractB->id}/print", 'GET');
$respCross = $kernel->handle($reqCross);

if (in_array($respCross->getStatusCode(), [404, 403])) {
    echo "[PASS] Cross Tenant Access Denied ({$respCross->getStatusCode()})\n";
} else {
    echo "[FAIL] Cross Tenant Access Allowed? Status: " . $respCross->getStatusCode() . "\n";
    exit(1);
}

echo "\n[SUCCESS] Verify Contract Render Script Passed.\n";
