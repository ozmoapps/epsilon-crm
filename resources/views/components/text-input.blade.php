@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'h-10 w-full rounded-xl border-slate-200 bg-white px-3 text-sm text-slate-900 shadow-sm focus:border-brand-500 focus:ring-brand-500 ui-focus disabled:cursor-not-allowed disabled:opacity-60']) }}>
