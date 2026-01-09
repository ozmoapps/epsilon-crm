<div class="space-y-8">
    <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-4">
        <h3 class="text-sm font-semibold tracking-wide text-gray-700">{{ __('Genel Bilgiler') }}</h3>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
                <x-input-label for="customer_id" :value="__('Müşteri')" />
                <x-select id="customer_id" name="customer_id" class="mt-1" required>
                    <option value="">{{ __('Müşteri seçin') }}</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected(old('customer_id', $salesOrder->customer_id) == $customer->id)>
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
                        <option value="{{ $vessel->id }}" @selected(old('vessel_id', $salesOrder->vessel_id) == $vessel->id)>
                            {{ $vessel->name }}{{ $vessel->customer ? ' · ' . $vessel->customer->name : '' }}
                        </option>
                    @endforeach
                </x-select>
                <x-input-error :messages="$errors->get('vessel_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="work_order_id" :value="__('İş Emri (Opsiyonel)')" />
                <x-select id="work_order_id" name="work_order_id" class="mt-1">
                    <option value="">{{ __('İş emri seçin') }}</option>
                    @foreach ($workOrders as $workOrder)
                        <option value="{{ $workOrder->id }}" @selected(old('work_order_id', $salesOrder->work_order_id) == $workOrder->id)>
                            {{ $workOrder->title }}
                        </option>
                    @endforeach
                </x-select>
                <x-input-error :messages="$errors->get('work_order_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="title" :value="__('Sipariş Başlığı')" />
                <x-input id="title" name="title" type="text" class="mt-1" :value="old('title', $salesOrder->title ?? '')" required />
                <x-input-error :messages="$errors->get('title')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="status" :value="__('Durum')" />
                <x-select id="status" name="status" class="mt-1" required>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $salesOrder->status ?? 'draft') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </x-select>
                <x-input-error :messages="$errors->get('status')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="currency" :value="__('Para Birimi')" />
                <x-input id="currency" name="currency" type="text" class="mt-1" :value="old('currency', $salesOrder->currency ?? '')" required />
                <x-input-error :messages="$errors->get('currency')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="order_date" :value="__('Sipariş Tarihi')" />
                <x-input id="order_date" name="order_date" type="date" class="mt-1" :value="old('order_date', optional($salesOrder->order_date)->format('Y-m-d'))" />
                <x-input-error :messages="$errors->get('order_date')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="delivery_place" :value="__('Teslim Yeri')" />
                <x-input id="delivery_place" name="delivery_place" type="text" class="mt-1" :value="old('delivery_place', $salesOrder->delivery_place ?? '')" />
                <x-input-error :messages="$errors->get('delivery_place')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="delivery_days" :value="__('Teslim Süresi (Gün)')" />
                <x-input id="delivery_days" name="delivery_days" type="number" min="0" class="mt-1" :value="old('delivery_days', $salesOrder->delivery_days)" />
                <x-input-error :messages="$errors->get('delivery_days')" class="mt-2" />
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-gray-100 bg-white p-4">
        <h3 class="text-sm font-semibold tracking-wide text-gray-700">{{ __('Koşullar') }}</h3>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
                <x-input-label for="payment_terms" :value="__('Ödeme Şartları')" />
                <x-textarea id="payment_terms" name="payment_terms" rows="3" class="mt-1">{{ old('payment_terms', $salesOrder->payment_terms ?? '') }}</x-textarea>
                <x-input-error :messages="$errors->get('payment_terms')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="warranty_text" :value="__('Garanti')" />
                <x-textarea id="warranty_text" name="warranty_text" rows="3" class="mt-1">{{ old('warranty_text', $salesOrder->warranty_text ?? '') }}</x-textarea>
                <x-input-error :messages="$errors->get('warranty_text')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="exclusions" :value="__('Hariçler')" />
                <x-textarea id="exclusions" name="exclusions" rows="3" class="mt-1">{{ old('exclusions', $salesOrder->exclusions ?? '') }}</x-textarea>
                <x-input-error :messages="$errors->get('exclusions')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="notes" :value="__('Notlar')" />
                <x-textarea id="notes" name="notes" rows="3" class="mt-1">{{ old('notes', $salesOrder->notes ?? '') }}</x-textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="fx_note" :value="__('Kur Notu')" />
                <x-textarea id="fx_note" name="fx_note" rows="3" class="mt-1">{{ old('fx_note', $salesOrder->fx_note ?? '') }}</x-textarea>
                <x-input-error :messages="$errors->get('fx_note')" class="mt-2" />
            </div>
        </div>
    </div>
</div>
