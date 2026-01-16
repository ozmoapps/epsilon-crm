@props(['workOrder'])

<x-ui.card class="mt-6 !p-0 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
        <h3 class="font-semibold text-slate-900">{{ __('Fotoğraflar') }}</h3>
    </div>
    
    <div class="p-5 grid gap-8 sm:grid-cols-2">
        {{-- Before Photos --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('Öncesi') }}</h4>
                <div class="relative">
                    <input type="file" id="photo_upload_before" class="hidden" 
                           onchange="document.getElementById('form_photo_before').submit()" 
                           name="photo" form="form_photo_before" accept="image/*">
                    <form id="form_photo_before" action="{{ route('work-orders.photos.store', $workOrder) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="type" value="before">
                        <label for="photo_upload_before" class="cursor-pointer inline-flex items-center gap-1.5 text-xs font-medium text-brand-600 hover:text-brand-700 transition-colors">
                            <x-icon.plus class="h-3.5 w-3.5" />
                            {{ __('Ekle') }}
                        </label>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                @forelse($workOrder->photos->where('type', 'before') as $photo)
                    <div class="group relative aspect-video bg-slate-100 rounded-lg overflow-hidden border border-slate-200">
                        <img src="{{ Storage::url($photo->path) }}" alt="Fotoğraf" class="w-full h-full object-cover transition duration-300 group-hover:scale-105">
                        
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                            <a href="{{ Storage::url($photo->path) }}" target="_blank" class="p-1.5 bg-white/10 text-white rounded-full hover:bg-white/20 backdrop-blur-sm transition">
                                <x-icon.eye class="h-4 w-4" />
                            </a>
                            <form id="delete-photo-{{ $photo->id }}" action="{{ route('work-order-photos.destroy', $photo) }}" method="POST" class="hidden">
                                @csrf
                                @method('DELETE')
                            </form>
                            <button type="button" 
                                class="p-1.5 bg-rose-500/80 text-white rounded-full hover:bg-rose-600 transition backdrop-blur-sm"
                                data-confirm
                                data-confirm-title="{{ __('Silinsin mi?') }}"
                                data-confirm-message="{{ __('Fotoğraf silinecek. Bu işlem geri alınamaz.') }}"
                                data-confirm-text="{{ __('Sil') }}"
                                data-confirm-cancel-text="{{ __('Vazgeç') }}"
                                data-confirm-submit="delete-photo-{{ $photo->id }}">
                                <x-icon.trash class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-2 border border-dashed border-slate-200 rounded-lg p-4 text-center">
                        <span class="text-xs text-slate-400">{{ __('Fotoğraf yok') }}</span>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- After Photos --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('Sonrası') }}</h4>
                <div class="relative">
                    <input type="file" id="photo_upload_after" class="hidden" 
                           onchange="document.getElementById('form_photo_after').submit()" 
                           name="photo" form="form_photo_after" accept="image/*">
                    <form id="form_photo_after" action="{{ route('work-orders.photos.store', $workOrder) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="type" value="after">
                        <label for="photo_upload_after" class="cursor-pointer inline-flex items-center gap-1.5 text-xs font-medium text-brand-600 hover:text-brand-700 transition-colors">
                            <x-icon.plus class="h-3.5 w-3.5" />
                            {{ __('Ekle') }}
                        </label>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                @forelse($workOrder->photos->where('type', 'after') as $photo)
                    <div class="group relative aspect-video bg-slate-100 rounded-lg overflow-hidden border border-slate-200">
                        <img src="{{ Storage::url($photo->path) }}" alt="Fotoğraf" class="w-full h-full object-cover transition duration-300 group-hover:scale-105">
                        
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                            <a href="{{ Storage::url($photo->path) }}" target="_blank" class="p-1.5 bg-white/10 text-white rounded-full hover:bg-white/20 backdrop-blur-sm transition">
                                <x-icon.eye class="h-4 w-4" />
                            </a>
                            <form id="delete-photo-{{ $photo->id }}" action="{{ route('work-order-photos.destroy', $photo) }}" method="POST" class="hidden">
                                @csrf
                                @method('DELETE')
                            </form>
                            <button type="button" 
                                class="p-1.5 bg-rose-500/80 text-white rounded-full hover:bg-rose-600 transition backdrop-blur-sm"
                                data-confirm
                                data-confirm-title="{{ __('Silinsin mi?') }}"
                                data-confirm-message="{{ __('Fotoğraf silinecek. Bu işlem geri alınamaz.') }}"
                                data-confirm-text="{{ __('Sil') }}"
                                data-confirm-cancel-text="{{ __('Vazgeç') }}"
                                data-confirm-submit="delete-photo-{{ $photo->id }}">
                                <x-icon.trash class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-2 border border-dashed border-slate-200 rounded-lg p-4 text-center">
                        <span class="text-xs text-slate-400">{{ __('Fotoğraf yok') }}</span>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-ui.card>
