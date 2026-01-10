<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Para Birimini Düzenle') }}" subtitle="{{ __('Para birimi bilgilerini güncelleyin.') }}" />
    </x-slot>

    <x-ui.card>
        <form method="POST" action="{{ route('currencies.update', $currency) }}">
            @csrf
            @method('PUT')
            @include('currencies._form', ['currency' => $currency])
        </form>
    </x-ui.card>
</x-app-layout>
