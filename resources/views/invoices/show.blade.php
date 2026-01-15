<x-app-layout>
    @php
        // Strict Status Mapping for Badge Lock
        $invoiceStatusVariants = [
            'draft'     => 'neutral',
            'issued'    => 'info',
            'cancelled' => 'danger',
        ];

        $paymentStatusVariants = [
            'unpaid'   => 'neutral',
            'partial'  => 'info',
            'paid'     => 'success',
            'overpaid' => 'success',
        ];

        // Calculated values
        $paidSum = (float) $invoice->payments->sum('amount');
        $remaining = (float) $invoice->total - $paidSum;
        // Deterministic sorting via Query Builder (Laravel version independent)
        $sortedPayments = $invoice->payments()->orderByDesc('payment_date')->orderByDesc('id')->get();
    @endphp

    <x-slot name="header">
        <x-ui.page-header title="{{ $invoice->invoice_no ?? __('Taslak Fatura') }}" subtitle="{{ $invoice->customer->name }}">
            <x-slot name="status">
                <div class="flex items-center gap-2">
                    <x-ui.badge :variant="$invoiceStatusVariants[$invoice->status] ?? 'neutral'">
                        {{ $invoice->status_label }}
                    </x-ui.badge>
                    <x-ui.badge :variant="$paymentStatusVariants[$invoice->payment_status] ?? 'neutral'">
                        {{ $invoice->payment_status_label }}
                    </x-ui.badge>
                </div>
            </x-slot>

            <x-slot name="actions">
                @if($invoice->status === 'draft')
                    {{-- Hidden Form for Issue Action --}}
                    <form id="issue-form-{{ $invoice->id }}" action="{{ route('invoices.issue', $invoice) }}" method="POST" class="hidden">
                        @csrf
                    </form>

                    {{-- Confirm Modal with Trigger --}}
                    <x-ui.confirm
                        title="{{ __('Emin misiniz?') }}"
                        message="{{ __('Fatura resmileştirilecek. Bu işlem geri alınamaz.') }}"
                        confirm-text="{{ __('Resmileştir') }}"
                        cancel-text="{{ __('Vazgeç') }}"
                        form-id="issue-form-{{ $invoice->id }}"
                    >
                        <x-slot:trigger>
                            <x-ui.button variant="primary">
                                <x-icon.check class="w-4 h-4 mr-1"/>
                                {{ __('Resmileştir') }}
                            </x-ui.button>
                        </x-slot:trigger>
                    </x-ui.confirm>
                @endif
                
                @if($invoice->salesOrder)
                    <x-ui.button href="{{ route('sales-orders.show', $invoice->salesOrder) }}" variant="secondary" size="sm">
                        {{ __('Siparişe Dön') }}
                    </x-ui.button>
                @else
                    <x-ui.button href="{{ route('invoices.index') }}" variant="secondary" size="sm">
                        {{ __('Listeye Dön') }}
                    </x-ui.button>
                @endif
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Invoice Lines -->
            <x-ui.card>
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">{{ __('Fatura Detayları') }}</h2>
                        <p class="text-sm text-slate-500">{{ $invoice->issue_date ? $invoice->issue_date->format('d.m.Y') : __('Tarih Yok') }}</p>
                    </div>
                </div>

                <x-ui.table density="compact">
                    <x-slot name="head">
                        <tr>
                            <x-ui.table.th>{{ __('Açıklama') }}</x-ui.table.th>
                            <x-ui.table.th align="right">{{ __('Miktar') }}</x-ui.table.th>
                            <x-ui.table.th align="right">{{ __('Birim Fiyat') }}</x-ui.table.th>
                            <x-ui.table.th align="right">{{ __('Vergi') }}</x-ui.table.th>
                            <x-ui.table.th align="right">{{ __('Toplam') }}</x-ui.table.th>
                        </tr>
                    </x-slot>
                    <x-slot name="body">
                        @foreach($invoice->lines as $line)
                            <x-ui.table.tr>
                                <x-ui.table.td>
                                    <span class="font-medium text-slate-900">{{ $line->description }}</span>
                                    <div class="text-xs text-slate-500">{{ $line->salesOrderItem->product->name ?? '' }}</div>
                                </x-ui.table.td>
                                <x-ui.table.td align="right" class="text-slate-500">{{ \App\Support\MoneyMath::formatQty($line->quantity) }}</x-ui.table.td>
                                <x-ui.table.td align="right" class="text-slate-500">{{ \App\Support\MoneyMath::formatTR($line->unit_price) }}</x-ui.table.td>
                                <x-ui.table.td align="right" class="text-slate-500">%{{ \App\Support\MoneyMath::formatQty($line->tax_rate) }}</x-ui.table.td>
                                <x-ui.table.td align="right" class="font-medium text-slate-900">{{ \App\Support\MoneyMath::formatTR($line->total) }}</x-ui.table.td>
                            </x-ui.table.tr>
                        @endforeach
                    </x-slot>
                    <x-slot name="foot">
                        <tr>
                            <td colspan="4" class="px-3 py-3 text-right text-sm font-medium text-slate-900">{{ __('Ara Toplam') }}</td>
                            <td class="px-3 py-3 text-right text-sm font-medium text-slate-900">{{ \App\Support\MoneyMath::formatTR($invoice->subtotal) }}</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-3 py-3 text-right text-sm font-medium text-slate-900">{{ __('KDV Toplam') }}</td>
                            <td class="px-3 py-3 text-right text-sm font-medium text-slate-900">{{ \App\Support\MoneyMath::formatTR($invoice->tax_total) }}</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-3 py-3 text-right text-base font-bold text-slate-900">{{ __('Genel Toplam') }}</td>
                            <td class="px-3 py-3 text-right text-base font-bold text-slate-900">{{ \App\Support\MoneyMath::formatTR($invoice->total) }} {{ $invoice->currency }}</td>
                        </tr>
                    </x-slot>
                </x-ui.table>
            </x-ui.card>

            <!-- Payments History -->
            <x-ui.card>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-slate-900">{{ __('Tahsilat Geçmişi') }}</h3>
                </div>

                @if($sortedPayments->count() > 0)
                    <x-ui.table density="compact">
                        <x-slot name="head">
                            <tr>
                                <x-ui.table.th>{{ __('Tarih') }}</x-ui.table.th>
                                <x-ui.table.th>{{ __('Orijinal Tutar') }}</x-ui.table.th>
                                <x-ui.table.th>{{ __('Kur') }}</x-ui.table.th>
                                <x-ui.table.th align="right">{{ __('Eşdeğer') }}</x-ui.table.th>
                            </tr>
                        </x-slot>
                        <x-slot name="body">
                            @foreach($sortedPayments as $payment)
                                @php
                                    $origAmt = $payment->original_amount ?? $payment->amount;
                                    $origCur = $payment->original_currency ?? $invoice->currency;
                                    $fx = $payment->fx_rate ?? 1;
                                @endphp
                                <x-ui.table.tr>
                                    <x-ui.table.td class="text-slate-900">{{ $payment->payment_date?->format('d.m.Y') ?? '-' }}</x-ui.table.td>
                                    <x-ui.table.td>
                                        <div class="text-slate-700 font-medium">{{ \App\Support\MoneyMath::formatTR($origAmt) }} {{ $origCur }}</div>
                                        @if(!empty($payment->payment_method))
                                            <div class="text-xs text-slate-500 capitalize">{{ $payment->payment_method_label ?? $payment->payment_method }}</div>
                                        @endif
                                    </x-ui.table.td>
                                    <x-ui.table.td>
                                        @if($origCur !== $invoice->currency)
                                            <x-ui.badge variant="info">
                                                1 {{ $invoice->currency }} = {{ rtrim(rtrim(number_format((float)$fx, 8, '.', ''), '0'), '.') }} {{ $origCur }}
                                            </x-ui.badge>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </x-ui.table.td>
                                    <x-ui.table.td align="right" class="font-semibold text-slate-900">
                                        {{ \App\Support\MoneyMath::formatTR($payment->amount) }} {{ $invoice->currency }}
                                    </x-ui.table.td>
                                </x-ui.table.tr>
                            @endforeach
                        </x-slot>
                    </x-ui.table>
                @else
                    <x-ui.empty-state
                        title="Henüz tahsilat yok"
                        description="Fatura için henüz herhangi bir ödeme alınmamış."
                        icon="cash"
                    />
                @endif
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <!-- Payment Form (Sidebar) -->
            @if($invoice->status === 'issued')
                <x-ui.card>
                    <div x-data="{
                        invoiceCurrency: '{{ $invoice->currency }}',
                        selectedAccount: '',
                        accountCurrency: '{{ $invoice->currency }}',
                        amount: '',
                        fxRate: '1',
                        method: 'bank_transfer',

                        normalize(v) {
                            if (v === null || v === undefined) return '';
                            let s = String(v).trim().replaceAll(' ', '');
                            if (s.includes(',')) {
                                s = s.replaceAll('.', '');
                                s = s.replaceAll(',', '.');
                            }
                            s = s.replace(/[^0-9\.\-]/g, '');
                            return s;
                        },
                        toNumber(v) {
                            const n = parseFloat(this.normalize(v));
                            return isNaN(n) ? 0 : n;
                        },
                        get isCrossCurrency() {
                            return this.accountCurrency !== this.invoiceCurrency;
                        },
                        get equivalent() {
                            const amt = this.toNumber(this.amount);
                            if (!amt) return 0;
                            if (!this.isCrossCurrency) return amt;
                            const r = this.toNumber(this.fxRate);
                            if (!r || r <= 0) return 0;
                            return amt / r;
                        },
                        formatTR(n) {
                            try {
                                return new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
                            } catch (e) {
                                return (Math.round(n * 100) / 100).toFixed(2);
                            }
                        },
                        updateCurrency(e) {
                            const option = e.target.options[e.target.selectedIndex];
                            this.accountCurrency = option.dataset.currency || this.invoiceCurrency;
                            if (!this.isCrossCurrency) this.fxRate = '1';
                        }
                    }">
                        <h3 class="font-semibold text-slate-900 mb-4">{{ __('Tahsilat Ekle') }}</h3>

                        <!-- Balance Info -->
                        <div class="mb-6 p-4 bg-slate-50 border border-slate-100 rounded-xl text-sm text-slate-700">
                            <div class="flex justify-between items-center">
                                @if($remaining >= 0)
                                    <span class="text-slate-500">{{ __('Kalan Tutar') }}</span>
                                    <span class="font-bold text-slate-900">{{ \App\Support\MoneyMath::formatTR($remaining) }} {{ $invoice->currency }}</span>
                                @else
                                    <span class="text-slate-500">{{ __('Cari Alacak') }}</span>
                                    <span class="font-bold text-emerald-600">{{ \App\Support\MoneyMath::formatTR(abs($remaining)) }} {{ $invoice->currency }}</span>
                                @endif
                            </div>

                            @if($invoice->payment_status === 'paid')
                                <div class="mt-2 text-xs text-slate-500 border-t border-slate-200 pt-2">
                                    {{ __('Not: Fatura tamamen ödenmiş durumda.') }}
                                </div>
                            @endif
                        </div>

                        <form action="{{ route('invoices.payments.store', $invoice) }}" method="POST" class="space-y-4">
                            @csrf

                            <!-- Bank Account -->
                            <x-ui.field label="Kasa / Banka" name="bank_account_id" required>
                                <select id="bank_account_id" name="bank_account_id"
                                        required
                                        @change="updateCurrency"
                                        x-model="selectedAccount"
                                        class="block ui-input shadow-sm sm:text-sm">
                                    <option value="" data-currency="{{ $invoice->currency }}">{{ __('Seçiniz') }}</option>
                                    @foreach($bankAccounts as $acc)
                                        <option value="{{ $acc->id }}" data-currency="{{ $acc->currency->code ?? $invoice->currency }}">
                                            {{ $acc->name }} ({{ $acc->currency->code ?? $invoice->currency }})
                                        </option>
                                    @endforeach
                                </select>
                            </x-ui.field>

                            <!-- Payment Method -->
                            <x-ui.field label="Ödeme Yöntemi" name="payment_method">
                                <select id="payment_method" name="payment_method"
                                        x-model="method"
                                        class="block ui-input shadow-sm sm:text-sm">
                                    <option value="bank_transfer">{{ __('Banka Transferi') }}</option>
                                    <option value="cash">{{ __('Nakit') }}</option>
                                    <option value="credit_card">{{ __('Kredi Kartı') }}</option>
                                    <option value="check">{{ __('Çek') }}</option>
                                </select>
                            </x-ui.field>

                            <!-- Amount -->
                            <div>
                                <label for="amount" class="block text-sm font-medium text-slate-700 mb-1">
                                    {{ __('Tutar') }} <span x-show="selectedAccount" class="text-xs text-brand-600" x-text="'(' + accountCurrency + ')'"></span>
                                </label>
                                <div class="relative rounded-xl shadow-sm">
                                    <input type="text"
                                           inputmode="decimal"
                                           id="amount"
                                           name="amount"
                                           x-model="amount"
                                           required
                                           class="block ui-input pl-3 pr-16 shadow-sm sm:text-sm"
                                           placeholder="0,00">
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                        <span class="text-gray-500 sm:text-sm" x-text="accountCurrency"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- FX Rate -->
                            <div x-show="isCrossCurrency" style="display: none;" class="p-3 bg-brand-50 border border-brand-100 rounded-xl">
                                <label for="fx_rate" class="block text-xs font-medium text-brand-800 mb-1">
                                    {{ __('Döviz Kuru') }} (1 <span x-text="invoiceCurrency"></span> = ? <span x-text="accountCurrency"></span>)
                                </label>
                                <input type="text"
                                       inputmode="decimal"
                                       id="fx_rate"
                                       name="fx_rate"
                                       x-model="fxRate"
                                       class="block ui-input text-right shadow-sm sm:text-sm"
                                       placeholder="Örn: 32,50">
                                <div class="mt-2 text-xs text-right text-brand-700">
                                    {{ __('Eşdeğer:') }} <span class="font-bold" x-text="formatTR(equivalent)"></span> <span x-text="invoiceCurrency"></span>
                                </div>
                            </div>

                            <!-- Date -->
                            <x-ui.field label="Tarih" name="payment_date">
                                <x-input type="date" name="payment_date" value="{{ now()->format('Y-m-d') }}" class="shadow-sm" />
                            </x-ui.field>

                            <!-- Notes -->
                            <div class="space-y-2">
                                <x-ui.field label="Referans / Açıklama" name="description">
                                    <div class="space-y-2">
                                        <x-input name="reference_number" placeholder="Referans No (Opsiyonel)" class="text-sm shadow-sm" />
                                        <x-textarea name="notes" rows="2" placeholder="Notlar..." class="text-sm shadow-sm" />
                                    </div>
                                </x-ui.field>
                            </div>

                            <x-ui.button type="submit" class="w-full justify-center" variant="primary">
                                <x-icon.plus class="w-4 h-4 mr-1"/>
                                {{ __('Tahsilat Ekle') }}
                            </x-ui.button>
                        </form>
                    </div>
                </x-ui.card>
            @endif
        </div>
    </div>
</x-app-layout>
