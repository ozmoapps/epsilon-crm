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
        // 1. Default Tenant ID'yi bul
        $defaultTenantId = DB::table('tenants')->where('name', 'Varsayılan Firma')->value('id');
        
        if (! $defaultTenantId) {
            // Eğer yoksa (sıfır kurulum veya hata), temiz kurulum varsayabiliriz veya hata fırlatabiliriz.
            // Fakat önceki migration'da oluşturduk.
            $defaultTenantId = DB::table('tenants')->insertGetId([
                'name' => 'Varsayılan Firma',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Customers tablosuna tenant_id ekle
        if (Schema::hasTable('customers') && !Schema::hasColumn('customers', 'tenant_id')) {
            Schema::table('customers', function (Blueprint $table) use ($defaultTenantId) {
                // Önce nullable ekle
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
                $table->index('tenant_id');
            });

            // Backfill
            DB::table('customers')->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
        }

        // 3. Vessels tablosuna tenant_id ekle
        if (Schema::hasTable('vessels') && !Schema::hasColumn('vessels', 'tenant_id')) {
            Schema::table('vessels', function (Blueprint $table) use ($defaultTenantId) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
                $table->index('tenant_id');
            });

            // Backfill
            DB::table('vessels')->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('vessels', 'tenant_id')) {
            Schema::table('vessels', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }

        if (Schema::hasColumn('customers', 'tenant_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }
    }
};
