<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Vessel;

$user = User::first();

// Clean previous
Customer::where('email', 'like', 'scope_test_%')->delete(); // Cascade handled by DB or ignored (Using quick creation)
// Force clean via loop to trigger model events if needed, but direct delete is faster for bulk.
// Assuming verify script will work.

// Create 21 Customers with 100 EUR Open Invoice each
for ($i = 1; $i <= 21; $i++) {
    $email = "scope_test_{$i}@test.com";
    $name = "Scope Test " . str_pad($i, 2, '0', STR_PAD_LEFT);
    
    $c = Customer::create(['email' => $email, 'name' => $name, 'phone' => '555']);
    $v = Vessel::create(['customer_id' => $c->id, 'name' => "Vessel {$i}", 'type'=>'yacht', 'flag'=>'TR']);
    $so = SalesOrder::create(['customer_id' => $c->id, 'vessel_id'=>$v->id, 'created_by' => $user->id, 'status' => 'approved', 'total' => 100, 'currency' => 'EUR', 'date' => now(), 'order_no' => "SO-SCP-{$i}", 'title'=>'x']);
    Invoice::create(['customer_id' => $c->id, 'sales_order_id' => $so->id, 'status' => 'issued', 'payment_status' => 'unpaid', 'currency' => 'EUR', 'total' => 100, 'issue_date' => now(), 'due_date' => now(), 'invoice_no' => "INV-SCP-{$i}"]);
}

echo "Setup Complete: 21 Customers created (Scope Test 01-21) with 100 EUR each.\n";
