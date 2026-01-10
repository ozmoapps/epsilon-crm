@props([
    'title',
    'subtitle' => null,
])

<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between px-6 py-2">
    <div class="min-w-0 flex-1">
        <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">
            {{ $title }}
        </h1>
        @if ($subtitle)
            <div class="mt-1 flex items-center gap-2 text-sm text-slate-500">
                {{ $subtitle }}
            </div>
        @endif
        @if (isset($status))
            <div class="mt-2">
                {{ $status }}
            </div>
        @endif
    </div>

    @if (isset($actions))
        <div class="flex flex-wrap items-center gap-3">
            {{ $actions }}
        </div>
    @endif
</div>
