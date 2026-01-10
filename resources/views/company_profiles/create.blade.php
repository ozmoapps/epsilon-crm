<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Yeni Şirket Profili') }}" subtitle="{{ __('Şirket bilgilerini oluşturun.') }}" />
    </x-slot>

    <x-ui.card>
        <form method="POST" action="{{ route('admin.company-profiles.store') }}">
            @csrf
            @include('company_profiles._form', ['companyProfile' => $companyProfile])
        </form>
    </x-ui.card>
</x-app-layout>
