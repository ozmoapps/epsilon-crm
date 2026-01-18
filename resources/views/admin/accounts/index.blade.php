<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Hesaplar') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-ui.card>
                <div class="overflow-x-auto">
                    <div class="inline-block min-w-full align-middle">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead>
                                <tr>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('ID') }}</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('Hesap Sahibi') }}</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('Paket') }}</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('Firma Kullanımı') }}</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('Kullanıcı (Seat)') }}</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('Durum') }}</th>
                                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                        <span class="sr-only">{{ __('İşlemler') }}</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @forelse($accounts as $account)
                                    <tr>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                            #{{ $account->id }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-slate-900">
                                            {{ $account->owner->name ?? '-' }}
                                            <div class="text-xs text-slate-500">{{ $account->owner->email ?? '' }}</div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                            <x-ui.badge variant="neutral">
                                                {{ $account->plan_name }}
                                            </x-ui.badge>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                            {{ $entitlements->accountTenantUsage($account) }} / 
                                            {{ $entitlements->accountTenantLimit($account) ?? '∞' }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                            {{ $entitlements->accountSeatUsage($account) }} / 
                                            {{ $entitlements->accountSeatLimit($account) ?? '∞' }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                            @if($account->status === 'active')
                                                <x-ui.badge variant="success">{{ __('Aktif') }}</x-ui.badge>
                                            @else
                                                <x-ui.badge variant="neutral">{{ $account->status }}</x-ui.badge>
                                            @endif
                                        </td>
                                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                            <a href="{{ route('admin.accounts.show', $account) }}" class="text-brand-600 hover:text-brand-900">
                                                {{ __('Detay') }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-3 py-4 text-sm text-slate-500 text-center">
                                            {{ __('Kayıt bulunamadı.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="mt-4">
                    {{ $accounts->links() }}
                </div>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
