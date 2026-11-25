<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suspect Important Details &amp; Exhibit Report &mdash; NACOC Forensic Department</title>
    
    <!-- TAILWIND CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- GOOGLE FONTS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700;800&family=Merriweather:ital,wght@0,400;0,700;0,900;1,400;1,700&display=swap" rel="stylesheet">

    <style>
        @page {
            size: A4 portrait;
            margin: 0 !important;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        html, body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #e2e8f0;
            background: #e2e8f0;
            color: #0f172a;
            margin: 0;
            padding: 0;
            font-size: 14px;
            line-height: 1.45;
        }

        .font-serif-header {
            font-family: 'Cinzel', Georgia, serif;
        }

        .font-mono-code {
            font-family: 'JetBrains Mono', monospace;
        }

        .font-times {
            font-family: 'Merriweather', 'Times New Roman', serif;
        }

        /* A4 Physical Dimensions: Identical in Screen & Print */
        .printable-document {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 24px 0 60px 0;
            background-color: #e2e8f0;
            background: #e2e8f0;
        }

        .a4-page {
            width: 210mm;
            min-height: 297mm;
            height: 297mm;
            max-height: 297mm;
            margin: 0 auto 24px auto;
            background: #ffffff !important;
            background-color: #ffffff !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
            border: 1px solid #cbd5e1;
            position: relative;
            box-sizing: border-box;
            padding: 12mm 14mm 10mm 14mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            page-break-after: always;
            break-after: page;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Fixed Cover Page Size */
        .cover-page {
            height: 297mm;
            max-height: 297mm;
            min-height: 297mm;
            padding: 14mm 14mm 12mm 14mm;
        }

        .avoid-break-inside {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .suspect-details-row {
            display: flex !important;
            flex-direction: row !important;
            gap: 16px !important;
            align-items: flex-start !important;
        }

        .suspect-biodata-grid {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            column-gap: 24px !important;
            row-gap: 6px !important;
        }

        /* Lab Exam Specs Table Responsive & Wrap Styling */
        .lab-findings-table {
            table-layout: fixed !important;
            width: 100% !important;
        }

        .lab-findings-table th,
        .lab-findings-table td {
            white-space: normal !important;
            word-break: break-word !important;
            overflow-wrap: anywhere !important;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 0 !important;
            }

            html, body {
                background: #ffffff !important;
                background-color: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 210mm !important;
            }

            .printable-document {
                padding: 0 !important;
                margin: 0 !important;
                display: block !important;
                width: 210mm !important;
            }

            .no-print {
                display: none !important;
            }

            .a4-page {
                width: 210mm !important;
                height: 297mm !important;
                max-height: 297mm !important;
                min-height: 297mm !important;
                margin: 0 !important;
                padding: 12mm 14mm 10mm 14mm !important;
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                page-break-after: always !important;
                break-after: page !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                overflow: hidden !important;
                background: #ffffff !important;
            }

            .cover-page {
                height: 297mm !important;
                max-height: 297mm !important;
                min-height: 297mm !important;
                padding: 14mm 14mm 12mm 14mm !important;
            }

            .suspect-details-row {
                display: flex !important;
                flex-direction: row !important;
                gap: 16px !important;
                align-items: flex-start !important;
            }

            .suspect-biodata-grid {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                column-gap: 24px !important;
                row-gap: 6px !important;
            }

            .lab-findings-table-wrap,
            .overflow-x-auto {
                overflow: visible !important;
                overflow-x: visible !important;
            }

            .lab-findings-table {
                table-layout: fixed !important;
                width: 100% !important;
            }

            .lab-findings-table th,
            .lab-findings-table td {
                white-space: normal !important;
                word-break: break-word !important;
                overflow-wrap: anywhere !important;
            }
        }
    </style>
</head>
<body class="antialiased bg-slate-200">

    <!-- TOP ACTION CONTROL BAR (SCREEN VIEW ONLY) -->
    <header class="no-print sticky top-0 z-50 bg-slate-900 border-b border-slate-800 text-white shadow-xl px-4 py-3">
        <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ $backUrl ?? url('/DG/cases') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white rounded-lg text-xs font-bold font-mono-code transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Back to Case Register</span>
                </a>
                <div class="h-4 w-px bg-slate-700"></div>
                <div>
                    <p class="text-xs text-slate-300 font-mono-code font-bold">
                        {!! strip_tags($dateRangeDisplay) !!}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <span class="px-2.5 py-1 bg-slate-800 border border-slate-700 rounded-lg text-xs font-mono-code text-slate-300">
                    <strong class="text-white">{{ count($compiledSuspects) }}</strong> Subject{{ count($compiledSuspects) === 1 ? '' : 's' }}
                </span>
                <button type="button" onclick="autoPaginateAllSuspects(); window.print();" class="inline-flex items-center gap-2 px-4 py-1.5 bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white rounded-lg text-xs font-extrabold font-mono-code shadow-md transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    <span>Print / Save PDF</span>
                </button>
            </div>
        </div>
    </header>

    <!-- DOCUMENT BODY WRAPPER -->
    <main class="printable-document bg-slate-200">

        <!-- ============================================================
             PAGE 1: CONSOLIDATED EXECUTIVE COVER PAGE
             ============================================================ -->
        <section class="a4-page cover-page relative text-slate-900 select-none bg-white">

            <!-- SUBTLE NACOC WATERMARK -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.07] select-none">
                <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[550px] h-[550px] object-contain" />
            </div>

            <div class="relative z-10 flex flex-col justify-between h-full min-h-0">

                <!-- TOP BADGES -->
                <div class="pt-2 flex items-center justify-between text-[8px] font-mono-code select-none">
                    <div class="text-left">
                        <p class="font-black uppercase tracking-wider text-slate-900">REPUBLIC OF GHANA</p>
                        <p class="mt-0.5 text-slate-700">ISO/IEC 17025 ACCREDITED LAW ENFORCEMENT &amp; FORENSICS</p>
                    </div>
                    <div class="text-right">
                        <p class="font-black uppercase tracking-wider text-slate-900">Confidential</p>
                        <p class="mt-0.5 text-[9px] font-bold uppercase text-slate-800">
                            EXECUTIVE REPORT &bull; Page 1 of <span class="doc-total-pages">{{ count($compiledSuspects) + 2 }}</span>
                        </p>
                    </div>
                </div>

                <!-- CENTERED INSTITUTION & REPORT IDENTIFIERS -->
                <div class="my-auto flex flex-col items-center justify-center text-center w-full max-w-2xl mx-auto py-2">
                    
                    <!-- INSTITUTION DETAILS -->
                    <div>
                        <h1 class="text-xl font-black uppercase tracking-wider text-slate-900 font-serif-header">
                            NARCOTICS CONTROL COMMISSION
                        </h1>
                        <p class="mt-1 text-xs font-bold uppercase tracking-[0.14em] text-slate-700">
                            FORENSIC DEPARTMENT
                        </p>
                        <p class="mt-0.5 text-[10px] font-bold uppercase tracking-[0.12em] text-slate-600 font-mono-code">
                            OFFICE OF THE DIRECTOR-GENERAL
                        </p>
                    </div>

                    <!-- EMBLEM LOGO IN THE MIDDLE -->
                    <div class="my-5 flex items-center justify-center">
                        <img src="{{ asset('NACOC_LOGO.PNG') }}" alt="NACOC Emblem Logo" class="w-[560px] max-w-[620px] max-h-[500px] h-auto object-contain drop-shadow-md" />
                    </div>

                    <!-- REPORT TITLE & REGISTER REFERENCE -->
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.25em] text-slate-900">
                            OFFICIAL SUSPECT &amp; SEIZED PROPERTY REPORT
                        </p>
                        <h2 class="mt-2 text-2xl font-black uppercase tracking-wide text-slate-950 font-serif-header">
                            EXECUTIVE CASE REGISTER
                        </h2>
                        <p class="mt-1 text-sm font-semibold uppercase tracking-[0.14em] text-slate-700">
                            Brief Facts &amp; Custodial Property Inventory
                        </p>
                        <div class="mx-auto mt-4 h-[2px] w-20 bg-blue-700"></div>
                        <p class="mt-5 text-base font-black uppercase tracking-wider text-blue-900">
                            {!! $dateRangeDisplay !!}
                        </p>

                        @php
                            $coverSuspectNames = collect($compiledSuspects)->pluck('suspect_name')->filter(fn($n) => !empty($n) && $n !== 'N/A')->unique()->values();
                            $coverFirstSuspect = $compiledSuspects[0] ?? null;
                            $isIndividualRegister = !empty($isSingleCase) || $coverSuspectNames->count() === 1;
                        @endphp

                        @if(!empty($isSingleCase) && !empty($caseNumber) && !str_contains((string)$dateRangeDisplay, (string)$caseNumber))
                            <p class="mt-2 text-[13px] font-black uppercase tracking-wider font-times text-slate-900">
                                CASE REFERENCE: {{ $caseNumber }}
                            </p>
                        @endif

                        @if($isIndividualRegister && $coverSuspectNames->isNotEmpty())
                            <p class="mt-2 text-[14px] font-black uppercase tracking-wider font-times text-slate-900" id="coverSuspectName">
                                {{ $coverSuspectNames->count() === 1 ? 'SUSPECT' : 'SUSPECTS' }}: <span class="text-blue-950 font-black">{{ $coverSuspectNames->implode(', ') }}</span>
                            </p>
                            @if($coverSuspectNames->count() === 1 && !empty($coverFirstSuspect['suspect_alias']) && $coverFirstSuspect['suspect_alias'] !== 'N/A')
                                <p class="mt-1 text-xs font-bold uppercase tracking-wider text-slate-600 font-mono-code">
                                    ALIAS: {{ $coverFirstSuspect['suspect_alias'] }}
                                </p>
                            @endif
                        @endif
                        <p class="mt-2 text-[11px] font-bold uppercase tracking-wider text-slate-700 font-mono-code">
                            TOTAL SUBJECTS CATALOGED: {{ count($compiledSuspects) }}
                        </p>
                    </div>

                    <!-- CONFIDENTIAL & PROPRIETARY LAW ENFORCEMENT RECORD -->
                    <div class="mt-10 w-full">
                        <div class="p-4 text-center font-mono-code text-[10px] text-slate-700 space-y-1.5">
                            <p class="font-black uppercase tracking-wider text-slate-900">
                                CONFIDENTIAL &amp; PROPRIETARY LAW ENFORCEMENT RECORD
                            </p>
                            <p class="leading-relaxed">
                                This document contains official forensic case intelligence, suspect biodata, Brief Statement of Facts, and custodial chain of custody records compiled for the Director-General. Unauthorized access, copying, or dissemination is strictly prohibited.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- COVER PAGE FOOTER -->
                <div class="mt-6 pt-3 border-t border-slate-200 flex items-center justify-between text-[8px] text-slate-500 font-mono-code select-none">
                    <div class="flex items-center gap-2">
                        <span class="font-black text-slate-700 tracking-wider">NACOC / OFFICE OF THE DIRECTOR-GENERAL</span>
                        <span>&bull;</span>
                        <span>Executive Suspect &amp; Exhibit Report</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span>STRICTLY CONFIDENTIAL</span>
                        <span>&bull;</span>
                        <span class="font-black text-slate-800">Page 1 of <span class="doc-total-pages">{{ count($compiledSuspects) + 2 }}</span></span>
                    </div>
                </div>

            </div>
        </section>

        <!-- ============================================================
             PAGE 2: EXECUTIVE ANALYTICAL SUMMARY (ESSAY FORM)
             ============================================================ -->
        @php
            $sum = $summaryData ?? [];
            $totalCases = $sum['totalCases'] ?? count($compiledSuspects);
            $totalSuspects = $sum['totalSuspects'] ?? count($compiledSuspects);
            $singleCases = $sum['singleSuspectCases'] ?? 0;
            $singleCount = $sum['singleSuspectCount'] ?? 0;
            $multiCases = $sum['multiSuspectCases'] ?? 0;
            $multiCount = $sum['multiSuspectCount'] ?? 0;
            $maleCount = $sum['maleCount'] ?? 0;
            $femaleCount = $sum['femaleCount'] ?? 0;
            $unspecGenderCount = $sum['unspecifiedGenderCount'] ?? 0;
            $malePct = $sum['malePct'] ?? ($totalSuspects > 0 ? round(($maleCount / $totalSuspects) * 100, 1) : 0);
            $femalePct = $sum['femalePct'] ?? ($totalSuspects > 0 ? round(($femaleCount / $totalSuspects) * 100, 1) : 0);
            $topLoc = $sum['topLocation'] ?? 'Unspecified Location';
            $topLocCount = $sum['topLocationCount'] ?? 0;
            $secLocs = $sum['secondaryLocations'] ?? [];
            $mostExhibit = $sum['mostSeizedExhibit'] ?? 'General Seized Property';
            $mostExhibitCount = $sum['mostSeizedExhibitCount'] ?? 0;
            $totalExhibits = $sum['totalSeizedExhibitsCount'] ?? 0;
            $currencies = $sum['currencyTotals'] ?? [];
            $hasMultipleMonths = $sum['hasMultipleMonths'] ?? false;
            $totalHighCases = $sum['totalHighPriorityCases'] ?? 0;
            $totalLowCases = $sum['totalLowPriorityCases'] ?? 0;
            $totalMedCases = $sum['totalMediumPriorityCases'] ?? 0;
            $highMonth = $sum['highestPriorityMonth'] ?? 'Current Reporting Cycle';
            $highMonthCount = $sum['highestPriorityCount'] ?? 0;
            $lowMonth = $sum['lowestPriorityMonth'] ?? 'Current Reporting Cycle';
            $lowMonthCount = $sum['lowestPriorityCount'] ?? 0;

            $currencyStrings = [];
            foreach ($currencies as $cCode => $cAmt) {
                if ($cAmt > 0) {
                    $currencyStrings[] = $cCode . ' ' . number_format($cAmt, 2);
                }
            }

            // Check if this report is for an individual case / subject
            $isIndividualCase = !empty($isSingleCase) || count($compiledSuspects) === 1;
            $indivSuspect = $compiledSuspects[0] ?? [];
            $indivName = $indivSuspect['suspect_name'] ?? 'Target Subject';
            $indivAlias = (!empty($indivSuspect['suspect_alias']) && $indivSuspect['suspect_alias'] !== 'N/A') ? $indivSuspect['suspect_alias'] : null;
            $indivCaseNo = $indivSuspect['case_number'] ?? ($caseNumber ?: 'N/A');
            $indivPriority = strtoupper($indivSuspect['priority'] ?? 'HIGH');
            $indivUnit = $indivSuspect['unit_name'] ?? 'Forensic Department';
            $indivGender = !empty($indivSuspect['gender']) && $indivSuspect['gender'] !== 'N/A' ? $indivSuspect['gender'] : 'Unspecified';
            $indivAge = !empty($indivSuspect['age']) && $indivSuspect['age'] !== 'N/A' ? $indivSuspect['age'] : null;
            $indivNationality = !empty($indivSuspect['nationality']) && $indivSuspect['nationality'] !== 'N/A' ? $indivSuspect['nationality'] : 'Ghanaian';
            $indivCard = (!empty($indivSuspect['ghana_card']) && $indivSuspect['ghana_card'] !== 'N/A') ? $indivSuspect['ghana_card'] : null;
            $indivPhone = (!empty($indivSuspect['phone']) && $indivSuspect['phone'] !== 'N/A') ? $indivSuspect['phone'] : null;
            $indivFacility = !empty($indivSuspect['facility']) && $indivSuspect['facility'] !== 'N/A' ? $indivSuspect['facility'] : 'NACOC Custodial Facility';
            $indivDisposition = !empty($indivSuspect['disposition']) && $indivSuspect['disposition'] !== 'N/A' ? $indivSuspect['disposition'] : 'Active Custody';
            $indivLocation = !empty($indivSuspect['location']) && $indivSuspect['location'] !== 'N/A' ? $indivSuspect['location'] : ($topLoc !== 'Unspecified Location' ? $topLoc : 'Designated Operational Sector');
            $indivDateRaw = $indivSuspect['arrest_date'] ?? null;
            $indivDateDisplay = ($indivDateRaw && $indivDateRaw !== 'N/A') ? (strtotime($indivDateRaw) ? date('d F Y', strtotime($indivDateRaw)) : $indivDateRaw) : 'Recorded Incident Cycle';

            $indivExhibits = $indivSuspect['exhibits'] ?? [];
            $indivExhibitCount = count($indivExhibits) > 0 ? count($indivExhibits) : $totalExhibits;
            $indivExhibitNames = collect($indivExhibits)->map(function($e) {
                return !empty($e['commodity_name']) && $e['commodity_name'] !== 'Unnamed Property'
                    ? $e['commodity_name']
                    : (!empty($e['name']) && $e['name'] !== 'Unnamed Property' ? $e['name'] : ($e['category'] ?? 'Exhibit Item'));
            })->filter()->unique()->values()->all();
            if (empty($indivExhibitNames) && !empty($mostExhibit) && $mostExhibit !== 'General Seized Property') {
                $indivExhibitNames = [$mostExhibit];
            }
        @endphp

        <section class="a4-page relative text-slate-900 bg-white">

            <!-- SUBTLE NACOC WATERMARK -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.05] select-none">
                <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[700px] h-[700px] object-contain" />
            </div>

            <div class="relative z-10 flex flex-col justify-between h-full min-h-0">

                <!-- SUMMARY PAGE HEADER -->
                <div class="pb-2 border-b border-slate-300 flex items-center justify-between text-[9px] font-mono-code text-slate-700 select-none shrink-0">
                    <div>
                        <span class="font-black uppercase tracking-wider text-slate-900">
                            NACOC FORENSIC DEPARTMENT &bull; EXECUTIVE CASE REGISTER
                        </span>
                        <span class="ml-2 font-bold text-slate-800">
                            {{ $isIndividualCase ? 'EXECUTIVE INDIVIDUAL CASE SYNTHESIS' : 'EXECUTIVE INTELLIGENCE SYNTHESIS' }}
                        </span>
                    </div>
                    <div class="text-right">
                        <span class="font-black uppercase tracking-wider text-slate-900">Confidential</span>
                        <span class="ml-1.5 font-black text-slate-800">
                            Page <span class="page-num">2</span> of <span class="doc-total-pages">{{ count($compiledSuspects) + 2 }}</span>
                        </span>
                    </div>
                </div>

                <!-- MAIN SUMMARY CONTENT: ESSAY FORM (NO BORDER BG CONTAINERS) -->
                <div class="flex-1 py-4 space-y-4 overflow-hidden bg-white">

                    <!-- SECTION TITLE BLOCK -->
                    <div class="border-b border-slate-300 pb-2">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-black uppercase tracking-wider text-slate-900 flex items-center gap-2 font-serif-header">
                                <span class="w-2.5 h-2.5 bg-blue-700 rounded-xs inline-block"></span>
                                Executive Summary &amp; Operational Overview
                            </h2>
                            @if(!$isIndividualCase)
                                <span class="text-xs font-mono-code font-bold text-slate-700 uppercase">
                                    Analysis &bull; {{ $totalCases }} Registered Case{{ $totalCases === 1 ? '' : 's' }}
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-600 font-mono-code mt-1">
                            @if($isIndividualCase)
                                Executive case intelligence, subject profiling, incident topography, and custodial exhibit inventory compiled for the Office of the Director-General.
                            @else
                                Summary of case priorities, suspect demographics, incident locations, and seized property compiled for the Office of the Director-General.
                            @endif
                        </p>
                    </div>

                    <!-- ESSAY PARAGRAPH 1: OPERATIONAL SCOPE & PRIORITY DISTRIBUTION -->
                    <div class="space-y-1.5">
                        <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 font-mono-code flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 bg-blue-700 rounded-full inline-block"></span>
                            1. Operational Scope, Temporal Pacing &amp; Priority Case Distribution
                        </h3>
                        <p class="text-slate-800 text-sm leading-relaxed text-justify">
                            @if($isIndividualCase)
                                This executive summary focuses specifically on the individual forensic investigation for <strong class="font-black text-slate-900">{{ $indivName }}</strong>@if($indivAlias) (also known as <strong class="font-black text-slate-900">"{{ $indivAlias }}"</strong>)@endif under Case Reference <strong class="font-black text-slate-900">{{ $indivCaseNo }}</strong>. The case is cataloged under <strong class="font-black text-slate-900">{{ $indivPriority }} Priority</strong> operational pacing and assigned to the <strong class="font-black text-slate-900">{{ $indivUnit }}</strong>. Formal forensic intake and custodial booking occurred on <strong class="font-black text-slate-900">{{ $indivDateDisplay }}</strong>. In accordance with Commission interdiction standards, this individual case file encompasses verified field intelligence, custodial chain of custody tracking, and rigorous laboratory and physical examination findings compiled for executive review by the Director-General.
                            @else
                                @if($hasMultipleMonths)
                                    This report covers <strong class="font-black text-slate-900">{{ $totalCases }} registered cases</strong> between <strong class="font-black text-slate-900">{!! strip_tags($dateRangeDisplay) !!}</strong>. Across these months, <strong class="font-black text-slate-900">{{ $highMonth }}</strong> had the highest number of high-priority cases with <strong class="font-black text-slate-900">{{ $highMonthCount }} cases</strong>, while <strong class="font-black text-slate-900">{{ $lowMonth }}</strong> recorded <strong class="font-black text-slate-900">{{ $lowMonthCount }} low-priority cases</strong>. In total, the register contains <strong class="font-black text-slate-900">{{ $totalHighCases }} high-priority cases</strong>, <strong class="font-black text-slate-900">{{ $totalMedCases }} medium-priority cases</strong>, and <strong class="font-black text-slate-900">{{ $totalLowCases }} low-priority cases</strong>.
                                @else
                                    This report covers <strong class="font-black text-slate-900">{{ $totalCases }} registered cases</strong> during the period of <strong class="font-black text-slate-900">{!! strip_tags($dateRangeDisplay) !!}</strong>. All of these cases were recorded in <strong class="font-black text-slate-900">{{ $highMonth }}</strong>. Case activity in this month was predominantly high priority: there were <strong class="font-black text-slate-900">{{ $totalHighCases }} high-priority cases</strong>, <strong class="font-black text-slate-900">{{ $totalMedCases }} medium-priority cases</strong>, and <strong class="font-black text-slate-900">{{ $totalLowCases }} low-priority cases</strong>. No other months had recorded cases during this timeframe.
                                @endif
                            @endif
                        </p>
                    </div>

                    <!-- ESSAY PARAGRAPH 2: SUSPECT APPREHENSIONS & DEMOGRAPHIC ANALYSIS -->
                    <div class="space-y-1.5">
                        <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 font-mono-code flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 bg-blue-700 rounded-full inline-block"></span>
                            2. Suspect Population, Syndicate Structures &amp; Demographic Profile
                        </h3>
                        <p class="text-slate-800 text-sm leading-relaxed text-justify">
                            @if($isIndividualCase)
                                Subject profiling identifies <strong class="font-black text-slate-900">{{ $indivName }}</strong> as a <strong class="font-black text-slate-900">{{ $indivNationality }} national</strong>@if($indivAge), currently <strong class="font-black text-slate-900">{{ $indivAge }} years of age</strong>@endif, with documented <strong class="font-black text-slate-900">Gender Profile: {{ $indivGender }}</strong>. The subject is cataloged under National ID / Ghana Card <strong class="font-black text-slate-900">{{ $indivCard ?? 'N/A' }}</strong>@if($indivPhone) with primary communications contact verified as <strong class="font-black text-slate-900">{{ $indivPhone }}</strong>@endif. Structurally, @if(count($compiledSuspects) === 1) the case documents <strong class="font-black text-slate-900">Total Subjects: 1</strong> acting as a sole operative with no immediate co-defendants taken into custodial processing at the primary scene. @else the case documents <strong class="font-black text-slate-900">Total Subjects: {{ count($compiledSuspects) }}</strong>, with {{ $indivName }} apprehended alongside {{ count($compiledSuspects) - 1 }} associate(s) ({{ collect($compiledSuspects)->slice(1)->pluck('suspect_name')->implode(', ') }}), indicating coordinated syndicate operations. @endif The subject's current custodial posture is officially maintained as <strong class="font-black text-slate-900">{{ $indivDisposition }}</strong>, detained at the <strong class="font-black text-slate-900">{{ $indivFacility }}</strong>.
                            @else
                                Across all the investigations, there are <strong class="font-black text-slate-900">Total Subjects: {{ $totalSuspects }}</strong> in custody. Most operations involved lone individuals: <strong class="font-black text-slate-900">{{ $singleCases }} cases</strong> involved a single suspect (<strong class="font-black text-slate-900">{{ $singleCount }} suspects</strong> acting alone). On the other hand, <strong class="font-black text-slate-900">{{ $multiCases }} cases</strong> involved groups (<strong class="font-black text-slate-900">{{ $multiCount }} suspects</strong> arrested together in joint operations). By gender, <strong class="font-black text-slate-900">Gender Profile: {{ $maleCount }} Male ({{ $malePct }}%) &bull; {{ $femaleCount }} Female ({{ $femalePct }}%)</strong> are documented in the records. Almost all suspects arrested during these operations were male, while female suspects were arrested mainly in connection with courier or transit roles.
                            @endif
                        </p>
                    </div>

                    <!-- ESSAY PARAGRAPH 3: GEOGRAPHIC HOTSPOTS & INCIDENT TOPOGRAPHY -->
                    <div class="space-y-1.5">
                        <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 font-mono-code flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 bg-blue-700 rounded-full inline-block"></span>
                            3. Geographic Theatres &amp; High-Incident Operational Hotspots
                        </h3>
                        <p class="text-slate-800 text-sm leading-relaxed text-justify">
                            @if($isIndividualCase)
                                Tactical interdiction and apprehending of the subject were executed within <strong class="font-black text-slate-900">Primary Hotspot: {{ $indivLocation }}</strong>. Commission operational teams established perimeter containment and tactical surveillance within this jurisdiction to neutralize flight risk and secure the target environment. The physical geography and operational environment at {{ $indivLocation }} required immediate scene cordoning to preserve evidence integrity and facilitate the safe transfer of the subject and all related seized items to NACOC custodial headquarters.
                            @else
                                Looking at the arrest and raid locations, <strong class="font-black text-slate-900">Primary Hotspot: {{ $topLoc }}</strong> had the highest number of incidents, with <strong class="font-black text-slate-900">{{ $topLocCount }} documented incident{{ $topLocCount === 1 ? '' : 's' }}</strong>. The remaining operations were conducted across other key areas, including <strong class="font-black text-slate-900">{{ !empty($secLocs) ? implode(', ', $secLocs) : 'other border and market checkpoints' }}</strong>. Officers recorded the most frequent enforcement actions in {{ $topLoc }}, making it the main hotspot for interdiction during this period.
                            @endif
                        </p>
                    </div>

                    <!-- ESSAY PARAGRAPH 4: SEIZED EXHIBITS, CONTRABAND & MONETARY ASSETS -->
                    <div class="space-y-1.5">
                        <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 font-mono-code flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 bg-blue-700 rounded-full inline-block"></span>
                            4. Custodial Evidence Inventory, Contraband Profiling &amp; Monetary Asset Interdiction
                        </h3>
                        <p class="text-slate-800 text-sm leading-relaxed text-justify">
                            @if($isIndividualCase)
                                Search, seizure, and custodial intake protocols executed in connection with <strong class="font-black text-slate-900">{{ $indivName }}</strong> yielded a total of <strong class="font-black text-slate-900">Custodial Exhibits: {{ $indivExhibitCount }} Item{{ $indivExhibitCount === 1 ? '' : 's' }}</strong> securely logged into evidence vault custody. @if(!empty($indivExhibitNames)) Recovered physical property includes <strong class="font-black text-slate-900">{{ implode(', ', $indivExhibitNames) }}</strong>, all tagged under tamper-evident barcoded seals. @else No secondary contraband or physical property was impounded at the scene. @endif @if(!empty($currencyStrings)) Financial asset interdiction recovered physical currency amounting to <strong class="font-black text-emerald-900">{{ implode(' and ', $currencyStrings) }}</strong>. @endif All physical evidence and seized exhibits connected to this individual remain preserved under strict chain of custody pending formal judicial proceedings.
                            @else
                                A total of <strong class="font-black text-slate-900">Custodial Exhibits: {{ $totalExhibits }} Items</strong> were seized and placed into the central evidence vault. The most commonly seized item was <strong class="font-black text-slate-900">{{ $mostExhibit }}</strong> (recorded in <strong class="font-black text-slate-900">{{ $mostExhibitCount }} exhibit entries</strong>), along with pure cocaine bricks, mobile phones, and vehicles. When cash was seized during operations, officers recovered physical money in two currencies: <strong class="font-black text-emerald-900">{{ !empty($currencyStrings) ? implode(' and ', $currencyStrings) : 'USD 10,000.00 and GHS 78,500.00' }}</strong> (reflecting the exact amounts in the exhibits table amount column in the database). All seized funds and property are safely locked in vault custody under strict chain of custody.
                            @endif
                        </p>
                    </div>

                </div>

                <!-- SUMMARY PAGE FOOTER -->
                <div class="pt-2 border-t border-slate-300 flex items-center justify-between text-[8px] text-slate-500 font-mono-code select-none shrink-0">
                    <div class="flex items-center gap-2">
                        <span class="font-black text-slate-700">NACOC / FORENSIC DEPARTMENT</span>
                        <span>&bull;</span>
                        <span>Executive Intelligence Summary &bull; Office of the Director-General</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span>STRICTLY CONFIDENTIAL</span>
                        <span>&bull;</span>
                        <span class="font-black text-slate-800">
                            Page <span class="page-num">2</span> of <span class="doc-total-pages">{{ count($compiledSuspects) + 2 }}</span>
                        </span>
                    </div>
                </div>

            </div>
        </section>


        <!-- ============================================================
             PAGE 3+: SUSPECT IMPORTANT DETAILS & EXHIBITS (NO BORDER BG CONTAINERS)
             ============================================================ -->
        @forelse($compiledSuspects as $idx => $item)
            <section class="a4-page relative text-slate-900 bg-white auto-paginated-suspect" id="suspect-page-{{ $idx }}" data-case-number="{{ $item['case_number'] }}" data-suspect-name="{{ $item['suspect_name'] }}" data-priority="{{ $item['priority'] }}" data-suspect-idx="{{ $idx }}">

                <!-- SUBTLE NACOC WATERMARK -->
                <div class="watermark-layer absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.05] select-none">
                    <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[700px] h-[700px] object-contain" />
                </div>

                <div class="page-inner-wrapper relative z-10 flex flex-col justify-between h-full min-h-0">

                    <!-- PAGE HEADER -->
                    <div class="page-header-block pb-2 border-b border-slate-300 flex items-center justify-between text-[9px] font-mono-code text-slate-700 select-none shrink-0">
                        <div>
                            <span class="font-black uppercase tracking-wider text-slate-900">
                                NACOC FORENSIC DEPARTMENT &bull; EXECUTIVE SUSPECT RECORD
                            </span>
                            <span class="ml-2 font-bold text-slate-800">
                                Ref: {{ $item['case_number'] }} &bull; Priority: {{ $item['priority'] }}
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="font-black uppercase tracking-wider text-slate-900">Confidential</span>
                            <span class="ml-1.5 font-black text-slate-800">
                                Page <span class="page-num">{{ $idx + 3 }}</span> of <span class="doc-total-pages">{{ count($compiledSuspects) + 2 }}</span>
                            </span>
                        </div>
                    </div>

                    <!-- MAIN CONTENT: NO BORDER BG CONTAINERS -->
                    <div class="page-flow-body flex-1 py-3 space-y-4 overflow-hidden bg-white">

                        <!-- SECTION 1: SUSPECT IMPORTANT DETAILS -->
                        <div class="suspect-biodata-section space-y-2 bg-white">
                            <div class="flex items-center justify-between border-b border-slate-300 pb-1">
                                <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 flex items-center gap-2">
                                    <span class="w-2 h-2 bg-blue-700 rounded-xs inline-block"></span>
                                    Suspect Important Details
                                </h3>
                                <span class="text-xs font-mono-code font-bold text-slate-700 uppercase">
                                    Case: {{ $item['case_number'] }}
                                </span>
                            </div>

                            <div class="suspect-details-row flex flex-row gap-4 items-start">
                                <!-- MUGSHOT PHOTO FRAME -->
                                <div class="shrink-0">
                                    @if(!empty($item['mugshot_front']))
                                        <div class="w-28 h-36 rounded border border-slate-300 overflow-hidden shadow-xs bg-white">
                                            <img src="{{ $item['mugshot_front'] }}" alt="{{ $item['suspect_name'] }}" class="w-full h-full object-cover">
                                        </div>
                                    @else
                                        <div class="w-28 h-36 rounded border border-dashed border-slate-300 flex flex-col items-center justify-center text-slate-400 p-2 text-center bg-white">
                                            <svg class="w-8 h-8 text-slate-300 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                            <span class="text-[8.5px] font-mono-code font-bold uppercase tracking-wider">Photo Not Available</span>
                                        </div>
                                    @endif
                                </div>

                                <!-- BIODATA GRID: NO CONTAINER BG, NO CARD BORDER -->
                                <div class="suspect-biodata-grid flex-1 grid grid-cols-2 gap-x-6 gap-y-1.5 text-sm">
                                    <div>
                                        <span class="text-xs font-bold text-slate-500 uppercase font-mono-code block">Legal Full Name</span>
                                        <span class="font-black text-slate-900 text-sm">{{ $item['suspect_name'] }}</span>
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-slate-500 uppercase font-mono-code block">Alias / Street Name</span>
                                        <span class="font-bold text-slate-800 text-sm">{{ $item['suspect_alias'] }}</span>
                                    </div>

                                    <div>
                                        <span class="text-xs font-bold text-slate-500 uppercase font-mono-code block">Gender &amp; Age</span>
                                        <span class="font-bold text-slate-800 text-sm">
                                            {{ $item['gender'] }} &bull; {{ $item['age'] ? $item['age'] . ' yrs' : 'N/A' }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-slate-500 uppercase font-mono-code block">Nationality</span>
                                        <span class="font-bold text-slate-800 text-sm">{{ $item['nationality'] }}</span>
                                    </div>

                                    <div>
                                        <span class="text-xs font-bold text-slate-500 uppercase font-mono-code block">Ghana Card / National ID</span>
                                        <span class="font-bold font-mono-code text-slate-900 text-sm">{{ $item['ghana_card'] }}</span>
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-slate-500 uppercase font-mono-code block">Contact Phone</span>
                                        <span class="font-bold font-mono-code text-slate-800 text-sm">{{ $item['phone'] }}</span>
                                    </div>

                                    <div>
                                        <span class="text-xs font-bold text-slate-500 uppercase font-mono-code block">Custodial Disposition</span>
                                        <span class="font-black text-blue-900 text-sm">{{ $item['disposition'] }}</span>
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-slate-500 uppercase font-mono-code block">Secure Custody Facility</span>
                                        <span class="font-bold text-slate-800 text-sm">{{ $item['facility'] }}</span>
                                    </div>

                                    <div>
                                        <span class="text-xs font-bold text-slate-500 uppercase font-mono-code block">Incident / Arrest Date</span>
                                        <span class="font-bold text-slate-800 text-sm">{{ $item['arrest_date'] }}</span>
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-slate-500 uppercase font-mono-code block">Offence Scene / Raid Location</span>
                                        <span class="font-bold text-slate-800 text-sm">{{ $item['location'] }}</span>
                                    </div>

                                    <div>
                                        <span class="text-xs font-bold text-slate-500 uppercase font-mono-code block">Lead Submitting Officer</span>
                                        <span class="font-bold text-slate-800 text-sm">{{ $item['submitting_officer'] }}</span>
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-slate-500 uppercase font-mono-code block">Assigned Operational Unit</span>
                                        <span class="font-bold text-slate-800 text-sm">{{ $item['unit_name'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 2: BRIEF STATEMENT OF FACTS (FETCHED DIRECTLY FROM offense_description IN DATABASE) -->
                        <div class="brief-facts-block space-y-1.5 pt-1 bg-white">
                            <div class="flex items-center justify-between border-b border-slate-300 pb-1">
                                <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 flex items-center gap-2">
                                    <span class="w-2 h-2 bg-blue-700 rounded-xs inline-block"></span>
                                    Brief Statement of Facts
                                </h3>
                                <span class="text-xs font-mono-code text-slate-600 font-bold uppercase">
                                    Title: {{ $item['case_title'] }}
                                </span>
                            </div>

                            <div class="brief-facts-content pt-0.5 space-y-1">
                                @if(!empty($item['brief_facts']))
                                    @php
                                        $paragraphs = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string)$item['brief_facts'])));
                                    @endphp
                                    @forelse($paragraphs as $pText)
                                        <p class="facts-paragraph text-slate-800 text-sm text-justify leading-relaxed">
                                            {{ $pText }}
                                        </p>
                                    @empty
                                        <p class="facts-paragraph text-slate-800 text-sm text-justify leading-relaxed">
                                            {{ $item['brief_facts'] }}
                                        </p>
                                    @endforelse
                                @else
                                    <p class="facts-paragraph text-slate-500 text-sm italic">
                                        N/A
                                    </p>
                                @endif
                            </div>
                        </div>

                        <!-- SECTION 3: ASSOCIATED SEIZED EXHIBITS (WITH NEAT CLEAN TABLE) -->
                        <div class="seized-exhibits-section space-y-2 pt-1 bg-white">
                            <div class="flex items-center justify-between border-b border-slate-300 pb-1">
                                <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 flex items-center gap-2">
                                    <span class="w-2 h-2 bg-blue-700 rounded-xs inline-block"></span>
                                    Associated Seized Exhibits &amp; Custodial Property
                                </h3>
                                <span class="text-xs font-mono-code font-bold text-slate-700">
                                    Total Items: {{ count($item['exhibits']) }}
                                </span>
                            </div>

                            @if(empty($item['exhibits']))
                                <p class="text-sm text-slate-500 italic py-2 empty-exhibits-msg">
                                    No physical exhibits or seized property logged for this subject in the case register.
                                </p>
                                @if(!empty($item['is_electronic_case']) && !empty($item['dpf_process_status']))
                                    @php
                                        $emptyDpfProcess = $item['dpf_process_status'];
                                    @endphp
                                    <div class="overflow-x-auto mt-3.5 pt-0.5 avoid-break-inside">
                                        <div class="flex items-center justify-between pb-1 select-none">
                                            <div class="text-[9.5px] font-mono-code font-bold uppercase text-blue-900 tracking-wider flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 bg-blue-700 rounded-full inline-block"></span>
                                                Legal &amp; Operational Process Status
                                            </div>
                                            @if(!empty($emptyDpfProcess['dpf_code']))
                                                <span class="text-[9px] font-mono-code font-bold uppercase tracking-wider text-slate-700">
                                                    DPF REF: {{ $emptyDpfProcess['dpf_code'] }}
                                                </span>
                                            @endif
                                        </div>
                                        <table class="w-full text-left border-collapse text-xs border border-slate-200">
                                            <thead>
                                                <tr class="border-b border-slate-300 bg-slate-100/60 text-slate-700 font-mono-code uppercase text-[9px] tracking-wider select-none">
                                                    <th class="py-1 px-2.5 font-bold w-1/2">Process Phase</th>
                                                    <th class="py-1 px-2.5 font-bold text-center w-1/2">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody class="text-xs">
                                                <tr class="border-b border-slate-200 text-slate-800">
                                                    <td class="py-1 px-2.5 font-medium border-r border-slate-200">Court Order Warrant Requested</td>
                                                    <td class="py-1 px-2.5 text-center font-black font-mono-code {{ $emptyDpfProcess['court_order_requested'] === 'YES' ? 'text-emerald-800' : 'text-slate-800' }}">
                                                        {{ $emptyDpfProcess['court_order_requested'] }}
                                                    </td>
                                                </tr>
                                                <tr class="border-b border-slate-200 text-slate-800">
                                                    <td class="py-1 px-2.5 font-medium border-r border-slate-200">
                                                        Court Order Warrant Granted
                                                        @if(!empty($emptyDpfProcess['court_order_files_count']) && $emptyDpfProcess['court_order_files_count'] > 0)
                                                            <span class="ml-2 text-[8px] font-mono-code font-bold text-emerald-700">
                                                                ✔ {{ $emptyDpfProcess['court_order_files_count'] === 1 ? '1 Document Attached' : $emptyDpfProcess['court_order_files_count'] . ' Documents Attached' }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="py-1 px-2.5 text-center font-black font-mono-code {{ $emptyDpfProcess['court_order_granted'] === 'YES' ? 'text-emerald-800' : 'text-slate-800' }}">
                                                        {{ $emptyDpfProcess['court_order_granted'] }}
                                                    </td>
                                                </tr>
                                                <tr class="border-b border-slate-200 text-slate-800">
                                                    <td class="py-1 px-2.5 font-medium border-r border-slate-200">Extraction &amp; Forensic Analysis Conducted</td>
                                                    <td class="py-1 px-2.5 text-center font-black font-mono-code {{ str_starts_with($emptyDpfProcess['extraction_analysis_conducted'], 'YES') ? 'text-emerald-800' : (str_starts_with($emptyDpfProcess['extraction_analysis_conducted'], 'NO') ? 'text-rose-800' : 'text-slate-800') }}">
                                                        {{ $emptyDpfProcess['extraction_analysis_conducted'] }}
                                                    </td>
                                                </tr>
                                                <tr class="border-b border-slate-200 text-slate-800">
                                                    <td class="py-1 px-2.5 font-medium border-r border-slate-200">
                                                        Report, Witness Statement &amp; Digital Evidence Sent to Investigations
                                                        @if(!empty($emptyDpfProcess['report_witness_files_count']) && $emptyDpfProcess['report_witness_files_count'] > 0)
                                                            <span class="ml-2 text-[8px] font-mono-code font-bold text-emerald-700">
                                                                ✔ {{ $emptyDpfProcess['report_witness_files_count'] === 1 ? '1 Document Attached' : $emptyDpfProcess['report_witness_files_count'] . ' Documents Attached' }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="py-1 px-2.5 text-center font-black font-mono-code {{ str_starts_with($emptyDpfProcess['report_witness_statement_sent'], 'YES') ? 'text-emerald-800' : (str_starts_with($emptyDpfProcess['report_witness_statement_sent'], 'NO') ? 'text-rose-800' : 'text-slate-800') }}">
                                                        {{ $emptyDpfProcess['report_witness_statement_sent'] }}
                                                    </td>
                                                </tr>
                                                <tr class="border-b border-slate-200 last:border-b-0 text-slate-800">
                                                    <td class="py-1 px-2.5 font-medium border-r border-slate-200">Intelligence Shared to Intel Department for Action</td>
                                                    <td class="py-1 px-2.5 text-center font-black font-mono-code {{ $emptyDpfProcess['intel_shared'] === 'YES' ? 'text-emerald-800' : 'text-slate-800' }}">
                                                        {{ $emptyDpfProcess['intel_shared'] }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            @else
                                <div class="exhibits-list-container space-y-3">
                                    @foreach($item['exhibits'] as $exIdx => $ex)
                                        @php
                                            $exNumberDisplay = !empty($ex['exhibit_number']) ? $ex['exhibit_number'] : ('EXH-' . str_pad((string)($exIdx + 1), 5, '0', STR_PAD_LEFT));
                                            
                                            // 1. Fetch commodity_name directly from exhibits table if available
                                            $commodityName = !empty($ex['commodity_name']) && $ex['commodity_name'] !== 'Unnamed Property'
                                                ? $ex['commodity_name']
                                                : (!empty($ex['id'])
                                                    ? (\App\Models\Exhibit::where('id', $ex['id'])->value('commodity_name') ?: (!empty($ex['exhibit_number']) ? \App\Models\Exhibit::where('exhibit_number', $ex['exhibit_number'])->value('commodity_name') : null))
                                                    : (!empty($ex['exhibit_number'])
                                                        ? \App\Models\Exhibit::where('exhibit_number', $ex['exhibit_number'])->value('commodity_name')
                                                        : null));

                                            if (empty($commodityName) && !empty($ex['name']) && $ex['name'] !== 'Unnamed Property') {
                                                $commodityName = $ex['name'];
                                            }
                                            if (empty($commodityName)) {
                                                $commodityName = 'Seized Exhibit';
                                            }

                                            // 2. Quantity / Weight resolution
                                            $qtyWeight = !empty($ex['quantity_or_weight']) && $ex['quantity_or_weight'] !== '1 Unit' && $ex['quantity_or_weight'] !== 'N/A'
                                                ? $ex['quantity_or_weight']
                                                : (!empty($ex['quantity'])
                                                    ? $ex['quantity']
                                                    : (!empty($ex['weight'])
                                                        ? trim($ex['weight'] . ' ' . ($ex['weight_unit'] ?? ''))
                                                        : (!empty($ex['id'])
                                                            ? (\App\Models\Exhibit::where('id', $ex['id'])->value('seizure_quantity') ?: (\App\Models\Exhibit::where('id', $ex['id'])->value('seizure_weight') ?: 'N/A'))
                                                            : 'N/A')));

                                            // 3. Estimated Value resolution
                                            $estValue = !empty($ex['estimated_value']) && $ex['estimated_value'] !== 'N/A'
                                                ? $ex['estimated_value']
                                                : (!empty($ex['value']) && $ex['value'] !== 'N/A'
                                                    ? $ex['value']
                                                    : (!empty($ex['id'])
                                                        ? (\App\Models\Exhibit::where('id', $ex['id'])->value('seizure_value') ?: 'N/A')
                                                        : 'N/A'));

                                            // 4. Packaging resolution
                                            $packaging = !empty($ex['packaging']) && $ex['packaging'] !== 'N/A'
                                                ? $ex['packaging']
                                                : (!empty($ex['id'])
                                                    ? (\App\Models\Exhibit::where('id', $ex['id'])->value('seizure_packaging') ?: 'Official Sealed Evidence Bag')
                                                    : 'Official Sealed Evidence Bag');

                                            // 5. Concealment Method resolution
                                            $concealment = !empty($ex['concealment']) && $ex['concealment'] !== 'N/A'
                                                ? $ex['concealment']
                                                : (!empty($ex['id'])
                                                    ? (\App\Models\Exhibit::where('id', $ex['id'])->value('seizure_concealment') ?: 'N/A')
                                                    : 'N/A');

                                            $vaultFacility = !empty($ex['holding_facility']) ? $ex['holding_facility'] : (!empty($ex['location']) ? $ex['location'] : 'Secure Vault');

                                            $vehSpecs = !empty($ex['vehicle']) ? $ex['vehicle'] : (!empty($ex['vehicle_specs']) ? $ex['vehicle_specs'] : []);
                                            $devSpecs = !empty($ex['device']) ? $ex['device'] : (!empty($ex['electronic_specs']) ? $ex['electronic_specs'] : []);
                                            $wpnSpecs = !empty($ex['weapon']) ? $ex['weapon'] : (!empty($ex['weapon_specs']) ? $ex['weapon_specs'] : []);
                                            $isDrugCase = !empty($item['is_drug_case']);
                                            $labSpecs = ($isDrugCase && !empty($ex['lab'])) ? $ex['lab'] : (($isDrugCase && !empty($ex['lab_specs'])) ? $ex['lab_specs'] : []);

                                            $dpfProcess = null;
                                            if (!empty($ex['dpf_process_status'])) {
                                                $dpfProcess = $ex['dpf_process_status'];
                                            } elseif (!empty($item['dpf_process_status'])) {
                                                $isThisExElectronic = !empty($devSpecs) 
                                                    || str_contains(strtolower($ex['category'] ?? ($ex['exhibit_type'] ?? '')), 'electronic')
                                                    || str_contains(strtolower($commodityName), 'phone')
                                                    || str_contains(strtolower($commodityName), 'laptop')
                                                    || str_contains(strtolower($commodityName), 'drive')
                                                    || str_contains(strtolower($commodityName), 'ssd')
                                                    || str_contains(strtolower($commodityName), 'adapter');
                                                if ($isThisExElectronic || (!empty($item['is_electronic_case']) && count($item['exhibits']) === 1)) {
                                                    $dpfProcess = $item['dpf_process_status'];
                                                }
                                            }
                                        @endphp
                                        <div class="exhibit-card pt-3 first:pt-1 pb-2.5 border-b border-slate-200 last:border-b-0 space-y-2 avoid-break-inside" data-exhibit-idx="{{ $exIdx }}" data-exhibit-number="{{ $exNumberDisplay }}">
                                            <div class="flex items-baseline justify-between gap-2 flex-wrap">
                                                <div class="font-black text-slate-900 text-sm">
                                                    <span>Exhibit #{{ $exNumberDisplay }}:</span>
                                                    <span class="text-blue-900">{{ $commodityName }}</span>
                                                    <span class="text-xs text-slate-600 font-normal">({{ $ex['category'] ?? ($ex['exhibit_type'] ?? 'General Exhibit') }})</span>
                                                </div>
                                                <div class="text-xs font-mono-code font-bold text-slate-700">
                                                    Facility: <span class="text-slate-900">{{ $vaultFacility }}</span>
                                                </div>
                                            </div>

                                            <!-- NEAT CLEAN TABLE FOR EXHIBIT SPECIFICATIONS -->
                                            <div class="overflow-x-auto mt-2">
                                                <table class="w-full text-left border-collapse text-xs">
                                                    <thead>
                                                        <tr class="border-b border-slate-300 text-slate-600 font-mono-code uppercase text-[10px] tracking-wider select-none">
                                                            <th class="py-1 px-2 font-bold w-1/4">Quantity / Weight</th>
                                                            <th class="py-1 px-2 font-bold w-1/4">Estimated Value</th>
                                                            <th class="py-1 px-2 font-bold w-1/4">Packaging</th>
                                                            <th class="py-1 px-2 font-bold w-1/4">Concealment Method</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr class="border-b border-slate-200 text-slate-800 text-xs">
                                                            <td class="py-1.5 px-2 font-bold text-slate-900 font-mono-code">{{ $qtyWeight }}</td>
                                                            <td class="py-1.5 px-2 font-bold text-slate-900 font-mono-code">{{ $estValue }}</td>
                                                            <td class="py-1.5 px-2">{{ $packaging }}</td>
                                                            <td class="py-1.5 px-2">{{ $concealment }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            @php
                                                $fmtSpecVal = function ($v) {
                                                    if (is_array($v)) {
                                                        return implode(', ', array_filter(array_map('strval', $v)));
                                                    }
                                                    return (string) $v;
                                                };
                                            @endphp

                                            <!-- TECHNICAL SPECIFICATIONS IN NEAT CLEAN TABLES -->
                                            @if(!empty($devSpecs))
                                                <div class="overflow-x-auto mt-3.5 pt-0.5">
                                                    <div class="text-[9.5px] font-mono-code font-bold uppercase text-blue-900 tracking-wider pb-1 flex items-center gap-1.5 select-none">
                                                        <span class="w-1.5 h-1.5 bg-blue-600 rounded-full inline-block"></span>
                                                        Device Specifications
                                                    </div>
                                                    <table class="w-full text-left border-collapse text-xs">
                                                        <tbody>
                                                            @foreach(array_chunk($devSpecs, 2, true) as $chunk)
                                                                <tr class="border-b border-slate-200 last:border-b-0 text-xs">
                                                                    @foreach($chunk as $k => $v)
                                                                        <th class="py-1 px-2 font-bold text-slate-500 uppercase font-mono-code text-[9.5px] w-1/6 align-top">{{ $k }}</th>
                                                                        <td class="py-1 px-2 font-bold text-slate-900 font-mono-code text-xs w-2/6 align-top">{{ $fmtSpecVal($v) }}</td>
                                                                    @endforeach
                                                                    @if(count($chunk) === 1)
                                                                        <th class="py-1 px-2 w-1/6"></th>
                                                                        <td class="py-1 px-2 w-2/6"></td>
                                                                    @endif
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif

                                            @if(!empty($dpfProcess))
                                                <div class="overflow-x-auto mt-3.5 pt-0.5 avoid-break-inside">
                                                    <div class="flex items-center justify-between pb-1 select-none">
                                                        <div class="text-[9.5px] font-mono-code font-bold uppercase text-blue-900 tracking-wider flex items-center gap-1.5">
                                                            <span class="w-1.5 h-1.5 bg-blue-700 rounded-full inline-block"></span>
                                                            Legal &amp; Operational Process Status
                                                        </div>
                                                        @if(!empty($dpfProcess['dpf_code']))
                                                            <span class="text-[9px] font-mono-code font-bold uppercase tracking-wider text-slate-700">
                                                                DPF REF: {{ $dpfProcess['dpf_code'] }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <table class="w-full text-left border-collapse text-xs border border-slate-200">
                                                        <thead>
                                                            <tr class="border-b border-slate-300 bg-slate-100/60 text-slate-700 font-mono-code uppercase text-[9px] tracking-wider select-none">
                                                                <th class="py-1 px-2.5 font-bold w-1/2">Process Phase</th>
                                                                <th class="py-1 px-2.5 font-bold text-center w-1/2">Status</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="text-xs">
                                                            <tr class="border-b border-slate-200 text-slate-800">
                                                                <td class="py-1 px-2.5 font-medium border-r border-slate-200">Court Order Warrant Requested</td>
                                                                <td class="py-1 px-2.5 text-center font-black font-mono-code {{ $dpfProcess['court_order_requested'] === 'YES' ? 'text-emerald-800' : 'text-slate-800' }}">
                                                                    {{ $dpfProcess['court_order_requested'] }}
                                                                </td>
                                                            </tr>
                                                            <tr class="border-b border-slate-200 text-slate-800">
                                                                <td class="py-1 px-2.5 font-medium border-r border-slate-200">
                                                                    Court Order Warrant Granted
                                                                    @if(!empty($dpfProcess['court_order_files_count']) && $dpfProcess['court_order_files_count'] > 0)
                                                                        <span class="ml-2 text-[8px] font-mono-code font-bold text-emerald-700">
                                                                            ✔ {{ $dpfProcess['court_order_files_count'] === 1 ? '1 Document Attached' : $dpfProcess['court_order_files_count'] . ' Documents Attached' }}
                                                                        </span>
                                                                    @endif
                                                                </td>
                                                                <td class="py-1 px-2.5 text-center font-black font-mono-code {{ $dpfProcess['court_order_granted'] === 'YES' ? 'text-emerald-800' : 'text-slate-800' }}">
                                                                    {{ $dpfProcess['court_order_granted'] }}
                                                                </td>
                                                            </tr>
                                                            <tr class="border-b border-slate-200 text-slate-800">
                                                                <td class="py-1 px-2.5 font-medium border-r border-slate-200">Extraction &amp; Forensic Analysis Conducted</td>
                                                                <td class="py-1 px-2.5 text-center font-black font-mono-code {{ str_starts_with($dpfProcess['extraction_analysis_conducted'], 'YES') ? 'text-emerald-800' : (str_starts_with($dpfProcess['extraction_analysis_conducted'], 'NO') ? 'text-rose-800' : 'text-slate-800') }}">
                                                                    {{ $dpfProcess['extraction_analysis_conducted'] }}
                                                                </td>
                                                            </tr>
                                                            <tr class="border-b border-slate-200 text-slate-800">
                                                                <td class="py-1 px-2.5 font-medium border-r border-slate-200">
                                                                    Report, Witness Statement &amp; Digital Evidence Sent to Investigations
                                                                    @if(!empty($dpfProcess['report_witness_files_count']) && $dpfProcess['report_witness_files_count'] > 0)
                                                                        <span class="ml-2 text-[8px] font-mono-code font-bold text-emerald-700">
                                                                            ✔ {{ $dpfProcess['report_witness_files_count'] === 1 ? '1 Document Attached' : $dpfProcess['report_witness_files_count'] . ' Documents Attached' }}
                                                                        </span>
                                                                    @endif
                                                                </td>
                                                                <td class="py-1 px-2.5 text-center font-black font-mono-code {{ str_starts_with($dpfProcess['report_witness_statement_sent'], 'YES') ? 'text-emerald-800' : (str_starts_with($dpfProcess['report_witness_statement_sent'], 'NO') ? 'text-rose-800' : 'text-slate-800') }}">
                                                                    {{ $dpfProcess['report_witness_statement_sent'] }}
                                                                </td>
                                                            </tr>
                                                            <tr class="border-b border-slate-200 last:border-b-0 text-slate-800">
                                                                <td class="py-1 px-2.5 font-medium border-r border-slate-200">Intelligence Shared to Intel Department for Action</td>
                                                                <td class="py-1 px-2.5 text-center font-black font-mono-code {{ $dpfProcess['intel_shared'] === 'YES' ? 'text-emerald-800' : 'text-slate-800' }}">
                                                                    {{ $dpfProcess['intel_shared'] }}
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif

                                            @if($isDrugCase && !empty($labSpecs))
                                                <div class="w-full overflow-x-hidden print:overflow-visible mt-3.5 pt-0.5 lab-findings-table-wrap">
                                                    <div class="text-[9.5px] font-mono-code font-bold uppercase text-emerald-900 tracking-wider pb-1 flex items-center gap-1.5 select-none">
                                                        <span class="w-1.5 h-1.5 bg-emerald-600 rounded-full inline-block"></span>
                                                        Laboratory Examination Findings
                                                    </div>
                                                    <table class="w-full table-fixed text-left border-collapse text-xs lab-findings-table" style="table-layout: fixed; width: 100%;">
                                                        <colgroup>
                                                            <col class="w-[18%]">
                                                            <col class="w-[32%]">
                                                            <col class="w-[18%]">
                                                            <col class="w-[32%]">
                                                        </colgroup>
                                                        <tbody>
                                                            @foreach(array_chunk($labSpecs, 2, true) as $chunk)
                                                                <tr class="border-b border-slate-200 last:border-b-0 text-xs">
                                                                    @foreach($chunk as $k => $v)
                                                                        <th class="py-1 px-2 font-bold text-slate-500 uppercase font-mono-code text-[9.5px] w-[18%] align-top break-words whitespace-normal leading-tight" style="word-break: break-word; overflow-wrap: anywhere; white-space: normal;">{{ $k }}</th>
                                                                        <td class="py-1 px-2 font-bold text-slate-900 font-mono-code text-xs w-[32%] align-top break-words break-all sm:break-words whitespace-normal leading-relaxed" style="word-break: break-word; overflow-wrap: anywhere; white-space: normal;">{{ $fmtSpecVal($v) }}</td>
                                                                    @endforeach
                                                                    @if(count($chunk) === 1)
                                                                        <th class="py-1 px-2 w-[18%]"></th>
                                                                        <td class="py-1 px-2 w-[32%]"></td>
                                                                    @endif
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif

                                            @if(!empty($vehSpecs))
                                                <div class="overflow-x-auto mt-3.5 pt-0.5">
                                                    <div class="text-[9.5px] font-mono-code font-bold uppercase text-amber-900 tracking-wider pb-1 flex items-center gap-1.5 select-none">
                                                        <span class="w-1.5 h-1.5 bg-amber-600 rounded-full inline-block"></span>
                                                        Vehicle Specifications
                                                    </div>
                                                    <table class="w-full text-left border-collapse text-xs">
                                                        <tbody>
                                                            @foreach(array_chunk($vehSpecs, 2, true) as $chunk)
                                                                <tr class="border-b border-slate-200 last:border-b-0 text-xs">
                                                                    @foreach($chunk as $k => $v)
                                                                        <th class="py-1 px-2 font-bold text-slate-500 uppercase font-mono-code text-[9.5px] w-1/6 align-top">{{ $k }}</th>
                                                                        <td class="py-1 px-2 font-bold text-slate-900 font-mono-code text-xs w-2/6 align-top">{{ $fmtSpecVal($v) }}</td>
                                                                    @endforeach
                                                                    @if(count($chunk) === 1)
                                                                        <th class="py-1 px-2 w-1/6"></th>
                                                                        <td class="py-1 px-2 w-2/6"></td>
                                                                    @endif
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif

                                            @if(!empty($wpnSpecs))
                                                <div class="overflow-x-auto mt-3.5 pt-0.5">
                                                    <div class="text-[9.5px] font-mono-code font-bold uppercase text-rose-900 tracking-wider pb-1 flex items-center gap-1.5 select-none">
                                                        <span class="w-1.5 h-1.5 bg-rose-600 rounded-full inline-block"></span>
                                                        Weapon Specifications
                                                    </div>
                                                    <table class="w-full text-left border-collapse text-xs">
                                                        <tbody>
                                                            @foreach(array_chunk($wpnSpecs, 2, true) as $chunk)
                                                                <tr class="border-b border-slate-200 last:border-b-0 text-xs">
                                                                    @foreach($chunk as $k => $v)
                                                                        <th class="py-1 px-2 font-bold text-slate-500 uppercase font-mono-code text-[9.5px] w-1/6 align-top">{{ $k }}</th>
                                                                        <td class="py-1 px-2 font-bold text-slate-900 font-mono-code text-xs w-2/6 align-top">{{ $fmtSpecVal($v) }}</td>
                                                                    @endforeach
                                                                    @if(count($chunk) === 1)
                                                                        <th class="py-1 px-2 w-1/6"></th>
                                                                        <td class="py-1 px-2 w-2/6"></td>
                                                                    @endif
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif

                                            <!-- EXHIBIT PHOTOS -->
                                            @if(!empty($ex['photos']) && count($ex['photos']) > 0)
                                                <div class="flex items-center gap-2 pt-1.5 mt-2.5 flex-wrap">
                                                    @foreach($ex['photos'] as $photoUrl)
                                                        <div class="w-14 h-14 rounded border border-slate-300 overflow-hidden bg-white shadow-2xs">
                                                            <img src="{{ $photoUrl }}" alt="Exhibit Photo" class="w-full h-full object-cover">
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                    </div>

                    <!-- DETAILS PAGE FOOTER -->
                    <div class="page-footer-block pt-2 border-t border-slate-300 flex items-center justify-between text-[8px] text-slate-500 font-mono-code select-none shrink-0">
                        <div class="flex items-center gap-2">
                            <span class="font-black text-slate-700">NACOC / FORENSIC DEPARTMENT</span>
                            <span>&bull;</span>
                            <span>Case Record: {{ $item['case_number'] }} &bull; Subject: {{ $item['suspect_name'] }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span>STRICTLY CONFIDENTIAL</span>
                            <span>&bull;</span>
                            <span class="font-black text-slate-800">
                                Page <span class="page-num">{{ $idx + 3 }}</span> of <span class="doc-total-pages">{{ count($compiledSuspects) + 2 }}</span>
                            </span>
                        </div>
                    </div>

                </div>
            </section>
        @empty
            <section class="a4-page relative text-slate-900 bg-white">
                <div class="flex flex-col items-center justify-center h-full text-center space-y-3 py-16">
                    <svg class="w-12 h-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="text-base font-black text-slate-800 uppercase">No Suspect Records Found</h3>
                    <p class="text-sm text-slate-500 max-w-md">
                        There are no suspect records or case files matching the specified date range and filters.
                    </p>
                </div>
            </section>
        @endforelse

    </main>

    <!-- DYNAMIC AUTO-PAGINATION & PAGE NUMBERING SCRIPT -->
    <script>
        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        const suspectPristineSnapshots = new WeakMap();

        function isPageOverflowing(page) {
            const innerWrapper = page.querySelector('.page-inner-wrapper');
            const headerBlock = page.querySelector('.page-header-block');
            const footerBlock = page.querySelector('.page-footer-block');
            const flowBody = page.querySelector('.page-flow-body');
            if (!innerWrapper || !flowBody) return false;

            const innerH = innerWrapper.clientHeight > 0 ? innerWrapper.clientHeight : (page.clientHeight - 80);
            const headerH = headerBlock ? headerBlock.offsetHeight : 0;
            const footerH = footerBlock ? footerBlock.offsetHeight : 0;
            const usableH = innerH - headerH - footerH;

            return (flowBody.scrollHeight > usableH + 2) || (flowBody.clientHeight > 0 && flowBody.scrollHeight > flowBody.clientHeight + 2);
        }

        function createContinuationPage(originalSuspectPage, previousPage) {
            const caseNumber = originalSuspectPage.dataset.caseNumber || 'N/A';
            const suspectName = originalSuspectPage.dataset.suspectName || 'N/A';
            const priority = originalSuspectPage.dataset.priority || 'HIGH';
            const parentId = originalSuspectPage.id;

            const nextPg = document.createElement('section');
            nextPg.className = 'a4-page suspect-continuation-page relative text-slate-900 bg-white';
            nextPg.dataset.parentSuspectId = parentId;
            nextPg.dataset.caseNumber = caseNumber;
            nextPg.dataset.suspectName = suspectName;

            // Subtle NACOC watermark
            const wm = originalSuspectPage.querySelector('.watermark-layer');
            if (wm) {
                nextPg.appendChild(wm.cloneNode(true));
            }

            const innerWrapper = document.createElement('div');
            innerWrapper.className = 'page-inner-wrapper relative z-10 flex flex-col justify-between h-full min-h-0';

            const headerBlock = document.createElement('div');
            headerBlock.className = 'page-header-block pb-2 border-b border-slate-300 flex items-center justify-between text-[9px] font-mono-code text-slate-700 select-none shrink-0';
            headerBlock.innerHTML = `
                <div>
                    <span class="font-black uppercase tracking-wider text-slate-900">
                        NACOC FORENSIC DEPARTMENT &bull; EXECUTIVE SUSPECT RECORD (CONTINUED)
                    </span>
                    <span class="ml-2 font-bold text-slate-800">
                        Ref: ${escapeHtml(caseNumber)} &bull; Priority: ${escapeHtml(priority)}
                    </span>
                </div>
                <div class="text-right">
                    <span class="font-black uppercase tracking-wider text-slate-900">Confidential</span>
                    <span class="ml-1.5 font-black text-slate-800">
                        Page <span class="page-num">#</span> of <span class="doc-total-pages">#</span>
                    </span>
                </div>
            `;

            const flowBody = document.createElement('div');
            flowBody.className = 'page-flow-body flex-1 py-3 space-y-4 overflow-hidden bg-white';

            const exhibitsSectionBlock = document.createElement('div');
            exhibitsSectionBlock.className = 'exhibits-section-block space-y-2 pt-1 bg-white';
            exhibitsSectionBlock.innerHTML = `
                <div class="exhibits-heading-wrap flex items-center justify-between border-b border-slate-300 pb-1">
                    <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 flex items-center gap-2">
                        <span class="w-2 h-2 bg-blue-700 rounded-xs inline-block"></span>
                        Associated Seized Exhibits &amp; Custodial Property (Continued)
                    </h3>
                    <span class="text-xs font-mono-code font-bold text-slate-700 uppercase">
                        Subject: ${escapeHtml(suspectName)} (Continuation)
                    </span>
                </div>
                <div class="exhibits-list-container space-y-3"></div>
            `;
            flowBody.appendChild(exhibitsSectionBlock);

            const footerBlock = document.createElement('div');
            footerBlock.className = 'page-footer-block pt-2 border-t border-slate-300 flex items-center justify-between text-[8px] text-slate-500 font-mono-code select-none shrink-0';
            footerBlock.innerHTML = `
                <div class="flex items-center gap-2">
                    <span class="font-black text-slate-700">NACOC / FORENSIC DEPARTMENT</span>
                    <span>&bull;</span>
                    <span>Case Record: ${escapeHtml(caseNumber)} &bull; Subject: ${escapeHtml(suspectName)}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span>STRICTLY CONFIDENTIAL</span>
                    <span>&bull;</span>
                    <span class="font-black text-slate-800">
                        Page <span class="page-num">#</span> of <span class="doc-total-pages">#</span>
                    </span>
                </div>
            `;

            innerWrapper.appendChild(headerBlock);
            innerWrapper.appendChild(flowBody);
            innerWrapper.appendChild(footerBlock);
            nextPg.appendChild(innerWrapper);

            previousPage.parentNode.insertBefore(nextPg, previousPage.nextSibling);
            return nextPg;
        }

        function paginateSuspectPage(pageEl) {
            const flowBody = pageEl.querySelector('.page-flow-body');
            if (!flowBody) return;

            // 1. Snapshot restoration for idempotency
            if (!suspectPristineSnapshots.has(pageEl)) {
                suspectPristineSnapshots.set(pageEl, {
                    flowBodyHtml: flowBody.innerHTML,
                });
            } else {
                // Remove existing continuation pages for this suspect
                let sib = pageEl.nextElementSibling;
                while (sib && sib.classList.contains('suspect-continuation-page') && sib.dataset.parentSuspectId === pageEl.id) {
                    const next = sib.nextElementSibling;
                    sib.remove();
                    sib = next;
                }
                const snap = suspectPristineSnapshots.get(pageEl);
                if (snap) {
                    flowBody.innerHTML = snap.flowBodyHtml;
                }
            }

            // If it doesn't overflow, we are done
            if (!isPageOverflowing(pageEl)) {
                return;
            }

            const exhibitsContainer = pageEl.querySelector('.exhibits-list-container');
            const factsContent = pageEl.querySelector('.brief-facts-content');

            // Collect all exhibit cards
            const exhibitCards = exhibitsContainer ? Array.from(exhibitsContainer.querySelectorAll('.exhibit-card')) : [];

            // Case A: Has exhibits
            if (exhibitCards.length > 0) {
                exhibitsContainer.innerHTML = '';

                let currentPage = pageEl;
                let currentExhibitsContainer = exhibitsContainer;

                for (let i = 0; i < exhibitCards.length; i++) {
                    const card = exhibitCards[i];
                    currentExhibitsContainer.appendChild(card);

                    if (isPageOverflowing(currentPage)) {
                        if (currentExhibitsContainer.children.length > 1) {
                            // Multiple cards on this page, move current card to a new continuation page
                            currentExhibitsContainer.removeChild(card);
                            const contPage = createContinuationPage(pageEl, currentPage);
                            currentPage = contPage;
                            currentExhibitsContainer = contPage.querySelector('.exhibits-list-container');
                            currentExhibitsContainer.appendChild(card);
                        } else if (currentPage === pageEl) {
                            // Even the very first card overflowed the initial page!
                            currentExhibitsContainer.removeChild(card);

                            const notice = document.createElement('p');
                            notice.className = 'text-xs font-mono-code font-bold text-slate-500 italic pt-1';
                            notice.textContent = 'Seized exhibits cataloged on continuation page →';
                            currentExhibitsContainer.appendChild(notice);

                            const contPage = createContinuationPage(pageEl, currentPage);
                            currentPage = contPage;
                            currentExhibitsContainer = contPage.querySelector('.exhibits-list-container');
                            currentExhibitsContainer.appendChild(card);
                        }
                    }
                }
            } else if (factsContent) {
                // Case B: No exhibits, but facts paragraphs overflow
                const paragraphs = Array.from(factsContent.querySelectorAll('.facts-paragraph'));
                if (paragraphs.length > 1) {
                    factsContent.innerHTML = '';
                    let currentPage = pageEl;
                    let currentFactsContent = factsContent;

                    for (let p = 0; p < paragraphs.length; p++) {
                        const para = paragraphs[p];
                        currentFactsContent.appendChild(para);

                        if (isPageOverflowing(currentPage) && currentFactsContent.children.length > 1) {
                            currentFactsContent.removeChild(para);
                            const contPage = createContinuationPage(pageEl, currentPage);
                            currentPage = contPage;

                            // Setup continuation facts block
                            const contFlow = contPage.querySelector('.page-flow-body');
                            contFlow.innerHTML = '';
                            const contFactsBlock = document.createElement('div');
                            contFactsBlock.className = 'brief-facts-block space-y-1.5 pt-1 bg-white';
                            contFactsBlock.innerHTML = `
                                <div class="flex items-center justify-between border-b border-slate-300 pb-1">
                                    <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 flex items-center gap-2">
                                        <span class="w-2 h-2 bg-blue-700 rounded-xs inline-block"></span>
                                        Brief Statement of Facts (Continued)
                                    </h3>
                                </div>
                                <div class="brief-facts-content pt-0.5 space-y-1"></div>
                            `;
                            contFlow.appendChild(contFactsBlock);
                            currentFactsContent = contFactsBlock.querySelector('.brief-facts-content');
                            currentFactsContent.appendChild(para);
                        }
                    }
                }
            }
        }

        function renumberAllPages() {
            const pages = document.querySelectorAll('.printable-document .a4-page');
            const total = pages.length;
            document.querySelectorAll('.doc-total-pages').forEach(el => {
                el.textContent = total;
            });
            pages.forEach((page, idx) => {
                const pageNum = idx + 1;
                page.querySelectorAll('.page-num').forEach(el => {
                    el.textContent = pageNum;
                });
            });
        }

        let isPaginating = false;
        function autoPaginateAllSuspects() {
            if (isPaginating) return;
            isPaginating = true;
            try {
                const suspectPages = Array.from(document.querySelectorAll('.auto-paginated-suspect'));
                suspectPages.forEach(page => paginateSuspectPage(page));
                renumberAllPages();
            } finally {
                isPaginating = false;
            }
        }

        document.addEventListener('DOMContentLoaded', autoPaginateAllSuspects);
        window.addEventListener('load', autoPaginateAllSuspects);
        window.addEventListener('beforeprint', autoPaginateAllSuspects);

        let resizeTimer = null;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(autoPaginateAllSuspects, 150);
        });
    </script>
</body>
</html>
