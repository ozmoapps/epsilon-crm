<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>{{ $quote->quote_no }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1, h2, h3 { margin: 0 0 6px; }
        p { margin: 0 0 4px; }
        .muted { color: #6b7280; }
        .header { display: flex; justify-content: space-between; gap: 16px; margin-bottom: 16px; }
        .quote-meta table { width: 100%; border-collapse: collapse; font-size: 11px; }
        .quote-meta td { padding: 2px 0; }
        .info-grid { display: flex; gap: 16px; margin-bottom: 16px; }
        .info-block { flex: 1; border: 1px solid #e5e7eb; padding: 10px; border-radius: 6px; }
        .section { margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; font-size: 11px; letter-spacing: 0.03em; }
        .text-right { text-align: right; }
        .section-row { background: #f9fafb; font-weight: 600; }
        .total-row td { font-weight: bold; background: #f3f4f6; }
        .payment-list { margin: 6px 0 0; padding-left: 16px; }
        .payment-list li { margin-bottom: 4px; }
        .terms { margin: 6px 0 0; padding-left: 18px; }
        .footer { margin-top: 24px; font-size: 10px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
    @include('quotes.partials._print', ['quote' => $quote, 'companyProfile' => $companyProfile, 'bankAccounts' => $bankAccounts])
</body>
</html>
