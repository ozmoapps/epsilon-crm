<x-app-layout>
    <x-slot name="header">
        <x-partials.page-header title="{{ __('Sözleşmeler') }}" subtitle="{{ __('Sözleşme listesi ve detayları.') }}">
            <!-- Actions removed: Contracts must be created from Sales Orders -->
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
                            <a href="{{ route('contracts.index', $view->query) }}" class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out flex justify-between items-center">
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
                        <a href="{{ route('saved-views.index', ['scope' => 'contracts']) }}" class="block px-4 py-2 text-sm leading-5 text-indigo-600 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out text-center font-medium">
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

            <x-slot name="filters">
                <form method="GET" action="{{ route('contracts.index') }}" class="contents">
                    {{-- Search --}}
                    <div class="col-span-1">
                        <x-input name="search" type="text" placeholder="Sözleşme No / Müşteri Adı" :value="$search" class="w-full" />
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

                    {{-- Date Range --}}
                    <div class="col-span-1 flex space-x-2">
                        <x-input name="date_from" type="date" placeholder="Başlangıç" :value="$dateFrom" class="w-full" />
                        <x-input name="date_to" type="date" placeholder="Bitiş" :value="$dateTo" class="w-full" />
                    </div>

                    {{-- Actions --}}
                    <div class="col-span-1 flex items-end justify-end space-x-2">
                        <x-button type="submit" class="w-full sm:w-auto justify-center">{{ __('Filtrele') }}</x-button>
                        <x-button href="{{ route('contracts.index') }}" variant="secondary" class="w-full sm:w-auto justify-center">{{ __('Temizle') }}</x-button>
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

                <input type="hidden" name="scope" value="contracts">
                <input type="hidden" name="query" value="{{ json_encode(request()->except(['page'])) }}">

                <div class="mt-6">
                    <x-input-label for="view_name" value="{{ __('Görünüm Adı') }}" />
                    <x-input id="view_name" name="name" type="text" class="mt-1 block w-3/4" required placeholder="Örn: İmzalanan Sözleşmeler" />
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

        @php
            $statusVariants = [
                'draft' => 'draft',
                'issued' => 'neutral',
                'sent' => 'sent',
                'signed' => 'signed',
                'superseded' => 'neutral',
                'cancelled' => 'cancelled',
            ];
        @endphp

        <x-card class="!p-0 overflow-hidden">
             <x-ui.table>
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-500">{{ __('Sözleşme No') }}</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-500">{{ __('Müşteri') }}</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-500">{{ __('Tarih') }}</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-500">{{ __('Durum') }}</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold tracking-wider text-gray-500">{{ __('Aksiyonlar') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($contracts as $contract)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $contract->contract_no }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $contract->customer_name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $contract->issued_at?->format('d.m.Y') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-ui.badge :variant="$statusVariants[$contract->status] ?? 'neutral'">
                                    {{ $contract->status_label }}
                                </x-ui.badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <form id="contract-delete-{{ $contract->id }}" method="POST" action="{{ route('contracts.destroy', $contract) }}" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                <x-ui.row-actions
                                    show="{{ route('contracts.show', $contract) }}"
                                    delete="{{ route('contracts.destroy', $contract) }}"
                                    delete-form-id="contract-delete-{{ $contract->id }}"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <div class="h-12 w-12 rounded-full bg-indigo-50 flex items-center justify-center">
                                        <x-icon.inbox class="h-6 w-6 text-indigo-600" />
                                    </div>
                                    <div class="text-center">
                                        <p class="text-sm font-medium text-gray-900">{{ __('Sözleşme bulunamadı.') }}</p>
                                        <p class="mt-1 text-sm text-gray-500">{{ __('Filtreleri temizleyerek tekrar deneyin.') }}</p>
                                        <div class="mt-4">
                                            <x-button href="{{ route('sales-orders.index') }}" size="sm">{{ __('Siparişlerden Oluştur') }}</x-button>
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
            {{ $contracts->links() }}
        </div>
    </div>
</x-app-layout>
