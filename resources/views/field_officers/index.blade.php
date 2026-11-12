<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Field Officers Directory | NACOC Forensic</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

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
        .glass-panel-white {
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid #E2E8F0;
            box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.05);
        }
        .filter-tab-btn {
            padding: 6px 14px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            background: transparent;
            transition: all 0.2s ease;
            cursor: pointer;
            border: 1px solid transparent;
        }
        .filter-tab-btn:hover {
            background: #F1F5F9;
            color: #0F172A;
        }
        .filter-tab-btn.active-tab {
            background: #059669;
            color: #FFFFFF;
            box-shadow: 0 4px 10px rgba(5, 150, 105, 0.25);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-white text-slate-900 antialiased selection:bg-emerald-600 selection:text-white">

    <div class="flex h-screen overflow-hidden">

        <!-- UNIT COMMAND SIDEBAR -->
        @include('Crime_Scene_Support.partials.sidebar')

        <!-- MAIN WORKSPACE CONTENT -->
        <div class="flex-1 flex flex-col min-w-0 bg-white overflow-hidden">

            <!-- TOP NAVBAR -->
            <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 z-10 shadow-xs gap-4">

                <div class="flex items-center space-x-4 shrink-0">
                    <div class="w-9 h-9 rounded-xl bg-emerald-600/10 border border-emerald-600/20 flex items-center justify-center text-emerald-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-sm font-extrabold text-slate-900 tracking-wide font-mono-code uppercase">
                            Unit Field Officers &amp; Staff Details
                        </h1>
                        <p class="text-[11px] text-slate-500 font-medium">
                            {{ !empty($isUnitScoped) ? (($viewerUnit?->value ?? 'Assigned Unit') . ' Staff Officers & Head-of-Unit Account Approval Console') : 'All Operational Unit Staff Officers & Head-of-Unit Account Approval Console' }}
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
                            id="fieldOfficersSearchInput"
                            value="{{ $search ?? '' }}"
                            placeholder="Search staff by name, service ID, username, unit, phone, or email..."
                            data-table-filter="#fieldOfficersTableContainer"
                            class="w-full pl-10 pr-16 py-1.5 bg-slate-100/80 border border-slate-200 rounded-xl text-xs text-slate-900 font-mono-code focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15 transition-all"
                        >
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <kbd class="px-1.5 py-0.5 text-[10px] font-mono-code font-semibold text-emerald-700 bg-emerald-50 rounded border border-emerald-200 shadow-2xs">Live</kbd>
                        </span>
                    </div>
                </div>

                <!-- OFFICER BADGE -->
                <div class="flex items-center space-x-3 shrink-0">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs uppercase">
                            {{ substr(auth()->user()->fullname ?? 'U', 0, 1) }}
                        </div>
                        <div class="hidden sm:block text-left">
                            <div class="text-xs font-bold text-slate-900 leading-tight">{{ auth()->user()->fullname ?? 'Officer' }}</div>
                            <div class="text-[10px] text-slate-500 font-mono-code">
                                {{ (auth()->user()->unit ?? 'Operational Unit') . (auth()->user()?->is_head ? ' Head' : ' Staff') }}
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- SCROLLABLE PAGE BODY -->
            <main class="flex-1 overflow-y-auto p-6 space-y-6">

                @include('partials.flash')

                <!-- 1. KPI SUMMARY CARDS -->
                @php
                    $defaultUnitFilter = $defaultUnitFilter ?? ($scopedUnitSlug ?? 'all');
                    $isUnitScoped = !empty($isUnitScoped);
                    $unitScopeLabels = [
                        'css' => 'Crime Scene Support (CSSU)',
                        'em'  => 'Evidence Management (EMU)',
                        'lab' => 'Lab Analysis (LAU)',
                        'dpf' => 'Digital & Physical Forensics (DPFU)',
                    ];
                    $scopedUnitLabel = $unitScopeLabels[$defaultUnitFilter] ?? ($viewerUnit?->value ?? 'Assigned Unit');
                @endphp
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <button type="button" onclick="applyFieldOfficerQuickFilter('{{ $defaultUnitFilter }}', 'all')" class="glass-panel-white rounded-2xl p-4 flex items-center justify-between border-l-4 border-l-slate-900 text-left hover:shadow-md transition-shadow cursor-pointer bg-white">
                        <div>
                            <div class="text-[10px] font-bold font-mono-code text-slate-500 uppercase tracking-wider">Total Field Officers</div>
                            <div class="text-2xl font-black text-slate-900 font-mono-code mt-1" id="kpiTotalStaff">{{ number_format($totalStaffCount ?? 0) }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">{{ $isUnitScoped ? ($viewerUnit?->value ?? 'Assigned Unit') : 'All Staff-Related Units' }}</div>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-800">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                    </button>

                    <button type="button" onclick="applyFieldOfficerQuickFilter('{{ $defaultUnitFilter }}', 'approved')" class="glass-panel-white rounded-2xl p-4 flex items-center justify-between border-l-4 border-l-emerald-600 text-left hover:shadow-md transition-shadow cursor-pointer bg-white">
                        <div>
                            <div class="text-[10px] font-bold font-mono-code text-emerald-700 uppercase tracking-wider">Approved Staff</div>
                            <div class="text-2xl font-black text-emerald-900 font-mono-code mt-1" id="kpiApprovedStaff">{{ number_format($approvedStaffCount ?? 0) }}</div>
                            <div class="text-[11px] text-emerald-600 mt-0.5">Cleared for System Login</div>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </button>

                    <button type="button" onclick="applyFieldOfficerQuickFilter('{{ $defaultUnitFilter }}', 'pending')" class="glass-panel-white rounded-2xl p-4 flex items-center justify-between border-l-4 border-l-amber-500 text-left hover:shadow-md transition-shadow cursor-pointer bg-white">
                        <div>
                            <div class="text-[10px] font-bold font-mono-code text-amber-700 uppercase tracking-wider">Pending Unit Approval</div>
                            <div class="text-2xl font-black text-amber-900 font-mono-code mt-1" id="kpiPendingStaff">{{ number_format($pendingStaffCount ?? 0) }}</div>
                            <div class="text-[11px] text-amber-600 mt-0.5">Requires Head of Unit Clearance</div>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </button>
                </div>

                <!-- 2. UNIT QUICK TABS & FILTER BAR -->
                <div class="glass-panel-white rounded-2xl p-4 space-y-4">

                    <div class="flex items-center justify-between flex-wrap gap-3 border-b border-slate-200 pb-3">
                        <div class="flex items-center gap-1.5 flex-wrap" id="fieldOfficerUnitTabsGroup">
                            @if($isUnitScoped)
                                <button type="button" data-unit-tab="{{ $defaultUnitFilter }}" onclick="switchFieldOfficerUnitTab('{{ $defaultUnitFilter }}')" class="filter-tab-btn active-tab">{{ $viewerUnit?->value ?? 'Assigned Unit' }}</button>
                            @else
                                <button type="button" data-unit-tab="all" onclick="switchFieldOfficerUnitTab('all')" class="filter-tab-btn {{ ($unitFilter ?? 'all') === 'all' ? 'active-tab' : '' }}">All Units</button>
                                <button type="button" data-unit-tab="css" onclick="switchFieldOfficerUnitTab('css')" class="filter-tab-btn {{ ($unitFilter ?? '') === 'css' ? 'active-tab' : '' }}">Crime Scene Support</button>
                                <button type="button" data-unit-tab="em" onclick="switchFieldOfficerUnitTab('em')" class="filter-tab-btn {{ ($unitFilter ?? '') === 'em' ? 'active-tab' : '' }}">Evidence Management</button>
                                <button type="button" data-unit-tab="lab" onclick="switchFieldOfficerUnitTab('lab')" class="filter-tab-btn {{ ($unitFilter ?? '') === 'lab' ? 'active-tab' : '' }}">Lab Analysis</button>
                                <button type="button" data-unit-tab="dpf" onclick="switchFieldOfficerUnitTab('dpf')" class="filter-tab-btn {{ ($unitFilter ?? '') === 'dpf' ? 'active-tab' : '' }}">Digital &amp; Physical</button>
                            @endif
                        </div>

                        <div class="text-xs text-slate-500 font-mono-code" id="fieldOfficersResultsCounter">
                            Showing <span class="font-bold text-slate-900">{{ $officers->firstItem() ?? 0 }}</span> - <span class="font-bold text-slate-900">{{ $officers->lastItem() ?? 0 }}</span> of <span class="font-bold text-slate-900">{{ $officers->total() }}</span> officers
                        </div>
                    </div>

                    <form action="{{ url($currentEndpoint ?? '/field-officers') }}" method="GET" id="fieldOfficersFilterForm" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <input type="hidden" name="search" id="fieldOfficersHiddenSearch" value="{{ $search ?? '' }}">

                        <div>
                            <label for="fieldUnitSelectFilter" class="block text-[10px] font-bold font-mono-code uppercase text-slate-500 mb-1">Filter by Staff Unit</label>
                            <select name="unit" id="fieldUnitSelectFilter" class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:border-emerald-600">
                                @if($isUnitScoped)
                                    <option value="{{ $defaultUnitFilter }}" selected>{{ $scopedUnitLabel }}</option>
                                @else
                                    <option value="all" {{ ($unitFilter ?? 'all') === 'all' ? 'selected' : '' }}>All Staff-Related Units</option>
                                    <option value="css" {{ ($unitFilter ?? '') === 'css' ? 'selected' : '' }}>Crime Scene Support (CSSU)</option>
                                    <option value="em" {{ ($unitFilter ?? '') === 'em' ? 'selected' : '' }}>Evidence Management (EMU)</option>
                                    <option value="lab" {{ ($unitFilter ?? '') === 'lab' ? 'selected' : '' }}>Lab Analysis (LAU)</option>
                                    <option value="dpf" {{ ($unitFilter ?? '') === 'dpf' ? 'selected' : '' }}>Digital &amp; Physical Forensics (DPFU)</option>
                                @endif
                            </select>
                        </div>

                        <div>
                            <label for="fieldStatusSelectFilter" class="block text-[10px] font-bold font-mono-code uppercase text-slate-500 mb-1">Approval Status</label>
                            <select name="status" id="fieldStatusSelectFilter" class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:border-emerald-600">
                                <option value="all" {{ ($statusFilter ?? 'all') === 'all' ? 'selected' : '' }}>All Approval Statuses</option>
                                <option value="pending" {{ ($statusFilter ?? '') === 'pending' ? 'selected' : '' }}>Pending Approval</option>
                                <option value="approved" {{ ($statusFilter ?? '') === 'approved' ? 'selected' : '' }}>Approved / Active</option>
                            </select>
                        </div>

                        <div class="flex items-end gap-2">
                            <div class="flex-1">
                                <label for="fieldPerPageSelectFilter" class="block text-[10px] font-bold font-mono-code uppercase text-slate-500 mb-1">Rows</label>
                                <select name="per_page" id="fieldPerPageSelectFilter" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:border-emerald-600">
                                    <option value="15" {{ ($perPage ?? 15) == 15 ? 'selected' : '' }}>15</option>
                                    <option value="25" {{ ($perPage ?? 15) == 25 ? 'selected' : '' }}>25</option>
                                    <option value="50" {{ ($perPage ?? 15) == 50 ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ ($perPage ?? 15) == 100 ? 'selected' : '' }}>100</option>
                                </select>
                            </div>
                            <button type="button" onclick="resetFieldOfficerFilters()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold font-mono-code transition-colors shrink-0 border-none cursor-pointer">
                                Reset
                            </button>
                        </div>
                    </form>
                </div>

                <!-- 3. FIELD OFFICERS TABLE WITH SKELETON LOADER -->
                <div class="glass-panel-white rounded-2xl overflow-hidden relative min-h-[360px]" id="fieldOfficersTableContainer">
                    @include('partials.field_officers_table')
                </div>

            </main>
        </div>
    </div>

    <!-- INLINE ACTION TOAST -->
    <div id="fieldOfficersToast" class="fixed bottom-5 right-5 z-50 hidden transition-all duration-300 translate-y-2">
        <div class="flex items-center space-x-3 px-4 py-3 bg-slate-900 text-white rounded-xl shadow-xl border border-slate-700">
            <span class="w-2 h-2 rounded-full bg-emerald-400" id="fieldOfficersToastDot"></span>
            <span class="text-xs font-semibold" id="fieldOfficersToastMsg">Status updated.</span>
        </div>
    </div>

    <script>
        const FIELD_OFFICERS_ENDPOINT = '{{ url($currentEndpoint ?? '/field-officers') }}';
        const DEFAULT_UNIT_FILTER = @json($defaultUnitFilter ?? 'all');

        function renderFieldOfficersSkeletonRows(colCount = 6, rowCount = 6) {
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
                    <td class="py-4 px-5">
                        <div class="h-3 bg-slate-200 rounded w-28"></div>
                        <div class="h-2.5 bg-slate-100 rounded w-36 mt-1.5"></div>
                    </td>
                    <td class="py-4 px-5 text-center whitespace-nowrap">
                        <div class="h-5 bg-slate-200 rounded-full w-24 mx-auto"></div>
                    </td>
                    <td class="py-4 px-5 text-center whitespace-nowrap">
                        <div class="h-7 bg-slate-200 rounded-xl w-20 mx-auto"></div>
                    </td>
                </tr>`;
            }

            return html;
        }

        function syncFieldOfficersCountersFromDom() {
            const meta = document.getElementById('fieldOfficersTableMeta');
            if (!meta) return;

            const first = meta.getAttribute('data-first-item') || '0';
            const last = meta.getAttribute('data-last-item') || '0';
            const total = meta.getAttribute('data-total') || '0';

            const counterEl = document.getElementById('fieldOfficersResultsCounter');
            if (counterEl) {
                counterEl.innerHTML = `Showing <span class="font-bold text-slate-900">${first}</span> - <span class="font-bold text-slate-900">${last}</span> of <span class="font-bold text-slate-900">${Number(total).toLocaleString()}</span> officers`;
            }

            const totalAll = meta.getAttribute('data-total-all');
            const approvedCount = meta.getAttribute('data-approved-count');
            const pendingCount = meta.getAttribute('data-pending-count');

            if (totalAll !== null) {
                const el = document.getElementById('kpiTotalStaff');
                if (el) el.textContent = Number(totalAll).toLocaleString();
            }
            if (approvedCount !== null) {
                const el = document.getElementById('kpiApprovedStaff');
                if (el) el.textContent = Number(approvedCount).toLocaleString();
            }
            if (pendingCount !== null) {
                const el = document.getElementById('kpiPendingStaff');
                if (el) el.textContent = Number(pendingCount).toLocaleString();
            }
        }

        function syncFieldOfficerUnitTabState(unitSlug) {
            const buttons = document.querySelectorAll('#fieldOfficerUnitTabsGroup .filter-tab-btn');
            const target = unitSlug || DEFAULT_UNIT_FILTER;
            buttons.forEach((btn) => {
                if (btn.getAttribute('data-unit-tab') === target) {
                    btn.classList.add('active-tab');
                } else {
                    btn.classList.remove('active-tab');
                }
            });
        }

        function setupFieldOfficersAjaxTable() {
            if (typeof initAjaxTable === 'function' && !window.fieldOfficersAjaxTable) {
                window.fieldOfficersAjaxTable = initAjaxTable({
                    container: '#fieldOfficersTableContainer',
                    table: '#fieldOfficersTable',
                    form: '#fieldOfficersFilterForm',
                    endpoint: FIELD_OFFICERS_ENDPOINT,
                    debounce: 300,
                    columns: 6,
                    rows: 6,
                    skeletonRenderer: renderFieldOfficersSkeletonRows,
                    onAfterUpdate: () => {
                        syncFieldOfficersCountersFromDom();
                        const unitEl = document.getElementById('fieldUnitSelectFilter');
                        if (unitEl) syncFieldOfficerUnitTabState(unitEl.value);
                    }
                });
            }

            const searchInput = document.getElementById('fieldOfficersSearchInput');
            if (searchInput && !searchInput.dataset.boundSync) {
                searchInput.dataset.boundSync = '1';
                searchInput.addEventListener('input', function () {
                    const hidden = document.getElementById('fieldOfficersHiddenSearch');
                    if (hidden) hidden.value = searchInput.value.trim();
                });
            }

            const unitSelect = document.getElementById('fieldUnitSelectFilter');
            if (unitSelect && !unitSelect.dataset.boundSync) {
                unitSelect.dataset.boundSync = '1';
                unitSelect.addEventListener('change', function () {
                    syncFieldOfficerUnitTabState(unitSelect.value);
                });
            }
        }

        function switchFieldOfficerUnitTab(unitSlug) {
            const target = unitSlug || DEFAULT_UNIT_FILTER;
            const unitSelect = document.getElementById('fieldUnitSelectFilter');
            if (unitSelect) unitSelect.value = target;
            syncFieldOfficerUnitTabState(target);

            if (window.fieldOfficersAjaxTable) {
                window.fieldOfficersAjaxTable.applyFilters({ unit: target });
            }
        }

        function applyFieldOfficerQuickFilter(unitSlug, statusSlug) {
            const targetUnit = unitSlug || DEFAULT_UNIT_FILTER;
            const unitSelect = document.getElementById('fieldUnitSelectFilter');
            const statusSelect = document.getElementById('fieldStatusSelectFilter');

            if (unitSelect) unitSelect.value = targetUnit;
            if (statusSelect) statusSelect.value = statusSlug;
            syncFieldOfficerUnitTabState(targetUnit);

            if (window.fieldOfficersAjaxTable) {
                window.fieldOfficersAjaxTable.applyFilters({
                    unit: targetUnit,
                    status: statusSlug
                });
            }
        }

        function resetFieldOfficerFilters() {
            const form = document.getElementById('fieldOfficersFilterForm');
            if (form) form.reset();

            const searchInput = document.getElementById('fieldOfficersSearchInput');
            if (searchInput) searchInput.value = '';
            const hiddenSearch = document.getElementById('fieldOfficersHiddenSearch');
            if (hiddenSearch) hiddenSearch.value = '';

            syncFieldOfficerUnitTabState(DEFAULT_UNIT_FILTER);

            if (window.fieldOfficersAjaxTable) {
                window.fieldOfficersAjaxTable.applyFilters({
                    search: '',
                    unit: DEFAULT_UNIT_FILTER,
                    status: 'all',
                    per_page: 15
                });
            } else {
                window.location.href = FIELD_OFFICERS_ENDPOINT;
            }
        }

        function showFieldOfficersToast(message, isError = false) {
            const toast = document.getElementById('fieldOfficersToast');
            const msgEl = document.getElementById('fieldOfficersToastMsg');
            const dotEl = document.getElementById('fieldOfficersToastDot');
            if (!toast || !msgEl) return;

            msgEl.textContent = message;
            if (dotEl) {
                dotEl.className = isError ? 'w-2 h-2 rounded-full bg-rose-400' : 'w-2 h-2 rounded-full bg-emerald-400';
            }

            toast.classList.remove('hidden', 'translate-y-2');
            clearTimeout(window.fieldOfficersToastTimer);
            window.fieldOfficersToastTimer = setTimeout(() => {
                toast.classList.add('translate-y-2');
                setTimeout(() => toast.classList.add('hidden'), 250);
            }, 3500);
        }

        async function handleStaffApproval(actionUrl, actionType, btnEl) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            if (btnEl) btnEl.disabled = true;

            try {
                const res = await fetch(actionUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                const data = await res.json();
                if (!res.ok || !data.success) {
                    showFieldOfficersToast(data.message || 'Authorization action failed.', true);
                    if (btnEl) btnEl.disabled = false;
                    return;
                }

                showFieldOfficersToast(data.message || (actionType === 'approve' ? 'Staff officer approved.' : 'Staff officer access revoked.'));
                await pollFieldOfficers(true);
            } catch (err) {
                showFieldOfficersToast('Network error while updating officer status.', true);
                if (btnEl) btnEl.disabled = false;
            }
        }

        let isPollingFieldOfficers = false;
        async function pollFieldOfficers(force = false) {
            if (isPollingFieldOfficers || (!force && document.hidden)) return;
            if (!force && window.fieldOfficersAjaxTable && window.fieldOfficersAjaxTable.isLoading) return;

            isPollingFieldOfficers = true;
            const controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
            const timeoutId = controller ? setTimeout(() => controller.abort(), 6000) : null;

            try {
                const params = window.fieldOfficersAjaxTable
                    ? window.fieldOfficersAjaxTable.collectParams()
                    : new URLSearchParams(window.location.search);
                params.set('poll', '1');

                const res = await fetch(FIELD_OFFICERS_ENDPOINT + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                    signal: controller ? controller.signal : undefined
                });
                if (timeoutId) clearTimeout(timeoutId);
                if (!res.ok) return;

                const data = await res.json();
                if (!force && window.fieldOfficersAjaxTable && window.fieldOfficersAjaxTable.isLoading) return;

                if (data.html) {
                    const container = document.getElementById('fieldOfficersTableContainer');
                    if (container) {
                        container.innerHTML = data.html;
                        syncFieldOfficersCountersFromDom();
                    }
                }
            } catch (e) {
                // Silent catch during background polling
            } finally {
                if (timeoutId) clearTimeout(timeoutId);
                isPollingFieldOfficers = false;
            }
        }

        setupFieldOfficersAjaxTable();
        document.addEventListener('DOMContentLoaded', setupFieldOfficersAjaxTable);
        setInterval(pollFieldOfficers, 10000);
    </script>
</body>
</html>

