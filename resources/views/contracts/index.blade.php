<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Sözleşmeler') }}" subtitle="{{ __('Sözleşme listesi') }}" />
    </x-slot>

    <div class="space-y-6">
        <x-card>
            <x-slot name="header">{{ __('Filtreler') }}</x-slot>
            <form method="GET" action="{{ route('contracts.index') }}" class="grid gap-4 md:grid-cols-5">
                <div>
                    <x-input-label for="search" :value="__('Sözleşme / Müşteri')" />
                    <x-input id="search" name="search" type="text" class="mt-1" :value="old('search', $search)" />
                </div>
                <div>
                    <x-input-label for="customer" :value="__('Müşteri')" />
                    <x-input id="customer" name="customer" type="text" class="mt-1" :value="old('customer', $customer)" />
                </div>
                <div>
                    <x-input-label for="status" :value="__('Durum')" />
                    <x-select id="status" name="status" class="mt-1">
                        <option value="">{{ __('Tümü') }}</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div>
                    <x-input-label for="date_from" :value="__('Düzenleme Tarihi (Başlangıç)')" />
                    <x-input id="date_from" name="date_from" type="date" class="mt-1" :value="old('date_from', $dateFrom)" />
                </div>
                <div>
                    <x-input-label for="date_to" :value="__('Düzenleme Tarihi (Bitiş)')" />
                    <x-input id="date_to" name="date_to" type="date" class="mt-1" :value="old('date_to', $dateTo)" />
                </div>
                <div class="flex items-end gap-2 md:col-span-3">
                    <x-button type="submit">{{ __('Filtrele') }}</x-button>
                    <x-button href="{{ route('contracts.index') }}" variant="secondary">{{ __('Sıfırla') }}</x-button>
                </div>
            </form>
        </x-card>

        @php
            $statusVariants = [
                'draft' => 'draft',
                'issued' => 'neutral',
                'sent' => 'sent',
                'signed' => 'signed',
                'superseded' => 'neutral',
                'cancelled' => 'cancelled',
            ];
        @endphp
        <div class="space-y-4">
            @forelse ($contracts as $contract)
                <x-card>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs text-slate-500">{{ $contract->contract_no }}</p>
                            <p class="text-base font-semibold text-slate-900">{{ $contract->customer_name }}</p>
                            <p class="text-xs text-slate-500">{{ $contract->issued_at?->format('d.m.Y') }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <x-ui.badge :variant="$statusVariants[$contract->status] ?? 'neutral'">
                                {{ $contract->status_label }}
                            </x-ui.badge>
                            <x-button href="{{ route('contracts.show', $contract) }}" variant="secondary" size="sm">
                                {{ __('Detay') }}
                            </x-button>
                            <form id="contract-delete-{{ $contract->id }}" method="POST" action="{{ route('contracts.destroy', $contract) }}" class="hidden">
                                @csrf
                                @method('DELETE')
                            </form>
                            <x-ui.confirm
                                title="{{ __('Silme işlemini onayla') }}"
                                message="{{ __('Bu işlem geri alınamaz. Devam etmek istiyor musunuz?') }}"
                                confirm-text="{{ __('Evet, sil') }}"
                                cancel-text="{{ __('Vazgeç') }}"
                                variant="danger"
                                form-id="contract-delete-{{ $contract->id }}"
                            >
                                <x-slot name="trigger">
                                    <x-button type="button" variant="danger" size="sm">
                                        {{ __('Sil') }}
                                    </x-button>
                                </x-slot>
                            </x-ui.confirm>
                        </div>
                    </div>
                </x-card>
            @empty
                <x-card>
                    <div class="flex flex-col items-center gap-3 py-6 text-center">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                            <x-icon.info class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-slate-700">{{ __('Sözleşme bulunamadı.') }}</p>
                            <p class="text-xs text-slate-500">{{ __('Filtreleri temizleyerek tekrar deneyin.') }}</p>
                        </div>
                    </div>
                </x-card>
            @endforelse
        </div>

        <div>
            {{ $contracts->links() }}
        </div>
    </div>
</x-app-layout>
