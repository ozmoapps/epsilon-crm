@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'loading' => false,
    'disabled' => false,
])

@php
    $isDisabled = $disabled || $loading;
    $baseClasses = 'inline-flex items-center justify-center gap-2 rounded-xl font-semibold transition ui-focus disabled:pointer-events-none disabled:opacity-60';

    $variantClasses = [
        'primary' => 'bg-brand-600 text-white shadow-soft hover:bg-brand-500',
        'secondary' => 'bg-white text-slate-700 ring-1 ring-inset ring-slate-200 hover:bg-slate-50',
        'ghost' => 'bg-transparent text-slate-600 hover:bg-slate-100 hover:text-slate-900',
        'danger' => 'bg-rose-600 text-white shadow-soft hover:bg-rose-500',
    ];

    $sizeClasses = [
        'sm' => 'h-9 px-3 text-xs',
        'md' => 'h-10 px-4 text-sm',
        'lg' => 'h-11 px-5 text-sm',
    ];

    $classes = $baseClasses . ' ' . ($variantClasses[$variant] ?? $variantClasses['primary']) . ' ' . ($sizeClasses[$size] ?? $sizeClasses['md']);
@endphp

@if ($attributes->get('href'))
    <a {{ $attributes->merge(['class' => $classes]) }}>
        @if ($loading)
            <svg class="h-4 w-4 animate-spin text-current" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                <path class="opacity-70" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
        @endif
        <span>{{ $slot }}</span>
    </a>
@else
    <button type="{{ $type }}" @disabled($isDisabled) {{ $attributes->merge(['class' => $classes, 'aria-busy' => $loading ? 'true' : 'false']) }}>
        @if ($loading)
            <svg class="h-4 w-4 animate-spin text-current" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                <path class="opacity-70" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
        @endif
        <span>{{ $slot }}</span>
    </button>
@endif
