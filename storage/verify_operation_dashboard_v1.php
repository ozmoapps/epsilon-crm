<?php

use App\Models\SalesOrder;
use App\Models\WorkOrder;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext; // Check naming
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

// Bootstrap Application
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Bootstrap Http Kernel
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

// Bind global request for Auth/Observers during setup
$globalRequest = \Illuminate\Http\Request::create('/', 'GET');
$app->instance('request', $globalRequest);
\Illuminate\Support\Facades\Facade::clearResolvedInstance('request');

// 1. Guard: Local & SQLite Only
if (!app()->environment('local', 'testing')) {
    die("ERROR: This script can only run in local/testing environments.\n");
}
if (DB::connection()->getDriverName() !== 'sqlite') {
    die("ERROR: This script must be run on SQLite to avoid data loss on production DBs.\n");
}

echo "--- Operations Dashboard Verification Script ---\n";

// 2. Setup Data
// Create User First (needed for Account Owner)
$userA = User::firstOrCreate(['email' => 'userA@test.com'], [
    'name' => 'User A',
    'password' => bcrypt('password'),
]);

// Create Plan (needed for Account)
$plan = \App\Models\Plan::firstOrCreate(['key' => 'basic'], [
    'name' => 'Basic Plan',
    'name_tr' => 'Temel Plan', // Required
    'price' => 0,
    'description' => 'Test plan'
]);

// Create Account (needs Owner and Plan)
$account = \App\Models\Account::firstOrCreate(['name' => 'Teant Account'], [ 
    'status' => 'active', 
    'owner_user_id' => $userA->id,
    'plan_id' => $plan->id // Required!
]);

// Create Tenants (need Account)
$tenantA = Tenant::firstOrCreate(['domain' => 'tenantA.test'], ['name' => 'Tenant A', 'account_id' => $account->id]);
if (!$tenantA->account_id) {
    $tenantA->update(['account_id' => $account->id]);
}

$tenantB = Tenant::firstOrCreate(['domain' => 'tenantB.test'], ['name' => 'Tenant B', 'account_id' => $account->id]);
if (!$tenantB->account_id) {
    $tenantB->update(['account_id' => $account->id]);
}

if (!$userA->tenants()->where('tenants.id', $tenantA->id)->exists()) {
    $userA->tenants()->attach($tenantA);
}

// 3. Clear Data & Setup
$uniq = uniqid();

echo "Creating Data for Tenant A...\n";
// Manually create data (Models use TenantScoped, so we need to valid tenant set or manually set ids)
// For model creation in script, we can force context or just set IDs.
// Setting IDs explicitly is safer/clearer for data setup in script.

$customerA = \App\Models\Customer::firstOrCreate(['email' => 'custA@test.com'], ['name' => 'Customer A', 'tenant_id' => $tenantA->id]);
$vesselA = \App\Models\Vessel::firstOrCreate(['imo_number' => '1234567'], ['name' => 'Vessel A', 'tenant_id' => $tenantA->id, 'customer_id' => $customerA->id]); 

// SO1
$so1 = SalesOrder::create([
    'tenant_id' => $tenantA->id,
    'customer_id' => $customerA->id,
    'vessel_id' => $vesselA->id,
    'title' => "SO-A-1-$uniq",
    'order_no' => "ORD-A-1-$uniq",
    'order_date' => now(),
    'created_by' => $userA->id,
    'status' => 'confirmed'
]);



// SO2 + WO + Contract
$so2 = SalesOrder::create([
    'tenant_id' => $tenantA->id,
    'customer_id' => $customerA->id,
    'vessel_id' => $vesselA->id,
    'title' => "SO-A-2-$uniq",
    'order_date' => now(),
    'created_by' => $userA->id,
    'status' => 'in_progress'
]);
$wo2 = WorkOrder::create([
    'tenant_id' => $tenantA->id,
    'customer_id' => $customerA->id,
    'vessel_id' => $vesselA->id,
    'title' => "WO-A-2-$uniq",
    'status' => 'started',
    'planned_start_at' => now(),
    'created_by' => $userA->id
]);
$so2->work_order_id = $wo2->id;
$so2->save();
\App\Models\Contract::create([
    'tenant_id' => $tenantA->id,
    'sales_order_id' => $so2->id,
    'contract_no' => "CTR-A-2-$uniq",
    'status' => 'signed',
    'is_current' => true,
    'created_by' => $userA->id,
    'customer_name' => 'Customer A',
    'customer_company' => 'Company A',
    'issued_at' => now(),
]);

// SO3 (Delivered)
$so3 = SalesOrder::create([
    'tenant_id' => $tenantA->id,
    'customer_id' => $customerA->id,
    'vessel_id' => $vesselA->id,
    'title' => "SO-A-3-$uniq",
    'order_date' => now(),
    'created_by' => $userA->id,
    'status' => 'completed'
]);
$wo3 = WorkOrder::create([
    'tenant_id' => $tenantA->id,
    'customer_id' => $customerA->id,
    'vessel_id' => $vesselA->id,
    'title' => "WO-A-3-$uniq",
    'status' => 'delivered',
    'planned_start_at' => now(),
    'created_by' => $userA->id
]);
$so3->work_order_id = $wo3->id;
$so3->save();

// SO4 (Pending Delivery)
$so4 = SalesOrder::create([
    'tenant_id' => $tenantA->id,
    'customer_id' => $customerA->id,
    'vessel_id' => $vesselA->id,
    'title' => "SO-A-4-$uniq",
    'order_date' => now(),
    'created_by' => $userA->id,
    'status' => 'in_progress'
]);
$wo4 = WorkOrder::create([
    'tenant_id' => $tenantA->id,
    'customer_id' => $customerA->id,
    'vessel_id' => $vesselA->id,
    'title' => "WO-A-4-$uniq",
    'status' => 'in_progress',
    'planned_start_at' => now(),
    'created_by' => $userA->id
]);
$so4->work_order_id = $wo4->id;
$so4->save();


echo "Creating Data for Tenant B...\n";
$customerB = \App\Models\Customer::firstOrCreate(['email' => 'custB@test.com'], ['name' => 'Customer B', 'tenant_id' => $tenantB->id]);
$vesselB = \App\Models\Vessel::firstOrCreate(['imo_number' => '1234567-B'], ['name' => 'Vessel B', 'tenant_id' => $tenantB->id, 'customer_id' => $customerB->id]);
$soB = SalesOrder::create([
    'tenant_id' => $tenantB->id,
    'customer_id' => $customerB->id,
    'vessel_id' => $vesselB->id,
    'title' => "SO-B-1-LEAK-TEST-$uniq",
    'order_no' => "LEAK-TEST-$uniq",
    'order_date' => now(),
    'created_by' => $userA->id,
    'status' => 'confirmed'
]);

// 4. Verify via HTTP Kernel (Simulation)
// Helper to simulate request
function run_dashboard_request($user, $tenant, $kernel) {
    // Simulate Request
    $request = \Illuminate\Http\Request::create('/dashboard', 'GET');
    
    // Simulate Session / Auth
    // In a real middleware stack, 'StartSession' and 'Authenticate' run.
    // For manual kernel handle, if we don't have persistent cookies, we might need to rely on actingAs logic 
    // or manual session setup if middleware requires it.
    // However, Laravel's "actingAs" helper usually works with full app tests. Here we are in a script.
    
    // Establishing Tenant Context via Session (Simulating SetTenant behavior for membership)
    $session = app('session')->driver();
    $session->start();
    $session->put('current_tenant_id', $tenant->id);
    $request->setLaravelSession($session);

    // Act as user
    auth()->login($user);
    $request->setUserResolver(fn() => $user);

    // Dispatch
    $response = $kernel->handle($request);
    $content = $response->getContent();
    
    // Terminate (optional but good practice)
    $kernel->terminate($request, $response);
    
    return $content;
}

// Test 1: Admin User
echo "Verifying Dashboard for Tenant A (Admin User)...\n";
// Ensure Admin Role
$userA->tenants()->syncWithoutDetaching([$tenantA->id => ['role' => 'admin']]);

$htmlAdmin = run_dashboard_request($userA, $tenantA, $kernel);

if (!str_contains($htmlAdmin, 'Kesilen Faturalar')) {
    echo "FAIL: Admin should see 'Kesilen Faturalar'\n";
    exit(1);
}
if (!str_contains($htmlAdmin, 'Operasyon Özeti')) {
    echo "FAIL: Admin should see 'Operasyon Özeti'\n";
    exit(1);
}
echo "PASS: Admin view verified.\n";


// Test 2: Staff User
echo "Verifying Dashboard for Tenant A (Staff User)...\n";
$userStaff = \App\Models\User::firstOrCreate(['email' => 'staffA@test.com'], [
    'name' => 'Staff A',
    'password' => bcrypt('password'),
]);
$userStaff->tenants()->syncWithoutDetaching([$tenantA->id => ['role' => 'member']]);

$htmlStaff = run_dashboard_request($userStaff, $tenantA, $kernel);

if (str_contains($htmlStaff, 'Kesilen Faturalar')) {
    echo "FAIL: Staff should NOT see 'Kesilen Faturalar'\n";
    exit(1);
}
if (str_contains($htmlStaff, 'Kasa / Banka')) {
    echo "FAIL: Staff should NOT see 'Kasa / Banka'\n";
    exit(1);
}
if (!str_contains($htmlStaff, 'Operasyon Özeti')) {
    echo "FAIL: Staff SHOULD see 'Operasyon Özeti'\n";
    exit(1);
}
if (!str_contains($htmlStaff, $so1->order_no)) {
     echo "FAIL: Staff SHOULD see SO1 Order No ({$so1->order_no})\n";
     exit(1);
}

echo "PASS: Staff view verified.\n";


// Test 3: Tenant Isolation (Leak Check)
if (str_contains($htmlStaff, 'LEAK-TEST')) {
   echo "FAIL: LEAK DETECTED! Tenant B order found in Tenant A dashboard.\n";
   exit(1);
}
echo "PASS: Tenant Scope Isolation Verified.\n";

echo "\nALL TESTS PASSED.\n";
