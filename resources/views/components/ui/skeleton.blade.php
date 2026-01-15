@props([
    'rounded' => 'rounded-xl',
])

<div {{ $attributes->merge(['class' => "animate-pulse bg-slate-200/70 {$rounded}"]) }}></div>
