<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_orders', 'status')) {
                $table->string('status')->default('draft')->after('title');
            }

            if (! Schema::hasColumn('sales_orders', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('status');
            }

            if (! Schema::hasColumn('sales_orders', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('confirmed_at');
            }

            if (! Schema::hasColumn('sales_orders', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('started_at');
            }

            if (! Schema::hasColumn('sales_orders', 'canceled_at')) {
                $table->timestamp('canceled_at')->nullable()->after('completed_at');
            }

            if (! Schema::hasColumn('sales_orders', 'delivery_place')) {
                $table->string('delivery_place')->nullable()->after('order_date');
            }

            if (! Schema::hasColumn('sales_orders', 'delivery_days')) {
                $table->unsignedSmallInteger('delivery_days')->nullable()->after('delivery_place');
            }

            if (! Schema::hasColumn('sales_orders', 'delivery_date')) {
                $table->date('delivery_date')->nullable()->after('delivery_days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            if (Schema::hasColumn('sales_orders', 'delivery_date')) {
                $table->dropColumn('delivery_date');
            }

            if (Schema::hasColumn('sales_orders', 'delivery_days')) {
                $table->dropColumn('delivery_days');
            }

            if (Schema::hasColumn('sales_orders', 'delivery_place')) {
                $table->dropColumn('delivery_place');
            }

            if (Schema::hasColumn('sales_orders', 'canceled_at')) {
                $table->dropColumn('canceled_at');
            }

            if (Schema::hasColumn('sales_orders', 'completed_at')) {
                $table->dropColumn('completed_at');
            }

            if (Schema::hasColumn('sales_orders', 'started_at')) {
                $table->dropColumn('started_at');
            }

            if (Schema::hasColumn('sales_orders', 'confirmed_at')) {
                $table->dropColumn('confirmed_at');
            }

            if (Schema::hasColumn('sales_orders', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
