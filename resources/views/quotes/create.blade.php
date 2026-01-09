<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                {{ __('Yeni Teklif') }}
            </h2>
            <a href="{{ route('quotes.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
                {{ __('Geri dön') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('quotes.store') }}" class="space-y-6">
                    @csrf

                    @include('quotes._form')

                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            {{ __('Kaydet') }}
                        </button>
                        <a href="{{ route('quotes.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            {{ __('İptal') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
