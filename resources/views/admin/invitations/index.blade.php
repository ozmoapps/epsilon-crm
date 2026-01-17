<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Davet Yönetimi') }}
            </h2>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-brand-600 hover:text-brand-800">
                {{ __('&larr; Kullanıcılara Dön') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-ui.card>
                <header class="mb-4">
                    <h2 class="text-base font-semibold text-slate-900">
                        {{ __('Bekleyen ve Kabul Edilen Davetler') }}
                    </h2>
                    <p class="text-sm text-slate-500">{{ __('Tüm firma davetlerini buradan yönetebilirsiniz.') }}</p>
                </header>

                @if (session('invite_link'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-sm text-green-800 font-medium mb-2">{{ __('Yeni Link Oluşturuldu:') }}</p>
                        <div class="flex items-center gap-2">
                             <code class="block w-full p-2 bg-white border border-gray-200 rounded text-sm text-gray-600 select-all">{{ session('invite_link') }}</code>
                             <button onclick="navigator.clipboard.writeText(@js(session('invite_link'))).then(() => alert('Kopyalandı!')).catch(() => alert('Kopyalama başarısız.'));" class="p-2 text-gray-500 hover:text-gray-700">
                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                     <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" />
                                     <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z" />
                                 </svg>
                             </button>
                        </div>
                    </div>
                @endif

                <x-ui.table>
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 tracking-wide">{{ __('Firma') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 tracking-wide">{{ __('E-posta') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 tracking-wide">{{ __('Durum') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 tracking-wide">{{ __('Geçerlilik') }}</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-slate-500 tracking-wide">{{ __('İşlemler') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($invitations as $invite)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-6 py-3 text-sm font-medium text-slate-900">
                                    {{ $invite->tenant->name }}
                                </td>
                                <td class="px-6 py-3 text-sm text-slate-600">{{ $invite->email }}</td>
                                <td class="px-6 py-3 text-sm">
                                    @if($invite->accepted_at)
                                        <x-ui.badge variant="success" size="sm">{{ __('Kabul Edildi') }}</x-ui.badge>
                                        <div class="text-xs text-slate-400 mt-1">
                                            {{ $invite->accepted_at->format('d.m.Y H:i') }}
                                            @if($invite->acceptedBy)
                                                ({{ $invite->acceptedBy->name }})
                                            @endif
                                        </div>
                                    @elseif($invite->expires_at->isPast())
                                        <x-ui.badge variant="danger" size="sm">{{ __('Süresi Doldu') }}</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="neutral" size="sm">{{ __('Bekliyor') }}</x-ui.badge>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-sm text-slate-600">
                                    {{ $invite->expires_at->format('d.m.Y H:i') }}
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        @if(!$invite->accepted_at)
                                            <!-- Regenerate -->
                                            <form action="{{ route('admin.invitations.regenerate', $invite) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <x-ui.button type="submit" variant="secondary" size="sm" title="{{ __('Linki Yenile ve Süreyi Uzat') }}">
                                                    {{ __('Yenile') }}
                                                </x-ui.button>
                                            </form>
                                            
                                            <!-- Revoke -->
                                            <form action="{{ route('admin.invitations.destroy', $invite) }}" method="POST" class="inline" 
                                                  onsubmit="return confirm('Bu daveti iptal etmek istediğinize emin misiniz?');">
                                                @csrf
                                                @method('DELETE')
                                                <x-ui.button type="submit" variant="danger" size="sm">
                                                    {{ __('İptal Et') }}
                                                </x-ui.button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-400">{{ __('Tamamlandı') }}</span>
                                        @endif
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
