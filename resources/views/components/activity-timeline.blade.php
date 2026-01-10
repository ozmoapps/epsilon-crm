@props([
    'logs',
    'title' => null,
    'showSubject' => false,
])

@php
    $title = $title ?? __('Aktivite');
    $actionLabels = [
        'created' => 'Oluşturuldu',
        'updated' => 'Güncellendi',
        'status_changed' => 'Durum değişti',
        'converted_to_sales_order' => 'Siparişe dönüştürüldü',
        'created_from_quote' => 'Tekliften oluşturuldu',
        'converted_to_contract' => 'Sözleşmeye dönüştürüldü',
        'created_from_sales_order' => 'Siparişten oluşturuldu',
        'delete_blocked' => 'Silme engellendi',
    ];
    $actionIcons = [
        'created' => 'plus',
        'updated' => 'pencil',
        'status_changed' => 'arrow-right',
        'converted_to_sales_order' => 'arrow-right',
        'created_from_quote' => 'plus',
        'converted_to_contract' => 'arrow-right',
        'created_from_sales_order' => 'plus',
        'delete_blocked' => 'x',
    ];
    $statusLabelsByType = [
        \App\Models\Quote::class => \App\Models\Quote::statusOptions(),
        \App\Models\SalesOrder::class => \App\Models\SalesOrder::statusOptions(),
        \App\Models\Contract::class => \App\Models\Contract::statusOptions(),
    ];
    $subjectLabels = [
        \App\Models\Quote::class => [
            'label' => 'Teklif',
            'field' => 'quote_no',
        ],
        \App\Models\SalesOrder::class => [
            'label' => 'Satış Siparişi',
            'field' => 'order_no',
        ],
        \App\Models\Contract::class => [
            'label' => 'Sözleşme',
            'field' => 'contract_no',
        ],
        \App\Models\WorkOrder::class => [
            'label' => 'İş Emri',
            'field' => 'title',
        ],
    ];
    $formatStatus = function ($log, $status) use ($statusLabelsByType) {
        return $statusLabelsByType[$log->subject_type][$status] ?? $status;
    };
@endphp

    <div class="space-y-0">
        @php
            $limitedLogs = $logs->take(8);
            $hasMore = $logs->count() > 8;
        @endphp

        @forelse ($limitedLogs as $log)
            @php
                $icon = $actionIcons[$log->action] ?? 'info';
                $label = $actionLabels[$log->action] ?? $log->action;
                $meta = $log->meta ?? [];
                $actorName = $log->actor?->name ?? __('Sistem');
                $subject = $log->subject;
                $subjectMeta = $subjectLabels[$log->subject_type] ?? null;
                $subjectLabel = $subjectMeta['label'] ?? __('Kayıt');
                $subjectField = $subjectMeta['field'] ?? null;
                $subjectIdentifier = $subjectField && $subject ? data_get($subject, $subjectField) : null;
                $fallbackIdentifier = $subject?->title ?? $subject?->name ?? $subject?->id;
                $subjectIdentifier = $subjectIdentifier ?: $fallbackIdentifier;
            @endphp
            <div class="flex gap-3 relative pb-6 last:pb-0">
                 <!-- Line -->
                <div class="absolute left-[1.125rem] top-8 bottom-0 w-px bg-slate-100 last:hidden"></div>
                
                <div class="relative z-10 mt-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white border border-slate-200 text-slate-500 ring-4 ring-white">
                    <x-dynamic-component :component="'icon.' . $icon" class="h-4 w-4" />
                </div>
                
                <div class="flex-1 min-w-0 pt-1.5">
                    <div class="text-sm text-slate-900 leading-snug">
                        <span class="font-medium">{{ $label }}</span>
                        @if ($log->action === 'status_changed')
                           <span class="mx-1 text-slate-300">·</span>
                            <x-ui.badge variant="neutral" class="scale-90 origin-left border border-slate-200 !px-1.5 !py-0.5">
                                {{ $formatStatus($log, $meta['from'] ?? '-') }}
                            </x-ui.badge>
                            <span class="text-slate-300 mx-1">→</span>
                            <x-ui.badge variant="neutral" class="scale-90 origin-left border border-slate-200 !px-1.5 !py-0.5">
                                {{ $formatStatus($log, $meta['to'] ?? '-') }}
                            </x-ui.badge>
                        @endif
                    </div>
                    
                    <div class="mt-1 text-xs text-slate-500 flex items-center gap-2">
                        <span class="font-medium text-slate-600">{{ $actorName }}</span>
                        <span class="text-slate-300">•</span>
                        <span>{{ $log->created_at?->format('d.m.Y H:i') ?? '-' }}</span>
                    </div>

                    @if ($showSubject)
                        <div class="mt-1.5 text-xs font-medium text-slate-600 bg-slate-50 px-2 py-1 rounded inline-block border border-slate-100">
                            {{ $subjectLabel }}@if ($subjectIdentifier): {{ $subjectIdentifier }}@endif
                        </div>
                    @endif
                    
                    @if ($log->action === 'converted_to_sales_order' && ! empty($meta['sales_order_no']))
                         <div class="mt-2 text-xs text-slate-600 bg-slate-50 p-2 rounded border border-slate-100">
                            {{ __('Sipariş No') }}: <span class="font-medium font-mono">{{ $meta['sales_order_no'] }}</span>
                        </div>
                    @elseif ($log->action === 'created_from_quote' && ! empty($meta['quote_no']))
                        <div class="mt-2 text-xs text-slate-600 bg-slate-50 p-2 rounded border border-slate-100">
                            {{ __('Teklif No') }}: <span class="font-medium font-mono">{{ $meta['quote_no'] }}</span>
                        </div>
                    @elseif ($log->action === 'converted_to_contract' && ! empty($meta['contract_no']))
                         <div class="mt-2 text-xs text-slate-600 bg-slate-50 p-2 rounded border border-slate-100">
                            {{ __('Sözleşme No') }}: <span class="font-medium font-mono">{{ $meta['contract_no'] }}</span>
                        </div>
                    @elseif ($log->action === 'created_from_sales_order' && ! empty($meta['sales_order_no']))
                         <div class="mt-2 text-xs text-slate-600 bg-slate-50 p-2 rounded border border-slate-100">
                            {{ __('Sipariş No') }}: <span class="font-medium font-mono">{{ $meta['sales_order_no'] }}</span>
                        </div>
                    @elseif ($log->action === 'updated' && ! empty($meta['fields']))
                        <div class="mt-2 text-xs text-slate-500 bg-slate-50 p-2 rounded border border-slate-100">
                            {{ __('Güncellenenler') }}: <span class="font-medium text-slate-700">{{ implode(', ', $meta['fields']) }}</span>
                        </div>
                    @elseif ($log->action === 'delete_blocked')
                         <div class="mt-2 text-xs text-rose-600 bg-rose-50 p-2 rounded border border-rose-100">
                            {{ __('Silme isteği kilit nedeniyle engellendi.') }}
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center py-8 text-center">
                <div class="rounded-full bg-slate-50 p-3 mb-3">
                    <x-icon.clock class="h-6 w-6 text-slate-300" />
                </div>
                <p class="text-sm font-medium text-slate-900">{{ __('Henüz aktivite yok') }}</p>
            </div>
        @endforelse
        
        @if($hasMore)
            <div class="pt-4 text-center border-t border-slate-50 mt-2">
                <a href="#" class="text-xs font-semibold text-brand-600 hover:text-brand-700 hover:underline">
                    {{ __('Tüm Aktiviteleri Görüntüle') }}
                </a>
            </div>
        @endif
    </div>

