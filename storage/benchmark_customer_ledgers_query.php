<?php

use Illuminate\Http\Request;
use App\Http\Controllers\CustomerLedgerIndexController;

echo "--- Customer Ledgers Query Benchmark ---\n\n";

$scenarios = [
    'Default (No Filters)' => [],
    'Currency Filter (EUR)' => ['currency' => 'EUR'],
    'Quick Filter (Overdue)' => ['quick' => 'overdue'],
    'Sort (Open Invoice Desc)' => ['sort' => 'open_invoice_desc'],
    'Sort (Advance Desc)' => ['sort' => 'advance_desc'],
];

$controller = app(CustomerLedgerIndexController::class);

// Login as first user for SavedView::visibleTo scope
$user = \App\Models\User::first();
if ($user) {
    auth()->login($user);
} else {
    echo "WARNING: No user found. SavedView check might fail.\n";
}

foreach ($scenarios as $name => $params) {
    echo "Running: {$name} ... ";
    
    // Create Request
    $request = Request::create('/customer-ledgers', 'GET', $params);
    $request->setUserResolver(function () use ($user) {
        return $user;
    });
    
    // Start Timer
    $start = microtime(true);
    
    // Execute Controller Action (capturing view render overhead too is fine, but mainly query)
    // Actually controller returns View instance, query executes on render or explicit get().
    // The controller calls ->paginate(), so query IS executed for the main list inside controller.
    // But totals aggregation logic runs AFTER paginate(), inside the controller logic (totals loops).
    // So calling index($request) triggers the heavy lifting.
    
    try {
        $view = $controller->index($request);
        
        // Force render to ensure all lazy queries (if any remaining in view) run, 
        // though most heavy stuff is pre-calculated in controller.
        $view->render(); 
        
    } catch (\Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        continue;
    }
    
    $duration = microtime(true) - $start;
    $durationMs = number_format($duration * 1000, 2);
    
    echo "{$durationMs} ms\n";
}

echo "\nDone.\n";
