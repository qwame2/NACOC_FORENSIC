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

    $reportObj = $report ?? null;
    $caseNo = $reportObj->case_number ?? ($case->case_number ?? 'N/A');
    $dpfCode = $reportObj->dpf_code ?? ($caseNo !== 'N/A' ? 'DPF-' . $caseNo : 'N/A');

    $rawDate = $reportObj->investigation_date ?? null;
    if ($rawDate instanceof \Carbon\Carbon || $rawDate instanceof \DateTimeInterface) {
        $investigationDate = $rawDate->format('Y-m-d');
    } else {
        $investigationDate = (string)($rawDate ?: date('Y-m-d'));
    }

    $locationPost = $reportObj->location_post ?? ($case->location ?? 'N/A');
    $devicesRetrieved = $reportObj->devices_retrieved ?? 'N/A';
    $quantity = $reportObj->quantity ?? 'N/A';
    $briefDescription = $reportObj->brief_case_description ?? ($case->description ?? 'N/A');

    // Check if suspect is linked to only electronics or drugs
    $caseHasDrug = false;
    $caseHasElectronic = false;
    if (isset($case)) {
        if (method_exists($case, 'classifyExhibits')) {
            $cFlags = $case->classifyExhibits();
            $caseHasDrug = !empty($cFlags['has_drug']);
            $caseHasElectronic = !empty($cFlags['has_electronic']);
        }
        if (method_exists($case, 'isDrugCase') && $case->isDrugCase()) {
            $caseHasDrug = true;
        }
        if (method_exists($case, 'isDigitalCase') && $case->isDigitalCase()) {
            $caseHasElectronic = true;
        }
        if (!empty($case->phones) || !empty($case->underlyingRecord->phones)) {
            $caseHasElectronic = true;
        }
    }

    if ($caseHasElectronic && !$caseHasDrug) {
        // Suspect is linked ONLY to electronics (no drugs) - eliminate any drug references
        if (str_contains($briefDescription, 'narcotic drugs')) {
            $briefDescription = str_replace(
                [
                    'to curb the use and sale of narcotic drugs within the area',
                    'to curb the use and sale of narcotic drugs',
                    'use and sale of narcotic drugs',
                    'sale of narcotic drugs',
                    'narcotic drugs',
                ],
                [
                    'to intercept and seize illicit electronic devices and digital evidence within the area',
                    'to intercept and seize illicit electronic devices and digital evidence within the area',
                    'illicit digital evidence and mobile exhibits',
                    'illicit electronic hardware',
                    'electronic devices',
                ],
                $briefDescription
            );
        }
    }
    $courtRequested = strtoupper($reportObj->court_order_requested ?? 'NO');
    $courtGranted = strtoupper($reportObj->court_order_granted ?? 'NO');

    $reportDataArray = is_array($reportObj->report_data ?? null) 
        ? $reportObj->report_data 
        : (is_string($reportObj->report_data ?? null) ? json_decode($reportObj->report_data, true) : []);

    $caseSectionLabel = ($courtGranted === 'YES')
        ? 'ONGOING CASES (EXTRACTION COURT ORDERS RECEIVED)'
        : ($reportDataArray['case_section_label'] ?? 'CURRENT/NEW CASE');
    $cleanStatusDisplay = function ($val, $default = 'NO') {
        $str = trim((string)($val ?: $default));
        if ($str === '') {
            return $default;
        }

        if (preg_match('/^(YES|NO|IN PROGRESS|PENDING)\s*[-–—:]\s*(.*)$/is', $str, $matches)) {
            $status = strtoupper(trim($matches[1]));
            $msg = trim($matches[2]);

            $msg = preg_replace('/^' . preg_quote($status, '/') . '\s*[-–—:]\s*/i', '', $msg);
            $msg = trim($msg);

            if ($msg === '' || strtoupper($msg) === $status || in_array(strtoupper($msg), ['N/A', 'NONE', 'NIL', 'NULL', '-', 'NO', 'YES'])) {
                return $status;
            }

            return $status . ' - ' . $msg;
        }

        if (in_array(strtoupper($str), ['YES', 'NO', 'IN PROGRESS', 'PENDING'])) {
            return strtoupper($str);
        }

        if (preg_match('/^(YES|NO)\s+\1$/i', $str, $matches)) {
            return strtoupper($matches[1]);
        }

        return $str;
    };

    $rawExtraction = $reportObj->extraction_analysis_message ?? ($reportObj->extraction_analysis_conducted ?? 'NO');
    $extractionConducted = $cleanStatusDisplay($rawExtraction, 'NO');

    $rawWitnessSent = $reportObj->report_witness_statement_message ?? ($reportObj->report_witness_statement_sent ?? 'NO');
    $reportWitnessSent = $cleanStatusDisplay($rawWitnessSent, 'NO');
    $intelShared = strtoupper($reportObj->intel_shared ?? 'YES');
    $rawRemark = trim((string)($reportObj->remark ?? ''));
    if (empty($rawRemark) || in_array($rawRemark, [
        'Registry intake logged. Electronic exhibits pending forensic write-block extraction and court order verification.',
        'Forensic acquisition completed cleanly. Hardware hash checksums match baseline acquisition.',
        'N/A', 'NONE', 'NIL', 'null'
    ])) {
        $remarkVal = 'N/A';
    } else {
        $remarkVal = $rawRemark;
    }

    if ($reportObj && $reportObj->user) {
        $loggedBy = $reportObj->user->fullname;
    } elseif ($authUser) {
        $loggedBy = $authUser->fullname;
    } else {
        $loggedBy = 'Lead Cyber Analyst';
    }

    $rawCreatedAt = $reportObj->created_at ?? null;
    if ($rawCreatedAt instanceof \Carbon\Carbon || $rawCreatedAt instanceof \DateTimeInterface) {
        $loggedAt = $rawCreatedAt->format('Y-m-d');
    } elseif (!empty($rawCreatedAt)) {
        try {
            $loggedAt = \Carbon\Carbon::parse($rawCreatedAt)->format('Y-m-d');
        } catch (\Throwable $e) {
            $loggedAt = explode(' ', trim((string)$rawCreatedAt))[0] ?: date('Y-m-d');
        }
    } else {
        $loggedAt = date('Y-m-d');
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
    } elseif (isset($case->suspects) && count($case->suspects) > 0) {
        $rawSuspects = $case->suspects->toArray();
    } elseif (isset($case->underlyingRecord) && isset($case->underlyingRecord->suspects) && count($case->underlyingRecord->suspects) > 0) {
        $rawSuspects = $case->underlyingRecord->suspects->toArray();
    }

    if (empty($rawSuspects)) {
        $rawSuspects[] = [
            'suspect_name' => $suspectName,
            'role' => 'PRIMARY SUSPECT',
            'mugshot_front' => $case->mugshot_front ?? null,
        ];
    }

    if (empty($suspectName) || $suspectName === 'PRIMARY SUSPECT') {
        $suspectName = !empty($rawSuspects[0]['suspect_name']) ? $rawSuspects[0]['suspect_name'] : ($case->suspect_name ?? 'PRIMARY SUSPECT');
    }

    $allSuspectNames = array_map(function ($s) {
        return strtoupper($s['suspect_name'] ?? ($s['name'] ?? 'SUSPECT'));
    }, $rawSuspects);
    $dossierSuspectNames = implode(', ', array_unique($allSuspectNames));

    $submittingOfficer = $case->submitting_officer ?? ($docketData['submitting_officer'] ?? ($docketData['submitting'] ?? 'N/A'));
    $raidLoc = $case->raid_location ?? ($case->location ?? ($locationPost ?? 'N/A'));

    // Collect all uploaded attachment files (Court Orders, Reports & Witness Statements)
    $attachedFiles = [];

    // 1. Court Order Files
    $coList = $reportObj->court_order_files ?? [];
    if (empty($coList) && !empty($reportDataArray['court_order_files'])) {
        $coList = $reportDataArray['court_order_files'];
    }
    if (is_string($coList)) {
        $coList = json_decode($coList, true) ?: [$coList];
    }
    if (is_array($coList)) {
        foreach ($coList as $cIdx => $cPath) {
            if (!empty($cPath) && is_string($cPath)) {
                $attachedFiles[] = [
                    'id' => 'attachment-co-' . $cIdx,
                    'path' => $cPath,
                    'section' => 'Court Order Warrant',
                    'title' => 'Judicial Court Order Warrant' . (count($coList) > 1 ? ' (Doc #' . ($cIdx + 1) . ')' : ''),
                    'category' => 'Legal Process Authorization & Judicial Warrant',
                    'badge' => 'COURT ORDER WARRANT',
                ];
            }
        }
    }

    // 2. Report & Witness Statement Files
    $rwList = $reportObj->report_witness_statement_files ?? [];
    if (empty($rwList) && !empty($reportDataArray['report_witness_statement_files'])) {
        $rwList = $reportDataArray['report_witness_statement_files'];
    }
    if (is_string($rwList)) {
        $rwList = json_decode($rwList, true) ?: [$rwList];
    }
    if (is_array($rwList)) {
        foreach ($rwList as $rIdx => $rPath) {
            if (!empty($rPath) && is_string($rPath)) {
                $attachedFiles[] = [
                    'id' => 'attachment-rw-' . $rIdx,
                    'path' => $rPath,
                    'section' => 'Report, Witness Statement & Digital Evidence',
                    'title' => 'Report, Witness Statement & Digital Evidence' . (count($rwList) > 1 ? ' (Doc #' . ($rIdx + 1) . ')' : ''),
                    'category' => '',
                    'badge' => 'REPORT, WITNESS STATEMENT & DIGITAL EVIDENCE',
                ];
            }
        }
    }

    $totalReportPages = 2 + count($attachedFiles);

    if (!isset($renderReportFooter) || !is_callable($renderReportFooter)) {
        $renderReportFooter = function ($currentPage = 1, $totalPages = null) use ($dpfCode, $totalReportPages) {
            $effectivePages = $totalPages ?? $totalReportPages;
            return '
            <div class="pt-auto mb-1 border-t border-slate-200 footer">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-900">
                            <span class="font-bold text-slate-700">' . e($dpfCode) . '</span>
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="mt-0.5 text-[9px] text-slate-700 text-right">
                            <span class="dpf-footer-page-num">Digital &amp; Physical Forensics Unit (DPFU) &bull; Page ' . e($currentPage) . ' of ' . e($effectivePages) . '</span>
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
            $devName = $ex->dev_model 
                ?: ($ex->commodity_name 
                ?: ($ex->exhibit_category 
                ?: ($ex->exhibit_type ?: $devicesRetrieved)));

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

    // Also extract suspect phones if available and no formal exhibits were already found
    if (empty($deviceSpecsList)) {
        $suspectPhones = $case->phones 
            ?? ($case->underlyingRecord->phones 
            ?? ($dData['phones'] 
            ?? ($dData['additional_phones'] 
            ?? ($rawSuspects[0]['phones'] ?? []))));
        if (is_string($suspectPhones)) {
            $suspectPhones = json_decode($suspectPhones, true) ?: [];
        }
        if (is_array($suspectPhones) && !empty($suspectPhones)) {
            foreach ($suspectPhones as $pIdx => $ph) {
                // Skip if this is merely a suspect contact phone number (label + number)
                if (is_array($ph) && !empty($ph['number']) && empty($ph['model']) && empty($ph['brand']) && empty($ph['name'])) {
                    continue;
                }
                $pModel = is_array($ph) ? ($ph['model'] ?? ($ph['name'] ?? ($ph['brand'] ?? null))) : (is_string($ph) && !preg_match('/^\+?[0-9\s\-()]{5,}$/', trim($ph)) ? trim($ph) : null);
                if (empty($pModel) || in_array($pModel, ['Mobile Phone Device', 'N/A'])) {
                    continue;
                }
                $deviceSpecsList[] = [
                    'exhibit_no'  => 'PH-EX-' . ($pIdx + 1),
                    'device_name' => $pModel,
                    'quantity'    => '1',
                    'model'       => $pModel,
                    'serial'      => is_array($ph) ? ($ph['serial'] ?? 'N/A') : 'N/A',
                    'imei1'       => is_array($ph) ? ($ph['imei'] ?? ($ph['imei1'] ?? 'N/A')) : 'N/A',
                    'imei2'       => is_array($ph) ? ($ph['imei2'] ?? 'N/A') : 'N/A',
                    'mac'         => is_array($ph) ? ($ph['mac'] ?? 'N/A') : 'N/A',
                    'storage'     => is_array($ph) ? ($ph['storage'] ?? 'N/A') : 'N/A',
                    'colour'      => is_array($ph) ? ($ph['colour'] ?? 'N/A') : 'N/A',
                    'marks'       => 'Recovered from suspect possession',
                ];
            }
        }
    }

    if (empty($deviceSpecsList) && !empty($docketData)) {
        $exArr = $docketData['exhibits'] ?? ($docketData['seizures'] ?? []);
        if (is_array($exArr)) {
            foreach ($exArr as $ex) {
                $devName = $ex['dev_model'] ?? ($ex['device_model'] ?? ($ex['commodity_name'] ?? ($ex['exhibit_category'] ?? ($ex['exhibit_type'] ?? $devicesRetrieved))));
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
            'device_name' => $reportObj->dev_model ?? ($case->dev_model ?? $devicesRetrieved),
            'quantity'    => $quantity,
            'model'       => $reportObj->dev_model ?? ($case->dev_model ?? 'N/A'),
            'serial'      => $reportObj->dev_serial_no ?? ($case->dev_serial_no ?? 'F2LZX984K102'),
            'imei1'       => $reportObj->dev_imei1 ?? ($case->dev_imei1 ?? '356938035643809'),
            'imei2'       => $reportObj->dev_imei2 ?? ($case->dev_imei2 ?? 'N/A'),
            'mac'         => $reportObj->dev_mac_address ?? ($case->dev_mac_address ?? '00:1A:2B:3C:4D:5E'),
            'storage'     => $reportObj->dev_storage ?? ($case->dev_storage ?? '256 GB'),
            'colour'      => $reportObj->dev_colour ?? ($case->dev_colour ?? 'Space Gray'),
            'marks'       => $reportObj->dev_marks ?? ($case->dev_marks ?? 'Write-blocked physical image captured'),
        ];
    }

    // Compute Exhibit Type display (Quantity and Device Model Name)
    $exhibitTypeParts = [];
    foreach ($deviceSpecsList as $dSpec) {
        $mName = trim((string)($dSpec['model'] ?? ''));
        if ($mName === '' || strtoupper($mName) === 'N/A' || $mName === 'Mobile Devices & Hardware Exhibits') {
            $mName = trim((string)($dSpec['device_name'] ?? ''));
        }
        if ($mName === '' || strtoupper($mName) === 'N/A' || $mName === 'Mobile Devices & Hardware Exhibits') {
            $mName = !empty($reportObj->dev_model) ? trim($reportObj->dev_model) : (!empty($case->dev_model) ? trim($case->dev_model) : '');
        }

        $qVal = trim((string)($dSpec['quantity'] ?? ''));
        if ($qVal === '' || $qVal === '0') {
            $qVal = !empty($quantity) && $quantity !== '0' ? trim((string)$quantity) : '';
        }

        if (!empty($mName) && strtoupper($mName) !== 'N/A' && $mName !== 'Mobile Devices & Hardware Exhibits') {
            if (!empty($qVal)) {
                if (preg_match('/^' . preg_quote($qVal, '/') . '(\s+|x\s*)/i', $mName)) {
                    $exhibitTypeParts[] = $mName;
                } else {
                    $exhibitTypeParts[] = $qVal . ' ' . $mName;
                }
            } else {
                $exhibitTypeParts[] = $mName;
            }
        }
    }

    if (!empty($exhibitTypeParts)) {
        $exhibitTypeDisplay = implode(', ', array_unique($exhibitTypeParts));
    } else {
        $exhibitTypeDisplay = $devicesRetrieved;
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NACOC Digital Forensic Investigation Report - {{ $caseNo }}</title>
    <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    <script src="{{ asset('vendor/pdfjs/pdf.min.js') }}"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        .font-serif-header { font-family: 'Cinzel', serif; }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
        
        .a4-page-attachment {
            padding: 6mm 10mm !important;
        }

        .pdf-page-canvas {
            width: auto;
            max-width: 100%;
            max-height: 970px;
            object-fit: contain;
            margin: 0 auto;
            display: block;
        }
        
        /* A4 Document Styling constraints */
        body {
            font-family: 'Inter', sans-serif;
            background: #ffffffff;
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

        .cover-page {
            width: 210mm;
            height: 297mm;
            max-height: 297mm;
            padding: 14mm 14mm 12mm 14mm;
            overflow: hidden;
        }

        .page-break {
            page-break-after: always;
            break-after: page;
        }

        .footer, .break-inside-avoid, tr, .signature-block, .no-break, .avoid-break {
            break-inside: avoid !important;
            page-break-inside: avoid !important;
        }

        thead {
            display: table-header-group;
        }

        .font-times {
            font-family: 'Times New Roman', Times, Baskerville, Georgia, serif;
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

        @media print {
            @page {
                size: A4 portrait;
                margin: 0;
            }
            body {
                background: transparent !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .a4-page {
                width: 210mm !important;
                height: 297mm !important;
                max-height: 297mm !important;
                min-height: 297mm !important;
                margin: 0 !important;
                padding: 10mm 12mm 10mm 12mm !important;
                box-shadow: none !important;
                page-break-after: always !important;
                break-after: page !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                background: #ffffff !important;
            }
            .cover-page {
                padding: 14mm 14mm 12mm 14mm !important;
            }
            .a4-page.a4-page-attachment {
                padding: 8mm 12mm !important;
                height: 297mm !important;
                max-height: 297mm !important;
            }
            .a4-page:last-child {
                page-break-after: avoid !important;
                break-after: avoid !important;
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
                <button onclick="handlePrintDocument()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs font-mono-code rounded-lg flex items-center gap-1.5 transition-colors cursor-pointer border-0">
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
        <div class="a4-page cover-page page-break relative text-slate-900">

            <!-- SUBTLE NACOC WATERMARK -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.07] select-none">
                <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[900px] h-[900px] object-contain" />
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
                            <p class="mt-0.5 text-[9px] font-bold uppercase text-slate-800" id="page1HeaderBadge">
                                DPF &bull; Page 1 of <span class="doc-total-pages">{{ $totalReportPages }}</span>
                            </p>
                        </div>

                        <!-- CENTERED LOGO -->
                        <div class="mx-auto w-20 h-20 flex items-center justify-center mb-7">
                            <img src="{{ asset('NACOC_LOGO.PNG') }}" alt="NACOC Emblem Logo" class="max-h-40 max-w-40 object-contain" />
                        </div>

                        <!-- INSTITUTION DETAILS -->
                        <div>
                            <h1 class="text-lg font-bold uppercase tracking-wide text-slate-900 font-serif-header">
                                NARCOTICS CONTROL COMMISSION
                            </h1>
                            <p class="mt-1 text-[10.5px] font-semibold uppercase tracking-[0.12em] text-slate-700">
                                FORENSIC DEPARTMENT
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
                        <p id="pdfCaseSectionLabel" class="mt-7 text-base font-extrabold uppercase tracking-wider font-times {{ $courtGranted === 'YES' ? 'text-emerald-800' : 'text-slate-900' }}">
                            {{ $caseSectionLabel }}
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
                                <p class="text-[8px] font-bold uppercase tracking-wider text-slate-700">Report Date</p>
                                <p class="mt-1 text-sm font-bold text-slate-900">{{ $loggedAt }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 01: SUSPECT & CASE INFORMATION -->
                    <div class="mt-7">
                        <div class="flex items-center gap-3 mb-1.5">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-50 text-[9px] font-bold text-emerald-700">
                                01
                            </span>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-[12px] font-bold uppercase tracking-[0.12em] text-slate-900">
                                        Suspect &amp; Case Information
                                    </h3>
                                    <span class="text-[8px] font-semibold uppercase tracking-wider text-slate-700">
                                        High Court Admissible
                                    </span>
                                </div>
                                <div class="mt-1 h-px bg-slate-200"></div>
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
                                        @php
                                            $sRoleBadge = strtoupper($sItem['role'] ?? ($sIdx === 0 ? 'PRIMARY SUSPECT' : 'CO-SUSPECT'));
                                        @endphp
                                        <span class="text-[7px] font-extrabold uppercase tracking-wider {{ str_contains($sRoleBadge, 'PRIMARY') ? 'text-emerald-700' : 'text-slate-600' }}">
                                            {{ $sRoleBadge }}
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
                                        {{ $exhibitTypeDisplay }}
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
                                        Location of Arrest
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
                {!! $renderReportFooter(1, $totalReportPages) !!}

            </div>
        </div>

        <!-- ============================================================
             PAGE 2: ANALYTICAL & TECHNICAL INVESTIGATION LOG REPORT
             ============================================================ -->
        <div class="a4-page report-page auto-paginated-root relative text-slate-900 {{ !empty($attachedFiles) ? 'page-break' : '' }}">

            <!-- SUBTLE NACOC WATERMARK -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.07] select-none">
                <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[900px] h-[900px] object-contain" />
            </div>

            <div class="relative z-10 flex flex-col justify-between h-full min-h-0">

                <!-- PAGE HEADER -->
                <div class="page-header-block shrink-0">
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
                            <p class="mt-0.5 text-[10px] font-bold text-slate-900 dpf-header-page-num">Page 2 of <span class="doc-total-pages">{{ $totalReportPages }}</span></p>
                        </div>
                    </div>

                    <!-- DOCUMENT TITLE SUBHEADER -->
                    <div class="mt-2 pt-1.5 border-t border-slate-100 flex items-center justify-between">
                        <div>
                            <span class="text-[8px] font-bold uppercase tracking-wider text-slate-700">Title</span>
                            <span class="ml-2 text-[10px] font-bold uppercase text-slate-900">Digital Forensic Investigation Status Log</span>
                        </div>
                        <div>
                            <span class="text-[8px] font-bold uppercase tracking-wider text-slate-700">Doc. No.</span>
                            <span class="ml-2 text-[10px] font-bold text-slate-900 font-mono-code">{{ $dpfCode }}</span>
                        </div>
                    </div>
                </div>

                <!-- FLOW CONTENT BODY -->
                <div class="report-flow-body flex-1 min-h-0 flex flex-col justify-between">
                    <div class="flow-sections-container">
                        <!-- SECTION 01: BRIEF CASE DESCRIPTION & OPERATIONAL CONTEXT -->
                        <div class="flow-block section-01 mt-3">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-50 text-[8.5px] font-bold text-emerald-700">
                                    01
                                </span>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-[12px] font-bold uppercase tracking-[0.12em] text-slate-900">
                                            Brief Case Description
                                        </h3>
                                        <span class="text-[8px] font-semibold uppercase tracking-wider text-slate-700">
                                            Intelligence Fact Sheet
                                        </span>
                                    </div>
                                    <div class="mt-0.5 h-px bg-slate-200"></div>
                                </div>
                            </div>

                            <div class="text-[12px] leading-relaxed text-slate-700 whitespace-pre-line pt-0">
                                {{ $briefDescription }}
                            </div>
                        </div>

                        <!-- SECTION 02: LEGAL & OPERATIONAL PROCESS STATUS -->
                        <div class="flow-block section-02 mt-3 avoid-break">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-50 text-[8.5px] font-bold text-emerald-700">
                                    02
                                </span>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-[12px] font-bold uppercase tracking-[0.12em] text-slate-900">
                                            Legal &amp; Operational Process Status
                                        </h3>
                                        <span class="text-[8px] font-semibold uppercase tracking-wider text-slate-700">
                                            Warrant &amp; Docket Verification
                                        </span>
                                    </div>
                                    <div class="mt-0.5 h-px bg-slate-200"></div>
                                </div>
                            </div>

                            <div class="overflow-hidden border-y border-slate-300">
                                <table class="w-full text-left border-collapse table-fixed">
                                    <thead>
                                        <tr class="border-b border-slate-300 bg-slate-100/50">
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
                                            <td class="py-1 px-2.5 text-center font-extrabold {{ $courtRequested === 'YES' ? 'text-emerald-800' : 'text-slate-900' }}">{{ $courtRequested }}</td>
                                        </tr>
                                        <tr class="border-b border-slate-200">
                                            <td class="py-1 px-2.5 border-r border-slate-300 font-medium">
                                                Court Order Warrant Granted
                                                @if(!empty($coList))
                                                    <span class="ml-2 text-[8px] font-mono-code font-bold text-emerald-700">
                                                        ✔ {{ count($coList) === 1 ? '1 Document Attached' : count($coList) . ' Documents Attached' }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="py-1 px-2.5 text-center font-extrabold {{ $courtGranted === 'YES' ? 'text-emerald-800' : 'text-slate-900' }}">{{ $courtGranted }}</td>
                                        </tr>
                                        <tr class="border-b border-slate-200">
                                            <td class="py-1 px-2.5 border-r border-slate-300 font-medium">Extraction &amp; Forensic Analysis Conducted</td>
                                            <td class="py-1 px-2.5 text-center font-extrabold {{ str_starts_with(strtoupper($extractionConducted), 'YES') ? 'text-emerald-800' : (str_starts_with(strtoupper($extractionConducted), 'NO') ? 'text-rose-800' : 'text-slate-900') }}">{{ $extractionConducted }}</td>
                                        </tr>
                                        <tr class="border-b border-slate-200">
                                            <td class="py-1 px-2.5 border-r border-slate-300 font-medium">
                                                Report, Witness Statement &amp; Digital Evidence Sent to Investigations
                                                @if(!empty($rwList))
                                                    <span class="ml-2 text-[8px] font-mono-code font-bold text-emerald-700">
                                                        ✔ {{ count($rwList) === 1 ? '1 Document Attached' : count($rwList) . ' Documents Attached' }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="py-1 px-2.5 text-center font-extrabold {{ str_starts_with(strtoupper($reportWitnessSent), 'YES') ? 'text-emerald-800' : (str_starts_with(strtoupper($reportWitnessSent), 'NO') ? 'text-rose-800' : 'text-slate-900') }}">{{ $reportWitnessSent }}</td>
                                        </tr>
                                        <tr class="border-b border-slate-200">
                                            <td class="py-1 px-2.5 border-r border-slate-300 font-medium">Intelligence Shared to Intel Department for Action</td>
                                            <td class="py-1 px-2.5 text-center font-extrabold {{ $intelShared === 'YES' ? 'text-emerald-800' : 'text-slate-900' }}">{{ $intelShared }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- SECTION 03: REMARKS & OBSERVATIONS -->
                        <div class="flow-block section-03 mt-3">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-50 text-[8.5px] font-bold text-emerald-700">
                                    03
                                </span>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-[12px] font-bold uppercase tracking-[0.12em] text-slate-900">
                                            Remarks
                                        </h3>
                                        <span class="text-[8px] font-semibold uppercase tracking-wider text-slate-700">
                                            Technical Observations
                                        </span>
                                    </div>
                                    <div class="mt-0.5 h-px bg-slate-200"></div>
                                </div>
                            </div>

                            <div class="flow-text text-[12px] leading-[2] text-slate-900 text-justify whitespace-pre-line pt-0">
                                {{ $remarkVal }}
                            </div>
                        </div>

                        <!-- CONDITIONS & DISCLAIMER NOTE -->
                        <div class="flow-block section-conditions mt-3 pt-2 border-t border-slate-200 avoid-break">
                            <p class="text-[9px] font-semibold text-slate-800 mb-0.5 italic">
                                Note: The result(s) relate only to the item(s) / digital exhibit(s) examined
                            </p>
                            <p class="text-[9px] font-extrabold uppercase tracking-wider text-slate-900 mb-0.5">
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
                    <div class="flow-block section-signatures mt-3 pb-6 footer avoid-break">
                        <div class="grid grid-cols-2 gap-10">
                            <!-- HEAD OF DIGITAL & PHYSICAL FORENSICS UNIT -->
                            <div class="flex flex-col">
                                <p class="text-[8px] font-bold uppercase tracking-wider text-slate-700">Head of Digital &amp; Physical Forensics</p>
                                
                                <!-- SIGNATURE AREA (MATCHING HEIGHT, FIXED TO PREVENT HEADER MOVING UP) -->
                                <div class="relative h-20 flex items-end pb-1">
                                    @if(!empty($dpfHeadSignature))
                                        <img src="{{ $dpfHeadSignature }}" alt="Official Digital Signature - {{ $dpfHeadName }}" class="absolute bottom-0 left-0 h-24 max-h-[96px] max-w-[300px] w-auto object-contain object-left filter contrast-125 mix-blend-multiply pointer-events-none" />
                                    @endif
                                </div>

                                <div class="border-b border-dashed border-slate-300 pb-1">
                                    <span class="text-[8px] italic text-slate-700">Electronically Signed &amp; Validated</span>
                                </div>
                                <div class="mt-1">
                                    <p class="text-[10px] font-bold text-slate-900 leading-tight">{{ $dpfHeadName }}</p>
                                    <p class="text-[8px] text-slate-900 leading-tight">Head of Digital &amp; Physical Forensics Unit</p>
                                    <p class="text-[7.5px] text-slate-700 leading-tight">Digital &amp; Physical Forensics Unit, NACOC</p>
                                </div>
                            </div>

                            <!-- HEAD OF FORENSIC DEPARTMENT -->
                            <div class="flex flex-col">
                                <p class="text-[8px] font-bold uppercase tracking-wider text-slate-700">Head of Forensic Department</p>
                                
                                <!-- SIGNATURE AREA (MATCHING HEIGHT, FIXED TO PREVENT HEADER MOVING UP) -->
                                <div class="relative h-20 flex items-end pb-1">
                                    @if(!empty($deptHeadSignature))
                                        <img src="{{ $deptHeadSignature }}" alt="Official Digital Signature - {{ $deptHeadName }}" class="absolute bottom-0 left-0 h-24 max-h-[96px] max-w-[300px] w-auto object-contain object-left filter contrast-125 mix-blend-multiply pointer-events-none" />
                                    @endif
                                </div>

                                <div class="border-b border-dashed border-slate-300 pb-1">
                                    <span class="text-[8px] italic text-slate-700">Authorized &amp; Counter-signed</span>
                                </div>
                                <div class="mt-1">
                                    <p class="text-[10px] font-bold text-slate-900 leading-tight">{{ $deptHeadName }}</p>
                                    <p class="text-[8px] text-slate-900 leading-tight">Head of Forensic Department</p>
                                    <p class="text-[7.5px] text-slate-700 leading-tight">Forensic Department, NACOC</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PAGE FOOTER -->
                <div class="page-footer-block shrink-0 mt-auto">
                    {!! $renderReportFooter(2, $totalReportPages) !!}
                </div>

            </div>
        </div>

        <!-- ============================================================
             ATTACHED EVIDENCE & PROCESS EXHIBIT PAGES (APPENDIX)
             ============================================================ -->
        @if(!empty($attachedFiles))
            @foreach($attachedFiles as $attIndex => $att)
                @php
                    $attPageNum = 3 + $attIndex;
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
                @endphp

                <div id="{{ $att['id'] }}" class="a4-page a4-page-attachment relative text-slate-900 page-break">
                    <!-- SUBTLE NACOC WATERMARK -->
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.07] select-none">
                        <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Watermark" class="w-[900px] h-[900px] object-contain" />
                    </div>

                    <div class="relative z-10 flex flex-col justify-between h-full min-h-0">
                        <div>
                            <!-- PAGE HEADER -->
                            <div class="relative pt-2 pb-3 border-b border-slate-300 flex items-center justify-between">
                                <div class="flex items-center flex-wrap gap-x-2 gap-y-0.5 max-w-[80%]">
                                    <span class="text-[9px] font-bold uppercase tracking-wider text-slate-900 font-serif-header">
                                        NACOC DPF REPORT &bull; ATTACHED EXHIBIT
                                    </span>
                                    <span class="font-mono-code text-[9px] font-semibold text-slate-800">
                                        Ref: {{ $caseNo }} &bull; {{ $dpfCode }}
                                    </span>
                                    <span class="text-[9px] font-bold uppercase tracking-wider text-slate-900 font-serif-header">
                                        &bull; Suspect: <span class="font-mono-code text-slate-800 font-bold">{{ strtoupper($suspectName) }}</span>
                                    </span>
                                </div>
                                <div class="text-right">
                                    <p class="text-[8px] font-bold uppercase tracking-wider text-slate-900">Confidential</p>
                                    <p class="mt-0.5 text-[9px] font-bold uppercase text-slate-800">
                                        Page {{ $attPageNum }} of {{ $totalReportPages }}
                                    </p>
                                </div>
                            </div>

                            <!-- ATTACHED FILE TITLE & INFO -->
                            <div class="mt-2.5 pb-1 flex items-center justify-between">
                                <h3 class="text-[10px] font-bold uppercase tracking-wider text-slate-900 font-serif-header">
                                    {{ $att['title'] }} &bull; Suspect: {{ strtoupper($suspectName) }}
                                </h3>
                                <div class="text-[8px] text-slate-500 font-mono-code">
                                    {{ $cleanFilename }}@if($fileSizeStr) &bull; {{ $fileSizeStr }}@endif
                                </div>
                            </div>

                            <!-- FILE DISPLAY CONTAINER -->
                            <div class="mt-2.5">
                                @if($isImage)
                                    <!-- IMAGE FILE DISPLAY -->
                                    <div class="border border-slate-300 rounded-lg overflow-hidden bg-slate-100/60 p-2 flex flex-col items-center justify-center min-h-[620px] max-h-[980px]">
                                        <img src="{{ $fileAssetUrl }}" alt="{{ $att['title'] }}" class="max-w-full max-h-[950px] object-contain rounded shadow-sm border border-slate-200" />
                                        <div class="mt-1 text-[7.5px] font-mono-code text-slate-500 uppercase tracking-wider text-center">
                                            Attached Photographic / Scanned Exhibit Capture
                                        </div>
                                    </div>
                                @elseif($isPdf)
                                    <!-- MULTIPAGE PDF CONTAINER: RENDERED PAGE-BY-PAGE ON DEDICATED A4 SHEETS WITHOUT SCROLLBARS -->
                                    <div class="pdf-multipage-attachment"
                                         data-pdf-url="{{ $fileAssetUrl }}"
                                         data-att-index="{{ $attIndex + 1 }}"
                                         data-att-title="{{ $att['title'] }}"
                                         data-att-category="{{ $att['category'] }}"
                                         data-att-badge="{{ $att['badge'] }}"
                                         data-filename="{{ $cleanFilename }}"
                                         data-filesize="{{ $fileSizeStr }}"
                                         data-case-no="{{ $caseNo }}"
                                         data-dpf-code="{{ $dpfCode }}"
                                         data-suspect-name="{{ strtoupper($suspectName) }}"
                                         data-watermark="{{ asset('nacoc.jfif') }}">
                                        <!-- INITIAL PREVIEW / FALLBACK -->
                                        <div class="border border-slate-300 rounded-lg overflow-hidden bg-white shadow-xs p-8 text-center flex flex-col items-center justify-center min-h-[480px]">
                                            <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-700 flex items-center justify-center mb-3">
                                                <svg class="w-6 h-6 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                            </div>
                                            <h4 class="text-sm font-bold text-slate-900 mb-1">Formatting Attached Document Pages...</h4>
                                            <p class="text-[10px] text-slate-500 mb-4 max-w-sm">Every page of this official PDF attachment is being generated as an individual full-bleed forensic exhibit sheet.</p>
                                            <a href="{{ $fileAssetUrl }}" target="_blank" class="px-3.5 py-1.5 bg-emerald-700 text-white rounded text-xs font-bold inline-flex items-center gap-1.5 shadow-xs no-underline">
                                                <span>Direct Download / View PDF</span>
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <!-- GENERIC DOCUMENT (DOC, DOCX, OTHER) -->
                                    <div class="border border-slate-300 rounded-lg bg-slate-50 p-10 text-center flex flex-col items-center justify-center min-h-[380px]">
                                        <div class="w-16 h-16 rounded-2xl bg-white border border-slate-300 shadow-sm flex items-center justify-center text-slate-700 mb-4">
                                            <svg class="w-8 h-8 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        </div>
                                        <h4 class="text-sm font-bold text-slate-900 mb-1">{{ $cleanFilename }}</h4>
                                        <p class="text-xs text-slate-600 max-w-md mb-5">Official digital case file attachment ({{ strtoupper($fExt) }} document). Click below to view or download the original file.</p>
                                        <a href="{{ $fileAssetUrl }}" target="_blank" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded text-xs font-bold transition-colors inline-flex items-center gap-2 shadow-sm no-underline">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                            <span>Download / View {{ strtoupper($fExt) }} Document</span>
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- APPENDIX FOOTER -->
                        {!! $renderReportFooter($attPageNum, $totalReportPages) !!}
                    </div>
                </div>
            @endforeach
        @endif

    </div>

    <script>
        window.isPdfRendering = false;

        if (window.pdfjsLib) {
            pdfjsLib.GlobalWorkerOptions.workerSrc = "{{ asset('vendor/pdfjs/pdf.worker.min.js') }}";
        }

        async function renderAllPdfAttachments() {
            const wrappers = document.querySelectorAll('.pdf-multipage-attachment');
            if (!wrappers.length || !window.pdfjsLib) {
                renumberAllPages();
                return;
            }

            window.isPdfRendering = true;

            for (const wrapper of wrappers) {
                const pdfUrl = wrapper.getAttribute('data-pdf-url');
                if (!pdfUrl) continue;

                try {
                    const loadingTask = pdfjsLib.getDocument({
                        url: pdfUrl
                    });
                    const pdfDoc = await loadingTask.promise;
                    const numPages = pdfDoc.numPages;

                    const attIdx = wrapper.getAttribute('data-att-index');
                    const attTitle = wrapper.getAttribute('data-att-title') || 'Attached Exhibit';
                    const attCategory = wrapper.getAttribute('data-att-category') || 'Document Attachment';
                    const attBadge = wrapper.getAttribute('data-att-badge') || 'ATTACHMENT';
                    const filename = wrapper.getAttribute('data-filename') || 'document.pdf';
                    const filesize = wrapper.getAttribute('data-filesize') || '';
                    const caseNo = wrapper.getAttribute('data-case-no') || '';
                    const dpfCode = wrapper.getAttribute('data-dpf-code') || '';
                    const suspectName = wrapper.getAttribute('data-suspect-name') || '';
                    const watermarkUrl = wrapper.getAttribute('data-watermark') || '';

                    // Find parent .a4-page to replace or empty wrapper
                    const parentPage = wrapper.closest('.a4-page');
                    const insertTarget = parentPage ? parentPage : wrapper;
                    const parentContainer = insertTarget.parentNode;

                    const generatedPages = [];

                    for (let pNum = 1; pNum <= numPages; pNum++) {
                        const page = await pdfDoc.getPage(pNum);

                        // High resolution scale for sharp, un-blurred print & display with increased dimensions
                        const unscaledViewport = page.getViewport({ scale: 1 });
                        const maxDisplayW = 750;
                        const maxDisplayH = 970;
                        const scaleFactor = Math.min(maxDisplayW / unscaledViewport.width, maxDisplayH / unscaledViewport.height);
                        const renderScale = Math.max(scaleFactor * 2.5, 2.5);
                        const viewport = page.getViewport({ scale: renderScale });

                        const canvas = document.createElement('canvas');
                        canvas.width = viewport.width;
                        canvas.height = viewport.height;
                        canvas.style.width = `${Math.floor(unscaledViewport.width * scaleFactor)}px`;
                        canvas.style.height = `${Math.floor(unscaledViewport.height * scaleFactor)}px`;
                        canvas.className = 'pdf-page-canvas rounded border border-slate-200 shadow-sm block';

                        const renderContext = {
                            canvasContext: canvas.getContext('2d'),
                            viewport: viewport
                        };
                        await page.render(renderContext).promise;

                        const pageEl = document.createElement('div');
                        pageEl.className = 'a4-page a4-page-attachment relative text-slate-900 page-break';
                        pageEl.id = `attachment-a${attIdx}-p${pNum}`;

                        const subpageNote = (numPages > 1) ? ` (Sheet ${pNum} of ${numPages})` : '';

                        pageEl.innerHTML = `
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-0 overflow-hidden opacity-[0.07] select-none">
                                <img src="${watermarkUrl}" alt="NACOC Watermark" class="w-[900px] h-[900px] object-contain" />
                            </div>
                            <div class="relative z-10 flex flex-col justify-between h-full min-h-0">
                                <div>
                                    <div class="relative pt-2 pb-2.5 border-b border-slate-300 flex items-center justify-between">
                                        <div class="flex items-center flex-wrap gap-x-2 gap-y-0.5 max-w-[80%]">
                                            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-900 font-serif-header">
                                                NACOC DPF REPORT &bull; ATTACHED EXHIBIT
                                            </span>
                                            <span class="font-mono-code text-[9px] font-semibold text-slate-800">
                                                Ref: ${caseNo} &bull; ${dpfCode}
                                            </span>
                                            ${suspectName ? `
                                            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-900 font-serif-header">
                                                &bull; Suspect: <span class="font-mono-code text-slate-800 font-bold">${suspectName}</span>
                                            </span>
                                            ` : ''}
                                        </div>
                                        <div class="text-right">
                                            <p class="text-[8px] font-bold uppercase tracking-wider text-slate-900">Confidential</p>
                                            <p class="mt-0.5 text-[9px] font-bold uppercase text-slate-800 dpf-header-page-num">
                                                Page -
                                            </p>
                                        </div>
                                    </div>

                                    <!-- ATTACHED FILE TITLE & INFO -->
                                    <div class="mt-2.5 pb-1 flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-[10px] font-bold uppercase tracking-wider text-slate-900 font-serif-header">
                                                ${attTitle} ${suspectName ? `&bull; Suspect: ${suspectName}` : ''}
                                            </h3>
                                        </div>
                                        <div class="text-[8px] text-slate-500 font-mono-code">
                                            ${filename}${filesize ? ' &bull; ' + filesize : ''}
                                        </div>
                                    </div>

                                    <div class="mt-1 flex flex-col items-center justify-center min-h-[620px] max-h-[995px] overflow-hidden">
                                        <div class="canvas-mount-point flex items-center justify-center w-full"></div>
                                        <div class="mt-1 text-[7.5px] font-mono-code text-slate-500 uppercase tracking-wider text-center">
                                            Forensic Exhibit Sheet &bull; Document Page ${pNum} of ${numPages}
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-auto mb-9 border-t footer">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-[8px] font-semibold uppercase tracking-wider text-slate-900">
                                                <span class="font-bold text-slate-700">${dpfCode}</span>
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="mt-0.5 text-[9px] text-slate-700 text-right">
                                                Digital &amp; Physical Forensics Unit (DPFU) &bull; <span class="dpf-footer-page-num">Page -</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;

                        pageEl.querySelector('.canvas-mount-point').appendChild(canvas);
                        generatedPages.push(pageEl);
                    }

                    // Insert all generated individual pages and remove the placeholder
                    generatedPages.forEach(p => parentContainer.insertBefore(p, insertTarget));
                    parentContainer.removeChild(insertTarget);

                } catch (err) {
                    // Silently ignore PDF render error
                }
            }

            window.isPdfRendering = false;
            renumberAllPages();
        }

        function autoPaginateAllReports() {
            const reportRoots = document.querySelectorAll('#dpfReportSheet .auto-paginated-root');
            reportRoots.forEach(page => paginateReportPage(page));
        }

        function paginateReportPage(pageEl) {
            const inner = pageEl.querySelector('.relative.z-10');
            const headerBlock = pageEl.querySelector('.page-header-block');
            const footerBlock = pageEl.querySelector('.page-footer-block');
            const flowBody = pageEl.querySelector('.report-flow-body');
            if (!inner || !headerBlock || !footerBlock || !flowBody) return;

            const sectionsContainer = flowBody.querySelector('.flow-sections-container');
            const signaturesBlock = flowBody.querySelector('.section-signatures');
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

                const wm = pageEl.querySelector('.absolute.inset-0');
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
                remarksHeadingWrap.className = 'flow-block section-03-heading mt-3 avoid-break-after';
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

        function renumberAllPages() {
            const pages = document.querySelectorAll('#dpfReportSheet .a4-page');
            const total = pages.length;

            const p1Badge = document.getElementById('page1HeaderBadge');
            if (p1Badge) p1Badge.innerHTML = `DPF &bull; Page 1 of <span class="doc-total-pages">${total}</span>`;

            document.querySelectorAll('.doc-total-pages').forEach(el => el.textContent = total);

            pages.forEach((p, idx) => {
                const pageNum = idx + 1;
                const hBadge = p.querySelector('.dpf-header-page-num');
                if (hBadge) hBadge.innerHTML = `Page ${pageNum} of <span class="doc-total-pages">${total}</span>`;

                const fBadge = p.querySelector('.dpf-footer-page-num');
                if (fBadge) fBadge.textContent = `Digital & Physical Forensics Unit (DPFU) • Page ${pageNum} of ${total}`;
            });
        }

        window.handlePrintDocument = function() {
            if (window.isPdfRendering) {
                alert('Document pages are currently being prepared for print. Please try again in a few seconds.');
                return;
            }
            window.print();
        };

        async function initDpfReport() {
            if (document.fonts && document.fonts.ready) {
                try {
                    await document.fonts.ready;
                } catch (e) {}
            }
            autoPaginateAllReports();
            await renderAllPdfAttachments();
            renumberAllPages();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initDpfReport);
        } else {
            initDpfReport();
        }
    </script>
</body>
</html>
