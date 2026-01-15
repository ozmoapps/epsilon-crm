<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="{{ __('Dashboard') }}" subtitle="{{ __('Genel durum ve hızlı işlemler.') }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('quotes.create') }}" size="sm">
                    <x-icon.plus class="h-4 w-4 mr-1"/>
                    {{ __('Yeni Teklif') }}
                </x-ui.button>
                <x-ui.button href="{{ route('invoices.create') }}" size="sm">
                    <x-icon.plus class="h-4 w-4 mr-1"/>
                    {{ __('Yeni Fatura') }}
                </x-ui.button>
                <x-ui.button href="{{ route('payments.create') }}" size="sm">
                    <x-icon.plus class="h-4 w-4 mr-1"/>
                    {{ __('Tahsilat Ekle') }}
                </x-ui.button>
                <x-ui.button href="{{ route('work-orders.create') }}" variant="secondary" size="sm">
                    {{ __('Yeni İş Emri') }}
                </x-ui.button>
                <x-ui.button href="{{ route('customers.create') }}" variant="secondary" size="sm">
                    {{ __('Yeni Müşteri') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div 
        x-data="{ 
            currency: 'EUR',
            currencies: ['EUR', 'USD', 'TRY', 'GBP'],
            openInvoices: {{ json_encode($openInvoices) }},
            overdueInvoices: {{ json_encode($overdueInvoices) }},
            advances: {{ json_encode($advances) }},
            bankBalances: {{ json_encode($bankBalances) }},
            finance: {{ json_encode($financeStats) }},
            
            getKpi(data, field = 'total_amount') {
                if (this.currency === 'all') {
                    // For amounts, 'all' is invalid/mixed. Return counts sum?
                    if (field === 'count') return data.reduce((sum, item) => sum + item.count, 0);
                    return '---'; 
                }
                const item = data.find(i => i.currency === this.currency);
                return item ? (field === 'count' ? item.count : this.formatMoney(item[field])) : (field === 'count' ? 0 : this.formatMoney(0));
            },

            formatMoney(amount) {
                return new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount);
            }
        }"
        class="space-y-6"
    >
        {{-- KPI Filter --}}
        <div class="flex items-center justify-end space-x-2">
            <span class="text-xs font-medium text-slate-500 uppercase">{{ __('Para Birimi:') }}</span>
            <div class="flex bg-white rounded-lg p-1 shadow-sm border border-slate-200">
                <template x-for="curr in currencies" :key="curr">
                    <button 
                        @click="currency = curr"
                        :class="currency === curr ? 'bg-brand-100 text-brand-700 shadow-sm' : 'text-slate-500 hover:bg-slate-50'"
                        class="px-3 py-1 text-xs font-semibold rounded-md transition-all"
                        x-text="curr"
                    ></button>
                </template>
            </div>
        </div>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            
            {{-- Open Invoices --}}
            <a href="{{ route('invoices.index', ['payment_status' => 'unpaid']) }}" class="block">
                <x-ui.card class="h-full hover:border-brand-300 transition-colors group">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase">{{ __('Açık Faturalar') }}</p>
                            <div class="mt-2 flex items-baseline gap-2">
                                <span class="text-2xl font-bold text-slate-900" x-text="getKpi(openInvoices, 'total_amount')"></span>
                                <span class="text-sm font-medium text-slate-400" x-text="currency"></span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">
                                <span x-text="getKpi(openInvoices, 'count')"></span> {{ __('adet fatura') }}
                            </p>
                        </div>
                        <div class="p-2 bg-brand-50 rounded-lg group-hover:bg-brand-100 transition-colors text-brand-600">
                            <x-icon.document-text class="h-5 w-5" />
                        </div>
                    </div>
                </x-ui.card>
            </a>

            {{-- Overdue Invoices --}}
            <a href="{{ route('invoices.index', ['status' => 'overdue']) }}" class="block">
                <x-ui.card class="h-full hover:border-rose-300 transition-colors group">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-medium text-rose-600 uppercase">{{ __('Vadesi Geçmiş') }}</p>
                            <div class="mt-2 flex items-baseline gap-2">
                                <span class="text-2xl font-bold text-slate-900" x-text="getKpi(overdueInvoices, 'total_amount')"></span>
                                <span class="text-sm font-medium text-slate-400" x-text="currency"></span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">
                                <span x-text="getKpi(overdueInvoices, 'count')"></span> {{ __('adet fatura') }}
                            </p>
                        </div>
                        <div class="p-2 bg-rose-50 rounded-lg group-hover:bg-rose-100 transition-colors text-rose-600">
                            <x-icon.exclamation-circle class="h-5 w-5" />
                        </div>
                    </div>
                </x-ui.card>
            </a>

            {{-- Advances --}}
            <a href="{{ route('payments.index', ['only_open' => 1]) }}" class="block">
                <x-ui.card class="h-full hover:border-emerald-300 transition-colors group">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-medium text-emerald-600 uppercase">{{ __('Kullanılabilir Avans') }}</p>
                            <div class="mt-2 flex items-baseline gap-2">
                                <span class="text-2xl font-bold text-slate-900" x-text="getKpi(advances, 'total_amount')"></span>
                                <span class="text-sm font-medium text-slate-400" x-text="currency"></span>
                            </div>
                             <p class="mt-1 text-xs text-slate-500">
                                {{ __('Tahsis edilmemiş') }}
                            </p>
                        </div>
                        <div class="p-2 bg-emerald-50 rounded-lg group-hover:bg-emerald-100 transition-colors text-emerald-600">
                            <x-icon.credit-card class="h-5 w-5" />
                        </div>
                    </div>
                </x-ui.card>
            </a>

            {{-- Bank Balances --}}
            <a href="{{ route('bank-accounts.index') }}" class="block">
                <x-ui.card class="h-full hover:border-blue-300 transition-colors group">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-medium text-blue-600 uppercase">{{ __('Kasa / Banka') }}</p>
                            <div class="mt-2 flex items-baseline gap-2">
                                <span class="text-2xl font-bold text-slate-900" x-text="getKpi(bankBalances, 'total_balance')"></span>
                                <span class="text-sm font-medium text-slate-400" x-text="currency"></span>
                            </div>
                             <p class="mt-1 text-xs text-slate-500">
                                <span x-text="getKpi(bankBalances, 'count')"></span> {{ __('hesap bakiyesi') }}
                            </p>
                        </div>
                        <div class="p-2 bg-blue-50 rounded-lg group-hover:bg-blue-100 transition-colors text-blue-600">
                            <x-icon.building class="h-5 w-5" />
                        </div>
                    </div>
                </x-ui.card>
            </a>

             {{-- Stock Alerts --}}
             <a href="{{ route('stock.dashboard') }}" class="block">
                <x-ui.card class="h-full hover:border-amber-300 transition-colors group">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-medium text-amber-600 uppercase">{{ __('Stok Uyarıları') }}</p>
                            <div class="mt-2 flex items-baseline gap-2">
                                <span class="text-2xl font-bold text-slate-900">{{ $stockAlertsCount }}</span>
                            </div>
                             <p class="mt-1 text-xs text-slate-500">
                                {{ __('Kritik seviye altı') }}
                            </p>
                        </div>
                        <div class="p-2 bg-amber-50 rounded-lg group-hover:bg-amber-100 transition-colors text-amber-600">
                            <x-icon.cube class="h-5 w-5" />
                        </div>
                    </div>
                </x-ui.card>
            </a>
        </div>

        {{-- Main Content Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {{-- Left Column (Operasyon Paneli) --}}
            <div class="lg:col-span-2 space-y-6">
                
                {{-- Sales Funnel --}}
                <x-ui.card>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-slate-900">{{ __('Satış Hunisi') }}</h3>
                        <x-ui.button href="{{ route('quotes.index') }}" variant="ghost" size="sm" class="text-xs">
                            {{ __('Tümü') }}
                        </x-ui.button>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4">
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                            <div class="text-xs text-slate-500">{{ __('Taslak Teklif') }}</div>
                            <div class="text-lg font-bold text-slate-700 mt-1">{{ $quoteStats->draft }}</div>
                        </div>
                        <div class="p-3 bg-blue-50 rounded-xl border border-blue-100">
                            <div class="text-xs text-blue-600">{{ __('Gönderilen') }}</div>
                            <div class="text-lg font-bold text-blue-700 mt-1">{{ $quoteStats->sent }}</div>
                        </div>
                         <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-100">
                            <div class="text-xs text-emerald-600">{{ __('Onaylanan') }}</div>
                            <div class="text-lg font-bold text-emerald-700 mt-1">{{ $quoteStats->accepted }}</div>
                        </div>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-slate-100">
                         <div class="flex items-center justify-between mb-2">
                            <h4 class="text-xs font-semibold text-slate-900">{{ __('Satış Siparişleri') }}</h4>
                        </div>
                         <div class="grid grid-cols-2 gap-4">
                            <div class="p-3 bg-white border border-slate-200 rounded-xl flex justify-between items-center">
                                <span class="text-sm text-slate-600">{{ __('Aktif Siparişler') }}</span>
                                <span class="text-lg font-bold text-slate-900">{{ $salesOrderStats->active }}</span>
                            </div>
                             <div class="p-3 bg-white border border-slate-200 rounded-xl flex justify-between items-center">
                                <span class="text-sm text-slate-600">{{ __('Tamamlanan') }}</span>
                                <span class="text-lg font-bold text-slate-900">{{ $salesOrderStats->completed }}</span>
                            </div>
                        </div>
                    </div>
                </x-ui.card>

                {{-- Workload --}}
                 <x-ui.card>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-slate-900">{{ __('İş Yükü & Operasyon') }}</h3>
                         <x-ui.button href="{{ route('work-orders.index') }}" variant="ghost" size="sm" class="text-xs">
                            {{ __('Tümü') }}
                        </x-ui.button>
                    </div>
                     <div class="grid grid-cols-3 gap-4">
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 text-center">
                            <div class="text-2xl font-bold text-slate-700">{{ $workOrderStats->open }}</div>
                            <div class="text-xs text-slate-500 mt-1">{{ __('Açık İş Emri') }}</div>
                        </div>
                        <div class="p-3 bg-amber-50 rounded-xl border border-amber-100 text-center">
                            <div class="text-2xl font-bold text-amber-700">{{ $workOrderStats->in_progress }}</div>
                            <div class="text-xs text-amber-600 mt-1">{{ __('Devam Eden') }}</div>
                        </div>
                         <div class="p-3 bg-purple-50 rounded-xl border border-purple-100 text-center">
                            <div class="text-2xl font-bold text-purple-700">{{ $workOrderStats->planned }}</div>
                            <div class="text-xs text-purple-600 mt-1">{{ __('Planlanan') }}</div>
                        </div>
                    </div>
                 </x-ui.card>

                {{-- Finance Summary (30 Days) --}}
                 <x-ui.card>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-slate-900">{{ __('Finans (Son 30 Gün)') }}</h3>
                    </div>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                             <p class="text-xs font-medium text-slate-500 mb-2">{{ __('Kesilen Faturalar') }}</p>
                             <div class="text-2xl font-bold text-slate-900">
                                <span x-text="finance.invoiced[currency] ? formatMoney(finance.invoiced[currency]) : '0,00'"></span>
                                <span class="text-sm font-medium text-slate-400" x-text="currency"></span>
                             </div>
                        </div>
                        <div>
                             <p class="text-xs font-medium text-slate-500 mb-2">{{ __('Tahsilatlar') }}</p>
                             <div class="text-2xl font-bold text-slate-900">
                                <span x-text="finance.collected[currency] ? formatMoney(finance.collected[currency]) : '0,00'"></span>
                                <span class="text-sm font-medium text-slate-400" x-text="currency"></span>
                             </div>
                        </div>
                    </div>
                 </x-ui.card>

            </div>

             {{-- Right Sidebar --}}
             <div class="space-y-6">
                
                {{-- Critical Alerts --}}
                @if($criticalOverdueInvoices->isNotEmpty())
                    <x-ui.card class="border-rose-100 bg-rose-50/30">
                        <h3 class="text-sm font-bold text-rose-700 mb-3 flex items-center">
                            <x-icon.exclamation-circle class="w-4 h-4 mr-2"/>
                            {{ __('Acil: Vadesi Geçmiş') }}
                        </h3>
                        <div class="space-y-3">
                            @foreach($criticalOverdueInvoices as $inv)
                                <a href="{{ route('invoices.show', $inv) }}" class="block p-2 bg-white rounded-lg border border-rose-100 shadow-sm hover:border-rose-300 transition-colors">
                                    <div class="flex justify-between items-start">
                                        <div class="text-xs font-bold text-slate-700 truncate max-w-[120px]">{{ $inv->customer->name }}</div>
                                        <div class="text-xs font-bold text-rose-600">
                                            {{ \App\Support\MoneyMath::formatTR($inv->total) }} {{ $inv->currency }}
                                        </div>
                                    </div>
                                    <div class="text-[10px] text-rose-400 mt-1">
                                        {{ $inv->due_date->diffForHumans() }}
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </x-ui.card>
                @endif

                 {{-- Follow Ups --}}
                 <x-ui.card>
                    <h3 class="text-sm font-bold text-slate-900 mb-3">{{ __('Yaklaşan Takipler') }}</h3>
                    @forelse($upcomingFollowUps as $followUp)
                         <div class="flex items-start gap-3 py-3 border-b border-slate-100 last:border-0">
                            <div class="mt-0.5">
                                @if($followUp->is_completed)
                                    <div class="w-2 h-2 rounded-full bg-emerald-400"></div>
                                @else
                                    <div class="w-2 h-2 rounded-full {{ $followUp->isOverdue() ? 'bg-rose-500' : 'bg-amber-400' }}"></div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-700 truncate">{{ $followUp->title }}</p>
                                <p class="text-xs text-slate-500 truncate">
                                    {{ $followUp->subject?->name ?? 'Genel' }}
                                </p>
                            </div>
                            <div class="text-xs text-slate-400 whitespace-nowrap">
                                {{ $followUp->next_at->format('d/m') }}
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 italic text-center py-4">{{ __('Takip kaydı yok.') }}</p>
                    @endforelse
                    {{-- <div class="mt-2 text-center">
                        <a href="#" class="text-xs font-medium text-brand-600 hover:text-brand-800 disabled cursor-not-allowed opacity-50" title="Coming Soon">{{ __('Tümünü Gör') }}</a>
                    </div> --}}
                 </x-ui.card>

                 {{-- Activity Feed --}}
                 <x-ui.card>
                    <h3 class="text-sm font-bold text-slate-900 mb-4">{{ __('Son Hareketler') }}</h3>
                    <div class="relative pl-4 border-l border-slate-200 space-y-6">
                        @foreach($recentActivity as $log)
                             <div class="relative">
                                <div class="absolute -left-[21px] top-1.5 w-2.5 h-2.5 rounded-full bg-slate-200 border-2 border-white"></div>
                                <div class="text-xs text-slate-500 mb-0.5">{{ $log->created_at->diffForHumans() }}</div>
                                <div class="text-sm font-medium text-slate-800">
                                    {{ $log->description }}
                                </div>
                                <div class="text-xs text-slate-500 truncate">
                                    {{ $log->subject_type ? class_basename($log->subject_type) : '' }} #{{ $log->subject_id }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                 </x-ui.card>

             </div>

        </div>
    </div>
</x-app-layout>
