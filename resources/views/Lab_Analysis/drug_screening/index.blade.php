@php
    $isStaffUser = auth()->user() instanceof \App\Models\StaffAccount || request()->is('*/staff*') || (auth()->user() && !auth()->user()->is_head);
    $screeningBaseUrl = $isStaffUser ? url('/Lab_Analysis/staff/drug-screening') : url('/Lab_Analysis/drug-screening');
    $screeningCreateUrl = $isStaffUser ? url('/Lab_Analysis/staff/drug-screening/create') : url('/Lab_Analysis/drug-screening/create');
    $screeningReportPdfBase = $isStaffUser ? url('/Lab_Analysis/staff/drug-screening/report/pdf') : url('/Lab_Analysis/drug-screening/report/pdf');
    $screeningPdfBase = $isStaffUser ? url('/Lab_Analysis/staff/drug-screening') : url('/Lab_Analysis/drug-screening');
    $labHomeUrl = $isStaffUser ? url('/Lab_Analysis/staff/dashboard') : url('/Lab_Analysis');

    $authUser = auth()->user();
    $officerDisplayName = $authUser->fullname ?? session('user_name') ?? ($headName !== 'N/A' ? $headName : 'Forensic Analyst');
    $officerParts = preg_split('/\s+/', trim((string) $officerDisplayName)) ?: [];
    $officerInitials = count($officerParts) >= 2
        ? strtoupper(substr($officerParts[0], 0, 1) . substr(end($officerParts), 0, 1))
        : strtoupper(substr((string) $officerDisplayName, 0, 2));

    $perPageInit = (int) request('per_page', 15);
    $totalFilteredInit = method_exists($screenings, 'total') ? $screenings->total() : $screenings->count();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Drug Screening Register | NACOC Forensic Laboratory</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    @endif

    <script src="{{ asset('js/ajax-table.js') }}"></script>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800|jetbrains-mono:400,500,600,700" rel="stylesheet" />

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
        .custom-scrollbar::-webkit-scrollbar, ::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track, ::-webkit-scrollbar-track { background: #EEF2F6; }
        .custom-scrollbar::-webkit-scrollbar-thumb, ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover, ::-webkit-scrollbar-thumb:hover { background: #94A3B8; }
    </style>
</head>
<body class="min-h-screen flex antialiased bg-[#F4F6F8] text-slate-900 selection:bg-emerald-700 selection:text-white">

    <div class="flex min-h-screen lg:h-screen w-full overflow-hidden">
        <!-- SHARED SIDEBAR -->
        @if($isStaffUser)
            @include('staff.partials.sidebar', ['sidebarUnit' => 'Lab Analysis'])
        @else
            @include('Crime_Scene_Support.partials.sidebar')
        @endif

        <!-- MAIN WORKSPACE CONTAINER -->
        <div class="flex-1 flex flex-col min-w-0 bg-[#F4F6F8] overflow-hidden">

            <!-- COMMAND TOPBAR -->
            <header class="h-16 bg-white border-b border-slate-200/80 px-4 sm:px-6 flex items-center justify-between gap-4 shrink-0 z-20">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 border border-slate-200/90 text-emerald-700 flex items-center justify-center shrink-0 shadow-2xs" aria-hidden="true">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <nav class="flex items-center gap-1.5 text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-emerald-700">
                            <a href="{{ $labHomeUrl }}" class="hover:underline truncate">Laboratory Analysis</a>
                            <span class="text-slate-300">/</span>
                            <span class="text-slate-500">Drug Screening</span>
                        </nav>
                        <h1 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight truncate">
                            Drug Screening Register
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
                        <input type="text"
                               id="screeningSearchInput"
                               oninput="handleScreeningTableSearchInput(this.value, 'screeningSearchInput')"
                               value="{{ request()->query('search', '') }}"
                               placeholder="Search subject, alias, Ghana Card, Case #, substance..."
                               aria-label="Search drug screening records"
                               class="w-full pl-10 pr-14 py-2 bg-slate-100/80 border border-slate-200/90 rounded-xl text-xs font-mono-code text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-emerald-600 transition-colors">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-2.5 pointer-events-none">
                            <kbd class="px-1.5 py-0.5 text-[9.5px] font-mono-code font-semibold text-slate-400 bg-white rounded border border-slate-200">Ctrl K</kbd>
                        </span>
                    </div>
                </div>

                <!-- TOPBAR ACTIONS & ANALYST BADGE -->
                <div class="flex items-center gap-2.5 shrink-0">
                    <div class="hidden lg:flex items-center px-3 py-1.5 rounded-xl bg-slate-100 border border-slate-200/90 text-[11px] font-mono-code font-bold text-slate-700 tnum" id="liveClock">
                        {{ gmdate('Y-m-d H:i:s') }} UTC
                    </div>

                    <button type="button"
                            onclick="openGenerateReportModal()"
                            class="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/90 text-xs font-bold shadow-2xs transition-colors"
                            title="Generate SITREP or Personal Screening PDF Report">
                        <svg class="w-3.5 h-3.5 text-slate-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Export Report</span>
                    </button>

                    <a href="{{ $screeningCreateUrl }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-xs shadow-emerald-700/20 transition-colors">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>New Drug Screening</span>
                    </a>

                    <div class="hidden xl:flex items-center gap-2.5 pl-2.5 ml-1 border-l border-slate-200">
                        <div class="text-right">
                            <div class="text-xs font-extrabold text-slate-900 leading-tight truncate max-w-[140px]">{{ $officerDisplayName }}</div>
                            <div class="text-[10.5px] font-mono-code font-semibold text-emerald-700">{{ $isStaffUser ? 'LAU Staff' : 'LAU Command' }}</div>
                        </div>
                        <div class="w-8 h-8 rounded-xl bg-slate-200 border border-slate-300/80 text-slate-800 flex items-center justify-center font-mono-code font-extrabold text-[11px] uppercase">
                            {{ $officerInitials ?: 'LA' }}
                        </div>
                    </div>
                </div>
            </header>

            <!-- MAIN CONTENT AREA -->
            <main class="flex-1 overflow-y-auto px-4 sm:px-6 py-5 space-y-5 custom-scrollbar">

                {{-- TOP RIGHT FLASH NOTIFICATION (WHITE BACKGROUND) --}}
                @if(session('success') || session('status'))
                    <div id="topRightFlashToast"
                         class="fixed top-5 right-5 z-[999999] max-w-md w-[calc(100vw-2.5rem)] bg-white rounded-2xl shadow-2xl border border-slate-200/90 overflow-hidden transition-all duration-300 transform translate-x-0 opacity-100 font-sans"
                         style="position: fixed; top: 1.25rem; right: 1.25rem; z-index: 999999; animation: flashSlideInRight 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;">
                        <div class="p-4 flex items-start gap-3.5 bg-white">
                            <div class="w-9 h-9 rounded-xl bg-emerald-50 border border-emerald-200/80 text-emerald-600 flex items-center justify-center shrink-0 shadow-2xs mt-0.5">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0 pr-1">
                                <div class="flex items-center gap-1.5 font-mono-code font-bold text-[10.5px] uppercase tracking-wider text-emerald-600">
                                    <span>Drug Screening System</span>
                                </div>
                                <div class="text-xs text-slate-800 font-sans mt-1 leading-snug font-semibold">
                                    {{ session('success') ?? session('status') }}
                                </div>
                            </div>
                            <button type="button"
                                    onclick="dismissTopRightFlashToast()"
                                    class="text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors p-1.5 rounded-lg shrink-0"
                                    title="Dismiss notification">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <div class="w-full bg-slate-100 h-1 overflow-hidden">
                            <div id="flashProgressBar" class="h-full bg-emerald-500" style="width: 100%; transition: width 6s linear;"></div>
                        </div>
                    </div>

                    <style>
                        @keyframes flashSlideInRight {
                            0% { transform: translateX(110%); opacity: 0; }
                            100% { transform: translateX(0); opacity: 1; }
                        }
                        @keyframes flashSlideOutRight {
                            0% { transform: translateX(0); opacity: 1; }
                            100% { transform: translateX(110%); opacity: 0; }
                        }
                    </style>

                    <script>
                        function dismissTopRightFlashToast() {
                            const toast = document.getElementById('topRightFlashToast');
                            if (toast) {
                                toast.style.animation = 'flashSlideOutRight 0.3s cubic-bezier(0.4, 0, 1, 1) forwards';
                                setTimeout(() => toast.remove(), 310);
                            }
                        }
                        document.addEventListener('DOMContentLoaded', () => {
                            const bar = document.getElementById('flashProgressBar');
                            if (bar) {
                                setTimeout(() => { bar.style.width = '0%'; }, 50);
                            }
                            setTimeout(dismissTopRightFlashToast, 6000);
                        });
                    </script>
                @endif

                {{-- AUTOMATIC CIVILIAN PDF REPORT PROMPT & AUTO-OPEN POPOVER MODAL (CLEAN GREY SURFACE) --}}
                @if(session('auto_download_pdf'))
                    <div id="civilianPdfPopoverModal"
                         class="fixed inset-0 z-[99999] bg-slate-500/50 backdrop-blur-xs flex items-center justify-center p-4 transition-all duration-200"
                         onclick="if(event.target === this) closeCivilianPdfPopover();">
                        <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all animate-popoverIn"
                             onclick="event.stopPropagation();">
                            <div class="px-6 py-4 bg-slate-100 border-b border-slate-200 flex items-start justify-between gap-4">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 font-mono-code uppercase tracking-wider">Civilian PDF Report Ready</h3>
                                            <span class="px-2 py-0.5 rounded-full text-[9.5px] bg-emerald-100 text-emerald-800 font-extrabold font-mono-code border border-emerald-200">Auto-Print Ready</span>
                                        </div>
                                        <div class="text-[11px] font-mono-code text-slate-500 font-semibold mt-0.5">
                                            NACOC Forensic Laboratory &bull; Toxicology Register
                                        </div>
                                    </div>
                                </div>
                                <button type="button" onclick="closeCivilianPdfPopover()" class="text-slate-500 hover:text-slate-800 hover:bg-slate-200/80 p-1.5 rounded-lg transition-colors" title="Close popover">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            <div class="px-6 py-4 space-y-2.5 text-xs bg-white">
                                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80 leading-relaxed font-semibold text-slate-800">
                                    Official Medical Screening PDF Report generated for <span class="text-emerald-700 font-extrabold">{{ session('civilian_name') ?? 'Civilian' }}</span>.
                                </div>
                                <p class="text-slate-500 text-[11.5px] leading-relaxed">
                                    If the print window was blocked by your browser, click below to open and save as PDF.
                                </p>
                            </div>

                            <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200/80 flex items-center justify-end gap-2.5">
                                <button type="button" onclick="closeCivilianPdfPopover()"
                                        class="px-4 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold transition-colors">
                                    Cancel
                                </button>
                                <a href="{{ session('auto_download_pdf') }}" target="_blank" onclick="closeCivilianPdfPopover()"
                                   class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-colors inline-flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                    <span>Print / Save</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <style>
                        @keyframes popoverIn {
                            0% { transform: scale(0.96); opacity: 0; }
                            100% { transform: scale(1); opacity: 1; }
                        }
                        .animate-popoverIn {
                            animation: popoverIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                        }
                    </style>

                    <script>
                        function closeCivilianPdfPopover() {
                            const popover = document.getElementById('civilianPdfPopoverModal');
                            if (popover) {
                                popover.style.opacity = '0';
                                popover.style.pointerEvents = 'none';
                                setTimeout(() => popover.remove(), 200);
                            }
                        }
                        document.addEventListener('DOMContentLoaded', function () {
                            const pdfUrl = {!! json_encode(session('auto_download_pdf')) !!};
                            if (pdfUrl) {
                                try {
                                    const win = window.open(pdfUrl, '_blank');
                                    if (win) win.focus();
                                } catch (e) {}
                            }
                        });
                    </script>
                @endif

                {{-- PDF GENERATION ERROR NOTICE --}}
                @if(session('pdf_error'))
                    <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-start gap-3.5">
                        <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div class="flex-1 text-xs">
                            <div class="font-bold text-rose-900 font-mono-code uppercase tracking-wider">Civilian PDF Notice</div>
                            <div class="text-rose-800 mt-0.5 font-medium leading-relaxed">{{ session('pdf_error') }}</div>
                        </div>
                    </div>
                @endif

                <!-- 1. LABORATORY DRUG SCREENING OVERVIEW & 4-CELL INTERACTIVE KPI STRIP -->
                <section aria-label="Laboratory Analysis Drug Screening Overview" class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                    <div class="px-5 py-4 sm:px-6 bg-slate-100/90 border-b border-slate-200/80 text-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="space-y-0.5">
                            <div class="text-[10.5px] font-mono-code font-bold uppercase tracking-widest text-emerald-700">
                                Laboratory Analysis Drug Screening Overview
                            </div>
                            <p class="text-xs text-slate-600">
                                Forensic Laboratory toxicology register tracking custodial suspect and civilian drug screening outcomes, declared medications, and certified clinical reports.
                            </p>
                        </div>
                        <span class="px-2.5 py-1 rounded-lg bg-white border border-slate-200/90 text-[10.5px] font-mono-code font-bold text-emerald-800 shrink-0 self-start sm:self-auto">
                            LABORATORY ANALYSIS UNIT
                        </span>
                    </div>

                    <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-slate-200/70">
                        <!-- KPI 1: TOTAL SCREENINGS -->
                        <button type="button"
                                onclick="setScreeningResultFilter(event, '')"
                                class="p-4 sm:px-6 text-left hover:bg-slate-50/90 transition-colors group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-slate-500 group-hover:text-slate-800">Total Screenings</span>
                                <span class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 border border-slate-200/80 flex items-center justify-center shrink-0" aria-hidden="true">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                </span>
                            </div>
                            <div id="kpiTotalScreenings" class="text-2xl font-extrabold text-slate-900 font-mono-code tnum mt-1.5">{{ $totalCount }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">All registered assays</div>
                        </button>

                        <!-- KPI 2: POSITIVE SCREENINGS -->
                        <button type="button"
                                onclick="setScreeningResultFilter(event, 'positive')"
                                class="p-4 sm:px-6 text-left hover:bg-rose-50/40 transition-colors group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-rose-700">Positive Screenings</span>
                                <span class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 border border-rose-200/80 flex items-center justify-center shrink-0" aria-hidden="true">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                </span>
                            </div>
                            <div id="kpiPositiveScreenings" class="text-2xl font-extrabold text-slate-900 font-mono-code tnum mt-1.5">{{ $positiveCount }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Detected substances</div>
                        </button>

                        <!-- KPI 3: NEGATIVE SCREENINGS -->
                        <button type="button"
                                onclick="setScreeningResultFilter(event, 'negative')"
                                class="p-4 sm:px-6 text-left hover:bg-emerald-50/40 transition-colors group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-emerald-700">Negative Screenings</span>
                                <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200/80 flex items-center justify-center shrink-0" aria-hidden="true">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </span>
                            </div>
                            <div id="kpiNegativeScreenings" class="text-2xl font-extrabold text-slate-900 font-mono-code tnum mt-1.5">{{ $negativeCount }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Clear / Not detected</div>
                        </button>

                        <!-- KPI 4: PENDING OUTCOME -->
                        <button type="button"
                                onclick="setScreeningResultFilter(event, 'pending')"
                                class="p-4 sm:px-6 text-left hover:bg-amber-50/40 transition-colors group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-amber-800">Pending Outcome</span>
                                <span class="w-7 h-7 rounded-lg bg-amber-50 text-amber-700 border border-amber-200/80 flex items-center justify-center shrink-0" aria-hidden="true">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </span>
                            </div>
                            <div id="kpiPendingScreenings" class="text-2xl font-extrabold text-slate-900 font-mono-code tnum mt-1.5">{{ $pendingCount }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Awaiting confirmation</div>
                        </button>
                    </div>
                </section>

                <!-- 2. REPORT GENERATION & PERIOD FILTER CONSOLE -->
                <section aria-label="Report Generation & Filter Console" class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                    <div class="px-5 py-3.5 sm:px-6 bg-slate-100/75 border-b border-slate-200/80 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-emerald-700 flex items-center justify-center shrink-0" aria-hidden="true">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </span>
                            <div>
                                <h2 class="text-xs font-extrabold text-slate-900 font-mono-code uppercase tracking-wider">
                                    Report Generation &amp; Period Filter Console
                                </h2>
                                <p class="text-[11px] text-slate-500">
                                    Export personal dossiers or consolidated general, weekly, monthly, and annual PDF registers.
                                </p>
                            </div>
                        </div>

                        <!-- PERIOD PRESETS -->
                        <div class="flex flex-wrap items-center gap-1 bg-slate-200/75 p-1 rounded-xl text-[11px] font-bold font-mono-code">
                            <button type="button" onclick="setConsolePeriod('general')"
                                    class="console-period-btn px-2.5 py-1 rounded-lg transition-all {{ request('period', 'general') === 'general' && !request()->filled('from') && !request()->filled('to') ? 'bg-white text-emerald-800 shadow-2xs font-extrabold' : 'text-slate-600 hover:text-slate-900' }}"
                                    data-period="general">
                                General
                            </button>
                            <button type="button" onclick="setConsolePeriod('weekly')"
                                    class="console-period-btn px-2.5 py-1 rounded-lg transition-all {{ in_array(request('period'), ['weekly', 'week']) ? 'bg-white text-emerald-800 shadow-2xs font-extrabold' : 'text-slate-600 hover:text-slate-900' }}"
                                    data-period="weekly">
                                Weekly
                            </button>
                            <button type="button" onclick="setConsolePeriod('monthly')"
                                    class="console-period-btn px-2.5 py-1 rounded-lg transition-all {{ in_array(request('period'), ['monthly', 'month']) ? 'bg-white text-emerald-800 shadow-2xs font-extrabold' : 'text-slate-600 hover:text-slate-900' }}"
                                    data-period="monthly">
                                Monthly
                            </button>
                            <button type="button" onclick="setConsolePeriod('annually')"
                                    class="console-period-btn px-2.5 py-1 rounded-lg transition-all {{ in_array(request('period'), ['annually', 'annual', 'yearly', 'year']) ? 'bg-white text-emerald-800 shadow-2xs font-extrabold' : 'text-slate-600 hover:text-slate-900' }}"
                                    data-period="annually">
                                Annually
                            </button>
                            <button type="button" onclick="setConsolePeriod('custom')"
                                    class="console-period-btn px-2.5 py-1 rounded-lg transition-all {{ (request()->filled('from') || request()->filled('to')) ? 'bg-white text-emerald-800 shadow-2xs font-extrabold' : 'text-slate-600 hover:text-slate-900' }}"
                                    data-period="custom">
                                Custom Range
                            </button>
                        </div>
                    </div>

                    <div class="p-4 sm:px-6 space-y-3.5 bg-white">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                            <div>
                                <label for="consoleTypeSelect" class="block text-[10.5px] font-mono-code font-bold uppercase text-slate-500 mb-1">
                                    Classification Scope
                                </label>
                                <select id="consoleTypeSelect"
                                        onchange="updateConsoleScope()"
                                        class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:border-emerald-600 focus:bg-white transition-colors">
                                    <option value="all" {{ !request()->filled('type') ? 'selected' : '' }}>All Profiles (Suspects &amp; Civilians)</option>
                                    <option value="suspects" {{ in_array(strtolower(request('type', '')), ['suspects', 'suspect']) ? 'selected' : '' }}>Suspects Only</option>
                                    <option value="civilians" {{ in_array(strtolower(request('type', '')), ['civilians', 'civilian']) ? 'selected' : '' }}>Civilians Only</option>
                                </select>
                            </div>

                            <div>
                                <label for="consoleFromDate" class="block text-[10.5px] font-mono-code font-bold uppercase text-slate-500 mb-1">
                                    From Date
                                </label>
                                <input type="date"
                                       id="consoleFromDate"
                                       value="{{ request('from') }}"
                                       onchange="setConsolePeriod('custom')"
                                       class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:border-emerald-600 focus:bg-white transition-colors">
                            </div>

                            <div>
                                <label for="consoleToDate" class="block text-[10.5px] font-mono-code font-bold uppercase text-slate-500 mb-1">
                                    To Date
                                </label>
                                <input type="date"
                                       id="consoleToDate"
                                       value="{{ request('to') }}"
                                       onchange="setConsolePeriod('custom')"
                                       class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:border-emerald-600 focus:bg-white transition-colors">
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                            <div class="text-[11px] font-mono-code text-slate-500">
                                Active Scope: <strong class="text-slate-800" id="consoleScopeDisplay">{{ request('type') ? ucfirst(request('type')) : 'All Screening Profiles' }}</strong>
                                &bull; Period: <strong class="text-emerald-700" id="consolePeriodDisplay">{{ ucfirst(request('period', 'General')) }}</strong>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button"
                                        onclick="resetReportFilterConsole()"
                                        class="px-3 py-1.5 rounded-xl border border-slate-200 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
                                    Reset
                                </button>
                                <button type="button"
                                        onclick="applyReportFilterToTable()"
                                        class="px-3.5 py-1.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-800 text-xs font-bold shadow-2xs transition-colors inline-flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                    </svg>
                                    <span>Filter Table</span>
                                </button>
                                <button type="button"
                                        onclick="openGenerateReportModal(); toggleQuickModalMode('personal')"
                                        class="px-3.5 py-1.5 rounded-xl border border-slate-300 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition-colors inline-flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <span>Personal Dossier PDF</span>
                                </button>
                                <button type="button"
                                        onclick="generateFilteredPdfReport()"
                                        class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-colors inline-flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                    <span id="generateBtnText">Generate PDF Report</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 3. SCREENING PROFILES TABLE CARD -->
                <section aria-label="Drug Screening Profiles Table" class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">

                    <div class="border-b border-slate-200/80 divide-y divide-slate-100">

                        <!-- ROW 1: TITLE, RECORD BADGE, TYPE PILLS & RESULT PILLS -->
                        <div class="p-4 sm:px-5 flex flex-col xl:flex-row xl:items-center justify-between gap-3">
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-xs font-extrabold uppercase font-mono-code text-slate-800 tracking-wider">Screening Profiles</span>
                                <span id="tableRecordCountBadge" class="px-2.5 py-0.5 bg-emerald-50 border border-emerald-200/80 text-emerald-900 rounded-lg text-[10.5px] font-mono-code font-bold tnum">
                                    {{ $totalFilteredInit }} records
                                </span>
                            </div>

                            <div class="flex flex-wrap items-center gap-2.5">
                                <!-- TYPE FILTER PILLS -->
                                <div class="flex items-center gap-1 p-1 bg-slate-100 border border-slate-200/80 rounded-xl text-xs font-semibold" id="tableTypeFilterGroup">
                                    <span class="px-2 text-[10px] uppercase font-mono-code font-bold text-slate-500">Type:</span>
                                    <a href="{{ request()->fullUrlWithQuery(['type' => null, 'page' => null]) }}"
                                       data-filter-type=""
                                       onclick="setScreeningTypeFilter(event, '')"
                                       class="filter-type-pill px-2.5 py-1 rounded-lg transition-colors {{ !request()->filled('type') ? 'bg-slate-200 text-slate-900 font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                                        All (<span id="typePillAllCount">{{ $totalCount }}</span>)
                                    </a>
                                    <a href="{{ request()->fullUrlWithQuery(['type' => 'suspects', 'page' => null]) }}"
                                       data-filter-type="suspects"
                                       onclick="setScreeningTypeFilter(event, 'suspects')"
                                       class="filter-type-pill px-2.5 py-1 rounded-lg transition-colors {{ in_array(strtolower(request()->query('type', '')), ['suspects', 'suspect']) ? 'bg-sky-600 text-white shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900' }}">
                                        Suspects (<span id="typePillSuspectsCount">{{ $suspectsCount ?? 0 }}</span>)
                                    </a>
                                    <a href="{{ request()->fullUrlWithQuery(['type' => 'civilians', 'page' => null]) }}"
                                       data-filter-type="civilians"
                                       onclick="setScreeningTypeFilter(event, 'civilians')"
                                       class="filter-type-pill px-2.5 py-1 rounded-lg transition-colors {{ in_array(strtolower(request()->query('type', '')), ['civilians', 'civilian']) ? 'bg-teal-700 text-white shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900' }}">
                                        Civilians (<span id="typePillCiviliansCount">{{ $civiliansCount ?? 0 }}</span>)
                                    </a>
                                </div>

                                <!-- RESULT FILTER PILLS -->
                                <div class="flex items-center gap-1 p-1 bg-slate-100 border border-slate-200/80 rounded-xl text-xs font-semibold" id="tableResultFilterGroup">
                                    <span class="px-2 text-[10px] uppercase font-mono-code font-bold text-slate-500">Result:</span>
                                    <a href="{{ request()->fullUrlWithQuery(['result' => null, 'page' => null]) }}"
                                       data-filter-result=""
                                       onclick="setScreeningResultFilter(event, '')"
                                       class="filter-result-pill px-2.5 py-1 rounded-lg transition-colors {{ !request()->filled('result') ? 'bg-slate-200 text-slate-900 font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                                        All
                                    </a>
                                    <a href="{{ request()->fullUrlWithQuery(['result' => 'positive', 'page' => null]) }}"
                                       data-filter-result="positive"
                                       onclick="setScreeningResultFilter(event, 'positive')"
                                       class="filter-result-pill px-2.5 py-1 rounded-lg transition-colors {{ strtolower(request()->query('result', '')) === 'positive' ? 'bg-rose-600 text-white shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900' }}">
                                        Positive
                                    </a>
                                    <a href="{{ request()->fullUrlWithQuery(['result' => 'negative', 'page' => null]) }}"
                                       data-filter-result="negative"
                                       onclick="setScreeningResultFilter(event, 'negative')"
                                       class="filter-result-pill px-2.5 py-1 rounded-lg transition-colors {{ strtolower(request()->query('result', '')) === 'negative' ? 'bg-emerald-600 text-white shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900' }}">
                                        Negative
                                    </a>
                                    <a href="{{ request()->fullUrlWithQuery(['result' => 'pending', 'page' => null]) }}"
                                       data-filter-result="pending"
                                       onclick="setScreeningResultFilter(event, 'pending')"
                                       class="filter-result-pill px-2.5 py-1 rounded-lg transition-colors {{ strtolower(request()->query('result', '')) === 'pending' ? 'bg-amber-600 text-white shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900' }}">
                                        Pending
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- ROW 2: INLINE SEARCH FORM & PER-PAGE SELECTOR -->
                        <div class="px-4 py-2.5 sm:px-5 bg-slate-50/70 flex flex-wrap items-center justify-between gap-3">
                            <form method="GET"
                                  action="{{ $screeningBaseUrl }}"
                                  class="relative w-full sm:w-80"
                                  id="tableSearchForm"
                                  onsubmit="executeScreeningTableSearchImmediate(event)">
                                <input type="hidden" name="type" id="screeningFilterType" value="{{ request()->query('type', '') }}">
                                <input type="hidden" name="result" id="screeningFilterResult" value="{{ request()->query('result', '') }}">
                                <input type="hidden" name="period" id="screeningFilterPeriod" value="{{ request()->query('period', '') }}">
                                <input type="hidden" name="from" id="screeningFilterFrom" value="{{ request()->query('from', '') }}">
                                <input type="hidden" name="to" id="screeningFilterTo" value="{{ request()->query('to', '') }}">

                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 pointer-events-none">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </span>
                                <input type="text"
                                       name="search"
                                       id="tableSearchInput"
                                       value="{{ request()->query('search', '') }}"
                                       oninput="handleScreeningTableSearchInput(this.value, 'tableSearchInput')"
                                       placeholder="Search person, Ghana Card, Case #, purpose..."
                                       class="w-full pl-8.5 pr-8 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:border-emerald-600 transition-colors">
                                <button type="button"
                                        id="clearTableSearchBtn"
                                        onclick="clearScreeningTableSearch(event)"
                                        class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-rose-600 transition-colors {{ request()->filled('search') ? '' : 'hidden' }}"
                                        title="Clear search filter">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </form>

                            <div class="flex items-center gap-2">
                                <label for="screeningPerPageSelect" class="sr-only">Rows per page</label>
                                <select id="screeningPerPageSelect"
                                        onchange="changeScreeningPerPage(this.value)"
                                        class="px-2.5 py-1 bg-white border border-slate-200 rounded-lg text-[11px] font-mono-code font-semibold text-slate-700 focus:outline-none focus:border-emerald-600">
                                    @foreach([15, 25, 50, 100] as $pp)
                                        <option value="{{ $pp }}" {{ $perPageInit === $pp ? 'selected' : '' }}>{{ $pp }} / page</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- TABLE CONTENT CONTAINER (AJAX REPLACED) -->
                    <div id="drugScreeningTableContainer">
                        @include('Lab_Analysis.drug_screening.partials.table')
                    </div>
                </section>

            </main>
        </div>
    </div>

    <!-- SCREENING DOSSIER DETAIL MODAL (CLEAN GREY & WHITE SURFACE) -->
    <div id="screeningDetailModal"
         role="dialog"
         aria-modal="true"
         class="fixed inset-0 z-50 bg-slate-500/50 backdrop-blur-xs hidden flex items-center justify-center p-4"
         onclick="if(event.target && event.target.id === 'screeningDetailModal') closeScreeningModal()">
        <div class="bg-white rounded-2xl max-w-3xl w-full shadow-2xl border border-slate-200 overflow-hidden" onclick="event.stopPropagation()">
            <div class="px-6 py-4 bg-slate-100 border-b border-slate-200 text-slate-900 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="text-xs font-mono-code font-extrabold uppercase tracking-wider text-slate-800">
                        Screening Dossier #<span id="modalScreeningId">001</span>
                    </span>
                    <span id="modalTypeBadge" class="px-2 py-0.5 rounded-md text-[10px] font-mono-code font-bold bg-teal-600 text-white">
                        Suspect
                    </span>
                </div>
                <button type="button"
                        onclick="closeScreeningModal()"
                        aria-label="Close screening dossier modal"
                        class="p-1.5 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-200/80 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="p-6 space-y-4 text-xs max-h-[82vh] overflow-y-auto custom-scrollbar bg-white">
                <!-- Subject Header -->
                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80 flex items-center gap-3">
                    <div id="modalAvatarContainer" class="w-12 h-12 rounded-xl overflow-hidden border border-slate-200 bg-slate-200 text-slate-800 font-extrabold flex items-center justify-center shrink-0 uppercase font-mono-code text-sm">
                        <span id="modalInitials">SP</span>
                    </div>
                    <div class="min-w-0">
                        <div id="modalSuspectName" class="font-extrabold text-sm text-slate-900 truncate">Person Name</div>
                        <div class="text-[11px] text-slate-500 font-mono-code mt-0.5" id="modalSubInfo">
                            Case: <span id="modalCaseNumber" class="font-bold text-emerald-800">NCC-{{ date('Y') }}</span> &bull;
                            ID: <span id="modalGhanaCard" class="text-slate-700 font-semibold">GHA-000</span>
                        </div>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                        <div class="text-[10px] font-mono-code uppercase text-slate-500 font-bold">Screening Date</div>
                        <div id="modalScreeningDate" class="font-bold text-slate-900 mt-0.5 font-mono-code">N/A</div>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                        <div class="text-[10px] font-mono-code uppercase text-slate-500 font-bold">Outcome Result</div>
                        <div id="modalResult" class="font-extrabold mt-0.5 font-mono-code">N/A</div>
                    </div>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                    <div class="text-[10px] font-mono-code uppercase text-slate-500 font-bold">Substance(s) Identified / Found</div>
                    <div id="modalSubstancesFound" class="font-extrabold mt-0.5 font-mono-code text-xs text-slate-800">No drug(s) detected</div>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                    <div class="text-[10px] font-mono-code uppercase text-slate-500 font-bold">Purpose of Screening</div>
                    <div id="modalPurpose" class="font-semibold text-slate-800 mt-0.5">N/A</div>
                </div>

                <!-- Medication Profile Section -->
                <div id="modalMedicationSection" class="p-4 bg-slate-50 rounded-xl border border-slate-200/90 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-[10.5px] font-mono-code uppercase text-slate-800 font-extrabold flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-teal-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                            </svg>
                            <span>Clinical Prescriptions &amp; Medications</span>
                        </span>
                        <span id="modalOnMedicationBadge" class="text-[9.5px] font-mono-code font-bold bg-teal-100 text-teal-900 px-2 py-0.5 rounded">
                            On Meds
                        </span>
                    </div>

                    <div id="modalMedSummaryContainer" class="hidden text-xs font-mono-code font-bold text-teal-900 bg-white p-2.5 rounded-lg border border-slate-200">
                        Summary: <span id="modalMedSummaryText" class="font-normal text-slate-800"></span>
                    </div>

                    <div id="modalMedsTableWrapper" class="hidden overflow-x-auto border border-slate-200 rounded-xl bg-white">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead class="bg-slate-100 text-slate-600 font-mono-code uppercase text-[10px] font-bold border-b border-slate-200">
                                <tr>
                                    <th class="py-2 px-2.5">Medication</th>
                                    <th class="py-2 px-2.5">Dosage</th>
                                    <th class="py-2 px-2.5">Form / Route</th>
                                    <th class="py-2 px-2.5">Frequency</th>
                                    <th class="py-2 px-2.5">Source</th>
                                    <th class="py-2 px-2.5">Schedule &amp; Dates</th>
                                    <th class="py-2 px-2.5">Last Dose</th>
                                </tr>
                            </thead>
                            <tbody id="modalMedsTableBody" class="divide-y divide-slate-100 text-[11px] text-slate-700"></tbody>
                        </table>
                    </div>

                    <div id="modalNoMedsText" class="text-[11px] text-slate-500 italic">
                        No regular medication or clinical prescription recorded for this screening.
                    </div>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                    <div class="text-[10px] font-mono-code uppercase text-slate-500 font-bold">Notes &amp; Remarks</div>
                    <div id="modalNotes" class="text-slate-700 mt-1 whitespace-pre-wrap leading-relaxed">No remarks.</div>
                </div>

                <div class="text-[10px] text-slate-400 font-mono-code text-right">
                    Logged: <span id="modalCreatedAt">N/A</span>
                </div>
            </div>

            <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200/80 flex items-center justify-between gap-3">
                <a id="modalPrintPdfBtn"
                   href="#"
                   target="_blank"
                   class="hidden inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    <span>Print / Save PDF Report</span>
                </a>
                <button type="button"
                        onclick="closeScreeningModal()"
                        class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-xs rounded-xl transition-colors ml-auto">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- GENERATE REPORT QUICK MODAL (CLEAN GREY & WHITE SURFACE) -->
    <div id="generateReportQuickModal"
         role="dialog"
         aria-modal="true"
         class="fixed inset-0 z-50 bg-slate-500/50 backdrop-blur-xs hidden flex items-center justify-center p-4"
         onclick="if(event.target && event.target.id === 'generateReportQuickModal') closeGenerateReportModal()">
        <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl border border-slate-200 overflow-hidden" onclick="event.stopPropagation()">
            <div class="px-6 py-4 bg-slate-100 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 bg-white text-emerald-700 rounded-xl border border-slate-200 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-xs font-mono-code font-extrabold uppercase tracking-wider text-slate-900">Generate Drug Screening Report</h3>
                        <p class="text-[10.5px] text-emerald-700 font-mono-code font-semibold">NACOC Forensic Laboratory &bull; Toxicology Reporting Engine</p>
                    </div>
                </div>
                <button type="button"
                        onclick="closeGenerateReportModal()"
                        aria-label="Close report generator modal"
                        class="p-1.5 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-200/80 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="p-6 space-y-4 text-xs max-h-[80vh] overflow-y-auto custom-scrollbar bg-white">
                <!-- 1. Report Mode Toggle -->
                <div>
                    <label class="block text-[10.5px] font-mono-code font-bold uppercase text-slate-600 mb-1.5">
                        1. Select Report Type
                    </label>
                    <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 border border-slate-200/80 rounded-xl">
                        <button type="button" id="quickBtnRegister" onclick="toggleQuickModalMode('register')"
                                class="py-2 px-3 rounded-lg font-bold font-mono-code text-xs transition-all bg-white text-slate-900 shadow-2xs">
                            Consolidated Register SITREP
                        </button>
                        <button type="button" id="quickBtnPersonal" onclick="toggleQuickModalMode('personal')"
                                class="py-2 px-3 rounded-lg font-bold font-mono-code text-xs transition-all text-slate-600 hover:text-slate-900">
                            Personal Screening Dossier
                        </button>
                    </div>
                </div>

                <!-- 2A. Period Selector (Register Mode) -->
                <div id="quickPeriodSection" class="space-y-2.5">
                    <label class="block text-[10.5px] font-mono-code font-bold uppercase text-slate-600">
                        2. Select Time Horizon / Period
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <button type="button" onclick="selectQuickPeriod('general')" data-quick-period="general"
                                class="quick-period-btn p-2.5 text-center rounded-xl border border-emerald-500 bg-emerald-50 text-emerald-900 font-extrabold font-mono-code text-xs transition-all">
                            General
                            <span class="block text-[10px] font-normal text-emerald-700 mt-0.5">Cumulative All</span>
                        </button>
                        <button type="button" onclick="selectQuickPeriod('weekly')" data-quick-period="weekly"
                                class="quick-period-btn p-2.5 text-center rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold font-mono-code text-xs transition-all">
                            Weekly
                            <span class="block text-[10px] font-normal text-slate-500 mt-0.5">Last 7 Days</span>
                        </button>
                        <button type="button" onclick="selectQuickPeriod('monthly')" data-quick-period="monthly"
                                class="quick-period-btn p-2.5 text-center rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold font-mono-code text-xs transition-all">
                            Monthly
                            <span class="block text-[10px] font-normal text-slate-500 mt-0.5">Last 30 Days</span>
                        </button>
                        <button type="button" onclick="selectQuickPeriod('annually')" data-quick-period="annually"
                                class="quick-period-btn p-2.5 text-center rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold font-mono-code text-xs transition-all">
                            Annually
                            <span class="block text-[10px] font-normal text-slate-500 mt-0.5">Last 365 Days</span>
                        </button>
                    </div>

                    <div class="pt-1">
                        <label class="block text-[10.5px] font-mono-code font-semibold uppercase text-slate-500 mb-1">
                            Or Specify Exact Date Window (Optional)
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <span class="text-[10px] font-mono-code text-slate-400">From Date:</span>
                                <input type="date" id="quickFromDate" class="w-full mt-0.5 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:border-emerald-600 focus:bg-white">
                            </div>
                            <div>
                                <span class="text-[10px] font-mono-code text-slate-400">To Date:</span>
                                <input type="date" id="quickToDate" class="w-full mt-0.5 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:border-emerald-600 focus:bg-white">
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-1">
                        <div>
                            <label class="block text-[10.5px] font-mono-code font-bold uppercase text-slate-600 mb-1">Classification Scope</label>
                            <select id="quickTypeSelect" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:border-emerald-600 focus:bg-white">
                                <option value="all">All (Suspects &amp; Civilians)</option>
                                <option value="suspects">Suspects Only</option>
                                <option value="civilians">Civilians Only</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10.5px] font-mono-code font-bold uppercase text-slate-600 mb-1">Outcome Filter</label>
                            <select id="quickResultSelect" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:border-emerald-600 focus:bg-white">
                                <option value="all">All Outcomes</option>
                                <option value="positive">Positive Outcomes Only</option>
                                <option value="negative">Negative Outcomes Only</option>
                                <option value="pending">Pending Outcomes Only</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 2B. Person Selector (Personal Mode) -->
                <div id="quickPersonalSection" class="space-y-2.5 hidden">
                    <label for="quickPersonSelect" class="block text-[10.5px] font-mono-code font-bold uppercase text-slate-600">
                        2. Select Suspect or Civilian for Personal Report
                    </label>
                    <select id="quickPersonSelect" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:border-emerald-600 focus:bg-white">
                        <option value="">-- Choose Screened Individual --</option>
                        @foreach($allScreenedPersons as $p)
                            <option value="{{ $p['person_name'] }}" data-screening-id="{{ $p['id'] }}">
                                [{{ $p['type'] }}] {{ $p['person_name'] }} &bull; {{ $p['date'] }} ({{ $p['result'] }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-500 leading-relaxed">
                        Generates a formal individual PDF screening dossier with executive SITREP cover page, biological examination parameters, and declared medications.
                    </p>
                </div>

                <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl flex items-start gap-2.5">
                    <svg class="w-4 h-4 text-emerald-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-[11px] text-slate-600 leading-relaxed" id="quickModalSummary">
                        Will generate an official <strong>General Drug Forensic Report</strong> across all registered suspect and civilian screenings.
                    </div>
                </div>
            </div>

            <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200/80 flex items-center justify-between gap-3">
                <button type="button"
                        onclick="closeGenerateReportModal()"
                        class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-xs rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="button"
                        onclick="executeQuickReportModal()"
                        class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-colors inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    <span>Generate &amp; Download PDF</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        const SCREENING_BASE_URL = @json($screeningBaseUrl);
        const SCREENING_REPORT_PDF_BASE = @json($screeningReportPdfBase);
        const SCREENING_PDF_BASE = @json($screeningPdfBase);

        let currentConsolePeriod = @json(request('period', 'general'));
        let quickModalMode = 'register';
        let quickModalPeriod = 'general';
        let lastScreeningSignature = @json($initialSignature ?? null);
        let isPollingScreenings = false;

        function updateScreeningClock() {
            const now = new Date();
            const utcString = now.toISOString().replace('T', ' ').substring(0, 19) + ' UTC';
            const clockEl = document.getElementById('liveClock');
            if (clockEl) clockEl.textContent = utcString;
        }
        setInterval(updateScreeningClock, 1000);
        updateScreeningClock();

        function syncScreeningStatsFromDom(explicitStats = null) {
            const meta = document.getElementById('screeningTableDataMeta');
            if (meta) {
                const badge = document.getElementById('tableRecordCountBadge');
                const total = meta.getAttribute('data-total');
                if (badge && total !== null && total !== undefined) {
                    badge.textContent = total + ' records';
                }
            }

            const stats = explicitStats || (meta && meta.hasAttribute('data-stat-total') ? {
                total: Number(meta.getAttribute('data-stat-total') || 0),
                positive: Number(meta.getAttribute('data-stat-positive') || 0),
                negative: Number(meta.getAttribute('data-stat-negative') || 0),
                pending: Number(meta.getAttribute('data-stat-pending') || 0),
                suspects: Number(meta.getAttribute('data-stat-suspects') || 0),
                civilians: Number(meta.getAttribute('data-stat-civilians') || 0),
            } : null);

            if (!stats) return;
            const map = {
                kpiTotalScreenings: stats.total,
                kpiPositiveScreenings: stats.positive,
                kpiNegativeScreenings: stats.negative,
                kpiPendingScreenings: stats.pending,
                typePillAllCount: stats.total,
                typePillSuspectsCount: stats.suspects,
                typePillCiviliansCount: stats.civilians,
            };
            Object.entries(map).forEach(([id, val]) => {
                const el = document.getElementById(id);
                if (el && val !== undefined) el.textContent = String(val);
            });
        }

        function handleScreeningTableSearchInput(term, sourceId) {
            const cleanTerm = (term || '').trim();

            if (sourceId === 'tableSearchInput') {
                const topInput = document.getElementById('screeningSearchInput');
                if (topInput && topInput.value !== term) topInput.value = term;
            } else if (sourceId === 'screeningSearchInput') {
                const tableInput = document.getElementById('tableSearchInput');
                if (tableInput && tableInput.value !== term) tableInput.value = term;
            }

            const clearBtn = document.getElementById('clearTableSearchBtn');
            if (clearBtn) {
                clearBtn.classList.toggle('hidden', !term || !term.length);
            }

            if (window.drugScreeningAjaxTable) {
                if (window.drugScreeningAjaxTable.debounceTimer) {
                    clearTimeout(window.drugScreeningAjaxTable.debounceTimer);
                }
                window.drugScreeningAjaxTable.debounceTimer = setTimeout(() => {
                    window.drugScreeningAjaxTable.debounceTimer = null;
                    window.drugScreeningAjaxTable.applyFilters({
                        search: cleanTerm || null,
                        page: null
                    });
                }, 300);
            }
        }

        function filterScreeningTable(term, sourceId) {
            handleScreeningTableSearchInput(term, sourceId);
        }

        function executeScreeningTableSearchImmediate(event) {
            if (event) event.preventDefault();
            const tableInput = document.getElementById('tableSearchInput');
            const cleanTerm = tableInput ? (tableInput.value || '').trim() : '';

            if (window.drugScreeningAjaxTable) {
                if (window.drugScreeningAjaxTable.debounceTimer) {
                    clearTimeout(window.drugScreeningAjaxTable.debounceTimer);
                    window.drugScreeningAjaxTable.debounceTimer = null;
                }
                window.drugScreeningAjaxTable.applyFilters({
                    search: cleanTerm || null,
                    page: null
                });
            }
        }

        function clearScreeningTableSearch(event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            const tableInput = document.getElementById('tableSearchInput');
            if (tableInput) tableInput.value = '';
            const topInput = document.getElementById('screeningSearchInput');
            if (topInput) topInput.value = '';

            const clearBtn = document.getElementById('clearTableSearchBtn');
            if (clearBtn) clearBtn.classList.add('hidden');

            if (window.drugScreeningAjaxTable) {
                if (window.drugScreeningAjaxTable.debounceTimer) {
                    clearTimeout(window.drugScreeningAjaxTable.debounceTimer);
                    window.drugScreeningAjaxTable.debounceTimer = null;
                }
                window.drugScreeningAjaxTable.applyFilters({
                    search: null,
                    page: null
                });
            }
        }

        function setScreeningTypeFilter(event, type) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            const normalizedType = type ? type.trim().toLowerCase() : '';
            const hiddenTypeInput = document.getElementById('screeningFilterType');
            if (hiddenTypeInput) hiddenTypeInput.value = normalizedType;

            updateTypeFilterPillsUi(normalizedType);

            if (window.drugScreeningAjaxTable) {
                window.drugScreeningAjaxTable.applyFilters({
                    type: normalizedType || null,
                    page: null
                });
            }
        }

        function updateTypeFilterPillsUi(activeType) {
            const normalized = (activeType || '').toLowerCase();
            document.querySelectorAll('.filter-type-pill').forEach(pill => {
                const pillType = (pill.getAttribute('data-filter-type') || '').toLowerCase();
                const isMatch = (pillType === normalized) || (normalized === '' && pillType === '');

                if (isMatch) {
                    if (pillType === 'suspects' || pillType === 'suspect') {
                        pill.className = 'filter-type-pill px-2.5 py-1 rounded-lg transition-colors bg-sky-600 text-white shadow-2xs font-bold';
                    } else if (pillType === 'civilians' || pillType === 'civilian') {
                        pill.className = 'filter-type-pill px-2.5 py-1 rounded-lg transition-colors bg-teal-700 text-white shadow-2xs font-bold';
                    } else {
                        pill.className = 'filter-type-pill px-2.5 py-1 rounded-lg transition-colors bg-slate-200 text-slate-900 shadow-2xs font-bold';
                    }
                } else {
                    pill.className = 'filter-type-pill px-2.5 py-1 rounded-lg transition-colors text-slate-600 hover:text-slate-900';
                }
            });
        }

        function setScreeningResultFilter(event, result) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            const normalizedResult = result ? result.trim().toLowerCase() : '';
            const hiddenResultInput = document.getElementById('screeningFilterResult');
            if (hiddenResultInput) hiddenResultInput.value = normalizedResult;

            updateResultFilterPillsUi(normalizedResult);

            if (window.drugScreeningAjaxTable) {
                window.drugScreeningAjaxTable.applyFilters({
                    result: normalizedResult || null,
                    page: null
                });
            }
        }

        function updateResultFilterPillsUi(activeResult) {
            const normalized = (activeResult || '').toLowerCase();
            document.querySelectorAll('.filter-result-pill').forEach(pill => {
                const pillResult = (pill.getAttribute('data-filter-result') || '').toLowerCase();
                const isMatch = (pillResult === normalized) || (normalized === '' && pillResult === '');

                if (isMatch) {
                    if (pillResult === 'positive') {
                        pill.className = 'filter-result-pill px-2.5 py-1 rounded-lg transition-colors bg-rose-600 text-white shadow-2xs font-bold';
                    } else if (pillResult === 'negative') {
                        pill.className = 'filter-result-pill px-2.5 py-1 rounded-lg transition-colors bg-emerald-600 text-white shadow-2xs font-bold';
                    } else if (pillResult === 'pending') {
                        pill.className = 'filter-result-pill px-2.5 py-1 rounded-lg transition-colors bg-amber-600 text-white shadow-2xs font-bold';
                    } else {
                        pill.className = 'filter-result-pill px-2.5 py-1 rounded-lg transition-colors bg-slate-200 text-slate-900 shadow-2xs font-bold';
                    }
                } else {
                    pill.className = 'filter-result-pill px-2.5 py-1 rounded-lg transition-colors text-slate-600 hover:text-slate-900';
                }
            });
        }

        function changeScreeningPerPage(val) {
            if (window.drugScreeningAjaxTable) {
                window.drugScreeningAjaxTable.applyFilters({
                    per_page: val,
                    page: 1
                });
            }
        }

        function copyScreeningRef(refValue) {
            if (!refValue || !navigator.clipboard) return;
            navigator.clipboard.writeText(refValue).catch(() => {});
        }

        function syncScreeningFilterUiFromUrl(urlStr) {
            try {
                const url = new URL(urlStr, window.location.origin);
                const searchVal = url.searchParams.get('search') || '';
                const typeVal = url.searchParams.get('type') || '';
                const resultVal = url.searchParams.get('result') || '';

                const tableSearchInput = document.getElementById('tableSearchInput');
                if (tableSearchInput && tableSearchInput.value !== searchVal) {
                    tableSearchInput.value = searchVal;
                }
                const topSearchInput = document.getElementById('screeningSearchInput');
                if (topSearchInput && topSearchInput.value !== searchVal) {
                    topSearchInput.value = searchVal;
                }

                const clearBtn = document.getElementById('clearTableSearchBtn');
                if (clearBtn) {
                    clearBtn.classList.toggle('hidden', !searchVal);
                }

                const hiddenTypeInput = document.getElementById('screeningFilterType');
                if (hiddenTypeInput) hiddenTypeInput.value = typeVal;

                const hiddenResultInput = document.getElementById('screeningFilterResult');
                if (hiddenResultInput) hiddenResultInput.value = resultVal;

                updateTypeFilterPillsUi(typeVal);
                updateResultFilterPillsUi(resultVal);
            } catch (e) {}
        }

        function setConsolePeriod(period) {
            currentConsolePeriod = period;
            document.querySelectorAll('.console-period-btn').forEach(btn => {
                if (btn.getAttribute('data-period') === period) {
                    btn.className = 'console-period-btn px-2.5 py-1 rounded-lg transition-all bg-white text-emerald-800 shadow-2xs font-extrabold';
                } else {
                    btn.className = 'console-period-btn px-2.5 py-1 rounded-lg transition-all text-slate-600 hover:text-slate-900';
                }
            });

            const periodLabel = document.getElementById('consolePeriodDisplay');
            if (periodLabel) {
                periodLabel.textContent = period.charAt(0).toUpperCase() + period.slice(1);
            }

            if (period !== 'custom') {
                const fromInput = document.getElementById('consoleFromDate');
                const toInput = document.getElementById('consoleToDate');
                if (fromInput) fromInput.value = '';
                if (toInput) toInput.value = '';
            }
        }

        function updateConsoleScope() {
            const typeSelect = document.getElementById('consoleTypeSelect');
            const scopeDisplay = document.getElementById('consoleScopeDisplay');
            const genBtnText = document.getElementById('generateBtnText');

            const selectedType = typeSelect?.options[typeSelect.selectedIndex]?.text || 'All Screening Profiles';
            if (scopeDisplay) scopeDisplay.textContent = selectedType;
            if (genBtnText) genBtnText.textContent = 'Generate PDF Report';
        }

        function initDrugScreeningTable() {
            if (typeof initAjaxTable !== 'function') return;

            window.drugScreeningAjaxTable = initAjaxTable({
                container: '#drugScreeningTableContainer',
                table: '#drugScreeningTable',
                form: '#tableSearchForm',
                endpoint: SCREENING_BASE_URL,
                debounce: 300,
                columns: 9,
                rows: 6,
                onAfterUpdate: function () {
                    syncScreeningStatsFromDom();
                    syncScreeningFilterUiFromUrl(window.location.href);
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initDrugScreeningTable);
        } else {
            initDrugScreeningTable();
        }

        window.addEventListener('popstate', function () {
            syncScreeningFilterUiFromUrl(window.location.href);
        });

        function applyReportFilterToTable() {
            const params = new URLSearchParams();
            if (currentConsolePeriod && currentConsolePeriod !== 'general') {
                params.set('period', currentConsolePeriod);
            }

            const type = document.getElementById('consoleTypeSelect')?.value;
            if (type && type !== 'all') params.set('type', type);

            const from = document.getElementById('consoleFromDate')?.value;
            const to = document.getElementById('consoleToDate')?.value;
            if (from) params.set('from', from);
            if (to) params.set('to', to);

            const targetUrl = SCREENING_BASE_URL + (params.toString() ? '?' + params.toString() : '');
            if (window.drugScreeningAjaxTable) {
                window.drugScreeningAjaxTable.fetchData(targetUrl, true);
            } else {
                window.location.href = targetUrl;
            }
        }

        function resetReportFilterConsole() {
            const typeSelect = document.getElementById('consoleTypeSelect');
            if (typeSelect) typeSelect.value = 'all';
            const fromInput = document.getElementById('consoleFromDate');
            if (fromInput) fromInput.value = '';
            const toInput = document.getElementById('consoleToDate');
            if (toInput) toInput.value = '';
            const tableInput = document.getElementById('tableSearchInput');
            if (tableInput) tableInput.value = '';
            const topInput = document.getElementById('screeningSearchInput');
            if (topInput) topInput.value = '';

            setConsolePeriod('general');
            updateConsoleScope();
            updateTypeFilterPillsUi('');
            updateResultFilterPillsUi('');

            if (window.drugScreeningAjaxTable) {
                window.drugScreeningAjaxTable.fetchData(SCREENING_BASE_URL, true);
            } else {
                window.location.href = SCREENING_BASE_URL;
            }
        }

        function generateFilteredPdfReport() {
            const params = new URLSearchParams();
            params.set('period', currentConsolePeriod || 'general');
            const type = document.getElementById('consoleTypeSelect')?.value;
            if (type && type !== 'all') params.set('type', type);

            const from = document.getElementById('consoleFromDate')?.value;
            const to = document.getElementById('consoleToDate')?.value;
            if (from) params.set('from', from);
            if (to) params.set('to', to);

            window.open(SCREENING_REPORT_PDF_BASE + '?' + params.toString(), '_blank');
        }

        function openGenerateReportModal() {
            const modal = document.getElementById('generateReportQuickModal');
            if (modal) {
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }
        }

        function closeGenerateReportModal() {
            const modal = document.getElementById('generateReportQuickModal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        }

        function toggleQuickModalMode(mode) {
            quickModalMode = mode;
            const btnReg = document.getElementById('quickBtnRegister');
            const btnPer = document.getElementById('quickBtnPersonal');
            const secReg = document.getElementById('quickPeriodSection');
            const secPer = document.getElementById('quickPersonalSection');
            const sumEl = document.getElementById('quickModalSummary');

            if (mode === 'personal') {
                if (btnReg) btnReg.className = 'py-2 px-3 rounded-lg font-bold font-mono-code text-xs transition-all text-slate-600 hover:text-slate-900';
                if (btnPer) btnPer.className = 'py-2 px-3 rounded-lg font-bold font-mono-code text-xs transition-all bg-white text-emerald-800 shadow-2xs';
                if (secReg) secReg.classList.add('hidden');
                if (secPer) secPer.classList.remove('hidden');
                if (sumEl) sumEl.innerHTML = 'Will generate an individual <strong>Personal Screening Dossier</strong> for the selected suspect or civilian.';
            } else {
                if (btnReg) btnReg.className = 'py-2 px-3 rounded-lg font-bold font-mono-code text-xs transition-all bg-white text-slate-900 shadow-2xs';
                if (btnPer) btnPer.className = 'py-2 px-3 rounded-lg font-bold font-mono-code text-xs transition-all text-slate-600 hover:text-slate-900';
                if (secReg) secReg.classList.remove('hidden');
                if (secPer) secPer.classList.add('hidden');
                updateQuickSummaryText();
            }
        }

        function selectQuickPeriod(period) {
            quickModalPeriod = period;
            document.querySelectorAll('.quick-period-btn').forEach(btn => {
                if (btn.getAttribute('data-quick-period') === period) {
                    btn.className = 'quick-period-btn p-2.5 text-center rounded-xl border border-emerald-500 bg-emerald-50 text-emerald-900 font-extrabold font-mono-code text-xs transition-all';
                } else {
                    btn.className = 'quick-period-btn p-2.5 text-center rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold font-mono-code text-xs transition-all';
                }
            });
            updateQuickSummaryText();
        }

        function updateQuickSummaryText() {
            const sumEl = document.getElementById('quickModalSummary');
            if (!sumEl) return;
            const names = {
                general: 'General Drug Forensic Report (Cumulative All-Time)',
                weekly: 'Weekly Drug Screening Situation Report (Last 7 Days)',
                monthly: 'Monthly Drug Screening Situation Report (Last 30 Days)',
                annually: 'Annual Drug Screening Situation Report (Last 365 Days)'
            };
            sumEl.innerHTML = 'Will generate an official <strong>' + (names[quickModalPeriod] || 'Consolidated Report') + '</strong>.';
        }

        function executeQuickReportModal() {
            if (quickModalMode === 'personal') {
                const select = document.getElementById('quickPersonSelect');
                const person = select?.value;
                const opt = select?.options[select.selectedIndex];
                const screeningId = opt?.getAttribute('data-screening-id');

                if (screeningId) {
                    closeGenerateReportModal();
                    window.open(SCREENING_PDF_BASE + '/' + screeningId + '/pdf', '_blank');
                    return;
                } else if (person) {
                    closeGenerateReportModal();
                    window.open(SCREENING_REPORT_PDF_BASE + '?report_mode=personal&person=' + encodeURIComponent(person), '_blank');
                    return;
                } else {
                    alert('Please select a suspect or civilian to generate their personal report.');
                    return;
                }
            }

            const params = new URLSearchParams();
            params.set('period', quickModalPeriod);

            const type = document.getElementById('quickTypeSelect')?.value;
            if (type && type !== 'all') params.set('type', type);

            const result = document.getElementById('quickResultSelect')?.value;
            if (result && result !== 'all') params.set('result', result);

            const from = document.getElementById('quickFromDate')?.value;
            const to = document.getElementById('quickToDate')?.value;
            if (from) params.set('from', from);
            if (to) params.set('to', to);

            closeGenerateReportModal();
            window.open(SCREENING_REPORT_PDF_BASE + '?' + params.toString(), '_blank');
        }

        function openScreeningModal(data) {
            document.getElementById('modalScreeningId').textContent = data.id;
            const typeBadge = document.getElementById('modalTypeBadge');
            if (typeBadge) {
                typeBadge.textContent = (data.person_type || 'Suspect').toUpperCase();
                if (data.is_civilian) {
                    typeBadge.className = 'px-2 py-0.5 rounded-md text-[10px] font-mono-code font-bold bg-teal-600 text-white';
                } else {
                    typeBadge.className = 'px-2 py-0.5 rounded-md text-[10px] font-mono-code font-bold bg-sky-600 text-white';
                }
            }
            document.getElementById('modalSuspectName').textContent = data.suspect_name + (data.suspect_alias && data.suspect_alias !== 'N/A' ? ' ("' + data.suspect_alias + '")' : '');
            document.getElementById('modalCaseNumber').textContent = data.case_number;
            document.getElementById('modalGhanaCard').textContent = data.ghana_card;
            document.getElementById('modalScreeningDate').textContent = data.screening_date;
            document.getElementById('modalPurpose').textContent = data.purpose;
            document.getElementById('modalNotes').textContent = data.notes;
            document.getElementById('modalCreatedAt').textContent = data.created_at;

            const avatarContainer = document.getElementById('modalAvatarContainer');
            const initials = data.initials || 'SP';
            if (data.mugshot) {
                avatarContainer.innerHTML = `<img src="${data.mugshot}" alt="${data.suspect_name}" class="w-full h-full object-cover" onerror="this.outerHTML='<span class=\\'font-extrabold font-mono-code text-sm text-slate-800\\'>${initials}</span>'">`;
            } else {
                avatarContainer.innerHTML = `<span class="font-extrabold font-mono-code text-sm ${data.is_civilian ? 'text-teal-800' : 'text-slate-800'}">${initials}</span>`;
            }

            const resEl = document.getElementById('modalResult');
            resEl.textContent = data.result.toUpperCase();
            if (data.result.toLowerCase() === 'positive') {
                resEl.className = 'font-extrabold mt-0.5 font-mono-code text-rose-600';
            } else if (data.result.toLowerCase() === 'negative') {
                resEl.className = 'font-extrabold mt-0.5 font-mono-code text-emerald-600';
            } else {
                resEl.className = 'font-extrabold mt-0.5 font-mono-code text-amber-600';
            }

            const subEl = document.getElementById('modalSubstancesFound');
            if (subEl) {
                subEl.textContent = data.substances_found || (data.result.toLowerCase() === 'negative' ? 'No drug(s) detected' : 'None specified');
                if (data.result.toLowerCase() === 'positive') {
                    subEl.className = 'font-extrabold mt-0.5 font-mono-code text-xs text-rose-700';
                } else if (data.result.toLowerCase() === 'negative') {
                    subEl.className = 'font-extrabold mt-0.5 font-mono-code text-xs text-emerald-700';
                } else {
                    subEl.className = 'font-extrabold mt-0.5 font-mono-code text-xs text-amber-700';
                }
            }

            const medBadge = document.getElementById('modalOnMedicationBadge');
            const medSummaryBox = document.getElementById('modalMedSummaryContainer');
            const medSummaryText = document.getElementById('modalMedSummaryText');
            const medsTableWrapper = document.getElementById('modalMedsTableWrapper');
            const medsTbody = document.getElementById('modalMedsTableBody');
            const noMedsText = document.getElementById('modalNoMedsText');

            if (data.on_medication) {
                if (medBadge) {
                    medBadge.textContent = 'ON MEDICATION';
                    medBadge.className = 'text-[9.5px] font-mono-code font-bold bg-teal-600 text-white px-2 py-0.5 rounded';
                }
                if (medSummaryBox && data.medication_summary) {
                    medSummaryBox.classList.remove('hidden');
                    if (medSummaryText) medSummaryText.textContent = data.medication_summary;
                } else if (medSummaryBox) {
                    medSummaryBox.classList.add('hidden');
                }

                if (noMedsText) noMedsText.classList.add('hidden');

                if (medsTbody && Array.isArray(data.medications) && data.medications.length > 0) {
                    medsTbody.innerHTML = '';
                    data.medications.forEach(m => {
                        const tr = document.createElement('tr');
                        tr.className = 'hover:bg-slate-50';
                        tr.innerHTML = `
                            <td class="py-2 px-2.5 font-bold text-slate-900">${m.medication_name}</td>
                            <td class="py-2 px-2.5 font-mono-code text-slate-700">${m.dosage || '—'}</td>
                            <td class="py-2 px-2.5 text-slate-700">${m.form || '—'} ${m.route && m.route !== '—' ? '(' + m.route + ')' : ''}</td>
                            <td class="py-2 px-2.5 text-slate-700">${m.frequency || '—'}</td>
                            <td class="py-2 px-2.5 text-slate-700">${m.source_label || m.prescription_source || '—'}</td>
                            <td class="py-2 px-2.5 font-mono-code text-[10.5px] text-slate-600">
                                ${(m.date_started && m.date_started !== '—') ? '<div><span class="font-bold text-slate-800">Started:</span> ' + m.date_started + '</div>' : ''}${(m.duration_ongoing && m.duration_ongoing !== '—') ? '<div><span class="font-bold text-slate-800">Duration:</span> ' + m.duration_ongoing + '</div>' : ''}${(!m.date_started || m.date_started === '—') && (!m.duration_ongoing || m.duration_ongoing === '—') ? '—' : ''}
                            </td>
                            <td class="py-2 px-2.5 font-mono-code text-[10.5px] text-slate-600">
                                ${(m.last_dose_taken && m.last_dose_taken !== '—') ? '<span class="font-bold text-teal-800">' + m.last_dose_taken + '</span>' : '—'}
                            </td>
                        `;
                        medsTbody.appendChild(tr);
                    });
                    if (medsTableWrapper) medsTableWrapper.classList.remove('hidden');
                } else if (medsTableWrapper) {
                    medsTableWrapper.classList.add('hidden');
                }
            } else {
                if (medBadge) {
                    medBadge.textContent = 'NO MEDICATIONS';
                    medBadge.className = 'text-[9.5px] font-mono-code font-bold bg-slate-200 text-slate-700 px-2 py-0.5 rounded';
                }
                if (medSummaryBox) medSummaryBox.classList.add('hidden');
                if (medsTableWrapper) medsTableWrapper.classList.add('hidden');
                if (noMedsText) noMedsText.classList.remove('hidden');
            }

            const printPdfBtn = document.getElementById('modalPrintPdfBtn');
            if (printPdfBtn) {
                if (data.pdf_url || data.raw_id) {
                    printPdfBtn.href = data.pdf_url || (SCREENING_PDF_BASE + '/' + data.raw_id + '/pdf');
                    printPdfBtn.classList.remove('hidden');
                } else {
                    printPdfBtn.classList.add('hidden');
                }
            }

            const modal = document.getElementById('screeningDetailModal');
            if (modal) {
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }
        }

        function closeScreeningModal() {
            const modal = document.getElementById('screeningDetailModal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        }

        /* ── Diff-Guarded 5-Second Silent Background Polling ── */
        async function pollDrugScreeningTable(force = false) {
            if (isPollingScreenings || (!force && document.hidden)) return;
            if (window.drugScreeningAjaxTable && (window.drugScreeningAjaxTable.isLoading || window.drugScreeningAjaxTable.debounceTimer)) return;

            const detailModal = document.getElementById('screeningDetailModal');
            const reportModal = document.getElementById('generateReportQuickModal');
            const civModal = document.getElementById('civilianPdfPopoverModal');
            if ((detailModal && !detailModal.classList.contains('hidden')) ||
                (reportModal && !reportModal.classList.contains('hidden')) ||
                civModal) return;

            isPollingScreenings = true;
            try {
                const params = new URLSearchParams(window.location.search);
                params.set('poll', '1');

                const res = await fetch(SCREENING_BASE_URL + '?' + params.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                if (!res.ok) return;

                const data = await res.json();
                if (!data || !data.success) return;

                if (data.stats) {
                    syncScreeningStatsFromDom(data.stats);
                }

                if (!force && data.signature && data.signature === lastScreeningSignature) {
                    return;
                }
                if (data.signature) {
                    lastScreeningSignature = data.signature;
                }

                const container = document.getElementById('drugScreeningTableContainer');
                if (container && typeof data.html === 'string') {
                    const prevScroll = container.querySelector('.overflow-x-auto')?.scrollLeft || 0;
                    container.innerHTML = data.html;
                    if (prevScroll > 0) {
                        const nextWrap = container.querySelector('.overflow-x-auto');
                        if (nextWrap) nextWrap.scrollLeft = prevScroll;
                    }
                    syncScreeningStatsFromDom(data.stats || null);
                }
            } catch (e) {
            } finally {
                isPollingScreenings = false;
            }
        }

        setInterval(() => pollDrugScreeningTable(false), 5000);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) pollDrugScreeningTable(false);
        });

        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                const topSearch = document.getElementById('screeningSearchInput');
                const tblSearch = document.getElementById('tableSearchInput');
                const target = (topSearch && topSearch.offsetParent !== null) ? topSearch : tblSearch;
                if (target) {
                    e.preventDefault();
                    target.focus();
                    target.select();
                }
            }
            if (e.key === 'Escape') {
                closeScreeningModal();
                closeGenerateReportModal();
                if (typeof closeCivilianPdfPopover === 'function') closeCivilianPdfPopover();
            }
        });
    </script>
</body>
</html>
