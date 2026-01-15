@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'loading' => false,
    'disabled' => false,
])

@php
    $isDisabled = $disabled || $loading;
    $baseClasses = 'inline-flex items-center justify-center gap-2 rounded-full font-medium transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 disabled:cursor-not-allowed';

    $variantClasses = [
        'primary' => 'bg-slate-900 text-white shadow-sm hover:bg-slate-800 focus:ring-slate-500',
        'secondary' => 'bg-white text-slate-700 ring-1 ring-inset ring-slate-300 shadow-sm hover:bg-slate-50 focus:ring-slate-300',
        'danger' => 'bg-rose-600 text-white shadow-sm hover:bg-rose-700 focus:ring-rose-500',
        'ghost' => 'bg-transparent text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:ring-slate-300',
        'link' => 'bg-transparent text-slate-900 hover:underline shadow-none p-0 h-auto focus:ring-slate-500',
    ];

    $sizeClasses = [
        'sm' => 'h-8 px-3 text-xs', // Adjusted for rounded-full look
        'md' => 'h-10 px-4 text-sm',
        'lg' => 'h-12 px-6 text-base',
    ];

    // Override size for link variant
    if ($variant === 'link') {
        $sizeClasses = [
            'sm' => 'text-xs',
            'md' => 'text-sm',
            'lg' => 'text-base',
        ];
    }

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
