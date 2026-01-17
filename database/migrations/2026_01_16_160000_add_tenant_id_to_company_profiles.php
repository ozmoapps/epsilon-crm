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
        // 1. Add tenant_id column (nullable first for safe migration)
        if (!Schema::hasColumn('company_profiles', 'tenant_id')) {
            Schema::table('company_profiles', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
                // We won't add foreign key constraint immediately to avoid issues if tenants table is empty or different ecosystem
                // But logically it links to tenants.id
            });
        }

        // 2. Backfill existing records to Default Tenant (ID: 1)
        // This ensures existing data is not lost and is assigned to the "primary" tenant
        DB::table('company_profiles')->whereNull('tenant_id')->update(['tenant_id' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('company_profiles', 'tenant_id')) {
            Schema::table('company_profiles', function (Blueprint $table) {
                $table->dropColumn('tenant_id');
            });
        }
    }
};
