<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ $bankAccount->name }}" subtitle="{{ __('Banka hesabı detayları.') }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('admin.bank-accounts.edit', $bankAccount) }}" size="sm">
                    {{ __('Düzenle') }}
                </x-ui.button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">
        <x-ui.card>
            <x-slot name="header">{{ __('Hesap Bilgileri') }}</x-slot>
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold text-slate-500">{{ __('Hesap Adı') }}</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ $bankAccount->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500">{{ __('Banka') }}</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ $bankAccount->bank_name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500">{{ __('Şube') }}</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ $bankAccount->branch_name ?: __('Bilgi yok') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500">{{ __('Para Birimi') }}</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ $bankAccount->currency?->code ?: __('Tanımsız') }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold text-slate-500">{{ __('IBAN') }}</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ $bankAccount->iban }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card>
            <x-slot name="header">{{ __('İşlemler') }}</x-slot>
            <div class="flex flex-wrap gap-3">
                <x-ui.button href="{{ route('admin.bank-accounts.edit', $bankAccount) }}">{{ __('Düzenle') }}</x-ui.button>
                <form id="bank-account-delete" method="POST" action="{{ route('admin.bank-accounts.destroy', $bankAccount) }}">
                    @csrf
                    @method('DELETE')
                </form>
                <x-ui.confirm
                    title="{{ __('Silme işlemini onayla') }}"
                    message="{{ __('Bu işlem geri alınamaz. Devam etmek istiyor musunuz?') }}"
                    confirm-text="{{ __('Evet, sil') }}"
                    cancel-text="{{ __('Vazgeç') }}"
                    variant="danger"
                    form-id="bank-account-delete"
                >
                    <x-slot name="trigger">
                        <x-ui.button variant="danger" type="button">{{ __('Sil') }}</x-ui.button>
                    </x-slot>
                </x-ui.confirm>
            </div>
        </x-ui.card>
    </div>
</x-app-layout>
