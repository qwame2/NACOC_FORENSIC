<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Case Directory & Docket Management | NACOC Forensic</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif

    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #FFFFFF;
            color: #0F172A;
        }
        .font-mono-code {
            font-family: 'JetBrains Mono', monospace;
        }
        ::-webkit-scrollbar {
            width: 7px;
            height: 7px;
        }
        ::-webkit-scrollbar-track {
            background: #F1F5F9;
        }
        ::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94A3B8;
        }
        .glass-panel-white {
            background: #F1F5F9;
            border: none;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.03);
        }
        .glass-modal-white {
            background: #F1F5F9;
            border: none;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .table-row-hover:hover {
            background-color: #E2E8F0;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-white text-slate-900 antialiased selection:bg-blue-600 selection:text-white">

    <!-- GLOBAL APPARATUS WRAPPER -->
    <div class="flex h-screen overflow-hidden">

        <!-- NAVIGATION SIDEBAR (CRIME SCENE SUPPORT HEAD SIDEBAR) -->
        @include('Crime_Scene_Support.partials.sidebar')

        <!-- MAIN MAIN WORKSPACE CONTENT -->
        <div class="flex-1 flex flex-col min-w-0 bg-white overflow-hidden">

            <!-- TOP WORKSPACE NAVBAR (WHITE THEME) -->
            <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 z-10 shadow-xs">
                
                <!-- TITLE & BADGES -->
                <div class="flex items-center space-x-4">
                    <h1 class="text-sm font-extrabold text-slate-900 tracking-wide font-mono-code uppercase flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Case Management System
                    </h1>
                    <span class="hidden sm:inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                        Secured Terminal v4.2
                    </span>
                </div>

                <!-- RIGHT HEADER ACTIONS -->
                <div class="flex items-center space-x-4">
                    <div class="text-right hidden sm:block">
                        <div class="text-xs font-semibold text-slate-800" id="liveClock">2026-09-05 00:00:00 UTC</div>
                        <div class="text-[10px] font-mono-code text-blue-600 font-bold">STATUS: ONLINE &amp; SYNCHRONIZED</div>
                    </div>
                </div>
            </header>

            <!-- DASHBOARD BODY CONTAINER -->
            <main class="flex-1 overflow-y-auto p-6 space-y-6">

                @if (session('status'))
                    <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-xs font-bold text-emerald-800 flex items-center justify-between shadow-2xs">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shrink-0 shadow-md shadow-emerald-600/30">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-emerald-900 font-mono-code uppercase tracking-wider">SUCCESSFUL LOGIN</div>
                                <div class="text-[11px] font-semibold text-emerald-700 mt-0.5">{{ session('status') }}</div>
                            </div>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 p-1 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                @endif

                <!-- HERO SECTION BANNER (GRAY EXECUTIVE THEME WITH SHADOW & NO BORDER) -->
                <div class="relative overflow-hidden rounded-2xl p-6 sm:p-8 bg-slate-100 shadow-xl text-slate-900 border-none">
                    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        <div class="space-y-2">
                            
                            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Forensic Case Directory &amp; Docket Registry</h2>
                            <p class="text-xs sm:text-sm font-semibold text-slate-700 max-w-3xl leading-relaxed">
                                Centralized law enforcement repository for logging, searching, and managing narcotic seizures, suspect records, and physical forensic exhibits with full chain-of-custody compliance.
                            </p>
                        </div>

                        <!-- CTA BUTTONS IN HERO BANNER -->
                        <div class="flex items-center gap-3 shrink-0 pt-2 lg:pt-0">
                            <a 
                                href="{{ url('/arrest-person') }}" 
                                class="px-5 py-3 bg-blue-600 hover:bg-blue-700 text-white text-xs font-extrabold rounded-xl shadow-md shadow-blue-600/30 flex items-center space-x-2 transition-all hover:scale-[1.02] cursor-pointer border-none"
                            >
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                <span>Add New Case File</span>
                            </a>
                            <button onclick="exportCaseReport()" class="px-4 py-3 bg-white hover:bg-slate-200 text-slate-800 text-xs font-bold rounded-xl shadow-sm border-none transition-all flex items-center space-x-2 cursor-pointer">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span>Export Report</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- KPI STAT METRICS CARDS (GRAY EXECUTIVE THEME) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- CARD 1 -->
                    <div class="glass-panel-white p-5 rounded-2xl transition-all shadow-xl hover:shadow-2xl">
                        <div class="flex items-center justify-between text-slate-500 mb-2">
                            <span class="text-xs font-bold text-slate-600 uppercase tracking-wider font-mono-code">Total Registered Cases</span>
                            <div class="w-9 h-9 rounded-xl bg-blue-100/70 text-blue-700 flex items-center justify-center shadow-xs">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-slate-900 font-mono-code" id="statTotalCases">148</div>
                        <div class="flex items-center text-[11px] text-emerald-600 mt-2 font-bold">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            +12 cases registered this month
                        </div>
                    </div>

                    <!-- CARD 2 -->
                    <div class="glass-panel-white p-5 rounded-2xl transition-all shadow-xl hover:shadow-2xl">
                        <div class="flex items-center justify-between text-slate-500 mb-2">
                            <span class="text-xs font-bold text-slate-600 uppercase tracking-wider font-mono-code">Under Forensic Lab Analysis</span>
                            <div class="w-9 h-9 rounded-xl bg-amber-100/70 text-amber-700 flex items-center justify-center shadow-xs">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-amber-600 font-mono-code" id="statPendingLab">32</div>
                        <div class="flex items-center text-[11px] text-amber-700 mt-2 font-semibold">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5 animate-ping"></span>
                            Priority exhibit chemical test in progress
                        </div>
                    </div>

                    <!-- CARD 3 -->
                    <div class="glass-panel-white p-5 rounded-2xl transition-all shadow-xl hover:shadow-2xl">
                        <div class="flex items-center justify-between text-slate-500 mb-2">
                            <span class="text-xs font-bold text-slate-600 uppercase tracking-wider font-mono-code">Active Interceptions</span>
                            <div class="w-9 h-9 rounded-xl bg-indigo-100/70 text-indigo-700 flex items-center justify-center shadow-xs">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-indigo-600 font-mono-code" id="statActiveOps">79</div>
                        <div class="flex items-center text-[11px] text-slate-500 mt-2 font-semibold">
                            Accra Port, KIA Airport &amp; Border Posts
                        </div>
                    </div>

                    <!-- CARD 4 -->
                    <div class="glass-panel-white p-5 rounded-2xl transition-all shadow-xl hover:shadow-2xl">
                        <div class="flex items-center justify-between text-slate-500 mb-2">
                            <span class="text-xs font-bold text-slate-600 uppercase tracking-wider font-mono-code">Court Ready / Certified</span>
                            <div class="w-9 h-9 rounded-xl bg-emerald-100/70 text-emerald-700 flex items-center justify-center shadow-xs">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-emerald-600 font-mono-code" id="statCourtReady">37</div>
                        <div class="flex items-center text-[11px] text-emerald-700 mt-2 font-semibold">
                            Signed off by Directorate Head
                        </div>
                    </div>
                </div>

                <!-- CASE ROSTER MAIN TABLE PANEL (GRAY EXECUTIVE CARD) -->
                <div class="glass-panel-white rounded-2xl overflow-hidden shadow-xl border-none">
                    
                    <!-- TOOLBAR & SEARCH BAR HEADER -->
                    <div class="p-5 bg-slate-200/40 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        
                        <!-- SEARCH INPUT FIELD WITH LIVE FILTERING -->
                        <div class="flex-1 max-w-md relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input 
                                type="text" 
                                id="caseSearchInput" 
                                onkeyup="filterCases()"
                                placeholder="Search case ref #, suspect name, officer, offense, location..." 
                                class="w-full pl-10 pr-10 py-2.5 bg-white border-none rounded-xl text-xs text-slate-900 placeholder-slate-400 font-mono-code focus:outline-none focus:ring-2 focus:ring-blue-600/20 transition-all shadow-md"
                            >
                            <button 
                                id="clearSearchBtn" 
                                onclick="clearSearch()" 
                                class="hidden absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-700"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <!-- QUICK FILTER TABS & CATEGORIES -->
                        <div class="flex items-center gap-2 overflow-x-auto pb-1 md:pb-0">
                            <button onclick="filterStatus('ALL')" class="status-filter-btn px-3.5 py-1.5 rounded-xl text-xs font-bold bg-blue-600 text-white transition-all shadow-md border-none" data-status="ALL">
                                All Cases
                            </button>
                            <button onclick="filterStatus('Active Investigation')" class="status-filter-btn px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-200/80 text-slate-800 hover:bg-slate-300 border-none transition-all shadow-xs" data-status="Active Investigation">
                                Active
                            </button>
                            <button onclick="filterStatus('Lab Analysis')" class="status-filter-btn px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-200/80 text-slate-800 hover:bg-slate-300 border-none transition-all shadow-xs" data-status="Lab Analysis">
                                Lab Testing
                            </button>
                            <button onclick="filterStatus('Pending Approval')" class="status-filter-btn px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-200/80 text-slate-800 hover:bg-slate-300 border-none transition-all shadow-xs" data-status="Pending Approval">
                                Pending
                            </button>
                            <button onclick="filterStatus('Court Ready')" class="status-filter-btn px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-200/80 text-slate-800 hover:bg-slate-300 border-none transition-all shadow-xs" data-status="Court Ready">
                                Certified
                            </button>
                        </div>
                    </div>

                    <!-- SEARCH STATS BANNER -->
                    <div class="px-5 py-2.5 bg-slate-200/60 flex items-center justify-between text-xs text-slate-500 font-mono-code">
                        <div>
                            Showing <span id="visibleCaseCount" class="font-bold text-slate-900">8</span> of <span id="totalCaseCount" class="font-bold text-slate-900">8</span> case records
                        </div>
                        <div id="activeFilterBadge" class="hidden text-[11px] text-blue-700 bg-blue-100 px-2 py-0.5 rounded border-none font-bold shadow-xs">
                            Filtered by search
                        </div>
                    </div>

                    <!-- TABLE CONTENT -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs" id="casesTable">
                            <thead>
                                <tr class="border-none bg-slate-200/70 text-slate-700 font-mono-code uppercase text-[11px] tracking-wider select-none">
                                    <th class="py-3.5 px-4 font-bold">Case Reference #</th>
                                    <th class="py-3.5 px-4 font-bold">Suspect / Target</th>
                                    <th class="py-3.5 px-4 font-bold">Offense &amp; Location</th>
                                    <th class="py-3.5 px-4 font-bold">Lead Officer / Unit</th>
                                    <th class="py-3.5 px-4 font-bold">Date Registered</th>
                                    <th class="py-3.5 px-4 font-bold text-center">Status</th>
                                    <th class="py-3.5 px-4 font-bold text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200" id="casesTableBody">
                                
                                <!-- ROW 1 -->
                                <tr class="table-row-hover transition-colors case-row" data-status="Lab Analysis">
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <div class="font-bold font-mono-code text-blue-600 text-xs">NFC-2026-0892</div>
                                        <div class="flex items-center space-x-1.5 mt-0.5">
                                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold bg-red-100 text-red-700 border border-red-200">CRITICAL</span>
                                            <span class="text-[10px] text-slate-500 font-mono-code">5 Exhibits</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 rounded-full bg-slate-200 border border-slate-300 flex items-center justify-center font-bold text-slate-700 text-xs shrink-0">
                                                KO
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-900">Kwame Ofori</div>
                                                <div class="text-[10px] text-slate-500 font-mono-code">ID: GHA-8920194-X</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="font-semibold text-slate-800">Cocaine Concealment &amp; Export Attempt</div>
                                        <div class="text-[10px] text-slate-500 flex items-center gap-1 mt-0.5">
                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                            Kotoka Int. Airport (Cargo Village)
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="font-medium text-slate-800">Insp. Emmanuel Mensah</div>
                                        <div class="text-[10px] text-blue-600 font-mono-code font-semibold">Crime Scene Support</div>
                                    </td>
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <div class="text-slate-700 font-mono-code">2026-09-04</div>
                                        <div class="text-[10px] text-slate-400 font-mono-code">14:22:08 UTC</div>
                                    </td>
                                    <td class="py-4 px-4 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5 animate-pulse"></span>
                                            Lab Analysis
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-right whitespace-nowrap">
                                        <button onclick="viewCaseDocket('NFC-2026-0892')" class="px-3 py-1.5 bg-slate-200/80 hover:bg-slate-300 text-blue-700 text-xs font-bold rounded-lg border-none transition-all inline-flex items-center gap-1 shadow-xs">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            View Docket
                                        </button>
                                    </td>
                                </tr>

                                <!-- ROW 2 -->
                                <tr class="table-row-hover transition-colors case-row" data-status="Active Investigation">
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <div class="font-bold font-mono-code text-blue-600 text-xs">NFC-2026-0891</div>
                                        <div class="flex items-center space-x-1.5 mt-0.5">
                                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-100 text-amber-800 border border-amber-200">HIGH</span>
                                            <span class="text-[10px] text-slate-500 font-mono-code">12 Exhibits</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 rounded-full bg-slate-200 border border-slate-300 flex items-center justify-center font-bold text-slate-700 text-xs shrink-0">
                                                AA
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-900">Abena Addo</div>
                                                <div class="text-[10px] text-slate-500 font-mono-code">ID: GHA-7740291-A</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="font-semibold text-slate-800">Parcel Interception (Heroin Bricks)</div>
                                        <div class="text-[10px] text-slate-500 flex items-center gap-1 mt-0.5">
                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                            Tema Central Post Office
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="font-medium text-slate-800">Det. Supt. K. Appiah</div>
                                        <div class="text-[10px] text-indigo-600 font-mono-code font-semibold">Interception Unit</div>
                                    </td>
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <div class="text-slate-700 font-mono-code">2026-09-03</div>
                                        <div class="text-[10px] text-slate-400 font-mono-code">11:05:40 UTC</div>
                                    </td>
                                    <td class="py-4 px-4 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600 mr-1.5"></span>
                                            Active Investigation
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-right whitespace-nowrap">
                                        <button onclick="viewCaseDocket('NFC-2026-0891')" class="px-3 py-1.5 bg-slate-200/80 hover:bg-slate-300 text-blue-700 text-xs font-bold rounded-lg border-none transition-all inline-flex items-center gap-1 shadow-xs">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            View Docket
                                        </button>
                                    </td>
                                </tr>

                                <!-- ROW 3 -->
                                <tr class="table-row-hover transition-colors case-row" data-status="Court Ready">
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <div class="font-bold font-mono-code text-blue-600 text-xs">NFC-2026-0888</div>
                                        <div class="flex items-center space-x-1.5 mt-0.5">
                                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold bg-slate-200 text-slate-700 border border-slate-300">ROUTINE</span>
                                            <span class="text-[10px] text-slate-500 font-mono-code">2 Exhibits</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 rounded-full bg-slate-200 border border-slate-300 flex items-center justify-center font-bold text-slate-700 text-xs shrink-0">
                                                IK
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-900">Ibrahim Konate</div>
                                                <div class="text-[10px] text-slate-500 font-mono-code">ID: GHA-5529103-B</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="font-semibold text-slate-800">Cannabis Sativa Seizure (140 Sacks)</div>
                                        <div class="text-[10px] text-slate-500 flex items-center gap-1 mt-0.5">
                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                            Aflao Border Crossing
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="font-medium text-slate-800">Insp. Frank Boateng</div>
                                        <div class="text-[10px] text-emerald-600 font-mono-code font-semibold">Border Operations</div>
                                    </td>
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <div class="text-slate-700 font-mono-code">2026-09-01</div>
                                        <div class="text-[10px] text-slate-400 font-mono-code">09:14:12 UTC</div>
                                    </td>
                                    <td class="py-4 px-4 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            <svg class="w-3 h-3 mr-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Court Ready
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-right whitespace-nowrap">
                                        <button onclick="viewCaseDocket('NFC-2026-0888')" class="px-3 py-1.5 bg-slate-200/80 hover:bg-slate-300 text-blue-700 text-xs font-bold rounded-lg border-none transition-all inline-flex items-center gap-1 shadow-xs">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            View Docket
                                        </button>
                                    </td>
                                </tr>

                                <!-- ROW 4 -->
                                <tr class="table-row-hover transition-colors case-row" data-status="Pending Approval">
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <div class="font-bold font-mono-code text-blue-600 text-xs">NFC-2026-0885</div>
                                        <div class="flex items-center space-x-1.5 mt-0.5">
                                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold bg-red-100 text-red-700 border border-red-200">CRITICAL</span>
                                            <span class="text-[10px] text-slate-500 font-mono-code">8 Exhibits</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 rounded-full bg-slate-200 border border-slate-300 flex items-center justify-center font-bold text-slate-700 text-xs shrink-0">
                                                MS
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-900">Mustapha Sani</div>
                                                <div class="text-[10px] text-slate-500 font-mono-code">ID: GHA-9930114-K</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="font-semibold text-slate-800">Methamphetamine Clandestine Lab Raid</div>
                                        <div class="text-[10px] text-slate-500 flex items-center gap-1 mt-0.5">
                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                            Spintex Industrial Area
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="font-medium text-slate-800">Chief Insp. R. Quarshie</div>
                                        <div class="text-[10px] text-purple-600 font-mono-code font-semibold">Tactical Raid Unit</div>
                                    </td>
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <div class="text-slate-700 font-mono-code">2026-08-30</div>
                                        <div class="text-[10px] text-slate-400 font-mono-code">18:45:00 UTC</div>
                                    </td>
                                    <td class="py-4 px-4 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-purple-600 mr-1.5"></span>
                                            Pending Approval
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-right whitespace-nowrap">
                                        <button onclick="viewCaseDocket('NFC-2026-0885')" class="px-3 py-1.5 bg-slate-200/80 hover:bg-slate-300 text-blue-700 text-xs font-bold rounded-lg border-none transition-all inline-flex items-center gap-1 shadow-xs">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            View Docket
                                        </button>
                                    </td>
                                </tr>

                                <!-- ROW 5 -->
                                <tr class="table-row-hover transition-colors case-row" data-status="Active Investigation">
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <div class="font-bold font-mono-code text-blue-600 text-xs">NFC-2026-0880</div>
                                        <div class="flex items-center space-x-1.5 mt-0.5">
                                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-100 text-amber-800 border border-amber-200">HIGH</span>
                                            <span class="text-[10px] text-slate-500 font-mono-code">4 Exhibits</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 rounded-full bg-slate-200 border border-slate-300 flex items-center justify-center font-bold text-slate-700 text-xs shrink-0">
                                                GA
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-900">George Ansah</div>
                                                <div class="text-[10px] text-slate-500 font-mono-code">ID: GHA-1102948-C</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="font-semibold text-slate-800">Tramadol &amp; Illicit Opioids Distribution</div>
                                        <div class="text-[10px] text-slate-500 flex items-center gap-1 mt-0.5">
                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                            Alajo Market Area
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="font-medium text-slate-800">Insp. Daniel Tetteh</div>
                                        <div class="text-[10px] text-blue-600 font-mono-code font-semibold">Crime Scene Support</div>
                                    </td>
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <div class="text-slate-700 font-mono-code">2026-08-28</div>
                                        <div class="text-[10px] text-slate-400 font-mono-code">10:20:15 UTC</div>
                                    </td>
                                    <td class="py-4 px-4 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600 mr-1.5"></span>
                                            Active Investigation
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-right whitespace-nowrap">
                                        <button onclick="viewCaseDocket('NFC-2026-0880')" class="px-3 py-1.5 bg-slate-200/80 hover:bg-slate-300 text-blue-700 text-xs font-bold rounded-lg border-none transition-all inline-flex items-center gap-1 shadow-xs">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            View Docket
                                        </button>
                                    </td>
                                </tr>

                                <!-- ROW 6 -->
                                <tr class="table-row-hover transition-colors case-row" data-status="Lab Analysis">
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <div class="font-bold font-mono-code text-blue-600 text-xs">NFC-2026-0874</div>
                                        <div class="flex items-center space-x-1.5 mt-0.5">
                                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold bg-red-100 text-red-700 border border-red-200">CRITICAL</span>
                                            <span class="text-[10px] text-slate-500 font-mono-code">15 Exhibits</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 rounded-full bg-slate-200 border border-slate-300 flex items-center justify-center font-bold text-slate-700 text-xs shrink-0">
                                                AD
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-900">Alhassan Dauda</div>
                                                <div class="text-[10px] text-slate-500 font-mono-code">ID: GHA-4410928-M</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="font-semibold text-slate-800">Ephedrine Precursor Interception</div>
                                        <div class="text-[10px] text-slate-500 flex items-center gap-1 mt-0.5">
                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                            Takoradi Harbor Terminal
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="font-medium text-slate-800">Dr. Janet Owusu</div>
                                        <div class="text-[10px] text-amber-600 font-mono-code font-semibold">Chemical Forensics</div>
                                    </td>
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <div class="text-slate-700 font-mono-code">2026-08-25</div>
                                        <div class="text-[10px] text-slate-400 font-mono-code">16:10:00 UTC</div>
                                    </td>
                                    <td class="py-4 px-4 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5 animate-pulse"></span>
                                            Lab Analysis
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-right whitespace-nowrap">
                                        <button onclick="viewCaseDocket('NFC-2026-0874')" class="px-3 py-1.5 bg-slate-200/80 hover:bg-slate-300 text-blue-700 text-xs font-bold rounded-lg border-none transition-all inline-flex items-center gap-1 shadow-xs">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            View Docket
                                        </button>
                                    </td>
                                </tr>

                                <!-- ROW 7 -->
                                <tr class="table-row-hover transition-colors case-row" data-status="Court Ready">
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <div class="font-bold font-mono-code text-blue-600 text-xs">NFC-2026-0865</div>
                                        <div class="flex items-center space-x-1.5 mt-0.5">
                                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold bg-slate-200 text-slate-700 border border-slate-300">ROUTINE</span>
                                            <span class="text-[10px] text-slate-500 font-mono-code">3 Exhibits</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 rounded-full bg-slate-200 border border-slate-300 flex items-center justify-center font-bold text-slate-700 text-xs shrink-0">
                                                PK
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-900">Peter Koomson</div>
                                                <div class="text-[10px] text-slate-500 font-mono-code">ID: GHA-3392019-P</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="font-semibold text-slate-800">Speedboat Narcotics Interception</div>
                                        <div class="text-[10px] text-slate-500 flex items-center gap-1 mt-0.5">
                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                            Ada Coastal Estuary
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="font-medium text-slate-800">{{ auth()->user()->fullname ?? session('user_name') ?? 'Adomako Emmanuel' }}</div>
                                        <div class="text-[10px] text-emerald-600 font-mono-code font-semibold">Maritime Security</div>
                                    </td>
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <div class="text-slate-700 font-mono-code">2026-08-20</div>
                                        <div class="text-[10px] text-slate-400 font-mono-code">08:00:10 UTC</div>
                                    </td>
                                    <td class="py-4 px-4 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            <svg class="w-3 h-3 mr-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Court Ready
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-right whitespace-nowrap">
                                        <button onclick="viewCaseDocket('NFC-2026-0865')" class="px-3 py-1.5 bg-slate-200/80 hover:bg-slate-300 text-blue-700 text-xs font-bold rounded-lg border-none transition-all inline-flex items-center gap-1 shadow-xs">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            View Docket
                                        </button>
                                    </td>
                                </tr>

                                <!-- ROW 8 -->
                                <tr class="table-row-hover transition-colors case-row" data-status="Active Investigation">
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <div class="font-bold font-mono-code text-blue-600 text-xs">NFC-2026-0850</div>
                                        <div class="flex items-center space-x-1.5 mt-0.5">
                                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-100 text-amber-800 border border-amber-200">HIGH</span>
                                            <span class="text-[10px] text-slate-500 font-mono-code">6 Exhibits</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 rounded-full bg-slate-200 border border-slate-300 flex items-center justify-center font-bold text-slate-700 text-xs shrink-0">
                                                FA
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-900">Farida Alhassan</div>
                                                <div class="text-[10px] text-slate-500 font-mono-code">ID: GHA-6620194-L</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="font-semibold text-slate-800">Courier Parcel Airport Seizure</div>
                                        <div class="text-[10px] text-slate-500 flex items-center gap-1 mt-0.5">
                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                            DHL Central Cargo Terminal
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="font-medium text-slate-800">Insp. Samuel Mensah</div>
                                        <div class="text-[10px] text-indigo-600 font-mono-code font-semibold">Cargo Inspection</div>
                                    </td>
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <div class="text-slate-700 font-mono-code">2026-08-15</div>
                                        <div class="text-[10px] text-slate-400 font-mono-code">13:30:45 UTC</div>
                                    </td>
                                    <td class="py-4 px-4 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600 mr-1.5"></span>
                                            Active Investigation
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-right whitespace-nowrap">
                                        <button onclick="viewCaseDocket('NFC-2026-0850')" class="px-3 py-1.5 bg-slate-200/80 hover:bg-slate-300 text-blue-700 text-xs font-bold rounded-lg border-none transition-all inline-flex items-center gap-1 shadow-xs">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            View Docket
                                        </button>
                                    </td>
                                </tr>

                            </tbody>
                        </table>

                        <!-- NO MATCHES FOUND MESSAGE -->
                        <div id="noCasesFound" class="hidden p-12 text-center">
                            <div class="w-16 h-16 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center mx-auto text-slate-400 mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 mb-1">No Matching Forensic Cases Found</h3>
                            <p class="text-xs text-slate-500 max-w-sm mx-auto mb-4">
                                No case records matched your active search or filter query. Try modifying your keywords or clear your filters.
                            </p>
                            <button onclick="clearSearch()" class="px-4 py-2 bg-white hover:bg-slate-50 text-blue-600 text-xs font-semibold rounded-xl border border-slate-300 transition-all shadow-2xs">
                                Clear Search &amp; Reset Filters
                            </button>
                        </div>
                    </div>

                    <!-- FOOTER PAGINATION -->
                    <div class="px-5 py-4 border-none bg-slate-200/40 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-xs text-slate-500">
                            Showing page <span class="font-bold text-slate-900">1</span> of <span class="font-bold text-slate-900">19</span> pages
                        </div>
                        <div class="flex items-center space-x-2">
                            <button disabled class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-200/60 text-slate-400 border-none cursor-not-allowed">Previous</button>
                            <button class="px-3 py-1.5 rounded-lg text-xs font-bold bg-blue-600 text-white shadow-md border-none">1</button>
                            <button class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-200/80 text-slate-700 hover:bg-slate-300 border-none transition-all shadow-xs">2</button>
                            <button class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-200/80 text-slate-700 hover:bg-slate-300 border-none transition-all shadow-xs">3</button>
                            <span class="text-slate-400 px-1 font-mono-code">...</span>
                            <button class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-200/80 text-slate-700 hover:bg-slate-300 border-none transition-all shadow-xs">19</button>
                            <button class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-200/80 text-slate-700 hover:bg-slate-300 border-none transition-all shadow-xs">Next</button>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- MODAL: ADD NEW CASE (GRAY EXECUTIVE FORM) -->
    <div id="addCaseModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- BACKDROP -->
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeAddCaseModal()"></div>

        <!-- MODAL CONTAINER -->
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl glass-modal-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl border-none">
                
                <!-- MODAL HEADER -->
                <div class="px-6 py-5 border-none flex items-center justify-between bg-slate-200/50">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-100/70 text-blue-700 flex items-center justify-center shadow-xs">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900 tracking-wide" id="modal-title">Register New Forensic Case</h3>
                            <p class="text-xs text-slate-500">Generate a new master case file and assign initial exhibits</p>
                        </div>
                    </div>
                    <button onclick="closeAddCaseModal()" class="text-slate-400 hover:text-slate-700 p-1 rounded-lg hover:bg-slate-300 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- FORM CONTENT -->
                <form id="newCaseForm" onsubmit="handleNewCaseSubmit(event)">
                    <div class="p-6 space-y-5">
                        
                        <!-- ROW 1: CASE REF & PRIORITY -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 font-mono-code uppercase mb-1">Case Reference #</label>
                                <input 
                                    type="text" 
                                    id="inputCaseRef" 
                                    readonly 
                                    value="NFC-2026-0941" 
                                    class="w-full px-3.5 py-2.5 bg-slate-200/60 border-none rounded-xl text-xs text-blue-700 font-bold font-mono-code focus:outline-none cursor-not-allowed shadow-inner"
                                >
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 font-mono-code uppercase mb-1">Priority Classification *</label>
                                <select id="inputPriority" required class="w-full px-3.5 py-2.5 bg-white border-none rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-600/20 shadow-sm">
                                    <option value="CRITICAL">Critical (Immediate Lab Testing)</option>
                                    <option value="HIGH" selected>High Priority</option>
                                    <option value="ROUTINE">Routine Investigation</option>
                                </select>
                            </div>
                        </div>

                        <!-- ROW 2: SUSPECT DETAILS -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 font-mono-code uppercase mb-1">Suspect Full Name *</label>
                                <input 
                                    type="text" 
                                    id="inputSuspectName" 
                                    required 
                                    placeholder="e.g., Kofi Mensah" 
                                    class="w-full px-3.5 py-2.5 bg-white border-none rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                                >
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 font-mono-code uppercase mb-1">National ID / Passport #</label>
                                <input 
                                    type="text" 
                                    id="inputSuspectId" 
                                    placeholder="e.g., GHA-9081234-A" 
                                    class="w-full px-3.5 py-2.5 bg-white border-none rounded-xl text-xs text-slate-900 placeholder-slate-400 font-mono-code focus:outline-none focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                                >
                            </div>
                        </div>

                        <!-- ROW 3: OFFENSE & LOCATION -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 font-mono-code uppercase mb-1">Primary Offense *</label>
                                <input 
                                    type="text" 
                                    id="inputOffense" 
                                    required 
                                    placeholder="e.g., Cocaine Trafficking / Airport Interception" 
                                    class="w-full px-3.5 py-2.5 bg-white border-none rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                                >
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 font-mono-code uppercase mb-1">Seizure / Scene Location *</label>
                                <input 
                                    type="text" 
                                    id="inputLocation" 
                                    required 
                                    placeholder="e.g., Kotoka Int. Airport Cargo Hall" 
                                    class="w-full px-3.5 py-2.5 bg-white border-none rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                                >
                            </div>
                        </div>

                        <!-- ROW 4: ASSIGNED UNIT & OFFICER -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 font-mono-code uppercase mb-1">Assigned Division *</label>
                                <select id="inputUnit" required class="w-full px-3.5 py-2.5 bg-white border-none rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-600/20 shadow-sm">
                                    <option value="Crime Scene Support" selected>Crime Scene Support Unit</option>
                                    <option value="Interception Unit">Cargo &amp; Parcel Interception</option>
                                    <option value="Chemical Forensics">Chemical Forensics Laboratory</option>
                                    <option value="Maritime Operations">Maritime &amp; Border Operations</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 font-mono-code uppercase mb-1">Lead Investigator *</label>
                                <input 
                                    type="text" 
                                    id="inputOfficer" 
                                    required 
                                    placeholder="e.g., Insp. Samuel Mensah" 
                                    class="w-full px-3.5 py-2.5 bg-white border-none rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                                >
                            </div>
                        </div>

                        <!-- ROW 5: INITIAL EXHIBIT COUNT & DESCRIPTION -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 font-mono-code uppercase mb-1">Seized Exhibits</label>
                                <input 
                                    type="number" 
                                    id="inputExhibits" 
                                    value="1" 
                                    min="1" 
                                    class="w-full px-3.5 py-2.5 bg-white border-none rounded-xl text-xs text-slate-900 font-mono-code focus:outline-none focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                                >
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 font-mono-code uppercase mb-1">Initial Status</label>
                                <select id="inputStatus" required class="w-full px-3.5 py-2.5 bg-white border-none rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-600/20 shadow-sm">
                                    <option value="Active Investigation" selected>Active Investigation</option>
                                    <option value="Lab Analysis">Lab Analysis</option>
                                    <option value="Pending Approval">Pending Approval</option>
                                </select>
                            </div>
                        </div>

                    </div>

                    <!-- FOOTER ACTIONS -->
                    <div class="px-6 py-4 border-none bg-slate-200/50 flex items-center justify-end space-x-3">
                        <button type="button" onclick="closeAddCaseModal()" class="px-4 py-2.5 bg-slate-200/80 hover:bg-slate-300 text-slate-800 border-none text-xs font-bold rounded-xl transition-all shadow-xs">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-600/25 transition-all flex items-center gap-2 border-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Create &amp; Register Case
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <!-- DOCKET VIEW MODAL -->
    <div id="docketModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="docket-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeDocketModal()"></div>
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl glass-modal-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-xl border-none p-6 space-y-4">
                <div class="flex items-center justify-between border-none pb-3">
                    <div>
                        <div class="text-[10px] text-blue-600 font-mono-code font-bold">CASE DOCKET DOSSIER</div>
                        <h3 class="text-lg font-bold text-slate-900 font-mono-code" id="docketRef">NFC-2026-0892</h3>
                    </div>
                    <button onclick="closeDocketModal()" class="text-slate-400 hover:text-slate-700 p-1 rounded-lg hover:bg-slate-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="space-y-3 text-xs text-slate-700 font-mono-code" id="docketContent">
                    <p class="text-slate-500">Loading docket information...</p>
                </div>
                <div class="pt-4 border-none flex justify-end">
                    <button onclick="closeDocketModal()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md border-none">
                        Close Dossier
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- TOAST NOTIFICATION CONTAINER -->
    <div id="toastNotification" class="fixed bottom-5 right-5 z-50 hidden transform transition-all duration-300 translate-y-2">
        <div class="flex items-center space-x-3 px-4 py-3 bg-slate-900 border border-emerald-500/40 text-white rounded-xl shadow-2xl">
            <div class="w-7 h-7 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <div class="text-xs font-bold text-white" id="toastTitle">Case Created Successfully</div>
                <div class="text-[11px] text-slate-300" id="toastMessage">Case NFC-2026-0941 added to directory.</div>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT FOR INTERACTIVE FEATURES -->
    <script>
        // Live UTC Clock
        function updateClock() {
            const now = new Date();
            const year = now.getUTCFullYear();
            const month = String(now.getUTCMonth() + 1).padStart(2, '0');
            const day = String(now.getUTCDate()).padStart(2, '0');
            const hours = String(now.getUTCHours()).padStart(2, '0');
            const minutes = String(now.getUTCMinutes()).padStart(2, '0');
            const seconds = String(now.getUTCSeconds()).padStart(2, '0');
            const clockElem = document.getElementById('liveClock');
            if (clockElem) {
                clockElem.innerText = `${year}-${month}-${day} ${hours}:${minutes}:${seconds} UTC`;
            }
        }
        setInterval(updateClock, 1000);
        updateClock();

        let activeStatusFilter = 'ALL';

        // Filter Cases Function (Live Text Search)
        function filterCases() {
            const searchInput = document.getElementById('caseSearchInput');
            const query = searchInput.value.toLowerCase().trim();
            const clearBtn = document.getElementById('clearSearchBtn');
            const rows = document.querySelectorAll('.case-row');
            const noCasesFound = document.getElementById('noCasesFound');
            const activeFilterBadge = document.getElementById('activeFilterBadge');

            if (query.length > 0) {
                clearBtn.classList.remove('hidden');
                activeFilterBadge.classList.remove('hidden');
            } else {
                clearBtn.classList.add('hidden');
                if (activeStatusFilter === 'ALL') {
                    activeFilterBadge.classList.add('hidden');
                }
            }

            let visibleCount = 0;

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                const rowStatus = row.getAttribute('data-status');

                const matchesQuery = query === '' || text.includes(query);
                const matchesStatus = activeStatusFilter === 'ALL' || rowStatus === activeStatusFilter;

                if (matchesQuery && matchesStatus) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('visibleCaseCount').innerText = visibleCount;
            document.getElementById('totalCaseCount').innerText = rows.length;

            if (visibleCount === 0) {
                noCasesFound.classList.remove('hidden');
            } else {
                noCasesFound.classList.add('hidden');
            }
        }

        function clearSearch() {
            const searchInput = document.getElementById('caseSearchInput');
            searchInput.value = '';
            filterStatus('ALL');
            filterCases();
        }

        // Status Filter Buttons
        function filterStatus(status) {
            activeStatusFilter = status;
            
            const filterBtns = document.querySelectorAll('.status-filter-btn');
            filterBtns.forEach(btn => {
                if (btn.getAttribute('data-status') === status) {
                    btn.className = 'status-filter-btn px-3.5 py-1.5 rounded-xl text-xs font-bold bg-blue-600 text-white transition-all shadow-md border-none';
                } else {
                    btn.className = 'status-filter-btn px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-200/80 text-slate-800 hover:bg-slate-300 border-none transition-all shadow-xs';
                }
            });

            filterCases();
        }

        // Modal Handlers
        function openAddCaseModal() {
            // Generate auto case ref number
            const randomNum = Math.floor(1000 + Math.random() * 9000);
            document.getElementById('inputCaseRef').value = `NFC-2026-${randomNum}`;
            document.getElementById('addCaseModal').classList.remove('hidden');
        }

        function closeAddCaseModal() {
            document.getElementById('addCaseModal').classList.add('hidden');
        }

        // Handle New Case Form Submission
        function handleNewCaseSubmit(e) {
            e.preventDefault();

            const caseRef = document.getElementById('inputCaseRef').value;
            const suspectName = document.getElementById('inputSuspectName').value;
            const suspectId = document.getElementById('inputSuspectId').value || 'N/A';
            const offense = document.getElementById('inputOffense').value;
            const location = document.getElementById('inputLocation').value;
            const unit = document.getElementById('inputUnit').value;
            const officer = document.getElementById('inputOfficer').value;
            const priority = document.getElementById('inputPriority').value;
            const exhibits = document.getElementById('inputExhibits').value;
            const status = document.getElementById('inputStatus').value;

            // Generate initials
            const initials = suspectName.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2) || 'XX';

            // Create new row element
            const tbody = document.getElementById('casesTableBody');
            const tr = document.createElement('tr');
            tr.className = 'table-row-hover transition-colors case-row border-none bg-blue-50/40';
            tr.setAttribute('data-status', status);

            // Priority Badge
            let priorityBadge = '';
            if (priority === 'CRITICAL') {
                priorityBadge = '<span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold bg-red-100 text-red-700 border-none">CRITICAL</span>';
            } else if (priority === 'HIGH') {
                priorityBadge = '<span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-100 text-amber-800 border-none">HIGH</span>';
            } else {
                priorityBadge = '<span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold bg-slate-200 text-slate-700 border-none">ROUTINE</span>';
            }

            // Status Badge
            let statusBadge = '';
            if (status === 'Lab Analysis') {
                statusBadge = `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border-none"><span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5 animate-pulse"></span>Lab Analysis</span>`;
            } else if (status === 'Pending Approval') {
                statusBadge = `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 border-none"><span class="w-1.5 h-1.5 rounded-full bg-purple-600 mr-1.5"></span>Pending Approval</span>`;
            } else {
                statusBadge = `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border-none"><span class="w-1.5 h-1.5 rounded-full bg-blue-600 mr-1.5"></span>Active Investigation</span>`;
            }

            const today = new Date().toISOString().split('T')[0];

            tr.innerHTML = `
                <td class="py-4 px-4 whitespace-nowrap">
                    <div class="font-bold font-mono-code text-blue-600 text-xs">${caseRef}</div>
                    <div class="flex items-center space-x-1.5 mt-0.5">
                        ${priorityBadge}
                        <span class="text-[10px] text-slate-500 font-mono-code">${exhibits} Exhibits</span>
                    </div>
                </td>
                <td class="py-4 px-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-full bg-blue-100 border-none flex items-center justify-center font-bold text-blue-700 text-xs shrink-0">
                            ${initials}
                        </div>
                        <div>
                            <div class="font-bold text-slate-900">${suspectName}</div>
                            <div class="text-[10px] text-slate-500 font-mono-code">ID: ${suspectId}</div>
                        </div>
                    </div>
                </td>
                <td class="py-4 px-4">
                    <div class="font-semibold text-slate-800">${offense}</div>
                    <div class="text-[10px] text-slate-500 flex items-center gap-1 mt-0.5">
                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                        ${location}
                    </div>
                </td>
                <td class="py-4 px-4">
                    <div class="font-medium text-slate-800">${officer}</div>
                    <div class="text-[10px] text-blue-600 font-mono-code font-semibold">${unit}</div>
                </td>
                <td class="py-4 px-4 whitespace-nowrap">
                    <div class="text-slate-700 font-mono-code">${today}</div>
                    <div class="text-[10px] text-emerald-600 font-mono-code font-bold">JUST NOW</div>
                </td>
                <td class="py-4 px-4 text-center whitespace-nowrap">
                    ${statusBadge}
                </td>
                <td class="py-4 px-4 text-right whitespace-nowrap">
                    <button onclick="viewCaseDocket('${caseRef}')" class="px-3 py-1.5 bg-slate-200/80 hover:bg-slate-300 text-blue-700 text-xs font-bold rounded-lg border-none transition-all inline-flex items-center gap-1 shadow-xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        View Docket
                    </button>
                </td>
            `;

            // Insert at top of table
            tbody.insertBefore(tr, tbody.firstChild);

            // Update Total Stat Count
            const totalStat = document.getElementById('statTotalCases');
            if (totalStat) {
                totalStat.innerText = parseInt(totalStat.innerText) + 1;
            }

            closeAddCaseModal();
            document.getElementById('newCaseForm').reset();

            // Show Toast
            showToast('Case Registered Successfully', `Case ${caseRef} has been assigned to ${unit}.`);

            // Re-filter
            filterCases();
        }

        // View Docket Modal
        function viewCaseDocket(ref) {
            document.getElementById('docketRef').innerText = ref;
            document.getElementById('docketContent').innerHTML = `
                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex justify-between border-b border-slate-200 pb-1.5">
                        <span class="text-slate-500">Security Level:</span>
                        <span class="font-bold text-red-600">RESTRICTED DOSSIER</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200 pb-1.5">
                        <span class="text-slate-500">Chain of Custody:</span>
                        <span class="font-bold text-emerald-600">VERIFIED &amp; SEALED</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200 pb-1.5">
                        <span class="text-slate-500">Primary Division:</span>
                        <span class="font-bold text-slate-900">NACOC Forensic Directorate</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Vault Locker #:</span>
                        <span class="font-bold text-blue-600">LOCKER-B-14</span>
                    </div>
                </div>
            `;
            document.getElementById('docketModal').classList.remove('hidden');
        }

        function closeDocketModal() {
            document.getElementById('docketModal').classList.add('hidden');
        }

        // Toast Notification Helper
        function showToast(title, message) {
            const toast = document.getElementById('toastNotification');
            document.getElementById('toastTitle').innerText = title;
            document.getElementById('toastMessage').innerText = message;

            toast.classList.remove('hidden');
            setTimeout(() => {
                toast.classList.remove('translate-y-2');
            }, 10);

            setTimeout(() => {
                toast.classList.add('translate-y-2');
                setTimeout(() => {
                    toast.classList.add('hidden');
                }, 300);
            }, 4000);
        }

        function exportCaseReport() {
            showToast('Exporting Directory PDF', 'Generating master case docket report...');
        }

        // Global keyboard shortcut Ctrl+K focus search
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const searchInput = document.getElementById('caseSearchInput');
                if (searchInput) {
                    searchInput.focus();
                }
            }
        });
    </script>
</body>
</html>
