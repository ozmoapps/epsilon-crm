@props([
    'count' => 0,
])

<div
    x-cloak

    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-full"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-full"
    {{ $attributes->merge(['class' => 'fixed bottom-6 left-1/2 -translate-x-1/2 z-40 w-full max-w-2xl px-4']) }}
>
    <div class="bg-slate-900/90 backdrop-blur-md text-white rounded-full shadow-2xl px-6 py-3 flex items-center justify-between border border-slate-700/50">
        <div class="flex items-center space-x-4">
            <x-ui.badge variant="info" class="!px-2 !py-0.5 text-xs font-bold min-w-[1.5rem] justify-center">
                {{ $count }}
            </x-ui.badge>
            <span class="text-sm font-medium text-slate-200">
                {{ __('seçildi') }}
            </span>
            <div class="w-px h-4 bg-slate-700"></div>
            
            <div class="flex items-center space-x-2">
                {{ $actions ?? '' }}
            </div>
        </div>

        <div class="flex items-center pl-4 border-l border-slate-700 ml-4">
            <button 
                type="button" 
                @click="clearSelection()" 
                class="text-slate-400 hover:text-white transition-colors text-sm font-medium focus:outline-none"
            >
                <span class="sr-only">{{ __('Seçimi Temizle') }}</span>
                <x-icon.x class="w-5 h-5" />
            </button>
        </div>
    </div>
</div>
