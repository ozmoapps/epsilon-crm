<div class="space-y-8">
    <div class="space-y-4">
        <div>
            <h3 class="text-base font-semibold text-gray-900">{{ __('Genel Bilgiler') }}</h3>
            <p class="text-sm text-gray-500">{{ __('Tekneye ait temel bilgileri girin.') }}</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-input-label for="customer_id" :value="__('Müşteri')" />
                <select id="customer_id" name="customer_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    <option value="">{{ __('Müşteri seçin') }}</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}"
                            @selected(old('customer_id', $vessel->customer_id ?? request('customer_id')) == $customer->id)>
                            {{ $customer->name }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('customer_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="name" :value="__('Tekne Adı')" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $vessel->name ?? '')" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="type" :value="__('Tekne Tipi')" />
                <x-text-input id="type" name="type" type="text" class="mt-1 block w-full" :value="old('type', $vessel->type ?? '')" />
                <x-input-error :messages="$errors->get('type')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="boat_type" :value="__('Tekne Sınıfı')" />
                <select id="boat_type" name="boat_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">{{ __('Seçiniz') }}</option>
                    @foreach (config('vessels.boat_types', []) as $key => $label)
                        <option value="{{ $key }}" @selected(old('boat_type', $vessel->boat_type ?? '') === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('boat_type')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="material" :value="__('Gövde Malzemesi')" />
                <select id="material" name="material" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">{{ __('Seçiniz') }}</option>
                    @foreach (config('vessels.materials', []) as $key => $label)
                        <option value="{{ $key }}" @selected(old('material', $vessel->material ?? '') === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('material')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="registration_number" :value="__('Ruhsat Numarası')" />
                <x-text-input id="registration_number" name="registration_number" type="text" class="mt-1 block w-full" :value="old('registration_number', $vessel->registration_number ?? '')" />
                <x-input-error :messages="$errors->get('registration_number')" class="mt-2" />
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div>
            <h3 class="text-base font-semibold text-gray-900">{{ __('Boyut, Ağırlık ve Kapasite Bilgileri') }}</h3>
            <p class="text-sm text-gray-500">{{ __('Teknenin ölçü ve tonaj detaylarını belirtin.') }}</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <x-input-label for="loa_m" :value="__('LOA (m)')" />
                <x-text-input id="loa_m" name="loa_m" type="number" step="0.01" class="mt-1 block w-full" :value="old('loa_m', $vessel->loa_m ?? '')" />
                <x-input-error :messages="$errors->get('loa_m')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="beam_m" :value="__('Beam (m)')" />
                <x-text-input id="beam_m" name="beam_m" type="number" step="0.01" class="mt-1 block w-full" :value="old('beam_m', $vessel->beam_m ?? '')" />
                <x-input-error :messages="$errors->get('beam_m')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="draft_m" :value="__('Draft (m)')" />
                <x-text-input id="draft_m" name="draft_m" type="number" step="0.01" class="mt-1 block w-full" :value="old('draft_m', $vessel->draft_m ?? '')" />
                <x-input-error :messages="$errors->get('draft_m')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="net_tonnage" :value="__('Net Tonaj')" />
                <x-text-input id="net_tonnage" name="net_tonnage" type="number" step="0.01" class="mt-1 block w-full" :value="old('net_tonnage', $vessel->net_tonnage ?? '')" />
                <x-input-error :messages="$errors->get('net_tonnage')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="gross_tonnage" :value="__('Brüt Tonaj')" />
                <x-text-input id="gross_tonnage" name="gross_tonnage" type="number" step="0.01" class="mt-1 block w-full" :value="old('gross_tonnage', $vessel->gross_tonnage ?? '')" />
                <x-input-error :messages="$errors->get('gross_tonnage')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="passenger_capacity" :value="__('Yolcu Kapasitesi')" />
                <x-text-input id="passenger_capacity" name="passenger_capacity" type="number" step="1" class="mt-1 block w-full" :value="old('passenger_capacity', $vessel->passenger_capacity ?? '')" />
                <x-input-error :messages="$errors->get('passenger_capacity')" class="mt-2" />
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div>
            <h3 class="text-base font-semibold text-gray-900">{{ __('Diğer Bilgiler ve Notlar') }}</h3>
            <p class="text-sm text-gray-500">{{ __('Ek açıklamalarınızı girin.') }}</p>
        </div>

        <div>
            <x-input-label for="notes" :value="__('Notlar')" />
            <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $vessel->notes ?? '') }}</textarea>
            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
        </div>
    </div>
</div>
