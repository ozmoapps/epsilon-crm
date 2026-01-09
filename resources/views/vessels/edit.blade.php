<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Tekne Düzenle') }}" subtitle="{{ __('Tekne bilgilerini güncelleyin.') }}">
            <x-slot name="actions">
                <x-button href="{{ route('vessels.show', $vessel) }}" variant="secondary" size="sm">
                    {{ __('Detaya Dön') }}
                </x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <x-card>
        <x-slot name="header">{{ __('Tekne Bilgileri') }}</x-slot>
        <form method="POST" action="{{ route('vessels.update', $vessel) }}" class="space-y-6">
            @csrf
            @method('PUT')

            @include('vessels._form', ['vessel' => $vessel])

            <div class="flex items-center justify-end">
                <x-button type="submit">{{ __('Güncelle') }}</x-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
