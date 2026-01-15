<?php

use App\Models\PaymentAllocation;
use Illuminate\Support\Facades\DB;

// This script identifies and optionally deletes "dirty" payment allocations
// where the parent payment is NOT an advance payment (i.e., has an invoice_id).
// Such allocations are redundant/invalid because non-advance payments are
// implicitly allocated to their linked invoice.

echo "--- Invoice-Linked Allocation Cleanup Tool ---\n";

$isDryRun = true;
$args = isset($argv) ? $argv : [];
foreach ($args as $arg) {
    if (trim($arg) === '--force') {
        $isDryRun = false;
    }
}

if ($isDryRun) {
    echo "MODE: DRY RUN (No changes will be made)\n";
    echo "To actually delete records, run with: --force\n\n";
} else {
    echo "MODE: FORCE (Records WILL be deleted)\n\n";
}

DB::transaction(function () use ($isDryRun) {
    // 1. Find dirty allocations
    $query = DB::table('payment_allocations')
        ->join('payments', 'payments.id', '=', 'payment_allocations.payment_id')
        ->whereNotNull('payments.invoice_id')
        ->select(
            'payment_allocations.id as allocation_id',
            'payment_allocations.amount as allocation_amount',
            'payment_allocations.created_at as allocation_created_at',
            'payments.id as payment_id',
            'payments.invoice_id as payment_linked_invoice_id',
            'payments.amount as payment_amount',
            'payment_allocations.invoice_id as allocated_invoice_id'
        );

    $count = $query->count();
    $results = $query->get();

    echo "Found {$count} dirty allocation(s).\n";

    if ($count > 0) {
        echo str_pad("Alloc ID", 10) . str_pad("Pay ID", 10) . str_pad("Alloc Amt", 15) . str_pad("Pay Invoice", 15) . str_pad("Alloc Invoice", 15) . "\n";
        echo str_repeat("-", 70) . "\n";

        foreach ($results as $row) {
            echo str_pad($row->allocation_id, 10) . 
                 str_pad($row->payment_id, 10) . 
                 str_pad($row->allocation_amount, 15) . 
                 str_pad($row->payment_linked_invoice_id, 15) . 
                 str_pad($row->allocated_invoice_id, 15) . "\n";
        }
        echo "\n";
    }

    // 2. Delete if forced
    if (!$isDryRun && $count > 0) {
        // We can't easily delete via join in all DB drivers using Eloquent/QueryBuilder standard delete()
        // so we gather IDs and delete from the main table.
        $idsToDelete = $results->pluck('allocation_id')->toArray();
        
        $deletedCount = DB::table('payment_allocations')->whereIn('id', $idsToDelete)->delete();
        
        echo "Deleted {$deletedCount} records.\n";
        
        // 3. Verify
        $remainingCount = DB::table('payment_allocations')
            ->join('payments', 'payments.id', '=', 'payment_allocations.payment_id')
            ->whereNotNull('payments.invoice_id')
            ->count();
            
        if ($remainingCount === 0) {
            echo "VERIFICATION PASS: Count is now 0.\n";
        } else {
            echo "VERIFICATION FAIL: Count is {$remainingCount}.\n";
            throw new Exception("Verification failed, rolling back.");
        }
    }
});

echo "\nDone.\n";
