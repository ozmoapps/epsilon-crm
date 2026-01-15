@props(['variant' => 'neutral', 'status' => null])
<x-ui.badge :variant="$status ?? $variant" {{ $attributes }}>
  {{ $slot }}
</x-ui.badge>
