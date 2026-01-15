@props(['size' => 'md'])

@php
    $sizeClasses = [
        'md' => 'h-10',
        'sm' => 'h-9 text-sm',
    ];
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<select {{ $attributes->merge(['class' => "block {$sizeClass} ui-input"]) }}>
    {{ $slot }}
</select>
