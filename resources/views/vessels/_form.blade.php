<div class="space-y-8">
    <div class="space-y-4">
        <div>
            <h3 class="text-base font-semibold text-slate-900">{{ __('Genel Bilgiler') }}</h3>
            <p class="text-sm text-slate-500">{{ __('Tekneye ait temel bilgileri girin.') }}</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-ui.field label="Müşteri" name="customer_id" required>
                    <x-select id="customer_id" name="customer_id" class="mt-1" required>
                        <option value="">{{ __('Müşteri seçin') }}</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}"
                                @selected(old('customer_id', $vessel->customer_id ?? request('customer_id')) == $customer->id)>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </x-select>
                </x-ui.field>
            </div>

            <div>
                <x-ui.field label="Tekne Adı" name="name" required>
                    <x-input id="name" name="name" type="text" class="mt-1" :value="old('name', $vessel->name ?? '')" required />
                </x-ui.field>
            </div>

            <div>
                <x-ui.field label="Tekne Tipi" name="boat_type">
                    <x-select id="boat_type" name="boat_type" class="mt-1">
                        <option value="">{{ __('Seçiniz') }}</option>
                        @foreach (config('vessels.boat_types', []) as $key => $label)
                            <option value="{{ $key }}" @selected(old('boat_type', $vessel->boat_type ?? '') === $key)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </x-select>
                </x-ui.field>
            </div>

            <div>
                <x-ui.field label="Marka" name="type">
                    <x-input id="type" name="type" type="text" class="mt-1" :value="old('type', $vessel->type ?? '')" />
                </x-ui.field>
            </div>

            <div>
                <x-ui.field label="Model" name="registration_number">
                    <x-input id="registration_number" name="registration_number" type="text" class="mt-1" :value="old('registration_number', $vessel->registration_number ?? '')" />
                </x-ui.field>
            </div>

            <div>
                <x-ui.field label="Gövde Malzemesi" name="material">
                    <x-select id="material" name="material" class="mt-1">
                        <option value="">{{ __('Seçiniz') }}</option>
                        @foreach (config('vessels.materials', []) as $key => $label)
                            <option value="{{ $key }}" @selected(old('material', $vessel->material ?? '') === $key)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </x-select>
                </x-ui.field>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div>
            <h3 class="text-base font-semibold text-slate-900">{{ __('Boyut, Ağırlık ve Kapasite Bilgileri') }}</h3>
            <p class="text-sm text-slate-500">{{ __('Teknenin ölçü ve tonaj detaylarını belirtin.') }}</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div>
                <x-ui.field label="LOA (m)" name="loa_m">
                    <x-input id="loa_m" name="loa_m" type="number" step="0.01" class="mt-1" :value="old('loa_m', $vessel->loa_m ?? '')" />
                </x-ui.field>
            </div>

            <div>
                <x-ui.field label="Beam (m)" name="beam_m">
                    <x-input id="beam_m" name="beam_m" type="number" step="0.01" class="mt-1" :value="old('beam_m', $vessel->beam_m ?? '')" />
                </x-ui.field>
            </div>

            <div>
                <x-ui.field label="Draft (m)" name="draft_m">
                    <x-input id="draft_m" name="draft_m" type="number" step="0.01" class="mt-1" :value="old('draft_m', $vessel->draft_m ?? '')" />
                </x-ui.field>
            </div>

            <div>
                <x-ui.field label="Net Tonaj" name="net_tonnage">
                    <x-input id="net_tonnage" name="net_tonnage" type="number" step="0.01" class="mt-1" :value="old('net_tonnage', $vessel->net_tonnage ?? '')" />
                </x-ui.field>
            </div>

            <div>
                <x-ui.field label="Brüt Tonaj" name="gross_tonnage">
                    <x-input id="gross_tonnage" name="gross_tonnage" type="number" step="0.01" class="mt-1" :value="old('gross_tonnage', $vessel->gross_tonnage ?? '')" />
                </x-ui.field>
            </div>

            <div>
                <x-ui.field label="Yolcu Kapasitesi" name="passenger_capacity">
                    <x-input id="passenger_capacity" name="passenger_capacity" type="number" step="1" class="mt-1" :value="old('passenger_capacity', $vessel->passenger_capacity ?? '')" />
                </x-ui.field>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div>
            <h3 class="text-base font-semibold text-slate-900">{{ __('Diğer Bilgiler ve Notlar') }}</h3>
            <p class="text-sm text-slate-500">{{ __('Ek açıklamalarınızı girin.') }}</p>
        </div>

        <div>
            <x-ui.field label="Notlar" name="notes">
                <x-textarea id="notes" name="notes" rows="4" class="mt-1">{{ old('notes', $vessel->notes ?? '') }}</x-textarea>
            </x-ui.field>
        </div>
    </div>
</div>
