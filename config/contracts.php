<?php

return [
    'prefix' => 'CT',
    'padding' => 4,
    'statuses' => [
        'draft' => 'Taslak',
        'sent' => 'Gönderildi',
        'signed' => 'İmzalandı',
        'cancelled' => 'İptal',
    ],
    'locales' => [
        'tr' => 'Türkçe',
        'en' => 'İngilizce',
    ],
    'defaults' => [
        'payment_terms' => "Ödeme, sözleşme tarihinde belirtilen toplam bedel üzerinden yapılacaktır.",
        'warranty_terms' => "İşçilik ve malzeme için sözleşme tarihinden itibaren 12 ay garanti verilir.",
        'scope_text' => "Bu sözleşme kapsamındaki işler, satış siparişinde belirtilen kalemler doğrultusunda gerçekleştirilir.",
        'exclusions_text' => "Sözleşme kapsamı dışında kalan işler ayrıca fiyatlandırılır.",
        'delivery_terms' => "Teslim ve tamamlanma süreleri satış siparişinde belirtilen plan doğrultusunda yürütülür.",
    ],
    'attachments' => [
        'disk' => 'public',
        'max_size_kb' => 10240,
        'mimes' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],
    ],
];
