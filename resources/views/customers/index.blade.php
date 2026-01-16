<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="{{ __('Müşteriler') }}" subtitle="{{ __('Müşteri kayıtlarını hızlıca yönetin.') }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('customers.create') }}" size="sm">
                    {{ __('Yeni Müşteri') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6" x-data="{ 
        selected: [], 
        allIds: @js($customers->pluck('id')->map(fn($id) => (string) $id)),
        toggleAll() {
            this.selected = this.selected.length === this.allIds.length ? [] : [...this.allIds];
        },
        clearSelection() {
            this.selected = [];
        }
    }">
        <x-ui.card>
            <x-slot name="header">{{ __('Filtreler') }}</x-slot>
            <form method="GET" action="{{ route('customers.index') }}" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <x-input-label for="search" :value="__('İsimle arayın')" />
                    <x-input id="search" name="search" type="text" class="mt-1" placeholder="{{ __('İsme göre ara') }}" :value="$search" />
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-ui.button type="submit" size="sm">{{ __('Ara') }}</x-ui.button>
                    <x-ui.button href="{{ route('customers.index') }}" variant="secondary" size="sm">{{ __('Temizle') }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        {{-- Bulk Actions Bar --}}
        <x-ui.bulk-bar x-show="selected.length > 0" x-transition x-cloak>
             <x-slot name="count">
                <span x-text="selected.length"></span>
             </x-slot>
             <x-slot name="actions">
                 <form id="bulk-delete-customer-form" action="{{ route('customers.bulk_destroy') }}" method="POST" class="hidden">
                     @csrf
                     <template x-for="id in selected" :key="id">
                         <input type="hidden" name="ids[]" :value="id">
                     </template>
                 </form>
                 <x-ui.button 
                     type="button" 
                     variant="danger" 
                     size="sm"
                     data-confirm-title="{{ __('Çoklu Silme') }}"
                     data-confirm-message="{{ __('Seçili müşterileri silmek istediğinize emin misiniz? Bu işlem geri alınamaz.') }}"
                     data-confirm-submit="bulk-delete-customer-form"
                     data-confirm-text="{{ __('Sil') }}"
                     data-confirm-cancel-text="{{ __('Vazgeç') }}"
                 >
                     {{ __('Sil') }}
                 </x-ui.button>
             </x-slot>
        </x-ui.bulk-bar>

        <x-ui.card>
            <x-slot name="header">{{ __('Liste') }}</x-slot>
            <div class="overflow-x-auto">
                <x-ui.table>
                    <x-slot name="head">
                        <tr>
                            <th class="w-10 px-4 py-3 text-left">
                                <div class="flex items-center">
                                    <x-checkbox 
                                           @click="toggleAll()" 
                                           x-bind:checked="selected.length > 0 && selected.length === allIds.length"
                                           x-bind:indeterminate="selected.length > 0 && selected.length < allIds.length"
                                    />
                                </div>
                            </th>
                            <th class="px-4 py-3 text-left font-semibold">{{ __('Müşteri') }}</th>
                            <th class="px-4 py-3 text-left font-semibold">{{ __('İletişim') }}</th>
                            <th class="px-4 py-3 text-right w-28 font-semibold">{{ __('Aksiyonlar') }}</th>
                        </tr>
                    </x-slot>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($customers as $customer)
                            <tr class="hover:bg-slate-50/60 transition-colors" :class="selected.includes({{ $customer->id }}) ? 'bg-brand-50/50' : ''">
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex items-center">
                                        <x-checkbox value="{{ $customer->id }}" x-model="selected" />
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900 max-w-0 truncate">{{ $customer->name }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600 max-w-0 truncate">
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
                                        :delete-disabled="$customer->vessels_count > 0"
                                        delete-disabled-title="{{ __('Bu müşteriye bağlı tekneler var.') }}"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6">
                                    <x-ui.empty-state
                                        icon="users"
                                        title="{{ __('Kayıt yok') }}"
                                        description="{{ __('Henüz müşteri eklenmemiş.') }}"
                                    >
                                        <x-slot:actions>
                                            <x-ui.button href="{{ route('customers.create') }}" size="sm">
                                                <x-icon.plus class="w-4 h-4 mr-1.5" />
                                                {{ __('Yeni Müşteri') }}
                                            </x-ui.button>
                                        </x-slot:actions>
                                    </x-ui.empty-state>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-ui.table>
            </div>
            
            @if ($customers->hasPages())
                <div class="bg-slate-50 border-t border-slate-200 p-4 rounded-b-xl">
                    {{ $customers->links() }}
                </div>
            @endif
        </x-ui.card>


    </div>
</x-app-layout>
