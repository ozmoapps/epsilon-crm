<div class="space-y-8">
    {{-- Summary --}}
    <div>
        <h4 class="text-xs font-bold tracking-wider text-slate-500 uppercase mb-4">{{ __('Özet') }}</h4>
        <div class="grid gap-4 text-sm sm:grid-cols-2">
            <div>
                <p class="text-xs font-medium text-slate-500 mb-1">{{ __('Sözleşme No') }}</p>
                <p class="text-sm font-semibold text-slate-900">{{ $contract->contract_no }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 mb-1">{{ __('Revizyon') }}</p>
                <p class="text-sm font-semibold text-slate-900">{{ $contract->revision_label }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 mb-1">{{ __('Durum') }}</p>
                <x-ui.badge :variant="$statusVariants[$contract->status] ?? 'neutral'" class="!px-2 !py-0.5">
                    {{ $contract->status_label }}
                </x-ui.badge>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 mb-1">{{ __('Düzenleme Tarihi') }}</p>
                <p class="text-sm font-semibold text-slate-900">{{ $contract->issued_at?->format('d.m.Y') }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 mb-1">{{ __('İmza Tarihi') }}</p>
                <p class="text-sm font-semibold text-slate-900">{{ $contract->signed_at?->format('d.m.Y H:i') ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 mb-1">{{ __('Dil') }}</p>
                <p class="text-sm font-semibold text-slate-900">{{ config('contracts.locales')[$contract->locale] ?? $contract->locale }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 mb-1">{{ __('Para Birimi') }}</p>
                <p class="text-sm font-semibold text-slate-900">{{ $contract->currency }}</p>
            </div>
        </div>
    </div>

    {{-- Parties --}}
    <div class="border-t border-slate-100 pt-8">
        <h4 class="text-xs font-bold tracking-wider text-slate-500 uppercase mb-4">{{ __('Taraflar') }}</h4>
        <div class="grid gap-4 text-sm sm:grid-cols-2">
            <div>
                <p class="text-xs font-medium text-slate-500 mb-1">{{ __('Müşteri Adı') }}</p>
                <p class="text-sm font-semibold text-slate-900">{{ $contract->customer_name }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 mb-1">{{ __('Firma') }}</p>
                <p class="text-sm font-semibold text-slate-900">{{ $contract->customer_company ?: '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 mb-1">{{ __('Vergi No') }}</p>
                <p class="text-sm font-semibold text-slate-900">{{ $contract->customer_tax_no ?: '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 mb-1">{{ __('Telefon') }}</p>
                <p class="text-sm font-semibold text-slate-900">{{ $contract->customer_phone ?: '-' }}</p>
            </div>
            <div class="sm:col-span-2">
                <p class="text-xs font-medium text-slate-500 mb-1">{{ __('Adres') }}</p>
                <p class="text-sm font-semibold text-slate-900">{{ $contract->customer_address ?: '-' }}</p>
            </div>
            <div class="sm:col-span-2">
                <p class="text-xs font-medium text-slate-500 mb-1">{{ __('E-posta') }}</p>
                <p class="text-sm font-semibold text-slate-900">{{ $contract->customer_email ?: '-' }}</p>
            </div>
        </div>
    </div>

    {{-- Totals --}}
    <div class="border-t border-slate-100 pt-8">
        <h4 class="text-xs font-bold tracking-wider text-slate-500 uppercase mb-4">{{ __('Toplamlar') }}</h4>
        <div class="grid gap-4 text-sm sm:grid-cols-3">
            <div>
                <p class="text-xs font-medium text-slate-500 mb-1">{{ __('Ara Toplam') }}</p>
                <p class="text-sm font-semibold text-slate-900">{{ $formatMoney($contract->subtotal) }} {{ $currencySymbol }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 mb-1">{{ __('Vergi Toplamı') }}</p>
                <p class="text-sm font-semibold text-slate-900">{{ $formatMoney($contract->tax_total) }} {{ $currencySymbol }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 mb-1">{{ __('Genel Toplam') }}</p>
                <p class="text-sm font-bold text-brand-600">{{ $formatMoney($contract->grand_total) }} {{ $currencySymbol }}</p>
            </div>
        </div>
    </div>

    {{-- Conditions --}}
    <div class="border-t border-slate-100 pt-8">
        <h4 class="text-xs font-bold tracking-wider text-slate-500 uppercase mb-4">{{ __('Koşullar') }}</h4>
        <div class="grid gap-4 text-sm text-slate-600 md:grid-cols-2">
            <div>
                <p class="text-xs font-medium text-slate-500 mb-1">{{ __('Ödeme Şartları') }}</p>
                <p class="text-sm text-slate-900">{{ $contract->payment_terms ?: '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 mb-1">{{ __('Garanti Şartları') }}</p>
                <p class="text-sm text-slate-900">{{ $contract->warranty_terms ?: '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 mb-1">{{ __('Kapsam') }}</p>
                <p class="text-sm text-slate-900">{{ $contract->scope_text ?: '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 mb-1">{{ __('Hariç Tutulanlar') }}</p>
                <p class="text-sm text-slate-900">{{ $contract->exclusions_text ?: '-' }}</p>
            </div>
            <div class="md:col-span-2">
                <p class="text-xs font-medium text-slate-500 mb-1">{{ __('Teslim Şartları') }}</p>
                <p class="text-sm text-slate-900">{{ $contract->delivery_terms ?: '-' }}</p>
            </div>
        </div>
    </div>
</div>
