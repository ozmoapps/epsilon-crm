@php
    $navItemBase = 'group flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition-colors ui-focus';
    $navItemActive = 'relative bg-slate-50 text-slate-900 before:absolute before:inset-y-2 before:left-0 before:w-1 before:rounded-full before:bg-slate-900/70 [&>svg]:text-slate-700';
    $navItemInactive = 'text-slate-600 hover:bg-slate-50 hover:text-slate-900';
@endphp

<div class="relative">
    <div
        x-cloak
        x-show="sidebarOpen"
        x-transition.opacity.duration.150ms
        class="fixed inset-0 z-40 bg-slate-900/40 lg:hidden"
        @click="sidebarOpen = false"
    ></div>

    <aside
        x-cloak
        class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-slate-100 bg-white/95 shadow-soft backdrop-blur transition-transform duration-200 lg:translate-x-0"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        role="dialog"
        aria-label="{{ __('Yan Menü') }}"
    >
        <div class="flex items-center justify-between px-6 py-5">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <x-application-logo class="h-9 w-auto fill-current text-slate-800" />
                <span class="text-sm font-semibold text-slate-800">{{ config('app.name', 'Epsilon CRM') }}</span>
            </a>
            <button
                type="button"
                class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-slate-500 transition hover:text-slate-900 ui-focus lg:hidden"
                @click="sidebarOpen = false"
                aria-label="{{ __('Menüyü kapat') }}"
            >
                <x-icon.close />
            </button>
        </div>

        <div class="flex-1 space-y-8 overflow-y-auto px-4 pb-8">
            <div>
                <p class="px-3 text-xs font-semibold tracking-wide text-slate-500">{{ __('Operasyonlar') }}</p>
                <div class="mt-3 space-y-1">
                    <a
                        href="{{ route('dashboard') }}"
                        class="{{ $navItemBase }} {{ request()->routeIs('dashboard') ? $navItemActive : $navItemInactive }}"
                        @if (request()->routeIs('dashboard')) aria-current="page" @endif
                    >
                        <x-icon.home class="nav-icon" />
                        <span>{{ __('Kontrol Paneli') }}</span>
                    </a>
                    <a
                        href="{{ route('quotes.index') }}"
                        class="{{ $navItemBase }} {{ request()->routeIs('quotes.*') ? $navItemActive : $navItemInactive }}"
                        @if (request()->routeIs('quotes.*')) aria-current="page" @endif
                    >
                        <x-icon.document class="nav-icon" />
                        <span>{{ __('Teklifler') }}</span>
                    </a>
                    <a
                        href="{{ route('sales-orders.index') }}"
                        class="{{ $navItemBase }} {{ request()->routeIs('sales-orders.*') ? $navItemActive : $navItemInactive }}"
                        @if (request()->routeIs('sales-orders.*')) aria-current="page" @endif
                    >
                        <x-icon.clipboard class="nav-icon" />
                        <span>{{ __('Satış Siparişleri') }}</span>
                    </a>
                    <a
                        href="{{ route('contracts.index') }}"
                        class="{{ $navItemBase }} {{ request()->routeIs('contracts.*') ? $navItemActive : $navItemInactive }}"
                        @if (request()->routeIs('contracts.*')) aria-current="page" @endif
                    >
                        <x-icon.file class="nav-icon" />
                        <span>{{ __('Sözleşmeler') }}</span>
                    </a>

                    <a
                        href="{{ route('work-orders.index') }}"
                        class="{{ $navItemBase }} {{ request()->routeIs('work-orders.*') ? $navItemActive : $navItemInactive }}"
                        @if (request()->routeIs('work-orders.*')) aria-current="page" @endif
                    >
                        <x-icon.tools class="nav-icon" />
                        <span>{{ __('İş Emirleri') }}</span>
                    </a>
                </div>
            </div>

            <div class="border-t border-slate-100/80 pt-6">
                <p class="px-3 text-xs font-semibold tracking-wide text-slate-500">{{ __('Finans') }}</p>

                @php
                    $hasPaymentsIndex = \Illuminate\Support\Facades\Route::has('payments.index');
                    $hasBankAccountsIndex = \Illuminate\Support\Facades\Route::has('bank-accounts.index');
                    $lockedFinanceClass = 'opacity-60 cursor-not-allowed text-slate-400';
                @endphp

                <div class="mt-3 space-y-1">
                    {{-- Global Customer Ledger --}}
                    <a
                        href="{{ route('customer-ledgers.index') }}"
                        class="{{ $navItemBase }} {{ request()->routeIs('customer-ledgers.*') ? $navItemActive : $navItemInactive }}"
                        @if (request()->routeIs('customer-ledgers.*')) aria-current="page" @endif
                    >
                        <x-icon.cash class="nav-icon" />
                        <span>{{ __('Cari Hesaplar') }}</span>
                    </a>

                    <a
                        href="{{ route('invoices.index') }}"
                        class="{{ $navItemBase }} {{ request()->routeIs('invoices.*') ? $navItemActive : $navItemInactive }}"
                        @if (request()->routeIs('invoices.*')) aria-current="page" @endif
                    >
                        <x-icon.file class="nav-icon" />
                        <span>{{ __('Faturalar') }}</span>
                    </a>

                    @if ($hasPaymentsIndex)
                        <a
                            href="{{ route('payments.index') }}"
                            class="{{ $navItemBase }} {{ request()->routeIs('payments.*') ? $navItemActive : $navItemInactive }}"
                            @if (request()->routeIs('payments.*')) aria-current="page" @endif
                        >
                            <x-icon.bank class="nav-icon" />
                            <span>{{ __('Tahsilatlar') }}</span>
                        </a>
                    @else
                        <a
                            href="#"
                            onclick="return false;"
                            title="Yakında"
                            class="{{ $navItemBase }} {{ $lockedFinanceClass }}"
                        >
                            <x-icon.bank class="nav-icon" />
                            <span>{{ __('Tahsilatlar') }}</span>
                            <x-ui.icon.lock class="w-3 h-3 ml-auto text-slate-400" />
                        </a>
                    @endif

                    @if ($hasBankAccountsIndex)
                        <a
                            href="{{ route('bank-accounts.index') }}"
                            class="{{ $navItemBase }} {{ request()->routeIs('bank-accounts.*') ? $navItemActive : $navItemInactive }}"
                            @if (request()->routeIs('bank-accounts.*')) aria-current="page" @endif
                        >
                            <x-icon.credit-card class="nav-icon" />
                            <span>{{ __('Kasa & Bankalar') }}</span>
                        </a>
                    @else
                        <a
                            href="#"
                            onclick="return false;"
                            title="Yakında"
                            class="{{ $navItemBase }} {{ $lockedFinanceClass }}"
                        >
                            <x-icon.credit-card class="nav-icon" />
                            <span>{{ __('Kasa & Bankalar') }}</span>
                            <x-ui.icon.lock class="w-3 h-3 ml-auto text-slate-400" />
                        </a>
                    @endif
                </div>
            </div>

            <div class="border-t border-slate-100/80 pt-6">
                <p class="px-3 text-xs font-semibold tracking-wide text-slate-500">{{ __('Stok & Depo') }}</p>
                <div class="mt-3 space-y-1">
                    <a
                        href="{{ route('stock.dashboard') }}"
                        class="{{ $navItemBase }} {{ request()->routeIs('stock.dashboard') ? $navItemActive : $navItemInactive }}"
                        @if (request()->routeIs('stock.dashboard')) aria-current="page" @endif
                    >
                        <x-icon.chart-bar class="nav-icon" />
                        <span>{{ __('Operasyon Paneli') }}</span>
                    </a>
                    <a
                        href="{{ route('stock-operations.create') }}"
                        class="{{ $navItemBase }} {{ request()->routeIs('stock-operations.create') ? $navItemActive : $navItemInactive }}"
                        @if (request()->routeIs('stock-operations.create')) aria-current="page" @endif
                    >
                        <x-icon.lightning-bolt class="nav-icon" />
                        <span>{{ __('Hızlı Stok İşlemi') }}</span>
                    </a>
                    <a
                        href="{{ route('products.index') }}"
                        class="{{ $navItemBase }} {{ request()->routeIs('products.*') ? $navItemActive : $navItemInactive }}"
                        @if (request()->routeIs('products.*')) aria-current="page" @endif
                    >
                        <x-icon.cube class="nav-icon" />
                        <span>{{ __('Ürünler') }}</span>
                    </a>
                    <a
                        href="{{ route('categories.index') }}"
                        class="{{ $navItemBase }} {{ request()->routeIs('categories.*') ? $navItemActive : $navItemInactive }}"
                        @if (request()->routeIs('categories.*')) aria-current="page" @endif
                    >
                        <x-icon.tag class="nav-icon" />
                        <span>{{ __('Kategoriler') }}</span>
                    </a>
                    <a
                        href="{{ route('warehouses.index') }}"
                        class="{{ $navItemBase }} {{ request()->routeIs('warehouses.*') ? $navItemActive : $navItemInactive }}"
                        @if (request()->routeIs('warehouses.*')) aria-current="page" @endif
                    >
                        <x-icon.office-building class="nav-icon" />
                        <span>{{ __('Depolar') }}</span>
                    </a>
                    <a
                        href="{{ route('stock-movements.index') }}"
                        class="{{ $navItemBase }} {{ request()->routeIs('stock-movements.*') ? $navItemActive : $navItemInactive }}"
                        @if (request()->routeIs('stock-movements.*')) aria-current="page" @endif
                    >
                        <x-icon.switch-horizontal class="nav-icon" />
                        <span>{{ __('Stok Hareketleri') }}</span>
                    </a>
                    <a
                        href="{{ route('stock-transfers.index') }}"
                        class="{{ $navItemBase }} {{ request()->routeIs('stock-transfers.*') ? $navItemActive : $navItemInactive }}"
                        @if (request()->routeIs('stock-transfers.*')) aria-current="page" @endif
                    >
                        <x-icon.truck class="nav-icon" />
                        <span>{{ __('Transferler') }}</span>
                    </a>
                </div>
            </div>

            <div class="border-t border-slate-100/80 pt-6">
                <p class="px-3 text-xs font-semibold tracking-wide text-slate-500">{{ __('Ana Veriler') }}</p>
                <div class="mt-3 space-y-1">
                    <a
                        href="{{ route('customers.index') }}"
                        class="{{ $navItemBase }} {{ request()->routeIs('customers.*') ? $navItemActive : $navItemInactive }}"
                        @if (request()->routeIs('customers.*')) aria-current="page" @endif
                    >
                        <x-icon.users class="nav-icon" />
                        <span>{{ __('Müşteriler') }}</span>
                    </a>
                    <a
                        href="{{ route('vessels.index') }}"
                        class="{{ $navItemBase }} {{ request()->routeIs('vessels.*') ? $navItemActive : $navItemInactive }}"
                        @if (request()->routeIs('vessels.*')) aria-current="page" @endif
                    >
                        <x-icon.ship class="nav-icon" />
                        <span>{{ __('Tekneler') }}</span>
                    </a>
                </div>
            </div>

            <div class="border-t border-slate-100/80 pt-6">
                <p class="px-3 text-xs font-semibold tracking-wide text-slate-500">{{ __('Ayarlar') }}</p>
                @php
                    $isAdmin = auth()->check() && auth()->user()->is_admin;
                    $lockedClass = 'opacity-50 cursor-not-allowed';

                    $itemClass = function($routePattern) use ($navItemBase, $navItemActive, $navItemInactive, $isAdmin, $lockedClass) {
                        $base = $navItemBase;
                        if (!$isAdmin) {
                            $base = str_replace('hover:bg-slate-50 hover:text-slate-900', '', $navItemInactive) . ' text-slate-400';
                            return $navItemBase . ' ' . $base . ' ' . $lockedClass;
                        }
                        return $navItemBase . ' ' . (request()->routeIs($routePattern) ? $navItemActive : $navItemInactive);
                    };

                    $getHref = fn($route) => $isAdmin ? route($route) : '#';
                    $getAttrs = fn() => $isAdmin ? '' : 'onclick="return false;" title="Sadece Admin"';
                @endphp

                <div class="mt-3 space-y-1">
                    <a
                        href="{{ route('saved-views.index') }}"
                        class="{{ $navItemBase }} {{ request()->routeIs('saved-views.*') ? $navItemActive : $navItemInactive }}"
                        @if (request()->routeIs('saved-views.*')) aria-current="page" @endif
                    >
                        <x-icon.bookmark class="nav-icon" />
                        <span>{{ __('Kaydedilmiş Görünümler') }}</span>
                    </a>

                    <a
                        href="{{ route('profile.edit') }}"
                        class="{{ $navItemBase }} {{ request()->routeIs('profile.*') ? $navItemActive : $navItemInactive }}"
                        @if (request()->routeIs('profile.*')) aria-current="page" @endif
                    >
                        <x-icon.user class="nav-icon" />
                        <span>{{ __('Profil') }}</span>
                    </a>

                    <a
                        href="{{ $getHref('admin.users.index') }}"
                        class="{{ $itemClass('admin.users.*') }}"
                        {!! $getAttrs() !!}
                    >
                        <x-icon.user-group class="nav-icon" />
                        <span>{{ __('Kullanıcılar') }}</span>
                        @if(!$isAdmin) <x-ui.icon.lock class="w-3 h-3 ml-auto text-slate-400" /> @endif
                    </a>

                    <a
                        href="{{ $getHref('admin.company-profiles.index') }}"
                        class="{{ $itemClass('admin.company-profiles.*') }}"
                        {!! $getAttrs() !!}
                    >
                        <x-icon.building class="nav-icon" />
                        <span>{{ __('Şirket Profili') }}</span>
                        @if(!$isAdmin) <x-ui.icon.lock class="w-3 h-3 ml-auto text-slate-400" /> @endif
                    </a>

                    <a
                        href="{{ $getHref('admin.currencies.index') }}"
                        class="{{ $itemClass('admin.currencies.*') }}"
                        {!! $getAttrs() !!}
                    >
                        <x-icon.currency class="nav-icon" />
                        <span>{{ __('Para Birimleri') }}</span>
                        @if(!$isAdmin) <x-ui.icon.lock class="w-3 h-3 ml-auto text-slate-400" /> @endif
                    </a>

                    <a
                        href="{{ $getHref('admin.contract-templates.index') }}"
                        class="{{ $itemClass('admin.contract-templates.*') }}"
                        {!! $getAttrs() !!}
                    >
                        <x-icon.template class="nav-icon" />
                        <span>{{ __('Sözleşme Şablonları') }}</span>
                        @if(!$isAdmin) <x-ui.icon.lock class="w-3 h-3 ml-auto text-slate-400" /> @endif
                    </a>
                </div>
            </div>
        </div>
    </aside>

    <header class="sticky top-0 z-30 border-b border-slate-100 bg-white/90 backdrop-blur lg:pl-72">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-slate-600 transition hover:text-slate-900 ui-focus lg:hidden"
                    @click="sidebarOpen = true"
                    aria-label="{{ __('Menüyü aç') }}"
                >
                    <x-icon.menu />
                </button>
                <div class="hidden items-center gap-2 lg:flex">
                    <span class="text-sm font-semibold text-slate-700">{{ __('Kontrol Paneli') }}</span>
                    <span class="text-xs text-slate-500">{{ __('Epsilon CRM') }}</span>
                </div>
            </div>

            @auth
                <x-ui.dropdown align="right" width="w-56">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-3 rounded-full border border-transparent bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 ui-focus" aria-label="{{ __('Kullanıcı menüsü') }}">
                            <span class="hidden text-right sm:block">
                                <span class="block text-xs text-slate-500">{{ __('Hoş geldiniz') }}</span>
                                <span class="block">{{ Auth::user()->name }}</span>
                            </span>
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </span>
                            <x-icon.chevron-down-small class="text-slate-400" />
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-3 text-sm text-slate-600">
                            <div class="font-semibold text-slate-900">{{ Auth::user()->name }}</div>
                            <div class="text-xs">{{ Auth::user()->email }}</div>
                        </div>
                        <div class="border-t border-slate-100 py-1">
                            <a href="{{ route('profile.edit') }}" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-slate-700 transition hover:bg-slate-50">
                                <x-icon.user class="h-4 w-4 text-slate-500" />
                                {{ __('Profil') }}
                            </a>
                        </div>
                        <div class="border-t border-slate-100 py-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button
                                    type="submit"
                                    class="flex w-full items-center gap-2 px-4 py-2 text-sm text-rose-600 transition hover:bg-rose-50"
                                    onclick="event.preventDefault(); this.closest('form').submit();"
                                >
                                    <x-icon.logout />
                                    {{ __('Çıkış Yap') }}
                                </button>
                            </form>
                        </div>
                    </x-slot>
                </x-ui.dropdown>
            @endauth
        </div>
    </header>
</div>
