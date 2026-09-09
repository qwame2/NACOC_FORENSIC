@php
    $cases = $cases ?? collect();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Case Directory &amp; Docket Registry | Crime Scene Support</title>

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
    </style>
</head>
<body class="min-h-screen flex flex-col bg-white text-slate-900 antialiased selection:bg-emerald-600 selection:text-white">

    <!-- GLOBAL APPARATUS WRAPPER -->
    <div class="flex h-screen overflow-hidden">

        <!-- UNIT HEAD SIDEBAR -->
        @include('Crime_Scene_Support.partials.sidebar')

        <!-- MAIN WORKSPACE CONTENT -->
        <div class="flex-1 flex flex-col min-w-0 bg-white overflow-hidden">

            <!-- TOP WORKSPACE NAVBAR -->
            <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 z-10 shadow-xs">
                
                <!-- TITLE & BADGES -->
                <div class="flex items-center space-x-4">
                    <h1 class="text-sm font-extrabold text-slate-900 tracking-wide font-mono-code uppercase">
                        Case Directory &amp; Field Docket Registry
                    </h1>
                </div>

                <!-- RIGHT HEADER ACTIONS -->
                <div class="flex items-center space-x-4">
                    <div class="text-right hidden sm:block">
                        <div class="text-xs font-mono-code font-bold text-slate-800" id="liveClock">2026-09-08 00:00:00 UTC</div>
                    </div>
                </div>
            </header>

            <!-- DASHBOARD BODY CONTAINER -->
            <main class="flex-1 overflow-y-auto p-6 space-y-6">

                @include('partials.flash')

                <!-- HERO SECTION BANNER -->
                <div class="relative overflow-hidden rounded-2xl p-6 sm:p-8 bg-slate-100 shadow-sm text-slate-900 border border-slate-200">
                    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        <div class="space-y-2">
                            <div class="flex items-center space-x-2">
                                <span class="px-2.5 py-0.5 bg-emerald-600 text-white text-[10px] font-extrabold font-mono-code rounded-md uppercase tracking-wider">FIELD COMMAND</span>
                            </div>
                            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Forensic Case Directory &amp; Docket Registry</h2>
                            <p class="text-xs sm:text-sm font-semibold text-slate-700 max-w-3xl leading-relaxed">
                                Centralized law enforcement repository for logging, searching, and managing narcotic seizures, suspect records, and physical forensic exhibits with full chain-of-custody compliance.
                            </p>
                        </div>

                        <!-- CTA BUTTONS -->
                        <div class="flex items-center gap-3 shrink-0 pt-2 lg:pt-0">
                            <a 
                                href="{{ url('/arrest-person') }}" 
                                class="px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold rounded-xl shadow-md shadow-emerald-600/30 flex items-center space-x-2 transition-all hover:scale-[1.02] cursor-pointer border-none"
                            >
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                <span>+ Add New Case File</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- KPI STAT METRICS CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- CARD 1 -->
                    <div class="glass-panel-white p-5 rounded-2xl transition-all shadow-xs">
                        <div class="flex items-center justify-between text-slate-500 mb-2">
                            <span class="text-xs font-bold text-slate-600 uppercase tracking-wider font-mono-code">Total Registered Cases</span>
                            <div class="w-9 h-9 rounded-xl bg-emerald-100/70 text-emerald-700 flex items-center justify-center font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-slate-900 font-mono-code" id="statTotalCases">{{ count($cases) }}</div>
                        <div class="flex items-center text-[11px] text-emerald-600 mt-2 font-bold">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            +{{ count($cases) }} cases registered in database
                        </div>
                    </div>

                    <!-- CARD 2 -->
                    <div class="glass-panel-white p-5 rounded-2xl transition-all shadow-xs">
                        <div class="flex items-center justify-between text-slate-500 mb-2">
                            <span class="text-xs font-bold text-slate-600 uppercase tracking-wider font-mono-code">Under Lab Analysis</span>
                            <div class="w-9 h-9 rounded-xl bg-amber-100/70 text-amber-700 flex items-center justify-center font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-amber-600 font-mono-code" id="statPendingLab">{{ $cases->whereIn('status', ['Lab Testing', 'Lab Analysis', 'Awaiting Sign-off'])->count() }}</div>
                        <div class="flex items-center text-[11px] text-amber-700 mt-2 font-semibold">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5 inline-block"></span>
                            Priority exhibit test in progress
                        </div>
                    </div>

                    <!-- CARD 3 -->
                    <div class="glass-panel-white p-5 rounded-2xl transition-all shadow-xs">
                        <div class="flex items-center justify-between text-slate-500 mb-2">
                            <span class="text-xs font-bold text-slate-600 uppercase tracking-wider font-mono-code">Pending Approval Cases</span>
                            <div class="w-9 h-9 rounded-xl bg-purple-100/70 text-purple-700 flex items-center justify-center font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-purple-600 font-mono-code" id="statPendingApproval">{{ $cases->whereIn('status', ['Awaiting Sign-off', 'Pending Approval', 'Pending'])->count() }}</div>
                        <div class="flex items-center text-[11px] text-purple-700 mt-2 font-semibold">
                            Awaiting Directorate &amp; Head Approval
                        </div>
                    </div>

                    <!-- CARD 4 -->
                    <div class="glass-panel-white p-5 rounded-2xl transition-all shadow-xs">
                        <div class="flex items-center justify-between text-slate-500 mb-2">
                            <span class="text-xs font-bold text-slate-600 uppercase tracking-wider font-mono-code">Court Certified</span>
                            <div class="w-9 h-9 rounded-xl bg-emerald-100/70 text-emerald-700 flex items-center justify-center font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-emerald-600 font-mono-code" id="statCourtReady">{{ $cases->whereIn('status', ['Approved', 'Court Ready', 'Certified', 'Completed', 'Authorized'])->count() }}</div>
                        <div class="flex items-center text-[11px] text-emerald-700 mt-2 font-semibold">
                            Issued &amp; Certified Dockets
                        </div>
                    </div>
                </div>

                <!-- CASE ROSTER MAIN TABLE PANEL -->
                <div class="bg-white rounded-2xl overflow-hidden shadow-sm border border-slate-200">
                    
                    <!-- TOOLBAR & SEARCH BAR HEADER -->
                    <div class="p-5 bg-slate-50 flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200">
                        
                        <!-- SEARCH INPUT FIELD -->
                        <div class="flex-1 max-w-md relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input 
                                type="text" 
                                id="caseSearchInput" 
                                placeholder="Search Case #, Suspect Name, Offense, Location, Officer..." 
                                oninput="filterCases()"
                                class="w-full pl-10 pr-4 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-600/30 transition-all shadow-2xs placeholder:text-slate-400"
                            >
                        </div>

                        <!-- QUICK FILTER TABS -->
                        <div class="flex items-center gap-2 overflow-x-auto pb-1 md:pb-0">
                            <button onclick="filterStatus('ALL', this)" class="filter-tab-btn status-tab-btn active-tab">
                                All Statuses
                            </button>
                            <button onclick="filterStatus('Active Investigation', this)" class="filter-tab-btn status-tab-btn">
                                Active Ops
                            </button>
                            <button onclick="filterStatus('Lab Analysis', this)" class="filter-tab-btn status-tab-btn">
                                Pending
                            </button>
                            <button onclick="filterStatus('Court Ready', this)" class="filter-tab-btn status-tab-btn">
                                Certified
                            </button>
                        </div>
                    </div>

                    <!-- SEARCH STATS BANNER -->
                    <div class="px-5 py-2.5 bg-slate-100/70 flex items-center justify-between text-xs text-slate-500 font-mono-code border-b border-slate-200">
                        <div>
                            Showing <span id="visibleCaseCount" class="font-extrabold text-slate-900">{{ count($cases) }}</span> of <span id="totalCaseCount" class="font-extrabold text-slate-900">{{ count($cases) }}</span> field case records
                        </div>
                        <div id="activeFilterBadge" class="hidden text-[10px] text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded font-bold">
                            Filtered by search
                        </div>
                    </div>

                    <!-- TABLE CONTENT -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs" id="casesTable">
                            <thead>
                                <tr class="bg-slate-100 border-b border-slate-200 text-slate-700 font-mono-code uppercase text-[11px] tracking-wider select-none">
                                    <th class="py-3.5 px-4 font-extrabold">Case Ref</th>
                                    <th class="py-3.5 px-4 font-extrabold">Case Title</th>
                                    <th class="py-3.5 px-4 font-extrabold">Suspect</th>
                                    <th class="py-3.5 px-4 font-extrabold">Location</th>
                                    <th class="py-3.5 px-4 font-extrabold">Registered Date</th>
                                    <th class="py-3.5 px-4 font-extrabold text-center">Status</th>
                                    <th class="py-3.5 px-4 font-extrabold text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200" id="casesTableBody">
                                @forelse($cases as $case)
                                    @php
                                        $priorityClass = match(strtoupper($case->priority)) {
                                            'CRITICAL' => 'bg-red-100 text-red-700 border-red-200',
                                            'HIGH' => 'bg-amber-100 text-amber-800 border-amber-200',
                                            default => 'bg-slate-200 text-slate-700 border-slate-300'
                                        };

                                        $statusTab = match($case->status) {
                                            'Approved', 'Court Ready', 'Certified', 'Completed', 'Authorized' => 'Court Ready',
                                            'Lab Testing', 'Lab Analysis', 'Under Review' => 'Lab Analysis',
                                            default => 'Active Investigation'
                                        };

                                        $statusBadge = match($case->status) {
                                            'Approved', 'Court Ready', 'Certified', 'Completed', 'Authorized' => [
                                                'bg' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                                'dot' => 'bg-emerald-500',
                                                'label' => $case->status
                                            ],
                                            'Lab Testing', 'Lab Analysis', 'Under Review', 'Awaiting Sign-off' => [
                                                'bg' => 'bg-amber-100 text-amber-800 border-amber-200',
                                                'dot' => 'bg-amber-500',
                                                'label' => $case->status
                                            ],
                                            default => [
                                                'bg' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                                'dot' => 'bg-emerald-600',
                                                'label' => $case->status
                                            ]
                                        };

                                        $suspectDisplayName = $case->suspect_name ?? $case->commanding_officer ?? 'Unassigned Target';
                                        $nameParts = explode(' ', $suspectDisplayName);
                                        $initials = strtoupper(substr($nameParts[0] ?? 'N', 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));

                                        $frontPic = $case->mugshot_front ?? (is_array($case->docket_data) ? ($case->docket_data['mugshot_front'] ?? null) : null);
                                        if ($frontPic && !str_starts_with($frontPic, 'data:') && !str_starts_with($frontPic, 'http://') && !str_starts_with($frontPic, 'https://')) {
                                            $frontPic = asset($frontPic);
                                        }
                                    @endphp
                                    <tr class="table-row-hover transition-colors case-row" data-status="{{ $statusTab }}">
                                        <td class="py-4 px-4 whitespace-nowrap">
                                            <div class="font-bold font-mono-code text-blue-600 text-xs">{{ $case->case_number }}</div>
                                            <div class="flex items-center space-x-1.5 mt-0.5">
                                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold {{ $priorityClass }}">
                                                    {{ strtoupper($case->priority) }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="font-semibold text-slate-800">{{ $case->title }}</div>
                                            <div class="text-[10px] text-blue-600 font-mono-code font-semibold">{{ $case->unit_name ?? 'Forensic Directorate' }}</div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-9 h-9 rounded-full bg-slate-200 border border-slate-300 flex items-center justify-center font-bold text-slate-700 text-xs shrink-0 overflow-hidden shadow-2xs">
                                                    @if(!empty($frontPic))
                                                        <img src="{{ $frontPic }}" alt="{{ $suspectDisplayName }}" class="w-full h-full object-cover">
                                                    @else
                                                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="font-bold text-slate-900">{{ $suspectDisplayName }}</div>
                                                    <div class="text-[10px] text-slate-500 font-mono-code">ALIAS: {{ $case->suspect_alias ?? 'N/A' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="font-medium text-slate-800 flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                                <span>{{ $case->location ?? 'NACOC Central Vault' }}</span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4 whitespace-nowrap">
                                            <div class="text-slate-700 font-mono-code">{{ $case->created_at ? $case->created_at->format('Y-m-d') : date('Y-m-d') }}</div>
                                            <div class="text-[10px] text-slate-400 font-mono-code">{{ $case->created_at ? $case->created_at->format('H:i:s T') : '12:00:00 UTC' }}</div>
                                        </td>
                                        <td class="py-4 px-4 text-center whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold {{ $statusBadge['bg'] }}">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $statusBadge['dot'] }} mr-1.5"></span>
                                                {{ $statusBadge['label'] }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 text-right whitespace-nowrap">
                                            <a href="/cases/{{ $case->case_number }}/review" class="px-3 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 text-xs font-bold rounded-lg border border-emerald-200 transition-all inline-flex items-center gap-1 cursor-pointer">
                                                View Docket
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-8 text-center text-slate-500 font-mono-code text-xs">
                                            No forensic case dockets found in database.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- DOCKET VIEW MODAL -->
    <div id="docketModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-300 shadow-2xl max-w-lg w-full p-6 space-y-4 relative">
            <div class="flex items-start justify-between border-b border-slate-200 pb-3">
                <div>
                    <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 font-mono-code font-bold text-[10px] uppercase">CASE DOCKET LOG</span>
                    <h3 class="text-lg font-black text-slate-900 mt-1" id="docketTitle">Case Docket Details</h3>
                </div>
                <button onclick="closeDocketModal()" class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1">&times;</button>
            </div>

            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2 text-xs">
                <div class="flex justify-between">
                    <span class="text-slate-500 font-bold font-mono-code">DOCKET REF:</span>
                    <span class="font-bold text-blue-600 font-mono-code" id="docketRef">NFC-2026-0892</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500 font-bold font-mono-code">PRIMARY SUSPECT:</span>
                    <span class="font-bold text-slate-900" id="docketSuspect">Kwame Ofori</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500 font-bold font-mono-code">OFFENSE TITLE:</span>
                    <span class="font-bold text-slate-900" id="docketOffense">Cocaine Concealment & Export Attempt</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500 font-bold font-mono-code">CHAIN INTEGRITY:</span>
                    <span class="font-bold text-emerald-600">100% VERIFIED</span>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-2">
                <button onclick="closeDocketModal()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-bold rounded-xl border-none">Close</button>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        let currentStatusFilter = 'ALL';

        function updateClock() {
            const now = new Date();
            const utcString = now.toISOString().replace('T', ' ').substring(0, 19) + ' UTC';
            const clockEl = document.getElementById('liveClock');
            if (clockEl) clockEl.innerText = utcString;
        }
        setInterval(updateClock, 1000);
        updateClock();

        function filterCases() {
            const searchInput = document.getElementById('caseSearchInput').value.toLowerCase().trim();
            const rows = document.querySelectorAll('#casesTableBody .case-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                const status = row.getAttribute('data-status') || '';

                const matchesSearch = text.includes(searchInput);
                const matchesStatus = (currentStatusFilter === 'ALL' || status === currentStatusFilter);

                if (matchesSearch && matchesStatus) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('visibleCaseCount').innerText = visibleCount;
            const badge = document.getElementById('activeFilterBadge');
            if (searchInput || currentStatusFilter !== 'ALL') {
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }

        function filterStatus(statusName, element) {
            currentStatusFilter = statusName;
            document.querySelectorAll('.status-tab-btn').forEach(btn => btn.classList.remove('active-tab'));
            if (element) element.classList.add('active-tab');
            filterCases();
        }

        function viewDocket(ref) {
            window.location.href = '/cases/' + encodeURIComponent(ref) + '/review';
        }

        function closeDocketModal() {
            document.getElementById('docketModal').classList.add('hidden');
        }

        function exportCaseReport() {
            alert('Exporting Field Case Directory PDF report...');
        }

        /* ── 3-Second Real-Time Polling Interval ─────── */
        async function pollMasterCasesTable() {
            try {
                const docketModal = document.getElementById('docketModal');
                if (docketModal && !docketModal.classList.contains('hidden')) return;

                const res = await fetch('/api/cases', { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const cases = await res.json();
                if (!Array.isArray(cases)) return;

                const tbody = document.getElementById('casesTableBody');
                if (!tbody) return;

                const searchInput = (document.getElementById('caseSearchInput')?.value || '').toLowerCase().trim();

                let pendingLabCount = 0;
                let pendingApprovalCount = 0;
                let courtReadyCount = 0;

                let html = '';
                if (cases.length === 0) {
                    html = `<tr><td colspan="7" class="py-8 text-center text-slate-500 font-mono-code text-xs">No forensic case dockets found in database.</td></tr>`;
                } else {
                    cases.forEach(c => {
                        const status = c.status || 'Active Investigation';
                        const isApproved = ['Approved', 'Court Ready', 'Certified', 'Completed', 'Authorized'].includes(status);
                        const isLab = ['Lab Testing', 'Lab Analysis', 'Awaiting Sign-off'].includes(status);
                        const isPending = ['Awaiting Sign-off', 'Pending Approval', 'Pending'].includes(status);

                        if (isLab) pendingLabCount++;
                        if (isPending) pendingApprovalCount++;
                        if (isApproved) courtReadyCount++;

                        const priorityClass = (c.priority || '').toUpperCase() === 'CRITICAL'
                            ? 'bg-red-100 text-red-700 border-red-200'
                            : ((c.priority || '').toUpperCase() === 'HIGH' ? 'bg-amber-100 text-amber-800 border-amber-200' : 'bg-slate-200 text-slate-700 border-slate-300');

                        let statusBadgeHtml = '';
                        if (isApproved) {
                            statusBadgeHtml = `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>${status}</span>`;
                        } else if (isLab) {
                            statusBadgeHtml = `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200"><span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>${status}</span>`;
                        } else {
                            statusBadgeHtml = `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200"><span class="w-1.5 h-1.5 rounded-full bg-emerald-600 mr-1.5"></span>${status}</span>`;
                        }

                        const suspectDisplayName = c.suspect_name || c.commanding_officer || 'Unassigned Target';
                        let frontPic = c.mugshot_front || (c.docket_data ? c.docket_data.mugshot_front : null);
                        let avatarHtml = '';
                        if (frontPic) {
                            if (!frontPic.startsWith('data:') && !frontPic.startsWith('http://') && !frontPic.startsWith('https://')) {
                                if (!frontPic.startsWith('/')) frontPic = '/' + frontPic;
                            }
                            avatarHtml = `<img src="${frontPic}" alt="${suspectDisplayName.replace(/"/g, '&quot;')}" class="w-full h-full object-cover">`;
                        } else {
                            avatarHtml = `<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>`;
                        }
                        const createdDate = c.created_at ? c.created_at.slice(0, 10) : new Date().toISOString().slice(0, 10);
                        const createdTime = c.created_at ? c.created_at.slice(11, 19) + ' UTC' : '12:00:00 UTC';

                        const rowText = `${c.case_number} ${suspectDisplayName} ${c.title} ${c.location} ${c.submitting_officer} ${c.unit_name}`.toLowerCase();
                        const matchesSearch = !searchInput || rowText.includes(searchInput);

                        let statusTab = isApproved ? 'Court Ready' : (isLab ? 'Lab Analysis' : 'Active Investigation');
                        const matchesStatus = (currentStatusFilter === 'ALL' || statusTab === currentStatusFilter);

                        const isVisible = matchesSearch && matchesStatus;

                        html += `<tr class="table-row-hover transition-colors case-row" data-status="${statusTab}" style="${isVisible ? '' : 'display:none;'}">
                            <td class="py-4 px-4 whitespace-nowrap">
                                <div class="font-bold font-mono-code text-blue-600 text-xs">${c.case_number}</div>
                                <div class="flex items-center space-x-1.5 mt-0.5">
                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold ${priorityClass}">${(c.priority || 'HIGH').toUpperCase()}</span>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="font-semibold text-slate-800">${c.title || 'Untitled Case'}</div>
                                <div class="text-[10px] text-blue-600 font-mono-code font-semibold">${c.unit_name || 'Crime Scene Support'}</div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-full bg-slate-200 border border-slate-300 flex items-center justify-center shrink-0 overflow-hidden shadow-2xs">
                                        ${avatarHtml}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900">${suspectDisplayName}</div>
                                        <div class="text-[10px] text-slate-500 font-mono-code">ALIAS: ${c.suspect_alias || (c.docket_data ? c.docket_data.suspect_alias : null) || 'N/A'}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="font-medium text-slate-800 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                    <span>${c.location || 'NACOC Central Vault'}</span>
                                </div>
                            </td>
                            <td class="py-4 px-4 whitespace-nowrap">
                                <div class="text-slate-700 font-mono-code">${createdDate}</div>
                                <div class="text-[10px] text-slate-400 font-mono-code">${createdTime}</div>
                            </td>
                            <td class="py-4 px-4 text-center whitespace-nowrap">${statusBadgeHtml}</td>
                            <td class="py-4 px-4 text-right whitespace-nowrap">
                                <a href="/cases/${c.case_number}/review" class="px-3 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 text-xs font-bold rounded-lg border border-emerald-200 transition-all inline-flex items-center gap-1 cursor-pointer">
                                    View Docket
                                </a>
                            </td>
                        </tr>`;
                    });
                }

                tbody.innerHTML = html;

                // Update counters
                const totalEl = document.getElementById('statTotalCases');
                if (totalEl) totalEl.innerText = cases.length;
                const labEl = document.getElementById('statPendingLab');
                if (labEl) labEl.innerText = pendingLabCount;
                const appEl = document.getElementById('statPendingApproval');
                if (appEl) appEl.innerText = pendingApprovalCount;
                const courtEl = document.getElementById('statCourtReady');
                if (courtEl) courtEl.innerText = courtReadyCount;

                const visCountEl = document.getElementById('visibleCaseCount');
                if (visCountEl && !searchInput && currentStatusFilter === 'ALL') visCountEl.innerText = cases.length;
                const totCountEl = document.getElementById('totalCaseCount');
                if (totCountEl) totCountEl.innerText = cases.length;
            } catch (e) {
                console.warn('Master cases polling failed:', e);
            }
        }
        setInterval(pollMasterCasesTable, 3000);
    </script>
</body>
</html>
