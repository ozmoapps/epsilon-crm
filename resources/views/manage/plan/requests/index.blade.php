<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Paket Taleplerim') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-ui.card>
                <div class="flex items-center justify-between mb-6">
                    <p class="text-sm text-slate-500">
                        {{ __('Paket yükseltme taleplerinizin durumunu buradan takip edebilirsiniz.') }}
                    </p>
                    <x-ui.button href="{{ route('manage.plan_requests.create') }}" variant="primary" size="sm">
                        {{ __('Yeni Yükseltme Talebi') }}
                    </x-ui.button>
                </div>

                <x-ui.table>
                    <x-slot name="head">
                        <x-ui.table.th>{{ __('Tarih') }}</x-ui.table.th>
                        <x-ui.table.th>{{ __('Mevcut Paket') }}</x-ui.table.th>
                        <x-ui.table.th>{{ __('İstenen Paket') }}</x-ui.table.th>
                        <x-ui.table.th>{{ __('Durum') }}</x-ui.table.th>
                        <x-ui.table.th>{{ __('İnceleme Notu') }}</x-ui.table.th>
                        <x-ui.table.th>{{ __('İnceleme Tarihi') }}</x-ui.table.th>
                    </x-slot>

                    <x-slot name="body">
                        @forelse($requests as $request)
                            <tr class="hover:bg-slate-50">
                                <x-ui.table.td>
                                    {{ $request->created_at->format('d.m.Y H:i') }}
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    <x-ui.badge variant="neutral">
                                        {{ config("plans.plans.{$request->current_plan_key}.name", $request->current_plan_key) }}
                                    </x-ui.badge>
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    <x-ui.badge variant="info">
                                        {{ config("plans.plans.{$request->requested_plan_key}.name", $request->requested_plan_key) }}
                                    </x-ui.badge>
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    @if($request->status === 'pending')
                                        <x-ui.badge variant="info">{{ __('Bekliyor') }}</x-ui.badge>
                                    @elseif($request->status === 'approved')
                                        <x-ui.badge variant="success">{{ __('Onaylandı') }}</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="danger">{{ __('Reddedildi') }}</x-ui.badge>
                                    @endif
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    @if($request->review_note)
                                        <span class="text-sm text-slate-700" title="{{ $request->review_note }}">
                                            {{ \Illuminate\Support\Str::limit($request->review_note, 30) }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    @if($request->reviewed_at)
                                        <span class="text-xs text-slate-500">
                                            {{ $request->reviewed_at->format('d.m.Y H:i') }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </x-ui.table.td>
                            </tr>
                        @empty
                            <tr class="bg-white">
                                <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 text-center">
                                    {{ __('Henüz bir talebiniz bulunmuyor.') }}
                                </td>
                            </tr>
                        @endforelse
                    </x-slot>
                </x-ui.table>

                <div class="mt-4">
                    {{ $requests->links() }}
                </div>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
