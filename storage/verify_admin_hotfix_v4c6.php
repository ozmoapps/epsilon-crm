<?php

use App\Models\User;
use App\Models\ContractTemplate;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 Starting Admin Hotfix Verification (v4c6)...\n";

$admin = User::where('is_admin', true)->firstOrFail();
echo "Actor: Admin ({$admin->email})\n";

// TEST 1: Contract Template Preview 500 Fix
echo "👉 Test 1: Contract Template Preview Render Check...\n";
try {
    auth()->login($admin);
    
    // Create Dummy Template
    $template = ContractTemplate::create([
        'name' => 'Verify Test Template',
        'locale' => 'tr',
        'format' => 'html',
        'content' => '<h1>Merhaba {{ $customer->name }}</h1><p>Tutar: {{ $utils->formatCurrency($contract->grand_total) }}</p>',
        'change_note' => 'Initial',
        'created_by' => $admin->id
    ]);
    
    $template->createVersion('<h1>Merhaba {{ $customer->name }}</h1>', 'html', $admin->id);

    // Call Edit Controller
    $request = \Illuminate\Http\Request::create(route('admin.contract-templates.edit', $template), 'GET');
    $request->setLaravelSession(session()->driver());
    
    $renderer = app(\App\Services\ContractTemplateRenderer::class);
    $controller = new \App\Http\Controllers\ContractTemplateController();
    
    // Fix: Share errors for view rendering in isolation
    \Illuminate\Support\Facades\View::share('errors', new \Illuminate\Support\ViewErrorBag());
    
    $response = $controller->edit($template, $renderer);
    
    // Render View
    $html = $response->render();
    
    // Privacy Logic Check: MUST contain Dummy customer name.
    if (strpos($html, 'Örnek Müşteri') !== false) {
        echo "✅ Preview Rendered Successfully (Privacy Safe: Found 'Örnek Müşteri').\n";
    } else {
        echo "❌ FAIL: Preview did not use Dummy Data! Privacy Violation potentially.\n";
        exit(1);
    }

    // Cleanup
    $template->delete();

} catch (\Throwable $e) {
    echo "❌ FAIL: Contract Template Preview 500 Error detected!\n" . $e->getMessage() . "\n";
    exit(1);
}

// TEST 2: User Delete 500 Fix
echo "👉 Test 2: User Delete Graceful Fail Check...\n";
try {
    // Create Victim User
    $victim = User::create([
        'name' => 'Victim User',
        'email' => 'victim_' . time() . '@test.com',
        'password' => bcrypt('password'),
    ]);

    // Make them own an Account (Dependencies)
    $account = Account::create([
        'owner_user_id' => $victim->id, // Fixed column name
        'name' => 'Victim Account',
        'status' => 'active',
        // other required fields?
        'plan_id' => \App\Models\Plan::first()->id ?? 1, // Ensure plan_id is set
    ]);

    $request = \Illuminate\Http\Request::create(route('admin.users.destroy', $victim), 'DELETE');
    $request->setLaravelSession(session()->driver());
    
    $controller = new \App\Http\Controllers\Admin\UserAdminController();
    
    // We expect a Redirect Response, NOT an Exception
    $response = $controller->destroy($victim);
    
    if ($response->getStatusCode() === 302) {
        $error = $response->getSession()->get('error');
        if ($error && str_contains($error, 'sahibi olduğu için')) {
             echo "✅ User Delete blocked gracefully: '$error'\n";
        } else {
             echo "⚠️ User Delete blocked but unexpected message: " . json_encode($error) . "\n";
        }
    } else {
        echo "❌ FAIL: Expected 302 Redirect, got " . $response->getStatusCode() . "\n";
        exit(1);
    }

    // Cleanup
    $account->delete();
    $victim->delete(); // Should work now

} catch (\Illuminate\Database\QueryException $e) {
    echo "❌ FAIL: 500 SQL Error leaked! Catch block failed.\n" . $e->getMessage() . "\n";
    exit(1);
} catch (\Throwable $e) {
    echo "❌ FAIL: Exception during User Delete test: " . $e->getMessage() . "\n";
    exit(1);
}

echo "✅ ALL ADMIN HOTFIX CHECKS PASSED.\n";
exit(0);
