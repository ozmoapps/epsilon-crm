<?php

use App\Models\User;
use App\Models\Tenant;
use App\Models\Payment;
use App\Models\Contract; // If exists
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\BankAccount;
use App\Models\Customer;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// --- Helper Functions ---
$errors = [];

function assertIsNull($value, $description) {
    global $errors;
    if (!is_null($value)) {
        $errors[] = "[FAIL] $description: Expected NULL, got " . (is_object($value) ? get_class($value) : $value);
    } else {
        echo "[PASS] $description" . PHP_EOL;
    }
}

function assertNotNull($value, $description) {
    global $errors;
    if (is_null($value)) {
        $errors[] = "[FAIL] $description: Expected NOT NULL, got NULL";
    } else {
        echo "[PASS] $description" . PHP_EOL;
    }
}

function assertException($callback, $exceptionClass, $description) {
    global $errors;
    try {
        $callback();
        $errors[] = "[FAIL] $description: Expected exception $exceptionClass was NOT thrown.";
    } catch (\Exception $e) {
        if (get_class($e) === $exceptionClass || is_subclass_of($e, $exceptionClass)) {
            echo "[PASS] $description" . PHP_EOL;
        } else {
            $errors[] = "[FAIL] $description: Expected exception $exceptionClass, got " . get_class($e);
        }
    }
}

echo "--- Verify PR5c: Wave-2 Model Tenant Scope ---\n";

// 1. Setup Data
echo "--- Setup ---\n";
// Create Tenants
$tenantA = Tenant::firstOrCreate(['domain' => 'wave2-a.test'], ['name' => 'Wave2 Tenant A', 'is_active' => true]);
$tenantB = Tenant::firstOrCreate(['domain' => 'wave2-b.test'], ['name' => 'Wave2 Tenant B', 'is_active' => true]);

// Cleanup old data
Payment::withoutGlobalScopes()->where('reference_number', 'WAVE2-REF-A')->forceDelete();
Product::withoutGlobalScopes()->where('sku', 'WAVE2-SKU-A')->forceDelete();
Warehouse::withoutGlobalScopes()->where('name', 'Wave2 Warehouse A')->forceDelete();
BankAccount::withoutGlobalScopes()->where('name', 'Wave2 Bank A')->forceDelete();
if (class_exists(Contract::class)) {
    Contract::withoutGlobalScopes()->where('customer_name', 'Wave2 Contract A')->forceDelete();
}

// 2. Create Data in Context A
echo "--- Context A: Creation ---\n";
app(\App\Services\TenantContext::class)->setTenant($tenantA);

$user = User::first() ?? User::factory()->create();
$customerA = Customer::firstOrCreate(['name' => 'Wave2 Customer A', 'tenant_id' => $tenantA->id]);

// Create dependent records
$bankAccountA = BankAccount::create([
    'name' => 'Wave2 Bank A',
    'type' => 'bank',
    'currency_id' => 1, // Assumed
]);
assertNotNull($bankAccountA->id, "Created BankAccount A");

$paymentA = Payment::create([
    'amount' => 100.00,
    'original_amount' => 100.00,
    'original_currency' => 'EUR',
    'payment_date' => now(),
    'reference_number' => 'WAVE2-REF-A',
    'created_by' => $user->id,
    'tenant_id' => null, // Should auto-set
    'bank_account_id' => $bankAccountA->id,
    'customer_id' => $customerA->id
]);
assertNotNull($paymentA->id, "Created Payment A");
if ($paymentA->tenant_id != $tenantA->id) {
    $errors[] = "[FAIL] Payment tenant_id auto-set failed. Expected {$tenantA->id}, got {$paymentA->tenant_id}";
}

$warehouseA = Warehouse::create([
    'name' => 'Wave2 Warehouse A',
    'is_active' => true
]);
assertNotNull($warehouseA->id, "Created Warehouse A");

$productA = Product::create([
    'name' => 'Wave2 Product A',
    'sku' => 'WAVE2-SKU-A',
    'type' => 'service',
    'track_stock' => false
]);
assertNotNull($productA->id, "Created Product A");

$contractA = null;
if (class_exists(Contract::class)) {
    // Create Vessel
    $vesselA = \App\Models\Vessel::create([
        'customer_id' => $customerA->id,
        'name' => 'Wave2 Vessel',
        'created_by' => $user->id
    ]);

    // Create SalesOrder for Contract dependency
    $salesOrderA = \App\Models\SalesOrder::create([
        'customer_id' => $customerA->id,
        'vessel_id' => $vesselA->id,
        'title' => 'Wave2 SO',
        'created_by' => $user->id,
        'status' => 'draft'
    ]);

    $contractA = Contract::create([
        'sales_order_id' => $salesOrderA->id,
        'customer_name' => 'Wave2 Contract A',
        'status' => 'draft',
        'created_by' => $user->id
    ]);
    assertNotNull($contractA->id, "Created Contract A");
}

// 3. Switch to Context B
echo "--- Context B: Leak Tests ---\n";
app(\App\Services\TenantContext::class)->setTenant($tenantB);

// A. Test BankAccount Leak
$leakedBank = BankAccount::find($bankAccountA->id);
assertIsNull($leakedBank, "Tenant B CANNOT find Tenant A BankAccount");

// B. Test Payment Leak
$leakedPayment = Payment::find($paymentA->id);
assertIsNull($leakedPayment, "Tenant B CANNOT find Tenant A Payment");

// C. Test Warehouse Leak
$leakedWarehouse = Warehouse::find($warehouseA->id);
assertIsNull($leakedWarehouse, "Tenant B CANNOT find Tenant A Warehouse");

// D. Test Product Leak
$leakedProduct = Product::find($productA->id);
assertIsNull($leakedProduct, "Tenant B CANNOT find Tenant A Product");

// E. Test Contract Leak
if ($contractA) {
    $leakedContract = Contract::find($contractA->id);
    assertIsNull($leakedContract, "Tenant B CANNOT find Tenant A Contract");
}

// F. Test findOrFail Exception (Payment)
assertException(function() use ($paymentA) {
    Payment::findOrFail($paymentA->id);
}, \Illuminate\Database\Eloquent\ModelNotFoundException::class, "Tenant B throws 404 for Tenant A Payment via findOrFail()");


// 4. Test Bypass Logic
echo "--- Context B: Bypass Tests ---\n";
// Attempt to find A's payment using bypass scope
$bypassedPayment = Payment::withoutTenantScope()->find($paymentA->id);
assertNotNull($bypassedPayment, "Tenant B CAN find Tenant A Payment via withoutTenantScope()");

if ($bypassedPayment && $bypassedPayment->id === $paymentA->id) {
    echo "[PASS] Bypass returned correct record.\n";
} else {
    $errors[] = "[FAIL] Bypass returned incorrect record or null.\n";
}

// --- Summary ---
if (count($errors) > 0) {
    echo "\n!!! FAILURES DETECTED !!!\n";
    foreach ($errors as $error) {
        echo "$error\n";
    }
    echo "VERIFY RESULT: FAIL\n";
    exit(1);
} else {
    echo "\nVERIFY RESULT: PASS\n";
    exit(0);
}
