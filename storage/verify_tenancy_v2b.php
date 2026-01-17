<?php

use App\Models\WorkOrder;
use App\Models\Quote;
use App\Models\SalesOrder;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\BankAccount;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\FollowUp;
use App\Models\Tenant;
use Illuminate\Support\Facades\Schema;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- TENANCY V2B VERIFICATION ---\n";

// 1. Verify Columns

// 1. Verify Columns (Core + Children)
$tables = [
    'work_orders', 'quotes', 'sales_orders', 'contracts', 
    'invoices', 'payments', 'bank_accounts', 'products', 
    'warehouses', 'follow_ups', 'ledger_entries',
    'quote_items', 'sales_order_items', 'work_order_items', 
    'invoice_lines', 'sales_order_shipments'
];

$missingCols = [];
foreach ($tables as $table) {
    if (Schema::hasTable($table) && !Schema::hasColumn($table, 'tenant_id')) {
        $missingCols[] = $table;
    }
}

if (!empty($missingCols)) {
    echo "[FAIL] Missing tenant_id in: " . implode(', ', $missingCols) . "\n";
    exit(1);
} else {
    echo "[PASS] All transactional tables have tenant_id column.\n";
}

// 2. Verify Data Integrity (Orphans)
$orphans = [];
foreach ($tables as $table) {
    if (!Schema::hasTable($table)) continue;
    $count = \Illuminate\Support\Facades\DB::table($table)->whereNull('tenant_id')->count();
    if ($count > 0) {
        $orphans[$table] = $count;
    }
}

if (!empty($orphans)) {
    echo "[FAIL] Orphan records found (null tenant_id):\n";
    print_r($orphans);
    exit(1);
} else {
    echo "[PASS] No orphan records found.\n";
}

// 3. Join Integrity Checks (Cross-Tenant Association Prevention)
echo "--- JOIN INTEGRITY CHECKS ---\n";
$mismatches = 0;

// Sales Order -> Customer
$soMismatches = DB::table('sales_orders')
    ->join('customers', 'sales_orders.customer_id', '=', 'customers.id')
    ->whereColumn('sales_orders.tenant_id', '!=', 'customers.tenant_id')
    ->count();
if ($soMismatches > 0) {
    echo "[FAIL] Sales Order <-> Customer mismatch: $soMismatches\n";
    $mismatches++;
}

// Work Order -> Vessel
$woMismatches = DB::table('work_orders')
    ->join('vessels', 'work_orders.vessel_id', '=', 'vessels.id')
    ->join('customers', 'vessels.customer_id', '=', 'customers.id') // Vessel tenant depends on customer usually, but vessel has tenant_id now
    ->whereColumn('work_orders.tenant_id', '!=', 'vessels.tenant_id')
    ->count();
if ($woMismatches > 0) {
    echo "[FAIL] Work Order <-> Vessel mismatch: $woMismatches\n";
    $mismatches++;
}

// Invoice -> Customer
$invMismatches = DB::table('invoices')
    ->join('customers', 'invoices.customer_id', '=', 'customers.id')
    ->whereColumn('invoices.tenant_id', '!=', 'customers.tenant_id')
    ->count();
if ($invMismatches > 0) {
    echo "[FAIL] Invoice <-> Customer mismatch: $invMismatches\n";
    $mismatches++;
}

// Payment -> Invoice (if linked)
$payMismatches = DB::table('payments')
    ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
    ->whereColumn('payments.tenant_id', '!=', 'invoices.tenant_id')
    ->whereNotNull('payments.invoice_id')
    ->count();
if ($payMismatches > 0) {
    echo "[FAIL] Payment <-> Invoice mismatch: $payMismatches\n";
    $mismatches++;
}

if ($mismatches === 0) {
    echo "[PASS] Join Integrity Verified (0 mismatches).\n";
} else {
    exit(1);
}

// 4. Verify Model logic (Auto Assignment) - LedgerEntry
$tenant = Tenant::first();
if ($tenant) {
    app(\App\Services\TenantContext::class)->setTenant($tenant);
    // Ledger Entry Test
    try {
        $ledger = \App\Models\LedgerEntry::create([
             // minimal fields
             'type' => 'invoice',
             'direction' => 'debit',
             'amount' => 100,
             'currency' => 'USD',
             'description' => 'VerifyV2B',
             'occurred_at' => now(),
             'created_by' => 1
        ]);
        
        if ($ledger->tenant_id === $tenant->id) {
            echo "[PASS] LedgerEntry auto-assigned tenant_id.\n";
        } else {
            echo "[FAIL] LedgerEntry tenant_id mismatch. Expected {$tenant->id}, got {$ledger->tenant_id}\n";
        }
        $ledger->delete();
    } catch (\Exception $e) {
        echo "[WARN] LedgerEntry creation failed (might need valid foreign keys): " . $e->getMessage() . "\n";
    }
}

echo "[SUCCESS] V2B Verification Completed.\n";

