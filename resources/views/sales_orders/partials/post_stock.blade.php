<x-ui.card class="mt-6">
    <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
        <h3 class="font-semibold text-slate-900">{{ __('Stok İşlemleri') }}</h3>
        
        @if($salesOrder->stock_posted_at)
            <x-ui.badge variant="success" class="flex items-center gap-1.5 !px-3 !py-1">
                <x-icon.check class="w-3.5 h-3.5" />
                <span>{{ __('Stoktan düşüldü') }}</span>
                <span class="opacity-75 text-[10px] ml-1">({{ $salesOrder->stock_posted_at->format('d.m.Y H:i') }})</span>
            </x-ui.badge>
        @endif
    </div>
    
    <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
        <div class="text-sm text-slate-600">
            <p>{{ __('Bu siparişteki ürünleri stoktan düşmek için depoyu seçip işlemi onaylayın.') }}</p>
            @if($salesOrder->stock_posted_at)
                <p class="mt-1 font-medium text-slate-700">
                    {{ __('İşlem yapılan depo:') }} 
                    <strong>{{ $salesOrder->stockPostedWarehouse?->name ?? 'Silinmiş Depo' }}</strong>
                </p>
            @endif
        </div>

        @if(!$salesOrder->stock_posted_at)
             @if($salesOrder->shipments()->exists())
                <div class="flex items-center gap-3 w-full md:w-auto p-3 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-sm">
                    <x-icon.exclamation-triangle class="w-5 h-5 flex-shrink-0" />
                    <span>{{ __('Bu siparişte sevkiyat oluşturulduğu için stok düşüşü sevkiyat üzerinden yapılır.') }}</span>
                </div>
             @else
                 <form action="{{ route('sales-orders.post-stock', $salesOrder) }}" method="POST" class="flex items-end gap-3 w-full md:w-auto" onsubmit="return confirm('Seçili ürünler stoktan düşülecektir. Onaylıyor musunuz?')">
                    @csrf
                    <div class="w-full md:w-48">
                        <label class="block text-xs font-semibold text-slate-500 mb-1">{{ __('Depo Seçimi') }}</label>
                        <select name="warehouse_id" class="w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm py-1.5">
                            @foreach(\App\Models\Warehouse::where('is_active', true)->orderBy('is_default', 'desc')->orderBy('name')->get() as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }} {{ $wh->is_default ? '(Varsayılan)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-ui.button type="submit" size="sm">
                        {{ __('Stoktan Düş') }}
                    </x-ui.button>
                </form>
            @endif
        @else
            <x-ui.button disabled variant="secondary" size="sm" class="opacity-50 cursor-not-allowed">
                {{ __('İşlem Tamamlandı') }}
            </x-ui.button>
        @endif
    </div>
</x-ui.card>
