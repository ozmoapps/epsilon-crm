@props(['logs'])

@php
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
    $formatStatus = function ($log, $status) use ($statusLabelsByType) {
        return $statusLabelsByType[$log->subject_type][$status] ?? $status;
    };
@endphp

<x-ui.card>
    <x-slot name="header">{{ __('Aktivite') }}</x-slot>

    <div class="space-y-4">
        @forelse ($logs as $log)
            @php
                $icon = $actionIcons[$log->action] ?? 'info';
                $label = $actionLabels[$log->action] ?? $log->action;
                $meta = $log->meta ?? [];
                $actorName = $log->actor?->name ?? __('Sistem');
            @endphp
            <div class="flex gap-3 rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
                <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                    <x-dynamic-component :component="'icon.' . $icon" class="h-4 w-4" />
                </div>
                <div class="flex-1 space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-sm font-semibold text-slate-900">{{ $label }}</p>
                        @if ($log->action === 'status_changed')
                            <x-ui.badge variant="neutral">
                                {{ $formatStatus($log, $meta['from'] ?? '-') }}
                            </x-ui.badge>
                            <span class="text-xs text-slate-400">→</span>
                            <x-ui.badge variant="neutral">
                                {{ $formatStatus($log, $meta['to'] ?? '-') }}
                            </x-ui.badge>
                        @endif
                    </div>
                    <p class="text-xs text-slate-500">
                        {{ $actorName }} · {{ $log->created_at?->format('d.m.Y H:i') ?? '-' }}
                    </p>
                    @if ($log->action === 'converted_to_sales_order' && ! empty($meta['sales_order_no']))
                        <p class="text-xs text-slate-500">{{ __('Sipariş No') }}: {{ $meta['sales_order_no'] }}</p>
                    @elseif ($log->action === 'created_from_quote' && ! empty($meta['quote_no']))
                        <p class="text-xs text-slate-500">{{ __('Teklif No') }}: {{ $meta['quote_no'] }}</p>
                    @elseif ($log->action === 'converted_to_contract' && ! empty($meta['contract_no']))
                        <p class="text-xs text-slate-500">{{ __('Sözleşme No') }}: {{ $meta['contract_no'] }}</p>
                    @elseif ($log->action === 'created_from_sales_order' && ! empty($meta['sales_order_no']))
                        <p class="text-xs text-slate-500">{{ __('Sipariş No') }}: {{ $meta['sales_order_no'] }}</p>
                    @elseif ($log->action === 'updated' && ! empty($meta['fields']))
                        <p class="text-xs text-slate-500">
                            {{ __('Güncellenen alanlar') }}:
                            {{ implode(', ', $meta['fields']) }}
                        </p>
                    @elseif ($log->action === 'delete_blocked')
                        <p class="text-xs text-slate-500">{{ __('Silme isteği kilit nedeniyle engellendi.') }}</p>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500">{{ __('Henüz aktivite kaydı bulunmuyor.') }}</p>
        @endforelse
    </div>
</x-ui.card>
