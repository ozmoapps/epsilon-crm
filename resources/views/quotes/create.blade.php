<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Yeni Teklif') }}" subtitle="{{ __('Teklif detaylarını oluşturun.') }}">
            <x-slot name="actions">
                <x-button href="{{ route('quotes.index') }}" variant="secondary" size="sm">
                    {{ __('Listeye Dön') }}
                </x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <x-card>
        <x-slot name="header">{{ __('Teklif Bilgileri') }}</x-slot>
        <form method="POST" action="{{ route('quotes.store') }}" class="space-y-6">
            @csrf

            @include('quotes._form', ['quote' => $quote])

            <div class="flex items-center justify-end">
                <x-button type="submit">{{ __('Kaydet') }}</x-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
