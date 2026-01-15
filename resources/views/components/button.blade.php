@props([
  'variant' => 'primary',
  'size' => 'md',
  'type' => 'button',
  'loading' => false,
  'disabled' => false,
])
<x-ui.button
  :variant="$variant"
  :size="$size"
  :type="$type"
  :loading="$loading"
  :disabled="$disabled"
  {{ $attributes }}
>
  {{ $slot }}
</x-ui.button>
