<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->after('status')->constrained()->nullOnDelete();
        });

        if (! Schema::hasTable('currencies')) {
            return;
        }

        $currencyMap = DB::table('currencies')->pluck('id', 'code');
        $quotes = DB::table('quotes')->select('id', 'currency')->whereNull('currency_id')->get();

        foreach ($quotes as $quote) {
            $currencyId = $currencyMap[$quote->currency] ?? null;

            if ($currencyId) {
                DB::table('quotes')->where('id', $quote->id)->update([
                    'currency_id' => $currencyId,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('currency_id');
        });
    }
};
