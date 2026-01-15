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
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            // Matching standard amount precision (decimal:4 for original, but here we match DB standard.
            // Payments table uses decimal(15, 2). LedgerEntry uses decimal(15, 4).
            // We'll use decimal(20, 4) to be safe for all currencies and allocations.
            $table->decimal('amount', 20, 4);
            $table->timestamps();

            // Unique allocation per payment-invoice pair
            $table->unique(['payment_id', 'invoice_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};
