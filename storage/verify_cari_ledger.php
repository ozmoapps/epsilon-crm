<?php

use App\Models\User;
use App\Models\Customer;
use App\Models\Vessel;
use App\Models\Quote;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\LedgerEntry;
use App\Models\Product;
use App\Models\InvoiceLine;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "--- VERIFY CARI LEDGER HARDENED TESTS ---\n";

// Login
$admin = User::first();
if (!$admin) { 
    $admin = User::factory()->create(['email' => 'admin@example.com']);
}
auth()->login($admin);

DB::beginTransaction();

try {
    $ledgerService = app(\App\Services\LedgerService::class);
    $invoiceController = app(\App\Http\Controllers\InvoiceController::class);
    $paymentController = app(\App\Http\Controllers\PaymentController::class);

    // --- Scenario A: EUR Flow ---
    echo "\n[Scenario A] EUR Flow\n";
    
    $customer1 = Customer::create(['name' => 'EUR Customer ' . time(), 'created_by' => auth()->id()]);
    $vessel1 = Vessel::create(['customer_id' => $customer1->id, 'name' => 'EUR Yacht', 'type' => 'yacht']);
    $product = Product::first() ?? Product::create(['name' => 'Test Product', 'price' => 100]);

    // Sales Order
    $soEUR = SalesOrder::create([
        'customer_id' => $customer1->id,
        'vessel_id' => $vessel1->id,
        'title' => 'EUR Order',
        'status' => 'confirmed',
        'currency' => 'EUR',
        'order_date' => now(),
        'created_by' => auth()->id(),
    ]);
    
    SalesOrderItem::create([
        'sales_order_id' => $soEUR->id,
        'product_id' => $product->id,
        'item_type' => 'product',
        'description' => 'Service EUR',
        'quantity' => 1,
        'unit_price' => 1000,
        'vat_rate' => 0,
        'total' => 1000,
    ]);
    $soEUR->recalculateTotals();

    // Invoice EUR
    $invEUR = Invoice::create([
        'sales_order_id' => $soEUR->id,
        'customer_id' => $customer1->id,
        'status' => 'draft',
        'issue_date' => now(),
        'currency' => 'EUR',
        'created_by' => auth()->id(),
    ]);
    
    InvoiceLine::create([
        'invoice_id' => $invEUR->id,
        'sales_order_item_id' => $soEUR->items->first()->id,
        'product_id' => $product->id,
        'description' => 'Service EUR',
        'quantity' => 1,
        'unit_price' => 1000,
        'tax_rate' => 0,
        'total' => 1000,
    ]);
    $invEUR->update(['subtotal' => 1000, 'tax_total' => 0, 'total' => 1000]);

    // Issue
    try { $invoiceController->issue($invEUR, $ledgerService); } catch (\Exception $e) {}
    $invEUR->refresh();
    
    $debitEUR = LedgerEntry::where('source_type', Invoice::class)->where('source_id', $invEUR->id)->first();
    if (!$debitEUR || $debitEUR->amount != 1000 || $debitEUR->currency != 'EUR') {
        throw new Exception("FAIL Scenario A: EUR Debit missing or incorrect.");
    }
    echo "PASS: EUR Debit Created (1000)\n";

    // Payment EUR
    $reqPay = \Illuminate\Http\Request::create('/', 'POST', ['amount' => 600, 'payment_date' => now()->format('Y-m-d')]);
    $paymentController->store($reqPay, $invEUR, $ledgerService);

    $creditEUR = LedgerEntry::where('type', 'payment')->where('customer_id', $customer1->id)->first();
    if (!$creditEUR || $creditEUR->amount != 600 || $creditEUR->currency != 'EUR') {
        throw new Exception("FAIL Scenario A: EUR Credit missing or incorrect.");
    }
    echo "PASS: EUR Credit Created (600)\n";

    // --- Scenario B: TRY Flow ---
    echo "\n[Scenario B] TRY Flow\n";
    $customer2 = Customer::create(['name' => 'TRY Customer ' . time(), 'created_by' => auth()->id()]);
    $vessel2 = Vessel::create(['customer_id' => $customer2->id, 'name' => 'TRY Yacht', 'type' => 'yacht']);
    
    $soTRY = SalesOrder::create([
        'customer_id' => $customer2->id,
        'vessel_id' => $vessel2->id,
        'title' => 'TRY Order',
        'status' => 'confirmed',
        'currency' => 'TRY',
        'order_date' => now(),
        'created_by' => auth()->id(),
    ]);
    SalesOrderItem::create([
        'sales_order_id' => $soTRY->id,
        'product_id' => $product->id,
        'item_type' => 'product',
        'description' => 'Service TRY',
        'quantity' => 2,
        'unit_price' => 500,
        'vat_rate' => 20,
        'total' => 1200,
    ]);
    $soTRY->recalculateTotals();

    $invTRY = Invoice::create([
        'sales_order_id' => $soTRY->id,
        'customer_id' => $customer2->id,
        'status' => 'draft',
        'issue_date' => now(),
        'currency' => 'TRY',
        'created_by' => auth()->id(),
    ]);

    InvoiceLine::create([
        'invoice_id' => $invTRY->id,
        'sales_order_item_id' => $soTRY->items->first()->id,
        'product_id' => $product->id,
        'description' => 'Service TRY',
        'quantity' => 2,
        'unit_price' => 500,
        'tax_rate' => 20,
        'total' => 1200,
    ]);
    $invTRY->update(['subtotal' => 1000, 'tax_total' => 200, 'total' => 1200]);

    // Issue
    try { $invoiceController->issue($invTRY, $ledgerService); } catch (\Exception $e) {}

    $debitTRY = LedgerEntry::where('source_type', Invoice::class)->where('source_id', $invTRY->id)->first();
    if (!$debitTRY || $debitTRY->currency != 'TRY' || $debitTRY->amount != 1200) {
        throw new Exception("FAIL Scenario B: TRY Debit incorrect.");
    }
    echo "PASS: TRY Debit Created (1200)\n";
    
    // Payment TRY (Partial)
    $reqPayTRY = \Illuminate\Http\Request::create('/', 'POST', ['amount' => 500, 'payment_date' => now()->format('Y-m-d')]);
    $paymentController->store($reqPayTRY, $invTRY, $ledgerService);
    
    $creditTRY = LedgerEntry::where('type', 'payment')->where('source_id', $invTRY->payments->first()->id)->first();
    if (!$creditTRY || $creditTRY->amount != 500 || $creditTRY->currency != 'TRY') {
         throw new Exception("FAIL Scenario B: TRY Credit missing or incorrect.");
    }
    echo "PASS: TRY Credit Created (500 Partial)\n";

    // --- Scenario C: Vessel Filter ---
    echo "\n[Scenario C] Vessel Verification\n";
    if ($debitEUR->vessel_id != $vessel1->id) {
        throw new Exception("FAIL Scenario C: Vessel ID not linked to Ledger Entry.");
    }
    echo "PASS: Ledger Entry has Vessel ID\n";


    // --- Scenario D: Idempotency (Crucial) ---
    echo "\n[Scenario D] Idempotency Force Test\n";
    // Force call createDebit again
    $dup = $ledgerService->createDebitFromInvoice($invEUR);
    if ($dup !== null) {
        throw new Exception("FAIL: Idempotency failed, returned value instead of null.");
    }
    echo "PASS: Duplicate Debit Rejected (Null returned)\n";
    
    // Check DB count (Debit)
    $cnt = LedgerEntry::where('source_type', Invoice::class)->where('source_id', $invEUR->id)->count();
    if ($cnt !== 1) {
         throw new Exception("FAIL: DB has duplicate debit entries!");
    }
    echo "PASS: DB Debit Count is 1\n";

    // --- Scenario D-2: Credit Idempotency ---
    echo "\n[Scenario D-2] Idempotency Credit Test\n";
    $payTRY = $invTRY->payments->first();
    if (!$payTRY) throw new Exception("FAIL SETUP: Payment not found for TRY");
    
    $dupCredit = $ledgerService->createCreditFromPayment($payTRY);
    if ($dupCredit !== null) {
        throw new Exception("FAIL: Credit Idempotency failed, returned value.");
    }
    echo "PASS: Duplicate Credit Rejected (Null returned)\n";
    
    $cntCredit = LedgerEntry::where('type', 'payment')->where('source_id', $payTRY->id)->count();
    if ($cntCredit !== 1) {
        throw new Exception("FAIL: DB has duplicate credit entries!");
    }
    echo "PASS: DB Credit Count is 1\n";

    
    // --- Final Balance Check ---
    echo "\n[Final] Balance Reports\n";
    
    // Customer 1 (EUR): 1000 Debit - 600 Credit = 400 Balance
    $bal1 = LedgerEntry::where('customer_id', $customer1->id)
         ->selectRaw('sum(case when direction = "debit" then amount else -amount end) as bal')
         ->value('bal');
    
    echo "EUR Customer Balance: $bal1 (Expected: 400.0000)\n";
    if (abs($bal1 - 400) > 0.001) throw new Exception("FAIL: EUR Balance Mismatch");
    
    // Customer 2 (TRY): 1200 Debit - 500 Credit = 700 Balance
    $bal2 = LedgerEntry::where('customer_id', $customer2->id)
         ->selectRaw('sum(case when direction = "debit" then amount else -amount end) as bal')
         ->value('bal');

    echo "TRY Customer Balance: $bal2 (Expected: 700.0000)\n";
    if (abs($bal2 - 700) > 0.001) throw new Exception("FAIL: TRY Balance Mismatch");

    echo "\n--- ALL/HARDENED TESTS PASSED ---\n";
    
    DB::rollBack();

} catch (\Exception $e) {
    DB::rollBack();
    echo "\nFAIL: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}
