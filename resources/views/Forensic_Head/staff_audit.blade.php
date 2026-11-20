@php
    $authUser = auth()->user();
    $unitKey = $unitContext['key'] ?? 'crime_scene_support';
    $unitLabel = $unitContext['label'] ?? 'Crime Scene Support & Documentation Unit';
    $sidebarUnit = $sidebarUnit ?? ($unitContext['sidebar'] ?? 'Crime Scene Support');
    $isDirectorate = $isDirectorate ?? false;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Staff Audit &amp; Activity Logs | {{ $unitLabel }} | NACOC Forensic</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    @endif

    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #F6F7F9;
            color: #0F172A;
            -webkit-font-smoothing: antialiased;
        }
        .font-mono-code {
            font-family: 'JetBrains Mono', ui-monospace, monospace;
        }
        .tnum {
            font-variant-numeric: tabular-nums;
        }
        a[href], button:not(:disabled), select:not(:disabled) {
            cursor: pointer;
        }
        .audit-card {
            background: #FFFFFF;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 4px 12px -4px rgba(15, 23, 42, 0.05);
            transition: box-shadow 0.18s ease, transform 0.18s ease;
        }
        .audit-card:hover {
            box-shadow: 0 10px 24px -8px rgba(15, 23, 42, 0.10);
            transform: translateY(-1px);
        }
        .audit-pagination nav {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .audit-pagination span, .audit-pagination a {
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 700;
            border-radius: 8px;
            border: 1px solid #E2E8F0;
            background: #FFFFFF;
            color: #334155;
            text-decoration: none;
        }
        .audit-pagination .active span {
            background: #059669;
            color: #FFFFFF;
            border-color: #059669;
        }
    </style>
    @include('partials.staff_audit_print_styles')
</head>
<body class="min-h-screen bg-[#F6F7F9] text-slate-900 antialiased selection:bg-emerald-700 selection:text-white">

    <div class="flex h-screen overflow-hidden">
        <!-- LEFT SIDEBAR -->
        @include('Forensic_Head.partials.sidebar')

        <!-- MAIN VIEWPORT -->
        <div class="flex-1 flex flex-col min-w-0 bg-[#F6F7F9] overflow-hidden">
            <!-- TOPBAR -->
            <header class="h-16 bg-white shadow-2xs px-4 sm:px-6 flex items-center justify-between gap-4 shrink-0 z-20 border-b border-slate-200/80">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center shrink-0 border border-indigo-200 shadow-2xs" aria-hidden="true">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h1 class="text-base font-extrabold text-slate-900 tracking-tight truncate">
                                Staff Activity &amp; Audit Logs
                            </h1>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono-code font-bold uppercase tracking-wide border {{ $isDirectorate ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' }}">
                                {{ $isDirectorate ? 'DIRECTORATE COMMAND' : 'UNIT HEAD OVERSIGHT' }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 font-medium truncate">
                            {{ $unitLabel }} &bull; Immutable audit trail of operational staff interactions across system pages
                        </p>
                    </div>
                </div>

                <!-- TOPBAR ACTION BUTTONS -->
                <div class="flex items-center gap-2 shrink-0">
                    <!-- POLLING STATUS & CONTROLS -->
                    <div class="flex items-center space-x-1.5">
                        <div id="auditPollingPill" class="hidden md:flex items-center space-x-1.5 px-2.5 py-1 bg-emerald-50 text-emerald-800 rounded-lg border border-emerald-200 text-[11px] font-bold">
                            <span id="auditPollingDot" class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span id="auditPollingLabel" class="font-mono-code uppercase text-[10px]">Live (10s)</span>
                        </div>
                        <button type="button" id="auditPollingToggleBtn" onclick="toggleAuditPolling()" class="p-1.5 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg shadow-2xs transition-colors cursor-pointer" title="Pause Live Polling">
                            <svg class="w-3.5 h-3.5 text-slate-500 hover:text-slate-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </button>
                        <span id="auditSyncStatus" class="hidden lg:inline text-[10px] text-slate-500 font-mono-code font-bold pl-1">Synced</span>
                    </div>

                    <button type="button" onclick="refreshAuditTable({ page: 1, showSkeleton: true })" class="inline-flex items-center px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl shadow-2xs transition-colors cursor-pointer" title="Refresh Table">
                        <svg id="refreshIcon" class="w-3.5 h-3.5 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Refresh
                    </button>
                </div>
            </header>

            <!-- SCROLLABLE CONTENT BODY -->
            <main class="flex-1 overflow-y-auto px-4 sm:px-6 py-6 space-y-6">
                <!-- METRIC SUMMARY CARDS -->
                <div id="auditSummaryMetrics" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- TOTAL LOGS -->
                    <div class="audit-card p-4 rounded-2xl border border-slate-200/90 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Total Logged Activities</span>
                            <span id="statTotalLogs" class="text-2xl font-black text-slate-900 tnum">{{ number_format($stats['total'] ?? 0) }}</span>
                            <span class="text-[11px] text-slate-500 block mt-0.5">Recorded across all unit pages</span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center border border-emerald-100 shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                        </div>
                    </div>

                    <!-- ACTIVE STAFF MEMBERS -->
                    <div class="audit-card p-4 rounded-2xl border border-slate-200/90 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Active Staff Officers</span>
                            <span id="statUniqueStaff" class="text-2xl font-black text-slate-900 tnum">{{ number_format($stats['unique_staff'] ?? 0) }}</span>
                            <span class="text-[11px] text-slate-500 block mt-0.5">Distinct verified personnel</span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center border border-indigo-100 shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- MUTATIONS / OPERATIONS -->
                    <div class="audit-card p-4 rounded-2xl border border-slate-200/90 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">State Mutations / Submissions</span>
                            <span id="statMutations" class="text-2xl font-black text-amber-700 tnum">{{ number_format($stats['mutations'] ?? 0) }}</span>
                            <span class="text-[11px] text-slate-500 block mt-0.5">POST, PUT, DELETE operations</span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center border border-amber-100 shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- TODAY'S ACTIVITIES -->
                    <div class="audit-card p-4 rounded-2xl border border-slate-200/90 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Today's Activities</span>
                            <span id="statToday" class="text-2xl font-black text-blue-700 tnum">{{ number_format($stats['today'] ?? 0) }}</span>
                            <span class="text-[11px] text-slate-500 block mt-0.5">Events recorded since 00:00</span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center border border-blue-100 shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- FILTER CONTROLS BAR -->
                <div id="auditFiltersBar" class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-2xs space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                        <!-- SEARCH INPUT -->
                        <div class="md:col-span-4 relative flex items-center">
                            <span class="absolute left-3.5 text-slate-400 pointer-events-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </span>
                            <input type="text"
                                   id="auditSearchInput"
                                   value="{{ $search ?? '' }}"
                                   placeholder="Search officer, badge ID, action, route, or IP..."
                                   class="w-full pl-9 pr-3 py-2 bg-slate-50 hover:bg-white focus:bg-white border border-slate-300 rounded-xl font-medium text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all placeholder:text-slate-400">
                        </div>

                        <!-- ACTION FILTER -->
                        <div class="md:col-span-2">
                            <select id="auditActionFilter"
                                    class="w-full px-3 py-2 bg-slate-50 hover:bg-white focus:bg-white border border-slate-300 rounded-xl font-bold text-slate-800 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer">
                                <option value="ALL" {{ ($actionFilter ?? 'ALL') === 'ALL' ? 'selected' : '' }}>All Actions</option>
                                <option value="VIEW" {{ ($actionFilter ?? '') === 'VIEW' ? 'selected' : '' }}>VIEW (Page Access)</option>
                                <option value="CREATE" {{ ($actionFilter ?? '') === 'CREATE' ? 'selected' : '' }}>CREATE / LODGE</option>
                                <option value="UPDATE" {{ ($actionFilter ?? '') === 'UPDATE' ? 'selected' : '' }}>UPDATE / EDIT</option>
                                <option value="DELETE" {{ ($actionFilter ?? '') === 'DELETE' ? 'selected' : '' }}>DELETE</option>
                                <option value="APPROVE" {{ ($actionFilter ?? '') === 'APPROVE' ? 'selected' : '' }}>APPROVE</option>
                                <option value="DECLINE" {{ ($actionFilter ?? '') === 'DECLINE' ? 'selected' : '' }}>DECLINE</option>
                                <option value="ROLLBACK" {{ ($actionFilter ?? '') === 'ROLLBACK' ? 'selected' : '' }}>ROLLBACK</option>
                            </select>
                        </div>

                        <!-- DATE RANGE FILTER -->
                        <div class="md:col-span-2">
                            <select id="auditRangeFilter"
                                    class="w-full px-3 py-2 bg-slate-50 hover:bg-white focus:bg-white border border-slate-300 rounded-xl font-bold text-slate-800 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer">
                                <option value="all" {{ ($dateRange ?? 'all') === 'all' ? 'selected' : '' }}>All Time</option>
                                <option value="today" {{ ($dateRange ?? '') === 'today' ? 'selected' : '' }}>Today Only</option>
                                <option value="7_days" {{ ($dateRange ?? '') === '7_days' ? 'selected' : '' }}>Last 7 Days</option>
                                <option value="30_days" {{ ($dateRange ?? '') === '30_days' ? 'selected' : '' }}>Last 30 Days</option>
                                <option value="90_days" {{ ($dateRange ?? '') === '90_days' ? 'selected' : '' }}>Last 90 Days</option>
                            </select>
                        </div>

                        <!-- UNIT SELECTOR (DIRECTORATE) OR UNIT BADGE (HEAD) -->
                        <div class="md:col-span-2">
                            @if($isDirectorate)
                                <select id="auditUnitFilter"
                                        class="w-full px-3 py-2 bg-purple-50/60 hover:bg-white focus:bg-white border border-purple-200 rounded-xl font-bold text-purple-900 text-xs focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-600/20 transition-all cursor-pointer">
                                    <option value="ALL" {{ empty($effectiveUnit) ? 'selected' : '' }}>All Units (Directorate)</option>
                                    <option value="Crime Scene Support" {{ ($effectiveUnit ?? '') === 'Crime Scene Support' ? 'selected' : '' }}>Crime Scene Support</option>
                                    <option value="Lab Analysis" {{ ($effectiveUnit ?? '') === 'Lab Analysis' ? 'selected' : '' }}>Lab Analysis</option>
                                    <option value="Evidence Management" {{ ($effectiveUnit ?? '') === 'Evidence Management' ? 'selected' : '' }}>Evidence Management</option>
                                    <option value="Digital Forensics" {{ ($effectiveUnit ?? '') === 'Digital Forensics' ? 'selected' : '' }}>Digital Forensics</option>
                                </select>
                            @else
                                <div class="px-3 py-2 bg-slate-100 border border-slate-200 rounded-xl font-bold text-slate-700 text-xs flex items-center justify-between" title="Scope locked to your active unit">
                                    <span class="truncate">{{ $sidebarUnit }}</span>
                                    <span class="text-[9px] font-mono-code font-bold text-slate-500 uppercase">Scoped</span>
                                </div>
                            @endif
                        </div>

                        <!-- PER PAGE & RESET -->
                        <div class="md:col-span-2 flex items-center justify-end space-x-2">
                            <select id="auditPerPage"
                                    class="px-2.5 py-2 bg-slate-50 hover:bg-white focus:bg-white border border-slate-300 rounded-xl font-bold text-slate-800 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 cursor-pointer">
                                <option value="15" {{ ($perPage ?? 20) == 15 ? 'selected' : '' }}>15 / page</option>
                                <option value="25" {{ ($perPage ?? 20) == 25 ? 'selected' : '' }}>25 / page</option>
                                <option value="50" {{ ($perPage ?? 20) == 50 ? 'selected' : '' }}>50 / page</option>
                                <option value="100" {{ ($perPage ?? 20) == 100 ? 'selected' : '' }}>100 / page</option>
                            </select>

                            <button type="button"
                                    onclick="resetAuditFilters()"
                                    class="p-2 text-slate-500 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 rounded-xl border border-slate-200 transition-colors"
                                    title="Reset All Filters">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- MAIN AUDIT LOGS TABLE CARD -->
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden" id="staffAuditTableContainer">
                    @include('partials.staff_audit_table')
                </div>
            </main>
        </div>
    </div>

    <!-- AUDIT INSPECT DETAILS MODAL / SLIDE-OVER -->
    <div id="auditInspectModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <!-- MODAL HEADER -->
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900">Audit Record Inspection</h2>
                        <p class="text-[11px] text-slate-500 font-medium" id="modalTimestampHeader">—</p>
                    </div>
                </div>
                <button type="button" onclick="closeAuditModal()" class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 rounded-xl transition-colors cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- MODAL BODY -->
            <div class="p-6 overflow-y-auto space-y-5 text-xs font-medium text-slate-700">
                <!-- OFFICER & CONTEXT DETAILS -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-4 bg-slate-50 rounded-2xl border border-slate-200/80">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Officer Name</span>
                        <span class="text-xs font-black text-slate-900 block truncate" id="modalStaffName">—</span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Service ID</span>
                        <span class="text-xs font-mono-code font-bold text-slate-800 block truncate" id="modalServiceId">—</span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Unit &amp; Role</span>
                        <span class="text-xs font-bold text-slate-800 block truncate" id="modalUnitRole">—</span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Action &amp; Method</span>
                        <span class="text-xs font-bold text-slate-900 block truncate" id="modalActionMethod">—</span>
                    </div>
                </div>

                <!-- DESCRIPTION -->
                <div>
                    <label class="block text-[11px] font-extrabold text-slate-900 uppercase tracking-wider mb-1">Activity Context</label>
                    <div class="p-3 bg-white rounded-xl border border-slate-200 text-xs text-slate-800 leading-relaxed font-medium" id="modalDescription">—</div>
                </div>

                <!-- REQUEST METRICS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-extrabold text-slate-900 uppercase tracking-wider mb-1">Route Path</label>
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200 font-mono-code text-[11px] text-slate-800 truncate" id="modalRoute">—</div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-extrabold text-slate-900 uppercase tracking-wider mb-1">Client IP Address</label>
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200 font-mono-code text-[11px] text-slate-800" id="modalIpAddress">—</div>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-extrabold text-slate-900 uppercase tracking-wider mb-1">Target URL</label>
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200 font-mono-code text-[11px] text-slate-800 break-all" id="modalUrl">—</div>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-extrabold text-slate-900 uppercase tracking-wider mb-1">User Agent</label>
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200 text-[11px] text-slate-600 break-all leading-relaxed" id="modalUserAgent">—</div>
                    </div>
                </div>

                <!-- PAYLOAD VIEWER -->
                <div id="modalPayloadContainer" class="hidden">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-[11px] font-extrabold text-slate-900 uppercase tracking-wider">Sanitized Request Payload</label>
                        <button type="button" onclick="copyPayloadJson()" class="text-[10px] font-bold text-emerald-700 hover:text-emerald-900 font-mono-code cursor-pointer">
                            Copy JSON
                        </button>
                    </div>
                    <pre id="modalPayloadJson" class="p-3 bg-slate-900 text-emerald-400 rounded-xl font-mono-code text-[11px] max-h-48 overflow-y-auto leading-relaxed border border-slate-800"></pre>
                </div>
            </div>

            <!-- MODAL FOOTER -->
            <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex justify-end shrink-0">
                <button type="button" onclick="closeAuditModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl shadow-xs transition-colors cursor-pointer">
                    Close Details
                </button>
            </div>
        </div>
    </div>

    <!-- CLIENT SCRIPTS (PAGINATION, POLLING, SKELETON LOADER & MODALS) -->
    @include('partials.staff_audit_scripts')
</body>
</html>
