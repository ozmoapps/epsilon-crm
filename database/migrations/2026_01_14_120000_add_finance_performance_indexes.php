<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Payment Allocations
        $this->addIndexSafe('payment_allocations', 'pa_invoice_id_idx', ['invoice_id']);
        $this->addIndexSafe('payment_allocations', 'pa_payment_id_idx', ['payment_id']);

        // 2) Payments
        $this->addIndexSafe('payments', 'pay_invoice_id_idx', ['invoice_id']);
        $this->addIndexSafe('payments', 'pay_customer_invoice_idx', ['customer_id', 'invoice_id']);
        $this->addIndexSafe('payments', 'pay_customer_curr_invoice_idx', ['customer_id', 'original_currency', 'invoice_id']);

        // 3) Invoices
        $this->addIndexSafe('invoices', 'inv_customer_status_pay_curr_idx', ['customer_id', 'status', 'payment_status', 'currency']);
        $this->addIndexSafe('invoices', 'inv_customer_curr_duedate_idx', ['customer_id', 'currency', 'due_date']);

        // 4) Ledger Entries
        $this->addIndexSafe('ledger_entries', 'le_customer_curr_idx', ['customer_id', 'currency']);
    }

    public function down(): void
    {
        $this->dropIndexSafe('payment_allocations', 'pa_invoice_id_idx');
        $this->dropIndexSafe('payment_allocations', 'pa_payment_id_idx');

        $this->dropIndexSafe('payments', 'pay_invoice_id_idx');
        $this->dropIndexSafe('payments', 'pay_customer_invoice_idx');
        $this->dropIndexSafe('payments', 'pay_customer_curr_invoice_idx');

        $this->dropIndexSafe('invoices', 'inv_customer_status_pay_curr_idx');
        $this->dropIndexSafe('invoices', 'inv_customer_curr_duedate_idx');

        $this->dropIndexSafe('ledger_entries', 'le_customer_curr_idx');
    }

    private function addIndexSafe(string $table, string $indexName, array $columns): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        if ($this->indexExists($table, $indexName)) {
            if (app()->runningInConsole()) {
                echo "Index {$indexName} on {$table} already exists. Skipping.\n";
            }
            return;
        }

        try {
            Schema::table($table, function (Blueprint $tableObj) use ($indexName, $columns) {
                $tableObj->index($columns, $indexName);
            });

            if (app()->runningInConsole()) {
                echo "Created index {$indexName} on {$table}.\n";
            }
        } catch (QueryException $e) {
            // If a race condition or driver nuance tries to create a duplicate, ignore
            if ($this->isDuplicateIndexError($e)) {
                if (app()->runningInConsole()) {
                    echo "Index {$indexName} on {$table} already exists (duplicate). Skipping.\n";
                }
                return;
            }

            // Any other error: fail fast (do NOT silently pass)
            throw $e;
        }
    }

    private function dropIndexSafe(string $table, string $indexName): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        if (!$this->indexExists($table, $indexName)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $tableObj) use ($indexName) {
                $tableObj->dropIndex($indexName);
            });
        } catch (\Throwable $e) {
            // Ignore drop errors (down should be best-effort)
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return DB::table('sqlite_master')
                ->where('type', 'index')
                ->where('tbl_name', $table)
                ->where('name', $indexName)
                ->count() > 0;
        }

        if ($driver === 'mysql') {
            $dbName = DB::connection()->getDatabaseName();
            return DB::table('information_schema.statistics')
                ->where('table_schema', $dbName)
                ->where('table_name', $table)
                ->where('index_name', $indexName)
                ->count() > 0;
        }

        if ($driver === 'pgsql') {
            return DB::table('pg_indexes')
                ->where('tablename', $table)
                ->where('indexname', $indexName)
                ->count() > 0;
        }

        // Unknown driver: assume not exists, rely on try/catch
        return false;
    }

    private function isDuplicateIndexError(QueryException $e): bool
    {
        $sqlState = $e->getCode();
        $errorCode = $e->errorInfo[1] ?? null;
        $msg = $e->getMessage();

        // Postgres: relation already exists / duplicate object
        if ($sqlState === '42P07' || $sqlState === '42710') return true;

        // MySQL: Duplicate key name
        if ($sqlState === '23000' && $errorCode === 1061) return true;

        // SQLite / generic messages
        if (str_contains($msg, 'already exists')) return true;
        if (str_contains($msg, 'Duplicate key name')) return true;
        if (str_contains($msg, 'UNIQUE constraint failed')) return true;

        return false;
    }
};
