<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "--- Finance Performance Index Verification ---\n\n";

$failCount = 0;

$checks = [
    [
        'table' => 'payment_allocations',
        'index' => 'pa_invoice_id_idx',
        'desc'  => 'Allocation -> Invoice Lookup'
    ],
    [
        'table' => 'payment_allocations',
        'index' => 'pa_payment_id_idx',
        'desc'  => 'Allocation -> Payment Lookup'
    ],
    [
        'table' => 'payments',
        'index' => 'pay_invoice_id_idx',
        'desc'  => 'Payment -> Invoice Lookup'
    ],
    [
        'table' => 'payments',
        'index' => 'pay_customer_invoice_idx',
        'desc'  => 'Payment -> Customer + Invoice Lookup'
    ],
    [
        'table' => 'payments',
        'index' => 'pay_customer_curr_invoice_idx',
        'desc'  => 'Payment -> Advance Filtering (Cust+Curr+NoInv)'
    ],
    [
        'table' => 'invoices',
        'index' => 'inv_customer_status_pay_curr_idx',
        'desc'  => 'Invoice -> Open Invoice Filtering (Status/Pay/Curr)'
    ],
    [
        'table' => 'invoices',
        'index' => 'inv_customer_curr_duedate_idx',
        'desc'  => 'Invoice -> Overdue Filtering'
    ],
    [
        'table' => 'ledger_entries',
        'index' => 'le_customer_curr_idx',
        'desc'  => 'Ledger -> Balance Aggregation'
    ]
];

$driver = DB::getDriverName();
echo "Database Driver: {$driver}\n\n";

foreach ($checks as $c) {
    if (!Schema::hasTable($c['table'])) {
        echo "SKIP ⚠️ Table '{$c['table']}' missing.\n";
        continue;
    }

    $exists = indexExists($c['table'], $c['index'], $driver);

    if ($exists) {
        echo "PASS ✅ {$c['table']}: {$c['index']} ({$c['desc']})\n";
    } else {
        echo "FAIL ❌ {$c['table']}: {$c['index']} (Missing!)\n";
        $failCount++;
    }
}

echo "\n";
if ($failCount > 0) {
    echo "{$failCount} Indexes Missing. Migration failure?\n";
    exit(1);
} else {
    echo "All Performance Indexes Verified.\n";
}


/**
 * Helper: Check Index Existence (borrowed logic from migration)
 */
function indexExists($table, $indexName, $driver) {
    if ($driver === 'sqlite') {
        $count = DB::table('sqlite_master')
            ->where('type', 'index')
            ->where('tbl_name', $table)
            ->where('name', $indexName)
            ->count();
        return $count > 0;
    }

    if ($driver === 'mysql') {
        $dbName = DB::connection()->getDatabaseName();
        $count = DB::table('information_schema.statistics')
            ->where('table_schema', $dbName)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->count();
        return $count > 0;
    }

    if ($driver === 'pgsql') {
        $count = DB::table('pg_indexes')
            ->where('tablename', $table)
            ->where('indexname', $indexName)
            ->count();
        return $count > 0;
    }

    return false;
}
