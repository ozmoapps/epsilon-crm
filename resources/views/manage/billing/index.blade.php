<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Paket & Kullanım') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-ui.card>
                <div class="flex items-center justify-between border-b border-slate-200 pb-4 mb-4">
                     <div>
                         <h3 class="text-lg font-medium leading-6 text-slate-900">{{ __('Mevcut Paketiniz') }}</h3>
                         <p class="mt-1 text-sm text-slate-500">{{ __('Hesabınızın aktif paketi ve özellikleri.') }}</p>
                     </div>
                     <x-ui.badge variant="info" size="lg">
                         {{ $account->plan->name_tr ?? $account->plan->key }}
                     </x-ui.badge>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    {{-- Firma Limiti --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-semibold text-slate-700">{{ __('Firma Limiti') }}</h4>
                            <span class="text-sm font-medium {{ $tenantUsage >= $tenantLimit && $tenantLimit !== null ? 'text-rose-600' : 'text-slate-600' }}">
                                {{ $tenantUsage }} / {{ $tenantLimit ?? '∞' }}
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2.5">
                             @php
                                $tPercent = $tenantLimit ? min(100, ($tenantUsage / $tenantLimit) * 100) : 0;
                             @endphp
                             <div class="bg-brand-600 h-2.5 rounded-full" style="width: {{ $tPercent }}%"></div>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">
                            {{ __('Bu hesaba bağlı oluşturulmuş aktif firma sayısı.') }}
                        </p>
                    </div>

                    {{-- Kullanıcı Limiti --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-semibold text-slate-700">{{ __('Kullanıcı (Seat) Limiti') }}</h4>
                            <span class="text-sm font-medium {{ $seatUsage >= $seatLimit && $seatLimit !== null ? 'text-rose-600' : 'text-slate-600' }}">
                                {{ $seatUsage }} / {{ $seatLimit ?? '∞' }}
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2.5">
                             @php
                                $sPercent = $seatLimit ? min(100, ($seatUsage / $seatLimit) * 100) : 0;
                             @endphp
                             <div class="bg-brand-600 h-2.5 rounded-full" style="width: {{ $sPercent }}%"></div>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">
                            {{ __('Tüm firmalarınızdaki toplam benzersiz üye ve bekleyen davet sayısı.') }}
                        </p>
                    </div>
                </div>
            </x-ui.card>

            {{-- Upgrade / Info --}}
            <div class="rounded-md bg-blue-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <x-icon.info class="h-5 w-5 text-blue-400" />
                    </div>
                    <div class="ml-3 flex-1 md:flex md:justify-between">
                        <p class="text-sm text-blue-700">
                            {{ __('Paketinizi yükseltmek veya ek kullanıcı satın almak için lütfen platform yönetimi ile iletişime geçin.') }}
                        </p>
                         <p class="mt-3 text-sm md:mt-0 md:ml-6">
                            <a href="#" class="whitespace-nowrap font-medium text-blue-700 hover:text-blue-600">
                                {{ __('İletişim ->') }}
                            </a>
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
