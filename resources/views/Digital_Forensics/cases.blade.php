@php
    $authUser = auth()->user();
    $dbUnitHead = \App\Models\User::where('unit', 'LIKE', '%Digital%')->orWhere('unit', 'LIKE', '%Physical%')->where('is_head', true)->first()
               ?? \App\Models\User::where('is_head', true)->first();
    $headName = $headName ?? ($authUser->fullname ?? session('user_name') ?? ($dbUnitHead ? $dbUnitHead->fullname : 'Lead Cyber Analyst'));
    $headParts = preg_split('/\s+/', trim((string) $headName)) ?: [];
    $headInitials = count($headParts) >= 2
        ? strtoupper(substr($headParts[0], 0, 1) . substr(end($headParts), 0, 1))
        : strtoupper(substr((string) $headName, 0, 2));

    $allCases = $allCases ?? \App\Models\ForensicCase::allCases();
    $digitalCases = $digitalCases ?? $allCases->filter(function ($c) {
        if (!$c->isDigitalCase()) {
            return false;
        }
        $elecSuspects = $c->getElectronicDeviceSuspects();
        return !empty($elecSuspects);
    })->values();

    if (!isset($cases) || !($cases instanceof \Illuminate\Pagination\LengthAwarePaginator)) {
        $cases = \App\Models\ForensicCase::paginateCases($digitalCases, (int) request()->input('per_page', 15));
    }

    $dpfReportsByCase = $dpfReportsByCase ?? (
        (\Illuminate\Support\Facades\Schema::hasTable('dpf_report') && $digitalCases->isNotEmpty())
            ? \App\Models\DpfReport::whereIn('case_number', $digitalCases->pluck('case_number')->filter()->unique()->all())
                ->orderByDesc('id')
                ->get()
                ->groupBy('case_number')
            : collect()
    );

    if (!isset($registerStats)) {
        $totCases = $digitalCases->count();
        $totDevices = 0;
        $extCount = 0;
        $warCount = 0;
        $mulCount = 0;
        foreach ($digitalCases as $dc) {
            $rawEx = ($dc->relationLoaded('exhibits') && $dc->exhibits) ? collect($dc->exhibits) : collect();
            $totDevices += max(1, $rawEx->count());
            $rep = $dpfReportsByCase->get($dc->case_number, collect())->first();
            $extRaw = strtoupper(trim((string) ($rep->extraction_analysis_message ?? ($rep->extraction_analysis_conducted ?? ''))));
            $courtRaw = strtoupper(trim((string) ($rep->court_order_granted ?? '')));
            if ($rep && (str_contains($extRaw, 'YES') || str_contains($extRaw, 'COMPLET'))) {
                $extCount++;
            }
            if ($rep && str_contains($courtRaw, 'YES')) {
                $warCount++;
            }
            if (!empty($dc->is_multiple) || count($dc->getElectronicDeviceSuspects()) > 1) {
                $mulCount++;
            }
        }
        $registerStats = [
            'total_cases'         => $totCases,
            'total_devices'       => $totDevices,
            'extracted_cases'     => $extCount,
            'pending_extraction'  => max(0, $totCases - $extCount),
            'court_order_cases'   => $warCount,
            'multi_subject_cases' => $mulCount,
            'extraction_rate'     => $totCases > 0 ? (int) round(($extCount / $totCases) * 100) : 0,
        ];
    }

    $search = $search ?? trim((string) request('search', ''));
    $pipeline = $pipeline ?? strtolower(trim((string) request('pipeline', 'all')));
    $disposition = $disposition ?? strtolower(trim((string) request('disposition', 'all')));
    $from = $from ?? trim((string) request('from', ''));
    $to = $to ?? trim((string) request('to', ''));
    $perPage = $perPage ?? (int) request('per_page', 15);

    $firstItemInit = method_exists($cases, 'firstItem') ? ($cases->firstItem() ?? 0) : (count($cases) > 0 ? 1 : 0);
    $lastItemInit = method_exists($cases, 'lastItem') ? ($cases->lastItem() ?? 0) : count($cases);
    $totalFilteredInit = method_exists($cases, 'total') ? $cases->total() : count($cases);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Digital Forensics Case Register | NACOC Forensic</title>

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
        a[href], button:not(:disabled), select:not(:disabled), [role="button"] {
            cursor: pointer;
        }
        button:disabled, select:disabled {
            cursor: not-allowed;
            opacity: 0.65;
        }
        a:focus-visible, button:not(:disabled):focus-visible, input:focus-visible, select:focus-visible, [role="button"]:focus-visible {
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-emerald-700">
                            <span>Digital &amp; Physical Forensic Unit</span>
                            <span class="text-slate-300">/</span>
                            <span class="text-slate-500">Case Register</span>
                        </div>
                        <h1 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight truncate">
                            Digital Forensics Case Register
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
                               id="topGlobalSearchInput"
                               value="{{ $search }}"
                               oninput="syncSearchFields(this.value)"
                               placeholder="Search Case #, Subject, IMEI, Serial, Model, DPF Code..."
                               aria-label="Search digital forensics dockets"
                               class="w-full pl-10 pr-14 py-2 bg-slate-100/80 border border-slate-200/90 rounded-xl text-xs font-mono-code text-slate-900 placeholder-slate-400 focus:bg-white focus:border-emerald-600 focus:outline-none transition-colors">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-2.5 pointer-events-none">
                            <kbd class="px-1.5 py-0.5 text-[9.5px] font-mono-code font-semibold text-slate-400 bg-white rounded border border-slate-200">Ctrl K</kbd>
                        </span>
                    </div>
                </div>

                <!-- TOPBAR ACTIONS & COMMANDER BADGE -->
                <div class="flex items-center gap-2.5 shrink-0">
                    <button type="button"
                            id="regBtnGenerateFilter"
                            onclick="generateRegCaseFilter()"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/90 text-xs font-bold shadow-2xs transition-colors"
                            title="Export currently filtered dockets as an official DPF PDF Register">
                        <svg class="w-3.5 h-3.5 text-slate-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        <span class="hidden sm:inline">Export PDF</span>
                    </button>

                    <div class="hidden xl:flex items-center gap-2.5 pl-2.5 ml-1 border-l border-slate-200">
                        <div class="text-right">
                            <div class="text-xs font-extrabold text-slate-900 leading-tight truncate max-w-[140px]">{{ $headName }}</div>
                            <div class="text-[10.5px] font-mono-code font-semibold text-emerald-700">DPFU Command</div>
                        </div>
                        <div class="w-8 h-8 rounded-xl bg-slate-200 border border-slate-300/80 text-slate-800 flex items-center justify-center font-mono-code font-extrabold text-[11px]">
                            {{ $headInitials ?: 'DF' }}
                        </div>
                    </div>
                </div>
            </header>

            <!-- SCROLLABLE WORKSPACE -->
            <main class="flex-1 overflow-y-auto px-4 sm:px-6 py-5 space-y-5">

                <!-- EXECUTIVE TELEMETRY LEDGER (CLICKABLE QUICK-FILTER CELLS) -->
                <section aria-label="Digital Forensics Register Telemetry" class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                    <div class="px-5 py-4 sm:px-6 bg-slate-100/90 border-b border-slate-200/80 text-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="space-y-0.5">
                            <div class="text-[10.5px] font-mono-code font-bold uppercase tracking-widest text-emerald-700">
                                Digital Forensics Case Overview
                            </div>
                            <p class="text-xs text-slate-600">
                                Centralized register of dockets with seized mobile phones, computers, storage media, court extraction warrants, and forensic examination reports.
                            </p>
                        </div>
                        <a href="{{ url('/Digital_Forensics/forensic-status') }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 hover:text-emerald-800 border border-slate-200/90 shadow-2xs text-[11px] font-mono-code font-bold shrink-0 self-start sm:self-auto transition-colors">
                            <span>Extraction &amp; Warrant Log</span>
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>

                    <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-slate-200/70">
                        <!-- KPI 1: TOTAL CYBER DOCKETS -->
                        <button type="button"
                                onclick="setPipelineFilter('all')"
                                class="p-4 sm:px-6 text-left hover:bg-slate-50/90 transition-colors group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-slate-500 group-hover:text-slate-800">Registered Dockets</span>
                                <span class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center shrink-0" aria-hidden="true">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                                </span>
                            </div>
                            <div id="kpiTotalCases" class="text-2xl font-extrabold text-slate-900 tnum mt-1.5">{{ $registerStats['total_cases'] }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">
                                <span id="kpiMultiSubjectSub" class="font-mono-code font-bold text-slate-700 tnum">{{ $registerStats['multi_subject_cases'] }}</span> multi-subject dockets
                            </div>
                        </button>

                        <!-- KPI 2: SEIZED ELECTRONIC EXHIBITS -->
                        <div class="p-4 sm:px-6 text-left">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-teal-800">Seized Digital Exhibits</span>
                                <span class="w-7 h-7 rounded-lg bg-teal-50 text-teal-700 border border-teal-200/70 flex items-center justify-center shrink-0" aria-hidden="true">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                </span>
                            </div>
                            <div id="kpiTotalDevices" class="text-2xl font-extrabold text-slate-900 tnum mt-1.5">{{ $registerStats['total_devices'] }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Phones, computers &amp; storage media</div>
                        </div>

                        <!-- KPI 3: EXTRACTION COMPLETED -->
                        <button type="button"
                                onclick="setPipelineFilter('extracted')"
                                class="p-4 sm:px-6 text-left hover:bg-emerald-50/40 transition-colors group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-emerald-700">Extraction Complete</span>
                                <span id="kpiExtractionRateBadge" class="px-2 py-0.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 font-mono-code text-[10px] font-extrabold tnum">
                                    {{ $registerStats['extraction_rate'] }}%
                                </span>
                            </div>
                            <div id="kpiExtractedCases" class="text-2xl font-extrabold text-slate-900 tnum mt-1.5">{{ $registerStats['extracted_cases'] }}</div>
                            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden mt-2">
                                <div id="kpiExtractionProgressBar" class="h-full bg-emerald-600 rounded-full transition-all duration-300" style="width: {{ $registerStats['extraction_rate'] }}%;"></div>
                            </div>
                        </button>

                        <!-- KPI 4: PENDING EXTRACTION & WARRANTS -->
                        <button type="button"
                                onclick="setPipelineFilter('pending_extraction')"
                                class="p-4 sm:px-6 text-left hover:bg-amber-50/40 transition-colors group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-amber-800">Pending Extraction</span>
                                <span class="w-7 h-7 rounded-lg bg-amber-50 text-amber-700 border border-amber-200/80 flex items-center justify-center shrink-0" aria-hidden="true">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </span>
                            </div>
                            <div id="kpiPendingExtraction" class="text-2xl font-extrabold text-slate-900 tnum mt-1.5">{{ $registerStats['pending_extraction'] }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">
                                <span id="kpiWarrantSub" class="font-mono-code font-bold text-emerald-700 tnum">{{ $registerStats['court_order_cases'] }}</span> court warrants granted
                            </div>
                        </button>
                    </div>
                </section>

                <!-- CASE REGISTER LEDGER CARD -->
                <section aria-label="Digital Forensics Case Table" class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">

                    <!-- UNIFIED FILTER FORM -->
                    <form id="dpfFilterForm" action="{{ url('/Digital_Forensics/cases') }}" method="GET" class="border-b border-slate-200/80 divide-y divide-slate-100">
                        <input type="hidden" name="pipeline" id="pipelineFilterInput" value="{{ $pipeline ?: 'all' }}">

                        <!-- ROW 1: PIPELINE TABS + MOBILE SEARCH + LIVE RESULT COUNTER -->
                        <div class="p-4 sm:px-5 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                            <div class="flex items-center gap-1.5 flex-wrap" role="tablist" aria-label="Filter dockets by forensic pipeline stage">
                                @php
                                    $pipelineTabs = [
                                        'all'                => ['label' => 'All Dockets',        'count' => $registerStats['total_cases'],         'id' => 'tabCountAll'],
                                        'pending_extraction' => ['label' => 'Pending Extraction', 'count' => $registerStats['pending_extraction'],  'id' => 'tabCountPending'],
                                        'extracted'          => ['label' => 'Extraction Done',    'count' => $registerStats['extracted_cases'],     'id' => 'tabCountExtracted'],
                                        'court_order'        => ['label' => 'Warrant Granted',    'count' => $registerStats['court_order_cases'],   'id' => 'tabCountWarrant'],
                                        'multi_subject'      => ['label' => 'Multi-Subject',      'count' => $registerStats['multi_subject_cases'], 'id' => 'tabCountMulti'],
                                    ];
                                @endphp
                                @foreach($pipelineTabs as $pKey => $pMeta)
                                    @php $isActiveTab = ($pipeline ?: 'all') === $pKey; @endphp
                                    <button type="button"
                                            role="tab"
                                            aria-selected="{{ $isActiveTab ? 'true' : 'false' }}"
                                            data-pipeline-tab="{{ $pKey }}"
                                            onclick="setPipelineFilter('{{ $pKey }}')"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all {{ $isActiveTab ? 'bg-slate-200/95 text-slate-900 border-slate-300 shadow-2xs' : 'bg-slate-100/80 text-slate-600 border-transparent hover:bg-slate-200/60 hover:text-slate-900' }}">
                                        <span>{{ $pMeta['label'] }}</span>
                                        <span id="{{ $pMeta['id'] }}"
                                              class="px-1.5 py-0.2 rounded-md text-[10px] font-mono-code font-extrabold tnum {{ $isActiveTab ? 'bg-emerald-600 text-white' : 'bg-white text-slate-700 border border-slate-200/80' }}">
                                            {{ $pMeta['count'] }}
                                        </span>
                                    </button>
                                @endforeach
                            </div>

                            <div class="flex items-center justify-between lg:justify-end gap-3">
                                <div class="relative flex-1 md:w-64 lg:w-72">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 pointer-events-none" aria-hidden="true">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    </span>
                                    <input type="search"
                                           name="search"
                                           id="regSearchInput"
                                           value="{{ $search }}"
                                           oninput="syncSearchFields(this.value, true)"
                                           placeholder="Filter by Case #, IMEI, Subject..."
                                           aria-label="Filter case register table"
                                           class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 placeholder-slate-400 focus:bg-white focus:border-emerald-600 focus:outline-none transition-colors">
                                </div>

                                <span id="regRowCountBadge" class="px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-200/80 text-emerald-900 text-[11px] font-mono-code font-bold whitespace-nowrap tnum">
                                    {{ $firstItemInit }}–{{ $lastItemInit }} of {{ $totalFilteredInit }}
                                </span>
                            </div>
                        </div>

                        <!-- ROW 2: PERIOD PRESETS, DATE RANGE, DISPOSITION & ROWS PER PAGE -->
                        <div class="px-4 py-3 sm:px-5 bg-slate-50/70 flex flex-wrap items-center justify-between gap-3 text-xs">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-slate-500 mr-1">Period:</span>
                                <button type="button" onclick="setRegDateFilterPreset('all')" id="regBtnPresetAll" class="px-2.5 py-1 rounded-lg text-[11px] font-mono-code font-bold transition-colors {{ empty($from) && empty($to) ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200' }}">
                                    All Time
                                </button>
                                <button type="button" onclick="setRegDateFilterPreset('weekly')" id="regBtnPresetWeekly" class="px-2.5 py-1 rounded-lg text-[11px] font-mono-code font-bold transition-colors bg-white hover:bg-slate-100 text-slate-700 border border-slate-200">
                                    This Week
                                </button>
                                <button type="button" onclick="setRegDateFilterPreset('monthly')" id="regBtnPresetMonthly" class="px-2.5 py-1 rounded-lg text-[11px] font-mono-code font-bold transition-colors bg-white hover:bg-slate-100 text-slate-700 border border-slate-200">
                                    This Month
                                </button>
                                <button type="button" onclick="setRegDateFilterPreset('annually')" id="regBtnPresetAnnually" class="px-2.5 py-1 rounded-lg text-[11px] font-mono-code font-bold transition-colors bg-white hover:bg-slate-100 text-slate-700 border border-slate-200">
                                    This Year
                                </button>

                                <div class="hidden sm:block h-4 w-px bg-slate-200 mx-1"></div>

                                <label for="regDateFilterFrom" class="inline-flex items-center gap-1.5">
                                    <span class="text-[10px] font-mono-code font-bold uppercase text-slate-500">From</span>
                                    <input type="date"
                                           name="from"
                                           id="regDateFilterFrom"
                                           value="{{ $from }}"
                                           onchange="onRegCustomDateChange()"
                                           class="px-2 py-1 bg-white border border-slate-200 rounded-lg text-[11px] font-mono-code text-slate-800 focus:border-emerald-600 focus:outline-none">
                                </label>
                                <label for="regDateFilterTo" class="inline-flex items-center gap-1.5">
                                    <span class="text-[10px] font-mono-code font-bold uppercase text-slate-500">To</span>
                                    <input type="date"
                                           name="to"
                                           id="regDateFilterTo"
                                           value="{{ $to }}"
                                           onchange="onRegCustomDateChange()"
                                           class="px-2 py-1 bg-white border border-slate-200 rounded-lg text-[11px] font-mono-code text-slate-800 focus:border-emerald-600 focus:outline-none">
                                </label>
                            </div>

                            <div class="flex items-center gap-2 flex-wrap">
                                <label for="dispositionFilterSelect" class="sr-only">Filter by subject disposition</label>
                                <select name="disposition"
                                        id="dispositionFilterSelect"
                                        class="px-2.5 py-1 bg-white border border-slate-200 rounded-lg text-[11px] font-semibold text-slate-700 focus:border-emerald-600 focus:outline-none">
                                    <option value="all" {{ $disposition === 'all' || $disposition === '' ? 'selected' : '' }}>All Dispositions</option>
                                    <option value="active" {{ $disposition === 'active' ? 'selected' : '' }}>Active Investigation</option>
                                    <option value="cleared" {{ $disposition === 'cleared' ? 'selected' : '' }}>Cleared</option>
                                    <option value="referred" {{ $disposition === 'referred' ? 'selected' : '' }}>Referred</option>
                                    <option value="released" {{ $disposition === 'released' ? 'selected' : '' }}>Released / Reclassified</option>
                                </select>

                                <label for="perPageSelect" class="sr-only">Rows per page</label>
                                <select name="per_page"
                                        id="perPageSelect"
                                        class="px-2.5 py-1 bg-white border border-slate-200 rounded-lg text-[11px] font-mono-code font-semibold text-slate-700 focus:border-emerald-600 focus:outline-none">
                                    @foreach([15, 25, 50] as $pp)
                                        <option value="{{ $pp }}" {{ (int) $perPage === $pp ? 'selected' : '' }}>{{ $pp }} / page</option>
                                    @endforeach
                                </select>

                                <button type="button"
                                        onclick="resetRegDateFilters()"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white hover:bg-rose-50 text-slate-600 hover:text-rose-700 border border-slate-200 hover:border-rose-200 text-[11px] font-mono-code font-bold transition-colors"
                                        title="Reset all filters">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    <span>Reset</span>
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- AJAX & SILENT-POLLED TABLE CONTAINER -->
                    <div id="digitalCasesTableContainer">
                        @include('Digital_Forensics.partials.cases_table')
                    </div>
                </section>

            </main>
        </div>
    </div>

    <!-- CUSTODIAL PORTRAIT LIGHTBOX MODAL -->
    <div id="mugshotModal"
         role="dialog"
         aria-modal="true"
         aria-labelledby="mugshotModalTitle"
         class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-500/50 backdrop-blur-xs transition-opacity duration-200"
         onclick="handleMugshotBackdropClick(event)">
        <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 max-w-sm w-full overflow-hidden" onclick="event.stopPropagation()">
            <div class="px-5 py-3.5 bg-slate-100 border-b border-slate-200 text-slate-900 flex items-center justify-between">
                <div>
                    <h2 id="mugshotModalTitle" class="font-mono-code font-bold text-xs uppercase tracking-wider text-slate-800">Custodial Front Portrait</h2>
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

    <script>
        const DPF_REGISTER_ENDPOINT = '{{ url('/Digital_Forensics/cases') }}';
        let currentRegPreset = @json((empty($from) && empty($to)) ? 'all' : 'custom');
        let lastDpfSignature = @json($initialSignature ?? null);
        let isPollingDpfCases = false;

        function renderDigitalCasesSkeleton(colCount = 6, rowCount = 6) {
            let html = '';
            for (let i = 0; i < rowCount; i++) {
                html += `<tr class="animate-pulse border-b border-slate-100">
                    <td class="py-4 px-5"><div class="h-3.5 bg-slate-200 rounded w-28"></div><div class="h-2.5 bg-slate-100 rounded w-20 mt-2"></div></td>
                    <td class="py-4 px-4"><div class="flex items-start gap-3"><div class="w-10 h-12 rounded-xl bg-slate-200 shrink-0"></div><div class="space-y-2 flex-1"><div class="h-3.5 bg-slate-200 rounded w-48"></div><div class="h-2.5 bg-slate-100 rounded w-32"></div></div></div></td>
                    <td class="py-4 px-4"><div class="h-4 bg-slate-200 rounded w-20"></div><div class="h-3 bg-slate-100 rounded w-36 mt-2"></div></td>
                    <td class="py-4 px-4"><div class="h-5 bg-slate-200 rounded-full w-32"></div><div class="h-2.5 bg-slate-100 rounded w-24 mt-2"></div></td>
                    <td class="py-4 px-4"><div class="h-5 bg-slate-200 rounded-md w-36"></div></td>
                    <td class="py-4 px-5 text-right"><div class="h-7 bg-slate-200 rounded-xl w-28 ml-auto"></div></td>
                </tr>`;
            }
            return html;
        }

        function syncDpfRegisterMetaFromDom(explicitStats = null) {
            const meta = document.getElementById('dpfRegisterTableMeta');
            const badge = document.getElementById('regRowCountBadge');
            if (meta && badge) {
                const first = meta.getAttribute('data-first-item') || '0';
                const last = meta.getAttribute('data-last-item') || '0';
                const total = meta.getAttribute('data-total-filtered') || '0';
                badge.textContent = Number(total) > 0 ? `${first}–${last} of ${total}` : '0 dockets';
            }

            const stats = explicitStats || (meta && meta.hasAttribute('data-stat-total') ? {
                total_cases: Number(meta.getAttribute('data-stat-total') || 0),
                total_devices: Number(meta.getAttribute('data-stat-devices') || 0),
                extracted_cases: Number(meta.getAttribute('data-stat-extracted') || 0),
                pending_extraction: Number(meta.getAttribute('data-stat-pending') || 0),
                court_order_cases: Number(meta.getAttribute('data-stat-warrant') || 0),
                multi_subject_cases: Number(meta.getAttribute('data-stat-multi') || 0),
                extraction_rate: Number(meta.getAttribute('data-stat-rate') || 0),
            } : null);

            if (!stats) return;

            const textMap = {
                kpiTotalCases: stats.total_cases,
                kpiMultiSubjectSub: stats.multi_subject_cases,
                kpiTotalDevices: stats.total_devices,
                kpiExtractedCases: stats.extracted_cases,
                kpiPendingExtraction: stats.pending_extraction,
                kpiWarrantSub: stats.court_order_cases,
                tabCountAll: stats.total_cases,
                tabCountPending: stats.pending_extraction,
                tabCountExtracted: stats.extracted_cases,
                tabCountWarrant: stats.court_order_cases,
                tabCountMulti: stats.multi_subject_cases,
            };
            Object.entries(textMap).forEach(([id, val]) => {
                const el = document.getElementById(id);
                if (el && val !== undefined) el.textContent = String(val);
            });

            const rateBadge = document.getElementById('kpiExtractionRateBadge');
            if (rateBadge && stats.extraction_rate !== undefined) {
                rateBadge.textContent = `${stats.extraction_rate}%`;
            }
            const bar = document.getElementById('kpiExtractionProgressBar');
            if (bar && stats.extraction_rate !== undefined) {
                bar.style.width = `${stats.extraction_rate}%`;
            }
        }

        function setupDigitalCasesAjaxTable() {
            if (typeof initAjaxTable !== 'function' || window.digitalCasesAjaxTable) return;

            window.digitalCasesAjaxTable = initAjaxTable({
                container: '#digitalCasesTableContainer',
                table: '#regMasterCaseTable',
                form: '#dpfFilterForm',
                endpoint: DPF_REGISTER_ENDPOINT,
                columns: 6,
                rows: 6,
                skeletonRenderer: renderDigitalCasesSkeleton,
                onAfterUpdate: () => {
                    syncDpfRegisterMetaFromDom();
                    const pipeVal = document.getElementById('pipelineFilterInput')?.value || 'all';
                    updatePipelineTabVisuals(pipeVal);
                    const inlineVal = document.getElementById('regSearchInput')?.value || '';
                    const topInput = document.getElementById('topGlobalSearchInput');
                    if (topInput && topInput.value !== inlineVal) topInput.value = inlineVal;
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupDigitalCasesAjaxTable);
        } else {
            setupDigitalCasesAjaxTable();
        }

        function syncSearchFields(val, fromInline = false) {
            const topInput = document.getElementById('topGlobalSearchInput');
            const inlineInput = document.getElementById('regSearchInput');
            if (fromInline) {
                if (topInput && topInput.value !== val) topInput.value = val;
            } else {
                if (inlineInput && inlineInput.value !== val) {
                    inlineInput.value = val;
                    inlineInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }
        }

        // Global Ctrl+K / Cmd+K shortcut to focus search
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                const target = document.getElementById('topGlobalSearchInput') || document.getElementById('regSearchInput');
                if (target && target.offsetParent !== null) {
                    e.preventDefault();
                    target.focus();
                    target.select();
                } else {
                    const fallback = document.getElementById('regSearchInput');
                    if (fallback) {
                        e.preventDefault();
                        fallback.focus();
                        fallback.select();
                    }
                }
            }
            if (e.key === 'Escape') {
                closeMugshotModal();
            }
        });

        function updatePipelineTabVisuals(activeKey) {
            document.querySelectorAll('[data-pipeline-tab]').forEach(btn => {
                const key = btn.getAttribute('data-pipeline-tab');
                const isActive = key === activeKey;
                btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
                btn.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all ' + (
                    isActive
                        ? 'bg-slate-200/95 text-slate-900 border-slate-300 shadow-2xs'
                        : 'bg-slate-100/80 text-slate-600 border-transparent hover:bg-slate-200/60 hover:text-slate-900'
                );
                const countSpan = btn.querySelector('span[id^="tabCount"]');
                if (countSpan) {
                    countSpan.className = 'px-1.5 py-0.2 rounded-md text-[10px] font-mono-code font-extrabold tnum ' + (
                        isActive
                            ? 'bg-emerald-600 text-white'
                            : 'bg-white text-slate-700 border border-slate-200/80'
                    );
                }
            });
        }

        function setPipelineFilter(pipelineKey) {
            const input = document.getElementById('pipelineFilterInput');
            if (input) input.value = pipelineKey;
            updatePipelineTabVisuals(pipelineKey);
            if (window.digitalCasesAjaxTable) {
                window.digitalCasesAjaxTable.applyFilters({ pipeline: pipelineKey === 'all' ? null : pipelineKey, page: null });
            }
        }

        function getRegReferenceDate() {
            const rows = document.querySelectorAll('#regMasterCaseTable tbody tr.case-register-row');
            let maxDateStr = null;
            rows.forEach(r => {
                const d = r.getAttribute('data-date');
                if (d && (!maxDateStr || d > maxDateStr)) {
                    maxDateStr = d;
                }
            });

            const now = new Date();
            if (maxDateStr) {
                const dataYear = parseInt(maxDateStr.slice(0, 4), 10);
                if (Math.abs(now.getFullYear() - dataYear) >= 1) {
                    const parts = maxDateStr.split('-');
                    if (parts.length === 3) {
                        return new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
                    }
                }
            }
            return now;
        }

        function updateRegPresetButtonsVisual(activePreset) {
            const presets = ['all', 'weekly', 'monthly', 'annually'];
            presets.forEach(p => {
                const btn = document.getElementById('regBtnPreset' + p.charAt(0).toUpperCase() + p.slice(1));
                if (!btn) return;
                btn.className = 'px-2.5 py-1 rounded-lg text-[11px] font-mono-code font-bold transition-colors ' + (
                    p === activePreset
                        ? 'bg-emerald-600 text-white shadow-2xs'
                        : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200'
                );
            });
        }

        function setRegDateFilterPreset(preset) {
            currentRegPreset = preset;
            updateRegPresetButtonsVisual(preset);

            const fromInput = document.getElementById('regDateFilterFrom');
            const toInput = document.getElementById('regDateFilterTo');

            const refDate = getRegReferenceDate();
            const pad = n => String(n).padStart(2, '0');
            const formatDate = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

            if (preset === 'all') {
                if (fromInput) fromInput.value = '';
                if (toInput) toInput.value = '';
            } else if (preset === 'weekly') {
                const day = refDate.getDay();
                const diffToMon = (day === 0 ? -6 : 1) - day;
                const monday = new Date(refDate);
                monday.setDate(refDate.getDate() + diffToMon);
                const sunday = new Date(monday);
                sunday.setDate(monday.getDate() + 6);
                if (fromInput) fromInput.value = formatDate(monday);
                if (toInput) toInput.value = formatDate(sunday);
            } else if (preset === 'monthly') {
                const firstDay = new Date(refDate.getFullYear(), refDate.getMonth(), 1);
                const lastDay = new Date(refDate.getFullYear(), refDate.getMonth() + 1, 0);
                if (fromInput) fromInput.value = formatDate(firstDay);
                if (toInput) toInput.value = formatDate(lastDay);
            } else if (preset === 'annually') {
                const firstDay = new Date(refDate.getFullYear(), 0, 1);
                const lastDay = new Date(refDate.getFullYear(), 11, 31);
                if (fromInput) fromInput.value = formatDate(firstDay);
                if (toInput) toInput.value = formatDate(lastDay);
            }

            if (window.digitalCasesAjaxTable) {
                window.digitalCasesAjaxTable.applyFilters({
                    from: fromInput?.value || null,
                    to: toInput?.value || null,
                    page: null
                });
            }
        }

        function onRegCustomDateChange() {
            currentRegPreset = 'custom';
            updateRegPresetButtonsVisual('custom');
        }

        function resetRegDateFilters() {
            const topSearch = document.getElementById('topGlobalSearchInput');
            const inlineSearch = document.getElementById('regSearchInput');
            const fromInput = document.getElementById('regDateFilterFrom');
            const toInput = document.getElementById('regDateFilterTo');
            const pipeInput = document.getElementById('pipelineFilterInput');
            const dispSelect = document.getElementById('dispositionFilterSelect');
            const perPageSelect = document.getElementById('perPageSelect');

            if (topSearch) topSearch.value = '';
            if (inlineSearch) inlineSearch.value = '';
            if (fromInput) fromInput.value = '';
            if (toInput) toInput.value = '';
            if (pipeInput) pipeInput.value = 'all';
            if (dispSelect) dispSelect.value = 'all';
            if (perPageSelect) perPageSelect.value = '15';

            currentRegPreset = 'all';
            updateRegPresetButtonsVisual('all');
            updatePipelineTabVisuals('all');

            if (window.digitalCasesAjaxTable) {
                window.digitalCasesAjaxTable.fetchData(DPF_REGISTER_ENDPOINT, true);
            }
        }

        function generateRegCaseFilter() {
            const visibleRows = Array.from(document.querySelectorAll('#regMasterCaseTable tbody tr.case-register-row'))
                .filter(r => r.style.display !== 'none');

            const caseList = [];
            visibleRows.forEach(r => {
                const cRef = (r.getAttribute('data-caseref') || '').trim();
                if (cRef && !caseList.includes(cRef)) {
                    caseList.push(cRef);
                }
            });

            if (caseList.length === 0) {
                if (typeof window.showFlashToast === 'function') {
                    window.showFlashToast('No digital forensic dockets are currently listed to export.', 'warning', 'Export Unavailable');
                }
                return;
            }

            const params = new URLSearchParams();
            params.set('cases', caseList.join(','));
            params.set('print', '1');

            const fromVal = (document.getElementById('regDateFilterFrom')?.value || '').trim();
            const toVal = (document.getElementById('regDateFilterTo')?.value || '').trim();
            if (fromVal) params.set('from', fromVal);
            if (toVal) params.set('to', toVal);

            let periodParam = 'general';
            if (currentRegPreset === 'weekly') periodParam = 'weekly';
            else if (currentRegPreset === 'monthly') periodParam = 'monthly';
            else if (currentRegPreset === 'annually') periodParam = 'annual';
            else if (fromVal || toVal || currentRegPreset === 'custom') periodParam = 'date_range';
            params.set('period', periodParam);

            window.open('{{ url('/Digital_Forensics/multi_dpf_pdf') }}?' + params.toString(), '_blank', 'noopener');
        }

        function copyCaseNumber(caseNumber) {
            if (!caseNumber || !navigator.clipboard) return;
            navigator.clipboard.writeText(caseNumber).then(() => {
                if (typeof window.showFlashToast === 'function') {
                    window.showFlashToast(`Copied docket reference ${caseNumber} to clipboard.`, 'success', 'Reference Copied');
                }
            }).catch(() => {});
        }

        // Custodial Portrait Lightbox Modal
        function openMugshotModal(imgUrl, suspectName, caseRef = '') {
            if (!imgUrl) return;
            const modal = document.getElementById('mugshotModal');
            const img = document.getElementById('modalMugshotImg');
            const nameEl = document.getElementById('modalSuspectName');
            const caseEl = document.getElementById('modalCaseRefLabel');
            if (img) img.src = imgUrl;
            if (nameEl) nameEl.textContent = suspectName || 'Subject';
            if (caseEl) caseEl.textContent = caseRef ? `Docket: ${caseRef}` : '';
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

        function handleMugshotBackdropClick(e) {
            if (e.target && e.target.id === 'mugshotModal') {
                closeMugshotModal();
            }
        }

        /* ── Silent 5-Second Background Polling Engine ── */
        async function pollDigitalForensicsRegister(force = false) {
            if (isPollingDpfCases || (!force && document.hidden)) return;
            if (window.digitalCasesAjaxTable && (window.digitalCasesAjaxTable.isLoading || window.digitalCasesAjaxTable.debounceTimer)) return;

            isPollingDpfCases = true;
            const controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
            const timeoutId = controller ? setTimeout(() => controller.abort(), 6000) : null;

            try {
                const params = new URLSearchParams(window.location.search);
                params.set('poll', '1');

                const res = await fetch(`${DPF_REGISTER_ENDPOINT}?${params.toString()}`, {
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
                if (window.digitalCasesAjaxTable && window.digitalCasesAjaxTable.isLoading) return;

                if (data.stats) {
                    syncDpfRegisterMetaFromDom(data.stats);
                }

                if (!force && data.signature && data.signature === lastDpfSignature) {
                    return;
                }
                if (data.signature) {
                    lastDpfSignature = data.signature;
                }

                const container = document.getElementById('digitalCasesTableContainer');
                if (container && typeof data.html === 'string') {
                    const prevScroll = container.querySelector('.overflow-x-auto')?.scrollLeft || 0;
                    container.innerHTML = data.html;
                    if (prevScroll > 0) {
                        const nextWrap = container.querySelector('.overflow-x-auto');
                        if (nextWrap) nextWrap.scrollLeft = prevScroll;
                    }
                    syncDpfRegisterMetaFromDom(data.stats || null);
                }
            } catch (e) {
                // Silent catch during background polling
            } finally {
                if (timeoutId) clearTimeout(timeoutId);
                isPollingDpfCases = false;
            }
        }

        setInterval(() => pollDigitalForensicsRegister(false), 5000);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) pollDigitalForensicsRegister(false);
        });
        window.addEventListener('focus', () => pollDigitalForensicsRegister(false));
        window.addEventListener('storage', (e) => {
            if (e.key === 'nacoc_badge_sync') pollDigitalForensicsRegister(true);
        });
    </script>
</body>
</html>
