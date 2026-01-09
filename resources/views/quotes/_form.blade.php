<div class="space-y-8">
    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700">{{ __('Genel Bilgiler') }}</h3>
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-1">
                <x-input-label for="customer_id" :value="__('Müşteri')" />
                <select id="customer_id" name="customer_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    <option value="">{{ __('Müşteri seçin') }}</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected(old('customer_id', $quote->customer_id) == $customer->id)>
                            {{ $customer->name }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('customer_id')" class="mt-2" />
            </div>

            <div class="sm:col-span-1">
                <x-input-label for="vessel_id" :value="__('Tekne')" />
                <select id="vessel_id" name="vessel_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    <option value="">{{ __('Tekne seçin') }}</option>
                    @foreach ($vessels as $vessel)
                        <option value="{{ $vessel->id }}" @selected(old('vessel_id', $quote->vessel_id) == $vessel->id)>
                            {{ $vessel->name }}{{ $vessel->customer ? ' · ' . $vessel->customer->name : '' }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('vessel_id')" class="mt-2" />
            </div>

            <div class="sm:col-span-1">
                <x-input-label for="work_order_id" :value="__('İş Emri (Opsiyonel)')" />
                <select id="work_order_id" name="work_order_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">{{ __('İş emri seçin') }}</option>
                    @foreach ($workOrders as $workOrder)
                        <option value="{{ $workOrder->id }}" @selected(old('work_order_id', $quote->work_order_id) == $workOrder->id)>
                            {{ $workOrder->title }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('work_order_id')" class="mt-2" />
            </div>

            <div class="sm:col-span-1">
                <x-input-label for="title" :value="__('Teklif Konusu')" />
                <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $quote->title ?? '')" required />
                <x-input-error :messages="$errors->get('title')" class="mt-2" />
            </div>

            <div class="sm:col-span-1">
                <x-input-label for="status" :value="__('Durum')" />
                <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $quote->status ?? 'draft') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('status')" class="mt-2" />
            </div>

            <div class="sm:col-span-1">
                <x-input-label for="currency" :value="__('Para Birimi')" />
                <x-text-input id="currency" name="currency" type="text" class="mt-1 block w-full" :value="old('currency', $quote->currency ?? '')" required />
                <x-input-error :messages="$errors->get('currency')" class="mt-2" />
            </div>

            <div class="sm:col-span-1">
                <x-input-label for="validity_days" :value="__('Geçerlilik (Gün)')" />
                <x-text-input id="validity_days" name="validity_days" type="number" min="0" class="mt-1 block w-full" :value="old('validity_days', $quote->validity_days)" />
                <x-input-error :messages="$errors->get('validity_days')" class="mt-2" />
            </div>

            <div class="sm:col-span-1">
                <x-input-label for="estimated_duration_days" :value="__('Tahmini Süre (Gün)')" />
                <x-text-input id="estimated_duration_days" name="estimated_duration_days" type="number" min="0" class="mt-1 block w-full" :value="old('estimated_duration_days', $quote->estimated_duration_days)" />
                <x-input-error :messages="$errors->get('estimated_duration_days')" class="mt-2" />
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-100 bg-white p-4">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700">{{ __('Koşullar') }}</h3>
        <div class="mt-4 grid grid-cols-1 gap-4">
            <div>
                <x-input-label for="payment_terms" :value="__('Ödeme Şartları')" />
                <textarea id="payment_terms" name="payment_terms" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('payment_terms', $quote->payment_terms ?? '') }}</textarea>
                <x-input-error :messages="$errors->get('payment_terms')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="warranty_text" :value="__('Garanti')" />
                <textarea id="warranty_text" name="warranty_text" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('warranty_text', $quote->warranty_text ?? '') }}</textarea>
                <x-input-error :messages="$errors->get('warranty_text')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="exclusions" :value="__('Hariçler')" />
                <textarea id="exclusions" name="exclusions" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('exclusions', $quote->exclusions ?? '') }}</textarea>
                <x-input-error :messages="$errors->get('exclusions')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="notes" :value="__('Notlar')" />
                <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $quote->notes ?? '') }}</textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="fx_note" :value="__('Kur Notu')" />
                <textarea id="fx_note" name="fx_note" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('fx_note', $quote->fx_note ?? '') }}</textarea>
                <x-input-error :messages="$errors->get('fx_note')" class="mt-2" />
            </div>
        </div>
    </div>
</div>
