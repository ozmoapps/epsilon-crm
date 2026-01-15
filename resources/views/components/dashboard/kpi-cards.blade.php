@props([
    'quotesCount',
    'quotesStatusCounts',
    'salesOrdersCount',
    'salesOrdersStatusCounts',
    'contractsCount',
    'contractStatusCounts',
    'workOrdersCount',
    'workOrderStatusCounts',
    'customersCount',
    'customersRecentCount',
    'salesOrdersRecentCount',
    'contractsRecentCount',
])

@php
    $quoteStatusLabels = \App\Models\Quote::statusOptions();
    $salesOrderStatusLabels = \App\Models\SalesOrder::statusOptions();
    $contractStatusLabels = \App\Models\Contract::statusOptions();
    $workOrderStatusLabels = \App\Models\WorkOrder::statusOptions();

    $kpiCards = [
        [
            'title' => __('Teklifler'),
            'total' => $quotesCount,
            'statuses' => [
                ['label' => $quoteStatusLabels['sent'] ?? __('Gönderildi'), 'count' => $quotesStatusCounts->get('sent', 0), 'route' => route('quotes.index', ['status' => 'sent'])],
                ['label' => $quoteStatusLabels['accepted'] ?? __('Onaylandı'), 'count' => $quotesStatusCounts->get('accepted', 0), 'route' => route('quotes.index', ['status' => 'accepted'])],
            ],
            'icon_bg' => 'bg-brand-50 text-brand-600',
            'icon' => 'document',
        ],
        [
            'title' => __('Satış Siparişleri'),
            'total' => $salesOrdersCount,
            'statuses' => [
                ['label' => $salesOrderStatusLabels['in_progress'] ?? __('Devam Ediyor'), 'count' => $salesOrdersStatusCounts->get('in_progress', 0), 'route' => route('sales-orders.index', ['status' => 'in_progress'])],
                ['label' => $salesOrderStatusLabels['completed'] ?? __('Tamamlandı'), 'count' => $salesOrdersStatusCounts->get('completed', 0), 'route' => route('sales-orders.index', ['status' => 'completed'])],
            ],
            'icon_bg' => 'bg-emerald-50 text-emerald-600',
            'icon' => 'clipboard',
        ],
        [
            'title' => __('Sözleşmeler'),
            'total' => $contractsCount,
            'statuses' => [
                ['label' => $contractStatusLabels['sent'] ?? __('Gönderildi'), 'count' => $contractStatusCounts->get('sent', 0), 'route' => route('contracts.index', ['status' => 'sent'])],
                ['label' => $contractStatusLabels['signed'] ?? __('İmzalandı'), 'count' => $contractStatusCounts->get('signed', 0), 'route' => route('contracts.index', ['status' => 'signed'])],
            ],
            'icon_bg' => 'bg-violet-50 text-violet-600',
            'icon' => 'file',
        ],
        [
            'title' => __('İş Emirleri'),
            'total' => $workOrdersCount,
            'statuses' => [
                ['label' => $workOrderStatusLabels['planned'] ?? __('Planlandı'), 'count' => $workOrderStatusCounts->get('planned', 0), 'route' => route('work-orders.index', ['status' => 'planned'])],
                ['label' => $workOrderStatusLabels['in_progress'] ?? __('Devam Ediyor'), 'count' => $workOrderStatusCounts->get('in_progress', 0), 'route' => route('work-orders.index', ['status' => 'in_progress'])],
            ],
            'icon_bg' => 'bg-sky-50 text-sky-600',
            'icon' => 'tools',
        ],
    ];
@endphp

<section aria-labelledby="kpi-overview">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 id="kpi-overview" class="text-lg font-semibold text-slate-900">{{ __('Operasyon Özeti') }}</h2>
            <p class="text-sm text-slate-500">{{ __('Tekliften teslimata kadar kritik metrikler.') }}</p>
        </div>
        <div class="flex gap-2">
            <span class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm">
                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                {{ __('Son 7 gün') }}: {{ number_format($customersRecentCount + $salesOrdersRecentCount + $contractsRecentCount) }}
            </span>
            <span class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm">
                {{ __('Toplam Müşteri') }}: {{ number_format($customersCount) }}
            </span>
        </div>
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpiCards as $card)
            <x-ui.card class="relative">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500">{{ $card['title'] }}</p>
                        <p class="mt-2 text-3xl font-semibold text-slate-900 tracking-tight">{{ number_format($card['total']) }}</p>
                        
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($card['statuses'] as $status)
                                <a href="{{ $status['route'] }}" class="inline-flex items-center gap-2 rounded-lg bg-slate-50 border border-slate-100 px-2 py-1 text-xs transition hover:bg-slate-100 hover:border-slate-200 ui-focus">
                                    <span class="font-bold text-slate-900">{{ number_format($status['count']) }}</span>
                                    <span class="text-slate-500 font-medium">{{ $status['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl {{ $card['icon_bg'] }}">
                        <x-dynamic-component :component="'icon.' . $card['icon']" class="h-6 w-6" />
                    </span>
                </div>
            </x-ui.card>
        @endforeach
    </div>
</section>
