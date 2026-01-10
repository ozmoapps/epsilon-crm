@props([
    'title',
    'subtitle' => null,
])

<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
    <div class="min-w-0 flex-1">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">
                {{ $title }}
            </h1>
            @if(isset($status))
                {{ $status }}
            @endif
        </div>
        @if($subtitle)
            <p class="mt-1 text-sm text-slate-500">
                {{ $subtitle }}
            </p>
        @endif
    </div>
    
    @if(isset($actions))
        <div class="flex flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endif
</div>
