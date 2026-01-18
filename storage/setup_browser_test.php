<?php

use App\Models\User;
use App\Models\Tenant;
use App\Models\Account;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

// Ensure Identities
// 1. Platform Admin
$admin = User::firstOrCreate(['email' => 'admin@epsilon.com'], [
    'name' => 'Admin User',
    'password' => Hash::make('password'),
    'is_admin' => true,
]);

// 2. Tenant Admin (Owner of Account)
$tenantAdmin = User::firstOrCreate(['email' => 'tenant_admin@epsilon.com'], [
    'name' => 'Tenant Admin',
    'password' => Hash::make('password'),
    'is_admin' => false,
]);

// 3. Ensure Tenant & Account
// Check if tenant_admin has a tenant
$tenant = $tenantAdmin->tenants()->first();

if (!$tenant) {
    // Get Plan
    $plan = \App\Models\Plan::firstOrCreate(['key' => 'starter'], ['name_tr' => 'Başlangıç']);
    
    // Create Account
    $account = Account::create([
        'name' => 'Browser Test Account',
        'owner_user_id' => $tenantAdmin->id,
        'plan_key' => 'starter', // Start lower so we can upgrade
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    // Create Tenant
    $tenant = Tenant::create([
        'account_id' => $account->id,
        'name' => 'Browser Test Tenant',
        'slug' => 'browser-test-tenant',
    ]);

    $tenant->users()->attach($tenantAdmin, ['role' => 'admin']);
    
    // Set fallback tenant_id for user
    $tenantAdmin->tenant_id = $tenant->id;
    $tenantAdmin->save();
} else {
    // Reset plan to starter for test
    $account = $tenant->account;
    if ($account) {
        $account->plan_key = 'starter';
        $account->save();
    }
}

// Clear existing requests for this account to avoid "Pending request exists" error
if ($tenant->account) {
    \App\Models\PlanChangeRequest::where('account_id', $tenant->account_id)->delete();
} else {
    echo "WARNING: Tenant {$tenant->id} has NO Account linked!\n";
    echo "Tenant Account ID: " . $tenant->account_id . "\n";
    $acc = Account::find($tenant->account_id);
    echo "Account Found: " . ($acc ? 'Yes' : 'No') . "\n";
    
    // Attempt fix
    if ($tenant->account_id && !$acc) {
        $tenant->account_id = null;
        $tenant->save();
    }
    
    if (!$tenant->account_id) {
         // Get default plan
         $plan = \App\Models\Plan::firstOrCreate(['key' => 'starter'], ['name_tr' => 'Başlangıç']);
         
         $account = Account::create([
            'name' => 'Browser Test Account (Fix)',
            'owner_user_id' => $tenantAdmin->id,
            'plan_key' => 'starter',
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);
        $tenant->account_id = $account->id;
        $tenant->save();
        echo "FIXED: Created new account {$account->id} for tenant.\n";
    }
}

echo "Setup Complete.\n";
echo "Admin: admin@epsilon.com\n";
echo "Tenant Admin: tenant_admin@epsilon.com\n";
