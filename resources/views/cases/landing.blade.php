@php
    $cases = $cases ?? collect();

    // ─────────────────────────────────────────────────────────────
    // RESOLVE ACTIVE UNIT CONTEXT ACROSS OPERATIONAL UNITS
    // ─────────────────────────────────────────────────────────────
    $reqUnitLanding = request()->query('unit') ?? request()->query('from');
    $userUnitLanding = auth()->user()->unit ?? (session('user_unit') ?? '');

    $resolveUnit = function ($raw) {
        $lower = strtolower((string) $raw);

        if (str_contains($lower, 'crime') || str_contains($lower, 'field')) {
            return [
                'context'     => 'Crime Scene Support',
                'code'        => 'CSSU',
                'badge'       => 'CRIME SCENE SUPPORT UNIT',
                'heading'     => "Crime Scene Support Unit (CSSU)\nCase Registry & Directory",
                'overview'    => 'Crime Scene Support Case Overview',
                'description' => 'Centralized unit directory for field incident seizures, suspect arrests, scene documentation, and physical forensic exhibits.',
                'homeUrl'     => url('/Crime_Scene_Support/head'),
                'reportUrl'   => url('/Crime_Scene_Support/report'),
                'reportLabel' => 'Unit Report',
                'createUrl'   => url('/Crime_Scene_Support/create'),
                'createLabel' => 'Create New Case',
            ];
        }
        if (str_contains($lower, 'lab')) {
            return [
                'context'     => 'Laboratory Analysis (LAU)',
                'code'        => 'LAU',
                'badge'       => 'LABORATORY ANALYSIS UNIT',
                'heading'     => "Laboratory Analysis Unit (LAU)\nCase Registry & Directory",
                'overview'    => 'Laboratory Analysis Case Overview',
                'description' => 'Centralized laboratory directory for narcotic and chemical exhibit testing, analytical screening, and certified examination dockets.',
                'homeUrl'     => url('/Lab_Analysis'),
                'reportUrl'   => url('/Lab_Analysis/report'),
                'reportLabel' => 'Lab Report',
                'createUrl'   => url('/Lab_Analysis/create'),
                'createLabel' => 'Create New Case',
            ];
        }
        if (str_contains($lower, 'evidence')) {
            return [
                'context'     => 'Evidence Management (EMU)',
                'code'        => 'EMU',
                'badge'       => 'EVIDENCE MANAGEMENT UNIT',
                'heading'     => "Evidence Management Unit (EMU)\nCase Registry & Directory",
                'overview'    => 'Evidence Management Case Overview',
                'description' => 'Centralized custody directory for logged forensic exhibits, vault storage verification, and inter-unit chain-of-custody transfers.',
                'homeUrl'     => url('/Evidence_Management'),
                'reportUrl'   => url('/Evidence_Management/report'),
                'reportLabel' => 'Custody Report',
                'createUrl'   => url('/Evidence_Management/create'),
                'createLabel' => 'Create New Case',
            ];
        }
        if (str_contains($lower, 'digital') || str_contains($lower, 'physical')) {
            return [
                'context'     => 'Digital & Physical Forensics (DPFU)',
                'code'        => 'DPFU',
                'badge'       => 'DIGITAL & PHYSICAL FORENSICS UNIT',
                'heading'     => "Digital & Physical Forensics Unit (DPFU)\nCase Registry & Directory",
                'overview'    => 'Digital & Physical Forensics Case Overview',
                'description' => 'Centralized unit directory for seized electronic devices, mobile hardware exhibits, extraction warrants, and forensic case files.',
                'homeUrl'     => url('/Digital_Forensics'),
                'reportUrl'   => url('/Digital_Forensics/forensic-status'),
                'reportLabel' => 'Status & Reports',
                'createUrl'   => null,
                'createLabel' => null,
            ];
        }

        $fallbackLabel = $raw ?: 'Crime Scene Support';
        return [
            'context'     => $fallbackLabel,
            'code'        => 'FIMS',
            'badge'       => strtoupper($fallbackLabel) . ' UNIT',
            'heading'     => $fallbackLabel . "\nCase Registry & Directory",
            'overview'    => $fallbackLabel . ' Case Overview',
            'description' => 'Centralized repository for logging, searching, and managing narcotic seizures, suspect records, and forensic exhibits.',
            'homeUrl'     => url('/Crime_Scene_Support/head'),
            'reportUrl'   => url('/Crime_Scene_Support/report'),
            'reportLabel' => 'Unit Report',
            'createUrl'   => url('/Crime_Scene_Support/create'),
            'createLabel' => 'Create New Case',
        ];
    };

    if (!empty($reqUnitLanding)) {
        $rawUnit = $reqUnitLanding;
    } elseif (request()->is('Lab_Analysis*') || request()->is('lab-analysis*') || request()->is('lab-result*')) {
        $rawUnit = 'lab';
    } elseif (request()->is('Crime_Scene_Support*') || request()->is('crime-scene-support*')) {
        $rawUnit = 'crime';
    } elseif (!empty($userUnitLanding)) {
        $rawUnit = $userUnitLanding;
    } else {
        $rawUnit = 'crime';
    }

    $resolved = $resolveUnit($rawUnit);
    $landingUnitContext = $resolved['context'];
    $unitCode = $resolved['code'];
    $unitBadge = $resolved['badge'];
    $unitHeading = $resolved['heading'];
    $unitOverviewLabel = $resolved['overview'];
    $unitDescription = $resolved['description'];
    $unitHomeUrl = $resolved['homeUrl'];
    $unitReportUrl = $resolved['reportUrl'];
    $unitReportLabel = $resolved['reportLabel'];
    $addCaseUrl = $resolved['createUrl'];
    $addCaseBtnLabel = $resolved['createLabel'];
    $isLabLanding = str_contains(strtolower($landingUnitContext), 'lab');

    $authUser = auth()->user();
    $officerDisplayName = $authUser->fullname ?? session('user_name') ?? ($unitCode . ' Officer');
    $officerParts = preg_split('/\s+/', trim((string) $officerDisplayName)) ?: [];
    $officerInitials = count($officerParts) >= 2
        ? strtoupper(substr($officerParts[0], 0, 1) . substr(end($officerParts), 0, 1))
        : strtoupper(substr((string) $officerDisplayName, 0, 2));

    $allDatasetCases = $allCases ?? \App\Models\ForensicCase::allCases();

    $kpiStats = $kpiStats ?? [
        'total'            => count($allDatasetCases),
        'lab_analysis'     => $allDatasetCases->whereIn('status', ['Lab Testing', 'Lab Analysis', 'Awaiting Sign-off', 'Under Review'])->count(),
        'pending_approval' => $allDatasetCases->whereIn('status', ['Awaiting Sign-off', 'Pending Approval', 'Pending', 'Pending Review'])->count(),
        'court_certified'  => $allDatasetCases->whereIn('status', ['Approved', 'Court Ready', 'Certified', 'Completed', 'Authorized'])->count(),
        'active_inv'       => $allDatasetCases->filter(fn($c) => !in_array($c->status ?? '', ['Approved', 'Court Ready', 'Certified', 'Completed', 'Authorized', 'Lab Testing', 'Lab Analysis', 'Under Review'], true))->count(),
    ];

    $searchInit = trim((string) request('search', ''));
    $statusInit = trim((string) request('status', 'ALL')) ?: 'ALL';
    $priorityInit = strtoupper(trim((string) request('priority', 'ALL'))) ?: 'ALL';
    $unitFilterInit = trim((string) request('unit_filter', 'all')) ?: 'all';
    $fromDateInit = trim((string) request('from_date', ''));
    $toDateInit = trim((string) request('to_date', ''));
    $perPageInit = (int) request('per_page', 15);
    $totalFilteredInit = method_exists($cases, 'total') ? $cases->total() : count($cases);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ str_replace("\n", ' | ', $unitHeading) }} | NACOC Forensic</title>

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
            opacity: 0.65;
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
        @include('Crime_Scene_Support.partials.sidebar')

        <div class="flex-1 flex flex-col min-w-0 bg-[#F4F6F8] overflow-hidden">

            <!-- COMMAND TOPBAR -->
            <header class="h-16 bg-white border-b border-slate-200/80 px-4 sm:px-6 flex items-center justify-between gap-4 shrink-0 z-20">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 border border-slate-200/90 text-emerald-700 flex items-center justify-center shrink-0 shadow-2xs" aria-hidden="true">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <nav class="flex items-center gap-1.5 text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-emerald-700">
                            <a href="{{ $unitHomeUrl }}" class="hover:underline truncate">{{ $landingUnitContext }}</a>
                            <span class="text-slate-300">/</span>
                            <span class="text-slate-500">Case Registry &amp; Directory</span>
                        </nav>
                        <h1 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight truncate">
                            {{ str_replace("\n", ' • ', $unitHeading) }}
                        </h1>
                    </div>
                </div>

                <!-- GLOBAL COMMAND SEARCH -->
                <div class="hidden md:flex items-center flex-1 max-w-md mx-2">
                    <div class="relative w-full">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 pointer-events-none" aria-hidden="true">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </span>
                        <input type="search"
                               id="topRegistrySearchInput"
                               value="{{ $searchInit }}"
                               oninput="syncRegistrySearch(this.value)"
                               placeholder="Search Case #, Suspect, Location, Officer..."
                               aria-label="Search case registry"
                               class="w-full pl-10 pr-14 py-2 bg-slate-100/80 border border-slate-200/90 rounded-xl text-xs font-mono-code text-slate-900 placeholder-slate-400 focus:bg-white focus:border-emerald-600 focus:outline-none transition-colors">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-2.5 pointer-events-none">
                            <kbd class="px-1.5 py-0.5 text-[9.5px] font-mono-code font-semibold text-slate-400 bg-white rounded border border-slate-200">Ctrl K</kbd>
                        </span>
                    </div>
                </div>

                <!-- TOPBAR ACTIONS & COMMANDER BADGE -->
                <div class="flex items-center gap-2.5 shrink-0">
                    <div class="hidden lg:flex items-center px-3 py-1.5 rounded-xl bg-slate-100 border border-slate-200/90 text-[11px] font-mono-code font-bold text-slate-700 tnum" id="liveClock">
                        {{ gmdate('Y-m-d H:i:s') }} UTC
                    </div>

                    <a href="{{ $unitReportUrl }}"
                       class="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/90 text-xs font-bold shadow-2xs transition-colors">
                        <svg class="w-3.5 h-3.5 text-slate-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>{{ $unitReportLabel }}</span>
                    </a>

                    @if($unitCode !== 'DPFU')
                    <a href="{{ $addCaseUrl }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold shadow-xs shadow-emerald-700/20 transition-colors">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>{{ $addCaseBtnLabel }}</span>
                    </a>
                    @endif

                    <div class="hidden xl:flex items-center gap-2.5 pl-2.5 ml-1 border-l border-slate-200">
                        <div class="text-right">
                            <div class="text-xs font-extrabold text-slate-900 leading-tight truncate max-w-[140px]">{{ $officerDisplayName }}</div>
                            <div class="text-[10.5px] font-mono-code font-semibold text-emerald-700">{{ $unitCode }} Command</div>
                        </div>
                        <div class="w-8 h-8 rounded-xl bg-slate-200 border border-slate-300/80 text-slate-800 flex items-center justify-center font-mono-code font-extrabold text-[11px]">
                            {{ $officerInitials ?: 'OF' }}
                        </div>
                    </div>
                </div>
            </header>

            <!-- SCROLLABLE WORKSPACE -->
            <main class="flex-1 overflow-y-auto px-4 sm:px-6 py-5 space-y-5">

                <!-- UNIT OVERVIEW & 4-COLUMN KPI STRIP -->
                <section aria-label="{{ $unitOverviewLabel }}" class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                    <div class="px-5 py-4 sm:px-6 bg-slate-100/90 border-b border-slate-200/80 text-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="space-y-0.5">
                            <div class="text-[10.5px] font-mono-code font-bold uppercase tracking-widest text-emerald-700">
                                {{ $unitOverviewLabel }}
                            </div>
                            <p class="text-xs text-slate-600">
                                {{ $unitDescription }}
                            </p>
                        </div>
                        <span class="px-2.5 py-1 rounded-lg bg-white border border-slate-200/90 text-[10.5px] font-mono-code font-bold text-emerald-800 shrink-0 self-start sm:self-auto">
                            {{ $unitBadge }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-slate-200/70">
                        <!-- KPI 1: TOTAL REGISTERED CASES -->
                        <button type="button"
                                onclick="filterStatus('ALL')"
                                class="p-4 sm:px-6 text-left hover:bg-slate-50/90 transition-colors group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-slate-500 group-hover:text-slate-800">Total Registered Cases</span>
                                <span class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 border border-slate-200/80 flex items-center justify-center shrink-0" aria-hidden="true">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                </span>
                            </div>
                            <div id="statTotalCases" class="text-2xl font-extrabold text-slate-900 tnum mt-1.5">{{ $kpiStats['total'] }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Active &amp; archived dockets</div>
                        </button>

                        <!-- KPI 2: UNDER LAB ANALYSIS -->
                        <button type="button"
                                onclick="filterStatus('Lab Analysis')"
                                class="p-4 sm:px-6 text-left hover:bg-amber-50/40 transition-colors group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-amber-800">Under Lab Analysis</span>
                                <span class="w-7 h-7 rounded-lg bg-amber-50 text-amber-700 border border-amber-200/80 flex items-center justify-center shrink-0" aria-hidden="true">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                                </span>
                            </div>
                            <div id="statPendingLab" class="text-2xl font-extrabold text-slate-900 tnum mt-1.5">{{ $kpiStats['lab_analysis'] }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Exhibit testing in progress</div>
                        </button>

                        <!-- KPI 3: PENDING APPROVAL -->
                        <button type="button"
                                onclick="filterStatus('Pending Approval')"
                                class="p-4 sm:px-6 text-left hover:bg-teal-50/40 transition-colors group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-teal-800">Pending Approval</span>
                                <span class="w-7 h-7 rounded-lg bg-teal-50 text-teal-700 border border-teal-200/80 flex items-center justify-center shrink-0" aria-hidden="true">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </span>
                            </div>
                            <div id="statPendingApproval" class="text-2xl font-extrabold text-slate-900 tnum mt-1.5">{{ $kpiStats['pending_approval'] }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Awaiting Head &amp; Directorate sign-off</div>
                        </button>

                        <!-- KPI 4: COURT CERTIFIED -->
                        <button type="button"
                                onclick="filterStatus('Court Ready')"
                                class="p-4 sm:px-6 text-left hover:bg-emerald-50/40 transition-colors group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-emerald-700">Court Certified</span>
                                <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200/80 flex items-center justify-center shrink-0" aria-hidden="true">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </span>
                            </div>
                            <div id="statCourtReady" class="text-2xl font-extrabold text-slate-900 tnum mt-1.5">{{ $kpiStats['court_certified'] }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Approved &amp; certified dockets</div>
                        </button>
                    </div>
                </section>

                <!-- CASE REGISTRY & DIRECTORY TABLE CARD -->
                <section aria-label="Case Registry Table" class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">

                    <!-- UNIFIED FILTER TOOLBAR -->
                    <div class="border-b border-slate-200/80 divide-y divide-slate-100">

                        <!-- ROW 1: STAGE TABS + INLINE SEARCH + VISIBLE COUNTER -->
                        <div class="p-4 sm:px-5 flex flex-col xl:flex-row xl:items-center justify-between gap-3">
                            <div class="flex items-center gap-1.5 flex-wrap" role="tablist" aria-label="Filter cases by workflow status">
                                @php
                                    $stageTabs = [
                                        'ALL'                  => ['label' => 'All Cases',            'count' => $kpiStats['total'],            'id' => 'tabRegTotal'],
                                        'Active Investigation' => ['label' => 'Active Investigation', 'count' => $kpiStats['active_inv'],       'id' => 'tabRegActive'],
                                        'Lab Analysis'         => ['label' => 'Lab Analysis',         'count' => $kpiStats['lab_analysis'],     'id' => 'tabRegLab'],
                                        'Pending Approval'     => ['label' => 'Pending Approval',     'count' => $kpiStats['pending_approval'], 'id' => 'tabRegPending'],
                                        'Court Ready'          => ['label' => 'Court Certified',      'count' => $kpiStats['court_certified'],  'id' => 'tabRegCourt'],
                                    ];
                                @endphp
                                @foreach($stageTabs as $tabKey => $tabMeta)
                                    @php $isSel = strcasecmp($statusInit, $tabKey) === 0; @endphp
                                    <button type="button"
                                            role="tab"
                                            aria-selected="{{ $isSel ? 'true' : 'false' }}"
                                            data-status-tab="{{ $tabKey }}"
                                            onclick="filterStatus('{{ $tabKey }}', this)"
                                            class="status-tab-btn inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all {{ $isSel ? 'bg-slate-200/95 text-slate-900 border-slate-300 shadow-2xs' : 'bg-slate-100/80 text-slate-600 border-transparent hover:bg-slate-200/60 hover:text-slate-900' }}">
                                        <span>{{ $tabMeta['label'] }}</span>
                                        <span id="{{ $tabMeta['id'] }}"
                                              class="px-1.5 py-0.2 rounded-md text-[10px] font-mono-code font-extrabold tnum {{ $isSel ? 'bg-emerald-600 text-white' : 'bg-white text-slate-700 border border-slate-200/80' }}">
                                            {{ $tabMeta['count'] }}
                                        </span>
                                    </button>
                                @endforeach
                            </div>

                            <div class="flex items-center justify-between xl:justify-end gap-3">
                                <div class="relative flex-1 sm:w-72">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </div>
                                    <input type="text"
                                           id="caseSearchInput"
                                           value="{{ $searchInit }}"
                                           placeholder="Search Case #, Suspect Name, Offense, Location, Officer..."
                                           oninput="syncRegistrySearch(this.value, true); filterCases()"
                                           class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 transition-colors placeholder:text-slate-400">
                                </div>

                                <div class="flex items-center gap-2 shrink-0 text-[11px] font-mono-code">
                                    <span class="px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-200/80 text-emerald-900 font-bold whitespace-nowrap tnum">
                                        Showing <span id="visibleCaseCount">{{ count($cases) }}</span> of <span id="totalCaseCount">{{ $totalFilteredInit }}</span>
                                    </span>
                                    <span id="activeFilterBadge" class="{{ ($searchInit !== '' || strtoupper($statusInit) !== 'ALL' || $priorityInit !== 'ALL' || $fromDateInit !== '' || $toDateInit !== '' || strtolower($unitFilterInit) !== 'all') ? '' : 'hidden' }} text-[10px] text-emerald-800 bg-emerald-100 border border-emerald-200 px-2 py-0.5 rounded-md font-bold">
                                        Filtered
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- ROW 2: PERIOD PRESETS, DATE RANGE, UNIT, PRIORITY & PER-PAGE -->
                        <div class="px-4 py-3 sm:px-5 bg-slate-50/70 flex flex-wrap items-center justify-between gap-3 text-xs">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-slate-500 mr-1">Period:</span>
                                <button type="button" onclick="setRegistryDatePreset('all')" id="regPresetBtnAll" class="px-2.5 py-1 rounded-lg text-[11px] font-mono-code font-bold transition-colors {{ empty($fromDateInit) && empty($toDateInit) ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200' }}">
                                    All Time
                                </button>
                                <button type="button" onclick="setRegistryDatePreset('weekly')" id="regPresetBtnWeekly" class="px-2.5 py-1 rounded-lg text-[11px] font-mono-code font-bold transition-colors bg-white hover:bg-slate-100 text-slate-700 border border-slate-200">
                                    This Week
                                </button>
                                <button type="button" onclick="setRegistryDatePreset('monthly')" id="regPresetBtnMonthly" class="px-2.5 py-1 rounded-lg text-[11px] font-mono-code font-bold transition-colors bg-white hover:bg-slate-100 text-slate-700 border border-slate-200">
                                    This Month
                                </button>
                                <button type="button" onclick="setRegistryDatePreset('annually')" id="regPresetBtnAnnually" class="px-2.5 py-1 rounded-lg text-[11px] font-mono-code font-bold transition-colors bg-white hover:bg-slate-100 text-slate-700 border border-slate-200">
                                    This Year
                                </button>

                                <div class="hidden sm:block h-4 w-px bg-slate-200 mx-1"></div>

                                <label for="regFromDateInput" class="inline-flex items-center gap-1.5">
                                    <span class="text-[10px] font-mono-code font-bold uppercase text-slate-500">From</span>
                                    <input type="date"
                                           id="regFromDateInput"
                                           value="{{ $fromDateInit }}"
                                           onchange="onRegistryCustomDateChange()"
                                           class="px-2 py-1 bg-white border border-slate-200 rounded-lg text-[11px] font-mono-code text-slate-800 focus:border-emerald-600 focus:outline-none">
                                </label>
                                <label for="regToDateInput" class="inline-flex items-center gap-1.5">
                                    <span class="text-[10px] font-mono-code font-bold uppercase text-slate-500">To</span>
                                    <input type="date"
                                           id="regToDateInput"
                                           value="{{ $toDateInit }}"
                                           onchange="onRegistryCustomDateChange()"
                                           class="px-2 py-1 bg-white border border-slate-200 rounded-lg text-[11px] font-mono-code text-slate-800 focus:border-emerald-600 focus:outline-none">
                                </label>
                            </div>

                            <div class="flex items-center gap-2 flex-wrap">
                                <label for="regUnitFilterSelect" class="sr-only">Filter by originating unit</label>
                                <select id="regUnitFilterSelect"
                                        onchange="executeCaseFilter()"
                                        class="px-2.5 py-1 bg-white border border-slate-200 rounded-lg text-[11px] font-semibold text-slate-700 focus:border-emerald-600 focus:outline-none">
                                    <option value="all" {{ strtolower($unitFilterInit) === 'all' ? 'selected' : '' }}>All Originating Units</option>
                                    <option value="Crime Scene" {{ str_contains(strtolower($unitFilterInit), 'crime') ? 'selected' : '' }}>Crime Scene Support</option>
                                    <option value="Lab" {{ str_contains(strtolower($unitFilterInit), 'lab') ? 'selected' : '' }}>Laboratory Analysis</option>
                                    <option value="Evidence" {{ str_contains(strtolower($unitFilterInit), 'evidence') ? 'selected' : '' }}>Evidence Management</option>
                                    <option value="Digital" {{ str_contains(strtolower($unitFilterInit), 'digital') ? 'selected' : '' }}>Digital &amp; Physical Forensics</option>
                                </select>

                                <label for="regPriorityFilterSelect" class="sr-only">Filter by priority</label>
                                <select id="regPriorityFilterSelect"
                                        onchange="executeCaseFilter()"
                                        class="px-2.5 py-1 bg-white border border-slate-200 rounded-lg text-[11px] font-mono-code font-semibold text-slate-700 focus:border-emerald-600 focus:outline-none">
                                    <option value="ALL" {{ $priorityInit === 'ALL' ? 'selected' : '' }}>Priority: All</option>
                                    <option value="HIGH" {{ $priorityInit === 'HIGH' ? 'selected' : '' }}>High</option>
                                    <option value="LOW" {{ $priorityInit === 'LOW' ? 'selected' : '' }}>Low</option>
                                </select>

                                <label for="regPerPageSelect" class="sr-only">Rows per page</label>
                                <select id="regPerPageSelect"
                                        onchange="changePerPage(this.value)"
                                        class="px-2.5 py-1 bg-white border border-slate-200 rounded-lg text-[11px] font-mono-code font-semibold text-slate-700 focus:border-emerald-600 focus:outline-none">
                                    @foreach([5, 10, 15, 25, 50] as $pp)
                                        <option value="{{ $pp }}" {{ (int) $perPageInit === $pp ? 'selected' : '' }}>{{ $pp }} / page</option>
                                    @endforeach
                                </select>

                                <button type="button"
                                        onclick="resetRegistryFilters()"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white hover:bg-rose-50 text-slate-600 hover:text-rose-700 border border-slate-200 hover:border-rose-200 text-[11px] font-mono-code font-bold transition-colors"
                                        title="Reset all filters">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    <span>Reset</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- TABLE CONTENT -->
                    <div id="casesTableContainer">
                        @include('cases.partials.table')
                    </div>
                </section>

            </main>
        </div>
    </div>

    <!-- DOCKET VIEW MODAL -->
    <div id="docketModal"
         role="dialog"
         aria-modal="true"
         class="hidden fixed inset-0 z-50 bg-slate-500/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden">
            <div class="px-5 py-3.5 bg-slate-100 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <span class="px-2 py-0.5 rounded bg-emerald-50 border border-emerald-200 text-emerald-800 font-mono-code font-bold text-[10px] uppercase">CASE DOCKET LOG</span>
                    <h3 class="text-sm font-extrabold text-slate-900 mt-1" id="docketTitle">Case Docket Details</h3>
                </div>
                <button type="button" onclick="closeDocketModal()" class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1">&times;</button>
            </div>
            <div class="p-5 space-y-4">
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-500 font-bold font-mono-code">DOCKET REF:</span>
                        <span class="font-bold text-emerald-700 font-mono-code" id="docketRef">-</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500 font-bold font-mono-code">PRIMARY SUSPECT:</span>
                        <span class="font-bold text-slate-900" id="docketSuspect">-</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500 font-bold font-mono-code">OFFENSE TITLE:</span>
                        <span class="font-bold text-slate-900" id="docketOffense">-</span>
                    </div>
                </div>
                <div class="flex items-center justify-end">
                    <button type="button" onclick="closeDocketModal()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-bold rounded-xl">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- CUSTODIAL PORTRAIT ENLARGE MODAL -->
    <div id="mugshotModal"
         role="dialog"
         aria-modal="true"
         aria-labelledby="mugshotModalTitle"
         class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-500/50 backdrop-blur-xs transition-opacity duration-200"
         onclick="if(event.target && event.target.id==='mugshotModal') closeMugshotModal()">
        <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 max-w-sm w-full overflow-hidden" onclick="event.stopPropagation()">
            <div class="px-5 py-3.5 bg-slate-100 border-b border-slate-200 text-slate-900 flex items-center justify-between">
                <div>
                    <h4 id="mugshotModalTitle" class="font-mono-code font-bold text-xs uppercase tracking-wider text-slate-800">Custodial Front Portrait</h4>
                    <div id="modalCaseRefLabel" class="text-[10px] font-mono-code font-bold text-emerald-700 mt-0.5"></div>
                </div>
                <button type="button" onclick="closeMugshotModal()" aria-label="Close portrait preview" class="text-slate-500 hover:text-slate-900 transition-colors p-1 rounded-lg hover:bg-slate-200/80">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 flex flex-col items-center bg-slate-50">
                <div class="relative w-56 h-64 rounded-2xl overflow-hidden border-2 border-slate-300 bg-slate-200 shadow-md flex items-center justify-center">
                    <img id="modalMugshotImg" src="" alt="Subject Custodial Portrait" width="224" height="256" class="w-full h-full object-cover">
                </div>
                <div class="mt-4 text-center">
                    <div id="modalSuspectName" class="font-mono-code font-extrabold text-sm uppercase text-slate-900 tracking-tight"></div>
                    <div class="text-[10px] font-mono-code text-slate-500 uppercase mt-0.5 tracking-wider">NACOC Custodial Photographic Archive</div>
                </div>
            </div>
            <div class="px-5 py-3 bg-white border-t border-slate-100 flex items-center justify-end">
                <button type="button" onclick="closeMugshotModal()" class="px-4 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-mono-code font-bold rounded-xl transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- GLOBAL FLOATING SUSPECT HOVER PREVIEW (SOFT GREY & WHITE SURFACE) -->
    <div id="suspectHoverPreview" class="fixed z-[99999] hidden pointer-events-none transition-opacity duration-150 ease-out">
        <div class="w-60 bg-white/95 backdrop-blur-md text-slate-900 rounded-2xl shadow-2xl p-3 border border-slate-200">
            <div class="relative w-full h-44 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 mb-2.5">
                <img id="suspectPreviewImg" src="" alt="" class="w-full h-full object-cover object-center hidden">
                <div id="suspectPreviewFallback" class="w-full h-full flex flex-col items-center justify-center text-slate-400 bg-slate-100">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span class="text-[9px] font-mono-code mt-1 text-slate-400">NO MUGSHOT AVAILABLE</span>
                </div>
                <div class="absolute top-1.5 right-1.5 bg-slate-100/95 border border-slate-200 text-slate-700 text-[8px] font-mono-code px-2 py-0.5 rounded-md uppercase font-bold tracking-wider">
                    SUSPECT PREVIEW
                </div>
            </div>
            <div class="px-0.5 space-y-1">
                <div id="suspectPreviewName" class="font-extrabold text-slate-900 text-xs truncate leading-tight"></div>
                <div class="text-[10px] text-slate-500 font-mono-code truncate">ALIAS: <span id="suspectPreviewAlias" class="text-emerald-700 font-bold"></span></div>
                <div class="text-[9.5px] text-slate-500 font-mono-code truncate pt-1.5 border-t border-slate-100 mt-1 flex justify-between items-center">
                    <span>REF: <span id="suspectPreviewRef" class="font-mono-code text-slate-800 font-bold"></span></span>
                    <span id="suspectPreviewPriority" class="text-emerald-800 font-bold px-1.5 py-0.5 bg-emerald-50 rounded border border-emerald-200"></span>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentStatusFilter = @json($statusInit);
        let currentRegDatePreset = @json((empty($fromDateInit) && empty($toDateInit)) ? 'all' : 'custom');
        let lastCasesSignature = @json($initialSignature ?? null);
        let searchDebounceTimer = null;
        let isPollingMasterCases = false;
        const isLabLanding = {{ $isLabLanding ? 'true' : 'false' }};
        const landingUnitContext = @json($landingUnitContext);

        function updateClock() {
            const now = new Date();
            const utcString = now.toISOString().replace('T', ' ').substring(0, 19) + ' UTC';
            const clockEl = document.getElementById('liveClock');
            if (clockEl) clockEl.innerText = utcString;
        }
        setInterval(updateClock, 1000);
        updateClock();

        function syncRegistryMetaFromDom(explicitStats = null) {
            const meta = document.getElementById('casesRegistryTableMeta');
            if (meta) {
                const vis = meta.getAttribute('data-visible-count') || '0';
                const totFiltered = meta.getAttribute('data-total-filtered') || '0';
                const visEl = document.getElementById('visibleCaseCount');
                const totEl = document.getElementById('totalCaseCount');
                if (visEl) visEl.textContent = vis;
                if (totEl) totEl.textContent = totFiltered;
            }

            const stats = explicitStats || (meta && meta.hasAttribute('data-stat-total') ? {
                total: Number(meta.getAttribute('data-stat-total') || 0),
                lab_analysis: Number(meta.getAttribute('data-stat-lab') || 0),
                pending_approval: Number(meta.getAttribute('data-stat-pending') || 0),
                court_certified: Number(meta.getAttribute('data-stat-court') || 0),
                active_inv: Number(meta.getAttribute('data-stat-active') || 0),
            } : null);

            if (!stats) return;
            const map = {
                statTotalCases: stats.total,
                statPendingLab: stats.lab_analysis,
                statPendingApproval: stats.pending_approval,
                statCourtReady: stats.court_certified,
                tabRegTotal: stats.total,
                tabRegActive: stats.active_inv,
                tabRegLab: stats.lab_analysis,
                tabRegPending: stats.pending_approval,
                tabRegCourt: stats.court_certified,
            };
            Object.entries(map).forEach(([id, val]) => {
                const el = document.getElementById(id);
                if (el && val !== undefined) el.textContent = String(val);
            });
        }

        function updateActiveFilterBadge() {
            const searchVal = (document.getElementById('caseSearchInput')?.value || '').trim();
            const priorityVal = document.getElementById('regPriorityFilterSelect')?.value || 'ALL';
            const unitVal = document.getElementById('regUnitFilterSelect')?.value || 'all';
            const fromVal = document.getElementById('regFromDateInput')?.value || '';
            const toVal = document.getElementById('regToDateInput')?.value || '';
            const isFiltered = Boolean(
                searchVal ||
                (currentStatusFilter && currentStatusFilter.toUpperCase() !== 'ALL') ||
                priorityVal !== 'ALL' ||
                unitVal.toLowerCase() !== 'all' ||
                fromVal ||
                toVal
            );
            const badge = document.getElementById('activeFilterBadge');
            if (badge) badge.classList.toggle('hidden', !isFiltered);
        }

        function setupCasesAjaxTable() {
            if (typeof initAjaxTable !== 'function') return;
            if (window.casesAjaxTable) return;

            window.casesAjaxTable = initAjaxTable({
                container: '#casesTableContainer',
                table: '#casesTable',
                columns: 6,
                rows: 6,
                debounce: 300,
                onAfterUpdate: () => {
                    syncRegistryMetaFromDom();
                    updateActiveFilterBadge();
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupCasesAjaxTable);
        } else {
            setupCasesAjaxTable();
        }

        function syncRegistrySearch(val, fromInline = false) {
            const topEl = document.getElementById('topRegistrySearchInput');
            const inlineEl = document.getElementById('caseSearchInput');
            if (fromInline) {
                if (topEl && topEl.value !== val) topEl.value = val;
            } else {
                if (inlineEl && inlineEl.value !== val) {
                    inlineEl.value = val;
                }
                filterCases();
            }
        }

        function filterCases() {
            const searchInput = document.getElementById('caseSearchInput');
            const searchVal = (searchInput?.value || '').trim();

            if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(() => {
                searchDebounceTimer = null;
                executeCaseFilter(searchVal);
            }, 300);
        }

        function collectFilterPayload(explicitSearch = undefined) {
            const searchVal = explicitSearch !== undefined
                ? explicitSearch
                : (document.getElementById('caseSearchInput')?.value || '').trim();
            const priorityVal = document.getElementById('regPriorityFilterSelect')?.value || 'ALL';
            const unitVal = document.getElementById('regUnitFilterSelect')?.value || 'all';
            const fromVal = document.getElementById('regFromDateInput')?.value || '';
            const toVal = document.getElementById('regToDateInput')?.value || '';

            return {
                search: searchVal || null,
                status: (currentStatusFilter && currentStatusFilter.toUpperCase() !== 'ALL') ? currentStatusFilter : null,
                priority: priorityVal !== 'ALL' ? priorityVal : null,
                unit_filter: unitVal.toLowerCase() !== 'all' ? unitVal : null,
                from_date: fromVal || null,
                to_date: toVal || null,
                page: null,
            };
        }

        function executeCaseFilter(searchVal = undefined) {
            updateActiveFilterBadge();

            if (!window.casesAjaxTable && typeof initAjaxTable === 'function') {
                setupCasesAjaxTable();
            }

            const payload = collectFilterPayload(searchVal);
            if (window.casesAjaxTable) {
                window.casesAjaxTable.applyFilters(payload);
            }
        }

        function updateStatusTabVisuals(activeName) {
            document.querySelectorAll('.status-tab-btn').forEach(btn => {
                const tabKey = btn.getAttribute('data-status-tab') || '';
                const isSelected = tabKey.toLowerCase() === String(activeName || 'ALL').toLowerCase();
                btn.setAttribute('aria-selected', isSelected ? 'true' : 'false');
                btn.className = 'status-tab-btn inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all ' + (
                    isSelected
                        ? 'bg-slate-200/95 text-slate-900 border-slate-300 shadow-2xs'
                        : 'bg-slate-100/80 text-slate-600 border-transparent hover:bg-slate-200/60 hover:text-slate-900'
                );
                const countSpan = btn.querySelector('span[id^="tabReg"]');
                if (countSpan) {
                    countSpan.className = 'px-1.5 py-0.2 rounded-md text-[10px] font-mono-code font-extrabold tnum ' + (
                        isSelected
                            ? 'bg-emerald-600 text-white'
                            : 'bg-white text-slate-700 border border-slate-200/80'
                    );
                }
            });
        }

        function filterStatus(statusName) {
            currentStatusFilter = statusName;
            updateStatusTabVisuals(statusName);
            executeCaseFilter();
        }

        function updateDatePresetVisuals(activePreset) {
            const presets = ['all', 'weekly', 'monthly', 'annually'];
            presets.forEach(p => {
                const btn = document.getElementById('regPresetBtn' + p.charAt(0).toUpperCase() + p.slice(1));
                if (!btn) return;
                btn.className = 'px-2.5 py-1 rounded-lg text-[11px] font-mono-code font-bold transition-colors ' + (
                    p === activePreset
                        ? 'bg-emerald-600 text-white shadow-2xs'
                        : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200'
                );
            });
        }

        function setRegistryDatePreset(preset) {
            currentRegDatePreset = preset;
            updateDatePresetVisuals(preset);

            const fromEl = document.getElementById('regFromDateInput');
            const toEl = document.getElementById('regToDateInput');
            const now = new Date();
            const pad = n => String(n).padStart(2, '0');
            const fmt = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

            if (preset === 'all') {
                if (fromEl) fromEl.value = '';
                if (toEl) toEl.value = '';
            } else if (preset === 'weekly') {
                const day = now.getDay();
                const diffToMon = (day === 0 ? -6 : 1) - day;
                const monday = new Date(now);
                monday.setDate(now.getDate() + diffToMon);
                const sunday = new Date(monday);
                sunday.setDate(monday.getDate() + 6);
                if (fromEl) fromEl.value = fmt(monday);
                if (toEl) toEl.value = fmt(sunday);
            } else if (preset === 'monthly') {
                const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                if (fromEl) fromEl.value = fmt(firstDay);
                if (toEl) toEl.value = fmt(lastDay);
            } else if (preset === 'annually') {
                const firstDay = new Date(now.getFullYear(), 0, 1);
                const lastDay = new Date(now.getFullYear(), 11, 31);
                if (fromEl) fromEl.value = fmt(firstDay);
                if (toEl) toEl.value = fmt(lastDay);
            }

            executeCaseFilter();
        }

        function onRegistryCustomDateChange() {
            currentRegDatePreset = 'custom';
            updateDatePresetVisuals('custom');
            executeCaseFilter();
        }

        function resetRegistryFilters() {
            const topSearch = document.getElementById('topRegistrySearchInput');
            const inlineSearch = document.getElementById('caseSearchInput');
            const fromEl = document.getElementById('regFromDateInput');
            const toEl = document.getElementById('regToDateInput');
            const priEl = document.getElementById('regPriorityFilterSelect');
            const unitEl = document.getElementById('regUnitFilterSelect');

            if (topSearch) topSearch.value = '';
            if (inlineSearch) inlineSearch.value = '';
            if (fromEl) fromEl.value = '';
            if (toEl) toEl.value = '';
            if (priEl) priEl.value = 'ALL';
            if (unitEl) unitEl.value = 'all';

            currentStatusFilter = 'ALL';
            currentRegDatePreset = 'all';
            updateStatusTabVisuals('ALL');
            updateDatePresetVisuals('all');
            executeCaseFilter('');
        }

        function goToPage(page) {
            if (window.casesAjaxTable) {
                window.casesAjaxTable.applyFilters({ page: page });
            }
        }

        function changePerPage(val) {
            if (window.casesAjaxTable) {
                window.casesAjaxTable.applyFilters({ per_page: val, page: 1 });
            }
        }

        function viewDocket(ref) {
            window.location.href = '/cases/' + encodeURIComponent(ref) + '/review?from=' + encodeURIComponent(landingUnitContext);
        }

        function closeDocketModal() {
            document.getElementById('docketModal')?.classList.add('hidden');
        }

        function copyRegistryCaseRef(caseRef) {
            if (!caseRef || !navigator.clipboard) return;
            navigator.clipboard.writeText(caseRef).then(() => {
                if (typeof window.showFlashToast === 'function') {
                    window.showFlashToast(`Copied case reference ${caseRef} to clipboard.`, 'success', 'Reference Copied');
                }
            }).catch(() => {});
        }

        function openMugshotModal(imgUrl, suspectName, caseRef = '') {
            if (!imgUrl) return;
            const modal = document.getElementById('mugshotModal');
            const img = document.getElementById('modalMugshotImg');
            const nameEl = document.getElementById('modalSuspectName');
            const refEl = document.getElementById('modalCaseRefLabel');
            if (img) img.src = imgUrl;
            if (nameEl) nameEl.textContent = suspectName || 'Subject';
            if (refEl) refEl.textContent = caseRef ? `Docket: ${caseRef}` : '';
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            }
        }

        function closeMugshotModal() {
            const modal = document.getElementById('mugshotModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('caseSearchInput');
            if (searchInput) {
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
                        searchDebounceTimer = null;
                        executeCaseFilter((this.value || '').trim());
                    }
                });
            }
        });

        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                const target = document.getElementById('topRegistrySearchInput') || document.getElementById('caseSearchInput');
                if (target && target.offsetParent !== null) {
                    e.preventDefault();
                    target.focus();
                    target.select();
                } else {
                    const fallback = document.getElementById('caseSearchInput');
                    if (fallback) {
                        e.preventDefault();
                        fallback.focus();
                        fallback.select();
                    }
                }
            }
            if (e.key === 'Escape') {
                closeMugshotModal();
                closeDocketModal();
                document.querySelectorAll('.case-action-menu').forEach(m => m.classList.add('hidden'));
            }
        });

        /* ── Diff-Guarded 5-Second Silent Background Polling Engine ── */
        async function pollMasterCasesTable(force = false) {
            if (isPollingMasterCases || (!force && document.hidden)) return;
            if (searchDebounceTimer !== null) return;
            if (window.casesAjaxTable && window.casesAjaxTable.isLoading) return;

            const docketModal = document.getElementById('docketModal');
            const mugshotModal = document.getElementById('mugshotModal');
            const activeActionMenu = document.querySelector('.case-action-menu:not(.hidden)');
            if ((docketModal && !docketModal.classList.contains('hidden')) ||
                (mugshotModal && !mugshotModal.classList.contains('hidden')) ||
                activeActionMenu) return;

            isPollingMasterCases = true;
            const controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
            const timeoutId = controller ? setTimeout(() => controller.abort(), 6000) : null;

            try {
                const params = new URLSearchParams(window.location.search);
                params.set('poll', '1');

                const res = await fetch(`${window.location.pathname}?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    signal: controller ? controller.signal : undefined,
                });
                if (timeoutId) clearTimeout(timeoutId);
                if (!res.ok) return;

                const data = await res.json();
                if (!data || !data.success) return;
                if (window.casesAjaxTable && window.casesAjaxTable.isLoading) return;

                if (data.stats) {
                    syncRegistryMetaFromDom(data.stats);
                }

                if (!force && data.signature && data.signature === lastCasesSignature) {
                    return;
                }
                if (data.signature) {
                    lastCasesSignature = data.signature;
                }

                const container = document.getElementById('casesTableContainer');
                if (container && typeof data.html === 'string') {
                    const prevScroll = container.querySelector('.overflow-x-auto')?.scrollLeft || 0;
                    container.innerHTML = data.html;
                    if (prevScroll > 0) {
                        const nextWrap = container.querySelector('.overflow-x-auto');
                        if (nextWrap) nextWrap.scrollLeft = prevScroll;
                    }
                    syncRegistryMetaFromDom(data.stats || null);
                }
            } catch (e) {
                // Background polling failure handled silently
            } finally {
                if (timeoutId) clearTimeout(timeoutId);
                isPollingMasterCases = false;
            }
        }

        setInterval(() => pollMasterCasesTable(false), 5000);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) pollMasterCasesTable(false);
        });
        window.addEventListener('focus', () => pollMasterCasesTable(false));
        window.addEventListener('storage', (e) => {
            if (e.key === 'nacoc_badge_sync') pollMasterCasesTable(true);
        });

        // Floating Subject Hover Preview
        document.addEventListener('mouseover', function(e) {
            const trigger = e.target.closest('.suspect-avatar-trigger');
            const popover = document.getElementById('suspectHoverPreview');
            if (!popover || !trigger) return;

            const name = trigger.getAttribute('data-name') || 'Unassigned Target';
            const alias = trigger.getAttribute('data-alias') || 'N/A';
            const pic = trigger.getAttribute('data-pic') || '';
            const ref = trigger.getAttribute('data-ref') || '';
            const priority = trigger.getAttribute('data-priority') || 'HIGH';

            const nameEl = document.getElementById('suspectPreviewName');
            const aliasEl = document.getElementById('suspectPreviewAlias');
            const refEl = document.getElementById('suspectPreviewRef');
            const priorityEl = document.getElementById('suspectPreviewPriority');
            const imgEl = document.getElementById('suspectPreviewImg');
            const fallbackEl = document.getElementById('suspectPreviewFallback');

            if (nameEl) nameEl.textContent = name;
            if (aliasEl) aliasEl.textContent = alias;
            if (refEl) refEl.textContent = ref;
            if (priorityEl) priorityEl.textContent = priority;

            if (pic) {
                imgEl.src = pic;
                imgEl.classList.remove('hidden');
                fallbackEl.classList.add('hidden');
            } else {
                imgEl.src = '';
                imgEl.classList.add('hidden');
                fallbackEl.classList.remove('hidden');
            }

            const rect = trigger.getBoundingClientRect();
            const popoverWidth = 240;
            const popoverHeight = 250;

            let left = rect.right + 12;
            let top = rect.top + (rect.height / 2) - (popoverHeight / 2);

            if (top < 10) top = 10;
            if (top + popoverHeight > window.innerHeight - 10) top = window.innerHeight - popoverHeight - 10;
            if (left + popoverWidth > window.innerWidth - 10) left = rect.left - popoverWidth - 12;

            popover.style.top = top + 'px';
            popover.style.left = left + 'px';
            popover.classList.remove('hidden');
        });

        document.addEventListener('mouseout', function(e) {
            const trigger = e.target.closest('.suspect-avatar-trigger');
            if (trigger) {
                document.getElementById('suspectHoverPreview')?.classList.add('hidden');
            }
        });

        function toggleCaseActionDropdown(event, caseRef) {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            const btn = event ? (event.currentTarget || (event.target ? event.target.closest('button') : null)) : null;
            const targetMenu = document.getElementById('actionMenu-' + caseRef);
            if (!targetMenu) return;

            const isHidden = targetMenu.classList.contains('hidden');

            document.querySelectorAll('.case-action-menu').forEach(menu => {
                menu.classList.add('hidden');
            });

            if (isHidden) {
                targetMenu.classList.remove('hidden');

                const buttonEl = btn || document.querySelector(`button[onclick*="'${caseRef}'"]`);
                if (buttonEl) {
                    const rect = buttonEl.getBoundingClientRect();
                    const menuWidth = 224;
                    const menuHeight = targetMenu.offsetHeight || 168;

                    let top = rect.bottom + 6;
                    let left = rect.right - menuWidth;

                    if (top + menuHeight > window.innerHeight - 10 && rect.top > menuHeight + 10) {
                        top = rect.top - menuHeight - 6;
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

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.case-action-dropdown-wrapper') && !e.target.closest('.case-action-menu')) {
                document.querySelectorAll('.case-action-menu').forEach(menu => {
                    menu.classList.add('hidden');
                });
            }
        });

        window.addEventListener('scroll', function() {
            document.querySelectorAll('.case-action-menu').forEach(menu => {
                menu.classList.add('hidden');
            });
        }, true);
    </script>
</body>
</html>
