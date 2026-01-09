<?php

return [
    'prefix' => 'EPS',
    'padding' => 4,
    'default_currency' => 'EUR',
    'default_validity_days' => 15,
    'statuses' => [
        'draft' => 'Taslak',
        'sent' => 'Gönderildi',
        'accepted' => 'Onaylandı',
        'rejected' => 'Reddedildi',
        'expired' => 'Süresi Doldu',
        'canceled' => 'İptal',
    ],
    'item_types' => [
        'labor' => 'İşçilik',
        'material' => 'Malzeme',
        'outsourced' => 'Taşeron',
        'other' => 'Diğer',
    ],
    'currency_symbols' => [
        'EUR' => '€',
        'TRY' => '₺',
        'USD' => '$',
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
