@php
    $currencyCode = $salesOrder->currency;
    // Attempt lookup for symbol
    $currencySymbol = \App\Models\Currency::where('code', $currencyCode)->value('symbol') ?? $currencyCode;
    $formatMoney = fn ($value) => \App\Support\MoneyMath::formatTR($value);
    
    // Group items by section (SalesOrder items have 'section' column)
    $itemsBySection = $salesOrder->items
        ->groupBy(fn ($item) => $item->section ?: 'Genel');
        
    $orderDate = $salesOrder->order_date?->format('d.m.Y')
        ?? $salesOrder->created_at?->format('d.m.Y')
        ?? now()->format('d.m.Y');
        
    $companyName = $companyProfile?->name ?? config('company.name');
    $companyAddress = $companyProfile?->address ?? config('company.address');
    $companyPhone = $companyProfile?->phone ?? config('company.phone');
    $companyEmail = $companyProfile?->email ?? config('company.email');
    $companyFooter = $companyProfile?->footer_text ?? config('company.footer_text');
@endphp

<div class="header">
    <div>
        <h1>{{ $companyName }}</h1>
        <p class="muted">{{ $companyAddress }}</p>
        <p class="muted">{{ $companyPhone }} · {{ $companyEmail }}</p>
    </div>
    <div class="quote-meta">
        <h1>{{ __('SATIŞ SİPARİŞİ') }}</h1>
        <table>
            <tr>
                <td class="muted">{{ __('Sipariş No') }}</td>
                <td>{{ $salesOrder->order_no }}</td>
            </tr>
            <tr>
                <td class="muted">{{ __('Tarih') }}</td>
                <td>{{ $orderDate }}</td>
            </tr>
            <tr>
                <td class="muted">{{ __('Durum') }}</td>
                <td>{{ $salesOrder->status_label }}</td>
            </tr>
            @if($salesOrder->quote)
            <tr>
                <td class="muted">{{ __('Teklif Ref') }}</td>
                <td>{{ $salesOrder->quote->quote_no }}</td>
            </tr>
            @endif
        </table>
    </div>
</div>

<div class="info-grid">
    <div class="info-block">
        <h3>{{ __('Müşteri') }}</h3>
        <p><strong>{{ $salesOrder->customer?->name ?? '-' }}</strong></p>
        <p class="muted">{{ $salesOrder->customer?->phone ?? '-' }}</p>
        <p class="muted">{{ $salesOrder->customer?->email ?? '-' }}</p>
        <p class="muted">{{ $salesOrder->customer?->address ?? '' }}</p>
    </div>
    <div class="info-block">
        <h3>{{ __('Tekne Bilgileri') }}</h3>
        <p><strong>{{ $salesOrder->vessel?->name ?? '-' }}</strong></p>
        <p class="muted">{{ $salesOrder->vessel?->type ?? '-' }} / {{ $salesOrder->vessel?->flag ?? '-' }}</p>
        
        <!-- Assuming we might want contact info if available on model, but standard SO doesn't always have it on root like Quote -->
    </div>
</div>

@if($salesOrder->title)
<div class="section">
    <h3 class="section-title">{{ __('Konu') }}</h3>
    <p>{{ $salesOrder->title }}</p>
</div>
@endif

<div class="section">
    <table class="doc-table">
        <thead>
            <tr>
                <th style="width: 40%">{{ __('AÇIKLAMA') }}</th>
                <th class="text-right" style="width: 10%">{{ __('MİKTAR') }}</th>
                <th style="width: 10%">{{ __('BİRİM') }}</th>
                <th class="text-right" style="width: 20%">{{ __('FİYAT') }}</th>
                <th class="text-right" style="width: 20%">{{ __('ARA TOPLAM') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($itemsBySection as $section => $items)
                <tr>
                    <td colspan="5" class="section-row">{{ $section }}</td>
                </tr>
                @foreach ($items as $item)
                    @php
                        // MoneyMath Standardization
                        $qty = \App\Support\MoneyMath::decimalToScaledInt($item->qty);
                        $unitPrice = \App\Support\MoneyMath::decimalToScaledInt($item->unit_price);
                        $discountAmount = \App\Support\MoneyMath::decimalToScaledInt($item->discount_amount ?? 0);
                        $vatBp = \App\Support\MoneyMath::percentToBasisPoints($item->vat_rate ?? 0);

                        $line = \App\Support\MoneyMath::calculateLineCents($qty, $unitPrice, $discountAmount, $vatBp);
                        $format = fn($cents) => \App\Support\MoneyMath::formatTR($cents / 100);
                        
                        $qtyDisplay = $item->qty + 0;
                    @endphp
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="text-right">{{ $qtyDisplay }}</td>
                        <td>{{ $item->unit }}</td>
                        <td class="text-right">{{ $formatMoney($item->unit_price) }}</td>
                        <td class="text-right">{{ $format($line['total_cents']) }}</td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="5" class="muted text-center py-4">{{ __('Herhangi bir kalem bulunamadı.') }}</td>
                </tr>
            @endforelse
            
            {{-- Totals --}}
            <tr class="total-row">
                <td colspan="3" rowspan="3" class="border-0 bg-white"></td>
                <td class="text-right font-medium">{{ __('Ara Toplam') }}</td>
                <td class="text-right">{{ $formatMoney($salesOrder->sub_total) }} {{ $currencySymbol }}</td>
            </tr>
            @if($salesOrder->vat_total > 0)
            <tr>
                <td class="text-right font-medium">{{ __('KDV / Vergi') }}</td>
                <td class="text-right">{{ $formatMoney($salesOrder->vat_total) }} {{ $currencySymbol }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td class="text-right font-bold text-slate-900 bg-slate-100">{{ __('GENEL TOPLAM') }}</td>
                <td class="text-right font-bold text-slate-900 bg-slate-100">{{ $formatMoney($salesOrder->grand_total) }} {{ $currencySymbol }}</td>
            </tr>
        </tbody>
    </table>
</div>

<x-doc.payment-instructions />

<div class="section break-inside-avoid">
    <h3>{{ __('Onay') }}</h3>
    
    <div class="grid grid-cols-2 gap-8 mt-8">
        <div class="border-t border-slate-300 pt-2">
            <p class="font-bold text-xs uppercase tracking-wider mb-1">{{ __('Firma Yetkilisi') }}</p>
            <p class="text-xs text-slate-500">{{ $salesOrder->creator->name ?? config('company.name') }}</p>
            <p class="text-xs text-slate-500">{{ $orderDate }}</p>
        </div>
        <div class="border-t border-slate-300 pt-2">
            <p class="font-bold text-xs uppercase tracking-wider mb-1">{{ __('Müşteri Onayı') }}</p>
            <p class="text-xs text-slate-500 mb-6">{{ __('Ad Soyad / Kaşe / İmza') }}</p>
            <p class="text-xs text-slate-400 italic">"Siparişi onaylıyorum."</p>
        </div>
    </div>
</div>

<div class="section mt-8">
    <ol class="terms">
        <li>{{ __('Fiyatlar belirtilen para birimindedir.') }}</li>
        <li>{{ __('Ödeme koşulları: ') }} {{ $salesOrder->payment_terms ?: __('Belirtilmemiş') }}</li>
        @if($salesOrder->warranty_text)
        <li>{{ __('Garanti: ') }} {{ $salesOrder->warranty_text }}</li>
        @endif
        <li>{{ __('Sipariş onayı sözleşme niteliğindedir.') }}</li>
    </ol>
</div>

<div class="footer">
    <p>{{ $companyFooter }}</p>
    <p>{{ $companyAddress }} · {{ $companyPhone }} · {{ $companyEmail }}</p>
</div>
