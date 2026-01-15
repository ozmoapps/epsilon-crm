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
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vessel_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // invoice, payment
            $table->morphs('source'); // source_type, source_id
            $table->string('direction'); // debit, credit
            $table->decimal('amount', 15, 4);
            $table->char('currency', 3);
            $table->timestamp('occurred_at');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Idempotency / Logical Unique
            $table->unique(['source_type', 'source_id', 'direction', 'currency'], 'ledger_unique_entry');
            
            // Indexes for filtering
            $table->index(['customer_id', 'created_at']);
            $table->index('vessel_id');
            $table->index('occurred_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
