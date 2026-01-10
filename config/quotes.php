<?php

return [
    'prefix' => 'EPS',
    'padding' => 4,
    'default_currency' => 'EUR',
    'default_validity_days' => 5,
    'default_payment_terms' => "Ödeme, teklif tarihindeki toplam bedel üzerinden yapılacaktır.",
    'statuses' => [
        'draft' => 'Taslak',
        'sent' => 'Gönderildi',
        'accepted' => 'Onaylandı',
        'converted' => 'Siparişe Dönüştü',
        'cancelled' => 'İptal',
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
