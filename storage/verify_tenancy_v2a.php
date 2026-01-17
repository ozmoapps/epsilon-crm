<?php
require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\Vessel;

echo "--- VERIFY SAAS PHASE 2A: CUSTOMER & VESSEL PROPAGATION ---\n";

// 1. Column Check
if (Schema::hasColumn('customers', 'tenant_id') && Schema::hasColumn('vessels', 'tenant_id')) {
    echo "[PASS] tenant_id columns exist on customers and vessels.\n";
} else {
    echo "[FAIL] tenant_id columns MISSING!\n";
    exit(1);
}

// 2. Default Tenant ID
$defaultTenantId = DB::table('tenants')->where('name', 'Varsayılan Firma')->value('id');
echo "Default Tenant ID: {$defaultTenantId}\n";

// 3. Backfill Check
$orphanCustomers = Customer::whereNull('tenant_id')->count();
$orphanVessels = Vessel::whereNull('tenant_id')->count();

if ($orphanCustomers === 0 && $orphanVessels === 0) {
    echo "[PASS] No orphan records (Customers: 0, Vessels: 0).\n";
} else {
    echo "[FAIL] Found orphans! Customers: {$orphanCustomers}, Vessels: {$orphanVessels}\n";
}

// 4. Integrity Check (Vessel.customer mismatch)
// Join vessels to customers on customer_id, check if vessel.tenant_id != customer.tenant_id
$mismatches = DB::table('vessels')
    ->join('customers', 'vessels.customer_id', '=', 'customers.id')
    ->whereColumn('vessels.tenant_id', '!=', 'customers.tenant_id')
    ->count();

if ($mismatches === 0) {
    echo "[PASS] Data integrity verified: All vessels match their customer's tenant.\n";
} else {
    echo "[FAIL] Found {$mismatches} vessels belonging to a different tenant than their customer!\n";
}

echo "--- VERIFICATION COMPLETE ---\n";
