<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Kullanıcı Yönetimi') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Create User Form -->
            <x-ui.card>
                <header>
                    <h2 class="text-lg font-medium text-slate-900">
                        {{ __('Yeni Kullanıcı Ekle') }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ __('Sisteme yeni bir kullanıcı ekleyin.') }}
                    </p>
                </header>

                <form method="post" action="{{ route('admin.users.store') }}" class="mt-6 space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="name" :value="__('İsim')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div>
                            <x-input-label for="email" :value="__('E-posta')" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('email')" />
                        </div>

                        <div>
                            <x-input-label for="password" :value="__('Şifre')" />
                            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required />
                            <x-input-error class="mt-2" :messages="$errors->get('password')" />
                        </div>

                        <div>
                            <x-input-label for="password_confirmation" :value="__('Şifre Tekrar')" />
                            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required />
                            <x-input-error class="mt-2" :messages="$errors->get('password_confirmation')" />
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <label for="is_admin" class="inline-flex items-center">
                            <input id="is_admin" type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" name="is_admin" value="1">
                            <span class="ms-2 text-sm text-slate-600">{{ __('Admin Yetkisi Ver') }}</span>
                        </label>
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Kaydet') }}</x-primary-button>

                        @if (session('success'))
                            <p
                                x-data="{ show: true }"
                                x-show="show"
                                x-transition
                                x-init="setTimeout(() => show = false, 2000)"
                                class="text-sm text-slate-600"
                            >{{ session('success') }}</p>
                        @endif
                    </div>
                </form>
            </x-ui.card>

            <!-- Users List -->
            <x-ui.card>
                <header class="mb-4">
                    <h2 class="text-base font-semibold text-slate-900">
                        {{ __('Kullanıcı Listesi') }}
                    </h2>
                </header>

                <x-ui.table>
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 tracking-wide">{{ __('İsim') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 tracking-wide">{{ __('E-posta') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 tracking-wide">{{ __('Rol') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 tracking-wide">{{ __('Kayıt Tarihi') }}</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-slate-500 tracking-wide w-32">{{ __('İşlemler') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($users as $user)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-6 py-3 text-sm font-medium text-slate-900 max-w-0 truncate">
                                    {{ $user->name }}
                                    @if(auth()->id() === $user->id) 
                                        <span class="text-xs text-slate-500 ms-1">({{ __('Siz') }})</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-sm text-slate-600 max-w-0 truncate">{{ $user->email }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm">
                                    @if($user->is_admin)
                                        <x-ui.badge variant="primary">{{ __('Admin') }}</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="neutral">{{ __('Staff') }}</x-ui.badge>
                                    @endif
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-slate-600">{{ $user->created_at->format('d.m.Y H:i') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        
                                        <!-- Role Toggle -->
                                        <form id="user-role-update-{{ $user->id }}" action="{{ route('admin.users.update', $user) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_admin" value="{{ $user->is_admin ? '0' : '1' }}">
                                            <button type="submit" class="p-2 text-slate-400 hover:text-brand-600 hover:bg-brand-50 rounded-xl transition-all" 
                                                    title="{{ $user->is_admin ? __('Staff yetkisine düşür') : __('Admin yetkisi ver') }}"
                                                    data-confirm
                                                    data-confirm-title="{{ __('Emin misiniz?') }}"
                                                    data-confirm-message="{{ __('Kullanıcı yetkisi değiştirilecek.') }}"
                                                    data-confirm-text="{{ __('Onayla') }}"
                                                    data-confirm-cancel-text="{{ __('Vazgeç') }}"
                                                    data-confirm-submit="user-role-update-{{ $user->id }}">
                                                <x-icon.user class="h-4 w-4" />
                                            </button>
                                        </form>

                                        <!-- Password Reset -->
                                        <div x-data="{ open: false }">
                                            <button @click="open = true" class="p-2 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-xl transition-all" title="{{ __('Şifre Sıfırla') }}">
                                                <x-icon.key class="h-4 w-4" />
                                            </button>
                                            
                                            <x-modal name="password-reset-{{ $user->id }}" :show="false" focusable>
                                                <form method="post" action="{{ route('admin.users.password', $user) }}" class="text-left">
                                                    @csrf
                                                    @method('PATCH')

                                                    <h2 class="text-lg font-medium text-slate-900">
                                                        {{ __('Şifre Sıfırla') }} - {{ $user->name }}
                                                    </h2>

                                                    <div class="mt-6">
                                                        <x-input-label for="password_{{ $user->id }}" value="{{ __('Yeni Şifre') }}" />
                                                        <x-text-input id="password_{{ $user->id }}" name="password" type="password" class="mt-1 block w-full" placeholder="{{ __('Yeni şifre') }}" required />
                                                    </div>

                                                    <div class="mt-4">
                                                        <x-input-label for="password_confirmation_{{ $user->id }}" value="{{ __('Şifre Tekrar') }}" />
                                                        <x-text-input id="password_confirmation_{{ $user->id }}" name="password_confirmation" type="password" class="mt-1 block w-full" placeholder="{{ __('Şifre tekrar') }}" required />
                                                    </div>

                                                    <div class="mt-6 flex justify-end">
                                                        <x-secondary-button x-on:click="$dispatch('close')">
                                                            {{ __('İptal') }}
                                                        </x-secondary-button>

                                                        <x-primary-button class="ms-3">
                                                            {{ __('Güncelle') }}
                                                        </x-primary-button>
                                                    </div>
                                                </form>
                                            </x-modal>
                                            <!-- Custom trigger for x-modal usually uses dispatch open-modal with name -->
                                            <div x-init="$watch('open', value => value && $dispatch('open-modal', 'password-reset-{{ $user->id }}'))"></div>
                                        </div>

                                        @if(auth()->id() !== $user->id)
                                            <form id="user-delete-{{ $user->id }}" action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-all" 
                                                        title="{{ __('Kullanıcıyı Sil') }}" 
                                                        data-confirm
                                                        data-confirm-title="{{ __('Emin misiniz?') }}"
                                                        data-confirm-message="{{ __('Kullanıcı silinecek. Bu işlem geri alınamaz.') }}"
                                                        data-confirm-text="{{ __('Sil') }}"
                                                        data-confirm-cancel-text="{{ __('Vazgeç') }}"
                                                        data-confirm-submit="user-delete-{{ $user->id }}">
                                                    <x-icon.trash class="h-4 w-4" />
                                                </button>
                                            </form>
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
