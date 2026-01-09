@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-full bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 transition'
            : 'inline-flex items-center rounded-full px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-50 transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
