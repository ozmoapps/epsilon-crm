<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>{{ $contract->contract_no }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1, h2, h3 { margin: 0 0 8px; }
        .section { margin-bottom: 16px; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px 8px; border: 1px solid #e5e7eb; text-align: left; }
        .totals td { font-weight: bold; }
        .signature { margin-top: 30px; }
        .signature-box { width: 45%; display: inline-block; vertical-align: top; }
        .footer { margin-top: 24px; font-size: 10px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
    @php
        $currencySymbols = config('quotes.currency_symbols', []);
        $currencySymbol = $currencySymbols[$contract->currency] ?? $contract->currency;
        $formatMoney = fn ($value) => number_format((float) $value, 2, ',', '.');
    @endphp

    <div class="section">
        <h1>Sözleşme</h1>
        <p class="muted">No: {{ $contract->contract_no }} · Tarih: {{ $contract->issued_at?->format('d.m.Y') }}</p>
    </div>

    <div class="section">
        <h2>Taraflar</h2>
        <table>
            <tr>
                <th>Alıcı</th>
                <th>Satıcı</th>
            </tr>
            <tr>
                <td>
                    <strong>{{ $contract->customer_name }}</strong><br>
                    {{ $contract->customer_company }}<br>
                    {{ $contract->customer_address }}<br>
                    {{ $contract->customer_phone }}<br>
                    {{ $contract->customer_email }}
                </td>
                <td>
                    <strong>Epsilon CRM</strong><br>
                    Teknik Servis<br>
                    {{ __('İletişim bilgileri sistemde kayıtlıdır.') }}
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>Kapsam</h2>
        <p>{{ $contract->scope_text }}</p>
    </div>

    <div class="section">
        <h2>Toplamlar</h2>
        <table>
            <tr>
                <th>Ara Toplam</th>
                <th>Vergi Toplamı</th>
                <th>Genel Toplam</th>
            </tr>
            <tr class="totals">
                <td>{{ $formatMoney($contract->subtotal) }} {{ $currencySymbol }}</td>
                <td>{{ $formatMoney($contract->tax_total) }} {{ $currencySymbol }}</td>
                <td>{{ $formatMoney($contract->grand_total) }} {{ $currencySymbol }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>Ödeme Şartları</h2>
        <p>{{ $contract->payment_terms }}</p>
    </div>

    <div class="section">
        <h2>Garanti ve Hariç Tutulanlar</h2>
        <p><strong>Garanti:</strong> {{ $contract->warranty_terms }}</p>
        <p><strong>Hariç:</strong> {{ $contract->exclusions_text }}</p>
    </div>

    <div class="section">
        <h2>Teslim Şartları</h2>
        <p>{{ $contract->delivery_terms }}</p>
    </div>

    <div class="signature">
        <div class="signature-box">
            <p>Alıcı</p>
            <p>İmza:</p>
        </div>
        <div class="signature-box" style="float: right;">
            <p>Satıcı</p>
            <p>İmza:</p>
        </div>
        <div style="clear: both;"></div>
    </div>

    <div class="footer">
        <p>{{ __('Bu belge elektronik olarak oluşturulmuştur.') }}</p>
    </div>
</body>
</html>
