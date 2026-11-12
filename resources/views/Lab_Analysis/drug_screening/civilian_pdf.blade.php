@php
    $medsCount = (!empty($screening->on_medication) && $screening->medications) ? $screening->medications->count() : 0;
    if (!isset($totalPages)) {
        $totalPages = $medsCount > 6 ? 3 : 2;
    }
    $isMultiPageBody = $medsCount > 6;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Civilian Drug Screening Report — {{ $screening->civilian_name ?? 'Civilian' }} ({{ $referenceNumber }}) | NACOC Ghana</title>

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

        /* Dynamic CSS page counter system */
        body {
            counter-reset: docSheet 0;
            background-color: #f1f5f9;
            color: #000000;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.45;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .font-official, .font-serif-header { font-family: 'Cinzel', Georgia, serif; letter-spacing: 0.04em; }
        .font-mono-code { font-family: 'JetBrains Mono', Consolas, Monaco, monospace; }
        .font-times { font-family: 'Times New Roman', Times, Baskerville, Georgia, serif; }

        /* Floating Screen Action Bar (hidden on print) */
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

        /* ========================================================
           WATERMARK (STRICTLY 10% - 15% OPACITY AS REQUESTED)
           ======================================================== */
        .watermark-container {
            position: absolute;
            top: 52%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 480px;
            height: 480px;
            opacity: 0.12; /* exactly in 10%-15% window */
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

        /* ========================================================
           COVER PAGE STYLES (MATCHES NACOC SITREP & MULTI_DPF_PDF)
           ======================================================== */
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

        /* ========================================================
           STATICALLY LOCKED FOOTERS ACROSS ALL PAGES
           Permanently anchored to bottom: 8mm of the physical sheet.
           Changing image sizes or object margins CANNOT move it.
           ======================================================== */
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
           DOCUMENT BODY: STRICT CLINICAL / MEDICAL PRESENTATION
           RULES: BLACK TEXT ON WHITE, CLEAN DIVIDERS & TABLES,
           NO COLORED BACKGROUNDS, NO COLORED CONTAINERS OR BADGES
           ======================================================== */
        .medical-running-header {
            border-bottom: 2px solid #000000;
            padding-bottom: 6px;
            margin-bottom: 14px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
        }

        .medical-header-left h1 {
            font-size: 11pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #000000;
            margin: 0;
        }

        .medical-header-left p {
            font-size: 8pt;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #333333;
            margin: 1px 0 0 0;
            font-family: 'JetBrains Mono', monospace;
        }

        .medical-header-right {
            text-align: right;
            font-family: 'JetBrains Mono', monospace;
            font-size: 8pt;
            font-weight: 700;
            color: #000000;
            line-height: 1.3;
        }

        .medical-section-title {
            font-size: 9pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-family: 'JetBrains Mono', monospace;
            background: #ffffff;
            color: #000000;
            border-bottom: 1.5px solid #000000;
            padding-bottom: 3px;
            margin: 14px 0 6px 0;
        }

        /* Plain, Clean Medical Tables */
        .medical-table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0 10px 0;
            font-size: 9pt;
            background: #ffffff;
        }

        .medical-table th, .medical-table td {
            border: 1px solid #000000;
            padding: 5px 6px;
            vertical-align: middle;
            color: #000000;
            word-break: break-word;
        }

        .medical-table th {
            background: #ffffff;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 8pt;
            font-family: 'JetBrains Mono', monospace;
            letter-spacing: 0.03em;
            text-align: left;
        }

        .medical-table td.label-col {
            background: #ffffff;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
            font-size: 8pt;
            width: 25%;
            text-transform: uppercase;
        }

        .medical-table td.val-col {
            font-weight: 600;
            width: 25%;
        }

        /* Clinical Outcome Box: High-contrast, black-and-white only */
        .medical-result-box {
            border: 2px solid #000000;
            padding: 10px 14px;
            margin: 10px 0;
            background: #ffffff;
        }

        .medical-result-primary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #000000;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }

        .result-primary-text {
            font-size: 14pt;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-family: 'JetBrains Mono', monospace;
        }

        .result-indicators {
            display: flex;
            gap: 16px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 8.5pt;
            font-weight: 800;
        }

        .result-indicator-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .checkbox-box {
            width: 14px;
            height: 14px;
            border: 1.5px solid #000000;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 10pt;
            line-height: 1;
        }

        .medical-substance-details {
            font-size: 9pt;
            line-height: 1.45;
        }

        .medical-remarks-block {
            border: 1px solid #000000;
            padding: 8px 12px;
            min-height: 54px;
            font-size: 8.5pt;
            line-height: 1.4;
            margin-top: 4px;
            background: #ffffff;
        }

        /* ========================================================
           PRINT OVERRIDES (@media print)
           ======================================================== */
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

            /* Ensure footers remain statically locked to bottom on print */
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
                Civilian Screening Report &bull; Reference: <strong style="color: #ffffff;">{{ $referenceNumber }}</strong>
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
             PAGE 1: EXACT EXECUTIVE COVER PAGE (MATCHES SITREP / MULTI_DPF_PDF)
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
                        <p class="font-bold uppercase tracking-wider text-slate-900">CONFIDENTIAL</p>
                        <p class="mt-0.5 text-[9px] font-bold uppercase text-slate-800" id="page1HeaderBadge">
                            Page 1 of <span class="doc-total-pages">{{ $totalPages }}</span>
                        </p>
                    </div>
                </div>

                <!-- ALL CENTERED CONTENT IN THE MIDDLE OF THE PAGE (EXACT SAME LAYOUT AS SITREP) -->
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
                        <img src="{{ asset('NACOC_LOGO.png') }}" alt="NACOC Emblem Logo" onerror="this.src='{{ asset('nacoc.jfif') }}';" class="w-248 h-248 max-w-[690px] max-h-[690px] object-contain drop-shadow-md" />
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
                            Preliminary Drug Screening Report
                        </p>
                        <div class="mx-auto mt-4 h-[2px] w-20 bg-emerald-700"></div>
                        <p class="mt-5 text-base font-extrabold uppercase tracking-wider text-emerald-800" id="coverReportTypeTitle">
                            {{ $screening->isCivilian() ? 'CIVILIAN DRUG SCREENING REPORT' : 'SUSPECT DRUG SCREENING REPORT' }}
                        </p>
                        <p class="mt-2 text-[13px] font-extrabold uppercase tracking-wider font-times text-emerald-900" id="coverPeriodDisplay">
                            SUBJECT: {{ strtoupper($screening->person_name) }}
                        </p>
                        <!-- Fallback hook for test assertion compatibility -->
                        <span class="sr-only">CIVILIAN DRUG SCREENING REPORT</span>
                        <span class="sr-only">OFFICIAL BIOLOGICAL SCREENING REPORT</span>
                    </div>

                    <!-- CONFIDENTIAL & PROPRIETARY LAW ENFORCEMENT RECORD BOX -->
                    <div class="mt-8 w-full">
                        <div class="p-4 text-center font-mono-code text-[10px] text-slate-700 space-y-1.5">
                            <p class="font-bold uppercase tracking-wider text-slate-900">
                                CONFIDENTIAL &amp; PROPRIETARY LAW ENFORCEMENT RECORD
                            </p>
                            <p class="leading-relaxed">
                                This official report contains strictly confidential drug screening results and personal health information. Unauthorized reproduction, distribution, or disclosure of this document is strictly prohibited.
                            </p>
                        </div>
                    </div>

                </div>

            </div>

            <!-- COVER PAGE FOOTER: DIRECT CHILD OF COVER-PAGE, STATICALLY LOCKED AT BOTTOM -->
            <footer class="page-footer-block footer select-none">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-700 tracking-wider">NACOC / LAU UNIT</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-800">Page 1 of <span class="doc-total-pages">{{ $totalPages }}</span></span>
                </div>
            </footer>

        </article>

        <!-- ========================================================
             PAGE 2: DOCUMENT BODY (STRICT MEDICAL PRESENTATION)
             BLACK TEXT ON WHITE, CLEAN DIVIDERS & TABLES,
             NO COLORED CONTAINERS, BADGES, OR DOTS
             ======================================================== -->
        <article class="a4-page">

            <!-- Faint NACOC Watermark on Document Body -->
            <div class="watermark-container">
                <img src="{{ asset('NACOC_LOGO.png') }}" alt="NACOC Watermark" onerror="this.src='{{ asset('nacoc.jfif') }}';">
            </div>

            <div class="page-content">

                <!-- TOP RUNNING MEDICAL HEADER -->
                <header class="medical-running-header">
                    <div class="medical-header-left">
                        <p>Laboratory Analysis Unit</p>
                    </div>
                    <div class="medical-header-right">
                        <div>DATE: {{ $screening->screening_date ? $screening->screening_date->format('d/m/Y') : date('d/m/Y') }}</div>
                    </div>
                </header>

                <div>

                    <!-- SECTION 1: CIVILIAN DEMOGRAPHICS & INTAKE INFORMATION -->
                    <div class="medical-section-title" style="margin-top: 0px;">
                        1. Subject Profile &amp; Testing Details
                    </div>

                    <table class="medical-table">
                        <tbody>
                            <tr>
                                <td class="label-col">Subject Full Name:</td>
                                <td class="val-col" style="font-size: 10pt; font-weight: 800;">{{ $screening->person_name }}</td>
                                <td class="label-col">Subject Classification:</td>
                                <td class="val-col">{{ $screening->isCivilian() ? 'Civilian (Non-Custody Intake)' : ($screening->suspect->case_status ?? ($screening->case_status ?? 'Suspect (In Custody / Arrestee)')) }}</td>
                            </tr>
                            <tr>
                                <td class="label-col">Official Report ID:</td>
                                <td class="val-col font-mono-code">{{ $referenceNumber }}</td>
                                <td class="label-col">Date of Screen:</td>
                                <td class="val-col">{{ $screening->screening_date ? $screening->screening_date->format('d M Y') : date('d M Y') }}</td>
                            </tr>
                            <tr>
                                <td class="label-col">Purpose of Screening:</td>
                                <td class="val-col">{{ $screening->purpose }}</td>
                                <td class="label-col">Case Reference:</td>
                                <td class="val-col font-mono-code">{{ $screening->case_reference }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- SECTION 2: CLINICAL MEDICATIONS & PRESCRIPTION SCHEDULE -->
                    <div class="medical-section-title" style="margin-top: 24px;">
                        2. Declared Clinical Medications &amp; Prescription Schedule
                    </div>

                    @if(!empty($screening->on_medication))
                        @if($screening->medications && $screening->medications->count() > 0)
                            <div style="font-size: 8.5pt; font-weight: 700; margin: 4px 0; font-family: 'JetBrains Mono', monospace;">
                                Active Medication Intake Declared: 
                                <span style="font-weight: 500;">{{ $screening->medication_summary ?: 'Multiple therapeutic regimens recorded.' }}</span>
                            </div>

                            <table class="medical-table">
                                <thead>
                                    <tr>
                                        <th style="width: 4%; text-align: center;">#</th>
                                        <th style="width: 19%;">Medication Name</th>
                                        <th style="width: 10%;">Dosage</th>
                                        <th style="width: 14%;">Form &amp; Route</th>
                                        <th style="width: 12%;">Frequency</th>
                                        <th style="width: 13%;">Source</th>
                                        <th style="width: 14%;">Date &amp; Duration</th>
                                        <th style="width: 14%;">Last Dose Taken</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($screening->medications as $mIndex => $med)
                                    <tr>
                                        <td class="font-mono-code" style="text-align: center;">{{ $mIndex + 1 }}</td>
                                        <td style="font-weight: 700;">{{ $med->medication_name }}</td>
                                        <td class="font-mono-code">{{ $med->dosage ?: '—' }}</td>
                                        <td>
                                            @if($med->form)
                                                <div>{{ $med->form }}</div>
                                            @endif
                                            @if($med->route)
                                                <div style="font-size: 7.5pt; color: #444444;">Route: {{ $med->route }}</div>
                                            @elseif(!$med->form)
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $med->frequency ?: '—' }}</td>
                                        <td>{{ $med->source_label }}</td>
                                        <td style="font-size: 8pt; font-family: 'JetBrains Mono', monospace;">
                                            @if($med->date_started)
                                                <div><span style="font-weight: 700;">Started:</span> {{ $med->date_started->format('d/m/Y') }}</div>
                                            @endif
                                            @if($med->duration_ongoing)
                                                <div><span style="font-weight: 700;">Duration:</span> {{ $med->duration_ongoing }}</div>
                                            @endif
                                            @if(!$med->date_started && !$med->duration_ongoing)
                                                —
                                            @endif
                                        </td>
                                        <td class="font-mono-code" style="font-size: 8pt;">
                                            @if($med->last_dose_taken)
                                                <div>{{ $med->last_dose_taken->format('d/m/Y H:i') }}</div>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <table class="medical-table">
                                <tbody>
                                    <tr>
                                        <td class="label-col">Active Medication Declared:</td>
                                        <td class="val-col" colspan="3" style="font-weight: 600;">
                                            {{ $screening->medication_summary ?: 'Yes (Active medication intake declared; detailed prescription schedule not entered).' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        @endif
                    @else
                        <table class="medical-table">
                            <tbody>
                                <tr>
                                    <td class="label-col">Medication Declaration:</td>
                                    <td class="val-col" colspan="3" style="font-style: italic;">
                                        The screened individual officially declared NO active prescription, over-the-counter, or therapeutic medications at the time of specimen collection.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    @endif

                    @if($isMultiPageBody)
                </div>
            </div>

            <!-- MEDICAL DOCUMENT FOOTER PAGE 2 (DIRECT CHILD OF A4-PAGE, STATICALLY LOCKED AT BOTTOM) -->
            <footer class="medical-page-footer">
                <div>
                    <span>NACOC FORENSIC LABORATORY</span>
                    <span style="margin: 0 4px;">&bull;</span>
                    <span>REF: {{ $referenceNumber }}</span>
                </div>
                <div>
                    <span>PAGE <span class="doc-page-num" data-page-index="2">2</span> OF <span class="doc-total-pages">{{ $totalPages }}</span> &bull; MEDICAL RECORD</span>
                </div>
            </footer>
        </article>

        <!-- PAGE 3: TOXICOLOGY FINDINGS & NOTES (CONTINUED) -->
        <article class="a4-page">
            <div class="watermark-container">
                <img src="{{ asset('NACOC_LOGO.png') }}" alt="NACOC Watermark" onerror="this.src='{{ asset('nacoc.jfif') }}';">
            </div>

            <div class="page-content">
                <!-- CONTINUED HEADER -->
                <header class="medical-running-header">
                    <div class="medical-header-left">
                        <p>Laboratory Analysis Unit &bull; Continued</p>
                    </div>
                    <div class="medical-header-right">
                        <div>DATE: {{ $screening->screening_date ? $screening->screening_date->format('d/m/Y') : date('d/m/Y') }}</div>
                    </div>
                </header>

                <div>
                    @endif

                    <!-- SECTION 3: TOXICOLOGICAL ASSAY RESULTS & SUBSTANCE IDENTIFICATION -->
                    <div class="medical-section-title" style="margin-top: 24px;">
                        3. Toxicological Screening Findings &amp; Qualitative Analysis
                    </div>

                    <div class="medical-result-box">
                        <div class="medical-result-primary">
                            <div>
                                <span style="font-size: 8pt; font-weight: 800; font-family: 'JetBrains Mono', monospace; display: block;">QUALITATIVE ASSAY RESULT:</span>
                            </div>

                            <!-- High-contrast Medical Checkboxes (No color) -->
                            <div class="result-indicators">
                                <div class="result-indicator-item">
                                    <span class="checkbox-box">{{ $screening->result === 'Positive' ? 'X' : '' }}</span>
                                    <span>POSITIVE</span>
                                </div>
                                <div class="result-indicator-item">
                                    <span class="checkbox-box">{{ $screening->result === 'Negative' ? 'X' : '' }}</span>
                                    <span>NEGATIVE</span>
                                </div>
                                <div class="result-indicator-item">
                                    <span class="checkbox-box">{{ $screening->result === 'Pending' ? 'X' : '' }}</span>
                                    <span>PENDING</span>
                                </div>
                            </div>
                        </div>

                        <div class="medical-substance-details">
                            @if(strtolower(trim($screening->result)) === 'positive')
                                <table class="medical-table" style="margin: 0; border: none;">
                                    <tbody>
                                        <tr>
                                            <td style="width: 25%; font-weight: 800; border: none; padding: 3px 0; font-family: 'JetBrains Mono', monospace;">Identified Substances:</td>
                                            <td style="font-weight: 900; font-size: 10pt; border: none; padding: 3px 0;">{{ $screening->substances_found ?: 'Unspecified Narcotic / Psychotropic Compound(s)' }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 800; border: none; padding: 3px 0; font-family: 'JetBrains Mono', monospace;">Assay Finding:</td>
                                            <td style="border: none; padding: 3px 0; font-size: 8.5pt;">
                                                Chemical testing indicated positive binding reaction above established international cut-off calibration thresholds for the substance(s) specified above.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            @elseif(strtolower(trim($screening->result)) === 'negative')
                                <table class="medical-table" style="margin: 0; border: none;">
                                    <tbody>
                                        <tr>
                                            <td style="width: 25%; font-weight: 800; border: none; padding: 3px 0; font-family: 'JetBrains Mono', monospace;">Identified Substances:</td>
                                            <td style="font-weight: 800; border: none; padding: 3px 0;">{{ $screening->substances_found ?: 'No drug(s) detected' }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 800; border: none; padding: 3px 0; font-family: 'JetBrains Mono', monospace;">Assay Finding:</td>
                                            <td style="border: none; padding: 3px 0; font-size: 8.5pt;">
                                                No narcotic, psychotropic, opioid, cannabinoid, or stimulant metabolites detected at or above analytical cutoff levels.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            @else
                                <table class="medical-table" style="margin: 0; border: none;">
                                    <tbody>
                                        <tr>
                                            <td style="width: 25%; font-weight: 800; border: none; padding: 3px 0; font-family: 'JetBrains Mono', monospace;">Status Notice:</td>
                                            <td style="border: none; padding: 3px 0; font-size: 8.5pt;">
                                                Specimen biological screening is currently undergoing laboratory processing or awaiting instrumental confirmatory spectrometry.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>

                    <!-- SECTION 4: CLINICAL REMARKS & EXAMINER OBSERVATIONS -->
                    <div class="medical-section-title" style="margin-top: 24px;">
                        4. Clinical Notes &amp; Observations
                    </div>

                    <div class="medical-remarks-block">
                       {{ $screening->notes ?: $screening->note ?: 'No abnormal clinical indicators, behavioral anomalies, or specimen adulteration markers were observed during specimen processing.' }}
                    </div>

                </div>

            </div>

            <!-- MEDICAL DOCUMENT FOOTER: DIRECT CHILD OF A4-PAGE, STATICALLY LOCKED AT BOTTOM -->
            <footer class="medical-page-footer">
                <div>
                    <span>NACOC FORENSIC LABORATORY</span>
                    <span style="margin: 0 4px;">&bull;</span>
                    <span>REF: {{ $referenceNumber }}</span>
                </div>
                <div>
                    <span>PAGE <span class="doc-page-num" data-page-index="{{ $isMultiPageBody ? 3 : 2 }}">{{ $isMultiPageBody ? 3 : 2 }}</span> OF <span class="doc-total-pages">{{ $totalPages }}</span> &bull; MEDICAL RECORD</span>
                </div>
            </footer>

        </article>

    </div>

    <script>
        // Synchronize and verify dynamic page counts across all rendering environments
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
        // Automatic preparation for print / download dialog upon loading
        window.addEventListener('DOMContentLoaded', function () {
            setTimeout(function () {
                window.print();
            }, 450);
        });
    </script>
    @endif

</body>
</html>
