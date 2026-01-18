<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Tenant;
use Illuminate\Support\Str;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// --- 1. Environment Safety Check ---
if (!app()->environment(['local', 'testing'])) {
    echo "❌ ERROR: This script can only be run in local or testing environments.\n";
    echo "Current environment: " . app()->environment() . "\n";
    exit(1);
}

echo "🔒 Environment check passed (" . app()->environment() . ")\n";
echo "📊 Starting Database Audit (READ-ONLY)...\n\n";

$report = [];
$report[] = "# Database Audit Report";
$report[] = "Date: " . now()->toDateTimeString();
$report[] = "Environment: " . app()->environment();
$report[] = "Database Driver: " . DB::connection()->getDriverName();
$report[] = "Database Name: " . DB::connection()->getDatabaseName();
$report[] = "";

// --- 2. Table Inventory ---
echo "Running Table Inventory...\n";
$report[] = "## 1. Table Inventory";
$report[] = "| Table | Rows |";
$report[] = "|---|---|";

// Get all tables (Driver agnostic approach)
// Get all tables (Driver agnostic approach)
$tables = [];
$driver = DB::connection()->getDriverName();
$tableNames = [];

try {
    if ($driver === 'sqlite') {
        $results = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        $tableNames = array_map(fn($row) => $row->name, $results);
    } elseif ($driver === 'mysql') {
        $results = DB::select('SHOW TABLES');
        $dbName = DB::connection()->getDatabaseName();
        $key = "Tables_in_{$dbName}";
        $tableNames = array_map(fn($row) => $row->$key, $results);
    } elseif ($driver === 'pgsql') {
        $results = DB::select("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname != 'pg_catalog' AND schemaname != 'information_schema'");
        $tableNames = array_map(fn($row) => $row->tablename, $results);
    } else {
        // Method 2: Schema::getTables() (Laravel 11+) or Doctrine fallback
        if (method_exists(Schema::class, 'getTables')) {
             $tableNames = array_map(fn($t) => $t['name'], Schema::getTables()); // vary by version
        } else {
            echo "Warning: Could not list tables for driver '$driver'.\n";
        }
    }
} catch (\Exception $e) {
    echo "Error listing tables: " . $e->getMessage() . "\n";
}

foreach ($tableNames as $table) {
    if ($table === 'migrations' || $table === 'sqlite_sequence') {
        continue;
    }
    $count = DB::table($table)->count();
    $tables[$table] = $count;
    $report[] = "| `{$table}` | {$count} |";
}
$report[] = "";


// --- 3. Tenant Inventory ---
echo "Running Tenant Inventory...\n";
$report[] = "## 2. Tenant Inventory";

$tenants = Tenant::with('account')->get();
$report[] = "Found **{$tenants->count()}** tenants.";
$report[] = "";

$tenantTables = [
    'customers', 'quotes', 'sales_orders', 'work_orders', 
    'invoices', 'payments', 'vessels', 'products', 
    'warehouses', 'bank_accounts', 'activity_logs', 'follow_ups'
];

foreach ($tenants as $tenant) {
    $report[] = "### Tenant: {$tenant->name} (ID: {$tenant->id})";
    $report[] = "- Slug: `{$tenant->slug}`";
    $report[] = "- Domain: `{$tenant->domain}`";
    $report[] = "- Account ID: `{$tenant->account_id}`";
    $report[] = "";
    $report[] = "**Data Counts:**";
    
    foreach ($tenantTables as $table) {
        if (!Schema::hasTable($table)) continue;
        
        // Check if table has tenant_id
        if (Schema::hasColumn($table, 'tenant_id')) {
            $count = DB::table($table)->where('tenant_id', $tenant->id)->count();
            if ($count > 0) {
                $report[] = "- `{$table}`: {$count}";
            }
        }
    }
    $report[] = "";
}

// --- 4. Tenancy Integrity Checks ---
echo "Running Integrity Checks...\n";
$report[] = "## 3. Integrity & Anomalies";

$anomaliesFound = false;

// 4.1 Null tenant_id checks
$report[] = "### Null tenant_id Checks";
foreach ($tables as $table => $count) {
    if (!Schema::hasColumn($table, 'tenant_id')) continue;
    
    // Skip some tables if they are meant to be global or nullable (checking commonly strictly scoped tables)
    $strictScopedTables = [
        'customers', 'quotes', 'sales_orders', 'work_orders', 'invoices', 
        'payments', 'vessels', 'products', 'warehouses'
    ];
    
    if (in_array($table, $strictScopedTables)) {
        $nullCount = DB::table($table)->whereNull('tenant_id')->count();
        if ($nullCount > 0) {
            $report[] = "- 🔴 **ALERT**: `{$table}` has {$nullCount} records with NULL tenant_id.";
            $anomaliesFound = true;
        }
    }
}
if (!$anomaliesFound) $report[] = "No null tenant_id issues found in strict tables.";

// 4.2 Cross-tenant mismatch (Sample: Order -> Customer)
$report[] = "### Cross-Tenant Mismatches";
$mismatchFound = false;

if (Schema::hasTable('sales_orders') && Schema::hasTable('customers')) {
    $mismatches = DB::table('sales_orders')
        ->join('customers', 'sales_orders.customer_id', '=', 'customers.id')
        ->whereColumn('sales_orders.tenant_id', '!=', 'customers.tenant_id')
        ->count();
        
    if ($mismatches > 0) {
        $report[] = "- 🔴 **Mismatch**: {$mismatches} Sales Orders have different tenant_id than their Customer.";
        $mismatchFound = true;
        $anomaliesFound = true;
    }
}

if (Schema::hasTable('vessels') && Schema::hasTable('customers')) {
    // If vessels are linked to customers (depending on schema, assuming they might share tenant context)
    // Actually vessels usually belong to a customer or are standalone tenant resources.
    // Let's check if vessel tenant_id matches its customer's tenant_id if linked
    if (Schema::hasColumn('vessels', 'customer_id')) {
        $mismatches = DB::table('vessels')
            ->join('customers', 'vessels.customer_id', '=', 'customers.id')
            ->whereColumn('vessels.tenant_id', '!=', 'customers.tenant_id')
            ->count();
            
        if ($mismatches > 0) {
           $report[] = "- 🔴 **Mismatch**: {$mismatches} Vessels have different tenant_id than their Customer.";
           $mismatchFound = true;
           $anomaliesFound = true;
        }
    }
}

if (!$mismatchFound) $report[] = "No direct cross-tenant mismatches detected in checked relations.";


// --- 5. Data Leak / Demo Logic Detection ---
echo "Running Data Leak Detection...\n";
$report[] = "## 4. Leak & Demo Data Detection";
$report[] = "Scanning for patterns: `*.test`, `*@test.com`, `Test Tenant`...";

$leakCounts = 0;

// Tenants
$testTenants = Tenant::where('domain', 'like', '%.test')
    ->orWhere('name', 'like', '%Test%')
    ->orWhere('name', 'like', '%Demo%')
    ->count();

if ($testTenants > 0) {
    $report[] = "- Found **{$testTenants}** tenants matching test/demo patterns.";
    $leakCounts += $testTenants;
}

// Customers
if (Schema::hasTable('customers')) {
    $testCustomers = DB::table('customers')
        ->where('email', 'like', '%@test.com')
        ->orWhere('email', 'like', '%@example.com')
        ->count();
    
    if ($testCustomers > 0) {
        $report[] = "- Found **{$testCustomers}** customers with test emails.";
        $leakCounts += $testCustomers;
    }
}

if ($leakCounts === 0) {
    $report[] = "No obvious demo data patterns found.";
}

// --- 6. Cleanup Recommendations ---
echo "Generating Recommendations...\n";
$report[] = "## 5. Cleanup Recommendations (PR13b)";

$report[] = "Based on the audit, the following actions are recommended for the Garden Cleanup:";

// Determine which tables have data
$dirtyTables = [];
foreach ($tables as $table => $count) {
    if ($count > 0 && !in_array($table, ['migrations', 'users', 'password_reset_tokens', 'sessions', 'jobs', 'failed_jobs', 'telescope_entries', 'telescope_entries_tags', 'telescope_monitoring'])) {
        $dirtyTables[] = $table;
    }
}

$report[] = "### Safe to Truncate (Garden)";
$report[] = "The following tables contain operational data and should be truncated for a fresh seed:";
$report[] = "```json";
$report[] = json_encode($dirtyTables, JSON_PRETTY_PRINT);
$report[] = "```";

$report[] = "### Protected Tables";
$report[] = "These tables likely contain system configuration or permanent accounts and should NOT be truncated usually (unless fully re-seeding):";
$report[] = "- `users` (Admin access)";
$report[] = "- `tenants` (If we want to keep structure, otherwise re-seed)";
$report[] = "- `plans` / `accounts` (Billing structure)";
$report[] = "- `permissions` / `roles`";

// --- Output ---
$outputFile = __DIR__ . '/db_audit_report.md';
file_put_contents($outputFile, implode("\n", $report));

echo "\n✅ Audit Complete! Report saved to: {$outputFile}\n";
echo "Run `cat storage/db_audit_report.md` to view.\n";
