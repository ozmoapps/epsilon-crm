<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Sözleşme Detayı') }}" subtitle="{{ $contract->contract_no }}">
            <x-slot name="actions">
                @php
                    $canSend = $contract->status === 'draft';
                    $canSign = $contract->status === 'sent';
                    $canCancel = $contract->status !== 'cancelled';
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

                <x-button href="{{ route('contracts.index') }}" variant="secondary" size="sm">
                    {{ __('Tüm sözleşmeler') }}
                </x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    @php
        $statusVariants = [
            'draft' => 'draft',
            'sent' => 'sent',
            'signed' => 'signed',
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

    <div class="space-y-6">
        <x-card>
            <x-slot name="header">{{ __('Özet') }}</x-slot>
            <div class="grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <p class="text-xs tracking-wide text-gray-500">{{ __('Sözleşme No') }}</p>
                    <p class="text-base font-medium text-gray-900">{{ $contract->contract_no }}</p>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-gray-500">{{ __('Revizyon') }}</p>
                    <p class="text-base font-medium text-gray-900">{{ $contract->revision_label }}</p>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-gray-500">{{ __('Durum') }}</p>
                    <x-ui.badge :variant="$statusVariants[$contract->status] ?? 'neutral'">
                        {{ $contract->status_label }}
                    </x-ui.badge>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-gray-500">{{ __('Düzenleme Tarihi') }}</p>
                    <p class="text-base font-medium text-gray-900">{{ $contract->issued_at?->format('d.m.Y') }}</p>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-gray-500">{{ __('İmza Tarihi') }}</p>
                    <p class="text-base font-medium text-gray-900">{{ $contract->signed_at?->format('d.m.Y H:i') ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-gray-500">{{ __('Dil') }}</p>
                    <p class="text-base font-medium text-gray-900">{{ config('contracts.locales')[$contract->locale] ?? $contract->locale }}</p>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-gray-500">{{ __('Para Birimi') }}</p>
                    <p class="text-base font-medium text-gray-900">{{ $contract->currency }}</p>
                </div>
            </div>
        </x-card>

        <x-card>
            <x-slot name="header">{{ __('Taraflar') }}</x-slot>
            <div class="grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <p class="text-xs tracking-wide text-gray-500">{{ __('Müşteri Adı') }}</p>
                    <p class="text-base font-medium text-gray-900">{{ $contract->customer_name }}</p>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-gray-500">{{ __('Firma') }}</p>
                    <p class="text-base font-medium text-gray-900">{{ $contract->customer_company ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-gray-500">{{ __('Vergi No') }}</p>
                    <p class="text-base font-medium text-gray-900">{{ $contract->customer_tax_no ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-gray-500">{{ __('Telefon') }}</p>
                    <p class="text-base font-medium text-gray-900">{{ $contract->customer_phone ?: '-' }}</p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs tracking-wide text-gray-500">{{ __('Adres') }}</p>
                    <p class="text-base font-medium text-gray-900">{{ $contract->customer_address ?: '-' }}</p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs tracking-wide text-gray-500">{{ __('E-posta') }}</p>
                    <p class="text-base font-medium text-gray-900">{{ $contract->customer_email ?: '-' }}</p>
                </div>
            </div>
        </x-card>

        <x-card>
            <x-slot name="header">{{ __('Toplamlar') }}</x-slot>
            <div class="grid gap-4 text-sm sm:grid-cols-3">
                <div>
                    <p class="text-xs tracking-wide text-gray-500">{{ __('Ara Toplam') }}</p>
                    <p class="text-base font-medium text-gray-900">{{ $formatMoney($contract->subtotal) }} {{ $currencySymbol }}</p>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-gray-500">{{ __('Vergi Toplamı') }}</p>
                    <p class="text-base font-medium text-gray-900">{{ $formatMoney($contract->tax_total) }} {{ $currencySymbol }}</p>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-gray-500">{{ __('Genel Toplam') }}</p>
                    <p class="text-base font-medium text-gray-900">{{ $formatMoney($contract->grand_total) }} {{ $currencySymbol }}</p>
                </div>
            </div>
        </x-card>

        <x-card>
            <x-slot name="header">{{ __('Koşullar') }}</x-slot>
            <div class="grid gap-4 text-sm text-gray-700 md:grid-cols-2">
                <div>
                    <p class="font-semibold text-gray-900">{{ __('Ödeme Şartları') }}</p>
                    <p class="mt-1">{{ $contract->payment_terms ?: '-' }}</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">{{ __('Garanti Şartları') }}</p>
                    <p class="mt-1">{{ $contract->warranty_terms ?: '-' }}</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">{{ __('Kapsam') }}</p>
                    <p class="mt-1">{{ $contract->scope_text ?: '-' }}</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">{{ __('Hariç Tutulanlar') }}</p>
                    <p class="mt-1">{{ $contract->exclusions_text ?: '-' }}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="font-semibold text-gray-900">{{ __('Teslim Şartları') }}</p>
                    <p class="mt-1">{{ $contract->delivery_terms ?: '-' }}</p>
                </div>
            </div>
        </x-card>

        <x-card>
            <x-slot name="header">{{ __('Revizyonlar') }}</x-slot>
            <div class="space-y-3 text-sm">
                @foreach ($revisions as $revision)
                    <div class="flex flex-col gap-3 rounded-lg border border-gray-100 p-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="space-y-1">
                            <p class="text-xs text-gray-500">{{ $revision->contract_no }}</p>
                            <p class="text-base font-semibold text-gray-900">{{ $revision->revision_label }}</p>
                            <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                <span>{{ $revision->issued_at?->format('d.m.Y') ?? '-' }}</span>
                                <span>·</span>
                                <span>{{ $revision->signed_at?->format('d.m.Y H:i') ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-ui.badge :variant="$statusVariants[$revision->status] ?? 'neutral'">
                                {{ $revision->status_label }}
                            </x-ui.badge>
                            @if ($revision->is_current)
                                <x-ui.badge variant="success">
                                    {{ __('Güncel') }}
                                </x-ui.badge>
                            @endif
                            <x-button href="{{ route('contracts.show', $revision) }}" variant="secondary" size="sm">
                                {{ __('Görüntüle') }}
                            </x-button>
                            <x-button href="{{ route('contracts.pdf', $revision) }}" variant="secondary" size="sm">
                                {{ __('PDF') }}
                            </x-button>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>

        <section
            id="delivery-pack"
            x-data="{ tab: 'message', isDesktop: false }"
            x-init="
                const media = window.matchMedia('(min-width: 768px)');
                isDesktop = media.matches;
                media.addEventListener('change', event => isDesktop = event.matches);
            "
            class="space-y-4"
        >
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">{{ __('Gönderim / Paylaşım') }}</h2>
                    <p class="text-sm text-gray-500">{{ __('Sözleşmeyi paylaşın, ekleri yönetin ve gönderim geçmişini takip edin.') }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2" x-data="{ downloadingPdf: false, downloadingZip: false }">
                    <x-button
                        href="{{ route('contracts.pdf', $contract) }}"
                        variant="secondary"
                        size="sm"
                        @click="downloadingPdf = true"
                        x-bind:class="downloadingPdf ? 'pointer-events-none opacity-60' : ''"
                    >
                        <span x-show="!downloadingPdf">{{ __('PDF İndir') }}</span>
                        <span x-cloak x-show="downloadingPdf">{{ __('Hazırlanıyor...') }}</span>
                    </x-button>
                    <x-button
                        href="{{ route('contracts.delivery_pack', $contract) }}"
                        variant="secondary"
                        size="sm"
                        @click="downloadingZip = true"
                        x-bind:class="downloadingZip ? 'pointer-events-none opacity-60' : ''"
                    >
                        <span x-show="!downloadingZip">{{ __('ZIP İndir') }}</span>
                        <span x-cloak x-show="downloadingZip">{{ __('Hazırlanıyor...') }}</span>
                    </x-button>
                </div>
            </div>

            <div class="md:hidden">
                <div class="flex items-center gap-2 rounded-lg bg-gray-50 p-2 text-sm" role="tablist">
                    <button
                        type="button"
                        role="tab"
                        class="flex-1 rounded-md px-3 py-2 font-medium"
                        :class="tab === 'message' ? 'bg-white text-gray-900 shadow' : 'text-gray-500'"
                        @click="tab = 'message'"
                        :aria-selected="tab === 'message'"
                        :tabindex="tab === 'message' ? 0 : -1"
                    >
                        {{ __('Mesaj') }}
                    </button>
                    <button
                        type="button"
                        role="tab"
                        class="flex-1 rounded-md px-3 py-2 font-medium"
                        :class="tab === 'attachments' ? 'bg-white text-gray-900 shadow' : 'text-gray-500'"
                        @click="tab = 'attachments'"
                        :aria-selected="tab === 'attachments'"
                        :tabindex="tab === 'attachments' ? 0 : -1"
                    >
                        {{ __('Ek Dosyalar') }}
                    </button>
                    <button
                        type="button"
                        role="tab"
                        class="flex-1 rounded-md px-3 py-2 font-medium"
                        :class="tab === 'history' ? 'bg-white text-gray-900 shadow' : 'text-gray-500'"
                        @click="tab = 'history'"
                        :aria-selected="tab === 'history'"
                        :tabindex="tab === 'history' ? 0 : -1"
                    >
                        {{ __('Geçmiş') }}
                    </button>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-4" x-show="tab === 'message' || isDesktop" x-cloak>
                    <x-card>
                        <x-slot name="header">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-base font-semibold text-gray-900">{{ __('Gönderim Mesajı') }}</p>
                                    <p class="text-sm text-gray-500">{{ __('Kanal, alıcı ve metni hızlıca hazırlayın.') }}</p>
                                </div>
                                <div class="text-right text-xs text-gray-500">
                                    <span class="block font-semibold text-gray-900">{{ __('Son Durum') }}</span>
                                    @if ($lastDelivery)
                                        <span>{{ $deliveryStatusLabels[$lastDelivery->status] ?? $lastDelivery->status }}</span>
                                        <span class="text-gray-400">·</span>
                                        <span>{{ ($lastDelivery->sent_at ?? $lastDelivery->created_at)?->format('d.m.Y H:i') }}</span>
                                    @else
                                        <span>{{ __('Henüz kayıt yok') }}</span>
                                    @endif
                                </div>
                            </div>
                        </x-slot>
                        <form
                            method="POST"
                            action="{{ route('contracts.deliveries.store', $contract) }}"
                            class="space-y-5"
                            x-data="{
                                channel: '{{ old('channel', 'email') }}',
                                recipientName: @js(old('recipient_name')),
                                recipient: @js(old('recipient')),
                                includedPdf: {{ old('included_pdf', true) ? 'true' : 'false' }},
                                includedAttachments: {{ old('included_attachments') ? 'true' : 'false' }},
                                templateLocale: '{{ $contract->locale === 'en' ? 'en' : 'tr' }}',
                                templateLength: '{{ str_contains($defaultTemplateKey, 'long') ? 'long' : 'short' }}',
                                templates: @js($deliveryTemplates),
                                message: @js(old('message', $deliveryTemplates[$defaultTemplateKey])),
                                get templateKey() {
                                    return `${this.templateLocale}_${this.templateLength}`;
                                },
                                updateMessage() {
                                    this.message = this.templates[this.templateKey];
                                },
                                copied: false,
                                loading: false,
                                copyMessage() {
                                    if (!navigator.clipboard) {
                                        return;
                                    }
                                    navigator.clipboard.writeText(this.message).then(() => {
                                        this.copied = true;
                                        setTimeout(() => (this.copied = false), 2000);
                                    });
                                }
                            }"
                            @submit="loading = true"
                        >
                            @csrf
                            <div class="space-y-3">
                                <x-input-label :value="__('Gönderim Kanalı')" />
                                <div class="grid gap-2 sm:grid-cols-3">
                                    <label class="flex items-start gap-2 rounded-lg border border-gray-200 p-3 text-sm text-gray-700" :class="channel === 'email' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : ''">
                                        <input class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" type="radio" name="channel" value="email" x-model="channel">
                                        <span class="space-y-1">
                                            <span class="block font-semibold">{{ __('E-posta') }}</span>
                                            <span class="block text-xs text-gray-500">{{ __('Kopyala & gönder.') }}</span>
                                        </span>
                                    </label>
                                    <label class="flex items-start gap-2 rounded-lg border border-gray-200 p-3 text-sm text-gray-700" :class="channel === 'whatsapp' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : ''">
                                        <input class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" type="radio" name="channel" value="whatsapp" x-model="channel">
                                        <span class="space-y-1">
                                            <span class="block font-semibold">{{ __('WhatsApp') }}</span>
                                            <span class="block text-xs text-gray-500">{{ __('Hızlı paylaşım.') }}</span>
                                        </span>
                                    </label>
                                    <label class="flex items-start gap-2 rounded-lg border border-gray-200 p-3 text-sm text-gray-700" :class="channel === 'manual' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : ''">
                                        <input class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" type="radio" name="channel" value="manual" x-model="channel">
                                        <span class="space-y-1">
                                            <span class="block font-semibold">{{ __('Manuel') }}</span>
                                            <span class="block text-xs text-gray-500">{{ __('Kaydı elle gir.') }}</span>
                                        </span>
                                    </label>
                                </div>
                                <x-input-error :messages="$errors->get('channel')" class="mt-2" />
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <x-input-label for="recipient_name" :value="__('Alıcı Adı')" />
                                    <x-input id="recipient_name" name="recipient_name" type="text" class="mt-1" x-model="recipientName" />
                                    <p class="mt-1 text-xs text-gray-500">{{ __('Örn. Ahmet Yılmaz') }}</p>
                                    <x-input-error :messages="$errors->get('recipient_name')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="recipient" :value="__('Alıcı (E-posta / Telefon)')" />
                                    <x-input id="recipient" name="recipient" type="text" class="mt-1" x-model="recipient" />
                                    <p class="mt-1 text-xs text-gray-500">{{ __('E-posta veya telefon numarası girin.') }}</p>
                                    <x-input-error :messages="$errors->get('recipient')" class="mt-2" />
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-4 text-sm text-gray-700">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="included_pdf" value="1" x-model="includedPdf" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    {{ __('PDF dahil') }}
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="included_attachments" value="1" x-model="includedAttachments" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    {{ __('Ekler dahil') }}
                                </label>
                            </div>

                            <div class="space-y-3">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <x-input-label :value="__('Mesaj Şablonu')" />
                                        <p class="text-xs text-gray-500">{{ __('Kısa veya detaylı metni seçin.') }}</p>
                                    </div>
                                    <div class="flex items-center gap-2 rounded-lg bg-gray-50 p-2 text-xs">
                                        <button type="button" class="rounded-md px-3 py-1 font-semibold" :class="templateLength === 'short' ? 'bg-white text-gray-900 shadow' : 'text-gray-500'" @click="templateLength = 'short'; updateMessage()">
                                            {{ __('Kısa') }}
                                        </button>
                                        <button type="button" class="rounded-md px-3 py-1 font-semibold" :class="templateLength === 'long' ? 'bg-white text-gray-900 shadow' : 'text-gray-500'" @click="templateLength = 'long'; updateMessage()">
                                            {{ __('Detaylı') }}
                                        </button>
                                        <div class="mx-1 h-4 w-px bg-gray-200"></div>
                                        <button type="button" class="rounded-md px-3 py-1 font-semibold" :class="templateLocale === 'tr' ? 'bg-white text-gray-900 shadow' : 'text-gray-500'" @click="templateLocale = 'tr'; updateMessage()">
                                            {{ __('TR') }}
                                        </button>
                                        <button type="button" class="rounded-md px-3 py-1 font-semibold" :class="templateLocale === 'en' ? 'bg-white text-gray-900 shadow' : 'text-gray-500'" @click="templateLocale = 'en'; updateMessage()">
                                            {{ __('EN') }}
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <x-input-label for="message" :value="__('Mesaj Metni')" />
                                    <x-textarea id="message" name="message" rows="6" class="mt-1" x-model="message"></x-textarea>
                                    <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500">
                                        <span>{{ __('Metni düzenleyebilir veya kopyalayabilirsiniz.') }}</span>
                                        <div class="flex items-center gap-2">
                                            <span x-cloak x-show="copied" class="rounded-full bg-emerald-50 px-2 py-1 font-medium text-emerald-700">{{ __('Kopyalandı') }}</span>
                                            <x-button type="button" size="sm" variant="secondary" @click="copyMessage()">
                                                {{ __('Kopyala') }}
                                            </x-button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 text-sm">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Gönderim Kaydı Önizleme') }}</p>
                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <p class="text-xs text-gray-500">{{ __('Kanal') }}</p>
                                        <p class="font-medium text-gray-900" x-text="channel === 'email' ? 'E-posta' : channel === 'whatsapp' ? 'WhatsApp' : 'Manuel'"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">{{ __('Alıcı') }}</p>
                                        <p class="font-medium text-gray-900" x-text="recipientName || @js(__('Alıcı belirtilmedi'))"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">{{ __('Bilgi') }}</p>
                                        <p class="font-medium text-gray-900" x-text="recipient || @js(__('E-posta/telefon yok'))"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">{{ __('Ekler') }}</p>
                                        <p class="font-medium text-gray-900" x-text="includedAttachments ? @js(__('Ekler dahil')) : @js(__('Ekler yok'))"></p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center justify-end gap-3">
                                <x-button type="submit" :disabled="loading">
                                    <span x-show="!loading">{{ __('Paketi Hazırla') }}</span>
                                    <span x-cloak x-show="loading">{{ __('Hazırlanıyor...') }}</span>
                                </x-button>
                            </div>
                        </form>
                    </x-card>
                </div>

                <div class="space-y-4" x-show="tab !== 'message' || isDesktop" x-cloak>
                    <x-card x-show="tab === 'attachments' || isDesktop" x-cloak>
                        <x-slot name="header">
                            <div>
                                <p class="text-base font-semibold text-gray-900">{{ __('Ek Dosyalar') }}</p>
                                <p class="text-sm text-gray-500">{{ __('Dosyaları ekleyin ve paylaşım paketinde yönetin.') }}</p>
                            </div>
                        </x-slot>
                        <div class="space-y-6">
                            <form
                                method="POST"
                                action="{{ route('contracts.attachments.store', $contract) }}"
                                enctype="multipart/form-data"
                                class="space-y-4"
                                x-data="{ uploading: false }"
                                @submit="uploading = true"
                            >
                                @csrf
                                <div class="grid gap-4 md:grid-cols-3">
                                    <div>
                                        <x-input-label for="attachment_title" :value="__('Dosya Başlığı')" />
                                        <x-input id="attachment_title" name="title" type="text" class="mt-1" :value="old('title')" />
                                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-input-label for="attachment_type" :value="__('Dosya Türü')" />
                                        <x-select id="attachment_type" name="type" class="mt-1">
                                            @foreach ($attachmentTypeLabels as $typeValue => $label)
                                                <option value="{{ $typeValue }}" @selected(old('type') === $typeValue)>{{ $label }}</option>
                                            @endforeach
                                        </x-select>
                                        <x-input-error :messages="$errors->get('type')" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-input-label for="attachment_file" :value="__('Dosya')" />
                                        <x-input id="attachment_file" name="file" type="file" class="mt-1" />
                                        <p class="mt-1 text-xs text-gray-500">
                                            {{ __('İzinli türler:') }} {{ implode(', ', $allowedAttachmentMimes) }} · {{ __('Maksimum:') }} {{ $formatBytes($maxAttachmentSizeKb * 1024) }}
                                        </p>
                                        <x-input-error :messages="$errors->get('file')" class="mt-2" />
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <x-button type="submit" variant="secondary" :disabled="uploading">
                                        <span x-show="!uploading">{{ __('Dosya Yükle') }}</span>
                                        <span x-cloak x-show="uploading">{{ __('Yükleniyor...') }}</span>
                                    </x-button>
                                </div>
                            </form>

                            <div class="space-y-3 text-sm">
                                @forelse ($contract->attachments as $attachment)
                                    <div class="flex flex-col gap-3 rounded-lg border border-gray-100 p-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="space-y-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="font-semibold text-gray-900">{{ $attachment->title }}</p>
                                                <x-ui.badge variant="neutral">{{ $attachmentTypeLabels[$attachment->type] ?? $attachment->type }}</x-ui.badge>
                                            </div>
                                            <p class="text-xs text-gray-500">
                                                {{ $formatBytes($attachment->size) }} · {{ $attachment->created_at?->format('d.m.Y H:i') }}
                                                @if ($attachment->uploader)
                                                    · {{ $attachment->uploader->name }}
                                                @endif
                                            </p>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <x-button
                                                href="{{ route('contracts.attachments.download', [$contract, $attachment]) }}"
                                                variant="secondary"
                                                size="sm"
                                                target="_blank"
                                                rel="noopener"
                                            >
                                                {{ __('İndir') }}
                                            </x-button>
                                            <form method="POST" action="{{ route('contracts.attachments.destroy', [$contract, $attachment]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <x-button type="submit" variant="danger" size="sm" onclick="return confirm('Ek dosya silinsin mi?')">
                                                    {{ __('Sil') }}
                                                </x-button>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-lg border border-dashed border-gray-200 p-6 text-center">
                                        <p class="text-sm font-medium text-gray-700">{{ __('Henüz ek dosya yok') }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ __('İlk dosyayı yükleyerek paylaşım paketini zenginleştirin.') }}</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </x-card>

                    <x-card x-data="{ filter: 'all' }" x-show="tab === 'history' || isDesktop" x-cloak>
                        <x-slot name="header">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-base font-semibold text-gray-900">{{ __('Gönderim Geçmişi') }}</p>
                                    <p class="text-sm text-gray-500">{{ __('Hazırlanan ve gönderilen kayıtları takip edin.') }}</p>
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <button type="button" class="rounded-full px-3 py-1 font-semibold" :class="filter === 'all' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600'" @click="filter = 'all'">
                                        {{ __('Tümü') }}
                                    </button>
                                    <button type="button" class="rounded-full px-3 py-1 font-semibold" :class="filter === 'sent' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600'" @click="filter = 'sent'">
                                        {{ __('Gönderildi') }}
                                    </button>
                                    <button type="button" class="rounded-full px-3 py-1 font-semibold" :class="filter === 'prepared' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600'" @click="filter = 'prepared'">
                                        {{ __('Hazır') }}
                                    </button>
                                </div>
                            </div>
                        </x-slot>
                        <div class="space-y-3 text-sm">
                            @forelse ($deliveriesSorted as $delivery)
                                <div
                                    class="rounded-lg border border-gray-100 p-3 space-y-3"
                                    x-show="filter === 'all' || filter === '{{ $delivery->status }}'"
                                    x-cloak
                                >
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="space-y-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="font-semibold text-gray-900">{{ $deliveryChannelLabels[$delivery->channel] ?? $delivery->channel }}</p>
                                                <x-ui.badge :variant="$deliveryStatusVariants[$delivery->status] ?? 'neutral'">
                                                    {{ $deliveryStatusLabels[$delivery->status] ?? $delivery->status }}
                                                </x-ui.badge>
                                            </div>
                                            <p class="text-xs text-gray-500">
                                                {{ $delivery->recipient_name ?: __('Alıcı belirtilmedi') }}
                                                @if ($delivery->recipient)
                                                    · {{ $delivery->recipient }}
                                                @endif
                                            </p>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                            <span>{{ $delivery->sent_at?->format('d.m.Y H:i') ?? $delivery->created_at?->format('d.m.Y H:i') }}</span>
                                            @if ($delivery->status !== 'sent')
                                                <form method="POST" action="{{ route('contracts.deliveries.mark_sent', [$contract, $delivery]) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <x-button type="submit" size="sm" onclick="return confirm('Gönderildi olarak işaretlensin mi?')">
                                                        {{ __('Gönderildi') }}
                                                    </x-button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500">
                                        <span>{{ __('Oluşturan') }}: {{ $delivery->creator?->name ?? '-' }}</span>
                                        <span>{{ __('Kanal') }}: {{ $deliveryChannelLabels[$delivery->channel] ?? $delivery->channel }}</span>
                                    </div>
                                    @if ($delivery->message)
                                        <div x-data="{ open: false }">
                                            <button type="button" class="text-xs font-semibold text-indigo-600" @click="open = !open" :aria-expanded="open.toString()">
                                                {{ __('Mesajı Gör') }}
                                            </button>
                                            <div class="mt-2" x-show="open" x-cloak>
                                                <pre class="whitespace-pre-wrap rounded bg-gray-50 p-3 text-xs text-gray-700">{{ $delivery->message }}</pre>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">{{ __('Henüz gönderim kaydı yok.') }}</p>
                            @endforelse
                        </div>
                    </x-card>
                </div>
            </div>
        </section>

        <x-card>
            <x-slot name="header">{{ __('Satış Siparişi Kalemleri') }}</x-slot>
            <div class="space-y-4">
                @forelse ($contract->salesOrder->items as $item)
                    <div class="flex flex-col gap-2 rounded-lg border border-gray-100 p-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-medium text-gray-900">{{ $item->description }}</p>
                            <p class="text-xs text-gray-500">{{ $item->section ?: __('Genel') }}</p>
                        </div>
                        <div class="text-right text-gray-700">
                            {{ $item->qty }} {{ $item->unit }} · {{ $formatMoney($item->unit_price) }} {{ $currencySymbol }}
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">{{ __('Kalem bulunamadı.') }}</p>
                @endforelse
            </div>
        </x-card>
    </div>
</x-app-layout>
