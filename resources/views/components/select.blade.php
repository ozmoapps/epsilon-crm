<select {{ $attributes->merge(['class' => 'block h-10 w-full rounded-xl border-slate-200 bg-white px-3 text-sm text-slate-900 shadow-sm focus:border-brand-500 focus:ring-brand-500 ui-focus']) }}>
    {{ $slot }}
</select>
