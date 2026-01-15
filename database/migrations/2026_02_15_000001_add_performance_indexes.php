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
        // Add indexes for commonly filtered and joined columns to improve query performance
        // Helper function to check if index exists (SQLite compatible)
        $hasIndex = function ($table, $column) {
            try {
                $indexes = DB::select("PRAGMA index_list({$table})");
                foreach ($indexes as $index) {
                    $indexInfo = DB::select("PRAGMA index_info({$index->name})");
                    foreach ($indexInfo as $info) {
                        if ($info->name === $column) {
                            return true;
                        }
                    }
                }
                return false;
            } catch (\Exception $e) {
                // For MySQL, just try to create and catch the error
                return false;
            }
        };
        
        Schema::table('quotes', function (Blueprint $table) use ($hasIndex) {
            if (!$hasIndex('quotes', 'customer_id')) $table->index('customer_id');
            if (!$hasIndex('quotes', 'vessel_id')) $table->index('vessel_id');
            if (!$hasIndex('quotes', 'status')) $table->index('status');
            if (!$hasIndex('quotes', 'created_at')) $table->index('created_at');
        });

        Schema::table('sales_orders', function (Blueprint $table) use ($hasIndex) {
            if (!$hasIndex('sales_orders', 'customer_id')) $table->index('customer_id');
            if (!$hasIndex('sales_orders', 'quote_id')) $table->index('quote_id');
            if (!$hasIndex('sales_orders', 'status')) $table->index('status');
            if (!$hasIndex('sales_orders', 'created_at')) $table->index('created_at');
        });

        Schema::table('contracts', function (Blueprint $table) use ($hasIndex) {
            if (!$hasIndex('contracts', 'sales_order_id')) $table->index('sales_order_id');
            if (!$hasIndex('contracts', 'status')) $table->index('status');
            if (!$hasIndex('contracts', 'created_at')) $table->index('created_at');
        });

        Schema::table('work_orders', function (Blueprint $table) use ($hasIndex) {
            if (!$hasIndex('work_orders', 'customer_id')) $table->index('customer_id');
            if (!$hasIndex('work_orders', 'vessel_id')) $table->index('vessel_id');
            if (!$hasIndex('work_orders', 'status')) $table->index('status');
            if (!$hasIndex('work_orders', 'created_at')) $table->index('created_at');
        });

        Schema::table('vessels', function (Blueprint $table) use ($hasIndex) {
            if (!$hasIndex('vessels', 'customer_id')) $table->index('customer_id');
        });

        Schema::table('activity_logs', function (Blueprint $table) use ($hasIndex) {
            if (!$hasIndex('activity_logs', 'created_at')) $table->index('created_at');
            if (!$hasIndex('activity_logs', 'actor_id')) $table->index('actor_id');
        });

        // Skip follow_ups - already has indexes
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropIndex(['customer_id']);
            $table->dropIndex(['vessel_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropIndex(['customer_id']);
            $table->dropIndex(['quote_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex(['sales_order_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropIndex(['customer_id']);
            $table->dropIndex(['vessel_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('vessels', function (Blueprint $table) {
            $table->dropIndex(['customer_id']);
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['actor_id']);
        });
    }
};
