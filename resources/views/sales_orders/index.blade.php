<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="{{ __('Satış Siparişleri') }}"
            subtitle="{{ __('Tüm satış siparişlerini görüntüleyin.') }}"
        />
    </x-slot>

    <div class="space-y-6">
        <x-card>
            <x-slot name="header">{{ __('Filtreler') }}</x-slot>
            <div class="rounded-xl border border-gray-200 bg-slate-50/60 p-4">
                <form method="GET" action="{{ route('sales-orders.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="flex-1">
                        <x-input-label for="search" :value="__('Ara (Sipariş No / Başlık)')" />
                        <x-input id="search" name="search" type="text" class="mt-1 w-full" :value="$search" placeholder="SO-2026-0001" />
                    </div>
                    <div class="sm:w-52">
                        <x-input-label for="status" :value="__('Durum')" />
                        <x-select id="status" name="status" class="mt-1 w-full">
                            <option value="">{{ __('Tümü') }}</option>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected($status === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </x-select>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-button type="submit">{{ __('Filtrele') }}</x-button>
                        <x-button href="{{ route('sales-orders.index') }}" variant="secondary">{{ __('Temizle') }}</x-button>
                    </div>
                </form>
            </div>
        </x-card>

        <x-card>
            <x-slot name="header">{{ __('Satış Siparişleri') }}</x-slot>
            @php
                $statusVariants = [
                    'draft' => 'draft',
                    'confirmed' => 'confirmed',
                    'in_progress' => 'in_progress',
                    'completed' => 'completed',
                    'canceled' => 'canceled',
                ];
                $actionItemClass = 'flex w-full items-center gap-2 px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50';
                $actionDangerClass = 'flex w-full items-center gap-2 px-3 py-2 text-sm text-rose-600 transition hover:bg-rose-50';
            @endphp
            <x-ui.table>
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">{{ __('Sipariş No') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Başlık') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Müşteri') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Tekne') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Durum') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Genel Toplam') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Aksiyonlar') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($salesOrders as $salesOrder)
                        <tr class="odd:bg-white even:bg-slate-50 hover:bg-slate-100/60">
                            <td class="px-4 py-3">
                                <div class="text-sm font-semibold text-slate-900">{{ $salesOrder->order_no }}</div>
                                <div class="text-xs text-slate-500">
                                    {{ $salesOrder->order_date ? $salesOrder->order_date->format('d.m.Y') : '-' }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-slate-700">{{ $salesOrder->title }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $salesOrder->customer?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $salesOrder->vessel?->name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <x-ui.badge :variant="$statusVariants[$salesOrder->status] ?? 'neutral'">
                                    {{ $salesOrder->status_label }}
                                </x-ui.badge>
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-semibold text-slate-900">
                                {{ number_format((float) $salesOrder->grand_total, 2, ',', '.') }} {{ $salesOrder->currency }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <x-ui.dropdown align="right" width="w-44">
                                    <x-slot name="trigger">
                                        <x-ui.tooltip text="{{ __('İşlemler') }}">
                                            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-slate-300 hover:text-slate-900" aria-label="{{ __('İşlemler') }}">
                                                <x-icon.dots class="h-4 w-4" />
                                            </button>
                                        </x-ui.tooltip>
                                    </x-slot>
                                    <x-slot name="content">
                                        <a href="{{ route('sales-orders.show', $salesOrder) }}" class="{{ $actionItemClass }}">
                                            <x-icon.info class="h-4 w-4 text-sky-600" />
                                            {{ __('Görüntüle') }}
                                        </a>
                                        <a href="{{ route('sales-orders.edit', $salesOrder) }}" class="{{ $actionItemClass }}">
                                            <x-icon.pencil class="h-4 w-4 text-indigo-600" />
                                            {{ __('Düzenle') }}
                                        </a>
                                        <form method="POST" action="{{ route('sales-orders.destroy', $salesOrder) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="{{ $actionDangerClass }}" onclick="return confirm('Satış siparişi silinsin mi?')">
                                                <x-icon.trash class="h-4 w-4" />
                                                {{ __('Sil') }}
                                            </button>
                                        </form>
                                    </x-slot>
                                </x-ui.dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-slate-500">
                                {{ __('Henüz satış siparişi oluşturulmadı.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-card>

        <div>
            {{ $salesOrders->links() }}
        </div>
    </div>
</x-app-layout>
