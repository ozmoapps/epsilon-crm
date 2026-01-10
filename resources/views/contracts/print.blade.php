@extends('layouts.print')

@section('title', 'Sözleşme Yazdır - ' . $contract->contract_no)

@section('content')
    @php
        $companyName = $companyProfile?->name ?? config('company.name');
        $companyAddress = $companyProfile?->address ?? config('company.address');
        $companyPhone = $companyProfile?->phone ?? config('company.phone');
        $companyEmail = $companyProfile?->email ?? config('company.email');
        $companyFooter = $companyProfile?->footer_text ?? config('company.footer_text');
        
        $customer = $contract->salesOrder->customer;
        $vessel = $contract->salesOrder->vessel;
        $items = $contract->salesOrder->items;
        
        $currencyCode = $contract->currency;
        $currencySymbol = $contract->currency; // Simple for now
        $formatMoney = fn ($value) => number_format((float) $value, 2, ',', '.');
        
        $itemsBySection = $items->groupBy(fn ($item) => $item->section ?: 'Genel');
    @endphp

    <div class="header">
        <div>
            <h1>{{ $companyName }}</h1>
            <p class="muted">{{ $companyAddress }}</p>
            <p class="muted">{{ $companyPhone }} · {{ $companyEmail }}</p>
        </div>
        <div class="quote-meta">
            <h1>SÖZLEŞME</h1>
            <table>
                <tr>
                    <td class="muted">Sözleşme No</td>
                    <td>{{ $contract->contract_no }}</td>
                </tr>
                <tr>
                    <td class="muted">Revizyon</td>
                    <td>{{ $contract->revision_no ? 'R' . $contract->revision_no : '-' }}</td>
                </tr>
                <tr>
                    <td class="muted">Tarih</td>
                    <td>{{ $contract->created_at->format('d.m.Y') }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-block">
            <h3>Müşteri</h3>
            <p>{{ $contract->customer_name }}</p>
            <p>{{ $contract->customer_company }}</p>
            <p class="muted">{{ $contract->customer_address }}</p>
            <p class="muted">{{ $contract->customer_phone }} · {{ $contract->customer_email }}</p>
        </div>
        <div class="info-block">
            <h3>Tekne Bilgileri</h3>
            <p>{{ $vessel?->name ?? '-' }}</p>
            <p class="muted">{{ $vessel?->type ?? '-' }} / {{ $vessel?->flag ?? '-' }}</p>
        </div>
    </div>

    @if($items->isNotEmpty())
        <div class="section">
            <h3 class="section-title">Hizmet ve Ürün Detayları</h3>
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Açıklama</th>
                        <th class="text-right">Miktar</th>
                        <th class="text-right">Birim Fiyat</th>
                        <th class="text-right">Toplam</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($itemsBySection as $section => $sectionItems)
                        <tr>
                            <td colspan="4" class="font-bold bg-gray-50">{{ $section }}</td>
                        </tr>
                        @foreach ($sectionItems as $item)
                        <tr>
                            <td>{{ $item->description }}</td>
                            <td class="text-right">{{ $item->qty }} {{ $item->unit }}</td>
                            <td class="text-right">{{ $formatMoney($item->unit_price) }} {{ $currencySymbol }}</td>
                            <td class="text-right">{{ $formatMoney($item->total_price) }} {{ $currencySymbol }}</td>
                        </tr>
                        @endforeach
                    @endforeach
                    <tr>
                        <td colspan="3" class="text-right font-bold">Ara Toplam</td>
                        <td class="text-right">{{ $formatMoney($contract->subtotal) }} {{ $currencySymbol }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-right font-bold">KDV Toplam</td>
                        <td class="text-right">{{ $formatMoney($contract->tax_total) }} {{ $currencySymbol }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-right font-bold text-lg">GENEL TOPLAM</td>
                        <td class="text-right font-bold text-lg">{{ $formatMoney($contract->grand_total) }} {{ $currencySymbol }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif

    <div class="section mt-8">
        <h3 class="section-title">Sözleşme Metni</h3>
        <div class="prose max-w-none text-sm">
            {!! $contract->rendered_body !!}
        </div>
    </div>

    <div class="footer">
        <p>{{ $companyFooter }}</p>
        <p>{{ $companyAddress }} · {{ $companyPhone }} · {{ $companyEmail }}</p>
    </div>
@endsection
