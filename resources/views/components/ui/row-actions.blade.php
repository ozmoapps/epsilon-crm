@props([
    'show' => null,
    'edit' => null,
    'delete' => null,
    'deleteFormId' => null,
    'confirmTitle' => __('Silinsin mi?'),
    'confirmMessage' => __('Bu işlem geri alınamaz.'),
    'confirmText' => __('Sil'),
    'cancelText' => __('Vazgeç'),
    'editDisabled' => false,
    'deleteDisabled' => false,
    'editDisabledTitle' => null,
    'deleteDisabledTitle' => null,
    'viewLabel' => __('Görüntüle'),
    'editLabel' => __('Düzenle'),
    'deleteLabel' => __('Sil'),
])

<div {{ $attributes->merge(['class' => 'flex items-center justify-end gap-2']) }}>
    @if ($show)
        <a
            href="{{ $show }}"
            class="inline-flex h-8 w-8 items-center justify-center rounded-xl text-slate-600 transition hover:bg-slate-100/70 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/20"
            aria-label="{{ $viewLabel }}"
        >
            <x-icon.info class="h-4 w-4" />
            <span class="sr-only">{{ $viewLabel }}</span>
        </a>
    @endif

    @if ($edit)
        @if ($editDisabled)
            <button
                type="button"
                class="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-xl text-slate-300"
                aria-label="{{ $editDisabledTitle ?? $editLabel }}"
                aria-disabled="true"
            >
                <x-icon.pencil class="h-4 w-4" />
                <span class="sr-only">{{ $editDisabledTitle ?? $editLabel }}</span>
            </button>
        @else
            <a
                href="{{ $edit }}"
                class="inline-flex h-8 w-8 items-center justify-center rounded-xl text-slate-600 transition hover:bg-slate-100/70 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/20"
                aria-label="{{ $editLabel }}"
            >
                <x-icon.pencil class="h-4 w-4" />
                <span class="sr-only">{{ $editLabel }}</span>
            </a>
        @endif
    @endif

    @if ($delete)
        @if ($deleteDisabled)
            <button
                type="button"
                class="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-xl text-slate-300"
                aria-label="{{ $deleteDisabledTitle ?? $deleteLabel }}"
                aria-disabled="true"
            >
                <x-icon.trash class="h-4 w-4" />
                <span class="sr-only">{{ $deleteDisabledTitle ?? $deleteLabel }}</span>
            </button>
        @elseif ($deleteFormId)
            <button
                type="button"
                class="inline-flex h-8 w-8 items-center justify-center rounded-xl text-slate-600 transition hover:bg-rose-50 hover:text-rose-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500/20"
                aria-label="{{ $deleteLabel }}"
                data-confirm
                data-confirm-title="{{ $confirmTitle }}"
                data-confirm-message="{{ $confirmMessage }}"
                data-confirm-text="{{ $confirmText }}"
                data-confirm-cancel-text="{{ $cancelText }}"
                data-confirm-submit="{{ $deleteFormId }}"
            >
                <x-icon.trash class="h-4 w-4" />
                <span class="sr-only">{{ $deleteLabel }}</span>
            </button>
        @endif
    @endif

    {{ $slot }}
</div>
