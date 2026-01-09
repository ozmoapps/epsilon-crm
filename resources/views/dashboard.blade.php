<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Kontrol Paneli') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    <p class="text-base font-medium">
                        {{ __('Giriş yaptınız.') }}
                    </p>

                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                            {{ __('Hızlı Menü') }}
                        </h3>
                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <a href="/customers" class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-900 shadow-sm transition hover:bg-gray-100">
                                <span>{{ __('Müşteriler') }}</span>
                                <span class="text-gray-400">→</span>
                            </a>
                            <a href="/vessels" class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-900 shadow-sm transition hover:bg-gray-100">
                                <span>{{ __('Tekneler') }}</span>
                                <span class="text-gray-400">→</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
