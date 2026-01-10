@php
    $navItemBase = 'group flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition ui-focus';
    $navItemActive = 'bg-brand-50 text-brand-700 shadow-soft';
    $navItemInactive = 'text-slate-600 hover:bg-slate-100 hover:text-slate-900';
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
        class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-slate-200/80 bg-white/95 shadow-soft backdrop-blur transition-transform duration-200 lg:translate-x-0"
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
                class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-2 text-slate-500 transition hover:text-slate-900 ui-focus lg:hidden"
                @click="sidebarOpen = false"
                aria-label="{{ __('Menüyü kapat') }}"
            >
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <div class="flex-1 space-y-8 overflow-y-auto px-4 pb-8">
            <div>
                <p class="px-3 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('Genel') }}</p>
                <div class="mt-3 space-y-1">
                    <a
                        href="{{ route('dashboard') }}"
                        class="{{ $navItemBase }} {{ request()->routeIs('dashboard') ? $navItemActive : $navItemInactive }}"
                    >
                        <svg class="h-5 w-5 text-slate-400 transition group-hover:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5V21h6v-6h3v6h6V10.5" />
                        </svg>
                        <span>{{ __('Kontrol Paneli') }}</span>
                    </a>
                </div>
            </div>

            <div>
                <p class="px-3 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('CRM') }}</p>
                <div class="mt-3 space-y-1">
                    <a
                        href="{{ route('customers.index') }}"
                        class="{{ $navItemBase }} {{ request()->routeIs('customers.*') ? $navItemActive : $navItemInactive }}"
                    >
                        <svg class="h-5 w-5 text-slate-400 transition group-hover:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11c1.657 0 3-1.567 3-3.5S17.657 4 16 4s-3 1.567-3 3.5S14.343 11 16 11z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 11C9.433 11 11 9.433 11 7.5S9.433 4 7.5 4 4 5.567 4 7.5 5.567 11 7.5 11z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 20v-1c0-2.209 1.791-4 4-4h3" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 20v-1c0-2.209 1.791-4 4-4h3" />
                        </svg>
                        <span>{{ __('Müşteriler') }}</span>
                    </a>
                </div>
            </div>

            <div>
                <p class="px-3 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('Satış') }}</p>
                <div class="mt-3 space-y-1">
                    <a
                        href="{{ route('sales-orders.index') }}"
                        class="{{ $navItemBase }} {{ request()->routeIs('sales-orders.*') ? $navItemActive : $navItemInactive }}"
                    >
                        <svg class="h-5 w-5 text-slate-400 transition group-hover:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10M7 12h10M7 17h6" />
                            <rect x="3.5" y="4" width="17" height="16" rx="2" />
                        </svg>
                        <span>{{ __('Satış Siparişleri') }}</span>
                    </a>
                    <a
                        href="{{ route('contracts.index') }}"
                        class="{{ $navItemBase }} {{ request()->routeIs('contracts.*') ? $navItemActive : $navItemInactive }}"
                    >
                        <svg class="h-5 w-5 text-slate-400 transition group-hover:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 3h6l4 4v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 3v5h5" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6M9 17h4" />
                        </svg>
                        <span>{{ __('Sözleşmeler') }}</span>
                    </a>
                </div>
            </div>

            <div>
                <p class="px-3 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('Hesap') }}</p>
                <div class="mt-3 space-y-1">
                    <a
                        href="{{ route('profile.edit') }}"
                        class="{{ $navItemBase }} {{ request()->routeIs('profile.*') ? $navItemActive : $navItemInactive }}"
                    >
                        <svg class="h-5 w-5 text-slate-400 transition group-hover:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 12a4 4 0 100-8 4 4 0 000 8z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 20a8 8 0 0116 0" />
                        </svg>
                        <span>{{ __('Profil') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </aside>

    <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur lg:pl-72">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-2 text-slate-600 transition hover:text-slate-900 ui-focus lg:hidden"
                    @click="sidebarOpen = true"
                    aria-label="{{ __('Menüyü aç') }}"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div class="hidden items-center gap-2 lg:flex">
                    <span class="text-sm font-semibold text-slate-700">{{ __('Kontrol Paneli') }}</span>
                    <span class="text-xs text-slate-500">{{ __('Epsilon CRM') }}</span>
                </div>
            </div>

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
                        <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <div class="px-4 py-3 text-sm text-slate-600">
                        <div class="font-semibold text-slate-900">{{ Auth::user()->name }}</div>
                        <div class="text-xs">{{ Auth::user()->email }}</div>
                    </div>
                    <div class="border-t border-slate-100 py-1">
                        <a href="{{ route('profile.edit') }}" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-slate-700 transition hover:bg-slate-50">
                            <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 12a4 4 0 100-8 4 4 0 000 8z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 20a8 8 0 0116 0" />
                            </svg>
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
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 17l5-5-5-5" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H3" />
                                </svg>
                                {{ __('Çıkış Yap') }}
                            </button>
                        </form>
                    </div>
                </x-slot>
            </x-ui.dropdown>
        </div>
    </header>
</div>
