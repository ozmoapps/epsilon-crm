<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex h-10 items-center justify-center rounded-xl bg-rose-600 px-4 text-xs font-semibold uppercase tracking-widest text-white shadow-soft transition hover:bg-rose-500 ui-focus disabled:cursor-not-allowed disabled:opacity-60']) }}>
    {{ $slot }}
</button>
