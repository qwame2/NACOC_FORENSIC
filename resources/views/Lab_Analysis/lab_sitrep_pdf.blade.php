@php
    $authUser = auth()->user();

    // Data collections
    $seizuresList   = collect($filteredSeizures ?? []);
    $screeningList  = collect($drugScreeningRecords ?? []);
    $gsaList        = collect($gsaSubmissions ?? []);

    // Calibrated printable height budget for A4 portrait (in millimeters)
    // A4 height = 297mm. Padding = 10mm top + 10mm bottom = 20mm. Usable page: 277mm.
    // Page 2 header: running (10mm) + title block (16mm) + intro paragraph (9mm) + gap (4mm) = 39mm.
    // Continuation header: running (10mm) + gap (4mm) = 14mm.
    // Page footer: 8mm.
    // Target margin above footer: 15mm.
    $firstPageBudget        = 215.0; // mm available for content on Page 2 (fills page nicely, avoids premature breaks)
    $continuationPageBudget = 218.0; // mm available for content on Page 3+ (balanced fullness, prevents footer overlap)

    // Section overheads and component weights (in millimeters)
    $sec01HeaderH       = 22.0;  // Section 01 badge + title + counter + thead
    $sec01ContinuationH = 19.0;  // Continuation title + thead
    $sec02HeaderH       = 26.0;  // Section 02 badge + title + intro + thead
    $sec02ContinuationH = 18.0;  // Continuation title + thead
    $sec03HeaderH       = 26.0;  // Section 03 badge + title + intro + thead
    $sec03ContinuationH = 18.0;  // Continuation title + thead
    $sec03EmptyRowH     = 11.0;  // Empty GSA message row
    $sectionGapH        = 5.0;   // Vertical space between consecutive sections
    $signaturesH        = 44.0;  // Dual official signature block

    // Dynamic row height estimators calibrated to real rendered table cell heights
    $calcSeizureRowH = function($item) {
        $remarks = (!empty($item['exhibit_remark']) && $item['exhibit_remark'] !== 'Unspecified') ? trim((string)$item['exhibit_remark']) : null;
        if (empty($remarks) && !empty($item['exhibit_id'])) {
            $remarks = \App\Models\Exhibit::where('id', $item['exhibit_id'])->whereNotNull('exhibit_remark')->where('exhibit_remark', '!=', '')->value('exhibit_remark');
        }
        $remarks = (string)($remarks ?: 'Unspecified');
        $suspects = $item['suspects'] ?? [];
        $suspectNames = $item['suspects_display'] ?? '';
        if (empty($suspectNames) && !empty($suspects)) {
            $names = array_map(fn($s) => trim($s['name'] ?? ($s['suspect_name'] ?? '')), $suspects);
            $suspectNames = implode(', ', array_filter($names));
        }

        $remLen = mb_strlen($remarks);
        $remLines = max(1, (int)ceil($remLen / 25));

        $suspLen = mb_strlen($suspectNames);
        $suspLines = max(1, (int)ceil($suspLen / 16));

        $exLen = mb_strlen((string)($item['exhibit_type'] ?? ''));
        $exLines = max(1, (int)ceil($exLen / 16));

        $sourceLen = mb_strlen((string)($item['source_of_exhibits'] ?? ($item['source'] ?? '')));
        $sourceLines = max(1, (int)ceil($sourceLen / 16));

        $dateLines = mb_strlen((string)($item['date_display'] ?? '')) > 11 ? 2 : 1;

        $maxLines = max($remLines, $suspLines, $exLines, $sourceLines, $dateLines, 1);
        return 5.5 + ($maxLines * 3.5);
    };

    $calcScreeningRowH = function($item) {
        $sub = (string)($item['substances_found'] ?? '');
        $name = (string)($item['suspect_name'] ?? '');
        $purpose = (string)($item['purpose'] ?? '');
        $lines = max(
            (int)ceil(mb_strlen($sub) / 25),
            (int)ceil(mb_strlen($name) / 20),
            (int)ceil(mb_strlen($purpose) / 20),
            1
        );
        return 6.0 + ($lines * 3.5);
    };

    $calcGsaRowH = function($item) {
        $exType = (string)($item['exhibit_type'] ?? '');
        $caseRef = (string)($item['gsa_case_reference'] ?? ($item['case_reference'] ?? ''));
        $lines = max(
            (int)ceil(mb_strlen($exType) / 25),
            (int)ceil(mb_strlen($caseRef) / 20),
            1
        );
        return 6.0 + ($lines * 3.5);
    };

    $remainingSeizures    = $seizuresList->values();
    $remainingScreenings  = $screeningList->values();
    $remainingGsa         = $gsaList->values();
    $hasGsaEmptyNotice    = $gsaList->isEmpty();

    $sec01Started         = false;
    $sec01Finished        = false;
    $sec02Started         = false;
    $sec02Finished        = $screeningList->isEmpty();
    $sec03Started         = false;
    $sec03Finished        = false;
    $signaturesPlaced     = false;

    $dossierPages         = [];
    $isFirst              = true;

    while (!$signaturesPlaced) {
        $pageLimit           = $isFirst ? $firstPageBudget : $continuationPageBudget;
        $currentUsed         = 0.0;

        $pageSeizures        = collect();
        $isSec01Cont         = false;

        $pageScreenings      = collect();
        $isSec02Cont         = false;
        $showSec02Intro      = false;

        $pageGsa             = collect();
        $isSec03Cont         = false;
        $showSec03Intro      = false;
        $showGsaEmpty        = false;

        $pageSignatures      = false;
        $hasAnyContentOnPage = false;

        // 1. Pack Section 01 if not finished
        if (!$sec01Finished) {
            if ($seizuresList->isEmpty()) {
                $emptyRowH = 12.0;
                $needed = $sec01HeaderH + $emptyRowH;
                if (($pageLimit - $currentUsed >= $needed) || !$hasAnyContentOnPage) {
                    $currentUsed += $needed;
                    $sec01Finished = true;
                    $hasAnyContentOnPage = true;
                }
            } else {
                $isSec01Cont = $sec01Started;
                $headerH     = $isSec01Cont ? $sec01ContinuationH : $sec01HeaderH;
                $gap         = $hasAnyContentOnPage ? $sectionGapH : 0.0;
                $firstRowH   = $remainingSeizures->isNotEmpty() ? $calcSeizureRowH($remainingSeizures->first()) : 11.5;
                $minNeeded   = $gap + $headerH + $firstRowH;

                if (($pageLimit - $currentUsed >= $minNeeded) || !$hasAnyContentOnPage) {
                    $currentUsed += ($gap + $headerH);
                    $sec01Started = true;
                    $hasAnyContentOnPage = true;

                    while ($remainingSeizures->isNotEmpty()) {
                        $nextItem = $remainingSeizures->first();
                        $rowH = $calcSeizureRowH($nextItem);

                        if (($currentUsed + $rowH > $pageLimit) && $pageSeizures->isNotEmpty()) {
                            break;
                        }

                        $pageSeizures->push($remainingSeizures->shift());
                        $currentUsed += $rowH;
                    }

                    if ($remainingSeizures->isEmpty()) {
                        $sec01Finished = true;
                    }
                }
            }
        }

        // 2. Pack Section 02 if not finished
        if ($sec01Finished && !$sec02Finished) {
            $isSec02Cont = $sec02Started;
            $headerH     = $isSec02Cont ? $sec02ContinuationH : $sec02HeaderH;
            $gap         = $hasAnyContentOnPage ? $sectionGapH : 0.0;
            $firstRowH   = $remainingScreenings->isNotEmpty() ? $calcScreeningRowH($remainingScreenings->first()) : 10.5;
            $minNeeded   = $gap + $headerH + $firstRowH;

            if (($pageLimit - $currentUsed >= $minNeeded) || !$hasAnyContentOnPage) {
                $currentUsed += ($gap + $headerH);
                $showSec02Intro = !$sec02Started;
                $sec02Started = true;
                $hasAnyContentOnPage = true;

                while ($remainingScreenings->isNotEmpty()) {
                    $nextItem = $remainingScreenings->first();
                    $rowH = $calcScreeningRowH($nextItem);

                    if (($currentUsed + $rowH > $pageLimit) && $pageScreenings->isNotEmpty()) {
                        break;
                    }

                    $pageScreenings->push($remainingScreenings->shift());
                    $currentUsed += $rowH;
                }

                if ($remainingScreenings->isEmpty()) {
                    $sec02Finished = true;
                }
            }
        }

        // 3. Pack Section 03 if not finished
        if ($sec01Finished && $sec02Finished && !$sec03Finished) {
            $gap = $hasAnyContentOnPage ? $sectionGapH : 0.0;

            if ($hasGsaEmptyNotice) {
                // Section 03 has no records - 1-row empty notice
                $needed = $gap + $sec03HeaderH + $sec03EmptyRowH;

                // Check if both Section 03 empty notice AND Signatures can fit together
                $bothFitWithSignatures = ($currentUsed + $needed + $sectionGapH + $signaturesH <= $pageLimit);

                // If they don't both fit, and the page already has content, defer Section 03
                // to the next page so the next page has Section 03 + Signatures together,
                // rather than having a page with ONLY signatures.
                $deferForSignatures = (!$bothFitWithSignatures && $hasAnyContentOnPage);

                if (!$deferForSignatures && (($pageLimit - $currentUsed >= $needed) || !$hasAnyContentOnPage)) {
                    $currentUsed += $needed;
                    $showGsaEmpty   = true;
                    $showSec03Intro = true;
                    $sec03Started   = true;
                    $sec03Finished  = true;
                    $hasAnyContentOnPage = true;
                }
            } else {
                // Section 03 has actual GSA submission records
                $isSec03Cont = $sec03Started;
                $headerH     = $isSec03Cont ? $sec03ContinuationH : $sec03HeaderH;
                $firstRowH   = $remainingGsa->isNotEmpty() ? $calcGsaRowH($remainingGsa->first()) : 10.5;
                $minNeeded   = $gap + $headerH + $firstRowH;

                // Anti-orphan check: If Section 03 has not started yet, and there is only 1 GSA item,
                // and the current page already has content, check if Section 03 + Signatures can fit together.
                // If not, defer Section 03 to the next page so the next page has Section 03 + Signatures together.
                $deferSec03 = (!$sec03Started && $remainingGsa->count() === 1 && $hasAnyContentOnPage && ($currentUsed + $minNeeded + $sectionGapH + $signaturesH > $pageLimit));

                if (!$deferSec03 && (($pageLimit - $currentUsed >= $minNeeded) || !$hasAnyContentOnPage)) {
                    $currentUsed += ($gap + $headerH);
                    $showSec03Intro = !$sec03Started;
                    $sec03Started   = true;
                    $hasAnyContentOnPage = true;

                    while ($remainingGsa->isNotEmpty()) {
                        $nextItem = $remainingGsa->first();
                        $rowH = $calcGsaRowH($nextItem);

                        // If adding this row exceeds page limit, and we have at least 1 row on this page, stop
                        if (($currentUsed + $rowH > $pageLimit) && $pageGsa->isNotEmpty()) {
                            break;
                        }

                        // Anti-orphan check: If this is the LAST item in remaining GSA,
                        // check whether Signatures will also be able to fit on this page.
                        // If Signatures cannot fit, and we already have at least 1 row on this page,
                        // keep this last row for the next page so the next page has content + signatures!
                        if ($remainingGsa->count() === 1 && $pageGsa->isNotEmpty()) {
                            $canFitSignaturesToo = ($currentUsed + $rowH + $sectionGapH + $signaturesH <= $pageLimit);
                            if (!$canFitSignaturesToo) {
                                break;
                            }
                        }

                        $pageGsa->push($remainingGsa->shift());
                        $currentUsed += $rowH;
                    }

                    if ($remainingGsa->isEmpty()) {
                        $sec03Finished = true;
                    }
                }
            }
        }

        // 4. Pack Signatures if all sections are finished
        if ($sec01Finished && $sec02Finished && $sec03Finished) {
            $gap = $hasAnyContentOnPage ? $sectionGapH : 0.0;
            $needed = $gap + $signaturesH;

            if (($pageLimit - $currentUsed >= $needed) || !$hasAnyContentOnPage) {
                $currentUsed += $needed;
                $pageSignatures   = true;
                $signaturesPlaced = true;
            }
        }

        // Build dossier page configuration
        $dossierPages[] = [
            'isFirst'               => $isFirst,
            'isLast'                => false, // Updated after loop finishes
            'seizures'              => $pageSeizures,
            'isSection01Continued'  => $isSec01Cont,
            'screenings'            => $pageScreenings,
            'isSection02Continued'  => $isSec02Cont,
            'showSection02Intro'    => $showSec02Intro,
            'gsa'                   => $pageGsa,
            'isSection03Continued'  => $isSec03Cont,
            'showSection03Intro'    => $showSec03Intro,
            'showGsaEmpty'          => $showGsaEmpty,
            'signatures'            => $pageSignatures,
        ];

        $isFirst = false;
    }

    // Ensure only the final dossier page is designated as the last page with signatures
    if (!empty($dossierPages)) {
        $lastIdx = count($dossierPages) - 1;
        for ($i = 0; $i < $lastIdx; $i++) {
            $dossierPages[$i]['isLast'] = false;
            $dossierPages[$i]['signatures'] = false;
        }
        $dossierPages[$lastIdx]['isLast'] = true;
        $dossierPages[$lastIdx]['signatures'] = true;
    }

    // Total dossier pages = 1 cover page + count of dossier pages
    $totalDossierPages = 1 + count($dossierPages);

    $intelDirectorName = $intelDirectorName ?? null;
    $reportGeneratedDate = $generatedDate ?? date('d F Y');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NACOC Laboratory Analysis Unit &bull; Forensic Drug Report</title>
    
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
            margin-bottom: 3mm;
        }

        .section-signatures {
            flex-shrink: 0;
            width: 100%;
            margin-top: auto;
            margin-bottom: 6px;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .page-footer-block {
            flex-shrink: 0;
            margin-top: auto;
            width: 100%;
            position: relative;
            z-index: 20;
            background-color: #ffffff;
            padding-top: 2.5mm;
        }

        .section-signatures + .page-footer-block {
            margin-top: 0 !important;
        }

        .section-header-block {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            page-break-after: avoid !important;
            break-after: avoid !important;
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

            .a4-page {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                width: 210mm !important;
                height: 297mm !important;
                max-height: 297mm !important;
                min-height: 297mm !important;
                padding: 10mm 12mm 10mm 12mm !important;
                overflow: hidden !important;
                page-break-after: always !important;
                break-after: page !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                box-sizing: border-box !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                background: #ffffff !important;
            }

            .cover-page {
                box-shadow: none !important;
                border: none !important;
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
                box-sizing: border-box !important;
            }

            .dynamic-dossier-page {
                box-shadow: none !important;
                border: none !important;
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
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                box-sizing: border-box !important;
            }

            .page-content-wrapper {
                height: 100% !important;
                min-height: 100% !important;
                max-height: 100% !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                flex: 1 1 0% !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }

            .page-header-block {
                flex-shrink: 0 !important;
                width: 100% !important;
            }

            .report-flow-body {
                flex: 1 1 auto !important;
                min-height: 0 !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: flex-start !important;
                width: 100% !important;
                margin-bottom: 3mm !important;
            }

            .section-signatures {
                flex-shrink: 0 !important;
                width: 100% !important;
                margin-top: auto !important;
                margin-bottom: 6px !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .page-footer-block {
                flex-shrink: 0 !important;
                margin-top: auto !important;
                width: 100% !important;
                position: relative !important;
                z-index: 20 !important;
                background-color: #ffffff !important;
                padding-top: 2mm !important;
            }

            .section-signatures + .page-footer-block {
                margin-top: 0 !important;
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

        /* Preview mode / Iframe mode: suppress interactive filter row */
        .is-preview-mode #sitrepFilterBar,
        .is-preview-mode .sitrep-filter-bar,
        html.is-preview-mode #sitrepFilterBar,
        html.is-preview-mode .sitrep-filter-bar {
            display: none !important;
        }
    </style>
    <script>
        try {
            if (window.self !== window.top || new URLSearchParams(window.location.search).has('preview') || new URLSearchParams(window.location.search).get('from') === 'court-evidence') {
                document.documentElement.classList.add('is-preview-mode');
            }
        } catch (e) {}
    </script>
</head>
<body class="antialiased bg-white">

    <!-- PRINT-SPECIFIC FIXED WATERMARK -->
    <div class="print-fixed-watermark select-none pointer-events-none" aria-hidden="true">
        <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[550px] h-[550px] object-contain" />
    </div>

    <!-- FLOATING ACTION & FILTER CONTROL BAR (SCREEN VIEW ONLY) -->
    <div class="action-bar-container max-w-[210mm] mx-auto mb-4 px-2 space-y-3 select-none">
        <!-- TOP ROW: NAVIGATION & ACTION BUTTONS -->
        <div class="bg-slate-900/90 backdrop-blur-md border border-slate-700/80 rounded-2xl p-3 px-4 flex items-center justify-between shadow-xl">
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
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

        @php
            $isCourtEvidencePreview = request()->has('preview') 
                || request('from') === 'court-evidence' 
                || request()->boolean('hide_filters') 
                || request()->boolean('hide_filter');
        @endphp

        @if(!$isCourtEvidencePreview)
        <!-- SECOND ROW: INTERACTIVE FILTERS & SEARCH (INLINE) -->
        <div id="sitrepFilterBar" class="sitrep-filter-bar bg-white border border-slate-200/90 rounded-2xl p-2.5 px-3 sm:px-4 shadow-sm">
            <div class="flex items-center justify-between gap-2.5 sm:gap-4 overflow-x-auto min-w-0 flex-nowrap">
                <!-- PERIOD PRESETS -->
                <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl border border-slate-200 shrink-0">
                    <button type="button" onclick="setPeriodFilter('weekly')" class="preset-btn px-2.5 sm:px-3 py-1 rounded-lg text-xs font-bold transition-all whitespace-nowrap {{ in_array($rawPeriod ?? '', ['weekly', 'week']) ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}" data-preset="weekly">
                        Weekly
                    </button>
                    <button type="button" onclick="setPeriodFilter('monthly')" class="preset-btn px-2.5 sm:px-3 py-1 rounded-lg text-xs font-bold transition-all whitespace-nowrap {{ in_array($rawPeriod ?? '', ['monthly', 'month']) ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}" data-preset="monthly">
                        Monthly
                    </button>
                    <button type="button" onclick="setPeriodFilter('annual')" class="preset-btn px-2.5 sm:px-3 py-1 rounded-lg text-xs font-bold transition-all whitespace-nowrap {{ in_array($rawPeriod ?? '', ['annual', 'annually', 'yearly', 'year']) ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}" data-preset="annual">
                        Annual
                    </button>
                    <button type="button" onclick="setPeriodFilter('general')" class="preset-btn px-2.5 sm:px-3 py-1 rounded-lg text-xs font-bold transition-all whitespace-nowrap {{ in_array($rawPeriod ?? '', ['general', 'all']) ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}" data-preset="general">
                        General
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
        @endif
    </div>

    <!-- PRINTABLE DOCUMENT ROOT -->
    <div id="sitrepPrintableDossier" class="printable-document">

        <!-- ============================================================
             PAGE 1: EXACT EXECUTIVE COVER PAGE (IDENTICAL TO MULTI_DPF_PDF)
             ============================================================ -->
        <div class="a4-page cover-page page-break relative text-slate-900 select-none">

            <!-- SUBTLE NACOC WATERMARK -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.07] select-none">
                <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[550px] h-[550px] object-contain" />
            </div>

            <div class="page-content-wrapper relative z-10 flex flex-col justify-between h-full min-h-0 flex-1 w-full">

                <!-- TOP BADGES -->
                <div class="pt-2 flex items-center justify-between text-[8px] font-mono-code select-none">
                    <div class="text-left">
                        <p class="font-bold uppercase tracking-wider text-slate-900">REPUBLIC OF GHANA</p>
                        <p class="mt-0.5 text-slate-700">ISO/IEC 17025 ACCREDITED FORENSIC LABORATORY</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold uppercase tracking-wider text-slate-900">Confidential</p>
                        <p class="mt-0.5 text-[9px] font-bold uppercase text-slate-800" id="page1HeaderBadge">
                            SITREP COVER &bull; Page 1 of <span class="doc-total-pages">{{ $totalDossierPages }}</span>
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
                            OFFICIAL FORENSIC DRUG ANALYSIS &amp; SITUATION REPORT
                        </p>
                        <h2 class="mt-2 text-2xl font-bold uppercase tracking-wide text-slate-950 font-serif-header">
                            LABORATORY ANALYSIS UNIT
                        </h2>
                        <p class="mt-1 text-sm font-semibold uppercase tracking-[0.14em] text-slate-700">
                              Preliminary Forensic Examination &amp; Drug Testing Report
                        </p>
                        <div class="mx-auto mt-4 h-[2px] w-20 bg-emerald-700"></div>
                        <p class="mt-5 text-base font-extrabold uppercase tracking-wider text-emerald-800" id="coverReportTypeTitle">
                            {{ $reportTypeLabel ?? 'FORENSIC DRUG ANALYSIS REPORT' }}
                        </p>
                        <p class="mt-2 text-[13px] font-extrabold uppercase tracking-wider font-times text-emerald-900" id="coverPeriodDisplay">
                            {{ $periodLabel ?? 'Not Specified' }}
                        </p>
                        @if(!empty($selectedSuspect) && strtolower($selectedSuspect) !== 'all')
                            <p class="mt-2 text-[13px] font-black uppercase tracking-wider text-slate-900 font-serif-header">
                                REPUBLIC VS {{ strtoupper($selectedSuspect) }}
                            </p>
                            <p class="mt-1 text-[10px] font-bold uppercase tracking-wider text-emerald-700 font-mono-code">
                                INDIVIDUAL SUSPECT CASE REPORT
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
                                This official situational report contains laboratory analysis, drug screening results, and chain-of-custody intelligence. 
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
                <div class="page-footer-block mt-auto pt-3 border-t border-slate-200 flex items-center justify-between text-[8px] text-slate-500 font-mono-code select-none footer">
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
             PAGE 2+: EXECUTIVE FORENSIC DOSSIER & LOG
             ============================================================ -->
        @foreach ($dossierPages as $pageIdx => $p)
            @php
                $pageNumber = $pageIdx + 2;
                $isFirstDossierPage = $p['isFirst'];
                $isLastDossierPage = $p['isLast'];

                $seizureChunk   = $p['seizures'];
                $screeningChunk = $p['screenings'];
                $gsaChunk       = $p['gsa'];
            @endphp
        <div class="a4-page dynamic-dossier-page page-break relative text-slate-900">

            <!-- SUBTLE NACOC WATERMARK (FOR SCREEN PREVIEW) -->
            <div class="dossier-watermark absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.07] select-none">
                <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[900px] h-[900px] object-contain" />
            </div>

            <div class="page-content-wrapper relative z-10 flex flex-col justify-between h-full min-h-0 flex-1 w-full">

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
                            {{ $reportTypeLabel ?? 'WEEKLY DRUG FORENSIC REPORT' }}
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
                <div class="report-flow-body flex-1 mt-3 min-h-0 flex flex-col justify-start">
                    <div class="flow-sections-container space-y-4">

                    @if ($seizureChunk->isNotEmpty() || ($isFirstDossierPage && count($filteredSeizures ?? []) === 0))
                    <!-- ========================================================
                         SECTION 01: PRELIMINARY FORENSIC EXAMINATION & TESTING
                         (ALL DRUG CASES & SUSPECTS WITH MODERN EXECUTIVE DESIGN)
                         ======================================================== -->
                    <div class="section-01">
                        <div class="section-header-block flex items-center justify-between pb-1 mt-3 mb-2 border-b-2 border-emerald-700">
                            <div class="flex items-center gap-1.5">
                                
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-950 font-serif-header">
                                    Preliminary Forensic Examination &amp; Drug Testing Log{{ !empty($p['isSection01Continued']) ? ' (Continued)' : '' }}
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

                                            $statusText = (!empty($item['exhibit_remark']) && $item['exhibit_remark'] !== 'Unspecified') ? trim((string)$item['exhibit_remark']) : null;
                                            if (empty($statusText) && !empty($item['exhibit_id'])) {
                                                $statusText = \App\Models\Exhibit::where('id', $item['exhibit_id'])->whereNotNull('exhibit_remark')->where('exhibit_remark', '!=', '')->value('exhibit_remark');
                                            }
                                            if (empty($statusText) && !empty($item['real_case_id'])) {
                                                $rId = $item['real_case_id'];
                                                $statusText = \App\Models\Exhibit::where(function($q) use ($rId) {
                                                    $q->where('single_suspect_case_id', $rId)
                                                      ->orWhere('multiple_suspect_case_id', $rId);
                                                })->whereNotNull('exhibit_remark')->where('exhibit_remark', '!=', '')->latest('id')->value('exhibit_remark');
                                            }
                                            if (empty($statusText) && !empty($item['case_reference'])) {
                                                $cRef = $item['case_reference'];
                                                $statusText = \App\Models\Exhibit::where(function($q) use ($cRef) {
                                                    $q->whereHas('singleCase', fn($sq) => $sq->where('case_number', $cRef))
                                                      ->orWhereHas('multipleCase', fn($mq) => $mq->where('case_number', $cRef));
                                                })->whereNotNull('exhibit_remark')->where('exhibit_remark', '!=', '')->latest('id')->value('exhibit_remark');
                                            }
                                            if (empty($statusText)) {
                                                $statusText = 'Unspecified';
                                            }

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
                    <div class="section-02 pt-1">
                        <div class="section-header-block">
                            <div class="flex items-center justify-between pb-1 mt-4 mb-2 border-b-2 border-teal-600">
                                <div class="flex items-center gap-1.5">
                                 
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-950 font-serif-header">
                                        Drug Screening of Suspect(s){{ !empty($p['isSection02Continued']) ? ' (Continued)' : '' }}
                                    </h3>
                                </div>
                                <span class="text-[8px] font-mono-code text-slate-600 font-bold uppercase">
                                    Toxicological Urinalysis Register
                                </span>
                            </div>

                            @if (!empty($p['showSection02Intro']))
                            <p class="text-[9.5px] text-slate-700 mb-2 italic">
                                One (1) or more suspects were subjected to rapid urine multi-panel drug testing during the period under review:
                            </p>
                            @endif
                        </div>

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


                    @if ($gsaChunk->isNotEmpty() || (!empty($p['showGsaEmpty'])))
                    <!-- ========================================================
                         SECTION 03: GHANA STANDARDS AUTHORITY (GSA) SUBMISSIONS
                         ======================================================== -->
                    <div class="section-03 pt-1">
                        <div class="section-header-block">
                            <div class="flex items-center justify-between pb-1 mt-4 mb-2 border-b-2 border-indigo-700">
                                <div class="flex items-center gap-1.5">
                                    
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-950 font-serif-header">
                                        Ghana Standards Authority (GSA) Submissions{{ !empty($p['isSection03Continued']) ? ' (Continued)' : '' }}
                                    </h3>
                                </div>
                                <span class="text-[8px] font-mono-code text-slate-600 font-bold uppercase">
                                    External Confirmatory Testing
                                </span>
                            </div>

                            @if (!empty($p['showSection03Intro']))
                            <p class="text-[9.5px] text-slate-700 mb-2 italic">
                                The details of exhibits and seized case samples submitted to Ghana Standards Authority (GSA) for laboratory examination and reporting:
                            </p>
                            @endif
                        </div>

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
                                                {{ !empty($gsa['exhibit_type']) ? $gsa['exhibit_type'] : 'N/A' }}
                                            </td>
                                            <td class="py-2 px-2 border-r border-slate-200 text-center font-mono-code font-bold text-indigo-900 align-top text-[9.5px] break-words">
                                                {{ $gsa['gsa_lab_number'] ?? ($gsa['gsa_lab_no'] ?? 'N/A') }}
                                            </td>
                                            <td class="py-2 px-2.5 align-top text-center break-words font-bold uppercase text-slate-800 text-[9.5px]">
                                                {{ $gsa['gsa_status'] ?? 'N/A' }}
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
                </div>

                @if (!empty($p['signatures']))
                <!-- ========================================================
                     SECTION 05: OFFICIAL TRI-LEVEL SIGN-OFF & CERTIFICATION
                     (IN-PERSON PHYSICAL SIGN-OFF)
                     ======================================================== -->
                <div class="section-signatures avoid-break shrink-0 w-full mt-auto pt-2 pb-1.5 border-t-2 border-slate-300">
                    <div class="grid grid-cols-3 gap-6">
                        <!-- LEFT: HEAD OF LABORATORY ANALYSIS -->
                        <div class="flex flex-col">
                            <p class="text-[8.5px] font-bold uppercase tracking-wider text-slate-700 font-mono-code truncate">Head of Laboratory Analysis Unit</p>
                            
                            <!-- Space for in-person physical signature -->
                            <div class="h-10"></div>

                            <div class="border-b border-slate-700 pb-0.5"></div>
                            <div class="mt-1">
                                <p class="text-[10px] font-extrabold text-slate-900 leading-tight truncate">{{ $labHeadName }}</p>
                                <p class="text-[8px] text-slate-700 leading-tight">Head of Laboratory Analysis Unit, NACOC</p>
                                <p class="text-[8px] text-slate-600 font-mono-code pt-0.5">Date: <span class="font-bold text-slate-800">{{ $reportGeneratedDate }}</span></p>
                            </div>
                        </div>

                        <!-- MIDDLE: HEAD OF FORENSIC DEPARTMENT -->
                        <div class="flex flex-col">
                            <p class="text-[8.5px] font-bold uppercase tracking-wider text-slate-700 font-mono-code truncate">Head of Forensic Department</p>
                            
                            <!-- Space for in-person physical signature -->
                            <div class="h-10"></div>

                            <div class="border-b border-slate-700 pb-0.5"></div>
                            <div class="mt-1">
                                <p class="text-[10px] font-extrabold text-slate-900 leading-tight truncate">{{ $deptHeadName }}</p>
                                <p class="text-[8px] text-slate-700 leading-tight">Head of Forensic Department, NACOC</p>
                                <p class="text-[8px] text-slate-600 font-mono-code pt-0.5">Date: <span class="font-bold text-slate-800">{{ $reportGeneratedDate }}</span></p>
                            </div>
                        </div>

                        <!-- RIGHT: DIRECTOR OF INTELLIGENCE AND INVESTIGATION -->
                        <div class="flex flex-col">
                            <p class="text-[8.5px] font-bold uppercase tracking-wider text-slate-700 font-mono-code truncate">Director of Intel &amp; Investigation</p>
                            
                            <!-- Space for in-person physical signature -->
                            <div class="h-10"></div>

                            <div class="border-b border-slate-700 pb-0.5"></div>
                            <div class="mt-1">
                                @if(!empty($intelDirectorName) && $intelDirectorName !== 'N/A')
                                    <p class="text-[10px] font-extrabold text-slate-900 leading-tight truncate">{{ $intelDirectorName }}</p>
                                @else
                                    <p class="text-[9px] font-bold text-slate-900 leading-tight font-mono-code">Name: ____________________</p>
                                @endif
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
                        <span class="font-bold text-slate-700 tracking-wider">NACOC / LABORATORY ANALYSIS UNIT</span>
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
            } else if (preset === 'annual' || preset === 'annually') {
                const firstYear = new Date(now.getFullYear(), 0, 1);
                const lastYear = new Date(now.getFullYear(), 11, 31);
                url.searchParams.set('from', formatYmd(firstYear));
                url.searchParams.set('to', formatYmd(lastYear));
            } else if (preset === 'all' || preset === 'general') {
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
