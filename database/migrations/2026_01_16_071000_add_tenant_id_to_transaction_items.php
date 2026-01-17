<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $map = [
            'quote_items' => 'quote_id',
            'sales_order_items' => 'sales_order_id',
            'work_order_items' => 'work_order_id',
            'work_order_photos' => 'work_order_id',
            'work_order_updates' => 'work_order_id', // Assuming updates table name
            'invoice_lines' => 'invoice_id',
            'payment_allocations' => 'payment_id',
            'sales_order_shipments' => 'sales_order_id',
            'sales_order_shipment_lines' => 'sales_order_shipment_id',
            'sales_order_returns' => 'sales_order_id',
            'sales_order_return_lines' => 'sales_order_return_id',
            'work_order_progress' => 'work_order_id', // If separate from updates
        ];
        
        // Find default tenant for orphaned records (fallback)
        $defaultTenantId = DB::table('tenants')->where('name', 'Varsayılan Firma')->value('id');
        if (!$defaultTenantId) {
            $defaultTenantId = DB::table('tenants')->insertGetId([
                'name' => 'Varsayılan Firma', 
                'created_at' => now(), 
                'updated_at' => now()
            ]);
        }

        foreach ($map as $table => $foreignKey) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->foreignId('tenant_id')->nullable()->after('id')->index();
                });

                // Backfill from parent
                // Get parent table from foreign key convention (e.g. quote_id -> quotes)
                $parentTable = \Illuminate\Support\Str::plural(str_replace('_id', '', $foreignKey));
                
                if (Schema::hasTable($parentTable) && Schema::hasColumn($parentTable, 'tenant_id')) {
                    DB::statement("
                        UPDATE {$table}
                        SET tenant_id = (SELECT tenant_id FROM {$parentTable} WHERE {$parentTable}.id = {$table}.{$foreignKey})
                        WHERE tenant_id IS NULL AND {$foreignKey} IS NOT NULL
                    ");
                }
                
                // Fallback
                DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'quote_items', 'sales_order_items', 'work_order_items', 'work_order_photos', 
            'work_order_updates', 'invoice_lines', 'payment_allocations', 
            'sales_order_shipments', 'sales_order_shipment_lines', 
            'sales_order_returns', 'sales_order_return_lines', 'work_order_progress'
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
