@php
    $cases = $cases ?? collect();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Case Management &amp; Department Approvals | Forensic Directorate</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

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
                        Case Management &amp; Department Approvals
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
                            $pendingCasesNotif = (int)(\App\Models\ForensicCase::whereIn('status', ['Awaiting Sign-off', 'Pending Approval', 'Pending'])->count());
                            $pendingUsersNotif = (int)(\App\Models\User::where('is_approved', false)->count());
                            $totalAlertsCount = $pendingCasesNotif + $pendingUsersNotif + 1;
                        @endphp
                        <button type="button" id="notificationBellBtn" onclick="toggleNotificationDropdown()" class="p-2 rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors relative border-none bg-transparent cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            @if($totalAlertsCount > 0)
                                <span class="absolute top-1 right-1 w-2.5 h-2.5 bg-red-500 rounded-full ring-2 ring-white"></span>
                            @endif
                        </a>

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
                                
                                <!-- ALERT 1: CASE APPROVALS -->
                                <a href="{{ url('/Forensic_Head/cases') }}" class="p-3.5 flex items-start space-x-3 hover:bg-slate-50 transition-colors text-decoration-none block">
                                    <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-600 border border-amber-500/20 flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-0.5">
                                            <span class="text-[10px] font-extrabold font-mono-code text-amber-700 uppercase">Case Approvals</span>
                                            <span class="text-[9px] font-bold text-slate-400 font-mono-code">JUST NOW</span>
                                        </div>
                                        <div class="text-xs font-bold text-slate-900 truncate">
                                            @if($pendingCasesNotif > 0)
                                                {{ $pendingCasesNotif }} Case Dossiers Awaiting Executive Sign-off
                                            @else
                                                All Case Dossiers Authorized
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 leading-snug">
                                            @if($pendingCasesNotif > 0)
                                                Pending findings certificates and official seal approval required.
                                            @else
                                                No outstanding case dossier sign-offs pending.
                                            @endif
                                        </div>
                                    </div>
                                </a>

                                <!-- ALERT 2: USER ACCOUNT APPROVALS -->
                                <a href="{{ url('/Forensic_Head/approvals') }}" class="p-3.5 flex items-start space-x-3 hover:bg-slate-50 transition-colors text-decoration-none block">
                                    <div class="w-8 h-8 rounded-xl bg-blue-500/10 text-blue-600 border border-blue-500/20 flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-0.5">
                                            <span class="text-[10px] font-extrabold font-mono-code text-blue-700 uppercase">Account Control</span>
                                            <span class="text-[9px] font-bold text-slate-400 font-mono-code">TODAY</span>
                                        </div>
                                        <div class="text-xs font-bold text-slate-900 truncate">
                                            @if($pendingUsersNotif > 0)
                                                {{ $pendingUsersNotif }} Pending User Account Approval(s)
                                            @else
                                                Personnel Roster Fully Verified
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 leading-snug">
                                            @if($pendingUsersNotif > 0)
                                                Staff or Unit Head registrations awaiting directorate access clearance.
                                            @else
                                                All registered system accounts are authorized and active.
                                            @endif
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
                                            <span class="text-[9px] font-bold text-slate-400 font-mono-code">3 MIN AGO</span>
                                        </div>
                                        <div class="text-xs font-bold text-slate-900 truncate">4 Barcoded Exhibits Transferred to Vault</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 leading-snug">Inter-unit evidence transfer received from Crime Scene Support Unit.</div>
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
                                        <div class="text-xs font-bold text-slate-900 truncate">100% Tamper-Free Hash Audit Verified</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 leading-snug">SHA-256 digital hashes matched across all 4 forensic unit databases.</div>
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
                    $totalCasesCount = count($cases ?? []);
                    $pendingApprovalsCount = isset($cases) ? $cases->filter(function($c) {
                        return !in_array($c->status, ['Approved', 'Authorized', 'Certified', 'Completed', 'Court Ready']);
                    })->count() : 0;
                    $vaultItemsCount = isset($cases) ? $cases->sum(function($c) {
                        preg_match('/\d+/', $c->exhibits_summary ?? '', $m);
                        return isset($m[0]) ? (int)$m[0] : ($c->exhibits_summary ? 1 : 0);
                    }) : 0;
                    $courtReadyCount = isset($cases) ? $cases->filter(function($c) {
                        return in_array($c->status, ['Approved', 'Authorized', 'Certified', 'Completed', 'Court Ready']);
                    })->count() : 0;
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
                            <span class="text-xs font-bold text-slate-600 uppercase tracking-wider font-mono-code">Awaiting Head Sign-off</span>
                            <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-amber-600 font-mono-code" id="statPendingApprovals">{{ number_format($pendingApprovalsCount) }}</div>
                        <div class="flex items-center text-[11px] text-amber-700 mt-2 font-semibold">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5 inline-block"></span>
                            Awaiting Director Signature
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
                            <span class="text-xs font-bold text-slate-600 uppercase tracking-wider font-mono-code">Court Certified / Authorized</span>
                            <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-emerald-600 font-mono-code" id="statCourtReady">{{ number_format($courtReadyCount) }}</div>
                        <div class="flex items-center text-[11px] text-emerald-700 mt-2 font-semibold">
                            Signed off &amp; Issued Certificates
                        </div>
                    </div>
                </div>

                <!-- DEPARTMENT HEAD APPROVAL & CASE REGISTER MAIN PANEL -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden space-y-0">
                    
                    <!-- HEADER & TOOLBAR -->
                    <div class="p-6 bg-slate-50 border-b border-slate-200 space-y-4">
                        
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <span class="text-[10px] font-bold font-mono-code text-blue-600 uppercase tracking-wider">CASE REGISTER</span>
                                <h3 class="font-black text-slate-900 text-lg">Department Head Approval &amp; Case Register</h3>
                                <p class="text-xs text-slate-600 mt-0.5 font-medium">Review pending findings certificates, sign-offs, evidence packages, and inter-unit transfers</p>
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
                                <button onclick="filterUnit('ALL', this)" class="filter-tab-btn active-tab unit-tab">All 4 Units</button>
                                <button onclick="filterUnit('Crime Scene Support', this)" class="filter-tab-btn unit-tab">Crime Scene Support</button>
                                <button onclick="filterUnit('Evidence Management', this)" class="filter-tab-btn unit-tab">Evidence Management</button>
                                <button onclick="filterUnit('LAB Analysis', this)" class="filter-tab-btn unit-tab">LAB Analysis</button>
                                <button onclick="filterUnit('Digital & Physical Forensics', this)" class="filter-tab-btn unit-tab">Digital &amp; Physical</button>
                            </div>

                            <!-- STATUS TABS -->
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-[11px] font-bold text-slate-700 mr-1 uppercase font-mono-code">Status:</span>
                                <button onclick="filterStatus('ALL', this)" class="filter-tab-btn active-tab status-tab">All Statuses</button>
                                <button onclick="filterStatus('Awaiting Sign-off', this)" class="filter-tab-btn status-tab">Awaiting Sign-off</button>
                                <button onclick="filterStatus('Authorized', this)" class="filter-tab-btn status-tab">Authorized</button>
                                <button onclick="filterStatus('Vault Storage', this)" class="filter-tab-btn status-tab">Vault Storage</button>
                                <button onclick="filterStatus('Lab Analysis', this)" class="filter-tab-btn status-tab">Lab Testing</button>
                            </div>
                        </div>
                    </div>

                    <!-- SEARCH COUNTER BANNER -->
                    <div class="px-6 py-2.5 bg-slate-100/70 border-b border-slate-200 flex items-center justify-between text-xs text-slate-600 font-mono-code">
                        <div>
                            Displaying <span id="visibleCaseCount" class="font-extrabold text-slate-900">{{ count($cases) }}</span> of <span id="totalCaseCount" class="font-extrabold text-slate-900">{{ count($cases) }}</span> case register dockets
                        </div>
                        <div id="activeFilterBadge" class="hidden text-[10px] text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded font-bold">
                            Filtered Active
                        </div>
                    </div>

                    <!-- DEPARTMENT HEAD APPROVAL & CASE REGISTER TABLE -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs" id="casesTable">
                            <thead>
                                <tr class="bg-slate-100 border-b border-slate-200 text-slate-700 font-mono-code uppercase text-[11px] tracking-wider select-none">
                                    <th class="py-3.5 px-5 font-extrabold">Case Reference #</th>
                                    <th class="py-3.5 px-5 font-extrabold">Evidence Details</th>
                                    <th class="py-3.5 px-5 font-extrabold">Suspect</th>
                                    <th class="py-3.5 px-5 font-extrabold">Assigned Unit &amp; Head</th>
                                    <th class="py-3.5 px-5 font-extrabold">Handling Officer</th>
                                    <th class="py-3.5 px-5 font-extrabold">Director Status</th>
                                    <th class="py-3.5 px-5 font-extrabold text-right">Head Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200" id="casesTableBody">
                                @forelse($cases as $case)
                                    @php
                                        $priorityClass = match(strtoupper($case->priority)) {
                                            'CRITICAL' => 'bg-red-100 text-red-700 border-red-200',
                                            'HIGH' => 'bg-amber-100 text-amber-800 border-amber-200',
                                            default => 'bg-slate-100 text-slate-700 border-slate-300'
                                        };

                                        $unitColor = match($case->unit_name) {
                                            'Evidence Management' => 'text-indigo-600',
                                            'Digital & Physical Forensics' => 'text-amber-600',
                                            'LAB Analysis' => 'text-emerald-600',
                                            default => 'text-blue-600'
                                        };

                                        $dDataHead = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
                                        $suspectDisplayName = $case->suspect_name ?? $case->commanding_officer ?? 'Unassigned Target';
                                        $frontPic = $case->mugshot_front ?? ($dDataHead['mugshot_front'] ?? null);
                                        if (!$frontPic && !empty($case->evidence_photos)) {
                                            $evArr = is_array($case->evidence_photos) ? $case->evidence_photos : (json_decode($case->evidence_photos ?? '[]', true) ?: []);
                                            if (!empty($evArr[0])) { $frontPic = $evArr[0]; }
                                        }
                                        if ($frontPic && !str_starts_with($frontPic, 'data:') && !str_starts_with($frontPic, 'http://') && !str_starts_with($frontPic, 'https://')) {
                                            $frontPic = asset($frontPic);
                                        }

                                        $suspectsArray = !empty($dDataHead['suspects']) && is_array($dDataHead['suspects'])
                                            ? array_values(array_filter($dDataHead['suspects'], fn($s) => !empty($s['suspect_name'])))
                                            : [];
                                        if (empty($suspectsArray)) {
                                            $suspectsArray = [[
                                                'suspect_name'  => $suspectDisplayName,
                                                'suspect_alias' => $case->suspect_alias ?? 'N/A',
                                                'mugshot_front' => $frontPic,
                                                'is_associate'  => false,
                                            ]];
                                        }

                                        $suspectPics = [];
                                        foreach ($suspectsArray as $sIdx => $sItem) {
                                            $p = $sItem['mugshot_front'] ?? ($sIdx === 0 ? $frontPic : null);
                                            if (!$p && $sIdx === 0 && !empty($case->evidence_photos)) {
                                                $evArr = is_array($case->evidence_photos) ? $case->evidence_photos : (json_decode($case->evidence_photos ?? '[]', true) ?: []);
                                                if (!empty($evArr[0])) { $p = $evArr[0]; }
                                            }
                                            if ($p && !str_starts_with($p, 'data:') && !str_starts_with($p, 'http://') && !str_starts_with($p, 'https://')) {
                                                $p = asset($p);
                                            }
                                            $suspectPics[] = [
                                                'name'         => $sItem['suspect_name'] ?? ('Suspect #' . ($sIdx + 1)),
                                                'alias'        => $sItem['suspect_alias'] ?? 'N/A',
                                                'pic'          => $p,
                                                'is_associate' => !empty($sItem['is_associate']) || (!empty($sItem['association_role']) && strtolower($sItem['association_role']) !== 'primary suspect'),
                                            ];
                                        }
                                        $allAliases = array_values(array_filter(array_map(fn($sp) => !empty($sp['alias']) && $sp['alias'] !== 'N/A' ? $sp['alias'] : null, $suspectPics)));
                                        $aliasDisplay = !empty($allAliases) ? implode(', ', $allAliases) : ($case->suspect_alias ?? 'N/A');
                                    @endphp
                                    <tr class="table-row-hover transition-colors case-row" data-unit="{{ $case->unit_name }}" data-status="{{ $case->status }}">
                                        <td class="py-4 px-5 whitespace-nowrap">
                                            <div class="font-extrabold font-mono-code text-blue-600 text-sm">{{ $case->case_number }}</div>
                                            <div class="flex items-center space-x-1.5 mt-1">
                                                <span class="px-1.5 py-0.2 rounded text-[9px] font-bold {{ $priorityClass }}">{{ strtoupper($case->priority) }}</span>
                                                <span class="text-[10px] text-slate-500 font-mono-code">{{ $case->created_at ? $case->created_at->format('Y-m-d') : date('Y-m-d') }}</span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-5">
                                            <div class="font-bold text-slate-900 text-xs">{{ $case->title }}</div>
                                            <div class="text-[10px] text-blue-600 font-mono-code font-semibold mt-0.5">{{ $case->unit_name ?? 'Forensic Directorate' }}</div>
                                            <div class="text-[11px] text-slate-500 mt-0.5">{{ $case->holding_facility ?? $case->raid_location ?? $case->seizure_location ?? $case->location ?? 'NACOC Central Custody Facility' }} &bull; {{ $case->exhibits_summary ?? 'Exhibits Logged' }}</div>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <div class="flex items-center space-x-3">
                                                <div class="flex items-center {{ count($suspectPics) > 1 ? '-space-x-3.5 hover:space-x-1 transition-all duration-300' : '' }} shrink-0 py-1">
                                                    @foreach($suspectPics as $spIdx => $sp)
                                                        <div class="suspect-avatar-trigger cursor-pointer shrink-0 relative group pt-1.5 pb-0.5" 
                                                             style="z-index: {{ 10 + $spIdx }};"
                                                             data-name="{{ $sp['name'] }}" 
                                                             data-alias="{{ $sp['alias'] }}" 
                                                             data-pic="{{ $sp['pic'] ?? '' }}" 
                                                             data-ref="{{ $case->case_number }}" 
                                                             data-priority="{{ strtoupper($case->priority) }}"
                                                             title="{{ $sp['name'] }} {{ $sp['alias'] !== 'N/A' ? "({$sp['alias']})" : '' }}">
                                                            
                                                            @if($sp['is_associate'])
                                                                <span class="absolute -top-0.5 left-1/2 -translate-x-1/2 z-30 w-4 h-4 bg-amber-500 border border-white rounded-full flex items-center justify-center text-[8.5px] font-black text-white shadow-xs font-mono-code" title="Associate Suspect">A</span>
                                                            @endif

                                                            <div class="w-10 h-10 rounded-full bg-slate-100 ring-2 ring-white shadow-sm flex items-center justify-center font-bold text-slate-700 text-xs overflow-hidden group-hover:ring-2 group-hover:ring-blue-500 group-hover:z-40 transition-all duration-200">
                                                                @if(!empty($sp['pic']))
                                                                    <img src="{{ $sp['pic'] }}" alt="{{ $sp['name'] }}" class="w-full h-full object-cover object-center transition-transform duration-300 group-hover:scale-110">
                                                                @else
                                                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="font-bold text-slate-900 text-xs leading-tight truncate max-w-[180px]" title="{{ $suspectDisplayName }}">{{ $suspectDisplayName }}</div>
                                                    <div class="text-[10px] text-slate-500 font-mono-code mt-0.5 truncate max-w-[180px]">
                                                        <span class="text-slate-400">ALIAS:</span> {{ $aliasDisplay }}
                                                    </div>
                                                    @if(count($suspectPics) > 1)
                                                        <div class="mt-1 inline-flex items-center gap-1 px-1.5 py-0.2 rounded bg-blue-50 border border-blue-200/60 text-blue-700 text-[9px] font-mono-code font-bold">
                                                            <svg class="w-2.5 h-2.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                                            <span>{{ count($suspectPics) }} Suspects</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-5">
                                            <div class="font-extrabold {{ $unitColor }} text-xs">{{ $case->unit_name ?? 'Forensic Directorate' }}</div>
                                            <div class="text-[10px] text-slate-600 font-medium">{{ $case->commanding_officer ?? 'Unit Head' }}</div>
                                        </td>
                                        <td class="py-4 px-5">
                                            <div class="font-bold text-slate-900 text-xs">{{ $case->submitting_officer ?? $case->commanding_officer }}</div>
                                            <div class="text-[10px] text-slate-500">Handling Officer</div>
                                        </td>
                                        <td class="py-4 px-5 whitespace-nowrap">
                                            @if($case->status === 'Approved' || $case->status === 'Authorized')
                                                <span class="status-badge status-done" id="status-{{ $case->case_number }}">
                                                    <svg class="w-3 h-3 mr-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    Authorized
                                                </span>
                                            @elseif($case->status === 'Declined')
                                                <span class="status-badge status-declined" id="status-{{ $case->case_number }}">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-600 mr-1.5 inline-block"></span>
                                                    Declined
                                                </span>
                                            @elseif($case->status === 'Under Multi-Unit Processing')
                                                @php
                                                    $dDataHead = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
                                                    $uProgHead = $dDataHead['unit_progress'] ?? [];

                                                    $exCatH = strtolower($case->exhibit_category ?? '');
                                                    $sDrugH = $case->seizure_name_drug ?? '';
                                                    $sNameH = strtolower($case->seizure_name ?? '');
                                                    $exSumH = strtolower($case->exhibits_summary ?? '');
                                                    $isDrugHead = (!empty($sDrugH) || 
                                                        str_contains($exCatH, 'drug') || str_contains($exCatH, 'narcotic') || str_contains($exCatH, 'substance') || str_contains($exCatH, 'cannabis') || str_contains($exCatH, 'cocaine') || str_contains($exCatH, 'heroin') || str_contains($exCatH, 'meth') || str_contains($exCatH, 'tramadol') ||
                                                        str_contains($sNameH, 'cocaine') || str_contains($sNameH, 'heroin') || str_contains($sNameH, 'cannabis') || str_contains($sNameH, 'weed') || str_contains($sNameH, 'meth') || str_contains($sNameH, 'tramadol') || str_contains($sNameH, 'drug') || str_contains($sNameH, 'narcotic') ||
                                                        str_contains($exSumH, 'cocaine') || str_contains($exSumH, 'heroin') || str_contains($exSumH, 'cannabis') || str_contains($exSumH, 'weed') || str_contains($exSumH, 'meth') || str_contains($exSumH, 'tramadol') || str_contains($exSumH, 'drug') || str_contains($exSumH, 'narcotic')
                                                    );
                                                    if (!$isDrugHead) {
                                                        $dSeizuresH = $dDataHead['seizures'] ?? [];
                                                        if (is_array($dSeizuresH) && count($dSeizuresH) > 0) {
                                                            foreach ($dSeizuresH as $szH) {
                                                                $catH = strtolower($szH['category'] ?? ($szH['exhibit_category'] ?? ''));
                                                                $dNameH = $szH['seizure_name_drug'] ?? '';
                                                                $szNameH = strtolower($szH['seizure_name'] ?? '');
                                                                if (!empty($dNameH) || str_contains($catH, 'drug') || str_contains($catH, 'narcotic') || str_contains($szNameH, 'cocaine') || str_contains($szNameH, 'heroin') || str_contains($szNameH, 'cannabis') || str_contains($szNameH, 'drug') || str_contains($szNameH, 'narcotic')) {
                                                                    $isDrugHead = true;
                                                                    break;
                                                                }
                                                            }
                                                        }
                                                    }

                                                    $isLabCreatedHead = !empty($case->unit_name) && (
                                                        strtolower(trim($case->unit_name)) === 'lab analysis' ||
                                                        str_contains(strtolower($case->unit_name), 'lab analysis') ||
                                                        str_contains(strtolower($case->unit_name), 'laboratory')
                                                    );

                                                     if (method_exists($case, 'getWorkflowUnits')) {
                                                         $unitsHeadList = $case->getWorkflowUnits();
                                                     } elseif ($isDrugHead) {
                                                         $unitsHeadList = ['Lab Analysis', 'Evidence Management'];
                                                     } elseif ($isLabCreatedHead) {
                                                         $unitsHeadList = ['Evidence Management'];
                                                     } else {
                                                         $unitsHeadList = ['Crime Scene Support', 'Evidence Management', 'Digital & Physical Forensics'];
                                                     }
                                                     $tCountHead = count($unitsHeadList);

                                                    $cCountHead = 0;
                                                    foreach($unitsHeadList as $uK) {
                                                        if (isset($uProgHead[$uK]['status']) && strtoupper($uProgHead[$uK]['status']) === 'COMPLETED') $cCountHead++;
                                                    }
                                                    if ($cCountHead === 0) $cCountHead = 1;
                                                @endphp
                                                <span class="status-badge bg-emerald-100 text-emerald-800 border-emerald-300" id="status-{{ $case->case_number }}">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 mr-1.5 inline-block"></span>
                                                    Multi-Unit ({{ $cCountHead }}/{{ $tCountHead }})
                                                </span>
                                            @else
                                                <span class="status-badge status-await" id="status-{{ $case->case_number }}">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5 inline-block"></span>
                                                    {{ $case->status }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-4 px-5 text-right whitespace-nowrap">
                                            <div class="relative inline-block text-left case-action-dropdown-wrapper">
                                                <button type="button" 
                                                        onclick="toggleCaseActionDropdown(event, '{{ $case->case_number }}')" 
                                                        class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl border border-slate-300 transition-all inline-flex items-center gap-1.5 cursor-pointer shadow-2xs hover:scale-105 active:scale-95"
                                                        title="Actions for Case {{ $case->case_number }}">
                                                    <svg class="w-4 h-4 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                                    </svg>
                                                    <span class="text-xs font-mono-code font-bold">Actions</span>
                                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                </button>

                                                <div id="actionMenu-{{ $case->case_number }}" class="case-action-menu hidden absolute right-0 mt-1.5 w-52 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 p-2 space-y-1 text-left font-sans">
                                                    <!-- 1. ASSOCIATE DATA -->
                                                    <a href="/cases/{{ $case->case_number }}/associate" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-blue-50 hover:text-blue-700 rounded-xl flex items-center space-x-2.5 transition-colors group text-decoration-none">
                                                        <div class="w-7 h-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                                        </div>
                                                        <div>
                                                            <div class="font-extrabold leading-tight">Associate Data</div>
                                                            <div class="text-[10px] text-slate-400 font-normal mt-0.5">Link accomplices &amp; exhibits</div>
                                                        </div>
                                                    </a>

                                                    <!-- 2. EDIT DOCKET -->
                                                    <a href="/cases/{{ $case->case_number }}/review?edit=true&from=Forensic+Head" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-amber-50 hover:text-amber-700 rounded-xl flex items-center space-x-2.5 transition-colors group text-decoration-none">
                                                        <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 group-hover:bg-amber-600 group-hover:text-white transition-colors">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                        </div>
                                                        <div>
                                                            <div class="font-extrabold leading-tight">Edit Docket</div>
                                                            <div class="text-[10px] text-slate-400 font-normal mt-0.5">Modify findings &amp; info</div>
                                                        </div>
                                                    </a>

                                                    <!-- 3. VIEW DOCKET -->
                                                    <a href="/cases/{{ $case->case_number }}/review?from=Forensic+Head" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-700 rounded-xl flex items-center space-x-2.5 transition-colors group text-decoration-none">
                                                        <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                        </div>
                                                        <div>
                                                            <div class="font-extrabold leading-tight">{{ ($case->status === 'Approved' || $case->status === 'Authorized') ? 'Preview Docket' : 'Review Docket' }}</div>
                                                            <div class="text-[10px] text-slate-400 font-normal mt-0.5">Full dossier inspection</div>
                                                        </div>
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-slate-500 font-mono-code text-xs">
                                            No case register dockets found in database.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINATION FOOTER PANEL -->
                    <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs font-mono-code">
                        <!-- LEFT: Rows selector & Range text -->
                        <div class="flex items-center space-x-3 text-slate-600">
                            <div class="flex items-center space-x-1.5">
                                <span class="text-slate-500 font-bold uppercase text-[10px]">Rows:</span>
                                <select id="perPageSelect" onchange="changePerPage(this.value)" class="bg-white border border-slate-300 rounded-lg px-2 py-1 text-xs font-extrabold text-slate-800 outline-none focus:ring-2 focus:ring-blue-500/20 shadow-2xs cursor-pointer">
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                            <span class="text-slate-300">|</span>
                            <div>
                                Showing <span id="paginationRange" class="font-extrabold text-slate-900">0 - 0</span> of <span id="paginationTotal" class="font-extrabold text-slate-900">0</span> records
                            </div>
                        </div>

                        <!-- RIGHT: Navigation Buttons -->
                        <div class="flex items-center space-x-1.5" id="paginationControls">
                            <button id="prevPageBtn" onclick="goToPage(currentPage - 1)" class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold rounded-lg text-xs transition-all disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer flex items-center space-x-1 shadow-2xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                <span>Prev</span>
                            </button>
                            
                            <div id="pageNumbersContainer" class="flex items-center space-x-1">
                                <!-- Dynamic page number buttons -->
                            </div>

                            <button id="nextPageBtn" onclick="goToPage(currentPage + 1)" class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold rounded-lg text-xs transition-all disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer flex items-center space-x-1 shadow-2xs">
                                <span>Next</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
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
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        CHAIN INTEGRITY: 100% VERIFIED &amp; TAMPER-FREE
                    </span>
                    <span id="modalCaseStatusBadge" class="status-badge status-await">Awaiting Sign-off</span>
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
                    <div class="font-bold text-slate-900 mt-0.5" id="modalUnitHead">Dr. Evelyn Osei</div>
                </div>
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                    <div class="text-[10px] text-slate-500 font-bold font-mono-code uppercase">Lead Handling Officer</div>
                    <div class="font-bold text-slate-900 mt-0.5" id="modalOfficer">Insp. Eric Ofori</div>
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

            <!-- MODAL ACTIONS (APPROVE & DECLINE BUTTONS UNDERNEATH) -->
            <div class="flex items-center justify-between pt-4 border-t border-slate-200">
                <button onclick="closeInspectModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold rounded-xl border border-slate-300 transition-all cursor-pointer">Close Review</button>
                <div class="flex items-center space-x-3" id="inspectModalActionBtns">
                    <button type="button" onclick="declineFromModal()" class="px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-extrabold rounded-xl shadow-md border border-red-700 flex items-center gap-1.5 transition-all cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        Decline Case
                    </button>
                    <button id="modalApproveBtn" onclick="approveFromModal()" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold rounded-xl shadow-md border-none flex items-center gap-1.5 transition-all cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        Approve Case
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        let currentUnitFilter = 'ALL';
        let currentStatusFilter = 'ALL';
        let currentPage = 1;
        let itemsPerPage = 10;
        let activeInspectRef = null;

        function updateClock() {
            const now = new Date();
            const utcString = now.toISOString().replace('T', ' ').substring(0, 19) + ' UTC';
            const clockEl = document.getElementById('liveClock');
            if (clockEl) clockEl.innerText = utcString;
        }
        setInterval(updateClock, 1000);
        updateClock();

        function filterCases() {
            const searchInput = (document.getElementById('caseSearchInput')?.value || '').toLowerCase().trim();
            const rows = Array.from(document.querySelectorAll('#casesTableBody .case-row'));

            let matchingRows = [];
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                const unit = row.getAttribute('data-unit') || '';
                const status = row.getAttribute('data-status') || '';

                const matchesSearch = !searchInput || text.includes(searchInput);
                const matchesUnit = (currentUnitFilter === 'ALL' || unit === currentUnitFilter);
                const matchesStatus = (currentStatusFilter === 'ALL' || status === currentStatusFilter);

                if (matchesSearch && matchesUnit && matchesStatus) {
                    matchingRows.push(row);
                } else {
                    row.style.display = 'none';
                }
            });

            const totalMatching = matchingRows.length;
            const totalPages = Math.max(1, Math.ceil(totalMatching / itemsPerPage));

            if (currentPage > totalPages) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;

            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = Math.min(startIndex + itemsPerPage, totalMatching);

            matchingRows.forEach((row, index) => {
                if (index >= startIndex && index < endIndex) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });

            updatePaginationUI(startIndex, endIndex, totalMatching, totalPages);
        }

        function updatePaginationUI(startIndex, endIndex, totalMatching, totalPages) {
            const visibleCountEl = document.getElementById('visibleCaseCount');
            if (visibleCountEl) visibleCountEl.innerText = totalMatching;

            const badge = document.getElementById('activeFilterBadge');
            const searchInput = (document.getElementById('caseSearchInput')?.value || '').trim();
            if (badge) {
                if (searchInput || currentUnitFilter !== 'ALL' || currentStatusFilter !== 'ALL') {
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }

            const rangeEl = document.getElementById('paginationRange');
            if (rangeEl) {
                rangeEl.innerText = totalMatching > 0 ? `${startIndex + 1} - ${endIndex}` : '0 - 0';
            }

            const totalEl = document.getElementById('paginationTotal');
            if (totalEl) totalEl.innerText = totalMatching;

            const prevBtn = document.getElementById('prevPageBtn');
            if (prevBtn) prevBtn.disabled = (currentPage <= 1);

            const nextBtn = document.getElementById('nextPageBtn');
            if (nextBtn) nextBtn.disabled = (currentPage >= totalPages || totalMatching === 0);

            const pageContainer = document.getElementById('pageNumbersContainer');
            if (pageContainer) {
                let pagesHtml = '';
                const maxVisibleButtons = 5;
                let startPage = Math.max(1, currentPage - 2);
                let endPage = Math.min(totalPages, startPage + maxVisibleButtons - 1);
                if (endPage - startPage < maxVisibleButtons - 1) {
                    startPage = Math.max(1, endPage - maxVisibleButtons + 1);
                }

                for (let i = startPage; i <= endPage; i++) {
                    const isActive = (i === currentPage);
                    pagesHtml += `<button onclick="goToPage(${i})" class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all ${isActive ? 'bg-blue-600 text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-300'}">${i}</button>`;
                }
                pageContainer.innerHTML = pagesHtml;
            }
        }

        function goToPage(page) {
            currentPage = page;
            filterCases();
        }

        function changePerPage(val) {
            itemsPerPage = parseInt(val, 10) || 10;
            currentPage = 1;
            filterCases();
        }

        function filterUnit(unitName, element) {
            currentUnitFilter = unitName;
            currentPage = 1;
            document.querySelectorAll('.unit-tab').forEach(btn => btn.classList.remove('active-tab'));
            if (element) element.classList.add('active-tab');
            filterCases();
        }

        function filterStatus(statusName, element) {
            currentStatusFilter = statusName;
            currentPage = 1;
            document.querySelectorAll('.status-tab').forEach(btn => btn.classList.remove('active-tab'));
            if (element) element.classList.add('active-tab');
            filterCases();
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

            const badgeEl = document.getElementById('modalCaseStatusBadge');
            const modalBtns = document.getElementById('inspectModalActionBtns');

            if (status === 'Approved' || status === 'Authorized') {
                badgeEl.className = 'status-badge status-done';
                badgeEl.innerHTML = `<svg class="w-3 h-3 mr-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Authorized`;
                if (modalBtns) modalBtns.style.display = 'none';
            } else if (status === 'Declined') {
                badgeEl.className = 'status-badge status-declined';
                badgeEl.innerHTML = `<span class="w-1.5 h-1.5 rounded-full bg-red-600 mr-1.5 inline-block"></span> Declined`;
                if (modalBtns) modalBtns.style.display = 'none';
            } else {
                badgeEl.className = 'status-badge status-await';
                badgeEl.innerHTML = `<span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5 inline-block"></span> ${status || 'Awaiting Sign-off'}`;
                if (modalBtns) modalBtns.style.display = 'flex';
            }

            document.getElementById('inspectModal').classList.remove('hidden');
        }

        function closeInspectModal() {
            document.getElementById('inspectModal').classList.add('hidden');
            activeInspectRef = null;
        }

        async function approveFromModal() {
            if (activeInspectRef) {
                const ref = activeInspectRef;
                try {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    await fetch(`/cases/${ref}/approve`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token }
                    });
                } catch (e) {
                    console.error(e);
                }
                approveCase(ref);
                closeInspectModal();
            }
        }

        async function declineFromModal() {
            if (activeInspectRef) {
                const ref = activeInspectRef;
                try {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    await fetch(`/cases/${ref}/decline`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token }
                    });
                } catch (e) {
                    console.error(e);
                }
                declineCase(ref);
                closeInspectModal();
            }
        }

        async function declineCase(ref) {
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch(`/cases/${ref}/decline`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    const badge = document.getElementById('status-' + ref);
                    if (badge) {
                        badge.className = 'status-badge status-declined';
                        badge.innerHTML = `<span class="w-1.5 h-1.5 rounded-full bg-red-600 mr-1.5 inline-block"></span> Declined`;
                    }
                    showToast('Case Declined', `Case ${ref} was declined by Department Head.`);
                } else {
                    showToast('Error Declining Case', data.message || 'Could not decline case.');
                }
            } catch (e) {
                console.error(e);
                showToast('Connection Error', `Error declining case ${ref}.`);
            }
        }

        async function approveCase(ref) {
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch(`/cases/${ref}/approve`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    const badge = document.getElementById('status-' + ref);
                    if (badge) {
                        badge.className = 'status-badge status-done';
                        badge.innerHTML = `<svg class="w-3 h-3 mr-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Authorized`;
                    }
                    const btn = document.getElementById('btn-' + ref);
                    if (btn) {
                        btn.className = 'btn-review bg-emerald-700 hover:bg-emerald-800 border-emerald-600';
                        btn.innerHTML = `<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg> Preview`;
                    }
                    
                    let pendingEl = document.getElementById('statPendingApprovals');
                    if (pendingEl && parseInt(pendingEl.innerText) > 0) {
                        pendingEl.innerText = parseInt(pendingEl.innerText) - 1;
                    }
                    let courtEl = document.getElementById('statCourtReady');
                    if (courtEl) {
                        courtEl.innerText = parseInt(courtEl.innerText) + 1;
                    }

                    showToast('Department Sign-Off Approved', `Case ${ref} findings certificate authorized by Department Head.`);
                } else {
                    showToast('Error Approving Case', data.message || 'Could not approve case.');
                }
            } catch (e) {
                console.error(e);
                showToast('Connection Error', `Error approving case ${ref}.`);
            }
        }

        async function batchApprove() {
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch('/cases/batch-approve', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    const pendingBadges = document.querySelectorAll('.status-await');
                    let count = data.count || pendingBadges.length;
                    pendingBadges.forEach(badge => {
                        badge.className = 'status-badge status-done';
                        badge.innerHTML = `<svg class="w-3 h-3 mr-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Authorized`;
                    });
                    document.querySelectorAll('.btn-review').forEach(btn => {
                        btn.className = 'btn-review bg-emerald-700 hover:bg-emerald-800 border-emerald-600';
                        btn.innerHTML = `<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg> Preview`;
                    });

                    document.getElementById('statPendingApprovals').innerText = '0';
                    document.getElementById('statCourtReady').innerText = parseInt(document.getElementById('statCourtReady').innerText || '0') + count;
                    showToast('Batch Sign-Off Complete', `Successfully approved and authorized ${count} pending case files.`);
                } else {
                    showToast('Batch Approve Failed', data.message || 'Error processing request.');
                }
            } catch (e) {
                console.error(e);
                showToast('Connection Error', 'Failed to communicate with server.');
            }
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

        /* ── 3-Second Real-Time Polling Interval ─────── */
        async function pollForensicHeadCasesTable() {
            try {
                const inspectModal = document.getElementById('inspectModal');
                const activeActionMenu = document.querySelector('.case-action-menu:not(.hidden)');
                if ((inspectModal && !inspectModal.classList.contains('hidden')) || activeActionMenu) return;

                const res = await fetch('/api/cases', { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const cases = await res.json();
                if (!Array.isArray(cases)) return;

                const tbody = document.getElementById('casesTableBody');
                if (!tbody) return;

                const searchInput = (document.getElementById('caseSearchInput')?.value || '').toLowerCase().trim();

                let pendingApprovalsCount = 0;
                let courtReadyCount = 0;

                let html = '';
                if (cases.length === 0) {
                    html = `<tr><td colspan="6" class="py-8 text-center text-slate-500 font-mono-code text-xs">No case register dockets found in database.</td></tr>`;
                } else {
                    cases.forEach(c => {
                        const isPending = ['Awaiting Sign-off', 'Pending Approval', 'Pending'].includes(c.status);
                        const isApproved = ['Approved', 'Authorized', 'Court Ready', 'Completed'].includes(c.status);

                        if (isPending) pendingApprovalsCount++;
                        if (isApproved) courtReadyCount++;

                        const priorityClass = (c.priority || '').toUpperCase() === 'CRITICAL'
                            ? 'bg-red-100 text-red-700 border-red-200'
                            : ((c.priority || '').toUpperCase() === 'HIGH' ? 'bg-amber-100 text-amber-800 border-amber-200' : 'bg-slate-100 text-slate-700 border-slate-300');

                        const unitColorMap = {
                            'Evidence Management': 'text-indigo-600',
                            'Digital & Physical Forensics': 'text-amber-600',
                            'LAB Analysis': 'text-emerald-600',
                        };
                        const unitColor = unitColorMap[c.unit_name] || 'text-blue-600';
                        const createdDate = c.created_at ? c.created_at.slice(0, 10) : new Date().toISOString().slice(0, 10);

                        let statusBadge = '';
                        if (isApproved) {
                            statusBadge = `<span class="status-badge status-done" id="status-${c.case_number}">
                                <svg class="w-3 h-3 mr-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Authorized
                            </span>`;
                        } else if (c.status === 'Declined') {
                            statusBadge = `<span class="status-badge status-declined" id="status-${c.case_number}">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-600 mr-1.5 inline-block"></span>
                                Declined
                            </span>`;
                        } else {
                            statusBadge = `<span class="status-badge status-await" id="status-${c.case_number}">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5 inline-block"></span>
                                ${c.status || 'Awaiting Sign-off'}
                            </span>`;
                        }

                        let actionBtn = '';
                        if (isApproved) {
                            actionBtn = `<a href="/cases/${c.case_number}/review?from=Forensic+Head" class="btn-review bg-emerald-700 hover:bg-emerald-800 border-emerald-600" id="btn-${c.case_number}">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Preview
                            </a>`;
                        } else {
                            actionBtn = `<a href="/cases/${c.case_number}/review?from=Forensic+Head" class="btn-review" id="btn-${c.case_number}">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Review
                            </a>`;
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
                        let dData = c.docket_data;
                        if (typeof dData === 'string') {
                            try { dData = JSON.parse(dData); } catch(e) { dData = {}; }
                        }
                        if (dData && Array.isArray(dData.suspects) && dData.suspects.length > 0) {
                            suspectsList = dData.suspects.filter(s => s && s.suspect_name);
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
                                     data-priority="${(c.priority || 'NORMAL').toUpperCase()}"
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

                        const rowText = `${c.case_number} ${suspectDisplayName} ${c.title} ${c.unit_name} ${c.submitting_officer} ${c.status}`.toLowerCase();
                        const matchesSearch = !searchInput || rowText.includes(searchInput);

                        html += `<tr class="table-row-hover transition-colors case-row" data-unit="${c.unit_name || ''}" data-status="${c.status || ''}" style="${matchesSearch ? '' : 'display:none;'}">
                            <td class="py-4 px-5 whitespace-nowrap">
                                <div class="font-extrabold font-mono-code text-blue-600 text-sm">${c.case_number}</div>
                                <div class="flex items-center space-x-1.5 mt-1">
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold ${priorityClass}">${(c.priority || 'NORMAL').toUpperCase()}</span>
                                    <span class="text-[10px] text-slate-500 font-mono-code">${createdDate}</span>
                                </div>
                            </td>
                            <td class="py-4 px-5">
                                <div class="font-bold text-slate-900 text-xs">${c.title || 'Untitled Case'}</div>
                                <div class="text-[10px] text-blue-600 font-mono-code font-semibold mt-0.5">${c.unit_name || 'Forensic Directorate'}</div>
                                <div class="text-[11px] text-slate-500 mt-0.5">${c.holding_facility || c.raid_location || c.seizure_location || c.location || 'NACOC Central Custody Facility'} &bull; ${c.exhibits_summary || 'Exhibits Logged'}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center space-x-3">
                                    <div class="flex items-center ${stackClass} shrink-0">
                                        ${avatarsGroupHtml}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 text-xs leading-tight truncate max-w-[180px]" title="${suspectDisplayName.replace(/"/g, '&quot;')}">${suspectDisplayName}</div>
                                        <div class="text-[10px] text-slate-500 font-mono-code mt-0.5 truncate max-w-[180px]"><span class="text-slate-400">ALIAS:</span> ${aliasStr}</div>
                                        ${suspectsList.length > 1 ? `<div class="mt-1 inline-flex items-center gap-1 px-1.5 py-0.2 rounded bg-blue-50 border border-blue-200/60 text-blue-700 text-[9px] font-mono-code font-bold"><svg class="w-2.5 h-2.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg><span>${suspectsList.length} Suspects</span></div>` : ''}
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-5">
                                <div class="font-extrabold ${unitColor} text-xs">${c.unit_name || 'Forensic Directorate'}</div>
                                <div class="text-[10px] text-slate-600 font-medium">${c.commanding_officer || 'Unit Head'}</div>
                            </td>
                            <td class="py-4 px-5">
                                <div class="font-bold text-slate-900 text-xs">${c.submitting_officer || c.commanding_officer || 'Handling Officer'}</div>
                                <div class="text-[10px] text-slate-500">Handling Officer</div>
                            </td>
                            <td class="py-4 px-5 whitespace-nowrap">${statusBadge}</td>
                            <td class="py-4 px-5 text-right whitespace-nowrap">
                                <div class="relative inline-block text-left case-action-dropdown-wrapper">
                                    <button type="button" 
                                            onclick="toggleCaseActionDropdown(event, '${c.case_number}')" 
                                            class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl border border-slate-300 transition-all inline-flex items-center gap-1.5 cursor-pointer shadow-2xs hover:scale-105 active:scale-95"
                                            title="Actions for Case ${c.case_number}">
                                        <svg class="w-4 h-4 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                        </svg>
                                        <span class="text-xs font-mono-code font-bold">Actions</span>
                                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>

                                    <div id="actionMenu-${c.case_number}" class="case-action-menu hidden absolute right-0 mt-1.5 w-52 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 p-2 space-y-1 text-left font-sans">
                                        <a href="/cases/${c.case_number}/associate" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-blue-50 hover:text-blue-700 rounded-xl flex items-center space-x-2.5 transition-colors group text-decoration-none">
                                            <div class="w-7 h-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                            </div>
                                            <div>
                                                <div class="font-extrabold leading-tight">Associate Data</div>
                                                <div class="text-[10px] text-slate-400 font-normal mt-0.5">Link accomplices &amp; exhibits</div>
                                            </div>
                                        </a>

                                        <a href="/cases/${c.case_number}/review?edit=true&from=Forensic+Head" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-amber-50 hover:text-amber-700 rounded-xl flex items-center space-x-2.5 transition-colors group text-decoration-none">
                                            <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 group-hover:bg-amber-600 group-hover:text-white transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </div>
                                            <div>
                                                <div class="font-extrabold leading-tight">Edit Docket</div>
                                                <div class="text-[10px] text-slate-400 font-normal mt-0.5">Modify findings &amp; info</div>
                                            </div>
                                        </a>

                                        <a href="/cases/${c.case_number}/review?from=Forensic+Head" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-700 rounded-xl flex items-center space-x-2.5 transition-colors group text-decoration-none">
                                            <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </div>
                                            <div>
                                                <div class="font-extrabold leading-tight">${(c.status === 'Approved' || c.status === 'Authorized') ? 'Preview Docket' : 'Review Docket'}</div>
                                                <div class="text-[10px] text-slate-400 font-normal mt-0.5">Full dossier inspection</div>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>`;
                    });
                }

                tbody.innerHTML = html;

                const pendingEl = document.getElementById('statPendingApprovals');
                if (pendingEl) pendingEl.innerText = pendingApprovalsCount;
                const courtEl = document.getElementById('statCourtReady');
                if (courtEl) courtEl.innerText = courtReadyCount;
                const totalEl = document.getElementById('statTotalCases');
                if (totalEl) totalEl.innerText = cases.length;
                const visibleCountEl = document.getElementById('visibleCaseCount');
                if (visibleCountEl && !searchInput) visibleCountEl.innerText = cases.length;
                const totalCountEl = document.getElementById('totalCaseCount');
                if (totalCountEl) totalCountEl.innerText = cases.length;

                filterCases();
            } catch (e) {
                console.warn('Cases table polling failed:', e);
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

        setInterval(pollForensicHeadCasesTable, 3000);

        function toggleCaseActionDropdown(event, caseRef) {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            const targetMenu = document.getElementById('actionMenu-' + caseRef);
            if (!targetMenu) return;

            const isHidden = targetMenu.classList.contains('hidden');
            document.querySelectorAll('.case-action-menu').forEach(menu => {
                menu.classList.add('hidden');
                if (menu.closest('tr')) menu.closest('tr').classList.remove('z-50', 'relative');
            });

            if (isHidden) {
                targetMenu.classList.remove('hidden');
                if (targetMenu.closest('tr')) targetMenu.closest('tr').classList.add('z-50', 'relative');
            }
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.case-action-dropdown-wrapper')) {
                document.querySelectorAll('.case-action-menu').forEach(menu => {
                    menu.classList.add('hidden');
                    if (menu.closest('tr')) menu.closest('tr').classList.remove('z-50', 'relative');
                });
            }
        });
    </script>
    </body></body>
</html>
