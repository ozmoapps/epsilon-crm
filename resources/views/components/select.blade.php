<select {{ $attributes->merge(['class' => 'block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500']) }}>
    {{ $slot }}
</select>
