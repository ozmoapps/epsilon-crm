<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="{{ __('Yeni Müşteri') }}" subtitle="{{ __('Yeni müşteri kaydını oluşturun.') }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('customers.index') }}" variant="secondary" size="sm">
                    {{ __('Vazgeç') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.card class="max-w-4xl">
        <x-slot name="header">{{ __('Müşteri Bilgileri') }}</x-slot>
        <form method="POST" action="{{ route('customers.store') }}" class="space-y-6">
            @csrf

            @include('customers._form')

            <div class="flex items-center justify-end gap-3 mt-8 pt-6 border-t border-slate-100">
                <x-ui.button type="submit" size="sm">{{ __('Kaydet') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-app-layout>
