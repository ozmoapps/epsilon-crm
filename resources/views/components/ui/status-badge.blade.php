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

  $base = 'inline-flex items-center rounded-full font-medium whitespace-nowrap';
  $sizes = [
      'sm' => 'px-2 py-0.5 text-xs',
      'md' => 'px-2.5 py-1 text-sm',
  ];
  $colors = [
      'success' => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20',
      'warning' => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20',
      'danger'  => 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20',
      'info'    => 'bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-600/20',
      'neutral' => 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-600/10',
  ];
@endphp

<span {{ $attributes->merge(['class' => $base.' '.($sizes[$size] ?? $sizes['sm']).' '.($colors[$variant] ?? $colors['neutral'])]) }}>
  {{ $label }}
</span>
