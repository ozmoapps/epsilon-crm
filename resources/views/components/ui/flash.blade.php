@php
    $messages = [
        'success' => ['type' => 'success', 'title' => __('Başarılı')],
        'error' => ['type' => 'danger', 'title' => __('Hata')],
        'warning' => ['type' => 'warning', 'title' => __('Uyarı')],
        'info' => ['type' => 'info', 'title' => __('Bilgi')],
    ];

    $activeMessages = collect($messages)->filter(fn ($config, $key) => session()->has($key));
@endphp

@if ($activeMessages->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'space-y-3']) }}>
        @foreach ($activeMessages as $key => $config)
            <x-ui.alert :type="$config['type']" :title="$config['title']" dismissible>
                {{ session($key) }}
            </x-ui.alert>
        @endforeach
    </div>
@endif
