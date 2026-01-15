@props([
    'type' => 'info',
    'title' => null,
    'dismissible' => false,
])

@php
    // Semantic status colors (aligned with badge)
    $styles = [
        'neutral' => 'bg-slate-50/50 text-slate-700 border-slate-200/60',
        'info' => 'bg-brand-50/50 text-brand-700 border-brand-200/60',
        'success' => 'bg-emerald-50/50 text-emerald-700 border-emerald-200/60',
        'warning' => 'bg-amber-50/50 text-amber-700 border-amber-200/60',
        'danger' => 'bg-rose-50/50 text-rose-700 border-rose-200/60',
        
        // Legacy type mappings (backward compatibility)
        'primary' => 'bg-brand-50/50 text-brand-700 border-brand-200/60',
        'secondary' => 'bg-slate-50/50 text-slate-700 border-slate-200/60',
        'light' => 'bg-slate-50/50 text-slate-700 border-slate-200/60',
        'dark' => 'bg-slate-200 text-slate-900 border-slate-300',
    ];

    $classes = $styles[$type] ?? $styles['info'];
@endphp

<div x-data="{ open: true }" x-show="open" class="rounded-xl border px-4 py-3 text-sm {{ $classes }}" role="alert">
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
            <button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-xl text-current transition hover:bg-black/5 ui-focus" @click="open = false" aria-label="{{ __('Kapat') }}">
                <x-icon.x class="h-4 w-4" />
            </button>
        @endif
    </div>
</div>
