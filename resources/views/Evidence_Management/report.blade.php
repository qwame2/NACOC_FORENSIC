@php
    $authUser = auth()->user();
    $isEvidenceContext = true;
    $sidebarUnit = 'Evidence Management';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Evidence Management Unit | Central Exhibits &amp; Seized Property Report | NACOC Forensic</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Styles / Scripts: System Vite Asset Loader & Compiled CSS -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <!-- Tailwind CSS Local Asset Fallback & Config -->
    <script>
        const _origWarn = console.warn;
        console.warn = function(...args) {
            if (args[0] && typeof args[0] === 'string' && args[0].includes('tailwindcss')) return;
            _origWarn.apply(console, args);
        };
    </script>
    <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        finnova: {
                            purple: '#5c56df',
                            dark: '#181926',
                            darker: '#12131d',
                            card: '#2b2a5c',
                            light: '#f4f5fa'
                        }
                    },
                    spacing: {
                        '4.5': '1.125rem',
                        '18': '4.5rem',
                    },
                    boxShadow: {
                        'finnova': '0 20px 50px -15px rgba(24, 25, 38, 0.08)',
                        'finnova-dark': '0 25px 60px -15px rgba(10, 11, 20, 0.5)',
                        'tactical-glow': '0 0 50px rgba(16, 185, 129, 0.35)',
                        'xs': '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
                        '2xs': '0 1px 1px 0 rgba(0, 0, 0, 0.03)'
                    }
                }
            }
        };
    </script>

    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
        }
        .font-serif-header {
            font-family: 'Cinzel', Georgia, serif;
            letter-spacing: 0.04em;
        }
        .font-mono-code {
            font-family: 'JetBrains Mono', monospace;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Sidebar styling safeguard */
        aside.w-64 {
            width: 16rem !important;
            min-width: 16rem !important;
            max-width: 16rem !important;
            background-color: #f1f5f9 !important;
            color: #1e293b !important;
            display: flex !important;
            flex-direction: column !important;
            flex-shrink: 0 !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
            position: relative !important;
            z-index: 30 !important;
            overflow-x: hidden !important;
        }
        aside.w-64 svg.w-4\.5,
        aside.w-64 svg[class*="w-4.5"] {
            width: 1.125rem !important;
            height: 1.125rem !important;
            min-width: 1.125rem !important;
            min-height: 1.125rem !important;
        }

        /* Professional Print Media Formatting */
        @media print {
            aside, header, #reportFilterConsole, #paginationSection, #printActionButtons, #dossierModal, .no-print {
                display: none !important;
            }
            body, .report-print-container {
                background: white !important;
                color: black !important;
                font-size: 10pt !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .print-only-header {
                display: block !important;
            }
            table {
                width: 100% !important;
                border-collapse: collapse !important;
            }
            th, td {
                border: 1px solid #cbd5e1 !important;
                padding: 6px 8px !important;
            }
            tr {
                page-break-inside: avoid !important;
            }
        }
        .print-only-header {
            display: none;
        }
    </style>
</head>
<body class="min-h-screen flex antialiased selection:bg-emerald-500 selection:text-white">

    <div class="flex h-screen w-full overflow-hidden">
        
        <!-- SHARED SIDEBAR -->
        @include('Crime_Scene_Support.partials.sidebar')

        <!-- MAIN WORKSPACE CONTAINER -->
        <div class="flex-1 flex flex-col min-w-0 bg-slate-50 overflow-hidden report-print-container">

            <!-- TOP NAVBAR -->
            <header class="h-16 bg-white border-b border-slate-200/90 px-6 flex items-center justify-between shrink-0 z-20 shadow-2xs">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-700 font-black">
                        <i data-lucide="file-spreadsheet" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-sm font-black text-slate-900 font-mono-code uppercase tracking-wider">
                                Central Exhibits &amp; Seized Property Report
                            </h1>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold font-mono-code bg-emerald-100 text-emerald-800">
                                Evidence Management Unit
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 font-medium">
                            Official registry, custodial status, QR verification, and forensic commodity lodgement dossier.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5" id="printActionButtons">
                    <a href="{{ url('/Evidence_Management/vault') }}" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-colors">
                        <i data-lucide="layout-grid" class="w-3.5 h-3.5"></i>
                        <span>Exhibit Vault</span>
                    </a>

                    <button type="button" onclick="window.print()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white rounded-xl text-xs font-bold flex items-center gap-2 transition-all shadow-md shadow-emerald-900/30 cursor-pointer">
                        <i data-lucide="printer" class="w-4 h-4"></i>
                        <span>Print / Export PDF</span>
                    </button>
                </div>
            </header>

            <!-- PRINT-ONLY HEADER BANNER -->
            <div class="print-only-header p-6 border-b border-slate-300 text-center">
                <div class="text-xs uppercase tracking-widest font-extrabold text-slate-600">Republic of Ghana • Narcotics Control Commission</div>
                <h1 class="text-lg font-black text-slate-900 font-mono-code uppercase mt-1">Evidence Management Unit • Exhibits Master Audit Report</h1>
                <div class="text-[11px] text-slate-600 mt-1 font-mono-code">
                    Generated: {{ now()->format('d M Y, H:i') }} | Officer: {{ $authUser ? $authUser->name : 'System Generated' }} | Total Ledger Records: {{ $totalExhibitsCount }}
                </div>
            </div>

            <!-- SCROLLABLE PAGE BODY -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar">

                <!-- ==================================================== -->
                <!-- 1. OPERATIONAL KPI METRIC TILES                      -->
                <!-- ==================================================== -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    
                    <!-- Total Exhibits -->
                    <div class="p-4 bg-white border border-slate-200/90 rounded-2xl shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-400">
                            <span class="text-[10px] font-bold font-mono-code uppercase tracking-wider text-slate-500">Exhibits Lodged</span>
                            <span class="p-1.5 rounded-lg bg-emerald-50 text-emerald-700">
                                <i data-lucide="archive" class="w-4 h-4"></i>
                            </span>
                        </div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-2xl font-black text-slate-900 font-mono-code">{{ number_format($totalExhibitsCount) }}</span>
                            <span class="text-[10px] text-slate-500 font-medium">Items</span>
                        </div>
                        <span class="text-[10px] text-emerald-600 font-bold mt-1">Central Vault Inventory</span>
                    </div>

                    <!-- Seizure Valuation -->
                    <div class="p-4 bg-white border border-slate-200/90 rounded-2xl shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-400">
                            <span class="text-[10px] font-bold font-mono-code uppercase tracking-wider text-slate-500">Seizure Valuation</span>
                            <span class="p-1.5 rounded-lg bg-teal-50 text-teal-700">
                                <i data-lucide="coins" class="w-4 h-4"></i>
                            </span>
                        </div>
                        <div class="mt-2 flex items-baseline gap-1.5">
                            <span class="text-xs font-bold text-slate-500 font-mono-code">GH₵</span>
                            <span class="text-xl font-black text-teal-700 font-mono-code">{{ number_format($totalSeizureValue, 2) }}</span>
                        </div>
                        <span class="text-[10px] text-teal-600 font-bold mt-1">Estimated Asset Worth</span>
                    </div>

                    <!-- QR Tagged & Property IDs -->
                    <div class="p-4 bg-white border border-slate-200/90 rounded-2xl shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-400">
                            <span class="text-[10px] font-bold font-mono-code uppercase tracking-wider text-slate-500">QR Tagged</span>
                            <span class="p-1.5 rounded-lg bg-blue-50 text-blue-700">
                                <i data-lucide="qr-code" class="w-4 h-4"></i>
                            </span>
                        </div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-2xl font-black text-blue-700 font-mono-code">{{ number_format($taggedQrCount) }}</span>
                            <span class="text-[10px] text-slate-500 font-medium">Assigned</span>
                        </div>
                        <span class="text-[10px] text-blue-600 font-bold mt-1">Unique Property IDs</span>
                    </div>

                    <!-- Classified Drugs / Commodities -->
                    <div class="p-4 bg-white border border-slate-200/90 rounded-2xl shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-400">
                            <span class="text-[10px] font-bold font-mono-code uppercase tracking-wider text-slate-500">Classified Items</span>
                            <span class="p-1.5 rounded-lg bg-purple-50 text-purple-700">
                                <i data-lucide="microscope" class="w-4 h-4"></i>
                            </span>
                        </div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-2xl font-black text-purple-700 font-mono-code">{{ number_format($classifiedCount) }}</span>
                            <span class="text-[10px] text-slate-500 font-medium">Classified</span>
                        </div>
                        <span class="text-[10px] text-purple-600 font-bold mt-1">Forensic Schedules Filed</span>
                    </div>

                    <!-- Linked Cases & Suspects -->
                    <div class="p-4 bg-white border border-slate-200/90 rounded-2xl shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-400">
                            <span class="text-[10px] font-bold font-mono-code uppercase tracking-wider text-slate-500">Registered Cases</span>
                            <span class="p-1.5 rounded-lg bg-indigo-50 text-indigo-700">
                                <i data-lucide="folder-lock" class="w-4 h-4"></i>
                            </span>
                        </div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-2xl font-black text-indigo-700 font-mono-code">{{ number_format($totalCasesCount) }}</span>
                            <span class="text-[10px] text-slate-500 font-medium">Dockets</span>
                        </div>
                        <span class="text-[10px] text-indigo-600 font-bold mt-1">Linked Investigation Units</span>
                    </div>

                </div>

                <!-- ==================================================== -->
                <!-- 2. INTERACTIVE FILTER & SEARCH CONSOLE               -->
                <!-- ==================================================== -->
                <div class="p-5 bg-white border border-slate-200/90 rounded-2xl shadow-xs space-y-4" id="reportFilterConsole">
                    
                    <form method="GET" action="{{ url('/Evidence_Management/report') }}" id="reportFilterForm" class="space-y-4">
                        <input type="hidden" name="period" id="periodInput" value="{{ $period }}">

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                            <div>
                                <h2 class="text-xs font-black text-slate-900 font-mono-code uppercase tracking-wider flex items-center gap-1.5">
                                    <i data-lucide="sliders" class="w-3.5 h-3.5 text-emerald-600"></i>
                                    <span>Audit Report Filter Parameters</span>
                                </h2>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    Filter by commodity, property ID, case number, category, custodial status, and lodgement date window.
                                </p>
                            </div>

                            <!-- PERIOD PRESET BUTTONS: Weekly, Monthly, Annual, General -->
                            <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-xl text-xs font-bold font-mono-code shrink-0">
                                <button type="button" onclick="setReportPeriod('weekly')" class="console-period-btn px-3 py-1 rounded-lg transition-all {{ in_array(strtolower($period ?? ''), ['weekly', 'week']) ? 'bg-white text-emerald-800 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}" data-period="weekly">
                                    Weekly
                                </button>
                                <button type="button" onclick="setReportPeriod('monthly')" class="console-period-btn px-3 py-1 rounded-lg transition-all {{ in_array(strtolower($period ?? ''), ['monthly', 'month']) ? 'bg-white text-emerald-800 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}" data-period="monthly">
                                    Monthly
                                </button>
                                <button type="button" onclick="setReportPeriod('annual')" class="console-period-btn px-3 py-1 rounded-lg transition-all {{ in_array(strtolower($period ?? ''), ['annual', 'annually', 'year', 'yearly']) ? 'bg-white text-emerald-800 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}" data-period="annual">
                                    Annual
                                </button>
                                <button type="button" onclick="setReportPeriod('general')" class="console-period-btn px-3 py-1 rounded-lg transition-all {{ (in_array(strtolower($period ?? ''), ['all', 'general']) || empty($period)) ? 'bg-white text-emerald-800 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}" data-period="general">
                                    General
                                </button>
                            </div>
                        </div>

                        <!-- FILTER CONTROLS IN A UNIFIED ROW -->
                        <div class="flex flex-col xl:flex-row xl:items-end justify-between gap-3">
                            
                            <!-- CONTROLS: SEARCH + CATEGORY + STATUS + FROM + TO + RESET -->
                            <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end min-w-0">
                                
                                <!-- Search Bar -->
                                <div class="lg:col-span-3 space-y-1.5">
                                    <label for="searchInput" class="block text-[11px] font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                        Search Exhibit / Case / Suspect
                                    </label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 pointer-events-none">
                                            <i data-lucide="search" class="w-3.5 h-3.5"></i>
                                        </span>
                                        <input type="text" 
                                               name="search" 
                                               id="searchInput" 
                                               value="{{ $search }}" 
                                               placeholder="Search exhibit #, property ID, commodity, suspect..." 
                                               class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition-all">
                                    </div>
                                </div>

                                <!-- Category Selector -->
                                <div class="lg:col-span-2 space-y-1.5">
                                    <label for="categorySelect" class="block text-[11px] font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                        Category
                                    </label>
                                    <select name="category" id="categorySelect" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 transition-all cursor-pointer">
                                        <option value="all" {{ ($category === 'all' || empty($category)) ? 'selected' : '' }}>All Categories ({{ $totalExhibitsCount }})</option>
                                        <option value="drug" {{ str_contains(strtolower($category), 'drug') ? 'selected' : '' }}>Drugs &amp; Narcotics ({{ $drugsCount }})</option>
                                        <option value="weapon" {{ str_contains(strtolower($category), 'weapon') ? 'selected' : '' }}>Weapons &amp; Firearms ({{ $weaponsCount }})</option>
                                        <option value="vehicle" {{ str_contains(strtolower($category), 'vehicle') ? 'selected' : '' }}>Vehicles &amp; Transport ({{ $vehiclesCount }})</option>
                                        <option value="device" {{ str_contains(strtolower($category), 'device') ? 'selected' : '' }}>Digital Devices ({{ $devicesCount }})</option>
                                        <option value="money" {{ str_contains(strtolower($category), 'money') ? 'selected' : '' }}>Currency / Cash ({{ $currencyCount }})</option>
                                        <option value="general" {{ str_contains(strtolower($category), 'general') ? 'selected' : '' }}>General Physical Items</option>
                                    </select>
                                </div>

                                <!-- Action / Intake Status Selector -->
                                <div class="lg:col-span-2 space-y-1.5">
                                    <label for="actionStatusSelect" class="block text-[11px] font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                        Vault Status
                                    </label>
                                    <select name="action_status" id="actionStatusSelect" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 transition-all cursor-pointer">
                                        <option value="all" {{ ($actionStatus === 'all' || empty($actionStatus)) ? 'selected' : '' }}>All Statuses</option>
                                        <option value="completed" {{ $actionStatus === 'completed' ? 'selected' : '' }}>✓ Tagged &amp; Classified ({{ $classifiedCount }})</option>
                                        <option value="pending" {{ $actionStatus === 'pending' ? 'selected' : '' }}>⚠️ Needs QR / Classify ({{ $pendingCount }})</option>
                                        <option value="unassigned_qr" {{ $actionStatus === 'unassigned_qr' ? 'selected' : '' }}>🏷️ Unassigned QR Tag</option>
                                        <option value="unclassified" {{ $actionStatus === 'unclassified' ? 'selected' : '' }}>📋 Unclassified Exhibits</option>
                                    </select>
                                </div>

                                <!-- Date From -->
                                <div class="lg:col-span-2 space-y-1.5">
                                    <label for="fromDate" class="block text-[11px] font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                        From:
                                    </label>
                                    <input type="date" 
                                           name="from_date" 
                                           id="fromDate" 
                                           value="{{ $fromDate }}" 
                                           placeholder="mm/dd/yyyy"
                                           class="w-full px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 transition-all">
                                </div>

                                <!-- Date To -->
                                <div class="lg:col-span-2 space-y-1.5">
                                    <label for="toDate" class="block text-[11px] font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                        To:
                                    </label>
                                    <input type="date" 
                                           name="to_date" 
                                           id="toDate" 
                                           value="{{ $toDate }}" 
                                           placeholder="mm/dd/yyyy"
                                           class="w-full px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 transition-all">
                                </div>

                                <!-- Reset -->
                                <div class="lg:col-span-1 space-y-1.5">
                                    <a href="{{ url('/Evidence_Management/report') }}" 
                                       class="w-full block py-2 text-xs text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl font-bold font-mono-code transition-colors whitespace-nowrap cursor-pointer text-center" 
                                       title="Reset all filters">
                                        Reset
                                    </a>
                                </div>

                            </div>

                            <!-- RIGHT ACTION: GENERATE FILTERED PDF REPORT BUTTON -->
                            <div class="shrink-0 space-y-1.5">
                                <button type="button" 
                                        onclick="triggerGenerateFilteredPdf()" 
                                        class="w-full xl:w-auto px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white rounded-xl text-xs font-black flex items-center justify-center gap-2 transition-all shadow-md shadow-emerald-700/30 cursor-pointer whitespace-nowrap">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span id="generateBtnLabel">Generate Filtered PDF Report</span>
                                </button>
                            </div>

                        </div>

                        <!-- Secondary Row: Sort, Per Page, Actions -->
                        <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-100">
                            
                            <div class="flex items-center gap-2 flex-wrap">
                               

                                <!-- Per Page -->
                                <select name="per_page" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-semibold text-slate-700 focus:outline-none focus:border-emerald-500">
                                    <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25 per page</option>
                                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 per page</option>
                                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 per page</option>
                                    <option value="200" {{ $perPage == 200 ? 'selected' : '' }}>200 per page</option>
                                </select>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer">
                                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                                    <span>Apply Filters</span>
                                </button>
                            </div>

                        </div>
                    </form>

                </div>

                <!-- ==================================================== -->
                <!-- 3. EXHIBITS MASTER REPORT DATA TABLE                 -->
                <!-- ==================================================== -->
                <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden">
                    
                    <!-- Table Action Sub-Header -->
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center space-x-2">
                           
                            <span class="text-xs font-extrabold text-slate-800 font-mono-code uppercase tracking-wider">
                                Exhibit Table
                            </span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 font-mono-code font-bold">
                                {{ $exhibits->total() }} Records
                            </span>
                        </div>

                        <div class="text-xs text-slate-500 font-medium font-mono-code">
                            Showing {{ $exhibits->firstItem() ?? 0 }} - {{ $exhibits->lastItem() ?? 0 }} of {{ $exhibits->total() }}
                        </div>
                    </div>

                    @if($exhibits->isEmpty())
                        <!-- EMPTY STATE -->
                        <div class="p-12 text-center space-y-3">
                            <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400">
                                <i data-lucide="package-x" class="w-7 h-7"></i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-800">No Matching Exhibits Found</h3>
                            <p class="text-xs text-slate-500 max-w-md mx-auto">
                                No exhibit records matched your active filter parameters. Adjust your keyword search or date window.
                            </p>
                            <div>
                                <a href="{{ url('/Evidence_Management/report') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-colors">
                                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                                    <span>Clear Filters &amp; View All</span>
                                </a>
                            </div>
                        </div>
                    @else
                        <!-- DATA TABLE -->
                        <div class="overflow-x-auto custom-scrollbar">
                            <table class="w-full min-w-[1100px] text-left border-collapse text-xs">
                                <thead class="sticky top-0 z-10 bg-slate-100 border-b border-slate-200 text-slate-700 font-extrabold uppercase text-[10px] tracking-wider font-mono-code shadow-2xs select-none">
                                    <tr>
                                        <th scope="col" class="py-3.5 px-3 text-center w-12 whitespace-nowrap">#</th>
                                        <th scope="col" class="py-3.5 px-4 w-[11%] min-w-[130px] whitespace-nowrap">Property Tag &amp; QR</th>
                                        <th scope="col" class="py-3.5 px-4 w-[11%] min-w-[130px] whitespace-nowrap">Exhibit Number</th>
                                        <th scope="col" class="py-3.5 px-4 w-[18%] min-w-[180px] whitespace-nowrap">Commodity / Property Name</th>
                                        <th scope="col" class="py-3.5 px-4 w-[12%] min-w-[130px] whitespace-nowrap">Category &amp; Specs</th>
                                        <th scope="col" class="py-3.5 px-4 w-[16%] min-w-[170px] whitespace-nowrap">Docket &amp; Suspect(s)</th>
                                        <th scope="col" class="py-3.5 px-4 w-[9%] min-w-[105px] text-center whitespace-nowrap">Date Seized</th>
                                        <th scope="col" class="py-3.5 px-4 w-[10%] min-w-[120px] text-right whitespace-nowrap">Est. Value (GH₵)</th>
                                        <th scope="col" class="py-3.5 px-4 w-[6%] min-w-[85px] text-center whitespace-nowrap">Status</th>
                                        <th scope="col" class="py-3.5 px-4 w-[5%] min-w-[65px] text-center whitespace-nowrap no-print">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                                    @foreach($exhibits as $index => $exhibit)
                                        @php
                                            $val = (float)($exhibit->seizure_value ?? 0);
                                            $categoryLower = strtolower($exhibit->exhibit_category ?? 'general');

                                            $catBadge = match(true) {
                                                str_contains($categoryLower, 'drug') => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                                str_contains($categoryLower, 'weapon') => 'bg-amber-100 text-amber-800 border-amber-200',
                                                str_contains($categoryLower, 'vehic') => 'bg-blue-100 text-blue-800 border-blue-200',
                                                str_contains($categoryLower, 'devic') => 'bg-purple-100 text-purple-800 border-purple-200',
                                                str_contains($categoryLower, 'money') || str_contains($categoryLower, 'cash') => 'bg-teal-100 text-teal-800 border-teal-200',
                                                default => 'bg-slate-100 text-slate-700 border-slate-200'
                                            };

                                            $suspectDisplay = 'Unlinked / No Suspect';
                                            if ($exhibit->singleSuspectCase) {
                                                $suspectDisplay = $exhibit->singleSuspectCase->suspect_name;
                                            } elseif ($exhibit->suspect) {
                                                $suspectDisplay = $exhibit->suspect->suspect_name;
                                            } elseif ($exhibit->multipleSuspectCase && $exhibit->multipleSuspectCase->suspects->isNotEmpty()) {
                                                $suspectDisplay = $exhibit->multipleSuspectCase->suspects->first()->suspect_name;
                                                if ($exhibit->multipleSuspectCase->suspects->count() > 1) {
                                                    $suspectDisplay .= ' (+'.($exhibit->multipleSuspectCase->suspects->count() - 1).')';
                                                }
                                            }

                                            $caseNum = $exhibit->singleSuspectCase->case_number ?? $exhibit->multipleSuspectCase->case_number ?? 'No Case Assigned';
                                        @endphp
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            
                                            <!-- Index -->
                                            <td class="py-3 px-3 text-center font-mono-code text-[11px] text-slate-400">
                                                {{ ($exhibits->currentPage() - 1) * $exhibits->perPage() + $index + 1 }}
                                            </td>

                                            <!-- Property ID & QR Tag -->
                                            <td class="py-3 px-4 font-mono-code whitespace-nowrap">
                                                @if($exhibit->property_id)
                                                    <span class="font-bold text-slate-900 block text-[11px]">{{ $exhibit->property_id }}</span>
                                                    <span class="text-[9px] text-emerald-600 font-bold uppercase tracking-wider">Verified Tag</span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-md text-[9px] font-extrabold uppercase bg-amber-100 text-amber-800">
                                                        Unassigned
                                                    </span>
                                                @endif
                                            </td>

                                            <!-- Exhibit Number -->
                                            <td class="py-3 px-4 font-mono-code font-bold text-emerald-700 text-xs whitespace-nowrap">
                                                {{ $exhibit->exhibit_number }}
                                            </td>

                                            <!-- Commodity Name -->
                                            <td class="py-3 px-4">
                                                <div class="font-bold text-slate-900">{{ $exhibit->commodity_name }}</div>
                                                <div class="text-[10px] text-slate-400 truncate max-w-[220px]" title="{{ $exhibit->packaging_type }}">
                                                    {{ $exhibit->packaging_type ?: 'Standard Secure Packaging' }}
                                                </div>
                                            </td>

                                            <!-- Category & Classification -->
                                            <td class="py-3 px-4">
                                                <div class="space-y-1">
                                                    <span class="inline-block px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase border {{ $catBadge }}">
                                                        {{ $exhibit->exhibit_category ?? 'General' }}
                                                    </span>
                                                    @if($exhibit->drug_classification)
                                                        <div class="text-[10px] text-emerald-800 font-semibold font-mono-code">
                                                            {{ $exhibit->drug_classification }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>

                                            <!-- Case & Suspect -->
                                            <td class="py-3 px-4">
                                                <div class="font-bold text-slate-900 truncate max-w-[190px]" title="{{ $suspectDisplay }}">
                                                    {{ $suspectDisplay }}
                                                </div>
                                                <div class="font-mono-code text-[10px] text-slate-500">
                                                    {{ $caseNum }}
                                                </div>
                                            </td>

                                            <!-- Date Seized -->
                                            <td class="py-3 px-4 text-center font-mono-code text-[11px] text-slate-600 whitespace-nowrap">
                                                {{ $exhibit->date_seized ? \Carbon\Carbon::parse($exhibit->date_seized)->format('d M Y') : ($exhibit->created_at ? $exhibit->created_at->format('d M Y') : 'N/A') }}
                                            </td>

                                            <!-- Seizure Valuation -->
                                            <td class="py-3 px-4 text-right font-mono-code font-bold text-slate-900 text-xs whitespace-nowrap">
                                                {{ $exhibit->seizure_value ? number_format((float)$exhibit->seizure_value, 2) : '0.00' }}
                                            </td>

                                            <!-- Custodial Status -->
                                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold font-mono-code bg-slate-100 text-slate-700">
                                                    {{ $exhibit->custodial_status ?? 'Seized' }}
                                                </span>
                                            </td>

                                            <!-- Actions -->
                                            <td class="py-3 px-4 text-center no-print whitespace-nowrap">
                                                <button type="button" 
                                                        onclick="openReportExhibitDetails({{ json_encode($exhibit) }})" 
                                                        class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors cursor-pointer inline-flex items-center justify-center" 
                                                        title="Inspect Exhibit Details">
                                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                </button>
                                            </td>

                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Section -->
                        <div class="p-4 border-t border-slate-200 bg-white flex flex-col sm:flex-row items-center justify-between gap-3" id="paginationSection">
                            <div class="text-xs text-slate-500 font-medium">
                                Showing <span class="font-bold text-slate-900">{{ $exhibits->firstItem() ?? 0 }}</span> to 
                                <span class="font-bold text-slate-900">{{ $exhibits->lastItem() ?? 0 }}</span> of 
                                <span class="font-bold text-slate-900">{{ $exhibits->total() }}</span> total exhibits
                            </div>
                            <div>
                                {{ $exhibits->links() }}
                            </div>
                        </div>

                    @endif

                </div>

            </div>
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- 4. EXPANDED DETAIL INSPECTION MODAL                        -->
    <!-- ========================================================== -->
    <div id="dossierModal" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 overflow-y-auto no-print">
        <div class="bg-white rounded-3xl max-w-4xl w-full border border-slate-200 shadow-2xl overflow-hidden flex flex-col">
            
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between shrink-0 border-b border-slate-800">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center">
                        <i data-lucide="archive" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-extrabold tracking-tight text-white" id="modalCommodityTitle">Exhibit Dossier</h2>
                        <div class="flex items-center space-x-2 text-[11px] text-slate-400 font-mono-code">
                            <span class="text-emerald-400 font-bold" id="modalExhibitNum">EXH-0000</span>
                            <span>•</span>
                            <span id="modalPropertyId">Property ID: Unassigned</span>
                        </div>
                    </div>
                </div>
                <button type="button" onclick="closeReportDossierModal()" class="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="p-6 space-y-4 text-xs font-medium text-slate-700 bg-slate-50/50">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 font-mono-code">
                    <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-2xs">
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Category</span>
                        <span class="text-xs font-extrabold text-slate-900 block mt-0.5" id="modalCategory">General</span>
                    </div>
                    <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-2xs">
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Classification</span>
                        <span class="text-xs font-extrabold text-emerald-700 block mt-0.5" id="modalClassification">N/A</span>
                    </div>
                    <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-2xs">
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Quantity / Weight</span>
                        <span class="text-xs font-extrabold text-slate-900 block mt-0.5" id="modalWeight">N/A</span>
                    </div>
                    <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-2xs">
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Seizure Valuation</span>
                        <span class="text-xs font-extrabold text-teal-700 block mt-0.5" id="modalValue">GH₵ 0.00</span>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200 space-y-2">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <span class="text-slate-500 font-semibold">Packaging Specification:</span>
                        <span class="font-bold text-slate-800" id="modalPackaging">N/A</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <span class="text-slate-500 font-semibold">Date of Seizure / Lodgement:</span>
                        <span class="font-mono-code font-bold text-slate-800" id="modalDate">N/A</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <span class="text-slate-500 font-semibold">Custodial Status:</span>
                        <span class="font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-full text-[10px]" id="modalCustody">Seized</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-semibold">Lodgement Remarks:</span>
                        <span class="font-medium text-slate-700 max-w-sm text-right" id="modalRemarks">No remarks registered</span>
                    </div>
                </div>
            </div>

            <div class="px-6 py-3 bg-white border-t border-slate-200 flex items-center justify-end">
                <button type="button" onclick="closeReportDossierModal()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-xs rounded-xl transition-colors cursor-pointer">
                    Close
                </button>
            </div>

        </div>
    </div>

    <!-- JavaScript Interactions -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });

        function setReportPeriod(period) {
            document.getElementById('periodInput').value = period;
            document.getElementById('fromDate').value = '';
            document.getElementById('toDate').value = '';
            document.getElementById('reportFilterForm').submit();
        }

        function triggerGenerateFilteredPdf() {
            const period = document.getElementById('periodInput')?.value || 'general';
            const search = document.getElementById('searchInput')?.value || '';
            const category = document.getElementById('categorySelect')?.value || 'all';
            const actionStatus = document.getElementById('actionStatusSelect')?.value || 'all';
            const from = document.getElementById('fromDate')?.value || '';
            const to = document.getElementById('toDate')?.value || '';
            const sort = document.querySelector('select[name="sort"]')?.value || 'newest';

            const params = new URLSearchParams();
            if (period && period !== 'general') params.set('period', period);
            if (search) params.set('search', search);
            if (category && category !== 'all') params.set('category', category);
            if (actionStatus && actionStatus !== 'all') params.set('action_status', actionStatus);
            if (from) params.set('from_date', from);
            if (to) params.set('to_date', to);
            if (sort && sort !== 'newest') params.set('sort', sort);

            const targetUrl = '{{ url("/Evidence_Management/report_pdf") }}?' + params.toString();
            window.open(targetUrl, '_blank');
        }

        function openReportExhibitDetails(exhibit) {
            document.getElementById('modalCommodityTitle').textContent = exhibit.commodity_name || 'Exhibit Details';
            document.getElementById('modalExhibitNum').textContent = exhibit.exhibit_number || 'N/A';
            document.getElementById('modalPropertyId').textContent = 'Property ID: ' + (exhibit.property_id || 'Unassigned');
            document.getElementById('modalCategory').textContent = exhibit.exhibit_category || 'General';
            document.getElementById('modalClassification').textContent = exhibit.drug_classification || exhibit.exhibit_type || 'Unclassified';
            
            let weightText = (exhibit.quantity || 1) + ' ' + (exhibit.unit_of_measure || 'units');
            if (exhibit.gross_weight || exhibit.net_weight) {
                weightText += ' (' + (exhibit.gross_weight || exhibit.net_weight) + ' kg)';
            }
            document.getElementById('modalWeight').textContent = weightText;
            document.getElementById('modalValue').textContent = 'GH₵ ' + (exhibit.seizure_value ? Number(exhibit.seizure_value).toLocaleString('en-US', { minimumFractionDigits: 2 }) : '0.00');
            document.getElementById('modalPackaging').textContent = exhibit.packaging_type || 'Standard Packaging';
            document.getElementById('modalDate').textContent = exhibit.date_seized ? new Date(exhibit.date_seized).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : 'N/A';
            document.getElementById('modalCustody').textContent = exhibit.custodial_status || 'Seized';
            document.getElementById('modalRemarks').textContent = exhibit.exhibit_remark || 'No remarks recorded';

            document.getElementById('dossierModal').classList.remove('hidden');
        }

        function closeReportDossierModal() {
            document.getElementById('dossierModal').classList.add('hidden');
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeReportDossierModal();
        });

        document.getElementById('dossierModal')?.addEventListener('click', (e) => {
            if (e.target.id === 'dossierModal') closeReportDossierModal();
        });
    </script>
</body>
</html>
