@props([
    'action',
    'method' => 'GET',
])

<x-ui.card class="p-4">
    <form action="{{ $action }}" method="{{ $method }}" class="flex flex-col lg:flex-row lg:items-center gap-4 w-full">
        {{-- Left Slot: Usually for Search --}}
        @if(isset($left))
            <div class="w-full lg:w-72 flex-none">
                {{ $left }}
            </div>
        @endif

        {{-- Right Slot: Filters --}}
        @if(isset($right))
            <div {{ $right->attributes->merge(['class' => 'flex-1 flex flex-col sm:flex-row flex-wrap items-center gap-3']) }}>
                {{ $right }}
            </div>
        @endif

        {{-- Actions Slot: Submit/Reset Buttons --}}
        @if(isset($actions))
            <div class="flex items-center gap-2 w-full sm:w-auto justify-end flex-none lg:ml-auto">
                {{ $actions }}
            </div>
        @endif
    </form>
</x-ui.card>
