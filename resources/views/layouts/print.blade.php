<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dosya Yazdır')</title>

    @vite(['resources/css/app.css'])

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap&subset=latin-ext" rel="stylesheet">

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

        /* Common Classes for Print View Standard v1 */
        
        body {
            font-family: 'Inter', sans-serif !important;
        }

        .doc-root {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            position: relative;
        }

        @media screen {
            body {
                background: #f1f5f9; /* slate-100 */
                padding-bottom: 50px;
            }
            .doc-root {
                margin-top: 2rem;
                padding: 12mm;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); /* heavier shadow */
            }
        }

        /* Typography */
        h1, h2, h3 { margin: 0 0 6px; }
        p { margin: 0 0 4px; }
        .muted { color: #64748b; } /* slate-500 */
        
        /* Layout Structure */
        .header { 
            display: flex; 
            justify-content: space-between; 
            gap: 16px; 
            margin-bottom: 16px; 
            border-bottom: 1px solid #e2e8f0; /* slate-200 */
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
            border: 1px solid #e2e8f0; /* slate-200 */
            padding: 10px; 
            border-radius: 12px; /* rounded-xl equivalent */
        }
        .info-block h3 {
            font-size: 11px; 
            text-transform: none; 
            font-weight: bold; 
            border-bottom: 1px solid #f1f5f9; /* slate-100 */
            padding-bottom: 4px; 
            margin-bottom: 6px; 
            color: #64748b; /* slate-500 */
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
            text-transform: none;
        }

        /* Table Styling */
        .doc-table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 12px; 
        }
        .doc-table th, .doc-table td { 
            border: 1px solid #e2e8f0; /* slate-200 */
            padding: 6px 8px; 
            text-align: left; 
        }
        .doc-table th { 
            background: #f8fafc; /* slate-50 */
            font-size: 11px; 
            letter-spacing: 0.03em; 
            text-transform: none; 
            font-weight: 600; 
        }
        .doc-table .text-right { text-align: right; }
        .doc-table .section-row { background: #f8fafc; font-weight: 600; } /* slate-50 */
        .doc-table .total-row td { font-weight: bold; background: #f1f5f9; } /* slate-100 */

        .payment-list { margin: 6px 0 0; padding-left: 16px; }
        .payment-list li { margin-bottom: 4px; font-size: 12px; }

        .terms { margin: 6px 0 0; padding-left: 18px; font-size: 11px; color: #334155; } /* slate-700 */
        .terms li { margin-bottom: 2px; }

        .footer { 
            margin-top: 24px; 
            font-size: 10px; 
            color: #64748b; /* slate-500 */
            text-align: center; 
            border-top: 1px solid #e2e8f0; /* slate-200 */
            padding-top: 12px;
        }
    </style>
</head>
<body class="font-sans antialiased text-slate-900">

    <!-- No-Print Toolbar -->
    <div class="no-print fixed top-0 left-0 right-0 z-50 bg-white border-b border-slate-200 px-4 py-3 flex justify-between items-center shadow-card print:hidden">
        <div class="flex items-center gap-4">
            <h2 class="font-semibold text-slate-800">Baskı Önizleme</h2>
        </div>
        <div class="flex items-center gap-3">
            @if(request()->has('backUrl') || url()->previous())
                <a href="{{ request('backUrl', url()->previous()) }}" 
                   class="inline-flex items-center px-4 py-2 border border-slate-300 shadow-soft text-sm font-medium rounded-xl text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-colors">
                    <svg class="-ml-1 mr-2 h-5 w-5 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Geri Dön
                </a>
            @endif
            <button onclick="window.print()" 
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-full text-white bg-slate-900 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 transition-colors">
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
