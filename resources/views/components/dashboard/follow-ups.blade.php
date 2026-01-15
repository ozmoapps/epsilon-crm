@props(['upcomingFollowUps'])

<x-ui.card>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <span>{{ __('Takipte (Yaklaşan)') }}</span>
        </div>
    </x-slot>

    @if($upcomingFollowUps->isNotEmpty())
        <div class="space-y-3">
            @foreach($upcomingFollowUps as $followUp)
                @if(! $followUp->subject)
                    @continue
                @endif
                <div class="flex items-start gap-3 rounded-xl border border-slate-100 p-3 hover:bg-slate-50">
                    <div class="min-w-0 flex-1">
                        <div class="mb-1 flex items-center gap-2 text-xs text-slate-500">
                             <span class="font-medium text-slate-900">{{ $followUp->next_at->format('d M H:i') }}</span>
                             <span>&bull;</span>
                             <span>{{ class_basename($followUp->subject_type) }}</span>
                        </div>
                        <a href="{{ route(str_replace('_', '-', $followUp->subject->getTable()) . '.show', $followUp->subject_id) }}" class="block truncate text-sm font-medium text-slate-900 hover:text-brand-600">
                             @if($followUp->subject_type === \App\Models\Quote::class) {{ $followUp->subject->quote_no }}
                             @elseif($followUp->subject_type === \App\Models\SalesOrder::class) {{ $followUp->subject->order_no }}
                             @elseif($followUp->subject_type === \App\Models\Contract::class) {{ $followUp->subject->contract_no }}
                             @elseif($followUp->subject_type === \App\Models\WorkOrder::class) {{ $followUp->subject->title }}
                             @endif
                        </a>
                        @if($followUp->note)
                            <p class="mt-1 line-clamp-1 text-xs text-slate-500">{{ $followUp->note }}</p>
                        @endif
                    </div>
                    <form action="{{ route('follow-ups.complete', $followUp) }}" method="POST">
                        @csrf
                        <button type="submit" class="text-slate-400 hover:text-emerald-600" title="{{ __('Tamamla') }}">
                            <x-icon.check class="h-5 w-5" />
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @else
        <p class="py-4 text-center text-sm text-slate-500">{{ __('Yaklaşan takip bulunmuyor.') }}</p>
    @endif
</x-ui.card>
