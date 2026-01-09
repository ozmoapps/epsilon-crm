<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Müşteri Düzenle') }}" subtitle="{{ __('Müşteri bilgilerini güncelleyin.') }}">
            <x-slot name="actions">
                <x-button href="{{ route('customers.show', $customer) }}" variant="secondary" size="sm">
                    {{ __('Detaya Dön') }}
                </x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <x-card class="max-w-4xl">
        <x-slot name="header">{{ __('Müşteri Bilgileri') }}</x-slot>
        <form method="POST" action="{{ route('customers.update', $customer) }}" class="space-y-6">
            @csrf
            @method('PUT')

            @include('customers._form', ['customer' => $customer])

            <div class="flex items-center justify-end gap-3">
                <x-button type="submit">{{ __('Güncelle') }}</x-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
