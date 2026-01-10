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
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    @if(session('success'))
                        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
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
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    Evet
                                                </span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                    Hayır
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ $targetRoute }}" class="text-indigo-600 hover:text-indigo-900 mr-4">Uygula</a>
                                            
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
                                        <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">
                                            Henüz kayıtlı bir görünüm yok.
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
