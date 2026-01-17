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
        $tables = [
            'contract_templates',
            'saved_views',
            'categories',
            'tags', // tags table name might be 'tags' or 'product_tag' - confirmed 'tags' in migration list
            'activity_logs'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->unsignedBigInteger('tenant_id')->nullable()->index();
                });
                // Backfill
                DB::table($table)->update(['tenant_id' => 1]);
            }
        }

        // Sequences - Special Handling (PK change)
        $seqTables = ['quote_sequences', 'sales_order_sequences', 'contract_sequences'];
        foreach ($seqTables as $table) {
            if (Schema::hasTable($table)) {
                if (!Schema::hasColumn($table, 'tenant_id')) {
                    Schema::table($table, function (Blueprint $table) {
                        $table->unsignedBigInteger('tenant_id')->nullable()->after('year');
                    });
                    
                    DB::table($table)->update(['tenant_id' => 1]);

                    Schema::table($table, function (Blueprint $table) {
                        // Drop old primary key (year)
                        $table->dropPrimary();
                        // Add new composite primary key
                        $table->primary(['tenant_id', 'year']);
                    });
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not implementing strict down for rapid dev/fix forward, 
        // but generally would drop columns and restore PKs.
        $tables = [
            'contract_templates',
            'saved_views',
            'categories',
            'tags',
            'activity_logs'
        ];
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $table) {
                     $table->dropColumn('tenant_id');
                });
            }
        }
    }
};
