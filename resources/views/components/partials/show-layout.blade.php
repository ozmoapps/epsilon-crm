<div class="max-w-7xl mx-auto px-6 pb-12">
    @if(isset($header))
        {{ $header }}
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        {{-- Main Content --}}
        <div class="lg:col-span-8 space-y-8 min-w-0">
            {{ $left }}
        </div>

        {{-- Sidebar --}}
        <div class="lg:col-span-4 space-y-8 lg:sticky lg:top-8 self-start min-w-0">
            {{ $right }}
        </div>
    </div>
</div>
