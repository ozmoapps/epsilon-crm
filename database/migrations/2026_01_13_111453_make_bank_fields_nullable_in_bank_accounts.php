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
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->change();
            $table->string('branch_name')->nullable()->change();
            $table->string('iban')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            // Cannot easily revert nullable to not null without data loss risk or default values, 
            // but primarily we just want to revert the definition.
            // SQLite restriction might apply (alter column), providing DB agnostic approach is hard for down() here.
            // We usually skip strict down() for nullable changes or specific driver hacks.
            // Leaving empty for safety or basic attempt.
            
            // $table->string('bank_name')->nullable(false)->change(); // Risky
        });
    }
};
