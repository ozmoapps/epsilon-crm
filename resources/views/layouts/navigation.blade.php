{{-- resources/views/layouts/navigation.blade.php --}}
@php
    $navItemBase = 'group flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition-colors ui-focus';
    $navItemActive = 'relative bg-slate-50 text-slate-900 before:absolute before:inset-y-2 before:left-0 before:w-1 before:rounded-full before:bg-slate-900/70 [&>svg]:text-slate-700';
    $navItemInactive = 'text-slate-600 hover:bg-slate-50 hover:text-slate-900';

    // Header label (dinamik)
    $headerTitle = __('Kontrol Paneli');
    $headerSubtitle = __('Epsilon CRM');

    if (request()->routeIs('quotes.*')) { $headerTitle = __('Teklifler'); $headerSubtitle = __('Satış'); }
    elseif (request()->routeIs('sales-orders.*')) { $headerTitle = __('Satış Siparişleri'); $headerSubtitle = __('Satış'); }
    elseif (request()->routeIs('contracts.*')) { $headerTitle = __('Sözleşmeler'); $headerSubtitle = __('Satış'); }
    elseif (request()->routeIs('work-orders.*')) { $headerTitle = __('İş Emirleri'); $headerSubtitle = __('Operasyon'); }
    elseif (request()->routeIs('customer-ledgers.*') || request()->routeIs('customers.ledger*')) { $headerTitle = __('Cari Hesaplar'); $headerSubtitle = __('Finans'); }
    elseif (request()->routeIs('invoices.*')) { $headerTitle = __('Faturalar'); $headerSubtitle = __('Finans'); }
    elseif (request()->routeIs('payments.*')) { $headerTitle = __('Tahsilatlar'); $headerSubtitle = __('Finans'); }
    elseif (request()->routeIs('bank-accounts.*')) { $headerTitle = __('Kasa & Bankalar'); $headerSubtitle = __('Finans'); }
    elseif (request()->routeIs('stock.dashboard') || request()->routeIs('stock-operations.*') || request()->routeIs('stock-movements.*') || request()->routeIs('stock-transfers.*')) { $headerTitle = __('Stok & Depo'); $headerSubtitle = __('Operasyon'); }
    elseif (request()->routeIs('products.*') || request()->routeIs('categories.*') || request()->routeIs('warehouses.*')) { $headerTitle = __('Stok & Depo'); $headerSubtitle = __('Ana Veriler'); }
    elseif (request()->routeIs('customers.*')) { $headerTitle = __('Müşteriler'); $headerSubtitle = __('Ana Veriler'); }
    elseif (request()->routeIs('vessels.*')) { $headerTitle = __('Tekneler'); $headerSubtitle = __('Ana Veriler'); }
    elseif (request()->routeIs('saved-views.*')) { $headerTitle = __('Kaydedilmiş Görünümler'); $headerSubtitle = __('Kişisel'); }
    elseif (request()->routeIs('profile.*')) { $headerTitle = __('Profil'); $headerSubtitle = __('Ayarlar'); }
    elseif (request()->routeIs('admin.*')) { $headerTitle = __('Yönetim'); $headerSubtitle = __('Admin'); }

    // Define isAdmin based on authenticated user
    $isAdmin = auth()->check() && auth()->user()->is_admin;

    // Admin link styles (reuse main styles)
    $adminLinkBase = $navItemBase;
    $adminActive = $navItemActive;
    $adminInactive = $navItemInactive;

    // Sidebar Logic: Platform Admin Isolation
    // Show tenant menus ONLY if:
    // 1. User is NOT a platform admin (Normal Tenant User)
    // 2. OR User IS a platform admin BUT has an active Support Session (Break-Glass)
    $hasSupportSession = session('support_session_id');
    // Show tenant menus ONLY if:
    // 1. User is NOT a platform admin (Normal Tenant User)
    // 2. OR User IS a platform admin BUT has an active Support Session (Break-Glass)
    $hasSupportSession = session('support_session_id');
    $showTenantMenu = !$isAdmin || ($isAdmin && $hasSupportSession);
    
    // Platform Only (Normal Mode)
    // Items that should be hidden in normal platform admin mode but visible in support/break-glass
    $isPlatformOnly = $isAdmin && !$hasSupportSession;
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
        role="navigation"
        aria-label="{{ __('Yan Menü') }}"
    >
        <div class="flex items-center justify-between px-6 py-5">
            <a href="{{ ($isAdmin && !$hasSupportSession) ? route('admin.dashboard') : route('dashboard') }}" class="flex items-center gap-3">
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
            {{-- Kısayollar --}}
            @if($showTenantMenu)
            <div class="rounded-2xl border border-slate-100 bg-white p-3 shadow-sm">
                <div class="flex items-center justify-between px-1">
                    <p class="text-xs font-semibold tracking-wide text-slate-500">{{ __('Kısayollar') }}</p>
                    <x-ui.badge variant="neutral">{{ __('Hızlı') }}</x-ui.badge>
                </div>

                <div class="mt-3 grid grid-cols-3 gap-2">
                    @if (\Illuminate\Support\Facades\Route::has('quotes.create'))
                        <x-ui.button href="{{ route('quotes.create') }}" variant="secondary" size="sm" class="justify-center">
                            {{ __('Teklif') }}
                        </x-ui.button>
                    @endif

                    @if (\Illuminate\Support\Facades\Route::has('customers.create'))
                        <x-ui.button href="{{ route('customers.create') }}" variant="secondary" size="sm" class="justify-center">
                            {{ __('Müşteri') }}
                        </x-ui.button>
                    @endif

                    @if (\Illuminate\Support\Facades\Route::has('stock-operations.create'))
                        <x-ui.button href="{{ route('stock-operations.create') }}" variant="secondary" size="sm" class="justify-center">
                            {{ __('Stok') }}
                        </x-ui.button>
                    @endif
                </div>

                <div class="mt-2 px-1 text-xs text-slate-500">
                    {{ __('Yeni kayıt açıp akışı başlatın.') }}
                </div>
            </div>
            @endif

            @if($showTenantMenu)
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


            @endif

            @if($showTenantMenu)
            <div class="border-t border-slate-100/80 pt-6">
                <p class="px-3 text-xs font-semibold tracking-wide text-slate-500">{{ __('Finans') }}</p>

                @php
                    $hasPaymentsIndex = \Illuminate\Support\Facades\Route::has('payments.index');
                    $hasBankAccountsIndex = \Illuminate\Support\Facades\Route::has('bank-accounts.index');
                    $lockedFinanceClass = 'opacity-60 cursor-not-allowed text-slate-400';
                @endphp

                <div class="mt-3 space-y-1">
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
                        <a href="#" onclick="return false;" title="{{ __('Yakında') }}" class="{{ $navItemBase }} {{ $lockedFinanceClass }}">
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
                        <a href="#" onclick="return false;" title="{{ __('Yakında') }}" class="{{ $navItemBase }} {{ $lockedFinanceClass }}">
                            <x-icon.credit-card class="nav-icon" />
                            <span>{{ __('Kasa & Bankalar') }}</span>
                            <x-ui.icon.lock class="w-3 h-3 ml-auto text-slate-400" />
                        </a>
                    @endif
                </div>
            </div>


            @endif

            @if($showTenantMenu)
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


            @endif

            @if($showTenantMenu)
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


            @endif

            <div class="border-t border-slate-100/80 pt-6">
                <p class="px-3 text-xs font-semibold tracking-wide text-slate-500 mb-3">{{ __('Ayarlar') }}</p>
                
                <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-2 space-y-4">
                    {{-- Kişisel --}}
                    @if($showTenantMenu)
                    <div>
                        <p class="px-2 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">{{ __('Kişisel') }}</p>
                        <div class="space-y-0.5">
                            <a
                                href="{{ route('saved-views.index') }}"
                                class="{{ $navItemBase }} {{ request()->routeIs('saved-views.*') ? $navItemActive : $navItemInactive }}"
                                @if (request()->routeIs('saved-views.*')) aria-current="page" @endif
                            >
                                <x-icon.bookmark class="nav-icon" />
                                <span>{{ __('Görünümler') }}</span>
                            </a>

                            <a
                                href="{{ route('profile.edit') }}"
                                class="{{ $navItemBase }} {{ request()->routeIs('profile.*') ? $navItemActive : $navItemInactive }}"
                                @if (request()->routeIs('profile.*')) aria-current="page" @endif
                            >
                                <x-icon.user class="nav-icon" />
                                <span>{{ __('Profil') }}</span>
                            </a>
                        </div>
                    </div>
                    @endif

                    {{-- Global Yönetim (Platform Admin) --}}
                    @if($isAdmin)
                        <div>
                            <p class="px-2 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">{{ __('Platform') }}</p>
                            <div class="space-y-0.5">
                                {{-- Kullanıcılar --}}
                                <a
                                    href="{{ route('admin.users.index') }}"
                                    class="{{ $adminLinkBase }} {{ request()->routeIs('admin.users.*') ? $adminActive : $adminInactive }}"
                                >
                                    <x-icon.user-group class="nav-icon" />
                                    <span>{{ __('Kullanıcılar') }}</span>
                                </a>

                                {{-- Davetler --}}
                                <a
                                    href="{{ route('admin.invitations.index') }}"
                                    class="{{ $adminLinkBase }} {{ request()->routeIs('admin.invitations.*') ? $adminActive : $adminInactive }}"
                                >
                                    <x-icon.mail class="nav-icon" />
                                    <span>{{ __('Davetler') }}</span>
                                </a>

                                {{-- Genel Bakış --}}
                                <a
                                    href="{{ route('admin.dashboard') }}"
                                    class="{{ $adminLinkBase }} {{ request()->routeIs('admin.dashboard') ? $adminActive : $adminInactive }}"
                                >
                                    <x-icon.chart-bar class="nav-icon" />
                                    <span>{{ __('Genel Bakış') }}</span>
                                </a>

                                {{-- Denetim Günlüğü --}}
                                <a
                                    href="{{ route('admin.audit.index') }}"
                                    class="{{ $adminLinkBase }} {{ request()->routeIs('admin.audit.*') ? $adminActive : $adminInactive }}"
                                >
                                    <x-icon.clipboard-list class="nav-icon" />
                                    <span>{{ __('Denetim Günlüğü') }}</span>
                                </a>

                                {{-- Şirket Profili --}}
                                <a
                                    href="{{ route('admin.company-profiles.index') }}"
                                    class="{{ $adminLinkBase }} {{ request()->routeIs('admin.company-profiles.*') ? $adminActive : $adminInactive }}"
                                >
                                    <x-icon.building class="nav-icon" />
                                    <span>{{ __('Şirket Profili') }}</span>
                                </a>

                                {{-- Hesaplar (v4d3) --}}
                                <a
                                    href="{{ route('admin.accounts.index') }}"
                                    class="{{ $adminLinkBase }} {{ request()->routeIs('admin.accounts.*') ? $adminActive : $adminInactive }}"
                                >
                                    <x-icon.credit-card class="nav-icon" />
                                    <span>{{ __('Hesaplar') }}</span>
                                </a>

                                {{-- Firmalar --}}
                                <a
                                    href="{{ route('admin.tenants.index') }}"
                                    class="{{ $adminLinkBase }} {{ request()->routeIs('admin.tenants.*') ? $adminActive : $adminInactive }}"
                                >
                                    <x-icon.office-building class="nav-icon" />
                                    <span>{{ __('Firmalar') }}</span>
                                </a>

                                {{-- Para Birimleri --}}
                                <a
                                    href="{{ route('admin.currencies.index') }}"
                                    class="{{ $adminLinkBase }} {{ request()->routeIs('admin.currencies.*') ? $adminActive : $adminInactive }}"
                                >
                                    <x-icon.currency class="nav-icon" />
                                    <span>{{ __('Para Birimleri') }}</span>
                                </a>

                                {{-- Sözleşme Şablonları --}}
                                <a
                                    href="{{ route('admin.contract-templates.index') }}"
                                    class="{{ $adminLinkBase }} {{ request()->routeIs('admin.contract-templates.*') ? $adminActive : $adminInactive }}"
                                >
                                    <x-icon.template class="nav-icon" />
                                    <span>{{ __('Sözleşmeler') }}</span>
                                </a>
                            </div>
                        </div>
                    @endif

                    {{-- Firma Yönetimi (Tenant Admin) --}}
                    @php
                        // Check if user is tenant admin for CURRENT tenant
                        $isTenantAdmin = false;
                        if(auth()->check() && session('current_tenant_id')) {
                             $isTenantAdmin = auth()->user()->tenants()
                                ->where('tenants.id', session('current_tenant_id'))
                                ->wherePivot('role', 'admin')
                                ->exists();
                        }
                    @endphp

                    @if($showTenantMenu && $isTenantAdmin && \Illuminate\Support\Facades\Route::has('manage.members.index'))
                        <div class="mt-2">
                             <p class="px-2 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">{{ __('Firma Yönetimi') }}</p>
                             <div class="space-y-0.5">
                                <a
                                    href="{{ route('manage.members.index') }}"
                                    class="{{ $navItemBase }} {{ request()->routeIs('manage.members.*') ? $navItemActive : $navItemInactive }}"
                                >
                                    <x-icon.users class="nav-icon" />
                                    <span>{{ __('Üyeler') }}</span>
                                </a>
                                <a
                                    href="{{ route('manage.invitations.index') }}"
                                    class="{{ $navItemBase }} {{ request()->routeIs('manage.invitations.*') ? $navItemActive : $navItemInactive }}"
                                >
                                    <x-icon.document class="nav-icon" />
                                    <span>{{ __('Davetler') }}</span>
                                </a>
                                    <span>{{ __('Davetler') }}</span>
                                </a>
                                @php
                                    // Check if user is Account Owner or Billing Admin
                                    $isAccountOwner = false;
                                    if(auth()->check() && session('current_tenant_id')) {
                                         // Get current tenant's account
                                         // Optimisation: We could cache this or share via view composer, but for nav simplicity:
                                         // We need to resolve it safely.
                                         // However, Blade is rendered AFTER middleware, but accessing DB in loop might be heavy?
                                         // Not really, it's one query.
                                         // Let's rely on DB query for correct visibility.
                                         // We can't easily access Account model here without query.
                                         
                                         // Using DB facade to be lightweight
                                         $accId = \Illuminate\Support\Facades\DB::table('tenants')->where('id', session('current_tenant_id'))->value('account_id');
                                         if($accId) {
                                             $role = \Illuminate\Support\Facades\DB::table('account_users')
                                                ->where('account_id', $accId)
                                                ->where('user_id', auth()->id())
                                                ->value('role');
                                             $isAccountOwner = in_array($role, ['owner', 'billing_admin']);
                                             
                                             // Fallback owner check
                                             if(!$isAccountOwner) {
                                                 $ownerId = \Illuminate\Support\Facades\DB::table('accounts')->where('id', $accId)->value('owner_user_id');
                                                 $isAccountOwner = ($ownerId === auth()->id());
                                             }
                                         }
                                    }
                                @endphp
                                
                                @if($isAccountOwner && \Illuminate\Support\Facades\Route::has('manage.billing.index'))
                                    <a
                                        href="{{ route('manage.billing.index') }}"
                                        class="{{ $navItemBase }} {{ request()->routeIs('manage.billing.*') ? $navItemActive : $navItemInactive }}"
                                    >
                                        <x-icon.credit-card class="nav-icon" />
                                        <span>{{ __('Paket & Kullanım') }}</span>
                                    </a>
                                @endif
                             </div>
                        </div>
                    @endif

                    {{-- Geliştirici (Sadece Local ve NON-ADMIN) --}}
                    @if (app()->environment('local') && !$isAdmin && \Illuminate\Support\Facades\Route::has('ui.index'))
                        <div>
                            <p class="px-2 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">{{ __('Geliştirici') }}</p>
                            <a
                                href="{{ route('ui.index') }}"
                                class="{{ $navItemBase }} {{ request()->routeIs('ui.*') ? $navItemActive : $navItemInactive }}"
                                @if (request()->routeIs('ui.*')) aria-current="page" @endif
                            >
                                <x-icon.template class="nav-icon" />
                                <span>{{ __('UI Demo') }}</span>
                                <x-ui.badge variant="info" size="xs" class="ml-auto">LOCAL</x-ui.badge>
                            </a>
                        </div>
                    @endif
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
                    <span class="text-sm font-semibold text-slate-700">{{ $headerTitle }}</span>
                    <span class="text-xs text-slate-500">{{ $headerSubtitle }}</span>
                </div>
            </div>

            @auth
                <!-- Tenant Display (SaaS Phase 1) -->
                @if(isset($currentTenant) && $showTenantMenu)
                    <x-ui.dropdown align="right" width="w-48">
                        <x-slot name="trigger">
                            <button class="hidden items-center gap-2 border-r border-slate-100 pr-4 mr-4 lg:flex group ui-focus rounded-lg py-1 transition-colors hover:bg-slate-50">
                                <span class="text-xs text-slate-500">{{ __('Firma:') }}</span>
                                <x-ui.badge variant="neutral" size="sm" class="group-hover:bg-white group-hover:shadow-sm transition-all">
                                    {{ $currentTenant->name }}
                                </x-ui.badge>
                                <x-icon.chevron-down-small class="text-slate-400 group-hover:text-slate-600 transition-colors" />
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="px-3 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">
                                {{ __('Firma Değiştir') }}
                            </div>
                            
                            @foreach(Auth::user()->tenants as $tenant)
                                <form method="POST" action="{{ route('tenants.switch') }}">
                                    @csrf
                                    <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">
                                    @php
                                        $isActive = $tenant->is_active ?? true;
                                        $isCurrent = $currentTenant->id === $tenant->id;
                                        $isDisabled = !$isActive || $isCurrent;
                                    @endphp
                                    <button 
                                        type="submit" 
                                        class="flex w-full items-center justify-between px-4 py-2 text-sm text-left transition disabled:cursor-not-allowed
                                        {{ $isCurrent ? 'font-semibold text-slate-900 bg-slate-50/50' : ($isActive ? 'text-slate-600 hover:bg-slate-50' : 'text-slate-400 opacity-75') }}"
                                        {{ $isDisabled ? 'disabled' : '' }}
                                    >
                                        <span>{{ $tenant->name }}</span>
                                        <div class="flex items-center gap-2">
                                            @if(!$isActive)
                                                <x-ui.badge variant="danger" size="sm" class="text-[10px]">{{ __('Pasif') }}</x-ui.badge>
                                            @endif
                                            @if($isCurrent)
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            @endif
                                        </div>
                                    </button>
                                </form>
                            @endforeach
                        </x-slot>
                    </x-ui.dropdown>
                @endif

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
