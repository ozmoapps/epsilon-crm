@props([
    'rows' => 5,
    'cols' => 5,
    'density' => 'comfort', // comfort | compact
])

@php
    $headerHeight = $density === 'compact' ? 'h-8' : 'h-10';
    $rowHeight = $density === 'compact' ? 'h-12' : 'h-16';
@endphp

<x-ui.card class="!p-0 overflow-hidden">
    <div class="space-y-0">
        {{-- Header Skeleton --}}
        <div class="border-b border-slate-100 bg-slate-50/70 px-6 {{ $headerHeight }} flex items-center gap-4">
            @for ($i = 0; $i < $cols; $i++)
                <x-ui.skeleton class="h-3 {{ $i === 0 ? 'w-24' : 'w-20' }}" />
            @endfor
        </div>

        {{-- Row Skeletons --}}
        <div class="divide-y divide-slate-100">
            @for ($row = 0; $row < $rows; $row++)
                <div class="px-6 {{ $rowHeight }} flex items-center gap-4">
                    @for ($col = 0; $col < $cols; $col++)
                        @php
                            // Vary widths for more realistic look
                            $widths = ['w-32', 'w-24', 'w-28', 'w-20', 'w-16'];
                            $width = $widths[$col % count($widths)];
                        @endphp
                        <x-ui.skeleton class="h-4 {{ $width }}" />
                    @endfor
                </div>
            @endfor
        </div>
    </div>
</x-ui.card>
