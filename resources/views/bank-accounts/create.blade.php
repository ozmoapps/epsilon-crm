<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="{{ __('Yeni Hesap Oluştur') }}" >
             <x-ui.button href="{{ route('bank-accounts.index') }}" variant="secondary" size="sm">
                {{ __('Listeye Dön') }}
            </x-ui.button>
        </x-ui.page-header>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-ui.card>
                <form action="{{ route('bank-accounts.store') }}" method="POST" class="space-y-6" x-data="{ type: 'bank' }">
                    @csrf

                    <!-- Account Type Toggle -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('Hesap Tipi') }}</label>
                        <div class="mt-2 flex items-center space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="type" value="bank" x-model="type" class="form-radio text-brand-600 focus:ring-brand-500 h-4 w-4">
                                <span class="ml-2 text-sm text-slate-700">{{ __('Banka Hesabı') }}</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="type" value="cash" x-model="type" class="form-radio text-brand-600 focus:ring-brand-500 h-4 w-4">
                                <span class="ml-2 text-sm text-slate-700">{{ __('Kasa Hesabı') }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Common Fields -->
                    <div>
                        <x-input-label for="name" value="{{ __('Hesap Adı') }}" />
                        <x-text-input id="name" type="text" name="name" class="mt-1 block w-full" required autofocus placeholder="Örn: Garanti Ana Hesap veya Merkez TL Kasa" />
                    </div>

                    <div>
                        <x-input-label for="currency_id" value="{{ __('Döviz Cinsi') }}" />
                        <select name="currency_id" id="currency_id" class="ui-input mt-1 block">
                            @foreach($currencies as $currency)
                                <option value="{{ $currency->id }}">{{ $currency->code }} - {{ $currency->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Bank Specific Fields -->
                    <div x-show="type === 'bank'" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="bank_name" value="{{ __('Banka Adı') }}" />
                                <x-text-input id="bank_name" type="text" name="bank_name" class="mt-1 block w-full" placeholder="Örn: Garanti BBVA" />
                            </div>
                            <div>
                                <x-input-label for="branch_name" value="{{ __('Şube Adı / Kodu') }}" />
                                <x-text-input id="branch_name" type="text" name="branch_name" class="mt-1 block w-full" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="iban" value="{{ __('IBAN') }}" />
                            <x-text-input id="iban" type="text" name="iban" class="mt-1 block w-full" placeholder="TR..." />
                        </div>
                    </div>

                    <!-- Opening Balance -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50 p-4 rounded-xl border border-slate-200">
                        <div>
                            <x-input-label for="opening_balance" value="{{ __('Açılış Bakiyesi') }}" />
                            <x-text-input id="opening_balance" type="number" step="0.01" name="opening_balance" value="0.00" class="mt-1 block w-full text-right" />
                        </div>
                        <div>
                            <x-input-label for="opening_balance_date" value="{{ __('Bakiye Tarihi') }}" />
                            <x-text-input id="opening_balance_date" type="date" name="opening_balance_date" value="{{ now()->format('Y-m-d') }}" class="mt-1 block w-full" />
                        </div>
                    </div>
                    
                    <div>
                         <label class="inline-flex items-center">
                            <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-brand-600 shadow-sm focus:border-brand-300 focus:ring focus:ring-brand-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-slate-600">{{ __('Aktif') }}</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end">
                        <x-ui.button type="submit" variant="primary">
                            {{ __('Kaydet') }}
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
