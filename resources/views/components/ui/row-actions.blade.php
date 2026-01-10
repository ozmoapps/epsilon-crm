@props([
    'show' => null,
    'edit' => null,
    'delete' => null,
    'deleteFormId' => null,
    'confirmTitle' => __('Silme işlemini onayla'),
    'confirmMessage' => __('Bu işlem geri alınamaz. Devam etmek istiyor musunuz?'),
    'confirmText' => __('Evet, sil'),
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
            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-sky-600 transition hover:bg-sky-50 hover:text-sky-700"
            title="{{ $viewLabel }}"
            aria-label="{{ $viewLabel }}"
        >
            <x-icon.info class="h-4 w-4" />
        </a>
    @endif

    @if ($edit)
        @if ($editDisabled)
            <button
                type="button"
                class="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-lg text-slate-300"
                title="{{ $editDisabledTitle ?? $editLabel }}"
                aria-label="{{ $editDisabledTitle ?? $editLabel }}"
                aria-disabled="true"
            >
                <x-icon.pencil class="h-4 w-4" />
            </button>
        @else
            <a
                href="{{ $edit }}"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-indigo-600 transition hover:bg-indigo-50 hover:text-indigo-700"
                title="{{ $editLabel }}"
                aria-label="{{ $editLabel }}"
            >
                <x-icon.pencil class="h-4 w-4" />
            </a>
        @endif
    @endif

    @if ($delete)
        @if ($deleteDisabled)
            <button
                type="button"
                class="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-lg text-slate-300"
                title="{{ $deleteDisabledTitle ?? $deleteLabel }}"
                aria-label="{{ $deleteDisabledTitle ?? $deleteLabel }}"
                aria-disabled="true"
            >
                <x-icon.trash class="h-4 w-4" />
            </button>
        @elseif ($deleteFormId)
            <x-ui.confirm
                title="{{ $confirmTitle }}"
                message="{{ $confirmMessage }}"
                confirm-text="{{ $confirmText }}"
                cancel-text="{{ $cancelText }}"
                variant="danger"
                form-id="{{ $deleteFormId }}"
            >
                <x-slot name="trigger">
                    <button
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-rose-600 transition hover:bg-rose-50 hover:text-rose-700"
                        title="{{ $deleteLabel }}"
                        aria-label="{{ $deleteLabel }}"
                    >
                        <x-icon.trash class="h-4 w-4" />
                    </button>
                </x-slot>
            </x-ui.confirm>
        @endif
    @endif

    {{ $slot }}
</div>
