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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete(); // or restric? user said prevent delete products used
            $table->foreignId('warehouse_id')->constrained();
            $table->decimal('qty', 10, 2);
            $table->enum('direction', ['in', 'out']);
            $table->string('type'); // manual_in, manual_out, workorder_consume, etc.
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->dateTime('occurred_at')->useCurrent();
            $table->nullableMorphs('reference');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
