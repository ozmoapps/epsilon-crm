<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('sales_order_id')->nullable()->after('status')->constrained('sales_orders')->nullOnDelete();
            $table->timestamp('converted_at')->nullable()->after('sales_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['sales_order_id']);
            $table->dropColumn(['sales_order_id', 'converted_at']);
        });
    }
};
