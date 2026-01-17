<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Aşağıdaki firmaya davet edildiniz:') }}
    </div>
    
    <div class="p-4 mb-4 bg-white rounded-lg shadow-sm border border-slate-200">
        <h3 class="font-bold text-lg text-slate-800">{{ $invitation->tenant->name }}</h3>
        <p class="text-sm text-slate-500 mt-1">{{ __('Davet edilen e-posta:') }} <span class="font-medium text-slate-700">{{ $invitation->email }}</span></p>
    </div>

    @if(strtolower(auth()->user()->email) !== strtolower($invitation->email))
         <div class="p-3 mb-4 text-sm text-red-600 bg-red-50 rounded-lg">
             {{ __('Dikkat: Davet edilen e-posta adresi, şu anki hesabınızla eşleşmiyor.') }}
             <br>
             {{ __('Lütfen ') }} {{ $invitation->email }} {{ __(' adresiyle giriş yapın.') }}
         </div>
         
         <form method="POST" action="{{ route('logout') }}" class="flex justify-end">
            @csrf
            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900">
                {{ __('Çıkış Yap') }}
            </button>
        </form>
    @else
        <form method="POST" action="{{ url('/invite/'.$token.'/accept') }}">
            @csrf
            <x-primary-button class="w-full justify-center">
                {{ __('Daveti Kabul Et ve Katıl') }}
            </x-primary-button>
        </form>
        
        <div class="mt-4 flex justify-center">
             <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 underline hover:text-gray-900">{{ __('Vazgeç') }}</a>
        </div>
    @endif
</x-guest-layout>
