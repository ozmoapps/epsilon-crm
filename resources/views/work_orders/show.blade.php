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
                    <x-button href="{{ route('work-orders.edit', $workOrder) }}" variant="secondary" size="sm">
                        {{ __('Düzenle') }}
                    </x-button>
                    <x-button href="{{ route('work-orders.print', $workOrder) }}" variant="secondary" size="sm">
                        {{ __('Yazdır') }}
                    </x-button>
                    
                    <x-ui.dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <x-button type="button" variant="secondary" size="sm" class="inline-flex items-center gap-2">
                                {{ __('İşlemler') }}
                                <x-icon.dots class="h-4 w-4" />
                            </x-button>
                        </x-slot>
                        <x-slot name="content">
                            <form id="work-order-delete-{{ $workOrder->id }}" method="POST" action="{{ route('work-orders.destroy', $workOrder) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-rose-600 transition hover:bg-rose-50" onclick="return confirm('İş emri silinsin mi?')">
                                    <x-icon.trash class="h-4 w-4" />
                                    {{ __('Sil') }}
                                </button>
                            </form>
                        </x-slot>
                    </x-ui.dropdown>

                    <x-button href="{{ route('work-orders.index') }}" variant="secondary" size="sm">
                        {{ __('Listeye Dön') }}
                    </x-button>
                @endslot
            @endcomponent
        @endslot

        @slot('left')
             <x-card class="rounded-2xl border border-slate-200 bg-white shadow-sm !p-5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                    <h3 class="font-semibold text-slate-900">{{ __('Genel Bilgiler') }}</h3>
                </div>
                <dl class="grid gap-6 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-bold tracking-wider text-slate-500 uppercase mb-1">{{ __('Müşteri') }}</dt>
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
                        <dt class="text-xs font-bold tracking-wider text-slate-500 uppercase mb-1">{{ __('Tekne') }}</dt>
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
                        <dt class="text-xs font-bold tracking-wider text-slate-500 uppercase mb-1">{{ __('Planlanan Başlangıç') }}</dt>
                        <dd class="mt-1 font-medium text-slate-900">{{ $workOrder->planned_start_at ? $workOrder->planned_start_at->format('d.m.Y') : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold tracking-wider text-slate-500 uppercase mb-1">{{ __('Planlanan Bitiş') }}</dt>
                        <dd class="mt-1 font-medium text-slate-900">{{ $workOrder->planned_end_at ? $workOrder->planned_end_at->format('d.m.Y') : '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-bold tracking-wider text-slate-500 uppercase mb-1">{{ __('Açıklama') }}</dt>
                        <dd class="mt-1 font-medium text-slate-900 bg-slate-50 rounded-lg p-3 text-sm leading-relaxed border border-slate-200">
                            {{ $workOrder->description ?: '—' }}
                        </dd>
                    </div>
                </dl>
            </x-card>
        @endslot

        @slot('right')
            <div class="space-y-6">
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

                <x-card class="overflow-hidden border border-slate-200 rounded-2xl shadow-sm !p-0 bg-white">
                    <div class="px-5 py-4 border-b border-slate-100 bg-white">
                        <h3 class="font-semibold text-slate-900">{{ __('Aktivite') }}</h3>
                    </div>
                    <div class="p-5">
                        <x-activity-timeline :logs="$timeline" :show-subject="true" />
                    </div>
                </x-card>
            </div>
        @endslot
    @endcomponent
</x-app-layout>
