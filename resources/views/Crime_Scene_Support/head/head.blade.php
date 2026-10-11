@php
    $authUser = auth()->user();
    $userUnit = $authUser->unit ?? session('user_unit') ?? 'Crime Scene Support';
    $unitDisplayTitle = (str_contains(strtolower($userUnit), 'unit') || str_contains(strtolower($userUnit), 'directorate'))
        ? $userUnit 
        : $userUnit . ' Unit';
    $dbUnitHead = \App\Models\User::where('unit', 'LIKE', '%' . $userUnit . '%')->where('is_head', true)->first()
               ?? \App\Models\User::where('is_head', true)->first();
    $headName = $authUser->fullname ?? session('user_name') ?? ($dbUnitHead ? $dbUnitHead->fullname : 'Unit Head');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $unitDisplayTitle }} Command Center | NACOC Forensic Analytics</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    @endif

    <style>
        :root {
            --bg-page: #F8FAFC;
            --bg-card: #FFFFFF;
            --bg-subtle: #F1F5F9;
            --bg-hover: #F8FAFC;
            --border-subtle: #E2E8F0;
            --border-strong: #CBD5E1;
            --accent-emerald: #10B981;
            --accent-cyan: #06B6D4;
            --accent-amber: #F59E0B;
            --accent-purple: #A855F7;
            --text-primary: #0F172A;
            --text-secondary: #64748B;
            --text-muted: #94A3B8;
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-page);
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
        }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }

        .card {
            background: #FFFFFF;
            border: 1px solid var(--border-subtle);
            box-shadow: 0 1px 2px 0 rgba(15, 23, 42, 0.04);
            transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
        }
        .card:hover {
            border-color: var(--border-strong);
            box-shadow: 0 8px 24px -8px rgba(15, 23, 42, 0.12);
        }

        .metric-accent-emerald { border-top: 2px solid var(--accent-emerald); }
        .metric-accent-amber   { border-top: 2px solid var(--accent-amber); }
        .metric-accent-purple  { border-top: 2px solid var(--accent-purple); }
        .metric-accent-cyan    { border-top: 2px solid var(--accent-cyan); }

        .pulse-dot { animation: pulseDot 2s infinite; }
        @keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }

        .scan-line::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent, rgba(16,185,129,0.04), transparent);
            animation: scan 4s linear infinite;
            pointer-events: none;
        }
        @keyframes scan {
            0% { transform: translateY(-100%); }
            100% { transform: translateY(100%); }
        }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #F1F5F9; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94A3B8; }
    </style>
</head>
<body class="min-h-screen flex antialiased selection:bg-emerald-500 selection:text-white">

    <div class="flex h-screen w-full overflow-hidden">

        @include('Crime_Scene_Support.partials.sidebar')

        <div class="flex-1 flex flex-col min-w-0 bg-slate-50 overflow-hidden">

            <!-- TOP BAR -->
            <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 z-20">
                <div class="flex items-center gap-4 flex-1 max-w-2xl">
                    <div class="relative w-full">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input type="text" id="globalSearch"
                            placeholder="Search case ID, officer, GPS coordinate, exhibit tag…"
                            class="w-full pl-10 pr-14 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 font-mono-code placeholder:text-slate-400 focus:outline-none focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/15 transition-all">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-2.5">
                            <kbd class="px-1.5 py-0.5 text-[10px] font-mono-code font-semibold text-slate-400 bg-white rounded border border-slate-200">⌘K</kbd>
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    
                    <div class="flex items-center gap-2.5 pl-3 border-l border-slate-200">
                        <div class="text-right">
                            <div class="text-[11px] font-bold text-slate-900 leading-tight">{{ $headName }}</div>
                            <div class="text-[10px] font-mono-code text-slate-400 leading-tight">UNIT HEAD</div>
                        </div>
                        <div class="w-9 h-9 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center">
                            <span class="text-xs font-bold text-slate-700">{{ strtoupper(substr($headName, 0, 2)) }}</span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- MAIN WORKSPACE -->
            <main class="flex-1 overflow-y-auto bg-slate-50">

                @include('partials.flash')

                <!-- HERO -->
                <div class="px-6 pt-6">
                    <div class="card rounded-xl p-5 flex flex-col lg:flex-row lg:items-center justify-between gap-5 relative overflow-hidden scan-line">
                        <div class="absolute top-0 left-0 w-1 h-full bg-slate-900"></div>
                        <div class="pl-4">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-[10px] font-mono-code font-bold uppercase tracking-widest text-emerald-600">Command Center</span>
                                <span class="text-[10px] font-mono-code text-slate-400">// FORENSIC OPERATIONS</span>
                            </div>
                            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ $unitDisplayTitle }}</h1>
                            <p class="text-xs text-slate-500 mt-1">
                                Head of Unit: <strong class="text-slate-900">{{ $headName }}</strong>
                                <span class="text-slate-300 mx-2">·</span>
                                <span class="font-mono-code text-cyan-600 font-semibold">14 field investigators</span> reporting
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button onclick="toggleDispatchModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold rounded-lg transition-all shadow-sm shadow-emerald-500/20">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                Dispatch Team
                            </button>
                            <button onclick="alert('Exporting Unit Head Field Summary PDF…')" class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-xs font-bold rounded-lg transition-all">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Export Report
                            </button>
                        </div>
                    </div>
                </div>

                <!-- KPI GRID -->
                <div class="px-6 pt-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                    <div class="card metric-accent-emerald rounded-xl p-5">
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-9 h-9 rounded-lg bg-emerald-50 border border-emerald-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <span class="text-[10px] font-mono-code font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100">+12%</span>
                        </div>
                        <div class="text-[10px] font-mono-code font-bold uppercase tracking-wider text-slate-400 mb-1">Active Field Ops</div>
                        <div class="text-2xl font-extrabold text-slate-900 font-mono-code tracking-tight">142</div>
                        <div class="text-[10px] text-slate-500 mt-1.5 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            6 teams dispatched
                        </div>
                    </div>

                    <div class="card metric-accent-amber rounded-xl p-5">
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-9 h-9 rounded-lg bg-amber-50 border border-amber-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <span class="text-[10px] font-mono-code font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-100">Action</span>
                        </div>
                        <div class="text-[10px] font-mono-code font-bold uppercase tracking-wider text-slate-400 mb-1">Pending Sign-offs</div>
                        <div class="text-2xl font-extrabold text-amber-600 font-mono-code tracking-tight">8</div>
                        <div class="text-[10px] text-slate-500 mt-1.5">Awaiting approval</div>
                    </div>

                    <div class="card metric-accent-purple rounded-xl p-5">
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-9 h-9 rounded-lg bg-purple-50 border border-purple-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                            <span class="text-[10px] font-mono-code font-bold text-purple-700 bg-purple-50 px-2 py-0.5 rounded border border-purple-100">3D</span>
                        </div>
                        <div class="text-[10px] font-mono-code font-bold uppercase tracking-wider text-slate-400 mb-1">Point Cloud Scans</div>
                        <div class="text-2xl font-extrabold text-purple-600 font-mono-code tracking-tight">38</div>
                        <div class="text-[10px] text-slate-500 mt-1.5">LiDAR &amp; drone surveys</div>
                    </div>

                    <div class="card metric-accent-cyan rounded-xl p-5">
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-9 h-9 rounded-lg bg-cyan-50 border border-cyan-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </div>
                            <span class="text-[10px] font-mono-code font-bold text-cyan-700 bg-cyan-50 px-2 py-0.5 rounded border border-cyan-100">Ready</span>
                        </div>
                        <div class="text-[10px] font-mono-code font-bold uppercase tracking-wider text-slate-400 mb-1">Field Officers</div>
                        <div class="text-2xl font-extrabold text-cyan-600 font-mono-code tracking-tight">14</div>
                        <div class="text-[10px] text-slate-500 mt-1.5">Ready &amp; dispatched</div>
                    </div>

                </div>

                <!-- ANALYTICS ROW -->
                <div class="px-6 pt-5 grid grid-cols-1 lg:grid-cols-3 gap-4">

                    <div class="lg:col-span-2 card rounded-xl p-5">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-900">Case Throughput</h3>
                                <p class="text-[10px] font-mono-code text-slate-400 mt-0.5">LAST 14 DAYS · SUBMISSIONS VS APPROVALS</p>
                            </div>
                            <div class="flex items-center gap-3 text-[10px] font-mono-code">
                                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-sm bg-emerald-500"></span><span class="text-slate-500">Submissions</span></span>
                                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-sm bg-cyan-500"></span><span class="text-slate-500">Approvals</span></span>
                            </div>
                        </div>
                        <div class="h-56">
                            <canvas id="caseFlowChart"></canvas>
                        </div>
                    </div>

                    <div class="card rounded-xl p-5 flex flex-col">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-900">Live Activity</h3>
                                <p class="text-[10px] font-mono-code text-slate-400 mt-0.5">REAL-TIME FEED</p>
                            </div>
                            <span class="w-2 h-2 rounded-full bg-emerald-500 pulse-dot"></span>
                        </div>
                        <div class="space-y-3 flex-1 overflow-y-auto max-h-56 pr-1">
                            <div class="flex gap-3">
                                <div class="w-7 h-7 rounded-md bg-emerald-50 border border-emerald-100 flex items-center justify-center shrink-0">
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-[11px] text-slate-900 font-semibold truncate">NCC-2026-0908 approved</div>
                                    <div class="text-[10px] text-slate-400 font-mono-code">2m ago · Kotoka Sector B</div>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div class="w-7 h-7 rounded-md bg-purple-50 border border-purple-100 flex items-center justify-center shrink-0">
                                    <svg class="w-3.5 h-3.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-[11px] text-slate-900 font-semibold truncate">LiDAR scan uploaded</div>
                                    <div class="text-[10px] text-slate-400 font-mono-code">14m ago · Tema Terminal 3</div>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div class="w-7 h-7 rounded-md bg-cyan-50 border border-cyan-100 flex items-center justify-center shrink-0">
                                    <svg class="w-3.5 h-3.5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-[11px] text-slate-900 font-semibold truncate">Team dispatched to Aflao</div>
                                    <div class="text-[10px] text-slate-400 font-mono-code">27m ago · Border Sector</div>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div class="w-7 h-7 rounded-md bg-amber-50 border border-amber-100 flex items-center justify-center shrink-0">
                                    <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-[11px] text-slate-900 font-semibold truncate">2 dossiers awaiting sign-off</div>
                                    <div class="text-[10px] text-slate-400 font-mono-code">1h ago · Approval queue</div>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div class="w-7 h-7 rounded-md bg-emerald-50 border border-emerald-100 flex items-center justify-center shrink-0">
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-[11px] text-slate-900 font-semibold truncate">Evidence chain verified</div>
                                    <div class="text-[10px] text-slate-400 font-mono-code">2h ago · Evidence Unit</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- MASTER DISPATCH REGISTER -->
                <div id="sceneRegister" class="px-6 pt-5 pb-8">
                    <div class="card rounded-xl overflow-hidden">

                        <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-extrabold text-slate-900 text-sm">Scene Approvals &amp; Dispatch Oversight</h3>
                                    <span class="text-[10px] font-mono-code font-bold text-emerald-700 bg-emerald-50 border border-emerald-100 px-1.5 py-0.5 rounded">LIVE</span>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-0.5">Review field submissions · authorize 3D reports · initiate evidence transfers</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button onclick="pollUnitHeadCasesTable()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 border border-slate-200 text-slate-600 hover:text-slate-900 rounded-lg text-[11px] font-semibold transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    Refresh
                                </button>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-mono-code text-[10px] uppercase tracking-wider">
                                    <tr>
                                        <th class="py-3 px-5 font-bold">Incident Ref</th>
                                        <th class="py-3 px-5 font-bold">Location &amp; Summary</th>
                                        <th class="py-3 px-5 font-bold">Field Officer</th>
                                        <th class="py-3 px-5 font-bold">3D Dataset</th>
                                        <th class="py-3 px-5 font-bold">Approval Status</th>
                                        <th class="py-3 px-5 font-bold text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 font-medium" id="headTableBody">

                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="py-4 px-5">
                                            <div class="font-bold text-slate-900 font-mono-code text-sm">NCC-2026-0912</div>
                                            <div class="text-[10px] text-slate-400 font-mono-code mt-0.5">2026-09-04 08:30</div>
                                        </td>
                                        <td class="py-4 px-5 max-w-xs">
                                            <div class="font-semibold text-slate-900">Tema Harbour Terminal 3</div>
                                            <div class="text-[11px] text-slate-500 truncate">Latent fingerprint &amp; seal sweep</div>
                                        </td>
                                        <td class="py-4 px-5">
                                            <div class="font-bold text-slate-900">Insp. Samuel Mensah</div>
                                            <div class="text-[10px] text-slate-400">Senior Field Lead</div>
                                        </td>
                                        <td class="py-4 px-5">
                                            <div class="text-purple-600 font-bold font-mono-code">LiDAR-3D #0912</div>
                                            <div class="text-[10px] text-slate-400">14 exhibits bagged</div>
                                        </td>
                                        <td class="py-4 px-5">
                                            <span id="status-NCC-2026-0912" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-100">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                Awaiting Sign-off
                                            </span>
                                        </td>
                                        <td class="py-4 px-5 text-right space-x-1.5 whitespace-nowrap">
                                            <button onclick="viewHeadDetails('NCC-2026-0912')" class="px-2.5 py-1 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 rounded-md text-[10px] font-semibold transition-all">Inspect</button>
                                            <button onclick="approveHeadDossier('NCC-2026-0912')" class="px-2.5 py-1 bg-emerald-500 hover:bg-emerald-600 text-white rounded-md text-[10px] font-bold transition-all">Approve</button>
                                        </td>
                                    </tr>

                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="py-4 px-5">
                                            <div class="font-bold text-slate-900 font-mono-code text-sm">NCC-2026-0908</div>
                                            <div class="text-[10px] text-slate-400 font-mono-code mt-0.5">2026-09-03 14:15</div>
                                        </td>
                                        <td class="py-4 px-5 max-w-xs">
                                            <div class="font-semibold text-slate-900">Kotoka Int. Airport · Sector B</div>
                                            <div class="text-[11px] text-slate-500 truncate">High-value concealment incident</div>
                                        </td>
                                        <td class="py-4 px-5">
                                            <div class="text-slate-900">Sgt. Abena Osei</div>
                                            <div class="text-[10px] text-slate-400">Field Officer</div>
                                        </td>
                                        <td class="py-4 px-5">
                                            <div class="text-purple-600 font-bold font-mono-code">Drone-3D #0908</div>
                                            <div class="text-[10px] text-slate-400">8 exhibits bagged</div>
                                        </td>
                                        <td class="py-4 px-5">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Approved by {{ $headName }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-5 text-right space-x-1.5 whitespace-nowrap">
                                            <button onclick="viewHeadDetails('NCC-2026-0908')" class="px-2.5 py-1 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 rounded-md text-[10px] font-semibold transition-all">Inspect</button>
                                            <button onclick="alert('Transferring exhibits to Evidence Unit…')" class="px-2.5 py-1 bg-cyan-500 hover:bg-cyan-600 text-white rounded-md text-[10px] font-bold transition-all">Transfer</button>
                                        </td>
                                    </tr>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- MODAL: DISPATCH TEAM -->
    <div id="dispatchModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="card rounded-xl max-w-lg w-full p-6 space-y-5 !shadow-2xl">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                <div>
                    <div class="text-[10px] font-mono-code font-bold text-emerald-600 uppercase tracking-widest">Field Authorization</div>
                    <h3 class="text-base font-extrabold text-slate-900">Dispatch {{ $unitDisplayTitle }} Team</h3>
                </div>
                <button onclick="toggleDispatchModal()" class="text-slate-400 hover:text-slate-700 font-bold p-1 text-xl leading-none">&times;</button>
            </div>

            <form onsubmit="event.preventDefault(); submitHeadDispatch();" class="space-y-4 text-xs font-medium">
                <div>
                    <label class="block text-slate-500 font-bold mb-1.5 font-mono-code text-[10px] uppercase tracking-wider">Incident Reference</label>
                    <input type="text" id="headDispatchRef" value="NCC-2026-0920" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg font-mono-code font-bold text-emerald-700 focus:outline-none focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/15">
                </div>

                <div>
                    <label class="block text-slate-500 font-bold mb-1.5 font-mono-code text-[10px] uppercase tracking-wider">Location &amp; Sector</label>
                    <input type="text" id="headDispatchLoc" placeholder="e.g. Tema Port Terminal 2 / Aflao Border" required class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-lg text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/15">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-500 font-bold mb-1.5 font-mono-code text-[10px] uppercase tracking-wider">Assigned Investigator</label>
                        <select id="headDispatchOfficer" class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/15">
                            <option>Insp. Samuel Mensah</option>
                            <option>Sgt. Abena Osei</option>
                            <option>Det. Eric Boateng</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-500 font-bold mb-1.5 font-mono-code text-[10px] uppercase tracking-wider">Authorizing Head</label>
                        <input type="text" value="{{ $headName }}" readonly class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg font-bold text-emerald-700">
                    </div>
                </div>

                <div class="pt-3 flex justify-end gap-2 border-t border-slate-200">
                    <button type="button" onclick="toggleDispatchModal()" class="px-4 py-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-bold rounded-lg transition-all">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white font-bold rounded-lg transition-all shadow-sm shadow-emerald-500/20">Authorize Dispatch</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: 3D DOSSIER -->
    <div id="headDetailsModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="card rounded-xl max-w-lg w-full p-6 space-y-4 !shadow-2xl">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                <div>
                    <div class="text-[10px] font-mono-code font-bold text-purple-600 uppercase tracking-widest">3D LiDAR · Point Cloud Dossier</div>
                    <h3 class="text-base font-extrabold text-slate-900 font-mono-code" id="modalHeadTitle">NCC-2026-0912</h3>
                </div>
                <button onclick="toggleHeadDetailsModal()" class="text-slate-400 hover:text-slate-700 font-bold p-1 text-xl leading-none">&times;</button>
            </div>

            <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 font-mono-code text-[11px] space-y-2.5">
                <div class="flex justify-between"><span class="text-slate-400">GPS COORDINATES</span><span class="font-bold text-cyan-600">5.6037° N, 0.1870° W</span></div>
                <div class="flex justify-between"><span class="text-slate-400">SCAN RESOLUTION</span><span class="text-purple-600 font-bold">0.5mm Point Cloud</span></div>
                <div class="flex justify-between"><span class="text-slate-400">LOGGED EXHIBITS</span><span class="text-emerald-600 font-bold">14 Sealed Bags</span></div>
                <div class="flex justify-between"><span class="text-slate-400">UNIT HEAD</span><span class="text-slate-900">{{ $headName }}</span></div>
            </div>

            <div class="pt-2 flex justify-end border-t border-slate-200">
                <button onclick="toggleHeadDetailsModal()" class="px-4 py-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-bold rounded-lg text-xs transition-all">Close</button>
            </div>
        </div>
    </div>

    <script>
        function toggleDispatchModal() {
            document.getElementById('dispatchModal').classList.toggle('hidden');
        }
        function toggleHeadDetailsModal() {
            document.getElementById('headDetailsModal').classList.toggle('hidden');
        }
        function viewHeadDetails(ref) {
            document.getElementById('modalHeadTitle').innerText = ref;
            toggleHeadDetailsModal();
        }
        function approveHeadDossier(ref) {
            const statusEl = document.getElementById(`status-${ref}`);
            if (statusEl) {
                statusEl.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100';
                statusEl.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Approved by ' + @json($headName);
            }
            alert(`Field Incident Dossier ${ref} approved by Unit Head ${@json($headName)}.`);
        }

        function submitHeadDispatch() {
            const ref = document.getElementById('headDispatchRef').value;
            const loc = document.getElementById('headDispatchLoc').value;
            const officer = document.getElementById('headDispatchOfficer').value;
            const tableBody = document.getElementById('headTableBody');

            const newRow = document.createElement('tr');
            newRow.className = 'hover:bg-slate-50 transition-colors';
            newRow.innerHTML = `
                <td class="py-4 px-5">
                    <div class="font-bold text-slate-900 font-mono-code text-sm">${ref}</div>
                    <div class="text-[10px] text-slate-400 font-mono-code mt-0.5">${new Date().toISOString().slice(0,10)}</div>
                </td>
                <td class="py-4 px-5 max-w-xs">
                    <div class="font-semibold text-slate-900">${loc}</div>
                    <div class="text-[11px] text-slate-500 truncate">Unit Head Authorized Dispatch</div>
                </td>
                <td class="py-4 px-5">
                    <div class="font-bold text-slate-900">${officer}</div>
                    <div class="text-[10px] text-slate-400">Assigned Investigator</div>
                </td>
                <td class="py-4 px-5">
                    <div class="text-purple-600 font-bold font-mono-code">3D LiDAR In-Progress</div>
                    <div class="text-[10px] text-slate-400">Field Scanning</div>
                </td>
                <td class="py-4 px-5">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-bold bg-cyan-50 text-cyan-700 border border-cyan-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span>
                        Dispatch Active
                    </span>
                </td>
                <td class="py-4 px-5 text-right space-x-1.5 whitespace-nowrap">
                    <button onclick="viewHeadDetails('${ref}')" class="px-2.5 py-1 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 rounded-md text-[10px] font-semibold">Inspect</button>
                    <button onclick="approveHeadDossier('${ref}')" class="px-2.5 py-1 bg-emerald-500 hover:bg-emerald-600 text-white rounded-md text-[10px] font-bold">Approve</button>
                </td>
            `;
            tableBody.insertBefore(newRow, tableBody.firstChild);
            toggleDispatchModal();
        }

        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                document.getElementById('globalSearch').focus();
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            const canvas = document.getElementById('caseFlowChart');
            if (!canvas || typeof Chart === 'undefined') return;

            const ctx = canvas.getContext('2d');
            const gradientEmerald = ctx.createLinearGradient(0, 0, 0, 220);
            gradientEmerald.addColorStop(0, 'rgba(16,185,129,0.18)');
            gradientEmerald.addColorStop(1, 'rgba(16,185,129,0)');
            const gradientCyan = ctx.createLinearGradient(0, 0, 0, 220);
            gradientCyan.addColorStop(0, 'rgba(6,182,212,0.15)');
            gradientCyan.addColorStop(1, 'rgba(6,182,212,0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['09-01','09-02','09-03','09-04','09-05','09-06','09-07','09-08','09-09','09-10','09-11','09-12','09-13','09-14'],
                    datasets: [
                        {
                            label: 'Submissions',
                            data: [8, 12, 9, 15, 18, 14, 22, 19, 24, 21, 28, 26, 31, 29],
                            borderColor: '#10B981',
                            backgroundColor: gradientEmerald,
                            fill: true,
                            tension: 0.4,
                            borderWidth: 2,
                            pointRadius: 0,
                            pointHoverRadius: 5,
                            pointHoverBackgroundColor: '#10B981',
                            pointHoverBorderColor: '#FFFFFF',
                            pointHoverBorderWidth: 2
                        },
                        {
                            label: 'Approvals',
                            data: [6, 10, 8, 13, 15, 12, 18, 16, 20, 18, 23, 22, 26, 25],
                            borderColor: '#06B6D4',
                            backgroundColor: gradientCyan,
                            fill: true,
                            tension: 0.4,
                            borderWidth: 2,
                            pointRadius: 0,
                            pointHoverRadius: 5,
                            pointHoverBackgroundColor: '#06B6D4',
                            pointHoverBorderColor: '#FFFFFF',
                            pointHoverBorderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#FFFFFF',
                            borderColor: '#E2E8F0',
                            borderWidth: 1,
                            titleColor: '#0F172A',
                            bodyColor: '#64748B',
                            titleFont: { family: 'JetBrains Mono', size: 11, weight: '600' },
                            bodyFont: { family: 'JetBrains Mono', size: 11 },
                            padding: 10,
                            cornerRadius: 8,
                            displayColors: true,
                            boxPadding: 4
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: 'rgba(226,232,240,0.6)', drawBorder: false },
                            ticks: { color: '#94A3B8', font: { family: 'JetBrains Mono', size: 10 } }
                        },
                        y: {
                            grid: { color: 'rgba(226,232,240,0.6)', drawBorder: false },
                            ticks: { color: '#94A3B8', font: { family: 'JetBrains Mono', size: 10 } },
                            beginAtZero: true
                        }
                    }
                }
            });
        });

        async function pollUnitHeadCasesTable() {
            try {
                const dispatchModal = document.getElementById('dispatchModal');
                const headDetailsModal = document.getElementById('headDetailsModal');
                if ((dispatchModal && !dispatchModal.classList.contains('hidden')) ||
                    (headDetailsModal && !headDetailsModal.classList.contains('hidden'))) {
                    return;
                }
                const res = await fetch('/api/cases', { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const cases = await res.json();
                if (!Array.isArray(cases)) return;

                const tbody = document.getElementById('headTableBody');
                if (!tbody) return;
                const searchQuery = (document.getElementById('globalSearch')?.value || '').toLowerCase().trim();

                let html = '';
                if (cases.length === 0) {
                    html = `<tr><td colspan="6" class="py-12 text-center text-slate-400 font-mono-code text-xs">NO UNIT CASES FOUND IN DATABASE</td></tr>`;
                } else {
                    cases.forEach(c => {
                        const createdDate = c.created_at ? c.created_at.slice(0,16).replace('T',' ') : new Date().toISOString().slice(0,16).replace('T',' ');
                        const isApproved = ['Approved','Authorized','Court Ready','Completed'].includes(c.status);

                        const statusBadge = isApproved
                            ? `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Approved by Unit Head</span>`
                            : `<span id="status-${c.case_number}" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-100"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Awaiting Sign-off</span>`;

                        const actionBtns = isApproved
                            ? `<button onclick="viewHeadDetails('${c.case_number}')" class="px-2.5 py-1 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 rounded-md text-[10px] font-semibold">Inspect</button> <a href="/cases/${c.case_number}/review?from=${encodeURIComponent(@json($userUnit))}" class="px-2.5 py-1 bg-emerald-500 hover:bg-emerald-600 text-white rounded-md text-[10px] font-bold inline-block">Review</a>`
                            : `<button onclick="viewHeadDetails('${c.case_number}')" class="px-2.5 py-1 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 rounded-md text-[10px] font-semibold">Inspect</button> <button onclick="approveHeadDossier('${c.case_number}')" class="px-2.5 py-1 bg-emerald-500 hover:bg-emerald-600 text-white rounded-md text-[10px] font-bold">Approve</button>`;

                        const rowText = `${c.case_number} ${c.title} ${c.location} ${c.submitting_officer}`.toLowerCase();
                        const matchesSearch = !searchQuery || rowText.includes(searchQuery);

                        html += `<tr class="hover:bg-slate-50 transition-colors" style="${matchesSearch ? '' : 'display:none;'}">
                            <td class="py-4 px-5">
                                <div class="font-bold text-slate-900 font-mono-code text-sm">${c.case_number}</div>
                                <div class="text-[10px] text-slate-400 font-mono-code mt-0.5">${createdDate}</div>
                            </td>
                            <td class="py-4 px-5 max-w-xs">
                                <div class="font-semibold text-slate-900">${c.location || c.raid_location || 'Theme Harbour Terminal'}</div>
                                <div class="text-[11px] text-slate-500 truncate">${c.title || 'Field Incident Record'}</div>
                            </td>
                            <td class="py-4 px-5">
                                <div class="font-bold text-slate-900">${c.submitting_officer || c.commanding_officer || 'Field Lead'}</div>
                                <div class="text-[10px] text-slate-400">Senior Field Lead</div>
                            </td>
                            <td class="py-4 px-5">
                                <div class="text-purple-600 font-bold font-mono-code">LiDAR-3D #${c.case_number.slice(-4)}</div>
                                <div class="text-[10px] text-slate-400">${c.exhibits_summary || 'Exhibits Bagged'}</div>
                            </td>
                            <td class="py-4 px-5">${statusBadge}</td>
                            <td class="py-4 px-5 text-right space-x-1.5 whitespace-nowrap">${actionBtns}</td>
                        </tr>`;
                    });
                }
                tbody.innerHTML = html;
            } catch (e) {
                console.warn('Unit head polling failed:', e);
            }
        }
        setInterval(pollUnitHeadCasesTable, 3000);
    </script>
</body>
</html>