<?php

echo "--- Verify PR6b3: Tenant Scope & Bypass Audit ---\n";

$root = __DIR__ . '/..';
$scanPaths = [
    'app/Http/Controllers',
    'app/Services',
    'app/Models',
    'app/Listeners',
    'app/Observers',
    'app/Jobs',
    'routes'
];

$bannedTerms = [
    'withoutTenantScope' => 'Found explicit tenant scope bypass',
    'withoutGlobalScope' => 'Found explicit global scope bypass (check context)',
    'withoutGlobalScopes' => 'Found explicit global scopes bypass',
];

// Exemptions (File Path => [Allowed Term => Reason])
$exemptions = [
    'app/Models/Concerns/TenantScoped.php' => [
        'withoutGlobalScope' => 'Required to define the bypass method',
        'withoutTenantScope' => 'Required to define the macro' // If macro used
    ],
    // Add any other KNOWN safe spots (e.g. specialized admin traits)
];

$fail = false;

foreach ($scanPaths as $path) {
    if (!is_dir("$root/$path") && !is_file("$root/$path")) continue;
    
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/$path"));
    
    foreach ($files as $file) {
        if ($file->isDir()) continue;
        if ($file->getExtension() !== 'php') continue;
        
        $relPath = str_replace("$root/", '', $file->getPathname());
        $content = file_get_contents($file->getPathname());
        
        foreach ($bannedTerms as $term => $msg) {
            if (str_contains($content, "$term(")) {
                // Check exemption
                if (isset($exemptions[$relPath]) && isset($exemptions[$relPath][$term])) {
                    echo "[INFO] Exempted usage of $term in $relPath: " . $exemptions[$relPath][$term] . "\n";
                    continue;
                }
                
                echo "[FAIL] $relPath: $msg ($term)\n";
                // Show snippet
                $lines = explode("\n", $content);
                foreach ($lines as $ln => $line) {
                    if (str_contains($line, $term)) {
                        echo "       Line " . ($ln+1) . ": " . trim($line) . "\n";
                    }
                }
                $fail = true;
            }
        }
    }
}

// Check DB::table usage without tenant_id in CustomerLedgerIndexController
// This is a specific check for the 'FIX' identified.
echo "\n--- Checking Specific Fixes ---\n";

// We read CustomerLedgerIndexController and look for DB::table('invoices')
// It should be followed (eventually) by a where tenant_id check OR be whitelisted if proven safe.
// But strictly, we want to ENFORCE adding it.
$clcPath = 'app/Http/Controllers/CustomerLedgerIndexController.php';
$clcContent = file_get_contents("$root/$clcPath");

// Very rough heuristic check: Does it have multiple DB::table calls?
// Does it use TenantContext?
if (!str_contains($clcContent, "TenantContext::class)->id()")) {
    echo "[WARN] CustomerLedgerIndexController does not seem to call TenantContext::id()! (Might use model relations)\n";
}

// We will rely on the "Human Audit" (me) to fix the code, 
// this script mainly polices the 'withoutTenantScope' ban.

if ($fail) {
    echo "\nVERIFY RESULT: FAIL\n";
    exit(1);
} else {
    echo "\nVERIFY RESULT: PASS\n";
    exit(0);
}
