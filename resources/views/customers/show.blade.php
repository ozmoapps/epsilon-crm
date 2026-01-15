<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ $customer->name }}" subtitle="{{ __('Müşteri detay görünümü') }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('customers.ledger', $customer) }}" variant="secondary" size="sm">
                    <x-icon.document-text class="mr-1 h-3 w-3" />
                    {{ __('Cari Ekstre') }}
                </x-ui.button>
                <x-ui.button href="{{ route('customers.edit', $customer) }}" variant="secondary" size="sm">
                    {{ __('Düzenle') }}
                </x-ui.button>
                <x-ui.button href="{{ route('customers.index') }}" variant="secondary" size="sm">
                    {{ __('Listeye Dön') }}
                </x-ui.button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">
        <div class="grid gap-4 lg:grid-cols-2">
            <x-ui.card>
                <x-slot name="header">{{ __('İletişim') }}</x-slot>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-slate-500">{{ __('Telefon') }}</dt>
                        <dd class="font-medium text-slate-900">{{ $customer->phone ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">{{ __('E-posta') }}</dt>
                        <dd class="font-medium text-slate-900">{{ $customer->email ?: '—' }}</dd>
                    </div>
                </dl>
            </x-ui.card>

            <x-ui.card>
                <x-slot name="header">{{ __('Adres ve Notlar') }}</x-slot>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-slate-500">{{ __('Adres') }}</dt>
                        <dd class="font-medium text-slate-900">{{ $customer->address ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">{{ __('Notlar') }}</dt>
                        <dd class="font-medium text-slate-900">{{ $customer->notes ?: '—' }}</dd>
                    </div>
                </dl>
            </x-ui.card>
        </div>

        <x-ui.card>
            <x-slot name="header">
                <div class="flex items-center justify-between">
                    <span>{{ __('Tekneler') }}</span>
                    <x-ui.button href="{{ route('vessels.create', ['customer_id' => $customer->id]) }}" variant="secondary" size="sm">
                        {{ __('Tekne Ekle') }}
                    </x-ui.button>
                </div>
            </x-slot>
            <div class="space-y-3">
                @forelse ($customer->vessels as $vessel)
                    <a href="{{ route('vessels.show', $vessel) }}" class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/70 px-4 py-3 text-sm text-slate-700 transition hover:bg-slate-100 ui-focus">
                        <span class="font-medium text-slate-900">{{ $vessel->name }}</span>
                        <span class="text-slate-400">→</span>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">{{ __('Henüz tekne yok.') }}</p>
                @endforelse
            </div>
        </x-ui.card>

        <x-ui.card>
            <x-slot name="header">
                <div class="flex items-center justify-between">
                    <span>{{ __('İş Emirleri') }}</span>
                    <x-ui.button href="{{ route('work-orders.create') }}" variant="secondary" size="sm">
                        {{ __('İş Emri Ekle') }}
                    </x-ui.button>
                </div>
            </x-slot>
            <div class="space-y-3">
                @forelse ($customer->workOrders as $workOrder)
                    <a href="{{ route('work-orders.show', $workOrder) }}" class="flex flex-col gap-1 rounded-xl border border-slate-100 bg-slate-50/70 px-4 py-3 text-sm text-slate-700 transition hover:bg-slate-100 ui-focus sm:flex-row sm:items-center sm:justify-between">
                        <span class="font-medium text-slate-900">{{ $workOrder->title }}</span>
                        <span class="text-slate-500">
                            {{ $workOrder->vessel?->name ?? 'Tekne yok' }} · {{ $workOrder->status_label }}
                        </span>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">{{ __('Henüz iş emri yok.') }}</p>
                @endforelse
            </div>
        </x-ui.card>

        <form id="customer-delete-{{ $customer->id }}" method="POST" action="{{ route('customers.destroy', $customer) }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
        <x-ui.confirm
            title="{{ __('Silme işlemini onayla') }}"
            message="{{ __('Bu işlem geri alınamaz. Devam etmek istiyor musunuz?') }}"
            confirm-text="{{ __('Evet, sil') }}"
            cancel-text="{{ __('Vazgeç') }}"
            variant="danger"
            form-id="customer-delete-{{ $customer->id }}"
        >
            <x-slot name="trigger">
                <x-ui.button type="button" variant="danger" class="w-full">
                    {{ __('Müşteri Kaydını Sil') }}
                </x-ui.button>
            </x-slot>
        </x-ui.confirm>

        <x-ui.card class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-card mt-6">
             <x-slot name="header">
                <div class="flex items-center justify-between">
                    <span>{{ __('Cari Hesap Özeti') }}</span>
                    <div class="flex gap-2">
                        @foreach($balances as $currency => $balance)
                            <span class="inline-flex items-center rounded-xl px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $balance >= 0 ? 'bg-red-50 text-red-700 ring-red-600/10' : 'bg-green-50 text-green-700 ring-green-600/10' }}">
                                {{ $currency }}: {{ number_format(abs($balance), 2) }} {{ $balance >= 0 ? '(Borç)' : '(Alacak)' }}
                            </span>
                        @endforeach
                        @if($balances->isEmpty())
                             <span class="inline-flex items-center rounded-xl bg-slate-50 px-2 py-1 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-500/10">{{ __('Bakiye Yok') }}</span>
                        @endif
                    </div>
                </div>
            </x-slot>

            {{-- Filters --}}
            <div class="border-b border-slate-100 bg-slate-50/50 px-4 py-3">
                <form method="GET" action="{{ route('customers.show', $customer) }}" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('Tarih Aralığı') }}</label>
                        <div class="flex items-center gap-2">
                            <input type="date" name="ledger_start_date" value="{{ request('ledger_start_date') }}" class="h-8 rounded-xl border-slate-200 text-xs shadow-sm focus:border-brand-500 focus:ring-brand-500 ui-focus">
                            <span class="text-slate-400">-</span>
                            <input type="date" name="ledger_end_date" value="{{ request('ledger_end_date') }}" class="h-8 rounded-xl border-slate-200 text-xs shadow-sm focus:border-brand-500 focus:ring-brand-500 ui-focus">
                        </div>
                    </div>
                    
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('İşlem Tipi') }}</label>
                        <select name="ledger_type" class="h-8 rounded-xl border-slate-200 text-xs shadow-sm focus:border-brand-500 focus:ring-brand-500 ui-focus">
                            <option value="">{{ __('Tümü') }}</option>
                            <option value="invoice" {{ request('ledger_type') == 'invoice' ? 'selected' : '' }}>{{ __('Fatura') }}</option>
                            <option value="payment" {{ request('ledger_type') == 'payment' ? 'selected' : '' }}>{{ __('Tahsilat') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('Tekne') }}</label>
                        <select name="ledger_vessel_id" class="h-8 max-w-[150px] rounded-xl border-slate-200 text-xs shadow-sm focus:border-brand-500 focus:ring-brand-500 ui-focus">
                            <option value="">{{ __('Tümü') }}</option>
                            @foreach($customer->vessels as $vessel)
                                <option value="{{ $vessel->id }}" {{ request('ledger_vessel_id') == $vessel->id ? 'selected' : '' }}>{{ $vessel->name }}</option>
                            @endforeach
                        </select>
                    </div>

                     <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('Para Birimi') }}</label>
                         <select name="ledger_currency" class="h-8 rounded-xl border-slate-200 text-xs shadow-sm focus:border-brand-500 focus:ring-brand-500 ui-focus">
                            <option value="">{{ __('Tümü') }}</option>
                            <option value="EUR" {{ request('ledger_currency') == 'EUR' ? 'selected' : '' }}>EUR</option>
                            <option value="TRY" {{ request('ledger_currency') == 'TRY' ? 'selected' : '' }}>TRY</option>
                            <option value="USD" {{ request('ledger_currency') == 'USD' ? 'selected' : '' }}>USD</option>
                            <option value="GBP" {{ request('ledger_currency') == 'GBP' ? 'selected' : '' }}>GBP</option>
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <x-ui.button type="submit" variant="primary" class="h-8 !px-3 text-xs">
                            {{ __('Filtrele') }}
                        </x-ui.button>
                         <x-ui.button href="{{ route('customers.show', $customer) }}" variant="secondary" class="h-8 !px-3 text-xs">
                            {{ __('Sıfırla') }}
                        </x-ui.button>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <x-ui.table>
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ __('Tarih') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Tip') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Açıklama') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Tekne') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ __('Borç') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ __('Alacak') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($ledgerEntries as $entry)
                            <tr class="hover:bg-slate-50/50">
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-900">
                                    {{ $entry->occurred_at->format('d.m.Y') }}
                                    <div class="text-xs font-normal text-slate-400">{{ $entry->occurred_at->format('H:i') }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    @if($entry->type === 'invoice')
                                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/10">{{ __('Fatura') }}</span>
                                    @elseif($entry->type === 'payment')
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/10">{{ __('Tahsilat') }}</span>
                                    @else
                                        {{ $entry->type }}
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    {{ $entry->description }}
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500">
                                    {{ $entry->vessel ? $entry->vessel->name : '-' }}
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-slate-900">
                                    @if($entry->direction === 'debit')
                                        {{ number_format($entry->amount, 2) }} <span class="text-xs text-slate-400">{{ $entry->currency }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-slate-900">
                                    @if($entry->direction === 'credit')
                                        {{ number_format($entry->amount, 2) }} <span class="text-xs text-slate-400">{{ $entry->currency }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                    {{ __('Kayıt bulunamadı.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    </tbody>
                </x-ui.table>
            </div>
            
            @if($ledgerEntries->hasPages())
                <div class="border-t border-slate-100 bg-slate-50/50 px-4 py-3 border-gray-200 sm:px-6">
                    {{ $ledgerEntries->appends(request()->query())->links() }}
                </div>
            @endif
        </x-ui.card>

        <x-ui.card class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-card !p-0 mt-6">
            <div class="border-b border-slate-100 bg-white px-4 py-3">
                <h3 class="font-semibold text-slate-900">{{ __('Aktivite') }}</h3>
            </div>
            <div class="bg-slate-50/40 p-4">
                <x-activity-timeline :logs="$timeline" :show-subject="false" />
            </div>
        </x-ui.card>
    </div>
</x-app-layout>
