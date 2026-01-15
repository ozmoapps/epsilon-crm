<x-ui.card {{ $attributes }}>
  @isset($header)
    <x-slot name="header">{{ $header }}</x-slot>
  @endisset
  {{ $slot }}
</x-ui.card>
