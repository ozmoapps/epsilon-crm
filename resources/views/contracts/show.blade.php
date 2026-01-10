<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Sözleşme Detayı') }}" subtitle="{{ $contract->contract_no }}">
            <x-slot name="actions">
                @php
                    $canSend = $contract->status === 'draft';
                    $canSign = $contract->status === 'sent';
                    $canCancel = $contract->status !== 'cancelled';
                    $isLocked = $contract->isLocked();
                @endphp

                <x-button href="{{ route('contracts.pdf', $contract) }}" variant="secondary" size="sm">
                    {{ __('PDF İndir') }}
                </x-button>

                <x-button href="#delivery-pack" variant="secondary" size="sm">
                    {{ __('Gönderim Paketi') }}
                </x-button>

                @if ($contract->isEditable())
                    <x-button href="{{ route('contracts.edit', $contract) }}" variant="secondary" size="sm">
                        {{ __('Düzenle') }}
                    </x-button>
                @endif

                @if ($contract->canCreateRevision())
                    <form method="POST" action="{{ route('contracts.revise', $contract) }}">
                        @csrf
                        <x-button type="submit" variant="secondary" size="sm">
                            {{ __('Revizyon Oluştur') }}
                        </x-button>
                    </form>
                @endif

                @if ($canSend)
                    <form method="POST" action="{{ route('contracts.mark_sent', $contract) }}">
                        @csrf
                        @method('PATCH')
                        <x-button type="submit" size="sm">
                            {{ __('Gönderildi') }}
                        </x-button>
                    </form>
                @elseif ($canSign)
                    <form method="POST" action="{{ route('contracts.mark_signed', $contract) }}">
                        @csrf
                        @method('PATCH')
                        <x-button type="submit" size="sm">
                            {{ __('İmzalandı') }}
                        </x-button>
                    </form>
                @endif

                @if ($canCancel)
                    <form method="POST" action="{{ route('contracts.cancel', $contract) }}">
                        @csrf
                        @method('PATCH')
                        <x-button type="submit" variant="danger" size="sm" onclick="return confirm('Sözleşme iptal edilsin mi?')">
                            {{ __('İptal') }}
                        </x-button>
                    </form>
                @endif

                @if ($isLocked)
                    <div class="flex flex-col items-start gap-1">
                        <x-button
                            type="button"
                            variant="danger"
                            size="sm"
                            class="cursor-not-allowed opacity-60"
                            aria-disabled="true"
                            title="{{ __('İmzalı sözleşmeler silinemez.') }}"
                            @click.prevent
                        >
                            {{ __('Sil') }}
                        </x-button>
                        <x-ui.badge variant="neutral" class="text-[10px]">{{ __('Kilitli') }}</x-ui.badge>
                    </div>
                @else
                    <form id="contract-delete-{{ $contract->id }}" method="POST" action="{{ route('contracts.destroy', $contract) }}" class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                    <x-ui.confirm
                        title="{{ __('Silme işlemini onayla') }}"
                        message="{{ __('Bu işlem geri alınamaz. Devam etmek istiyor musunuz?') }}"
                        confirm-text="{{ __('Evet, sil') }}"
                        cancel-text="{{ __('Vazgeç') }}"
                        variant="danger"
                        form-id="contract-delete-{{ $contract->id }}"
                    >
                        <x-slot name="trigger">
                            <x-button type="button" variant="danger" size="sm">
                                {{ __('Sil') }}
                            </x-button>
                        </x-slot>
                    </x-ui.confirm>
                @endif

                <x-button href="{{ route('contracts.index') }}" variant="secondary" size="sm">
                    {{ __('Tüm sözleşmeler') }}
                </x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    @php
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

    <div
        class="space-y-6"
        x-data="{
            tab: 'message',
            isDesktop: false,
            downloadingPdf: false,
            downloadingZip: false,
        }"
        x-init="
            const media = window.matchMedia('(min-width: 768px)');
            isDesktop = media.matches;
            media.addEventListener('change', event => isDesktop = event.matches);
        "
    >
        @include('contracts.partials._header', [
            'contract' => $contract,
            'currencySymbol' => $currencySymbol,
            'formatMoney' => $formatMoney,
            'statusVariants' => $statusVariants,
        ])

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

        <x-card>
            <x-slot name="header">{{ __('Satış Siparişi Kalemleri') }}</x-slot>
            <div class="space-y-4">
                @forelse ($contract->salesOrder->items as $item)
                    <div class="flex flex-col gap-2 rounded-lg border border-slate-100 p-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-medium text-slate-900">{{ $item->description }}</p>
                            <p class="text-xs text-slate-500">{{ $item->section ?: __('Genel') }}</p>
                        </div>
                        <div class="text-right text-slate-700">
                            {{ $item->qty }} {{ $item->unit }} · {{ $formatMoney($item->unit_price) }} {{ $currencySymbol }}
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">{{ __('Kalem bulunamadı.') }}</p>
                @endforelse
            </div>
        </x-card>

        <x-activity-timeline :logs="$contract->activityLogs" />
    </div>
</x-app-layout>
