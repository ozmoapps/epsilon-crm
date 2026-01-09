<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                {{ __('Teklifler') }}
            </h2>
            <a href="{{ route('quotes.create') }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                {{ __('Yeni Teklif') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="space-y-6">
                @if (session('success'))
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <form method="GET" action="{{ route('quotes.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="flex-1">
                            <x-text-input name="search" type="text" class="block w-full" placeholder="Teklif no veya başlığa göre ara" :value="$search" />
                        </div>
                        <div class="sm:w-48">
                            <select name="status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('Tüm Durumlar') }}</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                            {{ __('Ara') }}
                        </button>
                        <a href="{{ route('quotes.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
                            {{ __('Temizle') }}
                        </a>
                    </form>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="divide-y divide-gray-200">
                        @forelse ($quotes as $quote)
                            <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-base font-semibold text-gray-900">{{ $quote->quote_no }}</p>
                                    <p class="text-sm text-gray-700">{{ $quote->title }}</p>
                                    <p class="text-sm text-gray-500">
                                        {{ $quote->customer?->name ?? 'Müşteri yok' }}
                                        @if ($quote->vessel)
                                            · {{ $quote->vessel->name }}
                                        @endif
                                        @if ($quote->status)
                                            · {{ $quote->status_label }}
                                        @endif
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2 text-sm">
                                    <a href="{{ route('quotes.show', $quote) }}" class="rounded-md border border-gray-200 px-3 py-1 text-gray-700 hover:bg-gray-50">
                                        {{ __('Görüntüle') }}
                                    </a>
                                    <a href="{{ route('quotes.edit', $quote) }}" class="rounded-md border border-gray-200 px-3 py-1 text-gray-700 hover:bg-gray-50">
                                        {{ __('Düzenle') }}
                                    </a>
                                    <form method="POST" action="{{ route('quotes.destroy', $quote) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md border border-red-200 px-3 py-1 text-red-600 hover:bg-red-50" onclick="return confirm('Teklif silinsin mi?')">
                                            {{ __('Sil') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="p-6 text-center text-sm text-gray-500">
                                {{ __('Kayıt bulunamadı.') }}
                            </div>
                        @endforelse
                    </div>
                </div>

                <div>
                    {{ $quotes->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
