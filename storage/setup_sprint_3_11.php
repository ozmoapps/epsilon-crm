<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\SalesOrder;
use App\Models\User;

// Ensure User
$user = User::first() ?? User::create([
    'name' => 'Tester',
    'email' => 'overdue_tester@test.com',
    'password' => bcrypt('password')
]);

// 1. Customer with OVERDUE Invoice
$custOverdue = Customer::firstOrCreate(['email' => 'overdue@test.com'], ['name' => 'Overdue Customer', 'phone' => '111']);
// Clean
Invoice::where('customer_id', $custOverdue->id)->delete();
SalesOrder::where('customer_id', $custOverdue->id)->delete();

$vessel1 = \App\Models\Vessel::create(['customer_id' => $custOverdue->id, 'name' => 'OD Vessel', 'type'=>'yacht', 'flag'=>'TR']);

$so1 = SalesOrder::create([
    'customer_id' => $custOverdue->id, 'created_by' => $user->id,
    'vessel_id' => $vessel1->id,
    'status' => 'approved', 'total' => 1000, 'currency' => 'EUR',
    'date' => now(), 'order_no' => 'SO-OD-01', 'title' => 'Overdue Order'
]);
Invoice::create([
    'customer_id' => $custOverdue->id, 'sales_order_id' => $so1->id,
    'status' => 'issued', 'payment_status' => 'unpaid',
    'currency' => 'EUR', 'total' => 1000,
    'issue_date' => now()->subDays(10),
    'due_date' => now()->subDays(1), // YESTERDAY = OVERDUE
    'invoice_no' => 'INV-OD-01'
]);

// 2. Customer with FUTURE Invoice (Not Overdue)
$custFuture = Customer::firstOrCreate(['email' => 'future@test.com'], ['name' => 'Future Customer', 'phone' => '222']);
// Clean
Invoice::where('customer_id', $custFuture->id)->delete();
SalesOrder::where('customer_id', $custFuture->id)->delete();

$vessel2 = \App\Models\Vessel::create(['customer_id' => $custFuture->id, 'name' => 'FUT Vessel', 'type'=>'yacht', 'flag'=>'TR']);

$so2 = SalesOrder::create([
    'customer_id' => $custFuture->id, 'created_by' => $user->id,
    'vessel_id' => $vessel2->id,
    'status' => 'approved', 'total' => 500, 'currency' => 'EUR',
    'date' => now(), 'order_no' => 'SO-FUT-01', 'title' => 'Future Order'
]);
Invoice::create([
    'customer_id' => $custFuture->id, 'sales_order_id' => $so2->id,
    'status' => 'issued', 'payment_status' => 'unpaid',
    'currency' => 'EUR', 'total' => 500,
    'issue_date' => now(),
    'due_date' => now()->addDays(5), // FUTURE = NOT OVERDUE
    'invoice_no' => 'INV-FUT-01'
]);

echo "Setup Complete: Overdue Customer & Future Customer created.\n";
