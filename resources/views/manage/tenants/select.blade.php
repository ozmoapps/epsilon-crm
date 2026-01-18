<x-app-layout>
    <div class="min-h-screen flex flex-col items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-slate-900">
                    {{ __('Firma Seçin') }}
                </h2>
                <p class="mt-2 text-sm text-slate-600">
                    {{ __('Devam etmek için işlem yapmak istediğiniz firmayı seçin.') }}
                </p>
            </div>

            <x-ui.card class="divide-y divide-slate-100">
                @forelse(auth()->user()->tenants as $tenant)
                    <div class="flex items-center justify-between p-4 hover:bg-slate-50 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-lg bg-brand-100 flex items-center justify-center text-brand-700 font-bold">
                                {{ substr($tenant->name, 0, 1) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-900 truncate">
                                    {{ $tenant->name }}
                                </p>
                                @if($tenant->domain)
                                    <p class="text-xs text-slate-500 truncate">
                                        {{ $tenant->domain }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <form method="POST" action="{{ route('tenants.switch') }}">
                            @csrf
                            <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">
                            <x-ui.button type="submit" variant="secondary" size="sm">
                                {{ __('Seç') }}
                            </x-ui.button>
                        </form>
                    </div>
                @empty
                    <div class="p-6 text-center text-slate-500">
                        {{ __('Listelenecek firma bulunamadı.') }}
                    </div>
                @endforelse
            </x-ui.card>

            <div class="text-center">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-slate-500 hover:text-slate-700 underline">
                        {{ __('Farklı bir hesapla giriş yap') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
