{{-- resources/views/auth/register.blade.php --}}
<x-guest-layout>
    <div class="relative w-full">
        {{-- Calm background --}}
        <div class="pointer-events-none absolute -inset-6 -z-10">
            <div class="absolute inset-0 rounded-[32px] bg-gradient-to-b from-slate-50 via-white to-slate-50"></div>
            <div class="absolute -top-24 left-1/2 h-64 w-64 -translate-x-1/2 rounded-full bg-slate-200/40 blur-3xl"></div>
            <div class="absolute -bottom-24 left-1/3 h-72 w-72 rounded-full bg-slate-200/30 blur-3xl"></div>
        </div>

        {{-- Header / Brand --}}
        <div class="mb-6 text-center">
            <div class="mx-auto mb-3 grid h-11 w-11 place-items-center rounded-2xl bg-slate-900 text-white shadow-sm">
                <span class="text-base font-black">ε</span>
            </div>

            <div class="text-lg font-semibold text-slate-900">
                {{ config('app.name', 'Epsilon CRM') }}
            </div>
            <div class="mt-1 text-sm text-slate-600">
                {{ __('Hesap oluşturun ve operasyonu tek panelde toplayın.') }}
            </div>

            <div class="mt-3 flex flex-wrap justify-center gap-2">
                <x-ui.badge variant="neutral">{{ __('Calm UI') }}</x-ui.badge>
                <x-ui.badge variant="info">{{ __('Tekne/Proje Bazlı') }}</x-ui.badge>
                <x-ui.badge variant="success">{{ __('Daha Net Takip') }}</x-ui.badge>
            </div>
        </div>

        {{-- Card --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5 rounded-2xl bg-slate-50 p-4">
                <div class="text-sm font-semibold text-slate-900">{{ __('Dakikalar içinde başlayın.') }}</div>
                <div class="mt-1 text-sm leading-relaxed text-slate-600">
                    {{ __('Müşteri/tekne kayıtlarını açın, teklif akışını başlatın, fatura ve tahsilatları aynı mantıkla yönetin.') }}
                </div>
            </div>

            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf

                {{-- Name --}}
                <div>
                    <x-input-label for="name" :value="__('Ad Soyad')" />
                    <x-input
                        id="name"
                        class="mt-1 block w-full"
                        type="text"
                        name="name"
                        :value="old('name')"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="{{ __('Adınız Soyadınız') }}"
                    />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                {{-- Email --}}
                <div>
                    <x-input-label for="email" :value="__('E-posta')" />
                    <x-input
                        id="email"
                        class="mt-1 block w-full"
                        type="email"
                        name="email"
                        :value="old('email')"
                        required
                        autocomplete="username"
                        placeholder="{{ __('ornek@firma.com') }}"
                    />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                {{-- Password --}}
                <div>
                    <x-input-label for="password" :value="__('Şifre')" />
                    <x-input
                        id="password"
                        class="mt-1 block w-full"
                        type="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        placeholder="{{ __('Güçlü bir şifre belirleyin') }}"
                    />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                {{-- Confirm Password --}}
                <div>
                    <x-input-label for="password_confirmation" :value="__('Şifre (Tekrar)')" />
                    <x-input
                        id="password_confirmation"
                        class="mt-1 block w-full"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="{{ __('Şifreyi tekrar girin') }}"
                    />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                {{-- Submit --}}
                <div class="pt-1">
                    <x-ui.button type="submit" class="w-full justify-center">
                        {{ __('Hesap Oluştur') }}
                    </x-ui.button>
                </div>

                {{-- Divider --}}
                <div class="py-2">
                    <div class="flex items-center gap-3">
                        <div class="h-px flex-1 bg-slate-200"></div>
                        <div class="text-xs text-slate-500">{{ __('zaten hesabınız var mı?') }}</div>
                        <div class="h-px flex-1 bg-slate-200"></div>
                    </div>
                </div>

                {{-- Secondary --}}
                <div class="flex flex-col gap-2">
                    <x-ui.button href="{{ route('login') }}" variant="secondary" class="w-full justify-center">
                        {{ __('Giriş Yap') }}
                    </x-ui.button>

                    <a href="{{ url('/') }}" class="text-center text-xs font-semibold text-slate-600 hover:text-slate-900 hover:underline">
                        {{ __('Ana sayfaya dön') }}
                    </a>
                </div>
            </form>
        </div>

        <div class="mt-5 text-center text-xs text-slate-500">
            {{ __('Güvenli oturum • Sade arayüz • Tekne odaklı iş takibi') }}
        </div>
    </div>
</x-guest-layout>
