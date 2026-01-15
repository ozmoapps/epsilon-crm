<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="{{ __('Kategoriler') }}" subtitle="{{ __('Ürün ve hizmet kategorileri.') }}">
        </x-ui.page-header>
    </x-slot>

    <div class="grid md:grid-cols-3 gap-6">
        <div class="md:col-span-2">
            <x-ui.card>
                 <x-slot name="header">{{ __('Kategori Listesi') }}</x-slot>
                 <x-ui.table>
                    <thead class="bg-slate-50 text-xs font-semibold tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left">{{ __('İsim') }}</th>
                            <th class="px-4 py-3 text-center">{{ __('Ürün Sayısı') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('İşlemler') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($categories as $category)
                            <tr class="hover:bg-slate-50/70" x-data="{ editing: false, name: '{{ $category->name }}' }">
                                <td class="px-4 py-3">
                                    <span x-show="!editing" class="font-medium text-slate-900">{{ $category->name }}</span>
                                    <form x-show="editing" x-cloak action="{{ route('categories.update', $category) }}" method="POST" class="flex items-center gap-2">
                                        @csrf @method('PUT')
                                        <x-input name="name" x-model="name" class="py-1 text-sm h-8" required />
                                        <button type="submit" class="text-brand-600 hover:text-brand-800"><x-icon.check class="w-4 h-4" /></button>
                                        <button type="button" @click="editing = false; name='{{ $category->name }}'" class="text-slate-400 hover:text-slate-600"><x-icon.x class="w-4 h-4" /></button>
                                    </form>
                                </td>
                                <td class="px-4 py-3 text-center text-sm text-slate-500">
                                    {{ $category->products_count }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div x-show="!editing" class="flex justify-end items-center gap-2">
                                        <button @click="editing = true" class="text-slate-400 hover:text-brand-600 transition-colors">
                                            <x-icon.pencil class="w-4 h-4" />
                                        </button>
                                        
                                        @if($category->products_count == 0)
                                            <form action="{{ route('categories.destroy', $category) }}" method="POST" 
                                                  onsubmit="return confirm('Silmek istediğinize emin misiniz?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-slate-400 hover:text-rose-600 transition-colors">
                                                    <x-icon.trash class="w-4 h-4" />
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-slate-500 italic">{{ __('Kategori bulunamadı.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                 </x-ui.table>
            </x-ui.card>
        </div>

        <div>
            <x-ui.card>
                <x-slot name="header">{{ __('Yeni Kategori Ekle') }}</x-slot>
                <form action="{{ route('categories.store') }}" method="POST" class="space-y-4 p-4">
                    @csrf
                    <div>
                        <x-ui.field label="Kategori Adı" name="name" required>
                             <x-input name="name" required placeholder="Örn: Elektronik" />
                        </x-ui.field>
                    </div>
                    <x-ui.button type="submit" class="w-full justify-center">{{ __('Ekle') }}</x-ui.button>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
