<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            [x-cloak] {
                display: none !important;
            }
        </style>
    </head>
    <body class="bg-slate-50 font-sans antialiased text-slate-900">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen" @keydown.escape.window="sidebarOpen = false">
            @include('layouts.navigation')

            <div class="lg:pl-72">
                <main class="py-6 sm:py-8">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        @isset($header)
                            <div class="pb-6">
                                {{ $header }}
                            </div>
                        @endisset

                        <x-ui.flash class="mb-6" />

                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
