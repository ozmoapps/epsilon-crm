@php
    $statusVariants = [
        // Neutral (Taslak / İşlemsiz / Eski)
        'draft' => 'neutral',
        'issued' => 'neutral',
        'superseded' => 'neutral',

        // Info (Süreçte / Bekliyor / Gönderildi)
        'sent' => 'info',
        'confirmed' => 'info',
        'in_progress' => 'info',

        // Success (Tamamlandı / Onaylandı / İmzalandı)
        'signed' => 'success',
        'accepted' => 'success',
        'completed' => 'success',
        'contracted' => 'success',
        'converted' => 'success',

        // Danger (İptal)
        'cancelled' => 'danger',
        'canceled' => 'danger',
    ];
@endphp
