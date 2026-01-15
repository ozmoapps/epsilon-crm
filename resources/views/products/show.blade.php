<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$product->name" :subtitle="$product->sku ?? 'Kod Yok'">
            <x-slot name="actions">
                <x-ui.button href="{{ route('products.edit', $product) }}" variant="secondary" size="sm">
                    <x-icon.pencil class="w-4 h-4 mr-1.5" />
                    {{ __('Düzenle') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Main Info --}}
        <div class="md:col-span-2 space-y-6">
            <x-ui.card>
                <x-slot name="header">{{ __('Genel Bilgiler') }}</x-slot>
                
                <div class="grid grid-cols-2 gap-y-4 text-sm">
                    <div>
                        <span class="block text-xs font-semibold text-slate-500 uppercase">{{ __('Tip') }}</span>
                        <span class="font-medium text-slate-900">{{ $product->type === 'product' ? 'Ürün' : 'Hizmet' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-slate-500 uppercase">{{ __('Kategori') }}</span>
                        <span class="font-medium text-slate-900">{{ $product->category?->name ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-slate-500 uppercase">{{ __('SKU') }}</span>
                        <span class="font-mono text-slate-900">{{ $product->sku ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-slate-500 uppercase">{{ __('Barkod') }}</span>
                        <span class="font-mono text-slate-900">{{ $product->barcode ?? '-' }}</span>
                    </div>
                </div>

                @if($product->tags->isNotEmpty())
                    <div class="mt-6 pt-6 border-t border-slate-100">
                        <span class="block text-xs font-semibold text-slate-500 uppercase mb-2">{{ __('Etiketler') }}</span>
                        <div class="flex flex-wrap gap-2">
                            @foreach($product->tags as $tag)
                                <span class="px-2 py-1 rounded text-xs font-semibold bg-{{ $tag->color ?? 'slate' }}-100 text-{{ $tag->color ?? 'slate' }}-700 border border-{{ $tag->color ?? 'slate' }}-200">
                                    {{ $tag->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
                
                @if($product->notes)
                    <div class="mt-6 pt-6 border-t border-slate-100">
                        <span class="block text-xs font-semibold text-slate-500 uppercase mb-1">{{ __('Notlar') }}</span>
                        <p class="text-slate-600 text-sm whitespace-pre-line">{{ $product->notes }}</p>
                    </div>
                @endif
            </x-ui.card>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
             <x-ui.card>
                 <x-slot name="header">{{ __('Fiyatlandırma') }}</x-slot>
                 <div class="space-y-4">
                     <div class="flex justify-between items-center py-2 border-b border-slate-100">
                         <span class="text-slate-600 text-sm">{{ __('Satış Fiyatı') }}</span>
                         <span class="text-lg font-bold text-slate-900">{{ number_format($product->default_sell_price, 2) }} <span class="text-xs text-slate-500">{{ $product->currency_code }}</span></span>
                     </div>
                     <div class="flex justify-between items-center py-2">
                         <span class="text-slate-600 text-sm">{{ __('Alış Fiyatı') }}</span>
                         <span class="font-medium text-slate-900">{{ number_format($product->default_buy_price, 2) }} <span class="text-xs text-slate-500">{{ $product->currency_code }}</span></span>
                     </div>
                 </div>
             </x-ui.card>

             <x-ui.card>
                 <x-slot name="header">{{ __('Stok Durumu') }}</x-slot>
                 
                 @if($product->track_stock)
                     <div class="space-y-4">
                        <div class="text-center py-4 border-b border-slate-100">
                            <div class="text-3xl font-bold text-slate-900">{{ $product->inventoryBalances->sum('qty_on_hand') + 0 }}</div>
                            <div class="text-xs text-slate-500 uppercase tracking-wide mt-1">{{ __('Toplam Mevcut Stok') }}</div>
                        </div>

                        <div class="relative overflow-x-auto">
                            <table class="w-full text-sm text-left text-slate-500">
                                <thead class="text-xs text-slate-700 uppercase bg-slate-50">
                                    <tr>
                                        <th scope="col" class="px-4 py-2">{{ __('Depo') }}</th>
                                        <th scope="col" class="px-4 py-2 text-right">{{ __('Miktar') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($product->inventoryBalances as $balance)
                                        <tr class="bg-white border-b hover:bg-slate-50">
                                            <td class="px-4 py-2 font-medium text-slate-900">{{ $balance->warehouse->name }}</td>
                                            <td class="px-4 py-2 text-right">{{ $balance->qty_on_hand + 0 }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="px-4 py-2 text-center text-slate-400 italic">{{ __('Depo bakiyesi yok') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($product->critical_stock_level)
                             <div class="mt-2 text-center">
                                 <x-ui.badge variant="danger" class="!px-2 !py-1 text-xs font-medium">
                                     {{ __('Kritik Seviye: ') }} {{ $product->critical_stock_level }}
                                 </x-ui.badge>
                             </div>
                        @endif
                     </div>
                 @else
                    <div class="text-center py-8">
                       <div class="text-slate-400 text-sm italic">{{ __('Stok takibi kapalı') }}</div> 
                    </div>
                 @endif
             </x-ui.card>
        </div>
    </div>
</x-app-layout>
