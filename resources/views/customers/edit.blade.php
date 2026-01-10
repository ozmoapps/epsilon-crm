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
        <form id="customer-update-{{ $customer->id }}" method="POST" action="{{ route('customers.update', $customer) }}" class="space-y-6">
            @csrf
            @method('PUT')

            @include('customers._form', ['customer' => $customer])

            <div class="flex items-center justify-end gap-3">
                <x-ui.confirm-dialog
                    title="{{ __('Değişiklikleri kaydet') }}"
                    message="{{ __('Yaptığınız değişiklikler kaydedilecek. Devam edilsin mi?') }}"
                    confirm-text="{{ __('Kaydet') }}"
                    cancel-text="{{ __('Vazgeç') }}"
                    variant="primary"
                    form-id="customer-update-{{ $customer->id }}"
                >
                    <x-slot name="trigger">
                        <x-button type="button" size="sm">{{ __('Güncelle') }}</x-button>
                    </x-slot>
                </x-ui.confirm-dialog>
            </div>
        </form>
    </x-card>
</x-app-layout>
