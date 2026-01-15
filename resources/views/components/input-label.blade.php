@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-body-sm font-medium text-slate-700']) }}>
    {{ $value ?? $slot }}
</label>
