<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vessel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('quote_no')->unique();
            $table->string('title');
            $table->string('status')->default('draft');
            $table->string('currency')->default('EUR');
            $table->unsignedSmallInteger('validity_days')->nullable()->default(15);
            $table->unsignedSmallInteger('estimated_duration_days')->nullable();
            $table->text('payment_terms')->nullable();
            $table->text('warranty_text')->nullable();
            $table->text('exclusions')->nullable();
            $table->text('notes')->nullable();
            $table->text('fx_note')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
