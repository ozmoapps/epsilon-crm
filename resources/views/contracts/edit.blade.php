<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Sözleşme Düzenle') }}" subtitle="{{ $contract->contract_no }}">
            <x-slot name="actions">
                <x-button href="{{ route('contracts.show', $contract) }}" variant="secondary" size="sm">
                    {{ __('Detaya Dön') }}
                </x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">
        @include('contracts._sales_order_summary', ['salesOrder' => $salesOrder])

        <x-card>
            <x-slot name="header">{{ __('Sözleşme Detayları') }}</x-slot>
            <form method="POST" action="{{ route('contracts.update', $contract) }}" class="space-y-6">
                @csrf
                @method('PUT')

                @include('contracts._form', ['contract' => $contract])

                <div class="flex flex-wrap items-center justify-end gap-3">
                    <x-button type="submit" name="apply_template" value="1" variant="secondary">
                        {{ __('Şablonu Uygula') }}
                    </x-button>
                    <x-button type="submit">{{ __('Güncelle') }}</x-button>
                </div>
            </form>
        </x-card>

        <x-card>
            <x-slot name="header">{{ __('Önizleme') }}</x-slot>
            @if ($contract->rendered_body)
                <div class="prose max-w-none">
                    {!! $contract->rendered_body !!}
                </div>
            @elseif ($previewHtml)
                <div class="prose max-w-none">
                    {!! $previewHtml !!}
                </div>
            @else
                <p class="text-sm text-slate-500">{{ __('Henüz bir şablon uygulanmadı.') }}</p>
            @endif
        </x-card>
    </div>
</x-app-layout>
