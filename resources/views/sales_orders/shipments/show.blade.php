<x-app-layout>
    @component('partials.show-layout')
        @slot('header')
            @component('partials.page-header', [
                'title' => __('Sevkiyat Detayı') . ' #' . $shipment->id,
                'subtitle' => 'Sipariş: ' . $salesOrder->order_no,
            ])
                @slot('status')
                     <x-ui.badge :variant="$shipment->status === 'posted' ? 'success' : 'neutral'">{{ $shipment->status === 'posted' ? 'İşlendi' : 'Taslak' }}</x-ui.badge>
                @endslot

                @slot('actions')
                     @if($shipment->status === 'draft')
                        <form method="POST" action="{{ route('shipments.post', $shipment) }}" class="mr-2">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-sm font-bold rounded-xl hover:bg-emerald-700 transition flex items-center gap-2">
                                <x-icon.check class="w-4 h-4" />
                                {{ __('İşle (Stok Düş)') }}
                            </button>
                        </form>
                        
                        <form method="POST" action="{{ route('shipments.destroy', $shipment) }}" onsubmit="return confirm('Silmek istediğinize emin misiniz?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-2 text-rose-600 hover:bg-rose-50 rounded-xl transition">
                                <x-icon.trash class="w-5 h-5" />
                            </button>
                        </form>
                    @endif
                    
                    <x-ui.button href="{{ route('sales-orders.show', $salesOrder) }}" variant="secondary" size="sm" class="ml-2">
                        {{ __('Siparişe Dön') }}
                    </x-ui.button>
                @endslot
            @endcomponent
        @endslot

        @slot('left')
             <div class="space-y-6">
                <x-ui.card>
                     <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-wider mb-1">{{ __('Depo') }}</p>
                            <p class="font-medium text-slate-900">{{ $shipment->warehouse->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-wider mb-1">{{ __('Oluşturan') }}</p>
                            <p class="font-medium text-slate-900">{{ $shipment->creator?->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-wider mb-1">{{ __('Oluşturulma') }}</p>
                            <p class="font-medium text-slate-900">{{ $shipment->created_at->format('d.m.Y H:i') }}</p>
                        </div>
                         <div>
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-wider mb-1">{{ __('İşlenme Tarihi') }}</p>
                            <p class="font-medium text-slate-900">{{ $shipment->posted_at ? $shipment->posted_at->format('d.m.Y H:i') : '-' }}</p>
                        </div>
                        @if($shipment->note)
                             <div class="col-span-2 mt-2 pt-2 border-t border-slate-100">
                                <p class="text-xs text-slate-500 font-bold uppercase tracking-wider mb-1">{{ __('Not') }}</p>
                                <p class="text-slate-700">{{ $shipment->note }}</p>
                            </div>
                        @endif
                    </div>
                </x-ui.card>

                <x-ui.card class="!p-0 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50">
                         <h3 class="font-bold text-slate-900">{{ __('Kalemler') }}</h3>
                    </div>
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                            <tr>
                                <th class="px-5 py-3">{{ __('Ürün / Açıklama') }}</th>
                                <th class="px-5 py-3 text-right">{{ __('Miktar') }}</th>
                                <th class="px-5 py-3 text-left w-20">{{ __('Birim') }}</th>
                            </tr>
                        </thead>
                       <tbody class="divide-y divide-slate-100">
                            @foreach($shipment->lines as $line)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-5 py-3">
                                        <div class="font-medium text-slate-900">{{ $line->description }}</div>
                                        @if($line->product)
                                            <div class="text-xs text-slate-500">{{ $line->product->name }} ({{ $line->product->sku }})</div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right font-bold text-slate-700">
                                        {{ floatval($line->qty) }}
                                    </td>
                                    <td class="px-5 py-3 text-slate-500">
                                        {{ $line->unit }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-ui.card>
             </div>
        @endslot
        
        @slot('right')
            @if($shipment->status === 'posted')
                 <x-ui.card class="bg-emerald-50 border-emerald-100">
                    <div class="flex items-start gap-3">
                        <x-icon.check-circle class="w-5 h-5 text-emerald-600 mt-0.5" />
                        <div>
                            <h4 class="font-bold text-emerald-900 text-sm">{{ __('Stoktan Düşüldü') }}</h4>
                            <p class="text-xs text-emerald-700 mt-1">Bu sevkiyat onaylanmış ve stok hareketleri oluşturulmuştur.</p>
                        </div>
                    </div>
                </x-ui.card>
                
                <div class="mt-4 flex justify-end">
                    <x-ui.button href="{{ route('shipments.returns.create', $shipment) }}" variant="secondary" size="sm">
                        <x-icon.reply class="w-4 h-4 mr-1" />
                        {{ __('İade Oluştur') }}
                    </x-ui.button>
                </div>
            @else
                <x-ui.card class="bg-amber-50 border-amber-100">
                    <div class="flex items-start gap-3">
                        <x-icon.clock class="w-5 h-5 text-amber-600 mt-0.5" />
                        <div>
                            <h4 class="font-bold text-amber-900 text-sm">{{ __('Taslak') }}</h4>
                            <p class="text-xs text-amber-700 mt-1">Bu sevkiyat henüz işlenmemiştir. Stok düşüşü için lütfen "İşle" butonunu kullanın.</p>
                        </div>
                    </div>
                </x-ui.card>
            @endif
        @endslot
    @endcomponent
</x-app-layout>
