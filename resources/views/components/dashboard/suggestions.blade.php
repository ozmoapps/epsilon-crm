@props([
    'staleSentQuotes',
    'staleSentContracts',
    'upcomingPlannedWorkOrders',
])

@if($staleSentQuotes->isNotEmpty() || $staleSentContracts->isNotEmpty() || $upcomingPlannedWorkOrders->isNotEmpty())
    <x-ui.card>
        <x-slot name="header">
            <span>{{ __('Otomatik Takip Önerileri') }}</span>
        </x-slot>

        <div class="space-y-4">
            @if($staleSentQuotes->isNotEmpty())
                <div>
                    <h4 class="mb-2 text-xs font-semibold text-slate-500">{{ __('Yanıt Bekleyen Teklifler') }}</h4>
                    <ul class="space-y-2">
                        @foreach($staleSentQuotes as $quote)
                            <li class="flex items-center justify-between text-sm">
                                <a href="{{ route('quotes.show', $quote) }}" class="truncate text-slate-700 hover:text-brand-600">{{ $quote->quote_no }}</a>
                                <form action="{{ route('follow-ups.store') }}" method="POST" class="flex-shrink-0">
                                    @csrf
                                    <input type="hidden" name="subject_type" value="{{ \App\Models\Quote::class }}">
                                    <input type="hidden" name="subject_id" value="{{ $quote->id }}">
                                    <input type="hidden" name="next_at" value="{{ now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s') }}">
                                    <input type="hidden" name="note" value="Otomatik Öneri Takibi">
                                    <button type="submit" class="text-xs text-brand-600 hover:underline">{{ __('Takip Oluştur') }}</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
    
            @if($staleSentContracts->isNotEmpty())
                 <div class="border-t border-slate-100 pt-2">
                    <h4 class="mb-2 mt-2 text-xs font-semibold text-slate-500">{{ __('İmza Bekleyen Sözleşmeler') }}</h4>
                    <ul class="space-y-2">
                        @foreach($staleSentContracts as $contract)
                            <li class="flex items-center justify-between text-sm">
                                 <a href="{{ route('contracts.show', $contract) }}" class="truncate text-slate-700 hover:text-brand-600">{{ $contract->contract_no }}</a>
                                 <form action="{{ route('follow-ups.store') }}" method="POST" class="flex-shrink-0">
                                    @csrf
                                    <input type="hidden" name="subject_type" value="{{ \App\Models\Contract::class }}">
                                    <input type="hidden" name="subject_id" value="{{ $contract->id }}">
                                    <input type="hidden" name="next_at" value="{{ now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s') }}">
                                    <input type="hidden" name="note" value="Otomatik Öneri Takibi">
                                    <button type="submit" class="text-xs text-brand-600 hover:underline">{{ __('Takip Oluştur') }}</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
    
            @if($upcomingPlannedWorkOrders->isNotEmpty())
                 <div class="border-t border-slate-100 pt-2">
                    <h4 class="mb-2 mt-2 text-xs font-semibold text-slate-500">{{ __('Yaklaşan İş Emirleri') }}</h4>
                    <ul class="space-y-2">
                        @foreach($upcomingPlannedWorkOrders as $wo)
                            <li class="flex items-center justify-between text-sm">
                                 <a href="{{ route('work-orders.show', $wo) }}" class="truncate text-slate-700 hover:text-brand-600">{{ $wo->title }}</a>
                                 <form action="{{ route('follow-ups.store') }}" method="POST" class="flex-shrink-0">
                                    @csrf
                                    <input type="hidden" name="subject_type" value="{{ \App\Models\WorkOrder::class }}">
                                    <input type="hidden" name="subject_id" value="{{ $wo->id }}">
                                    <input type="hidden" name="next_at" value="{{ now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s') }}">
                                    <input type="hidden" name="note" value="Otomatik Öneri Takibi">
                                    <button type="submit" class="text-xs text-brand-600 hover:underline">{{ __('Takip Oluştur') }}</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </x-ui.card>
@endif
