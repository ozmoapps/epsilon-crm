<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="{{ __('Depo Transferleri') }}" subtitle="{{ __('Depolar arası ürün sevkıyatları.') }}">
             <x-slot name="actions">
                <x-ui.button href="{{ route('stock-transfers.create') }}" variant="primary">
                    <x-icon.plus class="w-4 h-4 mr-1" />
                    {{ __('Yeni Transfer') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <x-ui.card>
            <x-ui.table>
                <thead class="bg-slate-50 text-xs font-semibold tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">#ID</th>
                        <th class="px-4 py-3 text-left">{{ __('Tarih') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Çıkış Deposu') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Giriş Deposu') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('Durum') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Kalem') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('İşlemler') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($transfers as $transfer)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-3 font-mono text-sm text-slate-700">
                                #{{ $transfer->id }}
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ $transfer->created_at->format('d.m.Y H:i') }}
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-slate-700">
                                {{ $transfer->fromWarehouse->name }}
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-slate-700">
                                {{ $transfer->toWarehouse->name }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($transfer->status === 'draft')
                                    <x-ui.badge variant="neutral">{{ __('Taslak') }}</x-ui.badge>
                                @else
                                    <x-ui.badge variant="success">{{ __('İşlendi') }}</x-ui.badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-slate-600">
                                {{ $transfer->lines_count ?? $transfer->lines()->count() }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('stock-transfers.show', $transfer) }}" class="text-brand-600 hover:text-brand-700 text-sm font-medium">
                                    {{ __('Detay') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                         <tr>
                            <td colspan="7" class="px-4 py-8">
                                <x-ui.empty-state icon="switch-horizontal" title="Transfer bulunamadı" description="Henüz transfer kaydı yok." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>
        
        <div>{{ $transfers->links() }}</div>
    </div>
</x-app-layout>
