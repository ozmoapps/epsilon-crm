<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Break-Glass / Support Access 
    |--------------------------------------------------------------------------
    |
    | Controls whether tenant admins can grant temporary access to platform admins.
    |
    */

    'break_glass_enabled' => env('PRIVACY_BREAK_GLASS_ENABLED', false),
    
    'break_glass_ttl_minutes' => 60,
];
