@props([
    'type' => 'info',
    'title' => null,
    'dismissible' => false,
])

@php
    $styles = [
        'primary' => 'bg-brand-50 text-brand-900 border-brand-200',
        'secondary' => 'bg-slate-50 text-slate-700 border-slate-200',
        'success' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
        'warning' => 'bg-amber-50 text-amber-900 border-amber-200',
        'danger' => 'bg-rose-50 text-rose-800 border-rose-200',
        'info' => 'bg-brand-50 text-brand-900 border-brand-200',
        'light' => 'bg-slate-50 text-slate-700 border-slate-200',
        'dark' => 'bg-slate-200 text-slate-900 border-slate-300',
    ];

    $classes = $styles[$type] ?? $styles['info'];
@endphp

<div x-data="{ open: true }" x-show="open" class="rounded-lg border px-4 py-3 text-sm {{ $classes }}" role="alert">
    <div class="flex items-start gap-3">
        <div class="flex-1">
            @if ($title)
                <p class="font-semibold">{{ $title }}</p>
            @endif
            <div class="{{ $title ? 'mt-1' : '' }}">
                {{ $slot }}
            </div>
        </div>
        @if ($dismissible)
            <button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-current transition hover:bg-black/5 ui-focus" @click="open = false" aria-label="{{ __('Kapat') }}">
                <x-icon.x class="h-4 w-4" />
            </button>
        @endif
    </div>
</div>
