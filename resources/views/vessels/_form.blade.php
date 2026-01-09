<div class="space-y-6">
    <div>
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
        <x-input-label for="registration_number" :value="__('Ruhsat Numarası')" />
        <x-text-input id="registration_number" name="registration_number" type="text" class="mt-1 block w-full" :value="old('registration_number', $vessel->registration_number ?? '')" />
        <x-input-error :messages="$errors->get('registration_number')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="notes" :value="__('Notlar')" />
        <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $vessel->notes ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>
</div>
