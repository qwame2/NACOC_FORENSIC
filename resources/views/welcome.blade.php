@php
    $authUser = auth()->user();
    $dbForensicHead = \App\Models\User::where('is_head', true)
        ->where(function($q) {
            $q->where('unit', 'LIKE', '%Forensic%')
              ->orWhere('unit', 'LIKE', '%Directorate%');
        })->first() ?? \App\Models\User::where('is_head', true)->first();
    $forensicHeadName = $authUser->fullname ?? session('user_name') ?? ($dbForensicHead ? $dbForensicHead->fullname : 'Anita Johnson');

    $dbCrimeHead = \App\Models\User::where('unit', 'LIKE', '%Crime Scene%')->where('is_head', true)->first();
    $crimeHeadName = $dbCrimeHead ? $dbCrimeHead->fullname : 'John Adu';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forensic Department Head Command Portal | NACOC</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif

    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #F8FAFC;
            color: #0F172A;
        }
        .font-mono-code {
            font-family: 'JetBrains Mono', monospace;
        }
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #F1F5F9;
        }
        ::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94A3B8;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-slate-50 text-slate-900 antialiased selection:bg-emerald-600 selection:text-white">

    <!-- SYSTEM APP CONTAINER WITH SIDEBAR & MAIN WORKSPACE -->
    <div class="flex h-screen overflow-hidden">

        <!-- LEFT EXECUTIVE NAVIGATION SIDEBAR -->
       @include('Forensic_Head.partials.sidebar')

        <!-- RIGHT WORKSPACE MAIN EXECUTIVE PANEL -->
        <div class="flex-1 flex flex-col min-w-0 bg-slate-50 overflow-hidden">

            <!-- SYSTEM TOP NAVBAR -->
            <header class="h-16 bg-white border-b border-slate-200/90 px-6 flex items-center justify-between shrink-0 shadow-2xs">
                
                <!-- EXECUTIVE SEARCH BAR -->
                <div class="flex items-center flex-1 max-w-lg">
                    <div class="relative w-full">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input 
                            type="text" 
                            id="globalSearch"
                            placeholder="Executive Search: Case #, Unit Head, Evidence Barcode, Suspect..." 
                            class="w-full pl-10 pr-12 py-2 bg-slate-100/70 border border-slate-200 rounded-xl text-xs text-slate-900 font-mono-code focus:outline-none focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/15 transition-all"
                        >
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3">
                            <kbd class="px-1.5 py-0.5 text-[10px] font-mono-code font-semibold text-slate-400 bg-white rounded border border-slate-200 shadow-2xs">Ctrl K</kbd>
                        </span>
                    </div>
                </div>

                <!-- TOP ACTIONS & EXECUTIVE BADGES -->
                <div class="flex items-center space-x-4">
                    
                    <!-- NOTIFICATIONS BELL -->
                    <button class="relative p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 01-6 0v-1m6 0H9"/></svg>
                        <span class="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-amber-500 rounded-full ring-2 ring-white"></span>
                    </button>

                    <div class="h-6 w-px bg-slate-200"></div>

                    <!-- EXECUTIVE ACTION BUTTON -->
                    <button onclick="toggleIntakeModal()" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-sm shadow-emerald-600/25 transition-all">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Authorize Case File</span>
                    </button>
                </div>
            </header>

            <!-- MAIN WORKSPACE EXECUTIVE DASHBOARD VIEW -->
            <main class="flex-1 overflow-y-auto p-6 space-y-6">
                @include('partials.flash')

                <!-- EXECUTIVE HEADER & TITLE -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center space-x-2 mb-1">
                            <span class="px-2.5 py-0.5 bg-emerald-600 text-white text-[10px] font-extrabold font-mono-code rounded-md uppercase">DEPARTMENT HEAD PORTAL</span>
                            <span class="text-xs text-slate-400">&bull;</span>
                            <span class="text-xs font-bold text-slate-700 font-mono-code">DIRECTORATE COMMAND &amp; OVERSIGHT</span>
                        </div>
                        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Forensic Directorate Executive Command</h1>
                    </div>

                    <div class="flex items-center space-x-3">
                        <button onclick="alert('Generating Directorate Quarterly Executive Summary PDF...')" class="inline-flex items-center px-3.5 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-800 text-xs font-bold rounded-xl shadow-2xs">
                            <svg class="w-4 h-4 mr-1.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Export Executive Report
                        </button>
                    </div>
                </div>

                <!-- EXECUTIVE HIGH-LEVEL DIRECTORATE KPIS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    
                    <!-- KPI 1: TOTAL DIRECTORATE CASES -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold font-mono-code uppercase text-slate-500">Directorate Cases</span>
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="text-2xl font-extrabold text-slate-900 font-mono-code tracking-tight">1,428</div>
                            <div class="flex items-center text-[11px] font-semibold text-emerald-600 mt-1">
                                <span>Across All 4 Units</span>
                                <span class="text-slate-400 font-normal ml-2">(84 High Priority)</span>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 2: PENDING DIRECTOR SIGN-OFFS -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold font-mono-code uppercase text-slate-500">Pending Head Approvals</span>
                            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="text-2xl font-extrabold text-amber-600 font-mono-code tracking-tight">12 Reports</div>
                            <div class="text-[11px] font-semibold text-slate-500 mt-1">
                                <span>Awaiting Director Signature</span>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 3: VAULT ITEMS UNDER COMMAND -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold font-mono-code uppercase text-slate-500">Vault Evidence Items</span>
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="text-2xl font-extrabold text-slate-900 font-mono-code tracking-tight">5,892</div>
                            <div class="text-[11px] font-semibold text-slate-600 mt-1">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block mr-1"></span>
                                <span>100% Chain Integrity Verified</span>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 4: PERSONNEL UNDER COMMAND -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold font-mono-code uppercase text-slate-500">Personnel &amp; Unit Heads</span>
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="text-2xl font-extrabold text-slate-900 font-mono-code tracking-tight">54 Officers</div>
                            <div class="text-[11px] font-semibold text-slate-500 mt-1">
                                <span>4 Unit Heads Reporting</span>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- 4 UNITS DIVISIONAL PERFORMANCE SUMMARY FOR THE DEPT HEAD -->
                <div id="unitPerformance" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="font-extrabold text-slate-900 text-base">Division Unit Heads Oversight</h3>
                        <span class="text-xs font-mono-code font-bold text-slate-500">4 SPECIALIZED UNITS</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        
                        <!-- UNIT 1 CARD -->
                        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs border-t-4 border-t-emerald-600 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-mono-code font-bold px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded border border-emerald-200">UNIT 01</span>
                                <span class="text-xs font-bold text-slate-500">14 Officers</span>
                            </div>
                            <div>
                                <h4 class="font-extrabold text-slate-900 text-sm">Crime Scene Support</h4>
                                <p class="text-[11px] text-slate-500">Unit Head: <strong class="text-slate-800">{{ $crimeHeadName }}</strong></p>
                            </div>
                            <div class="pt-2 border-t border-slate-100 flex justify-between text-xs font-mono-code">
                                <span class="text-slate-500">Active Cases:</span>
                                <span class="font-bold text-blue-600">142 Cases</span>
                            </div>
                        </div>

                        <!-- UNIT 2 CARD -->
                        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs border-t-4 border-t-indigo-600 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-mono-code font-bold px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded border border-indigo-200">UNIT 02</span>
                                <span class="text-xs font-bold text-slate-500">18 Custodians</span>
                            </div>
                            <div>
                                <h4 class="font-extrabold text-slate-900 text-sm">Evidence Management</h4>
                                <p class="text-[11px] text-slate-500">Unit Head: <strong class="text-slate-800">Dr. E. Osei</strong></p>
                            </div>
                            <div class="pt-2 border-t border-slate-100 flex justify-between text-xs font-mono-code">
                                <span class="text-slate-500">Logged Evidence:</span>
                                <span class="font-bold text-indigo-600">215 Items</span>
                            </div>
                        </div>

                        <!-- UNIT 3 CARD -->
                        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs border-t-4 border-t-emerald-600 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-mono-code font-bold px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded border border-emerald-200">UNIT 03</span>
                                <span class="text-xs font-bold text-slate-500">12 Analysts</span>
                            </div>
                            <div>
                                <h4 class="font-extrabold text-slate-900 text-sm">LAB Analysis</h4>
                                <p class="text-[11px] text-slate-500">Unit Head: <strong class="text-slate-800">Dr. K. Appiah</strong></p>
                            </div>
                            <div class="pt-2 border-t border-slate-100 flex justify-between text-xs font-mono-code">
                                <span class="text-slate-500">Lab Analysis:</span>
                                <span class="font-bold text-emerald-600">98 Tests</span>
                            </div>
                        </div>

                        <!-- UNIT 4 CARD -->
                        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs border-t-4 border-t-amber-600 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-mono-code font-bold px-2 py-0.5 bg-amber-50 text-amber-700 rounded border border-amber-200">UNIT 04</span>
                                <span class="text-xs font-bold text-slate-500">10 Experts</span>
                            </div>
                            <div>
                                <h4 class="font-extrabold text-slate-900 text-sm">Digital &amp; Physical Forensics</h4>
                                <p class="text-[11px] text-slate-500">Unit Head: <strong class="text-slate-800">Eng. M. Mensah</strong></p>
                            </div>
                            <div class="pt-2 border-t border-slate-100 flex justify-between text-xs font-mono-code">
                                <span class="text-slate-500">Exhibits:</span>
                                <span class="font-bold text-amber-600">64 Items</span>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- MAIN MASTER EVIDENCE & CASE APPROVAL TABLE -->
                <div id="pendingApprovals" class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">
                    
                    <!-- TABLE HEADER / FILTERS BAR -->
                    <div class="p-5 border-b border-slate-200/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-slate-50/50">
                        <div>
                            <h3 class="font-extrabold text-slate-900 text-base">Department Head Approval &amp; Case Register</h3>
                            <p class="text-xs text-slate-500">Review pending findings certificates, sign-offs, and inter-unit transfers</p>
                        </div>

                        <!-- UNIT FILTER BUTTON TABS -->
                        <div class="flex items-center space-x-2 text-xs font-medium">
                            <button onclick="filterDeptHeadUnit('all')" class="dept-unit-tab px-3 py-1.5 rounded-lg bg-emerald-600 text-white font-semibold">All 4 Units</button>
                            <button onclick="filterDeptHeadUnit('Crime Scene Support')" class="dept-unit-tab px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50">Crime Scene Support</button>
                            <button onclick="filterDeptHeadUnit('Evidence Management')" class="dept-unit-tab px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50">Evidence Management</button>
                            <button onclick="filterDeptHeadUnit('LAB Analysis')" class="dept-unit-tab px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50">LAB Analysis</button>
                            <button onclick="filterDeptHeadUnit('Digital & Physical Forensics')" class="dept-unit-tab px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50">Digital &amp; Physical Forensics</button>
                        </div>
                    </div>

                    <!-- DATA TABLE -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-700">
                            <thead class="bg-slate-100/70 border-b border-slate-200 text-slate-500 font-mono-code text-[11px] uppercase tracking-wider">
                                <tr>
                                    <th class="py-3.5 px-5 font-semibold">Case Reference</th>
                                    <th class="py-3.5 px-5 font-semibold">Evidence Details</th>
                                    <th class="py-3.5 px-5 font-semibold">Assigned Unit &amp; Head</th>
                                    <th class="py-3.5 px-5 font-semibold">Handling Officer</th>
                                    <th class="py-3.5 px-5 font-semibold">Director Status</th>
                                    <th class="py-3.5 px-5 font-semibold text-right">Head Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200/80 font-medium" id="caseTableBody">
                                
                                <!-- ROW 1 -->
                                <tr class="hover:bg-slate-50/80 transition-colors dept-case-row" data-unit="Evidence Management">
                                    <td class="py-4 px-5">
                                        <div class="font-bold text-slate-900 font-mono-code text-sm">FSD-2026-8842</div>
                                        <div class="text-[10px] text-slate-400 font-mono-code">Submitted: 2026-09-04</div>
                                    </td>
                                    <td class="py-4 px-5 max-w-xs">
                                        <div class="font-semibold text-slate-800">Suspected Organic Powder Specimen</div>
                                        <div class="text-[11px] text-slate-500 truncate">Sealed Poly Bag (3.40 kg)</div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <div class="font-bold text-indigo-700">Evidence Management</div>
                                        <div class="text-[10px] text-slate-400">Unit Head: Dr. Evelyn Osei</div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <div class="text-slate-800">Insp. Eric Ofori</div>
                                        <div class="text-[10px] text-slate-400">Evidence Officer</div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200" id="status-FSD-2026-8842">
                                            Awaiting Director Sign-off
                                        </span>
                                    </td>
                                    <td class="py-4 px-5 text-right space-x-2">
                                        <button onclick="viewDetails('FSD-2026-8842')" class="px-2.5 py-1 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 rounded-lg text-[11px] font-semibold shadow-2xs">Inspect</button>
                                        <button onclick="approveCert('FSD-2026-8842')" class="px-2.5 py-1 bg-emerald-600 text-white hover:bg-emerald-700 rounded-lg text-[11px] font-bold shadow-2xs">Approve Report</button>
                                    </td>
                                </tr>

                                <!-- ROW 2 -->
                                <tr class="hover:bg-slate-50/80 transition-colors dept-case-row" data-unit="Digital & Physical Forensics">
                                    <td class="py-4 px-5">
                                        <div class="font-bold text-slate-900 font-mono-code text-sm">FSD-2026-8839</div>
                                        <div class="text-[10px] text-slate-400 font-mono-code">Submitted: 2026-09-04</div>
                                    </td>
                                    <td class="py-4 px-5 max-w-xs">
                                        <div class="font-semibold text-slate-800">Encrypted NVMe SSD Storage Drive</div>
                                        <div class="text-[11px] text-slate-500 truncate">Bit-Stream Image Checksum #DIG-884</div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <div class="font-bold text-emerald-700">Digital &amp; Physical Forensics</div>
                                        <div class="text-[10px] text-slate-400">Unit Head: Eng. Michael Mensah</div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <div class="text-slate-800">Eng. Michael Mensah</div>
                                        <div class="text-[10px] text-slate-400">Digital Head</div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200" id="status-FSD-2026-8839">
                                            Awaiting Director Sign-off
                                        </span>
                                    </td>
                                    <td class="py-4 px-5 text-right space-x-2">
                                        <button onclick="viewDetails('FSD-2026-8839')" class="px-2.5 py-1 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 rounded-lg text-[11px] font-semibold shadow-2xs">Inspect</button>
                                        <button onclick="approveCert('FSD-2026-8839')" class="px-2.5 py-1 bg-emerald-600 text-white hover:bg-emerald-700 rounded-lg text-[11px] font-bold shadow-2xs">Approve Report</button>
                                    </td>
                                </tr>

                                <!-- ROW 3 -->
                                <tr class="hover:bg-slate-50/80 transition-colors dept-case-row" data-unit="Crime Scene Support">
                                    <td class="py-4 px-5">
                                        <div class="font-bold text-slate-900 font-mono-code text-sm">FSD-2026-8830</div>
                                        <div class="text-[10px] text-slate-400 font-mono-code">Submitted: 2026-09-03</div>
                                    </td>
                                    <td class="py-4 px-5 max-w-xs">
                                        <div class="font-semibold text-slate-800">3D Laser Scene Point Cloud Data</div>
                                        <div class="text-[11px] text-slate-500 truncate">Incident Sector 4 Documentation</div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <div class="font-bold text-blue-700">Crime Scene Support</div>
                                        <div class="text-[10px] text-slate-400">Unit Head: {{ $crimeHeadName }}</div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <div class="text-slate-800">{{ $crimeHeadName }}</div>
                                        <div class="text-[10px] text-slate-400">Scene Unit Head</div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Authorized by Director
                                        </span>
                                    </td>
                                    <td class="py-4 px-5 text-right space-x-2">
                                        <button onclick="viewDetails('FSD-2026-8830')" class="px-2.5 py-1 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 rounded-lg text-[11px] font-semibold shadow-2xs">Inspect</button>
                                        <button onclick="alert('Exporting Certified Court Report PDF...')" class="px-2.5 py-1 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 rounded-lg text-[11px] font-semibold shadow-2xs">Export Report</button>
                                    </td>
                                </tr>

                                <!-- ROW 4 -->
                                <tr class="hover:bg-slate-50/80 transition-colors dept-case-row" data-unit="LAB Analysis">
                                    <td class="py-4 px-5">
                                        <div class="font-bold text-slate-900 font-mono-code text-sm">FSD-2026-8825</div>
                                        <div class="text-[10px] text-slate-400 font-mono-code">Submitted: 2026-09-02</div>
                                    </td>
                                    <td class="py-4 px-5 max-w-xs">
                                        <div class="font-semibold text-slate-800">Toxicology Spectrometry Sample</div>
                                        <div class="text-[11px] text-slate-500 truncate">GC-MS Quantitative Analysis</div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <div class="font-bold text-amber-700">LAB Analysis</div>
                                        <div class="text-[10px] text-slate-400">Unit Head: Dr. K. Appiah</div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <div class="text-slate-800">Dr. K. Appiah</div>
                                        <div class="text-[10px] text-slate-400">Lab Head Analyst</div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-300">
                                            Vault Storage B-04
                                        </span>
                                    </td>
                                    <td class="py-4 px-5 text-right space-x-2">
                                        <button onclick="viewDetails('FSD-2026-8825')" class="px-2.5 py-1 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 rounded-lg text-[11px] font-semibold shadow-2xs">Inspect</button>
                                        <button onclick="alert('Inter-unit Transfer Authorized by Department Head.')" class="px-2.5 py-1 bg-emerald-600 text-white hover:bg-emerald-700 rounded-lg text-[11px] font-semibold shadow-2xs">Transfer</button>
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- BOTTOM ROW: EXECUTIVE AUDIT LOG STREAM & DIRECTOR CONTROLS -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/90 p-5 shadow-sm space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div>
                                <h3 class="font-extrabold text-slate-900 text-sm">Directorate Real-time Audit Stream</h3>
                                <p class="text-xs text-slate-500">Logged actions across all 4 division heads</p>
                            </div>
                            <span class="text-[10px] font-mono-code font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-md">LIVE LOG</span>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-start space-x-3 text-xs p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 mt-1 shrink-0"></div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-slate-900 font-mono-code">FSD-2026-8842</span>
                                        <span class="text-[10px] text-slate-400 font-mono-code">10 mins ago</span>
                                    </div>
                                    <div class="text-slate-600 mt-0.5">Submitted by <strong class="text-slate-800">Dr. Evelyn Osei (Chemical Lab Head)</strong> for Director Approval.</div>
                                </div>
                            </div>
                            <div class="flex items-start space-x-3 text-xs p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 mt-1 shrink-0"></div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-slate-900 font-mono-code">FSD-2026-8839</span>
                                        <span class="text-[10px] text-slate-400 font-mono-code">35 mins ago</span>
                                    </div>
                                    <div class="text-slate-600 mt-0.5">Hardware write-blocker image checksum validated by <strong class="text-slate-800">Eng. Michael Mensah (Digital Head)</strong>.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DIRECTOR QUICK TOOLS -->
                    <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-sm space-y-4">
                        <h3 class="font-extrabold text-slate-900 text-sm pb-2 border-b border-slate-100">Department Head Actions</h3>
                        <div class="space-y-2.5">
                            <button onclick="toggleIntakeModal()" class="w-full text-left p-3 rounded-xl bg-slate-50 hover:bg-emerald-50/60 border border-slate-200 transition-all flex items-center justify-between group">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-bold">+</div>
                                    <div>
                                        <div class="text-xs font-bold text-slate-900 group-hover:text-emerald-600">Authorize Case File</div>
                                        <div class="text-[10px] text-slate-500">Create high-priority directorate case</div>
                                    </div>
                                </div>
                                <svg class="w-4 h-4 text-slate-400 group-hover:text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>

                            <button onclick="alert('Exporting Certified Directorate Audit Certificate...')" class="w-full text-left p-3 rounded-xl bg-slate-50 hover:bg-emerald-50/60 border border-slate-200 transition-all flex items-center justify-between group">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-bold">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold text-slate-900 group-hover:text-emerald-600">Approve All Pending Reports</div>
                                        <div class="text-[10px] text-slate-500">Batch sign-off court documents</div>
                                    </div>
                                </div>
                                <svg class="w-4 h-4 text-slate-400 group-hover:text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- MODAL 1: AUTHORIZE CASE FILE -->
    <div id="intakeModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full border border-slate-200 shadow-2xl p-6 space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-base font-extrabold text-slate-900">Authorize High-Priority Directorate Case</h3>
                <button onclick="toggleIntakeModal()" class="text-slate-400 hover:text-slate-600 font-bold p-1">&times;</button>
            </div>

            <form onsubmit="event.preventDefault(); submitIntake();" class="space-y-4 text-xs font-medium">
                <div>
                    <label class="block text-slate-700 font-bold mb-1">Case Reference Number</label>
                    <input type="text" id="newCaseRef" value="FSD-2026-8850" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-mono-code font-bold text-slate-900">
                </div>

                <div>
                    <label class="block text-slate-700 font-bold mb-1">Evidence Item Description</label>
                    <input type="text" id="newDesc" placeholder="e.g. Suspected Substance Batch / Encrypted Storage Drive" required class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-700 font-bold mb-1">Assigned Division</label>
                        <select id="newUnit" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-medium">
                            <option>Crime Scene Support</option>
                            <option>Evidence Management</option>
                            <option>LAB Analysis</option>
                            <option>Digital &amp; Physical Forensics</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-700 font-bold mb-1">Authorizing Director</label>
                        <input type="text" id="newOfficer" value="{{ $forensicHeadName }}" readonly class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-bold text-blue-700">
                    </div>
                </div>

                <div class="pt-3 flex justify-end space-x-3">
                    <button type="button" onclick="toggleIntakeModal()" class="px-4 py-2 bg-white border border-slate-300 text-slate-700 font-bold rounded-xl hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 text-white font-bold rounded-xl hover:bg-emerald-700 shadow-sm shadow-emerald-600/30">Issue Directorate Authorization</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: CASE INSPECTION DOSSIER -->
    <div id="detailsModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full border border-slate-200 shadow-2xl p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <div class="text-[10px] font-mono-code font-bold text-blue-600 uppercase">Director Dossier Inspection</div>
                    <h3 class="text-base font-extrabold text-slate-900" id="modalCaseTitle">FSD-2026-8842</h3>
                </div>
                <button onclick="toggleDetailsModal()" class="text-slate-400 hover:text-slate-600 font-bold p-1">&times;</button>
            </div>

            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 font-mono-code text-xs space-y-2.5">
                <div class="flex justify-between"><span class="text-slate-500">CHAIN INTEGRITY:</span> <span class="font-bold text-emerald-600">100% VERIFIED &amp; TAMPER-FREE</span></div>
                <div class="flex justify-between"><span class="text-slate-500">DIVISION:</span> <span class="text-slate-800">CHEMICAL &amp; EVIDENCE LAB</span></div>
                <div class="flex justify-between"><span class="text-slate-500">UNIT HEAD:</span> <span class="text-slate-800">DR. EVELYN OSEI</span></div>
                <div class="flex justify-between"><span class="text-slate-500">DIRECTORATE HASH:</span> <span class="text-blue-700">SHA256:8f4b2c19904a...</span></div>
            </div>

            <div class="pt-2 flex justify-end">
                <button onclick="toggleDetailsModal()" class="px-4 py-2 bg-slate-900 text-white font-bold rounded-xl text-xs">Close Inspection</button>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT FOR DEPARTMENT HEAD PORTAL ACTIONS -->
    <script>
        function filterDeptHeadUnit(unitName, clickedElement) {
            // 1. Filter table rows
            const rows = document.querySelectorAll('.dept-case-row');
            rows.forEach(row => {
                const rowUnit = row.getAttribute('data-unit') || '';
                const isMatch = (unitName === 'all') || 
                                (rowUnit.toLowerCase() === unitName.toLowerCase()) ||
                                (unitName.includes('Crime') && rowUnit.includes('Crime')) ||
                                (unitName.includes('Evidence') && rowUnit.includes('Evidence')) ||
                                (unitName.includes('LAB') && rowUnit.includes('LAB')) ||
                                (unitName.includes('Digital') && rowUnit.includes('Digital'));
                if (isMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });

            // 2. Update sidebar buttons active styling
            const sidebarBtns = document.querySelectorAll('.sidebar-unit-btn');
            sidebarBtns.forEach(btn => {
                btn.className = 'sidebar-unit-btn w-full text-left flex items-center px-3 py-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/60 transition-all';
            });

            if (clickedElement) {
                let btn = clickedElement;
                if (!btn.classList.contains('sidebar-unit-btn')) {
                    btn = clickedElement.closest('.sidebar-unit-btn');
                }
                if (btn) {
                    btn.className = 'sidebar-unit-btn w-full text-left flex items-center px-3 py-2 rounded-xl text-white bg-emerald-600 shadow-sm shadow-emerald-600/30 font-bold transition-all';
                }
            }

            // 3. Sync table filter tabs styling
            const tabs = document.querySelectorAll('.dept-unit-tab');
            tabs.forEach(tab => {
                const tabText = tab.textContent.trim();
                if ((unitName === 'all' && tabText.includes('All')) || 
                    (unitName !== 'all' && tabText.toLowerCase() === unitName.toLowerCase())) {
                    tab.className = 'dept-unit-tab px-3 py-1.5 rounded-lg bg-emerald-600 text-white font-semibold';
                } else {
                    tab.className = 'dept-unit-tab px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50';
                }
            });
        }

        function approveCert(caseRef) {
            const statusEl = document.getElementById(`status-${caseRef}`);
            if(statusEl) {
                statusEl.className = 'inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200';
                statusEl.innerText = 'Authorized by Director';
            }
            alert(`Case ${caseRef} Court Evidence Certificate has been officially signed and authorized by Department Head ${@json($forensicHeadName)}.`);
        }

        function toggleIntakeModal() {
            document.getElementById('intakeModal').classList.toggle('hidden');
        }

        function toggleDetailsModal() {
            document.getElementById('detailsModal').classList.toggle('hidden');
        }

        function viewDetails(caseRef) {
            document.getElementById('modalCaseTitle').innerText = caseRef;
            toggleDetailsModal();
        }

        function submitIntake() {
            const caseRef = document.getElementById('newCaseRef').value;
            const desc = document.getElementById('newDesc').value;
            const unit = document.getElementById('newUnit').value;

            const tableBody = document.getElementById('caseTableBody');
            const newRow = document.createElement('tr');
            newRow.className = 'hover:bg-slate-50/80 transition-colors dept-case-row';
            newRow.setAttribute('data-unit', unit);
            newRow.innerHTML = `
                <td class="py-4 px-5">
                    <div class="font-bold text-slate-900 font-mono-code text-sm">${caseRef}</div>
                    <div class="text-[10px] text-slate-400 font-mono-code">Submitted: ${new Date().toISOString().slice(0, 10)}</div>
                </td>
                <td class="py-4 px-5 max-w-xs">
                    <div class="font-semibold text-slate-800">${desc}</div>
                    <div class="text-[11px] text-slate-500">Director Authorized Case</div>
                </td>
                <td class="py-4 px-5">
                    <div class="font-bold text-blue-700">${unit}</div>
                    <div class="text-[10px] text-slate-400">Assigned Division</div>
                </td>
                <td class="py-4 px-5">
                    <div class="text-slate-800">${@json($forensicHeadName)}</div>
                    <div class="text-[10px] text-slate-400">Department Head</div>
                </td>
                <td class="py-4 px-5">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200" id="status-${caseRef}">
                        Authorized by Director
                    </span>
                </td>
                <td class="py-4 px-5 text-right space-x-2">
                    <button onclick="viewDetails('${caseRef}')" class="px-2.5 py-1 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 rounded-lg text-[11px] font-semibold shadow-2xs">Inspect</button>
                    <button onclick="alert('Exporting Certified Court Report PDF...')" class="px-2.5 py-1 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 rounded-lg text-[11px] font-semibold shadow-2xs">Export Report</button>
                </td>
            `;
            tableBody.prepend(newRow);
            toggleIntakeModal();
        }

        // Keyboard Shortcut Ctrl+K
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.getElementById('globalSearch').focus();
            }
        });
    </script>
</body>
</html>
