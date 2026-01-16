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
        <p class="text-lg font-semibold border p-4 bg-slate-50">{{ $workOrder->title }}</p>
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

    {{-- Progress & Updates for Print --}}
    <div class="section break-inside-avoid">
        <h3 class="section-title">İlerleme & Hakediş</h3>
        <table class="w-full border-collapse border border-slate-200 text-sm mb-4">
            <thead>
                <tr class="bg-slate-50 text-left">
                    <th class="border border-slate-200 p-2">{{ __('Hakediş Durumu') }}</th>
                    <th class="border border-slate-200 p-2">{{ __('Son Güncelleme') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="border border-slate-200 p-2">
                         @php
                            $latestProgress = $workOrder->progress->first();
                            $percent = $latestProgress ? $latestProgress->progress_percent : 0;
                        @endphp
                        %{{ $percent }}
                    </td>
                    <td class="border border-slate-200 p-2">{{ $latestProgress ? $latestProgress->created_at->format('d.m.Y H:i') : '-' }}</td>
                </tr>
            </tbody>
        </table>

        @if($workOrder->updates->isNotEmpty())
            <h4 class="font-bold text-sm mb-2 mt-4">{{ __('İlerleme Kayıtları') }}</h4>
            <table class="w-full border-collapse border border-slate-200 text-sm">
                <thead>
                    <tr class="bg-slate-50 text-left">
                        <th class="border border-slate-200 p-2 w-32">{{ __('Tarih') }}</th>
                        <th class="border border-slate-200 p-2 w-32">{{ __('Kişi') }}</th>
                        <th class="border border-slate-200 p-2">{{ __('Not') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($workOrder->updates as $update)
                    <tr>
                        <td class="border border-slate-200 p-2">{{ $update->happened_at->format('d.m.Y H:i') }}</td>
                        <td class="border border-slate-200 p-2">{{ $update->creator->name ?? '-' }}</td>
                        <td class="border border-slate-200 p-2">{{ $update->note }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Delivery / Signature Block --}}
    <div class="section break-inside-avoid mt-8">
        <div class="grid grid-cols-2 gap-8">
            <div class="border border-slate-200 p-4 h-32">
                <p class="font-bold text-xs uppercase tracking-wider mb-2 text-slate-500">{{ __('Teslim Eden (Personel)') }}</p>
                <div class="mt-8 border-b border-slate-300 w-2/3"></div>
                <p class="text-xs text-slate-400 mt-1">{{ __('İmza') }}</p>
            </div>
            <div class="border border-slate-200 p-4 h-32">
                <p class="font-bold text-xs uppercase tracking-wider mb-2 text-slate-500">{{ __('Teslim Alan (Müşteri)') }}</p>
                <div class="mt-8 border-b border-slate-300 w-2/3"></div>
                <p class="text-xs text-slate-400 mt-1">{{ __('İmza') }}</p>
                
                <div class="mt-4 flex items-center gap-2">
                    <div class="h-4 w-4 border border-slate-400 rounded"></div>
                    <span class="text-xs font-semibold">{{ __('Eksiksiz Teslim Aldım') }}</span>
                </div>
            </div>
        </div>
    </div>
@endsection
