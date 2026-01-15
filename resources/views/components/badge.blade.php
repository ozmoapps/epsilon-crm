@props(['variant' => 'neutral', 'status' => null])

@php
  $value = $status ?? $variant;
  $normalized = is_string($value) ? strtolower($value) : $value;
  $map = [
    'paid' => 'success',
    'active' => 'success',
    'approved' => 'success',
    'accepted' => 'success',
    'posted' => 'success',
    'issued' => 'success',
    'sent' => 'info',
    'shared' => 'info',
    'info' => 'info',
    'draft' => 'neutral',
    'pending' => 'neutral',
    'neutral' => 'neutral',
    'cancelled' => 'danger',
    'canceled' => 'danger',
    'overdue' => 'danger',
    'error' => 'danger',
    'failed' => 'danger',
    'danger' => 'danger',
    'success' => 'success',
  ];
  $variant = $map[$normalized] ?? (in_array($normalized, ['neutral', 'info', 'success', 'danger'], true) ? $normalized : 'neutral');
@endphp

<x-ui.badge :variant="$variant" {{ $attributes }}>
  {{ $slot }}
</x-ui.badge>
