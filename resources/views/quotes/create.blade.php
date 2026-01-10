<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Yeni Teklif') }}" subtitle="{{ __('Teklif detaylarını oluşturun.') }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('quotes.index') }}" variant="secondary" size="sm">
                    {{ __('Listeye Dön') }}
                </x-ui.button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <form id="quote-create" method="POST" action="{{ route('quotes.store') }}" class="space-y-6">
        @csrf

        @include('quotes._form', ['quote' => $quote, 'formId' => 'quote-create'])
    </form>
</x-app-layout>
