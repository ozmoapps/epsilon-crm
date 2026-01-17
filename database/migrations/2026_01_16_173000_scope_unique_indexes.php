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
        $indexes = [
            'quotes' => ['col' => 'quote_no', 'idx' => 'quotes_quote_no_unique'],
            'sales_orders' => ['col' => 'order_no', 'idx' => 'sales_orders_order_no_unique'],
            'invoices' => ['col' => 'invoice_no', 'idx' => 'invoices_invoice_no_unique'],
            'contracts' => ['col' => 'contract_no', 'idx' => 'contracts_contract_no_unique'],
        ];

        foreach ($indexes as $table => $data) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($data) {
                    // Drop global unique
                    $table->dropUnique($data['idx']);
                    // Add scoped unique
                    $table->unique(['tenant_id', $data['col']]);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting would be complex if data duplication exists, skipping strict down for this fix.
    }
};
