@props([
    'header' => null,
    'left' => null,
    'right' => null,
])

<div class="max-w-7xl mx-auto px-6 py-6 transition-all duration-300 ease-in-out">
    @if(isset($header))
        {{ $header }}
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <div class="lg:col-span-8 min-w-0 space-y-6">
            {{ $left }}
        </div>
        
        <div class="lg:col-span-4 min-w-0 space-y-6 lg:sticky lg:top-6 self-start">
            {{ $right }}
        </div>
    </div>
</div>
