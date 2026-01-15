@php
    $accounts = \App\Models\BankAccount::with('currency')
        ->where('is_active', true)
        ->where('type', 'bank') // ✅ SADECE BANKA HESAPLARI
        ->orderBy('name')
        ->get()
        ->groupBy(fn($a) => optional($a->currency)->code ?? '—');
@endphp

@if($accounts->isNotEmpty())
    <div class="section payment-instructions mt-8 print:break-inside-avoid">
        <h3 class="section-title text-sm font-bold uppercase tracking-wider border-b border-gray-200 pb-1 mb-2">
            {{ __('Tahsilat Bilgileri') }}
        </h3>

        <p class="text-xs text-gray-500 mb-3">
            {{ __('Ödemeler aşağıdaki hesaplara yapılabilir. Lütfen açıklamaya referans numarasını yazınız.') }}
        </p>

        <div class="space-y-4">
            @foreach($accounts as $currency => $currencyAccounts)
                <div class="currency-group">
                    <h4 class="text-xs font-bold text-gray-700 mb-1 flex items-center gap-2">
                        <span class="bg-gray-100 px-1.5 py-0.5 rounded">{{ $currency }}</span>
                        {{ __('Hesapları') }}
                    </h4>

                    <table class="w-full text-xs border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500">
                                <th class="border border-gray-200 px-2 py-1 text-left w-1/4">{{ __('Hesap Adı') }}</th>
                                <th class="border border-gray-200 px-2 py-1 text-left w-1/4">{{ __('Banka / Şube') }}</th>
                                <th class="border border-gray-200 px-2 py-1 text-left">{{ __('IBAN / Hesap No') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($currencyAccounts as $acc)
                                <tr>
                                    <td class="border border-gray-200 px-2 py-1 font-medium">
                                        {{ $acc->name }}
                                    </td>

                                    <td class="border border-gray-200 px-2 py-1">
                                        {{ $acc->bank_name ?: '—' }}
                                        @if($acc->branch_name)
                                            <span class="text-gray-400">/</span> {{ $acc->branch_name }}
                                        @endif
                                    </td>

                                    <td class="border border-gray-200 px-2 py-1 font-mono">
                                        {{ $acc->iban ?: '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
    </div>

    @once
        <style>
            .payment-instructions table { width: 100%; border-collapse: collapse; font-size: 10px; }
            .payment-instructions th, .payment-instructions td { border: 1px solid #e5e7eb; padding: 4px 6px; }
            .payment-instructions th { background-color: #f9fafb; font-weight: 600; }
            .payment-instructions .currency-group { margin-bottom: 10px; }
        </style>
    @endonce
@endif
