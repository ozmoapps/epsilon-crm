<?php

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Tenant;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); // Bootstrap console kernel for services

$app->loadEnvironmentFrom('.env.testing');

function info($msg) {
    echo "  -> " . $msg . PHP_EOL;
}

function error($msg) {
    echo "  !! ERROR: " . $msg . PHP_EOL;
    // Don't exit, just report
}

function make_request($method, $uri, $user = null) {
    global $kernel;

    $server = [
        'REQUEST_METHOD' => $method,
        'REQUEST_URI' => $uri,
        'HTTP_HOST' => 'localhost', 
    ];

    $request = Illuminate\Http\Request::create($uri, $method, [], [], [], $server);

    if ($user) {
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        Auth::login($user);
    }

    return $kernel->handle($request);
}

try {
    echo "\n--- Verify Tenant Layout Whitespace (PR4 v4d8) ---\n";

    // 1. Create/Get Tenant User
    $tenant = Tenant::first();
    if (!$tenant) {
        // Create dummy if needed or fail
        // Assuming tenants exist from previous steps
        $tenant = Tenant::create(['name' => 'Layout Test Tenant', 'domain' => 'layout.test', 'slug' => 'layout-test']);
    }
    
    $user = User::where('email', 'layout_test_user@example.com')->first();
    if (!$user) {
        $user = User::create([
            'name' => 'Layout Test User',
            'email' => 'layout_test_user@example.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant->id
        ]);
        // Attach
        if (!$user->tenants()->where('tenants.id', $tenant->id)->exists()) {
             $user->tenants()->attach($tenant->id, ['role' => 'admin']);
        }
    }
    
    // Set Session Context
    session(['current_tenant_id' => $tenant->id]);

    info("User: {$user->email}, Tenant: {$tenant->name}");

    // 2. Fetch Dashboard
    echo "\n[TEST 1] GET /dashboard ...\n";
    $response = make_request('GET', '/dashboard', $user);
    
    if ($response->getStatusCode() === 200) {
        $content = $response->getContent();
        
        // Basic check: HTML structure
        // Does it contain expected layout markers?
        if (str_contains($content, 'sidebarOpen')) {
             info("SUCCESS: Layout detected.");
        } else {
             error("FAILED: Layout markers missing.");
        }

        // Check for specific problematic classes if known
        if (str_contains($content, 'justify-end') || str_contains($content, 'mt-auto')) {
             info("WARNING: Found 'justify-end' or 'mt-auto' in response content. Verify if this applies to the main container.");
        }
        
        // Dump the beginning of body
        if (preg_match('/<body[^>]*>(.*?)<\/body>/s', $content, $matches)) {
            $bodyContent = substr(trim($matches[1]), 0, 500);
            // echo "Body Start Snippet:\n" . $bodyContent . "\n";
        }

    } else {
        error("FAILED: Dashboard returned {$response->getStatusCode()}.");
        if ($response->getStatusCode() == 403) {
             error("Forbidden. Check access.");
        }
    }

    echo "\nVERIFICATION SCRIPT COMPLETE. Plz run manual browser check for visual gap.\n";

} catch (\Exception $e) {
    error("Exception: " . $e->getMessage());
}
