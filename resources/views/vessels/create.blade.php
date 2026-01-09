<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Yeni Tekne') }}" subtitle="{{ __('Tekne bilgilerini kaydedin.') }}">
            <x-slot name="actions">
                <x-button href="{{ route('vessels.index') }}" variant="secondary" size="sm">
                    {{ __('Listeye Dön') }}
                </x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <x-card>
        <x-slot name="header">{{ __('Tekne Bilgileri') }}</x-slot>
        <form method="POST" action="{{ route('vessels.store') }}" class="space-y-6">
            @csrf

            @include('vessels._form')

            <div class="flex items-center justify-end">
                <x-button type="submit">{{ __('Kaydet') }}</x-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
