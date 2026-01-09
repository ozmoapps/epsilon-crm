<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('İş Emri Düzenle') }}" subtitle="{{ __('İş emri bilgilerini güncelleyin.') }}">
            <x-slot name="actions">
                <x-button href="{{ route('work-orders.show', $workOrder) }}" variant="secondary" size="sm">
                    {{ __('Detaya Dön') }}
                </x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <x-card>
        <x-slot name="header">{{ __('İş Emri Bilgileri') }}</x-slot>
        <form method="POST" action="{{ route('work-orders.update', $workOrder) }}" class="space-y-6">
            @csrf
            @method('PUT')

            @include('work_orders._form')

            <div class="flex items-center justify-end">
                <x-button type="submit">{{ __('Güncelle') }}</x-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
