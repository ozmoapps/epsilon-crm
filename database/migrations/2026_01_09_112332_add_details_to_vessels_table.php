<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vessels', function (Blueprint $table) {
            $table->string('boat_type')->nullable();
            $table->string('material')->nullable();
            $table->decimal('loa_m', 6, 2)->nullable();
            $table->decimal('beam_m', 6, 2)->nullable();
            $table->decimal('draft_m', 6, 2)->nullable();
            $table->decimal('net_tonnage', 10, 2)->nullable();
            $table->decimal('gross_tonnage', 10, 2)->nullable();
            $table->unsignedSmallInteger('passenger_capacity')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('vessels', function (Blueprint $table) {
            $table->dropColumn([
                'boat_type',
                'material',
                'loa_m',
                'beam_m',
                'draft_m',
                'net_tonnage',
                'gross_tonnage',
                'passenger_capacity',
            ]);
        });
    }
};
