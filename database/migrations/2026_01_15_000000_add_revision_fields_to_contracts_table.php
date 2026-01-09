<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropUnique('contracts_sales_order_id_unique');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('root_contract_id')->nullable()->after('sales_order_id')->constrained('contracts')->nullOnDelete();
            $table->unsignedInteger('revision_no')->default(1)->after('root_contract_id');
            $table->foreignId('superseded_by_id')->nullable()->after('revision_no')->constrained('contracts')->nullOnDelete();
            $table->dateTime('superseded_at')->nullable()->after('superseded_by_id');
            $table->boolean('is_current')->default(true)->after('superseded_at');
            $table->unique(['root_contract_id', 'revision_no']);
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropUnique(['root_contract_id', 'revision_no']);
            $table->dropForeign(['root_contract_id']);
            $table->dropForeign(['superseded_by_id']);
            $table->dropColumn([
                'root_contract_id',
                'revision_no',
                'superseded_by_id',
                'superseded_at',
                'is_current',
            ]);
            $table->unique('sales_order_id');
        });
    }
};
