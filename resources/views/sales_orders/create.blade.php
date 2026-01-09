<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="{{ __('Yeni Satış Siparişi') }}"
        >
            <x-slot name="actions">
                <x-button href="{{ route('sales-orders.index') }}" variant="secondary" size="sm">
                    {{ __('Tüm satış siparişleri') }}
                </x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <form method="POST" action="{{ route('sales-orders.store') }}" class="space-y-6">
        @csrf
        @include('sales_orders._form', ['salesOrder' => $salesOrder])

        <div class="flex justify-end gap-3">
            <x-button type="submit">{{ __('Kaydet') }}</x-button>
        </div>
    </form>
</x-app-layout>
