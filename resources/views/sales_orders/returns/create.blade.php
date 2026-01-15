<x-app-layout>
    @component('partials.show-layout')
        @slot('header')
            @component('partials.page-header', [
                'title' => __('Yeni İade Oluştur'),
                'subtitle' => 'Sevkiyat: #' . $shipment->id . ' - Sipariş: ' . $shipment->salesOrder->order_no,
            ])
                @slot('actions')
                    <x-ui.button href="{{ route('sales-orders.shipments.show', [$shipment->salesOrder, $shipment]) }}" variant="secondary" size="sm">
                        {{ __('Vazgeç') }}
                    </x-ui.button>
                @endslot
            @endcomponent
        @endslot

        @slot('left')
            <form method="POST" action="{{ route('shipments.returns.store', $shipment) }}" id="return-form" class="space-y-6">
                @csrf
                
                <x-ui.card>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('İade Yapılacak Depo') }} <span class="text-rose-500">*</span></label>
                            <x-select name="warehouse_id" class="w-full" required>
                                @foreach($warehouses as $w)
                                    <option value="{{ $w->id }}" @selected(old('warehouse_id', $shipment->warehouse_id) == $w->id)>{{ $w->name }}</option>
                                @endforeach
                            </x-select>
                            <p class="text-xs text-slate-500 mt-1">{{ __('Varsayılan olarak sevkiyatın yapıldığı depo seçilidir.') }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Not') }}</label>
                            <x-textarea name="note" rows="2">{{ old('note') }}</x-textarea>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card class="!p-0 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                         <h3 class="font-bold text-slate-900">{{ __('İade Edilecek Ürünler') }}</h3>
                         <span class="text-xs text-slate-500">{{ $returnList->count() }} kalem mevcut</span>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                                <tr>
                                    <th class="px-5 py-3 w-10">#</th>
                                    <th class="px-5 py-3">{{ __('Ürün / Açıklama') }}</th>
                                    <th class="px-5 py-3 text-center">{{ __('Sevk Edilen') }}</th>
                                    <th class="px-5 py-3 text-center">{{ __('Önceki İadeler') }}</th>
                                    <th class="px-5 py-3 text-center w-32">{{ __('İadeye Uygun') }}</th>
                                    <th class="px-5 py-3 text-right w-40">{{ __('İade Miktarı') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($returnList as $index => $item)
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-5 py-3 text-center">
                                            <input type="hidden" name="lines[{{ $index }}][line_id]" value="{{ $item['line_id'] }}">
                                            <input type="checkbox" name="selected_lines[]" value="{{ $index }}" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                        </td>
                                        <td class="px-5 py-3">
                                            <div class="font-medium text-slate-900">{{ $item['product_name'] }}</div>
                                            @if($item['description'] && $item['description'] !== $item['product_name'])
                                                <div class="text-xs text-slate-500">{{ $item['description'] }}</div>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 text-center text-slate-500">
                                            {{ floatval($item['shipped_qty']) }} {{ $item['unit'] }}
                                        </td>
                                        <td class="px-5 py-3 text-center text-slate-500">
                                            {{ floatval($item['returned_qty']) }}
                                        </td>
                                        <td class="px-5 py-3 text-center font-medium text-slate-700">
                                            {{ floatval($item['remaining_returnable_qty']) }}
                                        </td>
                                        <td class="px-5 py-3 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <input type="number" 
                                                       name="lines[{{ $index }}][qty]" 
                                                       value="{{ old("lines.{$index}.qty") }}"
                                                       step="0.01" 
                                                       min="0" 
                                                       max="{{ $item['remaining_returnable_qty'] }}"
                                                       placeholder="{{ floatval($item['remaining_returnable_qty']) }}"
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
            <x-ui.card>
                <h3 class="font-bold text-slate-900 mb-2">{{ __('Bilgi') }}</h3>
                <p class="text-sm text-slate-600 leading-relaxed">
                    {{ __('İade edilebilir miktar, daha önce "İşlenmiş" (Posted) iadelerden düşülerek hesaplanmıştır. Taslak iadeler bu hesaplamaya dahil edilmemiştir.') }}
                </p>
                <p class="text-sm text-slate-600 leading-relaxed mt-2">
                    {{ __('Onayladığınızda seçili depoya stok girişi yapılacaktır.') }}
                </p>
            </x-ui.card>
        @endslot
    @endcomponent
</x-app-layout>
