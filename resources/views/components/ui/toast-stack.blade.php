{{-- Toast Stack Component --}}
<div
    class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"
    aria-live="polite"
    aria-atomic="true"
    {{-- Init with session flashes --}}
    @php
        $normalizeMessage = function($msg) {
            if ($msg === 'Oluşturuldu.') return 'Kaydedildi.';
            return $msg;
        };
    @endphp
    x-init="
        @if(session('success'))
            $store.toast.add(@js($normalizeMessage(session('success'))), 'success');
        @endif
        @if(session('error'))
            $store.toast.add(@js(session('error')), 'danger');
        @endif
        @if(session('warning'))
            {{-- Map warning to info for premium/calm look --}}
            $store.toast.add(@js($normalizeMessage(session('warning'))), 'info');
        @endif
        @if(session('info'))
            $store.toast.add(@js($normalizeMessage(session('info'))), 'info');
        @endif
        {{-- Legacy key mapping --}}
        @if(session('status'))
            $store.toast.add(@js($normalizeMessage(session('status'))), 'success');
        @endif
        @if(session('message'))
            $store.toast.add(@js(session('message')), 'info');
        @endif
    "
>
    <!-- 
        x-data not needed here because we use global $store.toast 
        However, to loop we use template tag accessing the store.
    -->
    <template x-for="toast in $store.toast.notifications" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2"
            class="flex items-start gap-3 rounded-2xl p-3 shadow-soft ring-1 ring-inset backdrop-blur-sm min-w-[320px] max-w-md pointer-events-auto"
            :class="{
                'bg-slate-50/95 text-slate-700 ring-slate-200/60': toast.variant === 'neutral',
                'bg-brand-50/95 text-brand-700 ring-brand-200/60': toast.variant === 'info',
                'bg-emerald-50/95 text-emerald-700 ring-emerald-200/60': toast.variant === 'success',
                'bg-amber-50/95 text-amber-700 ring-amber-200/60': toast.variant === 'warning',
                'bg-rose-50/95 text-rose-700 ring-rose-200/60': toast.variant === 'danger'
            }"
            role="alert"
        >
            {{-- Icons --}}
            <div class="flex-shrink-0">
                <template x-if="toast.variant === 'neutral'">
                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                </template>
                <template x-if="toast.variant === 'info'">
                    <svg class="h-5 w-5 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                </template>
                <template x-if="toast.variant === 'success'">
                     <svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>
                {{-- Warning uses Amber IF explicitly requested, but we map session('warning') to info above --}}
                <template x-if="toast.variant === 'warning'">
                    <svg class="h-5 w-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </template>
                <template x-if="toast.variant === 'danger'">
                    <svg class="h-5 w-5 text-rose-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>
            </div>

            <p x-text="toast.message" class="flex-1 text-sm font-medium"></p>

            <button
                @click="$store.toast.remove(toast.id)"
                type="button"
                class="flex-shrink-0 -mr-1 -mt-0.5 inline-flex rounded-lg p-1.5 hover:bg-black/5 focus:outline-none focus:ring-2 focus:ring-offset-2"
                :class="{
                    'focus:ring-brand-500': toast.variant === 'info',
                    'focus:ring-emerald-500': toast.variant === 'success',
                    'focus:ring-amber-500': toast.variant === 'warning',
                    'focus:ring-rose-500': toast.variant === 'danger',
                    'focus:ring-slate-500': toast.variant === 'neutral',
                }"
            >
                <span class="sr-only">Kapat</span>
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </template>
</div>
