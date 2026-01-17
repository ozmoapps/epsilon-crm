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
        // 1. Create Plans Table
        if (!Schema::hasTable('plans')) {
            Schema::create('plans', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique(); // starter, pro, master
                $table->string('name_tr');
                $table->integer('tenant_limit')->nullable(); // null = unlimited
                $table->integer('seat_limit')->nullable();   // null = unlimited
                $table->integer('extra_seat_price_cents')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Create Accounts Table (Subscription/Billing Account)
        if (!Schema::hasTable('accounts')) {
            Schema::create('accounts', function (Blueprint $table) {
                $table->id();
                // Owner is the primary contact for billing
                $table->foreignId('owner_user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('plan_id')->constrained('plans');
                $table->string('status')->default('active'); // active, suspended, canceled
                $table->integer('extra_seats_purchased')->default(0);
                
                // Billing fields (Placeholders for now)
                $table->string('billing_provider')->nullable();
                $table->string('billing_customer_id')->nullable();
                $table->string('billing_subscription_id')->nullable();
                
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->timestamps();
            });
        }

        // 3. Create Account Users Table (Access/Role within the Account scope)
        if (!Schema::hasTable('account_users')) {
            Schema::create('account_users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('role')->default('member'); // owner, billing_admin, member
                $table->timestamps();

                $table->unique(['account_id', 'user_id']);
            });
        }

        // 4. Update Tenants Table (Link to Account)
        if (!Schema::hasColumn('tenants', 'account_id')) {
            Schema::table('tenants', function (Blueprint $table) {
                // Nullable initially for Safe Migration/SQLite
                $table->foreignId('account_id')->nullable()->after('id')->constrained('accounts');
            });
        }

        // 5. Deterministic Seed Plans
        $plans = [
            [
                'key' => 'starter',
                'name_tr' => 'Başlangıç',
                'tenant_limit' => 1,
                'seat_limit' => 1,
                'extra_seat_price_cents' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'pro',
                'name_tr' => 'Profesyonel',
                'tenant_limit' => 4,
                'seat_limit' => 4,
                'extra_seat_price_cents' => 5000,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'master',
                'name_tr' => 'Master',
                'tenant_limit' => null, // unlimited
                'seat_limit' => null,   // unlimited
                'extra_seat_price_cents' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('plans')->updateOrInsert(
                ['key' => $plan['key']],
                $plan
            );
        }

        // 6. Backfill Accounts for Existing Tenants
        // Logic: Create 1 Account per Tenant.
        // Owner = Tenant Admin (pivot role=admin) OR Smallest User ID
        // Plan = Starter (Default)
        
        $starterPlan = DB::table('plans')->where('key', 'starter')->first();
        if (!$starterPlan) {
            throw new \Exception("Starter plan not found during migration.");
        }

        $tenantsWithoutAccount = DB::table('tenants')->whereNull('account_id')->get();

        foreach ($tenantsWithoutAccount as $tenant) {
            // Find Owner
            // Try to find an admin in pivot
            $adminUser = DB::table('tenant_user')
                ->where('tenant_id', $tenant->id)
                ->where('role', 'admin') // Assuming role column exists and 'admin' is the value
                ->orderBy('user_id')
                ->first();

            $ownerUserId = $adminUser ? $adminUser->user_id : null;

            if (!$ownerUserId) {
                // Fallback: Smallest user_id in tenant_user
                $anyUser = DB::table('tenant_user')
                    ->where('tenant_id', $tenant->id)
                    ->orderBy('user_id')
                    ->first();
                $ownerUserId = $anyUser ? $anyUser->user_id : null;
            }
            
            if (!$ownerUserId) {
                // Fallback: Assign to Platform Admin (User 1 or first admin)
                $adminUser = DB::table('users')->where('is_admin', true)->orderBy('id')->first();
                $ownerUserId = $adminUser ? $adminUser->id : DB::table('users')->orderBy('id')->value('id');
            }

            // Extreme fallback: If system has NO users at all, we can't create an account.
            if (!$ownerUserId) {
                 continue; 
            }

            // Create Account
            $accountId = DB::table('accounts')->insertGetId([
                'owner_user_id' => $ownerUserId,
                'plan_id' => $starterPlan->id,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create Account User (Owner)
            DB::table('account_users')->updateOrInsert(
                ['account_id' => $accountId, 'user_id' => $ownerUserId],
                ['role' => 'owner', 'created_at' => now(), 'updated_at' => now()]
            );

            // Link Tenant to Account
            DB::table('tenants')->where('id', $tenant->id)->update([
                'account_id' => $accountId
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('tenants', 'account_id')) {
            Schema::table('tenants', function (Blueprint $table) {
                // SQLite constraint drop might be tricky, but standard logic:
                $table->dropForeign(['account_id']);
                $table->dropColumn('account_id');
            });
        }
        
        Schema::dropIfExists('account_users');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('plans');
    }
};
