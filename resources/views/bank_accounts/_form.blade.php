@php
    $isEdit = $bankAccount->exists;
@endphp

<div class="grid gap-6 lg:grid-cols-2">
    <div>
        <x-input-label for="name" :value="__('Hesap Adı')" />
        <x-input id="name" name="name" type="text" class="mt-1" :value="old('name', $bankAccount->name)" required />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>
    <div>
        <x-input-label for="bank_name" :value="__('Banka Adı')" />
        <x-input id="bank_name" name="bank_name" type="text" class="mt-1" :value="old('bank_name', $bankAccount->bank_name)" required />
        <x-input-error class="mt-2" :messages="$errors->get('bank_name')" />
    </div>
    <div>
        <x-input-label for="branch_name" :value="__('Şube')" />
        <x-input id="branch_name" name="branch_name" type="text" class="mt-1" :value="old('branch_name', $bankAccount->branch_name)" />
        <x-input-error class="mt-2" :messages="$errors->get('branch_name')" />
    </div>
    <div>
        <x-input-label for="currency_id" :value="__('Para Birimi')" />
        <x-select id="currency_id" name="currency_id" class="mt-1">
            <option value="">{{ __('Seçiniz') }}</option>
            @foreach ($currencies as $currency)
                <option value="{{ $currency->id }}" @selected(old('currency_id', $bankAccount->currency_id) == $currency->id)>
                    {{ $currency->name }} ({{ $currency->code }})
                </option>
            @endforeach
        </x-select>
        <x-input-error class="mt-2" :messages="$errors->get('currency_id')" />
    </div>
    <div class="lg:col-span-2">
        <x-input-label for="iban" :value="__('IBAN')" />
        <x-input id="iban" name="iban" type="text" class="mt-1" :value="old('iban', $bankAccount->iban)" required />
        <x-input-error class="mt-2" :messages="$errors->get('iban')" />
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <x-ui.button type="submit">
        {{ $isEdit ? __('Kaydet') : __('Oluştur') }}
    </x-ui.button>
    <x-ui.button variant="secondary" href="{{ route('admin.bank-accounts.index') }}">
        {{ __('Vazgeç') }}
    </x-ui.button>
</div>
