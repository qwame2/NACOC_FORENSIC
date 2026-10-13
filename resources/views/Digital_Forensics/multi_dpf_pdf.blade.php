@php
    $authUser = auth()->user();
    $dbDpfHead = \App\Models\User::where(function($q) {
                    $q->where('unit', 'LIKE', '%Digital%')
                      ->orWhere('unit', 'LIKE', '%Physical%');
                })->where('is_head', true)->first()
                ?? \App\Models\User::where('unit', 'LIKE', '%Digital%')->first();
    $dpfHeadName = $dbDpfHead ? $dbDpfHead->fullname : 'Ofori Graham';

    $dbDeptHead = \App\Models\User::where('unit', 'LIKE', '%Directorate%')->where('is_head', true)->first()
               ?? \App\Models\User::where('unit', 'LIKE', '%Forensic Directorate%')->first()
               ?? \App\Models\User::where('unit', 'LIKE', '%Forensic Head%')->first()
               ?? \App\Models\User::where('unit', 'LIKE', '%Forensic%')->where('is_head', true)->first();
    $deptHeadName = $dbDeptHead ? $dbDeptHead->fullname : 'Anita Addo';

    // If $suspectReports was not passed (e.g., opened for a single case), build from single case/report
    if (!isset($suspectReports) || empty($suspectReports)) {
        $reportObj = $report ?? null;
        $caseNo = $reportObj->case_number ?? ($case->case_number ?? 'NCC-2026');
        $dpfCode = $reportObj->dpf_code ?? ('DPF-' . $caseNo);
        $sName = $case->suspect_name ?? ($case->suspects_list[0]['suspect_name'] ?? 'PRIMARY SUSPECT');

        $suspectReports = [
            [
                'case_number' => $caseNo,
                'dpf_code' => $dpfCode,
                'suspect_name' => strtoupper($sName),
                'role' => 'PRIMARY SUSPECT',
                'mugshot_front' => $case->mugshot_front ?? null,
                'meta' => implode(' &bull; ', array_filter([$case->gender ?? null, !empty($case->age) ? $case->age . ' yrs' : null, $case->nationality ?? 'Ghanaian'])) ?: 'Ghanaian',
                'nationality' => $case->nationality ?? 'Ghanaian',
                'gender' => $case->gender ?? null,
                'age' => $case->age ?? null,
                'ghana_card' => $case->ghana_card ?? null,
                'investigation_date' => $reportObj->investigation_date ? (string)$reportObj->investigation_date : date('Y-m-d'),
                'report_date' => $reportObj->created_at ? $reportObj->created_at->format('Y-m-d') : date('Y-m-d'),
                'location_post' => $reportObj->location_post ?? ($case->location ?? 'N/A'),
                'raid_location' => $case->raid_location ?? ($case->seizure_location ?? 'N/A'),
                'submitting_officer' => $case->submitting_officer ?? 'N/A',
                'exhibit_type_display' => $exhibitTypeDisplay ?: ($reportObj->devices_retrieved ?? 'Electronic Exhibits'),
                'device_specs' => $deviceSpecsList ?? [],
                'brief_description' => $reportObj->brief_case_description ?? ($case->description ?? 'N/A'),
                'court_requested' => strtoupper($reportObj->court_order_requested ?? 'NO'),
                'court_granted' => strtoupper($reportObj->court_order_granted ?? 'NO'),
                'extraction_status' => strtoupper($reportObj->extraction_analysis_conducted ?? 'NO'),
                'report_witness_status' => strtoupper($reportObj->report_witness_statement_sent ?? 'NO'),
                'intel_shared' => strtoupper($reportObj->intel_shared ?? 'NO'),
                'remark' => !empty($reportObj->remark) ? $reportObj->remark : 'N/A',
                'case_section_label' => (strtoupper($reportObj->court_order_granted ?? 'NO') === 'YES') ? 'ONGOING CASES (EXTRACTION COURT ORDERS RECEIVED)' : 'CURRENT/NEW CASES',
                'attached_files' => $attachedFiles ?? [],
            ]
        ];
    }

    // Always ensure CURRENT/NEW CASES come first before ONGOING CASES
    if (!empty($suspectReports) && is_array($suspectReports)) {
        $indexedReports = [];
        foreach ($suspectReports as $i => $sr) {
            $indexedReports[] = ['idx' => $i, 'item' => $sr];
        }
        usort($indexedReports, function($a, $b) {
            $aIsOngoing = (strtoupper($a['item']['court_granted'] ?? 'NO') === 'YES' || (isset($a['item']['case_section_label']) && str_contains(strtoupper($a['item']['case_section_label']), 'ONGOING')));
            $bIsOngoing = (strtoupper($b['item']['court_granted'] ?? 'NO') === 'YES' || (isset($b['item']['case_section_label']) && str_contains(strtoupper($b['item']['case_section_label']), 'ONGOING')));

            $aRank = $aIsOngoing ? 1 : 0;
            $bRank = $bIsOngoing ? 1 : 0;

            if ($aRank !== $bRank) {
                return $aRank <=> $bRank;
            }
            return $a['idx'] <=> $b['idx'];
        });
        $suspectReports = array_values(array_column($indexedReports, 'item'));
    }

    // Consolidated exhibit type display
    if (empty($exhibitTypeDisplay)) {
        $allExhibits = array_filter(array_map(fn($sr) => $sr['exhibit_type_display'], $suspectReports));
        $exhibitTypeDisplay = !empty($allExhibits) ? implode(', ', array_unique($allExhibits)) : 'Mobile Devices & Hardware Exhibits';
    }

    // Determine overall cover case section label
    $ongoingCasesCount = collect($suspectReports)->filter(fn($sr) => strtoupper($sr['court_granted'] ?? 'NO') === 'YES')->count();
    $totalSuspectsCount = count($suspectReports);

    if ($ongoingCasesCount === $totalSuspectsCount && $totalSuspectsCount > 0) {
        $coverCaseSectionLabel = 'ONGOING CASES (EXTRACTION COURT ORDERS RECEIVED)';
    } elseif ($ongoingCasesCount === 0 && $totalSuspectsCount > 0) {
        $coverCaseSectionLabel = 'CURRENT/NEW CASES';
    } elseif ($ongoingCasesCount > 0) {
        $coverCaseSectionLabel = 'CURRENT/NEW CASES & ONGOING CASES (EXTRACTION COURT ORDERS RECEIVED)';
    } else {
        $coverCaseSectionLabel = (strtoupper($reportObj->court_order_granted ?? 'NO') === 'YES')
            ? 'ONGOING CASES (EXTRACTION COURT ORDERS RECEIVED)'
            : 'CURRENT/NEW CASES';
    }

    // Dynamic Total Dossier Pages Calculation: Cover Page (1) + Each Suspect (1 + attachments count)
    $totalDossierPages = 1;
    foreach ($suspectReports as $sr) {
        $totalDossierPages += 1 + count($sr['attached_files'] ?? []);
    }

    // Determine report type / period (Weekly, Monthly, Annual, Date Range, General Report)
    $rawPeriod = strtolower(trim((string)(request('period') ?: (request('preset') ?: (request('report_type') ?: '')))));
    $fromDate = trim((string)request('from', ''));
    $toDate = trim((string)request('to', ''));
    $hasDateFilter = !empty($fromDate) || !empty($toDate);

    if (in_array($rawPeriod, ['weekly', 'week'])) {
        $reportTypeLabel = 'WEEKLY SITUATIONAL REPORT (SITREP)';
        $reportScopeDisplay = 'Weekly Directory Filter';
    } elseif (in_array($rawPeriod, ['monthly', 'month'])) {
        $reportTypeLabel = 'MONTHLY SITUATIONAL REPORT (SITREP)';
        $reportScopeDisplay = 'Monthly Directory Filter';
    } elseif (in_array($rawPeriod, ['annual', 'annually', 'yearly', 'year'])) {
        $reportTypeLabel = 'ANNUAL SITUATIONAL REPORT (SITREP)';
        $reportScopeDisplay = 'Annual Directory Filter';
    } elseif (in_array($rawPeriod, ['date_range', 'daterange', 'custom', 'range']) || ($hasDateFilter && $rawPeriod !== 'general' && $rawPeriod !== 'all')) {
        if (!empty($fromDate) && !empty($toDate)) {
            $fromTime = strtotime($fromDate);
            $toTime = strtotime($toDate);
            $diffDays = ($fromTime && $toTime) ? round(abs($toTime - $fromTime) / 86400) + 1 : 0;
            if ($diffDays == 7) {
                $reportTypeLabel = 'WEEKLY REPORT';
                $reportScopeDisplay = 'Weekly Range (' . $fromDate . ' to ' . $toDate . ')';
            } elseif ($fromTime && $toTime && date('Y-m-01', $fromTime) === $fromDate && date('Y-m-t', $fromTime) === $toDate) {
                $reportTypeLabel = 'MONTHLY REPORT';
                $reportScopeDisplay = 'Monthly Range (' . date('M Y', $fromTime) . ')';
            } elseif ($fromTime && $toTime && date('Y-01-01', $fromTime) === $fromDate && date('Y-12-31', $fromTime) === $toDate) {
                $reportTypeLabel = 'ANNUAL REPORT';
                $reportScopeDisplay = 'Annual Range (' . date('Y', $fromTime) . ')';
            } else {
                $reportTypeLabel = 'DATE RANGE REPORT';
                $reportScopeDisplay = 'Date Range (' . $fromDate . ' to ' . $toDate . ')';
            }
        } else {
            $reportTypeLabel = 'DATE RANGE REPORT';
            $reportScopeDisplay = 'Date Range Filter';
        }
    } else {
        // When user clicked Generate button without adding filters, or all-time
        $reportTypeLabel = 'GENERAL REPORT';
        $reportScopeDisplay = 'General Report (All Cases)';
    }

    $reportHeadingDisplay = $reportTypeLabel;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NACOC Digital Forensic Investigation Directory Dossier</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="{{ asset('vendor/pdfjs/pdf.min.js') }}"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
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
            background-color: #ffffffff;
            color: #ffffffff;
            margin: 0;
            padding: 24px 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .a4-page {
            width: 210mm;
            height: 297mm;
            max-height: 297mm;
            padding: 10mm 12mm 10mm 12mm;
            margin: 0 auto 24px auto;
            background: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
            box-sizing: border-box;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            page-break-after: always;
            break-after: page;
        }

        /* Cover Page: Exactly 1 static A4 sheet with equal 14mm side margins */
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
        }

        /* Dynamic Dossier Pages: Paginated A4 sheets with reduced margins (10mm top/bottom, 12mm sides) */
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
        }

        .a4-page-attachment, .a4-page-pdf-slice {
            width: 210mm;
            height: 297mm;
            max-height: 297mm;
            padding: 8mm 12mm;
            box-sizing: border-box;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
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

        .no-break {
            page-break-inside: avoid;
            break-inside: avoid;
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

            /* Static Cover Page: Locked to Page 1 with zero shift & equal 14mm side margins */
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

            /* Dynamic Dossier Pages: Paginated A4 sheets with reduced margins (10mm top/bottom, 12mm sides) */
            .dynamic-dossier-page {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 10mm 12mm 10mm 12mm !important;
                width: 210mm !important;
                height: 297mm !important;
                max-height: 297mm !important;
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

            /* Attachment Pages: Fit within single A4 page */
            .a4-page-attachment, .a4-page-pdf-slice {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 8mm 12mm !important;
                width: 210mm !important;
                height: 297mm !important;
                max-height: 297mm !important;
                min-height: 297mm !important;
                page-break-after: always !important;
                break-after: page !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                overflow: hidden !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                background: #ffffff !important;
            }

            .page-break {
                page-break-after: always !important;
                break-after: page !important;
            }

            .avoid-break {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .avoid-break-after {
                page-break-after: avoid !important;
                break-after: avoid !important;
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

            .footer {
                position: relative !important;
            }
        }
    </style>
</head>
<body class="antialiased text-slate-800">

    <!-- PRINT-SPECIFIC FIXED WATERMARK (REPEATS CENTERED ON EVERY DYNAMIC PRINTED PAGE) -->
    <div class="print-fixed-watermark select-none pointer-events-none" aria-hidden="true">
        <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[550px] h-[550px] object-contain filter grayscale" />
    </div>

    @php
        $currentPageNumber = 1;
    @endphp

    <!-- FLOATING TOP ACTION BAR (HIDDEN IN PRINT) -->
    <div class="action-bar-container max-w-[210mm] mx-auto mb-4 px-2 flex items-center justify-between select-none">
        <div class="flex items-center gap-3">
            <a href="{{ url('/Digital_Forensics/forensic-status') }}" class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-colors border border-slate-700 shadow-sm no-underline">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Back</span>
            </a>
           
        </div>

        <div class="flex items-center gap-3">
            <span class="px-2.5 py-1 bg-emerald-950/80 text-emerald-300 border border-emerald-800 rounded-md text-[11px] font-mono-code font-bold">
                {{ count($suspectReports) }} Suspect Dossier(s) Compiled
            </span>

            <button onclick="handlePrintDocument()" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold flex items-center gap-2 transition-all shadow-md hover:shadow-lg active:scale-95 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>Print / Save PDF</span>
            </button>
        </div>
    </div>

    <!-- REUSABLE A4 DPF REPORT SHEET -->
    <div id="dpfReportSheet" class="printable-document">

        <!-- ============================================================
             PAGE 1: SINGLE CONSOLIDATED DIRECTORY COVER PAGE (NO PHOTOS)
             ============================================================ -->
        <div class="a4-page cover-page page-break relative text-slate-900">

            <!-- SUBTLE NACOC WATERMARK -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.025] select-none">
                <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[550px] h-[550px] object-contain filter grayscale" />
            </div>

            <div class="relative z-10 flex flex-col justify-between h-full min-h-0">

                <!-- TOP BADGES -->
                <div class="pt-2 flex items-center justify-between text-[8px] font-mono-code select-none">
                    <div class="text-left">
                        <p class="font-bold uppercase tracking-wider text-slate-900">REPUBLIC OF GHANA</p>
                        <p class="mt-0.5 text-slate-700">ISO/IEC 17025</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold uppercase tracking-wider text-slate-900">Confidential</p>
                        <p class="mt-0.5 text-[9px] font-bold uppercase text-slate-800" id="page1HeaderBadge">
                            DOSSIER COVER &bull; Page 1 of <span class="doc-total-pages">{{ $totalDossierPages }}</span>
                        </p>
                    </div>
                </div>

                <!-- ALL CENTERED CONTENT IN THE MIDDLE OF THE PAGE -->
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
                            DIGITAL &amp; PHYSICAL FORENSICS UNIT (DPFU)
                        </p>
                    </div>

                    <!-- INCREASED SIZE EMBLEM LOGO IN THE MIDDLE -->
                    <div class="my-6 flex items-center justify-center">
                        <img src="{{ asset('NACOC_LOGO.PNG') }}" alt="NACOC Emblem Logo" class="w-160 h-160 max-w-[1800px] max-h-[1800px] object-contain drop-shadow-md" />
                    </div>

                    <!-- CASE FILE & REPORT TITLE -->
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.25em] text-slate-900">
                            OFFICIAL SUSPECT FORENSIC CASE FILES
                        </p>
                        <h2 class="mt-2 text-2xl font-bold uppercase tracking-wide text-slate-950 font-serif-header">
                            DIGITAL FORENSIC INVESTIGATION UNIT
                        </h2>
                        <p class="mt-1 text-sm font-semibold uppercase tracking-[0.14em] text-slate-700">
                            Suspect Extraction &amp; Status Log Register
                        </p>
                        <div class="mx-auto mt-4 h-[2px] w-20 bg-emerald-700"></div>
                        <p class="mt-5 text-base font-extrabold uppercase tracking-wider text-emerald-800">
                            {!! $reportHeadingDisplay !!}
                        </p>
                        <p class="mt-2 text-[13px] font-extrabold uppercase tracking-wider font-times text-emerald-800">
                            {{ $coverCaseSectionLabel ?? 'CURRENT/NEW CASES' }}
                        </p>
                    </div>

                    <!-- CONFIDENTIAL & PROPRIETARY LAW ENFORCEMENT RECORD -->
                    <div class="mt-10 w-full">
                        <div class="p-4 text-center font-mono-code text-[10px] text-slate-700 space-y-1.5 shadow-2xs">
                            <p class="font-bold uppercase tracking-wider text-slate-900">
                                CONFIDENTIAL &amp; PROPRIETARY LAW ENFORCEMENT RECORD
                            </p>
                            <p class="leading-relaxed">
                                This document contains confidential forensic and intelligence information. Unauthorized access, copying, or distribution is prohibited. Individual suspect reports follow.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- COVER PAGE FOOTER -->
                <div class="mt-6 pt-3 border-t border-slate-200 flex items-center justify-between text-[8px] text-slate-500 font-mono-code select-none footer">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-700 tracking-wider">NACOC / DPFU UNIT</span>
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
             INDIVIDUAL SUSPECT REPORT DOSSIERS (UNIFIED EXECUTIVE REPORT)
             ============================================================ -->
        @foreach($suspectReports as $sIdx => $sr)
            @php
                $sReportPage = ++$currentPageNumber;
            @endphp

            <div class="a4-page dynamic-dossier-page auto-paginated-root relative text-slate-900 {{ !empty($sr['attached_files']) || $sIdx < count($suspectReports) - 1 ? 'page-break' : '' }}">

                <!-- SUBTLE NACOC WATERMARK (FOR SCREEN PREVIEW) -->
                <div class="dossier-watermark absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.025] select-none">
                    <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[900px] h-[900px] object-contain filter grayscale" />
                </div>

                <div class="relative z-10 flex flex-col justify-between h-full min-h-0">

                    <!-- PAGE HEADER BLOCK -->
                    <div class="page-header-block shrink-0">
                        <!-- REPORT HEADER -->
                        <div class="relative pt-1 pb-1.5 border-b border-slate-300 flex items-center justify-between avoid-break-after">
                            <div>
                                <span class="text-[9px] font-bold uppercase tracking-wider text-slate-900 font-serif-header">
                                    NACOC DIGITAL FORENSIC INVESTIGATION REPORT
                                </span>
                                <span class="ml-2 font-mono-code text-[8.5px] font-semibold text-slate-800">
                                    Ref: {{ $sr['case_number'] }} &bull; {{ $sr['dpf_code'] }}
                                </span>
                            </div>
                            <div class="text-right">
                                <p class="text-[7.5px] font-bold uppercase tracking-wider text-slate-900">Confidential</p>
                                <p class="mt-0.5 text-[8.5px] font-bold uppercase text-slate-800 dpf-header-page-num">
                                    Suspect {{ $sIdx + 1 }} &bull; Page {{ $sReportPage }} of <span class="doc-total-pages">{{ $totalDossierPages }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- REPORT TITLE -->
                        <div class="mt-1.5 pb-1.5 border-b border-slate-200 text-center avoid-break-after">
                            <p class="text-[8px] font-bold uppercase tracking-[0.2em] text-emerald-800">
                                INDIVIDUAL SUSPECT FORENSIC CASE &bull; RECORD #{{ $sIdx + 1 }}
                            </p>
                            <h2 class="mt-0.5 text-sm font-bold uppercase tracking-wide text-slate-950 font-serif-header">
                                REPUBLIC VS {{ $sr['suspect_name'] }}{{ !empty($sr['co_suspects_display']) ? ' & ' . $sr['co_suspects_display'] : '' }}
                            </h2>
                            <p class="mt-0.5 text-[11px] font-bold uppercase tracking-wide text-slate-800 font-serif-header">
                                CASE REF ID: {{ $sr['case_number'] }}
                            </p>
                            <p class="mt-1 text-[13px] font-extrabold uppercase tracking-wider font-times text-emerald-800">
                                {{ $sr['case_section_label'] }}
                            </p>
                        </div>
                    </div>

                    <!-- FLOW CONTENT BODY -->
                    <div class="report-flow-body flex-1 min-h-0 flex flex-col justify-between overflow-hidden">
                        <div class="flow-sections-container">
                            <!-- SECTION 01: SUSPECT & CASE PARTICULARS (PHOTO IN TOP LEFT CORNER) -->
                            <div class="flow-block section-01 mt-3 avoid-break">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-50 text-[8.5px] font-bold text-emerald-700">
                                        01
                                    </span>
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-[12px] font-bold uppercase tracking-[0.12em] text-slate-900">
                                                Suspect Identification &amp; Case Particulars
                                            </h3>
                                        </div>
                                        <div class="mt-0.5 h-px bg-slate-200"></div>
                                    </div>
                                </div>

                                <!-- SUSPECT PROFILE CONTAINER (PHOTO ON TOP LEFT CORNER) -->
                                <div class="flex items-start gap-4 p-2.5">
                                    <!-- TOP LEFT CORNER: PHOTO (MUGSHOT) -->
                                    <div class="shrink-0 flex items-center gap-2">
                                        <div class="flex flex-col items-center">
                                            <div class="relative w-24 h-28 rounded-lg overflow-hidden border-2 border-slate-300 bg-slate-100 shadow-sm flex items-center justify-center">
                                                @if(!empty($sr['mugshot_front']))
                                                    <img src="{{ $sr['mugshot_front'] }}" alt="{{ $sr['suspect_name'] }} Photo" class="w-full h-full object-cover" />
                                                @else
                                                    <div class="flex flex-col items-center justify-center p-2 text-center text-slate-500">
                                                        <svg class="w-7 h-7 text-slate-300 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                        </svg>
                                                        <span class="text-[6.5px] font-bold uppercase tracking-tight text-slate-500">Front Mugshot</span>
                                                    </div>
                                                @endif
                                            </div>
                                            <span class="mt-1 px-1.5 py-0.5 rounded text-[6.5px] font-extrabold uppercase tracking-wider {{ str_contains($sr['role'], 'PRIMARY') ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-slate-200 text-slate-800' }}">
                                                {{ $sr['role'] }}
                                            </span>
                                        </div>
                                        @if(!empty($sr['co_suspects']) && is_array($sr['co_suspects']))
                                            @foreach($sr['co_suspects'] as $coS)
                                                @php
                                                    $coMug = $coS['mugshot_front'] ?? ($coS['mugshots']['front'] ?? null);
                                                    if (!empty($coMug) && !str_starts_with($coMug, 'data:image') && !str_starts_with($coMug, 'http://') && !str_starts_with($coMug, 'https://') && !str_starts_with($coMug, '/')) {
                                                        $coMug = asset($coMug);
                                                    }
                                                @endphp
                                                <div class="flex flex-col items-center">
                                                    <div class="relative w-20 h-28 rounded-lg overflow-hidden border-2 border-slate-300 bg-slate-100 shadow-sm flex items-center justify-center">
                                                        @if(!empty($coMug))
                                                            <img src="{{ $coMug }}" alt="{{ $coS['suspect_name'] ?? 'Co-Suspect' }} Photo" class="w-full h-full object-cover" />
                                                        @else
                                                            <div class="flex flex-col items-center justify-center p-2 text-center text-slate-500">
                                                                <svg class="w-6 h-6 text-slate-300 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                                </svg>
                                                                <span class="text-[6px] font-bold uppercase tracking-tight text-slate-500">Co-Suspect</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <span class="mt-1 px-1.5 py-0.5 rounded text-[6.5px] font-extrabold uppercase tracking-wider bg-slate-200 text-slate-800">
                                                        CO-SUSPECT
                                                    </span>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>

                                    <!-- DETAILS BESIDE PHOTO IN A HORIZONTAL LINE -->
                                    <div class="flex-1 grid grid-cols-3 gap-3 pt-0.5 items-start">
                                        <div>
                                            <p class="text-[7.5px] font-bold uppercase tracking-wider text-slate-600">Suspect Full Name</p>
                                            <p class="mt-0.5 text-[12px] font-extrabold text-slate-900">{{ $sr['suspect_name'] }}</p>
                                            <p class="text-[7.5px] text-slate-500 font-medium">{!! $sr['meta'] !!}</p>

                                            @if(!empty($sr['co_suspects']) && is_array($sr['co_suspects']))
                                                <div class="mt-2.5 pt-1.5 border-t border-slate-200">
                                                    <p class="text-[7.5px] font-bold uppercase tracking-wider text-slate-600">Co-Suspect(s):</p>
                                                    @foreach($sr['co_suspects'] as $coS)
                                                        @php
                                                            $coName = strtoupper($coS['suspect_name'] ?? ($coS->suspect_name ?? ($coS['name'] ?? 'CO-SUSPECT')));
                                                            $coGender = !empty($coS['gender']) ? ucfirst($coS['gender']) : (!empty($coS->gender) ? ucfirst($coS->gender) : null);
                                                            $coAge = !empty($coS['age']) ? $coS['age'] . ' yrs' : (!empty($coS->age) ? $coS->age . ' yrs' : null);
                                                            $coNat = $coS['nationality'] ?? ($coS->nationality ?? null);
                                                            $coMeta = implode(' &bull; ', array_filter([$coGender, $coAge, $coNat])) ?: 'N/A';
                                                        @endphp
                                                        <div class="{{ !$loop->first ? 'mt-2' : 'mt-1' }}">
                                                            <p class="text-[12px] font-extrabold text-slate-900">{{ $coName }}</p>
                                                            <p class="text-[7.5px] text-slate-500 font-medium">{!! $coMeta !!}</p>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @elseif(!empty($sr['co_suspects_display']))
                                                <div class="mt-2.5 pt-1.5 border-t border-slate-200">
                                                    <p class="text-[7.5px] font-bold uppercase tracking-wider text-slate-600">Co-Suspect(s):</p>
                                                    <p class="mt-1 text-[12px] font-extrabold text-slate-900">{{ $sr['co_suspects_display'] }}</p>
                                                </div>
                                            @endif
                                        </div>

                                        <div>
                                            <p class="text-[7.5px] font-bold uppercase tracking-wider text-slate-600">Exhibit Type</p>
                                            <p class="mt-0.5 text-[12px] font-bold text-slate-900">{{ $sr['exhibit_type_display'] }}</p>
                                        </div>

                                        <div>
                                            <p class="text-[7.5px] font-bold uppercase tracking-wider text-slate-600">Submitting Officer</p>
                                            <p class="mt-0.5 text-[12px] font-medium text-slate-800">{{ $sr['submitting_officer'] }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SECTION 02: BRIEF CASE DESCRIPTION & OPERATIONAL CONTEXT -->
                            <div class="flow-block section-02 mt-3">
                                <div class="flex items-center gap-2 mb-0.5 avoid-break-after">
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-50 text-[8.5px] font-bold text-emerald-700">
                                        02
                                    </span>
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-[12px] font-bold uppercase tracking-[0.12em] text-slate-900">
                                                Brief Case Description
                                            </h3>
                                            <span class="text-[7.5px] font-semibold uppercase tracking-wider text-slate-600">
                                                Intelligence Fact Sheet
                                            </span>
                                        </div>
                                        <div class="mt-0.5 h-px bg-slate-200"></div>
                                    </div>
                                </div>

                                <div class="text-[12px] leading-snug text-slate-700 whitespace-pre-line pt-0">
                                    {{ $sr['brief_description'] }}
                                </div>
                            </div>

                            <!-- SECTION 03: LEGAL & OPERATIONAL PROCESS STATUS TABLE -->
                            <div class="flow-block section-03 mt-9 avoid-break">
                                <div class="flex items-center gap-2 mb-0.5 avoid-break-after">
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-50 text-[8.5px] font-bold text-emerald-700">
                                        03
                                    </span>
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-[12px] font-bold uppercase tracking-[0.12em] text-slate-900">
                                                Legal &amp; Operational Process Status
                                            </h3>
                                            <span class="text-[7.5px] font-semibold uppercase tracking-wider text-slate-600">
                                                Warrant &amp; Docket Verification
                                            </span>
                                        </div>
                                        <div class="mt-0.5 h-px bg-slate-200"></div>
                                    </div>
                                </div>

                                <div class="overflow-hidden border border-slate-300 rounded-lg">
                                    <table class="w-full text-left border-collapse table-fixed">
                                        <thead>
                                            <tr class="border-b border-slate-300 bg-slate-100/70">
                                                <th class="py-1 px-2.5 text-center text-[9px] font-bold uppercase tracking-wider text-slate-900 w-1/2">
                                                    Process Phase
                                                </th>
                                                <th class="py-1 px-2.5 text-center text-[9px] font-bold uppercase tracking-wider text-slate-900 w-1/2">
                                                    Status
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-[12px] text-slate-800">
                                            <tr class="border-b border-slate-200">
                                                <td class="py-1 px-2.5 border-r border-slate-300 font-medium">Court Order Warrant Requested</td>
                                                <td class="py-1 px-2.5 text-center font-extrabold {{ $sr['court_requested'] === 'YES' ? 'text-emerald-800' : 'text-slate-900' }}">{{ $sr['court_requested'] }}</td>
                                            </tr>
                                            <tr class="border-b border-slate-200">
                                                <td class="py-1 px-2.5 border-r border-slate-300 font-medium">Court Order Warrant Granted</td>
                                                <td class="py-1 px-2.5 text-center font-extrabold {{ $sr['court_granted'] === 'YES' ? 'text-emerald-800' : 'text-slate-900' }}">{{ $sr['court_granted'] }}</td>
                                            </tr>
                                            <tr class="border-b border-slate-200">
                                                <td class="py-1 px-2.5 border-r border-slate-300 font-medium">Extraction &amp; Forensic Analysis Conducted</td>
                                                <td class="py-1 px-2.5 text-center font-extrabold {{ str_starts_with($sr['extraction_status'], 'YES') ? 'text-emerald-800' : (str_starts_with($sr['extraction_status'], 'NO') ? 'text-rose-800' : 'text-slate-900') }}">{{ $sr['extraction_status'] }}</td>
                                            </tr>
                                            <tr class="border-b border-slate-200">
                                                <td class="py-1 px-2.5 border-r border-slate-300 font-medium">Report, Witness Statement &amp; Digital Evidence Sent to Investigations</td>
                                                <td class="py-1 px-2.5 text-center font-extrabold {{ str_starts_with($sr['report_witness_status'], 'YES') ? 'text-emerald-800' : (str_starts_with($sr['report_witness_status'], 'NO') ? 'text-rose-800' : 'text-slate-900') }}">{{ $sr['report_witness_status'] }}</td>
                                            </tr>
                                            <tr>
                                                <td class="py-1 px-2.5 border-r border-slate-300 font-medium">Intelligence Shared to Intel Department for Action</td>
                                                <td class="py-1 px-2.5 text-center font-extrabold {{ $sr['intel_shared'] === 'YES' ? 'text-emerald-800' : 'text-slate-900' }}">{{ $sr['intel_shared'] }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- SECTION 04: REMARKS & OBSERVATIONS -->
                            <div class="flow-block section-04 section-remarks mt-9">
                                <div class="flex items-center gap-2 mb-0.5 avoid-break-after">
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-50 text-[8.5px] font-bold text-emerald-700">
                                        04
                                    </span>
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-[12px] font-bold uppercase tracking-[0.12em] text-slate-900">
                                                Remarks
                                            </h3>
                                            <span class="text-[7.5px] font-semibold uppercase tracking-wider text-slate-600">
                                                Technical Observations
                                            </span>
                                        </div>
                                        <div class="mt-0.5 h-px bg-slate-200"></div>
                                    </div>
                                </div>

                                <div class="flow-text text-[12px] leading-snug text-slate-700 whitespace-pre-line pb-30">
                                    {{ $sr['remark'] }}
                                </div>
                            </div>

                            <!-- CONDITIONS NOTE -->
                            <div class="flow-block section-conditions mt-3 pt-1.5 border-t border-slate-200 avoid-break">
                                <p class="text-[8.5px] font-semibold text-slate-800 mb-0.5 italic">
                                    Note: The result(s) relate only to the item(s) / digital exhibit(s) examined for suspect {{ $sr['suspect_name'] }} (Ref: {{ $sr['case_number'] }}).
                                </p>
                                <p class="text-[8.5px] font-extrabold uppercase tracking-wider text-slate-900 mb-0.5">
                                    Conditions:
                                </p>
                                <ol class="list-decimal list-inside space-y-0.5 text-[8.5px] font-medium text-slate-700 leading-tight pl-0.5">
                                    <li>Not valid without NACOC Digital &amp; Physical Forensics Unit approval.</li>
                                    <li>Not to be used for litigation and advertisement without written consent of the Director General of NACOC.</li>
                                    <li>This report shall not be reproduced in part without the written approval of the Director General of NACOC.</li>
                                </ol>
                            </div>
                        </div>

                        <!-- SIGNATURE & ENDORSEMENT FOOTER WRAPPER -->
                        <div class="flow-block section-signatures avoid-break mt-3 footer">
                            <div class="grid grid-cols-2 gap-8">
                                <div>
                                    <p class="text-[7px] font-bold uppercase tracking-wider text-slate-700">Head of Digital &amp; Physical Forensics</p>
                                    <div class="mt-2.5 border-b border-dashed border-slate-300 pb-0.5">
                                        <span class="text-[7px] italic text-slate-700">Electronically Signed &amp; Validated</span>
                                    </div>
                                    <p class="mt-0.5 text-[8.5px] font-bold text-slate-900">{{ $dpfHeadName }}</p>
                                    <p class="text-[7px] text-slate-800">Head of Digital &amp; Physical Forensics Unit, NACOC</p>
                                </div>

                                <div>
                                    <p class="text-[7px] font-bold uppercase tracking-wider text-slate-700">Head of Forensic Department</p>
                                    <div class="mt-2.5 border-b border-dashed border-slate-300 pb-0.5">
                                        <span class="text-[7px] italic text-slate-700">Authorized &amp; Counter-signed</span>
                                    </div>
                                    <p class="mt-0.5 text-[8.5px] font-bold text-slate-900">{{ $deptHeadName }}</p>
                                    <p class="text-[7px] text-slate-800">Head of Forensic Department, NACOC</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SUSPECT REPORT FOOTER -->
                    <div class="page-footer-block shrink-0 mt-auto">
                        <div class="mt-2 pb-2 border-t border-slate-200 flex items-center justify-between text-[7.5px] text-slate-500 font-mono-code select-none footer">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-700 tracking-wider">NACOC / DPFU &bull; {{ $sr['suspect_name'] }}</span>
                                <span>&bull;</span>
                                <span>Ref: {{ $sr['case_number'] }} &bull; {{ $sr['dpf_code'] }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span>STRICTLY CONFIDENTIAL</span>
                                <span>&bull;</span>
                                <span class="font-bold text-slate-800 dpf-footer-page-num">Page {{ $sReportPage }} of <span class="doc-total-pages">{{ $totalDossierPages }}</span></span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ATTACHMENT PAGES FOR THIS SUSPECT (IF ANY) -->
            @if(!empty($sr['attached_files']))
                @foreach($sr['attached_files'] as $attIdx => $att)
                    @php
                        $attPageNum = ++$currentPageNumber;
                        $rawFilePath = $att['path'];
                        $cleanPath = ltrim($rawFilePath, '/\\');
                        $fileAssetUrl = asset($cleanPath);
                        $filename = basename($rawFilePath);
                        $fExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        $isImage = in_array($fExt, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'jfif', 'bmp']);
                        $isPdf = ($fExt === 'pdf');
                        $cleanFilename = preg_replace('/^\d+_[a-f0-9]+_/', '', $filename);
                        $cleanFilename = str_replace('_', ' ', $cleanFilename);
                        $serverPath = public_path($cleanPath);
                        $fileSizeStr = file_exists($serverPath) ? number_format(filesize($serverPath) / 1024, 1) . ' KB' : null;
                    @endphp

                    <div id="{{ $att['id'] }}" class="a4-page a4-page-attachment relative text-slate-900 page-break">
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.025] select-none">
                            <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[900px] h-[900px] object-contain filter grayscale" />
                        </div>

                        <div class="relative z-10 flex flex-col justify-between h-full min-h-0">
                            <div>
                                <div class="relative pt-2 pb-3 border-b border-slate-300 flex items-center justify-between">
                                    <div>
                                        <span class="text-[9px] font-bold uppercase tracking-wider text-slate-900 font-serif-header">
                                            NACOC FORENSIC REPORT &bull; ATTACHED EXHIBIT
                                        </span>
                                        <span class="ml-2 font-mono-code text-[9px] font-semibold text-slate-800">
                                            Ref: {{ $sr['case_number'] }} &bull; {{ $sr['dpf_code'] }}
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[8px] font-bold uppercase tracking-wider text-slate-900">Confidential</p>
                                        <p class="mt-0.5 text-[9px] font-bold uppercase text-slate-800">
                                            Page {{ $attPageNum }} of {{ $totalDossierPages }}
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-3 pb-2 flex items-center justify-between">
                                    <h3 class="text-[11px] font-bold uppercase tracking-wider text-slate-900 font-serif-header">
                                        {{ $att['title'] }} &bull; Suspect: {{ $sr['suspect_name'] }}
                                    </h3>
                                    <div class="text-[8px] text-slate-500 font-mono-code">
                                        {{ $cleanFilename }} {{ $fileSizeStr ? '&bull; ' . $fileSizeStr : '' }}
                                    </div>
                                </div>

                                <div class="mt-2 border border-slate-300 rounded-lg overflow-hidden bg-slate-50 p-2 flex flex-col items-center justify-center min-h-[750px] max-h-[920px] print:min-h-0 print:max-h-[215mm]">
                                    @if($isImage)
                                        <img src="{{ $fileAssetUrl }}" alt="{{ $cleanFilename }}" class="max-h-[850px] print:max-h-[210mm] max-w-full object-contain rounded shadow-xs" />
                                    @elseif($isPdf)
                                        <div class="pdf-viewer-canvas-container flex flex-col items-center justify-center w-full"
                                             data-pdf-url="{{ $fileAssetUrl }}"
                                             data-filename="{{ $cleanFilename }}"
                                             data-filesize="{{ $fileSizeStr }}"
                                             data-title="{{ $att['title'] }}"
                                             data-case-no="{{ $sr['case_number'] }}"
                                             data-dpf-code="{{ $sr['dpf_code'] }}"
                                             data-watermark="{{ asset('nacoc.jfif') }}">
                                            <div class="flex flex-col items-center gap-2 p-4 text-center">
                                                <svg class="w-8 h-8 text-emerald-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <span class="text-xs font-mono-code text-slate-600 font-semibold">Rendering PDF Attachment...</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-8 print:mt-2 pt-3 border-t border-slate-200 flex items-center justify-between text-[8px] text-slate-500 font-mono-code select-none">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-700 tracking-wider">NACOC / DPFU &bull; {{ $sr['suspect_name'] }}</span>
                                    <span>&bull;</span>
                                    <span>Document Page {{ $attPageNum }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span>STRICTLY CONFIDENTIAL</span>
                                    <span>&bull;</span>
                                    <span class="font-bold text-slate-800">OFFICIAL EXHIBIT ATTACHMENT</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif

        @endforeach

    </div>

    <!-- SCRIPT FOR PDF.JS RENDERING & AUTO-PRINT -->
    <script>
        function splitTextIntoAtomicParagraphs(text) {
            if (!text) return [];
            const rawLines = text.split(/\r?\n+/).map(l => l.trim()).filter(Boolean);
            const finalChunks = [];

            rawLines.forEach(line => {
                if (line.length <= 250) {
                    finalChunks.push(line);
                    return;
                }
                const sentences = line.split(/(?<=[.!?])\s+|(?<=[.!?])(?=[A-Z])/).map(s => s.trim()).filter(Boolean);
                let accum = '';
                sentences.forEach(s => {
                    if (s.length > 300) {
                        const words = s.split(/\s+/).filter(Boolean);
                        let wAccum = '';
                        words.forEach(w => {
                            if ((wAccum + ' ' + w).trim().length > 220 && wAccum.length > 0) {
                                finalChunks.push(wAccum.trim());
                                wAccum = w;
                            } else {
                                wAccum = (wAccum + ' ' + w).trim();
                            }
                        });
                        if (wAccum.trim().length > 0) {
                            s = wAccum.trim();
                        }
                    }
                    if ((accum + ' ' + s).trim().length > 250 && accum.length > 0) {
                        finalChunks.push(accum.trim());
                        accum = s;
                    } else {
                        accum = (accum + ' ' + s).trim();
                    }
                });
                if (accum.trim().length > 0) {
                    finalChunks.push(accum.trim());
                }
            });

            return finalChunks.length > 0 ? finalChunks : [text.trim()];
        }

        function autoPaginateAllReports() {
            const reportRoots = document.querySelectorAll('#dpfReportSheet .auto-paginated-root');
            reportRoots.forEach(page => paginateReportPage(page));
        }

        function paginateReportPage(pageEl) {
            const headerBlock = pageEl.querySelector('.page-header-block');
            const flowBody = pageEl.querySelector('.report-flow-body');
            const footerBlock = pageEl.querySelector('.page-footer-block');
            if (!flowBody || !headerBlock || !footerBlock) return;

            const maxH = pageEl.clientHeight - headerBlock.offsetHeight - footerBlock.offsetHeight - 15;
            if (flowBody.scrollHeight <= maxH + 4) return;

            const sectionsContainer = flowBody.querySelector('.flow-sections-container');
            const signaturesBlock = flowBody.querySelector('.section-signatures');
            const rawBlocks = sectionsContainer ? Array.from(sectionsContainer.children) : [];
            if (signaturesBlock) rawBlocks.push(signaturesBlock);

            const atomicItems = [];

            rawBlocks.forEach(block => {
                const hasTable = !!block.querySelector('table');
                const isRemarks = (block.classList.contains('section-04') || block.classList.contains('section-remarks')) && !hasTable;

                if (isRemarks && block.querySelector('.flow-text')) {
                    const heading = block.querySelector('.flex.items-center');
                    const textElem = block.querySelector('.flow-text');
                    const fullText = (textElem ? textElem.innerText : '').trim();
                    const atomicParagraphs = splitTextIntoAtomicParagraphs(fullText);

                    if (heading) {
                        const hWrap = document.createElement('div');
                        hWrap.className = 'flow-block section-remarks-heading mt-3 avoid-break-after';
                        hWrap.appendChild(heading.cloneNode(true));
                        atomicItems.push(hWrap);
                    }

                    if (atomicParagraphs.length > 0) {
                        atomicParagraphs.forEach(p => {
                            const pDiv = document.createElement('div');
                            pDiv.className = 'flow-block flow-p text-[12px] leading-snug text-slate-700 mt-2';
                            pDiv.innerText = p.trim();
                            atomicItems.push(pDiv);
                        });
                    }
                } else {
                    atomicItems.push(block);
                }
            });

            flowBody.innerHTML = '';
            const newSectionsContainer = document.createElement('div');
            newSectionsContainer.className = 'flow-sections-container';
            flowBody.appendChild(newSectionsContainer);

            let currentPage = pageEl;
            let currentSectionsContainer = newSectionsContainer;
            let currentFlowBody = flowBody;

            function appendNewPage() {
                const nextPg = pageEl.cloneNode(false);
                nextPg.className = 'a4-page dynamic-dossier-page generated-flow-page relative text-slate-900 page-break';

                const wm = pageEl.querySelector('.absolute.inset-0, .dossier-watermark');
                if (wm) nextPg.appendChild(wm.cloneNode(true));

                const inner = document.createElement('div');
                inner.className = 'relative z-10 flex flex-col justify-between h-full min-h-0';

                const hdr = headerBlock.cloneNode(true);
                const bdy = document.createElement('div');
                bdy.className = 'report-flow-body flex-1 min-h-0 flex flex-col justify-between overflow-hidden';
                const secCont = document.createElement('div');
                secCont.className = 'flow-sections-container';
                bdy.appendChild(secCont);

                const ftr = footerBlock.cloneNode(true);

                inner.appendChild(hdr);
                inner.appendChild(bdy);
                inner.appendChild(ftr);
                nextPg.appendChild(inner);

                currentPage.parentNode.insertBefore(nextPg, currentPage.nextSibling);
                currentPage = nextPg;
                currentSectionsContainer = secCont;
                currentFlowBody = bdy;
            }

            atomicItems.forEach(item => {
                if (item.classList.contains('section-signatures')) {
                    currentFlowBody.appendChild(item);
                } else {
                    currentSectionsContainer.appendChild(item);
                }

                if (currentFlowBody.scrollHeight > maxH + 4) {
                    const totalChildren = currentSectionsContainer.children.length + (currentFlowBody.querySelector('.section-signatures') ? 1 : 0);
                    if (totalChildren > 1) {
                        if (item.classList.contains('section-signatures')) {
                            currentFlowBody.removeChild(item);
                        } else {
                            currentSectionsContainer.removeChild(item);
                        }

                        // Prevent orphan heading or orphan conditions block
                        const lastChild = currentSectionsContainer.lastElementChild;
                        let movedHeaderOrConditions = null;
                        if (lastChild && (
                            lastChild.classList.contains('section-remarks-heading') ||
                            lastChild.classList.contains('section-04-heading') ||
                            lastChild.classList.contains('avoid-break-after') ||
                            (item.classList.contains('section-signatures') && lastChild.classList.contains('section-conditions'))
                        )) {
                            movedHeaderOrConditions = lastChild;
                            currentSectionsContainer.removeChild(lastChild);
                        }

                        appendNewPage();

                        if (movedHeaderOrConditions) {
                            currentSectionsContainer.appendChild(movedHeaderOrConditions);
                        }

                        if (item.classList.contains('section-signatures')) {
                            currentFlowBody.appendChild(item);
                        } else {
                            currentSectionsContainer.appendChild(item);
                        }
                    }
                }
            });
        }

        function renumberAllPages() {
            const pages = document.querySelectorAll('#dpfReportSheet .a4-page');
            const total = pages.length;

            const p1Badge = document.getElementById('page1HeaderBadge');
            if (p1Badge) p1Badge.innerHTML = `DOSSIER COVER &bull; Page 1 of <span class="doc-total-pages">${total}</span>`;

            document.querySelectorAll('.doc-total-pages').forEach(el => el.textContent = total);

            pages.forEach((p, idx) => {
                const pageNum = idx + 1;
                const hBadge = p.querySelector('.dpf-header-page-num');
                if (hBadge) {
                    const fullBadge = hBadge.textContent || '';
                    if (fullBadge.includes('Suspect') && fullBadge.includes('•')) {
                        const sLabel = fullBadge.split('•')[0].trim();
                        hBadge.innerHTML = `${sLabel} &bull; Page ${pageNum} of <span class="doc-total-pages">${total}</span>`;
                    } else {
                        hBadge.innerHTML = `Page ${pageNum} of <span class="doc-total-pages">${total}</span>`;
                    }
                }

                const fBadge = p.querySelector('.dpf-footer-page-num');
                if (fBadge) fBadge.innerHTML = `Page ${pageNum} of <span class="doc-total-pages">${total}</span>`;
            });
        }

        window.handlePrintDocument = function() {
            if (window.isPdfRendering) {
                alert('Document pages are currently being prepared for print. Please try again in a few seconds.');
                return;
            }
            window.print();
        };

        async function initMultiDpfReport() {
            autoPaginateAllReports();
            await renderPdfAttachments();
            renumberAllPages();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initMultiDpfReport);
        } else {
            initMultiDpfReport();
        }

        async function renderPdfAttachments() {
            const pdfContainers = document.querySelectorAll('.pdf-viewer-canvas-container');
            if (!pdfContainers.length) return;

            window.isPdfRendering = true;

            for (const wrapper of pdfContainers) {
                const pdfUrl = wrapper.getAttribute('data-pdf-url');
                const basePage = wrapper.closest('.a4-page-attachment');
                const filename = wrapper.getAttribute('data-filename') || 'PDF Attachment';
                const filesize = wrapper.getAttribute('data-filesize') || '';
                const attTitle = wrapper.getAttribute('data-title') || 'Judicial Process Exhibit';
                const caseNo = wrapper.getAttribute('data-case-no') || '';
                const dpfCode = wrapper.getAttribute('data-dpf-code') || '';
                const watermarkUrl = wrapper.getAttribute('data-watermark') || '';

                if (!pdfUrl || typeof pdfjsLib === 'undefined') continue;

                try {
                    const loadingTask = pdfjsLib.getDocument({
                        url: pdfUrl,
                        cMapUrl: 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/cmaps/',
                        cMapPacked: true
                    });
                    const pdfDoc = await loadingTask.promise;
                    const numPages = pdfDoc.numPages;

                    const docContainer = document.getElementById('dpfReportSheet');
                    let insertReferenceNode = basePage;

                    for (let pNum = 1; pNum <= numPages; pNum++) {
                        const page = await pdfDoc.getPage(pNum);

                        const targetA4WidthPx = 794;
                        const targetA4HeightPx = 1123;
                        const headerFooterOffsetPx = 165;
                        const availableHeightPx = targetA4HeightPx - headerFooterOffsetPx;

                        const unscaledViewport = page.getViewport({ scale: 1.0 });
                        const scaleW = (targetA4WidthPx - 70) / unscaledViewport.width;
                        const scaleH = availableHeightPx / unscaledViewport.height;
                        const renderScale = Math.min(scaleW, scaleH, 1.85);

                        const viewport = page.getViewport({ scale: renderScale });
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');
                        canvas.width = viewport.width;
                        canvas.height = viewport.height;
                        canvas.className = 'w-full h-auto object-contain shadow-xs border border-slate-300 rounded';

                        await page.render({
                            canvasContext: ctx,
                            viewport: viewport
                        }).promise;

                        const pageElement = document.createElement('div');
                        pageElement.className = 'a4-page a4-page-pdf-slice relative text-slate-900 page-break';
                        pageElement.innerHTML = `
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.025] select-none">
                                <img src="${watermarkUrl}" alt="NACOC Watermark" class="w-[900px] h-[900px] object-contain filter grayscale" />
                            </div>
                            <div class="relative z-10 flex flex-col justify-between h-full min-h-0">
                                <div>
                                    <div class="relative pt-2 pb-3 border-b border-slate-300 flex items-center justify-between">
                                        <div>
                                            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-900 font-serif-header">
                                                NACOC FORENSIC REPORT &bull; ATTACHED EXHIBIT
                                            </span>
                                            <span class="ml-2 font-mono-code text-[9px] font-semibold text-slate-800">
                                                Ref: ${caseNo} &bull; ${dpfCode}
                                            </span>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-[8px] font-bold uppercase tracking-wider text-slate-900">Confidential</p>
                                            <p class="mt-0.5 text-[9px] font-bold uppercase text-slate-800">
                                                Attachment Doc Page ${pNum} of ${numPages}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="mt-2 pb-1 flex items-center justify-between">
                                        <h3 class="text-[10px] font-bold uppercase tracking-wider text-slate-900 font-serif-header">
                                            ${attTitle}
                                        </h3>
                                        <div class="text-[8px] text-slate-500 font-mono-code">
                                            ${filename} ${filesize ? `&bull; ${filesize}` : ''}
                                        </div>
                                    </div>
                                    <div class="mt-2 border border-slate-300 rounded-lg overflow-hidden bg-slate-50 p-2 flex flex-col items-center justify-center min-h-[750px] max-h-[920px] print:min-h-0 print:max-h-[215mm]">
                                        <div class="pdf-canvas-container flex items-center justify-center w-full"></div>
                                    </div>
                                </div>
                                <div class="mt-8 print:mt-2 pt-3 border-t border-slate-200 flex items-center justify-between text-[8px] text-slate-500 font-mono-code select-none">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-700 tracking-wider">NACOC / DPFU SUSPECT</span>
                                        <span>&bull;</span>
                                        <span>Document Page ${pNum} of ${numPages}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span>STRICTLY CONFIDENTIAL</span>
                                        <span>&bull;</span>
                                        <span class="font-bold text-slate-800">OFFICIAL EXHIBIT ATTACHMENT</span>
                                    </div>
                                </div>
                            </div>
                        `;

                        pageElement.querySelector('.pdf-canvas-container').appendChild(canvas);
                        docContainer.insertBefore(pageElement, insertReferenceNode.nextSibling);
                        insertReferenceNode = pageElement;
                    }

                    basePage.remove();
                } catch (err) {
                    console.error('Failed to load PDF attachment: ', err);
                }
            }
            window.isPdfRendering = false;
            renumberAllPages();
        }
    </script>

    @if(request()->has('print') || request()->has('auto_print'))
    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 800);
        });
    </script>
    @endif
</body>
</html>
