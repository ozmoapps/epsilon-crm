@props(['show' => false])

<div
    x-show="{{ $show }}"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 -translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 -translate-y-2"
    class="fixed top-4 left-1/2 z-50 -translate-x-1/2 transform"
    style="display: none;"
>
    <div class="flex items-center gap-4 rounded-xl border border-blue-200 bg-white p-3 shadow-lg ring-1 ring-blue-500/10">
        <div class="flex items-center gap-3 border-r border-slate-100 pr-3">
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/>
                    <path d="M17 21v-8H7v8"/>
                    <path d="M7 3v5h8"/>
                </svg>
            </span>
            <div>
                <p class="text-sm font-semibold text-slate-800">{{ __('Taslak bulundu') }}</p>
                <p class="text-xs text-slate-500">{{ __('Form verilerini geri yüklemek ister misiniz?') }}</p>
            </div>
        </div>
        
        <div class="flex items-center gap-2">
            <button 
                type="button" 
                x-on:click="restoreDraft()" 
                class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
            >
                {{ __('Geri Yükle') }}
            </button>
            <button 
                type="button" 
                x-on:click="discardDraft()" 
                class="rounded-lg px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-1"
            >
                {{ __('Sil') }}
            </button>
        </div>
    </div>
</div>
