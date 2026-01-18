<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Paket ve Kullanım') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <x-ui.card>
                <div class="border-b border-slate-200 pb-4 mb-4">
                     <h3 class="text-lg font-medium leading-6 text-slate-900">{{ __('Mevcut Paket') }}</h3>
                     <p class="mt-1 text-sm text-slate-500">{{ __('Firmanızın bağlı olduğu hesap ve kullanım limitleri.') }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-6">
                    <div>
                         <div class="text-sm font-medium text-slate-500 mb-1">{{ __('Paket Adı') }}</div>
                         <x-ui.badge variant="info" size="lg">
                             {{ $account->plan_name }}
                         </x-ui.badge>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                     <!-- Tenant Usage -->
                     <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-slate-700">{{ __('Firma Limiti') }}</span>
                            <span class="text-sm font-bold {{ $tenantUsage >= $tenantLimit && $tenantLimit !== null ? 'text-rose-600' : 'text-slate-900' }}">
                                {{ $tenantUsage }} / {{ $tenantLimit ?? '∞' }}
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2.5">
                            @php
                               $tenantPercent = $tenantLimit ? min(100, ($tenantUsage / $tenantLimit) * 100) : 0;
                            @endphp
                            <div class="bg-brand-600 h-2.5 rounded-full" style="width: {{ $tenantPercent }}%"></div>
                        </div>
                     </div>

                     <!-- Seat Usage -->
                     <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-slate-700">{{ __('Kullanıcı (Seat) Limiti') }}</span>
                            <span class="text-sm font-bold {{ $seatUsage >= $seatLimit && $seatLimit !== null ? 'text-rose-600' : 'text-slate-900' }}">
                                {{ $seatUsage }} / {{ $seatLimit ?? '∞' }}
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2.5">
                            @php
                               $seatPercent = $seatLimit ? min(100, ($seatUsage / $seatLimit) * 100) : 0;
                            @endphp
                            <div class="bg-brand-600 h-2.5 rounded-full" style="width: {{ $seatPercent }}%"></div>
                        </div>
                        <p class="text-xs text-slate-500 mt-2">
                            {{ __('Aktif üyeler ve bekleyen davetler dahildir.') }}
                        </p>
                     </div>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-100">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-slate-500 italic">
                            {{ __('Paket yükseltme işlemleri şu anda platform yöneticisi tarafından yapılır.') }}
                        </p>
                        <div class="flex gap-2">
                            <x-ui.button href="{{ route('manage.plan_requests.index') }}" variant="secondary">
                                {{ __('Taleplerim') }}
                            </x-ui.button>
                            <x-ui.button href="{{ route('manage.plan_requests.create') }}" variant="primary">
                                {{ __('Paket Yükseltme Talebi') }}
                            </x-ui.button>
                        </div>
                    </div>
                </div>

            </x-ui.card>
        </div>
    </div>
</x-app-layout>
