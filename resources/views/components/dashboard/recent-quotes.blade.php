@props(['recentQuotes'])

@include('components.dashboard._status_mapping')

<x-ui.card class="h-full">
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="font-semibold text-slate-900">{{ __('Son Teklifler') }}</h3>
            <x-ui.button href="{{ route('quotes.index') }}" variant="ghost" size="sm">
                {{ __('Tümünü Gör') }}
            </x-ui.button>
        </div>
    </x-slot>

    @if ($recentQuotes->isNotEmpty())
        <x-ui.table density="compact">
            <x-slot name="head">
                <tr>
                    <x-ui.table.th>{{ __('Teklif No') }}</x-ui.table.th>
                    <x-ui.table.th>{{ __('Müşteri') }}</x-ui.table.th>
                    <x-ui.table.th>{{ __('Durum') }}</x-ui.table.th>
                    <x-ui.table.th align="right">{{ __('Tutar') }}</x-ui.table.th>
                </tr>
            </x-slot>
            <x-slot name="body">
                @foreach ($recentQuotes as $quote)
                    <x-ui.table.tr>
                        <x-ui.table.td>
                            <a href="{{ route('quotes.show', $quote) }}" class="font-medium text-slate-900 hover:text-brand-600 transition">
                                {{ $quote->quote_no }}
                            </a>
                        </x-ui.table.td>
                        <x-ui.table.td>
                            <span class="truncate max-w-[150px] block" title="{{ $quote->customer?->name ?? '-' }}">
                                {{ $quote->customer?->name ?? '-' }}
                            </span>
                        </x-ui.table.td>
                        <x-ui.table.td>
                            <x-ui.badge :variant="$statusVariants[$quote->status] ?? 'neutral'">
                                {{ $quote->status_label }}
                            </x-ui.badge>
                        </x-ui.table.td>
                        <x-ui.table.td align="right" class="font-medium text-slate-900">
                            {{ \App\Support\MoneyMath::formatTR($quote->grand_total) }} {{ $quote->currency }}
                        </x-ui.table.td>
                    </x-ui.table.tr>
                @endforeach
            </x-slot>
        </x-ui.table>
    @else
        <x-ui.empty-state
            title="Henüz teklif yok"
            description="Yeni bir teklif oluşturarak başlayın."
            icon="document"
        >
            <x-slot:actions>
                <x-ui.button href="{{ route('quotes.create') }}" size="sm">
                    {{ __('Yeni Teklif') }}
                </x-ui.button>
            </x-slot>
        </x-ui.empty-state>
    @endif
</x-ui.card>
