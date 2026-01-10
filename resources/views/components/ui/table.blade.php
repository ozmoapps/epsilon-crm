<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card']) }}>
    <div class="overflow-x-auto sm:overflow-visible">
        <table class="min-w-full table-fixed text-sm text-slate-700">
            {{ $slot }}
        </table>
    </div>
</div>
