@props([
    'title',
    'message',
    'confirmText' => 'Onayla',
    'cancelText' => 'Vazgeç',
    'variant' => 'primary',
    'formId' => null,
    'onConfirm' => null,
])

@php
    $dialogId = 'confirm-dialog-' . \Illuminate\Support\Str::uuid();
    $titleId = $dialogId . '-title';
    $messageId = $dialogId . '-message';
@endphp

<div
    x-data="{
        open: false,
        formId: @js($formId),
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
            }
            @if ($onConfirm)
                {!! $onConfirm !!}
            @endif
            this.open = false;
        },
    }"
    x-init="$watch('open', value => {
        if (value) {
            document.body.classList.add('overflow-y-hidden');
            $nextTick(() => ($refs.confirmButton || $refs.cancelButton)?.focus());
        } else {
            document.body.classList.remove('overflow-y-hidden');
        }
    })"
    class="inline-flex"
>
    <span class="inline-flex" @click="open = true">
        {{ $trigger }}
    </span>

    <div
        x-cloak
        x-show="open"
        class="fixed inset-0 z-[10000] flex items-center justify-center px-4 py-6"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $titleId }}"
        aria-describedby="{{ $messageId }}"
        @keydown.escape.window="open = false"
    >
        <div class="fixed inset-0 bg-slate-900/50" @click="open = false"></div>
        <div
            class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-soft"
            @click.outside="open = false"
        >
            <div class="space-y-3">
                <div class="space-y-1">
                    <h2 id="{{ $titleId }}" class="text-lg font-semibold text-slate-900">
                        {{ $title }}
                    </h2>
                    <p id="{{ $messageId }}" class="text-sm text-slate-600">
                        {{ $message }}
                    </p>
                </div>
                <div class="flex flex-wrap justify-end gap-3 pt-2">
                    <x-ui.button
                        type="button"
                        variant="secondary"
                        size="sm"
                        x-ref="cancelButton"
                        @click="open = false"
                    >
                        {{ $cancelText }}
                    </x-ui.button>
                    <x-ui.button
                        type="button"
                        :variant="$variant"
                        size="sm"
                        x-ref="confirmButton"
                        @click="confirm"
                    >
                        {{ $confirmText }}
                    </x-ui.button>
                </div>
            </div>
        </div>
    </div>
</div>
