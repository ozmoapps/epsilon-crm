<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Sözleşme Şablonları') }}" subtitle="{{ __('Sözleşme şablonlarını yönetin.') }}">
            <x-slot name="actions">
                <x-button href="{{ route('admin.contract-templates.create') }}">
                    {{ __('Yeni Şablon') }}
                </x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">
        <x-card>
            <x-slot name="header">{{ __('Filtreler') }}</x-slot>
            <form method="GET" action="{{ route('admin.contract-templates.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <x-input name="search" type="text" placeholder="İsme göre ara" :value="$search" />
                </div>
                <x-button type="submit">{{ __('Ara') }}</x-button>
                <x-button href="{{ route('admin.contract-templates.index') }}" variant="secondary">{{ __('Temizle') }}</x-button>
            </form>
        </x-card>

        <x-card>
            <x-slot name="header">{{ __('Liste') }}</x-slot>
            <x-ui.table>
                <thead class="bg-slate-50 text-xs font-semibold tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">{{ __('Şablon') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Dil') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Durum') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Güncelleme') }}</th>
                        <th class="px-4 py-3 text-right w-32">{{ __('Aksiyonlar') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($templates as $template)
                        <tr class="odd:bg-white even:bg-slate-50 hover:bg-slate-100/60">
                            <td class="px-4 py-3 text-sm font-semibold text-slate-900 max-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="truncate">{{ $template->name }}</span>
                                    @if ($template->is_default)
                                        <span class="shrink-0 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">
                                            {{ __('Varsayılan') }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ config('contracts.locales')[$template->locale] ?? $template->locale }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ $template->is_active ? __('Aktif') : __('Pasif') }}
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $template->updated_at?->format('d.m.Y H:i') }}</td>
                            <td class="px-4 py-3 text-right">
                                <x-ui.row-actions edit="{{ route('admin.contract-templates.edit', $template) }}">
                                    <form method="POST" action="{{ route('admin.contract-templates.make_default', $template) }}">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-emerald-600 transition hover:bg-emerald-50 hover:text-emerald-700"
                                            title="{{ __('Varsayılan Yap') }}"
                                            aria-label="{{ __('Varsayılan Yap') }}"
                                        >
                                            <x-icon.check class="h-4 w-4" />
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.contract-templates.toggle_active', $template) }}">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-amber-500 transition hover:bg-amber-50 hover:text-amber-600"
                                            title="{{ $template->is_active ? __('Pasifleştir') : __('Aktifleştir') }}"
                                            aria-label="{{ $template->is_active ? __('Pasifleştir') : __('Aktifleştir') }}"
                                        >
                                            <x-icon.x class="h-4 w-4" />
                                        </button>
                                    </form>
                                </x-ui.row-actions>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">
                                {{ __('Kayıt bulunamadı.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-card>

        <div>
            {{ $templates->links() }}
        </div>
    </div>
</x-app-layout>
