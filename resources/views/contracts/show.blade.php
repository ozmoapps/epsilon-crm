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
        $currencySymbols = config('quotes.currency_symbols', []);
        $currencySymbol = $currencySymbols[$contract->currency] ?? $contract->currency;
        $formatMoney = fn ($value) => number_format((float) $value, 2, ',', '.');
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
