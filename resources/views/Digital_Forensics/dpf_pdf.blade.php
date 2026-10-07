@php
    $authUser = auth()->user();
    $dbUnitHead = \App\Models\User::where('unit', 'LIKE', '%Digital%')->orWhere('unit', 'LIKE', '%Physical%')->where('is_head', true)->first()
               ?? \App\Models\User::where('is_head', true)->first();
    $headName = $dbUnitHead ? $dbUnitHead->fullname : 'Lead Cyber Analyst';

    $reportObj = $report ?? null;
    $caseNo = $reportObj->case_number ?? ($case->case_number ?? 'CSS-2026-6730');
    $dpfCode = $reportObj->dpf_code ?? 'DPF-STATUS-2026-001';

    $rawDate = $reportObj->investigation_date ?? null;
    if ($rawDate instanceof \Carbon\Carbon || $rawDate instanceof \DateTimeInterface) {
        $investigationDate = $rawDate->format('Y-m-d');
    } else {
        $investigationDate = (string)($rawDate ?: date('Y-m-d'));
    }

    $locationPost = $reportObj->location_post ?? ($case->location ?? 'Kotoka International Airport (Cargo Wing)');
    $devicesRetrieved = $reportObj->devices_retrieved ?? 'Mobile Devices & Hardware Exhibits';
    $quantity = $reportObj->quantity ?? '1';
    $briefDescription = $reportObj->brief_case_description ?? ($case->description ?? 'Target intercepted with encrypted mobile hardware. Write-blocked forensic imaging executed under judicial authorization.');
    $courtRequested = strtoupper($reportObj->court_order_requested ?? 'YES');
    $courtGranted = strtoupper($reportObj->court_order_granted ?? 'YES');
    $extractionConducted = strtoupper($reportObj->extraction_analysis_conducted ?? 'YES');
    $reportWitnessSent = strtoupper($reportObj->report_witness_statement_sent ?? 'YES');
    $intelShared = strtoupper($reportObj->intel_shared ?? 'YES');
    $remarkVal = $reportObj->remark ?? 'N/A';

    if ($reportObj && $reportObj->user) {
        $loggedBy = $reportObj->user->fullname;
    } elseif ($authUser) {
        $loggedBy = $authUser->fullname;
    } else {
        $loggedBy = 'Lead Cyber Analyst';
    }

    $rawCreatedAt = $reportObj->created_at ?? null;
    if ($rawCreatedAt instanceof \Carbon\Carbon || $rawCreatedAt instanceof \DateTimeInterface) {
        $loggedAt = $rawCreatedAt->format('Y-m-d H:i:s');
    } else {
        $loggedAt = (string)($rawCreatedAt ?: date('Y-m-d H:i:s'));
    }

    $suspectName = $case->suspect_name ?? ($case->suspects_list[0]['suspect_name'] ?? 'PRIMARY SUSPECT');

    $docketData = is_array($case->docket_data ?? null)
        ? $case->docket_data
        : (json_decode($case->docket_data ?? '[]', true) ?: []);

    $rawSuspects = [];
    if (!empty($docketData['suspects']) && is_array($docketData['suspects'])) {
        $rawSuspects = $docketData['suspects'];
    } elseif (isset($case) && method_exists($case, 'getSuspectsList')) {
        $rawSuspects = $case->getSuspectsList();
    } elseif (!empty($case->suspects_list) && is_array($case->suspects_list)) {
        $rawSuspects = $case->suspects_list;
    }

    if (empty($rawSuspects)) {
        $rawSuspects[] = [
            'suspect_name' => $suspectName,
            'mugshot_front' => $case->mugshot_front ?? null,
        ];
    }

    $allSuspectNames = array_map(function ($s) {
        return strtoupper($s['suspect_name'] ?? ($s['name'] ?? 'SUSPECT'));
    }, $rawSuspects);
    $dossierSuspectNames = implode(', ', array_unique($allSuspectNames));

    $submittingOfficer = $case->submitting_officer ?? ($docketData['submitting_officer'] ?? 'Det. Insp. K. Mensah');
    $raidLoc = $case->raid_location ?? ($locationPost ?? ($case->location ?? 'Kotoka International Airport (Cargo Wing)'));

    if (!isset($renderReportFooter) || !is_callable($renderReportFooter)) {
        $renderReportFooter = function ($currentPage = 1, $totalPages = 2) use ($dpfCode) {
            return '
            <div class="pt-auto mb-9 border-t footer">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-900">
                            <span class="font-bold text-slate-700">' . e($dpfCode) . '</span>
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="mt-0.5 text-[9px] text-slate-700 text-right">
                            Digital &amp; Physical Forensics Unit (DPFU) &bull; Page ' . e($currentPage) . ' of ' . e($totalPages) . '
                        </p>
                    </div>
                </div>
            </div>';
        };
    }

    // Extract Electronic Device Forensic Specifications from Database exhibits table
    $deviceSpecsList = [];
    $dbExhibits = collect([]);

    if (isset($case)) {
        if (method_exists($case, 'exhibits') && $case->exhibits() instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
            $dbExhibits = $case->exhibits()->get();
        } elseif (isset($case->underlyingRecord)) {
            $underlying = $case->underlyingRecord;
            if ($underlying instanceof \App\Models\MultipleSuspectCase) {
                $dbExhibits = \App\Models\Exhibit::where('multiple_suspect_case_id', $underlying->id)->get();
            } elseif ($underlying instanceof \App\Models\SingleSuspectCase) {
                $dbExhibits = \App\Models\Exhibit::where('single_suspect_case_id', $underlying->id)->get();
            }
        } elseif (isset($case->id)) {
            $dbExhibits = \App\Models\Exhibit::where('single_suspect_case_id', $case->id)
                ->orWhere('multiple_suspect_case_id', $case->id)
                ->get();
        }
    }

    if ($dbExhibits->isEmpty() && !empty($caseNo)) {
        $sCase = \App\Models\SingleSuspectCase::where('case_number', $caseNo)->first();
        if ($sCase) {
            $dbExhibits = \App\Models\Exhibit::where('single_suspect_case_id', $sCase->id)->get();
        } else {
            $mCase = \App\Models\MultipleSuspectCase::where('case_number', $caseNo)->first();
            if ($mCase) {
                $dbExhibits = \App\Models\Exhibit::where('multiple_suspect_case_id', $mCase->id)->get();
            }
        }
    }

    if ($dbExhibits->isNotEmpty()) {
        foreach ($dbExhibits as $ex) {
            $devName = $ex->commodity_name 
                ?: ($ex->exhibit_category 
                ?: ($ex->exhibit_type 
                ?: ($ex->dev_model ?: $devicesRetrieved)));

            $deviceSpecsList[] = [
                'exhibit_no'  => $ex->exhibit_number ?: null,
                'device_name' => $devName,
                'quantity'    => $ex->seizure_quantity ?: $quantity,
                'model'       => $ex->dev_model ?: ($reportObj->dev_model ?? ($case->dev_model ?? 'N/A')),
                'serial'      => $ex->dev_serial_no ?: ($reportObj->dev_serial_no ?? ($case->dev_serial_no ?? 'N/A')),
                'imei1'       => $ex->dev_imei1 ?: ($reportObj->dev_imei1 ?? ($case->dev_imei1 ?? 'N/A')),
                'imei2'       => $ex->dev_imei2 ?: ($reportObj->dev_imei2 ?? ($case->dev_imei2 ?? 'N/A')),
                'mac'         => $ex->dev_mac_address ?: ($reportObj->dev_mac_address ?? ($case->dev_mac_address ?? 'N/A')),
                'storage'     => $ex->dev_storage ?: ($reportObj->dev_storage ?? ($case->dev_storage ?? 'N/A')),
                'colour'      => $ex->dev_colour ?: ($reportObj->dev_colour ?? ($case->dev_colour ?? 'N/A')),
                'marks'       => $ex->dev_marks ?: ($ex->other_exhibit_description ?: ($reportObj->dev_marks ?? ($case->dev_marks ?? 'N/A'))),
            ];
        }
    }

    if (empty($deviceSpecsList) && !empty($docketData)) {
        $exArr = $docketData['exhibits'] ?? ($docketData['seizures'] ?? []);
        if (is_array($exArr)) {
            foreach ($exArr as $ex) {
                $devName = $ex['commodity_name'] ?? ($ex['exhibit_category'] ?? ($ex['exhibit_type'] ?? ($ex['dev_model'] ?? $devicesRetrieved)));
                $deviceSpecsList[] = [
                    'exhibit_no'  => $ex['exhibit_number'] ?? null,
                    'device_name' => $devName,
                    'quantity'    => $ex['seizure_quantity'] ?? $quantity,
                    'model'       => $ex['dev_model'] ?? ($ex['device_model'] ?? 'N/A'),
                    'serial'      => $ex['dev_serial_no'] ?? ($ex['device_serial'] ?? 'N/A'),
                    'imei1'       => $ex['dev_imei1'] ?? ($ex['imei1'] ?? 'N/A'),
                    'imei2'       => $ex['dev_imei2'] ?? ($ex['imei2'] ?? 'N/A'),
                    'mac'         => $ex['dev_mac_address'] ?? ($ex['mac_address'] ?? 'N/A'),
                    'storage'     => $ex['dev_storage'] ?? ($ex['storage'] ?? 'N/A'),
                    'colour'      => $ex['dev_colour'] ?? ($ex['colour'] ?? 'N/A'),
                    'marks'       => $ex['dev_marks'] ?? ($ex['marks'] ?? 'N/A'),
                ];
            }
        }
    }

    if (empty($deviceSpecsList)) {
        $deviceSpecsList[] = [
            'exhibit_no'  => null,
            'device_name' => $devicesRetrieved,
            'quantity'    => $quantity,
            'model'       => $reportObj->dev_model ?? ($case->dev_model ?? $devicesRetrieved),
            'serial'      => $reportObj->dev_serial_no ?? ($case->dev_serial_no ?? 'F2LZX984K102'),
            'imei1'       => $reportObj->dev_imei1 ?? ($case->dev_imei1 ?? '356938035643809'),
            'imei2'       => $reportObj->dev_imei2 ?? ($case->dev_imei2 ?? 'N/A'),
            'mac'         => $reportObj->dev_mac_address ?? ($case->dev_mac_address ?? '00:1A:2B:3C:4D:5E'),
            'storage'     => $reportObj->dev_storage ?? ($case->dev_storage ?? '256 GB'),
            'colour'      => $reportObj->dev_colour ?? ($case->dev_colour ?? 'Space Gray'),
            'marks'       => $reportObj->dev_marks ?? ($case->dev_marks ?? 'Write-blocked physical image captured'),
        ];
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NACOC Digital Forensic Investigation Report - {{ $caseNo }}</title>
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
        <!-- TOP TOOLBAR (EXACT MATCHING NLR REPORT TOOLBAR) -->
        <div class="bg-slate-900 text-white p-4 rounded-xl flex items-center justify-between shadow-lg">
            <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 bg-emerald-700 font-bold text-xs tracking-wider rounded uppercase">NACOC DPF REPORT</span>
                <span class="font-mono-code text-xs text-slate-300">Ref: {{ $caseNo }} &bull; {{ $dpfCode }}</span>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/Digital_Forensics/forensic-status') }}" onclick="if (window.history.length > 1 && document.referrer) { event.preventDefault(); window.history.back(); }" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-400 font-bold text-xs font-mono-code rounded-lg flex items-center gap-1.5 transition-colors border border-slate-700 cursor-pointer no-underline">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Back</span>
                </a>
                <button onclick="window.print()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs font-mono-code rounded-lg flex items-center gap-1.5 transition-colors cursor-pointer border-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    <span>Print / Save PDF</span>
                </button>
            </div>
        </div>
    </div>

    <!-- REUSABLE A4 DPF REPORT SHEET (2-PAGE FORMAT) -->
    <div id="dpfReportSheet" class="printable-document">

        <!-- ============================================================
             PAGE 1: OFFICIAL FORENSIC DPF COVER PAGE
             ============================================================ -->
        <div class="a4-page page-break relative text-slate-900">

            <!-- SUBTLE NACOC WATERMARK -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.025] select-none">
                <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[900px] h-[900px] object-contain filter grayscale" />
            </div>

            <div class="relative z-10 flex flex-col justify-between h-full min-h-0">

                <!-- PAGE 1 CONTENT -->
                <div>
                    <!-- HEADER -->
                    <div class="relative pt-4 pb-6 border-b border-slate-300 text-center">

                        <!-- TOP LEFT BADGE -->
                        <div class="absolute top-0 left-0 text-left">
                            <p class="text-[8px] font-bold uppercase tracking-wider text-slate-900">REPUBLIC OF GHANA</p>
                            <p class="mt-0.5 text-[8px] text-slate-700">ISO/IEC 17025</p>
                        </div>

                        <!-- TOP RIGHT BADGE -->
                        <div class="absolute top-0 right-0 text-right">
                            <p class="text-[8px] font-bold uppercase tracking-wider text-slate-900">Confidential</p>
                            <p class="mt-0.5 text-[9px] font-bold uppercase text-slate-800">
                                DPF &bull; Page 1 of 2
                            </p>
                        </div>

                        <!-- CENTERED LOGO -->
                        <div class="mx-auto w-20 h-20 flex items-center justify-center mb-7">
                            <img src="{{ asset('NACOC LOGO.jfif') }}" alt="NACOC Emblem Logo" class="max-h-40 max-w-40 object-contain" />
                        </div>

                        <!-- INSTITUTION DETAILS -->
                        <div>
                            <h1 class="text-lg font-bold uppercase tracking-wide text-slate-900 font-serif-header">
                                NARCOTICS CONTROL COMMISSION
                            </h1>
                            <p class="mt-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-700">
                                DEPARTMENT OF FORENSIC
                            </p>
                            <p class="text-[10px] font-semibold uppercase tracking-[0.1em] text-slate-600 font-mono-code">
                                DIGITAL &amp; PHYSICAL FORENSICS UNIT (DPFU)
                            </p>
                        </div>

                    </div>

                    <!-- REPORT TITLE -->
                    <div class="mt-10 text-center">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-900">
                            OFFICIAL FORENSIC DOSSIER
                        </p>
                        <h2 class="mt-2 text-2xl font-bold uppercase tracking-wide text-slate-950 font-serif-header">
                            DIGITAL FORENSIC INVESTIGATION
                        </h2>
                        <p class="mt-1 text-sm font-semibold uppercase tracking-[0.12em] text-slate-700">
                            Extraction &amp; Investigation Status Log Report
                        </p>
                        <div class="mx-auto mt-4 h-[2px] w-16 bg-emerald-700"></div>
                        <p class="mt-9 text-[9px] font-medium uppercase tracking-wider text-slate-900">
                            HIGH COURT ADMISSIBLE FORENSIC EVIDENCE DOSSIER
                        </p>
                    </div>

                    <!-- REPORT INFORMATION -->
                    <div class="mt-9">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-[9px] font-bold uppercase tracking-[0.15em] text-emerald-700">
                                    Report Information
                                </p>
                                <p class="mt-0.5 text-[9px] text-slate-700">
                                    Official document identification
                                </p>
                            </div>
                            <p class="text-[9px] font-semibold text-slate-700 font-mono-code">
                                Ref: {{ $dpfCode }}
                            </p>
                        </div>

                        <div class="grid grid-cols-4 border-y border-slate-200">
                            <div class="py-3 pr-4 border-r border-slate-200">
                                <p class="text-[8px] font-bold uppercase tracking-wider text-slate-700">Case Ref No.</p>
                                <p class="mt-1 text-sm font-bold text-slate-900">{{ $caseNo }}</p>
                            </div>
                            <div class="py-3 px-4 border-r border-slate-200">
                                <p class="text-[8px] font-bold uppercase tracking-wider text-slate-700">Investigation Date</p>
                                <p class="mt-1 text-sm font-bold text-slate-900">{{ $investigationDate }}</p>
                            </div>
                            <div class="py-3 px-4 border-r border-slate-200">
                                <p class="text-[8px] font-bold uppercase tracking-wider text-slate-700">Location / Post</p>
                                <p class="mt-1 text-sm font-bold text-slate-900 truncate">{{ $locationPost }}</p>
                            </div>
                            <div class="py-3 pl-4">
                                <p class="text-[8px] font-bold uppercase tracking-wider text-slate-700">Logged Timestamp</p>
                                <p class="mt-1 text-sm font-bold text-slate-900">{{ $loggedAt }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 01: SUSPECT & CASE INFORMATION -->
                    <div class="mt-9">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-50 text-[9px] font-bold text-emerald-700">
                                01
                            </span>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-900">
                                        Suspect &amp; Case Information
                                    </h3>
                                    <span class="text-[8px] font-semibold uppercase tracking-wider text-slate-700">
                                        High Court Admissible
                                    </span>
                                </div>
                                <div class="mt-1.5 h-px bg-slate-200"></div>
                            </div>
                        </div>

                        <div class="flex items-start gap-6">
                            <!-- SUSPECT FRONT MUGSHOT(S) -->
                            <div class="shrink-0 flex flex-wrap items-center gap-3">
                                @foreach($rawSuspects as $sIdx => $sItem)
                                    @php
                                        $sFrontMug = $sItem['mugshot_front']
                                            ?? ($sItem['mugshots']['front'] ?? ($docketData['mugshot_front'] ?? ($case->mugshot_front ?? null)));
                                        if (!empty($sFrontMug) && !str_starts_with($sFrontMug, 'data:image') && !str_starts_with($sFrontMug, 'http://') && !str_starts_with($sFrontMug, 'https://') && !str_starts_with($sFrontMug, '/')) {
                                            $sFrontMug = asset($sFrontMug);
                                        }
                                        $sNameDisp = $sItem['suspect_name'] ?? ($sItem['name'] ?? 'SUSPECT');
                                    @endphp
                                    <div class="flex flex-col items-center">
                                        <div class="relative w-28 h-32 rounded-xl overflow-hidden border-2 border-slate-300 bg-slate-100 shadow-sm flex items-center justify-center">
                                            @if(!empty($sFrontMug))
                                                <img src="{{ $sFrontMug }}" alt="{{ $sNameDisp }} Front Photo" class="w-full h-full object-cover" />
                                            @else
                                                <div class="flex flex-col items-center justify-center p-2 text-center text-slate-700">
                                                    <svg class="w-8 h-8 text-slate-300 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                    </svg>
                                                    <span class="text-[7px] font-bold uppercase tracking-tight text-slate-700">Front Mugshot</span>
                                                </div>
                                            @endif
                                        </div>
                                        <span class="mt-1.5 text-[8px] font-bold uppercase tracking-wider text-slate-700 truncate max-w-[110px] text-center">
                                            {{ $sNameDisp }}
                                        </span>
                                        <span class="text-[7px] font-semibold uppercase tracking-wider text-emerald-700">
                                            Front View Photo
                                        </span>
                                    </div>
                                @endforeach
                            </div>

                            <!-- CASE DETAILS GRID -->
                            <div class="flex-1 grid grid-cols-2 gap-x-6 gap-y-4 pt-1">
                                <div>
                                    <p class="text-[8px] font-bold uppercase tracking-wider text-slate-700">
                                        Suspect Name(s)
                                    </p>
                                    <p class="mt-1 text-xs font-semibold text-slate-900">
                                        {{ $dossierSuspectNames }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[8px] font-bold uppercase tracking-wider text-slate-700">
                                        Exhibit Type
                                    </p>
                                    <p class="mt-1 text-xs font-semibold text-slate-900">
                                        {{ $devicesRetrieved }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[8px] font-bold uppercase tracking-wider text-slate-700">
                                        Submitting Officer
                                    </p>
                                    <p class="mt-1 text-xs font-medium text-slate-800">
                                        {{ $submittingOfficer }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[8px] font-bold uppercase tracking-wider text-slate-700">
                                        Seizure / Raid Location
                                    </p>
                                    <p class="mt-1 text-xs font-medium text-slate-800">
                                        {{ $raidLoc }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- PAGE 1 FOOTER -->
                {!! $renderReportFooter(1, 2) !!}

            </div>
        </div>

        <!-- ============================================================
             PAGE 2: ANALYTICAL & TECHNICAL INVESTIGATION LOG REPORT
             ============================================================ -->
        <div class="a4-page relative text-slate-900">

            <!-- SUBTLE NACOC WATERMARK -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.025] select-none">
                <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[900px] h-[900px] object-contain filter grayscale" />
            </div>

            <div class="relative z-10 flex flex-col justify-between h-full min-h-0">

                <div>
                    <!-- PAGE 2 HEADER -->
                    <div class="relative pt-2 pb-3 border-b border-slate-300 flex items-center justify-between">
                        <div>
                            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-900 font-serif-header">
                                NACOC DPF REPORT
                            </span>
                            <span class="ml-2 font-mono-code text-[9px] font-semibold text-slate-800">
                                Ref: {{ $caseNo }} &bull; {{ $dpfCode }}
                            </span>
                        </div>
                        <div class="text-right">
                            <p class="text-[8px] font-bold uppercase tracking-wider text-slate-700">REPORT</p>
                            <p class="mt-0.5 text-[10px] font-bold text-slate-900">Page 2 of 2</p>
                        </div>
                    </div>

                    <!-- DOCUMENT TITLE SUBHEADER -->
                    <div class="mt-2.5 pt-1.5 border-t border-slate-100 flex items-center justify-between">
                        <div>
                            <span class="text-[8px] font-bold uppercase tracking-wider text-slate-700">Title</span>
                            <span class="ml-2 text-[10px] font-bold uppercase text-slate-900">Digital Forensic Investigation Status Log</span>
                        </div>
                        <div>
                            <span class="text-[8px] font-bold uppercase tracking-wider text-slate-700">Doc. No.</span>
                            <span class="ml-2 text-[10px] font-bold text-slate-900 font-mono-code">{{ $dpfCode }}</span>
                        </div>
                    </div>

                    <!-- SECTION 01: HARDWARE EXHIBITS & ACQUISITION SUMMARY -->
                    <div class="mt-9">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-50 text-[9px] font-bold text-emerald-700">
                                01
                            </span>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-900">
                                        Hardware Exhibits Summary
                                    </h3>
                                    <span class="text-[8px] font-semibold uppercase tracking-wider text-slate-700">
                                        Chain of Custody Active
                                    </span>
                                </div>
                                <div class="mt-1.5 h-px bg-slate-200"></div>
                            </div>
                        </div>

                        <!-- HARDWARE EXHIBITS & ELECTRONIC DEVICE FORENSIC SPECIFICATIONS TABLE -->
                        @if(!empty($deviceSpecsList))
                            <div class="space-y-3">
                                @foreach($deviceSpecsList as $dIdx => $spec)
                                    <div class="border border-slate-300 rounded-lg overflow-hidden bg-white">
                                        <!-- BAR HEADER -->
                                        <div class="bg-slate-100 px-3 py-1.5 border-b border-slate-300 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <span class="px-2 py-0.5 bg-slate-900 text-white font-mono-code font-bold text-[8px] rounded uppercase tracking-wider">
                                                    {{ !empty($spec['exhibit_no']) ? $spec['exhibit_no'] : ('SPEC ' . (count($deviceSpecsList) > 1 ? '#' . ($dIdx + 1) : 'DETAILS')) }}
                                                </span>
                                                <span class="text-[9.5px] font-bold uppercase tracking-wider text-slate-900">
                                                    Electronic Device Forensic Specifications
                                                </span>
                                            </div>
                                        </div>

                                        <!-- TABLE GRID -->
                                        <table class="w-full text-left border-collapse">
                                            <tbody class="text-[9.5px] text-slate-900">
                                                <!-- ROW 1: DEVICES & QUANTITY -->
                                                <tr class="border-b border-slate-200">
                                                    <td class="py-1.5 px-3 bg-slate-50/70 border-r border-slate-200 w-[24%] font-bold text-[8px] uppercase tracking-wider text-slate-700">
                                                        Devices / Media Retrieved
                                                    </td>
                                                    <td class="py-1.5 px-3 border-r border-slate-200 w-[42%] font-bold text-slate-900">
                                                        {{ $spec['device_name'] ?? $devicesRetrieved }}
                                                    </td>
                                                    <td class="py-1.5 px-3 bg-slate-50/70 border-r border-slate-200 w-[14%] font-bold text-[8px] uppercase tracking-wider text-slate-700">
                                                        Quantity
                                                    </td>
                                                    <td class="py-1.5 px-3 font-bold text-slate-900 font-mono-code w-[20%]">
                                                        {{ !empty($spec['quantity']) ? $spec['quantity'] . (is_numeric($spec['quantity']) ? ' Unit(s)' : '') : '1 Unit' }}
                                                    </td>
                                                </tr>

                                                <!-- ROW 2: MODEL & SERIAL NUMBER -->
                                                <tr class="border-b border-slate-200">
                                                    <td class="py-1.5 px-3 bg-slate-50/70 border-r border-slate-200 font-bold text-[8px] uppercase tracking-wider text-slate-700">
                                                        Device Model
                                                    </td>
                                                    <td class="py-1.5 px-3 border-r border-slate-200 font-bold text-slate-900">
                                                        {{ $spec['model'] }}
                                                    </td>
                                                    <td class="py-1.5 px-3 bg-slate-50/70 border-r border-slate-200 font-bold text-[8px] uppercase tracking-wider text-slate-700">
                                                        Serial Number
                                                    </td>
                                                    <td class="py-1.5 px-3 font-bold text-slate-900 font-mono-code">
                                                        {{ $spec['serial'] }}
                                                    </td>
                                                </tr>

                                                <!-- ROW 3: TELEPHONY / IDENTIFIERS (IMEI 1 & IMEI 2) -->
                                                <tr class="border-b border-slate-200">
                                                    <td class="py-1.5 px-3 bg-slate-50/70 border-r border-slate-200 font-bold text-[8px] uppercase tracking-wider text-slate-700">
                                                        IMEI 1
                                                    </td>
                                                    <td class="py-1.5 px-3 border-r border-slate-200 font-bold font-mono-code text-slate-900">
                                                        {{ $spec['imei1'] }}
                                                    </td>
                                                    <td class="py-1.5 px-3 bg-slate-50/70 border-r border-slate-200 font-bold text-[8px] uppercase tracking-wider text-slate-700">
                                                        IMEI 2
                                                    </td>
                                                    <td class="py-1.5 px-3 font-bold font-mono-code text-slate-900">
                                                        {{ $spec['imei2'] }}
                                                    </td>
                                                </tr>

                                                <!-- ROW 4: MAC ADDRESS & STORAGE CAPACITY -->
                                                <tr class="border-b border-slate-200">
                                                    <td class="py-1.5 px-3 bg-slate-50/70 border-r border-slate-200 font-bold text-[8px] uppercase tracking-wider text-slate-700">
                                                        MAC Address
                                                    </td>
                                                    <td class="py-1.5 px-3 border-r border-slate-200 font-bold font-mono-code text-slate-900">
                                                        {{ $spec['mac'] }}
                                                    </td>
                                                    <td class="py-1.5 px-3 bg-slate-50/70 border-r border-slate-200 font-bold text-[8px] uppercase tracking-wider text-slate-700">
                                                        Storage Capacity
                                                    </td>
                                                    <td class="py-1.5 px-3 font-bold text-slate-900">
                                                        {{ $spec['storage'] }}
                                                    </td>
                                                </tr>

                                                <!-- ROW 5: COLOUR & OTHER MARKS -->
                                                <tr>
                                                    <td class="py-1.5 px-3 bg-slate-50/70 border-r border-slate-200 font-bold text-[8px] uppercase tracking-wider text-slate-700">
                                                        Colour
                                                    </td>
                                                    <td class="py-1.5 px-3 border-r border-slate-200 font-bold text-slate-900">
                                                        {{ $spec['colour'] }}
                                                    </td>
                                                    <td class="py-1.5 px-3 bg-slate-50/70 border-r border-slate-200 font-bold text-[8px] uppercase tracking-wider text-slate-700">
                                                        Other Marks / Notes
                                                    </td>
                                                    <td class="py-1.5 px-3 font-medium text-slate-800">
                                                        {{ $spec['marks'] }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- SECTION 02: BRIEF CASE DESCRIPTION & OPERATIONAL CONTEXT -->
                    <div class="mt-9">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-50 text-[9px] font-bold text-emerald-700">
                                02
                            </span>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-900">
                                        Brief Case Description
                                    </h3>
                                    <span class="text-[8px] font-semibold uppercase tracking-wider text-slate-700">
                                        Intelligence Fact Sheet
                                    </span>
                                </div>
                                <div class="mt-1.5 h-px bg-slate-200"></div>
                            </div>
                        </div>

                        <div class="text-[10.5px] leading-snug text-slate-700 whitespace-pre-line py-0.5">
                            {{ $briefDescription }}
                        </div>
                    </div>

                    <!-- SECTION 03: LEGAL & OPERATIONAL PROCESS STATUS -->
                    <div class="mt-9">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-50 text-[9px] font-bold text-emerald-700">
                                03
                            </span>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-900">
                                        Legal &amp; Operational Process Status
                                    </h3>
                                    <span class="text-[8px] font-semibold uppercase tracking-wider text-slate-700">
                                        Warrant &amp; Docket Verification
                                    </span>
                                </div>
                                <div class="mt-1.5 h-px bg-slate-200"></div>
                            </div>
                        </div>

                        <div class="overflow-hidden border-y border-slate-300">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-300 bg-slate-100/50">
                                        <th class="py-1 px-2.5 text-left text-[8px] font-bold uppercase tracking-wider text-slate-900 w-[80%] border-r border-slate-300">
                                            Process Phase
                                        </th>
                                        <th class="py-1 px-2.5 text-center text-[8px] font-bold uppercase tracking-wider text-slate-900 w-[20%]">
                                            Logged Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="text-[9.5px] text-slate-800">
                                    <tr class="border-b border-slate-200">
                                        <td class="py-1 px-2.5 border-r border-slate-300 font-medium">Court Order Warrant Requested</td>
                                        <td class="py-1 px-2.5 text-center font-extrabold {{ $courtRequested === 'YES' ? 'text-emerald-800' : 'text-slate-900' }}">{{ $courtRequested }}</td>
                                    </tr>
                                    <tr class="border-b border-slate-200">
                                        <td class="py-1 px-2.5 border-r border-slate-300 font-medium">Court Order Warrant Granted</td>
                                        <td class="py-1 px-2.5 text-center font-extrabold {{ $courtGranted === 'YES' ? 'text-emerald-800' : 'text-slate-900' }}">{{ $courtGranted }}</td>
                                    </tr>
                                    <tr class="border-b border-slate-200">
                                        <td class="py-1 px-2.5 border-r border-slate-300 font-medium">Extraction &amp; Forensic Analysis Conducted</td>
                                        <td class="py-1 px-2.5 text-center font-extrabold {{ $extractionConducted === 'YES' ? 'text-emerald-800' : 'text-slate-900' }}">{{ $extractionConducted }}</td>
                                    </tr>
                                    <tr class="border-b border-slate-200">
                                        <td class="py-1 px-2.5 border-r border-slate-300 font-medium">Report &amp; Witness Statement Sent to Investigations</td>
                                        <td class="py-1 px-2.5 text-center font-extrabold {{ $reportWitnessSent === 'YES' ? 'text-emerald-800' : 'text-slate-900' }}">{{ $reportWitnessSent }}</td>
                                    </tr>
                                    <tr class="border-b border-slate-200">
                                        <td class="py-1 px-2.5 border-r border-slate-300 font-medium">Intelligence Shared to Intel Department for Action</td>
                                        <td class="py-1 px-2.5 text-center font-extrabold {{ $intelShared === 'YES' ? 'text-emerald-800' : 'text-slate-900' }}">{{ $intelShared }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TECHNICAL REMARKS & OBSERVATIONS -->
                    <div class="mt-9">
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-900">
                                Remarks
                            </h3>
                            <div class="h-px flex-1 ml-4 bg-slate-200"></div>
                        </div>
                        <div class="text-[10.5px] leading-snug text-slate-700 whitespace-pre-line">
                            {{ $remarkVal }}
                        </div>
                    </div>

                    <!-- CONDITIONS & DISCLAIMER NOTE -->
                    <div class="mt-9 pt-2 border-t border-slate-200">
                        <p class="text-[9.5px] font-semibold text-slate-800 mb-1 italic">
                            Note: The result(s) relate only to the item(s) / digital exhibit(s) examined
                        </p>
                        <p class="text-[9.5px] font-extrabold uppercase tracking-wider text-slate-900 mb-1">
                            Conditions:
                        </p>
                        <ol class="list-decimal list-inside space-y-0.5 text-[9px] font-medium text-slate-700 leading-tight pl-0.5">
                            <li>Not valid without NACOC Digital &amp; Physical Forensics Unit approval.</li>
                            <li>This report does not signify that media/device extracted has been certified.</li>
                            <li>Not to be used for litigation and advertisement without written consent of the Director General of NACOC.</li>
                            <li>This report shall not be reproduced in part without the written approval of the Director General of NACOC.</li>
                        </ol>
                    </div>
                </div>

                <!-- SIGNATURE & ENDORSEMENT FOOTER WRAPPER -->
                <div>
                    <!-- SIGNATURES -->
                    <div class="mt-9 pt-2 pb-0 footer no-break">
                        <div class="grid grid-cols-2 gap-10">
                            <!-- ANALYST -->
                            <div>
                                <p class="text-[8px] font-bold uppercase tracking-wider text-slate-700">Digital Forensic Analyst</p>
                                <div class="mt-5 border-b border-dashed border-slate-300 pb-1">
                                    <span class="text-[8px] italic text-slate-700">Electronically Signed &amp; Validated</span>
                                </div>
                                <p class="mt-1.5 text-[10px] font-bold text-slate-900">{{ $loggedBy }}</p>
                                <p class="text-[8px] text-slate-900">Senior Cyber &amp; Digital Analyst</p>
                                <p class="text-[8px] text-slate-700">Digital &amp; Physical Forensics Unit, NACOC</p>
                            </div>

                            <!-- HEAD -->
                            <div>
                                <p class="text-[8px] font-bold uppercase tracking-wider text-slate-700">Head of Digital &amp; Physical Forensics</p>
                                <div class="mt-5 border-b border-dashed border-slate-300 pb-1">
                                    <span class="text-[8px] italic text-slate-700">Authorized &amp; Counter-signed</span>
                                </div>
                                <p class="mt-1.5 text-[10px] font-bold text-slate-900">{{ $headName }}</p>
                                <p class="text-[8px] text-slate-900">Director / Head of DPF Unit</p>
                                <p class="text-[8px] text-slate-700">National Drug Control Commission</p>
                            </div>
                        </div>
                    </div>

                    <!-- PAGE 2 FOOTER -->
                    {!! $renderReportFooter(2, 2) !!}
                </div>

            </div>
        </div>

    </div>
</body>
</html>
