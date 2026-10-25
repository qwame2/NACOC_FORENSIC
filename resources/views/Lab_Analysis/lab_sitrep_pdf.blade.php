@php
    $authUser = auth()->user();

    // Total dossier page calculation
    $totalDossierPages = 2; // Cover (1) + Dossier (1+)
    if (count($filteredSeizures ?? []) > 5) {
        $totalDossierPages = 3;
    }

    $johnSig = $labHeadSignature ?? null;
    if (!empty($johnSig)) {
        $johnSig = trim((string)$johnSig);
        if (!str_starts_with($johnSig, 'data:image') && !str_starts_with($johnSig, 'http://') && !str_starts_with($johnSig, 'https://') && !str_starts_with($johnSig, '/')) {
            $johnSig = asset($johnSig);
        }
    }

    $anitaSig = $deptHeadSignature ?? null;
    if (!empty($anitaSig)) {
        $anitaSig = trim((string)$anitaSig);
        if (!str_starts_with($anitaSig, 'data:image') && !str_starts_with($anitaSig, 'http://') && !str_starts_with($anitaSig, 'https://') && !str_starts_with($anitaSig, '/')) {
            $anitaSig = asset($anitaSig);
        }
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NACOC Laboratory Analysis Unit &bull; Situational Report (SITREP)</title>
    
    <!-- Scripts & Styles -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        @page {
            size: A4 portrait;
            margin: 0 !important;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #0f172a;
            color: #1e293b;
            margin: 0;
            padding: 24px 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .font-serif-header {
            font-family: 'Cinzel', Georgia, serif;
            letter-spacing: 0.04em;
        }

        .font-mono-code {
            font-family: 'JetBrains Mono', monospace;
        }

        .font-times {
            font-family: 'Times New Roman', Times, Baskerville, Georgia, serif;
        }

        .a4-page {
            width: 210mm;
            min-height: 297mm;
            padding: 10mm 12mm 10mm 12mm;
            margin: 0 auto 24px auto;
            background: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
            box-sizing: border-box;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            page-break-after: always;
            break-after: page;
        }

        /* Cover Page: Exactly 1 static A4 sheet matching multi_dpf_pdf */
        .cover-page {
            width: 210mm;
            height: 297mm;
            max-height: 297mm;
            min-height: 297mm;
            padding: 14mm 14mm 12mm 14mm;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #ffffff;
        }

        /* Dynamic Dossier Pages */
        .dynamic-dossier-page {
            width: 210mm;
            min-height: 297mm;
            height: auto;
            padding: 10mm 12mm 10mm 12mm;
            box-sizing: border-box;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #ffffff;
        }

        .page-break {
            page-break-after: always;
            break-after: page;
        }

        .avoid-break {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .avoid-break-after {
            page-break-after: avoid !important;
            break-after: avoid !important;
        }

        .print-fixed-watermark {
            display: none;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 0 !important;
            }

            body {
                background-color: transparent !important;
                color: #0f172a !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .action-bar-container {
                display: none !important;
            }

            .print-fixed-watermark {
                position: fixed !important;
                top: 50% !important;
                left: 50% !important;
                transform: translate(-50%, -50%) !important;
                width: 550px !important;
                height: 550px !important;
                pointer-events: none !important;
                z-index: 0 !important;
                opacity: 0.025 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }

            .cover-page {
                box-shadow: none !important;
                margin: 0 !important;
                width: 210mm !important;
                height: 297mm !important;
                max-height: 297mm !important;
                min-height: 297mm !important;
                padding: 14mm 14mm 12mm 14mm !important;
                overflow: hidden !important;
                page-break-after: always !important;
                break-after: page !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                background: #ffffff !important;
            }

            .dynamic-dossier-page {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 10mm 12mm 10mm 12mm !important;
                width: 210mm !important;
                min-height: 297mm !important;
                overflow: hidden !important;
                background: #ffffff !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                page-break-after: always !important;
                break-after: page !important;
            }

            .dynamic-dossier-page > .dossier-watermark {
                display: none !important;
            }

            table {
                width: 100% !important;
            }

            tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            thead {
                display: table-header-group !important;
            }
        }
    </style>
</head>
<body class="antialiased">

    <!-- PRINT-SPECIFIC FIXED WATERMARK -->
    <div class="print-fixed-watermark select-none pointer-events-none" aria-hidden="true">
        <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[550px] h-[550px] object-contain filter grayscale" />
    </div>

    <!-- FLOATING ACTION & FILTER CONTROL BAR (SCREEN VIEW ONLY) -->
    <div class="action-bar-container max-w-[210mm] mx-auto mb-4 px-2 space-y-3 select-none">
        <!-- TOP ROW: NAVIGATION & ACTION BUTTONS -->
        <div class="bg-slate-900/90 backdrop-blur-md border border-slate-700/80 rounded-2xl p-3 px-4 flex items-center justify-between shadow-xl">
            <div class="flex items-center gap-3">
                <a href="{{ url('/Lab_Analysis/report') }}" class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-semibold flex items-center gap-2 transition-colors border border-slate-700 shadow-sm no-underline">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Report Console</span>
                </a>

                <div class="hidden sm:flex items-center gap-2 pl-2 border-l border-slate-700">
                    <span class="text-xs font-bold text-slate-300 font-mono-code tracking-wide">
                        LAB ANALYSIS SITREP
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="hidden md:inline-flex px-3 py-1 bg-emerald-950/80 text-emerald-300 border border-emerald-800 rounded-xl text-xs font-mono-code font-bold">
                    <span id="statCasesCount">{{ count($filteredSeizures) }}</span> Drug Cases | <span id="statSuspectsCount">{{ $totalSuspectsCount }}</span> Suspect(s)
                </span>

                <button onclick="handlePrintReport()" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white rounded-xl text-xs font-bold flex items-center gap-2 transition-all shadow-md shadow-emerald-900/40 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    <span>Print / Save PDF</span>
                </button>
            </div>
        </div>

        <!-- SECOND ROW: INTERACTIVE FILTERS & SEARCH -->
        <div class="bg-slate-900/90 backdrop-blur-md border border-slate-700/80 rounded-2xl p-3 px-4 shadow-xl">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <!-- PERIOD PRESETS -->
                <div class="flex items-center gap-1.5 bg-slate-950/70 p-1 rounded-xl border border-slate-800">
                    <button type="button" onclick="setPeriodFilter('weekly')" class="preset-btn px-3 py-1 rounded-lg text-xs font-bold transition-all {{ ($rawPeriod ?? 'weekly') === 'weekly' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-400 hover:text-white' }}" data-preset="weekly">
                        Weekly (SITREP)
                    </button>
                    <button type="button" onclick="setPeriodFilter('monthly')" class="preset-btn px-3 py-1 rounded-lg text-xs font-bold transition-all {{ ($rawPeriod ?? '') === 'monthly' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-400 hover:text-white' }}" data-preset="monthly">
                        Monthly
                    </button>
                    <button type="button" onclick="setPeriodFilter('annual')" class="preset-btn px-3 py-1 rounded-lg text-xs font-bold transition-all {{ in_array($rawPeriod ?? '', ['annual', 'annually']) ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-400 hover:text-white' }}" data-preset="annual">
                        Annual
                    </button>
                    <button type="button" onclick="setPeriodFilter('all')" class="preset-btn px-3 py-1 rounded-lg text-xs font-bold transition-all {{ ($rawPeriod ?? '') === 'all' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-400 hover:text-white' }}" data-preset="all">
                        All Records
                    </button>
                </div>

                <!-- LIVE SEARCH INPUT -->
                <div class="relative flex-1 min-w-[220px]">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 pointer-events-none">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input type="text" id="liveSearchInput" value="{{ $searchQuery ?? '' }}" placeholder="Filter suspect, exhibit, source, remarks..." class="w-full pl-9 pr-8 py-1.5 bg-slate-950/80 border border-slate-700/80 rounded-xl text-xs font-mono-code text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all" oninput="applyClientFilter()">
                    <button type="button" onclick="clearLiveSearch()" id="clearSearchBtn" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-white {{ empty($searchQuery) ? 'hidden' : '' }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- DATE RANGE PICKERS -->
                <div class="flex items-center gap-2 text-xs">
                    <span class="text-slate-400 font-mono-code text-[11px]">From:</span>
                    <input type="date" id="filterFromDate" value="{{ $fromDate ?? '' }}" class="bg-slate-950/80 border border-slate-700/80 text-slate-200 text-xs rounded-xl px-2.5 py-1 focus:outline-none focus:border-emerald-500 font-mono-code" onchange="applyDateRange()">
                    <span class="text-slate-400 font-mono-code text-[11px]">To:</span>
                    <input type="date" id="filterToDate" value="{{ $toDate ?? '' }}" class="bg-slate-950/80 border border-slate-700/80 text-slate-200 text-xs rounded-xl px-2.5 py-1 focus:outline-none focus:border-emerald-500 font-mono-code" onchange="applyDateRange()">
                    <button type="button" onclick="resetAllFilters()" title="Reset All Filters" class="p-1.5 text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 rounded-xl transition-colors border border-slate-700">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- PRINTABLE DOCUMENT ROOT -->
    <div id="sitrepPrintableDossier" class="printable-document">

        <!-- ============================================================
             PAGE 1: EXACT EXECUTIVE COVER PAGE (IDENTICAL TO MULTI_DPF_PDF)
             ============================================================ -->
        <div class="a4-page cover-page page-break relative text-slate-900 select-none">

            <!-- SUBTLE NACOC WATERMARK -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.025] select-none">
                <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[550px] h-[550px] object-contain filter grayscale" />
            </div>

            <div class="relative z-10 flex flex-col justify-between h-full min-h-0">

                <!-- TOP BADGES -->
                <div class="pt-2 flex items-center justify-between text-[8px] font-mono-code select-none">
                    <div class="text-left">
                        <p class="font-bold uppercase tracking-wider text-slate-900">REPUBLIC OF GHANA</p>
                        <p class="mt-0.5 text-slate-700">ISO/IEC 17025 ACCREDITED FORENSIC LABORATORY</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold uppercase tracking-wider text-slate-900">Confidential</p>
                        <p class="mt-0.5 text-[9px] font-bold uppercase text-slate-800" id="page1HeaderBadge">
                            DOSSIER COVER &bull; Page 1 of <span class="doc-total-pages">{{ $totalDossierPages }}</span>
                        </p>
                    </div>
                </div>

                <!-- ALL CENTERED CONTENT IN THE MIDDLE OF THE PAGE (EXACT SAME LAYOUT HAS MULTI_DPF_PDF) -->
                <div class="my-auto flex flex-col items-center justify-center text-center w-full max-w-2xl mx-auto py-2">
                    
                    <!-- INSTITUTION DETAILS -->
                    <div>
                        <h1 class="text-xl font-bold uppercase tracking-wider text-slate-900 font-serif-header">
                            NARCOTICS CONTROL COMMISSION
                        </h1>
                        <p class="mt-1 text-xs font-semibold uppercase tracking-[0.14em] text-slate-700">
                            DEPARTMENT OF FORENSIC
                        </p>
                        <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-600 font-mono-code">
                            LABORATORY ANALYSIS UNIT (LAU)
                        </p>
                    </div>

                    <!-- INCREASED SIZE EMBLEM LOGO IN THE MIDDLE -->
                    <div class="my-6 flex items-center justify-center">
                        <img src="{{ asset('NACOC_LOGO.PNG') }}" alt="NACOC Emblem Logo" class="w-160 h-160 max-w-[180px] max-h-[180px] object-contain drop-shadow-md" />
                    </div>

                    <!-- CASE FILE & REPORT TITLE -->
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.25em] text-slate-900">
                            OFFICIAL FORENSIC DRUG ANALYSIS &amp; CHEMICAL REGISTER
                        </p>
                        <h2 class="mt-2 text-2xl font-bold uppercase tracking-wide text-slate-950 font-serif-header">
                            LABORATORY ANALYSIS UNIT
                        </h2>
                        <p class="mt-1 text-sm font-semibold uppercase tracking-[0.14em] text-slate-700">
                            Preliminary Forensic Examination &amp; Drug Testing Log
                        </p>
                        <div class="mx-auto mt-4 h-[2px] w-20 bg-emerald-700"></div>
                        <p class="mt-5 text-base font-extrabold uppercase tracking-wider text-emerald-800" id="coverReportTypeTitle">
                            {{ $reportTypeLabel ?? 'WEEKLY SITUATIONAL REPORT (SITREP)' }}
                        </p>
                        <p class="mt-2 text-[13px] font-extrabold uppercase tracking-wider font-times text-emerald-900" id="coverPeriodDisplay">
                            {{ $periodLabel ?? 'Period: 20 September – 27 September 2026' }}
                        </p>
                        @if(!empty($selectedSuspect) && strtolower($selectedSuspect) !== 'all')
                            <p class="mt-2 text-[13px] font-black uppercase tracking-wider text-slate-900 font-serif-header">
                                REPUBLIC VS {{ strtoupper($selectedSuspect) }}
                            </p>
                            <p class="mt-1 text-[10px] font-bold uppercase tracking-wider text-emerald-700 font-mono-code">
                                INDIVIDUAL SUSPECT CHEMICAL DOSSIER
                            </p>
                        @else
                            <p class="mt-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-700 font-mono-code">
                                ALL DRUG-RELATED CASES &amp; SUSPECT REGISTRY
                            </p>
                        @endif
                    </div>

                    <!-- CONFIDENTIAL & PROPRIETARY LAW ENFORCEMENT RECORD BOX -->
                    <div class="mt-8 w-full">
                        <div class="p-4 text-center font-mono-code text-[10px] text-slate-700 space-y-1.5 border border-slate-300 rounded-xl bg-slate-50/70 shadow-2xs">
                            <p class="font-bold uppercase tracking-wider text-slate-900">
                                CONFIDENTIAL &amp; PROPRIETARY LAW ENFORCEMENT RECORD
                            </p>
                            <p class="leading-relaxed">
                                This official situational dossier contains classified forensic chemistry observations, drug screening results, and chain-of-custody intelligence. Unauthorized copying or redistribution is strictly prohibited. Detailed suspect examination records follow.
                            </p>
                        </div>
                    </div>

                    <!-- SIGN-OFF AUTHENTICATION ACCREDITATION PREVIEW -->
                    <div class="mt-6 w-full grid grid-cols-2 gap-8 text-left border-t border-slate-200 pt-3">
                        <div class="text-[9px] font-mono-code">
                            <span class="text-slate-500 uppercase tracking-wider block text-[8px] font-bold">Compiling Unit:</span>
                            <span class="font-bold text-slate-800">Laboratory Analysis Unit (LAU)</span>
                            <span class="block text-slate-600 text-[8px]">NACOC National Headquarters, Accra</span>
                        </div>
                        <div class="text-right text-[9px] font-mono-code">
                            <span class="text-slate-500 uppercase tracking-wider block text-[8px] font-bold">Validation Authority:</span>
                            <span class="font-bold text-slate-800">{{ $labHeadName }}</span>
                            <span class="block text-slate-600 text-[8px]">Head of Laboratory Analysis Unit</span>
                        </div>
                    </div>

                </div>

                <!-- COVER PAGE FOOTER -->
                <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between text-[8px] text-slate-500 font-mono-code select-none footer">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-700 tracking-wider">NACOC / LAU UNIT</span>
                        <span>&bull;</span>
                        <span>Executive Directory Cover Page</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span>STRICTLY CONFIDENTIAL</span>
                        <span>&bull;</span>
                        <span class="font-bold text-slate-800">Page 1 of <span class="doc-total-pages">{{ $totalDossierPages }}</span></span>
                    </div>
                </div>

            </div>
        </div>


        <!-- ============================================================
             PAGE 2+: EXECUTIVE FORENSIC DOSSIER & SITUATIONAL LOG
             ============================================================ -->
        <div class="a4-page dynamic-dossier-page relative text-slate-900">

            <!-- SUBTLE NACOC WATERMARK (FOR SCREEN PREVIEW) -->
            <div class="dossier-watermark absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.025] select-none">
                <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[900px] h-[900px] object-contain filter grayscale" />
            </div>

            <div class="relative z-10 flex flex-col justify-between h-full min-h-0">

                <!-- DOSSIER HEADER -->
                <div class="shrink-0">
                    <div class="pt-1 pb-2 border-b border-slate-300 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-900 font-serif-header">
                                NACOC FORENSIC &bull; LABORATORY ANALYSIS UNIT
                            </span>
                            <span class="ml-2 font-mono-code text-[9px] font-semibold text-slate-700">
                                SITREP &bull; {{ date('Y') }}
                            </span>
                        </div>
                        <div class="text-right">
                            <p class="text-[8px] font-bold uppercase tracking-wider text-slate-900">Confidential</p>
                            <p class="mt-0.5 text-[9px] font-bold uppercase text-slate-800">
                                Page 2 of <span class="doc-total-pages">{{ $totalDossierPages }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- REPORT TITLE BLOCK -->
                    <div class="mt-2.5 pb-2 text-center border-b border-slate-200">
                        <p class="text-[8.5px] font-bold uppercase tracking-[0.2em] text-emerald-800">
                            FORENSICS DEPARTMENT &bull; LABORATORY ANALYSIS UNIT
                        </p>
                        <h2 class="mt-0.5 text-base font-extrabold uppercase tracking-wide text-slate-950 font-serif-header" id="dossierHeadingText">
                            {{ $reportTypeLabel ?? 'WEEKLY SITUATIONAL REPORT' }}
                        </h2>
                        <p class="mt-0.5 text-xs font-extrabold uppercase tracking-wider text-emerald-900 font-times" id="dossierPeriodText">
                            {{ $periodLabel ?? 'Period: 20 September – 27 September 2026' }}
                        </p>
                    </div>

                    <!-- OPERATIONAL INTRODUCTORY PARAGRAPH (FROM UPLOADED FILE) -->
                    <div class="mt-2.5 p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-[9.5px] text-slate-800 leading-relaxed font-sans">
                        <p class="font-medium">
                            <span class="font-bold text-slate-900">Operational Briefing:</span> During the reporting period, the Laboratory Analysis Unit continued to provide essential preliminary forensic examination and drug testing for the underlisted cases;
                        </p>
                    </div>

                    <!-- EXECUTIVE KPI SUMMARY METRIC TILES -->
                    <div class="mt-2.5 grid grid-cols-4 gap-2 avoid-break">
                        <div class="p-2 bg-white border border-slate-200 rounded-lg shadow-2xs flex flex-col">
                            <span class="text-[7.5px] font-bold uppercase tracking-wider text-slate-500 font-mono-code">Total Seizure Cases</span>
                            <span class="text-base font-black text-slate-900 font-mono-code mt-0.5" id="kpiCasesVal">{{ count($filteredSeizures) }}</span>
                            <span class="text-[7px] text-emerald-600 font-semibold mt-auto">Active Chemical Analysis</span>
                        </div>
                        <div class="p-2 bg-white border border-slate-200 rounded-lg shadow-2xs flex flex-col">
                            <span class="text-[7.5px] font-bold uppercase tracking-wider text-slate-500 font-mono-code">Suspects Involved</span>
                            <span class="text-base font-black text-slate-900 font-mono-code mt-0.5" id="kpiSuspectsVal">{{ $totalSuspectsCount }}</span>
                            <span class="text-[7px] text-slate-600 font-semibold mt-auto">Primary &amp; Co-Suspects</span>
                        </div>
                        <div class="p-2 bg-white border border-slate-200 rounded-lg shadow-2xs flex flex-col">
                            <span class="text-[7.5px] font-bold uppercase tracking-wider text-slate-500 font-mono-code">Drug Screenings</span>
                            <span class="text-base font-black text-teal-700 font-mono-code mt-0.5">{{ count($drugScreeningRecords) }}</span>
                            <span class="text-[7px] text-teal-600 font-semibold mt-auto">Urine Toxicology Conducted</span>
                        </div>
                        <div class="p-2 bg-white border border-slate-200 rounded-lg shadow-2xs flex flex-col">
                            <span class="text-[7.5px] font-bold uppercase tracking-wider text-slate-500 font-mono-code">GSA Submissions</span>
                            <span class="text-base font-black text-indigo-700 font-mono-code mt-0.5">{{ count($gsaSubmissions) }}</span>
                            <span class="text-[7px] text-indigo-600 font-semibold mt-auto">Ghana Standards Authority</span>
                        </div>
                    </div>
                </div>

                <!-- MAIN DOSSIER FLOW CONTENT -->
                <div class="flex-1 mt-3 space-y-4">

                    <!-- ========================================================
                         SECTION 01: PRELIMINARY FORENSIC EXAMINATION & TESTING
                         (ALL DRUG CASES & SUSPECTS WITH MODERN EXECUTIVE DESIGN)
                         ======================================================== -->
                    <div class="section-01 avoid-break">
                        <div class="flex items-center justify-between pb-1 mb-2 border-b-2 border-emerald-700">
                            <div class="flex items-center gap-2">
                                <span class="flex h-5 w-5 items-center justify-center rounded-md bg-emerald-600 text-white text-[9px] font-bold font-mono-code">
                                    01
                                </span>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-950 font-serif-header">
                                    Preliminary Forensic Examination &amp; Drug Testing Log
                                </h3>
                            </div>
                            <span class="text-[8px] font-mono-code text-slate-600 font-bold uppercase">
                                Registered Seizure &bull; <span id="tableRowsCounter">{{ count($filteredSeizures) }}</span> Entries
                            </span>
                        </div>

                        <!-- BEAUTIFULLY ARRANGED EXECUTIVE DATA TABLE (NOT THE UGLY WIREFRAME DESIGN) -->
                        <div class="overflow-hidden border border-slate-300 rounded-xl shadow-xs bg-white">
                            <table class="w-full text-left border-collapse text-[9px]" id="sitrepSeizureTable">
                                <thead>
                                    <tr class="bg-slate-100/90 border-b border-slate-300 text-slate-900 font-bold uppercase tracking-wider font-mono-code text-[8px]">
                                        <th class="py-2 px-2.5 w-[85px] border-r border-slate-200">Date Of Seizure</th>
                                        <th class="py-2 px-2.5 w-[95px] border-r border-slate-200">Source of Exhibits</th>
                                        <th class="py-2 px-3 border-r border-slate-200">Drug Seizures &amp; Suspect(s)</th>
                                        <th class="py-2 px-2.5 w-[115px] border-r border-slate-200">Exhibit Type</th>
                                        <th class="py-2 px-2 text-center w-[65px] border-r border-slate-200">Quantity</th>
                                        <th class="py-2 px-2 text-center w-[60px] border-r border-slate-200">Weight</th>
                                        <th class="py-2 px-3">Status / Analytical Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 text-slate-800">
                                    @forelse($filteredSeizures as $index => $item)
                                        @php
                                            $isEven = $index % 2 === 0;
                                            $statusText = $item['status_remarks'] ?? '';
                                            $statusLower = strtolower($statusText);
                                            
                                            $statusBadgeBg = 'bg-slate-100 text-slate-800 border-slate-300';
                                            if (str_contains($statusLower, 'thc') || str_contains($statusLower, 'codeine') || str_contains($statusLower, 'positive')) {
                                                $statusBadgeBg = 'bg-emerald-50 text-emerald-900 border-emerald-300';
                                            } elseif (str_contains($statusLower, 'inconclusive')) {
                                                $statusBadgeBg = 'bg-amber-50 text-amber-900 border-amber-300';
                                            } elseif (str_contains($statusLower, 'prohibited')) {
                                                $statusBadgeBg = 'bg-rose-50 text-rose-900 border-rose-300';
                                            }
                                            $isMultipleCasePdf = !empty($item['is_multiple']) || count($item['suspects']) > 1;
                                        @endphp
                                        <tr class="seizure-row hover:bg-slate-50/80 transition-colors {{ $isEven ? 'bg-white' : 'bg-slate-50/40' }}"
                                            data-date="{{ $item['date'] }}"
                                            data-source="{{ strtolower($item['source']) }}"
                                            data-suspect="{{ strtolower($item['suspects_display']) }}"
                                            data-exhibit="{{ strtolower($item['exhibit_type']) }}"
                                            data-remarks="{{ strtolower($item['status_remarks']) }}"
                                            data-suspects-count="{{ count($item['suspects']) }}">
                                            
                                            <!-- DATE -->
                                            <td class="py-2 px-2.5 border-r border-slate-200 font-mono-code text-slate-700 font-semibold align-top whitespace-nowrap">
                                                {{ $item['date_display'] }}
                                            </td>

                                            <!-- SOURCE OF EXHIBITS -->
                                            <td class="py-2 px-2.5 border-r border-slate-200 align-top">
                                                <div class="font-bold text-slate-900 leading-snug">{{ $item['source'] }}</div>
                                                <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[7px] font-bold font-mono-code uppercase bg-slate-200/80 text-slate-700">
                                                    {{ $item['source_badge'] ?? 'UNIT POST' }}
                                                </span>
                                            </td>

                                            <!-- DRUG SEIZURES & SUSPECTS (SINGLE & MULTIPLE CASE SUSPECTS: OVERLAPPING IMAGES & NAMES) -->
                                            <td class="py-2 px-3 border-r border-slate-200 align-top">
                                                @php
                                                    $suspectsListPdf = $item['suspects'] ?? [];
                                                    $namesListPdf = array_map(function($s) {
                                                        return trim($s['name'] ?? ($s['suspect_name'] ?? 'Suspect'));
                                                    }, $suspectsListPdf);
                                                    $namesDisplayPdf = implode(', ', array_filter($namesListPdf));
                                                @endphp
                                                <div class="flex items-center gap-2">
                                                    <!-- OVERLAPPING AVATAR IMAGES -->
                                                    <div class="flex items-center {{ count($suspectsListPdf) > 1 ? '-space-x-1.5' : '' }} shrink-0">
                                                        @foreach($suspectsListPdf as $sIdx => $s)
                                                            @php
                                                                $rawMug = $s['mugshot_front'] ?? ($s['mugshot'] ?? null);
                                                                $sMug = null;
                                                                if (!empty($rawMug)) {
                                                                    $rawMug = trim((string)$rawMug);
                                                                    $sMug = (!str_starts_with($rawMug, 'data:image') && !str_starts_with($rawMug, 'http://') && !str_starts_with($rawMug, 'https://') && !str_starts_with($rawMug, '/'))
                                                                        ? asset($rawMug)
                                                                        : $rawMug;
                                                                }
                                                                $sFullName = $s['name'] ?? ($s['suspect_name'] ?? 'Suspect');
                                                                $sInit = strtoupper(substr($sFullName, 0, 2));
                                                            @endphp
                                                            <div class="relative w-6 h-6 rounded-full border border-white bg-slate-100 overflow-hidden shadow-2xs flex items-center justify-center shrink-0"
                                                                 style="z-index: {{ 20 - $sIdx }};"
                                                                 title="{{ $sFullName }}">
                                                                @if(!empty($sMug))
                                                                    <img src="{{ $sMug }}" alt="{{ $sFullName }}" class="w-full h-full object-cover">
                                                                @else
                                                                    <span class="text-emerald-800 font-bold text-[8px] font-mono-code">{{ $sInit }}</span>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>

                                                    <!-- NAMES NEXT (e.g. "a, b") -->
                                                    <div class="min-w-0">
                                                        <span class="font-extrabold text-slate-950 text-[9px] font-mono-code leading-tight">
                                                            {{ $namesDisplayPdf ?: 'Suspect' }}
                                                        </span>
                                                        @foreach($suspectsListPdf as $s)
                                                            <span class="hidden" data-status="{{ $s['status'] ?? '' }}">{{ $s['status'] ?? '' }}</span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- EXHIBIT TYPE -->
                                            <td class="py-2 px-2.5 border-r border-slate-200 align-top">
                                                <span class="font-extrabold text-slate-900 block leading-tight">
                                                    {{ $item['exhibit_type'] }}
                                                </span>
                                                @if(!empty($item['substance_name']) && $item['substance_name'] !== $item['exhibit_type'])
                                                    <span class="text-[7.5px] text-slate-500 block leading-tight mt-0.5">
                                                        {{ $item['substance_name'] }}
                                                    </span>
                                                @endif
                                            </td>

                                            <!-- QUANTITY -->
                                            <td class="py-2 px-2 border-r border-slate-200 text-center font-mono-code font-bold text-slate-900 align-top whitespace-nowrap">
                                                {{ $item['quantity'] ?? '-' }}
                                            </td>

                                            <!-- WEIGHT -->
                                            <td class="py-2 px-2 border-r border-slate-200 text-center font-mono-code font-bold text-slate-800 align-top whitespace-nowrap">
                                                {{ $item['weight'] ?? '-' }}
                                            </td>

                                            <!-- STATUS / REMARKS -->
                                            <td class="py-2 px-3 align-top">
                                                <div class="p-1.5 rounded-lg border text-[8.5px] font-medium leading-relaxed {{ $statusBadgeBg }}">
                                                    {{ $item['status_remarks'] }}
                                                </div>
                                                <div class="mt-1 flex items-center justify-between text-[7px] text-slate-500 font-mono-code">
                                                    <span>Lab Code: {{ $item['lab_number'] }}</span>
                                                    <span class="font-bold text-slate-700">Verified</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr id="emptySeizureRow">
                                            <td colspan="7" class="py-6 text-center text-slate-500 font-mono-code text-xs">
                                                No drug cases or exhibits found matching the selected filter criteria.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>


                    <!-- ========================================================
                         SECTION 02: DRUG SCREENING OF SUSPECTS (URINE TOXICOLOGY)
                         ======================================================== -->
                    <div class="section-02 avoid-break pt-1">
                        <div class="flex items-center justify-between pb-1 mb-2 border-b-2 border-teal-600">
                            <div class="flex items-center gap-2">
                                <span class="flex h-5 w-5 items-center justify-center rounded-md bg-teal-600 text-white text-[9px] font-bold font-mono-code">
                                    02
                                </span>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-950 font-serif-header">
                                    Drug Screening of Suspects (Urine Toxicology)
                                </h3>
                            </div>
                            <span class="text-[8px] font-mono-code text-slate-600 font-bold uppercase">
                                Toxicological Urinalysis Register
                            </span>
                        </div>

                        <p class="text-[8.5px] text-slate-700 mb-2 italic">
                            One (1) or more suspects were subjected to rapid urine multi-panel drug testing during the period under review:
                        </p>

                        <div class="overflow-hidden border border-slate-300 rounded-xl shadow-xs bg-white">
                            <table class="w-full text-left border-collapse text-[9px]">
                                <thead>
                                    <tr class="bg-teal-900/10 border-b border-slate-300 text-slate-900 font-bold uppercase tracking-wider font-mono-code text-[8px]">
                                        <th class="py-1.5 px-2.5 w-[35px] text-center border-r border-slate-200">SN</th>
                                        <th class="py-1.5 px-3 w-[100px] border-r border-slate-200">Screening Date</th>
                                        <th class="py-1.5 px-3 border-r border-slate-200">Suspect Name &amp; Ref</th>
                                        <th class="py-1.5 px-3 w-[140px] border-r border-slate-200">Purpose</th>
                                        <th class="py-1.5 px-3 w-[120px] text-center border-r border-slate-200">Results</th>
                                        <th class="py-1.5 px-3">Clinical / Analytical Notes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 text-slate-800">
                                    @foreach($drugScreeningRecords as $ds)
                                        @php
                                            $isPos = strtolower($ds['result']) === 'positive';
                                        @endphp
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="py-2 px-2.5 text-center font-mono-code font-bold text-slate-600 border-r border-slate-200 align-top">
                                                {{ $ds['sn'] }}
                                            </td>
                                            <td class="py-2 px-3 font-mono-code text-slate-700 border-r border-slate-200 align-top whitespace-nowrap">
                                                {{ $ds['date_display'] }}
                                            </td>
                                            <td class="py-2 px-3 border-r border-slate-200 align-top">
                                                <div class="font-extrabold text-slate-900 text-[9.5px]">{{ $ds['suspect_name'] }}</div>
                                                <div class="text-[7.5px] font-mono-code text-slate-500">Ref: {{ $ds['case_reference'] }}</div>
                                            </td>
                                            <td class="py-2 px-3 border-r border-slate-200 text-slate-700 align-top font-medium">
                                                {{ $ds['purpose'] }}
                                            </td>
                                            <td class="py-2 px-3 border-r border-slate-200 text-center align-top whitespace-nowrap">
                                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider {{ $isPos ? 'bg-rose-100 text-rose-900 border border-rose-300' : 'bg-emerald-100 text-emerald-900 border border-emerald-300' }}">
                                                    {{ $ds['result'] }}
                                                </span>
                                            </td>
                                            <td class="py-2 px-3 text-slate-600 text-[8px] leading-relaxed align-top">
                                                {{ $ds['notes'] }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>


                    <!-- ========================================================
                         SECTION 03: GHANA STANDARDS AUTHORITY (GSA) SUBMISSIONS
                         ======================================================== -->
                    <div class="section-03 avoid-break pt-1">
                        <div class="flex items-center justify-between pb-1 mb-2 border-b-2 border-indigo-700">
                            <div class="flex items-center gap-2">
                                <span class="flex h-5 w-5 items-center justify-center rounded-md bg-indigo-700 text-white text-[9px] font-bold font-mono-code">
                                    03
                                </span>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-950 font-serif-header">
                                    Ghana Standards Authority (GSA) Submissions
                                </h3>
                            </div>
                            <span class="text-[8px] font-mono-code text-slate-600 font-bold uppercase">
                                External Confirmatory Testing
                            </span>
                        </div>

                        <p class="text-[8.5px] text-slate-700 mb-2 italic">
                            The details of exhibits and seized case samples submitted to Ghana Standards Authority (GSA) on 20 August 2026 for laboratory examination and reporting:
                        </p>

                        <div class="overflow-hidden border border-slate-300 rounded-xl shadow-xs bg-white">
                            <table class="w-full text-left border-collapse text-[9px]">
                                <thead>
                                    <tr class="bg-indigo-900/10 border-b border-slate-300 text-slate-900 font-bold uppercase tracking-wider font-mono-code text-[8px]">
                                        <th class="py-1.5 px-2.5 w-[35px] text-center border-r border-slate-200">S/N</th>
                                        <th class="py-1.5 px-3 w-[180px] border-r border-slate-200">Case Reference / Docket</th>
                                        <th class="py-1.5 px-3 border-r border-slate-200">Exhibit Type</th>
                                        <th class="py-1.5 px-3 w-[110px] text-center border-r border-slate-200">GSA Lab No.</th>
                                        <th class="py-1.5 px-3 w-[100px] border-r border-slate-200">Submission Date</th>
                                        <th class="py-1.5 px-3 w-[150px]">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 text-slate-800">
                                    @foreach($gsaSubmissions as $gsa)
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="py-2 px-2.5 text-center font-mono-code font-bold text-slate-600 border-r border-slate-200 align-top">
                                                {{ $gsa['sn'] }}
                                            </td>
                                            <td class="py-2 px-3 border-r border-slate-200 font-bold text-slate-900 align-top">
                                                {{ $gsa['case_reference'] }}
                                            </td>
                                            <td class="py-2 px-3 border-r border-slate-200 text-slate-800 font-semibold align-top">
                                                {{ $gsa['exhibit_type'] }}
                                            </td>
                                            <td class="py-2 px-3 border-r border-slate-200 text-center font-mono-code font-bold text-indigo-900 align-top whitespace-nowrap">
                                                {{ $gsa['gsa_lab_no'] }}
                                            </td>
                                            <td class="py-2 px-3 border-r border-slate-200 font-mono-code text-slate-700 align-top whitespace-nowrap">
                                                {{ $gsa['submission_date'] }}
                                            </td>
                                            <td class="py-2 px-3 align-top">
                                                <span class="inline-block px-2 py-0.5 rounded text-[7.5px] font-bold uppercase bg-amber-100 text-amber-900 border border-amber-300">
                                                    {{ $gsa['status'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>


                    <!-- ========================================================
                         SECTION 04: CONDITIONS & ACCREDITATION NOTE
                         ======================================================== -->
                    <div class="section-conditions avoid-break pt-2 border-t border-slate-200 text-[8px] text-slate-700 space-y-1">
                        <p class="font-bold text-slate-900 uppercase tracking-wide">
                            Conditions &amp; Regulatory Declarations:
                        </p>
                        <ol class="list-decimal list-inside space-y-0.5 text-slate-600 pl-1 leading-tight">
                            <li>Analysis results relate strictly to the tested drug exhibits, samples, and specimens cataloged under chain-of-custody protocols.</li>
                            <li>Preliminary tests are presumptive; confirmatory testing (GC-MS / FTIR / HPLC) is performed in accordance with ISO/IEC 17025 accredited standards.</li>
                            <li>This document constitutes an official situational law enforcement record and shall not be published without prior written consent from the Director-General of NACOC.</li>
                        </ol>
                    </div>


                    <!-- ========================================================
                         SECTION 05: DUAL OFFICIAL SIGN-OFF & CERTIFICATION
                         (MATCHING HEIGHT & DUAL AUTHENTICATION OF MULTI_DPF_PDF)
                         ======================================================== -->
                    <div class="section-signatures avoid-break mt-4 pt-3 border-t-2 border-slate-300">
                        <div class="grid grid-cols-2 gap-8">
                            <!-- LEFT: HEAD OF LABORATORY ANALYSIS -->
                            <div class="flex flex-col">
                                <p class="text-[7.5px] font-bold uppercase tracking-wider text-slate-700">Head of Laboratory Analysis Unit</p>
                                
                                <div class="relative h-20 flex items-end pb-0.5">
                                    @if(!empty($johnSig))
                                        <img src="{{ $johnSig }}" alt="Official Signature - {{ $labHeadName }}" class="absolute bottom-0 left-0 h-24 max-h-[96px] max-w-[280px] w-auto object-contain object-left filter contrast-125 mix-blend-multiply pointer-events-none" />
                                    @else
                                        <div class="h-12 flex items-center text-slate-400 italic text-[10px] font-times">
                                            [ Electronic Signature on Master Record ]
                                        </div>
                                    @endif
                                </div>

                                <div class="border-b border-dashed border-slate-400 pb-0.5">
                                    <span class="text-[7px] italic text-slate-700">Electronically Signed &amp; Validated</span>
                                </div>
                                <div class="mt-0.5">
                                    <p class="text-[8.5px] font-bold text-slate-900 leading-tight">{{ $labHeadName }}</p>
                                    <p class="text-[7px] text-slate-800 leading-tight">Head of Laboratory Analysis Unit, NACOC</p>
                                </div>
                            </div>

                            <!-- RIGHT: HEAD OF FORENSIC DEPARTMENT -->
                            <div class="flex flex-col">
                                <p class="text-[7.5px] font-bold uppercase tracking-wider text-slate-700">Head of Forensic Department</p>
                                
                                <div class="relative h-20 flex items-end pb-0.5">
                                    @if(!empty($anitaSig))
                                        <img src="{{ $anitaSig }}" alt="Official Signature - {{ $deptHeadName }}" class="absolute bottom-0 left-0 h-24 max-h-[96px] max-w-[280px] w-auto object-contain object-left filter contrast-125 mix-blend-multiply pointer-events-none" />
                                    @else
                                        <div class="h-12 flex items-center text-slate-400 italic text-[10px] font-times">
                                            [ Counter-Signature on Master Record ]
                                        </div>
                                    @endif
                                </div>

                                <div class="border-b border-dashed border-slate-400 pb-0.5">
                                    <span class="text-[7px] italic text-slate-700">Authorized &amp; Counter-signed</span>
                                </div>
                                <div class="mt-0.5">
                                    <p class="text-[8.5px] font-bold text-slate-900 leading-tight">{{ $deptHeadName }}</p>
                                    <p class="text-[7px] text-slate-800 leading-tight">Head of Forensic Directorate / Department, NACOC</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- DOSSIER FOOTER -->
                <div class="mt-auto pt-2 border-t border-slate-200 flex items-center justify-between text-[7.5px] text-slate-500 font-mono-code select-none footer">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-700 tracking-wider">NACOC / LABORATORY ANALYSIS UNIT</span>
                        <span>&bull;</span>
                        <span>Official Situational Dossier</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span>STRICTLY CONFIDENTIAL</span>
                        <span>&bull;</span>
                        <span class="font-bold text-slate-800">Page 2 of <span class="doc-total-pages">{{ $totalDossierPages }}</span></span>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- CLIENT-SIDE SCRIPT FOR DYNAMIC FILTERING & PRINT -->
    <script>
        function handlePrintReport() {
            window.print();
        }

        function setPeriodFilter(preset) {
            const url = new URL(window.location.href);
            url.searchParams.set('period', preset);
            const now = new Date();
            const formatYmd = (d) => d.toISOString().split('T')[0];

            if (preset === 'weekly') {
                const past7 = new Date();
                past7.setDate(now.getDate() - 7);
                url.searchParams.set('from', formatYmd(past7));
                url.searchParams.set('to', formatYmd(now));
            } else if (preset === 'monthly') {
                const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                url.searchParams.set('from', formatYmd(firstDay));
                url.searchParams.set('to', formatYmd(lastDay));
            } else if (preset === 'annual') {
                const firstYear = new Date(now.getFullYear(), 0, 1);
                const lastYear = new Date(now.getFullYear(), 11, 31);
                url.searchParams.set('from', formatYmd(firstYear));
                url.searchParams.set('to', formatYmd(lastYear));
            } else if (preset === 'all') {
                url.searchParams.delete('from');
                url.searchParams.delete('to');
            }
            window.location.href = url.toString();
        }

        function applyDateRange() {
            const from = document.getElementById('filterFromDate').value;
            const to = document.getElementById('filterToDate').value;
            const url = new URL(window.location.href);
            if (from) url.searchParams.set('from', from);
            if (to) url.searchParams.set('to', to);
            url.searchParams.set('period', 'custom');
            window.location.href = url.toString();
        }

        function clearLiveSearch() {
            const input = document.getElementById('liveSearchInput');
            if (input) {
                input.value = '';
                applyClientFilter();
            }
        }

        function resetAllFilters() {
            window.location.href = '{{ url("/Lab_Analysis/report") }}';
        }

        function applyClientFilter() {
            const input = document.getElementById('liveSearchInput');
            const clearBtn = document.getElementById('clearSearchBtn');
            const q = (input ? input.value : '').toLowerCase().trim();

            if (clearBtn) {
                if (q.length > 0) clearBtn.classList.remove('hidden');
                else clearBtn.classList.add('hidden');
            }

            const rows = document.querySelectorAll('.seizure-row');
            let visibleCount = 0;
            let visibleSuspects = 0;

            rows.forEach(r => {
                const date = (r.getAttribute('data-date') || '').toLowerCase();
                const source = (r.getAttribute('data-source') || '').toLowerCase();
                const suspect = (r.getAttribute('data-suspect') || '').toLowerCase();
                const exhibit = (r.getAttribute('data-exhibit') || '').toLowerCase();
                const remarks = (r.getAttribute('data-remarks') || '').toLowerCase();

                const matches = !q || date.includes(q) || source.includes(q) || suspect.includes(q) || exhibit.includes(q) || remarks.includes(q);

                if (matches) {
                    r.style.display = '';
                    visibleCount++;
                    const sCount = parseInt(r.getAttribute('data-suspects-count') || '1', 10);
                    visibleSuspects += sCount;
                } else {
                    r.style.display = 'none';
                }
            });

            // Update stats
            const casesSpan = document.getElementById('statCasesCount');
            const suspectsSpan = document.getElementById('statSuspectsCount');
            const tableCounter = document.getElementById('tableRowsCounter');
            const kpiCases = document.getElementById('kpiCasesVal');
            const kpiSuspects = document.getElementById('kpiSuspectsVal');

            if (casesSpan) casesSpan.textContent = visibleCount;
            if (suspectsSpan) suspectsSpan.textContent = visibleSuspects;
            if (tableCounter) tableCounter.textContent = visibleCount;
            if (kpiCases) kpiCases.textContent = visibleCount;
            if (kpiSuspects) kpiSuspects.textContent = visibleSuspects;

            // Handle empty row
            const emptyRow = document.getElementById('emptySeizureRow');
            if (emptyRow) {
                emptyRow.style.display = visibleCount === 0 ? '' : 'none';
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            applyClientFilter();
        });
    </script>
</body>
</html>
