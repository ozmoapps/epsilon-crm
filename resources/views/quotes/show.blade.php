<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Teklif Detayı') }}" subtitle="{{ $quote->quote_no }}">
            <x-slot name="actions">
                <x-button href="{{ route('quotes.edit', $quote) }}" variant="secondary" size="sm">
                    {{ __('Düzenle') }}
                </x-button>
                <x-button href="{{ route('quotes.index') }}" variant="secondary" size="sm">
                    {{ __('Tüm teklifler') }}
                </x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">
        <x-card>
            <x-slot name="header">{{ __('Özet') }}</x-slot>
            <div class="grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Müşteri') }}</p>
                    <p class="text-base font-medium text-gray-900">{{ $quote->customer?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Tekne') }}</p>
                    <p class="text-base font-medium text-gray-900">{{ $quote->vessel?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('İş Emri') }}</p>
                    <p class="text-base font-medium text-gray-900">{{ $quote->workOrder?->title ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Durum') }}</p>
                    <x-badge status="{{ $quote->status }}">{{ $quote->status_label }}</x-badge>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Para Birimi') }}</p>
                    <p class="text-base font-medium text-gray-900">{{ $quote->currency }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Geçerlilik') }}</p>
                    <p class="text-base font-medium text-gray-900">
                        {{ $quote->validity_days !== null ? $quote->validity_days . ' gün' : '-' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Tahmini Süre') }}</p>
                    <p class="text-base font-medium text-gray-900">
                        {{ $quote->estimated_duration_days !== null ? $quote->estimated_duration_days . ' gün' : '-' }}
                    </p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Teklif Konusu') }}</p>
                    <p class="text-base font-medium text-gray-900">{{ $quote->title }}</p>
                </div>
            </div>
        </x-card>

        <x-card>
            <x-slot name="header">{{ __('Koşullar') }}</x-slot>
            <div class="grid gap-4 text-sm text-gray-700 md:grid-cols-2">
                <div>
                    <p class="font-semibold text-gray-900">{{ __('Ödeme Şartları') }}</p>
                    <p class="mt-1">{{ $quote->payment_terms ?: '-' }}</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">{{ __('Garanti') }}</p>
                    <p class="mt-1">{{ $quote->warranty_text ?: '-' }}</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">{{ __('Hariçler') }}</p>
                    <p class="mt-1">{{ $quote->exclusions ?: '-' }}</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">{{ __('Notlar') }}</p>
                    <p class="mt-1">{{ $quote->notes ?: '-' }}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="font-semibold text-gray-900">{{ __('Kur Notu') }}</p>
                    <p class="mt-1">{{ $quote->fx_note ?: '-' }}</p>
                </div>
            </div>
        </x-card>

        <x-card>
            @php
                $itemTypes = config('quotes.item_types', []);
                $currencySymbols = config('quotes.currency_symbols', []);
                $currencySymbol = $currencySymbols[$quote->currency] ?? $quote->currency;
                $itemsBySection = $quote->items->groupBy(fn ($item) => $item->section ?: 'Genel');
                $formatMoney = fn ($value) => number_format((float) $value, 2, ',', '.');
                $unitOptions = config('quotes.unit_options', []);
                $vatOptions = config('quotes.vat_rates', []);
                $discountTotal = (float) $quote->discount_total;
                $computedGrandTotal = $quote->subtotal - $discountTotal + $quote->vat_total;
            @endphp

            <x-slot name="header">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <span>{{ __('Kalemler') }}</span>
                    <span class="text-xs font-normal text-gray-500">{{ __('Teklif kalemlerini tek ekrandan yönetin.') }}</span>
                </div>
            </x-slot>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div class="space-y-6">
                    <div class="hidden rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 lg:grid lg:grid-cols-12 lg:items-center">
                        <div class="lg:col-span-5">{{ __('Hizmet/Ürün') }}</div>
                        <div class="lg:col-span-1 text-right">{{ __('Miktar') }}</div>
                        <div class="lg:col-span-1">{{ __('Birim') }}</div>
                        <div class="lg:col-span-2 text-right">{{ __('Br. Fiyat') }}</div>
                        <div class="lg:col-span-1 text-right">{{ __('Vergi') }}</div>
                        <div class="lg:col-span-1 text-right">{{ __('Toplam') }}</div>
                        <div class="lg:col-span-1 text-right">{{ __('Aksiyonlar') }}</div>
                    </div>

                    <div class="space-y-6">
                        @forelse ($itemsBySection as $section => $items)
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $section }}</h4>
                                </div>
                                <div class="space-y-3">
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
                                            $showErrors = old('form_context') === 'item-' . $item->id;
                                        @endphp
                                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                                            <form method="POST" action="{{ route('quotes.items.update', [$quote, $item]) }}" class="space-y-4 lg:grid lg:grid-cols-12 lg:items-start lg:gap-4 lg:space-y-0" x-data="{ dirty: false }" @input="dirty = true" @change="dirty = true">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="form_context" value="item-{{ $item->id }}">

                                                <div class="space-y-2 lg:col-span-5">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <x-badge variant="success">{{ __('Kayıtlı') }}</x-badge>
                                                        <x-badge variant="info">
                                                            {{ $itemTypes[$item->item_type] ?? $item->item_type }}
                                                        </x-badge>
                                                        @if ($item->is_optional)
                                                            <x-badge variant="warning">{{ __('Opsiyon') }}</x-badge>
                                                        @endif
                                                    </div>
                                                    <label class="text-xs font-semibold text-gray-500 lg:sr-only">{{ __('Hizmet/Ürün') }}</label>
                                                    <x-select name="item_type" class="mt-1">
                                                        @foreach ($itemTypes as $value => $label)
                                                            <option value="{{ $value }}" @selected(old('item_type', $item->item_type) === $value)>
                                                                {{ $label }}
                                                            </option>
                                                        @endforeach
                                                    </x-select>
                                                    <p class="text-sm text-gray-600">
                                                        {{ \Illuminate\Support\Str::limit(old('description', $item->description), 120) ?: __('Detay eklenmedi.') }}
                                                    </p>
                                                    <details class="group" @if ($showErrors) open @endif>
                                                        <summary class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 transition hover:text-indigo-500">
                                                            <span>{{ __('Detay') }}</span>
                                                            <svg class="h-4 w-4 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                                            </svg>
                                                        </summary>
                                                        <x-textarea name="description" rows="3" class="mt-2" required>{{ old('description', $item->description) }}</x-textarea>
                                                    </details>
                                                    <div class="mt-2 flex items-center gap-2 text-xs text-gray-600">
                                                        <input id="is_optional_{{ $item->id }}" name="is_optional" type="checkbox" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked(old('is_optional', $item->is_optional))>
                                                        <label for="is_optional_{{ $item->id }}">{{ __('Opsiyon') }}</label>
                                                    </div>
                                                    @if ($showErrors)
                                                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                                                        <x-input-error :messages="$errors->get('item_type')" class="mt-2" />
                                                    @endif
                                                </div>

                                                <div class="space-y-2 lg:col-span-1">
                                                    <label class="text-xs font-semibold text-gray-500 lg:sr-only">{{ __('Miktar') }}</label>
                                                    <x-input name="qty" type="text" inputmode="decimal" class="mt-1 w-full text-right tabular-nums" :value="old('qty', $item->qty)" required />
                                                    @if ($showErrors)
                                                        <x-input-error :messages="$errors->get('qty')" class="mt-2" />
                                                    @endif
                                                </div>

                                                <div class="space-y-2 lg:col-span-1">
                                                    <label class="text-xs font-semibold text-gray-500 lg:sr-only">{{ __('Birim') }}</label>
                                                    <x-select name="unit" class="mt-1">
                                                        <option value="">{{ __('Seçiniz') }}</option>
                                                        @foreach ($unitOptions as $unitOption)
                                                            <option value="{{ $unitOption }}" @selected(old('unit', $item->unit) === $unitOption)>
                                                                {{ $unitOption }}
                                                            </option>
                                                        @endforeach
                                                    </x-select>
                                                    @if ($showErrors)
                                                        <x-input-error :messages="$errors->get('unit')" class="mt-2" />
                                                    @endif
                                                </div>

                                                <div class="space-y-2 lg:col-span-2">
                                                    <label class="text-xs font-semibold text-gray-500 lg:sr-only">{{ __('Br. Fiyat') }}</label>
                                                    <div class="relative">
                                                        <x-input name="unit_price" type="text" inputmode="decimal" class="mt-1 w-full pr-9 text-right tabular-nums" :value="old('unit_price', $item->unit_price)" required />
                                                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs font-semibold text-gray-400">
                                                            {{ $currencySymbol }}
                                                        </span>
                                                    </div>
                                                    @if ($showErrors)
                                                        <x-input-error :messages="$errors->get('unit_price')" class="mt-2" />
                                                    @endif
                                                </div>

                                                <div class="space-y-2 lg:col-span-1">
                                                    <label class="text-xs font-semibold text-gray-500 lg:sr-only">{{ __('Vergi') }}</label>
                                                    <x-select name="vat_rate" class="mt-1">
                                                        <option value="">{{ __('KDV Yok') }}</option>
                                                        @foreach ($vatOptions as $vatOption)
                                                            <option value="{{ $vatOption }}" @selected((string) old('vat_rate', $item->vat_rate) === (string) $vatOption)>
                                                                {{ __('KDV %:rate', ['rate' => $vatOption]) }}
                                                            </option>
                                                        @endforeach
                                                    </x-select>
                                                    @if ($showErrors)
                                                        <x-input-error :messages="$errors->get('vat_rate')" class="mt-2" />
                                                    @endif
                                                </div>

                                                <div class="space-y-1 text-sm font-semibold text-gray-900 lg:col-span-1 lg:text-right">
                                                    <span class="text-xs font-semibold text-gray-500 lg:sr-only">{{ __('Toplam') }}</span>
                                                    <div>{{ $currencySymbol }} {{ $formatMoney($lineTotal) }}</div>
                                                </div>

                                                <div class="flex flex-wrap items-center justify-end gap-2 lg:col-span-1">
                                                    <x-button type="submit" size="sm" class="disabled:cursor-not-allowed disabled:opacity-50" x-bind:disabled="!dirty">
                                                        {{ __('Kaydet') }}
                                                    </x-button>
                                                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 text-gray-600 transition hover:border-gray-300 hover:text-gray-900" onclick="const form = document.getElementById('new-item-form'); if (form) { form.open = true; } const field = document.getElementById('new-item-description'); if (field) { field.focus(); field.scrollIntoView({ behavior: 'smooth', block: 'center' }); }" aria-label="{{ __('Satır ekle') }}">
                                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                            <path fill-rule="evenodd" d="M10 4.25a.75.75 0 0 1 .75.75v4.25H15a.75.75 0 0 1 0 1.5h-4.25V15a.75.75 0 0 1-1.5 0v-4.25H5a.75.75 0 0 1 0-1.5h4.25V5a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                    <button form="delete-item-{{ $item->id }}" type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-red-200 text-red-600 transition hover:border-red-300 hover:text-red-700" aria-label="{{ __('Satırı sil') }}">
                                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                            <path fill-rule="evenodd" d="M8.75 2.75a.75.75 0 0 1 .75.75V4h2.5v-.5a.75.75 0 0 1 1.5 0V4h2a.75.75 0 0 1 0 1.5h-.5v9a2 2 0 0 1-2 2H6.25a2 2 0 0 1-2-2v-9h-.5a.75.75 0 0 1 0-1.5h2v-.5a.75.75 0 0 1 .75-.75h2.5Zm-2 2.75v9a.5.5 0 0 0 .5.5h5.5a.5.5 0 0 0 .5-.5v-9h-6.5Z" clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </form>

                                            @if ($showErrors)
                                                <div class="mt-3 rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-xs text-red-600">
                                                    <ul class="list-disc space-y-1 pl-4">
                                                        @foreach (['description', 'qty', 'unit', 'unit_price', 'vat_rate', 'item_type'] as $field)
                                                            @foreach ($errors->get($field) as $message)
                                                                <li>{{ $message }}</li>
                                                            @endforeach
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        </div>

                                        <form id="delete-item-{{ $item->id }}" method="POST" action="{{ route('quotes.items.destroy', [$quote, $item]) }}">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="rounded-xl border border-dashed border-gray-200 p-4 text-sm text-gray-500">
                                {{ __('Henüz kalem eklenmedi.') }}
                            </div>
                        @endforelse
                    </div>

                    @php
                        $showNewErrors = old('form_context') === 'new-item';
                    @endphp
                    <details id="new-item-form" class="rounded-xl border border-gray-100 bg-gray-50/60 p-4" @if ($showNewErrors) open @endif>
                        <summary class="cursor-pointer text-sm font-semibold text-gray-800">
                            {{ __('+ Yeni Satır Ekle') }}
                        </summary>
                        <div class="mt-4">
                            <form method="POST" action="{{ route('quotes.items.store', $quote) }}" class="space-y-4 lg:grid lg:grid-cols-12 lg:items-start lg:gap-4 lg:space-y-0">
                                @csrf
                                <input type="hidden" name="form_context" value="new-item">

                                <div class="space-y-2 lg:col-span-5">
                                    <label class="text-xs font-semibold text-gray-500 lg:sr-only">{{ __('Hizmet/Ürün') }}</label>
                                    <x-select name="item_type" class="mt-1">
                                        @foreach ($itemTypes as $value => $label)
                                            <option value="{{ $value }}" @selected(old('item_type') === $value)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </x-select>
                                    <p class="text-sm text-gray-600">{{ old('description') ? \Illuminate\Support\Str::limit(old('description'), 120) : __('Detay eklenmedi.') }}</p>
                                    <details class="group" @if ($showNewErrors) open @endif>
                                        <summary class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 transition hover:text-indigo-500">
                                            <span>{{ __('Detay') }}</span>
                                            <svg class="h-4 w-4 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                            </svg>
                                        </summary>
                                        <x-textarea id="new-item-description" name="description" rows="3" class="mt-2" required>{{ old('description') }}</x-textarea>
                                    </details>
                                    <div class="mt-2 flex items-center gap-2 text-xs text-gray-600">
                                        <input id="is_optional_new" name="is_optional" type="checkbox" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked(old('is_optional'))>
                                        <label for="is_optional_new">{{ __('Opsiyon') }}</label>
                                    </div>
                                    @if ($showNewErrors)
                                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                                        <x-input-error :messages="$errors->get('item_type')" class="mt-2" />
                                    @endif
                                </div>

                                <div class="space-y-2 lg:col-span-1">
                                    <label class="text-xs font-semibold text-gray-500 lg:sr-only">{{ __('Miktar') }}</label>
                                    <x-input name="qty" type="text" inputmode="decimal" class="mt-1 w-full text-right tabular-nums" :value="old('qty', 1)" required />
                                    @if ($showNewErrors)
                                        <x-input-error :messages="$errors->get('qty')" class="mt-2" />
                                    @endif
                                </div>

                                <div class="space-y-2 lg:col-span-1">
                                    <label class="text-xs font-semibold text-gray-500 lg:sr-only">{{ __('Birim') }}</label>
                                    <x-select name="unit" class="mt-1">
                                        <option value="">{{ __('Seçiniz') }}</option>
                                        @foreach ($unitOptions as $unitOption)
                                            <option value="{{ $unitOption }}" @selected(old('unit') === $unitOption)>
                                                {{ $unitOption }}
                                            </option>
                                        @endforeach
                                    </x-select>
                                    @if ($showNewErrors)
                                        <x-input-error :messages="$errors->get('unit')" class="mt-2" />
                                    @endif
                                </div>

                                <div class="space-y-2 lg:col-span-2">
                                    <label class="text-xs font-semibold text-gray-500 lg:sr-only">{{ __('Br. Fiyat') }}</label>
                                    <div class="relative">
                                        <x-input name="unit_price" type="text" inputmode="decimal" class="mt-1 w-full pr-9 text-right tabular-nums" :value="old('unit_price', 0)" required />
                                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs font-semibold text-gray-400">
                                            {{ $currencySymbol }}
                                        </span>
                                    </div>
                                    @if ($showNewErrors)
                                        <x-input-error :messages="$errors->get('unit_price')" class="mt-2" />
                                    @endif
                                </div>

                                <div class="space-y-2 lg:col-span-1">
                                    <label class="text-xs font-semibold text-gray-500 lg:sr-only">{{ __('Vergi') }}</label>
                                    <x-select name="vat_rate" class="mt-1">
                                        <option value="">{{ __('KDV Yok') }}</option>
                                        @foreach ($vatOptions as $vatOption)
                                            <option value="{{ $vatOption }}" @selected((string) old('vat_rate') === (string) $vatOption)>
                                                {{ __('KDV %:rate', ['rate' => $vatOption]) }}
                                            </option>
                                        @endforeach
                                    </x-select>
                                    @if ($showNewErrors)
                                        <x-input-error :messages="$errors->get('vat_rate')" class="mt-2" />
                                    @endif
                                </div>

                                <div class="space-y-1 text-sm font-semibold text-gray-900 lg:col-span-1 lg:text-right">
                                    <span class="text-xs font-semibold text-gray-500 lg:sr-only">{{ __('Toplam') }}</span>
                                    <div>{{ $currencySymbol }} --</div>
                                </div>

                                <div class="flex flex-wrap items-center justify-end gap-2 lg:col-span-1">
                                    <x-button type="submit" size="sm">{{ __('Satır Ekle') }}</x-button>
                                </div>
                            </form>

                            @if ($showNewErrors)
                                <div class="mt-3 rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-xs text-red-600">
                                    <ul class="list-disc space-y-1 pl-4">
                                        @foreach (['description', 'qty', 'unit', 'unit_price', 'vat_rate', 'item_type'] as $field)
                                            @foreach ($errors->get($field) as $message)
                                                <li>{{ $message }}</li>
                                            @endforeach
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </details>
                </div>

                <div class="lg:sticky lg:top-6">
                    <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-4">
                        <h4 class="text-sm font-semibold text-gray-800">{{ __('Toplamlar') }}</h4>
                        <dl class="mt-3 space-y-2 text-sm text-gray-700">
                            <div class="flex items-center justify-between">
                                <dt>{{ __('Ara Toplam (KDV hariç)') }}</dt>
                                <dd class="font-semibold">{{ $currencySymbol }} {{ $formatMoney($quote->subtotal) }}</dd>
                            </div>
                            @if ($discountTotal > 0)
                                <div class="flex items-center justify-between">
                                    <dt>{{ __('İndirim') }}</dt>
                                    <dd class="font-semibold">- {{ $currencySymbol }} {{ $formatMoney($discountTotal) }}</dd>
                                </div>
                            @endif
                            <div class="flex items-center justify-between">
                                <dt>{{ __('Toplam KDV') }}</dt>
                                <dd class="font-semibold">{{ $currencySymbol }} {{ $formatMoney($quote->vat_total) }}</dd>
                            </div>
                            <div class="flex items-center justify-between border-t border-gray-200 pt-2 text-base">
                                <dt>{{ __('Genel Toplam') }}</dt>
                                <dd class="font-semibold text-gray-900">{{ $currencySymbol }} {{ $formatMoney($computedGrandTotal) }}</dd>
                            </div>
                        </dl>
                        <p class="mt-2 text-xs text-gray-500">
                            {{ __('Ara Toplam - İndirim + KDV') }}: {{ $currencySymbol }} {{ $formatMoney($computedGrandTotal) }}
                        </p>
                        <p class="mt-2 text-xs text-gray-500">{{ __('Opsiyon kalemler toplam dışında bırakılır.') }}</p>
                    </div>
                </div>
            </div>
        </x-card>
    </div>
</x-app-layout>
