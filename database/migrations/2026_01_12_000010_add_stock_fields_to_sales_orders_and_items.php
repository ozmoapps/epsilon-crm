<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_orders', 'stock_posted_at')) {
                $table->dateTime('stock_posted_at')->nullable();
                $table->foreignId('stock_posted_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
                $table->foreignId('stock_posted_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('sales_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_order_items', 'product_id')) {
                $table->foreignId('product_id')->nullable()->after('sales_order_id')->constrained('products')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign(['stock_posted_warehouse_id']);
            $table->dropForeign(['stock_posted_by']);
            $table->dropColumn(['stock_posted_at', 'stock_posted_warehouse_id', 'stock_posted_by']);
        });
    }
};
