<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Yeni İş Emri') }}" subtitle="{{ __('İş emri detaylarını oluşturun.') }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('work-orders.index') }}" variant="secondary" size="sm">
                    {{ __('Listeye Dön') }}
                </x-ui.button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <x-ui.card>
        <x-slot name="header">{{ __('İş Emri Bilgileri') }}</x-slot>
        <form method="POST" action="{{ route('work-orders.store') }}" class="space-y-6">
            @csrf

            @include('work_orders._form', ['workOrder' => $workOrder])

            <div class="flex items-center justify-end">
                <x-ui.button type="submit">{{ __('Kaydet') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-app-layout>
