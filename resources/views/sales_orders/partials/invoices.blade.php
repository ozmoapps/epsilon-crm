<x-ui.card class="mb-6">
    <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
        <h3 class="font-semibold text-slate-900">{{ __('Faturalar') }}</h3>
        
        <x-ui.button href="{{ route('invoices.create', ['sales_order_id' => $salesOrder->id]) }}" size="sm">
            <x-icon.plus class="w-4 h-4 mr-1"/>
            {{ __('Fatura Oluştur') }}
        </x-ui.button>
    </div>

    @if($salesOrder->invoices->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">{{ __('Fatura No') }}</th>
                        <th class="px-4 py-3">{{ __('Tarih') }}</th>
                        <th class="px-4 py-3">{{ __('Durum') }}</th>
                        <th class="px-4 py-3">{{ __('Ödeme') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Tutar') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('İşlemler') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 border-t border-slate-100">
                    @foreach($salesOrder->invoices as $invoice)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-4 py-3 font-medium text-slate-900">
                                {{ $invoice->invoice_no ?? '(Taslak)' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $invoice->issue_date ? $invoice->issue_date->format('d.m.Y') : '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <x-ui.badge :variant="$invoice->status === 'issued' ? 'success' : ($invoice->status === 'cancelled' ? 'danger' : 'neutral')">
                                    {{ $invoice->status_label }}
                                </x-ui.badge>
                            </td>
                            <td class="px-4 py-3">
                                <x-ui.badge :variant="$invoice->payment_status === 'paid' ? 'success' : ($invoice->payment_status === 'partial' ? 'info' : 'neutral')">
                                    {{ $invoice->payment_status_label }}
                                </x-ui.badge>
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-slate-900">
                                {{ \App\Support\MoneyMath::formatTR($invoice->total) }} {{ $invoice->currency }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('invoices.show', $invoice) }}" class="text-brand-600 hover:text-brand-800 font-medium text-xs">
                                    {{ __('Görüntüle') }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50/50 p-8 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                <x-icon.document-text class="h-6 w-6 text-slate-400" />
            </div>
            <h3 class="mt-2 text-sm font-semibold text-slate-900">{{ __('Fatura Bulunamadı') }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ __('Henüz bu sipariş için oluşturulmuş bir fatura yok.') }}</p>
        </div>
    @endif
</x-ui.card>
