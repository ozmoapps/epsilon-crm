<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="{{ __('Yeni Avans/Tahsilat') }}" />
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <x-ui.card>
                <div class="p-6 text-slate-900">

                    <form method="POST" action="{{ route('payments.store') }}" class="space-y-6">
                        @csrf

                        {{-- Customer --}}
                        <div>
                            <x-input-label for="customer_id" :value="__('Müşteri (Zorunlu)')" />
                            <select name="customer_id" id="customer_id" class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-xl shadow-sm ui-focus" required>
                                <option value="">{{ __('Seçiniz') }}</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('customer_id')" class="mt-2" />
                        </div>

                        {{-- Vessel (Optional) --}}
                        <div>
                            <x-input-label for="vessel_id" :value="__('Tekne (Opsiyonel)')" />
                            <select name="vessel_id" id="vessel_id" class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-xl shadow-sm ui-focus" disabled>
                                <option value="">{{ __('Önce müşteri seçiniz') }}</option>
                            </select>
                            <x-input-error :messages="$errors->get('vessel_id')" class="mt-2" />
                            <p class="text-xs text-slate-500 mt-1">{{ __('Cari ekstrede bu işlem seçili tekne altında görünecektir.') }}</p>
                        </div>

                        {{-- Bank Account --}}
                        <div>
                            <x-input-label for="bank_account_id" :value="__('Kasa / Banka Hesabı')" />
                            <select name="bank_account_id" id="bank_account_id" class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-xl shadow-sm ui-focus" required>
                                <option value="">{{ __('Seçiniz') }}</option>
                                @foreach($bankAccounts as $acc)
                                    <option value="{{ $acc->id }}" {{ old('bank_account_id') == $acc->id ? 'selected' : '' }}>
                                        {{ $acc->name }} ({{ $acc->currency->code ?? '?' }}) - {{ $acc->bank_name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('bank_account_id')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            {{-- Date --}}
                            <div>
                                <x-input-label for="payment_date" :value="__('Tarih')" />
                                <x-text-input id="payment_date" class="block mt-1 w-full rounded-xl" type="date" name="payment_date" :value="old('payment_date', now()->format('Y-m-d'))" required />
                                <x-input-error :messages="$errors->get('payment_date')" class="mt-2" />
                            </div>

                            {{-- Amount --}}
                            <div>
                                <x-input-label for="amount" :value="__('Tutar')" />
                                <x-text-input id="amount" class="block mt-1 w-full rounded-xl" type="text" name="amount" :value="old('amount')" placeholder="0.00" required />
                                <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                            </div>
                        </div>

                        {{-- Method & Ref --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="payment_method" :value="__('Ödeme Yöntemi')" />
                                <select name="payment_method" id="payment_method" class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-xl shadow-sm ui-focus">
                                    <option value="">{{ __('Belirtilmedi') }}</option>
                                    <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>{{ __('Havale / EFT') }}</option>
                                    <option value="credit_card" {{ old('payment_method') === 'credit_card' ? 'selected' : '' }}>{{ __('Kredi Kartı') }}</option>
                                    <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>{{ __('Nakit') }}</option>
                                    <option value="check" {{ old('payment_method') === 'check' ? 'selected' : '' }}>{{ __('Çek') }}</option>
                                </select>
                            </div>
                            <div>
                                <x-input-label for="reference_number" :value="__('Referans No')" />
                                <x-text-input id="reference_number" class="block mt-1 w-full rounded-xl" type="text" name="reference_number" :value="old('reference_number')" />
                            </div>
                        </div>

                        {{-- Notes --}}
                        <div>
                            <x-input-label for="notes" :value="__('Notlar')" />
                            <textarea id="notes" name="notes" class="block mt-1 w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-xl shadow-sm ui-focus" rows="3">{{ old('notes') }}</textarea>
                        </div>

                        <div class="flex items-center justify-end">
                            <x-ui.button variant="primary" class="ml-4" type="submit">
                                {{ __('Kaydet') }}
                            </x-ui.button>
                        </div>

                    </form>
                </div>
            </x-ui.card>
        </div>
    </div>

    {{-- Client-side Vessel Filtering --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const customerSelect = document.getElementById('customer_id');
            const vesselSelect = document.getElementById('vessel_id');

            // All vessels passed from controller
            const allVessels = @json($vessels);

            // V2: safer string embedding
            const oldVesselId = @json(old('vessel_id'));
            const i18n = {
                selectOptional: @json(__('Seçiniz (Opsiyonel)')),
                selectCustomerFirst: @json(__('Önce müşteri seçiniz')),
                noVesselFound: @json(__('Müşteriye ait tekne bulunamadı')),
            };

            function updateVessels() {
                const customerId = customerSelect.value;

                vesselSelect.innerHTML = `<option value="">${i18n.selectOptional}</option>`;

                if (!customerId) {
                    vesselSelect.innerHTML = `<option value="">${i18n.selectCustomerFirst}</option>`;
                    vesselSelect.disabled = true;
                    return;
                }

                const filtered = allVessels.filter(v => String(v.customer_id) === String(customerId));

                if (filtered.length === 0) {
                    vesselSelect.innerHTML = `<option value="">${i18n.noVesselFound}</option>`;
                    vesselSelect.disabled = true;
                } else {
                    vesselSelect.disabled = false;
                    filtered.forEach(v => {
                        const option = document.createElement('option');
                        option.value = v.id;
                        option.textContent = v.name;
                        if (String(v.id) === String(oldVesselId)) {
                            option.selected = true;
                        }
                        vesselSelect.appendChild(option);
                    });
                }
            }

            customerSelect.addEventListener('change', updateVessels);

            // Initial run
            updateVessels();
        });
    </script>
</x-app-layout>
