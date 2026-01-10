<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm']) }}>
    <div class="overflow-x-auto sm:overflow-x-hidden">
        <table class="w-full table-fixed text-sm text-slate-700">
            {{ $slot }}
        </table>
    </div>
</div>
