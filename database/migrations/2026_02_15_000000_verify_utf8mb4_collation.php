<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite: UTF-8 is the default encoding and it natively supports all Unicode characters including Turkish
        // For MySQL: Ensure utf8mb4_unicode_ci collation
        
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql') {
            $tables = [
                'users',
                'customers',
                'vessels',
                'work_orders',
                'quotes',
                'quote_items',
                'sales_orders',
                'sales_order_items',
                'contracts',
                'contract_templates',
                'contract_template_versions',
                'contract_attachments',
                'contract_deliveries',
                'activity_logs',
                'company_profiles',
                'bank_accounts',
                'currencies',
                'follow_ups',
                'saved_views',
            ];

            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    DB::statement("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                }
            }
        }
        
        // SQLite: No action needed, UTF-8 support is built-in and always enabled
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed - collation change is not destructive
        // and keeping utf8mb4_unicode_ci (MySQL) or UTF-8 (SQLite) is always beneficial
    }
};
