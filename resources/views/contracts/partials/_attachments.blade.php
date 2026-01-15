<x-ui.card x-show="tab === 'attachments' || isDesktop" x-cloak>
    <x-slot name="header">
        <div>
            <p class="text-base font-semibold text-slate-900">{{ __('Ek Dosyalar') }}</p>
            <p class="text-sm text-slate-500">{{ __('Dosyaları ekleyin ve paylaşım paketinde yönetin.') }}</p>
        </div>
    </x-slot>
    <div class="space-y-6">
        <form
            method="POST"
            action="{{ route('contracts.attachments.store', $contract) }}"
            enctype="multipart/form-data"
            class="space-y-4"
            x-data="{ uploading: false }"
            @submit="uploading = true"
        >
            @csrf
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <x-input-label for="attachment_title" :value="__('Dosya Başlığı')" />
                    <x-input id="attachment_title" name="title" type="text" class="mt-1" :value="old('title')" />
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="attachment_type" :value="__('Dosya Türü')" />
                    <x-select id="attachment_type" name="type" class="mt-1">
                        @foreach ($attachmentTypeLabels as $typeValue => $label)
                            <option value="{{ $typeValue }}" @selected(old('type') === $typeValue)>{{ $label }}</option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('type')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="attachment_file" :value="__('Dosya')" />
                    <x-input id="attachment_file" name="file" type="file" class="mt-1" />
                    <p class="mt-1 text-xs text-slate-500">
                        {{ __('İzinli türler:') }} {{ implode(', ', $allowedAttachmentMimes) }} · {{ __('Maksimum:') }} {{ $formatBytes($maxAttachmentSizeKb * 1024) }}
                    </p>
                    <x-input-error :messages="$errors->get('file')" class="mt-2" />
                </div>
            </div>
            <div class="flex justify-end">
                <x-ui.button type="submit" variant="secondary" x-bind:disabled="uploading">
                    <span x-show="!uploading">{{ __('Dosya Yükle') }}</span>
                    <span x-cloak x-show="uploading">{{ __('Yükleniyor...') }}</span>
                </x-ui.button>
            </div>
        </form>

        <div class="space-y-3 text-sm">
            @forelse ($contract->attachments as $attachment)
                <div class="flex flex-col gap-3 rounded-xl border border-slate-100 p-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold text-slate-900">{{ $attachment->title }}</p>
                            <x-ui.badge variant="neutral">{{ $attachmentTypeLabels[$attachment->type] ?? $attachment->type }}</x-ui.badge>
                        </div>
                        <p class="text-xs text-slate-500">
                            {{ $formatBytes($attachment->size) }} · {{ $attachment->created_at?->format('d.m.Y H:i') }}
                            @if ($attachment->uploader)
                                · {{ $attachment->uploader->name }}
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.button
                            href="{{ route('contracts.attachments.download', [$contract, $attachment]) }}"
                            variant="secondary"
                            size="sm"
                            target="_blank"
                            rel="noopener"
                        >
                            {{ __('İndir') }}
                        </x-ui.button>
                        <form id="contract-attachment-delete-{{ $attachment->id }}" method="POST" action="{{ route('contracts.attachments.destroy', [$contract, $attachment]) }}" class="hidden">
                            @csrf
                            @method('DELETE')
                        </form>
                        <x-ui.confirm
                            title="{{ __('Silme işlemini onayla') }}"
                            message="{{ __('Bu işlem geri alınamaz. Devam etmek istiyor musunuz?') }}"
                            confirm-text="{{ __('Evet, sil') }}"
                            cancel-text="{{ __('Vazgeç') }}"
                            variant="danger"
                            form-id="contract-attachment-delete-{{ $attachment->id }}"
                        >
                            <x-slot name="trigger">
                                <x-ui.button type="button" variant="danger" size="sm">
                                    {{ __('Sil') }}
                                </x-ui.button>
                            </x-slot>
                        </x-ui.confirm>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-slate-200 p-6 text-center">
                    <p class="text-sm font-medium text-slate-700">{{ __('Henüz ek dosya yok') }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ __('İlk dosyayı yükleyerek paylaşım paketini zenginleştirin.') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</x-ui.card>
