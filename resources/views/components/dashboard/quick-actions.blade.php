<x-ui.card>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <span>{{ __('Hızlı Aksiyonlar') }}</span>
            <span class="text-xs font-medium text-slate-400">{{ __('Klavye ile erişilebilir') }}</span>
        </div>
    </x-slot>

    <div class="grid gap-3">
        <x-ui.button href="{{ route('quotes.create') }}" class="w-full justify-between" aria-label="{{ __('Yeni teklif oluştur') }}">
            <span>{{ __('Yeni Teklif') }}</span>
            <x-icon.plus class="h-4 w-4" aria-hidden="true" />
        </x-ui.button>
        <x-ui.button href="{{ route('sales-orders.create') }}" variant="secondary" class="w-full justify-between" aria-label="{{ __('Yeni satış siparişi oluştur') }}">
            <span>{{ __('Yeni Satış Siparişi') }}</span>
            <x-icon.plus class="h-4 w-4" aria-hidden="true" />
        </x-ui.button>
        <x-ui.button href="{{ route('work-orders.create') }}" variant="secondary" class="w-full justify-between" aria-label="{{ __('Yeni iş emri oluştur') }}">
            <span>{{ __('Yeni İş Emri') }}</span>
            <x-icon.plus class="h-4 w-4" aria-hidden="true" />
        </x-ui.button>
        <x-ui.button href="{{ route('contracts.index') }}" variant="ghost" class="w-full justify-between" aria-label="{{ __('Sözleşmeleri görüntüle') }}">
            <span>{{ __('Sözleşmeleri Görüntüle') }}</span>
            <x-icon.arrow-right class="h-4 w-4" aria-hidden="true" />
        </x-ui.button>
    </div>
</x-ui.card>
