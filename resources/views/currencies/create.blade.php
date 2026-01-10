<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Yeni Para Birimi') }}" subtitle="{{ __('Yeni bir para birimi ekleyin.') }}" />
    </x-slot>

    <x-ui.card>
        <form method="POST" action="{{ route('admin.currencies.store') }}">
            @csrf
            @include('currencies._form', ['currency' => $currency])
        </form>
    </x-ui.card>
</x-app-layout>
