<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="{{ __('Yeni Transfer') }}" subtitle="{{ __('Depolar arası stok transferi başlatın.') }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('stock-transfers.index') }}" variant="secondary">
                    {{ __('İptal') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <form action="{{ route('stock-transfers.store') }}" method="POST" x-data="transferForm()">
        @csrf
        
        <div class="grid md:grid-cols-3 gap-6">
            {{-- Header Info --}}
            <div class="md:col-span-1 space-y-6">
                <x-ui.card>
                    <x-slot name="header">{{ __('Transfer Bilgileri') }}</x-slot>
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="from_warehouse_id" :value="__('Çıkış Deposu')" />
                            <select id="from_warehouse_id" name="from_warehouse_id" x-model="from_warehouse_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                                <option value="">{{ __('Seçiniz') }}</option>
                                @foreach($warehouses as $w)
                                    <option value="{{ $w->id }}">{{ $w->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="to_warehouse_id" :value="__('Giriş Deposu')" />
                            <select id="to_warehouse_id" name="to_warehouse_id" x-model="to_warehouse_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                                <option value="">{{ __('Seçiniz') }}</option>
                                @foreach($warehouses as $w)
                                    <option value="{{ $w->id }}">{{ $w->name }}</option>
                                @endforeach
                            </select>
                            <p x-show="from_warehouse_id && from_warehouse_id == to_warehouse_id" class="text-xs text-rose-500 mt-1" x-cloak>
                                {{ __('Çıkış ve giriş deposu aynı olamaz.') }}
                            </p>
                        </div>

                        <div>
                            <x-ui.field label="Notlar" name="note">
                                <x-textarea name="note" rows="3" placeholder="Transfer nedeni vb." />
                            </x-ui.field>
                        </div>
                    </div>
                </x-ui.card>

                <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 text-sm text-blue-700">
                    <h4 class="font-semibold mb-1">Bilgi</h4>
                    <p>Transferi kaydettiğinizde stok hemen düşülmeyecektir. "Kaydet ve İşle" seçeneği ile stoğu anında güncelleyebilirsiniz.</p>
                </div>
            </div>

            {{-- Lines --}}
            <div class="md:col-span-2 space-y-6">
                <x-ui.card>
                    <x-slot name="header">
                        <div class="flex items-center justify-between">
                            <span>{{ __('Transfer Listesi') }}</span>
                            <x-ui.button type="button" @click="addItem()" size="sm" variant="secondary">
                                <x-icon.plus class="w-4 h-4 mr-1" />
                                {{ __('Satır Ekle') }}
                            </x-ui.button>
                        </div>
                    </x-slot>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                                <tr>
                                    <th class="px-4 py-2 w-16">#</th>
                                    <th class="px-4 py-2">{{ __('Ürün') }}</th>
                                    <th class="px-4 py-2 w-32 text-right">{{ __('Miktar') }}</th>
                                    <th class="px-4 py-2 w-16"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(item, index) in items" :key="index">
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-2 text-slate-500" x-text="index + 1"></td>
                                        <td class="px-4 py-2">
                                            <select :name="'items['+index+'][product_id]'" x-model="item.product_id" required class="block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm py-1.5">
                                                <option value="">{{ __('Ürün Seçiniz') }}</option>
                                                @foreach($products as $p)
                                                    <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }})</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-4 py-2">
                                            <input type="number" step="0.01" :name="'items['+index+'][qty]'" x-model="item.qty" required class="ui-input text-right" placeholder="0.00">
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            <button type="button" @click="removeItem(index)" class="text-slate-400 hover:text-rose-600">
                                                <x-icon.trash class="w-4 h-4" />
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        
                        <div x-show="items.length === 0" class="p-8 text-center text-slate-500 italic">
                            {{ __('Henüz ürün eklenmedi.') }}
                        </div>
                    </div>
                </x-ui.card>

                <div class="flex justify-end gap-3">
                    <x-ui.button type="submit" variant="secondary" name="action" value="draft">
                        {{ __('Taslak Olarak Kaydet') }}
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary" name="post_now" value="1">
                        {{ __('Kaydet ve İşle (Stoktan Düş)') }}
                    </x-ui.button>
                </div>
            </div>
        </div>
    </form>

    <script>
        function transferForm() {
            return {
                from_warehouse_id: '',
                to_warehouse_id: '',
                items: [
                    { product_id: '', qty: 1 }
                ],
                addItem() {
                    this.items.push({ product_id: '', qty: 1 });
                },
                removeItem(index) {
                    if (this.items.length > 1) {
                        this.items = this.items.filter((_, i) => i !== index);
                    } else {
                        // Clear first item instead of removing if it's the only one
                        this.items[0].product_id = '';
                        this.items[0].qty = 1;
                    }
                }
            }
        }
    </script>
</x-app-layout>
