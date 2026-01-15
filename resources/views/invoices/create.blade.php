<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="{{ __('Yeni Fatura Oluştur') }}" subtitle="{{ $salesOrder->order_no }} için fatura taslağı">
            <x-slot name="actions">
                <x-ui.button href="{{ route('sales-orders.show', $salesOrder) }}" variant="secondary" size="sm">
                    {{ __('İptal') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="max-w-4xl mx-auto">
        <x-ui.card>
            @if ($errors->any())
                <div class="mb-4 p-4 rounded-md bg-red-50 border border-red-200">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <x-icon.x class="h-5 w-5 text-red-400" aria-hidden="true" />
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">
                                {{ __('Lütfen aşağıdaki hataları düzeltin:') }}
                            </h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul role="list" class="list-disc pl-5 space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <form action="{{ route('invoices.store') }}" method="POST" class="space-y-6">
                @csrf
                <input type="hidden" name="sales_order_id" value="{{ $salesOrder->id }}">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-ui.field label="Fatura Tarihi" name="issue_date">
                        <x-date-picker name="issue_date" :value="now()->format('Y-m-d')" class="w-full" />
                    </x-ui.field>

                    <x-ui.field label="Vade Tarihi" name="due_date">
                        <x-date-picker name="due_date" class="w-full" />
                    </x-ui.field>
                </div>

                <div class="border-t border-slate-100 pt-6">
                    <h3 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Faturalanacak Kalemler') }}</h3>
                    
                    <div class="overflow-hidden ring-1 ring-slate-200 rounded-lg">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900">{{ __('Ürün / Hizmet') }}</th>
                                    <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900">{{ __('Birim Fiyat') }}</th>
                                    <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900">{{ __('Sevk Edilen') }}</th>
                                    <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900">{{ __('Faturalanan') }}</th>
                                    <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900">{{ __('Kalan') }}</th>
                                    <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900 w-32">{{ __('Fatura Miktarı') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @foreach($items as $index => $item)
                                    <tr>
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900">
                                            {{ $item['product_name'] }}
                                            <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item['id'] }}">
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-right text-sm text-slate-500">
                                            {{ \App\Support\MoneyMath::formatTR($item['unit_price']) }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-right text-sm text-slate-500">
                                            {{ \App\Support\MoneyMath::formatQty($item['shipped_qty']) }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-right text-sm text-slate-500">
                                            {{ \App\Support\MoneyMath::formatQty($item['invoiced_qty']) }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-right text-sm text-slate-900 font-semibold">
                                            {{ \App\Support\MoneyMath::formatQty($item['remaining_qty']) }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-right text-sm text-slate-500">
                                            <input type="number" step="0.01" min="0" max="{{ $item['remaining_qty'] }}" 
                                                   name="items[{{ $index }}][quantity]" 
                                                   value="{{ $item['remaining_qty'] }}" 
                                                   class="block w-full rounded-md border-0 py-1.5 text-right text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm sm:leading-6">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex items-center justify-end border-t border-slate-100 pt-6 gap-3">
                    <x-ui.button href="{{ route('sales-orders.show', $salesOrder) }}" variant="secondary">
                        {{ __('İptal') }}
                    </x-ui.button>
                    <x-ui.button type="submit">
                        <x-icon.check class="w-4 h-4 mr-1"/>
                        {{ __('Faturayı Oluştur') }}
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-app-layout>
