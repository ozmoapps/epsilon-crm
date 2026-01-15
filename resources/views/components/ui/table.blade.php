@props(['density' => 'comfort'])

@php
    $densityClasses = [
        'comfort' => '[&_thead_th]:py-3 [&_tbody_td]:py-3',
        'compact' => '[&_thead_th]:py-2 [&_tbody_td]:py-2',
    ];
    $densityClass = $densityClasses[$density] ?? $densityClasses['comfort'];

    $hasHead = isset($head);
    $hasBody = isset($body);
    $hasFoot = isset($foot);
    $useNamedSlots = $hasHead || $hasBody || $hasFoot;
@endphp

<div {{ $attributes->merge(['class' => 'overflow-x-auto']) }}>
    <table class="w-full text-sm text-slate-700 {{ $densityClass }} [&_thead]:bg-slate-50 [&_thead_th]:text-caption [&_thead_th]:text-slate-500 [&_tbody_tr]:border-b [&_tbody_tr]:border-slate-100 [&_tbody_tr]:transition-colors [&_tbody_tr]:hover:bg-slate-50">
        @if($useNamedSlots)
            @if($hasHead)
                <thead>
                    {{ $head }}
                </thead>
            @endif

            <tbody>
                @if($hasBody)
                    {{ $body }}
                @else
                    {{ $slot }}
                @endif
            </tbody>

            @if($hasFoot)
                <tfoot>
                    {{ $foot }}
                </tfoot>
            @endif
        @else
            {{ $slot }}
        @endif
    </table>
</div>
