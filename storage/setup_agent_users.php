<?php

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Setting up Agent Users...\n";

// 1. Platform Admin
$adminEmail = 'admin@epsilon.com';
$adminUser = User::firstOrCreate(
    ['email' => $adminEmail],
    [
        'name' => 'Admin User',
        'password' => Hash::make('password'),
        'is_admin' => true,
        'email_verified_at' => now(),
    ]
);

// Ensure is_admin is true if user existed but wasn't admin
if (!$adminUser->is_admin) {
    $adminUser->is_admin = true;
    $adminUser->save();
}
echo "Checked/Created Platform Admin: {$adminEmail} (ID: {$adminUser->id})\n";

// 2. Tenant
$tenantSlug = 'agent-test-tenant';
$tenant = Tenant::firstOrCreate(
    ['slug' => $tenantSlug],
    [
        'name' => 'Agent Test Tenant',
        'domain' => 'agent-test.localhost',
        'is_active' => true,
    ]
);
echo "Checked/Created Tenant: {$tenant->name} (ID: {$tenant->id})\n";


// 3. Tenant Admin
$tenantAdminEmail = 'tenant_admin@epsilon.com';
$tenantAdminUser = User::firstOrCreate(
    ['email' => $tenantAdminEmail],
    [
        'name' => 'Tenant Admin',
        'password' => Hash::make('password'),
        'is_admin' => false,
        'email_verified_at' => now(),
    ]
);
echo "Checked/Created Tenant Admin User: {$tenantAdminEmail} (ID: {$tenantAdminUser->id})\n";

// Attach to tenant as admin
if (!$tenantAdminUser->tenants()->where('tenant_id', $tenant->id)->exists()) {
    $tenantAdminUser->tenants()->attach($tenant->id, ['role' => 'admin']);
    echo " > Attached to Tenant as 'admin'\n";
} else {
    // Ensure role is admin
    $tenantAdminUser->tenants()->updateExistingPivot($tenant->id, ['role' => 'admin']);
}

// 4. Tenant User
$tenantUserEmail = 'tenant_user@epsilon.com';
$tenantUser = User::firstOrCreate(
    ['email' => $tenantUserEmail],
    [
        'name' => 'Tenant User',
        'password' => Hash::make('password'),
        'is_admin' => false,
        'email_verified_at' => now(),
    ]
);
echo "Checked/Created Tenant User: {$tenantUserEmail} (ID: {$tenantUser->id})\n";

// Attach to tenant as user
if (!$tenantUser->tenants()->where('tenant_id', $tenant->id)->exists()) {
    $tenantUser->tenants()->attach($tenant->id, ['role' => 'member']); // Assuming 'member' or 'user' is the role. Using 'member' as it is common.
    echo " > Attached to Tenant as 'member'\n";
} else {
     $tenantUser->tenants()->updateExistingPivot($tenant->id, ['role' => 'member']);
}

echo "\nDone. Use password 'password' for all accounts.\n";
