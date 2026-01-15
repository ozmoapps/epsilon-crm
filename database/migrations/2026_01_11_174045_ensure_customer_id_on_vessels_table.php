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
        Schema::table('vessels', function (Blueprint $table) {
            if (!Schema::hasColumn('vessels', 'customer_id')) {
                $table->foreignId('customer_id')
                    ->nullable()
                    ->constrained('customers')
                    ->nullOnDelete();
                
                // Index is automatically added by foreignId + constrained usually, 
                // but checking source code, constrained() calls references which adds FK.
                // explicitly adding index is safe.
                //$table->index('customer_id'); 
                // Actually foreignId creates an unsignedBigInteger column.
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vessels', function (Blueprint $table) {
            if (Schema::hasColumn('vessels', 'customer_id')) {
                // We only drop it if we likely added it, but migrations are tricky with 'if exists'.
                // Ideally we shouldn't drop it if it was there before, but for reversibility of *this* migration:
                // We can't know if it was there before effectively without state.
                // I will leave down empty or comment it out to be safe, OR drop FK and column.
                // Given the instruction is "add if missing", reversal is "remove if added".
                // I'll assume if we roll back, we might want to remove it, BUT checking non-existence in Up means we are unsure.
                // Safer to do nothing in down to avoid data loss on rollback of a "fix" migration.
            }
        });
    }
};
