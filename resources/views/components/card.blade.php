<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100']) }}>
    @isset($header)
        <div class="border-b border-gray-100 px-6 py-4 text-sm font-semibold text-gray-700">
            {{ $header }}
        </div>
    @endisset

    <div class="p-6">
        {{ $slot }}
    </div>
</div>
