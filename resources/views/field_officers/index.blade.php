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

    $unitMeta = match ($defaultUnitFilter) {
        'css' => [
            'code'           => 'CSSU',
            'badge'          => 'CRIME SCENE SUPPORT UNIT',
            'contextTitle'   => 'Crime Scene Support',
            'overview'       => 'Crime Scene Support Staff Overview',
            'description'    => 'Manage field investigators, verify enrolled officer credentials, monitor session activity, and control system login clearance.',
            'homeUrl'        => url('/Crime_Scene_Support/head'),
            'permissionsUrl' => url('/Crime_Scene_Support/permissions'),
        ],
        'em' => [
            'code'           => 'EMU',
            'badge'          => 'EVIDENCE MANAGEMENT UNIT',
            'contextTitle'   => 'Evidence Management',
            'overview'       => 'Evidence Management Staff Overview',
            'description'    => 'Manage custody vault officers, verify enrolled staff credentials, monitor session activity, and control system login clearance.',
            'homeUrl'        => url('/Evidence_Management'),
            'permissionsUrl' => url('/Evidence_Management/permissions'),
        ],
        'lab' => [
            'code'           => 'LAU',
            'badge'          => 'LABORATORY ANALYSIS UNIT',
            'contextTitle'   => 'Laboratory Analysis',
            'overview'       => 'Laboratory Analysis Staff Overview',
            'description'    => 'Manage forensic chemists and analysts, verify enrolled staff credentials, monitor session activity, and control system login clearance.',
            'homeUrl'        => url('/Lab_Analysis'),
            'permissionsUrl' => url('/Lab_Analysis/permissions'),
        ],
        'dpf' => [
            'code'           => 'DPFU',
            'badge'          => 'DIGITAL & PHYSICAL FORENSICS UNIT',
            'contextTitle'   => 'Digital & Physical Forensics',
            'overview'       => 'Digital & Physical Forensics Staff Overview',
            'description'    => 'Manage digital forensic examiners, verify enrolled staff credentials, monitor session activity, and control system login clearance.',
            'homeUrl'        => url('/Digital_Forensics'),
            'permissionsUrl' => url('/Digital_Forensics/permissions'),
        ],
        default => [
            'code'           => 'FDept',
            'badge'          => 'FORENSIC DIRECTORATE COMMAND',
            'contextTitle'   => 'Forensic Directorate',
            'overview'       => 'Cross-Unit Field Officers & Staff Overview',
            'description'    => 'Centralized oversight of operational staff officers, enrollment verification, live session status, and unit-level login clearances.',
            'homeUrl'        => url('/Forensic_Head'),
            'permissionsUrl' => null,
        ],
    };

    $authUser = $viewer ?? auth()->user();
    $officerDisplayName = $authUser->fullname ?? session('user_name') ?? ($unitMeta['code'] . ' Command');
    $officerParts = preg_split('/\s+/', trim((string) $officerDisplayName)) ?: [];
    $officerInitials = count($officerParts) >= 2
        ? strtoupper(substr($officerParts[0], 0, 1) . substr(end($officerParts), 0, 1))
        : strtoupper(substr((string) $officerDisplayName, 0, 2));

    $hasInitialFilter = !empty($search)
        || (($statusFilter ?? 'all') !== 'all')
        || (!$isUnitScoped && ($unitFilter ?? 'all') !== 'all');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Unit Field Officers &amp; Staff Details | NACOC Forensic</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    @endif

    <script src="{{ asset('js/ajax-table.js') }}"></script>

    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #F4F6F8;
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
        button:disabled, select:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }
        a:focus-visible, button:not(:disabled):focus-visible, input:focus-visible, select:focus-visible {
            outline: 2px solid #059669;
            outline-offset: 2px;
        }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #EEF2F6; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94A3B8; }
    </style>
</head>
<body class="min-h-screen flex antialiased bg-[#F4F6F8] text-slate-900 selection:bg-emerald-700 selection:text-white">
    @include('partials.flash')

    <div class="flex min-h-screen lg:h-screen w-full overflow-hidden">

        <!-- UNIT COMMAND SIDEBAR -->
        @include('Crime_Scene_Support.partials.sidebar')

        <!-- MAIN WORKSPACE CONTENT -->
        <div class="flex-1 flex flex-col min-w-0 bg-[#F4F6F8] overflow-hidden">

            <!-- COMMAND TOPBAR -->
            <header class="h-16 bg-white border-b border-slate-200/80 px-4 sm:px-6 flex items-center justify-between gap-4 shrink-0 z-20">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 border border-slate-200/90 text-emerald-700 flex items-center justify-center shrink-0 shadow-2xs" aria-hidden="true">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <nav class="flex items-center gap-1.5 text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-emerald-700">
                            <a href="{{ $unitMeta['homeUrl'] }}" class="hover:underline truncate">{{ $unitMeta['contextTitle'] }}</a>
                            <span class="text-slate-300">/</span>
                            <span class="text-slate-500">Field Officers</span>
                        </nav>
                        <h1 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight truncate">
                            Unit Field Officers &amp; Staff Details
                        </h1>
                    </div>
                </div>

                <!-- DEBOUNCED COMMAND SEARCH -->
                <div class="hidden md:flex items-center flex-1 max-w-md mx-2">
                    <div class="relative w-full">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400" aria-hidden="true">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </span>
                        <input
                            type="text"
                            name="search"
                            id="fieldOfficersSearchInput"
                            value="{{ $search ?? '' }}"
                            placeholder="Search name, Service ID, username, phone, or email..."
                            aria-label="Search field officers"
                            data-table-filter="#fieldOfficersTableContainer"
                            class="w-full pl-10 pr-14 py-2 bg-slate-100/80 border border-slate-200/90 rounded-xl text-xs text-slate-900 font-mono-code placeholder-slate-400 focus:outline-none focus:bg-white focus:border-emerald-600 transition-colors"
                        >
                        <span class="absolute inset-y-0 right-0 flex items-center pr-2.5 pointer-events-none">
                            <kbd class="px-1.5 py-0.5 text-[9.5px] font-mono-code font-semibold text-slate-400 bg-white rounded border border-slate-200">Ctrl K</kbd>
                        </span>
                    </div>
                </div>

                <!-- TOPBAR UTILITIES & COMMANDER BADGE -->
                <div class="flex items-center gap-2.5 shrink-0">
                    <div class="hidden lg:flex items-center px-3 py-1.5 rounded-xl bg-slate-100 border border-slate-200/90 text-[11px] font-mono-code font-bold text-slate-700 tnum" id="liveClock">
                        {{ gmdate('Y-m-d H:i:s') }} UTC
                    </div>

                    @if(!empty($unitMeta['permissionsUrl']) && ($authUser?->is_head ?? false))
                        <a href="{{ $unitMeta['permissionsUrl'] }}"
                           class="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/90 text-xs font-bold shadow-2xs transition-colors">
                            <svg class="w-3.5 h-3.5 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <span>Unit Permissions</span>
                        </a>
                    @endif

                    <div class="hidden xl:flex items-center gap-2.5 pl-2.5 ml-1 border-l border-slate-200">
                        <div class="text-right">
                            <div class="text-xs font-extrabold text-slate-900 leading-tight truncate max-w-[140px]">{{ $officerDisplayName }}</div>
                            <div class="text-[10.5px] font-mono-code font-semibold text-emerald-700">
                                {{ $unitMeta['code'] . ($authUser?->is_head ? ' Head' : ' Staff') }}
                            </div>
                        </div>
                        <div class="w-8 h-8 rounded-xl bg-slate-200 border border-slate-300/80 text-slate-800 flex items-center justify-center font-mono-code font-extrabold text-[11px] uppercase">
                            {{ $officerInitials ?: 'OF' }}
                        </div>
                    </div>
                </div>
            </header>

            <!-- SCROLLABLE WORKSPACE -->
            <main class="flex-1 overflow-y-auto px-4 sm:px-6 py-5 space-y-5">

                <!-- 1. UNIT STAFF OVERVIEW & 4-CELL INTERACTIVE KPI STRIP -->
                <section aria-label="{{ $unitMeta['overview'] }}" class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                    <div class="px-5 py-4 sm:px-6 bg-slate-100/90 border-b border-slate-200/80 text-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="space-y-0.5">
                            <div class="text-[10.5px] font-mono-code font-bold uppercase tracking-widest text-emerald-700">
                                {{ $unitMeta['overview'] }}
                            </div>
                            <p class="text-xs text-slate-600">
                                {{ $unitMeta['description'] }}
                            </p>
                        </div>
                        <span class="px-2.5 py-1 rounded-lg bg-white border border-slate-200/90 text-[10.5px] font-mono-code font-bold text-emerald-800 shrink-0 self-start sm:self-auto">
                            {{ $unitMeta['badge'] }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-slate-200/70">
                        <!-- KPI 1: TOTAL FIELD OFFICERS -->
                        <button type="button"
                                onclick="applyFieldOfficerQuickFilter('{{ $defaultUnitFilter }}', 'all')"
                                class="p-4 sm:px-6 text-left hover:bg-slate-50/90 transition-colors group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-slate-500 group-hover:text-slate-800">Total Field Officers</span>
                                <span class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 border border-slate-200/80 flex items-center justify-center shrink-0" aria-hidden="true">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </span>
                            </div>
                            <div class="text-2xl font-extrabold text-slate-900 font-mono-code tnum mt-1.5" id="kpiTotalStaff">{{ number_format($totalStaffCount ?? 0) }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">{{ $isUnitScoped ? ($viewerUnit?->value ?? 'Assigned Unit') : 'All operational units' }}</div>
                        </button>

                        <!-- KPI 2: APPROVED STAFF -->
                        <button type="button"
                                onclick="applyFieldOfficerQuickFilter('{{ $defaultUnitFilter }}', 'approved')"
                                class="p-4 sm:px-6 text-left hover:bg-emerald-50/40 transition-colors group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-emerald-700">Approved Staff</span>
                                <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200/80 flex items-center justify-center shrink-0" aria-hidden="true">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </span>
                            </div>
                            <div class="text-2xl font-extrabold text-slate-900 font-mono-code tnum mt-1.5" id="kpiApprovedStaff">{{ number_format($approvedStaffCount ?? 0) }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Cleared for system login</div>
                        </button>

                        <!-- KPI 3: PENDING UNIT APPROVAL -->
                        <button type="button"
                                onclick="applyFieldOfficerQuickFilter('{{ $defaultUnitFilter }}', 'pending')"
                                class="p-4 sm:px-6 text-left hover:bg-amber-50/40 transition-colors group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-amber-800">Pending Approval</span>
                                <span class="w-7 h-7 rounded-lg bg-amber-50 text-amber-700 border border-amber-200/80 flex items-center justify-center shrink-0" aria-hidden="true">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </span>
                            </div>
                            <div class="text-2xl font-extrabold text-slate-900 font-mono-code tnum mt-1.5" id="kpiPendingStaff">{{ number_format($pendingStaffCount ?? 0) }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Requires Head clearance</div>
                        </button>

                        <!-- KPI 4: CURRENTLY ONLINE -->
                        <button type="button"
                                onclick="applyFieldOfficerQuickFilter('{{ $defaultUnitFilter }}', 'approved')"
                                class="p-4 sm:px-6 text-left hover:bg-teal-50/40 transition-colors group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-teal-800">Currently Online</span>
                                <span class="w-7 h-7 rounded-lg bg-teal-50 text-teal-700 border border-teal-200/80 flex items-center justify-center shrink-0" aria-hidden="true">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
                                </span>
                            </div>
                            <div class="text-2xl font-extrabold text-slate-900 font-mono-code tnum mt-1.5" id="kpiOnlineStaff">{{ number_format($onlineStaffCount ?? 0) }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Active sessions (last 5m)</div>
                        </button>
                    </div>
                </section>

                <!-- 2. UNIFIED FILTER TOOLBAR & FIELD OFFICERS TABLE -->
                <section aria-label="Field Officers Directory Table" class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">

                    <div class="border-b border-slate-200/80 divide-y divide-slate-100">

                        <!-- ROW 1: UNIT TABS + STATUS QUICK PILLS + COUNTER -->
                        <div class="p-4 sm:px-5 flex flex-col xl:flex-row xl:items-center justify-between gap-3">
                            <div class="flex items-center gap-1.5 flex-wrap" id="fieldOfficerUnitTabsGroup" role="tablist" aria-label="Filter officers by unit">
                                @if($isUnitScoped)
                                    <button type="button"
                                            data-unit-tab="{{ $defaultUnitFilter }}"
                                            onclick="switchFieldOfficerUnitTab('{{ $defaultUnitFilter }}')"
                                            class="filter-tab-btn inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border bg-slate-200/95 text-slate-900 border-slate-300 shadow-2xs active-tab">
                                        <span>{{ $viewerUnit?->value ?? 'Assigned Unit' }}</span>
                                        <span id="unitTabCountScoped" class="px-1.5 py-0.2 rounded-md text-[10px] font-mono-code font-extrabold tnum bg-emerald-600 text-white">
                                            {{ $totalStaffCount ?? 0 }}
                                        </span>
                                    </button>
                                @else
                                    @php
                                        $dirUnitTabs = [
                                            'all' => ['label' => 'All Units',            'count' => $totalStaffCount ?? 0,      'id' => 'unitTabCountAll'],
                                            'css' => ['label' => 'Crime Scene Support',  'count' => $unitCounts['css'] ?? 0,    'id' => 'unitTabCountCss'],
                                            'em'  => ['label' => 'Evidence Management',  'count' => $unitCounts['em'] ?? 0,     'id' => 'unitTabCountEm'],
                                            'lab' => ['label' => 'Lab Analysis',         'count' => $unitCounts['lab'] ?? 0,    'id' => 'unitTabCountLab'],
                                            'dpf' => ['label' => 'Digital & Physical',   'count' => $unitCounts['dpf'] ?? 0,    'id' => 'unitTabCountDpf'],
                                        ];
                                    @endphp
                                    @foreach($dirUnitTabs as $uKey => $uMeta)
                                        @php $isUTab = ($unitFilter ?? 'all') === $uKey; @endphp
                                        <button type="button"
                                                data-unit-tab="{{ $uKey }}"
                                                onclick="switchFieldOfficerUnitTab('{{ $uKey }}')"
                                                class="filter-tab-btn inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all {{ $isUTab ? 'bg-slate-200/95 text-slate-900 border-slate-300 shadow-2xs active-tab' : 'bg-slate-100/80 text-slate-600 border-transparent hover:bg-slate-200/60 hover:text-slate-900' }}">
                                            <span>{{ $uMeta['label'] }}</span>
                                            <span id="{{ $uMeta['id'] }}"
                                                  class="px-1.5 py-0.2 rounded-md text-[10px] font-mono-code font-extrabold tnum {{ $isUTab ? 'bg-emerald-600 text-white' : 'bg-white text-slate-700 border border-slate-200/80' }}">
                                                {{ $uMeta['count'] }}
                                            </span>
                                        </button>
                                    @endforeach
                                @endif
                            </div>

                            <div class="flex items-center justify-between xl:justify-end gap-2.5">
                                <!-- MOBILE SEARCH -->
                                <div class="relative flex-1 md:hidden">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    </span>
                                    <input type="search"
                                           id="fieldOfficersMobileSearchInput"
                                           value="{{ $search ?? '' }}"
                                           placeholder="Search staff..."
                                           aria-label="Search staff officers"
                                           class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:bg-white focus:border-emerald-600 focus:outline-none">
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <div class="px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-200/80 text-[11px] text-emerald-900 font-mono-code tnum" id="fieldOfficersResultsCounter">
                                        Showing <span class="font-bold text-slate-900">{{ $officers->firstItem() ?? 0 }}</span> - <span class="font-bold text-slate-900">{{ $officers->lastItem() ?? 0 }}</span> of <span class="font-bold text-slate-900">{{ $officers->total() }}</span> officers
                                    </div>
                                    <span id="fieldOfficersActiveFilterBadge"
                                          class="{{ $hasInitialFilter ? '' : 'hidden' }} text-[10px] font-mono-code font-bold px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        Filtered
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- ROW 2: STRUCTURED FILTER CONTROLS -->
                        <form action="{{ url($currentEndpoint ?? '/field-officers') }}"
                              method="GET"
                              id="fieldOfficersFilterForm"
                              class="px-4 py-3 sm:px-5 bg-slate-50/70 flex flex-wrap items-center justify-between gap-3 text-xs">
                            <input type="hidden" name="search" id="fieldOfficersHiddenSearch" value="{{ $search ?? '' }}">

                            <div class="flex items-center gap-2.5 flex-wrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <label for="fieldUnitSelectFilter" class="text-[10.5px] font-bold font-mono-code uppercase text-slate-500 tracking-wider">Unit:</label>
                                    <select name="unit"
                                            id="fieldUnitSelectFilter"
                                            class="px-3 py-1 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 focus:outline-none focus:border-emerald-600">
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

                                <div class="inline-flex items-center gap-1.5">
                                    <label for="fieldStatusSelectFilter" class="text-[10.5px] font-bold font-mono-code uppercase text-slate-500 tracking-wider">Clearance:</label>
                                    <select name="status"
                                            id="fieldStatusSelectFilter"
                                            class="px-3 py-1 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 focus:outline-none focus:border-emerald-600">
                                        <option value="all" {{ ($statusFilter ?? 'all') === 'all' ? 'selected' : '' }}>All Approval Statuses</option>
                                        <option value="pending" {{ ($statusFilter ?? '') === 'pending' ? 'selected' : '' }}>Pending Approval</option>
                                        <option value="approved" {{ ($statusFilter ?? '') === 'approved' ? 'selected' : '' }}>Approved / Active</option>
                                    </select>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 flex-wrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <label for="fieldPerPageSelectFilter" class="sr-only">Rows per page</label>
                                    <select name="per_page"
                                            id="fieldPerPageSelectFilter"
                                            class="px-2.5 py-1 bg-white border border-slate-200 rounded-lg text-[11px] font-mono-code font-semibold text-slate-700 focus:outline-none focus:border-emerald-600">
                                        <option value="15" {{ ($perPage ?? 15) == 15 ? 'selected' : '' }}>15 / page</option>
                                        <option value="25" {{ ($perPage ?? 15) == 25 ? 'selected' : '' }}>25 / page</option>
                                        <option value="50" {{ ($perPage ?? 15) == 50 ? 'selected' : '' }}>50 / page</option>
                                        <option value="100" {{ ($perPage ?? 15) == 100 ? 'selected' : '' }}>100 / page</option>
                                    </select>
                                </div>

                                <button type="button"
                                        onclick="resetFieldOfficerFilters()"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white hover:bg-rose-50 text-slate-600 hover:text-rose-700 border border-slate-200 hover:border-rose-200 text-[11px] font-mono-code font-bold transition-colors"
                                        title="Reset all filters">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    <span>Reset</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- 3. FIELD OFFICERS TABLE WITH SKELETON LOADER -->
                    <div class="relative min-h-[320px]" id="fieldOfficersTableContainer">
                        @include('partials.field_officers_table')
                    </div>
                </section>

            </main>
        </div>
    </div>

    <!-- STAFF OFFICER DETAILS MODAL (CLEAN GREY & WHITE SURFACE) -->
    <div id="staffOfficerDetailsModal"
         role="dialog"
         aria-modal="true"
         aria-labelledby="staffModalTitle"
         class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-500/50 backdrop-blur-xs"
         onclick="if(event.target && event.target.id === 'staffOfficerDetailsModal') closeOfficerDetailsModal()">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden" onclick="event.stopPropagation()">
            <div class="px-5 py-4 bg-slate-100 border-b border-slate-200 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div id="modalOfficerAvatarWrap" class="w-11 h-11 rounded-xl bg-slate-200 border border-slate-300/80 text-slate-800 flex items-center justify-center font-mono-code font-extrabold text-sm shrink-0 overflow-hidden">
                        OF
                    </div>
                    <div class="min-w-0">
                        <h3 id="staffModalTitle" class="text-sm font-extrabold text-slate-900 truncate">Officer Profile</h3>
                        <div id="modalOfficerUsername" class="text-[11px] font-mono-code font-semibold text-emerald-700 truncate">@officer</div>
                    </div>
                </div>
                <button type="button"
                        onclick="closeOfficerDetailsModal()"
                        aria-label="Close officer profile modal"
                        class="p-1.5 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-200/80 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="p-5 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                        <div class="text-[10px] font-mono-code font-bold uppercase text-slate-400">Service ID</div>
                        <div class="mt-1 flex items-center justify-between gap-2">
                            <span id="modalOfficerServiceId" class="font-mono-code font-extrabold text-slate-900 text-xs">-</span>
                            <button type="button"
                                    id="modalCopyServiceIdBtn"
                                    class="px-2 py-0.5 rounded bg-white hover:bg-slate-100 border border-slate-200 text-[10px] font-mono-code font-bold text-slate-600">
                                Copy
                            </button>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                        <div class="text-[10px] font-mono-code font-bold uppercase text-slate-400">Assigned Unit</div>
                        <div id="modalOfficerUnit" class="mt-1 font-bold text-slate-900 text-xs truncate">-</div>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                        <div class="text-[10px] font-mono-code font-bold uppercase text-slate-400">Phone Number</div>
                        <div id="modalOfficerPhone" class="mt-1 font-mono-code font-bold text-slate-800 text-xs">-</div>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                        <div class="text-[10px] font-mono-code font-bold uppercase text-slate-400">Email Address</div>
                        <div id="modalOfficerEmail" class="mt-1 font-medium text-slate-800 text-xs truncate">-</div>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                        <div class="text-[10px] font-mono-code font-bold uppercase text-slate-400">Enrolled On</div>
                        <div id="modalOfficerEnrolled" class="mt-1 font-mono-code font-semibold text-slate-800 text-xs">-</div>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                        <div class="text-[10px] font-mono-code font-bold uppercase text-slate-400">Session Activity</div>
                        <div id="modalOfficerOnline" class="mt-1 font-mono-code font-bold text-slate-800 text-xs">-</div>
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-slate-100/90 border border-slate-200 flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[10px] font-mono-code font-bold uppercase text-slate-500">Account Clearance</div>
                        <div id="modalOfficerClearance" class="text-xs font-extrabold text-slate-900 mt-0.5">-</div>
                    </div>
                    <div id="modalOfficerActionSlot"></div>
                </div>
            </div>

            <div class="px-5 py-3 bg-white border-t border-slate-100 flex items-center justify-end">
                <button type="button"
                        onclick="closeOfficerDetailsModal()"
                        class="px-4 py-1.5 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-bold transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- INLINE ACTION TOAST (CLEAN GREY & WHITE SURFACE) -->
    <div id="fieldOfficersToast" class="fixed top-5 right-5 z-50 hidden transition-all duration-300 -translate-y-2">
        <div class="flex items-center space-x-2.5 px-4 py-3 bg-white text-slate-900 rounded-2xl shadow-xl border border-slate-200">
            <svg id="fieldOfficersToastDot" class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7" /></svg>
            <span class="text-xs font-bold text-slate-800" id="fieldOfficersToastMsg">Status updated.</span>
        </div>
    </div>

    <script>
        const FIELD_OFFICERS_ENDPOINT = '{{ url($currentEndpoint ?? '/field-officers') }}';
        const DEFAULT_UNIT_FILTER = @json($defaultUnitFilter ?? 'all');
        const IS_UNIT_SCOPED = @json($isUnitScoped);
        let lastFieldOfficersSignature = @json($initialSignature ?? null);

        function updateLiveClock() {
            const now = new Date();
            const utcString = now.toISOString().replace('T', ' ').substring(0, 19) + ' UTC';
            const clockEl = document.getElementById('liveClock');
            if (clockEl) clockEl.textContent = utcString;
        }
        setInterval(updateLiveClock, 1000);
        updateLiveClock();

        function renderFieldOfficersSkeletonRows(colCount = 8, rowCount = 6) {
            const nameWidths = ['w-36', 'w-44', 'w-32', 'w-40', 'w-28', 'w-36'];
            const unitWidths = ['w-40', 'w-44', 'w-36', 'w-48', 'w-32', 'w-40'];
            let html = '';

            for (let i = 0; i < rowCount; i++) {
                const nw = nameWidths[i % nameWidths.length];
                const uw = unitWidths[i % unitWidths.length];
                html += `<tr class="border-b border-slate-100 bg-white">
                    <td class="py-4 px-5 whitespace-nowrap">
                        <div class="h-3.5 bg-slate-200/80 rounded w-24"></div>
                        <div class="h-2.5 bg-slate-100 rounded w-20 mt-1.5"></div>
                    </td>
                    <td class="py-3.5 px-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-9.5 h-9.5 rounded-xl bg-slate-200/80 shrink-0"></div>
                            <div class="space-y-1.5">
                                <div class="h-3.5 bg-slate-200/80 rounded ${nw}"></div>
                                <div class="h-2.5 bg-slate-100 rounded w-24"></div>
                            </div>
                        </div>
                    </td>
                    <td class="py-4 px-4 whitespace-nowrap">
                        <div class="h-6 bg-slate-200/80 rounded-lg ${uw}"></div>
                    </td>
                    <td class="py-4 px-4">
                        <div class="h-3 bg-slate-200/80 rounded w-28"></div>
                        <div class="h-2.5 bg-slate-100 rounded w-36 mt-1.5"></div>
                    </td>
                    <td class="py-4 px-4 whitespace-nowrap">
                        <div class="h-3 bg-slate-200/80 rounded w-20"></div>
                        <div class="h-2.5 bg-slate-100 rounded w-16 mt-1.5"></div>
                    </td>
                    <td class="py-4 px-4 text-center whitespace-nowrap">
                        <div class="h-5 bg-slate-200/80 rounded-full w-16 mx-auto"></div>
                    </td>
                    <td class="py-4 px-4 text-center whitespace-nowrap">
                        <div class="h-5 bg-slate-200/80 rounded-full w-24 mx-auto"></div>
                    </td>
                    <td class="py-4 px-5 text-right whitespace-nowrap">
                        <div class="h-7 bg-slate-200/80 rounded-xl w-28 ml-auto"></div>
                    </td>
                </tr>`;
            }

            return html;
        }

        function updateFieldOfficersActiveFilterBadge() {
            const searchVal = (document.getElementById('fieldOfficersSearchInput')?.value || '').trim();
            const statusVal = document.getElementById('fieldStatusSelectFilter')?.value || 'all';
            const unitVal = document.getElementById('fieldUnitSelectFilter')?.value || DEFAULT_UNIT_FILTER;
            const isFiltered = Boolean(
                searchVal ||
                statusVal !== 'all' ||
                (!IS_UNIT_SCOPED && unitVal !== 'all')
            );
            const badge = document.getElementById('fieldOfficersActiveFilterBadge');
            if (badge) badge.classList.toggle('hidden', !isFiltered);
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
            const onlineCount = meta.getAttribute('data-online-count');

            if (totalAll !== null) {
                const el = document.getElementById('kpiTotalStaff');
                if (el) el.textContent = Number(totalAll).toLocaleString();
                const tabAll = document.getElementById('unitTabCountAll');
                if (tabAll) tabAll.textContent = Number(totalAll).toLocaleString();
                const tabScoped = document.getElementById('unitTabCountScoped');
                if (tabScoped) tabScoped.textContent = Number(totalAll).toLocaleString();
            }
            if (approvedCount !== null) {
                const el = document.getElementById('kpiApprovedStaff');
                if (el) el.textContent = Number(approvedCount).toLocaleString();
            }
            if (onlineCount !== null) {
                const el = document.getElementById('kpiOnlineStaff');
                if (el) el.textContent = Number(onlineCount).toLocaleString();
            }
            if (pendingCount !== null) {
                const pendingNum = Number(pendingCount);
                const el = document.getElementById('kpiPendingStaff');
                if (el) el.textContent = pendingNum.toLocaleString();

                const badgeEl = document.getElementById('sidebarFieldOfficersBadge');
                if (badgeEl) {
                    badgeEl.textContent = pendingNum;
                    badgeEl.classList.toggle('hidden', pendingNum <= 0);
                    badgeEl.style.display = pendingNum > 0 ? 'inline-flex' : 'none';
                }
            }

            const unitTabIds = {
                css: 'unitTabCountCss',
                em: 'unitTabCountEm',
                lab: 'unitTabCountLab',
                dpf: 'unitTabCountDpf',
            };
            Object.entries(unitTabIds).forEach(([slug, elId]) => {
                const val = meta.getAttribute('data-unit-' + slug);
                const el = document.getElementById(elId);
                if (val !== null && el) el.textContent = Number(val).toLocaleString();
            });

            updateFieldOfficersActiveFilterBadge();
        }

        function syncFieldOfficerUnitTabState(unitSlug) {
            const buttons = document.querySelectorAll('#fieldOfficerUnitTabsGroup .filter-tab-btn');
            const target = unitSlug || DEFAULT_UNIT_FILTER;
            buttons.forEach((btn) => {
                const isSelected = btn.getAttribute('data-unit-tab') === target;
                btn.className = 'filter-tab-btn inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all ' + (
                    isSelected
                        ? 'bg-slate-200/95 text-slate-900 border-slate-300 shadow-2xs active-tab'
                        : 'bg-slate-100/80 text-slate-600 border-transparent hover:bg-slate-200/60 hover:text-slate-900'
                );
                const badgeSpan = btn.querySelector('span[id^="unitTabCount"]');
                if (badgeSpan) {
                    badgeSpan.className = 'px-1.5 py-0.2 rounded-md text-[10px] font-mono-code font-extrabold tnum ' + (
                        isSelected
                            ? 'bg-emerald-600 text-white'
                            : 'bg-white text-slate-700 border border-slate-200/80'
                    );
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
                    columns: 8,
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
            const mobileSearchInput = document.getElementById('fieldOfficersMobileSearchInput');

            if (searchInput && !searchInput.dataset.boundSync) {
                searchInput.dataset.boundSync = '1';
                searchInput.addEventListener('input', function () {
                    const val = searchInput.value.trim();
                    const hidden = document.getElementById('fieldOfficersHiddenSearch');
                    if (hidden) hidden.value = val;
                    if (mobileSearchInput && mobileSearchInput.value !== searchInput.value) {
                        mobileSearchInput.value = searchInput.value;
                    }
                    updateFieldOfficersActiveFilterBadge();
                });
            }

            if (mobileSearchInput && !mobileSearchInput.dataset.boundSync) {
                mobileSearchInput.dataset.boundSync = '1';
                let mobileTimer = null;
                mobileSearchInput.addEventListener('input', function () {
                    const val = mobileSearchInput.value.trim();
                    if (searchInput && searchInput.value !== mobileSearchInput.value) {
                        searchInput.value = mobileSearchInput.value;
                    }
                    const hidden = document.getElementById('fieldOfficersHiddenSearch');
                    if (hidden) hidden.value = val;
                    updateFieldOfficersActiveFilterBadge();
                    clearTimeout(mobileTimer);
                    mobileTimer = setTimeout(() => {
                        if (window.fieldOfficersAjaxTable) {
                            window.fieldOfficersAjaxTable.applyFilters({ search: val });
                        }
                    }, 300);
                });
            }

            const unitSelect = document.getElementById('fieldUnitSelectFilter');
            if (unitSelect && !unitSelect.dataset.boundSync) {
                unitSelect.dataset.boundSync = '1';
                unitSelect.addEventListener('change', function () {
                    syncFieldOfficerUnitTabState(unitSelect.value);
                    updateFieldOfficersActiveFilterBadge();
                });
            }

            const statusSelect = document.getElementById('fieldStatusSelectFilter');
            if (statusSelect && !statusSelect.dataset.boundSync) {
                statusSelect.dataset.boundSync = '1';
                statusSelect.addEventListener('change', updateFieldOfficersActiveFilterBadge);
            }
        }

        function switchFieldOfficerUnitTab(unitSlug) {
            const target = unitSlug || DEFAULT_UNIT_FILTER;
            const unitSelect = document.getElementById('fieldUnitSelectFilter');
            if (unitSelect) unitSelect.value = target;
            syncFieldOfficerUnitTabState(target);
            updateFieldOfficersActiveFilterBadge();

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
            updateFieldOfficersActiveFilterBadge();

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
            const mobileSearchInput = document.getElementById('fieldOfficersMobileSearchInput');
            if (mobileSearchInput) mobileSearchInput.value = '';
            const hiddenSearch = document.getElementById('fieldOfficersHiddenSearch');
            if (hiddenSearch) hiddenSearch.value = '';

            syncFieldOfficerUnitTabState(DEFAULT_UNIT_FILTER);
            updateFieldOfficersActiveFilterBadge();

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

        function copyOfficerServiceId(serviceId) {
            if (!serviceId || !navigator.clipboard) return;
            navigator.clipboard.writeText(serviceId).then(() => {
                showFieldOfficersToast(`Copied Service ID ${serviceId} to clipboard.`);
            }).catch(() => {});
        }

        function openOfficerDetailsModalFromBtn(btnEl) {
            if (!btnEl) return;
            const raw = btnEl.getAttribute('data-officer-modal');
            if (!raw) return;
            try {
                const payload = JSON.parse(raw);
                openOfficerDetailsModal(payload);
            } catch (e) {}
        }

        function openOfficerDetailsModal(officer) {
            const modal = document.getElementById('staffOfficerDetailsModal');
            if (!modal || !officer) return;

            const avatarWrap = document.getElementById('modalOfficerAvatarWrap');
            if (avatarWrap) {
                if (officer.avatar) {
                    avatarWrap.innerHTML = '';
                    const img = document.createElement('img');
                    img.src = officer.avatar;
                    img.alt = officer.fullname || 'Officer';
                    img.className = 'w-full h-full object-cover';
                    avatarWrap.appendChild(img);
                } else {
                    avatarWrap.textContent = officer.initials || 'OF';
                }
            }

            const setTxt = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.textContent = val || '-';
            };

            setTxt('staffModalTitle', officer.fullname);
            setTxt('modalOfficerUsername', '@' + (officer.username || 'unknown'));
            setTxt('modalOfficerServiceId', officer.service_id);
            setTxt('modalOfficerUnit', (officer.unit_code ? officer.unit_code + ' | ' : '') + (officer.unit || 'Unassigned Unit'));
            setTxt('modalOfficerPhone', officer.phone);
            setTxt('modalOfficerEmail', officer.email);
            setTxt('modalOfficerEnrolled', officer.enrolled);
            setTxt('modalOfficerOnline', (officer.is_online ? 'Online' : 'Offline') + (officer.last_seen ? ' (' + officer.last_seen + ')' : ''));
            setTxt('modalOfficerClearance', officer.status_label);

            const copyBtn = document.getElementById('modalCopyServiceIdBtn');
            if (copyBtn) {
                copyBtn.onclick = () => copyOfficerServiceId(officer.service_id);
            }

            const actionSlot = document.getElementById('modalOfficerActionSlot');
            if (actionSlot) {
                actionSlot.innerHTML = '';
                if (officer.can_manage) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    if (!officer.is_approved) {
                        btn.className = 'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold font-mono-code transition-colors';
                        btn.textContent = officer.revoked ? 'Activate Account' : 'Approve Account';
                        btn.onclick = async () => {
                            await handleStaffApproval(officer.approve_url, 'approve', btn);
                            closeOfficerDetailsModal();
                        };
                    } else {
                        btn.className = 'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-white hover:bg-rose-50 text-rose-700 border border-rose-200 text-xs font-bold font-mono-code transition-colors';
                        btn.textContent = 'Revoke Access';
                        btn.onclick = async () => {
                            await handleStaffApproval(officer.revoke_url, 'revoke', btn);
                            closeOfficerDetailsModal();
                        };
                    }
                    actionSlot.appendChild(btn);
                }
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeOfficerDetailsModal() {
            const modal = document.getElementById('staffOfficerDetailsModal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        function showFieldOfficersToast(message, isError = false) {
            const toast = document.getElementById('fieldOfficersToast');
            const msgEl = document.getElementById('fieldOfficersToastMsg');
            const dotEl = document.getElementById('fieldOfficersToastDot');
            if (!toast || !msgEl) return;

            msgEl.textContent = message;
            if (dotEl) {
                dotEl.setAttribute('class', isError ? 'w-4 h-4 text-rose-600 shrink-0' : 'w-4 h-4 text-emerald-600 shrink-0');
            }

            toast.classList.remove('hidden', '-translate-y-2');
            clearTimeout(window.fieldOfficersToastTimer);
            window.fieldOfficersToastTimer = setTimeout(() => {
                toast.classList.add('-translate-y-2');
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
                try {
                    localStorage.setItem('nacoc_badge_sync', String(Date.now()));
                } catch (e) {}

                if (actionType === 'approve' && data.user) {
                    const approvalPayload = {
                        type: 'staff_approved',
                        user: data.user,
                        ts: Date.now()
                    };
                    if (typeof BroadcastChannel !== 'undefined') {
                        try {
                            const ch = new BroadcastChannel('nacoc_staff_approval');
                            ch.postMessage(approvalPayload);
                            ch.close();
                        } catch (e) {}
                    }
                    try {
                        localStorage.setItem('nacoc_last_staff_approved', JSON.stringify(approvalPayload));
                    } catch (e) {}
                }
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

            const detailsModal = document.getElementById('staffOfficerDetailsModal');
            if (!force && detailsModal && !detailsModal.classList.contains('hidden')) return;

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

                if (!force && data.signature && data.signature === lastFieldOfficersSignature) {
                    return;
                }
                if (data.signature) {
                    lastFieldOfficersSignature = data.signature;
                }

                if (data.html) {
                    const container = document.getElementById('fieldOfficersTableContainer');
                    if (container) {
                        const prevScroll = container.querySelector('.overflow-x-auto')?.scrollLeft || 0;
                        container.innerHTML = data.html;
                        if (prevScroll > 0) {
                            const nextWrap = container.querySelector('.overflow-x-auto');
                            if (nextWrap) nextWrap.scrollLeft = prevScroll;
                        }
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

        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                const desktop = document.getElementById('fieldOfficersSearchInput');
                const mobile = document.getElementById('fieldOfficersMobileSearchInput');
                const target = (desktop && desktop.offsetParent !== null) ? desktop : mobile;
                if (target) {
                    e.preventDefault();
                    target.focus();
                    target.select();
                }
            }
            if (e.key === 'Escape') {
                closeOfficerDetailsModal();
            }
        });

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) pollFieldOfficers(false);
        });
        window.addEventListener('storage', (e) => {
            if (e.key === 'nacoc_badge_sync') pollFieldOfficers(true);
        });

        setupFieldOfficersAjaxTable();
        document.addEventListener('DOMContentLoaded', setupFieldOfficersAjaxTable);
        setInterval(pollFieldOfficers, 10000);
    </script>
</body>
</html>
