<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = ['quote_sequences', 'sales_order_sequences', 'contract_sequences'];

        foreach ($tables as $table) {
            // Drop valid table if exists (we will recreate it, backing up data first)
            if (Schema::hasTable($table)) {
                $rows = DB::table($table)->get();
                
                Schema::drop($table);

                Schema::create($table, function (Blueprint $table) {
                    $table->unsignedBigInteger('tenant_id');
                    $table->unsignedSmallInteger('year');
                    $table->unsignedInteger('last_number')->default(0);
                    
                    $table->primary(['tenant_id', 'year']);
                });

                foreach ($rows as $row) {
                    DB::table($table)->insert([
                        'tenant_id' => $row->tenant_id ?? 1,
                        'year' => $row->year,
                        'last_number' => $row->last_number
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse logic needed for fix
    }
};
