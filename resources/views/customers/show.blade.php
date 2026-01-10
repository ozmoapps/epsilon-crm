<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ $customer->name }}" subtitle="{{ __('Müşteri detay görünümü') }}">
            <x-slot name="actions">
                <x-button href="{{ route('customers.edit', $customer) }}" variant="secondary" size="sm">
                    {{ __('Düzenle') }}
                </x-button>
                <x-button href="{{ route('customers.index') }}" variant="secondary" size="sm">
                    {{ __('Listeye Dön') }}
                </x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">
        <div class="grid gap-4 lg:grid-cols-2">
            <x-card>
                <x-slot name="header">{{ __('İletişim') }}</x-slot>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-slate-500">{{ __('Telefon') }}</dt>
                        <dd class="font-medium text-slate-900">{{ $customer->phone ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">{{ __('E-posta') }}</dt>
                        <dd class="font-medium text-slate-900">{{ $customer->email ?: '—' }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card>
                <x-slot name="header">{{ __('Adres ve Notlar') }}</x-slot>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-slate-500">{{ __('Adres') }}</dt>
                        <dd class="font-medium text-slate-900">{{ $customer->address ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">{{ __('Notlar') }}</dt>
                        <dd class="font-medium text-slate-900">{{ $customer->notes ?: '—' }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>

        <x-card>
            <x-slot name="header">
                <div class="flex items-center justify-between">
                    <span>{{ __('Tekneler') }}</span>
                    <x-button href="{{ route('vessels.create', ['customer_id' => $customer->id]) }}" variant="secondary" size="sm">
                        {{ __('Tekne Ekle') }}
                    </x-button>
                </div>
            </x-slot>
            <div class="space-y-3">
                @forelse ($customer->vessels as $vessel)
                    <a href="{{ route('vessels.show', $vessel) }}" class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/70 px-4 py-3 text-sm text-slate-700 transition hover:bg-slate-100 ui-focus">
                        <span class="font-medium text-slate-900">{{ $vessel->name }}</span>
                        <span class="text-slate-400">→</span>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">{{ __('Henüz tekne yok.') }}</p>
                @endforelse
            </div>
        </x-card>

        <x-card>
            <x-slot name="header">
                <div class="flex items-center justify-between">
                    <span>{{ __('İş Emirleri') }}</span>
                    <x-button href="{{ route('work-orders.create') }}" variant="secondary" size="sm">
                        {{ __('İş Emri Ekle') }}
                    </x-button>
                </div>
            </x-slot>
            <div class="space-y-3">
                @forelse ($customer->workOrders as $workOrder)
                    <a href="{{ route('work-orders.show', $workOrder) }}" class="flex flex-col gap-1 rounded-xl border border-slate-100 bg-slate-50/70 px-4 py-3 text-sm text-slate-700 transition hover:bg-slate-100 ui-focus sm:flex-row sm:items-center sm:justify-between">
                        <span class="font-medium text-slate-900">{{ $workOrder->title }}</span>
                        <span class="text-slate-500">
                            {{ $workOrder->vessel?->name ?? 'Tekne yok' }} · {{ $workOrder->status_label }}
                        </span>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">{{ __('Henüz iş emri yok.') }}</p>
                @endforelse
            </div>
        </x-card>

        <form id="customer-delete-{{ $customer->id }}" method="POST" action="{{ route('customers.destroy', $customer) }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
        <x-ui.confirm
            title="{{ __('Silme işlemini onayla') }}"
            message="{{ __('Bu işlem geri alınamaz. Devam etmek istiyor musunuz?') }}"
            confirm-text="{{ __('Evet, sil') }}"
            cancel-text="{{ __('Vazgeç') }}"
            variant="danger"
            form-id="customer-delete-{{ $customer->id }}"
        >
            <x-slot name="trigger">
                <x-button type="button" variant="danger" class="w-full">
                    {{ __('Müşteri Kaydını Sil') }}
                </x-button>
            </x-slot>
        </x-ui.confirm>
    </div>
</x-app-layout>
