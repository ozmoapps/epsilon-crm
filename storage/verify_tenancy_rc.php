<?php

echo "\n=============================================\n";
echo "   EPSILON CRM TENANCY RELEASE GATE (RC)   \n";
echo "=============================================\n\n";

$scripts = [
    'storage/verify_tenancy_v5d_support_session_enforcement.php',
    'storage/verify_tenancy_v5e_negative_leaks.php',
    'storage/verify_tenancy_v5e_platform_admin_privacy.php',
    'storage/verify_tenancy_v6a_wave3_propagation.php',
    'storage/verify_tenancy_v6b1_no_context_denies.php',
    'storage/verify_tenancy_v6b2_aggregate_isolation.php',
    'storage/verify_tenancy_v6b3_bypass_audit.php',
    'storage/verify_plan_v1.php',
    'storage/verify_plan_v2_tenant_create_enforcement.php',
    'storage/verify_plan_v2_seat_limit_enforcement.php',
    'storage/verify_plan_v3_upgrade_request_flow.php',
    'storage/verify_plan_v4_manage_request_list.php',
    'storage/verify_plan_v5_tenant_account_integrity.php',
    'storage/verify_plan_v6_manage_upgrade_request_page.php',
];

$root = __DIR__ . '/..';
$passCount = 0;

foreach ($scripts as $script) {
    echo "[RUN] $script ... ";
    
    $output = [];
    $exitCode = 0;
    $cmd = "php " . $root . "/" . $script . " 2>&1";
    
    exec($cmd, $output, $exitCode);
    
    if ($exitCode === 0) {
        echo "PASS\n";
        $passCount++;
    } else {
        echo "FAIL (Exit Code: $exitCode)\n";
        echo "--------------------------------------------------\n";
        // Show last 10 lines of output for context
        $tail = array_slice($output, -15);
        foreach ($tail as $line) {
            echo "   > $line\n";
        }
        echo "--------------------------------------------------\n";
        echo "\n[CRITICAL] RC Verification FAILED on $script\n";
        echo "Fix the script or the code and re-run.\n";
        exit(1);
    }
}

echo "\n=============================================\n";
echo " VERIFY RC RESULT: PASS (All $passCount scripts passed)\n";
echo "=============================================\n";
exit(0);
