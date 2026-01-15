<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="{{ __('Müşteri Düzenle') }}" subtitle="{{ __('Müşteri bilgilerini güncelleyin.') }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('customers.show', $customer) }}" variant="secondary" size="sm">
                    {{ __('Vazgeç') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.card class="max-w-4xl">
        <x-slot name="header">{{ __('Müşteri Bilgileri') }}</x-slot>
        <form id="customer-update-{{ $customer->id }}" method="POST" action="{{ route('customers.update', $customer) }}" class="space-y-6">
            @csrf
            @method('PUT')

            @include('customers._form', ['customer' => $customer])

            <div class="flex items-center justify-end gap-3 mt-8 pt-6 border-t border-slate-100">
                <x-ui.confirm
                    title="{{ __('Değişiklikler kaydedilsin mi?') }}"
                    message="{{ __('Yaptığınız değişiklikler kaydedilecek. Devam edilsin mi?') }}"
                    confirm-text="{{ __('Kaydet') }}"
                    cancel-text="{{ __('Vazgeç') }}"
                    variant="primary"
                    form-id="customer-update-{{ $customer->id }}"
                >
                    <x-slot name="trigger">
                        <x-ui.button type="button" size="sm">{{ __('Güncelle') }}</x-ui.button>
                    </x-slot>
                </x-ui.confirm>
            </div>
        </form>
    </x-ui.card>
</x-app-layout>
