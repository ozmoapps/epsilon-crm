<x-app-layout>
    <div class="min-h-screen flex flex-col items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <x-ui.card class="text-center p-8">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 mb-6">
                    <x-icon.building class="h-8 w-8 text-slate-500" />
                </div>
                
                <h2 class="text-xl font-bold text-slate-900 mb-2">
                    {{ __('Henüz bir firmaya üye değilsiniz') }}
                </h2>
                
                <p class="text-slate-600 mb-8">
                    {{ __('Bu hesaba bağlı herhangi bir firma bulunamadı. Lütfen yöneticinizden davet isteyin veya e-postanıza gelen davet bağlantısını kullanın.') }}
                </p>

                <div class="flex flex-col gap-3">
                    <x-ui.button href="{{ route('profile.edit') }}" variant="secondary" class="w-full justify-center">
                        {{ __('Profil Ayarları') }}
                    </x-ui.button>
                    
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-ui.button type="submit" variant="ghost" class="w-full justify-center text-rose-600 hover:text-rose-700 hover:bg-rose-50">
                            {{ __('Çıkış Yap') }}
                        </x-ui.button>
                    </form>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
