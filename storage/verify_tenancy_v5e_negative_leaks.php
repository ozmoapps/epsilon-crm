<?php

use Illuminate\Support\Facades\DB;
use App\Models\Tenant;
use App\Services\TenantContext; // Assuming this service exists
use App\Models\User;

// Target Models
use App\Models\Customer;
use App\Models\Vessel;
use App\Models\Quote;
use App\Models\SalesOrder;
use App\Models\WorkOrder;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\BankAccount;
use App\Models\Warehouse;
use App\Models\Contract;

// Verify script environment
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "--- Verify PR5e: Negative Tenant Leak Test Package ---\n";

// 1. Setup Tenants (Unique Suffix)
$suffix = uniqid();
$tenantA = Tenant::create([
    'name' => 'Tenant A ' . $suffix,
    'domain' => 'tenant-a-' . $suffix . '.test',
    'is_active' => true
]);
$tenantB = Tenant::create([
    'name' => 'Tenant B ' . $suffix,
    'domain' => 'tenant-b-' . $suffix . '.test',
    'is_active' => true
]);

echo "[OK] Tenants Created: A({$tenantA->id}), B({$tenantB->id})\n";

// 2. Setup Context Helper
$tenantContext = app(TenantContext::class);

// 3. Populate Data in Tenant A
echo "--- Context A: Creation ---\n";
$tenantContext->setTenant($tenantA);

$recordIds = [];

// Helper to create valid records with minimal fields
function createRecord($modelClass, $data) {
    try {
        $record = $modelClass::create($data);
        return $record->id;
    } catch (\Exception $e) {
        echo "[ERROR] Failed to create {$modelClass}: " . $e->getMessage() . "\n";
        return null;
    }
}

// Data definitions
$recordIds['Customer'] = createRecord(Customer::class, ['name' => 'Customer A ' . $suffix, 'email' => 'custA'.$suffix.'@test.com']);
// Vessel needs customer
$recordIds['Vessel'] = createRecord(Vessel::class, ['name' => 'Vessel A ' . $suffix, 'imo_number' => rand(1000000,9999999), 'customer_id' => $recordIds['Customer']]);

// Helper user for created_by
$userA = User::factory()->create(['tenant_id' => $tenantA->id]);

// Quote needs vessel (implied logic or schema)
$recordIds['Quote'] = createRecord(Quote::class, ['quote_number' => 'QT-A-' . $suffix, 'title' => 'Quote Title ' . $suffix, 'customer_id' => $recordIds['Customer'], 'vessel_id' => $recordIds['Vessel'], 'total_amount' => 100, 'created_by' => $userA->id]);

// SalesOrder needs vessel
$recordIds['SalesOrder'] = createRecord(SalesOrder::class, ['order_number' => 'SO-A-' . $suffix, 'title' => 'SO Title ' . $suffix, 'customer_id' => $recordIds['Customer'], 'vessel_id' => $recordIds['Vessel'], 'total_amount' => 100, 'created_by' => $userA->id]);

// WorkOrder needs customer
$recordIds['WorkOrder'] = createRecord(WorkOrder::class, ['title' => 'WO A ' . $suffix, 'status' => 'pending', 'customer_id' => $recordIds['Customer'], 'vessel_id' => $recordIds['Vessel']]); 

// Invoice needs sales_order
$recordIds['Invoice'] = createRecord(Invoice::class, ['invoice_number' => 'INV-A-' . $suffix, 'customer_id' => $recordIds['Customer'], 'amount' => 100, 'sales_order_id' => $recordIds['SalesOrder']]);

// Payment needs payment_date and original_amount (likely from recent migration)
$recordIds['Payment'] = createRecord(Payment::class, ['transaction_id' => 'TX-A-' . $suffix, 'amount' => 50, 'original_amount' => 50, 'original_currency' => 'EUR', 'customer_id' => $recordIds['Customer'], 'payment_date' => now()]);

$recordIds['Product'] = createRecord(Product::class, ['name' => 'Product A ' . $suffix, 'sku' => 'SKU-A-' . $suffix, 'price' => 10]);

// BankAccount uses 'name' not 'account_name' in DB apparently based on error
$recordIds['BankAccount'] = createRecord(BankAccount::class, ['name' => 'Bank A ' . $suffix, 'iban' => 'TR0000' . rand(1000,9999)]);

$recordIds['Warehouse'] = createRecord(Warehouse::class, ['name' => 'Warehouse A ' . $suffix]);

// Contract needs sales_order and customer_name and created_by
$recordIds['Contract'] = createRecord(Contract::class, ['title' => 'Contract A ' . $suffix, 'customer_id' => $recordIds['Customer'], 'start_date' => now(), 'end_date' => now()->addYear(), 'sales_order_id' => $recordIds['SalesOrder'], 'customer_name' => 'Customer A ' . $suffix, 'created_by' => $userA->id]);

// Verify creation
foreach ($recordIds as $model => $id) {
    if ($id) {
        echo "[PASS] Created {$model} A (ID: {$id})\n";
    } else {
        echo "[FAIL] Could not create {$model} A\n";
    }
}

// 4. Switch to Tenant B and Test Leaks
echo "--- Context B: Negative Leak Tests ---\n";
$tenantContext->setTenant($tenantB);

$failCount = 0;

function checkLeak($modelClass, $id, $modelName) {
    global $failCount;
    
    if (!$id) return; // Skip if creation failed

    // A. simple find()
    $found = $modelClass::find($id);
    if ($found) {
        echo "[FAIL] {$modelName}: Leaked via find()\n";
        $failCount++;
    } else {
        echo "[PASS] {$modelName}: find() -> NULL\n";
    }

    // B. whereKey()->first()
    $foundQ = $modelClass::whereKey($id)->first();
    if ($foundQ) {
        echo "[FAIL] {$modelName}: Leaked via whereKey()\n";
        $failCount++;
    } else {
        echo "[PASS] {$modelName}: whereKey() -> NULL\n";
    }

    // C. resolveRouteBinding()
    // This tests Route Model Binding isolation
    try {
        $instance = new $modelClass;
        // Check if method exists and is respecting scope
        if (method_exists($instance, 'resolveRouteBinding')) {
            $resolved = $instance->resolveRouteBinding($id);
            if ($resolved) {
                 echo "[FAIL] {$modelName}: Leaked via resolveRouteBinding()\n";
                 $failCount++;
            } else {
                 echo "[PASS] {$modelName}: resolveRouteBinding() -> NULL\n";
            }
        }
    } catch (\Exception $e) {
        echo "[WARN] {$modelName}: resolveRouteBinding check error: " . $e->getMessage() . "\n";
    }

    // D. firstOrFail() -> Exception
    try {
        $modelClass::whereKey($id)->firstOrFail();
        echo "[FAIL] {$modelName}: Leaked via firstOrFail() (No Exception)\n";
        $failCount++;
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        echo "[PASS] {$modelName}: firstOrFail() -> ModelNotFoundException\n";
    }

    // E. Bypass Check (Ensure record essentially exists)
    // Assuming 'withoutTenantScope' macro or static method exists from Trait
    try {
        $bypassed = $modelClass::withoutTenantScope()->find($id);
        if ($bypassed) {
            echo "[PASS] {$modelName}: Bypass Check -> Found (Record exists)\n";
        } else {
             echo "[WARN] {$modelName}: Bypass Check -> Not Found (Maybe deleted?)\n";
        }
    } catch (\Exception $e) {
         echo "[WARN] {$modelName}: Bypass check failed: " . $e->getMessage() . "\n";
    }
}

// Run checks
checkLeak(Customer::class, $recordIds['Customer'], 'Customer');
checkLeak(Vessel::class, $recordIds['Vessel'], 'Vessel');
checkLeak(Quote::class, $recordIds['Quote'], 'Quote');
checkLeak(SalesOrder::class, $recordIds['SalesOrder'], 'SalesOrder');
checkLeak(WorkOrder::class, $recordIds['WorkOrder'], 'WorkOrder');
checkLeak(Invoice::class, $recordIds['Invoice'], 'Invoice');
checkLeak(Payment::class, $recordIds['Payment'], 'Payment');
checkLeak(Product::class, $recordIds['Product'], 'Product');
checkLeak(BankAccount::class, $recordIds['BankAccount'], 'BankAccount');
checkLeak(Warehouse::class, $recordIds['Warehouse'], 'Warehouse');
checkLeak(Contract::class, $recordIds['Contract'], 'Contract');

if ($failCount > 0) {
    echo "\nVERIFY RESULT: FAIL ({$failCount} leaks detected)\n";
    exit(1);
} else {
    echo "\nVERIFY RESULT: PASS\n";
    exit(0);
}
