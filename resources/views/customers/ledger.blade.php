<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                {{ __('Cari Ekstre') }}: {{ $customer->name }}
            </h2>
            <div class="flex gap-2 print:hidden">
                <x-ui.button href="{{ route('customers.ledger.manual.create', $customer) }}" variant="secondary">
                     <x-icon.plus class="mr-2 h-4 w-4" />
                     {{ __('İşlem Ekle') }}
                </x-ui.button>
                <x-ui.button variant="secondary" onclick="window.print()">
                    <x-icon.document class="mr-2 h-4 w-4" />
                    {{ __('Yazdır') }}
                </x-ui.button>
                <x-ui.button href="{{ route('customers.show', $customer) }}" variant="secondary">
                    {{ __('Müşteriye Dön') }}
                </x-ui.button>
            </div>
        </div>
    </x-slot>

    <div class="py-6 space-y-6">

        {{-- Filter Bar --}}
        <x-ui.card class="print:hidden">
            <form method="GET" class="flex flex-wrap items-end gap-4">
                <div>
                    <x-input-label for="start_date" :value="__('Başlangıç')" />
                    <x-text-input id="start_date" type="date" name="start_date" :value="$request->start_date" class="mt-1 block w-full" />
                </div>

                <div>
                    <x-input-label for="end_date" :value="__('Bitiş')" />
                    <x-text-input id="end_date" type="date" name="end_date" :value="$request->end_date" class="mt-1 block w-full" />
                </div>

                <div>
                    <x-input-label for="currency" :value="__('Para Birimi')" />
                    <select name="currency" id="currency" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm ui-focus">
                        <option value="">{{ __('Tümü') }}</option>
                        @foreach(($availableCurrencies ?? collect()) as $c)
                            <option value="{{ $c }}" {{ $request->currency === $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="vessel_id" :value="__('Tekne')" />
                    <select name="vessel_id" id="vessel_id" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm ui-focus">
                        <option value="">{{ __('Tümü') }}</option>
                        @foreach(($vessels ?? collect()) as $v)
                            <option value="{{ $v->id }}" {{ (string)$request->vessel_id === (string)$v->id ? 'selected' : '' }}>
                                {{ $v->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="type" :value="__('İşlem Tipi')" />
                    <select name="type" id="type" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm ui-focus">
                        <option value="">{{ __('Tümü') }}</option>
                        @foreach(($availableTypes ?? collect()) as $t)
                            <option value="{{ $t }}" {{ $request->type === $t ? 'selected' : '' }}>
                                {{ $t === 'invoice' ? __('Fatura') : ($t === 'payment' ? __('Tahsilat') : ($t === 'manual' ? __('Manuel') : $t)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-ui.button variant="primary" type="submit">{{ __('Filtrele') }}</x-ui.button>

                    @if(request()->anyFilled(['start_date', 'end_date', 'currency', 'vessel_id', 'type']))
                        <a href="{{ route('customers.ledger', $customer) }}" class="ml-2 text-sm text-slate-500 hover:underline hover:text-brand-600">
                            {{ __('Sıfırla') }}
                        </a>
                    @endif
                </div>
            </form>
        </x-ui.card>

        {{-- Open Advances Query --}}
        @php
            // Calculate Open Advances (Payments without Invoice AND with unallocated amount)
            $openAdvances = \App\Models\Payment::where('customer_id', $customer->id)
                ->whereNull('invoice_id')
                ->with('allocations') // Optimization per Hotfix
                ->get()
                ->filter(fn($p) => $p->unallocated_amount > 0.001)
                ->groupBy('effective_currency');
        @endphp

        {{-- Empty State --}}
        @if($groupedEntries->isEmpty() && $openAdvances->isEmpty())
            <div class="text-center text-slate-500 py-12 bg-white rounded-xl border border-dashed border-slate-300">
                {{ __('Kayıt bulunamadı.') }}
            </div>
        @endif

        @if($openAdvances->isNotEmpty())
            <x-ui.card class="bg-amber-50 border-amber-200">
                <div class="mb-2 border-b border-amber-200 pb-2">
                    <h3 class="text-sm font-bold text-amber-800 uppercase tracking-wider flex items-center gap-2">
                        <x-icon.info class="h-4 w-4" />
                        {{ __('Dağıtılmamış Açık Avanslar') }}
                    </h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($openAdvances as $currency => $advances)
                        <div class="bg-white rounded p-3 border border-amber-100 shadow-sm">
                            <span class="block text-xs text-gray-500 font-bold mb-1">{{ $currency }}</span>
                            <span class="block text-lg font-mono font-bold text-amber-700">
                                {{ number_format($advances->sum('unallocated_amount'), 2) }}
                            </span>
                            <span class="text-xs text-gray-400">
                                {{ $advances->count() }} {{ __('adet işlem') }}
                            </span>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 text-right">
                    <a href="{{ route('payments.index', ['customer_id' => $customer->id, 'only_open' => 1]) }}" class="text-xs font-semibold text-amber-700 hover:text-amber-900 border-b border-amber-700/30 hover:border-amber-900">
                        {{ __('Detayları Görüntüle ve Dağıt') }} &rarr;
                    </a>
                </div>
            </x-ui.card>
        @endif

        @foreach($groupedEntries as $currency => $entries)
            @php
                $opening = (float) ($openingBalances[$currency] ?? 0);
                $running = $opening;

                $totalDebit  = (float) $entries->where('direction', 'debit')->sum('amount');
                $totalCredit = (float) $entries->where('direction', 'credit')->sum('amount');

                // Debit increases (customer owes us), Credit decreases
                $closing = $opening + $totalDebit - $totalCredit;
            @endphp

            <x-ui.card class="break-inside-avoid">
                <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-4">
                    <h3 class="text-lg font-bold text-slate-700">{{ $currency }} {{ __('Hesap Özeti') }}</h3>

                    <div class="text-right text-sm">
                        @if($request->start_date)
                            <div class="text-slate-500">
                                {{ __('Devreden') }}:
                                <span class="font-mono font-bold">{{ number_format($opening, 2) }}</span>
                            </div>
                        @endif

                        <div class="{{ $closing >= 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                            {{ __('Son Bakiye') }}:
                            <span class="font-mono font-bold text-lg">{{ number_format($closing, 2) }}</span>
                        </div>
                    </div>
                </div>

                <x-ui.table>
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50">
                            <th class="px-2 py-2">{{ __('Tarih') }}</th>
                            <th class="px-2 py-2">{{ __('İşlem') }}</th>
                            <th class="px-2 py-2">{{ __('Ref / Açıklama') }}</th>
                            <th class="px-2 py-2 text-right text-rose-600">{{ __('Borç') }}</th>
                            <th class="px-2 py-2 text-right text-emerald-600">{{ __('Alacak') }}</th>
                            <th class="px-2 py-2 text-right">{{ __('Bakiye') }}</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        {{-- Opening Row --}}
                        @if($request->start_date && abs($opening) > 0.000001)
                            <tr class="bg-slate-50 italic">
                                <td class="px-2 py-2">{{ \Carbon\Carbon::parse($request->start_date)->format('d.m.Y') }}</td>
                                <td class="px-2 py-2">{{ __('DEVİR') }}</td>
                                <td class="px-2 py-2">{{ __('Başlangıç Bakiyesi') }}</td>
                                <td class="px-2 py-2 text-right"></td>
                                <td class="px-2 py-2 text-right"></td>
                                <td class="px-2 py-2 text-right font-mono">{{ number_format($opening, 2) }}</td>
                            </tr>
                        @endif

                        @forelse($entries as $entry)
                            @php
                                if ($entry->direction === 'debit') {
                                    $running += (float) $entry->amount;
                                } else {
                                    $running -= (float) $entry->amount;
                                }
                            @endphp

                            @endphp

                            <tr class="hover:bg-slate-50/50">
                                <td class="px-2 py-2 whitespace-nowrap">
                                    {{ $entry->occurred_at?->format('d.m.Y') }}
                                </td>
                                <td class="px-2 py-2 whitespace-nowrap">
                                    @if($entry->type === 'invoice')
                                        <x-ui.badge variant="danger">{{ __('Fatura') }}</x-ui.badge>
                                    @elseif($entry->type === 'payment')
                                        <x-ui.badge variant="success">{{ __('Tahsilat') }}</x-ui.badge>
                                    @elseif($entry->type === 'manual')
                                        <x-ui.badge variant="info">{{ __('Manuel') }}</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="neutral">{{ $entry->type }}</x-ui.badge>
                                    @endif
                                </td>
                                <td class="px-2 py-2">
                                    @if($entry->source_type === \App\Models\Invoice::class)
                                        <a href="{{ route('invoices.show', $entry->source_id) }}" class="text-brand-600 hover:underline print:no-underline print:text-inherit">
                                            {{ $entry->description }}
                                        </a>
                                    @else
                                        {{ $entry->description }}
                                    @endif

                                    @if($entry->vessel)
                                        <div class="text-xs text-slate-500">{{ $entry->vessel->name }}</div>
                                    @endif

                                    @if($entry->type === 'manual')
                                        <form method="POST" action="{{ route('customers.ledger.manual.destroy', [$customer, $entry]) }}" class="inline-block print:hidden ml-2" onsubmit="return confirm('{{ __('Bu kaydı silmek istediğinize emin misiniz?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-rose-400 hover:text-rose-700 underline">{{ __('Sil') }}</button>
                                        </form>
                                    @endif
                                </td>

                                <td class="px-2 py-2 text-right font-mono text-rose-600">
                                    {{ $entry->direction === 'debit' ? number_format((float)$entry->amount, 2) : '-' }}
                                </td>

                                <td class="px-2 py-2 text-right font-mono text-emerald-600">
                                    {{ $entry->direction === 'credit' ? number_format((float)$entry->amount, 2) : '-' }}
                                </td>

                                <td class="px-2 py-2 text-right font-mono font-bold bg-slate-50/50">
                                    {{ number_format($running, 2) }}
                                </td>
                            </tr>
                        @empty
                            {{-- no period entries; still show summary + possible opening --}}
                            <tr>
                                <td colspan="6" class="px-2 py-4 text-center text-slate-500">
                                    {{ __('Bu para biriminde seçili aralıkta hareket yok.') }}
                                </td>
                            </tr>
                        @endforelse

                        <tr class="bg-slate-100 font-bold border-t-2 border-slate-300">
                            <td colspan="3" class="px-2 py-2 text-right">{{ __('TOPLAM') }}</td>
                            <td class="px-2 py-2 text-right text-rose-600">{{ number_format($totalDebit, 2) }}</td>
                            <td class="px-2 py-2 text-right text-emerald-600">{{ number_format($totalCredit, 2) }}</td>
                            <td class="px-2 py-2 text-right">{{ number_format($closing, 2) }}</td>
                        </tr>
                    </tbody>
                </x-ui.table>
            </x-ui.card>
        @endforeach

    </div>
</x-app-layout>
