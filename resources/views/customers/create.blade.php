<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Yeni Müşteri') }}" subtitle="{{ __('Yeni müşteri kaydını oluşturun.') }}">
            <x-slot name="actions">
                <x-button href="{{ route('customers.index') }}" variant="secondary" size="sm">
                    {{ __('Listeye Dön') }}
                </x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <x-card class="max-w-4xl">
        <x-slot name="header">{{ __('Müşteri Bilgileri') }}</x-slot>
        <form method="POST" action="{{ route('customers.store') }}" class="space-y-6">
            @csrf

            @include('customers._form')

            <div class="flex items-center justify-end gap-3">
                <x-button type="submit" size="sm">{{ __('Kaydet') }}</x-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
