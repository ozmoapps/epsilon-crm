{{-- resources/views/auth/forgot-password.blade.php --}}
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
                {{ __('Şifre Yenileme') }}
            </div>
            <div class="mt-1 text-sm text-slate-600">
                {{ __('E-posta adresinizi girin, size yenileme bağlantısı gönderelim.') }}
            </div>

            <div class="mt-3 flex flex-wrap justify-center gap-2">
                <x-ui.badge variant="neutral">{{ __('Güvenli') }}</x-ui.badge>
                <x-ui.badge variant="info">{{ __('Hızlı') }}</x-ui.badge>
            </div>
        </div>

        {{-- Card --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <div class="mb-5 rounded-2xl bg-slate-50 p-4">
                <div class="text-sm font-semibold text-slate-900">{{ __('Kolay yenileme') }}</div>
                <div class="mt-1 text-sm leading-relaxed text-slate-600">
                    {{ __('Bağlantı size ulaştığında, yeni şifrenizi belirleyip tekrar giriş yapabilirsiniz.') }}
                </div>
            </div>

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf

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
                        autofocus
                        autocomplete="username"
                        placeholder="{{ __('ornek@firma.com') }}"
                    />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                {{-- Submit --}}
                <div class="pt-1">
                    <x-ui.button type="submit" class="w-full justify-center">
                        {{ __('Yenileme Bağlantısı Gönder') }}
                    </x-ui.button>
                </div>

                {{-- Secondary --}}
                <div class="flex flex-col gap-2 pt-1">
                    <x-ui.button href="{{ route('login') }}" variant="secondary" class="w-full justify-center">
                        {{ __('Giriş ekranına dön') }}
                    </x-ui.button>

                    @if (Route::has('register'))
                        <x-ui.button href="{{ route('register') }}" variant="ghost" class="w-full justify-center">
                            {{ __('Hesap oluştur') }}
                        </x-ui.button>
                    @endif

                    <a href="{{ url('/') }}" class="text-center text-xs font-semibold text-slate-600 hover:text-slate-900 hover:underline">
                        {{ __('Ana sayfaya dön') }}
                    </a>
                </div>
            </form>
        </div>

        <div class="mt-5 text-center text-xs text-slate-500">
            {{ __('Güvenli oturum • Sade arayüz') }}
        </div>
    </div>
</x-guest-layout>
