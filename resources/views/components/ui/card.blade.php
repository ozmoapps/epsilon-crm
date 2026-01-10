<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-card']) }}>
    @isset($header)
        <div class="border-b border-slate-100 bg-slate-50/70 px-6 py-4 text-sm font-semibold text-slate-700">
            {{ $header }}
        </div>
    @endisset

    <div class="p-6">
        {{ $slot }}
    </div>
</div>
