<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Satış Siparişleri') }}" subtitle="{{ __('Tüm satış siparişlerini görüntüleyin.') }}" />
    </x-slot>

    <div class="space-y-6">
        <x-card>
            <form method="GET" action="{{ route('sales-orders.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <x-input-label for="search" :value="__('Ara (Sipariş No / Başlık)')" />
                    <x-input id="search" name="search" type="text" class="mt-1 w-full" :value="$search" placeholder="SO-2026-0001" />
                </div>
                <div>
                    <x-input-label for="status" :value="__('Durum')" />
                    <x-select id="status" name="status" class="mt-1 w-full sm:w-48">
                        <option value="">{{ __('Tümü') }}</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </x-select>
                </div>
                <div class="flex gap-2">
                    <x-button type="submit">{{ __('Filtrele') }}</x-button>
                    <x-button href="{{ route('sales-orders.index') }}" variant="secondary">{{ __('Temizle') }}</x-button>
                </div>
            </form>
        </x-card>

        <x-card>
            <x-slot name="header">{{ __('Satış Siparişleri') }}</x-slot>
            <div class="space-y-4">
                @forelse ($salesOrders as $salesOrder)
                    <div class="flex flex-col gap-3 rounded-lg border border-gray-100 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ $salesOrder->order_no }}</p>
                            <p class="text-sm text-gray-600">{{ $salesOrder->title }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $salesOrder->customer?->name ?? '-' }} · {{ $salesOrder->vessel?->name ?? '-' }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500">{{ __('Genel Toplam') }}</p>
                            <p class="text-sm font-semibold text-gray-900">
                                {{ number_format((float) $salesOrder->grand_total, 2, ',', '.') }} {{ $salesOrder->currency }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-badge status="{{ $salesOrder->status }}">{{ $salesOrder->status_label }}</x-badge>
                            <x-button href="{{ route('sales-orders.show', $salesOrder) }}" variant="secondary" size="sm">
                                {{ __('Görüntüle') }}
                            </x-button>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">{{ __('Henüz satış siparişi oluşturulmadı.') }}</p>
                @endforelse
            </div>
        </x-card>

        <div>
            {{ $salesOrders->links() }}
        </div>
    </div>
</x-app-layout>
