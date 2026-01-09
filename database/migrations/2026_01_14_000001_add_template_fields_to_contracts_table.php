<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('contract_template_id')->nullable()->after('sales_order_id')
                ->constrained('contract_templates')->nullOnDelete();
            $table->longText('rendered_body')->nullable()->after('delivery_terms');
            $table->dateTime('rendered_at')->nullable()->after('rendered_body');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['contract_template_id']);
            $table->dropColumn(['contract_template_id', 'rendered_body', 'rendered_at']);
        });
    }
};
