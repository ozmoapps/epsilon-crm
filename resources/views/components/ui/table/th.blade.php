@props(['align' => 'left'])

@php
    $alignClass = $align === 'right' ? 'text-right' : ($align === 'center' ? 'text-center' : 'text-left');
@endphp

<th {{ $attributes->merge(['class' => 'px-6 py-3 ' . $alignClass]) }}>
    {{ $slot }}
</th>
