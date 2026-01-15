<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="{{ __('Teklifler') }}" subtitle="{{ __('Teklif süreçlerini takip edin.') }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('quotes.create') }}">{{ __('Yeni Teklif') }}</x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6" x-data="{ 
        selected: [], 
        allIds: @js($quotes->pluck('id')),
        toggleAll() {
            this.selected = this.selected.length === this.allIds.length ? [] : [...this.allIds];
        },
        clearSelection() {
            this.selected = [];
        }
    }">
        <x-ui.filter-bar action="{{ route('quotes.index') }}" method="GET">
            <x-slot:left>
                <x-input name="search" type="text" placeholder="{{ __('Teklif No / Başlık') }}" :value="$search" class="w-full" />
            </x-slot:left>

            <x-slot:right>
                {{-- Status --}}
                <x-select name="status" class="w-full sm:w-40">
                    <option value="">{{ __('Durum: Tümü') }}</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </x-select>
                
                {{-- Customer --}}
                <x-select name="customer_id" class="w-full sm:w-48">
                    <option value="">{{ __('Müşteri: Tümü') }}</option>
                    @foreach ($customers as $msg)
                        <option value="{{ $msg->id }}" @selected($customerId == $msg->id)>{{ $msg->name }}</option>
                    @endforeach
                </x-select>
                
                {{-- Vessel --}}
                <x-select name="vessel_id" class="w-full sm:w-48">
                    <option value="">{{ __('Tekne: Tümü') }}</option>
                    @foreach ($vessels as $vsl)
                        <option value="{{ $vsl->id }}" @selected($vesselId == $vsl->id)>{{ $vsl->name }}</option>
                    @endforeach
                </x-select>

                {{-- Currency --}}
                <x-select name="currency" class="w-full sm:w-32">
                    <option value="">{{ __('Kur: Tümü') }}</option>
                    @foreach ($currencies as $curr)
                        <option value="{{ $curr->code }}" @selected($currency == $curr->code)>{{ $curr->code }}</option>
                    @endforeach
                </x-select>

                {{-- Date Range --}}
                <div class="flex space-x-2 w-full sm:w-auto">
                    <x-input name="date_from" type="date" placeholder="Başla" :value="$dateFrom" class="w-full sm:w-32 px-2 text-xs" />
                    <x-input name="date_to" type="date" placeholder="Bitiş" :value="$dateTo" class="w-full sm:w-32 px-2 text-xs" />
                </div>
            </x-slot:right>

            <x-slot:actions>
                {{-- Saved Views --}}
                <x-ui.dropdown align="right" width="w-64">
                    <x-slot name="trigger">
                        {{-- Standardize trigger: Secondary Button with Icon --}}
                        <x-ui.button variant="secondary" class="mr-2 px-2 shadow-soft">
                            <x-icon.bookmark class="h-4 w-4 text-slate-500" />
                        </x-ui.button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="px-4 py-2 text-xs text-slate-400 font-bold text-center">
                            {{ __('Kayıtlı Görünümler') }}
                        </div>
                        @forelse($savedViews as $view)
                            <a href="{{ route('quotes.index', $view->query) }}" class="block px-4 py-2 text-sm leading-5 text-slate-700 hover:bg-slate-100 focus:outline-none focus:bg-slate-100 transition duration-150 ease-in-out flex justify-between items-center">
                                <span>{{ $view->name }}</span>
                                @if($view->is_shared)
                                    <x-ui.badge variant="info">{{ __('Ortak') }}</x-ui.badge>
                                @endif
                            </a>
                        @empty
                            <div class="px-4 py-2 text-sm text-slate-500 text-center italic">
                                {{ __('Görünüm yok') }}
                            </div>
                        @endforelse
                        <div class="border-t border-slate-100"></div>
                        <a href="{{ route('saved-views.index', ['scope' => 'quotes']) }}" class="block px-4 py-2 text-sm leading-5 text-brand-600 hover:bg-slate-100 focus:outline-none focus:bg-slate-100 transition duration-150 ease-in-out text-center font-medium">
                            {{ __('Görünümleri Yönet') }}
                        </a>
                    </x-slot>
                </x-ui.dropdown>

                <x-ui.button 
                    type="button" 
                    variant="ghost"
                    x-data=""
                    x-on:click.prevent="$dispatch('open-modal', 'save-view-modal')"
                    class="text-slate-400 hover:text-brand-600 transition-colors mr-2 px-2"
                    title="{{ __('Görünümü Kaydet') }}"
                >
                    <x-icon.save class="h-4 w-4" />
                </x-ui.button>

                {{-- Filter Actions --}}
                <x-ui.button type="submit" variant="primary">
                    {{ __('Filtrele') }}
                </x-ui.button>
                <x-ui.button href="{{ route('quotes.index') }}" variant="secondary">
                    {{ __('Temizle') }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.filter-bar>

        {{-- Bulk Actions Bar --}}
        <x-ui.bulk-bar x-show="selected.length > 0" x-transition x-cloak class="!top-24 !bottom-auto">
             <x-slot name="count">
                <span x-text="selected.length"></span>
             </x-slot>
             <x-slot name="actions">
                 <form id="bulk-delete-form" action="{{ route('quotes.bulk_destroy') }}" method="POST" class="hidden">
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
                     data-confirm-message="{{ __('Seçili teklifleri silmek istediğinize emin misiniz? Bu işlem geri alınamaz.') }}"
                     data-confirm-submit="bulk-delete-form"
                     data-confirm-text="{{ __('Evet, Sil') }}"
                     data-confirm-cancel-text="{{ __('Vazgeç') }}"
                 >
                     {{ __('Sil') }}
                 </x-ui.button>
             </x-slot>
        </x-ui.bulk-bar>

        {{-- Save View Modal --}}
        <x-modal name="save-view-modal" :show="false" focusable>
            <form method="POST" action="{{ route('saved-views.store') }}">
                @csrf
                <h2 class="text-lg font-medium text-slate-900">
                    {{ __('Görünümü Kaydet') }}
                </h2>
                <p class="mt-1 text-sm text-slate-600">
                    {{ __('Mevcut filtrelerinizi daha sonra hızlıca kullanmak için kaydedin.') }}
                </p>

                <input type="hidden" name="scope" value="quotes">
                <input type="hidden" name="query" value="{{ json_encode(request()->except(['page'])) }}">

                <div class="mt-6">
                    <x-input-label for="view_name" value="{{ __('Görünüm Adı') }}" />
                    <x-input id="view_name" name="name" type="text" class="mt-1 block w-3/4" required placeholder="Örn: Yüksek Tutar - EUR" />
                </div>

                <div class="mt-4 block">
                    <label for="is_shared" class="inline-flex items-center">
                        <input id="is_shared" type="checkbox" class="rounded border-slate-300 text-brand-600 focus:border-brand-300 focus:ring focus:ring-brand-200 focus:ring-opacity-50" name="is_shared" value="1">
                        <span class="ml-2 text-sm text-slate-600">{{ __('Ekip ile paylaş') }}</span>
                    </label>
                </div>

                <div class="mt-6 flex justify-end">
                    <x-ui.button type="button" variant="secondary" x-on:click="$dispatch('close-modal', 'save-view-modal')">
                        {{ __('İptal') }}
                    </x-ui.button>
                    <x-ui.button class="ml-3">
                        {{ __('Kaydet') }}
                    </x-ui.button>
                </div>
            </form>
        </x-modal>

        {{-- Loading skeleton demo: /quotes?loading=1 --}}
        @if(request('loading'))
            <x-ui.table-skeleton :rows="6" :cols="6" density="compact" />
        @else
            <x-ui.card class="!p-0 overflow-hidden">
                @php
                    $statusVariants = [
                        'draft' => 'neutral',
                        'sent' => 'info',
                        'accepted' => 'success',
                        'converted' => 'success',
                        'cancelled' => 'danger',
                    ];
                @endphp
                <x-ui.table density="compact">
                    <x-slot name="head">
                        <tr>
                            <th class="w-10 px-6 py-3 text-left">
                                <div class="flex items-center">
                                    <input type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" 
                                           @click="toggleAll()" 
                                           :checked="selected.length > 0 && selected.length === allIds.length"
                                           :indeterminate="selected.length > 0 && selected.length < allIds.length"
                                    >
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left tracking-wide">{{ __('Teklif No') }}</th>
                            <th class="px-6 py-3 text-left tracking-wide">{{ __('Başlık') }}</th>
                            <th class="px-6 py-3 text-left tracking-wide">{{ __('Müşteri') }}</th>
                            <th class="px-6 py-3 text-left tracking-wide">{{ __('Tekne') }}</th>
                            <th class="px-6 py-3 text-left tracking-wide">{{ __('Durum') }}</th>
                            <th class="px-6 py-3 text-right tracking-wide w-32">{{ __('Aksiyonlar') }}</th>
                        </tr>
                    </x-slot>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($quotes as $quote)
                            @php
                                $isLocked = $quote->isLocked();
                            @endphp
                            <tr class="group" :class="selected.includes({{ $quote->id }}) ? 'bg-brand-50/50' : ''">
                                <td class="px-6 py-3 text-sm">
                                    <div class="flex items-center">
                                        <input type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" value="{{ $quote->id }}" x-model="selected">
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-sm font-medium text-slate-900 max-w-0 truncate">{{ $quote->quote_no }}</td>
                                <td class="px-6 py-3 text-sm text-slate-600 max-w-0 truncate">{{ $quote->title }}</td>
                                <td class="px-6 py-3 text-sm text-slate-600 max-w-0 truncate">{{ $quote->customer?->name ?? 'Müşteri yok' }}</td>
                                <td class="px-6 py-3 text-sm text-slate-600 max-w-0 truncate">{{ $quote->vessel?->name ?? '-' }}</td>
                                <td class="px-6 py-3 whitespace-nowrap">
                                    @if ($quote->status)
                                        <x-ui.badge :variant="$statusVariants[$quote->status] ?? 'neutral'">
                                            {{ $quote->status_label }}
                                        </x-ui.badge>
                                    @endif
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium">
                                    <form id="quote-delete-{{ $quote->id }}" method="POST" action="{{ route('quotes.destroy', $quote) }}" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                    <x-ui.row-actions
                                        show="{{ route('quotes.show', $quote) }}"
                                        edit="{{ route('quotes.edit', $quote) }}"
                                        delete="{{ route('quotes.destroy', $quote) }}"
                                        delete-form-id="quote-delete-{{ $quote->id }}"
                                        :edit-disabled="$isLocked"
                                        :delete-disabled="$isLocked"
                                        edit-disabled-title="{{ __('Bu teklif siparişe dönüştürüldüğü için düzenlenemez.') }}"
                                        delete-disabled-title="{{ __('Bu teklifin bağlı siparişi olduğu için silinemez.') }}"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6">
                                    <x-ui.empty-state
                                        title="{{ __('Kayıt bulunamadı') }}"
                                        description="{{ __('Aradığınız kriterlere uygun teklif bulunamadı.') }}"
                                    >
                                        <x-slot:actions>
                                            <x-ui.button href="{{ route('quotes.create') }}" size="sm">
                                                <x-icon.plus class="w-4 h-4 mr-1.5" />
                                                {{ __('Yeni Teklif') }}
                                            </x-ui.button>
                                        </x-slot:actions>
                                    </x-ui.empty-state>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-ui.table>
            </x-ui.card>
        @endif

        <div class="bg-slate-50 border-t border-slate-200 p-4 rounded-b-xl">
            {{ $quotes->links() }}
        </div>
    </div>
</x-app-layout>
