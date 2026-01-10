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
            @php
                $actionItemClass = 'flex w-full items-center gap-2 px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50';
            @endphp
            <x-ui.table>
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">{{ __('Şablon') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Dil') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Durum') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Güncelleme') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Aksiyonlar') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($templates as $template)
                        <tr class="odd:bg-white even:bg-slate-50 hover:bg-slate-100/60">
                            <td class="px-4 py-3 text-sm font-semibold text-slate-900">
                                {{ $template->name }}
                                @if ($template->is_default)
                                    <span class="ml-2 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">
                                        {{ __('Varsayılan') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ config('contracts.locales')[$template->locale] ?? $template->locale }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ $template->is_active ? __('Aktif') : __('Pasif') }}
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $template->updated_at?->format('d.m.Y H:i') }}</td>
                            <td class="px-4 py-3 text-right">
                                <x-ui.dropdown align="right" width="w-48">
                                    <x-slot name="trigger">
                                        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-slate-300 hover:text-slate-900" aria-label="{{ __('İşlemler') }}">
                                            <x-icon.dots class="h-4 w-4" />
                                        </button>
                                    </x-slot>
                                    <x-slot name="content">
                                        <a href="{{ route('admin.contract-templates.edit', $template) }}" class="{{ $actionItemClass }}">
                                            <x-icon.pencil class="h-4 w-4 text-indigo-600" />
                                            {{ __('Düzenle') }}
                                        </a>
                                        <form method="POST" action="{{ route('admin.contract-templates.make_default', $template) }}">
                                            @csrf
                                            <button type="submit" class="{{ $actionItemClass }}">
                                                <x-icon.check class="h-4 w-4 text-emerald-600" />
                                                {{ __('Varsayılan Yap') }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.contract-templates.toggle_active', $template) }}">
                                            @csrf
                                            <button type="submit" class="{{ $actionItemClass }}">
                                                <x-icon.x class="h-4 w-4 text-amber-500" />
                                                {{ $template->is_active ? __('Pasifleştir') : __('Aktifleştir') }}
                                            </button>
                                        </form>
                                    </x-slot>
                                </x-ui.dropdown>
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
