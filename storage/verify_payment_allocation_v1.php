<?php

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\Currency;
use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Ensure we have a user
if (!auth()->check()) {
    $user = User::first() ?? User::factory()->create();
    auth()->login($user);
}

echo "Starting Payment Allocation Hardening Verification...\n";

// 1. Setup: Create Customer, Invoice, Bank Account
$customer = Customer::factory()->create(['name' => 'Allocation Test Customer']);
$currency = 'EUR';

$bankAccount = BankAccount::whereHas('currency', fn($q) => $q->where('code', $currency))->first();
if (!$bankAccount) {
    // Create dummy
    $c = Currency::firstOrCreate(['code' => $currency], ['name' => 'Euro', 'symbol' => '€', 'is_active' => true]);
    $bankAccount = BankAccount::create([
        'name' => 'Test Bank',
        'currency_id' => $c->id,
        'account_number' => 'TR00',
        'bank_name' => 'Test',
        'is_active' => true
    ]);
}

// Create Vessel
$vessel = \App\Models\Vessel::create([
    'name' => 'M/Y TEST VESSEL',
    'customer_id' => $customer->id,
    'type' => 'Yacht',
]);

// Create Dummy Sales Order
$salesOrder = \App\Models\SalesOrder::create([
    'order_no' => 'SO-TEST-' . rand(1000, 9999),
    'title' => 'Test Order ' . rand(1000, 9999),
    'customer_id' => $customer->id,
    'vessel_id' => $vessel->id,
    'status' => 'approved',
    'date' => now(),
    'currency' => $currency,
    'total' => 1000.00,
    'subtotal' => 1000.00,
    'tax_total' => 0,
    'created_by' => auth()->id(),
]);

// Create Invoice: 1000 EUR
$invoice = Invoice::create([
    'invoice_no' => 'INV-TEST-ALLOC-' . rand(1000, 9999),
    'sales_order_id' => $salesOrder->id,
    'customer_id' => $customer->id,
    'status' => 'issued',
    'payment_status' => 'unpaid',
    'issue_date' => now(),
    'due_date' => now()->addDays(30),
    'currency' => $currency,
    'total' => 1000.00,
    'subtotal' => 1000.00,
    'tax_total' => 0,
    'created_by' => auth()->id(),
]);
echo "[OK] Invoice created: {$invoice->invoice_no} (1000 EUR)\n";

// Create Advance Payment: 600 EUR
$payment = Payment::create([
    'invoice_id' => null,
    'customer_id' => $customer->id,
    'bank_account_id' => $bankAccount->id,
    'amount' => 600.00, // effective
    'original_amount' => 600.00,
    'original_currency' => $currency,
    'fx_rate' => 1.00,
    'payment_date' => now(),
    'created_by' => auth()->id(),
]);
echo "[OK] Advance Payment created: {$payment->id} (600 EUR)\n";

// Create Second Invoice for tests
$salesOrder2 = \App\Models\SalesOrder::create([
    'order_no' => 'SO-TEST-' . rand(1000, 9999),
    'title' => 'Test Order 2',
    'customer_id' => $customer->id,
    'vessel_id' => $vessel->id,
    'status' => 'approved',
    'currency' => $currency,
    'total' => 500.00,
    'subtotal' => 500.00,
    'tax_total' => 0,
    'created_by' => auth()->id(),
]);

$invoice2 = Invoice::create([
    'invoice_no' => 'INV-TEST-ALLOC-2-' . rand(1000, 9999),
    'sales_order_id' => $salesOrder2->id,
    'customer_id' => $customer->id,
    'status' => 'issued',
    'payment_status' => 'unpaid',
    'issue_date' => now(),
    'due_date' => now()->addDays(30),
    'currency' => $currency,
    'total' => 500.00,
    'subtotal' => 500.00,
    'tax_total' => 0,
    'created_by' => auth()->id(),
]);

// 2. Test: Allocate 400 EUR to Invoice 1 (Happy Path)
$controller = app(\App\Http\Controllers\PaymentAllocationController::class);
$request = new \Illuminate\Http\Request();
$request->merge([
    'invoice_id' => $invoice->id,
    'amount' => 400.00
]);

echo "Allocate 400 to Invoice 1...\n";
try {
    $controller->store($request, $payment);
    echo "[OK] Allocation successful.\n";
} catch (\Exception $e) {
    echo "[FAIL] Allocation failed: " . $e->getMessage() . "\n";
    exit(1);
}

$invoice->refresh();
$payment->refresh();
// unallocated should be 200

// 3. Test: Try to allocate 300 EUR to Invoice 2 (Available is 200) -> Should Fail
echo "Allocate 300 to Invoice 2 (Over limit)...\n";
$request->merge([
    'invoice_id' => $invoice2->id,
    'amount' => 300.00
]);

try {
    $controller->store($request, $payment);
    echo "[FAIL] Should have failed due to insufficient balance!\n";
} catch (\Illuminate\Validation\ValidationException $e) {
    echo "[OK] Correctly failed: " . $e->getMessage() . "\n";
}

// 4. Test: Allocate 200 EUR to Invoice 2 (Happy Path - exact balance)
echo "Allocate 200 to Invoice 2...\n";
$request->merge(['amount' => 200.00]);
try {
    $controller->store($request, $payment);
    echo "[OK] Allocation successful.\n";
} catch (\Exception $e) {
    echo "[FAIL] " . $e->getMessage() . "\n";
}

$payment->refresh();
if ($payment->unallocated_amount > 0.01) {
    echo "[FAIL] Payment should be fully allocated. Got: {$payment->unallocated_amount}\n";
} else {
    echo "[OK] Payment fully allocated.\n";
}

// 5. Test: Delete all allocations
echo "Deleting allocations...\n";
foreach ($payment->allocations as $allocation) {
    $controller->destroy($payment, $allocation);
}

$invoice->refresh();
$invoice2->refresh();
$payment->refresh();

if ($invoice->payment_status !== 'unpaid' || $invoice2->payment_status !== 'unpaid') {
    echo "[FAIL] Invoices status should be unpaid.\n";
} else {
    echo "[OK] Invoices status reverted to unpaid.\n";
}

if (abs($payment->unallocated_amount - 600.00) > 0.01) {
    echo "[FAIL] Payment remaining should be 600. Got: {$payment->unallocated_amount}\n";
} else {
    echo "[OK] Payment balance restored to 600.\n";
}

echo "✅ ALL TESTS PASSED.\n";
