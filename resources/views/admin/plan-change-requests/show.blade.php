<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Talep Detayı') }} #{{ $request->id }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Sol: Talep Bilgileri --}}
            <x-ui.card>
                <h3 class="text-lg font-medium text-slate-900 border-b pb-3 mb-4">{{ __('Talep Bilgileri') }}</h3>
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-slate-500">{{ __('Firma') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $request->tenant->name }}</dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-slate-500">{{ __('Talep Eden') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $request->requester->name }} <br><span class="text-xs text-slate-500">{{ $request->requester->email }}</span></dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-slate-500">{{ __('Mevcut Paket') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                             <x-ui.badge variant="neutral">
                                 {{ config("plans.plans.{$request->current_plan_key}.name", $request->current_plan_key) }}
                             </x-ui.badge>
                        </dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-slate-500">{{ __('İstenen Paket') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                             <x-ui.badge variant="info">
                                 {{ config("plans.plans.{$request->requested_plan_key}.name", $request->requested_plan_key) }}
                             </x-ui.badge>
                        </dd>
                    </div>
                    @if($request->reason)
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-slate-500">{{ __('Not / Sebep') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 bg-slate-50 p-3 rounded-md">{{ $request->reason }}</dd>
                    </div>
                    @endif
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-slate-500">{{ __('Talep Tarihi') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $request->created_at->format('d.m.Y H:i:s') }}</dd>
                    </div>
                </dl>
            </x-ui.card>

            {{-- Sağ: Aksiyon --}}
            <div class="space-y-6">
                {{-- Durum --}}
                <x-ui.card>
                    <h3 class="text-lg font-medium text-slate-900 border-b pb-3 mb-4">{{ __('Durum & İşlem') }}</h3>
                    
                    @if($request->status === 'pending')
                        <div class="rounded-md bg-amber-50 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <x-icon.clock class="h-5 w-5 text-amber-400" />
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-amber-800">Bekliyor</h3>
                                    <div class="mt-2 text-sm text-amber-700">
                                        <p>Bu talep inceleme bekliyor. Aşağıdan onaylayabilir veya reddedebilirsiniz.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Onay Formu --}}
                        <div x-data="{ action: null }">
                            <div class="flex gap-4 mb-4">
                                <button @click="action = 'approve'" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline text-center transition">
                                    {{ __('Onayla') }}
                                </button>
                                <button @click="action = 'reject'" class="flex-1 bg-rose-600 hover:bg-rose-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline text-center transition">
                                    {{ __('Reddet') }}
                                </button>
                            </div>

                            {{-- Approve Form --}}
                            <div x-show="action === 'approve'" class="border border-emerald-200 bg-emerald-50 p-4 rounded-md">
                                <form method="post" action="{{ route('admin.plan_requests.approve', $request) }}">
                                    @csrf
                                    <h4 class="font-semibold text-emerald-800 mb-2">{{ __('Onaylıyor musunuz?') }}</h4>
                                    <p class="text-sm text-emerald-700 mb-4">
                                        Hesabın paketi <strong>{{ $request->requested_plan_key }}</strong> olarak güncellenecektir.
                                    </p>
                                    <textarea name="review_note" placeholder="Not (Opsiyonel)" class="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm mb-3"></textarea>
                                    <div class="flex items-center gap-2">
                                        <x-ui.button type="submit" variant="success">{{ __('Evet, Onayla ve Paket Değiştir') }}</x-ui.button>
                                        <button type="button" @click="action = null" class="text-sm text-slate-600 hover:underline">{{ __('Vazgeç') }}</button>
                                    </div>
                                </form>
                            </div>

                            {{-- Reject Form --}}
                            <div x-show="action === 'reject'" class="border border-rose-200 bg-rose-50 p-4 rounded-md">
                                <form method="post" action="{{ route('admin.plan_requests.reject', $request) }}">
                                    @csrf
                                    <h4 class="font-semibold text-rose-800 mb-2">{{ __('Reddediyor musunuz?') }}</h4>
                                    <p class="text-sm text-rose-700 mb-4">Talep reddedilecek ve kullanıcıya bildirim gösterilecek.</p>
                                    <textarea name="review_note" placeholder="Red Sebebi (Opsiyonel)" class="w-full rounded-md border-slate-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 sm:text-sm mb-3"></textarea>
                                    <div class="flex items-center gap-2">
                                        <x-ui.button type="submit" variant="danger">{{ __('Evet, Reddet') }}</x-ui.button>
                                        <button type="button" @click="action = null" class="text-sm text-slate-600 hover:underline">{{ __('Vazgeç') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    @else
                        {{-- Sonuç --}}
                        <div class="rounded-md {{ $request->status == 'approved' ? 'bg-emerald-50' : 'bg-rose-50' }} p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    @if($request->status == 'approved')
                                        <x-icon.check class="h-5 w-5 text-emerald-400" />
                                    @else
                                        <x-icon.x class="h-5 w-5 text-rose-400" />
                                    @endif
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium {{ $request->status == 'approved' ? 'text-emerald-800' : 'text-rose-800' }}">
                                        {{ $request->status == 'approved' ? 'Onaylandı' : 'Reddedildi' }}
                                    </h3>
                                    <div class="mt-2 text-sm {{ $request->status == 'approved' ? 'text-emerald-700' : 'text-rose-700' }}">
                                        <p>İşlemi Yapan: {{ $request->reviewer->name ?? '-' }}</p>
                                        <p>Tarih: {{ $request->reviewed_at?->format('d.m.Y H:i') }}</p>
                                        @if($request->review_note)
                                            <p class="mt-1">Not: {{ $request->review_note }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </x-ui.card>
            </div>
        </div>
    </div>
</x-app-layout>
