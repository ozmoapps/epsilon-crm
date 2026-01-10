<x-card>
    <x-slot name="header">{{ __('Özet') }}</x-slot>
    <div class="grid gap-4 text-sm sm:grid-cols-2">
        <div>
            <p class="text-xs tracking-wide text-slate-500">{{ __('Sözleşme No') }}</p>
            <p class="text-base font-medium text-slate-900">{{ $contract->contract_no }}</p>
        </div>
        <div>
            <p class="text-xs tracking-wide text-slate-500">{{ __('Revizyon') }}</p>
            <p class="text-base font-medium text-slate-900">{{ $contract->revision_label }}</p>
        </div>
        <div>
            <p class="text-xs tracking-wide text-slate-500">{{ __('Durum') }}</p>
            <x-ui.badge :variant="$statusVariants[$contract->status] ?? 'neutral'">
                {{ $contract->status_label }}
            </x-ui.badge>
        </div>
        <div>
            <p class="text-xs tracking-wide text-slate-500">{{ __('Düzenleme Tarihi') }}</p>
            <p class="text-base font-medium text-slate-900">{{ $contract->issued_at?->format('d.m.Y') }}</p>
        </div>
        <div>
            <p class="text-xs tracking-wide text-slate-500">{{ __('İmza Tarihi') }}</p>
            <p class="text-base font-medium text-slate-900">{{ $contract->signed_at?->format('d.m.Y H:i') ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs tracking-wide text-slate-500">{{ __('Dil') }}</p>
            <p class="text-base font-medium text-slate-900">{{ config('contracts.locales')[$contract->locale] ?? $contract->locale }}</p>
        </div>
        <div>
            <p class="text-xs tracking-wide text-slate-500">{{ __('Para Birimi') }}</p>
            <p class="text-base font-medium text-slate-900">{{ $contract->currency }}</p>
        </div>
    </div>
</x-card>

<x-card>
    <x-slot name="header">{{ __('Taraflar') }}</x-slot>
    <div class="grid gap-4 text-sm sm:grid-cols-2">
        <div>
            <p class="text-xs tracking-wide text-slate-500">{{ __('Müşteri Adı') }}</p>
            <p class="text-base font-medium text-slate-900">{{ $contract->customer_name }}</p>
        </div>
        <div>
            <p class="text-xs tracking-wide text-slate-500">{{ __('Firma') }}</p>
            <p class="text-base font-medium text-slate-900">{{ $contract->customer_company ?: '-' }}</p>
        </div>
        <div>
            <p class="text-xs tracking-wide text-slate-500">{{ __('Vergi No') }}</p>
            <p class="text-base font-medium text-slate-900">{{ $contract->customer_tax_no ?: '-' }}</p>
        </div>
        <div>
            <p class="text-xs tracking-wide text-slate-500">{{ __('Telefon') }}</p>
            <p class="text-base font-medium text-slate-900">{{ $contract->customer_phone ?: '-' }}</p>
        </div>
        <div class="sm:col-span-2">
            <p class="text-xs tracking-wide text-slate-500">{{ __('Adres') }}</p>
            <p class="text-base font-medium text-slate-900">{{ $contract->customer_address ?: '-' }}</p>
        </div>
        <div class="sm:col-span-2">
            <p class="text-xs tracking-wide text-slate-500">{{ __('E-posta') }}</p>
            <p class="text-base font-medium text-slate-900">{{ $contract->customer_email ?: '-' }}</p>
        </div>
    </div>
</x-card>

<x-card>
    <x-slot name="header">{{ __('Toplamlar') }}</x-slot>
    <div class="grid gap-4 text-sm sm:grid-cols-3">
        <div>
            <p class="text-xs tracking-wide text-slate-500">{{ __('Ara Toplam') }}</p>
            <p class="text-base font-medium text-slate-900">{{ $formatMoney($contract->subtotal) }} {{ $currencySymbol }}</p>
        </div>
        <div>
            <p class="text-xs tracking-wide text-slate-500">{{ __('Vergi Toplamı') }}</p>
            <p class="text-base font-medium text-slate-900">{{ $formatMoney($contract->tax_total) }} {{ $currencySymbol }}</p>
        </div>
        <div>
            <p class="text-xs tracking-wide text-slate-500">{{ __('Genel Toplam') }}</p>
            <p class="text-base font-medium text-slate-900">{{ $formatMoney($contract->grand_total) }} {{ $currencySymbol }}</p>
        </div>
    </div>
</x-card>

<x-card>
    <x-slot name="header">{{ __('Koşullar') }}</x-slot>
    <div class="grid gap-4 text-sm text-slate-700 md:grid-cols-2">
        <div>
            <p class="font-semibold text-slate-900">{{ __('Ödeme Şartları') }}</p>
            <p class="mt-1">{{ $contract->payment_terms ?: '-' }}</p>
        </div>
        <div>
            <p class="font-semibold text-slate-900">{{ __('Garanti Şartları') }}</p>
            <p class="mt-1">{{ $contract->warranty_terms ?: '-' }}</p>
        </div>
        <div>
            <p class="font-semibold text-slate-900">{{ __('Kapsam') }}</p>
            <p class="mt-1">{{ $contract->scope_text ?: '-' }}</p>
        </div>
        <div>
            <p class="font-semibold text-slate-900">{{ __('Hariç Tutulanlar') }}</p>
            <p class="mt-1">{{ $contract->exclusions_text ?: '-' }}</p>
        </div>
        <div class="md:col-span-2">
            <p class="font-semibold text-slate-900">{{ __('Teslim Şartları') }}</p>
            <p class="mt-1">{{ $contract->delivery_terms ?: '-' }}</p>
        </div>
    </div>
</x-card>
