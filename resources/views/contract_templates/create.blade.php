<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Sözleşme Şablonu Oluştur') }}" subtitle="{{ __('Yeni şablon ekleyin.') }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('admin.contract-templates.index') }}" variant="secondary" size="sm">
                    {{ __('Listeye Dön') }}
                </x-ui.button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <x-ui.card>
        <x-slot name="header">{{ __('Şablon Bilgileri') }}</x-slot>
        <form method="POST" action="{{ route('admin.contract-templates.store') }}" class="space-y-6">
            @csrf

            @include('contract_templates._form', ['template' => $template])

            <div class="flex items-center justify-end gap-3">
                <x-ui.button type="submit" formmethod="POST" formaction="{{ route('admin.contract-templates.preview') }}" variant="secondary">
                    {{ __('Önizleme') }}
                </x-ui.button>
                <x-ui.button type="submit">{{ __('Kaydet') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-app-layout>
