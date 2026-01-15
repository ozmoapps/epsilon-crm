<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('İş Emri Düzenle') }}" subtitle="{{ __('İş emri bilgilerini güncelleyin.') }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('work-orders.show', $workOrder) }}" variant="secondary" size="sm">
                    {{ __('Detaya Dön') }}
                </x-ui.button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <x-ui.card>
        <x-slot name="header">{{ __('İş Emri Bilgileri') }}</x-slot>
        <form method="POST" action="{{ route('work-orders.update', $workOrder) }}" class="space-y-6">
            @csrf
            @method('PUT')

            @include('work_orders._form')

            <div class="flex items-center justify-end">
                <x-ui.button type="submit">{{ __('Güncelle') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-app-layout>
