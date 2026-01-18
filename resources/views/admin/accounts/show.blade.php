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
                               {{ $account->plan_name }}
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
                        <div class="text-right">
                             <span class="text-2xl font-bold {{ $tenantUsage > ($tenantLimit ?? PHP_INT_MAX) ? 'text-rose-600' : 'text-slate-900' }}">
                                {{ $tenantUsage }} / {{ $tenantLimit ?? '∞' }}
                            </span>
                            @if($tenantLimit !== null && $tenantUsage > $tenantLimit)
                                <x-ui.badge variant="danger" size="sm" class="ml-2">{{ __('Limit Aşıldı') }}</x-ui.badge>
                            @endif
                        </div>
                    </div>
                    <div class="mt-4 w-full bg-slate-100 rounded-full h-2.5 dark:bg-slate-700">
                        @php
                           $tenantPercent = $tenantLimit ? min(100, ($tenantUsage / $tenantLimit) * 100) : 0;
                           $tenantBarColor = ($tenantLimit && $tenantUsage > $tenantLimit) ? 'bg-rose-500' : 'bg-brand-600';
                        @endphp
                        <div class="{{ $tenantBarColor }} h-2.5 rounded-full" style="width: {{ $tenantPercent }}%"></div>
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-slate-900">{{ __('Kullanıcı (Seat) Limiti') }}</h3>
                        <div class="text-right">
                            <span class="text-2xl font-bold {{ $seatUsage > ($seatLimit ?? PHP_INT_MAX) ? 'text-rose-600' : 'text-slate-900' }}">
                                {{ $seatUsage }} / {{ $seatLimit ?? '∞' }}
                            </span>
                             @if($seatLimit !== null && $seatUsage > $seatLimit)
                                <x-ui.badge variant="danger" size="sm" class="ml-2">{{ __('Limit Aşıldı') }}</x-ui.badge>
                            @endif
                        </div>
                    </div>
                    <div class="mt-4 w-full bg-slate-100 rounded-full h-2.5 dark:bg-slate-700">
                        @php
                           $seatPercent = $seatLimit ? min(100, ($seatUsage / $seatLimit) * 100) : 0;
                           $seatBarColor = ($seatLimit && $seatUsage > $seatLimit) ? 'bg-rose-500' : 'bg-brand-600';
                        @endphp
                        <div class="{{ $seatBarColor }} h-2.5 rounded-full" style="width: {{ $seatPercent }}%"></div>
                    </div>
                </x-ui.card>
            </div>
            {{-- Account Users & Roles (PR4D6) --}}
            @php
                // Minimal inline helper for email masking
                $maskEmail = function($email) {
                    if (!str_contains($email, '@')) return $email;
                    $parts = explode('@', $email);
                    $name = $parts[0];
                    $domain = $parts[1];
                    $maskedName = substr($name, 0, 1) . '***';
                    $maskedDomain = '***.' . explode('.', $domain)[1] ?? 'com'; // simplified
                    // Better: a***@d***.com pattern
                     return preg_replace_callback('/^(.{1})(.*)(@.{1})(.*)(\..{2,})/', function ($matches) {
                        return $matches[1] . '***' . $matches[3] . '***' . $matches[5];
                    }, $email) ?? '***@***.com';
                };
            @endphp
            <x-ui.card>
                <div class="border-b border-slate-200 pb-4 mb-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-medium leading-6 text-slate-900">{{ __('Hesap Kullanıcıları') }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Hesaba bağlı kullanıcıları ve rollerini yönetin.') }}</p>
                    </div>
                </div>

                {{-- Users List --}}
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 rounded-lg mb-8">
                    <x-ui.table>
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">{{ __('Kullanıcı') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('Email') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('Rol') }}</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                    <span class="sr-only">{{ __('İşlem') }}</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @foreach($account->users as $user)
                                <tr>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">
                                        {{ $user->name }}
                                        @if($user->id === $account->owner_user_id)
                                            <span class="ml-2 inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">
                                                {{ __('Sahip') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        {{ $maskEmail($user->email) }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        @if($user->pivot->role === 'owner')
                                            <x-ui.badge variant="success">Owner</x-ui.badge>
                                        @elseif($user->pivot->role === 'billing_admin')
                                            <x-ui.badge variant="info">Billing Admin</x-ui.badge>
                                        @else
                                            <x-ui.badge variant="neutral">Member</x-ui.badge>
                                        @endif
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        @if($user->id !== $account->owner_user_id)
                                            <form action="{{ route('admin.accounts.roles.update', $account) }}" method="POST" class="flex items-center justify-end gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="user_id" value="{{ $user->id }}">
                                                <select name="role" class="rounded-md border-slate-300 py-1 pl-3 pr-8 text-xs focus:border-brand-500 focus:outline-none focus:ring-brand-500 sm:text-xs">
                                                    <option value="member" {{ $user->pivot->role === 'member' ? 'selected' : '' }}>Member</option>
                                                    <option value="billing_admin" {{ $user->pivot->role === 'billing_admin' ? 'selected' : '' }}>Billing Admin</option>
                                                </select>
                                                <x-ui.button type="submit" size="xs" variant="secondary">{{ __('Kaydet') }}</x-ui.button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-400 italic">{{ __('Rol değiştirilemez') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>
                </div>

                {{-- Owner Transfer Section --}}
                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                    <h4 class="text-sm font-medium text-slate-900 mb-2">{{ __('Hesap Sahibi Devri') }}</h4>
                    <p class="text-xs text-slate-500 mb-4">
                        {{ __('Dikkat: Hesap sahipliğini devrettiğinizde, mevcut sahip normal üye statüsüne düşecektir.') }}
                    </p>
                    
                    <form action="{{ route('admin.accounts.owner.update', $account) }}" method="POST" class="flex items-center gap-4">
                        @csrf
                        @method('PATCH')
                        <div class="w-full max-w-xs">
                             <select name="new_owner_user_id" class="block w-full rounded-md border-slate-300 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600 sm:text-sm sm:leading-6">
                                <option value="">{{ __('Yeni Sahip Seçin...') }}</option>
                                @foreach($account->users as $user)
                                    @if($user->id !== $account->owner_user_id)
                                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $maskEmail($user->email) }})</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <x-ui.button type="submit" variant="danger" size="sm" onclick="return confirm('Emin misiniz? Bu işlem geri alınamaz (sahiplik devredilecek).');">
                            {{ __('Sahipliği Devret') }}
                        </x-ui.button>
                    </form>
                </div>
            </x-ui.card>

            {{-- Manage / Update --}}
            <x-ui.card>
                <div class="border-b border-slate-200 pb-4 mb-4">
                    <h3 class="text-lg font-medium leading-6 text-slate-900">{{ __('Yönetim') }}</h3>
                </div>
                
                <form action="{{ route('admin.accounts.update', $account) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    
                    @if(($tenantLimit !== null && $tenantUsage > $tenantLimit) || ($seatLimit !== null && $seatUsage > $seatLimit))
                        <div class="mb-4 rounded-md bg-rose-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <x-icon.exclamation-triangle class="h-5 w-5 text-rose-400" />
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-rose-800">{{ __('Limit Aşımı Mevcut') }}</h3>
                                    <div class="mt-2 text-sm text-rose-700">
                                        <p>{{ __('Şu anda hesap limitleri aşılmış durumda. Daha düşük bir pakete geçiş yapmanız engellenecektir. Lütfen kullanıcıları veya firmaları azalttıktan sonra deneyin veya paketi yükseltin.') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="plan_key" :value="__('Paket Değiştir')" />
                            <select id="plan_key" name="plan_key" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                                @foreach(config('plans') as $key => $planConfig)
                                    @php
                                        // Optional: Check if moving to this plan would immediately block
                                        // This logic is mostly for UI hint, controller does hard check.
                                        $wouldBlockTenant = isset($planConfig['tenant_limit']) && $tenantUsage > $planConfig['tenant_limit'];
                                        $wouldBlockSeat = isset($planConfig['user_limit']) && $seatUsage > $planConfig['user_limit'];
                                        $disabled = $wouldBlockTenant || $wouldBlockSeat;
                                    @endphp
                                    <option value="{{ $key }}" {{ ($account->plan_key ?? 'starter') == $key ? 'selected' : '' }} {{ $disabled ? 'class=text-slate-400' : '' }}>
                                        {{ $planConfig['name'] }} (Limit: {{ $planConfig['user_limit'] ?? 'Sınırsız' }} Kullanıcı / {{ $planConfig['tenant_limit'] ?? 'Sınırsız' }} Firma)
                                        @if($disabled) [Yetersiz Limit] @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <x-input-label for="extra_seats_purchased" :value="__('Ek Kullanıcı (Seat) Satın Alındı')" />
                            <x-text-input id="extra_seats_purchased" name="extra_seats_purchased" type="number" min="0" class="mt-1 block w-full" :value="$account->extra_seats_purchased" />
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
