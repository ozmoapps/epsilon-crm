@props([
    'align' => 'right',
    'width' => 'w-56',
])

@php
    $alignmentClasses = $align === 'left' ? 'left-0 origin-top-left' : 'right-0 origin-top-right';
@endphp

<div class="relative inline-flex" x-data="{ open: false }" @keydown.escape="open = false">
    <div @click="open = !open" @keydown.enter.prevent="open = !open" @keydown.space.prevent="open = !open">
        {{ $trigger }}
    </div>

    <div
        x-show="open"
        x-transition
        @click.away="open = false"
        class="absolute {{ $alignmentClasses }} z-20 mt-2 {{ $width }} rounded-lg border border-slate-200 bg-white shadow-lg ring-1 ring-black/5"
    >
        <div class="py-1">
            {{ $content }}
        </div>
    </div>
</div>
