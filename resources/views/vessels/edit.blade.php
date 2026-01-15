<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Tekne Düzenle') }}" subtitle="{{ __('Tekne bilgilerini güncelleyin.') }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('vessels.show', $vessel) }}" variant="secondary" size="sm">
                    {{ __('Detaya Dön') }}
                </x-ui.button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <x-ui.card>
        <x-slot name="header">{{ __('Tekne Bilgileri') }}</x-slot>
        <form method="POST" action="{{ route('vessels.update', $vessel) }}" class="space-y-6">
            @csrf
            @method('PUT')

            @include('vessels._form', ['vessel' => $vessel])

            <div class="flex items-center justify-end">
                <x-ui.button type="submit">{{ __('Güncelle') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-app-layout>
