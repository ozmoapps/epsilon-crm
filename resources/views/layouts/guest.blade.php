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
    </head>
    <body class="bg-slate-50 font-sans text-slate-900 antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center px-4 py-8 sm:px-6">
            <div class="flex items-center justify-center">
                <a href="/">
                    <x-application-logo class="h-20 w-20 fill-current text-slate-500" />
                </a>
            </div>

            <div class="mt-6 w-full max-w-md overflow-hidden rounded-2xl border border-slate-200/80 bg-white px-6 py-6 shadow-card">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
