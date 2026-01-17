<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Platform Genel Bakış') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- 1. Metrics Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Hesaplar & Kullanıcılar --}}
                <x-ui.card>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-semibold text-slate-500">{{ __('Hesaplar') }}</span>
                        <x-icon.building class="text-slate-400 w-5 h-5" />
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-slate-800">{{ $metrics['total_accounts'] }}</span>
                        <span class="text-xs text-slate-500">{{ __('Toplam Hesap') }}</span>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-500">{{ __('Kullanıcılar:') }}</span>
                        <span class="font-semibold text-slate-700">{{ $metrics['total_users'] }}</span>
                    </div>
                </x-ui.card>

                {{-- Firmalar --}}
                <x-ui.card>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-semibold text-slate-500">{{ __('Firmalar') }}</span>
                        <x-icon.office-building class="text-slate-400 w-5 h-5" />
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-slate-800">{{ $metrics['total_tenants'] }}</span>
                        <span class="text-xs text-slate-500">{{ __('Toplam Firma') }}</span>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-100 flex items-center gap-3 text-xs">
                        <div class="flex items-center gap-1">
                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                            <span class="text-slate-600">{{ $metrics['active_tenants'] }} {{ __('Aktif') }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <div class="w-1.5 h-1.5 rounded-full bg-slate-300"></div>
                            <span class="text-slate-600">{{ $metrics['inactive_tenants'] }} {{ __('Pasif') }}</span>
                        </div>
                    </div>
                </x-ui.card>

                {{-- Davetler & Support --}}
                <x-ui.card>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-semibold text-slate-500">{{ __('Erişim & Bekleyen') }}</span>
                        <x-icon.users class="text-slate-400 w-5 h-5" />
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-slate-800">{{ $metrics['pending_invites'] }}</span>
                        <span class="text-xs text-slate-500">{{ __('Davet') }}</span>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-500">{{ __('Aktif Destek Oturumu:') }}</span>
                        @if($metrics['active_support_sessions'] > 0)
                            <x-ui.badge variant="success" size="sm">{{ $metrics['active_support_sessions'] }}</x-ui.badge>
                        @else
                            <span class="font-semibold text-slate-700">0</span>
                        @endif
                    </div>
                </x-ui.card>

                {{-- Hızlı Linkler --}}
                <x-ui.card class="bg-slate-50 border-slate-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-bold text-slate-800">{{ __('Yönetim') }}</span>
                    </div>
                    <div class="space-y-2">
                        <x-ui.button href="{{ route('admin.tenants.index') }}" variant="secondary" size="sm" class="w-full justify-between">
                            {{ __('Firma Listesi') }} <x-icon.arrow-right class="w-4 h-4 text-slate-400" />
                        </x-ui.button>
                        <x-ui.button href="{{ route('admin.audit.index') }}" variant="secondary" size="sm" class="w-full justify-between">
                            {{ __('Denetim Günlüğü') }} <x-icon.arrow-right class="w-4 h-4 text-slate-400" />
                        </x-ui.button>
                    </div>
                </x-ui.card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- 2. Plan Breakdown --}}
                <div class="lg:col-span-1 space-y-6">
                    <x-ui.card>
                        <h3 class="text-sm font-bold text-slate-800 mb-4">{{ __('Plan Dağılımı (Hesaplar)') }}</h3>
                        <div class="space-y-3">
                            @foreach($planBreakdown as $plan)
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-slate-600">{{ $plan->plan_name }}</span>
                                    <x-ui.badge variant="neutral">{{ $plan->count }}</x-ui.badge>
                                </div>
                            @endforeach

                            @if($planBreakdown->isEmpty())
                                <div class="text-sm text-slate-400 italic text-center py-2">{{ __('Plan verisi bulunamadı') }}</div>
                            @endif
                        </div>
                    </x-ui.card>

                    {{-- Son Gizlilik İhlali --}}
                    @if($lastPrivacyViolation)
                        <x-ui.card class="border-amber-200 bg-amber-50/30">
                            <div class="flex items-center gap-2 mb-2">
                                <x-icon.exclamation-triangle class="text-amber-500 w-5 h-5" />
                                <h3 class="text-sm font-bold text-slate-800">{{ __('Son Gizlilik İhlali') }}</h3>
                            </div>
                            <div class="text-xs text-slate-600 mb-1">
                                {{ $lastPrivacyViolation->occurred_at->format('d.m.Y H:i') }}
                            </div>
                            <div class="text-sm font-semibold text-slate-800 mb-3">
                                {{ $lastPrivacyViolation->tenant ? $lastPrivacyViolation->tenant->name : 'Bilinmeyen Firma' }}
                            </div>
                             <x-ui.button href="{{ route('admin.audit.index') }}" variant="secondary" size="xs" class="w-full justify-center">
                                {{ __('Detayları Gör') }}
                            </x-ui.button>
                        </x-ui.card>
                    @endif
                </div>

                {{-- 3. Audit Summary (Last 24h) --}}
                <div class="lg:col-span-2">
                    <x-ui.card>
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-bold text-slate-800">{{ __('Son 24 Saat Olay Özeti') }}</h3>
                            <span class="text-xs text-slate-500">{{ __('Sistem Geneli') }}</span>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <x-ui.table>
                                <x-slot name="head">
                                    <tr>
                                        <th class="px-3 py-2 text-left">{{ __('Olay Türü') }}</th>
                                        <th class="px-3 py-2 text-right">{{ __('Adet') }}</th>
                                    </tr>
                                </x-slot>
                                <x-slot name="body">
                                    @foreach($auditSummary as $stat)
                                        @php
                                            $label = $eventMap[$stat->event_key] ?? $stat->event_key;
                                        @endphp
                                        <tr class="hover:bg-slate-50/50">
                                            <td class="px-3 py-2 text-slate-700 font-medium">
                                                {{ $label }} 
                                                <span class="text-xs text-slate-400 font-normal ml-1">({{ $stat->event_key }})</span>
                                            </td>
                                            <td class="px-3 py-2 text-right">
                                                <x-ui.badge variant="neutral">{{ $stat->count }}</x-ui.badge>
                                            </td>
                                        </tr>
                                    @endforeach

                                    @if($auditSummary->isEmpty())
                                        <tr>
                                            <td colspan="2" class="text-center text-slate-500 py-8">
                                                {{ __('Son 24 saatte kayıtlı olay yok.') }}
                                            </td>
                                        </tr>
                                    @endif
                                </x-slot>
                            </x-ui.table>
                        </div>
                    </x-ui.card>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
