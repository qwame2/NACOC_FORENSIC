@php
    $authUser = auth()->user();
    $isLabAnalysis = true;
    $sidebarUnit = 'Lab Analysis';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Laboratory Analysis Unit | Forensic Drug Report &amp; Suspect Register | NACOC Forensic</title>

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

        /* Direct aside bar styling safeguard */
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
    </style>
</head>
<body class="min-h-screen flex antialiased selection:bg-emerald-500 selection:text-white">

    <div class="flex h-screen w-full overflow-hidden">
        
        <!-- SHARED SIDEBAR -->
        @include('Crime_Scene_Support.partials.sidebar')

        <!-- MAIN WORKSPACE CONTAINER -->
        <div class="flex-1 flex flex-col min-w-0 bg-slate-50 overflow-hidden">

            <!-- TOP NAVBAR -->
            <header class="h-16 bg-white border-b border-slate-200/90 px-6 flex items-center justify-between shrink-0 z-20 shadow-2xs">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-700 font-black">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-sm font-black text-slate-900 font-mono-code uppercase tracking-wider">
                                Laboratory Analysis Report &bull; LABORATORY ANALYSIS UNIT
                            </h1>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold font-mono-code bg-emerald-100 text-emerald-800">
                                Forensic Drug &amp; Suspect Register
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 font-medium">
                            Generate laboratory analysis reports, forensic drug reports, and suspect registers.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" onclick="triggerMainGeneratePdf()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white rounded-xl text-xs font-bold flex items-center gap-2 transition-all shadow-md shadow-emerald-900/30 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        <span>Generate PDF Report</span>
                    </button>
                </div>
            </header>

            <!-- SCROLLABLE PAGE BODY -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar">

                <!-- 1. OPERATIONAL KPI METRIC TILES -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div class="p-4 bg-white border border-slate-200/90 rounded-2xl shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-400">
                            <span class="text-[10px] font-bold font-mono-code uppercase tracking-wider text-slate-500">Seizure Operations</span>
                            <span class="p-1.5 rounded-lg bg-emerald-50 text-emerald-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            </span>
                        </div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-2xl font-black text-slate-900 font-mono-code" id="kpiTotalSeizures">{{ count($baselineSeizures) }}</span>
                            <span class="text-[10px] text-slate-500 font-medium">Logged</span>
                        </div>
                        <span class="text-[10px] text-emerald-600 font-bold mt-1">Chemical Analysis Underway</span>
                    </div>

                    <div class="p-4 bg-white border border-slate-200/90 rounded-2xl shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-400">
                            <span class="text-[10px] font-bold font-mono-code uppercase tracking-wider text-slate-500">Suspects Identified</span>
                            <span class="p-1.5 rounded-lg bg-indigo-50 text-indigo-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </span>
                        </div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-2xl font-black text-slate-900 font-mono-code" id="kpiTotalSuspects">{{ $totalSuspectsCount }}</span>
                            <span class="text-[10px] text-slate-500 font-medium">Linked to Narcotics</span>
                        </div>
                        <span class="text-[10px] text-indigo-600 font-bold mt-1">Registered Dockets</span>
                    </div>

                    <div class="p-4 bg-white border border-slate-200/90 rounded-2xl shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-400">
                            <span class="text-[10px] font-bold font-mono-code uppercase tracking-wider text-slate-500">Suspects At Large</span>
                            <span class="p-1.5 rounded-lg bg-amber-50 text-amber-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </span>
                        </div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-2xl font-black text-amber-700 font-mono-code">{{ $atLargeCount }}</span>
                            <span class="text-[10px] text-slate-500 font-medium">Warrants Issued</span>
                        </div>
                        <span class="text-[10px] text-amber-600 font-bold mt-1">Active Tracing Protocols</span>
                    </div>

                    <div class="p-4 bg-white border border-slate-200/90 rounded-2xl shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-400">
                            <span class="text-[10px] font-bold font-mono-code uppercase tracking-wider text-slate-500">Urine Drug Screenings</span>
                            <span class="p-1.5 rounded-lg bg-teal-50 text-teal-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                            </span>
                        </div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-2xl font-black text-teal-700 font-mono-code">{{ count($drugScreeningRecords) }}</span>
                            <span class="text-[10px] text-slate-500 font-medium">Assays</span>
                        </div>
                        <span class="text-[10px] text-teal-600 font-bold mt-1">Toxicology Registered</span>
                    </div>

                    <div class="p-4 bg-white border border-slate-200/90 rounded-2xl shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-400">
                            <span class="text-[10px] font-bold font-mono-code uppercase tracking-wider text-slate-500">GSA Submissions</span>
                            <span class="p-1.5 rounded-lg bg-purple-50 text-purple-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                            </span>
                        </div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-2xl font-black text-purple-700 font-mono-code">{{ count($gsaSubmissions) }}</span>
                            <span class="text-[10px] text-slate-500 font-medium">Transferred</span>
                        </div>
                        <span class="text-[10px] text-purple-600 font-bold mt-1">External Lab Standards</span>
                    </div>
                </div>


                <!-- 2. INTERACTIVE REPORT GENERATION & FILTER CONSOLE -->
                <div class="p-5 bg-white border border-slate-200/90 rounded-2xl shadow-xs space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                        <div>
                            <h2 class="text-xs font-black text-slate-900 font-mono-code uppercase tracking-wider">
                                Report Generation &amp; Filter Parameters
                            </h2>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Select single suspect or consolidated parameters. Clicking Generate will produce the official formatted PDF report with cover page.
                            </p>
                        </div>

                        <!-- PERIOD PRESET BUTTONS -->
                        <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-xl text-xs font-bold font-mono-code">
                            <button type="button" onclick="setConsolePeriod('weekly')" class="console-period-btn px-3 py-1 rounded-lg transition-all {{ in_array($rawPeriod ?? '', ['weekly', 'week']) ? 'bg-white text-emerald-800 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}" data-period="weekly">
                                Weekly
                            </button>
                            <button type="button" onclick="setConsolePeriod('monthly')" class="console-period-btn px-3 py-1 rounded-lg transition-all {{ in_array($rawPeriod ?? '', ['monthly', 'month']) ? 'bg-white text-emerald-800 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}" data-period="monthly">
                                Monthly
                            </button>
                            <button type="button" onclick="setConsolePeriod('annual')" class="console-period-btn px-3 py-1 rounded-lg transition-all {{ in_array($rawPeriod ?? '', ['annual', 'annually', 'yearly', 'year']) ? 'bg-white text-emerald-800 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}" data-period="annual">
                                Annual
                            </button>
                            <button type="button" onclick="setConsolePeriod('general')" class="console-period-btn px-3 py-1 rounded-lg transition-all {{ in_array($rawPeriod ?? '', ['all', 'general']) ? 'bg-white text-emerald-800 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}" data-period="general">
                                General
                            </button>
                        </div>
                    </div>

                    <!-- FILTER INPUT CONTROLS IN A SINGLE UNIFIED ROW -->
                    <div class="flex flex-col xl:flex-row xl:items-end justify-between gap-3">
                        
                        <!-- LEFT CONTROLS: TARGET SUSPECT + LIVE SEARCH + FROM + TO + RESET -->
                        <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end min-w-0">
                            
                            <!-- 1. TARGET SUSPECT SELECTOR -->
                            <div class="lg:col-span-3 space-y-1.5">
                                <label for="consoleSuspectSelect" class="block text-[11px] font-extrabold text-slate-700 font-mono-code uppercase tracking-wider truncate">
                                    Target Suspect(s) <span class="text-emerald-600">(Single)</span>
                                </label>
                                <select id="consoleSuspectSelect" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer" onchange="applyConsoleFilter()">
                                    <option value="" {{ empty($selectedSuspect) ? 'selected' : '' }}>— All Suspects (Consolidated Report) —</option>
                                    @foreach($allSuspectOptions as $suspectOpt)
                                        <option value="{{ $suspectOpt }}" {{ ($selectedSuspect ?? '') === $suspectOpt ? 'selected' : '' }}>
                                            {{ $suspectOpt }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- 2. LIVE SEARCH KEYWORD -->
                            <div class="lg:col-span-4 space-y-1.5">
                                <label for="consoleSearchInput" class="block text-[11px] font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                    Live Search Keyword
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 pointer-events-none">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    </span>
                                    <input type="text" id="consoleSearchInput" value="{{ $searchQuery ?? '' }}" placeholder="Search name, case ref, remarks..." class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all" oninput="applyConsoleFilter()">
                                </div>
                            </div>

                            <!-- 3. FROM DATE -->
                            <div class="lg:col-span-2 space-y-1.5">
                                <label for="consoleFromDate" class="block text-[11px] font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                    From:
                                </label>
                                <input type="date" id="consoleFromDate" value="{{ $fromDate ?? '' }}" class="w-full px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:border-emerald-600" onchange="applyConsoleFilter()">
                            </div>

                            <!-- 4. TO DATE -->
                            <div class="lg:col-span-2 space-y-1.5">
                                <label for="consoleToDate" class="block text-[11px] font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                    To:
                                </label>
                                <input type="date" id="consoleToDate" value="{{ $toDate ?? '' }}" class="w-full px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:border-emerald-600" onchange="applyConsoleFilter()">
                            </div>

                            <!-- 5. RESET FILTERS -->
                            <div class="lg:col-span-1 space-y-1.5">
                                <button type="button" onclick="resetConsoleFilters()" class="w-full px-2 py-2 text-xs text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl font-bold font-mono-code transition-colors whitespace-nowrap cursor-pointer text-center">
                                    Reset
                                </button>
                            </div>

                        </div>

                        <!-- RIGHT ACTION: GENERATE FILTERED PDF REPORT BUTTON -->
                        <div class="shrink-0 space-y-1.5">
                            <button type="button" onclick="triggerMainGeneratePdf()" class="w-full xl:w-auto px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white rounded-xl text-xs font-black flex items-center justify-center gap-2 transition-all shadow-md shadow-emerald-700/30 cursor-pointer whitespace-nowrap">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span id="generateBtnLabel">Generate Filtered PDF Report</span>
                            </button>
                        </div>

                    </div>
                </div>


                <!-- 3. MAIN DATA TABS & DRUG SUSPECT DIRECTORY -->
                <div class="bg-white border border-slate-200/90 rounded-2xl shadow-xs overflow-hidden">
                    
                    <!-- TAB BAR -->
                    <div class="flex items-center justify-between border-b border-slate-200 px-6 pt-3 bg-slate-50/70">
                        <div class="flex items-center gap-6 text-xs font-extrabold font-mono-code">
                            <button type="button" onclick="switchActiveTab('seizures')" id="tabBtnSeizures" class="pb-3 border-b-2 border-emerald-600 text-emerald-800 flex items-center gap-2 transition-all">
                                <span>Drug Seizures &amp; Suspects</span>
                                <span class="px-2 py-0.5 rounded-full text-[9px] bg-emerald-100 text-emerald-800" id="badgeSeizuresCount">{{ count($filteredSeizures) }}</span>
                            </button>
                            <button type="button" onclick="switchActiveTab('screenings')" id="tabBtnScreenings" class="pb-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center gap-2 transition-all">
                                <span>Suspect Drug Screenings (Urine)</span>
                                <span class="px-2 py-0.5 rounded-full text-[9px] bg-teal-100 text-teal-800">{{ count($drugScreeningRecords) }}</span>
                            </button>
                            <button type="button" onclick="switchActiveTab('gsa')" id="tabBtnGsa" class="pb-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center gap-2 transition-all">
                                <span>GSA External Submissions</span>
                                <span class="px-2 py-0.5 rounded-full text-[9px] bg-indigo-100 text-indigo-800">{{ count($gsaSubmissions) }}</span>
                            </button>
                        </div>

                        <div class="pb-3 text-right text-[11px] font-mono-code text-slate-500">
                            <span>Showing <span id="visibleEntriesCount">{{ count($filteredSeizures) }}</span> items</span>
                        </div>
                    </div>

                    <!-- TAB PANE 1: DRUG SEIZURES & SUSPECT DIRECTORY -->
                    <div id="paneSeizures" class="p-6 space-y-4">
                        <div class="flex items-center justify-between pb-1">
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 font-mono-code">
                                    Preliminary Forensic Examination &amp; Drug Testing Log
                                </h3>
                                <p class="text-[11px] text-slate-500">
                                    Official forensic analysis registry for all single and multiple suspect drug cases.
                                </p>
                            </div>
                        </div>
                        <div class="overflow-x-auto rounded-xl border border-slate-200 shadow-2xs">
                            <table class="w-full text-left border-collapse text-xs" id="directorySuspectTable">
                                <thead>
                                    <tr class="bg-slate-100/90 border-b border-slate-200 text-slate-800 font-bold uppercase tracking-wider font-mono-code text-[10px]">
                                        <th class="py-3 px-4">Drug Seizures &amp; Suspects</th>
                                        <th class="py-3 px-3">Date &amp; Source</th>
                                        <th class="py-3 px-3">Exhibit Information</th>
                                        <th class="py-3 px-3">Quantity &amp; Weight</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-slate-800" id="seizuresTableBody">
                                    @forelse($filteredSeizures as $item)
                                        @php
                                            $firstSuspect = $item['primary_suspect'] ?? ($item['suspects'][0]['name'] ?? 'Suspect');
                                            $allSuspectNamesLower = strtolower(implode(' ', array_filter(array_column($item['suspects'], 'name'))));
                                            $isMultipleCase = !empty($item['is_multiple']) || count($item['suspects']) > 1;
                                        @endphp
                                        <tr class="directory-row hover:bg-slate-50/80 transition-colors"
                                            data-suspect="{{ $allSuspectNamesLower }} {{ strtolower($item['suspects_display']) }}"
                                            data-primary="{{ strtolower($firstSuspect) }}"
                                            data-source="{{ strtolower($item['source']) }}"
                                            data-exhibit="{{ strtolower($item['exhibit_type']) }}"
                                            data-date="{{ $item['date'] }}"
                                            data-remarks="{{ strtolower($item['status_remarks']) }}"
                                            data-suspects-count="{{ count($item['suspects']) }}">
                                            
                                            <!-- DRUG SEIZURES & SUSPECTS (SINGLE & MULTIPLE CASE SUSPECTS: OVERLAPPING IMAGES & NAMES) -->
                                            <td class="py-3 px-4 align-top">
                                                @php
                                                    $suspectsList = $item['suspects'] ?? [];
                                                    $namesList = array_map(function($s) {
                                                        return trim($s['name'] ?? ($s['suspect_name'] ?? 'Suspect'));
                                                    }, $suspectsList);
                                                    $namesDisplay = implode(', ', array_filter($namesList));
                                                @endphp
                                                <div class="flex items-center gap-3">
                                                    <!-- OVERLAPPING AVATAR IMAGES -->
                                                    <div class="flex items-center {{ count($suspectsList) > 1 ? '-space-x-2.5' : '' }} shrink-0">
                                                        @foreach($suspectsList as $sIdx => $s)
                                                            @php
                                                                $rawMug = $s['mugshot_front'] ?? ($s['mugshot'] ?? null);
                                                                $sMug = null;
                                                                if (!empty($rawMug)) {
                                                                    $rawMug = trim((string)$rawMug);
                                                                    $sMug = (!str_starts_with($rawMug, 'data:image') && !str_starts_with($rawMug, 'http://') && !str_starts_with($rawMug, 'https://') && !str_starts_with($rawMug, '/'))
                                                                        ? asset($rawMug)
                                                                        : $rawMug;
                                                                }
                                                                $sFullName = $s['name'] ?? ($s['suspect_name'] ?? 'Suspect');
                                                                $sInit = strtoupper(substr($sFullName, 0, 2));
                                                            @endphp
                                                            <div class="relative w-8 h-8 rounded-full border-2 border-white bg-slate-100 overflow-hidden shadow-xs flex items-center justify-center shrink-0 hover:z-30 transition-transform hover:scale-105"
                                                                 style="z-index: {{ 20 - $sIdx }};"
                                                                 title="{{ $sFullName }}">
                                                                @if(!empty($sMug))
                                                                    <img src="{{ $sMug }}" alt="{{ $sFullName }}" class="w-full h-full object-cover">
                                                                @else
                                                                    <span class="text-emerald-800 font-black text-[10px] font-mono-code">{{ $sInit }}</span>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>

                                                    <!-- NAMES NEXT (e.g. "a, b") -->
                                                    <div class="min-w-0">
                                                        <span class="font-extrabold text-slate-900 text-xs font-mono-code leading-snug">
                                                            {{ $namesDisplay ?: 'Suspect' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- DATE & SOURCE -->
                                            <td class="py-3 px-3 align-top whitespace-nowrap">
                                                <div class="font-mono-code font-bold text-slate-800 text-[11px]">
                                                    {{ $item['date_display'] }}
                                                </div>
                                                <div class="font-bold text-slate-700 text-xs mt-0.5">
                                                    {{ $item['source_of_exhibits'] ?? ($item['source'] ?? 'N/A') }}
                                                </div>
                                                <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[8px] font-bold font-mono-code bg-slate-100 text-slate-600 border border-slate-200">
                                                    {{ $item['source_badge'] ?? 'PORT POST' }}
                                                </span>
                                            </td>

                                            <!-- EXHIBIT INFORMATION -->
                                            <td class="py-3 px-3 align-top">
                                                @if(!empty($item['lab_exhibit_no']) || !empty($item['exhibit_mark']))
                                                    <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold font-mono-code bg-indigo-50 text-indigo-700 border border-indigo-200 mb-1">
                                                        Exhibit {{ $item['lab_exhibit_no'] ?? $item['exhibit_mark'] }}
                                                    </span>
                                                @endif
                                                <span class="font-extrabold text-slate-900 block text-xs">
                                                    {{ $item['exhibit_type'] }}
                                                </span>
                                                @if(!empty($item['substance_name']) && $item['substance_name'] !== $item['exhibit_type'])
                                                    <span class="text-[10px] text-slate-500 font-medium block mt-0.5">
                                                        {{ $item['substance_name'] }}
                                                    </span>
                                                @endif
                                                <span class="text-[9px] text-slate-400 font-mono-code mt-0.5 block">
                                                    Lab No: {{ $item['lab_number'] }}
                                                </span>
                                            </td>

                                            <!-- QUANTITY & WEIGHT -->
                                            <td class="py-3 px-3 align-top whitespace-nowrap">
                                                @php
                                                    $displayQty = $item['quantity'] ?? '-';
                                                    $pkg = $item['package_type'] ?? ($item['packaging'] ?? null);
                                                    if ($displayQty !== '-' && !empty($pkg) && !str_contains(strtolower($displayQty), strtolower($pkg))) {
                                                        $displayQty = trim($displayQty . ' ' . $pkg);
                                                    }
                                                @endphp
                                                <div class="font-mono-code font-bold text-slate-900 text-xs">
                                                    {{ $displayQty }}
                                                </div>
                                                <div class="font-mono-code text-[11px] text-slate-600">
                                                    {{ $item['weight'] ?? '-' }}
                                                </div>
                                            </td>

                                        </tr>
                                    @empty
                                        <tr id="emptyDirectoryRow">
                                            <td colspan="4" class="py-12 text-center text-slate-500 font-mono-code text-xs">
                                                <div class="flex flex-col items-center justify-center space-y-2">
                                                    <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L5.6 15.12a2 2 0 00-1.18.125l-.26.104a2 2 0 00-1.16 1.861v1.895a2 2 0 002 2h14a2 2 0 002-2v-1.895a2 2 0 00-1.16-1.861l-.412-.165z"/></svg>
                                                    <p class="font-bold text-slate-700">No drug cases or exhibits found</p>
                                                    <p class="text-[11px] text-slate-400">No records matching the selected filter criteria or reporting timeframe.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if(method_exists($filteredSeizures, 'links') && $filteredSeizures->hasPages())
                            <div class="border-t border-slate-200">
                                {{ $filteredSeizures->links() }}
                            </div>
                        @endif
                    </div>

                    <!-- TAB PANE 2: SUSPECT URINE DRUG SCREENINGS -->
                    <div id="paneScreenings" class="p-6 space-y-4 hidden">
                        <div class="flex items-center justify-between pb-2">
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 font-mono-code">
                                    Suspect Rapid Urine Drug Screenings Register
                                </h3>
                                <p class="text-[11px] text-slate-500">
                                    Urinalysis toxicology multi-panel test records conducted during the period under review.
                                </p>
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-slate-200">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-teal-900/10 border-b border-slate-200 text-slate-900 font-bold uppercase tracking-wider font-mono-code text-[10px]">
                                        <th class="py-2.5 px-3 w-10 text-center">SN</th>
                                        <th class="py-2.5 px-4">Screening Date</th>
                                        <th class="py-2.5 px-4">Suspect Name &amp; Reference</th>
                                        <th class="py-2.5 px-4">Purpose</th>
                                        <th class="py-2.5 px-4 text-center">Results</th>
                                        <th class="py-2.5 px-4">Toxicology Notes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-slate-800">
                                    @foreach($drugScreeningRecords as $ds)
                                        @php
                                            $isPositive = strtolower($ds['result']) === 'positive';
                                            $isNegative = strtolower($ds['result']) === 'negative';
                                            $subFound = trim($ds['substances_found'] ?? '');
                                            if ($isPositive) {
                                                $detectedLabel = (!empty($subFound) && !in_array(strtolower($subFound), ['no substance(s) found', 'no substances found', 'no drug(s) detected', 'no drugs detected']))
                                                    ? $subFound
                                                    : 'Substances detected';
                                                $resDisplay = $detectedLabel . ' (POSITIVE)';
                                            } elseif ($isNegative) {
                                                $resDisplay = 'No drug(s) detected (NEGATIVE)';
                                            } else {
                                                $resDisplay = $ds['result'];
                                            }
                                        @endphp
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="py-3 px-3 text-center font-mono-code font-bold text-slate-500">
                                                {{ $ds['sn'] }}
                                            </td>
                                            <td class="py-3 px-4 font-mono-code font-bold text-slate-800 whitespace-nowrap">
                                                {{ $ds['date_display'] }}
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="font-extrabold text-slate-900 block text-xs">{{ $ds['suspect_name'] }}</span>
                                                <span class="text-[10px] text-slate-400 font-mono-code">Ref: {{ $ds['case_reference'] }}</span>
                                            </td>
                                            <td class="py-3 px-4 text-slate-700 font-medium">
                                                {{ $ds['purpose'] }}
                                            </td>
                                            <td class="py-3 px-4 text-center align-middle font-bold text-xs leading-snug">
                                                @if($isPositive)
                                                    <span class="text-rose-700 font-extrabold">{{ $resDisplay }}</span>
                                                @elseif($isNegative)
                                                    <span class="text-emerald-700 font-bold">{{ $resDisplay }}</span>
                                                @else
                                                    <span class="text-amber-700 font-semibold">{{ $resDisplay }}</span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-4 text-slate-600 text-[11px] leading-relaxed">
                                                {{ str_ireplace(['No substance(s) found', 'No substances found'], 'No drug(s) detected', $ds['notes']) }}
                                            </td>

                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB PANE 3: GSA SUBMISSIONS -->
                    <div id="paneGsa" class="p-6 space-y-4 hidden">
                        <div class="flex items-center justify-between pb-2">
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 font-mono-code">
                                    Ghana Standards Authority (GSA) External Submissions
                                </h3>
                                <p class="text-[11px] text-slate-500">
                                    Details of seized exhibits submitted to Ghana Standards Authority for confirmatory GC-MS assay.
                                </p>
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-slate-200">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-indigo-900/10 border-b border-slate-200 text-slate-900 font-bold uppercase tracking-wider font-mono-code text-[10px]">
                                        <th class="py-2.5 px-3 w-10 text-center">S/N</th>
                                        <th class="py-2.5 px-4">Case Reference</th>
                                        <th class="py-2.5 px-4">Exhibit Type</th>
                                        <th class="py-2.5 px-4 text-center">GSA Lab No.</th>
                                        <th class="py-2.5 px-4">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-slate-800">
                                    @forelse($gsaSubmissions as $gsa)
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="py-3 px-3 text-center font-mono-code font-bold text-slate-500">
                                                {{ $gsa['sn'] }}
                                            </td>
                                            <td class="py-3 px-4 font-bold text-slate-900">
                                                {{ $gsa['gsa_case_reference'] ?? ($gsa['case_reference'] ?? 'N/A') }}
                                            </td>
                                            <td class="py-3 px-4 text-slate-800 font-semibold">
                                                {{ !empty($gsa['exhibit_type']) ? $gsa['exhibit_type'] : 'N/A' }}
                                            </td>
                                            <td class="py-3 px-4 text-center font-mono-code font-bold text-indigo-900">
                                                {{ $gsa['gsa_lab_number'] ?? ($gsa['gsa_lab_no'] ?? 'N/A') }}
                                            </td>
                                            <td class="py-3 px-4 font-bold uppercase text-slate-800 text-[10px]">
                                                {{ $gsa['gsa_status'] ?? ($gsa['status'] ?? 'Pending') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="py-4 px-4 text-center text-slate-500 italic text-xs">
                                                No Ghana Standards Authority (GSA) submissions recorded for this reporting period.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

            </div>
        </div>

    </div>

    <!-- CLIENT SCRIPT FOR DYNAMIC CONSOLE FILTERING & PDF LAUNCH -->
    <script>
        function switchActiveTab(tabName) {
            const tabs = ['seizures', 'screenings', 'gsa'];
            tabs.forEach(t => {
                const pane = document.getElementById('pane' + t.charAt(0).toUpperCase() + t.slice(1));
                const btn = document.getElementById('tabBtn' + t.charAt(0).toUpperCase() + t.slice(1));
                if (t === tabName) {
                    pane?.classList.remove('hidden');
                    btn?.classList.remove('border-transparent', 'text-slate-500');
                    btn?.classList.add('border-emerald-600', 'text-emerald-800');
                } else {
                    pane?.classList.add('hidden');
                    btn?.classList.add('border-transparent', 'text-slate-500');
                    btn?.classList.remove('border-emerald-600', 'text-emerald-800');
                }
            });
        }

        let currentPeriodPreset = '{{ $rawPeriod ?? "weekly" }}';

        function setConsolePeriod(preset) {
            currentPeriodPreset = preset;
            const btns = document.querySelectorAll('.console-period-btn');
            btns.forEach(b => {
                if (b.getAttribute('data-period') === preset) {
                    b.className = 'console-period-btn px-3 py-1 rounded-lg transition-all bg-white text-emerald-800 shadow-xs font-bold';
                } else {
                    b.className = 'console-period-btn px-3 py-1 rounded-lg transition-all text-slate-600 hover:text-slate-900 font-bold';
                }
            });

            const fromInput = document.getElementById('consoleFromDate');
            const toInput = document.getElementById('consoleToDate');
            const now = new Date();
            const formatYmd = (d) => d.toISOString().split('T')[0];

            if (preset === 'weekly') {
                const past7 = new Date();
                past7.setDate(now.getDate() - 7);
                fromInput.value = formatYmd(past7);
                toInput.value = formatYmd(now);
            } else if (preset === 'monthly') {
                const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                fromInput.value = formatYmd(firstDay);
                toInput.value = formatYmd(lastDay);
            } else if (preset === 'annual' || preset === 'annually') {
                const firstYear = new Date(now.getFullYear(), 0, 1);
                const lastYear = new Date(now.getFullYear(), 11, 31);
                fromInput.value = formatYmd(firstYear);
                toInput.value = formatYmd(lastYear);
            } else if (preset === 'all' || preset === 'general') {
                fromInput.value = '';
                toInput.value = '';
            }
            applyConsoleFilter();
        }

        function resetConsoleFilters() {
            document.getElementById('consoleSuspectSelect').value = '';
            document.getElementById('consoleSearchInput').value = '';
            setConsolePeriod('weekly');
        }

        function applyConsoleFilter() {
            const suspectVal = (document.getElementById('consoleSuspectSelect')?.value || '').toLowerCase().trim();
            const searchVal = (document.getElementById('consoleSearchInput')?.value || '').toLowerCase().trim();
            const fromVal = document.getElementById('consoleFromDate')?.value || '';
            const toVal = document.getElementById('consoleToDate')?.value || '';

            const rows = document.querySelectorAll('#seizuresTableBody tr.directory-row');
            let visibleCount = 0;

            rows.forEach(r => {
                const rSuspect = (r.getAttribute('data-suspect') || '').toLowerCase();
                const rSource = (r.getAttribute('data-source') || '').toLowerCase();
                const rExhibit = (r.getAttribute('data-exhibit') || '').toLowerCase();
                const rDate = (r.getAttribute('data-date') || '');
                const rRemarks = (r.getAttribute('data-remarks') || '').toLowerCase();

                let match = true;

                if (suspectVal && !rSuspect.includes(suspectVal)) {
                    match = false;
                }
                if (fromVal && rDate && rDate < fromVal) {
                    match = false;
                }
                if (toVal && rDate && rDate > toVal) {
                    match = false;
                }
                if (searchVal) {
                    const haystack = rSuspect + ' ' + rSource + ' ' + rExhibit + ' ' + rDate + ' ' + rRemarks;
                    if (!haystack.includes(searchVal)) {
                        match = false;
                    }
                }

                if (match) {
                    r.style.display = '';
                    visibleCount++;
                } else {
                    r.style.display = 'none';
                }
            });

            // Update visible badge
            const countBadge = document.getElementById('visibleEntriesCount');
            const totalBadge = document.getElementById('badgeSeizuresCount');
            if (countBadge) countBadge.textContent = visibleCount;
            if (totalBadge) totalBadge.textContent = visibleCount;

            // Update main button label
            const btnLabel = document.getElementById('generateBtnLabel');
            if (btnLabel) {
                if (suspectVal) {
                    btnLabel.textContent = 'Generate Report: ' + document.getElementById('consoleSuspectSelect').selectedOptions[0].text;
                } else {
                    btnLabel.textContent = 'Generate Filtered PDF Report';
                }
            }
        }

        function triggerMainGeneratePdf() {
            const suspect = document.getElementById('consoleSuspectSelect')?.value || '';
            const search = document.getElementById('consoleSearchInput')?.value || '';
            const from = document.getElementById('consoleFromDate')?.value || '';
            const to = document.getElementById('consoleToDate')?.value || '';

            const params = new URLSearchParams();
            params.set('period', currentPeriodPreset);
            if (suspect) params.set('suspect', suspect);
            if (search) params.set('search', search);
            if (from) params.set('from', from);
            if (to) params.set('to', to);

            const targetUrl = '{{ url("/Lab_Analysis/lab_sitrep_pdf") }}?' + params.toString();
            window.open(targetUrl, '_blank');
        }

        function generateSingleSuspectPdf(suspectName) {
            const params = new URLSearchParams();
            params.set('suspect', suspectName);
            params.set('period', 'weekly');
            const targetUrl = '{{ url("/Lab_Analysis/lab_sitrep_pdf") }}?' + params.toString();
            window.open(targetUrl, '_blank');
        }

        function toggleCheckAll(checked) {
            const boxes = document.querySelectorAll('.row-checkbox');
            if (!boxes.length) return;
            boxes.forEach(b => b.checked = checked);
            onRowCheckChange();
        }

        function onRowCheckChange() {
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
            const btnLabel = document.getElementById('generateBtnLabel');
            if (checkedBoxes.length === 1) {
                const sName = checkedBoxes[0].value;
                const selectEl = document.getElementById('consoleSuspectSelect');
                if (selectEl) selectEl.value = sName;
                if (btnLabel) btnLabel.textContent = 'Generate Report: ' + sName;
            } else if (checkedBoxes.length > 1) {
                if (btnLabel) btnLabel.textContent = 'Generate Report for Selected (' + checkedBoxes.length + ') Suspects';
            } else {
                applyConsoleFilter();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            applyConsoleFilter();
        });
    </script>
</body>
</html>
