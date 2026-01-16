{{-- resources/views/auth/login.blade.php --}}
<x-guest-layout>
    <div class="relative w-full">
        {{-- Calm background (slot içinde, guest layout’a zarar vermez) --}}
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
                {{ __('Tekne operasyonunu tek panelden yönetin.') }}
            </div>

            <div class="mt-3 flex flex-wrap justify-center gap-2">
                <x-ui.badge variant="neutral">{{ __('Calm UI') }}</x-ui.badge>
                <x-ui.badge variant="info">{{ __('Tekne/Proje Bazlı') }}</x-ui.badge>
                <x-ui.badge variant="success">{{ __('Doğrulanabilir Akışlar') }}</x-ui.badge>
            </div>
        </div>

        {{-- Card --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            {{-- Session Status --}}
            <x-auth-session-status class="mb-4" :status="session('status')" />

            {{-- Mini value copy --}}
            <div class="mb-5 rounded-2xl bg-slate-50 p-4">
                <div class="text-sm font-semibold text-slate-900">{{ __('Hızlı giriş, net takip.') }}</div>
                <div class="mt-1 text-sm leading-relaxed text-slate-600">
                    {{ __('Fatura, tahsilat, cari ve stok akışlarını aynı mantıkla izleyin. Arama/filtre alışkanlığı her yerde aynı; operasyon daha hızlı ilerler.') }}
                </div>
            </div>

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
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

                {{-- Password --}}
                <div>
                    <x-input-label for="password" :value="__('Şifre')" />
                    <x-input
                        id="password"
                        class="mt-1 block w-full"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="{{ __('Şifrenizi girin') }}"
                    />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                {{-- Remember + Forgot --}}
                <div class="flex items-center justify-between gap-3">
                    <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input
                            id="remember_me"
                            type="checkbox"
                            class="rounded border-slate-300 text-slate-900 shadow-sm focus:ring-slate-900"
                            name="remember"
                        >
                        <span>{{ __('Beni hatırla') }}</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a
                            class="text-sm font-semibold text-slate-600 hover:text-slate-900 hover:underline"
                            href="{{ route('password.request') }}"
                        >
                            {{ __('Şifremi unuttum') }}
                        </a>
                    @endif
                </div>

                {{-- Submit --}}
                <div class="pt-1">
                    <x-ui.button type="submit" class="w-full justify-center">
                        {{ __('Giriş Yap') }}
                    </x-ui.button>
                </div>

                {{-- Divider --}}
                <div class="py-2">
                    <div class="flex items-center gap-3">
                        <div class="h-px flex-1 bg-slate-200"></div>
                        <div class="text-xs text-slate-500">{{ __('veya') }}</div>
                        <div class="h-px flex-1 bg-slate-200"></div>
                    </div>
                </div>

                {{-- Secondary actions --}}
                <div class="flex flex-col gap-2">
                    @if (Route::has('register'))
                        <x-ui.button href="{{ route('register') }}" variant="secondary" class="w-full justify-center">
                            {{ __('Yeni hesap oluştur') }}
                        </x-ui.button>
                    @endif

                    <a href="{{ url('/') }}" class="text-center text-xs font-semibold text-slate-600 hover:text-slate-900 hover:underline">
                        {{ __('Ana sayfaya dön') }}
                    </a>
                </div>
            </form>
        </div>

        {{-- Footer note --}}
        <div class="mt-5 text-center text-xs text-slate-500">
            {{ __('Güvenli oturum • Sade arayüz • Tekne/Proje odaklı takip') }}
        </div>
    </div>
</x-guest-layout>
