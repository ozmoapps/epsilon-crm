<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Faturalar</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Fatura oluşturmak için önce bir Satış Siparişi seçmelisin.
                </p>
            </div>

            <x-ui.button href="{{ route('sales-orders.index') }}" variant="secondary">
                {{ __('Satış Siparişlerinden Fatura Oluştur') }}
            </x-ui.button>
        </div>

        <form method="GET" action="{{ route('invoices.index') }}" class="mt-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-slate-600">Arama</label>
                    <input
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Fatura no, müşteri, sipariş no..."
                        class="ui-input mt-1"
                    />
                </div>

                <div class="w-full sm:w-56">
                    <label class="block text-xs font-semibold text-slate-600">Durum</label>
                    <select
                        name="status"
                        class="ui-input mt-1"
                    >
                        <option value="">Tümü</option>
                        @foreach (['draft' => 'Taslak', 'issued' => 'Resmileşmiş', 'cancelled' => 'İptal'] as $k => $v)
                            <option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <x-ui.button type="submit" variant="primary">
                        {{ __('Filtrele') }}
                    </x-ui.button>

                    <x-ui.button href="{{ route('invoices.index') }}" variant="secondary">
                        {{ __('Temizle') }}
                    </x-ui.button>
                </div>
            </div>
        </form>

        <div class="mt-6 border border-slate-200 rounded-xl overflow-hidden shadow-soft bg-white">
            <div class="overflow-x-auto">
                <x-ui.table>
                    <x-slot name="head">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Fatura</th>
                            <th class="px-4 py-3 text-left font-semibold">Müşteri</th>
                            <th class="px-4 py-3 text-left font-semibold">Tarih</th>
                            <th class="px-4 py-3 text-left font-semibold">Durum</th>
                            <th class="px-4 py-3 text-right font-semibold">Toplam</th>
                            <th class="px-4 py-3 relative"><span class="sr-only">İşlemler</span></th>
                        </tr>
                    </x-slot>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($invoices as $invoice)
                            @php
                                $status = $invoice->status ?? 'draft';
                                $statusLabel = match ($status) {
                                    'issued' => 'Resmileşmiş',
                                    'cancelled' => 'İptal',
                                    default => 'Taslak',
                                };

                                $badgeVariant = match ($status) {
                                    'issued' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'neutral',
                                };

                                $customerName = $invoice->salesOrder?->customer?->name ?? '-';
                                $issueDate = $invoice->issue_date
                                    ? \Illuminate\Support\Carbon::parse($invoice->issue_date)->format('d.m.Y')
                                    : '—';
                                $total = is_null($invoice->total) ? null : (float) $invoice->total;
                                $currency = $invoice->currency ?? '';
                            @endphp

                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-4 py-3">
                                    <a href="{{ route('invoices.show', $invoice) }}" class="font-semibold text-slate-900 hover:underline">
                                        {{ $invoice->invoice_no ?: ('Taslak #' . $invoice->id) }}
                                    </a>
                                    <div class="mt-0.5 text-xs text-slate-500">
                                        Sipariş #{{ $invoice->sales_order_id }}
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-sm text-slate-700">
                                    {{ $customerName }}
                                </td>

                                <td class="px-4 py-3 text-sm text-slate-700">
                                    {{ $issueDate }}
                                </td>

                                <td class="px-4 py-3">
                                    <x-ui.badge :variant="$badgeVariant" class="!px-2 !py-1 text-xs font-semibold">
                                        {{ $statusLabel }}
                                    </x-ui.badge>
                                </td>

                                <td class="px-4 py-3 text-right text-sm font-semibold text-slate-900">
                                    @if (!is_null($total))
                                        {{ number_format($total, 2, ',', '.') }} {{ $currency }}
                                    @else
                                        —
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <x-ui.button href="{{ route('invoices.show', $invoice) }}" variant="secondary" size="xs">
                                        {{ __('Görüntüle') }}
                                    </x-ui.button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-slate-500">
                                    <div class="mx-auto h-12 w-12 text-slate-300 mb-3">
                                        <x-icon.search class="w-12 h-12" />
                                    </div>
                                    <div class="text-lg font-medium text-slate-900">{{ __('Sonuç bulunamadı') }}</div>
                                    <div class="mt-1 text-sm text-slate-500">{{ __('Filtreleri değiştirip tekrar deneyin.') }}</div>
                                    <div class="mt-6">
                                        <x-ui.button href="{{ route('invoices.index') }}" variant="secondary">
                                            {{ __('Temizle') }}
                                        </x-ui.button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-ui.table>
            </div>

            @if($invoices->hasPages())
                <div class="bg-slate-50 border-t border-slate-200 p-4">
                    {{ $invoices->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
