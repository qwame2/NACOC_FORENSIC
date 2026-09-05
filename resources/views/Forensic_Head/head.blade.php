@php
    $authUser = auth()->user();
    $officerName = $authUser->fullname ?? session('user_name') ?? 'Adomako Emmanuel';
    $officerRole = ($authUser && $authUser->is_head) ? 'Forensic Head' : 'Commanding Officer';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forensic Department Head Command Portal | NACOC</title>
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

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #060A14;
            color: #E2E8F0;
            overflow: hidden;
        }

        .font-mono-code { font-family: 'JetBrains Mono', monospace; }

        /* ── Scrollbars ─────────────────────────────── */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: rgba(255,255,255,0.03); }
        ::-webkit-scrollbar-thumb { background: rgba(59,130,246,0.35); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(59,130,246,0.6); }

        /* ── Sidebar ─────────────────────────────────── */
        .sidebar {
            background: linear-gradient(180deg, #060d1f 0%, #080f1e 100%);
            border-right: 1px solid rgba(59,130,246,0.12);
        }

        .sidebar-logo-ring {
            background: linear-gradient(135deg, #1d4ed8, #3b82f6);
            box-shadow: 0 0 20px rgba(59,130,246,0.5), 0 0 40px rgba(59,130,246,0.2);
        }

        .nav-item {
            display: flex; align-items: center;
            padding: 9px 12px; border-radius: 10px;
            font-size: 12px; font-weight: 500;
            color: rgba(148,163,184,0.8);
            transition: all 0.2s ease;
            cursor: pointer; text-decoration: none;
        }
        .nav-item:hover { background: rgba(59,130,246,0.1); color: #e2e8f0; }
        .nav-item.active {
            background: linear-gradient(135deg, rgba(37,99,235,0.3), rgba(59,130,246,0.15));
            color: #93c5fd;
            border: 1px solid rgba(59,130,246,0.3);
            box-shadow: 0 0 15px rgba(59,130,246,0.1);
        }
        .nav-item .nav-icon { margin-right: 10px; opacity: 0.7; }
        .nav-item.active .nav-icon { opacity: 1; color: #60a5fa; }

        .nav-badge {
            margin-left: auto;
            padding: 2px 7px; border-radius: 20px;
            font-size: 10px; font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
        }
        .badge-amber { background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }
        .badge-blue  { background: rgba(59,130,246,0.15);  color: #60a5fa;  border: 1px solid rgba(59,130,246,0.3); }
        .badge-slate { background: rgba(100,116,139,0.15); color: #94a3b8;  border: 1px solid rgba(100,116,139,0.3);}

        /* ── Top Header ──────────────────────────────── */
        .top-header {
            background: rgba(6,10,20,0.85);
            border-bottom: 1px solid rgba(59,130,246,0.1);
            backdrop-filter: blur(20px);
        }

        .search-input {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            color: #e2e8f0;
            border-radius: 10px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            padding: 8px 12px 8px 38px;
            transition: all 0.2s;
            outline: none;
        }
        .search-input:focus {
            background: rgba(59,130,246,0.08);
            border-color: rgba(59,130,246,0.4);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .search-input::placeholder { color: rgba(148,163,184,0.4); }

        .btn-primary {
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
            border: 1px solid rgba(59,130,246,0.4);
            box-shadow: 0 4px 15px rgba(37,99,235,0.4), inset 0 1px 0 rgba(255,255,255,0.1);
            color: white; font-weight: 700; font-size: 11px;
            padding: 8px 16px; border-radius: 10px;
            cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            box-shadow: 0 6px 20px rgba(37,99,235,0.55), inset 0 1px 0 rgba(255,255,255,0.15);
            transform: translateY(-1px);
        }

        /* ── Hero Banner ─────────────────────────────── */
        .hero-banner {
            background: linear-gradient(135deg, #0c1a3a 0%, #0a1628 40%, #0d1f3c 70%, #081424 100%);
            border: 1px solid rgba(59,130,246,0.2);
            border-radius: 16px;
            position: relative; overflow: hidden;
        }
        .hero-banner::before {
            content: '';
            position: absolute; inset: 0;
            background: radial-gradient(ellipse at 20% 50%, rgba(37,99,235,0.15) 0%, transparent 60%),
                        radial-gradient(ellipse at 80% 20%, rgba(99,102,241,0.1) 0%, transparent 50%);
            pointer-events: none;
        }
        .hero-banner::after {
            content: '';
            position: absolute; top: -1px; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, transparent, #3b82f6, #6366f1, #3b82f6, transparent);
            border-radius: 16px 16px 0 0;
        }

        .hero-grid-lines {
            position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(59,130,246,0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(59,130,246,0.05) 1px, transparent 1px);
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
            background: linear-gradient(135deg, rgba(15,23,42,0.9), rgba(10,15,35,0.95));
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 16px;
            padding: 20px;
            position: relative; overflow: hidden;
            transition: all 0.3s ease;
            cursor: default;
        }
        .kpi-card:hover {
            border-color: rgba(59,130,246,0.3);
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.4), 0 0 20px rgba(59,130,246,0.08);
        }
        .kpi-card::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
        }

        .kpi-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
        }
        .kpi-icon-blue   { background: rgba(59,130,246,0.15); color: #60a5fa; border: 1px solid rgba(59,130,246,0.2); }
        .kpi-icon-amber  { background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.2); }
        .kpi-icon-indigo { background: rgba(99,102,241,0.15); color: #a5b4fc; border: 1px solid rgba(99,102,241,0.2); }
        .kpi-icon-emerald{ background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.2); }

        .kpi-value { font-size: 28px; font-weight: 900; font-family: 'JetBrains Mono', monospace; line-height: 1; }
        .kpi-value-blue   { color: #93c5fd; }
        .kpi-value-amber  { color: #fcd34d; }
        .kpi-value-indigo { color: #c7d2fe; }
        .kpi-value-emerald{ color: #6ee7b7; }

        /* Sparkline SVG */
        .sparkline { display: block; }

        /* ── Unit Cards ──────────────────────────────── */
        .unit-card {
            background: linear-gradient(135deg, rgba(15,23,42,0.95), rgba(8,12,28,0.98));
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 14px; padding: 18px;
            position: relative; overflow: hidden;
            transition: all 0.3s ease;
        }
        .unit-card:hover { transform: translateY(-3px); }
        .unit-card-blue   { border-top: 3px solid #3b82f6; }
        .unit-card-blue:hover   { box-shadow: 0 15px 40px rgba(59,130,246,0.15); border-color: rgba(59,130,246,0.5); border-top-color: #3b82f6; }
        .unit-card-indigo { border-top: 3px solid #6366f1; }
        .unit-card-indigo:hover { box-shadow: 0 15px 40px rgba(99,102,241,0.15); border-color: rgba(99,102,241,0.5); border-top-color: #6366f1; }
        .unit-card-emerald{ border-top: 3px solid #10b981; }
        .unit-card-emerald:hover{ box-shadow: 0 15px 40px rgba(16,185,129,0.15); border-color: rgba(16,185,129,0.5); border-top-color: #10b981; }
        .unit-card-amber  { border-top: 3px solid #f59e0b; }
        .unit-card-amber:hover  { box-shadow: 0 15px 40px rgba(245,158,11,0.15); border-color: rgba(245,158,11,0.5); border-top-color: #f59e0b; }

        /* Radial progress */
        .radial-ring { transform: rotate(-90deg); }
        .radial-text { dominant-baseline: central; text-anchor: middle; }

        /* ── Approvals Table ─────────────────────────── */
        .table-panel {
            background: linear-gradient(135deg, rgba(12,20,40,0.95), rgba(8,14,30,0.98));
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 16px; overflow: hidden;
        }
        .table-header-row {
            background: rgba(255,255,255,0.03);
            border-bottom: 1px solid rgba(255,255,255,0.07);
        }
        .table-header-row th {
            padding: 12px 16px;
            font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.08em;
            color: rgba(148,163,184,0.6);
            font-family: 'JetBrains Mono', monospace;
        }
        .case-row { transition: background 0.15s; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .case-row:hover { background: rgba(59,130,246,0.05); }
        .case-row td { padding: 14px 16px; font-size: 12px; vertical-align: middle; }

        .status-badge {
            display: inline-flex; align-items: center;
            padding: 4px 10px; border-radius: 20px;
            font-size: 10px; font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
        }
        .status-await  { background: rgba(245,158,11,0.12); color: #fcd34d; border: 1px solid rgba(245,158,11,0.25); }
        .status-done   { background: rgba(16,185,129,0.12); color: #34d399; border: 1px solid rgba(16,185,129,0.25); }
        .status-vault  { background: rgba(100,116,139,0.12); color: #94a3b8; border: 1px solid rgba(100,116,139,0.25); }

        .btn-inspect {
            padding: 5px 12px; border-radius: 8px; font-size: 10px; font-weight: 700; cursor: pointer;
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
            color: #94a3b8; transition: all 0.2s; font-family: 'JetBrains Mono', monospace;
        }
        .btn-inspect:hover { background: rgba(255,255,255,0.1); color: #e2e8f0; border-color: rgba(255,255,255,0.2); }

        .btn-approve {
            padding: 5px 12px; border-radius: 8px; font-size: 10px; font-weight: 700; cursor: pointer;
            background: linear-gradient(135deg, rgba(5,150,105,0.3), rgba(16,185,129,0.2));
            border: 1px solid rgba(16,185,129,0.35); color: #34d399;
            transition: all 0.2s; font-family: 'JetBrains Mono', monospace;
        }
        .btn-approve:hover { background: linear-gradient(135deg, rgba(5,150,105,0.5), rgba(16,185,129,0.35)); box-shadow: 0 0 12px rgba(16,185,129,0.3); }

        .btn-transfer {
            padding: 5px 12px; border-radius: 8px; font-size: 10px; font-weight: 700; cursor: pointer;
            background: linear-gradient(135deg, rgba(37,99,235,0.3), rgba(59,130,246,0.2));
            border: 1px solid rgba(59,130,246,0.35); color: #93c5fd;
            transition: all 0.2s; font-family: 'JetBrains Mono', monospace;
        }
        .btn-transfer:hover { background: linear-gradient(135deg, rgba(37,99,235,0.5), rgba(59,130,246,0.35)); box-shadow: 0 0 12px rgba(59,130,246,0.3); }

        /* ── Filter Tabs ─────────────────────────────── */
        .filter-tab {
            padding: 5px 12px; border-radius: 8px; font-size: 11px; font-weight: 600; cursor: pointer;
            background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);
            color: rgba(148,163,184,0.7); transition: all 0.2s;
        }
        .filter-tab:hover { background: rgba(255,255,255,0.08); color: #e2e8f0; }
        .filter-tab.active {
            background: linear-gradient(135deg, rgba(37,99,235,0.3), rgba(59,130,246,0.2));
            border-color: rgba(59,130,246,0.4); color: #93c5fd;
        }

        /* ── Audit Timeline ──────────────────────────── */
        .audit-panel {
            background: linear-gradient(135deg, rgba(12,20,40,0.95), rgba(8,14,30,0.98));
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 16px; overflow: hidden;
        }
        .audit-entry {
            padding: 12px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            display: flex; gap: 12px; align-items: flex-start;
            transition: background 0.15s;
        }
        .audit-entry:hover { background: rgba(255,255,255,0.02); }
        .audit-entry:last-child { border-bottom: none; }

        .audit-dot {
            width: 8px; height: 8px; border-radius: 50%; margin-top: 4px; flex-shrink: 0;
        }
        .dot-blue   { background: #3b82f6; box-shadow: 0 0 8px rgba(59,130,246,0.5); }
        .dot-emerald{ background: #10b981; box-shadow: 0 0 8px rgba(16,185,129,0.5); }
        .dot-amber  { background: #f59e0b; box-shadow: 0 0 8px rgba(245,158,11,0.5); }
        .dot-indigo { background: #6366f1; box-shadow: 0 0 8px rgba(99,102,241,0.5); }

        /* ── Quick Actions ───────────────────────────── */
        .action-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 12px; padding: 14px;
            display: flex; align-items: center; gap: 12px;
            cursor: pointer; transition: all 0.2s;
        }
        .action-card:hover {
            background: rgba(59,130,246,0.08);
            border-color: rgba(59,130,246,0.25);
        }
        .action-icon {
            width: 38px; height: 38px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }

        /* ── Modal ───────────────────────────────────── */
        .modal-overlay {
            position: fixed; inset: 0; z-index: 100;
            background: rgba(0,0,0,0.75);
            backdrop-filter: blur(8px);
            display: flex; align-items: center; justify-content: center; padding: 20px;
        }
        .modal-box {
            background: linear-gradient(135deg, #0e1829, #0a1020);
            border: 1px solid rgba(59,130,246,0.25);
            border-radius: 20px;
            box-shadow: 0 30px 80px rgba(0,0,0,0.7), 0 0 40px rgba(59,130,246,0.1);
            max-width: 520px; width: 100%; padding: 28px;
        }
        .modal-box::before {
            content: ''; display: block; height: 2px; margin-bottom: 0;
            background: linear-gradient(90deg, #3b82f6, #6366f1, #3b82f6);
            border-radius: 2px 2px 0 0; margin: -28px -28px 28px;
        }

        .form-label { font-size: 11px; font-weight: 700; color: rgba(148,163,184,0.7); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block; }
        .form-input {
            width: 100%; padding: 10px 14px; border-radius: 10px;
            background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1);
            color: #e2e8f0; font-size: 12px; font-family: 'JetBrains Mono', monospace;
            outline: none; transition: all 0.2s;
        }
        .form-input:focus { border-color: rgba(59,130,246,0.5); background: rgba(59,130,246,0.06); box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .form-input::placeholder { color: rgba(148,163,184,0.3); }
        .form-input[readonly] { color: #60a5fa; cursor: not-allowed; }

        .form-select {
            width: 100%; padding: 10px 14px; border-radius: 10px;
            background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1);
            color: #e2e8f0; font-size: 12px; outline: none; transition: all 0.2s;
            appearance: none; cursor: pointer;
        }
        .form-select:focus { border-color: rgba(59,130,246,0.5); box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .form-select option { background: #0e1829; color: #e2e8f0; }

        /* ── Pill Tags ───────────────────────────────── */
        .pill {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 8px; border-radius: 6px; font-size: 10px; font-weight: 700;
            font-family: 'JetBrains Mono', monospace; letter-spacing: 0.03em;
        }
        .pill-blue   { background: rgba(59,130,246,0.15); color: #93c5fd; border: 1px solid rgba(59,130,246,0.25); }
        .pill-indigo { background: rgba(99,102,241,0.15); color: #a5b4fc; border: 1px solid rgba(99,102,241,0.25); }
        .pill-emerald{ background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.25); }
        .pill-amber  { background: rgba(245,158,11,0.15); color: #fcd34d; border: 1px solid rgba(245,158,11,0.25); }
        .pill-exec   { background: rgba(37,99,235,0.2); color: #60a5fa; border: 1px solid rgba(59,130,246,0.3); }

        /* ── Animated counter ────────────────────────── */
        @keyframes countUp {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .kpi-value { animation: countUp 0.6s ease forwards; }

        /* ── Section headings ────────────────────────── */
        .section-label {
            font-size: 10px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
            color: rgba(99,102,241,0.8); font-family: 'JetBrains Mono', monospace;
        }

        /* ── Scrollable main ─────────────────────────── */
        .main-scroll { overflow-y: auto; height: calc(100vh - 64px); }

        /* ── Live badge ──────────────────────────────── */
        .live-badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 9px; border-radius: 20px; font-size: 10px; font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
            background: rgba(16,185,129,0.12); color: #34d399; border: 1px solid rgba(16,185,129,0.25);
        }

        /* ── Notification dot ────────────────────────── */
        .notif-dot {
            position: absolute; top: 6px; right: 6px;
            width: 8px; height: 8px; border-radius: 50%;
            background: #f59e0b; border: 2px solid #060A14;
        }

        /* ── Avatar ──────────────────────────────────── */
        .avatar {
            width: 38px; height: 38px; border-radius: 10px;
            background: linear-gradient(135deg, #1d4ed8, #3b82f6);
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 800; color: white;
            box-shadow: 0 0 12px rgba(59,130,246,0.35);
        }

        /* ── Divider line ────────────────────────────── */
        .v-divider { width: 1px; height: 24px; background: rgba(255,255,255,0.08); }

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

        <!-- TOP HEADER ─────────────────────────────── -->
        <header class="top-header h-16 px-6 flex items-center justify-between shrink-0 z-20">

            <!-- Search -->
            <div class="flex items-center gap-4 flex-1 max-w-xl">
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input id="globalSearch" type="text"
                        placeholder="Search cases, unit heads, evidence, suspects…"
                        class="search-input w-full pr-16">
                    <kbd class="absolute right-3 top-1/2 -translate-y-1/2 px-2 py-0.5 text-[9px] font-mono-code font-bold text-slate-500 bg-white/5 rounded border border-white/10">Ctrl K</kbd>
                </div>
            </div>

            <!-- Right Controls -->
            <div class="flex items-center gap-3">
                <!-- Clock -->
                <div class="hidden lg:block text-right">
                    <div class="text-xs font-mono-code font-semibold text-slate-300" id="liveClock"></div>
                    <div class="text-[10px] font-mono-code text-blue-400 font-bold">ACCRA · GMT+0</div>
                </div>

                <div class="v-divider"></div>

                <!-- Notification Bell -->
                <button class="relative p-2 rounded-lg text-slate-500 hover:text-slate-300 hover:bg-white/5 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 01-6 0v-1m6 0H9"/>
                    </svg>
                    <span class="notif-dot"></span>
                </button>

                <div class="v-divider"></div>

                <!-- CTA -->
                <button onclick="toggleIntakeModal()" class="btn-primary">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    Authorize Case File
                </button>
            </div>
        </header>

        <!-- MAIN SCROLLABLE WORKSPACE ───────────────── -->
        <main class="main-scroll p-6 space-y-6">

            <!-- ════ HERO BANNER ═════════════════════════ -->
            <div class="hero-banner p-6 sm:p-8">
                <div class="hero-grid-lines"></div>
                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <span class="pill pill-exec">DEPT HEAD PORTAL</span>
                            <span class="text-slate-600">•</span>
                            <span class="text-xs font-mono-code font-bold text-slate-500">DIRECTORATE COMMAND &amp; OVERSIGHT</span>
                        </div>
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight leading-tight">
                                Forensic Directorate
                                <span class="text-transparent bg-clip-text" style="background-image: linear-gradient(135deg, #60a5fa, #818cf8);">Executive Command</span>
                            </h1>
                            <p class="text-sm text-slate-400 mt-1.5 max-w-xl leading-relaxed">
                                Real-time oversight of all 4 forensic units, pending approvals, vault evidence, and personnel — under directorate authority.
                            </p>
                        </div>
                        <div class="flex items-center gap-4 flex-wrap">
                            <div class="live-badge">
                                <span class="status-dot-live" style="width:6px;height:6px;"></span>
                                SYSTEM ONLINE
                            </div>
                            <span class="text-xs text-slate-500 font-mono-code" id="heroDate"></span>
                            <div class="flex items-center gap-1.5 text-xs text-slate-500">
                                <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                <span>ISO 17025 Compliant</span>
                            </div>
                        </div>
                    </div>

                    <!-- Hero Actions -->
                    <div class="flex flex-col sm:flex-row gap-3 shrink-0">
                        <button onclick="alert('Generating Directorate Quarterly Executive Summary PDF...')"
                            class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-slate-300 hover:text-white transition-all"
                            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
                            <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Export Executive Report
                        </button>
                        <button onclick="toggleIntakeModal()" class="btn-primary">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                            </svg>
                            Authorize Case File
                        </button>
                    </div>
                </div>
            </div>

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
                        <div class="kpi-value kpi-value-blue mb-1">1,428</div>
                        <div class="text-[11px] text-slate-500 font-semibold mb-3">Across All 4 Units <span class="text-red-400">(84 High Priority)</span></div>
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
                        <div class="kpi-value kpi-value-amber mb-1">12</div>
                        <div class="text-[11px] text-slate-500 font-semibold mb-3 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-ping inline-block"></span>
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
                        <div class="kpi-value kpi-value-indigo mb-1">5,892</div>
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
                        <div class="kpi-value kpi-value-emerald mb-1">54</div>
                        <div class="text-[11px] text-slate-500 font-semibold mb-3">Officers • 4 Unit Heads Reporting</div>
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
                        <h2 class="font-black text-white text-base">Unit Heads Oversight</h2>
                    </div>
                    <span class="text-[10px] font-mono-code font-bold text-slate-600">4 SPECIALIZED UNITS ACTIVE</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

                    <!-- UNIT 01 — Crime Scene Support -->
                    <div class="unit-card unit-card-blue">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <span class="pill pill-blue mb-2 inline-flex">UNIT 01</span>
                                <h3 class="font-black text-white text-sm leading-tight">Crime Scene Support</h3>
                                <p class="text-[11px] text-slate-500 mt-0.5">Cmdr. J. Annan</p>
                            </div>
                            <!-- Radial Progress -->
                            <svg width="52" height="52" viewBox="0 0 52 52">
                                <circle cx="26" cy="26" r="21" fill="none" stroke="rgba(59,130,246,0.12)" stroke-width="4"/>
                                <circle cx="26" cy="26" r="21" fill="none" stroke="#3b82f6" stroke-width="4"
                                    stroke-dasharray="132" stroke-dashoffset="33" stroke-linecap="round"
                                    class="radial-ring" style="transform-origin:26px 26px; transform:rotate(-90deg);"/>
                                <text x="26" y="26" class="radial-text" font-size="10" font-weight="800" fill="#93c5fd" font-family="JetBrains Mono">75%</text>
                            </svg>
                        </div>
                        <div class="space-y-2 pt-3 border-t" style="border-color: rgba(255,255,255,0.06);">
                            <div class="flex justify-between text-xs font-mono-code">
                                <span class="text-slate-500">Active Cases</span>
                                <span class="font-bold text-blue-400">142 Cases</span>
                            </div>
                            <div class="flex justify-between text-xs font-mono-code">
                                <span class="text-slate-500">Officers</span>
                                <span class="font-bold text-slate-300">14</span>
                            </div>
                            <div class="w-full bg-white/5 rounded-full h-1.5 mt-1">
                                <div class="h-1.5 rounded-full bg-blue-500" style="width:75%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- UNIT 02 — Evidence Management -->
                    <div class="unit-card unit-card-indigo">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <span class="pill pill-indigo mb-2 inline-flex">UNIT 02</span>
                                <h3 class="font-black text-white text-sm leading-tight">Evidence Management</h3>
                                <p class="text-[11px] text-slate-500 mt-0.5">Dr. E. Osei</p>
                            </div>
                            <svg width="52" height="52" viewBox="0 0 52 52">
                                <circle cx="26" cy="26" r="21" fill="none" stroke="rgba(99,102,241,0.12)" stroke-width="4"/>
                                <circle cx="26" cy="26" r="21" fill="none" stroke="#6366f1" stroke-width="4"
                                    stroke-dasharray="132" stroke-dashoffset="26" stroke-linecap="round"
                                    style="transform-origin:26px 26px; transform:rotate(-90deg);"/>
                                <text x="26" y="26" class="radial-text" font-size="10" font-weight="800" fill="#a5b4fc" font-family="JetBrains Mono">80%</text>
                            </svg>
                        </div>
                        <div class="space-y-2 pt-3 border-t" style="border-color: rgba(255,255,255,0.06);">
                            <div class="flex justify-between text-xs font-mono-code">
                                <span class="text-slate-500">Logged Evidence</span>
                                <span class="font-bold text-indigo-400">215 Items</span>
                            </div>
                            <div class="flex justify-between text-xs font-mono-code">
                                <span class="text-slate-500">Custodians</span>
                                <span class="font-bold text-slate-300">18</span>
                            </div>
                            <div class="w-full bg-white/5 rounded-full h-1.5 mt-1">
                                <div class="h-1.5 rounded-full bg-indigo-500" style="width:80%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- UNIT 03 — LAB Analysis -->
                    <div class="unit-card unit-card-emerald">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <span class="pill pill-emerald mb-2 inline-flex">UNIT 03</span>
                                <h3 class="font-black text-white text-sm leading-tight">LAB Analysis</h3>
                                <p class="text-[11px] text-slate-500 mt-0.5">Dr. K. Appiah</p>
                            </div>
                            <svg width="52" height="52" viewBox="0 0 52 52">
                                <circle cx="26" cy="26" r="21" fill="none" stroke="rgba(16,185,129,0.12)" stroke-width="4"/>
                                <circle cx="26" cy="26" r="21" fill="none" stroke="#10b981" stroke-width="4"
                                    stroke-dasharray="132" stroke-dashoffset="46" stroke-linecap="round"
                                    style="transform-origin:26px 26px; transform:rotate(-90deg);"/>
                                <text x="26" y="26" class="radial-text" font-size="10" font-weight="800" fill="#34d399" font-family="JetBrains Mono">65%</text>
                            </svg>
                        </div>
                        <div class="space-y-2 pt-3 border-t" style="border-color: rgba(255,255,255,0.06);">
                            <div class="flex justify-between text-xs font-mono-code">
                                <span class="text-slate-500">Lab Analysis</span>
                                <span class="font-bold text-emerald-400">98 Tests</span>
                            </div>
                            <div class="flex justify-between text-xs font-mono-code">
                                <span class="text-slate-500">Analysts</span>
                                <span class="font-bold text-slate-300">12</span>
                            </div>
                            <div class="w-full bg-white/5 rounded-full h-1.5 mt-1">
                                <div class="h-1.5 rounded-full bg-emerald-500" style="width:65%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- UNIT 04 — Digital & Physical Forensics -->
                    <div class="unit-card unit-card-amber">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <span class="pill pill-amber mb-2 inline-flex">UNIT 04</span>
                                <h3 class="font-black text-white text-sm leading-tight">Digital &amp; Physical Forensics</h3>
                                <p class="text-[11px] text-slate-500 mt-0.5">Eng. M. Mensah</p>
                            </div>
                            <svg width="52" height="52" viewBox="0 0 52 52">
                                <circle cx="26" cy="26" r="21" fill="none" stroke="rgba(245,158,11,0.12)" stroke-width="4"/>
                                <circle cx="26" cy="26" r="21" fill="none" stroke="#f59e0b" stroke-width="4"
                                    stroke-dasharray="132" stroke-dashoffset="53" stroke-linecap="round"
                                    style="transform-origin:26px 26px; transform:rotate(-90deg);"/>
                                <text x="26" y="26" class="radial-text" font-size="10" font-weight="800" fill="#fcd34d" font-family="JetBrains Mono">60%</text>
                            </svg>
                        </div>
                        <div class="space-y-2 pt-3 border-t" style="border-color: rgba(255,255,255,0.06);">
                            <div class="flex justify-between text-xs font-mono-code">
                                <span class="text-slate-500">Exhibits</span>
                                <span class="font-bold text-amber-400">64 Items</span>
                            </div>
                            <div class="flex justify-between text-xs font-mono-code">
                                <span class="text-slate-500">Experts</span>
                                <span class="font-bold text-slate-300">10</span>
                            </div>
                            <div class="w-full bg-white/5 rounded-full h-1.5 mt-1">
                                <div class="h-1.5 rounded-full bg-amber-500" style="width:60%"></div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ════ APPROVALS TABLE ═════════════════════ -->
            <div id="pendingApprovals" class="table-panel">

                <!-- Table Header -->
                <div class="p-5 border-b flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4" style="border-color: rgba(255,255,255,0.07); background: rgba(255,255,255,0.02);">
                    <div>
                        <div class="section-label mb-1">Case Register</div>
                        <h2 class="font-black text-white text-base">Department Head Approval &amp; Case Register</h2>
                        <p class="text-xs text-slate-500 mt-1">Review pending findings certificates, sign-offs, and inter-unit transfers</p>
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

                            <!-- ROW 1 -->
                            <tr class="case-row dept-case-row" data-unit="Evidence Management">
                                <td>
                                    <div class="font-bold text-blue-400 font-mono-code text-sm">FSD-2026-8842</div>
                                    <div class="text-[10px] text-slate-600 font-mono-code mt-0.5">Submitted: 2026-09-04</div>
                                </td>
                                <td>
                                    <div class="font-semibold text-slate-200 text-xs">Suspected Organic Powder Specimen</div>
                                    <div class="text-[11px] text-slate-500">Sealed Poly Bag (3.40 kg)</div>
                                </td>
                                <td>
                                    <div class="font-bold text-indigo-400 text-xs">Evidence Management</div>
                                    <div class="text-[10px] text-slate-600">Dr. Evelyn Osei</div>
                                </td>
                                <td>
                                    <div class="text-slate-300 text-xs font-semibold">Insp. Eric Ofori</div>
                                    <div class="text-[10px] text-slate-600">Evidence Officer</div>
                                </td>
                                <td>
                                    <span class="status-badge status-await" id="status-FSD-2026-8842">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 mr-1.5 animate-pulse inline-block"></span>
                                        Awaiting Sign-off
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button onclick="viewDetails('FSD-2026-8842')" class="btn-inspect">Inspect</button>
                                        <button onclick="approveCert('FSD-2026-8842')" class="btn-approve">Approve</button>
                                    </div>
                                </td>
                            </tr>

                            <!-- ROW 2 -->
                            <tr class="case-row dept-case-row" data-unit="Digital & Physical Forensics">
                                <td>
                                    <div class="font-bold text-blue-400 font-mono-code text-sm">FSD-2026-8839</div>
                                    <div class="text-[10px] text-slate-600 font-mono-code mt-0.5">Submitted: 2026-09-04</div>
                                </td>
                                <td>
                                    <div class="font-semibold text-slate-200 text-xs">Encrypted NVMe SSD Storage Drive</div>
                                    <div class="text-[11px] text-slate-500">Bit-Stream Image Checksum #DIG-884</div>
                                </td>
                                <td>
                                    <div class="font-bold text-amber-400 text-xs">Digital &amp; Physical Forensics</div>
                                    <div class="text-[10px] text-slate-600">Eng. Michael Mensah</div>
                                </td>
                                <td>
                                    <div class="text-slate-300 text-xs font-semibold">Eng. Michael Mensah</div>
                                    <div class="text-[10px] text-slate-600">Digital Head</div>
                                </td>
                                <td>
                                    <span class="status-badge status-await" id="status-FSD-2026-8839">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 mr-1.5 animate-pulse inline-block"></span>
                                        Awaiting Sign-off
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button onclick="viewDetails('FSD-2026-8839')" class="btn-inspect">Inspect</button>
                                        <button onclick="approveCert('FSD-2026-8839')" class="btn-approve">Approve</button>
                                    </div>
                                </td>
                            </tr>

                            <!-- ROW 3 -->
                            <tr class="case-row dept-case-row" data-unit="Crime Scene Support">
                                <td>
                                    <div class="font-bold text-blue-400 font-mono-code text-sm">FSD-2026-8830</div>
                                    <div class="text-[10px] text-slate-600 font-mono-code mt-0.5">Submitted: 2026-09-03</div>
                                </td>
                                <td>
                                    <div class="font-semibold text-slate-200 text-xs">3D Laser Scene Point Cloud Data</div>
                                    <div class="text-[11px] text-slate-500">Incident Sector 4 Documentation</div>
                                </td>
                                <td>
                                    <div class="font-bold text-blue-400 text-xs">Crime Scene Support</div>
                                    <div class="text-[10px] text-slate-600">{{ $officerName }}</div>
                                </td>
                                <td>
                                    <div class="text-slate-300 text-xs font-semibold">{{ $officerName }}</div>
                                    <div class="text-[10px] text-slate-600">Scene Unit Head</div>
                                </td>
                                <td>
                                    <span class="status-badge status-done">
                                        <svg class="w-3 h-3 mr-1 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Authorized
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button onclick="viewDetails('FSD-2026-8830')" class="btn-inspect">Inspect</button>
                                        <button onclick="alert('Exporting Certified Court Report PDF...')" class="btn-inspect">Export</button>
                                    </div>
                                </td>
                            </tr>

                            <!-- ROW 4 -->
                            <tr class="case-row dept-case-row" data-unit="LAB Analysis">
                                <td>
                                    <div class="font-bold text-blue-400 font-mono-code text-sm">FSD-2026-8825</div>
                                    <div class="text-[10px] text-slate-600 font-mono-code mt-0.5">Submitted: 2026-09-02</div>
                                </td>
                                <td>
                                    <div class="font-semibold text-slate-200 text-xs">Toxicology Spectrometry Sample</div>
                                    <div class="text-[11px] text-slate-500">GC-MS Quantitative Analysis</div>
                                </td>
                                <td>
                                    <div class="font-bold text-emerald-400 text-xs">LAB Analysis</div>
                                    <div class="text-[10px] text-slate-600">Dr. K. Appiah</div>
                                </td>
                                <td>
                                    <div class="text-slate-300 text-xs font-semibold">Dr. K. Appiah</div>
                                    <div class="text-[10px] text-slate-600">Lab Head Analyst</div>
                                </td>
                                <td>
                                    <span class="status-badge status-vault">
                                        Vault Storage B-04
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button onclick="viewDetails('FSD-2026-8825')" class="btn-inspect">Inspect</button>
                                        <button onclick="alert('Inter-unit Transfer Authorized by Department Head.')" class="btn-transfer">Transfer</button>
                                    </div>
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ════ BOTTOM ROW: Audit Log + Quick Actions ═════════════════════ -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 pb-6">

                <!-- Audit Log (2/3 width) -->
                <div class="lg:col-span-2 audit-panel">
                    <div class="p-5 border-b flex items-center justify-between" style="border-color: rgba(255,255,255,0.07);">
                        <div>
                            <div class="section-label mb-1">System Feed</div>
                            <h3 class="font-black text-white text-sm">Directorate Real-time Audit Stream</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Logged actions across all 4 division heads</p>
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
                                    <span class="font-bold text-blue-400 text-xs font-mono-code">FSD-2026-8842</span>
                                    <span class="text-[10px] text-slate-600 font-mono-code shrink-0">10 mins ago</span>
                                </div>
                                <div class="text-xs text-slate-400 mt-0.5">Submitted by <strong class="text-slate-300">Dr. Evelyn Osei (Evidence Mgmt Head)</strong> for Director Approval.</div>
                            </div>
                        </div>
                        <div class="audit-entry">
                            <div class="audit-dot dot-emerald mt-1"></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-bold text-emerald-400 text-xs font-mono-code">FSD-2026-8839</span>
                                    <span class="text-[10px] text-slate-600 font-mono-code shrink-0">35 mins ago</span>
                                </div>
                                <div class="text-xs text-slate-400 mt-0.5">Hardware write-blocker image checksum validated by <strong class="text-slate-300">Eng. Michael Mensah (Digital Head)</strong>.</div>
                            </div>
                        </div>
                        <div class="audit-entry">
                            <div class="audit-dot dot-amber mt-1"></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-bold text-amber-400 text-xs font-mono-code">FSD-2026-8830</span>
                                    <span class="text-[10px] text-slate-600 font-mono-code shrink-0">1 hr ago</span>
                                </div>
                                <div class="text-xs text-slate-400 mt-0.5">3D Point Cloud data certified and <strong class="text-slate-300">authorized by {{ $officerName }} ({{ $officerRole }})</strong>. Court report issued.</div>
                            </div>
                        </div>
                        <div class="audit-entry">
                            <div class="audit-dot dot-indigo mt-1"></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-bold text-indigo-400 text-xs font-mono-code">FSD-2026-8825</span>
                                    <span class="text-[10px] text-slate-600 font-mono-code shrink-0">3 hrs ago</span>
                                </div>
                                <div class="text-xs text-slate-400 mt-0.5">Toxicology GC-MS batch results logged to <strong class="text-slate-300">Vault Storage B-04</strong> by Dr. K. Appiah (Lab Head).</div>
                            </div>
                        </div>
                        <div class="audit-entry">
                            <div class="audit-dot dot-blue mt-1"></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-bold text-blue-400 text-xs font-mono-code">SYSTEM</span>
                                    <span class="text-[10px] text-slate-600 font-mono-code shrink-0">6 hrs ago</span>
                                </div>
                                <div class="text-xs text-slate-400 mt-0.5">Scheduled chain-of-custody integrity audit completed. <strong class="text-slate-300">5,892 vault items verified</strong> — 100% compliant.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions (1/3 width) -->
                <div class="audit-panel p-5 flex flex-col gap-4">
                    <div>
                        <div class="section-label mb-1">Command Controls</div>
                        <h3 class="font-black text-white text-sm">Department Head Actions</h3>
                    </div>

                    <div class="space-y-3">
                        <div class="action-card" onclick="toggleIntakeModal()">
                            <div class="action-icon" style="background: linear-gradient(135deg, rgba(37,99,235,0.3), rgba(59,130,246,0.15)); border: 1px solid rgba(59,130,246,0.3);">
                                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold text-slate-200">Authorize Case File</div>
                                <div class="text-[10px] text-slate-500">Create high-priority directorate case</div>
                            </div>
                            <svg class="w-4 h-4 text-slate-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>

                        <div class="action-card" onclick="batchApprove()">
                            <div class="action-icon" style="background: linear-gradient(135deg, rgba(5,150,105,0.3), rgba(16,185,129,0.15)); border: 1px solid rgba(16,185,129,0.3);">
                                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold text-slate-200">Approve All Pending</div>
                                <div class="text-[10px] text-slate-500">Batch sign-off court documents</div>
                            </div>
                            <svg class="w-4 h-4 text-slate-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>

                        <div class="action-card" onclick="alert('Exporting Directorate Audit Certificate...')">
                            <div class="action-icon" style="background: linear-gradient(135deg, rgba(99,102,241,0.3), rgba(139,92,246,0.15)); border: 1px solid rgba(99,102,241,0.3);">
                                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold text-slate-200">Export Audit Certificate</div>
                                <div class="text-[10px] text-slate-500">Generate ISO 17025 audit report</div>
                            </div>
                            <svg class="w-4 h-4 text-slate-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>

                        <div class="action-card" onclick="alert('Opening Personnel Roster...')">
                            <div class="action-icon" style="background: linear-gradient(135deg, rgba(245,158,11,0.25), rgba(251,191,36,0.1)); border: 1px solid rgba(245,158,11,0.3);">
                                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold text-slate-200">Personnel Roster</div>
                                <div class="text-[10px] text-slate-500">54 officers across 4 units</div>
                            </div>
                            <svg class="w-4 h-4 text-slate-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                <h3 class="text-lg font-black text-white">Authorize High-Priority Case</h3>
            </div>
            <button onclick="toggleIntakeModal()" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-500 hover:text-slate-300 hover:bg-white/5 transition-colors text-xl font-bold">&times;</button>
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
                    class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-slate-200 transition-colors"
                    style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1);">
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
<div id="detailsModal" class="hidden modal-overlay">
    <div class="modal-box">
        <div class="flex items-center justify-between mb-6">
            <div>
                <div class="section-label mb-1">Director Dossier Inspection</div>
                <h3 class="text-lg font-black text-white" id="modalCaseTitle">FSD-2026-8842</h3>
            </div>
            <button onclick="toggleDetailsModal()" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-500 hover:text-slate-300 hover:bg-white/5 transition-colors text-xl font-bold">&times;</button>
        </div>

        <div class="rounded-xl p-5 space-y-3 font-mono-code text-xs" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07);">
            <div class="flex justify-between py-2 border-b" style="border-color: rgba(255,255,255,0.06);">
                <span class="text-slate-500">CHAIN INTEGRITY</span>
                <span class="font-bold text-emerald-400">100% VERIFIED &amp; TAMPER-FREE</span>
            </div>
            <div class="flex justify-between py-2 border-b" style="border-color: rgba(255,255,255,0.06);">
                <span class="text-slate-500">DIVISION</span>
                <span class="text-slate-300">CHEMICAL &amp; EVIDENCE LAB</span>
            </div>
            <div class="flex justify-between py-2 border-b" style="border-color: rgba(255,255,255,0.06);">
                <span class="text-slate-500">UNIT HEAD</span>
                <span class="text-slate-300">DR. EVELYN OSEI</span>
            </div>
            <div class="flex justify-between py-2">
                <span class="text-slate-500">DIRECTORATE HASH</span>
                <span class="text-blue-400">SHA256:8f4b2c19904a…</span>
            </div>
        </div>

        <div class="flex justify-end mt-5">
            <button onclick="toggleDetailsModal()"
                class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-200 transition-all"
                style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
                Close Inspection
            </button>
        </div>
    </div>
</div>


<script>
    const officerName = @json($officerName);
    const officerRole = @json($officerRole);

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
    function approveCert(caseRef) {
        const el = document.getElementById(`status-${caseRef}`);
        if (el) {
            el.className = 'status-badge status-done';
            el.innerHTML = `<svg class="w-3 h-3 mr-1 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Authorized`;
        }
        showToast(`✔ Case ${caseRef} has been officially signed and authorized by ${officerName} (${officerRole}).`);
    }

    /* ── Batch Approve ───────────────────────────── */
    function batchApprove() {
        approveCert('FSD-2026-8842');
        approveCert('FSD-2026-8839');
    }

    /* ── Modal Toggles ───────────────────────────── */
    function toggleIntakeModal()  { document.getElementById('intakeModal').classList.toggle('hidden'); }
    function toggleDetailsModal() { document.getElementById('detailsModal').classList.toggle('hidden'); }

    function viewDetails(ref) {
        document.getElementById('modalCaseTitle').textContent = ref;
        toggleDetailsModal();
    }

    /* ── Submit Intake ───────────────────────────── */
    function submitIntake() {
        const caseRef = document.getElementById('newCaseRef').value;
        const desc    = document.getElementById('newDesc').value;
        const unit    = document.getElementById('newUnit').value;
        const body    = document.getElementById('caseTableBody');

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
                <div class="font-bold text-blue-400 font-mono-code text-sm">${caseRef}</div>
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
                <span class="status-badge status-done" id="status-${caseRef}">
                    <svg class="w-3 h-3 mr-1 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    Authorized
                </span>
            </td>
            <td class="text-right">
                <div class="flex items-center justify-end gap-2">
                    <button onclick="viewDetails('${caseRef}')" class="btn-inspect">Inspect</button>
                    <button onclick="approveCert('${caseRef}')" class="btn-approve">Approve</button>
                </div>
            </td>
        `;
        body.insertBefore(row, body.firstChild);
        toggleIntakeModal();
        showToast(`✔ Case ${caseRef} authorized and added to the register.`);
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
</script>

</body>
</html>
