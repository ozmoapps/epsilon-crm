@php
    $companyName = $companyProfile?->name ?? config('company.name');
    $companyAddress = $companyProfile?->address ?? config('company.address');
    $companyPhone = $companyProfile?->phone ?? config('company.phone');
    $companyEmail = $companyProfile?->email ?? config('company.email');
    $companyFooter = $companyProfile?->footer_text ?? config('company.footer_text');
    
    // Safety check for relations
    $salesOrder = $contract->salesOrder;
    $customer = $salesOrder?->customer;
    $vessel = $salesOrder?->vessel;
    $items = $salesOrder?->items ?? collect();
    
    $currencyCode = $contract->currency;
    $currencySymbol = \App\Models\Currency::where('code', $currencyCode)->value('symbol') ?? $currencyCode;
    $formatMoney = fn ($value) => \App\Support\MoneyMath::formatTR($value);
    
    $itemsBySection = $items->groupBy(fn ($item) => $item->section ?: 'Genel');
@endphp

<div class="header">
    <div>
        <h1>{{ $companyName }}</h1>
        <p class="muted">{{ $companyAddress }}</p>
        <p class="muted">{{ $companyPhone }} · {{ $companyEmail }}</p>
    </div>
    <div class="quote-meta">
        <h1>{{ __('SÖZLEŞME') }}</h1>
        <table>
            <tr>
                <td class="muted">{{ __('Sözleşme No') }}</td>
                <td>{{ $contract->contract_no }}</td>
            </tr>
            @if($contract->revision_no)
            <tr>
                <td class="muted">{{ __('Revizyon') }}</td>
                <td>R{{ $contract->revision_no }}</td>
            </tr>
            @endif
            <tr>
                <td class="muted">{{ __('Tarih') }}</td>
                <td>{{ $contract->issued_at?->format('d.m.Y') ?? $contract->created_at->format('d.m.Y') }}</td>
            </tr>
            <tr>
                <td class="muted">{{ __('Durum') }}</td>
                <td>{{ $contract->status_label }}</td>
            </tr>
        </table>
    </div>
</div>

<div class="info-grid">
    <div class="info-block">
        <h3>{{ __('Müşteri') }}</h3>
        <p><strong>{{ $contract->customer_name }}</strong></p>
        @if($contract->customer_company)
        <p>{{ $contract->customer_company }}</p>
        @endif
        <p class="muted">{{ $contract->customer_address }}</p>
        <p class="muted">{{ $contract->customer_phone }}</p>
        <p class="muted">{{ $contract->customer_email }}</p>
    </div>
    <div class="info-block">
        <h3>{{ __('Tekne Bilgileri') }}</h3>
        <p><strong>{{ $vessel?->name ?? '-' }}</strong></p>
        <p class="muted">{{ $vessel?->type ?? '-' }} / {{ $vessel?->flag ?? '-' }}</p>
    </div>
</div>

{{-- Items Table --}}
@if($items->isNotEmpty())
    <div class="section">
        <h3 class="section-title">{{ __('Hizmet ve Ürün Detayları') }}</h3>
        <table class="doc-table">
            <thead>
                <tr>
                    <th style="width: 40%">{{ __('AÇIKLAMA') }}</th>
                    <th class="text-right" style="width: 15%">{{ __('MİKTAR') }}</th>
                    <th class="text-right" style="width: 20%">{{ __('BİRİM FİYAT') }}</th>
                    <th class="text-right" style="width: 25%">{{ __('TOPLAM') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($itemsBySection as $section => $sectionItems)
                    @if($section !== 'Genel' || $itemsBySection->count() > 1)
                    <tr>
                        <td colspan="4" class="section-row">{{ $section }}</td>
                    </tr>
                    @endif
                    
                    @foreach ($sectionItems as $item)
                    @php
                       // MoneyMath Logic from PR5/PR6
                       // Assumes item fields are standard decimals
                       $qty = \App\Support\MoneyMath::decimalToScaledInt($item->qty);
                       $unitPrice = \App\Support\MoneyMath::decimalToScaledInt($item->unit_price);
                       // We don't have total_price formatted in model sometimes, better to calc or use model attribute if trusted
                       // Using model attribute from existing print view: $item->total_price
                       $totalPrice = $item->total_price;
                       
                       $qtyDisplay = $item->qty + 0; 
                    @endphp
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="text-right">{{ $qtyDisplay }} {{ $item->unit }}</td>
                        <td class="text-right">{{ $formatMoney($item->unit_price) }}</td>
                        <td class="text-right">{{ $formatMoney($totalPrice) }}</td>
                    </tr>
                    @endforeach
                @endforeach
                
                {{-- Totals --}}
                <tr class="total-row">
                    <td colspan="2" rowspan="3" class="border-0 bg-white"></td>
                    <td class="text-right font-medium">{{ __('Ara Toplam') }}</td>
                    <td class="text-right">{{ $formatMoney($contract->subtotal) }} {{ $currencySymbol }}</td>
                </tr>
                @if($contract->tax_total > 0)
                <tr>
                    <td class="text-right font-medium">{{ __('KDV / Vergi') }}</td>
                    <td class="text-right">{{ $formatMoney($contract->tax_total) }} {{ $currencySymbol }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td class="text-right font-bold text-slate-900 bg-slate-100">{{ __('GENEL TOPLAM') }}</td>
                    <td class="text-right font-bold text-slate-900 bg-slate-100">{{ $formatMoney($contract->grand_total) }} {{ $currencySymbol }}</td>
                </tr>
            </tbody>
        </table>
    </div>
@endif

{{-- Body of Contract --}}
<div class="section mt-8">
    <h3 class="section-title">{{ __('Sözleşme Metni') }}</h3>
    <div class="prose max-w-none text-sm">
        {{-- rendered_body is generated from internal templates and filtered by the backend, so it is safe to output raw --}}
        {!! $contract->rendered_body !!}
    </div>
</div>

<x-doc.payment-instructions />

{{-- Signatures --}}
<div class="section break-inside-avoid">
    <h3>{{ __('Onay') }}</h3>
    
    <div class="grid grid-cols-2 gap-8 mt-8">
        <div class="border-t border-slate-300 pt-2">
            <p class="font-bold text-xs uppercase tracking-wider mb-1">{{ __('Firma Yetkilisi') }}</p>
            <p class="text-xs text-slate-500">{{ $contract->creator?->name ?? config('company.name') }}</p>
            <p class="text-xs text-slate-500">{{ now()->format('d.m.Y') }}</p>
        </div>
        <div class="border-t border-slate-300 pt-2">
            <p class="font-bold text-xs uppercase tracking-wider mb-1">{{ __('Müşteri Onayı') }}</p>
            <p class="text-xs text-slate-500 mb-6">{{ __('Ad Soyad / Kaşe / İmza') }}</p>
            <p class="text-xs text-slate-400 italic">"Bu sözleşmeyi okudum ve kabul ediyorum."</p>
        </div>
    </div>
</div>

<div class="footer">
    <p>{{ $companyFooter }}</p>
    <p>{{ $companyAddress }} · {{ $companyPhone }} · {{ $companyEmail }}</p>
</div>
