@php
    $cases = $cases ?? collect();

    // ─────────────────────────────────────────────────────────────
    // RESOLVE ACTIVE UNIT CONTEXT (available to whole page)
    // ─────────────────────────────────────────────────────────────
    $reqUnitLanding = request()->query('unit') ?? request()->query('from');
    $userUnitLanding = auth()->user()->unit ?? (session('user_unit') ?? '');

    $resolveUnit = function ($raw) {
        $lower = strtolower((string) $raw);

        if (str_contains($lower, 'crime') || str_contains($lower, 'field')) {
            return [
                'context' => 'Crime Scene Support',
                'badge' => 'CRIME SCENE SUPPORT UNIT',
                'heading' => "Crime Scene Support Unit (CSSU)\nCase Registry & Directory",
            ];
        }
        if (str_contains($lower, 'lab')) {
            return [
                'context' => 'Laboratory Analysis (LAU)',
                'badge' => 'LABORATORY ANALYSIS UNIT',
                'heading' => "Laboratory Analysis Unit (LAU)\nCase Registry & Directory",
            ];
        }
        if (str_contains($lower, 'evidence')) {
            return [
                'context' => 'Evidence Management (EMU)',
                'badge' => 'EVIDENCE MANAGEMENT UNIT',
                'heading' => "Evidence Management Unit (EMU)\nCase Registry & Directory",
            ];
        }
        if (str_contains($lower, 'digital') || str_contains($lower, 'physical')) {
            return [
                'context' => 'Digital & Physical Forensics (DPFU)',
                'badge' => 'DIGITAL & PHYSICAL FORENSICS UNIT',
                'heading' => "Digital & Physical Forensics Unit (DPFU)\nCase Registry & Directory",
            ];
        }

        return [
            'context' => $raw ?: 'Crime Scene Support',
            'badge' => strtoupper($raw ?: 'CRIME SCENE SUPPORT') . ' UNIT',
            'heading' => ($raw ?: 'Crime Scene Support') . "\nCase Registry & Directory",
        ];
    };

    if (!empty($reqUnitLanding)) {
        $rawUnit = $reqUnitLanding;
    } elseif (request()->is('Lab_Analysis*') || request()->is('lab-analysis*') || request()->is('lab-result*')) {
        $rawUnit = 'lab';
    } elseif (request()->is('Crime_Scene_Support*') || request()->is('crime-scene-support*')) {
        $rawUnit = 'crime';
    } elseif (!empty($userUnitLanding)) {
        $rawUnit = $userUnitLanding;
    } else {
        $rawUnit = 'crime';
    }

    $resolved = $resolveUnit($rawUnit);
    $landingUnitContext = $resolved['context'];
    $unitBadge = $resolved['badge'];
    $unitHeading = $resolved['heading'];
    $isLabLanding = str_contains(strtolower($landingUnitContext), 'lab');

    if (str_contains(strtolower($landingUnitContext), 'lab')) {
        $addCaseUrl = url('/Lab_Analysis/create');
    } elseif (str_contains(strtolower($landingUnitContext), 'evidence')) {
        $addCaseUrl = url('/Evidence_Management/create');
    } elseif (
        str_contains(strtolower($landingUnitContext), 'digital') ||
        str_contains(strtolower($landingUnitContext), 'physical')
    ) {
        $addCaseUrl = url('/Digital_Forensics/create');
    } else {
        $addCaseUrl = url('/Crime_Scene_Support/create');
    }
    $addCaseBtnLabel = 'Create New Case';
    $allDatasetCases = $allCases ?? \App\Models\ForensicCase::allCases();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ str_replace("\n", ' — ', $unitHeading) }} | {{ $landingUnitContext }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    @endif
    <script src="{{ asset('js/ajax-table.js') }}"></script>

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
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, .03), 0 2px 4px -1px rgba(0, 0, 0, .02);
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
            transition: all .2s;
        }

        .filter-tab-btn:hover {
            background: #E2E8F0;
            color: #0F172A;
        }

        .filter-tab-btn.active-tab {
            background: #2563EB;
            border-color: #2563EB;
            color: #FFFFFF;
            box-shadow: 0 4px 10px rgba(37, 99, 235, .25);
        }

        .unit-heading {
            white-space: pre-line;
        }
    </style>
</head>

<body
    class="min-h-screen flex flex-col bg-white text-slate-900 antialiased selection:bg-emerald-600 selection:text-white">
    <div class="flex h-screen overflow-hidden">

        @include('Crime_Scene_Support.partials.sidebar')

        <div class="flex-1 flex flex-col min-w-0 bg-white overflow-hidden">

            <!-- TOP WORKSPACE NAVBAR -->
            
            <header
                class="h-16 bg-gradient-to-r from-white via-slate-50 to-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 z-10 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">

                <!-- LEFT: PAGE CONTEXT -->
                <div class="flex items-center space-x-3 min-w-0">
                    <!-- Context Icon -->
                    
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-extrabold font-mono-code uppercase tracking-[0.14em] text-slate-400">
                                Case Management
                            </span>
                            <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                            <span class="text-[10px] font-bold font-mono-code uppercase tracking-[0.14em] text-emerald-700">
                                {{ strtoupper($landingUnitContext) }}
                            </span>
                        </div>
                        <h1 class="text-sm font-extrabold text-slate-900 tracking-tight truncate">
                            Case Directory &amp; Field Docket Registry
                        </h1>
                    </div>
                </div>

                <!-- RIGHT: UTILITY CLUSTER -->
                <div class="flex items-center space-x-3 shrink-0">

                    <!-- Live Clock + Status -->
                    <div class="hidden sm:flex items-center gap-3 px-3 py-1.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                        
                        <div class="text-right leading-tight">
                            
                            <div class="text-[11px] font-mono-code font-bold text-slate-800" id="liveClock">
                                2026-09-08 00:00:00 
                            </div>
                        </div>
                    </div>

                    <!-- Divider -->
                    <span class="hidden sm:block h-8 w-px bg-slate-200"></span>

                    <!-- Quick Actions -->
                    <div class="flex items-center gap-1">
                        <button type="button"
                            onclick="location.reload()"
                            title="Refresh workspace"
                            class="w-9 h-9 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 text-slate-600 hover:text-slate-900 flex items-center justify-center transition-all cursor-pointer shadow-2xs active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </button>

                        <button type="button"
                            title="Notifications"
                            class="relative w-9 h-9 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 text-slate-600 hover:text-slate-900 flex items-center justify-center transition-all cursor-pointer shadow-2xs active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </button>

                        <button type="button"
                            title="Help & Documentation"
                            class="w-9 h-9 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 text-slate-600 hover:text-slate-900 flex items-center justify-center transition-all cursor-pointer shadow-2xs active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </button>
                    </div>

                </div>
            </header>

            <!-- DASHBOARD BODY -->
            <main class="flex-1 overflow-y-auto p-6 space-y-6">

                @include('partials.flash')

                <!-- HERO SECTION BANNER -->
                <div
                    class="relative overflow-hidden rounded-2xl p-6 sm:p-8 bg-white shadow-md shadow-slate-200/60 text-slate-900 border border-slate-200/90">
                    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        <div class="space-y-2">
                            <div class="flex items-center space-x-2">
                                <span
                                    class="px-2.5 py-0.5 bg-white text-emerald-700 border border-slate-200/90 shadow-2xs text-[10px] font-extrabold font-mono-code rounded-md uppercase tracking-wider">
                                    {{ $unitBadge }}
                                </span>
                            </div>
                            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight unit-heading">
                                {{ $unitHeading }}
                            </h2>
                            <p class="text-xs sm:text-sm font-semibold text-slate-700 max-w-3xl leading-relaxed">
                                Centralized law enforcement repository for logging, searching, and managing narcotic
                                seizures, suspect records, and physical forensic exhibits with full chain-of-custody
                                compliance.
                            </p>
                        </div>

                        <!-- CTA BUTTONS -->
                        <div class="flex items-center gap-3 shrink-0 pt-2 lg:pt-0">
                            <a href="{{ $addCaseUrl }}"
                                class="px-5 py-3 bg-emerald-600 hover:bg-emerald-700 shadow-emerald-600/30 text-white text-xs font-extrabold rounded-xl shadow-md flex items-center space-x-2 transition-all hover:scale-[1.02] cursor-pointer border-none">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                <span>{{ $addCaseBtnLabel }}</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- KPI STAT METRICS CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- CARD 1 -->
                    <div class="glass-panel-white p-5 rounded-2xl transition-all shadow-xs">
                        <div class="flex items-center justify-between text-slate-500 mb-2">
                            <span class="text-xs font-bold text-slate-600 uppercase tracking-wider font-mono-code">Total
                                Registered Cases</span>
                            <div
                                class="w-9 h-9 rounded-xl bg-emerald-100/70 text-emerald-700 flex items-center justify-center font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-slate-900 font-mono-code" id="statTotalCases">
                            {{ count($allDatasetCases) }}</div>
                        <div class="flex items-center text-[11px] text-emerald-600 mt-2 font-bold">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                            +{{ count($allDatasetCases) }} cases registered in database
                        </div>
                    </div>

                    <!-- CARD 2 -->
                    <div class="glass-panel-white p-5 rounded-2xl transition-all shadow-xs">
                        <div class="flex items-center justify-between text-slate-500 mb-2">
                            <span class="text-xs font-bold text-slate-600 uppercase tracking-wider font-mono-code">Under
                                Lab Analysis</span>
                            <div
                                class="w-9 h-9 rounded-xl bg-amber-100/70 text-amber-700 flex items-center justify-center font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-amber-600 font-mono-code" id="statPendingLab">
                            {{ $allDatasetCases->whereIn('status', ['Lab Testing', 'Lab Analysis', 'Awaiting Sign-off'])->count() }}
                        </div>
                        <div class="flex items-center text-[11px] text-amber-700 mt-2 font-semibold">
                            Priority exhibit test in progress
                        </div>
                    </div>

                    <!-- CARD 3 -->
                    <div class="glass-panel-white p-5 rounded-2xl transition-all shadow-xs">
                        <div class="flex items-center justify-between text-slate-500 mb-2">
                            <span
                                class="text-xs font-bold text-slate-600 uppercase tracking-wider font-mono-code">Pending
                                Approval Cases</span>
                            <div
                                class="w-9 h-9 rounded-xl bg-emerald-100/70 text-emerald-700 flex items-center justify-center font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-emerald-600 font-mono-code" id="statPendingApproval">
                            {{ $allDatasetCases->whereIn('status', ['Awaiting Sign-off', 'Pending Approval', 'Pending'])->count() }}
                        </div>
                        <div class="flex items-center text-[11px] text-emerald-700 mt-2 font-semibold">
                            Awaiting Directorate &amp; Head Approval
                        </div>
                    </div>

                    <!-- CARD 4 -->
                    <div class="glass-panel-white p-5 rounded-2xl transition-all shadow-xs">
                        <div class="flex items-center justify-between text-slate-500 mb-2">
                            <span class="text-xs font-bold text-slate-600 uppercase tracking-wider font-mono-code">Court
                                Certified</span>
                            <div
                                class="w-9 h-9 rounded-xl bg-emerald-100/70 text-emerald-700 flex items-center justify-center font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-emerald-600 font-mono-code" id="statCourtReady">
                            {{ $allDatasetCases->whereIn('status', ['Approved', 'Court Ready', 'Certified', 'Completed', 'Authorized'])->count() }}
                        </div>
                        <div class="flex items-center text-[11px] text-emerald-700 mt-2 font-semibold">
                            Issued &amp; Certified Dockets
                        </div>
                    </div>
                </div>

                <!-- CASE ROSTER MAIN TABLE PANEL -->
                <div class="bg-white rounded-2xl overflow-hidden shadow-sm border border-slate-200">

                    <!-- TOOLBAR & SEARCH BAR HEADER -->
                    <div
                        class="p-5 bg-slate-50 flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200">
                        <div class="flex-1 max-w-md relative">
                            <div
                                class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" id="caseSearchInput"
                                placeholder="Search Case #, Suspect Name, Offense, Location, Officer..."
                                oninput="filterCases()"
                                class="w-full pl-10 pr-4 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-600/30 transition-all shadow-2xs placeholder:text-slate-400">
                        </div>
                    </div>

                    <!-- SEARCH STATS BANNER -->
                    <div
                        class="px-5 py-2.5 bg-slate-100/70 flex items-center justify-between text-xs text-slate-500 font-mono-code border-b border-slate-200">
                        <div>
                            Showing <span id="visibleCaseCount"
                                class="font-extrabold text-slate-900">{{ count($cases) }}</span>
                            of <span id="totalCaseCount"
                                class="font-extrabold text-slate-900">{{ count($allDatasetCases) }}</span>
                            field case records
                        </div>
                        <div id="activeFilterBadge"
                            class="hidden text-[10px] text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded font-bold">
                            Filtered by search
                        </div>
                    </div>

                    <!-- TABLE CONTENT -->
                    <div id="casesTableContainer">
                        @include('cases.partials.table')
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- DOCKET VIEW MODAL -->
    <div id="docketModal"
        class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-300 shadow-2xl max-w-lg w-full p-6 space-y-4 relative">
            <div class="flex items-start justify-between border-b border-slate-200 pb-3">
                <div>
                    <span
                        class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 font-mono-code font-bold text-[10px] uppercase">CASE
                        DOCKET LOG</span>
                    <h3 class="text-lg font-black text-slate-900 mt-1" id="docketTitle">Case Docket Details</h3>
                </div>
                <button onclick="closeDocketModal()"
                    class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1">&times;</button>
            </div>

            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2 text-xs">
                <div class="flex justify-between">
                    <span class="text-slate-500 font-bold font-mono-code">DOCKET REF:</span>
                    <span class="font-bold text-blue-600 font-mono-code" id="docketRef">—</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500 font-bold font-mono-code">PRIMARY SUSPECT:</span>
                    <span class="font-bold text-slate-900" id="docketSuspect">—</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500 font-bold font-mono-code">OFFENSE TITLE:</span>
                    <span class="font-bold text-slate-900" id="docketOffense">—</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500 font-bold font-mono-code">CHAIN INTEGRITY:</span>
                    <span class="font-bold text-emerald-600">100% VERIFIED</span>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-2">
                <button onclick="closeDocketModal()"
                    class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-bold rounded-xl border-none">Close</button>
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

        // Initialize Reusable AJAX Table Controller for Master Cases
        function setupCasesAjaxTable() {
            if (typeof initAjaxTable !== 'function') return;
            if (window.casesAjaxTable) return;

            window.casesAjaxTable = initAjaxTable({
                container: '#casesTableContainer',
                table: '#casesTable',
                columns: 6,
                rows: 6,
                debounce: 300,
                onAfterUpdate: (container) => {
                    const rows = container.querySelectorAll('.case-row');
                    const visEl = document.getElementById('visibleCaseCount');
                    if (visEl) visEl.innerText = rows.length;
                    
                    const searchInput = (document.getElementById('caseSearchInput')?.value || '').trim();
                    const badge = document.getElementById('activeFilterBadge');
                    if (badge) {
                        if (searchInput || (currentStatusFilter && currentStatusFilter !== 'ALL')) {
                            badge.classList.remove('hidden');
                        } else {
                            badge.classList.add('hidden');
                        }
                    }
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupCasesAjaxTable);
        } else {
            setupCasesAjaxTable();
        }

        let searchDebounceTimer = null;

        function filterCases() {
            const searchInput = document.getElementById('caseSearchInput');
            const searchVal = (searchInput?.value || '').trim();

            if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(() => {
                executeCaseFilter(searchVal);
            }, 300);
        }

        function executeCaseFilter(searchVal) {
            const badge = document.getElementById('activeFilterBadge');
            if (badge) {
                if (searchVal || (currentStatusFilter && currentStatusFilter !== 'ALL')) {
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }

            if (!window.casesAjaxTable && typeof initAjaxTable === 'function') {
                setupCasesAjaxTable();
            }

            if (window.casesAjaxTable) {
                window.casesAjaxTable.applyFilters({
                    search: searchVal || null,
                    status: (currentStatusFilter && currentStatusFilter !== 'ALL') ? currentStatusFilter : null,
                    page: null
                });
            } else {
                const url = new URL(window.location.href);
                if (searchVal) {
                    url.searchParams.set('search', searchVal);
                } else {
                    url.searchParams.delete('search');
                }
                if (currentStatusFilter && currentStatusFilter !== 'ALL') {
                    url.searchParams.set('status', currentStatusFilter);
                } else {
                    url.searchParams.delete('status');
                }
                url.searchParams.delete('page');
                window.location.href = url.toString();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('caseSearchInput');
            if (searchInput) {
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
                        executeCaseFilter((this.value || '').trim());
                    }
                });
            }
        });

        function filterStatus(statusName, element) {
            currentStatusFilter = statusName;
            document.querySelectorAll('.status-tab-btn').forEach(btn => btn.classList.remove('active-tab'));
            if (element) element.classList.add('active-tab');

            const searchVal = (document.getElementById('caseSearchInput')?.value || '').trim();
            if (!window.casesAjaxTable && typeof initAjaxTable === 'function') {
                setupCasesAjaxTable();
            }

            if (window.casesAjaxTable) {
                window.casesAjaxTable.applyFilters({
                    status: statusName === 'ALL' ? null : statusName,
                    search: searchVal || null,
                    page: null
                });
            }
        }

        function goToPage(page) {
            if (window.casesAjaxTable) {
                window.casesAjaxTable.applyFilters({ page: page });
            } else {
                const url = new URL(window.location.href);
                url.searchParams.set('page', page);
                window.location.href = url.toString();
            }
        }

        function changePerPage(val) {
            if (window.casesAjaxTable) {
                window.casesAjaxTable.applyFilters({ per_page: val, page: 1 });
            } else {
                const url = new URL(window.location.href);
                url.searchParams.set('per_page', val);
                url.searchParams.set('page', '1');
                window.location.href = url.toString();
            }
        }

        const isLabLanding = {{ $isLabLanding ? 'true' : 'false' }};
        const landingUnitContext = @json($landingUnitContext);

        function viewDocket(ref) {
            window.location.href = '/cases/' + encodeURIComponent(ref) + '/review?from=' + encodeURIComponent(
                landingUnitContext);
        }

        function closeDocketModal() {
            document.getElementById('docketModal').classList.add('hidden');
        }

        function exportCaseReport() {
            alert('Exporting Field Case Directory PDF report...');
        }

        let isPollingMasterCases = false;
        async function pollMasterCasesTable() {
            if (isPollingMasterCases) return;
            if (document.hidden) return;

            const docketModal = document.getElementById('docketModal');
            const associateModal = document.getElementById('associateModal');
            const activeActionMenu = document.querySelector('.case-action-menu:not(.hidden)');
            if ((docketModal && !docketModal.classList.contains('hidden')) ||
                (associateModal && !associateModal.classList.contains('hidden')) ||
                activeActionMenu) return;

            const searchInput = (document.getElementById('caseSearchInput')?.value || '').trim();
            if (searchInput || (window.casesAjaxTable && window.casesAjaxTable.isLoading)) return;

            isPollingMasterCases = true;
            const controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
            const timeoutId = controller ? setTimeout(() => controller.abort(), 6000) : null;

            try {
                const params = new URLSearchParams(window.location.search);
                if (!params.has('per_page')) {
                    params.set('per_page', '15');
                }
                const apiEndpoint = '{{ url("/api/cases") }}' + (params.toString() ? ('?' + params.toString()) : '');
                const res = await fetch(apiEndpoint, {
                    headers: {
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    signal: controller ? controller.signal : undefined
                });
                if (timeoutId) clearTimeout(timeoutId);
                if (!res.ok) return;
                const rawCasesData = await res.json();
                const cases = Array.isArray(rawCasesData) ? rawCasesData : (rawCasesData.data || []);

                const tbody = document.getElementById('casesTableBody');
                if (!tbody) return;

                const searchInput = (document.getElementById('caseSearchInput')?.value || '').toLowerCase().trim();

                let pendingLabCount = 0;
                let pendingApprovalCount = 0;
                let courtReadyCount = 0;

                let html = '';
                if (cases.length === 0) {
                    html =
                        `<tr><td colspan="6" class="py-12 text-center text-slate-500 font-mono-code text-xs"><div class="flex flex-col items-center justify-center space-y-2"><svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><p class="font-bold text-slate-700">No forensic case dockets found</p></div></td></tr>`;
                } else {
                    cases.forEach(c => {
                        const status = c.status || 'Active Investigation';
                        const isApproved = ['Approved', 'Court Ready', 'Certified', 'Completed', 'Authorized']
                            .includes(status);
                        const isLab = ['Lab Testing', 'Lab Analysis', 'Awaiting Sign-off'].includes(status);
                        const isPending = ['Awaiting Sign-off', 'Pending Approval', 'Pending'].includes(status);

                        if (isLab) pendingLabCount++;
                        if (isPending) pendingApprovalCount++;
                        if (isApproved) courtReadyCount++;

                        const pUpper = (c.priority || 'HIGH').toUpperCase();
                        const priorityClass = pUpper === 'CRITICAL' ?
                            'bg-red-100 text-red-700 border-red-200' :
                            (pUpper === 'HIGH' ?
                                'bg-amber-100 text-amber-800 border-amber-200' :
                                (pUpper === 'LOW' ?
                                    'bg-slate-100 text-slate-700 border-slate-300' :
                                    (pUpper === 'MEDIUM' ?
                                        'bg-blue-100 text-blue-800 border-blue-200' :
                                        (pUpper === 'URGENT' ?
                                            'bg-rose-100 text-rose-800 border-rose-200' :
                                            'bg-slate-200 text-slate-700 border-slate-300'))));

                        const suspectDisplayName = c.suspect_name || (c.docket_data?.full_name ? c.docket_data
                            .full_name : 'Unnamed Suspect');
                        const suspectAlias = c.suspect_alias || (c.docket_data ? c.docket_data.suspect_alias :
                            null) || 'N/A';

                        let frontPic = c.mugshot_front || (c.docket_data?.mugshot_front || c.docket_data
                            ?.mugshots?.front || null);
                        if (!frontPic) {
                            let evPhotos = c.evidence_photos || (c.docket_data ? c.docket_data.evidence_photos :
                                null);
                            if (evPhotos) {
                                let evArr = [];
                                if (Array.isArray(evPhotos)) evArr = evPhotos;
                                else if (typeof evPhotos === 'string') {
                                    try {
                                        evArr = JSON.parse(evPhotos);
                                    } catch (e) {
                                        evArr = [evPhotos];
                                    }
                                }
                                if (evArr && evArr.length > 0 && evArr[0]) frontPic = evArr[0];
                            }
                        }

                        let suspectsList = [];
                        if (Array.isArray(c.suspects_list) && c.suspects_list.length > 0) {
                            suspectsList = c.suspects_list.filter(s => s && s.suspect_name);
                        }
                        if (suspectsList.length === 0) {
                            let dData = c.docket_data;
                            if (typeof dData === 'string') {
                                try {
                                    dData = JSON.parse(dData);
                                } catch (e) {
                                    dData = {};
                                }
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
                            if (sAlias !== 'N/A' && !aliasArray.includes(sAlias)) aliasArray.push(
                                sAlias);
                            let sPic = sItem.mugshot_front || (sIdx === 0 ? frontPic : null);
                            if (sPic && !sPic.startsWith('data:') && !sPic.startsWith('http://') && !
                                sPic.startsWith('https://')) {
                                if (!sPic.startsWith('/')) sPic = '/' + sPic;
                            }
                            const isAssoc = sItem.is_associate || (sItem.association_role && sItem
                                .association_role.toLowerCase() !== 'primary suspect');

                            let imgTag = sPic ?
                                `<img src="${sPic}" alt="${sName.replace(/"/g, '&quot;')}" class="w-full h-full object-cover object-center transition-transform duration-300 group-hover:scale-110">` :
                                `<svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>`;

                            let assocBadge = isAssoc ?
                                `<span class="absolute -top-0.5 left-1/2 -translate-x-1/2 z-30 w-4 h-4 bg-amber-500 border border-white rounded-full flex items-center justify-center text-[8.5px] font-black text-white shadow-xs font-mono-code" title="Associate Suspect">A</span>` :
                                '';

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
                                    <div class="w-10 h-10 rounded-full bg-slate-100 ring-2 ring-gray-300 shadow-sm flex items-center justify-center font-bold text-slate-700 text-xs overflow-hidden group-hover:ring-2 group-hover:ring-blue-500 group-hover:z-40 transition-all duration-200">
                                        ${imgTag}
                                    </div>
                                </div>
                            `;
                        });

                        const aliasStr = aliasArray.length > 0 ? aliasArray.join(', ') : suspectAlias;
                        const stackClass = suspectsList.length > 1 ?
                            '-space-x-3.5 hover:space-x-1 transition-all duration-300' : '';

                        const allSuspectsNamesStr = suspectsList.map(s => s.suspect_name || '').join(' ');
                        const rowText =
                            `${c.case_number} ${suspectDisplayName} ${allSuspectsNamesStr} ${c.title} ${c.location} ${c.submitting_officer} ${c.unit_name}`
                            .toLowerCase();
                        const matchesSearch = !searchInput || rowText.includes(searchInput);

                        let statusTab = isApproved ? 'Court Ready' : (isLab ? 'Lab Analysis' :
                            'Active Investigation');
                        const matchesStatus = (currentStatusFilter === 'ALL' || statusTab ===
                            currentStatusFilter);
                        const isVisible = matchesSearch && matchesStatus;

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

                        html += `<tr class="table-row-hover transition-colors case-row" data-status="${statusTab}" style="${isVisible ? '' : 'display:none;'}">
                            <td class="py-4 px-4 whitespace-nowrap">
                                <div class="font-bold font-mono-code text-blue-600 text-xs">${c.case_number}</div>
                                <div class="flex items-center space-x-1.5 mt-0.5">
                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold ${priorityClass}">${(c.priority || 'HIGH').toUpperCase()}</span>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="font-semibold text-slate-800">${c.title || 'Untitled Case'}</div>
                                <div class="text-[10px] text-blue-600 font-mono-code font-semibold mt-0.5">${c.unit_name || 'Crime Scene Support'}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center space-x-3">
                                    <div class="flex items-center ${stackClass} shrink-0">${avatarsGroupHtml}</div>
                                    <div class="min-w-0">
                                        ${suspectsNamesHtml}
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4 whitespace-nowrap">
                                ${suspectDispHtml}
                            </td>
                            <td class="py-4 px-4 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold border ${cStatCls}">${cStatVal}</span>
                            </td>
                            <td class="py-4 px-4 text-right whitespace-nowrap">
                                <div class="relative inline-block text-left case-action-dropdown-wrapper">
                                    <button type="button"
                                            onclick="toggleCaseActionDropdown(event, '${c.case_number}')"
                                            class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl border border-slate-300 transition-all inline-flex items-center gap-1.5 cursor-pointer shadow-2xs hover:scale-105 active:scale-95"
                                            title="Actions for Case ${c.case_number}">
                                        <svg class="w-4 h-4 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                        </svg>
                                        <span class="text-xs font-mono-code font-bold">Actions</span>
                                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>

                                    <div id="actionMenu-${c.case_number}" class="case-action-menu hidden fixed z-[99999] w-52 bg-white rounded-2xl shadow-2xl border border-slate-200 p-2 space-y-1 text-left font-sans">
                                        <a href="/cases/${c.case_number}/associate?from=${encodeURIComponent(landingUnitContext)}" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-blue-50 hover:text-blue-700 rounded-xl flex items-center space-x-2.5 transition-colors group text-decoration-none">
                                            <div class="w-7 h-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                            </div>
                                            <div>
                                                <div class="font-extrabold leading-tight">Associate Data</div>
                                                <div class="text-[10px] text-slate-400 font-normal mt-0.5">Link accomplices &amp; exhibits</div>
                                            </div>
                                        </a>

                                        <a href="/cases/${c.case_number}/review?edit=true&from=${encodeURIComponent(landingUnitContext)}" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-amber-50 hover:text-amber-700 rounded-xl flex items-center space-x-2.5 transition-colors group text-decoration-none">
                                            <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 group-hover:bg-amber-600 group-hover:text-white transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </div>
                                            <div>
                                                <div class="font-extrabold leading-tight">Edit Docket</div>
                                                <div class="text-[10px] text-slate-400 font-normal mt-0.5">Modify findings &amp; info</div>
                                            </div>
                                        </a>

                                        <a href="/cases/${c.case_number}/review?from=${encodeURIComponent(landingUnitContext)}" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-700 rounded-xl flex items-center space-x-2.5 transition-colors group text-decoration-none">
                                            <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </div>
                                            <div>
                                                <div class="font-extrabold leading-tight">View Docket</div>
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

                const totalCasesCount = (rawCasesData && rawCasesData.total !== undefined) ? rawCasesData.total : cases.length;
                const totalEl = document.getElementById('statTotalCases');
                if (totalEl) totalEl.innerText = totalCasesCount;
                const labEl = document.getElementById('statPendingLab');
                if (labEl) labEl.innerText = pendingLabCount;
                const appEl = document.getElementById('statPendingApproval');
                if (appEl) appEl.innerText = pendingApprovalCount;
                const courtEl = document.getElementById('statCourtReady');
                if (courtEl) courtEl.innerText = courtReadyCount;

                const visCountEl = document.getElementById('visibleCaseCount');
                if (visCountEl && !searchInput && currentStatusFilter === 'ALL') visCountEl.innerText = cases.length;
                const totCountEl = document.getElementById('totalCaseCount');
                if (totCountEl) totCountEl.innerText = totalCasesCount;
            } catch (e) {
                // Background polling failure handled silently
            } finally {
                if (timeoutId) clearTimeout(timeoutId);
                isPollingMasterCases = false;
            }
        }
        setInterval(pollMasterCasesTable, 15000);

        document.addEventListener('mouseover', function(e) {
            const trigger = e.target.closest('.suspect-avatar-trigger');
            const popover = document.getElementById('suspectHoverPreview');
            if (!popover) return;

            if (trigger) {
                const name = trigger.getAttribute('data-name') || 'Unassigned Target';
                const alias = trigger.getAttribute('data-alias') || 'N/A';
                const pic = trigger.getAttribute('data-pic') || '';
                const ref = trigger.getAttribute('data-ref') || '';
                const priority = trigger.getAttribute('data-priority') || 'HIGH';

                const nameEl = document.getElementById('suspectPreviewName');
                const aliasEl = document.getElementById('suspectPreviewAlias');
                const refEl = document.getElementById('suspectPreviewRef');
                const priorityEl = document.getElementById('suspectPreviewPriority');
                const imgEl = document.getElementById('suspectPreviewImg');
                const fallbackEl = document.getElementById('suspectPreviewFallback');

                if (nameEl) nameEl.textContent = name;
                if (aliasEl) aliasEl.textContent = alias;
                if (refEl) refEl.textContent = ref;
                if (priorityEl) priorityEl.textContent = priority;

                if (pic) {
                    imgEl.src = pic;
                    imgEl.classList.remove('hidden');
                    fallbackEl.classList.add('hidden');
                } else {
                    imgEl.src = '';
                    imgEl.classList.add('hidden');
                    fallbackEl.classList.remove('hidden');
                }

                const rect = trigger.getBoundingClientRect();
                const popoverWidth = 240;
                const popoverHeight = 250;

                let left = rect.right + 12;
                let top = rect.top + (rect.height / 2) - (popoverHeight / 2);

                if (top < 10) top = 10;
                if (top + popoverHeight > window.innerHeight - 10) {
                    top = window.innerHeight - popoverHeight - 10;
                }
                if (left + popoverWidth > window.innerWidth - 10) {
                    left = rect.left - popoverWidth - 12;
                }

                popover.style.top = top + 'px';
                popover.style.left = left + 'px';
                popover.classList.remove('hidden');
            }
        });

        document.addEventListener('mouseout', function(e) {
            const trigger = e.target.closest('.suspect-avatar-trigger');
            if (trigger) {
                const popover = document.getElementById('suspectHoverPreview');
                if (popover) {
                    popover.classList.add('hidden');
                }
            }
        });
    </script>

    <!-- GLOBAL FLOATING SUSPECT PREVIEW OVERLAY -->
    <div id="suspectHoverPreview"
        class="fixed z-[99999] hidden pointer-events-none transition-opacity duration-150 ease-out">
        <div
            class="w-60 bg-slate-900/95 backdrop-blur-md text-white rounded-2xl shadow-2xl p-3 border border-slate-700/80 ring-1 ring-white/10">
            <div
                class="relative w-full h-44 rounded-xl overflow-hidden bg-slate-800 border border-slate-700/80 mb-2.5 shadow-inner">
                <img id="suspectPreviewImg" src="" alt=""
                    class="w-full h-full object-cover object-center hidden">
                <div id="suspectPreviewFallback"
                    class="w-full h-full flex flex-col items-center justify-center text-slate-500 bg-slate-800/80">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span class="text-[9px] font-mono-code mt-1 text-slate-400">NO MUGSHOT AVAILABLE</span>
                </div>
                <div
                    class="absolute top-1.5 right-1.5 bg-blue-600/90 text-white text-[8px] font-mono-code px-2 py-0.5 rounded-md uppercase font-bold tracking-wider shadow-xs">
                    SUSPECT PREVIEW
                </div>
            </div>
            <div class="px-0.5 space-y-1">
                <div id="suspectPreviewName" class="font-bold text-white text-xs truncate leading-tight"></div>
                <div class="text-[10px] text-slate-400 font-mono-code truncate">ALIAS: <span id="suspectPreviewAlias"
                        class="text-blue-400 font-semibold"></span></div>
                <div
                    class="text-[9px] text-slate-400 font-mono-code truncate pt-1.5 border-t border-slate-800 mt-1 flex justify-between items-center">
                    <span>REF: <span id="suspectPreviewRef"
                            class="font-mono-code text-slate-300 font-bold"></span></span>
                    <span id="suspectPreviewPriority"
                        class="text-emerald-400 font-bold px-1.5 py-0.5 bg-emerald-950/60 rounded border border-emerald-800/50"></span>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleCaseActionDropdown(event, caseRef) {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            const btn = event ? (event.currentTarget || (event.target ? event.target.closest('button') : null)) : null;
            const targetMenu = document.getElementById('actionMenu-' + caseRef);
            if (!targetMenu) return;

            const isHidden = targetMenu.classList.contains('hidden');

            document.querySelectorAll('.case-action-menu').forEach(menu => {
                menu.classList.add('hidden');
            });

            if (isHidden) {
                targetMenu.classList.remove('hidden');

                const buttonEl = btn || document.querySelector(`button[onclick*="'${caseRef}'"]`);
                if (buttonEl) {
                    const rect = buttonEl.getBoundingClientRect();
                    const menuWidth = 208;
                    const menuHeight = targetMenu.offsetHeight || 160;

                    let top = rect.bottom + 6;
                    let left = rect.right - menuWidth;

                    if (top + menuHeight > window.innerHeight - 10 && rect.top > menuHeight + 10) {
                        top = rect.top - menuHeight - 6;
                    }
                    if (left < 10) left = 10;
                    if (left + menuWidth > window.innerWidth - 10) {
                        left = window.innerWidth - menuWidth - 10;
                    }

                    targetMenu.style.position = 'fixed';
                    targetMenu.style.top = top + 'px';
                    targetMenu.style.left = left + 'px';
                    targetMenu.style.zIndex = '99999';
                }
            }
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.case-action-dropdown-wrapper') && !e.target.closest('.case-action-menu')) {
                document.querySelectorAll('.case-action-menu').forEach(menu => {
                    menu.classList.add('hidden');
                });
            }
        });

        window.addEventListener('scroll', function() {
            document.querySelectorAll('.case-action-menu').forEach(menu => {
                menu.classList.add('hidden');
            });
        }, true);
    </script>
</body>

</html>
