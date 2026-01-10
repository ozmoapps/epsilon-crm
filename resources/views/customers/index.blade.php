<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Müşteriler') }}" subtitle="{{ __('Müşteri kayıtlarını hızlıca yönetin.') }}">
            <x-slot name="actions">
                <x-button href="{{ route('customers.create') }}" size="sm">
                    {{ __('Yeni Müşteri') }}
                </x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">
        <x-card>
            <x-slot name="header">{{ __('Filtreler') }}</x-slot>
            <form method="GET" action="{{ route('customers.index') }}" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <x-input-label for="search" :value="__('İsimle arayın')" />
                    <x-input id="search" name="search" type="text" class="mt-1" placeholder="{{ __('İsme göre ara') }}" :value="$search" />
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-button type="submit" size="sm">{{ __('Ara') }}</x-button>
                    <x-button href="{{ route('customers.index') }}" variant="secondary" size="sm">{{ __('Temizle') }}</x-button>
                </div>
            </form>
        </x-card>

        <x-card>
            <x-slot name="header">{{ __('Liste') }}</x-slot>
            <x-ui.table>
                <thead class="bg-slate-50 text-xs font-semibold tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">{{ __('Müşteri') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('İletişim') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Aksiyonlar') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($customers as $customer)
                        <tr class="odd:bg-white even:bg-slate-50 hover:bg-slate-100/60">
                            <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $customer->name }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ $customer->phone ?: __('Telefon yok') }}
                                @if ($customer->email)
                                    · {{ $customer->email }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form id="customer-delete-{{ $customer->id }}" method="POST" action="{{ route('customers.destroy', $customer) }}" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                <x-ui.row-actions
                                    show="{{ route('customers.show', $customer) }}"
                                    edit="{{ route('customers.edit', $customer) }}"
                                    delete="{{ route('customers.destroy', $customer) }}"
                                    delete-form-id="customer-delete-{{ $customer->id }}"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-sm text-slate-500">
                                <div class="flex flex-col items-center gap-2 py-4">
                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                                        <x-icon.info class="h-5 w-5" />
                                    </span>
                                    <div class="text-sm font-semibold text-slate-700">{{ __('Kayıt bulunamadı.') }}</div>
                                    <div class="text-xs text-slate-500">{{ __('Yeni bir müşteri ekleyerek başlayabilirsiniz.') }}</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-card>

        <div>
            {{ $customers->links() }}
        </div>
    </div>
</x-app-layout>
