<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="{{ __('Kasa & Bankalar') }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('bank-accounts.create') }}" variant="primary">
                    <x-icon.plus class="mr-2 h-5 w-5" />
                    {{ __('Yeni Hesap') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-ui.card class="overflow-hidden">
                <div class="overflow-x-auto">
                    <x-ui.table>
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('Hesap Adı') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('Tip') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('Banka / Şube') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('Bakiye') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('Durum') }}</th>
                                <th class="relative px-6 py-3"><span class="sr-only">{{ __('İşlemler') }}</span></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            @forelse($accounts as $account)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                                @if($account->type === 'cash')
                                                    <x-icon.cash class="h-6 w-6" />
                                                @else
                                                    <x-icon.bank class="h-6 w-6" />
                                                @endif
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-slate-900">
                                                    <a href="{{ route('bank-accounts.show', $account) }}" class="hover:text-brand-600 hover:underline">
                                                        {{ $account->name }}
                                                    </a>
                                                </div>
                                                @if($account->type === 'bank' && $account->iban)
                                                    <div class="text-xs text-slate-500">{{ $account->iban }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $account->type === 'cash' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">
                                            {{ $account->type === 'cash' ? 'Kasa' : 'Banka' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                        @if($account->type === 'bank')
                                            {{ $account->bank_name ?? '-' }} 
                                            @if($account->branch_name) / {{ $account->branch_name }} @endif
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-slate-900">
                                        {{ number_format($account->balance, 2) }} {{ $account->currency->code ?? '' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 {{ $account->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-800' }}">
                                            {{ $account->is_active ? 'Aktif' : 'Pasif' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <a href="{{ route('bank-accounts.show', $account) }}" class="text-slate-400 hover:text-brand-600 transition">
                                                <x-icon.eye class="h-5 w-5" />
                                            </a>
                                            <a href="{{ route('bank-accounts.edit', $account) }}" class="text-slate-400 hover:text-amber-600 transition">
                                                <x-icon.pencil class="h-5 w-5" />
                                            </a>
                                            <form action="{{ route('bank-accounts.destroy', $account) }}" method="POST" onsubmit="return confirm('Silmek istediğinize emin misiniz?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-slate-400 hover:text-rose-600 transition">
                                                    <x-icon.trash class="h-5 w-5" />
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-slate-500">
                                        {{ __('Kayıtlı hesap bulunamadı.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </x-ui.table>
                </div>
                <div class="px-4 py-3 border-t border-slate-200">
                    {{ $accounts->links() }}
                </div>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
