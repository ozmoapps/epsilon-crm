@props(['flow'])

<x-ui.card class="!p-0 overflow-hidden border border-slate-200 rounded-2xl bg-white shadow-card">
    <div class="border-b border-slate-100 bg-white px-4 py-3">
        <h3 class="font-semibold text-slate-900">{{ __('Operasyon Akışı') }}</h3>
    </div>

    <div class="divide-y divide-slate-100 px-4">
        @foreach($flow as $step)
            <div class="flex items-center gap-3 py-3">
                {{-- Icon State --}}
                <div @class([
                    'flex h-6 w-6 shrink-0 items-center justify-center rounded-full border',
                    'bg-emerald-50 border-emerald-200 text-emerald-600' => $step['completed'],
                    'bg-slate-50 border-slate-200 text-slate-300' => !$step['completed'],
                ])>
                    @if($step['completed'])
                        <x-icon.check class="h-3 w-3" />
                    @else
                        <div class="h-1.5 w-1.5 rounded-full bg-slate-300"></div>
                    @endif
                </div>

                {{-- Content --}}
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between">
                        <span @class([
                            'text-xs font-medium',
                            'text-slate-900' => $step['completed'],
                            'text-slate-500' => !$step['completed'],
                        ])>
                            {{ $step['label'] }}
                        </span>
                        
                        {{-- Status Badge (if active/visible) --}}
                        @if(!empty($step['status_label']))
                            <x-ui.badge :variant="$step['status_variant'] ?? 'neutral'" class="!px-1.5 !py-0 text-[10px]">
                                {{ $step['status_label'] }}
                            </x-ui.badge>
                        @endif
                    </div>
                </div>

                {{-- Action / Lock --}}
                @if($step['locked'] ?? false)
                    <div class="flex items-center justify-center text-slate-300" title="{{ __('Yetki Gerekli') }}">
                        <x-icon.lock class="h-4 w-4" />
                    </div>
                @elseif(!empty($step['href']))
                    <a href="{{ $step['href'] }}" class="text-slate-400 hover:text-brand-600 transition-colors">
                        <x-icon.arrow-right class="h-4 w-4" />
                    </a>
                @endif
            </div>
        @endforeach
    </div>
</x-ui.card>
