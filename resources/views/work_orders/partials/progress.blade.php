@props(['workOrder'])

<x-ui.card class="rounded-2xl border border-slate-200 bg-white shadow-card !p-0">
    <div class="border-b border-slate-100 bg-white px-4 py-3 flex items-center justify-between">
        <h3 class="font-semibold text-slate-900">{{ __('Hakediş Durumu') }}</h3>
        <div class="flex items-center gap-2">
            @php
                $latestProgress = $workOrder->progress->first();
                $percent = $latestProgress ? $latestProgress->progress_percent : 0;
            @endphp
            <span class="text-sm font-bold text-slate-900">%{{ $percent }}</span>
        </div>
    </div>
    
    <div class="p-4 space-y-4">
        {{-- Progress Bar --}}
        <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
            <div class="bg-brand-600 h-2.5 rounded-full transition-all duration-500" style="width: {{ $percent }}%"></div>
        </div>

        {{-- Staff View: Percentage Only --}}
        {{-- Admin View: Financial Calculation --}}
        @if(auth()->user()->is_admin)
             @php
                $totalAmount = 0;
                $currency = 'TRY';
                // Try to find a related Sales Order first, then Quote
                // Note: In a real scenario, we might need a more robust way to link Order->WorkOrder or Quote->WorkOrder
                // Here we rely on 'document-hub' context or simple helpers if defined. 
                // For now, let's look for a manual logic or placeholder. 
                // Since this is "Minimum Diff", we might not have a direct "Order Total" on the WorkOrder model.
                // We will rely on manual relation checks if they exist, or just placeholder if relation not easy.
                
                // However, the prompt asked: "Admin: eğer work order bir quote/sales order ile ilişkiliyse, toplam tutar üzerinden “hak edilen tutar” hesaplayıp göster"
                // Let's try to fetch via DB if possible, or just leave as "Future" if no direct link key exists in WorkOrder table.
                // Looking at WorkOrder model, there is NO `quote_id` or `sales_order_id`.
                // But `SalesOrder` has `work_order_id`. 
                // Let's try to find it.
                $salesOrder = \App\Models\SalesOrder::where('work_order_id', $workOrder->id)->first();
                $quote = null; // Quote usually converts to SO, so SO is better source.
             @endphp

             @if($salesOrder)
                <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 space-y-2">
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-500">{{ __('Toplam Sipariş Tutarı') }}</span>
                        <span class="font-semibold text-slate-900">{{ number_format($salesOrder->total_amount, 2) }} {{ $salesOrder->currency }}</span>
                    </div>
                    <div class="flex justify-between text-sm font-bold text-brand-700">
                        <span>{{ __('Hak Edilen Tutar') }}</span>
                        <span>{{ number_format($salesOrder->total_amount * ($percent / 100), 2) }} {{ $salesOrder->currency }}</span>
                    </div>
                </div>
             @endif
        @endif

        {{-- Update Form --}}
        <form action="{{ route('work-orders.progress.store', $workOrder) }}" method="POST" class="flex gap-2 items-end">
            @csrf
            <div class="flex-1">
                <x-input-label for="progress_percent" :value="__('Yeni Yüzde')" class="sr-only" />
                <div class="relative">
                    <input type="number" name="progress_percent" id="progress_percent" min="0" max="100" value="{{ $percent }}" 
                        class="w-full rounded-xl border-slate-200 text-sm focus:border-brand-500 focus:ring-brand-500">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                        <span class="text-slate-400 sm:text-sm">%</span>
                    </div>
                </div>
            </div>
            <x-ui.button type="submit" size="sm" variant="secondary">
                {{ __('Güncelle') }}
            </x-ui.button>
        </form>
    </div>
</x-ui.card>
