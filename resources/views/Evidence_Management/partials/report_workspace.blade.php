@php
    $authUser = auth()->user();
    $isStaffReport = $isStaffReport ?? false;
    $isEvidenceContext = true;
    $sidebarUnit = 'Evidence Management';
    $reportEndpoint = $reportEndpoint ?? ($isStaffReport ? url('/Evidence_Management/staff/report') : url('/Evidence_Management/report'));

    $officerFullname = $authUser ? ($authUser->fullname ?? $authUser->name ?? 'Vault Custodian') : 'Chief Vault Custodian';
    $officerParts = preg_split('/\s+/', trim((string) $officerFullname)) ?: [];
    $officerInitials = count($officerParts) >= 2
        ? strtoupper(substr($officerParts[0], 0, 1) . substr(end($officerParts), 0, 1))
        : strtoupper(substr((string) $officerFullname, 0, 2));

    $qrPct = ($totalExhibitsCount ?? 0) > 0 ? (int) round((($taggedQrCount ?? 0) / $totalExhibitsCount) * 100) : 0;
    $classifiedPct = ($totalExhibitsCount ?? 0) > 0 ? (int) round((($classifiedCount ?? 0) / $totalExhibitsCount) * 100) : 0;

    $periodNorm = strtolower(trim((string) ($period ?? 'general')));
    $activePeriodLabel = match (true) {
        in_array($periodNorm, ['weekly', 'week'], true) => 'Weekly Window',
        in_array($periodNorm, ['monthly', 'month'], true) => 'Monthly Window',
        in_array($periodNorm, ['annual', 'annually', 'year', 'yearly'], true) => 'Annual Window',
        default => 'Cumulative Vault',
    };

    $systemTablesList = !empty($systemGeneratedTableNames) && $systemGeneratedTableNames->isNotEmpty()
        ? $systemGeneratedTableNames->values()->all()
        : ['Precious Stones Exhibit Log', 'Weapons Exhibit Log', 'Ammunition Exhibit Log'];
    $notesByTitleMap = !empty($recentNotesByTitle) ? $recentNotesByTitle->toArray() : [];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Evidence Management Unit | Central Exhibits &amp; Seized Property Report | NACOC Forensic</title>

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

    <style>
        @media print {
            table {
                border: 2px solid #0f172a !important;
            }
            th, td {
                border: 1px solid #475569 !important;
                padding: 6px 8px !important;
            }
        }
    </style>
</head>
<body class="min-h-screen flex antialiased selection:bg-emerald-600 selection:text-white">

    <div class="flex h-screen w-full overflow-hidden">

        @if($isStaffReport)
            @include('staff.partials.sidebar', ['sidebarUnit' => 'Evidence Management'])
        @else
            @include('Crime_Scene_Support.partials.sidebar')
        @endif

        <div class="flex-1 flex flex-col min-w-0 bg-[#f3f5f8] overflow-hidden report-print-container">

            <!-- STICKY COMMAND HEADER -->
            <header class="h-16 bg-white/95 backdrop-blur-md border-b border-slate-200/90 px-5 sm:px-6 flex items-center justify-between gap-4 shrink-0 z-20">
                <div class="flex items-center gap-3.5 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-emerald-700 border border-slate-200/90 flex items-center justify-center shrink-0 shadow-2xs">
                        <i data-lucide="archive" class="w-4 h-4"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-[10px] font-extrabold font-mono-code uppercase tracking-widest text-emerald-700">
                                Evidence Management Unit
                            </span>
                            <span class="text-slate-300">/</span>
                            <span class="text-[10px] font-bold font-mono-code uppercase tracking-wider text-slate-500">
                                {{ $isStaffReport ? 'Staff Custodial Workstation' : 'Central Custodial Audit' }}
                            </span>
                        </div>
                        <h1 class="text-sm font-extrabold text-slate-900 tracking-tight truncate">
                            Central Exhibits &amp; Seized Property Report
                        </h1>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 shrink-0" id="printActionButtons">
                    <a href="{{ url('/Evidence_Management/vault') }}"
                       class="rep-btn-green-soft hidden md:inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold">
                        <i data-lucide="layout-grid" class="w-3.5 h-3.5"></i>
                        <span>Exhibit Vault</span>
                    </a>

                    <div class="hidden xl:flex items-center gap-2.5 px-3 border-x border-slate-200">
                        <div class="text-right">
                            <div class="text-xs font-bold text-slate-900 leading-tight truncate max-w-[150px]">{{ $officerFullname }}</div>
                            <div class="text-[10px] font-mono-code font-semibold text-slate-500">{{ now()->format('d M Y • H:i') }}</div>
                        </div>
                        <div class="w-8 h-8 rounded-xl bg-slate-100 border border-slate-200/90 text-slate-800 flex items-center justify-center font-mono-code font-extrabold text-[11px]">
                            {{ $officerInitials ?: 'EM' }}
                        </div>
                    </div>

                    <button type="button"
                            onclick="openReportNotesModal('print')"
                            class="rep-btn-green px-4 py-2.5 rounded-xl text-xs font-extrabold flex items-center gap-2 cursor-pointer">
                        <i data-lucide="printer" class="w-4 h-4"></i>
                        <span>Print / Export Official PDF</span>
                    </button>
                </div>
            </header>

            <!-- PRINT-ONLY BANNER -->
            <div class="print-only-header p-6 border-b border-slate-300 text-center">
                <div class="text-xs uppercase tracking-widest font-extrabold text-slate-600">Republic of Ghana &bull; Narcotics Control Commission</div>
                <h1 class="text-lg font-black text-slate-900 font-mono-code uppercase mt-1">Evidence Management Unit &bull; Exhibits Master Audit Report</h1>
                <div class="text-[11px] text-slate-600 mt-1 font-mono-code">
                    Generated: {{ now()->format('d M Y, H:i') }} | Officer: {{ $officerFullname }} | Total Ledger Records: {{ number_format($totalExhibitsCount ?? 0) }}
                </div>
            </div>

            <!-- SCROLLABLE WORKSPACE -->
            <main class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-5 custom-scrollbar">

                <!-- 1. EXECUTIVE GREY COMMAND MASTHEAD + 5-COLUMN TELEMETRY MATRIX -->
                <section class="rep-masthead" aria-label="Custodial Vault Telemetry">
                    <div class="rep-masthead-hero px-6 py-5 sm:px-7 sm:py-6">
                        <div class="rep-masthead-inner flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <div class="space-y-1.5 max-w-3xl">
                                <h2 class="text-lg sm:text-xl font-extrabold tracking-tight text-slate-900">
                                    Master Exhibit Ledger, QR Property Tagging &amp; Seized Valuation Audit
                                </h2>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    Authoritative custodial inventory tracking seized narcotics, weapons, vehicles, electronic media, currency lodgements, and forensic classification status.
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                                <div class="px-3.5 py-2 rounded-xl bg-white border border-slate-200/90 shadow-2xs">
                                    <div class="text-[9.5px] font-mono-code uppercase tracking-wider text-slate-500">Audit Scope</div>
                                    <div class="text-xs font-mono-code font-extrabold text-slate-900 mt-0.5">{{ $activePeriodLabel }}</div>
                                </div>
                                <div class="px-3.5 py-2 rounded-xl bg-emerald-50 border border-emerald-200/90 shadow-2xs">
                                    <div class="text-[9.5px] font-mono-code uppercase tracking-wider text-emerald-700">Matched Inventory</div>
                                    <div class="text-xs font-mono-code font-extrabold text-emerald-800 mt-0.5 tnum">{{ number_format($exhibits->total()) }} Exhibits</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 5-COLUMN PRECISION KPI STRIP -->
                    <div class="rep-kpi-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 divide-y sm:divide-y-0 sm:divide-x divide-slate-200/80">

                        <!-- Exhibits Lodged -->
                        <div class="rep-kpi-tile p-4 sm:px-5 flex flex-col justify-between">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-extrabold font-mono-code uppercase tracking-wider text-emerald-700">Exhibits Lodged</span>
                                <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200/70 flex items-center justify-center shrink-0">
                                    <i data-lucide="archive" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                            <div class="mt-2.5 flex items-baseline gap-2">
                                <span class="text-2xl font-black text-slate-900 font-mono-code tnum">{{ number_format($totalExhibitsCount) }}</span>
                                <span class="text-[11px] text-slate-500 font-semibold">Items</span>
                            </div>
                            <div class="mt-2 text-[10.5px] text-emerald-700 font-semibold">
                                Central vault inventory
                            </div>
                        </div>

                        <!-- Seizure Valuation -->
                        <div class="rep-kpi-tile p-4 sm:px-5 flex flex-col justify-between">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-extrabold font-mono-code uppercase tracking-wider text-teal-700">Seizure Valuation</span>
                                <span class="w-7 h-7 rounded-lg bg-teal-50 text-teal-700 border border-teal-200/70 flex items-center justify-center shrink-0">
                                    <i data-lucide="coins" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                            <div class="mt-2.5 flex items-baseline gap-1.5 min-w-0">
                                <span class="text-xs font-extrabold text-slate-500 font-mono-code">GH₵</span>
                                <span class="text-xl font-black text-slate-900 font-mono-code tnum truncate" title="GH₵ {{ number_format($totalSeizureValue, 2) }}">{{ number_format($totalSeizureValue, 2) }}</span>
                            </div>
                            <div class="mt-2 text-[10.5px] text-teal-700 font-semibold">
                                Appraised seized property
                            </div>
                        </div>

                        <!-- QR Tagged -->
                        <div class="rep-kpi-tile p-4 sm:px-5 flex flex-col justify-between">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-extrabold font-mono-code uppercase tracking-wider text-blue-700">QR Property Tags</span>
                                <span class="w-7 h-7 rounded-lg bg-blue-50 text-blue-700 border border-blue-200/70 flex items-center justify-center shrink-0">
                                    <i data-lucide="qr-code" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                            <div class="mt-2.5 flex items-baseline gap-2">
                                <span class="text-2xl font-black text-slate-900 font-mono-code tnum">{{ number_format($taggedQrCount) }}</span>
                                <span class="text-[11px] text-blue-700 font-bold font-mono-code">{{ $qrPct }}%</span>
                            </div>
                            <div class="mt-2 w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-600 rounded-full" style="width: {{ $qrPct }}%;"></div>
                            </div>
                        </div>

                        <!-- Classified Items -->
                        <div class="rep-kpi-tile p-4 sm:px-5 flex flex-col justify-between">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-extrabold font-mono-code uppercase tracking-wider text-purple-700">Classified Items</span>
                                <span class="w-7 h-7 rounded-lg bg-purple-50 text-purple-700 border border-purple-200/70 flex items-center justify-center shrink-0">
                                    <i data-lucide="microscope" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                            <div class="mt-2.5 flex items-baseline gap-2">
                                <span class="text-2xl font-black text-slate-900 font-mono-code tnum">{{ number_format($classifiedCount) }}</span>
                                <span class="text-[11px] text-purple-700 font-bold font-mono-code">{{ $classifiedPct }}%</span>
                            </div>
                            <div class="mt-2 w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-purple-600 rounded-full" style="width: {{ $classifiedPct }}%;"></div>
                            </div>
                        </div>

                        <!-- Registered Cases -->
                        <div class="rep-kpi-tile p-4 sm:px-5 flex flex-col justify-between">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-extrabold font-mono-code uppercase tracking-wider text-indigo-700">Linked Dockets</span>
                                <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200/70 flex items-center justify-center shrink-0">
                                    <i data-lucide="folder-lock" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                            <div class="mt-2.5 flex items-baseline gap-2">
                                <span class="text-2xl font-black text-slate-900 font-mono-code tnum">{{ number_format($totalCasesCount) }}</span>
                                <span class="text-[11px] text-slate-500 font-semibold">Cases</span>
                            </div>
                            <div class="mt-2 text-[10.5px] text-indigo-700 font-semibold">
                                Cross-unit dockets linked
                            </div>
                        </div>

                    </div>
                </section>

                <!-- 2. INSTRUMENT-GRADE AUDIT FILTER CONSOLE -->
                <section class="rep-surface-card p-5 sm:px-6 space-y-4" id="reportFilterConsole" aria-label="Exhibit Audit Filter Console">
                    <form method="GET" action="{{ $reportEndpoint }}" id="reportFilterForm" class="space-y-4">
                        <input type="hidden" name="period" id="periodInput" value="{{ $period }}">

                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3.5 border-b border-slate-100">
                            <div class="flex items-center gap-2.5">
                                <span class="w-7 h-7 rounded-lg bg-emerald-50 border border-emerald-200/70 text-emerald-700 flex items-center justify-center shrink-0">
                                    <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i>
                                </span>
                                <div>
                                    <h2 class="text-xs font-extrabold text-slate-900 font-mono-code uppercase tracking-wider">
                                        Custodial Audit Filter Parameters
                                    </h2>
                                    <p class="text-[11px] text-slate-500">
                                        Filter inventory by exhibit number, QR property ID, commodity category, custodial status, and seizure window.
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

                        <div class="flex flex-col xl:flex-row xl:items-end justify-between gap-3">
                            <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end min-w-0">

                                <!-- Search -->
                                <div class="lg:col-span-3 space-y-1.5">
                                    <label for="searchInput" class="block text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider">
                                        Search Exhibit / Case / Suspect
                                    </label>
                                    <div class="relative flex items-center">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 pointer-events-none">
                                            <i data-lucide="search" class="w-3.5 h-3.5"></i>
                                        </span>
                                        <input type="text"
                                               name="search"
                                               id="searchInput"
                                               value="{{ $search }}"
                                               placeholder="Exhibit #, property ID, commodity, suspect..."
                                               class="rep-input w-full pl-9 pr-10 py-2 text-xs font-mono-code font-semibold">
                                        <button type="submit"
                                                title="Search"
                                                class="rep-btn-green absolute right-1 top-1/2 -translate-y-1/2 w-7 h-7 rounded-lg flex items-center justify-center cursor-pointer">
                                            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Category -->
                                <div class="lg:col-span-2 space-y-1.5">
                                    <label for="categorySelect" class="block text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider">
                                        Commodity Category
                                    </label>
                                    <select name="category" id="categorySelect" onchange="this.form.submit()" class="rep-select w-full px-3 py-2 text-xs font-semibold">
                                        <option value="all" {{ ($category === 'all' || empty($category)) ? 'selected' : '' }}>All Categories ({{ $totalExhibitsCount }})</option>
                                        <option value="drug" {{ str_contains(strtolower($category), 'drug') ? 'selected' : '' }}>Drugs &amp; Narcotics ({{ $drugsCount }})</option>
                                        <option value="weapon" {{ str_contains(strtolower($category), 'weapon') ? 'selected' : '' }}>Weapons &amp; Firearms ({{ $weaponsCount }})</option>
                                        <option value="vehicle" {{ str_contains(strtolower($category), 'vehicle') ? 'selected' : '' }}>Vehicles &amp; Transport ({{ $vehiclesCount }})</option>
                                        <option value="device" {{ str_contains(strtolower($category), 'device') ? 'selected' : '' }}>Digital Devices ({{ $devicesCount }})</option>
                                        <option value="money" {{ str_contains(strtolower($category), 'money') ? 'selected' : '' }}>Currency / Cash ({{ $currencyCount }})</option>
                                        <option value="general" {{ str_contains(strtolower($category), 'general') ? 'selected' : '' }}>General Physical Items</option>
                                    </select>
                                </div>

                                <!-- Vault Status -->
                                <div class="lg:col-span-2 space-y-1.5">
                                    <label for="actionStatusSelect" class="block text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider">
                                        Vault Status
                                    </label>
                                    <select name="action_status" id="actionStatusSelect" onchange="this.form.submit()" class="rep-select w-full px-3 py-2 text-xs font-semibold">
                                        <option value="all" {{ ($actionStatus === 'all' || empty($actionStatus)) ? 'selected' : '' }}>All Statuses</option>
                                        <option value="completed" {{ $actionStatus === 'completed' ? 'selected' : '' }}>Tagged &amp; Classified ({{ $classifiedCount }})</option>
                                        <option value="pending" {{ $actionStatus === 'pending' ? 'selected' : '' }}>Needs QR / Classify ({{ $pendingCount }})</option>
                                        <option value="unassigned_qr" {{ $actionStatus === 'unassigned_qr' ? 'selected' : '' }}>Unassigned QR Tag</option>
                                        <option value="unclassified" {{ $actionStatus === 'unclassified' ? 'selected' : '' }}>Unclassified Exhibits</option>
                                    </select>
                                </div>

                                <!-- Date From -->
                                <div class="lg:col-span-2 space-y-1.5">
                                    <label for="fromDate" class="block text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider">
                                        From Date
                                    </label>
                                    <input type="date"
                                           name="from_date"
                                           id="fromDate"
                                           value="{{ $fromDate }}"
                                           onchange="document.getElementById('periodInput').value = '';"
                                           class="rep-input w-full px-2.5 py-2 text-xs font-mono-code font-semibold">
                                </div>

                                <!-- Date To -->
                                <div class="lg:col-span-2 space-y-1.5">
                                    <label for="toDate" class="block text-[10.5px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider">
                                        To Date
                                    </label>
                                    <input type="date"
                                           name="to_date"
                                           id="toDate"
                                           value="{{ $toDate }}"
                                           onchange="document.getElementById('periodInput').value = '';"
                                           class="rep-input w-full px-2.5 py-2 text-xs font-mono-code font-semibold">
                                </div>

                                <!-- Reset -->
                                <div class="lg:col-span-1 space-y-1.5">
                                    <a href="{{ $reportEndpoint }}"
                                       class="rep-btn-green-soft w-full block py-2 text-xs rounded-xl font-bold font-mono-code whitespace-nowrap cursor-pointer text-center"
                                       title="Reset all filters">
                                        Reset
                                    </a>
                                </div>

                            </div>

                            <!-- Generate Filtered PDF -->
                            <div class="shrink-0">
                                <button type="button"
                                        onclick="openReportNotesModal('filtered')"
                                        class="rep-btn-green w-full xl:w-auto px-5 py-2.5 rounded-xl text-xs font-extrabold flex items-center justify-center gap-2 cursor-pointer whitespace-nowrap">
                                    <i data-lucide="file-check-2" class="w-4 h-4"></i>
                                    <span id="generateBtnLabel">Generate Filtered PDF Report</span>
                                </button>
                            </div>
                        </div>

                        <!-- Secondary Row -->
                        <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-100">
                            <div class="flex items-center gap-3 flex-wrap">
                                <div class="flex items-center gap-2">
                                    <label for="sortSelect" class="text-[10.5px] font-extrabold text-slate-500 font-mono-code uppercase">Sort</label>
                                    <select name="sort" id="sortSelect" onchange="this.form.submit()" class="rep-select px-3 py-1.5 text-xs font-semibold">
                                        <option value="newest" {{ ($sort ?? 'newest') === 'newest' ? 'selected' : '' }}>Newest Lodged</option>
                                        <option value="oldest" {{ ($sort ?? '') === 'oldest' ? 'selected' : '' }}>Oldest Lodged</option>
                                        <option value="value_desc" {{ ($sort ?? '') === 'value_desc' ? 'selected' : '' }}>Highest Value (GH₵)</option>
                                        <option value="value_asc" {{ ($sort ?? '') === 'value_asc' ? 'selected' : '' }}>Lowest Value (GH₵)</option>
                                        <option value="name_asc" {{ ($sort ?? '') === 'name_asc' ? 'selected' : '' }}>Commodity Name (A–Z)</option>
                                    </select>
                                </div>

                                <div class="flex items-center gap-2">
                                    <label for="perPageSelect" class="text-[10.5px] font-extrabold text-slate-500 font-mono-code uppercase">Rows</label>
                                    <select name="per_page" id="perPageSelect" onchange="this.form.submit()" class="rep-select px-3 py-1.5 text-xs font-semibold">
                                        <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25 / page</option>
                                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 / page</option>
                                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 / page</option>
                                        <option value="200" {{ $perPage == 200 ? 'selected' : '' }}>200 / page</option>
                                    </select>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="submit" class="rep-btn-green px-4 py-1.5 font-bold text-xs rounded-xl flex items-center gap-1.5 cursor-pointer">
                                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                                    <span>Apply Filters</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </section>

                <!-- 3. EXHIBITS MASTER LEDGER TABLE -->
                <section class="rep-surface-card overflow-hidden" aria-label="Exhibits Master Ledger">
                    <div class="px-6 py-4 bg-slate-50/70 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-white border border-slate-200/90 text-emerald-700 flex items-center justify-center shrink-0 shadow-2xs">
                                <i data-lucide="package-check" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 tracking-tight">
                                        Exhibits Master Audit Ledger
                                    </h3>
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200/80 text-[10px] font-mono-code font-extrabold tnum">
                                        {{ number_format($exhibits->total()) }} Records
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500">
                                    Click <strong class="text-slate-700">View</strong> on any item to inspect full packaging, weight, custody status, and appraisal remarks.
                                </p>
                            </div>
                        </div>

                        <div class="text-[11px] text-slate-500 font-semibold font-mono-code tnum">
                            Showing <span class="font-extrabold text-slate-900">{{ $exhibits->firstItem() ?? 0 }}</span>–<span class="font-extrabold text-slate-900">{{ $exhibits->lastItem() ?? 0 }}</span> of <span class="font-extrabold text-slate-900">{{ number_format($exhibits->total()) }}</span>
                        </div>
                    </div>

                    @if($exhibits->isEmpty())
                        <div class="p-14 text-center space-y-3">
                            <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400">
                                <i data-lucide="package-x" class="w-6 h-6"></i>
                            </div>
                            <h4 class="text-sm font-extrabold text-slate-900 font-mono-code uppercase tracking-wider">No Matching Exhibits Found</h4>
                            <p class="text-xs text-slate-500 max-w-md mx-auto">
                                No custodial exhibit records matched your active filter parameters. Clear your filters or broaden your date range.
                            </p>
                            <div>
                                <a href="{{ $reportEndpoint }}" class="rep-btn-green inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold">
                                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                    <span>Clear Filters &amp; View All</span>
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="overflow-x-auto custom-scrollbar">
                            <table class="rep-table w-full min-w-[1120px] text-left border-collapse text-xs">
                                <thead class="sticky top-0 z-10 select-none">
                                    <tr>
                                        <th scope="col" class="py-3.5 px-4 text-center w-12 whitespace-nowrap">#</th>
                                        <th scope="col" class="py-3.5 px-4 w-[12%] min-w-[135px] whitespace-nowrap">Property Tag &amp; QR</th>
                                        <th scope="col" class="py-3.5 px-4 w-[11%] min-w-[125px] whitespace-nowrap">Exhibit Number</th>
                                        <th scope="col" class="py-3.5 px-4 w-[18%] min-w-[185px] whitespace-nowrap">Commodity / Property</th>
                                        <th scope="col" class="py-3.5 px-4 w-[13%] min-w-[135px] whitespace-nowrap">Category &amp; Schedule</th>
                                        <th scope="col" class="py-3.5 px-4 w-[16%] min-w-[170px] whitespace-nowrap">Docket &amp; Suspect(s)</th>
                                        <th scope="col" class="py-3.5 px-4 w-[9%] min-w-[105px] text-center whitespace-nowrap">Date Seized</th>
                                        <th scope="col" class="py-3.5 px-4 w-[10%] min-w-[120px] text-right whitespace-nowrap">Est. Value (GH₵)</th>
                                        <th scope="col" class="py-3.5 px-4 w-[6%] min-w-[85px] text-center whitespace-nowrap">Custody</th>
                                        <th scope="col" class="py-3.5 px-5 w-[5%] min-w-[75px] text-right whitespace-nowrap no-print">Inspect</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white font-medium text-slate-700">
                                    @foreach($exhibits as $index => $exhibit)
                                        @php
                                            $categoryLower = strtolower($exhibit->exhibit_category ?? 'general');
                                            $catBadge = match (true) {
                                                str_contains($categoryLower, 'drug') => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                                str_contains($categoryLower, 'weapon') => 'bg-amber-50 text-amber-800 border-amber-200',
                                                str_contains($categoryLower, 'vehic') => 'bg-blue-50 text-blue-800 border-blue-200',
                                                str_contains($categoryLower, 'devic') => 'bg-purple-50 text-purple-800 border-purple-200',
                                                str_contains($categoryLower, 'money') || str_contains($categoryLower, 'cash') => 'bg-teal-50 text-teal-800 border-teal-200',
                                                default => 'bg-slate-100 text-slate-700 border-slate-200'
                                            };

                                            $suspectDisplay = 'Unlinked / No Suspect';
                                            if ($exhibit->singleSuspectCase) {
                                                $suspectDisplay = $exhibit->singleSuspectCase->suspect_name;
                                            } elseif ($exhibit->suspect) {
                                                $suspectDisplay = $exhibit->suspect->suspect_name;
                                            } elseif ($exhibit->multipleSuspectCase && $exhibit->multipleSuspectCase->suspects->isNotEmpty()) {
                                                $suspectDisplay = $exhibit->multipleSuspectCase->suspects->first()->suspect_name;
                                                if ($exhibit->multipleSuspectCase->suspects->count() > 1) {
                                                    $suspectDisplay .= ' (+' . ($exhibit->multipleSuspectCase->suspects->count() - 1) . ')';
                                                }
                                            }

                                            $caseNum = $exhibit->singleSuspectCase->case_number ?? $exhibit->multipleSuspectCase->case_number ?? 'No Case Assigned';
                                        @endphp
                                        <tr>
                                            <td class="py-3.5 px-4 text-center font-mono-code text-[11px] text-slate-400 tnum">
                                                {{ ($exhibits->currentPage() - 1) * $exhibits->perPage() + $index + 1 }}
                                            </td>

                                            <!-- Property ID & QR -->
                                            <td class="py-3.5 px-4 font-mono-code whitespace-nowrap">
                                                @if($exhibit->property_id)
                                                    <span class="font-extrabold text-slate-900 block text-[11px]">{{ $exhibit->property_id }}</span>
                                                    <span class="inline-flex items-center gap-1 text-[9.5px] text-emerald-700 font-bold uppercase tracking-wider mt-0.5">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                        <span>QR Verified</span>
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-extrabold uppercase bg-amber-50 text-amber-800 border border-amber-200">
                                                        Unassigned
                                                    </span>
                                                @endif
                                            </td>

                                            <!-- Exhibit Number -->
                                            <td class="py-3.5 px-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 border border-slate-200 font-mono-code font-extrabold text-slate-900 text-[11px]">
                                                    {{ $exhibit->exhibit_number }}
                                                </span>
                                            </td>

                                            <!-- Commodity Name -->
                                            <td class="py-3.5 px-4">
                                                <div class="font-bold text-slate-900">{{ $exhibit->commodity_name }}</div>
                                                <div class="text-[10.5px] text-slate-400 truncate max-w-[220px] mt-0.5" title="{{ $exhibit->packaging_type }}">
                                                    {{ $exhibit->packaging_type ?: 'Standard Secure Packaging' }}
                                                </div>
                                            </td>

                                            <!-- Category & Schedule -->
                                            <td class="py-3.5 px-4">
                                                <div class="space-y-1">
                                                    <span class="inline-block px-2 py-0.5 rounded-md text-[9.5px] font-extrabold font-mono-code uppercase border {{ $catBadge }}">
                                                        {{ $exhibit->exhibit_category ?? 'General' }}
                                                    </span>
                                                    @if($exhibit->drug_classification)
                                                        <div class="text-[10px] text-emerald-800 font-bold font-mono-code truncate max-w-[150px]">
                                                            {{ $exhibit->drug_classification }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>

                                            <!-- Case & Suspect -->
                                            <td class="py-3.5 px-4">
                                                <div class="font-bold text-slate-900 truncate max-w-[190px]" title="{{ $suspectDisplay }}">
                                                    {{ $suspectDisplay }}
                                                </div>
                                                <div class="font-mono-code text-[10.5px] text-slate-500 mt-0.5">
                                                    {{ $caseNum }}
                                                </div>
                                            </td>

                                            <!-- Date Seized -->
                                            <td class="py-3.5 px-4 text-center font-mono-code text-[11px] text-slate-600 whitespace-nowrap tnum">
                                                {{ $exhibit->date_seized ? \Carbon\Carbon::parse($exhibit->date_seized)->format('d M Y') : ($exhibit->created_at ? $exhibit->created_at->format('d M Y') : 'N/A') }}
                                            </td>

                                            <!-- Valuation -->
                                            <td class="py-3.5 px-4 text-right font-mono-code font-extrabold text-slate-900 text-xs whitespace-nowrap tnum">
                                                {{ $exhibit->seizure_value ? number_format((float) $exhibit->seizure_value, 2) : '0.00' }}
                                            </td>

                                            <!-- Custodial Status -->
                                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold font-mono-code bg-slate-100 text-slate-700 border border-slate-200/80">
                                                    {{ $exhibit->custodial_status ?? 'Seized' }}
                                                </span>
                                            </td>

                                            <!-- Inspect -->
                                            <td class="py-3.5 px-5 text-right no-print whitespace-nowrap">
                                                <button type="button"
                                                        onclick="openReportExhibitDetails({{ json_encode($exhibit) }})"
                                                        class="rep-btn-green px-2.5 py-1.5 rounded-lg cursor-pointer inline-flex items-center gap-1 text-[10.5px] font-mono-code font-bold"
                                                        title="Inspect Exhibit Dossier">
                                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                    <span>View</span>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="rep-pagination px-6 py-4 border-t border-slate-200/80 bg-slate-50/60 flex flex-col sm:flex-row items-center justify-between gap-3" id="paginationSection">
                            <div class="text-[11px] text-slate-500 font-mono-code tnum">
                                Showing <span class="font-extrabold text-slate-900">{{ $exhibits->firstItem() ?? 0 }}</span>–<span class="font-extrabold text-slate-900">{{ $exhibits->lastItem() ?? 0 }}</span> of <span class="font-extrabold text-slate-900">{{ number_format($exhibits->total()) }}</span> total exhibits
                            </div>
                            <div>
                                {{ $exhibits->links() }}
                            </div>
                        </div>
                    @endif
                </section>

            </main>
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- 4. EXHIBIT DOSSIER INSPECTION MODAL (GREY HEADER)          -->
    <!-- ========================================================== -->
    <div id="dossierModal" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 overflow-y-auto no-print" role="dialog" aria-modal="true">
        <div class="bg-white rounded-3xl max-w-3xl w-full border border-slate-200 shadow-2xl overflow-hidden flex flex-col">

            <div class="rep-masthead-hero px-6 py-5 flex items-center justify-between shrink-0">
                <div class="rep-masthead-inner flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-white text-emerald-700 border border-slate-200/90 flex items-center justify-center shadow-2xs">
                        <i data-lucide="archive" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-sm sm:text-base font-extrabold tracking-tight text-slate-900" id="modalCommodityTitle">Exhibit Dossier</h2>
                        <div class="flex items-center gap-2 text-[11px] text-slate-600 font-mono-code mt-0.5">
                            <span class="text-emerald-700 font-bold" id="modalExhibitNum">EXH-0000</span>
                            <span class="text-slate-400">&bull;</span>
                            <span id="modalPropertyId">Property ID: Unassigned</span>
                        </div>
                    </div>
                </div>
                <button type="button" onclick="closeReportDossierModal()" class="rep-masthead-inner rep-btn-green w-8 h-8 rounded-xl flex items-center justify-center cursor-pointer" aria-label="Close">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="p-6 space-y-4 text-xs font-medium text-slate-700 bg-slate-50/60">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 font-mono-code">
                    <div class="bg-white p-3.5 rounded-2xl border border-slate-200/90 shadow-2xs">
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Category</span>
                        <span class="text-xs font-extrabold text-slate-900 block mt-1" id="modalCategory">General</span>
                    </div>
                    <div class="bg-white p-3.5 rounded-2xl border border-slate-200/90 shadow-2xs">
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Classification</span>
                        <span class="text-xs font-extrabold text-emerald-700 block mt-1" id="modalClassification">N/A</span>
                    </div>
                    <div class="bg-white p-3.5 rounded-2xl border border-slate-200/90 shadow-2xs">
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Quantity / Weight</span>
                        <span class="text-xs font-extrabold text-slate-900 block mt-1 tnum" id="modalWeight">N/A</span>
                    </div>
                    <div class="bg-white p-3.5 rounded-2xl border border-slate-200/90 shadow-2xs">
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Seizure Valuation</span>
                        <span class="text-xs font-extrabold text-teal-700 block mt-1 tnum" id="modalValue">GH₵ 0.00</span>
                    </div>
                </div>

                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/90 space-y-2.5">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <span class="text-slate-500 font-semibold">Packaging Specification</span>
                        <span class="font-bold text-slate-900" id="modalPackaging">N/A</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <span class="text-slate-500 font-semibold">Date of Seizure / Lodgement</span>
                        <span class="font-mono-code font-bold text-slate-900 tnum" id="modalDate">N/A</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <span class="text-slate-500 font-semibold">Custodial Status</span>
                        <span class="font-mono-code font-extrabold text-emerald-800 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full text-[10px]" id="modalCustody">Seized</span>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <span class="text-slate-500 font-semibold shrink-0">Lodgement Remarks</span>
                        <span class="font-medium text-slate-700 max-w-md text-right leading-relaxed" id="modalRemarks">No remarks registered</span>
                    </div>
                </div>
            </div>

            <div class="px-6 py-3.5 bg-white border-t border-slate-200 flex items-center justify-end">
                <button type="button" onclick="closeReportDossierModal()" class="rep-btn-green px-4 py-2 font-bold text-xs rounded-xl cursor-pointer">
                    Close Dossier
                </button>
            </div>

        </div>
    </div>

    <!-- ========================================================== -->
    <!-- 5. TABLE NOTES MODAL (GREY HEADER + GREEN BUTTONS)         -->
    <!-- ========================================================== -->
    <div id="reportNotesModal" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5 overflow-y-auto no-print" role="dialog" aria-modal="true">
        <div class="bg-white rounded-3xl max-w-5xl w-full border border-slate-200 shadow-2xl overflow-hidden flex flex-col my-auto max-h-[92vh]">

            <div class="rep-masthead-hero px-6 py-5 flex items-center justify-between shrink-0">
                <div class="rep-masthead-inner flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-white text-emerald-700 border border-slate-200/90 flex items-center justify-center shrink-0 shadow-2xs">
                        <i data-lucide="file-signature" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm sm:text-base font-extrabold tracking-wide uppercase font-mono-code text-slate-900">Report Ledger Table Notes</h2>
                            <span class="px-2 py-0.5 rounded-full text-[9.5px] font-extrabold bg-emerald-50 text-emerald-800 border border-emerald-200 font-mono-code uppercase">Optional</span>
                        </div>
                        <p class="text-[11px] text-slate-600 mt-0.5">
                            Append official custodial audit annotations beneath individual ledger tables in the exported PDF dossier.
                        </p>
                    </div>
                </div>
                <button type="button" onclick="closeReportNotesModal()" class="rep-masthead-inner rep-btn-green w-8 h-8 rounded-xl flex items-center justify-center cursor-pointer shrink-0" aria-label="Close">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="p-5 sm:p-6 space-y-5 overflow-y-auto flex-1 bg-slate-50/60 text-slate-800 text-xs custom-scrollbar">

                <div class="p-3.5 bg-emerald-50/90 border border-emerald-200 rounded-2xl flex items-start gap-3">
                    <i data-lucide="info" class="w-4 h-4 text-emerald-700 shrink-0 mt-0.5"></i>
                    <p class="text-xs text-emerald-950 leading-relaxed">
                        Audit notes entered below are persisted to the Central Evidence database and rendered directly beneath their corresponding ledger tables in the printed dossier.
                    </p>
                </div>

                <!-- Table 2: Physical Assets -->
                <div class="p-4 bg-white border border-slate-200/90 rounded-2xl shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 font-mono-code font-bold text-[9.5px]">T2</span>
                            <h3 class="font-extrabold text-slate-900 text-xs sm:text-[13px] uppercase tracking-wide">
                                Physical Assets &amp; Non-Drug Seized Property Log
                            </h3>
                        </div>
                        <span class="text-[10px] font-mono-code font-bold text-blue-700 bg-blue-50 px-2.5 py-0.5 rounded-md border border-blue-200">
                            Table 2
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500">
                        Vehicles, electronic devices, and non-drug physical property seized during operations.
                    </p>
                    <textarea id="modalNoteAssets"
                              rows="3"
                              class="rep-input w-full p-3 text-xs leading-relaxed"
                              placeholder="Enter optional audit note for Physical Assets & Non-Drug Seized Property Log...">{{ $recentNotes['assets']->note ?? '' }}</textarea>
                </div>

                <!-- Table 3: Seized Currency -->
                <div class="p-4 bg-white border border-slate-200/90 rounded-2xl shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 font-mono-code font-bold text-[9.5px]">T3</span>
                            <h3 class="font-extrabold text-slate-900 text-xs sm:text-[13px] uppercase tracking-wide">
                                Seized Currency &amp; Money Received Register
                            </h3>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold font-mono-code bg-emerald-50 text-emerald-800 border border-emerald-200 tnum">
                                <span class="text-[9.5px] uppercase font-semibold text-emerald-600">Total:</span>
                                <span>GH₵ {{ number_format($totalMoneyAmount ?? 0, 2) }}</span>
                            </span>
                        </div>
                        <span class="text-[10px] font-mono-code font-bold text-amber-700 bg-amber-50 px-2.5 py-0.5 rounded-md border border-amber-200">
                            Table 3
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500">
                        Monetary funds, cash notes, foreign currencies, and Central Vault lodgements.
                    </p>
                    <textarea id="modalNoteMoney"
                              rows="3"
                              class="rep-input w-full p-3 text-xs leading-relaxed"
                              placeholder="Enter optional audit note for Seized Currency & Money Received Register...">{{ $recentNotes['money']->note ?? '' }}</textarea>
                </div>

                <!-- Dynamic System Tables -->
                <div class="space-y-3 pt-1">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div>
                            <h3 class="font-extrabold text-slate-900 text-xs uppercase tracking-wide flex items-center gap-1.5">
                                <i data-lucide="layers" class="w-4 h-4 text-emerald-600"></i>
                                <span>Additional Category Tables</span>
                            </h3>
                            <div class="text-[11px] text-slate-500 flex items-center gap-1.5 flex-wrap mt-1">
                                <span>System-assigned tables:</span>
                                @foreach($systemTablesList as $tName)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold font-mono-code bg-white text-slate-700 border border-slate-200">
                                        {{ $tName }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        <button type="button"
                                id="addNewTableNoteBtn"
                                onclick="addDynamicTableNoteRow()"
                                class="rep-btn-green px-3.5 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 cursor-pointer">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            <span>Add Table Note</span>
                        </button>
                    </div>

                    <div id="dynamicNotesContainer" class="space-y-3"></div>
                </div>

            </div>

            <div class="px-6 py-4 bg-white border-t border-slate-200 flex flex-wrap items-center justify-between gap-3 shrink-0">
                <button type="button"
                        onclick="proceedWithoutNotes()"
                        class="rep-btn-green-soft px-4 py-2.5 font-bold text-xs rounded-xl cursor-pointer">
                    Skip &bull; Generate Without Notes
                </button>

                <div class="flex items-center gap-2.5">
                    <button type="button"
                            onclick="closeReportNotesModal()"
                            class="rep-btn-green-soft px-4 py-2.5 font-bold text-xs rounded-xl cursor-pointer">
                        Cancel
                    </button>
                    <button type="button"
                            id="submitNotesBtn"
                            onclick="submitReportWithNotes()"
                            class="rep-btn-green px-5 py-2.5 font-extrabold text-xs rounded-xl cursor-pointer flex items-center gap-2">
                        <i data-lucide="file-check-2" class="w-4 h-4" id="submitNotesIcon"></i>
                        <span id="submitNotesText">Save Notes &amp; Generate PDF</span>
                    </button>
                </div>
            </div>

        </div>
    </div>

    <script>
        window.systemAssignedTableNames = @json($systemTablesList);
        window.recentNotesByTitle = @json($notesByTitleMap);
        let currentReportModalMode = 'filtered';

        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });

        function setReportPeriod(period) {
            document.getElementById('periodInput').value = period;
            document.getElementById('fromDate').value = '';
            document.getElementById('toDate').value = '';
            document.getElementById('reportFilterForm').submit();
        }

        function openReportNotesModal(mode = 'filtered') {
            currentReportModalMode = mode;
            const modal = document.getElementById('reportNotesModal');
            if (!modal) return;

            const submitBtnText = document.getElementById('submitNotesText');
            if (submitBtnText) {
                submitBtnText.textContent = (mode === 'print')
                    ? 'Save Notes & Print / Export PDF'
                    : 'Save Notes & Generate PDF';
            }

            modal.classList.remove('hidden');
            if (window.lucide) window.lucide.createIcons();
        }

        function closeReportNotesModal() {
            const modal = document.getElementById('reportNotesModal');
            if (modal) modal.classList.add('hidden');
        }

        function getNextSystemAssignedTableName() {
            const systemTables = window.systemAssignedTableNames || [
                'Precious Stones Exhibit Log',
                'Weapons Exhibit Log',
                'Ammunition Exhibit Log'
            ];

            const currentlyPicked = Array.from(document.querySelectorAll('.dynamic-note-item .dyn-table-name-val'))
                .map(input => input.value.trim());

            for (const name of systemTables) {
                if (!currentlyPicked.includes(name)) {
                    return name;
                }
            }

            return systemTables[0] || 'General Exhibits Log';
        }

        function addDynamicTableNoteRow(forcedTitle = '', defaultNote = '') {
            const container = document.getElementById('dynamicNotesContainer');
            if (!container) return;

            const rowId = 'dynNoteRow_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
            const systemTables = window.systemAssignedTableNames || [
                'Precious Stones Exhibit Log',
                'Weapons Exhibit Log',
                'Ammunition Exhibit Log'
            ];

            const autoTableName = forcedTitle ? forcedTitle : getNextSystemAssignedTableName();
            const isKnown = systemTables.includes(autoTableName);
            const isCustom = !isKnown;

            const optionsHtml = systemTables.map(t => {
                const sel = (t === autoTableName) ? 'selected' : '';
                return `<option value="${t}" ${sel}>${t}</option>`;
            }).join('') + `<option value="__custom__" ${isCustom ? 'selected' : ''}>Other Custom Table...</option>`;

            const prefillNote = defaultNote || (window.recentNotesByTitle || {})[autoTableName.toLowerCase().trim()] || '';

            const card = document.createElement('div');
            card.id = rowId;
            card.className = 'dynamic-note-item p-4 bg-white border border-slate-200/90 rounded-2xl shadow-2xs space-y-2.5';
            card.innerHTML = `
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <div class="flex-1 flex flex-wrap items-center gap-2">
                        <label class="text-[10.5px] font-extrabold text-slate-500 uppercase font-mono-code shrink-0">Target Table:</label>
                        <span class="dyn-table-picked-badge text-xs font-extrabold uppercase font-mono-code bg-emerald-50 text-emerald-900 border border-emerald-200 px-3 py-1 rounded-xl">
                            ${autoTableName}
                        </span>
                        <input type="hidden" class="dyn-table-name-val" value="${autoTableName}">
                        <select onchange="handleDynTableChange(this, '${rowId}')"
                                class="dyn-table-select rep-select px-2.5 py-1 text-xs font-semibold">
                            ${optionsHtml}
                        </select>
                        <input type="text"
                               oninput="handleDynCustomNameInput(this, '${rowId}')"
                               class="dyn-table-custom-input ${isCustom ? '' : 'hidden'} rep-input px-3 py-1 text-xs font-semibold flex-1 min-w-[200px]"
                               placeholder="Type exact table title"
                               value="${isCustom ? autoTableName : ''}">
                    </div>
                    <button type="button"
                            onclick="document.getElementById('${rowId}').remove()"
                            class="rep-btn-green-soft p-1.5 px-2.5 rounded-lg text-xs font-bold flex items-center gap-1 cursor-pointer shrink-0">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        <span>Remove</span>
                    </button>
                </div>
                <textarea rows="2"
                          class="dyn-note-text rep-input w-full p-3 text-xs leading-relaxed"
                          placeholder="Enter optional audit note for ${autoTableName}...">${prefillNote}</textarea>
            `;

            container.appendChild(card);
            if (window.lucide) window.lucide.createIcons();
        }

        function handleDynTableChange(selectEl, rowId) {
            const row = document.getElementById(rowId);
            if (!row) return;

            const customInput = row.querySelector('.dyn-table-custom-input');
            const badge = row.querySelector('.dyn-table-picked-badge');
            const hiddenInput = row.querySelector('.dyn-table-name-val');
            const textarea = row.querySelector('.dyn-note-text');

            if (selectEl.value === '__custom__') {
                if (customInput) {
                    customInput.classList.remove('hidden');
                    customInput.focus();
                }
                const customVal = customInput ? customInput.value.trim() : 'Custom Table Log';
                if (badge) badge.textContent = customVal || 'Custom Table Log';
                if (hiddenInput) hiddenInput.value = customVal || 'Custom Table Log';
            } else {
                if (customInput) customInput.classList.add('hidden');
                const selectedVal = selectEl.value;
                if (badge) badge.textContent = selectedVal;
                if (hiddenInput) hiddenInput.value = selectedVal;

                if (textarea) {
                    textarea.placeholder = `Enter optional audit note for ${selectedVal}...`;
                    const savedNote = (window.recentNotesByTitle || {})[selectedVal.toLowerCase().trim()];
                    if (savedNote && !textarea.value.trim()) {
                        textarea.value = savedNote;
                    }
                }
            }
        }

        function handleDynCustomNameInput(inputEl, rowId) {
            const row = document.getElementById(rowId);
            if (!row) return;
            const val = inputEl.value.trim() || 'Custom Table Log';
            const badge = row.querySelector('.dyn-table-picked-badge');
            const hiddenInput = row.querySelector('.dyn-table-name-val');
            const textarea = row.querySelector('.dyn-note-text');
            if (badge) badge.textContent = val;
            if (hiddenInput) hiddenInput.value = val;
            if (textarea) textarea.placeholder = `Enter optional audit note for ${val}...`;
        }

        async function submitReportWithNotes() {
            const btn = document.getElementById('submitNotesBtn');
            const btnText = document.getElementById('submitNotesText');
            const originalText = btnText ? btnText.innerText : 'Save Notes & Generate PDF';

            const notesPayload = [];

            const assetNote = document.getElementById('modalNoteAssets')?.value?.trim();
            if (assetNote) {
                notesPayload.push({
                    table_key: 'assets',
                    table_title: 'Physical Assets & Non-Drug Seized Property Log',
                    note: assetNote
                });
            }

            const moneyNote = document.getElementById('modalNoteMoney')?.value?.trim();
            if (moneyNote) {
                notesPayload.push({
                    table_key: 'money',
                    table_title: 'Seized Currency & Money Received Register',
                    note: moneyNote
                });
            }

            const dynItems = document.querySelectorAll('.dynamic-note-item');
            dynItems.forEach(item => {
                const title = item.querySelector('.dyn-table-name-val')?.value?.trim()
                    || item.querySelector('.dyn-table-select')?.value?.trim()
                    || 'Generated Table Log';
                const textarea = item.querySelector('.dyn-note-text');
                const noteVal = textarea ? textarea.value.trim() : '';
                if (!noteVal || !title) return;

                const slugKey = 'dyn_' + title.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
                notesPayload.push({
                    table_key: slugKey,
                    table_title: title,
                    note: noteVal
                });
            });

            const period = document.getElementById('periodInput')?.value || 'general';
            const search = document.getElementById('searchInput')?.value || '';
            const category = document.getElementById('categorySelect')?.value || 'all';
            const actionStatus = document.getElementById('actionStatusSelect')?.value || 'all';
            const from = document.getElementById('fromDate')?.value || '';
            const to = document.getElementById('toDate')?.value || '';
            const sort = document.querySelector('select[name="sort"]')?.value || 'newest';

            let batchId = null;

            if (notesPayload.length > 0) {
                try {
                    if (btnText) btnText.innerText = 'Saving Notes...';
                    if (btn) btn.disabled = true;

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                        || '{{ csrf_token() }}';

                    const response = await fetch('{{ url("/Evidence_Management/report/notes") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            notes: notesPayload,
                            period: period,
                            from_date: from,
                            to_date: to
                        })
                    });

                    const data = await response.json();
                    if (data && data.success && data.batch_id) {
                        batchId = data.batch_id;
                    }
                } catch (err) {
                    console.error('Failed to save table notes:', err);
                } finally {
                    if (btnText) btnText.innerText = originalText;
                    if (btn) btn.disabled = false;
                }
            }

            const params = new URLSearchParams();
            if (period && period !== 'general') params.set('period', period);
            if (search) params.set('search', search);
            if (category && category !== 'all') params.set('category', category);
            if (actionStatus && actionStatus !== 'all') params.set('action_status', actionStatus);
            if (from) params.set('from_date', from);
            if (to) params.set('to_date', to);
            if (sort && sort !== 'newest') params.set('sort', sort);
            if (batchId) params.set('batch_id', batchId);
            if (currentReportModalMode === 'print') params.set('autoprint', '1');

            closeReportNotesModal();
            const targetUrl = '{{ url("/Evidence_Management/report_pdf") }}?' + params.toString();
            window.open(targetUrl, '_blank');
        }

        function proceedWithoutNotes() {
            const period = document.getElementById('periodInput')?.value || 'general';
            const search = document.getElementById('searchInput')?.value || '';
            const category = document.getElementById('categorySelect')?.value || 'all';
            const actionStatus = document.getElementById('actionStatusSelect')?.value || 'all';
            const from = document.getElementById('fromDate')?.value || '';
            const to = document.getElementById('toDate')?.value || '';
            const sort = document.querySelector('select[name="sort"]')?.value || 'newest';

            const params = new URLSearchParams();
            if (period && period !== 'general') params.set('period', period);
            if (search) params.set('search', search);
            if (category && category !== 'all') params.set('category', category);
            if (actionStatus && actionStatus !== 'all') params.set('action_status', actionStatus);
            if (from) params.set('from_date', from);
            if (to) params.set('to_date', to);
            if (sort && sort !== 'newest') params.set('sort', sort);
            if (currentReportModalMode === 'print') params.set('autoprint', '1');

            closeReportNotesModal();
            const targetUrl = '{{ url("/Evidence_Management/report_pdf") }}?' + params.toString();
            window.open(targetUrl, '_blank');
        }

        function triggerGenerateFilteredPdf() {
            openReportNotesModal('filtered');
        }

        function openReportExhibitDetails(exhibit) {
            document.getElementById('modalCommodityTitle').textContent = exhibit.commodity_name || 'Exhibit Details';
            document.getElementById('modalExhibitNum').textContent = exhibit.exhibit_number || 'N/A';
            document.getElementById('modalPropertyId').textContent = 'Property ID: ' + (exhibit.property_id || 'Unassigned');
            document.getElementById('modalCategory').textContent = exhibit.exhibit_category || 'General';
            document.getElementById('modalClassification').textContent = exhibit.drug_classification || exhibit.exhibit_type || 'Unclassified';

            let weightText = (exhibit.quantity || 1) + ' ' + (exhibit.unit_of_measure || 'units');
            if (exhibit.gross_weight || exhibit.net_weight) {
                weightText += ' (' + (exhibit.gross_weight || exhibit.net_weight) + ' kg)';
            }
            document.getElementById('modalWeight').textContent = weightText;
            document.getElementById('modalValue').textContent = 'GH₵ ' + (exhibit.seizure_value ? Number(exhibit.seizure_value).toLocaleString('en-US', { minimumFractionDigits: 2 }) : '0.00');
            document.getElementById('modalPackaging').textContent = exhibit.packaging_type || 'Standard Packaging';
            document.getElementById('modalDate').textContent = exhibit.date_seized ? new Date(exhibit.date_seized).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : 'N/A';
            document.getElementById('modalCustody').textContent = exhibit.custodial_status || 'Seized';
            document.getElementById('modalRemarks').textContent = exhibit.exhibit_remark || 'No remarks recorded';

            document.getElementById('dossierModal').classList.remove('hidden');
        }

        function closeReportDossierModal() {
            document.getElementById('dossierModal').classList.add('hidden');
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeReportDossierModal();
                closeReportNotesModal();
            }
        });

        document.getElementById('dossierModal')?.addEventListener('click', (e) => {
            if (e.target.id === 'dossierModal') closeReportDossierModal();
        });

        document.getElementById('reportNotesModal')?.addEventListener('click', (e) => {
            if (e.target.id === 'reportNotesModal') closeReportNotesModal();
        });
    </script>
</body>
</html>
