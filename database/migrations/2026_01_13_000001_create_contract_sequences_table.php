<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_sequences', function (Blueprint $table) {
            $table->unsignedInteger('year')->primary();
            $table->unsignedInteger('last_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_sequences');
    }
};
