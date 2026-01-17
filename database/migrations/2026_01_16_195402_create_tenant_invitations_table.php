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
        Schema::create('tenant_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('email')->index();
            $table->string('token_hash', 64);
            $table->string('role')->default('staff');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('accepted_by_user_id')->nullable()->constrained('users');
            $table->timestamps();
            
            // Unique (pending invite limitation per user+tenant)
            // But we might want history, so maybe just index.
            // User requirement: "active invite" rule -> revoke old. 
            // So we can enforce unique on (tenant_id, email) where accepted_at is null? 
            // SQLite doesn't support partial indexes easily in all versions, 
            // safe approach: simple index on email+tenant_id.
            $table->index(['tenant_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_invitations');
    }
};
