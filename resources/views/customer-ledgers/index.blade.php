<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="{{ __('Cari Hesaplar') }}" />
    </x-slot>

    @php
        // Sprint 3.18.1 mini: tooltip text (native title)
        $calcNote = __('Hesaplama Notu: Açık Fatura = issued ve paid olmayan faturaların kalan tutarı. Kalan = Toplam - (Faturaya bağlı tahsilatlar) - (Avans dağıtımları). Avans = Faturasız tahsilat - dağıtılmış tutar. ÖNEMLİ: payment_allocations sadece AVANS ödemelerden sayılır (payments.invoice_id IS NULL). Vadesi Geçmiş = due_date < bugün ve kalan > 0.');
        $openInvHeaderNote = __('Kalan = Toplam - Tahsilatlar - Avans Dağıtımları. Avans Dağıtımları sadece payments.invoice_id IS NULL (advance) ödemelerden sayılır.');
    @endphp

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

            {{-- Filter Bar --}}
            <div class="mb-6 bg-white p-4 shadow-soft rounded-xl border border-slate-100">
                <form id="filter-form" method="GET" action="{{ route('customer-ledgers.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">

                    {{-- Search --}}
                    <div class="md:col-span-1">
                        <x-input-label for="search" :value="__('Arama')" />
                        <x-text-input
                            id="search"
                            name="search"
                            type="text"
                            class="mt-1 block w-full text-sm rounded-xl"
                            :value="$request->search"
                            placeholder="Müşteri adı, e-posta, telefon, vergi no…"
                        />
                    </div>

                    {{-- Currency --}}
                    <div class="md:col-span-1">
                        <x-input-label for="currency" :value="__('Para Birimi')" />
                        <x-select name="currency" id="currency" class="mt-1 rounded-xl">
                            <option value="">{{ __('Tümü') }}</option>
                            @foreach($currencies as $code)
                                <option value="{{ $code }}" {{ $request->currency === $code ? 'selected' : '' }}>
                                    {{ $code }}
                                </option>
                            @endforeach
                        </x-select>
                    </div>

                    {{-- Checkboxes --}}
                    <div class="md:col-span-2 flex items-center gap-4 py-2">
                        <div class="flex items-center">
                            <x-checkbox
                                id="only_nonzero"
                                name="only_nonzero"
                                value="1"
                                :checked="$request->only_nonzero"
                            />
                            <label for="only_nonzero" class="ml-2 block text-sm text-slate-900">
                                {{ __('Bakiyesi olanlar') }}
                            </label>
                            <span class="ml-2 text-xs text-slate-400" title="Sıfır bakiyeli müşterileri gizler. Para birimi seçerseniz daha net sonuç verir.">?</span>
                        </div>

                        <div class="flex items-center">
                            <x-checkbox
                                id="only_open_advances"
                                name="only_open_advances"
                                value="1"
                                :checked="$request->only_open_advances"
                            />
                            <label for="only_open_advances" class="ml-2 block text-sm text-slate-900">
                                {{ __('Açık avansı olanlar') }}
                            </label>
                            <span class="ml-2 text-xs text-slate-400" title="Faturaya dağıtılabilir avansı (kalan > 0) olan müşterileri gösterir.">?</span>
                        </div>
                    </div>

                    {{-- Sorting --}}
                    <div class="md:col-span-1">
                        <x-input-label for="sort" :value="__('Sıralama')" />
                        <x-select name="sort" id="sort" class="mt-1 rounded-xl">
                            <option value="open_invoice_desc" {{ $request->sort === 'open_invoice_desc' ? 'selected' : '' }}>{{ __('Açık Fatura (Yüksek > Düşük)') }}</option>
                            <option value="name_asc" {{ $request->sort === 'name_asc' ? 'selected' : '' }}>{{ __('İsim (A-Z)') }}</option>
                            <option value="debt_desc" {{ $request->sort === 'debt_desc' ? 'selected' : '' }}>{{ __('Borçlu Bakiye (Yüksek > Düşük)') }}</option>
                            <option value="advance_desc" {{ $request->sort === 'advance_desc' ? 'selected' : '' }}>{{ __('Avans (Yüksek > Düşük)') }}</option>
                        </x-select>
                    </div>

                    {{-- Buttons & Saved Views --}}
                    <div class="md:col-span-1 flex gap-2 justify-end items-start">
                        {{-- Saved Views Dropdown --}}
                        <div class="relative">
                            <x-ui.dropdown align="right" width="w-60">
                                <x-slot name="trigger">
                                    <x-ui.button variant="secondary" class="shadow-soft">
                                        {{ __('Görünüm') }}
                                        <x-icon.chevron-down-small class="ml-2 -mr-0.5 h-4 w-4 text-slate-500" />
                                    </x-ui.button>
                                </x-slot>

                                <x-slot name="content">
                                    <div class="px-4 py-2 text-xs text-slate-500">
                                        {{ __('Filtrelerini kaydet, tek tıkla geri dön.') }}
                                    </div>
                                    <div class="border-t border-slate-100"></div>

                                    <x-dropdown-link :href="route('customer-ledgers.index')">
                                        {{ __('Varsayılan') }}
                                    </x-dropdown-link>

                                    @php
                                        // Filter out excluded params for Determinism
                                        $currentQueryRaw = request()->except(['page', '_token', '_method']);
                                        ksort($currentQueryRaw);
                                        $currentQueryJson = json_encode($currentQueryRaw);
                                    @endphp

                                    @foreach($savedViews as $view)
                                        @php
                                            $vQuery = json_decode($view->query, true) ?? [];
                                            ksort($vQuery);
                                            $isActive = json_encode($vQuery) === $currentQueryJson;
                                        @endphp
                                        <div class="flex items-center justify-between px-4 py-2 text-sm leading-5 group transition duration-150 ease-in-out {{ $isActive ? 'bg-brand-50 text-brand-700 font-medium' : 'text-slate-700 hover:bg-slate-100/70' }}">
                                            <a href="{{ route('customer-ledgers.index', $view->query) }}" 
                                               class="flex-1 truncate mr-2 flex items-center gap-2"
                                               title="{{ $view->name }}">
                                                @if($isActive)
                                                    <x-icon.check class="w-3.5 h-3.5 text-brand-600 shrink-0" />
                                                @else
                                                    <span class="w-3.5 h-3.5"></span>
                                                @endif
                                                <span class="truncate">{{ $view->name }}</span>
                                            </a>
                                            <form action="{{ route('saved-views.destroy', $view) }}" method="POST" onsubmit="return confirm('{{ __('Bu görünümü silmek istediğinize emin misiniz? Bu işlem geri alınamaz.') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        aria-label="{{ __('Görünümü sil') }}"
                                                        class="p-1 rounded-md text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors focus:outline-none focus:ring-2 focus:ring-rose-500/20">
                                                    <x-icon.trash class="w-4 h-4" />
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach

                                    <div class="border-t border-slate-100 my-1"></div>

                                    <button x-data x-on:click.prevent="$dispatch('open-modal', 'save-view-modal')" 
                                            class="w-full text-left block px-4 py-2.5 text-sm leading-5 text-brand-700 hover:bg-brand-50 hover:text-brand-800 focus:outline-none focus:bg-brand-50 transition duration-150 ease-in-out font-medium flex items-center gap-2">
                                        <x-icon.plus class="w-4 h-4" />
                                        {{ __('Görünümü Kaydet') }}
                                    </button>
                                </x-slot>
                            </x-ui.dropdown>
                        </div>

                        <x-ui.button variant="secondary" type="button" onclick="window.location='{{ route('customer-ledgers.index') }}'">
                            {{ __('Temizle') }}
                        </x-ui.button>
                        <x-ui.button variant="primary" type="submit">
                            {{ __('Uygula') }}
                        </x-ui.button>
                    </div>
                </form>

                {{-- Save View Modal --}}
                <x-modal name="save-view-modal" :show="false" focusable>
                    <form method="POST" action="{{ route('saved-views.store') }}">
                        @csrf
                        <div class="p-6">
                            <h2 class="text-lg font-medium text-slate-900">
                                {{ __('Görünümü Kaydet') }}
                            </h2>
                            <p class="mt-1 text-sm text-slate-600">
                                {{ __('Mevcut filtrelerinizi daha sonra hızlıca kullanmak için kaydedin.') }}
                            </p>

                            <input type="hidden" name="scope" value="customer_ledgers">
                            <input type="hidden" name="query" value="{{ $currentQueryJson }}">

                            <div class="mt-6">
                                <x-input-label for="view_name" value="{{ __('Görünüm Adı') }}" />
                                <x-text-input 
                                    id="view_name" 
                                    name="name" 
                                    type="text" 
                                    class="mt-1 block w-full" 
                                    required 
                                    minlength="3"
                                    maxlength="60"
                                    placeholder="{{ __('Örn: EUR Borçlular') }}" 
                                    x-ref="nameInput"
                                />
                                <p class="mt-1 text-xs text-slate-500">{{ __('Kısa ve açıklayıcı bir isim verin.') }}</p>
                            </div>

                            <div class="mt-6 flex justify-end gap-3">
                                <x-ui.button type="button" variant="secondary" x-on:click="$dispatch('close-modal', 'save-view-modal')">
                                    {{ __('İptal') }}
                                </x-ui.button>
                                <x-ui.button type="submit">
                                    {{ __('Kaydet') }}
                                </x-ui.button>
                            </div>
                        </div>
                    </form>
                </x-modal>



                {{-- Quick Filters (Sprint 3.9) --}}
                <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap items-center gap-2">
                    <span class="text-sm font-medium text-slate-700 mr-1">{{ __('Hızlı Filtre:') }}</span>

                    {{-- Open Invoice --}}
                    @php
                        $isQuickInv = request('quick') === 'open_invoice';
                        $urlInv = $isQuickInv
                            ? request()->fullUrlWithQuery(['quick' => null, 'page' => null])
                            : request()->fullUrlWithQuery(['quick' => 'open_invoice', 'page' => null]);
                    @endphp
                    <a href="{{ $urlInv }}" class="transition-colors duration-200">
                        <x-ui.badge :variant="$isQuickInv ? 'danger' : 'neutral'" class="!px-3 !py-1 text-xs font-semibold {{ $isQuickInv ? '' : 'hover:bg-slate-100' }}">
                            {{ __('Açık Fatura') }}
                        </x-ui.badge>
                    </a>

                    {{-- Open Advance --}}
                    @php
                        $isQuickAdv = request('quick') === 'open_advance';
                        $urlAdv = $isQuickAdv
                            ? request()->fullUrlWithQuery(['quick' => null, 'page' => null])
                            : request()->fullUrlWithQuery(['quick' => 'open_advance', 'page' => null]);
                    @endphp
                    <a href="{{ $urlAdv }}" class="transition-colors duration-200">
                        <x-ui.badge :variant="$isQuickAdv ? 'info' : 'neutral'" class="!px-3 !py-1 text-xs font-semibold {{ $isQuickAdv ? '' : 'hover:bg-slate-100' }}">
                            {{ __('Avans') }}
                        </x-ui.badge>
                    </a>

                    {{-- Debtor --}}
                    @php
                        $isQuickDebt = request('quick') === 'debtor';
                        $urlDebt = $isQuickDebt
                            ? request()->fullUrlWithQuery(['quick' => null, 'page' => null])
                            : request()->fullUrlWithQuery(['quick' => 'debtor', 'page' => null]);
                    @endphp
                    <a href="{{ $urlDebt }}" class="transition-colors duration-200">
                        <x-ui.badge :variant="$isQuickDebt ? 'danger' : 'neutral'" class="!px-3 !py-1 text-xs font-semibold {{ $isQuickDebt ? '' : 'hover:bg-slate-100' }}">
                            {{ __('Borçlu') }}
                        </x-ui.badge>
                    </a>

                    {{-- Overdue (Sprint 3.11) --}}
                    @php
                        $isQuickOverdue = request('quick') === 'overdue';
                        $urlOverdue = $isQuickOverdue
                            ? request()->fullUrlWithQuery(['quick' => null, 'page' => null])
                            : request()->fullUrlWithQuery(['quick' => 'overdue', 'page' => null]);
                    @endphp
                    <a href="{{ $urlOverdue }}" class="transition-colors duration-200">
                        <x-ui.badge :variant="$isQuickOverdue ? 'danger' : 'neutral'" class="!px-3 !py-1 text-xs font-semibold {{ $isQuickOverdue ? '' : 'hover:bg-slate-100' }}">
                            {{ __('Vadesi Geçmiş') }}
                        </x-ui.badge>
                    </a>
                </div>
            </div>

            {{-- Mini KPI Bar --}}
            @if(isset($kpi) && ($kpi['open_invoice_customers'] > 0 || $kpi['open_advance_customers'] > 0 || $kpi['debt_customers'] > 0))
                <div class="mb-6 bg-white p-4 shadow-soft rounded-xl border border-slate-100 flex flex-wrap gap-6 items-center text-sm">
                    {{-- This Page --}}
                    <div class="flex items-center gap-4">
                        <div class="flex bg-slate-100 rounded-xl p-1 items-center gap-1 text-xs">
                            <a href="{{ request()->fullUrlWithQuery(['scope' => 'page', 'page' => null]) }}"
                               class="px-2 py-0.5 rounded-lg {{ request('scope', 'page') === 'page' ? 'bg-white shadow-soft text-slate-800 font-semibold' : 'text-slate-500 hover:text-slate-700' }}">
                               {{ __('Bu Sayfa') }}
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['scope' => 'all', 'page' => null]) }}"
                               class="px-2 py-0.5 rounded-lg {{ request('scope') === 'all' ? 'bg-white shadow-soft text-slate-800 font-semibold' : 'text-slate-500 hover:text-slate-700' }}">
                               {{ __('Tüm Sonuçlar') }}
                            </a>
                        </div>

                        {{-- Sprint 3.18.1 mini: real hover tooltip --}}
                        <span class="inline-flex items-center gap-1 text-slate-400" title="{{ $calcNote }}">
                            <x-icon.info class="w-4 h-4" />
                            <span class="text-xs hover:text-slate-600 hover:underline">{{ __('Hesaplama Notu') }}</span>
                        </span>

                        @php
                            // Drilldown Contract Hardening
                            // 1. Currency: Propagate if selected
                            $drillParams = array_filter(['currency' => request('currency')]);
                            
                            // 2. Search: Map strictly to 'q' for Invoices (InvoiceController accepts q)
                            // Payments does NOT support search text, so we ignore it there.
                            $drillSearch = request('search') ? ['q' => request('search')] : [];

                            // 3. Specific Flags
                            $drillInv = array_merge($drillParams, $drillSearch, ['open' => 1]); 
                            $drillAdv = array_merge($drillParams, ['only_open' => 1]); // No search for payments
                            $drillOver = array_merge($drillParams, $drillSearch, ['overdue' => 1]);
                        @endphp

                        <a href="{{ route('invoices.index', $drillInv) }}" class="flex items-center gap-2 hover:bg-slate-100 rounded-xl px-2 py-1 transition" title="{{ __('Detaya git') }}">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            <span class="text-slate-600">{{ __('Açık Fatura:') }}</span>
                            <span class="font-bold text-slate-900">{{ $kpi['open_invoice_customers'] }}</span>
                        </a>

                        <a href="{{ route('payments.index', $drillAdv) }}" class="flex items-center gap-2 hover:bg-slate-100 rounded-xl px-2 py-1 transition" title="{{ __('Detaya git') }}">
                            <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                            <span class="text-slate-600">{{ __('Avans:') }}</span>
                            <span class="font-bold text-slate-900">{{ $kpi['open_advance_customers'] }}</span>
                        </a>

                        <a href="{{ request()->fullUrlWithQuery(['quick' => 'debtor']) }}" class="flex items-center gap-2 hover:bg-slate-100 rounded-xl px-2 py-1 transition" title="{{ __('Detaya git') }}">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            <span class="text-slate-600">{{ __('Borçlu:') }}</span>
                            <span class="font-bold text-slate-900">{{ $kpi['debt_customers'] }}</span>
                        </a>

                        @if(isset($kpi['overdue_customers']) && $kpi['overdue_customers'] > 0)
                            <div class="flex items-center gap-2 ml-2">
                                <a href="{{ route('invoices.index', $drillOver) }}" class="flex items-center gap-2 hover:bg-slate-100 rounded-xl px-2 py-1 transition" title="{{ __('Detaya git') }}">
                                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                    <span class="text-slate-600">{{ __('Vadesi Geçmiş:') }}</span>
                                    <span class="font-bold text-slate-900">{{ $kpi['overdue_customers'] }} {{ __('müşteri') }}</span>
                                </a>

                                {{-- Overdue Amount in KPI (Sprint 3.12) --}}
                                @if($request->currency && isset($totals['overdue_invoices'][$request->currency]))
                                    <span class="text-slate-400">·</span>
                                    <span class="font-semibold text-rose-700">
                                        {{ number_format($totals['overdue_invoices'][$request->currency]['amount'] ?? 0, 2, ',', '.') }} {{ $request->currency }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Total Results (Divider) --}}
                    @if(isset($grandKpis))
                        <div class="hidden md:block w-px h-6 bg-slate-200 mx-2"></div>

                        <div class="flex items-center gap-4">
                            <span class="font-semibold text-slate-500 uppercase tracking-wider text-xs">{{ __('Toplam Sonuçlar:') }}</span>

                            <div class="flex items-center gap-2" title="{{ __('Tüm sonuçlar içinde') }}">
                                <span class="text-slate-600">{{ __('Açık Fatura:') }}</span>
                                <span class="font-bold text-slate-900">{{ $grandKpis['open_invoice_customers'] }}</span>
                            </div>
                            <div class="flex items-center gap-2" title="{{ __('Tüm sonuçlar içinde') }}">
                                <span class="text-slate-600">{{ __('Avans:') }}</span>
                                <span class="font-bold text-slate-900">{{ $grandKpis['open_advance_customers'] }}</span>
                            </div>
                            <div class="flex items-center gap-2" title="{{ __('Tüm sonuçlar içinde') }}">
                                <span class="text-slate-600">{{ __('Borçlu:') }}</span>
                                <span class="font-bold text-slate-900">{{ $grandKpis['debt_customers'] }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Table --}}
            <div class="bg-white shadow-soft rounded-xl border border-slate-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <x-ui.table>
                        <thead>
                            <tr>
                                <th class="px-4 py-3">{{ __('Müşteri') }}</th>
                                <th class="px-4 py-3">
                                    <span class="flex flex-col">
                                        <span>{{ __('Bakiye') }}</span>
                                        <span class="text-[0.65rem] lowercase font-normal text-slate-400">{{ __('Borçlu: Tahsil edilecek • Alacaklı: Ödenecek') }}</span>
                                    </span>
                                </th>
                                <th class="px-4 py-3">
                                    <span class="flex flex-col">
                                        <span>{{ __('Kullanılabilir Avans') }}</span>
                                        <span class="text-[0.65rem] lowercase font-normal text-slate-400">{{ __('Otomatik mahsuplaşma için hazır') }}</span>
                                    </span>
                                </th>
                                <th class="px-4 py-3">
                                    <span class="flex flex-col">
                                        <span class="inline-flex items-center gap-1 cursor-help" title="{{ $openInvHeaderNote }}">
                                            {{ __('Açık Fatura (Tutar)') }}
                                            <x-icon.info class="w-3 h-3 text-slate-400" />
                                        </span>
                                        <span class="text-[0.65rem] lowercase font-normal text-slate-400">{{ __('Ödenmemiş kalan bakiye') }}</span>
                                    </span>
                                </th>
                                <th class="px-4 py-3 text-right">{{ __('İşlemler') }}</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($customers as $customer)
                                @php
                                    $selectedCurrency = $request->currency ?: null;
                                    $hasOpenAdvance = !empty($customer->open_advances) &&
                                        ($selectedCurrency
                                            ? (isset($customer->open_advances[$selectedCurrency]) && $customer->open_advances[$selectedCurrency] > 0.001)
                                            : collect($customer->open_advances)->max() > 0.001
                                        );
                                @endphp

                                <tr class="bg-white">
                                    {{-- Customer Name --}}
                                    <td class="px-4 py-3 font-medium text-slate-900">
                                        <a href="{{ route('customers.show', $customer) }}" class="hover:underline text-brand-600">
                                            {{ $customer->name }}
                                        </a>
                                        <div class="text-xs text-slate-500">{{ $customer->email ?? '-' }}</div>
                                    </td>

                                    {{-- Balances --}}
                                    <td class="px-4 py-3">
                                        @if(isset($customer->balances) && $customer->balances->isNotEmpty())
                                            <div class="flex flex-col gap-1 items-start">
                                                @foreach($customer->balances as $bal)
                                                    @if(!$selectedCurrency || $selectedCurrency === $bal->currency)
                                                        @php
                                                            $val = (float)$bal->balance;
                                                            $isDebt = $val > 0.001;
                                                            $isCredit = $val < -0.001;
                                                        @endphp

                                                        @if($isDebt)
                                                            <a href="{{ route('customers.ledger', ['customer' => $customer->id, 'currency' => $bal->currency]) }}" class="transition" title="{{ __('Müşteri size borçlu') }}">
                                                                <x-ui.badge variant="danger" class="!px-2 !py-1 text-xs font-semibold hover:bg-rose-100">
                                                                    {{ number_format(abs($val), 2, ',', '.') }} {{ $bal->currency }} · {{ __('Borçlu') }}
                                                                </x-ui.badge>
                                                            </a>
                                                        @elseif($isCredit)
                                                            <a href="{{ route('customers.ledger', ['customer' => $customer->id, 'currency' => $bal->currency]) }}" class="transition" title="{{ __('Müşteriye borcunuz var') }}">
                                                                <x-ui.badge variant="success" class="!px-2 !py-1 text-xs font-semibold hover:bg-emerald-100">
                                                                    {{ number_format(abs($val), 2, ',', '.') }} {{ $bal->currency }} · {{ __('Alacaklı') }}
                                                                </x-ui.badge>
                                                            </a>
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-slate-400 text-xs">-</span>
                                        @endif
                                    </td>

                                    {{-- Open Advances --}}
                                    <td class="px-4 py-3">
                                        @if(!empty($customer->open_advances))
                                            <div class="flex flex-col gap-1 items-start">
                                                @foreach($customer->open_advances as $curr => $amt)
                                                    @if(!$selectedCurrency || $selectedCurrency === $curr)
                                                        @if((float)$amt > 0.001)
                                                            <a href="{{ route('payments.index', ['customer_id' => $customer->id, 'only_open' => 1, 'currency' => $curr]) }}" class="transition" title="{{ __('Kullanılabilir avans: otomatik mahsuplaşma için hazır kredi') }}">
                                                                <x-ui.badge variant="info" class="!px-2 !py-1 text-xs font-medium hover:bg-sky-100">
                                                                    {{ number_format((float)$amt, 2, ',', '.') }} {{ $curr }} · {{ __('Avans') }}
                                                                </x-ui.badge>
                                                            </a>
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-slate-400 text-xs">-</span>
                                        @endif
                                    </td>

                                    {{-- Open Invoice Amount --}}
                                    <td class="px-4 py-3">
                                        @if(!empty($customer->open_invoice_amounts))
                                            <div class="flex flex-col gap-1 items-start">
                                                @foreach($customer->open_invoice_amounts as $curr => $data)
                                                    @if(!$selectedCurrency || $selectedCurrency === $curr)
                                                        @php
                                                            $amt = (float)($data['amount'] ?? 0);
                                                            $isOverdue = false;
                                                            if (isset($customer->overdue_invoice_amounts[$curr]) && $customer->overdue_invoice_amounts[$curr]['amount'] > 0.001) {
                                                                $isOverdue = true;
                                                            }
                                                        @endphp
                                                        @if($amt > 0.001)
                                                            <div class="flex flex-col items-start gap-1">
                                                                <a href="{{ route('invoices.index', ['customer_id' => $customer->id, 'open' => 1, 'currency' => $curr]) }}" class="transition" title="{{ __('Ödenmemiş (kalan) fatura bakiyesi') }}">
                                                                    <x-ui.badge variant="danger" class="!px-2 !py-1 text-xs font-medium hover:bg-rose-100">
                                                                        {{ number_format($amt, 2, ',', '.') }} {{ $curr }} · {{ __('Açık') }}
                                                                    </x-ui.badge>
                                                                </a>

                                                                @if($isOverdue)
                                                                    @php $odAmt = (float)($customer->overdue_invoice_amounts[$curr]['amount'] ?? 0); @endphp
                                                                    <a href="{{ route('invoices.index', ['customer_id' => $customer->id, 'overdue' => 1, 'currency' => $curr]) }}" class="transition" title="{{ __('Vadesi geçmiş tutar') }}">
                                                                        <x-ui.badge variant="danger" class="!px-2 !py-1 text-xs font-bold hover:bg-rose-100">
                                                                            {{ number_format($odAmt, 2, ',', '.') }} {{ $curr }} · {{ __('Vadesi Geçmiş') }}
                                                                        </x-ui.badge>
                                                                    </a>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-slate-400 text-xs">-</span>
                                        @endif
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-4 py-3 text-right">
                                        <x-ui.dropdown align="right" width="w-48">
                                            <x-slot name="trigger">
                                                <x-ui.button variant="secondary" size="sm" class="shadow-none border-transparent !px-3">
                                                    <div>{{ __('İşlemler') }}</div>
                                                    <div class="ml-1">
                                                        <x-icon.chevron-down-small class="h-4 w-4" />
                                                    </div>
                                                </x-ui.button>
                                            </x-slot>

                                            <x-slot name="content">
                                                <x-dropdown-link :href="route('customers.ledger', $customer)">
                                                    {{ __('Ekstre') }}
                                                </x-dropdown-link>

                                                <x-dropdown-link :href="route('payments.create', ['customer_id' => $customer->id])">
                                                    {{ __('Tahsilat Ekle') }}
                                                </x-dropdown-link>

                                                <x-dropdown-link :href="route('invoices.index', ['customer_id' => $customer->id])">
                                                    {{ __('Faturalar') }}
                                                </x-dropdown-link>

                                                @if($hasOpenAdvance)
                                                    <x-dropdown-link :href="route('payments.index', ['customer_id' => $customer->id, 'only_open' => 1])">
                                                        {{ __('Açık Avanslar') }}
                                                    </x-dropdown-link>
                                                @else
                                                    <span class="block px-4 py-2 text-xs text-slate-400 cursor-not-allowed" title="{{ __('Kullanılabilir avans yok') }}">
                                                        {{ __('Açık Avanslar') }}
                                                    </span>
                                                @endif
                                            </x-slot>
                                        </x-ui.dropdown>
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
                                            <x-ui.button variant="secondary" type="button" onclick="window.location='{{ route('customer-ledgers.index') }}'">
                                                {{ __('Temizle') }}
                                            </x-ui.button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                        {{-- Totals Row --}}
                        @if(!empty($totals) && $customers->count() > 0)
                            @php
                                $selectedCurrency = $request->currency ?: null;

                                $sumBalance = function($curr) use ($totals) {
                                    return (float)($totals['balances'][$curr] ?? 0);
                                };

                                $sumAdv = function($curr) use ($totals) {
                                    return (float)($totals['open_advances'][$curr] ?? 0);
                                };

                                $sumInvAmt = function($curr) use ($totals) {
                                    return (float)($totals['open_invoices'][$curr]['amount'] ?? 0);
                                };

                                $currencyKeys = collect(array_unique(array_merge(
                                    array_keys($totals['balances'] ?? []),
                                    array_keys($totals['open_advances'] ?? []),
                                    array_keys($totals['open_invoices'] ?? []),
                                    array_keys($totals['overdue_invoices'] ?? [])
                                )))->sort()->values();

                                $sumOverdue = function($curr) use ($totals) {
                                    return (float)($totals['overdue_invoices'][$curr]['amount'] ?? 0);
                                };
                            @endphp

                            <tfoot class="bg-slate-50 border-t-2 border-slate-200">
                                <tr>
                                    <td class="px-4 py-4 font-bold text-slate-800 text-right">
                                        {{ request('scope') === 'all' ? __('TOPLAM (Tüm Sonuçlar):') : __('TOPLAM (Bu Sayfa):') }}
                                    </td>

                                    {{-- Total Balance --}}
                                    <td class="px-4 py-4">
                                        <div class="flex flex-col gap-1 items-start">
                                            @foreach($currencyKeys as $curr)
                                                @if(!$selectedCurrency || $selectedCurrency === $curr)
                                                    @php $v = $sumBalance($curr); @endphp
                                                    @if(abs($v) > 0.001)
                                                        @if($v > 0)
                                                            <x-ui.badge variant="danger" class="!px-2 !py-1 text-xs font-bold">
                                                                {{ number_format(abs($v), 2, ',', '.') }} {{ $curr }} · {{ __('Borçlu') }}
                                                            </x-ui.badge>
                                                        @else
                                                            <x-ui.badge variant="success" class="!px-2 !py-1 text-xs font-bold">
                                                                {{ number_format(abs($v), 2, ',', '.') }} {{ $curr }} · {{ __('Alacaklı') }}
                                                            </x-ui.badge>
                                                        @endif
                                                    @endif
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>

                                    {{-- Total Open Advances --}}
                                    <td class="px-4 py-4">
                                        <div class="flex flex-col gap-1 items-start">
                                            @foreach($currencyKeys as $curr)
                                                @if(!$selectedCurrency || $selectedCurrency === $curr)
                                                    @php $v = $sumAdv($curr); @endphp
                                                    @if($v > 0.001)
                                                        <x-ui.badge variant="info" class="!px-2 !py-1 text-xs font-bold">
                                                            {{ number_format($v, 2, ',', '.') }} {{ $curr }} · {{ __('Avans') }}
                                                        </x-ui.badge>
                                                    @endif
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>

                                    {{-- Total Open Invoices --}}
                                    <td class="px-4 py-4">
                                        <div class="flex flex-col gap-1 items-start">
                                            @foreach($currencyKeys as $curr)
                                                @if(!$selectedCurrency || $selectedCurrency === $curr)
                                                    @php
                                                        $amt = $sumInvAmt($curr);
                                                        $odAmt = $sumOverdue($curr);
                                                    @endphp
                                                    @if($amt > 0.001)
                                                        <div class="flex flex-col items-start gap-1">
                                                            <x-ui.badge variant="danger" class="!px-2 !py-1 text-xs font-bold">
                                                                {{ number_format($amt, 2, ',', '.') }} {{ $curr }} · {{ __('Açık') }}
                                                            </x-ui.badge>
                                                            @if($odAmt > 0.001)
                                                                <x-ui.badge variant="danger" class="!px-2 !py-1 text-xs font-bold">
                                                                    {{ number_format($odAmt, 2, ',', '.') }} {{ $curr }} · {{ __('Vadesi Geçmiş') }}
                                                                </x-ui.badge>
                                                            @endif
                                                        </div>
                                                    @endif
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>

                                    <td class="px-4 py-4 text-right text-slate-400 text-xs"></td>
                                </tr>

                                {{-- Grand Totals Row --}}
                                @if(isset($grandTotals))
                                    <tr class="bg-brand-50/40 border-t border-brand-100">
                                        <td class="px-4 py-4 font-bold text-brand-900 text-right">
                                            {{ __('TOPLAM (Tüm Sonuçlar):') }}
                                        </td>

                                        {{-- Grand Balance --}}
                                        <td class="px-4 py-4">
                                            <div class="flex flex-col gap-1 items-start">
                                                @foreach($currencyKeys as $curr)
                                                    @if(!$selectedCurrency || $selectedCurrency === $curr)
                                                        @php $v = (float)($grandTotals['balances'][$curr] ?? 0); @endphp
                                                        @if(abs($v) > 0.001)
                                                            @if($v > 0)
                                                                <x-ui.badge variant="danger" class="!px-2 !py-1 text-xs font-bold">
                                                                    {{ number_format(abs($v), 2, ',', '.') }} {{ $curr }} · {{ __('Borçlu') }}
                                                                </x-ui.badge>
                                                            @else
                                                                <x-ui.badge variant="success" class="!px-2 !py-1 text-xs font-bold">
                                                                    {{ number_format(abs($v), 2, ',', '.') }} {{ $curr }} · {{ __('Alacaklı') }}
                                                                </x-ui.badge>
                                                            @endif
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </div>
                                        </td>

                                        {{-- Grand Open Advances --}}
                                        <td class="px-4 py-4">
                                            <div class="flex flex-col gap-1 items-start">
                                                @foreach($currencyKeys as $curr)
                                                    @if(!$selectedCurrency || $selectedCurrency === $curr)
                                                        @php $v = (float)($grandTotals['open_advances'][$curr] ?? 0); @endphp
                                                        @if($v > 0.001)
                                                            <x-ui.badge variant="info" class="!px-2 !py-1 text-xs font-bold">
                                                                {{ number_format($v, 2, ',', '.') }} {{ $curr }} · {{ __('Avans') }}
                                                            </x-ui.badge>
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </div>
                                        </td>

                                        {{-- Grand Open Invoices --}}
                                        <td class="px-4 py-4">
                                            <div class="flex flex-col gap-1 items-start">
                                                @foreach($currencyKeys as $curr)
                                                    @if(!$selectedCurrency || $selectedCurrency === $curr)
                                                        @php $amt = (float)($grandTotals['open_invoices'][$curr] ?? 0); @endphp
                                                        @if($amt > 0.001)
                                                            <x-ui.badge variant="danger" class="!px-2 !py-1 text-xs font-bold">
                                                                {{ number_format($amt, 2, ',', '.') }} {{ $curr }} · {{ __('Açık') }}
                                                            </x-ui.badge>
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </div>
                                        </td>

                                        <td class="px-4 py-4"></td>
                                    </tr>
                                @endif
                            </tfoot>
                        @endif
                    </x-ui.table>
                </div>

                {{-- Pagination --}}
                @if($customers->hasPages())
                    <div class="bg-slate-50 border-t border-slate-200 p-4">
                        {{ $customers->links() }}
                    </div>
                @endif
            </div>

            <div class="mt-4 text-xs text-center text-slate-400">
                {{ __('Tutarlar TR formatındadır.') }}
            </div>
        </div>
    </div>
</x-app-layout>
