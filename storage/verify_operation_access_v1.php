<?php
// storage/verify_operation_access_v1.php
// Purpose: Verify Staff Access to Operational Actions (PR9) - Hardened Version
// Usage: php storage/verify_operation_access_v1.php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Tenant;
use App\Models\SalesOrder;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

// Global Request Dummy
$app->instance('request', Request::create('/', 'GET'));

// 1. Guard: Local & SQLite Only
if (!app()->environment('local', 'testing')) {
    die("ERROR: This script can only run in local/testing environments.\n");
}
if (DB::connection()->getDriverName() !== 'sqlite') {
    die("ERROR: This script must be run on SQLite to avoid data loss on production DBs.\n");
}

// 2. Setup Data
echo "--- Operations Access Verification (PR9) [Hardened] ---\n";

// Create User (Staff)
$staff = User::firstOrCreate(['email' => 'staff_ops_h@test.com'], [
    'name' => 'Operational Staff H',
    'password' => bcrypt('password'),
]);

// Create User (Admin)
$admin = User::firstOrCreate(['email' => 'admin_ops_h@test.com'], [
    'name' => 'Admin Ops H',
    'password' => bcrypt('password'),
]);

// Create Account & Plan
$plan = \App\Models\Plan::firstOrCreate(['key' => 'basic'], [
    'name' => 'Basic Plan',
    'name_tr' => 'Temel Plan',
    'price' => 0,
    'description' => 'Test plan'
]);

$account = \App\Models\Account::firstOrCreate(['name' => 'Ops Account H'], [
    'status' => 'active', 
    'owner_user_id' => $admin->id,
    'plan_id' => $plan->id
]);

// Create Tenant A
$tenantA = Tenant::firstOrCreate(['domain' => 'opsAh.test'], ['name' => 'Tenant Ops A H', 'account_id' => $account->id]);
if (!$tenantA->account_id) $tenantA->update(['account_id' => $account->id]);

// Create Tenant B (Isolation Test)
$tenantB = Tenant::firstOrCreate(['domain' => 'opsBh.test'], ['name' => 'Tenant Ops B H', 'account_id' => $account->id]);
if (!$tenantB->account_id) $tenantB->update(['account_id' => $account->id]);

// Attach Users
if (!$staff->tenants()->where('tenants.id', $tenantA->id)->exists()) {
    $staff->tenants()->attach($tenantA, ['role' => 'member']);
}
if (!$admin->tenants()->where('tenants.id', $tenantA->id)->exists()) {
    $admin->tenants()->attach($tenantA, ['role' => 'admin']);
}

// Data Setup
// Customer
$customerA = \App\Models\Customer::firstOrCreate(['email' => 'custA_opsh@test.com', 'tenant_id' => $tenantA->id], [
    'name' => 'Cust A Ops H',
    'type' => 'individual'
]);

// Vessel
$vesselA = \App\Models\Vessel::firstOrCreate(['name' => 'Vessel A Ops H', 'tenant_id' => $tenantA->id], [
    'customer_id' => $customerA->id,
    'type' => 'sailing'
]);

// Sales Order (Draft)
$soDraft = SalesOrder::firstOrCreate(['order_no' => 'SO-OPS-H-1', 'tenant_id' => $tenantA->id], [
    'customer_id' => $customerA->id,
    'vessel_id' => $vesselA->id,
    'title' => 'Draft Order for Ops H',
    'status' => 'draft',
    'order_date' => now(),
    'currency' => 'EUR',
    'created_by' => $admin->id
]);

// Tenant B Data (Leak Test)
$soLeak = SalesOrder::firstOrCreate(['order_no' => 'LEAK-OPS-H-1', 'tenant_id' => $tenantB->id], [
    'customer_id' => $customerA->id, // Simplified
    'vessel_id' => $vesselA->id, // Simplified
    'title' => 'Leak Order H',
    'status' => 'draft',
    'order_date' => now(),
    'currency' => 'EUR',
    'created_by' => $admin->id 
]);


// Helper Function (Simulate Request with Session & CSRF)
function run_request($kernel, $user, $tenantId, $method, $uri) {
    // 1. Start Session
    $session = app('session')->driver();
    $session->start();
    
    // 2. Set Tenant Context in Session
    $session->put('current_tenant_id', $tenantId);
    
    // 3. CSRF Token
    $token = bin2hex(random_bytes(16));
    $session->put('_token', $token);
    $session->save();

    // 4. Create Request
    $request = Request::create($uri, $method);
    
    // 5. Attach Session to Request
    $request->setLaravelSession($session);
    
    // 6. Set CSRF Header
    $request->headers->set('X-CSRF-TOKEN', $token);

    // 7. Auth
    if ($user) {
        auth()->login($user);
        $request->setUserResolver(fn() => $user);
    }
    
    return $kernel->handle($request);
}

// 2. Scenario Checks

echo "\n--- 1. Staff Access Checks (Tenant A) ---\n";

// A. Dashboard (GET) -> Expect 200
$resp = run_request($kernel, $staff, $tenantA->id, 'GET', '/dashboard');
echo "Dashboard Status: " . $resp->getStatusCode() . " ";
if ($resp->getStatusCode() === 200) echo "PASS\n"; 
else { 
    echo "FAIL\n"; 
    echo "Response Content: " . substr(strip_tags($resp->getContent()), 0, 500) . "\n";
    exit(1); 
}

// B. Sales Orders Index (GET) -> Expect 200
$resp = run_request($kernel, $staff, $tenantA->id, 'GET', '/sales-orders');
echo "Sales Index Status: " . $resp->getStatusCode() . " ";
if ($resp->getStatusCode() === 200) echo "PASS\n"; else { echo "FAIL\n"; exit(1); }

// C. Operational Action: Confirm SO (PATCH)
// Just Checking Show first
$resp = run_request($kernel, $staff, $tenantA->id, 'GET', "/sales-orders/{$soDraft->id}");
echo "Sales Show Status: " . $resp->getStatusCode() . " ";
$html = $resp->getContent();

if ($resp->getStatusCode() === 200) {
    echo "PASS\n";
    // Check for Action Buttons
    $confirmAction = "sales-orders/{$soDraft->id}/confirm";
    if (strpos($html, $confirmAction) !== false) {
        echo "PASS: 'Confirm' action form found for Staff.\n";
    } else {
        echo "FAIL: 'Confirm' action form NOT found for Staff.\n";
    }
} else { 
    echo "FAIL\n"; 
    echo "Response Content: " . substr(strip_tags($resp->getContent()), 0, 500) . "\n";
    exit(1); 
}


echo "\n--- 2. Finance Restriction Checks (Tenant A) ---\n";

// A. Invoices (GET) -> Expect 403
try {
    $resp = run_request($kernel, $staff, $tenantA->id, 'GET', '/invoices');
    echo "Invoices Status: " . $resp->getStatusCode() . " ";
    if ($resp->getStatusCode() === 403) {
        echo "PASS (Forbidden)\n";
    } else {
        echo "FAIL (Expected 403, got " . $resp->getStatusCode() . ")\n";
        exit(1);
    }
} catch (\Exception $e) {
    if ($e->getStatusCode() === 403) echo "PASS (403 Exception)\n"; 
    else { echo "FAIL (Exception: " . $e->getMessage() . ")\n"; exit(1); }
}


echo "\n--- 3. Tenant Isolation Checks (Tenant A viewing Tenant B data) ---\n";

// Leak Test
try {
    $resp = run_request($kernel, $staff, $tenantA->id, 'GET', "/sales-orders/{$soLeak->id}");
    echo "Leak Access Status: " . $resp->getStatusCode() . " ";
    if ($resp->getStatusCode() === 404) {
        echo "PASS (404 Not Found - Correct Isolation)\n";
    } else {
        echo "FAIL (Leak Accessible! Status: " . $resp->getStatusCode() . ")\n";
        exit(1);
    }
} catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
    echo "PASS (404 Exception - Correct Isolation)\n";
} catch (\Exception $e) {
    echo "FAIL (Exception: " . $e->getMessage() . ")\n";
    exit(1);
}

echo "\n--- 4. Navigation Menu & Dashboard Visibility Checks ---\n";

// A. Check Staff - Should NOT see "Yeni Fatura" (or invoices link) in Dashboard
echo "A. Staff Finance Visibility: ";
$resp = run_request($kernel, $staff, $tenantA->id, 'GET', '/dashboard');
$html = $resp->getContent();

if (strpos($html, route('invoices.index', [], false)) === false && strpos($html, 'Yeni Fatura') === false) {
     echo "PASS (Hidden for Staff)\n";
} else {
     echo "FAIL (Found for Staff)\n";
     $pos = strpos($html, route('invoices.index', [], false));
     $start = max(0, $pos - 100);
     $length = 200;
     echo "Context: " . substr($html, $start, $length) . "\n";
     exit(1);
}

// A2. Check Staff - Should NOT have sensitive Finance Data in JSON (View Source Leak Check)
// openInvoices: [], overdueInvoices: [], etc.
if (strpos($html, 'openInvoices: []') !== false && 
    strpos($html, 'finance: {"invoiced":[],"collected":[]}') !== false) {
    echo "PASS (Finance Data is EMPTY for Staff)\n";
} else {
    echo "FAIL (Finance Data LEAK Found in Staff Dashboard)\n";
    // echo substr($html, 0, 1000); 
    exit(1);
}

// B. Check Admin (Tenant Admin) - SHOULD see "Yeni Fatura" in Dashboard
echo "B. Admin Finance Visibility: ";
$resp = run_request($kernel, $admin, $tenantA->id, 'GET', '/dashboard');
$html = $resp->getContent();

if (strpos($html, 'Yeni Fatura') !== false) {
     echo "PASS (Visible for Admin)\n";
} else {
     echo "FAIL (Hidden for Admin)\n";
     exit(1);
}

echo "\nALL TESTS PASSED.\n";
exit(0);
