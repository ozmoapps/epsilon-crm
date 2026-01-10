@php
    $isEdit = $companyProfile->exists;
@endphp

<div class="grid gap-6 lg:grid-cols-2">
    <div>
        <x-input-label for="name" :value="__('Şirket Adı')" />
        <x-input id="name" name="name" type="text" class="mt-1" :value="old('name', $companyProfile->name)" required />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>
    <div>
        <x-input-label for="tax_no" :value="__('Vergi Numarası')" />
        <x-input id="tax_no" name="tax_no" type="text" class="mt-1" :value="old('tax_no', $companyProfile->tax_no)" />
        <x-input-error class="mt-2" :messages="$errors->get('tax_no')" />
    </div>
    <div>
        <x-input-label for="phone" :value="__('Telefon')" />
        <x-input id="phone" name="phone" type="text" class="mt-1" :value="old('phone', $companyProfile->phone)" />
        <x-input-error class="mt-2" :messages="$errors->get('phone')" />
    </div>
    <div>
        <x-input-label for="email" :value="__('E-posta')" />
        <x-input id="email" name="email" type="email" class="mt-1" :value="old('email', $companyProfile->email)" />
        <x-input-error class="mt-2" :messages="$errors->get('email')" />
    </div>
    <div class="lg:col-span-2">
        <x-input-label for="address" :value="__('Adres')" />
        <x-textarea id="address" name="address" class="mt-1" rows="3">{{ old('address', $companyProfile->address) }}</x-textarea>
        <x-input-error class="mt-2" :messages="$errors->get('address')" />
    </div>
    <div class="lg:col-span-2">
        <x-input-label for="footer_text" :value="__('Dipnot Metni')" />
        <x-textarea id="footer_text" name="footer_text" class="mt-1" rows="3">{{ old('footer_text', $companyProfile->footer_text) }}</x-textarea>
        <x-input-error class="mt-2" :messages="$errors->get('footer_text')" />
        <p class="mt-2 text-xs text-slate-500">{{ __('Sözleşme ve dokümanların altında gösterilecek kısa açıklama.') }}</p>
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <x-ui.button type="submit">
        {{ $isEdit ? __('Kaydet') : __('Oluştur') }}
    </x-ui.button>
    <x-ui.button variant="secondary" href="{{ route('company-profiles.index') }}">
        {{ __('Vazgeç') }}
    </x-ui.button>
</div>
