<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Kontrol Paneli') }}" subtitle="{{ __('Operasyonların anlık görünümü ve hızlı aksiyon alanı.') }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('quotes.create') }}" size="sm">
                    {{ __('Yeni Teklif') }}
                </x-ui.button>
                <x-ui.button href="{{ route('sales-orders.create') }}" variant="secondary" size="sm">
                    {{ __('Yeni Satış Siparişi') }}
                </x-ui.button>
                <x-ui.button href="{{ route('work-orders.create') }}" variant="secondary" size="sm">
                    {{ __('Yeni İş Emri') }}
                </x-ui.button>
                <x-ui.button href="{{ route('contracts.index') }}" variant="ghost" size="sm">
                    {{ __('Sözleşmelere Git') }}
                </x-ui.button>
            </x-slot>
        </x-page-header>
    </x-slot>

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
                    ['label' => $quoteStatusLabels['sent'] ?? __('Gönderildi'), 'count' => $quoteStatusCounts->get('sent', 0)],
                    ['label' => $quoteStatusLabels['accepted'] ?? __('Onaylandı'), 'count' => $quoteStatusCounts->get('accepted', 0)],
                ],
                'accent' => 'bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white',
                'icon' => 'document',
            ],
            [
                'title' => __('Satış Siparişleri'),
                'total' => $salesOrdersCount,
                'statuses' => [
                    ['label' => $salesOrderStatusLabels['in_progress'] ?? __('Devam Ediyor'), 'count' => $salesOrderStatusCounts->get('in_progress', 0)],
                    ['label' => $salesOrderStatusLabels['completed'] ?? __('Tamamlandı'), 'count' => $salesOrderStatusCounts->get('completed', 0)],
                ],
                'accent' => 'bg-gradient-to-br from-brand-600 via-brand-500 to-brand-600 text-white',
                'icon' => 'clipboard',
            ],
            [
                'title' => __('Sözleşmeler'),
                'total' => $contractsCount,
                'statuses' => [
                    ['label' => $contractStatusLabels['sent'] ?? __('Gönderildi'), 'count' => $contractStatusCounts->get('sent', 0)],
                    ['label' => $contractStatusLabels['signed'] ?? __('İmzalandı'), 'count' => $contractStatusCounts->get('signed', 0)],
                ],
                'accent' => 'bg-gradient-to-br from-emerald-600 via-emerald-500 to-emerald-600 text-white',
                'icon' => 'file',
            ],
            [
                'title' => __('İş Emirleri'),
                'total' => $workOrdersCount,
                'statuses' => [
                    ['label' => $workOrderStatusLabels['planned'] ?? __('Planlandı'), 'count' => $workOrderStatusCounts->get('planned', 0)],
                    ['label' => $workOrderStatusLabels['in_progress'] ?? __('Devam Ediyor'), 'count' => $workOrderStatusCounts->get('in_progress', 0)],
                ],
                'accent' => 'bg-gradient-to-br from-sky-600 via-sky-500 to-sky-600 text-white',
                'icon' => 'tools',
            ],
        ];
    @endphp

    <div class="space-y-8">
        <section aria-labelledby="kpi-overview">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 id="kpi-overview" class="text-lg font-semibold text-slate-900">{{ __('Operasyon Özeti') }}</h2>
                    <p class="text-sm text-slate-500">{{ __('Tekliften teslimata kadar kritik metrikler.') }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        {{ __('Son 7 gün') }}: {{ number_format($customersRecentCount + $salesOrdersRecentCount + $contractsRecentCount) }}
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600">
                        {{ __('Toplam Müşteri') }}: {{ number_format($customersCount) }}
                    </span>
                </div>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($kpiCards as $card)
                    <x-ui.card class="relative overflow-hidden border border-white/10 {{ $card['accent'] }}">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-white/70">{{ $card['title'] }}</p>
                                <p class="mt-3 text-3xl font-semibold text-white">{{ number_format($card['total']) }}</p>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    @foreach ($card['statuses'] as $status)
                                        <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-2.5 py-1 text-xs text-white">
                                            <span class="font-semibold">{{ number_format($status['count']) }}</span>
                                            <span class="text-white/80">{{ $status['label'] }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white/15 text-white" aria-hidden="true">
                                @if ($card['icon'] === 'document')
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10M7 12h8M7 17h6" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 4h11l3 3v13a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z" />
                                    </svg>
                                @elseif ($card['icon'] === 'clipboard')
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10M7 12h10M7 17h6" />
                                        <rect x="3.5" y="4" width="17" height="16" rx="2" />
                                    </svg>
                                @elseif ($card['icon'] === 'file')
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 3h6l4 4v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 3v5h5" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6M9 17h4" />
                                    </svg>
                                @else
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h12l4 5-4 5H4a2 2 0 01-2-2V9a2 2 0 012-2z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 12h5" />
                                    </svg>
                                @endif
                            </span>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-3" aria-labelledby="dashboard-details">
            <h2 id="dashboard-details" class="sr-only">{{ __('Detaylı Görünüm') }}</h2>
            <div class="space-y-6 lg:col-span-2">
                <x-ui.card>
                    <x-slot name="header">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <span>{{ __('Son Satış Siparişleri') }}</span>
                            <x-ui.button href="{{ route('sales-orders.index') }}" variant="secondary" size="sm">
                                {{ __('Tümünü Gör') }}
                            </x-ui.button>
                        </div>
                    </x-slot>

                    @if ($recentSalesOrders->isNotEmpty())
                        @php
                            $statusVariants = [
                                'draft' => 'draft',
                                'confirmed' => 'confirmed',
                                'in_progress' => 'in_progress',
                                'completed' => 'completed',
                                'contracted' => 'success',
                                'cancelled' => 'cancelled',
                            ];
                        @endphp
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm text-slate-700">
                                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left">{{ __('Sipariş') }}</th>
                                        <th scope="col" class="px-4 py-3 text-left">{{ __('Müşteri') }}</th>
                                        <th scope="col" class="px-4 py-3 text-left">{{ __('Durum') }}</th>
                                        <th scope="col" class="px-4 py-3 text-right">{{ __('Tutar') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($recentSalesOrders as $salesOrder)
                                        <tr class="odd:bg-white even:bg-slate-50 hover:bg-slate-100/60">
                                            <td class="px-4 py-3">
                                                <a href="{{ route('sales-orders.show', $salesOrder) }}" class="font-semibold text-slate-900 transition hover:text-brand-600 ui-focus">
                                                    {{ $salesOrder->order_no }}
                                                </a>
                                                <div class="text-xs text-slate-500">{{ $salesOrder->title }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-slate-600">{{ $salesOrder->customer?->name ?? '-' }}</td>
                                            <td class="px-4 py-3">
                                                <x-ui.badge :variant="$statusVariants[$salesOrder->status] ?? 'neutral'">
                                                    {{ $salesOrder->status_label }}
                                                </x-ui.badge>
                                            </td>
                                            <td class="px-4 py-3 text-right text-sm font-semibold text-slate-900">
                                                {{ number_format((float) $salesOrder->grand_total, 2, ',', '.') }} {{ $salesOrder->currency }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="flex flex-col items-center gap-3 py-8 text-center">
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10M7 12h10M7 17h6" />
                                    <rect x="3.5" y="4" width="17" height="16" rx="2" />
                                </svg>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-slate-700">{{ __('Henüz satış siparişi yok') }}</p>
                                <p class="text-xs text-slate-500">{{ __('Yeni bir sipariş oluşturarak listeyi başlatın.') }}</p>
                            </div>
                            <x-ui.button href="{{ route('sales-orders.create') }}" size="sm">
                                {{ __('Satış Siparişi Oluştur') }}
                            </x-ui.button>
                        </div>
                    @endif
                </x-ui.card>

                <x-ui.card>
                    <x-slot name="header">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <span>{{ __('Son Sözleşmeler') }}</span>
                            <x-ui.button href="{{ route('contracts.index') }}" variant="secondary" size="sm">
                                {{ __('Tümünü Gör') }}
                            </x-ui.button>
                        </div>
                    </x-slot>

                    @if ($recentContracts->isNotEmpty())
                        @php
                            $statusVariants = [
                                'draft' => 'draft',
                                'issued' => 'neutral',
                                'sent' => 'sent',
                                'signed' => 'signed',
                                'superseded' => 'neutral',
                                'cancelled' => 'cancelled',
                            ];
                        @endphp
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm text-slate-700">
                                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left">{{ __('Sözleşme') }}</th>
                                        <th scope="col" class="px-4 py-3 text-left">{{ __('Müşteri') }}</th>
                                        <th scope="col" class="px-4 py-3 text-left">{{ __('Durum') }}</th>
                                        <th scope="col" class="px-4 py-3 text-right">{{ __('Tarih') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($recentContracts as $contract)
                                        <tr class="odd:bg-white even:bg-slate-50 hover:bg-slate-100/60">
                                            <td class="px-4 py-3">
                                                <a href="{{ route('contracts.show', $contract) }}" class="font-semibold text-slate-900 transition hover:text-brand-600 ui-focus">
                                                    {{ $contract->contract_no }}
                                                </a>
                                                <div class="text-xs text-slate-500">{{ $contract->revision_label }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-slate-600">{{ $contract->customer_name ?: '-' }}</td>
                                            <td class="px-4 py-3">
                                                <x-ui.badge :variant="$statusVariants[$contract->status] ?? 'neutral'">
                                                    {{ $contract->status_label }}
                                                </x-ui.badge>
                                            </td>
                                            <td class="px-4 py-3 text-right text-sm text-slate-600">
                                                {{ $contract->issued_at?->format('d.m.Y') ?? '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="flex flex-col items-center gap-3 py-8 text-center">
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 3h6l4 4v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 3v5h5" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6M9 17h4" />
                                </svg>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-slate-700">{{ __('Henüz sözleşme bulunmuyor') }}</p>
                                <p class="text-xs text-slate-500">{{ __('Satış siparişleri üzerinden sözleşme oluşturabilirsiniz.') }}</p>
                            </div>
                            <x-ui.button href="{{ route('sales-orders.index') }}" size="sm">
                                {{ __('Satış Siparişlerini Gör') }}
                            </x-ui.button>
                        </div>
                    @endif
                </x-ui.card>
            </div>

            <div class="space-y-6">
                <x-ui.card>
                    <x-slot name="header">
                        <div class="flex items-center justify-between">
                            <span>{{ __('Hızlı Aksiyonlar') }}</span>
                            <span class="text-xs font-medium text-slate-400">{{ __('Klavye ile erişilebilir') }}</span>
                        </div>
                    </x-slot>

                    <div class="grid gap-3">
                        <x-ui.button href="{{ route('quotes.create') }}" class="w-full justify-between" aria-label="{{ __('Yeni teklif oluştur') }}">
                            <span>{{ __('Yeni Teklif') }}</span>
                            <x-dynamic-component :component="'icon.plus'" class="h-4 w-4" aria-hidden="true" />
                        </x-ui.button>
                        <x-ui.button href="{{ route('sales-orders.create') }}" variant="secondary" class="w-full justify-between" aria-label="{{ __('Yeni satış siparişi oluştur') }}">
                            <span>{{ __('Yeni Satış Siparişi') }}</span>
                            <x-dynamic-component :component="'icon.plus'" class="h-4 w-4" aria-hidden="true" />
                        </x-ui.button>
                        <x-ui.button href="{{ route('work-orders.create') }}" variant="secondary" class="w-full justify-between" aria-label="{{ __('Yeni iş emri oluştur') }}">
                            <span>{{ __('Yeni İş Emri') }}</span>
                            <x-dynamic-component :component="'icon.plus'" class="h-4 w-4" aria-hidden="true" />
                        </x-ui.button>
                        <x-ui.button href="{{ route('contracts.index') }}" variant="ghost" class="w-full justify-between" aria-label="{{ __('Sözleşmeleri görüntüle') }}">
                            <span>{{ __('Sözleşmeleri Görüntüle') }}</span>
                            <x-dynamic-component :component="'icon.arrow-right'" class="h-4 w-4" aria-hidden="true" />
                        </x-ui.button>
                    </div>
                </x-ui.card>

                <x-activity-timeline :logs="$recentActivity" :show-subject="true" title="{{ __('Son Aktivite') }}" />
            </div>
        </section>
    </div>
</x-app-layout>
