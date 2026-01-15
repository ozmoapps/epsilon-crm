<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="{{ __('Depolar') }}" subtitle="{{ __('Stok lokasyonlarını yönetin.') }}">
        </x-ui.page-header>
    </x-slot>

    <div class="grid md:grid-cols-3 gap-6">
        <div class="md:col-span-2">
            <x-ui.card>
                 <x-slot name="header">{{ __('Depo Listesi') }}</x-slot>
                 
                <x-ui.table>
                    <thead class="bg-slate-50 text-xs font-semibold tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left w-1/3">{{ __('Depo Adı') }}</th>
                            <th class="px-4 py-3 text-center w-24">{{ __('Varsayılan') }}</th>
                            <th class="px-4 py-3 text-center w-24">{{ __('Durum') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('İşlemler') }}</th>
                        </tr>
                    </thead>
                    @forelse($warehouses as $warehouse)
                        <tbody class="border-b border-slate-100 last:border-0 hover:bg-slate-50/40 transition-colors" x-data="{ editing: false, name: '{{ $warehouse->name }}', is_default: {{ $warehouse->is_default ? 'true' : 'false' }}, is_active: {{ $warehouse->is_active ? 'true' : 'false' }}, notes: '{{ $warehouse->notes }}' }">
                            <!-- View Row -->
                            <tr x-show="!editing">
                                <td class="px-4 py-3 font-medium text-slate-900 align-middle">
                                    {{ $warehouse->name }}
                                    @if($warehouse->notes)
                                        <div class="text-xs text-slate-400 font-normal truncate max-w-[200px]">{{ $warehouse->notes }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center align-middle">
                                    @if($warehouse->is_default)
                                        <x-ui.status-badge variant="success" text="Varsayılan" />
                                    @else
                                        <span class="text-slate-300 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center align-middle">
                                    <x-ui.status-badge :status="$warehouse->is_active" />
                                </td>
                                <td class="px-4 py-3 text-right align-middle">
                                    <div class="flex justify-end items-center gap-2">
                                        <button @click="editing = true" class="text-slate-400 hover:text-brand-600 transition-colors p-1" title="Düzenle">
                                            <x-icon.pencil class="w-4 h-4" />
                                        </button>
                                        
                                        @if(!$warehouse->stockMovements()->exists() && !$warehouse->is_default)
                                            <form action="{{ route('warehouses.destroy', $warehouse) }}" method="POST" 
                                                  onsubmit="return confirm('Silmek istediğinize emin misiniz?');" class="inline-block">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-slate-400 hover:text-rose-600 transition-colors p-1" title="Sil">
                                                    <x-icon.trash class="w-4 h-4" />
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Edit Row -->
                            <tr x-show="editing" x-cloak class="bg-slate-50">
                                <td colspan="4" class="p-2">
                                    <form action="{{ route('warehouses.update', $warehouse) }}" method="POST" class="flex items-center gap-4">
                                        @csrf @method('PUT')
                                        <div class="flex-1 grid gap-2">
                                            <x-input name="name" x-model="name" class="py-1 text-sm h-9" required placeholder="Depo Adı" />
                                            <x-input name="notes" x-model="notes" placeholder="Notlar" class="py-1 text-sm h-8" />
                                        </div>
                                        
                                        <div class="flex flex-col gap-2 min-w-[120px]">
                                            <label class="inline-flex items-center text-sm">
                                                <input type="checkbox" name="is_default" value="1" x-model="is_default" class="rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500">
                                                <span class="ml-2">Varsayılan</span>
                                            </label>
                                            <label class="inline-flex items-center text-sm">
                                                <input type="checkbox" name="is_active" value="1" x-model="is_active" class="rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500">
                                                <span class="ml-2">Aktif</span>
                                            </label>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <button type="submit" class="p-2 bg-slate-900 text-white rounded-lg hover:bg-slate-800 shadow-sm transition-colors" title="Kaydet">
                                                <x-icon.check class="w-5 h-5" />
                                            </button>
                                            <button type="button" @click="editing = false; name='{{ $warehouse->name }}'" class="p-2 bg-white border border-slate-200 text-slate-500 rounded-lg hover:text-slate-700 hover:bg-slate-50 transition-colors" title="İptal">
                                                <x-icon.x class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        </tbody>
                    @empty
                        <tbody>
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-slate-500 italic">
                                    <div class="flex flex-col items-center gap-2">
                                        <x-icon.office-building class="w-8 h-8 text-slate-300" />
                                        <span>{{ __('Henüz depo eklenmemiş.') }}</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    @endforelse
                </x-ui.table>
            </x-ui.card>
        </div>
        
        <!-- Add New Warehouse Form Column -->
        <div>
            <x-ui.card>
                <x-slot name="header">{{ __('Yeni Depo Ekle') }}</x-slot>
                <form action="{{ route('warehouses.store') }}" method="POST" class="space-y-4 p-4">
                    @csrf
                    <div>
                        <x-ui.field label="Depo Adı" name="name" required>
                             <x-input name="name" :value="old('name')" required placeholder="Örn: Garaj Depo" />
                        </x-ui.field>
                    </div>
                    <div>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="is_default" value="1" class="rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500">
                            <span class="ml-2 text-sm text-slate-700">Varsayılan Depo Yap</span>
                        </label>
                    </div>
                    <div>
                        <x-ui.field label="Notlar" name="notes">
                             <x-textarea name="notes" rows="2" placeholder="Adres vb. bilgiler" />
                        </x-ui.field>
                    </div>
                    <x-ui.button type="submit" class="w-full justify-center">{{ __('Ekle') }}</x-ui.button>
                </form>
            </x-ui.card>
        </div>


    </div>
</x-app-layout>
