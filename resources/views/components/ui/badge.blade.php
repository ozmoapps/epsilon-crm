@props(['variant' => 'neutral'])

@php
    // Core Design System Variants (Strict Mode)
    // Only 4 semantic variants are supported. All other inputs fallback to neutral.
    // 'warning' is intentionally removed to enforce calm UI standards.
    $variants = [
        'neutral' => 'bg-slate-50 text-slate-700 ring-slate-200',
        'info'    => 'bg-sky-50 text-sky-700 ring-sky-200',
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'danger'  => 'bg-rose-50 text-rose-700 ring-rose-200',
    ];

    $classes = $variants[$variant] ?? $variants['neutral'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset ' . $classes]) }}>
    {{ $slot }}
</span>
