<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Payment;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('original_amount', 18, 4)->nullable()->after('amount');
            $table->char('original_currency', 3)->nullable()->after('original_amount');
            $table->decimal('fx_rate', 18, 8)->nullable()->after('original_currency');
        });

        // Backfill Logic (DB Agnostic)
        // Using chunk to handle potential large datasets memory-efficiently
        if (Payment::count() > 0) {
            Payment::with('invoice')->chunk(100, function ($payments) {
                foreach ($payments as $payment) {
                    // Safe default if invoice is missing (should not happen due to FK)
                    $currency = $payment->invoice->currency ?? 'EUR';
                    
                    $payment->update([
                        'original_amount' => $payment->amount,
                        'original_currency' => $currency,
                        'fx_rate' => 1.0,
                    ]);
                }
            });
        }

        // Now make them non-nullable after backfill
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('original_amount', 18, 4)->nullable(false)->change();
            $table->char('original_currency', 3)->nullable(false)->change();
            // fx_rate can remain nullable logically if we assume same-currency doesn't need it, 
            // but plan said "fx_rate = 1" for same currency. Let's enforce it as nullable(false) or keep default 1?
            // Plan says "fx_rate zorunlu (>0)". So let's make it not null.
            $table->decimal('fx_rate', 18, 8)->nullable(false)->default(1.0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['original_amount', 'original_currency', 'fx_rate']);
        });
    }
};
