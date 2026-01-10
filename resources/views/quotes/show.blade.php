<x-app-layout>
    @php
        $hasSalesOrder = (bool) $quote->salesOrder;
        $isLocked = $quote->isLocked();
        
        $itemTypes = config('quotes.item_types', []);
        $currencyCode = $quote->currencyRelation?->code ?? $quote->currency;
        $currencySymbol = $quote->currencyRelation?->symbol ?? $currencyCode;
        $itemsBySection = $quote->items->groupBy(fn ($item) => $item->section ?: 'Genel');
        $formatMoney = fn ($value) => \App\Support\MoneyMath::formatTR($value);
        $unitOptions = config('quotes.unit_options', []);
        $vatOptions = config('quotes.vat_rates', []);
    @endphp

    @component('partials.show-layout')
        @slot('header')
            @component('partials.page-header', [
                'title' => __('Teklif') . ' ' . ($quote->quote_no ?? '#' . $quote->id),
                'subtitle' => ($quote->customer?->name ?? '-') . ' • ' . ($quote->vessel?->name ?? '-') . ' • ' . ($quote->issued_at?->format('d.m.Y') ?? '-'),
            ])
                @slot('status')
                     <x-badge status="{{ $quote->status }}">{{ $quote->status_label }}</x-badge>
                @endslot
                
                @slot('actions')
                    @if ($hasSalesOrder)
                        <x-button href="{{ route('sales-orders.show', $quote->salesOrder) }}" variant="secondary" size="sm">
                            {{ __('Siparişi Gör') }}
                        </x-button>
                    @elseif ($quote->status === 'accepted')
                        <form method="POST" action="{{ route('quotes.convert_to_sales_order', $quote) }}">
                            @csrf
                            <x-button type="submit" size="sm">
                                {{ __('Satış Siparişi Oluştur') }}
                            </x-button>
                        </form>
                    @else
                         <x-button type="button" size="sm" disabled class="disabled:cursor-not-allowed disabled:opacity-50">
                            {{ __('Satış Siparişi Oluştur') }}
                        </x-button>
                    @endif

                    @if ($quote->status === 'draft')
                        <form method="POST" action="{{ route('quotes.mark_sent', $quote) }}">
                            @csrf
                            <x-button type="submit" variant="secondary" size="sm">
                                {{ __('Gönderildi') }}
                            </x-button>
                        </form>
                    @elseif ($quote->status === 'sent')
                        <form method="POST" action="{{ route('quotes.mark_accepted', $quote) }}">
                            @csrf
                            <x-button type="submit" variant="secondary" size="sm">
                                {{ __('Onaylandı') }}
                            </x-button>
                        </form>
                    @endif
                    
                    @if ($isLocked)
                        <div class="flex items-center gap-1">
                            <x-button
                                type="button"
                                variant="secondary"
                                size="sm"
                                class="cursor-not-allowed opacity-60"
                                aria-disabled="true"
                                title="{{ __('Bu teklif siparişe dönüştürüldüğü için düzenlenemez.') }}"
                                @click.prevent
                            >
                                {{ __('Düzenle') }}
                            </x-button>
                             <x-ui.badge variant="neutral" class="text-[10px]">{{ __('Kilitli') }}</x-ui.badge>
                        </div>
                    @else
                        <x-button href="{{ route('quotes.edit', $quote) }}" variant="secondary" size="sm">
                            {{ __('Düzenle') }}
                        </x-button>
                    @endif

                    <x-ui.dropdown align="right" width="48">
                        <x-slot name="trigger">
                             <button class="inline-flex items-center px-3 py-2 border border-slate-200 shadow-sm text-sm leading-4 font-medium rounded-lg text-slate-700 bg-white hover:bg-slate-50 focus:outline-none transition ease-in-out duration-150">
                                <x-icon.dots class="h-4 w-4" />
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link href="{{ route('quotes.preview', $quote) }}">
                                {{ __('Önizle') }}
                            </x-dropdown-link>
                            <x-dropdown-link href="{{ route('quotes.pdf', $quote) }}">
                                {{ __('Yazdır/PDF') }}
                            </x-dropdown-link>

                             <div class="border-t border-gray-100"></div>

                            <form method="POST" action="{{ route('quotes.destroy', $quote) }}">
                                @csrf
                                @method('DELETE')
                                 <x-dropdown-link href="#" onclick="event.preventDefault(); if(confirm('Bu işlem geri alınamaz. Emin misiniz?')) this.closest('form').submit();" class="text-rose-600">
                                    {{ __('Sil') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-ui.dropdown>

                    <x-button href="{{ route('quotes.index') }}" variant="secondary" size="sm">
                        {{ __('Listeye Dön') }}
                    </x-button>
                @endslot
            @endcomponent
        @endslot

        @slot('left')
            {{-- General Info --}}
            <x-card class="rounded-2xl border border-slate-200 bg-white shadow-sm !p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-bold tracking-wider text-slate-500 uppercase mb-1">{{ __('Müşteri') }}</p>
                        <a href="{{ route('admin.company-profiles.show', $quote->customer_id) }}" class="text-sm font-medium text-slate-900 hover:text-brand-600 hover:underline decoration-brand-600/50 underline-offset-4 transition-all">
                            {{ $quote->customer?->name ?? '-' }}
                        </a>
                    </div>
                    <div>
                        <p class="text-xs font-bold tracking-wider text-slate-500 uppercase mb-1">{{ __('Tekne') }}</p>
                        <p class="text-sm font-medium text-slate-900">{{ $quote->vessel?->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold tracking-wider text-slate-500 uppercase mb-1">{{ __('Teklif Tarihi') }}</p>
                        <p class="text-sm font-medium text-slate-900">{{ $quote->issued_at?->format('d.m.Y') ?? '-' }}</p>
                    </div>
                     <div>
                        <p class="text-xs font-bold tracking-wider text-slate-500 uppercase mb-1">{{ __('Geçerlilik') }}</p>
                        <p class="text-sm font-medium text-slate-900">
                            {{ $quote->validity_days !== null ? $quote->validity_days . ' gün' : '-' }}
                        </p>
                    </div>
                     <div>
                        <p class="text-xs font-bold tracking-wider text-slate-500 uppercase mb-1">{{ __('İş Emri') }}</p>
                        <p class="text-sm font-medium text-slate-900">{{ $quote->workOrder?->title ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold tracking-wider text-slate-500 uppercase mb-1">{{ __('Tahmini Süre') }}</p>
                        <p class="text-sm font-medium text-slate-900">
                            {{ $quote->estimated_duration_days !== null ? $quote->estimated_duration_days . ' gün' : '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-bold tracking-wider text-slate-500 uppercase mb-1">{{ __('İletişim') }}</p>
                        <div class="space-y-0.5">
                            <p class="text-sm font-medium text-slate-900">{{ $quote->contact_name ?: '-' }}</p>
                            @if ($quote->contact_phone)
                                <p class="text-xs text-slate-500">{{ $quote->contact_phone }}</p>
                            @endif
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-bold tracking-wider text-slate-500 uppercase mb-1">{{ __('Lokasyon') }}</p>
                        <p class="text-sm font-medium text-slate-900">{{ $quote->location ?: '-' }}</p>
                    </div>
                    <div class="sm:col-span-2 border-t border-slate-100 pt-4 mt-2">
                        <p class="text-xs font-bold tracking-wider text-slate-500 uppercase mb-1">{{ __('Teklif Konusu') }}</p>
                        <p class="text-sm font-medium text-slate-900">{{ $quote->title }}</p>
                    </div>
                </div>
            </x-card>

            <x-card>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 pb-4 mb-6">
                     <h3 class="font-semibold text-slate-900">{{ __('Kalemler') }}</h3>
                     <span class="text-xs font-medium text-slate-500 bg-slate-100 px-2 py-1 rounded">{{ count($quote->items) }} {{ __('kalem') }}</span>
                </div>

                 <div class="space-y-6">
                    <div class="hidden rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 text-xs font-semibold tracking-wide text-gray-500 lg:grid lg:grid-cols-12 lg:items-center">
                        <div class="lg:col-span-4">{{ __('Hizmet/Ürün') }}</div>
                        <div class="lg:col-span-1 text-right">{{ __('Miktar') }}</div>
                        <div class="lg:col-span-1 border-l border-gray-200 pl-2">{{ __('Birim') }}</div>
                        <div class="lg:col-span-2 text-right">{{ __('Br. Fiyat') }}</div>
                        <div class="lg:col-span-1 text-right">{{ __('İndirim') }}</div>
                        <div class="lg:col-span-1 text-right">{{ __('Vergi') }}</div>
                        <div class="lg:col-span-1 text-right">{{ __('Toplam') }}</div>
                        <div class="lg:col-span-1 text-right">{{ __('') }}</div>
                    </div>

                    <div class="space-y-6">
                        @forelse ($itemsBySection as $section => $items)
                            <div class="space-y-3">
                                <div class="flex items-center justify-between border-b border-gray-100 pb-1">
                                    <h4 class="text-xs font-bold tracking-wider text-gray-900 uppercase">{{ $section }}</h4>
                                </div>
                                <div class="space-y-3">
                                    @foreach ($items as $item)
                                        @php
                                            $qty = \App\Support\MoneyMath::decimalToScaledInt($item->qty);
                                            $unitPrice = \App\Support\MoneyMath::decimalToScaledInt($item->unit_price);
                                            $discountAmount = \App\Support\MoneyMath::decimalToScaledInt($item->discount_amount ?? 0);
                                            $vatBp = \App\Support\MoneyMath::percentToBasisPoints($item->vat_rate ?? 0);
                                            $line = \App\Support\MoneyMath::calculateLineCents($qty, $unitPrice, $discountAmount, $vatBp);
                                            $format = fn($cents) => \App\Support\MoneyMath::formatTR($cents / 100);
                                        @endphp
                                        <div class="rounded-lg border border-gray-100 bg-white p-3 shadow-sm hover:border-gray-200 transition-colors">
                                            <form method="POST" action="{{ route('quotes.items.update', [$quote, $item]) }}" class="space-y-4 lg:grid lg:grid-cols-12 lg:items-center lg:gap-4 lg:space-y-0" x-data="{ dirty: false }" @input="dirty = true" @change="dirty = true">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="form_context" value="item-{{ $item->id }}">

                                                <div class="space-y-2 lg:col-span-4">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        @if ($item->is_optional)
                                                            <x-badge variant="warning">{{ __('Opsiyon') }}</x-badge>
                                                        @endif
                                                    </div>
                                                    <label class="text-xs font-semibold text-gray-500 lg:sr-only">{{ __('Hizmet/Ürün') }}</label>
                                                    <x-select name="item_type" class="mt-1 !py-1 !text-xs">
                                                        @foreach ($itemTypes as $value => $label)
                                                            <option value="{{ $value }}" @selected($item->item_type === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </x-select>
                                                    <x-textarea name="description" rows="1" class="mt-2 text-sm resize-none" required>{{ $item->description }}</x-textarea>
                                                     <div class="mt-1 flex items-center gap-2 text-[10px] text-gray-500">
                                                        <input id="is_optional_{{ $item->id }}" name="is_optional" type="checkbox" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-3 w-3" @checked($item->is_optional)>
                                                        <label for="is_optional_{{ $item->id }}">{{ __('Opsiyon') }}</label>
                                                    </div>
                                                </div>

                                                <div class="space-y-1 lg:col-span-1">
                                                     <x-input name="qty" type="text" class="mt-1 w-full text-right !py-1 !text-sm" :value="$item->qty" required />
                                                </div>
                                                <div class="space-y-1 lg:col-span-1">
                                                     <x-select name="unit" class="mt-1 !py-1 !text-xs">
                                                        @foreach ($unitOptions as $unitOption)
                                                            <option value="{{ $unitOption }}" @selected($item->unit === $unitOption)>{{ $unitOption }}</option>
                                                        @endforeach
                                                    </x-select>
                                                </div>
                                                <div class="space-y-1 lg:col-span-2">
                                                     <x-input name="unit_price" type="text" class="mt-1 w-full text-right !py-1 !text-sm" :value="$item->unit_price" required />
                                                </div>
                                                <div class="space-y-1 lg:col-span-1">
                                                     <x-input name="discount_amount" type="text" class="mt-1 w-full text-right !py-1 !text-sm" :value="$item->discount_amount" />
                                                </div>
                                                 <div class="space-y-1 lg:col-span-1">
                                                    <x-select name="vat_rate" class="mt-1 !py-1 !text-xs">
                                                        <option value="">{{ __('Yok') }}</option>
                                                        @foreach ($vatOptions as $vatOption)
                                                            <option value="{{ $vatOption }}" @selected(((string)$item->vat_rate ?? '') === (string)$vatOption)>%{{ $vatOption }}</option>
                                                        @endforeach
                                                    </x-select>
                                                </div>
                                                 <div class="space-y-1 text-sm font-semibold text-gray-900 lg:col-span-1 lg:text-right">
                                                    <div>{{ $format($line['total_cents']) }} {{ $currencySymbol }}</div>
                                                </div>
                                                 <div class="flex flex-wrap items-center justify-end gap-2 lg:col-span-1">
                                                    <x-button type="submit" size="sm" class="!px-2 !py-1 text-xs" x-bind:disabled="!dirty" x-show="dirty">
                                                        {{ __('K') }}
                                                    </x-button>
                                                    <button form="delete-item-{{ $item->id }}" type="submit" class="text-slate-400 hover:text-red-600 transition-colors p-1" onclick="return confirm('Sil?')">
                                                        <x-icon.trash class="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                         <form id="delete-item-{{ $item->id }}" method="POST" action="{{ route('quotes.items.destroy', [$quote, $item]) }}" class="hidden">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                             <div class="text-sm text-gray-500 text-center py-8 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                                <p>{{ __('Henüz kalem eklenmedi.') }}</p>
                             </div>
                        @endforelse
                    </div>

                    {{-- Add New Item Form --}}
                    <div class="mt-6 border-t border-gray-100 pt-6">
                        <details class="group rounded-xl border border-dashed border-gray-300 bg-white p-4 open:border-solid open:border-gray-200 open:shadow-sm transition-all duration-200">
                            <summary class="flex cursor-pointer items-center gap-2 text-sm font-medium text-gray-600 transition group-open:mb-4 group-open:text-brand-600">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-100 text-gray-500 group-hover:bg-brand-50 group-hover:text-brand-600 transition">
                                    <x-icon.plus class="h-4 w-4" />
                                </span>
                                {{ __('Yeni Satır Ekle') }}
                            </summary>
                            <div class="animate-in fade-in slide-in-from-top-2 duration-200">
                                 <form method="POST" action="{{ route('quotes.items.store', $quote) }}" class="space-y-4 lg:grid lg:grid-cols-12 lg:items-start lg:gap-4 lg:space-y-0">
                                    @csrf
                                    <input type="hidden" name="form_context" value="new-item">
                                    
                                    <div class="lg:col-span-4 space-y-2">
                                        <x-select name="item_type" class="w-full">
                                            @foreach ($itemTypes as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </x-select>
                                        <x-textarea name="description" rows="2" class="w-full" placeholder="Açıklama" required></x-textarea>
                                    </div>
                                    <div class="lg:col-span-1"><x-input name="qty" value="1" class="w-full text-right" required placeholder="Miktar" /></div>
                                    <div class="lg:col-span-1">
                                        <x-select name="unit" class="w-full">
                                            @foreach ($unitOptions as $unitOption)
                                                <option value="{{ $unitOption }}">{{ $unitOption }}</option>
                                            @endforeach
                                        </x-select>
                                    </div>
                                    <div class="lg:col-span-2"><x-input name="unit_price" value="0" class="w-full text-right" required placeholder="Birim Fiyat" /></div>
                                    <div class="lg:col-span-1"><x-input name="discount_amount" value="0" class="w-full text-right" placeholder="İndirim" /></div>
                                    <div class="lg:col-span-1">
                                        <x-select name="vat_rate" class="w-full">
                                            <option value="">{{ __('KDV Yok') }}</option>
                                            @foreach ($vatOptions as $vatOption)
                                                <option value="{{ $vatOption }}" @selected(((string)$item->vat_rate ?? '') === (string)$vatOption)>%{{ $vatOption }}</option>
                                            @endforeach
                                        </x-select>
                                    </div>
                                    <div class="lg:col-span-2 text-right">
                                        <x-button type="submit" size="sm" class="w-full justify-center">{{ __('Ekle') }}</x-button>
                                    </div>
                                 </form>
                            </div>
                        </details>
                    </div>

                    {{-- Totals --}}
                    <div class="grid gap-8 md:grid-cols-12 md:items-start mt-8">
                        <div class="rounded-xl bg-blue-50/50 p-4 md:col-span-7 border border-blue-100">
                            <h5 class="text-xs font-semibold text-blue-900 uppercase tracking-wider mb-2">{{ __('Hesaplama Notları') }}</h5>
                            <ul class="text-xs text-blue-800 space-y-1 list-disc list-inside">
                                <li>{{ __('Ara Toplam - İndirim + KDV formülü kullanılır.') }}</li>
                                <li>{{ __('Opsiyon olarak işaretlenen kalemler toplama dahil edilmez.') }}</li>
                            </ul>
                        </div>
                        <div class="md:col-span-5 w-full">
                            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                                <h4 class="text-base font-semibold text-gray-900 mb-4">{{ __('Özet') }}</h4>
                                <dl class="space-y-3 text-sm text-gray-600">
                                    <div class="flex items-center justify-between">
                                        <dt>{{ __('Ara Toplam') }}</dt>
                                        <dd class="font-medium text-gray-900">{{ $formatMoney($quote->subtotal) }} {{ $currencySymbol }}</dd>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <dt>{{ __('İndirim') }}</dt>
                                        <dd class="font-medium text-red-600">- {{ $formatMoney($quote->discount_total) }} {{ $currencySymbol }}</dd>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <dt>{{ __('KDV') }}</dt>
                                        <dd class="font-medium text-gray-900">{{ $formatMoney($quote->vat_total) }} {{ $currencySymbol }}</dd>
                                    </div>
                                    <div class="border-t border-gray-100 pt-3 mt-3">
                                        <div class="flex items-center justify-between text-base">
                                            <dt class="font-bold text-gray-900">{{ __('Genel Toplam') }}</dt>
                                            <dd class="font-bold text-brand-600 text-lg">{{ $formatMoney($quote->grand_total) }} {{ $currencySymbol }}</dd>
                                        </div>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                 </div>
            </x-card>

            <x-card>
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                    <h3 class="font-semibold text-slate-900">{{ __('Koşullar ve Notlar') }}</h3>
                </div>
                <div class="grid gap-6 text-sm text-gray-600 md:grid-cols-2">
                    <div class="space-y-1">
                        <p class="font-medium text-gray-900">{{ __('Ödeme Şartları') }}</p>
                        <p class="leading-relaxed">{{ $quote->payment_terms ?: '-' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="font-medium text-gray-900">{{ __('Garanti') }}</p>
                        <p class="leading-relaxed">{{ $quote->warranty_text ?: '-' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="font-medium text-gray-900">{{ __('Hariçler') }}</p>
                        <p class="leading-relaxed">{{ $quote->exclusions ?: '-' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="font-medium text-gray-900">{{ __('Notlar') }}</p>
                        <p class="leading-relaxed">{{ $quote->notes ?: '-' }}</p>
                    </div>
                    <div class="md:col-span-2 space-y-1">
                        <p class="font-medium text-gray-900">{{ __('Kur Notu') }}</p>
                        <p class="leading-relaxed">{{ $quote->fx_note ?: '-' }}</p>
                    </div>
                </div>
            </x-card>
        @endslot

        @slot('right')
             @include('partials.document-hub', [
                'context' => 'quote',
                'quote' => $quote,
                'salesOrder' => $salesOrder ?? null,
                'contract' => $contract ?? null,
                'workOrder' => $workOrder ?? null,
                'timeline' => $timeline,
                'showTimeline' => false,
            ])

            <x-partials.follow-up-card :context="$quote" />

             <x-card class="overflow-hidden border border-slate-200 rounded-2xl shadow-sm !p-0 bg-white">
                <div class="px-5 py-4 border-b border-slate-100 bg-white">
                    <h3 class="font-semibold text-slate-900">{{ __('Aktivite') }}</h3>
                </div>
                <div class="p-5">
                    <x-activity-timeline :logs="$timeline" :show-subject="true" />
                </div>
            </x-card>
        @endslot
    @endcomponent
</x-app-layout>
