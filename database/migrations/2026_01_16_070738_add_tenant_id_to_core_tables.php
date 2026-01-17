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
        $defaultTenantId = DB::table('tenants')->where('name', 'Varsayılan Firma')->value('id');
        
        if (!$defaultTenantId) {
            $defaultTenantId = DB::table('tenants')->insertGetId([
                'name' => 'Varsayılan Firma',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $tablesWithCustomer = ['work_orders', 'quotes', 'contracts', 'sales_orders', 'invoices', 'payments', 'follow_ups'];
        $tablesWithDefault = ['bank_accounts', 'products', 'warehouses', 'ledger_entries'];

        // 1. Tables with customer relationship -> Derive from Customer
        foreach ($tablesWithCustomer as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
                    $table->index('tenant_id');
                });

                // Backfill via Customer
                if (Schema::hasColumn($table, 'customer_id')) {
                    DB::statement("
                        UPDATE {$table}
                        SET tenant_id = (SELECT tenant_id FROM customers WHERE customers.id = {$table}.customer_id)
                        WHERE tenant_id IS NULL AND customer_id IS NOT NULL
                    ");
                }

                // Fallback for remaining nulls
                DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
            }
        }

        // 2. Tables without direct customer relationship -> Default Tenant
        foreach ($tablesWithDefault as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
                    $table->index('tenant_id');
                });

                DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'work_orders', 'quotes', 'contracts', 'sales_orders', 'invoices', 'payments', 'follow_ups',
            'bank_accounts', 'products', 'warehouses', 'ledger_entries'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropForeign(['tenant_id']);
                    $table->dropColumn('tenant_id');
                });
            }
        }
    }
};
