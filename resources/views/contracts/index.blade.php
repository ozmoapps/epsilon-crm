<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="{{ __('Sözleşmeler') }}" subtitle="{{ __('Sözleşme listesi ve detayları.') }}">
            <!-- Actions removed: Contracts must be created from Sales Orders -->
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
<x-ui.filter-bar action="{{ route('contracts.index') }}" method="GET">
            <x-slot:left>
                <x-input name="search" type="text" placeholder="Sözleşme No / Müşteri Adı" :value="$search" class="w-full" />
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

                {{-- Date Range --}}
                <div class="flex space-x-2 w-full sm:w-auto">
                    <x-input name="date_from" type="date" placeholder="Başlangıç" :value="$dateFrom" class="w-full sm:w-32 px-2 text-xs" />
                    <x-input name="date_to" type="date" placeholder="Bitiş" :value="$dateTo" class="w-full sm:w-32 px-2 text-xs" />
                </div>
            </x-slot:right>

            <x-slot:actions>
                {{-- Saved Views --}}
                <x-ui.dropdown align="right" width="w-64">
                    <x-slot name="trigger">
                        <x-ui.button variant="secondary" class="mr-2 px-2 shadow-soft">
                            <x-icon.bookmark class="h-4 w-4 text-slate-500" />
                        </x-ui.button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="px-4 py-2 text-xs text-slate-400 font-bold text-center">
                            {{ __('Kayıtlı Görünümler') }}
                        </div>
                        @forelse($savedViews as $view)
                            <a href="{{ route('contracts.index', $view->query) }}" class="block px-4 py-2 text-sm leading-5 text-slate-700 hover:bg-slate-100 focus:outline-none focus:bg-slate-100 transition duration-150 ease-in-out flex justify-between items-center">
                                <span>{{ $view->name }}</span>
                                @if($view->is_shared)
                                    <span class="px-1.5 py-0.5 text-[10px] rounded bg-blue-100 text-blue-800">{{ __('Ortak') }}</span>
                                @endif
                            </a>
                        @empty
                            <div class="px-4 py-2 text-sm text-slate-500 text-center italic">
                                {{ __('Görünüm yok') }}
                            </div>
                        @endforelse
                        <div class="border-t border-slate-100"></div>
                        <a href="{{ route('saved-views.index', ['scope' => 'contracts']) }}" class="block px-4 py-2 text-sm leading-5 text-brand-600 hover:bg-slate-100 focus:outline-none focus:bg-slate-100 transition duration-150 ease-in-out text-center font-medium">
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
                <x-ui.button href="{{ route('contracts.index') }}" variant="secondary">
                    {{ __('Temizle') }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.filter-bar>

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

                <input type="hidden" name="scope" value="contracts">
                <input type="hidden" name="query" value="{{ json_encode(request()->except(['page'])) }}">

                <div class="mt-6">
                    <x-input-label for="view_name" value="{{ __('Görünüm Adı') }}" />
                    <x-input id="view_name" name="name" type="text" class="mt-1 block w-3/4" required placeholder="Örn: İmzalanan Sözleşmeler" />
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

        <x-ui.card class="!p-0 overflow-hidden">
            <x-ui.table density="compact">
                <x-slot name="head">
                    <tr>
                        <th class="px-6 py-3 text-left tracking-wide">{{ __('Sözleşme No') }}</th>
                        <th class="px-6 py-3 text-left tracking-wide">{{ __('Müşteri') }}</th>
                        <th class="px-6 py-3 text-left tracking-wide">{{ __('Tarih') }}</th>
                        <th class="px-6 py-3 text-left tracking-wide">{{ __('Durum') }}</th>
                        <th class="px-6 py-3 text-right tracking-wide w-32">{{ __('Aksiyonlar') }}</th>
                    </tr>
                </x-slot>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($contracts as $contract)
                        <tr class="group">
                            <td class="px-6 py-3 text-sm font-medium text-slate-900 max-w-0 truncate">{{ $contract->contract_no }}</td>
                            <td class="px-6 py-3 text-sm text-slate-600 max-w-0 truncate">{{ $contract->customer_name }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-slate-600">{{ $contract->issued_at?->format('d.m.Y') }}</td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <x-ui.badge :variant="$statusVariants[$contract->status] ?? 'neutral'">
                                    {{ $contract->status_label }}
                                </x-ui.badge>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium">
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
                            <td colspan="5" class="px-6">
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

        <div class="bg-slate-50 border-t border-slate-200 p-4 rounded-b-xl">
            {{ $contracts->links() }}
        </div>
    </div>
</x-app-layout>
