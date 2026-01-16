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

<div {{ $attributes->merge(['class' => 'flex items-center justify-end gap-1.5']) }}>
    @if ($show)
        <a
            href="{{ $show }}"
            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-slate-600 shadow-sm transition-colors hover:bg-slate-50 hover:text-slate-900 hover:border-slate-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300 focus-visible:ring-offset-2 focus-visible:ring-offset-white active:bg-slate-100"
            aria-label="{{ $viewLabel }}"
            title="{{ $viewLabel }}"
        >
            <x-icon.search class="h-4 w-4" />
            <span class="sr-only">{{ $viewLabel }}</span>
        </a>
    @endif

    @if ($edit)
        @if ($editDisabled)
            <button
                type="button"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200/60 bg-white text-slate-300 opacity-40 cursor-not-allowed pointer-events-none"
                aria-label="{{ $editDisabledTitle ?? $editLabel }}"
                title="{{ $editDisabledTitle ?? $editLabel }}"
                aria-disabled="true"
            >
                <x-icon.pencil class="h-4 w-4" />
                <span class="sr-only">{{ $editDisabledTitle ?? $editLabel }}</span>
            </button>
        @else
            <a
                href="{{ $edit }}"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-slate-600 shadow-sm transition-colors hover:bg-slate-50 hover:text-slate-900 hover:border-slate-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300 focus-visible:ring-offset-2 focus-visible:ring-offset-white active:bg-slate-100"
                aria-label="{{ $editLabel }}"
                title="{{ $editLabel }}"
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
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200/60 bg-white text-slate-300 opacity-40 cursor-not-allowed pointer-events-none"
                aria-label="{{ $deleteDisabledTitle ?? $deleteLabel }}"
                title="{{ $deleteDisabledTitle ?? $deleteLabel }}"
                aria-disabled="true"
            >
                <x-icon.trash class="h-4 w-4" />
                <span class="sr-only">{{ $deleteDisabledTitle ?? $deleteLabel }}</span>
            </button>
        @elseif ($deleteFormId)
            <button
                type="button"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-slate-600 shadow-sm transition-colors hover:bg-rose-50 hover:text-rose-700 hover:border-rose-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-200 focus-visible:ring-offset-2 focus-visible:ring-offset-white active:bg-rose-100/50"
                aria-label="{{ $deleteLabel }}"
                title="{{ $deleteLabel }}"
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
