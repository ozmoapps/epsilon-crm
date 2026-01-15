<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="{{ __('Ürünler & Hizmetler') }}" subtitle="{{ __('Stok kartlarını ve hizmetleri yönetin.') }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('products.create') }}" size="sm">
                    {{ __('Yeni Ekle') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <x-ui.card>
            <x-slot name="header">{{ __('Filtreler') }}</x-slot>
            <form method="GET" action="{{ route('products.index') }}" class="grid gap-4 md:grid-cols-4 items-end">
                <div>
                     <x-input-label for="search" :value="__('Arama')" />
                     <x-input id="search" name="search" type="text" class="mt-1 w-full" placeholder="{{ __('İsim, SKU, Barkod') }}" :value="$search" />
                </div>
                <div>
                    <x-input-label for="category_id" :value="__('Kategori')" />
                    <x-select id="category_id" name="category_id" class="mt-1 w-full">
                        <option value="">{{ __('Tümü') }}</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected($categoryId == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div>
                    <x-input-label for="type" :value="__('Tip')" />
                     <x-select id="type" name="type" class="mt-1 w-full">
                        <option value="">{{ __('Tümü') }}</option>
                        <option value="product" @selected($type == 'product')>{{ __('Ürün') }}</option>
                        <option value="service" @selected($type == 'service')>{{ __('Hizmet') }}</option>
                    </x-select>
                </div>
                <div class="flex gap-2">
                    <x-ui.button type="submit" size="sm" class="w-full">{{ __('Filtrele') }}</x-ui.button>
                    <x-ui.button href="{{ route('products.index') }}" variant="secondary" size="sm">{{ __('Temizle') }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card>
            <x-ui.table>
                <thead class="bg-slate-50 text-xs font-semibold tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left w-20">{{ __('Tip') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('İsim') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Kategori') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Fiyat') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('Stok') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('İşlemler') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($products as $product)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-3 text-xs">
                                @if($product->type == 'service')
                                    <x-ui.badge variant="info" class="!px-2 !py-1 text-xs">{{ __('Hizmet') }}</x-ui.badge>
                                @else
                                    <x-ui.badge variant="success" class="!px-2 !py-1 text-xs">{{ __('Ürün') }}</x-ui.badge>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-semibold text-slate-900 block">{{ $product->name }}</span>
                                <span class="text-xs text-slate-500 font-mono">{{ $product->sku }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ $product->category?->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-medium text-slate-900">
                                {{ number_format($product->default_sell_price, 2) }} <span class="text-xs text-slate-500">{{ $product->currency_code }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                @if(!$product->track_stock)
                                    <span class="text-slate-400">-</span>
                                @else
                                    <span class="font-bold">0</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form id="delete-form-{{ $product->id }}" action="{{ route('products.destroy', $product) }}" method="POST" class="hidden">
                                    @csrf @method('DELETE')
                                </form>
                                <x-ui.row-actions 
                                    show="{{ route('products.show', $product) }}"
                                    edit="{{ route('products.edit', $product) }}"
                                    delete="#"
                                    delete-form-id="delete-form-{{ $product->id }}"
                                />
                            </td>
                        </tr>
                    @empty
                         <tr>
                            <td colspan="6" class="px-4 py-8">
                                <x-ui.empty-state icon="archive-box" title="Ürün bulunamadı" description="Kriterlere uygun kayıt yok veya henüz eklenmedi." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>
        
        <div>{{ $products->links() }}</div>
    </div>
</x-app-layout>
