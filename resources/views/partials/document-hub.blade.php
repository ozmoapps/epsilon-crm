@props([
    'context',
    'quote' => null,
    'salesOrder' => null,
    'contract' => null,
    'workOrder' => null,
    'timeline',
    'showTimeline' => true,
])

<x-ui.card class="!p-0 overflow-hidden border border-slate-200 rounded-2xl bg-white shadow-card">
    <div class="border-b border-slate-100 bg-white px-4 py-3">
        <h3 class="font-semibold text-slate-900">{{ __('İlgili Dokümanlar') }}</h3>
    </div>

    <div class="divide-y divide-slate-100 px-4">
        {{-- Quote --}}
        <div class="flex flex-col gap-2 py-3 sm:grid sm:grid-cols-12 sm:items-center sm:gap-3">
            <div class="flex min-w-0 items-center gap-3 sm:col-span-7">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-slate-500">
                    <x-icon.document-text class="h-4 w-4" />
                </div>
                <div class="min-w-0">
                    <div class="text-[10px] font-bold tracking-wider text-slate-400">{{ __('Teklif') }}</div>
                    <div class="truncate text-sm font-semibold text-slate-900">
                        @if($quote)
                            {{ $quote->quote_no ?? $quote->id }}
                        @else
                            <span class="font-normal italic text-slate-400">{{ __('Mevcut değil') }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex items-center sm:col-span-3">
                @if($quote)
                    <x-badge :status="$quote->status" class="!px-1.5 !py-0 text-[10px]" />
                @else
                    <span class="text-xs text-slate-300">—</span>
                @endif
            </div>
            <div class="flex items-center justify-end gap-2 sm:col-span-2">
                @if($quote)
                    <x-ui.button href="{{ route('quotes.show', $quote) }}" variant="ghost" size="sm" class="!px-2 !py-1 text-slate-400 hover:text-brand-600 hover:bg-brand-50 rounded-xl" title="{{ __('Görüntüle') }}">
                        <x-icon.eye class="h-4 w-4" />
                    </x-ui.button>
                @else
                    <span class="text-xs text-slate-300">—</span>
                @endif
            </div>
        </div>

        {{-- Sales Order --}}
        <div class="flex flex-col gap-2 py-3 sm:grid sm:grid-cols-12 sm:items-center sm:gap-3">
            <div class="flex min-w-0 items-center gap-3 sm:col-span-7">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-slate-500">
                    <x-icon.shopping-bag class="h-4 w-4" />
                </div>
                <div class="min-w-0">
                    <div class="text-[10px] font-bold tracking-wider text-slate-400">{{ __('Satış Siparişi') }}</div>
                    <div class="truncate text-sm font-semibold text-slate-900">
                        @if($salesOrder)
                            {{ $salesOrder->order_no ?? $salesOrder->id }}
                        @else
                            <span class="font-normal italic text-slate-400">{{ __('Mevcut değil') }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex items-center sm:col-span-3">
                @if($salesOrder)
                    <x-badge :status="$salesOrder->status" class="!px-1.5 !py-0 text-[10px]" />
                @else
                    <span class="text-xs text-slate-300">—</span>
                @endif
            </div>
            <div class="flex items-center justify-end gap-2 sm:col-span-2">
                @if($salesOrder)
                    <x-ui.button href="{{ route('sales-orders.show', $salesOrder) }}" variant="ghost" size="sm" class="!px-2 !py-1 text-slate-400 hover:text-brand-600 hover:bg-brand-50 rounded-xl" title="{{ __('Görüntüle') }}">
                        <x-icon.eye class="h-4 w-4" />
                    </x-ui.button>
                @elseif($context === 'quote' && $quote && $quote->status === 'accepted')
                     <form action="{{ route('quotes.convert_to_sales_order', $quote) }}" method="POST">
                        @csrf
                        <x-ui.button type="submit" size="xs" variant="secondary" class="!py-1 px-3">
                            {{ __('Satışa Çevir') }}
                        </x-ui.button>
                    </form>
                @else
                    <span class="text-xs text-slate-300">—</span>
                @endif
            </div>
        </div>

        {{-- Invoice --}}
        <div class="flex flex-col gap-2 py-3 sm:grid sm:grid-cols-12 sm:items-center sm:gap-3">
            <div class="flex min-w-0 items-center gap-3 sm:col-span-7">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-slate-500">
                    <x-icon.currency class="h-4 w-4" />
                </div>
                <div class="min-w-0">
                    <div class="text-[10px] font-bold tracking-wider text-slate-400">{{ __('Fatura') }}</div>
                    <div class="truncate text-sm font-semibold text-slate-900">
                        @if($salesOrder && $salesOrder->invoices->isNotEmpty())
                             @php $latestInvoice = $salesOrder->invoices->last(); @endphp
                             {{ $latestInvoice->invoice_no ?? '(Taslak)' }}
                        @else
                            <span class="font-normal italic text-slate-400">{{ __('Mevcut değil') }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex items-center sm:col-span-3">
                @if($salesOrder && $salesOrder->invoices->isNotEmpty())
                    @php $latestInvoice = $salesOrder->invoices->last(); @endphp
                    <x-badge :status="$latestInvoice->status" class="!px-1.5 !py-0 text-[10px]" />
                @else
                    <span class="text-xs text-slate-300">—</span>
                @endif
            </div>
            <div class="flex items-center justify-end gap-2 sm:col-span-2">
                @if($salesOrder && $salesOrder->invoices->isNotEmpty())
                    @php $latestInvoice = $salesOrder->invoices->last(); @endphp
                    <x-ui.button href="{{ route('invoices.show', $latestInvoice) }}" variant="ghost" size="sm" class="!px-2 !py-1 text-slate-400 hover:text-brand-600 hover:bg-brand-50 rounded-xl" title="{{ __('Görüntüle') }}">
                        <x-icon.eye class="h-4 w-4" />
                    </x-ui.button>
                @else
                     <span class="text-xs text-slate-300">—</span>
                @endif
            </div>
        </div>

        {{-- Contract --}}
        <div class="flex flex-col gap-2 py-3 sm:grid sm:grid-cols-12 sm:items-center sm:gap-3">
            <div class="flex min-w-0 items-center gap-3 sm:col-span-7">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-slate-500">
                    <x-icon.pencil-alt class="h-4 w-4" />
                </div>
                <div class="min-w-0">
                    <div class="text-[10px] font-bold tracking-wider text-slate-400">{{ __('Sözleşme') }}</div>
                    <div class="truncate text-sm font-semibold text-slate-900">
                        @if($contract)
                            {{ $contract->contract_no ?? $contract->id }}
                        @else
                            <span class="font-normal italic text-slate-400">{{ __('Mevcut değil') }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex items-center sm:col-span-3">
                @if($contract)
                    <x-badge :status="$contract->status" class="!px-1.5 !py-0 text-[10px]" />
                @else
                    <span class="text-xs text-slate-300">—</span>
                @endif
            </div>
            <div class="flex items-center justify-end gap-2 sm:col-span-2">
                @if($contract)
                    <x-ui.button href="{{ route('contracts.show', $contract) }}" variant="ghost" size="sm" class="!px-2 !py-1 text-slate-400 hover:text-brand-600 hover:bg-brand-50 rounded-xl" title="{{ __('Görüntüle') }}">
                        <x-icon.eye class="h-4 w-4" />
                    </x-ui.button>
                @elseif($salesOrder)
                     <x-ui.button href="{{ route('sales-orders.contracts.create', $salesOrder) }}" variant="secondary" size="xs" class="!py-1 px-3">
                        {{ __('Oluştur') }}
                     </x-ui.button>
                @else
                    <span class="text-xs text-slate-300">—</span>
                @endif
            </div>
        </div>

        {{-- Work Order --}}
        <div class="flex flex-col gap-2 py-3 sm:grid sm:grid-cols-12 sm:items-center sm:gap-3">
            <div class="flex min-w-0 items-center gap-3 sm:col-span-7">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-slate-500">
                    <x-icon.briefcase class="h-4 w-4" />
                </div>
                <div class="min-w-0">
                    <div class="text-[10px] font-bold tracking-wider text-slate-400">{{ __('İş Emri') }}</div>
                    <div class="truncate text-sm font-semibold text-slate-900" title="{{ $workOrder->title ?? '' }}">
                        @if($workOrder)
                            {{ \Illuminate\Support\Str::limit($workOrder->title ?? $workOrder->id, 20) }}
                        @else
                            <span class="font-normal italic text-slate-400">{{ __('Mevcut değil') }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex items-center sm:col-span-3">
                @if($workOrder)
                    <x-badge :status="$workOrder->status" class="!px-1.5 !py-0 text-[10px]" />
                @else
                    <span class="text-xs text-slate-300">—</span>
                @endif
            </div>
            <div class="flex items-center justify-end gap-2 sm:col-span-2">
                 @if($workOrder)
                    <x-ui.button href="{{ route('work-orders.show', $workOrder) }}" variant="ghost" size="sm" class="!px-2 !py-1 text-slate-400 hover:text-brand-600 hover:bg-brand-50 rounded-xl" title="{{ __('Görüntüle') }}">
                        <x-icon.eye class="h-4 w-4" />
                    </x-ui.button>
                @elseif($context !== 'work_order')
                     <span class="text-xs text-slate-300">—</span>
                @endif
            </div>
        </div>
    </div>
</x-ui.card>
