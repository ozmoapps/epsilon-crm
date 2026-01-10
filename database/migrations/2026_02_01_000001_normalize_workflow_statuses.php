<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('quotes')
            ->whereIn('status', ['rejected', 'expired', 'canceled'])
            ->update(['status' => 'cancelled']);

        DB::table('sales_orders')
            ->where('status', 'canceled')
            ->update(['status' => 'cancelled']);

        DB::table('contracts')
            ->where('status', 'canceled')
            ->update(['status' => 'cancelled']);
    }

    public function down(): void
    {
        //
    }
};
