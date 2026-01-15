@php
    $currencySymbols = config('quotes.currency_symbols', []);
    $currencySymbol = $currencySymbols[$salesOrder->currency] ?? $salesOrder->currency;
    $formatMoney = fn ($value) => number_format((float) $value, 2, ',', '.');
@endphp

<x-ui.card>
    <x-slot name="header">{{ __('Satış Siparişi Özeti') }}</x-slot>
    <div class="grid gap-4 text-sm sm:grid-cols-2">
        <div>
            <p class="text-xs tracking-wide text-slate-500">{{ __('Sipariş No') }}</p>
            <p class="text-base font-medium text-slate-900">{{ $salesOrder->order_no }}</p>
        </div>
        <div>
            <p class="text-xs tracking-wide text-slate-500">{{ __('Sipariş Başlığı') }}</p>
            <p class="text-base font-medium text-slate-900">{{ $salesOrder->title }}</p>
        </div>
        <div>
            <p class="text-xs tracking-wide text-slate-500">{{ __('Müşteri') }}</p>
            <p class="text-base font-medium text-slate-900">{{ $salesOrder->customer?->name ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs tracking-wide text-slate-500">{{ __('Tekne') }}</p>
            <p class="text-base font-medium text-slate-900">{{ $salesOrder->vessel?->name ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs tracking-wide text-slate-500">{{ __('Ara Toplam') }}</p>
            <p class="text-base font-medium text-slate-900">{{ $formatMoney($salesOrder->subtotal) }} {{ $currencySymbol }}</p>
        </div>
        <div>
            <p class="text-xs tracking-wide text-slate-500">{{ __('Vergi Toplamı') }}</p>
            <p class="text-base font-medium text-slate-900">{{ $formatMoney($salesOrder->vat_total) }} {{ $currencySymbol }}</p>
        </div>
        <div>
            <p class="text-xs tracking-wide text-slate-500">{{ __('Genel Toplam') }}</p>
            <p class="text-base font-medium text-slate-900">{{ $formatMoney($salesOrder->grand_total) }} {{ $currencySymbol }}</p>
        </div>
    </div>
</x-ui.card>

<x-ui.card>
    <x-slot name="header">{{ __('Kalem Özeti') }}</x-slot>
    <div class="space-y-4">
        @forelse ($salesOrder->items as $item)
            <div class="flex flex-col gap-2 rounded-xl border border-slate-100 p-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-medium text-slate-900">{{ $item->description }}</p>
                    <p class="text-xs text-slate-500">{{ $item->section ?: __('Genel') }}</p>
                </div>
                <div class="text-right text-slate-700">
                    {{ $item->qty }} {{ $item->unit }} · {{ $formatMoney($item->unit_price) }} {{ $currencySymbol }}
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500">{{ __('Kalem bulunamadı.') }}</p>
        @endforelse
    </div>
</x-ui.card>
