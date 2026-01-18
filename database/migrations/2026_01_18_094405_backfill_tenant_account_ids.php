<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant;
use App\Models\Account;
use App\Models\Plan;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Get all tenants with missing account_id
        $tenants = Tenant::whereNull('account_id')->get();

        foreach ($tenants as $tenant) {
            // Find an admin user for this tenant
            $adminUser = $tenant->users()->wherePivot('role', 'admin')->first();
            
            // Fallback: any user if no admin found (unlikely but safe)
            if (!$adminUser) {
                $adminUser = $tenant->users()->first();
            }

            // If still no user, we can't do much automatically -> Skip or Log.
            // Requirement says "Safe migration", so we skip orphans.
            if (!$adminUser) {
                continue;
            }

            // 2. Check if this user owns an account
            $account = Account::where('owner_user_id', $adminUser->id)->first();

            if (!$account) {
                // 3. Create new Account (Starter)
                $plan = Plan::where('key', 'starter')->first();
                $planId = $plan ? $plan->id : null; 
                
                // If plan missing in older DBs, create default or use null (handled by logic later)
                // But Plan table should exist.
                
                $account = Account::create([
                    'owner_user_id' => $adminUser->id,
                    'plan_key' => 'starter',
                    'plan_id' => $planId,
                    'status' => 'active',
                    'name' => $adminUser->name . ' Account',
                ]);

                // Attach owner role
                $account->users()->attach($adminUser->id, ['role' => 'owner']);
            }

            // 4. Update Tenant
            $tenant->account_id = $account->id;
            $tenant->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting this data fix is risky/unnecessary as it fixes integrity.
        // We do nothing.
    }
};
