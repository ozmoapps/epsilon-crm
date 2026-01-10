@php
    $isEdit = $currency->exists;
@endphp

<div class="grid gap-6 lg:grid-cols-2">
    <div>
        <x-input-label for="name" :value="__('Para Birimi Adı')" />
        <x-input id="name" name="name" type="text" class="mt-1" :value="old('name', $currency->name)" required />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>
    <div>
        <x-input-label for="code" :value="__('Kod')" />
        <x-input id="code" name="code" type="text" class="mt-1" :value="old('code', $currency->code)" required />
        <x-input-error class="mt-2" :messages="$errors->get('code')" />
    </div>
    <div>
        <x-input-label for="symbol" :value="__('Sembol')" />
        <x-input id="symbol" name="symbol" type="text" class="mt-1" :value="old('symbol', $currency->symbol)" />
        <x-input-error class="mt-2" :messages="$errors->get('symbol')" />
    </div>
    <div class="flex items-center gap-3 pt-7">
        <input type="hidden" name="is_active" value="0">
        <input id="is_active" name="is_active" type="checkbox" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500 ui-focus" @checked(old('is_active', $currency->is_active ?? true))>
        <label for="is_active" class="text-sm text-slate-700">{{ __('Aktif') }}</label>
        <x-input-error class="mt-2" :messages="$errors->get('is_active')" />
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <x-ui.button type="submit">
        {{ $isEdit ? __('Kaydet') : __('Oluştur') }}
    </x-ui.button>
    <x-ui.button variant="secondary" href="{{ route('currencies.index') }}">
        {{ __('Vazgeç') }}
    </x-ui.button>
</div>
