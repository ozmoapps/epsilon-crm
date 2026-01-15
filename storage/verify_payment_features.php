<?php

use App\Models\User;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\BankAccount;
use App\Models\Currency;
use App\Models\Product;
use App\Services\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$admin = User::first() ?? User::factory()->create();
auth()->login($admin);

echo "\n--- VERIFY PAYMENT FEATURES ---\n";

DB::beginTransaction();

try {
    $ledgerService = app(LedgerService::class);
    $invController = app(\App\Http\Controllers\InvoiceController::class);
    $payController = app(\App\Http\Controllers\PaymentController::class);

    $eur = Currency::firstOrCreate(['code' => 'EUR'], ['name' => 'Euro', 'symbol' => '€', 'is_active' => true]);
    $usd = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'is_active' => true]);

    $accEUR = BankAccount::create(['name' => 'EUR Kasa', 'currency_id' => $eur->id, 'bank_name' => 'Test Bank', 'branch_name' => 'Main', 'iban' => 'TR00']);
    $accUSD = BankAccount::create(['name' => 'USD Kasa', 'currency_id' => $usd->id, 'bank_name' => 'Test Bank', 'branch_name' => 'Main', 'iban' => 'US00']);

    $customer = Customer::create(['name' => 'Payment Test Customer', 'created_by' => auth()->id()]);
    $vessel = \App\Models\Vessel::create(['name' => 'Test Vessel', 'customer_id' => $customer->id, 'type' => 'yacht']);

    $so = SalesOrder::create([
        'customer_id' => $customer->id,
        'vessel_id' => $vessel->id,
        'title' => 'Verification Order',
        'currency' => 'EUR',
        'status' => 'confirmed',
        'order_date' => now(),
        'created_by' => auth()->id()
    ]);

    $product = Product::firstOrCreate(['name' => 'Service'], ['price' => 100, 'created_by' => auth()->id()]);

    SalesOrderItem::create([
        'sales_order_id' => $so->id,
        'product_id' => $product->id,
        'item_type' => 'product',
        'description' => 'Svc',
        'quantity' => 1,
        'unit_price' => 100,
        'vat_rate' => 0,
        'total' => 100,
        'created_by' => auth()->id()
    ]);

    $so->recalculateTotals();

    $invoice = Invoice::create([
        'sales_order_id' => $so->id,
        'customer_id' => $customer->id,
        'status' => 'issued',
        'issue_date' => now(),
        'currency' => 'EUR',
        'subtotal' => 100,
        'tax_total' => 0,
        'total' => 100,
        'created_by' => auth()->id()
    ]);

    echo "SETUP: Invoice #{$invoice->id} created (100 EUR)\n";

    // Same currency: 50 EUR
    $req1 = Request::create('/', 'POST', [
        'amount' => 50,
        'payment_date' => now()->format('Y-m-d'),
        'bank_account_id' => $accEUR->id,
        'payment_method' => 'cash'
    ]);
    $payController->store($req1, $invoice, $ledgerService);

    $pay1 = Payment::latest('id')->first();
    if ((float)$pay1->amount !== 50.0) throw new Exception("FAIL: Same currency - amount mismatch");
    if ($pay1->original_currency !== 'EUR') throw new Exception("FAIL: Same currency - original_currency mismatch");
    if (abs((float)$pay1->fx_rate - 1.0) > 0.00000001) throw new Exception("FAIL: Same currency - fx_rate mismatch");
    echo "PASS: Same Currency Payment (50 EUR)\n";

    // Cross currency: 50 USD @ 1.25 => 40 EUR
    $req2 = Request::create('/', 'POST', [
        'amount' => 50,
        'payment_date' => now()->format('Y-m-d'),
        'bank_account_id' => $accUSD->id,
        'fx_rate' => 1.25,
        'payment_method' => 'wire'
    ]);
    $payController->store($req2, $invoice, $ledgerService);

    $pay2 = Payment::latest('id')->first();
    if ($pay2->original_currency !== 'USD') throw new Exception("FAIL: Cross currency - original_currency mismatch");
    if (abs((float)$pay2->original_amount - 50.0) > 0.001) throw new Exception("FAIL: Cross currency - original_amount mismatch");
    if (abs((float)$pay2->fx_rate - 1.25) > 0.00001) throw new Exception("FAIL: Cross currency - fx_rate mismatch");
    if (abs((float)$pay2->amount - 40.0) > 0.01) throw new Exception("FAIL: Cross currency - equivalent mismatch (Expected 40, got {$pay2->amount})");
    echo "PASS: Cross Currency Payment (50 USD => 40 EUR)\n";

    $ledger2 = \App\Models\LedgerEntry::where('source_type', Payment::class)->where('source_id', $pay2->id)->first();
    if (!$ledger2) throw new Exception("FAIL: Ledger entry missing for payment");
    if (abs((float)$ledger2->amount - 40.0) > 0.01) throw new Exception("FAIL: Ledger amount should be invoice equivalent (40 EUR)");
    if (!str_contains($ledger2->description, '50.00 USD') || !str_contains($ledger2->description, 'Kur: 1 EUR = 1.25 USD') || !str_contains($ledger2->description, 'Eşdeğer: 40.00 EUR')) {
        throw new Exception("FAIL: Ledger description incorrect: " . $ledger2->description);
    }
    echo "PASS: Ledger Description Correct\n";

    // Destroy guard
    $invController->destroy($invoice);
    if (!Invoice::find($invoice->id)) throw new Exception("FAIL: Invoice was deleted but should be guarded");
    echo "PASS: Invoice Deletion Guarded (Still exists)\n";

    DB::rollBack();
    echo "\n--- ALL TESTS PASSED ---\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "\nFAIL: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}
