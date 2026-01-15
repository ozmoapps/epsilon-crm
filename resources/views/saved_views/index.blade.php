<x-app-layout>
    <x-slot name="header">
        <h2 class="text-3xl font-semibold text-slate-900 leading-tight">
            {{ __('Kayıtlı Görünümler') }}
            @if($scope)
                <span class="ml-2 text-sm text-slate-500">({{ ucfirst(str_replace('_', ' ', $scope)) }})</span>
            @endif
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-card sm:rounded-2xl">
                <div class="p-6 text-slate-900">
                    
                    @if(session('success'))
                        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                            <input id="is_shared" type="checkbox" class="rounded border-slate-300 text-brand-600 focus:border-brand-300 focus:ring focus:ring-brand-200 focus:ring-opacity-50" name="is_shared" value="1">
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif

                    <div class="overflow-x-auto sm:overflow-x-hidden">
                        <table class="w-full table-fixed divide-y divide-slate-200 text-sm text-slate-700">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 tracking-wide">
                                        Görünüm Adı
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 tracking-wide">
                                        Kapsam (Scope)
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 tracking-wide">
                                        Sahibi
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 tracking-wide">
                                        Paylaşılan?
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-slate-500 tracking-wide w-32">
                                        İşlemler
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-200">
                                @forelse ($views as $view)
                                    @php
                                        $routeMap = [
                                            'quotes' => 'quotes.index',
                                            'sales_orders' => 'sales-orders.index',
                                            'contracts' => 'contracts.index',
                                            'work_orders' => 'work-orders.index',
                                        ];
                                        $targetRoute = isset($routeMap[$view->scope]) ? route($routeMap[$view->scope], $view->query) : '#';
                                    @endphp
                                    <tr class="hover:bg-slate-50/70 transition-colors">
                                        <td class="px-6 py-3 text-sm font-medium text-slate-900 max-w-0 truncate">
                                            {{ $view->name }}
                                        </td>
                                        <td class="px-6 py-3 text-sm text-slate-600 max-w-0 truncate">
                                            {{ ucfirst(str_replace('_', ' ', $view->scope)) }}
                                        </td>
                                        <td class="px-6 py-3 text-sm text-slate-600 max-w-0 truncate">
                                            {{ $view->user_id === auth()->id() ? 'Ben' : $view->user->name ?? 'User #' . $view->user_id }}
                                        </td>
                                        <td class="px-6 py-3 whitespace-nowrap text-sm text-slate-600">
                                            @if($view->is_shared)
                                                <x-ui.badge variant="info">{{ __('Evet') }}</x-ui.badge>
                                            @else
                                                <x-ui.badge variant="neutral">{{ __('Hayır') }}</x-ui.badge>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ $targetRoute }}" class="text-brand-600 hover:text-brand-900 mr-4">Uygula</a>
                                            
                                            @if($view->user_id === auth()->id())
                                                <form action="{{ route('saved-views.destroy', $view) }}" method="POST" class="inline-block" onsubmit="return confirm('Silmek istediğinize emin misiniz?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Sil</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4">
                                            <x-ui.empty-state
                                                title="{{ __('Kayıt bulunamadı') }}"
                                                description="{{ __('Henüz kaydedilmiş bir görünümünüz bulunmuyor.') }}"
                                            >
                                                <x-slot:actions>
                                                    <x-ui.button href="{{ route('saved-views.index') }}" variant="secondary" size="sm">
                                                        <x-icon.refresh-cw class="w-4 h-4 mr-1.5" />
                                                        {{ __('Filtreleri Sıfırla') }}
                                                    </x-ui.button>
                                                </x-slot:actions>
                                            </x-ui.empty-state>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
