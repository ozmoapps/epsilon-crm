@props(['filters', 'actions' => null])

<div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm mb-6">
    <div class="flex flex-col space-y-4">
        @if(isset($actions))
            <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                <h3 class="text-sm font-medium text-gray-900">{{ __('Filtreler') }}</h3>
                <div class="flex items-center gap-2">
                    {{ $actions }}
                </div>
            </div>
        @endif

        <div {{ $filters->attributes->class(['grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-4']) }}>
            {{ $filters }}
        </div>
    </div>
</div>
