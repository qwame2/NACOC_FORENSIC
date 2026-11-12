<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Court Evidence &amp; SITREP Repository | Forensic Directorate</title>

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
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
        }
        .table-row-hover:hover {
            background-color: #F8FAFC;
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
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .filter-tab-btn:hover {
            background: #E2E8F0;
            color: #0F172A;
        }
        .filter-tab-btn.active-tab {
            background: #059669;
            border-color: #059669;
            color: #FFFFFF;
            box-shadow: 0 4px 10px rgba(5, 150, 105, 0.25);
        }

        /* HIGH-FIDELITY SKELETON SHIMMER ANIMATION */
        @keyframes shimmerPulse {
            0% { opacity: 0.5; }
            50% { opacity: 1; }
            100% { opacity: 0.5; }
        }
        .skeleton-pulse {
            animation: shimmerPulse 1.4s ease-in-out infinite;
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
                    <div class="w-9 h-9 rounded-xl bg-emerald-600/10 border border-emerald-600/20 flex items-center justify-center text-emerald-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-sm font-extrabold text-slate-900 tracking-wide font-mono-code uppercase">
                            Court Evidence &amp; SITREP Repository
                        </h1>
                        <p class="text-[11px] text-slate-500 font-medium">
                            Comprehensive Registry of All System Generated PDFs &amp; Officer Uploads Across 4 Units
                        </p>
                    </div>
                </div>

                <!-- GENERAL SEARCH INPUT -->
                <div class="flex items-center flex-1 max-w-md mx-4">
                    <div class="relative w-full">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input 
                            type="text" 
                            name="search"
                            id="globalDocSearch"
                            value="{{ $search ?? '' }}"
                            placeholder="Search by case #, document title, officer, or filename..." 
                            data-table-filter="#courtEvidenceTableContainer"
                            class="w-full pl-10 pr-12 py-1.5 bg-slate-100/80 border border-slate-200 rounded-xl text-xs text-slate-900 font-mono-code focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15 transition-all"
                        >
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <kbd class="px-1.5 py-0.5 text-[10px] font-mono-code font-semibold text-slate-400 bg-white rounded border border-slate-200 shadow-2xs">Live</kbd>
                        </span>
                    </div>
                </div>

                <!-- RIGHT HEADER ACTIONS -->
                <div class="flex items-center space-x-4 shrink-0">
                    <!-- User Profile Dropdown Pill -->
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs uppercase">
                            {{ substr(auth()->user()->fullname ?? 'H', 0, 1) }}
                        </div>
                        <div class="hidden sm:block text-left">
                            <div class="text-xs font-bold text-slate-900 leading-tight">{{ auth()->user()->fullname ?? 'Directorate Head' }}</div>
                            <div class="text-[10px] text-slate-500 font-mono-code">{{ auth()->user()->unit ?? 'Forensic Directorate' }}</div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- SCROLLABLE PAGE BODY -->
            <main class="flex-1 overflow-y-auto p-6 space-y-6">

                <!-- 1. KPI SUMMARY CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    
                    <!-- TOTAL DOCUMENTS -->
                    <div class="glass-panel-white rounded-2xl p-4 flex items-center justify-between border-l-4 border-l-slate-900">
                        <div>
                            <div class="text-[10px] font-bold font-mono-code text-slate-500 uppercase tracking-wider">Total Court Evidence</div>
                            <div class="text-2xl font-black text-slate-900 font-mono-code mt-1">{{ number_format($totalCount) }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Across all 4 Operational Units</div>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-800">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                    </div>

                    <!-- SYSTEM GENERATED PDFS -->
                    <button type="button" onclick="switchCourtEvidenceTab(event, 'generated', '')" class="glass-panel-white rounded-2xl p-4 flex items-center justify-between border-l-4 border-l-blue-600 text-left hover:shadow-md transition-shadow cursor-pointer bg-white">
                        <div>
                            <div class="text-[10px] font-bold font-mono-code text-blue-700 uppercase tracking-wider">System Generated PDFs</div>
                            <div class="text-2xl font-black text-blue-900 font-mono-code mt-1">{{ number_format($generatedCount) }}</div>
                            <div class="text-[11px] text-blue-600 mt-0.5">SITREPs, Dockets &amp; Certificates</div>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                    </button>

                    <!-- OFFICER UPLOADED PDFS -->
                    <button type="button" onclick="switchCourtEvidenceTab(event, 'uploaded', '')" class="glass-panel-white rounded-2xl p-4 flex items-center justify-between border-l-4 border-l-emerald-600 text-left hover:shadow-md transition-shadow cursor-pointer bg-white">
                        <div>
                            <div class="text-[10px] font-bold font-mono-code text-emerald-700 uppercase tracking-wider">Officer Uploaded PDFs</div>
                            <div class="text-2xl font-black text-emerald-900 font-mono-code mt-1">{{ number_format($uploadedCount) }}</div>
                            <div class="text-[11px] text-emerald-600 mt-0.5">GSA, Court Orders &amp; Warrants</div>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        </div>
                    </button>

                    <!-- 4 UNITS CROSS COVERAGE -->
                    <div class="glass-panel-white rounded-2xl p-4 flex flex-col justify-between border-l-4 border-l-indigo-600">
                        <div class="flex items-center justify-between">
                            <div class="text-[10px] font-bold font-mono-code text-indigo-700 uppercase tracking-wider">4 Units Coverage</div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">Live</span>
                        </div>
                        <div class="flex items-center gap-1.5 mt-2 flex-wrap">
                            <button type="button" onclick="filterByUnitDirect('css')" class="text-[10px] font-mono-code font-bold px-2 py-0.5 rounded bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 cursor-pointer transition-colors" title="Crime Scene Support">CSS: {{ $unitCounts['css'] ?? 0 }}</button>
                            <button type="button" onclick="filterByUnitDirect('em')" class="text-[10px] font-mono-code font-bold px-2 py-0.5 rounded bg-indigo-50 hover:bg-indigo-100 text-indigo-800 border border-indigo-200 cursor-pointer transition-colors" title="Evidence Management">EM: {{ $unitCounts['em'] ?? 0 }}</button>
                            <button type="button" onclick="filterByUnitDirect('lab')" class="text-[10px] font-mono-code font-bold px-2 py-0.5 rounded bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 cursor-pointer transition-colors" title="Laboratory Analysis">LAB: {{ $unitCounts['lab'] ?? 0 }}</button>
                            <button type="button" onclick="filterByUnitDirect('dpf')" class="text-[10px] font-mono-code font-bold px-2 py-0.5 rounded bg-cyan-50 hover:bg-cyan-100 text-cyan-800 border border-cyan-200 cursor-pointer transition-colors" title="Digital & Physical Forensics">DPF: {{ $unitCounts['dpf'] ?? 0 }}</button>
                        </div>
                    </div>

                </div>

                <!-- 2. FILTER TABS & CONTROL BAR -->
                <div class="glass-panel-white rounded-2xl p-4 space-y-4">
                    
                    <!-- TAB BUTTONS -->
                    <div class="flex items-center justify-between flex-wrap gap-3 border-b border-slate-200 pb-3">
                        <div class="flex items-center gap-2 flex-wrap" id="quickFilterTabsGroup">
                            <button type="button" onclick="switchCourtEvidenceTab(event, '', '')" class="filter-tab-btn {{ empty(request('origin')) && empty(request('category')) ? 'active-tab' : '' }}">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                                <span>All Evidence ({{ $totalCount }})</span>
                            </button>
                            <button type="button" onclick="switchCourtEvidenceTab(event, 'generated', '')" class="filter-tab-btn {{ request('origin') === 'generated' ? 'active-tab' : '' }}">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                <span>System Generated PDFs ({{ $generatedCount }})</span>
                            </button>
                            <button type="button" onclick="switchCourtEvidenceTab(event, 'uploaded', '')" class="filter-tab-btn {{ request('origin') === 'uploaded' ? 'active-tab' : '' }}">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                <span>Officer Uploaded PDFs ({{ $uploadedCount }})</span>
                            </button>
                        </div>

                        <!-- RESULTS COUNTER -->
                        <div class="text-xs text-slate-500 font-mono-code" id="resultsCounterInfo">
                            Showing <span class="font-bold text-slate-900">{{ $documents->firstItem() ?? 0 }}</span> - <span class="font-bold text-slate-900">{{ $documents->lastItem() ?? 0 }}</span> of <span class="font-bold text-slate-900">{{ $documents->total() }}</span> files
                        </div>
                    </div>

                    <!-- DETAILED FILTER FORM -->
                    <form action="{{ url('/court-evidence') }}" method="GET" id="courtEvidenceFilterForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <input type="hidden" name="search" id="formHiddenSearch" value="{{ $search ?? '' }}">
                        <input type="hidden" name="origin" id="originSelectFilter" value="{{ $originFilter ?? '' }}">

                        <!-- FILTER BY UNIT -->
                        <div>
                            <label class="block text-[10px] font-bold font-mono-code uppercase text-slate-500 mb-1">Filter by Unit</label>
                            <select name="unit" id="unitSelectFilter" onchange="triggerFilterChange()" class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:border-emerald-600">
                                <option value="all" {{ ($unitFilter ?? 'all') === 'all' ? 'selected' : '' }}>All Forensic Units</option>
                                <option value="css" {{ ($unitFilter ?? '') === 'css' ? 'selected' : '' }}>Crime Scene Support (CSS)</option>
                                <option value="em" {{ ($unitFilter ?? '') === 'em' ? 'selected' : '' }}>Evidence Management (EMU)</option>
                                <option value="lab" {{ ($unitFilter ?? '') === 'lab' ? 'selected' : '' }}>Laboratory Analysis (LAB)</option>
                                <option value="dpf" {{ ($unitFilter ?? '') === 'dpf' ? 'selected' : '' }}>Digital &amp; Physical Forensics (DPF)</option>
                            </select>
                        </div>

                        <!-- FILTER BY CATEGORY -->
                        <div>
                            <label class="block text-[10px] font-bold font-mono-code uppercase text-slate-500 mb-1">Evidence Category</label>
                            <select name="category" id="categorySelectFilter" onchange="triggerFilterChange()" class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:border-emerald-600">
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

                        <!-- SORTING -->
                        <div>
                            <label class="block text-[10px] font-bold font-mono-code uppercase text-slate-500 mb-1">Sort By</label>
                            <select name="sort" id="sortSelectFilter" onchange="triggerFilterChange()" class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:border-emerald-600">
                                <option value="latest" {{ ($sort ?? 'latest') === 'latest' ? 'selected' : '' }}>Latest First</option>
                                <option value="oldest" {{ ($sort ?? '') === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                                <option value="case_asc" {{ ($sort ?? '') === 'case_asc' ? 'selected' : '' }}>Case # (A-Z)</option>
                                <option value="title_asc" {{ ($sort ?? '') === 'title_asc' ? 'selected' : '' }}>Title (A-Z)</option>
                            </select>
                        </div>

                        <!-- PER PAGE & RESET -->
                        <div class="flex items-end gap-2">
                            <div class="flex-1">
                                <label class="block text-[10px] font-bold font-mono-code uppercase text-slate-500 mb-1">Rows</label>
                                <select name="per_page" id="perPageSelectFilter" onchange="triggerFilterChange()" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:border-emerald-600">
                                    <option value="15" {{ ($perPage ?? 20) == 15 ? 'selected' : '' }}>15</option>
                                    <option value="25" {{ ($perPage ?? 20) == 25 ? 'selected' : '' }}>25</option>
                                    <option value="50" {{ ($perPage ?? 20) == 50 ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ ($perPage ?? 20) == 100 ? 'selected' : '' }}>100</option>
                                </select>
                            </div>
                            <button type="button" onclick="resetCourtEvidenceFilters()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold font-mono-code transition-colors shrink-0 border-none cursor-pointer">
                                Reset
                            </button>
                        </div>

                    </form>
                </div>

                <!-- 3. COURT EVIDENCE DATA TABLE WITH SKELETON LOADER -->
                <div class="glass-panel-white rounded-2xl overflow-hidden relative min-h-[380px]" id="courtEvidenceTableContainer">
                    @include('Forensic_Head.partials.court_evidence_table')
                </div>

            </main>
        </div>
    </div>

    <!-- PDF IN-PAGE PREVIEW MODAL -->
    <div id="pdfModal" class="hidden fixed inset-0 z-50 bg-slate-900/70 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6 transition-opacity">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-5xl h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            
            <!-- MODAL HEADER -->
            <div class="h-14 px-6 border-b border-slate-200 flex items-center justify-between shrink-0 bg-slate-900 text-white">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center text-emerald-400 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <div id="modalDocTitle" class="text-xs font-bold tracking-wide truncate">Court Evidence Preview</div>
                        <div class="text-[10px] font-mono-code text-slate-400">NACOC Forensic Directorate Certified PDF Viewer</div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a id="modalExternalLink" href="#" target="_blank" class="px-3 py-1 bg-white/10 hover:bg-white/20 text-white rounded-lg text-xs font-bold font-mono-code flex items-center gap-1.5 transition-colors text-decoration-none">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        <span>Open in New Tab</span>
                    </a>
                    <button type="button" onclick="closePdfModal()" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-colors border-none bg-transparent cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <!-- MODAL VIEWER IFRAME -->
            <div class="flex-1 bg-slate-100 relative">
                <iframe id="pdfIframe" src="" class="w-full h-full border-0" frameborder="0"></iframe>
            </div>

        </div>
    </div>

    <!-- PREVIEW MODAL & SKELETON LOADER CONTROLLER -->
    <script>
        // High-Fidelity Custom Skeleton Generator matching Document & File Details Table
        function renderCourtEvidenceSkeletonRows(colCount = 6, rowCount = 7) {
            let html = '';
            const titleWidths = ['w-64', 'w-80', 'w-56', 'w-72', 'w-60', 'w-48', 'w-68'];
            const fileWidths = ['w-36', 'w-44', 'w-28', 'w-40', 'w-32', 'w-30', 'w-38'];
            const officerWidths = ['w-32', 'w-36', 'w-28', 'w-40', 'w-30', 'w-34', 'w-32'];

            for (let r = 0; r < rowCount; r++) {
                const tw = titleWidths[r % titleWidths.length];
                const fw = fileWidths[r % fileWidths.length];
                const ow = officerWidths[r % officerWidths.length];

                html += `
                <tr class="skeleton-pulse border-b border-slate-100">
                    
                    <!-- COL 1: Document & File Details Skeleton -->
                    <td class="py-3.5 px-4">
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

                    <!-- COL 2: Unit & Origin Skeleton -->
                    <td class="py-3.5 px-4">
                        <div class="space-y-1.5">
                            <div class="h-5 bg-slate-200 rounded-full w-24"></div>
                            <div class="h-3 bg-slate-100 rounded w-28"></div>
                        </div>
                    </td>

                    <!-- COL 3: Case Reference Skeleton -->
                    <td class="py-3.5 px-4">
                        <div class="h-3.5 bg-slate-200 rounded w-28"></div>
                        <div class="h-2.5 bg-slate-100 rounded w-16 mt-1.5"></div>
                    </td>

                    <!-- COL 4: Officer / Submitter Skeleton -->
                    <td class="py-3.5 px-4">
                        <div class="h-3.5 bg-slate-200 rounded ${ow}"></div>
                        <div class="h-2.5 bg-slate-100 rounded w-16 mt-1.5"></div>
                    </td>

                    <!-- COL 5: Date & Size Skeleton -->
                    <td class="py-3.5 px-4">
                        <div class="h-3.5 bg-slate-200 rounded w-24"></div>
                        <div class="h-2.5 bg-slate-100 rounded w-16 mt-1.5"></div>
                    </td>

                    <!-- COL 6: Actions Skeleton -->
                    <td class="py-3.5 px-4 text-right">
                        <div class="inline-flex items-center gap-1.5 justify-end">
                            <div class="w-7 h-7 rounded-lg bg-slate-200"></div>
                            <div class="w-7 h-7 rounded-lg bg-slate-200"></div>
                            <div class="w-7 h-7 rounded-lg bg-slate-200"></div>
                        </div>
                    </td>

                </tr>`;
            }
            return html;
        }

        // Initialize AJAX Table with Skeleton Loader
        function setupCourtEvidenceAjaxTable() {
            if (typeof initAjaxTable !== 'function') return;
            if (window.courtEvidenceAjaxTable) return;

            window.courtEvidenceAjaxTable = initAjaxTable({
                container: '#courtEvidenceTableContainer',
                table: '#courtEvidenceTable',
                form: '#courtEvidenceFilterForm',
                endpoint: '{{ url("/court-evidence") }}',
                debounce: 250,
                columns: 6,
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
                            counterEl.innerHTML = `Showing <span class="font-bold text-slate-900">${first}</span> - <span class="font-bold text-slate-900">${last}</span> of <span class="font-bold text-slate-900">${Number(total).toLocaleString()}</span> files`;
                        }
                    }
                }
            });

            // Bind top global search input
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

        // Trigger filter change on select dropdowns
        function triggerFilterChange() {
            if (window.courtEvidenceAjaxTable) {
                window.courtEvidenceAjaxTable.applyFilters();
            } else {
                const form = document.getElementById('courtEvidenceFilterForm');
                if (form) form.submit();
            }
        }

        // Switch quick filter tabs with skeleton loader
        function switchCourtEvidenceTab(event, originVal, categoryVal) {
            if (event) event.preventDefault();

            // Update UI tab active state
            const tabs = document.querySelectorAll('#quickFilterTabsGroup .filter-tab-btn');
            tabs.forEach(t => t.classList.remove('active-tab'));
            if (event && event.currentTarget) {
                event.currentTarget.classList.add('active-tab');
            }

            // Sync with select elements
            const originSelect = document.getElementById('originSelectFilter');
            if (originSelect) originSelect.value = originVal || 'all';

            const categorySelect = document.getElementById('categorySelectFilter');
            if (categorySelect) categorySelect.value = categoryVal || 'all';

            if (window.courtEvidenceAjaxTable) {
                window.courtEvidenceAjaxTable.applyFilters({
                    origin: originVal,
                    category: categoryVal
                });
            } else {
                window.location.href = `{{ url('/court-evidence') }}?origin=${encodeURIComponent(originVal)}&category=${encodeURIComponent(categoryVal)}`;
            }
        }

        // Direct filter by unit pill
        function filterByUnitDirect(unitSlug) {
            const unitSelect = document.getElementById('unitSelectFilter');
            if (unitSelect) unitSelect.value = unitSlug;
            triggerFilterChange();
        }

        // Reset all filters
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
                window.location.href = '{{ url("/court-evidence") }}';
            }
        }

        // Initialize immediately
        setupCourtEvidenceAjaxTable();
        document.addEventListener('DOMContentLoaded', setupCourtEvidenceAjaxTable);

        // PDF In-Page Preview Modal Logic
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

        // Close on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePdfModal();
            }
        });
    </script>

</body>
</html>
