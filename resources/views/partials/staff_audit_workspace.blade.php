@php
    $authUser = auth()->user();
    $unitKey = $unitContext['key'] ?? 'crime_scene_support';
    $unitLabel = $unitContext['label'] ?? 'Crime Scene Support & Documentation Unit';
    $sidebarUnit = $sidebarUnit ?? ($unitContext['sidebar'] ?? 'Crime Scene Support');
    $isDirectorate = $isDirectorate ?? false;

    $useForensicHeadSidebar = $useForensicHeadSidebar ?? (
        request()->is('Forensic_Head*') ||
        request()->is('forensic-head*') ||
        ($isDirectorate && ($authUser?->is_head && str_contains(strtolower($authUser->unit ?? ''), 'forensic')))
    );

    $officerFullname = $authUser ? ($authUser->fullname ?? $authUser->name ?? 'Unit Head') : 'Unit Head';
    $officerParts = preg_split('/\s+/', trim((string) $officerFullname)) ?: [];
    $officerInitials = count($officerParts) >= 2
        ? strtoupper(substr($officerParts[0], 0, 1) . substr(end($officerParts), 0, 1))
        : strtoupper(substr((string) $officerFullname, 0, 2));

    $activeActionNorm = strtoupper((string) ($actionFilter ?? 'ALL'));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Staff Activity &amp; Audit Logs | {{ $unitLabel }} | NACOC Forensic</title>

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
    @include('partials.staff_audit_print_styles')

    <style>
        .audit-modal-header {
            background: linear-gradient(180deg, #f1f5f9 0%, #e6ecf3 100%);
            border-bottom: 1px solid #cbd5e1;
            color: #0f172a;
        }
        .audit-kpi-active {
            background: #ecfdf5 !important;
            box-shadow: inset 0 -2px 0 #059669;
        }
    </style>
</head>
<body class="min-h-screen flex antialiased selection:bg-emerald-600 selection:text-white">

    <div class="flex h-screen w-full overflow-hidden">

        <!-- LEFT SIDEBAR -->
        @if($useForensicHeadSidebar)
            @include('Forensic_Head.partials.sidebar')
        @else
            @include('Crime_Scene_Support.partials.sidebar')
        @endif

        <!-- MAIN WORKSPACE -->
        <div class="flex-1 flex flex-col min-w-0 bg-[#f3f5f8] overflow-hidden">

            <!-- STICKY COMMAND HEADER -->
            <header class="h-16 bg-white/95 backdrop-blur-md border-b border-slate-200/90 px-5 sm:px-6 flex items-center justify-between gap-4 shrink-0 z-20">
                <div class="flex items-center gap-3.5 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-emerald-700 border border-slate-200/90 flex items-center justify-center shrink-0 shadow-2xs">
                        <i data-lucide="shield-check" class="w-4 h-4"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-[10px] font-extrabold font-mono-code uppercase tracking-widest text-emerald-700">
                                {{ $sidebarUnit }}
                            </span>
                        </div>
                        <h1 class="text-sm font-extrabold text-slate-900 tracking-tight truncate">
                            Staff Activity &amp; Audit Logs
                        </h1>
                    </div>
                </div>

                <!-- Right Officer Identity -->
                <div class="flex items-center gap-2.5 shrink-0">
                    <div class="text-right hidden md:block">
                        <div class="text-xs font-bold text-slate-900 leading-tight truncate max-w-[160px]">{{ $officerFullname }}</div>
                        <div class="text-[10px] font-mono-code font-semibold text-slate-500">{{ $isDirectorate ? 'Directorate Oversight' : 'Unit Head Oversight' }}</div>
                    </div>
                    <div class="w-8 h-8 rounded-xl bg-slate-100 border border-slate-200/90 text-slate-800 flex items-center justify-center font-mono-code font-extrabold text-[11px]">
                        {{ $officerInitials ?: 'UH' }}
                    </div>
                </div>
            </header>

            <!-- SCROLLABLE WORKSPACE -->
            <main class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-5 custom-scrollbar">

                <!-- 1. EXECUTIVE GREY COMMAND MASTHEAD + 4-COLUMN TELEMETRY MATRIX -->
                <section id="auditSummaryMetrics" class="rep-masthead no-print" aria-label="Audit Telemetry Summary">
                    <div class="rep-masthead-hero px-6 py-5 sm:px-7 sm:py-6">
                        <div class="rep-masthead-inner flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <div class="space-y-1.5 max-w-3xl">
                                <h2 class="text-lg sm:text-xl font-extrabold tracking-tight text-slate-900">
                                    Staff Activity &amp; Audit Logs
                                </h2>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    Immutable chronological ledger of staff page access, case filings, exhibit updates, approvals, and state mutations across {{ $isDirectorate ? 'all operational forensic units' : $unitLabel }}.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 4-COLUMN PRECISION KPI STRIP -->
                    <div class="rep-kpi-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-slate-200/80">

                        <!-- Tile 1: Total Logged Activities -->
                        <button type="button"
                                onclick="resetAuditFilters()"
                                class="rep-kpi-tile p-4 sm:px-6 text-left group cursor-pointer">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-mono-code font-extrabold uppercase tracking-wider text-slate-500 group-hover:text-emerald-700">Total Logged Activities</span>
                                <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200/80 flex items-center justify-center shrink-0">
                                    <i data-lucide="clipboard-check" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                            <div id="statTotalLogs" class="text-2xl font-black text-slate-900 font-mono-code tnum mt-2">{{ number_format($stats['total'] ?? 0) }}</div>
                            <div class="text-[10.5px] text-emerald-700 font-semibold mt-1">Recorded across unit pages</div>
                        </button>

                        <!-- Tile 2: Active Staff Officers -->
                        <div class="rep-kpi-tile p-4 sm:px-6">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-mono-code font-extrabold uppercase tracking-wider text-indigo-700">Active Staff Officers</span>
                                <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200/80 flex items-center justify-center shrink-0">
                                    <i data-lucide="users" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                            <div id="statUniqueStaff" class="text-2xl font-black text-slate-900 font-mono-code tnum mt-2">{{ number_format($stats['unique_staff'] ?? 0) }}</div>
                            <div class="text-[10.5px] text-slate-500 font-semibold mt-1">Distinct verified personnel</div>
                        </div>

                        <!-- Tile 3: State Mutations / Submissions -->
                        <div class="rep-kpi-tile p-4 sm:px-6">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-mono-code font-extrabold uppercase tracking-wider text-amber-700">State Mutations</span>
                                <span class="w-7 h-7 rounded-lg bg-amber-50 text-amber-700 border border-amber-200/80 flex items-center justify-center shrink-0">
                                    <i data-lucide="file-pen-line" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                            <div id="statMutations" class="text-2xl font-black text-amber-700 font-mono-code tnum mt-2">{{ number_format($stats['mutations'] ?? 0) }}</div>
                            <div class="text-[10.5px] text-slate-500 font-semibold mt-1">POST &amp; PUT operations</div>
                        </div>

                        <!-- Tile 4: Today's Activities -->
                        <button type="button"
                                onclick="selectAuditQuickRange('today')"
                                class="rep-kpi-tile p-4 sm:px-6 text-left group cursor-pointer">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-mono-code font-extrabold uppercase tracking-wider text-teal-700">Today's Activities</span>
                                <span class="w-7 h-7 rounded-lg bg-teal-50 text-teal-700 border border-teal-200/80 flex items-center justify-center shrink-0">
                                    <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                            <div id="statToday" class="text-2xl font-black text-slate-900 font-mono-code tnum mt-2">{{ number_format($stats['today'] ?? 0) }}</div>
                            <div class="text-[10.5px] text-emerald-700 font-semibold mt-1 group-hover:underline">Events since 00:00 &rarr;</div>
                        </button>

                    </div>
                </section>

                <!-- 2. PRECISION FILTER & ACTION WORKBENCH -->
                <section id="auditFiltersBar" class="rep-surface-card overflow-hidden no-print" aria-label="Audit Filter Console">
                    <input type="hidden" id="auditActionFilter" value="{{ $activeActionNorm }}">
                    <div class="p-4 sm:p-5">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">

                            <!-- Search Input -->
                            <div class="md:col-span-7 relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 pointer-events-none">
                                    <i data-lucide="search" class="w-4 h-4"></i>
                                </span>
                                <input type="text"
                                       id="auditSearchInput"
                                       value="{{ $search ?? '' }}"
                                       placeholder="Search officer name, service ID, description, route path, or IP..."
                                       autocomplete="off"
                                       class="rep-input w-full pl-10 pr-9 py-2.5 text-xs font-mono-code font-semibold">
                                <button type="button"
                                        id="auditClearSearchBtn"
                                        onclick="clearAuditSearch()"
                                        aria-label="Clear search"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-700 {{ !empty($search) ? '' : 'hidden' }}">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                </button>
                            </div>

                            <!-- Date Range Filter -->
                            <div class="md:col-span-2">
                                <select id="auditRangeFilter"
                                        aria-label="Filter by date range"
                                        class="rep-select w-full px-3 py-2.5 text-xs font-mono-code font-semibold">
                                    <option value="all" {{ ($dateRange ?? 'all') === 'all' ? 'selected' : '' }}>Window: All Time</option>
                                    <option value="today" {{ ($dateRange ?? '') === 'today' ? 'selected' : '' }}>Today Only</option>
                                    <option value="7_days" {{ ($dateRange ?? '') === '7_days' ? 'selected' : '' }}>Last 7 Days</option>
                                    <option value="30_days" {{ ($dateRange ?? '') === '30_days' ? 'selected' : '' }}>Last 30 Days</option>
                                    <option value="90_days" {{ ($dateRange ?? '') === '90_days' ? 'selected' : '' }}>Last 90 Days</option>
                                </select>
                            </div>

                            <!-- Unit Selector (Directorate) or Scoped Unit Chip (Unit Head) -->
                            <div class="md:col-span-3">
                                @if($isDirectorate)
                                    <select id="auditUnitFilter"
                                            aria-label="Filter by operational unit"
                                            class="rep-select w-full px-3 py-2.5 text-xs font-mono-code font-semibold">
                                        <option value="ALL" {{ empty($effectiveUnit) ? 'selected' : '' }}>Unit: All Units (Directorate)</option>
                                        <option value="Crime Scene Support" {{ ($effectiveUnit ?? '') === 'Crime Scene Support' ? 'selected' : '' }}>Crime Scene Support</option>
                                        <option value="Lab Analysis" {{ ($effectiveUnit ?? '') === 'Lab Analysis' ? 'selected' : '' }}>Lab Analysis</option>
                                        <option value="Evidence Management" {{ ($effectiveUnit ?? '') === 'Evidence Management' ? 'selected' : '' }}>Evidence Management</option>
                                        <option value="Digital Forensics" {{ ($effectiveUnit ?? '') === 'Digital Forensics' ? 'selected' : '' }}>Digital Forensics</option>
                                    </select>
                                @else
                                    <div class="px-3.5 py-2.5 bg-slate-100/90 border border-slate-200/90 rounded-xl font-bold text-slate-800 text-xs flex items-center justify-between gap-2" title="Audit scope locked to your operational unit">
                                        <span class="truncate font-mono-code">{{ $sidebarUnit }}</span>
                                        <span class="text-[9.5px] font-mono-code font-extrabold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.2 rounded uppercase shrink-0">Unit Scope</span>
                                    </div>
                                @endif
                            </div>

                        </div>
                    </div>

                    <!-- Bottom Quick Action Pills & Controls Strip -->
                    <div class="px-4 py-3 sm:px-5 bg-slate-100/80 border-t border-slate-200/80 flex flex-col xl:flex-row xl:items-center justify-between gap-3">
                        <div class="flex items-center gap-1.5 overflow-x-auto pb-0.5 custom-scrollbar text-xs font-mono-code font-bold" id="auditActionPillsBar">
                            @foreach([
                                'ALL'     => 'All Events',
                                'VIEW'    => 'Page Views',
                                'CREATE'  => 'Created / Lodged',
                                'UPDATE'  => 'Updated',
                                'APPROVE' => 'Approved',
                                'DECLINE' => 'Declined',
                            ] as $actKey => $actLabel)
                                <button type="button"
                                        data-audit-action-pill="{{ $actKey }}"
                                        onclick="selectAuditQuickAction('{{ $actKey }}')"
                                        class="audit-action-pill px-3 py-1.5 rounded-xl transition-all shrink-0 cursor-pointer {{ $activeActionNorm === $actKey ? 'rep-btn-green' : 'rep-btn-green-soft' }}">
                                    {{ $actLabel }}
                                </button>
                            @endforeach
                        </div>

                        <div class="flex items-center gap-2 flex-wrap shrink-0">
                            <span id="auditSyncStatus" class="hidden sm:inline text-[10.5px] text-slate-500 font-mono-code font-semibold pr-1">Synced</span>

                            <select id="auditPerPage"
                                    aria-label="Records per page"
                                    class="rep-select px-2.5 py-1.5 text-[11px] font-mono-code font-bold">
                                <option value="15" {{ ($perPage ?? 20) == 15 ? 'selected' : '' }}>15 / page</option>
                                <option value="20" {{ ($perPage ?? 20) == 20 ? 'selected' : '' }}>20 / page</option>
                                <option value="50" {{ ($perPage ?? 20) == 50 ? 'selected' : '' }}>50 / page</option>
                                <option value="100" {{ ($perPage ?? 20) == 100 ? 'selected' : '' }}>100 / page</option>
                            </select>

                            <button type="button"
                                    onclick="refreshAuditTable({ page: currentAuditPage, showSkeleton: true })"
                                    class="rep-btn-green-soft px-3 py-1.5 rounded-xl text-xs font-bold inline-flex items-center gap-1.5 cursor-pointer"
                                    title="Refresh Audit Logs">
                                <svg id="refreshIcon" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                <span>Refresh</span>
                            </button>

                            <button type="button"
                                    onclick="resetAuditFilters()"
                                    class="rep-btn-green px-3.5 py-1.5 rounded-xl text-xs font-extrabold cursor-pointer"
                                    title="Reset all filters">
                                Reset
                            </button>
                        </div>
                    </div>
                </section>

                <!-- 3. MASTER AUDIT LOGS TABLE CARD -->
                <section class="rep-surface-card overflow-hidden" id="staffAuditTableContainer" aria-label="Staff Audit Ledger">
                    @include('partials.staff_audit_table')
                </section>

            </main>
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- 4. AUDIT RECORD INSPECTION MODAL                           -->
    <!-- ========================================================== -->
    <div id="auditInspectModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 no-print">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-300 w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden">

            <!-- Grey Executive Modal Header -->
            <div class="audit-modal-header px-6 py-4 flex items-center justify-between gap-4 shrink-0">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-white text-emerald-700 border border-slate-200/90 flex items-center justify-center shrink-0 shadow-2xs">
                        <i data-lucide="file-search-2" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Audit Record Inspection</h2>
                        <p class="text-xs text-slate-600 font-mono-code truncate" id="modalTimestampHeader">&mdash;</p>
                    </div>
                </div>
                <button type="button" onclick="closeAuditModal()" class="rep-btn-green-soft w-9 h-9 rounded-xl flex items-center justify-center cursor-pointer" title="Close">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-5 sm:p-6 overflow-y-auto space-y-4 text-xs font-medium text-slate-700 bg-[#f3f5f8] custom-scrollbar">

                <!-- Officer & Action Summary Strip -->
                <div class="rep-surface-card p-4 grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div>
                        <span class="text-[9.5px] uppercase font-extrabold text-slate-400 font-mono-code block tracking-wider">Officer Name</span>
                        <span class="text-xs font-extrabold text-slate-900 block truncate mt-0.5" id="modalStaffName">&mdash;</span>
                    </div>
                    <div>
                        <span class="text-[9.5px] uppercase font-extrabold text-slate-400 font-mono-code block tracking-wider">Service ID</span>
                        <span class="text-xs font-mono-code font-bold text-emerald-700 block truncate mt-0.5" id="modalServiceId">&mdash;</span>
                    </div>
                    <div>
                        <span class="text-[9.5px] uppercase font-extrabold text-slate-400 font-mono-code block tracking-wider">Unit &amp; Role</span>
                        <span class="text-xs font-bold text-slate-800 block truncate mt-0.5" id="modalUnitRole">&mdash;</span>
                    </div>
                    <div>
                        <span class="text-[9.5px] uppercase font-extrabold text-slate-400 font-mono-code block tracking-wider">Action &amp; HTTP</span>
                        <span class="text-xs font-mono-code font-extrabold text-slate-900 block truncate mt-0.5" id="modalActionMethod">&mdash;</span>
                    </div>
                </div>

                <!-- Activity Context -->
                <div class="rep-surface-card p-4 space-y-1.5">
                    <span class="block text-[10px] font-extrabold text-slate-500 font-mono-code uppercase tracking-wider">Activity Context</span>
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 text-xs text-slate-900 leading-relaxed font-semibold" id="modalDescription">&mdash;</div>
                </div>

                <!-- Request Telemetry Grid -->
                <div class="rep-surface-card p-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <span class="block text-[10px] font-extrabold text-slate-500 font-mono-code uppercase tracking-wider mb-1">Route Path</span>
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/80 font-mono-code text-[11px] text-emerald-800 font-semibold truncate" id="modalRoute">&mdash;</div>
                    </div>
                    <div>
                        <span class="block text-[10px] font-extrabold text-slate-500 font-mono-code uppercase tracking-wider mb-1">Client IP Address</span>
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/80 font-mono-code text-[11px] text-slate-800 font-semibold" id="modalIpAddress">&mdash;</div>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="block text-[10px] font-extrabold text-slate-500 font-mono-code uppercase tracking-wider mb-1">Target URL</span>
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/80 font-mono-code text-[11px] text-slate-800 break-all" id="modalUrl">&mdash;</div>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="block text-[10px] font-extrabold text-slate-500 font-mono-code uppercase tracking-wider mb-1">Browser / User Agent</span>
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200/80 text-[11px] text-slate-600 break-all leading-relaxed" id="modalUserAgent">&mdash;</div>
                    </div>
                </div>

                <!-- Sanitized Payload Viewer (Grey Code Surface) -->
                <div id="modalPayloadContainer" class="hidden rep-surface-card p-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="block text-[10px] font-extrabold text-slate-600 font-mono-code uppercase tracking-wider">Sanitized Request Payload</span>
                        <button type="button" id="copyPayloadBtn" onclick="copyPayloadJson()" class="rep-btn-green-soft px-2.5 py-1 rounded-lg text-[10.5px] font-bold font-mono-code cursor-pointer">
                            Copy JSON
                        </button>
                    </div>
                    <pre id="modalPayloadJson" class="p-3.5 bg-slate-100 text-slate-800 rounded-xl font-mono-code text-[11px] max-h-48 overflow-y-auto leading-relaxed border border-slate-300/90 custom-scrollbar"></pre>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-3.5 bg-white border-t border-slate-200 flex items-center justify-between shrink-0">
                <span class="text-[10.5px] text-slate-400 font-mono-code hidden sm:inline">Immutable Audit Log Entry</span>
                <button type="button" onclick="closeAuditModal()" class="rep-btn-green px-5 py-2 font-extrabold text-xs rounded-xl cursor-pointer ml-auto">
                    Close Details
                </button>
            </div>
        </div>
    </div>

    @include('partials.staff_audit_scripts')
</body>
</html>

