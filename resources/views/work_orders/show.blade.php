<x-app-layout>
    @component('partials.show-layout')
        @slot('header')
            @component('partials.page-header', [
                'title' => __('İş Emri') . ' ' . ($workOrder->title ?? '#' . $workOrder->id),
                'subtitle' => ($workOrder->customer?->name ?? '-') . ' • ' . ($workOrder->vessel?->name ?? '-') . ' • ' . ($workOrder->planned_start_at ? $workOrder->planned_start_at->format('d.m.Y') : '-') . ' - ' . ($workOrder->planned_end_at ? $workOrder->planned_end_at->format('d.m.Y') : '-'),
            ])
                @slot('status')
                    @php
                        $statusVariants = [
                            'draft' => 'neutral',
                            'planned' => 'info',
                            'started' => 'info',
                            'in_progress' => 'info',
                            'on_hold' => 'neutral',
                            'completed' => 'success',
                            'delivered' => 'success',
                            'cancelled' => 'danger',
                        ];
                    @endphp
                     <x-ui.badge :variant="$statusVariants[$workOrder->status] ?? 'neutral'">{{ $workOrder->status_label }}</x-ui.badge>
                @endslot

                @slot('actions')
                    <x-ui.button href="{{ route('work-orders.edit', $workOrder) }}" variant="secondary" size="sm">
                        {{ __('Düzenle') }}
                    </x-ui.button>
                     <x-ui.button href="{{ route('work-orders.print', $workOrder) }}" variant="secondary" size="sm">
                        <x-icon.printer class="h-4 w-4 mr-1 text-slate-500" />
                        {{ __('Teslim Raporu') }}
                    </x-ui.button>
                    
                    <x-ui.dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <x-ui.button type="button" variant="secondary" size="sm" class="inline-flex items-center gap-2">
                                <x-icon.dots class="h-4 w-4" />
                            </x-ui.button>
                        </x-slot>
                        <x-slot name="content">
                            <form id="work-order-delete-{{ $workOrder->id }}" method="POST" action="{{ route('work-orders.destroy', $workOrder) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                    class="flex w-full items-center gap-2 px-3 py-2 text-sm text-rose-600 transition hover:bg-rose-50"
                                    data-confirm
                                    data-confirm-title="{{ __('Emin misiniz?') }}"
                                    data-confirm-message="{{ __('İş emri silinecek. Bu işlem geri alınamaz.') }}"
                                    data-confirm-text="{{ __('Sil') }}"
                                    data-confirm-cancel-text="{{ __('Vazgeç') }}"
                                    data-confirm-submit="work-order-delete-{{ $workOrder->id }}">
                                    <x-icon.trash class="h-4 w-4" />
                                    {{ __('Sil') }}
                                </button>
                            </form>
                        </x-slot>
                    </x-ui.dropdown>

                    <x-ui.button href="{{ route('work-orders.index') }}" variant="secondary" size="sm">
                        {{ __('Listeye Dön') }}
                    </x-ui.button>
                @endslot
            @endcomponent
        @endslot

        @slot('left')
             <x-ui.card class="rounded-2xl border border-slate-200 bg-white shadow-card !p-5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                    <h3 class="font-semibold text-slate-900">{{ __('Genel Bilgiler') }}</h3>
                </div>
                <dl class="grid gap-6 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-bold tracking-wider text-slate-500 mb-1">{{ __('Müşteri') }}</dt>
                        <dd class="mt-1 font-medium text-slate-900 border-b border-slate-100 pb-1">
                            @if ($workOrder->customer)
                                <a href="{{ route('customers.show', $workOrder->customer) }}" class="text-brand-600 hover:text-brand-700 hover:underline decoration-brand-600/50 underline-offset-4 transition-all">
                                    {{ $workOrder->customer->name }}
                                </a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold tracking-wider text-slate-500 mb-1">{{ __('Tekne') }}</dt>
                        <dd class="mt-1 font-medium text-slate-900 border-b border-slate-100 pb-1">
                            @if ($workOrder->vessel)
                                <a href="{{ route('vessels.show', $workOrder->vessel) }}" class="text-brand-600 hover:text-brand-700 hover:underline decoration-brand-600/50 underline-offset-4 transition-all">
                                    {{ $workOrder->vessel->name }}
                                </a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold tracking-wider text-slate-500 mb-1">{{ __('Planlanan Başlangıç') }}</dt>
                        <dd class="mt-1 font-medium text-slate-900">{{ $workOrder->planned_start_at ? $workOrder->planned_start_at->format('d.m.Y') : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold tracking-wider text-slate-500 mb-1">{{ __('Planlanan Bitiş') }}</dt>
                        <dd class="mt-1 font-medium text-slate-900">{{ $workOrder->planned_end_at ? $workOrder->planned_end_at->format('d.m.Y') : '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-bold tracking-wider text-slate-500 mb-1">{{ __('Açıklama') }}</dt>
                        <dd class="mt-1 font-medium text-slate-900 bg-slate-50 rounded-xl p-3 text-sm leading-relaxed border border-slate-200">
                            {{ $workOrder->description ?: '—' }}
                        </dd>
                    </div>
                </dl>
            </x-ui.card>
            
            @include('work_orders.partials.items')
            @include('work_orders.partials.photos')
            @include('work_orders.partials.updates')
            @include('work_orders.partials.post_stock')
        @endslot

        @slot('right')
            {{-- Delivery Card --}}
            <x-ui.card class="rounded-2xl border border-slate-200 bg-white shadow-card !p-5 mb-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                    <h3 class="font-semibold text-slate-900">{{ __('Teslimat') }}</h3>
                </div>

                @if($workOrder->status === 'delivered')
                    <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 text-sm">
                        <div class="flex items-center gap-2 text-emerald-800 font-semibold mb-2">
                             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                             {{ __('Teslim Edildi') }}
                        </div>
                        <dl class="space-y-2 mt-3">
                            <div>
                                <dt class="text-xs text-emerald-600/80">{{ __('Teslim Alan') }}</dt>
                                <dd class="font-medium text-emerald-900">{{ $workOrder->delivered_to_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-emerald-600/80">{{ __('Teslim Tarihi') }}</dt>
                                <dd class="font-medium text-emerald-900">{{ $workOrder->delivered_at?->format('d.m.Y H:i') }}</dd>
                            </div>
                            @if($workOrder->delivery_notes)
                            <div class="pt-2 border-t border-emerald-200/50 mt-2">
                                <dt class="text-xs text-emerald-600/80">{{ __('Notlar') }}</dt>
                                <dd class="text-emerald-900 italic">"{{ $workOrder->delivery_notes }}"</dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                @else
                    <form action="{{ route('work-orders.deliver', $workOrder) }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                             <x-ui.input-label for="delivered_to_name" value="{{ __('Teslim Alan Kişi') }}" />
                             <x-ui.text-input id="delivered_to_name" name="delivered_to_name" class="mt-1 w-full" required placeholder="Müşteri Ad Soyad" />
                        </div>

                        <div>
                             <x-ui.input-label for="delivered_at" value="{{ __('Teslim Tarihi') }}" />
                             <x-ui.text-input type="datetime-local" id="delivered_at" name="delivered_at" class="mt-1 w-full" required value="{{ now()->format('Y-m-d\TH:i') }}" />
                        </div>
                        
                        <div>
                             <x-ui.input-label for="delivery_notes" value="{{ __('Notlar (Opsiyonel)') }}" />
                             <x-ui.text-input id="delivery_notes" name="delivery_notes" class="mt-1 w-full" placeholder="Teslimat notları..." />
                        </div>

                        <x-ui.button type="submit" variant="primary" class="w-full justify-center">
                            {{ __('Teslim Et') }}
                        </x-ui.button>
                    </form>
                @endif
            </x-ui.card>

            @include('work_orders.partials.progress', ['workOrder' => $workOrder])

            @include('partials.operation-flow', ['flow' => $operationFlow])

            @include('partials.document-hub', [
                'context' => 'work_order',
                'quote' => $quote ?? null,
                'salesOrder' => $salesOrder ?? null,
                'contract' => $contract ?? null,
                'workOrder' => $workOrder,
                'timeline' => $timeline,
                'showTimeline' => false,
            ])

            <x-partials.follow-up-card :context="$workOrder" />

            <x-ui.card class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-card !p-0">
                <div class="border-b border-slate-100 bg-white px-4 py-3">
                    <h3 class="font-semibold text-slate-900">{{ __('Sistem Günlüğü') }}</h3>
                </div>
                <div class="bg-slate-50/40 p-4">
                    <x-activity-timeline :logs="$timeline" :show-subject="true" />
                </div>
            </x-ui.card>
        @endslot
    @endcomponent
</x-app-layout>
