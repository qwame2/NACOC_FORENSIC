<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forensic Department Personnel Details</title>

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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-sm font-extrabold text-slate-900 tracking-wide font-mono-code uppercase">
                            Forensic Department Personnel Details
                        </h1>
                        <p class="text-[11px] text-slate-500 font-medium">
                            All Registered System Personnel Across Department &amp; Operational Units
                        </p>
                    </div>
                </div>

                <!-- DEBOUNCED SEARCH INPUT -->
                <div class="flex items-center flex-1 max-w-md mx-4">
                    <div class="relative w-full">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input
                            type="text"
                            name="search"
                            id="personnelSearchInput"
                            value="{{ $search ?? '' }}"
                            placeholder="Search by name, service ID, username, unit, phone, or email..."
                            data-table-filter="#personnelTableContainer"
                            class="w-full pl-10 pr-16 py-1.5 bg-slate-100/80 border border-slate-200 rounded-xl text-xs text-slate-900 font-mono-code focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15 transition-all"
                        >
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <kbd class="px-1.5 py-0.5 text-[10px] font-mono-code font-semibold text-emerald-700 bg-emerald-50 rounded border border-emerald-200 shadow-2xs">Live</kbd>
                        </span>
                    </div>
                </div>

                <!-- RIGHT HEADER INFO -->
                <div class="flex items-center space-x-4 shrink-0">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs uppercase">
                            {{ substr(auth()->user()->fullname ?? 'H', 0, 1) }}
                        </div>
                        <div class="hidden sm:block text-left">
                            @php
                                $headerUnitRaw = auth()->user()->unit ?? 'Forensic Department';
                                $headerUnitDisplay = strcasecmp(trim($headerUnitRaw), 'Forensic Directorate') === 0 ? 'Forensic Department' : $headerUnitRaw;
                            @endphp
                            <div class="text-xs font-bold text-slate-900 leading-tight">{{ auth()->user()->fullname ?? 'Department Head' }}</div>
                            <div class="text-[10px] text-slate-500 font-mono-code">{{ $headerUnitDisplay }}</div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- SCROLLABLE PAGE BODY -->
            <main class="flex-1 overflow-y-auto p-6 space-y-6">

                <!-- 1. KPI SUMMARY CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <button type="button" onclick="applyPersonnelQuickFilter('all', 'all', 'all')" class="glass-panel-white rounded-2xl p-4 flex items-center justify-between border-l-4 border-l-slate-900 text-left hover:shadow-md transition-shadow cursor-pointer bg-white">
                        <div>
                            <div class="text-[10px] font-bold font-mono-code text-slate-500 uppercase tracking-wider">Total Personnel</div>
                            <div class="text-2xl font-black text-slate-900 font-mono-code mt-1" id="kpiTotalPersonnel">{{ number_format($totalUsersCount ?? 0) }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">All Registered Users</div>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-800">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                    </button>

                    <button type="button" onclick="applyPersonnelQuickFilter('all', 'head', 'all')" class="glass-panel-white rounded-2xl p-4 flex items-center justify-between border-l-4 border-l-blue-600 text-left hover:shadow-md transition-shadow cursor-pointer bg-white">
                        <div>
                            <div class="text-[10px] font-bold font-mono-code text-blue-700 uppercase tracking-wider">Unit &amp; Dept Heads</div>
                            <div class="text-2xl font-black text-blue-900 font-mono-code mt-1" id="kpiHeadCount">{{ number_format($unitHeadsCount ?? 0) }}</div>
                            <div class="text-[11px] text-blue-600 mt-0.5">Command Leadership</div>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                    </button>

                    <button type="button" onclick="applyPersonnelQuickFilter('all', 'all', 'approved')" class="glass-panel-white rounded-2xl p-4 flex items-center justify-between border-l-4 border-l-emerald-600 text-left hover:shadow-md transition-shadow cursor-pointer bg-white">
                        <div>
                            <div class="text-[10px] font-bold font-mono-code text-emerald-700 uppercase tracking-wider">Approved Personnel</div>
                            <div class="text-2xl font-black text-emerald-900 font-mono-code mt-1" id="kpiApprovedCount">{{ number_format($approvedUsersCount ?? 0) }}</div>
                            <div class="text-[11px] text-emerald-600 mt-0.5">Verified Active Accounts</div>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </button>
                </div>

                <!-- 2. FILTER TABS & CONTROL BAR -->
                <div class="glass-panel-white rounded-2xl p-4 space-y-4">

                    <div class="flex items-center justify-between flex-wrap gap-3 border-b border-slate-200 pb-3">
                        <div class="flex items-center gap-1.5 flex-wrap" id="unitQuickTabsGroup">
                            <button type="button" onclick="switchPersonnelUnitTab('all', this)" class="filter-tab-btn {{ ($unitFilter ?? 'all') === 'all' ? 'active-tab' : '' }}">All Units</button>
                            <button type="button" onclick="switchPersonnelUnitTab('css', this)" class="filter-tab-btn {{ ($unitFilter ?? '') === 'css' ? 'active-tab' : '' }}">Crime Scene Support</button>
                            <button type="button" onclick="switchPersonnelUnitTab('em', this)" class="filter-tab-btn {{ ($unitFilter ?? '') === 'em' ? 'active-tab' : '' }}">Evidence Management</button>
                            <button type="button" onclick="switchPersonnelUnitTab('lab', this)" class="filter-tab-btn {{ ($unitFilter ?? '') === 'lab' ? 'active-tab' : '' }}">Lab Analysis</button>
                            <button type="button" onclick="switchPersonnelUnitTab('dpf', this)" class="filter-tab-btn {{ ($unitFilter ?? '') === 'dpf' ? 'active-tab' : '' }}">Digital &amp; Physical</button>
                            <button type="button" onclick="switchPersonnelUnitTab('directorate', this)" class="filter-tab-btn {{ ($unitFilter ?? '') === 'directorate' ? 'active-tab' : '' }}">Forensic Department</button>
                        </div>

                        <div class="text-xs text-slate-500 font-mono-code" id="personnelResultsCounter">
                            Showing <span class="font-bold text-slate-900">{{ $users->firstItem() ?? 0 }}</span> - <span class="font-bold text-slate-900">{{ $users->lastItem() ?? 0 }}</span> of <span class="font-bold text-slate-900">{{ $users->total() }}</span> personnel
                        </div>
                    </div>

                    <form action="{{ url('/Forensic_Head/personnel-roster') }}" method="GET" id="personnelFilterForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <input type="hidden" name="search" id="personnelHiddenSearch" value="{{ $search ?? '' }}">

                        <div>
                            <label for="unitSelectFilter" class="block text-[10px] font-bold font-mono-code uppercase text-slate-500 mb-1">Filter by Unit</label>
                            <select name="unit" id="unitSelectFilter" class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:border-emerald-600">
                                <option value="all" {{ ($unitFilter ?? 'all') === 'all' ? 'selected' : '' }}>All Forensic Units</option>
                                <option value="css" {{ ($unitFilter ?? '') === 'css' ? 'selected' : '' }}>Crime Scene Support (CSSU)</option>
                                <option value="em" {{ ($unitFilter ?? '') === 'em' ? 'selected' : '' }}>Evidence Management (EMU)</option>
                                <option value="lab" {{ ($unitFilter ?? '') === 'lab' ? 'selected' : '' }}>Lab Analysis (LAU)</option>
                                <option value="dpf" {{ ($unitFilter ?? '') === 'dpf' ? 'selected' : '' }}>Digital &amp; Physical Forensics (DPFU)</option>
                                <option value="directorate" {{ ($unitFilter ?? '') === 'directorate' ? 'selected' : '' }}>Forensic Department (FDept)</option>
                            </select>
                        </div>

                        <div>
                            <label for="roleSelectFilter" class="block text-[10px] font-bold font-mono-code uppercase text-slate-500 mb-1">Command Role</label>
                            <select name="role" id="roleSelectFilter" class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:border-emerald-600">
                                <option value="all" {{ ($roleFilter ?? 'all') === 'all' ? 'selected' : '' }}>All Roles</option>
                                <option value="head" {{ ($roleFilter ?? '') === 'head' ? 'selected' : '' }}>Units / Department Heads</option>
                                <option value="staff" {{ ($roleFilter ?? '') === 'staff' ? 'selected' : '' }}>Standard Officers / Staff</option>
                            </select>
                        </div>

                        <div>
                            <label for="statusSelectFilter" class="block text-[10px] font-bold font-mono-code uppercase text-slate-500 mb-1">Account Status</label>
                            <select name="status" id="statusSelectFilter" class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:border-emerald-600">
                                <option value="all" {{ ($statusFilter ?? 'all') === 'all' ? 'selected' : '' }}>All Statuses</option>
                                <option value="approved" {{ ($statusFilter ?? '') === 'approved' ? 'selected' : '' }}>Approved / Active</option>
                                <option value="pending" {{ ($statusFilter ?? '') === 'pending' ? 'selected' : '' }}>Pending Verification</option>
                            </select>
                        </div>

                        <div class="flex items-end gap-2">
                            <div class="flex-1">
                                <label for="perPageSelectFilter" class="block text-[10px] font-bold font-mono-code uppercase text-slate-500 mb-1">Rows</label>
                                <select name="per_page" id="perPageSelectFilter" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:border-emerald-600">
                                    <option value="15" {{ ($perPage ?? 15) == 15 ? 'selected' : '' }}>15</option>
                                    <option value="25" {{ ($perPage ?? 15) == 25 ? 'selected' : '' }}>25</option>
                                    <option value="50" {{ ($perPage ?? 15) == 50 ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ ($perPage ?? 15) == 100 ? 'selected' : '' }}>100</option>
                                </select>
                            </div>
                            <button type="button" onclick="resetPersonnelFilters()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold font-mono-code transition-colors shrink-0 border-none cursor-pointer">
                                Reset
                            </button>
                        </div>
                    </form>
                </div>

                <!-- 3. PERSONNEL ROSTER TABLE WITH SKELETON LOADER -->
                <div class="glass-panel-white rounded-2xl overflow-hidden relative min-h-[360px]" id="personnelTableContainer">
                    @include('Forensic_Head.partials.personnel_table')
                </div>

            </main>
        </div>
    </div>

    <script>
        function renderPersonnelSkeletonRows(colCount = 6, rowCount = 6) {
            const nameWidths = ['w-36', 'w-44', 'w-32', 'w-40', 'w-28', 'w-36'];
            const unitWidths = ['w-40', 'w-44', 'w-36', 'w-48', 'w-32', 'w-40'];
            let html = '';

            for (let i = 0; i < rowCount; i++) {
                const nw = nameWidths[i % nameWidths.length];
                const uw = unitWidths[i % unitWidths.length];
                html += `<tr class="animate-pulse border-b border-slate-100 hover:bg-transparent">
                    <td class="py-4 px-5 whitespace-nowrap">
                        <div class="h-3.5 bg-slate-200 rounded w-24"></div>
                        <div class="h-2.5 bg-slate-100 rounded w-20 mt-1.5"></div>
                    </td>
                    <td class="py-3.5 px-5">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 rounded-xl bg-slate-200 shrink-0"></div>
                            <div class="space-y-1.5">
                                <div class="h-3.5 bg-slate-200 rounded ${nw}"></div>
                                <div class="h-2.5 bg-slate-100 rounded w-24"></div>
                            </div>
                        </div>
                    </td>
                    <td class="py-4 px-5 whitespace-nowrap">
                        <div class="h-6 bg-slate-200 rounded-lg ${uw}"></div>
                    </td>
                    <td class="py-4 px-5 text-center whitespace-nowrap">
                        <div class="h-5 bg-slate-200 rounded-full w-24 mx-auto"></div>
                    </td>
                    <td class="py-4 px-5">
                        <div class="h-3 bg-slate-200 rounded w-28"></div>
                        <div class="h-2.5 bg-slate-100 rounded w-36 mt-1.5"></div>
                    </td>
                    <td class="py-4 px-5 text-center whitespace-nowrap">
                        <div class="h-5 bg-slate-200 rounded-full w-20 mx-auto"></div>
                    </td>
                </tr>`;
            }

            return html;
        }

        function syncPersonnelCountersFromDom() {
            const meta = document.getElementById('personnelTableMeta');
            if (!meta) return;

            const first = meta.getAttribute('data-first-item') || '0';
            const last = meta.getAttribute('data-last-item') || '0';
            const total = meta.getAttribute('data-total') || '0';

            const counterEl = document.getElementById('personnelResultsCounter');
            if (counterEl) {
                counterEl.innerHTML = `Showing <span class="font-bold text-slate-900">${first}</span> - <span class="font-bold text-slate-900">${last}</span> of <span class="font-bold text-slate-900">${Number(total).toLocaleString()}</span> personnel`;
            }

            const totalAll = meta.getAttribute('data-total-all');
            const headCount = meta.getAttribute('data-head-count');
            const approvedCount = meta.getAttribute('data-approved-count');

            if (totalAll !== null) {
                const el = document.getElementById('kpiTotalPersonnel');
                if (el) el.textContent = Number(totalAll).toLocaleString();
            }
            if (headCount !== null) {
                const el = document.getElementById('kpiHeadCount');
                if (el) el.textContent = Number(headCount).toLocaleString();
            }
            if (approvedCount !== null) {
                const el = document.getElementById('kpiApprovedCount');
                if (el) el.textContent = Number(approvedCount).toLocaleString();
            }
        }

        function syncUnitTabActiveState(unitSlug) {
            const buttons = document.querySelectorAll('#unitQuickTabsGroup .filter-tab-btn');
            const tabOrder = ['all', 'css', 'em', 'lab', 'dpf', 'directorate'];
            buttons.forEach((btn, idx) => {
                if (tabOrder[idx] === (unitSlug || 'all')) {
                    btn.classList.add('active-tab');
                } else {
                    btn.classList.remove('active-tab');
                }
            });
        }

        function setupPersonnelAjaxTable() {
            if (typeof initAjaxTable === 'function' && !window.personnelAjaxTable) {
                window.personnelAjaxTable = initAjaxTable({
                    container: '#personnelTableContainer',
                    table: '#personnelTable',
                    form: '#personnelFilterForm',
                    endpoint: '{{ url('/Forensic_Head/personnel-roster') }}',
                    debounce: 300,
                    columns: 6,
                    rows: 6,
                    skeletonRenderer: renderPersonnelSkeletonRows,
                    onAfterUpdate: () => {
                        syncPersonnelCountersFromDom();
                        const unitEl = document.getElementById('unitSelectFilter');
                        if (unitEl) syncUnitTabActiveState(unitEl.value);
                    }
                });
            }

            const searchInput = document.getElementById('personnelSearchInput');
            if (searchInput && !searchInput.dataset.boundSync) {
                searchInput.dataset.boundSync = '1';
                searchInput.addEventListener('input', function () {
                    const hidden = document.getElementById('personnelHiddenSearch');
                    if (hidden) hidden.value = searchInput.value.trim();
                });
            }

            const unitSelect = document.getElementById('unitSelectFilter');
            if (unitSelect && !unitSelect.dataset.boundSync) {
                unitSelect.dataset.boundSync = '1';
                unitSelect.addEventListener('change', function () {
                    syncUnitTabActiveState(unitSelect.value);
                });
            }
        }

        function switchPersonnelUnitTab(unitSlug, btn) {
            const unitSelect = document.getElementById('unitSelectFilter');
            if (unitSelect) unitSelect.value = unitSlug || 'all';
            syncUnitTabActiveState(unitSlug || 'all');

            if (window.personnelAjaxTable) {
                window.personnelAjaxTable.applyFilters({ unit: unitSlug || 'all' });
            }
        }

        function applyPersonnelQuickFilter(unitSlug, roleSlug, statusSlug) {
            const unitSelect = document.getElementById('unitSelectFilter');
            const roleSelect = document.getElementById('roleSelectFilter');
            const statusSelect = document.getElementById('statusSelectFilter');

            if (unitSelect) unitSelect.value = unitSlug;
            if (roleSelect) roleSelect.value = roleSlug;
            if (statusSelect) statusSelect.value = statusSlug;
            syncUnitTabActiveState(unitSlug);

            if (window.personnelAjaxTable) {
                window.personnelAjaxTable.applyFilters({
                    unit: unitSlug,
                    role: roleSlug,
                    status: statusSlug
                });
            }
        }

        function resetPersonnelFilters() {
            const form = document.getElementById('personnelFilterForm');
            if (form) form.reset();

            const searchInput = document.getElementById('personnelSearchInput');
            if (searchInput) searchInput.value = '';
            const hiddenSearch = document.getElementById('personnelHiddenSearch');
            if (hiddenSearch) hiddenSearch.value = '';

            syncUnitTabActiveState('all');

            if (window.personnelAjaxTable) {
                window.personnelAjaxTable.applyFilters({
                    search: '',
                    unit: 'all',
                    role: 'all',
                    status: 'all',
                    per_page: 15
                });
            } else {
                window.location.href = '{{ url('/Forensic_Head/personnel-roster') }}';
            }
        }

        let isPollingPersonnelRoster = false;
        async function pollPersonnelRoster() {
            if (isPollingPersonnelRoster || document.hidden) return;
            if (window.personnelAjaxTable && window.personnelAjaxTable.isLoading) return;

            isPollingPersonnelRoster = true;
            const controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
            const timeoutId = controller ? setTimeout(() => controller.abort(), 6000) : null;

            try {
                const params = new URLSearchParams(window.location.search);
                params.set('poll', '1');
                const pollUrl = '{{ url('/Forensic_Head/personnel-roster') }}?' + params.toString();

                const res = await fetch(pollUrl, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                    signal: controller ? controller.signal : undefined
                });
                if (timeoutId) clearTimeout(timeoutId);
                if (!res.ok) return;

                const data = await res.json();
                if (window.personnelAjaxTable && window.personnelAjaxTable.isLoading) return;

                if (data.html) {
                    const container = document.getElementById('personnelTableContainer');
                    if (container) {
                        container.innerHTML = data.html;
                        syncPersonnelCountersFromDom();
                    }
                }
            } catch (e) {
                // Silent catch during background polling
            } finally {
                if (timeoutId) clearTimeout(timeoutId);
                isPollingPersonnelRoster = false;
            }
        }

        setupPersonnelAjaxTable();
        document.addEventListener('DOMContentLoaded', setupPersonnelAjaxTable);
        setInterval(pollPersonnelRoster, 10000);
    </script>
</body>
</html>

