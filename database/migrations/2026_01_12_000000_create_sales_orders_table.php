<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vessel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete()->unique();
            $table->string('order_no')->unique();
            $table->string('title');
            $table->string('status')->default('draft');
            $table->string('currency')->default('EUR');
            $table->date('order_date')->default(new Expression('CURRENT_DATE'));
            $table->string('delivery_place')->nullable();
            $table->unsignedSmallInteger('delivery_days')->nullable();
            $table->text('payment_terms')->nullable();
            $table->text('warranty_text')->nullable();
            $table->text('exclusions')->nullable();
            $table->text('notes')->nullable();
            $table->text('fx_note')->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount_total', 14, 2)->default(0);
            $table->decimal('vat_total', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
