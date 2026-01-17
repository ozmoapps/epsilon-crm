<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Hesap Detayı') }} <span class="text-slate-400">#{{ $account->id }}</span>
            </h2>
            <x-ui.button href="{{ route('admin.accounts.index') }}" variant="secondary" size="sm">
                {{ __('Geri Dön') }}
            </x-ui.button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            {{-- Overview --}}
            <x-ui.card>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                   <div>
                       <h3 class="text-sm font-medium text-slate-500">{{ __('Hesap Sahibi') }}</h3>
                       <div class="mt-1 text-lg font-semibold text-slate-900">{{ $account->owner->name ?? '-' }}</div>
                       <div class="text-sm text-slate-500">{{ $account->owner->email ?? '-' }}</div>
                   </div>
                   <div>
                       <h3 class="text-sm font-medium text-slate-500">{{ __('Paket') }}</h3>
                       <div class="mt-1">
                           <x-ui.badge variant="info" size="lg">
                               {{ $account->plan->name_tr ?? $account->plan->key }}
                           </x-ui.badge>
                           @if($account->extra_seats_purchased > 0)
                               <div class="mt-1 text-xs text-slate-500">+{{ $account->extra_seats_purchased }} Ek Kullanıcı</div>
                           @endif
                       </div>
                   </div>
                   <div>
                       <h3 class="text-sm font-medium text-slate-500">{{ __('Durum') }}</h3>
                       <div class="mt-1">
                            @if($account->status === 'active')
                                <x-ui.badge variant="success">{{ __('Aktif') }}</x-ui.badge>
                            @else
                                <x-ui.badge variant="neutral">{{ $account->status }}</x-ui.badge>
                            @endif
                       </div>
                   </div>
                </div>
            </x-ui.card>
            
            {{-- Usage Stats --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-ui.card>
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-slate-900">{{ __('Firma Limiti') }}</h3>
                        <span class="text-2xl font-bold {{ $tenantUsage >= $tenantLimit && $tenantLimit !== null ? 'text-rose-600' : 'text-slate-900' }}">
                            {{ $tenantUsage }} / {{ $tenantLimit ?? '∞' }}
                        </span>
                    </div>
                    <div class="mt-4 w-full bg-slate-100 rounded-full h-2.5 dark:bg-slate-700">
                        @php
                           $tenantPercent = $tenantLimit ? min(100, ($tenantUsage / $tenantLimit) * 100) : 0;
                        @endphp
                        <div class="bg-brand-600 h-2.5 rounded-full" style="width: {{ $tenantPercent }}%"></div>
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-slate-900">{{ __('Kullanıcı (Seat) Limiti') }}</h3>
                        <span class="text-2xl font-bold {{ $seatUsage >= $seatLimit && $seatLimit !== null ? 'text-rose-600' : 'text-slate-900' }}">
                            {{ $seatUsage }} / {{ $seatLimit ?? '∞' }}
                        </span>
                    </div>
                    <div class="mt-4 w-full bg-slate-100 rounded-full h-2.5 dark:bg-slate-700">
                        @php
                           $seatPercent = $seatLimit ? min(100, ($seatUsage / $seatLimit) * 100) : 0;
                        @endphp
                        <div class="bg-brand-600 h-2.5 rounded-full" style="width: {{ $seatPercent }}%"></div>
                    </div>
                </x-ui.card>
            </div>

            {{-- Manage / Update --}}
            <x-ui.card>
                <div class="border-b border-slate-200 pb-4 mb-4">
                    <h3 class="text-lg font-medium leading-6 text-slate-900">{{ __('Yönetim') }}</h3>
                </div>
                
                <form action="{{ route('admin.accounts.update', $account) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-ui.label for="plan_id" value="{{ __('Paket Değiştir') }}" />
                            <select id="plan_id" name="plan_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                                @foreach(\App\Models\Plan::all() as $plan)
                                    <option value="{{ $plan->id }}" {{ $account->plan_id == $plan->id ? 'selected' : '' }}>
                                        {{ $plan->name_tr ?? $plan->key }} (Limit: {{ $plan->seat_limit ?? 'Sınırsız' }} Seat / {{ $plan->tenant_limit ?? 'Sınırsız' }} Firma)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <x-ui.label for="extra_seats_purchased" value="{{ __('Ek Kullanıcı (Seat) Satın Alındı') }}" />
                            <x-ui.input id="extra_seats_purchased" name="extra_seats_purchased" type="number" min="0" class="mt-1 block w-full" :value="$account->extra_seats_purchased" />
                            <p class="text-xs text-slate-500 mt-1">{{ __('Paket limitine eklenir.') }}</p>
                        </div>
                    </div>
                    
                    <div class="mt-4 flex justify-end">
                        <x-ui.button type="submit" variant="primary">
                            {{ __('Kaydet') }}
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>

            {{-- Audit Alert --}}
            <x-ui.card>
                 <div class="border-b border-slate-200 pb-4 mb-4">
                    <h3 class="text-lg font-medium leading-6 text-slate-900">{{ __('Son 24 Saat Limit Blokları') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Sistem tarafından engellenen limit aşım denemeleri') }}</p>
                 </div>
                 
                 @if($blockedEvents->count() > 0)
                    <div class="grid grid-cols-3 gap-4 mb-6">
                         <div class="bg-rose-50 p-4 rounded-lg">
                             <div class="text-sm font-medium text-rose-800">Toplam Blok</div>
                             <div class="mt-1 text-2xl font-semibold text-rose-900">{{ $blockedEvents->count() }}</div>
                         </div>
                         <div class="bg-slate-50 p-4 rounded-lg">
                             <div class="text-sm font-medium text-slate-800">Seat Limit</div>
                             <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $stats['seat_limit'] ?? 0 }}</div>
                         </div>
                         <div class="bg-slate-50 p-4 rounded-lg">
                             <div class="text-sm font-medium text-slate-800">Tenant Limit</div>
                             <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $stats['tenant_limit'] ?? 0 }}</div>
                         </div>
                    </div>
                @else
                    <div class="text-sm text-slate-500 py-4 italic">{{ __('Son 24 saatte bloklanan işlem yok.') }}</div>
                @endif
            </x-ui.card>

        </div>
    </div>
</x-app-layout>
