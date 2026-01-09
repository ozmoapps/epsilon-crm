<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('contract_no')->unique();
            $table->string('status')->default('draft');
            $table->date('issued_at')->default(new Expression('CURRENT_DATE'));
            $table->dateTime('signed_at')->nullable();
            $table->string('locale', 5)->default('tr');
            $table->string('currency', 10)->default('EUR');
            $table->string('customer_name');
            $table->string('customer_company')->nullable();
            $table->string('customer_tax_no')->nullable();
            $table->text('customer_address')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->longText('payment_terms')->nullable();
            $table->longText('warranty_terms')->nullable();
            $table->longText('scope_text')->nullable();
            $table->longText('exclusions_text')->nullable();
            $table->longText('delivery_terms')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
