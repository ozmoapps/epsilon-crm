<?php

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 Starting Platform Overview Dashboard Verification...\n";

// 1. User Resolution
$platformAdmin = User::where('is_admin', true)->firstOrFail();
// Ensure we have a non-admin user
$tenantUser = User::where('is_admin', false)->first();
if (!$tenantUser) {
    // If no non-admin, create or find one.
    // Assuming seeds exist. If not, just warn.
    echo "⚠️ Warning: No non-admin user found. Skipping 403 test.\n";
}

$tenant = Tenant::firstOrFail();

// Test 1: Platform Admin Access
try {
    auth()->login($platformAdmin);
    
    $request = \Illuminate\Http\Request::create(route('admin.dashboard'), 'GET');
    $request->setLaravelSession(session()->driver());
    
    // Simulate Middleware Pipeline for SetTenant check
    // We want to ensure middleware runs (SetTenant) to prove bypass works.
    $middleware = app(\App\Http\Middleware\SetTenant::class);
    // Also TenantContext
    $tenantContext = app(\App\Services\TenantContext::class);
    // Reset Context
    $tenantContext->setTenant(null);

    $response = $middleware->handle($request, function ($req) {
         // Pass through to controller simulation
         // Just return 200 OK
         return new \Illuminate\Http\Response('OK', 200);
    });

    if ($response->getStatusCode() !== 200) {
        echo "❌ FAIL: Platform Admin /admin/dashboard returned " . $response->getStatusCode() . "\n";
        exit(1);
    }
    
    // Assertion: Tenant Context MUST be null
    if ($tenantContext->getTenant()) {
        echo "❌ FAIL: Tenant Context was set! Should be null for admin dashboard.\n";
        exit(1);
    }

    echo "✅ Platform Admin Access (Status 200 + No Tenant Context) verified.\n";

} catch (\Throwable $e) {
    echo "❌ FAIL: Test 1 Exception: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Tenant User Access (Security)
if ($tenantUser) {
    try {
        auth()->login($tenantUser);
        
        // We need to run through route middleware 'admin' which is aliased to CheckAdmin (or EnsureAdmin).
        // Since we can't easily run route middleware in this raw script without full kernel handling, 
        // we can check if the route group has the middleware.
        
        $routes = \Illuminate\Support\Facades\Route::getRoutes();
        $route = $routes->getByName('admin.dashboard');
        
        if (! in_array('admin', $route->middleware())) {
             echo "❌ FAIL: admin.dashboard route missing 'admin' middleware!\n";
             exit(1);
        }
        
        // Simulate middleware fail
        // Manual check of is_admin
        if (!$tenantUser->is_admin) {
            // OK
        } else {
             echo "❌ FAIL: Tenant User IS Admin? Setup error.\n";
             exit(1);
        }

        echo "✅ Tenant User Access Restriction (Middleware Check) verified.\n";

    } catch (\Throwable $e) {
        echo "❌ FAIL: Test 2 Exception: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Test 3: Domain Mode Bypass
try {
    // 1. Snapshot Before
    $beforeCount = AuditLog::where('event_key', 'privacy.violation')->count();

    config(['tenancy.resolve_by_domain' => true]);
    auth()->login($platformAdmin);
    
    $request = \Illuminate\Http\Request::create(route('admin.dashboard'), 'GET');
    // Set Host to Tenant Domain
    $request->headers->set('HOST', $tenant->domain ?? 'foo.com');
    $request->setLaravelSession(session()->driver());
    
    $tenantContext = app(\App\Services\TenantContext::class);
    $tenantContext->setTenant(null); // Reset
    
    $middleware = app(\App\Http\Middleware\SetTenant::class);
    $response = $middleware->handle($request, function ($req) {
        return new \Illuminate\Http\Response('OK', 200);
    });
    
    config(['tenancy.resolve_by_domain' => false]); // Restore config

    if ($response->getStatusCode() !== 200) {
        echo "❌ FAIL: Domain Mode /admin/dashboard returned " . $response->getStatusCode() . "\n";
        exit(1);
    }
    
    if ($tenantContext->getTenant()) {
        echo "❌ FAIL: Tenant Context was set in Domain Mode! Should be null.\n";
        exit(1);
    }
    
    // 2. Snapshot After
    $afterCount = AuditLog::where('event_key', 'privacy.violation')->count();
        
    if ($afterCount > $beforeCount) {
        echo "❌ FAIL: Privacy Violation logged for admin dashboard access!\n";
        exit(1);
    }

    echo "✅ Domain Mode Bypass verified.\n";

} catch (\Throwable $e) {
    echo "❌ FAIL: Test 3 Exception: " . $e->getMessage() . "\n";
    exit(1);
}


// Test 4: Dashboard Query Resilience (Controller Execution)
try {
    // Just execute the controller logic to ensure no crashes
    $controller = new \App\Http\Controllers\Admin\DashboardController();
    $request = \Illuminate\Http\Request::create(route('admin.dashboard'), 'GET');
    
    $response = $controller->__invoke($request); // Should return View
    
    // RENDER CHECK: Critical to catch missing components/variables in Blade
    try {
        $html = $response->render();
        if (strpos($html, 'Platform Genel Bakış') === false) {
             echo "❌ FAIL: Rendered HTML missing expected content.\n";
             exit(1);
        }
        
        // Sidebar Leakage Check (Platform Admin Isolation)
        $forbiddenTerms = [
            'Operasyonlar', 
            'Finans', 
            'Stok & Depo', 
            'Kısayollar', 
            'Teklifler', 
            'Tahsilatlar', 
            'Kasa & Bankalar',
            'Firma:', // Header Label
            'Firma Değiştir', // Dropdown Header
            'tenants.switch', // Form Action
            'Şirket Profili',
            'Para Birimleri',
            'Sözleşmeler',
            'Sözleşme Şablonları',
            'UI Demo',
            'Geliştirici',
            'LOCAL'
        ];
        
        foreach ($forbiddenTerms as $term) {
            if (strpos($html, $term) !== false) {
                 echo "❌ FAIL: Platform sidebar tenant menüleri sızdırıyor: '{$term}' bulundu.\n";
                 exit(1);
            }
        }
        
        echo "✅ View Rendered Successfully (No Blade Exceptions & No Tenant Menu Leakage).\n";
    } catch (\Throwable $e) {
        echo "❌ FAIL: View Render Exception: " . $e->getMessage() . "\n";
        exit(1);
    }
    
    // Check if view data has metrics
    $data = $response->getData(); // View keys
    
    if (!isset($data['metrics']['total_accounts'])) {
         echo "❌ FAIL: Metrics missing in View Data.\n";
         exit(1);
    }
    
    echo "✅ Controller Execution & Metrics verified.\n";
    
    // Additional Assertion: Check for robustness (Partial State Simulation)
    // We can't actually delete tables here, but we can verify the controller keys exist
    // and keys like 'auditSummary' are collections even if empty.
    
    if (!$data['auditSummary'] instanceof \Illuminate\Support\Collection) {
         echo "❌ FAIL: auditSummary should be a Collection.\n";
         exit(1);
    }
    
    if (!$data['planBreakdown'] instanceof \Illuminate\Support\Collection) {
         echo "❌ FAIL: planBreakdown should be a Collection.\n";
         exit(1);
    }
    
    if (!$data['planBreakdown'] instanceof \Illuminate\Support\Collection) {
         echo "❌ FAIL: planBreakdown should be a Collection.\n";
         exit(1);
    }
    
    echo "✅ Robustness Checks (Return Types) verified.\n";
    
    // Test 5: Access Control Middleware (EnsureSupportSession)
    // Attempt to access restricted route as normal Platform Admin (no support session)
    try {
        $restrictedRoute = route('admin.company-profiles.index');
        $req = \Illuminate\Http\Request::create($restrictedRoute, 'GET');
        $req->setLaravelSession(session()->driver());
        $req->setUserResolver(fn() => $platformAdmin);
        
        // We need to run through the middleware stack simulated
        // Since we can't easily simulate route middleware in this script without kernel,
        // we will check if the route has the middleware applied.
        
        $routes = \Illuminate\Support\Facades\Route::getRoutes();
        $route = $routes->getByName('admin.company-profiles.index');
        
        if (!in_array('admin.support', $route->middleware())) {
             echo "❌ FAIL: admin.company-profiles.index route missing 'admin.support' middleware!\n";
             exit(1);
        }
        
        echo "✅ Access Control Middleware (admin.support) Application verified.\n";
        
    } catch (\Throwable $e) {
         echo "❌ FAIL: Test 5 Exception: " . $e->getMessage() . "\n";
         exit(1);
    }

} catch (\Throwable $e) {
    echo "❌ FAIL: Test 4 Exception: " . $e->getMessage() . "\n";
    exit(1);
}

echo "✅ ALL PLATFORM OVERVIEW TESTS PASSED.\n";
exit(0);
