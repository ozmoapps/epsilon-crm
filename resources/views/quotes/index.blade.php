<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            title="{{ __('Teklifler') }}"
            subtitle="{{ __('Müşterilerinize hızlıca profesyonel teklif hazırlayın, PDF/önizleme ile paylaşın.') }}"
        >
            <x-slot name="actions">
                <x-ui.button href="{{ route('quotes.create') }}">
                    <x-icon.plus class="w-4 h-4 mr-2" />
                    {{ __('Yeni Teklif') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @php
        $hasAnyFilters = request()->anyFilled(['search', 'status', 'customer_id', 'vessel_id', 'currency', 'date_from', 'date_to', 'total_min', 'total_max']);
        $hasAdvancedFilters = request()->anyFilled(['customer_id', 'vessel_id', 'currency', 'date_from', 'date_to', 'total_min', 'total_max']);
    @endphp

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
        {{-- Filter Bar --}}
        <x-ui.card class="!p-4 sm:!p-5">
            <form action="{{ route('quotes.index') }}" method="GET">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 items-start">
                    {{-- Always Visible: Search --}}
                    <div class="sm:col-span-2 lg:col-span-4">
                        <x-input-label for="search" value="{{ __('Arama') }}" class="sr-only" />
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <x-icon.search class="h-4 w-4 text-slate-400" />
                            </div>
                            <x-input
                                id="search"
                                name="search"
                                type="text"
                                placeholder="{{ __('Teklif No / Başlık') }}"
                                :value="$search"
                                class="w-full pl-9"
                            />
                        </div>
                    </div>

                    {{-- Always Visible: Status --}}
                    <div class="sm:col-span-1 lg:col-span-3">
                        <x-input-label for="status" value="{{ __('Durum') }}" class="sr-only" />
                        <x-select id="status" name="status" class="w-full">
                            <option value="">{{ __('Durum: Tümü') }}</option>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                            @endforeach
                        </x-select>
                    </div>

                    {{-- Actions (Desktop) --}}
                    <div class="hidden lg:flex lg:col-span-5 justify-end items-center gap-2">
                        <div class="flex items-center gap-2">
                            <x-ui.button type="submit" variant="primary">
                                {{ __('Filtrele') }}
                            </x-ui.button>

                            @if($hasAnyFilters)
                                <x-ui.button href="{{ route('quotes.index') }}" variant="secondary">
                                    {{ __('Temizle') }}
                                </x-ui.button>
                            @endif
                        </div>

                        <div class="h-6 w-px bg-slate-200/70 mx-2"></div>

                        {{-- Saved Views Dropdown --}}
                        <x-ui.dropdown align="right" width="w-64">
                            <x-slot name="trigger">
                                <x-ui.button variant="secondary" size="sm" class="flex items-center">
                                    <x-icon.bookmark class="w-4 h-4 mr-1.5 text-slate-500" />
                                    <span>{{ __('Görünümler') }}</span>
                                </x-ui.button>
                            </x-slot>

                            <x-slot name="content">
                                <div class="px-4 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider text-center">
                                    {{ __('Kayıtlı Görünümler') }}
                                </div>

                                @forelse($savedViews as $view)
                                    <a
                                        href="{{ route('quotes.index', $view->query) }}"
                                        class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 flex justify-between items-center group"
                                    >
                                        <span class="group-hover:text-brand-600 transition-colors">
                                            {{ $view->name }}
                                        </span>

                                        @if($view->is_shared)
                                            <x-ui.badge variant="info" size="xs">{{ __('Ortak') }}</x-ui.badge>
                                        @endif
                                    </a>
                                @empty
                                    <div class="px-4 py-2 text-sm text-slate-500 text-center italic">
                                        {{ __('Görünüm yok') }}
                                    </div>
                                @endforelse

                                <div class="border-t border-slate-100 my-1"></div>

                                <a
                                    href="{{ route('saved-views.index', ['scope' => 'quotes']) }}"
                                    class="block px-4 py-2 text-sm text-brand-600 hover:bg-slate-50 text-center font-medium"
                                >
                                    {{ __('Yönet') }}
                                </a>

                                <div class="border-t border-slate-100"></div>

                                <button
                                    type="button"
                                    x-on:click.prevent="$dispatch('open-modal', 'save-view-modal')"
                                    class="block w-full px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 text-center"
                                >
                                    {{ __('Bu Filtreyi Kaydet') }}
                                </button>
                            </x-slot>
                        </x-ui.dropdown>
                    </div>

                    {{-- Advanced Filters (Details) --}}
                    <details class="col-span-1 sm:col-span-2 lg:col-span-12 group">
                        <summary class="inline-flex items-center gap-2 text-sm text-slate-600 font-medium cursor-pointer hover:text-slate-900 select-none transition-colors">
                            <span>{{ __('Gelişmiş Filtreler') }}</span>

                            @if($hasAdvancedFilters)
                                <x-ui.badge variant="neutral" size="xs">
                                    {{ __('Aktif') }}
                                </x-ui.badge>
                            @endif

                            <x-icon.chevron-down class="w-4 h-4 transition-transform group-open:rotate-180 text-slate-400" />
                        </summary>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-3 pt-4 border-t border-slate-100/80">
                            {{-- Customer --}}
                            <div class="space-y-1">
                                <x-input-label for="customer_id" value="{{ __('Müşteri') }}" />
                                <x-select id="customer_id" name="customer_id" class="w-full text-sm">
                                    <option value="">{{ __('Tümü') }}</option>
                                    @foreach ($customers as $msg)
                                        <option value="{{ $msg->id }}" @selected($customerId == $msg->id)>{{ $msg->name }}</option>
                                    @endforeach
                                </x-select>
                            </div>

                            {{-- Vessel --}}
                            <div class="space-y-1">
                                <x-input-label for="vessel_id" value="{{ __('Tekne') }}" />
                                <x-select id="vessel_id" name="vessel_id" class="w-full text-sm">
                                    <option value="">{{ __('Tümü') }}</option>
                                    @foreach ($vessels as $vsl)
                                        <option value="{{ $vsl->id }}" @selected($vesselId == $vsl->id)>{{ $vsl->name }}</option>
                                    @endforeach
                                </x-select>
                            </div>

                            {{-- Currency --}}
                            <div class="space-y-1">
                                <x-input-label for="currency" value="{{ __('Para Birimi') }}" />
                                <x-select id="currency" name="currency" class="w-full text-sm">
                                    <option value="">{{ __('Tümü') }}</option>
                                    @foreach ($currencies as $curr)
                                        <option value="{{ $curr->code }}" @selected($currency == $curr->code)>{{ $curr->code }}</option>
                                    @endforeach
                                </x-select>
                            </div>

                            {{-- Date Range --}}
                            <div class="space-y-1">
                                <x-input-label value="{{ __('Tarih Aralığı') }}" />
                                <div class="flex items-center gap-2">
                                    <x-input name="date_from" type="date" :value="$dateFrom" class="w-full text-xs" />
                                    <span class="text-slate-400">-</span>
                                    <x-input name="date_to" type="date" :value="$dateTo" class="w-full text-xs" />
                                </div>
                            </div>
                        </div>
                    </details>

                    {{-- Actions (Mobile/Tablet Only) --}}
                    <div class="col-span-1 sm:col-span-2 lg:hidden flex justify-end items-center gap-2 mt-2">
                        <x-ui.button type="submit" variant="primary" class="w-full sm:w-auto">
                            {{ __('Filtrele') }}
                        </x-ui.button>
                        @if($hasAnyFilters)
                            <x-ui.button href="{{ route('quotes.index') }}" variant="secondary" class="w-full sm:w-auto">
                                {{ __('Temizle') }}
                            </x-ui.button>
                        @endif
                    </div>
                </div>
            </form>
        </x-ui.card>

        {{-- Bulk Actions Bar --}}
        <x-ui.bulk-bar x-show="selected.length > 0" x-transition x-cloak class="!top-24 !bottom-auto shadow-lg border border-slate-200">
            <x-slot name="count">
                <span x-text="selected.length" class="font-bold text-slate-900"></span>
                <span class="text-slate-600 ml-1">{{ __('öğe seçildi') }}</span>
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
                    <x-icon.trash class="w-4 h-4 mr-1.5" />
                    {{ __('Sil') }}
                </x-ui.button>
            </x-slot>
        </x-ui.bulk-bar>

        {{-- Save View Modal --}}
        <x-modal name="save-view-modal" :show="false" focusable>
            <form method="POST" action="{{ route('saved-views.store') }}" class="p-6">
                @csrf

                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-medium text-slate-900">
                        {{ __('Görünümü Kaydet') }}
                    </h2>

                    <button type="button" x-on:click="$dispatch('close-modal', 'save-view-modal')" class="text-slate-400 hover:text-slate-500">
                        <x-icon.x class="w-5 h-5" />
                    </button>
                </div>

                <p class="text-sm text-slate-600 mb-6">
                    {{ __('Mevcut filtrelerinizi daha sonra hızlıca kullanmak için kaydedin.') }}
                </p>

                <input type="hidden" name="scope" value="quotes">
                <input type="hidden" name="query" value="{{ json_encode(request()->except(['page'])) }}">

                <div class="space-y-4">
                    <div>
                        <x-input-label for="view_name" value="{{ __('Görünüm Adı') }}" />
                        <x-input
                            id="view_name"
                            name="name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                            placeholder="Örn: Bekleyen Teklifler"
                            autofocus
                        />
                    </div>

                    <div class="flex items-center">
                        <input id="is_shared" type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" name="is_shared" value="1">
                        <label for="is_shared" class="ml-2 text-sm text-slate-700 select-none cursor-pointer">
                            {{ __('Tüm ekiple paylaş') }}
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-ui.button type="button" variant="secondary" x-on:click="$dispatch('close-modal', 'save-view-modal')">
                        {{ __('İptal') }}
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        {{ __('Kaydet') }}
                    </x-ui.button>
                </div>
            </form>
        </x-modal>

        {{-- Main Content --}}
        @if(request('loading'))
            <x-ui.table-skeleton :rows="6" :cols="6" density="compact" />
        @else
            <x-ui.card class="!p-0 overflow-hidden border border-slate-200 shadow-sm">
                @php
                    $statusVariants = [
                        'draft' => 'neutral',
                        'sent' => 'info',
                        'accepted' => 'success',
                        'converted' => 'success',
                        'cancelled' => 'danger',
                    ];
                @endphp

                <x-ui.table density="compact" class="w-full min-w-0 table-fixed">
                    <x-slot name="head">
                        <tr>
                            <th class="w-8 px-4 py-3 text-left bg-slate-50/70 border-b border-slate-100">
                                <div class="flex items-center">
                                    <input
                                        type="checkbox"
                                        class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                        @click="toggleAll()"
                                        x-bind:checked="selected.length > 0 && selected.length === allIds.length"
                                        x-bind:indeterminate="selected.length > 0 && selected.length < allIds.length"
                                    >
                                </div>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 bg-slate-50/70 border-b border-slate-100">{{ __('Teklif No') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 bg-slate-50/70 border-b border-slate-100">{{ __('Başlık') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 bg-slate-50/70 border-b border-slate-100">{{ __('Müşteri') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 bg-slate-50/70 border-b border-slate-100">{{ __('Tekne') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 bg-slate-50/70 border-b border-slate-100">{{ __('Toplam') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 bg-slate-50/70 border-b border-slate-100">{{ __('Durum') }}</th>
                            <th class="px-2 py-3 text-right text-xs font-semibold text-slate-500 bg-slate-50/70 border-b border-slate-100 w-20">{{ __('Aksiyonlar') }}</th>
                        </tr>
                    </x-slot>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($quotes as $quote)
                            @php
                                $isLocked = $quote->isLocked();
                            @endphp

                            <tr class="group hover:bg-slate-50/60 transition-colors" :class="selected.includes({{ $quote->id }}) ? 'bg-brand-50/50' : ''">
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex items-center">
                                        <input type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" value="{{ $quote->id }}" x-model="selected">
                                    </div>
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    <a href="{{ route('quotes.show', $quote) }}" class="text-sm font-semibold text-slate-900 hover:text-brand-600 transition-colors">
                                        {{ $quote->quote_no }}
                                    </a>
                                </td>

                                <td class="px-4 py-3 text-sm text-slate-600 max-w-[12rem] truncate" title="{{ $quote->title }}">
                                    {{ $quote->title }}
                                </td>

                                <td class="px-4 py-3 text-sm text-slate-600 max-w-[10rem] truncate">
                                    {{ $quote->customer?->name ?? '—' }}
                                </td>

                                <td class="px-4 py-3 text-sm text-slate-500 max-w-[10rem] truncate">
                                    {{ $quote->vessel?->name ?? '—' }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-600 tabular-nums">
                                    {{ $quote->formatted_grand_total }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($quote->status)
                                        <x-ui.badge :variant="$statusVariants[$quote->status] ?? 'neutral'">
                                            {{ $quote->status_label }}
                                        </x-ui.badge>
                                    @endif
                                </td>

                                <td class="px-2 py-3 whitespace-nowrap text-right text-sm font-medium w-20">
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
                                <td colspan="8" class="px-6 py-12">
                                    @if($hasAnyFilters)
                                        <x-ui.empty-state
                                            title="{{ __('Sonuç bulunamadı') }}"
                                            description="{{ __('Filtreleri sadeleştirip yeniden deneyin veya yeni bir teklif oluşturun.') }}"
                                            icon="search"
                                        >
                                            <x-slot:actions>
                                                <x-ui.button href="{{ route('quotes.index') }}" variant="secondary">
                                                    {{ __('Filtreleri Temizle') }}
                                                </x-ui.button>

                                                <x-ui.button href="{{ route('quotes.create') }}" variant="primary" class="ml-2">
                                                    <x-icon.plus class="w-4 h-4 mr-1.5" />
                                                    {{ __('Yeni Teklif') }}
                                                </x-ui.button>
                                            </x-slot:actions>
                                        </x-ui.empty-state>
                                    @else
                                        <x-ui.empty-state
                                            title="{{ __('Henüz teklif oluşturulmadı') }}"
                                            description="{{ __('İlk teklifinizi oluşturun; önizleme ve PDF ile müşterinize dakikalar içinde gönderebilirsiniz.') }}"
                                            icon="document-add"
                                        >
                                            <x-slot:actions>
                                                <x-ui.button href="{{ route('quotes.create') }}" size="md">
                                                    <x-icon.plus class="w-4 h-4 mr-1.5" />
                                                    {{ __('Yeni Teklif') }}
                                                </x-ui.button>
                                            </x-slot:actions>
                                        </x-ui.empty-state>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-ui.table>
            </x-ui.card>
        @endif

        <div class="mt-4">
            {{ $quotes->links() }}
        </div>
    </div>
</x-app-layout>
