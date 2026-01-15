<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            title="{{ __('Satış Siparişleri') }}"
            subtitle="{{ __('Tüm satış siparişlerini görüntüleyin.') }}"
        >
            <x-slot name="actions">
                <x-ui.button href="{{ route('sales-orders.create') }}">
                    {{ __('Yeni Satış Siparişi') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <x-partials.filter-card>
            <x-slot name="actions">
                <x-ui.dropdown align="right" width="w-64">
                    <x-slot name="trigger">
                        <x-ui.button variant="secondary" class="shadow-soft px-2">
                            <x-icon.bookmark class="h-4 w-4 text-slate-500" />
                        </x-ui.button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="px-4 py-2 text-xs text-slate-400 font-bold text-center">
                            {{ __('Kayıtlı Görünümler') }}
                        </div>
                        @forelse($savedViews as $view)
                            <a href="{{ route('sales-orders.index', $view->query) }}" class="block px-4 py-2 text-sm leading-5 text-slate-700 hover:bg-slate-100 focus:outline-none focus:bg-slate-100 transition duration-150 ease-in-out flex justify-between items-center">
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
                        <a href="{{ route('saved-views.index', ['scope' => 'sales_orders']) }}" class="block px-4 py-2 text-sm leading-5 text-brand-600 hover:bg-slate-100 focus:outline-none focus:bg-slate-100 transition duration-150 ease-in-out text-center font-medium">
                            {{ __('Görünümleri Yönet') }}
                        </a>
                    </x-slot>
                </x-ui.dropdown>

                <x-ui.button 
                    type="button" 
                    variant="ghost"
                    x-data=""
                    x-on:click.prevent="$dispatch('open-modal', 'save-view-modal')"
                    class="text-slate-400 hover:text-brand-600 transition-colors ml-2 px-2"
                    title="{{ __('Görünümü Kaydet') }}"
                >
                    <x-icon.save class="h-4 w-4" />
                </x-ui.button>
            </x-slot>

            <x-slot name="filters">
                <form method="GET" action="{{ route('sales-orders.index') }}" class="contents">
                    {{-- Search --}}
                    <div class="col-span-1">
                        <x-input name="search" type="text" placeholder="Sipariş No / Başlık" :value="$search" class="w-full" />
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
                        <x-input name="date_from" type="date" placeholder="Başlangıç" :value="$dateFrom" class="w-full" />
                        <x-input name="date_to" type="date" placeholder="Bitiş" :value="$dateTo" class="w-full" />
                    </div>

                    {{-- Amount Range --}}
                    <div class="col-span-1 flex space-x-2">
                        <x-input name="total_min" type="number" placeholder="Min Tutar" :value="$totalMin" step="0.01" class="w-full" />
                        <x-input name="total_max" type="number" placeholder="Max Tutar" :value="$totalMax" step="0.01" class="w-full" />
                    </div>

                    {{-- Contract & Actions --}}
                    <div class="col-span-1 flex space-x-2">
                        <x-select name="has_contract" class="w-full">
                            <option value="">{{ __('Sözleşme: Tümü') }}</option>
                            <option value="1" @selected($hasContract == '1')>{{ __('Var') }}</option>
                            <option value="0" @selected($hasContract == '0')>{{ __('Yok') }}</option>
                        </x-select>
                    </div>

                    {{-- Actions --}}
                    <div class="col-span-1 md:col-span-2 lg:col-span-4 flex items-center justify-end space-x-2 mt-2">
                        <x-ui.button type="submit" class="w-full sm:w-auto justify-center">{{ __('Filtrele') }}</x-ui.button>
                        <x-ui.button href="{{ route('sales-orders.index') }}" variant="secondary" class="w-full sm:w-auto justify-center">{{ __('Temizle') }}</x-ui.button>
                    </div>
                </form>
            </x-slot>
        </x-partials.filter-card>

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

                <input type="hidden" name="scope" value="sales_orders">
                <input type="hidden" name="query" value="{{ json_encode(request()->except(['page'])) }}">

                <div class="mt-6">
                    <x-input-label for="view_name" value="{{ __('Görünüm Adı') }}" />
                    <x-input id="view_name" name="name" type="text" class="mt-1 block w-3/4" required placeholder="Örn: Açık Siparişler" />
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

        <x-ui.card class="!p-0 overflow-hidden">
            @php
                $statusVariants = [
                    'draft' => 'draft',
                    'confirmed' => 'confirmed',
                    'in_progress' => 'in_progress',
                    'completed' => 'completed',
                    'contracted' => 'success',
                    'cancelled' => 'cancelled',
                ];
            @endphp
            <x-ui.table class="ui-table-sticky">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold tracking-wide text-slate-500">{{ __('Sipariş No') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold tracking-wide text-slate-500">{{ __('Başlık') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold tracking-wide text-slate-500">{{ __('Müşteri') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold tracking-wide text-slate-500">{{ __('Tekne') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold tracking-wide text-slate-500">{{ __('Durum') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold tracking-wide text-slate-500">{{ __('Genel Toplam') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold tracking-wide text-slate-500 w-32">{{ __('Aksiyonlar') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($salesOrders as $salesOrder)
                        @php
                            $isLocked = $salesOrder->isLocked();
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-6 py-3 whitespace-nowrap max-w-0">
                                <div class="text-sm font-medium text-slate-900 truncate">{{ $salesOrder->order_no }}</div>
                                <div class="text-xs text-slate-500">
                                    {{ $salesOrder->order_date ? $salesOrder->order_date->format('d.m.Y') : '-' }}
                                </div>
                            </td>
                            <td class="px-6 py-3 text-sm text-slate-600 max-w-0 truncate">{{ $salesOrder->title }}</td>
                            <td class="px-6 py-3 text-sm text-slate-600 max-w-0 truncate">{{ $salesOrder->customer?->name ?? '-' }}</td>
                            <td class="px-6 py-3 text-sm text-slate-600 max-w-0 truncate">{{ $salesOrder->vessel?->name ?? '-' }}</td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <x-ui.badge :variant="$statusVariants[$salesOrder->status] ?? 'neutral'">
                                    {{ $salesOrder->status_label }}
                                </x-ui.badge>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium text-slate-900">
                                {{ \App\Support\MoneyMath::formatTR($salesOrder->grand_total) }} {{ $salesOrder->currency }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium">
                                <form id="sales-order-delete-{{ $salesOrder->id }}" method="POST" action="{{ route('sales-orders.destroy', $salesOrder) }}" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                <x-ui.row-actions
                                    show="{{ route('sales-orders.show', $salesOrder) }}"
                                    edit="{{ route('sales-orders.edit', $salesOrder) }}"
                                    delete="{{ route('sales-orders.destroy', $salesOrder) }}"
                                    delete-form-id="sales-order-delete-{{ $salesOrder->id }}"
                                    :edit-disabled="$isLocked"
                                    :delete-disabled="$isLocked"
                                    edit-disabled-title="{{ __('Bu sipariş sözleşmeye dönüştürüldüğü için düzenlenemez.') }}"
                                    delete-disabled-title="{{ __('Bu siparişin bağlı sözleşmesi olduğu için silinemez.') }}"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6">
                                <x-ui.empty-state
                                    title="{{ __('Kayıt bulunamadı') }}"
                                    description="{{ __('Filtreleri temizleyip tekrar deneyin.') }}"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>

        <div class="mt-4">
            {{ $salesOrders->links() }}
        </div>
    </div>
</x-app-layout>
