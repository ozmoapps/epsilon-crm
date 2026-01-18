<?php

use App\Models\User;
use App\Models\Tenant;
use App\Models\Customer;
use App\Models\Vessel;
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

echo "--- Verify PR5b: Model Level Tenant Scope ---\n";

// 1. Setup Data
echo "--- Setup ---\n";
// Create Tenants
$tenantA = Tenant::firstOrCreate(['domain' => 'scope-a.test'], ['name' => 'Scope Tenant A', 'is_active' => true]);
$tenantB = Tenant::firstOrCreate(['domain' => 'scope-b.test'], ['name' => 'Scope Tenant B', 'is_active' => true]);

// Cleanup old data
Customer::withoutGlobalScopes()->where('name', 'Scope Customer A')->forceDelete();
Customer::withoutGlobalScopes()->where('name', 'Scope Customer B')->forceDelete();
Vessel::withoutGlobalScopes()->where('name', 'Scope Vessel A')->forceDelete();

// 2. Create Data in Context A
echo "--- Context A: Creation ---\n";
app(\App\Services\TenantContext::class)->setTenant($tenantA);

$customerA = Customer::create(['name' => 'Scope Customer A', 'created_by' => 1]); // Auto-set tenant_id expected
$vesselA = Vessel::create(['name' => 'Scope Vessel A', 'customer_id' => $customerA->id, 'created_by' => 1]); // Auto-set tenant_id expected

echo "[INFO] Created Customer A (ID: {$customerA->id}, Tenant: {$customerA->tenant_id})\n";

if ($customerA->tenant_id != $tenantA->id) {
    $errors[] = "[FAIL] Context A: Customer tenant_id auto-set failed. Expected {$tenantA->id}, got {$customerA->tenant_id}";
} else {
    echo "[PASS] Context A: Customer tenant_id auto-set correctly.\n";
}

// 3. Switch to Context B
echo "--- Context B: Leak Tests ---\n";
app(\App\Services\TenantContext::class)->setTenant($tenantB);

$customerB = Customer::create(['name' => 'Scope Customer B', 'created_by' => 1]);
echo "[INFO] Created Customer B (ID: {$customerB->id}, Tenant: {$customerB->tenant_id})\n";

// A. Test find() Leak
$leakedCustomer = Customer::find($customerA->id);
assertIsNull($leakedCustomer, "Tenant B CANNOT find Tenant A Customer via find()");

// B. Test whereKey() Leak
$leakedCustomerKey = Customer::whereKey($customerA->id)->first();
assertIsNull($leakedCustomerKey, "Tenant B CANNOT find Tenant A Customer via whereKey()->first()");

// C. Test Vessel Leak
$leakedVessel = Vessel::find($vesselA->id);
assertIsNull($leakedVessel, "Tenant B CANNOT find Tenant A Vessel via find()");

// D. Test findOrFail Exception
assertException(function() use ($customerA) {
    Customer::findOrFail($customerA->id);
}, \Illuminate\Database\Eloquent\ModelNotFoundException::class, "Tenant B throws 404 for Tenant A Customer via findOrFail()");

// 4. Test Bypass Logic
echo "--- Context B: Bypass Tests ---\n";
// Attempt to find A's customer using bypass scope
$bypassedCustomer = Customer::withoutTenantScope()->find($customerA->id);
assertNotNull($bypassedCustomer, "Tenant B CAN find Tenant A Customer via withoutTenantScope()");

if ($bypassedCustomer && $bypassedCustomer->id === $customerA->id) {
    echo "[PASS] Bypass returned correct record.\n";
} else {
    $errors[] = "[FAIL] Bypass returned incorrect record or null.\n";
}

// 5. Test Null Context (Platform Admin / Neutral)
echo "--- Null Context Tests ---\n";
app(\App\Services\TenantContext::class)->setTenant(null);

// Should find everything if no context is set (Standard Laravel behavior without explicit scope filtering)
// Note: Our Global Scope implementation checks `if ($tenantId)`. If null, it shouldn't apply query scope.
$allCustomers = Customer::whereIn('id', [$customerA->id, $customerB->id])->get();
if ($allCustomers->count() === 2) {
    echo "[PASS] Null Context: Found both customers (Scope skipped).\n";
} else {
    $errors[] = "[FAIL] Null Context: Expected 2 customers, got {$allCustomers->count()}. Scope might be incorrectly active.";
}

// --- Summary ---
if (count($errors) > 0) {
    echo "\n!!! FAILURES DETECTED !!!\n";
    foreach ($errors as $error) {
        echo "$error\n";
    }
    exit(1);
} else {
    echo "\nALL TEST PASSED.\n";
    exit(0);
}
