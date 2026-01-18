<?php

use App\Models\Tenant;
use App\Models\User; // Assuming User model exists
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\StockMovement;
use App\Services\TenantContext;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- Verify PR6a: Wave-3 Propagation ---\n";

// Helper
function createTenant($name, $domain) {
    return Tenant::create(['name' => $name, 'domain' => $domain, 'is_active' => true]);
}

$suffix = uniqid();
$tenantA = createTenant('Tenant 6A A', 't6a-a-'.$suffix.'.test');
$tenantB = createTenant('Tenant 6A B', 't6a-b-'.$suffix.'.test');

$context = app(TenantContext::class);

// 1. Context A
$context->setTenant($tenantA);
echo "[OK] Context set to Tenant A ({$tenantA->id})\n";

// Setup Dependencies
$customerA = \App\Models\Customer::create(['name' => 'Cust A', 'email' => 'a'.$suffix.'@test.com']);
$vesselA = \App\Models\Vessel::create(['name' => 'Vessel A', 'imo_number' => rand(1000000, 9999999), 'customer_id' => $customerA->id]);
$productA = Product::create(['name' => 'Prod A', 'sku' => 'SKU-A-'.$suffix, 'price' => 10]);
$warehouseA = Warehouse::create(['name' => 'WH A ' . $suffix]);

// A1. Quote Item Propagation
echo "\n--- Testing Quote Item Propagation ---\n";
// Create Parent
$quoteA = Quote::create([
    'quote_number' => 'Q-A-'.$suffix,
    'title' => 'Quote A Title',
    'customer_id' => $customerA->id,
    'vessel_id' => $vesselA->id, // Required
    'total_amount' => 100,
    'created_by' => 1 // simplified
]);
echo "[PASS] Created Quote A (ID: {$quoteA->id}, Tenant: {$quoteA->tenant_id})\n";

// Create Child (Item) - Eloquent auto-applies tenant from context via TenantScoped
$itemA = QuoteItem::create([
    'quote_id' => $quoteA->id,
    'product_id' => $productA->id,
    'item_type' => 'product',
    'description' => 'Item A Desc',
    'qty' => 5,
    'unit_price' => 10
]);

if ($itemA->tenant_id == $tenantA->id) {
    echo "[PASS] QuoteItem A has correct tenant_id ({$itemA->tenant_id})\n";
} else {
    echo "[FAIL] QuoteItem A mismatch! Expected {$tenantA->id}, got {$itemA->tenant_id}\n";
    exit(1);
}

// A2. Work Order & Photos
echo "\n--- Testing WorkOrder Propagation ---\n";
$woA = WorkOrder::create([
    'title' => 'WO A',
    'status' => 'pending',
    'customer_id' => $customerA->id,
    'vessel_id' => $vesselA->id
]);
$photoA = \App\Models\WorkOrderPhoto::create([
    'work_order_id' => $woA->id,
    'type' => 'before',
    'path' => 'path/to/img.jpg'
]);

if ($photoA->tenant_id == $tenantA->id) {
    echo "[PASS] WorkOrderPhoto A has correct tenant_id ({$photoA->tenant_id})\n";
} else {
    echo "[FAIL] WorkOrderPhoto A mismatch! Expected {$tenantA->id}, got {$photoA->tenant_id}\n";
    exit(1);
}

// A3. Stock Movement
echo "\n--- Testing StockMovement Propagation ---\n";
$movementA = StockMovement::create([
    'warehouse_id' => $warehouseA->id,
    'product_id' => $productA->id,
    'direction' => 'in',
    'type' => 'manual_in',
    'qty' => 50,
    'unit_cost' => 5,
    'occurred_at' => now()
]);

if ($movementA->tenant_id == $tenantA->id) {
    echo "[PASS] StockMovement A has correct tenant_id ({$movementA->tenant_id})\n";
} else {
    echo "[FAIL] StockMovement A mismatch! Expected {$tenantA->id}, got {$movementA->tenant_id}\n";
    exit(1);
}


// 2. Context B: Isolation Test
echo "\n--- Context B: Isolation Tests ---\n";
$context->setTenant($tenantB);
echo "[OK] Context set to Tenant B ({$tenantB->id})\n";

// Try to find Item A
$foundItem = QuoteItem::withoutGlobalScopes()->where('id', $itemA->id)->first(); // Bypass to check existence
if ($foundItem->tenant_id == $tenantA->id) {
     // OK, record exists physically
}

// Proper Scope Check
$leakedItem = QuoteItem::find($itemA->id);
if (!$leakedItem) {
    echo "[PASS] Tenant B CANNOT see QuoteItem A\n";
} else {
    echo "[FAIL] Tenant B SAW QuoteItem A! (Leak)\n";
    exit(1);
}

$leakedPhoto = \App\Models\WorkOrderPhoto::find($photoA->id);
if (!$leakedPhoto) {
    echo "[PASS] Tenant B CANNOT see WorkOrderPhoto A\n";
} else {
    echo "[FAIL] Tenant B SAW WorkOrderPhoto A! (Leak)\n";
    exit(1);
}

$leakedMov = StockMovement::find($movementA->id);
if (!$leakedMov) {
    echo "[PASS] Tenant B CANNOT see StockMovement A\n";
} else {
    echo "[FAIL] Tenant B SAW StockMovement A! (Leak)\n";
    exit(1);
}

echo "\nVERIFY RESULT: PASS\n";
