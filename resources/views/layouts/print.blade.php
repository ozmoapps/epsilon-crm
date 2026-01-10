<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dosya Yazdır')</title>

    @vite(['resources/css/app.css'])

    <style>
        @page {
            size: A4;
            margin: 12mm;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                background: white;
            }
            .doc-root {
                box-shadow: none;
                margin: 0;
                padding: 0;
                max-width: none;
            }
        }

        /* Common Classes for Print View Standard v1 (moved from preview.blade.php) */
        
        .doc-root {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            position: relative;
        }

        @media screen {
            body {
                background: #f3f4f6;
                padding-bottom: 50px;
            }
            .doc-root {
                margin-top: 2rem;
                padding: 12mm;
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            }
        }

        /* Typography */
        h1, h2, h3 { margin: 0 0 6px; }
        p { margin: 0 0 4px; }
        .muted { color: #6b7280; }
        
        /* Layout Structure */
        .header { 
            display: flex; 
            justify-content: space-between; 
            gap: 16px; 
            margin-bottom: 16px; 
            border-bottom: 1px solid #e5e7eb; 
            padding-bottom: 16px; 
        }
        
        .quote-meta table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 11px; 
        }
        .quote-meta td { 
            padding: 2px 0; 
            text-align: right; 
        }
        .quote-meta .muted {
            padding-right: 12px;
        }

        .info-grid { 
            display: flex; 
            gap: 16px; 
            margin-bottom: 16px; 
        }
        
        .info-block { 
            flex: 1; 
            border: 1px solid #e5e7eb; 
            padding: 10px; 
            border-radius: 6px; 
        }
        .info-block h3 {
            font-size: 11px; 
            text-transform: uppercase; 
            font-weight: bold; 
            border-bottom: 1px solid #f3f4f6; 
            padding-bottom: 4px; 
            margin-bottom: 6px; 
            color: #6b7280; 
        }
        .info-block p {
            font-size: 13px;
        }

        .section { 
            margin-bottom: 16px; 
        }
        .section-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        /* Table Styling */
        .doc-table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 12px; 
        }
        .doc-table th, .doc-table td { 
            border: 1px solid #e5e7eb; 
            padding: 6px 8px; 
            text-align: left; 
        }
        .doc-table th { 
            background: #f3f4f6; 
            font-size: 11px; 
            letter-spacing: 0.03em; 
            text-transform: uppercase; 
            font-weight: 600; 
        }
        .doc-table .text-right { text-align: right; }
        .doc-table .section-row { background: #f9fafb; font-weight: 600; }
        .doc-table .total-row td { font-weight: bold; background: #f3f4f6; }

        .payment-list { margin: 6px 0 0; padding-left: 16px; }
        .payment-list li { margin-bottom: 4px; font-size: 12px; }

        .terms { margin: 6px 0 0; padding-left: 18px; font-size: 11px; color: #374151; }
        .terms li { margin-bottom: 2px; }

        .footer { 
            margin-top: 24px; 
            font-size: 10px; 
            color: #6b7280; 
            text-align: center; 
            border-top: 1px solid #e5e7eb;
            padding-top: 12px;
        }
    </style>
</head>
<body class="font-sans antialiased text-gray-900">

    <!-- No-Print Toolbar -->
    <div class="no-print fixed top-0 left-0 right-0 z-50 bg-white border-b border-gray-200 px-4 py-3 flex justify-between items-center shadow-sm print:hidden">
        <div class="flex items-center gap-4">
            <h2 class="font-semibold text-gray-800">Baskı Önizleme</h2>
        </div>
        <div class="flex items-center gap-3">
            @if(request()->has('backUrl') || url()->previous())
                <a href="{{ request('backUrl', url()->previous()) }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Geri Dön
                </a>
            @endif
            <button onclick="window.print()" 
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Yazdır
            </button>
        </div>
    </div>
    
    <!-- Spacing for the fixed header on screen -->
    <div class="h-16 no-print"></div>

    <div class="doc-root">
        @yield('content')
    </div>

</body>
</html>
