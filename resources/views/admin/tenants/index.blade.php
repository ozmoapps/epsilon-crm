<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Firma Yönetimi') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Tenants List -->
            <x-ui.card>
                <header class="mb-4 flex justify-between items-center">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">
                            {{ __('Firma Listesi') }}
                        </h2>
                        <p class="text-sm text-slate-500">
                            {{ __('Sistemdeki tüm firmaların durumu ve erişim ayarları.') }}
                        </p>
                    </div>
                    <x-ui.button href="{{ route('admin.tenants.create') }}" variant="primary" size="sm">
                        <x-icon.plus class="h-4 w-4 mr-1" />
                        {{ __('Yeni Firma') }}
                    </x-ui.button>
                </header>

                <x-ui.table>
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 tracking-wide">{{ __('Firma Adı') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 tracking-wide">{{ __('Slug') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 tracking-wide">{{ __('Domain') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 tracking-wide">{{ __('Plan / Limitler') }}</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-slate-500 tracking-wide">{{ __('Kullanıcı') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 tracking-wide">{{ __('Durum') }}</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-slate-500 tracking-wide w-32">{{ __('İşlemler') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($tenants as $tenant)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-6 py-3 text-sm font-medium text-slate-900">
                                    {{ $tenant->name }}
                                </td>
                                <td class="px-6 py-3 text-sm text-slate-600 font-mono text-xs">
                                    {{ $tenant->slug ?? '-' }}
                                </td>
                                <td class="px-6 py-3 text-sm text-slate-600">
                                    {{ $tenant->domain }}
                                </td>
                                <td class="px-6 py-3 text-sm">
                                    <div class="flex flex-col gap-1">
                                        @if($tenant->plan)
                                            <span class="font-medium text-slate-700">{{ $tenant->plan->name_tr }}</span>
                                            <span class="text-xs text-slate-500">
                                                @php
                                                    $tLimit = $tenant->account?->effectiveTenantLimit();
                                                    $sLimit = $tenant->account?->effectiveSeatLimit();
                                                @endphp
                                                {{ $tLimit ?? '∞' }} Firma / {{ $sLimit ?? '∞' }} Kul.
                                            </span>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-sm text-slate-600 text-center">
                                    <x-ui.badge variant="neutral" size="sm">{{ $tenant->users_count }}</x-ui.badge>
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm">
                                    @if($tenant->is_active)
                                        <x-ui.badge variant="success">{{ __('Aktif') }}</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="danger">{{ __('Pasif') }}</x-ui.badge>
                                    @endif
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        
                                        <!-- Active Toggle -->
                                        <a href="{{ route('admin.tenants.edit', $tenant) }}" class="p-2 text-slate-400 hover:text-brand-600 hover:bg-brand-50 rounded-xl transition-all" title="{{ __('Düzenle') }}">
                                            <x-icon.pencil class="h-4 w-4" />
                                        </a>

                                        @php
                                            $isSelf = session('current_tenant_id') == $tenant->id;
                                        @endphp
                                        <form id="tenant-toggle-{{ $tenant->id }}" action="{{ route('admin.tenants.toggle-active', $tenant) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            
                                            @if($tenant->is_active)
                                                <button type="submit" class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-xl transition-all" 
                                                        title="{{ __('Pasif Yap') }}"
                                                        {{ $isSelf ? 'disabled opacity-50 cursor-not-allowed' : '' }}
                                                        data-confirm
                                                        data-confirm-title="{{ __('Emin misiniz?') }}"
                                                        data-confirm-message="{{ $tenant->name . ' pasif duruma getirilecek. Kullanıcılar erişemeyecek.' }}"
                                                        data-confirm-text="{{ __('Pasif Yap') }}"
                                                        data-confirm-variant="danger"
                                                        data-confirm-cancel-text="{{ __('Vazgeç') }}"
                                                        data-confirm-submit="tenant-toggle-{{ $tenant->id }}">
                                                    <x-icon.x class="h-4 w-4 text-rose-500" />
                                                </button>
                                            @else
                                                <button type="submit" class="p-2 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-xl transition-all" 
                                                        title="{{ __('Aktif Yap') }}"
                                                        data-confirm
                                                        data-confirm-title="{{ __('Emin misiniz?') }}"
                                                        data-confirm-message="{{ $tenant->name . ' tekrar aktif hale getirilecek.' }}"
                                                        data-confirm-text="{{ __('Aktif Yap') }}"
                                                        data-confirm-variant="primary"
                                                        data-confirm-cancel-text="{{ __('Vazgeç') }}"
                                                        data-confirm-submit="tenant-toggle-{{ $tenant->id }}">
                                                    <x-icon.check class="h-4 w-4" />
                                                </button>
                                            @endif
                                        </form>

                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
