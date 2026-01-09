<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="{{ __('Satış Siparişleri') }}"
            subtitle="{{ __('Tüm satış siparişlerini görüntüleyin.') }}"
        />
    </x-slot>

    <div class="space-y-6">
        <x-card>
            <form method="GET" action="{{ route('sales-orders.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6 lg:items-end">
                <div class="sm:col-span-2">
                    <x-input-label for="search" :value="__('Ara (Sipariş No / Başlık)')" />
                    <x-input id="search" name="search" type="text" class="mt-1 w-full" :value="$search" placeholder="SO-2026-0001" />
                </div>
                <div>
                    <x-input-label for="status" :value="__('Durum')" />
                    <x-select id="status" name="status" class="mt-1 w-full">
                        <option value="">{{ __('Tümü') }}</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </x-select>
                </div>
                <div>
                    <x-input-label for="date_from" :value="__('Tarih (Başlangıç)')" />
                    <x-input id="date_from" name="date_from" type="date" class="mt-1 w-full" :value="$dateFrom" />
                </div>
                <div>
                    <x-input-label for="date_to" :value="__('Tarih (Bitiş)')" />
                    <x-input id="date_to" name="date_to" type="date" class="mt-1 w-full" :value="$dateTo" />
                </div>
                <div>
                    <x-input-label for="customer_id" :value="__('Müşteri')" />
                    <x-select id="customer_id" name="customer_id" class="mt-1 w-full">
                        <option value="">{{ __('Tümü') }}</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((string) $customerId === (string) $customer->id)>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </x-select>
                </div>
                <div>
                    <x-input-label for="vessel_id" :value="__('Tekne')" />
                    <x-select id="vessel_id" name="vessel_id" class="mt-1 w-full">
                        <option value="">{{ __('Tümü') }}</option>
                        @foreach ($vessels as $vessel)
                            <option value="{{ $vessel->id }}" @selected((string) $vesselId === (string) $vessel->id)>
                                {{ $vessel->name }}{{ $vessel->customer ? ' · ' . $vessel->customer->name : '' }}
                            </option>
                        @endforeach
                    </x-select>
                </div>
                <div class="flex gap-2 sm:col-span-2 lg:col-span-6">
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
                            <p class="text-xs text-gray-500">
                                {{ __('Sipariş Tarihi') }}: {{ $salesOrder->order_date?->format('d.m.Y') ?? '-' }}
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
