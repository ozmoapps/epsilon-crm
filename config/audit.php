<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Audit Log Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('AUDIT_ENABLED', true),

    'retention_days' => 180,

    // Toggle specific high-volume or sensitive logs if needed
    'log_privacy_violations' => true,
    'log_entitlement_blocks' => true,
    
    // Privacy / PII Settings
    'mask_emails' => true,
];
