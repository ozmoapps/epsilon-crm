<div class="space-y-6">
    <div>
        <x-input-label for="customer_id" :value="__('Müşteri')" />
        <select id="customer_id" name="customer_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
            <option value="">{{ __('Müşteri seçin') }}</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" @selected(old('customer_id', $workOrder->customer_id) == $customer->id)>
                    {{ $customer->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('customer_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="vessel_id" :value="__('Tekne')" />
        <select id="vessel_id" name="vessel_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
            <option value="">{{ __('Tekne seçin') }}</option>
            @foreach ($vessels as $vessel)
                <option value="{{ $vessel->id }}" @selected(old('vessel_id', $workOrder->vessel_id) == $vessel->id)>
                    {{ $vessel->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('vessel_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="title" :value="__('Başlık')" />
        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $workOrder->title ?? '')" required />
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="status" :value="__('Durum')" />
        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $workOrder->status ?? 'draft') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="planned_start_at" :value="__('Planlanan Başlangıç')" />
        <x-text-input id="planned_start_at" name="planned_start_at" type="date" class="mt-1 block w-full" :value="old('planned_start_at', optional($workOrder->planned_start_at)->format('Y-m-d'))" />
        <x-input-error :messages="$errors->get('planned_start_at')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="planned_end_at" :value="__('Planlanan Bitiş')" />
        <x-text-input id="planned_end_at" name="planned_end_at" type="date" class="mt-1 block w-full" :value="old('planned_end_at', optional($workOrder->planned_end_at)->format('Y-m-d'))" />
        <x-input-error :messages="$errors->get('planned_end_at')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="description" :value="__('Açıklama')" />
        <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $workOrder->description ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>
</div>
