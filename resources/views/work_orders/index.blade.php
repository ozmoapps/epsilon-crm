<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('İş Emirleri') }}" subtitle="{{ __('İş emri süreçlerini takip edin.') }}">
            <x-slot name="actions">
                <x-button href="{{ route('work-orders.create') }}">{{ __('Yeni İş Emri') }}</x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">
        <x-card>
            <x-slot name="header">{{ __('Filtreler') }}</x-slot>
            <form method="GET" action="{{ route('work-orders.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <x-input name="search" type="text" placeholder="İsme göre ara" :value="$search" />
                </div>
                <div class="sm:w-56">
                    <x-select name="status">
                        <option value="">{{ __('Tüm Durumlar') }}</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </x-select>
                </div>
                <x-button type="submit">{{ __('Ara') }}</x-button>
                <x-button href="{{ route('work-orders.index') }}" variant="secondary">{{ __('Temizle') }}</x-button>
            </form>
        </x-card>

        <x-card>
            <x-slot name="header">{{ __('Liste') }}</x-slot>
            <div class="space-y-4">
                @forelse ($workOrders as $workOrder)
                    <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-base font-semibold text-gray-900">{{ $workOrder->title }}</p>
                                <p class="text-sm text-gray-500">
                                    {{ $workOrder->customer?->name ?? 'Müşteri yok' }}
                                    @if ($workOrder->vessel)
                                        · {{ $workOrder->vessel->name }}
                                    @endif
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <x-badge variant="info">{{ $workOrder->status_label }}</x-badge>
                                <x-button href="{{ route('work-orders.show', $workOrder) }}" variant="secondary" size="sm">
                                    {{ __('Detay') }}
                                </x-button>
                                <x-button href="{{ route('work-orders.edit', $workOrder) }}" variant="secondary" size="sm">
                                    {{ __('Düzenle') }}
                                </x-button>
                                <form method="POST" action="{{ route('work-orders.destroy', $workOrder) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" variant="danger" size="sm" onclick="return confirm('İş emri silinsin mi?')">
                                        {{ __('Sil') }}
                                    </x-button>
                                </form>
                            </div>
                        </div>
                        <div class="mt-4 grid gap-3 text-sm text-gray-600 sm:grid-cols-2">
                            <div>
                                <span class="text-gray-500">{{ __('Planlanan Başlangıç') }}:</span>
                                <span class="font-medium text-gray-900">
                                    {{ $workOrder->planned_start_at ? $workOrder->planned_start_at->format('d.m.Y') : '—' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-500">{{ __('Planlanan Bitiş') }}:</span>
                                <span class="font-medium text-gray-900">
                                    {{ $workOrder->planned_end_at ? $workOrder->planned_end_at->format('d.m.Y') : '—' }}
                                </span>
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
            {{ $workOrders->links() }}
        </div>
    </div>
</x-app-layout>
