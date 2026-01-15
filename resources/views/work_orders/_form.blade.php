@php
    $isEdit = isset($workOrder) && $workOrder->exists;
    $formId = $isEdit ? 'work_order_edit_' . $workOrder->id : 'work_order_create';
@endphp

<div 
    x-data="workOrderForm({
        formId: '{{ $formId }}',
        customerId: '{{ old('customer_id', $workOrder->customer_id) }}',
        vesselId: '{{ old('vessel_id', $workOrder->vessel_id) }}',
        title: '{{ old('title', $workOrder->title ?? '') }}',
        status: '{{ old('status', $workOrder->status ?? 'draft') }}',
        plannedStartAt: '{{ old('planned_start_at', optional($workOrder->planned_start_at)->format('Y-m-d')) }}',
        plannedEndAt: '{{ old('planned_end_at', optional($workOrder->planned_end_at)->format('Y-m-d')) }}',
        description: @json(old('description', $workOrder->description ?? ''))
    })"
    x-init="init()"
    class="relative"
>
    <!-- Autosave Notification -->
    <x-ui.autosave-alert show="draftFound" />

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="customer_id" :value="__('Müşteri')" />
            <x-select id="customer_id" name="customer_id" class="mt-1" x-model="form.customerId" required>
                <option value="">{{ __('Müşteri seçin') }}</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}">
                        {{ $customer->name }}
                    </option>
                @endforeach
            </x-select>
            <x-input-error :messages="$errors->get('customer_id')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="vessel_id" :value="__('Tekne')" />
            <x-select id="vessel_id" name="vessel_id" class="mt-1" x-model="form.vesselId" required>
                <option value="">{{ __('Tekne seçin') }}</option>
                @foreach ($vessels as $vessel)
                    <option value="{{ $vessel->id }}">
                        {{ $vessel->name }}
                    </option>
                @endforeach
            </x-select>
            <x-input-error :messages="$errors->get('vessel_id')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="title" :value="__('Başlık')" />
            <x-input id="title" name="title" type="text" class="mt-1" x-model="form.title" required />
            <x-input-error :messages="$errors->get('title')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="status" :value="__('Durum')" />
            <x-select id="status" name="status" class="mt-1" x-model="form.status" required>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}">
                        {{ $label }}
                    </option>
                @endforeach
            </x-select>
            <x-input-error :messages="$errors->get('status')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="planned_start_at" :value="__('Planlanan Başlangıç')" />
            <x-input id="planned_start_at" name="planned_start_at" type="date" class="mt-1" x-model="form.plannedStartAt" />
            <x-input-error :messages="$errors->get('planned_start_at')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="planned_end_at" :value="__('Planlanan Bitiş')" />
            <x-input id="planned_end_at" name="planned_end_at" type="date" class="mt-1" x-model="form.plannedEndAt" />
            <x-input-error :messages="$errors->get('planned_end_at')" class="mt-2" />
        </div>

        <div class="md:col-span-2">
            <x-input-label for="description" :value="__('Açıklama')" />
            <x-textarea id="description" name="description" rows="4" class="mt-1" x-model="form.description"></x-textarea>
            <x-input-error :messages="$errors->get('description')" class="mt-2" />
        </div>
    </div>
</div>

<script>
    function workOrderForm(config) {
        return {
            storageKey: `autosave_${config.formId}`,
            draftFound: false,
            restoring: false,
            
            // Group fields to avoid ID collisions
            form: {
                customerId: config.customerId || '',
                vesselId: config.vesselId || '',
                title: config.title || '',
                status: config.status || '',
                plannedStartAt: config.plannedStartAt || '',
                plannedEndAt: config.plannedEndAt || '',
                description: config.description || '',
            },

            init() {
                this.checkDraft();
                
                // Watch 'form' instead of '$data'
                this.$watch('form', () => this.saveDraft());
                
                // Cleanup on submit
                this.$nextTick(() => {
                    const formEl = this.$el.closest('form');
                    if (formEl) {
                        formEl.addEventListener('submit', () => {
                            this.discardDraft();
                        });
                    }
                });
            },

            checkDraft() {
                if (localStorage.getItem(this.storageKey)) {
                    this.draftFound = true;
                }
            },

            saveDraft: Alpine.debounce(function() {
                if (this.restoring || this.draftFound) return;

                const data = {
                    ...this.form,
                    timestamp: Date.now()
                };

                localStorage.setItem(this.storageKey, JSON.stringify(data));
            }, 2000),

            restoreDraft() {
                this.restoring = true;
                try {
                    const data = JSON.parse(localStorage.getItem(this.storageKey));
                    if (data) {
                        this.form.customerId = data.customerId;
                        this.form.vesselId = data.vesselId;
                        this.form.title = data.title;
                        this.form.status = data.status;
                        this.form.plannedStartAt = data.plannedStartAt;
                        this.form.plannedEndAt = data.plannedEndAt;
                        this.form.description = data.description;
                        
                        this.draftFound = false;
                    }
                } catch(e) { console.error(e); }
                this.$nextTick(() => this.restoring = false);
            },

            discardDraft() {
                localStorage.removeItem(this.storageKey);
                this.draftFound = false;
            }
        };
    }
</script>
