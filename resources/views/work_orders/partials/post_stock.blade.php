<x-ui.card class="mt-6">
    <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
        <h3 class="font-semibold text-slate-900">{{ __('Stok İşlemleri') }}</h3>
        
        @if($workOrder->stock_posted_at)
            <x-ui.badge variant="success" class="!px-3 !py-1.5 text-sm font-medium gap-2">
                <x-icon.check class="w-4 h-4" />
                <span>{{ __('Stoktan düşüldü') }}</span>
                <span class="text-xs text-emerald-500">({{ $workOrder->stock_posted_at->format('d.m.Y H:i') }})</span>
            </x-ui.badge>
        @endif
    </div>
    
    <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
        <div class="text-sm text-slate-600">
            <p>{{ __('Bu iş emrinde kullanılan malzemeleri stoktan düşmek için depoyu seçip işlemi onaylayın.') }}</p>
            @if($workOrder->stock_posted_at)
                <p class="mt-1 font-medium text-slate-700">
                    {{ __('İşlem yapılan depo:') }} 
                    <strong>{{ $workOrder->stockPostedWarehouse?->name ?? 'Silinmiş Depo' }}</strong>
                </p>
            @endif
        </div>

        @if(!$workOrder->stock_posted_at)
             <form action="{{ route('work-orders.post-stock', $workOrder) }}" method="POST" class="flex items-end gap-3 w-full md:w-auto" onsubmit="return confirm('Seçili malzemeler stoktan düşülecektir. Onaylıyor musunuz?')">
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
        @else
            <x-ui.button disabled variant="secondary" size="sm" class="opacity-50 cursor-not-allowed">
                {{ __('İşlem Tamamlandı') }}
            </x-ui.button>
        @endif
    </div>
</x-ui.card>
