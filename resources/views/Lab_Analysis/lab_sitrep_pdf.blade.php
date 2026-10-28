@php
    $authUser = auth()->user();

    // WHY: Server-side pagination chunks the data into fixed-size pages so
    // the browser never has to paginate. This removes the O(n^2) JS paginator
    // and lifts the ~60-page ceiling entirely.
    $rowsPerPage = 25; // Tune this to fit one A4 page. Increase until it just fits.

    $seizureChunks   = collect($filteredSeizures ?? [])->chunk($rowsPerPage);
    $screeningChunks = collect($drugScreeningRecords ?? [])->chunk($rowsPerPage);
    $gsaChunks       = collect($gsaSubmissions ?? [])->chunk($rowsPerPage);

    // Total dossier pages = 1 cover + the max chunk count across all three sections.
    $maxChunks = max(
        $seizureChunks->count(),
        $screeningChunks->count(),
        $gsaChunks->count(),
        1
    );
    $totalDossierPages = 1 + $maxChunks;

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
    <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
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
            background-color: #ffffff;
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
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.08), 0 2px 6px -1px rgba(0, 0, 0, 0.04);
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
            height: 297mm;
            max-height: 297mm;
            padding: 10mm 12mm 10mm 12mm;
            box-sizing: border-box;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            background: #ffffff;
            page-break-after: always;
            break-after: page;
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

        .table-responsive-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            table-layout: fixed;
            word-wrap: break-word;
        }

        th, td {
            word-break: break-word;
            overflow-wrap: break-word;
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

            /* WHY: Hide the single fixed watermark in print; each page now carries its
               own per-page .dossier-watermark element, which scales to many pages without
               the fixed-element repetition cost that caused print rasterization lag. */
            .print-fixed-watermark {
                display: none !important;
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
                height: 297mm !important;
                max-height: 297mm !important;
                overflow: hidden !important;
                background: #ffffff !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                page-break-after: always !important;
                break-after: page !important;
            }

            /* WHY: Keep the per-page watermark visible in print on every page.
               Each .dynamic-dossier-page (including generated continuation pages)
               already contains its own .dossier-watermark element, so print cost is
               O(1) per page instead of O(n) fixed-element rasterization. */
            .dynamic-dossier-page > .dossier-watermark {
                display: flex !important;
                position: absolute !important;
                inset: 0 !important;
                align-items: center !important;
                justify-content: center !important;
                pointer-events: none !important;
                z-index: 0 !important;
                opacity: 0.025 !important;
                overflow: hidden !important;
            }

            .table-responsive-wrapper {
                overflow: visible !important;
            }

            table {
                width: 100% !important;
                max-width: 100% !important;
                table-layout: fixed !important;
            }

            th, td {
                word-break: break-word !important;
                overflow-wrap: break-word !important;
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
<body class="antialiased bg-white">

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

        <!-- SECOND ROW: INTERACTIVE FILTERS & SEARCH (INLINE) -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-2.5 px-3 sm:px-4 shadow-sm">
            <div class="flex items-center justify-between gap-2.5 sm:gap-4 overflow-x-auto min-w-0 flex-nowrap">
                <!-- PERIOD PRESETS -->
                <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl border border-slate-200 shrink-0">
                    <button type="button" onclick="setPeriodFilter('weekly')" class="preset-btn px-2.5 sm:px-3 py-1 rounded-lg text-xs font-bold transition-all whitespace-nowrap {{ ($rawPeriod ?? 'weekly') === 'weekly' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}" data-preset="weekly">
                        Weekly (SITREP)
                    </button>
                    <button type="button" onclick="setPeriodFilter('monthly')" class="preset-btn px-2.5 sm:px-3 py-1 rounded-lg text-xs font-bold transition-all whitespace-nowrap {{ ($rawPeriod ?? '') === 'monthly' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}" data-preset="monthly">
                        Monthly
                    </button>
                    <button type="button" onclick="setPeriodFilter('annual')" class="preset-btn px-2.5 sm:px-3 py-1 rounded-lg text-xs font-bold transition-all whitespace-nowrap {{ in_array($rawPeriod ?? '', ['annual', 'annually']) ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}" data-preset="annual">
                        Annual
                    </button>
                    <button type="button" onclick="setPeriodFilter('all')" class="preset-btn px-2.5 sm:px-3 py-1 rounded-lg text-xs font-bold transition-all whitespace-nowrap {{ ($rawPeriod ?? '') === 'all' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}" data-preset="all">
                        All Records
                    </button>
                </div>

                <!-- DATE RANGE PICKERS -->
                <div class="flex items-center gap-1.5 sm:gap-2 text-xs shrink-0">
                    <span class="text-slate-600 font-mono-code text-[11px] whitespace-nowrap font-medium">From:</span>
                    <input type="date" id="filterFromDate" value="{{ $fromDate ?? '' }}" class="bg-slate-50 border border-slate-200 text-slate-800 text-xs rounded-xl px-2.5 py-1 w-[122px] sm:w-[128px] focus:outline-none focus:bg-white focus:border-emerald-500 font-mono-code shadow-2xs" onchange="applyDateRange()">
                    <span class="text-slate-600 font-mono-code text-[11px] whitespace-nowrap font-medium">To:</span>
                    <input type="date" id="filterToDate" value="{{ $toDate ?? '' }}" class="bg-slate-50 border border-slate-200 text-slate-800 text-xs rounded-xl px-2.5 py-1 w-[122px] sm:w-[128px] focus:outline-none focus:bg-white focus:border-emerald-500 font-mono-code shadow-2xs" onchange="applyDateRange()">
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
                            FORENSIC DEPARTMENT
                        </p>
                        <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-600 font-mono-code">
                            LABORATORY ANALYSIS UNIT (LAU)
                        </p>
                    </div>

                    <!-- INCREASED SIZE EMBLEM LOGO IN THE MIDDLE -->
                    <div class="my-6 flex items-center justify-center">
                        <img src="{{ asset('NACOC_LOGO.PNG') }}" alt="NACOC Emblem Logo" class="w-680 h-680 max-w-[780px] max-h-[780px] object-contain drop-shadow-md" />
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
                        <div class="p-4 text-center font-mono-code text-[10px] text-slate-700 space-y-1.5">
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
        @for ($pageIdx = 0; $pageIdx < $maxChunks; $pageIdx++)
            @php
                $pageNumber = $pageIdx + 2;
                $isFirstDossierPage = ($pageIdx === 0);
                $isLastDossierPage = ($pageIdx === $maxChunks - 1);

                $seizureChunk   = $seizureChunks->get($pageIdx, collect());
                $screeningChunk = $screeningChunks->get($pageIdx, collect());
                $gsaChunk       = $gsaChunks->get($pageIdx, collect());
            @endphp
        <div class="a4-page dynamic-dossier-page page-break relative text-slate-900">

            <!-- SUBTLE NACOC WATERMARK (FOR SCREEN PREVIEW) -->
            <div class="dossier-watermark absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.025] select-none">
                <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[900px] h-[900px] object-contain filter grayscale" />
            </div>

            <div class="relative z-10 flex flex-col justify-between h-full min-h-0">

                <!-- DOSSIER HEADER -->
                <div class="page-header-block shrink-0">
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
                            <p class="text-[8px] font-bold uppercase tracking-wider text-slate-900">CONFIDENTIAL</p>
                            <p class="mt-0.5 text-[9px] font-bold uppercase text-slate-800">
                                PAGE {{ $pageNumber }} OF {{ $totalDossierPages }}
                            </p>
                        </div>
                    </div>

                    @if ($isFirstDossierPage)
                    <!-- REPORT TITLE BLOCK -->
                    <div class="page-title-block mt-2 pb-1.5 text-center border-b border-slate-200">
                        <p class="text-[8.5px] font-bold uppercase tracking-[0.2em] text-emerald-800">
                            FORENSIC DEPARTMENT &bull; LABORATORY ANALYSIS UNIT
                        </p>
                        <h2 class="mt-0.5 text-base font-bold uppercase tracking-wide text-slate-950 font-serif-header" id="dossierHeadingText">
                            {{ $reportTypeLabel ?? 'WEEKLY SITUATIONAL REPORT' }}
                        </h2>
                        <p class="mt-0.5 text-xs font-bold uppercase tracking-wider text-emerald-900 font-times" id="dossierPeriodText">
                            {{ $periodLabel ?? 'Period: 20 September – 27 September 2026' }}
                        </p>
                    </div>

                    <!-- OPERATIONAL INTRODUCTORY PARAGRAPH -->
                    <div class="page-intro-block mt-2 text-[11px] text-slate-800 leading-relaxed font-sans">
                        <p class="font-medium">
                            During the reporting period, the Laboratory Analysis Unit continued to provide essential preliminary forensic examination and drug testing for the underlisted cases;
                        </p>
                    </div>
                    @endif
                </div>

                <!-- MAIN DOSSIER FLOW CONTENT -->
                <div class="report-flow-body flex-1 mt-3 min-h-0 flex flex-col justify-between">
                    <div class="flow-sections-container space-y-4">

                    @if ($seizureChunk->isNotEmpty() || ($isFirstDossierPage && count($filteredSeizures ?? []) === 0))
                    <!-- ========================================================
                         SECTION 01: PRELIMINARY FORENSIC EXAMINATION & TESTING
                         (ALL DRUG CASES & SUSPECTS WITH MODERN EXECUTIVE DESIGN)
                         ======================================================== -->
                    <div class="section-01 avoid-break">
                        <div class="flex items-center justify-between pb-1 mt-4 mb-2 border-b-2 border-emerald-700">
                            <div class="flex items-center gap-1.5">
                                <span class="flex h-5 w-5 items-center justify-center rounded bg-emerald-600 text-white text-[8.5px] font-bold font-mono-code">
                                    01
                                </span>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-950 font-serif-header">
                                    Preliminary Forensic Examination &amp; Drug Testing Log @if (!$isFirstDossierPage) (Continued) @endif
                                </h3>
                            </div>
                            <span class="text-[8px] font-mono-code text-slate-600 font-bold uppercase">
                                Registered Seizure &bull; <span id="tableRowsCounter">{{ count($filteredSeizures) }}</span> Entries
                            </span>
                        </div>

                        <!-- BEAUTIFULLY ARRANGED EXECUTIVE DATA TABLE (NOT THE UGLY WIREFRAME DESIGN) -->
                        <div class="table-responsive-wrapper overflow-x-auto border border-slate-300 rounded-xl shadow-xs bg-white">
                            <table class="w-full table-fixed text-left border-collapse text-[10px]" id="sitrepSeizureTable">
                                <thead>
                                    <tr class="bg-slate-100/90 border-b border-slate-300 text-slate-900 font-bold uppercase tracking-wider font-mono-code text-[9px]">
                                        <th class="py-2 px-2 w-[11%] border-r border-slate-200">Date Of Seizure</th>
                                        <th class="py-2 px-2 w-[15%] border-r border-slate-200">Source of Exhibits</th>
                                        <th class="py-2 px-2.5 w-[18%] border-r border-slate-200">Suspect(s)</th>
                                        <th class="py-2 px-2 w-[16%] border-r border-slate-200">Exhibit Type</th>
                                        <th class="py-2 px-1.5 text-center w-[9%] border-r border-slate-200">Quantity</th>
                                        <th class="py-2 px-1.5 text-center w-[9%] border-r border-slate-200">Weight</th>
                                        <th class="py-2 px-2.5 w-[22%]">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 text-slate-800">
                                    @forelse($seizureChunk as $index => $item)
                                        @php
                                            $isEven = $index % 2 === 0;

                                            // Fetch exhibit_remark directly from the exhibit table in database
                                            $exRemark = null;
                                            $dbExhibit = null;
                                            if (!empty($item['exhibit_id'])) {
                                                $dbExhibit = \App\Models\Exhibit::find($item['exhibit_id']);
                                            }
                                            if (!$dbExhibit && !empty($item['case_reference'])) {
                                                $cRef = $item['case_reference'];
                                                $isMulti = !empty($item['is_multiple']) || (isset($item['case_type']) && strtoupper($item['case_type']) === 'MULTIPLE');
                                                $cleanId = !empty($item['id']) ? preg_replace('/[^0-9]/', '', (string)$item['id']) : null;

                                                $dbExhibit = \App\Models\Exhibit::where(function($q) use ($isMulti, $cRef, $cleanId) {
                                                    if ($isMulti) {
                                                        $q->whereHas('multipleCase', fn($mq) => $mq->where('case_number', $cRef));
                                                        if ($cleanId) {
                                                            $q->orWhere('multiple_suspect_case_id', $cleanId);
                                                        }
                                                    } else {
                                                        $q->whereHas('singleCase', fn($sq) => $sq->where('case_number', $cRef));
                                                        if ($cleanId) {
                                                            $q->orWhere('single_suspect_case_id', $cleanId);
                                                        }
                                                    }
                                                })->whereNotNull('exhibit_remark')->where('exhibit_remark', '!=', '')->latest()->first();
                                            }

                                            if ($dbExhibit && !empty(trim((string)$dbExhibit->exhibit_remark))) {
                                                $exRemark = trim((string)$dbExhibit->exhibit_remark);
                                            } elseif (!empty($item['exhibit_remark'])) {
                                                $exRemark = trim((string)$item['exhibit_remark']);
                                            }

                                            $statusText = $exRemark ?: ($item['status_remarks'] ?? 'Preliminary examination conducted; sample secured for confirmatory assay.');

                                            $isMultipleCasePdf = !empty($item['is_multiple']) || count($item['suspects']) > 1;
                                        @endphp
                                        <tr class="seizure-row hover:bg-slate-50/80 transition-colors {{ $isEven ? 'bg-white' : 'bg-slate-50/40' }}"
                                            data-date="{{ $item['date'] }}"
                                            data-source="{{ strtolower($item['source']) }}"
                                            data-suspect="{{ strtolower($item['suspects_display']) }}"
                                            data-exhibit="{{ strtolower($item['exhibit_type']) }}"
                                            data-remarks="{{ strtolower($statusText) }}"
                                            data-suspects-count="{{ count($item['suspects']) }}">
                                            
                                            <!-- DATE -->
                                            <td class="py-2 px-2 border-r border-slate-200 font-mono-code text-slate-700 font-semibold align-top text-[9.5px] break-words">
                                                {{ $item['date_display'] }}
                                            </td>

                                            <!-- SOURCE OF EXHIBITS -->
                                            <td class="py-2 px-2 border-r border-slate-200 align-top break-words">
                                                <div class="font-bold text-slate-900 leading-snug text-[10px] break-words">{{ $item['source_of_exhibits'] ?? ($item['source'] ?? 'N/A') }}</div>
                                            </td>

                                            <!-- SUSPECTS -->
                                            <td class="py-2 px-2.5 border-r border-slate-200 align-top break-words">
                                                @php
                                                    $suspectsListPdf = $item['suspects'] ?? [];
                                                    $namesListPdf = array_map(function($s) {
                                                        return trim($s['name'] ?? ($s['suspect_name'] ?? 'Suspect'));
                                                    }, $suspectsListPdf);
                                                    $namesDisplayPdf = implode(', ', array_filter($namesListPdf));
                                                @endphp
                                                <div class="min-w-0">
                                                    <span class="font-extrabold text-slate-950 text-[10px] font-mono-code leading-tight break-words block">
                                                        {{ $namesDisplayPdf ?: 'Suspect' }}
                                                    </span>
                                                    @foreach($suspectsListPdf as $s)
                                                        <span class="hidden" data-status="{{ $s['status'] ?? '' }}">{{ $s['status'] ?? '' }}</span>
                                                    @endforeach
                                                </div>
                                            </td>

                                            <!-- EXHIBIT TYPE -->
                                            <td class="py-2 px-2 border-r border-slate-200 align-top break-words">
                                                <span class="font-extrabold text-slate-900 block leading-tight text-[10px] break-words">
                                                    {{ $item['exhibit_type'] }}
                                                </span>
                                                @if(!empty($item['substance_name']) && $item['substance_name'] !== $item['exhibit_type'])
                                                    <span class="text-[8.5px] text-slate-500 block leading-tight mt-0.5 break-words">
                                                        {{ $item['substance_name'] }}
                                                    </span>
                                                @endif
                                            </td>

                                            <!-- QUANTITY -->
                                            <td class="py-2 px-1.5 border-r border-slate-200 text-center font-mono-code font-bold text-slate-900 align-top text-[10px] break-words">
                                                @php
                                                    $displayQty = $item['quantity'] ?? '-';
                                                    $pkg = $item['package_type'] ?? ($item['packaging'] ?? null);
                                                    if ($displayQty !== '-' && !empty($pkg) && !str_contains(strtolower($displayQty), strtolower($pkg))) {
                                                        $displayQty = trim($displayQty . ' ' . $pkg);
                                                    }
                                                @endphp
                                                {{ $displayQty }}
                                            </td>

                                            <!-- WEIGHT -->
                                            <td class="py-2 px-1.5 border-r border-slate-200 text-center font-mono-code font-bold text-slate-800 align-top text-[10px] break-words">
                                                {{ $item['weight'] ?? '-' }}
                                            </td>

                                            <!-- STATUS / REMARKS -->
                                            <td class="py-2 px-2.5 align-top text-[9.5px] font-medium text-slate-800 leading-relaxed break-words">
                                                {{ $statusText }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr id="emptySeizureRow">
                                            <td colspan="7" class="py-6 text-center text-slate-500 font-mono-code text-sm">
                                                No drug cases or exhibits found matching the selected filter criteria.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif


                    @if ($screeningChunk->isNotEmpty())
                    <!-- ========================================================
                         SECTION 02: DRUG SCREENING OF SUSPECTS
                         ======================================================== -->
                    <div class="section-02 avoid-break pt-1">
                        <div class="flex items-center justify-between pb-1 mt-5 mb-3 border-b-2 border-teal-600">
                            <div class="flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-teal-600 text-white text-[10.5px] font-bold font-mono-code">
                                    02
                                </span>
                                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-950 font-serif-header">
                                    Drug Screening of Suspect(s) @if (!$isFirstDossierPage) (Continued) @endif
                                </h3>
                            </div>
                            <span class="text-[9.5px] font-mono-code text-slate-600 font-bold uppercase">
                                Toxicological Urinalysis Register
                            </span>
                        </div>

                        <p class="text-[10.5px] text-slate-700 mb-2.5 italic">
                            One (1) or more suspects were subjected to rapid urine multi-panel drug testing during the period under review:
                        </p>

                        <div class="table-responsive-wrapper overflow-x-auto border border-slate-300 rounded-xl shadow-xs bg-white">
                            <table class="w-full table-fixed text-left border-collapse text-[10px]">
                                <thead>
                                    <tr class="bg-teal-900/10 border-b border-slate-300 text-slate-900 font-bold uppercase tracking-wider font-mono-code text-[9px]">
                                        <th class="py-2 px-1.5 w-[7%] text-center border-r border-slate-200">SN</th>
                                        <th class="py-2 px-2 w-[18%] border-r border-slate-200">Screening Date</th>
                                        <th class="py-2 px-2.5 w-[30%] border-r border-slate-200">Suspect Name &amp; Ref</th>
                                        <th class="py-2 px-2 w-[20%] border-r border-slate-200">Purpose</th>
                                        <th class="py-2 px-2 w-[25%] text-center">Results</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 text-slate-800">
                                    @foreach($screeningChunk as $ds)
                                        @php
                                            $isPos = strtolower($ds['result']) === 'positive';
                                            $isNeg = strtolower($ds['result']) === 'negative';
                                            $subFound = trim($ds['substances_found'] ?? '');
                                            if ($isPos) {
                                                $detectedLabel = (!empty($subFound) && !in_array(strtolower($subFound), ['no substance(s) found', 'no substances found', 'no drug(s) detected', 'no drugs detected']))
                                                    ? $subFound
                                                    : 'Substances detected';
                                                $resDisplay = $detectedLabel . ' (POSITIVE)';
                                            } elseif ($isNeg) {
                                                $resDisplay = 'No drug(s) detected (NEGATIVE)';
                                            } else {
                                                $resDisplay = $ds['result'];
                                            }
                                        @endphp
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="py-2 px-1.5 text-center font-mono-code font-bold text-slate-600 border-r border-slate-200 align-top text-[9.5px]">
                                                {{ $ds['sn'] }}
                                            </td>
                                            <td class="py-2 px-2 font-mono-code text-slate-700 border-r border-slate-200 align-top text-[9.5px] break-words">
                                                {{ $ds['date_display'] }}
                                            </td>
                                            <td class="py-2 px-2.5 border-r border-slate-200 align-top break-words">
                                                <div class="font-extrabold text-slate-900 text-[10px] break-words">{{ $ds['suspect_name'] }}</div>
                                                <div class="text-[8.5px] font-mono-code text-slate-500 break-words">Ref: {{ $ds['case_reference'] }}</div>
                                            </td>
                                            <td class="py-2 px-2 border-r border-slate-200 text-slate-700 align-top font-medium text-[9.5px] break-words">
                                                {{ $ds['purpose'] }}
                                            </td>
                                            <td class="py-2 px-2 text-center align-top font-bold text-[9.5px] leading-snug break-words">
                                                @if($isPos)
                                                    <span class="text-rose-700 font-extrabold break-words">{{ $resDisplay }}</span>
                                                @elseif($isNeg)
                                                    <span class="text-emerald-700 font-bold break-words">{{ $resDisplay }}</span>
                                                @else
                                                    <span class="text-amber-700 font-semibold break-words">{{ $resDisplay }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif


                    @if ($gsaChunk->isNotEmpty() || ($isFirstDossierPage && count($gsaSubmissions ?? []) === 0))
                    <!-- ========================================================
                         SECTION 03: GHANA STANDARDS AUTHORITY (GSA) SUBMISSIONS
                         ======================================================== -->
                    <div class="section-03 avoid-break pt-1">
                        <div class="flex items-center justify-between pb-1 mt-5 mb-3 border-b-2 border-indigo-700">
                            <div class="flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-teal-600 text-white text-[10.5px] font-bold font-mono-code">
                                    03
                                </span>
                                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-950 font-serif-header">
                                    Ghana Standards Authority (GSA) Submissions @if (!$isFirstDossierPage) (Continued) @endif
                                </h3>
                            </div>
                            <span class="text-[9.5px] font-mono-code text-slate-600 font-bold uppercase">
                                External Confirmatory Testing
                            </span>
                        </div>

                        <p class="text-[10.5px] text-slate-700 mb-2.5 italic">
                            The details of exhibits and seized case samples submitted to Ghana Standards Authority (GSA) for laboratory examination and reporting:
                        </p>

                        <div class="table-responsive-wrapper overflow-x-auto border border-slate-300 rounded-xl shadow-xs bg-white">
                            <table class="w-full table-fixed text-left border-collapse text-[10px]">
                                <thead>
                                    <tr class="bg-indigo-900/10 border-b border-slate-300 text-slate-900 font-bold uppercase tracking-wider font-mono-code text-[9px]">
                                        <th class="py-2 px-1.5 w-[6%] text-center border-r border-slate-200">S/N</th>
                                        <th class="py-2 px-2.5 w-[24%] border-r border-slate-200">Case Reference</th>
                                        <th class="py-2 px-2.5 w-[28%] border-r border-slate-200">Exhibit Type</th>
                                        <th class="py-2 px-2 w-[20%] text-center border-r border-slate-200">GSA Lab No.</th>
                                        <th class="py-2 px-2.5 w-[22%] text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 text-slate-800">
                                    @forelse($gsaChunk as $gsa)
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="py-2 px-1.5 text-center font-mono-code font-bold text-slate-600 border-r border-slate-200 align-top text-[9.5px]">
                                                {{ $gsa['sn'] }}
                                            </td>
                                            <td class="py-2 px-2.5 border-r border-slate-200 font-bold text-slate-900 align-top text-[10px] break-words">
                                                {{ $gsa['gsa_case_reference'] ?? ($gsa['case_reference'] ?? 'N/A') }}
                                            </td>
                                            <td class="py-2 px-2.5 border-r border-slate-200 text-slate-800 font-semibold align-top text-[10px] break-words">
                                                {{ $gsa['exhibit_type'] }}
                                            </td>
                                            <td class="py-2 px-2 border-r border-slate-200 text-center font-mono-code font-bold text-indigo-900 align-top text-[9.5px] break-words">
                                                {{ $gsa['gsa_lab_number'] ?? ($gsa['gsa_lab_no'] ?? 'N/A') }}
                                            </td>
                                            <td class="py-2 px-2.5 align-top text-center break-words">
                                                <span class="inline-block px-2 py-0.5 rounded text-[8.5px] font-bold uppercase bg-amber-100 text-amber-900 border border-amber-300 break-words max-w-full">
                                                    {{ $gsa['gsa_status'] ?? 'N/A' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="py-3 px-3 text-center text-slate-500 italic text-[10px]">
                                                No Ghana Standards Authority (GSA) submissions recorded for this reporting period.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    </div>

                    @if ($isLastDossierPage)
                    <!-- ========================================================
                         SECTION 05: DUAL OFFICIAL SIGN-OFF & CERTIFICATION
                         (IN-PERSON PHYSICAL SIGN-OFF)
                         ======================================================== -->
                    <div class="section-signatures avoid-break mt-7 pt-3 border-t-2 border-slate-300">
                        <div class="grid grid-cols-2 gap-8">
                            <!-- LEFT: HEAD OF LABORATORY ANALYSIS -->
                            <div class="flex flex-col">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-700 font-mono-code">Head of Laboratory Analysis Unit</p>
                                
                                <!-- Space for in-person physical signature -->
                                <div class="h-14"></div>

                                <div class="border-b border-slate-700 pb-0.5"></div>
                                <div class="mt-1">
                                    <p class="text-[11.5px] font-extrabold text-slate-900 leading-tight">{{ $labHeadName }}</p>
                                    <p class="text-[9.5px] text-slate-700 leading-tight">Head of Laboratory Analysis Unit, NACOC</p>
                                    <p class="text-[9.5px] text-slate-500 font-mono-code pt-0.5">Signature &amp; Date: ________________________</p>
                                </div>
                            </div>

                            <!-- RIGHT: HEAD OF FORENSIC DEPARTMENT -->
                            <div class="flex flex-col">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-700 font-mono-code">Head of Forensic Department</p>
                                
                                <!-- Space for in-person physical signature -->
                                <div class="h-14"></div>

                                <div class="border-b border-slate-700 pb-0.5"></div>
                                <div class="mt-1">
                                    <p class="text-[11.5px] font-extrabold text-slate-900 leading-tight">{{ $deptHeadName }}</p>
                                    <p class="text-[9.5px] text-slate-700 leading-tight">Head of Forensic Department, NACOC</p>
                                    <p class="text-[9.5px] text-slate-500 font-mono-code pt-0.5">Signature &amp; Date: ________________________</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                </div>

                <!-- DOSSIER FOOTER -->
                <div class="page-footer-block mt-auto pt-2 border-t border-slate-200 flex items-center justify-between text-[9.5px] text-slate-500 font-mono-code select-none footer">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-700 tracking-wider">NACOC / LABORATORY ANALYSIS UNIT</span>
                        <span>&bull;</span>
                        <span>Official Situational Dossier</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span>STRICTLY CONFIDENTIAL</span>
                        <span>&bull;</span>
                        <span class="font-bold text-slate-800">Page {{ $pageNumber }} of {{ $totalDossierPages }}</span>
                    </div>
                </div>

            </div>
        </div>
        @endfor

    </div>

    <!-- CLIENT-SIDE SCRIPT FOR DYNAMIC FILTERING & PRINT -->
    <script>
        /**
         * WHY: Pages are now pre-rendered server-side by Blade. No pagination,
         * no snapshot/restore, no reflow. Just open the print dialog.
         */
        function handlePrintReport() {
            window.print();
        }

        async function initSitrepReport() {
            applyClientFilter();

            if (document.fonts && document.fonts.ready) {
                try { await document.fonts.ready; } catch (e) {}
            }
            // No pagination call — Blade already rendered all pages.
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

        function resetAllFilters() {
            window.location.href = '{{ url("/Lab_Analysis/report") }}';
        }

        function applyClientFilter() {
            const rows = document.querySelectorAll('.seizure-row');
            let visibleCount = 0;
            let visibleSuspects = 0;

            rows.forEach(r => {
                r.style.display = '';
                visibleCount++;
                const sCount = parseInt(r.getAttribute('data-suspects-count') || '1', 10);
                visibleSuspects += sCount;
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
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initSitrepReport);
        } else {
            initSitrepReport();
        }
    </script>
</body>
</html>
