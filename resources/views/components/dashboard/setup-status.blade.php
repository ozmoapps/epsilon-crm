@props([
    'hasCompanyProfile',
    'bankAccountsCount',
    'activeCurrenciesCount',
    'hasDefaultContractTemplate',
])

@php
    $setupItems = [
        [
            'label' => __('Şirket Profili'),
            'status' => $hasCompanyProfile,
            'route' => route('admin.company-profiles.index'),
            'action' => $hasCompanyProfile ? __('Yönet') : __('Ekle'),
        ],
        [
            'label' => __('Banka Hesapları'),
            'status' => $bankAccountsCount > 0,
            'route' => route('bank-accounts.index'),
            'action' => $bankAccountsCount > 0 ? __('Yönet') : __('Ekle'),
        ],
        [
            'label' => __('Aktif Para Birimi'),
            'status' => $activeCurrenciesCount > 0,
            'route' => route('admin.currencies.index'),
            'action' => $activeCurrenciesCount > 0 ? __('Yönet') : __('Ekle'),
        ],
        [
            'label' => __('Varsayılan Sözleşme Şablonu'),
            'status' => $hasDefaultContractTemplate,
            'route' => route('admin.contract-templates.index'),
            'action' => $hasDefaultContractTemplate ? __('Yönet') : __('Ekle'),
        ],
    ];
@endphp

<x-ui.card>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <span>{{ __('Sistem Kurulum Durumu') }}</span>
        </div>
    </x-slot>

    <nav class="space-y-1" aria-label="{{ __('Sistem Kurulum Adımları') }}">
        @foreach ($setupItems as $item)
            <div class="@if(!$item['status']) bg-amber-50/50 @endif flex items-center justify-between rounded-xl p-3 transition hover:bg-slate-50">
                <div class="flex items-center gap-3">
                    <x-ui.badge :variant="$item['status'] ? 'success' : 'neutral'" class="flex h-6 w-6 shrink-0 items-center justify-center !p-0">
                        @if($item['status'])
                            <x-icon.checkmark />
                        @else
                            <x-icon.cross />
                        @endif
                    </x-ui.badge>
                    <span class="text-sm font-medium text-slate-700">{{ $item['label'] }}</span>
                </div>
                <a href="{{ $item['route'] }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 hover:underline">
                    {{ $item['action'] }}
                </a>
            </div>
        @endforeach
    </nav>
</x-ui.card>
