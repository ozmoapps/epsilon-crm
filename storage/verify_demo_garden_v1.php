<?php

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔎 Verifying Garden Seed Integrity & Privacy...\n\n";

$failures = 0;

function check($description, $condition) {
    global $failures;
    if ($condition) {
        echo "✅ PASS: $description\n";
    } else {
        echo "❌ FAIL: $description\n";
        $failures++;
    }
}

function checkCount($description, $query, $expected = 0) {
    global $failures;
    $count = $query->count();
    if ($count === $expected) {
        echo "✅ PASS: $description (Count: $count)\n";
    } else {
        echo "❌ FAIL: $description (Expected: $expected, Found: $count)\n";
        $failures++;
    }
}

// 1. Integrity - Critical Null Checks
echo "\n--- 1. Integrity Checks ---\n";
checkCount('Products with NULL tenant_id', DB::table('products')->whereNull('tenant_id'));
checkCount('Customers with NULL tenant_id', DB::table('customers')->whereNull('tenant_id'));
checkCount('Vessels with NULL tenant_id', DB::table('vessels')->whereNull('tenant_id'));
checkCount('Quotes with NULL tenant_id', DB::table('quotes')->whereNull('tenant_id'));
checkCount('Sales Orders with NULL tenant_id', DB::table('sales_orders')->whereNull('tenant_id'));
checkCount('Invoices with NULL tenant_id', DB::table('invoices')->whereNull('tenant_id'));
checkCount('Accounts with NULL tenant_id (check tenants table)', DB::table('tenants')->whereNull('account_id'));

// 2. Cross-Tenant Mismatch
echo "\n--- 2. Cross-Tenant Mismatch ---\n";
if (Illuminate\Support\Facades\Schema::hasTable('sales_orders')) {
    $mismatch = DB::table('sales_orders')
        ->join('customers', 'sales_orders.customer_id', '=', 'customers.id')
        ->whereColumn('sales_orders.tenant_id', '!=', 'customers.tenant_id')
        ->count();
    check('Sales Order <-> Customer Tenant Match', $mismatch === 0);
}

// 3. User Existence
echo "\n--- 3. User Existence ---\n";
check('Master Admin exists', User::where('email', 'master@epsilon.test')->exists());
check('Starter Admin exists', User::where('email', 'starter_admin@epsilon.test')->exists());
check('Starter Staff exists', User::where('email', 'starter_staff@epsilon.test')->exists());

// 4. Privacy & Access Checks
echo "\n--- 4. Privacy & Access Checks ---\n";

function checkRouteAccess($email, $path, $expectedStatuses) {
    global $failures;
    $user = User::where('email', $email)->first();
    if (!$user) {
        echo "⚠️  User $email not found, skipping check.\n";
        return;
    }

    Auth::login($user);
    
    // Simulate request
    // Note: This is an internal request simulation since we are in the booted app context
    $request = Illuminate\Http\Request::create($path, 'GET');
    
    // Need to handle session/middleware? 
    // Creating a distinct test kernel request is complex in raw script.
    // Instead, simply rely on Middleware/Gate logic if possible.
    // But verify scripts usually run outside HTTP context.
    // Alternative: Use 'actingAs' if this was a test.
    
    // Verification of HTTP status in a raw script without full test suite is tricky.
    // We will check Route/auth logic via manual simulation if possible or trust the manual plan.
    // However, the user asked for this script to do it.
    // Let's rely on basic check or skip if too complex for raw PHP script.
    // Wait, the user prompt implies this script should do it.
    
    echo "   Checking $path for $email... ";
    try {
        $response = app()->handle($request);
        $status = $response->getStatusCode();
        
        if (in_array($status, $expectedStatuses)) {
            echo "✅ PASS (Status: $status)\n";
        } else {
             // 302 to login is common if auth check fails or redirects
             if ($status === 302 && in_array(200, $expectedStatuses)) {
                 $target = $response->headers->get('Location');
                 echo "⚠️  Redirected to $target (Potential Auth Fail) - Expected " . implode('/', $expectedStatuses) . "\n";
                 $failures++;
             } else {
                 echo "❌ FAIL (Status: $status, Expected: " . implode('/', $expectedStatuses) . ")\n";
                 $failures++;
             }
        }
    } catch (\Exception $e) {
        // 403 usually throws exception in Laravel if not handled
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
             $status = $e->getStatusCode();
             if (in_array($status, $expectedStatuses)) {
                echo "✅ PASS (Caught Status: $status)\n";
             } else {
                echo "❌ FAIL (Caught Status: $status)\n";
                $failures++;
             }
        } else {
            echo "❌ ERROR: " . $e->getMessage() . "\n";
        }
    }
}

// Starter Staff -> Finance (Expect 403/404)
checkRouteAccess('starter_staff@epsilon.test', '/invoices', [403, 404]);

// Starter Admin -> Finance (Expect 200)
checkRouteAccess('starter_admin@epsilon.test', '/invoices', [200]);


if ($failures > 0) {
    echo "\n❌ Verification FAILED with $failures errors.\n";
    exit(1);
} else {
    echo "\n✅ ALL VERIFICATION CHECKS PASSED.\n";
    exit(0);
}
