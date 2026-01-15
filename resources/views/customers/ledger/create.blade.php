<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                {{ __('Manuel Cari İşlem Ekle') }}: {{ $customer->name }}
            </h2>
            <a href="{{ route('customers.ledger', $customer) }}" class="text-sm text-slate-500 hover:text-slate-700 transition-colors">
                &larr; {{ __('Ekstreye Dön') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-soft rounded-xl border border-slate-200/60">
                <div class="p-6 text-slate-900">
                    
                    <form method="POST" action="{{ route('customers.ledger.manual.store', $customer) }}" class="space-y-6">
                        @csrf
                        
                        {{-- Date --}}
                        <div>
                            <x-input-label for="occurred_at" :value="__('İşlem Tarihi')" />
                            <x-text-input id="occurred_at" class="block mt-1 w-full" type="date" name="occurred_at" :value="old('occurred_at', now()->format('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('occurred_at')" class="mt-2" />
                        </div>

                        {{-- Currency --}}
                        <div>
                        <div>
                            <x-input-label for="currency" :value="__('Para Birimi')" />
                            <select name="currency" id="currency" class="ui-input mt-1 block" required>
                                <option value="">{{ __('Seçiniz') }}</option>
                                @foreach($currencies as $c)
                                    <option value="{{ $c->code }}" {{ old('currency') == $c->code ? 'selected' : '' }}>
                                        {{ $c->code }} - {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('currency')" class="mt-2" />
                        </div>

                        {{-- Direction --}}
                        <div>
                            <x-input-label for="direction" :value="__('İşlem Yönü')" />
                            <div class="mt-2 flex items-center space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="direction" value="debit" class="h-4 w-4 border-slate-300 text-brand-600 focus:ring-brand-500" {{ old('direction') === 'debit' ? 'checked' : '' }} required>
                                    <span class="ml-2">{{ __('BORÇ (Debit)') }}</span>
                                    <span class="ml-1 text-xs text-slate-500">({{ __('Müşteri borçlanır') }})</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="direction" value="credit" class="h-4 w-4 border-slate-300 text-brand-600 focus:ring-brand-500" {{ old('direction') === 'credit' ? 'checked' : '' }}>
                                    <span class="ml-2">{{ __('ALACAK (Credit)') }}</span>
                                    <span class="ml-1 text-xs text-slate-500">({{ __('Müşteri alacaklanır') }})</span>
                                </label>
                            </div>
                            <x-input-error :messages="$errors->get('direction')" class="mt-2" />
                        </div>

                        {{-- Amount --}}
                        <div>
                            <x-input-label for="amount" :value="__('Tutar')" />
                            <x-text-input id="amount" class="block mt-1 w-full" type="text" name="amount" :value="old('amount')" placeholder="0.00" required />
                            <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                        </div>

                        {{-- Vessel (Optional) --}}
                        <div>
                            <x-input-label for="vessel_id" :value="__('Tekne (Opsiyonel)')" />
                            <select name="vessel_id" id="vessel_id" class="ui-input mt-1 block">
                                <option value="">{{ __('Seçiniz (Opsiyonel)') }}</option>
                                @foreach($vessels as $v)
                                    <option value="{{ $v->id }}" {{ old('vessel_id') == $v->id ? 'selected' : '' }}>
                                        {{ $v->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('vessel_id')" class="mt-2" />
                            <p class="text-xs text-slate-500 mt-1">{{ __('İşlem belirli bir tekne ile ilişkilendirilecekse seçiniz.') }}</p>
                        </div>

                        {{-- Description --}}
                        <div>
                             <x-input-label for="description" :value="__('Açıklama')" />
                             <x-text-input id="description" class="block mt-1 w-full" type="text" name="description" :value="old('description')" required />
                             <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end">
                            <x-ui.button variant="secondary" href="{{ route('customers.ledger', $customer) }}" class="mr-3">
                                {{ __('İptal') }}
                            </x-ui.button>
                            <x-ui.button type="submit" variant="primary">
                                {{ __('Kaydet') }}
                            </x-ui.button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
