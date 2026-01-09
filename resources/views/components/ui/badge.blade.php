@props(['variant' => 'neutral'])

@php
    $variants = [
        'draft' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'confirmed' => 'bg-blue-50 text-blue-700 ring-blue-200',
        'in_progress' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'canceled' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'cancelled' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'neutral' => 'bg-gray-100 text-gray-700 ring-gray-200',
    ];

    $classes = $variants[$variant] ?? $variants['neutral'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset ' . $classes]) }}>
    {{ $slot }}
</span>
