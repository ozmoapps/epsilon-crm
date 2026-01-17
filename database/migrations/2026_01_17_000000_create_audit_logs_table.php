<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            
            // Core
            $table->string('event_key')->index(); // e.g. support_session.created
            $table->string('severity')->default('info'); // info, warn, critical
            
            // Actor Context
            $table->unsignedBigInteger('actor_user_id')->nullable()->index(); // Users table FK manually handled or loose
            $table->string('actor_type')->nullable(); // platform_admin, tenant_user, system
            
            // Scope Context
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('account_id')->nullable()->index();
            $table->unsignedBigInteger('support_session_id')->nullable()->index();
            
            // Request Context (PII Safe)
            $table->string('route')->nullable(); // route name preferred
            $table->string('method', 10)->nullable();
            $table->string('ip_trunc', 45)->nullable(); // IPv6 max length
            $table->string('user_agent_trunc', 120)->nullable();
            
            // Data
            $table->text('metadata')->nullable(); // SQLite compat (json/text)
            
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamp('created_at')->useCurrent();
            // updated_at not strictly needed for immutable logs, but keeping for standard if needed.
            // Let's stick to just created_at/occurred_at for efficiency unless Eloquent forces it.
            // Eloquent expects updated_at by default. Let's add it or disable in model. 
            // Better to have standard timestamps for less friction.
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
