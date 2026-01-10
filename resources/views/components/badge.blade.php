@props([
    'variant' => 'neutral',
    'status' => null,
])

@php
    $variants = [
        'neutral' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'info' => 'bg-brand-50 text-brand-700 ring-brand-200',
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'warning' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'danger' => 'bg-rose-50 text-rose-700 ring-rose-200',
    ];

    $statusVariants = [
        // Quotes
        'draft' => 'neutral',
        'sent' => 'info',
        'accepted' => 'success',
        'rejected' => 'danger',
        'converted' => 'success',
        
        // Sales Orders
        'confirmed' => 'info',
        'in_progress' => 'warning',
        'completed' => 'success',
        // 'cancelled' handled below

        // Contracts
        'signed' => 'success',
        'active' => 'success',
        'superseded' => 'neutral',
        'issued' => 'neutral',

        // Work Orders
        'pending' => 'warning',
        
        // Common
        'cancelled' => 'danger',
        'canceled' => 'danger',
    ];

    $key = $status && isset($statusVariants[$status]) ? $statusVariants[$status] : $variant;
    // Fallback if status not found, use variant. If variant not found (e.g. key from statusVariants is 'neutral' which is a key in variants), look it up.
    
    // Logic: 
    // 1. If status is provided, map it to a variant name (e.g. 'draft' -> 'neutral').
    // 2. Resolve classes from $variants using that variant name.

    $resolvedVariant = $key;
    if ($status && isset($statusVariants[$status])) {
        $resolvedVariant = $statusVariants[$status];
    } else {
        $resolvedVariant = $variant;
    }

    $classes = $variants[$resolvedVariant] ?? $variants['neutral'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset ' . $classes]) }}>
    {{ $slot }}
</span>
