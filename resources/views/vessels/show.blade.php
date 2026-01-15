<x-app-layout>
    <x-slot name="header">
        <x-page-header title="{{ $vessel->name }}" subtitle="{{ __('Tekne detay görünümü') }}">
            <x-slot name="actions">
                <x-ui.button href="{{ route('vessels.edit', $vessel) }}" variant="secondary" size="sm">
                    {{ __('Düzenle') }}
                </x-ui.button>
                <x-ui.button href="{{ route('vessels.index') }}" variant="secondary" size="sm">
                    {{ __('Listeye Dön') }}
                </x-ui.button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">
        <x-ui.card>
            <x-slot name="header">{{ __('Genel Bilgiler') }}</x-slot>
            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-slate-500">{{ __('Müşteri') }}</dt>
                    <dd class="font-medium text-slate-900">
                        @if ($vessel->customer)
                            <a href="{{ route('customers.show', $vessel->customer) }}" class="text-brand-600 hover:text-brand-500">
                                {{ $vessel->customer->name }}
                            </a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Tekne Tipi') }}</dt>
                    <dd class="font-medium text-slate-900">{{ $vessel->boat_type_label ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Marka') }}</dt>
                    <dd class="font-medium text-slate-900">{{ $vessel->type ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Model') }}</dt>
                    <dd class="font-medium text-slate-900">{{ $vessel->registration_number ?: '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-slate-500">{{ __('Gövde Malzemesi') }}</dt>
                    <dd class="font-medium text-slate-900">{{ $vessel->material_label ?: '—' }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card>
            <x-slot name="header">{{ __('Boyut, Ağırlık ve Kapasite Bilgileri') }}</x-slot>
            <dl class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-slate-500">{{ __('LOA (m)') }}</dt>
                    <dd class="font-medium text-slate-900">{{ $vessel->loa_m ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Beam (m)') }}</dt>
                    <dd class="font-medium text-slate-900">{{ $vessel->beam_m ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Draft (m)') }}</dt>
                    <dd class="font-medium text-slate-900">{{ $vessel->draft_m ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Net Tonaj') }}</dt>
                    <dd class="font-medium text-slate-900">{{ $vessel->net_tonnage ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Brüt Tonaj') }}</dt>
                    <dd class="font-medium text-slate-900">{{ $vessel->gross_tonnage ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Yolcu Kapasitesi') }}</dt>
                    <dd class="font-medium text-slate-900">{{ $vessel->passenger_capacity ?? '—' }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card>
            <x-slot name="header">{{ __('Diğer Bilgiler ve Notlar') }}</x-slot>
            <dl class="text-sm">
                <div>
                    <dt class="text-slate-500">{{ __('Notlar') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $vessel->notes ?: '—' }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card>
            <x-slot name="header">
                <div class="flex items-center justify-between">
                    <span>{{ __('İletişim Kişileri') }}</span>
                    <x-ui.button type="button" variant="secondary" size="sm" x-data x-on:click="$dispatch('open-modal', 'add-contact-modal')">
                        {{ __('Kişi Ekle') }}
                    </x-ui.button>
                </div>
            </x-slot>
            
            @if($vessel->contacts->isEmpty())
                 <p class="text-sm text-slate-500">{{ __('Kayıtlı iletişim kişisi bulunmuyor.') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                            <tr>
                                <th class="px-3 py-2">{{ __('Rol') }}</th>
                                <th class="px-3 py-2">{{ __('İsim') }}</th>
                                <th class="px-3 py-2">{{ __('Telefon') }}</th>
                                <th class="px-3 py-2">{{ __('E-posta') }}</th>
                                <th class="px-3 py-2 text-right">{{ __('İşlem') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($vessel->contacts as $contact)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-slate-900">{{ $contact->role }}</td>
                                    <td class="px-3 py-2">{{ $contact->name }}</td>
                                    <td class="px-3 py-2">{{ $contact->phone ?? '—' }}</td>
                                    <td class="px-3 py-2">{{ $contact->email ?? '—' }}</td>
                                    <td class="px-3 py-2 text-right">
                                        <form id="contact-delete-{{ $contact->id }}" method="POST" action="{{ route('vessels.contacts.destroy', [$vessel, $contact]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600 hover:text-rose-700"
                                                    data-confirm
                                                    data-confirm-title="{{ __('Emin misiniz?') }}"
                                                    data-confirm-message="{{ __('Bu kişi silinecek. İşlem geri alınamaz.') }}"
                                                    data-confirm-text="{{ __('Sil') }}"
                                                    data-confirm-cancel-text="{{ __('Vazgeç') }}"
                                                    data-confirm-submit="contact-delete-{{ $contact->id }}">
                                                {{ __('Sil') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>

        <x-ui.card>
            <x-slot name="header">{{ __('Sahiplik Geçmişi') }}</x-slot>
            
            @if($vessel->ownerHistories->isEmpty())
                <p class="text-sm text-slate-500">{{ __('Sahiplik değişikliği kaydı bulunmuyor.') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                            <tr>
                                <th class="px-3 py-2">{{ __('Eski Müşteri') }}</th>
                                <th class="px-3 py-2">{{ __('Yeni Müşteri') }}</th>
                                <th class="px-3 py-2">{{ __('Değiştiren') }}</th>
                                <th class="px-3 py-2">{{ __('Tarih') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($vessel->ownerHistories as $history)
                                <tr>
                                    <td class="px-3 py-2">
                                        @if($history->oldCustomer)
                                            <a href="{{ route('customers.show', $history->oldCustomer) }}" class="text-brand-600 hover:text-brand-500">
                                                {{ $history->oldCustomer->name }}
                                            </a>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 font-medium text-slate-900">
                                        @if($history->newCustomer)
                                            <a href="{{ route('customers.show', $history->newCustomer) }}" class="text-brand-600 hover:text-brand-500">
                                                {{ $history->newCustomer->name }}
                                            </a>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-slate-600">
                                        {{ $history->changedBy?->name ?? '—' }}
                                    </td>
                                    <td class="px-3 py-2 text-slate-600">
                                        {{ $history->changed_at->format('d.m.Y H:i') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>


        <form id="vessel-delete-{{ $vessel->id }}" method="POST" action="{{ route('vessels.destroy', $vessel) }}">
            @csrf
            @method('DELETE')
            <x-ui.button type="submit" variant="danger" class="w-full"
                         data-confirm
                         data-confirm-title="{{ __('Emin misiniz?') }}"
                         data-confirm-message="{{ __('Tekne kaydı ve ilgili tüm veriler silinecek. Bu işlem geri alınamaz.') }}"
                         data-confirm-text="{{ __('Sil') }}"
                         data-confirm-cancel-text="{{ __('Vazgeç') }}"
                         data-confirm-submit="vessel-delete-{{ $vessel->id }}">
                {{ __('Tekne Kaydını Sil') }}
            </x-ui.button>
        </form>

         <x-ui.card class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-card !p-0 mt-6">
            <div class="border-b border-slate-100 bg-white px-4 py-3">
                <h3 class="font-semibold text-slate-900">{{ __('Aktivite') }}</h3>
            </div>
            <div class="bg-slate-50/40 p-4">
                <x-activity-timeline :logs="$timeline" :show-subject="false" />
            </div>
        </x-ui.card>
    </div>
    <x-modal name="add-contact-modal" title="{{ __('Yeni İletişim Kişisi') }}">
        <form method="POST" action="{{ route('vessels.contacts.store', $vessel) }}" class="space-y-4">
            @csrf
            <div>
                <x-input-label for="role" :value="__('Rol')" />
                <x-input id="role" name="role" type="text" class="mt-1 w-full" placeholder="Örn: Kaptan, Yönetici" required />
            </div>
            
            <div>
                <x-input-label for="name" :value="__('İsim')" />
                <x-input id="name" name="name" type="text" class="mt-1 w-full" required />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="phone" :value="__('Telefon')" />
                    <x-input id="phone" name="phone" type="text" class="mt-1 w-full" />
                </div>
                <div>
                    <x-input-label for="email" :value="__('E-posta')" />
                    <x-input id="email" name="email" type="email" class="mt-1 w-full" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button type="button" variant="secondary" x-data x-on:click="$dispatch('close-modal', 'add-contact-modal')">
                    {{ __('İptal') }}
                </x-ui.button>
                <x-ui.button type="submit">
                    {{ __('Kaydet') }}
                </x-ui.button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
