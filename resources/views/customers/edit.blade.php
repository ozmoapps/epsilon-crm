<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                {{ __('Müşteri Düzenle') }}
            </h2>
            <a href="{{ route('customers.show', $customer) }}" class="text-sm text-gray-500 hover:text-gray-700">
                {{ __('Detaya Dön') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('customers.update', $customer) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    @include('customers._form', ['customer' => $customer])

                    <div class="flex items-center justify-end gap-3">
                        <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            {{ __('Güncelle') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
