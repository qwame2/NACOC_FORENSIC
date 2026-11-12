@php
    $itemsPerPage = 2;
    $screeningChunks = $screenings->chunk($itemsPerPage);
    if ($screeningChunks->isEmpty()) {
        $screeningChunks = collect([collect()]);
    }
    $totalReportPages = 1 + $screeningChunks->count();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $reportTypeLabel }} ({{ $referenceNumber }}) | NACOC Forensic Laboratory</title>

    <!-- Scripts & Stylesheets -->
    <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Base Reset & Medical Print Stylesheet */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            counter-reset: docSheet 0;
            background-color: #f1f5f9;
            color: #000000;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            font-size: 10.5pt;
            line-height: 1.45;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .font-official, .font-serif-header { font-family: 'Cinzel', Georgia, serif; letter-spacing: 0.04em; }
        .font-mono-code { font-family: 'JetBrains Mono', Consolas, Monaco, monospace; }
        .font-times { font-family: 'Times New Roman', Times, Baskerville, Georgia, serif; }

        /* Floating Screen Action Bar */
        .screen-toolbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 52px;
            background: #0f172a;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            z-index: 99999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.25);
        }

        .screen-toolbar a, .screen-toolbar button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.15s ease;
        }

        .btn-print {
            background: #059669;
            color: #ffffff;
        }
        .btn-print:hover { background: #047857; }

        .btn-close {
            background: #334155;
            color: #e2e8f0;
        }
        .btn-close:hover { background: #475569; }

        /* Document Container */
        .document-wrapper {
            max-width: 210mm;
            margin: 70px auto 40px auto;
        }

        /* Standard A4 Sheet: Exact fixed physical dimensions */
        .a4-page {
            width: 210mm !important;
            height: 297mm !important;
            min-height: 297mm !important;
            max-height: 297mm !important;
            background: #ffffff !important;
            margin: 0 auto 24px auto;
            padding: 12mm 16mm 22mm 16mm !important;
            position: relative !important;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            box-sizing: border-box !important;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            overflow: hidden !important;
        }

        .page-break {
            page-break-after: always;
            break-after: page;
        }

        /* Watermark (Strictly 10% - 15% opacity) */
        .watermark-container {
            position: absolute;
            top: 52%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 480px;
            height: 480px;
            opacity: 0.12;
            pointer-events: none;
            user-select: none;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .watermark-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .page-content {
            position: relative;
            z-index: 10;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            width: 100%;
            box-sizing: border-box;
        }

        /* Cover Page Styles */
        .cover-page {
            width: 210mm !important;
            height: 297mm !important;
            max-height: 297mm !important;
            min-height: 297mm !important;
            padding: 12mm 14mm 22mm 14mm !important;
            overflow: hidden !important;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #ffffff !important;
            box-sizing: border-box !important;
            position: relative !important;
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
        }

        /* Statically Locked Footers across all pages */
        .page-footer-block,
        .medical-page-footer {
            position: absolute !important;
            bottom: 8mm !important;
            width: auto !important;
            margin: 0 !important;
            padding-top: 5px !important;
            z-index: 50 !important;
            box-sizing: border-box !important;
            background: #ffffff !important;
        }

        .medical-page-footer {
            left: 16mm !important;
            right: 16mm !important;
            border-top: 1.5px solid #000000 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            font-size: 7.5pt !important;
            font-family: 'JetBrains Mono', monospace !important;
            color: #000000 !important;
        }

        .page-footer-block {
            left: 14mm !important;
            right: 14mm !important;
            border-top: 1px solid #cbd5e1 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            font-size: 8px !important;
            font-family: 'JetBrains Mono', monospace !important;
            color: #64748b !important;
        }

        /* ========================================================
           DETAILED MODERN FORENSIC DOSSIER STYLESHEET
           ======================================================== */
        
        /* Modern Executive Running Header */
        .modern-running-header {
            border-bottom: 2px solid #000000;
            padding-bottom: 6px;
            margin-bottom: 11px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
        }

        .running-header-left {
            display: flex;
            flex-direction: column;
            gap: 1px;
        }

        .running-agency {
            font-family: 'JetBrains Mono', monospace;
            font-size: 6.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #444444;
        }

        .running-title {
            font-size: 9.5pt;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #000000;
            line-height: 1.2;
            margin: 0;
        }

        .running-subtitle {
            font-family: 'JetBrains Mono', monospace;
            font-size: 7.5pt;
            font-weight: 600;
            text-transform: uppercase;
            color: #222222;
            letter-spacing: 0.03em;
        }

        .running-header-right {
            text-align: right;
            font-family: 'JetBrains Mono', monospace;
            font-size: 7.5pt;
            font-weight: 700;
            color: #000000;
            line-height: 1.35;
        }

        /* Modern Executive Forensic Dashboard (Page 2) */
        .executive-dashboard-card {
            border: 1.5px solid #000000;
            margin: 0 0 11px 0;
            background: #ffffff;
            box-sizing: border-box;
        }

        .dashboard-header-strip {
            background: #000000;
            color: #ffffff;
            padding: 3.5px 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-family: 'JetBrains Mono', monospace;
            font-size: 7pt;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .dashboard-kpi-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            border-bottom: 1px solid #000000;
        }

        .kpi-tile {
            padding: 5px 8px;
            border-right: 1px solid #cbd5e1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-sizing: border-box;
        }

        .kpi-tile:last-child {
            border-right: none;
        }

        .kpi-tile-label {
            font-family: 'JetBrains Mono', monospace;
            font-size: 6.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #555555;
            line-height: 1.2;
        }

        .kpi-tile-val {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13pt;
            font-weight: 900;
            color: #000000;
            line-height: 1.15;
            margin: 2px 0 1px 0;
        }

        .kpi-tile-sub {
            font-family: 'JetBrains Mono', monospace;
            font-size: 6pt;
            font-weight: 600;
            color: #444444;
            text-transform: uppercase;
        }

        .dashboard-meta-strip {
            padding: 3px 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: 'JetBrains Mono', monospace;
            font-size: 6.5pt;
            font-weight: 600;
            color: #333333;
            background: #f8fafc;
        }

        /* Section Title Bar */
        .dossier-section-bar {
            border-bottom: 1.5px solid #000000;
            padding-bottom: 3px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            font-family: 'JetBrains Mono', monospace;
        }

        .dossier-section-bar h2 {
            font-size: 8pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #000000;
            margin: 0;
        }

        .dossier-section-bar span {
            font-size: 7pt;
            font-weight: 700;
            color: #444444;
            text-transform: uppercase;
        }

        /* Modern Clinical Dossier Block */
        .dossier-block {
            border: 1.5px solid #000000;
            margin-bottom: 13px;
            background: #ffffff;
            box-sizing: border-box;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .dossier-block:last-child {
            margin-bottom: 0;
        }

        /* Dossier Header */
        .dossier-header-bar {
            background: #f1f5f9;
            border-bottom: 1px solid #000000;
            padding: 5px 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dossier-header-left {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .dossier-badge-idx {
            background: #000000;
            color: #ffffff;
            font-family: 'JetBrains Mono', monospace;
            font-size: 7pt;
            font-weight: 800;
            padding: 2px 6px;
            letter-spacing: 0.04em;
        }

        .dossier-subject-name {
            font-size: 9.5pt;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #000000;
        }

        .dossier-classification-pill {
            font-family: 'JetBrains Mono', monospace;
            font-size: 6.5pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 1.5px 6px;
            border: 1px solid #000000;
            background: #ffffff;
            color: #000000;
        }

        .dossier-alias-tag {
            font-size: 8pt;
            font-style: italic;
            color: #333333;
        }

        .dossier-header-right {
            text-align: right;
            font-family: 'JetBrains Mono', monospace;
            font-size: 7.5pt;
            color: #000000;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dossier-header-right strong {
            font-weight: 800;
        }

        /* Dossier Body Content */
        .dossier-inner-content {
            padding: 7px 9px;
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        /* Structured Demographic Table */
        .dossier-details-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }

        .dossier-details-table td {
            border: 1px solid #cbd5e1;
            padding: 3.5px 7px;
            vertical-align: middle;
        }

        .dossier-details-table .lbl {
            width: 22%;
            font-family: 'JetBrains Mono', monospace;
            font-size: 6.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #444444;
            background: #f8fafc;
        }

        .dossier-details-table .val {
            width: 28%;
            font-weight: 600;
            color: #000000;
        }

        /* Toxicological Assay Findings Box (Matching Civilian Medical Report Style) */
        .dossier-assay-box {
            border: 1px solid #000000;
            background: #ffffff;
            padding: 6px 9px;
            box-sizing: border-box;
        }

        .assay-top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px;
            margin-bottom: 5px;
        }

        .assay-title-text {
            font-family: 'JetBrains Mono', monospace;
            font-size: 7.5pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #000000;
        }

        .assay-checkbox-group {
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 7.5pt;
            font-weight: 700;
        }

        .assay-chk-item {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .chk-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 13px;
            height: 13px;
            border: 1.5px solid #000000;
            font-size: 8pt;
            font-weight: 900;
            line-height: 1;
            box-sizing: border-box;
            background: #ffffff;
        }

        .assay-finding-details {
            display: flex;
            flex-direction: column;
            gap: 3px;
            font-size: 8pt;
            line-height: 1.35;
        }

        .finding-row {
            display: flex;
            align-items: baseline;
            gap: 6px;
        }

        .finding-lbl {
            font-family: 'JetBrains Mono', monospace;
            font-size: 6.8pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #000000;
            width: 135px;
            flex-shrink: 0;
        }

        .finding-val {
            font-weight: 600;
            color: #000000;
        }

        .finding-val.substances {
            font-family: 'JetBrains Mono', monospace;
            font-size: 8.5pt;
            font-weight: 900;
        }

        .finding-desc {
            font-size: 7.5pt;
            color: #333333;
        }

        /* Medication Schedule Mini-Table (If declared) */
        .dossier-meds-section {
            border: 1px solid #cbd5e1;
            padding: 5px 8px;
            background: #f8fafc;
            box-sizing: border-box;
        }

        .dossier-meds-header {
            font-family: 'JetBrains Mono', monospace;
            font-size: 6.8pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #000000;
            margin-bottom: 4px;
            display: flex;
            justify-content: space-between;
        }

        .dossier-meds-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
            background: #ffffff;
        }

        .dossier-meds-table th {
            background: #e2e8f0;
            border: 1px solid #cbd5e1;
            padding: 2px 4px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 6.5pt;
            font-weight: 700;
            text-transform: uppercase;
            text-align: left;
        }

        .dossier-meds-table td {
            border: 1px solid #cbd5e1;
            padding: 2.5px 5px;
            color: #000000;
            font-weight: 500;
        }

        /* Clinical Notes Block */
        .dossier-notes-section {
            border-left: 2.5px solid #000000;
            padding: 2px 8px;
            font-size: 7.5pt;
            line-height: 1.35;
            background: #f8fafc;
        }

        .notes-lbl {
            font-family: 'JetBrains Mono', monospace;
            font-size: 6.5pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #000000;
            margin-right: 4px;
        }

        .notes-text {
            color: #222222;
        }

        .dossier-empty-state {
            border: 1.5px dashed #000000;
            padding: 40px 16px;
            text-align: center;
            background: #ffffff;
            margin-top: 16px;
        }

        .dossier-empty-state .empty-title {
            font-family: 'JetBrains Mono', monospace;
            font-size: 9.5pt;
            font-weight: 900;
            text-transform: uppercase;
            color: #000000;
            margin-bottom: 4px;
        }

        .dossier-empty-state .empty-desc {
            font-size: 8pt;
            color: #555555;
            font-style: italic;
        }

        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .screen-toolbar, .no-print {
                display: none !important;
            }

            .document-wrapper {
                margin: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
            }

            .a4-page {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                width: 210mm !important;
                height: 297mm !important;
                min-height: 297mm !important;
                max-height: 297mm !important;
                padding: 12mm 16mm 22mm 16mm !important;
                position: relative !important;
                page-break-after: always !important;
                break-after: page !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
            }

            .a4-page:last-child {
                page-break-after: avoid !important;
                break-after: avoid !important;
            }

            .cover-page {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                width: 210mm !important;
                height: 297mm !important;
                max-height: 297mm !important;
                min-height: 297mm !important;
                padding: 12mm 14mm 22mm 14mm !important;
                overflow: hidden !important;
                position: relative !important;
                page-break-after: always !important;
                break-after: page !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                box-sizing: border-box !important;
            }

            .page-footer-block,
            .medical-page-footer {
                position: absolute !important;
                bottom: 8mm !important;
                width: auto !important;
                margin: 0 !important;
                padding-top: 5px !important;
                z-index: 50 !important;
                background: #ffffff !important;
                box-sizing: border-box !important;
            }

            .medical-page-footer {
                left: 16mm !important;
                right: 16mm !important;
                border-top: 1.5px solid #000000 !important;
            }

            .page-footer-block {
                left: 14mm !important;
                right: 14mm !important;
                border-top: 1px solid #cbd5e1 !important;
            }

            .watermark-container {
                position: absolute !important;
                top: 50% !important;
                left: 50% !important;
                transform: translate(-50%, -50%) !important;
                width: 480px !important;
                height: 480px !important;
                opacity: 0.12 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            @page {
                size: A4 portrait;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <!-- SCREEN-ONLY FLOATING TOOLBAR -->
    <header class="screen-toolbar no-print">
        <div style="display: flex; align-items: center; gap: 12px;">
           
            <span style="font-family: 'JetBrains Mono', monospace; font-size: 11px; color: #94a3b8;">
                {{ $reportTypeLabel }} &bull; Reference: <strong style="color: #ffffff;">{{ $referenceNumber }}</strong> &bull; {{ $totalCount }} Records
            </span>
        </div>

        <div style="display: flex; align-items: center; gap: 10px;">
            <button type="button" onclick="window.print()" class="btn-print">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Print / Save as PDF</span>
            </button>
            <button type="button" onclick="window.close()" class="btn-close">
                Close
            </button>
        </div>
    </header>

    <div class="document-wrapper">

        <!-- ========================================================
             PAGE 1: EXACT EXECUTIVE COVER PAGE (MATCHES SITREP)
             ======================================================== -->
        <article class="a4-page cover-page page-break relative text-slate-900 select-none">

            <!-- SUBTLE NACOC WATERMARK -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.07] select-none">
                <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[550px] h-[550px] object-contain" />
            </div>

            <div class="page-content-wrapper relative z-10 flex flex-col justify-between h-full min-h-0 flex-1 w-full">

                <!-- TOP BADGES -->
                <div class="pt-2 flex items-center justify-between text-[8px] font-mono-code select-none">
                    <div class="text-left">
                        <p class="font-bold uppercase tracking-wider text-slate-900">REPUBLIC OF GHANA</p>
                    </div>
                    <div class="text-right">
                        <p class="mt-0.5 text-[9px] font-bold uppercase text-slate-800" id="page1HeaderBadge">
                            SITREP COVER &bull; Page 1 of <span class="doc-total-pages">{{ $totalReportPages }}</span>
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
                            LABORATORY ANALYSIS UNIT (LAU)
                        </p>
                    </div>

                    <!-- EMBLEM LOGO IN THE MIDDLE -->
                    <div class="my-6 flex items-center justify-center">
                        <img src="{{ asset('NACOC_LOGO.png') }}" alt="NACOC Emblem Logo" onerror="this.src='{{ asset('nacoc.jfif') }}';" class="w-300 h-300 max-w-[690px] max-h-[690px] object-contain drop-shadow-md" />
                    </div>

                    <!-- CASE FILE & REPORT TITLE -->
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.25em] text-slate-900">
                            OFFICIAL DRUG SCREENING REPORT
                        </p>
                        <h2 class="mt-2 text-2xl font-bold uppercase tracking-wide text-slate-950 font-serif-header">
                            LABORATORY ANALYSIS UNIT
                        </h2>
                        <p class="mt-1 text-sm font-semibold uppercase tracking-[0.14em] text-slate-700">
                            Preliminary Forensic Examination &amp; Drug Testing Report
                        </p>
                        <div class="mx-auto mt-4 h-[2px] w-20 bg-emerald-700"></div>
                        <p class="mt-5 text-base font-extrabold uppercase tracking-wider text-emerald-800" id="coverReportTypeTitle">
                            {{ $reportTypeLabel }}
                        </p>
                        <p class="mt-2 text-[13px] font-extrabold uppercase tracking-wider font-times text-emerald-900" id="coverPeriodDisplay">
                            {{ strtoupper($periodLabel) }}
                        </p>
                        @if(!empty($scopeLabel))
                        <p class="mt-1 text-[10.5px] font-bold uppercase tracking-wider text-slate-700 font-mono-code">
                            {{ $scopeLabel }}
                        </p>
                        @endif
                        <span class="sr-only">DRUG SCREENING SITUATIONAL REPORT</span>
                    </div>

                    <!-- CONFIDENTIAL & PROPRIETARY LAW ENFORCEMENT RECORD BOX -->
                    <div class="mt-8 w-full">
                        <div class="p-4 text-center font-mono-code text-[10px] text-slate-700 space-y-1.5">
                            <p class="font-bold uppercase tracking-wider text-slate-900">
                                CONFIDENTIAL &amp; PROPRIETARY LAW ENFORCEMENT RECORD
                            </p>
                            <p class="leading-relaxed">
                                This official situational report contains laboratory analysis, drug screening results, and chain-of-custody intelligence. Unauthorized distribution, disclosure, or reproduction is strictly prohibited.
                            </p>
                        </div>
                    </div>

                </div>

            </div>

            <!-- COVER PAGE FOOTER: DIRECT CHILD OF COVER-PAGE, LOCKED AT BOTTOM -->
            <footer class="page-footer-block footer select-none">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-700 tracking-wider">NACOC / LAU UNIT</span>
                    <span>&bull;</span>
                    <span>Executive Directory Cover Page</span>
                </div>
                <div class="flex items-center gap-2">
                    <span>STRICTLY CONFIDENTIAL</span>
                    <span>&bull;</span>
                    <span class="font-bold text-slate-800">Page 1 of <span class="doc-total-pages">{{ $totalReportPages }}</span></span>
                </div>
            </footer>

        </article>

        <!-- ========================================================
             BODY PAGES: DETAILED MODERN FORENSIC DOSSIERS
             ======================================================== -->
        @foreach($screeningChunks as $pageIndex => $chunk)
        @php
            $currentPageNum = $pageIndex + 2;
        @endphp
        <article class="a4-page">

            <!-- Faint NACOC Watermark (Strictly 12% Opacity) -->
            <div class="watermark-container">
                <img src="{{ asset('NACOC_LOGO.png') }}" alt="NACOC Watermark" onerror="this.src='{{ asset('nacoc.jfif') }}';">
            </div>

            <div class="page-content">

                <!-- TOP RUNNING FORENSIC HEADER -->
                <header class="modern-running-header">
                    <div class="running-header-left">
                        <div class="running-agency">REPUBLIC OF GHANA &bull; NARCOTICS CONTROL COMMISSION (NACOC)</div>
                        <h1 class="running-title">LABORATORY ANALYSIS UNIT &bull; FORENSIC DEPARTMENT</h1>
                        <div class="running-subtitle">{{ $reportTypeLabel }}{{ $pageIndex > 0 ? ' (CONTINUED)' : '' }}</div>
                    </div>
                    <div class="running-header-right">
                        <div>REF: <strong>{{ $referenceNumber }}</strong></div>
                        <div style="font-size: 7pt; color: #444444;">DATE: {{ date('d/m/Y') }}</div>
                        <div style="font-size: 6.5pt; color: #666666;">PAGE {{ $currentPageNum }} OF {{ $totalReportPages }}</div>
                    </div>
                </header>

                <div>

                    @if($pageIndex === 0)
                    <!-- MODERN EXECUTIVE FORENSIC DASHBOARD CARD -->
                    <div class="executive-dashboard-card">
                        <div class="dashboard-header-strip">
                            <span>NARCOTICS CONTROL COMMISSION </span>
                            
                        </div>
                        <div class="dashboard-kpi-grid">
                            <div class="kpi-tile">
                                <span class="kpi-tile-label">Total Tests</span>
                                <span class="kpi-tile-val">{{ $totalCount }}</span>
                                <span class="kpi-tile-sub">100% Evaluated</span>
                            </div>
                            <div class="kpi-tile">
                                <span class="kpi-tile-label">Positive</span>
                                <span class="kpi-tile-val">{{ $positiveCount }}</span>
                                <span class="kpi-tile-sub">
                                    {{ $totalCount > 0 ? round(($positiveCount / $totalCount) * 100, 1) : 0 }}% Detection
                                </span>
                            </div>
                            <div class="kpi-tile">
                                <span class="kpi-tile-label">Negative</span>
                                <span class="kpi-tile-val">{{ $negativeCount }}</span>
                                <span class="kpi-tile-sub">
                                    {{ $totalCount > 0 ? round(($negativeCount / $totalCount) * 100, 1) : 0 }}% Cleared
                                </span>
                            </div>
                            <div class="kpi-tile">
                                <span class="kpi-tile-label">Pending</span>
                                <span class="kpi-tile-val">{{ $pendingCount }}</span>
                                <span class="kpi-tile-sub">Processing</span>
                            </div>
                            <div class="kpi-tile">
                                <span class="kpi-tile-label">On Medication</span>
                                <span class="kpi-tile-val">{{ $medsCount }}</span>
                                <span class="kpi-tile-sub">Therapeutic</span>
                            </div>
                        </div>
                        <div class="dashboard-meta-strip">
                            <span>{{ $periodLabel }}</span>
                            @if(!empty($scopeLabel))
                            <span>{{ $scopeLabel }}</span>
                            @endif
                            <span>FACILITY: NACOC NATIONAL HQ, ACCRA</span>
                        </div>
                    </div>
                    @endif

                    <!-- SECTION TITLE BAR -->
                    <div class="dossier-section-bar">
                        <h2>Subject Screening Profiles{{ $pageIndex > 0 ? ' (Continued)' : '' }}</h2>
                        <span> {{ ($pageIndex * $itemsPerPage) + 1 }} &ndash; {{ min(($pageIndex + 1) * $itemsPerPage, $totalCount) }} of {{ $totalCount }} Active Records</span>
                    </div>

                    <!-- DETAILED CLINICAL DOSSIER BLOCKS -->
                    <div class="dossier-entries-list">
                        @forelse($chunk as $rIndex => $row)
                        @php
                            $globalIndex = ($pageIndex * $itemsPerPage) + $rIndex + 1;
                            $isCivilian = $row->isCivilian();
                        @endphp
                        <div class="dossier-block">
                            <!-- DOSSIER HEADER BAR -->
                            <div class="dossier-header-bar">
                                <div class="dossier-header-left">
                                    <span class="dossier-badge-idx">PROFILE #{{ str_pad($globalIndex, 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="sr-only">Subject Details</span><span class="dossier-subject-name">{{ $row->person_name }}</span>
                                    <span class="dossier-classification-pill">{{ $isCivilian ? 'CIVILIAN' : 'SUSPECT' }}</span>
                                    @if($row->suspect && $row->suspect->suspect_alias)
                                        <span class="dossier-alias-tag">"{{ $row->suspect->suspect_alias }}"</span>
                                    @endif
                                </div>
                                <div class="dossier-header-right">
                                    @if($row->suspect && $row->suspect->ghana_card)
                                        <span>GH-ID: <strong>{{ $row->suspect->ghana_card }}</strong></span>
                                        <span style="color: #999999;">&bull;</span>
                                    @endif
                                    <span>REF: <strong>{{ $row->case_reference }}</strong></span>
                                </div>
                            </div>

                            <!-- DOSSIER BODY CONTENT -->
                            <div class="dossier-inner-content">
                                
                                <!-- 1. DEMOGRAPHIC & TESTING INTAKE TABLE -->
                                <table class="dossier-details-table">
                                    <tbody>
                                        <tr>
                                            <td class="lbl">Subject Name:</td>
                                            <td class="val" style="font-size: 8.5pt; font-weight: 800;">{{ $row->person_name }}</td>
                                            <td class="lbl">Classification:</td>
                                            <td class="val">
                                                {{ $isCivilian ? 'Civilian (Non-Custody Intake)' : ($row->suspect->case_status ?? ($row->case_status ?? 'Under Investigation')) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="lbl">Date of Screen:</td>
                                            <td class="val font-mono-code">{{ $row->screening_date ? $row->screening_date->format('d M Y') : '—' }}</td>
                                            <td class="lbl">Purpose:</td>
                                            <td class="val">{{ $row->purpose ?: 'Unspecified' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="lbl">Docket Reference:</td>
                                            <td class="val font-mono-code">{{ $row->case_reference }}</td>
                                            
                                        </tr>
                                    </tbody>
                                </table>

                                <!-- 2. TOXICOLOGICAL ASSAY FINDINGS & QUALITATIVE ANALYSIS (IMAGE 2 STYLE) -->
                                <div class="dossier-assay-box">
                                    <div class="assay-top-row">
                                        <span class="assay-title-text">SCREENING RESULTS:</span>
                                        <div class="assay-checkbox-group">
                                            <div class="assay-chk-item">
                                                <span class="chk-box">{{ $row->isPositive() ? 'X' : '' }}</span>
                                                <span style="{{ $row->isPositive() ? 'font-weight: 900;' : '' }}">POSITIVE</span>
                                            </div>
                                            <div class="assay-chk-item">
                                                <span class="chk-box">{{ $row->isNegative() ? 'X' : '' }}</span>
                                                <span style="{{ $row->isNegative() ? 'font-weight: 900;' : '' }}">NEGATIVE</span>
                                            </div>
                                            <div class="assay-chk-item">
                                                <span class="chk-box">{{ $row->isPending() ? 'X' : '' }}</span>
                                                <span style="{{ $row->isPending() ? 'font-weight: 900;' : '' }}">PENDING</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="assay-finding-details">
                                        <div class="finding-row">
                                            <span class="finding-lbl">Identified Substances:</span>
                                            <span class="finding-val substances">
                                                @if($row->isPositive())
                                                    {{ $row->substances_found ?: 'Unspecified Narcotic / Psychotropic Compound(s)' }}
                                                @elseif($row->isNegative())
                                                    {{ $row->substances_found ?: 'No drug(s) detected' }}
                                                @else
                                                    Assay Under Processing
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 3. DECLARED CLINICAL MEDICATIONS (IF ON MEDICATION) -->
                                @if($row->on_medication)
                                <div class="dossier-meds-section">
                                    <div class="dossier-meds-header">
                                        <span>Declared Clinical Medications &amp; Prescription Schedule:</span>
                                        <span>Active Intake Recorded</span>
                                    </div>
                                    @if($row->medications && $row->medications->count() > 0)
                                    <table class="dossier-meds-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%; text-align: center;">#</th>
                                                <th style="width: 25%;">Medication Name</th>
                                                <th style="width: 15%;">Dosage</th>
                                                <th style="width: 18%;">Form &amp; Route</th>
                                                <th style="width: 17%;">Frequency</th>
                                                <th style="width: 20%;">Prescription Source</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($row->medications as $mIdx => $m)
                                            <tr>
                                                <td class="font-mono-code" style="text-align: center;">{{ $mIdx + 1 }}</td>
                                                <td style="font-weight: 700;">{{ $m->medication_name }}</td>
                                                <td class="font-mono-code">{{ $m->dosage ?: '—' }}</td>
                                                <td>{{ $m->form ?: '—' }}{{ $m->route ? ' (' . $m->route . ')' : '' }}</td>
                                                <td>{{ $m->frequency ?: '—' }}</td>
                                                <td>{{ $m->source_label }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @else
                                    <div style="font-size: 7.5pt; color: #333333; font-weight: 600;">
                                        {{ $row->medication_summary ?: 'Subject officially declared active therapeutic medication intake; schedule verified by screening officer.' }}
                                    </div>
                                    @endif
                                </div>
                                @endif

                                <!-- 4. CLINICAL OBSERVATIONS & NOTES (IF RECORDED) -->
                                @if(!empty($row->notes))
                                <div class="dossier-notes-section">
                                    <span class="notes-lbl">Clinical Observations:</span>
                                    <span class="notes-text">{{ $row->notes }}</span>
                                </div>
                                @endif

                            </div>
                        </div>
                        @empty
                        <div class="dossier-empty-state">
                            <div class="empty-title">No Drug Screening Records Found</div>
                            <div class="empty-desc">No registered screening records match the selected scope, period, or outcome filters.</div>
                        </div>
                        @endforelse
                    </div>

                </div>

            </div>

            <!-- MEDICAL DOCUMENT FOOTER: DIRECT CHILD OF A4-PAGE, STATICALLY LOCKED AT BOTTOM -->
            <footer class="medical-page-footer">
                <div>
                    <span>NACOC FORENSIC LABORATORY</span>
                    <span style="margin: 0 4px;">&bull;</span>
                    <span>REF: {{ $referenceNumber }}</span>
                    @if(!empty($scopeLabel))
                    <span style="margin: 0 4px;">&bull;</span>
                    <span>{{ $scopeLabel }}</span>
                    @endif
                </div>
                <div>
                    <span>PAGE <span class="doc-page-num" data-page-index="{{ $currentPageNum }}">{{ $currentPageNum }}</span> OF <span class="doc-total-pages">{{ $totalReportPages }}</span> &bull; TOXICOLOGY REGISTER</span>
                </div>
            </footer>

        </article>
        @endforeach

    </div>

    <script>
        function updateDynamicPageNumbers() {
            try {
                const pages = document.querySelectorAll('.a4-page');
                const total = pages.length;
                pages.forEach(function (page, index) {
                    const currentPage = index + 1;
                    const pageNumEls = page.querySelectorAll('.doc-page-num');
                    pageNumEls.forEach(function (el) { el.textContent = currentPage; });
                    const totalPageEls = page.querySelectorAll('.doc-total-pages');
                    totalPageEls.forEach(function (el) { el.textContent = total; });
                });
            } catch (e) {
                console.warn('Page number calculation notice:', e);
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', updateDynamicPageNumbers);
        } else {
            updateDynamicPageNumbers();
        }
        window.addEventListener('beforeprint', updateDynamicPageNumbers);
    </script>

    @if(request('auto_print') == '1')
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            setTimeout(function () {
                window.print();
            }, 450);
        });
    </script>
    @endif

</body>
</html>
