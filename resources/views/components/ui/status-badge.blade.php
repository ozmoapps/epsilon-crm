@props([
  'variant' => null,   // success|warning|danger|info|neutral
  'status'  => null,   // true/false
  'text'    => null,   // override label
  'size'    => 'sm',   // sm|md
])

@php
  $label = $text ?? trim($slot);
  if ($label === '' && $status !== null) {
      $label = $status ? 'Aktif' : 'Pasif';
  }
  // Default variant based on status if not provided
  if (($variant === null || $variant === '') && $status !== null) {
      $variant = $status ? 'success' : 'neutral';
  }
  $variant = $variant ?: 'neutral';
  $sizes = [
      'sm' => 'px-2 py-0.5 text-xs',
      'md' => 'px-2.5 py-1 text-sm',
  ];
  $variants = [
      'success' => 'success',
      'warning' => 'info',
      'danger'  => 'danger',
      'info'    => 'info',
      'neutral' => 'neutral',
  ];
  $variant = $variants[$variant] ?? 'neutral';
@endphp

<x-ui.badge :variant="$variant" {{ $attributes->merge(['class' => $sizes[$size] ?? $sizes['sm']]) }}>
  {{ $label }}
</x-ui.badge>
