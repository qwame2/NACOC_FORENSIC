@php
    $authUser = auth()->user();
    $dbDpfHead = null;
    if ($authUser && $authUser->is_head && (str_contains(strtolower($authUser->unit ?? ''), 'digital') || str_contains(strtolower($authUser->unit ?? ''), 'physical'))) {
        $dbDpfHead = $authUser;
    }
    if (!$dbDpfHead) {
        $dbDpfHead = \App\Models\User::where(function($q) {
                        $q->where('unit', 'LIKE', '%Digital%')
                          ->orWhere('unit', 'LIKE', '%Physical%');
                    })->where('is_head', true)->first()
                    ?? \App\Models\User::where('unit', 'LIKE', '%Digital%')->first();
    }
    $dpfHeadName = $dbDpfHead ? $dbDpfHead->fullname : 'N/A';
    $dpfHeadSignature = $dbDpfHead ? $dbDpfHead->signature : null;
    if (!empty($dpfHeadSignature)) {
        $dpfHeadSignature = trim((string)$dpfHeadSignature);
        if (!str_starts_with($dpfHeadSignature, 'data:image') && !str_starts_with($dpfHeadSignature, 'http://') && !str_starts_with($dpfHeadSignature, 'https://') && !str_starts_with($dpfHeadSignature, '/')) {
            $dpfHeadSignature = asset($dpfHeadSignature);
        }
    }

    $dbDeptHead = null;
    if ($authUser && $authUser->is_head && (str_contains(strtolower($authUser->unit ?? ''), 'directorate') || str_contains(strtolower($authUser->unit ?? ''), 'forensic head'))) {
        $dbDeptHead = $authUser;
    }
    if (!$dbDeptHead) {
        $dbDeptHead = \App\Models\User::where('unit', 'LIKE', '%Directorate%')->where('is_head', true)->first()
                   ?? \App\Models\User::where('unit', 'LIKE', '%Forensic Directorate%')->first()
                   ?? \App\Models\User::where('unit', 'LIKE', '%Forensic Head%')->first()
                   ?? \App\Models\User::where('unit', 'LIKE', '%Forensic%')->where('is_head', true)->first();
    }
    $deptHeadName = $dbDeptHead ? $dbDeptHead->fullname : 'N/A';
    $deptHeadSignature = $dbDeptHead ? $dbDeptHead->signature : null;
    if (!empty($deptHeadSignature)) {
        $deptHeadSignature = trim((string)$deptHeadSignature);
        if (!str_starts_with($deptHeadSignature, 'data:image') && !str_starts_with($deptHeadSignature, 'http://') && !str_starts_with($deptHeadSignature, 'https://') && !str_starts_with($deptHeadSignature, '/')) {
            $deptHeadSignature = asset($deptHeadSignature);
        }
    }

    // If $suspectReports was not passed (e.g., opened for a single case), build from single case/report
    if (!isset($suspectReports) || empty($suspectReports)) {
        $reportObj = $report ?? null;
        $caseNo = $reportObj->case_number ?? ($case->case_number ?? 'N/A');
        $dpfCode = $reportObj->dpf_code ?? ($caseNo !== 'N/A' ? 'DPF-' . $caseNo : 'N/A');
        $sName = $case->suspect_name ?? ($case->suspects_list[0]['suspect_name'] ?? 'PRIMARY SUSPECT');

        $suspectReports = [
            [
                'case_number' => $caseNo,
                'case_title' => $case->title ?? ($case->case_title ?? null),
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
                'raid_location' => $case->raid_location ?? 'N/A',
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

    // Dynamic Total Dossier Pages Calculation: Cover Page (1) + Each Suspect (1 + attachments count) + Executive Summary Page (1)
    $totalDossierPages = 1;
    foreach ($suspectReports as $sr) {
        $totalDossierPages += 1 + count($sr['attached_files'] ?? []);
    }
    $totalDossierPages += 1;

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
    <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
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

        .report-flow-body {
            margin-bottom: 3mm;
        }

        .page-footer-block {
            position: relative;
            z-index: 20;
            background-color: #ffffff;
            padding-top: 2mm;
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

            /* WHY: Hide the single fixed watermark in print; each page now carries its
               own per-page .dossier-watermark element, which scales to many pages without
               the fixed-element repetition cost that caused print rasterization lag. */
            .print-fixed-watermark {
                display: none !important;
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

            .report-flow-body {
                margin-bottom: 3mm !important;
            }

            .page-footer-block {
                position: relative !important;
                z-index: 20 !important;
                background-color: #ffffff !important;
                padding-top: 2mm !important;
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
                opacity: 0.07 !important;
                overflow: hidden !important;
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
        <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[550px] h-[550px] object-contain" />
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
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.07] select-none">
                <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[550px] h-[550px] object-contain" />
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
                            FORENSIC DEPARTMENT
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
                <div class="dossier-watermark absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.07] select-none">
                    <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[900px] h-[900px] object-contain" />
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
                    <div class="report-flow-body flex-1 min-h-0 flex flex-col justify-between">
                        <div class="flow-sections-container">
                            <!-- SECTION 01: SUSPECT & CASE PARTICULARS (PHOTO IN TOP LEFT CORNER) -->
                            <div class="flow-block section-01 mt-9 avoid-break">
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
                            <div class="flow-block section-02 mt-9">
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

                                <div class="text-[12px] leading-snug text-slate-900 text-justify whitespace-pre-line pt-0">
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

                                <div class="flow-text text-[12px] leading-[2] text-slate-900 text-justify whitespace-pre-line pb-0">
                                    {{ $sr['remark'] }}
                                </div>
                            </div>

                            <!-- CONDITIONS NOTE -->
                            <div class="flow-block section-conditions mt-9 pt-1.5 border-t border-slate-200 avoid-break">
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
                        <div class="flow-block section-signatures avoid-break mt-6 pb-2 footer">
                            <div class="grid grid-cols-2 gap-8">
                                <div class="flex flex-col">
                                    <p class="text-[7.5px] font-bold uppercase tracking-wider text-slate-700">Head of Digital &amp; Physical Forensics</p>
                                    
                                    <!-- SIGNATURE AREA (MATCHING HEIGHT, FIXED TO PREVENT HEADER MOVING UP) -->
                                    <div class="relative h-20 flex items-end pb-0.5">
                                        @if(!empty($dpfHeadSignature))
                                            <img src="{{ $dpfHeadSignature }}" alt="Official Digital Signature - {{ $dpfHeadName }}" class="absolute bottom-0 left-0 h-24 max-h-[96px] max-w-[280px] w-auto object-contain object-left filter contrast-125 mix-blend-multiply pointer-events-none" />
                                        @endif
                                    </div>

                                    <div class="border-b border-dashed border-slate-300 pb-0.5">
                                        <span class="text-[7px] italic text-slate-700">Electronically Signed &amp; Validated</span>
                                    </div>
                                    <div class="mt-0.5">
                                        <p class="text-[8.5px] font-bold text-slate-900 leading-tight">{{ $dpfHeadName }}</p>
                                        <p class="text-[7px] text-slate-800 leading-tight">Head of Digital &amp; Physical Forensics Unit, NACOC</p>
                                    </div>
                                </div>

                                <div class="flex flex-col">
                                    <p class="text-[7.5px] font-bold uppercase tracking-wider text-slate-700">Head of Forensic Department</p>
                                    
                                    <!-- SIGNATURE AREA (MATCHING HEIGHT, FIXED TO PREVENT HEADER MOVING UP) -->
                                    <div class="relative h-20 flex items-end pb-0.5">
                                        @if(!empty($deptHeadSignature))
                                            <img src="{{ $deptHeadSignature }}" alt="Official Digital Signature - {{ $deptHeadName }}" class="absolute bottom-0 left-0 h-24 max-h-[96px] max-w-[280px] w-auto object-contain object-left filter contrast-125 mix-blend-multiply pointer-events-none" />
                                        @endif
                                    </div>

                                    <div class="border-b border-dashed border-slate-300 pb-0.5">
                                        <span class="text-[7px] italic text-slate-700">Authorized &amp; Counter-signed</span>
                                    </div>
                                    <div class="mt-0.5">
                                        <p class="text-[8.5px] font-bold text-slate-900 leading-tight">{{ $deptHeadName }}</p>
                                        <p class="text-[7px] text-slate-800 leading-tight">Head of Forensic Department, NACOC</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SUSPECT REPORT FOOTER -->
                    <div class="page-footer-block shrink-0 mt-auto">
                        <div class="mt-auto pb-2 border-t border-slate-200 flex items-center justify-between text-[7.5px] text-slate-500 font-mono-code select-none footer">
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

                        // Build comprehensive list of suspect(s) for the case/attachment header
                        $sNames = [];
                        if (!empty($sr['suspect_name'])) {
                            $sNames[] = strtoupper(trim($sr['suspect_name']));
                        }
                        if (!empty($sr['co_suspects']) && is_array($sr['co_suspects'])) {
                            foreach ($sr['co_suspects'] as $coS) {
                                $cName = strtoupper(trim($coS['suspect_name'] ?? ($coS->suspect_name ?? ($coS['name'] ?? ''))));
                                if (!empty($cName) && !in_array($cName, $sNames)) {
                                    $sNames[] = $cName;
                                }
                            }
                        } elseif (!empty($sr['co_suspects_display'])) {
                            foreach (explode(',', $sr['co_suspects_display']) as $cn) {
                                $cn = strtoupper(trim($cn));
                                if (!empty($cn) && !in_array($cn, $sNames)) {
                                    $sNames[] = $cn;
                                }
                            }
                        } elseif (isset($case) && !empty($case->suspects_list) && is_array($case->suspects_list)) {
                            foreach ($case->suspects_list as $cs) {
                                $cn = strtoupper(trim($cs['suspect_name'] ?? ''));
                                if (!empty($cn) && !in_array($cn, $sNames)) {
                                    $sNames[] = $cn;
                                }
                            }
                        }
                        $suspectHeaderLabel = count($sNames) > 1 ? 'Suspect(s)' : 'Suspect';
                        $suspectNamesHeader = !empty($sNames) ? implode(' & ', $sNames) : strtoupper($sr['suspect_name'] ?? 'N/A');
                    @endphp

                    <div id="{{ $att['id'] }}" class="a4-page a4-page-attachment relative text-slate-900 page-break">
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.07] select-none">
                            <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[900px] h-[900px] object-contain" />
                        </div>

                        <div class="relative z-10 flex flex-col justify-between h-full min-h-0">
                            <div>
                                <div class="relative pt-2 pb-3 border-b border-slate-300 flex items-center justify-between">
                                    <div class="flex items-center flex-wrap gap-x-2 gap-y-0.5 max-w-[80%]">
                                        <span class="text-[9px] font-bold uppercase tracking-wider text-slate-900 font-serif-header">
                                            NACOC FORENSIC REPORT &bull; ATTACHED EXHIBIT
                                        </span>
                                        <span class="font-mono-code text-[9px] font-semibold text-slate-800">
                                            Ref: {{ $sr['case_number'] }} &bull; {{ $sr['dpf_code'] }}
                                        </span>
                                        <span class="text-[9px] font-bold uppercase tracking-wider text-slate-900 font-serif-header">
                                            &bull; {{ $suspectHeaderLabel }}: <span class="font-mono-code text-slate-800 font-bold">{{ $suspectNamesHeader }}</span>
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[8px] font-bold uppercase tracking-wider text-slate-900">Confidential</p>
                                        <p class="mt-0.5 text-[9px] font-bold uppercase text-slate-800">
                                            Page {{ $attPageNum }} of {{ $totalDossierPages }}
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-9 pb-2 flex items-center justify-between">
                                    <h3 class="text-[11px] font-bold uppercase tracking-wider text-slate-900 font-serif-header">
                                        {{ $att['title'] }} &bull; {{ $suspectHeaderLabel }}: {{ $suspectNamesHeader }}
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
                                             data-suspect-label="{{ $suspectHeaderLabel }}"
                                             data-suspect-name="{{ $suspectNamesHeader }}"
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
                                    <span class="font-bold text-slate-700 tracking-wider">NACOC / DPFU &bull; {{ $suspectNamesHeader }}</span>
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

        <!-- ============================================================
             CONSOLIDATED EXECUTIVE SUMMARY OF ALL GENERATED CASES
             Placed under the last case (summarizing all cases in the dossier)
             ============================================================ -->
        @php
            $summaryPageNum = ++$currentPageNumber;
        @endphp

        <div class="a4-page dynamic-dossier-page relative text-slate-900 section-summary-page">

            <!-- SUBTLE NACOC WATERMARK -->
            <div class="dossier-watermark absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.07] select-none">
                <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[900px] h-[900px] object-contain" />
            </div>

            <div class="relative z-10 flex flex-col justify-between h-full min-h-0">

                <!-- PAGE HEADER BLOCK -->
                <div class="page-header-block shrink-0">
                    <div class="relative pt-1 pb-1.5 border-b border-slate-300 flex items-center justify-between avoid-break-after">
                        <div>
                            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-900 font-serif-header">
                                NACOC DIGITAL FORENSIC INVESTIGATION REPORT
                            </span>
                            <span class="ml-2 font-mono-code text-[8.5px] font-semibold text-slate-800">
                                Consolidated Summary Register &bull; {{ count($suspectReports) }} Case{{ count($suspectReports) > 1 ? 's' : '' }}
                            </span>
                        </div>
                        <div class="text-right">
                            <p class="text-[7.5px] font-bold uppercase tracking-wider text-slate-900">Confidential</p>
                            <p class="mt-0.5 text-[8.5px] font-bold uppercase text-slate-800 dpf-header-page-num">
                                Executive Summary &bull; Page {{ $summaryPageNum }} of <span class="doc-total-pages">{{ $totalDossierPages }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- REPORT TITLE -->
                    <div class="mt-1.5 pb-1.5 border-b border-slate-200 text-center avoid-break-after">
                        <p class="text-[8px] font-bold uppercase tracking-[0.2em] text-emerald-800">
                            OFFICIAL DOSSIER EXECUTIVE SUMMARY REGISTER
                        </p>
                        <h2 class="mt-0.5 text-sm font-bold uppercase tracking-wide text-slate-950 font-serif-header">
                            DIGITAL FORENSIC INVESTIGATION STATUS LOG REGISTER
                        </h2>
                        <p class="mt-0.5 text-[11px] font-bold uppercase tracking-wide text-slate-800 font-serif-header">
                            CONSOLIDATED SUMMARY OF ALL GENERATED CASES
                        </p>
                        <p class="mt-1 text-[13px] font-extrabold uppercase tracking-wider font-times text-emerald-800">
                            {{ $coverCaseSectionLabel ?? 'CURRENT/NEW CASES' }}
                        </p>
                    </div>
                </div>

                <!-- FLOW CONTENT BODY -->
                <div class="report-flow-body flex-1 min-h-0 flex flex-col justify-between mt-3">
                    <div class="flow-sections-container">

                        <!-- SECTION HEADER -->
                        <div class="flow-block section-summary-heading mb-2">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-50 text-[8.5px] font-bold text-emerald-700">
                                    &bull;
                                </span>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-[12px] font-bold uppercase tracking-[0.12em] text-slate-900">
                                            Summary
                                        </h3>
                                        <span class="text-[7.5px] font-semibold uppercase tracking-wider text-slate-600">
                                            Consolidated Forensic &amp; Warrant Status Log
                                        </span>
                                    </div>
                                    <div class="mt-0.5 h-px bg-slate-200"></div>
                                </div>
                            </div>
                        </div>

                        <!-- CONSOLIDATED SUMMARY TABLE -->
                        <div class="flow-block section-summary overflow-hidden border border-slate-300 rounded-lg">
                            <table class="w-full text-left border-collapse table-fixed">
                                <thead>
                                    <tr class="border-b border-slate-300 bg-slate-100/70 text-[8.5px] font-bold uppercase tracking-wider text-slate-900">
                                        <th class="py-1.5 px-1 text-center w-[5%] border-r border-slate-300">No.</th>
                                        <th class="py-1.5 px-2 text-left w-[22%] border-r border-slate-300">Case Name</th>
                                        <th class="py-1.5 px-1.5 text-center w-[11%] border-r border-slate-300">Status</th>
                                        <th class="py-1.5 px-2 text-left w-[20%] border-r border-slate-300">Device Model</th>
                                        <th class="py-1.5 px-1 text-center w-[14%] border-r border-slate-300">Court Order Warrant Requested</th>
                                        <th class="py-1.5 px-1 text-center w-[14%] border-r border-slate-300">Court Order Warrant Granted</th>
                                        <th class="py-1.5 px-1 text-center w-[14%]">Extraction &amp; Forensic Analysis Conducted</th>
                                    </tr>
                                </thead>
                                <tbody class="text-[9.5px] text-slate-800">
                                    @php
                                        $summaryRowCounter = 0;
                                    @endphp
                                    @foreach($suspectReports as $srItem)
                                        @php
                                            $srItemDevices = !empty($srItem['device_specs']) && is_array($srItem['device_specs']) ? $srItem['device_specs'] : [];
                                            if (empty($srItemDevices)) {
                                                $fbName = !empty($srItem['exhibit_type_display']) && $srItem['exhibit_type_display'] !== 'Electronic Exhibits' && $srItem['exhibit_type_display'] !== 'Mobile Devices & Hardware Exhibits'
                                                    ? $srItem['exhibit_type_display']
                                                    : (!empty($reportObj->dev_model) ? $reportObj->dev_model : 'Electronic Device');
                                                $srItemDevices = [
                                                    ['model' => $fbName, 'device_name' => $fbName]
                                                ];
                                            }

                                            $srItemCaseName = !empty($srItem['case_title'])
                                                ? $srItem['case_title']
                                                : (!empty($case->title) && !preg_match('/^republic\s+vs\.?$/i', trim($case->title)) && count($suspectReports) === 1
                                                    ? $case->title 
                                                    : ('Republic vs ' . ($srItem['suspect_name'] ?? 'Primary Suspect') . (!empty($srItem['co_suspects_display']) ? ' & ' . $srItem['co_suspects_display'] : '')));

                                            $srItemIsOngoing = (strtoupper($srItem['court_granted'] ?? 'NO') === 'YES')
                                                || (isset($srItem['case_section_label']) && str_contains(strtoupper($srItem['case_section_label']), 'ONGOING'));
                                            $srItemSummaryStatus = $srItemIsOngoing ? 'Ongoing' : 'Current';
                                            $srItemCourtReq = strtoupper($srItem['court_requested'] ?? 'NO');
                                            $srItemCourtGrant = strtoupper($srItem['court_granted'] ?? 'NO');
                                            $srItemExtraction = $srItem['extraction_status'] ?? 'NO';
                                        @endphp

                                        @foreach($srItemDevices as $dSpec)
                                            @php
                                                $summaryRowCounter++;
                                                $devModelName = trim((string)($dSpec['model'] ?? ''));
                                                if ($devModelName === '' || strtoupper($devModelName) === 'N/A' || $devModelName === 'Mobile Devices & Hardware Exhibits') {
                                                    $devModelName = trim((string)($dSpec['device_name'] ?? ''));
                                                }
                                                if ($devModelName === '' || strtoupper($devModelName) === 'N/A' || $devModelName === 'Mobile Devices & Hardware Exhibits') {
                                                    $devModelName = !empty($srItem['exhibit_type_display']) && !in_array($srItem['exhibit_type_display'], ['Mobile Devices & Hardware Exhibits', 'Mobile Devices & Exhibits', 'Electronic Exhibits', 'N/A'])
                                                        ? trim($srItem['exhibit_type_display'])
                                                        : (!empty($reportObj->dev_model) ? trim($reportObj->dev_model) : (!empty($case->dev_model) ? trim($case->dev_model) : 'Electronic Device'));
                                                }
                                                if ($devModelName === '' || strtoupper($devModelName) === 'N/A') {
                                                    $devModelName = !empty($srItem['exhibit_type_display']) && $srItem['exhibit_type_display'] !== 'N/A' ? $srItem['exhibit_type_display'] : 'Electronic Device';
                                                }
                                            @endphp
                                            <tr class="border-b border-slate-200 last:border-b-0">
                                                <td class="py-1 px-1 text-center font-bold text-slate-700 border-r border-slate-300">{{ $summaryRowCounter }}</td>
                                                <td class="py-1 px-2 font-bold text-slate-900 border-r border-slate-300 break-words">{{ $srItemCaseName }}</td>
                                                <td class="py-1 px-1.5 text-center font-extrabold border-r border-slate-300 {{ strtoupper($srItemSummaryStatus) === 'ONGOING' ? 'text-emerald-800' : 'text-blue-800' }}">{{ $srItemSummaryStatus }}</td>
                                                <td class="py-1 px-2 font-semibold text-slate-900 border-r border-slate-300 break-words">{{ $devModelName }}</td>
                                                <td class="py-1 px-1 text-center font-extrabold border-r border-slate-300 {{ $srItemCourtReq === 'YES' ? 'text-emerald-800' : 'text-slate-900' }}">{{ $srItemCourtReq }}</td>
                                                <td class="py-1 px-1 text-center font-extrabold border-r border-slate-300 {{ $srItemCourtGrant === 'YES' ? 'text-emerald-800' : 'text-slate-900' }}">{{ $srItemCourtGrant }}</td>
                                                <td class="py-1 px-1 text-center font-extrabold {{ str_starts_with(strtoupper($srItemExtraction), 'YES') ? 'text-emerald-800' : (str_starts_with(strtoupper($srItemExtraction), 'NO') ? 'text-rose-800' : 'text-slate-900') }}">{{ $srItemExtraction }}</td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- CONDITIONS NOTE (PLACED RIGHT UNDER THE SUMMARY TABLE) -->
                        <div class="flow-block section-conditions mt-4 pt-1.5 border-t border-slate-200 avoid-break">
                            <p class="text-[8.5px] font-semibold text-slate-800 mb-0.5 italic">
                                Note: The result(s) relate only to the item(s) / digital exhibit(s) examined across all referenced forensic cases.
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
                    <div class="flow-block section-signatures avoid-break mt-6 pb-2 footer">
                        <div class="grid grid-cols-2 gap-8">
                            <div class="flex flex-col">
                                <p class="text-[7.5px] font-bold uppercase tracking-wider text-slate-700">Head of Digital &amp; Physical Forensics</p>
                                
                                <div class="relative h-20 flex items-end pb-0.5">
                                    @if(!empty($dpfHeadSignature))
                                        <img src="{{ $dpfHeadSignature }}" alt="Official Digital Signature - {{ $dpfHeadName }}" class="absolute bottom-0 left-0 h-24 max-h-[96px] max-w-[280px] w-auto object-contain object-left filter contrast-125 mix-blend-multiply pointer-events-none" />
                                    @endif
                                </div>

                                <div class="border-b border-dashed border-slate-300 pb-0.5">
                                    <span class="text-[7px] italic text-slate-700">Electronically Signed &amp; Validated</span>
                                </div>
                                <div class="mt-0.5">
                                    <p class="text-[8.5px] font-bold text-slate-900 leading-tight">{{ $dpfHeadName }}</p>
                                    <p class="text-[7px] text-slate-800 leading-tight">Head of Digital &amp; Physical Forensics Unit, NACOC</p>
                                </div>
                            </div>

                            <div class="flex flex-col">
                                <p class="text-[7.5px] font-bold uppercase tracking-wider text-slate-700">Head of Forensic Department</p>
                                
                                <div class="relative h-20 flex items-end pb-0.5">
                                    @if(!empty($deptHeadSignature))
                                        <img src="{{ $deptHeadSignature }}" alt="Official Digital Signature - {{ $deptHeadName }}" class="absolute bottom-0 left-0 h-24 max-h-[96px] max-w-[280px] w-auto object-contain object-left filter contrast-125 mix-blend-multiply pointer-events-none" />
                                    @endif
                                </div>

                                <div class="border-b border-dashed border-slate-300 pb-0.5">
                                    <span class="text-[7px] italic text-slate-700">Authorized &amp; Counter-signed</span>
                                </div>
                                <div class="mt-0.5">
                                    <p class="text-[8.5px] font-bold text-slate-900 leading-tight">{{ $deptHeadName }}</p>
                                    <p class="text-[7px] text-slate-800 leading-tight">Head of Forensic Department, NACOC</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FOOTER -->
                <div class="page-footer-block shrink-0 mt-auto">
                    <div class="mt-auto pb-2 border-t border-slate-200 flex items-center justify-between text-[7.5px] text-slate-500 font-mono-code select-none footer">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-slate-700 tracking-wider">NACOC / DPFU &bull; CONSOLIDATED SUMMARY REGISTER</span>
                            <span>&bull;</span>
                            <span>{{ count($suspectReports) }} Case{{ count($suspectReports) > 1 ? 's' : '' }} Recorded</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span>STRICTLY CONFIDENTIAL</span>
                            <span>&bull;</span>
                            <span class="font-bold text-slate-800 dpf-footer-page-num">Page {{ $summaryPageNum }} of <span class="doc-total-pages">{{ $totalDossierPages }}</span></span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- SCRIPT FOR PDF.JS RENDERING & AUTO-PRINT -->
    <script>
        /**
         * Pristine snapshot cache keyed by the original .auto-paginated-root element.
         * Guarantees paginateReportPage() is idempotent — re-running it on an
         * already-paginated suspect page restores the pristine DOM first, so
         * continuation pages and remarks splits are never duplicated.
         */
        const dpfPristineSnapshots = new WeakMap();

        let dpfPaginationDone = false;
        function autoPaginateAllReports() {
            if (dpfPaginationDone) return;
            dpfPaginationDone = true;

            const reportRoots = document.querySelectorAll('#dpfReportSheet .auto-paginated-root');
            reportRoots.forEach(page => paginateReportPage(page));

            const summaryPages = document.querySelectorAll('#dpfReportSheet .section-summary-page');
            summaryPages.forEach(page => paginateSummaryRegisterPage(page));
        }

        function paginateReportPage(pageEl) {
            const inner = pageEl.querySelector('.relative.z-10');
            const headerBlock = pageEl.querySelector('.page-header-block');
            const footerBlock = pageEl.querySelector('.page-footer-block');
            const flowBody = pageEl.querySelector('.report-flow-body');
            if (!inner || !headerBlock || !footerBlock || !flowBody) return;

            // WHY: On first call, capture the pristine server-rendered state. On every
            // subsequent call, remove any previously generated continuation pages and
            // restore the pristine sections/signatures HTML before measuring or mutating.
            if (!dpfPristineSnapshots.has(pageEl)) {
                const liveSectionsContainer = flowBody.querySelector('.flow-sections-container');
                const liveSignaturesBlock = flowBody.querySelector('.section-signatures');
                dpfPristineSnapshots.set(pageEl, {
                    sectionsHtml: liveSectionsContainer ? liveSectionsContainer.innerHTML : '',
                    signaturesHtml: liveSignaturesBlock ? liveSignaturesBlock.outerHTML : '',
                    flowBodyClasses: flowBody.className,
                });
            } else {
                // Remove previously generated continuation pages for THIS suspect only
                const parentReportSheet = pageEl.closest('#dpfReportSheet') || document.getElementById('dpfReportSheet');
                if (parentReportSheet) {
                    let sib = pageEl.nextElementSibling;
                    while (sib && sib.classList.contains('generated-flow-page')) {
                        const next = sib.nextElementSibling;
                        sib.remove();
                        sib = next;
                    }
                }

                const snap = dpfPristineSnapshots.get(pageEl);

                // Restore flowBody classes and sections HTML
                flowBody.className = snap.flowBodyClasses;
                const restoredSections = flowBody.querySelector('.flow-sections-container');
                if (restoredSections) {
                    restoredSections.innerHTML = snap.sectionsHtml;
                }

                // Remove any leftover .section-signatures and reinsert pristine
                flowBody.querySelectorAll('.section-signatures').forEach(el => el.remove());
                if (snap.signaturesHtml) {
                    flowBody.insertAdjacentHTML('beforeend', snap.signaturesHtml);
                }

                // Re-query all references after innerHTML restoration
                // (existing locals `sectionsContainer` and `signaturesBlock` are now stale)
                // Note: `sectionsContainer` and `signaturesBlock` below must be re-assigned
            }

            let sectionsContainer = flowBody.querySelector('.flow-sections-container');
            let signaturesBlock = flowBody.querySelector('.section-signatures');
            if (!sectionsContainer) return;

            function getUsableHeight(page) {
                const pInner = page.querySelector('.relative.z-10') || page;
                const pHdr = page.querySelector('.page-header-block');
                const pFtr = page.querySelector('.page-footer-block');
                const hH = pHdr ? pHdr.offsetHeight : 0;
                const fH = pFtr ? pFtr.offsetHeight : 0;
                const innerH = pInner.clientHeight > 0 ? pInner.clientHeight : (page.clientHeight - 76);
                return innerH - hH - fH - 6;
            }

            function getPageContentHeight(page) {
                const bdy = page.querySelector('.report-flow-body');
                if (!bdy) return 0;
                const secCont = bdy.querySelector('.flow-sections-container');
                const sigs = bdy.querySelector('.section-signatures');
                const secH = secCont ? secCont.offsetHeight : 0;
                const sigH = sigs ? (sigs.offsetHeight + 14) : 0;
                return secH + sigH;
            }

            function isPageOverflowing(page) {
                return getPageContentHeight(page) > getUsableHeight(page);
            }

            if (!isPageOverflowing(pageEl)) {
                return;
            }

            const rawBlocks = Array.from(sectionsContainer.children);

            const remarksIndex = rawBlocks.findIndex(block => {
                const hasTable = !!block.querySelector('table');
                return (
                    block.classList.contains('section-remarks') ||
                    block.classList.contains('section-04') ||
                    block.classList.contains('section-03')
                ) && !!block.querySelector('.flow-text') && !hasTable;
            });

            if (remarksIndex === -1) {
                return;
            }

            const remarksBlock = rawBlocks[remarksIndex];
            const remarksHeadingEl = remarksBlock.querySelector('.flex.items-center');
            const flowTextEl = remarksBlock.querySelector('.flow-text');
            const fullRemarksText = (flowTextEl ? flowTextEl.textContent : '').trim();

            const preRemarksBlocks = rawBlocks.slice(0, remarksIndex);
            const postRemarksBlocks = rawBlocks.slice(remarksIndex + 1);

            const conditionsIndex = postRemarksBlocks.findIndex(b => b.classList.contains('section-conditions'));
            const conditionsBlock = conditionsIndex !== -1 ? postRemarksBlocks[conditionsIndex] : null;

            sectionsContainer.innerHTML = '';
            if (signaturesBlock && signaturesBlock.parentNode) {
                signaturesBlock.parentNode.removeChild(signaturesBlock);
            }

            let currentPage = pageEl;
            let currentFlowBody = flowBody;
            let currentSectionsContainer = sectionsContainer;

            flowBody.classList.remove('overflow-hidden');

            function createNextPage() {
                const nextPg = pageEl.cloneNode(false);
                nextPg.className = pageEl.className;
                if (!nextPg.classList.contains('page-break')) {
                    nextPg.classList.add('page-break');
                }
                nextPg.classList.add('generated-flow-page');

                const wm = pageEl.querySelector('.dossier-watermark, .absolute.inset-0');
                if (wm) nextPg.appendChild(wm.cloneNode(true));

                const nextInner = document.createElement('div');
                nextInner.className = 'relative z-10 flex flex-col justify-between h-full min-h-0';

                const nextHdr = headerBlock.cloneNode(true);
                const nextBdy = document.createElement('div');
                nextBdy.className = 'report-flow-body flex-1 min-h-0 flex flex-col justify-between';
                const nextSecCont = document.createElement('div');
                nextSecCont.className = 'flow-sections-container';
                nextBdy.appendChild(nextSecCont);

                const nextFtr = footerBlock.cloneNode(true);

                nextInner.appendChild(nextHdr);
                nextInner.appendChild(nextBdy);
                nextInner.appendChild(nextFtr);
                nextPg.appendChild(nextInner);

                currentPage.parentNode.insertBefore(nextPg, currentPage.nextSibling);
                currentPage = nextPg;
                currentFlowBody = nextBdy;
                currentSectionsContainer = nextSecCont;
            }

            // 1. Append Pre-Remarks Blocks
            preRemarksBlocks.forEach(b => {
                currentSectionsContainer.appendChild(b);
            });

            // 2. Append Remarks Heading
            let remarksHeadingWrap = null;
            if (remarksHeadingEl) {
                remarksHeadingWrap = document.createElement('div');
                remarksHeadingWrap.className = 'flow-block section-remarks-heading mt-9 avoid-break-after';
                remarksHeadingWrap.appendChild(remarksHeadingEl.cloneNode(true));
                currentSectionsContainer.appendChild(remarksHeadingWrap);
            }

            // 3. Prepare Remarks Paragraphs
            const rawParagraphs = fullRemarksText
                ? fullRemarksText.split(/\r?\n/).map(p => p.trim()).filter(Boolean)
                : [];

            if (rawParagraphs.length === 0 && fullRemarksText.length > 0) {
                rawParagraphs.push(fullRemarksText.trim());
            }

            function createParagraphElement(text) {
                const p = document.createElement('p');
                p.className = 'flow-block flow-p text-[12px] leading-[2] text-slate-900 text-justify mt-2 whitespace-pre-line break-words';
                p.textContent = text;
                return p;
            }

            let remarksTextPlacedOnCurrentPage = false;

            for (let pIdx = 0; pIdx < rawParagraphs.length; pIdx++) {
                let remainingText = rawParagraphs[pIdx];

                while (remainingText.length > 0) {
                    const pEl = createParagraphElement(remainingText);
                    currentSectionsContainer.appendChild(pEl);

                    if (!isPageOverflowing(currentPage)) {
                        remarksTextPlacedOnCurrentPage = true;
                        remainingText = '';
                    } else {
                        const words = remainingText.split(/\s+/);

                        let bestK = 0;
                        if (words.length > 1) {
                            let low = 1;
                            let high = words.length - 1;

                            while (low <= high) {
                                const mid = Math.floor((low + high) / 2);
                                pEl.textContent = words.slice(0, mid).join(' ');
                                if (isPageOverflowing(currentPage)) {
                                    high = mid - 1;
                                } else {
                                    bestK = mid;
                                    low = mid + 1;
                                }
                            }
                        }

                        const isPageEmpty = (currentSectionsContainer.children.length <= 1);

                        if (bestK >= 4 || (isPageEmpty && words.length > 1 && bestK >= 1)) {
                            const takeCount = Math.max(bestK, 1);
                            pEl.textContent = words.slice(0, takeCount).join(' ');
                            remainingText = words.slice(takeCount).join(' ').trim();
                            remarksTextPlacedOnCurrentPage = true;

                            createNextPage();
                            remarksTextPlacedOnCurrentPage = false;
                        } else if (words.length <= 1) {
                            pEl.textContent = remainingText;
                            remainingText = '';
                            remarksTextPlacedOnCurrentPage = true;
                            break;
                        } else {
                            currentSectionsContainer.removeChild(pEl);

                            if (!remarksTextPlacedOnCurrentPage && remarksHeadingWrap && remarksHeadingWrap.parentNode === currentSectionsContainer) {
                                currentSectionsContainer.removeChild(remarksHeadingWrap);
                                createNextPage();
                                currentSectionsContainer.appendChild(remarksHeadingWrap);
                            } else {
                                createNextPage();
                            }

                            remarksTextPlacedOnCurrentPage = false;
                        }
                    }
                }
            }

            // 4. Append Conditions Block
            if (conditionsBlock) {
                currentSectionsContainer.appendChild(conditionsBlock);
                if (isPageOverflowing(currentPage)) {
                    currentSectionsContainer.removeChild(conditionsBlock);
                    createNextPage();
                    currentSectionsContainer.appendChild(conditionsBlock);
                }
            }

            // 5. Append Signatures Block
            if (signaturesBlock) {
                currentFlowBody.appendChild(signaturesBlock);
                if (isPageOverflowing(currentPage)) {
                    currentFlowBody.removeChild(signaturesBlock);

                    let movedConditions = null;
                    if (conditionsBlock && conditionsBlock.parentNode === currentSectionsContainer) {
                        movedConditions = conditionsBlock;
                        currentSectionsContainer.removeChild(conditionsBlock);
                    }

                    createNextPage();

                    if (movedConditions) {
                        currentSectionsContainer.appendChild(movedConditions);
                    }
                    currentFlowBody.appendChild(signaturesBlock);
                }
            }
        }

        const dpfSummaryPristineSnapshots = new WeakMap();

        function paginateSummaryRegisterPage(pageEl) {
            const inner = pageEl.querySelector('.relative.z-10');
            const headerBlock = pageEl.querySelector('.page-header-block');
            const footerBlock = pageEl.querySelector('.page-footer-block');
            const flowBody = pageEl.querySelector('.report-flow-body');
            if (!inner || !headerBlock || !footerBlock || !flowBody) return;

            if (!dpfSummaryPristineSnapshots.has(pageEl)) {
                const liveSectionsContainer = flowBody.querySelector('.flow-sections-container');
                const liveSignaturesBlock = flowBody.querySelector('.section-signatures');
                dpfSummaryPristineSnapshots.set(pageEl, {
                    sectionsHtml: liveSectionsContainer ? liveSectionsContainer.innerHTML : '',
                    signaturesHtml: liveSignaturesBlock ? liveSignaturesBlock.outerHTML : '',
                    flowBodyClasses: flowBody.className,
                });
            } else {
                const parentReportSheet = pageEl.closest('#dpfReportSheet') || document.getElementById('dpfReportSheet');
                if (parentReportSheet) {
                    let sib = pageEl.nextElementSibling;
                    while (sib && sib.classList.contains('generated-summary-page')) {
                        const next = sib.nextElementSibling;
                        sib.remove();
                        sib = next;
                    }
                }

                const snap = dpfSummaryPristineSnapshots.get(pageEl);
                flowBody.className = snap.flowBodyClasses;
                const restoredSections = flowBody.querySelector('.flow-sections-container');
                if (restoredSections) {
                    restoredSections.innerHTML = snap.sectionsHtml;
                }
                flowBody.querySelectorAll('.section-signatures').forEach(el => el.remove());
                if (snap.signaturesHtml) {
                    flowBody.insertAdjacentHTML('beforeend', snap.signaturesHtml);
                }
            }

            let sectionsContainer = flowBody.querySelector('.flow-sections-container');
            let signaturesBlock = flowBody.querySelector('.section-signatures');
            if (!sectionsContainer) return;

            function getUsableHeight(page) {
                const pInner = page.querySelector('.relative.z-10') || page;
                const pHdr = page.querySelector('.page-header-block');
                const pFtr = page.querySelector('.page-footer-block');
                const hH = pHdr ? pHdr.offsetHeight : 0;
                const fH = pFtr ? pFtr.offsetHeight : 0;
                const innerH = pInner.clientHeight > 0 ? pInner.clientHeight : (page.clientHeight - 76);
                return innerH - hH - fH - 18;
            }

            function getPageContentHeight(page) {
                const bdy = page.querySelector('.report-flow-body');
                if (!bdy) return 0;
                const secCont = bdy.querySelector('.flow-sections-container');
                const sigs = bdy.querySelector('.section-signatures');
                const secH = secCont ? secCont.offsetHeight : 0;
                const sigH = sigs ? (sigs.offsetHeight + 24) : 0;
                return secH + sigH;
            }

            function isPageOverflowing(page) {
                return getPageContentHeight(page) > getUsableHeight(page);
            }

            if (!isPageOverflowing(pageEl)) {
                return;
            }

            const headingBlock = sectionsContainer.querySelector('.section-summary-heading');
            const tableWrap = sectionsContainer.querySelector('.section-summary');
            const tableEl = tableWrap ? tableWrap.querySelector('table') : null;
            const theadEl = tableEl ? tableEl.querySelector('thead') : null;
            const tbodyEl = tableEl ? tableEl.querySelector('tbody') : null;
            const conditionsBlock = sectionsContainer.querySelector('.section-conditions');

            if (!tableEl || !tbodyEl) return;

            const allRows = Array.from(tbodyEl.children);
            if (allRows.length === 0) return;

            // Remove conditions and signatures temporarily while distributing table rows
            if (conditionsBlock && conditionsBlock.parentNode) {
                conditionsBlock.parentNode.removeChild(conditionsBlock);
            }
            if (signaturesBlock && signaturesBlock.parentNode) {
                signaturesBlock.parentNode.removeChild(signaturesBlock);
            }

            tbodyEl.innerHTML = '';

            let currentPage = pageEl;
            let currentFlowBody = flowBody;
            let currentSectionsContainer = sectionsContainer;
            let currentTbody = tbodyEl;

            function createNextSummaryPage() {
                const nextPg = pageEl.cloneNode(false);
                nextPg.className = pageEl.className;
                if (!nextPg.classList.contains('page-break')) {
                    nextPg.classList.add('page-break');
                }
                nextPg.classList.add('generated-summary-page');

                const wm = pageEl.querySelector('.dossier-watermark, .absolute.inset-0');
                if (wm) nextPg.appendChild(wm.cloneNode(true));

                const nextInner = document.createElement('div');
                nextInner.className = 'relative z-10 flex flex-col justify-between h-full min-h-0';

                const nextHdr = headerBlock.cloneNode(true);
                const nextBdy = document.createElement('div');
                nextBdy.className = 'report-flow-body flex-1 min-h-0 flex flex-col justify-between mt-3';
                const nextSecCont = document.createElement('div');
                nextSecCont.className = 'flow-sections-container';
                nextBdy.appendChild(nextSecCont);

                const nextFtr = footerBlock.cloneNode(true);

                nextInner.appendChild(nextHdr);
                nextInner.appendChild(nextBdy);
                nextInner.appendChild(nextFtr);
                nextPg.appendChild(nextInner);

                currentPage.parentNode.insertBefore(nextPg, currentPage.nextSibling);
                currentPage = nextPg;
                currentFlowBody = nextBdy;
                currentSectionsContainer = nextSecCont;
                currentTbody = null;
            }

            function getOrCreateCurrentTbody() {
                if (currentTbody && currentTbody.parentNode && currentTbody.parentNode.parentNode) {
                    return currentTbody;
                }
                const existingTbody = currentSectionsContainer.querySelector('.section-summary table tbody');
                if (existingTbody) {
                    currentTbody = existingTbody;
                    return currentTbody;
                }

                const nextTableWrap = document.createElement('div');
                nextTableWrap.className = tableWrap.className;
                const nextTable = document.createElement('table');
                nextTable.className = tableEl.className;
                nextTable.appendChild(theadEl.cloneNode(true));
                const newTbody = document.createElement('tbody');
                newTbody.className = tbodyEl.className;
                nextTable.appendChild(newTbody);
                nextTableWrap.appendChild(nextTable);
                currentSectionsContainer.appendChild(nextTableWrap);
                currentTbody = newTbody;
                return currentTbody;
            }

            for (let rIdx = 0; rIdx < allRows.length; rIdx++) {
                const rowEl = allRows[rIdx];
                const activeTbody = getOrCreateCurrentTbody();
                activeTbody.appendChild(rowEl);

                if (isPageOverflowing(currentPage)) {
                    if (activeTbody.children.length > 1) {
                        activeTbody.removeChild(rowEl);
                        createNextSummaryPage();
                        const newActiveTbody = getOrCreateCurrentTbody();
                        newActiveTbody.appendChild(rowEl);
                    }
                }
            }

            // Append Conditions Block
            if (conditionsBlock) {
                currentSectionsContainer.appendChild(conditionsBlock);
                if (isPageOverflowing(currentPage)) {
                    currentSectionsContainer.removeChild(conditionsBlock);
                    createNextSummaryPage();
                    currentSectionsContainer.appendChild(conditionsBlock);
                }
            }

            // Append Signatures Block
            if (signaturesBlock) {
                currentFlowBody.appendChild(signaturesBlock);
                if (isPageOverflowing(currentPage)) {
                    currentFlowBody.removeChild(signaturesBlock);

                    let movedConditions = null;
                    if (conditionsBlock && conditionsBlock.parentNode === currentSectionsContainer) {
                        movedConditions = conditionsBlock;
                        currentSectionsContainer.removeChild(conditionsBlock);
                    }

                    createNextSummaryPage();

                    if (movedConditions) {
                        currentSectionsContainer.appendChild(movedConditions);
                    }
                    currentFlowBody.appendChild(signaturesBlock);
                }
            }
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
                    } else if (fullBadge.includes('Executive Summary') || fullBadge.includes('Summary')) {
                        hBadge.innerHTML = `Executive Summary &bull; Page ${pageNum} of <span class="doc-total-pages">${total}</span>`;
                    } else {
                        hBadge.innerHTML = `Page ${pageNum} of <span class="doc-total-pages">${total}</span>`;
                    }
                }

                const fBadge = p.querySelector('.dpf-footer-page-num');
                if (fBadge) fBadge.innerHTML = `Page ${pageNum} of <span class="doc-total-pages">${total}</span>`;
            });
        }

        /**
         * WHY: Wait for (a) any in-flight PDF rendering to finish, then (b) a full
         * layout+paint cycle, then (c) fire window.print(). This guarantees the
         * print snapshot captures every PDF slice and every generated continuation
         * page, instead of printing a half-rendered document.
         */
        window.handlePrintDocument = async function() {
            if (window.isPdfRendering) {
                alert('Document pages are currently being prepared for print. Please try again in a few seconds.');
                return;
            }
            // Two rAFs + a macrotask so the browser finishes layout of any recently
            // inserted PDF slices / continuation pages before capture.
            await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)));
            await new Promise(resolve => setTimeout(resolve, 0));
            window.print();
        };

        async function initMultiDpfReport() {
            if (document.fonts && document.fonts.ready) {
                try {
                    await document.fonts.ready;
                } catch (e) {}
            }
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
                const suspectLabel = wrapper.getAttribute('data-suspect-label') || 'Suspect';
                const suspectName = wrapper.getAttribute('data-suspect-name') || '';
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
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.07] select-none">
                                <img src="${watermarkUrl}" alt="NACOC Watermark" class="w-[900px] h-[900px] object-contain" />
                            </div>
                            <div class="relative z-10 flex flex-col justify-between h-full min-h-0">
                                <div>
                                    <div class="relative pt-2 pb-3 border-b border-slate-300 flex items-center justify-between">
                                        <div class="flex items-center flex-wrap gap-x-2 gap-y-0.5 max-w-[80%]">
                                            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-900 font-serif-header">
                                                NACOC FORENSIC REPORT &bull; ATTACHED EXHIBIT
                                            </span>
                                            <span class="font-mono-code text-[9px] font-semibold text-slate-800">
                                                Ref: ${caseNo} &bull; ${dpfCode}
                                            </span>
                                            ${suspectName ? `
                                            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-900 font-serif-header">
                                                &bull; ${suspectLabel}: <span class="font-mono-code text-slate-800 font-bold">${suspectName}</span>
                                            </span>
                                            ` : ''}
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
                                            ${attTitle} ${suspectName ? `&bull; ${suspectLabel}: ${suspectName}` : ''}
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
                                        <span class="font-bold text-slate-700 tracking-wider">NACOC / DPFU &bull; ${suspectName || 'OFFICIAL EXHIBIT'}</span>
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
                    // Silently ignore PDF attachment render error
                }
            }
            window.isPdfRendering = false;
            renumberAllPages();
        }
    </script>

    @if(request()->has('print') || request()->has('auto_print'))
    <script>
        /**
         * WHY: Wait until initMultiDpfReport() has finished its async PDF rendering
         * (window.isPdfRendering flips back to false), then invoke the shared
         * print handler. No arbitrary setTimeout guess.
         */
        window.addEventListener('load', function() {
            const waitForRender = () => {
                if (window.isPdfRendering) {
                    return setTimeout(waitForRender, 150);
                }
                window.handlePrintDocument();
            };
            waitForRender();
        });
    </script>
    @endif
</body>
</html>
