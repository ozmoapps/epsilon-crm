<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Davetler') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Invite Form -->
            <x-ui.card>
                <div class="p-6">
                    <h3 class="text-lg font-medium text-slate-900 mb-4">{{ __('Yeni Davet Oluştur') }}</h3>
                    <form action="{{ route('manage.invitations.store') }}" method="POST" class="flex gap-4 items-end">
                        @csrf
                        <div class="flex-1">
                            <x-input-label for="email" :value="__('E-posta Adresi')" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" placeholder="user@example.com" required />
                        </div>
                        <div class="w-48">
                            <x-input-label for="role" :value="__('Rol')" />
                            <select id="role" name="role" class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-xl shadow-sm">
                                <option value="staff">{{ __('Personel') }}</option>
                                <option value="admin">{{ __('Yönetici') }}</option>
                            </select>
                        </div>
                        <x-primary-button class="mb-[2px]">{{ __('Davet Gönder') }}</x-primary-button>
                    </form>
                    
                    @if(session('invite_link'))
                        <div x-data="{ link: @js(session('invite_link')), copied: false }" class="mt-4 p-4 bg-emerald-50 border border-emerald-100 rounded-xl flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-emerald-800">{{ __('Davet Linki Oluşturuldu!') }}</p>
                                <p class="text-xs text-emerald-600 mt-1">{{ __('Bu linki kopyalayıp kullanıcıya iletebilirsiniz.') }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <code class="text-xs bg-white px-2 py-1 rounded border border-emerald-200 text-emerald-600" x-text="link"></code>
                                <button 
                                    @click="navigator.clipboard.writeText(link).then(() => { copied = true; setTimeout(() => copied = false, 2000) })" 
                                    class="text-emerald-600 hover:text-emerald-800 font-medium text-sm px-2 py-1"
                                    x-text="copied ? '{{ __('Kopyalandı!') }}' : '{{ __('Kopyala') }}'"
                                >
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </x-ui.card>

            <!-- Invitations List -->
            <x-ui.card>
                <div class="p-6">
                    <h3 class="text-lg font-medium text-slate-900 mb-6">{{ __('Davet Listesi') }}</h3>
                    
                    <x-ui.table>
                        <x-slot name="head">
                            <x-ui.table.th>{{ __('E-posta') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('Rol') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('Durum') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('Tarih') }}</x-ui.table.th>
                            <x-ui.table.th class="text-right">{{ __('İşlemler') }}</x-ui.table.th>
                        </x-slot>
                        <x-slot name="body">
                            @forelse ($invitations as $invitation)
                                <tr>
                                    <x-ui.table.td class="font-medium text-slate-900">{{ $invitation->email }}</x-ui.table.td>
                                    <x-ui.table.td>
                                        <x-ui.badge variant="neutral">{{ ucfirst($invitation->role) }}</x-ui.badge>
                                    </x-ui.table.td>
                                    <x-ui.table.td>
                                        @if($invitation->accepted_at)
                                            <x-ui.badge variant="success">{{ __('Kabul Edildi') }}</x-ui.badge>
                                            <div class="text-xs text-slate-400 mt-1">{{ $invitation->acceptedBy->name ?? '-' }}</div>
                                        @elseif($invitation->expires_at->isPast())
                                            <x-ui.badge variant="danger">{{ __('Süresi Dolmuş') }}</x-ui.badge>
                                        @else
                                            <x-ui.badge variant="warning">{{ __('Bekliyor') }}</x-ui.badge>
                                        @endif
                                    </x-ui.table.td>
                                    <x-ui.table.td class="text-slate-500 text-xs">
                                        <div>{{ __('Oluşturma:') }} {{ $invitation->created_at->format('d.m.Y H:i') }}</div>
                                        <div>{{ __('Bitiş:') }} {{ $invitation->expires_at->format('d.m.Y') }}</div>
                                    </x-ui.table.td>
                                    <x-ui.table.td class="text-right">
                                        @if(!$invitation->accepted_at)
                                            <div class="flex items-center justify-end gap-2">
                                                <form action="{{ route('manage.invitations.regenerate', $invitation) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="text-slate-400 hover:text-brand-600 transition-colors" title="{{ __('Yenile') }}">
                                                        <x-icon.refresh class="h-4 w-4" />
                                                    </button>
                                                </form>
                                                
                                                <form action="{{ route('manage.invitations.destroy', $invitation) }}" method="POST" onsubmit="return confirm('Bu daveti iptal etmek istediğinize emin misiniz?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-slate-400 hover:text-rose-600 transition-colors" title="{{ __('İptal Et') }}">
                                                        <x-icon.trash class="h-4 w-4" />
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </x-ui.table.td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-slate-500">
                                        {{ __('Henüz davet bulunmuyor.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </x-slot>
                    </x-ui.table>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
