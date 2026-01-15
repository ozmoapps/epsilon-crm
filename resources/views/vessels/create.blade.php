<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Yeni Tekne') }}" subtitle="{{ __('Tekne bilgilerini kaydedin.') }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('vessels.index') }}" variant="secondary" size="sm">
                    {{ __('Listeye Dön') }}
                </x-ui.button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <x-ui.card>
        <x-slot name="header">{{ __('Tekne Bilgileri') }}</x-slot>
        <form method="POST" action="{{ route('vessels.store') }}" class="space-y-6">
            @csrf

            @include('vessels._form')

            <div class="flex items-center justify-end">
                <x-ui.button type="submit">{{ __('Kaydet') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-app-layout>
