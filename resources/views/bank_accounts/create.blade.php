<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Yeni Banka Hesabı') }}" subtitle="{{ __('Yeni banka hesabı bilgilerini girin.') }}" />
    </x-slot>

    <x-ui.card>
        <form method="POST" action="{{ route('bank-accounts.store') }}">
            @csrf
            @include('bank_accounts._form', ['bankAccount' => $bankAccount, 'currencies' => $currencies])
        </form>
    </x-ui.card>
</x-app-layout>
