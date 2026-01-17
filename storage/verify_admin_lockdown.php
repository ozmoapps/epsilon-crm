<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- Verifying Admin Surface Lockdown (PR3C6A) ---\n";

// Configuration
$rootDomain = 'localhost'; // Assuming localhost is root in this env
$tenantDomain = 'tenant-a.test';

// 1. Setup Tenant with Domain
$tenant = Tenant::firstOrCreate(
    ['name' => 'Lockdown Test Tenant'],
    ['domain' => $tenantDomain, 'is_active' => true]
);
$tenant->domain = $tenantDomain;
$tenant->save();

echo "[OK] Tenant identified: {$tenant->name} ({$tenant->domain})\n";

// 2. Setup Admin User
$admin = User::firstOrCreate(
    ['email' => 'lockdown_admin@test.com'],
    [
        'name' => 'Lockdown Admin',
        'password' => bcrypt('password'),
        'is_admin' => true
    ]
);
echo "[OK] Admin User identified: {$admin->email}\n";

// 3. Test Root Domain Access to /admin/tenants
echo "\n--- Test 1: Root Domain Access ($rootDomain) ---\n";
// Simulate Request
$response = test_request('GET', 'http://'.$rootDomain.'/admin/tenants', $admin);
if ($response->status() === 200) {
    echo "[PASS] Root domain access allowed (Status 200)\n";
} else {
    echo "[FAIL] Root domain access blocked! Status: " . $response->status() . "\n";
}

// 4. Test Tenant Domain Access to /admin/tenants
echo "\n--- Test 2: Tenant Domain Access ($tenantDomain) ---\n";
// Needs 'tenancy.resolve_by_domain' to be true effectively
// We can mock config if needed, but assuming environment supports it.
// We force the Host header.
config(['tenancy.resolve_by_domain' => true]);

$response = test_request('GET', 'http://'.$tenantDomain.'/admin/tenants', $admin);
if ($response->status() === 403) {
    echo "[PASS] Tenant domain access BLOCKED (Status 403) - Correct\n";
} elseif ($response->status() === 200) {
    echo "[FAIL] Tenant domain access ALLOWED! (Leak)\n";
} else {
    echo "[INFO] Unexpected status: " . $response->status() . "\n";
}


function test_request($method, $url, $user) {
    $request = \Illuminate\Http\Request::create($url, $method);
    
    // Mock Session/Auth
    auth()->login($user);
    
    // We need to run the request through the kernel to trigger middleware
    // But simplified manual dispatching might miss global middleware order in raw script
    // So we use Laravel's internal testing helpers if available, or just carefully construct.
    // Given the complexity of middleware stack, using browsing/curl is better for full integration.
    // But here we try to simulate roughly.
    
    // Actually, calling handle() on Kernel is best.
    $kernel = app()->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle($request);
    
    return $response;
}
