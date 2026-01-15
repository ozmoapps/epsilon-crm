<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="{{ __('Tahsilatlar') }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('payments.create') }}" variant="primary" size="sm">
                    {{ __('+ Avans Al') }}
                </x-ui.button>
                <x-ui.button href="{{ route('invoices.index') }}" variant="secondary" size="sm">
                    {{ __('Faturalara git') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-soft">
                <div class="border-b border-slate-100 px-6 py-4">
                    {{-- Filter Bar --}}
                    <form method="GET" class="flex flex-wrap items-end gap-4">
                        {{-- Customer --}}
                        <div>
                            <x-input-label for="customer_id" :value="__('Müşteri')" />
                            <select name="customer_id" id="customer_id" class="ui-input mt-1 block w-full min-w-[150px] text-xs h-9">
                                <option value="">{{ __('Tümü') }}</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>
                                        {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Currency --}}
                        <div>
                            <x-input-label for="currency" :value="__('Para Birimi')" />
                            <select name="currency" id="currency" class="ui-input mt-1 block w-full min-w-[100px] text-xs h-9">
                                <option value="">{{ __('Tümü') }}</option>
                                @foreach($availableCurrencies as $cur)
                                    <option value="{{ $cur }}" {{ request('currency') == $cur ? 'selected' : '' }}>
                                        {{ $cur }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Date Range --}}
                        <div>
                            <x-input-label for="date_from" :value="__('Başlangıç')" />
                            <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="ui-input mt-1 block w-full text-xs h-9">
                        </div>
                        <div>
                            <x-input-label for="date_to" :value="__('Bitiş')" />
                            <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="ui-input mt-1 block w-full text-xs h-9">
                        </div>

                        {{-- Only Open Checkbox --}}
                        <div class="flex items-center pb-2">
                             <x-checkbox id="only_open" name="only_open" value="1" :checked="request('only_open')" />
                             <label for="only_open" class="ml-2 block text-sm text-slate-700">{{ __('Sadece Açık Avanslar') }}</label>
                        </div>

                        {{-- Actions --}}
                        <div class="flex gap-2">
                            <x-ui.button type="submit" variant="primary" size="sm">
                                {{ __('Filtrele') }}
                            </x-ui.button>
                            
                            @if(request()->anyFilled(['customer_id', 'currency', 'date_from', 'date_to', 'only_open']))
                                <x-ui.button href="{{ route('payments.index') }}" variant="ghost" size="sm" class="text-xs">
                                    {{ __('Sıfırla') }}
                                </x-ui.button>
                            @endif
                        </div>
                    </form>
                </div>

        <div class="mt-6 border border-slate-200 rounded-xl overflow-hidden shadow-soft bg-white">
            <div class="overflow-x-auto">
                <x-ui.table>
                    <x-slot name="head">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">{{ __('Tarih') }}</th>
                            <th class="px-4 py-3 text-left font-semibold">{{ __('Müşteri / Ref') }}</th>
                            <th class="px-4 py-3 text-left font-semibold">{{ __('Tutar') }}</th>
                            <th class="px-4 py-3 text-left font-semibold">{{ __('Durum') }}</th>
                            <th class="relative px-4 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </x-slot>

                    <tbody class="divide-y divide-slate-100">
                        @forelse($payments as $p)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    {{ \Illuminate\Support\Carbon::parse($p->payment_date)->format('d.m.Y') }}
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <div class="flex flex-col">
                                        @if($p->customer)
                                            <a href="{{ route('customers.ledger', $p->customer) }}" class="font-bold text-brand-600 hover:underline">
                                                {{ $p->customer->name }}
                                            </a>
                                        @else
                                            <span class="text-slate-400 font-style-italic">{{ __('Müşterisiz') }}</span>
                                        @endif
                                        
                                        <span class="text-xs text-slate-500">
                                            @if($p->invoice)
                                                {{ __('Fatura') }}: #{{ $p->invoice->invoice_no }}
                                            @else
                                                {{ __('Avans/Diğer') }}
                                            @endif
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    {{ number_format((float)$p->amount, 2) }} {{ $p->effective_currency }}
                                    @if($p->original_currency !== $p->effective_currency)
                                        <div class="text-xs text-slate-400">
                                            ({{ number_format((float)$p->original_amount, 2) }} {{ $p->original_currency }})
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    @php
                                        $unallocated = $p->unallocated_amount;
                                        $curr = $p->effective_currency;
                                    @endphp
                                    
                                    @if($unallocated > 0.001)
                                        <x-ui.badge variant="info" class="!px-2 !py-0.5 text-xs font-medium">
                                            {{ __('Kalan') }}: {{ number_format($unallocated, 2) }} {{ $curr }}
                                        </x-ui.badge>
                                    @else
                                         <x-ui.badge variant="neutral" class="!px-2 !py-0.5 text-xs font-medium">
                                            {{ __('Tamamı Dağıtıldı') }}
                                        </x-ui.badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-medium">
                                    {{-- Manual Allocation Removed --}}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center text-slate-500">
                                    <div class="mx-auto h-12 w-12 text-slate-300 mb-3">
                                        <x-icon.search class="w-12 h-12" />
                                    </div>
                                    <div class="text-lg font-medium text-slate-900">{{ __('Sonuç bulunamadı') }}</div>
                                    <div class="mt-1 text-sm text-slate-500">{{ __('Filtreleri değiştirip tekrar deneyin.') }}</div>
                                    <div class="mt-6">
                                        <x-ui.button href="{{ route('payments.index') }}" variant="secondary">
                                            {{ __('Temizle') }}
                                        </x-ui.button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-ui.table>
            </div>
            
            <div class="bg-slate-50 border-t border-slate-200 p-4">
                {{ $payments->links() }}
            </div>
        </div>
            </div>
        </div>
    </div>
</x-app-layout>
