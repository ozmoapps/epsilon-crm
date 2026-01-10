<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Teklif Önizleme') }}" subtitle="{{ $quote->quote_no }}">
            <x-slot name="actions">
                <x-button href="{{ route('quotes.show', $quote) }}" variant="secondary" size="sm">
                    {{ __('Teklife dön') }}
                </x-button>
                <x-button href="{{ route('quotes.print', $quote) }}" variant="secondary" size="sm">
                    {{ __('Yazdır') }}
                </x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="mx-auto max-w-5xl">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">

            @include('quotes.partials._print', ['quote' => $quote, 'companyProfile' => $companyProfile, 'bankAccounts' => $bankAccounts])
        </div>
    </div>
</x-app-layout>
