@php
    $authUser = auth()->user();

    // Data collections
    $drugList   = collect($drugExhibits ?? []);
    $assetList  = collect($assetExhibits ?? []);
    $moneyList  = collect($moneyExhibits ?? []);

    $drugCount  = $drugList->count();
    $assetCount = $assetList->count();
    $moneyCount = $moneyList->count();

    // Signatories
    $evidenceOfficerName = $dbEvidenceHead ? $dbEvidenceHead->fullname : ($authUser ? $authUser->fullname : 'N/A');
    $deptHeadName = $dbDeptHead ? $dbDeptHead->fullname : 'N/A';
    $intelDirectorName = 'Name: ____________________';
    $reportGeneratedDate = now()->format('d F Y');

    // Report Period Labeling
    $rawPeriod = strtolower(trim((string)($period ?? 'general')));
    $hasCustomRange = !empty($fromDate) || !empty($toDate);

    $periodLabel = match(true) {
        $hasCustomRange => 'Period: ' . ($fromDate ? date('d F Y', strtotime($fromDate)) : 'Start') . ' – ' . ($toDate ? date('d F Y', strtotime($toDate)) : 'Present'),
        in_array($rawPeriod, ['weekly', 'week']) => 'Period: ' . now()->subDays(7)->format('d F Y') . ' – ' . now()->format('d F Y'),
        in_array($rawPeriod, ['monthly', 'month']) => 'Period: ' . now()->subDays(30)->format('d F Y') . ' – ' . now()->format('d F Y'),
        in_array($rawPeriod, ['annual', 'annually', 'year', 'yearly']) => 'Period: ' . now()->subYear()->format('d F Y') . ' – ' . now()->format('d F Y'),
        default => 'PERIOD: ALL REGISTERED EXHIBITS (CUMULATIVE)'
    };

    $reportTypeLabel = match(true) {
        in_array($rawPeriod, ['weekly', 'week']) => 'WEEKLY DRUG & SEIZED PROPERTY REPORT',
        in_array($rawPeriod, ['monthly', 'month']) => 'MONTHLY DRUG & SEIZED PROPERTY REPORT',
        in_array($rawPeriod, ['annual', 'annually', 'year', 'yearly']) => 'ANNUAL DRUG & SEIZED PROPERTY REPORT',
        default => $hasCustomRange ? 'DATE RANGE & SEIZED PROPERTY REPORT' : 'GENERAL SITUATIONAL REPORT'
    };

    // Height Budgeting for A4 Portrait (in millimeters) matching lab_sitrep_pdf
    $firstPageBudget        = 215.0; // mm available on first body page
    $continuationPageBudget = 240.0; // mm available on continuation body pages

    $sec01HeaderH       = 22.0;  // Section 01 badge + title + counter + thead
    $sec01ContinuationH = 19.0;  // Continuation title + thead
    $sec02HeaderH       = 26.0;  // Section 02 badge + title + intro + thead
    $sec02ContinuationH = 18.0;  // Continuation title + thead
    $sec03HeaderH       = 26.0;  // Section 03 badge + title + intro + thead
    $sec03ContinuationH = 18.0;  // Continuation title + thead
    $secEmptyRowH       = 11.0;  // Empty section message row
    $sectionGapH        = 5.0;   // Vertical space between consecutive sections
    $signaturesH        = 42.0;  // 3 official physical signatures block

    // Row height estimators
    $calcDrugRowH = function($item) {
        $remarks = (string)($item->exhibit_remark ?: 'N/A');
        $suspect = 'N/A';
        if ($item->singleSuspectCase) {
            $suspect = $item->singleSuspectCase->suspect_name;
        } elseif ($item->suspect) {
            $suspect = $item->suspect->suspect_name;
        } elseif ($item->multipleSuspectCase && $item->multipleSuspectCase->suspects->isNotEmpty()) {
            $suspect = $item->multipleSuspectCase->suspects->pluck('suspect_name')->implode(', ');
        }
        $exType = (string)($item->commodity_name ?: ($item->drug_classification ?: ($item->exhibit_category ?: 'N/A')));

        $remLines  = max(1, (int)ceil(mb_strlen($remarks) / 25));
        $suspLines = max(1, (int)ceil(mb_strlen($suspect) / 16));
        $exLines   = max(1, (int)ceil(mb_strlen($exType) / 16));
        $maxLines  = max($remLines, $suspLines, $exLines, 1);
        return 7.0 + ($maxLines * 4.2);
    };

    $calcAssetRowH = function($item) {
        $suspect = 'N/A';
        if ($item->singleSuspectCase) {
            $suspect = $item->singleSuspectCase->suspect_name;
        } elseif ($item->suspect) {
            $suspect = $item->suspect->suspect_name;
        } elseif ($item->multipleSuspectCase && $item->multipleSuspectCase->suspects->isNotEmpty()) {
            $suspect = $item->multipleSuspectCase->suspects->pluck('suspect_name')->implode(', ');
        }
        $name    = (string)($item->commodity_name ?: ($item->exhibit_category ?: 'N/A'));
        $chassis = (string)($item->veh_vin ?: ($item->wpn_serial_no ?: ($item->dev_imei1 ?: 'N/A')));
        $reg     = (string)($item->veh_reg_no ?: 'N/A');
        
        $suspLines    = max(1, (int)ceil(mb_strlen($suspect) / 16));
        $nameLines    = max(1, (int)ceil(mb_strlen($name) / 18));
        $chassisLines = max(1, (int)ceil(mb_strlen($chassis) / 14));
        $regLines     = max(1, (int)ceil(mb_strlen($reg) / 14));
        $maxLines     = max($suspLines, $nameLines, $chassisLines, $regLines, 1);
        return 7.0 + ($maxLines * 4.2);
    };

    $calcMoneyRowH = function($item) {
        $desc   = (string)($item->commodity_name ?: '');
        $source = (string)($item->source_of_exhibits ?: ($item->seizure_location ?: 'N/A'));
        $suspect = 'N/A';
        if ($item->singleSuspectCase) {
            $suspect = $item->singleSuspectCase->suspect_name;
        } elseif ($item->suspect) {
            $suspect = $item->suspect->suspect_name;
        } elseif ($item->multipleSuspectCase && $item->multipleSuspectCase->suspects->isNotEmpty()) {
            $suspect = $item->multipleSuspectCase->suspects->pluck('suspect_name')->implode(', ');
        }
        
        $descLines   = max(1, (int)ceil(mb_strlen($desc) / 25));
        $sourceLines = max(1, (int)ceil(mb_strlen($source) / 18));
        $suspLines   = max(1, (int)ceil(mb_strlen($suspect) / 16));
        $maxLines    = max($descLines, $sourceLines, $suspLines, 1);
        return 7.0 + ($maxLines * 4.2);
    };

    // Partition collections across A4 pages
    $remainingDrugs  = $drugList->values();
    $remainingAssets = $assetList->values();
    $remainingMoney  = $moneyList->values();

    $sec01Started     = false;
    $sec01Finished    = false;
    $sec02Started     = false;
    $sec02Finished    = false;
    $sec03Started     = false;
    $sec03Finished    = false;
    $signaturesPlaced = false;

    $dossierPages = [];
    $isFirst = true;

    while (!$signaturesPlaced) {
        $pageLimit   = $isFirst ? $firstPageBudget : $continuationPageBudget;
        $currentUsed = 0.0;

        $pageDrugs       = collect();
        $isSec01Cont     = false;

        $pageAssets      = collect();
        $isSec02Cont     = false;
        $showSec02Intro  = false;
        $showAssetEmpty  = false;

        $pageMoney       = collect();
        $isSec03Cont     = false;
        $showSec03Intro  = false;
        $showMoneyEmpty  = false;

        $pageSignatures      = false;
        $hasAnyContentOnPage = false;

        // 1. Pack Section 01: Drug Exhibits
        if (!$sec01Finished) {
            if ($drugList->isEmpty()) {
                $needed = $sec01HeaderH + $secEmptyRowH;
                if (($pageLimit - $currentUsed >= $needed) || !$hasAnyContentOnPage) {
                    $currentUsed += $needed;
                    $sec01Finished = true;
                    $hasAnyContentOnPage = true;
                }
            } else {
                $isSec01Cont = $sec01Started;
                $headerH     = $isSec01Cont ? $sec01ContinuationH : $sec01HeaderH;
                $gap         = $hasAnyContentOnPage ? $sectionGapH : 0.0;
                $firstRowH   = $remainingDrugs->isNotEmpty() ? $calcDrugRowH($remainingDrugs->first()) : 11.5;
                $minNeeded   = $gap + $headerH + $firstRowH;

                if (($pageLimit - $currentUsed >= $minNeeded) || !$hasAnyContentOnPage) {
                    $currentUsed += ($gap + $headerH);
                    $sec01Started = true;
                    $hasAnyContentOnPage = true;

                    while ($remainingDrugs->isNotEmpty()) {
                        $nextItem = $remainingDrugs->first();
                        $rowH = $calcDrugRowH($nextItem);

                        if (($currentUsed + $rowH > $pageLimit) && $pageDrugs->isNotEmpty()) {
                            break;
                        }

                        $pageDrugs->push($remainingDrugs->shift());
                        $currentUsed += $rowH;
                    }

                    if ($remainingDrugs->isEmpty()) {
                        $sec01Finished = true;
                    }
                }
            }
        }

        // 2. Pack Section 02: Physical Assets
        if ($sec01Finished && !$sec02Finished) {
            if ($assetList->isEmpty()) {
                $gap = $hasAnyContentOnPage ? $sectionGapH : 0.0;
                $needed = $gap + $sec02HeaderH + $secEmptyRowH;
                if (($pageLimit - $currentUsed >= $needed) || !$hasAnyContentOnPage) {
                    $currentUsed += $needed;
                    $showAssetEmpty = true;
                    $showSec02Intro = true;
                    $sec02Started = true;
                    $sec02Finished = true;
                    $hasAnyContentOnPage = true;
                }
            } else {
                $isSec02Cont = $sec02Started;
                $headerH     = $isSec02Cont ? $sec02ContinuationH : $sec02HeaderH;
                $gap         = $hasAnyContentOnPage ? $sectionGapH : 0.0;
                $firstRowH   = $remainingAssets->isNotEmpty() ? $calcAssetRowH($remainingAssets->first()) : 10.5;
                $minNeeded   = $gap + $headerH + $firstRowH;

                if (($pageLimit - $currentUsed >= $minNeeded) || !$hasAnyContentOnPage) {
                    $currentUsed += ($gap + $headerH);
                    $showSec02Intro = !$sec02Started;
                    $sec02Started = true;
                    $hasAnyContentOnPage = true;

                    while ($remainingAssets->isNotEmpty()) {
                        $nextItem = $remainingAssets->first();
                        $rowH = $calcAssetRowH($nextItem);

                        if (($currentUsed + $rowH > $pageLimit) && $pageAssets->isNotEmpty()) {
                            break;
                        }

                        $pageAssets->push($remainingAssets->shift());
                        $currentUsed += $rowH;
                    }

                    if ($remainingAssets->isEmpty()) {
                        $sec02Finished = true;
                    }
                }
            }
        }

        // 3. Pack Section 03: Money Received
        if ($sec01Finished && $sec02Finished && !$sec03Finished) {
            if ($moneyList->isEmpty()) {
                $gap = $hasAnyContentOnPage ? $sectionGapH : 0.0;
                $needed = $gap + $sec03HeaderH + $secEmptyRowH;
                if (($pageLimit - $currentUsed >= $needed) || !$hasAnyContentOnPage) {
                    $currentUsed += $needed;
                    $showMoneyEmpty = true;
                    $showSec03Intro = true;
                    $sec03Started = true;
                    $sec03Finished = true;
                    $hasAnyContentOnPage = true;
                }
            } else {
                $isSec03Cont = $sec03Started;
                $headerH     = $isSec03Cont ? $sec03ContinuationH : $sec03HeaderH;
                $gap         = $hasAnyContentOnPage ? $sectionGapH : 0.0;
                $firstRowH   = $remainingMoney->isNotEmpty() ? $calcMoneyRowH($remainingMoney->first()) : 10.5;
                $minNeeded   = $gap + $headerH + $firstRowH;

                if (($pageLimit - $currentUsed >= $minNeeded) || !$hasAnyContentOnPage) {
                    $currentUsed += ($gap + $headerH);
                    $showSec03Intro = !$sec03Started;
                    $sec03Started = true;
                    $hasAnyContentOnPage = true;

                    while ($remainingMoney->isNotEmpty()) {
                        $nextItem = $remainingMoney->first();
                        $rowH = $calcMoneyRowH($nextItem);

                        if (($currentUsed + $rowH > $pageLimit) && $pageMoney->isNotEmpty()) {
                            break;
                        }

                        $pageMoney->push($remainingMoney->shift());
                        $currentUsed += $rowH;
                    }

                    if ($remainingMoney->isEmpty()) {
                        $sec03Finished = true;
                    }
                }
            }
        }

        // 4. Pack Signatures if all sections are completed
        if ($sec01Finished && $sec02Finished && $sec03Finished) {
            $gap = $hasAnyContentOnPage ? $sectionGapH : 0.0;
            $needed = $gap + $signaturesH;

            if (($pageLimit - $currentUsed >= $needed) || !$hasAnyContentOnPage) {
                $currentUsed += $needed;
                $pageSignatures   = true;
                $signaturesPlaced = true;
            }
        }

        $dossierPages[] = [
            'isFirst'              => $isFirst,
            'isLast'               => false,
            'drugs'                => $pageDrugs,
            'isSection01Continued' => $isSec01Cont,
            'assets'               => $pageAssets,
            'isSection02Continued' => $isSec02Cont,
            'showSection02Intro'   => $showSec02Intro,
            'showAssetEmpty'       => $showAssetEmpty,
            'money'                => $pageMoney,
            'isSection03Continued' => $isSec03Cont,
            'showSection03Intro'   => $showSec03Intro,
            'showMoneyEmpty'       => $showMoneyEmpty,
            'signatures'           => $pageSignatures,
        ];

        $isFirst = false;

        // Safety fallback limit
        if (count($dossierPages) > 50) {
            $signaturesPlaced = true;
            break;
        }
    }

    // Ensure signatures are placed on the final page
    if (!empty($dossierPages)) {
        $lastIdx = count($dossierPages) - 1;
        for ($i = 0; $i < $lastIdx; $i++) {
            $dossierPages[$i]['isLast'] = false;
            $dossierPages[$i]['signatures'] = false;
        }
        $dossierPages[$lastIdx]['isLast'] = true;
        $dossierPages[$lastIdx]['signatures'] = true;
    }

    $totalDossierPages = 1 + count($dossierPages);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NACOC Evidence Management Unit &bull; Forensic Seized Property Report</title>
    
    <!-- Scripts & Styles -->
    <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700;800&family=Times+New+Roman&display=swap" rel="stylesheet">
    
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

        /* Cover Page: Exactly 1 static A4 sheet matching lab_sitrep_pdf */
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

        /* Dynamic Dossier Pages */
        .dynamic-dossier-page {
            width: 210mm;
            height: 297mm;
            max-height: 297mm;
            min-height: 297mm;
            padding: 10mm 12mm 10mm 12mm;
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

        /* Full page height stretching inner wrapper */
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
        }

        .section-signatures {
            flex-shrink: 0;
            width: 100%;
            margin-top: auto;
            margin-bottom: 6px;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .avoid-break {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
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

            .a4-page {
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
                width: 210mm !important;
                height: 297mm !important;
                max-height: 297mm !important;
                min-height: 297mm !important;
                page-break-after: always !important;
                break-after: page !important;
            }

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
                padding: 10mm 12mm 10mm 12mm !important;
                page-break-after: always !important;
                break-after: page !important;
            }

            tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
    </style>
</head>
<body class="antialiased">

    <!-- FLOATING TOP ACTION BAR (SCREEN ONLY) -->
    <div class="action-bar-container max-w-[210mm] mx-auto mb-4 px-2 flex items-center justify-between select-none">
        <div class="flex items-center gap-3">
            <a href="{{ url('/Evidence_Management/report') }}" class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-colors border border-slate-700 shadow-sm no-underline">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Back to Reports</span>
            </a>
        </div>

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
             PAGE 1: EXACT EXECUTIVE COVER PAGE (IDENTICAL TO LAB SITREP)
             ============================================================ -->
        <div class="a4-page cover-page page-break relative text-slate-900 select-none">

            <!-- SUBTLE NACOC WATERMARK -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.025] select-none">
                <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[550px] h-[550px] object-contain filter grayscale" />
            </div>

            <div class="page-content-wrapper relative z-10 flex flex-col justify-between h-full min-h-0 flex-1 w-full">

                <!-- TOP BADGES -->
                <div class="pt-2 flex items-center justify-between text-[8px] font-mono-code select-none">
                    <div class="text-left">
                        <p class="font-bold uppercase tracking-wider text-slate-900">REPUBLIC OF GHANA</p>
                        <p class="mt-0.5 text-slate-700">ISO/IEC 17025 ACCREDITED FORENSIC LABORATORY</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold uppercase tracking-wider text-slate-900">CONFIDENTIAL</p>
                        <p class="mt-0.5 text-[9px] font-bold uppercase text-slate-800" id="page1HeaderBadge">
                            SITREP COVER &bull; PAGE 1 OF <span class="doc-total-pages">{{ $totalDossierPages }}</span>
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
                            EVIDENCE MANAGEMENT UNIT (EMU)
                        </p>
                    </div>

                    <!-- INCREASED SIZE EMBLEM LOGO IN THE MIDDLE (EXACT MATCH) -->
                    <div class="my-6 flex items-center justify-center">
                        <img src="{{ asset('NACOC_LOGO.PNG') }}" alt="NACOC Emblem Logo" class="w-680 h-680 max-w-[780px] max-h-[780px] object-contain drop-shadow-md" />
                    </div>

                    <!-- CASE FILE & REPORT TITLE -->
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.25em] text-slate-900">
                            OFFICIAL FORENSIC SITUATION REPORT
                        </p>
                        <h2 class="mt-2 text-2xl font-bold uppercase tracking-wide text-slate-950 font-serif-header">
                            EVIDENCE MANAGEMENT UNIT
                        </h2>
                        <p class="mt-1 text-sm font-semibold uppercase tracking-[0.14em] text-slate-700">
                            Preliminary Forensic Situational Report
                        </p>
                        <div class="mx-auto mt-4 h-[2px] w-20 bg-emerald-700"></div>
                        <p class="mt-5 text-base font-extrabold uppercase tracking-wider text-emerald-800" id="coverReportTypeTitle">
                            {{ $reportTypeLabel }}
                        </p>
                        <p class="mt-2 text-[13px] font-extrabold uppercase tracking-wider font-times text-emerald-900" id="coverPeriodDisplay">
                            {{ $periodLabel }}
                        </p>
                        <p class="mt-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-700 font-mono-code">
                            ALL DRUGS &amp; NON-DRUGS RELATED EXHIBIT 
                        </p>
                    </div>

                    <!-- CONFIDENTIAL & PROPRIETARY LAW ENFORCEMENT RECORD BOX -->
                    <div class="mt-8 w-full">
                        <div class="p-4 text-center font-mono-code text-[10px] text-slate-700 space-y-1.5">
                            <p class="font-bold uppercase tracking-wider text-slate-900">
                                CONFIDENTIAL &amp; PROPRIETARY LAW ENFORCEMENT RECORD
                            </p>
                            <p class="leading-relaxed">
                                This official situational report contains drugs, non-drug assets, and chain-of-custody intelligence.
                            </p>
                        </div>
                    </div>

                    <!-- SIGN-OFF AUTHENTICATION ACCREDITATION PREVIEW -->
                    <div class="mt-6 w-full grid grid-cols-2 gap-8 text-left border-t border-slate-200 pt-3">
                        <div class="text-[9px] font-mono-code">
                            <span class="text-slate-500 uppercase tracking-wider block text-[8px] font-bold">COMPILING UNIT:</span>
                            <span class="font-bold text-slate-800">Evidence Management Unit (EMU)</span>
                            <span class="block text-slate-600 text-[8px]">NACOC Headquarters, Accra</span>
                        </div>
                        <div class="text-right text-[9px] font-mono-code">
                            <span class="text-slate-500 uppercase tracking-wider block text-[8px] font-bold">VALIDATION AUTHORITY:</span>
                            <span class="font-bold text-slate-800">{{ $evidenceOfficerName }}</span>
                            <span class="block text-slate-600 text-[8px]">Head of Evidence Management Unit</span>
                        </div>
                    </div>

                </div>

                <!-- COVER PAGE FOOTER -->
                <div class="page-footer-block mt-auto pt-3 border-t border-slate-200 flex items-center justify-between text-[8px] text-slate-500 font-mono-code select-none footer">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-700 tracking-wider">NACOC / EMU UNIT</span>
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
             PAGE 2+: EXECUTIVE FORENSIC DOSSIER & LOGS
             ============================================================ -->
        @foreach ($dossierPages as $pageIdx => $p)
            @php
                $pageNumber = $pageIdx + 2;
                $isFirstDossierPage = $p['isFirst'];
                $isLastDossierPage = $p['isLast'];

                $drugChunk  = $p['drugs'];
                $assetChunk = $p['assets'];
                $moneyChunk = $p['money'];
            @endphp
        <div class="a4-page dynamic-dossier-page page-break relative text-slate-900">

            <!-- SUBTLE NACOC WATERMARK (FOR SCREEN PREVIEW) -->
            <div class="dossier-watermark absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.025] select-none">
                <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[900px] h-[900px] object-contain filter grayscale" />
            </div>

            <div class="page-content-wrapper relative z-10 flex flex-col justify-between h-full min-h-0 flex-1 w-full">

                <!-- DOSSIER HEADER -->
                <div class="page-header-block shrink-0">
                    <div class="pt-1 pb-2 border-b border-slate-300 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-900 font-serif-header">
                                NACOC FORENSIC &bull; EVIDENCE MANAGEMENT UNIT
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
                            FORENSIC DEPARTMENT &bull; EVIDENCE MANAGEMENT UNIT
                        </p>
                        <h2 class="mt-0.5 text-base font-bold uppercase tracking-wide text-slate-950 font-serif-header" id="dossierHeadingText">
                            {{ $reportTypeLabel }}
                        </h2>
                        <p class="mt-0.5 text-xs font-bold uppercase tracking-wider text-emerald-900 font-times" id="dossierPeriodText">
                            {{ $periodLabel }}
                        </p>
                    </div>

                    <!-- OPERATIONAL INTRODUCTORY PARAGRAPH
                    <div class="page-intro-block mt-2 text-[11px] text-slate-800 leading-relaxed font-sans">
                        <p class="font-medium">
                            During the reporting period, the Evidence Management Unit continued to provide essential preliminary forensic examination and drug testing for the underlisted cases;
                        </p>
                    </div> -->
                    @endif
                </div>

                <!-- MAIN DOSSIER FLOW CONTENT -->
                <div class="report-flow-body flex-1 mt-3 min-h-0 flex flex-col justify-start">
                    <div class="flow-sections-container space-y-4">

                    <!-- ========================================================
                         TABLE 1: ALL DRUG EXHIBITS
                         ======================================================== -->
                    @if ($drugChunk->isNotEmpty() || ($isFirstDossierPage && $drugCount === 0))
                    <div class="section-01">
                        <div class="section-header-block flex items-center justify-between pb-1 mt-2 mb-2 border-b-2 border-emerald-700">
                            <div class="flex items-center gap-1.5">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-950 font-serif-header">
                                    Drug exhibit Log{{ !empty($p['isSection01Continued']) ? ' (Continued)' : '' }}
                                </h3>
                            </div>
                            <span class="text-[8px] font-mono-code text-slate-600 font-bold uppercase">
                                REGISTERED SEIZURE &bull; <span>{{ $drugCount }}</span> ENTRIES
                            </span>
                        </div>

                        <div class="table-responsive-wrapper overflow-x-auto border border-slate-300 rounded-xl shadow-xs bg-white">
                            <table class="w-full table-fixed text-left border-collapse text-[10.5px]">
                                <thead>
                                    <tr class="bg-slate-100/90 border-b border-slate-300 text-slate-900 font-bold uppercase tracking-wider font-mono-code text-[9.5px]">
                                        <th class="py-2.5 px-2 w-[12%] border-r border-slate-200">Date Of Seizure</th>
                                        <th class="py-2.5 px-2 w-[15%] border-r border-slate-200">Source of Exhibits</th>
                                        <th class="py-2.5 px-2.5 w-[18%] border-r border-slate-200">Suspect(s)</th>
                                        <th class="py-2.5 px-2 w-[18%] border-r border-slate-200">Exhibit Type</th>
                                        <th class="py-2.5 px-1.5 text-center w-[10%] border-r border-slate-200">Quantity</th>
                                        <th class="py-2.5 px-1.5 text-center w-[10%] border-r border-slate-200">Weight</th>
                                        <th class="py-2.5 px-2.5 w-[17%]">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 text-slate-800">
                                    @forelse($drugChunk as $index => $item)
                                        @php
                                            $isEven = $index % 2 === 0;
                                            $suspectDisplay = 'N/A';
                                            if ($item->singleSuspectCase) {
                                                $suspectDisplay = $item->singleSuspectCase->suspect_name;
                                            } elseif ($item->suspect) {
                                                $suspectDisplay = $item->suspect->suspect_name;
                                            } elseif ($item->multipleSuspectCase && $item->multipleSuspectCase->suspects->isNotEmpty()) {
                                                $suspectDisplay = $item->multipleSuspectCase->suspects->pluck('suspect_name')->implode(', ');
                                            }

                                            $qtyDisplay = $item->seizure_quantity ? ($item->seizure_quantity . ($item->seizure_packaging ? ' ' . $item->seizure_packaging : '')) : ($item->packaging_type ?: 'N/A');
                                            $weightDisplay = $item->seizure_weight ? ($item->seizure_weight . ($item->seizure_unit ? ' ' . $item->seizure_unit : '')) : 'N/A';
                                            $remarksDisplay = $item->exhibit_remark ?: 'N/A';
                                            $sourceDisplay = $item->source_of_exhibits ?: ($item->seizure_location ?: 'N/A');
                                            $exTypeDisplay = $item->commodity_name ?: ($item->drug_classification ?: ($item->exhibit_category ?: 'N/A'));
                                            $dateDisplay = $item->date_seized ? date('d F Y', strtotime($item->date_seized)) : ($item->created_at ? $item->created_at->format('d F Y') : 'N/A');
                                        @endphp
                                        <tr class="hover:bg-slate-50/80 transition-colors {{ $isEven ? 'bg-white' : 'bg-slate-50/40' }}">
                                            <td class="py-2 px-2 font-mono-code text-[10px] border-r border-slate-200 align-top">
                                                {{ $dateDisplay }}
                                            </td>
                                            <td class="py-2 px-2 font-bold uppercase text-slate-900 text-[10.5px] border-r border-slate-200 align-top leading-tight">
                                                {{ $sourceDisplay }}
                                            </td>
                                            <td class="py-2 px-2.5 font-bold text-slate-950 text-[10.5px] border-r border-slate-200 align-top leading-tight">
                                                {{ $suspectDisplay }}
                                            </td>
                                            <td class="py-2 px-2 font-bold text-slate-900 text-[10.5px] border-r border-slate-200 align-top leading-tight">
                                                {{ $exTypeDisplay }}
                                            </td>
                                            <td class="py-2 px-1.5 text-center font-mono-code text-[10px] border-r border-slate-200 align-top">
                                                {{ $qtyDisplay }}
                                            </td>
                                            <td class="py-2 px-1.5 text-center font-mono-code text-[10px] border-r border-slate-200 align-top">
                                                {{ $weightDisplay }}
                                            </td>
                                            <td class="py-2 px-2.5 text-[10px] text-slate-700 align-top leading-tight">
                                                {{ $remarksDisplay }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="py-3 px-3 text-center text-slate-500 font-mono-code text-[10px]">
                                                No drug exhibits recorded during the reporting period.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    <!-- ========================================================
                         TABLE 2: PHYSICAL ASSETS (NON-DRUG EXHIBITS)
                         ======================================================== -->
                    @if ($assetChunk->isNotEmpty() || !empty($p['showAssetEmpty']))
                    <div class="section-02 mt-4">
                        <div class="section-header-block flex items-center justify-between pb-1 mt-2 mb-2 border-b-2 border-blue-700">
                            <div class="flex items-center gap-1.5">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-950 font-serif-header">
                                    Physical Assets &amp; Non-Drug Seized Property Log{{ !empty($p['isSection02Continued']) ? ' (Continued)' : '' }}
                                </h3>
                            </div>
                            <span class="text-[8px] font-mono-code text-slate-600 font-bold uppercase">
                                REGISTERED ASSETS &bull; <span>{{ $assetCount }}</span> ENTRIES
                            </span>
                        </div>

                        @if (!empty($p['showSection02Intro']))
                        <div class="text-[10px] text-slate-600 mb-2 italic font-sans">
                            Details of seized Assets within the Period are provided below:
                        </div>
                        @endif

                        <div class="table-responsive-wrapper overflow-x-auto border border-slate-300 rounded-xl shadow-xs bg-white">
                            <table class="w-full table-fixed text-left border-collapse text-[10.5px]">
                                <thead>
                                    <tr class="bg-slate-100/90 border-b border-slate-300 text-slate-900 font-bold uppercase tracking-wider font-mono-code text-[9.5px]">
                                        <th class="py-2.5 px-2 w-[18%] border-r border-slate-200">Date Of Seizure</th>
                                        <th class="py-2.5 px-2.5 w-[24%] border-r border-slate-200">Suspect(s)</th>
                                        <th class="py-2.5 px-2.5 w-[24%] border-r border-slate-200">Asset / Exhibit Type</th>
                                        <th class="py-2.5 px-2 w-[17%] border-r border-slate-200">Chassis Number</th>
                                        <th class="py-2.5 px-2 w-[17%]">Registration Number</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 text-slate-800">
                                    @forelse($assetChunk as $index => $item)
                                        @php
                                            $isEven = $index % 2 === 0;
                                            $suspectDisplay = 'N/A';
                                            if ($item->singleSuspectCase) {
                                                $suspectDisplay = $item->singleSuspectCase->suspect_name;
                                            } elseif ($item->suspect) {
                                                $suspectDisplay = $item->suspect->suspect_name;
                                            } elseif ($item->multipleSuspectCase && $item->multipleSuspectCase->suspects->isNotEmpty()) {
                                                $suspectDisplay = $item->multipleSuspectCase->suspects->pluck('suspect_name')->implode(', ');
                                            }

                                            $chassisDisplay = $item->veh_vin ?: ($item->wpn_serial_no ?: ($item->dev_imei1 ?: 'N/A'));
                                            $regDisplay = $item->veh_reg_no ?: 'N/A';
                                            $assetTypeDisplay = $item->commodity_name ?: ($item->exhibit_category ?: 'N/A');
                                            $dateDisplay = $item->date_seized ? date('d F Y', strtotime($item->date_seized)) : ($item->created_at ? $item->created_at->format('d F Y') : 'N/A');
                                        @endphp
                                        <tr class="hover:bg-slate-50/80 transition-colors {{ $isEven ? 'bg-white' : 'bg-slate-50/40' }}">
                                            <td class="py-2 px-2 font-mono-code text-[10px] border-r border-slate-200 align-top">
                                                {{ $dateDisplay }}
                                            </td>
                                            <td class="py-2 px-2.5 font-bold text-slate-950 text-[10.5px] border-r border-slate-200 align-top leading-tight">
                                                {{ $suspectDisplay }}
                                            </td>
                                            <td class="py-2 px-2.5 font-bold text-slate-900 text-[10.5px] border-r border-slate-200 align-top leading-tight">
                                                {{ $assetTypeDisplay }}
                                            </td>
                                            <td class="py-2 px-2 font-mono-code text-[10px] text-slate-800 border-r border-slate-200 align-top">
                                                {{ $chassisDisplay }}
                                            </td>
                                            <td class="py-2 px-2 font-mono-code font-bold text-slate-900 text-[10.5px] align-top">
                                                {{ $regDisplay }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="py-3 px-3 text-center text-slate-500 font-mono-code text-[10px]">
                                                No physical non-drug assets lodged during the reporting period.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    <!-- ========================================================
                         TABLE 3: MONEY RECEIVED
                         ======================================================== -->
                    @if ($moneyChunk->isNotEmpty() || !empty($p['showMoneyEmpty']))
                    <div class="section-03 mt-4">
                        <div class="section-header-block flex items-center justify-between pb-1 mt-2 mb-2 border-b-2 border-amber-600">
                            <div class="flex items-center gap-1.5">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-950 font-serif-header">
                                    Seized Currency &amp; Money Received Register{{ !empty($p['isSection03Continued']) ? ' (Continued)' : '' }}
                                </h3>
                            </div>
                            <span class="text-[8px] font-mono-code text-slate-600 font-bold uppercase">
                                MONETARY FUNDS &bull; <span>{{ $moneyCount }}</span> ENTRIES
                            </span>
                        </div>

                        @if (!empty($p['showSection03Intro']))
                        <div class="text-[10px] text-slate-600 mb-2 italic font-sans">
                            The details of seized currencies, monetary funds, and cash notes received and lodged in the Central Vault repository:
                        </div>
                        @endif

                        <div class="table-responsive-wrapper overflow-x-auto border border-slate-300 rounded-xl shadow-xs bg-white">
                            <table class="w-full table-fixed text-left border-collapse text-[10.5px]">
                                <thead>
                                    <tr class="bg-slate-100/90 border-b border-slate-300 text-slate-900 font-bold uppercase tracking-wider font-mono-code text-[9.5px]">
                                        <th class="py-2.5 px-2 w-[20%] border-r border-slate-200">Date</th>
                                        <th class="py-2.5 px-2.5 w-[32%] border-r border-slate-200">Suspect</th>
                                        <th class="py-2.5 px-2 text-right w-[22%] border-r border-slate-200">Amount</th>
                                        <th class="py-2.5 px-2.5 w-[26%]">Location</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 text-slate-800">
                                    @forelse($moneyChunk as $index => $item)
                                        @php
                                            $isEven = $index % 2 === 0;
                                            $suspectDisplay = 'N/A';
                                            if ($item->singleSuspectCase) {
                                                $suspectDisplay = $item->singleSuspectCase->suspect_name;
                                            } elseif ($item->suspect) {
                                                $suspectDisplay = $item->suspect->suspect_name;
                                            } elseif ($item->multipleSuspectCase && $item->multipleSuspectCase->suspects->isNotEmpty()) {
                                                $suspectDisplay = $item->multipleSuspectCase->suspects->pluck('suspect_name')->implode(', ');
                                            }

                                            $val = (float)($item->seizure_value ?? 0);
                                            $locationDisplay = $item->seizure_location ?: ($item->source_of_exhibits ?: 'N/A');
                                            $dateDisplay = $item->date_seized ? date('d F Y', strtotime($item->date_seized)) : ($item->created_at ? $item->created_at->format('d F Y') : 'N/A');
                                        @endphp
                                        <tr class="hover:bg-slate-50/80 transition-colors {{ $isEven ? 'bg-white' : 'bg-slate-50/40' }}">
                                            <td class="py-2 px-2 font-mono-code text-[10px] border-r border-slate-200 align-top">
                                                {{ $dateDisplay }}
                                            </td>
                                            <td class="py-2 px-2.5 font-bold text-slate-950 text-[10.5px] border-r border-slate-200 align-top leading-tight">
                                                <div>{{ $suspectDisplay }}</div>
                                                @if(!empty($item->commodity_name))
                                                    <div class="text-[9px] font-normal text-slate-500 font-mono-code pt-0.5">{{ $item->commodity_name }}</div>
                                                @endif
                                            </td>
                                            <td class="py-2 px-2 text-right font-mono-code font-black text-emerald-900 border-r border-slate-200 align-top text-[11px]">
                                                GH₵ {{ $val > 0 ? number_format($val, 2) : '0.00' }}
                                            </td>
                                            <td class="py-2 px-2.5 font-bold uppercase text-slate-900 text-[10.5px] align-top leading-tight">
                                                {{ $locationDisplay }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="py-3 px-3 text-center text-slate-500 font-mono-code text-[10px]">
                                                No monetary funds or seized currency recorded during the reporting period.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    </div>
                </div>

                <!-- ========================================================
                     SECTION SIGNATURES (ON FINAL DOSSIER PAGE - EXACT 3 COLS)
                     ======================================================== -->
                @if (!empty($p['signatures']))
                <div class="section-signatures avoid-break shrink-0 w-full mt-auto pt-2 pb-1.5 border-t-2 border-slate-300">
                    <div class="grid grid-cols-3 gap-6">
                        <!-- LEFT: HEAD OF EVIDENCE MANAGEMENT UNIT -->
                        <div class="flex flex-col">
                            <p class="text-[8.5px] font-bold uppercase tracking-wider text-slate-700 font-mono-code truncate">HEAD OF EVIDENCE MANAGEMENT UNIT</p>
                            
                            <!-- Space for in-person physical signature -->
                            <div class="h-10 flex items-center">
                                @if(!empty($dbEvidenceHead?->signature))
                                    <img src="{{ $dbEvidenceHead->signature }}" alt="Signature" class="max-h-8 object-contain" onerror="this.style.display='none'">
                                @endif
                            </div>

                            <div class="border-b border-slate-700 pb-0.5"></div>
                            <div class="mt-1">
                                <p class="text-[10px] font-extrabold text-slate-900 leading-tight truncate">{{ $evidenceOfficerName }}</p>
                                <p class="text-[8px] text-slate-700 leading-tight">Head of Evidence Management Unit, NACOC</p>
                                <p class="text-[8px] text-slate-600 font-mono-code pt-0.5">Date: <span class="font-bold text-slate-800">{{ $reportGeneratedDate }}</span></p>
                            </div>
                        </div>

                        <!-- MIDDLE: HEAD OF FORENSIC DEPARTMENT -->
                        <div class="flex flex-col">
                            <p class="text-[8.5px] font-bold uppercase tracking-wider text-slate-700 font-mono-code truncate">HEAD OF FORENSIC DEPARTMENT</p>
                            
                            <!-- Space for in-person physical signature -->
                            <div class="h-10 flex items-center">
                                @if(!empty($dbDeptHead?->signature))
                                    <img src="{{ $dbDeptHead->signature }}" alt="Signature" class="max-h-8 object-contain" onerror="this.style.display='none'">
                                @endif
                            </div>

                            <div class="border-b border-slate-700 pb-0.5"></div>
                            <div class="mt-1">
                                <p class="text-[10px] font-extrabold text-slate-900 leading-tight truncate">{{ $deptHeadName }}</p>
                                <p class="text-[8px] text-slate-700 leading-tight">Head of Forensic Department, NACOC</p>
                                <p class="text-[8px] text-slate-600 font-mono-code pt-0.5">Date: <span class="font-bold text-slate-800">{{ $reportGeneratedDate }}</span></p>
                            </div>
                        </div>

                        <!-- RIGHT: DIRECTOR OF INTELLIGENCE AND INVESTIGATION -->
                        <div class="flex flex-col">
                            <p class="text-[8.5px] font-bold uppercase tracking-wider text-slate-700 font-mono-code truncate">DIRECTOR OF INTEL &amp; INVESTIGATION</p>
                            
                            <!-- Space for in-person physical signature -->
                            <div class="h-10"></div>

                            <div class="border-b border-slate-700 pb-0.5"></div>
                            <div class="mt-1">
                                <p class="text-[9px] font-bold text-slate-900 leading-tight font-mono-code">{{ $intelDirectorName }}</p>
                                <p class="text-[8px] text-slate-700 leading-tight">Director of Intelligence &amp; Investigation, NACOC</p>
                                <p class="text-[8px] text-slate-600 font-mono-code pt-0.5">Date: <span class="font-bold text-slate-800">{{ $reportGeneratedDate }}</span></p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- DOSSIER FOOTER -->
                <div class="page-footer-block {{ empty($p['signatures']) ? 'mt-auto' : '' }} pt-2 border-t border-slate-200 flex items-center justify-between text-[9.5px] text-slate-500 font-mono-code select-none footer">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-700 tracking-wider">NACOC / EVIDENCE MANAGEMENT UNIT</span>
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

    <!-- SCRIPT FOR AUTOMATIC PRINT IF REQUESTED -->
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
