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

    $customerOptions = $customers->map(fn ($customer) => [
        'value' => $customer->id,
        'label' => $customer->name,
    ])->values()->all();
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
        formId: '{{ $isEdit ? 'quote_edit_' . $quote->id : 'quote_create' }}',
        items: @json($items->values()->all()),
        issuedAt: '{{ $issuedAt }}',
        validityDays: '{{ $validityDays }}',
        currencyId: '{{ $selectedCurrency }}',
        currencyOptions: @json($currencyOptions),
        
        customerId: '{{ old('customer_id', $quote->customer_id) }}',
        vesselId: '{{ old('vessel_id', $quote->vessel_id) }}',
        contactName: '{{ old('contact_name', $quote->contact_name) }}',
        contactPhone: '{{ old('contact_phone', $quote->contact_phone) }}',
        location: '{{ old('location', $quote->location) }}',
        workOrderId: '{{ old('work_order_id', $quote->work_order_id) }}',
        
        title: '{{ old('title', $quote->title ?? '') }}',
        status: '{{ old('status', $quote->status ?? 'draft') }}',
        estimatedDurationDays: '{{ old('estimated_duration_days', $quote->estimated_duration_days) }}',
        
        paymentTerms: @json(old('payment_terms', $quote->payment_terms ?? '')),
        warrantyText: @json(old('warranty_text', $quote->warranty_text ?? '')),
        exclusions: @json(old('exclusions', $quote->exclusions ?? '')),
        notes: @json(old('notes', $quote->notes ?? '')),
        fxNote: @json(old('fx_note', $quote->fx_note ?? ''))
    })"
    x-init="init()"
    class="relative grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]"
>
    <!-- Autosave Notification -->
    <x-ui.autosave-alert show="draftFound" />

    <div class="space-y-6">
        <x-ui.card>
            <x-slot name="header">
                {{ __('Müşteri / Tekne / İletişim / Lokasyon') }}
            </x-slot>
            
            <div class="space-y-4">
                <!-- Domain Specific Customer/Vessel Picker -->
                <x-domain.customer-vessel-picker
                    customer-name="customer_id"
                    vessel-name="vessel_id"
                    :customers="$customerOptions"
                    :initial-customer-id="old('customer_id', $quote->customer_id)"
                    :initial-vessel-id="old('vessel_id', $quote->vessel_id)"
                >
                    <x-slot name="afterCustomer">
                        <div class="mt-1 flex justify-end">
                            <x-ui.button 
                                type="button" 
                                variant="ghost" 
                                size="sm"
                                class="h-auto p-0 text-brand-600 hover:text-brand-700 hover:bg-transparent"
                                x-on:click="$dispatch('open-modal', 'new-customer-modal')"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="mr-1 size-3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                {{ __('Yeni Müşteri') }}
                            </x-ui.button>
                        </div>
                    </x-slot>
                </x-domain.customer-vessel-picker>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label for="contact_name" :value="__('İletişim Kişisi')" />
                        <x-input id="contact_name" name="contact_name" type="text" class="mt-1" x-model="form.contactName" />
                        <x-input-error :messages="$errors->get('contact_name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="contact_phone" :value="__('İletişim Telefonu')" />
                        <x-input id="contact_phone" name="contact_phone" type="text" class="mt-1" x-model="form.contactPhone" />
                        <x-input-error :messages="$errors->get('contact_phone')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="location" :value="__('Lokasyon')" />
                        <x-input id="location" name="location" type="text" class="mt-1" x-model="form.location" placeholder="{{ __('Örn. Marina, bakım sahası') }}" />
                        <x-input-error :messages="$errors->get('location')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="work_order_id" :value="__('İş Emri (Opsiyonel)')" />
                        <x-select id="work_order_id" name="work_order_id" class="mt-1" x-model="form.workOrderId">
                            <option value="">{{ __('İş emri seçin') }}</option>
                            @foreach ($workOrders as $workOrder)
                                <option value="{{ $workOrder->id }}">
                                    {{ $workOrder->title }}
                                </option>
                            @endforeach
                        </x-select>
                        <x-input-error :messages="$errors->get('work_order_id')" class="mt-2" />
                    </div>
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
                    <x-input id="title" name="title" type="text" class="mt-1" x-model="form.title" required />
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="status" :value="__('Durum')" />
                    <x-select id="status" name="status" class="mt-1" x-model="form.status" required>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="currency_id" :value="__('Para Birimi')" />
                    <x-select id="currency_id" name="currency_id" class="mt-1" x-model="form.currencyId" required>
                        <option value="">{{ __('Para birimi seçin') }}</option>
                        @foreach ($currencies as $currency)
                            <option value="{{ $currency->id }}">
                                {{ $currency->code }} · {{ $currency->name }}
                            </option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('currency_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="issued_at" :value="__('Teklif Tarihi')" />
                    <x-input id="issued_at" name="issued_at" type="date" class="mt-1" x-model="form.issuedAt" required />
                    <x-input-error :messages="$errors->get('issued_at')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="validity_days" :value="__('Geçerlilik (Gün)')" />
                    <x-input id="validity_days" name="validity_days" type="number" min="0" class="mt-1" x-model="form.validityDays" />
                    <x-input-error :messages="$errors->get('validity_days')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="valid_until" :value="__('Geçerlilik Bitişi')" />
                    <x-input id="valid_until" type="text" class="mt-1 bg-slate-50" x-bind:value="validUntil" readonly />
                </div>

                <div>
                    <x-input-label for="estimated_duration_days" :value="__('Tahmini Süre (Gün)')" />
                    <x-input id="estimated_duration_days" name="estimated_duration_days" type="number" min="0" class="mt-1" x-model="form.estimatedDurationDays" />
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
                <x-textarea id="payment_terms" name="payment_terms" rows="4" class="mt-1" x-model="form.paymentTerms"></x-textarea>
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
                    <x-textarea id="warranty_text" name="warranty_text" rows="3" class="mt-1" x-model="form.warrantyText"></x-textarea>
                    <x-input-error :messages="$errors->get('warranty_text')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="exclusions" :value="__('Hariçler')" />
                    <x-textarea id="exclusions" name="exclusions" rows="3" class="mt-1" x-model="form.exclusions"></x-textarea>
                    <x-input-error :messages="$errors->get('exclusions')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="notes" :value="__('Notlar')" />
                    <x-textarea id="notes" name="notes" rows="3" class="mt-1" x-model="form.notes"></x-textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="fx_note" :value="__('Kur Notu')" />
                    <x-textarea id="fx_note" name="fx_note" rows="3" class="mt-1" x-model="form.fxNote"></x-textarea>
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
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-card">
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
                                class="inline-flex items-center gap-1 rounded-xl border border-rose-200 px-3 py-1 text-rose-600 transition hover:border-rose-300 hover:text-rose-700 ui-focus"
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
                        <x-doc.payment-instructions />

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
                                <x-ui.button type="button" class="w-full" x-on:click="onBeforeSubmit">
                                    {{ __('Kaydet') }}
                                </x-ui.button>
                            </x-slot>
                        </x-ui.confirm>
                    @else
                        <x-ui.button type="submit" class="w-full" x-on:click="onBeforeSubmit">
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

<x-modal name="new-customer-modal" title="{{ __('Yeni Müşteri Ekle') }}">
    <div x-data="newCustomerForm()" class="space-y-4">
        <div x-show="errorMessage" class="rounded-lg bg-red-50 p-3" style="display: none;">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700" x-text="errorMessage"></p>
                </div>
            </div>
        </div>

        <div>
            <x-input-label for="new_customer_name" :value="__('Müşteri Adı')" />
            <x-input 
                id="new_customer_name" 
                type="text" 
                class="mt-1 w-full" 
                x-model="form.name" 
                x-bind:class="{'border-red-300 focus:border-red-500 focus:ring-red-500': errors.name}"
            />
            <p x-show="errors.name" class="mt-1 text-sm text-red-600" x-text="errors.name && errors.name[0]"></p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="new_customer_phone" :value="__('Telefon')" />
                <x-input id="new_customer_phone" type="text" class="mt-1 w-full" x-model="form.phone" />
                <p x-show="errors.phone" class="mt-1 text-sm text-red-600" x-text="errors.phone && errors.phone[0]"></p>
            </div>
            <div>
                <x-input-label for="new_customer_email" :value="__('E-posta')" />
                <x-input id="new_customer_email" type="email" class="mt-1 w-full" x-model="form.email" />
                <p x-show="errors.email" class="mt-1 text-sm text-red-600" x-text="errors.email && errors.email[0]"></p>
            </div>
        </div>

        <div>
            <x-input-label for="new_customer_address" :value="__('Adres')" />
            <x-textarea id="new_customer_address" rows="2" class="mt-1 w-full" x-model="form.address"></x-textarea>
            <p x-show="errors.address" class="mt-1 text-sm text-red-600" x-text="errors.address && errors.address[0]"></p>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <x-ui.button type="button" variant="secondary" x-on:click="$dispatch('close-modal', 'new-customer-modal')">
                {{ __('İptal') }}
            </x-ui.button>
            <x-ui.button type="button" x-on:click="submit" x-bind:disabled="loading" class="relative">
                <span x-show="!loading">{{ __('Kaydet') }}</span>
                <span x-show="loading" class="opacity-0">{{ __('Kaydet') }}</span>
                <span x-show="loading" class="absolute inset-0 flex items-center justify-center">
                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </span>
            </x-ui.button>
        </div>
    </div>
</x-modal>


<script>
    function quoteForm(config) {
        const formatter = new Intl.NumberFormat('tr-TR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });

        return {
            storageKey: `autosave_${config.formId}`,
            draftFound: false,
            restoring: false,
            
            items: (config.items || []).map((item, index) => ({
                key: item.id ? `item-${item.id}` : `new-${index}-${Date.now()}`,
                id: item.id || null,
                title: item.title || '',
                description: item.description || '',
                amount: item.amount || '',
                vat_rate: item.vat_rate || '',
            })),
            
            // Group fields to avoid ID collisions
            form: {
                issuedAt: config.issuedAt || '',
                validityDays: config.validityDays ?? '',
                currencyId: config.currencyId || '',
                customerId: config.customerId || '',
                vesselId: config.vesselId || '',
                contactName: config.contactName || '',
                contactPhone: config.contactPhone || '',
                location: config.location || '',
                workOrderId: config.workOrderId || '',
                title: config.title || '',
                status: config.status || '',
                estimatedDurationDays: config.estimatedDurationDays || '',
                paymentTerms: config.paymentTerms || '',
                warrantyText: config.warrantyText || '',
                exclusions: config.exclusions || '',
                notes: config.notes || '',
                fxNote: config.fxNote || '',
            },

            currencyOptions: config.currencyOptions || {},

            init() {
                if (this.items.length === 0) {
                    this.addItem();
                }
                
                // Autosave Check
                this.checkDraft();
                
                // Watch for changes
                this.$watch('items', () => this.saveDraft());
                this.$watch('form', () => this.saveDraft());
            },
            
            checkDraft() {
                if (localStorage.getItem(this.storageKey)) {
                    this.draftFound = true;
                }
            },
            
            saveDraft: Alpine.debounce(function() {
                if (this.restoring || this.draftFound) return;
                
                const data = {
                    items: this.items,
                    ...this.form,
                    timestamp: Date.now()
                };
                
                localStorage.setItem(this.storageKey, JSON.stringify(data));
            }, 2000),
            
            restoreDraft() {
                this.restoring = true;
                try {
                    const data = JSON.parse(localStorage.getItem(this.storageKey));
                    if (!data) return;
                    
                    // Restore form fields
                    this.form.issuedAt = data.issuedAt;
                    this.form.validityDays = data.validityDays;
                    this.form.currencyId = data.currencyId;
                    this.form.customerId = data.customerId;
                    this.form.vesselId = data.vesselId;
                    this.form.contactName = data.contactName;
                    this.form.contactPhone = data.contactPhone;
                    this.form.location = data.location;
                    this.form.workOrderId = data.workOrderId;
                    this.form.title = data.title;
                    this.form.status = data.status;
                    this.form.estimatedDurationDays = data.estimatedDurationDays;
                    this.form.paymentTerms = data.paymentTerms;
                    this.form.warrantyText = data.warrantyText;
                    this.form.exclusions = data.exclusions;
                    this.form.notes = data.notes;
                    this.form.fxNote = data.fxNote;
                    
                    // Restore items
                    if (Array.isArray(data.items)) {
                        this.items = data.items.map(item => ({
                             ...item,
                             key: item.key || `restored-${Date.now()}-${Math.random()}`
                        }));
                    }
                    
                    // Sync Combobox
                    this.$dispatch('set-combobox-value', { name: 'customer_id', value: this.form.customerId });
                    
                    this.draftFound = false;
                } catch(e) {
                    console.error('Failed to restore draft', e);
                }
                
                this.$nextTick(() => {
                    this.restoring = false;
                });
            },
            
            discardDraft() {
                localStorage.removeItem(this.storageKey);
                this.draftFound = false;
            },
            
            onBeforeSubmit() {
                this.discardDraft();
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
                        str = str.replace(/\./g, '').replace(',', '.');
                    } else {
                        str = str.replace(/,/g, '');
                    }
                } else if (hasComma) {
                    str = str.replace(/\./g, '').replace(',', '.');
                } else if (hasDot) {
                    // ok
                }

                const parsed = Number.parseFloat(str);
                return Number.isNaN(parsed) ? 0 : parsed;
            },
            get currencySymbol() {
                const option = this.currencyOptions[this.form.currencyId];
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
                if (!this.form.issuedAt || this.form.validityDays === '' || this.form.validityDays === null || this.form.validityDays === undefined) {
                    return '';
                }
                const baseDate = new Date(this.form.issuedAt);
                if (Number.isNaN(baseDate.getTime())) {
                    return '';
                }
                const days = Number.parseInt(this.form.validityDays, 10);
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

    function newCustomerForm() {
        return {
            form: {
                name: '',
                phone: '',
                email: '',
                address: '',
            },
            errors: {},
            errorMessage: null,
            loading: false,

            submit() {
                this.loading = true;
                this.errors = {};
                this.errorMessage = null;

                fetch('{{ route('customers.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(this.form)
                })
                .then(response => response.json().then(data => ({ status: response.status, data })))
                .then(({ status, data }) => {
                    this.loading = false;
                    
                    if (status === 200 || status === 201) {
                         this.$dispatch('close-modal', 'new-customer-modal');
                         this.form = { name: '', phone: '', email: '', address: '' };
                         const newOption = {
                             value: data.customer.id,
                             label: data.customer.name
                         };
                         this.$dispatch('combobox-new-option-added', {
                             key: 'customer_id',
                             option: newOption
                         });
                    } else if (status === 422) {
                        this.errors = data.errors || {};
                        this.errorMessage = data.message || '{{ __('Lütfen formdaki hataları kontrol edin.') }}';
                    } else {
                        this.errorMessage = data.message || '{{ __('Bir hata oluştu. Lütfen tekrar deneyin.') }}';
                    }
                })
                .catch(error => {
                    this.loading = false;
                    this.errorMessage = '{{ __('Bir ağ hatası oluştu.') }}';
                    console.error('Error:', error);
                });
            }
        };
    }
</script>
