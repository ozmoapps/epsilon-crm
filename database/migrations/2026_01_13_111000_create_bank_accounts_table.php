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
        if (Schema::hasTable('bank_accounts')) {
            return;
        }

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('bank_name');
            $table->string('branch_name')->nullable();
            $table->string('iban');
            // Check for currencies table for safety, or just nullable unsignedBigInteger
            if (Schema::hasTable('currencies')) {
                $table->foreignId('currency_id')->nullable()->constrained()->nullOnDelete();
            } else {
                $table->unsignedBigInteger('currency_id')->nullable();
            }
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
