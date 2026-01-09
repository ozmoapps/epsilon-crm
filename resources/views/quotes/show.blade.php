<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                    {{ __('Teklif Detayı') }}
                </h2>
                <p class="text-sm text-gray-500">{{ $quote->quote_no }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('quotes.edit', $quote) }}" class="inline-flex items-center justify-center rounded-md border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    {{ __('Düzenle') }}
                </a>
                <a href="{{ route('quotes.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
                    {{ __('Tüm teklifler') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
            <div class="space-y-6">
                @if (session('success'))
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Müşteri') }}</p>
                            <p class="text-base text-gray-900">{{ $quote->customer?->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Tekne') }}</p>
                            <p class="text-base text-gray-900">{{ $quote->vessel?->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('İş Emri') }}</p>
                            <p class="text-base text-gray-900">{{ $quote->workOrder?->title ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Durum') }}</p>
                            <p class="text-base text-gray-900">{{ $quote->status_label }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Para Birimi') }}</p>
                            <p class="text-base text-gray-900">{{ $quote->currency }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Geçerlilik') }}</p>
                            <p class="text-base text-gray-900">
                                {{ $quote->validity_days !== null ? $quote->validity_days . ' gün' : '-' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Tahmini Süre') }}</p>
                            <p class="text-base text-gray-900">
                                {{ $quote->estimated_duration_days !== null ? $quote->estimated_duration_days . ' gün' : '-' }}
                            </p>
                        </div>
                        <div class="sm:col-span-2">
                            <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Teklif Konusu') }}</p>
                            <p class="text-base text-gray-900">{{ $quote->title }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700">{{ __('Koşullar') }}</h3>
                    <div class="mt-4 space-y-4 text-sm text-gray-700">
                        <div>
                            <p class="font-semibold text-gray-900">{{ __('Ödeme Şartları') }}</p>
                            <p>{{ $quote->payment_terms ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">{{ __('Garanti') }}</p>
                            <p>{{ $quote->warranty_text ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">{{ __('Hariçler') }}</p>
                            <p>{{ $quote->exclusions ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">{{ __('Notlar') }}</p>
                            <p>{{ $quote->notes ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">{{ __('Kur Notu') }}</p>
                            <p>{{ $quote->fx_note ?: '-' }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    @php
                        $itemTypes = config('quotes.item_types', []);
                        $itemsBySection = $quote->items->groupBy(fn ($item) => $item->section ?: 'Genel');
                        $formatMoney = fn ($value) => number_format((float) $value, 2, ',', '.');
                    @endphp

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700">{{ __('Kalemler') }}</h3>
                        <p class="text-xs text-gray-500">{{ __('Teklif kalemlerini tek ekrandan yönetin.') }}</p>
                    </div>

                    <div class="mt-6 rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <p class="text-sm font-semibold text-gray-800">{{ __('Kalem Ekle') }}</p>
                        @include('quotes._item_form', [
                            'item' => new \\App\\Models\\QuoteItem(),
                            'action' => route('quotes.items.store', $quote),
                            'method' => 'POST',
                            'buttonLabel' => 'Kalem Ekle',
                            'itemTypes' => $itemTypes,
                            'fieldPrefix' => 'new',
                        ])
                    </div>

                    <div class="mt-6 space-y-6">
                        @forelse ($itemsBySection as $section => $items)
                            <div class="space-y-4">
                                <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $section }}</h4>
                                <div class="space-y-4">
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
                                            $quantityLabel = $item->unit ? $qty . ' ' . $item->unit : (string) $qty;
                                        @endphp
                                        <div class="rounded-lg border border-gray-100 p-4">
                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                <div>
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="rounded-full bg-indigo-50 px-2 py-1 text-xs font-semibold text-indigo-700">
                                                            {{ $itemTypes[$item->item_type] ?? $item->item_type }}
                                                        </span>
                                                        @if ($item->is_optional)
                                                            <span class="rounded-full bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700">
                                                                {{ __('Opsiyon') }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <p class="mt-2 text-sm text-gray-900">{{ $item->description }}</p>
                                                    <p class="mt-1 text-xs text-gray-500">
                                                        {{ $quantityLabel }}
                                                        · {{ $formatMoney($unitPrice) }} {{ $quote->currency }}
                                                    </p>
                                                    <p class="mt-1 text-xs text-gray-500">
                                                        {{ __('İndirim') }}: {{ $formatMoney($lineDiscount) }} {{ $quote->currency }}
                                                        · {{ __('KDV') }}: {{ $vatRate !== null ? $vatRate . '%' : '-' }}
                                                    </p>
                                                </div>
                                                <div class="text-sm font-semibold text-gray-900">
                                                    {{ $formatMoney($lineTotal) }} {{ $quote->currency }}
                                                </div>
                                            </div>

                                            <div class="mt-4 flex flex-wrap gap-3 text-sm">
                                                <details class="w-full rounded-md bg-gray-50 p-3">
                                                    <summary class="cursor-pointer text-sm font-semibold text-gray-700">{{ __('Düzenle') }}</summary>
                                                    <div class="mt-4">
                                                        @include('quotes._item_form', [
                                                            'item' => $item,
                                                            'action' => route('quotes.items.update', [$quote, $item]),
                                                            'method' => 'PUT',
                                                            'buttonLabel' => 'Kalemi Güncelle',
                                                            'itemTypes' => $itemTypes,
                                                            'fieldPrefix' => 'item-' . $item->id,
                                                        ])
                                                    </div>
                                                </details>

                                                <form method="POST" action="{{ route('quotes.items.destroy', [$quote, $item]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center justify-center rounded-md border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">
                                                        {{ __('Sil') }}
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="rounded-lg border border-dashed border-gray-200 p-4 text-sm text-gray-500">
                                {{ __('Henüz kalem eklenmedi.') }}
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-8 rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <h4 class="text-sm font-semibold text-gray-800">{{ __('Toplamlar') }}</h4>
                        <dl class="mt-3 space-y-2 text-sm text-gray-700">
                            <div class="flex items-center justify-between">
                                <dt>{{ __('Ara Toplam') }}</dt>
                                <dd class="font-semibold">{{ $formatMoney($quote->subtotal) }} {{ $quote->currency }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt>{{ __('İndirim') }}</dt>
                                <dd class="font-semibold">{{ $formatMoney($quote->discount_total) }} {{ $quote->currency }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt>{{ __('KDV') }}</dt>
                                <dd class="font-semibold">{{ $formatMoney($quote->vat_total) }} {{ $quote->currency }}</dd>
                            </div>
                            <div class="flex items-center justify-between border-t border-gray-200 pt-2 text-base">
                                <dt>{{ __('Genel Toplam') }}</dt>
                                <dd class="font-semibold text-gray-900">{{ $formatMoney($quote->grand_total) }} {{ $quote->currency }}</dd>
                            </div>
                        </dl>
                        <p class="mt-2 text-xs text-gray-500">{{ __('Opsiyon kalemler toplam dışında bırakılır.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
