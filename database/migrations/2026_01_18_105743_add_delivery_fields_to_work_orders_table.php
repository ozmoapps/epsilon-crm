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
        Schema::table('work_orders', function (Blueprint $table) {
            $table->timestamp('delivered_at')->nullable()->after('status');
            $table->unsignedBigInteger('delivered_by')->nullable()->after('delivered_at');
            $table->string('delivered_to_name')->nullable()->after('delivered_by');
            $table->text('delivery_notes')->nullable()->after('delivered_to_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn(['delivered_at', 'delivered_by', 'delivered_to_name', 'delivery_notes']);
        });
    }
};
