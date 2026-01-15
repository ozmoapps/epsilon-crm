<x-ui.card>
    <x-slot name="header">{{ __('Revizyonlar') }}</x-slot>
    <div class="space-y-3 text-sm">
        @foreach ($revisions as $revision)
            <div class="flex flex-col gap-3 rounded-xl border border-slate-100 p-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <p class="text-xs text-slate-500">{{ $revision->contract_no }}</p>
                    <p class="text-base font-semibold text-slate-900">{{ $revision->revision_label }}</p>
                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                        <span>{{ $revision->issued_at?->format('d.m.Y') ?? '-' }}</span>
                        <span>·</span>
                        <span>{{ $revision->signed_at?->format('d.m.Y H:i') ?? '-' }}</span>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.badge :variant="$statusVariants[$revision->status] ?? 'neutral'">
                        {{ $revision->status_label }}
                    </x-ui.badge>
                    @if ($revision->is_current)
                        <x-ui.badge variant="success">
                            {{ __('Güncel') }}
                        </x-ui.badge>
                    @endif
                    <x-ui.button href="{{ route('contracts.show', $revision) }}" variant="secondary" size="sm">
                        {{ __('Görüntüle') }}
                    </x-ui.button>
                    <x-ui.button href="{{ route('contracts.pdf', $revision) }}" variant="secondary" size="sm">
                        {{ __('PDF') }}
                    </x-ui.button>
                </div>
            </div>
        @endforeach
    </div>
</x-ui.card>
