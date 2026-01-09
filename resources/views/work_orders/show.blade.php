<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ $workOrder->title }}" subtitle="{{ __('İş emri detay görünümü') }}">
            <x-slot name="actions">
                <x-button href="{{ route('work-orders.edit', $workOrder) }}" variant="secondary" size="sm">
                    {{ __('Düzenle') }}
                </x-button>
                <x-button href="{{ route('work-orders.index') }}" variant="secondary" size="sm">
                    {{ __('Listeye Dön') }}
                </x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">
        <x-card>
            <x-slot name="header">{{ __('Özet') }}</x-slot>
            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-gray-500">{{ __('Müşteri') }}</dt>
                    <dd class="font-medium text-gray-900">
                        @if ($workOrder->customer)
                            <a href="{{ route('customers.show', $workOrder->customer) }}" class="text-indigo-600 hover:text-indigo-500">
                                {{ $workOrder->customer->name }}
                            </a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('Tekne') }}</dt>
                    <dd class="font-medium text-gray-900">
                        @if ($workOrder->vessel)
                            <a href="{{ route('vessels.show', $workOrder->vessel) }}" class="text-indigo-600 hover:text-indigo-500">
                                {{ $workOrder->vessel->name }}
                            </a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('Durum') }}</dt>
                    <dd>
                        <x-badge variant="info">{{ $workOrder->status_label }}</x-badge>
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('Planlanan Başlangıç') }}</dt>
                    <dd class="font-medium text-gray-900">{{ $workOrder->planned_start_at ? $workOrder->planned_start_at->format('d.m.Y') : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('Planlanan Bitiş') }}</dt>
                    <dd class="font-medium text-gray-900">{{ $workOrder->planned_end_at ? $workOrder->planned_end_at->format('d.m.Y') : '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-gray-500">{{ __('Açıklama') }}</dt>
                    <dd class="mt-1 font-medium text-gray-900">{{ $workOrder->description ?: '—' }}</dd>
                </div>
            </dl>
        </x-card>

        <form method="POST" action="{{ route('work-orders.destroy', $workOrder) }}">
            @csrf
            @method('DELETE')
            <x-button type="submit" variant="danger" class="w-full" onclick="return confirm('İş emri silinsin mi?')">
                {{ __('İş Emrini Sil') }}
            </x-button>
        </form>
    </div>
</x-app-layout>
