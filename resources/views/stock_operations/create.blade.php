<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="{{ __('Manuel Stok İşlemi') }}" subtitle="{{ __('Stok girişi, çıkışı veya sayım düzeltmesi.') }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('stock-movements.index') }}" variant="secondary">
                    {{ __('İptal') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <x-ui.card>
            <form action="{{ route('stock-operations.store') }}" method="POST" class="space-y-6" x-data="{ type: 'manual_in' }">
                @csrf

                <div>
                    <x-input-label for="operation_type" :value="__('İşlem Türü')" />
                    <div class="mt-2 grid grid-cols-3 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="operation_type" value="manual_in" class="peer sr-only" x-model="type">
                            <div class="text-center rounded-md px-3 py-2 text-sm font-medium border border-slate-200 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700 hover:bg-slate-50 transition-all">
                                {{ __('Stok Girişi') }}
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="operation_type" value="manual_out" class="peer sr-only" x-model="type">
                            <div class="text-center rounded-md px-3 py-2 text-sm font-medium border border-slate-200 peer-checked:border-rose-500 peer-checked:bg-rose-50 peer-checked:text-rose-700 hover:bg-slate-50 transition-all">
                                {{ __('Stok Çıkışı') }}
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="operation_type" value="adjust" class="peer sr-only" x-model="type">
                            <div class="text-center rounded-md px-3 py-2 text-sm font-medium border border-slate-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 hover:bg-slate-50 transition-all">
                                {{ __('Sayım Düzeltme') }}
                            </div>
                        </label>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="warehouse_id" :value="__('Depo')" />
                        <select id="warehouse_id" name="warehouse_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                            <option value="">{{ __('Seçiniz') }}</option>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->name }}</option>
                            @endforeach
                        </select>
                         <x-input-error :messages="$errors->get('warehouse_id')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="product_id" :value="__('Ürün')" />
                        <select id="product_id" name="product_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                            <option value="">{{ __('Seçiniz') }}</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }})</option>
                            @endforeach
                        </select>
                         <x-input-error :messages="$errors->get('product_id')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <template x-if="type !== 'adjust'">
                        <div>
                            <x-input-label for="qty" :value="__('Miktar')" />
                            <x-input id="qty" name="qty" type="number" step="0.01" class="mt-1 block w-full" placeholder="0.00" />
                            <x-input-error :messages="$errors->get('qty')" class="mt-2" />
                        </div>
                    </template>
                    <template x-if="type === 'adjust'">
                         <div>
                            <x-input-label for="counted_qty" :value="__('Sayım Miktarı (Gerçek Stok)')" />
                            <x-input id="counted_qty" name="counted_qty" type="number" step="0.01" class="mt-1 block w-full" placeholder="Depodaki gerçek adet" />
                            <p class="text-xs text-slate-500 mt-1">{{ __('Sistemdeki miktar ile sayım miktarı arasındaki fark otomatik hesaplanıp stok hareketi oluşturulacaktır.') }}</p>
                            <x-input-error :messages="$errors->get('counted_qty')" class="mt-2" />
                        </div>
                    </template>
                </div>

                <div>
                    <x-ui.field label="Notlar" name="note">
                        <x-textarea name="note" rows="2" placeholder="İşlem nedeni..." />
                    </x-ui.field>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end">
                    <x-ui.button type="submit" variant="primary" class="w-full md:w-auto justify-center">
                        {{ __('Kaydet') }}
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-app-layout>
