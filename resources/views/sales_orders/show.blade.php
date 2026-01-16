<x-app-layout>
    @php
        $canConfirm = $salesOrder->status === 'draft';
        $canStart = $salesOrder->status === 'confirmed';
        $canComplete = $salesOrder->status === 'in_progress';
        $canCancel = ! in_array($salesOrder->status, ['completed', 'cancelled', 'contracted'], true);
        $hasContract = (bool) $salesOrder->contract;
        $isLocked = $salesOrder->isLocked();

        $itemTypes = config('sales_orders.item_types', []);
        $currencySymbols = config('quotes.currency_symbols', []);
        $currencySymbol = $currencySymbols[$salesOrder->currency] ?? $salesOrder->currency;
        $itemsBySection = $salesOrder->items->groupBy(fn ($item) => $item->section ?: 'Genel');
        $formatMoney = fn ($value) => \App\Support\MoneyMath::formatTR($value);
    @endphp

    @component('partials.show-layout')
        @slot('header')
            @component('partials.page-header', [
                'title' => __('Satış Siparişi') . ' ' . ($salesOrder->order_no ?? '#' . $salesOrder->id),
                'subtitle' => ($salesOrder->customer?->name ?? '-') . ' • ' . ($salesOrder->vessel?->name ?? '-') . ' • ' . ($salesOrder->order_date ? $salesOrder->order_date->format('d.m.Y') : '-'),
            ])
                @slot('status')
                     <x-badge status="{{ $salesOrder->status }}">{{ $salesOrder->status_label }}</x-badge>
                @endslot

                @slot('actions')
                    @if ($hasContract)
                        <x-ui.button href="{{ route('contracts.show', $salesOrder->contract) }}" variant="secondary" size="sm">
                            {{ __('Sözleşmeyi Görüntüle') }}
                        </x-ui.button>
                    @else
                        <form id="sales-order-contract-create-{{ $salesOrder->id }}" method="GET" action="{{ route('sales-orders.contracts.create', $salesOrder) }}" class="hidden"></form>
                        <x-ui.confirm
                            title="{{ __('Sözleşme oluşturmayı onayla') }}"
                            message="{{ __('Bu siparişten yeni bir sözleşme oluşturulacak. Devam etmek istiyor musunuz?') }}"
                            confirm-text="{{ __('Sözleşme oluştur') }}"
                            cancel-text="{{ __('Vazgeç') }}"
                            variant="primary"
                            form-id="sales-order-contract-create-{{ $salesOrder->id }}"
                        >
                            <x-slot name="trigger">
                                <x-ui.button type="button" size="sm">
                                    {{ __('Sözleşme Oluştur') }}
                                </x-ui.button>
                            </x-slot>
                        </x-ui.confirm>
                    @endif

                    @if ($salesOrder->work_order_id)
                        <x-ui.button href="{{ route('work-orders.show', $salesOrder->work_order_id) }}" variant="secondary" size="sm">
                            <x-icon.clipboard class="h-4 w-4 mr-2" />
                            {{ __('İş Emrini Görüntüle') }}
                        </x-ui.button>
                     @elseif(auth()->user()->is_admin)
                        <form id="sales-order-work-order-create-{{ $salesOrder->id }}" method="POST" action="{{ route('sales-orders.create-work-order', $salesOrder) }}" class="hidden">
                            @csrf
                        </form>
                        <x-ui.confirm
                            title="{{ __('İş Emri Oluşturmayı Onayla') }}"
                            message="{{ __('Bu siparişten yeni bir iş emri oluşturulacak. Devam etmek istiyor musunuz?') }}"
                            confirm-text="{{ __('İş Emri Oluştur') }}"
                            cancel-text="{{ __('Vazgeç') }}"
                            variant="primary"
                            form-id="sales-order-work-order-create-{{ $salesOrder->id }}"
                        >
                            <x-slot name="trigger">
                                <x-ui.button type="button" size="sm" variant="secondary">
                                    <x-icon.clipboard class="h-4 w-4 mr-2" />
                                    {{ __('İş Emri Oluştur') }}
                                </x-ui.button>
                            </x-slot>
                        </x-ui.confirm>
                    @endif

                    <x-ui.dropdown align="right" width="w-60">
                        <x-slot name="trigger">
                             <button class="inline-flex items-center px-3 py-2 border border-slate-200 shadow-card text-sm leading-4 font-medium rounded-xl text-slate-700 bg-white hover:bg-slate-50 focus:outline-none transition ease-in-out duration-150 gap-2">
                                {{ __('İşlemler') }}
                                <x-icon.dots class="h-4 w-4" />
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            @if ($canConfirm)
                                <form method="POST" action="{{ route('sales-orders.confirm', $salesOrder) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50">
                                        <x-icon.check class="h-4 w-4 text-emerald-600" />
                                        {{ __('Onayla') }}
                                    </button>
                                </form>
                            @endif
                            @if ($canStart)
                                 <form method="POST" action="{{ route('sales-orders.start', $salesOrder) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50">
                                        <x-icon.arrow-right class="h-4 w-4 text-blue-600" />
                                        {{ __('Devam Ettir') }}
                                    </button>
                                </form>
                            @endif
                             @if ($canComplete)
                                <form method="POST" action="{{ route('sales-orders.complete', $salesOrder) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50">
                                        <x-icon.check class="h-4 w-4 text-emerald-600" />
                                        {{ __('Tamamla') }}
                                    </button>
                                </form>
                            @endif
                             @if ($canCancel)
                                <form id="sales-order-cancel-{{ $salesOrder->id }}" method="POST" action="{{ route('sales-orders.cancel', $salesOrder) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" 
                                        class="flex w-full items-center gap-2 px-3 py-2 text-sm text-rose-600 transition hover:bg-rose-50"
                                        data-confirm
                                        data-confirm-title="{{ __('Emin misiniz?') }}"
                                        data-confirm-message="{{ __('Satış siparişi iptal edilecek. Bu işlem geri alınamaz.') }}"
                                        data-confirm-text="{{ __('İptal Et') }}"
                                        data-confirm-cancel-text="{{ __('Vazgeç') }}"
                                        data-confirm-submit="sales-order-cancel-{{ $salesOrder->id }}">
                                        <x-icon.x class="h-4 w-4" />
                                        {{ __('İptal') }}
                                    </button>
                                </form>
                            @endif
                            
                            <div class="my-1 h-px bg-slate-100"></div>
    
                            @if ($isLocked)
                                 <button type="button" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50 cursor-not-allowed opacity-60" disabled>
                                    <x-icon.pencil class="h-4 w-4 text-brand-600" />
                                    {{ __('Düzenle') }}
                                    <x-ui.badge variant="neutral" class="ml-auto !px-1 !py-0.5 text-[10px]">
                                        {{ __('Kilitli') }}
                                    </x-ui.badge>
                                </button>
                            @else
                                 <a href="{{ route('sales-orders.edit', $salesOrder) }}" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50">
                                    <x-icon.pencil class="h-4 w-4 text-brand-600" />
                                    {{ __('Düzenle') }}
                                </a>
                            @endif
    
                             @if ($isLocked)
                                <button type="button" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-rose-600 transition hover:bg-rose-50 cursor-not-allowed opacity-60" disabled>
                                    <x-icon.trash class="h-4 w-4" />
                                    {{ __('Sil') }}
                                    <x-ui.badge variant="neutral" class="ml-auto !px-1 !py-0.5 text-[10px]">
                                        {{ __('Kilitli') }}
                                    </x-ui.badge>
                                </button>
                            @else
                                <form id="sales-order-delete-{{ $salesOrder->id }}" method="POST" action="{{ route('sales-orders.destroy', $salesOrder) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                        class="flex w-full items-center gap-2 px-3 py-2 text-sm text-rose-600 transition hover:bg-rose-50"
                                        data-confirm
                                        data-confirm-title="{{ __('Emin misiniz?') }}"
                                        data-confirm-message="{{ __('Sipariş silinecek. Bu işlem geri alınamaz.') }}"
                                        data-confirm-text="{{ __('Sil') }}"
                                        data-confirm-cancel-text="{{ __('Vazgeç') }}"
                                        data-confirm-submit="sales-order-delete-{{ $salesOrder->id }}">
                                        <x-icon.trash class="h-4 w-4" />
                                        {{ __('Sil') }}
                                    </button>
                                </form>
                            @endif
                        </x-slot>
                    </x-ui.dropdown>
    
                    <x-ui.button href="{{ route('sales-orders.index') }}" variant="secondary" size="sm">
                        {{ __('Listeye Dön') }}
                    </x-ui.button>
                @endslot
            @endcomponent
        @endslot

        @slot('left')
            {{-- General Info --}}
            <x-ui.card class="rounded-2xl border border-slate-200 bg-white shadow-card !p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-bold tracking-wider text-slate-500 mb-1">{{ __('Müşteri') }}</p>
                        <a href="{{ route('admin.company-profiles.show', $salesOrder->customer_id) }}" class="text-sm font-medium text-slate-900 hover:text-brand-600 hover:underline decoration-brand-600/50 underline-offset-4 transition-all">
                            {{ $salesOrder->customer?->name ?? '-' }}
                        </a>
                    </div>
                    <div>
                        <p class="text-xs font-bold tracking-wider text-slate-500 mb-1">{{ __('Tekne') }}</p>
                        <p class="text-sm font-medium text-slate-900">{{ $salesOrder->vessel?->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold tracking-wider text-slate-500 mb-1">{{ __('İş Emri') }}</p>
                        <p class="text-sm font-medium text-slate-900">{{ $salesOrder->workOrder?->title ?? '-' }}</p>
                    </div>
                     <div>
                        <p class="text-xs font-bold tracking-wider text-slate-500 mb-1">{{ __('Sipariş Tarihi') }}</p>
                        <p class="text-sm font-medium text-slate-900">
                            {{ $salesOrder->order_date ? $salesOrder->order_date->format('d.m.Y') : '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-bold tracking-wider text-slate-500 mb-1">{{ __('Teslim Yeri') }}</p>
                        <p class="text-sm font-medium text-slate-900">{{ $salesOrder->delivery_place ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold tracking-wider text-slate-500 mb-1">{{ __('Teslim Süresi') }}</p>
                        <p class="text-sm font-medium text-slate-900">
                            {{ $salesOrder->delivery_days !== null ? $salesOrder->delivery_days . ' gün' : '-' }}
                        </p>
                    </div>
                    <div class="sm:col-span-2 border-t border-slate-100 pt-4 mt-2">
                        <p class="text-xs font-bold tracking-wider text-slate-500 mb-1">{{ __('Sipariş Başlığı') }}</p>
                        <p class="text-sm font-medium text-slate-900">{{ $salesOrder->title }}</p>
                    </div>
                     @if ($salesOrder->quote)
                        <div class="sm:col-span-2">
                            <p class="text-xs font-bold tracking-wider text-slate-500 mb-1">{{ __('Kaynak Teklif') }}</p>
                            <p class="mt-1 text-base font-medium text-slate-900">
                                <a href="{{ route('quotes.show', $salesOrder->quote) }}" class="text-brand-600 hover:underline decoration-brand-600/50 underline-offset-4 transition-all">
                                    {{ $salesOrder->quote->quote_no }}
                                </a>
                            </p>
                        </div>
                    @endif
                </div>
            </x-ui.card>



            @include('sales_orders.partials.post_stock')
            
            @include('sales_orders.partials.shipments')

            @include('sales_orders.partials.returns')

            @include('sales_orders.partials.invoices')

            @include('sales_orders.partials.items')

            <x-ui.card>
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                    <h3 class="font-semibold text-slate-900">{{ __('Koşullar ve Notlar') }}</h3>
                </div>
                <div class="grid gap-6 text-sm text-slate-600 md:grid-cols-2">
                    <div class="space-y-1">
                        <p class="font-medium text-slate-900">{{ __('Ödeme Şartları') }}</p>
                        <p class="leading-relaxed">{{ $salesOrder->payment_terms ?: '-' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="font-medium text-slate-900">{{ __('Garanti') }}</p>
                        <p class="leading-relaxed">{{ $salesOrder->warranty_text ?: '-' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="font-medium text-slate-900">{{ __('Hariçler') }}</p>
                        <p class="leading-relaxed">{{ $salesOrder->exclusions ?: '-' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="font-medium text-slate-900">{{ __('Notlar') }}</p>
                        <p class="leading-relaxed">{{ $salesOrder->notes ?: '-' }}</p>
                    </div>
                    <div class="md:col-span-2 space-y-1">
                        <p class="font-medium text-slate-900">{{ __('Kur Notu') }}</p>
                        <p class="leading-relaxed">{{ $salesOrder->fx_note ?: '-' }}</p>
                    </div>
                </div>
            </x-ui.card>
        @endslot

        @slot('right')
             @include('partials.operation-flow', ['flow' => $operationFlow])

             @include('partials.document-hub', [
                'context' => 'sales_order',
                'quote' => $quote ?? null,
                'salesOrder' => $salesOrder,
                'contract' => $contract ?? null,
                'workOrder' => $workOrder ?? null,
                'timeline' => $timeline,
                'showTimeline' => false,
            ])

            <x-partials.follow-up-card :context="$salesOrder" />

             <x-ui.card class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-card !p-0">
                <div class="border-b border-slate-100 bg-white px-4 py-3">
                    <h3 class="font-semibold text-slate-900">{{ __('Aktivite') }}</h3>
                </div>
                <div class="bg-slate-50/40 p-4">
                    <x-activity-timeline :logs="$timeline" :show-subject="true" />
                </div>
            </x-ui.card>
        @endslot
    @endcomponent
</x-app-layout>
