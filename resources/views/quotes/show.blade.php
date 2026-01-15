<x-app-layout>
    @php
        $hasSalesOrder = (bool) $quote->salesOrder;
        $isLocked = $quote->isLocked();
        
        $itemTypes = config('quotes.item_types', []);
        $currencyCode = $quote->currencyRelation?->code ?? $quote->currency;
        $currencySymbol = $quote->currencyRelation?->symbol ?? $currencyCode;
        $itemsBySection = $quote->items->groupBy(fn ($item) => $item->section ?: 'Genel');
        $formatMoney = fn ($value) => \App\Support\MoneyMath::formatTR($value);
        $unitOptions = config('quotes.unit_options', []);
        $vatOptions = config('quotes.vat_rates', []);
    @endphp

    <div class="mx-auto w-full max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Header & Actions --}}
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-heading-2 font-bold text-slate-900 tracking-tight">
                        {{ __('Teklif') }} <span class="text-slate-400 font-medium">#{{ $quote->quote_no ?? $quote->id }}</span>
                    </h1>
                     <x-badge status="{{ $quote->status }}">{{ $quote->status_label }}</x-badge>
                </div>
                <div class="mt-1 flex items-center gap-2 text-sm text-slate-500 font-medium">
                    <x-icon.clock class="h-4 w-4 text-slate-400" />
                    <span>{{ $quote->issued_at?->format('d.m.Y') ?? '-' }}</span>
                    <span class="text-slate-300">|</span>
                    <x-icon.user class="h-4 w-4 text-slate-400" />
                    <span>{{ $quote->customer?->name ?? '-' }}</span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @if ($quote->sales_order_id)
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded border border-emerald-100 flex items-center gap-1">
                            <x-icon.check class="w-3 h-3" />
                            {{ __('Sipariş Oluşturuldu') }}
                        </span>
                        <x-ui.button href="{{ route('sales-orders.show', $quote->sales_order_id) }}" variant="secondary" size="sm">
                            {{ __('Siparişi Gör') }}
                        </x-ui.button>
                    </div>
                @elseif ($quote->status === 'accepted')
                    <form method="POST" action="{{ route('quotes.convert_to_sales_order', $quote) }}">
                        @csrf
                        <x-ui.button type="submit" size="sm">
                            {{ __('Satış Siparişi Oluştur') }}
                        </x-ui.button>
                    </form>
                @else
                     <x-ui.button type="button" size="sm" disabled class="disabled:cursor-not-allowed disabled:opacity-50" title="Teklif onaylanmadan sipariş oluşturulamaz">
                        {{ __('Satış Siparişi Oluştur') }}
                    </x-ui.button>
                @endif

                @if ($quote->status === 'draft')
                    <form method="POST" action="{{ route('quotes.mark_sent', $quote) }}">
                        @csrf
                        <x-ui.button type="submit" variant="secondary" size="sm">
                            {{ __('Gönderildi') }}
                        </x-ui.button>
                    </form>
                @elseif ($quote->status === 'sent')
                    <form method="POST" action="{{ route('quotes.mark_accepted', $quote) }}">
                        @csrf
                        <x-ui.button type="submit" variant="secondary" size="sm">
                            {{ __('Onaylandı') }}
                        </x-ui.button>
                    </form>
                @endif
                
                @if ($isLocked)
                    <x-ui.button type="button" variant="secondary" size="sm" class="cursor-not-allowed opacity-60" disabled>
                        {{ __('Düzenle') }}
                    </x-ui.button>
                @else
                    <x-ui.button href="{{ route('quotes.edit', $quote) }}" variant="secondary" size="sm">
                        {{ __('Düzenle') }}
                    </x-ui.button>
                @endif

                <x-ui.dropdown align="right" width="48">
                    <x-slot name="trigger">
                         <button class="inline-flex items-center px-3 py-2 border border-slate-200 shadow-soft text-sm leading-4 font-medium rounded-xl text-slate-700 bg-white hover:bg-slate-50 focus:outline-none transition ease-in-out duration-150">
                            <x-icon.dots class="h-4 w-4" />
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link href="{{ route('quotes.preview', $quote) }}">
                            {{ __('Önizle') }}
                        </x-dropdown-link>
                        <x-dropdown-link href="{{ route('quotes.pdf', $quote) }}">
                            {{ __('Yazdır/PDF') }}
                        </x-dropdown-link>
                        <div class="border-t border-slate-100"></div>
                        <form method="POST" action="{{ route('quotes.destroy', $quote) }}">
                            @csrf @method('DELETE')
                             <x-dropdown-link href="#" onclick="event.preventDefault(); if(confirm('Bu işlem geri alınamaz. Emin misiniz?')) this.closest('form').submit();" class="text-rose-600">
                                {{ __('Sil') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-ui.dropdown>
            </div>
        </div>

        {{-- At A Glance Bar --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="rounded-xl border border-slate-100 bg-white p-5 shadow-soft hover:shadow-card transition-shadow duration-300">
                <div class="flex items-center gap-2 mb-2">
                    <div class="p-1.5 bg-brand-50 rounded-md text-brand-600">
                        <x-icon.building class="h-4 w-4" />
                    </div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Müşteri') }}</p>
                </div>
                <a href="{{ route('customers.show', $quote->customer_id) }}" class="text-sm font-bold text-slate-900 hover:text-brand-600 truncate block transition-colors">
                    {{ $quote->customer?->name ?? '-' }}
                </a>
            </div>
            <div class="rounded-xl border border-slate-100 bg-white p-5 shadow-soft hover:shadow-card transition-shadow duration-300">
                 <div class="flex items-center gap-2 mb-2">
                    <div class="p-1.5 bg-brand-50 rounded-md text-brand-600">
                        <x-icon.ship class="h-4 w-4" />
                    </div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Tekne') }}</p>
                </div>
                <p class="text-sm font-bold text-slate-900 truncate">{{ $quote->vessel?->name ?? '-' }}</p>
            </div>
            <div class="rounded-xl border border-slate-100 bg-white p-5 shadow-soft hover:shadow-card transition-shadow duration-300">
                <div class="flex items-center gap-2 mb-2">
                    <div class="p-1.5 bg-amber-50 rounded-md text-amber-600">
                        <x-icon.clock class="h-4 w-4" />
                    </div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Geçerlilik') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <p class="text-sm font-bold text-slate-900">{{ $quote->validity_days ? $quote->validity_days . ' gün' : '-' }}</p>
                    @if($quote->validity_date && $quote->validity_date->isPast())
                        <span class="text-[10px] bg-red-100 text-red-700 px-1.5 py-0.5 rounded font-medium ml-auto">{{ __('Süresi Doldu') }}</span>
                    @endif
                </div>
            </div>
            <div class="rounded-xl bg-gradient-to-br from-brand-600 to-brand-700 p-5 shadow-lg shadow-brand-500/20 text-white relative overflow-hidden group">
                <div class="absolute top-0 right-0 -mt-2 -mr-2 w-16 h-16 bg-white/10 rounded-full blur-xl group-hover:bg-white/20 transition-all duration-500"></div>
                <p class="text-xs font-medium text-brand-100 mb-1 relative z-10">{{ __('Toplam Tutar') }}</p>
                <p class="text-heading-3 font-bold tracking-tight relative z-10">{{ $formatMoney($quote->grand_total) }} <span class="text-sm font-medium text-brand-200">{{ $currencySymbol }}</span></p>
            </div>
        </div>

        {{-- Main Document Card --}}
        <div class="bg-white rounded-2xl shadow-card border border-slate-100 overflow-hidden mb-10">
            {{-- Document Meta --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 p-6 md:p-8 bg-slate-50/50 border-b border-slate-100 text-sm">
                <div class="group">
                    <h5 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1 group-hover:text-brand-600 transition-colors">{{ __('İş Emri') }}</h5>
                    <p class="font-medium text-slate-900 group-hover:text-slate-700 transition-colors">{{ $quote->workOrder?->title ?? '-' }}</p>
                </div>
                <div class="group">
                    <h5 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1 group-hover:text-brand-600 transition-colors">{{ __('Teklif Konusu') }}</h5>
                    <p class="font-medium text-slate-900 truncate group-hover:text-slate-700 transition-colors" title="{{ $quote->title }}">{{ $quote->title }}</p>
                </div>
                <div class="group">
                    <h5 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1 group-hover:text-brand-600 transition-colors">{{ __('İletişim') }}</h5>
                     <p class="font-medium text-slate-900 group-hover:text-slate-700 transition-colors">{{ $quote->contact_name ?: '-' }}</p>
                </div>
                <div class="group">
                   <h5 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1 group-hover:text-brand-600 transition-colors">{{ __('Lokasyon') }}</h5>
                   <p class="font-medium text-slate-900 group-hover:text-slate-700 transition-colors">{{ $quote->location ?: '-' }}</p>
                </div>
            </div>

            {{-- Items --}}
            <div class="p-6 md:p-8">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-heading-3 font-bold text-slate-900">{{ __('Hizmet ve Ürünler') }}</h3>
                    <span class="text-xs font-medium text-brand-700 bg-brand-50 border border-brand-100 px-3 py-1 rounded-full">
                        {{ count($quote->items) }} {{ __('kalem') }}
                    </span>
                </div>

                <div class="space-y-10">
                     @forelse ($itemsBySection as $section => $items)
                        <div class="relative">
                            <div class="sticky top-0 z-10 bg-white/95 backdrop-blur-sm py-2 mb-4 border-b border-slate-100 flex items-center justify-between">
                                <h4 class="text-sm font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                                    <div class="w-1.5 h-4 bg-brand-500 rounded-full"></div>
                                    {{ $section }}
                                </h4>
                            </div>

                            <div class="grid gap-3">
                                @foreach ($items as $item)
                                    @php
                                        $qty = \App\Support\MoneyMath::decimalToScaledInt($item->qty);
                                        $unitPrice = \App\Support\MoneyMath::decimalToScaledInt($item->unit_price);
                                        $discountAmount = \App\Support\MoneyMath::decimalToScaledInt($item->discount_amount ?? 0);
                                        $vatBp = \App\Support\MoneyMath::percentToBasisPoints($item->vat_rate ?? 0);
                                        $line = \App\Support\MoneyMath::calculateLineCents($qty, $unitPrice, $discountAmount, $vatBp);
                                        $format = fn($cents) => \App\Support\MoneyMath::formatTR($cents / 100);
                                    @endphp
                                    
                                    <div class="group relative rounded-xl border border-slate-100 p-5 hover:border-brand-300 hover:shadow-card-hover transition-all duration-300 bg-white" 
                                         x-data="{ 
                                            editing: false,
                                            selectedProductId: '{{ $item->product_id }}',
                                            products: @json($products),
                                            get suggestion() {
                                                if (!this.selectedProductId) return null;
                                                return this.products.find(p => p.id == this.selectedProductId);
                                            },
                                            applySuggestion() {
                                                const s = this.suggestion;
                                                if (!s) return;
                                                
                                                // Desc
                                                const descField = $el.closest('form').querySelector('[name=description]');
                                                if (descField && !descField.value.trim()) {
                                                     descField.value = s.name + (s.sku ? ' (' + s.sku + ')' : '');
                                                }

                                                // Price
                                                const priceField = $el.closest('form').querySelector('[name=unit_price]');
                                                if (priceField && !priceField.value.trim() && s.default_sell_price) {
                                                    priceField.value = s.default_sell_price;
                                                }
                                            }
                                         }">
                                        {{-- View Mode --}}
                                        <div class="flex flex-col sm:flex-row sm:items-center gap-6" x-show="!editing">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-start justify-between gap-4">
                                                    <div>
                                                        <div class="flex items-center gap-2 mb-1.5">
                                                            <p class="font-bold text-slate-900 text-lg">{{ $item->description }}</p>
                                                            @if ($item->is_optional)
                                                                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">{{ __('Opsiyon') }}</span>
                                                            @endif
                                                        </div>
                                                        <div class="flex items-center gap-4 text-sm text-slate-500">
                                                            <span class="px-2 py-0.5 rounded bg-slate-50 text-xs font-medium border border-slate-100">{{ config('quotes.item_types')[$item->item_type] ?? $item->item_type }}</span>
                                                            <div class="flex items-center gap-1">
                                                                <span class="font-bold text-slate-700">{{ $item->qty }}</span> {{ $item->unit }} x <span class="font-medium text-slate-700">{{ $formatMoney($item->unit_price) }}</span> {{ $currencySymbol }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                @if($item->discount_amount > 0)
                                                    <div class="mt-2 flex items-center gap-2 text-xs font-medium text-rose-600 bg-rose-50 w-fit px-2 py-0.5 rounded">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3">
                                                          <path fill-rule="evenodd" d="M5.5 3A2.5 2.5 0 003 5.5v2.879a2.5 2.5 0 00.732 1.767l6.5 6.5a2.5 2.5 0 003.536 0l2.878-2.878a2.5 2.5 0 000-3.536l-6.5-6.5A2.5 2.5 0 008.38 3H5.5zM6 7a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                                        </svg>
                                                        İndirim: -{{ $formatMoney($item->discount_amount) }}
                                                    </div>
                                                @endif
                                            </div>
                                            
                                            <div class="text-right shrink-0 flex flex-col items-end min-w-[120px]">
                                                <p class="text-heading-4 font-bold text-slate-900 tracking-tight">{{ $format($line['total_cents']) }} <span class="text-sm font-medium text-slate-500">{{ $currencySymbol }}</span></p>
                                                @if($item->vat_rate > 0)
                                                    <p class="text-[10px] text-slate-400 font-medium">+ %{{ $item->vat_rate }} KDV</p>
                                                @endif
                                            </div>

                                            {{-- Actions --}}
                                            <div class="flex items-center gap-1 sm:opacity-0 group-hover:opacity-100 transition-opacity absolute top-4 right-4 sm:static sm:bg-transparent bg-white/90 backdrop-blur rounded p-1 sm:shadow-none border sm:border-0 border-slate-100">
                                                @if(!$isLocked)
                                                    <button @click="editing = true" class="p-2 text-slate-400 hover:text-brand-600 hover:bg-brand-50 rounded-xl transition-all" title="Düzenle">
                                                        <x-icon.pencil class="h-4 w-4" />
                                                    </button>
                                                    <form method="POST" action="{{ route('quotes.items.destroy', [$quote, $item]) }}" onsubmit="return confirm('Bu kalemi silmek istediğinize emin misiniz?');">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-all" title="Sil">
                                                            <x-icon.trash class="h-4 w-4" />
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Edit Form --}}
                                        <div x-show="editing" x-cloak class="bg-slate-50 rounded-xl p-4 -m-2 mt-2 border border-brand-100">
                                             <form method="POST" action="{{ route('quotes.items.update', [$quote, $item]) }}" class="space-y-4">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="form_context" value="item-{{ $item->id }}">
                                                
                                                <div class="grid grid-cols-12 gap-3">
                                                    <div class="col-span-12 sm:col-span-6">
                                                        <div class="mb-2">
                                                            <x-select name="product_id" class="w-full text-sm" x-model="selectedProductId">
                                                                <option value="">{{ __('Ürün Seçimi (Opsiyonel)') }}</option>
                                                                @foreach ($products as $p)
                                                                    <option value="{{ $p->id }}" @selected($item->product_id == $p->id)>
                                                                        {{ $p->name }} ({{ $p->sku }})
                                                                    </option>
                                                                @endforeach
                                                            </x-select>
                                                            
                                                            {{-- Suggestion Box (Edit) --}}
                                                            <template x-if="suggestion">
                                                                <div class="mt-2 p-3 bg-blue-50 border border-blue-100 rounded-lg flex items-center justify-between gap-3 text-xs">
                                                                    <div class="text-blue-700">
                                                                        <div class="font-bold flex items-center gap-1">
                                                                            <x-icon.info class="w-3 h-3" />
                                                                            {{ __('Ürün Önerisi') }}
                                                                        </div>
                                                                        <div class="mt-1">
                                                                            <span x-text="suggestion.name"></span>
                                                                            <span x-show="suggestion.default_sell_price" class="mx-1">•</span>
                                                                            <span x-show="suggestion.default_sell_price" x-text="suggestion.default_sell_price + ' ' + (suggestion.currency_code || '')"></span>
                                                                        </div>
                                                                    </div>
                                                                    <button type="button" @click="applySuggestion()" class="shrink-0 px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-[10px] font-bold transition-colors">
                                                                        {{ __('Öneriyi Uygula') }}
                                                                    </button>
                                                                </div>
                                                            </template>
                                                        </div>
                                                        <x-textarea name="description" rows="2" class="w-full text-sm" required>{{ $item->description }}</x-textarea>
                                                    </div>
                                                    <div class="col-span-4 sm:col-span-2">
                                                        <x-input name="qty" :value="$item->qty" class="w-full text-right text-sm" required placeholder="Miktar" />
                                                    </div>
                                                    <div class="col-span-4 sm:col-span-2">
                                                         <x-select name="unit" class="w-full text-sm !py-2">
                                                            @foreach ($unitOptions as $unitOption)
                                                                <option value="{{ $unitOption }}" @selected($item->unit === $unitOption)>{{ $unitOption }}</option>
                                                            @endforeach
                                                        </x-select>
                                                    </div>
                                                    <div class="col-span-4 sm:col-span-2">
                                                        <x-input name="unit_price" :value="$item->unit_price" class="w-full text-right text-sm" required placeholder="Fiyat" />
                                                    </div>
                                                </div>
                                                
                                                <div class="flex items-center justify-between border-t border-slate-200/60 pt-3">
                                                    <div class="flex items-center gap-3">
                                                         <div class="flex items-center gap-2">
                                                            <input id="edit_optional_{{ $item->id }}" name="is_optional" type="checkbox" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 h-4 w-4" @checked($item->is_optional)>
                                                            <label for="edit_optional_{{ $item->id }}" class="text-sm font-medium text-slate-600">{{ __('Opsiyonel') }}</label>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <x-ui.button type="button" variant="secondary" size="sm" @click="editing = false">{{ __('İptal') }}</x-ui.button>
                                                        <x-ui.button type="submit" size="sm">{{ __('Kaydet') }}</x-ui.button>
                                                    </div>
                                                </div>
                                             </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-16 rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/50">
                            <div class="mx-auto w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mb-3">
                                <x-icon.clipboard class="h-6 w-6 text-slate-400" />
                            </div>
                            <p class="text-slate-900 font-semibold">{{ __('Liste Boş') }}</p>
                            <p class="text-sm text-slate-500 mt-1 max-w-xs mx-auto">{{ __('Bu teklifte henüz kalem bulunmuyor. Yeni bir kalem ekleyerek başlayın.') }}</p>
                        </div>
                    @endforelse
                </div>

                {{-- Add New Item --}}
                @if(!$isLocked)
                    <div class="mt-8">
                         <details class="group rounded-xl border-2 border-dashed border-brand-200 bg-brand-50/20 p-1 open:bg-white open:border-solid open:border-brand-200 open:shadow-card transition-all duration-300">
                             <summary class="flex cursor-pointer items-center justify-center gap-2 p-3 text-sm font-bold text-brand-700 hover:text-brand-800 transition group-open:hidden uppercase tracking-wide">
                                <x-icon.plus class="h-4 w-4" />
                                {{ __('Yeni Kalem Ekle') }}
                            </summary>
                            <div class="p-6 animate-in fade-in slide-in-from-top-1"
                                 x-data="{
                                    selectedProductId: '',
                                    products: @json($products),
                                    get suggestion() {
                                        if (!this.selectedProductId) return null;
                                        return this.products.find(p => p.id == this.selectedProductId);
                                    },
                                    applySuggestion() {
                                        const s = this.suggestion;
                                        if (!s) return;
                                        
                                        // Desc
                                        const descField = $el.closest('form').querySelector('[name=description]');
                                        if (descField && !descField.value.trim()) {
                                             descField.value = s.name + (s.sku ? ' (' + s.sku + ')' : '');
                                        }

                                        // Price
                                        const priceField = $el.closest('form').querySelector('[name=unit_price]');
                                        if (priceField && !priceField.value.trim() && s.default_sell_price) {
                                            priceField.value = s.default_sell_price;
                                        }
                                    }
                                 }">
                                <div class="flex items-center justify-between mb-6">
                                    <h4 class="text-sm font-bold text-brand-900 uppercase tracking-wider flex items-center gap-2">
                                        <div class="p-1 bg-brand-100 rounded text-brand-600">
                                            <x-icon.plus class="h-3 w-3" />
                                        </div>
                                        {{ __('Yeni Kalem Ekle') }}
                                    </h4>
                                    <button type="button" @click="$el.closest('details').removeAttribute('open')" class="text-slate-400 hover:text-rose-500 transition-colors">
                                        <x-icon.x class="h-5 w-5" />
                                    </button>
                                </div>
                                
                                <form method="POST" action="{{ route('quotes.items.store', $quote) }}" class="space-y-5">
                                    @csrf
                                    <input type="hidden" name="form_context" value="new-item">
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
                                        <div class="md:col-span-5 space-y-4">
                                            <div>
                                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">{{ __('Kategori') }}</label>
                                                <x-select name="item_type" class="w-full text-sm">
                                                    @foreach ($itemTypes as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </x-select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">{{ __('Ürün (Opsiyonel)') }}</label>
                                                <x-select name="product_id" class="w-full text-sm" x-model="selectedProductId">
                                                    <option value="">{{ __('Ürün Seçiniz...') }}</option>
                                                    @foreach ($products as $p)
                                                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }})</option>
                                                    @endforeach
                                                </x-select>

                                                {{-- Suggestion Box (New) --}}
                                                <template x-if="suggestion">
                                                    <div class="mt-2 p-3 bg-blue-50 border border-blue-100 rounded-lg flex items-center justify-between gap-3 text-xs">
                                                        <div class="text-blue-700">
                                                            <div class="font-bold flex items-center gap-1">
                                                                <x-icon.info class="w-3 h-3" />
                                                                {{ __('Ürün Önerisi') }}
                                                            </div>
                                                            <div class="mt-1">
                                                                <span x-text="suggestion.name"></span>
                                                                <span x-show="suggestion.default_sell_price" class="mx-1">•</span>
                                                                <span x-show="suggestion.default_sell_price" x-text="suggestion.default_sell_price + ' ' + (suggestion.currency_code || '')"></span>
                                                            </div>
                                                        </div>
                                                        <button type="button" @click="applySuggestion()" class="shrink-0 px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-[10px] font-bold transition-colors">
                                                            {{ __('Öneriyi Uygula') }}
                                                        </button>
                                                    </div>
                                                </template>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">{{ __('Açıklama') }}</label>
                                                <x-textarea name="description" rows="3" class="w-full text-sm" placeholder="Hizmet veya ürün açıklaması..." required></x-textarea>
                                            </div>
                                        </div>
                                        
                                        <div class="md:col-span-7 grid grid-cols-2 sm:grid-cols-4 gap-4">
                                            <div class="sm:col-span-1">
                                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">{{ __('Miktar') }}</label>
                                                <x-input name="qty" value="1" class="w-full text-right" required />
                                            </div>
                                            <div class="sm:col-span-1">
                                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">{{ __('Birim') }}</label>
                                                <x-select name="unit" class="w-full text-sm !py-2">
                                                    @foreach ($unitOptions as $unitOption)
                                                        <option value="{{ $unitOption }}">{{ $unitOption }}</option>
                                                    @endforeach
                                                </x-select>
                                            </div>
                                            <div class="sm:col-span-2">
                                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">{{ __('Birim Fiyat') }}</label>
                                                <x-input name="unit_price" class="w-full text-right" required placeholder="0.00" />
                                            </div>
                                            <div class="sm:col-span-2">
                                                 <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">{{ __('İndirim') }}</label>
                                                <x-input name="discount_amount" class="w-full text-right" placeholder="0.00" />
                                            </div>
                                            <div class="sm:col-span-2">
                                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">{{ __('KDV') }}</label>
                                                <x-select name="vat_rate" class="w-full text-sm !py-2">
                                                    <option value="">{{ __('KDV Yok') }}</option>
                                                    @foreach ($vatOptions as $vatOption)
                                                        <option value="{{ $vatOption }}" @selected(old('vat_rate') == $vatOption)>%{{ $vatOption }}</option>
                                                    @endforeach
                                                </x-select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                                         <div class="flex items-center gap-2 mr-auto">
                                            <input id="new_optional" name="is_optional" type="checkbox" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 h-4 w-4">
                                            <label for="new_optional" class="text-sm font-medium text-slate-600">{{ __('Opsiyonel Kalem') }}</label>
                                        </div>
                                        <x-ui.button type="button" @click="$el.closest('details').removeAttribute('open')" variant="secondary" size="sm">{{ __('İptal') }}</x-ui.button>
                                        <x-ui.button type="submit" size="sm">{{ __('Listeye Ekle') }}</x-ui.button>
                                    </div>
                                </form>
                            </div>
                        </details>
                    </div>
                @endif
            </div>

            {{-- Totals --}}
            <div class="bg-slate-50 px-6 py-8 md:px-8 border-t border-slate-100">
                <div class="flex flex-col md:flex-row md:justify-end gap-8">
                     <div class="md:w-1/3">
                        <div class="space-y-4">
                            <div class="flex justify-between text-sm text-slate-600">
                                <span>{{ __('Ara Toplam') }}</span>
                                <span class="font-bold text-slate-900">{{ $formatMoney($quote->subtotal) }} {{ $currencySymbol }}</span>
                            </div>
                            @if($quote->discount_total > 0)
                                <div class="flex justify-between text-sm text-rose-600 font-medium">
                                    <span>{{ __('İndirim') }}</span>
                                    <span>- {{ $formatMoney($quote->discount_total) }} {{ $currencySymbol }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between text-sm text-slate-600">
                                <span>{{ __('KDV') }}</span>
                                <span class="font-bold text-slate-900">{{ $formatMoney($quote->vat_total) }} {{ $currencySymbol }}</span>
                            </div>
                            <div class="pt-4 border-t border-slate-200 flex justify-between items-end">
                                <span class="text-base font-bold text-slate-900">{{ __('Genel Toplam') }}</span>
                                <span class="text-heading-2 font-bold text-brand-600 tracking-tight">{{ $formatMoney($quote->grand_total) }} <span class="text-lg text-brand-400">{{ $currencySymbol }}</span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

             {{-- Terms Section (Inside Card) --}}
            <div class="grid md:grid-cols-2 gap-8 p-6 md:p-8 border-t border-slate-100 bg-white">
                <div>
                    <h5 class="flex items-center gap-2 font-bold text-slate-900 mb-4 text-sm uppercase tracking-wide">
                        <div class="p-1.5 bg-slate-100 rounded text-slate-500">
                             <x-icon.document-text class="h-4 w-4" />
                        </div>
                        {{ __('Notlar & Koşullar') }}
                    </h5>
                    <div class="prose prose-sm prose-slate text-xs bg-slate-50 p-4 rounded-xl border border-slate-100">
                        @if($quote->notes) <p class="mb-2">{{ $quote->notes }}</p> @endif
                        @if($quote->payment_terms) <p><strong>{{ __('Ödeme:') }}</strong> {{ $quote->payment_terms }}</p> @endif
                    </div>
                </div>
                <div>
                     <h5 class="flex items-center gap-2 font-bold text-slate-900 mb-4 text-sm uppercase tracking-wide">
                        <div class="p-1.5 bg-slate-100 rounded text-slate-500">
                            <x-icon.check class="h-4 w-4" />
                        </div>
                        {{ __('Garanti & Kapsam') }}
                    </h5>
                      <div class="prose prose-sm prose-slate text-xs bg-slate-50 p-4 rounded-xl border border-slate-100">
                        @if($quote->warranty_text) <p class="mb-2"><strong>{{ __('Garanti:') }}</strong> {{ $quote->warranty_text }}</p> @endif
                        @if($quote->exclusions) <p><strong>{{ __('Hariçler:') }}</strong> {{ $quote->exclusions }}</p> @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Secondary Info Tabs --}}
        <div x-data="{ activeTab: 'activity' }" class="mb-20">
            <div class="flex items-center gap-1 border-b border-slate-200 mb-6 overflow-x-auto">
                <button 
                    @click="activeTab = 'activity'" 
                    :class="{ 'border-brand-600 text-brand-700 bg-brand-50/50': activeTab === 'activity', 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50': activeTab !== 'activity' }"
                    class="px-5 py-3 text-sm font-bold border-b-2 transition-all rounded-t-lg flex items-center gap-2"
                >
                    <x-icon.clock class="h-4 w-4" />
                    {{ __('Aktivite') }}
                </button>
                <button 
                    @click="activeTab = 'documents'" 
                    :class="{ 'border-brand-600 text-brand-700 bg-brand-50/50': activeTab === 'documents', 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50': activeTab !== 'documents' }"
                    class="px-5 py-3 text-sm font-bold border-b-2 transition-all rounded-t-lg flex items-center gap-2"
                >
                    <x-icon.document class="h-4 w-4" />
                    {{ __('Dosyalar') }}
                </button>
                 <button 
                    @click="activeTab = 'followups'" 
                    :class="{ 'border-brand-600 text-brand-700 bg-brand-50/50': activeTab === 'followups', 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50': activeTab !== 'followups' }"
                    class="px-5 py-3 text-sm font-bold border-b-2 transition-all rounded-t-lg flex items-center gap-2"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                      <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
                    </svg>
                    {{ __('Takipler') }}
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div x-show="activeTab === 'activity'" class="md:col-span-2 space-y-4 animate-in fade-in slide-in-from-left-2">
                     <div class="bg-white rounded-xl border border-slate-100 p-6 shadow-card">
                        <x-activity-timeline :logs="$timeline" :show-subject="true" />
                     </div>
                </div>

                {{-- Documents --}}
                 <div x-show="activeTab === 'documents'" class="md:col-span-3 animate-in fade-in slide-in-from-left-2">
                      @include('partials.document-hub', [
                        'context' => 'quote',
                        'quote' => $quote,
                        'salesOrder' => $salesOrder ?? null,
                        'contract' => $contract ?? null,
                        'workOrder' => $workOrder ?? null,
                        'timeline' => $timeline,
                        'showTimeline' => false,
                    ])
                 </div>
                 
                 {{-- Follow-ups --}}
                 <div x-show="activeTab === 'followups'" class="md:col-span-2 animate-in fade-in slide-in-from-left-2">
                    <div class="max-w-2xl">
                         <x-partials.follow-up-card :context="$quote" />
                    </div>
                 </div>
            </div>
        </div>
    </div>
</x-app-layout>
