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
        // Driver check first
        $driver = DB::getDriverName();
        
        Schema::table('payments', function (Blueprint $table) use ($driver) {
            // 1. Add customer_id safely (avoid duplicate index constraint)
            // User requested explicit safe approach.
            $table->unsignedBigInteger('customer_id')->nullable()->after('invoice_id');
            $table->index('customer_id'); 
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();

            // 2. Make invoice_id nullable using Driver-Specific Safe Strategy
            // Identify FK name. Usually payments_invoice_id_foreign.
            // Skip dropForeign for sqlite to avoid "no such table/constraint" or unsupported errors if not using heavy DBAL
            if ($driver !== 'sqlite') {
                $table->dropForeign(['invoice_id']);
            }
        });

        $table = 'payments';
        $column = 'invoice_id';

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE {$table} MODIFY {$column} BIGINT UNSIGNED NULL");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} DROP NOT NULL");
        } elseif ($driver === 'sqlite') {
            // SQLite safe strategy: rely on Laravel's Schema Builder to handle it (via table rebuild if needed)
            // We do NOT explicitly drop FK before, letting Laravel handle the schema change.
            Schema::table($table, function (Blueprint $table) use ($column) {
                $table->unsignedBigInteger($column)->nullable()->change();
            });
        }

        // Re-add FK (Only for non-SQLite, as SQLite 'change' likely preserved or rebuilt it if valid, 
        // OR we skip to avoid complexity. User said "sqlite: drop/re-add FK işlemlerini SKIP et")
        if ($driver !== 'sqlite') {
            Schema::table('payments', function (Blueprint $table) {
                $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        Schema::table('payments', function (Blueprint $table) use ($driver) {
             $table->dropForeign(['customer_id']);
             $table->dropIndex(['customer_id']);
             $table->dropColumn('customer_id');

             if ($driver !== 'sqlite') {
                 $table->dropForeign(['invoice_id']);
             }
        });

        // Revert invoice_id to NOT NULL
        // For simplicity in hotfix, we skip strict revert of nullable on sqlite to avoid breaking.
        // For MySQL/PG:
        if ($driver !== 'sqlite') {
             // We can re-add the constraint, but converting NULLs to NOT NULL will fail if data exists.
             // We assume clean state or best effort.
             Schema::table('payments', function (Blueprint $table) {
                 $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
             });
        }
    }
};
