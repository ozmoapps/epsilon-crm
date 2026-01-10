<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-semibold text-slate-900">{{ __('Şifre Sıfırlama') }}</h1>
        <p class="mt-1 text-sm text-slate-500">
            {{ __('E-posta adresinizi paylaşın, size şifre sıfırlama bağlantısı gönderelim.') }}
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-6 flex items-center justify-end">
            <x-primary-button>
                {{ __('Şifre Sıfırlama Bağlantısı Gönder') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
