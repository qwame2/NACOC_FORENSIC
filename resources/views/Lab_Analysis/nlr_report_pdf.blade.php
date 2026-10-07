@php
    $authUser = auth()->user();
    $docketData = is_array($case->docket_data)
        ? $case->docket_data
        : (json_decode($case->docket_data ?? '[]', true) ?: []);

    $rawLab = $case->laboratory_number ?? ($docketData['laboratory_number'] ?? 'Lab No. 72/FS09/2026');
    $labNo = str_starts_with($rawLab, 'Lab No.') ? $rawLab : 'Lab No. ' . $rawLab;
    $caseNo = $case->case_number ?? 'CSS-2026-6730';
    $raidLoc = $case->raid_location ?? ($docketData['raid_location'] ?? ($case->location ?? 'Kotoka International Airport (Cargo Wing)'));

    $drugName = $case->seizure_name_drug ?? ($case->seizure_name ?? ($docketData['drug_name'] ?? 'Heroin (Brown Sugar / Diacetylmorphine)'));
    $drugCategory = $case->exhibit_category ?? ($docketData['drug_category'] ?? 'Narcotic Controlled Substances');
    $quantityStr = $case->exhibits_summary ?? ($docketData['quantity_str'] ?? '90 Kilograms (kg)');

    $submittingOfficer = $case->submitting_officer ?? ($docketData['submitting_officer'] ?? 'Det. Insp. K. Mensah');

    $rawAnalyst = $docketData['analyst'] 
        ?? ($docketData['lab_examination']['analyst'] 
        ?? ($case->analyst 
        ?? ($case->lab_analyst 
        ?? null)));

    if (!empty($rawAnalyst) && $rawAnalyst !== $submittingOfficer) {
        $analystName = $rawAnalyst;
    } elseif ($authUser && (str_contains(strtolower($authUser->unit ?? ''), 'lab') || !empty($authUser->fullname))) {
        $analystName = $authUser->fullname;
    } else {
        $analystName = 'John Mensah';
    }

    $dbLabHead = \App\Models\User::where('unit', 'LIKE', '%Lab%')->where('is_head', true)->first();
    $headName = $dbLabHead ? $dbLabHead->fullname : 'Dr. Samuel Atta (Head of Forensic Laboratory)';

    // Gather suspect records
    $rawSuspects = [];
    if (!empty($docketData['suspects']) && is_array($docketData['suspects'])) {
        $rawSuspects = $docketData['suspects'];
    } elseif (!empty($case->id)) {
        $rawSuspects = $case->getSuspectsList();
    }
    if (empty($rawSuspects)) {
        $rawSuspects[] = [
            'suspect_name' => $case->suspect_name ?? 'KOJO ADU',
            'suspect_alias' => $case->suspect_alias ?? 'Kofi Ghana',
            'role' => 'Primary Suspect',
        ];
    }

    $allSuspectNames = array_map(function ($s) {
        return strtoupper($s['suspect_name'] ?? ($s['name'] ?? 'SUSPECT'));
    }, $rawSuspects);
    $dossierSuspectNames = implode(', ', array_unique($allSuspectNames));
    $suspectLabResults = [];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NACOC Lab Result (NLR) Report - {{ $caseNo }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        .font-serif-header { font-family: 'Cinzel', serif; }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
        
        /* A4 Document Styling constraints */
        body {
            font-family: 'Inter', sans-serif;
            background: #e2e8f0;
            color: #1e293b;
            margin: 0;
            padding: 20px 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .a4-page {
            width: 210mm;
            min-height: 297mm;
            padding: 15mm 20mm;
            margin: 0 auto 20px auto;
            background: #ffffff;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            box-sizing: border-box;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .page-break {
            page-break-after: always;
            break-after: page;
        }

        .footer, .break-inside-avoid, tr, .signature-block, .no-break {
            break-inside: avoid !important;
            page-break-inside: avoid !important;
        }

        thead {
            display: table-header-group;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 5mm 0mm;
            }
            html, body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                height: 100% !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print {
                display: none !important;
            }
            .a4-page {
                width: 210mm !important;
                height: 287mm !important;
                max-height: 287mm !important;
                min-height: 287mm !important;
                margin: 0 auto !important;
                padding: 10mm 15mm !important;
                box-shadow: none !important;
                page-break-after: always !important;
                break-after: page !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                box-sizing: border-box !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                overflow: hidden !important;
            }
            .a4-page:last-child {
                page-break-after: avoid !important;
                break-after: avoid !important;
            }
        }
    </style>
</head>
<body>
    <div class="max-w-[210mm] mx-auto px-4 sm:px-0 mb-4 no-print">
        <!-- TOP TOOLBAR -->
        <div class="bg-slate-900 text-white p-4 rounded-xl flex items-center justify-between shadow-lg">
            <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 bg-emerald-700 font-bold text-xs tracking-wider rounded">NACOC NLR REPORT</span>
                <span class="font-mono-code text-xs text-slate-300">Ref: {{ $caseNo }} &bull; {{ $labNo }}</span>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/Lab_Analysis/analytical/' . $caseNo) }}" onclick="if (window.history.length > 1 && document.referrer) { event.preventDefault(); window.history.back(); }" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs font-mono-code rounded-lg flex items-center gap-1.5 transition-colors border border-slate-700 cursor-pointer no-underline">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Back</span>
                </a>
                <button onclick="window.print()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs font-mono-code rounded-lg flex items-center gap-1.5 transition-colors cursor-pointer border-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Print / Save PDF
                </button>
            </div>
        </div>
    </div>

    <!-- REUSABLE A4 NLR REPORT SHEET PARTIAL -->
    @include('Lab_Analysis.partials.nlr_report_sheet')
</body>
</html>