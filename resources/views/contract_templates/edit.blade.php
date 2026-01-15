<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Sözleşme Şablonu Düzenle') }}" subtitle="{{ $template->name }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('admin.contract-templates.index') }}" variant="secondary" size="sm">
                    {{ __('Listeye Dön') }}
                </x-ui.button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <x-ui.card>
        <x-slot name="header">{{ __('Şablon Bilgileri') }}</x-slot>
        <form method="POST" action="{{ route('admin.contract-templates.update', $template) }}" class="space-y-6">
            @csrf
            @method('PUT')
            <input type="hidden" name="template_id" value="{{ $template->id }}">

            @include('contract_templates._form', ['template' => $template])

            <div class="flex items-center justify-end gap-3">
                <x-ui.button type="submit" formmethod="POST" formaction="{{ route('admin.contract-templates.preview') }}" variant="secondary">
                    {{ __('Önizleme') }}
                </x-ui.button>
                <x-ui.button type="submit">{{ __('Güncelle') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card class="mt-6">
        <x-slot name="header">{{ __('Sürüm Geçmişi') }}</x-slot>
        @include('contract_templates._versions', ['template' => $template, 'versions' => $versions])
        <div class="mt-4 text-sm text-slate-500">
            {{ __('Önizleme için sürüm satırındaki butonu kullanabilirsiniz.') }}
        </div>
    </x-ui.card>
</x-app-layout>
