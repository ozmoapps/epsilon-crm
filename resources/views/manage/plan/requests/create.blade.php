<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Paket Yükseltme Talebi') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-ui.card>
                <div class="max-w-xl">
                    <header>
                        <h2 class="text-lg font-medium text-slate-900">
                            {{ __('Paket Yükseltme Talebi Oluştur') }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ __('Mevcut paketiniz: ') }} <span class="font-bold">{{ config("plans.plans.{$currentPlanKey}.name", ucfirst($currentPlanKey)) }}</span>
                        </p>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ __('Bu işlem faturalandırma içermez, sadece talep oluşturur. Platform yöneticisi talebinizi inceleyip onaylayacaktır.') }}
                        </p>
                    </header>

                    @if($pendingRequest)
                        <div class="mt-6 rounded-md bg-amber-50 p-4 border border-amber-200">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <x-icon.clock class="h-5 w-5 text-amber-400" />
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-amber-800">Bekleyen Talebiniz Var</h3>
                                    <div class="mt-2 text-sm text-amber-700">
                                        <p>Şu anda incelenmekte olan bir talebiniz bulunmaktadır:</p>
                                        <ul class="list-disc pl-5 mt-1 space-y-1">
                                            <li>Talep Edilen: <strong>{{ config("plans.plans.{$pendingRequest->requested_plan_key}.name", $pendingRequest->requested_plan_key) }}</strong></li>
                                            <li>Tarih: {{ $pendingRequest->created_at->format('d.m.Y H:i') }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex items-center gap-4">
                            <x-ui.button href="{{ route('manage.plan.index') }}" variant="secondary">
                                {{ __('Geri Dön') }}
                            </x-ui.button>
                        </div>
                    @else
                        <form method="post" action="{{ route('manage.plan_requests.store') }}" class="mt-6 space-y-6">
                            @csrf

                            {{-- Hedef Paket --}}
                            <div>
                                <x-input-label for="requested_plan_key" :value="__('Hedef Paket')" />
                                <select id="requested_plan_key" name="requested_plan_key" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                                    @foreach($plans as $key => $plan)
                                        <option value="{{ $key }}">{{ $plan['name'] }} @if(isset($plan['price'])) - {{ $plan['price'] }} @endif</option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('requested_plan_key')" />
                            </div>

                            {{-- Not/Sebep --}}
                            <div>
                                <x-input-label for="reason" :value="__('Notunuz (Opsiyonel)')" />
                                <textarea
                                    id="reason"
                                    name="reason"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                                    placeholder="{{ __('Örn: 50 kişilik ekipler için Enterprise pakete geçmek istiyoruz...') }}"
                                >{{ old('reason') }}</textarea>
                                <x-input-error class="mt-2" :messages="$errors->get('reason')" />
                            </div>

                            <div class="flex items-center gap-4">
                                <x-ui.button type="submit">
                                    {{ __('Talebi Gönder') }}
                                </x-ui.button>

                                <a class="underline text-sm text-slate-600 hover:text-slate-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500" href="{{ route('manage.plan.index') }}">
                                    {{ __('İptal') }}
                                </a>
                            </div>
                        </form>
                    @endif
                </div>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
