<?php
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesOrderShipment;
use App\Models\SalesOrderShipmentLine;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Customer;
use App\Models\Vessel;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;

// Ensure Warehouse and Product exist
$wh = Warehouse::first() ?? Warehouse::create(['name' => 'Main', 'created_by' => 1]);
$prod = Product::first();
if (!$prod->track_stock) {
    $prod->update(['track_stock' => true]);
}

echo "--- SCENARIO A: NO POSTED SHIPMENT ---\n";
$soA = SalesOrder::create([
    'customer_id' => Customer::first()->id, 
    'vessel_id' => Vessel::first()->id,
    'title' => 'Scenario A',
    'status' => 'confirmed', 
    'currency' => 'USD', 
    'order_no' => 'SO-A-' . rand(100,999), 
    'created_by' => 1
]);
$itemA = SalesOrderItem::create([
    'sales_order_id' => $soA->id,
    'product_id' => $prod->id,
    'item_type' => 'product',
    'description' => 'Item A',
    'quantity' => 10,
    'unit_price' => 100, 
    'tax_rate' => 20
]);

// 1. Create Invoice (Draft) - Should act like Order based
$invA = Invoice::create([
    'sales_order_id' => $soA->id,
    'customer_id' => $soA->customer_id,
    'status' => 'draft',
    'issue_date' => now(),
    'currency' => 'USD',
    'created_by' => 1
]);
$invLineA = InvoiceLine::create([
    'invoice_id' => $invA->id,
    'sales_order_item_id' => $itemA->id,
    'product_id' => $prod->id,
    'description' => 'Item A',
    'quantity' => 10,
    'unit_price' => 100,
    'tax_rate' => 20,
    'total' => 1000
]);

// 2. Issue Invoice
$controller = new \App\Http\Controllers\InvoiceController();
// Mock request? No, calling issue logic directly or via route. 
// Function signature: issue(Invoice $invoice)
$response = $controller->issue($invA);
// We expect a redirect response but we check side effects.

$soA->refresh();
$shipmentCount = $soA->shipments->count();
$autoShipment = $soA->shipments->first();
echo "Shipments Count: " . $shipmentCount . " (Expected: 1)\n";
if ($autoShipment) {
    echo "Shipment Status: " . $autoShipment->status . " (Expected: posted)\n";
    echo "Shipment Invoice ID: " . $autoShipment->invoice_id . " (Expected: " . $invA->id . ")\n";
} else {
    echo "FAIL: Auto shipment not created.\n";
}


echo "\n--- SCENARIO B: HAS POSTED SHIPMENT ---\n";
$soB = SalesOrder::create([
    'customer_id' => Customer::first()->id, 
    'vessel_id' => Vessel::first()->id,
    'title' => 'Scenario B',
    'status' => 'confirmed', 
    'currency' => 'USD', 
    'order_no' => 'SO-B-' . rand(100,999), 
    'created_by' => 1
]);
$itemB = SalesOrderItem::create([
    'sales_order_id' => $soB->id,
    'product_id' => $prod->id,
    'item_type' => 'product',
    'description' => 'Item B',
    'quantity' => 10,
    'unit_price' => 100, 
    'tax_rate' => 20
]);

// 1. Create Posting Shipment (Manual) - Part 1 (5 qty)
$shipB = SalesOrderShipment::create([
    'sales_order_id' => $soB->id,
    'warehouse_id' => $wh->id,
    'status' => 'draft',
    'created_by' => 1
]);
SalesOrderShipmentLine::create([
    'sales_order_shipment_id' => $shipB->id,
    'sales_order_item_id' => $itemB->id,
    'qty' => 5
]);
(new StockService)->postSalesOrderShipment($shipB);

// 2. Create Invoice (Draft) - Qty 5
$invB = Invoice::create([
    'sales_order_id' => $soB->id,
    'customer_id' => $soB->customer_id,
    'status' => 'draft',
    'issue_date' => now(),
    'currency' => 'USD',
    'created_by' => 1
]);
$invLineB = InvoiceLine::create([
    'invoice_id' => $invB->id,
    'sales_order_item_id' => $itemB->id,
    'product_id' => $prod->id,
    'description' => 'Item B',
    'quantity' => 5,
    'unit_price' => 100,
    'tax_rate' => 20,
    'total' => 500
]);

// 3. Issue Invoice
$controller->issue($invB);

$soB->refresh();
$shipmentCountB = $soB->shipments->count();
echo "Shipments Count: " . $shipmentCountB . " (Expected: 1 - just the manual one)\n";
$firstShip = $soB->shipments->first();
echo "First Ship ID: " . $firstShip->id . " (Expected: " . $shipB->id . ")\n";
