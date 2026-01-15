@props([
    'icon' => 'inbox',
    'title',
    'description' => null,
    'action' => null,
    'actionLabel' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center py-12 text-center']) }}>
    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-50 text-slate-400 mb-3">
        @php
            $isSlotIcon = $icon instanceof \Illuminate\View\ComponentSlot;
            $iconName = !$isSlotIcon ? (string)($icon ?: 'inbox') : null;
        @endphp

        @if($isSlotIcon && trim((string)$icon) !== '')
            {{ $icon }}
        @else
            <x-dynamic-component :component="'icon.' . $iconName" class="h-6 w-6" />
        @endif
    </div>

    <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>

    @if($description)
        <p class="mt-1 text-sm text-slate-500 max-w-sm mx-auto">{{ $description }}</p>
    @endif

    @if(isset($actions))
        <div class="mt-4 flex gap-2">
            {{ $actions }}
        </div>
    @elseif($action && $actionLabel)
        <div class="mt-4">
            <x-ui.button :href="$action" size="sm">
                {{ $actionLabel }}
            </x-ui.button>
        </div>
    @endif

    {{ $slot }}
</div>
