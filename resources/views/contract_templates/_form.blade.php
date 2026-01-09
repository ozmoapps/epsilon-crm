<div class="space-y-6">
    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <x-input-label for="name" :value="__('Şablon Adı')" />
            <x-input id="name" name="name" type="text" class="mt-1" :value="old('name', $template->name)" required />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="locale" :value="__('Dil')" />
            <x-select id="locale" name="locale" class="mt-1" required>
                @foreach ($locales as $value => $label)
                    <option value="{{ $value }}" @selected(old('locale', $template->locale ?? 'tr') === $value)>{{ $label }}</option>
                @endforeach
            </x-select>
            <x-input-error :messages="$errors->get('locale')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="format" :value="__('Format')" />
            <x-select id="format" name="format" class="mt-1" required>
                @foreach ($formats as $value => $label)
                    <option value="{{ $value }}" @selected(old('format', $template->format ?? 'html') === $value)>{{ $label }}</option>
                @endforeach
            </x-select>
            <x-input-error :messages="$errors->get('format')" class="mt-2" />
        </div>
        <div class="flex flex-col gap-3">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_default" value="1" class="rounded border-gray-300" @checked(old('is_default', $template->is_default))>
                {{ __('Varsayılan olarak ayarla') }}
            </label>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300" @checked(old('is_active', $template->is_active ?? true))>
                {{ __('Aktif') }}
            </label>
        </div>
    </div>

    <div>
        <x-input-label for="content" :value="__('Şablon İçeriği')" />
        <x-textarea id="content" name="content" rows="14" class="mt-1">{{ old('content', $template->content) }}</x-textarea>
        <x-input-error :messages="$errors->get('content')" class="mt-2" />
        <p class="mt-2 text-xs text-gray-500">
            {{ __('Kullanılabilir alanlar: {{contract.contract_no}}, {{contract.issued_at}}, {{customer.name}}, {{customer.address}}, {{sales_order.no}}, {{totals.grand_total}}, {{currency}}, {{line_items_table}}') }}
        </p>
    </div>

    <div>
        <x-input-label for="change_note" :value="__('Değişiklik Notu (Opsiyonel)')" />
        <x-input id="change_note" name="change_note" type="text" class="mt-1" :value="old('change_note')" />
        <x-input-error :messages="$errors->get('change_note')" class="mt-2" />
    </div>

    @if ($previewHtml)
        <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-4">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700">{{ __('Önizleme') }}</h3>
            <div class="mt-4 prose max-w-none">
                {!! $previewHtml !!}
            </div>
        </div>
    @endif
</div>
