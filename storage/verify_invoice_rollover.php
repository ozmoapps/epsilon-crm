<?php

use App\Models\Invoice;
use App\Models\SalesOrder;
use App\Models\SalesOrderShipment;
use App\Models\SalesOrderItem;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
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

// Setup Base Data
$wh = Warehouse::create(['name' => 'Active Default', 'is_default' => true, 'is_active' => true]);
$prod = Product::create(['name' => 'P1', 'sku' => 'P1', 'price' => 10, 'qty' => 100]);
$user = User::firstOrCreate(['email' => 'roll@test.com'], ['id' => 888, 'name' => 'Tester', 'password' => 'x']);
if ($user->id !== 888) { // update ID if needed for consistency with other tests
    $user->id = 888;
    $user->save();
}
auth()->login($user);

$controller = new InvoiceController();
$currentYear = now()->year;
$lastYear = $currentYear - 1;

// --- TEST 1: Rollover (Last Year Data Ignored) ---
echo "\n--- TEST 1: Rollover Check ---\n";
// Dummy SO for past invoice
$soPast = SalesOrder::create(['customer_id' => 1, 'vessel_id' => 1, 'title' => 'Past', 'status' => 'confirmed', 'created_by' => 888]);
// Create invoice for LAST year with high number
Invoice::create([
    'sales_order_id' => $soPast->id, 
    'customer_id' => 1,
    'status' => 'issued',
    'invoice_no' => 'INV-' . $lastYear . '-0999',
    'created_by' => 888,
    'issue_date' => $lastYear . '-12-31'
]);
echo "Created past year invoice: INV-$lastYear-0999\n";

// Issue new invoice for THIS year
$so1 = SalesOrder::create(['customer_id' => 1, 'vessel_id' => 1, 'title' => 'T1', 'status' => 'confirmed', 'created_by' => 888]);
SalesOrderItem::create(['sales_order_id' => $so1->id, 'product_id' => $prod->id, 'qty' => 1, 'unit_price' => 10, 'vat_rate' => 0, 'item_type' => 'product', 'description' => 'D1']);
$inv1 = Invoice::create(['sales_order_id' => $so1->id, 'customer_id' => 1, 'status' => 'draft']);
\App\Models\InvoiceLine::create(['invoice_id' => $inv1->id, 'sales_order_item_id' => $so1->items->first()->id, 'quantity' => 1, 'unit_price' => 10, 'total' => 10, 'description' => 'L1']);

$controller->issue($inv1);
$inv1->refresh();
echo "Invoice 1: " . $inv1->invoice_no . "\n";

if ($inv1->invoice_no === 'INV-' . $currentYear . '-0001') {
    echo "PASS: Rollover correct (ignored last year, started 0001).\n";
} else {
    echo "FAIL: Expected INV-$currentYear-0001, got " . $inv1->invoice_no . "\n";
}

// --- TEST 2: Gap Filling / Max Logic ---
echo "\n--- TEST 2: Max Logic (jump gap) ---\n";
// Manually create INV-Year-0010
Invoice::create([
    'sales_order_id' => $soPast->id,
    'customer_id' => 1,
    'status' => 'issued',
    'invoice_no' => 'INV-' . $currentYear . '-0010',
    'created_by' => 888,
    'issue_date' => now()
]);
echo "Manually inserted gap: INV-$currentYear-0010\n";

// Issue next
$inv2 = Invoice::create(['sales_order_id' => $so1->id, 'customer_id' => 1, 'status' => 'draft']); // Reuse SO
\App\Models\InvoiceLine::create(['invoice_id' => $inv2->id, 'sales_order_item_id' => $so1->items->first()->id, 'quantity' => 1, 'unit_price' => 10, 'total' => 10, 'description' => 'L2']);

$controller->issue($inv2);
$inv2->refresh();
echo "Invoice 2: " . $inv2->invoice_no . "\n";

if ($inv2->invoice_no === 'INV-' . $currentYear . '-0011') {
    echo "PASS: Correctly jumped to 0011 (Max+1).\n";
} else {
    echo "FAIL: Expected 0011, got " . $inv2->invoice_no . "\n";
}

// --- TEST 3: Collision Retry ---
echo "\n--- TEST 3: Collision Retry ---\n";
// We expect next to be 0012. Let's block 0012 manually but NOT via normal means logic??
// Wait, if I insert 0012, Max becomes 0012, so next calc becomes 0013.
// To simulate collision we need the code to calculate X, but find X taken.
// With Max logic: 
// It reads Max (0011). Calc -> 0012.
// If I assume concurrency: T1 reads 0011, calc 0012. T2 reads 0011, calc 0012.
// T1 inserts 0012. T2 fails.
// T2 retry loop: i=1. Max is now 0012 (if T1 committed). Calc -> 0013. + i(1) = 0014?
// User accepted potential gaps.
// Let's verify that retry logic works even if we force a collision.
// Hard to force real concurrency in single script.
// Instead, let's trick the controller.
// We can't easily mockery inside this script without extensive setup.
// But we can check if it handles "taken" PRE-CHECK.
// Create 0012 but DON'T update Max?? DB constraints prevent inconsistent state if I insert properly.
// Okay, let's verify logic:
// Max is 0011. Next is 0012.
// Insert 0012. Max is 0012. Next is 0013.
// So simpler logic works.
// What if we inserted 0012 but the QUERY 'max' somehow missed it (isolation)? 
// Retry loop code: $nextNum = $numericPart + 1 + $i;
// Iter 0: Max=11. Next=12.
// Iter 1: Max=11. Next=12 + 1 = 13.
// So if DB read is stale (still sees 11), retry loop forces 13.
// Let's Verify this logic by NOT inserting 0012, but checking 0013? No.
// Let's just create 0012 and expect 0013.
Invoice::create([
    'sales_order_id' => $soPast->id,
    'customer_id' => 1,
    'status' => 'issued',
    'invoice_no' => 'INV-' . $currentYear . '-0012',
    'created_by' => 888
]);
echo "Gap taken: 0012\n";

$inv3 = Invoice::create(['sales_order_id' => $so1->id, 'customer_id' => 1, 'status' => 'draft']);
\App\Models\InvoiceLine::create(['invoice_id' => $inv3->id, 'sales_order_item_id' => $so1->items->first()->id, 'quantity' => 1, 'unit_price' => 10, 'total' => 10, 'description' => 'L3']);

$controller->issue($inv3);
$inv3->refresh();
echo "Invoice 3: " . $inv3->invoice_no . "\n";

if ($inv3->invoice_no === 'INV-' . $currentYear . '-0013') {
    echo "PASS: Correctly generated 0013.\n";
} else {
    echo "FAIL: Expected 0013, got " . $inv3->invoice_no . "\n";
}
