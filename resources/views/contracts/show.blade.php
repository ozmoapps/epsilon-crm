<x-app-layout>
    @php
        $canSend = $contract->status === 'draft';
        $canSign = $contract->status === 'sent';
        $canCancel = $contract->status !== 'cancelled';
        $isLocked = $contract->isLocked();

        $statusVariants = [
            'draft' => 'draft',
            'issued' => 'neutral',
            'sent' => 'sent',
            'signed' => 'signed',
            'superseded' => 'neutral',
            'cancelled' => 'cancelled',
        ];
        $deliveryStatusVariants = [
            'prepared' => 'draft',
            'sent' => 'sent',
            'failed' => 'danger',
        ];
        $deliveryStatusLabels = [
            'prepared' => 'Hazır',
            'sent' => 'Gönderildi',
            'failed' => 'Başarısız',
        ];
        $deliveryChannelLabels = [
            'email' => 'E-posta',
            'whatsapp' => 'WhatsApp',
            'manual' => 'Manuel',
        ];
        $attachmentTypeLabels = [
            'signed_pdf' => 'İmzalı PDF',
            'annex' => 'Ek',
            'id' => 'Kimlik',
            'other' => 'Diğer',
        ];
        $currencySymbols = config('quotes.currency_symbols', []);
        $currencySymbol = $currencySymbols[$contract->currency] ?? $contract->currency;
        $formatMoney = fn ($value) => number_format((float) $value, 2, ',', '.');
        $formatBytes = function ($bytes) {
            $bytes = (int) $bytes;
            if ($bytes < 1024) {
                return $bytes . ' B';
            }
            if ($bytes < 1024 * 1024) {
                return number_format($bytes / 1024, 1, ',', '.') . ' KB';
            }

            return number_format($bytes / (1024 * 1024), 2, ',', '.') . ' MB';
        };
        $customerName = $contract->customer_name ?: __('Müşteri');
        $deliveryTemplates = [
            'tr_short' => "Merhaba {$customerName},\n{$contract->contract_no} ({$contract->revision_label}) numaralı sözleşmeyi sizinle paylaşıyorum.\nİyi çalışmalar.",
            'tr_long' => "Merhaba {$customerName},\n{$contract->contract_no} ({$contract->revision_label}) numaralı sözleşme ve ilgili ekler hazırlandı. İnceleyip onayınıza sunuyorum.\nSorunuz olursa memnuniyetle destek olurum.\nİyi çalışmalar.",
            'en_short' => "Hello {$customerName},\nSharing contract {$contract->contract_no} ({$contract->revision_label}).\nBest regards.",
            'en_long' => "Hello {$customerName},\nThe contract {$contract->contract_no} ({$contract->revision_label}) and related attachments are prepared. Please review and share your approval.\nLet me know if you have any questions.\nBest regards.",
        ];
        $defaultTemplateKey = $contract->locale === 'en' ? 'en_short' : 'tr_short';
        $deliveriesSorted = $contract->deliveries->sortByDesc('created_at')->values();
        $lastDelivery = $deliveriesSorted->first();
        $maxAttachmentSizeKb = config('contracts.attachments.max_size_kb', 10240);
        $allowedAttachmentMimes = config('contracts.attachments.mimes', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);
    @endphp

    @component('partials.show-layout')
        @slot('header')
            @component('partials.page-header', [
                'title' => __('Sözleşme') . ' ' . $contract->contract_no,
                'subtitle' => ($contract->customer?->name ?? '-') . ' • ' . ($contract->vessel?->name ?? '-') . ' • ' . ($contract->is_signed && $contract->signed_at ? $contract->signed_at->format('d.m.Y') : ($contract->issued_at ? $contract->issued_at->format('d.m.Y') : '-')),
            ])
                 @slot('status')
                     <x-badge :status="$contract->status">{{ $contract->status_label }}</x-badge>
                @endslot
                
                @slot('actions')
                    <x-button href="{{ route('contracts.print', $contract) }}" variant="secondary" size="sm">
                        {{ __('Yazdır') }}
                    </x-button>

                    <x-button href="#delivery-pack" variant="secondary" size="sm">
                        {{ __('Gönderim Paketi') }}
                    </x-button>

                    @if ($contract->isEditable())
                        <x-button href="{{ route('contracts.edit', $contract) }}" variant="secondary" size="sm">
                            {{ __('Düzenle') }}
                        </x-button>
                    @endif
                    
                    <x-ui.dropdown align="right" width="w-60">
                        <x-slot name="trigger">
                            <x-button type="button" variant="secondary" size="sm" class="inline-flex items-center gap-2">
                                {{ __('İşlemler') }}
                                <x-icon.dots class="h-4 w-4" />
                            </x-button>
                        </x-slot>
                        <x-slot name="content">
                             @if ($contract->canCreateRevision())
                                <form method="POST" action="{{ route('contracts.revise', $contract) }}">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50">
                                        <x-icon.plus class="h-4 w-4" />
                                        {{ __('Revizyon Oluştur') }}
                                    </button>
                                </form>
                            @endif
    
                            <div class="my-1 h-px bg-slate-100"></div>
    
                            @if ($canSend)
                                <form method="POST" action="{{ route('contracts.mark_sent', $contract) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-blue-600 transition hover:bg-blue-50">
                                        <x-icon.arrow-right class="h-4 w-4" />
                                        {{ __('Gönderildi') }}
                                    </button>
                                </form>
                            @elseif ($canSign)
                                <form method="POST" action="{{ route('contracts.mark_signed', $contract) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-emerald-600 transition hover:bg-emerald-50">
                                        <x-icon.check class="h-4 w-4" />
                                        {{ __('İmzalandı') }}
                                    </button>
                                </form>
                            @endif
    
                            @if ($canCancel)
                                <form method="POST" action="{{ route('contracts.cancel', $contract) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-rose-600 transition hover:bg-rose-50" onclick="return confirm('Sözleşme iptal edilsin mi?')">
                                        <x-icon.x class="h-4 w-4" />
                                        {{ __('İptal') }}
                                    </button>
                                </form>
                            @endif
    
                            <div class="my-1 h-px bg-slate-100"></div>
    
                            @if ($isLocked)
                                <button type="button" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-rose-600 transition hover:bg-rose-50 cursor-not-allowed opacity-60" disabled>
                                    <x-icon.trash class="h-4 w-4" />
                                    {{ __('Sil') }}
                                    <span class="ml-auto text-[10px] bg-gray-100 px-1 rounded">{{ __('Kilitli') }}</span>
                                </button>
                            @else
                                <form id="contract-delete-{{ $contract->id }}" method="POST" action="{{ route('contracts.destroy', $contract) }}">
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
    
                    <x-button href="{{ route('contracts.index') }}" variant="secondary" size="sm">
                        {{ __('Listeye Dön') }}
                    </x-button>
                @endslot
            @endcomponent
        @endslot

        @slot('left')
            <x-card class="rounded-2xl border border-slate-200 bg-white shadow-sm !p-5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                    <h3 class="font-semibold text-slate-900">{{ __('Sözleşme Bilgileri') }}</h3>
                </div>
                 @include('contracts.partials._header', [
                    'contract' => $contract,
                    'currencySymbol' => $currencySymbol,
                    'formatMoney' => $formatMoney,
                    'statusVariants' => $statusVariants,
                ])
            </x-card>

            @include('contracts.partials._revisions', [
                'revisions' => $revisions,
                'statusVariants' => $statusVariants,
            ])

            @include('contracts.partials._delivery_pack', [
                'allowedAttachmentMimes' => $allowedAttachmentMimes,
                'attachmentTypeLabels' => $attachmentTypeLabels,
                'contract' => $contract,
                'defaultTemplateKey' => $defaultTemplateKey,
                'deliveriesSorted' => $deliveriesSorted,
                'deliveryChannelLabels' => $deliveryChannelLabels,
                'deliveryStatusLabels' => $deliveryStatusLabels,
                'deliveryStatusVariants' => $deliveryStatusVariants,
                'deliveryTemplates' => $deliveryTemplates,
                'formatBytes' => $formatBytes,
                'lastDelivery' => $lastDelivery,
                'maxAttachmentSizeKb' => $maxAttachmentSizeKb,
            ])

             <x-card class="rounded-2xl border border-slate-200 bg-white shadow-sm !p-0 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 bg-white flex items-center justify-between">
                    <h3 class="font-semibold text-slate-900">{{ __('Satış Siparişi Kalemleri') }}</h3>
                    <span class="text-xs font-medium text-slate-500 bg-slate-100 px-2 py-1 rounded">{{ count($contract->salesOrder->items) }} {{ __('kalem') }}</span>
                </div>
                <div class="p-5 space-y-4">
                    @forelse ($contract->salesOrder->items as $item)
                        <div class="flex flex-col gap-2 rounded-lg border border-slate-100 p-3 text-sm sm:flex-row sm:items-center sm:justify-between hover:bg-slate-50 transition-colors">
                            <div>
                                <div class="flex items-center gap-2">
                                     @if ($item->is_optional)
                                        <span class="rounded bg-yellow-100 px-1.5 py-0.5 text-[10px] font-medium text-yellow-800">{{ __('Opsiyon') }}</span>
                                    @endif
                                    <p class="font-medium text-slate-900">{{ $item->description }}</p>
                                </div>
                                <p class="text-xs text-slate-500">{{ $item->section ?: __('Genel') }}</p>
                            </div>
                            <div class="text-right text-slate-700">
                                <span class="font-medium">{{ $item->qty }} {{ $item->unit }}</span>
                                <span class="text-slate-300 mx-2">|</span>
                                <span>{{ $formatMoney($item->unit_price) }} {{ $currencySymbol }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500 italic">{{ __('Kalem bulunamadı.') }}</p>
                    @endforelse
                </div>
            </x-card>
        @endslot

        @slot('right')
            <div class="space-y-6">
                @include('partials.document-hub', [
                    'context' => 'contract',
                    'quote' => $quote ?? null,
                    'salesOrder' => $salesOrder ?? null,
                    'contract' => $contract,
                    'workOrder' => $workOrder ?? null,
                    'timeline' => $timeline,
                    'showTimeline' => false,
                ])

                <x-partials.follow-up-card :context="$contract" />

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
