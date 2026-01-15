<?php

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesOrderShipment;
use App\Models\SalesOrderShipmentLine;
use App\Models\SalesOrderReturn;
use App\Models\SalesOrderReturnLine;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\StockService;
use App\Http\Controllers\InvoiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();


// Reset State
Invoice::truncate();
App\Models\InvoiceLine::truncate();
SalesOrderShipment::truncate();
SalesOrderShipmentLine::truncate();
SalesOrderReturn::truncate();
SalesOrderReturnLine::truncate();
SalesOrder::truncate();
SalesOrderItem::truncate();

// Setup User
if (!\App\Models\User::find(1)) {
    \App\Models\User::forceCreate(['id' => 1, 'name' => 'Admin', 'email' => 'admin@test.com', 'password' => bcrypt('password')]);
}
auth()->loginUsingId(1);
$userId = 1;

// Setup Common Data
$warehouse = Warehouse::firstOrCreate(['id' => 1], ['name' => 'Main', 'is_default' => true]);
if (Product::count() == 0) {
    Product::create(['name' => 'Test Product', 'sku' => 'TEST-SKU', 'price' => 100, 'track_stock' => true]);
}
$product = Product::first();

// Helper
function createReq($data) {
    $r = new Request();
    $r->merge($data);
    return $r;
}
$controller = new InvoiceController();

echo "--- START STRICT HYBRID VERIFICATION ---\n";

// --- SCENARIO A: No Posted Shipment -> Invoice Issue -> Auto Shipment ---
echo "\n[SCENARIO A] Order (10) -> No Ship -> Invoice (10) -> Issue -> Auto-Shipment?\n";
$soA = SalesOrder::create(['customer_id' => 1, 'vessel_id' => 1, 'title' => 'Test A', 'order_no' => 'SO-A', 'currency' => 'USD', 'status' => 'confirmed', 'created_by' => $userId]);
// using qty
$itemA = SalesOrderItem::create(['sales_order_id' => $soA->id, 'product_id' => $product->id, 'qty' => 10, 'unit_price' => 100, 'item_type' => 'product', 'description' => 'Item A']);

$reqA = createReq(['sales_order_id' => $soA->id, 'items' => [['id' => $itemA->id, 'quantity' => 10]]]);
app()->instance('request', $reqA);

try {
    $controller->store($reqA);
} catch (\Throwable $e) {
    echo "ERROR A (Store): " . $e->getMessage() . "\n";
}

$invoiceA = Invoice::where('sales_order_id', $soA->id)->first();
if ($invoiceA) {
    echo "Invoice A Status: " . $invoiceA->status . "\n";
    $controller->issue($invoiceA);
    $invoiceA->refresh();
    echo "Invoice A Issued Status: " . $invoiceA->status . "\n";
    
    $shipA = SalesOrderShipment::where('invoice_id', $invoiceA->id)->first();
    if ($shipA && $shipA->status === 'posted') {
        echo "SUCCESS A: Auto-Shipment created and posted. ID: " . $shipA->id . "\n";
    } else {
        echo "FAIL A: Auto-Shipment missing or not posted.\n";
    }
} else {
    echo "FAIL A: Invoice not created.\n";
}


// --- SCENARIO B: Posted Shipment -> Invoice Issue -> NO New Shipment ---
echo "\n[SCENARIO B] Order (10) -> Ship (5, Posted) -> Invoice (5) -> Issue -> NO New Shipment?\n";
$soB = SalesOrder::create(['customer_id' => 1, 'vessel_id' => 1, 'title' => 'Test B', 'order_no' => 'SO-B', 'currency' => 'USD', 'status' => 'confirmed', 'created_by' => $userId]);
$itemB = SalesOrderItem::create(['sales_order_id' => $soB->id, 'product_id' => $product->id, 'qty' => 10, 'unit_price' => 100, 'item_type' => 'product', 'description' => 'Item B']);

$shipB = SalesOrderShipment::create(['sales_order_id' => $soB->id, 'warehouse_id' => 1, 'status' => 'draft', 'created_by' => $userId]);
SalesOrderShipmentLine::create(['sales_order_shipment_id' => $shipB->id, 'sales_order_item_id' => $itemB->id, 'product_id' => $product->id, 'qty' => 5, 'description' => 'Ship Item B']);
(new StockService)->postSalesOrderShipment($shipB);

$reqB = createReq(['sales_order_id' => $soB->id, 'items' => [['id' => $itemB->id, 'quantity' => 5]]]);
app()->instance('request', $reqB);

try {
    $controller->store($reqB);
} catch (\Throwable $e) {
    echo "ERROR B: " . $e->getMessage() . "\n";
}

$invoiceB = Invoice::where('sales_order_id', $soB->id)->first();
if ($invoiceB) {
    $controller->issue($invoiceB);
    $count = SalesOrderShipment::where('sales_order_id', $soB->id)->count();
    $auto = SalesOrderShipment::where('sales_order_id', $soB->id)->where('invoice_id', $invoiceB->id)->count();
    
    // We expect count=1 (the original manual shipment) and auto=0.
    echo "Shipment Count B: $count (Expected 1)\n";
    if ($count == 1 && $auto == 0) {
        echo "SUCCESS B: No duplicate shipment.\n";
    } else {
        echo "FAIL B: Unexpected shipments.\n";
    }
} else {
    echo "FAIL B: Invoice not created.\n";
}


// --- SCENARIO C: Net Shipped Calculation (Return Logic) ---
echo "\n[SCENARIO C] Order (10) -> Ship (5) -> Return (2) -> Limit Should be 3\n";
$soC = SalesOrder::create(['customer_id' => 1, 'vessel_id' => 1, 'title' => 'Test C', 'order_no' => 'SO-C', 'currency' => 'USD', 'status' => 'confirmed', 'created_by' => $userId]);
$itemC = SalesOrderItem::create(['sales_order_id' => $soC->id, 'product_id' => $product->id, 'qty' => 10, 'unit_price' => 100, 'item_type' => 'product', 'description' => 'Item C']);

$shipC = SalesOrderShipment::create(['sales_order_id' => $soC->id, 'warehouse_id' => 1, 'status' => 'draft', 'created_by' => $userId]);
$shipLineC = SalesOrderShipmentLine::create(['sales_order_shipment_id' => $shipC->id, 'sales_order_item_id' => $itemC->id, 'product_id' => $product->id, 'qty' => 5, 'description' => 'Ship Item C']);
(new StockService)->postSalesOrderShipment($shipC);

$retC = SalesOrderReturn::create(['sales_order_id' => $soC->id, 'warehouse_id' => 1, 'status' => 'draft', 'sales_order_shipment_id' => $shipC->id, 'created_by' => $userId]);
SalesOrderReturnLine::create(['sales_order_return_id' => $retC->id, 'sales_order_shipment_line_id' => $shipLineC->id, 'product_id' => $product->id, 'qty' => 2, 'description' => 'Return Item C']);
(new StockService)->postSalesOrderReturn($retC);

// 1. Try Invoice 4 (Fail)
$reqC_Fail = createReq(['sales_order_id' => $soC->id, 'items' => [['id' => $itemC->id, 'quantity' => 4]]]);
app()->instance('request', $reqC_Fail);

$failed = false;
try {
    $controller->store($reqC_Fail);
} catch (\Illuminate\Validation\ValidationException $e) {
    echo "SUCCESS C1: Validation Blocked (ValidationException)\n";
    $failed = true;
} catch (\Throwable $e) {
    echo "INFO C1: Threw " . get_class($e) . "\n";
}

if (!$failed) {
    $res = $controller->store($reqC_Fail);
    if ($res->getSession()->has('error') && str_contains($res->getSession()->get('error'), 'aşıldı')) {
         echo "SUCCESS C1: Validation Blocked 4 qty (Limit 3). Msg: " . $res->getSession()->get('error') . "\n";
    } else {
         echo "FAIL C1: Validation passed for 4 qty!\n";
    }
}

// 2. Invoice 3 (Success)
$reqC_Success = createReq(['sales_order_id' => $soC->id, 'items' => [['id' => $itemC->id, 'quantity' => 3]]]);
app()->instance('request', $reqC_Success);
try {
    $controller->store($reqC_Success);
    $invoiceC = Invoice::where('sales_order_id', $soC->id)->first();
    if ($invoiceC) {
        echo "SUCCESS C2: Invoiced 3 qty.\n";
    } else {
        echo "FAIL C2: Invoice 3 qty failed.\n";
    }
} catch (\Throwable $e) {
    echo "FAIL C2: " . $e->getMessage() . "\n";
}

echo "\n--- VERIFICATION COMPLETE ---\n";
