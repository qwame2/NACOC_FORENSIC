@php
    $logoPath = public_path('NACOC_LOGO.png');
    $logoBase64 = file_exists($logoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : asset('NACOC_LOGO.png');

    $fontRegularPath = public_path('font/Arial-Narrow-Font.ttf');
    $fontRegularSrc = file_exists($fontRegularPath)
        ? 'data:font/truetype;charset=utf-8;base64,' . base64_encode(file_get_contents($fontRegularPath))
        : asset('font/Arial-Narrow-Font.ttf');

    $fontBoldPath = public_path('font/Arial-Narrow-Bold.ttf');
    $fontBoldSrc = file_exists($fontBoldPath)
        ? 'data:font/truetype;charset=utf-8;base64,' . base64_encode(file_get_contents($fontBoldPath))
        : asset('font/Arial-Narrow-Bold.ttf');

    $suspectNameVal = trim((string) ($expellingData['suspect_name'] ?? ''));
    $nationalityVal = trim((string) ($expellingData['nationality'] ?? ''));
    $passportVal = trim((string) ($expellingData['passport_number'] ?? ''));
    $placeOfArrestVal = trim((string) ($expellingData['place_of_arrest'] ?? ''));
    $dateOfArrestVal = trim((string) ($expellingData['date_of_arrest'] ?? ''));
    $rawTimeOfArrest = trim((string) ($expellingData['time_of_arrest'] ?? ''));
    $timeOfArrestVal = $rawTimeOfArrest;
    if ($rawTimeOfArrest !== '') {
        try {
            $timeOfArrestVal = \Carbon\Carbon::parse($rawTimeOfArrest)->format('g:i A');
        } catch (\Throwable $e) {
            $timeOfArrestVal = $rawTimeOfArrest;
        }
    }
    $arrestOfficersVal = trim((string) ($expellingData['arrest_officers'] ?? ''));
    $flightNoVal = trim((string) ($expellingData['flight_number'] ?? ''));
    $airlineVal = trim((string) ($expellingData['airline'] ?? ''));
    $destinationVal = trim((string) ($expellingData['destination'] ?? ''));
    $substanceExpelledVal = trim((string) ($expellingData['substance_expelled'] ?? ''));
    $caseNumberVal = trim((string) ($expellingData['case_number'] ?? ''));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suspect Expelling Report{{ $caseNumberVal ? ' — ' . $caseNumberVal : '' }} | NACOC Forensics</title>
    <style>
        @font-face {
            font-family: 'Arial-Narrow';
            src: url('{{ $fontRegularSrc }}') format('truetype');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Arial-Narrow';
            src: url('{{ $fontBoldSrc }}') format('truetype');
            font-weight: 700;
            font-style: normal;
            font-display: swap;
        }
        @page {
            size: A4 portrait;
            margin: 12mm 14mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 0;
            background: #e2e8f0;
            color: #000000;
            font-family: 'Arial-Narrow', sans-serif;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .screen-toolbar {
            position: sticky;
            top: 0;
            z-index: 50;
            background: #f1f5f9;
            border-bottom: 1px solid #cbd5e1;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            font-family: system-ui, -apple-system, sans-serif;
        }
        .toolbar-title {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
        }
        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-print {
            background: #059669;
            color: #ffffff;
            border: 1px solid #047857;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }
        .btn-print:hover {
            background: #047857;
        }
        .btn-close {
            background: #e2e8f0;
            color: #1e293b;
            border: 1px solid #cbd5e1;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }
        .sheet {
            position: relative;
            width: 210mm;
            min-height: 297mm;
            margin: 18px auto 28px auto;
            padding: 14mm 16mm 16mm 16mm;
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.14);
            overflow: hidden;
        }
        /* Colored NACOC logo watermark */
        .watermark-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            z-index: 1;
        }
        .watermark-overlay img {
            width: 920px;
            height: 920px;
            max-width: none;
            max-height: none;
            object-fit: contain;
            opacity: 0.16;
        }
        .sheet-content {
            position: relative;
            z-index: 2;
            color: #000000;
            font-family: 'Arial-Narrow', sans-serif;
        }
        /* Header: Logo Left, Institutional Title Middle */
        .doc-header {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-bottom: 10px;
        }
        .doc-logo {
            width: 136px;
            height: 136px;
            object-fit: contain;
            flex-shrink: 0;
        }
        .doc-header-center {
            flex: 1;
            text-align: center;
            padding-right: 60px;
        }
        .doc-org {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.03em;
            color: #000000;
            line-height: 1.25;
        }
        .doc-dept {
            font-size: 21px;
            font-weight: 700;
            letter-spacing: 0.04em;
            color: #000000;
            margin-top: 2px;
        }
        .doc-unit {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: #000000;
            margin-top: 2px;
        }
        .report-title-wrap {
            margin-top: 22px;
            margin-bottom: 28px;
            text-align: center;
        }
        .report-title {
            display: inline-block;
            font-size: 18.5px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            text-decoration: none;
            color: #000000;
        }
        .case-ref-note {
            text-align: right;
            font-size: 13px;
            font-weight: 700;
            color: #000000;
            margin-bottom: 22px;
        }
        .field-row {
            display: flex;
            align-items: baseline;
            gap: 14px;
            margin-bottom: 11px;
            font-size: 15.5px;
            line-height: 1.35;
            color: #000000;
        }
        .field-group {
            display: flex;
            align-items: baseline;
            flex: 1;
            min-width: 0;
        }
        .field-group-compact {
            display: flex;
            align-items: baseline;
            flex: 0.85;
            min-width: 0;
        }
        .field-label {
            font-weight: 700;
            white-space: nowrap;
            margin-right: 4px;
            color: #000000;
        }
        .field-line {
            flex: 1;
            border-bottom: 1.5px dotted #000000;
            min-height: 17px;
            padding: 0 6px 1px 6px;
            font-weight: 400;
            color: #000000;
            word-break: break-word;
        }
        /* Expelling Table */
        .expelling-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            margin-bottom: 18px;
            font-size: 13px;
            color: #000000;
        }
        .expelling-table th,
        .expelling-table td {
            border: 1.25px solid #000000;
            padding: 6px 5px;
            vertical-align: middle;
            color: #000000;
        }
        .expelling-table th {
            text-align: center;
            font-weight: 700;
            font-size: 12px;
            line-height: 1.25;
            background: rgba(255, 255, 255, 0.72);
            color: #000000;
        }
        .expelling-table tbody td {
            height: 28px;
        }
        /* Preliminary-style 2-Column Signatory Tables */
        .doc-section-title {
            font-size: 14pt;
            font-weight: 700;
            text-transform: uppercase;
            color: #000000;
            margin-top: 16px;
            margin-bottom: 6px;
        }
        .doc-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .doc-table td {
            border: 1.25px solid #000000;
            padding: 6px 10px;
            color: #000000;
            background: rgba(255, 255, 255, 0.72);
        }
        .doc-table-2col tbody td:first-child {
            width: 38%;
            font-size: 13pt;
            font-weight: 700;
        }
        .doc-table-2col tbody td:last-child {
            width: 62%;
            font-weight: 400;
        }
        .page-break-sheet {
            page-break-before: always;
            break-before: page;
        }
        @media print {
            body {
                background: #ffffff;
            }
            .screen-toolbar {
                display: none !important;
            }
            .sheet {
                margin: 0;
                padding: 0;
                width: 100%;
                min-height: 270mm;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="screen-toolbar">
        <div class="toolbar-title">
            NACOC Forensics &mdash; Suspect Expelling Report{{ $caseNumberVal ? ' (' . $caseNumberVal . ')' : '' }}
        </div>
        <div class="toolbar-actions">
            <button type="button" class="btn-print" onclick="window.print()">Print / Save PDF</button>
            <button type="button" class="btn-close" onclick="window.close()">Close</button>
        </div>
    </div>

    <div class="sheet">
        <div class="watermark-overlay">
            <img src="{{ $logoBase64 }}" alt="NACOC Watermark">
        </div>

        <div class="sheet-content">
            <!-- HEADER: NACOC LOGO LEFT + TEXT MIDDLE -->
            <div class="doc-header">
                <img src="{{ $logoBase64 }}" alt="NACOC Logo" class="doc-logo">
                <div class="doc-header-center">
                    <div class="doc-org">NARCOTICS CONTROL COMMISSION (NACOC)</div>
                    <div class="doc-dept">FORENSICS DEPARTMENT</div>
                    <div class="doc-unit">LABORATORY ANALYSIS UNIT</div>
                </div>
            </div>

            <!-- SPACE THEN CENTERED LABEL -->
            <div class="report-title-wrap">
                <div class="report-title">SUSPECT EXPELLING REPORT</div>
            </div>

            @if($caseNumberVal !== '')
                <div class="case-ref-note">Case Reference: {{ $caseNumberVal }}</div>
            @endif

            <!-- SUSPECT & FLIGHT DETAILS -->
            <div class="field-row">
                <div class="field-group">
                    <span class="field-label">Name of Suspect:</span>
                    <span class="field-line">{{ $suspectNameVal }}</span>
                </div>
                <div class="field-group-compact">
                    <span class="field-label">Nationality:</span>
                    <span class="field-line">{{ $nationalityVal }}</span>
                </div>
            </div>

            <div class="field-row">
                <div class="field-group">
                    <span class="field-label">Date of Arrest:</span>
                    <span class="field-line">{{ $dateOfArrestVal }}</span>
                </div>
                <div class="field-group-compact">
                    <span class="field-label">Time of Arrest:</span>
                    <span class="field-line">{{ $timeOfArrestVal }}</span>
                </div>
            </div>

            <div class="field-row">
                <div class="field-group">
                    <span class="field-label">Place of Arrest:</span>
                    <span class="field-line">{{ $placeOfArrestVal }}</span>
                </div>
                <div class="field-group-compact">
                    <span class="field-label">Arresting Officer(s):</span>
                    <span class="field-line">{{ $arrestOfficersVal }}</span>
                </div>
            </div>

            <div class="field-row">
                <div class="field-group">
                    <span class="field-label">Passport No.:</span>
                    <span class="field-line">{{ $passportVal }}</span>
                </div>
            </div>

            <div class="field-row">
                <div class="field-group">
                    <span class="field-label">Flight No.:</span>
                    <span class="field-line">{{ $flightNoVal }}</span>
                </div>
            </div>

            <div class="field-row">
                <div class="field-group">
                    <span class="field-label">Airline:</span>
                    <span class="field-line">{{ $airlineVal }}</span>
                </div>
            </div>

            <div class="field-row">
                <div class="field-group">
                    <span class="field-label">Destination:</span>
                    <span class="field-line">{{ $destinationVal }}</span>
                </div>
            </div>

            <div class="field-row" style="margin-bottom: 24px;">
                <div class="field-group">
                    <span class="field-label">Substance Expelled:</span>
                    <span class="field-line">{{ $substanceExpelledVal }}</span>
                </div>
            </div>

            <!-- EXPELLING LOG TABLE -->
            <table class="expelling-table">
                <thead>
                    <tr>
                        <th style="width: 11%;">DATE</th>
                        <th style="width: 10%;">TIME</th>
                        <th style="width: 13%;">NO. OF<br>PELLETS<br>EXPELLED</th>
                        <th style="width: 14%;">SIGNATURE OF<br>SUSPECT</th>
                        <th style="width: 18%;">NAME OF SUPERVISING<br>OFFICER</th>
                        <th style="width: 17%;">SIGNATURE OF<br>SUPERVISING<br>OFFICER</th>
                        <th style="width: 17%;">REMARKS OF<br>SUPERVISING<br>OFFICER</th>
                    </tr>
                </thead>
                <tbody>
                    @for($r = 0; $r < 11; $r++)
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>

    <!-- PAGE 2: SIGNATORY TABLES (PRELIMINARY REPORT STYLE) -->
    <div class="sheet page-break-sheet">
        <div class="watermark-overlay">
            <img src="{{ $logoBase64 }}" alt="NACOC Watermark">
        </div>

        <div class="sheet-content">
            <!-- TABLE 1: SUBMITTED BY -->
            <div class="doc-section-title" style="margin-top: 24px; margin-bottom: 6px;">
                SUBMITTED BY
            </div>
            <table class="doc-table doc-table-2col" style="margin-bottom: 28px;">
                <tbody>
                    <tr>
                        <td style="width: 38%; height: 36px; vertical-align: middle;">Name</td>
                        <td style="height: 36px;"></td>
                    </tr>
                    <tr>
                        <td style="height: 36px; vertical-align: middle;">Rank/Designation</td>
                        <td style="height: 36px;"></td>
                    </tr>
                    <tr>
                        <td style="height: 56px; vertical-align: middle;">Signature</td>
                        <td style="height: 56px;"></td>
                    </tr>
                    <tr>
                        <td style="height: 36px; vertical-align: middle;">Date</td>
                        <td style="height: 36px;"></td>
                    </tr>
                </tbody>
            </table>

            <!-- TABLE 2: APPROVED BY -->
            <div class="doc-section-title" style="margin-top: 24px; margin-bottom: 6px;">
                APPROVED BY
            </div>
            <table class="doc-table doc-table-2col" style="margin-bottom: 28px;">
                <tbody>
                    <tr>
                        <td style="width: 38%; height: 36px; vertical-align: middle;">Name</td>
                        <td style="height: 36px;"></td>
                    </tr>
                    <tr>
                        <td style="height: 36px; vertical-align: middle;">Rank/Designation</td>
                        <td style="height: 36px;"></td>
                    </tr>
                    <tr>
                        <td style="height: 56px; vertical-align: middle;">Signature</td>
                        <td style="height: 56px;"></td>
                    </tr>
                    <tr>
                        <td style="height: 36px; vertical-align: middle;">Date</td>
                        <td style="height: 36px;"></td>
                    </tr>
                </tbody>
            </table>

            <!-- TABLE 3: AUTHORIZED BY -->
            <div class="doc-section-title" style="margin-top: 24px; margin-bottom: 6px;">
                AUTHORIZED BY
            </div>
            <table class="doc-table doc-table-2col" style="margin-bottom: 28px;">
                <tbody>
                    <tr>
                        <td style="width: 38%; height: 36px; vertical-align: middle;">Name</td>
                        <td style="height: 36px;"></td>
                    </tr>
                    <tr>
                        <td style="height: 36px; vertical-align: middle;">Rank/Designation</td>
                        <td style="height: 36px;"></td>
                    </tr>
                    <tr>
                        <td style="height: 56px; vertical-align: middle;">Signature</td>
                        <td style="height: 56px;"></td>
                    </tr>
                    <tr>
                        <td style="height: 36px; vertical-align: middle;">Date</td>
                        <td style="height: 36px;"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

