@php
    $authUser = auth()->user();
    $caseList = collect($cases ?? []);
    $totalCount = $caseList->count();

    // Signatories (Fallback to Unspecify)
    $cssOfficerName = trim((string)($authUser?->fullname ?? ($authUser?->name ?? ''))) ?: 'Unspecify';
    $cssHeadName = trim((string)($dbCssHead?->fullname ?? ($dbCssHead?->name ?? ''))) ?: 'Unspecify';
    $deptHeadName = trim((string)($dbDeptHead?->fullname ?? ($dbDeptHead?->name ?? ''))) ?: 'Unspecify';
    $reportGeneratedDate = now()->format('d F Y');

    // Report Period Labeling
    $rawPeriod = strtolower(trim((string)($period ?? 'general')));
    $hasCustomRange = !empty($fromDate) || !empty($toDate);

    $periodLabel = match(true) {
        $hasCustomRange => 'Period: ' . ($fromDate ? date('d F Y', strtotime($fromDate)) : 'Start') . ' – ' . ($toDate ? date('d F Y', strtotime($toDate)) : 'Present'),
        in_array($rawPeriod, ['weekly', 'week']) => 'Period: ' . now()->subDays(7)->format('d F Y') . ' – ' . now()->format('d F Y'),
        in_array($rawPeriod, ['monthly', 'month']) => 'Period: ' . now()->subDays(30)->format('d F Y') . ' – ' . now()->format('d F Y'),
        in_array($rawPeriod, ['annual', 'annually', 'year', 'yearly']) => 'Period: ' . now()->subYear()->format('d F Y') . ' – ' . now()->format('d F Y'),
        default => 'PERIOD: ALL REGISTERED CRIME SCENE DOCKETS (CUMULATIVE)'
    };

    $reportTypeLabel = match(true) {
        in_array($rawPeriod, ['weekly', 'week']) => 'WEEKLY CRIME SCENE REPORT',
        in_array($rawPeriod, ['monthly', 'month']) => 'MONTHLY CRIME SCENE REPORT',
        in_array($rawPeriod, ['annual', 'annually', 'year', 'yearly']) => 'ANNUAL CRIME SCENE REPORT',
        default => $hasCustomRange ? 'CUSTOM DATE RANGE CRIME SCENE REPORT' : 'CRIME SCENE SUPPORT REPORT'
    };

    $ordinalWords = [
        1 => 'ONE', 2 => 'TWO', 3 => 'THREE', 4 => 'FOUR', 5 => 'FIVE',
        6 => 'SIX', 7 => 'SEVEN', 8 => 'EIGHT', 9 => 'NINE', 10 => 'TEN',
        11 => 'ELEVEN', 12 => 'TWELVE', 13 => 'THIRTEEN', 14 => 'FOURTEEN', 15 => 'FIFTEEN',
        16 => 'SIXTEEN', 17 => 'SEVENTEEN', 18 => 'EIGHTEEN', 19 => 'NINETEEN', 20 => 'TWENTY',
        21 => 'TWENTY-ONE', 22 => 'TWENTY-TWO', 23 => 'TWENTY-THREE', 24 => 'TWENTY-FOUR', 25 => 'TWENTY-FIVE'
    ];

    $validationAuthorityName = ($cssHeadName !== 'Unspecify') ? $cssHeadName : $cssOfficerName;

    // Pre-build each case dossier entry and estimate its height in mm for A4 sheet partitioning
    $preparedCases = collect();
    foreach ($caseList->values() as $idx => $case) {
        $caseIndex = $idx + 1;
        $caseOrdinal = $ordinalWords[$caseIndex] ?? (string)$caseIndex;
        $caseNumDisplay = trim((string)($case->case_number ?? '')) ?: 'Unspecify';
        $caseHeaderTitle = 'CASE ' . $caseOrdinal . ' — ' . $caseNumDisplay;

        // Date (Fallback to Unspecify)
        $rawDate = $case->incident_date ?: ($case->arrest_date ?: $case->created_at);
        $dateFormatted = ($rawDate && strtotime((string)$rawDate)) ? date('d F Y', strtotime((string)$rawDate)) : 'Unspecify';

        // Operation Type (Fallback to Unspecify)
        $opName = trim((string)(
            $case->operation_code_name 
            ?: ($case->operation_code 
            ?: ($case->docket_data['operation_code_name'] 
            ?? ($case->docket_data['operation_code'] 
            ?? '')))
        )) ?: 'Unspecify';

        // Location (Fallback to Unspecify)
        $location = trim((string)(
            $case->raid_location 
            ?: ($case->incident_scene 
            ?: ($case->seizure_location 
            ?: ($case->exhibits->first()?->seizure_location ?? '')))
        )) ?: 'Unspecify';

        // Suspects (Fallback to Unspecify)
        $suspectsList = collect($case->docket_data['suspects'] ?? []);
        $suspectNames = $suspectsList->pluck('suspect_name')->filter()->map(fn($n) => trim((string)$n))->filter()->values();
        if ($suspectNames->isEmpty() && !empty($case->suspect_name)) {
            $suspectNames = collect([trim((string)$case->suspect_name)]);
        }
        $suspectsDisplay = $suspectNames->isNotEmpty() ? $suspectNames->implode(', ') : 'Unspecify';

        // Case Briefing Narrative (Fetched from offense_description column in database, fallback to Unspecify)
        $caseBriefing = trim((string)(
            $case->offense_description 
            ?? ($case->underlyingRecord?->offense_description ?? '')
        )) ?: 'Unspecify';

        // Exhibits Retrieved (Fallback to Unspecify)
        $exhibitsList = $case->relationLoaded('exhibits') && $case->exhibits->isNotEmpty() 
            ? $case->exhibits 
            : collect($case->docket_data['seizures'] ?? []);

        $exhibitLines = [];
        foreach ($exhibitsList as $eIdx => $ex) {
            $name = is_object($ex) ? ($ex->commodity_name ?: ($ex->exhibit_category ?: '')) : ($ex['seizure_name'] ?? ($ex['category'] ?? ''));
            $name = trim((string)$name);
            if (empty($name)) {
                continue;
            }

            $details = [];

            $qty = is_object($ex) ? $ex->seizure_quantity : ($ex['seizure_quantity'] ?? null);
            if (!empty($qty)) $details[] = "Qty: {$qty}";

            $wt = is_object($ex) ? $ex->seizure_weight : ($ex['seizure_weight'] ?? null);
            $unit = is_object($ex) ? $ex->seizure_unit : ($ex['seizure_unit'] ?? null);
            if (!empty($wt)) $details[] = "Weight: {$wt}" . ($unit ? " {$unit}" : '');

            $amt = is_object($ex) ? ($ex->amount ?: $ex->seizure_value) : ($ex['amount'] ?? ($ex['seizure_value'] ?? null));
            if (!empty($amt)) {
                $cleanAmt = is_numeric($amt) ? 'GH₵' . number_format((float)$amt, 2) : (string)$amt;
                $details[] = $cleanAmt;
            }

            $pkg = is_object($ex) ? $ex->seizure_packaging : ($ex['seizure_packaging'] ?? null);
            if (!empty($pkg)) $details[] = "Packaging: {$pkg}";

            $desc = is_object($ex) ? $ex->description_of_exhibits : ($ex['seizure_description'] ?? null);
            if (!empty($desc) && strtolower((string)$desc) !== strtolower((string)$name)) {
                $details[] = $desc;
            }

            $detailStr = !empty($details) ? ' (' . implode(', ', $details) . ')' : '';
            $exhibitLines[] = ($eIdx + 1) . "\t" . $name . $detailStr;
        }

        if (empty($exhibitLines) && !empty($case->description_of_exhibits)) {
            $exhibitLines[] = trim((string)$case->description_of_exhibits);
        } elseif (empty($exhibitLines) && !empty($case->exhibits_summary)) {
            $exhibitLines[] = trim((string)$case->exhibits_summary);
        }

        // Estimate table height in millimeters for A4 page budgeting
        $briefLinesCount = max(1, (int)ceil(mb_strlen($caseBriefing) / 72)) + substr_count($caseBriefing, "\n");
        $suspectLinesCount = max(1, (int)ceil(mb_strlen($suspectsDisplay) / 65));
        $exhibitRowsCount = max(1, count($exhibitLines));
        $estimatedHeightMm = 54.0 + ($briefLinesCount * 4.5) + ($suspectLinesCount * 4.5) + ($exhibitRowsCount * 5.2);

        $preparedCases->push([
            'headerTitle'     => $caseHeaderTitle,
            'dateFormatted'   => $dateFormatted,
            'opName'          => $opName,
            'location'        => $location,
            'caseBriefing'    => $caseBriefing,
            'suspectsDisplay' => $suspectsDisplay,
            'exhibitLines'    => $exhibitLines,
            'heightMm'        => $estimatedHeightMm,
        ]);
    }

    // Partition prepared cases across A4 portrait sheets (matching Evidence_Management/report_pdf)
    $firstPageBudget        = 190.0; // mm available on first body page
    $continuationPageBudget = 215.0; // mm available on continuation body pages
    $signaturesH            = 42.0;  // mm for 2-column signature block

    // Estimate height for Status, Recommendation, Conclusion notes if provided
    $notesHeightMm = 0.0;
    if (!empty($reportNote?->status)) {
        $notesHeightMm += 14.0 + (max(1, (int)ceil(mb_strlen($reportNote->status) / 90)) * 4.0);
    }
    if (!empty($reportNote?->recommendation)) {
        $notesHeightMm += 14.0 + (max(1, (int)ceil(mb_strlen($reportNote->recommendation) / 90)) * 4.0);
    }
    if (!empty($reportNote?->conclusion)) {
        $notesHeightMm += 14.0 + (max(1, (int)ceil(mb_strlen($reportNote->conclusion) / 90)) * 4.0);
    }
    $closingBlockH = $signaturesH + $notesHeightMm;

    $dossierPages = [];
    $remainingCases = $preparedCases->values();
    $isFirst = true;

    if ($remainingCases->isEmpty()) {
        $dossierPages[] = [
            'isFirst'    => true,
            'isLast'     => true,
            'cases'      => collect(),
            'signatures' => true,
            'hasNotes'   => true,
        ];
    } else {
        while ($remainingCases->isNotEmpty()) {
            $pageLimit = $isFirst ? $firstPageBudget : $continuationPageBudget;
            $currentUsed = 0.0;
            $pageCases = collect();

            while ($remainingCases->isNotEmpty()) {
                $nextCase = $remainingCases->first();
                $h = $nextCase['heightMm'];

                $isLastCaseInSet = ($remainingCases->count() === 1);
                $extraH = ($isLastCaseInSet && $notesHeightMm > 0) ? $notesHeightMm : 0.0;

                if (($currentUsed + $h + $extraH > $pageLimit) && $pageCases->isNotEmpty()) {
                    break;
                }

                $pageCases->push($remainingCases->shift());
                $currentUsed += $h;
            }

            $isFinished = $remainingCases->isEmpty();
            $placeSignatures = false;

            if ($isFinished) {
                if (($currentUsed + $closingBlockH) <= ($pageLimit + 15.0)) {
                    $placeSignatures = true;
                }
            }

            $dossierPages[] = [
                'isFirst'    => $isFirst,
                'isLast'     => $isFinished && $placeSignatures,
                'cases'      => $pageCases,
                'signatures' => $placeSignatures,
                'hasNotes'   => $isFinished,
            ];

            if ($isFinished && !$placeSignatures) {
                $dossierPages[] = [
                    'isFirst'    => false,
                    'isLast'     => true,
                    'cases'      => collect(),
                    'signatures' => true,
                    'hasNotes'   => false,
                ];
            }

            $isFirst = false;
        }
    }

    $totalDossierPages = 1 + count($dossierPages);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NACOC Crime Scene Support Unit &bull; Crime Scene Report</title>

    <!-- Scripts & Styles -->
    <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700;800&family=Times+New+Roman&display=swap" rel="stylesheet">

    <style>
        @font-face {
            font-family: 'Arial-Narrow-Font';
            src: url('{{ asset('font/Arial-Narrow-Font.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'Arial-Narrow-Font';
            src: url('{{ asset('font/Arial-Narrow-Bold.ttf') }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        @font-face {
            font-family: 'Arial-Narrow-Font';
            src: url('{{ asset('font/Arial-Narrow-Bold.ttf') }}') format('truetype');
            font-weight: 700;
            font-style: normal;
        }

        @font-face {
            font-family: 'Arial-Narrow-Font';
            src: url('{{ asset('font/Arial-Narrow-Bold.ttf') }}') format('truetype');
            font-weight: 800;
            font-style: normal;
        }

        @font-face {
            font-family: 'Arial-Narrow-Font';
            src: url('{{ asset('font/Arial-Narrow-Bold.ttf') }}') format('truetype');
            font-weight: 900;
            font-style: normal;
        }

        @page {
            size: A4 portrait;
            margin: 0 !important;
        }

        * {
            box-sizing: border-box;
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
        }

        ::-webkit-scrollbar,
        *::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }

        body {
            font-family: 'Arial-Narrow-Font', 'Arial Narrow', Arial, sans-serif;
            font-size: 11.3pt;
            background-color: #ffffff;
            color: #1e293b;
            margin: 0;
            padding: 24px 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .font-serif-header {
            font-family: 'Arial-Narrow-Font', 'Arial Narrow', Arial, sans-serif;
            letter-spacing: 0.04em;
        }

        .font-mono-code {
            font-family: 'Arial-Narrow-Font', 'Arial Narrow', 'JetBrains Mono', monospace;
        }

        .font-times {
            font-family: 'Arial-Narrow-Font', 'Arial Narrow', Arial, sans-serif;
        }

        body,
        .a4-page,
        .cover-page,
        .dynamic-dossier-page,
        table,
        th,
        td,
        button {
            font-family: 'Arial-Narrow-Font', 'Arial Narrow', Arial, sans-serif !important;
        }

        /* Standard A4 Sheet (matching Evidence_Management & Lab_Analysis reports) */
        .a4-page {
            width: 210mm;
            height: 297mm;
            max-height: 297mm;
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
            box-sizing: border-box;
        }

        .dynamic-dossier-page {
            width: 210mm;
            height: 297mm;
            max-height: 297mm;
            min-height: 297mm;
            padding: 8mm 10mm 8mm 10mm;
            box-sizing: border-box;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #ffffff;
            overflow: hidden;
            page-break-after: always;
            break-after: page;
        }

        .page-content-wrapper {
            position: relative;
            z-index: 10;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
            min-height: 100%;
            max-height: 100%;
            width: 100%;
            flex: 1 1 0%;
            box-sizing: border-box;
            overflow: hidden;
        }

        .page-header-block {
            flex-shrink: 0;
            width: 100%;
        }

        .report-flow-body {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            width: 100%;
            margin-bottom: 12px !important;
            overflow: hidden;
        }

        .section-signatures {
            flex-shrink: 0;
            width: 100%;
            margin-top: auto;
            margin-bottom: 4px;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .page-footer-block {
            flex-shrink: 0 !important;
            margin-top: auto !important;
            width: 100% !important;
            position: relative !important;
            z-index: 30 !important;
            background-color: #ffffff !important;
            padding-top: 2.5mm !important;
            padding-bottom: 1mm !important;
        }

        .avoid-break {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        /* Dossier Case Table (Two-Column Format) */
        .dossier-wrapper {
            margin-bottom: 14px;
            page-break-inside: avoid;
        }

        table.case-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5pt;
            border: 1.5px solid #0f172a;
            page-break-inside: avoid;
        }

        table.case-table td {
            border: 1px solid #94a3b8;
            padding: 6px 10px;
            vertical-align: top;
            line-height: 1.32;
            overflow-wrap: break-word;
            word-break: break-word;
        }

        /* Header Row (CASE ONE, CASE TWO, etc.) - White Background */
        table.case-table tr.row-case-header td {
            background-color: #ffffff;
            color: #0f172a;
            font-weight: 900;
            font-size: 11pt;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border: 1px solid #94a3b8;
            border-bottom: 1.5px solid #0f172a;
            padding: 7px 10px;
        }

        table.case-table tr.row-case-header td.td-label {
            width: 150px;
            background-color: #ffffff;
            border: 1px solid #94a3b8;
            border-right: 1.5px solid #94a3b8;
            border-bottom: 1.5px solid #0f172a;
        }

        /* Label Column (Column 1) */
        table.case-table td.td-label {
            width: 150px;
            min-width: 140px;
            max-width: 165px;
            font-weight: 700;
            color: #0f172a;
            background-color: #ffffff;
            border-right: 1.5px solid #94a3b8;
        }

        /* Value Column (Column 2) */
        table.case-table td.td-value {
            color: #1e293b;
            background-color: #ffffff;
        }

        /* Sub-Header Row: Exhibits Retrieved */
        table.case-table tr.row-sub-header td {
            background-color: #ffffff;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #0f172a;
            padding: 6px 10px;
            border-top: 1.5px solid #0f172a;
            border-bottom: 1px solid #94a3b8;
        }

        table.case-table tr.row-sub-header td.td-label {
            background-color: #ffffff;
        }

        .case-briefing-text {
            text-align: justify;
            white-space: pre-line;
            line-height: 1.4;
        }

        .exhibits-list {
            padding-left: 0;
            list-style-type: none;
            margin: 0;
        }

        .exhibits-list li {
            margin-bottom: 3px;
            line-height: 1.35;
        }

        .exhibits-list li:last-child {
            margin-bottom: 0;
        }

        @media print {
            body {
                background: transparent !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .action-bar-container {
                display: none !important;
            }

            .a4-page,
            .cover-page {
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
                width: 210mm !important;
                height: 297mm !important;
                max-height: 297mm !important;
                min-height: 297mm !important;
                padding: 14mm 14mm 12mm 14mm !important;
                page-break-after: always !important;
                break-after: page !important;
            }

            .dynamic-dossier-page {
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
                width: 210mm !important;
                height: 297mm !important;
                max-height: 297mm !important;
                min-height: 297mm !important;
                padding: 8mm 10mm 8mm 10mm !important;
                page-break-after: always !important;
                break-after: page !important;
            }

            table.case-table tr.row-case-header td {
                background-color: #ffffff !important;
                color: #0f172a !important;
                -webkit-print-color-adjust: exact !important;
            }

            table.case-table tr.row-sub-header td {
                background-color: #ffffff !important;
                color: #0f172a !important;
                -webkit-print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body class="antialiased">

    <!-- FLOATING TOP ACTION BAR (SCREEN ONLY) -->
    <div class="action-bar-container max-w-[210mm] mx-auto mb-4 px-2 flex items-center justify-end select-none">
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold flex items-center gap-2 transition-all shadow-md hover:shadow-lg active:scale-95 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>Print / Save PDF</span>
            </button>
        </div>
    </div>

    <!-- MAIN DOSSIER SHEETS CONTAINER -->
    <div id="sitrepPrintableDossier" class="printable-document">

        <!-- ============================================================
             PAGE 1: EXECUTIVE COVER PAGE (IDENTICAL TO OTHER REPORTS)
             ============================================================ -->
        <div class="a4-page cover-page page-break relative text-slate-900 select-none">

            <!-- SUBTLE NACOC WATERMARK -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.07] select-none">
                <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[550px] h-[550px] object-contain" onerror="this.style.display='none'" />
            </div>

            <div class="page-content-wrapper relative z-10 flex flex-col justify-between h-full min-h-0 flex-1 w-full">

                <!-- TOP BADGES -->
                <div class="pt-2 flex items-center justify-between text-[9.5px] font-mono-code select-none">
                    <div class="text-left">
                        <p class="font-bold uppercase tracking-wider text-slate-900">REPUBLIC OF GHANA</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold uppercase tracking-wider text-slate-900">CONFIDENTIAL</p>
                        <p class="mt-0.5 text-[10.5px] font-bold uppercase text-slate-800" id="page1HeaderBadge">
                            SITREP COVER &bull; PAGE 1 OF <span class="doc-total-pages">{{ $totalDossierPages }}</span>
                        </p>
                    </div>
                </div>

                <!-- ALL CENTERED CONTENT IN THE MIDDLE OF THE PAGE -->
                <div class="my-auto flex flex-col items-center justify-center text-center w-full max-w-2xl mx-auto py-2">
                    
                    <!-- INSTITUTION DETAILS -->
                    <div>
                        <h1 class="text-2xl font-bold uppercase tracking-wider text-slate-900 font-serif-header">
                            NARCOTICS CONTROL COMMISSION
                        </h1>
                        <p class="mt-1 text-sm font-semibold uppercase tracking-[0.14em] text-slate-700">
                            FORENSIC DEPARTMENT
                        </p>
                        <p class="mt-0.5 text-[11.5px] font-semibold uppercase tracking-[0.12em] text-slate-600 font-mono-code">
                            Crime Scene Support Unit
                        </p>
                    </div>

                    <!-- EMBLEM LOGO IN THE MIDDLE -->
                    <div class="my-6 flex items-center justify-center">
                        <img src="{{ asset('NACOC_LOGO.PNG') }}" alt="NACOC Emblem Logo" class="w-680 h-680 max-w-[780px] max-h-[780px] object-contain drop-shadow-md" onerror="this.style.display='none'" />
                    </div>

                    <!-- CASE FILE & REPORT TITLE -->
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-900">
                            OFFICIAL FORENSIC SITUATION REPORT
                        </p>
                        <h2 class="mt-2 text-2xl font-bold uppercase tracking-wide text-slate-950 font-serif-header">
                            CRIME SCENE SUPPORT UNIT
                        </h2>
                        <p class="mt-1 text-base font-semibold uppercase tracking-[0.14em] text-slate-700">
                            Case Situational Report
                        </p>
                        <div class="mx-auto mt-4 h-[2px] w-20 bg-emerald-700"></div>
                        <p class="mt-5 text-lg font-extrabold uppercase tracking-wider text-emerald-800" id="coverReportTypeTitle">
                            {{ $reportTypeLabel }}
                        </p>
                        <p class="mt-2 text-[14px] font-extrabold uppercase tracking-wider font-times text-emerald-900" id="coverPeriodDisplay">
                            {{ $periodLabel }}
                        </p>
                        <p class="mt-1.5 text-xs font-bold uppercase tracking-wider text-slate-700 font-mono-code">
                            SUSPECT APPREHENSIONS &amp; SEIZED EXHIBITS
                        </p>
                    </div>

                    <!-- CONFIDENTIAL & PROPRIETARY LAW ENFORCEMENT RECORD BOX -->
                    <div class="mt-8 w-full">
                        <div class="p-4 text-center font-mono-code text-[11px] text-slate-700 space-y-1.5">
                            <p class="font-bold uppercase tracking-wider text-slate-900">
                                CONFIDENTIAL &amp; PROPRIETARY LAW ENFORCEMENT RECORD
                            </p>
                            <p class="leading-relaxed">
                                This official situational report contains crime scene dockets, suspect apprehensions, and seized exhibits intelligence.
                            </p>
                        </div>
                    </div>

                    <!-- SIGN-OFF AUTHENTICATION ACCREDITATION PREVIEW -->
                    <div class="mt-6 w-full grid grid-cols-2 gap-8 text-left border-t border-slate-200 pt-3">
                        <div class="text-[10px] font-mono-code">
                            <span class="text-slate-500 uppercase tracking-wider block text-[9px] font-bold">COMPILING UNIT:</span>
                            <span class="font-bold text-slate-800">Crime Scene Support Unit (CSSU)</span>
                            <span class="block text-slate-600 text-[9px]">NACOC Headquarters, Accra</span>
                        </div>
                        <div class="text-right text-[10px] font-mono-code">
                            <span class="text-slate-500 uppercase tracking-wider block text-[9px] font-bold">VALIDATION AUTHORITY:</span>
                            <span class="font-bold text-slate-800">{{ $validationAuthorityName }}</span>
                            <span class="block text-slate-600 text-[9px]">Head, Crime Scene Support Unit</span>
                        </div>
                    </div>

                </div>

                <!-- COVER PAGE FOOTER -->
                <div class="page-footer-block mt-auto pt-3 border-t border-slate-200 flex items-center justify-between text-[9.5px] text-slate-500 font-mono-code select-none footer">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-700 tracking-wider">NACOC / CSS UNIT</span>
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
             PAGE 2+: A4 EXECUTIVE FORENSIC DOSSIER SHEETS
             ============================================================ -->
        @foreach ($dossierPages as $pageIdx => $p)
            @php
                $pageNumber = $pageIdx + 2;
                $isFirstDossierPage = $p['isFirst'];
                $pageCases = $p['cases'];
            @endphp
        <div class="a4-page dynamic-dossier-page page-break relative text-slate-900">

            <!-- SUBTLE NACOC WATERMARK -->
            <div class="dossier-watermark absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.07] select-none">
                <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[800px] h-[800px] object-contain" onerror="this.style.display='none'" />
            </div>

            <div class="page-content-wrapper relative z-10 flex flex-col justify-between h-full min-h-0 flex-1 w-full">

                <!-- DOSSIER HEADER -->
                <div class="page-header-block shrink-0">
                    <div class="pt-1 pb-2 border-b border-slate-300 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-900 font-serif-header">
                                NACOC FORENSIC &bull; Crime Scene Support Unit
                            </span>
                            <span class="ml-2 font-mono-code text-[10.5px] font-semibold text-slate-700">
                                SITREP &bull; {{ date('Y') }}
                            </span>
                        </div>
                        <div class="text-right">
                            <p class="text-[9px] font-bold uppercase tracking-wider text-slate-900">CONFIDENTIAL</p>
                            <p class="mt-0.5 text-[10.5px] font-bold uppercase text-slate-800">
                                PAGE {{ $pageNumber }} OF {{ $totalDossierPages }}
                            </p>
                        </div>
                    </div>

                    @if ($isFirstDossierPage)
                    <!-- REPORT TITLE BLOCK -->
                    <div class="page-title-block mt-2 pb-1.5 text-center border-b border-slate-200">
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-800">
                            FORENSIC DEPARTMENT &bull; CRIME SCENE SUPPORT UNIT
                        </p>
                        <h2 class="mt-0.5 text-lg font-bold uppercase tracking-wide text-slate-950 font-serif-header" id="dossierHeadingText">
                            {{ $reportTypeLabel }}
                        </h2>
                        <p class="mt-0.5 text-[13.5px] font-bold uppercase tracking-wider text-emerald-900 font-times" id="dossierPeriodText">
                            {{ $periodLabel }}
                        </p>
                    </div>
                    @endif
                </div>

                <!-- MAIN DOSSIER FLOW CONTENT -->
                <div class="report-flow-body flex-1 mt-3 min-h-0 flex flex-col justify-start">
                    @forelse ($pageCases as $cItem)
                        <div class="dossier-wrapper">
                            <table class="case-table">
                                <tbody>
                                    <!-- Header Row: CASE ONE -->
                                    <tr class="row-case-header">
                                        <td class="td-label"></td>
                                        <td class="td-value font-mono-code">{{ $cItem['headerTitle'] }}</td>
                                    </tr>

                                    <!-- Date -->
                                    <tr>
                                        <td class="td-label">Date</td>
                                        <td class="td-value font-mono-code font-bold">{{ $cItem['dateFormatted'] }}</td>
                                    </tr>

                                    <!-- Operation Type -->
                                    <tr>
                                        <td class="td-label">Operation Type</td>
                                        <td class="td-value font-bold">{{ $cItem['opName'] }}</td>
                                    </tr>

                                    <!-- Location -->
                                    <tr>
                                        <td class="td-label">Location</td>
                                        <td class="td-value">{{ $cItem['location'] }}</td>
                                    </tr>

                                    <!-- Case Briefing -->
                                    <tr>
                                        <td class="td-label">Case briefing</td>
                                        <td class="td-value case-briefing-text">{{ $cItem['caseBriefing'] }}</td>
                                    </tr>

                                    <!-- Suspects -->
                                    <tr>
                                        <td class="td-label">Suspects</td>
                                        <td class="td-value font-bold">{{ $cItem['suspectsDisplay'] }}</td>
                                    </tr>

                                    <!-- Exhibits Retrieved -->
                                    <tr>
                                        <td class="td-label">Exhibits Retrieved</td>
                                        <td class="td-value">
                                            @if(!empty($cItem['exhibitLines']))
                                                <ul class="exhibits-list font-mono-code" style="font-size: 10pt;">
                                                    @foreach($cItem['exhibitLines'] as $line)
                                                        <li>{{ $line }}</li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                Unspecify
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @empty
                        @if ($totalCount === 0)
                            <div style="text-align: center; padding: 40px; color: #64748b; border: 1px dashed #cbd5e1; border-radius: 8px;">
                                No Crime Scene Support cases recorded for the selected audit parameters.
                            </div>
                        @endif
                    @endforelse

                    @if (!empty($p['hasNotes']) && (!empty($reportNote?->status) || !empty($reportNote?->recommendation) || !empty($reportNote?->conclusion)))
                    <!-- STATUS, RECOMMENDATION, CONCLUSION DIRECTLY UNDER LAST TABLE -->
                    <div class="report-notes-block avoid-break w-full mt-3 mb-2 space-y-3.5 text-black" style="color: #000000; background: transparent;">
                        @if (!empty($reportNote->status))
                        <div class="text-black" style="color: #000000; background: transparent;">
                            <div class="text-[14px] font-black uppercase text-black font-mono-code tracking-wide" style="color: #000000; font-weight: 800; font-size: 11pt;">
                                STATUS:
                            </div>
                            <div class="text-[13px] text-black font-sans leading-relaxed whitespace-pre-line mt-1" style="color: #000000; font-size: 10pt;">{{ $reportNote->status }}</div>
                        </div>
                        @endif

                        @if (!empty($reportNote->recommendation))
                        <div class="text-black" style="color: #000000; background: transparent;">
                            <div class="text-[14px] font-black uppercase text-black font-mono-code tracking-wide" style="color: #000000; font-weight: 800; font-size: 11pt;">
                                RECOMMENDATION:
                            </div>
                            <div class="text-[13px] text-black font-sans leading-relaxed whitespace-pre-line mt-1" style="color: #000000; font-size: 10pt;">{{ $reportNote->recommendation }}</div>
                        </div>
                        @endif

                        @if (!empty($reportNote->conclusion))
                        <div class="text-black" style="color: #000000; background: transparent;">
                            <div class="text-[14px] font-black uppercase text-black font-mono-code tracking-wide" style="color: #000000; font-weight: 800; font-size: 11pt;">
                                CONCLUSION:
                            </div>
                            <div class="text-[13px] text-black font-sans leading-relaxed whitespace-pre-line mt-1" style="color: #000000; font-size: 10pt;">{{ $reportNote->conclusion }}</div>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>

                <!-- SECTION SIGNATURES (ON FINAL DOSSIER PAGE - 2 COLS: UNIT HEAD & FORENSIC DEPT HEAD) -->
                @if (!empty($p['signatures']))
                <div class="section-signatures avoid-break shrink-0 w-full mt-auto pt-2 pb-1.5 border-t-2 border-slate-300">
                    <div class="grid grid-cols-2 gap-12">
                        <!-- LEFT: HEAD, CRIME SCENE SUPPORT UNIT -->
                        <div class="flex flex-col">
                            <p class="text-[9.5px] font-bold uppercase tracking-wider text-slate-700 font-mono-code truncate">HEAD OF CRIME SCENE SUPPORT</p>
                            <div class="h-10 flex items-center">
                                @if(!empty($dbCssHead?->signature))
                                    <img src="{{ $dbCssHead->signature }}" alt="Signature" class="max-h-8 object-contain" onerror="this.style.display='none'">
                                @endif
                            </div>
                            <div class="border-b border-slate-700 pb-0.5"></div>
                            <div class="mt-1">
                                <p class="text-[11.5px] font-extrabold text-slate-900 leading-tight truncate">{{ $cssHeadName }}</p>
                                <p class="text-[9px] text-slate-700 leading-tight">Head, Crime Scene Support Unit</p>
                                <p class="text-[9px] text-slate-600 font-mono-code pt-0.5">Date: <span class="font-bold text-slate-800">{{ $reportGeneratedDate }}</span></p>
                            </div>
                        </div>

                        <!-- RIGHT: HEAD OF FORENSIC DEPARTMENT -->
                        <div class="flex flex-col">
                            <p class="text-[9.5px] font-bold uppercase tracking-wider text-slate-700 font-mono-code truncate">HEAD OF FORENSIC DEPARTMENT</p>
                            <div class="h-10 flex items-center">
                                @if(!empty($dbDeptHead?->signature))
                                    <img src="{{ $dbDeptHead->signature }}" alt="Signature" class="max-h-8 object-contain" onerror="this.style.display='none'">
                                @endif
                            </div>
                            <div class="border-b border-slate-700 pb-0.5"></div>
                            <div class="mt-1">
                                <p class="text-[11.5px] font-extrabold text-slate-900 leading-tight truncate">{{ $deptHeadName }}</p>
                                <p class="text-[9px] text-slate-700 leading-tight">Director, Forensic Science Directorate</p>
                                <p class="text-[9px] text-slate-600 font-mono-code pt-0.5">Date: <span class="font-bold text-slate-800">{{ $reportGeneratedDate }}</span></p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- DOSSIER FOOTER -->
                <div class="page-footer-block {{ empty($p['signatures']) ? 'mt-auto' : '' }} pt-2 border-t border-slate-200 flex items-center justify-between text-[10.5px] text-slate-500 font-mono-code select-none footer">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-700 tracking-wider">NACOC / CRIME SCENE SUPPORT UNIT</span>
                        <span>&bull;</span>
                        <span>Official Forensic Dossier</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span>STRICTLY CONFIDENTIAL</span>
                        <span>&bull;</span>
                        <span class="font-bold text-slate-800">Page {{ $pageNumber }} of {{ $totalDossierPages }}</span>
                    </div>
                </div>

            </div>
        </div>
        @endforeach

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('autoprint') === '1') {
                setTimeout(() => window.print(), 500);
            }
        });
    </script>
</body>
</html>
