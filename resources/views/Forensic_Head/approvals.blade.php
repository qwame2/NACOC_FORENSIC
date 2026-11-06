<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Head Approvals &amp; Personnel Control | Forensic Directorate</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="NACOC Forensic Directorate Command Portal — Dynamic User Account Approvals, Activation, and Deactivation.">

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
            width: 6px;
            height: 6px;
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
            background-color: #F8FAFC;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 11px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
        }
        .status-pending {
            background-color: #FEF3C7;
            color: #D97706;
            border: 1px solid #FCD34D;
        }
        .status-active {
            background-color: #D1FAE5;
            color: #047857;
            border: 1px solid #6EE7B7;
        }
        .status-deactivated {
            background-color: #F1F5F9;
            color: #475569;
            border: 1px solid #CBD5E1;
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
        .toast-notification {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .toast-notification.show {
            transform: translateY(0);
            opacity: 1;
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
                        Head Account Control
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
                            placeholder="Search name, Service ID, unit, phone..." 
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
                            $pendingHandoversNotif = (int)(\App\Models\CaseHandover::whereNull('acknowledged_at')->count());
                            $totalAlertsCount = ($pendingCasesNotif > 0 ? 1 : 0) + ($pendingUsersNotif > 0 ? 1 : 0) + ($pendingHandoversNotif > 0 ? 1 : 0);
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
                                
                                <!-- ALERT 1: CASE APPROVALS -->
                                <a href="{{ url('/Forensic_Head/cases') }}" class="p-3.5 flex items-start space-x-3 hover:bg-slate-50 transition-colors text-decoration-none block">
                                    <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-600 border border-amber-500/20 flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-0.5">
                                            <span class="text-[10px] font-extrabold font-mono-code text-amber-700 uppercase">Case Approvals</span>
                                            <span class="text-[9px] font-bold text-slate-400 font-mono-code">STATUS</span>
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
                                            <span class="text-[9px] font-bold text-slate-400 font-mono-code">STATUS</span>
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



                <!-- KPI STAT METRICS CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- CARD 1 -->
                    <div class="glass-panel-white p-5 rounded-2xl transition-all shadow-sm">
                        <div class="flex items-center justify-between text-slate-500 mb-2">
                            <span class="text-xs font-bold text-slate-600 uppercase tracking-wider font-mono-code">Pending Approvals</span>
                            <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-amber-600 font-mono-code" id="statPendingApprovals">{{ $pendingCount ?? 0 }}</div>
                        <div class="flex items-center text-[11px] text-amber-700 mt-2 font-semibold">
                            Awaiting Directorate Approval
                        </div>
                    </div>

                    <!-- CARD 2 -->
                    <div class="glass-panel-white p-5 rounded-2xl transition-all shadow-sm">
                        <div class="flex items-center justify-between text-slate-500 mb-2">
                            <span class="text-xs font-bold text-slate-600 uppercase tracking-wider font-mono-code">Active Authorized Accounts</span>
                            <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-emerald-600 font-mono-code" id="statActiveCount">{{ $activeCount ?? 0 }}</div>
                        <div class="flex items-center text-[11px] text-emerald-700 mt-2 font-semibold">
                            Full System Access Enabled
                        </div>
                    </div>

                    <!-- CARD 3 -->
                    <div class="glass-panel-white p-5 rounded-2xl transition-all shadow-sm">
                        <div class="flex items-center justify-between text-slate-500 mb-2">
                            <span class="text-xs font-bold text-slate-600 uppercase tracking-wider font-mono-code">Registered Unit Heads</span>
                            <div class="w-9 h-9 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-indigo-600 font-mono-code" id="statHeadsCount">{{ $headsCount ?? 0 }} Heads</div>
                        <div class="flex items-center text-[11px] text-indigo-700 mt-2 font-semibold">
                            Unit Commanders in Database
                        </div>
                    </div>

                    <!-- CARD 4 -->
                    <div class="glass-panel-white p-5 rounded-2xl transition-all shadow-sm">
                        <div class="flex items-center justify-between text-slate-500 mb-2">
                            <span class="text-xs font-bold text-slate-600 uppercase tracking-wider font-mono-code">Total Database Users</span>
                            <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-slate-900 font-mono-code" id="statTotalCount">{{ $totalCount ?? 0 }} Users</div>
                        <div class="flex items-center text-[11px] text-slate-500 mt-2 font-semibold">
                            Real Database Records
                        </div>
                    </div>
                </div>

                <!-- ACCOUNT APPROVALS QUEUE MAIN PANEL (GRAY STYLED ROSTER CONTAINER) -->
                <div class="bg-slate-100 rounded-2xl border border-slate-300 shadow-sm overflow-hidden space-y-0">
                    
                    <!-- HEADER & TOOLBAR -->
                    <div class="p-6 bg-slate-200/80 border-b border-slate-300 space-y-4">
                        
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <span class="text-[10px] font-bold font-mono-code text-blue-700 uppercase tracking-wider">DATABASE PERSONNEL ROSTER</span>
                                <h3 class="font-black text-slate-900 text-lg">System Users &amp; Unit Heads Control Portal</h3>
                                <p class="text-xs text-slate-600 mt-0.5 font-medium">Click Inspect to view credentials, Activate to enable access, or Deactivate to suspend user accounts</p>
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
                                    id="userSearchInput" 
                                    onkeyup="filterUsers()"
                                    placeholder="Search name, Service ID, unit, phone..." 
                                    class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 font-mono-code focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all shadow-2xs"
                                >
                            </div>
                        </div>

                        <!-- FILTERS ROW WITH "ALL USERS" TAB -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 border-t border-slate-300/80">
                            <!-- STATUS & ROLE FILTER TABS -->
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-[11px] font-bold text-slate-700 mr-1 uppercase font-mono-code">Filter Roster:</span>
                                <button onclick="filterStatus('ALL_USERS', this)" class="filter-tab-btn active-tab status-tab">All Users ({{ $totalCount ?? 0 }})</button>
                                <button onclick="filterStatus('HEADS_ONLY', this)" class="filter-tab-btn status-tab">Unit Heads Only ({{ $headsCount ?? 0 }})</button>
                                <button onclick="filterStatus('PENDING', this)" class="filter-tab-btn status-tab">Pending Approval ({{ $pendingCount ?? 0 }})</button>
                                <button onclick="filterStatus('ACTIVE', this)" class="filter-tab-btn status-tab">Active ({{ $activeCount ?? 0 }})</button>
                            </div>

                            <!-- UNIT FILTER TABS -->
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-[11px] font-bold text-slate-700 mr-1 uppercase font-mono-code">Unit:</span>
                                <button onclick="filterUnit('ALL', this)" class="filter-tab-btn active-tab unit-tab">All Units</button>
                                <button onclick="filterUnit('Evidence Management', this)" class="filter-tab-btn unit-tab">Evidence Mgmt</button>
                                <button onclick="filterUnit('Digital & Physical Forensics', this)" class="filter-tab-btn unit-tab">Digital &amp; Physical</button>
                                <button onclick="filterUnit('Crime Scene Support', this)" class="filter-tab-btn unit-tab">Crime Scene</button>
                                <button onclick="filterUnit('LAB Analysis', this)" class="filter-tab-btn unit-tab">LAB Analysis</button>
                            </div>
                        </div>
                    </div>

                    <!-- SEARCH COUNTER BANNER -->
                    <div class="px-6 py-2.5 bg-slate-200/50 border-b border-slate-300 flex items-center justify-between text-xs text-slate-700 font-mono-code">
                        <div>
                            Displaying <span id="visibleUserCount" class="font-extrabold text-slate-900">{{ count($users ?? []) }}</span> of <span id="totalUserCount" class="font-extrabold text-slate-900">{{ count($users ?? []) }}</span> database user records
                        </div>
                        <div id="activeFilterBadge" class="hidden text-[10px] text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded font-bold">
                            Filtered View Active
                        </div>
                    </div>

                    <!-- TABLE CONTENT FETCHED DYNAMICALLY FROM DATABASE -->
                    <div id="approvalsTableContainer">
                        @include('Forensic_Head.partials.approvals_table')
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- USER CREDENTIAL INSPECTION MODAL -->
    <div id="inspectUserModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-300 shadow-2xl max-w-xl w-full p-6 space-y-5 relative">
            <div class="flex items-start justify-between border-b border-slate-200 pb-4">
                <div>
                    <div class="flex items-center space-x-2 mb-1">
                        <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-mono-code font-bold text-[10px] uppercase">DIRECTORATE USER INSPECTION</span>
                        <span class="text-xs text-slate-400">&bull;</span>
                        <span class="text-xs font-mono-code font-bold text-slate-600" id="modalSvcId">NAC-8842-EM</span>
                    </div>
                    <h3 class="text-lg font-black text-slate-900" id="modalOfficerName">N/A</h3>
                </div>
                <button onclick="closeUserModal()" class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1 cursor-pointer">&times;</button>
            </div>

            <!-- INTEGRITY VERIFICATION BANNER -->
            <div class="bg-slate-900 text-white p-4 rounded-xl space-y-2 font-mono-code">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-emerald-400 font-bold flex items-center gap-1.5">
                        DATABASE RECORD: VERIFIED &amp; MATCHED
                    </span>
                    <span class="text-slate-400 text-[10px]">NACOC HR SYSTEM</span>
                </div>
                <div class="text-[11px] text-slate-300 truncate">
                    SECURITY CLEARANCE: <span class="text-amber-400 font-bold" id="modalClearance">Level 4 Security Clearance</span>
                </div>
            </div>

            <!-- GRID METADATA -->
            <div class="grid grid-cols-2 gap-4 text-xs">
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                    <div class="text-[10px] text-slate-500 font-bold font-mono-code uppercase">Account Role</div>
                    <div class="font-bold text-slate-900 mt-0.5" id="modalRole">UNIT HEAD (COMMAND)</div>
                </div>
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                    <div class="text-[10px] text-slate-500 font-bold font-mono-code uppercase">Assigned Unit</div>
                    <div class="font-bold text-indigo-700 mt-0.5" id="modalUnit">Evidence Management</div>
                </div>
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                    <div class="text-[10px] text-slate-500 font-bold font-mono-code uppercase">Official Phone Number</div>
                    <div class="font-bold text-slate-900 mt-0.5 font-mono-code" id="modalPhone">0244112233</div>
                </div>
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                    <div class="text-[10px] text-slate-500 font-bold font-mono-code uppercase">Username &amp; Reg Time</div>
                    <div class="font-bold text-slate-900 mt-0.5 font-mono-code" id="modalUsername">@username</div>
                </div>
            </div>

            <!-- MODAL ACTIONS -->
            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-200" id="modalActionsContainer">
                <button onclick="closeUserModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold rounded-xl border border-slate-300 cursor-pointer">Close</button>
                <button id="modalToggleBtn" onclick="toggleActivationFromModal()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md border-none cursor-pointer">Activate Account</button>
            </div>
        </div>
    </div>

    <!-- TOAST NOTIFICATION CONTAINER -->
    <div id="toastNotification" class="toast-notification bg-slate-900 text-white px-5 py-4 rounded-2xl shadow-2xl border border-slate-800 flex items-center space-x-3 font-mono-code text-xs">
        <div id="toastIcon" class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        </div>
        <div>
            <div class="font-black text-white text-sm" id="toastTitle">Database Record Updated</div>
            <div class="text-[11px] text-slate-400" id="toastMessage">User account has been successfully updated in database.</div>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        let currentStatusFilter = 'ALL_USERS';
        let currentUnitFilter = 'ALL';
        let activeInspectUserId = null;
        let activeInspectName = null;
        let activeInspectStatus = null;

        function updateClock() {
            const now = new Date();
            const utcString = now.toISOString().replace('T', ' ').substring(0, 19) + ' UTC';
            const clockEl = document.getElementById('liveClock');
            if (clockEl) clockEl.innerText = utcString;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Initialize Reusable AJAX Table Controller for Forensic Head Approvals
        window.approvalsAjaxTable = initAjaxTable({
            container: '#approvalsTableContainer',
            table: '#usersTable',
            columns: 6,
            rows: 6,
            onAfterUpdate: (container) => {
                const rows = container.querySelectorAll('.user-row');
                const visEl = document.getElementById('visibleUserCount');
                if (visEl) visEl.innerText = rows.length;
                
                const searchInput = (document.getElementById('userSearchInput')?.value || '').trim();
                const badge = document.getElementById('activeFilterBadge');
                if (badge) {
                    if (searchInput || currentUnitFilter !== 'ALL' || currentStatusFilter !== 'ALL_USERS') {
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                }
            }
        });

        function filterUsers() {
            const searchVal = (document.getElementById('userSearchInput')?.value || '').trim();
            if (window.approvalsAjaxTable) {
                if (window.approvalsAjaxTable.debounceTimer) clearTimeout(window.approvalsAjaxTable.debounceTimer);
                window.approvalsAjaxTable.debounceTimer = setTimeout(() => {
                    window.approvalsAjaxTable.applyFilters({
                        search: searchVal || null,
                        unit: currentUnitFilter === 'ALL' ? null : currentUnitFilter,
                        status: currentStatusFilter === 'ALL_USERS' ? null : currentStatusFilter,
                        page: null
                    });
                }, 350);
            }
        }

        function filterStatus(status, element) {
            currentStatusFilter = status;
            document.querySelectorAll('.status-tab').forEach(btn => btn.classList.remove('active-tab'));
            if (element) element.classList.add('active-tab');

            const searchVal = (document.getElementById('userSearchInput')?.value || '').trim();
            if (window.approvalsAjaxTable) {
                window.approvalsAjaxTable.applyFilters({
                    status: status === 'ALL_USERS' ? null : status,
                    unit: currentUnitFilter === 'ALL' ? null : currentUnitFilter,
                    search: searchVal || null,
                    page: null
                });
            }
        }

        function filterUnit(unitName, element) {
            currentUnitFilter = unitName;
            document.querySelectorAll('.unit-tab').forEach(btn => btn.classList.remove('active-tab'));
            if (element) element.classList.add('active-tab');

            const searchVal = (document.getElementById('userSearchInput')?.value || '').trim();
            if (window.approvalsAjaxTable) {
                window.approvalsAjaxTable.applyFilters({
                    unit: unitName === 'ALL' ? null : unitName,
                    status: currentStatusFilter === 'ALL_USERS' ? null : currentStatusFilter,
                    search: searchVal || null,
                    page: null
                });
            }
        }

        function inspectUser(userId, name, role, unit, phone, username, regTime, clearance, status) {
            activeInspectUserId = userId;
            activeInspectName = name;
            activeInspectStatus = status;

            document.getElementById('modalSvcId').innerText = 'ID: ' + userId;
            document.getElementById('modalOfficerName').innerText = name;
            document.getElementById('modalRole').innerText = role;
            document.getElementById('modalUnit').innerText = unit;
            document.getElementById('modalPhone').innerText = phone;
            document.getElementById('modalUsername').innerText = '@' + username + ' · ' + regTime;
            document.getElementById('modalClearance').innerText = clearance;

            const toggleBtn = document.getElementById('modalToggleBtn');
            if (status === 'ACTIVE') {
                toggleBtn.innerText = 'Deactivate Account';
                toggleBtn.className = 'px-4 py-2 bg-slate-700 hover:bg-slate-800 text-white text-xs font-bold rounded-xl shadow-md border-none cursor-pointer';
            } else {
                toggleBtn.innerText = 'Activate Account';
                toggleBtn.className = 'px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md border-none cursor-pointer';
            }

            document.getElementById('inspectUserModal').classList.remove('hidden');
        }

        function closeUserModal() {
            document.getElementById('inspectUserModal').classList.add('hidden');
            activeInspectUserId = null;
            activeInspectName = null;
            activeInspectStatus = null;
        }

        function toggleActivationFromModal() {
            if (activeInspectUserId && activeInspectName) {
                if (activeInspectStatus === 'ACTIVE') {
                    deactivateUser(activeInspectUserId, activeInspectName);
                } else {
                    activateUser(activeInspectUserId, activeInspectName);
                }
                closeUserModal();
            }
        }

        function activateUser(userId, officerName) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            fetch(`/Forensic_Head/users/${userId}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(res => res.json())
            .then(data => {
                const row = document.getElementById(`status-${userId}`)?.closest('tr');
                if (row) {
                    row.setAttribute('data-status', 'ACTIVE');
                }

                const badge = document.getElementById('status-' + userId);
                if (badge) {
                    badge.className = 'status-badge status-active';
                    badge.innerHTML = `<svg class="w-3 h-3 mr-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Active & Authorized`;
                }

                const actionsCell = document.getElementById('actions-' + userId);
                if (actionsCell) {
                    actionsCell.innerHTML = `
                        <button onclick="inspectUser('${userId}', '${officerName}', 'USER', 'Directorate', '', '', '', 'Level 3 Clearance', 'ACTIVE')" class="px-3 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-bold rounded-lg text-xs border border-emerald-200 transition-all cursor-pointer">Inspect</button>
                        <button onclick="deactivateUser('${userId}', '${officerName}')" class="px-3 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold rounded-lg text-xs border border-slate-300 transition-all cursor-pointer">Deactivate</button>
                    `;
                }

                let pendingEl = document.getElementById('statPendingApprovals');
                if (pendingEl && parseInt(pendingEl.innerText) > 0) {
                    const newCount = parseInt(pendingEl.innerText) - 1;
                    pendingEl.innerText = newCount;
                    const batchEl = document.getElementById('batchPendingCount');
                    if (batchEl) batchEl.innerText = newCount;
                    const sidebarBadgeEl = document.getElementById('sidebarPendingBadgeCount');
                    if (sidebarBadgeEl) sidebarBadgeEl.innerText = newCount;
                }

                let activeEl = document.getElementById('statActiveCount');
                if (activeEl) {
                    activeEl.innerText = parseInt(activeEl.innerText) + 1;
                }

                filterUsers();
                showToast('Account Approved & Activated in Database', `Full system access enabled for ${officerName}.`);
            })
            .catch(err => {
                showToast('Database Update Error', 'Could not update user account in database.', true);
            });
        }

        function deactivateUser(userId, officerName) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            fetch(`/Forensic_Head/users/${userId}/deactivate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(res => res.json())
            .then(data => {
                const row = document.getElementById(`status-${userId}`)?.closest('tr');
                if (row) {
                    row.setAttribute('data-status', 'PENDING');
                }

                const badge = document.getElementById('status-' + userId);
                if (badge) {
                    badge.className = 'status-badge status-pending';
                    badge.innerHTML = `Pending Approval`;
                }

                const actionsCell = document.getElementById('actions-' + userId);
                if (actionsCell) {
                    actionsCell.innerHTML = `
                        <button onclick="inspectUser('${userId}', '${officerName}', 'USER', 'Directorate', '', '', '', 'Access Suspended', 'PENDING')" class="px-3 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-bold rounded-lg text-xs border border-emerald-200 transition-all cursor-pointer">Inspect</button>
                        <button onclick="activateUser('${userId}', '${officerName}')" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs border-none transition-all shadow-xs cursor-pointer">Activate</button>
                    `;
                }

                let activeEl = document.getElementById('statActiveCount');
                if (activeEl && parseInt(activeEl.innerText) > 0) {
                    activeEl.innerText = parseInt(activeEl.innerText) - 1;
                }

                let pendingEl = document.getElementById('statPendingApprovals');
                if (pendingEl) {
                    const newCount = parseInt(pendingEl.innerText) + 1;
                    pendingEl.innerText = newCount;
                    const batchEl = document.getElementById('batchPendingCount');
                    if (batchEl) batchEl.innerText = newCount;
                    const sidebarBadgeEl = document.getElementById('sidebarPendingBadgeCount');
                    if (sidebarBadgeEl) sidebarBadgeEl.innerText = newCount;
                }

                filterUsers();
                showToast('Account Deactivated in Database', `System access suspended for ${officerName}.`, true);
            })
            .catch(err => {
                showToast('Database Update Error', 'Could not deactivate user account in database.', true);
            });
        }

        function batchActivatePending() {
            const pendingRows = document.querySelectorAll('.user-row[data-status="PENDING"]');
            let count = 0;

            pendingRows.forEach(row => {
                const badge = row.querySelector('.status-badge');
                if (badge && badge.id) {
                    const userId = badge.id.replace('status-', '');
                    const name = row.querySelector('.font-black')?.innerText || 'User';
                    activateUser(userId, name);
                    count++;
                }
            });

            if (count > 0) {
                showToast('Batch Accounts Approved', `All ${count} Pending Officer accounts have been approved.`);
            } else {
                showToast('No Pending Accounts', 'There are no pending officer accounts awaiting approval.');
            }
        }

        function exportPersonnelRegister() {
            showToast('Exporting Database Roster', 'Generating complete Directorate System Personnel PDF report...');
        }

        function showToast(title, message, isWarning = false) {
            const toast = document.getElementById('toastNotification');
            const toastTitle = document.getElementById('toastTitle');
            const toastMsg = document.getElementById('toastMessage');
            const toastIcon = document.getElementById('toastIcon');

            toastTitle.innerText = title;
            toastMsg.innerText = message;

            if (isWarning) {
                toastIcon.className = 'w-8 h-8 rounded-xl bg-slate-700 text-slate-200 flex items-center justify-center font-bold';
                toastIcon.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>`;
            } else {
                toastIcon.className = 'w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold';
                toastIcon.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>`;
            }

            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3500);
        }

        /* ── 3-Second Real-Time Polling Interval ─────── */
        async function pollUsersTable() {
            try {
                const modal = document.getElementById('inspectUserModal');
                if (modal && !modal.classList.contains('hidden')) {
                    return;
                }

                const res = await fetch('/Forensic_Head/approvals', {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) return;
                const data = await res.json();
                if (!data || !Array.isArray(data.users)) return;

                const tbody = document.getElementById('usersTableBody');
                if (!tbody) return;

                const searchInput = (document.getElementById('userSearchInput')?.value || '').toLowerCase().trim();

                const unitColors = {
                    'Evidence Management': 'bg-indigo-600',
                    'Digital & Physical Forensics': 'bg-amber-600',
                    'Crime Scene Support': 'bg-emerald-600',
                    'LAB Analysis': 'bg-emerald-600',
                    'Forensic Directorate': 'bg-slate-900',
                };

                let html = '';
                data.users.forEach(u => {
                    const nameParts = (u.fullname || 'Officer').trim().split(/\s+/);
                    const initials = nameParts.length >= 2
                        ? (nameParts[0][0] + nameParts[nameParts.length - 1][0]).toUpperCase()
                        : (u.fullname || 'OF').slice(0, 2).toUpperCase();

                    const avatarBg = unitColors[u.unit] || 'bg-emerald-600';
                    const isForensicHeadUser = u.is_head && (
                        u.unit === 'Forensic Directorate' ||
                        u.unit === 'Forensic Head' ||
                        u.unit === 'Directorate Head' ||
                        (u.unit || '').includes('Forensic Directorate')
                    );

                    let roleBadge = '';
                    if (isForensicHeadUser) {
                        roleBadge = `<span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold font-mono-code bg-indigo-100 text-indigo-800 border border-indigo-200 flex items-center gap-1.5 w-fit">
                            <svg class="w-3 h-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            DIRECTORATE HEAD
                        </span>`;
                    } else if (u.is_head) {
                        roleBadge = `<span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold font-mono-code bg-indigo-100 text-indigo-800 border border-indigo-200 flex items-center gap-1.5 w-fit">
                            <svg class="w-3 h-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            UNIT HEAD (COMMAND)
                        </span>`;
                    } else {
                        roleBadge = `<span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold font-mono-code bg-slate-100 text-slate-700 border border-slate-300 flex items-center gap-1.5 w-fit">
                            <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            STAFF OFFICER
                        </span>`;
                    }

                    let statusBadge = u.is_approved
                        ? `<span class="status-badge status-active" id="status-${u.id}"><svg class="w-3 h-3 mr-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Active &amp; Authorized</span>`
                        : `<span class="status-badge status-pending" id="status-${u.id}">Pending Approval</span>`;

                    let actionBtns = `<button onclick="inspectUser('${u.id}', '${(u.fullname || '').replace(/'/g, "\\'")}', '${u.is_head ? 'UNIT HEAD (COMMAND)' : 'STAFF OFFICER'}', '${(u.unit || '').replace(/'/g, "\\'")}', '${u.phone_number || ''}', '${u.username || ''}', '${u.created_at ? u.created_at.slice(0,16) : ''}', '${u.is_head ? 'Level 4 Security Clearance' : 'Level 2 Security Clearance'}', '${u.is_approved ? 'ACTIVE' : 'PENDING'}')" class="px-3 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-bold rounded-lg text-xs border border-emerald-200 transition-all cursor-pointer">Inspect</button>`;

                    if (!isForensicHeadUser) {
                        if (u.is_approved) {
                            actionBtns += ` <button onclick="deactivateUser('${u.id}', '${(u.fullname || '').replace(/'/g, "\\'")}')" class="px-3 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold rounded-lg text-xs border border-slate-300 transition-all cursor-pointer">Deactivate</button>`;
                        } else {
                            actionBtns += ` <button onclick="activateUser('${u.id}', '${(u.fullname || '').replace(/'/g, "\\'")}')" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs border-none transition-all shadow-xs cursor-pointer">Activate</button>`;
                        }
                    }

                    const uStatus = u.is_approved ? 'ACTIVE' : 'PENDING';
                    const uRole = u.is_head ? 'HEAD' : 'STAFF';
                    const matchesSearch = !searchInput || `${u.fullname} ${u.service_id} ${u.unit} ${u.username} ${u.phone_number}`.toLowerCase().includes(searchInput);
                    const matchesUnit = currentUnitFilter === 'ALL' || u.unit === currentUnitFilter;
                    
                    let matchesStatus = false;
                    if (currentStatusFilter === 'ALL_USERS') matchesStatus = true;
                    else if (currentStatusFilter === 'HEADS_ONLY') matchesStatus = (uRole === 'HEAD');
                    else matchesStatus = (uStatus === currentStatusFilter);

                    const isVisible = matchesSearch && matchesUnit && matchesStatus;

                    html += `<tr class="table-row-hover transition-colors user-row" data-status="${uStatus}" data-role="${uRole}" data-unit="${u.unit || ''}" style="${isVisible ? '' : 'display:none;'}">
                        <td class="py-4 px-5 whitespace-nowrap">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-xl ${avatarBg} text-white font-extrabold flex items-center justify-center text-xs shadow-sm">
                                    ${initials}
                                </div>
                                <div>
                                    <div class="font-black text-slate-900 text-sm">${u.fullname || 'Officer'}</div>
                                    <div class="font-mono-code text-[11px] text-blue-600 font-bold">SVC ID: ${u.service_id || 'N/A'}</div>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-5 whitespace-nowrap">${roleBadge}</td>
                        <td class="py-4 px-5">
                            <div class="font-extrabold text-slate-900 text-xs">${u.unit || 'Forensic Directorate'}</div>
                            <div class="text-[10px] text-slate-500 font-medium">NACOC Directorate Unit</div>
                        </td>
                        <td class="py-4 px-5 font-mono-code">
                            <div class="font-bold text-slate-900 text-xs">${u.phone_number || ''}</div>
                            <div class="text-[10px] text-slate-500">@ ${u.username || ''}</div>
                        </td>
                        <td class="py-4 px-5 whitespace-nowrap font-mono-code text-[11px] text-slate-600">
                            ${u.created_at ? u.created_at.slice(0, 16).replace('T', ' ') : '2026-09-08 00:00'}
                        </td>
                        <td class="py-4 px-5 whitespace-nowrap">${statusBadge}</td>
                        <td class="py-4 px-5 text-right whitespace-nowrap space-x-1.5" id="actions-${u.id}">${actionBtns}</td>
                    </tr>`;
                });

                tbody.innerHTML = html;

                // Update KPI metrics
                if (data.pendingCount !== undefined) {
                    const el = document.getElementById('statPendingApprovals');
                    if (el) el.innerText = data.pendingCount;
                    const batchBtnEl = document.getElementById('batchPendingCount');
                    if (batchBtnEl) batchBtnEl.innerText = data.pendingCount;
                }
                if (data.activeCount !== undefined) {
                    const el = document.getElementById('statActiveCount');
                    if (el) el.innerText = data.activeCount;
                }
                if (data.headsCount !== undefined) {
                    const el = document.getElementById('statHeadsCount');
                    if (el) el.innerText = data.headsCount + ' Heads';
                }
                if (data.totalCount !== undefined) {
                    const el = document.getElementById('statTotalCount');
                    if (el) el.innerText = data.totalCount + ' Users';
                    const totUserCountEl = document.getElementById('totalUserCount');
                    if (totUserCountEl) totUserCountEl.innerText = data.totalCount;
                }
            } catch (e) {
                // Background polling failure handled silently
            }
        }
        /* ── Notifications & Global Search ──────────── */
        function toggleNotificationDropdown() {
            const dropdown = document.getElementById('notificationDropdown');
            if (dropdown) dropdown.classList.toggle('hidden');
        }

        function handleGlobalSearch(val) {
            const input = document.getElementById('userSearchInput');
            if (input) {
                input.value = val;
                filterUsers();
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

        setInterval(pollUsersTable, 3000);
    </script>
<script src="{{ asset('js/ajax-table.js') }}"></script>
</body>
</html>
