@php
    $authUser = auth()->user();
    $reqUnit = request()->query('unit') ?? request()->query('from') ?? $unitFilter ?? null;
    
    if (!empty($reqUnit)) {
        if (str_contains(strtolower($reqUnit), 'crime') || str_contains(strtolower($reqUnit), 'field')) {
            $activeUnitName = 'Crime Scene Support';
        } elseif (str_contains(strtolower($reqUnit), 'lab')) {
            $activeUnitName = 'Lab Analysis';
        } elseif (str_contains(strtolower($reqUnit), 'evidence')) {
            $activeUnitName = 'Evidence Management';
        } elseif (str_contains(strtolower($reqUnit), 'digital') || str_contains(strtolower($reqUnit), 'physical')) {
            $activeUnitName = 'Digital & Physical Forensics';
        } else {
            $activeUnitName = $reqUnit;
        }
    } elseif (request()->is('Lab_Analysis*')) {
        $activeUnitName = 'Lab Analysis';
    } elseif (request()->is('Evidence_Management*')) {
        $activeUnitName = 'Evidence Management';
    } elseif (request()->is('Digital_Forensics*')) {
        $activeUnitName = 'Digital & Physical Forensics';
    } elseif (request()->is('Crime_Scene_Support*')) {
        $activeUnitName = 'Crime Scene Support';
    } elseif (request()->is('Forensic_Head*')) {
        $activeUnitName = 'All Units';
    } elseif ($authUser && !empty($authUser->unit)) {
        $activeUnitName = $authUser->unit;
    } else {
        $activeUnitName = 'All Units';
    }

    $isHead = ($authUser && $authUser->is_head && str_contains(strtolower($authUser->unit ?? ''), 'forensic')) || request()->is('Forensic_Head*');

    $datasetCases = $allCases ?? \App\Models\ForensicCase::allCases();

    $totalCasesCount = count($datasetCases);
    $approvedCount = $datasetCases->filter(fn($c) => in_array($c->status, ['Approved', 'Authorized']))->count();
    $inProgressCount = $datasetCases->filter(fn($c) => in_array($c->status, ['Under Multi-Unit Processing', 'In Progress', 'Draft']))->count();
    $awaitingSignOffCount = $datasetCases->filter(fn($c) => in_array($c->status, ['Awaiting Sign-off', 'Pending Approval', 'Pending']))->count();

    $cases = $cases ?? \App\Models\ForensicCase::paginateCases($datasetCases);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unit Docket History &amp; Audit Trail Log | NACOC Forensic</title>

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
    <script src="{{ asset('js/ajax-table.js') }}"></script>

    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #F8FAFC;
            color: #0F172A;
        }
        .font-mono-code {
            font-family: 'JetBrains Mono', monospace;
        }
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #F1F5F9;
        }
        ::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94A3B8;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-slate-50 text-slate-900 antialiased selection:bg-emerald-600 selection:text-white">

    <!-- SYSTEM APP CONTAINER WITH SIDEBAR & MAIN WORKSPACE -->
    <div class="flex h-screen overflow-hidden">

        <!-- LEFT SIDEBAR INCLUSION -->
        @if($isHead)
            @include('Forensic_Head.partials.sidebar')
        @else
            @include('Crime_Scene_Support.partials.sidebar')
        @endif

        <!-- RIGHT WORKSPACE MAIN EXECUTIVE PANEL -->
        <div class="flex-1 flex flex-col min-w-0 bg-slate-50 overflow-hidden">

            <!-- SYSTEM TOP NAVBAR -->
            <header class="h-16 bg-white border-b border-slate-200/90 px-6 flex items-center justify-between shrink-0 shadow-2xs">
                <!-- SEARCH BAR -->
                <div class="flex items-center flex-1 max-w-lg">
                    <div class="relative w-full">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input 
                            type="text" 
                            id="historySearchInput" 
                            onkeyup="filterHistoryTable()" 
                            oninput="filterHistoryTable()" 
                            placeholder="Filter History: Case Ref, Suspect, Exhibit, Officer, Date..." 
                            class="w-full pl-10 pr-12 py-2 bg-slate-100/70 border border-slate-200 rounded-xl text-xs text-slate-900 font-mono-code focus:outline-none focus:bg-white focus:border-purple-600 focus:ring-2 focus:ring-purple-600/15 transition-all"
                        >
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3">
                            <kbd class="px-1.5 py-0.5 text-[10px] font-mono-code font-semibold text-slate-400 bg-white rounded border border-slate-200 shadow-2xs">Ctrl K</kbd>
                        </span>
                    </div>
                </div>

                <!-- TOP ACTIONS & BADGES -->
                <div class="flex items-center space-x-3">
                    <span class="px-3 py-1.5 bg-purple-50 text-purple-800 text-xs font-mono-code font-bold rounded-xl border border-purple-200 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>HISTORICAL ARCHIVE ACTIVE</span>
                    </span>
                </div>
            </header>

            <!-- MAIN WORKSPACE VIEW -->
            <main class="flex-1 overflow-y-auto p-6 space-y-6">
                @include('partials.flash')

                <!-- HEADER BANNER -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs">
                    <div>
                        <div class="flex items-center space-x-2 mb-1">
                            <span class="px-2.5 py-0.5 bg-purple-600 text-white text-[10px] font-extrabold font-mono-code rounded-md uppercase">UNIT HISTORY &amp; AUDIT LOG</span>
                            <span class="text-xs text-slate-400">&bull;</span>
                            <span class="text-xs font-bold text-slate-700 font-mono-code">{{ strtoupper($activeUnitName) }} DIRECTORATE</span>
                        </div>
                        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Case Docket &amp; Seizure History Log</h1>
                        <p class="text-xs text-slate-500 mt-1">Complete historical record of created dockets, laboratory test entries, exhibit lodgements, and head sign-offs across all units.</p>
                    </div>

                    <div class="flex items-center space-x-2">
                        <button onclick="window.print()" class="inline-flex items-center px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl shadow-xs transition-colors cursor-pointer">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            Print History Log
                        </button>
                    </div>
                </div>

                <!-- KPI METRICS SUMMARY -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold font-mono-code uppercase text-slate-500">Total Logged Dockets</span>
                            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                                📊
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="text-2xl font-extrabold text-slate-900 font-mono-code tracking-tight">{{ $totalCasesCount }} Cases</div>
                            <div class="text-[11px] font-semibold text-purple-600 mt-1">Archived in Master Registry</div>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold font-mono-code uppercase text-slate-500">Approved &amp; Authorized</span>
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                                ✓
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="text-2xl font-extrabold text-emerald-600 font-mono-code tracking-tight">{{ $approvedCount }} Completed</div>
                            <div class="text-[11px] font-semibold text-slate-500 mt-1">Directorate Sign-off Secured</div>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold font-mono-code uppercase text-slate-500">Under Processing</span>
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                                ⚙
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="text-2xl font-extrabold text-blue-700 font-mono-code tracking-tight">{{ $inProgressCount }} Active</div>
                            <div class="text-[11px] font-semibold text-slate-500 mt-1">Multi-Unit Investigation Log</div>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold font-mono-code uppercase text-slate-500">Awaiting Sign-off</span>
                            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                                ⏳
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="text-2xl font-extrabold text-amber-600 font-mono-code tracking-tight">{{ $awaitingSignOffCount }} Pending</div>
                            <div class="text-[11px] font-semibold text-slate-500 mt-1">Submitted for Head Authorization</div>
                        </div>
                    </div>
                </div>

                <!-- UNIT FILTER TABS & SEARCH TABLE -->
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden space-y-4 p-5">
                    
                    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 pb-4 border-b border-slate-200/80">
                        <!-- UNIT TABS -->
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <button onclick="filterByUnitTab('All Units', this)" class="unit-tab-btn px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all bg-purple-600 text-white shadow-sm cursor-pointer">
                                All Units
                            </button>
                            <button onclick="filterByUnitTab('Crime Scene Support', this)" class="unit-tab-btn px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all bg-slate-100 text-slate-700 hover:bg-slate-200 cursor-pointer">
                                Crime Scene Support
                            </button>
                            <button onclick="filterByUnitTab('Lab Analysis', this)" class="unit-tab-btn px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all bg-slate-100 text-slate-700 hover:bg-slate-200 cursor-pointer">
                                Lab Analysis
                            </button>
                            <button onclick="filterByUnitTab('Evidence Management', this)" class="unit-tab-btn px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all bg-slate-100 text-slate-700 hover:bg-slate-200 cursor-pointer">
                                Evidence Management
                            </button>
                            <button onclick="filterByUnitTab('Digital & Physical Forensics', this)" class="unit-tab-btn px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all bg-slate-100 text-slate-700 hover:bg-slate-200 cursor-pointer">
                                Digital &amp; Physical Forensics
                            </button>
                        </div>

                        <!-- STATUS FILTER DROPDOWN -->
                        <div class="flex items-center space-x-2">
                            <span class="text-xs font-mono-code text-slate-500 font-bold uppercase">Status:</span>
                            <select id="statusFilterSelect" onchange="filterHistoryTable()" class="px-3 py-1.5 bg-slate-100 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 outline-none cursor-pointer">
                                <option value="ALL">All Statuses</option>
                                <option value="APPROVED">Approved / Authorized</option>
                                <option value="PENDING">Awaiting Sign-off / Pending</option>
                                <option value="PROCESSING">Under Multi-Unit Processing</option>
                            </select>
                        </div>
                    </div>

                    <!-- DOCKET HISTORY DATA TABLE -->
                    <div id="historyTableContainer">
                        @include('partials.history_table')
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- JAVASCRIPT FOR HISTORY FILTERING -->
    <script>
        let currentUnitFilter = 'All Units';

        // Initialize Reusable AJAX Table Controller for System History
        function setupHistoryAjaxTable() {
            if (typeof initAjaxTable !== 'function') return;
            if (window.historyAjaxTable) return;

            window.historyAjaxTable = initAjaxTable({
                container: '#historyTableContainer',
                table: '#historyTable',
                columns: 6,
                rows: 6
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupHistoryAjaxTable);
        } else {
            setupHistoryAjaxTable();
        }

        function filterByUnitTab(unitName, btnEl) {
            currentUnitFilter = unitName;

            const tabs = document.querySelectorAll('.unit-tab-btn');
            tabs.forEach(t => {
                t.className = 'unit-tab-btn px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all bg-slate-100 text-slate-700 hover:bg-slate-200 cursor-pointer';
            });
            if (btnEl) {
                btnEl.className = 'unit-tab-btn px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all bg-purple-600 text-white shadow-sm cursor-pointer';
            }

            const searchVal = (document.getElementById('historySearchInput')?.value || '').trim();
            const statusVal = document.getElementById('statusFilterSelect')?.value || 'ALL';

            if (window.historyAjaxTable) {
                window.historyAjaxTable.applyFilters({
                    unit: unitName === 'All Units' ? null : unitName,
                    search: searchVal || null,
                    status: statusVal === 'ALL' ? null : statusVal,
                    page: null
                });
            }
        }

        function filterHistoryTable() {
            const query = (document.getElementById('historySearchInput')?.value || '').trim();
            const statusVal = document.getElementById('statusFilterSelect')?.value || 'ALL';

            if (window.historyAjaxTable) {
                if (window.historyAjaxTable.debounceTimer) clearTimeout(window.historyAjaxTable.debounceTimer);
                window.historyAjaxTable.debounceTimer = setTimeout(() => {
                    window.historyAjaxTable.applyFilters({
                        search: query || null,
                        unit: currentUnitFilter === 'All Units' ? null : currentUnitFilter,
                        status: statusVal === 'ALL' ? null : statusVal,
                        page: null
                    });
                }, 350);
            }
        }

        // Keyboard Shortcut Ctrl+K
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const input = document.getElementById('historySearchInput');
                if (input) input.focus();
            }
        });
    </script>
</body>
</html>
