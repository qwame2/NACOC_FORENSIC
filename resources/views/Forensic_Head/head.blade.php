@php
    $authUser = auth()->user();
    $cases = $cases ?? collect();
    $dbForensicHead = \App\Models\User::where('is_head', true)
        ->where(function($q) {
            $q->where('unit', 'LIKE', '%Forensic%')
              ->orWhere('unit', 'LIKE', '%Directorate%');
        })->first() ?? \App\Models\User::where('is_head', true)->first();
    $officerName = $authUser->fullname ?? session('user_name') ?? ($dbForensicHead ? $dbForensicHead->fullname : 'Anita Johnson');
    $officerRole = ($authUser && $authUser->is_head) ? 'Forensic Head' : 'Commanding Officer';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forensic Department Head Command Portal | NACOC</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="NACOC Forensic Directorate Command Portal — Executive oversight for all forensic units, case approvals, evidence management, and audit compliance.">

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
        * { box-sizing: border-box; }

        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #FFFFFF;
            color: #0F172A;
            overflow: hidden;
        }

        .font-mono-code { font-family: 'JetBrains Mono', monospace; }

        /* ── Scrollbars ─────────────────────────────── */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #F1F5F9; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94A3B8; }

        /* ── Sidebar ─────────────────────────────────── */
        .sidebar {
            background: #FFFFFF;
            border-right: 1px solid #E2E8F0;
        }

        .sidebar-logo-ring {
            background: #0f172a;
            box-shadow: 0 0 20px rgba(15,23,42,0.3);
        }

        .nav-item {
            display: flex; align-items: center;
            padding: 9px 12px; border-radius: 10px;
            font-size: 12px; font-weight: 500;
            color: #475569;
            transition: all 0.2s ease;
            cursor: pointer; text-decoration: none;
        }
        .nav-item:hover { background: #F1F5F9; color: #0F172A; }
        .nav-item.active {
            background: #EFF6FF;
            color: #2563EB;
            border: 1px solid #BFDBFE;
            box-shadow: 0 2px 4px rgba(37,99,235,0.05);
        }
        .nav-item .nav-icon { margin-right: 10px; opacity: 0.7; }
        .nav-item.active .nav-icon { opacity: 1; color: #2563eb; }

        .nav-badge {
            margin-left: auto;
            padding: 2px 7px; border-radius: 20px;
            font-size: 10px; font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
        }
        .badge-amber { background: #FEF3C7; color: #D97706; border: 1px solid #FCD34D; }
        .badge-blue  { background: #DBEAFE; color: #1D4ED8; border: 1px solid #93C5FD; }
        .badge-slate { background: #F1F5F9; color: #475569; border: 1px solid #CBD5E1; }

        /* ── Top Header ──────────────────────────────── */
        .top-header {
            background: #FFFFFF;
            border-bottom: 1px solid #E2E8F0;
            backdrop-filter: blur(20px);
        }

        .search-input {
            background: #F8FAFC;
            border: 1px solid #CBD5E1;
            color: #0F172A;
            border-radius: 10px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            padding: 8px 12px 8px 38px;
            transition: all 0.2s;
            outline: none;
        }
        .search-input:focus {
            background: #FFFFFF;
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
        .search-input::placeholder { color: #94A3B8; }

        .btn-primary {
            background: #059669;
            border: 1px solid #10b981;
            box-shadow: 0 4px 12px rgba(16,185,129,0.25);
            color: white; font-weight: 700; font-size: 11px;
            padding: 8px 16px; border-radius: 10px;
            cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background: #047857;
            box-shadow: 0 6px 18px rgba(16,185,129,0.35);
            transform: translateY(-1px);
        }

        /* ── Hero Banner ─────────────────────────────── */
        .hero-banner {
            background: #0F172A;
            border: 1px solid #334155;
            border-radius: 16px;
            position: relative; overflow: hidden;
            color: #FFFFFF;
            box-shadow: 0 10px 25px -5px rgba(15,23,42,0.15);
        }
        .hero-banner::before {
            content: '';
            position: absolute; inset: 0;
            background: transparent;
            pointer-events: none;
        }
        .hero-banner::after {
            content: '';
            position: absolute; top: -1px; left: 0; right: 0; height: 2px;
            background: #1e293b;
            border-radius: 16px 16px 0 0;
        }

        .hero-grid-lines {
            position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }

        .status-dot-live {
            width: 8px; height: 8px; border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 8px #10b981, 0 0 16px rgba(16,185,129,0.4);
            animation: liveGlow 1.5s ease-in-out infinite alternate;
        }
        @keyframes liveGlow {
            from { box-shadow: 0 0 4px #10b981; }
            to   { box-shadow: 0 0 12px #10b981, 0 0 24px rgba(16,185,129,0.4); }
        }

        /* ── KPI Cards ───────────────────────────────── */
        .kpi-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            padding: 20px;
            position: relative; overflow: hidden;
            transition: all 0.3s ease;
            cursor: default;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03), 0 2px 4px -2px rgba(0,0,0,0.03);
        }
        .kpi-card:hover {
            border-color: #3B82F6;
            transform: translateY(-2px);
            box-shadow: 0 12px 25px -5px rgba(37,99,235,0.1);
        }
        .kpi-card::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0; height: 1px;
            background: transparent;
        }

        .kpi-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
        }
        .kpi-icon-blue   { background: #EFF6FF; color: #2563EB; border: 1px solid #BFDBFE; }
        .kpi-icon-amber  { background: #FFFBEB; color: #D97706; border: 1px solid #FDE68A; }
        .kpi-icon-indigo { background: #EEF2FF; color: #4F46E5; border: 1px solid #C7D2FE; }
        .kpi-icon-emerald{ background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; }

        .kpi-value { font-size: 28px; font-weight: 900; font-family: 'JetBrains Mono', monospace; line-height: 1; }
        .kpi-value-blue   { color: #1E40AF; }
        .kpi-value-amber  { color: #D97706; }
        .kpi-value-indigo { color: #4338CA; }
        .kpi-value-emerald{ color: #047857; }

        /* Sparkline SVG */
        .sparkline { display: block; }

        /* ── Unit Cards ──────────────────────────────── */
        .unit-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 14px; padding: 18px;
            position: relative; overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03);
        }
        .unit-card:hover { transform: translateY(-3px); box-shadow: 0 12px 25px -5px rgba(0,0,0,0.08); }
        .unit-card-blue   { border-top: 3px solid #3b82f6; }
        .unit-card-blue:hover   { box-shadow: 0 15px 30px rgba(59,130,246,0.12); border-color: #93C5FD; border-top-color: #3b82f6; }
        .unit-card-indigo { border-top: 3px solid #6366f1; }
        .unit-card-indigo:hover { box-shadow: 0 15px 30px rgba(99,102,241,0.12); border-color: #C7D2FE; border-top-color: #6366f1; }
        .unit-card-emerald{ border-top: 3px solid #10b981; }
        .unit-card-emerald:hover{ box-shadow: 0 15px 30px rgba(16,185,129,0.12); border-color: #A7F3D0; border-top-color: #10b981; }
        .unit-card-amber  { border-top: 3px solid #f59e0b; }
        .unit-card-amber:hover  { box-shadow: 0 15px 30px rgba(245,158,11,0.12); border-color: #FDE68A; border-top-color: #f59e0b; }

        /* Radial progress */
        .radial-ring { transform: rotate(-90deg); }
        .radial-text { dominant-baseline: central; text-anchor: middle; }

        /* ── Approvals Table ─────────────────────────── */
        .table-panel {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 16px; overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03);
        }
        .table-header-row {
            background: #F8FAFC;
            border-bottom: 1px solid #E2E8F0;
        }
        .table-header-row th {
            padding: 12px 16px;
            font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.08em;
            color: #64748B;
            font-family: 'JetBrains Mono', monospace;
        }
        .case-row { transition: background 0.15s; border-bottom: 1px solid #F1F5F9; color: #0F172A; }
        .case-row:hover { background: #F8FAFC; }
        .case-row td { padding: 14px 16px; font-size: 12px; vertical-align: middle; }

        .status-badge {
            display: inline-flex; align-items: center;
            padding: 4px 10px; border-radius: 20px;
            font-size: 10px; font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
        }
        .status-await  { background: #FFFBEB; color: #D97706; border: 1px solid #FDE68A; }
        .status-done   { background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; }
        .status-vault  { background: #F1F5F9; color: #475569; border: 1px solid #CBD5E1; }

        .btn-inspect {
            padding: 5px 12px; border-radius: 8px; font-size: 10px; font-weight: 700; cursor: pointer;
            background: #F1F5F9; border: 1px solid #CBD5E1;
            color: #334155; transition: all 0.2s; font-family: 'JetBrains Mono', monospace;
        }
        .btn-inspect:hover { background: #E2E8F0; color: #0F172A; border-color: #94A3B8; }

        .btn-review {
            padding: 6px 14px; border-radius: 8px; font-size: 11px; font-weight: 700; cursor: pointer;
            background: #0f172a;
            border: 1px solid #1e293b; color: #ffffff;
            transition: all 0.2s ease; font-family: 'JetBrains Mono', monospace;
            display: inline-flex; align-items: center; gap: 6px;
            box-shadow: 0 2px 6px rgba(15,23,42,0.2);
        }
        .btn-review:hover {
            background: #1e293b;
            box-shadow: 0 4px 12px rgba(15,23,42,0.35);
            transform: translateY(-1px);
        }

        .status-declined {
            background: #FEF2F2; color: #DC2626; border: 1px solid #FCA5A5;
        }

        .btn-approve {
            padding: 5px 12px; border-radius: 8px; font-size: 10px; font-weight: 700; cursor: pointer;
            background: #ECFDF5;
            border: 1px solid #A7F3D0; color: #047857;
            transition: all 0.2s; font-family: 'JetBrains Mono', monospace;
        }
        .btn-approve:hover { background: #D1FAE5; box-shadow: 0 2px 6px rgba(16,185,129,0.2); }

        .btn-transfer {
            padding: 5px 12px; border-radius: 8px; font-size: 10px; font-weight: 700; cursor: pointer;
            background: #EFF6FF;
            border: 1px solid #BFDBFE; color: #1D4ED8;
            transition: all 0.2s; font-family: 'JetBrains Mono', monospace;
        }
        .btn-transfer:hover { background: #DBEAFE; box-shadow: 0 2px 6px rgba(59,130,246,0.2); }

        /* ── Filter Tabs ─────────────────────────────── */
        .filter-tab {
            padding: 5px 12px; border-radius: 8px; font-size: 11px; font-weight: 600; cursor: pointer;
            background: #F1F5F9; border: 1px solid #E2E8F0;
            color: #475569; transition: all 0.2s;
        }
        .filter-tab:hover { background: #E2E8F0; color: #0F172A; }
        .filter-tab.active {
            background: #DBEAFE;
            border-color: #93C5FD; color: #1E40AF;
        }

        /* ── Audit Timeline ──────────────────────────── */
        .audit-panel {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 16px; overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03);
        }
        .audit-entry {
            padding: 12px 16px;
            border-bottom: 1px solid #F1F5F9;
            display: flex; gap: 12px; align-items: flex-start;
            transition: background 0.15s;
        }
        .audit-entry:hover { background: #F8FAFC; }
        .audit-entry:last-child { border-bottom: none; }

        .audit-dot {
            width: 8px; height: 8px; border-radius: 50%; margin-top: 4px; flex-shrink: 0;
        }
        .dot-blue   { background: #3b82f6; box-shadow: 0 0 6px rgba(59,130,246,0.4); }
        .dot-emerald{ background: #10b981; box-shadow: 0 0 6px rgba(16,185,129,0.4); }
        .dot-amber  { background: #f59e0b; box-shadow: 0 0 6px rgba(245,158,11,0.4); }
        .dot-indigo { background: #6366f1; box-shadow: 0 0 6px rgba(99,102,241,0.4); }

        /* ── Quick Actions ───────────────────────────── */
        .action-card {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 12px; padding: 14px;
            display: flex; align-items: center; gap: 12px;
            cursor: pointer; transition: all 0.2s;
        }
        .action-card:hover {
            background: #EFF6FF;
            border-color: #BFDBFE;
        }
        .action-icon {
            width: 38px; height: 38px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }

        /* ── Modal ───────────────────────────────────── */
        .modal-overlay {
            position: fixed; inset: 0; z-index: 100;
            background: rgba(15,23,42,0.6);
            backdrop-filter: blur(6px);
            display: flex; align-items: center; justify-content: center; padding: 20px;
        }
        .modal-overlay.hidden {
            display: none !important;
        }
        .modal-box {
            background: #FFFFFF;
            border: 1px solid #CBD5E1;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            max-width: 520px; width: 100%; padding: 28px;
            color: #0F172A;
        }
        .modal-box::before {
            content: ''; display: block; height: 2px; margin-bottom: 0;
            background: #0f172a;
            border-radius: 2px 2px 0 0; margin: -28px -28px 28px;
        }

        .form-label { font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block; }
        .form-input {
            width: 100%; padding: 10px 14px; border-radius: 10px;
            background: #F8FAFC; border: 1px solid #CBD5E1;
            color: #0F172A; font-size: 12px; font-family: 'JetBrains Mono', monospace;
            outline: none; transition: all 0.2s;
        }
        .form-input:focus { border-color: #2563EB; background: #FFFFFF; box-shadow: 0 0 0 3px rgba(37,99,235,0.15); }
        .form-input::placeholder { color: #94A3B8; }
        .form-input[readonly] { color: #2563eb; cursor: not-allowed; background: #F1F5F9; }

        .form-select {
            width: 100%; padding: 10px 14px; border-radius: 10px;
            background: #F8FAFC; border: 1px solid #CBD5E1;
            color: #0F172A; font-size: 12px; outline: none; transition: all 0.2s;
            appearance: none; cursor: pointer;
        }
        .form-select:focus { border-color: #2563EB; background: #FFFFFF; box-shadow: 0 0 0 3px rgba(37,99,235,0.15); }
        .form-select option { background: #FFFFFF; color: #0F172A; }

        /* ── Pill Tags ───────────────────────────────── */
        .pill {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 8px; border-radius: 6px; font-size: 10px; font-weight: 700;
            font-family: 'JetBrains Mono', monospace; letter-spacing: 0.03em;
        }
        .pill-blue   { background: #DBEAFE; color: #1D4ED8; border: 1px solid #93C5FD; }
        .pill-indigo { background: #EEF2FF; color: #4338CA; border: 1px solid #C7D2FE; }
        .pill-emerald{ background: #ECFDF5; color: #047857; border: 1px solid #A7F3D0; }
        .pill-amber  { background: #FEF3C7; color: #B45309; border: 1px solid #FCD34D; }
        .pill-exec   { background: #EFF6FF; color: #1D4ED8; border: 1px solid #93C5FD; }

        /* ── Section headings ────────────────────────── */
        .section-label {
            font-size: 10px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
            color: #4F46E5; font-family: 'JetBrains Mono', monospace;
        }

        /* ── Scrollable main ─────────────────────────── */
        .main-scroll { overflow-y: auto; height: calc(100vh - 64px); background: #F8FAFC; }

        /* ── Live badge ──────────────────────────────── */
        .live-badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 9px; border-radius: 20px; font-size: 10px; font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
            background: #ECFDF5; color: #047857; border: 1px solid #A7F3D0;
        }

        /* ── Notification dot ────────────────────────── */
        .notif-dot {
            position: absolute; top: 6px; right: 6px;
            width: 8px; height: 8px; border-radius: 50%;
            background: #f59e0b; border: 2px solid #FFFFFF;
        }

        /* ── Avatar ──────────────────────────────────── */
        .avatar {
            width: 38px; height: 38px; border-radius: 10px;
            background: #0f172a;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 800; color: white;
            box-shadow: 0 2px 8px rgba(15,23,42,0.3);
        }

        /* ── Divider line ────────────────────────────── */
        .v-divider { width: 1px; height: 24px; background: #E2E8F0; }

    </style>
</head>
<body>

<div class="flex h-screen overflow-hidden">

    <!-- ════════════════════════════════════════════════
         LEFT SIDEBAR — Executive Navigation
    ════════════════════════════════════════════════ -->
    @include('Forensic_Head.partials.sidebar')

    <!-- ════════════════════════════════════════════════
         RIGHT — Main Workspace
    ════════════════════════════════════════════════ -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        <!-- TOP WORKSPACE NAVBAR -->
        <header class="top-header h-16 px-6 flex items-center justify-between shrink-0 z-20 gap-4">
            
            <!-- TITLE -->
            <div class="flex items-center space-x-4 shrink-0">
                <h1 class="text-sm font-extrabold text-slate-900 tracking-wide font-mono-code uppercase">
                    Forensic Department Head Command Portal
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
                                <div class="w-8 h-8 rounded-xl bg-purple-500/10 text-purple-600 border border-purple-500/20 flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
                                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v1a2 2 0 01-2 2M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-[10px] font-extrabold font-mono-code text-purple-700 uppercase">Evidence Vault</span>
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

        <!-- MAIN SCROLLABLE WORKSPACE ───────────────── -->
        <main class="main-scroll p-6 space-y-6">
            @include('partials.flash')



            @php
                $totalCasesHead = count($cases ?? []);
                $pendingApprovalsHead = isset($cases) ? $cases->filter(function($c) {
                    return !in_array($c->status, ['Approved', 'Authorized', 'Certified', 'Completed', 'Court Ready']);
                })->count() : 0;
                $vaultItemsHead = isset($cases) ? $cases->sum(function($c) {
                    preg_match('/\d+/', $c->exhibits_summary ?? '', $m);
                    return isset($m[0]) ? (int)$m[0] : ($c->exhibits_summary ? 1 : 0);
                }) : 0;
                $userCountHead = \App\Models\User::count();
            @endphp

            <!-- ════ KPI CARDS ═══════════════════════════ -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

                <!-- KPI 1: Total Cases -->
                <div class="kpi-card">
                    <div style="background: radial-gradient(circle at 80% 20%, rgba(59,130,246,0.08), transparent 60%); position:absolute; inset:0; pointer-events:none; border-radius:16px;"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-[10px] font-mono-code font-bold text-slate-500 uppercase tracking-wider">Directorate Cases</span>
                            <div class="kpi-icon kpi-icon-blue">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="kpi-value kpi-value-blue mb-1">{{ number_format($totalCasesHead) }}</div>
                        <div class="text-[11px] text-slate-500 font-semibold mb-3">Across All Specialized Units</div>
                        <!-- Sparkline -->
                        <svg width="100%" height="32" class="sparkline" viewBox="0 0 120 32" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="spk1" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.4"/>
                                    <stop offset="100%" stop-color="#3b82f6" stop-opacity="0"/>
                                </linearGradient>
                            </defs>
                            <path d="M0,28 L15,24 L30,26 L45,18 L60,20 L75,12 L90,14 L105,8 L120,4" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M0,28 L15,24 L30,26 L45,18 L60,20 L75,12 L90,14 L105,8 L120,4 L120,32 L0,32 Z" fill="url(#spk1)"/>
                        </svg>
                    </div>
                </div>

                <!-- KPI 2: Pending Approvals -->
                <div class="kpi-card">
                    <div style="background: radial-gradient(circle at 80% 20%, rgba(245,158,11,0.07), transparent 60%); position:absolute; inset:0; pointer-events:none; border-radius:16px;"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-[10px] font-mono-code font-bold text-slate-500 uppercase tracking-wider">Head Approvals</span>
                            <div class="kpi-icon kpi-icon-amber">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="kpi-value kpi-value-amber mb-1">{{ number_format($pendingApprovalsHead) }}</div>
                        <div class="text-[11px] text-slate-500 font-semibold mb-3 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 inline-block"></span>
                            Awaiting Director Signature
                        </div>
                        <svg width="100%" height="32" class="sparkline" viewBox="0 0 120 32" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="spk2" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#f59e0b" stop-opacity="0.4"/>
                                    <stop offset="100%" stop-color="#f59e0b" stop-opacity="0"/>
                                </linearGradient>
                            </defs>
                            <path d="M0,20 L15,16 L30,22 L45,10 L60,16 L75,8 L90,14 L105,6 L120,12" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M0,20 L15,16 L30,22 L45,10 L60,16 L75,8 L90,14 L105,6 L120,12 L120,32 L0,32 Z" fill="url(#spk2)"/>
                        </svg>
                    </div>
                </div>

                <!-- KPI 3: Vault Evidence -->
                <div class="kpi-card">
                    <div style="background: radial-gradient(circle at 80% 20%, rgba(99,102,241,0.07), transparent 60%); position:absolute; inset:0; pointer-events:none; border-radius:16px;"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-[10px] font-mono-code font-bold text-slate-500 uppercase tracking-wider">Vault Evidence</span>
                            <div class="kpi-icon kpi-icon-indigo">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                        </div>
                        <div class="kpi-value kpi-value-indigo mb-1">{{ number_format($vaultItemsHead) }}</div>
                        <div class="text-[11px] text-slate-500 font-semibold mb-3 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 inline-block"></span>
                            100% Chain Integrity Verified
                        </div>
                        <svg width="100%" height="32" class="sparkline" viewBox="0 0 120 32" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="spk3" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#6366f1" stop-opacity="0.4"/>
                                    <stop offset="100%" stop-color="#6366f1" stop-opacity="0"/>
                                </linearGradient>
                            </defs>
                            <path d="M0,26 L15,22 L30,24 L45,16 L60,18 L75,10 L90,15 L105,7 L120,10" fill="none" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M0,26 L15,22 L30,24 L45,16 L60,18 L75,10 L90,15 L105,7 L120,10 L120,32 L0,32 Z" fill="url(#spk3)"/>
                        </svg>
                    </div>
                </div>

                <!-- KPI 4: Personnel -->
                <div class="kpi-card">
                    <div style="background: radial-gradient(circle at 80% 20%, rgba(16,185,129,0.07), transparent 60%); position:absolute; inset:0; pointer-events:none; border-radius:16px;"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-[10px] font-mono-code font-bold text-slate-500 uppercase tracking-wider">Personnel &amp; Units</span>
                            <div class="kpi-icon kpi-icon-emerald">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="kpi-value kpi-value-emerald mb-1">{{ number_format($userCountHead) }}</div>
                        <div class="text-[11px] text-slate-500 font-semibold mb-3">System Personnel Registered</div>
                        <svg width="100%" height="32" class="sparkline" viewBox="0 0 120 32" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="spk4" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#10b981" stop-opacity="0.4"/>
                                    <stop offset="100%" stop-color="#10b981" stop-opacity="0"/>
                                </linearGradient>
                            </defs>
                            <path d="M0,22 L15,20 L30,20 L45,18 L60,18 L75,16 L90,16 L105,16 L120,16" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M0,22 L15,20 L30,20 L45,18 L60,18 L75,16 L90,16 L105,16 L120,16 L120,32 L0,32 Z" fill="url(#spk4)"/>
                        </svg>
                    </div>
                </div>

            </div>

            <!-- ════ 4 UNIT CARDS ════════════════════════ -->
            <div id="unitPerformance">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <div class="section-label mb-1">Division Command</div>
                        <h2 class="font-black text-slate-900 text-base">Unit Heads Oversight</h2>
                    </div>
                    <span class="text-[10px] font-mono-code font-bold text-slate-700">4 SPECIALIZED UNITS ACTIVE</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

                    <!-- UNIT 01 — Crime Scene Support -->
                    <div class="unit-card unit-card-blue">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <span class="pill pill-blue mb-2 inline-flex">UNIT 01</span>
                                <h3 class="font-black text-slate-900 text-sm leading-tight">Crime Scene Support</h3>
                                <p class="text-[11px] text-slate-700 mt-0.5">Cmdr. J. Annan</p>
                            </div>
                            <!-- Radial Progress -->
                            <svg width="52" height="52" viewBox="0 0 52 52">
                                <circle cx="26" cy="26" r="21" fill="none" stroke="rgba(59,130,246,0.12)" stroke-width="4"/>
                                <circle cx="26" cy="26" r="21" fill="none" stroke="#3b82f6" stroke-width="4"
                                    stroke-dasharray="132" stroke-dashoffset="33" stroke-linecap="round"
                                    class="radial-ring" style="transform-origin:26px 26px; transform:rotate(-90deg);"/>
                                <text x="26" y="26" class="radial-text" font-size="10" font-weight="800" fill="#1d4ed8" font-family="JetBrains Mono">75%</text>
                            </svg>
                        </div>
                        <div class="space-y-2 pt-3 border-t" style="border-color: #E2E8F0;">
                            <div class="flex justify-between text-xs font-mono-code">
                                <span class="text-slate-600">Active Cases</span>
                                <span class="font-bold text-blue-600">142 Cases</span>
                            </div>
                            <div class="flex justify-between text-xs font-mono-code">
                                <span class="text-slate-600">Officers</span>
                                <span class="font-bold text-slate-900">14</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-1.5 mt-1">
                                <div class="h-1.5 rounded-full bg-emerald-600" style="width:75%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- UNIT 02 — Evidence Management -->
                    <div class="unit-card unit-card-indigo">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <span class="pill pill-indigo mb-2 inline-flex">UNIT 02</span>
                                <h3 class="font-black text-slate-900 text-sm leading-tight">Evidence Management</h3>
                                <p class="text-[11px] text-slate-700 mt-0.5">Dr. E. Osei</p>
                            </div>
                            <svg width="52" height="52" viewBox="0 0 52 52">
                                <circle cx="26" cy="26" r="21" fill="none" stroke="rgba(99,102,241,0.12)" stroke-width="4"/>
                                <circle cx="26" cy="26" r="21" fill="none" stroke="#6366f1" stroke-width="4"
                                    stroke-dasharray="132" stroke-dashoffset="26" stroke-linecap="round"
                                    style="transform-origin:26px 26px; transform:rotate(-90deg);"/>
                                <text x="26" y="26" class="radial-text" font-size="10" font-weight="800" fill="#4338ca" font-family="JetBrains Mono">80%</text>
                            </svg>
                        </div>
                        <div class="space-y-2 pt-3 border-t" style="border-color: #E2E8F0;">
                            <div class="flex justify-between text-xs font-mono-code">
                                <span class="text-slate-600">Logged Evidence</span>
                                <span class="font-bold text-indigo-600">215 Items</span>
                            </div>
                            <div class="flex justify-between text-xs font-mono-code">
                                <span class="text-slate-600">Custodians</span>
                                <span class="font-bold text-slate-900">18</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-1.5 mt-1">
                                <div class="h-1.5 rounded-full bg-indigo-600" style="width:80%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- UNIT 03 — LAB Analysis -->
                    <div class="unit-card unit-card-emerald">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <span class="pill pill-emerald mb-2 inline-flex">UNIT 03</span>
                                <h3 class="font-black text-slate-900 text-sm leading-tight">LAB Analysis</h3>
                                <p class="text-[11px] text-slate-700 mt-0.5">Dr. K. Appiah</p>
                            </div>
                            <svg width="52" height="52" viewBox="0 0 52 52">
                                <circle cx="26" cy="26" r="21" fill="none" stroke="rgba(16,185,129,0.12)" stroke-width="4"/>
                                <circle cx="26" cy="26" r="21" fill="none" stroke="#10b981" stroke-width="4"
                                    stroke-dasharray="132" stroke-dashoffset="46" stroke-linecap="round"
                                    style="transform-origin:26px 26px; transform:rotate(-90deg);"/>
                                <text x="26" y="26" class="radial-text" font-size="10" font-weight="800" fill="#047857" font-family="JetBrains Mono">65%</text>
                            </svg>
                        </div>
                        <div class="space-y-2 pt-3 border-t" style="border-color: #E2E8F0;">
                            <div class="flex justify-between text-xs font-mono-code">
                                <span class="text-slate-600">Lab Analysis</span>
                                <span class="font-bold text-emerald-600">98 Tests</span>
                            </div>
                            <div class="flex justify-between text-xs font-mono-code">
                                <span class="text-slate-600">Analysts</span>
                                <span class="font-bold text-slate-900">12</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-1.5 mt-1">
                                <div class="h-1.5 rounded-full bg-emerald-600" style="width:65%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- UNIT 04 — Digital & Physical Forensics -->
                    <div class="unit-card unit-card-amber">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <span class="pill pill-amber mb-2 inline-flex">UNIT 04</span>
                                <h3 class="font-black text-slate-900 text-sm leading-tight">Digital &amp; Physical Forensics</h3>
                                <p class="text-[11px] text-slate-700 mt-0.5">Eng. M. Mensah</p>
                            </div>
                            <svg width="52" height="52" viewBox="0 0 52 52">
                                <circle cx="26" cy="26" r="21" fill="none" stroke="rgba(245,158,11,0.12)" stroke-width="4"/>
                                <circle cx="26" cy="26" r="21" fill="none" stroke="#f59e0b" stroke-width="4"
                                    stroke-dasharray="132" stroke-dashoffset="53" stroke-linecap="round"
                                    style="transform-origin:26px 26px; transform:rotate(-90deg);"/>
                                <text x="26" y="26" class="radial-text" font-size="10" font-weight="800" fill="#b45309" font-family="JetBrains Mono">60%</text>
                            </svg>
                        </div>
                        <div class="space-y-2 pt-3 border-t" style="border-color: #E2E8F0;">
                            <div class="flex justify-between text-xs font-mono-code">
                                <span class="text-slate-600">Exhibits</span>
                                <span class="font-bold text-amber-600">64 Items</span>
                            </div>
                            <div class="flex justify-between text-xs font-mono-code">
                                <span class="text-slate-600">Experts</span>
                                <span class="font-bold text-slate-900">10</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-1.5 mt-1">
                                <div class="h-1.5 rounded-full bg-amber-500" style="width:60%"></div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ════ APPROVALS TABLE ═════════════════════ -->
            <div id="pendingApprovals" class="table-panel">

                <!-- Table Header -->
                <div class="p-5 border-b flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4" style="border-color: #E2E8F0; background: #F8FAFC;">
                    <div>
                        <div class="section-label mb-1">Case Register</div>
                        <h2 class="font-black text-slate-900 text-base">Department Head Approval &amp; Case Register</h2>
                        <p class="text-xs text-slate-700 mt-1 font-medium">Review pending findings certificates, sign-offs, and inter-unit transfers</p>
                    </div>
                    <!-- Unit Filter Tabs -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <button onclick="filterDeptHeadUnit('all', this)" class="filter-tab active dept-unit-tab">All 4 Units</button>
                        <button onclick="filterDeptHeadUnit('Crime Scene Support', this)" class="filter-tab dept-unit-tab">Crime Scene</button>
                        <button onclick="filterDeptHeadUnit('Evidence Management', this)" class="filter-tab dept-unit-tab">Evidence</button>
                        <button onclick="filterDeptHeadUnit('LAB Analysis', this)" class="filter-tab dept-unit-tab">LAB</button>
                        <button onclick="filterDeptHeadUnit('Digital & Physical Forensics', this)" class="filter-tab dept-unit-tab">Digital</button>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="table-header-row">
                                <th class="text-left">Case Reference</th>
                                <th class="text-left">Evidence Details</th>
                                <th class="text-left">Assigned Unit &amp; Head</th>
                                <th class="text-left">Handling Officer</th>
                                <th class="text-left">Director Status</th>
                                <th class="text-right">Head Actions</th>
                            </tr>
                        </thead>
                        <tbody id="caseTableBody">
                            @forelse($cases as $case)
                                @php
                                    $unitColors = [
                                        'Crime Scene Support' => 'text-blue-600',
                                        'Evidence Management' => 'text-indigo-600',
                                        'LAB Analysis' => 'text-emerald-600',
                                        'Digital & Physical Forensics' => 'text-amber-600',
                                    ];
                                    $color = $unitColors[$case->unit_name] ?? 'text-blue-600';
                                @endphp
                                <tr class="case-row dept-case-row" data-unit="{{ $case->unit_name }}">
                                    <td>
                                        <div class="font-extrabold text-blue-600 font-mono-code text-sm">{{ $case->case_number }}</div>
                                        <div class="text-[10px] text-slate-500 font-mono-code mt-0.5">Submitted: {{ $case->created_at ? $case->created_at->format('Y-m-d') : date('Y-m-d') }}</div>
                                    </td>
                                    <td>
                                        <div class="font-extrabold text-slate-900 text-xs">{{ $case->title }}</div>
                                        <div class="text-[10px] text-blue-600 font-mono-code font-semibold mt-0.5">{{ $case->unit_name ?? 'Forensic Directorate' }}</div>
                                        <div class="text-[11px] text-slate-600 font-medium">{{ $case->exhibits_summary ?? 'Director Authorized Case' }}</div>
                                    </td>
                                    <td>
                                        <div class="font-extrabold {{ $color }} text-xs">{{ $case->unit_name ?? 'Forensic Directorate' }}</div>
                                        <div class="text-[10px] text-slate-600 font-medium">{{ $case->commanding_officer ?? 'Department Head' }}</div>
                                    </td>
                                    <td>
                                        <div class="text-slate-900 text-xs font-bold">{{ $case->submitting_officer ?? $case->commanding_officer }}</div>
                                        <div class="text-[10px] text-slate-500 font-medium">Handling Officer</div>
                                    </td>
                                    <td>
                                        @if($case->status === 'Approved' || $case->status === 'Authorized')
                                            <span class="status-badge status-done" id="status-{{ $case->case_number }}">
                                                <svg class="w-3 h-3 mr-1 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                Authorized
                                            </span>
                                        @elseif($case->status === 'Declined')
                                            <span class="status-badge status-declined" id="status-{{ $case->case_number }}">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-600 mr-1.5 inline-block"></span>
                                                Declined
                                            </span>
                                        @else
                                            <span class="status-badge status-await" id="status-{{ $case->case_number }}">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5 inline-block"></span>
                                                {{ $case->status }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @if($case->status === 'Approved' || $case->status === 'Authorized')
                                            <a href="/cases/{{ $case->case_number }}/review?from=Forensic+Head" class="btn-review bg-emerald-700 hover:bg-emerald-800 border-emerald-600" id="btn-{{ $case->case_number }}">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                Preview
                                            </a>
                                        @else
                                            <a href="/cases/{{ $case->case_number }}/review?from=Forensic+Head" class="btn-review" id="btn-{{ $case->case_number }}">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                Review
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-500 font-mono-code text-xs">
                                        No cases found in database.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ════ BOTTOM ROW: Audit Log + Quick Actions ═════════════════════ -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 pb-6">

                <!-- Audit Log (2/3 width) -->
                <div class="lg:col-span-2 audit-panel">
                    <div class="p-5 border-b flex items-center justify-between" style="border-color: #E2E8F0;">
                        <div>
                            <div class="section-label mb-1">System Feed</div>
                            <h3 class="font-black text-slate-900 text-sm">Directorate Real-time Audit Stream</h3>
                            <p class="text-xs text-slate-700 mt-0.5 font-medium">Logged actions across all 4 division heads</p>
                        </div>
                        <div class="live-badge">
                            <span class="status-dot-live" style="width:6px;height:6px;"></span>
                            LIVE LOG
                        </div>
                    </div>

                    <div>
                        <div class="audit-entry">
                            <div class="audit-dot dot-blue mt-1"></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-bold text-blue-600 text-xs font-mono-code">FSD-2026-8842</span>
                                    <span class="text-[10px] text-slate-700 font-mono-code shrink-0">10 mins ago</span>
                                </div>
                                <div class="text-xs text-slate-700 mt-0.5">Submitted by <strong class="text-slate-900 font-bold">Dr. Evelyn Osei (Evidence Mgmt Head)</strong> for Director Approval.</div>
                            </div>
                        </div>
                        <div class="audit-entry">
                            <div class="audit-dot dot-emerald mt-1"></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-bold text-emerald-600 text-xs font-mono-code">FSD-2026-8839</span>
                                    <span class="text-[10px] text-slate-700 font-mono-code shrink-0">35 mins ago</span>
                                </div>
                                <div class="text-xs text-slate-700 mt-0.5">Hardware write-blocker image checksum validated by <strong class="text-slate-900 font-bold">Eng. Michael Mensah (Digital Head)</strong>.</div>
                            </div>
                        </div>
                        <div class="audit-entry">
                            <div class="audit-dot dot-amber mt-1"></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-bold text-amber-600 text-xs font-mono-code">FSD-2026-8830</span>
                                    <span class="text-[10px] text-slate-700 font-mono-code shrink-0">1 hr ago</span>
                                </div>
                                <div class="text-xs text-slate-700 mt-0.5">3D Point Cloud data certified and <strong class="text-slate-900 font-bold">authorized by {{ $officerName }} ({{ $officerRole }})</strong>. Court report issued.</div>
                            </div>
                        </div>
                        <div class="audit-entry">
                            <div class="audit-dot dot-indigo mt-1"></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-bold text-indigo-600 text-xs font-mono-code">FSD-2026-8825</span>
                                    <span class="text-[10px] text-slate-700 font-mono-code shrink-0">3 hrs ago</span>
                                </div>
                                <div class="text-xs text-slate-700 mt-0.5">Toxicology GC-MS batch results logged to <strong class="text-slate-900 font-bold">Vault Storage B-04</strong> by Dr. K. Appiah (Lab Head).</div>
                            </div>
                        </div>
                        <div class="audit-entry">
                            <div class="audit-dot dot-blue mt-1"></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-bold text-blue-600 text-xs font-mono-code">SYSTEM</span>
                                    <span class="text-[10px] text-slate-700 font-mono-code shrink-0">6 hrs ago</span>
                                </div>
                                <div class="text-xs text-slate-700 mt-0.5">Scheduled chain-of-custody integrity audit completed. <strong class="text-slate-900 font-bold">5,892 vault items verified</strong> — 100% compliant.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions (1/3 width) -->
                <div class="audit-panel p-5 flex flex-col gap-4">
                    <div>
                        <div class="section-label mb-1">Command Controls</div>
                        <h3 class="font-black text-slate-900 text-sm">Department Head Actions</h3>
                    </div>

                    <div class="space-y-3">
                        <div class="action-card" onclick="toggleIntakeModal()">
                            <div class="action-icon" style="background: #EFF6FF; border: 1px solid #BFDBFE;">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold text-slate-900">Authorize Case File</div>
                                <div class="text-[10px] text-slate-700">Create high-priority directorate case</div>
                            </div>
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>

                        <div class="action-card" onclick="batchApprove()">
                            <div class="action-icon" style="background: #ECFDF5; border: 1px solid #A7F3D0;">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold text-slate-900">Approve All Pending</div>
                                <div class="text-[10px] text-slate-700">Batch sign-off court documents</div>
                            </div>
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>

                        <div class="action-card" onclick="alert('Exporting Directorate Audit Certificate...')">
                            <div class="action-icon" style="background: #EEF2FF; border: 1px solid #C7D2FE;">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold text-slate-900">Export Audit Certificate</div>
                                <div class="text-[10px] text-slate-700">Generate audit compliance report</div>
                            </div>
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>

                        <div class="action-card" onclick="alert('Opening Personnel Roster...')">
                            <div class="action-icon" style="background: #FEF3C7; border: 1px solid #FCD34D;">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold text-slate-900">Personnel Roster</div>
                                <div class="text-[10px] text-slate-700">54 officers across 4 units</div>
                            </div>
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>


<!-- ════════════════════════════════════════════════════════════
     MODAL 1 — Authorize Case File
════════════════════════════════════════════════════════════ -->
<div id="intakeModal" class="hidden modal-overlay">
    <div class="modal-box">
        <div class="flex items-center justify-between mb-6">
            <div>
                <div class="section-label mb-1">Director Authorization</div>
                <h3 class="text-lg font-black text-slate-900">Authorize High-Priority Case</h3>
            </div>
            <button onclick="toggleIntakeModal()" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors text-xl font-bold">&times;</button>
        </div>

        <form onsubmit="event.preventDefault(); submitIntake();" class="space-y-4">
            <div>
                <label class="form-label">Case Reference Number</label>
                <input type="text" id="newCaseRef" value="FSD-2026-8850" class="form-input">
            </div>
            <div>
                <label class="form-label">Evidence Item Description</label>
                <input type="text" id="newDesc" placeholder="e.g. Suspected Substance Batch / Encrypted Storage Drive" required class="form-input">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label">Assigned Division</label>
                    <select id="newUnit" class="form-select">
                        <option>Crime Scene Support</option>
                        <option>Evidence Management</option>
                        <option>LAB Analysis</option>
                        <option>Digital &amp; Physical Forensics</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Commanding Officer</label>
                    <input type="text" id="newOfficer" value="{{ $officerName }}" readonly class="form-input">
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="toggleIntakeModal()"
                    class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 border border-slate-300 hover:bg-slate-200 transition-colors">
                    Cancel
                </button>
                <button type="submit" class="btn-primary">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Issue Directorate Authorization
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     MODAL 2 — Case Inspection Dossier
════════════════════════════════════════════════════════════ -->
<!-- ════════════════════════════════════════════════════════════
     MODAL 2 — Case Detailed Review Dossier
════════════════════════════════════════════════════════════ -->
<div id="detailsModal" class="hidden modal-overlay">
    <div class="modal-box" style="max-width: 620px;">
        <div class="flex items-center justify-between mb-5">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="pill pill-exec">DIRECTOR DOSSIER REVIEW</span>
                    <span class="text-xs font-mono-code font-bold text-slate-500" id="modalCasePriority">HIGH PRIORITY</span>
                </div>
                <h3 class="text-xl font-black text-slate-900" id="modalCaseTitle">Case Review Dossier</h3>
            </div>
            <button onclick="toggleDetailsModal()" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors text-xl font-bold">&times;</button>
        </div>

        <div class="space-y-4">
            <!-- Summary Banner -->
            <div class="p-4 rounded-xl bg-slate-900 text-white font-mono-code space-y-2 border border-slate-800">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-400 font-bold">CASE REFERENCE: <span id="modalCaseRef" class="text-blue-400 font-extrabold text-sm">FSD-2026-8842</span></span>
                    <span id="modalCaseStatusBadge" class="status-badge status-await">Awaiting Sign-off</span>
                </div>
                <div class="text-xs text-slate-300">
                    TITLE / ITEM: <span id="modalItemTitle" class="text-white font-bold">Suspected Narcotics Specimen</span>
                </div>
                <div class="text-[10px] text-slate-400 flex items-center gap-1 pt-2 border-t border-slate-800">
                    <svg class="w-3.5 h-3.5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    CRYPTOGRAPHIC HASH: <span id="modalCaseHash" class="text-emerald-400 font-bold">SHA256:8f4b2c19904a77e8b912cd319f00192a83f</span>
                </div>
            </div>

            <!-- Detailed Grid Information -->
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Assigned Division</div>
                    <div id="modalUnit" class="font-bold text-blue-700 mt-0.5">Crime Scene Support</div>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Commanding Officer / Head</div>
                    <div id="modalUnitHead" class="font-bold text-slate-900 mt-0.5">Cmdr. J. Annan</div>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Handling / Submitting Officer</div>
                    <div id="modalOfficer" class="font-bold text-slate-900 mt-0.5">Insp. Eric Ofori</div>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Submission Timestamp</div>
                    <div id="modalDate" class="font-bold text-slate-800 font-mono-code mt-0.5">2026-09-08 10:15</div>
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
        </div>

        <!-- Action Footer with Approve & Decline Buttons underneath -->
        <div class="flex items-center justify-between pt-5 border-t border-slate-200 mt-6">
            <button type="button" onclick="toggleDetailsModal()"
                class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 border border-slate-300 hover:bg-slate-200 transition-colors">
                Close Review
            </button>
            <div class="flex items-center gap-3" id="modalActionButtons">
                <button type="button" onclick="declineCaseFromModal()"
                    class="px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-red-600 hover:bg-red-700 border border-red-700 shadow-md transition-all flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    Decline Case
                </button>
                <button type="button" onclick="approveCaseFromModal()" class="btn-primary flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    Approve Case
                </button>
            </div>
        </div>
    </div>
</div>


<script>
    const officerName = @json($officerName);
    const officerRole = @json($officerRole);

    let activeReviewRef = null;

    /* ── Clock ──────────────────────────────────── */
    function updateClock() {
        const now = new Date();
        const pad = n => String(n).padStart(2, '0');
        document.getElementById('liveClock').textContent =
            `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}  ${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())} UTC`;
        document.getElementById('heroDate').textContent =
            now.toLocaleDateString('en-GB', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }
    updateClock();
    setInterval(updateClock, 1000);

    /* ── Notifications & Global Search ──────────── */
    function toggleNotificationDropdown() {
        const dropdown = document.getElementById('notificationDropdown');
        if (dropdown) dropdown.classList.toggle('hidden');
    }

    function handleGlobalSearch(query) {
        const q = (query || '').toLowerCase().trim();
        document.querySelectorAll('.dept-case-row').forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = (!q || text.includes(q)) ? '' : 'none';
        });
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

    /* ── Unit Filter ─────────────────────────────── */
    function filterDeptHeadUnit(unitName, clickedEl) {
        // Filter rows
        document.querySelectorAll('.dept-case-row').forEach(row => {
            const ru = (row.getAttribute('data-unit') || '').toLowerCase();
            const match = unitName === 'all' ||
                ru.includes(unitName.toLowerCase().split(' ')[0].toLowerCase());
            row.style.display = match ? '' : 'none';
        });
        // Update tab styles
        document.querySelectorAll('.dept-unit-tab').forEach(t => t.classList.remove('active'));
        if (clickedEl) clickedEl.classList.add('active');
    }

    /* ── Approve ─────────────────────────────────── */
    async function approveCert(caseRef) {
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch(`/cases/${caseRef}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            });
            const data = await res.json();
            if (res.ok && data.success) {
                const el = document.getElementById(`status-${caseRef}`);
                if (el) {
                    el.className = 'status-badge status-done';
                    el.innerHTML = `<svg class="w-3 h-3 mr-1 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Authorized`;
                }
                const btn = document.getElementById(`btn-${caseRef}`);
                if (btn) {
                    btn.className = 'btn-review bg-emerald-700 hover:bg-emerald-800 border-emerald-600';
                    btn.innerHTML = `<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg> Preview`;
                }
                showToast(`✔ Case ${caseRef} has been officially signed and authorized by ${officerName} (${officerRole}).`);
            } else {
                showToast(`❌ Could not approve case ${caseRef}: ${data.message || 'Error'}`);
            }
        } catch (e) {
            console.error(e);
            showToast(`❌ Connection error approving case ${caseRef}.`);
        }
    }

    /* ── Decline ─────────────────────────────────── */
    async function declineCert(caseRef) {
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch(`/cases/${caseRef}/decline`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            });
            const data = await res.json();
            if (res.ok && data.success) {
                const el = document.getElementById(`status-${caseRef}`);
                if (el) {
                    el.className = 'status-badge status-declined';
                    el.innerHTML = `<span class="w-1.5 h-1.5 rounded-full bg-red-600 mr-1.5 inline-block"></span> Declined`;
                }
                showToast(`❌ Case ${caseRef} has been officially DECLINED by ${officerName} (${officerRole}).`);
            } else {
                showToast(`❌ Could not decline case ${caseRef}: ${data.message || 'Error'}`);
            }
        } catch (e) {
            console.error(e);
            showToast(`❌ Connection error declining case ${caseRef}.`);
        }
    }

    /* ── Batch Approve ───────────────────────────── */
    async function batchApprove() {
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch('/cases/batch-approve', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            });
            const data = await res.json();
            if (res.ok && data.success) {
                document.querySelectorAll('.status-await').forEach(el => {
                    el.className = 'status-badge status-done';
                    el.innerHTML = `<svg class="w-3 h-3 mr-1 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Authorized`;
                });
                document.querySelectorAll('.btn-review').forEach(btn => {
                    btn.className = 'btn-review bg-emerald-700 hover:bg-emerald-800 border-emerald-600';
                    btn.innerHTML = `<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg> Preview`;
                });
                showToast(`✔ ${data.message || 'All pending cases have been authorized.'}`);
            } else {
                showToast(`❌ Batch approve failed: ${data.message || 'Error'}`);
            }
        } catch (e) {
            console.error(e);
            showToast(`❌ Connection error during batch approve.`);
        }
    }

    /* ── Modal Toggles ───────────────────────────── */
    function toggleIntakeModal()  { document.getElementById('intakeModal').classList.toggle('hidden'); }
    function toggleDetailsModal() { document.getElementById('detailsModal').classList.toggle('hidden'); }

    function viewDetails(ref, title, unit, head, officer, status, exhibits, location, priority, desc, date) {
        activeReviewRef = ref;
        document.getElementById('modalCaseRef').textContent = ref;
        document.getElementById('modalCaseTitle').textContent = `Review Case: ${ref}`;
        document.getElementById('modalItemTitle').textContent = title || 'Forensic Case File';
        document.getElementById('modalUnit').textContent = unit || 'Forensic Directorate';
        document.getElementById('modalUnitHead').textContent = head || 'Department Head';
        document.getElementById('modalOfficer').textContent = officer || head || 'Handling Officer';
        document.getElementById('modalDate').textContent = date || new Date().toISOString().slice(0, 16).replace('T', ' ');
        document.getElementById('modalExhibits').textContent = exhibits || 'Logged Exhibits & Sealed Dossier';
        document.getElementById('modalLocation').textContent = location || 'NACOC Central Vault Storage';
        document.getElementById('modalCasePriority').textContent = `${(priority || 'HIGH').toUpperCase()} PRIORITY`;
        document.getElementById('modalDescription').textContent = desc || 'Directorate Authorized Forensic Case File with verified evidence chain.';
        
        const hashStr = 'SHA256:' + (ref.replace(/[^A-Z0-9]/g, '') + '8f4b2c19904a77e8b912cd319f00192a83f').toLowerCase().slice(0, 32);
        document.getElementById('modalCaseHash').textContent = hashStr;

        const badgeEl = document.getElementById('modalCaseStatusBadge');
        const modalBtns = document.getElementById('modalActionButtons');

        if (status === 'Approved' || status === 'Authorized') {
            badgeEl.className = 'status-badge status-done';
            badgeEl.innerHTML = `<svg class="w-3 h-3 mr-1 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Authorized`;
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

        document.getElementById('detailsModal').classList.remove('hidden');
    }

    async function approveCaseFromModal() {
        if (!activeReviewRef) return;
        const caseRef = activeReviewRef;
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            await fetch(`/cases/${caseRef}/approve`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token }
            });
        } catch (e) {
            console.error(e);
        }

        approveCert(caseRef);
        toggleDetailsModal();
    }

    async function declineCaseFromModal() {
        if (!activeReviewRef) return;
        const caseRef = activeReviewRef;
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            await fetch(`/cases/${caseRef}/decline`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token }
            });
        } catch (e) {
            console.error(e);
        }

        declineCert(caseRef);
        toggleDetailsModal();
    }

    /* ── Submit Intake ───────────────────────────── */
    async function submitIntake() {
        const caseRef = document.getElementById('newCaseRef').value;
        const desc    = document.getElementById('newDesc').value;
        const unit    = document.getElementById('newUnit').value;
        const body    = document.getElementById('caseTableBody');

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const response = await fetch('/cases', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    case_number: caseRef,
                    title: desc,
                    unit_name: unit,
                    commanding_officer: '{{ $officerName }}'
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                const unitColors = {
                    'Crime Scene Support': 'text-blue-400',
                    'Evidence Management': 'text-indigo-400',
                    'LAB Analysis': 'text-emerald-400',
                    'Digital & Physical Forensics': 'text-amber-400',
                };
                const color = unitColors[unit] || 'text-blue-400';

                const row = document.createElement('tr');
                row.className = 'case-row dept-case-row';
                row.setAttribute('data-unit', unit);
                row.innerHTML = `
                    <td>
                        <div class="font-bold text-blue-400 font-mono-code text-sm">${data.case.case_number}</div>
                        <div class="text-[10px] text-slate-600 font-mono-code mt-0.5">Submitted: ${new Date().toISOString().slice(0,10)}</div>
                    </td>
                    <td>
                        <div class="font-semibold text-slate-200 text-xs">${desc}</div>
                        <div class="text-[11px] text-slate-500">Director Authorized Case</div>
                    </td>
                    <td>
                        <div class="font-bold ${color} text-xs">${unit}</div>
                        <div class="text-[10px] text-slate-600">Assigned Division</div>
                    </td>
                    <td>
                        <div class="text-slate-300 text-xs font-semibold">${officerName}</div>
                        <div class="text-[10px] text-slate-600">Department Head</div>
                    </td>
                    <td>
                        <span class="status-badge status-done" id="status-${data.case.case_number}">
                            <svg class="w-3 h-3 mr-1 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            Authorized
                        </span>
                    </td>
                    <td class="text-right">
                        <a href="/cases/${data.case.case_number}/review?from=Forensic+Head" class="btn-review">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Review
                        </a>
                    </td>
                `;
                body.insertBefore(row, body.firstChild);
                toggleIntakeModal();
                showToast(`✔ Case ${data.case.case_number} authorized and saved to database.`);
            } else {
                showToast(`❌ Error saving case: ${data.message || 'Validation failed'}`);
            }
        } catch (err) {
            console.error(err);
            showToast(`❌ Connection error saving case to database.`);
        }
    }

    /* ── Toast Notification ──────────────────────── */
    function showToast(msg) {
        const t = document.createElement('div');
        t.textContent = msg;
        Object.assign(t.style, {
            position: 'fixed', bottom: '24px', right: '24px', zIndex: '9999',
            background: 'linear-gradient(135deg, #0e1829, #0d1e3a)',
            border: '1px solid rgba(59,130,246,0.35)',
            color: '#93c5fd', padding: '12px 18px', borderRadius: '12px',
            fontSize: '12px', fontFamily: "'JetBrains Mono', monospace", fontWeight: '600',
            boxShadow: '0 12px 40px rgba(0,0,0,0.5), 0 0 20px rgba(59,130,246,0.15)',
            maxWidth: '400px', lineHeight: '1.5',
            transform: 'translateY(20px)', opacity: '0',
            transition: 'all 0.35s cubic-bezier(0.34,1.56,0.64,1)',
        });
        document.body.appendChild(t);
        requestAnimationFrame(() => { t.style.transform = 'translateY(0)'; t.style.opacity = '1'; });
        setTimeout(() => {
            t.style.transform = 'translateY(10px)'; t.style.opacity = '0';
            setTimeout(() => t.remove(), 400);
        }, 4000);
    }

    /* ── Ctrl + K Search ─────────────────────────── */
    document.addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            document.getElementById('globalSearch').focus();
        }
        if (e.key === 'Escape') {
            document.getElementById('intakeModal').classList.add('hidden');
            document.getElementById('detailsModal').classList.add('hidden');
        }
    });

    /* ── Close modals on backdrop click ──────────── */
    document.getElementById('intakeModal').addEventListener('click', function(e) {
        if (e.target === this) toggleIntakeModal();
    });
    document.getElementById('detailsModal').addEventListener('click', function(e) {
        if (e.target === this) toggleDetailsModal();
    });

    /* ── 3-Second Real-Time Polling Interval ─────── */
    let currentPollingFilter = 'all';
    async function pollHeadCasesTable() {
        try {
            const intakeModal = document.getElementById('intakeModal');
            const detailsModal = document.getElementById('detailsModal');
            if ((intakeModal && !intakeModal.classList.contains('hidden')) ||
                (detailsModal && !detailsModal.classList.contains('hidden'))) {
                return;
            }

            const res = await fetch('/api/cases', {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const cases = await res.json();
            if (!Array.isArray(cases)) return;

            const tbody = document.getElementById('caseTableBody');
            if (!tbody) return;

            const activeTab = document.querySelector('.dept-unit-tab.active');
            const filterVal = activeTab ? (activeTab.getAttribute('onclick')?.match(/'([^']+)'/)?.[1] || 'all') : 'all';

            let pendingCount = 0;
            const unitColors = {
                'Crime Scene Support': 'text-blue-400',
                'Evidence Management': 'text-indigo-400',
                'LAB Analysis': 'text-emerald-400',
                'Digital & Physical Forensics': 'text-amber-400',
            };

            let html = '';
            if (cases.length === 0) {
                html = `<tr><td colspan="6" class="py-8 text-center text-slate-500 font-mono-code text-xs">No cases found in database.</td></tr>`;
            } else {
                cases.forEach(c => {
                    if (['Awaiting Sign-off', 'Pending Approval', 'Pending'].includes(c.status)) {
                        pendingCount++;
                    }

                    const color = unitColors[c.unit_name] || 'text-blue-400';
                    const createdDate = c.created_at ? c.created_at.slice(0, 10) : new Date().toISOString().slice(0, 10);
                    
                    let statusBadge = '';
                    if (c.status === 'Approved' || c.status === 'Authorized') {
                        statusBadge = `<span class="status-badge status-done" id="status-${c.case_number}">
                            <svg class="w-3 h-3 mr-1 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
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
                    if (c.status === 'Approved' || c.status === 'Authorized') {
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

                    const ru = (c.unit_name || '').toLowerCase();
                    const isMatch = filterVal === 'all' || ru.includes(filterVal.toLowerCase().split(' ')[0]);

                    html += `<tr class="case-row dept-case-row" data-unit="${c.unit_name || ''}" style="${isMatch ? '' : 'display:none;'}">
                        <td>
                            <div class="font-bold text-blue-400 font-mono-code text-sm">${c.case_number}</div>
                            <div class="text-[10px] text-slate-600 font-mono-code mt-0.5">Submitted: ${createdDate}</div>
                        </td>
                        <td>
                            <div class="font-semibold text-slate-200 text-xs">${c.title || 'Untitled Case'}</div>
                            <div class="text-[11px] text-slate-500">${c.exhibits_summary || 'Director Authorized Case'}</div>
                        </td>
                        <td>
                            <div class="font-bold ${color} text-xs">${c.unit_name || 'Forensic Directorate'}</div>
                            <div class="text-[10px] text-slate-600">${c.commanding_officer || 'Department Head'}</div>
                        </td>
                        <td>
                            <div class="text-slate-300 text-xs font-semibold">${c.submitting_officer || c.commanding_officer || 'Handling Officer'}</div>
                            <div class="text-[10px] text-slate-600">Handling Officer</div>
                        </td>
                        <td>${statusBadge}</td>
                        <td class="text-right">${actionBtn}</td>
                    </tr>`;
                });
            }

            tbody.innerHTML = html;

            const pendingBadgeEl = document.getElementById('pendingCasesCountBadge');
            if (pendingBadgeEl) pendingBadgeEl.innerText = pendingCount;
        } catch (e) {
            console.warn('Head cases polling failed:', e);
        }
    }
    setInterval(pollHeadCasesTable, 3000);
</script>

</body>
</html>
