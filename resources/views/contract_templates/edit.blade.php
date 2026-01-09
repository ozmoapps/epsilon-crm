<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Sözleşme Şablonu Düzenle') }}" subtitle="{{ $template->name }}">
            <x-slot name="actions">
                <x-button href="{{ route('contract-templates.index') }}" variant="secondary" size="sm">
                    {{ __('Listeye Dön') }}
                </x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <x-card>
        <x-slot name="header">{{ __('Şablon Bilgileri') }}</x-slot>
        <form method="POST" action="{{ route('contract-templates.update', $template) }}" class="space-y-6">
            @csrf
            @method('PUT')
            <input type="hidden" name="template_id" value="{{ $template->id }}">

            @include('contract_templates._form', ['template' => $template])

            <div class="flex items-center justify-end gap-3">
                <x-button type="submit" formmethod="POST" formaction="{{ route('contract-templates.preview') }}" variant="secondary">
                    {{ __('Önizleme') }}
                </x-button>
                <x-button type="submit">{{ __('Güncelle') }}</x-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
