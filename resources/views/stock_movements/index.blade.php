<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="{{ __('Stok Hareketleri') }}" subtitle="{{ __('Giriş, çıkış ve transfer kayıtları.') }}">
             <x-slot name="actions">
                <x-ui.button href="{{ route('stock-operations.create') }}" variant="primary">
                    <x-icon.plus class="w-4 h-4 mr-1" />
                    {{ __('Manuel İşlem') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <x-ui.card>
            <x-slot name="header">{{ __('Filtreler') }}</x-slot>
            <form method="GET" action="{{ route('stock-movements.index') }}" class="grid gap-4 md:grid-cols-5 items-end">
                <div>
                    <x-input-label for="warehouse_id" :value="__('Depo')" />
                    <x-select id="warehouse_id" name="warehouse_id" class="mt-1 w-full">
                        <option value="">{{ __('Tümü') }}</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected(request('warehouse_id') == $wh->id)>{{ $wh->name }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div>
                    <x-input-label for="product_id" :value="__('Ürün')" />
                    <x-select id="product_id" name="product_id" class="mt-1 w-full">
                        <option value="">{{ __('Tümü') }}</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" @selected(request('product_id') == $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div>
                    <x-input-label for="direction" :value="__('Yön')" />
                     <x-select id="direction" name="direction" class="mt-1 w-full">
                        <option value="">{{ __('Tümü') }}</option>
                        <option value="in" @selected(request('direction') == 'in')>{{ __('Giriş (+)') }}</option>
                        <option value="out" @selected(request('direction') == 'out')>{{ __('Çıkış (-)') }}</option>
                    </x-select>
                </div>
                <div>
                    <x-input-label for="type" :value="__('İşlem Tipi')" />
                     <x-select id="type" name="type" class="mt-1 w-full">
                        <option value="">{{ __('Tümü') }}</option>
                        <option value="manual_in" @selected(request('type') == 'manual_in')>{{ __('Manuel Giriş') }}</option>
                        <option value="manual_out" @selected(request('type') == 'manual_out')>{{ __('Manuel Çıkış') }}</option>
                        <option value="workorder_consume" @selected(request('type') == 'workorder_consume')>{{ __('İş Emri') }}</option>
                    </x-select>
                </div>
                <div class="flex gap-2">
                    <x-ui.button type="submit" size="sm" class="w-full">{{ __('Filtrele') }}</x-ui.button>
                     <x-ui.button href="{{ route('stock-movements.index') }}" variant="secondary" size="sm">{{ __('Temizle') }}</x-ui.button>
                </div>
                 <div class="col-span-full flex gap-2 pt-2 border-t border-slate-100">
                    <x-ui.button href="{{ route('stock-movements.index', array_merge(request()->all(), ['date_filter' => 'today'])) }}" size="xs" variant="{{ request('date_filter') == 'today' ? 'primary' : 'secondary' }}">Bugün</x-ui.button>
                    <x-ui.button href="{{ route('stock-movements.index', array_merge(request()->all(), ['date_filter' => 'last_7_days'])) }}" size="xs" variant="{{ request('date_filter') == 'last_7_days' ? 'primary' : 'secondary' }}">Son 7 Gün</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card>
            <x-ui.table>
                <thead class="bg-slate-50 text-xs font-semibold tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">{{ __('Tarih') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Depo') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Ürün') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('İşlem') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Miktar') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Referans / Not') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Kullanıcı') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($movements as $movement)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-3 text-xs text-slate-500">
                                {{ $movement->occurred_at->format('d.m.Y H:i') }}
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700">
                                {{ $movement->warehouse->name }}
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-900">
                                {{ $movement->product->name }}
                                <span class="text-xs text-slate-400 block">{{ $movement->product->sku }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <x-ui.badge :variant="$movement->direction == 'in' ? 'success' : 'danger'" class="!px-2 !py-1 text-xs">
                                    {{ $movement->type }}
                                </x-ui.badge>
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-sm {{ $movement->direction == 'in' ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $movement->direction == 'in' ? '+' : '-' }}{{ $movement->qty + 0 }}
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                @if($movement->reference)
                                    <span class="font-medium">
                                        @if($movement->reference_type == 'App\Models\WorkOrder')
                                            <a href="{{ route('work-orders.show', $movement->reference_id) }}" class="text-brand-600 hover:underline">
                                                İş Emri #{{ $movement->reference->id }}
                                            </a>
                                        @elseif($movement->reference_type == 'App\Models\StockTransfer')
                                             <a href="{{ route('stock-transfers.show', $movement->reference_id) }}" class="text-brand-600 hover:underline">
                                                Transfer #{{ $movement->reference->id }}
                                            </a>
                                        @elseif($movement->reference_type == 'App\Models\SalesOrder')
                                             <a href="{{ route('sales-orders.show', $movement->reference_id) }}" class="text-brand-600 hover:underline">
                                                Sipariş {{ $movement->reference->order_no ?? '#' . $movement->reference_id }}
                                            </a>
                                        @else
                                            #{{ $movement->reference->id }}
                                        @endif
                                    </span>
                                @endif
                                <div class="text-xs text-slate-400">{{ $movement->note }}</div>
                            </td>
                            <td class="px-4 py-3 text-right text-xs text-slate-500">
                                {{ $movement->creator->name ?? '-' }}
                            </td>
                        </tr>
                    @empty
                         <tr>
                            <td colspan="7" class="px-4 py-8">
                                <x-ui.empty-state icon="switch-horizontal" title="Hareket bulunamadı" description="Henüz stok işlemi yapılmamış." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>
        
        <div>{{ $movements->links() }}</div>
    </div>
</x-app-layout>
