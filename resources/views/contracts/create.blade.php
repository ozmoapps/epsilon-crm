<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Sözleşme Oluştur') }}" subtitle="{{ $salesOrder->order_no }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('sales-orders.show', $salesOrder) }}" variant="secondary" size="sm">
                    {{ __('Siparişe Dön') }}
                </x-ui.button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">
        @include('contracts._sales_order_summary', ['salesOrder' => $salesOrder])

        <x-ui.card>
            <x-slot name="header">{{ __('Sözleşme Detayları') }}</x-slot>
            <form method="POST" action="{{ route('sales-orders.contracts.store', $salesOrder) }}" class="space-y-6">
                @csrf

                @include('contracts._form', ['contract' => $contract])

                <div class="flex items-center justify-end">
                    <x-ui.button type="submit">{{ __('Sözleşmeyi Oluştur') }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-app-layout>
