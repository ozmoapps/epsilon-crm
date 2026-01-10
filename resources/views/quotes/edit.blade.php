<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Teklif Düzenle') }}" subtitle="{{ __('Teklif bilgilerini güncelleyin.') }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('quotes.show', $quote) }}" variant="secondary" size="sm">
                    {{ __('Detaya Dön') }}
                </x-ui.button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <form id="quote-update-{{ $quote->id }}" method="POST" action="{{ route('quotes.update', $quote) }}" class="space-y-6">
        @csrf
        @method('PUT')

        @include('quotes._form', ['formId' => 'quote-update-' . $quote->id])
    </form>
</x-app-layout>
