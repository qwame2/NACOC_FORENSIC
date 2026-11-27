@php
    $authUser = auth()->user();
    $isStaffReport = $isStaffReport ?? false;
    $isLabAnalysis = true;
    $sidebarUnit = 'Lab Analysis';

    $officerFullname = $authUser ? ($authUser->fullname ?? $authUser->name ?? 'Forensic Chemist') : 'Lead Forensic Chemist';
    $officerParts = preg_split('/\s+/', trim((string) $officerFullname)) ?: [];
    $officerInitials = count($officerParts) >= 2
        ? strtoupper(substr($officerParts[0], 0, 1) . substr(end($officerParts), 0, 1))
        : strtoupper(substr((string) $officerFullname, 0, 2));

    $rawPeriodNorm = strtolower(trim((string) ($rawPeriod ?? 'weekly')));
    $activePeriodLabel = match (true) {
        in_array($rawPeriodNorm, ['weekly', 'week'], true) => 'Weekly Window',
        in_array($rawPeriodNorm, ['monthly', 'month'], true) => 'Monthly Window',
        in_array($rawPeriodNorm, ['annual', 'annually', 'yearly', 'year'], true) => 'Annual Window',
        default => 'Cumulative Ledger',
    };

    $drugScreeningLink = $isStaffReport ? url('/Lab_Analysis/staff/drug-screening') : url('/Lab_Analysis/drug-screening');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Laboratory Analysis Unit | Forensic Drug Report &amp; Suspect Register | NACOC Forensic</title>

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

    @include('partials.report_design_system')
</head>
<body class="min-h-screen flex antialiased selection:bg-emerald-600 selection:text-white">

    <div class="flex h-screen w-full overflow-hidden">

        @if($isStaffReport)
            @include('staff.partials.sidebar', ['sidebarUnit' => 'Lab Analysis'])
        @else
            @include('Crime_Scene_Support.partials.sidebar')
        @endif

        <div class="flex-1 flex flex-col min-w-0 bg-[#f3f5f8] overflow-hidden report-print-container">

            <!-- STICKY COMMAND HEADER -->
            <header class="h-16 bg-white/95 backdrop-blur-md border-b border-slate-200/90 px-5 sm:px-6 flex items-center justify-between gap-4 shrink-0 z-20">
                <div class="flex items-center gap-3.5 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-emerald-700 border border-slate-200/90 flex items-center justify-center shrink-0 shadow-2xs">
                        <i data-lucide="flask-conical" class="w-4 h-4"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-[10px] font-extrabold font-mono-code uppercase tracking-widest text-emerald-700">
                                Laboratory Analysis Unit
                            </span>
                            <span class="text-slate-300">/</span>
                            <span class="text-[10px] font-bold font-mono-code uppercase tracking-wider text-slate-500">
                                {{ $isStaffReport ? 'Staff Analytical Workstation' : 'Forensic Drug & Suspect SITREP' }}
                            </span>
                        </div>
                        <h1 class="text-sm font-extrabold text-slate-900 tracking-tight truncate">
                            Forensic Drug &amp; Suspect Register Report
                        </h1>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 shrink-0" id="printActionButtons">
                    <a href="{{ $drugScreeningLink }}"
                       class="rep-btn-green-soft hidden md:inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold">
                        <i data-lucide="test-tubes" class="w-3.5 h-3.5"></i>
                        <span>Drug Screening</span>
                    </a>

                    <div class="hidden xl:flex items-center gap-2.5 pl-3 border-l border-slate-200">
                        <div class="text-right">
                            <div class="text-xs font-bold text-slate-900 leading-tight truncate max-w-[150px]">{{ $officerFullname }}</div>
                            <div class="text-[10px] font-mono-code font-semibold text-slate-500">{{ now()->format('d M Y • H:i') }}</div>
                        </div>
                        <div class="w-8 h-8 rounded-xl bg-slate-100 border border-slate-200/90 text-slate-800 flex items-center justify-center font-mono-code font-extrabold text-[11px]">
                            {{ $officerInitials ?: 'LA' }}
                        </div>
                    </div>
                </div>
            </header>

            <!-- SCROLLABLE WORKSPACE -->
            <main class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-5 custom-scrollbar">

                <!-- 1. EXECUTIVE GREY COMMAND MASTHEAD + 5-COLUMN TELEMETRY MATRIX -->
                <section class="rep-masthead" aria-label="Laboratory Analytical Telemetry">
                    <div class="rep-masthead-hero px-6 py-5 sm:px-7 sm:py-6">
                        <div class="rep-masthead-inner flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <div class="space-y-1.5 max-w-3xl">
                                <h2 class="text-lg sm:text-xl font-extrabold tracking-tight text-slate-900">
                                    Preliminary Narcotics Examination, Drug Screening &amp; GSA Confirmatory Report
                                </h2>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    Unified laboratory situational report covering seized narcotic exhibits, suspect drug associations, rapid multi-panel drug screening, and Ghana Standards Authority GC-MS referrals.
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                                <div class="px-3.5 py-2 rounded-xl bg-white border border-slate-200/90 shadow-2xs">
                                    <div class="text-[9.5px] font-mono-code uppercase tracking-wider text-slate-500">Active Window</div>
                                    <div id="labMastheadPeriod" class="text-xs font-mono-code font-extrabold text-slate-900 mt-0.5">{{ $activePeriodLabel }}</div>
                                </div>
                                <div class="px-3.5 py-2 rounded-xl bg-emerald-50 border border-emerald-200/90 shadow-2xs">
                                    <div class="text-[9.5px] font-mono-code uppercase tracking-wider text-emerald-700">Filtered Seizures</div>
                                    <div class="text-xs font-mono-code font-extrabold text-emerald-800 mt-0.5 tnum">
                                        <span id="mastheadVisibleSeizures">{{ count($filteredSeizures) }}</span> Exhibits Matched
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 5-COLUMN PRECISION KPI STRIP -->
                    <div class="rep-kpi-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 divide-y sm:divide-y-0 sm:divide-x divide-slate-200/80">

                        <button type="button" onclick="switchActiveTab('seizures')" class="rep-kpi-tile p-4 sm:px-5 flex flex-col justify-between text-left group">
                            <div class="flex items-center justify-between gap-2 w-full">
                                <span class="text-[10.5px] font-extrabold font-mono-code uppercase tracking-wider text-emerald-700">Seizure Operations</span>
                                <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200/70 flex items-center justify-center shrink-0">
                                    <i data-lucide="microscope" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                            <div class="mt-2.5 flex items-baseline gap-2">
                                <span class="text-2xl font-black text-slate-900 font-mono-code tnum" id="kpiTotalSeizures">{{ count($baselineSeizures) }}</span>
                                <span class="text-[11px] text-slate-500 font-semibold">Logged</span>
                            </div>
                            <div class="mt-2 text-[10.5px] text-emerald-700 font-semibold group-hover:underline">
                                Drug underway
                            </div>
                        </button>

                        <div class="rep-kpi-tile p-4 sm:px-5 flex flex-col justify-between">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-extrabold font-mono-code uppercase tracking-wider text-indigo-700">Suspects Identified</span>
                                <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200/70 flex items-center justify-center shrink-0">
                                    <i data-lucide="users" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                            <div class="mt-2.5 flex items-baseline gap-2">
                                <span class="text-2xl font-black text-slate-900 font-mono-code tnum" id="kpiTotalSuspects">{{ $totalSuspectsCount }}</span>
                                <span class="text-[11px] text-slate-500 font-semibold">Subjects</span>
                            </div>
                            <div class="mt-2 text-[10.5px] text-indigo-700 font-semibold">
                                Linked to narcotic exhibits
                            </div>
                        </div>

                        <div class="rep-kpi-tile p-4 sm:px-5 flex flex-col justify-between">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-extrabold font-mono-code uppercase tracking-wider text-amber-700">Suspects At Large</span>
                                <span class="w-7 h-7 rounded-lg bg-amber-50 text-amber-700 border border-amber-200/70 flex items-center justify-center shrink-0">
                                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                            <div class="mt-2.5 flex items-baseline gap-2">
                                <span class="text-2xl font-black text-amber-700 font-mono-code tnum">{{ $atLargeCount }}</span>
                                <span class="text-[11px] text-slate-500 font-semibold">Warrants</span>
                            </div>
                            <div class="mt-2 text-[10.5px] text-amber-700 font-semibold">
                                Active tracing protocols
                            </div>
                        </div>

                        <button type="button" onclick="switchActiveTab('screenings')" class="rep-kpi-tile p-4 sm:px-5 flex flex-col justify-between text-left group">
                            <div class="flex items-center justify-between gap-2 w-full">
                                <span class="text-[10.5px] font-extrabold font-mono-code uppercase tracking-wider text-teal-700">Drug Screenings</span>
                                <span class="w-7 h-7 rounded-lg bg-teal-50 text-teal-700 border border-teal-200/70 flex items-center justify-center shrink-0">
                                    <i data-lucide="droplets" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                            <div class="mt-2.5 flex items-baseline gap-2">
                                <span class="text-2xl font-black text-slate-900 font-mono-code tnum">{{ count($drugScreeningRecords) }}</span>
                                <span class="text-[11px] text-slate-500 font-semibold">Assays</span>
                            </div>
                            <div class="mt-2 text-[10.5px] text-emerald-700 font-semibold group-hover:underline">
                                View drug screening tab &rarr;
                            </div>
                        </button>

                        <button type="button" onclick="switchActiveTab('gsa')" class="rep-kpi-tile p-4 sm:px-5 flex flex-col justify-between text-left group">
                            <div class="flex items-center justify-between gap-2 w-full">
                                <span class="text-[10.5px] font-extrabold font-mono-code uppercase tracking-wider text-purple-700">GSA Submissions</span>
                                <span class="w-7 h-7 rounded-lg bg-purple-50 text-purple-700 border border-purple-200/70 flex items-center justify-center shrink-0">
                                    <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                            <div class="mt-2.5 flex items-baseline gap-2">
                                <span class="text-2xl font-black text-slate-900 font-mono-code tnum">{{ count($gsaSubmissions) }}</span>
                                <span class="text-[11px] text-slate-500 font-semibold">Referrals</span>
                            </div>
                            <div class="mt-2 text-[10.5px] text-emerald-700 font-semibold group-hover:underline">
                                Confirmatory GC-MS log &rarr;
                            </div>
                        </button>

                    </div>
                </section>

                <!-- 2. INTERACTIVE REPORT GENERATION & FILTER CONSOLE -->
                <section class="rep-surface-card p-5 sm:px-6 space-y-4" id="reportFilterConsole" aria-label="Report Generation Parameters">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3.5 border-b border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <span class="w-7 h-7 rounded-lg bg-emerald-50 border border-emerald-200/70 text-emerald-700 flex items-center justify-center shrink-0">
                                <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i>
                            </span>
                            <div>
                                <h2 class="text-xs font-extrabold text-slate-900 font-mono-code uppercase tracking-wider">
                                    Report Generation &amp; Filter Console
                                </h2>
                                <p class="text-[11px] text-slate-500">
                                    Isolate a single suspect or compile a consolidated multi-case laboratory SITREP dossier with official cover sheet.
                                </p>
                            </div>
                        </div>

                        <!-- SEGMENTED PERIOD SWITCHER (GREEN BUTTONS) -->
                        <div class="rep-segmented font-mono-code text-xs font-bold shrink-0 self-start md:self-auto">
                            <button type="button" onclick="setConsolePeriod('weekly')" class="console-period-btn px-3 py-1.5 rounded-lg transition-all {{ in_array($rawPeriodNorm, ['weekly', 'week'], true) ? 'rep-btn-green' : 'rep-btn-green-soft' }}" data-period="weekly">
                                Weekly
                            </button>
                            <button type="button" onclick="setConsolePeriod('monthly')" class="console-period-btn px-3 py-1.5 rounded-lg transition-all {{ in_array($rawPeriodNorm, ['monthly', 'month'], true) ? 'rep-btn-green' : 'rep-btn-green-soft' }}" data-period="monthly">
                                Monthly
                            </button>
                            <button type="button" onclick="setConsolePeriod('annual')" class="console-period-btn px-3 py-1.5 rounded-lg transition-all {{ in_array($rawPeriodNorm, ['annual', 'annually', 'yearly', 'year'], true) ? 'rep-btn-green' : 'rep-btn-green-soft' }}" data-period="annual">
                                Annual
                            </button>
                            <button type="button" onclick="setConsolePeriod('general')" class="console-period-btn px-3 py-1.5 rounded-lg transition-all {{ in_array($rawPeriodNorm, ['all', 'general'], true) ? 'rep-btn-green' : 'rep-btn-green-soft' }}" data-period="general">
                                General
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-col xl:flex-row xl:items-end justify-between gap-3">
                        <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end min-w-0">

                            <!-- Target Suspect -->
                            <div class="lg:col-span-3 space-y-1.5">
                                <label for="consoleSuspectSelect" class="block text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider truncate">
                                    Target Suspect <span class="text-emerald-700">(Single / All)</span>
                                </label>
                                <select id="consoleSuspectSelect" class="rep-select w-full px-3 py-2 text-xs font-mono-code font-semibold" onchange="applyConsoleFilter()">
                                    <option value="" {{ empty($selectedSuspect) ? 'selected' : '' }}>— All Suspects (Consolidated) —</option>
                                    @foreach($allSuspectOptions as $suspectOpt)
                                        <option value="{{ $suspectOpt }}" {{ ($selectedSuspect ?? '') === $suspectOpt ? 'selected' : '' }}>
                                            {{ $suspectOpt }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Live Search Keyword -->
                            <div class="lg:col-span-4 space-y-1.5">
                                <label for="consoleSearchInput" class="block text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider">
                                    Live Search Keyword
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 pointer-events-none">
                                        <i data-lucide="search" class="w-3.5 h-3.5"></i>
                                    </span>
                                    <input type="text"
                                           id="consoleSearchInput"
                                           value="{{ $searchQuery ?? '' }}"
                                           placeholder="Search suspect name, source unit, exhibit, lab no..."
                                           class="rep-input w-full pl-9 pr-3 py-2 text-xs font-mono-code font-semibold"
                                           oninput="applyConsoleFilter()">
                                </div>
                            </div>

                            <!-- From Date -->
                            <div class="lg:col-span-2 space-y-1.5">
                                <label for="consoleFromDate" class="block text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider">
                                    From Date
                                </label>
                                <input type="date"
                                       id="consoleFromDate"
                                       value="{{ $fromDate ?? '' }}"
                                       class="rep-input w-full px-2.5 py-2 text-xs font-mono-code font-semibold"
                                       onchange="applyConsoleFilter()">
                            </div>

                            <!-- To Date -->
                            <div class="lg:col-span-2 space-y-1.5">
                                <label for="consoleToDate" class="block text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider">
                                    To Date
                                </label>
                                <input type="date"
                                       id="consoleToDate"
                                       value="{{ $toDate ?? '' }}"
                                       class="rep-input w-full px-2.5 py-2 text-xs font-mono-code font-semibold"
                                       onchange="applyConsoleFilter()">
                            </div>

                            <!-- Reset -->
                            <div class="lg:col-span-1 space-y-1.5">
                                <button type="button"
                                        onclick="resetConsoleFilters()"
                                        class="rep-btn-green-soft w-full px-2.5 py-2 text-xs rounded-xl font-bold font-mono-code whitespace-nowrap cursor-pointer text-center">
                                    Reset
                                </button>
                            </div>

                        </div>

                        <!-- Primary Generate Action -->
                        <div class="shrink-0">
                            <button type="button"
                                    onclick="triggerMainGeneratePdf()"
                                    class="rep-btn-green w-full xl:w-auto px-5 py-2.5 rounded-xl text-xs font-extrabold flex items-center justify-center gap-2 cursor-pointer whitespace-nowrap">
                                <i data-lucide="file-check-2" class="w-4 h-4"></i>
                                <span id="generateBtnLabel">Generate Filtered PDF Report</span>
                            </button>
                        </div>
                    </div>
                </section>

                <!-- 3. MULTI-PANE ANALYTICAL LEDGER -->
                <section class="rep-surface-card overflow-hidden" aria-label="Laboratory Analytical Ledgers">

                    <!-- PRECISION TAB BAR -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 px-5 sm:px-6 py-3 bg-slate-100/80">
                        <div class="flex items-center gap-2.5 flex-wrap font-mono-code text-xs font-bold">
                            <button type="button"
                                    onclick="switchActiveTab('seizures')"
                                    id="tabBtnSeizures"
                                    class="rep-btn-green px-3.5 py-2 rounded-xl flex items-center gap-2 transition-all shadow-md">
                                <i data-lucide="package-search" class="w-3.5 h-3.5"></i>
                                <span>Drug Seizures &amp; Suspects</span>
                                <span class="tab-count-badge px-2 py-0.5 rounded-full text-[10px] bg-white/20 text-white tnum" id="badgeSeizuresCount">{{ count($filteredSeizures) }}</span>
                            </button>

                            <button type="button"
                                    onclick="switchActiveTab('screenings')"
                                    id="tabBtnScreenings"
                                    class="bg-slate-200 hover:bg-slate-300/80 text-slate-700 hover:text-slate-900 border border-slate-300/90 shadow-sm px-3.5 py-2 rounded-xl flex items-center gap-2 transition-all">
                                <i data-lucide="droplets" class="w-3.5 h-3.5"></i>
                                <span>Drug Screenings</span>
                                <span class="tab-count-badge px-2 py-0.5 rounded-full text-[10px] bg-slate-300/80 text-slate-800 tnum">{{ count($drugScreeningRecords) }}</span>
                            </button>

                            <button type="button"
                                    onclick="switchActiveTab('gsa')"
                                    id="tabBtnGsa"
                                    class="bg-slate-200 hover:bg-slate-300/80 text-slate-700 hover:text-slate-900 border border-slate-300/90 shadow-sm px-3.5 py-2 rounded-xl flex items-center gap-2 transition-all">
                                <i data-lucide="building-2" class="w-3.5 h-3.5"></i>
                                <span>GSA External Submissions</span>
                                <span class="tab-count-badge px-2 py-0.5 rounded-full text-[10px] bg-slate-300/80 text-slate-800 tnum">{{ count($gsaSubmissions) }}</span>
                            </button>
                        </div>

                        <div class="text-[11px] font-mono-code font-semibold text-slate-600 tnum">
                            Showing <span id="visibleEntriesCount" class="font-extrabold text-slate-900">{{ count($filteredSeizures) }}</span> active records
                        </div>
                    </div>

                    <!-- TAB PANE 1: DRUG SEIZURES & SUSPECT DIRECTORY -->
                    <div id="paneSeizures" class="divide-y divide-slate-100">
                        <div class="px-6 py-4 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 tracking-tight">
                                    Preliminary Forensic Examination &amp; Narcotic Seizure Ledger
                                </h3>
                                <p class="text-[11px] text-slate-500">
                                    Single and multiple suspect narcotic dockets logged for presumptive and instrumental analysis.
                                </p>
                            </div>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-200/70 text-[10px] font-mono-code font-bold text-emerald-800 self-start sm:self-auto">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                <span>Chain-of-Custody Synced</span>
                            </span>
                        </div>

                        <div class="overflow-x-auto custom-scrollbar">
                            <table class="rep-table w-full min-w-[920px] text-left border-collapse text-xs" id="directorySuspectTable">
                                <thead>
                                    <tr>
                                        <th class="py-3.5 px-6 w-[34%]">Suspect(s) &amp; Custody Profile</th>
                                        <th class="py-3.5 px-4 w-[22%]">Date &amp; Submitting Source</th>
                                        <th class="py-3.5 px-4 w-[26%]">Exhibit &amp; Substance Profile</th>
                                        <th class="py-3.5 px-6 w-[18%] text-right">Quantity &amp; Net Weight</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white text-slate-800" id="seizuresTableBody">
                                    @forelse($filteredSeizures as $item)
                                        @php
                                            $firstSuspect = $item['primary_suspect'] ?? ($item['suspects'][0]['name'] ?? 'Suspect');
                                            $allSuspectNamesLower = strtolower(implode(' ', array_filter(array_column($item['suspects'], 'name'))));
                                            $suspectsList = $item['suspects'] ?? [];
                                            $namesList = array_map(function($s) {
                                                return trim($s['name'] ?? ($s['suspect_name'] ?? 'Suspect'));
                                            }, $suspectsList);
                                            $namesDisplay = implode(', ', array_filter($namesList));
                                        @endphp
                                        <tr class="directory-row"
                                            data-suspect="{{ $allSuspectNamesLower }} {{ strtolower($item['suspects_display']) }}"
                                            data-primary="{{ strtolower($firstSuspect) }}"
                                            data-source="{{ strtolower($item['source']) }}"
                                            data-exhibit="{{ strtolower($item['exhibit_type']) }}"
                                            data-date="{{ $item['date'] }}"
                                            data-remarks="{{ strtolower($item['status_remarks']) }}"
                                            data-suspects-count="{{ count($suspectsList) }}">

                                            <!-- Suspects -->
                                            <td class="py-3.5 px-6 align-top">
                                                <div class="flex items-center gap-3">
                                                    <div class="flex items-center {{ count($suspectsList) > 1 ? '-space-x-2' : '' }} shrink-0">
                                                        @foreach($suspectsList as $sIdx => $s)
                                                            @php
                                                                $rawMug = $s['mugshot_front'] ?? ($s['mugshot'] ?? null);
                                                                $sMug = null;
                                                                if (!empty($rawMug)) {
                                                                    $rawMug = trim((string) $rawMug);
                                                                    $sMug = (!str_starts_with($rawMug, 'data:image') && !str_starts_with($rawMug, 'http://') && !str_starts_with($rawMug, 'https://') && !str_starts_with($rawMug, '/'))
                                                                        ? asset($rawMug)
                                                                        : $rawMug;
                                                                }
                                                                $sFullName = $s['name'] ?? ($s['suspect_name'] ?? 'Suspect');
                                                                $sInit = strtoupper(substr($sFullName, 0, 2));
                                                            @endphp
                                                            <div class="relative w-8 h-8 rounded-full ring-2 ring-white bg-slate-200 text-emerald-800 overflow-hidden shadow-xs flex items-center justify-center shrink-0 hover:z-30 transition-transform hover:scale-105"
                                                                 style="z-index: {{ 20 - $sIdx }};"
                                                                 title="{{ $sFullName }}">
                                                                @if(!empty($sMug))
                                                                    <img src="{{ $sMug }}" alt="{{ $sFullName }}" class="w-full h-full object-cover">
                                                                @else
                                                                    <span class="font-black text-[10px] font-mono-code">{{ $sInit }}</span>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>

                                                    <div class="min-w-0">
                                                        <div class="font-extrabold text-slate-900 text-xs font-mono-code leading-snug">
                                                            {{ $namesDisplay ?: 'Suspect' }}
                                                        </div>
                                                        <div class="mt-0.5 flex items-center gap-1.5">
                                                            <span class="text-[10px] font-mono-code font-semibold text-slate-500">
                                                                {{ count($suspectsList) > 1 ? count($suspectsList) . ' Linked Subjects' : 'Single Subject' }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Date & Source -->
                                            <td class="py-3.5 px-4 align-top whitespace-nowrap">
                                                <div class="font-mono-code font-bold text-slate-900 text-[11px] tnum">
                                                    {{ $item['date_display'] }}
                                                </div>
                                                <div class="font-semibold text-slate-600 text-xs mt-0.5 truncate max-w-[210px]" title="{{ $item['source_of_exhibits'] ?? ($item['source'] ?? 'N/A') }}">
                                                    {{ $item['source_of_exhibits'] ?? ($item['source'] ?? 'N/A') }}
                                                </div>
                                                <span class="inline-block mt-1 px-1.5 py-0.5 rounded text-[9px] font-bold font-mono-code bg-slate-100 text-slate-600 border border-slate-200">
                                                    {{ $item['source_badge'] ?? 'OPERATIONAL UNIT' }}
                                                </span>
                                            </td>

                                            <!-- Exhibit Info -->
                                            <td class="py-3.5 px-4 align-top">
                                                <div class="flex items-center gap-1.5 flex-wrap mb-1">
                                                    @if(!empty($item['lab_exhibit_no']) || !empty($item['exhibit_mark']))
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-extrabold font-mono-code bg-indigo-50 text-indigo-700 border border-indigo-200/80">
                                                            Exhibit {{ $item['lab_exhibit_no'] ?? $item['exhibit_mark'] }}
                                                        </span>
                                                    @endif
                                                    <span class="text-[9.5px] text-slate-400 font-mono-code font-semibold">
                                                        {{ $item['lab_number'] }}
                                                    </span>
                                                </div>
                                                <span class="font-extrabold text-slate-900 block text-xs">
                                                    {{ $item['exhibit_type'] }}
                                                </span>
                                                @if(!empty($item['substance_name']) && $item['substance_name'] !== $item['exhibit_type'])
                                                    <span class="text-[10.5px] text-emerald-700 font-semibold block mt-0.5">
                                                        {{ $item['substance_name'] }}
                                                    </span>
                                                @endif
                                            </td>

                                            <!-- Quantity & Weight -->
                                            <td class="py-3.5 px-6 align-top whitespace-nowrap text-right">
                                                @php
                                                    $displayQty = $item['quantity'] ?? '-';
                                                    $pkg = $item['package_type'] ?? ($item['packaging'] ?? null);
                                                    if ($displayQty !== '-' && !empty($pkg) && !str_contains(strtolower($displayQty), strtolower($pkg))) {
                                                        $displayQty = trim($displayQty . ' ' . $pkg);
                                                    }
                                                @endphp
                                                <div class="font-mono-code font-extrabold text-slate-900 text-xs tnum">
                                                    {{ $displayQty }}
                                                </div>
                                                <div class="font-mono-code text-[11px] font-semibold text-slate-500 mt-0.5 tnum">
                                                    {{ $item['weight'] ?? '-' }}
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr id="emptyDirectoryRow">
                                            <td colspan="4" class="py-14 text-center text-slate-500">
                                                <div class="flex flex-col items-center justify-center space-y-2">
                                                    <div class="w-12 h-12 rounded-2xl bg-slate-100 border border-slate-200 text-slate-400 flex items-center justify-center">
                                                        <i data-lucide="flask-conical" class="w-5 h-5"></i>
                                                    </div>
                                                    <p class="font-extrabold text-slate-800 font-mono-code uppercase text-xs">No Drug Seizures Found</p>
                                                    <p class="text-[11px] text-slate-500 max-w-sm">No narcotic case exhibits matched your active filter parameters or reporting window.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                    <tr id="clientFilteredEmptyRow" class="hidden">
                                        <td colspan="4" class="py-12 text-center text-slate-500">
                                            <p class="font-extrabold text-slate-800 font-mono-code uppercase text-xs">No Matching Records for Current Filter</p>
                                            <button type="button" onclick="resetConsoleFilters()" class="rep-btn-green mt-2.5 px-3.5 py-1.5 rounded-lg font-mono-code font-bold text-[11px]">
                                                Reset Filter Console
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        @if(method_exists($filteredSeizures, 'links') && $filteredSeizures->hasPages())
                            <div class="rep-pagination px-6 py-4 bg-slate-50/60">
                                {{ $filteredSeizures->links() }}
                            </div>
                        @endif
                    </div>

                    <!-- TAB PANE 2: SUSPECT URINE DRUG SCREENINGS -->
                    <div id="paneScreenings" class="hidden divide-y divide-slate-100">
                        <div class="px-6 py-4 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 tracking-tight">
                                    Suspect Rapid Urine Toxicology Register
                                </h3>
                                <p class="text-[11px] text-slate-500">
                                    Multi-panel immunoassay urinalysis screenings conducted on apprehended suspects and referrals.
                                </p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-teal-50 border border-teal-200/80 text-[10px] font-mono-code font-bold text-teal-800 self-start sm:self-auto">
                                {{ count($drugScreeningRecords) }} Toxicology Assays
                            </span>
                        </div>

                        <div class="overflow-x-auto custom-scrollbar">
                            <table class="rep-table w-full min-w-[880px] text-left border-collapse text-xs">
                                <thead>
                                    <tr>
                                        <th class="py-3.5 px-5 w-14 text-center">SN</th>
                                        <th class="py-3.5 px-4">Screening Date</th>
                                        <th class="py-3.5 px-4">Subject &amp; Case Ref</th>
                                        <th class="py-3.5 px-4">Assay Purpose</th>
                                        <th class="py-3.5 px-4 text-center">Toxicology Finding</th>
                                        <th class="py-3.5 px-6">Analytical Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white text-slate-800">
                                    @forelse($drugScreeningRecords as $ds)
                                        @php
                                            $isPositive = strtolower($ds['result']) === 'positive';
                                            $isNegative = strtolower($ds['result']) === 'negative';
                                            $subFound = trim($ds['substances_found'] ?? '');
                                            if ($isPositive) {
                                                $detectedLabel = (!empty($subFound) && !in_array(strtolower($subFound), ['no substance(s) found', 'no substances found', 'no drug(s) detected', 'no drugs detected']))
                                                    ? $subFound
                                                    : 'Substances detected';
                                                $resDisplay = $detectedLabel . ' (POSITIVE)';
                                            } elseif ($isNegative) {
                                                $resDisplay = 'No drug(s) detected (NEGATIVE)';
                                            } else {
                                                $resDisplay = $ds['result'];
                                            }
                                        @endphp
                                        <tr>
                                            <td class="py-3.5 px-5 text-center font-mono-code font-bold text-slate-400 tnum">
                                                {{ $ds['sn'] }}
                                            </td>
                                            <td class="py-3.5 px-4 font-mono-code font-bold text-slate-800 whitespace-nowrap tnum">
                                                {{ $ds['date_display'] }}
                                            </td>
                                            <td class="py-3.5 px-4">
                                                <span class="font-extrabold text-slate-900 block text-xs">{{ $ds['suspect_name'] }}</span>
                                                <span class="text-[10px] text-slate-500 font-mono-code">Ref: {{ $ds['case_reference'] }}</span>
                                            </td>
                                            <td class="py-3.5 px-4 text-slate-700 font-medium">
                                                {{ $ds['purpose'] }}
                                            </td>
                                            <td class="py-3.5 px-4 text-center align-middle">
                                                @if($isPositive)
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 font-mono-code font-extrabold text-[10.5px]">
                                                        {{ $resDisplay }}
                                                    </span>
                                                @elseif($isNegative)
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono-code font-bold text-[10.5px]">
                                                        {{ $resDisplay }}
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 border border-amber-200 font-mono-code font-bold text-[10.5px]">
                                                        {{ $resDisplay }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="py-3.5 px-6 text-slate-600 text-[11px] leading-relaxed">
                                                {{ str_ireplace(['No substance(s) found', 'No substances found'], 'No drug(s) detected', $ds['notes']) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="py-12 text-center text-slate-500 font-mono-code text-xs">
                                                No rapid urine toxicology records registered in this window.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB PANE 3: GSA SUBMISSIONS -->
                    <div id="paneGsa" class="hidden divide-y divide-slate-100">
                        <div class="px-6 py-4 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 tracking-tight">
                                    Ghana Standards Authority (GSA) External Confirmatory Referrals
                                </h3>
                                <p class="text-[11px] text-slate-500">
                                    Seized narcotic exhibits transmitted to GSA for confirmatory GC-MS certification.
                                </p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-indigo-50 border border-indigo-200/80 text-[10px] font-mono-code font-bold text-indigo-800 self-start sm:self-auto">
                                {{ count($gsaSubmissions) }} External Transfers
                            </span>
                        </div>

                        <div class="overflow-x-auto custom-scrollbar">
                            <table class="rep-table w-full min-w-[780px] text-left border-collapse text-xs">
                                <thead>
                                    <tr>
                                        <th class="py-3.5 px-5 w-14 text-center">S/N</th>
                                        <th class="py-3.5 px-4">Case Reference</th>
                                        <th class="py-3.5 px-4">Exhibit Commodity</th>
                                        <th class="py-3.5 px-4 text-center">GSA Lab Number</th>
                                        <th class="py-3.5 px-6 text-right">Confirmatory Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white text-slate-800">
                                    @forelse($gsaSubmissions as $gsa)
                                        @php
                                            $gStat = trim((string) ($gsa['gsa_status'] ?? ($gsa['status'] ?? 'Pending')));
                                            $gStatLower = strtolower($gStat);
                                            $gBadge = str_contains($gStatLower, 'complet') || str_contains($gStatLower, 'receiv') || str_contains($gStatLower, 'confirm')
                                                ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                                : 'bg-amber-50 text-amber-800 border-amber-200';
                                        @endphp
                                        <tr>
                                            <td class="py-3.5 px-5 text-center font-mono-code font-bold text-slate-400 tnum">
                                                {{ $gsa['sn'] }}
                                            </td>
                                            <td class="py-3.5 px-4 font-mono-code font-extrabold text-slate-900">
                                                {{ $gsa['gsa_case_reference'] ?? ($gsa['case_reference'] ?? 'N/A') }}
                                            </td>
                                            <td class="py-3.5 px-4 text-slate-800 font-semibold">
                                                {{ !empty($gsa['exhibit_type']) ? $gsa['exhibit_type'] : 'N/A' }}
                                            </td>
                                            <td class="py-3.5 px-4 text-center">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md bg-indigo-50 text-indigo-800 border border-indigo-200/80 font-mono-code font-bold text-[11px]">
                                                    {{ $gsa['gsa_lab_number'] ?? ($gsa['gsa_lab_no'] ?? 'N/A') }}
                                                </span>
                                            </td>
                                            <td class="py-3.5 px-6 text-right">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md border font-mono-code font-extrabold uppercase text-[10px] {{ $gBadge }}">
                                                    {{ $gStat }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="py-12 px-4 text-center text-slate-500 font-mono-code text-xs">
                                                No Ghana Standards Authority (GSA) submissions recorded for this reporting period.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </section>

            </main>
        </div>

    </div>

    <script>
        if (window.lucide) {
            lucide.createIcons();
        }

        const LAB_PERIOD_LABELS = {
            weekly: 'Weekly Window',
            monthly: 'Monthly Window',
            annual: 'Annual Window',
            general: 'Cumulative Ledger'
        };

        function switchActiveTab(tabName) {
            const tabs = [
                { key: 'seizures', paneId: 'paneSeizures', btnId: 'tabBtnSeizures', count: document.getElementById('badgeSeizuresCount')?.textContent || '0' },
                { key: 'screenings', paneId: 'paneScreenings', btnId: 'tabBtnScreenings', count: '{{ count($drugScreeningRecords) }}' },
                { key: 'gsa', paneId: 'paneGsa', btnId: 'tabBtnGsa', count: '{{ count($gsaSubmissions) }}' }
            ];

            tabs.forEach(t => {
                const pane = document.getElementById(t.paneId);
                const btn = document.getElementById(t.btnId);
                const badge = btn?.querySelector('.tab-count-badge');
                if (t.key === tabName) {
                    pane?.classList.remove('hidden');
                    if (btn) {
                        btn.className = 'rep-btn-green px-3.5 py-2 rounded-xl flex items-center gap-2 transition-all shadow-md';
                    }
                    if (badge) {
                        badge.className = 'tab-count-badge px-2 py-0.5 rounded-full text-[10px] bg-white/20 text-white tnum';
                    }
                    const vis = document.getElementById('visibleEntriesCount');
                    if (vis) vis.textContent = t.count;
                } else {
                    pane?.classList.add('hidden');
                    if (btn) {
                        btn.className = 'bg-slate-200 hover:bg-slate-300/80 text-slate-700 hover:text-slate-900 border border-slate-300/90 shadow-sm px-3.5 py-2 rounded-xl flex items-center gap-2 transition-all';
                    }
                    if (badge) {
                        badge.className = 'tab-count-badge px-2 py-0.5 rounded-full text-[10px] bg-slate-300/80 text-slate-800 tnum';
                    }
                }
            });
        }

        let currentPeriodPreset = '{{ $rawPeriod ?? "weekly" }}';

        function setConsolePeriod(preset) {
            currentPeriodPreset = preset;
            const btns = document.querySelectorAll('.console-period-btn');
            btns.forEach(b => {
                if (b.getAttribute('data-period') === preset) {
                    b.className = 'console-period-btn px-3 py-1.5 rounded-lg transition-all rep-btn-green';
                } else {
                    b.className = 'console-period-btn px-3 py-1.5 rounded-lg transition-all rep-btn-green-soft';
                }
            });

            const mastheadPeriod = document.getElementById('labMastheadPeriod');
            if (mastheadPeriod && LAB_PERIOD_LABELS[preset]) {
                mastheadPeriod.textContent = LAB_PERIOD_LABELS[preset];
            }

            const fromInput = document.getElementById('consoleFromDate');
            const toInput = document.getElementById('consoleToDate');
            const now = new Date();
            const formatYmd = (d) => d.toISOString().split('T')[0];

            if (preset === 'weekly') {
                const past7 = new Date();
                past7.setDate(now.getDate() - 7);
                fromInput.value = formatYmd(past7);
                toInput.value = formatYmd(now);
            } else if (preset === 'monthly') {
                const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                fromInput.value = formatYmd(firstDay);
                toInput.value = formatYmd(lastDay);
            } else if (preset === 'annual' || preset === 'annually') {
                const firstYear = new Date(now.getFullYear(), 0, 1);
                const lastYear = new Date(now.getFullYear(), 11, 31);
                fromInput.value = formatYmd(firstYear);
                toInput.value = formatYmd(lastYear);
            } else if (preset === 'all' || preset === 'general') {
                fromInput.value = '';
                toInput.value = '';
            }
            applyConsoleFilter();
        }

        function resetConsoleFilters() {
            document.getElementById('consoleSuspectSelect').value = '';
            document.getElementById('consoleSearchInput').value = '';
            setConsolePeriod('weekly');
        }

        function applyConsoleFilter() {
            const suspectVal = (document.getElementById('consoleSuspectSelect')?.value || '').toLowerCase().trim();
            const searchVal = (document.getElementById('consoleSearchInput')?.value || '').toLowerCase().trim();
            const fromVal = document.getElementById('consoleFromDate')?.value || '';
            const toVal = document.getElementById('consoleToDate')?.value || '';

            const rows = document.querySelectorAll('#seizuresTableBody tr.directory-row');
            let visibleCount = 0;

            rows.forEach(r => {
                const rSuspect = (r.getAttribute('data-suspect') || '').toLowerCase();
                const rSource = (r.getAttribute('data-source') || '').toLowerCase();
                const rExhibit = (r.getAttribute('data-exhibit') || '').toLowerCase();
                const rDate = (r.getAttribute('data-date') || '');
                const rRemarks = (r.getAttribute('data-remarks') || '').toLowerCase();

                let match = true;

                if (suspectVal && !rSuspect.includes(suspectVal)) {
                    match = false;
                }
                if (fromVal && rDate && rDate < fromVal) {
                    match = false;
                }
                if (toVal && rDate && rDate > toVal) {
                    match = false;
                }
                if (searchVal) {
                    const haystack = rSuspect + ' ' + rSource + ' ' + rExhibit + ' ' + rDate + ' ' + rRemarks;
                    if (!haystack.includes(searchVal)) {
                        match = false;
                    }
                }

                if (match) {
                    r.style.display = '';
                    visibleCount++;
                } else {
                    r.style.display = 'none';
                }
            });

            const clientEmpty = document.getElementById('clientFilteredEmptyRow');
            if (clientEmpty && rows.length > 0) {
                clientEmpty.classList.toggle('hidden', visibleCount > 0);
            }

            const countBadge = document.getElementById('visibleEntriesCount');
            const totalBadge = document.getElementById('badgeSeizuresCount');
            const mastheadCount = document.getElementById('mastheadVisibleSeizures');
            if (countBadge && !document.getElementById('paneSeizures')?.classList.contains('hidden')) {
                countBadge.textContent = visibleCount;
            }
            if (totalBadge) totalBadge.textContent = visibleCount;
            if (mastheadCount) mastheadCount.textContent = visibleCount;

            const btnLabel = document.getElementById('generateBtnLabel');
            if (btnLabel) {
                if (suspectVal) {
                    btnLabel.textContent = 'Generate Report: ' + document.getElementById('consoleSuspectSelect').selectedOptions[0].text;
                } else {
                    btnLabel.textContent = 'Generate Filtered PDF Report';
                }
            }
        }

        function triggerMainGeneratePdf() {
            const suspect = document.getElementById('consoleSuspectSelect')?.value || '';
            const search = document.getElementById('consoleSearchInput')?.value || '';
            const from = document.getElementById('consoleFromDate')?.value || '';
            const to = document.getElementById('consoleToDate')?.value || '';

            const params = new URLSearchParams();
            params.set('period', currentPeriodPreset);
            if (suspect) params.set('suspect', suspect);
            if (search) params.set('search', search);
            if (from) params.set('from', from);
            if (to) params.set('to', to);

            const targetUrl = '{{ url("/Lab_Analysis/lab_sitrep_pdf") }}?' + params.toString();
            window.open(targetUrl, '_blank');
        }

        function generateSingleSuspectPdf(suspectName) {
            const params = new URLSearchParams();
            params.set('suspect', suspectName);
            params.set('period', 'weekly');
            const targetUrl = '{{ url("/Lab_Analysis/lab_sitrep_pdf") }}?' + params.toString();
            window.open(targetUrl, '_blank');
        }

        function toggleCheckAll(checked) {
            const boxes = document.querySelectorAll('.row-checkbox');
            if (!boxes.length) return;
            boxes.forEach(b => b.checked = checked);
            onRowCheckChange();
        }

        function onRowCheckChange() {
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
            const btnLabel = document.getElementById('generateBtnLabel');
            if (checkedBoxes.length === 1) {
                const sName = checkedBoxes[0].value;
                const selectEl = document.getElementById('consoleSuspectSelect');
                if (selectEl) selectEl.value = sName;
                if (btnLabel) btnLabel.textContent = 'Generate Report: ' + sName;
            } else if (checkedBoxes.length > 1) {
                if (btnLabel) btnLabel.textContent = 'Generate Report for Selected (' + checkedBoxes.length + ') Suspects';
            } else {
                applyConsoleFilter();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            applyConsoleFilter();
        });
    </script>
</body>
</html>
