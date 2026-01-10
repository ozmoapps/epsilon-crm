@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-lg bg-brand-50 px-4 py-2 text-start text-base font-semibold text-brand-700 shadow-soft transition'
            : 'block w-full rounded-lg px-4 py-2 text-start text-base font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900';
@endphp

<a {{ $attributes->merge(['class' => $classes . ' ui-focus']) }}>
    {{ $slot }}
</a>
