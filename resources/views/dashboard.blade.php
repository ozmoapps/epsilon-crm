<x-app-layout>
    @php
        $statusVariants = [
            'draft' => 'neutral',
            'planned' => 'info',
            'confirmed' => 'info',
            'started' => 'info',
            'in_progress' => 'info',
            'on_hold' => 'neutral',
            'completed' => 'success',
            'delivered' => 'success',
            'cancelled' => 'danger',
            'sent' => 'info',
            'accepted' => 'success',
        ];

        // Access Contract
        $isPlatformAdmin = auth()->check() && auth()->user()->is_admin;
        $hasSupportSession = session('support_session_id');
        $showTenantMenu = !$isPlatformAdmin || ($isPlatformAdmin && $hasSupportSession);
        
        $isTenantAdmin = false;
        if(auth()->check() && session('current_tenant_id')) {
             $isTenantAdmin = auth()->user()->tenants()
                ->where('tenants.id', session('current_tenant_id'))
                ->wherePivot('role', 'admin')
                ->exists();
        }
        
        $canSeeFinance = $showTenantMenu && ($isTenantAdmin || ($isPlatformAdmin && $hasSupportSession));
    @endphp

    @if($is_onboarding ?? false)
        <x-slot name="header">
             <x-ui.page-header title="{{ __('Başlangıç') }}" subtitle="{{ __('Hoşgeldiniz') }}" />
        </x-slot>

        <div class="py-12">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white rounded-2xl border border-slate-100 p-8 text-center shadow-sm">
                    <div class="mx-auto w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                         <x-icon.office-building class="w-8 h-8 text-slate-400" />
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">{{ __('Henüz bir firmaya üye değilsiniz') }}</h3>
                    <p class="text-slate-500 mb-8 max-w-md mx-auto">{{ __('Bu hesaba bağlı firma bulunamadı. Yeni bir firma oluşturarak başlayabilirsiniz.') }}</p>
                    
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <x-ui.button href="{{ route('onboarding.company.create') }}" variant="primary">
                            {{ __('Firma Oluştur') }}
                        </x-ui.button>
                        
                         <a href="{{ route('manage.tenants.join') }}" class="text-sm font-medium text-slate-500 hover:text-slate-800 transition-colors">
                            {{ __('Davet Bağlantım Var') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
    <x-slot name="header">
        <x-ui.page-header title="{{ __('Dashboard') }}" subtitle="{{ __('Genel durum ve hızlı işlemler.') }}">
            <x-slot name="actions">
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.button href="{{ route('quotes.create') }}" variant="secondary" size="sm">
                        {{ __('Yeni Teklif') }}
                    </x-ui.button>
                    @if($canSeeFinance)
                    <x-ui.button href="{{ route('invoices.create') }}" variant="secondary" size="sm">
                        {{ __('Yeni Fatura') }}
                    </x-ui.button>
                     <x-ui.button href="{{ route('payments.create') }}" variant="secondary" size="sm">
                        {{ __('Tahsilat Ekle') }}
                    </x-ui.button>
                    @endif
                </div>
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
            openInvoices: {{ $canSeeFinance ? json_encode($openInvoices) : '[]' }},
            overdueInvoices: {{ $canSeeFinance ? json_encode($overdueInvoices) : '[]' }},
            advances: {{ $canSeeFinance ? json_encode($advances) : '[]' }},
            bankBalances: {{ $canSeeFinance ? json_encode($bankBalances) : '[]' }},
            finance: {{ $canSeeFinance ? json_encode($financeStats) : json_encode(['invoiced' => [], 'collected' => []]) }},
            
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
        @if($canSeeFinance)
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
        @endif


        {{-- Operations Dashboard --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            {{-- Today --}}
            <x-ui.card>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                        {{ __('Bugün Başlayanlar') }}
                    </h3>
                     <x-ui.button href="{{ route('work-orders.index', ['status' => 'planned']) }}" variant="ghost" size="sm" class="text-xs">
                        {{ __('Tümü') }}
                    </x-ui.button>
                </div>
                <div class="space-y-3">
                    @forelse($todaysWorkOrders as $wo)
                        <a href="{{ route('work-orders.show', $wo) }}" class="block p-3 rounded-xl border border-slate-100 hover:border-blue-200 hover:bg-slate-50 transition-all group">
                            <div class="flex justify-between items-start mb-1">
                                <span class="text-sm font-semibold text-slate-900 group-hover:text-blue-600 transition-colors">{{ $wo->title }}</span>
                                <x-ui.badge :variant="$statusVariants[$wo->status] ?? 'neutral'">{{ $wo->status_label }}</x-ui.badge>
                            </div>
                            <div class="text-xs text-slate-500">
                                {{ $wo->customer?->name }} • {{ $wo->vessel?->name }}
                            </div>
                        </a>
                    @empty
                         <p class="text-xs text-slate-400 italic text-center py-4">{{ __('Bugün için planlanan iş yok.') }}</p>
                    @endforelse
                </div>
            </x-ui.card>

             {{-- Overdue --}}
            <x-ui.card>
                 <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-rose-500"></div>
                        {{ __('Geciken İşler') }}
                    </h3>
                     {{-- Ideally link to filter overdue, currently manual query param or status filter --}}
                     <x-ui.button href="{{ route('work-orders.index', ['status' => 'in_progress']) }}" variant="ghost" size="sm" class="text-xs">
                        {{ __('Tümü') }}
                    </x-ui.button>
                </div>
                <div class="space-y-3">
                    @forelse($overdueWorkOrders as $wo)
                        <a href="{{ route('work-orders.show', $wo) }}" class="block p-3 rounded-xl border border-slate-100 hover:border-rose-200 hover:bg-rose-50/30 transition-all group">
                            <div class="flex justify-between items-start mb-1">
                                <span class="text-sm font-semibold text-slate-900 group-hover:text-rose-600 transition-colors">{{ $wo->title }}</span>
                                <span class="text-[10px] font-medium text-rose-500">
                                    {{ $wo->planned_end_at ? $wo->planned_end_at->format('d/m') : '-' }}
                                </span>
                            </div>
                            <div class="text-xs text-slate-500 mb-1">
                                {{ $wo->customer?->name }} • {{ $wo->vessel?->name }}
                            </div>
                             <div class="flex items-center gap-1">
                                <x-ui.badge :variant="$statusVariants[$wo->status] ?? 'neutral'">{{ $wo->status_label }}</x-ui.badge>
                            </div>
                        </a>
                    @empty
                         <p class="text-xs text-slate-400 italic text-center py-4">{{ __('Geciken iş yok.') }}</p>
                    @endforelse
                </div>
            </x-ui.card>

             {{-- On Hold --}}
            <x-ui.card>
                 <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                         <div class="w-2 h-2 rounded-full bg-slate-400"></div>
                        {{ __('Beklemedeki İşler') }}
                    </h3>
                     <x-ui.button href="{{ route('work-orders.index', ['status' => 'on_hold']) }}" variant="ghost" size="sm" class="text-xs">
                        {{ __('Tümü') }}
                    </x-ui.button>
                </div>
                 <div class="space-y-3">
                    @forelse($onHoldWorkOrders as $wo)
                        <a href="{{ route('work-orders.show', $wo) }}" class="block p-3 rounded-xl border border-slate-100 hover:border-slate-300 hover:bg-slate-50 transition-all group">
                             <div class="flex justify-between items-start mb-1">
                                <span class="text-sm font-semibold text-slate-900">{{ $wo->title }}</span>
                                <x-ui.badge :variant="$statusVariants[$wo->status] ?? 'neutral'">{{ $wo->status_label }}</x-ui.badge>
                            </div>
                            <div class="text-xs text-slate-500">
                                {{ $wo->customer?->name }} • {{ $wo->vessel?->name }}
                            </div>
                        </a>
                    @empty
                         <p class="text-xs text-slate-400 italic text-center py-4">{{ __('Beklemede iş yok.') }}</p>
                    @endforelse
                </div>
            </x-ui.card>
        </div>

        {{-- Recent Operations Table (PR8) --}}
        <x-ui.card>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-slate-900">{{ __('Son Operasyonlar') }}</h3>
                <x-ui.button href="{{ route('sales-orders.index') }}" variant="ghost" size="sm" class="text-xs">
                    {{ __('Tümünü Gör') }}
                </x-ui.button>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-xs text-slate-500 border-b border-slate-100">
                            <th class="py-2 pl-2 font-medium">{{ __('Sipariş No') }}</th>
                            <th class="py-2 font-medium">{{ __('Müşteri') }}</th>
                            <th class="py-2 font-medium">{{ __('Tekne') }}</th>
                            <th class="py-2 font-medium">{{ __('Durum') }}</th>
                            <th class="py-2 pr-2 font-medium text-right">{{ __('Sonraki Adım') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($recentOperations as $order)
                        <tr class="hover:bg-slate-50 transition-colors cursor-pointer group" onclick="window.location='{{ route('sales-orders.show', $order) }}'">
                            <td class="py-3 pl-2 text-xs font-semibold text-slate-700 group-hover:text-blue-600">
                                {{ $order->order_no }}
                            </td>
                            <td class="py-3 text-xs text-slate-600">
                                {{ $order->customer->name ?? '-' }}
                            </td>
                             <td class="py-3 text-xs text-slate-600">
                                {{ $order->vessel->name ?? '-' }}
                            </td>
                            <td class="py-3 text-xs">
                                <x-ui.badge :variant="$statusVariants[$order->status] ?? 'neutral'">{{ $order->status_label }}</x-ui.badge>
                            </td>
                            <td class="py-3 pr-2 text-right">
                                <x-ui.badge :variant="$order->next_step_variant" class="!px-1.5 !py-0.5 text-[10px]">
                                    {{ $order->next_step_label }}
                                </x-ui.badge>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-xs text-slate-400 italic">
                                {{ __('Kayıt bulunamadı.') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>

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
                            <div class="text-2xl font-bold text-slate-700">{{ $workOrderStats->planned }}</div>
                            <div class="text-xs text-slate-500 mt-1">{{ __('Planlanan') }}</div>
                        </div>
                        <div class="p-3 bg-amber-50 rounded-xl border border-amber-100 text-center">
                            <div class="text-2xl font-bold text-amber-700">{{ $workOrderStats->in_progress }}</div>
                            <div class="text-xs text-amber-600 mt-1">{{ __('Devam Eden') }}</div>
                        </div>
                         <div class="p-3 bg-purple-50 rounded-xl border border-purple-100 text-center">
                            <div class="text-2xl font-bold text-purple-700">{{ $workOrderStats->completed }}</div>
                            <div class="text-xs text-purple-600 mt-1">{{ __('Tamamlanan') }}</div>
                        </div>
                    </div>
                 </x-ui.card>

                {{-- Finance Summary (30 Days) --}}
                 @if($canSeeFinance)
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
                 @endif


            </div>

             {{-- Right Sidebar --}}
             <div class="space-y-6">
                
                    {{-- Operations Summary (PR8) --}}
                    <x-ui.card>
                        <h3 class="text-sm font-bold text-slate-900 mb-3">{{ __('Operasyon Özeti') }}</h3>
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div class="p-2 bg-slate-50 rounded-lg border border-slate-100 flex flex-col items-center text-center">
                                <span class="text-[10px] text-slate-500 uppercase font-bold">{{ __('Açık Sipariş') }}</span>
                                <div class="mt-1">
                                    <x-ui.badge variant="info" class="!text-xs">{{ $opOpenSalesOrders }}</x-ui.badge>
                                </div>
                            </div>
                            <div class="p-2 bg-slate-50 rounded-lg border border-slate-100 flex flex-col items-center text-center">
                                <span class="text-[10px] text-slate-500 uppercase font-bold">{{ __('Açık İş Emri') }}</span>
                                <div class="mt-1">
                                    <x-ui.badge variant="info" class="!text-xs">{{ $opOpenWorkOrders }}</x-ui.badge>
                                </div>
                            </div>
                             <div class="p-2 bg-slate-50 rounded-lg border border-slate-100 flex flex-col items-center text-center">
                                <span class="text-[10px] text-slate-500 uppercase font-bold">{{ __('Foto Eksik') }}</span>
                                <div class="mt-1">
                                    @if($opMissingPhotos > 0)
                                        <x-ui.badge variant="info" class="!text-xs">{{ $opMissingPhotos }}</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="success" class="!text-xs">OK</x-ui.badge>
                                    @endif
                                </div>
                            </div>
                            <div class="p-2 bg-slate-50 rounded-lg border border-slate-100 flex flex-col items-center text-center">
                                <span class="text-[10px] text-slate-500 uppercase font-bold">{{ __('Teslim Bekleyen') }}</span>
                                <div class="mt-1">
                                    <x-ui.badge variant="neutral" class="!text-xs">{{ $opPendingDelivery }}</x-ui.badge>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                             <a href="{{ route('sales-orders.index') }}" class="block text-xs font-medium text-blue-600 hover:text-blue-800 hover:bg-blue-50 p-2 rounded transition-colors flex items-center justify-between">
                                {{ __('Satış Siparişleri') }}
                                <x-icon.chevron-right class="h-3 w-3" />
                            </a>
                             <a href="{{ route('work-orders.index') }}" class="block text-xs font-medium text-blue-600 hover:text-blue-800 hover:bg-blue-50 p-2 rounded transition-colors flex items-center justify-between">
                                {{ __('İş Emirleri') }}
                                <x-icon.chevron-right class="h-3 w-3" />
                            </a>
                             <a href="{{ route('contracts.index') }}" class="block text-xs font-medium text-blue-600 hover:text-blue-800 hover:bg-blue-50 p-2 rounded transition-colors flex items-center justify-between">
                                {{ __('Sözleşmeler') }}
                                <x-icon.chevron-right class="h-3 w-3" />
                            </a>
                        </div>
                    </x-ui.card>

                    {{-- Critical Alerts --}}
                    @if($canSeeFinance && $criticalOverdueInvoices->isNotEmpty())
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
    @endif
</x-app-layout>
