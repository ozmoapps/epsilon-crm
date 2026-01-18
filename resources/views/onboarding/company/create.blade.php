<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Firma Oluştur') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-slate-900">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-slate-900">{{ __('Yeni Firma') }}</h3>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ __('Platformu kullanmaya başlamak için bir firma oluşturun veya size gönderilen bir daveti kabul edin.') }}
                        </p>
                    </div>

                    <form method="POST" action="{{ route('onboarding.company.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="name" :value="__('Firma Adı')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus autocomplete="organization" placeholder="Örn: Epsilon Denizcilik" />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <a href="{{ route('manage.tenants.join') }}" class="text-sm text-slate-600 hover:text-slate-900 underline">
                                {{ __('Davet bağlantım var') }}
                            </a>

                            <x-ui.button type="submit" variant="primary">
                                {{ __('Firma Oluştur ve Devam Et') }}
                            </x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
