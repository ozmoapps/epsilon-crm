<div class="grid gap-6 md:grid-cols-2">
    <div>
        <x-input-label for="name" :value="__('İsim')" />
        <x-input id="name" name="name" type="text" class="mt-1" :value="old('name', $customer->name ?? '')" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="phone" :value="__('Telefon')" />
        <x-input id="phone" name="phone" type="text" class="mt-1" :value="old('phone', $customer->phone ?? '')" />
        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="email" :value="__('E-posta')" />
        <x-input id="email" name="email" type="email" class="mt-1" :value="old('email', $customer->email ?? '')" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="address" :value="__('Adres')" />
        <x-input id="address" name="address" type="text" class="mt-1" :value="old('address', $customer->address ?? '')" />
        <x-input-error :messages="$errors->get('address')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="notes" :value="__('Notlar')" />
        <x-textarea id="notes" name="notes" rows="4" class="mt-1">{{ old('notes', $customer->notes ?? '') }}</x-textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>
</div>
