@props(['text'])

<div {{ $attributes->merge(['class' => 'group relative inline-flex']) }}>
    {{ $slot }}
    <div class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 hidden w-max -translate-x-1/2 rounded bg-slate-900 px-2 py-1 text-xs font-medium text-white shadow-lg group-hover:block group-focus-within:block">
        {{ $text }}
        <div class="absolute left-1/2 top-full h-2 w-2 -translate-x-1/2 -translate-y-1 rotate-45 bg-slate-900"></div>
    </div>
</div>
