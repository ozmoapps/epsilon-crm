<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ __('Teklifler') }}" subtitle="{{ __('Teklif süreçlerini takip edin.') }}">
            <x-slot name="actions">
                <x-button href="{{ route('quotes.create') }}">{{ __('Yeni Teklif') }}</x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">
        <x-card>
            <x-slot name="header">{{ __('Filtreler') }}</x-slot>
            <form method="GET" action="{{ route('quotes.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <x-input name="search" type="text" placeholder="Teklif no veya başlığa göre ara" :value="$search" />
                </div>
                <div class="sm:w-56">
                    <x-select name="status">
                        <option value="">{{ __('Tüm Durumlar') }}</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </x-select>
                </div>
                <x-button type="submit">{{ __('Ara') }}</x-button>
                <x-button href="{{ route('quotes.index') }}" variant="secondary">{{ __('Temizle') }}</x-button>
            </form>
        </x-card>

        <x-card>
            <x-slot name="header">{{ __('Liste') }}</x-slot>
            @php
                $statusVariants = [
                    'draft' => 'draft',
                    'sent' => 'neutral',
                    'accepted' => 'confirmed',
                    'rejected' => 'canceled',
                    'expired' => 'canceled',
                    'canceled' => 'canceled',
                ];
                $actionItemClass = 'flex w-full items-center gap-2 px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50';
                $actionDangerClass = 'flex w-full items-center gap-2 px-3 py-2 text-sm text-rose-600 transition hover:bg-rose-50';
            @endphp
            <x-ui.table>
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">{{ __('Teklif No') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Başlık') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Müşteri') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Tekne') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Durum') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Aksiyonlar') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($quotes as $quote)
                        @php
                            $isLocked = $quote->isLocked();
                        @endphp
                        <tr class="odd:bg-white even:bg-slate-50 hover:bg-slate-100/60">
                            <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $quote->quote_no }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ $quote->title }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $quote->customer?->name ?? 'Müşteri yok' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $quote->vessel?->name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @if ($quote->status)
                                    <x-ui.badge :variant="$statusVariants[$quote->status] ?? 'neutral'">
                                        {{ $quote->status_label }}
                                    </x-ui.badge>
                                @endif
                            </td>
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
                                        <a href="{{ route('quotes.show', $quote) }}" class="{{ $actionItemClass }}">
                                            <x-icon.info class="h-4 w-4 text-sky-600" />
                                            {{ __('Görüntüle') }}
                                        </a>
                                        @if ($isLocked)
                                            <button
                                                type="button"
                                                class="{{ $actionItemClass }} cursor-not-allowed opacity-60"
                                                aria-disabled="true"
                                                title="{{ __('Bu teklif siparişe dönüştürüldüğü için düzenlenemez.') }}"
                                                @click.prevent
                                            >
                                                <x-icon.pencil class="h-4 w-4 text-indigo-600" />
                                                {{ __('Düzenle') }}
                                                <x-ui.badge variant="neutral" class="ml-auto text-[10px]">{{ __('Kilitli') }}</x-ui.badge>
                                            </button>
                                        @else
                                            <a href="{{ route('quotes.edit', $quote) }}" class="{{ $actionItemClass }}">
                                                <x-icon.pencil class="h-4 w-4 text-indigo-600" />
                                                {{ __('Düzenle') }}
                                            </a>
                                        @endif
                                        @if ($isLocked)
                                            <button
                                                type="button"
                                                class="{{ $actionDangerClass }} cursor-not-allowed opacity-60"
                                                aria-disabled="true"
                                                title="{{ __('Bu teklifin bağlı siparişi olduğu için silinemez.') }}"
                                                @click.prevent
                                            >
                                                <x-icon.trash class="h-4 w-4" />
                                                {{ __('Sil') }}
                                                <x-ui.badge variant="neutral" class="ml-auto text-[10px]">{{ __('Kilitli') }}</x-ui.badge>
                                            </button>
                                        @else
                                            <form id="quote-delete-{{ $quote->id }}" method="POST" action="{{ route('quotes.destroy', $quote) }}" class="hidden">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                            <x-ui.confirm-dialog
                                                title="{{ __('Silme işlemini onayla') }}"
                                                message="{{ __('Bu işlem geri alınamaz. Devam etmek istiyor musunuz?') }}"
                                                confirm-text="{{ __('Evet, sil') }}"
                                                cancel-text="{{ __('Vazgeç') }}"
                                                variant="danger"
                                                form-id="quote-delete-{{ $quote->id }}"
                                            >
                                                <x-slot name="trigger">
                                                    <button type="button" class="{{ $actionDangerClass }}">
                                                        <x-icon.trash class="h-4 w-4" />
                                                        {{ __('Sil') }}
                                                    </button>
                                                </x-slot>
                                            </x-ui.confirm-dialog>
                                        @endif
                                    </x-slot>
                                </x-ui.dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">
                                {{ __('Kayıt bulunamadı.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-card>

        <div>
            {{ $quotes->links() }}
        </div>
    </div>
</x-app-layout>
