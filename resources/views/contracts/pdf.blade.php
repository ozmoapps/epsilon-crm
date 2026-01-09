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
    <p class="muted">Revizyon: {{ $contract->revision_label }}</p>
    {!! $contract->rendered_body !!}
</body>
</html>
