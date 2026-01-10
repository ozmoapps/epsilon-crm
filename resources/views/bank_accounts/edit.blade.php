<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Banka Hesabını Düzenle') }}" subtitle="{{ __('Banka hesabı bilgilerini güncelleyin.') }}" />
    </x-slot>

    <x-ui.card>
        <form method="POST" action="{{ route('admin.bank-accounts.update', $bankAccount) }}">
            @csrf
            @method('PUT')
            @include('bank_accounts._form', ['bankAccount' => $bankAccount, 'currencies' => $currencies])
        </form>
    </x-ui.card>
</x-app-layout>
