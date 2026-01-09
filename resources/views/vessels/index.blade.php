<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Tekneler') }}" subtitle="{{ __('Tekne kayıtlarını yönetin.') }}">
            <x-slot name="actions">
                <x-button href="{{ route('vessels.create') }}">{{ __('Yeni Tekne') }}</x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">
        <x-card>
            <x-slot name="header">{{ __('Filtreler') }}</x-slot>
            <form method="GET" action="{{ route('vessels.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <x-input name="search" type="text" placeholder="İsme göre ara" :value="$search" />
                </div>
                <x-button type="submit">{{ __('Ara') }}</x-button>
                <x-button href="{{ route('vessels.index') }}" variant="secondary">{{ __('Temizle') }}</x-button>
            </form>
        </x-card>

        <x-card>
            <x-slot name="header">{{ __('Liste') }}</x-slot>
            @php
                $actionItemClass = 'flex w-full items-center gap-2 px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50';
                $actionDangerClass = 'flex w-full items-center gap-2 px-3 py-2 text-sm text-rose-600 transition hover:bg-rose-50';
            @endphp
            <x-ui.table>
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">{{ __('Tekne') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Müşteri') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Tip') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('LOA') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Beam') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Draft') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Aksiyonlar') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($vessels as $vessel)
                        <tr class="odd:bg-white even:bg-slate-50 hover:bg-slate-100/60">
                            <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $vessel->name }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $vessel->customer?->name ?? 'Müşteri yok' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $vessel->boat_type_label ?: '—' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $vessel->loa_m ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $vessel->beam_m ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $vessel->draft_m ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <x-ui.dropdown align="right" width="w-44">
                                    <x-slot name="trigger">
                                        <x-ui.tooltip text="{{ __('İşlemler') }}">
                                            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-slate-300 hover:text-slate-900" aria-label="{{ __('İşlemler') }}">
                                                <x-icon.dots class="h-4 w-4" />
                                            </button>
                                        </x-ui.tooltip>
                                    </x-slot>
                                    <x-slot name="content">
                                        <x-ui.tooltip text="{{ __('Görüntüle') }}" class="w-full">
                                            <a href="{{ route('vessels.show', $vessel) }}" class="{{ $actionItemClass }}">
                                                <x-icon.info class="h-4 w-4 text-sky-600" />
                                                {{ __('Görüntüle') }}
                                            </a>
                                        </x-ui.tooltip>
                                        <x-ui.tooltip text="{{ __('Düzenle') }}" class="w-full">
                                            <a href="{{ route('vessels.edit', $vessel) }}" class="{{ $actionItemClass }}">
                                                <x-icon.pencil class="h-4 w-4 text-indigo-600" />
                                                {{ __('Düzenle') }}
                                            </a>
                                        </x-ui.tooltip>
                                        <form method="POST" action="{{ route('vessels.destroy', $vessel) }}">
                                            @csrf
                                            @method('DELETE')
                                            <x-ui.tooltip text="{{ __('Sil') }}" class="w-full">
                                                <button type="submit" class="{{ $actionDangerClass }}" onclick="return confirm('Tekne kaydı silinsin mi?')">
                                                    <x-icon.trash class="h-4 w-4" />
                                                    {{ __('Sil') }}
                                                </button>
                                            </x-ui.tooltip>
                                        </form>
                                    </x-slot>
                                </x-ui.dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-slate-500">
                                {{ __('Kayıt bulunamadı.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-card>

        <div>
            {{ $vessels->links() }}
        </div>
    </div>
</x-app-layout>
