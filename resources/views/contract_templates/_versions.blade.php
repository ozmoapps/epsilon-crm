<div class="overflow-hidden rounded-xl border border-slate-100">
    <table class="min-w-full divide-y divide-slate-100 text-sm">
        <thead class="bg-slate-50 text-xs tracking-wide text-slate-500">
            <tr>
                <th class="px-4 py-3 text-left">{{ __('Sürüm') }}</th>
                <th class="px-4 py-3 text-left">{{ __('Not') }}</th>
                <th class="px-4 py-3 text-left">{{ __('Oluşturan') }}</th>
                <th class="px-4 py-3 text-left">{{ __('Tarih') }}</th>
                <th class="px-4 py-3 text-right">{{ __('İşlemler') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($versions as $version)
                <tr class="{{ $template->current_version_id === $version->id ? 'bg-emerald-50/60' : 'bg-white' }}">
                    <td class="px-4 py-3 font-semibold text-slate-900">
                        v{{ $version->version }}
                        @if ($template->current_version_id === $version->id)
                            <x-ui.badge variant="success" class="ml-2 !px-2 !py-0.5 text-xs">{{ __('Aktif') }}</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-700">{{ $version->change_note ?: '-' }}</td>
                    <td class="px-4 py-3 text-slate-700">{{ $version->creator?->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-slate-700">{{ $version->created_at?->format('d.m.Y H:i') }}</td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <x-ui.button
                                href="{{ route('admin.contract-templates.show', [$template, 'version' => $version->id]) }}"
                                variant="secondary"
                                size="xs"
                            >
                                {{ __('Önizleme') }}
                            </x-ui.button>
                            @if ($template->current_version_id !== $version->id)
    <form
        id="contract-template-version-restore-{{ $template->id }}-{{ $version->id }}"
        method="POST"
        action="{{ route('admin.contract-templates.versions.restore', [$template, $version]) }}"
    >
        @csrf
        <x-ui.button
            type="submit"
            variant="danger"
            size="xs"
            data-confirm
            data-confirm-title="{{ __('Emin misiniz?') }}"
            data-confirm-message="{{ __('Bu sürüm geri yüklenecek ve aktif sürüm değişecek.') }}"
            data-confirm-text="{{ __('Geri Yükle') }}"
            data-confirm-cancel-text="{{ __('Vazgeç') }}"
            data-confirm-submit="contract-template-version-restore-{{ $template->id }}-{{ $version->id }}"
        >
            {{ __('Geri Yükle') }}
        </x-ui.button>
    </form>
@endif

                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-3 text-center text-slate-500" colspan="5">{{ __('Sürüm bulunamadı.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
