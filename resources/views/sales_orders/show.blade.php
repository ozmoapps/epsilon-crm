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
                        <x-button href="{{ route('contracts.show', $salesOrder->contract) }}" variant="secondary" size="sm">
                            {{ __('Sözleşmeyi Görüntüle') }}
                        </x-button>
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
                                <x-button type="button" size="sm">
                                    {{ __('Sözleşme Oluştur') }}
                                </x-button>
                            </x-slot>
                        </x-ui.confirm>
                    @endif

                    <x-ui.dropdown align="right" width="w-60">
                        <x-slot name="trigger">
                             <button class="inline-flex items-center px-3 py-2 border border-slate-200 shadow-sm text-sm leading-4 font-medium rounded-lg text-slate-700 bg-white hover:bg-slate-50 focus:outline-none transition ease-in-out duration-150 gap-2">
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
                                <form method="POST" action="{{ route('sales-orders.cancel', $salesOrder) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-rose-600 transition hover:bg-rose-50" onclick="return confirm('Satış siparişi iptal edilsin mi?')">
                                        <x-icon.x class="h-4 w-4" />
                                        {{ __('İptal') }}
                                    </button>
                                </form>
                            @endif
                            
                            <div class="my-1 h-px bg-slate-100"></div>
    
                            @if ($isLocked)
                                 <button type="button" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50 cursor-not-allowed opacity-60" disabled>
                                    <x-icon.pencil class="h-4 w-4 text-indigo-600" />
                                    {{ __('Düzenle') }}
                                    <span class="ml-auto text-[10px] bg-gray-100 px-1 rounded">{{ __('Kilitli') }}</span>
                                </button>
                            @else
                                 <a href="{{ route('sales-orders.edit', $salesOrder) }}" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50">
                                    <x-icon.pencil class="h-4 w-4 text-indigo-600" />
                                    {{ __('Düzenle') }}
                                </a>
                            @endif
    
                             @if ($isLocked)
                                <button type="button" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-rose-600 transition hover:bg-rose-50 cursor-not-allowed opacity-60" disabled>
                                    <x-icon.trash class="h-4 w-4" />
                                    {{ __('Sil') }}
                                    <span class="ml-auto text-[10px] bg-gray-100 px-1 rounded">{{ __('Kilitli') }}</span>
                                </button>
                            @else
                                <form method="POST" action="{{ route('sales-orders.destroy', $salesOrder) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-rose-600 transition hover:bg-rose-50" onclick="return confirm('Silme işlemini onayla')">
                                        <x-icon.trash class="h-4 w-4" />
                                        {{ __('Sil') }}
                                    </button>
                                </form>
                            @endif
                        </x-slot>
                    </x-ui.dropdown>
    
                    <x-button href="{{ route('sales-orders.index') }}" variant="secondary" size="sm">
                        {{ __('Listeye Dön') }}
                    </x-button>
                @endslot
            @endcomponent
        @endslot

        @slot('left')
            {{-- General Info --}}
            <x-card class="rounded-2xl border border-slate-200 bg-white shadow-sm !p-5">
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
            </x-card>

            <x-card>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 pb-4 mb-6">
                     <h3 class="font-semibold text-slate-900">{{ __('Kalemler') }}</h3>
                     <span class="text-xs font-medium text-slate-500 bg-slate-100 px-2 py-1 rounded">{{ count($salesOrder->items) }} {{ __('kalem') }}</span>
                </div>

                 <div class="space-y-6">
                    <div class="hidden rounded-lg border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-semibold tracking-wide text-slate-500 lg:grid lg:grid-cols-12 lg:items-center">
                        <div class="lg:col-span-4">{{ __('Hizmet/Ürün') }}</div>
                        <div class="lg:col-span-1 text-right">{{ __('Miktar') }}</div>
                        <div class="lg:col-span-1 border-l border-slate-200 pl-2">{{ __('Birim') }}</div>
                        <div class="lg:col-span-2 text-right">{{ __('Br. Fiyat') }}</div>
                        <div class="lg:col-span-1 text-right">{{ __('İndirim') }}</div>
                        <div class="lg:col-span-1 text-right">{{ __('KDV') }}</div>
                        <div class="lg:col-span-1 text-right">{{ __('Toplam') }}</div>
                        <div class="lg:col-span-1 text-right">{{ __('') }}</div>
                    </div>

                    <div class="space-y-6">
                         @forelse ($itemsBySection as $section => $items)
                            <div class="space-y-3">
                                <div class="flex items-center justify-between border-b border-slate-100 pb-1">
                                    <h4 class="text-xs font-bold tracking-wider text-slate-900">{{ $section }}</h4>
                                </div>
                                <div class="space-y-3">
                                    @foreach ($items as $item)
                                        <div class="flex flex-col gap-2 rounded-lg border border-slate-100 p-3 text-sm sm:flex-row sm:items-center sm:justify-between hover:bg-slate-50 transition-colors">
                                            <div class="lg:col-span-4 flex-1">
                                                <div class="flex items-center gap-2">
                                                    @if ($item->is_optional)
                                                        <span class="rounded bg-yellow-100 px-1.5 py-0.5 text-[10px] font-medium text-yellow-800">{{ __('Opsiyon') }}</span>
                                                    @endif
                                                    <span class="font-medium text-slate-900">{{ $item->description }}</span>
                                                </div>
                                                <div class="text-xs text-slate-500">
                                                    {{ $item->item_type_label }}
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-4 text-slate-700 justify-end flex-wrap sm:flex-nowrap">
                                                <div class="w-20 text-right">{{ $item->qty }} {{ $item->unit }}</div>
                                                <div class="w-24 text-right">{{ $formatMoney($item->unit_price) }} {{ $currencySymbol }}</div>
                                                <div class="w-24 text-right font-semibold">{{ $formatMoney($item->total_price) }} {{ $currencySymbol }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                             <div class="text-sm text-slate-500 text-center py-8 bg-slate-50 rounded-xl border border-dashed border-slate-300">
                                <p>{{ __('Henüz kalem eklenmedi.') }}</p>
                             </div>
                        @endforelse
                    </div>

                    {{-- Totals --}}
                    <div class="grid gap-8 md:grid-cols-12 md:items-start mt-8">
                         <div class="md:col-span-7"></div>
                        <div class="md:col-span-5 w-full">
                            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                                <h4 class="text-base font-semibold text-slate-900 mb-4">{{ __('Özet') }}</h4>
                                <dl class="space-y-3 text-sm text-slate-600">
                                    <div class="flex items-center justify-between">
                                        <dt>{{ __('Ara Toplam') }}</dt>
                                        <dd class="font-medium text-slate-900">{{ $currencySymbol }} {{ $formatMoney($salesOrder->subtotal) }}</dd>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <dt>{{ __('İndirim') }}</dt>
                                        <dd class="font-medium text-red-600">- {{ $currencySymbol }} {{ $formatMoney($salesOrder->discount_total) }}</dd>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <dt>{{ __('Toplam KDV') }}</dt>
                                        <dd class="font-medium text-slate-900">{{ $currencySymbol }} {{ $formatMoney($salesOrder->vat_total) }}</dd>
                                    </div>
                                    <div class="border-t border-slate-200 pt-3 mt-3">
                                        <div class="flex items-center justify-between text-base">
                                            <dt class="font-bold text-slate-900">{{ __('Genel Toplam') }}</dt>
                                            <dd class="font-bold text-brand-600 text-lg">{{ $currencySymbol }} {{ $formatMoney($salesOrder->grand_total) }}</dd>
                                        </div>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </x-card>

            <x-card>
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
            </x-card>
        @endslot

        @slot('right')
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

             <x-card class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm !p-0">
                <div class="border-b border-slate-100 bg-white px-4 py-3">
                    <h3 class="font-semibold text-slate-900">{{ __('Aktivite') }}</h3>
                </div>
                <div class="bg-slate-50/40 p-4">
                    <x-activity-timeline :logs="$timeline" :show-subject="true" />
                </div>
            </x-card>
        @endslot
    @endcomponent
</x-app-layout>
