<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Payment;
use App\Models\Vessel;

// Helper to clean
function cleanCust($email) {
    if ($c = Customer::where('email', $email)->first()) {
        Invoice::where('customer_id', $c->id)->delete();
        SalesOrder::where('customer_id', $c->id)->delete();
        Payment::where('customer_id', $c->id)->delete();
        Vessel::where('customer_id', $c->id)->delete();
        $c->delete();
    }
}

cleanCust('sort_a@test.com');
cleanCust('sort_b@test.com');
cleanCust('sort_c@test.com');

$user = User::first();

// Cust A: 2000 Open Invoice
$cA = Customer::create(['email' => 'sort_a@test.com', 'name' => 'Sort A (High Inv)', 'phone' => '111']);
$vA = Vessel::create(['customer_id' => $cA->id, 'name' => 'Vessel A', 'type'=>'yacht', 'flag'=>'TR']);
$soA = SalesOrder::create(['customer_id' => $cA->id, 'vessel_id'=>$vA->id, 'created_by' => $user->id, 'status' => 'approved', 'total' => 2000, 'currency' => 'EUR', 'date' => now(), 'order_no' => 'SO-SA-01', 'title'=>'x']);
Invoice::create(['customer_id' => $cA->id, 'sales_order_id' => $soA->id, 'status' => 'issued', 'payment_status' => 'unpaid', 'currency' => 'EUR', 'total' => 2000, 'issue_date' => now(), 'due_date' => now(), 'invoice_no' => 'INV-SA-01']);

// Cust B: 500 Open Invoice
$cB = Customer::create(['email' => 'sort_b@test.com', 'name' => 'Sort B (Low Inv)', 'phone' => '222']);
$vB = Vessel::create(['customer_id' => $cB->id, 'name' => 'Vessel B', 'type'=>'yacht', 'flag'=>'TR']);
$soB = SalesOrder::create(['customer_id' => $cB->id, 'vessel_id'=>$vB->id, 'created_by' => $user->id, 'status' => 'approved', 'total' => 500, 'currency' => 'EUR', 'date' => now(), 'order_no' => 'SO-SB-01', 'title'=>'x']);
Invoice::create(['customer_id' => $cB->id, 'sales_order_id' => $soB->id, 'status' => 'issued', 'payment_status' => 'unpaid', 'currency' => 'EUR', 'total' => 500, 'issue_date' => now(), 'due_date' => now(), 'invoice_no' => 'INV-SB-01']);

// Cust C: 1000 Advance
$cC = Customer::create(['email' => 'sort_c@test.com', 'name' => 'Sort C (High Adv)', 'phone' => '333']);
Payment::create(['customer_id' => $cC->id, 'amount' => 1000, 'original_amount' => 1000, 'currency' => 'EUR', 'original_currency' => 'EUR', 'payment_date' => now(), 'payment_method' => 'bank_transfer']);

echo "Setup Complete: A(2000 Inv), B(500 Inv), C(1000 Adv)\n";
