@props([
    'title' => null,
    'message' => null,
    'confirmText' => __('Onayla'),
    'cancelText' => __('Vazgeç'),
    'variant' => 'primary',
    'formId' => null,
    'onConfirm' => null,
])

@php
    $dialogId = 'confirm-dialog-' . \Illuminate\Support\Str::uuid();
    $titleId = $dialogId . '-title';
    $messageId = $dialogId . '-message';
    $isLocal = !empty($trigger);
@endphp

<div
    x-data="{
        open: false,
        title: @js($title),
        message: @js($message),
        confirmText: @js($confirmText),
        cancelText: @js($cancelText),
        formId: @js($formId),
        actionUrl: null,
        method: 'POST',
        
        init() {
            if (! @js($isLocal)) {
                // Global Mode Listeners
                window.addEventListener('open-confirm', (event) => {
                    this.openModal(event.detail);
                });

                document.addEventListener('click', (event) => {
                    const trigger = event.target.closest('[data-confirm]');
                    if (trigger) {
                        event.preventDefault();
                        event.stopPropagation();
                        
                        this.openModal({
                            title: trigger.dataset.confirmTitle,
                            message: trigger.dataset.confirmMessage,
                            confirmText: trigger.dataset.confirmText,
                            cancelText: trigger.dataset.confirmCancelText,
                            formId: trigger.dataset.confirmSubmit,
                            actionUrl: trigger.dataset.confirmUrl,
                            method: trigger.dataset.confirmMethod
                        });
                    }
                });
            }
        },

        openModal(data = {}) {
            this.title = data.title || @js($title) || '{{ __('Onay gerekiyor') }}';
            this.message = data.message || @js($message) || '{{ __('Bu işlem geri alınamaz.') }}';
            this.confirmText = data.confirmText || @js($confirmText) || '{{ __('Onayla') }}';
            this.cancelText = data.cancelText || @js($cancelText) || '{{ __('Vazgeç') }}';
            this.formId = data.formId || null;
            this.actionUrl = data.actionUrl || null;
            this.method = data.method || 'POST';
            this.open = true;
        },

        confirm() {
            if (this.formId) {
                const form = document.getElementById(this.formId);
                if (form) {
                    if (form.requestSubmit) {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                }
            } else if (this.actionUrl) {
                // Create a temporary form for URL actions (mostly delete)
                const form = document.createElement('form');
                form.action = this.actionUrl;
                form.method = this.method === 'GET' ? 'GET' : 'POST';
                document.body.appendChild(form);

                if (this.method !== 'GET' && this.method !== 'POST') {
                    const methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = this.method;
                    form.appendChild(methodInput);
                }

                if (this.method !== 'GET') {
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = document.querySelector('meta[name=\'csrf-token\']').getAttribute('content');
                    form.appendChild(csrfInput);
                }

                form.submit();
            }

            @if ($onConfirm)
                {!! $onConfirm !!}
            @endif
            
            this.open = false;
        }
    }"
    x-init="$watch('open', value => {
        if (value) {
            document.body.classList.add('overflow-y-hidden');
            $nextTick(() => ($refs.confirmButton || $refs.cancelButton)?.focus());
        } else {
            document.body.classList.remove('overflow-y-hidden');
        }
    })"
    class="{{ $isLocal ? 'inline-flex' : '' }}"
>
    @if($isLocal)
        <span class="inline-flex" @click="open = true">
            {{ $trigger }}
        </span>
    @endif

    <div
        x-cloak
        x-show="open"
        class="fixed inset-0 z-[10000] flex items-center justify-center px-4 py-6"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="$id('confirm-title')"
        :aria-describedby="$id('confirm-message')"
        @keydown.escape.window="open = false"
    >
        <div 
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-slate-900/40 backdrop-blur-[2px]" 
            @click="open = false"
        ></div>

        <div
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-soft"
            @click.outside="open = false"
        >
            <div class="space-y-4">
                <div class="space-y-2">
                    <h2 :id="$id('confirm-title')" class="text-base font-semibold text-slate-900" x-text="title"></h2>
                    <p :id="$id('confirm-message')" class="text-sm text-slate-600" x-text="message"></p>
                </div>
                
                <div class="flex flex-wrap justify-end gap-3 pt-2">
                    <x-ui.button
                        type="button"
                        variant="ghost"
                        size="sm"
                        x-ref="cancelButton"
                        @click="open = false"
                        x-text="cancelText"
                    >
                    </x-ui.button>
                    <x-ui.button
                        type="button"
                        :variant="$variant"
                        size="sm"
                        x-ref="confirmButton"
                        @click="confirm"
                        x-text="confirmText"
                    >
                    </x-ui.button>
                </div>
            </div>
        </div>
    </div>
</div>
