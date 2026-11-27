@php
    $authUser = auth()->user();
    $isDGContext = request()->is('DG*') || ($authUser && (
        $authUser->unit === 'Office of the Director-General' ||
        $authUser->unit === 'Director General' ||
        $authUser->unit === 'DG' ||
        str_contains(strtolower((string) $authUser->unit), 'director general') ||
        str_contains(strtolower((string) $authUser->unit), 'director-general')
    ));
    $endpointUrl = $isDGContext ? url('/DG/court-evidence') : url('/court-evidence');

    $officerFullname = $authUser ? ($authUser->fullname ?? $authUser->name ?? 'Directorate Head') : 'Directorate Head';
    $officerParts = preg_split('/\s+/', trim((string) $officerFullname)) ?: [];
    $officerInitials = count($officerParts) >= 2
        ? strtoupper(substr($officerParts[0], 0, 1) . substr(end($officerParts), 0, 1))
        : strtoupper(substr((string) $officerFullname, 0, 2));

    $genPct = ($totalCount ?? 0) > 0 ? (int) round((($generatedCount ?? 0) / $totalCount) * 100) : 0;
    $upPct = ($totalCount ?? 0) > 0 ? (int) round((($uploadedCount ?? 0) / $totalCount) * 100) : 0;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Court Evidence &amp; SITREP Repository | Forensic Directorate | NACOC</title>

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

    <style>
        .filter-tab-btn {
            padding: 6px 12px;
            border-radius: 0.65rem;
            font-size: 11px;
            font-weight: 700;
            font-family: 'JetBrains Mono', ui-monospace, monospace;
            cursor: pointer;
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            transition: all 0.16s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .filter-tab-btn:hover {
            background-color: #059669;
            border-color: #047857;
            color: #ffffff;
        }
        .filter-tab-btn.active-tab {
            background-color: #059669;
            border-color: #047857;
            color: #ffffff;
            box-shadow: 0 1px 2px rgba(5, 150, 105, 0.22);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-[#f3f5f8] text-slate-900 antialiased selection:bg-emerald-600 selection:text-white">

    <div class="flex h-screen overflow-hidden">

        @if($isDGContext)
            @include('DG.partials.sidebar')
        @else
            @include('Forensic_Head.partials.sidebar')
        @endif

        <div class="flex-1 flex flex-col min-w-0 bg-[#f3f5f8] overflow-hidden">

            <!-- STICKY COMMAND HEADER -->
            <header class="h-16 bg-white/95 backdrop-blur-md border-b border-slate-200/90 px-5 sm:px-6 flex items-center justify-between gap-4 shrink-0 z-20">
                <div class="flex items-center gap-3.5 shrink-0 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-emerald-700 border border-slate-200/90 flex items-center justify-center shrink-0 shadow-2xs">
                        <i data-lucide="scale" class="w-4 h-4"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-extrabold font-mono-code uppercase tracking-widest text-emerald-700">
                                {{ $isDGContext ? 'Office of the Director-General' : 'Forensic Directorate Command' }}
                            </span>
                            <span class="text-slate-300">/</span>
                            <span class="text-[10px] font-bold font-mono-code uppercase tracking-wider text-slate-500">
                                Master SITREP Vault
                            </span>
                        </div>
                        <h1 class="text-sm font-extrabold text-slate-900 tracking-tight truncate">
                            Court Evidence &amp; SITREP Repository
                        </h1>
                    </div>
                </div>

                <!-- LIVE GLOBAL SEARCH -->
                <div class="hidden md:flex items-center flex-1 max-w-md mx-4">
                    <div class="relative w-full">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </span>
                        <input type="text"
                               name="search"
                               id="globalDocSearch"
                               value="{{ $search ?? '' }}"
                               placeholder="Search case #, document title, officer, filename..."
                               data-table-filter="#courtEvidenceTableContainer"
                               class="rep-input w-full pl-10 pr-14 py-2 text-xs font-mono-code font-semibold">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-2.5 pointer-events-none">
                            <kbd class="px-1.5 py-0.5 text-[9.5px] font-mono-code font-bold text-emerald-700 bg-emerald-50 rounded border border-emerald-200/80">LIVE</kbd>
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 shrink-0">
                    <div class="hidden sm:block text-right">
                        <div class="text-xs font-bold text-slate-900 leading-tight">{{ $officerFullname }}</div>
                        <div class="text-[10px] text-slate-500 font-mono-code font-semibold">{{ $authUser->unit ?? 'Forensic Directorate' }}</div>
                    </div>
                    <div class="w-8 h-8 rounded-xl bg-slate-100 border border-slate-200/90 text-slate-800 flex items-center justify-center font-mono-code font-extrabold text-[11px] uppercase">
                        {{ $officerInitials ?: 'FD' }}
                    </div>
                </div>
            </header>

            <!-- SCROLLABLE WORKSPACE -->
            <main class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-5 custom-scrollbar">

                <!-- 1. EXECUTIVE GREY COMMAND MASTHEAD + 4-COLUMN TELEMETRY MATRIX -->
                <section class="rep-masthead" aria-label="Court Evidence Repository Telemetry">
                    <div class="rep-masthead-hero px-6 py-5 sm:px-7 sm:py-6">
                        <div class="rep-masthead-inner flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <div class="space-y-1.5 max-w-3xl">
                                <h2 class="text-lg sm:text-xl font-extrabold tracking-tight text-slate-900">
                                    Unified Judicial Dossiers, Unit SITREPs &amp; Certified Certificates
                                </h2>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    Centralized registry of system-generated PDF situational reports, forensic analysis certificates, judicial warrants, GSA reports, and witness statements across all 4 operational units.
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                                <div class="px-3.5 py-2 rounded-xl bg-white border border-slate-200/90 shadow-2xs">
                                    <div class="text-[9.5px] font-mono-code uppercase tracking-wider text-slate-500">Operational Units</div>
                                    <div class="text-xs font-mono-code font-extrabold text-slate-900 mt-0.5">4 Divisions Synced</div>
                                </div>
                                <div class="px-3.5 py-2 rounded-xl bg-emerald-50 border border-emerald-200/90 shadow-2xs">
                                    <div class="text-[9.5px] font-mono-code uppercase tracking-wider text-emerald-700">Total Repository</div>
                                    <div class="text-xs font-mono-code font-extrabold text-emerald-800 mt-0.5 tnum">{{ number_format($totalCount) }} Certified PDFs</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rep-kpi-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-slate-200/80">

                        <button type="button" onclick="switchCourtEvidenceTab(null, '', '')" class="rep-kpi-tile p-4 sm:px-6 text-left group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-extrabold font-mono-code text-slate-500 uppercase tracking-wider group-hover:text-slate-900">Total Court Evidence</span>
                                <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200/80 flex items-center justify-center shrink-0">
                                    <i data-lucide="folder-archive" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                            <div class="text-2xl font-black text-slate-900 font-mono-code tnum mt-2">{{ number_format($totalCount) }}</div>
                            <div class="text-[10.5px] text-emerald-700 font-semibold mt-1 group-hover:underline">All 4 operational forensic units &rarr;</div>
                        </button>

                        <button type="button" onclick="switchCourtEvidenceTab(null, 'generated', '')" class="rep-kpi-tile p-4 sm:px-6 text-left group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-extrabold font-mono-code text-blue-700 uppercase tracking-wider">System Generated</span>
                                <span class="px-2 py-0.5 rounded-full bg-blue-50 border border-blue-200 text-blue-800 font-mono-code text-[10px] font-extrabold tnum">{{ $genPct }}%</span>
                            </div>
                            <div class="text-2xl font-black text-slate-900 font-mono-code tnum mt-2">{{ number_format($generatedCount) }}</div>
                            <div class="text-[10.5px] text-emerald-700 font-semibold mt-1 group-hover:underline">SITREPs, dockets &amp; certificates &rarr;</div>
                        </button>

                        <button type="button" onclick="switchCourtEvidenceTab(null, 'uploaded', '')" class="rep-kpi-tile p-4 sm:px-6 text-left group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-extrabold font-mono-code text-emerald-700 uppercase tracking-wider">Officer Uploaded</span>
                                <span class="px-2 py-0.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 font-mono-code text-[10px] font-extrabold tnum">{{ $upPct }}%</span>
                            </div>
                            <div class="text-2xl font-black text-slate-900 font-mono-code tnum mt-2">{{ number_format($uploadedCount) }}</div>
                            <div class="text-[10.5px] text-emerald-700 font-semibold mt-1 group-hover:underline">GSA, warrants &amp; statements &rarr;</div>
                        </button>

                        <div class="rep-kpi-tile p-4 sm:px-6 flex flex-col justify-between">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-extrabold font-mono-code text-emerald-700 uppercase tracking-wider">Division Breakdown</span>
                                <span class="text-[9.5px] font-mono-code font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200">Quick Filter</span>
                            </div>
                            <div class="grid grid-cols-2 gap-1.5 mt-2.5">
                                <button type="button" onclick="filterByUnitDirect('css')" class="rep-btn-green-soft text-[10px] font-mono-code font-bold px-2 py-1 rounded-lg cursor-pointer text-left truncate tnum" title="Crime Scene Support">CSS &bull; {{ $unitCounts['css'] ?? 0 }}</button>
                                <button type="button" onclick="filterByUnitDirect('em')" class="rep-btn-green-soft text-[10px] font-mono-code font-bold px-2 py-1 rounded-lg cursor-pointer text-left truncate tnum" title="Evidence Management">EMU &bull; {{ $unitCounts['em'] ?? 0 }}</button>
                                <button type="button" onclick="filterByUnitDirect('lab')" class="rep-btn-green-soft text-[10px] font-mono-code font-bold px-2 py-1 rounded-lg cursor-pointer text-left truncate tnum" title="Laboratory Analysis">LAB &bull; {{ $unitCounts['lab'] ?? 0 }}</button>
                                <button type="button" onclick="filterByUnitDirect('dpf')" class="rep-btn-green-soft text-[10px] font-mono-code font-bold px-2 py-1 rounded-lg cursor-pointer text-left truncate tnum" title="Digital & Physical Forensics">DPF &bull; {{ $unitCounts['dpf'] ?? 0 }}</button>
                            </div>
                        </div>

                    </div>
                </section>

                <!-- 2. PRECISION FILTER CONSOLE -->
                <section class="rep-surface-card p-5 sm:px-6 space-y-4" id="reportFilterConsole">

                    <div class="flex items-center justify-between flex-wrap gap-3 border-b border-slate-100 pb-3.5">
                        <div class="rep-segmented" id="quickFilterTabsGroup">
                            <button type="button" data-origin-tab="" onclick="switchCourtEvidenceTab(event, '', '')" class="filter-tab-btn {{ empty(request('origin')) && empty(request('category')) ? 'active-tab' : '' }}">
                                <i data-lucide="layers" class="w-3.5 h-3.5"></i>
                                <span>All ({{ $totalCount }})</span>
                            </button>
                            <button type="button" data-origin-tab="generated" onclick="switchCourtEvidenceTab(event, 'generated', '')" class="filter-tab-btn {{ request('origin') === 'generated' ? 'active-tab' : '' }}">
                                <i data-lucide="cpu" class="w-3.5 h-3.5"></i>
                                <span>System Generated ({{ $generatedCount }})</span>
                            </button>
                            <button type="button" data-origin-tab="uploaded" onclick="switchCourtEvidenceTab(event, 'uploaded', '')" class="filter-tab-btn {{ request('origin') === 'uploaded' ? 'active-tab' : '' }}">
                                <i data-lucide="upload-cloud" class="w-3.5 h-3.5"></i>
                                <span>Officer Uploaded ({{ $uploadedCount }})</span>
                            </button>
                        </div>

                        <div class="text-[11px] text-slate-500 font-mono-code tnum" id="resultsCounterInfo">
                            Showing <span class="font-extrabold text-slate-900">{{ $documents->firstItem() ?? 0 }}</span>–<span class="font-extrabold text-slate-900">{{ $documents->lastItem() ?? 0 }}</span> of <span class="font-extrabold text-slate-900">{{ number_format($documents->total()) }}</span> files
                        </div>
                    </div>

                    <form action="{{ $endpointUrl }}" method="GET" id="courtEvidenceFilterForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <input type="hidden" name="search" id="formHiddenSearch" value="{{ $search ?? '' }}">
                        <input type="hidden" name="origin" id="originSelectFilter" value="{{ $originFilter ?? '' }}">

                        <div>
                            <label class="block text-[10.5px] font-extrabold font-mono-code uppercase text-slate-600 mb-1.5">Forensic Division</label>
                            <select name="unit" id="unitSelectFilter" onchange="triggerFilterChange()" class="rep-select w-full px-3 py-2 text-xs font-semibold">
                                <option value="all" {{ ($unitFilter ?? 'all') === 'all' ? 'selected' : '' }}>All Forensic Units</option>
                                <option value="css" {{ ($unitFilter ?? '') === 'css' ? 'selected' : '' }}>Crime Scene Support (CSS)</option>
                                <option value="em" {{ ($unitFilter ?? '') === 'em' ? 'selected' : '' }}>Evidence Management (EMU)</option>
                                <option value="lab" {{ ($unitFilter ?? '') === 'lab' ? 'selected' : '' }}>Laboratory Analysis (LAB)</option>
                                <option value="dpf" {{ ($unitFilter ?? '') === 'dpf' ? 'selected' : '' }}>Digital &amp; Physical Forensics (DPF)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-extrabold font-mono-code uppercase text-slate-600 mb-1.5">Evidence Document Class</label>
                            <select name="category" id="categorySelectFilter" onchange="triggerFilterChange()" class="rep-select w-full px-3 py-2 text-xs font-semibold">
                                <option value="all" {{ ($categoryFilter ?? 'all') === 'all' ? 'selected' : '' }}>All Categories</option>
                                <option value="Lab Report" {{ ($categoryFilter ?? '') === 'Lab Report' ? 'selected' : '' }}>Lab Report</option>
                                <option value="Crime Scene Report" {{ ($categoryFilter ?? '') === 'Crime Scene Report' ? 'selected' : '' }}>Crime Scene Report</option>
                                <option value="Evidence Report" {{ ($categoryFilter ?? '') === 'Evidence Report' ? 'selected' : '' }}>Evidence Report</option>
                                <option value="Digital Forensics Report" {{ ($categoryFilter ?? '') === 'Digital Forensics Report' ? 'selected' : '' }}>Digital Forensics Report</option>
                                <option value="GSA Confirmatory Test" {{ ($categoryFilter ?? '') === 'GSA Confirmatory Test' ? 'selected' : '' }}>GSA Confirmatory Report</option>
                                <option value="Court Order & Warrant" {{ ($categoryFilter ?? '') === 'Court Order & Warrant' ? 'selected' : '' }}>Court Order &amp; Warrant</option>
                                <option value="Witness Statement" {{ ($categoryFilter ?? '') === 'Witness Statement' ? 'selected' : '' }}>Witness Statement</option>
                                <option value="Preliminary Lab Report" {{ ($categoryFilter ?? '') === 'Preliminary Lab Report' ? 'selected' : '' }}>Preliminary Lab Report</option>
                                <option value="DPF Extraction Dossier" {{ ($categoryFilter ?? '') === 'DPF Extraction Dossier' ? 'selected' : '' }}>DPF Extraction Dossier</option>
                                <option value="Toxicology Screening" {{ ($categoryFilter ?? '') === 'Toxicology Screening' ? 'selected' : '' }}>Toxicology Screening</option>
                                <option value="Civilian Screening Report" {{ ($categoryFilter ?? '') === 'Civilian Screening Report' ? 'selected' : '' }}>Civilian Screening Report</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-extrabold font-mono-code uppercase text-slate-600 mb-1.5">Sort Order</label>
                            <select name="sort" id="sortSelectFilter" onchange="triggerFilterChange()" class="rep-select w-full px-3 py-2 text-xs font-semibold">
                                <option value="latest" {{ ($sort ?? 'latest') === 'latest' ? 'selected' : '' }}>Latest First</option>
                                <option value="oldest" {{ ($sort ?? '') === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                                <option value="case_asc" {{ ($sort ?? '') === 'case_asc' ? 'selected' : '' }}>Case # (A–Z)</option>
                                <option value="title_asc" {{ ($sort ?? '') === 'title_asc' ? 'selected' : '' }}>Title (A–Z)</option>
                            </select>
                        </div>

                        <div class="flex items-end gap-2">
                            <div class="flex-1">
                                <label class="block text-[10.5px] font-extrabold font-mono-code uppercase text-slate-600 mb-1.5">Rows</label>
                                <select name="per_page" id="perPageSelectFilter" onchange="triggerFilterChange()" class="rep-select w-full px-2.5 py-2 text-xs font-semibold">
                                    <option value="15" {{ ($perPage ?? 20) == 15 ? 'selected' : '' }}>15 / page</option>
                                    <option value="25" {{ ($perPage ?? 20) == 25 ? 'selected' : '' }}>25 / page</option>
                                    <option value="50" {{ ($perPage ?? 20) == 50 ? 'selected' : '' }}>50 / page</option>
                                    <option value="100" {{ ($perPage ?? 20) == 100 ? 'selected' : '' }}>100 / page</option>
                                </select>
                            </div>
                            <button type="button" onclick="resetCourtEvidenceFilters()" class="rep-btn-green px-3.5 py-2 rounded-xl text-xs font-bold font-mono-code shrink-0 cursor-pointer">
                                Reset
                            </button>
                        </div>
                    </form>
                </section>

                <!-- 3. COURT EVIDENCE LEDGER TABLE -->
                <section class="rep-surface-card overflow-hidden relative min-h-[380px]">
                    <div class="px-6 py-4 bg-slate-50/70 border-b border-slate-200/80 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-white border border-slate-200/90 text-emerald-700 flex items-center justify-center shrink-0 shadow-2xs">
                                <i data-lucide="file-text" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 tracking-tight">
                                    Certified Evidence &amp; SITREP Document Ledger
                                </h3>
                                <p class="text-[11px] text-slate-500">
                                    Preview official PDFs directly in-browser, launch in a new tab, or download archival copies.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div id="courtEvidenceTableContainer">
                        @include('Forensic_Head.partials.court_evidence_table')
                    </div>
                </section>

            </main>
        </div>
    </div>

    <!-- CERTIFIED IN-PAGE PDF PREVIEW MODAL (GREY HEADER + GREEN BUTTONS) -->
    <div id="pdfModal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6 transition-opacity" role="dialog" aria-modal="true">
        <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-5xl h-[90vh] flex flex-col overflow-hidden">

            <div class="rep-masthead-hero h-15 px-6 py-3.5 flex items-center justify-between shrink-0">
                <div class="rep-masthead-inner flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-white border border-slate-200/90 flex items-center justify-center text-emerald-700 shrink-0 shadow-2xs">
                        <i data-lucide="file-check" class="w-4 h-4"></i>
                    </div>
                    <div class="min-w-0">
                        <div id="modalDocTitle" class="text-xs sm:text-sm font-extrabold tracking-wide text-slate-900 truncate">Court Evidence Preview</div>
                        <div class="text-[10px] font-mono-code text-slate-600">NACOC Forensic Directorate Certified PDF Viewer</div>
                    </div>
                </div>
                <div class="rep-masthead-inner flex items-center gap-2">
                    <a id="modalExternalLink" href="#" target="_blank" class="rep-btn-green px-3 py-1.5 rounded-xl text-xs font-bold font-mono-code flex items-center gap-1.5 text-decoration-none">
                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                        <span>Open in New Tab</span>
                    </a>
                    <button type="button" onclick="closePdfModal()" class="rep-btn-green-soft w-8 h-8 rounded-xl cursor-pointer flex items-center justify-center" aria-label="Close">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <div class="flex-1 bg-slate-100 relative">
                <iframe id="pdfIframe" src="" class="w-full h-full border-0" frameborder="0"></iframe>
            </div>

        </div>
    </div>

    <script>
        if (window.lucide) {
            lucide.createIcons();
        }

        function renderCourtEvidenceSkeletonRows(colCount = 4, rowCount = 7) {
            let html = '';
            const titleWidths = ['w-64', 'w-80', 'w-56', 'w-72', 'w-60', 'w-48', 'w-64'];
            const fileWidths = ['w-36', 'w-44', 'w-28', 'w-40', 'w-32', 'w-36', 'w-40'];

            for (let r = 0; r < rowCount; r++) {
                const tw = titleWidths[r % titleWidths.length];
                const fw = fileWidths[r % fileWidths.length];

                html += `
                <tr class="skeleton-pulse border-b border-slate-100">
                    <td class="py-3.5 px-6">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl bg-slate-200/90 shrink-0"></div>
                            <div class="space-y-1.5 flex-1 min-w-0">
                                <div class="h-3.5 bg-slate-200 rounded ${tw}"></div>
                                <div class="h-2.5 bg-slate-100 rounded ${fw}"></div>
                                <div class="flex items-center gap-2 pt-0.5">
                                    <div class="h-4 bg-slate-100 rounded-md w-24"></div>
                                    <div class="h-3 bg-slate-100/70 rounded w-28 hidden sm:block"></div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="py-3.5 px-4">
                        <div class="space-y-1.5">
                            <div class="h-5 bg-slate-200 rounded-full w-28"></div>
                            <div class="h-3 bg-slate-100 rounded w-24"></div>
                        </div>
                    </td>
                    <td class="py-3.5 px-4">
                        <div class="h-3.5 bg-slate-200 rounded w-24"></div>
                        <div class="h-2.5 bg-slate-100 rounded w-16 mt-1.5"></div>
                    </td>
                    <td class="py-3.5 px-6 text-right">
                        <div class="inline-flex items-center gap-1.5 justify-end">
                            <div class="w-8 h-7 rounded-lg bg-slate-200"></div>
                            <div class="w-8 h-7 rounded-lg bg-slate-200"></div>
                            <div class="w-8 h-7 rounded-lg bg-slate-200"></div>
                        </div>
                    </td>
                </tr>`;
            }
            return html;
        }

        function setupCourtEvidenceAjaxTable() {
            if (typeof initAjaxTable !== 'function') return;
            if (window.courtEvidenceAjaxTable) return;

            window.courtEvidenceAjaxTable = initAjaxTable({
                container: '#courtEvidenceTableContainer',
                table: '#courtEvidenceTable',
                form: '#courtEvidenceFilterForm',
                endpoint: @json($endpointUrl),
                debounce: 250,
                columns: 4,
                rows: 7,
                skeletonRenderer: renderCourtEvidenceSkeletonRows,
                onAfterUpdate: function(container) {
                    const meta = container.querySelector('#courtEvidenceTableMeta');
                    if (meta) {
                        const total = meta.getAttribute('data-total');
                        const first = meta.getAttribute('data-first-item');
                        const last = meta.getAttribute('data-last-item');
                        const counterEl = document.getElementById('resultsCounterInfo');
                        if (counterEl && total !== null) {
                            counterEl.innerHTML = `Showing <span class="font-extrabold text-slate-900">${first}</span>–<span class="font-extrabold text-slate-900">${last}</span> of <span class="font-extrabold text-slate-900">${Number(total).toLocaleString()}</span> files`;
                        }
                    }
                    if (window.lucide) lucide.createIcons();
                }
            });

            const searchInput = document.getElementById('globalDocSearch');
            if (searchInput) {
                let searchTimer = null;
                searchInput.addEventListener('input', function() {
                    if (searchTimer) clearTimeout(searchTimer);
                    searchTimer = setTimeout(() => {
                        const val = searchInput.value.trim();
                        const hidden = document.getElementById('formHiddenSearch');
                        if (hidden) hidden.value = val;
                        if (window.courtEvidenceAjaxTable) {
                            window.courtEvidenceAjaxTable.applyFilters({ search: val });
                        }
                    }, 250);
                });
            }
        }

        function triggerFilterChange() {
            if (window.courtEvidenceAjaxTable) {
                window.courtEvidenceAjaxTable.applyFilters();
            } else {
                const form = document.getElementById('courtEvidenceFilterForm');
                if (form) form.submit();
            }
        }

        function switchCourtEvidenceTab(event, originVal, categoryVal) {
            if (event) event.preventDefault();

            const tabs = document.querySelectorAll('#quickFilterTabsGroup .filter-tab-btn');
            tabs.forEach(t => {
                if (t.getAttribute('data-origin-tab') === (originVal || '')) {
                    t.classList.add('active-tab');
                } else {
                    t.classList.remove('active-tab');
                }
            });

            const originSelect = document.getElementById('originSelectFilter');
            if (originSelect) originSelect.value = originVal || 'all';

            const categorySelect = document.getElementById('categorySelectFilter');
            if (categorySelect && categoryVal !== undefined && categoryVal !== '') {
                categorySelect.value = categoryVal;
            }

            if (window.courtEvidenceAjaxTable) {
                window.courtEvidenceAjaxTable.applyFilters({
                    origin: originVal,
                    category: categoryVal || (categorySelect ? categorySelect.value : 'all')
                });
            } else {
                window.location.href = `${@json($endpointUrl)}?origin=${encodeURIComponent(originVal)}&category=${encodeURIComponent(categoryVal)}`;
            }
        }

        function filterByUnitDirect(unitSlug) {
            const unitSelect = document.getElementById('unitSelectFilter');
            if (unitSelect) unitSelect.value = unitSlug;
            triggerFilterChange();
        }

        function resetCourtEvidenceFilters() {
            const form = document.getElementById('courtEvidenceFilterForm');
            if (form) {
                form.reset();
                const hidden = document.getElementById('formHiddenSearch');
                if (hidden) hidden.value = '';
            }
            const searchInput = document.getElementById('globalDocSearch');
            if (searchInput) searchInput.value = '';

            const tabs = document.querySelectorAll('#quickFilterTabsGroup .filter-tab-btn');
            tabs.forEach((t, i) => {
                if (i === 0) t.classList.add('active-tab');
                else t.classList.remove('active-tab');
            });

            if (window.courtEvidenceAjaxTable) {
                window.courtEvidenceAjaxTable.applyFilters({
                    search: '',
                    unit: 'all',
                    origin: 'all',
                    category: 'all',
                    sort: 'latest',
                    per_page: 20
                });
            } else {
                window.location.href = @json($endpointUrl);
            }
        }

        setupCourtEvidenceAjaxTable();
        document.addEventListener('DOMContentLoaded', setupCourtEvidenceAjaxTable);

        function openPdfModal(url, title) {
            const modal = document.getElementById('pdfModal');
            const iframe = document.getElementById('pdfIframe');
            const titleEl = document.getElementById('modalDocTitle');
            const extLink = document.getElementById('modalExternalLink');

            if (modal && iframe) {
                titleEl.textContent = title || 'Court Evidence Preview';
                iframe.src = url;
                extLink.href = url;
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        }

        function closePdfModal() {
            const modal = document.getElementById('pdfModal');
            const iframe = document.getElementById('pdfIframe');

            if (modal && iframe) {
                iframe.src = '';
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePdfModal();
            }
        });

        document.getElementById('pdfModal')?.addEventListener('click', function(e) {
            if (e.target.id === 'pdfModal') closePdfModal();
        });
    </script>

</body>
</html>
