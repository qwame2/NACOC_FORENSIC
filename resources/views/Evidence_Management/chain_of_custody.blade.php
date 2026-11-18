@php
    $authUser = auth()->user();
    $activeUnitName = 'Evidence Management';
    $officerName = $authUser?->fullname ?? session('user_name') ?? 'Vault Officer';
    $totalCustodyCount = $totalCustodyCount ?? 0;
    $inVaultCount = $inVaultCount ?? 0;
    $transferredCount = $transferredCount ?? 0;
    $releasedCount = $releasedCount ?? 0;
    $withExplicitCocCount = $withExplicitCocCount ?? 0;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chain of Custody Master Register | Evidence Management | NACOC Forensic</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    @endif
    <script src="{{ asset('vendor/lucide.min.js') }}"></script>

    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #F6F7F9;
            color: #0F172A;
            -webkit-font-smoothing: antialiased;
        }
        .font-mono-code {
            font-family: 'JetBrains Mono', monospace;
        }
        .tnum {
            font-variant-numeric: tabular-nums;
        }
        .custom-scrollbar::-webkit-scrollbar,
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track,
        ::-webkit-scrollbar-track {
            background: #F1F5F9;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb,
        ::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 3px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover,
        ::-webkit-scrollbar-thumb:hover {
            background: #94A3B8;
        }
        .coc-pagination nav {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .coc-pagination span, .coc-pagination a {
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 700;
            border-radius: 8px;
            border: 1px solid #E2E8F0;
            background: #FFFFFF;
            color: #334155;
            text-decoration: none;
        }
        .coc-pagination .active span {
            background: #059669;
            color: #FFFFFF;
            border-color: #059669;
        }

        @media print {
            aside, header, #cocFiltersBar, #cocSummaryMetrics, .coc-actions-bar, .coc-pagination, button {
                display: none !important;
            }
            body, main, #chainOfCustodyTableContainer {
                background: #FFFFFF !important;
                padding: 0 !important;
                margin: 0 !important;
                overflow: visible !important;
            }
            table {
                width: 100% !important;
                border: 1px solid #CBD5E1 !important;
            }
            th, td {
                border: 1px solid #E2E8F0 !important;
                color: #000000 !important;
            }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-[#F6F7F9] text-slate-900 antialiased selection:bg-emerald-600 selection:text-white">

    <!-- APP SHELL CONTAINER -->
    <div class="flex h-screen overflow-hidden">

        <!-- LEFT ASIDEBAR -->
        @include('Crime_Scene_Support.partials.sidebar')

        <!-- MAIN WORKSPACE -->
        <div class="flex-1 flex flex-col min-w-0 bg-[#F6F7F9] overflow-hidden">

            <!-- SYSTEM TOP NAVBAR -->
            <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between gap-4 shrink-0 shadow-2xs z-20">
                <div class="flex items-center space-x-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-white flex items-center justify-center text-emerald-600 border border-slate-200/90 shadow-md shadow-slate-200/60 shrink-0">
                        <i data-lucide="link-2" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h1 class="text-sm font-extrabold text-slate-900 tracking-tight uppercase truncate">
                                Chain of Custody Master Register
                            </h1>
                            <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono-code font-bold bg-slate-100 text-slate-700 border border-slate-200 tnum">
                                {{ number_format($totalCustodyCount) }} Records
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 font-medium truncate">
                            Evidence Management Unit &bull; Physical Exhibit Movements, Custody Transfers &amp; Handovers
                        </p>
                    </div>
                </div>

                <!-- TOPBAR ACTIONS -->
                <div class="flex items-center space-x-2.5 shrink-0 coc-actions-bar">
                    <button type="button"
                            onclick="fetchCocData(true)"
                            class="inline-flex items-center px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl shadow-2xs transition-colors cursor-pointer"
                            title="Refresh custody records">
                        <i id="cocRefreshIcon" data-lucide="refresh-cw" class="w-3.5 h-3.5 mr-1.5 text-slate-500"></i>
                        <span>Refresh</span>
                    </button>

                    <button type="button"
                            onclick="window.print()"
                            class="inline-flex items-center px-3 py-1.5 bg-slate-900 hover:bg-black text-white font-bold text-xs rounded-xl shadow-xs transition-colors cursor-pointer"
                            title="Print Custody Register">
                        <i data-lucide="printer" class="w-3.5 h-3.5 mr-1.5 text-slate-300"></i>
                        <span class="hidden sm:inline">Print Register</span>
                    </button>

                    <div class="hidden lg:flex items-center space-x-2 pl-2 border-l border-slate-200">
                        <div class="w-8 h-8 rounded-full bg-slate-900 text-white font-bold text-xs flex items-center justify-center">
                            {{ strtoupper(substr($officerName, 0, 2)) }}
                        </div>
                        <span class="text-xs font-bold text-slate-700 truncate max-w-[120px]">{{ $officerName }}</span>
                    </div>
                </div>
            </header>

            <!-- MAIN WORKSPACE VIEW -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-5 custom-scrollbar">
                @include('partials.flash')

                <!-- KPI METRICS SUMMARY -->
                <div id="cocSummaryMetrics" class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
                    <!-- 1. TOTAL UNDER CUSTODY -->
                    <div onclick="filterByStatusCard('all')" class="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs hover:border-emerald-300 transition-all cursor-pointer group flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-500 mb-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider group-hover:text-emerald-700 transition-colors">Total Logged Exhibits</span>
                            <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
                                <i data-lucide="layers" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <div>
                            <div class="text-2xl font-extrabold text-slate-900 font-mono-code tracking-tight tnum leading-none">{{ number_format($totalCustodyCount) }}</div>
                            <div class="text-[11px] font-medium text-slate-500 mt-1.5">Active custody records</div>
                        </div>
                    </div>

                    <!-- 2. IN VAULT CUSTODY -->
                    <div onclick="filterByStatusCard('In Vault Custody')" class="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs hover:border-emerald-300 transition-all cursor-pointer group flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-500 mb-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider group-hover:text-emerald-700 transition-colors">In Central Vault</span>
                            <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0">
                                <i data-lucide="archive" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <div>
                            <div class="text-2xl font-extrabold text-emerald-700 font-mono-code tracking-tight tnum leading-none">{{ number_format($inVaultCount) }}</div>
                            <div class="text-[11px] font-medium text-slate-500 mt-1.5">Secured in vault custody</div>
                        </div>
                    </div>

                    <!-- 3. TRANSFERRED / IN ANALYSIS -->
                    <div onclick="filterByStatusCard('Transferred')" class="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs hover:border-indigo-300 transition-all cursor-pointer group flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-500 mb-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider group-hover:text-indigo-700 transition-colors">Transferred / In Transit</span>
                            <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-700 flex items-center justify-center shrink-0">
                                <i data-lucide="arrow-right-left" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <div>
                            <div class="text-2xl font-extrabold text-indigo-700 font-mono-code tracking-tight tnum leading-none">{{ number_format($transferredCount) }}</div>
                            <div class="text-[11px] font-medium text-slate-500 mt-1.5">Lab analysis or court transit</div>
                        </div>
                    </div>

                    <!-- 4. RELEASED / CONCLUDED -->
                    <div onclick="filterByStatusCard('Released')" class="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs hover:border-slate-400 transition-all cursor-pointer group flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-500 mb-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider group-hover:text-slate-900 transition-colors">Released / Concluded</span>
                            <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center shrink-0">
                                <i data-lucide="scale" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <div>
                            <div class="text-2xl font-extrabold text-slate-800 font-mono-code tracking-tight tnum leading-none">{{ number_format($releasedCount) }}</div>
                            <div class="text-[11px] font-medium text-slate-500 mt-1.5">Final legal disposition</div>
                        </div>
                    </div>
                </div>

                <!-- SEARCH & FILTER CONTROL BAR -->
                <div id="cocFiltersBar" class="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs space-y-3">
                    <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
                        <!-- Search Input -->
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i data-lucide="search" class="w-4 h-4"></i>
                            </div>
                            <input
                                type="text"
                                id="globalCocSearch"
                                value="{{ $search ?? '' }}"
                                oninput="handleCocSearchInput()"
                                autocomplete="off"
                                placeholder="Search exhibit #, property ID, case reference, suspect, commodity, or custody pathway..."
                                class="w-full pl-10 pr-20 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition-all font-mono-code"
                            >
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center gap-1.5">
                                <button type="button" id="cocClearSearchBtn" onclick="clearCocSearch()" class="text-slate-400 hover:text-slate-600 {{ !empty($search) ? '' : 'hidden' }}" title="Clear search">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                </button>
                                <kbd class="hidden sm:inline-block px-1.5 py-0.5 text-[10px] font-mono-code font-semibold text-slate-400 bg-white rounded border border-slate-200">Ctrl K</kbd>
                            </div>
                        </div>

                        <!-- Filter Dropdowns -->
                        <div class="flex items-center gap-2 flex-wrap">
                            <div class="flex items-center space-x-1.5">
                                <label for="cocStatusSelect" class="text-[11px] font-mono-code text-slate-500 font-bold uppercase">Status:</label>
                                <select id="cocStatusSelect" onchange="fetchCocData(false)" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 outline-none cursor-pointer focus:bg-white focus:border-emerald-600">
                                    <option value="all" {{ ($custodialStatus ?? 'all') === 'all' ? 'selected' : '' }}>All Statuses</option>
                                    <option value="Seized" {{ ($custodialStatus ?? '') === 'Seized' ? 'selected' : '' }}>Seized</option>
                                    <option value="In Vault Custody" {{ ($custodialStatus ?? '') === 'In Vault Custody' ? 'selected' : '' }}>In Vault Custody</option>
                                    <option value="Under Laboratory Analysis" {{ ($custodialStatus ?? '') === 'Under Laboratory Analysis' ? 'selected' : '' }}>Under Laboratory Analysis</option>
                                    <option value="Court Evidence" {{ ($custodialStatus ?? '') === 'Court Evidence' ? 'selected' : '' }}>Court Evidence</option>
                                    <option value="Transferred" {{ ($custodialStatus ?? '') === 'Transferred' ? 'selected' : '' }}>Transferred</option>
                                    <option value="Released" {{ ($custodialStatus ?? '') === 'Released' ? 'selected' : '' }}>Released</option>
                                    <option value="Scheduled for Destruction" {{ ($custodialStatus ?? '') === 'Scheduled for Destruction' ? 'selected' : '' }}>Scheduled for Destruction</option>
                                </select>
                            </div>

                            <div class="flex items-center space-x-1.5">
                                <label for="cocCategorySelect" class="text-[11px] font-mono-code text-slate-500 font-bold uppercase">Category:</label>
                                <select id="cocCategorySelect" onchange="fetchCocData(false)" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 outline-none cursor-pointer focus:bg-white focus:border-emerald-600">
                                    <option value="all" {{ ($category ?? 'all') === 'all' ? 'selected' : '' }}>All Categories</option>
                                    <option value="Drugs" {{ ($category ?? '') === 'Drugs' ? 'selected' : '' }}>Narcotics &amp; Drugs</option>
                                    <option value="Weapons" {{ ($category ?? '') === 'Weapons' ? 'selected' : '' }}>Weapons &amp; Firearms</option>
                                    <option value="Vehicles" {{ ($category ?? '') === 'Vehicles' ? 'selected' : '' }}>Vehicles &amp; Conveyances</option>
                                    <option value="Devices" {{ ($category ?? '') === 'Devices' ? 'selected' : '' }}>Digital Devices</option>
                                    <option value="Money" {{ ($category ?? '') === 'Money' ? 'selected' : '' }}>Currency / Cash</option>
                                    <option value="General" {{ ($category ?? '') === 'General' ? 'selected' : '' }}>General Exhibits</option>
                                </select>
                            </div>

                            <div class="flex items-center space-x-1.5">
                                <label for="cocSortSelect" class="text-[11px] font-mono-code text-slate-500 font-bold uppercase">Sort:</label>
                                <select id="cocSortSelect" onchange="fetchCocData(false)" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 outline-none cursor-pointer focus:bg-white focus:border-emerald-600">
                                    <option value="newest" {{ ($sort ?? 'newest') === 'newest' ? 'selected' : '' }}>Newest Logged</option>
                                    <option value="oldest" {{ ($sort ?? '') === 'oldest' ? 'selected' : '' }}>Oldest Logged</option>
                                    <option value="exhibit_no" {{ ($sort ?? '') === 'exhibit_no' ? 'selected' : '' }}>Exhibit Number</option>
                                    <option value="status" {{ ($sort ?? '') === 'status' ? 'selected' : '' }}>Custodial Status</option>
                                </select>
                            </div>

                            <button type="button"
                                    onclick="resetCocFilters()"
                                    class="px-3 py-2 text-xs font-bold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors cursor-pointer">
                                Reset
                            </button>
                        </div>
                    </div>
                </div>

                <!-- MASTER DATA TABLE -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                    <div id="chainOfCustodyTableContainer" class="relative">
                        @include('Evidence_Management.partials.chain_of_custody_table')
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- DETAIL MODAL FOR FULL CHAIN OF CUSTODY RECORD -->
    <div id="cocDetailModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-2xl max-w-2xl w-full border border-slate-200 shadow-2xl overflow-hidden" onclick="event.stopPropagation()">
            <!-- MODAL HEADER -->
            <div class="bg-slate-900 px-6 py-4 text-white flex items-center justify-between border-b border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-600/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                        <i data-lucide="link-2" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold tracking-tight uppercase">Chain of Custody Record</h3>
                        <p class="text-[11px] text-slate-400 font-mono-code">NACOC FORENSIC &bull; EVIDENCE CUSTODIAL REGISTRY</p>
                    </div>
                </div>
                <button type="button" onclick="closeCocDetailModal()" class="text-slate-400 hover:text-white rounded-lg p-1.5 hover:bg-white/10 transition-colors cursor-pointer" aria-label="Close modal">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- MODAL BODY -->
            <div class="p-6 space-y-5 text-xs text-slate-700 max-h-[75vh] overflow-y-auto custom-scrollbar">
                <!-- TOP IDENTIFIERS -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                    <div>
                        <div class="text-[10px] font-mono-code text-slate-400 uppercase font-bold">Exhibit Number</div>
                        <div id="modalExhibitNumber" class="text-xs font-bold text-slate-900 font-mono-code mt-0.5">EXH-00001</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-mono-code text-slate-400 uppercase font-bold">Property ID</div>
                        <div id="modalPropertyId" class="text-xs font-bold text-slate-800 font-mono-code mt-0.5">N/A</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-mono-code text-slate-400 uppercase font-bold">Case Reference</div>
                        <div id="modalCaseNumber" class="text-xs font-bold text-slate-900 font-mono-code mt-0.5">N/A</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-mono-code text-slate-400 uppercase font-bold">Custodial Status</div>
                        <div id="modalCustodialStatus" class="text-xs font-extrabold text-emerald-700 font-mono-code mt-0.5">Seized</div>
                    </div>
                </div>

                <!-- COMMODITY DETAILS -->
                <div>
                    <h4 class="text-[11px] font-extrabold text-slate-900 uppercase font-mono-code mb-2">
                        Seized Exhibit Characterization
                    </h4>
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500 font-medium">Commodity:</span>
                            <span id="modalCommodityName" class="font-bold text-slate-900 text-right"></span>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500 font-medium">Category:</span>
                            <span id="modalExhibitCategory" class="font-bold text-slate-900 text-right"></span>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500 font-medium">Weight / Quantity:</span>
                            <span id="modalWeightQuantity" class="font-mono-code font-bold text-slate-900 text-right"></span>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500 font-medium">Packaging / Seal:</span>
                            <span id="modalPackaging" class="font-medium text-slate-800 text-right"></span>
                        </div>
                    </div>
                </div>

                <!-- CUSTODY PATHWAY -->
                <div>
                    <h4 class="text-[11px] font-extrabold text-slate-900 uppercase font-mono-code mb-2">
                        Verified Custody Pathway
                    </h4>
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl font-mono-code text-xs font-bold text-slate-800 leading-relaxed" id="modalChainOfCustody">
                        ---
                    </div>
                </div>

                <!-- OFFICERS & LOCATION -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-1.5">
                        <div class="text-[10px] font-mono-code text-slate-400 uppercase font-bold">Handling Officers</div>
                        <div><strong class="text-slate-700">Seizure Officer:</strong> <span id="modalSeizureOfficer"></span></div>
                        <div><strong class="text-slate-700">Investigating Officer:</strong> <span id="modalInvestigatingOfficer"></span></div>
                        <div><strong class="text-slate-700">Requesting Unit:</strong> <span id="modalRequestingUnit"></span></div>
                    </div>
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-1.5">
                        <div class="text-[10px] font-mono-code text-slate-400 uppercase font-bold">Source &amp; Incident Location</div>
                        <div><strong class="text-slate-700">Source of Exhibit:</strong> <span id="modalSourceExhibit"></span></div>
                        <div><strong class="text-slate-700">Location:</strong> <span id="modalLocation"></span></div>
                        <div><strong class="text-slate-700">Logged At:</strong> <span id="modalCreatedAt" class="font-mono-code"></span></div>
                    </div>
                </div>
            </div>

            <!-- MODAL FOOTER -->
            <div class="bg-slate-50 px-6 py-3.5 border-t border-slate-200 flex items-center justify-between">
                <span class="text-[11px] text-slate-400 font-mono-code">NACOC Legal &amp; Evidence Repository Standard</span>
                <button type="button" onclick="closeCocDetailModal()" class="px-4 py-2 bg-slate-900 hover:bg-black text-white rounded-xl text-xs font-bold cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT FOR CHAIN OF CUSTODY INTERACTION -->
    <script>
        const COC_BASE_URL = "{{ url('/Evidence_Management/chain-of-custody', [], false) }}";
        let cocAbortController = null;
        let cocDebounceTimer = null;
        let isCocFetching = false;
        let lastSearchTerm = (document.getElementById('globalCocSearch')?.value || '').trim();

        function getCocFilterParams() {
            const searchVal = (document.getElementById('globalCocSearch')?.value || '').trim();
            const statusVal = document.getElementById('cocStatusSelect')?.value || 'all';
            const categoryVal = document.getElementById('cocCategorySelect')?.value || 'all';
            const sortVal = document.getElementById('cocSortSelect')?.value || 'newest';

            const params = new URLSearchParams();
            params.set('ajax', '1');
            if (searchVal) params.set('search', searchVal);
            if (statusVal && statusVal !== 'all') params.set('custodial_status', statusVal);
            if (categoryVal && categoryVal !== 'all') params.set('category', categoryVal);
            if (sortVal && sortVal !== 'newest') params.set('sort', sortVal);

            return params;
        }

        function filterByStatusCard(status) {
            const statusSelect = document.getElementById('cocStatusSelect');
            if (statusSelect) {
                statusSelect.value = status;
                fetchCocData(true);
            }
        }

        function toggleClearSearchBtn() {
            const input = document.getElementById('globalCocSearch');
            const btn = document.getElementById('cocClearSearchBtn');
            if (!input || !btn) return;
            if (input.value.trim().length > 0) {
                btn.classList.remove('hidden');
            } else {
                btn.classList.add('hidden');
            }
        }

        function clearCocSearch() {
            const input = document.getElementById('globalCocSearch');
            if (input) {
                input.value = '';
                lastSearchTerm = '';
                toggleClearSearchBtn();
                fetchCocData(true);
            }
        }

        async function fetchCocData(isManual = false) {
            if (cocAbortController) {
                cocAbortController.abort();
            }
            cocAbortController = new AbortController();

            isCocFetching = true;
            const icon = document.getElementById('cocRefreshIcon');
            if (icon) icon.classList.add('animate-spin');

            const container = document.getElementById('chainOfCustodyTableContainer');
            if (isManual && container) {
                container.style.opacity = '0.6';
            }

            try {
                const params = getCocFilterParams();
                const url = COC_BASE_URL + '?' + params.toString();

                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html, */*'
                    },
                    signal: cocAbortController.signal
                });

                if (!response.ok) throw new Error('HTTP ' + response.status);

                const html = await response.text();
                // Validate that response is indeed a partial table and not a full layout crash
                if (html && (html.includes('id="chainOfCustodyTable"') || html.includes('id="chainOfCustodyTableBody"'))) {
                    if (container) {
                        closeAllCocActionDropdowns();
                        container.innerHTML = html;
                        bindCocPaginationLinks();
                        if (window.lucide) window.lucide.createIcons();
                    }
                } else {
                    console.warn('Chain of Custody: Server response did not contain expected table markup; skipping update.');
                }
            } catch (err) {
                if (err.name !== 'AbortError') {
                    console.error('Failed to load Chain of Custody table:', err);
                }
            } finally {
                isCocFetching = false;
                if (icon) icon.classList.remove('animate-spin');
                if (container) container.style.opacity = '1';
            }
        }

        function handleCocSearchInput() {
            toggleClearSearchBtn();
            const val = (document.getElementById('globalCocSearch')?.value || '').trim();
            if (val === lastSearchTerm) return;

            if (cocDebounceTimer) clearTimeout(cocDebounceTimer);
            cocDebounceTimer = setTimeout(() => {
                lastSearchTerm = val;
                fetchCocData(false);
            }, 350);
        }

        function resetCocFilters() {
            const searchInput = document.getElementById('globalCocSearch');
            if (searchInput) searchInput.value = '';
            lastSearchTerm = '';
            toggleClearSearchBtn();

            const statusSelect = document.getElementById('cocStatusSelect');
            if (statusSelect) statusSelect.value = 'all';

            const categorySelect = document.getElementById('cocCategorySelect');
            if (categorySelect) categorySelect.value = 'all';

            const sortSelect = document.getElementById('cocSortSelect');
            if (sortSelect) sortSelect.value = 'newest';

            fetchCocData(true);
        }

        function bindCocPaginationLinks() {
            const container = document.getElementById('chainOfCustodyTableContainer');
            if (!container) return;

            const links = container.querySelectorAll('.coc-pagination a');
            links.forEach(link => {
                link.addEventListener('click', async function(e) {
                    e.preventDefault();
                    const href = this.getAttribute('href');
                    if (!href) return;

                    const targetUrl = new URL(href, window.location.origin);
                    targetUrl.searchParams.set('ajax', '1');

                    if (cocAbortController) cocAbortController.abort();
                    cocAbortController = new AbortController();

                    container.style.opacity = '0.6';
                    try {
                        const res = await fetch(targetUrl.toString(), {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'text/html, */*'
                            },
                            signal: cocAbortController.signal
                        });
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        const html = await res.text();
                        if (html && (html.includes('id="chainOfCustodyTable"') || html.includes('id="chainOfCustodyTableBody"'))) {
                            container.innerHTML = html;
                            bindCocPaginationLinks();
                            if (window.lucide) window.lucide.createIcons();
                            container.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    } catch (err) {
                        if (err.name !== 'AbortError') {
                            console.error('Pagination load failed:', err);
                        }
                    } finally {
                        container.style.opacity = '1';
                    }
                });
            });
        }

        function openCocDetailModalFromBtn(btn) {
            try {
                const data = JSON.parse(btn.getAttribute('data-coc-details') || '{}');
                openCocDetailModal(data);
            } catch (e) {
                console.error('Failed to parse custody details:', e);
            }
        }

        function openCocDetailModal(data) {
            document.getElementById('modalExhibitNumber').textContent = data.exhibit_number || 'EXH-PENDING';
            document.getElementById('modalPropertyId').textContent = data.property_id || 'N/A';
            document.getElementById('modalCaseNumber').textContent = data.case_number || 'N/A';
            document.getElementById('modalCustodialStatus').textContent = data.custodial_status || 'Seized';
            document.getElementById('modalCommodityName').textContent = data.commodity_name || 'N/A';
            document.getElementById('modalExhibitCategory').textContent = data.exhibit_category || 'General';
            document.getElementById('modalWeightQuantity').textContent = (data.seizure_weight ? data.seizure_weight + ' ' + (data.seizure_unit || 'g') : 'N/A');
            document.getElementById('modalPackaging').textContent = data.seizure_packaging || 'Standard Packaging';
            document.getElementById('modalChainOfCustody').textContent = data.chain_of_custody || 'Seizure Site -> Central Evidence Vault';
            document.getElementById('modalSeizureOfficer').textContent = data.seizure_officer || 'Assigned Officer';
            document.getElementById('modalInvestigatingOfficer').textContent = data.investigating_officer || 'N/A';
            document.getElementById('modalRequestingUnit').textContent = data.source_requesting_unit || 'Evidence Management';
            document.getElementById('modalSourceExhibit').textContent = data.source_of_exhibits || 'Field Intake';
            document.getElementById('modalLocation').textContent = data.seizure_location || 'N/A';
            document.getElementById('modalCreatedAt').textContent = data.created_at || 'N/A';

            document.getElementById('cocDetailModal').style.display = 'flex';
            if (window.lucide) window.lucide.createIcons();
        }

        function closeCocDetailModal() {
            document.getElementById('cocDetailModal').style.display = 'none';
        }

        function toggleCocActionDropdown(event, id) {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            const btn = event ? (event.currentTarget || (event.target ? event.target.closest('button') : null)) : null;
            const targetMenu = document.getElementById('cocActionMenu-' + id);
            if (!targetMenu) return;

            const isCurrentlyOpen = !targetMenu.classList.contains('hidden');

            closeAllCocActionDropdowns();

            if (!isCurrentlyOpen) {
                targetMenu.classList.remove('hidden');

                if (btn) {
                    btn.setAttribute('aria-expanded', 'true');
                    const chevron = btn.querySelector('.dropdown-chevron');
                    if (chevron) chevron.style.transform = 'rotate(180deg)';

                    const rect = btn.getBoundingClientRect();
                    const menuWidth = 192; // 12rem = 192px
                    const menuHeight = targetMenu.offsetHeight || 130;

                    let top = rect.bottom + 4;
                    let left = rect.right - menuWidth;

                    if (top + menuHeight > window.innerHeight - 10 && rect.top > menuHeight + 10) {
                        top = rect.top - menuHeight - 4;
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

        function closeAllCocActionDropdowns() {
            document.querySelectorAll('.coc-action-menu').forEach(menu => {
                menu.classList.add('hidden');
            });
            document.querySelectorAll('.coc-action-dropdown-wrapper button').forEach(btn => {
                btn.setAttribute('aria-expanded', 'false');
                const chevron = btn.querySelector('.dropdown-chevron');
                if (chevron) chevron.style.transform = 'rotate(0deg)';
            });
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.coc-action-dropdown-wrapper')) {
                closeAllCocActionDropdowns();
            }
        });
        window.addEventListener('scroll', closeAllCocActionDropdowns, true);
        window.addEventListener('resize', closeAllCocActionDropdowns);

        document.getElementById('cocDetailModal')?.addEventListener('click', function(e) {
            if (e.target === this) closeCocDetailModal();
        });

        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const input = document.getElementById('globalCocSearch');
                if (input) input.focus();
            }
            if (e.key === 'Escape') {
                closeCocDetailModal();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            if (window.lucide) window.lucide.createIcons();
            bindCocPaginationLinks();
        });
    </script>
</body>
</html>
