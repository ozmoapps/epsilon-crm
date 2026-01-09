<div class="space-y-6">
    <div>
        <x-input-label for="name" :value="__('İsim')" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $customer->name ?? '')" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="phone" :value="__('Telefon')" />
        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $customer->phone ?? '')" />
        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="email" :value="__('E-posta')" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $customer->email ?? '')" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="address" :value="__('Adres')" />
        <x-text-input id="address" name="address" type="text" class="mt-1 block w-full" :value="old('address', $customer->address ?? '')" />
        <x-input-error :messages="$errors->get('address')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="notes" :value="__('Notlar')" />
        <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $customer->notes ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>
</div>
