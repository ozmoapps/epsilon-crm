<x-ui.card class="mt-6">
    <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
        <h3 class="font-semibold text-slate-900">{{ __('İadeler') }}</h3>
    </div>

    @if($salesOrder->returns->isEmpty())
        <div class="text-center py-6">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-50 text-slate-400 mb-3">
                <x-icon.refresh class="w-6 h-6" /> <!-- Assuming refresh icon or similar for return -->
            </div>
            <p class="text-sm text-slate-500">{{ __('Henüz iade kaydı bulunmuyor.') }}</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 w-16">#ID</th>
                        <th class="px-4 py-3">{{ __('İlgili Sevkiyat') }}</th>
                        <th class="px-4 py-3">{{ __('Depo') }}</th>
                        <th class="px-4 py-3">{{ __('Durum') }}</th>
                        <th class="px-4 py-3">{{ __('Tarih') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('İşlemler') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($salesOrder->returns as $return)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-4 py-3 font-medium text-slate-900">
                                <a href="{{ route('returns.show', $return) }}" class="hover:text-brand-600 hover:underline underline-offset-2">
                                    #{{ $return->id }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-slate-500">
                                <a href="{{ route('sales-orders.shipments.show', [$salesOrder, $return->shipment]) }}" class="hover:underline">
                                    #{{ $return->sales_order_shipment_id }}
                                </a>
                            </td>
                            <td class="px-4 py-3">{{ $return->warehouse->name }}</td>
                            <td class="px-4 py-3">
                                <x-badge status="{{ $return->status }}">{{ $return->status === 'posted' ? 'İşlendi' : 'Taslak' }}</x-badge>
                            </td>
                            <td class="px-4 py-3 text-slate-500">
                                {{ $return->created_at->format('d.m.Y H:i') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                     <a href="{{ route('returns.show', $return) }}" class="text-slate-400 hover:text-brand-600 transition p-1">
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
