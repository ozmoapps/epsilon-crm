<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="{{ $bankAccount->name }}" subtitle="{{ $bankAccount->type === 'cash' ? 'Kasa' : 'Banka Hesabı' }}">
             <x-slot name="actions">
                <x-ui.button href="{{ route('bank-accounts.edit', $bankAccount) }}" variant="secondary" size="sm" class="mr-2">
                    <x-icon.pencil class="h-4 w-4 mr-1" /> {{ __('Düzenle') }}
                </x-ui.button>
                <x-ui.button href="{{ route('bank-accounts.index') }}" variant="white" size="sm">
                    {{ __('Listeye Dön') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left: Details & Movements -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Transactions -->
                    <x-ui.card>
                        <h3 class="font-semibold text-slate-900 mb-4">{{ __('Hesap Hareketleri') }}</h3>
                        
                        <div class="overflow-hidden ring-1 ring-slate-200 rounded-lg">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase">{{ __('Tarih') }}</th>
                                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase">{{ __('İşlem Türü') }}</th>
                                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase">{{ __('Açıklama') }}</th>
                                        <th class="px-3 py-3 text-right text-xs font-semibold text-slate-500 uppercase">{{ __('Meblağ') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-slate-200">
                                    @php
                                        // Simple running balance calculation visual is tricky in paginated reverse order list.
                                        // Usually we show transactions as simple list. 
                                        // To show running balance column, we need strict ordering and "balance before".
                                        // For MVP/Sprint 2.2, we list transactions. Balance is shown in summary card.
                                    @endphp

                                    @forelse($payments as $payment)
                                        <tr class="hover:bg-slate-50">
                                            <td class="px-3 py-3 text-sm text-slate-900">
                                                {{ $payment->payment_date->format('d.m.Y') }}
                                            </td>
                                            <td class="px-3 py-3 text-sm text-slate-600">
                                                {{ __('Tahsilat') }}
                                            </td>
                                            <td class="px-3 py-3 text-sm text-slate-600">
                                                 <div class="font-medium text-slate-900">
                                                     {{ $payment->invoice->customer->name ?? '-' }}
                                                 </div>
                                                 <div class="text-xs text-slate-500">
                                                     {{ $payment->invoice->invoice_no ?? '-' }} 
                                                     @if($payment->reference_number) / Ref: {{ $payment->reference_number }} @endif
                                                 </div>
                                            </td>
                                            <td class="px-3 py-3 text-right text-sm font-bold text-emerald-600">
                                                +{{ number_format((float)$payment->original_amount, 2) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-3 py-8 text-center text-sm text-slate-500">
                                                {{ __('Henüz hareket yok.') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            {{ $payments->links() }}
                        </div>
                    </x-ui.card>
                </div>

                <!-- Right Sidebar -->
                <div class="space-y-6">
                    <!-- Balance Summary -->
                    <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-6 text-white shadow-xl">
                        <h4 class="text-slate-400 text-sm font-medium mb-1">{{ __('Güncel Bakiye') }}</h4>
                        <div class="text-3xl font-bold tracking-tight">
                            {{ number_format($bankAccount->balance, 2) }} <span class="text-lg opacity-70">{{ $bankAccount->currency->code ?? '' }}</span>
                        </div>
                        
                        <div class="mt-6 grid grid-cols-2 gap-4 border-t border-white/10 pt-4">
                            <div>
                                <div class="text-xs text-slate-400">{{ __('Açılış') }}</div>
                                <div class="text-sm font-semibold">{{ number_format($bankAccount->opening_balance, 2) }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-slate-400">{{ __('Toplam Giriş') }}</div>
                                <div class="text-sm font-semibold text-emerald-400">
                                    +{{ number_format($bankAccount->balance - $bankAccount->opening_balance, 2) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Details -->
                    <x-ui.card>
                        <h3 class="font-semibold text-slate-900 mb-4">{{ __('Hesap Bilgileri') }}</h3>
                        <dl class="space-y-3 text-sm">
                            @if($bankAccount->type === 'bank')
                                <div>
                                    <dt class="text-slate-500">{{ __('Banka') }}</dt>
                                    <dd class="font-medium text-slate-900">{{ $bankAccount->bank_name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-slate-500">{{ __('Şube') }}</dt>
                                    <dd class="font-medium text-slate-900">{{ $bankAccount->branch_name ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-slate-500">{{ __('IBAN') }}</dt>
                                    <dd class="font-medium text-slate-900 font-mono">{{ $bankAccount->iban ?? '-' }}</dd>
                                </div>
                            @endif
                                <div>
                                    <dt class="text-slate-500">{{ __('Döviz Cinsi') }}</dt>
                                    <dd class="font-medium text-slate-900">{{ $bankAccount->currency->name ?? '-' }} ({{ $bankAccount->currency->code ?? '' }})</dd>
                                </div>
                                <div>
                                    <dt class="text-slate-500">{{ __('Durum') }}</dt>
                                    <dd class="font-medium {{ $bankAccount->is_active ? 'text-emerald-600' : 'text-slate-500' }}">
                                        {{ $bankAccount->is_active ? 'Aktif' : 'Pasif' }}
                                    </dd>
                                </div>
                        </dl>
                    </x-ui.card>

                    <!-- Future Actions Placeholder -->
                    <div class="p-4 border border-dashed border-slate-300 rounded-xl text-center">
                        <p class="text-sm text-slate-500 mb-3">{{ __('Hızlı İşlemler (Yakında)') }}</p>
                         <button disabled class="w-full inline-flex justify-center items-center px-4 py-2 bg-slate-100 border border-transparent rounded-md font-semibold text-xs text-slate-400 uppercase tracking-widest cursor-not-allowed">
                            {{ __('Transfer Yap') }}
                        </button>
                        <button disabled class="mt-2 w-full inline-flex justify-center items-center px-4 py-2 bg-slate-100 border border-transparent rounded-md font-semibold text-xs text-slate-400 uppercase tracking-widest cursor-not-allowed">
                            {{ __('Manuel Hareket Ekle') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
