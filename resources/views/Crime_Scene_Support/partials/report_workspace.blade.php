@php
    $authUser = auth()->user();
    $isStaffReport = $isStaffReport ?? false;
    $sidebarUnit = $sidebarUnit ?? 'Crime Scene Support';
    $reportEndpoint = $reportEndpoint ?? url('/Crime_Scene_Support/report');
    $unitDisplayLabel = $unitDisplayLabel ?? 'Crime Scene Support Unit';
    $createCaseUrl = $createCaseUrl ?? ($isStaffReport ? url('/Crime_Scene_Support/staff/create') : url('/Crime_Scene_Support/create'));

    $officerFullname = $authUser ? ($authUser->fullname ?? $authUser->name ?? 'Forensic Officer') : 'Command Officer';
    $officerParts = preg_split('/\s+/', trim((string) $officerFullname)) ?: [];
    $officerInitials = count($officerParts) >= 2
        ? strtoupper(substr($officerParts[0], 0, 1) . substr(end($officerParts), 0, 1))
        : strtoupper(substr((string) $officerFullname, 0, 2));

    $activePct = ($totalCasesCount ?? 0) > 0 ? (int) round((($activeCasesCount ?? 0) / $totalCasesCount) * 100) : 0;
    $highPct = ($totalCasesCount ?? 0) > 0 ? (int) round((($criticalPriorityCount ?? 0) / $totalCasesCount) * 100) : 0;
    $lowPriorityCount = max(0, (int) ($totalCasesCount ?? 0) - (int) ($criticalPriorityCount ?? 0));

    $periodNorm = strtolower(trim((string) ($period ?? 'general')));
    $activePeriodLabel = match (true) {
        in_array($periodNorm, ['weekly', 'week'], true) => 'Weekly Window',
        in_array($periodNorm, ['monthly', 'month'], true) => 'Monthly Window',
        in_array($periodNorm, ['annual', 'annually', 'year', 'yearly'], true) => 'Annual Window',
        default => 'Cumulative Ledger',
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $unitDisplayLabel }} | Field Operations &amp; Case Audit Report | NACOC Forensic</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <script>
        const _origWarn = console.warn;
        console.warn = function(...args) {
            if (args[0] && typeof args[0] === 'string' && args[0].includes('tailwindcss')) return;
            _origWarn.apply(console, args);
        };
    </script>
    <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    <script src="{{ asset('vendor/lucide.min.js') }}"></script>
    <script src="{{ asset('js/ajax-table.js') }}"></script>

    @include('partials.report_design_system')
</head>

<body class="min-h-screen flex antialiased selection:bg-emerald-600 selection:text-white">

    <div class="flex h-screen w-full overflow-hidden">

        @if($isStaffReport)
            @include('staff.partials.sidebar', ['sidebarUnit' => $sidebarUnit])
        @else
            @include('Crime_Scene_Support.partials.sidebar')
        @endif

        <div class="flex-1 flex flex-col min-w-0 bg-[#f3f5f8] overflow-hidden report-print-container">

            <!-- STICKY COMMAND HEADER -->
            <header class="h-16 bg-white/95 backdrop-blur-md border-b border-slate-200/90 px-5 sm:px-6 flex items-center justify-between gap-4 shrink-0 z-20">
                <div class="flex items-center gap-3.5 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-emerald-700 border border-slate-200/90 flex items-center justify-center shrink-0 shadow-2xs">
                        <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-[10px] font-extrabold font-mono-code uppercase tracking-widest text-emerald-700">
                                {{ $unitDisplayLabel }}
                            </span>
                            <span class="text-slate-300">/</span>
                            <span class="text-[10px] font-bold font-mono-code uppercase tracking-wider text-slate-500">
                                {{ $isStaffReport ? 'Staff Audit Workstation' : 'Operational SITREP & Audit' }}
                            </span>
                        </div>
                        <h1 class="text-sm font-extrabold text-slate-900 tracking-tight truncate">
                            Field Operations &amp; Case Audit Report
                        </h1>
                    </div>
                </div>

                <div class="flex items-center gap-3 shrink-0" id="printActionButtons">
                    <div class="hidden xl:flex items-center gap-2.5 pr-3 border-r border-slate-200">
                        <div class="text-right">
                            <div class="text-xs font-bold text-slate-900 leading-tight truncate max-w-[150px]">{{ $officerFullname }}</div>
                            <div class="text-[10px] font-mono-code font-semibold text-slate-500">{{ now()->format('d M Y • H:i') }}</div>
                        </div>
                        <div class="w-8 h-8 rounded-xl bg-slate-100 border border-slate-200/90 text-slate-800 flex items-center justify-center font-mono-code font-extrabold text-[11px]">
                            {{ $officerInitials ?: 'NC' }}
                        </div>
                    </div>

                    <button type="button"
                            id="printExportPdfBtn"
                            onclick="openReportNotesModal()"
                            class="rep-btn-green px-4 py-2.5 rounded-xl text-xs font-extrabold flex items-center gap-2 cursor-pointer">
                        <i data-lucide="printer" class="w-4 h-4"></i>
                        <span>Print / Export Official PDF</span>
                    </button>
                </div>
            </header>

            <!-- PRINT-ONLY OFFICIAL BANNER -->
            <div class="print-only-header p-6 border-b border-slate-300 text-center">
                <div class="text-xs uppercase tracking-widest font-extrabold text-slate-600">Republic of Ghana &bull; Narcotics Control Commission</div>
                <h1 class="text-lg font-black text-slate-900 font-mono-code uppercase mt-1">{{ $unitDisplayLabel }} &bull; Field Operations &amp; Case Audit Report</h1>
                <div class="text-[11px] text-slate-600 mt-1 font-mono-code">
                    Generated: {{ now()->format('d M Y, H:i') }} | Officer: {{ $officerFullname }} | Total Registered Cases: {{ number_format($totalCasesCount ?? 0) }}
                </div>
            </div>

            <!-- SCROLLABLE WORKSPACE -->
            <main class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-5 custom-scrollbar">

                <!-- 1. EXECUTIVE GREY COMMAND MASTHEAD + 5-COLUMN TELEMETRY MATRIX -->
                <section class="rep-masthead" aria-label="Operational Telemetry Overview">
                    <div class="rep-masthead-hero px-6 py-5 sm:px-7 sm:py-6">
                        <div class="rep-masthead-inner flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <div class="space-y-1.5 max-w-3xl">
                                <h2 class="text-lg sm:text-xl font-extrabold tracking-tight text-slate-900">
                                    Crime Scene Support Report
                                </h2>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    Consolidated chain-of-evidence register covering raid operations, search warrants, cargo interceptions, suspect apprehensions, and field seizures.
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                                <div class="px-3.5 py-2 rounded-xl bg-white border border-slate-200/90 shadow-2xs">
                                    <div class="text-[9.5px] font-mono-code uppercase tracking-wider text-slate-500">Active Scope</div>
                                    <div id="mastheadScopeLabel" class="text-xs font-mono-code font-extrabold text-slate-900 mt-0.5">{{ $activePeriodLabel }}</div>
                                </div>
                                <div class="px-3.5 py-2 rounded-xl bg-emerald-50 border border-emerald-200/90 shadow-2xs">
                                    <div class="text-[9.5px] font-mono-code uppercase tracking-wider text-emerald-700">Audit Ledger</div>
                                    <div id="casesCountBadge" class="text-xs font-mono-code font-extrabold text-emerald-800 mt-0.5 tnum">{{ number_format($cases->total()) }} Dockets Filed</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 5-COLUMN PRECISION KPI STRIP -->
                    <div class="rep-kpi-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 divide-y sm:divide-y-0 sm:divide-x divide-slate-200/80">

                        <!-- Tile 1: Total Field Cases -->
                        <div class="rep-kpi-tile p-4 sm:px-5 flex flex-col justify-between">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-extrabold font-mono-code uppercase tracking-wider text-slate-500">Field Cases Logged</span>
                                <span class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 border border-slate-200/80 flex items-center justify-center shrink-0">
                                    <i data-lucide="folder-git-2" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                            <div class="mt-2.5 flex items-baseline gap-2">
                                <span class="text-2xl font-black text-slate-900 font-mono-code tnum">{{ number_format($totalCasesCount ?? 0) }}</span>
                                <span class="text-[11px] text-slate-500 font-semibold">Dockets</span>
                            </div>
                            <div class="mt-2 flex items-center justify-between text-[10.5px]">
                                <span class="text-slate-500 font-medium">Registered field operations</span>
                                <span class="font-mono-code font-bold text-slate-700">100%</span>
                            </div>
                        </div>

                        <!-- Tile 2: Active Investigations -->
                        <div class="rep-kpi-tile p-4 sm:px-5 flex flex-col justify-between">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-extrabold font-mono-code uppercase tracking-wider text-amber-700">Active Investigations</span>
                                <span class="w-7 h-7 rounded-lg bg-amber-50 text-amber-700 border border-amber-200/70 flex items-center justify-center shrink-0">
                                    <i data-lucide="activity" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                            <div class="mt-2.5 flex items-baseline gap-2">
                                <span class="text-2xl font-black text-slate-900 font-mono-code tnum">{{ number_format($activeCasesCount ?? 0) }}</span>
                                <span class="text-[11px] text-amber-700 font-bold font-mono-code">{{ $activePct }}%</span>
                            </div>
                            <div class="mt-2 w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-500 rounded-full" style="width: {{ $activePct }}%;"></div>
                            </div>
                        </div>

                        <!-- Tile 3: Suspects Apprehended -->
                        <div class="rep-kpi-tile p-4 sm:px-5 flex flex-col justify-between">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-extrabold font-mono-code uppercase tracking-wider text-emerald-700">Suspects Apprehended</span>
                                <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200/70 flex items-center justify-center shrink-0">
                                    <i data-lucide="users" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                            <div class="mt-2.5 flex items-baseline gap-2">
                                <span class="text-2xl font-black text-slate-900 font-mono-code tnum">{{ number_format($totalSuspectsCount ?? 0) }}</span>
                                <span class="text-[11px] text-slate-500 font-semibold">Persons</span>
                            </div>
                            <div class="mt-2 text-[10.5px] text-emerald-700 font-semibold">
                                Primary &amp; co-accused subjects
                            </div>
                        </div>

                        <!-- Tile 4: Exhibits & Seizures -->
                        <div class="rep-kpi-tile p-4 sm:px-5 flex flex-col justify-between">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-extrabold font-mono-code uppercase tracking-wider text-indigo-700">Exhibits &amp; Seizures</span>
                                <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200/70 flex items-center justify-center shrink-0">
                                    <i data-lucide="package" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                            <div class="mt-2.5 flex items-baseline gap-2">
                                <span class="text-2xl font-black text-slate-900 font-mono-code tnum">{{ number_format($totalExhibitsCount ?? 0) }}</span>
                                <span class="text-[11px] text-slate-500 font-semibold">Items</span>
                            </div>
                            <div class="mt-2 text-[10.5px] text-indigo-700 font-semibold">
                                Narcotics, arms &amp; seized assets
                            </div>
                        </div>

                        <!-- Tile 5: High Priority -->
                        <button type="button"
                                onclick="quickFilterByPriority('HIGH')"
                                class="rep-kpi-tile p-4 sm:px-5 flex flex-col justify-between text-left group"
                                title="Click to filter High Priority dockets">
                            <div class="flex items-center justify-between gap-2 w-full">
                                <span class="text-[10.5px] font-extrabold font-mono-code uppercase tracking-wider text-rose-700">High Priority</span>
                                <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200/80 flex items-center justify-center shrink-0 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                                    <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                            <div class="mt-2.5 flex items-baseline gap-2">
                                <span class="text-2xl font-black text-rose-700 font-mono-code tnum">{{ number_format($criticalPriorityCount ?? 0) }}</span>
                                <span class="text-[11px] text-slate-500 font-semibold">High ({{ $highPct }}%)</span>
                            </div>
                            <div class="mt-2 flex items-center justify-between text-[10.5px] w-full">
                                <span class="text-slate-500 font-mono-code">Low: <strong class="text-slate-700">{{ number_format($lowPriorityCount) }}</strong></span>
                                <span class="text-emerald-700 font-bold group-hover:underline">Filter High &rarr;</span>
                            </div>
                        </button>

                    </div>
                </section>

                <!-- 2. INSTRUMENT-GRADE AUDIT FILTER & QUERY CONSOLE -->
                <section class="rep-surface-card p-5 sm:px-6 space-y-4" id="reportFilterConsole" aria-label="Audit Filter Console">
                    <form method="GET" action="{{ $reportEndpoint }}" id="reportFilterForm" class="space-y-4">
                        <input type="hidden" name="period" id="periodInput" value="{{ $period }}">

                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3.5 border-b border-slate-100">
                            <div class="flex items-center gap-2.5">
                                <span class="w-7 h-7 rounded-lg bg-emerald-50 border border-emerald-200/70 text-emerald-700 flex items-center justify-center shrink-0">
                                    <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i>
                                </span>
                                <div>
                                    <h2 class="text-xs font-extrabold text-slate-900 font-mono-code uppercase tracking-wider">
                                        Audit Query &amp; Timeframe Parameters
                                    </h2>
                                    <p class="text-[11px] text-slate-500">
                                        Refine dockets by case reference, scene location, operation type, priority level, or date window.
                                    </p>
                                </div>
                            </div>

                            <!-- SEGMENTED PERIOD SWITCHER (GREEN BUTTONS) -->
                            <div class="rep-segmented font-mono-code text-xs font-bold shrink-0 self-start md:self-auto">
                                <button type="button" onclick="setReportPeriod('weekly')" class="console-period-btn px-3 py-1.5 rounded-lg transition-all {{ in_array($periodNorm, ['weekly', 'week'], true) ? 'rep-btn-green' : 'rep-btn-green-soft' }}" data-period="weekly">
                                    Weekly
                                </button>
                                <button type="button" onclick="setReportPeriod('monthly')" class="console-period-btn px-3 py-1.5 rounded-lg transition-all {{ in_array($periodNorm, ['monthly', 'month'], true) ? 'rep-btn-green' : 'rep-btn-green-soft' }}" data-period="monthly">
                                    Monthly
                                </button>
                                <button type="button" onclick="setReportPeriod('annual')" class="console-period-btn px-3 py-1.5 rounded-lg transition-all {{ in_array($periodNorm, ['annual', 'annually', 'year', 'yearly'], true) ? 'rep-btn-green' : 'rep-btn-green-soft' }}" data-period="annual">
                                    Annual
                                </button>
                                <button type="button" onclick="setReportPeriod('general')" class="console-period-btn px-3 py-1.5 rounded-lg transition-all {{ (in_array($periodNorm, ['all', 'general'], true) || empty($period)) ? 'rep-btn-green' : 'rep-btn-green-soft' }}" data-period="general">
                                    General
                                </button>
                            </div>
                        </div>

                        <!-- PRIMARY FILTER GRID -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">

                            <!-- Search -->
                            <div class="lg:col-span-4 space-y-1.5">
                                <label for="searchInput" class="block text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider">
                                    Search Case / Suspect / Officer / Scene
                                </label>
                                <div class="relative flex items-center">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 pointer-events-none">
                                        <i data-lucide="search" class="w-3.5 h-3.5"></i>
                                    </span>
                                    <input type="text"
                                           name="search"
                                           id="searchInput"
                                           value="{{ $search }}"
                                           placeholder="Case ref, suspect, arresting officer, scene..."
                                           class="rep-input w-full pl-9 pr-3 py-2 text-xs font-semibold">
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="lg:col-span-2 space-y-1.5">
                                <label for="statusSelect" class="block text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider">
                                    Case Status
                                </label>
                                <select name="status" id="statusSelect" class="rep-select w-full px-3 py-2 text-xs font-semibold">
                                    <option value="ALL" {{ (empty($status) || strtoupper($status) === 'ALL') ? 'selected' : '' }}>All Statuses</option>
                                    <option value="Active Investigation" {{ strcasecmp($status ?? '', 'Active Investigation') === 0 ? 'selected' : '' }}>Active Investigation</option>
                                    <option value="Under Multi-Unit Processing" {{ strcasecmp($status ?? '', 'Under Multi-Unit Processing') === 0 ? 'selected' : '' }}>Under Multi-Unit Processing</option>
                                    <option value="Approved" {{ strcasecmp($status ?? '', 'Approved') === 0 ? 'selected' : '' }}>Approved</option>
                                    <option value="Draft" {{ strcasecmp($status ?? '', 'Draft') === 0 ? 'selected' : '' }}>Draft</option>
                                    <option value="Court Ready" {{ strcasecmp($status ?? '', 'Court Ready') === 0 ? 'selected' : '' }}>Court Ready</option>
                                </select>
                            </div>

                            <!-- Operation Type -->
                            <div class="lg:col-span-2 space-y-1.5">
                                <label for="operationTypeSelect" class="block text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider">
                                    Operation Type
                                </label>
                                <select name="operation_type" id="operationTypeSelect" class="rep-select w-full px-3 py-2 text-xs font-semibold">
                                    <option value="ALL" {{ (empty($operationType) || strtoupper($operationType) === 'ALL') ? 'selected' : '' }}>All Operations</option>
                                    <option value="Raid" {{ strcasecmp($operationType ?? '', 'Raid') === 0 ? 'selected' : '' }}>Raid Operation</option>
                                    <option value="Search Warrant" {{ strcasecmp($operationType ?? '', 'Search Warrant') === 0 ? 'selected' : '' }}>Search Warrant</option>
                                    <option value="Cargo" {{ strcasecmp($operationType ?? '', 'Cargo') === 0 ? 'selected' : '' }}>Cargo / Parcel Interception</option>
                                    <option value="Intel" {{ strcasecmp($operationType ?? '', 'Intel') === 0 ? 'selected' : '' }}>Intel-Led Operation</option>
                                    <option value="Arrest" {{ strcasecmp($operationType ?? '', 'Arrest') === 0 ? 'selected' : '' }}>Arrest &amp; Apprehension</option>
                                    <option value="Transferred" {{ strcasecmp($operationType ?? '', 'Transferred') === 0 ? 'selected' : '' }}>Transferred Case</option>
                                </select>
                            </div>

                            <!-- Priority (Strictly High & Low) -->
                            <div class="lg:col-span-2 space-y-1.5">
                                <label for="prioritySelect" class="block text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider">
                                    Priority Level
                                </label>
                                <select name="priority" id="prioritySelect" class="rep-select w-full px-3 py-2 text-xs font-semibold">
                                    <option value="ALL" {{ (empty($priority) || strtoupper($priority) === 'ALL') ? 'selected' : '' }}>All Priorities</option>
                                    <option value="HIGH" {{ strtoupper($priority ?? '') === 'HIGH' ? 'selected' : '' }}>High</option>
                                    <option value="LOW" {{ strtoupper($priority ?? '') === 'LOW' ? 'selected' : '' }}>Low</option>
                                </select>
                            </div>

                            <!-- Date From & To -->
                            <div class="lg:col-span-2 grid grid-cols-2 gap-2">
                                <div class="space-y-1.5">
                                    <label for="fromDate" class="block text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider">
                                        From
                                    </label>
                                    <input type="date"
                                           name="from_date"
                                           id="fromDate"
                                           value="{{ $fromDate }}"
                                           class="rep-input w-full px-2 py-2 text-[11px] font-mono-code font-semibold">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="toDate" class="block text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider">
                                        To
                                    </label>
                                    <input type="date"
                                           name="to_date"
                                           id="toDate"
                                           value="{{ $toDate }}"
                                           class="rep-input w-full px-2 py-2 text-[11px] font-mono-code font-semibold">
                                </div>
                            </div>

                        </div>

                        <!-- SECONDARY PRECISION TOOLBAR -->
                        <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-100 text-xs">
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10.5px] font-extrabold text-slate-500 font-mono-code uppercase">Sort</span>
                                    <select name="sort" class="rep-select px-2.5 py-1.5 text-xs font-semibold">
                                        <option value="newest" {{ ($sort ?? 'newest') === 'newest' ? 'selected' : '' }}>Newest Incident First</option>
                                        <option value="oldest" {{ ($sort ?? '') === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                                        <option value="case_asc" {{ ($sort ?? '') === 'case_asc' ? 'selected' : '' }}>Case Number (A–Z)</option>
                                        <option value="case_desc" {{ ($sort ?? '') === 'case_desc' ? 'selected' : '' }}>Case Number (Z–A)</option>
                                        <option value="priority" {{ ($sort ?? '') === 'priority' ? 'selected' : '' }}>Priority (High First)</option>
                                    </select>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span class="text-[10.5px] font-extrabold text-slate-500 font-mono-code uppercase">Rows</span>
                                    <select name="per_page" class="rep-select px-2.5 py-1.5 text-xs font-semibold">
                                        <option value="15" {{ request('per_page', 20) == 15 ? 'selected' : '' }}>15 / page</option>
                                        <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20 / page</option>
                                        <option value="50" {{ request('per_page', 20) == 50 ? 'selected' : '' }}>50 / page</option>
                                        <option value="100" {{ request('per_page', 20) == 100 ? 'selected' : '' }}>100 / page</option>
                                    </select>
                                </div>

                                <a href="{{ $reportEndpoint }}"
                                   onclick="resetReportFilters(event)"
                                   class="rep-btn-green px-3 py-1.5 rounded-lg text-[11px] font-bold font-mono-code uppercase tracking-wider inline-flex items-center gap-1.5"
                                   title="Clear all active filters">
                                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                    <span>Reset</span>
                                </a>
                            </div>

                            <div class="text-[11px] font-mono-code font-semibold text-slate-500 tnum" id="filteredCountInfo">
                                Showing {{ $cases->firstItem() ?? 0 }}–{{ $cases->lastItem() ?? 0 }} of {{ number_format($totalFilteredCount ?? $cases->total()) }} filtered cases (Cumulative: {{ number_format($totalCasesCount ?? 0) }})
                            </div>
                        </div>
                    </form>
                </section>

                <!-- 3. FORENSIC FIELD CASES LEDGER TABLE -->
                <section class="rep-surface-card overflow-hidden" aria-label="Field Cases Ledger">
                    <div class="px-6 py-4 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/70">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-white border border-slate-200/90 text-emerald-700 flex items-center justify-center shrink-0 shadow-2xs">
                                <i data-lucide="clipboard-list" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 tracking-tight">
                                    Field Operations &amp; Case Register Ledger
                                </h3>
                                <p class="text-[11px] text-slate-500">
                                    Verified forensic case dockets, scene coordinates, apprehended subjects, and seized exhibits.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 self-start sm:self-auto">
                            <button type="button"
                                    onclick="openReportNotesModal()"
                                    class="no-print rep-btn-green inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-[11px] font-mono-code font-bold cursor-pointer">
                                <i data-lucide="file-down" class="w-3.5 h-3.5"></i>
                                <span>Export Filtered PDF</span>
                            </button>
                        </div>
                    </div>

                    <div id="fieldCasesTableContainer">
                        @include('Crime_Scene_Support.partials.report_table', ['createCaseUrl' => $createCaseUrl])
                    </div>
                </section>

            </main>
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- OFFICIAL REPORT BRIEFING NOTES MODAL (PRE-PDF GENERATION)  -->
    <!-- ========================================================== -->
    <div id="reportNotesModal" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5 overflow-y-auto no-print" role="dialog" aria-modal="true" aria-labelledby="reportNotesModalTitle">
        <div class="bg-white rounded-3xl max-w-4xl w-full border border-slate-200 shadow-2xl overflow-hidden flex flex-col my-auto max-h-[93vh]">

            <!-- Grey Executive Modal Header -->
            <div class="rep-masthead-hero px-6 py-5 flex items-center justify-between shrink-0">
                <div class="rep-masthead-inner flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-white text-emerald-700 border border-slate-200/90 flex items-center justify-center shrink-0 shadow-2xs">
                        <i data-lucide="file-signature" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 id="reportNotesModalTitle" class="text-sm sm:text-base font-extrabold tracking-wide uppercase font-mono-code text-slate-900">
                                Official SITREP Briefing Notes
                            </h2>
                            <span class="px-2 py-0.5 rounded-full text-[9.5px] font-mono-code font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 uppercase">
                                Optional
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-600 mt-0.5">
                            Append executive operational status, inter-unit directives, and conclusions to the generated PDF dossier.
                        </p>
                    </div>
                </div>
                <button type="button" onclick="closeReportNotesModal()" class="rep-masthead-inner rep-btn-green w-8 h-8 rounded-xl flex items-center justify-center cursor-pointer shrink-0" aria-label="Close modal">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 sm:p-7 space-y-5 overflow-y-auto flex-1 bg-slate-50/60 text-slate-800 text-xs custom-scrollbar">

                <!-- 01 STATUS -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between">
                        <label for="reportStatusInput" class="font-extrabold text-[11px] uppercase tracking-wider text-slate-900 font-mono-code flex items-center gap-2">
                            <span class="px-1.5 py-0.5 rounded bg-slate-200 text-slate-800 text-[9.5px]">01</span>
                            <span>Operational Status</span>
                        </label>
                        <span class="text-[10px] font-mono-code text-slate-400">Field deployment state</span>
                    </div>
                    <textarea id="reportStatusInput"
                              name="status"
                              rows="3"
                              class="rep-input w-full min-h-[86px] p-3.5 text-xs leading-relaxed"
                              placeholder="Summarize field operations, active raids, and current investigation posture...">{{ $latestReportNote->status ?? '' }}</textarea>
                </div>

                <!-- 02 RECOMMENDATION -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between">
                        <label for="reportRecommendationInput" class="font-extrabold text-[11px] uppercase tracking-wider text-slate-900 font-mono-code flex items-center gap-2">
                            <span class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 text-[9.5px]">02</span>
                            <span>Recommendation</span>
                        </label>
                        <span class="text-[10px] font-mono-code text-slate-400">Inter-unit directives</span>
                    </div>
                    <textarea id="reportRecommendationInput"
                              name="recommendation"
                              rows="3"
                              class="rep-input w-full min-h-[86px] p-3.5 text-xs leading-relaxed"
                              placeholder="Specify directives for prosecution, laboratory assays, custodial vaulting, or surveillance...">{{ $latestReportNote->recommendation ?? '' }}</textarea>
                </div>

                <!-- 03 CONCLUSION -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between">
                        <label for="reportConclusionInput" class="font-extrabold text-[11px] uppercase tracking-wider text-slate-900 font-mono-code flex items-center gap-2">
                            <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[9.5px]">03</span>
                            <span>Executive Conclusion</span>
                        </label>
                        <span class="text-[10px] font-mono-code text-slate-400">Command summary</span>
                    </div>
                    <textarea id="reportConclusionInput"
                              name="conclusion"
                              rows="3"
                              class="rep-input w-full min-h-[86px] p-3.5 text-xs leading-relaxed"
                              placeholder="Record concluding operational assessment and command sign-off remarks...">{{ $latestReportNote->conclusion ?? '' }}</textarea>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 bg-white border-t border-slate-200 flex flex-wrap items-center justify-between gap-3 shrink-0">
                <button type="button"
                        onclick="proceedWithoutNotes()"
                        class="rep-btn-green-soft px-4 py-2 font-bold text-xs rounded-xl cursor-pointer">
                    Skip &bull; Generate Without Notes
                </button>

                <div class="flex items-center gap-2.5">
                    <button type="button"
                            onclick="closeReportNotesModal()"
                            class="rep-btn-green-soft px-4 py-2 font-bold text-xs rounded-xl cursor-pointer">
                        Cancel
                    </button>
                    <button type="button"
                            id="submitNotesBtn"
                            onclick="saveReportNotesAndProceed()"
                            class="rep-btn-green px-5 py-2.5 font-extrabold text-xs rounded-xl cursor-pointer flex items-center gap-2">
                        <i data-lucide="printer" class="w-4 h-4" id="submitNotesIcon"></i>
                        <span id="submitNotesText">Save &amp; Export PDF</span>
                    </button>
                </div>
            </div>

        </div>
    </div>

    <script>
        if (window.lucide) {
            lucide.createIcons();
        }

        const PERIOD_LABELS = {
            weekly: 'Weekly Window',
            monthly: 'Monthly Window',
            annual: 'Annual Window',
            general: 'Cumulative Ledger'
        };

        function initFieldCasesTable() {
            if (typeof initAjaxTable !== 'function') return;

            window.fieldCasesAjaxTable = initAjaxTable({
                container: '#fieldCasesTableContainer',
                table: '#fieldCasesTable',
                form: '#reportFilterForm',
                endpoint: @json($reportEndpoint),
                debounce: 300,
                columns: 6,
                rows: 6,
                onAfterUpdate: function (container) {
                    const meta = container.querySelector('#fieldCasesTableMeta');
                    if (meta) {
                        const total = meta.getAttribute('data-total');
                        const first = meta.getAttribute('data-first-item');
                        const last = meta.getAttribute('data-last-item');
                        const filtered = meta.getAttribute('data-filtered-count');
                        const cumulative = meta.getAttribute('data-cumulative-count');

                        const badge = document.getElementById('casesCountBadge');
                        if (badge && total !== null) {
                            badge.textContent = Number(total).toLocaleString() + ' Dockets Filed';
                        }

                        const filteredInfo = document.getElementById('filteredCountInfo');
                        if (filteredInfo && filtered !== null) {
                            filteredInfo.textContent = `Showing ${first}–${last} of ${Number(filtered).toLocaleString()} filtered cases (Cumulative: ${Number(cumulative).toLocaleString()})`;
                        }
                    }

                    if (window.lucide && typeof window.lucide.createIcons === 'function') {
                        lucide.createIcons();
                    }
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initFieldCasesTable);
        } else {
            initFieldCasesTable();
        }

        function updatePeriodButtonsUi(period) {
            document.querySelectorAll('.console-period-btn').forEach(btn => {
                if (btn.getAttribute('data-period') === period) {
                    btn.className = 'console-period-btn px-3 py-1.5 rounded-lg transition-all rep-btn-green';
                } else {
                    btn.className = 'console-period-btn px-3 py-1.5 rounded-lg transition-all rep-btn-green-soft';
                }
            });
            const scopeEl = document.getElementById('mastheadScopeLabel');
            if (scopeEl && PERIOD_LABELS[period]) {
                scopeEl.textContent = PERIOD_LABELS[period];
            }
        }

        function setReportPeriod(period) {
            document.getElementById('periodInput').value = period;
            document.getElementById('fromDate').value = '';
            document.getElementById('toDate').value = '';
            updatePeriodButtonsUi(period);

            if (window.fieldCasesAjaxTable) {
                window.fieldCasesAjaxTable.applyFilters();
            } else {
                document.getElementById('reportFilterForm').submit();
            }
        }

        function quickFilterByPriority(level) {
            const sel = document.getElementById('prioritySelect');
            if (!sel) return;
            sel.value = (sel.value === level) ? 'ALL' : level;
            if (window.fieldCasesAjaxTable) {
                window.fieldCasesAjaxTable.applyFilters();
            } else {
                document.getElementById('reportFilterForm').submit();
            }
        }

        function resetReportFilters(e) {
            if (e) e.preventDefault();
            const form = document.getElementById('reportFilterForm');
            if (form) {
                form.reset();
                document.getElementById('periodInput').value = 'general';
                document.getElementById('searchInput').value = '';
                document.getElementById('statusSelect').value = 'ALL';
                document.getElementById('operationTypeSelect').value = 'ALL';
                document.getElementById('prioritySelect').value = 'ALL';
                document.getElementById('fromDate').value = '';
                document.getElementById('toDate').value = '';
                const sortSelect = form.querySelector('select[name="sort"]');
                if (sortSelect) sortSelect.value = 'newest';
                const perPageSelect = form.querySelector('select[name="per_page"]');
                if (perPageSelect) perPageSelect.value = '20';
            }
            updatePeriodButtonsUi('general');
            if (window.fieldCasesAjaxTable) {
                window.fieldCasesAjaxTable.applyFilters();
            } else {
                window.location.href = @json($reportEndpoint);
            }
        }

        function openReportNotesModal() {
            const modal = document.getElementById('reportNotesModal');
            if (modal) {
                modal.classList.remove('hidden');
                if (window.lucide) lucide.createIcons();
            }
        }

        function closeReportNotesModal() {
            const modal = document.getElementById('reportNotesModal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeReportNotesModal();
        });

        document.getElementById('reportNotesModal')?.addEventListener('click', function(e) {
            if (e.target.id === 'reportNotesModal') closeReportNotesModal();
        });

        function buildBasePdfParams() {
            const params = new URLSearchParams();
            const period = document.getElementById('periodInput')?.value;
            const search = document.getElementById('searchInput')?.value;
            const status = document.getElementById('statusSelect')?.value;
            const opType = document.getElementById('operationTypeSelect')?.value;
            const priority = document.getElementById('prioritySelect')?.value;
            const from = document.getElementById('fromDate')?.value;
            const to = document.getElementById('toDate')?.value;

            if (period && period !== 'general' && period !== 'all') params.set('period', period);
            if (search) params.set('search', search);
            if (status && status !== 'ALL') params.set('status', status);
            if (opType && opType !== 'ALL') params.set('operation_type', opType);
            if (priority && priority !== 'ALL') params.set('priority', priority);
            if (from) params.set('from_date', from);
            if (to) params.set('to_date', to);

            return params;
        }

        function proceedWithoutNotes() {
            closeReportNotesModal();
            const params = buildBasePdfParams();
            window.open('{{ url("/Crime_Scene_Support/report_pdf") }}?' + params.toString(), '_blank');
        }

        async function saveReportNotesAndProceed() {
            const btn = document.getElementById('submitNotesBtn');
            const btnText = document.getElementById('submitNotesText');
            const originalText = btnText ? btnText.innerText : 'Save & Export PDF';

            const statusVal = document.getElementById('reportStatusInput')?.value?.trim() || '';
            const recommendationVal = document.getElementById('reportRecommendationInput')?.value?.trim() || '';
            const conclusionVal = document.getElementById('reportConclusionInput')?.value?.trim() || '';

            const period = document.getElementById('periodInput')?.value || 'general';
            const from = document.getElementById('fromDate')?.value || '';
            const to = document.getElementById('toDate')?.value || '';
            const search = document.getElementById('searchInput')?.value || '';
            const priority = document.getElementById('prioritySelect')?.value || '';
            const actionStatus = document.getElementById('statusSelect')?.value || '';

            let batchId = null;

            try {
                if (btnText) btnText.innerText = 'Saving Briefing...';
                if (btn) btn.disabled = true;

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    || '{{ csrf_token() }}';

                const response = await fetch('{{ url("/Crime_Scene_Support/report/notes") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        status: statusVal,
                        recommendation: recommendationVal,
                        conclusion: conclusionVal,
                        period: period,
                        from_date: from,
                        to_date: to,
                        search: search,
                        priority: priority,
                        status_filter: actionStatus,
                        total_cases_count: {{ (int) ($totalCasesCount ?? 0) }}
                    })
                });

                const data = await response.json();
                if (data && data.success && data.batch_id) {
                    batchId = data.batch_id;
                }
            } catch (err) {
                console.error('Error saving crime scene report details:', err);
            } finally {
                if (btnText) btnText.innerText = originalText;
                if (btn) btn.disabled = false;
            }

            closeReportNotesModal();
            const params = buildBasePdfParams();
            if (batchId) {
                params.set('batch_id', batchId);
            }
            window.open('{{ url("/Crime_Scene_Support/report_pdf") }}?' + params.toString(), '_blank');
        }
    </script>
</body>
</html>
