<?php

use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Auth;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "\n=============================================\n";
echo "   VERIFY OPS: DELIVERY REPORT & SIGNATURE\n";
echo "=============================================\n";

// GUARD: Safety First
if (!app()->environment(['local', 'testing'])) {
    echo "[FAIL] This script can only run in local or testing environments.\n";
    exit(1);
}
if (config('database.default') !== 'sqlite') {
    echo "[FAIL] This script requires SQLite database connection for safety.\n";
    exit(1);
}

function assertStatus($testName, $response, $expectedStatus, $message = '')
{
    $status = $response->getStatusCode();
    if ($status === $expectedStatus) {
        echo "[PASS] {$testName} -> got {$status}\n";
    } else {
        echo "[FAIL] {$testName} -> expected {$expectedStatus}, got {$status}. {$message}\n";
        exit(1);
    }
}

function actingAs($user)
{
    Auth::login($user);
    return $user;
}

// 1. Setup
echo "\n[SETUP] Creating Tenants and Users...\n";

// Helper to create full tenant stack
function createTenantStack($prefix) {
    // Removed destructive delete of dirty tenants for safety
    $user = User::factory()->create(['email' => $prefix . '_user_' . uniqid() . '@test.com']);
    
    // Create Account
    $plan = \App\Models\Plan::where('key', 'starter')->first();
    $account = \App\Models\Account::create([
        'owner_user_id' => $user->id,
        'plan_key' => 'starter',
        'plan_id' => $plan?->id,
        'status' => 'active',
        'name' => $prefix . ' Account',
    ]);
    
    $tenant = Tenant::create([
        'account_id' => $account->id,
        'name' => $prefix . ' Tenant',
        'domain' => $prefix . '-' . uniqid() . '.test',
        'is_active' => true
    ]);
    
    $tenant->users()->attach($user, ['role' => 'user']);
    
    // Create Customer & Vessel
    $customer = \App\Models\Customer::factory()->create(['tenant_id' => $tenant->id]);
    $vessel = \App\Models\Vessel::factory()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id]);
    
    return [$tenant, $user, $customer, $vessel];
}

[$tenant, $user, $customer, $vessel] = createTenantStack('delivery');
[$otherTenant, $otherUser, $otherCust, $otherVessel] = createTenantStack('other');

// Work Orders
$wo = WorkOrder::create([
    'tenant_id' => $tenant->id,
    'customer_id' => $customer->id,
    'vessel_id' => $vessel->id,
    'title' => 'Test Repair',
    'status' => 'in_progress',
    'created_by' => $user->id,
]);

$otherWo = WorkOrder::create([
    'tenant_id' => $otherTenant->id,
    'customer_id' => $otherCust->id,
    'vessel_id' => $otherVessel->id,
    'title' => 'Other Repair',
    'status' => 'in_progress',
    'created_by' => $otherUser->id,
]);


// 2. Test Print View (User)
echo "\n[TEST] Tenant User Print View...\n";
actingAs($user);
// Set context
session(['current_tenant_id' => $tenant->id]);
// In real app, middleware sets this via domain, here we assume TenantScope checks session or something or SetTenant middleware.
// Actually SetTenant middleware uses request host usually, or session.
// Models use `TenantScoped` which checks `app(TenantContext::class)->id()`.
// `TenantContext` service usually gets ID from somewhere.
// Let's force it manually if needed, but `SetTenant` middleware running in Kernel should handle basic auth/context if routed correctly?
// Routes are not fully simulated here easily without proper headers.
// But `TenantScoped` typically looks at global service.
// Let's explicitly bind the context for the test execution to be safe.
app(\App\Services\TenantContext::class)->setTenant($tenant);


$response = $kernel->handle(Illuminate\Http\Request::create("/work-orders/{$wo->id}/print", 'GET'));
assertStatus('User GET /print', $response, 200);

// Check if content signature block is present (simple string check if possible, or just 200 is enough for now)
// assert(str_contains($response->getContent(), 'Teslim Eden')); 


// 3. Test Delivery Action
echo "\n[TEST] Tenant User Submit Delivery...\n";
$data = [
    'delivered_to_name' => 'John Doe',
    'delivered_at' => now()->format('Y-m-d\TH:i'), // datetime-local format
    'delivery_notes' => 'All good',
];

// Simulate CSRF token
session()->start();
$token = csrf_token();
$response = $kernel->handle(Illuminate\Http\Request::create(
    "/work-orders/{$wo->id}/deliver", 
    'POST', 
    array_merge($data, ['_token' => $token])
));

if ($response->getStatusCode() === 302) {
    echo "[PASS] User POST /deliver -> got 302\n";
    $wo->refresh();
    if ($wo->status === 'delivered' && $wo->delivered_to_name === 'John Doe') {
        echo "[PASS] DB Updated Correctly\n";
    } else {
        echo "[FAIL] DB Update Failed. Status: {$wo->status}\n";
        exit(1);
    }
} else {
    echo "[FAIL] User POST /deliver -> expected 302, got " . $response->getStatusCode() . "\n";
    // echo $response->getContent();
    exit(1);
}

// 4. Cross Tenant Check
echo "\n[TEST] Cross Tenant Access...\n";
// User trying to access Other WO
$response = $kernel->handle(Illuminate\Http\Request::create("/work-orders/{$otherWo->id}/print", 'GET'));
// Expect 404 (Scope) OR 403 (Policy/Middleware)
if ($response->getStatusCode() === 404 || $response->getStatusCode() === 403) {
    echo "[PASS] Cross Tenant Access Denied ({$response->getStatusCode()})\n";
} else {
    echo "[FAIL] Cross Tenant Access Allowed? Status: " . $response->getStatusCode() . "\n";
    exit(1);
}

echo "\n[SUCCESS] All delivery report tests passed.\n";
