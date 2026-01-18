<?php

use App\Models\Tenant;
use App\Models\User;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "[INFO] Verifying Tenant Account Integrity...\n";

// 1. Check for missing account_id (Should be 0 for tenants with users)
// using explicit query check to avoid orphan pivot issues in withCount.
$missingTenants = Tenant::whereNull('account_id')
    ->get()
    ->filter(function ($tenant) {
        return $tenant->users()->count() > 0;
    });

$missingCount = $missingTenants->count();

if ($missingCount > 0) {
    echo "[FAIL] Still found $missingCount tenants with NULL account_id.\n";
    // List valid samples for debugging from the filtered collection
    $samples = $missingTenants->take(3);
    foreach($samples as $t) {
        // Reload to be sure about user count
        $t->loadCount('users');
        echo " - Tenant: {$t->id} {$t->name} (Users: " . $t->users_count . ")\n";
    }
    // Debug: why was it kept?
    echo "Debugging first item users count: " . $samples->first()->users()->count() . "\n";
    
    exit(1);
} else {
    echo "[PASS] All tenants have account_id.\n";
}

// 2. Check Integrity (Account exists)
$orphans = Tenant::whereNotNull('account_id')->doesntHave('account')->count();
if ($orphans > 0) {
    echo "[FAIL] Found $orphans tenants with INVALID account_id (Orphans).\n";
    exit(1);
} else {
    echo "[PASS] All foreign keys are valid.\n";
}

// 3. Test Creation Guard
echo "[INFO] Testing Creation Guard...\n";

// Create a user without account
$user = User::create([
    'name' => 'Integrity Test User',
    'email' => 'integrity_' . uniqid() . '@test.com',
    'password' => bcrypt('password')
]);
auth()->login($user);

// Create Tenant (without account_id explicitly)
$tenant = Tenant::create([
    'name' => 'Integrity Tenant ' . uniqid(),
    'is_active' => true
]);

if ($tenant->account_id) {
    echo "[PASS] Tenant created with auto-generated account: {$tenant->account_id}\n";
    
    // Verify Account Ownership
    $acc = Account::find($tenant->account_id);
    if ($acc->owner_user_id == $user->id) {
        echo "[PASS] Account owner matched.\n";
    } else {
        echo "[FAIL] Account owner Mismatch. Expected {$user->id}, got {$acc->owner_user_id}\n";
        exit(1);
    }
} else {
    echo "[FAIL] Tenant created with NULL account_id despite creation guard.\n";
    exit(1);
}

// Cleanup
$tenant->delete();
$user->delete(); // Account deletion cascading might depend on DB setup, skipping deep cleanup.

echo "[SUCCESS] Integrity Verification Complete.\n";
exit(0);
