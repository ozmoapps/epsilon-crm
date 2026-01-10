@props([
    'header' => null,
    'left' => null,
    'right' => null,
])

<div class="mx-auto w-full max-w-screen-2xl px-4 py-6 transition-all duration-300 ease-in-out sm:px-6 lg:px-8">
    @if(isset($header))
        {{ $header }}
    @endif

    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
        <div class="min-w-0 space-y-6 lg:col-span-8">
            {{ $left }}
        </div>
        
        <div class="min-w-0 space-y-6 lg:col-span-4 lg:sticky lg:top-6 self-start">
            {{ $right }}
        </div>
    </div>
</div>
