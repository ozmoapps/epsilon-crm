@props(['size' => 'md'])

@php
    $sizeClasses = [
        'md' => 'py-2.5',
        'sm' => 'py-2 text-sm',
    ];
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<textarea {{ $attributes->merge(['class' => "block {$sizeClass} ui-input"]) }}>{{ $slot }}</textarea>
