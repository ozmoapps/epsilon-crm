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
        // 1. Add column to customers
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        });

        // 2. Add column to vessels
        Schema::table('vessels', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        });

        // 3. Add column to work_orders
        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        });

        // 4. Backfill data
        $firstUserId = \Illuminate\Support\Facades\DB::table('users')->orderBy('id')->value('id');

        if ($firstUserId) {
            \Illuminate\Support\Facades\DB::table('customers')->whereNull('created_by')->update(['created_by' => $firstUserId]);
            \Illuminate\Support\Facades\DB::table('vessels')->whereNull('created_by')->update(['created_by' => $firstUserId]);
            \Illuminate\Support\Facades\DB::table('work_orders')->whereNull('created_by')->update(['created_by' => $firstUserId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });

        Schema::table('vessels', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });
    }
};
