<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ $tenant ? __('Firmayı Düzenle') : __('Yeni Firma') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <x-ui.card>
                <form method="post" action="{{ $tenant ? route('admin.tenants.update', $tenant) : route('admin.tenants.store') }}">
                    @csrf
                    @if($tenant)
                        @method('PATCH')
                    @endif

                    <div class="space-y-6">
                        <!-- Name -->
                        <div>
                            <x-input-label for="name" :value="__('Firma Adı')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $tenant->name ?? '')" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <!-- Owner Selection (New for PR4C2) -->
                        @if(!$tenant) <!-- Only show on create -->
                        <div>
                            <x-input-label for="owner_user_id" :value="__('Hesap Sahibi (Kullanıcı)')" />
                            <select name="owner_user_id" id="owner_user_id" class="mt-1 block w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm" required>
                                <option value="" disabled selected>{{ __('Kullanıcı Seçin') }}</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('owner_user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-slate-500 mt-1">{{ __('Bu firma hangi müşterinin hesabına bağlı olacak? Limitler bu hesaba göre uygulanır.') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('owner_user_id')" />
                        </div>
                        @endif

                        <!-- Slug (Readonly) -->
                        @if($tenant)
                            <div>
                                <x-input-label for="slug" :value="__('Slug (URL Uzantısı)')" />
                                <x-text-input id="slug" type="text" class="mt-1 block w-full bg-slate-50 text-slate-500" :value="$tenant->slug" readonly />
                                <p class="text-xs text-slate-400 mt-1">{{ __('Otomatik oluşturulur, değiştirilemez.') }}</p>
                            </div>
                        @endif

                        <!-- Domain -->
                        <div>
                            <x-input-label for="domain" :value="__('Özel Domain (Opsiyonel)')" />
                            <x-text-input id="domain" name="domain" type="text" class="mt-1 block w-full" :value="old('domain', $tenant->domain ?? '')" placeholder="örnek: firma.com" />
                            <p class="text-xs text-slate-500 mt-1">{{ __('Domain yönlendirmesi için DNS ayarlarının yapılmış olması gerekir.') }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">{{ __('http(s)://, port (:8000) veya uzantı (/app) yazmayınız. Sadece alan adı.') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('domain')" />
                        </div>

                        <!-- Status -->
                        <div class="flex items-center gap-4">
                            <label for="is_active" class="inline-flex items-center">
                                <input id="is_active" type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" name="is_active" value="1" {{ old('is_active', $tenant->is_active ?? true) ? 'checked' : '' }}>
                                <span class="ms-2 text-sm text-slate-600">{{ __('Firma Aktif') }}</span>
                            </label>
                        </div>

                        <div class="flex items-center justify-end gap-4 pt-4 border-t border-slate-100">
                            <a href="{{ route('admin.tenants.index') }}" class="text-sm text-slate-500 hover:text-slate-700">{{ __('İptal') }}</a>
                            <x-primary-button>{{ __('Kaydet') }}</x-primary-button>
                        </div>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
