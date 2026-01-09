<div class="overflow-hidden rounded-xl border border-gray-100">
    <table class="min-w-full divide-y divide-gray-100 text-sm">
        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
            <tr>
                <th class="px-4 py-3 text-left">{{ __('Sürüm') }}</th>
                <th class="px-4 py-3 text-left">{{ __('Not') }}</th>
                <th class="px-4 py-3 text-left">{{ __('Oluşturan') }}</th>
                <th class="px-4 py-3 text-left">{{ __('Tarih') }}</th>
                <th class="px-4 py-3 text-right">{{ __('İşlemler') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($versions as $version)
                <tr class="{{ $template->current_version_id === $version->id ? 'bg-emerald-50/60' : 'bg-white' }}">
                    <td class="px-4 py-3 font-semibold text-gray-900">
                        v{{ $version->version }}
                        @if ($template->current_version_id === $version->id)
                            <span class="ml-2 rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">{{ __('Aktif') }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-700">{{ $version->change_note ?: '-' }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ $version->creator?->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ $version->created_at?->format('d.m.Y H:i') }}</td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <x-button
                                href="{{ route('contract-templates.show', ['contract_template' => $template, 'version' => $version->id]) }}"
                                variant="secondary"
                                size="xs"
                            >
                                {{ __('Önizleme') }}
                            </x-button>
                            @if ($template->current_version_id !== $version->id)
                                <form method="POST" action="{{ route('contract-templates.versions.restore', [$template, $version]) }}">
                                    @csrf
                                    <x-button type="submit" variant="danger" size="xs" onclick="return confirm('Bu sürüm geri yüklensin mi?')">
                                        {{ __('Geri Yükle') }}
                                    </x-button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-3 text-center text-gray-500" colspan="5">{{ __('Sürüm bulunamadı.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
