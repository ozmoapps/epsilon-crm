<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Kontrol Paneli') }}" subtitle="{{ __('Teknik servis operasyonlarına hızlı bakış.') }}">
            <x-slot name="actions">
                <x-button href="{{ route('customers.create') }}" size="sm">
                    {{ __('Yeni Müşteri') }}
                </x-button>
                <x-button href="{{ route('sales-orders.create') }}" variant="secondary" size="sm">
                    {{ __('Yeni Satış Siparişi') }}
                </x-button>
                <x-button href="{{ route('contracts.index') }}" variant="secondary" size="sm">
                    {{ __('Sözleşmelere Git') }}
                </x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-8">
        <div class="grid gap-4 md:grid-cols-3">
            <div class="ui-card p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('Müşteriler') }}</p>
                        <p class="mt-3 text-3xl font-semibold text-slate-900">{{ number_format($customersCount) }}</p>
                        <p class="mt-2 text-xs text-slate-500">
                            {{ __('Son 7 gün') }}: <span class="font-semibold text-slate-700">{{ number_format($customersRecentCount) }}</span>
                        </p>
                    </div>
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 12a4 4 0 100-8 4 4 0 000 8z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 20a8 8 0 0116 0" />
                        </svg>
                    </span>
                </div>
            </div>
            <div class="ui-card p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('Satış Siparişleri') }}</p>
                        <p class="mt-3 text-3xl font-semibold text-slate-900">{{ number_format($salesOrdersCount) }}</p>
                        <p class="mt-2 text-xs text-slate-500">
                            {{ __('Son 7 gün') }}: <span class="font-semibold text-slate-700">{{ number_format($salesOrdersRecentCount) }}</span>
                        </p>
                    </div>
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10M7 12h10M7 17h6" />
                            <rect x="3.5" y="4" width="17" height="16" rx="2" />
                        </svg>
                    </span>
                </div>
            </div>
            <div class="ui-card p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('Sözleşmeler') }}</p>
                        <p class="mt-3 text-3xl font-semibold text-slate-900">{{ number_format($contractsCount) }}</p>
                        <p class="mt-2 text-xs text-slate-500">
                            {{ __('Son 7 gün') }}: <span class="font-semibold text-slate-700">{{ number_format($contractsRecentCount) }}</span>
                        </p>
                    </div>
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 3h6l4 4v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 3v5h5" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6M9 17h4" />
                        </svg>
                    </span>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-card>
                <x-slot name="header">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <span>{{ __('Son Satış Siparişleri') }}</span>
                        <x-button href="{{ route('sales-orders.index') }}" variant="secondary" size="sm">
                            {{ __('Tümünü Gör') }}
                        </x-button>
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
                                    <th class="px-4 py-3 text-left">{{ __('Sipariş') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('Müşteri') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('Durum') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Tutar') }}</th>
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
                        <x-button href="{{ route('sales-orders.create') }}" size="sm">
                            {{ __('Satış Siparişi Oluştur') }}
                        </x-button>
                    </div>
                @endif
            </x-card>

            <x-card>
                <x-slot name="header">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <span>{{ __('Son Sözleşmeler') }}</span>
                        <x-button href="{{ route('contracts.index') }}" variant="secondary" size="sm">
                            {{ __('Tümünü Gör') }}
                        </x-button>
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
                                    <th class="px-4 py-3 text-left">{{ __('Sözleşme') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('Müşteri') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('Durum') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Tarih') }}</th>
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
                        <x-button href="{{ route('sales-orders.index') }}" size="sm">
                            {{ __('Satış Siparişlerini Gör') }}
                        </x-button>
                    </div>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
