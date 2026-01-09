<?php

return [
    'prefix' => 'SO',
    'padding' => 4,
    'statuses' => [
        'draft' => 'Taslak',
        'confirmed' => 'Onaylı',
        'completed' => 'Tamamlandı',
        'canceled' => 'İptal',
    ],
    'item_types' => [
        'labor' => 'İşçilik',
        'material' => 'Malzeme',
        'outsourced' => 'Taşeron',
        'other' => 'Diğer',
    ],
    'unit_options' => [
        'adet',
        'saat',
        'm²',
        'lt',
        'set',
        'gün',
        'paket',
    ],
    'vat_rates' => [0, 1, 10, 20],
];
