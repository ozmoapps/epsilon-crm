<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

use App\Models\User;

// Helper for requests
function createRequest($method, $uri, $user = null) {
    // Create request
    $request = \Illuminate\Http\Request::create($uri, $method);
    
    // Bind session
    $session = app('session')->driver();
    $session->start();
    $request->setLaravelSession($session);
    
    if ($user) {
        auth()->login($user);
    }
    
    return $request;
}

// 1. Create/Find Admin User
echo "Finding Admin User...\n";
$admin = User::where('email', 'admin@epsilon.com')->first();
if (!$admin) {
    $admin = User::where('is_admin', true)->first();
}

if (!$admin) {
    echo "Creating temporary admin user...\n";
    $admin = User::factory()->create([
        'name' => 'Verify Admin',
        'email' => 'verify_admin_' . uniqid() . '@epsilon.test',
        'password' => bcrypt('password'),
        'is_admin' => true,
    ]);
}

echo "Logging in as Admin: {$admin->email}\n";
// Note: Auth::login($admin) here might not stick for the request created separately unless we re-login inside createRequest or middleware picks it up from session. 
// Best to login inside the request setup or ensure session persistence.
// createRequest above does auth()->login($user) which sets it for the app instance.

$routes = [
    'admin.dashboard' => '/admin/dashboard',
    'admin.users.index' => '/admin/users',
    'admin.tenants.index' => '/admin/tenants',
    'admin.accounts.index' => '/admin/accounts',
    'admin.company-profiles.index' => '/admin/company-profiles',
    'admin.currencies.index' => '/admin/currencies',
    'admin.contract-templates.index' => '/admin/contract-templates',
    'admin.audit.index' => '/admin/audit',
    'admin.invitations.index' => '/admin/invitations',
];

$failed = 0;

foreach ($routes as $name => $uri) {
    echo "Testing Route: $name ($uri)... ";
    
    try {
        $request = createRequest('GET', $uri, $admin);
        $response = $kernel->handle($request);
        
        if ($response->getStatusCode() === 200) {
            echo "OK (200)\n";
        } else {
            echo "FAILED ({$response->getStatusCode()})\n";
            echo "Content: " . substr(strip_tags($response->getContent()), 0, 500) . "...\n";
            if ($response->exception) {
                echo "Exception: " . $response->exception->getMessage() . "\n";
            }
            $failed++;
        }
    } catch (\Throwable $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        $failed++;
    }
}

if ($failed === 0) {
    echo "\nALL ADMIN ROUTES PASSED.\n";
    exit(0);
} else {
    echo "\n$failed ROUTES FAILED.\n";
    exit(1);
}
