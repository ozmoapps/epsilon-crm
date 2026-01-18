<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Paket Talepleri') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-ui.card>
                <div class="flex items-center justify-between mb-6">
                    <div class="flex gap-2">
                        <x-ui.button href="{{ route('admin.plan_requests.index', ['status' => 'pending']) }}" variant="{{ $currentStatus === 'pending' ? 'primary' : 'secondary' }}" size="sm">
                            {{ __('Bekleyen') }}
                        </x-ui.button>
                        <x-ui.button href="{{ route('admin.plan_requests.index', ['status' => 'approved']) }}" variant="{{ $currentStatus === 'approved' ? 'primary' : 'secondary' }}" size="sm">
                            {{ __('Onaylanan') }}
                        </x-ui.button>
                        <x-ui.button href="{{ route('admin.plan_requests.index', ['status' => 'rejected']) }}" variant="{{ $currentStatus === 'rejected' ? 'primary' : 'secondary' }}" size="sm">
                            {{ __('Reddedilen') }}
                        </x-ui.button>
                    </div>
                </div>

                <x-ui.table>
                    <x-slot name="head">
                        <x-ui.table.th>{{ __('Tarih') }}</x-ui.table.th>
                        <x-ui.table.th>{{ __('Firma (Tenant)') }}</x-ui.table.th>
                        <x-ui.table.th>{{ __('Talep Eden') }}</x-ui.table.th>
                        <x-ui.table.th>{{ __('Mevcut') }}</x-ui.table.th>
                        <x-ui.table.th>{{ __('İstenen') }}</x-ui.table.th>
                        <x-ui.table.th>{{ __('Durum') }}</x-ui.table.th>
                        <x-ui.table.th class="text-right">{{ __('İşlem') }}</x-ui.table.th>
                    </x-slot>

                    <x-slot name="body">
                        @forelse($requests as $request)
                            <tr class="hover:bg-slate-50">
                                <x-ui.table.td>
                                    {{ $request->created_at->format('d.m.Y H:i') }}
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    <div class="text-sm font-medium text-slate-900">{{ $request->tenant->name }}</div>
                                    <div class="text-xs text-slate-500">ID: {{ $request->tenant_id }}</div>
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    <div class="text-sm text-slate-900">{{ $request->requester->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $request->requester->email }}</div>
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    <x-ui.badge variant="neutral">{{ $request->current_plan_key }}</x-ui.badge>
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    <x-ui.badge variant="info">{{ $request->requested_plan_key }}</x-ui.badge>
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    @if($request->status === 'pending')
                                        <x-ui.badge variant="warning">{{ __('Bekliyor') }}</x-ui.badge>
                                    @elseif($request->status === 'approved')
                                        <x-ui.badge variant="success">{{ __('Onaylandı') }}</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="danger">{{ __('Reddedildi') }}</x-ui.badge>
                                    @endif
                                </x-ui.table.td>
                                <x-ui.table.td class="text-right">
                                    <x-ui.button href="{{ route('admin.plan_requests.show', $request) }}" size="xs" variant="secondary">
                                        {{ __('Detay') }}
                                    </x-ui.button>
                                </x-ui.table.td>
                            </tr>
                        @empty
                            <tr class="bg-white">
                                <td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 text-center">
                                    {{ __('Kayıt bulunamadı.') }}
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
