@php
    $unitKey = $context['unit_key'] ?? 'crime_scene';
    $unitLabel = $context['unit_label'] ?? 'Crime Scene Support';
    $isForensicHead = !empty($context['is_forensic_head']);
    $isDgContext = !empty($context['is_dg']);

    $unitMeta = match (true) {
        $isDgContext => [
            'code'        => 'DG',
            'badge'       => 'OFFICE OF THE DIRECTOR-GENERAL',
            'overview'    => 'Directorate-General Notifications Overview',
            'description' => 'Executive dispatch feed for cross-unit exhibit intake alerts, inter-unit case handovers, pending docket sign-offs, and personnel activations.',
            'homeUrl'     => url('/DG'),
        ],
        $isForensicHead || $unitKey === 'forensic_head' => [
            'code'        => 'FDept',
            'badge'       => 'FORENSIC DIRECTORATE COMMAND',
            'overview'    => 'Forensic Directorate Notifications Overview',
            'description' => 'Directorate-wide alert stream monitoring cross-unit exhibits, pending case handovers, docket sign-offs, and staff activations across all forensic units.',
            'homeUrl'     => url('/Forensic_Head'),
        ],
        $unitKey === 'lab' => [
            'code'        => 'LAU',
            'badge'       => 'LABORATORY ANALYSIS UNIT',
            'overview'    => 'Laboratory Analysis Notifications Overview',
            'description' => 'Real-time unit dispatch center for incoming drug-related exhibit alerts, chemical custody handovers, pending laboratory sign-offs, and analyst activations.',
            'homeUrl'     => url('/Lab_Analysis'),
        ],
        $unitKey === 'evidence' => [
            'code'        => 'EMU',
            'badge'       => 'EVIDENCE MANAGEMENT UNIT',
            'overview'    => 'Evidence Management Notifications Overview',
            'description' => 'Real-time custody dispatch center for unassigned vault exhibits, inter-unit chain-of-custody handovers, docket sign-offs, and officer activations.',
            'homeUrl'     => url('/Evidence_Management'),
        ],
        $unitKey === 'digital' => [
            'code'        => 'DPFU',
            'badge'       => 'DIGITAL & PHYSICAL FORENSICS UNIT',
            'overview'    => 'Digital & Physical Forensics Notifications Overview',
            'description' => 'Real-time unit dispatch center for seized electronic-device alerts, hardware custody handovers, pending forensic sign-offs, and examiner activations.',
            'homeUrl'     => url('/Digital_Forensics'),
        ],
        default => [
            'code'        => 'CSSU',
            'badge'       => 'CRIME SCENE SUPPORT UNIT',
            'overview'    => 'Crime Scene Support Notifications Overview',
            'description' => 'Real-time field command feed for unassigned scene exhibits, inter-unit case handovers, pending docket sign-offs, and field officer activations.',
            'homeUrl'     => url('/Crime_Scene_Support/head'),
        ],
    };

    $authUser = $viewer ?? auth()->user();
    $officerDisplayName = $authUser->fullname ?? session('user_name') ?? ($unitMeta['code'] . ' Command');
    $officerParts = preg_split('/\s+/', trim((string) $officerDisplayName)) ?: [];
    $officerInitials = count($officerParts) >= 2
        ? strtoupper(substr($officerParts[0], 0, 1) . substr(end($officerParts), 0, 1))
        : strtoupper(substr((string) $officerDisplayName, 0, 2));

    $totalFilteredInit = method_exists($notifications, 'total') ? $notifications->total() : count($notifications);
    $visibleCountInit = count($notifications);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $unitLabel }} Notifications | NACOC Forensic</title>

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
        @if($isForensicHead)
            @include('Forensic_Head.partials.sidebar')
        @else
            @include('Crime_Scene_Support.partials.sidebar')
        @endif

        <div class="flex-1 flex flex-col min-w-0 bg-[#F4F6F8] overflow-hidden">

            <!-- COMMAND TOPBAR -->
            <header class="h-16 bg-white border-b border-slate-200/80 px-4 sm:px-6 flex items-center justify-between gap-4 shrink-0 z-20">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 border border-slate-200/90 text-emerald-700 flex items-center justify-center shrink-0 shadow-2xs" aria-hidden="true">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <nav class="flex items-center gap-1.5 text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-emerald-700">
                            <a href="{{ $unitMeta['homeUrl'] }}" class="hover:underline truncate">{{ $unitLabel }}</a>
                            <span class="text-slate-300">/</span>
                            <span class="text-slate-500">Notifications</span>
                        </nav>
                        <h1 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight truncate">
                            {{ $unitLabel }} Notifications
                        </h1>
                    </div>
                </div>

                <!-- GLOBAL COMMAND SEARCH -->
                <div class="hidden md:flex items-center flex-1 max-w-md mx-2">
                    <div class="relative w-full">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400" aria-hidden="true">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </span>
                        <input type="search"
                               id="notificationsSearchInput"
                               value="{{ $search }}"
                               placeholder="Search Case #, Property ID, Commodity, Suspect, Officer..."
                               aria-label="Search unit notifications"
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

                    <button type="button"
                            id="markAllNotificationsReadBtn"
                            onclick="markAllNotificationsRead()"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-200/90 hover:bg-slate-300 text-slate-800 border border-slate-300/80 text-xs font-bold transition-colors">
                        <svg class="w-3.5 h-3.5 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Mark All Read</span>
                    </button>

                    <div class="hidden xl:flex items-center gap-2.5 pl-2.5 ml-1 border-l border-slate-200">
                        <div class="text-right">
                            <div class="text-xs font-extrabold text-slate-900 leading-tight truncate max-w-[140px]">{{ $officerDisplayName }}</div>
                            <div class="text-[10.5px] font-mono-code font-semibold text-emerald-700">{{ $unitMeta['code'] }} Command</div>
                        </div>
                        <div class="w-8 h-8 rounded-xl bg-slate-200 border border-slate-300/80 text-slate-800 flex items-center justify-center font-mono-code font-extrabold text-[11px]">
                            {{ $officerInitials ?: 'NC' }}
                        </div>
                    </div>
                </div>
            </header>

            <!-- SCROLLABLE WORKSPACE -->
            <main class="flex-1 overflow-y-auto px-4 sm:px-6 py-5 space-y-5">

                <!-- UNIT NOTIFICATIONS OVERVIEW & 6-CELL INTERACTIVE KPI STRIP -->
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

                    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 divide-x divide-y xl:divide-y-0 divide-slate-200/70">
                        <!-- KPI 1: TOTAL ALERTS -->
                        <button type="button"
                                onclick="quickFilterNotifications('all', 'all')"
                                class="p-4 sm:px-5 text-left hover:bg-slate-50/90 transition-colors group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10px] font-mono-code font-bold uppercase tracking-wider text-slate-500 group-hover:text-slate-800">Total Alerts</span>
                                <span class="w-6.5 h-6.5 rounded-lg bg-slate-100 text-slate-700 border border-slate-200/80 flex items-center justify-center shrink-0" aria-hidden="true">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                </span>
                            </div>
                            <div id="statTotalCount" class="text-2xl font-extrabold text-slate-900 tnum mt-1.5">{{ $stats['total'] }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Active unit queue</div>
                        </button>

                        <!-- KPI 2: UNREAD -->
                        <button type="button"
                                onclick="quickFilterNotifications('all', 'unread')"
                                class="p-4 sm:px-5 text-left hover:bg-emerald-50/40 transition-colors group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10px] font-mono-code font-bold uppercase tracking-wider text-emerald-700">Unread</span>
                                <span class="w-6.5 h-6.5 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200/80 flex items-center justify-center shrink-0" aria-hidden="true">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </span>
                            </div>
                            <div id="statUnreadCount" class="text-2xl font-extrabold text-slate-900 tnum mt-1.5">{{ $stats['unread'] }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Awaiting review</div>
                        </button>

                        <!-- KPI 3: CROSS-UNIT INTAKE -->
                        <button type="button"
                                onclick="quickFilterNotifications('cross_unit', 'all')"
                                class="p-4 sm:px-5 text-left hover:bg-amber-50/40 transition-colors group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10px] font-mono-code font-bold uppercase tracking-wider text-amber-800">Cross-Unit</span>
                                <span class="w-6.5 h-6.5 rounded-lg bg-amber-50 text-amber-700 border border-amber-200/80 flex items-center justify-center shrink-0" aria-hidden="true">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </span>
                            </div>
                            <div id="statCrossUnitCount" class="text-2xl font-extrabold text-slate-900 tnum mt-1.5">{{ $stats['cross_unit'] }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Exhibit &amp; subject alerts</div>
                        </button>

                        <!-- KPI 4: CASE HANDOVERS -->
                        <button type="button"
                                onclick="quickFilterNotifications('handover', 'all')"
                                class="p-4 sm:px-5 text-left hover:bg-sky-50/40 transition-colors group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10px] font-mono-code font-bold uppercase tracking-wider text-sky-800">Handovers</span>
                                <span class="w-6.5 h-6.5 rounded-lg bg-sky-50 text-sky-700 border border-sky-200/80 flex items-center justify-center shrink-0" aria-hidden="true">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                </span>
                            </div>
                            <div id="statHandoverCount" class="text-2xl font-extrabold text-slate-900 tnum mt-1.5">{{ $stats['handover'] }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Pending custody transfer</div>
                        </button>

                        <!-- KPI 5: PENDING SIGN-OFFS -->
                        <button type="button"
                                onclick="quickFilterNotifications('case_approval', 'all')"
                                class="p-4 sm:px-5 text-left hover:bg-teal-50/40 transition-colors group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10px] font-mono-code font-bold uppercase tracking-wider text-teal-800">Sign-Offs</span>
                                <span class="w-6.5 h-6.5 rounded-lg bg-teal-50 text-teal-700 border border-teal-200/80 flex items-center justify-center shrink-0" aria-hidden="true">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                </span>
                            </div>
                            <div id="statCaseApprovalCount" class="text-2xl font-extrabold text-slate-900 tnum mt-1.5">{{ $stats['case_approval'] }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Dockets awaiting approval</div>
                        </button>

                        <!-- KPI 6: STAFF ACTIVATIONS -->
                        <button type="button"
                                onclick="quickFilterNotifications('staff_approval', 'all')"
                                class="p-4 sm:px-5 text-left hover:bg-rose-50/40 transition-colors group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10px] font-mono-code font-bold uppercase tracking-wider text-rose-800">Staff Activations</span>
                                <span class="w-6.5 h-6.5 rounded-lg bg-rose-50 text-rose-700 border border-rose-200/80 flex items-center justify-center shrink-0" aria-hidden="true">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                </span>
                            </div>
                            <div id="statStaffApprovalCount" class="text-2xl font-extrabold text-slate-900 tnum mt-1.5">{{ $stats['staff_approval'] }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Pending officer accounts</div>
                        </button>
                    </div>
                </section>

                <!-- UNIFIED NOTIFICATIONS FEED CARD -->
                <section aria-label="Unit Notifications Feed" class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">

                    <!-- TWO-TIER FILTER TOOLBAR -->
                    <div class="border-b border-slate-200/80 divide-y divide-slate-100">

                        <!-- ROW 1: CATEGORY TABS + MOBILE SEARCH + VISIBLE COUNTER -->
                        <div class="p-4 sm:px-5 flex flex-col xl:flex-row xl:items-center justify-between gap-3">
                            <div class="flex flex-wrap items-center gap-1.5" id="categoryFilterTabs" role="tablist" aria-label="Filter notifications by category">
                                @php
                                    $catTabs = [
                                        'all'            => ['label' => 'All',               'count' => $stats['total'],          'id' => 'tabCountAll'],
                                        'cross_unit'     => ['label' => 'Cross-Unit Intake', 'count' => $stats['cross_unit'],     'id' => 'tabCountCross'],
                                        'handover'       => ['label' => 'Case Handovers',    'count' => $stats['handover'],       'id' => 'tabCountHandover'],
                                        'case_approval'  => ['label' => 'Pending Sign-Offs', 'count' => $stats['case_approval'],  'id' => 'tabCountCase'],
                                        'staff_approval' => ['label' => 'Staff Activations', 'count' => $stats['staff_approval'], 'id' => 'tabCountStaff'],
                                    ];
                                @endphp
                                @foreach($catTabs as $catKey => $catMeta)
                                    @php $isCatActive = $category === $catKey; @endphp
                                    <button type="button"
                                            role="tab"
                                            aria-selected="{{ $isCatActive ? 'true' : 'false' }}"
                                            data-category="{{ $catKey }}"
                                            class="notif-cat-btn inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all {{ $isCatActive ? 'bg-slate-200/95 text-slate-900 border-slate-300 shadow-2xs active-tab' : 'bg-slate-100/80 text-slate-600 border-transparent hover:bg-slate-200/60 hover:text-slate-900' }}">
                                        <span>{{ $catMeta['label'] }}</span>
                                        <span id="{{ $catMeta['id'] }}"
                                              class="px-1.5 py-0.2 rounded-md text-[10px] font-mono-code font-extrabold tnum {{ $isCatActive ? 'bg-emerald-600 text-white' : 'bg-white text-slate-700 border border-slate-200/80' }}">
                                            {{ $catMeta['count'] }}
                                        </span>
                                    </button>
                                @endforeach
                            </div>

                            <div class="flex items-center justify-between xl:justify-end gap-2.5">
                                <div class="relative flex-1 md:hidden">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    </span>
                                    <input type="search"
                                           id="notificationsMobileSearchInput"
                                           value="{{ $search }}"
                                           placeholder="Search notifications..."
                                           aria-label="Search notifications"
                                           class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:bg-white focus:border-emerald-600 focus:outline-none">
                                </div>

                                <div class="flex items-center gap-2 shrink-0 text-[11px] font-mono-code">
                                    <span class="px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-200/80 text-emerald-900 font-bold whitespace-nowrap tnum">
                                        Showing <span id="visibleNotifCount">{{ $visibleCountInit }}</span> of <span id="totalNotifCount">{{ $totalFilteredInit }}</span>
                                    </span>
                                    <span id="activeNotifFilterBadge"
                                          class="{{ ($category !== 'all' || $statusFilter !== 'all' || trim((string) $search) !== '') ? '' : 'hidden' }} text-[10px] text-emerald-800 bg-emerald-100 border border-emerald-200 px-2 py-0.5 rounded-md font-bold">
                                        Filtered
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- ROW 2: READ STATUS, PER-PAGE & RESET -->
                        <div class="px-4 py-2.5 sm:px-5 bg-slate-50/70 flex flex-wrap items-center justify-between gap-3 text-xs">
                            <div class="flex items-center gap-2 flex-wrap">
                                <label for="statusFilterSelect" class="text-[10.5px] font-bold text-slate-500 uppercase font-mono-code tracking-wider">Read State:</label>
                                <select id="statusFilterSelect"
                                        class="px-3 py-1 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 focus:outline-none focus:border-emerald-600">
                                    <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Notifications</option>
                                    <option value="unread" {{ $statusFilter === 'unread' ? 'selected' : '' }}>Unread Only</option>
                                    <option value="read" {{ $statusFilter === 'read' ? 'selected' : '' }}>Read Only</option>
                                </select>
                            </div>

                            <div class="flex items-center gap-2 flex-wrap">
                                <label for="perPageSelect" class="sr-only">Notifications per page</label>
                                <select id="perPageSelect"
                                        class="px-2.5 py-1 bg-white border border-slate-200 rounded-lg text-[11px] font-mono-code font-semibold text-slate-700 focus:outline-none focus:border-emerald-600">
                                    @foreach([10, 15, 25, 50] as $pp)
                                        <option value="{{ $pp }}" {{ (int) $perPage === $pp ? 'selected' : '' }}>{{ $pp }} / page</option>
                                    @endforeach
                                </select>

                                <button type="button"
                                        onclick="resetNotificationFilters()"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white hover:bg-rose-50 text-slate-600 hover:text-rose-700 border border-slate-200 hover:border-rose-200 text-[11px] font-mono-code font-bold transition-colors"
                                        title="Reset all notification filters">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    <span>Reset</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- SKELETON LOADER -->
                    <div id="notificationsSkeleton" class="hidden divide-y divide-slate-200/70 bg-white" aria-hidden="true">
                        @for($i = 0; $i < 4; $i++)
                            <div class="px-5 py-4 flex items-start gap-3.5">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200 shrink-0"></div>
                                <div class="flex-1 space-y-2">
                                    <div class="flex items-center gap-2">
                                        <div class="h-4 w-16 bg-slate-200/80 rounded"></div>
                                        <div class="h-4 w-28 bg-slate-100 rounded"></div>
                                    </div>
                                    <div class="h-4 w-2/3 bg-slate-200/80 rounded"></div>
                                    <div class="h-3 w-1/2 bg-slate-100 rounded"></div>
                                </div>
                            </div>
                        @endfor
                    </div>

                    <!-- FEED CONTAINER -->
                    <div id="notificationsFeedContainer">
                        @include('partials.notifications_feed')
                    </div>
                </section>

            </main>
        </div>
    </div>

    <script>
        (function() {
            const ENDPOINT = '{{ url($currentEndpoint) }}';
            const MARK_READ_URL = '{{ route('notifications.mark_read') }}';
            const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const UNIT_LABEL = @json($unitLabel);

            const feedContainer = document.getElementById('notificationsFeedContainer');
            const skeletonEl = document.getElementById('notificationsSkeleton');
            const searchInput = document.getElementById('notificationsSearchInput');
            const mobileSearchInput = document.getElementById('notificationsMobileSearchInput');
            const statusSelect = document.getElementById('statusFilterSelect');
            const perPageSelect = document.getElementById('perPageSelect');
            const categoryTabs = document.getElementById('categoryFilterTabs');

            let activeCategory = @json($category);
            let activeStatus = @json($statusFilter);
            let activeSearch = @json($search);
            let activePerPage = @json((int) $perPage);
            let activePage = @json((int) ($notifications->currentPage() ?? 1));
            let lastSignature = @json($initialSignature ?? null);
            let debounceTimer = null;
            let isFetching = false;

            function updateClock() {
                const now = new Date();
                const utcString = now.toISOString().replace('T', ' ').substring(0, 19) + ' UTC';
                const clockEl = document.getElementById('liveClock');
                if (clockEl) clockEl.textContent = utcString;
            }
            setInterval(updateClock, 1000);
            updateClock();

            function broadcastBadgeSync() {
                try {
                    localStorage.setItem('nacoc_badge_sync', String(Date.now()));
                } catch (e) {}
            }

            function updateCategoryTabVisuals(selectedCat) {
                if (!categoryTabs) return;
                categoryTabs.querySelectorAll('button[data-category]').forEach(function(btn) {
                    const cat = btn.getAttribute('data-category') || 'all';
                    const isSelected = cat === selectedCat;
                    btn.setAttribute('aria-selected', isSelected ? 'true' : 'false');
                    btn.className = 'notif-cat-btn inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all ' + (
                        isSelected
                            ? 'bg-slate-200/95 text-slate-900 border-slate-300 shadow-2xs active-tab'
                            : 'bg-slate-100/80 text-slate-600 border-transparent hover:bg-slate-200/60 hover:text-slate-900'
                    );
                    const countSpan = btn.querySelector('span[id^="tabCount"]');
                    if (countSpan) {
                        countSpan.className = 'px-1.5 py-0.2 rounded-md text-[10px] font-mono-code font-extrabold tnum ' + (
                            isSelected
                                ? 'bg-emerald-600 text-white'
                                : 'bg-white text-slate-700 border border-slate-200/80'
                        );
                    }
                });
            }

            function updateActiveFilterBadge() {
                const isFiltered = Boolean(
                    (activeCategory && activeCategory !== 'all') ||
                    (activeStatus && activeStatus !== 'all') ||
                    (activeSearch && activeSearch.trim() !== '')
                );
                const badge = document.getElementById('activeNotifFilterBadge');
                if (badge) badge.classList.toggle('hidden', !isFiltered);
            }

            function syncMetaFromDom(explicitStats) {
                const meta = document.getElementById('notificationsFeedMeta');
                if (meta) {
                    const visEl = document.getElementById('visibleNotifCount');
                    const totEl = document.getElementById('totalNotifCount');
                    if (visEl) visEl.textContent = meta.getAttribute('data-visible-count') || '0';
                    if (totEl) totEl.textContent = meta.getAttribute('data-total-filtered') || '0';
                }

                const stats = explicitStats || (meta && meta.hasAttribute('data-stat-total') ? {
                    total: Number(meta.getAttribute('data-stat-total') || 0),
                    unread: Number(meta.getAttribute('data-stat-unread') || 0),
                    cross_unit: Number(meta.getAttribute('data-stat-cross') || 0),
                    handover: Number(meta.getAttribute('data-stat-handover') || 0),
                    case_approval: Number(meta.getAttribute('data-stat-case') || 0),
                    staff_approval: Number(meta.getAttribute('data-stat-staff') || 0),
                } : null);

                if (!stats) return;

                const map = {
                    statTotalCount: stats.total,
                    statUnreadCount: stats.unread,
                    statCrossUnitCount: stats.cross_unit,
                    statHandoverCount: stats.handover,
                    statCaseApprovalCount: stats.case_approval,
                    statStaffApprovalCount: stats.staff_approval,
                    tabCountAll: stats.total,
                    tabCountCross: stats.cross_unit,
                    tabCountHandover: stats.handover,
                    tabCountCase: stats.case_approval,
                    tabCountStaff: stats.staff_approval,
                };
                Object.keys(map).forEach(function(id) {
                    const el = document.getElementById(id);
                    if (el && map[id] !== undefined) {
                        el.textContent = String(map[id]);
                    }
                });

                const sidebarBadge = document.getElementById('sidebarNotificationsBadge');
                if (sidebarBadge && stats.unread !== undefined) {
                    const count = parseInt(stats.unread, 10) || 0;
                    sidebarBadge.textContent = String(count);
                    if (count > 0) {
                        sidebarBadge.classList.remove('hidden');
                        sidebarBadge.style.display = 'inline-flex';
                    } else {
                        sidebarBadge.classList.add('hidden');
                        sidebarBadge.style.display = 'none';
                    }
                }
            }

            function syncBrowserUrl() {
                try {
                    const url = new URL(window.location.href);
                    if (activeCategory && activeCategory !== 'all') url.searchParams.set('category', activeCategory);
                    else url.searchParams.delete('category');

                    if (activeStatus && activeStatus !== 'all') url.searchParams.set('status', activeStatus);
                    else url.searchParams.delete('status');

                    if (activeSearch && activeSearch.trim() !== '') url.searchParams.set('search', activeSearch.trim());
                    else url.searchParams.delete('search');

                    if (activePerPage && Number(activePerPage) !== 15) url.searchParams.set('per_page', String(activePerPage));
                    else url.searchParams.delete('per_page');

                    if (activePage && Number(activePage) > 1) url.searchParams.set('page', String(activePage));
                    else url.searchParams.delete('page');

                    url.searchParams.delete('poll');
                    window.history.replaceState({}, '', url.toString());
                } catch (e) {}
            }

            async function loadNotifications(showSkeleton, forceSwap) {
                if (isFetching) return;
                if (!showSkeleton && !forceSwap && document.hidden) return;

                isFetching = true;
                updateActiveFilterBadge();

                if (showSkeleton && skeletonEl && feedContainer) {
                    skeletonEl.classList.remove('hidden');
                    feedContainer.classList.add('hidden');
                }

                const params = new URLSearchParams(window.location.search);
                if (activeCategory && activeCategory !== 'all') params.set('category', activeCategory);
                else params.delete('category');

                if (activeStatus && activeStatus !== 'all') params.set('status', activeStatus);
                else params.delete('status');

                if (activeSearch && activeSearch.trim() !== '') params.set('search', activeSearch.trim());
                else params.delete('search');

                if (activePerPage) params.set('per_page', String(activePerPage));
                if (activePage > 1) params.set('page', String(activePage));
                else params.delete('page');

                params.set('poll', '1');

                try {
                    const resp = await fetch(ENDPOINT + '?' + params.toString(), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    });
                    if (!resp.ok) return;

                    const data = await resp.json();
                    if (!data || !data.success) return;

                    if (data.stats) {
                        syncMetaFromDom(data.stats);
                    }

                    const unchanged = !showSkeleton && !forceSwap && data.signature && data.signature === lastSignature;
                    if (data.signature) {
                        lastSignature = data.signature;
                    }
                    if (unchanged) return;

                    if (feedContainer && typeof data.html === 'string') {
                        feedContainer.innerHTML = data.html;
                        syncMetaFromDom(data.stats || null);
                    }
                } catch (e) {
                } finally {
                    isFetching = false;
                    if (skeletonEl && feedContainer) {
                        skeletonEl.classList.add('hidden');
                        feedContainer.classList.remove('hidden');
                    }
                }
            }

            if (categoryTabs) {
                categoryTabs.querySelectorAll('button[data-category]').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        activeCategory = btn.getAttribute('data-category') || 'all';
                        activePage = 1;
                        updateCategoryTabVisuals(activeCategory);
                        syncBrowserUrl();
                        loadNotifications(true, true);
                    });
                });
            }

            if (statusSelect) {
                statusSelect.addEventListener('change', function() {
                    activeStatus = statusSelect.value || 'all';
                    activePage = 1;
                    syncBrowserUrl();
                    loadNotifications(true, true);
                });
            }

            if (perPageSelect) {
                perPageSelect.addEventListener('change', function() {
                    activePerPage = parseInt(perPageSelect.value, 10) || 15;
                    activePage = 1;
                    syncBrowserUrl();
                    loadNotifications(true, true);
                });
            }

            function handleSearchTyping(val, sourceEl) {
                activeSearch = val || '';
                activePage = 1;
                if (sourceEl !== searchInput && searchInput && searchInput.value !== activeSearch) {
                    searchInput.value = activeSearch;
                }
                if (sourceEl !== mobileSearchInput && mobileSearchInput && mobileSearchInput.value !== activeSearch) {
                    mobileSearchInput.value = activeSearch;
                }
                if (debounceTimer) clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    debounceTimer = null;
                    syncBrowserUrl();
                    loadNotifications(true, true);
                }, 300);
            }

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    handleSearchTyping(searchInput.value, searchInput);
                });
            }
            if (mobileSearchInput) {
                mobileSearchInput.addEventListener('input', function() {
                    handleSearchTyping(mobileSearchInput.value, mobileSearchInput);
                });
            }

            if (feedContainer) {
                feedContainer.addEventListener('click', function(e) {
                    const pageLink = e.target.closest('.notifications-pagination a[href]');
                    if (!pageLink) return;
                    try {
                        const targetUrl = new URL(pageLink.href, window.location.origin);
                        const nextPage = parseInt(targetUrl.searchParams.get('page') || '1', 10) || 1;
                        e.preventDefault();
                        activePage = nextPage;
                        syncBrowserUrl();
                        loadNotifications(true, true);
                    } catch (err) {}
                });
            }

            window.quickFilterNotifications = function(categoryValue, statusValue) {
                activeCategory = categoryValue || 'all';
                activeStatus = statusValue || 'all';
                activePage = 1;
                if (statusSelect) statusSelect.value = activeStatus;
                updateCategoryTabVisuals(activeCategory);
                syncBrowserUrl();
                loadNotifications(true, true);
            };

            window.resetNotificationFilters = function() {
                activeCategory = 'all';
                activeStatus = 'all';
                activeSearch = '';
                activePage = 1;
                if (searchInput) searchInput.value = '';
                if (mobileSearchInput) mobileSearchInput.value = '';
                if (statusSelect) statusSelect.value = 'all';
                updateCategoryTabVisuals('all');
                syncBrowserUrl();
                loadNotifications(true, true);
            };

            window.copyNotificationRef = function(refValue) {
                if (!refValue || !navigator.clipboard) return;
                navigator.clipboard.writeText(refValue).then(function() {
                    if (typeof window.showFlashToast === 'function') {
                        window.showFlashToast('Copied ' + refValue + ' to clipboard.', 'success', 'Reference Copied');
                    }
                }).catch(function() {});
            };

            window.markSingleNotificationRead = async function(id, btn) {
                if (!id) return;
                if (btn) btn.disabled = true;
                try {
                    const resp = await fetch(MARK_READ_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN,
                        },
                        body: JSON.stringify({ id: id, from: UNIT_LABEL })
                    });
                    if (resp.ok) {
                        broadcastBadgeSync();
                        await loadNotifications(false, true);
                    }
                } catch (e) {
                } finally {
                    if (btn) btn.disabled = false;
                }
            };

            window.markAllNotificationsRead = async function() {
                const btn = document.getElementById('markAllNotificationsReadBtn');
                if (btn) btn.disabled = true;
                try {
                    const resp = await fetch(MARK_READ_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN,
                        },
                        body: JSON.stringify({ all: true, from: UNIT_LABEL })
                    });
                    if (resp.ok) {
                        broadcastBadgeSync();
                        await loadNotifications(false, true);
                        if (typeof window.showFlashToast === 'function') {
                            window.showFlashToast('All active unit notifications marked as read.', 'success', 'Queue Updated');
                        }
                    }
                } catch (e) {
                } finally {
                    if (btn) btn.disabled = false;
                }
            };

            window.acknowledgeHandoverNotification = async function(handoverId, btn) {
                if (!handoverId) return;
                if (btn) btn.disabled = true;
                try {
                    const resp = await fetch('/notifications/handovers/' + encodeURIComponent(handoverId) + '/acknowledge', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN,
                        },
                        body: JSON.stringify({})
                    });
                    if (resp.ok) {
                        const payload = await resp.json().catch(function() { return null; });
                        broadcastBadgeSync();
                        await loadNotifications(false, true);
                        if (typeof window.showFlashToast === 'function') {
                            window.showFlashToast((payload && payload.message) ? payload.message : 'Case handover acknowledged.', 'success', 'Handover Acknowledged');
                        }
                    }
                } catch (e) {
                } finally {
                    if (btn) btn.disabled = false;
                }
            };

            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                    const target = (searchInput && searchInput.offsetParent !== null) ? searchInput : mobileSearchInput;
                    if (target) {
                        e.preventDefault();
                        target.focus();
                        target.select();
                    }
                }
            });

            // Diff-guarded 5-second silent background polling
            setInterval(function() {
                if (debounceTimer === null) {
                    loadNotifications(false, false);
                }
            }, 5000);

            document.addEventListener('visibilitychange', function() {
                if (!document.hidden && debounceTimer === null) {
                    loadNotifications(false, false);
                }
            });

            window.addEventListener('focus', function() {
                if (debounceTimer === null) {
                    loadNotifications(false, false);
                }
            });

            window.addEventListener('storage', function(e) {
                if (e.key === 'nacoc_badge_sync') {
                    loadNotifications(false, true);
                }
            });
        })();
    </script>
</body>
</html>
