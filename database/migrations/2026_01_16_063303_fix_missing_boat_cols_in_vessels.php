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
        Schema::table('vessels', function (Blueprint $table) {
            if (!Schema::hasColumn('vessels', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('vessels', 'boat_type')) {
                $table->string('boat_type')->nullable();
            }
            if (!Schema::hasColumn('vessels', 'material')) {
                $table->string('material')->nullable();
            }
            if (!Schema::hasColumn('vessels', 'loa_m')) {
                $table->decimal('loa_m', 6, 2)->nullable();
            }
            if (!Schema::hasColumn('vessels', 'beam_m')) {
                $table->decimal('beam_m', 6, 2)->nullable();
            }
            if (!Schema::hasColumn('vessels', 'draft_m')) {
                $table->decimal('draft_m', 6, 2)->nullable();
            }
            if (!Schema::hasColumn('vessels', 'net_tonnage')) {
                $table->decimal('net_tonnage', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('vessels', 'gross_tonnage')) {
                $table->decimal('gross_tonnage', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('vessels', 'passenger_capacity')) {
                $table->unsignedSmallInteger('passenger_capacity')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vessels', function (Blueprint $table) {
            $columns = [
                'boat_type', 'material', 'loa_m', 'beam_m', 
                'draft_m', 'net_tonnage', 'gross_tonnage', 'passenger_capacity'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('vessels', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
