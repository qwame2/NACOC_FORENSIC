@php
    $presenter = ($presenter ?? null) instanceof \App\Services\CaseShowReviewPresenter
        ? $presenter
        : app(\App\Services\CaseShowReviewPresenter::class)->presentForUnit($case, 'lab', request(), auth()->user());
    extract($presenter->toViewData(), EXTR_SKIP);
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Case Review File — {{ $case->case_number }} | NACOC Forensic Department</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Tailwind / Vite & Barcode Engine -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    @endif


    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #F8FAFC;
            color: #0F172A;
        }

        .font-mono-code {
            font-family: 'JetBrains Mono', monospace;
        }

        .top-header {
            background: #FFFFFF;
            border-bottom: 1px solid #E2E8F0;
        }

        .btn-primary {
            background: #059669;
            border: 1px solid #10b981;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
            color: white;
            font-weight: 700;
            font-size: 12px;
            padding: 10px 20px;
            border-radius: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            background: #047857;
            box-shadow: 0 6px 18px rgba(16, 185, 129, 0.35);
            transform: translateY(-1px);
        }

        .btn-decline {
            background: #dc2626;
            border: 1px solid #f43f5e;
            box-shadow: 0 4px 12px rgba(225, 29, 72, 0.25);
            color: white;
            font-weight: 700;
            font-size: 12px;
            padding: 10px 20px;
            border-radius: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-decline:hover {
            background: #b91c1c;
            box-shadow: 0 6px 18px rgba(225, 29, 72, 0.35);
            transform: translateY(-1px);
        }

        .btn-back {
            background: #FFFFFF;
            border: 1px solid #CBD5E1;
            color: #334155;
            font-weight: 700;
            font-size: 12px;
            padding: 9px 16px;
            border-radius: 10px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-back:hover {
            background: #F1F5F9;
            color: #0F172A;
            border-color: #94A3B8;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
        }

        .status-await {
            background: #FEF3C7;
            color: #D97706;
            border: 1px solid #FCD34D;
        }

        .status-done {
            background: #ECFDF5;
            color: #059669;
            border: 1px solid #A7F3D0;
        }

        .status-declined {
            background: #FEF2F2;
            color: #DC2626;
            border: 1px solid #FCA5A5;
        }

        .dossier-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
            overflow: hidden;
        }
    </style>
</head>
@php @endphp

<body class="min-h-screen flex flex-col antialiased">

    <div class="flex h-screen overflow-hidden">

        <!-- LEFT SIDEBAR -->
                    @include('staff.partials.sidebar')
        
        <!-- MAIN WORKSPACE -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-slate-50">

            <!-- TOP NAVBAR -->
            <header class="top-header h-16 px-6 flex items-center justify-between shrink-0 z-20 gap-4">
                <div class="flex items-center gap-4 shrink-0">
                                            <a href="{{ url('/Lab_Analysis/staff/case') }}" class="btn-back">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Back to Case Register
                        </a>
                                        <div class="h-6 w-px bg-slate-200"></div>
                    <div class="flex items-center gap-2">
                        <span
                            class="text-xs font-mono-code font-extrabold text-slate-700">{{ $case->case_number }}</span>
                    </div>
                </div>

                <!-- GENERAL SEARCH INPUT -->
                <div class="flex items-center flex-1 max-w-md mx-4">
                    <div class="relative w-full">
                        <span
                            class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text" id="globalSearch" placeholder="Search case #, suspect, evidence ID..."
                            class="w-full pl-10 pr-12 py-1.5 bg-slate-100/80 border border-slate-200 rounded-xl text-xs text-slate-900 font-mono-code focus:outline-none focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/15 transition-all"
                            onkeydown="if(event.key === 'Enter' && this.value) window.location.href='/Lab_Analysis/staff/case?search=' + encodeURIComponent(this.value);">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <kbd
                                class="px-1.5 py-0.5 text-[10px] font-mono-code font-semibold text-slate-400 bg-white rounded border border-slate-200 shadow-2xs">Ctrl
                                K</kbd>
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-4 shrink-0">
                    <!-- NOTIFICATION BELL ICON & GENERAL ALERTS -->
                    <div class="relative">
                        @php @endphp
                        <button type="button" id="notificationBellBtn" onclick="toggleNotificationDropdown()"
                            class="p-2 rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors relative border-none bg-transparent cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            @if ($totalAlertsCount > 0)
                                <span
                                    class="ml-1 px-1.5 py-0.5 bg-red-600 text-white text-[9px] font-mono-code font-bold rounded leading-none">{{ $totalAlertsCount }}</span>
                            @endif
                        </button>

                        <!-- NOTIFICATIONS & GENERAL ALERTS DROPDOWN MENU -->
                        <div id="notificationDropdown"
                            class="hidden absolute right-0 mt-2 w-96 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden font-sans">
                            <!-- HEADER -->
                            <div class="p-4 bg-slate-900 text-white flex items-center justify-between">
                                <div>
                                    <div
                                        class="text-[10px] font-mono-code text-blue-400 font-bold uppercase tracking-wider">
                                        DIRECTORATE COMMAND</div>
                                    <div class="text-xs font-black tracking-wide">General System &amp; Department Alerts
                                    </div>
                                </div>
                                <span
                                    class="px-2.5 py-1 text-[10px] font-extrabold bg-blue-500/20 text-blue-300 rounded-full font-mono-code border border-blue-500/30">
                                    {{ $totalAlertsCount }} Active Alerts
                                </span>
                            </div>

                            <!-- ALERTS LIST -->
                            <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto">

                                <!-- ALERT 1: CASE APPROVALS -->
                                <a href="{{ url('/Lab_Analysis/staff/case') }}"
                                    class="p-3.5 flex items-start space-x-3 hover:bg-slate-50 transition-colors text-decoration-none block">
                                    <div
                                        class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-600 border border-amber-500/20 flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-0.5">
                                            <span
                                                class="text-[10px] font-extrabold font-mono-code text-amber-700 uppercase">Case
                                                Approvals</span>
                                            <span class="text-[9px] font-bold text-slate-400 font-mono-code">STATUS</span>
                                        </div>
                                        <div class="text-xs font-bold text-slate-900 truncate">
                                            @if ($pendingCasesNotif > 0)
                                                {{ $pendingCasesNotif }} Case Dossiers Awaiting Executive Sign-off
                                            @else
                                                All Case Dossiers Authorized
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 leading-snug">
                                            @if ($pendingCasesNotif > 0)
                                                Pending findings certificates and official seal approval required.
                                            @else
                                                No outstanding case dossier sign-offs pending.
                                            @endif
                                        </div>
                                    </div>
                                </a>

                                <!-- ALERT 3: EVIDENCE VAULT TRANSFERS -->
                                <a href="{{ url('/Lab_Analysis/staff/case') }}"
                                    class="p-3.5 flex items-start space-x-3 hover:bg-slate-50 transition-colors text-decoration-none block">
                                    <div
                                        class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 8h14M5 8a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v1a2 2 0 01-2 2M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-0.5">
                                            <span
                                                class="text-[10px] font-extrabold font-mono-code text-emerald-700 uppercase">Evidence
                                                Vault</span>
                                            <span class="text-[9px] font-bold text-slate-400 font-mono-code">STATUS</span>
                                        </div>
                                        <div class="text-xs font-bold text-slate-900 truncate">
                                            @if ($pendingHandoversNotif > 0)
                                                {{ $pendingHandoversNotif }} Pending Evidence Transfer(s)
                                            @else
                                                All Custody Transfers Acknowledged
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 leading-snug">
                                            @if ($pendingHandoversNotif > 0)
                                                Chain of custody handovers awaiting recipient acknowledgment.
                                            @else
                                                Evidence vault records and chain of custody are up to date.
                                            @endif
                                        </div>
                                    </div>
                                </a>

                                <!-- ALERT 4: SYSTEM INTEGRITY AUDIT -->
                                <div class="p-3.5 flex items-start space-x-3 bg-emerald-50/40">
                                    <div
                                        class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-0.5">
                                            <span
                                                class="text-[10px] font-extrabold font-mono-code text-emerald-700 uppercase">Chain
                                                Integrity Audit</span>
                                            <span class="text-[9px] font-bold text-emerald-600 font-mono-code">SYSTEM
                                                OK</span>
                                        </div>
                                        <div class="text-xs font-bold text-slate-900 truncate">Chain of Custody Verified</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 leading-snug">Audit trail active and synchronized across forensic directorates.</div>
                                    </div>
                                </div>

                            </div>

                            <!-- FOOTER -->
                            <div class="p-3 bg-slate-50 border-t border-slate-200 text-center">
                                <a href="{{ url('/Lab_Analysis/staff/case') }}"
                                    class="text-[11px] font-bold text-blue-600 hover:text-blue-800 font-mono-code uppercase tracking-wider text-decoration-none">
                                    View Executive Case Register &rarr;
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="text-right hidden sm:block">
                        <div class="text-xs font-mono-code font-semibold text-slate-700" id="liveClock"></div>
                        <div class="text-[10px] font-mono-code text-blue-600 font-bold">NACOC FORENSIC DEPARTMENT</div>
                    </div>
                </div>
            </header>

            <!-- SCROLLABLE CONTENT -->
            <main class="flex-1 overflow-y-auto p-6 space-y-6">

                @include('partials.flash')

                <!-- HERO DOSSIER HEADER BANNER -->
                <div
                    class="relative overflow-hidden rounded-2xl p-6 sm:p-8 bg-gray-500 bg-grey-500 text-white shadow-xl border border-gray-400">
                    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        <div class="space-y-3 flex-1">
                            <div class="flex items-center gap-3 flex-wrap">
                                <span
                                    class="px-2 py-0.5 rounded text-[10px] font-bold font-mono-code bg-slate-800 text-amber-400 border border-slate-700">{{ $priority }}
                                    PRIORITY</span>
                                <span class="text-slate-400">|</span>
                                <span id="headerStatusBadge">
                                    @if ($status === 'Approved' || $status === 'Authorized')
                                        <span class="status-badge status-done">
                                            <svg class="w-3 h-3 mr-1 text-emerald-400" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Authorized
                                        </span>
                                    @elseif($status === 'Declined')
                                        <span class="status-badge status-declined">
                                            Declined
                                        </span>
                                    @else
                                        <span class="status-badge bg-white text-slate-900 border border-slate-300">
                                            {{ $status }}
                                        </span>
                                    @endif
                                </span>
                            </div>

                            <div>
                                <h1
                                    class="text-2xl sm:text-3xl font-black text-white tracking-tight flex items-center gap-3">
                                    {{ $case->title }}
                                    <span class="text-base text-white font-normal">|
                                        {{ $case->case_number }}</span>
                                </h1>
                                <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-3xl leading-relaxed">
                                    The complete case record is available for your review. Please carefully review the
                                    registered details below before proceeding with the executive sign-off.
                                </p>
                            </div>
                        </div>

                        <!-- QUICK ACTIONS IN HERO -->
                        <div class="flex items-center gap-3 shrink-0">

                            @include('partials.edit_case_button', [
                                'case' => $case,
                                'editFrom' => 'Lab_Analysis',
                                'unitLabel' => 'Lab Analysis',
                            ])
                            <a href="/cases/{{ $case->case_number }}/pdf" target="_blank"
                                class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-xl border border-slate-700 transition-all flex items-center gap-2 cursor-pointer no-underline">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                <span>Print Case PDF</span>
                            </a>
                        </div>
                    </div>
                </div>

                @include('partials.unit-progress-pipeline', [
                    'unitProgress' => $unitProgress,
                    'unitsList' => $unitsList,
                    'completedUnitsCount' => $completedUnitsCount,
                    'totalUnitsCount' => $totalUnitsCount,
                    'hofDone' => $hofDone,
                    'isCaseController' => $isCaseController,
                    'currentCaseStatus' => $currentCaseStatus,
                    'caseCreatorName' => $caseCreatorName,
                    'caseCreatorUnit' => $caseCreatorUnit,
                    'caseCreatedAt' => $caseCreatedAt,
                    'statusUpdatedBy' => $statusUpdatedBy,
                    'statusUpdatedByUnit' => $statusUpdatedByUnit,
                    'statusUpdatedAt' => $statusUpdatedAt,
                ])

                <!-- SECTION 1: MASTER CASE TITLE & CLASSIFICATION -->
                <div class="dossier-card p-6 border border-slate-200">
                    <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-200">
                        <div class="flex items-center gap-3">
                            <div>
                                <h2 class="text-base font-black text-slate-900">Case Title &amp; Classification</h2>
                                <p class="text-xs text-slate-500">Case title, reference, priority, and division
                                    assignment</p>
                            </div>
                        </div>
                        @if (!empty($case->unit_name))
                            <span class="px-3 py-1 rounded-lg text-xs font-mono-code font-bold {{ $unitBadgeColor }}">
                                {{ $case->unit_name }}
                            </span>
                        @endif
                    </div>
                    @php
                        $dispLabNumber = !empty($case->laboratory_number) && $case->laboratory_number !== 'Pending Assignment'
                            ? $case->laboratory_number
                            : (!empty($savedLabNumber) && $savedLabNumber !== 'N/A' && $savedLabNumber !== 'Pending Assignment' ? $savedLabNumber : null);
                    @endphp
                    <div class="grid grid-cols-1 md:grid-cols-{{ !empty($dispLabNumber) ? '4' : '3' }} gap-4 text-xs">
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                            <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Case Name /
                                Legal Title</div>
                            <div class="font-extrabold text-slate-900 text-sm mt-1">{{ $case->title ?: 'N/A' }}</div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                            <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Case Reference
                                Number</div>
                            <div class="font-extrabold text-blue-600 font-mono-code text-sm mt-1">
                                {{ $case->case_number ?: 'N/A' }}</div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                            <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Operation Type
                            </div>
                            <div class="font-bold text-slate-800 text-sm mt-1">
                                {{ $case->operation_code_name ?: $case->operation_code ?? 'N/A' }}</div>
                        </div>
                        @if (!empty($case->search_warrant_document))
                            <div class="p-3.5 rounded-xl bg-blue-50/70 border border-blue-200">
                                <div class="text-[10px] font-mono-code font-bold text-blue-700 uppercase">Search Warrant Document</div>
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($case->search_warrant_document) }}" target="_blank" rel="noopener"
                                    class="inline-flex items-center space-x-1.5 mt-1 text-xs font-extrabold text-blue-700 hover:text-blue-900 underline">
                                    <span>View / Download Warrant</span>
                                </a>
                            </div>
                        @endif
                        @if (!empty($dispLabNumber))
                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Laboratory Number</div>
                                <div class="font-extrabold text-emerald-700 font-mono-code text-sm mt-1">{{ $dispLabNumber }}</div>
                            </div>
                        @endif
                        @if (!empty($case->description_of_exhibits))
                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 md:col-span-{{ !empty($dispLabNumber) ? '4' : '3' }}">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Description of Exhibit(s)</div>
                                <div class="font-bold text-slate-900 text-sm mt-1 leading-relaxed">{{ $case->description_of_exhibits }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                @php @endphp

                @foreach ($suspectsList as $sIdx => $sItem)
                    
                    @include('partials.suspect-mugshots', [
                        'suspect' => $sItem,
                        'index' => $sIdx,
                    ])

                    @include('partials.suspect-dossier', [
                        'suspect' => $sItem,
                        'case' => $case,
                    ])
                @endforeach

                @php @endphp
                                @php @endphp

                @if (!empty($isJointExhibitCase) || !empty($jointCaseExhibits) || !empty($allCaseExhibits))
                    {{-- {{ $isMultiple ? 'PRIMARY & CO-SUSPECT' : 'PRIMARY' }} EXHIBIT DOSSIER ({{ $isMultiple ? 'JOINT OWNERSHIP SECTION' : 'PRIMARY SUSPECT SECTION' }} ON TOP OF SEIZURES INVENTORY) --}}
                    @include('partials.exhibit_dossier_card', [
                        'dossierBadgeText' => $dossierBadgeText,
                        'dossierHeadingText' => $dossierHeadingText,
                        'dossierHeadingName' => $jointDossierHeadingName,
                        'dossierExhibits' => !empty($jointCaseExhibits) ? $jointCaseExhibits : ($allCaseExhibits ?? []),
                        'dossierPhotos' => $allCaseExhibitPhotos,
                        'formatPhotoUrlHelper' => $formatPhotoUrlHelper,
                        'extractAllPhotosHelper' => $extractAllPhotosHelper,
                        'case' => $case,
                    ])

                    {{-- LABORATORY EXAMINATION RESULT (JOINT OWNERSHIP SECTION ON TOP OF SEIZURES INVENTORY) --}}
                    @if (!empty($savedJointLabExamCards) && count($savedJointLabExamCards) > 0)
                        @foreach ($savedJointLabExamCards as $labCard)
                            @include('partials.lab-examination-card', [
                                'labExam' => $labCard,
                                'labHeadingName' => $labCard['labHeadingName'] ?? ($savedJointLabHeadingName ?? $jointDossierHeadingName),
                                'case' => $case,
                            ])
                        @endforeach
                    @elseif (!empty($savedJointLabExam) && !empty($savedJointLabExam['has_saved']))
                        @include('partials.lab-examination-card', [
                            'labExam' => $savedJointLabExamCard ?? $savedJointLabExam,
                            'labHeadingName' => $savedJointLabHeadingName ?? $jointDossierHeadingName,
                            'case' => $case,
                        ])
                    @endif
                @endif

                {{-- ATTACHED REPORT FILES CONTAINER ALWAYS ON TOP OF SEIZURES (COMMODITIES INVENTORY) --}}
                @php
                    $allCaseReportFiles = collect($allGsaForReview ?? [])
                        ->merge(collect($allNlrForReview ?? []))
                        ->merge(collect($jointCaseReportFiles ?? []))
                        ->unique(function ($item) {
                            return !empty($item->file_path) ? (string) $item->file_path : (get_class($item) . '_' . ($item->id ?? spl_object_id($item)));
                        })
                        ->values();
                @endphp
                @if ($allCaseReportFiles->count() > 0)
                    @include('partials.gsa_report_files', [
                        'gsaFiles' => $allCaseReportFiles,
                        'gsaForName' => null,
                    ])
                @endif

                @include('partials.exhibit-table', [
                    'exhibits' => $allCaseExhibits,
                    'defaultOwnerName' => $case->suspect_name ?? 'Primary Suspect',
                    'case' => $case,
                    'suspects' => $suspectsList ?? [],
                ])

                <!-- SECTION 5: EXAMINATION INFORMATION (LABORATORY ANALYSIS DIRECTORATE) -->
                                    @php @endphp

                    <div class="dossier-card p-6 border border-slate-200 space-y-6 shadow-sm bg-white rounded-2xl hidden"
                        id="section5LabExamination">

                        <!-- OFFICIAL READ-ONLY FORENSIC EXAMINATION REPORT DISPLAY -->
                        <div id="section5ReadOnlyDisplay"
                            data-has-any-saved-lab="{{ !empty($hasAnySavedLabData) ? '1' : '0' }}"
                            data-active-has-drug="{{ !empty($activeSuspectHasDrugExhibit) ? '1' : '0' }}"
                            data-active-has-saved-result="{{ !empty($activeSuspectHasSavedResult) ? '1' : '0' }}"
                            class="hidden">
                        </div>



                        <!-- NOTICE CARD WHEN ACTIVE SUSPECT IS NOT LINKED TO ANY DRUG EXHIBITS -->
                        <div id="section5NoDrugExhibitNotice"
                            class="p-6 bg-slate-50 border border-slate-200/90 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-slate-700 shadow-2xs {{ !empty($activeSuspectHasSavedResult) || !empty($activeSuspectHasDrugExhibit) ? 'hidden' : '' }}">
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-10 h-10 rounded-xl bg-slate-200/80 text-slate-500 flex items-center justify-center font-bold shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800 text-sm">No Drug Exhibit Linked</h4>
                                    <p class="text-xs text-slate-500 mt-0.5">The selected suspect
                                        (<strong>{{ $activeSuspectName }}</strong>) is not linked to any drug exhibits
                                        for Laboratory Examination.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                
                <!-- SECTION 4: SEIZURES (COMMODITIES & EXHIBITS) -->
                <div class="mt-8 bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm space-y-6 relative z-20 overflow-hidden">
                    <div class="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 rounded-xl bg-white text-emerald-600 border border-slate-200/90 flex items-center justify-center font-bold shadow-md shadow-slate-200/60 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-slate-900 text-base">Section 4: Seizures (Commodities)</h3>
                                <p class="text-xs text-slate-500">Record illicit substance types, street valuation, and evidence vault allocation</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 shrink-0">
                            <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200/80 uppercase tracking-wider shadow-2xs">
                                Contraband Inventory
                            </span>
                        </div>
                    </div>

                    <!-- SEIZURE ITEM SECTION ACTIVATION TOGGLE CARD -->
                    <div id="seizureItemActivationCard" class="space-y-2.5">
                        <div class="flex items-center justify-between pb-1">
                            <span class="text-xs font-black text-slate-900 uppercase font-mono-code tracking-wide">Seizure Item Section</span>
                            <span id="seizureItemStatusBadge" class="text-[9px] font-mono-code font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded border border-slate-200 uppercase">
                                INACTIVE / HIDDEN
                            </span>
                        </div>
                        <label for="toggleSeizureItemSectionCheckbox" id="labelToggleSeizureItemSection" class="relative flex items-center space-x-3.5 p-4 bg-slate-50/80 hover:bg-slate-100/80 border border-slate-300/80 rounded-xl cursor-pointer hover:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-600/20 focus-within:border-emerald-600 transition-all shadow-2xs select-none overflow-hidden">
                            <input type="checkbox" id="toggleSeizureItemSectionCheckbox" onchange="toggleSeizureItemSection(this.checked)" class="w-5 h-5 text-emerald-600 rounded-md border-slate-300 focus:ring-emerald-600 focus:ring-2 cursor-pointer accent-emerald-600 shrink-0">
                            <div class="flex flex-col min-w-0 flex-1">
                                <span class="text-xs font-extrabold text-slate-900">Seized Contraband / Illicit Commodities Recorded During Arrest</span>
                                <span class="text-[11px] text-slate-500 font-medium">Check this box if any narcotics, weapons, cash, or contraband commodities were confiscated from this suspect</span>
                            </div>
                        </label>
                    </div>

                    <!-- CONDITIONAL SEIZURES DETAILS CONTAINER -->
                    <div id="section4Seizures" class="hidden p-5 bg-emerald-50/40 rounded-2xl border border-emerald-200/80 space-y-5 shadow-2xs overflow-hidden">
                    <form id="section4SeizuresForm"
                        onsubmit="event.preventDefault(); return false;"
                        onkeydown="if(event.key === 'Enter' && event.target.tagName !== 'TEXTAREA') { event.preventDefault(); return false; }">
                        <!-- MANIFEST HEADER BAR WITH DYNAMIC SEIZED ITEMS NUMBER INPUT -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-slate-300/60 pb-4 mb-6 gap-3">
                            <div class="flex items-center space-x-2.5">
                                <div class="w-7 h-7 rounded-lg bg-white text-emerald-600 border border-slate-200/90 flex items-center justify-center text-xs font-bold shadow-sm shadow-slate-200/50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                                <div>
                                    <h4 class="font-extrabold text-slate-900 text-xs uppercase tracking-wider">Seized Commodities &amp; Contraband Manifest</h4>
                                    <p class="text-[11px] text-slate-500">Record illicit substance types, Weight/quantity, street valuation, and evidence vault allocation</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <div class="inline-flex items-center gap-2 bg-white px-3 py-1.5 rounded-xl border border-slate-300 shadow-2xs">
                                    <label for="seizureCountInput" class="text-[11px] font-extrabold uppercase font-mono-code text-slate-700 whitespace-nowrap">
                                        Number of Seized Items:
                                    </label>
                                    <input type="number" id="seizureCountInput" min="1" max="100" value="1"
                                        onchange="setSeizureBlocksCount(this.value)"
                                        oninput="setSeizureBlocksCount(this.value)"
                                        onblur="if(!this.value || parseInt(this.value, 10) < 1) { this.value = document.querySelectorAll('#seizuresListContainer .seizure-block').length; }"
                                        class="w-16 px-2 py-0.5 text-center font-mono-code font-black text-xs text-slate-900 bg-emerald-50 border border-emerald-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all">
                                </div>
                                <button type="button" onclick="addSeizureBlock()" class="inline-flex items-center px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-xs hover:shadow-md transition-all cursor-pointer">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                    Add Seizure
                                </button>
                                <button type="button" onclick="resetSeizureItemSection(true)" title="Reset Seizure Item Section" class="inline-flex items-center px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 hover:text-slate-900 font-extrabold text-xs rounded-xl border border-slate-300 shadow-xs hover:shadow transition-all cursor-pointer">
                                    <svg class="w-3.5 h-3.5 mr-1 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Reset Section
                                </button>
                            </div>
                        </div>

                        <!-- SHARED MANIFEST DESCRIPTION OF EXHIBIT(S) SECTION (Requirement 1C) -->
                        <div class="bg-white p-5 rounded-2xl border border-slate-300/80 shadow-2xs space-y-2 mb-6">
                            <div class="flex items-center justify-between">
                                <label for="manifestDescriptionOfExhibits" class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase flex items-center gap-2">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h7" />
                                    </svg>
                                    <span>Description of Exhibit(s)</span>
                                    <span class="text-red-600 font-bold">*</span>
                                </label>
                                <span class="px-2 py-0.5 bg-emerald-50 text-emerald-800 text-[10px] font-mono-code font-bold rounded-md border border-emerald-200 uppercase">
                                    SHARED MANIFEST DESCRIPTION
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 font-medium">Record the overall description, outer packaging, markings, and consignment details applicable to all seized items under this manifest.</p>
                            <textarea id="manifestDescriptionOfExhibits" name="description_of_exhibits" rows="3"
                                placeholder="e.g. Inside the brown carton were multiple individually sealed exhibits, labelled sequentially as received under chain of custody..."
                                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs resize-none placeholder:text-slate-400">{{ old('description_of_exhibits', $case->description_of_exhibits ?? '') }}</textarea>
                        </div>

                        <div id="seizuresListContainer" class="space-y-6">
                            <!-- SEIZURE BLOCK #1 -->
                            <div class="seizure-block bg-white p-5 rounded-2xl border border-slate-300/80 shadow-2xs space-y-5 relative"
                                data-seizure-index="1">
                                <!-- SEIZURE ITEM HEADER -->
                                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                                    <div class="flex items-center space-x-2">
                                        <span
                                            class="w-6 h-6 rounded-full bg-emerald-600 text-white font-extrabold text-xs flex items-center justify-center seizure-number-badge">1</span>
                                        <h5 class="font-extrabold text-slate-900 text-xs uppercase tracking-wider seizure-title">Seized Item 1</h5>
                                    </div>
                                    <div class="flex items-center space-x-2 seizure-header-right">
                                        <span
                                            class="px-2.5 py-0.5 bg-emerald-50 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200">ACTIVE
                                            EXHIBIT</span>
                                        <button type="button" onclick="removeSeizureBlock(this)"
                                            class="remove-seizure-btn hidden text-red-600 hover:bg-red-50 border border-red-200 px-2.5 py-1 rounded-lg text-xs font-bold transition-colors cursor-pointer flex items-center gap-1"
                                            title="Remove Seizure Item">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg> Remove Item
                                        </button>
                                    </div>
                                </div>

                                <!-- SEIZURES INPUT GRID -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
                                    <!-- SUSPECT / OWNERSHIP SELECT DROPDOWN -->
                                    <div class="relative z-30">
                                        <label
                                            class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                            Suspect / Ownership <span class="text-red-600 font-bold">*</span>
                                        </label>
                                        <div class="relative">
                                            <select onchange="this.setAttribute('data-user-selected', 'true')"
                                                class="seizure-associated-person-select w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs cursor-pointer">
                                                <option value="All Suspects">All Suspects (Joint Ownership)</option>
                                                @foreach ($allSuspectsInCase as $sOpt)
                                                    <option value="{{ $sOpt['name'] }}"
                                                        {{ strtolower(trim($sOpt['name'])) === strtolower(trim($activeSuspectName)) ? 'selected' : '' }}>
                                                        {{ $sOpt['name'] }} ({{ $sOpt['role'] }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    
                                    <!-- EXHIBIT (COMMODITY CATEGORY) - SELECT DROPDOWN -->
                                    <div class="relative z-[9999]" style="z-index: 9999;">
                                        <label
                                            class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                            Exhibit (Commodity Category) <span class="text-red-600 font-bold">*</span>
                                        </label>
                                        <div id="exhibitSelectWrapper" class="relative z-[9999]"
                                            style="z-index: 9999;">
                                            <div class="relative">
                                                <span
                                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                    </svg>
                                                </span>
                                                <input type="text" id="exhibitSearchInput"
                                                    value="Drugs"
                                                    placeholder="Drugs"
                                                    autocomplete="off"
                                                    class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-500 placeholder:font-normal cursor-text"
                                                    onclick="openExhibitListPanel(this)"
                                                    oninput="filterExhibitList(this)"
                                                    onfocus="openExhibitListPanel(this)">
                                                <input type="hidden" id="newSeizureCategory" value="Drugs">
                                            </div>
                                            <div id="exhibitListPanel"
                                                class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-[9999] overflow-hidden"
                                                style="z-index: 9999;">
                                                <div
                                                    class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                    <input type="text" id="exhibitSearchInner"
                                                        placeholder="Type to filter categories..."
                                                        oninput="filterExhibitListInner(this)"
                                                        class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                </div>
                                                <ul id="exhibitList" class="py-1.5 space-y-0.5 px-1.5">
                                                                                                            <li class="exhibit-item"
                                                            onclick="selectExhibitCategory('Drugs', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <div>
                                                                    <div class="font-extrabold text-slate-900">Drugs</div>
                                                                    <div class="text-[10px] text-slate-500 font-normal">Narcotics, Synthetics, Opioids</div>
                                                                </div>
                                                                <span class="text-[10px] font-mono-code bg-emerald-50 text-black px-2 py-0.5 rounded font-bold">DRG</span>
                                                            </button>
                                                        </li>
                                                                                                    </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- SPECIFIC COMMODITY / DRUG NAME (SMART FIELD) -->
                                    <div id="commodityFieldWrapper">
                                        <label
                                            class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Packaging as Received / Commodity Name <span class="text-red-600 font-bold">*</span></label>

                                        <!-- TEXT INPUT (shown for non-drug categories) -->
                                        <div id="commodityTextWrapper" class="hidden relative flex items-center group">
                                            <span
                                                class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                </svg>
                                            </span>
                                            <input type="text" id="newSeizureName"
                                                placeholder="e.g. Cocaine (High Purity Slabs)"
                                                class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                        </div>

                                        <!-- DRUG SELECT DROPDOWN -->
                                        <div id="drugSelectWrapper" class="relative z-40">
                                            <div class="relative">
                                                <span
                                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                    </svg>
                                                </span>
                                                <input type="text" id="drugSearchInput"
                                                    placeholder="Select or type custom drug name..."
                                                    autocomplete="off"
                                                    class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-500 placeholder:font-normal cursor-text"
                                                    onclick="openDrugListPanel(this)" oninput="filterDrugList(this)"
                                                    onchange="filterDrugList(this)" onblur="filterDrugList(this)"
                                                    onkeydown="handleCommoditySearchKeydown(event, this, 'drug')"
                                                    onfocus="openDrugListPanel(this)">
                                                <input type="hidden" id="newSeizureNameDrug"
                                                    name="newSeizureNameDrug" value="">
                                            </div>
                                            <div id="drugListPanel"
                                                class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden"
                                                style="max-height:240px;overflow-y:auto">
                                                <div
                                                    class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                    <input type="text" id="drugSearchInner"
                                                        placeholder="Type to filter drugs..."
                                                        oninput="filterDrugListInner(this)"
                                                        class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                </div>
                                                <ul id="drugList" class="py-1.5 space-y-0.5 px-1.5">
                                                    <li
                                                        class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                        Opioids &amp; Analgesics</li>
                                                    <li class="drug-item"
                                                        onclick="selectDrugType('Heroin (Brown Sugar / Diacetylmorphine)', event)">
                                                        <button type="button"
                                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Heroin
                                                                (Brown Sugar / Diacetylmorphine)</span><span
                                                                class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span></button>
                                                    </li>
                                                    <li class="drug-item"
                                                        onclick="selectDrugType('Fentanyl (Synthetic Opioid Patches / Powder)', event)">
                                                        <button type="button"
                                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Fentanyl
                                                                (Synthetic Opioid Patches / Powder)</span><span
                                                                class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span></button>
                                                    </li>
                                                    <li class="drug-item"
                                                        onclick="selectDrugType('Morphine (Raw Opium Extract)', event)">
                                                        <button type="button"
                                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Morphine
                                                                (Raw Opium Extract)</span><span
                                                                class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span></button>
                                                    </li>
                                                    <li class="drug-item"
                                                        onclick="selectDrugType('Tramadol (High-Dose Tablets / Capsules)', event)">
                                                        <button type="button"
                                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Tramadol
                                                                (High-Dose Tablets / Capsules)</span><span
                                                                class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span></button>
                                                    </li>
                                                    <li class="drug-item"
                                                        onclick="selectDrugType('Codeine (Cough Syrup Misuse / Purple Drank)', event)">
                                                        <button type="button"
                                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Codeine
                                                                (Cough Syrup Misuse / Purple Drank)</span><span
                                                                class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span></button>
                                                    </li>
                                                    <li class="drug-item"
                                                        onclick="selectDrugType('Oxycodone (OxyContin Tablets)', event)">
                                                        <button type="button"
                                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Oxycodone
                                                                (OxyContin Tablets)</span><span
                                                                class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span></button>
                                                    </li>

                                                    <li
                                                        class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                        Stimulants &amp; Cocaine-Class</li>
                                                    <li class="drug-item"
                                                        onclick="selectDrugType('Cocaine (High-Purity Powder / Slabs)', event)">
                                                        <button type="button"
                                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Cocaine
                                                                (High-Purity Powder / Slabs)</span><span
                                                                class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">COC</span></button>
                                                    </li>
                                                    <li class="drug-item"
                                                        onclick="selectDrugType('Crack Cocaine (Rock Form / Freebase)', event)">
                                                        <button type="button"
                                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Crack
                                                                Cocaine (Rock Form / Freebase)</span><span
                                                                class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">COC</span></button>
                                                    </li>
                                                    <li class="drug-item"
                                                        onclick="selectDrugType('Methamphetamine (Crystal Meth / Ice)', event)">
                                                        <button type="button"
                                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Methamphetamine
                                                                (Crystal Meth / Ice)</span><span
                                                                class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">STIM</span></button>
                                                    </li>
                                                    <li class="drug-item"
                                                        onclick="selectDrugType('Amphetamine (Speed / Pep Pills)', event)">
                                                        <button type="button"
                                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Amphetamine
                                                                (Speed / Pep Pills)</span><span
                                                                class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">STIM</span></button>
                                                    </li>
                                                    <li class="drug-item"
                                                        onclick="selectDrugType('MDMA (Ecstasy / Molly Tablets)', event)">
                                                        <button type="button"
                                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>MDMA
                                                                (Ecstasy / Molly Tablets)</span><span
                                                                class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">STIM</span></button>
                                                    </li>

                                                    <li
                                                        class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                        Cannabis &amp; Derivatives</li>
                                                    <li class="drug-item"
                                                        onclick="selectDrugType('Cannabis / Marijuana (Dried Flower / Weed)', event)">
                                                        <button type="button"
                                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Cannabis
                                                                / Marijuana (Dried Flower / Weed)</span><span
                                                                class="text-[10px] font-mono-code bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-bold">CAN</span></button>
                                                    </li>
                                                    <li class="drug-item"
                                                        onclick="selectDrugType('Cannabis Resin (Hashish / Black Hash Slabs)', event)">
                                                        <button type="button"
                                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Cannabis
                                                                Resin (Hashish / Black Hash Slabs)</span><span
                                                                class="text-[10px] font-mono-code bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-bold">CAN</span></button>
                                                    </li>
                                                    <li class="drug-item"
                                                        onclick="selectDrugType('Synthetic Cannabinoids (Spice / K2 Herbal)', event)">
                                                        <button type="button"
                                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Synthetic
                                                                Cannabinoids (Spice / K2 Herbal)</span><span
                                                                class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">SYN</span></button>
                                                    </li>

                                                    <li
                                                        class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                        Sedatives &amp; Depressants</li>
                                                    <li class="drug-item"
                                                        onclick="selectDrugType('Rohypnol (Flunitrazepam / Date Rape Drug)', event)">
                                                        <button type="button"
                                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Rohypnol
                                                                (Flunitrazepam / Date Rape Drug)</span><span
                                                                class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">SED</span></button>
                                                    </li>
                                                    <li class="drug-item"
                                                        onclick="selectDrugType('Ketamine (Special K / Dissociative Anesthetic)', event)">
                                                        <button type="button"
                                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Ketamine
                                                                (Special K / Dissociative Anesthetic)</span><span
                                                                class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">SED</span></button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <!-- WEAPON SELECT DROPDOWN -->
                                        <div id="weaponSelectWrapper" class="hidden relative z-40">
                                            <div class="relative">
                                                <span
                                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                    </svg>
                                                </span>
                                                <input type="text" id="weaponSearchInput"
                                                    placeholder="Select or type custom weapon type..."
                                                    autocomplete="off"
                                                    class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-500 placeholder:font-normal cursor-text"
                                                    onclick="openWeaponListPanel(this)"
                                                    oninput="filterWeaponList(this)"
                                                    onfocus="openWeaponListPanel(this)">
                                                <input type="hidden" id="newSeizureNameWeapon"
                                                    name="newSeizureNameWeapon" value="">
                                            </div>
                                            <div id="weaponListPanel"
                                                class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden"
                                                style="max-height:240px;overflow-y:auto">
                                                <div
                                                    class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                    <input type="text" id="weaponSearchInner"
                                                        placeholder="Type to filter weapons..."
                                                        oninput="filterWeaponListInner(this)"
                                                        class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                </div>
                                                <ul id="weaponList" class="py-1.5 space-y-0.5 px-1.5">
                                                    <li
                                                        class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                        Handguns &amp; Pistols</li>
                                                    <li class="weapon-item"
                                                        onclick="selectWeaponType('AK-47 / AKM Shorty Pistol (7.62x39mm)', event)">
                                                        <button type="button"
                                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>AK-47
                                                                / AKM Shorty Pistol (7.62x39mm)</span><span
                                                                class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button>
                                                    </li>
                                                    <li class="weapon-item"
                                                        onclick="selectWeaponType('Glock 17 Semi-Automatic Pistol (9mm)', event)">
                                                        <button type="button"
                                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Glock
                                                                17 Semi-Automatic Pistol (9mm)</span><span
                                                                class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button>
                                                    </li>
                                                    <li class="weapon-item"
                                                        onclick="selectWeaponType('Beretta 92FS / M9 Pistol (9mm)', event)">
                                                        <button type="button"
                                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Beretta
                                                                92FS / M9 Pistol (9mm)</span><span
                                                                class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button>
                                                    </li>

                                                    <li
                                                        class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                        Rifles &amp; Assault Rifles</li>
                                                    <li class="weapon-item"
                                                        onclick="selectWeaponType('AK-47 / AKM Kalashnikov Rifle (7.62x39mm)', event)">
                                                        <button type="button"
                                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>AK-47
                                                                / AKM Kalashnikov Rifle (7.62x39mm)</span><span
                                                                class="text-[10px] font-mono-code bg-red-100 text-red-800 px-1.5 py-0.5 rounded font-bold">RFL</span></button>
                                                    </li>
                                                    <li class="weapon-item"
                                                        onclick="selectWeaponType('M4A1 Carbine / AR-15 Platform (5.56x45mm NATO)', event)">
                                                        <button type="button"
                                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>M4A1
                                                                Carbine / AR-15 Platform (5.56x45mm NATO)</span><span
                                                                class="text-[10px] font-mono-code bg-red-100 text-red-800 px-1.5 py-0.5 rounded font-bold">RFL</span></button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <!-- CURRENCY SELECT DROPDOWN -->
                                        <div id="currencySelectWrapper" class="hidden relative z-40">
                                            <div class="relative">
                                                <span
                                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-emerald-500 pointer-events-none">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                    </svg>
                                                </span>
                                                <input type="text" id="currencySearchInput"
                                                    placeholder="Select currency..." autocomplete="off"
                                                    class="w-full pl-8 pr-3 py-2.5 bg-white border border-emerald-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-300 transition-all shadow-2xs placeholder:text-slate-400 placeholder:font-normal cursor-pointer"
                                                    onclick="openCurrencyListPanel(this)"
                                                    oninput="filterCurrencyList(this)"
                                                    onfocus="openCurrencyListPanel(this)">
                                                <input type="hidden" id="newSeizureNameCurrency"
                                                    name="newSeizureNameCurrency">
                                            </div>
                                            <div id="currencyListPanel"
                                                class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-emerald-200 z-50 overflow-hidden"
                                                style="max-height:240px;overflow-y:auto">
                                                <div
                                                    class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                    <input type="text" id="currencySearchInner"
                                                        placeholder="Type to filter currencies..."
                                                        oninput="filterCurrencyListInner(this)"
                                                        class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-300 transition-all">
                                                </div>
                                                <ul id="currencyList" class="py-1.5 space-y-0.5 px-1.5">
                                                    <li
                                                        class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-emerald-500 uppercase tracking-widest">
                                                        West Africa</li>
                                                    <li class="currency-item"
                                                        onclick="selectCurrencyType('Ghana Cedi (GHS ₵)')"><button
                                                            type="button"
                                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Ghana
                                                                Cedi</span><span
                                                                class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">GHS
                                                                ₵</span></button></li>
                                                    <li class="currency-item"
                                                        onclick="selectCurrencyType('Nigerian Naira (NGN ₦)')"><button
                                                            type="button"
                                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Nigerian
                                                                Naira</span><span
                                                                class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">NGN
                                                                ₦</span></button></li>
                                                    <li
                                                        class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-emerald-500 uppercase tracking-widest">
                                                        Major Global Currencies</li>
                                                    <li class="currency-item"
                                                        onclick="selectCurrencyType('US Dollar (USD $)')"><button
                                                            type="button"
                                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>US
                                                                Dollar</span><span
                                                                class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">USD
                                                                $</span></button></li>
                                                    <li class="currency-item"
                                                        onclick="selectCurrencyType('Euro (EUR €)')"><button
                                                            type="button"
                                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Euro</span><span
                                                                class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">EUR
                                                                €</span></button></li>
                                                    <li class="currency-item"
                                                        onclick="selectCurrencyType('British Pound Sterling (GBP £)')">
                                                        <button type="button"
                                                            class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>British
                                                                Pound</span><span
                                                                class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">GBP
                                                                £</span></button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <!-- AMMUNITION SELECT DROPDOWN (shown when Ammunition is selected) -->
                                        <div id="ammoSelectWrapper" class="hidden relative z-40">
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                </span>
                                                <input
                                                    type="text"
                                                    id="ammoSearchInput"
                                                    placeholder="Select or type custom ammunition type..."
                                                    autocomplete="off"
                                                    class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-500 placeholder:font-normal cursor-text"
                                                    onclick="openAmmoListPanel(this)"
                                                    oninput="filterAmmoList(this)"
                                                    onfocus="openAmmoListPanel(this)"
                                                >
                                                <input type="hidden" id="newSeizureNameAmmo" name="newSeizureNameAmmo" value="">
                                            </div>
                                            <div id="ammoListPanel" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden" style="max-height:240px;overflow-y:auto">
                                                <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                    <input type="text" id="ammoSearchInner" placeholder="Type to filter ammunitions..." oninput="filterAmmoListInner(this)" class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                </div>
                                                <ul id="ammoList" class="py-1.5 space-y-0.5 px-1.5">
                                                    <!-- Pistol & Handgun Ammunition -->
                                                    <li class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Pistol &amp; Handgun Ammunition</li>
                                                    <li class="ammo-item" onclick="selectAmmoType('9x19mm Parabellum / NATO (9mm Luger)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>9x19mm Parabellum / NATO (9mm Luger)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">9MM</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.45 ACP (Automatic Colt Pistol / 11.43x23mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.45 ACP (Automatic Colt Pistol / 11.43x23mm)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">.45</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.40 S&amp;W (Smith &amp; Wesson / 10x22mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.40 S&amp;W (Smith &amp; Wesson / 10x22mm)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">.40</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.380 ACP (9mm Short / Browning)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.380 ACP (9mm Short / Browning)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">.380</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.38 Special (9x29mmR Revolver Ammunition)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.38 Special (9x29mmR Revolver Ammunition)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">.38</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.357 Magnum (9x33mmR High Velocity)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.357 Magnum (9x33mmR High Velocity)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">.357</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.44 Remington Magnum (10.9x33mmR)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.44 Remington Magnum (10.9x33mmR)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">.44</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('7.62x25mm Tokarev (TT Pistol Cartridge)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>7.62x25mm Tokarev (TT Pistol Cartridge)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">7.62</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('9x18mm Makarov (PM Round)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>9x18mm Makarov (PM Round)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">9x18</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('5.7x28mm (FN P90 / Five-seveN High-Velocity)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>5.7x28mm (FN P90 / Five-seveN High-Velocity)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">5.7</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('10mm Auto (10x25mm Tactical)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>10mm Auto (10x25mm Tactical)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">10MM</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.50 Action Express (.50 AE Desert Eagle)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.50 Action Express (.50 AE Desert Eagle)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">.50AE</span></button></li>

                                                    <!-- Rifle & Intermediate Ammunition -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Rifle &amp; Intermediate Ammunition</li>
                                                    <li class="ammo-item" onclick="selectAmmoType('7.62x39mm Soviet (AK-47 / SKS / Type 56)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>7.62x39mm Soviet (AK-47 / SKS / Type 56)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">7.62</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('5.56x45mm NATO / .223 Remington (M16 / M4 / AR-15)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>5.56x45mm NATO / .223 Remington (M16 / M4 / AR-15)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">5.56</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('7.62x51mm NATO / .308 Winchester (FN FAL / G3)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>7.62x51mm NATO / .308 Winchester (FN FAL / G3)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">7.62</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('5.45x39mm Soviet (AK-74 Assault Rifle)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>5.45x39mm Soviet (AK-74 Assault Rifle)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">5.45</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('7.62x54mmR Russian (Dragunov SVD / PKM Belt)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>7.62x54mmR Russian (Dragunov SVD / PKM Belt)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">7.62R</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.300 AAC Blackout (7.62x35mm Subsonic / Supersonic)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.300 AAC Blackout (7.62x35mm Subsonic / Supersonic)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">.300</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('6.5mm Creedmoor (Precision Long Range Match)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>6.5mm Creedmoor (Precision Long Range Match)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">6.5</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.30-06 Springfield (7.62x63mm Military Rifle)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.30-06 Springfield (7.62x63mm Military Rifle)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">.30-06</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.303 British (7.7x56mmR Lee-Enfield)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.303 British (7.7x56mmR Lee-Enfield)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">.303</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('7.92x57mm Mauser (8mm Mauser Infantry)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>7.92x57mm Mauser (8mm Mauser Infantry)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">8MM</span></button></li>

                                                    <!-- Shotgun Shells & Shotshell Cartridges -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Shotgun Shells &amp; Shotshell Cartridges</li>
                                                    <li class="ammo-item" onclick="selectAmmoType('12 Gauge Buckshot (00 Buck / 9 Pellets Tactical)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>12 Gauge Buckshot (00 Buck / 9 Pellets Tactical)</span><span class="text-[10px] font-mono-code bg-orange-50 text-orange-700 px-1.5 py-0.5 rounded font-bold">12GA</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('12 Gauge Rifled Slug (Heavy Solid Lead Penetrator)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>12 Gauge Rifled Slug (Heavy Solid Lead Penetrator)</span><span class="text-[10px] font-mono-code bg-orange-50 text-orange-700 px-1.5 py-0.5 rounded font-bold">12GA</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('12 Gauge Birdshot / Game Load Cartridges', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>12 Gauge Birdshot / Game Load Cartridges</span><span class="text-[10px] font-mono-code bg-orange-50 text-orange-700 px-1.5 py-0.5 rounded font-bold">12GA</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('12 Gauge Less-Lethal Rubber Baton / Beanbag Round', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>12 Gauge Less-Lethal Rubber Baton / Beanbag Round</span><span class="text-[10px] font-mono-code bg-orange-50 text-orange-700 px-1.5 py-0.5 rounded font-bold">12GA</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('20 Gauge Shotgun Cartridge (Buck &amp; Slug)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>20 Gauge Shotgun Cartridge (Buck &amp; Slug)</span><span class="text-[10px] font-mono-code bg-orange-50 text-orange-700 px-1.5 py-0.5 rounded font-bold">20GA</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('16 Gauge Shotgun Cartridge', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>16 Gauge Shotgun Cartridge</span><span class="text-[10px] font-mono-code bg-orange-50 text-orange-700 px-1.5 py-0.5 rounded font-bold">16GA</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.410 Bore Shotgun Shell (Buckshot / Slug)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.410 Bore Shotgun Shell (Buckshot / Slug)</span><span class="text-[10px] font-mono-code bg-orange-50 text-orange-700 px-1.5 py-0.5 rounded font-bold">.410</span></button></li>

                                                    <!-- Heavy & Anti-Materiel Ammunition -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Heavy &amp; Anti-Materiel Ammunition</li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.50 BMG (12.7x99mm NATO Armor-Piercing Incendiary)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.50 BMG (12.7x99mm NATO Armor-Piercing Incendiary)</span><span class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">.50</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('12.7x108mm Russian Heavy Machine Gun Cartridge', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>12.7x108mm Russian Heavy Machine Gun Cartridge</span><span class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">12.7</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.338 Lapua Magnum (8.6x70mm Long-Range Sniper)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.338 Lapua Magnum (8.6x70mm Long-Range Sniper)</span><span class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">.338</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.300 Winchester Magnum (.300 Win Mag Match)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.300 Winchester Magnum (.300 Win Mag Match)</span><span class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">.300</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('14.5x114mm Heavy Armor-Piercing Cartridge', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>14.5x114mm Heavy Armor-Piercing Cartridge</span><span class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">14.5</span></button></li>

                                                    <!-- Rimfire & Specialty Ammunition -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Rimfire &amp; Specialty Ammunition</li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.22 Long Rifle (.22 LR Rimfire Round)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.22 Long Rifle (.22 LR Rimfire Round)</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded font-bold">.22LR</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.22 WMR (.22 Magnum Rimfire Cartridge)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.22 WMR (.22 Magnum Rimfire Cartridge)</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded font-bold">.22WMR</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('40x46mm High-Explosive Grenade Round (M203 / M320)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>40x46mm High-Explosive Grenade Round (M203 / M320)</span><span class="text-[10px] font-mono-code bg-red-100 text-red-900 px-1.5 py-0.5 rounded font-bold">40MM</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('37mm / 40mm Tear Gas / CS Smoke / Flare Round', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>37mm / 40mm Tear Gas / CS Smoke / Flare Round</span><span class="text-[10px] font-mono-code bg-amber-100 text-amber-900 px-1.5 py-0.5 rounded font-bold">37MM</span></button></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- CONTENTS (VISUAL) FIELD (Requirement 1B) -->
                                    <div id="contentsFieldWrapper" class="contents-field-wrapper">
                                        <label
                                            class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Contents (Visual) <span class="text-red-600 font-bold">*</span></label>
                                        <div class="relative flex items-center group">
                                            <span
                                                class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </span>
                                            <input type="text" id="newSeizureContents" name="seizure_contents"
                                                placeholder="e.g. White compressed powder / Brown pebbles"
                                                class="seizure-contents-input w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                        </div>
                                    </div>

                                    <!-- ESTIMATED STREET VALUE -->
                                    <div>
                                        <label
                                            class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Estimated
                                            Street Value</label>
                                        <div class="relative flex items-center group">
                                            <span
                                                class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors font-mono-code font-bold text-xs">₵</span>
                                            <input type="text" id="newSeizureValue"
                                                placeholder="e.g. GHS 1,500,000 / $120,000"
                                                class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                        </div>
                                    </div>

                                    <!-- QUANTITY, PACKAGING TYPE, WEIGHT & UNIT METRICS PANEL -->
                                    <div
                                        class="col-span-full bg-white/90 p-4 rounded-2xl border border-slate-300/80 shadow-2xs space-y-3">
                                        <div class="flex items-center space-x-2 text-slate-800">
                                            <div
                                                class="w-6 h-6 rounded-md bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <span
                                                class="text-xs font-extrabold uppercase tracking-wider text-slate-900">Seizure
                                                Quantification &amp; Packaging Metrics</span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3.5">
                                            <!-- QUANTITY -->
                                            <div>
                                                <label id="quantityLabel"
                                                    class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Quantity</label>
                                                <div class="relative flex items-center group">
                                                    <span
                                                        class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors font-mono-code font-bold text-xs">#</span>
                                                    <input type="text" inputmode="decimal" id="newSeizureQuantity"
                                                        placeholder="e.g. 10 or 50,000"
                                                        class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                            </div>

                                            <!-- PACKAGING TYPE -->
                                            <div class="relative z-30">
                                                <label
                                                    class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Packaging
                                                    Type</label>
                                                <div id="packagingSelectWrapper" class="relative">
                                                    <div class="relative">
                                                        <span
                                                            class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                            <svg class="w-3.5 h-3.5" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                            </svg>
                                                        </span>
                                                        <input type="text" id="pkgSearchInput"
                                                            placeholder="Select or type packaging..."
                                                            autocomplete="off"
                                                            class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400 placeholder:font-normal cursor-text"
                                                            onclick="openPkgListPanel(this)"
                                                            oninput="filterPackagingList(this)"
                                                            onfocus="openPkgListPanel(this)">
                                                        <input type="hidden" id="newSeizurePackaging"
                                                            value="">
                                                    </div>
                                                    <div id="pkgListPanel"
                                                        class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden"
                                                        style="max-height:220px;overflow-y:auto">
                                                        <div
                                                            class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                            <input type="text" id="pkgSearchInner"
                                                                placeholder="Type to filter packaging..."
                                                                oninput="filterPackagingListInner(this)"
                                                                class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                        </div>
                                                        <ul id="pkgList" class="py-1.5 space-y-0.5 px-1.5">
                                                            <li class="pkg-item"
                                                                onclick="selectPackagingType('Slabs', event)"><button
                                                                    type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Slabs</span><span
                                                                        class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">SLB</span></button>
                                                            </li>
                                                            <li class="pkg-item"
                                                                onclick="selectPackagingType('Blocks', event)"><button
                                                                    type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Blocks</span><span
                                                                        class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">BLK</span></button>
                                                            </li>
                                                            <li class="pkg-item"
                                                                onclick="selectPackagingType('Bundles', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Bundles</span><span
                                                                        class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">BDL</span></button>
                                                            </li>
                                                            <li class="pkg-item"
                                                                onclick="selectPackagingType('Sacks', event)"><button
                                                                    type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Sacks</span><span
                                                                        class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">SCK</span></button>
                                                            </li>
                                                            <li class="pkg-item"
                                                                onclick="selectPackagingType('Boxes', event)"><button
                                                                    type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Boxes</span><span
                                                                        class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">BOX</span></button>
                                                            </li>
                                                            <li class="pkg-item"
                                                                onclick="selectPackagingType('Pieces', event)"><button
                                                                    type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Pieces</span><span
                                                                        class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">PCS</span></button>
                                                            </li>
                                                            <li class="pkg-item"
                                                                onclick="selectPackagingType('Units', event)"><button
                                                                    type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Units</span><span
                                                                        class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">UNT</span></button>
                                                            </li>
                                                            <li class="pkg-item"
                                                                onclick="selectPackagingType('Packages', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Packages</span><span
                                                                        class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">PKG</span></button>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- WEIGHT -->
                                            <div>
                                                <label id="weightLabel"
                                                    class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Weight</label>
                                                <div class="relative flex items-center group">
                                                    <span
                                                        class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors font-mono-code font-bold text-xs">⚖</span>
                                                    <input type="number" step="0.01" id="newSeizureWeight"
                                                        placeholder="e.g. 25.50"
                                                        class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                            </div>

                                            <!-- MEASUREMENT UNIT -->
                                            <div class="relative z-20">
                                                <label
                                                    class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Measurement
                                                    Unit</label>
                                                <div id="unitSelectWrapper" class="relative">
                                                    <div class="relative">
                                                        <span
                                                            class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                            <svg class="w-3.5 h-3.5" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                            </svg>
                                                        </span>
                                                        <input type="text" id="unitSearchInput"
                                                            placeholder="Select or type unit..." autocomplete="off"
                                                            class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400 placeholder:font-normal cursor-text"
                                                            onclick="openUnitListPanel(this)"
                                                            oninput="filterUnitList(this)"
                                                            onfocus="openUnitListPanel(this)">
                                                        <input type="hidden" id="newSeizureUnit" value="">
                                                    </div>
                                                    <div id="unitListPanel"
                                                        class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden"
                                                        style="max-height:220px;overflow-y:auto">
                                                        <div
                                                            class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                            <input type="text" id="unitSearchInner"
                                                                placeholder="Type to filter units..."
                                                                oninput="filterUnitListInner(this)"
                                                                class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                        </div>
                                                        <ul id="unitList" class="py-1.5 space-y-0.5 px-1.5">
                                                            <li
                                                                class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                                Weight &amp; Mass</li>
                                                            <li class="unit-item"
                                                                onclick="selectUnit('Kilograms (kg)', event)"><button
                                                                    type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Kilograms</span><span
                                                                        class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">kg</span></button>
                                                            </li>
                                                            <li class="unit-item"
                                                                onclick="selectUnit('Grams (g)', event)"><button
                                                                    type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Grams</span><span
                                                                        class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">g</span></button>
                                                            </li>
                                                            <li class="unit-item"
                                                                onclick="selectUnit('Milligrams (mg)', event)"><button
                                                                    type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Milligrams</span><span
                                                                        class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">mg</span></button>
                                                            </li>
                                                            <li
                                                                class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                                Volume &amp; Liquid</li>
                                                            <li class="unit-item"
                                                                onclick="selectUnit('Litres (L)', event)"><button
                                                                    type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Litres</span><span
                                                                        class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">L</span></button>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ELECTRONIC DEVICE SPECIFIC FIELDS -->
                                    <div id="deviceFieldsWrapper"
                                        class="hidden col-span-full bg-blue-50/50 p-4 rounded-2xl border border-blue-200/80 space-y-3">
                                        <div class="flex items-center space-x-2 text-blue-900">
                                            <div
                                                class="w-6 h-6 rounded-md bg-blue-600 text-white flex items-center justify-center font-bold text-xs shadow-2xs">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <span
                                                class="text-xs font-extrabold uppercase tracking-wider text-blue-950">Electronic Device Forensic Specifications</span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3.5">
                                            <div>
                                                <label
                                                    class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Model</label>
                                                <input type="text" id="newDevModel"
                                                    placeholder="e.g. iPhone 15 Pro Max"
                                                    class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400">
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Serial
                                                    Number</label>
                                                <input type="text" id="newDevSerialNo"
                                                    placeholder="e.g. F2LZX984K102"
                                                    class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400 font-mono-code">
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">IMEI
                                                    1</label>
                                                <input type="text" id="newDevImei1"
                                                    placeholder="e.g. 356938035643809"
                                                    class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400 font-mono-code">
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">IMEI
                                                    2</label>
                                                <input type="text" id="newDevImei2"
                                                    placeholder="e.g. 356938035643817"
                                                    class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400 font-mono-code">
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">MAC
                                                    Address</label>
                                                <input type="text" id="newDevMacAddress"
                                                    placeholder="e.g. 00:1A:2B:3C:4D:5E"
                                                    class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400 font-mono-code">
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Storage
                                                    Capacity</label>
                                                <input type="text" id="newDevStorage" placeholder="e.g. 256 GB"
                                                    class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400">
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Colour</label>
                                                <input type="text" id="newDevColour"
                                                    placeholder="e.g. Space Gray"
                                                    class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400">
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Other
                                                    Marks</label>
                                                <input type="text" id="newDevMarks"
                                                    placeholder="e.g. Cracked screen"
                                                    class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- VEHICLE SPECIFIC FIELDS -->
                                    <div id="vehicleFieldsWrapper"
                                        class="hidden col-span-full pt-4 border-t border-slate-300/60 mt-4 space-y-5">
                                        <div class="flex items-center gap-2 mb-2">
                                            <div
                                                class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                                </svg>
                                            </div>
                                            <h4
                                                class="text-xs font-extrabold text-slate-800 uppercase tracking-widest">
                                                Vehicle Specific Details</h4>
                                        </div>

                                        <!-- 1. Basic Identification -->
                                        <div class="space-y-3">
                                            <h5
                                                class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-widest bg-emerald-50 px-2 py-1 rounded-md inline-block">
                                                1. Basic Identification</h5>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Make
                                                        <span class="text-red-600 font-bold">*</span></label>
                                                    <input type="text" id="vehMake" placeholder="e.g. Toyota"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Model
                                                        <span class="text-red-600 font-bold">*</span></label>
                                                    <input type="text" id="vehModel" placeholder="e.g. Hilux"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div class="relative z-20" id="vehTypeSelectWrapper">
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Vehicle
                                                        Type</label>
                                                    <div class="relative">
                                                        <span
                                                            class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                            <svg class="w-3.5 h-3.5" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                            </svg>
                                                        </span>
                                                        <input type="text" id="vehTypeSearchInput"
                                                            placeholder="Select or type vehicle type..." autocomplete="off"
                                                            class="w-full pl-8 pr-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400 placeholder:font-normal cursor-text"
                                                            onclick="openVehTypeListPanel(this)"
                                                            oninput="filterVehTypeList(this)"
                                                            onfocus="openVehTypeListPanel(this)">
                                                        <input type="hidden" id="vehType" value="">
                                                    </div>
                                                    <div id="vehTypeListPanel"
                                                        class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden"
                                                        style="max-height:220px;overflow-y:auto">
                                                        <div
                                                            class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                            <input type="text" id="vehTypeSearchInner"
                                                                placeholder="Type to filter..."
                                                                oninput="filterVehTypeListInner(this)"
                                                                class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                        </div>
                                                        <ul id="vehTypeList" class="py-1.5 space-y-0.5 px-1.5">
                                                            <li
                                                                class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                                Passenger Vehicles</li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Sedan / Saloon', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Sedan
                                                                    / Saloon</button>
                                                            </li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Hatchback', event)"><button
                                                                    type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Hatchback</button>
                                                            </li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('SUV / Crossover', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">SUV
                                                                    / Crossover</button>
                                                            </li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Coupe / Sports Car', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Coupe
                                                                    / Sports Car</button>
                                                            </li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Convertible', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Convertible</button>
                                                            </li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Station Wagon / Estate', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Station
                                                                    Wagon / Estate</button>
                                                            </li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Minivan / MPV', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Minivan
                                                                    / MPV</button>
                                                            </li>

                                                            <li
                                                                class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                                Commercial &amp; Utility</li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Pickup Truck', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Pickup
                                                                    Truck</button>
                                                            </li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Panel Van / Cargo Van', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Panel
                                                                    Van / Cargo Van</button>
                                                            </li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Minibus / Trotro', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Minibus
                                                                    / Trotro</button>
                                                            </li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Large Bus / Coach', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Large
                                                                    Bus / Coach</button>
                                                            </li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Box Truck / Rigid Truck', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Box
                                                                    Truck / Rigid Truck</button>
                                                            </li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Heavy Duty / Articulated Truck', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Heavy
                                                                    Duty / Articulated Truck</button>
                                                            </li>

                                                            <li
                                                                class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                                Other Types</li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Motorcycle / Motorbike', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Motorcycle
                                                                    / Motorbike</button>
                                                            </li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Tricycle (Aboboyaa / Pragya)', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Tricycle
                                                                    (Aboboyaa / Pragya)</button>
                                                            </li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Trailer / Caravan', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Trailer
                                                                    / Caravan</button>
                                                            </li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Agricultural / Tractor', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Agricultural
                                                                    / Tractor</button>
                                                            </li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Construction Equipment', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Construction
                                                                    Equipment</button>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Year
                                                        of Manufacture</label>
                                                    <input type="text" id="vehYear" placeholder="e.g. 2019"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Colour</label>
                                                    <input type="text" id="vehColour" placeholder="e.g. White"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Registration
                                                        Number <span class="text-red-600 font-bold">*</span></label>
                                                    <input type="text" id="vehRegNo"
                                                        placeholder="e.g. GT 1234-21"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">VIN
                                                        / Chassis Number</label>
                                                    <input type="text" id="vehVIN"
                                                        placeholder="e.g. JT11234567890ABC"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400 font-mono-code">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Engine
                                                        Number</label>
                                                    <input type="text" id="vehEngineNo"
                                                        placeholder="e.g. 1TR1234567"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400 font-mono-code">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Country
                                                        of Registration</label>
                                                    <input type="text" id="vehCountryReg" placeholder="e.g. Ghana"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 2. Ownership & Possession -->
                                        <div class="space-y-3">
                                            <h5
                                                class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-widest bg-emerald-50 px-2 py-1 rounded-md inline-block">
                                                2. Ownership &amp; Possession</h5>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Registered
                                                        Owner</label>
                                                    <input type="text" id="vehOwner" placeholder="e.g. John Doe"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Driver
                                                        / Person in Possession</label>
                                                    <input type="text" id="vehDriver"
                                                        placeholder="e.g. Kwame Mensah"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Owner's
                                                        Contact</label>
                                                    <input type="text" id="vehOwnerContact"
                                                        placeholder="e.g. 0244123456"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Docs
                                                        Available?</label>
                                                    <select id="vehDocsAvailable"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                                                        <option value="">Select...</option>
                                                        <option value="Yes">Yes</option>
                                                        <option value="No">No</option>
                                                    </select>
                                                </div>
                                                <div class="md:col-span-2">
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Reg/Title
                                                        Doc Reference</label>
                                                    <input type="text" id="vehDocRef"
                                                        placeholder="e.g. DVLA Title #998877"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 3. Vehicle Condition -->
                                        <div class="space-y-3">
                                            <h5
                                                class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-widest bg-emerald-50 px-2 py-1 rounded-md inline-block">
                                                3. Vehicle Condition</h5>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Overall
                                                        Condition</label>
                                                    <select id="vehCondition"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                                                        <option value="">Select...</option>
                                                        <option value="Excellent">Excellent / Driveable</option>
                                                        <option value="Good">Good</option>
                                                        <option value="Fair">Fair</option>
                                                        <option value="Poor">Poor / Damaged</option>
                                                        <option value="Non-Driveable">Non-Driveable / Towed</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Fuel
                                                        Level</label>
                                                    <select id="vehFuel"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                                                        <option value="">Select...</option>
                                                        <option value="Full">Full</option>
                                                        <option value="3/4">3/4</option>
                                                        <option value="Half">Half (1/2)</option>
                                                        <option value="1/4">1/4</option>
                                                        <option value="Empty">Empty (Light on)</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Keys
                                                        Received?</label>
                                                    <div class="flex items-center gap-2 mt-1">
                                                        <select id="vehKeysRecv"
                                                            class="w-1/2 px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                                                            <option value="">Select...</option>
                                                            <option value="Yes">Yes</option>
                                                            <option value="No">No</option>
                                                        </select>
                                                        <input type="number" id="vehKeyCount" placeholder="Qty"
                                                            min="0"
                                                            class="w-1/2 px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                    </div>
                                                </div>
                                                <div class="md:col-span-3">
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Existing
                                                        Damage</label>
                                                    <input type="text" id="vehDamage"
                                                        placeholder="e.g. Scratches on front right bumper, cracked left taillight"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div class="md:col-span-3">
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Visible
                                                        Modifications / Compartments</label>
                                                    <input type="text" id="vehMods"
                                                        placeholder="e.g. False floor welded in trunk, tinted windows"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div class="md:col-span-3">
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Notable
                                                        Identifying Features</label>
                                                    <input type="text" id="vehFeatures"
                                                        placeholder="e.g. Custom alloy wheels, stickers on rear window"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- WEAPON SPECIFIC FIELDS -->
                                    <div id="weaponFieldsWrapper"
                                        class="hidden col-span-full pt-4 border-t border-slate-300/60 mt-4 space-y-5">
                                        <div class="flex items-center gap-2 mb-2">
                                            <div
                                                class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                            </div>
                                            <h4
                                                class="text-xs font-extrabold text-slate-800 uppercase tracking-widest">
                                                Weapon / Firearm Specific Details</h4>
                                        </div>

                                        <!-- 1. Weapon Identification & Physical Specs -->
                                        <div class="space-y-3">
                                            <h5
                                                class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-widest bg-emerald-50 px-2 py-1 rounded-md inline-block">
                                                1. Identification &amp; Specifications</h5>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Model</label>
                                                    <input type="text" id="wpnModel"
                                                        placeholder="e.g. 19 Gen 5, M&amp;P 9, Type 56, Glock 17"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Serial
                                                        Number</label>
                                                    <input type="text" id="wpnSerialNo"
                                                        placeholder="e.g. G17-984210X / Defaced"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400 font-mono-code">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Calibre
                                                        / Gauge</label>
                                                    <input type="text" id="wpnCalibre"
                                                        placeholder="e.g. 9mm, 7.62x39mm, 12-Gauge, .45 ACP"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Colour</label>
                                                    <input type="text" id="wpnColour"
                                                        placeholder="e.g. Matte Black, Silver, Camouflage"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Approx.
                                                        Size / Length</label>
                                                    <input type="text" id="wpnSize"
                                                        placeholder="e.g. 18.5 cm (Overall), Short Barrel, 87 cm"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 2. Recovery Condition & Status -->
                                        <div class="space-y-3 pt-2">
                                            <h5
                                                class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-widest bg-emerald-50 px-2 py-1 rounded-md inline-block">
                                                2. Recovery Condition &amp; Location</h5>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Loaded
                                                        Status</label>
                                                    <select id="wpnLoadedStatus"
                                                        class="w-full appearance-none px-3.5 py-2.5 bg-white border border-slate-300 hover:border-emerald-500 focus:border-emerald-600 rounded-xl font-bold text-slate-800 text-xs shadow-xs focus:outline-none focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer">
                                                        <option value="" class="text-slate-400 font-normal">Select...</option>
                                                        <option value="Loaded" class="font-semibold text-slate-900">Loaded</option>
                                                        <option value="Unloaded" class="font-semibold text-slate-900">Unloaded</option>
                                                        <option value="Round in Chamber" class="font-semibold text-slate-900">Round in Chamber</option>
                                                        <option value="Magazine Attached (Loaded)" class="font-semibold text-slate-900">Magazine Attached (Loaded)</option>
                                                        <option value="Magazine Attached (Empty)" class="font-semibold text-slate-900">Magazine Attached (Empty)</option>
                                                        <option value="Unknown / Unspecified" class="font-semibold text-slate-900">Unknown / Unspecified</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Condition
                                                        at Recovery</label>
                                                    <select id="wpnCondition"
                                                        class="w-full appearance-none px-3.5 py-2.5 bg-white border border-slate-300 hover:border-emerald-500 focus:border-emerald-600 rounded-xl font-bold text-slate-800 text-xs shadow-xs focus:outline-none focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer">
                                                        <option value="" class="text-slate-400 font-normal">Select...</option>
                                                        <option value="Operable / Good Condition" class="font-semibold text-slate-900">Operable / Good Condition</option>
                                                        <option value="Fair / Used Condition" class="font-semibold text-slate-900">Fair / Used Condition</option>
                                                        <option value="Rusty / Corroded" class="font-semibold text-slate-900">Rusty / Corroded</option>
                                                        <option value="Damaged / Inoperable" class="font-semibold text-slate-900">Damaged / Inoperable</option>
                                                        <option value="Serial Defaced / Tampered" class="font-semibold text-slate-900">Serial Defaced / Tampered</option>
                                                        <option value="Modified / Sawed-Off" class="font-semibold text-slate-900">Modified / Sawed-Off</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Exact
                                                        Location Found</label>
                                                    <input type="text" id="wpnLocationFound"
                                                        placeholder="e.g. Bedroom closet, Under vehicle driver seat, Roadside ditch"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div class="md:col-span-3">
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Distinguishing
                                                        Marks / Features</label>
                                                    <textarea id="wpnMarks" rows="2"
                                                        placeholder="e.g. Scratch on left grip, custom insignia engraved, missing safety switch"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400 resize-none"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- AMMUNITION SPECIFIC FIELDS -->
                                    <div id="ammoFieldsWrapper"
                                        class="hidden col-span-full pt-4 border-t border-slate-300/60 mt-4 space-y-5">
                                        <div class="flex items-center gap-2 mb-2">
                                            <div
                                                class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                                </svg>
                                            </div>
                                            <h4
                                                class="text-sm font-extrabold text-slate-800 uppercase tracking-widest">
                                                Ammunition Specific Details</h4>
                                        </div>

                                        <!-- 1. Physical Description -->
                                        <div class="space-y-3">
                                            <h5
                                                class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-widest bg-emerald-50 px-2 py-1 rounded-md inline-block">
                                                1. Physical Description</h5>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Cartridge
                                                        Appearance</label>
                                                    <input type="text" id="ammoCartridgeAppearance"
                                                        placeholder="e.g. Brass casing, FMJ, Nickel-plated"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Projectile
                                                        / Bullet Type</label>
                                                    <input type="text" id="ammoProjectileType"
                                                        placeholder="e.g. Full Metal Jacket (FMJ), Hollow Point, Lead Round Nose"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Cartridge
                                                        Case Material</label>
                                                    <input type="text" id="ammoCaseMaterial"
                                                        placeholder="e.g. Brass, Steel, Aluminium, Plastic"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Colour
                                                        / Markings</label>
                                                    <input type="text" id="ammoColourMarkings"
                                                        placeholder="e.g. Headstamp CBC 9mm LUGER, Red primer sealant"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Condition</label>
                                                    <select id="ammoCondition" onchange="toggleAmmoOtherCondition(this)"
                                                        class="w-full appearance-none px-3.5 py-2.5 bg-white border border-slate-300 hover:border-emerald-500 focus:border-emerald-600 rounded-xl font-bold text-slate-800 text-xs shadow-xs focus:outline-none focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer">
                                                        <option value="" class="text-slate-400 font-normal">Select Condition...</option>
                                                        <option value="Intact" class="font-semibold text-slate-900">Intact</option>
                                                        <option value="Damaged" class="font-semibold text-slate-900">Damaged</option>
                                                        <option value="Corroded" class="font-semibold text-slate-900">Corroded</option>
                                                        <option value="Deformed" class="font-semibold text-slate-900">Deformed</option>
                                                        <option value="Other" class="font-semibold text-slate-900">Other</option>
                                                    </select>
                                                </div>
                                                <div id="ammoOtherConditionWrapper" class="hidden">
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Describe
                                                        Other Condition</label>
                                                    <input type="text" id="ammoOtherCondition"
                                                        placeholder="Specify condition details..."
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 2. Packaging & Sealing -->
                                        <div class="space-y-3 pt-2">
                                            <h5
                                                class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-widest bg-emerald-50 px-2 py-1 rounded-md inline-block">
                                                2. Packaging &amp; Sealing</h5>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Packaging
                                                        Type</label>
                                                    <input type="text" id="ammoPackagingType"
                                                        placeholder="e.g. Cardboard Box, Plastic Ammo Can, Loose Bag"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Seal
                                                        / Tamper-Evident Condition</label>
                                                    <input type="text" id="ammoSealCondition"
                                                        placeholder="e.g. Intact Tamper-Evident Bag #SEAL-90812"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Package
                                                        / Reference Number</label>
                                                    <input type="text" id="ammoPackageReferenceNumber"
                                                        placeholder="e.g. PKG-2026-AM01"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Condition
                                                        When Received</label>
                                                    <input type="text" id="ammoConditionWhenReceived"
                                                        placeholder="e.g. Sealed and untampered"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- OTHER EXHIBIT DESCRIPTION PANEL (shown when Other Exhibits is selected) -->
                                    <div id="otherExhibitDescriptionWrapper"
                                        class="other-exhibit-description-wrapper hidden col-span-full bg-slate-50/90 p-4 rounded-2xl border border-slate-300/80 shadow-2xs space-y-3">
                                        <div class="flex items-center space-x-2 text-slate-800">
                                            <div
                                                class="w-6 h-6 rounded-md bg-slate-700 text-white flex items-center justify-center font-bold text-xs shadow-2xs">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 012.828 0L19.828 4.828a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                                                </svg>
                                            </div>
                                            <span
                                                class="text-xs font-extrabold uppercase tracking-wider text-slate-900">Specify Other Exhibit Description &amp; Details</span>
                                        </div>
                                        <div>
                                            <label
                                                class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                Exhibit Description / Specifications <span
                                                    class="text-red-600 font-bold">*</span>
                                            </label>
                                            <textarea id="newOtherExhibitDescription" name="other_exhibit_description" rows="3"
                                                placeholder="Type detailed description and specifications of this exhibit..."
                                                class="other-exhibit-description-input w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400 font-sans"></textarea>
                                        </div>
                                    </div>

                                    <!-- CONCEALMENT METHOD -->
                                    <div class="col-span-full md:col-span-2">
                                        <label
                                            class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Concealment
                                            &amp; Packaging Notes</label>
                                        <div class="relative flex items-center group">
                                            <span
                                                class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                                </svg>
                                            </span>
                                            <input type="text" id="newSeizureConcealment"
                                                placeholder="e.g. Vacuum foil wrapped in false trunk bottom"
                                                class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                        </div>
                                    </div>

                                    <!-- DATE, TIME SEIZED & SOURCE OF EXHIBITS -->
                                    <div class="col-span-full grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <!-- DATE SEIZED -->
                                        <div>
                                            <label
                                                class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Date
                                                Seized</label>
                                            <input type="date" id="newSeizureDate"
                                                class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                                        </div>

                                        <!-- TIME SEIZED -->
                                        <div>
                                            <label
                                                class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Time
                                                Seized</label>
                                            <input type="time" id="newSeizureTime"
                                                class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                                        </div>

                                        <!-- SOURCE OF EXHIBITS -->
                                        <div>
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                Source of Exhibits
                                            </label>
                                            <div class="relative flex items-center group">
                                                <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors pointer-events-none">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                                    </svg>
                                                </span>
                                                <input type="text" id="newSeizureSourceOfExhibits" name="source_of_exhibits"
                                                    placeholder="e.g. Tema Port Warehouse, Vessel Compartment, Suspect Residence..."
                                                    class="seizure-source-of-exhibits-input w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ROW: INVESTIGATING OFFICER & SOURCE/REQUESTING UNIT -->
                                    <div class="col-span-full grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <!-- INVESTIGATING OFFICER -->
                                        <div>
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                Investigating Officer
                                            </label>
                                            <div class="relative flex items-center group">
                                                <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors pointer-events-none">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                </span>
                                                <input type="text" id="newSeizureInvestigatingOfficer" name="investigating_officer"
                                                    placeholder="e.g. Insp. Emmanuel Mensah / Service ID SD-0199"
                                                    class="seizure-investigating-officer-input w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                        </div>

                                        <!-- SOURCE / REQUESTING UNIT -->
                                        <div>
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                Source/Requesting Unit
                                            </label>
                                            <div class="relative flex items-center group">
                                                <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors pointer-events-none">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                    </svg>
                                                </span>
                                                <input type="text" id="newSeizureSourceRequestingUnit" name="source_requesting_unit"
                                                    placeholder="e.g. Enforcement and Operations Unit, KIA Command, DLEU..."
                                                    class="seizure-source-requesting-unit-input w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- SEIZING OFFICERS (MULTI-ROW) -->
                                    <div class="col-span-full">
                                        <div class="flex items-center justify-between mb-1.5">
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase">
                                                Seizing Officer(s) <span class="text-red-600 font-bold">*</span>
                                            </label>
                                            <button type="button" onclick="addSeizureOfficerRow(this)" class="inline-flex items-center text-[10px] font-extrabold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-2.5 py-1 rounded-lg transition-colors cursor-pointer shadow-2xs">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                                Add Seizing Officer
                                            </button>
                                        </div>

                                        <!-- OFFICER ROWS CONTAINER -->
                                        <div id="seizureOfficersContainer" class="space-y-2">
                                            <!-- Row 1 -->
                                            <div class="seizure-officer-row p-2.5 bg-slate-50 border border-slate-300/80 rounded-xl">
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                    <div class="relative flex items-center group">
                                                        <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors pointer-events-none">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                        </span>
                                                        <input type="text" class="seizure-officer-name w-full pl-8 pr-3 py-2 bg-white border border-slate-300 rounded-lg font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400" placeholder="Officer Full Name (e.g. DSP Kwame Mensah)">
                                                    </div>
                                                    <div class="flex items-center gap-1.5">
                                                        <div class="relative flex-1 flex items-center group">
                                                            <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors pointer-events-none">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 012-2h2a2 2 0 012 2v1m-4 0h4"/></svg>
                                                            </span>
                                                            <input type="text" class="seizure-officer-service-id w-full pl-8 pr-3 py-2 bg-white border border-slate-300 rounded-lg font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400 font-mono-code" placeholder="Service ID / Badge No (e.g. SD-0042)">
                                                        </div>
                                                        <button type="button" onclick="removeSeizureOfficerRow(this)" class="remove-officer-btn hidden p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg border border-transparent hover:border-red-200 transition-colors cursor-pointer" title="Remove Officer">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ROW: REASON FOR SEIZURE & REMARKS / OBSERVATIONS -->
                                    <div class="col-span-full grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <!-- REASON FOR SEIZURE -->
                                        <div>
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Reason for Seizure <span class="text-red-600 font-bold">*</span></label>
                                            <div class="relative flex items-start group">
                                                <span class="absolute left-3 top-3 text-slate-400 group-focus-within:text-black transition-colors pointer-events-none">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                </span>
                                                <textarea id="newSeizureReason" name="seizure_reason" rows="3" placeholder="e.g. Suspected drug trafficking — commodity found concealed in vehicle false compartment during routine checkpoint stop." class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400 resize-none"></textarea>
                                            </div>
                                        </div>

                                        <!-- REMARKS / OBSERVATIONS (OPTIONAL) -->
                                        <div>
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                                <span>Remarks / Observations <span class="text-slate-400 font-normal lowercase">(optional)</span></span>
                                                <span class="text-[9px] font-mono-code font-bold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200 uppercase">REMARK</span>
                                            </label>
                                            <div class="relative flex items-start group">
                                                <span class="absolute left-3 top-3 text-slate-400 group-focus-within:text-black transition-colors pointer-events-none">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                                    </svg>
                                                </span>
                                                <textarea id="newSeizureRemark" name="exhibit_remark" rows="3" data-alt-id="newSeizureExhibitRemark"
                                                    placeholder="e.g. Broken seal, sample retrieved under chain-of-custody, custody observations, packaging notes..."
                                                    class="newSeizureExhibitRemark seizure-remark-input w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400 resize-none"></textarea>
                                            </div>
                                        </div>
                                    </div>


                                    <!-- SEIZED EXHIBIT PHOTO UPLOAD AREA -->
                                    <div class="col-span-full pt-2 border-t border-slate-300/60 space-y-3">
                                        <div class="flex items-center justify-between">
                                            <label
                                                class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase">
                                                Upload Seized Exhibit Photos / Evidence Images <span
                                                    class="text-slate-400 font-normal">(Multiple Supported)</span>
                                            </label>
                                            <span id="seizurePhotosCount"
                                                class="seizure-photos-count hidden px-2.5 py-0.5 rounded-full text-[10px] font-mono-code font-bold bg-emerald-100 text-black border border-slate-300">0
                                                Photos Uploaded</span>
                                        </div>

                                        <div id="seizurePhotoDropzone" onclick="triggerSeizurePhotoUpload(this)"
                                            ondragover="event.preventDefault(); this.classList.add('border-emerald-500', 'bg-emerald-50/50');"
                                            ondragleave="this.classList.remove('border-emerald-500', 'bg-emerald-50/50');"
                                            ondrop="event.preventDefault(); this.classList.remove('border-emerald-500', 'bg-emerald-50/50'); handleSeizureDrop(event, this);"
                                            class="w-full min-h-[90px] bg-white rounded-xl border-2 border-dashed border-slate-300 hover:border-emerald-500 flex flex-col items-center justify-center p-4 cursor-pointer group transition-colors shadow-2xs text-center">
                                            <div id="placeholderSeizureBox"
                                                class="flex flex-col items-center space-y-1 text-slate-400 group-hover:text-black transition-colors">
                                                <div
                                                    class="w-9 h-9 rounded-full bg-emerald-50 text-black flex items-center justify-center mb-0.5">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <span
                                                    class="text-xs font-extrabold text-slate-800 group-hover:text-black">Click
                                                    or drag &amp; drop to upload exhibit photos</span>
                                                <span class="text-[10px] text-slate-500">Select one or multiple PNG,
                                                    JPG evidence photos</span>
                                            </div>
                                        </div>
                                        <input type="file" class="fileSeizurePhoto hidden" accept="image/*"
                                            multiple onchange="previewSeizurePhotos(event, this)">
                                        <div
                                            class="seizurePhotosGrid hidden grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3 pt-2">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ACTION BUTTONS -->
                        <div id="section4ActionButtons" class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200 mt-6">
                            <button type="button"
                                onclick="toggleSeizureItemSection(false, false)"
                                class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold text-xs rounded-xl transition-all cursor-pointer border-none">
                                Cancel
                            </button>
                            <button type="button"
                                id="saveSection4SeizureBtn"
                                onclick="submitSection4SeizureForm(event)"
                                class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-600/30 transition-all flex items-center justify-center cursor-pointer border-none">
                                <span id="saveSection4SeizureBtnText">Save Seizure / Exhibit</span>
                            </button>
                        </div>

                    </form>
                </div>

                    <!-- SECTION 4 TO 5 ACTION & REPORT GENERATION NAVIGATION BAR -->
                    <div class="mt-8 bg-slate-100/90 p-4 rounded-2xl border border-slate-200/90 shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-4 mb-6">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 rounded-xl bg-slate-800 text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-slate-900 uppercase font-mono-code tracking-wide">
                                    Report &amp; Laboratory Examination Modules
                                </h4>
                                <p class="text-[11px] text-slate-500 font-medium">
                                    Switch between uploading GSA Report Files and filling Section 5 Examination Information (NLR)
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3 w-full sm:w-auto justify-end shrink-0">
                            <!-- UPLOAD GSA FILE BUTTON -->
                            <button type="button" id="toggleGsaReportBtn" onclick="switchToGsaUploadSection()"
                                class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white font-extrabold text-xs rounded-xl shadow-md shadow-blue-600/20 transition-all flex items-center space-x-2 cursor-pointer border-none">
                                <svg class="w-4 h-4 text-blue-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>Upload GSA File</span>
                            </button>

                            <!-- EXAMINATION INFORMATION (NLR) BUTTON -->
                            <button type="button" id="toggleNlrReportBtn" onclick="switchToNlrExamSection()"
                                class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-600/20 transition-all flex items-center space-x-2 cursor-pointer border-none">
                                <svg class="w-4 h-4 text-emerald-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                </svg>
                                <span>Examination Information (NLR)</span>
                            </button>

                            <!-- RESET BUTTON TO NEUTRAL STATE -->
                            <button type="button" id="resetLabModulesBtn" onclick="resetLabModulesToNeutral()"
                                class="px-3.5 py-2.5 bg-slate-200 hover:bg-slate-300 active:scale-95 text-slate-700 font-extrabold text-xs rounded-xl transition-all flex items-center space-x-1.5 cursor-pointer border border-slate-300"
                                title="Reset both buttons and forms to neutral state">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <span>Reset</span>
                            </button>
                        </div>
                    </div>

                    <!-- GSA REPORT UPLOAD SECTION -->
                    <div id="gsaUploadSection" class="hidden bg-white p-6 rounded-2xl border border-blue-200/90 shadow-sm space-y-6 relative z-20 mb-6">
                        <!-- Section Header -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-blue-600 to-blue-800 text-white flex items-center justify-center shadow-md shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="px-2 py-0.5 bg-blue-600 text-white text-[10px] font-extrabold font-mono-code rounded-md uppercase tracking-wider">GSA FILES</span>
                                        <h3 class="text-base font-black text-slate-900 font-mono-code uppercase tracking-tight">GSA Report Upload</h3>
                                    </div>
                                    <p class="text-[11px] text-slate-500 font-mono-code mt-0.5">Attach scans, photos, or documents of the signed GSA Report Upload</p>
                                    @if (!empty($activeSuspectName))
                                        <p class="text-[11px] text-blue-700 font-mono-code font-bold mt-0.5">Target Suspect: {{ $activeSuspectName }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span id="gsaFileCount" class="hidden px-2.5 py-0.5 bg-blue-600 text-white text-[10px] font-extrabold font-mono-code rounded-full">0 files</span>
                                <button type="button" onclick="clearAllGsaFiles()" id="gsaClearAllBtn" class="hidden px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 text-[10px] font-bold rounded-lg border border-red-200 transition-colors cursor-pointer">Clear All</button>
                            </div>
                        </div>

                        <!-- GSA DETAILS: Target Suspect, GSA Lab No., GSA Case Reference, GSA Status & Exhibit Types -->
                        <div class="bg-blue-50/50 p-4 rounded-2xl border border-blue-100 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <!-- Target Suspect -->
                                <div class="space-y-1.5">
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase flex items-center justify-between">
                                        <span class="flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            Target Suspect
                                        </span>
                                        <span class="text-[9px] font-mono-code text-blue-700 bg-blue-100 px-1.5 py-0.5 rounded font-bold uppercase">Assign</span>
                                    </label>
                                    <select id="gsaSuspectSelect" name="gsa_suspect_name"
                                        class="w-full px-3.5 py-2.5 bg-white border border-blue-200 rounded-xl text-slate-900 font-mono-code font-bold outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all text-xs shadow-2xs">
                                        @if (!empty($allSuspectsInCase) && count($allSuspectsInCase) > 1)
                                            <option value="All Suspects" data-id="">All Suspects (Case Level / Joint)</option>
                                        @endif
                                        @if (!empty($allSuspectsInCase))
                                            @foreach ($allSuspectsInCase as $sOpt)
                                                <option value="{{ $sOpt['name'] }}" data-id="{{ $sOpt['id'] ?? '' }}"
                                                    {{ strtolower(trim($sOpt['name'])) === strtolower(trim($activeSuspectName)) ? 'selected' : '' }}>
                                                    {{ $sOpt['name'] }} ({{ $sOpt['role'] ?? 'Suspect' }})
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="{{ $case->suspect_name ?? 'Primary Suspect' }}" selected>
                                                {{ $case->suspect_name ?? 'Primary Suspect' }}
                                            </option>
                                        @endif
                                    </select>
                                </div>

                                <!-- GSA Lab No. -->
                                <div class="space-y-1.5">
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase flex items-center justify-between">
                                        <span class="flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                            </svg>
                                            GSA Lab No.
                                        </span>
                                        <span class="text-[9px] font-mono-code text-blue-700 bg-blue-100 px-1.5 py-0.5 rounded font-bold uppercase">Official No.</span>
                                    </label>
                                    <input type="text" id="gsaLabNumberInput" name="gsa_lab_number"
                                        value="{{ $case->laboratory_number ?? '' }}"
                                        placeholder="Enter GSA Lab No. (e.g. GSA/LAB/2026/0892)"
                                        class="w-full px-3.5 py-2.5 bg-white border border-blue-200 rounded-xl text-slate-900 font-mono-code font-bold outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all text-xs shadow-2xs">
                                </div>

                                <!-- GSA Case Reference -->
                                <div class="space-y-1.5">
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase flex items-center justify-between">
                                        <span class="flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            GSA Case Reference
                                        </span>
                                        <span class="text-[9px] font-mono-code text-slate-600 bg-slate-200/70 px-1.5 py-0.5 rounded font-bold uppercase">Reference</span>
                                    </label>
                                    <textarea id="gsaCaseReferenceInput" name="gsa_case_reference" rows="2"
                                        placeholder="Enter GSA Case Reference details..."
                                        class="w-full px-3.5 py-2 bg-white border border-blue-200 rounded-xl text-slate-900 font-mono-code text-xs outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all shadow-2xs resize-none">{{ $case->case_number ?? '' }}</textarea>
                                </div>

                                <!-- GSA Status -->
                                <div class="space-y-1.5">
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase flex items-center justify-between">
                                        <span class="flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            GSA Status
                                        </span>
                                        <span class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Status</span>
                                    </label>
                                    <textarea id="gsaStatusInput" name="gsa_status" rows="2"
                                        placeholder="Enter GSA Status (e.g. Analysis Complete, Certified...)"
                                        class="w-full px-3.5 py-2 bg-white border border-blue-200 rounded-xl text-slate-900 font-mono-code text-xs outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all shadow-2xs resize-none"></textarea>
                                </div>
                            </div>

                            <!-- Exhibit Types Container -->
                            <div class="pt-3 border-t border-blue-200/60 space-y-2" id="gsaExhibitTypesSection">
                                <div class="flex items-center justify-between">
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                        <span>Exhibit Type</span>
                                    </label>
                                    <button type="button" onclick="addGsaExhibitTypeInput()" class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-mono-code font-bold uppercase text-blue-700 bg-blue-100 hover:bg-blue-200 active:scale-95 rounded-lg border border-blue-200 transition-all cursor-pointer">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        <span>Add Exhibit Type</span>
                                    </button>
                                </div>
                                <div id="gsaExhibitTypeList" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                                    <div class="flex items-center gap-1.5 gsa-exhibit-type-row">
                                        <input type="text" name="exhibit_type[]" placeholder="Enter Exhibit Type (e.g. Cocaine, Heroin)"
                                            class="gsa-exhibit-type-input w-full px-3.5 py-2.5 bg-white border border-blue-200 rounded-xl text-slate-900 font-mono-code font-bold text-xs outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all shadow-2xs">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- DROP ZONE -->
                        <div id="gsaDropZone" ondragover="gsaDragOver(event)" ondragleave="gsaDragLeave(event)" ondrop="gsaHandleDrop(event)" class="relative w-full border-2 border-dashed border-slate-300 rounded-2xl bg-slate-50 hover:border-blue-400 transition-all overflow-hidden">
                            <!-- FILE LIST (inside the zone) -->
                            <div id="gsaFileGrid" class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-5 gap-3 p-3"></div>

                            <!-- FULL PROMPT — shown when no files -->
                            <div id="gsaFullPrompt" onclick="document.getElementById('gsaReportImageInput').click()" class="flex flex-col items-center justify-center gap-2 py-10 px-4 text-center cursor-pointer group hover:bg-blue-50/60 transition-all">
                                <div class="w-12 h-12 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center justify-center group-hover:bg-blue-50 group-hover:border-blue-300 transition-all">
                                    <svg class="w-6 h-6 text-slate-400 group-hover:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-slate-600 group-hover:text-blue-800">Click to add files or drag &amp; drop</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5 font-mono-code">JPG &middot; PNG &middot; WEBP &middot; PDF &middot; DOCX &middot; CSV &nbsp;|&nbsp; Max 10 MB each</p>
                                </div>
                            </div>

                            <!-- COMPACT ADD-MORE STRIP — shown when files exist -->
                            <div id="gsaAddMoreStrip" onclick="document.getElementById('gsaReportImageInput').click()" class="hidden items-center justify-center gap-2 py-2.5 px-4 border-t border-dashed border-slate-300 bg-slate-50 hover:bg-blue-50/60 hover:border-blue-400 transition-all cursor-pointer group">
                                <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span class="text-[11px] font-bold text-slate-500 group-hover:text-blue-800 font-mono-code">Click to add more files or drag &amp; drop</span>
                            </div>

                            <!-- Hidden file input -->
                            <input type="file" id="gsaReportImageInput" name="gsa_report_images[]" accept="image/jpeg,image/png,image/webp,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/csv,.csv,.docx" multiple class="hidden" onchange="gsaHandleFileSelect(this)">
                        </div>

                        <!-- ACTION BUTTONS UNDER GSA REPORT UPLOAD SECTION -->
                        <div id="gsaActionButtons" class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200">
                            <button type="button"
                                onclick="cancelGsaWorkflow()"
                                class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold text-xs rounded-xl transition-all cursor-pointer border-none">
                                Cancel
                            </button>
                            <button type="button" id="saveGsaDetailsBtn"
                                onclick="submitCombinedLabAndSeizureForm(event)"
                                class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-600/30 transition-all flex items-center justify-center cursor-pointer border-none">
                                <span id="saveGsaDetailsBtnText">Save Seizure Item &amp; GSA Details</span>
                            </button>
                        </div>
                    </div>
                
                <!-- EXAMINATION INFORMATION CONTAINER (HANDLED BY DEDICATED FUNCTION EXCLUSIVELY FOR LAB ANALYSIS SHOW_REVIEW) -->
                {!! renderExaminationInformationForm(true, get_defined_vars()) !!}
            </div>

    @php @endphp

    <!-- ASSOCIATE SUSPECT LABORATORY EXAMINATION RESULT DOSSIERS -->

    @php @endphp

    @foreach ($associateSuspectsForLab as $assocLabInfo)
        @php extract($assocLabInfo['resolved_dossier'] ?? []); @endphp

        @if ($hasThisAssocSavedLab && !$isCoveredByMainReport && $hasThisAssocDrug)
            <div class="mt-6 p-4 sm:p-5 bg-amber-50/60 rounded-2xl border-2 border-amber-300 space-y-4 shadow-sm">
                <!-- ASSOCIATE TOP BANNER -->
                <div
                    class="p-3.5 bg-amber-100/90 rounded-xl border border-amber-300 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center space-x-3">
                        <div
                            class="w-9 h-9 rounded-xl {{ $secondaryBadgeBg }} text-white flex items-center justify-center font-bold shadow-md shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div
                                class="text-[10px] font-mono-code font-black text-amber-900 uppercase tracking-wider flex items-center gap-1.5">
                                <span
                                    class="{{ $secondaryBadgeBg }} text-white px-1.5 py-0.5 rounded text-[9px]">{{ $secondaryBadge }}</span>
                                <span class="text-amber-400">|</span>
                                <span>SUSPECT #{{ $aSpIndex }}: {{ strtoupper($aSpName) }}</span>
                            </div>
                            <div class="text-sm font-black text-slate-900">LABORATORY EXAMINATION RESULT — {{ strtoupper($aSpName) }}</div>
                        </div>
                    </div>
                    <span
                        class="px-2.5 py-1 rounded-full text-[10px] font-mono-code font-extrabold bg-amber-200 text-amber-950 border border-amber-400 shrink-0">
                        ASSOCIATE EXHIBIT VERIFIED
                    </span>
                </div>

                <!-- ASSOCIATE LABORATORY EXAMINATION TABLE -->
                <table
                    class="w-full text-xs border-collapse border border-amber-300 rounded-xl overflow-hidden shadow-2xs bg-white">
                    <tbody>
                        <tr class="bg-amber-100/60 border-b border-amber-200">
                            <td
                                class="p-3 font-mono-code font-black text-amber-950 uppercase border-r border-amber-200 w-1/5">
                                Analysis Reference:</td>
                            <td
                                class="p-3 font-black text-amber-900 font-mono-code text-sm border-r border-amber-200 w-3/10 bg-white">
                                {{ $assocExamRef }}</td>
                            <td
                                class="p-3 font-mono-code font-black text-amber-950 uppercase border-r border-amber-200 w-1/5">
                                Exhibit Number:</td>
                            <td class="p-3 font-black text-amber-900 font-mono-code text-sm bg-white">
                                {{ $assocExhibitNo }}</td>
                        </tr>
                        <tr class="border-b border-amber-200">
                            <td
                                class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-amber-200 bg-amber-50/70">
                                Case Reference:</td>
                            <td
                                class="p-3 font-black text-blue-900 font-mono-code border-r border-amber-200 bg-white">
                                {{ $labCaseRef }}</td>
                            <td
                                class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-amber-200 bg-amber-50/70">
                                Laboratory Number:</td>
                            <td class="p-3 font-black text-slate-900 font-mono-code bg-white">{{ $assocLabNumber }}
                            </td>
                        </tr>
                        <tr class="bg-amber-100/60 border-b border-amber-200">
                            <td
                                class="p-3 font-mono-code font-black text-amber-950 uppercase border-r border-amber-200">
                                Analyst / Examiner:</td>
                            <td class="p-3 font-black text-slate-900 border-r border-amber-200 bg-white">
                                {{ $assocAnalyst }}</td>
                            <td
                                class="p-3 font-mono-code font-black text-amber-950 uppercase border-r border-amber-200">
                                Examination Timeline:</td>
                            <td class="p-2 bg-white">
                                <table class="w-full text-xs border border-amber-200 rounded overflow-hidden">
                                    <thead
                                        class="bg-amber-100/80 text-amber-950 font-mono-code uppercase font-black text-[10px]">
                                        <tr class="border-b border-amber-200">
                                            <th class="px-2 py-1 border-r border-amber-200 text-left">Received</th>
                                            <th class="px-2 py-1 border-r border-amber-200 text-left">Started</th>
                                            <th class="px-2 py-1 text-left">Completed</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white font-bold font-mono-code text-slate-900">
                                        <tr>
                                            <td class="px-2 py-1 border-r border-amber-200 text-blue-900">
                                                {{ $assocDateReceived }}</td>
                                            <td class="px-2 py-1 border-r border-amber-200 text-amber-900">
                                                {{ $assocDateStarted }}</td>
                                            <td class="px-2 py-1 text-emerald-900">{{ $assocDateCompleted }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr class="border-b border-amber-200">
                            <td
                                class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-amber-200 bg-amber-50/70">
                                Presumptive Reaction:</td>
                            <td class="p-2 border-r border-amber-200 bg-white">
                                @php @endphp
                                <table class="w-full text-xs border border-amber-200 rounded overflow-hidden">
                                    <thead
                                        class="bg-amber-100/80 text-amber-950 font-mono-code uppercase font-black text-[10px]">
                                        <tr class="border-b border-amber-200">
                                            <th class="px-2 py-1 border-r border-amber-200 text-left">Result</th>
                                            <th class="px-2 py-1 border-r border-amber-200 text-left">Presence of</th>
                                            <th class="px-2 py-1 text-left">Purity/Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white">
                                        @foreach ($pRowsAssoc as $pRowAssoc)
                                            <tr class="border-b border-amber-100 last:border-b-0">
                                                <td
                                                    class="px-2 py-1 border-r border-amber-200 font-extrabold {{ $pRowAssoc['result'] === 'YES' ? 'text-emerald-800' : 'text-slate-900' }}">
                                                    {{ $pRowAssoc['result'] }}</td>
                                                <td
                                                    class="px-2 py-1 border-r border-amber-200 font-bold text-slate-900">
                                                    {{ $pRowAssoc['presence'] }}</td>
                                                <td class="px-2 py-1 font-bold text-slate-900">
                                                    {{ $pRowAssoc['purity'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                            <td
                                class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-amber-200 bg-amber-50/70">
                                Confirmatory Test:</td>
                            <td class="p-2 bg-white">
                                @php
                                    $cRowsAssocCurrent = $cRowsAssoc ?? \App\Models\ForensicCase::parseConfirmatoryRows($assocConfirmatoryVal ?? null);
                                @endphp
                                <table class="w-full text-xs border border-amber-200 rounded overflow-hidden">
                                    <thead
                                        class="bg-amber-100/80 text-amber-950 font-mono-code uppercase font-black text-[10px]">
                                        <tr class="border-b border-amber-200">
                                            <th class="px-2 py-1 border-r border-amber-200 text-left">Result</th>
                                            <th class="px-2 py-1 border-r border-amber-200 text-left">Substance Detected</th>
                                            <th class="px-2 py-1 text-left">Purity/Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white">
                                        @foreach ($cRowsAssocCurrent as $cRowAssoc)
                                            <tr class="border-b border-amber-100 last:border-b-0">
                                                <td
                                                    class="px-2 py-1 border-r border-amber-200 font-extrabold {{ $cRowAssoc['result'] === 'YES' ? 'text-emerald-800' : ($cRowAssoc['result'] === 'NO' ? 'text-red-700' : 'text-slate-900') }}">
                                                    {{ $cRowAssoc['result'] }}</td>
                                                <td
                                                    class="px-2 py-1 border-r border-amber-200 font-bold text-slate-900">
                                                    {{ $cRowAssoc['presence'] }}</td>
                                                <td class="px-2 py-1 font-bold text-slate-900">
                                                    {{ $cRowAssoc['purity'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr class="bg-amber-100/60 border-b border-amber-200">
                            <td
                                class="p-3 font-mono-code font-black text-amber-950 uppercase border-r border-amber-200">
                                Sample Metrology:</td>
                            <td class="p-3 bg-white" colspan="3">
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                    <div
                                        class="col-span-2 sm:col-span-4 p-2 bg-amber-50/50 border border-amber-200 rounded-lg">
                                        <div
                                            class="text-[9px] font-mono-code font-black text-amber-800 uppercase tracking-wider">
                                            Description</div>
                                        <div class="font-bold text-slate-950 text-xs mt-0.5">{{ $assocDrugName }}
                                            (Associate Seizure — {{ $aSpName }})</div>
                                    </div>
                                    <div class="p-2 bg-amber-50/50 border border-amber-200 rounded-lg">
                                        <div
                                            class="text-[9px] font-mono-code font-black text-amber-800 uppercase tracking-wider">
                                            Number of Units</div>
                                        <div class="font-mono-code font-bold text-slate-950 text-xs mt-0.5">
                                            {{ $assocUnits }}</div>
                                    </div>
                                    <div class="p-2 bg-amber-50/50 border border-amber-200 rounded-lg">
                                        <div
                                            class="text-[9px] font-mono-code font-black text-amber-800 uppercase tracking-wider">
                                            Gross Weight</div>
                                        <div class="font-mono-code font-bold text-slate-950 text-xs mt-0.5">
                                            {{ $assocGross }}</div>
                                    </div>
                                    <div class="p-2 bg-amber-50/50 border border-amber-200 rounded-lg">
                                        <div
                                            class="text-[9px] font-mono-code font-black text-amber-800 uppercase tracking-wider">
                                            Sample Taken</div>
                                        <div class="font-mono-code font-bold text-emerald-900 text-xs mt-0.5">
                                            {{ $assocTaken }}</div>
                                    </div>
                                    <div class="p-2 bg-amber-50/50 border border-amber-200 rounded-lg">
                                        <div
                                            class="text-[9px] font-mono-code font-black text-amber-800 uppercase tracking-wider">
                                            Reserved Samples</div>
                                        <div class="font-mono-code font-bold text-slate-950 text-xs mt-0.5">
                                            {{ $assocReserved }}</div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="border-b border-amber-200">
                            <td
                                class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-amber-200 bg-amber-50/70">
                                Test Specifications:</td>
                            <td class="p-3 font-bold text-slate-900 bg-white" colspan="3">{{ $assocSpecsVal }}</td>
                        </tr>
                        <tr class="border-b border-amber-200">
                            <td
                                class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-amber-200 bg-amber-50/70">
                                Test / Examination Conducted:</td>
                            <td class="p-3 font-mono-code font-black text-slate-900 bg-white" colspan="3">
                                {{ $assocMethodsVal }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    @endforeach




    </main>
    </div>
    </div>

    <script>
        function onReviewLabExhibitChange(sel) {
            if (!sel) return;
            const opt = sel.options[sel.selectedIndex];
            if (!opt) return;
            const exhVal = opt.value;
            const exId = opt.getAttribute('data-ex-id') || exhVal || '';
            const hiddenExIdInput = document.getElementById('labFormExhibitId');
            if (hiddenExIdInput) {
                hiddenExIdInput.value = exId;
            }
            if (!exhVal || exhVal === '') {
                const exhNoInput = document.getElementById('labExhibitNumberInput');
                const descInput = document.getElementById('labExhibitDescriptionInput');
                const subIdInput = document.getElementById('confirmatorySubstanceIdentificationReview');
                if (exhNoInput) exhNoInput.value = '';
                if (descInput) descInput.value = '';
                if (subIdInput) subIdInput.value = '';
                return;
            }
            const nameVal = opt.getAttribute('data-name') || '';
            const qtyVal = opt.getAttribute('data-qty') || '';
            const weightVal = opt.getAttribute('data-weight') || '';
            const unitVal = opt.getAttribute('data-unit') || '';
            const hasSaved = opt.getAttribute('data-has-saved');
            const isSaved = (hasSaved === 'true' || hasSaved === '1');
            const formContainer = document.getElementById('section5FormContainer');
            const form = document.getElementById('section5LabForm');
            const readOnlyView = document.getElementById('section5ReadOnlyDisplay');

            if (readOnlyView) readOnlyView.classList.add('hidden');
            if (formContainer && window.activeLabWorkflowMode === 'nlr') {
                formContainer.classList.remove('hidden');
                if (form) form.classList.remove('hidden');
            }

            if (window.activeLabWorkflowMode === 'gsa') {
                if (formContainer) {
                    formContainer.classList.add('hidden', 'opacity-60', 'pointer-events-none', 'select-none');
                    formContainer.setAttribute('aria-disabled', 'true');
                }
                if (form) {
                    form.classList.add('hidden');
                    form.querySelectorAll('input, select, textarea, button').forEach(el => {
                        if (!el.disabled) {
                            el.setAttribute('data-gsa-disabled', 'true');
                            el.disabled = true;
                        }
                    });
                }
            }
            const savedConfirmatory = opt.getAttribute('data-confirmatory') || '';
            const savedSubstanceId = opt.getAttribute('data-substance-id') || nameVal;
            const savedTestMethods = opt.getAttribute('data-test-methods') || '';
            const savedSpecifications = opt.getAttribute('data-specifications') || '';
            const savedRemark = opt.getAttribute('data-remark') || '';
            const savedPurpose = opt.getAttribute('data-purpose-of-examination') || '';
            const savedSampleTaken = opt.getAttribute('data-sample-taken') || '';
            const savedSampleReserved = opt.getAttribute('data-sample-reserved') || '';
            const savedFindings = opt.getAttribute('data-findings') || '';

            const exhNoInput = document.getElementById('labExhibitNumberInput');
            const descInput = document.getElementById('labExhibitDescriptionInput');
            const purposeInput = document.getElementById('labPurposeOfExaminationInput');
            const subIdInput = document.getElementById('confirmatorySubstanceIdentificationReview');
            const sampleDescInput = document.getElementById('sampleWeightDescriptionReview');
            const unitsInput = document.getElementById('sampleWeightUnitsReview');
            const grossInput = document.getElementById('sampleWeightGrossReview');
            const takenInput = document.getElementById('sampleWeightTakenReview');
            const reservedInput = document.getElementById('sampleWeightReservedReview');
            const notesInput = document.getElementById('formLabNotes');
            const unitAddon = document.getElementById('sampleWeightTakenUnitAddonReview');
            const confMethods = document.getElementById('confirmatoryTestMethodsReview');
            const confSpecs = document.getElementById('confirmatorySpecificationsReview');
            const confRemark = document.getElementById('confirmatoryRemarkReview');

            const pkgVal = opt.getAttribute('data-pkg') || '';
            let unitsText = (qtyVal && pkgVal) ? `${qtyVal} ${pkgVal}`.trim() : (qtyVal || pkgVal || '');
            let grossWeightText = (weightVal && unitVal) ? `${weightVal} ${unitVal}`.trim() : (weightVal || unitVal ||
                unitsText || '');

            if (exhNoInput && exhVal) exhNoInput.value = opt.getAttribute('data-exhibit-number') || ('EXH-0' + exhVal);
            if (descInput) descInput.value = nameVal;
            if (purposeInput) purposeInput.value = savedPurpose;
            if (subIdInput) subIdInput.value = savedSubstanceId || nameVal;
            if (sampleDescInput) sampleDescInput.value = nameVal;
            if (unitsInput) unitsInput.value = unitsText;
            if (grossInput) grossInput.value = grossWeightText;
            if (unitAddon && unitVal) unitAddon.textContent = unitVal;

            if (isSaved) {
                const detectedContainer = document.getElementById('detectedSubstancesListContainerReview');
                if (detectedContainer) {
                    detectedContainer.innerHTML = '';
                    let savedDetList = [];
                    try {
                        const rawDet = opt.getAttribute('data-detected-substances');
                        if (rawDet) savedDetList = JSON.parse(rawDet);
                    } catch (e) {}
                    if (Array.isArray(savedDetList) && savedDetList.length > 0) {
                        savedDetList.forEach(item => {
                            const sName = typeof item === 'object' ? (item.substance || item.name || item
                                .compound || '') : String(item);
                            const sPct = typeof item === 'object' ? (item.percentage || item.purity || '') : '';
                            addDetectedSubstanceRowReview(sName, sPct);
                        });
                    } else {
                        addDetectedSubstanceRowReview(savedSubstanceId || nameVal || '', '');
                    }
                }
                if (takenInput) takenInput.value = savedSampleTaken;
                if (reservedInput) reservedInput.value = savedSampleReserved;
                if (notesInput) notesInput.value = savedFindings;
                if (confMethods) confMethods.value = savedTestMethods;
                if (confSpecs) confSpecs.value = savedSpecifications;
                if (confRemark) confRemark.value = savedRemark;
                if (typeof toggleConfirmatoryTestReview === 'function') {
                    toggleConfirmatoryTestReview(savedConfirmatory === 'NO' ? 'NO' : (savedConfirmatory === 'YES' ? 'YES' : 'NEUTRAL'));
                }

                // Set Badge to Completed for saved exhibit
                const badgeStatus = document.getElementById('section5BadgeStatus') || document.getElementById('badgeStatus');
                if (badgeStatus) {
                    badgeStatus.className =
                        'px-2 py-0.5 rounded text-[9px] font-mono-code font-bold uppercase bg-emerald-100 text-emerald-800 border border-emerald-300';
                    badgeStatus.textContent = '✓ LAB COMPLETED';
                }
            } else {
                // Exhibit has not been examined yet — unlock all Section 5 controls for input
                if (typeof unlockSection5Form === 'function') {
                    unlockSection5Form();
                }

                if (takenInput) takenInput.value = '';
                if (reservedInput) reservedInput.value = '';
                if (notesInput) notesInput.value = '';
                if (confMethods) confMethods.value = '';
                if (confSpecs) confSpecs.value = '';
                if (confRemark) confRemark.value = '';

                // Reset Presumptive Test
                const presRadios = document.querySelectorAll('input[name="form_presumptive_radio"]');
                presRadios.forEach(r => r.checked = false);

                const lblPresYes = document.getElementById('labelPresumptiveYes');
                const lblPresNo = document.getElementById('labelPresumptiveNo');
                if (lblPresYes) lblPresYes.className =
                    'relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-slate-200 bg-slate-50';
                if (lblPresNo) lblPresNo.className =
                    'relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-slate-200 bg-slate-50';

                const presContainer = document.getElementById('presumptiveTestRowsContainerReview');
                if (presContainer) {
                    const rows = presContainer.querySelectorAll('.presumptive-test-row-review');
                    rows.forEach((r, idx) => {
                        if (idx === 0) {
                            const presence = r.querySelector('.presumptive-presence-input-review');
                            const percent = r.querySelector('.presumptive-percentage-input-review');
                            if (presence) presence.value = '';
                            if (percent) percent.value = '';
                        } else {
                            r.remove();
                        }
                    });
                }

                const presResultInput = document.getElementById('presumptiveReactionResultInputReview');
                if (presResultInput) presResultInput.value = '';

                const presDetailsContainer = document.getElementById('presumptiveReactionDetailsContainerReview');
                if (presDetailsContainer) presDetailsContainer.classList.add('hidden');

                const detectedContainer = document.getElementById('detectedSubstancesListContainerReview');
                if (detectedContainer) {
                    detectedContainer.innerHTML = '';
                    addDetectedSubstanceRowReview(nameVal || '', '');
                }

                if (typeof toggleConfirmatoryTestReview === 'function') {
                    toggleConfirmatoryTestReview('NEUTRAL');
                }

                // Reset Lab Status Badges to PENDING for unexamined exhibit
                const badgeStatus = document.getElementById('section5BadgeStatus') || document.getElementById(
                    'badgeStatus');
                if (badgeStatus) {
                    badgeStatus.className =
                        'px-2 py-0.5 rounded text-[9px] font-mono-code font-bold uppercase bg-slate-100 text-slate-800 border border-slate-300';
                    badgeStatus.textContent = '⭕ LAB PENDING';
                }
            }
        }

        const caseNumber = @json($case->case_number);
        const officerName = @json($officerName);
        const officerRole = @json($officerRole);

        /* ── Live Clock ─────────────────────────────── */
        function updateClock() {
            const now = new Date();
            const pad = n => String(n).padStart(2, '0');
            document.getElementById('liveClock').textContent =
                `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}  ${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())} UTC`;
        }
        updateClock();
        setInterval(updateClock, 1000);

        /* ── Approve Case Action ─────────────────────── */
        async function approveCaseAction(ref) {
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch(`/cases/${ref}/approve`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    }
                });
                const data = await res.json();

                if (res.ok && data.success) {
                    // Update UI status
                    const badge = document.getElementById('headerStatusBadge');
                    if (badge) {
                        badge.innerHTML =
                            `<span class="status-badge status-done"><svg class="w-3 h-3 mr-1 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Authorized</span>`;
                    }

                    // Hide approve/decline buttons and display signed badge
                    const container = document.getElementById('executiveActionContainer');
                    if (container) {
                        container.innerHTML =
                            `<div class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-300 font-mono-code font-bold text-xs shadow-sm"><svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> OFFICIALLY APPROVED &amp; SIGNED BY DIRECTORATE HEAD</div>`;
                    }

                    showToast(
                        `✔ Case ${ref} has been officially APPROVED and signed off by ${officerName} (${officerRole}).`
                    );
                } else {
                    showToast(`❌ Error approving case: ${data.message || 'Server error'}`);
                }
            } catch (e) {
                showToast(`❌ Error connecting to server.`);
            }
        }

        /* ── Decline Case Action ─────────────────────── */
        async function declineCaseAction(ref) {
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch(`/cases/${ref}/decline`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    }
                });
                const data = await res.json();

                if (res.ok && data.success) {
                    // Update UI status
                    const badge = document.getElementById('headerStatusBadge');
                    if (badge) {
                        badge.innerHTML =
                            `<span class="status-badge status-declined">Declined</span>`;
                    }

                    const container = document.getElementById('executiveActionContainer');
                    if (container) {
                        container.innerHTML =
                            `<div class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-red-50 text-red-800 border border-red-300 font-mono-code font-bold text-xs shadow-sm">CASE DECLINED BY DIRECTORATE HEAD</div>`;
                    }

                    showToast(`❌ Case ${ref} has been officially DECLINED by ${officerName} (${officerRole}).`);
                } else {
                    showToast(`❌ Error declining case: ${data.message || 'Server error'}`);
                }
            } catch (e) {
                showToast(`❌ Error connecting to server.`);
            }
        }

        /* ── Toast Notification (Bootstrap Design) ──────────────────────── */
        function showToast(msg) {
            let container = document.getElementById('bsToastContainer');
            if (!container) {
                container = document.createElement('div');
                container.id = 'bsToastContainer';
                container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                container.style.zIndex = '999999';
                document.body.appendChild(container);
            }

            const isError = msg.includes('❌') || msg.toLowerCase().includes('error') || msg.toLowerCase().includes('cannot') || msg.toLowerCase().includes('failed');
            const isSuccess = msg.includes('✔') || msg.toLowerCase().includes('success');

            let bgClass = 'bg-dark';
            if (isError) bgClass = 'bg-danger';
            else if (isSuccess) bgClass = 'bg-success';

            const toastEl = document.createElement('div');
            toastEl.className = `toast align-items-center text-white ${bgClass} border-0 shadow-lg mb-2 show fade`;
            toastEl.setAttribute('role', 'alert');
            toastEl.setAttribute('aria-live', 'assertive');
            toastEl.setAttribute('aria-atomic', 'true');

            toastEl.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="toast-body font-monospace text-xs py-2.5 px-3" style="font-size: 13px; line-height: 1.4;">
                        ${msg}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.closest('.toast').remove()"></button>
                </div>
            `;

            container.appendChild(toastEl);

            setTimeout(() => {
                if (toastEl.parentNode) {
                    toastEl.classList.remove('show');
                    setTimeout(() => toastEl.remove(), 300);
                }
            }, 4500);
        }


        /* ── Joint Suspects Warning Alert Modal (Bootstrap Design) ─────────────────── */
        function showUnexaminedSuspectsAlert(unexaminedList, message) {
            let existingModal = document.getElementById('unexaminedSuspectsModal');
            if (existingModal) existingModal.remove();

            const suspectItems = (unexaminedList || []).map(s => `
            <li class="list-group-item list-group-item-danger d-flex align-items-center gap-2 py-2 px-3 rounded-2 text-danger font-monospace small mb-1 border-danger border-opacity-25">
                <svg class="bi flex-shrink-0" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>
                <span>${escapeHtml(s)}</span>
            </li>
        `).join('');

            const modalHtml = `
        <div id="unexaminedSuspectsModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); z-index: 99999;" role="dialog" aria-modal="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header bg-danger text-white border-0 py-3 px-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 bg-white bg-opacity-20 d-flex align-items-center justify-content-center p-2">
                                <svg class="bi text-white" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>
                            </div>
                            <div>
                                <h5 class="modal-title fw-bold text-white mb-0">Cannot Save Case — Unexamined Suspects</h5>
                                <span class="badge bg-danger-subtle text-danger-emphasis font-monospace text-uppercase" style="font-size: 10px;">Joint Suspects Validation Guard</span>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" onclick="document.getElementById('unexaminedSuspectsModal').remove()"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="text-secondary mb-3">
                            This is a <strong>joint suspects case</strong> involving multiple individuals. The system does not allow saving the case as completed while unexamined suspects remain.
                        </p>
                        <div class="mb-3">
                            <span class="fw-bold text-uppercase text-secondary font-monospace d-block mb-2" style="font-size: 11px;">Unexamined Suspect(s) Remaining:</span>
                            <ul class="list-group list-group-flush border-0 overflow-auto" style="max-height: 160px;">
                                ${suspectItems}
                            </ul>
                        </div>
                        <div class="alert alert-warning border-warning-subtle d-flex align-items-start gap-2 mb-0 p-3 rounded-3">
                            <span class="fs-5">💡</span>
                            <div class="small">
                                <strong>Required Action:</strong> Select each remaining suspect in <strong>Section 5 (Laboratory Examination Form)</strong>, enter their examination results, and save before submitting the case.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0 py-3 px-4 justify-content-end">
                        <button type="button" onclick="document.getElementById('unexaminedSuspectsModal').remove(); const el = document.getElementById('section5LabForm') || document.getElementById('labExhibitSelect'); if(el) el.scrollIntoView({behavior:'smooth'});" class="btn btn-dark fw-bold px-4 rounded-3 d-flex align-items-center gap-2">
                            <span>OK, Complete Suspect Examinations</span>
                            <svg class="bi" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>`;

            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }


        /* ── Submit Unit Progress Action (Real-Time DOM Update) ────────────────── */
        async function submitUnitProgress(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('unitSubmitBtn');
            if (submitBtn) submitBtn.disabled = true;

            const unitName = document.getElementById('updateUnitName').value;
            const unitStatus = document.getElementById('updateUnitStatus').value;
            const officer = document.getElementById('updateOfficer').value;
            const notes = document.getElementById('updateNotes').value;

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch(`/cases/${caseNumber}/update-unit-progress`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        unit_name: unitName,
                        unit_status: unitStatus,
                        officer: officer,
                        notes: notes
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    const compCount = data.completed_count || 0;
                    const updatedUnitKey = data.unit_name || unitName;
                    const unitProgMap = data.unit_progress || {};

                    // Update unitProgressData state
                    if (unitProgMap[updatedUnitKey]) {
                        unitProgressData[updatedUnitKey] = unitProgMap[updatedUnitKey];
                    }

                    // 1. Update target unit card in real-time
                    const slug = updatedUnitKey.toLowerCase().replace(/[^a-z0-9]/g, '');
                    const cardEl = document.getElementById(`unitCard-${slug}`);
                    const badgeEl = document.getElementById(`unitBadge-${slug}`);
                    const officerBoxEl = document.getElementById(`unitOfficerBox-${slug}`);
                    const notesBoxEl = document.getElementById(`unitNotesBox-${slug}`);
                    const updatedBoxEl = document.getElementById(`unitUpdatedBox-${slug}`);

                    const st = (unitStatus || 'PENDING').toUpperCase();

                    if (cardEl) {
                        if (st === 'COMPLETED') {
                            cardEl.className =
                                'p-4 rounded-xl border transition-all flex flex-col justify-between bg-emerald-50/70 border-emerald-300 shadow-xs';
                        } else if (st === 'IN_PROGRESS') {
                            cardEl.className =
                                'p-4 rounded-xl border transition-all flex flex-col justify-between bg-amber-50/70 border-amber-300 shadow-xs';
                        } else {
                            cardEl.className =
                                'p-4 rounded-xl border transition-all flex flex-col justify-between bg-slate-50 border-slate-200';
                        }
                    }

                    if (badgeEl) {
                        if (st === 'COMPLETED') {
                            badgeEl.className =
                                'text-[9px] font-extrabold font-mono-code uppercase px-2 py-0.5 rounded-md border bg-emerald-100 text-emerald-800 border-emerald-300';
                            badgeEl.textContent = '✓ COMPLETED';
                        } else if (st === 'IN_PROGRESS') {
                            badgeEl.className =
                                'text-[9px] font-extrabold font-mono-code uppercase px-2 py-0.5 rounded-md border bg-amber-100 text-amber-800 border-amber-300';
                            badgeEl.textContent = '⏳ IN PROGRESS';
                        } else {
                            badgeEl.className =
                                'text-[9px] font-extrabold font-mono-code uppercase px-2 py-0.5 rounded-md border bg-slate-200 text-slate-700 border-slate-300';
                            badgeEl.textContent = '⭕ PENDING';
                        }
                    }

                    if (officerBoxEl) {
                        officerBoxEl.innerHTML = '';
                    }

                    if (notesBoxEl) {
                        notesBoxEl.innerHTML = notes ?
                            `<div class="text-[11px] text-slate-700 mt-2 p-2.5 bg-white/90 rounded-lg border border-slate-200/80 leading-relaxed"><span class="font-bold text-[10px] text-slate-400 block font-mono-code uppercase mb-0.5">Unit Findings:</span>"${escapeHtml(notes)}"</div>` :
                            '';
                    }

                    if (updatedBoxEl) {
                        updatedBoxEl.innerHTML =
                            `<div class="text-[10px] font-mono-code text-slate-400 mt-3 pt-2 border-t border-slate-200/60">Updated: Just Now</div>`;
                    }

                    // 2. Update progress tracker counter and progress bar
                    const totalUnits = unitsListArray.length;
                    const badgeCounter = document.getElementById('unitCompletedCountBadge');
                    if (badgeCounter) {
                        badgeCounter.textContent = `${compCount}/${totalUnits} Units Completed`;
                    }

                    const pct = Math.round((compCount / totalUnits) * 100);
                    const progressBar = document.getElementById('unitProgressBar');
                    if (progressBar) {
                        progressBar.style.width = `${Math.max(18, pct)}%`;
                        progressBar.textContent = `${pct}%`;
                    }

                    const percentText = document.getElementById('unitProgressPercentText');
                    if (percentText) {
                        percentText.textContent = `${pct}%`;
                    }

                    // 3. Handle Completion or Pending Portal Updates - Immediately REMOVE/HIDE Unit Data Entry Portal
                    const portal = document.getElementById('unitDataEntryPortal');
                    const banner = document.getElementById('completedUnitsBanner');
                    const headerStatusBadge = document.getElementById('headerStatusBadge');

                    // Instantly hide the Unit Data Entry Portal section once saved!
                    if (portal) {
                        portal.classList.add('hidden');
                    }

                    if (compCount >= totalUnits) {
                        if (banner) banner.classList.remove('hidden');
                        if (headerStatusBadge) {
                            headerStatusBadge.innerHTML =
                                `<span class="status-badge bg-white text-slate-900 border border-slate-300">Awaiting Sign-off</span>`;
                        }
                        showToast(
                            `✔ Multi-Unit Processing Complete (${totalUnits}/${totalUnits} Units)! All ${totalUnits} specialized units have completed findings.`
                        );
                    } else {
                        showToast(
                            `✔ Progress for unit "${updatedUnitKey}" updated successfully! (${compCount}/${totalUnits} Units Completed)`
                        );
                    }
                } else {
                    if (data.unexamined_suspects && data.unexamined_suspects.length > 0) {
                        showUnexaminedSuspectsAlert(data.unexamined_suspects, data.message);
                    } else {
                        showToast(`❌ Error updating unit progress: ${data.message || 'Server error'}`);
                    }
                }
            } catch (err) {
                showToast(`❌ Connection error updating unit progress.`);
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        }

        function escapeHtml(str) {
            return (str || '').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        /* ── Dynamic Button Text: Save Case vs Submit Case for Approval ── */
        const unitProgressData = @json($unitProgress);
        const unitsListArray = @json($unitsList);

        function updateUnitSubmitButtonText() {
            const selectedUnit = document.getElementById('updateUnitName')?.value;
            const selectedStatus = document.getElementById('updateUnitStatus')?.value;
            const btnTextEl = document.getElementById('unitSubmitBtnText');
            if (!btnTextEl) return;

            let simulatedCompletedCount = 0;
            unitsListArray.forEach(u => {
                let st = (unitProgressData[u] && unitProgressData[u].status) ? unitProgressData[u].status
                    .toUpperCase() : 'PENDING';
                if (u === selectedUnit) {
                    st = selectedStatus ? selectedStatus.toUpperCase() : st;
                }
                if (st === 'COMPLETED') {
                    simulatedCompletedCount++;
                }
            });

            if (simulatedCompletedCount >= unitsListArray.length) {
                btnTextEl.innerText = 'Submit Case for Approval';
            } else {
                btnTextEl.innerText = 'Save Case';
            }
        }
        document.addEventListener('DOMContentLoaded', updateUnitSubmitButtonText);

        /* ── Notifications & Global Search ──────────── */
        function toggleNotificationDropdown() {
            const dropdown = document.getElementById('notificationDropdown');
            if (dropdown) dropdown.classList.toggle('hidden');
        }

        document.addEventListener('click', function(e) {
            const bellBtn = document.getElementById('notificationBellBtn');
            const dropdown = document.getElementById('notificationDropdown');
            if (bellBtn && dropdown && !bellBtn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const searchInput = document.getElementById('globalSearch');
                if (searchInput) searchInput.focus();
            }
        });

        function onPresumptiveRadioChange(val) {
            const lblYes = document.getElementById('labelPresumptiveYes');
            const lblNo = document.getElementById('labelPresumptiveNo');
            const container = document.getElementById('presumptiveReactionDetailsContainerReview');
            if (val === 'YES') {
                if (lblYes) lblYes.className =
                    'relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-emerald-500 bg-emerald-50/80 ring-2 ring-emerald-500/20';
                if (lblNo) lblNo.className =
                    'relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-slate-200 bg-slate-50';
                if (container) container.classList.remove('hidden');
            } else {
                if (lblYes) lblYes.className =
                    'relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-slate-200 bg-slate-50';
                if (lblNo) lblNo.className =
                    'relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-red-500 bg-red-50/80 ring-2 ring-red-500/20';
                if (container) container.classList.add('hidden');
            }
        }

        function toggleConfirmatoryTestReview(val) {
            const lblYes = document.getElementById('labelConfirmatoryYesReview');
            const lblNo = document.getElementById('labelConfirmatoryNoReview');
            const radYes = document.getElementById('confirmatoryTestYesReview');
            const radNo = document.getElementById('confirmatoryTestNoReview');
            const container = document.getElementById('sampleWeightContainerReview');
            const descInput = document.getElementById('sampleWeightDescriptionReview');
            const grossInput = document.getElementById('sampleWeightGrossReview');

            if (val === 'YES') {
                if (radYes) radYes.checked = true;
                if (radNo) radNo.checked = false;
                if (lblYes) lblYes.className =
                    'relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-emerald-500 bg-emerald-50/80 ring-2 ring-emerald-500/20';
                if (lblNo) lblNo.className =
                    'relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-slate-200 bg-slate-50';
                if (container) container.classList.remove('hidden');

                if (descInput && (!descInput.value || descInput.value.startsWith('e.g.'))) {
                    const defaultDrug = @json($defaultDrugName ?? '');
                    if (defaultDrug) descInput.value = defaultDrug;
                }
                if (grossInput && (!grossInput.value || grossInput.value.startsWith('e.g.'))) {
                    const defaultGross = @json($defaultGrossWeight ?? '');
                    if (defaultGross) grossInput.value = defaultGross;
                }
                const unitAddon = document.getElementById('sampleWeightTakenUnitAddonReview');
                if (unitAddon && grossInput && grossInput.value) {
                    const uMatch = grossInput.value.match(/(Kilograms?\s*\([^\)]+\)|Kilograms?|kg|Grams?|g|mg|lbs?)/i);
                    if (uMatch) unitAddon.textContent = uMatch[1];
                }
                if (typeof updateReservedWeightReview === 'function') updateReservedWeightReview();
            } else if (val === 'NO') {
                if (radYes) radYes.checked = false;
                if (radNo) radNo.checked = true;
                if (lblYes) lblYes.className =
                    'relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-slate-200 bg-slate-50';
                if (lblNo) lblNo.className =
                    'relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-red-500 bg-red-50/80 ring-2 ring-red-500/20';
                if (container) container.classList.add('hidden');
            } else {
                if (radYes) radYes.checked = false;
                if (radNo) radNo.checked = false;
                if (lblYes) lblYes.className =
                    'relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-slate-200 bg-slate-50';
                if (lblNo) lblNo.className =
                    'relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-slate-200 bg-slate-50';
                if (container) container.classList.add('hidden');
            }
        }

        function addPresumptiveTestRowReview() {
            const container = document.getElementById('presumptiveTestRowsContainerReview');
            if (!container) return;

            const rows = container.querySelectorAll('.presumptive-test-row-review');
            const newIdx = rows.length + 1;

            const rowDiv = document.createElement('div');
            rowDiv.className =
                'presumptive-test-row-review p-3.5 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-2.5';
            rowDiv.innerHTML = `
            <div class="flex items-center justify-between text-[11px] font-extrabold text-slate-700 uppercase font-mono-code">
                <span class="test-row-title-review">Test Result #${newIdx}</span>
                <button type="button" onclick="removePresumptiveTestRowReview(this)" class="remove-test-row-btn-review px-2 py-0.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg text-[10px] font-bold border border-transparent hover:border-red-200 transition-colors cursor-pointer">
                    &times; Remove
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                        Presence of <span class="text-red-600 font-bold">*</span>
                    </label>
                    <input 
                        type="text" 
                        class="presumptive-presence-input-review w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs" 
                        onchange="updatePresumptiveReactionTextReview()" 
                        oninput="updatePresumptiveReactionTextReview()" 
                        placeholder="e.g. Cocaine, Heroin, THC, Methamphetamine"
                    >
                </div>
                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                        Percentage (%) <span class="text-red-600 font-bold">*</span>
                    </label>
                    <div class="flex items-center rounded-xl shadow-2xs overflow-hidden">
                        <input 
                            type="text" 
                            class="presumptive-percentage-input-review w-full pl-3.5 pr-2 py-2.5 bg-white border border-y border-l border-slate-300 rounded-l-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 font-mono-code" 
                            onchange="updatePresumptiveReactionTextReview()" 
                            oninput="updatePresumptiveReactionTextReview()" 
                            placeholder="e.g. 85.5"
                        >
                        <span class="inline-flex items-center px-3 py-2.5 bg-slate-100 border border-slate-300 rounded-r-xl text-slate-700 font-extrabold text-xs font-mono-code shrink-0">%</span>
                    </div>
                </div>
            </div>
        `;
            container.appendChild(rowDiv);
            updatePresumptiveRemoveButtonsReview();
            updatePresumptiveReactionTextReview();
        }

        function removePresumptiveTestRowReview(btn) {
            const row = btn.closest('.presumptive-test-row-review');
            if (row) {
                row.remove();
                reindexPresumptiveTestRowsReview();
                updatePresumptiveRemoveButtonsReview();
                updatePresumptiveReactionTextReview();
            }
        }

        function reindexPresumptiveTestRowsReview() {
            const container = document.getElementById('presumptiveTestRowsContainerReview');
            if (!container) return;
            const rows = container.querySelectorAll('.presumptive-test-row-review');
            rows.forEach((row, idx) => {
                const titleSpan = row.querySelector('.test-row-title-review');
                if (titleSpan) titleSpan.innerText = `Test Result #${idx + 1}`;
            });
        }

        function updatePresumptiveRemoveButtonsReview() {
            const container = document.getElementById('presumptiveTestRowsContainerReview');
            if (!container) return;
            const rows = container.querySelectorAll('.presumptive-test-row-review');
            rows.forEach(row => {
                const btn = row.querySelector('.remove-test-row-btn-review');
                if (btn) {
                    if (rows.length > 1) {
                        btn.classList.remove('hidden');
                    } else {
                        btn.classList.add('hidden');
                    }
                }
            });
        }

        function updatePresumptiveReactionTextReview() {
            const container = document.getElementById('presumptiveTestRowsContainerReview');
            const inputEl = document.getElementById('presumptiveReactionResultInputReview');
            if (!container || !inputEl) return;

            const rows = container.querySelectorAll('.presumptive-test-row-review');
            let results = [];

            rows.forEach((row, idx) => {
                const presence = row.querySelector('.presumptive-presence-input-review')?.value?.trim() || '';
                let rawPct = row.querySelector('.presumptive-percentage-input-review')?.value?.trim() || '';
                let percentage = rawPct;
                if (percentage && !percentage.endsWith('%')) {
                    percentage = percentage + '%';
                }

                let parts = [];
                if (presence) parts.push(`Presence of: ${presence}`);
                if (percentage) parts.push(`Purity/Percentage: ${percentage}`);

                if (parts.length > 0) {
                    if (rows.length > 1) {
                        results.push(`[${idx + 1}] ${parts.join(' - ')}`);
                    } else {
                        results.push(parts.join(' - '));
                    }
                }
            });

            if (results.length > 0) {
                inputEl.value = `YES (${results.join('; ')})`;
            } else {
                inputEl.value = '';
            }
        }

        function onLabStatusChange(val) {
            const btnTextSpan = document.getElementById('saveSection5LabBtnText') || document.querySelector(
                '#saveSection5LabBtn span');
            if (btnTextSpan) {
                btnTextSpan.textContent = 'Save Seizure Item & Examination Information';
            }

            const selectEl = document.getElementById('formLabStatus');
            const dotIndicator = document.getElementById('labStatusDotIndicator');
            const text = document.getElementById('labStatusText');
            const container = document.getElementById('labStatusContainer');

            if (val === 'COMPLETED') {
                if (selectEl) {
                    selectEl.className =
                        'w-full appearance-none pl-4 pr-10 py-3 bg-white border-2 border-emerald-400 text-slate-900 font-extrabold text-xs outline-none transition-all shadow-sm cursor-pointer focus:ring-4 focus:ring-emerald-500/20';
                    selectEl.style.borderRadius = '14px';
                }
                if (container) {
                    container.className =
                        'p-4 bg-slate-50 border border-slate-200 space-y-3 shadow-sm transition-all duration-300';
                    container.style.borderRadius = '18px';
                }
                if (dotIndicator) {
                    dotIndicator.className =
                        'inline-flex items-center text-[10px] font-mono-code font-extrabold uppercase px-2.5 py-1 shadow-2xs transition-all bg-emerald-100 text-emerald-800 border border-emerald-300';
                    dotIndicator.style.borderRadius = '10px';
                }
                if (text) text.textContent = 'COMPLETED';
            } else if (val === 'IN_PROGRESS') {
                if (selectEl) {
                    selectEl.className =
                        'w-full appearance-none pl-4 pr-10 py-3 bg-white border-2 border-amber-400 text-slate-900 font-extrabold text-xs outline-none transition-all shadow-sm cursor-pointer focus:ring-4 focus:ring-amber-500/20';
                    selectEl.style.borderRadius = '14px';
                }
                if (container) {
                    container.className =
                        'p-4 bg-slate-50 border border-slate-200 space-y-3 shadow-sm transition-all duration-300';
                    container.style.borderRadius = '18px';
                }
                if (dotIndicator) {
                    dotIndicator.className =
                        'inline-flex items-center text-[10px] font-mono-code font-extrabold uppercase px-2.5 py-1 shadow-2xs transition-all bg-amber-100 text-amber-800 border border-amber-300';
                    dotIndicator.style.borderRadius = '10px';
                }
                if (text) text.textContent = 'IN PROGRESS';
            } else {
                if (selectEl) {
                    selectEl.className =
                        'w-full appearance-none pl-4 pr-10 py-3 bg-white border-2 border-emerald-400 text-slate-900 font-extrabold text-xs outline-none transition-all shadow-sm cursor-pointer focus:ring-4 focus:ring-emerald-500/20';
                    selectEl.style.borderRadius = '14px';
                }
                if (container) {
                    container.className =
                        'p-4 bg-slate-50 border border-slate-200 space-y-3 shadow-sm transition-all duration-300';
                    container.style.borderRadius = '18px';
                }
                if (dotIndicator) {
                    dotIndicator.className =
                        'inline-flex items-center text-[10px] font-mono-code font-extrabold uppercase px-2.5 py-1 shadow-2xs transition-all bg-emerald-100 text-emerald-800 border border-emerald-300';
                    dotIndicator.style.borderRadius = '10px';
                }
                if (text) text.textContent = 'PENDING';
            }
        }

        /* ── Submit Section 5 Lab Examination & Update Progress ────────── */
        async function submitSection5LabExam(e) {
            e.preventDefault();
            const saveBtn = document.getElementById('saveSection5LabBtn');
            if (saveBtn) saveBtn.disabled = true;

            // Auto-set Date Examination Completed to today's date if not already filled
            const todayStr = new Date().toISOString().split('T')[0];
            const dateCompletedInput = document.getElementById('labDateCompletedInput');
            if (dateCompletedInput && !dateCompletedInput.value) {
                dateCompletedInput.value = todayStr;
            }

            const dateReceived = document.getElementById('labDateReceivedInput')?.value || todayStr;
            const dateStarted = document.getElementById('labDateStartedInput')?.value || todayStr;
            const dateCompleted = dateCompletedInput?.value || todayStr;

            const labUnit = document.getElementById('formLabUnit')?.value || 'Chemistry & Narcotics Unit';
            const analyst = document.getElementById('formLabAnalyst')?.value || @json($labAnalyst ?? $defaultLabHeadName);

            // Active exhibit index resolution
            const exhibitSelect = document.getElementById('labExhibitSelect');
            const selectedOpt = exhibitSelect ? exhibitSelect.options[exhibitSelect.selectedIndex] : null;
            const activeIdx = window.activeLabExhibitIndex || (selectedOpt ? (selectedOpt.getAttribute('data-index') || selectedOpt.value || '1') : '1');

            // ── Presumptive Reaction validation (supports per-exhibit dynamic card, review radio, & legacy inputs) ──
            let presumptiveChoice = null;
            const checkedRadio = document.querySelector('input[name="form_presumptive_radio"]:checked');
            if (checkedRadio) {
                presumptiveChoice = checkedRadio.value;
            } else {
                const dynYes = document.getElementById(`presumptiveTestYes_${activeIdx}`);
                const dynNo = document.getElementById(`presumptiveTestNo_${activeIdx}`);
                if (dynYes && dynYes.checked) {
                    presumptiveChoice = 'YES';
                } else if (dynNo && dynNo.checked) {
                    presumptiveChoice = 'NO';
                } else {
                    const legYes = document.getElementById('presumptiveTestYes');
                    const legNo = document.getElementById('presumptiveTestNo');
                    if (legYes && legYes.checked) {
                        presumptiveChoice = 'YES';
                    } else if (legNo && legNo.checked) {
                        presumptiveChoice = 'NO';
                    } else {
                        const customVal = (document.getElementById(`presumptiveReactionResultInput_${activeIdx}`)?.value ||
                                           document.getElementById('presumptiveReactionResultInput')?.value || '').trim();
                        if (customVal.toUpperCase().startsWith('YES')) {
                            presumptiveChoice = 'YES';
                        } else if (customVal.toUpperCase().startsWith('NO')) {
                            presumptiveChoice = 'NO';
                        }
                    }
                }
            }

            if (!presumptiveChoice) {
                alert('Please select a Presumptive Reaction result (YES or NO) before saving the examination.');
                if (saveBtn) saveBtn.disabled = false;
                return;
            }

            let presumptive;
            let presumptivePresence = null;
            let presumptivePercentage = null;
            if (presumptiveChoice === 'NO') {
                presumptive = 'NO';
            } else {
                let rows = document.querySelectorAll(`#presumptiveTestRowsContainer_${activeIdx} .presumptive-test-row`);
                if (!rows || rows.length === 0) {
                    rows = document.querySelectorAll('#presumptiveTestRowsContainerReview .presumptive-test-row-review');
                }
                if (!rows || rows.length === 0) {
                    rows = document.querySelectorAll('#presumptiveTestRowsContainer .presumptive-test-row');
                }

                const validDetails = [];
                const presList = [];
                const pctList = [];
                let hasPartialRow = false;

                if (rows && rows.length > 0) {
                    rows.forEach((row) => {
                        const presence = (row.querySelector('.presumptive-presence-input')?.value ||
                                          row.querySelector('.presumptive-presence-input-review')?.value || '').trim();
                        const percent = (row.querySelector('.presumptive-percentage-input')?.value ||
                                         row.querySelector('.presumptive-percentage-input-review')?.value || '').trim();
                        if (presence && percent) {
                            const pctFormatted = percent.endsWith('%') ? percent : `${percent}%`;
                            validDetails.push(`Presence of: ${presence} - Purity/Percentage: ${pctFormatted}`);
                            presList.push(presence);
                            pctList.push(pctFormatted);
                        } else if ((presence && !percent) || (!presence && percent)) {
                            hasPartialRow = true;
                        }
                    });
                }

                if (hasPartialRow) {
                    alert('One or more presumptive test rows have only "Presence of" or only "Percentage (%)" filled. Please complete both fields or clear the row.');
                    if (saveBtn) saveBtn.disabled = false;
                    return;
                }

                if (validDetails.length === 0) {
                    const customResultInput = document.getElementById(`presumptiveReactionResultInput_${activeIdx}`) ||
                                              document.getElementById('presumptiveReactionResultInput');
                    const customVal = customResultInput?.value?.trim();
                    if (customVal && customVal !== '' && !customVal.startsWith('NO')) {
                        presumptive = customVal;
                    } else {
                        alert(
                            'Please enter both "Presence of" and "Percentage (%)" for at least one presumptive test result before saving.'
                        );
                        if (saveBtn) saveBtn.disabled = false;
                        return;
                    }
                } else {
                    presumptive = validDetails.length > 1
                        ? `YES (${validDetails.map((d, i) => `[${i + 1}] ${d}`).join('; ')})`
                        : `YES (${validDetails[0]})`;
                    presumptivePresence = presList.join('; ');
                    presumptivePercentage = pctList.join('; ');
                }
            }

            // ── Confirmatory Test validation (supports per-exhibit dynamic card, review radio, & legacy inputs) ──
            let confirmatoryChoice = null;
            const confirmatoryChecked = document.querySelector('input[name="form_confirmatory_radio"]:checked');
            if (confirmatoryChecked) {
                confirmatoryChoice = confirmatoryChecked.value;
            } else {
                const dynConfYes = document.getElementById(`confirmatoryTestYes_${activeIdx}`);
                const dynConfNo = document.getElementById(`confirmatoryTestNo_${activeIdx}`);
                if (dynConfYes && dynConfYes.checked) {
                    confirmatoryChoice = 'YES';
                } else if (dynConfNo && dynConfNo.checked) {
                    confirmatoryChoice = 'NO';
                } else {
                    const legConfYes = document.getElementById('confirmatoryTestYes');
                    const legConfNo = document.getElementById('confirmatoryTestNo');
                    if (legConfYes && legConfYes.checked) {
                        confirmatoryChoice = 'YES';
                    } else if (legConfNo && legConfNo.checked) {
                        confirmatoryChoice = 'NO';
                    } else {
                        const confResVal = (document.getElementById(`confirmatoryTestResultInput_${activeIdx}`)?.value ||
                                            document.getElementById('confirmatoryTestResultInput')?.value || '').trim();
                        if (confResVal.toUpperCase() === 'YES') {
                            confirmatoryChoice = 'YES';
                        } else if (confResVal.toUpperCase() === 'NO') {
                            confirmatoryChoice = 'NO';
                        }
                    }
                }
            }
            if (!confirmatoryChoice) {
                confirmatoryChoice = 'NO';
            }
            const confirmatory = confirmatoryChoice === 'YES' ? 'YES' : 'NO';

            const notes = document.getElementById('formLabNotes')?.value || '';

            const exhibitOwnerName = selectedOpt ? selectedOpt.getAttribute('data-owner') || '' : '';
            const exhibitIsAssociate = selectedOpt ? (selectedOpt.getAttribute('data-is-associate') === '1') : false;

            let status = 'COMPLETED';
            if (exhibitSelect && exhibitSelect.options.length > 1) {
                const options = Array.from(exhibitSelect.options);
                const otherUnexamined = options.filter(opt => opt !== selectedOpt && opt.getAttribute(
                    'data-has-saved') !== '1' && opt.getAttribute('data-has-saved') !== 'true');
                if (otherUnexamined.length > 0) {
                    status = 'IN_PROGRESS';
                }
            }

            const sampleDesc = document.getElementById(`sampleWeightDescription_${activeIdx}`)?.value
                || document.getElementById('sampleWeightDescriptionReview')?.value
                || document.getElementById('sampleWeightDescription')?.value
                || '';
            const sampleUnits = document.getElementById(`sampleWeightUnits_${activeIdx}`)?.value
                || document.getElementById('sampleWeightUnitsReview')?.value
                || document.getElementById('sampleWeightUnits')?.value
                || '';
            const sampleGross = document.getElementById(`sampleWeightGross_${activeIdx}`)?.value
                || document.getElementById('sampleWeightGrossReview')?.value
                || document.getElementById('sampleWeightGross')?.value
                || '';
            const sampleTaken = document.getElementById(`sampleWeightTaken_${activeIdx}`)?.value
                || document.getElementById('sampleWeightTakenReview')?.value
                || document.getElementById('sampleWeightTaken')?.value
                || '';
            const sampleReserved = document.getElementById(`sampleWeightReserved_${activeIdx}`)?.value
                || document.getElementById('sampleWeightReservedReview')?.value
                || document.getElementById('sampleWeightReserved')?.value
                || '';
            const confirmatorySubstance = document.getElementById(`confirmatorySubstanceIdentification_${activeIdx}`)?.value
                || document.getElementById('confirmatorySubstanceIdentificationReview')?.value
                || document.getElementById('confirmatorySubstanceIdentification')?.value
                || '';
            const detectedSubstances = (typeof getDetectedSubstancesDataForExhibit === 'function' ? getDetectedSubstancesDataForExhibit(activeIdx) : null)
                || (typeof getDetectedSubstancesDataReview === 'function' ? getDetectedSubstancesDataReview() : []);
            const confirmatoryMethods = document.getElementById(`confirmatoryTestMethods_${activeIdx}`)?.value
                || document.getElementById('confirmatoryTestMethodsReview')?.value
                || document.getElementById('confirmatoryTestMethods')?.value
                || '';
            const confirmatorySpecs = document.getElementById(`confirmatorySpecifications_${activeIdx}`)?.value
                || document.getElementById('confirmatorySpecificationsReview')?.value
                || document.getElementById('confirmatorySpecifications')?.value
                || '';
            const confirmatoryRemark = document.getElementById(`confirmatoryRemark_${activeIdx}`)?.value
                || document.getElementById('confirmatoryRemarkReview')?.value
                || document.getElementById('confirmatoryRemark')?.value
                || '';

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch(`/cases/${caseNumber}/update-unit-progress`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        unit_name: 'Lab Analysis',
                        unit_status: status,
                        officer: analyst,
                        notes: notes,
                        exhibit_id: document.getElementById('labFormExhibitId')?.value || (selectedOpt ?
                            (selectedOpt.getAttribute('data-ex-id') || selectedOpt.value) : ''),
                        drug_type: selectedOpt ? (selectedOpt.getAttribute('data-substance-id') ||
                            selectedOpt.getAttribute('data-name') || '') : '',
                        exhibit_owner_name: exhibitOwnerName,
                        exhibit_is_associate: exhibitIsAssociate,
                        lab_exam_reference: document.getElementById('labExamReferenceInput')?.value ||
                            '',
                        lab_exhibit_no: document.getElementById('labExhibitNumberInput')?.value ||
                            '',
                        lab_exhibit_number: document.getElementById('labExhibitNumberInput')?.value ||
                            '',
                        laboratory_number: document.getElementById('formLabNumber')?.value || '',
                        purpose_of_examination: document.getElementById('labPurposeOfExaminationInput')?.value || '',
                        preliminary_exam_procedure: document.getElementById('labPreliminaryExamProcedureInput')?.value || '',
                        screening_method: Array.from(document.querySelectorAll('#screeningMethodsContainer .screening-method-input')).map(el => el.value.trim()).filter(Boolean).join(', '),
                        lab_unit: labUnit,
                        presumptive_result: presumptive,
                        presumptive_presence: presumptivePresence,
                        presumptive_percentage: presumptivePercentage,
                        confirmatory_test_result: confirmatory,
                        sample_weight_description: sampleDesc,
                        sample_weight_units: sampleUnits,
                        sample_weight_gross: sampleGross,
                        sample_weight_taken: sampleTaken,
                        sample_weight_reserved: sampleReserved,
                        confirmatory_substance_identification: confirmatorySubstance,
                        detected_substances: detectedSubstances,
                        confirmatory_test_methods: confirmatoryMethods,
                        confirmatory_specifications: confirmatorySpecs,
                        confirmatory_remark: confirmatoryRemark,
                        disposition_of_exhibit: document.getElementById('confirmatoryDispositionOfExhibit')
                            ?.value || '',
                        lab_date_received: dateReceived,
                        lab_date_started: dateStarted,
                        lab_date_completed: dateCompleted,
                        date_received: dateReceived,
                        date_started: dateStarted,
                        date_completed: dateCompleted
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    const compCount = data.completed_count || 0;

                    // Update UI elements in Section 5
                    const uUnitEl = document.getElementById('section5LabUnitDisplay');
                    if (uUnitEl) uUnitEl.textContent = labUnit;
                    const uPurposeEl = document.getElementById('displayLabPurposeOfExamination');
                    if (uPurposeEl) uPurposeEl.textContent = document.getElementById('labPurposeOfExaminationInput')?.value || 'N/A';
                    const uAnalystEl = document.getElementById('section5AnalystDisplay');
                    if (uAnalystEl) uAnalystEl.textContent = analyst;
                    const uDateRecEl = document.getElementById('section5DateReceivedDisplay');
                    if (uDateRecEl) uDateRecEl.textContent = dateReceived;
                    const uDateStartEl = document.getElementById('section5DateStartedDisplay');
                    if (uDateStartEl) uDateStartEl.textContent = dateStarted;
                    const uDateCompEl = document.getElementById('section5DateCompletedDisplay');
                    if (uDateCompEl) uDateCompEl.textContent = dateCompleted;
                    const uPresumptiveEl = document.getElementById('section5PresumptiveDisplay');
                    if (uPresumptiveEl) {
                        const str = String(presumptive || '').trim();
                        let overallResult = 'N/A';
                        if (str && str !== 'N/A') {
                            const up = str.toUpperCase();
                            if (up.startsWith('YES') || up.startsWith('POS')) overallResult = 'YES';
                            else if (up.startsWith('NO') || up.startsWith('NEG')) overallResult = 'NO';
                        }

                        let cleanP = str;
                        const mP = str.match(/^[A-Z]+\s*\((.*)\)$/i);
                        if (mP) cleanP = mP[1];

                        const segments = cleanP.split(/;\s*(?=\[\d+\]|Presence of:)/i);
                        const pRowsJS = [];

                        for (let seg of segments) {
                            seg = seg.trim();
                            if (!seg) continue;

                            let presence = 'N/A';
                            let purity = 'N/A';

                            const mPres = seg.match(/Presence of:\s*([^\-\;\)]+)/i) || seg.match(
                                /^\[\d+\]\s*([^\-\;\)]+)/i);
                            if (mPres) {
                                presence = mPres[1].trim();
                            } else if (seg && !seg.includes('Purity')) {
                                presence = seg.trim();
                            }

                            if (presence === 'YES' || presence === 'NO' || presence === 'POS' || presence === 'NEG') {
                                presence = 'N/A';
                            }

                            const mPur = seg.match(/Purity\/Percentage:\s*([^\;\)]+)/i);
                            if (mPur) {
                                purity = mPur[1].trim();
                            }

                            if (presence !== 'N/A' || purity !== 'N/A' || segments.length === 1) {
                                pRowsJS.push({
                                    result: overallResult,
                                    presence: presence,
                                    purity: purity
                                });
                            }
                        }

                        if (pRowsJS.length === 0) {
                            pRowsJS.push({
                                result: 'N/A',
                                presence: 'N/A',
                                purity: 'N/A'
                            });
                        }

                        let rowsHtml = '';
                        for (let r of pRowsJS) {
                            rowsHtml += `
                            <tr class="border-b border-slate-200 last:border-b-0">
                                <td class="px-2 py-1 border-r border-slate-300 font-extrabold ${r.result === 'YES' ? 'text-emerald-800' : 'text-slate-900'}">${escapeHtml(r.result)}</td>
                                <td class="px-2 py-1 border-r border-slate-300 font-bold text-slate-900">${escapeHtml(r.presence)}</td>
                                <td class="px-2 py-1 font-bold text-slate-900">${escapeHtml(r.purity)}</td>
                            </tr>
                        `;
                        }

                        uPresumptiveEl.innerHTML = `
                        <table class="w-full text-xs border border-slate-300 rounded overflow-hidden">
                            <thead class="bg-slate-100 text-slate-950 font-mono-code uppercase font-black text-[10px]">
                                <tr class="border-b border-slate-300">
                                    <th class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">Result</th>
                                    <th class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">Presence of</th>
                                    <th class="px-2 py-1 text-left bg-slate-200/70">Purity/Percentage</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                ${rowsHtml}
                            </tbody>
                        </table>
                    `;
                    }
                    const uConfirmatoryEl = document.getElementById('section5ConfirmatoryDisplay');
                    if (uConfirmatoryEl) uConfirmatoryEl.innerHTML =
                        `<span>${confirmatory === 'YES' ? 'YES' : 'NO'}</span>`;
                    const uDescEl = document.getElementById('section5SampleDescDisplay');
                    if (uDescEl) uDescEl.textContent = document.getElementById('sampleWeightDescriptionReview')
                        ?.value || 'N/A';
                    const uGrossEl = document.getElementById('section5SampleGrossDisplay');
                    if (uGrossEl) uGrossEl.textContent = document.getElementById('sampleWeightGrossReview')?.value ||
                        'N/A';
                    const uTakenEl = document.getElementById('section5SampleTakenDisplay');
                    if (uTakenEl) uTakenEl.textContent = document.getElementById('sampleWeightTakenReview')?.value ||
                        'N/A';
                    const uReservedEl = document.getElementById('section5SampleReservedDisplay');
                    if (uReservedEl) uReservedEl.textContent = document.getElementById('sampleWeightReservedReview')
                        ?.value || 'N/A';
                    const uSubstanceIdEl = document.getElementById('section5SubstanceIdDisplay');
                    if (uSubstanceIdEl) uSubstanceIdEl.textContent = document.getElementById(
                        'confirmatorySubstanceIdentificationReview')?.value || 'N/A';
                    const uTestMethodsEl = document.getElementById('section5TestMethodsDisplay');
                    if (uTestMethodsEl) uTestMethodsEl.textContent = document.getElementById(
                        'confirmatoryTestMethodsReview')?.value || 'N/A';
                    const uSpecsEl = document.getElementById('section5SpecsDisplay');
                    if (uSpecsEl) uSpecsEl.textContent = document.getElementById('confirmatorySpecificationsReview')
                        ?.value || 'N/A';
                    const uRemarkEl = document.getElementById('section5RemarkDisplay');
                    if (uRemarkEl) uRemarkEl.textContent = document.getElementById('confirmatoryRemarkReview')?.value ||
                        'N/A';
                    const uDetectedEl = document.getElementById('section5DetectedSubstancesDisplay');
                    if (uDetectedEl) {
                        const arr = typeof getDetectedSubstancesDataReview === 'function' ?
                            getDetectedSubstancesDataReview() : [];
                        if (arr.length > 0) {
                            uDetectedEl.textContent = arr.map(item =>
                                `${item.substance}${item.percentage ? ' (' + item.percentage + ')' : ''}`).join(
                                ', ');
                        } else {
                            uDetectedEl.textContent = 'No individual compound breakdown logged';
                        }
                    }
                    const uFindingsEl = document.getElementById('section5FindingsDisplay');
                    if (uFindingsEl) uFindingsEl.textContent = notes ||
                        'No specific laboratory analytical findings logged yet.';

                    const uRecEl = document.getElementById('section5DateReceivedDisplay');
                    if (uRecEl) uRecEl.textContent = dateReceived;
                    const uStartEl = document.getElementById('section5DateStartedDisplay');
                    if (uStartEl) uStartEl.textContent = dateStarted;
                    const uCompEl = document.getElementById('section5DateCompletedDisplay');
                    if (uCompEl) uCompEl.textContent = dateCompleted;

                    const badgeStatus = document.getElementById('section5BadgeStatus');
                    if (badgeStatus) {
                        if (status === 'COMPLETED' || window.activeSuspectHasSavedLabResult) {
                            badgeStatus.className =
                                'px-2 py-0.5 rounded text-[9px] font-mono-code font-bold uppercase bg-emerald-100 text-emerald-800 border border-emerald-300';
                            badgeStatus.textContent = '✓ LAB COMPLETED';
                        } else if (status === 'IN_PROGRESS') {
                            badgeStatus.className =
                                'px-2 py-0.5 rounded text-[9px] font-mono-code font-bold uppercase bg-amber-100 text-amber-800 border border-amber-300';
                            badgeStatus.textContent = '⏳ LAB IN PROGRESS';
                        } else {
                            badgeStatus.className =
                                'px-2 py-0.5 rounded text-[9px] font-mono-code font-bold uppercase bg-slate-100 text-slate-800 border border-slate-300';
                            badgeStatus.textContent = '⭕ LAB PENDING';
                        }
                    }

                    // Update 4-Unit card
                    const slug = 'labanalysis';
                    const cardEl = document.getElementById(`unitCard-${slug}`);
                    const badgeEl = document.getElementById(`unitBadge-${slug}`);
                    const officerBoxEl = document.getElementById(`unitOfficerBox-${slug}`);
                    const notesBoxEl = document.getElementById(`unitNotesBox-${slug}`);

                    if (badgeEl) {
                        if (status === 'COMPLETED') {
                            badgeEl.className =
                                'text-[9px] font-extrabold font-mono-code uppercase px-2 py-0.5 rounded-md border bg-emerald-100 text-emerald-800 border-emerald-300';
                            badgeEl.textContent = '✓ COMPLETED';
                        } else if (status === 'IN_PROGRESS') {
                            badgeEl.className =
                                'text-[9px] font-extrabold font-mono-code uppercase px-2 py-0.5 rounded-md border bg-amber-100 text-amber-800 border-amber-300';
                            badgeEl.textContent = '⏳ IN PROGRESS';
                        }
                    }
                    if (officerBoxEl) officerBoxEl.innerHTML = '';
                    if (notesBoxEl) notesBoxEl.innerHTML =
                        `<div class="text-[11px] text-slate-700 mt-2 p-2.5 bg-white/90 rounded-lg border border-slate-200/80 leading-relaxed"><span class="font-bold text-[10px] text-slate-400 block font-mono-code uppercase mb-0.5">Unit Findings:</span>"${escapeHtml(notes)}"</div>`;

                    const badgeCounter = document.getElementById('unitCompletedCountBadge');
                    if (badgeCounter) badgeCounter.textContent = `${compCount}/4 Units Completed`;

                    const pct = Math.round((compCount / 4) * 100);
                    const progressBar = document.getElementById('unitProgressBar');
                    if (progressBar) {
                        progressBar.style.width = `${Math.max(18, pct)}%`;
                        progressBar.textContent = `${pct}%`;
                    }

                    const percentText = document.getElementById('unitProgressPercentText');
                    if (percentText) {
                        percentText.textContent = `${pct}%`;
                    }

                    if (selectedOpt) {
                        selectedOpt.setAttribute('data-has-saved', 'true');
                        selectedOpt.setAttribute('data-remark', document.getElementById('confirmatoryRemarkReview')
                            ?.value || '');
                    }

                    const readOnlyView = document.getElementById('section5ReadOnlyDisplay');
                    const formContainer = document.getElementById('section5FormContainer');
                    const form = document.getElementById('section5LabForm');
                    if (readOnlyView) readOnlyView.classList.add('hidden');
                    if (formContainer) formContainer.classList.add('hidden');
                    if (form) form.classList.add('hidden');

                    resetSection5Form();
                    showSaveSuccessPopover();
                    showToast(
                        `✔ Laboratory Examination saved for ${exhibitOwnerName || 'Suspect'}!`
                    );
                    setTimeout(() => {
                        window.location.reload();
                    }, 800);
                } else {
                    if (data.unexamined_suspects && data.unexamined_suspects.length > 0) {
                        showUnexaminedSuspectsAlert(data.unexamined_suspects, data.message);
                    } else {
                        showToast(`❌ Error saving lab examination: ${data.message || 'Server error'}`);
                    }
                }
            } catch (err) {
                showToast(`❌ Connection error saving lab examination.`);
            } finally {
                if (saveBtn && !document.getElementById('section5LabForm')?.disabled) {
                    saveBtn.disabled = false;
                }
            }
        }

        /* ── Combined Save: Seizure Item & Lab Examination Information / GSA Details ── */
        async function submitCombinedLabAndSeizureForm(e) {
            if (e && e.preventDefault) e.preventDefault();

            const isGsaMode = (window.activeLabWorkflowMode === 'gsa') ||
                Boolean(document.getElementById('gsaUploadSection') && !document.getElementById('gsaUploadSection').classList.contains('hidden'));

            const saveBtn4 = document.getElementById('saveSection4SeizureBtn');
            const saveBtnText4 = document.getElementById('saveSection4SeizureBtnText');
            const saveBtn5 = document.getElementById('saveSection5LabBtn');
            const saveBtnText5 = document.getElementById('saveSection5LabBtnText');
            const saveBtnGsa = document.getElementById('saveGsaDetailsBtn');
            const saveBtnTextGsa = document.getElementById('saveGsaDetailsBtnText');

            if (saveBtn4) saveBtn4.disabled = true;
            if (saveBtn5) saveBtn5.disabled = true;
            if (saveBtnGsa) saveBtnGsa.disabled = true;

            const savingLabel = isGsaMode ? 'Saving Seizure Item & GSA Details...' : 'Saving Seizure & Examination...';
            if (saveBtnText4) saveBtnText4.innerText = savingLabel;
            if (saveBtnText5) saveBtnText5.innerText = savingLabel;
            if (saveBtnTextGsa) saveBtnTextGsa.innerText = 'Saving Seizure Item & GSA Details...';

            const defaultLabel = typeof getLabReviewSaveButtonLabel === 'function' ? getLabReviewSaveButtonLabel() : (isGsaMode ? 'Save Seizure Item & GSA Details' : 'Save Seizure Item & Examination Information');

            const caseId = @json($case->id);
            const caseNumber = @json($case->case_number);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            try {
                // 1. Save Section 4 Seizure Items
                const s4Checkbox = document.getElementById('toggleSeizureItemSectionCheckbox');
                const s4Container = document.getElementById('section4Seizures');
                const isS4Active = s4Checkbox ? s4Checkbox.checked : (s4Container && !s4Container.classList.contains('hidden'));

                const blocks = document.querySelectorAll('#seizuresListContainer .seizure-block');
                const exhibits = [];

                if (isS4Active) {
                    blocks.forEach((block) => {
                    const assocPerson = block.querySelector('.seizure-associated-person-select')?.value ||
                        @json($activeSuspectName);
                    const category = block.querySelector('#newSeizureCategory')?.value || block.querySelector(
                        '#exhibitSearchInput')?.value || '';

                    let seizureName = block.querySelector('#newSeizureNameDrug')?.value ||
                        block.querySelector('#drugSearchInput')?.value ||
                        block.querySelector('#newSeizureNameWeapon')?.value ||
                        block.querySelector('#weaponSearchInput')?.value ||
                        block.querySelector('#newSeizureNameCurrency')?.value ||
                        block.querySelector('#currencySearchInput')?.value ||
                        block.querySelector('#newSeizureNameAmmo')?.value ||
                        block.querySelector('#ammoSearchInput')?.value ||
                        block.querySelector('#newSeizureNameDevice')?.value ||
                        block.querySelector('#deviceSearchInput')?.value ||
                        block.querySelector('#newSeizureName')?.value || '';

                    if (!seizureName.trim()) seizureName = category;

                    const quantity = block.querySelector('#newSeizureQuantity')?.value || '';
                    const weight = block.querySelector('#newSeizureWeight')?.value || '';
                    const unit = block.querySelector('#newSeizureUnit')?.value || block.querySelector(
                        '#unitSearchInput')?.value || '';
                    const packaging = block.querySelector('#newSeizurePackaging')?.value || block.querySelector(
                        '#pkgSearchInput')?.value || '';
                    const value = block.querySelector('#newSeizureValue')?.value || '';
                    const concealment = block.querySelector('#newSeizureConcealment')?.value || '';
                    const dateSeized = block.querySelector('#newSeizureDate')?.value || '';
                    const timeSeized = block.querySelector('#newSeizureTime')?.value || '';
                    const location = block.querySelector('#newSeizureLocation')?.value || '';
                    const sourceOfExhibits = block.querySelector('#newSeizureSourceOfExhibits')?.value || '';
                    const seizingOfficer = typeof getSeizureOfficersFormatted === 'function' ?
                        getSeizureOfficersFormatted(block) : '';
                    const investigatingOfficer = block.querySelector('#newSeizureInvestigatingOfficer')?.value || block.querySelector('.seizure-investigating-officer-input')?.value || block.querySelector('[name="investigating_officer"]')?.value || '';
                    const sourceRequestingUnit = block.querySelector('#newSeizureSourceRequestingUnit')?.value || block.querySelector('.seizure-source-requesting-unit-input')?.value || block.querySelector('[name="source_requesting_unit"]')?.value || '';

                    const devModel = block.querySelector('#newDevModel')?.value || '';
                    const devSerialNo = block.querySelector('#newDevSerialNo')?.value || '';
                    const devImei1 = block.querySelector('#newDevImei1')?.value || '';
                    const devImei2 = block.querySelector('#newDevImei2')?.value || '';
                    const devMacAddress = block.querySelector('#newDevMacAddress')?.value || '';
                    const devStorage = block.querySelector('#newDevStorage')?.value || '';
                    const devColour = block.querySelector('#newDevColour')?.value || '';
                    const devMarks = block.querySelector('#newDevMarks')?.value || '';

                    const wpnModel = block.querySelector('#wpnModel')?.value || '';
                    const wpnSerialNo = block.querySelector('#wpnSerialNo')?.value || '';
                    const wpnCalibre = block.querySelector('#wpnCalibre')?.value || '';
                    const wpnColour = block.querySelector('#wpnColour')?.value || '';
                    const wpnSize = block.querySelector('#wpnSize')?.value || '';
                    const wpnMarks = block.querySelector('#wpnMarks')?.value || '';
                    const wpnLocationFound = block.querySelector('#wpnLocationFound')?.value || '';
                    const wpnLoadedStatus = block.querySelector('#wpnLoadedStatus')?.value || '';
                    const wpnCondition = block.querySelector('#wpnCondition')?.value || '';

                    const vehMake = block.querySelector('#vehMake')?.value || '';
                    const vehModel = block.querySelector('#vehModel')?.value || '';
                    const vehType = block.querySelector('#vehType')?.value || block.querySelector('#vehTypeSearchInput')?.value || '';
                    const vehYear = block.querySelector('#vehYear')?.value || '';
                    const vehColour = block.querySelector('#vehColour')?.value || '';
                    const vehRegNo = block.querySelector('#vehRegNo')?.value || '';
                    const vehVIN = block.querySelector('#vehVIN')?.value || '';
                    const vehEngineNo = block.querySelector('#vehEngineNo')?.value || '';
                    const vehCountryReg = block.querySelector('#vehCountryReg')?.value || '';
                    const vehOwner = block.querySelector('#vehOwner')?.value || '';
                    const vehDriver = block.querySelector('#vehDriver')?.value || '';
                    const vehOwnerContact = block.querySelector('#vehOwnerContact')?.value || '';
                    const vehDocsAvailable = block.querySelector('#vehDocsAvailable')?.value || '';
                    const vehDocRef = block.querySelector('#vehDocRef')?.value || '';
                    const vehCondition = block.querySelector('#vehCondition')?.value || '';
                    const vehFuel = block.querySelector('#vehFuel')?.value || '';
                    const vehKeysRecv = block.querySelector('#vehKeysRecv')?.value || '';
                    const vehKeyCount = block.querySelector('#vehKeyCount')?.value || '';
                    const vehDamage = block.querySelector('#vehDamage')?.value || '';
                    const vehMods = block.querySelector('#vehMods')?.value || '';
                    const vehFeatures = block.querySelector('#vehFeatures')?.value || '';

                    const ammoCartridgeAppearance = block.querySelector('#ammoCartridgeAppearance')?.value || '';
                    const ammoProjectileType = block.querySelector('#ammoProjectileType')?.value || '';
                    const ammoCaseMaterial = block.querySelector('#ammoCaseMaterial')?.value || '';
                    const ammoColourMarkings = block.querySelector('#ammoColourMarkings')?.value || '';
                    const ammoConditionVal = block.querySelector('#ammoCondition')?.value || '';
                    const ammoOtherConditionVal = block.querySelector('#ammoOtherCondition')?.value || '';
                    const ammoPackagingTypeVal = block.querySelector('#ammoPackagingType')?.value || '';
                    const ammoSealConditionVal = block.querySelector('#ammoSealCondition')?.value || '';
                    const ammoPackageReferenceNumberVal = block.querySelector('#ammoPackageReferenceNumber')?.value || '';
                    const ammoConditionWhenReceivedVal = block.querySelector('#ammoConditionWhenReceived')?.value || '';
                    const otherExhibitDescription = block.querySelector('#newOtherExhibitDescription')?.value || block.querySelector('.other-exhibit-description-input')?.value || '';
                    const exhibitRemark = block.querySelector('#newSeizureRemark')?.value?.trim() || block.querySelector('.seizure-remark-input')?.value?.trim() || block.querySelector('[name="exhibit_remark"]')?.value?.trim() || '';

                    const isAmmoCat = category === 'Ammunition' || (typeof category === 'string' && (category.toLowerCase().includes('ammo') || category.toLowerCase().includes('ammunition')));
                    const isOtherCat = category === 'Other Exhibits' || category === 'Other' || category === 'Other Exhibit' || (typeof category === 'string' && category.toLowerCase().includes('other'));
                    const ammoDetailsObj = isAmmoCat ? {
                        cartridge_appearance: ammoCartridgeAppearance,
                        projectile_bullet_type: ammoProjectileType,
                        cartridge_case_material: ammoCaseMaterial,
                        colour_markings: ammoColourMarkings,
                        condition: ammoConditionVal,
                        other_condition: ammoOtherConditionVal,
                        packaging_type: ammoPackagingTypeVal,
                        seal_tamper_evident_condition: ammoSealConditionVal,
                        package_reference_number: ammoPackageReferenceNumberVal,
                        condition_when_received: ammoConditionWhenReceivedVal
                    } : null;

                    if ((category === 'Vehicles' || category === 'Vehicle' || (typeof category === 'string' && category.toLowerCase().includes('vehic'))) && !seizureName.trim()) {
                        seizureName = [vehMake, vehModel, vehRegNo ? `(${vehRegNo})` : ''].filter(Boolean).join(' ') || 'Vehicle Exhibit';
                    } else if (isAmmoCat && (!seizureName.trim() || seizureName === category)) {
                        seizureName = [ammoProjectileType, ammoCartridgeAppearance].filter(Boolean).join(' - ') || 'Ammunition Exhibit';
                    } else if (isOtherCat && (!seizureName.trim() || seizureName === category)) {
                        seizureName = otherExhibitDescription ? (otherExhibitDescription.length > 50 ? otherExhibitDescription.substring(0, 50) + '...' : otherExhibitDescription) : 'Other Exhibit';
                    }

                    if (seizureName.trim()) {
                        const photoInputs = block.querySelectorAll('.seizure-photo-url-input');
                        const photoItems = block.querySelectorAll('.seizure-photo-item[data-photo-url]');
                        const photoUrlsList = Array.from(photoInputs).map(inp => inp.value).filter(Boolean);
                        photoItems.forEach(item => {
                            const u = item.getAttribute('data-photo-url');
                            if (u && !photoUrlsList.includes(u)) photoUrlsList.push(u);
                        });

                        exhibits.push({
                            associated_person: assocPerson,
                            suspect_name: assocPerson,
                            category: category,
                            exhibit_category: category,
                            photos: photoUrlsList,
                            evidence_photos: photoUrlsList,
                            seizure_name: seizureName,
                            commodity_name: seizureName,
                            name: seizureName,
                            seizure_quantity: quantity,
                            quantity: quantity,
                            seizure_weight: weight,
                            weight: weight,
                            seizure_unit: unit,
                            unit: unit,
                            seizure_packaging: packaging,
                            packaging: packaging,
                            seizure_value: value,
                            value: value,
                            seizure_concealment: concealment,
                            seizure_date: dateSeized,
                            seizure_time: timeSeized,
                            seizure_location: location,
                            source_of_exhibits: sourceOfExhibits,
                            exhibit_remark: exhibitRemark,
                            seizure_officer: seizingOfficer,
                            investigating_officer: investigatingOfficer,
                            source_requesting_unit: sourceRequestingUnit,
                            dev_model: devModel,
                            dev_serial_no: devSerialNo,
                            dev_imei1: devImei1,
                            dev_imei2: devImei2,
                            dev_mac_address: devMacAddress,
                            dev_storage: devStorage,
                            dev_colour: devColour,
                            dev_marks: devMarks,
                            wpn_model: wpnModel,
                            wpn_serial_no: wpnSerialNo,
                            wpn_calibre: wpnCalibre,
                            wpn_colour: wpnColour,
                            wpn_size: wpnSize,
                            wpn_marks: wpnMarks,
                            wpn_location_found: wpnLocationFound,
                            wpn_loaded_status: wpnLoadedStatus,
                            wpn_condition: wpnCondition,
                            weapon_model: wpnModel,
                            weapon_serial: wpnSerialNo,
                            weapon_calibre: wpnCalibre,
                            weapon_colour: wpnColour,
                            weapon_size: wpnSize,
                            veh_make: vehMake,
                            veh_model: vehModel,
                            veh_type: vehType,
                            veh_year: vehYear,
                            veh_colour: vehColour,
                            veh_reg_no: vehRegNo,
                            veh_vin: vehVIN,
                            veh_engine_no: vehEngineNo,
                            veh_country_reg: vehCountryReg,
                            veh_owner: vehOwner,
                            veh_driver: vehDriver,
                            veh_owner_contact: vehOwnerContact,
                            veh_docs_available: vehDocsAvailable,
                            veh_doc_ref: vehDocRef,
                            veh_condition: vehCondition,
                            veh_fuel: vehFuel,
                            veh_keys_recv: vehKeysRecv,
                            veh_key_count: vehKeyCount,
                            veh_damage: vehDamage,
                            veh_mods: vehMods,
                            veh_features: vehFeatures,
                            vehicle_make_model: vehMake ? (vehModel ? (vehMake + ' ' + vehModel) : vehMake) : vehModel,
                            vehicle_registration: vehRegNo,
                            vehicle_chassis_vin: vehVIN,
                            vehicle_color_type: vehColour,
                            vehicle_type: vehType,
                            vehicle_year: vehYear,
                            engine_number: vehEngineNo,
                            country_of_registration: vehCountryReg,
                            vehicle_owner: vehOwner,
                            vehicle_driver: vehDriver,
                            ammo_cartridge_appearance: ammoCartridgeAppearance,
                            ammo_projectile_type: ammoProjectileType,
                            ammo_case_material: ammoCaseMaterial,
                            ammo_colour_markings: ammoColourMarkings,
                            ammo_condition: ammoConditionVal,
                            ammo_other_condition: ammoOtherConditionVal,
                            ammo_packaging_type: ammoPackagingTypeVal,
                            ammo_seal_condition: ammoSealConditionVal,
                            ammo_package_reference_number: ammoPackageReferenceNumberVal,
                            ammo_condition_when_received: ammoConditionWhenReceivedVal,
                            ammunition_details: ammoDetailsObj,
                            ammo: ammoDetailsObj,
                            other_exhibit_description: otherExhibitDescription,
                            other_description: otherExhibitDescription,
                            veh: {
                                make: vehMake,
                                model: vehModel,
                                type: vehType,
                                year: vehYear,
                                colour: vehColour,
                                reg_no: vehRegNo,
                                vin: vehVIN,
                                engine_no: vehEngineNo,
                                country_reg: vehCountryReg,
                                owner: vehOwner,
                                driver: vehDriver,
                                owner_contact: vehOwnerContact,
                                docs_available: vehDocsAvailable,
                                doc_ref: vehDocRef,
                                condition: vehCondition,
                                fuel: vehFuel,
                                keys_recv: vehKeysRecv,
                                key_count: vehKeyCount,
                                damage: vehDamage,
                                mods: vehMods,
                                features: vehFeatures
                            },
                            wpn: {
                                model: wpnModel,
                                serial_no: wpnSerialNo,
                                calibre: wpnCalibre,
                                colour: wpnColour,
                                size: wpnSize,
                                marks: wpnMarks,
                                location_found: wpnLocationFound,
                                loaded_status: wpnLoadedStatus,
                                condition: wpnCondition
                            },
                            contents: block.querySelector('#newSeizureContents')?.value || block.querySelector('.seizure-contents-input')?.value || '',
                            description_of_exhibits: document.getElementById('manifestDescriptionOfExhibits')?.value || ''
                        });
                    }
                });
                }

                const hasSection4Data = isS4Active && exhibits.length > 0;

                // ── GSA WORKFLOW BRANCH: Save Seizure Items & GSA Report / Details together ──
                if (isGsaMode) {
                    const fileKeys = Object.keys(gsaFilesMap || {});
                    const fileInput = document.getElementById('gsaReportImageInput');
                    const hasInputFiles = Boolean(fileInput && fileInput.files && fileInput.files.length > 0);
                    const hasGsaFiles = fileKeys.length > 0 || hasInputFiles;

                    const gsaLabNoVal = document.getElementById('gsaLabNumberInput')?.value?.trim() || '';
                    const gsaCaseRefVal = document.getElementById('gsaCaseReferenceInput')?.value?.trim() || '';
                    const gsaStatusVal = document.getElementById('gsaStatusInput')?.value?.trim() || '';
                    const exhibitInputs = document.querySelectorAll('#gsaExhibitTypeList .gsa-exhibit-type-input');
                    const hasExhibitTypes = Array.from(exhibitInputs).some(inp => inp.value?.trim());
                    const hasGsaDetails = Boolean(gsaLabNoVal || gsaCaseRefVal || gsaStatusVal || hasExhibitTypes);

                    if (!hasSection4Data && !hasGsaFiles && !hasGsaDetails) {
                        alert('Please enter Seizure Item details or select at least one GSA report file / enter GSA details before saving.');
                        if (saveBtn4) saveBtn4.disabled = false;
                        if (saveBtn5) saveBtn5.disabled = false;
                        if (saveBtnGsa) saveBtnGsa.disabled = false;
                        if (saveBtnText4) saveBtnText4.innerText = defaultLabel;
                        if (saveBtnText5) saveBtnText5.innerText = defaultLabel;
                        if (saveBtnTextGsa) saveBtnTextGsa.innerText = 'Save Seizure Item & GSA Details';
                        return;
                    }

                    if (hasGsaDetails && !hasGsaFiles && !hasSection4Data) {
                        alert('Please select or drop at least one GSA report file before saving GSA Details.');
                        if (saveBtn4) saveBtn4.disabled = false;
                        if (saveBtn5) saveBtn5.disabled = false;
                        if (saveBtnGsa) saveBtnGsa.disabled = false;
                        if (saveBtnText4) saveBtnText4.innerText = defaultLabel;
                        if (saveBtnText5) saveBtnText5.innerText = defaultLabel;
                        if (saveBtnTextGsa) saveBtnTextGsa.innerText = 'Save Seizure Item & GSA Details';
                        return;
                    }

                    let section4Saved = false;
                    let gsaSaved = false;

                    // 1. Save Section 4 Seizure Items if present
                    if (hasSection4Data) {
                        const res4 = await fetch(`/cases/${encodeURIComponent(caseId)}/associate`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({
                                associated_exhibits: exhibits,
                                description_of_exhibits: document.getElementById('manifestDescriptionOfExhibits')?.value || ''
                            })
                        });
                        const data4 = await res4.json().catch(() => ({}));
                        if (!res4.ok || !data4.success) {
                            alert(data4.message || 'Failed to save Seizure Items.');
                            if (saveBtn4) saveBtn4.disabled = false;
                            if (saveBtn5) saveBtn5.disabled = false;
                            if (saveBtnText4) saveBtnText4.innerText = defaultLabel;
                            if (saveBtnText5) saveBtnText5.innerText = defaultLabel;
                            return;
                        }
                        section4Saved = true;
                    }

                    // 2. Save GSA Report & Details if files present
                    if (hasGsaFiles) {
                        const formData = new FormData();
                        const isMultipleCase = {{ ($case->underlyingRecord instanceof \App\Models\MultipleSuspectCase) || !empty($case->is_multiple) ? 'true' : 'false' }};

                        const gsaSuspectSelect = document.getElementById('gsaSuspectSelect');
                        let targetSuspectName = '{{ $activeSuspectName ?? ($case->suspect_name ?? '') }}';
                        let targetSuspectId = '{{ $activeSuspectItem['id'] ?? '' }}';

                        if (gsaSuspectSelect && gsaSuspectSelect.options && gsaSuspectSelect.selectedIndex >= 0) {
                            const opt = gsaSuspectSelect.options[gsaSuspectSelect.selectedIndex];
                            targetSuspectName = opt.value;
                            targetSuspectId = opt.getAttribute('data-id') || '';
                        }

                        formData.append('case_number', caseNumber);
                        if (caseId) {
                            if (isMultipleCase) {
                                formData.append('multiple_suspect_case_id', caseId);
                            } else {
                                formData.append('single_suspect_case_id', caseId);
                            }
                            formData.append('case_id', caseId);
                        }
                        if (targetSuspectName && targetSuspectName !== 'All Suspects') {
                            formData.append('suspect_name', targetSuspectName);
                        }
                        if (targetSuspectId) {
                            formData.append('case_suspect_id', targetSuspectId);
                        }
                        if (gsaLabNoVal) {
                            formData.append('gsa_lab_number', gsaLabNoVal);
                            formData.append('lab_number', gsaLabNoVal);
                        }
                        if (gsaCaseRefVal) {
                            formData.append('gsa_case_reference', gsaCaseRefVal);
                        }
                        if (gsaStatusVal) {
                            formData.append('gsa_status', gsaStatusVal);
                        }

                        exhibitInputs.forEach(input => {
                            const val = input.value?.trim();
                            if (val) {
                                formData.append('exhibit_type[]', val);
                            }
                        });

                        formData.append('_token', csrfToken);

                        if (fileKeys.length > 0) {
                            fileKeys.forEach(k => {
                                if (gsaFilesMap[k] && gsaFilesMap[k].file) {
                                    formData.append('gsa_report_images[]', gsaFilesMap[k].file);
                                }
                            });
                        } else if (hasInputFiles) {
                            Array.from(fileInput.files).forEach(f => {
                                formData.append('gsa_report_images[]', f);
                            });
                        }

                        const targetUrl = caseNumber ? `/cases/${encodeURIComponent(caseNumber)}/upload-gsa-report` : '/Lab_Analysis/gsa-reports/store';

                        const resGsa = await fetch(targetUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: formData
                        });
                        const resultGsa = await resGsa.json().catch(() => ({}));
                        if (!resGsa.ok || !resultGsa.success) {
                            alert(resultGsa.message || (section4Saved ? 'Seizure Item saved, but failed to save GSA Details. Please check file formats and try again.' : 'Failed to upload GSA Report files. Please check file formats and try again.'));
                            if (saveBtn4) saveBtn4.disabled = false;
                            if (saveBtn5) saveBtn5.disabled = false;
                            if (saveBtnText4) saveBtnText4.innerText = defaultLabel;
                            if (saveBtnText5) saveBtnText5.innerText = defaultLabel;
                            return;
                        }
                        gsaSaved = true;
                        if (typeof clearAllGsaFiles === 'function') clearAllGsaFiles();

                        // Automatically update Unit Directorate Progress for Lab Analysis to COMPLETED
                        if (caseNumber) {
                            try {
                                await fetch(`/cases/${encodeURIComponent(caseNumber)}/update-unit-progress`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken
                                    },
                                    body: JSON.stringify({
                                        unit_name: 'Lab Analysis',
                                        unit_status: 'COMPLETED',
                                        notes: 'GSA Report uploaded and attached'
                                    })
                                });
                            } catch (e) {
                                console.warn('GSA unit progress sync warning:', e);
                            }
                        }
                    }

                    if (section4Saved && gsaSaved) {
                        if (typeof showSaveSuccessPopover === 'function') showSaveSuccessPopover();
                        if (typeof showToast === 'function') showToast('✔ Seizure Item & GSA Details saved successfully!');
                    } else if (gsaSaved) {
                        if (typeof showSaveSuccessPopover === 'function') showSaveSuccessPopover();
                        if (typeof showToast === 'function') showToast('✔ GSA Details saved successfully!');
                    } else if (section4Saved) {
                        if (typeof showToast === 'function') showToast('✔ Seizure Item saved successfully!');
                    }

                    setTimeout(() => {
                        window.location.reload();
                    }, 800);
                    return;
                }

                // ── NLR WORKFLOW BRANCH ──
                // Check Section 5 validity
                const section5Container = document.getElementById('section5FormContainer');
                const section5IsVisible = section5Container && !section5Container.classList.contains('hidden');
                const exhibitSelect = document.getElementById('labExhibitSelect');
                const selectedOpt = exhibitSelect ? exhibitSelect.options[exhibitSelect.selectedIndex] : null;
                const exhibitId = document.getElementById('labFormExhibitId')?.value || (selectedOpt ? (selectedOpt.getAttribute('data-ex-id') || selectedOpt.value) : '');

                const hasSection5Data = section5IsVisible && Boolean(exhibitId && exhibitId !== '');

                if (!hasSection4Data && !hasSection5Data) {
                    alert('Please enter Seizure Item details or select a valid exhibit in Examination Information before saving.');
                    if (saveBtn4) saveBtn4.disabled = false;
                    if (saveBtn5) saveBtn5.disabled = false;
                    if (saveBtnText4) saveBtnText4.innerText = defaultLabel;
                    if (saveBtnText5) saveBtnText5.innerText = defaultLabel;
                    return;
                }

                let section4Saved = false;
                if (hasSection4Data) {
                    const res4 = await fetch(`/cases/${encodeURIComponent(caseId)}/associate`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            associated_exhibits: exhibits,
                            description_of_exhibits: document.getElementById('manifestDescriptionOfExhibits')?.value || ''
                        })
                    });
                    const data4 = await res4.json();
                    if (res4.ok && data4.success) {
                        section4Saved = true;
                    }
                }

                // 2. Save Section 5 Laboratory Examination
                if (hasSection5Data) {
                    const todayStr = new Date().toISOString().split('T')[0];
                    const dateCompletedInput = document.getElementById('labDateCompletedInput');
                    if (dateCompletedInput && !dateCompletedInput.value) {
                        dateCompletedInput.value = todayStr;
                    }

                    const dateReceived = document.getElementById('labDateReceivedInput')?.value || todayStr;
                    const dateStarted = document.getElementById('labDateStartedInput')?.value || todayStr;
                    const dateCompleted = dateCompletedInput?.value || todayStr;

                    const labUnit = document.getElementById('formLabUnit')?.value || 'Chemistry & Narcotics Unit';
                    const analyst = document.getElementById('formLabAnalyst')?.value || @json($labAnalyst ?? $defaultLabHeadName);

                    // Active exhibit index resolution
                    const activeIdx = window.activeLabExhibitIndex || (selectedOpt ? (selectedOpt.getAttribute('data-index') || selectedOpt.value || '1') : '1');

                    // ── Presumptive Reaction validation (supports per-exhibit dynamic card, review radio, & legacy inputs) ──
                    let presumptiveChoice = null;
                    const checkedRadio = document.querySelector('input[name="form_presumptive_radio"]:checked');
                    if (checkedRadio) {
                        presumptiveChoice = checkedRadio.value;
                    } else {
                        const dynYes = document.getElementById(`presumptiveTestYes_${activeIdx}`);
                        const dynNo = document.getElementById(`presumptiveTestNo_${activeIdx}`);
                        if (dynYes && dynYes.checked) {
                            presumptiveChoice = 'YES';
                        } else if (dynNo && dynNo.checked) {
                            presumptiveChoice = 'NO';
                        } else {
                            const legYes = document.getElementById('presumptiveTestYes');
                            const legNo = document.getElementById('presumptiveTestNo');
                            if (legYes && legYes.checked) {
                                presumptiveChoice = 'YES';
                            } else if (legNo && legNo.checked) {
                                presumptiveChoice = 'NO';
                            } else {
                                const customVal = (document.getElementById(`presumptiveReactionResultInput_${activeIdx}`)?.value ||
                                                   document.getElementById('presumptiveReactionResultInput')?.value || '').trim();
                                if (customVal.toUpperCase().startsWith('YES')) {
                                    presumptiveChoice = 'YES';
                                } else if (customVal.toUpperCase().startsWith('NO')) {
                                    presumptiveChoice = 'NO';
                                }
                            }
                        }
                    }

                    if (!presumptiveChoice) {
                        alert('Please select a Presumptive Reaction result (YES or NO) before saving the examination.');
                        if (saveBtn4) saveBtn4.disabled = false;
                        if (saveBtn5) saveBtn5.disabled = false;
                        if (saveBtnText4) saveBtnText4.innerText = defaultLabel;
                        if (saveBtnText5) saveBtnText5.innerText = defaultLabel;
                        return;
                    }

                    let presumptive;
                    let presumptivePresence = null;
                    let presumptivePercentage = null;
                    if (presumptiveChoice === 'NO') {
                        presumptive = 'NO';
                    } else {
                        let rows = document.querySelectorAll(`#presumptiveTestRowsContainer_${activeIdx} .presumptive-test-row`);
                        if (!rows || rows.length === 0) {
                            rows = document.querySelectorAll('#presumptiveTestRowsContainerReview .presumptive-test-row-review');
                        }
                        if (!rows || rows.length === 0) {
                            rows = document.querySelectorAll('#presumptiveTestRowsContainer .presumptive-test-row');
                        }

                        const validDetails = [];
                        const presList = [];
                        const pctList = [];
                        let hasPartialRow = false;

                        if (rows && rows.length > 0) {
                            rows.forEach((row) => {
                                const presence = (row.querySelector('.presumptive-presence-input')?.value ||
                                                  row.querySelector('.presumptive-presence-input-review')?.value || '').trim();
                                const percent = (row.querySelector('.presumptive-percentage-input')?.value ||
                                                 row.querySelector('.presumptive-percentage-input-review')?.value || '').trim();
                                if (presence && percent) {
                                    const pctFormatted = percent.endsWith('%') ? percent : `${percent}%`;
                                    validDetails.push(`Presence of: ${presence} - Purity/Percentage: ${pctFormatted}`);
                                    presList.push(presence);
                                    pctList.push(pctFormatted);
                                } else if ((presence && !percent) || (!presence && percent)) {
                                    hasPartialRow = true;
                                }
                            });
                        }

                        if (hasPartialRow) {
                            alert('One or more presumptive test rows have only "Presence of" or only "Percentage (%)" filled. Please complete both fields or clear the row.');
                            if (saveBtn4) saveBtn4.disabled = false;
                            if (saveBtn5) saveBtn5.disabled = false;
                            if (saveBtnText4) saveBtnText4.innerText = defaultLabel;
                            if (saveBtnText5) saveBtnText5.innerText = defaultLabel;
                            return;
                        }

                        if (validDetails.length === 0) {
                            const customResultInput = document.getElementById(`presumptiveReactionResultInput_${activeIdx}`) ||
                                                      document.getElementById('presumptiveReactionResultInput');
                            const customVal = customResultInput?.value?.trim();
                            if (customVal && customVal !== '' && !customVal.startsWith('NO')) {
                                presumptive = customVal;
                            } else {
                                alert(
                                    'Please enter both "Presence of" and "Percentage (%)" for at least one presumptive test result before saving.'
                                );
                                if (saveBtn4) saveBtn4.disabled = false;
                                if (saveBtn5) saveBtn5.disabled = false;
                                if (saveBtnText4) saveBtnText4.innerText = defaultLabel;
                                if (saveBtnText5) saveBtnText5.innerText = defaultLabel;
                                return;
                            }
                        } else {
                            presumptive = validDetails.length > 1
                                ? `YES (${validDetails.map((d, i) => `[${i + 1}] ${d}`).join('; ')})`
                                : `YES (${validDetails[0]})`;
                            presumptivePresence = presList.join('; ');
                            presumptivePercentage = pctList.join('; ');
                        }
                    }

                    // ── Confirmatory Test validation (supports per-exhibit dynamic card, review radio, & legacy inputs) ──
                    let confirmatoryChoice = null;
                    const confirmatoryChecked = document.querySelector('input[name="form_confirmatory_radio"]:checked');
                    if (confirmatoryChecked) {
                        confirmatoryChoice = confirmatoryChecked.value;
                    } else {
                        const dynConfYes = document.getElementById(`confirmatoryTestYes_${activeIdx}`);
                        const dynConfNo = document.getElementById(`confirmatoryTestNo_${activeIdx}`);
                        if (dynConfYes && dynConfYes.checked) {
                            confirmatoryChoice = 'YES';
                        } else if (dynConfNo && dynConfNo.checked) {
                            confirmatoryChoice = 'NO';
                        } else {
                            const legConfYes = document.getElementById('confirmatoryTestYes');
                            const legConfNo = document.getElementById('confirmatoryTestNo');
                            if (legConfYes && legConfYes.checked) {
                                confirmatoryChoice = 'YES';
                            } else if (legConfNo && legConfNo.checked) {
                                confirmatoryChoice = 'NO';
                            } else {
                                const confResVal = (document.getElementById(`confirmatoryTestResultInput_${activeIdx}`)?.value ||
                                                    document.getElementById('confirmatoryTestResultInput')?.value || '').trim();
                                if (confResVal.toUpperCase() === 'YES') {
                                    confirmatoryChoice = 'YES';
                                } else if (confResVal.toUpperCase() === 'NO') {
                                    confirmatoryChoice = 'NO';
                                }
                            }
                        }
                    }
                    if (!confirmatoryChoice) {
                        confirmatoryChoice = 'NO';
                    }
                    const confirmatory = confirmatoryChoice === 'YES' ? 'YES' : 'NO';

                    const notes = document.getElementById('formLabNotes')?.value || '';
                    const exhibitOwnerName = selectedOpt ? selectedOpt.getAttribute('data-owner') || '' : '';
                    const exhibitIsAssociate = selectedOpt ? (selectedOpt.getAttribute('data-is-associate') === '1') :
                        false;

                    let status = 'COMPLETED';
                    if (exhibitSelect && exhibitSelect.options.length > 1) {
                        const options = Array.from(exhibitSelect.options);
                        const otherUnexamined = options.filter(opt => opt !== selectedOpt && opt.getAttribute(
                            'data-has-saved') !== '1' && opt.getAttribute('data-has-saved') !== 'true');
                        if (otherUnexamined.length > 0) {
                            status = 'IN_PROGRESS';
                        }
                    }

                    const sampleDesc = document.getElementById(`sampleWeightDescription_${activeIdx}`)?.value
                        || document.getElementById('sampleWeightDescriptionReview')?.value
                        || document.getElementById('sampleWeightDescription')?.value
                        || '';
                    const sampleUnits = document.getElementById(`sampleWeightUnits_${activeIdx}`)?.value
                        || document.getElementById('sampleWeightUnitsReview')?.value
                        || document.getElementById('sampleWeightUnits')?.value
                        || '';
                    const sampleGross = document.getElementById(`sampleWeightGross_${activeIdx}`)?.value
                        || document.getElementById('sampleWeightGrossReview')?.value
                        || document.getElementById('sampleWeightGross')?.value
                        || '';
                    const sampleTaken = document.getElementById(`sampleWeightTaken_${activeIdx}`)?.value
                        || document.getElementById('sampleWeightTakenReview')?.value
                        || document.getElementById('sampleWeightTaken')?.value
                        || '';
                    const sampleReserved = document.getElementById(`sampleWeightReserved_${activeIdx}`)?.value
                        || document.getElementById('sampleWeightReservedReview')?.value
                        || document.getElementById('sampleWeightReserved')?.value
                        || '';
                    const confirmatorySubstance = document.getElementById(`confirmatorySubstanceIdentification_${activeIdx}`)?.value
                        || document.getElementById('confirmatorySubstanceIdentificationReview')?.value
                        || document.getElementById('confirmatorySubstanceIdentification')?.value
                        || '';
                    const detectedSubstances = (typeof getDetectedSubstancesDataForExhibit === 'function' ? getDetectedSubstancesDataForExhibit(activeIdx) : null)
                        || (typeof getDetectedSubstancesDataReview === 'function' ? getDetectedSubstancesDataReview() : []);
                    const confirmatoryMethods = document.getElementById(`confirmatoryTestMethods_${activeIdx}`)?.value
                        || document.getElementById('confirmatoryTestMethodsReview')?.value
                        || document.getElementById('confirmatoryTestMethods')?.value
                        || '';
                    const confirmatorySpecs = document.getElementById(`confirmatorySpecifications_${activeIdx}`)?.value
                        || document.getElementById('confirmatorySpecificationsReview')?.value
                        || document.getElementById('confirmatorySpecifications')?.value
                        || '';
                    const confirmatoryRemark = document.getElementById(`confirmatoryRemark_${activeIdx}`)?.value
                        || document.getElementById('confirmatoryRemarkReview')?.value
                        || document.getElementById('confirmatoryRemark')?.value
                        || '';

                    const res5 = await fetch(`/cases/${caseNumber}/update-unit-progress`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            unit_name: 'Lab Analysis',
                            unit_status: status,
                            officer: analyst,
                            notes: notes,
                            exhibit_id: exhibitId,
                            drug_type: selectedOpt ? (selectedOpt.getAttribute('data-substance-id') ||
                                selectedOpt.getAttribute('data-name') || '') : '',
                            exhibit_owner_name: exhibitOwnerName,
                            exhibit_is_associate: exhibitIsAssociate,
                            lab_exam_reference: document.getElementById('labExamReferenceInput')?.value ||
                                '',
                            lab_exhibit_no: document.getElementById('labExhibitNumberInput')?.value ||
                                '',
                            lab_exhibit_number: document.getElementById('labExhibitNumberInput')?.value ||
                                '',
                            laboratory_number: document.getElementById('formLabNumber')?.value || '',
                            purpose_of_examination: document.getElementById('labPurposeOfExaminationInput')?.value || '',
                            preliminary_exam_procedure: document.getElementById('labPreliminaryExamProcedureInput')?.value || '',
                            screening_method: Array.from(document.querySelectorAll('#screeningMethodsContainer .screening-method-input')).map(el => el.value.trim()).filter(Boolean).join(', '),
                            lab_unit: labUnit,
                            presumptive_result: presumptive,
                            presumptive_presence: presumptivePresence,
                            presumptive_percentage: presumptivePercentage,
                            confirmatory_test_result: confirmatory,
                            sample_weight_description: sampleDesc,
                            sample_weight_units: sampleUnits,
                            sample_weight_gross: sampleGross,
                            sample_weight_taken: sampleTaken,
                            sample_weight_reserved: sampleReserved,
                            confirmatory_substance_identification: confirmatorySubstance,
                            detected_substances: detectedSubstances,
                            confirmatory_test_methods: confirmatoryMethods,
                            confirmatory_specifications: confirmatorySpecs,
                            confirmatory_remark: confirmatoryRemark,
                            disposition_of_exhibit: document.getElementById('confirmatoryDispositionOfExhibit')
                                ?.value || '',
                            lab_date_received: dateReceived,
                            lab_date_started: dateStarted,
                            lab_date_completed: dateCompleted,
                            date_received: dateReceived,
                            date_started: dateStarted,
                            date_completed: dateCompleted
                        })
                    });

                    const data5 = await res5.json();

                    if (res5.ok && data5.success) {
                        if (typeof showSaveSuccessPopover === 'function') showSaveSuccessPopover();
                        if (typeof showToast === 'function') showToast(
                            `✔ Seizure Item & Laboratory Examination saved successfully!`);
                        setTimeout(() => {
                            window.location.reload();
                        }, 800);
                    } else {
                        alert(data5.message || 'Saved Seizure Item, but failed to save Laboratory Examination.');
                        if (saveBtn4) saveBtn4.disabled = false;
                        if (saveBtn5) saveBtn5.disabled = false;
                        if (saveBtnText4) saveBtnText4.innerText = 'Save Seizure Item & Examination Information';
                        if (saveBtnText5) saveBtnText5.innerText = 'Save Seizure Item & Examination Information';
                    }
                } else if (section4Saved) {
                    if (typeof showToast === 'function') showToast(
                        `✔ Seizure Item saved successfully!`);
                    setTimeout(() => {
                        window.location.reload();
                    }, 800);
                }

            } catch (err) {
                alert('An error occurred while saving. Please check network connection and try again.');
                if (saveBtn4) saveBtn4.disabled = false;
                if (saveBtn5) saveBtn5.disabled = false;
                if (saveBtnText4) saveBtnText4.innerText = 'Save Seizure Item & Examination Information';
                if (saveBtnText5) saveBtnText5.innerText = 'Save Seizure Item & Examination Information';
            }
        }

        function emptySection5FormFields() {
            const formInputs = [
                'sampleWeightTakenReview',
                'sampleWeightReservedReview',
                'formLabNotes',
                'presumptiveReactionResultInputReview'
            ];

            formInputs.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });

            // Ensure reference fields retain/get valid defaults
            const examRefInput = document.getElementById('labExamReferenceInput');
            if (examRefInput && (!examRefInput.value || examRefInput.value.trim() === '')) {
                const randNum = Math.floor(1000 + Math.random() * 9000);
                examRefInput.value = `NLA-FM-T${randNum}-F`;
            }
            const caseRefInput = document.getElementById('labCaseReferenceInput');
            if (caseRefInput && (!caseRefInput.value || caseRefInput.value.trim() === '')) {
                caseRefInput.value = window.caseNumber || @json($case->case_number ?? '');
            }
            const labNumInput = document.getElementById('formLabNumber');
            if (labNumInput && (!labNumInput.value || labNumInput.value.trim() === '')) {
                labNumInput.value = @json($labNumber ?? '');
            }
            const analystInput = document.getElementById('formLabAnalyst');
            if (analystInput && (!analystInput.value || analystInput.value.trim() === '')) {
                analystInput.value = @json(auth()->user()->fullname ?? ($authUser->fullname ?? ($defaultLabHeadName !== 'N/A' ? $defaultLabHeadName : '')));
            }

            const unitAddon = document.getElementById('sampleWeightTakenUnitAddonReview');
            if (unitAddon) unitAddon.textContent = '';

            const errorMsgReviewEl = document.getElementById('sampleWeightTakenErrorReview');
            if (errorMsgReviewEl) errorMsgReviewEl.classList.add('hidden');
            const takenReview = document.getElementById('sampleWeightTakenReview');
            if (takenReview) {
                takenReview.classList.remove('border-red-500', 'ring-2', 'ring-red-500/20');
                takenReview.classList.add('border-slate-300');
            }

            // Uncheck presumptive radios and hide details
            const presRadios = document.querySelectorAll('input[name="form_presumptive_radio"]');
            presRadios.forEach(r => r.checked = false);

            const lblPresYes = document.getElementById('labelPresumptiveYes');
            const lblPresNo = document.getElementById('labelPresumptiveNo');
            if (lblPresYes) lblPresYes.className =
                'relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-slate-200 bg-slate-50';
            if (lblPresNo) lblPresNo.className =
                'relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-slate-200 bg-slate-50';

            const presDetailsContainer = document.getElementById('presumptiveReactionDetailsContainerReview');
            if (presDetailsContainer) presDetailsContainer.classList.add('hidden');

            const presContainer = document.getElementById('presumptiveTestRowsContainerReview');
            if (presContainer) {
                const rows = presContainer.querySelectorAll('.presumptive-test-row-review');
                rows.forEach((r, idx) => {
                    if (idx === 0) {
                        const presence = r.querySelector('.presumptive-presence-input-review');
                        const percent = r.querySelector('.presumptive-percentage-input-review');
                        if (presence) presence.value = '';
                        if (percent) percent.value = '';
                    } else {
                        r.remove();
                    }
                });
            }

            // Uncheck confirmatory radios and hide sample weight container
            const confRadios = document.querySelectorAll('input[name="form_confirmatory_radio"]');
            confRadios.forEach(r => r.checked = false);

            const lblConfYes = document.getElementById('labelConfirmatoryYesReview');
            const lblConfNo = document.getElementById('labelConfirmatoryNoReview');
            if (lblConfYes) lblConfYes.className =
                'relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-slate-200 bg-slate-50';
            if (lblConfNo) lblConfNo.className =
                'relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-slate-200 bg-slate-50';

            const sampleWeightContainer = document.getElementById('sampleWeightContainerReview');
            if (sampleWeightContainer) sampleWeightContainer.classList.add('hidden');

            const detectedContainer = document.getElementById('detectedSubstancesListContainerReview');
            if (detectedContainer) detectedContainer.innerHTML = '';
        }

        function resetSection5Form(findNext = false) {
            emptySection5FormFields();

            const form = document.getElementById('section5LabForm');
            if (form) {
                const elements = form.querySelectorAll('input, select, textarea, button');
                elements.forEach(el => {
                    if (!el.classList.contains('close-popover-btn')) {
                        el.disabled = false;
                        el.classList.remove('cursor-not-allowed', 'opacity-75', 'bg-slate-100');
                    }
                });
                form.querySelectorAll('.add-detected-substance-btn, .remove-detected-row-btn, .remove-test-row-btn-review')
                    .forEach(btn => {
                        btn.style.display = '';
                    });
            }

            const saveBtn = document.getElementById('saveSection5LabBtn');
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.className =
                    'px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-600/30 transition-all flex items-center justify-center cursor-pointer border-none';
                const textSpan = document.getElementById('saveSection5LabBtnText');
                if (textSpan) textSpan.innerHTML = 'Save Seizure Item & Examination Information';
            }

            if (findNext) {
                const exhibitSelect = document.getElementById('labExhibitSelect');
                if (exhibitSelect && exhibitSelect.options.length > 1) {
                    const options = Array.from(exhibitSelect.options);
                    const nextUnexamined = options.find(opt => opt.getAttribute('data-has-saved') !== 'true' && opt
                        .getAttribute('data-has-saved') !== '1');
                    if (nextUnexamined) {
                        exhibitSelect.value = nextUnexamined.value;
                        if (typeof onReviewLabExhibitChange === 'function') {
                            onReviewLabExhibitChange(exhibitSelect);
                        }
                    }
                }
            }
        }

        function findWeightChunkReview(parts) {
            if (!parts || parts.length === 0) return '';
            for (let i = parts.length - 1; i >= 0; i--) {
                const lower = parts[i].toLowerCase();
                if (/\b(kg|kgs|kilogram|kilograms|g|gm|gms|gram|grams|mg|mgs|milligram|milligrams|lb|lbs|pound|pounds)\b/i
                    .test(lower) || /\d\s*(kg|g|mg|lb)\b/i.test(lower)) {
                    return parts[i];
                }
            }
            for (let i = parts.length - 1; i >= 0; i--) {
                if (/[\d]+/.test(parts[i])) {
                    return parts[i];
                }
            }
            return parts[parts.length - 1];
        }

        function parsePartsReview(str) {
            return str.split('/').map(p => p.trim()).filter(p => p.length > 0);
        }

        function parseQtyAndUnitReview(chunk) {
            if (!chunk) return null;
            const numMatch = chunk.match(/([\d]+(?:\.[\d]+)?)/);
            if (!numMatch) return null;
            const num = parseFloat(numMatch[1]);
            if (isNaN(num)) return null;

            const lower = chunk.toLowerCase();
            let unit = '';

            if (/\b(kg|kgs|kilogram|kilograms)\b/i.test(lower) || /\d\s*kg\b/i.test(lower)) {
                unit = 'kg';
            } else if (/\b(mg|mgs|milligram|milligrams)\b/i.test(lower) || /\d\s*mg\b/i.test(lower)) {
                unit = 'mg';
            } else if (/\b(g|gm|gms|gram|grams)\b/i.test(lower) || /\d\s*g\b/i.test(lower)) {
                unit = 'g';
            } else if (/\b(lb|lbs|pound|pounds)\b/i.test(lower) || /\d\s*lb\b/i.test(lower)) {
                unit = 'lb';
            } else {
                const idx = chunk.indexOf(numMatch[1]);
                if (idx !== -1) {
                    const afterNum = chunk.substring(idx + numMatch[1].length).trim();
                    const firstWord = afterNum.split(/\s+/)[0];
                    if (firstWord) {
                        unit = firstWord.replace(/[^a-zA-Z]/g, '');
                    }
                }
            }
            return {
                num,
                unit
            };
        }

        function toKgReview(num, unit) {
            if (!unit) return null;
            const u = unit.toLowerCase();
            if (u === 'kg') return num;
            if (u === 'g') return num / 1000;
            if (u === 'mg') return num / 1000000;
            if (u === 'lb') return num * 0.453592;
            return null;
        }

        function formatNumReview(num) {
            if (num < 0) num = 0;
            const rounded = Math.round(num * 10000) / 10000;
            return rounded.toString();
        }

        function computeReservedSampleReview(grossStr, takenStr) {
            if (!grossStr || typeof grossStr !== 'string' || !grossStr.trim()) {
                return {
                    reserved: '0',
                    isOverflow: false
                };
            }

            const grossParts = parsePartsReview(grossStr);
            let targetGrossChunk = findWeightChunkReview(grossParts);
            const gParsed = parseQtyAndUnitReview(targetGrossChunk);

            const defaultReserved = (gParsed && gParsed.unit) ? `0 ${gParsed.unit}` : '0';

            if (!takenStr || typeof takenStr !== 'string' || !takenStr.trim()) {
                return {
                    reserved: defaultReserved,
                    isOverflow: false
                };
            }

            const takenParts = parsePartsReview(takenStr);
            let targetTakenChunk = findWeightChunkReview(takenParts);
            const tParsed = parseQtyAndUnitReview(targetTakenChunk);

            if (!gParsed || !tParsed) {
                return {
                    reserved: defaultReserved,
                    isOverflow: false
                };
            }

            let gKg = toKgReview(gParsed.num, gParsed.unit);
            let tKg = toKgReview(tParsed.num, tParsed.unit);

            if (gKg !== null && tKg === null && !tParsed.unit) {
                tKg = toKgReview(tParsed.num, gParsed.unit);
            }

            if (gKg !== null && tKg !== null) {
                if (tKg > gKg) {
                    return {
                        reserved: defaultReserved,
                        isOverflow: true
                    };
                }
                const diffKg = gKg - tKg;
                const gU = gParsed.unit.toLowerCase();
                let resVal = '0';
                if (gU === 'g' || gU === 'gm' || gU === 'gram' || gU === 'grams') {
                    resVal = formatNumReview(diffKg * 1000) + (gParsed.unit ? ' ' + gParsed.unit : '');
                } else if (gU === 'mg' || gU === 'milligram' || gU === 'milligrams') {
                    resVal = formatNumReview(diffKg * 1000000) + (gParsed.unit ? ' ' + gParsed.unit : '');
                } else {
                    resVal = formatNumReview(diffKg) + (gParsed.unit ? ' ' + gParsed.unit : '');
                }
                return {
                    reserved: resVal,
                    isOverflow: false
                };
            } else {
                if (tParsed.num > gParsed.num) {
                    return {
                        reserved: defaultReserved,
                        isOverflow: true
                    };
                }
                const diff = gParsed.num - tParsed.num;
                const unit = gParsed.unit || tParsed.unit;
                return {
                    reserved: formatNumReview(diff) + (unit ? ' ' + unit : ''),
                    isOverflow: false
                };
            }
        }

        function updateReservedWeightReview() {
            const grossReview = document.getElementById('sampleWeightGrossReview');
            const takenReview = document.getElementById('sampleWeightTakenReview');
            const addonReview = document.getElementById('sampleWeightTakenUnitAddonReview');
            const reservedReview = document.getElementById('sampleWeightReservedReview');
            const errorMsgReviewEl = document.getElementById('sampleWeightTakenErrorReview');

            if (!grossReview || !reservedReview) return;

            const grossStr = grossReview.value || '';
            const takenStr = takenReview ? takenReview.value : '';

            const result = computeReservedSampleReview(grossStr, takenStr);

            reservedReview.value = result.reserved;

            if (result.isOverflow) {
                if (errorMsgReviewEl) errorMsgReviewEl.classList.remove('hidden');
                if (takenReview) {
                    takenReview.classList.add('border-red-500', 'ring-2', 'ring-red-500/20');
                    takenReview.classList.remove('border-slate-300');
                }
                if (addonReview) {
                    addonReview.classList.add('border-red-500', 'bg-red-50', 'text-red-700');
                    addonReview.classList.remove('border-slate-300', 'bg-slate-100/90', 'text-slate-700');
                }
            } else {
                if (errorMsgReviewEl) errorMsgReviewEl.classList.add('hidden');
                if (takenReview) {
                    takenReview.classList.remove('border-red-500', 'ring-2', 'ring-red-500/20');
                    takenReview.classList.add('border-slate-300');
                }
                if (addonReview) {
                    addonReview.classList.remove('border-red-500', 'bg-red-50', 'text-red-700');
                    addonReview.classList.add('border-slate-300', 'bg-slate-100/90', 'text-slate-700');
                }
            }
        }

        function updateDetectedSubstanceRemoveButtonsReview() {
            const container = document.getElementById('detectedSubstancesListContainerReview');
            if (!container) return;
            const rows = container.querySelectorAll('.detected-substance-row-review');
            rows.forEach(r => {
                const btn = r.querySelector('button[title="Remove Row"]');
                if (btn) {
                    if (rows.length <= 1) {
                        btn.classList.add('hidden');
                        btn.style.display = 'none';
                    } else {
                        btn.classList.remove('hidden');
                        btn.style.display = '';
                    }
                }
            });
        }

        function addDetectedSubstanceRowReview(substance = '', percentage = '') {
            const container = document.getElementById('detectedSubstancesListContainerReview');
            if (!container) return;
            const rowDiv = document.createElement('div');
            rowDiv.className =
                'detected-substance-row-review flex flex-col sm:flex-row items-center gap-3 bg-white p-3 rounded-xl border border-slate-300 shadow-2xs animate-in fade-in duration-200';
            rowDiv.innerHTML = `
            <div class="flex-1 w-full">
                <label class="block text-slate-700 font-bold text-[10px] uppercase mb-1">Substance / Compound Detected</label>
                <input type="text" class="detected-substance-name-review w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-slate-900 font-extrabold text-xs focus:outline-none focus:border-emerald-600 font-sans" placeholder="e.g. Cocaine Hydrochloride" value="${substance}">
            </div>
            <div class="w-full sm:w-48">
                <label class="block text-slate-700 font-bold text-[10px] uppercase mb-1">Purity / Percentage</label>
                <div class="flex items-center rounded-lg overflow-hidden shadow-2xs">
                    <input type="text" class="detected-substance-percent-review w-full pl-3 pr-2 py-2 bg-slate-50 border border-y border-l border-slate-300 rounded-l-lg text-slate-900 font-extrabold text-xs focus:outline-none focus:border-emerald-600 font-mono-code" placeholder="e.g. 85.4" value="${percentage}">
                    <span class="inline-flex items-center px-2.5 py-2 bg-slate-200/80 border border-slate-300 rounded-r-lg text-slate-700 font-extrabold text-xs font-mono-code shrink-0">%</span>
                </div>
            </div>
            <button type="button" onclick="removeDetectedSubstanceRowReview(this)" class="mt-2 sm:mt-5 p-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg border border-red-200 transition-colors shrink-0 cursor-pointer" title="Remove Row">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        `;
            container.appendChild(rowDiv);
            updateDetectedSubstanceRemoveButtonsReview();
        }

        function removeDetectedSubstanceRowReview(btn) {
            const container = document.getElementById('detectedSubstancesListContainerReview');
            if (container) {
                const rows = container.querySelectorAll('.detected-substance-row-review');
                if (rows.length <= 1) return;
            }
            const row = btn.closest('.detected-substance-row-review');
            if (row) {
                row.remove();
                updateDetectedSubstanceRemoveButtonsReview();
            }
        }

        function getDetectedSubstancesDataReview() {
            const rows = document.querySelectorAll('#detectedSubstancesListContainerReview .detected-substance-row-review');
            const data = [];
            rows.forEach(r => {
                const sub = r.querySelector('.detected-substance-name-review')?.value || '';
                const pct = r.querySelector('.detected-substance-percent-review')?.value || '';
                if (sub.trim() !== '' || pct.trim() !== '') {
                    data.push({
                        substance: sub.trim(),
                        percentage: pct.trim()
                    });
                }
            });
            return data;
        }

        function toggleLabFormEditMode() {
            if (window.activeLabWorkflowMode === 'gsa') {
                return;
            }
            const formContainer = document.getElementById('section5FormContainer');
            const form = document.getElementById('section5LabForm');
            const readOnlyView = document.getElementById('section5ReadOnlyDisplay');
            if (form) {
                if (form.classList.contains('hidden')) {
                    if (formContainer) formContainer.classList.remove('hidden');
                    form.classList.remove('hidden');
                    if (readOnlyView) readOnlyView.classList.add('hidden');
                    const elements = form.querySelectorAll('input, select, textarea, button');
                    elements.forEach(el => {
                        el.disabled = false;
                        el.classList.remove('cursor-not-allowed', 'opacity-75', 'bg-slate-100');
                    });
                    form.querySelectorAll(
                        '.add-detected-substance-btn, .remove-detected-row-btn, .remove-test-row-btn-review').forEach(
                        btn => {
                            btn.style.display = '';
                        });
                    const saveBtn = document.getElementById('saveSection5LabBtn');
                    if (saveBtn) {
                        saveBtn.disabled = false;
                        saveBtn.className =
                            'px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-600/30 transition-all flex items-center justify-center cursor-pointer border-none';
                        const textSpan = document.getElementById('saveSection5LabBtnText');
                        if (textSpan) textSpan.innerHTML = 'Save Seizure Item & Examination Information';
                    }
                } else {
                    if (formContainer) formContainer.classList.add('hidden');
                    form.classList.add('hidden');
                    if (readOnlyView) readOnlyView.classList.add('hidden');
                }
            }
        }

        function lockSection5Form() {
            const formContainer = document.getElementById('section5FormContainer');
            const form = document.getElementById('section5LabForm');
            const readOnlyView = document.getElementById('section5ReadOnlyDisplay');

            if (readOnlyView) {
                readOnlyView.classList.add('hidden');
            }

            if (!form) return;

            const elements = form.querySelectorAll('input, select, textarea, button');
            elements.forEach(el => {
                if (el.id !== 'closeSaveSuccessPopoverBtn' && !el.classList.contains('close-popover-btn')) {
                    el.disabled = true;
                    el.classList.add('cursor-not-allowed', 'opacity-75', 'bg-slate-100');
                }
            });

            // Hide add/remove row buttons
            form.querySelectorAll('.add-detected-substance-btn, .remove-detected-row-btn').forEach(btn => {
                btn.style.display = 'none';
            });

            const saveBtn = document.getElementById('saveSection5LabBtn');
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.className =
                    'px-6 py-3 bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md flex items-center justify-center cursor-not-allowed opacity-90 border-none';
                const svgEl = saveBtn.querySelector('svg');
                if (svgEl) svgEl.remove();
                const textSpan = document.getElementById('saveSection5LabBtnText');
                if (textSpan) textSpan.innerHTML = 'LAB EXAMINATION SAVED & LOCKED';
            }
        }

        function unlockSection5Form() {
            const formContainer = document.getElementById('section5FormContainer');
            const form = document.getElementById('section5LabForm');
            if (!form) return;

            const elements = form.querySelectorAll('input, select, textarea, button');
            elements.forEach(el => {
                const isImmutableRef = [
                    'labExamReferenceInput',
                    'labCaseReferenceInput',
                    'formLabNumber'
                ].includes(el.id);

                const isAutoSampleWeight = [
                    'sampleWeightDescription',
                    'sampleWeightUnits',
                    'sampleWeightGross',
                    'sampleWeightReserved'
                ].some(prefix => el.id && el.id.startsWith(prefix) && !el.id.includes('Taken'));

                if (!isImmutableRef && !isAutoSampleWeight) {
                    el.disabled = false;
                    el.readOnly = false;
                    el.removeAttribute('readonly');
                    el.removeAttribute('disabled');
                    el.classList.remove('cursor-not-allowed', 'opacity-75', 'bg-slate-100');
                } else {
                    el.disabled = false;
                    el.removeAttribute('disabled');
                    el.classList.remove('opacity-75');
                    el.setAttribute('readonly', 'readonly');
                }
            });

            form.querySelectorAll('.add-detected-substance-btn, .remove-detected-row-btn, .remove-screening-method-btn').forEach(btn => {
                btn.style.display = '';
            });

            const saveBtn = document.getElementById('saveSection5LabBtn');
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.removeAttribute('disabled');
                saveBtn.className =
                    'px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-600/30 transition-all flex items-center justify-center cursor-pointer border-none';
                const textSpan = document.getElementById('saveSection5LabBtnText');
                if (textSpan) textSpan.innerHTML = 'Save Seizure Item &amp; Examination Information';
            }

            const examRefInput = document.getElementById('labExamReferenceInput');
            if (examRefInput && (!examRefInput.value || examRefInput.value.trim() === '' || examRefInput.value.startsWith('EXAM-') || examRefInput.value.includes('T28'))) {
                const randNum = Math.floor(1000 + Math.random() * 9000);
                examRefInput.value = `NLA-FM-T${randNum}-F`;
            } else if (examRefInput && (examRefInput.value.startsWith('GSA-') || examRefInput.value.startsWith('NLR-') || examRefInput.value.startsWith('CSS-') || examRefInput.value.startsWith('EMU-'))) {
                examRefInput.value = examRefInput.value.replace(/^(GSA|NLR|CSS|EMU)-/, 'NLA-');
            }
            const caseRefInput = document.getElementById('labCaseReferenceInput');
            if (caseRefInput && (!caseRefInput.value || caseRefInput.value.trim() === '')) {
                caseRefInput.value = window.caseNumber || @json($case->case_number ?? '');
            }
            const labNumInput = document.getElementById('formLabNumber');
            if (labNumInput && (!labNumInput.value || labNumInput.value.trim() === '')) {
                labNumInput.value = @json($labNumber ?? '');
            }
            const analystInput = document.getElementById('formLabAnalyst');
            if (analystInput && (!analystInput.value || analystInput.value.trim() === '')) {
                analystInput.value = @json(auth()->user()->fullname ?? ($authUser->fullname ?? ($defaultLabHeadName !== 'N/A' ? $defaultLabHeadName : '')));
            }
        }

        function showSaveSuccessPopover() {
            const modal = document.getElementById('saveSuccessPopoverModal');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.remove('hidden');
                modal.classList.add('show');
            }
        }

        function closeSaveSuccessPopover() {
            const modal = document.getElementById('saveSuccessPopoverModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
                modal.classList.remove('show');
            }
            window.location.reload();
        }

        document.addEventListener('DOMContentLoaded', function() {
            window.toggleSection5LabForm = function() {
                if (window.activeLabWorkflowMode === 'gsa') {
                    return;
                }
                const formContainer = document.getElementById('section5FormContainer');
                const form = document.getElementById('section5LabForm');
                if (formContainer) formContainer.classList.remove('hidden');
                if (form) {
                    form.classList.remove('hidden');
                    form.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            };

            const exhSel = document.getElementById('labExhibitSelect');
            const selectedOpt = exhSel ? exhSel.options[exhSel.selectedIndex] : null;
            const selectedExhibitIsSaved = selectedOpt ? (selectedOpt.getAttribute('data-has-saved') === 'true' || selectedOpt.getAttribute('data-has-saved') === '1') : false;

            if (selectedExhibitIsSaved) {
                lockSection5Form();
            } else {
                unlockSection5Form();
            }

            const initDetContainer = document.getElementById('detectedSubstancesListContainerReview');
            if (initDetContainer && initDetContainer.children.length === 0) {
                if (typeof addDetectedSubstanceRowReview === 'function') {
                    const initialName = document.getElementById('confirmatorySubstanceIdentificationReview')
                        ?.value || document.getElementById('labExhibitSelect')?.options[0]?.getAttribute(
                            'data-name') || '';
                    addDetectedSubstanceRowReview(initialName, '');
                }
            }
            const grossReviewEl = document.getElementById('sampleWeightGrossReview');
            const takenReviewEl = document.getElementById('sampleWeightTakenReview');
            ['input', 'keyup', 'change', 'blur', 'paste'].forEach(function(evt) {
                if (grossReviewEl) grossReviewEl.addEventListener(evt, updateReservedWeightReview);
                if (takenReviewEl) takenReviewEl.addEventListener(evt, updateReservedWeightReview);
            });

            updateReservedWeightReview();

            const existingDetected = @json($labDetectedSubstances);
            const detReviewContainer = document.getElementById('detectedSubstancesListContainerReview');
            if (detReviewContainer) {
                detReviewContainer.innerHTML = '';
                if (Array.isArray(existingDetected) && existingDetected.length > 0) {
                    existingDetected.forEach(item => {
                        if (typeof item === 'string') {
                            addDetectedSubstanceRowReview(item, '');
                        } else if (item && typeof item === 'object') {
                            addDetectedSubstanceRowReview(item.substance || '', item.percentage || '');
                        }
                    });
                } else {
                    addDetectedSubstanceRowReview('', '');
                }
            }

            const existingPresumptive = @json($labPresumptive);
            if (existingPresumptive && existingPresumptive !== 'N/A') {
                const isNo = existingPresumptive.toUpperCase().includes('NO (');
                const radioYes = document.querySelector('input[name="form_presumptive_radio"][value="YES"]');
                const radioNo = document.querySelector('input[name="form_presumptive_radio"][value="NO"]');
                if (isNo) {
                    if (radioNo) radioNo.checked = true;
                    onPresumptiveRadioChange('NO');
                } else {
                    if (radioYes) radioYes.checked = true;
                    onPresumptiveRadioChange('YES');

                    const matches = existingPresumptive.match(
                        /\[(\d+)\]\s*Presence of:\s*([^-\;]+)(?:\s*-\s*Purity\/Percentage:\s*([^\;]+))?/gi);
                    if (matches && matches.length > 0) {
                        const rowsContainer = document.getElementById('presumptiveTestRowsContainerReview');
                        if (rowsContainer) {
                            rowsContainer.innerHTML = '';
                            matches.forEach((m, idx) => {
                                addPresumptiveTestRowReview();
                                const rows = rowsContainer.querySelectorAll('.presumptive-test-row-review');
                                const targetRow = rows[rows.length - 1];
                                if (targetRow) {
                                    const presenceMatch = m.match(/Presence of:\s*([^-\;]+)/i);
                                    const pctMatch = m.match(/Purity\/Percentage:\s*([^\;]+)/i);
                                    if (presenceMatch && targetRow.querySelector(
                                            '.presumptive-presence-input-review')) {
                                        targetRow.querySelector('.presumptive-presence-input-review')
                                            .value = presenceMatch[1].trim();
                                    }
                                    if (pctMatch && targetRow.querySelector(
                                            '.presumptive-percentage-input-review')) {
                                        targetRow.querySelector('.presumptive-percentage-input-review')
                                            .value = pctMatch[1].trim();
                                    }
                                }
                            });
                        }
                    } else {
                        const singlePresence = existingPresumptive.match(/Presence of:\s*([^-\;\)]+)/i);
                        const singlePct = existingPresumptive.match(/Purity\/Percentage:\s*([^;\)]+)/i);
                        if (singlePresence || singlePct) {
                            const firstRow = document.querySelector('.presumptive-test-row-review');
                            if (firstRow) {
                                if (singlePresence && firstRow.querySelector(
                                        '.presumptive-presence-input-review')) {
                                    firstRow.querySelector('.presumptive-presence-input-review').value =
                                        singlePresence[1].trim();
                                }
                                if (singlePct && firstRow.querySelector('.presumptive-percentage-input-review')) {
                                    firstRow.querySelector('.presumptive-percentage-input-review').value =
                                        singlePct[1].trim();
                                }
                            }
                        }
                    }
                }
            }

            if (exhSel && typeof onReviewLabExhibitChange === 'function') {
                onReviewLabExhibitChange(exhSel);
            }

            // Lock form if currently selected exhibit is already completed
            if (selectedExhibitIsSaved) {
                lockSection5Form();
            } else {
                unlockSection5Form();
            }

            window.toggleSection5LabForm = function() {
                if (window.activeLabWorkflowMode === 'gsa') {
                    return;
                }
                const formContainer = document.getElementById('section5FormContainer');
                const form = document.getElementById('section5LabForm');
                if (formContainer) formContainer.classList.remove('hidden');
                if (form) {
                    form.classList.remove('hidden');
                    form.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            };

            const urlParams = new URLSearchParams(window.location.search);
            const modeParam = urlParams.get('mode');
            if (modeParam === 'nlr' || urlParams.get('section') === '5') {
                if (typeof switchToNlrExamSection === 'function') {
                    setTimeout(() => switchToNlrExamSection(), 200);
                }
            } else if (modeParam === 'gsa' || urlParams.get('section') === 'gsa') {
                if (typeof switchToGsaUploadSection === 'function') {
                    setTimeout(() => switchToGsaUploadSection(), 200);
                }
            }
        });

        // -------------------------------------------------------------------------
        // CASE STATUS MANAGEMENT, HANDOVER & AUDIT TRAIL JS HANDLERS
        // -------------------------------------------------------------------------

        function updateCaseStatus(newStatus) {
            if (!newStatus) return;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                '{{ csrf_token() }}';
            const caseId = @json($case->case_number) || @json($case->id);

            fetch(`/cases/${encodeURIComponent(caseId)}/case-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        case_status: newStatus
                    })
                })
                .then(response => {
                    return response.json().then(data => ({
                        status: response.status,
                        body: data
                    })).catch(() => ({
                        status: response.status,
                        body: { message: `Server error (HTTP ${response.status})` }
                    }));
                })
                .then(res => {
                    if (res.status === 200 && res.body && res.body.success) {
                        const msg = res.body.message || 'Case status updated successfully.';
                        alert(msg);
                        if (typeof fetchAuditTrail === 'function') {
                            try {
                                fetchAuditTrail();
                            } catch (e) {
                                // Audit trail update skipped
                            }
                        }
                        window.location.reload();
                    } else {
                        const errMsg = (res.body && res.body.message) || 'Failed to update case status.';
                        alert(errMsg);
                        window.location.reload();
                    }
                })
                .catch(err => {
                    alert('An unexpected error occurred while updating case status.');
                });
        }
    </script>

    <!-- REVIEW MUGSHOT / EXHIBIT LIGHTBOX MODAL -->
    <div id="reviewMugshotLightboxModal"
        class="hidden fixed inset-0 bg-slate-950/90 backdrop-blur-md z-[99999] flex items-center justify-center p-4"
        onclick="closeMugshotModal()">
        <div class="bg-white rounded-2xl max-w-3xl w-full p-6 shadow-2xl space-y-4 relative border border-slate-200"
            onclick="event.stopPropagation()">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <h4 id="reviewMugshotModalTitle"
                    class="text-xs font-black font-mono-code uppercase text-slate-900 tracking-wider">High-Resolution
                    Visual Preview</h4>
                <button type="button" onclick="closeMugshotModal()"
                    class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-xs font-bold border border-slate-300 transition-colors cursor-pointer">
                    Close
                </button>
            </div>
            <div
                class="flex items-center justify-center bg-slate-900 rounded-xl p-3 min-h-[350px] max-h-[70vh] overflow-hidden">
                <img id="reviewMugshotModalImage" src="" alt="Full Exhibit View"
                    class="max-h-[65vh] max-w-full object-contain rounded-lg shadow-lg">
            </div>
            <div class="flex justify-end pt-2">
                <button type="button" onclick="closeMugshotModal()"
                    class="px-5 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold rounded-xl transition-all cursor-pointer">
                    Close Preview
                </button>
            </div>
        </div>
    </div>

    <!-- BOOTSTRAP-STYLE POPOVER SUCCESS MODAL -->
    <div id="saveSuccessPopoverModal"
        class="modal fade hidden" tabindex="-1" style="display: none; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 99999;" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-body text-center p-4 p-md-5">
                    <div class="rounded-circle bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                        <svg class="bi" width="36" height="36" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425a.247.247 0 0 1 .02-.022z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace text-uppercase mb-2 px-3 py-1">SUCCESSFULLY RECORDED</span>
                        <h4 class="modal-title fw-bold text-dark mt-2 mb-2">Lab Examination Saved</h4>
                        <p class="text-secondary small mb-4">
                            The laboratory examination results &amp; findings log have been successfully saved to the database and locked against further editing.
                        </p>
                    </div>
                    <button id="closeSaveSuccessPopoverBtn" onclick="closeSaveSuccessPopover()"
                        class="btn btn-success w-100 py-2.5 fw-bold shadow-sm rounded-3 close-popover-btn">
                        Acknowledge &amp; Continue
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- SUSPECT DISPOSITION POPOVER MODAL (TAILWIND DESIGN SYSTEM) -->
    <div id="suspectDispositionModal"
        class="hidden fixed inset-0 z-[99999] bg-slate-950/75 backdrop-blur-md flex items-center justify-center p-4 overflow-y-auto"
        onclick="if(event.target === this) closeSuspectDispositionModal()">
        <div class="bg-white rounded-3xl shadow-2xl max-w-xl w-full overflow-hidden border border-slate-200/80 transform transition-all relative my-auto"
            onclick="event.stopPropagation()">
            
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 px-6 py-4 flex items-center justify-between border-b border-slate-700/60 text-white">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center border border-amber-500/30 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-mono-code font-black bg-amber-400 text-slate-950 uppercase tracking-widest">NACOC DISPOSITION CONTROL</span>
                        <h3 class="text-sm font-bold text-white mt-0.5" id="modalSuspectNameTitle">Update Suspect Disposition</h3>
                    </div>
                </div>
                <button type="button" onclick="closeSuspectDispositionModal()"
                    class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center transition-colors cursor-pointer border border-slate-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 space-y-5">
                <input type="hidden" id="suspectDispTargetName" value="">
                
                <!-- Suspect Context Banner -->
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-11 h-11 rounded-full bg-slate-800 text-white font-mono-code font-black text-sm flex items-center justify-center shrink-0 shadow-sm" id="modalTargetSuspectAvatar">
                            S
                        </div>
                        <div class="min-w-0">
                            <div class="font-extrabold text-slate-900 text-sm truncate" id="modalTargetSuspectNameDisplay">Target Suspect</div>
                            <div class="text-[11px] text-slate-500 font-mono-code mt-0.5">Case Docket: <span class="font-bold text-blue-600">#{{ $case->case_number }}</span></div>
                        </div>
                    </div>
                    <div class="shrink-0">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-mono-code font-bold border bg-slate-100 text-slate-700 border-slate-300" id="modalCurrentDispBadge">Active — Under Investigation</span>
                    </div>
                </div>

                <!-- Select Disposition -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-black font-mono-code uppercase tracking-wider text-slate-700">Select New Disposition Status <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <select id="suspectDispSelect" onchange="handleDispSelectChange(this.value)"
                            class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-2xs appearance-none cursor-pointer">
                            <option value="Active — Under Investigation">Active — Under Investigation (Default Active State)</option>
                            <option value="Cleared / No Further Action">Cleared / No Further Action (Full Clearance &amp; Exoneration)</option>
                            <option value="Released from Case">Released from Case (Released Without Charges)</option>
                            <option value="Reclassified">Reclassified (Reclassified Suspect State)</option>
                            <option value="Referred for Further Review">Referred for Further Review (Referred to Legal/Prosecution)</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                </div>

                <!-- Justification Reason -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-black font-mono-code uppercase tracking-wider text-slate-700">
                        Official Justification / Reason <span id="reasonReqAsterisk" class="text-red-500" style="display:none;">*</span>
                    </label>
                    <textarea id="suspectDispReason" rows="3"
                        placeholder="State official findings, authorization details, or clearance grounds..."
                        class="w-full bg-white border border-slate-300 rounded-xl p-3 text-xs text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-2xs resize-none"></textarea>
                    <p class="text-[11px] text-slate-500 flex items-center gap-1.5 mt-1 font-sans">
                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span id="reasonNoticeText">Optional justification for active investigation status.</span>
                    </p>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="bg-slate-50 px-6 py-4 flex items-center justify-between border-t border-slate-200">
                <button type="button" onclick="closeSuspectDispositionModal()"
                    class="px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold border border-slate-300 transition-all cursor-pointer shadow-2xs">
                    Cancel
                </button>
                <button type="button" id="submitDispBtn" onclick="submitSuspectDisposition()"
                    class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-extrabold shadow-md transition-all inline-flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Save Suspect Disposition
                </button>
            </div>
        </div>
    </div>

    <script>
        function openSuspectDispositionModal(suspectName, currentDisp) {
            document.getElementById('suspectDispTargetName').value = suspectName;

            const nameEl = document.getElementById('modalTargetSuspectNameDisplay');
            if (nameEl) nameEl.innerText = suspectName;

            const avatarEl = document.getElementById('modalTargetSuspectAvatar');
            if (avatarEl) avatarEl.innerText = suspectName ? suspectName.charAt(0).toUpperCase() : 'S';

            const dispBadge = document.getElementById('modalCurrentDispBadge');
            if (dispBadge) {
                const cDisp = currentDisp || 'Active — Under Investigation';
                dispBadge.innerText = cDisp;
                dispBadge.className = 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-mono-code font-bold border ' +
                    (cDisp.includes('Cleared') ? 'bg-emerald-100 text-emerald-800 border-emerald-300' :
                    (cDisp.includes('Released') ? 'bg-blue-100 text-blue-800 border-blue-300' :
                    (cDisp.includes('Reclassified') ? 'bg-purple-100 text-purple-800 border-purple-300' :
                    (cDisp.includes('Referred') ? 'bg-amber-100 text-amber-800 border-amber-300' :
                    'bg-slate-100 text-slate-700 border-slate-300'))));
            }

            if (document.getElementById('suspectDispSelect')) {
                document.getElementById('suspectDispSelect').value = currentDisp || 'Active — Under Investigation';
                handleDispSelectChange(document.getElementById('suspectDispSelect').value);
            }
            document.getElementById('suspectDispReason').value = '';

            const modal = document.getElementById('suspectDispositionModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }

        function closeSuspectDispositionModal() {
            const modal = document.getElementById('suspectDispositionModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        function handleDispSelectChange(val) {
            const reqAst = document.getElementById('reasonReqAsterisk');
            const noticeText = document.getElementById('reasonNoticeText');
            if (val && !val.includes('Active')) {
                if (reqAst) reqAst.style.display = 'inline';
                if (noticeText) noticeText.innerText = 'A valid justification reason is mandatory when clearing, releasing, or reclassifying a suspect.';
            } else {
                if (reqAst) reqAst.style.display = 'none';
                if (noticeText) noticeText.innerText = 'Optional justification for active investigation status.';
            }
        }

        function showTopSuccessBanner(msg) {
            let alertBanner = document.getElementById('topSuccessAlertBanner');
            if (!alertBanner) {
                alertBanner = document.createElement('div');
                alertBanner.id = 'topSuccessAlertBanner';
                alertBanner.className = 'fixed top-5 left-1/2 -translate-x-1/2 z-[100000] max-w-xl w-11/12 bg-slate-900 text-white border-2 border-emerald-500 rounded-2xl p-4 shadow-2xl flex items-center justify-between gap-3';
                document.body.appendChild(alertBanner);
            }
            alertBanner.innerHTML = `
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-500/40">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div>
                        <span class="inline-block px-2 py-0.5 rounded text-[9px] font-mono-code font-black bg-emerald-400 text-slate-950 uppercase tracking-wider">SUCCESSFUL UPDATE</span>
                        <div class="text-xs font-bold text-white mt-0.5">${msg}</div>
                    </div>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white p-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            `;
            setTimeout(() => { if (alertBanner) alertBanner.remove(); }, 6000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const savedMsg = sessionStorage.getItem('disp_success_msg');
            if (savedMsg) {
                sessionStorage.removeItem('disp_success_msg');
                showTopSuccessBanner(savedMsg);
                if (typeof showToast === 'function') {
                    showToast(`✔ ${savedMsg}`);
                }
            }
        });

        function submitSuspectDisposition(event) {
            if (event) event.preventDefault();

            const suspectName = document.getElementById('suspectDispTargetName').value;
            const disposition = document.getElementById('suspectDispSelect').value;
            const reason = document.getElementById('suspectDispReason').value.trim();

            if (!disposition.includes('Active') && !reason) {
                if (typeof showToast === 'function') {
                    showToast('⚠ A valid justification reason is required when clearing, releasing, or reclassifying a suspect.');
                } else {
                    alert('A valid justification reason is required when clearing, releasing, or reclassifying a suspect.');
                }
                return;
            }

            const btn = document.getElementById('submitDispBtn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="inline-block animate-spin rounded-full h-3.5 w-3.5 border-2 border-white border-t-transparent mr-1"></span> Saving...';
            }

            const caseId = @json($case->case_number) || @json($case->id);
            fetch(`/cases/${encodeURIComponent(caseId)}/suspect-disposition`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                            '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        suspect_name: suspectName,
                        suspect_disposition: disposition,
                        disposition_reason: reason
                    })
                })
                .then(res => {
                    return res.json().then(data => {
                        return { ok: res.ok, status: res.status, data: data };
                    }).catch(() => {
                        return { ok: res.ok, status: res.status, data: { message: `Server error (HTTP ${res.status})` } };
                    });
                })
                .then(result => {
                    const data = result.data;
                    if (result.ok && data.success) {
                        closeSuspectDispositionModal();
                        
                        const succMsg = data.message || `Suspect disposition for ${suspectName} updated to '${disposition}'.`;
                        sessionStorage.setItem('disp_success_msg', succMsg);

                        // Real-time badge update
                        const dispBadge = document.getElementById('modalCurrentDispBadge');
                        if (dispBadge) {
                            dispBadge.innerText = disposition;
                        }

                        showTopSuccessBanner(succMsg);
                        if (typeof showToast === 'function') {
                            showToast(`✔ ${succMsg}`);
                        }

                        setTimeout(() => window.location.reload(), 1200);
                    } else {
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = '<svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Save Suspect Disposition';
                        }
                        const errMsg = data.message || `Failed to update suspect disposition (HTTP ${result.status}).`;
                        if (typeof showToast === 'function') {
                            showToast(`❌ ${errMsg}`);
                        } else {
                            alert(errMsg);
                        }
                    }
                })
                .catch(err => {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Save Suspect Disposition';
                    }
                    const errMsg = err.message || 'An unexpected error occurred while updating suspect disposition.';
                    if (typeof showToast === 'function') {
                        showToast(`❌ ${errMsg}`);
                    } else {
                        alert(errMsg);
                    }
                });
        }

        function toggleAndScrollToSeizureSection() {
            toggleSeizureItemSection(true, true);
        }

        window.activeSuspectName = @json($activeSuspectName ?? '');
        window.activeSuspectHasPersistedDrugExhibit = {{ !empty($activeSuspectHasDrugExhibit) ? 'true' : 'false' }};
        window.activeSuspectHasSavedLabResult = {{ !empty($activeSuspectHasSavedResult) ? 'true' : 'false' }};
        window.activeSuspectDrugExhibitsJson = @json($suspectDrugExhibitsList ?? []);

        function onDrugTypeChange(selectEl) {
            const selectedType = selectEl ? (selectEl.value || '').trim().toLowerCase() : '';
            const labExhibitSelect = document.getElementById('labExhibitSelect');
            const labExhibitCountBadge = document.getElementById('labExhibitCountBadge');
            const hiddenExIdInput = document.getElementById('labFormExhibitId');

            if (!labExhibitSelect) return;

            let jsonExhibits = (window.activeSuspectDrugExhibitsJson || []).slice();
            let domExhibits = [];

            const blocks = document.querySelectorAll('.seizure-block');
            blocks.forEach((block, idx) => {
                const itemNum = idx + 1;
                const cat = block.querySelector('#newSeizureCategory')?.value || block.querySelector(
                    '#exhibitSearchInput')?.value || '';
                let name = block.querySelector('#newSeizureNameDrug')?.value ||
                    block.querySelector('#drugSearchInput')?.value ||
                    block.querySelector('#newSeizureNameWeapon')?.value ||
                    block.querySelector('#weaponSearchInput')?.value ||
                    block.querySelector('#newSeizureNameCurrency')?.value ||
                    block.querySelector('#currencySearchInput')?.value ||
                    block.querySelector('#newSeizureNameDevice')?.value ||
                    block.querySelector('#deviceSearchInput')?.value ||
                    block.querySelector('#newSeizureName')?.value || '';
                const qty = block.querySelector('input[name="seizure_quantity"]')?.value || block.querySelector(
                    '#newSeizureQuantity')?.value || '';
                const pkg = block.querySelector('input[name="seizure_packaging"]')?.value || block.querySelector(
                    '#newSeizurePackaging')?.value || block.querySelector('#pkgSearchInput')?.value || '';
                const weight = block.querySelector('input[name="seizure_weight"]')?.value || block.querySelector(
                    '#newSeizureWeight')?.value || '';
                const unit = block.querySelector('input[name="seizure_unit"]')?.value || block.querySelector(
                    '#newSeizureUnit')?.value || block.querySelector('#unitSearchInput')?.value || '';
                const owner = block.querySelector('.seizure-associated-person-select')?.value || window
                    .activeSuspectName || '';

                if (name || cat) {
                    let displayName = name || cat || `Exhibit #${itemNum}`;
                    domExhibits.push({
                        id: itemNum.toString(),
                        index: itemNum,
                        name: displayName,
                        cat: cat,
                        qty: qty,
                        weight: weight,
                        unit: unit,
                        pkg: pkg,
                        owner: owner,
                        description: pkg ? `${displayName} (${pkg})` : displayName,
                        is_associate: false,
                        has_saved_lab: false
                    });
                }
            });

            let allExhibits = domExhibits.length > 0 ?
                domExhibits.concat(jsonExhibits.filter(j => !domExhibits.some(d => (d.name || '').toLowerCase() === (j
                    .name || '').toLowerCase()))) :
                jsonExhibits;

            let matchingExhibits = allExhibits;
            if (selectedType !== '') {
                matchingExhibits = allExhibits.filter(function(ex) {
                    const name = (ex.name || '').toLowerCase();
                    const cat = (ex.cat || '').toLowerCase();
                    const subId = (ex.substance_identification || '').toLowerCase();
                    const desc = (ex.description || '').toLowerCase();
                    return name.includes(selectedType) || cat.includes(selectedType) || subId.includes(
                        selectedType) || desc.includes(selectedType);
                });
            }

            labExhibitSelect.innerHTML = '';

            const placeholderOpt = document.createElement('option');
            placeholderOpt.value = '';
            placeholderOpt.textContent = '-- Select Seized Exhibit --';
            placeholderOpt.selected = true;
            labExhibitSelect.appendChild(placeholderOpt);

            if (matchingExhibits.length > 0) {
                placeholderOpt.selected = false;
                labExhibitSelect.removeAttribute('disabled');
                matchingExhibits.forEach(function(dEx, idx) {
                    const opt = document.createElement('option');
                    opt.value = dEx.id || dEx.index;
                    opt.setAttribute('data-ex-id', dEx.id || '');
                    opt.setAttribute('data-index', dEx.index);
                    opt.setAttribute('data-name', dEx.name || '');
                    opt.setAttribute('data-cat', dEx.cat || '');
                    opt.setAttribute('data-owner', dEx.owner || '');
                    opt.setAttribute('data-qty', dEx.qty || '');
                    opt.setAttribute('data-weight', dEx.weight || '');
                    opt.setAttribute('data-unit', dEx.unit || '');
                    opt.setAttribute('data-pkg', dEx.pkg || '');
                    opt.setAttribute('data-description', dEx.description || '');
                    opt.setAttribute('data-is-associate', dEx.is_associate ? '1' : '0');
                    opt.setAttribute('data-has-saved', dEx.has_saved_lab ? 'true' : 'false');
                    opt.setAttribute('data-confirmatory', dEx.confirmatory_result || '');
                    opt.setAttribute('data-substance-id', dEx.substance_identification || dEx.name || '');
                    opt.setAttribute('data-test-methods', dEx.test_methods || '');
                    opt.setAttribute('data-specifications', dEx.specifications || '');
                    opt.setAttribute('data-remark', dEx.remark || '');
                    opt.setAttribute('data-sample-taken', dEx.sample_taken || '');
                    opt.setAttribute('data-sample-reserved', dEx.sample_reserved || '');
                    opt.setAttribute('data-findings', dEx.findings || '');
                    opt.setAttribute('data-detected-substances', JSON.stringify(dEx.detected_substances || []));

                    let itemTitle = dEx.name || dEx.cat || `Exhibit #${idx + 1}`;
                    let qtyUnitSuffix = (dEx.unit && dEx.qty && !dEx.qty.toLowerCase().includes(dEx.unit
                        .toLowerCase())) ? (' ' + dEx.unit) : '';
                    let weightUnitSuffix = (dEx.unit && dEx.weight && !dEx.weight.toLowerCase().includes(dEx.unit
                        .toLowerCase())) ? (' ' + dEx.unit) : '';
                    let metricsStr = dEx.qty ? (' — ' + dEx.qty + qtyUnitSuffix) : (dEx.weight ? (' — ' + dEx
                        .weight + weightUnitSuffix) : '');
                    let rawDesc = dEx.pkg || dEx.description || '';
                    let cleanDesc = rawDesc;
                    if (cleanDesc && itemTitle) {
                        if (cleanDesc.toLowerCase().startsWith(itemTitle.toLowerCase())) {
                            cleanDesc = cleanDesc.substring(itemTitle.length).trim();
                        }
                        cleanDesc = cleanDesc.replace(/^[\s\—\-\:\(\)]+/, '').replace(/[\)]+$/, '').trim();
                    }
                    let descStr = cleanDesc ? (` (${cleanDesc})`) : '';
                    opt.textContent = `${itemTitle}${metricsStr}${descStr}`;

                    if (idx === 0) {
                        opt.selected = true;
                    }

                    labExhibitSelect.appendChild(opt);
                });

                if (labExhibitCountBadge) {
                    labExhibitCountBadge.textContent = matchingExhibits.length + ' Drug Exhibit(s) Synced';
                }
                if (hiddenExIdInput) {
                    hiddenExIdInput.value = '';
                }

                onReviewLabExhibitChange(labExhibitSelect);
            } else {
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = 'No matching seized exhibit found';
                opt.selected = true;
                labExhibitSelect.appendChild(opt);
                labExhibitSelect.setAttribute('disabled', 'disabled');

                if (labExhibitCountBadge) {
                    labExhibitCountBadge.textContent = '0 Matching Exhibits';
                }
                if (hiddenExIdInput) {
                    hiddenExIdInput.value = '';
                }
            }
        }

        function syncSection4ToSection5InReview() {
            const s4Checkbox = document.getElementById('toggleSeizureItemSectionCheckbox');
            if (s4Checkbox && !s4Checkbox.checked) {
                return;
            }

            const block = document.querySelector('.seizure-block');
            if (!block) return;

            const qty = block.querySelector('#newSeizureQuantity')?.value || '';
            const pkg = block.querySelector('#newSeizurePackaging')?.value || block.querySelector('#pkgSearchInput')
                ?.value || '';
            const weight = block.querySelector('#newSeizureWeight')?.value || '';
            const unit = block.querySelector('#newSeizureUnit')?.value || block.querySelector('#unitSearchInput')?.value ||
                '';
            const drugName = (block.querySelector('#newSeizureNameDrug')?.value ||
                block.querySelector('#drugSearchInput')?.value ||
                block.querySelector('#newSeizureName')?.value || '').trim();

            if (!drugName) return;

            const sampleDescInput = document.getElementById('sampleWeightDescriptionReview');
            const sampleDescMain = document.getElementById('sampleWeightDescription');
            const descInput = document.getElementById('labExhibitDescriptionInput');
            const substanceIdInput = document.getElementById('confirmatorySubstanceIdentificationReview');
            const substanceIdMain = document.getElementById('confirmatorySubstanceIdentification');
            const unitsInput = document.getElementById('sampleWeightUnitsReview');
            const unitsMain = document.getElementById('sampleWeightUnits');
            const grossInput = document.getElementById('sampleWeightGrossReview');
            const grossMain = document.getElementById('sampleWeightGross');
            const unitAddon = document.getElementById('sampleWeightTakenUnitAddonReview');

            let unitsText = (qty && pkg) ? `${qty} ${pkg}`.trim() : (qty || pkg || '');
            let grossWeightText = (weight && unit) ? `${weight} ${unit}`.trim() : (weight || unit || unitsText || '');

            if (sampleDescInput) sampleDescInput.value = drugName;
            if (sampleDescMain) sampleDescMain.value = drugName;
            if (substanceIdInput) substanceIdInput.value = drugName;
            if (substanceIdMain) substanceIdMain.value = drugName;
            if (unitsInput && unitsText) unitsInput.value = unitsText;
            if (unitsMain && unitsText) unitsMain.value = unitsText;
            if (grossInput && grossWeightText) grossInput.value = grossWeightText;
            if (grossMain && grossWeightText) grossMain.value = grossWeightText;

            if (unitAddon && unit) {
                unitAddon.textContent = unit;
            } else if (unitAddon && grossWeightText) {
                const uMatch = grossWeightText.match(/(Kilograms?\s*\([^\)]+\)|Kilograms?|kg|Grams?|g|mg|lbs?|Litres?|L)/i);
                if (uMatch) unitAddon.textContent = uMatch[1];
            }

            if (descInput) {
                let formattedDesc = drugName;
                const cat = block.querySelector('#newSeizureCategory')?.value || block.querySelector('#exhibitSearchInput')?.value || '';
                const contents = block.querySelector('.seizure-contents-input')?.value || block.querySelector('#newSeizureContents')?.value || '';
                const owner = block.querySelector('.seizure-associated-person-select')?.value || '';
                if (cat) formattedDesc += ` | Category: ${cat}`;
                if (contents) formattedDesc += ` | Contents: ${contents}`;
                if (unitsText) formattedDesc += ` | Quantity/Packaging: ${unitsText}`;
                if (grossWeightText) formattedDesc += ` | Net Weight/Metrics: ${grossWeightText}`;
                if (owner) formattedDesc += ` | Suspect / Ownership: ${owner}`;
                descInput.value = formattedDesc;
            }

            const confDesc1 = document.getElementById('sampleWeightDescription_1');
            if (confDesc1) confDesc1.value = drugName;
            const confUnits1 = document.getElementById('sampleWeightUnits_1');
            if (confUnits1 && unitsText) confUnits1.value = unitsText;
            const confGross1 = document.getElementById('sampleWeightGross_1');
            if (confGross1 && grossWeightText) confGross1.value = grossWeightText;
            const confSub1 = document.getElementById('confirmatorySubstanceIdentification_1');
            if (confSub1) confSub1.value = drugName;

            const confTitle1 = document.querySelector('#confirmatoryTestExhibitCard_1 .confirmatory-card-title');
            if (confTitle1) confTitle1.innerHTML = `Confirmatory Test — Exhibit #1: <span class="text-emerald-700">${drugName}</span>`;
            const presTitle1 = document.querySelector('#presumptiveTestExhibitCard_1 .presumptive-card-title');
            if (presTitle1) presTitle1.innerHTML = `Presumptive Screening — Exhibit #1: <span class="text-emerald-700">${drugName}</span>`;

            const firstCard = document.querySelector('#labExhibitCardsContainer .lab-exhibit-card:first-child');
            if (firstCard) {
                const cardTitle = firstCard.querySelector('.font-extrabold.text-slate-900.text-xs');
                if (cardTitle) {
                    cardTitle.innerText = drugName;
                    cardTitle.title = drugName;
                }
            }
        }

        function evaluateLabExaminationVisibility() {
            const formContainer = document.getElementById('section5FormContainer');
            const noDrugNotice = document.getElementById('section5NoDrugExhibitNotice');
            const readOnlyView = document.getElementById('section5ReadOnlyDisplay');

            if (!formContainer && !noDrugNotice && !readOnlyView) return;

            if (readOnlyView) readOnlyView.classList.add('hidden');

            // In neutral mode (no module button clicked yet), keep examination form hidden
            if (window.activeLabWorkflowMode !== 'nlr') {
                if (formContainer) formContainer.classList.add('hidden');
                if (noDrugNotice) noDrugNotice.classList.add('hidden');
                return;
            }

            // Section 5 Examination Information section visible only when NLR mode is active
            if (readOnlyView) readOnlyView.classList.add('hidden');
            if (formContainer) formContainer.classList.remove('hidden');
            if (noDrugNotice) noDrugNotice.classList.add('hidden');

            if ((window.activeSuspectDrugExhibitsJson || []).length > 0) {
                onDrugTypeChange(null);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            evaluateLabExaminationVisibility();
            syncSection4ToSection5InReview();

            // Real-time capturing listeners for all Section 4 input events to sync to Section 5
            ['input', 'keyup', 'change', 'blur', 'paste'].forEach(function(eventType) {
                document.addEventListener(eventType, function(e) {
                    if (e.target && e.target.closest && (e.target.closest(
                            '#seizuresDetailsContainer') || e.target.closest(
                            '#seizuresListContainer') || e.target.closest(
                            '#section4Seizures'))) {
                        if (typeof syncSection4ExhibitsToLabSection === 'function') {
                            syncSection4ExhibitsToLabSection();
                        }
                        if (typeof syncSection4ToSection5InReview === 'function') {
                            syncSection4ToSection5InReview();
                        }
                    }
                }, true);
            });

            document.addEventListener('input', function(e) {
                syncSection4ToSection5InReview();
                if (e.target && (e.target.id === 'exhibitSearchInput' || e.target.id ===
                        'newSeizureCategory')) {
                    evaluateLabExaminationVisibility();
                }
            });
            document.addEventListener('change', function(e) {
                syncSection4ToSection5InReview();
                if (e.target && (e.target.id === 'exhibitSearchInput' || e.target.id ===
                        'newSeizureCategory' || e.target.classList.contains(
                            'seizure-associated-person-select') || e.target.id === 'newSeizureSuspect')) {
                    evaluateLabExaminationVisibility();
                }
            });
        });

        function getSeizureBlockFromEvent(e) {
            if (!e) return document.querySelector('.seizure-block') || document;
            const target = e.target || e.srcElement;
            if (target && target.closest) {
                return target.closest('.seizure-block') || document.querySelector('.seizure-block') || document;
            }
            return document.querySelector('.seizure-block') || document;
        }

        window._savedCaseExhibitsData = @json($allCaseExhibits ?? []);

        function getPristineSeizureBlockTemplate() {
            if (!window._seizureBlockTemplate) {
                const container = document.getElementById('seizuresListContainer');
                const firstBlock = container ? container.querySelector('.seizure-block') : null;
                if (firstBlock) {
                    window._seizureBlockTemplate = firstBlock.cloneNode(true);
                    window._seizureBlockTemplate.querySelectorAll('input, select, textarea, button').forEach(el => {
                        el.disabled = false;
                    });
                    window._seizureBlockTemplate.querySelectorAll('input:not([type="checkbox"]):not([type="radio"]), textarea').forEach(input => {
                        input.value = '';
                    });
                    window._seizureBlockTemplate.querySelectorAll('input[type="hidden"]').forEach(input => {
                        input.value = '';
                    });
                    window._seizureBlockTemplate.querySelectorAll('select').forEach(select => {
                        select.selectedIndex = 0;
                    });
                    window._seizureBlockTemplate.querySelector('.seizure-associated-person-select')?.removeAttribute('data-user-selected');
                    const photoGrid = window._seizureBlockTemplate.querySelector('.seizurePhotosGrid');
                    if (photoGrid) { photoGrid.innerHTML = ''; photoGrid.classList.add('hidden'); }
                    const photoCount = window._seizureBlockTemplate.querySelector('#seizurePhotosCount, .seizure-photos-count');
                    if (photoCount) { photoCount.innerText = '0 Photos Uploaded'; photoCount.classList.add('hidden'); }
                    const fileInput = window._seizureBlockTemplate.querySelector('.fileSeizurePhoto');
                    if (fileInput) fileInput.value = '';
                }
            }
            return window._seizureBlockTemplate;
        }

        function seizureBlockHasData(block) {
            if (!block) return false;
            const textInputs = block.querySelectorAll('input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]):not([type="file"]), textarea');
            for (const input of textInputs) {
                if (input.value && input.value.trim() !== '') {
                    return true;
                }
            }
            const hiddenInputs = block.querySelectorAll('input[type="hidden"]');
            for (const hidden of hiddenInputs) {
                if (hidden.name === '_token') continue;
                if (hidden.value && hidden.value.trim() !== '') {
                    return true;
                }
            }
            const suspectSelect = block.querySelector('.seizure-associated-person-select');
            if (suspectSelect && suspectSelect.getAttribute('data-user-selected') === 'true') {
                return true;
            }
            const photos = block.querySelectorAll('.seizure-photo-item, .seizurePhotoItem');
            if (photos.length > 0) return true;

            return false;
        }

                function resetSeizureItemSection(promptConfirm = false) {
            const container = document.getElementById('seizuresListContainer');
            if (!container) return;

            if (promptConfirm) {
                let hasData = false;
                const blocks = container.querySelectorAll('.seizure-block');
                for (const b of blocks) {
                    if (typeof seizureBlockHasData === 'function' && seizureBlockHasData(b)) {
                        hasData = true;
                        break;
                    }
                }
                const manifestDesc = document.getElementById('manifestDescriptionOfExhibits');
                if (manifestDesc && manifestDesc.value && manifestDesc.value.trim() !== '') {
                    hasData = true;
                }
                if (hasData) {
                    const confirmed = confirm('Are you sure you want to reset the Seizure Item section? All entered seizure information will be cleared.');
                    if (!confirmed) return;
                }
            }

            // Reset count input to 1
            const countInput = document.getElementById('seizureCountInput');
            if (countInput) {
                countInput.value = 1;
            }

            // Clear overall manifest description
            const manifestDesc = document.getElementById('manifestDescriptionOfExhibits');
            if (manifestDesc) {
                manifestDesc.value = '';
            }

            // Remove extra blocks so only 1 remains
            const allBlocks = container.querySelectorAll('.seizure-block');
            for (let i = 1; i < allBlocks.length; i++) {
                allBlocks[i].remove();
            }

            // Reset the remaining first block to clean/pristine state
            const firstBlock = container.querySelector('.seizure-block');
            if (firstBlock) {
                firstBlock.querySelectorAll('input:not([type="checkbox"]):not([type="radio"]), textarea').forEach(input => {
                    input.value = '';
                });
                firstBlock.querySelectorAll('input[type="hidden"]').forEach(input => {
                    input.value = '';
                });
                firstBlock.querySelectorAll('select').forEach(select => {
                    select.selectedIndex = 0;
                });
                firstBlock.querySelector('.seizure-associated-person-select')?.removeAttribute('data-user-selected');
                const photoGrid = firstBlock.querySelector('.seizurePhotosGrid');
                if (photoGrid) { photoGrid.innerHTML = ''; photoGrid.classList.add('hidden'); }
                const photoCount = firstBlock.querySelector('#seizurePhotosCount, .seizure-photos-count');
                if (photoCount) { photoCount.innerText = '0 Photos Uploaded'; photoCount.classList.add('hidden'); }
                const fileInput = firstBlock.querySelector('.fileSeizurePhoto');
                if (fileInput) fileInput.value = '';
                delete firstBlock.dataset.exhibitNumber;
                delete firstBlock.dataset.labExhibitNo;
                delete firstBlock.dataset.linkedExhibitId;
                delete firstBlock.dataset.propertyId;

                // Restore default unit category if defined
                if (window._initialDefaultUnitCategory) {
                    const catInput = firstBlock.querySelector('#newSeizureCategory, .new-seizure-category');
                    const searchInput = firstBlock.querySelector('#exhibitSearchInput, .exhibit-search-input');
                    if (catInput) catInput.value = window._initialDefaultUnitCategory;
                    if (searchInput) searchInput.value = window._initialDefaultUnitCategory;
                    if (typeof updateCommodityField === 'function') {
                        updateCommodityField(window._initialDefaultUnitCategory, firstBlock);
                    }
                }
            }

            if (typeof updateSeizureBlockNumbers === 'function') {
                updateSeizureBlockNumbers();
            }

            if (typeof syncSuspectDropdownsForExhibits === 'function') {
                syncSuspectDropdownsForExhibits();
            }
            if (typeof syncSection4ExhibitsToLabSection === 'function') {
                syncSection4ExhibitsToLabSection();
            }
            if (typeof evaluateLabExaminationVisibility === 'function') {
                evaluateLabExaminationVisibility();
            }
        }

function setSeizureBlocksCount(val) {
            const targetCount = parseInt(val, 10);
            const container = document.getElementById('seizuresListContainer');
            if (!container) return;

            const countInput = document.getElementById('seizureCountInput');

            if (isNaN(targetCount) || targetCount < 1) {
                return;
            }

            const finalTarget = Math.min(Math.max(1, targetCount), 100);
            const currentBlocks = container.querySelectorAll('.seizure-block');
            const currentCount = currentBlocks.length;

            if (finalTarget === currentCount) return;

            if (finalTarget > currentCount) {
                const diff = finalTarget - currentCount;
                for (let i = 0; i < diff; i++) {
                    addSeizureBlock(false);
                }
                updateSeizureBlockNumbers();
                if (typeof syncSuspectDropdownsForExhibits === 'function') {
                    syncSuspectDropdownsForExhibits();
                }
                if (typeof syncSection4ExhibitsToLabSection === 'function') {
                    syncSection4ExhibitsToLabSection();
                }
                if (typeof evaluateLabExaminationVisibility === 'function') {
                    evaluateLabExaminationVisibility();
                }
                if (typeof unlockSection5Form === 'function') {
                    unlockSection5Form();
                }
            } else {
                let hasData = false;
                for (let i = finalTarget; i < currentCount; i++) {
                    if (seizureBlockHasData(currentBlocks[i])) {
                        hasData = true;
                        break;
                    }
                }

                if (hasData) {
                    const confirmed = confirm('Decreasing the number of seized items will remove section(s) with entered data. Are you sure you want to proceed and discard this information?');
                    if (!confirmed) {
                        if (countInput) countInput.value = currentCount;
                        return;
                    }
                }

                for (let i = currentCount - 1; i >= finalTarget; i--) {
                    currentBlocks[i].remove();
                }
                updateSeizureBlockNumbers();
                if (typeof syncSuspectDropdownsForExhibits === 'function') {
                    syncSuspectDropdownsForExhibits();
                }
                if (typeof syncSection4ExhibitsToLabSection === 'function') {
                    syncSection4ExhibitsToLabSection();
                }
                if (typeof evaluateLabExaminationVisibility === 'function') {
                    evaluateLabExaminationVisibility();
                }
                if (typeof unlockSection5Form === 'function') {
                    unlockSection5Form();
                }
            }
        }

        function populateSeizureBlock(block, data) {
            if (!block || !data) return;

            if (data.exhibit_number) {
                block.dataset.exhibitNumber = data.exhibit_number;
            }
            if (data.lab_exhibit_no !== undefined && data.lab_exhibit_no !== null) {
                block.dataset.labExhibitNo = data.lab_exhibit_no;
            }

            const owner = data.resolved_owner || data.associated_person || data.suspect_name || data.owner_name || '';
            const assocSelect = block.querySelector('.seizure-associated-person-select');
            if (assocSelect && owner) {
                let matched = false;
                const ownerLower = owner.toLowerCase().trim();
                for (let i = 0; i < assocSelect.options.length; i++) {
                    const optVal = assocSelect.options[i].value.toLowerCase().trim();
                    const optText = assocSelect.options[i].text.toLowerCase().trim();
                    if (optVal === ownerLower || optText.includes(ownerLower) || (ownerLower === 'all suspects' && optVal.includes('all'))) {
                        assocSelect.selectedIndex = i;
                        assocSelect.setAttribute('data-user-selected', 'true');
                        matched = true;
                        break;
                    }
                }
                if (!matched && owner) {
                    const newOpt = document.createElement('option');
                    newOpt.value = owner;
                    newOpt.text = owner;
                    newOpt.selected = true;
                    assocSelect.appendChild(newOpt);
                    assocSelect.setAttribute('data-user-selected', 'true');
                }
            }

            const cat = data.category || data.exhibit_category || '';
            if (cat) {
                const catInput = block.querySelector('#newSeizureCategory');
                const catSearch = block.querySelector('#exhibitSearchInput');
                if (catInput) catInput.value = cat;
                if (catSearch) catSearch.value = cat;

                if (typeof selectExhibitCategory === 'function') {
                    try {
                        selectExhibitCategory(cat, { target: block });
                    } catch(e) {}
                }
            }

            const name = data.seizure_name || data.commodity_name || data.name || data.seizure_name_drug || '';
            const catLower = (cat || '').toLowerCase();

            if (catLower.includes('drug') || catLower === 'narcotics') {
                const drugHidden = block.querySelector('#newSeizureNameDrug');
                const drugSearch = block.querySelector('#drugSearchInput');
                const drugSelect = block.querySelector('#drugSelect');
                if (drugHidden) drugHidden.value = name;
                if (drugSearch) drugSearch.value = name;
                if (drugSelect) drugSelect.value = name;
            } else if (catLower.includes('weapon') || catLower.includes('firearm')) {
                const wpnHidden = block.querySelector('#newSeizureNameWeapon');
                const wpnSearch = block.querySelector('#weaponSearchInput');
                const wpnSelect = block.querySelector('#weaponSelect');
                if (wpnHidden) wpnHidden.value = name;
                if (wpnSearch) wpnSearch.value = name;
                if (wpnSelect) wpnSelect.value = name;
            } else if (catLower.includes('money') || catLower.includes('currency')) {
                const currHidden = block.querySelector('#newSeizureNameCurrency');
                const currSearch = block.querySelector('#currencySearchInput');
                if (currHidden) currHidden.value = name;
                if (currSearch) currSearch.value = name;
            } else if (catLower.includes('ammo')) {
                const ammoHidden = block.querySelector('#newSeizureNameAmmo');
                const ammoSearch = block.querySelector('#ammoSearchInput');
                if (ammoHidden) ammoHidden.value = name;
                if (ammoSearch) ammoSearch.value = name;
            } else if (catLower.includes('device') || catLower.includes('electronic')) {
                const devHidden = block.querySelector('#newSeizureNameDevice');
                const devSearch = block.querySelector('#deviceSearchInput');
                if (devHidden) devHidden.value = name;
                if (devSearch) devSearch.value = name;
            } else {
                const genInput = block.querySelector('#newSeizureName');
                if (genInput) genInput.value = name;
            }

            const setVal = (sel, v) => {
                if (v !== undefined && v !== null && v !== '') {
                    const el = block.querySelector(sel);
                    if (el) el.value = v;
                }
            };

            setVal('#newSeizureQuantity', data.quantity);
            setVal('#newSeizureContents', data.contents);
            setVal('.seizure-contents-input', data.contents);
            setVal('#newSeizureWeight', data.weight);
            setVal('#newSeizureUnit', data.unit);
            setVal('#unitSearchInput', data.unit);
            setVal('#newSeizurePackaging', data.packaging);
            setVal('#pkgSearchInput', data.packaging);
            setVal('#newSeizureValue', data.value || data.estimated_street_value);
            setVal('#newSeizureConcealment', data.concealment_method || data.concealment);
            setVal('#newSeizureDate', data.seizure_date || data.date);
            setVal('#newSeizureTime', data.seizure_time || data.time);
            setVal('#newSeizureLocation', data.seizure_location || data.location);
            setVal('#newSeizureReason', data.seizure_reason || data.reason);
            setVal('#newSeizureSourceOfExhibits', data.source_of_exhibits || data.source);
            setVal('#newSeizureInvestigatingOfficer', data.investigating_officer || data.investigation_officer);
            setVal('#newSeizureSourceRequestingUnit', data.source_requesting_unit || data.requesting_unit);
            setVal('#newSeizureRemark', data.exhibit_remark || data.remark || data.remarks);

            const officer = data.seizing_officer || data.seizure_officer || data.officer_name || '';
            if (officer) {
                setVal('#newSeizureOfficer', officer);
                setVal('.officerSearchInput', officer);
            }

            setVal('#newDevModel', data.model || data.dev_model);
            setVal('#newDevSerialNo', data.serial_number || data.dev_serial_no);
            setVal('#newDevImei1', data.imei1 || data.dev_imei1);
            setVal('#newDevImei2', data.imei2 || data.dev_imei2);
            setVal('#newDevMacAddress', data.mac_address || data.dev_mac_address);
            setVal('#newDevStorage', data.storage_capacity || data.dev_storage);
            setVal('#newDevColour', data.colour || data.dev_colour);
            setVal('#newDevMarks', data.identifying_marks || data.dev_marks);

            setVal('#wpnModel', data.model || data.weapon_model || data.wpn_model);
            setVal('#wpnSerialNo', data.serial_number || data.weapon_serial_no || data.wpn_serial_no);
            setVal('#wpnCalibre', data.caliber || data.wpn_calibre);
            setVal('#wpnColour', data.colour || data.wpn_colour);
            setVal('#wpnSize', data.size || data.wpn_size);
            setVal('#wpnMarks', data.identifying_marks || data.wpn_marks);
            setVal('#wpnLocationFound', data.location_found || data.wpn_location_found);
            setVal('#wpnLoadedStatus', data.loaded_status || data.wpn_loaded_status);
            setVal('#wpnCondition', data.condition || data.wpn_condition);

            setVal('#vehMake', data.make || data.veh_make);
            setVal('#vehModel', data.model || data.veh_model);
            setVal('#vehType', data.type || data.veh_type);
            setVal('#vehTypeSearchInput', data.type || data.veh_type);
            setVal('#vehYear', data.year || data.veh_year);
            setVal('#vehColour', data.colour || data.veh_colour);
            setVal('#vehRegNo', data.registration_number || data.veh_reg_no);
            setVal('#vehVIN', data.vin || data.veh_vin);
            setVal('#vehEngineNo', data.engine_number || data.veh_engine_no);
            setVal('#vehCountryReg', data.country_registration || data.veh_country_reg);
            setVal('#vehOwner', data.owner || data.veh_owner);
            setVal('#vehDriver', data.driver || data.veh_driver);
            setVal('#vehOwnerContact', data.owner_contact || data.veh_owner_contact);
            setVal('#vehDocsAvailable', data.documents_available || data.veh_docs_available);
            setVal('#vehDocRef', data.document_reference || data.veh_doc_ref);
            setVal('#vehCondition', data.condition || data.veh_condition);
            setVal('#vehFuel', data.fuel_level || data.veh_fuel);
            setVal('#vehKeysRecv', data.keys_received || data.veh_keys_recv);
            setVal('#vehKeyCount', data.key_count || data.veh_key_count);
            setVal('#vehDamage', data.damages_visible || data.veh_damage);
            setVal('#vehMods', data.modifications || data.veh_mods);
            setVal('#vehFeatures', data.features || data.veh_features);

            setVal('#ammoCartridgeAppearance', data.cartridge_appearance || data.ammo_cartridge_appearance);
            setVal('#ammoProjectileType', data.projectile_type || data.ammo_projectile_type);
            setVal('#ammoCaseMaterial', data.case_material || data.ammo_case_material);
            setVal('#ammoColourMarkings', data.colour_markings || data.ammo_colour_markings);
            setVal('#ammoCondition', data.condition || data.ammo_condition);
            setVal('#ammoOtherCondition', data.other_condition || data.ammo_other_condition);
            setVal('#ammoPackagingType', data.packaging_type || data.ammo_packaging_type);
            setVal('#ammoSealCondition', data.seal_condition || data.ammo_seal_condition);
            setVal('#ammoPackageReferenceNumber', data.package_reference_number || data.ammo_package_reference_number);
            setVal('#ammoConditionWhenReceived', data.condition_when_received || data.ammo_condition_when_received);

            const otherDesc = data.other_exhibit_description || data.other_description || '';
            if (otherDesc) {
                setVal('#newOtherExhibitDescription', otherDesc);
                setVal('.other-exhibit-description-input', otherDesc);
                const otherWrap = block.querySelector('#otherExhibitDescriptionWrapper, .other-exhibit-description-wrapper');
                if (otherWrap) otherWrap.classList.remove('hidden');
            }

            const photos = data.photos || data.photo_paths || data.all_photos || [];
            if (Array.isArray(photos) && photos.length > 0) {
                const photoGrid = block.querySelector('.seizurePhotosGrid');
                const photoCount = block.querySelector('#seizurePhotosCount') || block.querySelector('.seizure-photos-count');
                if (photoGrid) {
                    photoGrid.classList.remove('hidden');
                    photos.forEach(photoPath => {
                        if (!photoPath) return;
                        const photoUrl = typeof formatPhotoUrl === 'function' ? formatPhotoUrl(photoPath) : (photoPath.startsWith('http') || photoPath.startsWith('/') ? photoPath : '/storage/' + photoPath);
                        const photoCard = document.createElement('div');
                        photoCard.className = 'seizure-photo-item relative group rounded-xl overflow-hidden border border-slate-200 aspect-square bg-slate-100 shadow-2xs';
                        photoCard.innerHTML = '<img src="' + photoUrl + '" class="w-full h-full object-cover">' +
                            '<input type="hidden" name="existing_photos[]" value="' + photoPath + '">' +
                            '<div class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1.5 p-2">' +
                            '<button type="button" onclick="window.open(\'' + photoUrl + '\', \'_blank\')" class="px-2 py-1 bg-white/90 hover:bg-white text-slate-800 text-[9px] font-bold rounded-lg shadow-xs cursor-pointer border-none">View</button>' +
                            '<button type="button" onclick="this.closest(\'.seizure-photo-item\').remove(); if(typeof updateSeizurePhotosCount === \'function\') updateSeizurePhotosCount(this.closest(\'.seizure-block\') || document);" class="px-2 py-1 bg-red-600 hover:bg-red-700 text-white text-[9px] font-bold rounded-lg shadow-xs cursor-pointer border-none">Remove</button>' +
                            '</div>';
                        photoGrid.appendChild(photoCard);
                    });
                    if (photoCount && typeof updateSeizurePhotosCount === 'function') {
                        updateSeizurePhotosCount(block);
                    }
                }
            }
        }


        function populateExistingManifestSections(savedExhibits) {
            if (!savedExhibits || !Array.isArray(savedExhibits) || savedExhibits.length === 0) return;
            const container = document.getElementById('seizuresListContainer');
            if (!container) return;

            getPristineSeizureBlockTemplate();

            const countInput = document.getElementById('seizureCountInput');
            if (countInput) {
                countInput.value = savedExhibits.length;
            }

            let currentBlocks = container.querySelectorAll('.seizure-block');
            while (currentBlocks.length < savedExhibits.length) {
                addSeizureBlock(false);
                currentBlocks = container.querySelectorAll('.seizure-block');
            }
            while (currentBlocks.length > savedExhibits.length) {
                currentBlocks[currentBlocks.length - 1].remove();
                currentBlocks = container.querySelectorAll('.seizure-block');
            }

            currentBlocks.forEach((block, idx) => {
                if (savedExhibits[idx]) {
                    populateSeizureBlock(block, savedExhibits[idx]);
                }
            });

            const manifestDescInput = document.getElementById('manifestDescriptionOfExhibits');
            if (manifestDescInput && !manifestDescInput.value) {
                const firstExhWithDesc = savedExhibits.find(e => e && e.description_of_exhibits);
                if (firstExhWithDesc && firstExhWithDesc.description_of_exhibits) {
                    manifestDescInput.value = firstExhWithDesc.description_of_exhibits;
                }
            }

            updateSeizureBlockNumbers();
            if (typeof syncSuspectDropdownsForExhibits === 'function') {
                syncSuspectDropdownsForExhibits();
            }
            if (typeof syncSection4ExhibitsToLabSection === 'function') {
                syncSection4ExhibitsToLabSection();
            }
            if (typeof evaluateLabExaminationVisibility === 'function') {
                evaluateLabExaminationVisibility();
            }
        }

        function addSeizureBlock(shouldUpdate = true) {
            const container = document.getElementById('seizuresListContainer');
            if (!container) return;

            const tpl = getPristineSeizureBlockTemplate();
            const firstBlock = container.querySelector('.seizure-block');
            if (!tpl && !firstBlock) return;

            const clone = tpl ? tpl.cloneNode(true) : firstBlock.cloneNode(true);
            const newIndex = container.querySelectorAll('.seizure-block').length + 1;

            clone.setAttribute('data-seizure-index', newIndex);
            clone.dataset.hasSavedLab = 'false';

            const titleEl = clone.querySelector('.seizure-title');
            if (titleEl) titleEl.innerText = 'Seized Item ' + newIndex;
            const numberBadge = clone.querySelector('.seizure-number-badge');
            if (numberBadge) numberBadge.innerText = newIndex;

            // Re-enable all inputs inside the clone
            clone.querySelectorAll('input, select, textarea, button').forEach(el => {
                el.disabled = false;
            });

            clone.querySelectorAll('input[type="text"], input[type="number"], input[type="date"], input[type="time"], textarea').forEach(input => {
                input.value = '';
            });
            clone.querySelectorAll('input[type="hidden"]').forEach(input => {
                input.value = '';
            });
            clone.querySelectorAll('select').forEach(select => {
                select.selectedIndex = 0;
            });
            clone.querySelector('.seizure-associated-person-select')?.removeAttribute('data-user-selected');

            const wrappers = [
                '#drugSelectWrapper', '#weaponSelectWrapper', '#currencySelectWrapper',
                '#ammoSelectWrapper', '#deviceSelectWrapper', '#deviceFieldsWrapper',
                '#vehicleFieldsWrapper', '#weaponFieldsWrapper', '#ammoFieldsWrapper',
                '#otherExhibitDescriptionWrapper', '.other-exhibit-description-wrapper'
            ];
            wrappers.forEach(w => {
                const el = clone.querySelector(w);
                if (el) el.classList.add('hidden');
            });

            clone.querySelectorAll('#exhibitListPanel, #drugListPanel, #weaponListPanel, #currencyListPanel, #ammoListPanel, #deviceListPanel, #pkgListPanel, #unitListPanel').forEach(panel => {
                panel.classList.add('hidden');
            });

            const commodityTextWrapper = clone.querySelector('#commodityTextWrapper');
            if (commodityTextWrapper) commodityTextWrapper.classList.remove('hidden');

            const clonePhotoGrid = clone.querySelector('.seizurePhotosGrid');
            if (clonePhotoGrid) {
                clonePhotoGrid.innerHTML = '';
                clonePhotoGrid.classList.add('hidden');
            }
            const clonePhotoCount = clone.querySelector('#seizurePhotosCount') || clone.querySelector('.seizure-photos-count');
            if (clonePhotoCount) {
                clonePhotoCount.innerText = '0 Photos Uploaded';
                clonePhotoCount.classList.add('hidden');
            }
            const cloneFileInput = clone.querySelector('.fileSeizurePhoto');
            if (cloneFileInput) cloneFileInput.value = '';

            container.appendChild(clone);

            const defaultCat = window._initialDefaultUnitCategory || 'Drugs';
            const cloneCatIn = clone.querySelector('#newSeizureCategory');
            const cloneCatDis = clone.querySelector('#exhibitSearchInput');
            if (cloneCatIn) cloneCatIn.value = defaultCat;
            if (cloneCatDis) cloneCatDis.value = defaultCat;
            if (typeof updateCommodityField === 'function') {
                updateCommodityField(defaultCat, clone);
            }

            if (shouldUpdate) {
                updateSeizureBlockNumbers();
                if (typeof syncSuspectDropdownsForExhibits === 'function') {
                    syncSuspectDropdownsForExhibits();
                }
                if (typeof syncSection4ExhibitsToLabSection === 'function') {
                    syncSection4ExhibitsToLabSection();
                }
                if (typeof evaluateLabExaminationVisibility === 'function') {
                    evaluateLabExaminationVisibility();
                }
                if (typeof unlockSection5Form === 'function') {
                    unlockSection5Form();
                }
            }
        }

        function removeSeizureBlock(btn) {
            const block = btn ? btn.closest('.seizure-block') : null;
            const container = document.getElementById('seizuresListContainer');
            if (!block || !container || container.querySelectorAll('.seizure-block').length <= 1) return;

            if (seizureBlockHasData(block)) {
                const confirmed = confirm('This seized item section contains entered information. Are you sure you want to remove it?');
                if (!confirmed) return;
            }

            block.remove();
            updateSeizureBlockNumbers();
            if (typeof syncSuspectDropdownsForExhibits === 'function') {
                syncSuspectDropdownsForExhibits();
            }
            if (typeof syncSection4ExhibitsToLabSection === 'function') {
                syncSection4ExhibitsToLabSection();
            }
            if (typeof evaluateLabExaminationVisibility === 'function') {
                evaluateLabExaminationVisibility();
            }
        }

        function updateSeizureBlockNumbers() {
            const container = document.getElementById('seizuresListContainer');
            const blocks = container ? container.querySelectorAll('.seizure-block') : document.querySelectorAll('.seizure-block');
            blocks.forEach((block, index) => {
                const num = index + 1;
                block.setAttribute('data-seizure-index', num);
                const titleEl = block.querySelector('.seizure-title');
                const numberBadge = block.querySelector('.seizure-number-badge');
                if (titleEl) titleEl.innerText = 'Seized Item ' + num;
                if (numberBadge) numberBadge.innerText = num;

                const removeBtn = block.querySelector('.remove-seizure-btn');
                if (removeBtn) {
                    if (blocks.length > 1) {
                        removeBtn.classList.remove('hidden');
                    } else {
                        removeBtn.classList.add('hidden');
                    }
                }
            });

            const countInput = document.getElementById('seizureCountInput');
            if (countInput && parseInt(countInput.value, 10) !== blocks.length) {
                countInput.value = blocks.length;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (!window._initialDefaultUnitCategory) {
                const initialCatEl = document.getElementById('newSeizureCategory');
                const initialSearchEl = document.getElementById('exhibitSearchInput');
                window._initialDefaultUnitCategory = (initialCatEl?.value || initialSearchEl?.value || '').trim();
            }
            getPristineSeizureBlockTemplate();
            // Default Seizure Item section to 1 clean item for recording new exhibits
            if (typeof resetSeizureItemSection === 'function') {
                resetSeizureItemSection(false);
            }
        });


        function openSeizurePanel(el, panelSelector) {
            if (el && el.removeAttribute) el.removeAttribute('readonly');
            const block = el ? el.closest('.seizure-block') : null;
            if (block) {
                document.querySelectorAll('.seizure-block').forEach(b => b.style.zIndex = '1');
                block.style.zIndex = '99999';
            }
            const panel = block ? block.querySelector(panelSelector) : document.querySelector(panelSelector);
            if (panel) panel.classList.remove('hidden');
        }

        function openExhibitListPanel(el) {
            openSeizurePanel(el, '#exhibitListPanel');
        }

        function openDrugListPanel(el) {
            openSeizurePanel(el, '#drugListPanel');
        }

        function openWeaponListPanel(el) {
            openSeizurePanel(el, '#weaponListPanel');
        }

        function openCurrencyListPanel(el) {
            openSeizurePanel(el, '#currencyListPanel');
        }

        function openAmmoListPanel(el) {
            openSeizurePanel(el, '#ammoListPanel');
        }

        function openDeviceListPanel(el) {
            openSeizurePanel(el, '#deviceListPanel');
        }

        function openPkgListPanel(el) {
            openSeizurePanel(el, '#pkgListPanel');
        }

        function openUnitListPanel(el) {
            openSeizurePanel(el, '#unitListPanel');
        }

        function openVehTypeListPanel(el) {
            openSeizurePanel(el, '#vehTypeListPanel');
        }

        function selectVehType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root = getSeizureBlockFromEvent(e);
            const hidden = root.querySelector('#vehType');
            const display = root.querySelector('#vehTypeSearchInput');
            const panel = root.querySelector('#vehTypeListPanel');
            if (hidden) hidden.value = val;
            if (display) {
                display.value = val;
                display.removeAttribute('readonly');
            }
            if (panel) panel.classList.add('hidden');
        }

        function filterVehTypeList(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#vehTypeSearchInput')?.value || '';
            const hidden = root.querySelector('#vehType');
            if (hidden) hidden.value = rawVal;
            const q = rawVal.toLowerCase();
            filterVehTypeListByQuery(q, root);
        }

        function filterVehTypeListInner(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#vehTypeSearchInner')?.value || '';
            const hidden = root.querySelector('#vehType');
            if (hidden) hidden.value = rawVal;
            const display = root.querySelector('#vehTypeSearchInput');
            if (display) display.value = rawVal;
            const q = rawVal.toLowerCase();
            filterVehTypeListByQuery(q, root);
        }

        function filterVehTypeListByQuery(q, rootEl) {
            const root = rootEl || document;
            const list = root.querySelector('#vehTypeList');
            if (!list) return;

            let customLi = list.querySelector('#vehTypeCustomItem');
            const trimmed = (q || '').trim();
            if (trimmed.length > 0) {
                if (!customLi) {
                    customLi = document.createElement('li');
                    customLi.id = 'vehTypeCustomItem';
                    customLi.className = 'px-1.5 py-1 mb-1 border-b border-emerald-100';
                    list.insertBefore(customLi, list.firstChild);
                }
                const safeVal = trimmed.replace(/'/g, "\\'");
                customLi.innerHTML = `
                <button type="button" onclick="selectVehType('${safeVal}', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-xl flex items-center justify-between transition-colors border border-emerald-200 cursor-pointer">
                    <span>+ Use Custom Vehicle Type: "${trimmed}"</span>
                    <span class="text-[10px] font-mono-code bg-emerald-200 text-emerald-900 px-1.5 py-0.5 rounded font-bold">CUSTOM</span>
                </button>
            `;
                customLi.style.display = '';
            } else if (customLi) {
                customLi.style.display = 'none';
            }

            const items = list.querySelectorAll('.veh-type-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        function selectExhibitCategory(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root = getSeizureBlockFromEvent(e);
            const input = root.querySelector('#newSeizureCategory');
            const display = root.querySelector('#exhibitSearchInput');
            const panel = root.querySelector('#exhibitListPanel');
            if (input) input.value = val;
            if (display) {
                display.value = val;
                display.removeAttribute('readonly');
            }
            if (panel) panel.classList.add('hidden');
            updateCommodityField(val, root);

            const exhTypeSelect = root.querySelector('.exhibit-type-select');
            const drugClsSelect = root.querySelector('.drug-classification-select');
            if (exhTypeSelect && !exhTypeSelect.value) {
                exhTypeSelect.value = (val === 'Drugs') ? 'Drug Exhibit' : 'Non-Drug Exhibit';
            }
            if (drugClsSelect && val !== 'Drugs' && (!drugClsSelect.value || drugClsSelect.value === 'N/A')) {
                drugClsSelect.value = 'N/A';
            }

            evaluateLabExaminationVisibility();

            onDrugTypeChange(null);
        }

        function filterExhibitList(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#exhibitSearchInput')?.value || '';
            const categoryInput = root.querySelector('#newSeizureCategory');
            if (categoryInput) categoryInput.value = rawVal;
            filterExhibitListByQuery(rawVal.toLowerCase(), root);
            evaluateLabExaminationVisibility();
        }

        function filterExhibitListInner(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#exhibitSearchInner')?.value || '';
            const categoryInput = root.querySelector('#newSeizureCategory');
            if (categoryInput) categoryInput.value = rawVal;
            filterExhibitListByQuery(rawVal.toLowerCase(), root);
            evaluateLabExaminationVisibility();
        }

        function filterExhibitListByQuery(q, rootEl) {
            const root = rootEl || document;
            const list = root.querySelector('#exhibitList');
            if (!list) return;

            let customLi = list.querySelector('#exhibitCustomItem');
            const trimmed = (q || '').trim();
            if (trimmed.length > 0) {
                if (!customLi) {
                    customLi = document.createElement('li');
                    customLi.id = 'exhibitCustomItem';
                    customLi.className = 'px-1.5 py-1 mb-1 border-b border-emerald-100';
                    list.insertBefore(customLi, list.firstChild);
                }
                const safeVal = trimmed.replace(/'/g, "\\'");
                customLi.innerHTML = `
                <button type="button" onclick="selectExhibitCategory('${safeVal}', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-xl flex items-center justify-between transition-colors border border-emerald-200 cursor-pointer">
                    <span>+ Use Custom Category: "${trimmed}"</span>
                    <span class="text-[10px] font-mono-code bg-emerald-200 text-emerald-900 px-1.5 py-0.5 rounded font-bold">CUSTOM</span>
                </button>
            `;
                customLi.style.display = '';
            } else if (customLi) {
                customLi.style.display = 'none';
            }

            const items = list.querySelectorAll('.exhibit-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        function updateCommodityField(category, rootEl) {
            const root = rootEl || document.querySelector('.seizure-block') || document;
            const textWrapper = root.querySelector('#commodityTextWrapper');
            const drugWrapper = root.querySelector('#drugSelectWrapper');
            const weaponWrapper = root.querySelector('#weaponSelectWrapper');
            const currencyWrapper = root.querySelector('#currencySelectWrapper');
            const ammoWrapper = root.querySelector('#ammoSelectWrapper');
            const deviceWrapper = root.querySelector('#deviceSelectWrapper');
            const deviceFieldsWrapper = root.querySelector('#deviceFieldsWrapper');
            const vehicleFieldsWrapper = root.querySelector('#vehicleFieldsWrapper');
            const weaponFieldsWrapper = root.querySelector('#weaponFieldsWrapper');
            const ammoFieldsWrapper = root.querySelector('#ammoFieldsWrapper');
            const otherDescriptionWrapper = root.querySelector('#otherExhibitDescriptionWrapper') || root.querySelector('.other-exhibit-description-wrapper');
            const otherDescriptionInput = root.querySelector('#newOtherExhibitDescription') || root.querySelector('.other-exhibit-description-input');
            const textInput = root.querySelector('#newSeizureName');
            const quantityLabel = root.querySelector('#quantityLabel');

            if (textWrapper) textWrapper.classList.add('hidden');
            if (drugWrapper) drugWrapper.classList.add('hidden');
            if (weaponWrapper) weaponWrapper.classList.add('hidden');
            if (currencyWrapper) currencyWrapper.classList.add('hidden');
            if (ammoWrapper) ammoWrapper.classList.add('hidden');
            if (deviceWrapper) deviceWrapper.classList.add('hidden');
            if (deviceFieldsWrapper) deviceFieldsWrapper.classList.add('hidden');
            if (vehicleFieldsWrapper) vehicleFieldsWrapper.classList.add('hidden');
            if (weaponFieldsWrapper) weaponFieldsWrapper.classList.add('hidden');
            if (ammoFieldsWrapper) ammoFieldsWrapper.classList.add('hidden');
            if (otherDescriptionWrapper) otherDescriptionWrapper.classList.add('hidden');
            if (otherDescriptionInput) otherDescriptionInput.removeAttribute('required');

            const catNorm = typeof category === 'string' ? category.toLowerCase().trim() : '';

            if (category === 'Drugs') {
                if (quantityLabel) quantityLabel.innerText = 'Quantity';
                if (drugWrapper) drugWrapper.classList.remove('hidden');
            } else if (category === 'Weapons' || category === 'Weapon' || catNorm === 'weapons' || catNorm === 'weapon' || catNorm === 'firearms' || catNorm === 'firearm') {
                if (quantityLabel) quantityLabel.innerText = 'Quantity';
                if (weaponWrapper) weaponWrapper.classList.remove('hidden');
                if (weaponFieldsWrapper) weaponFieldsWrapper.classList.remove('hidden');
            } else if (category === 'Vehicles' || category === 'Vehicle' || catNorm === 'vehicles' || catNorm === 'vehicle') {
                if (quantityLabel) quantityLabel.innerText = 'Quantity / Units';
                if (textWrapper) textWrapper.classList.remove('hidden');
                if (vehicleFieldsWrapper) vehicleFieldsWrapper.classList.remove('hidden');
                if (textInput) textInput.placeholder = 'e.g. Toyota Hilux (Unregistered)';
            } else if (category === 'Money') {
                if (quantityLabel) quantityLabel.innerText = 'Amount';
                if (currencyWrapper) currencyWrapper.classList.remove('hidden');
            } else if (category === 'Ammunition' || catNorm === 'ammunition' || catNorm === 'ammo') {
                if (quantityLabel) quantityLabel.innerText = 'Quantity / Rounds';
                if (ammoWrapper) ammoWrapper.classList.remove('hidden');
                if (ammoFieldsWrapper) ammoFieldsWrapper.classList.remove('hidden');
            } else if (category === 'Electronic Devices') {
                if (quantityLabel) quantityLabel.innerText = 'Quantity / Units';
                if (deviceWrapper) deviceWrapper.classList.remove('hidden');
                if (deviceFieldsWrapper) deviceFieldsWrapper.classList.remove('hidden');
                if (textWrapper) textWrapper.classList.remove('hidden');
            } else if (category === 'Other Exhibits' || category === 'Other' || category === 'Other Exhibit' || catNorm === 'other exhibits' || catNorm === 'other exhibit' || catNorm === 'other' || catNorm.includes('other')) {
                if (quantityLabel) quantityLabel.innerText = 'Quantity';
                if (textWrapper) textWrapper.classList.remove('hidden');
                if (textInput) textInput.placeholder = 'e.g. Hardware Devices / Illegal Tools';
                if (otherDescriptionWrapper) {
                    otherDescriptionWrapper.classList.remove('hidden');
                    if (otherDescriptionInput) otherDescriptionInput.setAttribute('required', 'required');
                }
            } else {
                if (quantityLabel) quantityLabel.innerText = 'Quantity';
                if (textWrapper) textWrapper.classList.remove('hidden');
            }

            evaluateLabExaminationVisibility();
        }

        function selectDeviceType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root = getSeizureBlockFromEvent(e);
            const hidden = root.querySelector('#newSeizureNameDevice');
            const display = root.querySelector('#deviceSearchInput');
            const panel = root.querySelector('#deviceListPanel');
            if (hidden) hidden.value = val;
            if (display) display.value = val;
            if (panel) panel.classList.add('hidden');
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = val;
        }

        function filterDeviceList(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#deviceSearchInput')?.value || '';
            const hidden = root.querySelector('#newSeizureNameDevice');
            if (hidden) hidden.value = rawVal;
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = rawVal;
            filterDeviceListByQuery(rawVal.toLowerCase(), root);
        }

        function filterDeviceListInner(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#deviceSearchInner')?.value || '';
            const hidden = root.querySelector('#newSeizureNameDevice');
            if (hidden) hidden.value = rawVal;
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = rawVal;
            filterDeviceListByQuery(rawVal.toLowerCase(), root);
        }

        function filterDeviceListByQuery(q, rootEl) {
            const root = rootEl || document;
            const list = root.querySelector('#deviceList');
            if (!list) return;

            let customLi = list.querySelector('#deviceCustomItem');
            const trimmed = (q || '').trim();
            if (trimmed.length > 0) {
                if (!customLi) {
                    customLi = document.createElement('li');
                    customLi.id = 'deviceCustomItem';
                    customLi.className = 'px-1.5 py-1 mb-1 border-b border-emerald-100';
                    list.insertBefore(customLi, list.firstChild);
                }
                const safeVal = trimmed.replace(/'/g, "\\'");
                customLi.innerHTML = `
                <button type="button" onclick="selectDeviceType('${safeVal}', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-xl flex items-center justify-between transition-colors border border-emerald-200 cursor-pointer">
                    <span>+ Use Custom Entry: "${trimmed}"</span>
                    <span class="text-[10px] font-mono-code bg-emerald-200 text-emerald-900 px-1.5 py-0.5 rounded font-bold">CUSTOM</span>
                </button>
            `;
                customLi.style.display = '';
            } else if (customLi) {
                customLi.style.display = 'none';
            }

            const items = list.querySelectorAll('.device-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        function selectDrugType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root = getSeizureBlockFromEvent(e);
            const hidden = root.querySelector('#newSeizureNameDrug');
            const display = root.querySelector('#drugSearchInput');
            const panel = root.querySelector('#drugListPanel');
            if (hidden) hidden.value = val;
            if (display) display.value = val;
            if (panel) panel.classList.add('hidden');
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = val;

            if (typeof syncSection4ExhibitsToLabSection === 'function') {
                syncSection4ExhibitsToLabSection();
            }
            if (typeof syncSection4ToSection5InReview === 'function') {
                syncSection4ToSection5InReview();
            }
            if (typeof onDrugTypeChange === 'function') {
                onDrugTypeChange(null);
            }
        }

        function filterDrugList(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#drugSearchInput')?.value || '';
            const hidden = root.querySelector('#newSeizureNameDrug');
            if (hidden) hidden.value = rawVal;
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = rawVal;
            filterDrugListByQuery(rawVal.toLowerCase(), root);
            if (typeof syncSection4ExhibitsToLabSection === 'function') {
                syncSection4ExhibitsToLabSection();
            }
            if (typeof syncSection4ToSection5InReview === 'function') {
                syncSection4ToSection5InReview();
            }
            if (typeof onDrugTypeChange === 'function') {
                onDrugTypeChange(null);
            }
        }

        function filterDrugListInner(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#drugSearchInner')?.value || '';
            const hidden = root.querySelector('#newSeizureNameDrug');
            if (hidden) hidden.value = rawVal;
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = rawVal;
            const display = root.querySelector('#drugSearchInput');
            if (display) display.value = rawVal;
            filterDrugListByQuery(rawVal.toLowerCase(), root);
            if (typeof syncSection4ExhibitsToLabSection === 'function') {
                syncSection4ExhibitsToLabSection();
            }
            if (typeof syncSection4ToSection5InReview === 'function') {
                syncSection4ToSection5InReview();
            }
            if (typeof onDrugTypeChange === 'function') {
                onDrugTypeChange(null);
            }
        }

        function filterDrugListByQuery(q, rootEl) {
            const root = rootEl || document;
            const list = root.querySelector('#drugList');
            if (!list) return;

            let customLi = list.querySelector('#drugCustomItem');
            const trimmed = (q || '').trim();
            if (trimmed.length > 0) {
                if (!customLi) {
                    customLi = document.createElement('li');
                    customLi.id = 'drugCustomItem';
                    customLi.className = 'px-1.5 py-1 mb-1 border-b border-emerald-100';
                    list.insertBefore(customLi, list.firstChild);
                }
                const safeVal = trimmed.replace(/'/g, "\\'");
                customLi.innerHTML = `
                <button type="button" onclick="selectDrugType('${safeVal}', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-xl flex items-center justify-between transition-colors border border-emerald-200 cursor-pointer">
                    <span>+ Use Custom Entry: "${trimmed}"</span>
                    <span class="text-[10px] font-mono-code bg-emerald-200 text-emerald-900 px-1.5 py-0.5 rounded font-bold">CUSTOM</span>
                </button>
            `;
                customLi.style.display = '';
            } else if (customLi) {
                customLi.style.display = 'none';
            }

            const items = list.querySelectorAll('.drug-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        function selectWeaponType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root = getSeizureBlockFromEvent(e);
            const hidden = root.querySelector('#newSeizureNameWeapon');
            const display = root.querySelector('#weaponSearchInput');
            const panel = root.querySelector('#weaponListPanel');
            if (hidden) hidden.value = val;
            if (display) display.value = val;
            if (panel) panel.classList.add('hidden');
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = val;
        }

        function filterWeaponList(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#weaponSearchInput')?.value || '';
            const hidden = root.querySelector('#newSeizureNameWeapon');
            if (hidden) hidden.value = rawVal;
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = rawVal;
            filterWeaponListByQuery(rawVal.toLowerCase(), root);
        }

        function filterWeaponListInner(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#weaponSearchInner')?.value || '';
            const hidden = root.querySelector('#newSeizureNameWeapon');
            if (hidden) hidden.value = rawVal;
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = rawVal;
            filterWeaponListByQuery(rawVal.toLowerCase(), root);
        }

        function filterWeaponListByQuery(q, rootEl) {
            const root = rootEl || document;
            const list = root.querySelector('#weaponList');
            if (!list) return;

            let customLi = list.querySelector('#weaponCustomItem');
            const trimmed = (q || '').trim();
            if (trimmed.length > 0) {
                if (!customLi) {
                    customLi = document.createElement('li');
                    customLi.id = 'weaponCustomItem';
                    customLi.className = 'px-1.5 py-1 mb-1 border-b border-emerald-100';
                    list.insertBefore(customLi, list.firstChild);
                }
                const safeVal = trimmed.replace(/'/g, "\\'");
                customLi.innerHTML = `
                <button type="button" onclick="selectWeaponType('${safeVal}', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-xl flex items-center justify-between transition-colors border border-emerald-200 cursor-pointer">
                    <span>+ Use Custom Entry: "${trimmed}"</span>
                    <span class="text-[10px] font-mono-code bg-emerald-200 text-emerald-900 px-1.5 py-0.5 rounded font-bold">CUSTOM</span>
                </button>
            `;
                customLi.style.display = '';
            } else if (customLi) {
                customLi.style.display = 'none';
            }

            const items = list.querySelectorAll('.weapon-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        function selectCurrencyType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root = getSeizureBlockFromEvent(e);
            const hidden = root.querySelector('#newSeizureNameCurrency');
            const display = root.querySelector('#currencySearchInput');
            const panel = root.querySelector('#currencyListPanel');
            if (hidden) hidden.value = val;
            if (display) display.value = val;
            if (panel) panel.classList.add('hidden');
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = val;
        }

        function filterCurrencyList(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#currencySearchInput')?.value || '';
            const hidden = root.querySelector('#newSeizureNameCurrency');
            if (hidden) hidden.value = rawVal;
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = rawVal;
            filterCurrencyListByQuery(rawVal.toLowerCase(), root);
        }

        function filterCurrencyListInner(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#currencySearchInner')?.value || '';
            const hidden = root.querySelector('#newSeizureNameCurrency');
            if (hidden) hidden.value = rawVal;
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = rawVal;
            filterCurrencyListByQuery(rawVal.toLowerCase(), root);
        }

        function filterCurrencyListByQuery(q, rootEl) {
            const root = rootEl || document;
            const list = root.querySelector('#currencyList');
            if (!list) return;

            let customLi = list.querySelector('#currencyCustomItem');
            const trimmed = (q || '').trim();
            if (trimmed.length > 0) {
                if (!customLi) {
                    customLi = document.createElement('li');
                    customLi.id = 'currencyCustomItem';
                    customLi.className = 'px-1.5 py-1 mb-1 border-b border-emerald-100';
                    list.insertBefore(customLi, list.firstChild);
                }
                const safeVal = trimmed.replace(/'/g, "\\'");
                customLi.innerHTML = `
                <button type="button" onclick="selectCurrencyType('${safeVal}', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-xl flex items-center justify-between transition-colors border border-emerald-200 cursor-pointer">
                    <span>+ Use Custom Entry: "${trimmed}"</span>
                    <span class="text-[10px] font-mono-code bg-emerald-200 text-emerald-900 px-1.5 py-0.5 rounded font-bold">CUSTOM</span>
                </button>
            `;
                customLi.style.display = '';
            } else if (customLi) {
                customLi.style.display = 'none';
            }

            const items = list.querySelectorAll('.currency-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        function selectAmmoType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root = getSeizureBlockFromEvent(e);
            const hidden = root.querySelector('#newSeizureNameAmmo');
            const display = root.querySelector('#ammoSearchInput');
            const panel = root.querySelector('#ammoListPanel');
            if (hidden) hidden.value = val;
            if (display) display.value = val;
            if (panel) panel.classList.add('hidden');
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = val;
            if (typeof syncSection4ToSection5InReview === 'function') syncSection4ToSection5InReview();
        }

        function filterAmmoList(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#ammoSearchInput')?.value || '';
            const hidden = root.querySelector('#newSeizureNameAmmo');
            if (hidden) hidden.value = rawVal;
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = rawVal;
            filterAmmoListByQuery(rawVal.toLowerCase(), root);
        }

        function filterAmmoListInner(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#ammoSearchInner')?.value || '';
            const hidden = root.querySelector('#newSeizureNameAmmo');
            if (hidden) hidden.value = rawVal;
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = rawVal;
            filterAmmoListByQuery(rawVal.toLowerCase(), root);
        }

        function filterAmmoListByQuery(q, rootEl) {
            const root = rootEl || document;
            const list = root.querySelector('#ammoList');
            if (!list) return;

            let customLi = list.querySelector('#ammoCustomItem');
            const trimmed = (q || '').trim();
            if (trimmed.length > 0) {
                if (!customLi) {
                    customLi = document.createElement('li');
                    customLi.id = 'ammoCustomItem';
                    customLi.className = 'px-1.5 py-1 mb-1 border-b border-emerald-100';
                    list.insertBefore(customLi, list.firstChild);
                }
                const safeVal = trimmed.replace(/'/g, "\\'");
                customLi.innerHTML = `
                <button type="button" onclick="selectAmmoType('${safeVal}', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-xl flex items-center justify-between transition-colors border border-emerald-200 cursor-pointer">
                    <span>+ Use Custom Entry: "${trimmed}"</span>
                    <span class="text-[10px] font-mono-code bg-emerald-200 text-emerald-900 px-1.5 py-0.5 rounded font-bold">CUSTOM</span>
                </button>
            `;
                customLi.style.display = '';
            } else if (customLi) {
                customLi.style.display = 'none';
            }

            const items = list.querySelectorAll('.ammo-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        function selectPackagingType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root = getSeizureBlockFromEvent(e);
            const hidden = root.querySelector('#newSeizurePackaging');
            const display = root.querySelector('#pkgSearchInput');
            const panel = root.querySelector('#pkgListPanel');
            if (hidden) hidden.value = val;
            if (display) display.value = val;
            if (panel) panel.classList.add('hidden');
            if (typeof syncSection4ToSection5InReview === 'function') syncSection4ToSection5InReview();
        }

        function filterPackagingList(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#pkgSearchInput')?.value || '';
            const hidden = root.querySelector('#newSeizurePackaging');
            if (hidden) hidden.value = rawVal;
            filterPackagingListByQuery(rawVal.toLowerCase(), root);
        }

        function filterPackagingListInner(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#pkgSearchInner')?.value || '';
            const hidden = root.querySelector('#newSeizurePackaging');
            if (hidden) hidden.value = rawVal;
            filterPackagingListByQuery(rawVal.toLowerCase(), root);
        }

        function filterPackagingListByQuery(q, rootEl) {
            const root = rootEl || document;
            const list = root.querySelector('#pkgList');
            if (!list) return;

            let customLi = list.querySelector('#pkgCustomItem');
            const trimmed = (q || '').trim();
            if (trimmed.length > 0) {
                if (!customLi) {
                    customLi = document.createElement('li');
                    customLi.id = 'pkgCustomItem';
                    customLi.className = 'px-1.5 py-1 mb-1 border-b border-emerald-100';
                    list.insertBefore(customLi, list.firstChild);
                }
                const safeVal = trimmed.replace(/'/g, "\\'");
                customLi.innerHTML = `
                <button type="button" onclick="selectPackagingType('${safeVal}', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-xl flex items-center justify-between transition-colors border border-emerald-200 cursor-pointer">
                    <span>+ Use Custom Entry: "${trimmed}"</span>
                    <span class="text-[10px] font-mono-code bg-emerald-200 text-emerald-900 px-1.5 py-0.5 rounded font-bold">CUSTOM</span>
                </button>
            `;
                customLi.style.display = '';
            } else if (customLi) {
                customLi.style.display = 'none';
            }

            const items = list.querySelectorAll('.pkg-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        function selectUnit(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root = getSeizureBlockFromEvent(e);
            const hidden = root.querySelector('#newSeizureUnit');
            const display = root.querySelector('#unitSearchInput');
            const panel = root.querySelector('#unitListPanel');
            if (hidden) hidden.value = val;
            if (display) display.value = val;
            if (panel) panel.classList.add('hidden');
            if (typeof syncSection4ToSection5InReview === 'function') syncSection4ToSection5InReview();
        }

        function filterUnitList(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#unitSearchInput')?.value || '';
            const hidden = root.querySelector('#newSeizureUnit');
            if (hidden) hidden.value = rawVal;
            filterUnitListByQuery(rawVal.toLowerCase(), root);
        }

        function filterUnitListInner(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#unitSearchInner')?.value || '';
            const hidden = root.querySelector('#newSeizureUnit');
            if (hidden) hidden.value = rawVal;
            filterUnitListByQuery(rawVal.toLowerCase(), root);
        }

        function filterUnitListByQuery(q, rootEl) {
            const root = rootEl || document;
            const list = root.querySelector('#unitList');
            if (!list) return;

            let customLi = list.querySelector('#unitCustomItem');
            const trimmed = (q || '').trim();
            if (trimmed.length > 0) {
                if (!customLi) {
                    customLi = document.createElement('li');
                    customLi.id = 'unitCustomItem';
                    customLi.className = 'px-1.5 py-1 mb-1 border-b border-emerald-100';
                    list.insertBefore(customLi, list.firstChild);
                }
                const safeVal = trimmed.replace(/'/g, "\\'");
                customLi.innerHTML = `
                <button type="button" onclick="selectUnit('${safeVal}', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-xl flex items-center justify-between transition-colors border border-emerald-200 cursor-pointer">
                    <span>+ Use Custom Entry: "${trimmed}"</span>
                    <span class="text-[10px] font-mono-code bg-emerald-200 text-emerald-900 px-1.5 py-0.5 rounded font-bold">CUSTOM</span>
                </button>
            `;
                customLi.style.display = '';
            } else if (customLi) {
                customLi.style.display = 'none';
            }

            const items = list.querySelectorAll('.unit-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        function toggleAmmoOtherCondition(el) {
            const root = el ? (el.closest('.seizure-block') || document) : document;
            const wrapper = root.querySelector('#ammoOtherConditionWrapper');
            if (wrapper) {
                if (el && el.value === 'Other') {
                    wrapper.classList.remove('hidden');
                } else {
                    wrapper.classList.add('hidden');
                }
            }
        }

        function addSeizureOfficerRow(btn) {
            const block = btn ? btn.closest('.seizure-block') : null;
            const container = block ? block.querySelector('#seizureOfficersContainer') : document.getElementById(
                'seizureOfficersContainer');
            if (!container) return;
            const rowDiv = document.createElement('div');
            rowDiv.className = 'seizure-officer-row p-2.5 bg-slate-50 border border-slate-300/80 rounded-xl';
            rowDiv.innerHTML = `
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <div class="relative flex items-center group">
                    <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </span>
                    <input type="text" class="seizure-officer-name w-full pl-8 pr-3 py-2 bg-white border border-slate-300 rounded-lg font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400" placeholder="Officer Full Name">
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="relative flex-1 flex items-center group">
                        <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 012-2h2a2 2 0 012 2v1m-4 0h4"/></svg>
                        </span>
                        <input type="text" class="seizure-officer-service-id w-full pl-8 pr-3 py-2 bg-white border border-slate-300 rounded-lg font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400 font-mono-code" placeholder="Service ID / Badge No (e.g. SD-0042)">
                    </div>
                    <button type="button" onclick="removeSeizureOfficerRow(this)" class="remove-officer-btn p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg border border-transparent hover:border-red-200 transition-colors cursor-pointer" title="Remove Officer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
        `;
            container.appendChild(rowDiv);
            updateSeizureOfficerRemoveButtons(container);
        }

        function removeSeizureOfficerRow(btn) {
            const row = btn.closest('.seizure-officer-row');
            const container = btn.closest('#seizureOfficersContainer');
            if (row) {
                row.remove();
                if (container) updateSeizureOfficerRemoveButtons(container);
            }
        }

        function updateSeizureOfficerRemoveButtons(container) {
            const containers = container ? [container] : document.querySelectorAll('#seizureOfficersContainer');
            containers.forEach(c => {
                const rows = c.querySelectorAll('.seizure-officer-row');
                rows.forEach(row => {
                    const btn = row.querySelector('.remove-officer-btn');
                    if (btn) {
                        if (rows.length > 1) {
                            btn.classList.remove('hidden');
                        } else {
                            btn.classList.add('hidden');
                        }
                    }
                });
            });
        }

        function getSeizureOfficersFormatted(block) {
            const parent = block || document;
            const container = parent.querySelector('#seizureOfficersContainer');
            if (!container) return null;
            const rows = container.querySelectorAll('.seizure-officer-row');
            const list = [];
            rows.forEach(row => {
                const nameInput = row.querySelector('.seizure-officer-name');
                const idInput = row.querySelector('.seizure-officer-service-id');
                const name = nameInput ? nameInput.value.trim() : '';
                const svcId = idInput ? idInput.value.trim() : '';
                if (name && svcId) {
                    list.push(`${name} (${svcId})`);
                } else if (name) {
                    list.push(name);
                } else if (svcId) {
                    list.push(`(ID: ${svcId})`);
                }
            });
            return list.length > 0 ? list.join('; ') : null;
        }

        function triggerSeizurePhotoUpload(el) {
            const block = el ? el.closest('.seizure-block') : document;
            const input = block.querySelector('.fileSeizurePhoto');
            if (input) input.click();
        }

        function handleSeizureDrop(e, dropzoneEl) {
            if (e.preventDefault) e.preventDefault();
            if (e.stopPropagation) e.stopPropagation();
            const block = dropzoneEl ? dropzoneEl.closest('.seizure-block') : document;
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                uploadSeizurePhotos(e.dataTransfer.files, block);
            }
        }

        async function uploadSeizurePhotos(files, block) {
            if (!files || files.length === 0) return;
            const targetBlock = block || document;
            const grid = targetBlock.querySelector('.seizurePhotosGrid');
            if (!grid) return;

            grid.classList.remove('hidden');

            const tokenEl = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = tokenEl ? tokenEl.content : '';

            let validFiles = Array.from(files).filter(f => f.type.startsWith('image/'));
            if (validFiles.length === 0) {
                alert('Please select valid image files (JPEG, PNG, WEBP, GIF).');
                return;
            }

            if (validFiles.length > 15) {
                alert('Best practice batch limit: Limiting to 15 photos per upload batch to stay within server limits.');
                validFiles = validFiles.slice(0, 15);
            }

            for (const file of validFiles) {
                if (file.size > 5 * 1024 * 1024) {
                    alert(`File "${file.name}" exceeds the 5MB maximum limit. Please keep individual photos under 3–5MB.`);
                    continue;
                }

                // Temporary loading placeholder card
                const tempDiv = document.createElement('div');
                tempDiv.className = 'seizure-photo-item relative group rounded-xl overflow-hidden border border-emerald-300 shadow-2xs aspect-square bg-slate-100 flex items-center justify-center animate-pulse';
                tempDiv.innerHTML = `
                    <div class="flex flex-col items-center justify-center space-y-1 p-2 text-center">
                        <svg class="w-5 h-5 text-emerald-600 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span class="text-[9px] font-mono-code font-bold text-slate-500 truncate max-w-[80px]">Uploading...</span>
                    </div>
                `;
                grid.appendChild(tempDiv);

                const formData = new FormData();
                formData.append('photos[]', file);
                if (csrfToken) formData.append('_token', csrfToken);

                try {
                    const res = await fetch('/upload-evidence-photos', {
                        method: 'POST',
                        body: formData,
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    if (data.success && data.urls && data.urls.length > 0) {
                        const url = data.urls[0];
                        tempDiv.classList.remove('animate-pulse', 'border-emerald-300');
                        tempDiv.classList.add('border-slate-200');
                        tempDiv.setAttribute('data-photo-url', url);
                        tempDiv.innerHTML = `
                            <img src="${url}" class="w-full h-full object-cover" alt="Exhibit Photo">
                            <input type="hidden" class="seizure-photo-url-input" value="${url}">
                            <div class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center space-x-1.5 p-1" onclick="event.stopPropagation()">
                                <button type="button" onclick="openMugshotModal('${url}', 'Exhibit Visual Evidence Photo')" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-[9px] font-bold rounded-lg shadow-xs cursor-pointer border-none">
                                    View
                                </button>
                                <button type="button" onclick="this.closest('.seizure-photo-item').remove(); updateSeizurePhotosCount(this.closest('.seizure-block') || document);" class="px-2 py-1 bg-red-600 hover:bg-red-700 text-white text-[9px] font-bold rounded-lg shadow-xs cursor-pointer border-none">
                                    Remove
                                </button>
                            </div>
                        `;
                    } else {
                        tempDiv.remove();
                        alert(data.message || `Failed to upload ${file.name}`);
                    }
                } catch (err) {
                    tempDiv.remove();
                    alert(`Network error uploading ${file.name}`);
                }
            }

            updateSeizurePhotosCount(targetBlock);
        }

        function updateSeizurePhotosCount(block) {
            const targetBlock = block || document;
            const grid = targetBlock.querySelector('.seizurePhotosGrid');
            const countBadge = targetBlock.querySelector('#seizurePhotosCount') || targetBlock.querySelector('.seizure-photos-count');
            const items = targetBlock.querySelectorAll('.seizure-photo-item');
            if (countBadge) {
                if (items.length > 0) {
                    countBadge.innerText = `${items.length} Photo${items.length > 1 ? 's' : ''} Attached`;
                    countBadge.classList.remove('hidden');
                } else {
                    countBadge.innerText = '0 Photos Uploaded';
                    countBadge.classList.add('hidden');
                }
            }
            if (grid && items.length === 0) {
                grid.classList.add('hidden');
            }
        }

        function previewSeizurePhotos(e, inputEl) {
            const block = inputEl ? inputEl.closest('.seizure-block') : document;
            const files = e.target.files;
            if (files && files.length > 0) {
                uploadSeizurePhotos(files, block);
                inputEl.value = '';
            }
        }

        function handleCommoditySearchKeydown(e, el, type) {
            if (e.key === 'Enter') {
                e.preventDefault();
                e.stopPropagation();
                const val = (el.value || '').trim();
                if (val) {
                    if (type === 'drug') selectDrugType(val, e);
                    else if (type === 'weapon') selectWeaponType(val, e);
                    else if (type === 'currency') selectCurrencyType(val, e);
                    else if (type === 'ammo') selectAmmoType(val, e);
                    else if (type === 'device') selectDeviceType(val, e);
                }
            }
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            document.querySelectorAll('.seizure-block').forEach(block => {
                const items = [{
                        wrapper: block.querySelector('#exhibitSelectWrapper'),
                        panel: block.querySelector('#exhibitListPanel')
                    },
                    {
                        wrapper: block.querySelector('#drugSelectWrapper'),
                        panel: block.querySelector('#drugListPanel')
                    },
                    {
                        wrapper: block.querySelector('#weaponSelectWrapper'),
                        panel: block.querySelector('#weaponListPanel')
                    },
                    {
                        wrapper: block.querySelector('#currencySelectWrapper'),
                        panel: block.querySelector('#currencyListPanel')
                    },
                    {
                        wrapper: block.querySelector('#ammoSelectWrapper'),
                        panel: block.querySelector('#ammoListPanel')
                    },
                    {
                        wrapper: block.querySelector('#deviceSelectWrapper'),
                        panel: block.querySelector('#deviceListPanel')
                    },
                    {
                        wrapper: block.querySelector('#packagingSelectWrapper'),
                        panel: block.querySelector('#pkgListPanel')
                    },
                    {
                        wrapper: block.querySelector('#unitSelectWrapper'),
                        panel: block.querySelector('#unitListPanel')
                    },
                    {
                        wrapper: block.querySelector('#vehTypeSelectWrapper'),
                        panel: block.querySelector('#vehTypeListPanel')
                    },
                ];
                items.forEach(item => {
                    if (item.wrapper && item.panel && !item.wrapper.contains(e.target)) {
                        item.panel.classList.add('hidden');
                    }
                });
            });
        }, true);

        // Prevent accidental form submission when pressing Enter in any single-line input within exhibit/lab forms
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const target = e.target;
                if (target && target.tagName !== 'TEXTAREA' && (
                    target.closest('#section4SeizuresForm') ||
                    target.closest('#section5LabForm') ||
                    target.closest('.seizure-block') ||
                    target.closest('#section4Seizures') ||
                    target.closest('#section5FormContainer')
                )) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }
        }, true);

        async function submitSection4SeizureForm(e) {
            if (e) e.preventDefault();

            const saveBtn = document.getElementById('saveSection4SeizureBtn');
            const saveBtnText = document.getElementById('saveSection4SeizureBtnText');
            const caseId = @json($case->id);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const blocks = document.querySelectorAll('#seizuresListContainer .seizure-block');
            const exhibits = [];

            blocks.forEach((block, idx) => {
                const assocPerson = block.querySelector('.seizure-associated-person-select')?.value ||
                    @json($activeSuspectName);
                const category = block.querySelector('#newSeizureCategory')?.value || block.querySelector(
                    '#exhibitSearchInput')?.value || '';

                let seizureName = block.querySelector('#newSeizureNameDrug')?.value ||
                    block.querySelector('#drugSearchInput')?.value ||
                    block.querySelector('#newSeizureNameWeapon')?.value ||
                    block.querySelector('#weaponSearchInput')?.value ||
                    block.querySelector('#newSeizureNameCurrency')?.value ||
                    block.querySelector('#currencySearchInput')?.value ||
                    block.querySelector('#newSeizureNameAmmo')?.value ||
                    block.querySelector('#ammoSearchInput')?.value ||
                    block.querySelector('#newSeizureNameDevice')?.value ||
                    block.querySelector('#deviceSearchInput')?.value ||
                    block.querySelector('#newSeizureName')?.value || '';

                const quantity = block.querySelector('#newSeizureQuantity')?.value || '';
                const weight = block.querySelector('#newSeizureWeight')?.value || '';
                const unit = block.querySelector('#newSeizureUnit')?.value || block.querySelector(
                    '#unitSearchInput')?.value || '';
                const packaging = block.querySelector('#newSeizurePackaging')?.value || block.querySelector(
                    '#pkgSearchInput')?.value || '';
                const value = block.querySelector('#newSeizureValue')?.value || '';
                const concealment = block.querySelector('#newSeizureConcealment')?.value || '';
                const dateSeized = block.querySelector('#newSeizureDate')?.value || '';
                const timeSeized = block.querySelector('#newSeizureTime')?.value || '';
                const location = block.querySelector('#newSeizureLocation')?.value || '';
                const sourceOfExhibits = block.querySelector('#newSeizureSourceOfExhibits')?.value || '';
                const seizingOfficer = getSeizureOfficersFormatted(block);
                const investigatingOfficer = block.querySelector('#newSeizureInvestigatingOfficer')?.value || block.querySelector('.seizure-investigating-officer-input')?.value || block.querySelector('[name="investigating_officer"]')?.value || '';
                const sourceRequestingUnit = block.querySelector('#newSeizureSourceRequestingUnit')?.value || block.querySelector('.seizure-source-requesting-unit-input')?.value || block.querySelector('[name="source_requesting_unit"]')?.value || '';

                const devModel = block.querySelector('#newDevModel')?.value || '';
                const devSerialNo = block.querySelector('#newDevSerialNo')?.value || '';
                const devImei1 = block.querySelector('#newDevImei1')?.value || '';
                const devImei2 = block.querySelector('#newDevImei2')?.value || '';
                const devMacAddress = block.querySelector('#newDevMacAddress')?.value || '';
                const devStorage = block.querySelector('#newDevStorage')?.value || '';
                const devColour = block.querySelector('#newDevColour')?.value || '';
                const devMarks = block.querySelector('#newDevMarks')?.value || '';

                const wpnModel = block.querySelector('#wpnModel')?.value || '';
                const wpnSerialNo = block.querySelector('#wpnSerialNo')?.value || '';
                const wpnCalibre = block.querySelector('#wpnCalibre')?.value || '';
                const wpnColour = block.querySelector('#wpnColour')?.value || '';
                const wpnSize = block.querySelector('#wpnSize')?.value || '';
                const wpnMarks = block.querySelector('#wpnMarks')?.value || '';
                const wpnLocationFound = block.querySelector('#wpnLocationFound')?.value || '';
                const wpnLoadedStatus = block.querySelector('#wpnLoadedStatus')?.value || '';
                const wpnCondition = block.querySelector('#wpnCondition')?.value || '';

                const vehMake = block.querySelector('#vehMake')?.value || '';
                const vehModel = block.querySelector('#vehModel')?.value || '';
                const vehType = block.querySelector('#vehType')?.value || block.querySelector('#vehTypeSearchInput')?.value || '';
                const vehYear = block.querySelector('#vehYear')?.value || '';
                const vehColour = block.querySelector('#vehColour')?.value || '';
                const vehRegNo = block.querySelector('#vehRegNo')?.value || '';
                const vehVIN = block.querySelector('#vehVIN')?.value || '';
                const vehEngineNo = block.querySelector('#vehEngineNo')?.value || '';
                const vehCountryReg = block.querySelector('#vehCountryReg')?.value || '';
                const vehOwner = block.querySelector('#vehOwner')?.value || '';
                const vehDriver = block.querySelector('#vehDriver')?.value || '';
                const vehOwnerContact = block.querySelector('#vehOwnerContact')?.value || '';
                const vehDocsAvailable = block.querySelector('#vehDocsAvailable')?.value || '';
                const vehDocRef = block.querySelector('#vehDocRef')?.value || '';
                const vehCondition = block.querySelector('#vehCondition')?.value || '';
                const vehFuel = block.querySelector('#vehFuel')?.value || '';
                const vehKeysRecv = block.querySelector('#vehKeysRecv')?.value || '';
                const vehKeyCount = block.querySelector('#vehKeyCount')?.value || '';
                const vehDamage = block.querySelector('#vehDamage')?.value || '';
                const vehMods = block.querySelector('#vehMods')?.value || '';
                const vehFeatures = block.querySelector('#vehFeatures')?.value || '';

                const ammoCartridgeAppearance = block.querySelector('#ammoCartridgeAppearance')?.value || '';
                const ammoProjectileType = block.querySelector('#ammoProjectileType')?.value || '';
                const ammoCaseMaterial = block.querySelector('#ammoCaseMaterial')?.value || '';
                const ammoColourMarkings = block.querySelector('#ammoColourMarkings')?.value || '';
                const ammoConditionVal = block.querySelector('#ammoCondition')?.value || '';
                const ammoOtherConditionVal = block.querySelector('#ammoOtherCondition')?.value || '';
                const ammoPackagingTypeVal = block.querySelector('#ammoPackagingType')?.value || '';
                const ammoSealConditionVal = block.querySelector('#ammoSealCondition')?.value || '';
                const ammoPackageReferenceNumberVal = block.querySelector('#ammoPackageReferenceNumber')?.value || '';
                const ammoConditionWhenReceivedVal = block.querySelector('#ammoConditionWhenReceived')?.value || '';
                const otherExhibitDescription = block.querySelector('#newOtherExhibitDescription')?.value || block.querySelector('.other-exhibit-description-input')?.value || '';

                const isAmmoCat = category === 'Ammunition' || (typeof category === 'string' && (category.toLowerCase().includes('ammo') || category.toLowerCase().includes('ammunition')));
                const isOtherCat = category === 'Other Exhibits' || category === 'Other' || category === 'Other Exhibit' || (typeof category === 'string' && category.toLowerCase().includes('other'));
                const ammoDetailsObj = isAmmoCat ? {
                    cartridge_appearance: ammoCartridgeAppearance,
                    projectile_bullet_type: ammoProjectileType,
                    cartridge_case_material: ammoCaseMaterial,
                    colour_markings: ammoColourMarkings,
                    condition: ammoConditionVal,
                    other_condition: ammoOtherConditionVal,
                    packaging_type: ammoPackagingTypeVal,
                    seal_tamper_evident_condition: ammoSealConditionVal,
                    package_reference_number: ammoPackageReferenceNumberVal,
                    condition_when_received: ammoConditionWhenReceivedVal
                } : null;

                if ((category === 'Vehicles' || category === 'Vehicle' || (typeof category === 'string' && category.toLowerCase().includes('vehic'))) && !seizureName.trim()) {
                    seizureName = [vehMake, vehModel, vehRegNo ? `(${vehRegNo})` : ''].filter(Boolean).join(' ') || 'Vehicle Exhibit';
                } else if (isAmmoCat && (!seizureName.trim() || seizureName === category)) {
                    seizureName = [ammoProjectileType, ammoCartridgeAppearance].filter(Boolean).join(' - ') || 'Ammunition Exhibit';
                } else if (isOtherCat && (!seizureName.trim() || seizureName === category)) {
                    seizureName = otherExhibitDescription ? (otherExhibitDescription.length > 50 ? otherExhibitDescription.substring(0, 50) + '...' : otherExhibitDescription) : 'Other Exhibit';
                }

                const exhibitType = block.querySelector('.exhibit-type-select')?.value || (category === 'Drugs' ? 'Drug Exhibit' : 'Non-Drug Exhibit');
                const drugClassification = block.querySelector('.drug-classification-select')?.value || (category === 'Drugs' ? 'Class A' : 'N/A');
                const exhibitRemark = block.querySelector('#newSeizureRemark')?.value?.trim() || block.querySelector('.seizure-remark-input')?.value?.trim() || block.querySelector('[name="exhibit_remark"]')?.value?.trim() || '';

                if (seizureName.trim()) {
                    const photoInputs = block.querySelectorAll('.seizure-photo-url-input');
                    const photoItems = block.querySelectorAll('.seizure-photo-item[data-photo-url]');
                    const photoUrlsList = Array.from(photoInputs).map(inp => inp.value).filter(Boolean);
                    photoItems.forEach(item => {
                        const u = item.getAttribute('data-photo-url');
                        if (u && !photoUrlsList.includes(u)) photoUrlsList.push(u);
                    });

                    exhibits.push({
                        associated_person: assocPerson,
                        suspect_name: assocPerson,
                        category: category,
                        exhibit_category: category,
                        photos: photoUrlsList,
                        evidence_photos: photoUrlsList,
                        exhibit_type: exhibitType,
                        drug_classification: drugClassification,
                        seizure_name: seizureName,
                        commodity_name: seizureName,
                        name: seizureName,
                        seizure_quantity: quantity,
                        quantity: quantity,
                        amount: (category === 'Money' || category === 'Currency' || category === 'Cash') ? (quantity || value || '') : (block.querySelector('#newSeizureAmount')?.value || ''),
                        seizure_weight: weight,
                        weight: weight,
                        seizure_unit: unit,
                        unit: unit,
                        seizure_packaging: packaging,
                        packaging: packaging,
                        seizure_value: value,
                        value: value,
                        seizure_concealment: concealment,
                        seizure_date: dateSeized,
                        seizure_time: timeSeized,
                        seizure_location: location,
                        source_of_exhibits: sourceOfExhibits,
                        exhibit_remark: exhibitRemark,
                        seizure_officer: seizingOfficer,
                        investigating_officer: investigatingOfficer,
                        source_requesting_unit: sourceRequestingUnit,
                        dev_model: devModel,
                        dev_serial_no: devSerialNo,
                        dev_imei1: devImei1,
                        dev_imei2: devImei2,
                        dev_mac_address: devMacAddress,
                        dev_storage: devStorage,
                        dev_colour: devColour,
                        dev_marks: devMarks,
                        wpn_model: wpnModel,
                        wpn_serial_no: wpnSerialNo,
                        wpn_calibre: wpnCalibre,
                        wpn_colour: wpnColour,
                        wpn_size: wpnSize,
                        wpn_marks: wpnMarks,
                        wpn_location_found: wpnLocationFound,
                        wpn_loaded_status: wpnLoadedStatus,
                        wpn_condition: wpnCondition,
                        weapon_model: wpnModel,
                        weapon_serial: wpnSerialNo,
                        weapon_calibre: wpnCalibre,
                        weapon_colour: wpnColour,
                        weapon_size: wpnSize,
                        veh_make: vehMake,
                        veh_model: vehModel,
                        veh_type: vehType,
                        veh_year: vehYear,
                        veh_colour: vehColour,
                        veh_reg_no: vehRegNo,
                        veh_vin: vehVIN,
                        veh_engine_no: vehEngineNo,
                        veh_country_reg: vehCountryReg,
                        veh_owner: vehOwner,
                        veh_driver: vehDriver,
                        veh_owner_contact: vehOwnerContact,
                        veh_docs_available: vehDocsAvailable,
                        veh_doc_ref: vehDocRef,
                        veh_condition: vehCondition,
                        veh_fuel: vehFuel,
                        veh_keys_recv: vehKeysRecv,
                        veh_key_count: vehKeyCount,
                        veh_damage: vehDamage,
                        veh_mods: vehMods,
                        veh_features: vehFeatures,
                        vehicle_make_model: vehMake ? (vehModel ? (vehMake + ' ' + vehModel) : vehMake) : vehModel,
                        vehicle_registration: vehRegNo,
                        vehicle_chassis_vin: vehVIN,
                        vehicle_color_type: vehColour,
                        vehicle_type: vehType,
                        vehicle_year: vehYear,
                        engine_number: vehEngineNo,
                        country_of_registration: vehCountryReg,
                        vehicle_owner: vehOwner,
                        vehicle_driver: vehDriver,
                        ammo_cartridge_appearance: ammoCartridgeAppearance,
                        ammo_projectile_type: ammoProjectileType,
                        ammo_case_material: ammoCaseMaterial,
                        ammo_colour_markings: ammoColourMarkings,
                        ammo_condition: ammoConditionVal,
                        ammo_other_condition: ammoOtherConditionVal,
                        ammo_packaging_type: ammoPackagingTypeVal,
                        ammo_seal_condition: ammoSealConditionVal,
                        ammo_package_reference_number: ammoPackageReferenceNumberVal,
                        ammo_condition_when_received: ammoConditionWhenReceivedVal,
                        ammunition_details: ammoDetailsObj,
                        ammo: ammoDetailsObj,
                        other_exhibit_description: otherExhibitDescription,
                        other_description: otherExhibitDescription,
                        veh: {
                            make: vehMake,
                            model: vehModel,
                            type: vehType,
                            year: vehYear,
                            colour: vehColour,
                            reg_no: vehRegNo,
                            vin: vehVIN,
                            engine_no: vehEngineNo,
                            country_reg: vehCountryReg,
                            owner: vehOwner,
                            driver: vehDriver,
                            owner_contact: vehOwnerContact,
                            docs_available: vehDocsAvailable,
                            doc_ref: vehDocRef,
                            condition: vehCondition,
                            fuel: vehFuel,
                            keys_recv: vehKeysRecv,
                            key_count: vehKeyCount,
                            damage: vehDamage,
                            mods: vehMods,
                            features: vehFeatures
                        },
                        wpn: {
                            model: wpnModel,
                            serial_no: wpnSerialNo,
                            calibre: wpnCalibre,
                            colour: wpnColour,
                            size: wpnSize,
                            marks: wpnMarks,
                            location_found: wpnLocationFound,
                            loaded_status: wpnLoadedStatus,
                            condition: wpnCondition
                        },
                        contents: block.querySelector('#newSeizureContents')?.value || block.querySelector('.seizure-contents-input')?.value || '',
                        exhibit_number: block.dataset.exhibitNumber || null,
                        lab_exhibit_no: document.getElementById(`labCardExhibitNo_${idx + 1}`)?.value || block.dataset.labExhibitNo || '',
                        description_of_exhibits: document.getElementById('manifestDescriptionOfExhibits')?.value || ''
                    });
                }
            });

            if (exhibits.length === 0) {
                alert('Please enter a Packaging as Received / Commodity Name for at least one seizure item.');
                return;
            }

            if (saveBtn) saveBtn.disabled = true;
            if (saveBtnText) saveBtnText.innerText = 'Saving Exhibit...';

            try {
                const res = await fetch(`/cases/${encodeURIComponent(caseId)}/associate`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        associated_exhibits: exhibits,
                        description_of_exhibits: document.getElementById('manifestDescriptionOfExhibits')?.value || ''
                    })
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    alert(data.message || 'Seizure / Exhibit saved successfully.');
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to save seizure exhibit.');
                    if (saveBtn) saveBtn.disabled = false;
                    if (saveBtnText) saveBtnText.innerText = 'Save Seizure / Exhibit';
                }
            } catch (err) {
                alert('An error occurred while saving the exhibit. Please check network connection and try again.');
                if (saveBtn) saveBtn.disabled = false;
                if (saveBtnText) saveBtnText.innerText = 'Save Seizure / Exhibit';
            }
        }

        function toggleLabFormSections() {
            const s4 = document.getElementById('section4Seizures');
            const s5 = document.getElementById('section5LabExamination');
            const masterTexts = document.querySelectorAll('.toggleLabFormTextSpan');
            const s4Texts = document.querySelectorAll('.s4ToggleTextSpan');
            const s5Texts = document.querySelectorAll('.s5ToggleTextSpan');

            if (!s4 && !s5) return;

            const s4Visible = s4 && !s4.classList.contains('hidden');
            const s5Visible = s5 && !s5.classList.contains('hidden');

            if (s4Visible || s5Visible) {
                if (s4) s4.classList.add('hidden');
                if (s5) s5.classList.add('hidden');
                masterTexts.forEach(el => el.innerText = 'Show Both Sections');
                s4Texts.forEach(el => el.innerText = 'Show Section 4');
                s5Texts.forEach(el => el.innerText = 'Show Section 5');
            } else {
                if (s4) s4.classList.remove('hidden');
                if (s5) s5.classList.remove('hidden');
                masterTexts.forEach(el => el.innerText = 'Hide Both Sections');
                s4Texts.forEach(el => el.innerText = 'Hide Section 4');
                s5Texts.forEach(el => el.innerText = 'Hide Section 5');
            }
        }

        function toggleSeizureItemSection(isActive, shouldScroll = true) {
            window.toggleSeizureItemSection = toggleSeizureItemSection;
            const s4 = document.getElementById('section4Seizures');
            const chk = document.getElementById('toggleSeizureItemSectionCheckbox');
            const badge = document.getElementById('seizureItemStatusBadge');
            const label = document.getElementById('labelToggleSeizureItemSection');
            const s4Texts = document.querySelectorAll('.s4ToggleTextSpan');

            if (chk && chk.checked !== isActive) {
                chk.checked = isActive;
            }

            if (s4) {
                if (isActive) {
                    s4.classList.remove('hidden');
                    // Activate all inputs inside section 4 to receive data
                    const formElements = s4.querySelectorAll('input:not([type="hidden"]), select, textarea, button');
                    formElements.forEach(el => el.disabled = false);

                    if (badge) {
                        badge.className = 'text-[9px] font-mono-code font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 uppercase';
                        badge.textContent = 'ACTIVE / VISIBLE';
                    }
                    if (label) {
                        label.classList.remove('bg-slate-50', 'border-slate-300');
                        label.classList.add('bg-emerald-50/80', 'border-emerald-300', 'ring-1', 'ring-emerald-400/40');
                    }
                    s4Texts.forEach(el => el.innerText = 'Hide Section 4');

                    if (shouldScroll) {
                        s4.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                } else {
                    s4.classList.add('hidden');
                    // Deactivate all inputs inside section 4
                    const formElements = s4.querySelectorAll('input:not([type="hidden"]), select, textarea, button');
                    formElements.forEach(el => el.disabled = true);

                    if (badge) {
                        badge.className = 'text-[9px] font-mono-code font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded border border-slate-200 uppercase';
                        badge.textContent = 'INACTIVE / HIDDEN';
                    }
                    if (label) {
                        label.classList.remove('bg-emerald-50/80', 'border-emerald-300', 'ring-1', 'ring-emerald-400/40');
                        label.classList.add('bg-slate-50', 'border-slate-300');
                    }
                    s4Texts.forEach(el => el.innerText = 'Show Section 4');

                    // Reset Seizure Item Section when toggled off so it starts clean next time
                    if (typeof resetSeizureItemSection === 'function') {
                        resetSeizureItemSection(false);
                    }
                }
            }
        }

        function toggleSection4() {
            const chk = document.getElementById('toggleSeizureItemSectionCheckbox');
            const targetState = chk ? !chk.checked : true;
            toggleSeizureItemSection(targetState, targetState);
        }

        function toggleSection5() {
            if (window.activeLabWorkflowMode === 'gsa') {
                return;
            }
            const s5 = document.getElementById('section5FormContainer') || document.getElementById('section5LabExamination');
            const s5Texts = document.querySelectorAll('.s5ToggleTextSpan');
            const s4ActionBtns = document.getElementById('section4ActionButtons');
            if (s5) {
                if (s5.classList.contains('hidden')) {
                    s5.classList.remove('hidden');
                    s5Texts.forEach(el => el.innerText = 'Hide Section 5');
                    if (s4ActionBtns && true) s4ActionBtns.classList.add('hidden');
                } else {
                    s5.classList.add('hidden');
                    s5Texts.forEach(el => el.innerText = 'Show Section 5');
                    if (s4ActionBtns) s4ActionBtns.classList.remove('hidden');
                }
            }
        }

        /* ── Cancel GSA Workflow and return to neutral state ── */
        function cancelGsaWorkflow() {
            if (typeof clearAllGsaFiles === 'function') {
                clearAllGsaFiles();
            }
            if (typeof resetLabModulesToNeutral === 'function') {
                resetLabModulesToNeutral();
            }
        }

        /* ── Reset Report & Laboratory Examination Modules to Neutral State ── */
        function resetLabModulesToNeutral() {
            window.activeLabWorkflowMode = '';

            const gsaSec = document.getElementById('gsaUploadSection');
            const nlrSec = document.getElementById('section5LabExamination');
            const nlrForm = document.getElementById('section5FormContainer');
            const form = document.getElementById('section5LabForm');
            const gsaBtn = document.getElementById('toggleGsaReportBtn');
            const nlrBtn = document.getElementById('toggleNlrReportBtn');
            const s4ActionBtns = document.getElementById('section4ActionButtons');

            // 1. Hide both sections and restore controls
            if (gsaSec) {
                gsaSec.classList.add('hidden');
                gsaSec.classList.remove('opacity-60', 'pointer-events-none', 'select-none');
                gsaSec.removeAttribute('aria-disabled');
                const gsaElements = gsaSec.querySelectorAll('input, select, textarea, button');
                gsaElements.forEach(el => {
                    if (el.getAttribute('data-nlr-disabled') === 'true') {
                        el.disabled = false;
                        el.removeAttribute('data-nlr-disabled');
                    }
                });
            }

            [nlrSec, nlrForm, form].filter(Boolean).forEach(container => {
                container.classList.add('hidden');
                container.classList.remove('opacity-60', 'pointer-events-none', 'select-none');
                container.removeAttribute('aria-disabled');
                const elements = container.querySelectorAll('input, select, textarea, button');
                elements.forEach(el => {
                    if (el.getAttribute('data-gsa-disabled') === 'true') {
                        el.disabled = false;
                        el.removeAttribute('data-gsa-disabled');
                    }
                });
            });

            // 2. Re-enable both buttons and reset to neutral
            if (gsaBtn) {
                gsaBtn.classList.remove('ring-2', 'ring-blue-400', 'opacity-40', 'cursor-not-allowed', 'pointer-events-none');
                gsaBtn.disabled = false;
                gsaBtn.removeAttribute('aria-disabled');
                gsaBtn.removeAttribute('title');
            }
            if (nlrBtn) {
                nlrBtn.classList.remove('ring-2', 'ring-emerald-400', 'opacity-40', 'cursor-not-allowed', 'pointer-events-none');
                nlrBtn.disabled = false;
                nlrBtn.removeAttribute('aria-disabled');
                nlrBtn.removeAttribute('title');
            }

            if (s4ActionBtns) {
                s4ActionBtns.classList.add('hidden');
            }

            // Update hidden inputs
            let modeInput = document.getElementById('labWorkflowModeInput');
            if (modeInput) modeInput.value = '';

            const section5CompletedInput = document.getElementById('section5CompletedInput');
            if (section5CompletedInput) section5CompletedInput.value = '0';

            const navBar = document.getElementById('toggleGsaReportBtn')?.closest('.bg-slate-100\\/90');
            if (navBar) {
                navBar.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        /* ── Helper to resolve Section 4 Save Button Label based on workflow mode ── */
        function getLabReviewSaveButtonLabel() {
            return (window.activeLabWorkflowMode === 'gsa' || Boolean(document.getElementById('gsaUploadSection') && !document.getElementById('gsaUploadSection').classList.contains('hidden')))
                ? 'Save Seizure Item & GSA Details'
                : 'Save Seizure Item & Examination Information';
        }

        /* ── Report & Laboratory Examination Module Switcher (GSA File Upload vs Section 5 NLR Examination) ── */
        function switchToGsaUploadSection() {
            window.activeLabWorkflowMode = 'gsa';

            const gsaSec = document.getElementById('gsaUploadSection');
            const nlrSec = document.getElementById('section5LabExamination');
            const nlrForm = document.getElementById('section5FormContainer');
            const form = document.getElementById('section5LabForm');
            const gsaBtn = document.getElementById('toggleGsaReportBtn');
            const nlrBtn = document.getElementById('toggleNlrReportBtn');
            const s4ActionBtns = document.getElementById('section4ActionButtons');

            // 1. Show GSA Section and re-enable its controls
            if (gsaSec) {
                gsaSec.classList.remove('hidden', 'opacity-60', 'pointer-events-none', 'select-none');
                gsaSec.removeAttribute('aria-disabled');
                const gsaElements = gsaSec.querySelectorAll('input, select, textarea, button');
                gsaElements.forEach(el => {
                    if (el.getAttribute('data-nlr-disabled') === 'true') {
                        el.disabled = false;
                        el.removeAttribute('data-nlr-disabled');
                    }
                });
            }

            // 2. Hide NLR Section and completely disable its controls & form
            [nlrSec, nlrForm, form].filter(Boolean).forEach(container => {
                container.classList.add('hidden', 'opacity-60', 'pointer-events-none', 'select-none');
                container.setAttribute('aria-disabled', 'true');
                const elements = container.querySelectorAll('input, select, textarea, button');
                elements.forEach(el => {
                    if (!el.disabled) {
                        el.setAttribute('data-gsa-disabled', 'true');
                        el.disabled = true;
                    }
                });
            });

            // 3. Highlight GSA button as active
            if (gsaBtn) {
                gsaBtn.disabled = false;
                gsaBtn.removeAttribute('aria-disabled');
                gsaBtn.removeAttribute('title');
                gsaBtn.classList.remove('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
                gsaBtn.classList.add('ring-2', 'ring-blue-400');
            }

            // 4. Completely disable the NLR button
            if (nlrBtn) {
                nlrBtn.disabled = true;
                nlrBtn.setAttribute('aria-disabled', 'true');
                nlrBtn.classList.add('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
                nlrBtn.classList.remove('ring-2', 'ring-emerald-400');
                nlrBtn.setAttribute('title', 'Examination Information (NLR) is disabled because Upload GSA File is selected.');
            }

            if (s4ActionBtns) {
                s4ActionBtns.classList.remove('hidden');
            }

            const saveBtnText4 = document.getElementById('saveSection4SeizureBtnText');
            if (saveBtnText4) {
                saveBtnText4.innerText = 'Save Seizure Item & GSA Details';
            }
            const saveBtnText5 = document.getElementById('saveSection5LabBtnText');
            if (saveBtnText5) {
                saveBtnText5.innerText = 'Save Seizure Item & GSA Details';
            }

            const examRefInput = document.getElementById('labExamReferenceInput');
            if (examRefInput && examRefInput.value.startsWith('NLR-')) {
                examRefInput.value = examRefInput.value.replace(/^NLR-/, 'GSA-');
            }

            // Update hidden inputs for server-side form submission
            let modeInput = document.getElementById('labWorkflowModeInput');
            if (!modeInput) {
                modeInput = document.createElement('input');
                modeInput.type = 'hidden';
                modeInput.id = 'labWorkflowModeInput';
                modeInput.name = 'lab_workflow_mode';
                document.querySelector('form')?.appendChild(modeInput);
            }
            modeInput.value = 'gsa';

            const section5CompletedInput = document.getElementById('section5CompletedInput');
            if (section5CompletedInput) section5CompletedInput.value = '0';

            if (gsaSec) {
                gsaSec.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
        window.showGsaReportUploadSection = switchToGsaUploadSection;

        function switchToNlrExamSection() {
            const gsaCount = Object.keys(gsaFilesMap || {}).length;
            const fileInput = document.getElementById('gsaReportImageInput');
            const hasInputFiles = Boolean(fileInput && fileInput.files && fileInput.files.length > 0);

            if (gsaCount > 0 || hasInputFiles) {
                alert('Examination Information (NLR) is disabled while a GSA file is selected. Please remove or clear all GSA files if you wish to use the NLR examination workflow.');
                return;
            }

            window.activeLabWorkflowMode = 'nlr';

            const gsaSec = document.getElementById('gsaUploadSection');
            const nlrSec = document.getElementById('section5LabExamination');
            const nlrForm = document.getElementById('section5FormContainer');
            const form = document.getElementById('section5LabForm');
            const gsaBtn = document.getElementById('toggleGsaReportBtn');
            const nlrBtn = document.getElementById('toggleNlrReportBtn');
            const s4ActionBtns = document.getElementById('section4ActionButtons');
            const isLabCompletedNow = window.activeSuspectHasSavedLabResult || @json($activeSuspectHasSavedResult);

            // 1. Show NLR Section and re-enable its controls
            [nlrSec, nlrForm, form].filter(Boolean).forEach(container => {
                container.classList.remove('opacity-60', 'pointer-events-none', 'select-none');
                container.removeAttribute('aria-disabled');
                const elements = container.querySelectorAll('input, select, textarea, button');
                elements.forEach(el => {
                    if (el.getAttribute('data-gsa-disabled') === 'true') {
                        el.disabled = false;
                        el.removeAttribute('data-gsa-disabled');
                    }
                });
            });

            if (nlrSec) nlrSec.classList.add('hidden');
            const readOnlyView = document.getElementById('section5ReadOnlyDisplay');
            if (readOnlyView) readOnlyView.classList.add('hidden');

            if (nlrForm) nlrForm.classList.remove('hidden');
            if (form) form.classList.remove('hidden');

            // 2. Hide GSA Section and completely disable its controls & form
            if (gsaSec) {
                gsaSec.classList.add('hidden', 'opacity-60', 'pointer-events-none', 'select-none');
                gsaSec.setAttribute('aria-disabled', 'true');
                const gsaElements = gsaSec.querySelectorAll('input, select, textarea, button');
                gsaElements.forEach(el => {
                    if (!el.disabled) {
                        el.setAttribute('data-nlr-disabled', 'true');
                        el.disabled = true;
                    }
                });
            }

            // 3. Highlight NLR button as active
            if (nlrBtn) {
                nlrBtn.disabled = false;
                nlrBtn.removeAttribute('aria-disabled');
                nlrBtn.removeAttribute('title');
                nlrBtn.classList.remove('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
                nlrBtn.classList.add('ring-2', 'ring-emerald-400');
            }

            // 4. Completely disable the GSA button
            if (gsaBtn) {
                gsaBtn.disabled = true;
                gsaBtn.setAttribute('aria-disabled', 'true');
                gsaBtn.classList.add('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
                gsaBtn.classList.remove('ring-2', 'ring-blue-400');
                gsaBtn.setAttribute('title', 'Upload GSA File is disabled because Examination Information (NLR) is selected.');
            }

            const saveBtnText4 = document.getElementById('saveSection4SeizureBtnText');
            if (saveBtnText4) {
                saveBtnText4.innerText = 'Save Seizure Item & Examination Information';
            }
            const saveBtnText5 = document.getElementById('saveSection5LabBtnText');
            if (saveBtnText5) {
                saveBtnText5.innerText = 'Save Seizure Item & Examination Information';
            }

            if (s4ActionBtns) {
                if (isLabCompletedNow) {
                    s4ActionBtns.classList.remove('hidden');
                } else {
                    s4ActionBtns.classList.add('hidden');
                }
            }

            // Determine exhibit saved status
            const exhSel = document.getElementById('labExhibitSelect');
            const selectedOpt = exhSel ? exhSel.options[exhSel.selectedIndex] : null;
            const isOptSaved = selectedOpt ? (selectedOpt.getAttribute('data-has-saved') === 'true' || selectedOpt.getAttribute('data-has-saved') === '1') : false;

            const badgeStatus = document.getElementById('section5BadgeStatus');
            if (badgeStatus) {
                if (isOptSaved) {
                    badgeStatus.className =
                        'px-2 py-0.5 rounded text-[9px] font-mono-code font-bold uppercase bg-emerald-100 text-emerald-800 border border-emerald-300';
                    badgeStatus.textContent = '✓ LAB COMPLETED';
                } else {
                    badgeStatus.className =
                        'px-2 py-0.5 rounded text-[9px] font-mono-code font-bold uppercase bg-slate-100 text-slate-800 border border-slate-300';
                    badgeStatus.textContent = '⭕ LAB PENDING';
                }
            }

            if (!isOptSaved && typeof unlockSection5Form === 'function') {
                unlockSection5Form();
            }

            const examRefInput = document.getElementById('labExamReferenceInput');
            if (examRefInput) {
                if (!examRefInput.value || examRefInput.value.trim() === '' || examRefInput.value.startsWith('EXAM-') || examRefInput.value.includes('T28')) {
                    const randNum = Math.floor(1000 + Math.random() * 9000);
                    examRefInput.value = `NLA-FM-T${randNum}-F`;
                } else if (examRefInput.value.startsWith('GSA-') || examRefInput.value.startsWith('NLR-') || examRefInput.value.startsWith('CSS-') || examRefInput.value.startsWith('EMU-')) {
                    examRefInput.value = examRefInput.value.replace(/^(GSA|NLR|CSS|EMU)-/, 'NLA-');
                }
            }
            const caseRefInput = document.getElementById('labCaseReferenceInput');
            if (caseRefInput && (!caseRefInput.value || caseRefInput.value.trim() === '')) {
                caseRefInput.value = window.caseNumber || @json($case->case_number ?? '');
            }
            const labNumInput = document.getElementById('formLabNumber');
            if (labNumInput && (!labNumInput.value || labNumInput.value.trim() === '')) {
                labNumInput.value = @json($labNumber ?? '');
            }

            // Update hidden inputs for server-side form submission
            let modeInput = document.getElementById('labWorkflowModeInput');
            if (!modeInput) {
                modeInput = document.createElement('input');
                modeInput.type = 'hidden';
                modeInput.id = 'labWorkflowModeInput';
                modeInput.name = 'lab_workflow_mode';
                document.querySelector('form')?.appendChild(modeInput);
            }
            modeInput.value = 'nlr';

            const section5CompletedInput = document.getElementById('section5CompletedInput');
            if (section5CompletedInput) section5CompletedInput.value = '1';

            const targetSec = nlrForm;
            if (targetSec) {
                targetSec.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        /* ── GSA & NLR Mutual Exclusivity Enforcement ── */
        function applyGsaNlrMutualExclusivity(hasGsaFiles) {
            const nlrBtn = document.getElementById('toggleNlrReportBtn');
            const gsaBtn = document.getElementById('toggleGsaReportBtn');
            const nlrSec = document.getElementById('section5LabExamination');
            const nlrForm = document.getElementById('section5FormContainer');
            const form = document.getElementById('section5LabForm');
            const nlrContainers = [nlrSec, nlrForm, form].filter(Boolean);

            if (window.activeLabWorkflowMode === 'gsa' || hasGsaFiles) {
                if (nlrBtn) {
                    nlrBtn.disabled = true;
                    nlrBtn.setAttribute('aria-disabled', 'true');
                    nlrBtn.classList.add('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
                    nlrBtn.classList.remove('ring-2', 'ring-emerald-400');
                    nlrBtn.setAttribute('title', 'Examination Information (NLR) is disabled because Upload GSA File is selected.');
                }
                nlrContainers.forEach(container => {
                    container.classList.add('hidden', 'opacity-60', 'pointer-events-none', 'select-none');
                    container.setAttribute('aria-disabled', 'true');
                    const elements = container.querySelectorAll('input, select, textarea, button');
                    elements.forEach(el => {
                        if (!el.disabled) {
                            el.setAttribute('data-gsa-disabled', 'true');
                            el.disabled = true;
                        }
                    });
                });
            } else if (window.activeLabWorkflowMode === 'nlr') {
                if (gsaBtn) {
                    gsaBtn.disabled = true;
                    gsaBtn.setAttribute('aria-disabled', 'true');
                    gsaBtn.classList.add('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
                    gsaBtn.classList.remove('ring-2', 'ring-blue-400');
                    gsaBtn.setAttribute('title', 'Upload GSA File is disabled because Examination Information (NLR) is selected.');
                }
                const gsaSec = document.getElementById('gsaUploadSection');
                if (gsaSec) {
                    gsaSec.classList.add('hidden', 'opacity-60', 'pointer-events-none', 'select-none');
                    gsaSec.setAttribute('aria-disabled', 'true');
                    const gsaElements = gsaSec.querySelectorAll('input, select, textarea, button');
                    gsaElements.forEach(el => {
                        if (!el.disabled) {
                            el.setAttribute('data-nlr-disabled', 'true');
                            el.disabled = true;
                        }
                    });
                }
            } else {
                // Neutral mode: neither button active, keep both forms hidden and enabled
                if (nlrBtn) {
                    nlrBtn.disabled = false;
                    nlrBtn.removeAttribute('aria-disabled');
                    nlrBtn.classList.remove('opacity-40', 'cursor-not-allowed', 'pointer-events-none', 'ring-2', 'ring-emerald-400');
                    nlrBtn.removeAttribute('title');
                }
                if (gsaBtn) {
                    gsaBtn.disabled = false;
                    gsaBtn.removeAttribute('aria-disabled');
                    gsaBtn.classList.remove('opacity-40', 'cursor-not-allowed', 'pointer-events-none', 'ring-2', 'ring-blue-400');
                    gsaBtn.removeAttribute('title');
                }
                nlrContainers.forEach(container => {
                    container.classList.remove('opacity-60', 'pointer-events-none', 'select-none');
                    container.removeAttribute('aria-disabled');
                    const elements = container.querySelectorAll('input, select, textarea, button');
                    elements.forEach(el => {
                        if (el.getAttribute('data-gsa-disabled') === 'true') {
                            el.disabled = false;
                            el.removeAttribute('data-gsa-disabled');
                        }
                    });
                });
                const gsaSec = document.getElementById('gsaUploadSection');
                if (gsaSec) {
                    gsaSec.classList.remove('opacity-60', 'pointer-events-none', 'select-none');
                    gsaSec.removeAttribute('aria-disabled');
                    const gsaElements = gsaSec.querySelectorAll('input, select, textarea, button');
                    gsaElements.forEach(el => {
                        if (el.getAttribute('data-nlr-disabled') === 'true') {
                            el.disabled = false;
                            el.removeAttribute('data-nlr-disabled');
                        }
                    });
                }
            }
        }

        /* ── GSA Report multi-file upload helpers for show_review.blade.php ── */
        let gsaFilesMap = {}; // id -> {file, dataUrl}
        let gsaFileIdCounter = 0;

        function gsaDragOver(e) {
            e.preventDefault();
            const zone = document.getElementById('gsaDropZone');
            if (zone) {
                zone.classList.add('border-blue-500', 'bg-blue-50/80');
                zone.classList.remove('border-slate-300', 'bg-slate-50');
            }
        }

        function gsaDragLeave(e) {
            const zone = document.getElementById('gsaDropZone');
            if (zone) {
                zone.classList.remove('border-blue-500', 'bg-blue-50/80');
                zone.classList.add('border-slate-300', 'bg-slate-50');
            }
        }

        function gsaHandleDrop(e) {
            e.preventDefault();
            gsaDragLeave(e);
            Array.from(e.dataTransfer.files).forEach(f => gsaAddFile(f));
        }

        function gsaHandleFileSelect(input) {
            Array.from(input.files).forEach(f => gsaAddFile(f));
            input.value = '';
        }

        function gsaAddFile(file) {
            const maxMB = 10;
            if (file.size > maxMB * 1024 * 1024) {
                alert('"' + file.name + '" exceeds ' + maxMB + ' MB limit and was skipped.');
                return;
            }
            const id = 'gsa_' + (++gsaFileIdCounter);
            const ext = file.name.split('.').pop().toLowerCase();

            gsaFilesMap[id] = { file };

            const grid = document.getElementById('gsaFileGrid');
            if (grid) {
                const card = document.createElement('div');
                card.id = 'gsaCard_' + id;
                card.className = 'relative bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow group/card';
                card.innerHTML = gsaBuildCardInner(id, file.name, file.size, ext, null);
                grid.appendChild(card);

                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (ev) => {
                        const thumbWrapper = document.getElementById('gsaThumb_' + id);
                        if (thumbWrapper) {
                            thumbWrapper.innerHTML = '<img src="' + ev.target.result + '" class="w-full h-24 object-cover" alt="preview">';
                        }
                        gsaFilesMap[id].dataUrl = ev.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            }
            gsaUpdateCount();
        }

        function gsaBuildCardInner(id, name, size, ext, dataUrl) {
            const sizeStr = (size / 1024).toFixed(0) + ' KB';
            const shortName = name.length > 16 ? name.substring(0, 13) + '...' : name;
            const cfg = gsaIconConfig(ext);

            let thumbHtml;
            if (dataUrl) {
                thumbHtml = '<img src="' + dataUrl + '" class="w-full h-24 object-cover" alt="preview">';
            } else {
                thumbHtml = '<div class="w-full h-24 flex flex-col items-center justify-center gap-1 ' + cfg.bg + '">' +
                    '<svg class="w-7 h-7 ' + cfg.color + '" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="' + cfg.path + '"/></svg>' +
                    '<span class="text-[10px] font-extrabold ' + cfg.color + ' font-mono-code tracking-wider">' + ext.toUpperCase() + '</span>' +
                    '</div>';
            }

            const thumb = '<div id="gsaThumb_' + id + '" class="overflow-hidden">' + thumbHtml + '</div>';
            const badge = '<span class="absolute top-1.5 left-1.5 w-5 h-5 ' + cfg.badgeBg + ' text-white rounded-full flex items-center justify-center text-[9px] shadow">✓</span>';
            const removeBtn = '<button type="button" onclick="gsaRemoveFile(\'' + id + '\', event)" ' +
                'class="absolute top-1.5 right-1.5 w-5 h-5 bg-red-600 hover:bg-red-700 text-white rounded-full ' +
                'flex items-center justify-center opacity-0 group-hover/card:opacity-100 transition-opacity cursor-pointer shadow">' +
                '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>' +
                '</button>';

            const info = '<div class="px-2 py-1.5">' +
                '<p class="text-[11px] font-bold text-slate-800 font-mono-code truncate leading-tight" title="' + name + '">' + shortName + '</p>' +
                '<p class="text-[10px] text-slate-400 font-mono-code">' + sizeStr + '</p>' +
                '</div>';

            return thumb + badge + removeBtn + info;
        }

        function gsaIconConfig(ext) {
            if (ext === 'pdf') return { bg: 'bg-red-50', border: 'border-red-300', color: 'text-red-600', badgeBg: 'bg-red-600', path: 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z' };
            if (ext === 'docx') return { bg: 'bg-blue-50', border: 'border-blue-300', color: 'text-blue-600', badgeBg: 'bg-blue-600', path: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' };
            if (ext === 'csv') return { bg: 'bg-emerald-50', border: 'border-emerald-300', color: 'text-emerald-700', badgeBg: 'bg-emerald-600', path: 'M3 10h18M3 14h18M10 3v18M6 3h12a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V5a2 2 0 012-2z' };
            return { bg: 'bg-emerald-50', border: 'border-emerald-300', color: 'text-emerald-700', badgeBg: 'bg-emerald-600', path: 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z' };
        }

        function gsaRemoveFile(id, e) {
            if (e) e.stopPropagation();
            delete gsaFilesMap[id];
            const card = document.getElementById('gsaCard_' + id);
            if (card) card.remove();
            gsaUpdateCount();
        }

        function addGsaExhibitTypeInput() {
            const list = document.getElementById('gsaExhibitTypeList');
            if (!list) return;
            const row = document.createElement('div');
            row.className = 'flex items-center gap-1.5 gsa-exhibit-type-row';
            row.innerHTML = `
                <input type="text" name="exhibit_type[]" placeholder="Enter Exhibit Type (e.g. Cocaine, Heroin)"
                    class="gsa-exhibit-type-input w-full px-3.5 py-2.5 bg-white border border-blue-200 rounded-xl text-slate-900 font-mono-code font-bold text-xs outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all shadow-2xs">
                <button type="button" onclick="removeGsaExhibitTypeInput(this)" class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-xl border border-red-200 transition-all shrink-0 cursor-pointer" title="Remove">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            `;
            list.appendChild(row);
            const input = row.querySelector('input');
            if (input) input.focus();
        }

        function removeGsaExhibitTypeInput(btn) {
            const row = btn?.closest('.gsa-exhibit-type-row');
            if (row) {
                row.remove();
            }
        }

        function clearAllGsaFiles() {
            gsaFilesMap = {};
            const grid = document.getElementById('gsaFileGrid');
            if (grid) grid.innerHTML = '';
            const input = document.getElementById('gsaReportImageInput');
            if (input) input.value = '';
            const gsaLabInput = document.getElementById('gsaLabNumberInput');
            if (gsaLabInput) gsaLabInput.value = '';
            const gsaCaseRef = document.getElementById('gsaCaseReferenceInput');
            if (gsaCaseRef) gsaCaseRef.value = '';
            const gsaStatus = document.getElementById('gsaStatusInput');
            if (gsaStatus) gsaStatus.value = '';
            const exhibitList = document.getElementById('gsaExhibitTypeList');
            if (exhibitList) {
                exhibitList.innerHTML = `
                    <div class="flex items-center gap-1.5 gsa-exhibit-type-row">
                        <input type="text" name="exhibit_type[]" placeholder="Enter Exhibit Type (e.g. Cocaine, Heroin)"
                            class="gsa-exhibit-type-input w-full px-3.5 py-2.5 bg-white border border-blue-200 rounded-xl text-slate-900 font-mono-code font-bold text-xs outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all shadow-2xs">
                    </div>`;
            }
            gsaUpdateCount();
        }

        function gsaUpdateCount() {
            const fileInput = document.getElementById('gsaReportImageInput');
            const hasInputFiles = Boolean(fileInput && fileInput.files && fileInput.files.length > 0);
            const count = Object.keys(gsaFilesMap).length + (hasInputFiles ? fileInput.files.length : 0);
            const badge = document.getElementById('gsaFileCount');
            const clearBtn = document.getElementById('gsaClearAllBtn');
            const prompt = document.getElementById('gsaFullPrompt');
            const strip = document.getElementById('gsaAddMoreStrip');
            if (badge) {
                badge.textContent = count + (count === 1 ? ' file' : ' files');
                badge.classList.toggle('hidden', count === 0);
            }
            if (clearBtn) clearBtn.classList.toggle('hidden', count === 0);
            if (prompt) prompt.classList.toggle('hidden', count > 0);
            if (strip) {
                strip.classList.toggle('hidden', count === 0);
                strip.classList.toggle('flex', count > 0);
            }
            applyGsaNlrMutualExclusivity(count > 0);
        }

        async function saveGsaReportFiles() {
            const e = arguments[0] || window.event;
            window.activeLabWorkflowMode = 'gsa';
            return submitCombinedLabAndSeizureForm(e);
            const fileKeys = Object.keys(gsaFilesMap);
            const fileInput = document.getElementById('gsaReportImageInput');
            const hasInputFiles = fileInput && fileInput.files && fileInput.files.length > 0;

            if (fileKeys.length === 0 && !hasInputFiles) {
                alert('Please select or drop at least one GSA report file before saving.');
                return;
            }

            const saveBtn = document.getElementById('saveGsaFilesBtn');
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.classList.add('opacity-60', 'cursor-not-allowed');
            }

            const formData = new FormData();
            const caseNumber = '{{ $case->case_number ?? '' }}';
            const caseId = '{{ $case->id ?? '' }}';
            const isMultipleCase = {{ ($case->underlyingRecord instanceof \App\Models\MultipleSuspectCase) || !empty($case->is_multiple) ? 'true' : 'false' }};

            const gsaSuspectSelect = document.getElementById('gsaSuspectSelect');
            let targetSuspectName = '{{ $activeSuspectName ?? ($case->suspect_name ?? '') }}';
            let targetSuspectId = '{{ $activeSuspectItem['id'] ?? '' }}';

            if (gsaSuspectSelect && gsaSuspectSelect.options && gsaSuspectSelect.selectedIndex >= 0) {
                const opt = gsaSuspectSelect.options[gsaSuspectSelect.selectedIndex];
                targetSuspectName = opt.value;
                targetSuspectId = opt.getAttribute('data-id') || '';
            }

            const gsaLabNoVal = document.getElementById('gsaLabNumberInput')?.value?.trim() || '';
            const gsaCaseRefVal = document.getElementById('gsaCaseReferenceInput')?.value?.trim() || '';
            const gsaStatusVal = document.getElementById('gsaStatusInput')?.value?.trim() || '';

            formData.append('case_number', caseNumber);
            if (caseId) {
                if (isMultipleCase) {
                    formData.append('multiple_suspect_case_id', caseId);
                } else {
                    formData.append('single_suspect_case_id', caseId);
                }
                formData.append('case_id', caseId);
            }
            if (targetSuspectName && targetSuspectName !== 'All Suspects') {
                formData.append('suspect_name', targetSuspectName);
            }
            if (targetSuspectId) {
                formData.append('case_suspect_id', targetSuspectId);
            }
            if (gsaLabNoVal) {
                formData.append('gsa_lab_number', gsaLabNoVal);
                formData.append('lab_number', gsaLabNoVal);
            }
            if (gsaCaseRefVal) {
                formData.append('gsa_case_reference', gsaCaseRefVal);
            }
            if (gsaStatusVal) {
                formData.append('gsa_status', gsaStatusVal);
            }

            const exhibitInputs = document.querySelectorAll('#gsaExhibitTypeList .gsa-exhibit-type-input');
            exhibitInputs.forEach(input => {
                const val = input.value?.trim();
                if (val) {
                    formData.append('exhibit_type[]', val);
                }
            });

            formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}');

            if (fileKeys.length > 0) {
                fileKeys.forEach(k => {
                    if (gsaFilesMap[k] && gsaFilesMap[k].file) {
                        formData.append('gsa_report_images[]', gsaFilesMap[k].file);
                    }
                });
            } else if (hasInputFiles) {
                Array.from(fileInput.files).forEach(f => {
                    formData.append('gsa_report_images[]', f);
                });
            }

            const targetUrl = caseNumber ? `/cases/${encodeURIComponent(caseNumber)}/upload-gsa-report` : '/Lab_Analysis/gsa-reports/store';

            try {
                const res = await fetch(targetUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                });
                const result = await res.json().catch(() => ({}));
                if (res.ok && result.success) {
                    alert(result.message || 'GSA Report uploaded and attached successfully.');
                    clearAllGsaFiles();
                    window.location.reload();
                } else {
                    alert(result.message || 'Failed to upload GSA Report files. Please check file formats and try again.');
                    if (saveBtn) {
                        saveBtn.disabled = false;
                        saveBtn.classList.remove('opacity-60', 'cursor-not-allowed');
                    }
                }
            } catch (err) {
                alert('An error occurred while uploading GSA Report files. Please check network connection and try again.');
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.classList.remove('opacity-60', 'cursor-not-allowed');
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Default Seizure Item section to unchecked, inactive, and hidden
            if (typeof toggleSeizureItemSection === 'function') {
                toggleSeizureItemSection(false, false);
            }

            if (false) {
                const catInput = document.getElementById('newSeizureCategory');
                const searchInput = document.getElementById('exhibitSearchInput');
                if (catInput && !catInput.value) catInput.value = 'Electronic Devices';
                if (searchInput && !searchInput.value) searchInput.value = 'Electronic Devices';
                if (typeof updateCommodityField === 'function') updateCommodityField('Electronic Devices');
            } else if (true) {
                const catInput = document.getElementById('newSeizureCategory');
                const searchInput = document.getElementById('exhibitSearchInput');
                if (catInput && !catInput.value) catInput.value = 'Drugs';
                if (searchInput && !searchInput.value) searchInput.value = 'Drugs';
                if (typeof updateCommodityField === 'function') updateCommodityField('Drugs');
                const labSel = document.getElementById('labExhibitSelect');
                if (labSel && labSel.options.length > 0) {
                    if (!labSel.value || labSel.value === '') {
                        for (let i = 0; i < labSel.options.length; i++) {
                            if (labSel.options[i].value && labSel.options[i].value !== '') {
                                labSel.selectedIndex = i;
                                break;
                            }
                        }
                    }
                    if (labSel.value && typeof onReviewLabExhibitChange === 'function') {
                        onReviewLabExhibitChange(labSel);
                    }
                }
            }
        });

        /* ── SECTION 5: Dynamic Lab Exhibit & Examination Functions ── */
        window.activeLabExhibitIndex = window.activeLabExhibitIndex || 1;
        window.labExhibitUserEdits = window.labExhibitUserEdits || {};

        function computeReservedSample(grossStr, takenStr) {
            if (typeof computeReservedSampleReview === 'function') {
                return computeReservedSampleReview(grossStr, takenStr);
            }
            return { reserved: '0', isOverflow: false };
        }

        function getPerExhibitLabData(idx) {
            const yesBox = document.getElementById(`presumptiveTestYes_${idx}`) || (idx === 1 ? document.getElementById('presumptiveTestYes') : null);
            const noBox = document.getElementById(`presumptiveTestNo_${idx}`) || (idx === 1 ? document.getElementById('presumptiveTestNo') : null);
            let presVal = null;
            if (noBox && noBox.checked) {
                presVal = 'NO (Negative Reaction)';
            } else if (yesBox && yesBox.checked) {
                const customResult = document.getElementById(`presumptiveReactionResultInput_${idx}`)?.value ||
                                     (idx === 1 ? document.getElementById('presumptiveReactionResultInput')?.value : '');
                presVal = customResult && customResult.trim() !== '' ? customResult.trim() : 'YES (Presumptive Positive)';
            }

            const confYes = document.getElementById(`confirmatoryTestYes_${idx}`) || (idx === 1 ? document.getElementById('confirmatoryTestYes') : null);
            const confNo = document.getElementById(`confirmatoryTestNo_${idx}`) || (idx === 1 ? document.getElementById('confirmatoryTestNo') : null);
            let confVal = 'NO';
            if (confYes && confYes.checked) confVal = 'YES';
            else if (confNo && confNo.checked) confVal = 'NO';

            const desc = document.getElementById(`sampleWeightDescription_${idx}`)?.value || (idx === 1 ? document.getElementById('sampleWeightDescription')?.value : '') || '';
            const units = document.getElementById(`sampleWeightUnits_${idx}`)?.value || (idx === 1 ? document.getElementById('sampleWeightUnits')?.value : '') || '';
            const gross = document.getElementById(`sampleWeightGross_${idx}`)?.value || (idx === 1 ? document.getElementById('sampleWeightGross')?.value : '') || '';
            const taken = document.getElementById(`sampleWeightTaken_${idx}`)?.value || (idx === 1 ? document.getElementById('sampleWeightTaken')?.value : '') || '';
            const reserved = document.getElementById(`sampleWeightReserved_${idx}`)?.value || (idx === 1 ? document.getElementById('sampleWeightReserved')?.value : '') || '';

            const substanceId = document.getElementById(`confirmatorySubstanceIdentification_${idx}`)?.value || (idx === 1 ? document.getElementById('confirmatorySubstanceIdentification')?.value : '') || '';
            const detSubs = typeof getDetectedSubstancesDataForExhibit === 'function' ? getDetectedSubstancesDataForExhibit(idx) : [];

            const methods = document.getElementById(`confirmatoryTestMethods_${idx}`)?.value || document.getElementById('confirmatoryTestMethods')?.value || '';
            const specs = document.getElementById(`confirmatorySpecifications_${idx}`)?.value || document.getElementById('confirmatorySpecifications')?.value || '';
            const remark = document.getElementById(`confirmatoryRemark_${idx}`)?.value || document.getElementById('confirmatoryRemark')?.value || '';

            const cardLabExNo = document.getElementById(`labCardExhibitNo_${idx}`)?.value || '';

            const hasSaved = (yesBox && yesBox.checked) || (noBox && noBox.checked) || (confYes && confYes.checked) || (confNo && confNo.checked) || !!taken || !!gross || !!substanceId || (detSubs && detSubs.length > 0) || !!cardLabExNo;

            const scData = typeof getScreeningMethodsData === 'function' ? getScreeningMethodsData() : '';
            const scStr = Array.isArray(scData) ? scData.join(', ') : (scData || '');

            return {
                lab_exhibit_no: cardLabExNo,
                presumptive_result: presVal,
                confirmatory_result: confVal,
                substance_identification: substanceId,
                sample_weight_description: desc,
                sample_weight_units: units,
                sample_weight_gross: gross,
                sample_weight_taken: taken,
                sample_weight_reserved: reserved,
                sample_taken: taken,
                sample_reserved: reserved,
                sample_description: desc,
                sample_units: units,
                sample_gross: gross,
                sample_weight: {
                    description: desc,
                    units_packaging: units,
                    gross_weight: gross,
                    sample_taken: taken,
                    reserved_samples: reserved
                },
                detected_substances: detSubs,
                test_methods: methods,
                specifications: specs,
                remark: remark,
                purpose_of_examination: document.getElementById('labPurposeOfExaminationInput')?.value || '',
                preliminary_exam_procedure: document.getElementById('labPreliminaryExamProcedureInput')?.value || '',
                screening_method: scStr,
                disposition_of_exhibit: document.getElementById('confirmatoryDispositionOfExhibit')?.value || '',
                has_saved_lab: hasSaved
            };
        }

        function selectLabExhibitCard(idx) {
            window.activeLabExhibitIndex = idx;
            const selectEl = document.getElementById('labExhibitSelect');
            if (selectEl) {
                if (selectEl.options.length >= idx) {
                    selectEl.selectedIndex = idx - 1;
                } else {
                    selectEl.value = idx.toString();
                }
                if (typeof onLabExhibitSelected === 'function') onLabExhibitSelected();
                if (typeof onReviewLabExhibitChange === 'function') onReviewLabExhibitChange(selectEl);
            }

            const cardExhNoInput = document.getElementById(`labCardExhibitNo_${idx}`);
            const hiddenInput = document.getElementById('labExhibitNumberInput');
            if (cardExhNoInput && hiddenInput) {
                hiddenInput.value = cardExhNoInput.value;
            }

            document.querySelectorAll('.lab-exhibit-card').forEach(card => {
                const cIdx = parseInt(card.getAttribute('data-exhibit-index'), 10);
                if (cIdx === idx) {
                    card.classList.remove('border-slate-200', 'bg-white');
                    card.classList.add('border-emerald-500', 'ring-2', 'ring-emerald-500/30', 'bg-emerald-50/40');
                } else {
                    card.classList.remove('border-emerald-500', 'ring-2', 'ring-emerald-500/30', 'bg-emerald-50/40');
                    card.classList.add('border-slate-200', 'bg-white');
                }
            });

            const presCard = document.getElementById(`presumptiveTestExhibitCard_${idx}`);
            if (presCard) {
                presCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        function togglePresumptiveTestForExhibit(idx, choice) {
            const yesBox = document.getElementById(`presumptiveTestYes_${idx}`);
            const noBox = document.getElementById(`presumptiveTestNo_${idx}`);
            const detailsContainer = document.getElementById(`presumptiveReactionDetailsContainer_${idx}`);
            const resultInput = document.getElementById(`presumptiveReactionResultInput_${idx}`);

            if (choice === 'YES') {
                if (yesBox) yesBox.checked = true;
                if (noBox) noBox.checked = false;
                if (detailsContainer) detailsContainer.classList.remove('hidden');
                updatePresumptiveReactionTextForExhibit(idx);
            } else {
                if (yesBox) yesBox.checked = false;
                if (noBox) noBox.checked = true;
                if (detailsContainer) detailsContainer.classList.add('hidden');
                if (resultInput) resultInput.value = 'NO (Negative Reaction)';
            }

            // Keep legacy aliases synchronized
            const revYes = document.getElementById('presumptiveTestYesReview') || document.querySelector('input[name="form_presumptive_radio"][value="YES"]');
            const revNo = document.getElementById('presumptiveTestNoReview') || document.querySelector('input[name="form_presumptive_radio"][value="NO"]');
            if (revYes && choice === 'YES') {
                revYes.checked = true;
                if (typeof onPresumptiveRadioChange === 'function') onPresumptiveRadioChange('YES');
            }
            if (revNo && choice === 'NO') {
                revNo.checked = true;
                if (typeof onPresumptiveRadioChange === 'function') onPresumptiveRadioChange('NO');
            }

            if (idx === 1) {
                const legacyYes = document.getElementById('presumptiveTestYes');
                const legacyNo = document.getElementById('presumptiveTestNo');
                const legacyInput = document.getElementById('presumptiveReactionResultInput');
                if (legacyYes) legacyYes.checked = (choice === 'YES');
                if (legacyNo) legacyNo.checked = (choice === 'NO');
                if (legacyInput && resultInput) legacyInput.value = resultInput.value;
            }
        }

        function addPresumptiveSpecimenRow(idx, presence = '', percentage = '') {
            return addPresumptiveTestRowForExhibit(idx, presence, percentage);
        }

        function addPresumptiveTestRowForExhibit(idx, presence = '', percentage = '') {
            const container = document.getElementById(`presumptiveTestRowsContainer_${idx}`);
            if (!container) return;
            const currentRows = container.querySelectorAll('.presumptive-test-row');
            const newRowNum = currentRows.length + 1;

            const rowDiv = document.createElement('div');
            rowDiv.className = 'presumptive-test-row p-3 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-2 animate-in fade-in duration-200';
            rowDiv.innerHTML = `
                <div class="flex items-center justify-between text-[11px] font-extrabold text-slate-700 uppercase font-mono-code">
                    <span class="test-row-title">Test Result #${newRowNum}</span>
                    <button type="button" onclick="removePresumptiveTestRowForExhibit(this, ${idx})"
                        class="remove-test-row-btn px-2 py-0.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg text-[10px] font-bold border border-transparent hover:border-red-200 transition-colors cursor-pointer">
                        &times; Remove
                    </button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                            Presence of <span class="text-red-600 font-bold">*</span>
                        </label>
                        <input type="text" class="presumptive-presence-input w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs"
                            value="${presence}" oninput="updatePresumptiveReactionTextForExhibit(${idx})" placeholder="e.g. Cocaine, Heroin, THC, Methamphetamine">
                    </div>
                    <div>
                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                            Percentage (%) <span class="text-red-600 font-bold">*</span>
                        </label>
                        <div class="flex items-center rounded-xl shadow-2xs overflow-hidden">
                            <input type="text" class="presumptive-percentage-input w-full pl-3 pr-2 py-2 bg-white border border-y border-l border-slate-300 rounded-l-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 font-mono-code"
                                value="${percentage}" oninput="updatePresumptiveReactionTextForExhibit(${idx})" placeholder="e.g. 85.5">
                            <span class="inline-flex items-center px-3 py-2 bg-slate-100 border border-slate-300 rounded-r-xl text-slate-700 font-extrabold text-xs font-mono-code shrink-0">%</span>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(rowDiv);
            updatePresumptiveRowTitlesForExhibit(idx);
            updatePresumptiveReactionTextForExhibit(idx);
        }

        function removePresumptiveTestRowForExhibit(btn, idx) {
            const row = btn.closest('.presumptive-test-row');
            const container = document.getElementById(`presumptiveTestRowsContainer_${idx}`);
            if (row && container) {
                const rows = container.querySelectorAll('.presumptive-test-row');
                if (rows.length <= 1) return;
                row.remove();
                updatePresumptiveRowTitlesForExhibit(idx);
                updatePresumptiveReactionTextForExhibit(idx);
            }
        }

        function updatePresumptiveRowTitlesForExhibit(idx) {
            const container = document.getElementById(`presumptiveTestRowsContainer_${idx}`);
            if (!container) return;
            const rows = container.querySelectorAll('.presumptive-test-row');
            rows.forEach((r, i) => {
                const title = r.querySelector('.test-row-title');
                if (title) title.innerText = `Test Result #${i + 1}`;
                const removeBtn = r.querySelector('.remove-test-row-btn');
                if (removeBtn) {
                    if (rows.length <= 1) {
                        removeBtn.classList.add('hidden');
                    } else {
                        removeBtn.classList.remove('hidden');
                    }
                }
            });
        }

        function updatePresumptiveReactionTextForExhibit(idx) {
            const container = document.getElementById(`presumptiveTestRowsContainer_${idx}`);
            const resultInput = document.getElementById(`presumptiveReactionResultInput_${idx}`);
            if (!container || !resultInput) return;

            const rows = container.querySelectorAll('.presumptive-test-row');
            const parts = [];
            rows.forEach((r, i) => {
                const pres = (r.querySelector('.presumptive-presence-input')?.value || '').trim();
                const pct = (r.querySelector('.presumptive-percentage-input')?.value || '').trim();
                if (pres || pct) {
                    let part = `Test #${i + 1}: `;
                    if (pres) part += `Presence of ${pres}`;
                    if (pct) part += ` (${pct}%)`;
                    parts.push(part);
                }
            });

            if (parts.length > 0) {
                resultInput.value = 'YES: ' + parts.join('; ');
            } else {
                resultInput.value = 'YES (Presumptive Positive)';
            }

            if (idx === 1) {
                const legacyInput = document.getElementById('presumptiveReactionResultInput');
                if (legacyInput) legacyInput.value = resultInput.value;
            }
        }

        function toggleConfirmatoryTestForExhibit(idx, choice) {
            const yesBox = document.getElementById(`confirmatoryTestYes_${idx}`);
            const noBox = document.getElementById(`confirmatoryTestNo_${idx}`);
            const weightContainer = document.getElementById(`sampleWeightContainer_${idx}`);
            const resultInput = document.getElementById(`confirmatoryTestResultInput_${idx}`);

            if (choice === 'YES') {
                if (yesBox) yesBox.checked = true;
                if (noBox) noBox.checked = false;
                if (weightContainer) weightContainer.classList.remove('hidden');
                if (resultInput) resultInput.value = 'YES';
                updateSampleWeightReservedForExhibit(idx);
            } else {
                if (yesBox) yesBox.checked = false;
                if (noBox) noBox.checked = true;
                if (weightContainer) weightContainer.classList.add('hidden');
                if (resultInput) resultInput.value = 'NO';
            }

            // Keep legacy aliases synchronized
            const revConfYes = document.getElementById('confirmatoryTestYesReview') || document.querySelector('input[name="form_confirmatory_radio"][value="YES"]');
            const revConfNo = document.getElementById('confirmatoryTestNoReview') || document.querySelector('input[name="form_confirmatory_radio"][value="NO"]');
            if (revConfYes && choice === 'YES') {
                revConfYes.checked = true;
                if (typeof toggleConfirmatoryTestReview === 'function') toggleConfirmatoryTestReview('YES');
            }
            if (revConfNo && choice === 'NO') {
                revConfNo.checked = true;
                if (typeof toggleConfirmatoryTestReview === 'function') toggleConfirmatoryTestReview('NO');
            }

            if (idx === 1) {
                const legacyYes = document.getElementById('confirmatoryTestYes');
                const legacyNo = document.getElementById('confirmatoryTestNo');
                const legacyInput = document.getElementById('confirmatoryTestResultInput');
                if (legacyYes) legacyYes.checked = (choice === 'YES');
                if (legacyNo) legacyNo.checked = (choice === 'NO');
                if (legacyInput && resultInput) legacyInput.value = resultInput.value;
            }
        }

        function updateConfirmatorySampleReserved(idx) {
            return updateSampleWeightReservedForExhibit(idx);
        }

        function updateSampleWeightReservedForExhibit(idx) {
            const grossInput = document.getElementById(`sampleWeightGross_${idx}`);
            const takenInput = document.getElementById(`sampleWeightTaken_${idx}`);
            const addonInput = document.getElementById(`sampleWeightTakenUnitAddon_${idx}`);
            const reservedInput = document.getElementById(`sampleWeightReserved_${idx}`);
            const errorMsgEl = document.getElementById(`sampleWeightTakenError_${idx}`);

            if (!grossInput || !reservedInput) return;

            const grossStr = grossInput.value || '';
            const takenStr = takenInput ? takenInput.value : '';

            const result = computeReservedSample(grossStr, takenStr);
            reservedInput.value = result.reserved;

            if (result.isOverflow) {
                if (errorMsgEl) errorMsgEl.classList.remove('hidden');
                if (takenInput) {
                    takenInput.classList.add('border-red-500', 'ring-2', 'ring-red-500/20');
                    takenInput.classList.remove('border-slate-300');
                }
                if (addonInput) {
                    addonInput.classList.add('border-red-500', 'bg-red-50', 'text-red-700');
                    addonInput.classList.remove('border-slate-300', 'bg-slate-100/90', 'text-slate-700');
                }
            } else {
                if (errorMsgEl) errorMsgEl.classList.add('hidden');
                if (takenInput) {
                    takenInput.classList.remove('border-red-500', 'ring-2', 'ring-red-500/20');
                    takenInput.classList.add('border-slate-300');
                }
                if (addonInput) {
                    addonInput.classList.remove('border-red-500', 'bg-red-50', 'text-red-700');
                    addonInput.classList.add('border-slate-300', 'bg-slate-100/90', 'text-slate-700');
                }
            }

            if (idx === 1) {
                const legacyTaken = document.getElementById('sampleWeightTaken');
                const legacyReserved = document.getElementById('sampleWeightReserved');
                if (legacyTaken && takenInput) legacyTaken.value = takenInput.value;
                if (legacyReserved && reservedInput) legacyReserved.value = reservedInput.value;
            }
        }

        function addConfirmatoryDetectedSubstanceRow(idx, substance = '', percentage = '') {
            return addDetectedSubstanceRowForExhibit(idx, substance, percentage);
        }

        function addDetectedSubstanceRowForExhibit(idx, substance = '', percentage = '') {
            const container = document.getElementById(`detectedSubstancesListContainer_${idx}`);
            if (!container) return;
            const rowDiv = document.createElement('div');
            rowDiv.className = 'detected-substance-row flex flex-col sm:flex-row items-center gap-3 bg-white p-3 rounded-xl border border-slate-300 shadow-2xs animate-in fade-in duration-200';
            rowDiv.innerHTML = `
                <div class="flex-1 w-full">
                    <label class="block text-slate-700 font-bold text-[10px] uppercase mb-1">Substance / Compound Detected</label>
                    <input type="text" class="detected-substance-name w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-slate-900 font-extrabold text-xs focus:outline-none focus:border-emerald-600 font-sans" placeholder="e.g. Cocaine Hydrochloride" value="${substance}">
                </div>
                <div class="w-full sm:w-48">
                    <label class="block text-slate-700 font-bold text-[10px] uppercase mb-1">Purity / Percentage</label>
                    <div class="flex items-center rounded-lg overflow-hidden shadow-2xs">
                        <input type="text" class="detected-substance-percent w-full pl-3 pr-2 py-2 bg-slate-50 border border-y border-l border-slate-300 rounded-l-lg text-slate-900 font-extrabold text-xs focus:outline-none focus:border-emerald-600 font-mono-code" placeholder="e.g. 85.4" value="${percentage}">
                        <span class="inline-flex items-center px-2.5 py-2 bg-slate-200/80 border border-slate-300 rounded-r-lg text-slate-700 font-extrabold text-xs font-mono-code shrink-0">%</span>
                    </div>
                </div>
                <button type="button" onclick="removeDetectedSubstanceRowForExhibit(this, ${idx})" class="mt-2 sm:mt-5 p-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg border border-red-200 transition-colors shrink-0 cursor-pointer" title="Remove Row">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            `;
            container.appendChild(rowDiv);
            updateDetectedSubstanceRemoveButtonsForExhibit(idx);
        }

        function removeDetectedSubstanceRowForExhibit(btn, idx) {
            const container = document.getElementById(`detectedSubstancesListContainer_${idx}`);
            if (container) {
                const rows = container.querySelectorAll('.detected-substance-row');
                if (rows.length <= 1) return;
            }
            const row = btn.closest('.detected-substance-row');
            if (row) {
                row.remove();
                updateDetectedSubstanceRemoveButtonsForExhibit(idx);
            }
        }

        function updateDetectedSubstanceRemoveButtonsForExhibit(idx) {
            const container = document.getElementById(`detectedSubstancesListContainer_${idx}`);
            if (!container) return;
            const rows = container.querySelectorAll('.detected-substance-row');
            rows.forEach(r => {
                const btn = r.querySelector('button[title="Remove Row"]');
                if (btn) {
                    if (rows.length <= 1) {
                        btn.classList.add('hidden');
                        btn.style.display = 'none';
                    } else {
                        btn.classList.remove('hidden');
                        btn.style.display = '';
                    }
                }
            });
        }

        function getDetectedSubstancesDataForExhibit(idx) {
            const rows = document.querySelectorAll(`#detectedSubstancesListContainer_${idx} .detected-substance-row`);
            const data = [];
            rows.forEach(r => {
                const sub = r.querySelector('.detected-substance-name')?.value || '';
                const pct = r.querySelector('.detected-substance-percent')?.value || '';
                if (sub.trim() !== '' || pct.trim() !== '') {
                    data.push({
                        substance: sub.trim(),
                        percentage: pct.trim()
                    });
                }
            });
            return data;
        }

        function togglePresumptiveTest(choice) {
            togglePresumptiveTestForExhibit(1, choice);
        }
        function addPresumptiveTestRow() {
            addPresumptiveTestRowForExhibit(1);
        }
        function removePresumptiveTestRow(btn) {
            const card = btn.closest('.presumptive-exhibit-card');
            const idx = card ? parseInt(card.getAttribute('data-exhibit-index'), 10) : 1;
            removePresumptiveTestRowForExhibit(btn, idx);
        }
        function updatePresumptiveReactionText() {
            updatePresumptiveReactionTextForExhibit(1);
        }
        function toggleConfirmatoryTest(choice) {
            toggleConfirmatoryTestForExhibit(1, choice);
        }
        function updateSampleWeightReserved() {
            updateSampleWeightReservedForExhibit(1);
        }
        function addDetectedSubstanceRow(substance = '', percentage = '') {
            addDetectedSubstanceRowForExhibit(1, substance, percentage);
        }
        function removeDetectedSubstanceRow(btn) {
            const card = btn.closest('.confirmatory-exhibit-card');
            const idx = card ? parseInt(card.getAttribute('data-exhibit-index'), 10) : 1;
            removeDetectedSubstanceRowForExhibit(btn, idx);
        }
        function getDetectedSubstancesData() {
            return getDetectedSubstancesDataForExhibit(1);
        }

        function addScreeningMethodRow(val = '') {
            const container = document.getElementById('screeningMethodsContainer');
            if (!container) return;
            const rowDiv = document.createElement('div');
            rowDiv.className = 'screening-method-row flex items-center space-x-2 animate-in fade-in duration-200';
            rowDiv.innerHTML = `
                <input type="text" name="screening_method[]"
                    placeholder="e.g. Gas Chromatography-Mass Spectrometry (GC-MS), FTIR, Raman..."
                    value="${val ? String(val).replace(/"/g, '&quot;') : ''}"
                    class="screening-method-input w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-mono-code placeholder:text-slate-400">
                <button type="button" onclick="removeScreeningMethodRow(this)"
                    class="remove-screening-method-btn p-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl border border-red-200 transition-colors shrink-0 cursor-pointer" title="Remove Method">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            `;
            container.appendChild(rowDiv);
            updateScreeningMethodRemoveButtons();
        }

        function removeScreeningMethodRow(btn) {
            const container = document.getElementById('screeningMethodsContainer');
            if (!container) return;
            const rows = container.querySelectorAll('.screening-method-row');
            if (rows.length <= 1) return;
            const row = btn.closest('.screening-method-row');
            if (row) {
                row.remove();
                updateScreeningMethodRemoveButtons();
            }
        }

        function updateScreeningMethodRemoveButtons() {
            const container = document.getElementById('screeningMethodsContainer');
            if (!container) return;
            const rows = container.querySelectorAll('.screening-method-row');
            rows.forEach(r => {
                const btn = r.querySelector('.remove-screening-method-btn');
                if (btn) {
                    if (rows.length > 1) {
                        btn.classList.remove('hidden');
                    } else {
                        btn.classList.add('hidden');
                    }
                }
            });
        }

        function getScreeningMethodsData() {
            const container = document.getElementById('screeningMethodsContainer');
            if (!container) {
                const single = document.getElementById('screeningMethodInput');
                return single && single.value.trim() ? [single.value.trim()] : [];
            }
            const inputs = container.querySelectorAll('.screening-method-input');
            const values = [];
            inputs.forEach(inp => {
                const v = inp.value.trim();
                if (v) values.push(v);
            });
            return values;
        }

        function syncPresumptiveContainerForExhibit(idx, name) {
            const container = document.getElementById('presumptiveTestExhibitsContainer');
            if (!container) return;
            let card = document.getElementById(`presumptiveTestExhibitCard_${idx}`);
            const safeName = name || `Exhibit #${idx}`;

            if (!card) {
                card = document.createElement('div');
                card.id = `presumptiveTestExhibitCard_${idx}`;
                card.className = 'presumptive-exhibit-card p-5 bg-white rounded-2xl border border-slate-300/80 shadow-2xs space-y-4';
                card.setAttribute('data-exhibit-index', idx);
                card.innerHTML = `
                    <div class="flex items-center justify-between border-b border-slate-200 pb-2.5">
                        <div class="flex items-center space-x-2">
                            <span class="w-6 h-6 rounded-full bg-emerald-600 text-white font-extrabold text-xs flex items-center justify-center">${idx}</span>
                            <h5 class="font-extrabold text-slate-900 text-xs uppercase tracking-wider presumptive-card-title">Presumptive Test — Exhibit #${idx}: <span class="text-emerald-700">${safeName}</span></h5>
                        </div>
                        <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase">EXHIBIT #${idx}</span>
                    </div>

                    <div>
                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-2">
                            Presumptive Test Reaction Result <span class="text-red-600 font-bold">*</span>
                        </label>
                        <div class="flex items-center space-x-3">
                            <label class="relative inline-flex items-center px-4 py-2 rounded-xl border-2 border-slate-300 bg-slate-50 hover:bg-slate-100 transition-all cursor-pointer select-none space-x-2 group">
                                <input type="checkbox" id="presumptiveTestYes_${idx}" name="presumptive_test_yes_${idx}" value="YES"
                                    onchange="togglePresumptiveTestForExhibit(${idx}, 'YES')"
                                    class="presumptive-yes-box w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500 cursor-pointer">
                                <span class="font-black text-xs text-slate-900 font-mono-code uppercase">YES</span>
                            </label>
                            <label class="relative inline-flex items-center px-4 py-2 rounded-xl border-2 border-red-300 bg-red-50/60 hover:bg-red-100/70 transition-all cursor-pointer select-none space-x-2 group">
                                <input type="checkbox" id="presumptiveTestNo_${idx}" name="presumptive_test_no_${idx}" value="NO" checked
                                    onchange="togglePresumptiveTestForExhibit(${idx}, 'NO')"
                                    class="presumptive-no-box w-4 h-4 text-red-600 rounded border-slate-300 focus:ring-red-500 cursor-pointer">
                                <span class="font-black text-xs text-red-900 font-mono-code uppercase">NO</span>
                            </label>
                        </div>

                        <input type="hidden" id="presumptiveReactionResultInput_${idx}" name="presumptive_reaction_result_input_${idx}" value="">

                        <div id="presumptiveReactionDetailsContainer_${idx}" class="mt-3.5 p-4 bg-emerald-50/70 rounded-xl border border-emerald-200 space-y-3 hidden">
                            <div class="flex items-center justify-between border-b border-emerald-200/80 pb-2">
                                <div class="flex items-center space-x-2 text-emerald-800 font-extrabold text-xs uppercase tracking-wider">
                                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                                    <span>Presumptive Test Reaction Result Details &amp; Field Spec</span>
                                </div>
                                <span class="text-[10px] font-mono-code font-bold text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-md border border-emerald-200 uppercase">
                                    MULTIPLE SPECIMEN SUPPORT
                                </span>
                            </div>

                            <div id="presumptiveTestRowsContainer_${idx}" class="space-y-2.5">
                                <div class="presumptive-test-row p-3 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-2">
                                    <div class="flex items-center justify-between text-[11px] font-extrabold text-slate-700 uppercase font-mono-code">
                                        <span class="test-row-title">Test Result #1</span>
                                        <button type="button" onclick="removePresumptiveTestRowForExhibit(this, ${idx})" class="remove-test-row-btn hidden px-2 py-0.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg text-[10px] font-bold border border-transparent hover:border-red-200 transition-colors cursor-pointer">&times; Remove</button>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Presence of <span class="text-red-600 font-bold">*</span></label>
                                            <input type="text" class="presumptive-presence-input w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs"
                                                oninput="updatePresumptiveReactionTextForExhibit(${idx})" placeholder="e.g. Cocaine, Heroin, THC, Methamphetamine">
                                        </div>
                                        <div>
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Percentage (%) <span class="text-red-600 font-bold">*</span></label>
                                            <div class="flex items-center rounded-xl shadow-2xs overflow-hidden">
                                                <input type="text" class="presumptive-percentage-input w-full pl-3 pr-2 py-2 bg-white border border-y border-l border-slate-300 rounded-l-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 font-mono-code"
                                                    oninput="updatePresumptiveReactionTextForExhibit(${idx})" placeholder="e.g. 85.5">
                                                <span class="inline-flex items-center px-3 py-2 bg-slate-100 border border-slate-300 rounded-r-xl text-slate-700 font-extrabold text-xs font-mono-code shrink-0">%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-1 flex items-center justify-between">
                                <button type="button" onclick="addPresumptiveTestRowForExhibit(${idx})" class="inline-flex items-center px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-all cursor-pointer border-none space-x-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                    <span>+ Add More Test Result</span>
                                </button>
                                <span class="text-[10px] text-slate-500 font-medium italic">Log additional reaction tests for Exhibit #${idx}</span>
                            </div>
                        </div>
                    </div>
                `;
                container.appendChild(card);
                const revNo = document.getElementById('presumptiveTestNoReview') || document.querySelector('input[name="form_presumptive_radio"][value="NO"]');
                if (revNo && !document.querySelector('input[name="form_presumptive_radio"]:checked')) {
                    revNo.checked = true;
                }
            } else {
                const titleEl = card.querySelector('.presumptive-card-title');
                if (titleEl) titleEl.innerHTML = `Presumptive Test — Exhibit #${idx}: <span class="text-emerald-700">${safeName}</span>`;
            }
        }

        function syncConfirmatoryContainerForExhibit(idx, name, units, gross, unit) {
            const container = document.getElementById('confirmatoryTestExhibitsContainer');
            if (!container) return;
            let card = document.getElementById(`confirmatoryTestExhibitCard_${idx}`);
            const safeName = name || `Exhibit #${idx}`;
            const safeUnits = units || 'N/A';
            const safeGross = gross || 'N/A';
            const safeUnit = unit || 'kg';

            if (!card) {
                card = document.createElement('div');
                card.id = `confirmatoryTestExhibitCard_${idx}`;
                card.className = 'confirmatory-exhibit-card p-5 bg-white rounded-2xl border border-slate-300/80 shadow-2xs space-y-4';
                card.setAttribute('data-exhibit-index', idx);
                card.innerHTML = `
                    <div class="flex items-center justify-between border-b border-slate-200 pb-2.5">
                        <div class="flex items-center space-x-2">
                            <span class="w-6 h-6 rounded-full bg-emerald-600 text-white font-extrabold text-xs flex items-center justify-center">${idx}</span>
                            <h5 class="font-extrabold text-slate-900 text-xs uppercase tracking-wider confirmatory-card-title">Confirmatory Test — Exhibit #${idx}: <span class="text-emerald-700">${safeName}</span></h5>
                        </div>
                        <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase">EXHIBIT #${idx}</span>
                    </div>

                    <div>
                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-2">
                            Confirmatory Test Conducted <span class="text-red-600 font-bold">*</span>
                        </label>
                        <div class="flex items-center space-x-3">
                            <label class="relative inline-flex items-center px-4 py-2 rounded-xl border-2 border-slate-300 bg-slate-50 hover:bg-slate-100 transition-all cursor-pointer select-none space-x-2 group">
                                <input type="checkbox" id="confirmatoryTestYes_${idx}" name="confirmatory_test_yes_${idx}" value="YES"
                                    onchange="toggleConfirmatoryTestForExhibit(${idx}, 'YES')"
                                    class="confirmatory-yes-box w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500 cursor-pointer">
                                <span class="font-black text-xs text-slate-900 font-mono-code uppercase">YES</span>
                            </label>
                            <label class="relative inline-flex items-center px-4 py-2 rounded-xl border-2 border-red-300 bg-red-50/60 hover:bg-red-100/70 transition-all cursor-pointer select-none space-x-2 group">
                                <input type="checkbox" id="confirmatoryTestNo_${idx}" name="confirmatory_test_no_${idx}" value="NO" checked
                                    onchange="toggleConfirmatoryTestForExhibit(${idx}, 'NO')"
                                    class="confirmatory-no-box w-4 h-4 text-red-600 rounded border-slate-300 focus:ring-red-500 cursor-pointer">
                                <span class="font-black text-xs text-red-900 font-mono-code uppercase">NO</span>
                            </label>
                        </div>

                        <input type="hidden" id="confirmatoryTestResultInput_${idx}" name="confirmatory_test_result_${idx}" value="NO">

                        <div id="sampleWeightContainer_${idx}" class="mt-3.5 p-4 bg-emerald-50/70 rounded-xl border border-emerald-200 space-y-4 hidden">
                            <div class="flex items-center justify-between border-b border-emerald-200/80 pb-2">
                                <div class="flex items-center space-x-2 text-emerald-900 font-extrabold text-xs uppercase tracking-wider">
                                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                                    <span>SAMPLE WEIGHT</span>
                                </div>
                                <span class="text-[10px] font-mono-code font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-md border border-emerald-200 uppercase">
                                    CONFIRMATORY METROLOGY &amp; MASS
                                </span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                <div class="md:col-span-2">
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                        <span>Description <span class="text-red-600 font-bold">*</span></span>
                                        <span class="text-[9px] font-mono-code font-bold text-emerald-700 bg-emerald-100/90 px-1.5 py-0.5 rounded border border-emerald-200 uppercase">AUTO-FETCHED</span>
                                    </label>
                                    <input type="text" id="sampleWeightDescription_${idx}" name="sample_weight_description_${idx}" value="${safeName}" readonly
                                        class="w-full px-3 py-2 bg-slate-100/90 border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs outline-none shadow-2xs cursor-not-allowed">
                                </div>

                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                        <span>Number of Units <span class="text-red-600 font-bold">*</span></span>
                                        <span class="text-[9px] font-mono-code font-bold text-emerald-700 bg-emerald-100/90 px-1.5 py-0.5 rounded border border-emerald-200 uppercase">AUTO-FETCHED</span>
                                    </label>
                                    <input type="text" id="sampleWeightUnits_${idx}" name="sample_weight_units_${idx}" value="${safeUnits}" readonly
                                        class="w-full px-3 py-2 bg-slate-100/90 border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs outline-none shadow-2xs font-mono-code cursor-not-allowed">
                                </div>

                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                        <span>Gross Weight <span class="text-red-600 font-bold">*</span></span>
                                        <span class="text-[9px] font-mono-code font-bold text-emerald-700 bg-emerald-100/90 px-1.5 py-0.5 rounded border border-emerald-200 uppercase">AUTO-FETCHED</span>
                                    </label>
                                    <input type="text" id="sampleWeightGross_${idx}" name="sample_weight_gross_${idx}" value="${safeGross}" readonly
                                        class="w-full px-3 py-2 bg-slate-100/90 border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs outline-none shadow-2xs font-mono-code cursor-not-allowed">
                                </div>

                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                        Sample Taken (Weight/Volume) <span class="text-red-600 font-bold">*</span>
                                    </label>
                                    <div class="relative flex rounded-xl shadow-2xs">
                                        <input type="text" id="sampleWeightTaken_${idx}" name="sample_weight_taken_${idx}" placeholder="e.g. 5"
                                            oninput="updateSampleWeightReservedForExhibit(${idx})"
                                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-l-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 font-mono-code placeholder:text-slate-400 border-r-0">
                                        <span id="sampleWeightTakenUnitAddon_${idx}" class="inline-flex items-center px-3 py-2 rounded-r-xl border border-slate-300 bg-slate-100/90 text-slate-700 font-mono-code font-bold text-xs shrink-0 select-none cursor-not-allowed border-l-0">
                                            ${safeUnit}
                                        </span>
                                    </div>
                                    <p id="sampleWeightTakenError_${idx}" class="text-[11px] text-red-600 font-extrabold mt-1 hidden flex items-center space-x-1 animate-in fade-in duration-200">
                                        <svg class="w-3.5 h-3.5 shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>Sample taken cannot exceed the gross weight</span>
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                        <span>Reserved Samples <span class="text-red-600 font-bold">*</span></span>
                                        <span class="text-[9px] font-mono-code font-bold text-emerald-700 bg-emerald-100/90 px-1.5 py-0.5 rounded border border-emerald-200 uppercase">AUTO-CALCULATED</span>
                                    </label>
                                    <input type="text" id="sampleWeightReserved_${idx}" name="sample_weight_reserved_${idx}" readonly
                                        placeholder="Auto-calculated (Gross Weight - Sample Taken)"
                                        class="w-full px-3 py-2 bg-slate-100/90 border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none shadow-2xs font-mono-code placeholder:text-slate-400 cursor-not-allowed">
                                </div>

                                <div class="md:col-span-2 pt-3 border-t border-emerald-200/80 space-y-3.5">
                                    <div class="flex items-center justify-between border-b border-emerald-200/80 pb-2">
                                        <div class="flex items-center space-x-2 text-emerald-900 font-extrabold text-xs uppercase tracking-wider">
                                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>RESULT / CONFIRMATORY ANALYSIS</span>
                                        </div>
                                        <span class="text-[10px] font-mono-code font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-md border border-emerald-200 uppercase">
                                            ANALYTICAL FINDINGS &amp; SPECS
                                        </span>
                                    </div>

                                    <input type="hidden" id="confirmatorySubstanceIdentification_${idx}" name="confirmatory_substance_identification_${idx}" value="${safeName}">

                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between">
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase">
                                                Result (Detected Substances &amp; Purity %) <span class="text-red-600 font-bold">*</span>
                                            </label>
                                            <button type="button" onclick="addDetectedSubstanceRowForExhibit(${idx})"
                                                class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] rounded-xl shadow-2xs transition-all flex items-center space-x-1 border-none cursor-pointer">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                                <span>Add More</span>
                                            </button>
                                        </div>
                                        <div id="detectedSubstancesListContainer_${idx}" class="space-y-2.5"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                container.appendChild(card);
                const revConfNo = document.getElementById('confirmatoryTestNoReview') || document.querySelector('input[name="form_confirmatory_radio"][value="NO"]');
                if (revConfNo && !document.querySelector('input[name="form_confirmatory_radio"]:checked')) {
                    revConfNo.checked = true;
                }
                addDetectedSubstanceRowForExhibit(idx, '', '');
            } else {
                const titleEl = card.querySelector('.confirmatory-card-title');
                if (titleEl) titleEl.innerHTML = `Confirmatory Test — Exhibit #${idx}: <span class="text-emerald-700">${safeName}</span>`;

                const descInput = document.getElementById(`sampleWeightDescription_${idx}`);
                if (descInput && !descInput.getAttribute('data-user-edited')) descInput.value = safeName;

                const unitsInput = document.getElementById(`sampleWeightUnits_${idx}`);
                if (unitsInput) unitsInput.value = safeUnits;

                const grossInput = document.getElementById(`sampleWeightGross_${idx}`);
                if (grossInput) grossInput.value = safeGross;

                const addonInput = document.getElementById(`sampleWeightTakenUnitAddon_${idx}`);
                if (addonInput) addonInput.textContent = safeUnit;

                const subIdInput = document.getElementById(`confirmatorySubstanceIdentification_${idx}`);
                if (subIdInput && !subIdInput.getAttribute('data-user-edited') && (!subIdInput.value || subIdInput.value.trim() === '')) {
                    subIdInput.value = safeName;
                }

                updateSampleWeightReservedForExhibit(idx);
            }
        }

        function syncSection4ExhibitsToLabSection() {
            const selectEl = document.getElementById('labExhibitSelect');
            const badgeEl = document.getElementById('labExhibitCountBadge');
            const descInput = document.getElementById('labExhibitDescriptionInput');
            const labSec = document.getElementById('labAnalysisExclusiveSections') || document.getElementById('section5FormContainer');
            const cardsContainer = document.getElementById('labExhibitCardsContainer');
            const presContainer = document.getElementById('presumptiveTestExhibitsContainer');
            const confContainer = document.getElementById('confirmatoryTestExhibitsContainer');

            const isLabUser = (typeof isLabAnalysis !== 'undefined' && isLabAnalysis) || {{ !empty($isLabAnalysis) ? 'true' : 'false' }};
            const chk = document.getElementById('hasSeizuresCheckbox') || document.getElementById('toggleSeizureItemSectionCheckbox');
            const seizuresDetailsContainer = document.getElementById('seizuresDetailsContainer') || document.getElementById('section4Seizures');
            const hasSeizures = chk ? chk.checked : true;
            const isDetailsVisible = seizuresDetailsContainer ? !seizuresDetailsContainer.classList.contains('hidden') : true;

            const blocks = document.querySelectorAll('#seizuresListContainer .seizure-block');
            const totalBlocks = blocks.length;

            if (chk && (!hasSeizures || !isDetailsVisible) && totalBlocks === 0) {
                if (selectEl) {
                    selectEl.innerHTML = '<option value="">No exhibit saved yet</option>';
                    selectEl.setAttribute('disabled', 'disabled');
                }
                if (badgeEl) badgeEl.innerText = 'No exhibit saved yet';
                if (descInput) descInput.value = 'No exhibit saved yet';
                if (cardsContainer) cardsContainer.innerHTML = '<div class="p-3 bg-white/80 rounded-xl border border-dashed border-emerald-300 text-center text-xs text-slate-400 italic">No exhibits logged in manifest yet.</div>';
                if (presContainer) presContainer.innerHTML = '';
                if (confContainer) confContainer.innerHTML = '';
                return;
            }

            if (totalBlocks === 0) {
                if (selectEl && selectEl.options.length > 0 && selectEl.options[0].value !== '') {
                    Array.from(selectEl.options).forEach((opt, idx) => {
                        const itemNum = idx + 1;
                        const name = opt.getAttribute('data-name') || opt.text || `Exhibit #${itemNum}`;
                        const qty = opt.getAttribute('data-qty') || '';
                        const pkg = opt.getAttribute('data-pkg') || '';
                        const weight = opt.getAttribute('data-weight') || '';
                        const unit = opt.getAttribute('data-unit') || '';
                        const unitsStr = qty ? `${qty} ${pkg}`.trim() : (pkg || '');
                        const grossStr = weight ? `${weight} ${unit}`.trim() : (unit || '');
                        syncPresumptiveContainerForExhibit(itemNum, name);
                        syncConfirmatoryContainerForExhibit(itemNum, name, unitsStr, grossStr, unit);
                    });
                    return;
                }

                if (selectEl) {
                    selectEl.innerHTML = '<option value="">No exhibit saved yet</option>';
                    selectEl.setAttribute('disabled', 'disabled');
                }
                if (badgeEl) badgeEl.innerText = 'No exhibit saved yet';
                if (descInput) descInput.value = 'No exhibit saved yet';
                if (cardsContainer) cardsContainer.innerHTML = '<div class="p-3 bg-white/80 rounded-xl border border-dashed border-emerald-300 text-center text-xs text-slate-400 italic">No exhibits logged in manifest yet.</div>';
                if (presContainer) presContainer.innerHTML = '';
                if (confContainer) confContainer.innerHTML = '';
                return;
            }

            if (selectEl) {
                selectEl.innerHTML = '';
                selectEl.removeAttribute('disabled');
            }
            if (badgeEl) {
                badgeEl.innerText = `${totalBlocks} Exhibit${totalBlocks > 1 ? 's' : ''} in Manifest`;
            }

            const prevLabExNos = {};
            const prevSystemIds = {};
            if (cardsContainer) {
                cardsContainer.querySelectorAll('.lab-exhibit-card').forEach(existingCard => {
                    const cIdx = existingCard.getAttribute('data-exhibit-index');
                    const sysId = existingCard.getAttribute('data-system-exhibit-number');
                    if (cIdx && sysId) prevSystemIds[cIdx] = sysId;
                });
                cardsContainer.querySelectorAll('.card-exhibit-no-input').forEach(existingInp => {
                    const m = (existingInp.id || '').match(/labCardExhibitNo_(\d+)/);
                    if (m) prevLabExNos[m[1]] = existingInp.value;
                });
                cardsContainer.innerHTML = '';
            }

            const baseExhibitSeq = {{ (int) \App\Models\Exhibit::getMaxExhibitSequence() }};

            blocks.forEach((block, idx) => {
                const itemNum = idx + 1;
                const cat = block.querySelector('#newSeizureCategory')?.value || block.querySelector('#exhibitSearchInput')?.value || 'Exhibit';
                let name = block.querySelector('#newSeizureNameDrug')?.value ||
                           block.querySelector('#drugSearchInput')?.value ||
                           block.querySelector('#newSeizureName')?.value ||
                           block.querySelector('#weaponSearchInput')?.value ||
                           block.querySelector('#currencySearchInput')?.value ||
                           block.querySelector('#ammoSearchInput')?.value ||
                           block.querySelector('#deviceSearchInput')?.value ||
                           '';
                if (!name || name.trim() === '') name = cat;

                const contents = block.querySelector('.seizure-contents-input')?.value || block.querySelector('#newSeizureContents')?.value || '';
                const qty = block.querySelector('#newSeizureQuantity')?.value || '';
                const pkg = block.querySelector('#newSeizurePackaging')?.value || block.querySelector('#pkgSearchInput')?.value || '';
                const weight = block.querySelector('#newSeizureWeight')?.value || '';
                const unit = block.querySelector('#newSeizureUnit')?.value || block.querySelector('#unitSearchInput')?.value || '';
                const owner = block.querySelector('.seizure-associated-person-select')?.value || 'All Suspects';

                const systemExhibitId = block.dataset.exhibitNumber || prevSystemIds[itemNum] || ('EXH-' + String(baseExhibitSeq + itemNum).padStart(5, '0'));
                const exhNoVal = (prevLabExNos[itemNum] !== undefined) ? prevLabExNos[itemNum] : (block.dataset.labExhibitNo || '');

                if (selectEl) {
                    const opt = document.createElement('option');
                    opt.value = itemNum.toString();
                    opt.innerText = `Exhibit #${itemNum}: ${name}`;
                    opt.setAttribute('data-name', name);
                    opt.setAttribute('data-cat', cat);
                    opt.setAttribute('data-qty', qty);
                    opt.setAttribute('data-pkg', pkg);
                    opt.setAttribute('data-weight', weight);
                    opt.setAttribute('data-unit', unit);
                    opt.setAttribute('data-contents', contents);
                    opt.setAttribute('data-owner', owner);
                    opt.setAttribute('data-exhibit-number', systemExhibitId);
                    opt.setAttribute('data-lab-exhibit-no', exhNoVal);
                    const isBlockSaved = block.dataset.hasSavedLab === 'true' || block.dataset.hasSavedLab === '1';
                    opt.setAttribute('data-has-saved', isBlockSaved ? 'true' : 'false');
                    selectEl.appendChild(opt);
                }

                if (cardsContainer) {
                    const cardDiv = document.createElement('div');
                    const isActive = (window.activeLabExhibitIndex === itemNum);
                    cardDiv.className = `lab-exhibit-card p-3.5 rounded-xl border transition-all cursor-pointer shadow-2xs ${isActive ? 'border-emerald-500 ring-2 ring-emerald-500/30 bg-emerald-50/40' : 'border-slate-200 bg-white hover:border-emerald-300 hover:bg-slate-50/70'}`;
                    cardDiv.setAttribute('data-exhibit-index', itemNum);
                    cardDiv.setAttribute('data-system-exhibit-number', systemExhibitId);
                    cardDiv.setAttribute('data-lab-exhibit-no', exhNoVal);
                    cardDiv.onclick = () => selectLabExhibitCard(itemNum);

                    let metricStr = '';
                    if (qty || pkg) metricStr += `${qty} ${pkg}`.trim();
                    if (weight || unit) metricStr += (metricStr ? ' | ' : '') + `${weight} ${unit}`.trim();
                    if (!metricStr) metricStr = 'No metrics specified';

                    cardDiv.innerHTML = `
                        <div class="flex items-center justify-between mb-1.5 gap-1.5">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-md uppercase shrink-0">Exhibit #${itemNum}</span>
                                <span class="text-[10px] text-slate-400 font-medium truncate max-w-[110px]">${cat}</span>
                            </div>
                            <span class="lab-card-system-exhibit-id text-emerald-700 text-[10px] font-mono-code font-extrabold tracking-wider uppercase shrink-0" data-system-exhibit-number="${systemExhibitId}" title="System-Generated Exhibit ID">${systemExhibitId}</span>
                        </div>
                        <div class="font-extrabold text-slate-900 text-xs truncate mb-1" title="${name}">${name}</div>
                        <div class="text-[11px] text-slate-600 font-medium flex items-center gap-1 mb-1 truncate" title="Visual Contents: ${contents || 'Not specified'}">
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <span class="truncate"><strong class="font-semibold text-slate-700">Contents:</strong> ${contents || 'N/A'}</span>
                        </div>
                        <div class="flex items-center justify-between text-[10px] text-slate-500 font-mono-code border-t border-slate-100 pt-1.5 mt-1">
                            <span class="truncate font-semibold text-emerald-800">${metricStr}</span>
                            <span class="truncate text-slate-400 max-w-[90px]">${owner}</span>
                        </div>
                        <div class="mt-2.5 pt-2 border-t border-slate-100" onclick="event.stopPropagation()">
                            <label class="block text-slate-800 font-extrabold text-[10px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                <span>Exhibit No.</span>
                                <span class="text-[8px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Exhibit ID</span>
                            </label>
                            <input type="text"
                                id="labCardExhibitNo_${itemNum}"
                                name="lab_exhibit_nos[]"
                                data-field="lab_exhibit_no"
                                value="${exhNoVal}"
                                placeholder="A, B...Ai, Aii, Bi, Bii...., A1, A2, B1, B2"
                                class="card-exhibit-no-input w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-slate-900 font-mono-code font-bold text-xs outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600/30 shadow-2xs"
                                onclick="event.stopPropagation()"
                                oninput="onCardExhibitNoChange(this, ${itemNum})"
                            >
                        </div>
                    `;
                    cardsContainer.appendChild(cardDiv);
                }

                syncPresumptiveContainerForExhibit(itemNum, name);

                const unitsStr = qty ? `${qty} ${pkg}`.trim() : (pkg || '');
                const grossStr = weight ? `${weight} ${unit}`.trim() : (unit || '');
                syncConfirmatoryContainerForExhibit(itemNum, name, unitsStr, grossStr, unit);
            });

            if (presContainer) {
                const presCards = presContainer.querySelectorAll('.presumptive-exhibit-card');
                presCards.forEach(card => {
                    const cIdx = parseInt(card.getAttribute('data-exhibit-index'), 10);
                    if (cIdx > totalBlocks) card.remove();
                });
            }
            if (confContainer) {
                const confCards = confContainer.querySelectorAll('.confirmatory-exhibit-card');
                confCards.forEach(card => {
                    const cIdx = parseInt(card.getAttribute('data-exhibit-index'), 10);
                    if (cIdx > totalBlocks) card.remove();
                });
            }

            if (selectEl) {
                if (selectEl.querySelector(`option[value="${window.activeLabExhibitIndex}"]`)) {
                    selectEl.value = window.activeLabExhibitIndex.toString();
                } else if (selectEl.options.length > 0) {
                    selectEl.options[0].selected = true;
                    window.activeLabExhibitIndex = 1;
                }
            }
            onLabExhibitSelected();
            if (typeof onReviewLabExhibitChange === 'function') {
                onReviewLabExhibitChange(selectEl);
            }
        }

        function onCardExhibitNoChange(input, itemNum) {
            const blocks = document.querySelectorAll('#seizuresListContainer .seizure-block');
            if (blocks[itemNum - 1]) {
                blocks[itemNum - 1].dataset.labExhibitNo = input.value;
            }
            if (window.activeLabExhibitIndex === itemNum) {
                const hiddenInput = document.getElementById('labExhibitNumberInput');
                if (hiddenInput) hiddenInput.value = input.value;
            }
        }

        function onDrugTypeChangeCreate(selectEl) {
            syncSection4ExhibitsToLabSection();
        }

        function onLabExhibitSelected() {
            const selectEl = document.getElementById('labExhibitSelect');
            const descInput = document.getElementById('labExhibitDescriptionInput');
            const exhNumInput = document.getElementById('labExhibitNumberInput');
            const examRefInput = document.getElementById('labExamReferenceInput');

            if (!selectEl) return;
            const selectedOption = selectEl.options[selectEl.selectedIndex];

            if (!selectedOption || !selectEl.value || selectEl.value === '' || selectedOption.text ===
                'No exhibit saved yet' || selectedOption.text.startsWith('Select Seized Exhibit')) {
                if (descInput) descInput.value = 'No exhibit selected';
                return;
            }

            const idx = selectEl.value || '1';
            const paddedIdx = idx.padStart(3, '0');

            const cardExhNoInput = document.getElementById(`labCardExhibitNo_${idx}`);
            if (exhNumInput) exhNumInput.value = (cardExhNoInput && cardExhNoInput.value) ? cardExhNoInput.value : '';
            if (examRefInput) {
                if (!examRefInput.value || examRefInput.value.startsWith('EXAM-') || examRefInput.value.includes('T28')) {
                    const randNum = Math.floor(1000 + Math.random() * 9000);
                    examRefInput.value = `NLA-FM-T${randNum}-F`;
                } else if (examRefInput.value.startsWith('GSA-') || examRefInput.value.startsWith('NLR-') || examRefInput.value.startsWith('CSS-') || examRefInput.value.startsWith('EMU-')) {
                    examRefInput.value = examRefInput.value.replace(/^(GSA|NLR|CSS|EMU)-/, 'NLA-');
                }
            }

            const name = selectedOption.getAttribute('data-name') || '';
            const cat = selectedOption.getAttribute('data-cat') || '';
            const qty = selectedOption.getAttribute('data-qty') || '';
            const pkg = selectedOption.getAttribute('data-pkg') || '';
            const weight = selectedOption.getAttribute('data-weight') || '';
            const unit = selectedOption.getAttribute('data-unit') || '';
            const contents = selectedOption.getAttribute('data-contents') || '';
            const owner = selectedOption.getAttribute('data-owner') || '';

            let formattedDesc = name || cat || 'Exhibit';
            if (cat) formattedDesc += ` | Category: ${cat}`;
            if (contents) formattedDesc += ` | Contents: ${contents}`;
            if (qty || pkg) formattedDesc += ` | Quantity/Packaging: ${qty} ${pkg}`.trim();
            if (weight || unit) formattedDesc += ` | Net Weight/Metrics: ${weight} ${unit}`.trim();
            if (owner) formattedDesc += ` | Suspect / Ownership: ${owner}`;

            if (descInput) {
                if (!window.labExhibitUserEdits || !window.labExhibitUserEdits[idx]) {
                    descInput.value = formattedDesc;
                } else {
                    descInput.value = window.labExhibitUserEdits[idx];
                }
            }

            const sampleDescReview = document.getElementById('sampleWeightDescriptionReview');
            const sampleDescMain = document.getElementById('sampleWeightDescription');
            const subIdReview = document.getElementById('confirmatorySubstanceIdentificationReview');
            const subIdMain = document.getElementById('confirmatorySubstanceIdentification');
            const unitsReview = document.getElementById('sampleWeightUnitsReview');
            const unitsMain = document.getElementById('sampleWeightUnits');
            const grossReview = document.getElementById('sampleWeightGrossReview');
            const grossMain = document.getElementById('sampleWeightGross');
            const unitAddonReview = document.getElementById('sampleWeightTakenUnitAddonReview');

            let uText = (qty && pkg) ? `${qty} ${pkg}`.trim() : (qty || pkg || '');
            let gText = (weight && unit) ? `${weight} ${unit}`.trim() : (weight || unit || uText || '');

            if (sampleDescReview && name) sampleDescReview.value = name;
            if (sampleDescMain && name) sampleDescMain.value = name;
            if (subIdReview && name) subIdReview.value = name;
            if (subIdMain && name) subIdMain.value = name;
            if (unitsReview && uText) unitsReview.value = uText;
            if (unitsMain && uText) unitsMain.value = uText;
            if (grossReview && gText) grossReview.value = gText;
            if (grossMain && gText) grossMain.value = gText;
            if (unitAddonReview && unit) unitAddonReview.textContent = unit;
        }

        window.NACOC_DEBUG = localStorage.getItem('NACOC_DEBUG') === '1' || {{ config('app.debug') ? 'true' : 'false' }};

        if (window.NACOC_DEBUG) {
            console.log('[NACOC DEBUG] Boot flag:', window.NACOC_DEBUG);
            console.log('[NACOC DEBUG] Case ID:', @json($case->id));
            console.log('[NACOC DEBUG] Case Number:', @json($case->case_number));
            console.log('[NACOC DEBUG] Active suspect:', @json($activeSuspectName ?? null));
            console.log('[NACOC DEBUG] Active suspect has drug exhibit:', {{ !empty($activeSuspectHasDrugExhibit) ? 'true' : 'false' }});
            console.log('[NACOC DEBUG] Active suspect has saved lab result:', {{ !empty($activeSuspectHasSavedResult) ? 'true' : 'false' }});
            console.log('[NACOC DEBUG] suspectLabExamSaved:', {{ !empty($suspectLabExamSaved) ? 'true' : 'false' }});
            console.log('[NACOC DEBUG] hasAnySavedLabData:', {{ !empty($hasAnySavedLabData) ? 'true' : 'false' }});
            console.log('[NACOC DEBUG] caseHasAnySavedLab:', {{ !empty($caseHasAnySavedLab) ? 'true' : 'false' }});
            console.log('[NACOC DEBUG] Lab Analysis unit status:', @json($unitProgress['Lab Analysis']['status'] ?? null));
            console.log('[NACOC DEBUG] Unit progress map:', @json($unitProgress));
            console.log('[NACOC DEBUG] Suspect-linked exhibits count:', @json(count($suspectLinkedExhibits ?? [])));
            @php @endphp
            console.log('[NACOC DEBUG] sDossierLabExam (active):', @json($activeSuspectDossierExam ?? null));
            console.log('[NACOC DEBUG] sDossierHasSavedLab (active):', {{ !empty($activeSuspectDossierHasSaved) ? 'true' : 'false' }});
            console.log('[NACOC DEBUG] savedJointLabExam:', @json($savedJointLabExam ?? null));
            console.log('[NACOC DEBUG] $isLabAnalysis:', true);
            console.log('[NACOC DEBUG] $isLabContext:', true);
            console.log('[NACOC DEBUG] $isEvidenceManagement:', false);
            console.log('[NACOC DEBUG] $isDigitalForensics:', false);
        }

        document.addEventListener('DOMContentLoaded', function () {
            if (!window.NACOC_DEBUG) return;
            const readOnly = document.getElementById('section5ReadOnlyDisplay');
            const labCardBadges = document.querySelectorAll('[id^="labCard-"], [data-lab-card]');
            console.log('[NACOC DEBUG] #section5ReadOnlyDisplay exists:', !!readOnly);
            console.log('[NACOC DEBUG] #section5ReadOnlyDisplay hidden:', readOnly ? readOnly.classList.contains('hidden') : 'N/A');
            console.log('[NACOC DEBUG] Read-only display text sample:', readOnly ? readOnly.innerText.slice(0, 200) : 'N/A');
            console.log('[NACOC DEBUG] Rendered LAB cards on page:', labCardBadges.length);
            const labProgEl = document.getElementById('unitCard-labanalysis') || document.getElementById('unitBadge-labanalysis');
            console.log('[NACOC DEBUG] Lab progress DOM element:', labProgEl ? labProgEl.innerText : 'N/A');

            const ro = document.getElementById('section5ReadOnlyDisplay');
            if (ro) {
                console.log('[NACOC DEBUG] Read-only display gates:', {
                    hasAnySavedLab: ro.dataset.hasAnySavedLab,
                    activeHasDrug: ro.dataset.activeHasDrug,
                    activeHasSavedResult: ro.dataset.activeHasSavedResult,
                });
            }
        });
    </script>

</body>

</html>
