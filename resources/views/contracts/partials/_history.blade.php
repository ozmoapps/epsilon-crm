<x-ui.card x-data="{ filter: 'all' }" x-show="tab === 'history' || isDesktop" x-cloak>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-base font-semibold text-slate-900">{{ __('Gönderim Geçmişi') }}</p>
                <p class="text-sm text-slate-500">{{ __('Hazırlanan ve gönderilen kayıtları takip edin.') }}</p>
            </div>
            <div class="flex items-center gap-2 text-xs">
                <button type="button" class="rounded-full px-3 py-1 font-semibold" :class="filter === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600'" @click="filter = 'all'">
                    {{ __('Tümü') }}
                </button>
                <button type="button" class="rounded-full px-3 py-1 font-semibold" :class="filter === 'sent' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600'" @click="filter = 'sent'">
                    {{ __('Gönderildi') }}
                </button>
                <button type="button" class="rounded-full px-3 py-1 font-semibold" :class="filter === 'prepared' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600'" @click="filter = 'prepared'">
                    {{ __('Hazır') }}
                </button>
            </div>
        </div>
    </x-slot>
    <div class="space-y-3 text-sm">
        @forelse ($deliveriesSorted as $delivery)
            <div
                class="rounded-xl border border-slate-100 p-3 space-y-3"
                x-show="filter === 'all' || filter === '{{ $delivery->status }}'"
                x-cloak
            >
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold text-slate-900">{{ $deliveryChannelLabels[$delivery->channel] ?? $delivery->channel }}</p>
                            <x-ui.badge :variant="$deliveryStatusVariants[$delivery->status] ?? 'neutral'">
                                {{ $deliveryStatusLabels[$delivery->status] ?? $delivery->status }}
                            </x-ui.badge>
                        </div>
                        <p class="text-xs text-slate-500">
                            {{ $delivery->recipient_name ?: __('Alıcı belirtilmedi') }}
                            @if ($delivery->recipient)
                                · {{ $delivery->recipient }}
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                        <span>{{ $delivery->sent_at?->format('d.m.Y H:i') ?? $delivery->created_at?->format('d.m.Y H:i') }}</span>
                        @if ($delivery->status !== 'sent')
    <form
        id="contract-delivery-mark-sent-{{ $contract->id }}-{{ $delivery->id }}"
        method="POST"
        action="{{ route('contracts.deliveries.mark_sent', [$contract, $delivery]) }}"
    >
        @csrf
        @method('PATCH')

        <x-ui.button
            type="submit"
            size="sm"
            data-confirm
            data-confirm-title="{{ __('Emin misiniz?') }}"
            data-confirm-message="{{ __('Gönderim kaydı gönderildi olarak işaretlenecek. Bu işlem geri alınamaz.') }}"
            data-confirm-text="{{ __('Onayla') }}"
            data-confirm-cancel-text="{{ __('Vazgeç') }}"
            data-confirm-submit="contract-delivery-mark-sent-{{ $contract->id }}-{{ $delivery->id }}"
        >
            {{ __('Gönderildi') }}
        </x-ui.button>
    </form>
@endif

                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                    <span>{{ __('Oluşturan') }}: {{ $delivery->creator?->name ?? '-' }}</span>
                    <span>{{ __('Kanal') }}: {{ $deliveryChannelLabels[$delivery->channel] ?? $delivery->channel }}</span>
                </div>
                @if ($delivery->message)
                    <div x-data="{ open: false }">
                        <button type="button" class="text-xs font-semibold text-brand-600" @click="open = !open" :aria-expanded="open.toString()">
                            {{ __('Mesajı Gör') }}
                        </button>
                        <div class="mt-2" x-show="open" x-cloak>
                            <pre class="whitespace-pre-wrap rounded bg-slate-50 p-3 text-xs text-slate-700">{{ $delivery->message }}</pre>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <p class="text-sm text-slate-500">{{ __('Henüz gönderim kaydı yok.') }}</p>
        @endforelse
    </div>
</x-ui.card>
