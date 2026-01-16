@props(['workOrder'])

<div class="space-y-6">
    {{-- Add Update Form --}}
    <x-ui.card class="rounded-2xl border border-slate-200 bg-white shadow-card !p-5">
        <h3 class="font-semibold text-slate-900 mb-4">{{ __('İlerleme Kaydı Ekle') }}</h3>
        <form action="{{ route('work-orders.updates.store', $workOrder) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <x-input-label for="note" :value="__('Not / Açıklama')" />
                <x-textarea id="note" name="note" rows="3" class="mt-1 block w-full" placeholder="Yapılan işlemi kısaca özetleyin..." required></x-textarea>
            </div>
            
            <div class="flex gap-4">
                <div class="flex-1">
                    <x-input-label for="photo" :value="__('Fotoğraf (İsteğe bağlı)')" />
                    <input type="file" name="photo" id="photo" accept="image/*" class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                </div>
                <div class="flex-1">
                    <x-input-label for="happened_at" :value="__('Tarih/Saat')" />
                     <x-input id="happened_at" name="happened_at" type="datetime-local" class="mt-1" value="{{ now()->format('Y-m-d\TH:i') }}" />
                </div>
            </div>

            <div class="flex justify-end">
                <x-ui.button type="submit">
                    {{ __('Kaydet') }}
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- Timeline / List --}}
    <div class="relative">
        <div class="absolute top-0 bottom-0 left-6 w-px bg-slate-200"></div>

        <div class="space-y-6">
            @forelse($workOrder->updates as $update)
                <div class="relative pl-14">
                    {{-- Dot --}}
                    <div class="absolute left-4 top-2 h-4 w-4 -translate-x-1/2 rounded-full border-2 border-white bg-brand-500 shadow-sm"></div>

                    <div class="group relative flex flex-col gap-2 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md">
                        {{-- Header --}}
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-slate-900">{{ $update->creator->name ?? 'Sistem' }}</span>
                                <span class="text-xs text-slate-400">• {{ $update->happened_at->format('d.m.Y H:i') }}</span>
                            </div>
                            
                             @if(auth()->user()->is_admin || auth()->id() === $update->created_by)
                                <form action="{{ route('work-order-updates.destroy', $update) }}" method="POST" class="opacity-0 group-hover:opacity-100 transition-opacity">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-slate-400 hover:text-rose-500" onclick="return confirm('Silmek istediğinize emin misiniz?')">
                                        <x-icon.trash class="h-4 w-4" />
                                    </button>
                                </form>
                            @endif
                        </div>

                        {{-- Content --}}
                        <div class="text-sm text-slate-600 whitespace-pre-wrap">{{ $update->note }}</div>

                        {{-- Photo --}}
                        @if($update->photo_path)
                            <div class="mt-2">
                                <a href="{{ Storage::url($update->photo_path) }}" target="_blank" class="inline-block relative overflow-hidden rounded-lg border border-slate-100 group/img">
                                    <img src="{{ Storage::url($update->photo_path) }}" alt="Update Photo" class="h-32 w-auto object-cover transition group-hover/img:scale-105">
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-6 text-slate-400 text-sm pl-14">
                    {{ __('Henüz ilerleme kaydı bulunmuyor.') }}
                </div>
            @endforelse
        </div>
    </div>
</div>
