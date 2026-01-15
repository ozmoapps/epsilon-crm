<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Vessel;
use App\Models\LedgerEntry;
use App\Models\BankAccount;

$user = User::first();
$c = Customer::firstOrCreate(['email' => 'scope_test_01@test.com'], ['name' => 'Scope Test 01', 'phone' => '555']);

// Ensure Vessel
$v = $c->vessels()->first();
if (!$v) {
    $v = Vessel::create(['customer_id' => $c->id, 'name' => 'Vessel Scope 01', 'type'=>'yacht', 'flag'=>'TR']);
}

// Ensure Bank Account
$bank = BankAccount::first();
if (!$bank) {
    // Creating dummy bank if none exists (unlikely in dev but safe)
    $cur = \App\Models\Currency::firstOrCreate(['code' => 'EUR'], ['name' => 'Euro', 'symbol' => '€']);
    $bank = BankAccount::create(['name' => 'Test Bank', 'currency_id' => $cur->id, 'account_number' => '123', 'iban' => 'TR123', 'is_active' => true]);
}

// Clean previous related data to ensure idempotency (optional but clearer)
// We won't delete EVERYTHING to avoid breaking other tests, but targeted cleanup is good.
// For now, let's just ADD new distinct items or check existence.

// 1. Overdue Invoice (Total 200, Due 10 days ago)
$invNo = "INV-DD-01";
if (!Invoice::where('invoice_no', $invNo)->exists()) {
    $so = SalesOrder::create(['customer_id' => $c->id, 'vessel_id'=>$v->id, 'created_by' => $user->id, 'status' => 'approved', 'total' => 200, 'currency' => 'EUR', 'date' => now(), 'order_no' => "SO-DD-01", 'title'=>'Overdue Drill']);
    $inv = Invoice::create([
        'customer_id' => $c->id, 'sales_order_id' => $so->id, 'status' => 'issued', 'payment_status' => 'unpaid', 
        'currency' => 'EUR', 'total' => 200, 'issue_date' => now()->subDays(20), 'due_date' => now()->subDays(10), 
        'invoice_no' => $invNo
    ]);
    
    // Ledger for Invoice 1 (200 Debit)
    LedgerEntry::create([
        'customer_id' => $c->id, 'type' => 'invoice', 'direction' => 'debit', 'amount' => 200, 'currency' => 'EUR', 
        'occurred_at' => now()->subDays(20), 'source_type' => Invoice::class, 'source_id' => $inv->id, 'description' => 'Seed Overdue Invoice'
    ]);
}

// 2. Open Invoice (Total 100)
$invNo2 = "INV-DD-02";
if (!Invoice::where('invoice_no', $invNo2)->exists()) {
    $so2 = SalesOrder::create(['customer_id' => $c->id, 'vessel_id'=>$v->id, 'created_by' => $user->id, 'status' => 'approved', 'total' => 100, 'currency' => 'EUR', 'date' => now(), 'order_no' => "SO-DD-02", 'title'=>'Open Drill']);
    $inv2 = Invoice::create([
        'customer_id' => $c->id, 'sales_order_id' => $so2->id, 'status' => 'issued', 'payment_status' => 'unpaid', 
        'currency' => 'EUR', 'total' => 100, 'issue_date' => now(), 'due_date' => now()->addDays(10), 
        'invoice_no' => $invNo2
    ]);

    // Ledger for Invoice 2 (100 Debit)
    LedgerEntry::create([
        'customer_id' => $c->id, 'type' => 'invoice', 'direction' => 'debit', 'amount' => 100, 'currency' => 'EUR', 
        'occurred_at' => now(), 'source_type' => Invoice::class, 'source_id' => $inv2->id, 'description' => 'Seed Open Invoice'
    ]);
}

// 3. Advance Payment (50 EUR)
// Check if 50 EUR advance exists today
if (!Payment::where('customer_id', $c->id)->where('amount', 50)->whereNull('invoice_id')->exists()) {
    $pay = Payment::create([
        'customer_id' => $c->id, 'bank_account_id' => $bank->id,
        'amount' => 50, 'original_amount' => 50, 'original_currency' => 'EUR', 'fx_rate' => 1,
        'payment_date' => now(), 'created_by' => $user->id, 'invoice_id' => null
    ]);

    // Ledger for Advance (50 Credit)
    LedgerEntry::create([
        'customer_id' => $c->id, 'type' => 'payment', 'direction' => 'credit', 'amount' => 50, 'currency' => 'EUR', 
        'occurred_at' => now(), 'source_type' => Payment::class, 'source_id' => $pay->id, 'description' => 'Seed Advance'
    ]);
}

echo "Setup 3.15.1 Complete. 'Scope Test 01' ensured. Net Balance expected: 250 EUR (300 Debt - 50 Credit).\n";
