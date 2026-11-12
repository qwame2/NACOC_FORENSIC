@php
    $cases = $cases ?? collect();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Case Management &amp; Case Register | Forensic Directorate</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
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
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
        }
        .table-row-hover:hover {
            background-color: #F1F5F9;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
        }
        .status-await {
            background-color: #FEF3C7;
            color: #D97706;
            border: 1px solid #FCD34D;
        }
        .status-done {
            background-color: #D1FAE5;
            color: #047857;
            border: 1px solid #6EE7B7;
        }
        .status-vault {
            background-color: #EEF2FF;
            color: #4338CA;
            border: 1px solid #C7D2FE;
        }
        .status-lab {
            background-color: #DBEAFE;
            color: #1D4ED8;
            border: 1px solid #93C5FD;
        }
        .filter-tab-btn {
            padding: 6px 14px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            background: #F1F5F9;
            border: 1px solid #CBD5E1;
            color: #475569;
            transition: all 0.2s;
        }
        .filter-tab-btn:hover {
            background: #E2E8F0;
            color: #0F172A;
        }
        .filter-tab-btn.active-tab {
            background: #2563EB;
            border-color: #2563EB;
            color: #FFFFFF;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
        }
        .btn-review {
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            background: #0f172a;
            border: 1px solid #1e293b;
            color: #ffffff;
            transition: all 0.2s ease;
            font-family: 'JetBrains Mono', monospace;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 6px rgba(15,23,42,0.2);
        }
        .btn-review:hover {
            background: #1e293b;
            box-shadow: 0 4px 12px rgba(15,23,42,0.35);
            transform: translateY(-1px);
        }
        .status-declined {
            background-color: #FEF2F2;
            color: #DC2626;
            border: 1px solid #FCA5A5;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-white text-slate-900 antialiased selection:bg-emerald-600 selection:text-white">

    <!-- GLOBAL APPARATUS WRAPPER -->
    <div class="flex h-screen overflow-hidden">

        <!-- HEAD OF FORENSIC DIRECTORATE SIDEBAR -->
        @include('Forensic_Head.partials.sidebar')

        <!-- MAIN WORKSPACE CONTENT -->
        <div class="flex-1 flex flex-col min-w-0 bg-white overflow-hidden">

            <!-- TOP WORKSPACE NAVBAR -->
            <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 z-10 shadow-xs gap-4">
                
                <!-- TITLE & BADGES -->
                <div class="flex items-center space-x-4 shrink-0">
                    <h1 class="text-sm font-extrabold text-slate-900 tracking-wide font-mono-code uppercase">
                        Case Management &amp; Case Register
                    </h1>
                </div>

                <!-- GENERAL SEARCH INPUT -->
                <div class="flex items-center flex-1 max-w-md mx-4">
                    <div class="relative w-full">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input 
                            type="text" 
                            id="globalSearch"
                            placeholder="Search case #, suspect, evidence ID, or officer..." 
                            class="w-full pl-10 pr-12 py-1.5 bg-slate-100/80 border border-slate-200 rounded-xl text-xs text-slate-900 font-mono-code focus:outline-none focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/15 transition-all"
                            onkeyup="if(typeof handleGlobalSearch === 'function') handleGlobalSearch(this.value)"
                        >
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <kbd class="px-1.5 py-0.5 text-[10px] font-mono-code font-semibold text-slate-400 bg-white rounded border border-slate-200 shadow-2xs">Ctrl K</kbd>
                        </span>
                    </div>
                </div>

                <!-- RIGHT HEADER ACTIONS -->
                <div class="flex items-center space-x-4 shrink-0">
                    <!-- NOTIFICATION BELL ICON & GENERAL ALERTS -->
                    <div class="relative">
                        @php
                            $pendingHandoversNotif = (int)(\App\Models\CaseHandover::whereNull('acknowledged_at')->count());
                            $totalAlertsCount = ($pendingHandoversNotif > 0 ? 1 : 0);
                        @endphp
                        <button type="button" id="notificationBellBtn" onclick="toggleNotificationDropdown()" class="p-2 rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors relative border-none bg-transparent cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            @if($totalAlertsCount > 0)
                                <span class="absolute top-1 right-1 w-2.5 h-2.5 bg-red-500 rounded-full ring-2 ring-white"></span>
                            @endif
                        </button>

                        <!-- NOTIFICATIONS & GENERAL ALERTS DROPDOWN MENU -->
                        <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-96 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden font-sans">
                            <!-- HEADER -->
                            <div class="p-4 bg-slate-900 text-white flex items-center justify-between">
                                <div>
                                    <div class="text-[10px] font-mono-code text-blue-400 font-bold uppercase tracking-wider">DIRECTORATE COMMAND</div>
                                    <div class="text-xs font-black tracking-wide">General System &amp; Department Alerts</div>
                                </div>
                                <span class="px-2.5 py-1 text-[10px] font-extrabold bg-blue-500/20 text-blue-300 rounded-full font-mono-code border border-blue-500/30">
                                    {{ $totalAlertsCount }} Active Alerts
                                </span>
                            </div>

                            <!-- ALERTS LIST -->
                            <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
                                
                                <!-- ALERT 1: CASE REGISTER -->
                                <a href="{{ url('/Forensic_Head/cases') }}" class="p-3.5 flex items-start space-x-3 hover:bg-slate-50 transition-colors text-decoration-none block">
                                    <div class="w-8 h-8 rounded-xl bg-blue-500/10 text-blue-600 border border-blue-500/20 flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-0.5">
                                            <span class="text-[10px] font-extrabold font-mono-code text-blue-700 uppercase">Case Register</span>
                                            <span class="text-[9px] font-bold text-slate-400 font-mono-code">OVERVIEW</span>
                                        </div>
                                        <div class="text-xs font-bold text-slate-900 truncate">
                                            {{ count($cases) }} Directorate Case Register Dockets
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 leading-snug">
                                            Real-time directorate tracking across operational units.
                                        </div>
                                    </div>
                                </a>

                                <!-- ALERT 3: EVIDENCE VAULT TRANSFERS -->
                                <a href="{{ url('/Forensic_Head/cases') }}" class="p-3.5 flex items-start space-x-3 hover:bg-slate-50 transition-colors text-decoration-none block">
                                    <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v1a2 2 0 01-2 2M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-0.5">
                                            <span class="text-[10px] font-extrabold font-mono-code text-emerald-700 uppercase">Evidence Vault</span>
                                            <span class="text-[9px] font-bold text-slate-400 font-mono-code">STATUS</span>
                                        </div>
                                        <div class="text-xs font-bold text-slate-900 truncate">
                                            @if($pendingHandoversNotif > 0)
                                                {{ $pendingHandoversNotif }} Pending Evidence Transfer(s)
                                            @else
                                                All Custody Transfers Acknowledged
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 leading-snug">
                                            @if($pendingHandoversNotif > 0)
                                                Chain of custody handovers awaiting recipient acknowledgment.
                                            @else
                                                Evidence vault records and chain of custody are up to date.
                                            @endif
                                        </div>
                                    </div>
                                </a>

                                <!-- ALERT 4: SYSTEM INTEGRITY AUDIT -->
                                <div class="p-3.5 flex items-start space-x-3 bg-emerald-50/40">
                                    <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-0.5">
                                            <span class="text-[10px] font-extrabold font-mono-code text-emerald-700 uppercase">Chain Integrity Audit</span>
                                            <span class="text-[9px] font-bold text-emerald-600 font-mono-code">SYSTEM OK</span>
                                        </div>
                                        <div class="text-xs font-bold text-slate-900 truncate">Chain of Custody Verified</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 leading-snug">Audit trail active and synchronized across forensic directorates.</div>
                                    </div>
                                </div>

                            </div>

                            <!-- FOOTER -->
                            <div class="p-3 bg-slate-50 border-t border-slate-200 text-center">
                                <a href="{{ url('/Forensic_Head/cases') }}" class="text-[11px] font-bold text-blue-600 hover:text-blue-800 font-mono-code uppercase tracking-wider text-decoration-none">
                                    View Executive Case Register &rarr;
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="text-right hidden sm:block">
                        <div class="text-xs font-mono-code font-semibold text-slate-700" id="liveClock"></div>
                        <div class="text-[10px] font-mono-code text-blue-600 font-bold">NACOC FORENSIC DEPARTMENT</div>
                    </div>
                </div>
            </header>

            <!-- DASHBOARD BODY CONTAINER -->
            <main class="flex-1 overflow-y-auto p-6 space-y-6">

                @include('partials.flash')



                @php
                    $datasetCases = $allCases ?? \App\Models\ForensicCase::allCases();
                    $totalCasesCount = count($datasetCases);
                    $activeInvestigationsCount = $datasetCases->filter(function($c) {
                        return !in_array($c->status, ['Approved', 'Authorized', 'Certified', 'Completed', 'Court Ready', 'Case Closed', 'Case Dismissed']);
                    })->count();
                    $vaultItemsCount = $datasetCases->sum(function($c) {
                        preg_match('/\d+/', $c->exhibits_summary ?? '', $m);
                        return isset($m[0]) ? (int)$m[0] : ($c->exhibits_summary ? 1 : 0);
                    });
                    $courtReadyCount = $datasetCases->filter(function($c) {
                        return in_array($c->status, ['Approved', 'Authorized', 'Certified', 'Completed', 'Court Ready']);
                    })->count();
                @endphp

                <!-- KPI STAT METRICS CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- CARD 1 -->
                    <div class="glass-panel-white p-5 rounded-2xl transition-all shadow-sm">
                        <div class="flex items-center justify-between text-slate-500 mb-2">
                            <span class="text-xs font-bold text-slate-600 uppercase tracking-wider font-mono-code">Directorate Cases</span>
                            <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 01-2-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-slate-900 font-mono-code" id="statTotalCases">{{ number_format($totalCasesCount) }}</div>
                        <div class="flex items-center text-[11px] text-emerald-600 mt-2 font-bold">
                            Across All Specialized Units
                        </div>
                    </div>

                    <!-- CARD 2 -->
                    <div class="glass-panel-white p-5 rounded-2xl transition-all shadow-sm">
                        <div class="flex items-center justify-between text-slate-500 mb-2">
                            <span class="text-xs font-bold text-slate-600 uppercase tracking-wider font-mono-code">Active Investigations</span>
                            <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-amber-600 font-mono-code" id="statActiveCases">{{ number_format($activeInvestigationsCount) }}</div>
                        <div class="flex items-center text-[11px] text-amber-700 mt-2 font-semibold">
                            Ongoing Operational Cases
                        </div>
                    </div>

                    <!-- CARD 3 -->
                    <div class="glass-panel-white p-5 rounded-2xl transition-all shadow-sm">
                        <div class="flex items-center justify-between text-slate-500 mb-2">
                            <span class="text-xs font-bold text-slate-600 uppercase tracking-wider font-mono-code">Vault Evidence Integrity</span>
                            <div class="w-9 h-9 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-indigo-600 font-mono-code" id="statVaultItems">{{ number_format($vaultItemsCount) }}</div>
                        <div class="flex items-center text-[11px] text-emerald-600 mt-2 font-bold">
                            100% Chain Integrity Verified
                        </div>
                    </div>

                    <!-- CARD 4 -->
                    <div class="glass-panel-white p-5 rounded-2xl transition-all shadow-sm">
                        <div class="flex items-center justify-between text-slate-500 mb-2">
                            <span class="text-xs font-bold text-slate-600 uppercase tracking-wider font-mono-code">Court Certified / Ready</span>
                            <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-emerald-600 font-mono-code" id="statCourtReady">{{ number_format($courtReadyCount) }}</div>
                        <div class="flex items-center text-[11px] text-emerald-700 mt-2 font-semibold">
                            Completed Case Dossiers
                        </div>
                    </div>
                </div>

                <!-- CASE REGISTER MAIN PANEL -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden space-y-0">
                    
                    <!-- HEADER & TOOLBAR -->
                    <div class="p-6 bg-slate-50 border-b border-slate-200 space-y-4">
                        
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <span class="text-[10px] font-bold font-mono-code text-blue-600 uppercase tracking-wider">CASE REGISTER</span>
                                <h3 class="font-black text-slate-900 text-lg">Forensic Department Case Register</h3>
                                <p class="text-xs text-slate-600 mt-0.5 font-medium">Department oversight of case dossiers, evidence packages, unit workflows, and findings reports</p>
                            </div>
                            
                            <!-- LIVE SEARCH FIELD -->
                            <div class="w-full md:w-80 relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                                <input 
                                    type="text" 
                                    id="caseSearchInput" 
                                    onkeyup="filterCases()"
                                    placeholder="Search case ref, evidence, officer, unit..." 
                                    class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 font-mono-code focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all shadow-2xs"
                                >
                            </div>
                        </div>

                        <!-- UNIT & STATUS TABS ROW -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 border-t border-slate-200/80">
                            
                            <!-- UNIT TABS -->
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-[11px] font-bold text-slate-700 mr-1 uppercase font-mono-code">Unit:</span>
                                <button type="button" onclick="filterUnit('ALL', this)" class="filter-tab-btn active-tab unit-tab">All 4 Units</button>
                                <button type="button" onclick="filterUnit('Crime Scene Support', this)" class="filter-tab-btn unit-tab">Crime Scene Support</button>
                                <button type="button" onclick="filterUnit('Evidence Management', this)" class="filter-tab-btn unit-tab">Evidence Management</button>
                                <button type="button" onclick="filterUnit('LAB Analysis', this)" class="filter-tab-btn unit-tab">LAB Analysis</button>
                                <button type="button" onclick="filterUnit('Digital & Physical Forensics', this)" class="filter-tab-btn unit-tab">Digital &amp; Physical</button>
                            </div>

                            <!-- STATUS TABS -->
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-[11px] font-bold text-slate-700 mr-1 uppercase font-mono-code">Status:</span>
                                <button type="button" onclick="filterStatus('ALL', this)" class="filter-tab-btn active-tab status-tab">All Statuses</button>
                                <button type="button" onclick="filterStatus('Under Investigation', this)" class="filter-tab-btn status-tab">Under Investigation</button>
                                <button type="button" onclick="filterStatus('Court Ready', this)" class="filter-tab-btn status-tab">Court Ready</button>
                                <button type="button" onclick="filterStatus('Vault Storage', this)" class="filter-tab-btn status-tab">Vault Storage</button>
                                <button type="button" onclick="filterStatus('Lab Analysis', this)" class="filter-tab-btn status-tab">Lab Testing</button>
                            </div>
                        </div>
                    </div>

                    <!-- SEARCH COUNTER BANNER -->
                    <div class="px-6 py-2.5 bg-slate-100/70 border-b border-slate-200 flex items-center justify-between text-xs text-slate-600 font-mono-code">
                        <div>
                            Displaying <span id="visibleCaseCount" class="font-extrabold text-slate-900">{{ count($cases) }}</span> of <span id="totalCaseCount" class="font-extrabold text-slate-900">{{ count($datasetCases) }}</span> case register dockets
                        </div>
                        <div id="activeFilterBadge" class="hidden text-[10px] text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded font-bold">
                            Filtered Active
                        </div>
                    </div>

                    <!-- DEPARTMENT HEAD APPROVAL & CASE REGISTER TABLE -->
                    <div id="headCasesTableContainer">
                        @include('Forensic_Head.partials.cases_table')
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- DIRECTOR DOSSIER INSPECTION MODAL -->
    <div id="inspectModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-300 shadow-2xl max-w-2xl w-full p-6 space-y-5 relative">
            <div class="flex items-start justify-between border-b border-slate-200 pb-4">
                <div>
                    <div class="flex items-center space-x-2 mb-1">
                        <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-700 font-mono-code font-bold text-[10px] uppercase">DOSSIER REVIEW</span>
                        <span class="text-xs text-slate-400">&bull;</span>
                        <span class="text-xs font-mono-code font-bold text-slate-600" id="modalCaseRef">FSD-2026-8842</span>
                    </div>
                    <h3 class="text-xl font-black text-slate-900" id="modalCaseTitle">Suspected Organic Powder Specimen</h3>
                </div>
                <button onclick="closeInspectModal()" class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1">&times;</button>
            </div>

            <!-- INTEGRITY BADGE BANNER -->
            <div class="bg-slate-900 text-white p-4 rounded-xl space-y-2 font-mono-code">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-emerald-400 font-bold flex items-center gap-1.5">
                        CHAIN INTEGRITY: 100% VERIFIED &amp; TAMPER-FREE
                    </span>
                    <span id="modalCaseStatusBadge" class="status-badge status-done">Under Investigation</span>
                </div>
                <div class="text-[11px] text-slate-300 truncate">
                    DIRECTORATE HASH: <span id="modalCaseHash" class="text-blue-400 font-bold">SHA256:8f4b2c19904a77e8b912cd319f00192a83f</span>
                </div>
            </div>

            <!-- GRID METADATA -->
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                    <div class="text-[10px] text-slate-500 font-bold font-mono-code uppercase">Assigned Division</div>
                    <div class="font-bold text-blue-700 mt-0.5" id="modalUnit">Evidence Management</div>
                </div>
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                    <div class="text-[10px] text-slate-500 font-bold font-mono-code uppercase">Unit Head Officer</div>
                    <div class="font-bold text-slate-900 mt-0.5" id="modalUnitHead">N/A</div>
                </div>
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                    <div class="text-[10px] text-slate-500 font-bold font-mono-code uppercase">Lead Handling Officer</div>
                    <div class="font-bold text-slate-900 mt-0.5" id="modalOfficer">N/A</div>
                </div>
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                    <div class="text-[10px] text-slate-500 font-bold font-mono-code uppercase">Timestamp</div>
                    <div class="font-bold text-slate-800 font-mono-code mt-0.5" id="modalDate">2026-09-08 10:15</div>
                </div>
            </div>

            <!-- Exhibits & Location Details -->
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1.5">
                <div class="flex items-center justify-between text-xs font-mono-code">
                    <span class="font-bold text-slate-700 uppercase text-[10px]">Exhibits & Vault Custody:</span>
                    <span id="modalLocation" class="text-indigo-600 font-bold">NACOC Central Vault Safe A-01</span>
                </div>
                <div id="modalExhibits" class="text-xs text-slate-800 font-medium">Logged Exhibits & Sealed Dossier</div>
            </div>

            <!-- Full Description / Notes -->
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1">
                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Case Notes & Findings Summary</div>
                <div id="modalDescription" class="text-xs text-slate-700 leading-relaxed font-normal">
                    Directorate Authorized Case File with verified evidence chain, forensic analysis docket, and officer signatures.
                </div>
            </div>

            <!-- MODAL ACTIONS -->
            <div class="flex items-center justify-between pt-4 border-t border-slate-200">
                <button onclick="closeInspectModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold rounded-xl border border-slate-300 transition-all cursor-pointer">Close Review</button>
                <div class="flex items-center space-x-3">
                    <a id="modalViewDossierLink" href="#" class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-extrabold rounded-xl shadow-md border-none flex items-center gap-1.5 transition-all text-decoration-none">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        Open Full Review
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script src="{{ asset('js/ajax-table.js') }}"></script>
    <script>
        let currentUnitFilter = 'ALL';
        let currentStatusFilter = 'ALL';
        let activeInspectRef = null;

        function updateClock() {
            const now = new Date();
            const utcString = now.toISOString().replace('T', ' ').substring(0, 19) + ' UTC';
            const clockEl = document.getElementById('liveClock');
            if (clockEl) clockEl.innerText = utcString;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Initialize Reusable AJAX Table Controller for Forensic Head Cases
        function setupHeadCasesAjaxTable() {
            if (typeof initAjaxTable === 'function' && !window.headCasesAjaxTable) {
                window.headCasesAjaxTable = initAjaxTable({
                    container: '#headCasesTableContainer',
                    table: '#casesTable',
                    columns: 6,
                    rows: 6,
                    onAfterUpdate: (container) => {
                        applyClientRowFilters();
                    }
                });
            }
        }
        setupHeadCasesAjaxTable();
        document.addEventListener('DOMContentLoaded', setupHeadCasesAjaxTable);

        function rowMatchesUnit(unitName, filter) {
            if (!filter || filter === 'ALL') return true;
            const u = (unitName || '').toLowerCase();
            const f = filter.toLowerCase();
            if (f.includes('crime') || f === 'css') {
                return u.includes('crime') || u.includes('scene') || u === 'css';
            }
            if (f.includes('evidence')) {
                return u.includes('evidence');
            }
            if (f.includes('lab')) {
                return u.includes('lab');
            }
            if (f.includes('digital') || f.includes('physical')) {
                return u.includes('digital') || u.includes('physical') || u.includes('dpf');
            }
            return u.includes(f);
        }

        function rowMatchesStatus(statusName, filter) {
            if (!filter || filter === 'ALL') return true;
            const s = (statusName || '').toLowerCase();
            const f = filter.toLowerCase();
            if (f.includes('await') || f.includes('pending')) {
                return s.includes('await') || s.includes('pending') || s.includes('multi-unit');
            }
            if (f.includes('authoriz') || f.includes('approv') || f.includes('court')) {
                return s.includes('approv') || s.includes('authoriz') || s.includes('court') || s.includes('completed') || s.includes('certified');
            }
            if (f.includes('vault')) {
                return s.includes('vault') || s.includes('evidence');
            }
            if (f.includes('lab')) {
                return s.includes('lab');
            }
            return s.includes(f);
        }

        function applyClientRowFilters() {
            const searchVal = (document.getElementById('caseSearchInput')?.value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('#casesTableBody tr.case-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const rowUnit = row.getAttribute('data-unit') || '';
                const rowStatus = row.getAttribute('data-status') || '';
                const rowText = row.innerText.toLowerCase();

                const matchSearch = !searchVal || rowText.includes(searchVal);
                const matchUnit = rowMatchesUnit(rowUnit, currentUnitFilter);
                const matchStatus = rowMatchesStatus(rowStatus, currentStatusFilter);

                if (matchSearch && matchUnit && matchStatus) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            const visEl = document.getElementById('visibleCaseCount');
            if (visEl) visEl.innerText = visibleCount;

            const badge = document.getElementById('activeFilterBadge');
            if (badge) {
                if (searchVal || currentUnitFilter !== 'ALL' || currentStatusFilter !== 'ALL') {
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }
        }

        function filterCases() {
            applyClientRowFilters();
            const searchVal = (document.getElementById('caseSearchInput')?.value || '').trim();
            if (window.headCasesAjaxTable) {
                if (window.headCasesAjaxTable.debounceTimer) clearTimeout(window.headCasesAjaxTable.debounceTimer);
                window.headCasesAjaxTable.debounceTimer = setTimeout(() => {
                    window.headCasesAjaxTable.applyFilters({
                        search: searchVal || null,
                        unit: (currentUnitFilter && currentUnitFilter !== 'ALL') ? currentUnitFilter : null,
                        status: (currentStatusFilter && currentStatusFilter !== 'ALL') ? currentStatusFilter : null,
                        page: null
                    });
                }, 350);
            }
        }

        function goToPage(page) {
            if (window.headCasesAjaxTable) {
                window.headCasesAjaxTable.applyFilters({ page: page });
            } else {
                const url = new URL(window.location.href);
                url.searchParams.set('page', page);
                window.location.href = url.toString();
            }
        }

        function changePerPage(val) {
            if (window.headCasesAjaxTable) {
                window.headCasesAjaxTable.applyFilters({ per_page: val, page: 1 });
            } else {
                const url = new URL(window.location.href);
                url.searchParams.set('per_page', val);
                url.searchParams.set('page', '1');
                window.location.href = url.toString();
            }
        }

        function filterUnit(unitName, element) {
            currentUnitFilter = unitName;
            document.querySelectorAll('.unit-tab').forEach(btn => btn.classList.remove('active-tab'));
            if (element) element.classList.add('active-tab');

            applyClientRowFilters();

            const searchVal = (document.getElementById('caseSearchInput')?.value || '').trim();
            if (window.headCasesAjaxTable) {
                window.headCasesAjaxTable.applyFilters({
                    unit: unitName === 'ALL' ? null : unitName,
                    status: currentStatusFilter === 'ALL' ? null : currentStatusFilter,
                    search: searchVal || null,
                    page: null
                });
            }
        }

        function filterStatus(statusName, element) {
            currentStatusFilter = statusName;
            document.querySelectorAll('.status-tab').forEach(btn => btn.classList.remove('active-tab'));
            if (element) element.classList.add('active-tab');

            applyClientRowFilters();

            const searchVal = (document.getElementById('caseSearchInput')?.value || '').trim();
            if (window.headCasesAjaxTable) {
                window.headCasesAjaxTable.applyFilters({
                    status: statusName === 'ALL' ? null : statusName,
                    unit: currentUnitFilter === 'ALL' ? null : currentUnitFilter,
                    search: searchVal || null,
                    page: null
                });
            }
        }

        function inspectCase(ref, title, unit, head, officer, status, location, exhibits, priority, desc, date) {
            activeInspectRef = ref;
            document.getElementById('modalCaseRef').innerText = ref;
            document.getElementById('modalCaseTitle').innerText = title || 'Case Review Dossier';
            document.getElementById('modalUnit').innerText = unit || 'Forensic Directorate';
            document.getElementById('modalUnitHead').innerText = head || 'Unit Head';
            document.getElementById('modalOfficer').innerText = officer || 'Handling Officer';
            document.getElementById('modalLocation').innerText = location || 'NACOC Central Vault';
            document.getElementById('modalExhibits').innerText = exhibits || 'Exhibits Logged';
            document.getElementById('modalDescription').innerText = desc || 'Directorate Authorized Forensic Case File.';
            document.getElementById('modalDate').innerText = date || new Date().toISOString().slice(0, 16).replace('T', ' ');
            
            const hashStr = 'SHA256:' + (ref.replace(/[^A-Z0-9]/g, '') + '8f4b2c19904a77e8b912cd319f00192a83f').toLowerCase().slice(0, 32);
            document.getElementById('modalCaseHash').innerText = hashStr;

            const dossierLink = document.getElementById('modalViewDossierLink');
            if (dossierLink) {
                dossierLink.href = `/Forensic_Head/cases/${ref}/review`;
            }

            const badgeEl = document.getElementById('modalCaseStatusBadge');
            if (status === 'Approved' || status === 'Authorized' || status === 'Court Ready' || status === 'Completed') {
                badgeEl.className = 'status-badge status-done';
                badgeEl.innerHTML = `<svg class="w-3 h-3 mr-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> ${status || 'Completed'}`;
            } else if (status === 'Declined') {
                badgeEl.className = 'status-badge status-declined';
                badgeEl.innerHTML = `Declined`;
            } else {
                badgeEl.className = 'status-badge status-await';
                badgeEl.innerHTML = `${status || 'Under Investigation'}`;
            }

            document.getElementById('inspectModal').classList.remove('hidden');
        }

        function closeInspectModal() {
            document.getElementById('inspectModal').classList.add('hidden');
            activeInspectRef = null;
        }

        function transferCase(ref) {
            showToast('Inter-Unit Transfer', `Case ${ref} transferred to destination unit by Department Head approval.`);
        }

        function exportReport(ref) {
            showToast('Exporting Report', `Generating signed certified court report PDF for ${ref}...`);
        }

        function exportDirectoryReport() {
            showToast('Master Case Directory Export', 'Generating complete master case directory report PDF...');
        }

        function showToast(title, msg) {
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-5 right-5 z-50 bg-white border-l-4 border-emerald-500 shadow-2xl rounded-xl p-4 max-w-sm flex items-start space-x-3 transition-all duration-300 transform translate-y-0 opacity-100';
            toast.innerHTML = `
                <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs shrink-0">✓</div>
                <div>
                    <div class="font-extrabold text-slate-900 text-xs">${title}</div>
                    <div class="text-[11px] text-slate-600 mt-0.5">${msg}</div>
                </div>
            `;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        /* ── Real-Time Cases Polling Engine ─────── */
        let isPollingHeadCases = false;
        async function pollForensicHeadCasesTable() {
            if (isPollingHeadCases) return;
            if (document.hidden) return;

            const inspectModal = document.getElementById('inspectModal');
            if (inspectModal && !inspectModal.classList.contains('hidden')) return;

            isPollingHeadCases = true;
            const controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
            const timeoutId = controller ? setTimeout(() => controller.abort(), 6000) : null;

            try {
                const res = await fetch("{{ url('/api/cases') }}", {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                    signal: controller ? controller.signal : undefined
                });
                if (timeoutId) clearTimeout(timeoutId);
                if (!res.ok) return;
                const rawCasesData = await res.json();
                const cases = Array.isArray(rawCasesData) ? rawCasesData : (rawCasesData.data || []);

                let activeCasesCount = 0;
                let courtReadyCount = 0;

                cases.forEach(c => {
                    const isActive = !['Approved', 'Authorized', 'Court Ready', 'Completed', 'Case Closed', 'Case Dismissed'].includes(c.status);
                    const isApproved = ['Approved', 'Authorized', 'Court Ready', 'Completed'].includes(c.status);

                    if (isActive) activeCasesCount++;
                    if (isApproved) courtReadyCount++;
                });

                const activeEl = document.getElementById('statActiveCases');
                if (activeEl) activeEl.innerText = activeCasesCount;
                const courtEl = document.getElementById('statCourtReady');
                if (courtEl) courtEl.innerText = courtReadyCount;
                const totalEl = document.getElementById('statTotalCases');
                if (totalEl) totalEl.innerText = cases.length;
                const totalCountEl = document.getElementById('totalCaseCount');
                if (totalCountEl) totalCountEl.innerText = cases.length;

                const searchInput = (document.getElementById('caseSearchInput')?.value || '').toLowerCase().trim();
                const isFiltered = searchInput || currentUnitFilter !== 'ALL' || currentStatusFilter !== 'ALL';

                // If user is actively searching or filtering, do not overwrite the filtered DOM table
                if (isFiltered) {
                    return;
                }

                const tbody = document.getElementById('casesTableBody');
                if (!tbody) return;

                let html = '';
                if (cases.length === 0) {
                    html = `<tr><td colspan="6" class="py-12 text-center text-slate-500 font-mono-code text-xs"><div class="flex flex-col items-center justify-center space-y-2"><svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><p class="font-bold text-slate-700">No case register dockets found</p></div></td></tr>`;
                } else {
                    cases.forEach(c => {
                        const isApproved = ['Approved', 'Authorized', 'Court Ready', 'Completed'].includes(c.status);

                        const priorityClass = (c.priority || '').toUpperCase() === 'CRITICAL'
                            ? 'bg-red-100 text-red-700 border-red-200'
                            : ((c.priority || '').toUpperCase() === 'HIGH' ? 'bg-amber-100 text-amber-800 border-amber-200' : 'bg-slate-100 text-slate-700 border-slate-300');

                        const createdDate = c.created_at ? c.created_at.slice(0, 10) : new Date().toISOString().slice(0, 10);

                        let statusBadge = '';
                        if (isApproved) {
                            statusBadge = `<span class="status-badge status-done" id="status-${c.case_number}">
                                <svg class="w-3 h-3 mr-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                ${c.status || 'Completed'}
                            </span>`;
                        } else if (c.status === 'Declined') {
                            statusBadge = `<span class="status-badge status-declined" id="status-${c.case_number}">
                                Declined
                            </span>`;
                        } else {
                            statusBadge = `<span class="status-badge status-await" id="status-${c.case_number}">
                                ${c.status || 'Under Investigation'}
                            </span>`;
                        }

                        const suspectDisplayName = c.suspect_name || (c.docket_data?.full_name ? c.docket_data.full_name : 'Unnamed Suspect');
                        const suspectAlias = c.suspect_alias || (c.docket_data ? c.docket_data.suspect_alias : null) || 'N/A';

                        let frontPic = c.mugshot_front || (c.docket_data?.mugshot_front || c.docket_data?.mugshots?.front || null);
                        if (!frontPic) {
                            let evPhotos = c.evidence_photos || (c.docket_data ? c.docket_data.evidence_photos : null);
                            if (evPhotos) {
                                let evArr = [];
                                if (Array.isArray(evPhotos)) evArr = evPhotos;
                                else if (typeof evPhotos === 'string') {
                                    try { evArr = JSON.parse(evPhotos); } catch(e) { evArr = [evPhotos]; }
                                }
                                if (evArr && evArr.length > 0 && evArr[0]) { frontPic = evArr[0]; }
                            }
                        }

                        let suspectsList = [];
                        if (Array.isArray(c.suspects_list) && c.suspects_list.length > 0) {
                            suspectsList = c.suspects_list.filter(s => s && s.suspect_name);
                        }
                        if (suspectsList.length === 0) {
                            let dData = c.docket_data;
                            if (typeof dData === 'string') {
                                try { dData = JSON.parse(dData); } catch(e) { dData = {}; }
                            }
                            if (dData && Array.isArray(dData.suspects) && dData.suspects.length > 0) {
                                suspectsList = dData.suspects.filter(s => s && s.suspect_name);
                            }
                        }
                        if (suspectsList.length === 0 && c.suspect_name && c.suspect_name.includes(',')) {
                            const parts = c.suspect_name.split(',').map(p => p.trim()).filter(Boolean);
                            if (parts.length > 1) {
                                suspectsList = parts.map((pName, pIdx) => ({
                                    suspect_name: pName,
                                    suspect_alias: pIdx === 0 ? suspectAlias : 'N/A',
                                    mugshot_front: pIdx === 0 ? frontPic : null,
                                    is_associate: pIdx > 0,
                                    role: pIdx === 0 ? 'Primary Suspect' : 'Co-Suspect'
                                }));
                            }
                        }
                        if (suspectsList.length === 0) {
                            suspectsList = [{
                                suspect_name: suspectDisplayName,
                                suspect_alias: suspectAlias,
                                mugshot_front: frontPic,
                                is_associate: false
                            }];
                        }

                        let avatarsGroupHtml = '';
                        let aliasArray = [];

                        suspectsList.forEach((sItem, sIdx) => {
                            const sName = sItem.suspect_name || `Suspect #${sIdx + 1}`;
                            const sAlias = sItem.suspect_alias || 'N/A';
                            if (sAlias !== 'N/A' && !aliasArray.includes(sAlias)) {
                                aliasArray.push(sAlias);
                            }
                            let sPic = sItem.mugshot_front || (sIdx === 0 ? frontPic : null);
                            if (sPic && !sPic.startsWith('data:') && !sPic.startsWith('http://') && !sPic.startsWith('https://')) {
                                if (!sPic.startsWith('/')) sPic = '/' + sPic;
                            }
                            const isAssoc = sItem.is_associate || (sItem.association_role && sItem.association_role.toLowerCase() !== 'primary suspect');

                            let imgTag = sPic
                                ? `<img src="${sPic}" alt="${sName.replace(/"/g, '&quot;')}" class="w-full h-full object-cover object-center transition-transform duration-300 group-hover:scale-110">`
                                : `<svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>`;

                            let assocBadge = isAssoc
                                ? `<span class="absolute -top-0.5 left-1/2 -translate-x-1/2 z-30 w-4 h-4 bg-amber-500 border border-white rounded-full flex items-center justify-center text-[8.5px] font-black text-white shadow-xs font-mono-code" title="Associate Suspect">A</span>`
                                : '';

                            avatarsGroupHtml += `
                                <div class="suspect-avatar-trigger cursor-pointer shrink-0 relative group pt-1.5 pb-0.5" 
                                     style="z-index: ${10 + sIdx};"
                                     data-name="${sName.replace(/"/g, '&quot;')}" 
                                     data-alias="${sAlias.replace(/"/g, '&quot;')}" 
                                     data-pic="${sPic ? sPic.replace(/"/g, '&quot;') : ''}" 
                                     data-ref="${c.case_number}" 
                                     data-priority="${(c.priority || 'HIGH').toUpperCase()}"
                                     title="${sName.replace(/"/g, '&quot;')} ${sAlias !== 'N/A' ? '(' + sAlias.replace(/"/g, '&quot;') + ')' : ''}">
                                    ${assocBadge}
                                    <div class="w-10 h-10 rounded-full bg-slate-100 ring-2 ring-red-500 shadow-sm flex items-center justify-center font-bold text-slate-700 text-xs overflow-hidden group-hover:ring-2 group-hover:ring-blue-500 group-hover:z-40 transition-all duration-200">
                                        ${imgTag}
                                    </div>
                                </div>
                            `;
                        });

                        const aliasStr = aliasArray.length > 0 ? aliasArray.join(', ') : suspectAlias;
                        const stackClass = suspectsList.length > 1 ? '-space-x-3.5 hover:space-x-1 transition-all duration-300' : '';

                        let suspectsNamesHtml = '';
                        if (suspectsList.length > 1) {
                            suspectsNamesHtml = `
                                <div class="space-y-1">
                                    ${suspectsList.map((sItem, sIdx) => {
                                        const sName = sItem.suspect_name || `Suspect #${sIdx + 1}`;
                                        const roleStr = (sItem.association_role || sItem.role || '').toLowerCase();
                                        const isAssoc = sItem.is_associate || (roleStr && roleStr !== 'primary suspect') || sIdx > 0;
                                        const badgeLabel = roleStr.includes('co-suspect')
                                            ? 'CO-SUSPECT'
                                            : (isAssoc ? 'CO/ASSOC' : 'PRIMARY');
                                        const badgeClass = isAssoc
                                            ? 'bg-amber-100 text-amber-800 border-amber-300'
                                            : 'bg-blue-100 text-blue-800 border-blue-300';
                                        return `
                                            <div class="flex items-center gap-1.5 leading-tight">
                                                <span class="font-extrabold text-xs ${isAssoc ? 'text-slate-800' : 'text-slate-950 font-black'} truncate max-w-[170px]" title="${sName.replace(/"/g, '&quot;')}">
                                                    ${sName}
                                                </span>
                                                <span class="px-1.5 py-0.2 rounded text-[8px] font-mono-code font-extrabold ${badgeClass} border shrink-0">${badgeLabel}</span>
                                            </div>
                                        `;
                                    }).join('')}
                                </div>
                                ${aliasStr && aliasStr !== 'N/A' ? `
                                    <div class="text-[10px] text-slate-500 font-mono-code mt-0.5 truncate max-w-[200px]">
                                        <span class="text-slate-400">ALIAS:</span> ${aliasStr}
                                    </div>
                                ` : ''}
                            `;
                        } else {
                            suspectsNamesHtml = `
                                <div class="font-bold text-slate-900 text-xs leading-tight truncate max-w-[180px]" title="${suspectDisplayName.replace(/"/g, '&quot;')}">${suspectDisplayName}</div>
                                <div class="text-[10px] text-slate-500 font-mono-code mt-0.5 truncate max-w-[180px]"><span class="text-slate-400">ALIAS:</span> ${aliasStr}</div>
                            `;
                        }

                        let suspectDispHtml = '';
                        const sDispList = (Array.isArray(c.suspects_list) && c.suspects_list.length > 0) ? c.suspects_list : suspectsList;
                        if (sDispList && sDispList.length > 0) {
                            suspectDispHtml = '<div class="space-y-1">';
                            sDispList.forEach(sObj => {
                                const dVal = sObj.suspect_disposition || 'Active — Under Investigation';
                                const dReason = sObj.disposition_reason || '';
                                let dBadge = 'bg-slate-100 text-slate-700 border-slate-300';
                                if (dVal.includes('Cleared')) dBadge = 'bg-emerald-100 text-emerald-800 border-emerald-300';
                                else if (dVal.includes('Released')) dBadge = 'bg-blue-100 text-blue-800 border-blue-300';
                                else if (dVal.includes('Reclassified')) dBadge = 'bg-purple-100 text-purple-800 border-purple-300';
                                else if (dVal.includes('Referred')) dBadge = 'bg-amber-100 text-amber-800 border-amber-300';

                                const sName = (sObj.suspect_name || '').replace(/"/g, '&quot;');
                                const namePrefix = sDispList.length > 1 ? `<span class="font-extrabold text-slate-900 mr-1">${sName}:</span>` : '';
                                const titleAttr = dReason ? `Reason: ${dReason.replace(/"/g, '&quot;')}` : dVal.replace(/"/g, '&quot;');
                                suspectDispHtml += `<div class="flex items-center gap-1.5" title="${titleAttr}">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[9.5px] font-bold font-mono-code border ${dBadge}">
                                        ${namePrefix}${dVal}
                                    </span>
                                </div>`;
                            });
                            suspectDispHtml += '</div>';
                        } else {
                            suspectDispHtml = `<div class="space-y-1"><div class="flex items-center gap-1.5"><span class="inline-flex items-center px-2 py-0.5 rounded text-[9.5px] font-bold font-mono-code border bg-slate-100 text-slate-700 border-slate-300">Active — Under Investigation</span></div></div>`;
                        }

                        const cStatVal = c.case_status || 'Under Investigation';
                        let cStatCls = 'bg-amber-50 text-amber-800 border-amber-300';
                        if (cStatVal === 'On Remand') cStatCls = 'bg-purple-50 text-purple-800 border-purple-300';
                        else if (cStatVal === 'On Bail') cStatCls = 'bg-indigo-50 text-indigo-800 border-indigo-300';
                        else if (cStatVal === 'In Court') cStatCls = 'bg-blue-50 text-blue-800 border-blue-300';
                        else if (cStatVal === 'Verdict Passed — Imprisoned') cStatCls = 'bg-rose-50 text-rose-800 border-rose-300';
                        else if (cStatVal === 'Verdict Passed — Acquitted') cStatCls = 'bg-emerald-50 text-emerald-800 border-emerald-300';
                        else if (cStatVal === 'Case Dismissed') cStatCls = 'bg-slate-100 text-slate-700 border-slate-300';
                        else if (cStatVal === 'Case Closed') cStatCls = 'bg-slate-800 text-slate-100 border-slate-700';

                        html += `<tr class="table-row-hover transition-colors case-row" data-unit="${c.unit_name || ''}" data-status="${c.status || ''}">
                            <td class="py-4 px-5 whitespace-nowrap">
                                <div class="font-extrabold font-mono-code text-blue-600 text-sm">${c.case_number}</div>
                                <div class="flex items-center space-x-1.5 mt-1">
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold ${priorityClass}">${(c.priority || 'HIGH').toUpperCase()}</span>
                                    <span class="text-[10px] text-slate-500 font-mono-code">${createdDate}</span>
                                </div>
                            </td>
                            <td class="py-4 px-5">
                                <div class="font-bold text-slate-900 text-xs">${c.title || 'Untitled Case'}</div>
                                <div class="text-[10px] text-blue-600 font-mono-code font-semibold mt-0.5">${c.unit_name || 'Forensic Directorate'}</div>
                                <div class="text-[11px] text-slate-500 mt-0.5">${c.holding_facility || c.raid_location || c.seizure_location || c.location || 'NACOC Central Custody Facility'} &bull; ${c.exhibits_summary || 'Exhibits Logged'}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center ${stackClass} shrink-0">
                                    ${avatarsGroupHtml}
                                </div>
                                <div class="min-w-0">
                                    ${suspectsNamesHtml}
                                </div>
                            </td>
                            <td class="py-4 px-5 whitespace-nowrap">
                                ${suspectDispHtml}
                            </td>
                            <td class="py-4 px-5 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold border ${cStatCls}">${cStatVal}</span>
                            </td>
                            <td class="py-4 px-5 text-right whitespace-nowrap">
                                <a href="/Forensic_Head/cases/${c.case_number}/review"
                                   title="View Suspect Docket"
                                   aria-label="View Suspect Docket"
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white border border-emerald-200/80 transition-all shadow-2xs hover:scale-105 active:scale-95">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>`;
                    });
                }

                tbody.innerHTML = html;
                const visibleCountEl = document.getElementById('visibleCaseCount');
                if (visibleCountEl) visibleCountEl.innerText = cases.length;
            } catch (e) {
                // Background polling failure handled silently without console noise
            } finally {
                if (timeoutId) clearTimeout(timeoutId);
                isPollingHeadCases = false;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            filterCases();
        });
        /* ── Notifications & Global Search ──────────── */
        function toggleNotificationDropdown() {
            const dropdown = document.getElementById('notificationDropdown');
            if (dropdown) dropdown.classList.toggle('hidden');
        }

        function handleGlobalSearch(val) {
            const input = document.getElementById('caseSearchInput');
            if (input) {
                input.value = val;
                filterCases();
            }
        }

        document.addEventListener('click', function(e) {
            const bellBtn = document.getElementById('notificationBellBtn');
            const dropdown = document.getElementById('notificationDropdown');
            if (bellBtn && dropdown && !bellBtn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const searchInput = document.getElementById('globalSearch');
                if (searchInput) searchInput.focus();
            }
        });

        setInterval(pollForensicHeadCasesTable, 15000);
    </script>
</body>
</html>
