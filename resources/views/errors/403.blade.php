<!DOCTYPE html>
<html lang="tr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Erişim Engellendi (403) - {{ config('app.name', 'Epsilon CRM') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-100 font-sans text-slate-900 antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center pt-6 sm:pt-0">
            <div class="w-full max-w-md bg-white p-8 shadow-card overflow-hidden sm:rounded-2xl text-center border border-slate-200">
                <div class="flex justify-center mb-6">
                    <x-application-logo class="h-20 w-auto fill-current text-slate-500" />
                </div>

                <div class="mb-4 text-4xl font-bold text-rose-600">
                    403
                </div>

                <h2 class="mb-4 text-xl font-semibold text-slate-900">
                    Erişim İzniniz Yok
                </h2>

                <p class="mb-6 text-slate-600">
                    Üzgünüz, bu sayfayı görüntülemek için gerekli yetkiye sahip değilsiniz.
                    Bu alan sadece yönetici (Admin) kullanıcılar içindir.
                </p>

                <div class="flex items-center justify-center">
                    <a href="{{ route('dashboard') }}">
                        <x-primary-button>
                             {{ __('Ana Sayfaya Dön') }}
                        </x-primary-button>
                    </a>
                </div>
            </div>
        </div>
    </body>
</html>
