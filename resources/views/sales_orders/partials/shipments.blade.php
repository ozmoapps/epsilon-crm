<x-ui.card class="mt-6">
    <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
        <h3 class="font-semibold text-slate-900">{{ __('Sevkiyatlar') }}</h3>
        @if(!$salesOrder->stock_posted_at)
            <x-ui.button href="{{ route('sales-orders.shipments.create', $salesOrder) }}" size="sm">
                <x-icon.plus class="w-4 h-4 mr-1" />
                {{ __('Yeni Sevkiyat') }}
            </x-ui.button>
        @endif
    </div>

    @if($salesOrder->shipments->isEmpty())
        <div class="text-center py-6">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-50 text-slate-400 mb-3">
                <x-icon.truck class="w-6 h-6" />
            </div>
            <p class="text-sm text-slate-500">{{ __('Henüz sevkiyat kaydı bulunmuyor.') }}</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 w-16">#ID</th>
                        <th class="px-4 py-3">{{ __('Depo') }}</th>
                        <th class="px-4 py-3">{{ __('Durum') }}</th>
                        <th class="px-4 py-3">{{ __('Not') }}</th>
                        <th class="px-4 py-3">{{ __('Tarih') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('İşlemler') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($salesOrder->shipments as $shipment)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-4 py-3 font-medium text-slate-900">
                                <a href="{{ route('sales-orders.shipments.show', [$salesOrder, $shipment]) }}" class="hover:text-brand-600 hover:underline underline-offset-2">
                                    #{{ $shipment->id }}
                                </a>
                            </td>
                            <td class="px-4 py-3">{{ $shipment->warehouse->name }}</td>
                            <td class="px-4 py-3">
                                <x-badge status="{{ $shipment->status }}">{{ $shipment->status === 'posted' ? 'İşlendi' : 'Taslak' }}</x-badge>
                            </td>
                            <td class="px-4 py-3 text-slate-500 truncate max-w-xs">{{ $shipment->note ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-500">
                                {{ $shipment->created_at->format('d.m.Y H:i') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                     <a href="{{ route('sales-orders.shipments.show', [$salesOrder, $shipment]) }}" class="text-slate-400 hover:text-brand-600 transition p-1">
                                        <x-icon.eye class="w-4 h-4" />
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-ui.card>
