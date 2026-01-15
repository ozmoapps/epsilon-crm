@props([
    'customerName' => 'customer_id',
    'vesselName' => 'vessel_id',
    'customers' => [],
    'initialCustomerId' => null,
    'initialVesselId' => null,
    'endpoints' => [
        'vesselsByCustomerUrl' => '/api/customers/{id}/vessels',
        'vesselDetailUrl' => '/api/vessels/{id}',
        'vesselSearchUrl' => '/api/vessels/search',
    ],
])

<div
    x-data="customerVesselPicker({
        customerName: '{{ $customerName }}',
        vesselName: '{{ $vesselName }}',
        customerId: @js($initialCustomerId),
        vesselId: @js($initialVesselId),
        endpoints: @js($endpoints)
    })"
    class="space-y-4"
>
    <div class="grid gap-4 md:grid-cols-2">
        <!-- Customer Selection -->
        <x-ui.field :label="__('Müşteri')" :name="$customerName" required>
            <div>
                <x-ui.combobox
                    :name="$customerName"
                    :options="$customers"
                    :value="$initialCustomerId"
                    :placeholder="__('Müşteri seçin')"
                    x-bind:disabled="customerLocked"
                />
            </div>
            <!-- Slot for functionality like 'New Customer' -->
            <div class="mt-1">
                {{ $afterCustomer ?? '' }}
            </div>
        </x-ui.field>

        <!-- Vessel Selection -->
        <x-ui.field :label="__('Tekne')" :name="$vesselName" required>
            <div class="relative">
                <x-ui.combobox
                    :name="$vesselName"
                    :options="[]"
                    x-bind:placeholder="customerId ? '{{ __('Tekne seçin') }}' : '{{ __('Tekne ara...') }}'"
                    x-bind:disabled="false"
                />
                
                <!-- Simple Loading Spinner Overlay -->
                <div x-show="loadingVessels" style="display: none;" class="absolute right-9 top-1/2 -translate-y-1/2 pointer-events-none">
                     <svg class="animate-spin h-4 w-4 text-brand-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>
            
            <!-- Error & Empty State Handling -->
            <template x-if="error">
                <p class="mt-1 text-xs text-red-600" x-text="error"></p>
            </template>
            <template x-if="!error && vesselOptions.length === 0 && !loadingVessels && customerId">
                <p class="mt-1 text-xs text-amber-600">{{ __('Bu müşteriye ait kayıtlı tekne bulunamadı.') }}</p>
            </template>


        </x-ui.field>
    </div>

    <!-- Reset Selection Link -->
    <div x-show="customerLocked" style="display: none;">
        <button 
            type="button" 
            @click="resetSelection" 
            class="text-sm font-medium text-brand-600 hover:text-brand-700 hover:underline focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-1 rounded"
        >
            {{ __('Seçimi değiştir') }}
        </button>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        if (Alpine.data('customerVesselPicker')) return;

        Alpine.data('customerVesselPicker', (config) => ({
            customerId: config.customerId,
            vesselId: config.vesselId,
            customerLocked: false,
            vesselOptions: [],
            loadingVessels: false,
            error: null,
            searchDebounce: null,
            
            // Guards
            lastCustomerIdLoaded: null,
            lastGlobalQuery: null,
            suppressSearchUntil: 0,
            abortController: null,

            init() {
                // Initial State Sync
                if (this.customerId) {
                    this.loadVessels().then(() => {
                        if (this.vesselId) {
                            // Sync vessel combobox if vesselId present
                            this.hydrateFromVessel().then(() => {
                                 // Ensure UI reflects selection
                                 this.$nextTick(() => {
                                     window.dispatchEvent(new CustomEvent('combobox:set-value', {
                                         detail: { name: config.vesselName, value: this.vesselId }
                                     }));
                                 });
                            });
                        }
                    });
                } else if (this.vesselId) {
                    // Only vessel known initially
                    this.hydrateFromVessel();
                } else {
                    // No selection, setup global search
                    this.searchGlobalVessels('');
                }

                // --- Event Listeners with Explicit Name Checks ---

                // 1. User Selected an Option
                window.addEventListener('combobox:selected', (e) => {
                    const eventName = String(e.detail?.name ?? '').trim();
                    const custName = String(config.customerName).trim();
                    const vessName = String(config.vesselName).trim();

                    // Customer Selected
                    if (eventName === custName) {
                        this.customerId = e.detail.value;
                        this.customerLocked = false;
                        this.vesselId = null; // Reset vessel
                        this.loadVessels();   // Switch to customer's vessel list
                    }
                    
                    // Vessel Selected
                    if (eventName === vessName) {
                        this.vesselId = e.detail.value;
                        // Always try to hydrate -> locks customer if needed
                        this.hydrateFromVessel();
                    }
                });

                // 2. Global Search Request from Combobox
                window.addEventListener('combobox:search', (e) => {
                    const eventName = String(e.detail?.name ?? '').trim();
                    const vessName = String(config.vesselName).trim();

                    // Guard: Suppress search during programmatic updates
                    if (Date.now() < this.suppressSearchUntil) return;

                    // Only handle search for vessel field, AND only if no customer selected (Global Search Mode)
                    if (eventName === vessName && !this.customerId) {
                        clearTimeout(this.searchDebounce);
                        this.searchDebounce = setTimeout(() => {
                            this.searchGlobalVessels(e.detail.query);
                        }, 250);
                    }
                });
            },

            async loadVessels(force = false) {
                if (!this.customerId) {
                     this.searchGlobalVessels('');
                     return;
                }
                
                // Guard: Don't reload if same customer and already loaded (unless forced)
                if (this.customerId === this.lastCustomerIdLoaded && !force && this.vesselOptions.length > 0) {
                    // Just republish existing options to be safe
                    this.publishOptions(this.vesselOptions);
                    return;
                }

                // Guard: Prevent concurrent loads for same customer (simple lock)
                if (this.loadingVessels) return;
                
                this.loadingVessels = true;
                this.error = null;
                
                // Abort previous requests if any
                if (this.abortController) this.abortController.abort();
                this.abortController = new AbortController();
                
                this.publishOptions([]); 
                
                const url = config.endpoints.vesselsByCustomerUrl.replace('{id}', this.customerId);

                try {
                    const res = await fetch(url, {
                        signal: this.abortController.signal,
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });

                    if (!res.ok) {
                        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                            const txt = await res.text();
                            console.error('Vessel Fetch Error:', res.status, txt.substring(0, 150));
                            this.error = `Lookup Error: ${res.status}`;
                        }
                        return;
                    }

                    const json = await res.json();
                    const raw = Array.isArray(json) ? json : (json.data ?? json.vessels ?? json.items ?? []);
                    
                    const normalized = raw.map(v => ({
                        value: v.value ?? v.id,
                        label: v.label ?? v.name ?? v.title ?? ('#' + (v.id ?? v.value))
                    }));
                    
                    this.vesselOptions = normalized;
                    this.lastCustomerIdLoaded = this.customerId; // Mark as loaded
                    
                    this.publishOptions(normalized);

                    // Suppress search watcher briefly after setting options
                    this.suppressSearchUntil = Date.now() + 250;

                    // Auto-select logic if only 1 vessel
                    if (normalized.length === 1 && !this.vesselId) {
                         const single = normalized[0];
                         this.vesselId = single.value;
                         window.dispatchEvent(new CustomEvent('combobox:set-value', {
                             detail: { name: config.vesselName, value: single.value }
                         }));
                    } else if (normalized.length === 0) {
                        this.vesselId = null;
                        window.dispatchEvent(new CustomEvent('combobox:set-value', {
                            detail: { name: config.vesselName, value: null }
                        }));
                    }

                } catch (err) {
                    if (err.name !== 'AbortError') {
                        console.error(err);
                        if (window.location.hostname === 'localhost') this.error = err.message;
                    }
                } finally {
                    this.loadingVessels = false;
                    this.abortController = null;
                }
            },

            async searchGlobalVessels(query) {
                // Guard: Don't re-search same query while loading
                if (query === this.lastGlobalQuery && this.loadingVessels) return;
                
                // Guard: Don't search empty query repeatedly unless strictly needed (initial load)
                if (query === '' && this.vesselOptions.length > 0 && this.lastGlobalQuery === '') return;

                this.loadingVessels = true;
                this.lastGlobalQuery = query;

                // Abort previous
                if (this.abortController) this.abortController.abort();
                this.abortController = new AbortController();

                const url = new URL(config.endpoints.vesselSearchUrl || '/api/vessels/search', window.location.origin);
                if (query) url.searchParams.append('query', query);

                try {
                     const res = await fetch(url, {
                         signal: this.abortController.signal,
                         credentials: 'same-origin',
                         headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                     });
                     
                     if (!res.ok) throw new Error('Search failed');
                     
                     const data = await res.json(); 
                     
                     const normalized = data.map(v => ({
                         value: v.value ?? v.id,
                         label: v.customer_name ? `${v.label ?? v.name} (${v.customer_name})` : (v.label ?? v.name)
                     }));
                     
                     this.vesselOptions = normalized;
                     this.publishOptions(normalized);
                     
                } catch(e) {
                    if (e.name !== 'AbortError') {
                        console.error(e);
                        this.publishOptions([]);
                    }
                } finally {
                    this.loadingVessels = false;
                    this.abortController = null;
                }
            },

            async hydrateFromVessel() {
                if (!this.vesselId) return;

                const url = config.endpoints.vesselDetailUrl.replace('{id}', this.vesselId);

                try {
                    const response = await fetch(url, {
                         headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });

                    if (!response.ok) throw new Error('Tekne detayları alınamadı');

                    const data = await response.json();
                    
                    if (data.customer_id) {
                         this.customerId = data.customer_id;
                         this.customerLocked = true;
                         
                         window.dispatchEvent(new CustomEvent('combobox:set-value', {
                             detail: { name: config.customerName, value: this.customerId }
                         }));
                         
                         await this.loadVessels();
                         
                         this.vesselId = data.id;
                         window.dispatchEvent(new CustomEvent('combobox:set-value', {
                             detail: { name: config.vesselName, value: this.vesselId }
                         }));
                    }

                } catch (err) {
                    console.error(err);
                }
            },
            
            publishOptions(options) {
                window.dispatchEvent(new CustomEvent('combobox:set-options', { 
                    detail: { 
                        name: config.vesselName, 
                        options: options 
                    } 
                }));
            },

            resetSelection() {
                this.customerId = null;
                this.vesselId = null;
                this.customerLocked = false;
                this.error = null;
                this.lastCustomerIdLoaded = null; // Reset guard
                
                window.dispatchEvent(new CustomEvent('combobox:set-value', {
                    detail: { name: config.customerName, value: null }
                }));
                
                window.dispatchEvent(new CustomEvent('combobox:set-value', {
                    detail: { name: config.vesselName, value: null }
                }));
                
                this.searchGlobalVessels('');
            }
        }));
    });
</script>
