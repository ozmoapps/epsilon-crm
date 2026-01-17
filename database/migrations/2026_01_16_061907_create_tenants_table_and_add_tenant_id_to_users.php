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
        // 1. Tenants tablosunu oluştur
        if (!Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('domain')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Default Tenant Oluştur (Idempotent)
        if (DB::table('tenants')->count() === 0) {
            DB::table('tenants')->insert([
                'name' => 'Varsayılan Firma',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $defaultTenantId = DB::table('tenants')->first()->id;

        // 3. Users tablosuna tenant_id ekle
        if (!Schema::hasColumn('users', 'tenant_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
                $table->index('tenant_id');
            });
        }

        // 4. Backfill: tenant_id'si olmayan kullanıcıları varsayılan firmaya ata
        DB::table('users')->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'tenant_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }

        Schema::dropIfExists('tenants');
    }
};
