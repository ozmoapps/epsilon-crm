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
        Schema::create('saved_views', function (Blueprint $table) {
            $table->id();
            $table->string('scope'); // 'quotes','sales_orders','contracts','work_orders'
            $table->string('name');
            $table->json('query');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_shared')->default(false);
            $table->timestamps();

            $table->index(['scope', 'user_id']);
            $table->index('is_shared');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_views');
    }
};
