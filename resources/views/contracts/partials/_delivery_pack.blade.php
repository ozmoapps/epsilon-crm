<section
    id="delivery-pack"
    class="space-y-4"
>
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">{{ __('Gönderim / Paylaşım') }}</h2>
            <p class="text-sm text-slate-500">{{ __('Sözleşmeyi paylaşın, ekleri yönetin ve gönderim geçmişini takip edin.') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <x-ui.button
                href="{{ route('contracts.pdf', $contract) }}"
                variant="secondary"
                size="sm"
                @click="downloadingPdf = true"
                x-bind:class="downloadingPdf ? 'pointer-events-none opacity-60' : ''"
            >
                <span x-show="!downloadingPdf">{{ __('PDF İndir') }}</span>
                <span x-cloak x-show="downloadingPdf">{{ __('Hazırlanıyor...') }}</span>
            </x-ui.button>
            <x-ui.button
                href="{{ route('contracts.delivery_pack', $contract) }}"
                variant="secondary"
                size="sm"
                @click="downloadingZip = true"
                x-bind:class="downloadingZip ? 'pointer-events-none opacity-60' : ''"
            >
                <span x-show="!downloadingZip">{{ __('ZIP İndir') }}</span>
                <span x-cloak x-show="downloadingZip">{{ __('Hazırlanıyor...') }}</span>
            </x-ui.button>
        </div>
    </div>

    <div class="md:hidden">
        <div class="flex items-center gap-2 rounded-xl bg-slate-50 p-2 text-sm" role="tablist">
            <button
                type="button"
                role="tab"
                class="flex-1 rounded-md px-3 py-2 font-medium"
                :class="tab === 'message' ? 'bg-white text-slate-900 shadow' : 'text-slate-500'"
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
                :class="tab === 'attachments' ? 'bg-white text-slate-900 shadow' : 'text-slate-500'"
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
                :class="tab === 'history' ? 'bg-white text-slate-900 shadow' : 'text-slate-500'"
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
            <x-ui.card>
                <x-slot name="header">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-base font-semibold text-slate-900">{{ __('Gönderim Mesajı') }}</p>
                            <p class="text-sm text-slate-500">{{ __('Kanal, alıcı ve metni hızlıca hazırlayın.') }}</p>
                        </div>
                        <div class="text-right text-xs text-slate-500">
                            <span class="block font-semibold text-slate-900">{{ __('Son Durum') }}</span>
                            @if ($lastDelivery)
                                <span>{{ $deliveryStatusLabels[$lastDelivery->status] ?? $lastDelivery->status }}</span>
                                <span class="text-slate-400">·</span>
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
                            <label class="flex items-start gap-2 rounded-xl border border-slate-200 p-3 text-sm text-slate-700" :class="channel === 'email' ? 'border-brand-500 bg-brand-50 text-brand-700' : ''">
                                <input class="mt-1 rounded border-slate-300 text-brand-600 focus:ring-brand-500" type="radio" name="channel" value="email" x-model="channel">
                                <span class="space-y-1">
                                    <span class="block font-semibold">{{ __('E-posta') }}</span>
                                    <span class="block text-xs text-slate-500">{{ __('Kopyala & gönder.') }}</span>
                                </span>
                            </label>
                            <label class="flex items-start gap-2 rounded-xl border border-slate-200 p-3 text-sm text-slate-700" :class="channel === 'whatsapp' ? 'border-brand-500 bg-brand-50 text-brand-700' : ''">
                                <input class="mt-1 rounded border-slate-300 text-brand-600 focus:ring-brand-500" type="radio" name="channel" value="whatsapp" x-model="channel">
                                <span class="space-y-1">
                                    <span class="block font-semibold">{{ __('WhatsApp') }}</span>
                                    <span class="block text-xs text-slate-500">{{ __('Hızlı paylaşım.') }}</span>
                                </span>
                            </label>
                            <label class="flex items-start gap-2 rounded-xl border border-slate-200 p-3 text-sm text-slate-700" :class="channel === 'manual' ? 'border-brand-500 bg-brand-50 text-brand-700' : ''">
                                <input class="mt-1 rounded border-slate-300 text-brand-600 focus:ring-brand-500" type="radio" name="channel" value="manual" x-model="channel">
                                <span class="space-y-1">
                                    <span class="block font-semibold">{{ __('Manuel') }}</span>
                                    <span class="block text-xs text-slate-500">{{ __('Kaydı elle gir.') }}</span>
                                </span>
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('channel')" class="mt-2" />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <x-input-label for="recipient_name" :value="__('Alıcı Adı')" />
                            <x-input id="recipient_name" name="recipient_name" type="text" class="mt-1" x-model="recipientName" />
                            <p class="mt-1 text-xs text-slate-500">{{ __('Örn. Ahmet Yılmaz') }}</p>
                            <x-input-error :messages="$errors->get('recipient_name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="recipient" :value="__('Alıcı (E-posta / Telefon)')" />
                            <x-input id="recipient" name="recipient" type="text" class="mt-1" x-model="recipient" />
                            <p class="mt-1 text-xs text-slate-500">{{ __('E-posta veya telefon numarası girin.') }}</p>
                            <x-input-error :messages="$errors->get('recipient')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-4 text-sm text-slate-700">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="included_pdf" value="1" x-model="includedPdf" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            {{ __('PDF dahil') }}
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="included_attachments" value="1" x-model="includedAttachments" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            {{ __('Ekler dahil') }}
                        </label>
                    </div>

                    <div class="space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <x-input-label :value="__('Mesaj Şablonu')" />
                                <p class="text-xs text-slate-500">{{ __('Kısa veya detaylı metni seçin.') }}</p>
                            </div>
                            <div class="flex items-center gap-2 rounded-xl bg-slate-50 p-2 text-xs">
                                <button type="button" class="rounded-md px-3 py-1 font-semibold" :class="templateLength === 'short' ? 'bg-white text-slate-900 shadow' : 'text-slate-500'" @click="templateLength = 'short'; updateMessage()">
                                    {{ __('Kısa') }}
                                </button>
                                <button type="button" class="rounded-md px-3 py-1 font-semibold" :class="templateLength === 'long' ? 'bg-white text-slate-900 shadow' : 'text-slate-500'" @click="templateLength = 'long'; updateMessage()">
                                    {{ __('Detaylı') }}
                                </button>
                                <div class="mx-1 h-4 w-px bg-slate-200"></div>
                                <button type="button" class="rounded-md px-3 py-1 font-semibold" :class="templateLocale === 'tr' ? 'bg-white text-slate-900 shadow' : 'text-slate-500'" @click="templateLocale = 'tr'; updateMessage()">
                                    {{ __('TR') }}
                                </button>
                                <button type="button" class="rounded-md px-3 py-1 font-semibold" :class="templateLocale === 'en' ? 'bg-white text-slate-900 shadow' : 'text-slate-500'" @click="templateLocale = 'en'; updateMessage()">
                                    {{ __('EN') }}
                                </button>
                            </div>
                        </div>
                        <div>
                            <x-input-label for="message" :value="__('Mesaj Metni')" />
                            <x-textarea id="message" name="message" rows="6" class="mt-1" x-model="message"></x-textarea>
                            <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                                <span>{{ __('Metni düzenleyebilir veya kopyalayabilirsiniz.') }}</span>
                                <div class="flex items-center gap-2">
                                    <x-ui.badge x-cloak x-show="copied" variant="success" class="!px-2 !py-1 font-medium">{{ __('Kopyalandı') }}</x-ui.badge>
                                    <x-ui.button type="button" size="sm" variant="secondary" @click="copyMessage()">
                                        {{ __('Kopyala') }}
                                    </x-ui.button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 text-sm">
                        <p class="text-xs font-semibold tracking-wide text-slate-500">{{ __('Gönderim Kaydı Önizleme') }}</p>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div>
                                <p class="text-xs text-slate-500">{{ __('Kanal') }}</p>
                                <p class="font-medium text-slate-900" x-text="channel === 'email' ? 'E-posta' : channel === 'whatsapp' ? 'WhatsApp' : 'Manuel'"></p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">{{ __('Alıcı') }}</p>
                                <p class="font-medium text-slate-900" x-text="recipientName || @js(__('Alıcı belirtilmedi'))"></p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">{{ __('Bilgi') }}</p>
                                <p class="font-medium text-slate-900" x-text="recipient || @js(__('E-posta/telefon yok'))"></p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">{{ __('Ekler') }}</p>
                                <p class="font-medium text-slate-900" x-text="includedAttachments ? @js(__('Ekler dahil')) : @js(__('Ekler yok'))"></p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <x-ui.button type="submit" x-bind:disabled="loading">
                            <span x-show="!loading">{{ __('Paketi Hazırla') }}</span>
                            <span x-cloak x-show="loading">{{ __('Hazırlanıyor...') }}</span>
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>

        <div class="space-y-4" x-show="tab !== 'message' || isDesktop" x-cloak>
            @include('contracts.partials._attachments', [
                'contract' => $contract,
                'attachmentTypeLabels' => $attachmentTypeLabels,
                'allowedAttachmentMimes' => $allowedAttachmentMimes,
                'formatBytes' => $formatBytes,
                'maxAttachmentSizeKb' => $maxAttachmentSizeKb,
            ])

            @include('contracts.partials._history', [
                'contract' => $contract,
                'deliveriesSorted' => $deliveriesSorted,
                'deliveryChannelLabels' => $deliveryChannelLabels,
                'deliveryStatusLabels' => $deliveryStatusLabels,
                'deliveryStatusVariants' => $deliveryStatusVariants,
            ])
        </div>
    </div>
</section>
