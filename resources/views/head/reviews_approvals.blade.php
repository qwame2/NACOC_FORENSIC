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
    $approvedHistoryCount = $reviewedSubmissions->where('status', 'Approved')->count();
    $rolledBackHistoryCount = $reviewedSubmissions->where('status', 'Rolled Back')->count();

    $extractSubmissionMeta = function ($sub) use ($photoResolver) {
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
            ?? ($p['mugshots']['front'] ?? null)
            ?? ($suspectsList[0]['mugshot_front'] ?? null)
            ?? ($suspectsList[0]['mugshots']['front'] ?? null);
        $mugshotUrl = $photoResolver->formatMugshotUrl($rawFront);

        return [
            'suspect_count' => $suspectCount,
            'exhibit_count' => $exhibitCount,
            'offense'       => $offense,
            'location'      => $location,
            'case_ref'      => $caseRef,
            'mugshot_url'   => $mugshotUrl,
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
                        <button type="button" onclick="focusPendingFilter('ALL')" class="metric-action-btn p-5 text-left flex items-start justify-between gap-3">
                            <div>
                                <div class="text-[11px] font-mono-code font-bold uppercase tracking-wider text-slate-500">Pending Review</div>
                                <div class="text-2xl font-extrabold text-slate-900 tnum mt-1">{{ $pendingSubmissions->count() }}</div>
                                <div class="text-[11px] font-medium text-emerald-700 mt-1">Staged for sign-off</div>
                            </div>
                            <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </button>

                        <button type="button" onclick="focusPendingFilter('URGENT')" class="metric-action-btn p-5 text-left flex items-start justify-between gap-3">
                            <div>
                                <div class="text-[11px] font-mono-code font-bold uppercase tracking-wider text-slate-500">High &amp; Critical</div>
                                <div class="text-2xl font-extrabold {{ $urgentPendingCount > 0 ? 'text-red-600' : 'text-slate-900' }} tnum mt-1">{{ $urgentPendingCount }}</div>
                                <div class="text-[11px] font-medium {{ $urgentPendingCount > 0 ? 'text-red-600' : 'text-slate-500' }} mt-1">Priority docket attention</div>
                            </div>
                            <svg class="w-5 h-5 {{ $urgentPendingCount > 0 ? 'text-red-500' : 'text-slate-400' }} shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </button>

                        <button type="button" onclick="focusHistoryFilter('Rolled Back')" class="metric-action-btn p-5 text-left flex items-start justify-between gap-3">
                            <div>
                                <div class="text-[11px] font-mono-code font-bold uppercase tracking-wider text-slate-500">Rolled Back</div>
                                <div class="text-2xl font-extrabold {{ $rolledBackHistoryCount > 0 ? 'text-amber-700' : 'text-slate-900' }} tnum mt-1">{{ $rolledBackHistoryCount }}</div>
                                <div class="text-[11px] font-medium text-slate-500 mt-1">Returned for correction</div>
                            </div>
                            <svg class="w-5 h-5 {{ $rolledBackHistoryCount > 0 ? 'text-amber-600' : 'text-slate-400' }} shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                            </svg>
                        </button>

                        <button type="button" onclick="focusHistoryFilter('Approved')" class="metric-action-btn p-5 text-left flex items-start justify-between gap-3">
                            <div>
                                <div class="text-[11px] font-mono-code font-bold uppercase tracking-wider text-slate-500">Approved &amp; Saved</div>
                                <div class="text-2xl font-extrabold text-emerald-700 tnum mt-1">{{ $approvedHistoryCount }}</div>
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

                        <div class="flex items-center gap-5 overflow-x-auto" role="tablist" aria-label="Filter pending queue by priority">
                            <button type="button" class="filter-underline-tab is-active" data-pending-tab="ALL" onclick="setPendingFilter('ALL', this)">
                                All Priorities
                            </button>
                            <button type="button" class="filter-underline-tab" data-pending-tab="URGENT" onclick="setPendingFilter('URGENT', this)">
                                High &amp; Critical ({{ $urgentPendingCount }})
                            </button>
                            <button type="button" class="filter-underline-tab" data-pending-tab="LOW" onclick="setPendingFilter('LOW', this)">
                                Low
                            </button>
                        </div>
                    </div>

                    @if($pendingSubmissions->isEmpty())
                        <div class="bg-white rounded-2xl p-12 text-center shadow-sm">
                            <div class="w-12 h-12 mx-auto rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <h4 class="text-sm font-extrabold text-slate-900">Queue Clear for {{ $unitLabel }}</h4>
                            <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                                No pending staff case submissions are currently waiting for review. Newly submitted dockets from field officers will appear here automatically.
                            </p>
                        </div>
                    @else
                        <div id="pendingQueueList" class="space-y-3">
                            @foreach($pendingSubmissions as $sub)
                                @php
                                    $meta = $extractSubmissionMeta($sub);
                                    $prio = strtoupper((string) ($sub->priority ?: 'MEDIUM'));
                                    $isUrgent = in_array($prio, ['HIGH', 'CRITICAL'], true);
                                    $prioTextColor = match ($prio) {
                                        'CRITICAL' => 'text-red-700',
                                        'HIGH'     => 'text-red-600',
                                        'LOW'      => 'text-emerald-700',
                                        default    => 'text-amber-700',
                                    };
                                    $reviewUrl = $reviewUrlFor($sub->id);
                                    $subCode = 'SUB-' . str_pad((string) $sub->id, 4, '0', STR_PAD_LEFT);
                                    $searchHaystack = strtolower(implode(' ', array_filter([
                                        $subCode,
                                        $sub->suspect_name,
                                        $sub->title,
                                        $sub->submitted_by_name,
                                        $sub->unit_name,
                                        $prio,
                                        $meta['case_ref'],
                                        $meta['offense'],
                                        $meta['location'],
                                    ])));
                                @endphp
                                <article
                                    class="submission-card rounded-2xl p-4 sm:p-5 pending-item"
                                    data-priority="{{ $prio }}"
                                    data-urgent="{{ $isUrgent ? '1' : '0' }}"
                                    data-search="{{ $searchHaystack }}"
                                >
                                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-center">
                                        <!-- COL 1: SUBMISSION REF & PRIORITY (NO BG CONTAINERS, NO DOTS) -->
                                        <div class="lg:col-span-2 space-y-1">
                                            <a href="{{ $reviewUrl }}" class="font-mono-code font-extrabold text-sm text-slate-900 hover:text-emerald-700 transition-colors text-decoration-none block">
                                                {{ $subCode }}
                                            </a>
                                            <div class="font-mono-code font-extrabold text-[11px] uppercase tracking-wide {{ $prioTextColor }}">
                                                {{ $prio }} PRIORITY
                                            </div>
                                            <div class="text-[11px] text-slate-400 font-mono-code">
                                                {{ optional($sub->created_at)->format('d M Y, H:i') }}
                                            </div>
                                        </div>

                                        <!-- COL 2: SUSPECT PHOTO / IDENTITY & CASE TITLE -->
                                        <div class="lg:col-span-5 flex items-start gap-3.5 min-w-0">
                                            @if(!empty($meta['mugshot_url']))
                                                <img
                                                    src="{{ $meta['mugshot_url'] }}"
                                                    alt="{{ $sub->suspect_name ?: 'Suspect Mugshot' }}"
                                                    width="48"
                                                    height="48"
                                                    class="w-12 h-12 rounded-xl object-cover shrink-0"
                                                    onerror="this.style.display='none'"
                                                >
                                            @else
                                                <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center shrink-0" aria-hidden="true">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                    </svg>
                                                </div>
                                            @endif

                                            <div class="min-w-0 flex-1 space-y-1">
                                                <div class="flex items-baseline gap-2.5 flex-wrap">
                                                    <a href="{{ $reviewUrl }}" class="text-sm sm:text-[15px] font-extrabold text-slate-900 hover:text-emerald-700 transition-colors truncate text-decoration-none">
                                                        {{ $sub->suspect_name ?: 'Unnamed Suspect' }}
                                                    </a>
                                                    @if(!empty($meta['case_ref']))
                                                        <span class="text-[11px] font-mono-code font-semibold text-slate-500">
                                                            Ref: {{ $meta['case_ref'] }}
                                                        </span>
                                                    @endif
                                                </div>

                                                <div class="text-xs font-medium text-slate-600 line-clamp-1">
                                                    {{ $sub->title ?: 'Staff Case Submission' }}
                                                </div>

                                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500 pt-0.5">
                                                    <span>{{ $meta['suspect_count'] }} {{ $meta['suspect_count'] === 1 ? 'Suspect' : 'Suspects' }}</span>
                                                    @if($meta['exhibit_count'] > 0)
                                                        <span class="text-slate-300">|</span>
                                                        <span>{{ $meta['exhibit_count'] }} {{ $meta['exhibit_count'] === 1 ? 'Exhibit' : 'Exhibits' }}</span>
                                                    @endif
                                                    @if(!empty($meta['offense']))
                                                        <span class="text-slate-300">|</span>
                                                        <span class="truncate max-w-[200px]">{{ $meta['offense'] }}</span>
                                                    @endif
                                                    @if(!empty($meta['location']))
                                                        <span class="text-slate-300">|</span>
                                                        <span class="truncate max-w-[180px]">{{ $meta['location'] }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <!-- COL 3: SUBMITTED BY & UNIT (PURE TYPOGRAPHY) -->
                                        <div class="lg:col-span-2 min-w-0 space-y-0.5 lg:pl-4">
                                            <div class="text-[10px] font-mono-code uppercase tracking-wider text-slate-400 font-semibold">Submitted By</div>
                                            <div class="text-xs font-bold text-slate-800 truncate">{{ $sub->submitted_by_name ?: 'Staff Officer' }}</div>
                                            <div class="text-[11px] font-medium text-slate-500 truncate">{{ $sub->unit_name }}</div>
                                        </div>

                                        <!-- COL 4: DIRECT ACTIONS -->
                                        <div class="lg:col-span-3 flex items-center justify-start lg:justify-end shrink-0">
                                            <a
                                                href="{{ $reviewUrl }}"
                                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition-colors text-decoration-none"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                <span>Review Dossier</span>
                                            </a>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <!-- FILTERED EMPTY STATE FOR PENDING QUEUE -->
                        <div id="pendingFilterEmpty" class="hidden bg-white rounded-2xl shadow-sm p-10 text-center">
                            <h4 class="text-sm font-bold text-slate-900">No Matching Pending Submissions</h4>
                            <p class="text-xs text-slate-500 mt-1">Adjust your search query or selected priority tab to see staged submissions.</p>
                            <button type="button" onclick="resetAllQueueFilters()" class="mt-3 px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition-colors">
                                Reset Filters
                            </button>
                        </div>
                    @endif
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
                                All Decisions
                            </button>
                            <button type="button" class="filter-underline-tab" data-history-tab="Approved" onclick="setHistoryFilter('Approved', this)">
                                Approved ({{ $approvedHistoryCount }})
                            </button>
                            <button type="button" class="filter-underline-tab" data-history-tab="Rolled Back" onclick="setHistoryFilter('Rolled Back', this)">
                                Rolled Back ({{ $rolledBackHistoryCount }})
                            </button>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                        @if($reviewedSubmissions->isEmpty())
                            <div class="p-10 text-center text-slate-500 text-xs">
                                No reviewed submissions have been recorded yet for {{ $unitLabel }}.
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
                                                $revSearch = strtolower(implode(' ', array_filter([
                                                    $revCode,
                                                    $rev->suspect_name,
                                                    $rev->title,
                                                    $rev->submitted_by_name,
                                                    $rev->status,
                                                    $rev->approved_case_number,
                                                    $rev->reviewed_by_name,
                                                ])));
                                            @endphp
                                            <tr
                                                class="even:bg-slate-50/40 hover:bg-slate-100/60 transition-colors history-row"
                                                data-status="{{ $rev->status }}"
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

                                                <td class="py-4 px-5 min-w-[200px]">
                                                    <a href="{{ $revUrl }}" class="font-bold text-slate-900 hover:text-emerald-700 transition-colors block text-decoration-none">
                                                        {{ $rev->suspect_name ?: 'N/A' }}
                                                    </a>
                                                    <div class="text-[11px] text-slate-500 mt-0.5 line-clamp-1">{{ $rev->title }}</div>
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
                                <p class="text-xs text-slate-500 mt-1">Try selecting a different decision tab or clearing the search bar.</p>
                            </div>
                        @endif
                    </div>
                </section>
            </main>
        </div>
    </div>

    <script>
        let activePendingFilter = 'ALL';
        let activeHistoryFilter = 'ALL';
        let activeSearchQuery = '';

        function syncSearchInputs(val) {
            const desktop = document.getElementById('queueSearchInput');
            const mobile = document.getElementById('queueSearchInputMobile');
            const clearBtn = document.getElementById('queueSearchClear');
            if (desktop && desktop.value !== val) desktop.value = val;
            if (mobile && mobile.value !== val) mobile.value = val;
            if (clearBtn) {
                clearBtn.classList.toggle('hidden', val.trim() === '');
            }
            activeSearchQuery = val.trim().toLowerCase();
            applyAllFilters();
        }

        function clearQueueSearch() {
            syncSearchInputs('');
        }

        function setPendingFilter(filterKey, btnEl) {
            activePendingFilter = filterKey;
            document.querySelectorAll('[data-pending-tab]').forEach(btn => {
                btn.classList.toggle('is-active', btn.dataset.pendingTab === filterKey);
            });
            applyAllFilters();
        }

        function setHistoryFilter(statusKey, btnEl) {
            activeHistoryFilter = statusKey;
            document.querySelectorAll('[data-history-tab]').forEach(btn => {
                btn.classList.toggle('is-active', btn.dataset.historyTab === statusKey);
            });
            applyAllFilters();
        }

        function focusPendingFilter(filterKey) {
            const targetBtn = document.querySelector(`[data-pending-tab="${filterKey}"]`);
            setPendingFilter(filterKey, targetBtn);
            document.getElementById('pendingQueueSection')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function focusHistoryFilter(statusKey) {
            const targetBtn = document.querySelector(`[data-history-tab="${statusKey}"]`);
            setHistoryFilter(statusKey, targetBtn);
            document.getElementById('reviewedHistorySection')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function resetAllQueueFilters() {
            syncSearchInputs('');
            setPendingFilter('ALL');
            setHistoryFilter('ALL');
        }

        function applyAllFilters() {
            const pendingItems = document.querySelectorAll('.pending-item');
            let pendingShown = 0;
            pendingItems.forEach(item => {
                const prio = (item.dataset.priority || 'MEDIUM').toUpperCase();
                const isUrgent = item.dataset.urgent === '1';
                const haystack = item.dataset.search || '';

                const matchesTab = activePendingFilter === 'ALL'
                    || (activePendingFilter === 'URGENT' && isUrgent)
                    || (activePendingFilter === prio);
                const matchesSearch = !activeSearchQuery || haystack.includes(activeSearchQuery);

                const show = matchesTab && matchesSearch;
                item.classList.toggle('hidden', !show);
                if (show) pendingShown++;
            });

            const pendingCountEl = document.getElementById('pendingVisibleCount');
            if (pendingCountEl && pendingItems.length > 0) {
                pendingCountEl.textContent = activeSearchQuery || activePendingFilter !== 'ALL'
                    ? `${pendingShown} of ${pendingItems.length} shown`
                    : `${pendingItems.length} total`;
            }
            const pendingEmpty = document.getElementById('pendingFilterEmpty');
            if (pendingEmpty && pendingItems.length > 0) {
                pendingEmpty.classList.toggle('hidden', pendingShown > 0);
            }

            const historyRows = document.querySelectorAll('.history-row');
            let historyShown = 0;
            historyRows.forEach(row => {
                const st = row.dataset.status || '';
                const haystack = row.dataset.search || '';
                const matchesTab = activeHistoryFilter === 'ALL' || st === activeHistoryFilter;
                const matchesSearch = !activeSearchQuery || haystack.includes(activeSearchQuery);
                const show = matchesTab && matchesSearch;
                row.classList.toggle('hidden', !show);
                if (show) historyShown++;
            });

            const historyCountEl = document.getElementById('historyVisibleCount');
            if (historyCountEl && historyRows.length > 0) {
                historyCountEl.textContent = activeSearchQuery || activeHistoryFilter !== 'ALL'
                    ? `${historyShown} of ${historyRows.length} shown`
                    : `${historyRows.length} records`;
            }
            const historyEmpty = document.getElementById('historyFilterEmpty');
            if (historyEmpty && historyRows.length > 0) {
                historyEmpty.classList.toggle('hidden', historyShown > 0);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('queueSearchInput')?.addEventListener('input', (e) => syncSearchInputs(e.target.value));
            document.getElementById('queueSearchInputMobile')?.addEventListener('input', (e) => syncSearchInputs(e.target.value));
        });
    </script>
</body>
</html>
