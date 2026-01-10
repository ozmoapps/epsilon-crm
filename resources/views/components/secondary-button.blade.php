<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-xs font-semibold tracking-widest text-slate-700 shadow-soft transition hover:bg-slate-50 ui-focus disabled:cursor-not-allowed disabled:opacity-60']) }}>
    {{ $slot }}
</button>
