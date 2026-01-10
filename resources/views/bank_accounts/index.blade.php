<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Banka Hesapları') }}" subtitle="{{ __('Banka hesaplarınızı hızlıca yönetin.') }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('admin.bank-accounts.create') }}" size="sm">
                    {{ __('Yeni Hesap') }}
                </x-ui.button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">
        <x-ui.card>
            <x-slot name="header">{{ __('Filtreler') }}</x-slot>
            <form method="GET" action="{{ route('admin.bank-accounts.index') }}" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <x-input-label for="search" :value="__('İsimle arayın')" />
                    <x-input id="search" name="search" type="text" class="mt-1" placeholder="{{ __('İsme göre ara') }}" :value="$search" />
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-ui.button type="submit" size="sm">{{ __('Ara') }}</x-ui.button>
                    <x-ui.button href="{{ route('admin.bank-accounts.index') }}" variant="secondary" size="sm">{{ __('Temizle') }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card>
            <x-slot name="header">{{ __('Liste') }}</x-slot>
            @php
                $actionItemClass = 'flex w-full items-center gap-2 px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50';
                $actionDangerClass = 'flex w-full items-center gap-2 px-3 py-2 text-sm text-rose-600 transition hover:bg-rose-50';
            @endphp
            <x-ui.table>
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">{{ __('Hesap') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Banka') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Para Birimi') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Aksiyonlar') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($bankAccounts as $bankAccount)
                        <tr class="odd:bg-white even:bg-slate-50 hover:bg-slate-100/60">
                            <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $bankAccount->name }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ $bankAccount->bank_name }}
                                @if ($bankAccount->branch_name)
                                    · {{ $bankAccount->branch_name }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ $bankAccount->currency?->code ?: __('Tanımsız') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <x-ui.dropdown align="right" width="w-44">
                                    <x-slot name="trigger">
                                        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-slate-300 hover:text-slate-900 ui-focus" aria-label="{{ __('İşlemler') }}">
                                            <x-icon.dots class="h-4 w-4" />
                                        </button>
                                    </x-slot>
                                    <x-slot name="content">
                                        <a href="{{ route('admin.bank-accounts.show', $bankAccount) }}" class="{{ $actionItemClass }}">
                                            <x-icon.info class="h-4 w-4 text-sky-600" />
                                            {{ __('Görüntüle') }}
                                        </a>
                                        <a href="{{ route('admin.bank-accounts.edit', $bankAccount) }}" class="{{ $actionItemClass }}">
                                            <x-icon.pencil class="h-4 w-4 text-indigo-600" />
                                            {{ __('Düzenle') }}
                                        </a>
                                        <form id="bank-account-delete-{{ $bankAccount->id }}" method="POST" action="{{ route('admin.bank-accounts.destroy', $bankAccount) }}" class="hidden">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                        <x-ui.confirm
                                            title="{{ __('Silme işlemini onayla') }}"
                                            message="{{ __('Bu işlem geri alınamaz. Devam etmek istiyor musunuz?') }}"
                                            confirm-text="{{ __('Evet, sil') }}"
                                            cancel-text="{{ __('Vazgeç') }}"
                                            variant="danger"
                                            form-id="bank-account-delete-{{ $bankAccount->id }}"
                                        >
                                            <x-slot name="trigger">
                                                <button type="button" class="{{ $actionDangerClass }}">
                                                    <x-icon.trash class="h-4 w-4" />
                                                    {{ __('Sil') }}
                                                </button>
                                            </x-slot>
                                        </x-ui.confirm>
                                    </x-slot>
                                </x-ui.dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">
                                <div class="flex flex-col items-center gap-2 py-4">
                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                                        <x-icon.info class="h-5 w-5" />
                                    </span>
                                    <div class="text-sm font-semibold text-slate-700">{{ __('Kayıt bulunamadı.') }}</div>
                                    <div class="text-xs text-slate-500">{{ __('Yeni bir banka hesabı ekleyerek başlayabilirsiniz.') }}</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>

        <div>
            {{ $bankAccounts->links() }}
        </div>
    </div>
</x-app-layout>
