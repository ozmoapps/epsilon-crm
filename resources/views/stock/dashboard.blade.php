<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="{{ __('Operasyon Paneli') }}" subtitle="{{ __('Stok ve lojistik durum özeti.') }}">
             <x-slot name="actions">
                <x-ui.button href="{{ route('stock-movements.index') }}" variant="secondary" size="sm">
                    {{ __('Tüm Hareketler') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        
        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Negative Stock --}}
             <x-ui.card class="bg-rose-50 border-rose-100">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-rose-100 rounded-full text-rose-600">
                         <x-icon.exclamation-circle class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-rose-600">{{ __('Negatif Stok') }}</p>
                        <p class="text-2xl font-bold text-rose-900">{{ $negativeStockCount }} <span class="text-sm font-normal text-rose-700">ürün</span></p>
                    </div>
                </div>
            </x-ui.card>

             {{-- Critical Stock --}}
             <x-ui.card class="bg-amber-50 border-amber-100">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-amber-100 rounded-full text-amber-600">
                         <x-icon.lightning-bolt class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-amber-600">{{ __('Kritik Seviye Altı') }}</p>
                        <p class="text-2xl font-bold text-amber-900">{{ $criticalStockCount }} <span class="text-sm font-normal text-amber-700">ürün</span></p>
                    </div>
                </div>
            </x-ui.card>

            {{-- Today's Logistics --}}
             <x-ui.card class="bg-blue-50 border-blue-100">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-blue-100 rounded-full text-blue-600">
                         <x-icon.truck class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-blue-600">{{ __('Bugün Lojistik') }}</p>
                         <div class="flex gap-3 text-sm">
                            <span class="font-bold text-blue-900">{{ floatval($shippedToday) }} <span class="font-normal text-blue-700">Sevk</span></span>
                            <span class="text-blue-300">|</span>
                            <span class="font-bold text-blue-900">{{ floatval($returnedToday) }} <span class="font-normal text-blue-700">İade</span></span>
                         </div>
                    </div>
                </div>
            </x-ui.card>

             {{-- Pending Tasks --}}
             <x-ui.card class="bg-slate-50 border-slate-100">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-slate-100 rounded-full text-slate-600">
                         <x-icon.clipboard-list class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-600">{{ __('Bekleyen Taslaklar') }}</p>
                         <div class="flex gap-3 text-sm">
                            <span class="font-bold text-slate-900">{{ $pendingShipments }} <span class="font-normal text-slate-500">Sevkiyat</span></span>
                            <span class="text-slate-300">|</span>
                            <span class="font-bold text-slate-900">{{ $pendingReturns }} <span class="font-normal text-slate-500">İade</span></span>
                         </div>
                    </div>
                </div>
            </x-ui.card>
        </div>

        {{-- Tables Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            {{-- Negative Stock Table --}}
            <x-ui.card>
                <x-slot name="header">
                    <div class="flex items-center justify-between">
                         <h3 class="font-semibold text-slate-900">{{ __('Negatif Bakiyeler') }}</h3>
                         <span class="text-xs text-rose-600 font-medium">Top 10</span>
                    </div>
                </x-slot>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                            <tr>
                                <th class="px-3 py-2">{{ __('Ürün') }}</th>
                                <th class="px-3 py-2">{{ __('Depo') }}</th>
                                <th class="px-3 py-2 text-right">{{ __('Miktar') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($negativeBalances as $bal)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-slate-900">
                                        {{ $bal->product?->name ?? 'Unknown Product' }}
                                        <div class="text-xs text-slate-500">{{ $bal->product?->sku }}</div>
                                    </td>
                                    <td class="px-3 py-2 text-slate-600">{{ $bal->warehouse?->name ?? 'Unknown Warehouse' }}</td>
                                    <td class="px-3 py-2 text-right font-bold text-rose-600">{{ floatval($bal->qty_on_hand) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-3 py-4 text-center text-slate-400 text-xs">
                                        {{ __('Negatif bakiye bulunmuyor.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

             {{-- Critical Stock Table --}}
            <x-ui.card>
                <x-slot name="header">
                    <div class="flex items-center justify-between">
                         <h3 class="font-semibold text-slate-900">{{ __('Kritik Stok Seviyesi') }}</h3>
                         <span class="text-xs text-amber-600 font-medium">Top 10</span>
                    </div>
                </x-slot>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                            <tr>
                                <th class="px-3 py-2">{{ __('Ürün') }}</th>
                                <th class="px-3 py-2 text-right">{{ __('Toplam Stok') }}</th>
                                <th class="px-3 py-2 text-right">{{ __('Kritik Eşik') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($criticalProducts as $prod)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-slate-900">
                                        {{ $prod->name }}
                                        <div class="text-xs text-slate-500">{{ $prod->sku }}</div>
                                    </td>
                                    <td class="px-3 py-2 text-right font-bold text-amber-600">{{ floatval($prod->total_stock) }}</td>
                                    <td class="px-3 py-2 text-right text-slate-600">{{ floatval($prod->critical_stock_level) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-3 py-4 text-center text-slate-400 text-xs">
                                        {{ __('Kritik seviye altında ürün bulunmuyor.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

        </div>
    </div>
</x-app-layout>
