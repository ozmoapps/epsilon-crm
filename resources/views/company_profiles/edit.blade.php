<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Şirket Profilini Düzenle') }}" subtitle="{{ __('Şirket bilgilerinin güncellenmiş halini kaydedin.') }}" />
    </x-slot>

    <x-ui.card>
        <form method="POST" action="{{ route('company-profiles.update', $companyProfile) }}">
            @csrf
            @method('PUT')
            @include('company_profiles._form', ['companyProfile' => $companyProfile])
        </form>
    </x-ui.card>
</x-app-layout>
