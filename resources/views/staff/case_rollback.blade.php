@php
    $authUser = auth()->user();
    $staffName = $authUser->fullname ?? $authUser->name ?? 'Staff Officer';
    $sidebarUnit = $sidebarUnit ?? ($unitContext['sidebar'] ?? 'Crime Scene Support');
    $unitKey = $unitContext['key'] ?? 'crime_scene_support';
    $unitLabel = $unitContext['label'] ?? ($sidebarUnit . ' Unit');

    $staffDashboardUrl = match ($unitKey) {
        'lab_analysis' => url('/Lab_Analysis/staff/dashboard'),
        'evidence_management' => url('/Evidence_Management/staff/dashboard'),
        'digital_forensics' => url('/Digital_Forensics/staff/dashboard'),
        'forensic_head' => url('/Forensic_Head/staff/dashboard'),
        default => url('/Crime_Scene_Support/staff/dashboard'),
    };

    $caseRegisterUrl = match ($unitKey) {
        'lab_analysis' => url('/Lab_Analysis/staff/case'),
        'evidence_management' => url('/Evidence_Management/staff/case'),
        'digital_forensics' => url('/Digital_Forensics/staff/case'),
        'forensic_head' => url('/Forensic_Head/staff/case'),
        default => url('/Crime_Scene_Support/staff/case'),
    };

    $rollbackEditUrlFor = function ($id) use ($unitKey) {
        return match ($unitKey) {
            'lab_analysis' => url('/Lab_Analysis/staff/case-rollback/' . $id),
            'evidence_management' => url('/Evidence_Management/staff/case-rollback/' . $id),
            'digital_forensics' => url('/Digital_Forensics/staff/case-rollback/' . $id),
            'forensic_head' => url('/Forensic_Head/staff/case-rollback/' . $id),
            default => url('/Crime_Scene_Support/staff/case-rollback/' . $id),
        };
    };

    $photoResolver = app(\App\Services\CasePhotoResolver::class);

    $urgentRollbackCount = $submissions->filter(function ($s) {
        return in_array(strtoupper((string) $s->priority), ['HIGH', 'CRITICAL'], true);
    })->count();

    $multiCorrectionCount = $submissions->filter(function ($s) {
        return is_array($s->rollback_corrections) && count($s->rollback_corrections) >= 2;
    })->count();

    $totalFlaggedSections = $submissions->sum(function ($s) {
        return is_array($s->rollback_corrections) ? count($s->rollback_corrections) : 0;
    });

    $extractRollbackMeta = function ($sub) use ($photoResolver) {
        $p = is_array($sub->payload) ? $sub->payload : [];
        $docket = is_array($p['docket_data'] ?? null) ? $p['docket_data'] : [];
        $suspectsList = $p['suspects'] ?? ($docket['suspects'] ?? []);

        $rawFront = $p['mugshot_front']
            ?? ($p['mugshots']['front'] ?? null)
            ?? ($suspectsList[0]['mugshot_front'] ?? null)
            ?? ($suspectsList[0]['mugshots']['front'] ?? null);

        $corrList = is_array($sub->rollback_corrections) ? $sub->rollback_corrections : [];
        $flaggedFields = [];
        $flaggedNotes = [];

        foreach ($corrList as $item) {
            if (is_string($item) && trim($item) !== '') {
                $flaggedFields[] = trim($item);
            } elseif (is_array($item)) {
                $fieldLabel = trim((string) ($item['field'] ?? ($item['section'] ?? ($item['label'] ?? ''))));
                $noteText = trim((string) ($item['note'] ?? ''));
                if ($fieldLabel !== '') {
                    $flaggedFields[] = $fieldLabel;
                }
                if ($noteText !== '') {
                    $flaggedNotes[] = $noteText;
                }
            }
        }

        return [
            'mugshot_url' => $photoResolver->formatMugshotUrl($rawFront),
            'case_ref' => trim((string) ($sub->case_number_preview ?? ($p['case_number'] ?? ('PENDING-' . $sub->id)))),
            'corr_count' => count($corrList),
            'flagged_fields' => $flaggedFields,
            'flagged_notes' => $flaggedNotes,
        ];
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Case Rollback &amp; Corrections | {{ $sidebarUnit }} | NACOC Forensic</title>

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
        .rollback-card {
            background: #FFFFFF;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 4px 12px -4px rgba(15, 23, 42, 0.05);
            transition: box-shadow 0.18s ease, transform 0.18s ease;
        }
        .rollback-card:hover {
            box-shadow: 0 12px 26px -10px rgba(15, 23, 42, 0.12);
            transform: translateY(-1px);
        }
        .metric-action-btn {
            transition: background-color 0.16s ease;
        }
        .metric-action-btn:hover {
            background-color: #F8FAFC;
        }
        a:focus-visible, button:not(:disabled):focus-visible, input:focus-visible {
            outline: 2px solid #059669;
            outline-offset: 2px;
        }
    </style>
</head>
<body class="min-h-screen bg-[#F6F7F9] text-slate-900 antialiased selection:bg-emerald-700 selection:text-white">

    <div class="flex h-screen w-full overflow-hidden">
        @include('staff.partials.sidebar')

        <div class="flex-1 flex flex-col min-w-0 bg-[#F6F7F9] overflow-hidden">
            <!-- TOPBAR -->
            <header class="h-16 bg-white shadow-2xs px-4 sm:px-6 flex items-center justify-between gap-4 shrink-0 z-20">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50/70 text-emerald-700 flex items-center justify-center shrink-0" aria-hidden="true">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-sm font-extrabold text-slate-900 tracking-tight truncate">Case Rollback &amp; Corrections</h1>
                        <p class="text-[11px] text-slate-500 font-medium truncate">{{ $unitLabel }} Correction Queue</p>
                    </div>
                </div>

                <!-- LIVE SEARCH & DASHBOARD LINK -->
                <div class="flex items-center gap-3 shrink-0">
                    <div class="relative hidden md:block w-72">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 pointer-events-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </span>
                        <input
                            type="text"
                            id="rollbackSearchInput"
                            placeholder="Search SUB #, suspect, field, remark..."
                            aria-label="Search rolled back submissions"
                            class="w-full pl-9 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-emerald-600 focus:outline-none transition-colors"
                        >
                        <button
                            type="button"
                            id="rollbackSearchClear"
                            onclick="clearRollbackSearch()"
                            aria-label="Clear search"
                            class="hidden absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-700"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <a href="{{ $staffDashboardUrl }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 hover:border-slate-300 text-xs font-bold rounded-xl transition-colors text-decoration-none">
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
                                    Correction &amp; Resubmission Queue
                                </div>
                                <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900">
                                    Returned Submissions Requiring Corrections
                                </h2>
                                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                    Review Unit Head–flagged sections and notes, apply requested fixes to suspect profiles, mugshots, or exhibits, then resubmit the docket for final authorization.
                                </p>
                            </div>

                            <div class="hidden lg:block text-right shrink-0 pl-6">
                                <div class="text-[10px] font-mono-code font-bold uppercase tracking-wider text-slate-400">Assigned Officer</div>
                                <div class="text-sm font-extrabold text-slate-900 mt-0.5">{{ $staffName }}</div>
                                <div class="text-xs font-semibold text-emerald-700 mt-0.5">{{ $unitLabel }}</div>
                            </div>

                            <!-- MOBILE SEARCH FALLBACK -->
                            <div class="md:hidden w-full">
                                <input
                                    type="text"
                                    id="rollbackSearchInputMobile"
                                    placeholder="Search SUB #, suspect, field, remark..."
                                    aria-label="Search rolled back submissions on mobile"
                                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-emerald-600 focus:outline-none"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- 3-COLUMN ARCHITECTURAL KPI SUMMARY BAR (UNBOXED TYPOGRAPHY) -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 bg-slate-50/50">
                        <button type="button" onclick="focusRollbackFilter('ALL')" class="metric-action-btn p-5 text-left flex items-start justify-between gap-3">
                            <div>
                                <div class="text-[11px] font-mono-code font-bold uppercase tracking-wider text-slate-500">Rolled Back Cases</div>
                                <div class="text-2xl font-extrabold text-slate-900 tnum mt-1">{{ $submissions->count() }}</div>
                                <div class="text-[11px] font-medium text-emerald-700 mt-1">Awaiting staff revision</div>
                            </div>
                            <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                            </svg>
                        </button>

                        <button type="button" onclick="focusRollbackFilter('MULTI')" class="metric-action-btn p-5 text-left flex items-start justify-between gap-3">
                            <div>
                                <div class="text-[11px] font-mono-code font-bold uppercase tracking-wider text-slate-500">Flagged Sections</div>
                                <div class="text-2xl font-extrabold text-slate-900 tnum mt-1">{{ $totalFlaggedSections }}</div>
                                <div class="text-[11px] font-medium text-slate-500 mt-1">Highlighted items across queue</div>
                            </div>
                            <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                        </button>

                        <button type="button" onclick="focusRollbackFilter('URGENT')" class="metric-action-btn p-5 text-left flex items-start justify-between gap-3">
                            <div>
                                <div class="text-[11px] font-mono-code font-bold uppercase tracking-wider text-slate-500">High Priority</div>
                                <div class="text-2xl font-extrabold {{ $urgentRollbackCount > 0 ? 'text-red-600' : 'text-slate-900' }} tnum mt-1">{{ $urgentRollbackCount }}</div>
                                <div class="text-[11px] font-medium {{ $urgentRollbackCount > 0 ? 'text-red-600' : 'text-slate-500' }} mt-1">Priority docket attention</div>
                            </div>
                            <svg class="w-5 h-5 {{ $urgentRollbackCount > 0 ? 'text-red-500' : 'text-slate-400' }} shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </button>
                    </div>
                </section>

                <!-- ROLLED BACK CASES QUEUE -->
                <section id="rollbackQueueSection" class="space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 px-1">
                        <div class="pb-2.5 flex items-baseline gap-2.5">
                            <h3 class="text-base font-extrabold text-slate-900 tracking-tight">
                                Cases Flagged for Revision
                            </h3>
                            <span id="rollbackVisibleCount" class="text-xs font-mono-code font-bold text-slate-500 tnum">
                                {{ $submissions->count() }} {{ $submissions->count() === 1 ? 'case' : 'cases' }}
                            </span>
                        </div>

                        <div class="flex items-center gap-5 overflow-x-auto" role="tablist" aria-label="Filter rolled back cases">
                            <button type="button" class="filter-underline-tab is-active" data-rollback-tab="ALL" onclick="setRollbackFilter('ALL', this)">
                                All Cases
                            </button>
                            <button type="button" class="filter-underline-tab" data-rollback-tab="URGENT" onclick="setRollbackFilter('URGENT', this)">
                                High Priority ({{ $urgentRollbackCount }})
                            </button>
                            <button type="button" class="filter-underline-tab" data-rollback-tab="MULTI" onclick="setRollbackFilter('MULTI', this)">
                                Multiple Corrections ({{ $multiCorrectionCount }})
                            </button>
                        </div>
                    </div>

                    @if($submissions->isEmpty())
                        <div class="bg-white rounded-2xl p-12 text-center shadow-sm space-y-3">
                            <div class="w-12 h-12 mx-auto rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div class="max-w-md mx-auto space-y-1">
                                <h4 class="text-sm font-extrabold text-slate-900">No Rolled Back Cases for {{ $sidebarUnit }}</h4>
                                <p class="text-xs text-slate-500 leading-relaxed">
                                    All your submitted cases are either already approved into the forensic database or awaiting initial Unit Head authorization.
                                </p>
                            </div>
                            <div class="pt-1">
                                <a href="{{ $caseRegisterUrl }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition-colors text-decoration-none">
                                    View Case Register
                                </a>
                            </div>
                        </div>
                    @else
                        <div id="rollbackQueueList" class="space-y-3">
                            @foreach($submissions as $sub)
                                @php
                                    $meta = $extractRollbackMeta($sub);
                                    $prio = \App\Models\ForensicCase::normalizePriority($sub->priority ?: 'LOW');
                                    $isUrgent = $prio === 'HIGH';
                                    $isMulti = $meta['corr_count'] >= 2;
                                    $prioTextColor = $prio === 'HIGH' ? 'text-red-600' : 'text-emerald-700';
                                    $subCode = 'SUB-' . str_pad((string) $sub->id, 4, '0', STR_PAD_LEFT);
                                    $editUrl = $rollbackEditUrlFor($sub->id);
                                    $searchHaystack = strtolower(implode(' ', array_filter(array_merge([
                                        $subCode,
                                        $sub->suspect_name,
                                        $sub->title,
                                        $sub->reviewed_by_name,
                                        $sub->review_notes,
                                        $sub->unit_name,
                                        $prio,
                                        $meta['case_ref'],
                                    ], $meta['flagged_fields'], $meta['flagged_notes']))));
                                @endphp
                                <article
                                    class="rollback-card rounded-2xl p-4 sm:p-5 rollback-item"
                                    data-urgent="{{ $isUrgent ? '1' : '0' }}"
                                    data-multi="{{ $isMulti ? '1' : '0' }}"
                                    data-search="{{ $searchHaystack }}"
                                >
                                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-center">
                                        <!-- COL 1: SUBMISSION REF, PRIORITY & ROLLBACK DATE -->
                                        <div class="lg:col-span-2 space-y-1">
                                            <a href="{{ $editUrl }}" class="font-mono-code font-extrabold text-sm text-slate-900 hover:text-emerald-700 transition-colors text-decoration-none block">
                                                {{ $subCode }}
                                            </a>
                                            <div class="font-mono-code font-extrabold text-[11px] uppercase tracking-wide {{ $prioTextColor }}">
                                                {{ $prio }} PRIORITY
                                            </div>
                                            <div class="text-[11px] text-slate-400 font-mono-code">
                                                {{ optional($sub->rolled_back_at ?? $sub->reviewed_at ?? $sub->updated_at)->format('d M Y, H:i') }}
                                            </div>
                                        </div>

                                        <!-- COL 2: SUSPECT THUMBNAIL, CASE DETAILS & FLAGGED DIRECTIVES -->
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
                                                    <a href="{{ $editUrl }}" class="text-sm sm:text-[15px] font-extrabold text-slate-900 hover:text-emerald-700 transition-colors truncate text-decoration-none">
                                                        {{ $sub->suspect_name ?: 'Unnamed Suspect' }}
                                                    </a>
                                                    <span class="text-[11px] font-mono-code font-semibold text-slate-500">
                                                        Ref: {{ $meta['case_ref'] }}
                                                    </span>
                                                </div>

                                                <div class="text-xs font-medium text-slate-600 line-clamp-1">
                                                    {{ $sub->title ?: 'Staff Case Submission' }}
                                                </div>

                                                @if(!empty($sub->review_notes))
                                                    <div class="text-xs text-slate-700 leading-relaxed line-clamp-2 pt-0.5">
                                                        <span class="font-bold text-slate-900">Directive:</span>
                                                        {{ $sub->review_notes }}
                                                    </div>
                                                @endif

                                                @if(count($meta['flagged_fields']) > 0)
                                                    <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1 text-[11px] text-emerald-700 font-semibold pt-0.5">
                                                        <span class="font-mono-code uppercase text-[10px] tracking-wider text-slate-400 font-bold">Flagged:</span>
                                                        @foreach(array_slice($meta['flagged_fields'], 0, 3) as $idx => $fieldLabel)
                                                            @if($idx > 0)
                                                                <span class="text-slate-300">|</span>
                                                            @endif
                                                            <span class="truncate max-w-[180px]">{{ $fieldLabel }}</span>
                                                        @endforeach
                                                        @if(count($meta['flagged_fields']) > 3)
                                                            <span class="text-slate-300">|</span>
                                                            <span class="font-mono-code text-slate-500">+{{ count($meta['flagged_fields']) - 3 }} more</span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- COL 3: RETURNED BY & CORRECTION COUNT (PURE TYPOGRAPHY) -->
                                        <div class="lg:col-span-2 min-w-0 space-y-0.5 lg:pl-4">
                                            <div class="text-[10px] font-mono-code uppercase tracking-wider text-slate-400 font-semibold">Returned By</div>
                                            <div class="text-xs font-bold text-slate-800 truncate">{{ $sub->reviewed_by_name ?: 'Unit Head' }}</div>
                                            <div class="text-[11px] font-bold {{ $meta['corr_count'] > 0 ? 'text-red-600' : 'text-slate-500' }} tnum">
                                                {{ $meta['corr_count'] }} {{ $meta['corr_count'] === 1 ? 'Section Flagged' : 'Sections Flagged' }}
                                            </div>
                                        </div>

                                        <!-- COL 4: PRIMARY ACTION -->
                                        <div class="lg:col-span-3 flex items-center justify-start lg:justify-end shrink-0">
                                            <a
                                                href="{{ $editUrl }}"
                                                class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition-colors text-decoration-none"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                <span>Open &amp; Fix Corrections</span>
                                            </a>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <!-- FILTERED EMPTY STATE -->
                        <div id="rollbackFilterEmpty" class="hidden bg-white rounded-2xl shadow-sm p-10 text-center">
                            <h4 class="text-sm font-bold text-slate-900">No Matching Rolled Back Cases</h4>
                            <p class="text-xs text-slate-500 mt-1">Adjust your search query or selected filter tab to see returned submissions.</p>
                            <button type="button" onclick="resetRollbackFilters()" class="mt-3 px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition-colors">
                                Reset Filters
                            </button>
                        </div>
                    @endif
                </section>
            </main>
        </div>
    </div>

    <script>
        let activeRollbackFilter = 'ALL';
        let activeRollbackSearch = '';

        function syncRollbackSearch(val) {
            const desktop = document.getElementById('rollbackSearchInput');
            const mobile = document.getElementById('rollbackSearchInputMobile');
            const clearBtn = document.getElementById('rollbackSearchClear');
            if (desktop && desktop.value !== val) desktop.value = val;
            if (mobile && mobile.value !== val) mobile.value = val;
            if (clearBtn) {
                clearBtn.classList.toggle('hidden', val.trim() === '');
            }
            activeRollbackSearch = val.trim().toLowerCase();
            applyRollbackFilters();
        }

        function clearRollbackSearch() {
            syncRollbackSearch('');
        }

        function setRollbackFilter(filterKey, btnEl) {
            activeRollbackFilter = filterKey;
            document.querySelectorAll('[data-rollback-tab]').forEach(btn => {
                btn.classList.toggle('is-active', btn.dataset.rollbackTab === filterKey);
            });
            applyRollbackFilters();
        }

        function focusRollbackFilter(filterKey) {
            const targetBtn = document.querySelector(`[data-rollback-tab="${filterKey}"]`);
            setRollbackFilter(filterKey, targetBtn);
            document.getElementById('rollbackQueueSection')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function resetRollbackFilters() {
            syncRollbackSearch('');
            setRollbackFilter('ALL');
        }

        function applyRollbackFilters() {
            const items = document.querySelectorAll('.rollback-item');
            let shown = 0;

            items.forEach(item => {
                const isUrgent = item.dataset.urgent === '1';
                const isMulti = item.dataset.multi === '1';
                const haystack = item.dataset.search || '';

                const matchesTab = activeRollbackFilter === 'ALL'
                    || (activeRollbackFilter === 'URGENT' && isUrgent)
                    || (activeRollbackFilter === 'MULTI' && isMulti);
                const matchesSearch = !activeRollbackSearch || haystack.includes(activeRollbackSearch);

                const show = matchesTab && matchesSearch;
                item.classList.toggle('hidden', !show);
                if (show) shown++;
            });

            const countEl = document.getElementById('rollbackVisibleCount');
            if (countEl && items.length > 0) {
                if (activeRollbackSearch || activeRollbackFilter !== 'ALL') {
                    countEl.textContent = `${shown} of ${items.length} shown`;
                } else {
                    countEl.textContent = `${items.length} ${items.length === 1 ? 'case' : 'cases'}`;
                }
            }

            const emptyEl = document.getElementById('rollbackFilterEmpty');
            if (emptyEl && items.length > 0) {
                emptyEl.classList.toggle('hidden', shown > 0);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('rollbackSearchInput')?.addEventListener('input', (e) => syncRollbackSearch(e.target.value));
            document.getElementById('rollbackSearchInputMobile')?.addEventListener('input', (e) => syncRollbackSearch(e.target.value));
        });
    </script>
</body>
</html>
