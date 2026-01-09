<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Teklif Düzenle') }}" subtitle="{{ __('Teklif bilgilerini güncelleyin.') }}">
            <x-slot name="actions">
                <x-button href="{{ route('quotes.show', $quote) }}" variant="secondary" size="sm">
                    {{ __('Detaya Dön') }}
                </x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <x-card>
        <x-slot name="header">{{ __('Teklif Bilgileri') }}</x-slot>
        <form method="POST" action="{{ route('quotes.update', $quote) }}" class="space-y-6">
            @csrf
            @method('PUT')

            @include('quotes._form')

            <div class="flex items-center justify-end">
                <x-button type="submit">{{ __('Güncelle') }}</x-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
