<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Satış Siparişi Detayı') }}" subtitle="{{ $salesOrder->order_no }}">
            <x-slot name="actions">
                @php
                    $canConfirm = $salesOrder->status === 'draft';
                    $canStart = $salesOrder->status === 'confirmed';
                    $canComplete = $salesOrder->status === 'in_progress';
                    $canCancel = ! in_array($salesOrder->status, ['completed', 'cancelled', 'contracted'], true);
                    $hasContract = (bool) $salesOrder->contract;
                    $isLocked = $salesOrder->isLocked();
                    $actionItemClass = 'flex w-full items-center gap-2 px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50';
                    $actionDangerClass = 'flex w-full items-center gap-2 px-3 py-2 text-sm text-rose-600 transition hover:bg-rose-50';
                @endphp

                @if ($hasContract)
                    <x-button href="{{ route('contracts.show', $salesOrder->contract) }}" size="sm">
                        {{ __('Sözleşmeyi Görüntüle') }}
                    </x-button>
                @else
                    <form id="sales-order-contract-create-{{ $salesOrder->id }}" method="GET" action="{{ route('sales-orders.contracts.create', $salesOrder) }}" class="hidden"></form>
                    <x-ui.confirm
                        title="{{ __('Sözleşme oluşturmayı onayla') }}"
                        message="{{ __('Bu siparişten yeni bir sözleşme oluşturulacak. Devam etmek istiyor musunuz?') }}"
                        confirm-text="{{ __('Sözleşme oluştur') }}"
                        cancel-text="{{ __('Vazgeç') }}"
                        variant="primary"
                        form-id="sales-order-contract-create-{{ $salesOrder->id }}"
                    >
                        <x-slot name="trigger">
                            <x-button type="button" size="sm">
                                {{ __('Sözleşme Oluştur') }}
                            </x-button>
                        </x-slot>
                    </x-ui.confirm>
                @endif

                <x-ui.dropdown align="right" width="w-60">
                    <x-slot name="trigger">
                        <x-button type="button" variant="secondary" size="sm" class="inline-flex items-center gap-2">
                            {{ __('İşlemler') }}
                            <x-icon.dots class="h-4 w-4" />
                        </x-button>
                    </x-slot>
                    <x-slot name="content">
                        @if ($canConfirm)
                            <form method="POST" action="{{ route('sales-orders.confirm', $salesOrder) }}">
                                @csrf
                                @method('PATCH')
                                <x-ui.tooltip text="{{ __('Siparişi Onayla') }}" class="w-full">
                                    <button type="submit" class="{{ $actionItemClass }}">
                                        <x-icon.check class="h-4 w-4 text-emerald-600" />
                                        {{ __('Onayla') }}
                                    </button>
                                </x-ui.tooltip>
                            </form>
                        @endif
                        @if ($canStart)
                            <form method="POST" action="{{ route('sales-orders.start', $salesOrder) }}">
                                @csrf
                                @method('PATCH')
                                <x-ui.tooltip text="{{ __('Siparişi Başlat') }}" class="w-full">
                                    <button type="submit" class="{{ $actionItemClass }}">
                                        <x-icon.arrow-right class="h-4 w-4 text-blue-600" />
                                        {{ __('Devam Ettir') }}
                                    </button>
                                </x-ui.tooltip>
                            </form>
                        @endif
                        @if ($canComplete)
                            <form method="POST" action="{{ route('sales-orders.complete', $salesOrder) }}">
                                @csrf
                                @method('PATCH')
                                <x-ui.tooltip text="{{ __('Siparişi Tamamla') }}" class="w-full">
                                    <button type="submit" class="{{ $actionItemClass }}">
                                        <x-icon.check class="h-4 w-4 text-emerald-600" />
                                        {{ __('Tamamla') }}
                                    </button>
                                </x-ui.tooltip>
                            </form>
                        @endif
                        @if ($canCancel)
                            <form method="POST" action="{{ route('sales-orders.cancel', $salesOrder) }}">
                                @csrf
                                @method('PATCH')
                                <x-ui.tooltip text="{{ __('Siparişi İptal Et') }}" class="w-full">
                                    <button type="submit" class="{{ $actionDangerClass }}" onclick="return confirm('Satış siparişi iptal edilsin mi?')">
                                        <x-icon.x class="h-4 w-4" />
                                        {{ __('İptal') }}
                                    </button>
                                </x-ui.tooltip>
                            </form>
                        @endif
                        <div class="my-1 h-px bg-slate-100"></div>
                        @if ($isLocked)
                            <button
                                type="button"
                                class="{{ $actionItemClass }} cursor-not-allowed opacity-60"
                                aria-disabled="true"
                                title="{{ __('Bu sipariş sözleşmeye dönüştürüldüğü için düzenlenemez.') }}"
                                @click.prevent
                            >
                                <x-icon.pencil class="h-4 w-4 text-indigo-600" />
                                {{ __('Düzenle') }}
                                <x-ui.badge variant="neutral" class="ml-auto text-[10px]">{{ __('Kilitli') }}</x-ui.badge>
                            </button>
                        @else
                            <a href="{{ route('sales-orders.edit', $salesOrder) }}" class="{{ $actionItemClass }}">
                                <x-icon.pencil class="h-4 w-4 text-indigo-600" />
                                {{ __('Düzenle') }}
                            </a>
                        @endif
                        @if ($isLocked)
                            <button
                                type="button"
                                class="{{ $actionDangerClass }} cursor-not-allowed opacity-60"
                                aria-disabled="true"
                                title="{{ __('Bu siparişin bağlı sözleşmesi olduğu için silinemez.') }}"
                                @click.prevent
                            >
                                <x-icon.trash class="h-4 w-4" />
                                {{ __('Sil') }}
                                <x-ui.badge variant="neutral" class="ml-auto text-[10px]">{{ __('Kilitli') }}</x-ui.badge>
                            </button>
                        @else
                            <form id="sales-order-delete-{{ $salesOrder->id }}" method="POST" action="{{ route('sales-orders.destroy', $salesOrder) }}" class="hidden">
                                @csrf
                                @method('DELETE')
                            </form>
                            <x-ui.confirm
                                title="{{ __('Silme işlemini onayla') }}"
                                message="{{ __('Bu işlem geri alınamaz. Devam etmek istiyor musunuz?') }}"
                                confirm-text="{{ __('Evet, sil') }}"
                                cancel-text="{{ __('Vazgeç') }}"
                                variant="danger"
                                form-id="sales-order-delete-{{ $salesOrder->id }}"
                            >
                                <x-slot name="trigger">
                                    <button type="button" class="{{ $actionDangerClass }}">
                                        <x-icon.trash class="h-4 w-4" />
                                        {{ __('Sil') }}
                                    </button>
                                </x-slot>
                            </x-ui.confirm>
                        @endif
                    </x-slot>
                </x-ui.dropdown>

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
                    <p class="text-xs tracking-wide text-slate-500">{{ __('Müşteri') }}</p>
                    <p class="text-base font-medium text-slate-900">{{ $salesOrder->customer?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-slate-500">{{ __('Tekne') }}</p>
                    <p class="text-base font-medium text-slate-900">{{ $salesOrder->vessel?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-slate-500">{{ __('İş Emri') }}</p>
                    <p class="text-base font-medium text-slate-900">{{ $salesOrder->workOrder?->title ?? '-' }}</p>
                </div>
                @php
                    $statusVariants = [
                        'draft' => 'draft',
                        'confirmed' => 'confirmed',
                        'in_progress' => 'in_progress',
                        'completed' => 'completed',
                        'contracted' => 'success',
                        'cancelled' => 'cancelled',
                    ];
                @endphp
                <div>
                    <p class="text-xs tracking-wide text-slate-500">{{ __('Durum') }}</p>
                    <x-ui.badge :variant="$statusVariants[$salesOrder->status] ?? 'neutral'">
                        {{ $salesOrder->status_label }}
                    </x-ui.badge>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-slate-500">{{ __('Sipariş Tarihi') }}</p>
                    <p class="text-base font-medium text-slate-900">
                        {{ $salesOrder->order_date ? $salesOrder->order_date->format('d.m.Y') : '-' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-slate-500">{{ __('Teslim Yeri') }}</p>
                    <p class="text-base font-medium text-slate-900">{{ $salesOrder->delivery_place ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-slate-500">{{ __('Teslim Süresi') }}</p>
                    <p class="text-base font-medium text-slate-900">
                        {{ $salesOrder->delivery_days !== null ? $salesOrder->delivery_days . ' gün' : '-' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-slate-500">{{ __('Para Birimi') }}</p>
                    <p class="text-base font-medium text-slate-900">{{ $salesOrder->currency }}</p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs tracking-wide text-slate-500">{{ __('Sipariş Başlığı') }}</p>
                    <p class="text-base font-medium text-slate-900">{{ $salesOrder->title }}</p>
                </div>
                @if ($salesOrder->quote)
                    <div class="sm:col-span-2">
                        <p class="text-xs tracking-wide text-slate-500">{{ __('Kaynak Teklif') }}</p>
                        <p class="text-base font-medium text-slate-900">
                            <a href="{{ route('quotes.show', $salesOrder->quote) }}" class="text-brand-600 hover:text-brand-500 ui-focus">
                                {{ $salesOrder->quote->quote_no }}
                            </a>
                        </p>
                    </div>
                @endif
            </div>
        </x-card>

        <x-card>
            <x-slot name="header">{{ __('Koşullar') }}</x-slot>
            <div class="grid gap-4 text-sm text-slate-700 md:grid-cols-2">
                <div>
                    <p class="font-semibold text-slate-900">{{ __('Ödeme Şartları') }}</p>
                    <p class="mt-1">{{ $salesOrder->payment_terms ?: '-' }}</p>
                </div>
                <div>
                    <p class="font-semibold text-slate-900">{{ __('Garanti') }}</p>
                    <p class="mt-1">{{ $salesOrder->warranty_text ?: '-' }}</p>
                </div>
                <div>
                    <p class="font-semibold text-slate-900">{{ __('Hariçler') }}</p>
                    <p class="mt-1">{{ $salesOrder->exclusions ?: '-' }}</p>
                </div>
                <div>
                    <p class="font-semibold text-slate-900">{{ __('Notlar') }}</p>
                    <p class="mt-1">{{ $salesOrder->notes ?: '-' }}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="font-semibold text-slate-900">{{ __('Kur Notu') }}</p>
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
                $unitOptions = config('sales_orders.unit_options', []);
                $vatOptions = config('sales_orders.vat_rates', []);
                $discountTotal = (float) $salesOrder->discount_total;
                $computedGrandTotal = $salesOrder->subtotal - $discountTotal + $salesOrder->vat_total;
            @endphp

            <x-slot name="header">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <span>{{ __('Kalemler') }}</span>
                    <span class="text-xs font-normal text-slate-500">{{ __('Kalemleri satır satır yönetin.') }}</span>
                </div>
            </x-slot>

            <div class="space-y-6">
            <div class="hidden rounded-lg border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-semibold tracking-wide text-slate-500 lg:grid lg:grid-cols-12 lg:items-center">
                <div class="lg:col-span-4">{{ __('Hizmet/Ürün') }}</div>
                <div class="lg:col-span-1 text-right">{{ __('Miktar') }}</div>
                <div class="lg:col-span-1 border-l border-slate-200 pl-2">{{ __('Birim') }}</div>
                <div class="lg:col-span-2 text-right">{{ __('Br. Fiyat') }}</div>
                <div class="lg:col-span-1 text-right">{{ __('İndirim') }}</div>
                <div class="lg:col-span-1 text-right">{{ __('KDV') }}</div>
                <div class="lg:col-span-1 text-right">{{ __('Toplam') }}</div>
                    <div class="lg:col-span-1 text-right">{{ __('Sil') }}</div>
                </div>

                <div class="space-y-6">
                    @forelse ($itemsBySection as $section => $items)
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-semibold tracking-wide text-slate-500">{{ $section }}</h4>
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
                                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                        <form method="POST" action="{{ route('sales-orders.items.update', [$salesOrder, $item]) }}" class="space-y-4 lg:grid lg:grid-cols-12 lg:items-start lg:gap-4 lg:space-y-0" x-data="{ dirty: false }" @input="dirty = true" @change="dirty = true">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="form_context" value="item-{{ $item->id }}">

                                            <div class="space-y-2 lg:col-span-4">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    @if ($item->is_optional)
                                                        <x-badge variant="warning">{{ __('Opsiyon') }}</x-badge>
                                                    @endif
                                                </div>
                                                <label class="text-xs font-semibold text-slate-500 lg:sr-only">{{ __('Hizmet/Ürün') }}</label>
                                                <x-select name="item_type" class="mt-1">
                                                    @foreach ($itemTypes as $value => $label)
                                                        <option value="{{ $value }}" @selected(old('item_type', $item->item_type) === $value)>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </x-select>
                                                <p class="text-sm text-slate-600">
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
                                                <div class="mt-2 flex items-center gap-2 text-xs text-slate-600">
                                                    <input id="is_optional_{{ $item->id }}" name="is_optional" type="checkbox" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" @checked(old('is_optional', $item->is_optional))>
                                                    <label for="is_optional_{{ $item->id }}">{{ __('Opsiyon') }}</label>
                                                </div>
                                                @if ($showErrors)
                                                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                                                    <x-input-error :messages="$errors->get('item_type')" class="mt-2" />
                                                @endif
                                            </div>

                                            <div class="space-y-2 lg:col-span-1">
                                                <label class="text-xs font-semibold text-slate-500 lg:sr-only">{{ __('Miktar') }}</label>
                                                <x-input name="qty" type="text" inputmode="decimal" class="mt-1 w-full text-right tabular-nums" :value="old('qty', $item->qty)" required />
                                                @if ($showErrors)
                                                    <x-input-error :messages="$errors->get('qty')" class="mt-2" />
                                                @endif
                                            </div>

                                            <div class="space-y-2 lg:col-span-1">
                                                <label class="text-xs font-semibold text-slate-500 lg:sr-only">{{ __('Birim') }}</label>
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
                                                <label class="text-xs font-semibold text-slate-500 lg:sr-only">{{ __('Br. Fiyat') }}</label>
                                                <div class="relative">
                                                    <x-input name="unit_price" type="text" inputmode="decimal" class="mt-1 w-full pr-9 text-right tabular-nums" :value="old('unit_price', $item->unit_price)" required />
                                                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs font-semibold text-slate-400">
                                                        {{ $currencySymbol }}
                                                    </span>
                                                </div>
                                                @if ($showErrors)
                                                    <x-input-error :messages="$errors->get('unit_price')" class="mt-2" />
                                                @endif
                                            </div>

                                            <div class="space-y-2 lg:col-span-1">
                                                <label class="text-xs font-semibold text-slate-500 lg:sr-only">{{ __('İndirim') }}</label>
                                                <div class="relative">
                                                    <x-input name="discount_amount" type="text" inputmode="decimal" class="mt-1 w-full pr-9 text-right tabular-nums" :value="old('discount_amount', $item->discount_amount)" />
                                                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs font-semibold text-slate-400">
                                                        {{ $currencySymbol }}
                                                    </span>
                                                </div>
                                                @if ($showErrors)
                                                    <x-input-error :messages="$errors->get('discount_amount')" class="mt-2" />
                                                @endif
                                            </div>

                                            <div class="space-y-2 lg:col-span-1">
                                                <label class="text-xs font-semibold text-slate-500 lg:sr-only">{{ __('KDV') }}</label>
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

                                            <div class="space-y-1 text-sm font-semibold text-slate-900 lg:col-span-1 lg:text-right">
                                                <span class="text-xs font-semibold text-slate-500 lg:sr-only">{{ __('Toplam') }}</span>
                                                <div>{{ $currencySymbol }} {{ $formatMoney($lineTotal) }}</div>
                                            </div>

                                            <div class="flex flex-wrap items-center justify-end gap-2 lg:col-span-1">
                                                <x-button type="submit" size="sm" class="disabled:cursor-not-allowed disabled:opacity-50" x-bind:disabled="!dirty">
                                                    {{ __('Kaydet') }}
                                                </x-button>
                                                <x-ui.tooltip text="{{ __('Satır ekle') }}">
                                                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 text-slate-600 transition hover:border-slate-300 hover:text-slate-900" onclick="const form = document.getElementById('new-item-form'); if (form) { form.open = true; } const field = document.getElementById('new-item-description'); if (field) { field.focus(); field.scrollIntoView({ behavior: 'smooth', block: 'center' }); }" aria-label="{{ __('Satır ekle') }}">
                                                        <x-icon.plus class="h-4 w-4" />
                                                    </button>
                                                </x-ui.tooltip>
                                                <x-ui.tooltip text="{{ __('Satırı sil') }}">
                                                    <button form="delete-item-{{ $item->id }}" type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-red-200 text-red-600 transition hover:border-red-300 hover:text-red-700" aria-label="{{ __('Satırı sil') }}">
                                                        <x-icon.trash class="h-4 w-4" />
                                                    </button>
                                                </x-ui.tooltip>
                                            </div>
                                        </form>

                                        @if ($showErrors)
                                            <div class="mt-3 rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-xs text-red-600">
                                                <ul class="list-disc space-y-1 pl-4">
                                                    @foreach (['description', 'qty', 'unit', 'unit_price', 'discount_amount', 'vat_rate', 'item_type'] as $field)
                                                        @foreach ($errors->get($field) as $message)
                                                            <li>{{ $message }}</li>
                                                        @endforeach
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    </div>

                                    <form id="delete-item-{{ $item->id }}" method="POST" action="{{ route('sales-orders.items.destroy', [$salesOrder, $item]) }}">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-200 p-4 text-sm text-slate-500">
                            {{ __('Henüz kalem eklenmedi.') }}
                        </div>
                    @endforelse
                </div>

                @php
                    $showNewErrors = old('form_context') === 'new-item';
                @endphp
                <details id="new-item-form" class="rounded-xl border border-slate-100 bg-slate-50/60 p-4" @if ($showNewErrors) open @endif>
                    <summary class="cursor-pointer text-sm font-semibold text-slate-800">
                        {{ __('+ Yeni Satır Ekle') }}
                    </summary>
                    <div class="mt-4">
                        <form method="POST" action="{{ route('sales-orders.items.store', $salesOrder) }}" class="space-y-4 lg:grid lg:grid-cols-12 lg:items-start lg:gap-4 lg:space-y-0">
                            @csrf
                            <input type="hidden" name="form_context" value="new-item">

                            <div class="space-y-2 lg:col-span-4">
                                <label class="text-xs font-semibold text-slate-500 lg:sr-only">{{ __('Hizmet/Ürün') }}</label>
                                <x-select name="item_type" class="mt-1">
                                    @foreach ($itemTypes as $value => $label)
                                        <option value="{{ $value }}" @selected(old('item_type') === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </x-select>
                                <p class="text-sm text-slate-600">{{ old('description') ? \Illuminate\Support\Str::limit(old('description'), 120) : __('Detay eklenmedi.') }}</p>
                                <details class="group" @if ($showNewErrors) open @endif>
                                    <summary class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 transition hover:text-indigo-500">
                                        <span>{{ __('Detay') }}</span>
                                        <svg class="h-4 w-4 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                        </svg>
                                    </summary>
                                    <x-textarea id="new-item-description" name="description" rows="3" class="mt-2" required>{{ old('description') }}</x-textarea>
                                </details>
                                <div class="mt-2 flex items-center gap-2 text-xs text-slate-600">
                                    <input id="is_optional_new" name="is_optional" type="checkbox" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" @checked(old('is_optional'))>
                                    <label for="is_optional_new">{{ __('Opsiyon') }}</label>
                                </div>
                                @if ($showNewErrors)
                                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                                    <x-input-error :messages="$errors->get('item_type')" class="mt-2" />
                                @endif
                            </div>

                            <div class="space-y-2 lg:col-span-1">
                                <label class="text-xs font-semibold text-slate-500 lg:sr-only">{{ __('Miktar') }}</label>
                                <x-input name="qty" type="text" inputmode="decimal" class="mt-1 w-full text-right tabular-nums" :value="old('qty', 1)" required />
                                @if ($showNewErrors)
                                    <x-input-error :messages="$errors->get('qty')" class="mt-2" />
                                @endif
                            </div>

                            <div class="space-y-2 lg:col-span-1">
                                <label class="text-xs font-semibold text-slate-500 lg:sr-only">{{ __('Birim') }}</label>
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
                                <label class="text-xs font-semibold text-slate-500 lg:sr-only">{{ __('Br. Fiyat') }}</label>
                                <div class="relative">
                                    <x-input name="unit_price" type="text" inputmode="decimal" class="mt-1 w-full pr-9 text-right tabular-nums" :value="old('unit_price', 0)" required />
                                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs font-semibold text-slate-400">
                                        {{ $currencySymbol }}
                                    </span>
                                </div>
                                @if ($showNewErrors)
                                    <x-input-error :messages="$errors->get('unit_price')" class="mt-2" />
                                @endif
                            </div>

                            <div class="space-y-2 lg:col-span-1">
                                <label class="text-xs font-semibold text-slate-500 lg:sr-only">{{ __('İndirim') }}</label>
                                <div class="relative">
                                    <x-input name="discount_amount" type="text" inputmode="decimal" class="mt-1 w-full pr-9 text-right tabular-nums" :value="old('discount_amount', 0)" />
                                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs font-semibold text-slate-400">
                                        {{ $currencySymbol }}
                                    </span>
                                </div>
                                @if ($showNewErrors)
                                    <x-input-error :messages="$errors->get('discount_amount')" class="mt-2" />
                                @endif
                            </div>

                            <div class="space-y-2 lg:col-span-1">
                                <label class="text-xs font-semibold text-slate-500 lg:sr-only">{{ __('KDV') }}</label>
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

                            <div class="space-y-1 text-sm font-semibold text-slate-900 lg:col-span-1 lg:text-right">
                                <span class="text-xs font-semibold text-slate-500 lg:sr-only">{{ __('Toplam') }}</span>
                                <div>{{ $currencySymbol }} --</div>
                            </div>

                            <div class="flex flex-wrap items-center justify-end gap-2 lg:col-span-1">
                                <x-button type="submit" size="sm">{{ __('Satır Ekle') }}</x-button>
                            </div>
                        </form>

                        @if ($showNewErrors)
                            <div class="mt-3 rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-xs text-red-600">
                                <ul class="list-disc space-y-1 pl-4">
                                    @foreach (['description', 'qty', 'unit', 'unit_price', 'discount_amount', 'vat_rate', 'item_type'] as $field)
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

            <div class="grid gap-4 md:grid-cols-12 md:items-start">
                <div class="space-y-2 text-xs text-slate-500 md:col-span-8">
                    <p>
                        {{ __('Ara Toplam - İndirim + KDV') }}: {{ $currencySymbol }} {{ $formatMoney($computedGrandTotal) }}
                    </p>
                    <p>{{ __('Opsiyon kalemler toplam dışında bırakılır.') }}</p>
                </div>

                <div class="md:col-span-4 md:justify-self-end">
                    <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-4">
                        <h4 class="text-sm font-semibold text-slate-800">{{ __('Toplamlar') }}</h4>
                        <dl class="mt-3 space-y-2 text-sm text-slate-700">
                            <div class="flex items-center justify-between">
                                <dt>{{ __('Ara Toplam (KDV hariç)') }}</dt>
                                <dd class="font-semibold">{{ $currencySymbol }} {{ $formatMoney($salesOrder->subtotal) }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt>{{ __('İndirim') }}</dt>
                                <dd class="font-semibold">- {{ $currencySymbol }} {{ $formatMoney($discountTotal) }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt>{{ __('Toplam KDV') }}</dt>
                                <dd class="font-semibold">{{ $currencySymbol }} {{ $formatMoney($salesOrder->vat_total) }}</dd>
                            </div>
                            <div class="flex items-center justify-between border-t border-slate-200 pt-2 text-base">
                                <dt>{{ __('Genel Toplam') }}</dt>
                                <dd class="font-semibold text-slate-900">{{ $currencySymbol }} {{ $formatMoney($salesOrder->grand_total) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </x-card>

        <x-activity-timeline :logs="$salesOrder->activityLogs" />
    </div>
</x-app-layout>
