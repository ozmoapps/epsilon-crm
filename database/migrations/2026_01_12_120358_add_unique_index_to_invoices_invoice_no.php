<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Safe check for existing index to avoid "already exists" error
        $driver = DB::getDriverName();
        $indexName = 'invoices_invoice_no_unique';
        $exists = false;

        if ($driver === 'sqlite') {
            $result = DB::select("SELECT 1 FROM sqlite_master WHERE type='index' AND name=?", [$indexName]);
            $exists = !empty($result);
        } elseif ($driver === 'mysql') {
            $dbName = DB::connection()->getDatabaseName();
            $result = DB::select("SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = 'invoices' AND index_name = ?", [$dbName, $indexName]);
            $exists = !empty($result);
        } elseif ($driver === 'pgsql') {
            $result = DB::select("SELECT 1 FROM pg_indexes WHERE tablename = 'invoices' AND indexname = ?", [$indexName]);
            $exists = !empty($result);
        }

        if ($exists) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) use ($indexName) {
            // Ensure unique index on invoice_no if not exists
            $table->unique('invoice_no', $indexName);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if exists before dropping (best effort)
        $driver = DB::getDriverName();
        $indexName = 'invoices_invoice_no_unique';
        $exists = false;

        if ($driver === 'sqlite') {
            $result = DB::select("SELECT 1 FROM sqlite_master WHERE type='index' AND name=?", [$indexName]);
            $exists = !empty($result);
        } elseif ($driver === 'mysql') {
            $dbName = DB::connection()->getDatabaseName();
            $result = DB::select("SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = 'invoices' AND index_name = ?", [$dbName, $indexName]);
            $exists = !empty($result);
        } elseif ($driver === 'pgsql') {
            $result = DB::select("SELECT 1 FROM pg_indexes WHERE tablename = 'invoices' AND indexname = ?", [$indexName]);
            $exists = !empty($result);
        }

        if ($exists) {
            Schema::table('invoices', function (Blueprint $table) use ($indexName) {
                $table->dropUnique($indexName);
            });
        }
    }
};
