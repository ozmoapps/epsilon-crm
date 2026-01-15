<div {{ $attributes->merge(['class' => 'overflow-hidden ui-card']) }}>
    @isset($header)
        <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-4 text-heading-4 text-slate-900">
            {{ $header }}
        </div>
    @endisset

    <div class="p-6">
        {{ $slot }}
    </div>
</div>
