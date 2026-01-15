<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="{{ __('Ürün Düzenle') }}" subtitle="{{ $product->name }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('products.index') }}" variant="secondary" size="sm">
                    {{ __('Listeye Dön') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.card class="max-w-4xl">
        <x-slot name="header">{{ __('Ürün Bilgileri') }}</x-slot>
        <form method="POST" action="{{ route('products.update', $product) }}" class="space-y-6">
            @csrf
            @method('PUT')
            
            @include('products._form')
            
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
                <x-ui.button type="button" href="{{ route('products.index') }}" variant="secondary" size="sm">{{ __('İptal') }}</x-ui.button>
                <x-ui.button type="submit" size="sm">{{ __('Güncelle') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-app-layout>
