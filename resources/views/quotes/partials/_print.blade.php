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
        <table>
            <tr>
                <td class="muted">{{ __('Teklif Tarihi') }}</td>
                <td>{{ $issuedAt }}</td>
            </tr>
            <tr>
                <td class="muted">{{ __('Geçerlilik') }}</td>
                <td>{{ $validityDays }} {{ __('gün') }}</td>
            </tr>
            <tr>
                <td class="muted">{{ __('Teklif No') }}</td>
                <td>{{ $quote->quote_no }}</td>
            </tr>
        </table>
    </div>
</div>

<div class="info-grid">
    <div class="info-block">
        <h3>{{ __('Müşteri') }}</h3>
        <p>{{ $quote->customer?->name ?? '-' }}</p>
        <p class="muted">{{ $quote->customer?->phone ?? '-' }}</p>
        <p class="muted">{{ $quote->customer?->email ?? '-' }}</p>
    </div>
    <div class="info-block">
        <h3>{{ __('Tekne') }}</h3>
        <p>{{ $quote->vessel?->name ?? '-' }}</p>
        <p class="muted">{{ $quote->customer?->address ?? '-' }}</p>
    </div>
    <div class="info-block">
        <h3>{{ __('İletişim & Lokasyon') }}</h3>
        <p>{{ $quote->contact_name ?: '-' }}</p>
        <p class="muted">{{ $quote->contact_phone ?: '-' }}</p>
        <p class="muted">{{ $quote->location ?: '-' }}</p>
    </div>
</div>

<div class="section">
    <p class="muted">{{ __('Ödeme') }}: {{ $quote->payment_terms ?: '-' }}</p>
</div>

<div class="section">
    <table class="doc-table">
        <thead>
            <tr>
                <th>{{ __('İŞİN KONUSU') }}</th>
                <th class="text-right">{{ __('TUTAR') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($itemsBySection as $section => $items)
                <tr>
                    <td colspan="2" class="section-row">{{ $section }}</td>
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
                    @endphp
                    <tr>
                        <td>
                            <div>{{ $item->description }}</div>
                            <div class="muted">{{ $item->qty }} {{ $item->unit }} · {{ $formatMoney($item->unit_price) }} {{ $currencySymbol }}</div>
                        </td>
                        <td class="text-right">{{ $format($line['total_cents']) }} {{ $currencySymbol }}</td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="2" class="muted">{{ __('Kalem bulunamadı.') }}</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td>{{ __('GENEL TOPLAM') }}</td>
                <td class="text-right">{{ $formatMoney($quote->grand_total) }} {{ $currencySymbol }}</td>
            </tr>
        </tbody>
    </table>
</div>

<x-doc.payment-instructions />

<div class="section">
    <h3>{{ __('Şartlar') }}</h3>
    <p class="muted">{{ __('Bu form') }} {{ $validityDays }} {{ __('gün geçerlidir.') }}</p>
    <ol class="terms">
        <li>{{ __('Fiyatlar belirtilen para birimindedir ve teklif tarihindeki koşullara göre hazırlanmıştır.') }}</li>
        <li>{{ __('Ödeme koşulları teklif üzerindeki şartlara göre uygulanacaktır.') }}</li>
        <li>{{ __('İş kapsamı dışında kalan talepler ayrıca fiyatlandırılır.') }}</li>
        <li>{{ __('Malzeme ve kur değişimleri fiyatlara yansıtılabilir.') }}</li>
        <li>{{ __('İş programı müşteri onayı sonrası netleşir.') }}</li>
        <li>{{ __('Teklifte belirtilmeyen işler kapsam dışıdır.') }}</li>
        <li>{{ __('Teklif yazılı onay ile geçerlilik kazanır.') }}</li>
    </ol>
</div>

<div class="footer">
    <p>{{ $companyFooter }}</p>
    <p>{{ $companyAddress }} · {{ $companyPhone }} · {{ $companyEmail }}</p>
</div>
