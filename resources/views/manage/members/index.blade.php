<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Üyeler') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-ui.card>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-medium text-slate-900">{{ __('Firma Üyeleri') }}</h3>
                        <x-ui.button href="{{ route('manage.invitations.index') }}" variant="primary" size="sm">
                            <x-icon.plus class="h-4 w-4 mr-1" />
                            {{ __('Yeni Davet') }}
                        </x-ui.button>
                    </div>

                    <x-ui.table>
                        <x-slot name="head">
                            <x-ui.table.th>{{ __('İsim') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('E-posta') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('Rol') }}</x-ui.table.th>
                            <x-ui.table.th class="text-right">{{ __('İşlemler') }}</x-ui.table.th>
                        </x-slot>
                        <x-slot name="body">
                            @foreach ($members as $member)
                                <tr>
                                    <x-ui.table.td>
                                        <div class="font-medium text-slate-900">{{ $member->name }}</div>
                                    </x-ui.table.td>
                                    <x-ui.table.td class="text-slate-500">{{ $member->email }}</x-ui.table.td>
                                    <x-ui.table.td>
                                        @if($member->pivot->role === 'admin')
                                            <x-ui.badge variant="success">{{ __('Yönetici') }}</x-ui.badge>
                                        @else
                                            <x-ui.badge variant="neutral">{{ __('Personel') }}</x-ui.badge>
                                        @endif
                                    </x-ui.table.td>
                                    <x-ui.table.td class="text-right">
                                        @if(auth()->id() !== $member->id)
                                            <form action="{{ route('manage.members.destroy', $member) }}" method="POST" class="inline-block" onsubmit="return confirm('Bu kullanıcıyı firmadan çıkarmak istediğinize emin misiniz?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-slate-400 hover:text-rose-600 transition-colors" title="{{ __('Çıkar') }}">
                                                    <x-icon.trash class="h-4 w-4" />
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-300 italic">{{ __('Kendiniz') }}</span>
                                        @endif
                                    </x-ui.table.td>
                                </tr>
                            @endforeach
                        </x-slot>
                    </x-ui.table>
                </div>
            </x-ui.card>

            @if(config('privacy.break_glass_enabled'))
            <x-ui.card class="mt-6 border-amber-200 bg-amber-50">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-amber-900">{{ __('Platform Destek Erişimi') }}</h3>
                            <p class="text-sm text-amber-700 mt-1">
                                {{ __('Platform yöneticilerinin firmanıza geçici olarak erişmesi gerekiyorsa, buradan sınırlı süreli bir erişim linki oluşturabilirsiniz.') }}
                            </p>
                        </div>
                        <form action="{{ route('manage.support-access.store') }}" method="POST">
                            @csrf
                            <x-ui.button type="submit" variant="neutral" class="bg-white border-amber-300 text-amber-900 hover:bg-amber-100">
                                {{ __('Erişim Linki Oluştur') }}
                            </x-ui.button>
                        </form>
                    </div>

                    @if(session('support_link'))
                        <div class="mt-4 p-4 bg-white rounded-md border border-amber-200">
                            <p class="text-xs text-slate-500 mb-1 font-bold uppercase">{{ __('Aşağıdaki linki paylaşın:') }}</p>
                            <div class="flex items-center gap-2">
                                <code class="block w-full text-sm bg-slate-50 p-2 rounded border border-slate-200 select-all font-mono text-slate-700">
                                    {{ session('support_link') }}
                                </code>
                            </div>
                            <p class="text-xs text-amber-600 mt-2">
                                {{ __('Bu link 60 dakika geçerlidir.') }}
                            </p>
                        </div>
                    @endif
                </div>
            </x-ui.card>
            @endif
        </div>
    </div>
</x-app-layout>
