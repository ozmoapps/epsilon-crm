<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-slate-200 bg-white']) }}>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm text-slate-700">
            {{ $slot }}
        </table>
    </div>
</div>
