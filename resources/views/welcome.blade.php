{{-- resources/views/welcome.blade.php --}}
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Epsilon CRM') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
    <div class="min-h-screen">
        {{-- Background decoration (Calm UI / slate) --}}
        <div class="pointer-events-none fixed inset-0 -z-10">
            <div class="absolute inset-0 bg-gradient-to-b from-slate-50 via-white to-slate-50"></div>

            <div class="absolute -top-44 left-1/2 h-[540px] w-[540px] -translate-x-1/2 rounded-full bg-slate-200/35 blur-3xl"></div>
            <div class="absolute -bottom-52 left-1/4 h-[560px] w-[560px] rounded-full bg-slate-200/30 blur-3xl"></div>

            {{-- Subtle grid --}}
            <svg class="absolute inset-0 h-full w-full opacity-[0.06]" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="grid" width="44" height="44" patternUnits="userSpaceOnUse">
                        <path d="M 44 0 L 0 0 0 44" fill="none" stroke="currentColor" stroke-width="1" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid)" class="text-slate-900" />
            </svg>
        </div>

        {{-- Top Bar --}}
        <header class="sticky top-0 z-40 border-b border-slate-200/70 bg-white/80 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
                <div class="flex items-center gap-3">
                    <div class="grid h-9 w-9 place-items-center rounded-xl bg-slate-900 text-white shadow-sm">
                        <span class="text-sm font-black">ε</span>
                    </div>
                    <div class="leading-tight">
                        <div class="text-sm font-semibold text-slate-900">{{ config('app.name', 'Epsilon CRM') }}</div>
                        <div class="text-xs text-slate-500">{{ __('Tekne odaklı operasyon yönetimi') }}</div>
                    </div>
                </div>

                <nav class="hidden items-center gap-6 text-sm text-slate-600 md:flex">
                    <a href="#value" class="hover:text-slate-900">{{ __('Değer') }}</a>
                    <a href="#who" class="hover:text-slate-900">{{ __('Kimler için') }}</a>
                    <a href="#flow" class="hover:text-slate-900">{{ __('Akış') }}</a>
                    <a href="#features" class="hover:text-slate-900">{{ __('Özellikler') }}</a>
                </nav>

                <div class="flex items-center gap-2">
                    @if (Route::has('login'))
                        @auth
                            <x-ui.button href="{{ url('/dashboard') }}" size="sm">
                                {{ __('Panele Git') }}
                            </x-ui.button>
                        @else
                            <x-ui.button href="{{ route('login') }}" variant="secondary" size="sm">
                                {{ __('Giriş Yap') }}
                            </x-ui.button>

                            @if (Route::has('register'))
                                <x-ui.button href="{{ route('register') }}" size="sm">
                                    {{ __('Kayıt Ol') }}
                                </x-ui.button>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </header>

        <main>
            {{-- Hero --}}
            <section class="mx-auto max-w-6xl px-4 pt-10 sm:px-6 sm:pt-16">
                <div class="grid gap-10 lg:grid-cols-2 lg:items-center">
                    {{-- LEFT --}}
                    <div class="space-y-7">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-ui.badge variant="neutral">{{ __('Calm UI') }}</x-ui.badge>
                            <x-ui.badge variant="info">{{ __('Tekne/Proje Bazlı') }}</x-ui.badge>
                            <x-ui.badge variant="success">{{ __('Doğrulanabilir Akışlar') }}</x-ui.badge>
                        </div>

                        <div class="space-y-4">
                            <h1 class="text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">
                                {{ __('Tekne operasyonunu tek panelden yönet.') }}
                                <span class="text-slate-700">{{ __('Daha net takip, daha hızlı karar.') }}</span>
                            </h1>

                            <p class="max-w-xl text-base leading-relaxed text-slate-600">
                                {{ __('Epsilon-CRM; müşteri & tekne ilişkisini merkez alır. Tekliften faturaya, stoktan tahsilata kadar tüm adımları aynı ekranda, aynı dilde ve izlenebilir şekilde birleştirir.') }}
                            </p>
                        </div>

                        {{-- Value bullets --}}
                        <div id="value" class="grid gap-3 sm:grid-cols-2">
                            @php
                                $bullets = [
                                    ['t' => __('Daha az karmaşa'), 'd' => __('Her iş tekne/proje altında toplanır; “hangi iş neredeydi?” kaybolmaz.')],
                                    ['t' => __('Daha güvenli finans'), 'd' => __('Fatura/Tahsilat/Cari hareketleri aynı mantıkla ilerler; sürpriz azalarak kontrol artar.')],
                                    ['t' => __('Daha hızlı operasyon'), 'd' => __('Arama, filtre ve hızlı aksiyonlar ile günlük iş akışı kısalır.')],
                                    ['t' => __('Daha iyi izlenebilirlik'), 'd' => __('Kritik işlemler doğrulama betikleriyle kontrol edilebilir.')],
                                ];
                            @endphp

                            @foreach ($bullets as $b)
                                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                                    <div class="flex items-start gap-3">
                                        <div class="mt-0.5 grid h-8 w-8 place-items-center rounded-2xl bg-slate-100 text-slate-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-semibold text-slate-900">{{ $b['t'] }}</div>
                                            <div class="mt-1 text-sm leading-relaxed text-slate-600">{{ $b['d'] }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- CTA --}}
                        <div class="flex flex-wrap items-center gap-3 pt-1">
                            @if (Route::has('login'))
                                @auth
                                    <x-ui.button href="{{ url('/dashboard') }}">
                                        {{ __('Hemen Başla') }}
                                    </x-ui.button>
                                @else
                                    <x-ui.button href="{{ route('login') }}">
                                        {{ __('Giriş Yap') }}
                                    </x-ui.button>

                                    @if (Route::has('register'))
                                        <x-ui.button href="{{ route('register') }}" variant="secondary">
                                            {{ __('Hesap Oluştur') }}
                                        </x-ui.button>
                                    @endif
                                @endauth
                            @endif

                            <a href="#features" class="inline-flex items-center rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                                {{ __('Özelliklere göz at') }}
                                <span class="ml-1">→</span>
                            </a>
                        </div>

                        {{-- Micro metrics --}}
                        <div class="grid grid-cols-2 gap-4 pt-2 sm:grid-cols-3">
                            <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                                <div class="text-xs text-slate-500">{{ __('Odak') }}</div>
                                <div class="mt-1 text-sm font-semibold text-slate-900">{{ __('Tekne/Proje') }}</div>
                            </div>
                            <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                                <div class="text-xs text-slate-500">{{ __('Finans') }}</div>
                                <div class="mt-1 text-sm font-semibold text-slate-900">{{ __('Ledger / Cari') }}</div>
                            </div>
                            <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                                <div class="text-xs text-slate-500">{{ __('Yaklaşım') }}</div>
                                <div class="mt-1 text-sm font-semibold text-slate-900">{{ __('Calm UI') }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- RIGHT: Premium Preview (dashboard-like) --}}
                    @php
                        $fallback = Route::has('login') ? route('login') : '#';

                        $linkInvoices  = Route::has('invoices.index') ? route('invoices.index') : $fallback;
                        $linkPayments  = Route::has('payments.index') ? route('payments.index') : $fallback;
                        $linkStock     = Route::has('stock-movements.index') ? route('stock-movements.index') : $fallback;
                        $linkCustomers = Route::has('customers.index') ? route('customers.index') : $fallback;
                        $linkDashboard = url('/dashboard');

                        $recent = [
                            [
                                'type' => __('Fatura'),
                                'title' => 'INV-2026-0012',
                                'meta' => __('Aegean Private Owner'),
                                'amount' => '€ 4.850',
                                'badge' => 'success',
                                'badgeText' => __('Kesildi'),
                                'href' => $linkInvoices,
                                'icon' => 'document',
                                'when' => __('12 dk önce'),
                                'vessel' => 'LYRA',
                            ],
                            [
                                'type' => __('Tahsilat'),
                                'title' => __('Avans'),
                                'meta' => __('LYRA / EUR'),
                                'amount' => '€ 2.000',
                                'badge' => 'info',
                                'badgeText' => __('Alındı'),
                                'href' => $linkPayments,
                                'icon' => 'cash',
                                'when' => __('41 dk önce'),
                                'vessel' => 'LYRA',
                            ],
                            [
                                'type' => __('Stok'),
                                'title' => __('Çıkış'),
                                'meta' => __('Interfill 830'),
                                'amount' => __('- 4 Adet'),
                                'badge' => 'neutral',
                                'badgeText' => __('Hareket'),
                                'href' => $linkStock,
                                'icon' => 'box',
                                'when' => __('1 s önce'),
                                'vessel' => 'Small World',
                            ],
                        ];

                        // Mini tarih pill: date_label + date_sub
                        $due = [
                            ['t' => __('Açık Faturalar'), 'd' => __('7 gün içinde'), 'v' => '3', 'b' => 'info',   'href' => $linkInvoices, 'date_label' => __('22 Oca'), 'date_sub' => __('Son gün')],
                            ['t' => __('Overdue'),        'd' => __('Vadesi geçti'), 'v' => '1', 'b' => 'danger', 'href' => $linkInvoices, 'date_label' => __('10 Oca'), 'date_sub' => __('Gecikti')],
                            ['t' => __('Teklif Takibi'),  'd' => __('Onay bekliyor'), 'v' => '2', 'b' => 'neutral', 'href' => $fallback, 'date_label' => __('Bugün'), 'date_sub' => __('Takip')],
                        ];

                        $spark = [
                            'today'  => 'M2,18 C10,6 16,22 24,12 C32,2 40,14 48,10 C56,6 62,14 70,9',
                            'open'   => 'M2,14 C10,20 18,6 26,10 C34,14 40,22 48,16 C56,10 62,12 70,7',
                            'stock'  => 'M2,16 C10,10 18,12 26,8 C34,4 40,10 48,9 C56,8 62,12 70,6',
                        ];
                    @endphp

                    <div class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                        {{-- Header --}}
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-sm font-semibold text-slate-900">{{ __('Panel Önizleme') }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ __('Günlük operasyonu tek ekranda toparlama örneği') }}</div>
                            </div>
                            <x-ui.badge variant="neutral">{{ __('Örnek') }}</x-ui.badge>
                        </div>

                        {{-- KPI Row + mini sparklines --}}
                        <div class="mt-5 grid grid-cols-3 gap-3">
                            <a href="{{ $linkDashboard }}" class="group rounded-3xl border border-slate-200 bg-white p-4 transition hover:bg-slate-50/60">
                                <div class="flex items-center justify-between">
                                    <div class="text-xs text-slate-500">{{ __('Bugün') }}</div>
                                    <span class="h-2 w-2 rounded-full bg-slate-900"></span>
                                </div>
                                <div class="mt-2 text-lg font-semibold text-slate-900">8</div>
                                <div class="mt-1 text-xs text-slate-500">{{ __('aktif iş') }}</div>

                                <div class="mt-3">
                                    <svg viewBox="0 0 72 24" class="h-6 w-full">
                                        <path d="{{ $spark['today'] }}" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-300 group-hover:text-slate-400 transition" />
                                    </svg>
                                </div>
                            </a>

                            <a href="{{ $linkInvoices }}" class="group rounded-3xl border border-slate-200 bg-white p-4 transition hover:bg-slate-50/60">
                                <div class="flex items-center justify-between">
                                    <div class="text-xs text-slate-500">{{ __('Açık') }}</div>
                                    <span class="h-2 w-2 rounded-full bg-slate-900"></span>
                                </div>
                                <div class="mt-2 text-lg font-semibold text-slate-900">12</div>
                                <div class="mt-1 text-xs text-slate-500">{{ __('tahsilat') }}</div>

                                <div class="mt-3">
                                    <svg viewBox="0 0 72 24" class="h-6 w-full">
                                        <path d="{{ $spark['open'] }}" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-300 group-hover:text-slate-400 transition" />
                                    </svg>
                                </div>
                            </a>

                            <a href="{{ $linkStock }}" class="group rounded-3xl border border-slate-200 bg-white p-4 transition hover:bg-slate-50/60">
                                <div class="flex items-center justify-between">
                                    <div class="text-xs text-slate-500">{{ __('Stok') }}</div>
                                    <span class="h-2 w-2 rounded-full bg-slate-900"></span>
                                </div>
                                <div class="mt-2 text-lg font-semibold text-slate-900">%98</div>
                                <div class="mt-1 text-xs text-slate-500">{{ __('tutarlılık') }}</div>

                                <div class="mt-3">
                                    <svg viewBox="0 0 72 24" class="h-6 w-full">
                                        <path d="{{ $spark['stock'] }}" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-300 group-hover:text-slate-400 transition" />
                                    </svg>
                                </div>
                            </a>
                        </div>

                        {{-- Due / Alerts --}}
                        <div class="mt-4 rounded-3xl bg-slate-50 p-4">
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-semibold text-slate-900">{{ __('Yaklaşan Vadeler') }}</div>
                                <div class="flex items-center gap-2">
                                    <x-ui.badge variant="neutral">{{ __('Özet') }}</x-ui.badge>
                                    <a href="{{ $linkInvoices }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">
                                        {{ __('Tümünü gör') }} →
                                    </a>
                                </div>
                            </div>

                            <div class="mt-3 grid gap-2">
                                @foreach ($due as $d)
                                    <a href="{{ $d['href'] }}" class="group flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-3 py-2 transition hover:bg-slate-50">
                                        <div class="flex items-center gap-3 min-w-0">
                                            {{-- Mini date pill --}}
                                            <div class="shrink-0 rounded-2xl border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-center">
                                                <div class="text-xs font-semibold text-slate-900 leading-none">{{ $d['date_label'] }}</div>
                                                <div class="mt-1 text-[10px] text-slate-500 leading-none">{{ $d['date_sub'] }}</div>
                                            </div>

                                            <div class="min-w-0">
                                                <div class="text-sm font-semibold text-slate-900 truncate group-hover:underline">{{ $d['t'] }}</div>
                                                <div class="text-xs text-slate-500">{{ $d['d'] }}</div>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <x-ui.badge variant="{{ $d['b'] }}">{{ $d['v'] }}</x-ui.badge>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-300 group-hover:text-slate-400 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </div>
                                    </a>
                                @endforeach
                            </div>

                            <div class="mt-3 text-xs text-slate-500">
                                {{ __('Vade yaklaşan işlerinizi tek listeden takip edin; gecikmeleri erken yakalayın.') }}
                            </div>
                        </div>

                        {{-- Recent activity mini table --}}
                        <div class="mt-4 rounded-3xl border border-slate-200 bg-white p-4">
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-semibold text-slate-900">{{ __('Son Hareketler') }}</div>
                                <div class="flex items-center gap-2">
                                    <x-ui.badge variant="neutral">{{ __('Log') }}</x-ui.badge>
                                    <a href="{{ $linkDashboard }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">
                                        {{ __('Panele git') }} →
                                    </a>
                                </div>
                            </div>

                            <div class="mt-3 overflow-hidden rounded-2xl border border-slate-200">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50">
                                        <tr class="text-left text-xs font-semibold text-slate-500">
                                            <th class="px-3 py-2 w-10"></th>
                                            <th class="px-3 py-2">{{ __('Tip') }}</th>
                                            <th class="px-3 py-2">{{ __('Kayıt') }}</th>
                                            <th class="px-3 py-2 hidden sm:table-cell">{{ __('Bağlam') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Değer') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @foreach ($recent as $r)
                                            <tr class="hover:bg-slate-50/60">
                                                <td class="px-3 py-2 align-middle">
                                                    <a href="{{ $r['href'] }}" class="group inline-flex h-9 w-9 items-center justify-center rounded-2xl border border-slate-200 bg-white transition hover:bg-slate-50">
                                                        @if ($r['icon'] === 'document')
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-slate-500 group-hover:text-slate-700 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6M9 16h6M14 2H7a2 2 0 00-2 2v16a2 2 0 002 2h10a2 2 0 002-2V8l-5-6z" />
                                                            </svg>
                                                        @elseif ($r['icon'] === 'cash')
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-slate-500 group-hover:text-slate-700 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2M21 12h-8m8 0l-3-3m3 3l-3 3" />
                                                            </svg>
                                                        @else
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-slate-500 group-hover:text-slate-700 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4m16 0l-4 4m4-4l-4-4" />
                                                            </svg>
                                                        @endif
                                                    </a>
                                                </td>

                                                <td class="px-3 py-2 align-middle">
                                                    <x-ui.badge variant="{{ $r['badge'] }}">{{ $r['type'] }}</x-ui.badge>
                                                </td>

                                                <td class="px-3 py-2 align-middle">
                                                    <a href="{{ $r['href'] }}" class="group block">
                                                        <div class="font-semibold text-slate-900 group-hover:underline">{{ $r['title'] }}</div>

                                                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500 sm:hidden">
                                                            <span class="truncate">{{ $r['meta'] }}</span>
                                                            <span class="text-slate-300">•</span>
                                                            <span>{{ $r['when'] }}</span>
                                                            <x-ui.badge variant="neutral">{{ $r['vessel'] }}</x-ui.badge>
                                                        </div>

                                                        <div class="mt-1 hidden flex-wrap items-center gap-2 text-xs text-slate-500 sm:flex">
                                                            <span>{{ $r['when'] }}</span>
                                                            <span class="text-slate-300">•</span>
                                                            <x-ui.badge variant="neutral">{{ $r['vessel'] }}</x-ui.badge>
                                                        </div>
                                                    </a>
                                                </td>

                                                <td class="px-3 py-2 hidden sm:table-cell align-middle text-slate-600">
                                                    <a href="{{ $r['href'] }}" class="hover:underline">
                                                        {{ $r['meta'] }}
                                                    </a>
                                                </td>

                                                <td class="px-3 py-2 align-middle text-right">
                                                    <a href="{{ $r['href'] }}" class="group inline-flex flex-col items-end">
                                                        <div class="font-semibold text-slate-900 group-hover:underline">{{ $r['amount'] }}</div>
                                                        <div class="text-xs text-slate-500">{{ $r['badgeText'] }}</div>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3 text-xs text-slate-500">
                                {{ __('Fatura, tahsilat ve stok hareketlerini tek kronolojide görün. “Nerede kalmıştık?” sorusu azalır.') }}
                            </div>
                        </div>

                        {{-- Quick actions --}}
                        <div class="mt-4 rounded-3xl bg-slate-50 p-4">
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-semibold text-slate-900">{{ __('Hızlı Aksiyon') }}</div>
                                <x-ui.badge variant="info">{{ __('Hız') }}</x-ui.badge>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <a href="{{ $linkCustomers }}" class="rounded-2xl border border-slate-200 bg-white p-3 transition hover:bg-slate-50">
                                    <div class="text-xs text-slate-500">{{ __('Arama') }}</div>
                                    <div class="mt-1 text-sm font-semibold text-slate-900">{{ __('Müşteri / Tekne') }}</div>
                                </a>

                                <a href="{{ $linkInvoices }}" class="rounded-2xl border border-slate-200 bg-white p-3 transition hover:bg-slate-50">
                                    <div class="text-xs text-slate-500">{{ __('Liste') }}</div>
                                    <div class="mt-1 text-sm font-semibold text-slate-900">{{ __('Açık faturalar') }}</div>
                                </a>

                                <a href="{{ $linkPayments }}" class="rounded-2xl border border-slate-200 bg-white p-3 transition hover:bg-slate-50">
                                    <div class="text-xs text-slate-500">{{ __('İşlem') }}</div>
                                    <div class="mt-1 text-sm font-semibold text-slate-900">{{ __('Tahsilat gir') }}</div>
                                </a>

                                <a href="{{ $linkInvoices }}" class="rounded-2xl border border-slate-200 bg-white p-3 transition hover:bg-slate-50">
                                    <div class="text-xs text-slate-500">{{ __('İşlem') }}</div>
                                    <div class="mt-1 text-sm font-semibold text-slate-900">{{ __('Fatura kes') }}</div>
                                </a>
                            </div>

                            <div class="mt-3 text-xs text-slate-500">
                                {{ __('Operasyonu hızlandıran şey; doğru ekran + doğru alışkanlık. Aynı arama/filtre mantığı her yerde.') }}
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Who is it for --}}
            <section id="who" class="mx-auto max-w-6xl px-4 pt-12 sm:px-6">
                <div class="flex items-end justify-between gap-6">
                    <div>
                        <h2 class="text-2xl font-semibold tracking-tight text-slate-900">{{ __('Kimler için?') }}</h2>
                        <p class="mt-2 text-sm text-slate-600">{{ __('Tekne işleriyle uğraşan ekiplerin günlük ihtiyacına göre tasarlandı.') }}</p>
                    </div>
                </div>

                <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    @php
                        $who = [
                            ['t' => __('Yat sahibi / Manager'), 'd' => __('İşlerin durumu, bütçe ve tahsilat netliği.')],
                            ['t' => __('Kaptan / Operasyon'), 'd' => __('Hızlı erişim, günlük takip ve onay akışı.')],
                            ['t' => __('Ofis / Finans'), 'd' => __('Fatura, tahsilat ve cari hareket tutarlılığı.')],
                            ['t' => __('Depo / Stok'), 'd' => __('Hareket takibi, sevkiyat ve stok disiplinleri.')],
                        ];
                    @endphp

                    @foreach ($who as $w)
                        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="text-sm font-semibold text-slate-900">{{ $w['t'] }}</div>
                            <div class="mt-2 text-sm leading-relaxed text-slate-600">{{ $w['d'] }}</div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Flow --}}
            <section id="flow" class="mx-auto max-w-6xl px-4 pb-4 pt-12 sm:px-6">
                <div>
                    <h2 class="text-2xl font-semibold tracking-tight text-slate-900">{{ __('3 Adımda Akış') }}</h2>
                    <p class="mt-2 text-sm text-slate-600">{{ __('En sık kullanılan yol: müşteri/tekne → teklif → fatura/tahsilat.') }}</p>
                </div>

                <div class="mt-8 grid gap-6 md:grid-cols-3">
                    @php
                        $steps = [
                            ['n' => '01', 't' => __('Müşteri & Tekne'), 'd' => __('Her işin bağlamı net olsun. Tekne ilişkisi kopmasın.')],
                            ['n' => '02', 't' => __('Teklif & Sipariş'), 'd' => __('Kalem bazlı teklif; onay, revizyon ve sözleşme çıktısı.')],
                            ['n' => '03', 't' => __('Fatura & Tahsilat'), 'd' => __('Kesim güvenliği, ledger etkisi ve ödeme akışı aynı yerde.')],
                        ];
                    @endphp

                    @foreach ($steps as $s)
                        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div class="text-xs font-semibold text-slate-500">{{ $s['n'] }}</div>
                                <x-ui.badge variant="neutral">{{ __('Akış') }}</x-ui.badge>
                            </div>
                            <div class="mt-3 text-sm font-semibold text-slate-900">{{ $s['t'] }}</div>
                            <div class="mt-2 text-sm leading-relaxed text-slate-600">{{ $s['d'] }}</div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Features --}}
            <section id="features" class="mx-auto max-w-6xl px-4 pb-16 pt-12 sm:px-6">
                <div>
                    <h2 class="text-2xl font-semibold tracking-tight text-slate-900">{{ __('Öne Çıkanlar') }}</h2>
                    <p class="mt-2 text-sm text-slate-600">{{ __('Karmaşık operasyonları sadeleştiren parçalar.') }}</p>
                </div>

                @php
                    $features = [
                        ['title' => __('Müşteri & Tekne Modeli'), 'desc' => __('Tekne sektörüne uygun ilişki; arama/filtre akışı hızlı.'), 'variant' => 'neutral'],
                        ['title' => __('Teklif / Sipariş / Sözleşme'), 'desc' => __('Kalem bazlı yönetim; onay, revizyon ve çıktı süreçleri.'), 'variant' => 'info'],
                        ['title' => __('Fatura Kesimi (Hardening)'), 'desc' => __('Numara üretimi ve kritik akışlarda daha güvenli davranış.'), 'variant' => 'success'],
                        ['title' => __('Stok & Depo'), 'desc' => __('Stok hareketleri ve sevkiyat akışı ile operasyon netliği.'), 'variant' => 'neutral'],
                        ['title' => __('Cari / Ledger'), 'desc' => __('İzlenebilir finans hareketleri ve açıklanabilirlik.'), 'variant' => 'info'],
                        ['title' => __('Verify Betikleri'), 'desc' => __('Senaryo bazlı doğrulamalarla regresyon riskini azaltır.'), 'variant' => 'success'],
                    ];
                @endphp

                <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($features as $f)
                        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="flex items-center justify-between gap-3">
                                <div class="text-sm font-semibold text-slate-900">{{ $f['title'] }}</div>
                                <x-ui.badge variant="{{ $f['variant'] }}">{{ __('Standart') }}</x-ui.badge>
                            </div>
                            <div class="mt-2 text-sm leading-relaxed text-slate-600">{{ $f['desc'] }}</div>
                        </div>
                    @endforeach
                </div>

                {{-- Bottom CTA --}}
                <div class="mt-10 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="text-lg font-semibold text-slate-900">{{ __('Hazır mısınız?') }}</div>
                            <div class="mt-1 text-sm text-slate-600">{{ __('Giriş yapın, akışları deneyin ve operasyonu tek ekranda toplayın.') }}</div>
                        </div>

                        <div class="flex gap-2">
                            @if (Route::has('login'))
                                @auth
                                    <x-ui.button href="{{ url('/dashboard') }}">{{ __('Panele Git') }}</x-ui.button>
                                @else
                                    <x-ui.button href="{{ route('login') }}">{{ __('Giriş Yap') }}</x-ui.button>
                                    @if (Route::has('register'))
                                        <x-ui.button href="{{ route('register') }}" variant="secondary">{{ __('Hesap Oluştur') }}</x-ui.button>
                                    @endif
                                @endauth
                            @endif
                        </div>
                    </div>
                </div>
            </section>
        </main>

        {{-- Footer --}}
        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto max-w-6xl px-4 py-8 text-sm text-slate-500 sm:px-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>© {{ date('Y') }} {{ config('app.name', 'Epsilon CRM') }}</div>
                    <div class="flex items-center gap-3">
                        <span class="text-slate-400">{{ __('Calm UI') }}</span>
                        <span class="text-slate-300">•</span>
                        <span class="text-slate-400">{{ __('Tekne odaklı') }}</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
