<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ $companyProfile->name }}" subtitle="{{ __('Şirket profili detayları.') }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('admin.company-profiles.edit', $companyProfile) }}" size="sm">
                    {{ __('Düzenle') }}
                </x-ui.button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">
        <x-ui.card>
            <x-slot name="header">{{ __('Genel Bilgiler') }}</x-slot>
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase text-slate-500">{{ __('Şirket Adı') }}</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ $companyProfile->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase text-slate-500">{{ __('Vergi Numarası') }}</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ $companyProfile->tax_no ?: __('Bilgi yok') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase text-slate-500">{{ __('Telefon') }}</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ $companyProfile->phone ?: __('Bilgi yok') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase text-slate-500">{{ __('E-posta') }}</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ $companyProfile->email ?: __('Bilgi yok') }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase text-slate-500">{{ __('Adres') }}</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ $companyProfile->address ?: __('Bilgi yok') }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase text-slate-500">{{ __('Dipnot Metni') }}</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ $companyProfile->footer_text ?: __('Bilgi yok') }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card>
            <x-slot name="header">{{ __('İşlemler') }}</x-slot>
            <div class="flex flex-wrap gap-3">
                <x-ui.button href="{{ route('admin.company-profiles.edit', $companyProfile) }}">{{ __('Düzenle') }}</x-ui.button>
                <form id="company-profile-delete" method="POST" action="{{ route('admin.company-profiles.destroy', $companyProfile) }}">
                    @csrf
                    @method('DELETE')
                </form>
                <x-ui.confirm
                    title="{{ __('Silme işlemini onayla') }}"
                    message="{{ __('Bu işlem geri alınamaz. Devam etmek istiyor musunuz?') }}"
                    confirm-text="{{ __('Evet, sil') }}"
                    cancel-text="{{ __('Vazgeç') }}"
                    variant="danger"
                    form-id="company-profile-delete"
                >
                    <x-slot name="trigger">
                        <x-ui.button variant="danger" type="button">{{ __('Sil') }}</x-ui.button>
                    </x-slot>
                </x-ui.confirm>
            </div>
        </x-ui.card>
    </div>
</x-app-layout>
