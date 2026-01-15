<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 1. Setup Data
$user = \App\Models\User::first();
if (!$user) {
    // Should default seeder run? Or create dummy.
    $user = \App\Models\User::create([
        'name' => 'Tester',
        'email' => 'finance_tester@test.com',
        'password' => bcrypt('password')
    ]);
}
$customer = Customer::firstOrCreate(['email' => 'hardening@test.com'], ['name' => 'Hardening Test', 'phone' => '123']);
$currency = 'EUR';

// Clear previous data
Invoice::where('customer_id', $customer->id)->delete();
Payment::where('customer_id', $customer->id)->delete();
// Allocations cascade delete usually, but let's be safe
DB::table('payment_allocations')->whereIn('invoice_id', function($q) use ($customer) {
    $q->select('id')->from('invoices')->where('customer_id', $customer->id);
})->delete();
\App\Models\SalesOrder::where('customer_id', $customer->id)->delete();


// Create Vessel
$vessel = \App\Models\Vessel::create([
    'customer_id' => $customer->id,
    'name' => 'Hardening Vessel',
    'type' => 'Yacht',
    'flag' => 'TR'
]);

// Create SalesOrder
$so = \App\Models\SalesOrder::create([
    'customer_id' => $customer->id,
    'vessel_id' => $vessel->id,
    'status' => 'approved',
    'total' => 1000,
    'currency' => $currency,
    'date' => now(),
    'order_no' => 'SO-HARD-001',
    'title' => 'Hardening Order',
    'created_by' => $user->id
]);

// Create Invoice (1000 EUR)
$invoice = Invoice::create([
    'customer_id' => $customer->id,
    'sales_order_id' => $so->id,
    'status' => 'issued',
    'payment_status' => 'partial',
    'currency' => $currency,
    'total' => 1000,
    'issue_date' => now(),
    'due_date' => now(),
    'invoice_no' => 'HARD-001'
]);

// Create Legacy Payment (Linked) (200 EUR)
$legacyPayment = Payment::create([
    'customer_id' => $customer->id,
    'amount' => 200,
    'original_amount' => 200,
    'currency' => $currency, // Base
    'original_currency' => $currency,
    'invoice_id' => $invoice->id, // LINKED!
    'payment_date' => now(),
    'method' => 'bank'
]);

// FORCE Create Allocation for Legacy Payment (Double Count Scenario)
// This should NOT happen in strict usage, but we guard against it.
PaymentAllocation::create([
    'payment_id' => $legacyPayment->id,
    'invoice_id' => $invoice->id,
    'amount' => 200
]);

// Create Advance Payment (Unlinked) (300 EUR base)
$advancePayment = Payment::create([
    'customer_id' => $customer->id,
    'amount' => 300, 
    'original_amount' => 300,
    'currency' => $currency,
    'original_currency' => $currency,
    'invoice_id' => null, // UNLINKED
    'payment_date' => now(),
    'method' => 'bank'
]);

// Allocated 100 from Advance to Invoice
PaymentAllocation::create([
    'payment_id' => $advancePayment->id,
    'invoice_id' => $invoice->id,
    'amount' => 100
]);

echo "Data Setup:\n";
echo "Invoice Total: 1000\n";
echo "Legacy Payment (Linked): 200\n";
echo "Legacy Payment Allocation (Double Count Risk): 200\n";
echo "Advance Payment Allocation: 100\n";
echo "Expected Remaining: 1000 - 200 (Legacy) - 100 (Advance) = 700\n";

// 2. Verify Logic (Controller Query Reproduction)

$paidExpr = "COALESCE((SELECT SUM(amount) FROM payments WHERE invoice_id = invoices.id), 0)";
// NEW GUARDED LOGIC
$allocExpr = "COALESCE((SELECT SUM(pa.amount) FROM payment_allocations pa JOIN payments p ON p.id = pa.payment_id WHERE pa.invoice_id = invoices.id AND p.invoice_id IS NULL), 0)";

$result = Invoice::where('id', $invoice->id)
    ->select(
        'id',
        'total',
        DB::raw("($paidExpr) as legacy_paid"),
        DB::raw("($allocExpr) as alloc_paid"),
        DB::raw("(total - $paidExpr - $allocExpr) as remaining")
    )->first();

echo "\nVerification Result:\n";
echo "Legacy Paid (DB): " . $result->legacy_paid . "\n";
echo "Alloc Paid (DB): " . $result->alloc_paid . "\n";
echo "Remaining (DB): " . $result->remaining . "\n";

if (abs($result->remaining - 700) < 0.001) {
    echo "\n[PASS] Double-Count Guard is WORKING.\n";
} else {
    echo "\n[FAIL] Double-Count Guard Failed. Expected 700, got " . $result->remaining . "\n";
}
