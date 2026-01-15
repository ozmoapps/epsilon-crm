<div x-data="{ 
    showModal: false, 
    editing: false, 
    itemId: null,
    form: {
        product_id: '',
        description: '',
        qty: 1,
        unit: 'Adet'
    },
    products: {{ $products->toJson() }},
    
    resetForm() {
        this.editing = false;
        this.itemId = null;
        this.form = { product_id: '', description: '', qty: 1, unit: 'Adet' };
    },
    
    editItem(item) {
        this.editing = true;
        this.itemId = item.id;
        this.form = {
            product_id: item.product_id || '',
            description: item.description,
            qty: item.qty,
            unit: item.unit
        };
        this.showModal = true;
    },
    
    onProductChange() {
        if (this.form.product_id) {
            const product = this.products.find(p => p.id == this.form.product_id);
            if (product) {
                // If description is empty or matches another product name, update it
                // Ideally we might keep description independent, but user convenience:
                if (!this.form.description || this.products.some(p => p.name === this.form.description)) {
                    this.form.description = product.name; 
                }
                // Default unit? Maybe later from product metadata
            }
        }
    }
}" class="space-y-6">

    <x-ui.card>
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
            <h3 class="font-semibold text-slate-900">{{ __('Kullanılan Malzemeler') }}</h3>
            <x-ui.button @click="resetForm(); showModal = true" size="sm" variant="secondary">
                <x-icon.plus class="w-4 h-4 mr-1" />
                {{ __('Malzeme Ekle') }}
            </x-ui.button>
        </div>

        <div class="relative overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">{{ __('Malzeme / Hizmet') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Miktar') }}</th>
                        <th class="px-4 py-3">{{ __('Birim') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('İşlemler') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($workOrder->items as $item)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-4 py-3 font-medium text-slate-900">
                                {{ $item->description }}
                                @if($item->product)
                                    <span class="ml-2 inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-500/10">{{ $item->product->sku }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-mono">{{ $item->qty + 0 }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $item->unit }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end items-center gap-2">
                                    <button @click="editItem({{ $item->toJson() }})" class="text-slate-400 hover:text-brand-600 transition-colors">
                                        <x-icon.pencil class="w-4 h-4" />
                                    </button>
                                    <form action="{{ route('work-orders.items.destroy', [$workOrder, $item]) }}" method="POST" onsubmit="return confirm('Silmek istediğinize emin misiniz?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-slate-400 hover:text-rose-600 transition-colors">
                                            <x-icon.trash class="w-4 h-4" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-slate-500 italic">
                                {{ __('Henüz malzeme eklenmemiş.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    {{-- Attributes Modal --}}
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4" x-transition>
        <div @click.away="showModal = false" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl space-y-4">
            <h3 class="text-lg font-bold text-slate-900" x-text="editing ? 'Malzeme Düzenle' : 'Malzeme Ekle'"></h3>
            
            <form method="POST" :action="editing ? '{{ url('work-orders/'.$workOrder->id.'/items') }}/' + itemId : '{{ route('work-orders.items.store', $workOrder) }}'">
                @csrf
                <input type="hidden" name="_method" :value="editing ? 'PUT' : 'POST'">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Ürün Seçimi (Opsiyonel)</label>
                        <select name="product_id" x-model="form.product_id" @change="onProductChange()" class="w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                            <option value="">-- Serbest Giriş --</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Açıklama / Ürün Adı <span class="text-rose-500">*</span></label>
                        <input type="text" name="description" x-model="form.description" required class="ui-input">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Miktar <span class="text-rose-500">*</span></label>
                            <input type="number" step="0.01" name="qty" x-model="form.qty" required class="ui-input">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Birim</label>
                            <input type="text" name="unit" x-model="form.unit" list="units" class="ui-input">
                            <datalist id="units">
                                <option value="Adet">
                                <option value="Metre">
                                <option value="Kg">
                                <option value="Lt">
                                <option value="Kutu">
                                <option value="Set">
                                <option value="Saat">
                            </datalist>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="showModal = false" class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 rounded-lg">İptal</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-lg shadow-sm shadow-brand-600/20">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>
