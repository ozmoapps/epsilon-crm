@props([
    'label' => null,
    'name' => null,
    'hint' => null,
    'required' => false,
    'inline' => false,
])

@php
    $error = $name ? $errors->first($name) : null;
    $errorId = $name && $error ? "{$name}-error" : null;
    $hintId = $name && $hint && !$error ? "{$name}-hint" : null;
    
    // Build aria-describedby for the input
    $ariaDescribedBy = $errorId ?? $hintId;
@endphp

<div {{ $attributes->class(['space-y-1.5']) }}>
    @if($label)
        <label @if($name) for="{{ $name }}" @endif class="block text-sm font-medium text-slate-700">
            {{ $label }}
            @if($required)
                <span class="text-rose-600" aria-label="zorunlu">*</span>
            @endif
        </label>
    @endif

    {{-- Render slot content - note: aria-describedby should be added to input manually if needed --}}
    <div @if($ariaDescribedBy) data-aria-describedby="{{ $ariaDescribedBy }}" @endif>
        {{ $slot }}
    </div>

    @if($error)
        <p id="{{ $errorId }}" class="text-xs font-medium text-rose-600" role="alert">{{ $error }}</p>
    @elseif($hint)
        <p id="{{ $hintId }}" class="text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
