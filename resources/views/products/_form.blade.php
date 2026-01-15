<div class="grid gap-6 md:grid-cols-2">
    <div>
        <x-ui.field label="Tip" name="type" required>
            <x-select name="type" required>
                <option value="product" @selected(old('type', $product->type ?? 'product') == 'product')>Ürün</option>
                <option value="service" @selected(old('type', $product->type) == 'service')>Hizmet</option>
            </x-select>
        </x-ui.field>
    </div>

    <div>
        <x-ui.field label="İsim" name="name" required>
            <x-input id="name" name="name" type="text" class="mt-1" :value="old('name', $product->name ?? '')" required />
        </x-ui.field>
    </div>

    <div>
        <x-ui.field label="Kategori" name="category_id">
            <x-select name="category_id">
                <option value="">Seçiniz...</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                @endforeach
            </x-select>
        </x-ui.field>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <x-ui.field label="SKU" name="sku">
            <x-input id="sku" name="sku" type="text" class="mt-1" :value="old('sku', $product->sku ?? '')" />
        </x-ui.field>
        <x-ui.field label="Barkod" name="barcode">
            <x-input id="barcode" name="barcode" type="text" class="mt-1" :value="old('barcode', $product->barcode ?? '')" />
        </x-ui.field>
    </div>
    
    <div class="md:col-span-2 border-t border-slate-100 pt-4 mt-2">
        <h4 class="text-sm font-semibold text-slate-900 mb-4">Fiyat & Stok</h4>
        <div class="grid gap-6 md:grid-cols-3">
             <x-ui.field label="Para Birimi" name="currency_code" required>
                <x-select name="currency_code" required>
                    <option value="TRY" @selected(old('currency_code', $product->currency_code ?? 'TRY') == 'TRY')>TRY</option>
                    <option value="EUR" @selected(old('currency_code', $product->currency_code) == 'EUR')>EUR</option>
                    <option value="USD" @selected(old('currency_code', $product->currency_code) == 'USD')>USD</option>
                </x-select>
            </x-ui.field>
            
            <x-ui.field label="Alış Fiyatı" name="default_buy_price">
                <x-input id="default_buy_price" name="default_buy_price" type="text" class="mt-1 text-right" :value="old('default_buy_price', $product->default_buy_price ?? '')" placeholder="0.00" />
            </x-ui.field>
             <x-ui.field label="Satış Fiyatı" name="default_sell_price">
                <x-input id="default_sell_price" name="default_sell_price" type="text" class="mt-1 text-right" :value="old('default_sell_price', $product->default_sell_price ?? '')" placeholder="0.00" />
            </x-ui.field>
        </div>
    </div>

    <div class="md:col-span-2 grid gap-6 md:grid-cols-2 items-end">
         <div>
            <div class="flex items-center gap-2 mb-2">
                 <input type="checkbox" id="track_stock" name="track_stock" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" @checked(old('track_stock', $product->track_stock ?? true))>
                 <label for="track_stock" class="text-sm font-medium text-slate-700">Stok Takibi Yapılsın</label>
            </div>
             <x-ui.field label="Kritik Stok Seviyesi" name="critical_stock_level">
                <x-input id="critical_stock_level" name="critical_stock_level" type="number" class="mt-1" :value="old('critical_stock_level', $product->critical_stock_level ?? '')" />
            </x-ui.field>
         </div>

         <div>
             <x-ui.field label="Etiketler" name="tags">
                <select name="tags[]" multiple class="block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm h-24">
                    @foreach($tags as $tag)
                        <option value="{{ $tag->id }}" @selected(in_array($tag->id, old('tags', $product->tags->pluck('id')->toArray() ?? [])))>{{ $tag->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-500 mt-1">Birden fazla seçim için CTRL/CMD tuşuna basılı tutun.</p>
             </x-ui.field>
         </div>
    </div>


    <div class="md:col-span-2 border-t border-slate-100 pt-4 mt-2">
        <x-ui.field label="Notlar" name="notes">
            <x-textarea id="notes" name="notes" rows="4" class="mt-1">{{ old('notes', $product->notes ?? '') }}</x-textarea>
        </x-ui.field>
    </div>
</div>
