<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Müşteriler') }}" subtitle="{{ __('Müşteri kayıtlarını hızlıca yönetin.') }}">
            <x-slot name="actions">
                <x-button href="{{ route('customers.create') }}">
                    {{ __('Yeni Müşteri') }}
                </x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">
        <x-card>
            <x-slot name="header">{{ __('Filtreler') }}</x-slot>
            <form method="GET" action="{{ route('customers.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <x-input name="search" type="text" placeholder="İsme göre ara" :value="$search" />
                </div>
                <x-button type="submit">{{ __('Ara') }}</x-button>
                <x-button href="{{ route('customers.index') }}" variant="secondary">{{ __('Temizle') }}</x-button>
            </form>
        </x-card>

        <x-card>
            <x-slot name="header">{{ __('Liste') }}</x-slot>
            <div class="space-y-4">
                @forelse ($customers as $customer)
                    <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-4 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-base font-semibold text-gray-900">{{ $customer->name }}</p>
                            <p class="text-sm text-gray-500">
                                {{ $customer->phone ?: 'Telefon yok' }}
                                @if ($customer->email)
                                    · {{ $customer->email }}
                                @endif
                            </p>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2 text-sm sm:mt-0">
                            <x-button href="{{ route('customers.show', $customer) }}" variant="secondary" size="sm">
                                {{ __('Detay') }}
                            </x-button>
                            <x-button href="{{ route('customers.edit', $customer) }}" variant="secondary" size="sm">
                                {{ __('Düzenle') }}
                            </x-button>
                            <form method="POST" action="{{ route('customers.destroy', $customer) }}">
                                @csrf
                                @method('DELETE')
                                <x-button type="submit" variant="danger" size="sm" onclick="return confirm('Müşteri kaydı silinsin mi?')">
                                    {{ __('Sil') }}
                                </x-button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-200 bg-white p-10 text-center text-sm text-gray-500">
                        {{ __('Kayıt bulunamadı.') }}
                    </div>
                @endforelse
            </div>
        </x-card>

        <div>
            {{ $customers->links() }}
        </div>
    </div>
</x-app-layout>
