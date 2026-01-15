<?php

use App\Models\Invoice;
use App\Models\SalesOrder;
use App\Models\SalesOrderShipment;
use App\Models\SalesOrderItem;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use App\Http\Controllers\InvoiceController;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Reset data safely
Schema::disableForeignKeyConstraints();
Invoice::truncate();
SalesOrder::truncate();
SalesOrderShipment::truncate();
SalesOrderItem::truncate();
Warehouse::truncate();
Product::truncate();
Schema::enableForeignKeyConstraints();


// Create default warehouse (active)
$whDefault = Warehouse::create(['name' => 'Default Active', 'is_default' => true, 'is_active' => true]);
// Create inactive warehouse
$whInactive = Warehouse::create(['name' => 'Inactive', 'is_default' => false, 'is_active' => false]);
// Create active non-default
$whActive = Warehouse::create(['name' => 'Active Non-Default', 'is_default' => false, 'is_active' => true]);

$product = Product::create(['name' => 'Test', 'sku' => 'TEST', 'price' => 10, 'qty' => 100]);
$user = \App\Models\User::firstOrCreate(['id' => 999], ['name' => 'Tester', 'email' => 'tester@test.com', 'password' => 'x']);
auth()->login($user);

$controller = new InvoiceController();

// TEST 1: Standard Issue -> Should pick Default Active
echo "\n--- TEST 1: Standard Issue (Default Active) ---\n";
$so1 = SalesOrder::create(['customer_id' => 1, 'vessel_id' => 1, 'title' => 'T1', 'status' => 'confirmed', 'created_by' => 999]);
SalesOrderItem::create(['sales_order_id' => $so1->id, 'product_id' => $product->id, 'qty' => 1, 'unit_price' => 10, 'vat_rate' => 0, 'item_type' => 'product', 'description' => 'Test Item']);
$inv1 = Invoice::create(['sales_order_id' => $so1->id, 'customer_id' => 1, 'status' => 'draft']);
// Add line
\App\Models\InvoiceLine::create([
    'invoice_id' => $inv1->id,
    'sales_order_item_id' => $so1->items->first()->id,
    'quantity' => 1,
    'unit_price' => 10,
    'total' => 10,
    'description' => 'Test Item'
]);

$controller->issue($inv1);
$inv1->refresh();
echo "Invoice 1 Status: " . $inv1->status . " No: " . $inv1->invoice_no . "\n";
$ship1 = SalesOrderShipment::where('invoice_id', $inv1->id)->first();
echo "Shipment 1 Warehouse: " . $ship1->warehouse_id . " (Expected: " . $whDefault->id . ")\n";
if ($ship1->warehouse_id == $whDefault->id) echo "PASS: Picked Default Active\n";
else echo "FAIL: Picked wrong warehouse\n";

// TEST 2: Only Inactive Warehouse Exists -> Should Fail
echo "\n--- TEST 2: Active Warehouse Missing ---\n";
Warehouse::where('id', $whDefault->id)->update(['is_active' => false]);
Warehouse::where('id', $whActive->id)->update(['is_active' => false]);
// Now only inactive exists
$so2 = SalesOrder::create(['customer_id' => 1, 'vessel_id' => 1, 'title' => 'T2', 'status' => 'confirmed', 'created_by' => 999]);
$inv2 = Invoice::create(['sales_order_id' => $so2->id, 'customer_id' => 1, 'status' => 'draft']);

$res2 = $controller->issue($inv2);
if ($res2->getSession()->has('error') && str_contains($res2->getSession()->get('error'), 'Aktif depo bulunamadı')) {
    echo "PASS: Correctly failed due to no active warehouse.\n";
} else {
    echo "FAIL: Should have failed. Msg: " . $res2->getSession()->get('error') . "\n";
}

// Restore warehouses
Warehouse::where('id', $whDefault->id)->update(['is_active' => true]);

// TEST 3: Concurrency / Retry Logic
echo "\n--- TEST 3: Concurrency / Retry ---\n";
// Create a collision. Current year invoices count is 1 (from Test 1). Next should be 2.
// Let's manually create an invoice with No 2 (simulating a race condition where another process took it).
$year = now()->year;
$clashingNo = 'INV-' . $year . '-0002';
Invoice::create([
    'sales_order_id' => $so1->id, 
    'customer_id' => 1, 
    'status' => 'issued', 
    'invoice_no' => $clashingNo,
    'created_by' => 999
]);
echo "Simulated collision: Created $clashingNo manually.\n";

$so3 = SalesOrder::create(['customer_id' => 1, 'vessel_id' => 1, 'title' => 'T3', 'status' => 'confirmed', 'created_by' => 999]);
SalesOrderItem::create(['sales_order_id' => $so3->id, 'product_id' => $product->id, 'qty' => 1, 'unit_price' => 10, 'vat_rate' => 0, 'item_type' => 'product', 'description' => 'Test Item']);
$inv3 = Invoice::create(['sales_order_id' => $so3->id, 'customer_id' => 1, 'status' => 'draft']);
// Add line needed for auto shipment? Yes, but just verifying invoice no here.
\App\Models\InvoiceLine::create([
    'invoice_id' => $inv3->id,
    'sales_order_item_id' => $so3->items->first()->id,
    'quantity' => 1,
    'unit_price' => 10,
    'total' => 10,
    'description' => 'Test Item'
]);

$controller->issue($inv3);
$inv3->refresh();

echo "Invoice 3 No: " . $inv3->invoice_no . "\n";
if ($inv3->invoice_no === 'INV-' . $year . '-0003') {
    echo "PASS: Skipped collision (0002) and generated 0003.\n";
} else {
    echo "FAIL: Expected 0003, got " . $inv3->invoice_no . "\n";
}
