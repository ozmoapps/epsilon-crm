<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Kullanıcı Yönetimi') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Create User Form -->
            <x-ui.card>
                <header>
                    <h2 class="text-lg font-medium text-gray-900">
                        {{ __('Yeni Kullanıcı Ekle') }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
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
                            <input id="is_admin" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="is_admin" value="1">
                            <span class="ms-2 text-sm text-gray-600">{{ __('Admin Yetkisi Ver') }}</span>
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
                                class="text-sm text-gray-600"
                            >{{ session('success') }}</p>
                        @endif
                    </div>
                </form>
            </x-ui.card>

            <!-- Users List -->
            <x-ui.card>
                <header class="mb-4">
                    <h2 class="text-lg font-medium text-gray-900">
                        {{ __('Kullanıcı Listesi') }}
                    </h2>
                </header>

                <div class="overflow-x-auto sm:overflow-visible">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 tracking-wider">İsim</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 tracking-wider">E-posta</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 tracking-wider">Rol</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 tracking-wider">Kayıt Tarihi</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 tracking-wider">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($users as $user)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $user->name }}
                                        @if(auth()->id() === $user->id) 
                                            <span class="text-xs text-gray-500 ms-1">(Siz)</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->email }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if($user->is_admin)
                                            <x-ui.badge color="purple">Admin</x-ui.badge>
                                        @else
                                            <x-ui.badge color="gray">Staff</x-ui.badge>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->created_at->format('d.m.Y H:i') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2">
                                            
                                            <!-- Role Toggle -->
                                            <form action="{{ route('admin.users.update', $user) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="is_admin" value="{{ $user->is_admin ? '0' : '1' }}">
                                                <button type="submit" class="text-indigo-600 hover:text-indigo-900 text-xs" 
                                                        title="{{ $user->is_admin ? 'Staff yetkisine düşür' : 'Admin yetkisi ver' }}"
                                                        onclick="return confirm('Yetkiyi değiştirmek istediğinize emin misiniz?')">
                                                    {{ $user->is_admin ? 'Staff Yap' : 'Admin Yap' }}
                                                </button>
                                            </form>

                                            <!-- Password Reset -->
                                            <div x-data="{ open: false }">
                                                <button @click="open = true" class="text-yellow-600 hover:text-yellow-900 text-xs">Şifre</button>
                                                
                                                <x-modal name="password-reset-{{ $user->id }}" :show="false" focusable>
                                                    <form method="post" action="{{ route('admin.users.password', $user) }}" class="p-6 text-left">
                                                        @csrf
                                                        @method('PATCH')

                                                        <h2 class="text-lg font-medium text-gray-900">
                                                            {{ __('Şifre Sıfırla') }} - {{ $user->name }}
                                                        </h2>

                                                        <div class="mt-6">
                                                            <x-input-label for="password_{{ $user->id }}" value="{{ __('Yeni Şifre') }}" />
                                                            <x-text-input id="password_{{ $user->id }}" name="password" type="password" class="mt-1 block w-full" placeholder="Yeni şifre" required />
                                                        </div>

                                                        <div class="mt-4">
                                                            <x-input-label for="password_confirmation_{{ $user->id }}" value="{{ __('Şifre Tekrar') }}" />
                                                            <x-text-input id="password_confirmation_{{ $user->id }}" name="password_confirmation" type="password" class="mt-1 block w-full" placeholder="Şifre tekrar" required />
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
                                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900 text-xs" onclick="return confirm('Kullanıcıyı silmek istediğinize emin misiniz?')">
                                                        Sil
                                                    </button>
                                                </form>
                                            @endif

                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
