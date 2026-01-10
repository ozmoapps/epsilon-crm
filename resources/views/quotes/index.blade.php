<x-app-layout>
    <x-slot name="header">
        <x-partials.page-header title="{{ __('Teklifler') }}" subtitle="{{ __('Teklif süreçlerini takip edin.') }}">
            <x-slot name="actions">
                <x-button href="{{ route('quotes.create') }}">{{ __('Yeni Teklif') }}</x-button>
            </x-slot>
        </x-partials.page-header>
    </x-slot>

    <div class="space-y-6">
        <x-partials.filter-card>
            <x-slot name="actions">
                <x-ui.dropdown align="right" width="w-64">
                    <x-slot name="trigger">
                        <button class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none transition duration-150 ease-in-out">
                            <span>{{ __('Görünüm') }}</span>
                            <x-icon.chevron-down class="ml-1 h-4 w-4" />
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="px-4 py-2 text-xs text-gray-400 font-bold text-center">
                            {{ __('Kayıtlı Görünümler') }}
                        </div>
                        @forelse($savedViews as $view)
                            <a href="{{ route('quotes.index', $view->query) }}" class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out flex justify-between items-center">
                                <span>{{ $view->name }}</span>
                                @if($view->is_shared)
                                    <span class="px-1.5 py-0.5 text-[10px] rounded bg-blue-100 text-blue-800">{{ __('Ortak') }}</span>
                                @endif
                            </a>
                        @empty
                            <div class="px-4 py-2 text-sm text-gray-500 text-center italic">
                                {{ __('Görünüm yok') }}
                            </div>
                        @endforelse
                        <div class="border-t border-gray-100"></div>
                        <a href="{{ route('saved-views.index', ['scope' => 'quotes']) }}" class="block px-4 py-2 text-sm leading-5 text-indigo-600 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out text-center font-medium">
                            {{ __('Görünümleri Yönet') }}
                        </a>
                    </x-slot>
                </x-ui.dropdown>

                <button 
                    type="button" 
                    x-data=""
                    x-on:click.prevent="$dispatch('open-modal', 'save-view-modal')"
                    class="text-sm text-indigo-600 hover:text-indigo-900 font-medium ml-2"
                >
                    {{ __('Görünümü Kaydet') }}
                </button>
            </x-slot>

            <x-slot name="filters" class="!grid-cols-1 md:!grid-cols-3 lg:!grid-cols-6 gap-4">
                <form method="GET" action="{{ route('quotes.index') }}" class="contents">
                    {{-- Search --}}
                    <div class="col-span-1 md:col-span-2 lg:col-span-1">
                        <x-input name="search" type="text" placeholder="{{ __('Teklif No / Başlık') }}" :value="$search" class="w-full" />
                    </div>
                    
                    {{-- Status --}}
                    <div class="col-span-1">
                        <x-select name="status" class="w-full">
                            <option value="">{{ __('Durum: Tümü') }}</option>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    
                    {{-- Customer --}}
                    <div class="col-span-1">
                        <x-select name="customer_id" class="w-full">
                            <option value="">{{ __('Müşteri: Tümü') }}</option>
                            @foreach ($customers as $msg)
                                <option value="{{ $msg->id }}" @selected($customerId == $msg->id)>{{ $msg->name }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    
                    {{-- Vessel --}}
                    <div class="col-span-1">
                        <x-select name="vessel_id" class="w-full">
                            <option value="">{{ __('Tekne: Tümü') }}</option>
                            @foreach ($vessels as $vsl)
                                <option value="{{ $vsl->id }}" @selected($vesselId == $vsl->id)>{{ $vsl->name }}</option>
                            @endforeach
                        </x-select>
                    </div>

                    {{-- Currency --}}
                    <div class="col-span-1">
                        <x-select name="currency" class="w-full">
                            <option value="">{{ __('Kur: Tümü') }}</option>
                            @foreach ($currencies as $curr)
                                <option value="{{ $curr->code }}" @selected($currency == $curr->code)>{{ $curr->code }}</option>
                            @endforeach
                        </x-select>
                    </div>

                    {{-- Date Range --}}
                    <div class="col-span-1 flex space-x-2">
                        <x-input name="date_from" type="date" placeholder="Başlangıç" :value="$dateFrom" class="w-full px-2 text-xs" />
                        <x-input name="date_to" type="date" placeholder="Bitiş" :value="$dateTo" class="w-full px-2 text-xs" />
                    </div>

                    {{-- Amount Range --}}
                    <div class="col-span-1 flex space-x-2">
                        <x-input name="total_min" type="number" placeholder="Min" :value="$totalMin" step="0.01" class="w-full px-2 text-xs" />
                        <x-input name="total_max" type="number" placeholder="Max" :value="$totalMax" step="0.01" class="w-full px-2 text-xs" />
                    </div>

                    {{-- Actions --}}
                    <div class="col-span-1 md:col-span-3 lg:col-span-5 flex items-center justify-end space-x-2">
                        <x-button type="submit" class="w-full sm:w-auto justify-center">
                            <x-icon.search class="w-4 h-4 mr-2" />
                            {{ __('Filtrele') }}
                        </x-button>
                        <x-button href="{{ route('quotes.index') }}" variant="secondary" class="w-full sm:w-auto justify-center">
                            <x-icon.x class="w-4 h-4 mr-2" />
                            {{ __('Temizle') }}
                        </x-button>
                    </div>
                </form>
            </x-slot>
        </x-partials.filter-card>

        {{-- Save View Modal --}}
        <x-modal name="save-view-modal" :show="false" focusable>
            <form method="POST" action="{{ route('saved-views.store') }}" class="p-6">
                @csrf
                <h2 class="text-lg font-medium text-gray-900">
                    {{ __('Görünümü Kaydet') }}
                </h2>
                <p class="mt-1 text-sm text-gray-600">
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
                        <input id="is_shared" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" name="is_shared" value="1">
                        <span class="ml-2 text-sm text-gray-600">{{ __('Ekip ile paylaş') }}</span>
                    </label>
                </div>

                <div class="mt-6 flex justify-end">
                    <x-button type="button" variant="secondary" x-on:click="$dispatch('close-modal', 'save-view-modal')">
                        {{ __('İptal') }}
                    </x-button>
                    <x-button class="ml-3">
                        {{ __('Kaydet') }}
                    </x-button>
                </div>
            </form>
        </x-modal>

        <x-card class="!p-0 overflow-hidden">
            @php
                $statusVariants = [
                    'draft' => 'draft',
                    'sent' => 'neutral',
                    'accepted' => 'confirmed',
                    'converted' => 'success',
                    'cancelled' => 'cancelled',
                ];
            @endphp
            <x-ui.table>
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold tracking-wide text-slate-500">{{ __('Teklif No') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold tracking-wide text-slate-500">{{ __('Başlık') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold tracking-wide text-slate-500">{{ __('Müşteri') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold tracking-wide text-slate-500">{{ __('Tekne') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold tracking-wide text-slate-500">{{ __('Durum') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold tracking-wide text-slate-500 w-32">{{ __('Aksiyonlar') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($quotes as $quote)
                        @php
                            $isLocked = $quote->isLocked();
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors">
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
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <div class="h-12 w-12 rounded-full bg-indigo-50 flex items-center justify-center">
                                        <x-icon.inbox class="h-6 w-6 text-indigo-600" />
                                    </div>
                                    <div class="text-center">
                                        <p class="text-sm font-medium text-gray-900">{{ __('Kayıt bulunamadı') }}</p>
                                        <p class="mt-1 text-sm text-gray-500">{{ __('Arama kriterlerinize uygun teklif bulunmuyor.') }}</p>
                                        <div class="mt-4">
                                            <x-button href="{{ route('quotes.create') }}" size="sm">{{ __('Yeni Teklif Oluştur') }}</x-button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-card>

        <div class="mt-4">
            {{ $quotes->links() }}
        </div>
    </div>
</x-app-layout>
