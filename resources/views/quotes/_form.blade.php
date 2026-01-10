@php
    $items = collect(old('items', $quote->items->map(function ($item) {
        return [
            'id' => $item->id,
            'title' => $item->section,
            'description' => $item->description,
            'amount' => (string) $item->unit_price,
            'vat_rate' => $item->vat_rate !== null ? (string) $item->vat_rate : null,
        ];
    })->values()->all()));

    $currencyOptions = $currencies->mapWithKeys(fn ($currency) => [
        $currency->id => [
            'code' => $currency->code,
            'symbol' => $currency->symbol ?? $currency->code,
        ],
    ]);

    $issuedAt = old('issued_at', optional($quote->issued_at)->toDateString() ?? now()->toDateString());
    $validityDays = old('validity_days', $quote->validity_days ?? config('quotes.default_validity_days'));
    $selectedCurrency = old('currency_id', $quote->currency_id);
    $isEdit = $quote->exists;
@endphp

@if ($errors->any())
    <div class="mb-4">
        <x-ui.alert type="danger" title="{{ __('Formu kaydedemedik') }}">
            <p>{{ __('Lütfen aşağıdaki alanları kontrol edin.') }}</p>
            <ul class="mt-2 list-disc space-y-1 pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    </div>
@endif

<div
    x-data="quoteForm({
        items: @json($items->values()->all()),
        issuedAt: '{{ $issuedAt }}',
        validityDays: '{{ $validityDays }}',
        currencyId: '{{ $selectedCurrency }}',
        currencyOptions: @json($currencyOptions),
    })"
    x-init="init()"
    class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]"
>
    <div class="space-y-6">
        <x-ui.card>
            <x-slot name="header">
                {{ __('Müşteri / Tekne / İletişim / Lokasyon') }}
            </x-slot>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="customer_id" :value="__('Müşteri')" />
                    <x-select id="customer_id" name="customer_id" class="mt-1" required>
                        <option value="">{{ __('Müşteri seçin') }}</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(old('customer_id', $quote->customer_id) == $customer->id)>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('customer_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="vessel_id" :value="__('Tekne')" />
                    <x-select id="vessel_id" name="vessel_id" class="mt-1" required>
                        <option value="">{{ __('Tekne seçin') }}</option>
                        @foreach ($vessels as $vessel)
                            <option value="{{ $vessel->id }}" @selected(old('vessel_id', $quote->vessel_id) == $vessel->id)>
                                {{ $vessel->name }}{{ $vessel->customer ? ' · ' . $vessel->customer->name : '' }}
                            </option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('vessel_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="contact_name" :value="__('İletişim Kişisi')" />
                    <x-input id="contact_name" name="contact_name" type="text" class="mt-1" :value="old('contact_name', $quote->contact_name)" />
                    <x-input-error :messages="$errors->get('contact_name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="contact_phone" :value="__('İletişim Telefonu')" />
                    <x-input id="contact_phone" name="contact_phone" type="text" class="mt-1" :value="old('contact_phone', $quote->contact_phone)" />
                    <x-input-error :messages="$errors->get('contact_phone')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="location" :value="__('Lokasyon')" />
                    <x-input id="location" name="location" type="text" class="mt-1" :value="old('location', $quote->location)" placeholder="{{ __('Örn. Marina, bakım sahası') }}" />
                    <x-input-error :messages="$errors->get('location')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="work_order_id" :value="__('İş Emri (Opsiyonel)')" />
                    <x-select id="work_order_id" name="work_order_id" class="mt-1">
                        <option value="">{{ __('İş emri seçin') }}</option>
                        @foreach ($workOrders as $workOrder)
                            <option value="{{ $workOrder->id }}" @selected(old('work_order_id', $quote->work_order_id) == $workOrder->id)>
                                {{ $workOrder->title }}
                            </option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('work_order_id')" class="mt-2" />
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <x-slot name="header">
                {{ __('Teklif Meta') }}
            </x-slot>
            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-input-label for="title" :value="__('Teklif Konusu')" />
                    <x-input id="title" name="title" type="text" class="mt-1" :value="old('title', $quote->title ?? '')" required />
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="status" :value="__('Durum')" />
                    <x-select id="status" name="status" class="mt-1" required>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $quote->status ?? 'draft') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="currency_id" :value="__('Para Birimi')" />
                    <x-select id="currency_id" name="currency_id" class="mt-1" x-model="currencyId" required>
                        <option value="">{{ __('Para birimi seçin') }}</option>
                        @foreach ($currencies as $currency)
                            <option value="{{ $currency->id }}" @selected(old('currency_id', $quote->currency_id) == $currency->id)>
                                {{ $currency->code }} · {{ $currency->name }}
                            </option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('currency_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="issued_at" :value="__('Teklif Tarihi')" />
                    <x-input id="issued_at" name="issued_at" type="date" class="mt-1" :value="$issuedAt" x-model="issuedAt" required />
                    <x-input-error :messages="$errors->get('issued_at')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="validity_days" :value="__('Geçerlilik (Gün)')" />
                    <x-input id="validity_days" name="validity_days" type="number" min="0" class="mt-1" :value="$validityDays" x-model="validityDays" />
                    <x-input-error :messages="$errors->get('validity_days')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="valid_until" :value="__('Geçerlilik Bitişi')" />
                    <x-input id="valid_until" type="text" class="mt-1 bg-slate-50" x-bind:value="validUntil" readonly />
                </div>

                <div>
                    <x-input-label for="estimated_duration_days" :value="__('Tahmini Süre (Gün)')" />
                    <x-input id="estimated_duration_days" name="estimated_duration_days" type="number" min="0" class="mt-1" :value="old('estimated_duration_days', $quote->estimated_duration_days)" />
                    <x-input-error :messages="$errors->get('estimated_duration_days')" class="mt-2" />
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <x-slot name="header">
                {{ __('Ödeme Şartları') }}
            </x-slot>
            <div>
                <x-input-label for="payment_terms" :value="__('Ödeme Şartları')" />
                <x-textarea id="payment_terms" name="payment_terms" rows="4" class="mt-1">{{ old('payment_terms', $quote->payment_terms ?? '') }}</x-textarea>
                <x-input-error :messages="$errors->get('payment_terms')" class="mt-2" />
            </div>
        </x-ui.card>

        <x-ui.card>
            <x-slot name="header">
                {{ __('Ek Notlar') }}
            </x-slot>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="warranty_text" :value="__('Garanti')" />
                    <x-textarea id="warranty_text" name="warranty_text" rows="3" class="mt-1">{{ old('warranty_text', $quote->warranty_text ?? '') }}</x-textarea>
                    <x-input-error :messages="$errors->get('warranty_text')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="exclusions" :value="__('Hariçler')" />
                    <x-textarea id="exclusions" name="exclusions" rows="3" class="mt-1">{{ old('exclusions', $quote->exclusions ?? '') }}</x-textarea>
                    <x-input-error :messages="$errors->get('exclusions')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="notes" :value="__('Notlar')" />
                    <x-textarea id="notes" name="notes" rows="3" class="mt-1">{{ old('notes', $quote->notes ?? '') }}</x-textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="fx_note" :value="__('Kur Notu')" />
                    <x-textarea id="fx_note" name="fx_note" rows="3" class="mt-1">{{ old('fx_note', $quote->fx_note ?? '') }}</x-textarea>
                    <x-input-error :messages="$errors->get('fx_note')" class="mt-2" />
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <x-slot name="header">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-700">{{ __('Kalemler') }}</p>
                        <p class="text-xs text-slate-500">{{ __('Başlık, açıklama ve tutar bilgilerini ekleyin.') }}</p>
                    </div>
                    <x-ui.button type="button" variant="secondary" x-on:click="addItem">
                        {{ __('Satır Ekle') }}
                    </x-ui.button>
                </div>
            </x-slot>

            <div class="space-y-4">
                <template x-if="items.length === 0">
                    <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                        {{ __('Henüz kalem eklenmedi.') }}
                    </div>
                </template>

                <template x-for="(item, index) in items" :key="item.key">
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="grid gap-4 md:grid-cols-12">
                            <div class="md:col-span-3">
                                <label class="text-xs font-semibold text-slate-600" x-bind:for="`item-title-${item.key}`">{{ __('Başlık') }}</label>
                                <x-input
                                    x-bind:id="`item-title-${item.key}`"
                                    x-bind:name="`items[${index}][title]`"
                                    x-model="item.title"
                                    type="text"
                                    class="mt-1"
                                    required
                                />
                            </div>
                            <div class="md:col-span-5">
                                <label class="text-xs font-semibold text-slate-600" x-bind:for="`item-description-${item.key}`">{{ __('Açıklama') }}</label>
                                <x-textarea
                                    x-bind:id="`item-description-${item.key}`"
                                    x-bind:name="`items[${index}][description]`"
                                    x-model="item.description"
                                    rows="2"
                                    class="mt-1"
                                    required
                                ></x-textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-xs font-semibold text-slate-600" x-bind:for="`item-amount-${item.key}`">{{ __('Tutar') }}</label>
                                <div class="relative">
                                    <x-input
                                        x-bind:id="`item-amount-${item.key}`"
                                        x-bind:name="`items[${index}][amount]`"
                                        x-model="item.amount"
                                        type="text"
                                        inputmode="decimal"
                                        class="mt-1 w-full pr-10 text-right tabular-nums"
                                        required
                                    />
                                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs font-semibold text-slate-400" x-text="currencySymbol"></span>
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-xs font-semibold text-slate-600" x-bind:for="`item-vat-${item.key}`">{{ __('KDV (%)') }}</label>
                                <x-input
                                    x-bind:id="`item-vat-${item.key}`"
                                    x-bind:name="`items[${index}][vat_rate]`"
                                    x-model="item.vat_rate"
                                    type="text"
                                    inputmode="decimal"
                                    class="mt-1 w-full text-right tabular-nums"
                                    placeholder="{{ __('Opsiyonel') }}"
                                />
                            </div>
                        </div>
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500">
                            <span x-text="lineSummary(item)"></span>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 rounded-lg border border-rose-200 px-3 py-1 text-rose-600 transition hover:border-rose-300 hover:text-rose-700 ui-focus"
                                x-on:click="removeItem(index)"
                                aria-label="{{ __('Satırı kaldır') }}"
                            >
                                {{ __('Satırı kaldır') }}
                            </button>
                        </div>
                        <input type="hidden" x-bind:name="`items[${index}][id]`" x-model="item.id">
                    </div>
                </template>

                <div class="flex items-center justify-end">
                    <x-ui.button type="button" variant="ghost" x-on:click="addItem">
                        {{ __('+ Yeni satır ekle') }}
                    </x-ui.button>
                </div>
            </div>
        </x-ui.card>
    </div>

    <aside class="space-y-6">
        <x-ui.card class="sticky top-6">
            <x-slot name="header">
                {{ __('Toplamlar') }}
            </x-slot>
            <div class="space-y-4 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-slate-600">{{ __('Ara Toplam') }}</span>
                    <span class="font-semibold text-slate-900" x-text="formatMoney(subtotal) + ' ' + currencySymbol"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-600">{{ __('KDV (Opsiyonel)') }}</span>
                    <span class="font-semibold text-slate-900" x-text="formatMoney(taxTotal) + ' ' + currencySymbol"></span>
                </div>
                <div class="border-t border-slate-200 pt-3">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-700">{{ __('Genel Toplam') }}</span>
                        <span class="text-base font-semibold text-slate-900" x-text="formatMoney(grandTotal) + ' ' + currencySymbol"></span>
                    </div>
                </div>

                <div class="space-y-2">
                    @if ($isEdit)
                        <x-ui.confirm
                            title="{{ __('Değişiklikleri kaydet') }}"
                            message="{{ __('Yaptığınız değişiklikler kaydedilecek. Devam edilsin mi?') }}"
                            confirm-text="{{ __('Kaydet') }}"
                            cancel-text="{{ __('Vazgeç') }}"
                            variant="primary"
                            form-id="{{ $formId }}"
                        >
                            <x-slot name="trigger">
                                <x-ui.button type="button" class="w-full">
                                    {{ __('Kaydet') }}
                                </x-ui.button>
                            </x-slot>
                        </x-ui.confirm>
                    @else
                        <x-ui.button type="submit" class="w-full">
                            {{ __('Kaydet') }}
                        </x-ui.button>
                    @endif

                    @if ($isEdit)
                        <x-ui.button href="{{ route('quotes.preview', $quote) }}" variant="secondary" class="w-full">
                            {{ __('Önizle') }}
                        </x-ui.button>
                        <x-ui.button href="{{ route('quotes.pdf', $quote) }}" variant="secondary" class="w-full">
                            {{ __('Yazdır/PDF') }}
                        </x-ui.button>
                    @else
                        <x-ui.button variant="secondary" class="w-full" disabled>
                            {{ __('Önizle') }}
                        </x-ui.button>
                        <x-ui.button variant="secondary" class="w-full" disabled>
                            {{ __('Yazdır/PDF') }}
                        </x-ui.button>
                    @endif
                </div>
            </div>
        </x-ui.card>
    </aside>
</div>


<script>
    function quoteForm(config) {
        const formatter = new Intl.NumberFormat('tr-TR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });

        return {
            items: (config.items || []).map((item, index) => ({
                key: item.id ? `item-${item.id}` : `new-${index}-${Date.now()}`,
                id: item.id || null,
                title: item.title || '',
                description: item.description || '',
                amount: item.amount || '',
                vat_rate: item.vat_rate || '',
            })),
            issuedAt: config.issuedAt || '',
            validityDays: config.validityDays ?? '',
            currencyId: config.currencyId || '',
            currencyOptions: config.currencyOptions || {},
            init() {
                if (this.items.length === 0) {
                    this.addItem();
                }
            },
            addItem() {
                this.items.push({
                    key: `new-${this.items.length}-${Date.now()}`,
                    id: null,
                    title: '',
                    description: '',
                    amount: '',
                    vat_rate: '',
                });
            },
            removeItem(index) {
                this.items.splice(index, 1);
            },
            parseNumber(value) {
                if (value === null || value === undefined || value === '') {
                    return 0;
                }
                let str = String(value);
                const hasDot = str.indexOf('.') !== -1;
                const hasComma = str.indexOf(',') !== -1;

                if (hasDot && hasComma) {
                    const lastDot = str.lastIndexOf('.');
                    const lastComma = str.lastIndexOf(',');
                    if (lastComma > lastDot) {
                        // TR: 1.250,50 -> remove dots, comma to dot
                        str = str.replace(/\./g, '').replace(',', '.');
                    } else {
                        // EN: 1,250.50 -> remove commas
                        str = str.replace(/,/g, '');
                    }
                } else if (hasComma) {
                    // Only comma: 1250,50 -> comma to dot
                    str = str.replace(/\./g, '').replace(',', '.');
                } else if (hasDot) {
                    // Only dot: 1250.50 -> keep as is (standard js float)
                }

                const parsed = Number.parseFloat(str);
                return Number.isNaN(parsed) ? 0 : parsed;
            },
            get currencySymbol() {
                const option = this.currencyOptions[this.currencyId];
                return option ? option.symbol : '';
            },
            get subtotal() {
                return this.items.reduce((sum, item) => sum + this.parseNumber(item.amount), 0);
            },
            get taxTotal() {
                return this.items.reduce((sum, item) => {
                    const amount = this.parseNumber(item.amount);
                    const rate = this.parseNumber(item.vat_rate);
                    if (!rate) {
                        return sum;
                    }
                    return sum + (amount * rate) / 100;
                }, 0);
            },
            get grandTotal() {
                return this.subtotal + this.taxTotal;
            },
            formatMoney(value) {
                return formatter.format(value || 0);
            },
            get validUntil() {
                if (!this.issuedAt || this.validityDays === '' || this.validityDays === null || this.validityDays === undefined) {
                    return '';
                }
                const baseDate = new Date(this.issuedAt);
                if (Number.isNaN(baseDate.getTime())) {
                    return '';
                }
                const days = Number.parseInt(this.validityDays, 10);
                if (Number.isNaN(days)) {
                    return '';
                }
                baseDate.setDate(baseDate.getDate() + days);
                return baseDate.toLocaleDateString('tr-TR');
            },
            lineSummary(item) {
                const amount = this.parseNumber(item.amount);
                const vat = this.parseNumber(item.vat_rate);
                if (vat > 0) {
                    const total = amount + (amount * vat) / 100;
                    return `${this.formatMoney(total)} ${this.currencySymbol} (KDV %${vat})`;
                }
                return `${this.formatMoney(amount)} ${this.currencySymbol}`;
            },
        };
    }
</script>
