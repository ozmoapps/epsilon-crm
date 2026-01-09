<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Satış Siparişi Detayı') }}" subtitle="{{ $salesOrder->order_no }}">
            <x-slot name="actions">
                <x-button href="{{ route('sales-orders.index') }}" variant="secondary" size="sm">
                    {{ __('Tüm satış siparişleri') }}
                </x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">
        <x-card>
            <x-slot name="header">{{ __('Özet') }}</x-slot>
            <div class="grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <p class="text-xs tracking-wide text-gray-500">{{ __('Müşteri') }}</p>
                    <p class="text-base font-medium text-gray-900">{{ $salesOrder->customer?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-gray-500">{{ __('Tekne') }}</p>
                    <p class="text-base font-medium text-gray-900">{{ $salesOrder->vessel?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-gray-500">{{ __('İş Emri') }}</p>
                    <p class="text-base font-medium text-gray-900">{{ $salesOrder->workOrder?->title ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-gray-500">{{ __('Durum') }}</p>
                    <x-badge status="{{ $salesOrder->status }}">{{ $salesOrder->status_label }}</x-badge>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-gray-500">{{ __('Sipariş Tarihi') }}</p>
                    <p class="text-base font-medium text-gray-900">
                        {{ $salesOrder->order_date ? $salesOrder->order_date->format('d.m.Y') : '-' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-gray-500">{{ __('Teslim Yeri') }}</p>
                    <p class="text-base font-medium text-gray-900">{{ $salesOrder->delivery_place ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-gray-500">{{ __('Teslim Süresi') }}</p>
                    <p class="text-base font-medium text-gray-900">
                        {{ $salesOrder->delivery_days !== null ? $salesOrder->delivery_days . ' gün' : '-' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-gray-500">{{ __('Para Birimi') }}</p>
                    <p class="text-base font-medium text-gray-900">{{ $salesOrder->currency }}</p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs tracking-wide text-gray-500">{{ __('Sipariş Başlığı') }}</p>
                    <p class="text-base font-medium text-gray-900">{{ $salesOrder->title }}</p>
                </div>
                @if ($salesOrder->quote)
                    <div class="sm:col-span-2">
                        <p class="text-xs tracking-wide text-gray-500">{{ __('Kaynak Teklif') }}</p>
                        <p class="text-base font-medium text-gray-900">
                            <a href="{{ route('quotes.show', $salesOrder->quote) }}" class="text-indigo-600 hover:text-indigo-500">
                                {{ $salesOrder->quote->quote_no }}
                            </a>
                        </p>
                    </div>
                @endif
            </div>
        </x-card>

        <x-card>
            <x-slot name="header">{{ __('Koşullar') }}</x-slot>
            <div class="grid gap-4 text-sm text-gray-700 md:grid-cols-2">
                <div>
                    <p class="font-semibold text-gray-900">{{ __('Ödeme Şartları') }}</p>
                    <p class="mt-1">{{ $salesOrder->payment_terms ?: '-' }}</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">{{ __('Garanti') }}</p>
                    <p class="mt-1">{{ $salesOrder->warranty_text ?: '-' }}</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">{{ __('Hariçler') }}</p>
                    <p class="mt-1">{{ $salesOrder->exclusions ?: '-' }}</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">{{ __('Notlar') }}</p>
                    <p class="mt-1">{{ $salesOrder->notes ?: '-' }}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="font-semibold text-gray-900">{{ __('Kur Notu') }}</p>
                    <p class="mt-1">{{ $salesOrder->fx_note ?: '-' }}</p>
                </div>
            </div>
        </x-card>

        <x-card>
            @php
                $itemTypes = config('sales_orders.item_types', []);
                $currencySymbols = config('quotes.currency_symbols', []);
                $currencySymbol = $currencySymbols[$salesOrder->currency] ?? $salesOrder->currency;
                $itemsBySection = $salesOrder->items->groupBy(fn ($item) => $item->section ?: 'Genel');
                $formatMoney = fn ($value) => number_format((float) $value, 2, ',', '.');
            @endphp

            <x-slot name="header">{{ __('Kalemler') }}</x-slot>

            <div class="space-y-6">
                @forelse ($itemsBySection as $section => $items)
                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                        <div class="border-b border-gray-100 bg-gray-50 px-4 py-2">
                            <h4 class="text-xs font-semibold tracking-wide text-gray-600">{{ $section }}</h4>
                        </div>
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-semibold tracking-wide text-gray-500">
                                    <th class="px-4 py-2">{{ __('Kalem') }}</th>
                                    <th class="px-4 py-2 text-right">{{ __('Miktar') }}</th>
                                    <th class="px-4 py-2">{{ __('Birim') }}</th>
                                    <th class="px-4 py-2 text-right">{{ __('Br. Fiyat') }}</th>
                                    <th class="px-4 py-2 text-right">{{ __('Toplam') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($items as $item)
                                    @php
                                        $qty = (float) $item->qty;
                                        $unitPrice = (float) $item->unit_price;
                                        $lineBase = $qty * $unitPrice;
                                        $lineDiscount = (float) ($item->discount_amount ?? 0);
                                        $lineNet = max($lineBase - $lineDiscount, 0);
                                        $vatRate = $item->vat_rate !== null ? (float) $item->vat_rate : null;
                                        $lineVat = $vatRate !== null ? $lineNet * ($vatRate / 100) : 0;
                                        $lineTotal = $lineNet + $lineVat;
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="space-y-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="text-xs font-semibold text-gray-500">
                                                        {{ $itemTypes[$item->item_type] ?? $item->item_type }}
                                                    </span>
                                                    @if ($item->is_optional)
                                                        <x-badge variant="warning">{{ __('Opsiyon') }}</x-badge>
                                                    @endif
                                                </div>
                                                <p class="text-gray-900">{{ $item->description }}</p>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-right tabular-nums text-gray-700">{{ $formatMoney($item->qty) }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $item->unit ?? '-' }}</td>
                                        <td class="px-4 py-3 text-right tabular-nums text-gray-700">{{ $currencySymbol }} {{ $formatMoney($item->unit_price) }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ $currencySymbol }} {{ $formatMoney($lineTotal) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">{{ __('Siparişe eklenmiş kalem bulunmuyor.') }}</p>
                @endforelse
            </div>

            <div class="mt-6 rounded-lg border border-gray-100 bg-gray-50 p-4 text-sm">
                <h4 class="text-sm font-semibold text-gray-800">{{ __('Toplamlar') }}</h4>
                <div class="mt-3 flex items-center justify-between">
                    <span class="text-gray-500">{{ __('Ara Toplam') }}</span>
                    <span class="font-semibold text-gray-900">{{ $currencySymbol }} {{ $formatMoney($salesOrder->subtotal) }}</span>
                </div>
                <div class="mt-2 flex items-center justify-between">
                    <span class="text-gray-500">{{ __('İskonto Toplamı') }}</span>
                    <span class="font-semibold text-gray-900">{{ $currencySymbol }} {{ $formatMoney($salesOrder->discount_total) }}</span>
                </div>
                <div class="mt-2 flex items-center justify-between">
                    <span class="text-gray-500">{{ __('KDV Toplamı') }}</span>
                    <span class="font-semibold text-gray-900">{{ $currencySymbol }} {{ $formatMoney($salesOrder->vat_total) }}</span>
                </div>
                <div class="mt-3 flex items-center justify-between border-t border-gray-200 pt-3">
                    <span class="text-sm font-semibold text-gray-700">{{ __('Genel Toplam') }}</span>
                    <span class="text-lg font-semibold text-gray-900">{{ $currencySymbol }} {{ $formatMoney($salesOrder->grand_total) }}</span>
                </div>
            </div>
        </x-card>
    </div>
</x-app-layout>
