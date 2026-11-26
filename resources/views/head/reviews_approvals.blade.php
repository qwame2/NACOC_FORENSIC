@php
    $authUser = auth()->user();
    $unitKey = $unitContext['key'] ?? 'crime_scene_support';
    $unitLabel = $unitContext['label'] ?? 'Crime Scene Support Unit';
    $dashboardBackUrl = match ($unitKey) {
        'lab_analysis' => url('/Lab_Analysis'),
        'evidence_management' => url('/Evidence_Management'),
        'digital_forensics' => url('/Digital_Forensics'),
        'forensic_head' => url('/Forensic_Head'),
        default => url('/Crime_Scene_Support/head'),
    };
    $reviewUrlFor = function ($id) use ($unitKey) {
        return match ($unitKey) {
            'lab_analysis' => url('/Lab_Analysis/reviews-approvals/' . $id),
            'evidence_management' => url('/Evidence_Management/reviews-approvals/' . $id),
            'digital_forensics' => url('/Digital_Forensics/reviews-approvals/' . $id),
            'forensic_head' => url('/Forensic_Head/reviews-approvals/' . $id),
            default => url('/Crime_Scene_Support/reviews-approvals/' . $id),
        };
    };

    $photoResolver = app(\App\Services\CasePhotoResolver::class);

    $urgentPendingCount = $pendingSubmissions->filter(function ($s) {
        return in_array(strtoupper((string) $s->priority), ['HIGH', 'CRITICAL'], true);
    })->count();
    $awaitingReviewCount = $pendingSubmissions->where('status', 'Pending Approval')->count();
    $rolledBackCount = $pendingSubmissions->where('status', 'Rolled Back')->count();
    $approvedHistoryCount = $reviewedSubmissions->where('status', 'Approved')->count();
    $editedPendingCount = $pendingSubmissions->filter(function ($s) {
        return !empty($s->payload['is_edit_submission']) || (($s->payload['submission_type'] ?? '') === 'edit');
    })->count();

    $extractSubmissionMeta = function ($sub) use ($photoResolver) {
        if (method_exists($sub, 'getSubmissionMeta')) {
            return $sub->getSubmissionMeta();
        }
        $p = is_array($sub->payload) ? $sub->payload : [];
        $docket = is_array($p['docket_data'] ?? null) ? $p['docket_data'] : [];

        $suspectsList = $p['suspects'] ?? ($docket['suspects'] ?? []);
        $suspectCount = (is_array($suspectsList) && count($suspectsList) > 0)
            ? count($suspectsList)
            : (!empty($sub->suspect_name) ? 1 : 0);

        $seizuresList = $p['seizures'] ?? ($docket['seizures'] ?? ($p['exhibits'] ?? []));
        $exhibitCount = is_array($seizuresList) ? count($seizuresList) : 0;

        $offense = trim((string) ($p['offense_type'] ?? ($p['offense'] ?? ($p['operation_type'] ?? ''))));
        $location = trim((string) ($p['location'] ?? ($p['raid_location'] ?? ($p['arrest_location'] ?? ''))));
        $caseRef = trim((string) ($sub->case_number_preview ?? ($p['case_number'] ?? '')));

        $rawFront = $p['mugshot_front']
            ?? ($p['mugshot'] ?? null)
            ?? ($p['mugshots']['front'] ?? null)
            ?? ($p['photo'] ?? null)
            ?? ($p['suspect_photo'] ?? null)
            ?? ($suspectsList[0]['mugshot_front'] ?? null)
            ?? ($suspectsList[0]['mugshot'] ?? null)
            ?? ($suspectsList[0]['mugshots']['front'] ?? null)
            ?? ($suspectsList[0]['photo'] ?? null)
            ?? ($suspectsList[0]['suspect_photo'] ?? null)
            ?? ($docket['mugshot_front'] ?? null)
            ?? ($docket['mugshots']['front'] ?? null);

        if (empty($rawFront) && !empty($sub->approved_case_number)) {
            try {
                $approvedRecord = \App\Models\SingleSuspectCase::where('case_number', $sub->approved_case_number)->first()
                    ?? \App\Models\MultipleSuspectCase::where('case_number', $sub->approved_case_number)->first();
                if ($approvedRecord) {
                    if ($approvedRecord instanceof \App\Models\SingleSuspectCase) {
                        $rawFront = $approvedRecord->mugshot_front;
                    } elseif (isset($approvedRecord->suspects) && count($approvedRecord->suspects) > 0) {
                        $rawFront = $approvedRecord->suspects[0]->mugshot_front ?? null;
                    }
                }
                if (empty($rawFront)) {
                    $fCase = \App\Models\ForensicCase::where('case_number', $sub->approved_case_number)->first();
                    if ($fCase) {
                        $rawFront = $fCase->mugshot_front
                            ?? ($fCase->suspects[0]->mugshot_front ?? null)
                            ?? ($fCase->suspects[0]['mugshot_front'] ?? null);
                    }
                }
            } catch (\Throwable) {}
        }

        $mugshotUrl = $photoResolver->formatMugshotUrl($rawFront);

        return [
            'suspect_count' => $suspectCount,
            'exhibit_count' => $exhibitCount,
            'offense'       => $offense,
            'location'      => $location,
            'case_ref'      => $caseRef,
            'mugshot_url'   => $mugshotUrl,
            'suspects'      => [],
            'is_multiple_suspects' => false,
        ];
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reviews &amp; Approvals | {{ $unitLabel }} | NACOC Forensic</title>

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
        .filter-underline-tab {
            position: relative;
            padding: 10px 4px;
            font-size: 12px;
            font-weight: 600;
            color: #64748B;
            background: transparent;
            border: none;
            border-bottom: 2px solid transparent;
            transition: color 0.16s ease, border-color 0.16s ease;
            white-space: nowrap;
        }
        .filter-underline-tab:hover {
            color: #0F172A;
        }
        .filter-underline-tab.is-active {
            color: #059669;
            font-weight: 700;
            border-bottom-color: #059669;
        }
        .submission-card {
            background: #FFFFFF;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 4px 12px -4px rgba(15, 23, 42, 0.05);
            transition: box-shadow 0.18s ease, transform 0.18s ease;
        }
        .submission-card:hover {
            box-shadow: 0 12px 26px -10px rgba(15, 23, 42, 0.12);
            transform: translateY(-1px);
        }
        .metric-action-btn {
            transition: background-color 0.16s ease;
        }
        .metric-action-btn:hover {
            background-color: #F8FAFC;
        }
        a:focus-visible, button:focus-visible, input:focus-visible {
            outline: 2px solid #059669;
            outline-offset: 2px;
        }
    </style>
</head>
<body class="min-h-screen bg-[#F6F7F9] text-slate-900 antialiased selection:bg-emerald-700 selection:text-white">
    @include('partials.flash')

    <div class="flex h-screen overflow-hidden">
        @include('Crime_Scene_Support.partials.sidebar')

        <div class="flex-1 flex flex-col min-w-0 bg-[#F6F7F9] overflow-hidden">
            <!-- TOPBAR -->
            <header class="h-16 bg-white shadow-2xs px-4 sm:px-6 flex items-center justify-between gap-4 shrink-0 z-20">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50/70 text-emerald-700 flex items-center justify-center shrink-0" aria-hidden="true">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-sm font-extrabold text-slate-900 tracking-tight truncate">Reviews &amp; Approvals</h1>
                        <p class="text-[11px] text-slate-500 font-medium truncate">{{ $unitLabel }} Authorization Workspace</p>
                    </div>
                </div>

                <!-- LIVE SEARCH & BACK NAVIGATION -->
                <div class="flex items-center gap-3 shrink-0">
                    <div class="relative hidden md:block w-72">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 pointer-events-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </span>
                        <input
                            type="text"
                            id="queueSearchInput"
                            placeholder="Search suspect, SUB #, officer, docket..."
                            aria-label="Search submissions"
                            class="w-full pl-9 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-emerald-600 focus:outline-none transition-colors"
                        >
                        <button
                            type="button"
                            id="queueSearchClear"
                            onclick="clearQueueSearch()"
                            aria-label="Clear search"
                            class="hidden absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-700"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <a href="{{ $dashboardBackUrl }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 hover:border-slate-300 text-xs font-bold rounded-xl transition-colors text-decoration-none">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span>Back to Dashboard</span>
                    </a>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto px-4 sm:px-6 py-6 space-y-6">
                @include('partials.flash')

                <!-- COMMAND OVERVIEW BANNER + INTEGRATED METRIC STRIP -->
                <section class="bg-white rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-6 py-6 sm:px-7 sm:py-6">
                        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                            <div class="max-w-2xl space-y-1.5">
                                <div class="text-[11px] font-mono-code font-bold uppercase tracking-widest text-emerald-700">
                                    Pre-Database Staging Queue
                                </div>
                                <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900">
                                    Staff Case Submissions Awaiting Authorization
                                </h2>
                                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                    Verify suspect records, mugshots, and exhibits before committing cases to the NACOC registry — or roll back for correction.
                                </p>
                            </div>

                            <div class="hidden lg:block text-right shrink-0 pl-6">
                                <div class="text-[10px] font-mono-code font-bold uppercase tracking-wider text-slate-400">Reviewing Authority</div>
                                <div class="text-sm font-extrabold text-slate-900 mt-0.5">{{ $authUser->fullname ?? $authUser->name ?? 'Unit Head' }}</div>
                                <div class="text-xs font-semibold text-emerald-700 mt-0.5">{{ $unitLabel }}</div>
                            </div>

                            <!-- MOBILE SEARCH FALLBACK -->
                            <div class="md:hidden w-full">
                                <input
                                    type="text"
                                    id="queueSearchInputMobile"
                                    placeholder="Search suspect, SUB #, officer, docket..."
                                    aria-label="Search submissions on mobile"
                                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-emerald-600 focus:outline-none"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- 4-COLUMN ARCHITECTURAL KPI SUMMARY BAR (CLEAN UNBOXED TYPOGRAPHY) -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 bg-slate-50/50">
                        <button type="button" onclick="focusPendingFilter('PENDING')" class="metric-action-btn p-5 text-left flex items-start justify-between gap-3">
                            <div>
                                <div class="text-[11px] font-mono-code font-bold uppercase tracking-wider text-slate-500">Pending Review</div>
                                <div id="kpiPendingReviewCount" class="text-2xl font-extrabold text-slate-900 tnum mt-1">{{ $awaitingReviewCount }}</div>
                                <div class="text-[11px] font-medium text-emerald-700 mt-1">Staged for sign-off</div>
                            </div>
                            <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </button>

                        <button type="button" onclick="focusPendingFilter('ROLLED_BACK')" class="metric-action-btn p-5 text-left flex items-start justify-between gap-3">
                            <div>
                                <div class="text-[11px] font-mono-code font-bold uppercase tracking-wider text-slate-500">Awaiting Corrections</div>
                                <div id="kpiRolledBackCount" class="text-2xl font-extrabold {{ $rolledBackCount > 0 ? 'text-amber-700' : 'text-slate-900' }} tnum mt-1">{{ $rolledBackCount }}</div>
                                <div class="text-[11px] font-medium text-slate-500 mt-1">Returned to staff</div>
                            </div>
                            <svg id="kpiRolledBackIcon" class="w-5 h-5 {{ $rolledBackCount > 0 ? 'text-amber-600' : 'text-slate-400' }} shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                            </svg>
                        </button>

                        <button type="button" onclick="focusPendingFilter('URGENT')" class="metric-action-btn p-5 text-left flex items-start justify-between gap-3">
                            <div>
                                <div class="text-[11px] font-mono-code font-bold uppercase tracking-wider text-slate-500">High Priority</div>
                                <div id="kpiUrgentPendingCount" class="text-2xl font-extrabold {{ $urgentPendingCount > 0 ? 'text-red-600' : 'text-slate-900' }} tnum mt-1">{{ $urgentPendingCount }}</div>
                                <div class="text-[11px] font-medium {{ $urgentPendingCount > 0 ? 'text-red-600' : 'text-slate-500' }} mt-1">Priority docket attention</div>
                            </div>
                            <svg id="kpiUrgentPendingIcon" class="w-5 h-5 {{ $urgentPendingCount > 0 ? 'text-red-500' : 'text-slate-400' }} shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </button>

                        <button type="button" onclick="focusHistoryFilter('ALL')" class="metric-action-btn p-5 text-left flex items-start justify-between gap-3">
                            <div>
                                <div class="text-[11px] font-mono-code font-bold uppercase tracking-wider text-slate-500">Approved &amp; Saved</div>
                                <div id="kpiApprovedHistoryCount" class="text-2xl font-extrabold text-emerald-700 tnum mt-1">{{ $approvedHistoryCount }}</div>
                                <div class="text-[11px] font-medium text-slate-500 mt-1">Committed to case registry</div>
                            </div>
                            <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </button>
                    </div>
                </section>

                <!-- PENDING SUBMISSIONS QUEUE -->
                <section id="pendingQueueSection" class="space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 px-1">
                        <div class="pb-2.5 flex items-baseline gap-2.5">
                            <h3 class="text-base font-extrabold text-slate-900 tracking-tight">
                                Pending Staff Submissions
                            </h3>
                            <span id="pendingVisibleCount" class="text-xs font-mono-code font-bold text-slate-500 tnum">
                                {{ $pendingSubmissions->count() }} total
                            </span>
                        </div>

                        <div class="flex items-center gap-5 overflow-x-auto" role="tablist" aria-label="Filter pending queue by status and priority">
                            <button type="button" class="filter-underline-tab is-active" data-pending-tab="ALL" onclick="setPendingFilter('ALL', this)">
                                All Submissions (<span id="tabCountAll">{{ $pendingSubmissions->count() }}</span>)
                            </button>
                            <button type="button" class="filter-underline-tab" data-pending-tab="PENDING" onclick="setPendingFilter('PENDING', this)">
                                Pending Review (<span id="tabCountPending">{{ $awaitingReviewCount }}</span>)
                            </button>
                            <button type="button" class="filter-underline-tab {{ $editedPendingCount > 0 ? '' : 'hidden' }}" id="tabEditedWrapper" data-pending-tab="EDITED" onclick="setPendingFilter('EDITED', this)">
                                Edited Cases (<span id="tabCountEdited">{{ $editedPendingCount }}</span>)
                            </button>
                            <button type="button" class="filter-underline-tab {{ $rolledBackCount > 0 ? '' : 'hidden' }}" id="tabRolledBackWrapper" data-pending-tab="ROLLED_BACK" onclick="setPendingFilter('ROLLED_BACK', this)">
                                Awaiting Corrections (<span id="tabCountRolledBack">{{ $rolledBackCount }}</span>)
                            </button>
                            <button type="button" class="filter-underline-tab" data-pending-tab="URGENT" onclick="setPendingFilter('URGENT', this)">
                                High Priority (<span id="tabCountUrgent">{{ $urgentPendingCount }}</span>)
                            </button>
                        </div>
                    </div>

                    <!-- PENDING SUBMISSIONS FILTER CONSOLE -->
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-3 sm:p-3.5 shadow-2xs flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                        <!-- SEARCH INPUT -->
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input
                                type="text"
                                id="pendingSearchInput"
                                placeholder="Search pending submissions (suspect, title, officer, SUB #)..."
                                aria-label="Search pending submissions"
                                oninput="onPendingSearchInput(this.value)"
                                class="w-full pl-9 pr-8 py-2 bg-slate-50/70 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-emerald-600 focus:outline-none transition-colors"
                            >
                            <button
                                type="button"
                                id="pendingSearchClear"
                                onclick="clearPendingSearch()"
                                aria-label="Clear pending search"
                                class="hidden absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-700"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- DATE RANGE FILTER (FROM & TO) -->
                        <div class="flex items-center gap-1.5 bg-slate-50/70 border border-slate-200 rounded-xl px-3 py-1.5 shrink-0">
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <div class="flex items-center gap-1">
                                <label for="pendingDateFrom" class="text-[10px] font-bold text-slate-500 uppercase font-mono-code">From:</label>
                                <input
                                    type="date"
                                    id="pendingDateFrom"
                                    onchange="onPendingDateChange()"
                                    class="text-xs text-slate-800 bg-transparent border-none focus:outline-none focus:ring-0 p-0 font-mono-code cursor-pointer"
                                    title="Filter pending submissions from date"
                                >
                            </div>
                            <span class="text-slate-300 mx-0.5">&ndash;</span>
                            <div class="flex items-center gap-1">
                                <label for="pendingDateTo" class="text-[10px] font-bold text-slate-500 uppercase font-mono-code">To:</label>
                                <input
                                    type="date"
                                    id="pendingDateTo"
                                    onchange="onPendingDateChange()"
                                    class="text-xs text-slate-800 bg-transparent border-none focus:outline-none focus:ring-0 p-0 font-mono-code cursor-pointer"
                                    title="Filter pending submissions to date"
                                >
                            </div>
                            <button
                                type="button"
                                onclick="clearPendingDateFilter()"
                                title="Reset date filter"
                                id="clearPendingDateBtn"
                                class="hidden ml-1 text-[11px] font-bold text-slate-400 hover:text-rose-600 transition-colors p-0.5"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div id="pendingQueueContainer">
                        @include('head.partials.pending_queue')
                    </div>

                    <!-- PENDING PAGINATION FOOTER -->
                    <div id="pendingPaginationBar" class="bg-white border border-slate-200/80 rounded-2xl px-5 py-3 shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-3 text-slate-500 font-mono-code text-[11px]">
                            <span>Showing <strong id="pendingPageStart" class="text-slate-800">1</strong> to <strong id="pendingPageEnd" class="text-slate-800">5</strong> of <strong id="pendingPageTotal" class="text-slate-800">0</strong></span>
                            <span class="text-slate-300">|</span>
                            <div class="flex items-center gap-1.5">
                                <span class="text-slate-400">Per page:</span>
                                <select id="pendingPerPageSelect" onchange="changePendingPerPage(this.value)" class="bg-slate-50 border border-slate-200 rounded-lg text-xs py-0.5 px-2 font-mono-code text-slate-700 focus:outline-none focus:border-emerald-600">
                                    <option value="5" selected>5</option>
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                </select>
                            </div>
                        </div>
                        <div id="pendingPaginationControls" class="flex items-center gap-1"></div>
                    </div>
                </section>

                <!-- REVIEWED HISTORY LEDGER -->
                <section id="reviewedHistorySection" class="space-y-3 pt-2">
                    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 px-1">
                        <div class="pb-2.5 flex items-baseline gap-2.5">
                            <h3 class="text-base font-extrabold text-slate-900 tracking-tight">
                                Decision History
                            </h3>
                            <span id="historyVisibleCount" class="text-xs font-mono-code font-bold text-slate-500 tnum">
                                {{ $reviewedSubmissions->count() }} records
                            </span>
                        </div>

                        <div class="flex items-center gap-5 overflow-x-auto" role="tablist" aria-label="Filter history by decision">
                            <button type="button" class="filter-underline-tab is-active" data-history-tab="ALL" onclick="setHistoryFilter('ALL', this)">
                                Approved Cases ({{ $approvedHistoryCount }})
                            </button>
                        </div>
                    </div>

                    <!-- DECISION HISTORY FILTER CONSOLE -->
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-3 sm:p-3.5 shadow-2xs flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                        <!-- SEARCH INPUT -->
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input
                                type="text"
                                id="historySearchInput"
                                placeholder="Search decision history (suspect, case #, reviewer, SUB #)..."
                                aria-label="Search decision history"
                                oninput="onHistorySearchInput(this.value)"
                                class="w-full pl-9 pr-8 py-2 bg-slate-50/70 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-emerald-600 focus:outline-none transition-colors"
                            >
                            <button
                                type="button"
                                id="historySearchClear"
                                onclick="clearHistorySearch()"
                                aria-label="Clear history search"
                                class="hidden absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-700"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- DATE RANGE FILTER (FROM & TO) -->
                        <div class="flex items-center gap-1.5 bg-slate-50/70 border border-slate-200 rounded-xl px-3 py-1.5 shrink-0">
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <div class="flex items-center gap-1">
                                <label for="historyDateFrom" class="text-[10px] font-bold text-slate-500 uppercase font-mono-code">From:</label>
                                <input
                                    type="date"
                                    id="historyDateFrom"
                                    onchange="onHistoryDateChange()"
                                    class="text-xs text-slate-800 bg-transparent border-none focus:outline-none focus:ring-0 p-0 font-mono-code cursor-pointer"
                                    title="Filter decision history from date"
                                >
                            </div>
                            <span class="text-slate-300 mx-0.5">&ndash;</span>
                            <div class="flex items-center gap-1">
                                <label for="historyDateTo" class="text-[10px] font-bold text-slate-500 uppercase font-mono-code">To:</label>
                                <input
                                    type="date"
                                    id="historyDateTo"
                                    onchange="onHistoryDateChange()"
                                    class="text-xs text-slate-800 bg-transparent border-none focus:outline-none focus:ring-0 p-0 font-mono-code cursor-pointer"
                                    title="Filter decision history to date"
                                >
                            </div>
                            <button
                                type="button"
                                onclick="clearHistoryDateFilter()"
                                title="Reset date filter"
                                id="clearHistoryDateBtn"
                                class="hidden ml-1 text-[11px] font-bold text-slate-400 hover:text-rose-600 transition-colors p-0.5"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                        @if($reviewedSubmissions->isEmpty())
                            <div class="p-10 text-center text-slate-500 text-xs">
                                No approved submissions have been recorded yet for {{ $unitLabel }}.
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead class="bg-slate-50/80 text-[10px] font-mono-code font-bold uppercase tracking-wider text-slate-500">
                                        <tr>
                                            <th class="py-3.5 px-5">Submission</th>
                                            <th class="py-3.5 px-5">Suspect &amp; Case Title</th>
                                            <th class="py-3.5 px-5">Submitted By</th>
                                            <th class="py-3.5 px-5">Decision</th>
                                            <th class="py-3.5 px-5">Saved Case #</th>
                                            <th class="py-3.5 px-5">Reviewed By</th>
                                            <th class="py-3.5 px-5 text-right">Dossier</th>
                                        </tr>
                                    </thead>
                                    <tbody id="historyTableBody">
                                        @foreach($reviewedSubmissions as $rev)
                                            @php
                                                $revUrl = $reviewUrlFor($rev->id);
                                                $revCode = 'SUB-' . str_pad((string) $rev->id, 4, '0', STR_PAD_LEFT);
                                                $corrCount = is_array($rev->rollback_corrections) ? count($rev->rollback_corrections) : 0;
                                                $revMeta = $extractSubmissionMeta($rev);
                                                $revIsEdit = (!empty($rev->payload['is_edit_submission']) || (!empty($rev->payload['submission_type']) && $rev->payload['submission_type'] === 'edit'));
                                                $revSearch = strtolower(implode(' ', array_filter([
                                                    $revCode,
                                                    $rev->suspect_name,
                                                    $rev->title,
                                                    $rev->submitted_by_name,
                                                    $rev->status,
                                                    $rev->approved_case_number,
                                                    $rev->reviewed_by_name,
                                                    $revIsEdit ? 'edited case edit' : '',
                                                ])));
                                            @endphp
                                            <tr
                                                class="even:bg-slate-50/40 hover:bg-slate-100/60 transition-colors history-row"
                                                data-status="{{ $rev->status }}"
                                                data-date="{{ optional($rev->reviewed_at ?? $rev->created_at)->format('Y-m-d') }}"
                                                data-search="{{ $revSearch }}"
                                            >
                                                <td class="py-4 px-5 whitespace-nowrap">
                                                    <a href="{{ $revUrl }}" class="font-mono-code font-bold text-emerald-700 hover:underline text-decoration-none">
                                                        {{ $revCode }}
                                                    </a>
                                                    <div class="text-[10px] font-mono-code text-slate-400 mt-0.5">
                                                        {{ optional($rev->created_at)->format('d M Y') }}
                                                    </div>
                                                </td>

                                                <td class="py-4 px-5 min-w-[220px]">
                                                    <div class="flex items-center gap-3">
                                                        <a href="{{ $revUrl }}" class="shrink-0 text-decoration-none group focus:outline-none" title="{{ $rev->suspect_name ?: 'Suspect Front Image' }}">
                                                            @if(!empty($revMeta['mugshot_url']))
                                                                <img
                                                                    src="{{ $revMeta['mugshot_url'] }}"
                                                                    alt="{{ $rev->suspect_name ?: 'Suspect Front Image' }}"
                                                                    class="w-10 h-10 rounded-lg object-cover border border-slate-200 shrink-0 bg-slate-100 shadow-2xs group-hover:ring-2 group-hover:ring-emerald-500/40 transition-all"
                                                                    loading="lazy"
                                                                    onerror="this.onerror=null; this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';"
                                                                >
                                                                <div class="w-10 h-10 rounded-lg bg-slate-100 border border-slate-200 text-slate-400 items-center justify-center shrink-0" style="display: none;" aria-hidden="true">
                                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                                    </svg>
                                                                </div>
                                                            @else
                                                                <div class="w-10 h-10 rounded-lg bg-slate-100 border border-slate-200 text-slate-400 flex items-center justify-center shrink-0" aria-hidden="true">
                                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                                    </svg>
                                                                </div>
                                                            @endif
                                                        </a>
                                                        <div class="min-w-0">
                                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                                <a href="{{ $revUrl }}" class="font-bold text-slate-900 hover:text-emerald-700 transition-colors block text-decoration-none truncate">
                                                                    {{ $rev->suspect_name ?: 'N/A' }}
                                                                </a>
                                                                @if($revIsEdit)
                                                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-mono-code font-bold bg-amber-50 text-amber-900 border border-amber-300">
                                                                        Edited Case
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            <div class="text-[11px] text-slate-500 mt-0.5 line-clamp-1">{{ $rev->title }}</div>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td class="py-4 px-5">
                                                    <div class="font-semibold text-slate-800">{{ $rev->submitted_by_name ?: 'Staff Officer' }}</div>
                                                    <div class="text-[11px] text-slate-400">{{ $rev->unit_name }}</div>
                                                </td>

                                                <td class="py-4 px-5 whitespace-nowrap">
                                                    @if($rev->status === 'Approved')
                                                        <span class="inline-flex items-center gap-1.5 font-bold text-emerald-700 text-xs">
                                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                            </svg>
                                                            Approved
                                                        </span>
                                                    @elseif($rev->status === 'Rolled Back')
                                                        <span class="inline-flex items-center gap-1.5 font-bold text-amber-700 text-xs">
                                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                                            </svg>
                                                            Rolled Back
                                                        </span>
                                                        @if($corrCount > 0)
                                                            <div class="text-[10px] text-amber-700/80 font-medium mt-0.5">
                                                                {{ $corrCount }} {{ $corrCount === 1 ? 'section' : 'sections' }} flagged
                                                            </div>
                                                        @endif
                                                    @else
                                                        <span class="inline-flex items-center gap-1.5 font-bold text-rose-700 text-xs">
                                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                                            </svg>
                                                            {{ $rev->status }}
                                                        </span>
                                                    @endif
                                                </td>

                                                <td class="py-4 px-5 font-mono-code font-bold whitespace-nowrap">
                                                    @if(!empty($rev->approved_case_number))
                                                        <span class="text-slate-900">{{ $rev->approved_case_number }}</span>
                                                    @else
                                                        <span class="text-slate-400 font-normal">Not assigned</span>
                                                    @endif
                                                </td>

                                                <td class="py-4 px-5 whitespace-nowrap">
                                                    <div class="font-medium text-slate-700">{{ $rev->reviewed_by_name ?: 'Unit Head' }}</div>
                                                    <div class="text-[10px] font-mono-code text-slate-400 mt-0.5">{{ optional($rev->reviewed_at)->format('d M Y, H:i') }}</div>
                                                </td>

                                                <td class="py-4 px-5 text-right whitespace-nowrap">
                                                    <a href="{{ $revUrl }}" class="inline-flex items-center gap-1 text-xs font-bold text-slate-700 hover:text-emerald-700 transition-colors text-decoration-none">
                                                        <span>Open</span>
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                        </svg>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- FILTERED EMPTY STATE FOR HISTORY -->
                            <div id="historyFilterEmpty" class="hidden p-10 text-center">
                                <h4 class="text-sm font-bold text-slate-900">No Matching Reviewed Submissions</h4>
                                <p class="text-xs text-slate-500 mt-1">Try selecting a different decision tab, date range, or clearing the search bar.</p>
                                <button type="button" onclick="clearHistoryAllFilters()" class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors">
                                    Reset History Filters
                                </button>
                            </div>

                            <!-- HISTORY PAGINATION FOOTER -->
                            <div id="historyPaginationBar" class="px-6 py-4 border-t border-slate-200/80 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                                <div class="flex items-center gap-3 text-slate-500 font-mono-code text-[11px]">
                                    <span>Showing <strong id="historyPageStart" class="text-slate-800">1</strong> to <strong id="historyPageEnd" class="text-slate-800">5</strong> of <strong id="historyPageTotal" class="text-slate-800">0</strong></span>
                                    <span class="text-slate-300">|</span>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-slate-400">Per page:</span>
                                        <select id="historyPerPageSelect" onchange="changeHistoryPerPage(this.value)" class="bg-white border border-slate-200 rounded-lg text-xs py-0.5 px-2 font-mono-code text-slate-700 focus:outline-none focus:border-emerald-600">
                                            <option value="5" selected>5</option>
                                            <option value="10">10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                        </select>
                                    </div>
                                </div>
                                <div id="historyPaginationControls" class="flex items-center gap-1"></div>
                            </div>
                        @endif
                    </div>
                </section>
            </main>
        </div>
    </div>

    <script>
        // State for Pending Staff Submissions
        let activePendingFilter = 'ALL';
        let pendingSearchQuery = '';
        let pendingFromDate = '';
        let pendingToDate = '';
        let pendingCurrentPage = 1;
        let pendingPerPage = 5;

        // State for Decision History
        let activeHistoryFilter = 'ALL';
        let historySearchQuery = '';
        let historyFromDate = '';
        let historyToDate = '';
        let historyCurrentPage = 1;
        let historyPerPage = 5;

        // Global search query (header input)
        let globalSearchQuery = '';

        /* ── Global Header Search Sync ── */
        function syncSearchInputs(val) {
            const desktop = document.getElementById('queueSearchInput');
            const mobile = document.getElementById('queueSearchInputMobile');
            const clearBtn = document.getElementById('queueSearchClear');
            if (desktop && desktop.value !== val) desktop.value = val;
            if (mobile && mobile.value !== val) mobile.value = val;
            if (clearBtn) {
                clearBtn.classList.toggle('hidden', val.trim() === '');
            }
            globalSearchQuery = val.trim().toLowerCase();
            pendingCurrentPage = 1;
            historyCurrentPage = 1;
            applyAllFilters();
        }

        function clearQueueSearch() {
            syncSearchInputs('');
        }

        /* ── Pending Submissions Filter & Pagination Handlers ── */
        function onPendingSearchInput(val) {
            pendingSearchQuery = val.trim().toLowerCase();
            const clearBtn = document.getElementById('pendingSearchClear');
            if (clearBtn) {
                clearBtn.classList.toggle('hidden', val.trim() === '');
            }
            pendingCurrentPage = 1;
            applyPendingFilters();
        }

        function clearPendingSearch() {
            const input = document.getElementById('pendingSearchInput');
            if (input) input.value = '';
            onPendingSearchInput('');
        }

        function onPendingDateChange() {
            pendingFromDate = document.getElementById('pendingDateFrom')?.value || '';
            pendingToDate = document.getElementById('pendingDateTo')?.value || '';
            const clearBtn = document.getElementById('clearPendingDateBtn');
            if (clearBtn) {
                clearBtn.classList.toggle('hidden', !pendingFromDate && !pendingToDate);
            }
            pendingCurrentPage = 1;
            applyPendingFilters();
        }

        function clearPendingDateFilter() {
            const fromEl = document.getElementById('pendingDateFrom');
            const toEl = document.getElementById('pendingDateTo');
            if (fromEl) fromEl.value = '';
            if (toEl) toEl.value = '';
            onPendingDateChange();
        }

        function clearPendingAllFilters() {
            clearPendingSearch();
            clearPendingDateFilter();
            setPendingFilter('ALL');
        }

        function setPendingFilter(filterKey, btnEl) {
            activePendingFilter = filterKey;
            document.querySelectorAll('[data-pending-tab]').forEach(btn => {
                btn.classList.toggle('is-active', btn.dataset.pendingTab === filterKey);
            });
            pendingCurrentPage = 1;
            applyPendingFilters();
        }

        function focusPendingFilter(filterKey) {
            const targetBtn = document.querySelector(`[data-pending-tab="${filterKey}"]`);
            setPendingFilter(filterKey, targetBtn);
            document.getElementById('pendingQueueSection')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function changePendingPerPage(val) {
            pendingPerPage = parseInt(val, 10) || 5;
            pendingCurrentPage = 1;
            applyPendingFilters();
        }

        function goToPendingPage(p) {
            pendingCurrentPage = p;
            applyPendingFilters();
            document.getElementById('pendingQueueSection')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        /* ── Decision History Filter & Pagination Handlers ── */
        function onHistorySearchInput(val) {
            historySearchQuery = val.trim().toLowerCase();
            const clearBtn = document.getElementById('historySearchClear');
            if (clearBtn) {
                clearBtn.classList.toggle('hidden', val.trim() === '');
            }
            historyCurrentPage = 1;
            applyHistoryFilters();
        }

        function clearHistorySearch() {
            const input = document.getElementById('historySearchInput');
            if (input) input.value = '';
            onHistorySearchInput('');
        }

        function onHistoryDateChange() {
            historyFromDate = document.getElementById('historyDateFrom')?.value || '';
            historyToDate = document.getElementById('historyDateTo')?.value || '';
            const clearBtn = document.getElementById('clearHistoryDateBtn');
            if (clearBtn) {
                clearBtn.classList.toggle('hidden', !historyFromDate && !historyToDate);
            }
            historyCurrentPage = 1;
            applyHistoryFilters();
        }

        function clearHistoryDateFilter() {
            const fromEl = document.getElementById('historyDateFrom');
            const toEl = document.getElementById('historyDateTo');
            if (fromEl) fromEl.value = '';
            if (toEl) toEl.value = '';
            onHistoryDateChange();
        }

        function clearHistoryAllFilters() {
            clearHistorySearch();
            clearHistoryDateFilter();
            setHistoryFilter('ALL');
        }

        function setHistoryFilter(statusKey, btnEl) {
            activeHistoryFilter = statusKey;
            document.querySelectorAll('[data-history-tab]').forEach(btn => {
                btn.classList.toggle('is-active', btn.dataset.historyTab === statusKey);
            });
            historyCurrentPage = 1;
            applyHistoryFilters();
        }

        function focusHistoryFilter(statusKey) {
            const targetBtn = document.querySelector(`[data-history-tab="${statusKey}"]`);
            setHistoryFilter(statusKey, targetBtn);
            document.getElementById('reviewedHistorySection')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function changeHistoryPerPage(val) {
            historyPerPage = parseInt(val, 10) || 5;
            historyCurrentPage = 1;
            applyHistoryFilters();
        }

        function goToHistoryPage(p) {
            historyCurrentPage = p;
            applyHistoryFilters();
            document.getElementById('reviewedHistorySection')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function resetAllQueueFilters() {
            syncSearchInputs('');
            clearPendingSearch();
            clearPendingDateFilter();
            clearHistorySearch();
            clearHistoryDateFilter();
            setPendingFilter('ALL');
            setHistoryFilter('ALL');
        }

        /* ── Reusable Pagination UI Renderer ── */
        function renderPaginationUI(prefix, totalMatching, currentPage, perPage, totalPages, start, end, goFnName) {
            const bar = document.getElementById(`${prefix}PaginationBar`);
            if (!bar) return;

            if (totalMatching === 0) {
                bar.classList.add('hidden');
                return;
            }
            bar.classList.remove('hidden');

            const startEl = document.getElementById(`${prefix}PageStart`);
            const endEl = document.getElementById(`${prefix}PageEnd`);
            const totalEl = document.getElementById(`${prefix}PageTotal`);
            if (startEl) startEl.textContent = start;
            if (endEl) endEl.textContent = end;
            if (totalEl) totalEl.textContent = totalMatching;

            const controls = document.getElementById(`${prefix}PaginationControls`);
            if (!controls) return;

            let html = '';

            // Previous Button
            const prevDisabled = currentPage <= 1;
            html += `<button type="button" ${prevDisabled ? 'disabled' : `onclick="${goFnName}(${currentPage - 1})"`} class="px-2.5 py-1 text-xs font-semibold rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors" title="Previous page">&larr; Prev</button>`;

            // Numbered Page Buttons
            let pages = [];
            if (totalPages <= 7) {
                for (let i = 1; i <= totalPages; i++) pages.push(i);
            } else {
                pages.push(1);
                if (currentPage > 3) pages.push('...');
                let pStart = Math.max(2, currentPage - 1);
                let pEnd = Math.min(totalPages - 1, currentPage + 1);
                for (let i = pStart; i <= pEnd; i++) pages.push(i);
                if (currentPage < totalPages - 2) pages.push('...');
                pages.push(totalPages);
            }

            pages.forEach(p => {
                if (p === '...') {
                    html += `<span class="px-2 py-1 text-xs font-bold text-slate-400">...</span>`;
                } else if (p === currentPage) {
                    html += `<button type="button" class="px-3 py-1 text-xs font-bold rounded-lg bg-emerald-600 text-white shadow-2xs">${p}</button>`;
                } else {
                    html += `<button type="button" onclick="${goFnName}(${p})" class="px-3 py-1 text-xs font-semibold rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-100 transition-colors">${p}</button>`;
                }
            });

            // Next Button
            const nextDisabled = currentPage >= totalPages;
            html += `<button type="button" ${nextDisabled ? 'disabled' : `onclick="${goFnName}(${currentPage + 1})"`} class="px-2.5 py-1 text-xs font-semibold rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors" title="Next page">Next &rarr;</button>`;

            controls.innerHTML = html;
        }

        /* ── Filter Engine: Pending Submissions ── */
        function applyPendingFilters() {
            const pendingItems = Array.from(document.querySelectorAll('.pending-item'));
            if (!pendingItems.length) {
                const bar = document.getElementById('pendingPaginationBar');
                if (bar) bar.classList.add('hidden');
                return;
            }

            const activeQuery = pendingSearchQuery || globalSearchQuery;
            const matchingItems = [];

            pendingItems.forEach(item => {
                const prio = (item.dataset.priority || 'MEDIUM').toUpperCase();
                const isUrgent = item.dataset.urgent === '1';
                const itemStatus = item.dataset.status || 'Pending Approval';
                const haystack = item.dataset.search || '';
                const itemDate = item.dataset.date || '';

                const matchesTab = activePendingFilter === 'ALL'
                    || (activePendingFilter === 'PENDING' && itemStatus === 'Pending Approval')
                    || (activePendingFilter === 'ROLLED_BACK' && itemStatus === 'Rolled Back')
                    || (activePendingFilter === 'URGENT' && isUrgent)
                    || (activePendingFilter === 'EDITED' && item.dataset.isEdit === '1')
                    || (activePendingFilter === prio);

                const matchesSearch = !activeQuery || haystack.includes(activeQuery);

                let matchesDate = true;
                if (pendingFromDate && itemDate) {
                    matchesDate = matchesDate && (itemDate >= pendingFromDate);
                }
                if (pendingToDate && itemDate) {
                    matchesDate = matchesDate && (itemDate <= pendingToDate);
                }

                if (matchesTab && matchesSearch && matchesDate) {
                    matchingItems.push(item);
                } else {
                    item.classList.add('hidden');
                }
            });

            const totalMatching = matchingItems.length;
            const totalPages = Math.max(1, Math.ceil(totalMatching / pendingPerPage));
            if (pendingCurrentPage > totalPages) pendingCurrentPage = totalPages;
            if (pendingCurrentPage < 1) pendingCurrentPage = 1;

            const startIndex = (pendingCurrentPage - 1) * pendingPerPage;
            const endIndex = Math.min(startIndex + pendingPerPage, totalMatching);

            matchingItems.forEach((item, idx) => {
                if (idx >= startIndex && idx < endIndex) {
                    item.classList.remove('hidden');
                } else {
                    item.classList.add('hidden');
                }
            });

            const countEl = document.getElementById('pendingVisibleCount');
            if (countEl) {
                const isFiltered = activeQuery || activePendingFilter !== 'ALL' || pendingFromDate || pendingToDate;
                countEl.textContent = isFiltered
                    ? `${totalMatching} of ${pendingItems.length} filtered`
                    : `${pendingItems.length} total`;
            }

            const emptyEl = document.getElementById('pendingFilterEmpty');
            if (emptyEl) {
                emptyEl.classList.toggle('hidden', totalMatching > 0);
            }

            renderPaginationUI('pending', totalMatching, pendingCurrentPage, pendingPerPage, totalPages, totalMatching > 0 ? startIndex + 1 : 0, endIndex, 'goToPendingPage');
        }

        /* ── Filter Engine: Decision History ── */
        function applyHistoryFilters() {
            const historyRows = Array.from(document.querySelectorAll('.history-row'));
            if (!historyRows.length) {
                const bar = document.getElementById('historyPaginationBar');
                if (bar) bar.classList.add('hidden');
                return;
            }

            const activeQuery = historySearchQuery || globalSearchQuery;
            const matchingRows = [];

            historyRows.forEach(row => {
                const st = row.dataset.status || '';
                const haystack = row.dataset.search || '';
                const rowDate = row.dataset.date || '';

                const matchesTab = activeHistoryFilter === 'ALL' || st === activeHistoryFilter;
                const matchesSearch = !activeQuery || haystack.includes(activeQuery);

                let matchesDate = true;
                if (historyFromDate && rowDate) {
                    matchesDate = matchesDate && (rowDate >= historyFromDate);
                }
                if (historyToDate && rowDate) {
                    matchesDate = matchesDate && (rowDate <= historyToDate);
                }

                if (matchesTab && matchesSearch && matchesDate) {
                    matchingRows.push(row);
                } else {
                    row.classList.add('hidden');
                }
            });

            const totalMatching = matchingRows.length;
            const totalPages = Math.max(1, Math.ceil(totalMatching / historyPerPage));
            if (historyCurrentPage > totalPages) historyCurrentPage = totalPages;
            if (historyCurrentPage < 1) historyCurrentPage = 1;

            const startIndex = (historyCurrentPage - 1) * historyPerPage;
            const endIndex = Math.min(startIndex + historyPerPage, totalMatching);

            matchingRows.forEach((row, idx) => {
                if (idx >= startIndex && idx < endIndex) {
                    row.classList.remove('hidden');
                } else {
                    row.classList.add('hidden');
                }
            });

            const countEl = document.getElementById('historyVisibleCount');
            if (countEl) {
                const isFiltered = activeQuery || activeHistoryFilter !== 'ALL' || historyFromDate || historyToDate;
                countEl.textContent = isFiltered
                    ? `${totalMatching} of ${historyRows.length} filtered`
                    : `${historyRows.length} records`;
            }

            const emptyEl = document.getElementById('historyFilterEmpty');
            if (emptyEl) {
                emptyEl.classList.toggle('hidden', totalMatching > 0);
            }

            renderPaginationUI('history', totalMatching, historyCurrentPage, historyPerPage, totalPages, totalMatching > 0 ? startIndex + 1 : 0, endIndex, 'goToHistoryPage');
        }

        /* ── Unified Filter Applicator ── */
        function applyAllFilters() {
            applyPendingFilters();
            applyHistoryFilters();
        }

        /* ── Real-Time Silent Polling Engine for Pending Staff Submissions ─────── */
        let isPollingPendingQueue = false;
        let lastPendingSignature = null;

        async function pollPendingQueue(force = false) {
            if (isPollingPendingQueue || (!force && document.hidden)) return;
            isPollingPendingQueue = true;

            const controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
            const timeoutId = controller ? setTimeout(() => controller.abort(), 6000) : null;

            try {
                const pollUrl = window.location.pathname + (window.location.search ? (window.location.search + '&poll=1') : '?poll=1');
                const res = await fetch(pollUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    signal: controller ? controller.signal : undefined
                });
                if (timeoutId) clearTimeout(timeoutId);
                if (!res.ok) return;

                const data = await res.json();
                if (!data || !data.success) return;

                // If signature matches and not forced, no changes occurred
                if (data.signature && data.signature === lastPendingSignature && !force) {
                    return;
                }
                lastPendingSignature = data.signature;

                // 1. Update queue container HTML
                const container = document.getElementById('pendingQueueContainer');
                if (container && data.html !== undefined) {
                    container.innerHTML = data.html;
                }

                // 2. Update KPI metrics cards
                if (data.counts) {
                    const elPending = document.getElementById('kpiPendingReviewCount');
                    if (elPending) elPending.textContent = data.counts.awaiting_review;

                    const elRolledBack = document.getElementById('kpiRolledBackCount');
                    if (elRolledBack) {
                        elRolledBack.textContent = data.counts.rolled_back;
                        elRolledBack.className = 'text-2xl font-extrabold ' + (data.counts.rolled_back > 0 ? 'text-amber-700' : 'text-slate-900') + ' tnum mt-1';
                    }
                    const iconRolledBack = document.getElementById('kpiRolledBackIcon');
                    if (iconRolledBack) {
                        iconRolledBack.setAttribute('class', 'w-5 h-5 ' + (data.counts.rolled_back > 0 ? 'text-amber-600' : 'text-slate-400') + ' shrink-0 mt-0.5');
                    }

                    const elUrgent = document.getElementById('kpiUrgentPendingCount');
                    if (elUrgent) {
                        elUrgent.textContent = data.counts.urgent;
                        elUrgent.className = 'text-2xl font-extrabold ' + (data.counts.urgent > 0 ? 'text-red-600' : 'text-slate-900') + ' tnum mt-1';
                    }
                    const iconUrgent = document.getElementById('kpiUrgentPendingIcon');
                    if (iconUrgent) {
                        iconUrgent.setAttribute('class', 'w-5 h-5 ' + (data.counts.urgent > 0 ? 'text-red-500' : 'text-slate-400') + ' shrink-0 mt-0.5');
                    }

                    const elApproved = document.getElementById('kpiApprovedHistoryCount');
                    if (elApproved) elApproved.textContent = data.counts.approved_history;

                    // 3. Update tabs counts
                    const tabAll = document.getElementById('tabCountAll');
                    if (tabAll) tabAll.textContent = data.counts.pending_total;

                    const tabPending = document.getElementById('tabCountPending');
                    if (tabPending) tabPending.textContent = data.counts.awaiting_review;

                    const tabEdited = document.getElementById('tabCountEdited');
                    if (tabEdited && data.counts.edited_cases !== undefined) tabEdited.textContent = data.counts.edited_cases;

                    const tabEditedWrapper = document.getElementById('tabEditedWrapper');
                    if (tabEditedWrapper && data.counts.edited_cases !== undefined) {
                        tabEditedWrapper.classList.toggle('hidden', data.counts.edited_cases <= 0);
                        if (data.counts.edited_cases <= 0 && activePendingFilter === 'EDITED') {
                            setPendingFilter('ALL');
                        }
                    }

                    const tabRolledBack = document.getElementById('tabCountRolledBack');
                    if (tabRolledBack) tabRolledBack.textContent = data.counts.rolled_back;

                    const tabWrapper = document.getElementById('tabRolledBackWrapper');
                    if (tabWrapper) {
                        if (data.counts.rolled_back > 0) {
                            tabWrapper.classList.remove('hidden');
                        } else {
                            tabWrapper.classList.add('hidden');
                            if (activePendingFilter === 'ROLLED_BACK') {
                                setPendingFilter('ALL');
                            }
                        }
                    }

                    const tabUrgent = document.getElementById('tabCountUrgent');
                    if (tabUrgent) tabUrgent.textContent = data.counts.urgent;
                }

                // 4. Re-apply current tab filter, date filter, search query, and pagination to new items
                applyAllFilters();
            } catch (e) {
                // Silently ignore network failures during background polling
            } finally {
                if (timeoutId) clearTimeout(timeoutId);
                isPollingPendingQueue = false;
            }
        }
        window.pollPendingQueue = pollPendingQueue;

        // 1. Fast 3-second silent background polling interval
        setInterval(() => pollPendingQueue(), 3000);

        // 2. Immediate check when window regains focus or visibility
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) pollPendingQueue();
        });
        window.addEventListener('focus', () => pollPendingQueue());

        // 3. Cross-tab sync: trigger immediately when an action or submission is made in another tab
        window.addEventListener('storage', (e) => {
            if (e.key === 'nacoc_badge_sync') {
                pollPendingQueue(true);
            }
        });
        window.addEventListener('nacoc:badge-update', () => pollPendingQueue(true));

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('queueSearchInput')?.addEventListener('input', (e) => syncSearchInputs(e.target.value));
            document.getElementById('queueSearchInputMobile')?.addEventListener('input', (e) => syncSearchInputs(e.target.value));
            applyAllFilters();
        });
    </script>
</body>
</html>
