<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->string('status')->default('draft'); // draft, posted, canceled
            $table->timestamp('posted_at')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sales_order_shipment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_shipment_id')->constrained('sales_order_shipments')->cascadeOnDelete();
            // sales_order_item_id is useful for tracking remaining qty but nullable for ad-hoc
            $table->foreignId('sales_order_item_id')->nullable()->constrained('sales_order_items')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products'); // For stock tracking
            $table->string('description')->nullable();
            $table->decimal('qty', 10, 2);
            $table->string('unit')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_shipment_lines');
        Schema::dropIfExists('sales_order_shipments');
    }
};
