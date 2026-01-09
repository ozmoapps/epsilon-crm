<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ $vessel->name }}" subtitle="{{ __('Tekne detay görünümü') }}">
            <x-slot name="actions">
                <x-button href="{{ route('vessels.edit', $vessel) }}" variant="secondary" size="sm">
                    {{ __('Düzenle') }}
                </x-button>
                <x-button href="{{ route('vessels.index') }}" variant="secondary" size="sm">
                    {{ __('Listeye Dön') }}
                </x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">
        <x-card>
            <x-slot name="header">{{ __('Genel Bilgiler') }}</x-slot>
            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-gray-500">{{ __('Müşteri') }}</dt>
                    <dd class="font-medium text-gray-900">
                        @if ($vessel->customer)
                            <a href="{{ route('customers.show', $vessel->customer) }}" class="text-indigo-600 hover:text-indigo-500">
                                {{ $vessel->customer->name }}
                            </a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('Tekne Tipi') }}</dt>
                    <dd class="font-medium text-gray-900">{{ $vessel->boat_type_label ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('Marka') }}</dt>
                    <dd class="font-medium text-gray-900">{{ $vessel->type ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('Model') }}</dt>
                    <dd class="font-medium text-gray-900">{{ $vessel->registration_number ?: '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-gray-500">{{ __('Gövde Malzemesi') }}</dt>
                    <dd class="font-medium text-gray-900">{{ $vessel->material_label ?: '—' }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card>
            <x-slot name="header">{{ __('Boyut, Ağırlık ve Kapasite Bilgileri') }}</x-slot>
            <dl class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-gray-500">{{ __('LOA (m)') }}</dt>
                    <dd class="font-medium text-gray-900">{{ $vessel->loa_m ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('Beam (m)') }}</dt>
                    <dd class="font-medium text-gray-900">{{ $vessel->beam_m ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('Draft (m)') }}</dt>
                    <dd class="font-medium text-gray-900">{{ $vessel->draft_m ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('Net Tonaj') }}</dt>
                    <dd class="font-medium text-gray-900">{{ $vessel->net_tonnage ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('Brüt Tonaj') }}</dt>
                    <dd class="font-medium text-gray-900">{{ $vessel->gross_tonnage ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('Yolcu Kapasitesi') }}</dt>
                    <dd class="font-medium text-gray-900">{{ $vessel->passenger_capacity ?? '—' }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card>
            <x-slot name="header">{{ __('Diğer Bilgiler ve Notlar') }}</x-slot>
            <dl class="text-sm">
                <div>
                    <dt class="text-gray-500">{{ __('Notlar') }}</dt>
                    <dd class="mt-1 font-medium text-gray-900">{{ $vessel->notes ?: '—' }}</dd>
                </div>
            </dl>
        </x-card>

        <form method="POST" action="{{ route('vessels.destroy', $vessel) }}">
            @csrf
            @method('DELETE')
            <x-button type="submit" variant="danger" class="w-full" onclick="return confirm('Tekne kaydı silinsin mi?')">
                {{ __('Tekne Kaydını Sil') }}
            </x-button>
        </form>
    </div>
</x-app-layout>
