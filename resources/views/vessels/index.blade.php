<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Tekneler') }}" subtitle="{{ __('Tekne kayıtlarını yönetin.') }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('vessels.create') }}">{{ __('Yeni Tekne') }}</x-ui.button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">
        <x-ui.card>
            <x-slot name="header">{{ __('Filtreler') }}</x-slot>
            <form method="GET" action="{{ route('vessels.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <x-input name="search" type="text" placeholder="İsme göre ara" :value="$search" />
                </div>
                <x-ui.button type="submit">{{ __('Ara') }}</x-ui.button>
                <x-ui.button href="{{ route('vessels.index') }}" variant="secondary">{{ __('Temizle') }}</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card>
            <x-slot name="header">{{ __('Liste') }}</x-slot>
            <x-ui.table>
                <thead class="bg-slate-50 text-xs font-semibold tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">{{ __('Tekne') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Müşteri') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Tip') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('LOA') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Beam') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Draft') }}</th>
                        <th class="px-4 py-3 text-right w-28">{{ __('Aksiyonlar') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($vessels as $vessel)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-3 text-sm font-semibold text-slate-900 max-w-0 truncate">{{ $vessel->name }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600 max-w-0 truncate">{{ $vessel->customer?->name ?? 'Müşteri yok' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $vessel->boat_type_label ?: '—' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $vessel->loa_m ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $vessel->beam_m ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $vessel->draft_m ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <form id="vessel-delete-{{ $vessel->id }}" method="POST" action="{{ route('vessels.destroy', $vessel) }}" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                <x-ui.row-actions
                                    show="{{ route('vessels.show', $vessel) }}"
                                    edit="{{ route('vessels.edit', $vessel) }}"
                                    delete="{{ route('vessels.destroy', $vessel) }}"
                                    delete-form-id="vessel-delete-{{ $vessel->id }}"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6">
                                <x-ui.empty-state
                                    icon="inbox"
                                    title="{{ __('Kayıt bulunamadı.') }}"
                                    description="{{ __('Yeni bir tekne ekleyerek başlayabilirsiniz.') }}"
                                    action="{{ route('vessels.create') }}"
                                    action-label="{{ __('Yeni Tekne') }}"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>

        <div>
            {{ $vessels->links() }}
        </div>
    </div>
</x-app-layout>
