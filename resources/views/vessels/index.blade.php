<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Tekneler') }}" subtitle="{{ __('Tekne kayıtlarını yönetin.') }}">
            <x-slot name="actions">
                <x-button href="{{ route('vessels.create') }}">{{ __('Yeni Tekne') }}</x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">
        <x-card>
            <x-slot name="header">{{ __('Filtreler') }}</x-slot>
            <form method="GET" action="{{ route('vessels.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <x-input name="search" type="text" placeholder="İsme göre ara" :value="$search" />
                </div>
                <x-button type="submit">{{ __('Ara') }}</x-button>
                <x-button href="{{ route('vessels.index') }}" variant="secondary">{{ __('Temizle') }}</x-button>
            </form>
        </x-card>

        <x-card>
            <x-slot name="header">{{ __('Liste') }}</x-slot>
            <div class="space-y-4">
                @forelse ($vessels as $vessel)
                    <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-base font-semibold text-gray-900">{{ $vessel->name }}</p>
                                <p class="text-sm text-gray-500">{{ $vessel->customer?->name ?? 'Müşteri yok' }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2 text-sm">
                                <x-button href="{{ route('vessels.show', $vessel) }}" variant="secondary" size="sm">
                                    {{ __('Detay') }}
                                </x-button>
                                <x-button href="{{ route('vessels.edit', $vessel) }}" variant="secondary" size="sm">
                                    {{ __('Düzenle') }}
                                </x-button>
                                <form method="POST" action="{{ route('vessels.destroy', $vessel) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" variant="danger" size="sm" onclick="return confirm('Tekne kaydı silinsin mi?')">
                                        {{ __('Sil') }}
                                    </x-button>
                                </form>
                            </div>
                        </div>
                        <div class="mt-4 grid gap-3 text-sm text-gray-600 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <span class="text-gray-500">{{ __('Tip') }}:</span>
                                <span class="font-medium text-gray-900">{{ $vessel->boat_type_label ?: '—' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">{{ __('LOA') }}:</span>
                                <span class="font-medium text-gray-900">{{ $vessel->loa_m ?? '—' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">{{ __('Beam') }}:</span>
                                <span class="font-medium text-gray-900">{{ $vessel->beam_m ?? '—' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">{{ __('Draft') }}:</span>
                                <span class="font-medium text-gray-900">{{ $vessel->draft_m ?? '—' }}</span>
                            </div>
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
            {{ $vessels->links() }}
        </div>
    </div>
</x-app-layout>
