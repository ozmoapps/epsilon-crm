<x-app-layout>
    @component('partials.show-layout')
        @slot('header')
            @component('partials.page-header', [
                'title' => __('İş Emri') . ' ' . ($workOrder->title ?? '#' . $workOrder->id),
                'subtitle' => ($workOrder->customer?->name ?? '-') . ' • ' . ($workOrder->vessel?->name ?? '-') . ' • ' . ($workOrder->planned_start_at ? $workOrder->planned_start_at->format('d.m.Y') : '-') . ' - ' . ($workOrder->planned_end_at ? $workOrder->planned_end_at->format('d.m.Y') : '-'),
            ])
                @slot('status')
                     <x-badge variant="info">{{ $workOrder->status_label }}</x-badge>
                @endslot

                @slot('actions')
                    <x-ui.button href="{{ route('work-orders.edit', $workOrder) }}" variant="secondary" size="sm">
                        {{ __('Düzenle') }}
                    </x-ui.button>
                    <x-ui.button href="{{ route('work-orders.print', $workOrder) }}" variant="secondary" size="sm">
                        {{ __('Yazdır') }}
                    </x-ui.button>
                    
                    <x-ui.dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <x-ui.button type="button" variant="secondary" size="sm" class="inline-flex items-center gap-2">
                                {{ __('İşlemler') }}
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
                                <a href="{{ route('admin.company-profiles.show', $workOrder->customer_id) }}" class="text-brand-600 hover:text-brand-700 hover:underline decoration-brand-600/50 underline-offset-4 transition-all">
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
                </dl>
            </x-ui.card>
            
            @include('work_orders.partials.items')
            @include('work_orders.partials.post_stock')
        @endslot

        @slot('right')
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
                    <h3 class="font-semibold text-slate-900">{{ __('Aktivite') }}</h3>
                </div>
                <div class="bg-slate-50/40 p-4">
                    <x-activity-timeline :logs="$timeline" :show-subject="true" />
                </div>
            </x-ui.card>
        @endslot
    @endcomponent
</x-app-layout>
