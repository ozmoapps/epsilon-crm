<?php

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$app->bind('request', function () {
    return Illuminate\Http\Request::create('/manage/plan', 'GET', [], [], [], [
        'HTTP_HOST' => '127.0.0.1:8000',
        'REMOTE_ADDR' => '127.0.0.1',
    ]);
});

try {
    // 1. Setup User & Tenant
    $user = User::factory()->create(['is_admin' => false]);
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($user, ['role' => 'admin']);

    // 2. Login
    Auth::login($user);

    // 3. Clear Session (Ensure no pre-existing session)
    session()->forget('current_tenant_id');

    // 4. Handle Request
    $request = Illuminate\Http\Request::create('/manage/plan', 'GET');
    $request->setLaravelSession(session()); // Attach session store
    
    $response = $kernel->handle($request);

    echo "Status: " . $response->getStatusCode() . "\n";
    if ($response->getStatusCode() == 404) {
        echo "HIT 404 Error.\n";
    }

} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
} finally {
    // Cleanup
    if (isset($tenant)) { $tenant->users()->detach(); $tenant->forceDelete(); }
    if (isset($user)) { $user->forceDelete(); }
}
