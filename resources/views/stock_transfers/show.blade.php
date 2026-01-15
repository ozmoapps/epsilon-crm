<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="'Transfer #' . $stockTransfer->id" :subtitle="$stockTransfer->fromWarehouse?->name . ' -> ' . $stockTransfer->toWarehouse?->name">
            <x-slot name="actions">
                <x-ui.button href="{{ route('stock-transfers.index') }}" variant="secondary">
                    {{ __('Listeye Dön') }}
                </x-ui.button>
                <x-ui.button href="{{ route('stock-transfers.create') }}" variant="primary">
                    {{ __('Yeni Transfer') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="grid md:grid-cols-3 gap-6">
        <div class="md:col-span-2 space-y-6">
            <x-ui.card>
                <x-slot name="header">
                    <div class="flex items-center justify-between">
                        <span>{{ __('Transfer Detayları') }}</span>
                        @if($stockTransfer->status === 'draft')
                            <x-badge variant="warning">{{ __('Taslak') }}</x-badge>
                        @else
                            <x-badge variant="success">{{ __('İşlendi') }}</x-badge>
                        @endif
                    </div>
                </x-slot>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                            <tr>
                                <th class="px-4 py-3">{{ __('Ürün') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Miktar') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($stockTransfer->lines as $line)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-slate-900">{{ $line->product?->name ?? 'Deleted Product' }}</div>
                                        <div class="text-xs text-slate-500">{{ $line->product?->sku }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-slate-700">
                                        {{ number_format($line->qty, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-slate-50 font-semibold text-slate-700">
                            <tr>
                                <td class="px-4 py-3 text-right">{{ __('Toplam Kalem') }}</td>
                                <td class="px-4 py-3 text-right">{{ $stockTransfer->lines->count() }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </x-ui.card>

            @if($stockTransfer->status === 'draft')
                <x-ui.card class="bg-blue-50 border-blue-100">
                    <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-blue-800">
                            <p class="font-semibold">{{ __('Bu transfer henüz taslak durumunda.') }}</p>
                            <p>{{ __('Stok düşüşünü gerçekleştirmek için işlemi onaylayın.') }}</p>
                        </div>
                        <form action="{{ route('stock-transfers.post', $stockTransfer) }}" method="POST">
                            @csrf
                            <x-ui.button type="submit" variant="primary">
                                <x-icon.check class="w-4 h-4 mr-1" />
                                {{ __('Transferi Onayla ve İşle') }}
                            </x-ui.button>
                        </form>
                    </div>
                </x-ui.card>
            @endif
        </div>

        <div class="space-y-6">
            <x-ui.card>
                <x-slot name="header">{{ __('Bilgiler') }}</x-slot>
                <dl class="divide-y divide-slate-100">
                    <div class="py-3 grid grid-cols-2 gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('Oluşturan') }}</dt>
                        <dd class="text-sm text-slate-900 text-right">{{ $stockTransfer->creator?->name ?? '-' }}</dd>
                    </div>
                    <div class="py-3 grid grid-cols-2 gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('Tarih') }}</dt>
                        <dd class="text-sm text-slate-900 text-right">{{ $stockTransfer->created_at->format('d.m.Y H:i') }}</dd>
                    </div>
                    @if($stockTransfer->posted_at)
                    <div class="py-3 grid grid-cols-2 gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('İşlenme Tarihi') }}</dt>
                        <dd class="text-sm text-slate-900 text-right">{{ $stockTransfer->posted_at->format('d.m.Y H:i') }}</dd>
                    </div>
                    @endif
                    <div class="py-3 grid grid-cols-2 gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('Çıkış Deposu') }}</dt>
                        <dd class="text-sm text-slate-900 text-right">{{ $stockTransfer->fromWarehouse?->name ?? '-' }}</dd>
                    </div>
                    <div class="py-3 grid grid-cols-2 gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('Giriş Deposu') }}</dt>
                        <dd class="text-sm text-slate-900 text-right">{{ $stockTransfer->toWarehouse?->name ?? '-' }}</dd>
                    </div>
                </dl>
                
                @if($stockTransfer->note)
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <span class="block text-xs font-semibold text-slate-500 uppercase mb-1">{{ __('Notlar') }}</span>
                        <p class="text-sm text-slate-600">{{ $stockTransfer->note }}</p>
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
