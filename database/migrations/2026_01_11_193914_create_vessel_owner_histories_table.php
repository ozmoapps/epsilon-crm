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
        Schema::create('vessel_owner_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vessel_id')->constrained('vessels')->cascadeOnDelete();
            $table->foreignId('old_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('new_customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('changed_at');
            $table->text('note')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['vessel_id', 'changed_at']);
            $table->index('new_customer_id');
            $table->index('old_customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vessel_owner_histories');
    }
};
