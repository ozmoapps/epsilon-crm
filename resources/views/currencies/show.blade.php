<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ $currency->name }}" subtitle="{{ __('Para birimi detayları.') }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('currencies.edit', $currency) }}" size="sm">
                    {{ __('Düzenle') }}
                </x-ui.button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">
        <x-ui.card>
            <x-slot name="header">{{ __('Para Birimi Bilgileri') }}</x-slot>
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase text-slate-500">{{ __('Para Birimi') }}</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ $currency->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase text-slate-500">{{ __('Kod') }}</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ $currency->code }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase text-slate-500">{{ __('Sembol') }}</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ $currency->symbol ?: __('Bilgi yok') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase text-slate-500">{{ __('Durum') }}</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ $currency->is_active ? __('Aktif') : __('Pasif') }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card>
            <x-slot name="header">{{ __('İşlemler') }}</x-slot>
            <div class="flex flex-wrap gap-3">
                <x-ui.button href="{{ route('currencies.edit', $currency) }}">{{ __('Düzenle') }}</x-ui.button>
                <form id="currency-delete" method="POST" action="{{ route('currencies.destroy', $currency) }}">
                    @csrf
                    @method('DELETE')
                </form>
                <x-ui.confirm
                    title="{{ __('Silme işlemini onayla') }}"
                    message="{{ __('Bu işlem geri alınamaz. Devam etmek istiyor musunuz?') }}"
                    confirm-text="{{ __('Evet, sil') }}"
                    cancel-text="{{ __('Vazgeç') }}"
                    variant="danger"
                    form-id="currency-delete"
                >
                    <x-slot name="trigger">
                        <x-ui.button variant="danger" type="button">{{ __('Sil') }}</x-ui.button>
                    </x-slot>
                </x-ui.confirm>
            </div>
        </x-ui.card>
    </div>
</x-app-layout>
