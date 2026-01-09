<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                {{ $vessel->name }}
            </h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('vessels.edit', $vessel) }}" class="rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    {{ __('Düzenle') }}
                </a>
                <a href="{{ route('vessels.index') }}" class="rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
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
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">{{ __('Genel Bilgiler') }}</h3>
                            <dl class="mt-4 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                                <div>
                                    <dt class="text-gray-500">{{ __('Müşteri') }}</dt>
                                    <dd class="text-gray-900">
                                        @if ($vessel->customer)
                                            <a href="{{ route('customers.show', $vessel->customer) }}" class="text-indigo-600 hover:text-indigo-500">
                                                {{ $vessel->customer->name }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">{{ __('Tekne Tipi') }}</dt>
                                    <dd class="text-gray-900">{{ $vessel->type ?: '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">{{ __('Tekne Sınıfı') }}</dt>
                                    <dd class="text-gray-900">{{ $vessel->boat_type_label ?: '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">{{ __('Gövde Malzemesi') }}</dt>
                                    <dd class="text-gray-900">{{ $vessel->material_label ?: '—' }}</dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt class="text-gray-500">{{ __('Ruhsat Numarası') }}</dt>
                                    <dd class="text-gray-900">{{ $vessel->registration_number ?: '—' }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div>
                            <h3 class="text-base font-semibold text-gray-900">{{ __('Boyut, Ağırlık ve Kapasite Bilgileri') }}</h3>
                            <dl class="mt-4 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <dt class="text-gray-500">{{ __('LOA (m)') }}</dt>
                                    <dd class="text-gray-900">{{ $vessel->loa_m ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">{{ __('Beam (m)') }}</dt>
                                    <dd class="text-gray-900">{{ $vessel->beam_m ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">{{ __('Draft (m)') }}</dt>
                                    <dd class="text-gray-900">{{ $vessel->draft_m ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">{{ __('Net Tonaj') }}</dt>
                                    <dd class="text-gray-900">{{ $vessel->net_tonnage ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">{{ __('Brüt Tonaj') }}</dt>
                                    <dd class="text-gray-900">{{ $vessel->gross_tonnage ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">{{ __('Yolcu Kapasitesi') }}</dt>
                                    <dd class="text-gray-900">{{ $vessel->passenger_capacity ?? '—' }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div>
                            <h3 class="text-base font-semibold text-gray-900">{{ __('Diğer Bilgiler ve Notlar') }}</h3>
                            <dl class="mt-4 grid grid-cols-1 gap-4 text-sm">
                                <div>
                                    <dt class="text-gray-500">{{ __('Notlar') }}</dt>
                                    <dd class="text-gray-900">{{ $vessel->notes ?: '—' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('vessels.destroy', $vessel) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full rounded-md border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50" onclick="return confirm('Tekne kaydı silinsin mi?')">
                        {{ __('Tekne Kaydını Sil') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
