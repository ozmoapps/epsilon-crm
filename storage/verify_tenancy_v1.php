<?php
require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "--- VERIFY SAAS PHASE 1: TENANT FOUNDATION ---\n";

// 1. Check Table
if (Schema::hasTable('tenants')) {
    echo "[PASS] 'tenants' table exists.\n";
} else {
    echo "[FAIL] 'tenants' table MISSING!\n";
    exit(1);
}

// 2. Check Column
if (Schema::hasColumn('users', 'tenant_id')) {
    echo "[PASS] 'users.tenant_id' column exists.\n";
} else {
    echo "[FAIL] 'users.tenant_id' column MISSING!\n";
    exit(1);
}

// 3. Check Default Tenant
$defaultTenant = Tenant::first();
if ($defaultTenant && $defaultTenant->name === 'Varsayılan Firma') {
    echo "[PASS] Default tenant found: {$defaultTenant->name} (ID: {$defaultTenant->id})\n";
} else {
    echo "[FAIL] Default tenant NOT found or incorrect name.\n";
}

// 4. Check User Assignments (Backfill)
$orphanUsers = User::whereNull('tenant_id')->count();
if ($orphanUsers === 0) {
    echo "[PASS] All users assigned to a tenant (Orphans: 0).\n";
} else {
    echo "[FAIL] Found {$orphanUsers} users without tenant_id!\n";
}

// 5. Check Middleware/Context (Simulation)
// We can't easily test middleware here without a request, but we can check if service class exists
if (class_exists(\App\Services\TenantContext::class)) {
    echo "[PASS] TenantContext service class exists.\n";
} else {
    echo "[FAIL] TenantContext service class MISSING!\n";
}

echo "\n--- VERIFICATION COMPLETE ---\n";
