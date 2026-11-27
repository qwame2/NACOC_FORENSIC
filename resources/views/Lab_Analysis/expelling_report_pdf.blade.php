@php
    $logoPath = public_path('NACOC_LOGO.png');
    $logoBase64 = file_exists($logoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : asset('NACOC_LOGO.png');

    $suspectNameVal = trim((string) ($expellingData['suspect_name'] ?? ''));
    $passportVal = trim((string) ($expellingData['passport_number'] ?? ''));
    $placeOfArrestVal = trim((string) ($expellingData['place_of_arrest'] ?? ''));
    $dateOfArrestVal = trim((string) ($expellingData['date_of_arrest'] ?? ''));
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
            color: #111827;
            font-family: "Times New Roman", Georgia, serif;
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
            width: 390px;
            height: 390px;
            object-fit: contain;
            opacity: 0.12;
        }
        .sheet-content {
            position: relative;
            z-index: 2;
        }
        /* Header: Logo Left, Institutional Title Middle */
        .doc-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding-bottom: 10px;
            border-bottom: 2px solid #065f46;
        }
        .doc-logo {
            width: 78px;
            height: 78px;
            object-fit: contain;
            flex-shrink: 0;
        }
        .doc-header-center {
            flex: 1;
            text-align: center;
            padding-right: 40px;
        }
        .doc-org {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.04em;
            color: #064e3b;
            line-height: 1.25;
        }
        .doc-dept {
            font-size: 13.5px;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: #111827;
            margin-top: 2px;
        }
        .doc-unit {
            font-size: 12.5px;
            font-weight: 700;
            letter-spacing: 0.06em;
            color: #047857;
            margin-top: 2px;
        }
        .report-title-wrap {
            margin-top: 22px;
            margin-bottom: 18px;
            text-align: center;
        }
        .report-title {
            display: inline-block;
            font-size: 15.5px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            text-decoration: underline;
            text-underline-offset: 4px;
            color: #000000;
        }
        .case-ref-note {
            text-align: right;
            font-size: 11px;
            font-weight: 700;
            color: #374151;
            margin-bottom: 8px;
        }
        .field-row {
            display: flex;
            align-items: baseline;
            gap: 14px;
            margin-bottom: 11px;
            font-size: 13px;
            line-height: 1.35;
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
            color: #111827;
        }
        .field-line {
            flex: 1;
            border-bottom: 1.5px dotted #1f2937;
            min-height: 17px;
            padding: 0 6px 1px 6px;
            font-weight: 700;
            color: #0f172a;
            word-break: break-word;
        }
        /* Expelling Table */
        .expelling-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            margin-bottom: 18px;
            font-size: 11px;
        }
        .expelling-table th,
        .expelling-table td {
            border: 1.25px solid #111827;
            padding: 6px 5px;
            vertical-align: middle;
        }
        .expelling-table th {
            text-align: center;
            font-weight: 800;
            font-size: 10.5px;
            line-height: 1.25;
            background: rgba(241, 245, 249, 0.72);
            color: #000000;
        }
        .expelling-table tbody td {
            height: 28px;
        }
        .footer-block {
            margin-top: 14px;
            font-size: 13px;
        }
        .footer-row {
            display: flex;
            align-items: baseline;
            gap: 14px;
            margin-bottom: 12px;
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
                    <span class="field-label">Passport No.:</span>
                    <span class="field-line">{{ $passportVal }}</span>
                </div>
            </div>

            <div class="field-row">
                <div class="field-group">
                    <span class="field-label">Place of arrest:</span>
                    <span class="field-line">{{ $placeOfArrestVal }}</span>
                </div>
                <div class="field-group-compact">
                    <span class="field-label">Date of arrest:</span>
                    <span class="field-line">{{ $dateOfArrestVal }}</span>
                </div>
            </div>

            <div class="field-row">
                <div class="field-group">
                    <span class="field-label">Arrest Officer(s):</span>
                    <span class="field-line">{{ $arrestOfficersVal }}</span>
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

            <div class="field-row">
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
                        <th style="width: 14%;">SIGN. OF<br>SUSPECT</th>
                        <th style="width: 18%;">NAME OF SUPERVISING<br>OFFICER</th>
                        <th style="width: 17%;">SIGNATURE OF<br>SUPERVISING<br>OFFICER</th>
                        <th style="width: 17%;">REMARKS OF<br>SUPERVISING<br>OFFICER</th>
                    </tr>
                </thead>
                <tbody>
                    @for($r = 0; $r < 8; $r++)
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

            <!-- FOOTER SIGN-OFF -->
            <div class="footer-block">
                <div class="footer-row">
                    <div class="field-group" style="max-width: 56%;">
                        <span class="field-label">Total No. Expelled:</span>
                        <span class="field-line"></span>
                    </div>
                </div>

                <div class="footer-row">
                    <div class="field-group">
                        <span class="field-label">Submitted by:</span>
                        <span class="field-line"></span>
                    </div>
                </div>

                <div class="footer-row">
                    <div class="field-group" style="flex: 1.4;">
                        <span class="field-label">Signature:</span>
                        <span class="field-line"></span>
                    </div>
                    <div class="field-group" style="flex: 0.8;">
                        <span class="field-label">Date:</span>
                        <span class="field-line"></span>
                    </div>
                </div>

                <div class="footer-row">
                    <div class="field-group">
                        <span class="field-label">Received by:</span>
                        <span class="field-line"></span>
                    </div>
                </div>

                <div class="footer-row">
                    <div class="field-group" style="flex: 1.4;">
                        <span class="field-label">Signature:</span>
                        <span class="field-line"></span>
                    </div>
                    <div class="field-group" style="flex: 0.8;">
                        <span class="field-label">Date:</span>
                        <span class="field-line"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

