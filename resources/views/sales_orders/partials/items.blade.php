<div x-data="{ 
    showModal: false, 
    editing: false, 
    itemId: null,
    form: {
        product_id: '',
        section: 'Genel',
        item_type: 'product',
        description: '',
        qty: 1,
        unit: 'Adet',
        unit_price: 0,
        vat_rate: 20,
        discount_amount: 0,
        is_optional: false
    },
    products: {{ \App\Models\Product::orderBy('name')->get(['id', 'name', 'sku', 'unit'])->toJson() }},
    
    resetForm() {
        this.editing = false;
        this.itemId = null;
        this.form = { 
            product_id: '', 
            section: 'Genel', 
            item_type: 'product', 
            description: '', 
            qty: 1, 
            unit: 'Adet', 
            unit_price: 0,
            vat_rate: 20,
            discount_amount: 0,
            is_optional: false
        };
    },
    
    editItem(item) {
        this.editing = true;
        this.itemId = item.id;
        this.form = {
            product_id: item.product_id || '',
            section: item.section || 'Genel',
            item_type: item.item_type,
            description: item.description,
            qty: item.qty,
            unit: item.unit,
            unit_price: item.unit_price,
            vat_rate: item.vat_rate,
            discount_amount: item.discount_amount,
            is_optional: Boolean(item.is_optional)
        };
        this.showModal = true;
    },
    
    onProductChange() {
        if (this.form.product_id) {
            const product = this.products.find(p => p.id == this.form.product_id);
            if (product) {
                if (!this.form.description || this.products.some(p => p.name === this.form.description)) {
                    this.form.description = product.name; 
                }
                if (!this.form.unit) {
                    this.form.unit = product.unit || 'Adet';
                }
            }
        }
    }
}" class="space-y-6">

    <x-ui.card>
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
            <h3 class="font-semibold text-slate-900">{{ __('Kalemler') }}</h3>
            @if(!$isLocked)
                <x-ui.button @click="resetForm(); showModal = true" size="sm" variant="secondary">
                    <x-icon.plus class="w-4 h-4 mr-1" />
                    {{ __('Kalem Ekle') }}
                </x-ui.button>
            @endif
        </div>

        <div class="relative overflow-x-auto">
            <x-ui.table>
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">{{ __('Tip') }}</th>
                        <th class="px-4 py-3">{{ __('Açıklama / Ürün') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Miktar') }}</th>
                        <th class="px-4 py-3 text-right bg-blue-50/50 text-blue-600">{{ __('Sevk') }}</th>
                        <th class="px-4 py-3 text-right bg-rose-50/50 text-rose-600">{{ __('İade') }}</th>
                        <th class="px-4 py-3 text-right bg-slate-100 text-slate-600">{{ __('Kalan') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Birim Fiyat') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Toplam') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('İşlemler') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($salesOrder->items as $item)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-4 py-3 text-xs">
                                <x-ui.badge :variant="$item->item_type === 'product' ? 'info' : 'neutral'">
                                    {{ $item->item_type === 'product' ? 'Ürün' : 'Hizmet' }}
                                </x-ui.badge>
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-900">
                                {{ $item->description }}
                                @if($item->product)
                                    <x-ui.badge variant="success" class="ml-2">
                                        {{ $item->product->sku }}
                                    </x-ui.badge>
                                @elseif($item->item_type === 'product')
                                    <x-ui.badge variant="neutral" class="ml-2" title="Stok takibi için ürün seçin">
                                        (Ürün seçilmedi)
                                    </x-ui.badge>
                                @endif
                                @if($item->is_optional)
                                    <x-ui.badge variant="neutral" class="ml-1">Opsiyon</x-ui.badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-slate-700">
                                {{ $item->qty + 0 }} {{ $item->unit }}
                            </td>
                             <td class="px-4 py-3 text-right font-mono bg-blue-50/30 text-blue-700">
                                @if($item->shipped_qty > 0)
                                    {{ $item->shipped_qty + 0 }}
                                @else
                                    <span class="text-slate-300">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-mono bg-rose-50/30 text-rose-700">
                                @if($item->returned_qty > 0)
                                    {{ $item->returned_qty + 0 }}
                                @else
                                    <span class="text-slate-300">-</span>
                                @endif
                            </td>
                             <td class="px-4 py-3 text-right font-mono bg-slate-50 text-slate-700 font-bold">
                                {{ $item->remaining_qty + 0 }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono">
                                {{ number_format($item->unit_price, 2) }} {{ $salesOrder->currency }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-medium text-slate-900">
                                {{ number_format($item->total_price ?? ($item->qty * $item->unit_price), 2) }} {{ $salesOrder->currency }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if(!$isLocked)
                                    <div class="flex justify-end items-center gap-2">
                                        <button @click="editItem({{ $item->toJson() }})" class="text-slate-400 hover:text-brand-600 transition-colors">
                                            <x-icon.pencil class="w-4 h-4" />
                                        </button>
                                        <form action="{{ route('sales-orders.items.destroy', [$salesOrder, $item]) }}" method="POST" onsubmit="return confirm('Silmek istediğinize emin misiniz?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-slate-400 hover:text-rose-600 transition-colors">
                                                <x-icon.trash class="w-4 h-4" />
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-6 text-center text-slate-500 italic">
                                {{ __('Henüz kalem eklenmemiş.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                </tbody>
            </x-ui.table>
        </div>
        
        {{-- Totals Summary --}}
        <div class="mt-6 flex justify-end">
            <div class="w-full md:w-1/3 space-y-2 text-sm">
                <div class="flex justify-between text-slate-600">
                    <span>Ara Toplam:</span>
                    <span>{{ number_format($salesOrder->subtotal, 2) }} {{ $salesOrder->currency }}</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>İndirim:</span>
                    <span>-{{ number_format($salesOrder->discount_total, 2) }} {{ $salesOrder->currency }}</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>KDV:</span>
                    <span>{{ number_format($salesOrder->vat_total, 2) }} {{ $salesOrder->currency }}</span>
                </div>
                <div class="flex justify-between font-bold text-slate-900 text-base pt-2 border-t border-slate-200">
                    <span>Genel Toplam:</span>
                    <span>{{ number_format($salesOrder->grand_total, 2) }} {{ $salesOrder->currency }}</span>
                </div>
            </div>
        </div>
    </x-ui.card>

    {{-- Item Modal --}}
    @if(!$isLocked)
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4" x-transition>
        <div @click.away="showModal = false" class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl space-y-4 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-bold text-slate-900" x-text="editing ? 'Kalem Düzenle' : 'Kalem Ekle'"></h3>
            
            <form method="POST" :action="editing ? '{{ url('sales-orders/'.$salesOrder->id.'/items') }}/' + itemId : '{{ route('sales-orders.items.store', $salesOrder) }}'">
                @csrf
                <input type="hidden" name="_method" :value="editing ? 'PUT' : 'POST'">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Row 1 --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tip</label>
                        <select name="item_type" x-model="form.item_type" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm ui-focus">
                            <option value="product">Ürün</option>
                            <option value="service">Hizmet</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Bölüm (Opsiyonel)</label>
                        <input type="text" name="section" x-model="form.section" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm ui-focus" placeholder="Örn: Salon, Motor">
                    </div>

                    {{-- Row 2 --}}
                    <div class="md:col-span-2">
                         <label class="block text-sm font-medium text-slate-700 mb-1">Stok Ürünü (Opsiyonel)</label>
                         <select name="product_id" x-model="form.product_id" @change="onProductChange()" class="w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                            <option value="">-- Serbest Giriş --</option>
                            @foreach(\App\Models\Product::orderBy('name')->get(['id', 'name', 'sku']) as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }})</option>
                            @endforeach
                        </select>
                         <p class="text-xs text-slate-500 mt-1">Stoktan düşülebilmesi için buradan bir ürün seçilmelidir.</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Açıklama <span class="text-rose-500">*</span></label>
                        <textarea name="description" x-model="form.description" required rows="2" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm ui-focus"></textarea>
                    </div>

                    {{-- Row 3 --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Miktar <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" name="qty" x-model="form.qty" required class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm ui-focus">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Birim</label>
                        <input type="text" name="unit" x-model="form.unit" list="units" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm ui-focus">
                    </div>

                     <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Birim Fiyat <span class="text-rose-500">*</span></label>
                        <div class="relative rounded-xl shadow-sm">
                            <input type="number" step="0.01" name="unit_price" x-model="form.unit_price" required class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm pr-12 ui-focus">
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                <span class="text-gray-500 sm:text-sm">{{ $salesOrder->currency }}</span>
                            </div>
                        </div>
                    </div>
                     <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">KDV Oranı (%)</label>
                        <input type="number" step="0.01" name="vat_rate" x-model="form.vat_rate" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm ui-focus" placeholder="20">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">İndirim Tutarı</label>
                         <div class="relative rounded-xl shadow-sm">
                            <input type="number" step="0.01" name="discount_amount" x-model="form.discount_amount" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm pr-12 ui-focus">
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                <span class="text-gray-500 sm:text-sm">{{ $salesOrder->currency }}</span>
                            </div>
                        </div>
                    </div>
                     <div class="flex items-center pt-6">
                        <input id="is_optional" name="is_optional" type="checkbox" value="1" x-model="form.is_optional" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-600">
                        <label for="is_optional" class="ml-2 block text-sm text-slate-900">Opsiyonel Kalem</label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="showModal = false" class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 rounded-xl transition-colors">İptal</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-xl shadow-soft shadow-brand-600/20 transition-colors">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
