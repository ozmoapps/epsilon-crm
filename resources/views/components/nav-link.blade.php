@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-full bg-brand-50 px-3 py-2 text-sm font-semibold text-brand-700 shadow-soft transition'
            : 'inline-flex items-center rounded-full px-3 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900';
@endphp

<a {{ $attributes->merge(['class' => $classes . ' ui-focus']) }}>
    {{ $slot }}
</a>
