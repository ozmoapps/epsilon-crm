<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Deneme Süreniz Doldu" subtitle="Ödeme ve Abonelik" />
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg rounded-2xl border border-slate-100 p-8 text-center">
                <div class="mx-auto w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mb-4">
                    <x-icon.exclamation-triangle class="w-8 h-8 text-red-400" />
                </div>
                
                <h3 class="text-lg font-bold text-slate-900 mb-2">Deneme Süreniz Sona Erdi</h3>
                <p class="text-slate-500 mb-8 max-w-md mx-auto">
                    Deneme süreniz sona erdi. Yakında burada ödeme ekranı olacak. Devam etmek için paket yükseltmeniz gerekiyor.
                </p>

                <div class="flex items-center justify-center gap-4">
                     <!-- Logout Form -->
                     <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-ui.button type="submit" variant="secondary">
                            {{ __('Çıkış Yap') }}
                        </x-ui.button>
                     </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
