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
        Schema::create('plan_change_requests', function (Blueprint $table) {
            $table->id();
            
            // Context
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete(); // The tenant context where user clicked request
            
            // Requester
            $table->foreignId('requested_by_user_id')->constrained('users')->cascadeOnDelete();
            
            // Plan Info
            $table->string('current_plan_key');
            $table->string('requested_plan_key');
            $table->text('reason')->nullable();
            
            // Flow
            $table->string('status')->default('pending')->index(); // pending, approved, rejected
            
            // Reviewer
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['account_id', 'status']);
            $table->index(['tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_change_requests');
    }
};
