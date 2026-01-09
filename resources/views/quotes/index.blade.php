<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Teklifler') }}" subtitle="{{ __('Teklif süreçlerini takip edin.') }}">
            <x-slot name="actions">
                <x-button href="{{ route('quotes.create') }}">{{ __('Yeni Teklif') }}</x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">
        <x-card>
            <x-slot name="header">{{ __('Filtreler') }}</x-slot>
            <form method="GET" action="{{ route('quotes.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <x-input name="search" type="text" placeholder="Teklif no veya başlığa göre ara" :value="$search" />
                </div>
                <div class="sm:w-56">
                    <x-select name="status">
                        <option value="">{{ __('Tüm Durumlar') }}</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </x-select>
                </div>
                <x-button type="submit">{{ __('Ara') }}</x-button>
                <x-button href="{{ route('quotes.index') }}" variant="secondary">{{ __('Temizle') }}</x-button>
            </form>
        </x-card>

        <x-card>
            <x-slot name="header">{{ __('Liste') }}</x-slot>
            <div class="space-y-4">
                @forelse ($quotes as $quote)
                    <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-base font-semibold text-gray-900">{{ $quote->quote_no }}</p>
                                <p class="text-sm text-gray-700">{{ $quote->title }}</p>
                                <p class="text-sm text-gray-500">
                                    {{ $quote->customer?->name ?? 'Müşteri yok' }}
                                    @if ($quote->vessel)
                                        · {{ $quote->vessel->name }}
                                    @endif
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                @if ($quote->status)
                                    <x-badge status="{{ $quote->status }}">{{ $quote->status_label }}</x-badge>
                                @endif
                                <x-button href="{{ route('quotes.show', $quote) }}" variant="secondary" size="sm">
                                    {{ __('Görüntüle') }}
                                </x-button>
                                <x-button href="{{ route('quotes.edit', $quote) }}" variant="secondary" size="sm">
                                    {{ __('Düzenle') }}
                                </x-button>
                                <form method="POST" action="{{ route('quotes.destroy', $quote) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" variant="danger" size="sm" onclick="return confirm('Teklif silinsin mi?')">
                                        {{ __('Sil') }}
                                    </x-button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-200 bg-white p-10 text-center text-sm text-gray-500">
                        {{ __('Kayıt bulunamadı.') }}
                    </div>
                @endforelse
            </div>
        </x-card>

        <div>
            {{ $quotes->links() }}
        </div>
    </div>
</x-app-layout>
