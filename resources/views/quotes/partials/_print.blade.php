@php
    $currencyCode = $quote->currencyRelation?->code ?? $quote->currency;
    $currencySymbol = $quote->currencyRelation?->symbol ?? $currencyCode;
    $formatMoney = fn ($value) => \App\Support\MoneyMath::formatTR($value);
    $itemsBySection = $quote->items
        ->where('is_optional', false)
        ->groupBy(fn ($item) => $item->section ?: 'Genel');
    $validityDays = $quote->validity_days ?? 5;
    $issuedAt = $quote->issued_at?->format('d.m.Y')
        ?? $quote->created_at?->format('d.m.Y')
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
        <h1>{{ __('TEKLİF') }}</h1>
        <table>
            <tr>
                <td class="muted">{{ __('Teklif No') }}</td>
                <td>{{ $quote->quote_no }}</td>
            </tr>
            <tr>
                <td class="muted">{{ __('Tarih') }}</td>
                <td>{{ $issuedAt }}</td>
            </tr>
            <tr>
                <td class="muted">{{ __('Geçerlilik') }}</td>
                <td>{{ $validityDays }} {{ __('gün') }}</td>
            </tr>
            <tr>
                <td class="muted">{{ __('Durum') }}</td>
                <td>{{ $quote->status_label }}</td>
            </tr>
        </table>
    </div>
</div>

<div class="info-grid">
    <div class="info-block">
        <h3>{{ __('Müşteri') }}</h3>
        <p><strong>{{ $quote->customer?->name ?? '-' }}</strong></p>
        <p class="muted">{{ $quote->customer?->phone ?? '-' }}</p>
        <p class="muted">{{ $quote->customer?->email ?? '-' }}</p>
        <p class="muted">{{ $quote->customer?->address ?? '' }}</p>
    </div>
    <div class="info-block">
        <h3>{{ __('Tekne Bilgileri') }}</h3>
        <p><strong>{{ $quote->vessel?->name ?? '-' }}</strong></p>
        <p class="muted">{{ $quote->vessel?->type ?? '-' }} / {{ $quote->vessel?->flag ?? '-' }}</p>
        @if($quote->contact_name || $quote->location)
        <div class="mt-2 pt-2 border-t border-slate-100">
            <p class="muted">{{ $quote->contact_name }}</p>
            <p class="muted">{{ $quote->location }}</p>
        </div>
        @endif
    </div>
</div>

@if($quote->title)
<div class="section">
    <h3 class="section-title">{{ __('Konu') }}</h3>
    <p>{{ $quote->title }}</p>
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
                        
                        $qtyDisplay = $item->qty + 0; // Removing trailing zeros
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
                <td class="text-right">{{ $formatMoney($quote->sub_total) }} {{ $currencySymbol }}</td>
            </tr>
            @if($quote->vat_total > 0)
            <tr>
                <td class="text-right font-medium">{{ __('KDV / Vergi') }}</td>
                <td class="text-right">{{ $formatMoney($quote->vat_total) }} {{ $currencySymbol }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td class="text-right font-bold text-slate-900 bg-slate-100">{{ __('GENEL TOPLAM') }}</td>
                <td class="text-right font-bold text-slate-900 bg-slate-100">{{ $formatMoney($quote->grand_total) }} {{ $currencySymbol }}</td>
            </tr>
        </tbody>
    </table>
</div>

<x-doc.payment-instructions />

<div class="section break-inside-avoid">
    <h3>{{ __('Şartlar & Onay') }}</h3>
    <p class="muted text-xs mb-4">{{ __('Bu teklif') }} <strong>{{ $validityDays }} {{ __('gün') }}</strong> {{ __('boyunca geçerlidir.') }}</p>
    
    <div class="grid grid-cols-2 gap-8 mt-8">
        <div class="border-t border-slate-300 pt-2">
            <p class="font-bold text-xs uppercase tracking-wider mb-1">{{ __('Firma Yetkilisi') }}</p>
            <p class="text-xs text-slate-500">{{ $quote->creator->name ?? config('company.name') }}</p>
            <p class="text-xs text-slate-500">{{ $issuedAt }}</p>
        </div>
        <div class="border-t border-slate-300 pt-2">
            <p class="font-bold text-xs uppercase tracking-wider mb-1">{{ __('Müşteri Onayı') }}</p>
            <p class="text-xs text-slate-500 mb-6">{{ __('Ad Soyad / Kaşe / İmza') }}</p>
            <p class="text-xs text-slate-400 italic">"Bu teklifteki şartları ve fiyatı onaylıyorum."</p>
        </div>
    </div>
</div>

<div class="section mt-8">
    <ol class="terms">
        <li>{{ __('Fiyatlar belirtilen para birimindedir ve teklif tarihindeki koşullara göre hazırlanmıştır.') }}</li>
        <li>{{ __('Ödeme koşulları teklif üzerindeki şartlara göre uygulanacaktır.') }}</li>
        <li>{{ __('İş kapsamı dışında kalan talepler ayrıca fiyatlandırılır.') }}</li>
        <li>{{ __('Malzeme ve kur değişimleri fiyatlara yansıtılabilir.') }}</li>
        <li>{{ __('Onaylanan teklif sözleşme niteliğindedir.') }}</li>
    </ol>
</div>

<div class="footer">
    <p>{{ $companyFooter }}</p>
    <p>{{ $companyAddress }} · {{ $companyPhone }} · {{ $companyEmail }}</p>
</div>
