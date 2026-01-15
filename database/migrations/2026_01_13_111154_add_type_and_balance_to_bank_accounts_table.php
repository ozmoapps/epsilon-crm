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
        if (!Schema::hasTable('bank_accounts')) {
            return;
        }

        Schema::table('bank_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_accounts', 'type')) {
                $table->string('type')->default('bank')->after('name'); // 'bank' or 'cash'
            }
            if (!Schema::hasColumn('bank_accounts', 'opening_balance')) {
                $table->decimal('opening_balance', 18, 2)->default(0)->after('currency_id');
            }
            if (!Schema::hasColumn('bank_accounts', 'opening_balance_date')) {
                $table->date('opening_balance_date')->nullable()->after('opening_balance');
            }
            // Check existing is_active, if not add it
            if (!Schema::hasColumn('bank_accounts', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('opening_balance_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('bank_accounts')) {
            return;
        }

        Schema::table('bank_accounts', function (Blueprint $table) {
            $columns = ['type', 'opening_balance', 'opening_balance_date', 'is_active'];
            // Simplify drop, sqlite might complain if some missing but rare
            $table->dropColumn($columns);
        });
    }
};
