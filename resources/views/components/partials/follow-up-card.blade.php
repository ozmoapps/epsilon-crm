@props([
    'context',
])

@php
    $followUps = $context->openFollowUps;
    $classBasedType = get_class($context);
@endphp

<x-card class="overflow-hidden border border-slate-200 rounded-2xl bg-white shadow-sm !p-0">
    <div class="border-b border-slate-100 bg-white px-4 py-3">
        <h3 class="font-semibold text-slate-900">{{ __('Takip') }}</h3>
    </div>

    <div class="space-y-5 p-4">
        <form action="{{ route('follow-ups.store') }}" method="POST" class="space-y-3">
            @csrf
            <input type="hidden" name="subject_type" value="{{ $classBasedType }}">
            <input type="hidden" name="subject_id" value="{{ $context->id }}">
            
            <div class="space-y-3">
                 <div>
                     <input type="datetime-local" name="next_at" required class="block w-full rounded-lg border-slate-200 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" value="{{ now()->addDay()->setTime(10,0)->format('Y-m-d\TH:i') }}">
                </div>
                <div>
                    <textarea name="note" rows="2" placeholder="{{ __('Takip notu...') }}" class="block w-full rounded-lg border-slate-200 py-2 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 min-h-24 resize-none"></textarea>
                </div>
            </div>
            
            <div>
                <x-button type="submit" size="sm" variant="primary" class="w-full justify-center">
                    {{ __('Planla') }}
                </x-button>
            </div>
        </form>

        @if($followUps->isNotEmpty())
            <div class="border-t border-slate-100 pt-4">
                <ul class="space-y-1">
                    @foreach($followUps as $followUp)
                        <li class="flex items-start justify-between group p-3 rounded-xl hover:bg-slate-50 transition-colors border border-transparent hover:border-slate-200">
                            <div class="min-w-0 flex-1 pr-3">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-semibold text-slate-900">{{ $followUp->next_at->format('d.m.Y H:i') }}</span>
                                    @if($followUp->creator)
                                         <span class="text-[10px] text-slate-500 font-medium px-1.5 py-0.5 rounded bg-slate-100">{{ $followUp->creator->name }}</span>
                                    @endif
                                </div>
                                <p class="text-sm text-slate-600 line-clamp-2 leading-relaxed" title="{{ $followUp->note }}">{{ $followUp->note }}</p>
                            </div>
                            <div class="flex flex-col gap-1 items-end shrink-0">
                                 <form action="{{ route('follow-ups.complete', $followUp) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-[10px] font-medium text-slate-500 hover:text-emerald-600 px-2 py-1 rounded bg-slate-100 hover:bg-emerald-50 transition-colors border border-transparent hover:border-emerald-100 whitespace-nowrap" title="{{ __('Tamamla') }}">
                                        {{ __('Tamamlandı') }}
                                    </button>
                                 </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-card>
