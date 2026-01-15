@props(['recentContracts'])

@include('components.dashboard._status_mapping')

<x-ui.card class="h-full">
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="font-semibold text-slate-900">{{ __('Son Sözleşmeler') }}</h3>
            <x-ui.button href="{{ route('contracts.index') }}" variant="ghost" size="sm">
                {{ __('Tümünü Gör') }}
            </x-ui.button>
        </div>
    </x-slot>

    @if ($recentContracts->isNotEmpty())
        <x-ui.table density="compact">
            <x-slot name="head">
                <tr>
                    <x-ui.table.th>{{ __('Sözleşme') }}</x-ui.table.th>
                    <x-ui.table.th>{{ __('Müşteri') }}</x-ui.table.th>
                    <x-ui.table.th>{{ __('Durum') }}</x-ui.table.th>
                    <x-ui.table.th align="right">{{ __('Tarih') }}</x-ui.table.th>
                </tr>
            </x-slot>
            <x-slot name="body">
                @foreach ($recentContracts as $contract)
                    <x-ui.table.tr>
                        <x-ui.table.td>
                            <a href="{{ route('contracts.show', $contract) }}" class="font-medium text-slate-900 hover:text-brand-600 transition">
                                {{ $contract->contract_no }}
                            </a>
                            <div class="text-xs text-slate-500">{{ $contract->revision_label }}</div>
                        </x-ui.table.td>
                        <x-ui.table.td>
                            <span class="truncate max-w-[150px] block" title="{{ $contract->customer_name ?: '-' }}">
                                {{ $contract->customer_name ?: '-' }}
                            </span>
                        </x-ui.table.td>
                        <x-ui.table.td>
                            <x-ui.badge :variant="$statusVariants[$contract->status] ?? 'neutral'">
                                {{ $contract->status_label }}
                            </x-ui.badge>
                        </x-ui.table.td>
                        <x-ui.table.td align="right" class="text-slate-600">
                            {{ $contract->issued_at?->format('d.m.Y') ?? '-' }}
                        </x-ui.table.td>
                    </x-ui.table.tr>
                @endforeach
            </x-slot>
        </x-ui.table>
    @else
        <x-ui.empty-state
            title="Henüz sözleşme bulunmuyor"
            description="Satış siparişleri üzerinden sözleşme oluşturabilirsiniz."
            icon="file"
        >
            <x-slot:actions>
                <x-ui.button href="{{ route('sales-orders.index') }}" size="sm">
                    {{ __('Satış Siparişlerini Gör') }}
                </x-ui.button>
            </x-slot>
        </x-ui.empty-state>
    @endif
</x-ui.card>
