<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Sözleşme Şablonu Detayı') }}" subtitle="{{ $template->name }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('admin.contract-templates.edit', $template) }}" variant="secondary" size="sm">
                    {{ __('Düzenle') }}
                </x-ui.button>
                <x-ui.button href="{{ route('admin.contract-templates.index') }}" variant="secondary" size="sm">
                    {{ __('Listeye Dön') }}
                </x-ui.button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">
        <x-ui.card>
            <x-slot name="header">{{ __('Şablon Bilgileri') }}</x-slot>
            <div class="grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <p class="text-xs tracking-wide text-slate-500">{{ __('Ad') }}</p>
                    <p class="text-base font-medium text-slate-900">{{ $template->name }}</p>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-slate-500">{{ __('Dil') }}</p>
                    <p class="text-base font-medium text-slate-900">{{ config('contracts.locales')[$template->locale] ?? $template->locale }}</p>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-slate-500">{{ __('Format') }}</p>
                    <p class="text-base font-medium text-slate-900">{{ strtoupper($template->format) }}</p>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-slate-500">{{ __('Durum') }}</p>
                    <p class="text-base font-medium text-slate-900">
                        {{ $template->is_active ? __('Aktif') : __('Pasif') }}
                        @if ($template->is_default)
                            <span class="ml-2 rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">{{ __('Varsayılan') }}</span>
                        @endif
                    </p>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <x-slot name="header">{{ __('Sürüm Geçmişi') }}</x-slot>
            @include('contract_templates._versions', ['template' => $template, 'versions' => $versions])
        </x-ui.card>

        @if ($previewHtml)
            <x-ui.card>
                <x-slot name="header">{{ __('Önizleme') }}</x-slot>
                <div class="prose max-w-none">
                    {!! $previewHtml !!}
                </div>
            </x-ui.card>
        @endif
    </div>
</x-app-layout>
