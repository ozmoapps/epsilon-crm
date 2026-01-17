<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Denetim Günlüğü') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-ui.card>
                <div class="p-6 text-gray-900">
                    <x-ui.table>
                        <x-slot name="head">
                            <tr>
                                <th>Tarih</th>
                                <th>Olay</th>
                                <th>Kullanıcı</th>
                                <th>Detaylar</th>
                            </tr>
                        </x-slot>

                        <x-slot name="body">
                            @forelse ($logs as $log)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="whitespace-nowrap text-xs text-slate-500 px-3 py-3">
                                        {{ $log->occurred_at->format('d.m.Y H:i') }}
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="flex flex-col gap-1">
                                            <span class="font-medium text-slate-700">
                                                {{ $eventMap[$log->event_key] ?? $log->event_key }}
                                            </span>
                                            @php
                                                $variant = match($log->severity) {
                                                    'critical' => 'danger',
                                                    'warn' => 'danger',
                                                    default => 'neutral'
                                                };
                                            @endphp
                                            <x-ui.badge :variant="$variant" size="xs">
                                                {{ strtoupper($log->severity) }}
                                            </x-ui.badge>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium">
                                                {{ $log->actor ? $log->actor->name : 'Sistem' }}
                                            </span>
                                            <span class="text-xs text-slate-400">
                                                {{ $log->actor_type }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3">
                                        @if($log->metadata)
                                            <div class="text-xs font-mono text-slate-600 bg-slate-50 p-2 rounded max-w-sm overflow-x-auto">
                                                @foreach($log->metadata as $key => $value)
                                                    <div><span class="font-bold">{{ $key }}:</span> {{ is_array($value) ? json_encode($value) : $value }}</div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-slate-500 py-8">
                                        Henüz kayıt yok.
                                    </td>
                                </tr>
                            @endforelse
                        </x-slot>
                    </x-ui.table>

                    <div class="mt-4">
                        {{ $logs->links() }}
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
