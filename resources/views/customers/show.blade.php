<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                {{ $customer->name }}
            </h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('customers.edit', $customer) }}" class="rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    {{ __('Düzenle') }}
                </a>
                <a href="{{ route('customers.index') }}" class="rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    {{ __('Listeye Dön') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <div class="space-y-6">
                @if (session('success'))
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-gray-500">{{ __('Telefon') }}</dt>
                            <dd class="text-gray-900">{{ $customer->phone ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ __('E-posta') }}</dt>
                            <dd class="text-gray-900">{{ $customer->email ?: '—' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-gray-500">{{ __('Adres') }}</dt>
                            <dd class="text-gray-900">{{ $customer->address ?: '—' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-gray-500">{{ __('Notlar') }}</dt>
                            <dd class="text-gray-900">{{ $customer->notes ?: '—' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                            {{ __('Tekneler') }}
                        </h3>
                        <a href="{{ route('vessels.create', ['customer_id' => $customer->id]) }}" class="text-sm text-indigo-600 hover:text-indigo-500">
                            {{ __('Tekne Ekle') }}
                        </a>
                    </div>
                    <div class="mt-4 space-y-3">
                        @forelse ($customer->vessels as $vessel)
                            <a href="{{ route('vessels.show', $vessel) }}" class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50">
                                <span>{{ $vessel->name }}</span>
                                <span class="text-gray-400">→</span>
                            </a>
                        @empty
                            <p class="text-sm text-gray-500">{{ __('Henüz tekne yok.') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                            {{ __('İş Emirleri') }}
                        </h3>
                        <a href="{{ route('work-orders.create') }}" class="text-sm text-indigo-600 hover:text-indigo-500">
                            {{ __('İş Emri Ekle') }}
                        </a>
                    </div>
                    <div class="mt-4 space-y-3">
                        @forelse ($customer->workOrders as $workOrder)
                            <a href="{{ route('work-orders.show', $workOrder) }}" class="flex flex-col gap-1 rounded-lg border border-gray-200 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 sm:flex-row sm:items-center sm:justify-between">
                                <span class="font-medium text-gray-900">{{ $workOrder->title }}</span>
                                <span class="text-gray-500">
                                    {{ $workOrder->vessel?->name ?? 'Tekne yok' }} · {{ $workOrder->status_label }}
                                </span>
                            </a>
                        @empty
                            <p class="text-sm text-gray-500">{{ __('Henüz iş emri yok.') }}</p>
                        @endforelse
                    </div>
                </div>

                <form method="POST" action="{{ route('customers.destroy', $customer) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full rounded-md border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50" onclick="return confirm('Müşteri kaydı silinsin mi?')">
                        {{ __('Müşteri Kaydını Sil') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
