@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['type' => 'date', 'class' => 'block w-full ui-input']) !!}>
