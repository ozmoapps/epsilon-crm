<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->date('issued_at')->nullable()->after('status');
            $table->string('contact_name')->nullable()->after('issued_at');
            $table->string('contact_phone')->nullable()->after('contact_name');
            $table->string('location')->nullable()->after('contact_phone');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['issued_at', 'contact_name', 'contact_phone', 'location']);
        });
    }
};
