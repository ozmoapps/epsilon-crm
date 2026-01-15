<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Str;

class SystemHealthCheckTest extends TestCase
{
    // Do NOT use RefreshDatabase as we want to test against current data state if possible,
    // or use it if we want clean state. Given the request "mevcut yapıyı oku", 
    // it's better to test against existing data or seeded data. 
    // However, usually tests run in a separate DB. 
    // For a "System X-Ray" on a local dev environment, accessing the local DB is often what is wanted 
    // to catch data-specific issues, but standard PHPUnit tests use the env DB.
    public function test_all_get_routes_return_ok()
    {
        $admin = User::where('is_admin', true)->first();
        if (!$admin) {
            $admin = User::factory()->create(['is_admin' => true]);
        }
        
        $this->actingAs($admin);

        $routes = Route::getRoutes();
        $errors = [];
        $checkedCount = 0;

        foreach ($routes as $route) {
            if (!in_array('GET', $route->methods())) {
                continue;
            }

            $uri = $route->uri();

            // Skip internal/debug routes
            if (Str::startsWith($uri, ['_ignition', 'sanctum', 'api/user'])) {
                continue;
            }
            
            // Skip verification/password reset routes that require specific tokens
            if (Str::contains($uri, ['verify-email', 'reset-password', 'confirm-password'])) {
                continue;
            }

            // Prepare URL with parameters
            $url = $this->resolveUrl($route);
            
            if ($url === null) {
                // Could not resolve parameters, skip
                continue;
            }

            try {
                $response = $this->get($url);
                
                if ($response->status() === 500) {
                    $errors[] = [
                        'uri' => $uri,
                        'url' => $url,
                        'status' => $response->status(),
                        'exception' => $response->baseResponse->exception ? $response->baseResponse->exception->getMessage() : 'Unknown error'
                    ];
                }
                
                $checkedCount++;
            } catch (\Exception $e) {
                 $errors[] = [
                    'uri' => $uri,
                    'url' => $url,
                    'status' => 'EXCEPTION',
                    'exception' => $e->getMessage()
                ];
            }
        }

        if (count($errors) > 0) {
            $report = "Found " . count($errors) . " errors:\n";
            foreach ($errors as $error) {
                $report .= "Route: {$error['uri']} (URL: {$error['url']}) -> {$error['status']}\n";
                $report .= "Error: {$error['exception']}\n";
                $report .= "----------------------------------------\n";
            }
            $this->fail($report);
        }

        $this->assertTrue(true, "Checked $checkedCount routes successfully.");
    }

    private function resolveUrl($route)
    {
        $uri = $route->uri();
        $parameterNames = $route->parameterNames();
        
        if (empty($parameterNames)) {
            return '/' . $uri;
        }

        $parameters = [];
        foreach ($parameterNames as $name) {
            $modelName = Str::studly($name);
            // Handle some common deviations or snake_case matching
            if ($name === 'sales_order') $modelName = 'SalesOrder';
            if ($name === 'work_order') $modelName = 'WorkOrder';
            if ($name === 'bank_account') $modelName = 'BankAccount';
            if ($name === 'company_profile') $modelName = 'CompanyProfile';
            if ($name === 'stock_transfer') $modelName = 'StockTransfer';
            
            $modelClass = "App\\Models\\{$modelName}";
            
            if (class_exists($modelClass)) {
                try {
                    $model = $modelClass::first();
                    if ($model) {
                        $parameters[$name] = $model->id;
                    } else {
                        // If no record exists, we can't test this route
                        return null;
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                     // Table might not exist
                     return null;
                }
            } else {
                // Fallback for non-model parameters or unknown models
                return null;
            }
        }

        try {
            return route($route->getName(), $parameters);
        } catch (\Exception $e) {
            return null; // Route might not be named or param mismatch
        }
    }
}
