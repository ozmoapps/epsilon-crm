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
        // 1. Add nullable slug column (No 'after' for SQLite safety)
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('slug')->nullable();
        });

        // 2. Backfill
        // Use deterministic order to ensure consistent slugs
        \Illuminate\Support\Facades\DB::table('tenants')->orderBy('id')->chunkById(100, function ($tenants) {
            foreach ($tenants as $tenant) {
                $slug = \Illuminate\Support\Str::slug($tenant->name);
                if (empty($slug)) {
                    $slug = 'tenant-' . $tenant->id;
                }
                
                $originalSlug = $slug;
                $count = 1;
                // Simple collision resolution
                while (\Illuminate\Support\Facades\DB::table('tenants')->where('slug', $slug)->where('id', '!=', $tenant->id)->exists()) {
                    $count++;
                    $slug = $originalSlug . '-' . $count;
                }
                
                \Illuminate\Support\Facades\DB::table('tenants')
                    ->where('id', $tenant->id)
                    ->update(['slug' => $slug]);
            }
        });

        // 3. Add Unique Index (Keep column nullable to avoid SQLite alter issues)
        Schema::table('tenants', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Drop index first if needed, but dropColumn usually handles it.
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
