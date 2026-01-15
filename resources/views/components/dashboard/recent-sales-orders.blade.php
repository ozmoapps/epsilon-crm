@props(['recentSalesOrders'])

@include('components.dashboard._status_mapping')

<x-ui.card>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="font-semibold text-slate-900">{{ __('Son Satış Siparişleri') }}</h3>
            <x-ui.button href="{{ route('sales-orders.index') }}" variant="ghost" size="sm">
                {{ __('Tümünü Gör') }}
            </x-ui.button>
        </div>
    </x-slot>

    @if ($recentSalesOrders->isNotEmpty())
        <x-ui.table density="compact">
            <x-slot name="head">
                <tr>
                    <x-ui.table.th>{{ __('Sipariş') }}</x-ui.table.th>
                    <x-ui.table.th>{{ __('Müşteri') }}</x-ui.table.th>
                    <x-ui.table.th>{{ __('Durum') }}</x-ui.table.th>
                    <x-ui.table.th align="right">{{ __('Tutar') }}</x-ui.table.th>
                </tr>
            </x-slot>
            <x-slot name="body">
                @foreach ($recentSalesOrders as $salesOrder)
                    <x-ui.table.tr>
                        <x-ui.table.td>
                            <a href="{{ route('sales-orders.show', $salesOrder) }}" class="font-medium text-slate-900 hover:text-brand-600 transition">
                                {{ $salesOrder->order_no }}
                            </a>
                        </x-ui.table.td>
                        <x-ui.table.td>
                            <span class="truncate max-w-[150px] block" title="{{ $salesOrder->customer?->name ?? '-' }}">
                                {{ $salesOrder->customer?->name ?? '-' }}
                            </span>
                        </x-ui.table.td>
                        <x-ui.table.td>
                            <x-ui.badge :variant="$statusVariants[$salesOrder->status] ?? 'neutral'">
                                {{ $salesOrder->status_label }}
                            </x-ui.badge>
                        </x-ui.table.td>
                        <x-ui.table.td align="right" class="font-medium text-slate-900">
                            {{ \App\Support\MoneyMath::formatTR($salesOrder->grand_total) }} {{ $salesOrder->currency }}
                        </x-ui.table.td>
                    </x-ui.table.tr>
                @endforeach
            </x-slot>
        </x-ui.table>
    @else
        <x-ui.empty-state
            title="Henüz satış siparişi yok"
            description="Yeni bir sipariş oluşturarak listeyi başlatın."
            icon="clipboard"
        >
            <x-slot:actions>
                <x-ui.button href="{{ route('sales-orders.create') }}" size="sm">
                    {{ __('Satış Siparişi Oluştur') }}
                </x-ui.button>
            </x-slot>
        </x-ui.empty-state>
    @endif
</x-ui.card>
