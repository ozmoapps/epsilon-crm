<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Yeni İş Emri') }}" subtitle="{{ __('İş emri detaylarını oluşturun.') }}">
            <x-slot name="actions">
                <x-button href="{{ route('work-orders.index') }}" variant="secondary" size="sm">
                    {{ __('Listeye Dön') }}
                </x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <x-card>
        <x-slot name="header">{{ __('İş Emri Bilgileri') }}</x-slot>
        <form method="POST" action="{{ route('work-orders.store') }}" class="space-y-6">
            @csrf

            @include('work_orders._form', ['workOrder' => $workOrder])

            <div class="flex items-center justify-end">
                <x-button type="submit">{{ __('Kaydet') }}</x-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
