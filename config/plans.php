<?php

return [
    'starter' => [
        'name' => 'Başlangıç',
        'tenant_limit' => 1,
        'user_limit' => 1,
    ],
    'team' => [
        'name' => 'Ekip',
        'tenant_limit' => 4,
        'user_limit' => 4,
    ],
    'enterprise' => [
        'name' => 'Kurumsal',
        'tenant_limit' => null, // Sınırsız
        'user_limit' => null,   // Sınırsız
    ],
];
