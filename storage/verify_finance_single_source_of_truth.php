<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use App\Models\User;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\LedgerEntry;

use App\Services\LedgerService;

echo "Sprint 3.16 Verify: Financial Single Source of Truth\n";

if (!Schema::hasTable('payment_allocations')) {
    echo "SKIP: payment_allocations table missing.\n";
    return;
}

$ledgerService = app(LedgerService::class);

$user = User::first();
if (!$user) { echo "FAIL: No users.\n"; return; }

$bank = \App\Models\BankAccount::with('currency')->first();
if (!$bank) { echo "FAIL: No bank accounts.\n"; return; }
$cur = $bank->currency->code ?? 'EUR';

// Pick any existing customer that has at least one vessel (for SalesOrder)
$customer = Customer::with('vessels')->whereHas('vessels')->first();
if (!$customer) { echo "FAIL: No customer with vessel found.\n"; return; }
$vesselId = $customer->vessels->first()->id;

$uniq = 'SST-' . now()->format('YmdHis');

$so = SalesOrder::create([
    'customer_id' => $customer->id,
    'vessel_id'   => $vesselId,
    'created_by'  => $user->id,
    'status'      => 'approved',
    'total'       => 1000,
    'currency'    => $cur,
    'date'        => now(),
    'order_no'    => "SO-{$uniq}",
    'title'       => "SST Verify {$uniq}",
]);

$invoice = Invoice::create([
    'customer_id'    => $customer->id,
    'sales_order_id' => $so->id,
    'status'         => 'issued',
    'payment_status' => 'unpaid',
    'currency'       => $cur,
    'total'          => 1000,
    'issue_date'     => now(),
    'due_date'       => now()->addDays(7),
    'invoice_no'     => "INV-{$uniq}",
]);

// Create ledger debit via service (idempotent)
$ledgerService->createDebitFromInvoice($invoice, $user->id);

// Create an ADVANCE payment 1200 (overpay invoice -> remaining advance expected 200)
$payment = Payment::create([
    'invoice_id'        => null,
    'customer_id'       => $customer->id,
    'bank_account_id'   => $bank->id,
    'amount'            => 1200,
    'original_amount'   => 1200,
    'original_currency' => $cur,
    'fx_rate'           => 1.0,
    'payment_date'      => now(),
    'payment_method'    => 'transfer',
    'reference_number'  => "REF-{$uniq}",
    'notes'             => "SST Advance {$uniq}",
    'created_by'        => $user->id,
]);

// Ledger credit should be created in ORIGINAL currency/amount
$ledgerService->createCreditFromPayment($payment, $user->id, $vesselId);

// Idempotency check (call twice)
$ledgerService->createCreditFromPayment($payment, $user->id, $vesselId);

// Force allocation manually (deterministic)
// allocate 1000 to invoice, leave 200 as open advance
DB::table('payment_allocations')->insert([
    'payment_id' => $payment->id,
    'invoice_id' => $invoice->id,
    'amount'     => 1000,
    'created_at' => now(),
    'updated_at' => now(),
]);

// Compute remaining invoice (ignore legacyPaid; none created)
$allocPaid = (float) DB::table('payment_allocations')
    ->where('invoice_id', $invoice->id)
    ->where('payment_id', $payment->id)
    ->sum('amount');

$invoiceRemaining = max(0, (float)$invoice->total - $allocPaid);

// Compute open advance remaining for THIS payment
$allocFromPayment = (float) DB::table('payment_allocations')
    ->where('payment_id', $payment->id)
    ->sum('amount');

$openAdvanceRemaining = max(0, (float)$payment->amount - $allocFromPayment);

// Ledger delta for THIS invoice + THIS payment only
$invDebit = (float) LedgerEntry::where('source_type', Invoice::class)
    ->where('source_id', $invoice->id)
    ->where('direction', 'debit')
    ->sum('amount');

$payCredits = (float) LedgerEntry::where('source_type', Payment::class)
    ->where('source_id', $payment->id)
    ->where('direction', 'credit')
    ->sum('amount');

$ledgerDelta = $invDebit - $payCredits;

// Expect: ledgerDelta == invoiceRemaining - openAdvanceRemaining
$expected = $invoiceRemaining - $openAdvanceRemaining;

$ledgerRows = LedgerEntry::where('source_type', Payment::class)
    ->where('source_id', $payment->id)
    ->where('direction', 'credit')
    ->get(['id','amount','currency','description']);

echo "InvoiceRemaining: {$invoiceRemaining}\n";
echo "OpenAdvanceRemaining: {$openAdvanceRemaining}\n";
echo "LedgerDelta(inv - pay): {$ledgerDelta}\n";
echo "Expected(Rem - Adv): {$expected}\n";

$tol = 0.01;
$okMath = abs($ledgerDelta - $expected) <= $tol;

// Currency check: payment ledger must be original currency
$ledgerCurrencyOk = $ledgerRows->count() >= 1 && $ledgerRows->first()->currency === $payment->original_currency;

// Idempotency check: only 1 ledger credit row for this payment (unique guard)
$idempotentOk = $ledgerRows->count() === 1;

if ($okMath && $ledgerCurrencyOk && $idempotentOk) {
    echo "PASS ✅ SST math/currency/idempotency OK\n";
} else {
    echo "FAIL ❌\n";
    echo " - Math: " . ($okMath ? "OK" : "BAD") . "\n";
    echo " - Currency: " . ($ledgerCurrencyOk ? "OK" : "BAD") . " (ledger=" . ($ledgerRows->first()->currency ?? '-') . " payment=" . ($payment->original_currency ?? '-') . ")\n";
    echo " - Idempotency: " . ($idempotentOk ? "OK" : "BAD") . " (ledger rows=" . $ledgerRows->count() . ")\n";
}

echo "Done.\n";
