@props([
    'name',
    'options' => [],
    'value' => null,
    'placeholder' => 'Select an option...',
    'disabled' => false,
])

@php
    // Ensure value is present in options if set
    $selectedOption = collect($options)->firstWhere('value', $value);
    $selectedLabel = $selectedOption['label'] ?? null;
@endphp

<div
    x-data="{
        open: false,
        isProgrammatic: false,
        value: @js($value),
        label: @js($selectedLabel),
        search: '',
        options: @js($options),
        activeIndex: -1,
        
        get filteredOptions() {
            if (this.search === '') {
                return this.options;
            }
            return this.options.filter(option => 
                option.label.toLowerCase().includes(this.search.toLowerCase())
            );
        },
        


        addOption(detail) {
            if (detail.key === '{{ $name }}') {
                this.options.push(detail.option);
                this.select(detail.option);
                this.scrollToActive();
            }
        },

        init() {
            $watch('value', value => {
                const option = this.options.find(o => o.value == value);
                if (option) {
                    this.label = option.label;
                } else {
                    this.label = null;
                }
                $dispatch('change', value);
            });

            $watch('search', query => {
                if (this.isProgrammatic) return;
                window.dispatchEvent(new CustomEvent('combobox:search', {
                    detail: { name: '{{ $name }}', query: query, open: this.open }
                }));
            });

            $watch('open', isOpen => {
                if (isOpen) {
                    window.dispatchEvent(new CustomEvent('combobox:search', {
                        detail: { name: '{{ $name }}', query: this.search, open: true }
                    }));
                }
            });
        },

        handleSetOptions(e) {
            const incomingName = String(e.detail?.name ?? '').trim();
            const myName = String('{{ $name }}').trim();
            
            if (incomingName !== myName) return;
            
            this.isProgrammatic = true;
            this.options = e.detail.options || [];
            
            // Check if current selected value is still valid
            if (this.value) {
                // Use loose equality to match string/int differences
                const option = this.options.find(o => o.value == this.value);
                if (option) {
                    this.label = option.label;
                } else {
                    this.value = null;
                    this.label = null;
                }
            }
            
            setTimeout(() => { this.isProgrammatic = false; }, 0);
        },

        handleSetValue(e) {
            const incomingName = String(e.detail?.name ?? '').trim();
            const myName = String('{{ $name }}').trim();
            
            if (incomingName !== myName) return;
            
            this.isProgrammatic = true;

            // Handle null/clearing specifically
            if (e.detail.value === null) {
                this.value = null;
                this.label = null;
                setTimeout(() => { this.isProgrammatic = false; }, 0);
                return;
            }
            
            const option = this.options.find(o => o.value == e.detail.value);
            if (option) {
                this.select(option);
            } else {
                // Golden rule: If value not found in options, clear it
                this.value = null;
                this.label = null;
            }
            
            setTimeout(() => { this.isProgrammatic = false; }, 0);
        },

        toggle() {
            if (this.disabled) return;
            this.open = !this.open;
            if (this.open) {
                this.search = '';
                this.activeIndex = -1;
                $nextTick(() => {
                    $refs.subSearch.focus();
                });
            }
        },
        
        select(option) {
            this.value = option.value;
            this.label = option.label;
            
            if (!this.isProgrammatic) {
                window.dispatchEvent(new CustomEvent('combobox:selected', {
                    detail: { 
                        name: '{{ $name }}', 
                        value: this.value, 
                        label: this.label 
                    }
                }));
            }

            this.open = false;
            this.search = '';
        },
        
        onKeydown(event) {
            if (!this.open) return;
            
            const count = this.filteredOptions.length;
            
            switch(event.key) {
                case 'ArrowDown':
                    event.preventDefault();
                    this.activeIndex = (this.activeIndex + 1) % count;
                    this.scrollToActive();
                    break;
                case 'ArrowUp':
                    event.preventDefault();
                    this.activeIndex = (this.activeIndex - 1 + count) % count;
                    this.scrollToActive();
                    break;
                case 'Enter':
                    event.preventDefault();
                    if (this.activeIndex > -1 && this.filteredOptions[this.activeIndex]) {
                        this.select(this.filteredOptions[this.activeIndex]);
                    }
                    break;
                case 'Escape':
                    this.open = false;
                    break;
            }
        },
        
        scrollToActive() {
            $nextTick(() => {
                const activeEl = $refs.optionsList.children[this.activeIndex];
                if (activeEl) {
                    activeEl.scrollIntoView({ block: 'nearest' });
                }
            });
        }
    }"
    @click.away="open = false"
    @combobox-new-option-added.window="addOption($event.detail)"
    @combobox:set-options.window="handleSetOptions($event)"
    @combobox:set-value.window="handleSetValue($event)"
    class="relative"
>
    <!-- Hidden Input for Form Submission -->
    <input type="hidden" :name="'{{ $name }}'" :value="value">

    <!-- Trigger Button -->
    <button
        type="button"
        @click="toggle()"
        @keydown="if(!open && ($event.key === 'Enter' || $event.key === ' ' || $event.key === 'ArrowDown')) { $event.preventDefault(); toggle(); }"
        {{ $attributes->merge(['class' => 'relative w-full text-left cursor-default ui-input flex items-center justify-between disabled:opacity-50 disabled:cursor-not-allowed']) }}
        {{ $disabled ? 'disabled' : '' }}
    >
        <span x-text="label ? label : '{{ $placeholder }}'" :class="{ 'text-slate-500': !label }"></span>
        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a 1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </span>
    </button>

    <!-- Dropdown Panel -->
    <div
        x-show="open"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-xl bg-white text-base shadow-soft ring-1 ring-slate-200 focus:outline-none sm:text-sm ui-card"
        style="display: none;"
    >
        <!-- Search Input -->
        <div class="sticky top-0 z-10 bg-white border-b border-slate-100 p-2">
            <input
                x-ref="subSearch"
                x-model="search"
                @keydown="onKeydown($event)"
                type="text"
                x-model="search"
                @keydown="onKeydown($event)"
                type="text"
                class="block w-full rounded-lg border-0 bg-slate-50 py-1.5 pl-3 pr-3 text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm sm:leading-6"
                placeholder="Search..."
            >
        </div>

        <!-- Options List -->
        <ul x-ref="optionsList" class="max-h-50 overflow-y-auto py-1" role="listbox">
            <template x-for="(option, index) in filteredOptions" :key="option.value">
                <li
                    @click="select(option)"
                    @mouseenter="activeIndex = index"
                    :class="{ 'bg-slate-50/60': activeIndex === index, 'text-brand-600': value === option.value, 'text-slate-900': value !== option.value }"
                    class="relative cursor-default select-none py-2 pl-3 pr-9"
                    role="option"
                >
                    <span :class="{ 'font-semibold': value === option.value, 'font-normal': value !== option.value }" class="block truncate" x-text="option.label"></span>

                    <span x-show="value === option.value" class="absolute inset-y-0 right-0 flex items-center pr-4 text-brand-600">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </li>
            </template>
            <li x-show="filteredOptions.length === 0" class="relative cursor-default select-none py-2 pl-3 pr-9 text-slate-500 italic">
                No results found.
            </li>
        </ul>
    </div>
</div>
