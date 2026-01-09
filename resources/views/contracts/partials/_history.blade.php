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
