<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenant_user', function (Blueprint $table) {
            // Standard pivot: no id
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('admin');
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id']);
        });

        // Safe Backfill: DB Level
        DB::table('users')
            ->whereNotNull('tenant_id')
            ->select('id', 'tenant_id')
            ->chunkById(100, function ($users) {
                $inserts = [];
                $now = now();
                foreach ($users as $user) {
                    $inserts[] = [
                        'tenant_id' => $user->tenant_id,
                        'user_id' => $user->id,
                        'role' => 'admin',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                
                // Use InsertIgnore to prevent duplicates if any partial run occurred
                DB::table('tenant_user')->insertOrIgnore($inserts);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_user');
    }
};
