<nav x-data="{ open: false }" class="sticky top-0 z-50 border-b border-gray-100 bg-white/90 backdrop-blur">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
            <div class="flex">
                <!-- Logo -->
                <div class="flex shrink-0 items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden items-center gap-2 sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Kontrol Paneli') }}
                    </x-nav-link>
                    <x-nav-link :href="route('customers.index')" :active="request()->routeIs('customers.*')">
                        {{ __('Müşteriler') }}
                    </x-nav-link>
                    <x-nav-link :href="route('vessels.index')" :active="request()->routeIs('vessels.*')">
                        {{ __('Tekneler') }}
                    </x-nav-link>
                    <x-nav-link :href="route('work-orders.index')" :active="request()->routeIs('work-orders.*')">
                        {{ __('İş Emirleri') }}
                    </x-nav-link>
                    <x-nav-link :href="route('quotes.index')" :active="request()->routeIs('quotes.*')">
                        {{ __('Teklifler') }}
                    </x-nav-link>
                    <x-nav-link :href="route('sales-orders.index')" :active="request()->routeIs('sales-orders.*')">
                        {{ __('Satış Siparişleri') }}
                    </x-nav-link>
                    <x-nav-link :href="route('contracts.index')" :active="request()->routeIs('contracts.*')">
                        {{ __('Sözleşmeler') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 rounded-full border border-transparent bg-white px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50 hover:text-gray-800 focus:outline-none">
                            <div class="h-8 w-8 rounded-full bg-indigo-100 text-center text-sm font-semibold leading-8 text-indigo-700">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                            <div class="hidden sm:block">{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profil') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Çıkış Yap') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="space-y-1 px-4 pb-3 pt-2">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Kontrol Paneli') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('customers.index')" :active="request()->routeIs('customers.*')">
                {{ __('Müşteriler') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('vessels.index')" :active="request()->routeIs('vessels.*')">
                {{ __('Tekneler') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('work-orders.index')" :active="request()->routeIs('work-orders.*')">
                {{ __('İş Emirleri') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('quotes.index')" :active="request()->routeIs('quotes.*')">
                {{ __('Teklifler') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('sales-orders.index')" :active="request()->routeIs('sales-orders.*')">
                {{ __('Satış Siparişleri') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('contracts.index')" :active="request()->routeIs('contracts.*')">
                {{ __('Sözleşmeler') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="border-t border-gray-200 px-4 pb-4 pt-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-indigo-100 text-center text-sm font-semibold leading-10 text-indigo-700">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
                <div>
                    <div class="text-base font-medium text-gray-800">{{ Auth::user()->name }}</div>
                    <div class="text-sm text-gray-500">{{ Auth::user()->email }}</div>
                </div>
            </div>

            <div class="mt-4 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profil') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Çıkış Yap') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
