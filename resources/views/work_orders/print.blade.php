@extends('layouts.print')

@section('title', 'İş Emri Yazdır - #' . $workOrder->id)

@section('content')
    @php
        $companyName = $companyProfile?->name ?? config('company.name');
        $companyAddress = $companyProfile?->address ?? config('company.address');
        $companyPhone = $companyProfile?->phone ?? config('company.phone');
        $companyEmail = $companyProfile?->email ?? config('company.email');
        
        $customer = $workOrder->customer;
        $vessel = $workOrder->vessel;
    @endphp

    <div class="header">
        <div>
            <h1>{{ $companyName }}</h1>
            <p class="muted">{{ $companyAddress }}</p>
        </div>
        <div class="quote-meta">
            <h1>İŞ EMRİ</h1>
            <table>
                <tr>
                    <td class="muted">No</td>
                    <td>#{{ $workOrder->id }}</td>
                </tr>
                <tr>
                    <td class="muted">Durum</td>
                    <td>{{ $workOrder->status_label }}</td>
                </tr>
                <tr>
                    <td class="muted">Oluşturma</td>
                    <td>{{ $workOrder->created_at->format('d.m.Y') }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-block">
            <h3>Müşteri</h3>
            <p>{{ $customer->name }}</p>
            <p class="muted">{{ $customer->phone }}</p>
        </div>
        <div class="info-block">
            <h3>Tekne</h3>
            <p>{{ $vessel->name }}</p>
            <p class="muted">{{ $vessel->type }} / {{ $vessel->flag }}</p>
        </div>
        <div class="info-block">
            <h3>Planlanan Başlangıç</h3>
            <p>{{ $workOrder->planned_start_at?->format('d.m.Y') ?? '-' }}</p>
        </div>
        <div class="info-block">
            <h3>Planlanan Bitiş</h3>
            <p>{{ $workOrder->planned_end_at?->format('d.m.Y') ?? '-' }}</p>
        </div>
    </div>

    <div class="section">
        <h3 class="section-title">İş Emri Başlığı</h3>
        <p class="text-lg font-semibold border p-4 bg-gray-50">{{ $workOrder->title }}</p>
    </div>

    @if($workOrder->description)
    <div class="section">
        <h3 class="section-title">Açıklama / Notlar</h3>
        <div class="border p-4 bg-white whitespace-pre-line text-sm min-h-[100px]">
            {{ $workOrder->description }}
        </div>
    </div>
    @endif

    <div class="footer">
        <p>İş emri oluşturulmuştur. Lütfen ilgili birimlere iletiniz.</p>
    </div>
@endsection
