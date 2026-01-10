@props([
    'variant' => 'default',
    'status' => null,
])

@php
    $variants = [
        'default' => 'bg-gray-100 text-gray-700',
        'info' => 'bg-blue-100 text-blue-700',
        'success' => 'bg-emerald-100 text-emerald-700',
        'warning' => 'bg-amber-100 text-amber-700',
        'danger' => 'bg-rose-100 text-rose-700',
    ];

    $statusVariants = [
        'draft' => 'bg-slate-100 text-slate-700',
        'sent' => 'bg-blue-100 text-blue-700',
        'accepted' => 'bg-emerald-100 text-emerald-700',
        'converted' => 'bg-emerald-100 text-emerald-700',
        'cancelled' => 'bg-gray-200 text-gray-700',
    ];

    $classes = $status && isset($statusVariants[$status])
        ? $statusVariants[$status]
        : ($variants[$variant] ?? $variants['default']);
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ' . $classes]) }}>
    {{ $slot }}
</span>
