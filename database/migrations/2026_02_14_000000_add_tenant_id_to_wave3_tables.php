<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $tables = [
        'quote_items' => ['parent' => 'quotes', 'fk' => 'quote_id'],
        'sales_order_items' => ['parent' => 'sales_orders', 'fk' => 'sales_order_id'],
        'work_order_items' => ['parent' => 'work_orders', 'fk' => 'work_order_id'],
        'work_order_photos' => ['parent' => 'work_orders', 'fk' => 'work_order_id'],
        'work_order_updates' => ['parent' => 'work_orders', 'fk' => 'work_order_id'],
        'work_order_progress' => ['parent' => 'work_orders', 'fk' => 'work_order_id'],
        'stock_movements' => ['parent' => 'warehouses', 'fk' => 'warehouse_id'],
        'payment_allocations' => ['parent' => 'payments', 'fk' => 'payment_id'],
    ];

    public function up(): void
    {
        foreach ($this->tables as $table => $config) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'tenant_id')) {
                
                // 1. Add Column
                Schema::table($table, function (Blueprint $table) {
                    $table->unsignedBigInteger('tenant_id')->nullable()->after('id')->index();
                });

                // 2. Backfill (Subquery for portability)
                // SQLite/MySQL/Postgres compatible subquery update
                $parentTable = $config['parent'];
                $fkInfo = $config['fk'];
                
                DB::statement("
                    UPDATE {$table}
                    SET tenant_id = (
                        SELECT tenant_id 
                        FROM {$parentTable} 
                        WHERE {$parentTable}.id = {$table}.{$fkInfo}
                    )
                    WHERE tenant_id IS NULL
                ");
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table => $config) {
            if (Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('tenant_id');
                });
            }
        }
    }
};
