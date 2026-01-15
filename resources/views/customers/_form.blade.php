<div class="grid gap-6 md:grid-cols-2">
    <div>
        <x-ui.field label="İsim" name="name" required>
            <x-input id="name" name="name" type="text" :value="old('name', $customer->name ?? '')" required autofocus />
        </x-ui.field>
    </div>

    <div>
        <x-ui.field label="Telefon" name="phone">
            <x-input id="phone" name="phone" type="text" :value="old('phone', $customer->phone ?? '')" />
        </x-ui.field>
    </div>

    <div>
        <x-ui.field label="E-posta" name="email">
            <x-input id="email" name="email" type="email" :value="old('email', $customer->email ?? '')" />
        </x-ui.field>
    </div>

    <div class="md:col-span-2">
        <x-ui.field label="Adres" name="address">
            <x-input id="address" name="address" type="text" :value="old('address', $customer->address ?? '')" />
        </x-ui.field>
    </div>

    <div class="md:col-span-2">
        <x-ui.field label="Notlar" name="notes">
            <x-textarea id="notes" name="notes" rows="4">{{ old('notes', $customer->notes ?? '') }}</x-textarea>
        </x-ui.field>
    </div>
</div>
