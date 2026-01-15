<x-app-layout>
    @component('partials.show-layout')
        @slot('header')
            @component('partials.page-header', [
                'title' => __('Yeni Sevkiyat Oluştur'),
                'subtitle' => 'Sipariş: ' . $salesOrder->order_no,
            ])
                @slot('actions')
                    <x-ui.button href="{{ route('sales-orders.show', $salesOrder) }}" variant="secondary" size="sm">
                        {{ __('Vazgeç') }}
                    </x-ui.button>
                @endslot
            @endcomponent
        @endslot

        @slot('left')
            <form method="POST" action="{{ route('sales-orders.shipments.store', $salesOrder) }}" id="shipment-form" class="space-y-6">
                @csrf
                
                <x-ui.card>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Depo Seçimi') }} <span class="text-rose-500">*</span></label>
                            <x-select name="warehouse_id" class="w-full" required>
                                <option value="">{{ __('Seçiniz...') }}</option>
                                @foreach($warehouses as $w)
                                    <option value="{{ $w->id }}" @selected(old('warehouse_id') == $w->id)>{{ $w->name }}</option>
                                @endforeach
                            </x-select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Not') }}</label>
                            <x-textarea name="note" rows="2">{{ old('note') }}</x-textarea>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card class="!p-0 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                         <h3 class="font-bold text-slate-900">{{ __('Sevk Edilecek Ürünler') }}</h3>
                         <span class="text-xs text-slate-500">{{ $pickList->count() }} kalem mevcut</span>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                                <tr>
                                    <th class="px-5 py-3 w-10">#</th>
                                    <th class="px-5 py-3">{{ __('Ürün / Açıklama') }}</th>
                                    <th class="px-5 py-3 text-center">{{ __('Sipariş') }}</th>
                                    <th class="px-5 py-3 text-center">{{ __('Sevk Edilen') }}</th>
                                    <th class="px-5 py-3 text-center w-32">{{ __('Kalan') }}</th>
                                    <th class="px-5 py-3 text-right w-40">{{ __('Sevk Miktarı') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($pickList as $index => $item)
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-5 py-3 text-center">
                                            <input type="hidden" name="lines[{{ $index }}][item_id]" value="{{ $item['item_id'] }}">
                                            <input type="checkbox" name="selected_lines[]" value="{{ $index }}" checked class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                        </td>
                                        <td class="px-5 py-3">
                                            <div class="font-medium text-slate-900">{{ $item['product_name'] }}</div>
                                            @if($item['description'] && $item['description'] !== $item['product_name'])
                                                <div class="text-xs text-slate-500">{{ $item['description'] }}</div>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 text-center text-slate-500">
                                            {{ floatval($item['ordered_qty']) }} {{ $item['unit'] }}
                                        </td>
                                        <td class="px-5 py-3 text-center text-slate-500">
                                            {{ floatval($item['shipped_qty']) }}
                                        </td>
                                        <td class="px-5 py-3 text-center font-medium text-slate-700">
                                            {{ floatval($item['remaining_qty']) }}
                                        </td>
                                        <td class="px-5 py-3 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <input type="number" 
                                                       name="lines[{{ $index }}][qty]" 
                                                       value="{{ old("lines.{$index}.qty", $item['remaining_qty']) }}"
                                                       step="0.01" 
                                                       min="0" 
                                                       max="{{ $item['remaining_qty'] }}"
                                                       class="w-24 text-right text-sm border-slate-200 rounded-lg focus:border-brand-500 focus:ring-brand-500"
                                                >
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-5 py-8 text-center text-slate-500">
                                            {{ __('Sevk edilecek açık kalem bulunamadı.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-ui.card>

                <div class="flex items-center justify-end gap-3">
                    <button type="submit" name="post_now" value="1" class="px-4 py-2 bg-slate-900 text-white text-sm font-bold rounded-full hover:bg-slate-800 transition">
                        {{ __('Kaydet ve İşle') }}
                    </button>
                    <button type="submit" class="px-4 py-2 bg-slate-900 text-white text-sm font-bold rounded-full hover:bg-slate-800 transition">
                        {{ __('Taslak Olarak Kaydet') }}
                    </button>
                </div>
            </form>
        @endslot
        
        @slot('right')
            {{-- Info Panel --}}
             <x-ui.card>
                <h3 class="font-bold text-slate-900 mb-2">{{ __('Bilgi') }}</h3>
                <p class="text-sm text-slate-600 leading-relaxed">
                    {{ __('Listelenen miktarlar, daha önce "İşlenmiş" (Posted) sevkiyatlardan düşülerek hesaplanmıştır. Taslak sevkiyatlar bu hesaplamaya dahil edilmemiştir.') }}
                </p>
            </x-ui.card>
        @endslot
    @endcomponent
</x-app-layout>
