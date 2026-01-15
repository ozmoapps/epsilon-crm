<?php

use App\Models\Payment;
use App\Models\Invoice;
use App\Models\PaymentAllocation;
use App\Services\PaymentAutoAllocator;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Customer;
use App\Models\Vessel;
use App\Models\SalesOrder;
use App\Models\BankAccount;

echo "--- Sprint 3.17 Finance Hygiene Verification ---\n\n";

$failCount = 0;

// 1. Verify Cleanliness (No dirty allocations)
echo "[1] Checking for Invoice-Linked Allocation Dirty Data...\n";
$dirtyCount = DB::table('payment_allocations')
    ->join('payments', 'payments.id', '=', 'payment_allocations.payment_id')
    ->whereNotNull('payments.invoice_id')
    ->count();

if ($dirtyCount === 0) {
    echo "PASS ✅ No dirty allocations found.\n";
} else {
    echo "FAIL ❌ Found {$dirtyCount} dirty allocations. Run cleanup script!\n";
    $failCount++;
}
echo "\n";


// 2. Verify Guard Rail (Standard Payment should NOT allocate)
echo "[2] Verifying Guard Rail for Standard Payments...\n";
try {
DB::transaction(function () use (&$failCount) {
    $user = User::first();
    $bank = BankAccount::first();
    if (!$user || !$bank) {
        echo "SKIP ⚠️ Missing User or BankAccount for test.\n";
        return;
    }

    // Setup Test Data
    $marker = 'HYG-GUARD-' . uniqid();
    $c = Customer::create(['name' => $marker, 'email' => $marker.'@test.com']);
    $v = Vessel::create(['name' => $marker, 'customer_id' => $c->id]);
    
    // Create Sales Order
    $so = SalesOrder::create([
        'customer_id' => $c->id,
        'vessel_id' => $v->id, // If vessel required
        'created_by' => $user->id,
        'status' => 'approved',
        'total' => 100,
        'currency' => 'EUR',
        'date' => now(),
        'order_no' => 'SO-HYG-' . uniqid(),
        'title' => 'Hyg Test'
    ]);

    // Create Invoice
    $inv = Invoice::create([
        'customer_id' => $c->id,
        'sales_order_id' => $so->id,
        'status' => 'issued',
        'payment_status' => 'unpaid',
        'currency' => 'EUR',
        'total' => 100,
        'invoice_no' => 'INV-HYG-' . uniqid(),
        'issue_date' => now(),
        'due_date' => now(),
        'created_by' => $user->id
    ]);

    // Create Standard Payment (linked to invoice)
    $pay = Payment::create([
        'invoice_id' => $inv->id,
        'customer_id' => $c->id,
        'bank_account_id' => $bank->id,
        'amount' => 100,
        'original_amount' => 100,
        'original_currency' => 'EUR',
        'fx_rate' => 1.0,
        'payment_date' => now(),
        'created_by' => $user->id
    ]);

    // Call Allocator
    $allocator = app(PaymentAutoAllocator::class);
    $result = $allocator->allocateForPayment($pay);

    // Verify
    // 1. Result should indicate 0 allocations
    $msg = "  Result: alloc_total={$result['allocated_total']}, paid_count={$result['paid_count']}";
    
    // 2. No allocation records should exist
    $allocCount = PaymentAllocation::where('payment_id', $pay->id)->count();
    
    if ($result['allocated_total'] == 0 && $allocCount == 0) {
        echo "PASS ✅ Guard Rail working. No allocations created for standard payment. ({$msg})\n";
    } else {
        echo "FAIL ❌ Guard Rail failed. Allocations created! ({$msg}, DB Count: {$allocCount})\n";
        $failCount++;
    }

    throw new \Exception("ROLLBACK_TEST_DATA");
});
} catch (\Exception $e) {
    if ($e->getMessage() !== "ROLLBACK_TEST_DATA") {
        echo "ERROR during test execution: " . $e->getMessage() . "\n";
        $failCount++;
    }
}
echo "\n";

// 3. Verify Codebase for Double-Count Guard
echo "[3] Verifying Double-Count Guard Code in CustomerLedgerIndexController...\n";
$controllerFile = app_path('Http/Controllers/CustomerLedgerIndexController.php');
$content = file_get_contents($controllerFile);
if (str_contains($content, "->whereNull('payments.invoice_id')")) {
    echo "PASS ✅ Controller contains '->whereNull(\'payments.invoice_id\')' filter.\n";
} else {
    echo "FAIL ❌ Controller seems to miss the null check in string search.\n";
    $failCount++;
}
echo "\n";

// 4. Run Idempotency Script
echo "[4] Running Idempotency Verification...\n";
ob_start();
require_once __DIR__ . '/verify_allocator_idempotency.php';
$output = ob_get_clean();

// Check output for "PASS"
if (str_contains($output, 'PASS ✅')) {
    echo "PASS ✅ Idempotency script passed.\n";
} else {
    echo "FAIL ❌ Idempotency script failed. Output:\n{$output}\n";
    $failCount++;
}


echo "\n--- Verification Complete ---\n";
if ($failCount === 0) {
    echo "ALL TESTS PASSED ✅\n";
} else {
    echo "{$failCount} TESTS FAILED ❌\n";
    exit(1);
}
