@php
    $fieldPrefix = $fieldPrefix ?? 'item';
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4">
    @csrf
    @if (!empty($method) && strtoupper($method) !== 'POST')
        @method($method)
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <x-input-label :for="'section_' . $fieldPrefix" :value="__('Bölüm')" />
            <x-text-input :id="'section_' . $fieldPrefix" name="section" type="text" class="mt-1 block w-full" :value="old('section', $item->section)" />
            <x-input-error :messages="$errors->get('section')" class="mt-2" />
        </div>

        <div>
            <x-input-label :for="'item_type_' . $fieldPrefix" :value="__('Tip')" />
            <select id="{{ 'item_type_' . $fieldPrefix }}" name="item_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                @foreach ($itemTypes as $value => $label)
                    <option value="{{ $value }}" @selected(old('item_type', $item->item_type) === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('item_type')" class="mt-2" />
        </div>

        <div class="sm:col-span-2 lg:col-span-1">
            <x-input-label :for="'description_' . $fieldPrefix" :value="__('Açıklama')" />
            <textarea id="{{ 'description_' . $fieldPrefix }}" name="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>{{ old('description', $item->description) }}</textarea>
            <x-input-error :messages="$errors->get('description')" class="mt-2" />
        </div>

        <div>
            <x-input-label :for="'qty_' . $fieldPrefix" :value="__('Miktar')" />
            <x-text-input :id="'qty_' . $fieldPrefix" name="qty" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('qty', $item->qty ?? 1)" required />
            <x-input-error :messages="$errors->get('qty')" class="mt-2" />
        </div>

        <div>
            <x-input-label :for="'unit_' . $fieldPrefix" :value="__('Birim')" />
            <x-text-input :id="'unit_' . $fieldPrefix" name="unit" type="text" class="mt-1 block w-full" :value="old('unit', $item->unit)" />
            <x-input-error :messages="$errors->get('unit')" class="mt-2" />
        </div>

        <div>
            <x-input-label :for="'unit_price_' . $fieldPrefix" :value="__('Birim Fiyat')" />
            <x-text-input :id="'unit_price_' . $fieldPrefix" name="unit_price" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('unit_price', $item->unit_price ?? 0)" required />
            <x-input-error :messages="$errors->get('unit_price')" class="mt-2" />
        </div>

        <div>
            <x-input-label :for="'discount_amount_' . $fieldPrefix" :value="__('İndirim')" />
            <x-text-input :id="'discount_amount_' . $fieldPrefix" name="discount_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('discount_amount', $item->discount_amount)" />
            <x-input-error :messages="$errors->get('discount_amount')" class="mt-2" />
        </div>

        <div>
            <x-input-label :for="'vat_rate_' . $fieldPrefix" :value="__('KDV %')" />
            <x-text-input :id="'vat_rate_' . $fieldPrefix" name="vat_rate" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" :value="old('vat_rate', $item->vat_rate)" />
            <x-input-error :messages="$errors->get('vat_rate')" class="mt-2" />
        </div>

        <div>
            <x-input-label :for="'sort_order_' . $fieldPrefix" :value="__('Sıra')" />
            <x-text-input :id="'sort_order_' . $fieldPrefix" name="sort_order" type="number" min="0" class="mt-1 block w-full" :value="old('sort_order', $item->sort_order ?? 0)" />
            <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
        </div>

        <div class="flex items-center gap-2 pt-7">
            <input id="is_optional_{{ $fieldPrefix }}" name="is_optional" type="checkbox" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked(old('is_optional', $item->is_optional))>
            <label for="is_optional_{{ $fieldPrefix }}" class="text-sm text-gray-700">{{ __('Opsiyon') }}</label>
        </div>
    </div>

    <div>
        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            {{ $buttonLabel }}
        </button>
    </div>
</form>
