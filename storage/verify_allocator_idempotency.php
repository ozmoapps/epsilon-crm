<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Customer;
use App\Models\Vessel;
use App\Models\SalesOrder;
use App\Models\Invoice;
use App\Models\BankAccount;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Services\PaymentAutoAllocator;

echo "Sprint 3.16.1 Verify: Allocator Idempotency + Double-Count Guard\n";

$needTables = ['customers','vessels','sales_orders','invoices','payments','payment_allocations','bank_accounts'];
foreach ($needTables as $t) {
    if (!Schema::hasTable($t)) {
        echo "Missing table: {$t}\n";
        exit(1);
    }
}

$user = User::first();
$bank = BankAccount::first();

if (!$user || !$bank) {
    echo "Missing User or BankAccount seed.\n";
    exit(1);
}

$allocator = app(PaymentAutoAllocator::class);

// Unique-ish marker
$marker = 'SST-ALLOC-IDEM-' . now()->format('YmdHis');

// Clean old by name (best-effort, minimal)
$old = Customer::where('name', $marker)->first();
if ($old) {
    // best effort cleanup
    PaymentAllocation::whereIn('payment_id', Payment::where('customer_id', $old->id)->pluck('id'))->delete();
    Payment::where('customer_id', $old->id)->delete();
    Invoice::where('customer_id', $old->id)->delete();
    SalesOrder::where('customer_id', $old->id)->delete();
    Vessel::where('customer_id', $old->id)->delete();
    $old->delete();
}

// Create customer + vessel
$c = Customer::create([
    'name' => $marker,
    'email' => strtolower($marker) . '@example.test',
]);

$v = Vessel::create([
    'name' => 'Vessel ' . $marker,
    'customer_id' => $c->id,
]);

// Sales orders & invoices (two invoices, FIFO by due_date)
$so1 = SalesOrder::create([
    'customer_id' => $c->id,
    'vessel_id' => $v->id,
    'created_by' => $user->id,
    'status' => 'approved',
    'total' => 500,
    'currency' => 'EUR',
    'date' => now(),
    'order_no' => 'SO-' . $marker . '-1',
    'title' => 'Idem Test 1',
]);

$inv1 = Invoice::create([
    'customer_id' => $c->id,
    'sales_order_id' => $so1->id,
    'status' => 'issued',
    'payment_status' => 'unpaid',
    'currency' => 'EUR',
    'total' => 500,
    'issue_date' => now()->subDays(15),
    'due_date' => now()->subDays(5),   // older -> first
    'invoice_no' => 'INV-' . $marker . '-1',
]);

$so2 = SalesOrder::create([
    'customer_id' => $c->id,
    'vessel_id' => $v->id,
    'created_by' => $user->id,
    'status' => 'approved',
    'total' => 400,
    'currency' => 'EUR',
    'date' => now(),
    'order_no' => 'SO-' . $marker . '-2',
    'title' => 'Idem Test 2',
]);

$inv2 = Invoice::create([
    'customer_id' => $c->id,
    'sales_order_id' => $so2->id,
    'status' => 'issued',
    'payment_status' => 'unpaid',
    'currency' => 'EUR',
    'total' => 400,
    'issue_date' => now()->subDays(10),
    'due_date' => now()->subDays(1),
    'invoice_no' => 'INV-' . $marker . '-2',
]);

// Create advance payment 700 EUR
$p = Payment::create([
    'invoice_id' => null,
    'customer_id' => $c->id,
    'bank_account_id' => $bank->id,
    'amount' => 700,
    'original_amount' => 700,
    'original_currency' => 'EUR',
    'fx_rate' => 1.0,
    'payment_date' => now(),
    'created_by' => $user->id,
]);

// Call allocator twice
$r1 = $allocator->allocateForPayment($p);
$snap1 = PaymentAllocation::where('payment_id', $p->id)->orderBy('invoice_id')->get(['invoice_id','amount'])
    ->mapWithKeys(fn($row) => [$row->invoice_id => (float)$row->amount])->toArray();

$r2 = $allocator->allocateForPayment($p);
$snap2 = PaymentAllocation::where('payment_id', $p->id)->orderBy('invoice_id')->get(['invoice_id','amount'])
    ->mapWithKeys(fn($row) => [$row->invoice_id => (float)$row->amount])->toArray();

// Expectations:
// inv1 gets 500, inv2 gets 200, total allocated 700, remaining 0
$expected = [
    $inv1->id => 500.0,
    $inv2->id => 200.0,
];

$inv1Fresh = Invoice::find($inv1->id);
$inv2Fresh = Invoice::find($inv2->id);

$okSnap = ($snap1 == $expected) && ($snap2 == $expected);
$okIdem = ($snap1 == $snap2);
$okStatus = ($inv1Fresh->payment_status === 'paid') && ($inv2Fresh->payment_status === 'partial');
$okRemaining = ((float)str_replace(',', '', $r2['payment_remaining']) == 0.0) || ($r2['payment_remaining'] === '0.00');

echo "Run1: allocated_total={$r1['allocated_total']} remaining={$r1['payment_remaining']} currency={$r1['currency']}\n";
echo "Run2: allocated_total={$r2['allocated_total']} remaining={$r2['payment_remaining']} currency={$r2['currency']}\n";
echo "AllocSnap1: " . json_encode($snap1) . "\n";
echo "AllocSnap2: " . json_encode($snap2) . "\n";
echo "Invoice1 status={$inv1Fresh->payment_status} (expected paid)\n";
echo "Invoice2 status={$inv2Fresh->payment_status} (expected partial)\n";

if ($okSnap && $okIdem && $okStatus && $okRemaining) {
    echo "PASS ✅ Allocator idempotency + FIFO OK\n";
} else {
    echo "FAIL ❌\n";
    echo "okSnap=" . ($okSnap ? '1' : '0') . " okIdem=" . ($okIdem ? '1' : '0') . " okStatus=" . ($okStatus ? '1' : '0') . " okRemaining=" . ($okRemaining ? '1' : '0') . "\n";
}

echo "Done.\n";
