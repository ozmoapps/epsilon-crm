@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-lg bg-indigo-50 px-4 py-2 text-start text-base font-semibold text-indigo-700 transition'
            : 'block w-full rounded-lg px-4 py-2 text-start text-base font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-800 transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
