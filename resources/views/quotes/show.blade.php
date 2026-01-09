<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                    {{ __('Teklif Detayı') }}
                </h2>
                <p class="text-sm text-gray-500">{{ $quote->quote_no }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('quotes.edit', $quote) }}" class="inline-flex items-center justify-center rounded-md border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    {{ __('Düzenle') }}
                </a>
                <a href="{{ route('quotes.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
                    {{ __('Tüm teklifler') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
            <div class="space-y-6">
                @if (session('success'))
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Müşteri') }}</p>
                            <p class="text-base text-gray-900">{{ $quote->customer?->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Tekne') }}</p>
                            <p class="text-base text-gray-900">{{ $quote->vessel?->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('İş Emri') }}</p>
                            <p class="text-base text-gray-900">{{ $quote->workOrder?->title ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Durum') }}</p>
                            <p class="text-base text-gray-900">{{ $quote->status_label }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Para Birimi') }}</p>
                            <p class="text-base text-gray-900">{{ $quote->currency }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Geçerlilik') }}</p>
                            <p class="text-base text-gray-900">
                                {{ $quote->validity_days !== null ? $quote->validity_days . ' gün' : '-' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Tahmini Süre') }}</p>
                            <p class="text-base text-gray-900">
                                {{ $quote->estimated_duration_days !== null ? $quote->estimated_duration_days . ' gün' : '-' }}
                            </p>
                        </div>
                        <div class="sm:col-span-2">
                            <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Teklif Konusu') }}</p>
                            <p class="text-base text-gray-900">{{ $quote->title }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700">{{ __('Koşullar') }}</h3>
                    <div class="mt-4 space-y-4 text-sm text-gray-700">
                        <div>
                            <p class="font-semibold text-gray-900">{{ __('Ödeme Şartları') }}</p>
                            <p>{{ $quote->payment_terms ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">{{ __('Garanti') }}</p>
                            <p>{{ $quote->warranty_text ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">{{ __('Hariçler') }}</p>
                            <p>{{ $quote->exclusions ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">{{ __('Notlar') }}</p>
                            <p>{{ $quote->notes ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">{{ __('Kur Notu') }}</p>
                            <p>{{ $quote->fx_note ?: '-' }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-sm text-gray-600">
                    <p class="font-semibold text-gray-800">{{ __('Kalemler') }}</p>
                    <p>{{ __('(PR-2 ile eklenecek)') }}</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
