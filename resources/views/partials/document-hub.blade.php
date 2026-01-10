@props([
    'context',
    'quote' => null,
    'salesOrder' => null,
    'contract' => null,
    'workOrder' => null,
    'timeline',
    'showTimeline' => true,
])

<x-card class="!p-0 overflow-hidden border border-slate-200 rounded-2xl shadow-sm bg-white">
    <div class="px-5 py-4 border-b border-slate-100 bg-white">
        <h3 class="font-semibold text-slate-900">{{ __('İlgili Dokümanlar') }}</h3>
    </div>

    <div class="divide-y divide-slate-100 px-5">
        {{-- Quote --}}
        <div class="flex items-center gap-3 py-3 group">
            <div class="h-9 w-9 shrink-0 rounded-xl bg-slate-50 flex items-center justify-center text-slate-500">
                <x-icon.document-text class="h-4 w-4" />
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('Teklif') }}</div>
                    @if($quote)
                        <x-badge :status="$quote->status" class="!px-1.5 !py-0 text-[10px]" />
                    @endif
                </div>
                <div class="truncate text-sm font-semibold text-slate-900">
                    @if($quote)
                        {{ $quote->quote_no ?? $quote->id }}
                    @else
                        <span class="text-slate-400 font-normal italic">{{ __('Mevcut değil') }}</span>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if($quote)
                    <x-button href="{{ route('quotes.show', $quote) }}" variant="ghost" size="sm" class="!px-2 !py-1 text-slate-400 hover:text-brand-600 hover:bg-brand-50 rounded-lg" title="{{ __('Görüntüle') }}">
                        <x-icon.eye class="h-4 w-4" />
                    </x-button>
                @endif
            </div>
        </div>

        {{-- Sales Order --}}
        <div class="flex items-center gap-3 py-3 group">
            <div class="h-9 w-9 shrink-0 rounded-xl bg-slate-50 flex items-center justify-center text-slate-500">
                <x-icon.shopping-bag class="h-4 w-4" />
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('Satış Siparişi') }}</div>
                    @if($salesOrder)
                        <x-badge :status="$salesOrder->status" class="!px-1.5 !py-0 text-[10px]" />
                    @endif
                </div>
                <div class="truncate text-sm font-semibold text-slate-900">
                    @if($salesOrder)
                        {{ $salesOrder->order_no ?? $salesOrder->id }}
                    @else
                        <span class="text-slate-400 font-normal italic">{{ __('Mevcut değil') }}</span>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if($salesOrder)
                    <x-button href="{{ route('sales-orders.show', $salesOrder) }}" variant="ghost" size="sm" class="!px-2 !py-1 text-slate-400 hover:text-brand-600 hover:bg-brand-50 rounded-lg" title="{{ __('Görüntüle') }}">
                        <x-icon.eye class="h-4 w-4" />
                    </x-button>
                @elseif($context === 'quote' && $quote && $quote->status === 'accepted')
                     <form action="{{ route('quotes.convert_to_sales_order', $quote) }}" method="POST">
                        @csrf
                        <x-button type="submit" size="xs" variant="secondary" class="!py-1 px-3">
                            {{ __('Satışa Çevir') }}
                        </x-button>
                    </form>
                @else
                    <span class="text-slate-300 select-none">—</span>
                @endif
            </div>
        </div>

        {{-- Contract --}}
        <div class="flex items-center gap-3 py-3 group">
            <div class="h-9 w-9 shrink-0 rounded-xl bg-slate-50 flex items-center justify-center text-slate-500">
                <x-icon.pencil-alt class="h-4 w-4" />
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('Sözleşme') }}</div>
                    @if($contract)
                        <x-badge :status="$contract->status" class="!px-1.5 !py-0 text-[10px]" />
                    @endif
                </div>
                <div class="truncate text-sm font-semibold text-slate-900">
                    @if($contract)
                        {{ $contract->contract_no ?? $contract->id }}
                    @else
                        <span class="text-slate-400 font-normal italic">{{ __('Mevcut değil') }}</span>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if($contract)
                    <x-button href="{{ route('contracts.show', $contract) }}" variant="ghost" size="sm" class="!px-2 !py-1 text-slate-400 hover:text-brand-600 hover:bg-brand-50 rounded-lg" title="{{ __('Görüntüle') }}">
                        <x-icon.eye class="h-4 w-4" />
                    </x-button>
                @elseif($salesOrder)
                     <x-button href="{{ route('sales-orders.contracts.create', $salesOrder) }}" variant="secondary" size="xs" class="!py-1 px-3">
                        {{ __('Oluştur') }}
                     </x-button>
                @else
                    <span class="text-slate-300 select-none">—</span>
                @endif
            </div>
        </div>

        {{-- Work Order --}}
        <div class="flex items-center gap-3 py-3 group">
            <div class="h-9 w-9 shrink-0 rounded-xl bg-slate-50 flex items-center justify-center text-slate-500">
                <x-icon.briefcase class="h-4 w-4" />
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('İş Emri') }}</div>
                    @if($workOrder)
                        <x-badge :status="$workOrder->status" class="!px-1.5 !py-0 text-[10px]" />
                    @endif
                </div>
                <div class="truncate text-sm font-semibold text-slate-900" title="{{ $workOrder->title ?? '' }}">
                    @if($workOrder)
                        {{ \Illuminate\Support\Str::limit($workOrder->title ?? $workOrder->id, 20) }}
                    @else
                        <span class="text-slate-400 font-normal italic">{{ __('Mevcut değil') }}</span>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                 @if($workOrder)
                      <x-button href="{{ route('work-orders.show', $workOrder) }}" variant="ghost" size="sm" class="!px-2 !py-1 text-slate-400 hover:text-brand-600 hover:bg-brand-50 rounded-lg" title="{{ __('Görüntüle') }}">
                        <x-icon.eye class="h-4 w-4" />
                    </x-button>
                @elseif($context !== 'work_order')
                     <span class="text-slate-300 select-none">—</span>
                @endif
            </div>
        </div>
    </div>
</x-card>
