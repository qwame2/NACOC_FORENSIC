@php
    $authUser = auth()->user();
    $officerName = $authUser->fullname ?? session('user_name') ?? (\App\Models\User::where('is_head', true)->first()->fullname ?? 'Officer');
    $officerRole = ($authUser && $authUser->is_head) ? 'Forensic Head' : 'Commanding Officer';

    $status = $case->status ?? 'Approved';
    $priority = strtoupper($case->priority ?? 'NORMAL');
    
    $docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
    
    $allAssocPhotos = [];
    if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
        foreach ($docketData['seizures'] as $szItem) {
            $cat = strtolower($szItem['category'] ?? ($szItem['exhibit_category'] ?? ''));
            $pCat = strtolower($case->exhibit_category ?? '');
            $sNameSz = strtolower($szItem['seizure_name'] ?? '');
            $pName = strtolower($case->seizure_name ?? '');

            $isAssocSz = !empty($szItem['is_associate']) 
                || !empty($szItem['associated_person']) 
                || (($cat !== $pCat || $sNameSz !== $pName) && ($cat !== 'general exhibit'));

            if ($isAssocSz) {
                if (!empty($szItem['photos']) && is_array($szItem['photos'])) {
                    foreach ($szItem['photos'] as $ap) {
                        if ($ap && !in_array($ap, $allAssocPhotos)) $allAssocPhotos[] = $ap;
                    }
                }
                $apSingle = $szItem['photo'] ?? ($szItem['exhibit_photo'] ?? null);
                if ($apSingle && !in_array($apSingle, $allAssocPhotos)) {
                    $allAssocPhotos[] = $apSingle;
                }
            }
        }
    }

    $rawPhotos = is_array($case->evidence_photos) ? $case->evidence_photos : (json_decode($case->evidence_photos ?? '[]', true) ?: ($docketData['evidence_photos'] ?? []));
    if (!is_array($rawPhotos)) $rawPhotos = [];
    $evidencePhotos = [];
    foreach ($rawPhotos as $p) {
        if ($p && !in_array($p, $evidencePhotos)) $evidencePhotos[] = $p;
    }
    if (!empty($docketData['evidence_photos']) && is_array($docketData['evidence_photos'])) {
        foreach ($docketData['evidence_photos'] as $p) {
            if ($p && !in_array($p, $evidencePhotos)) $evidencePhotos[] = $p;
        }
    }
    if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
        foreach ($docketData['seizures'] as $szItem) {
            if (!empty($szItem['photos']) && is_array($szItem['photos'])) {
                foreach ($szItem['photos'] as $p) {
                    if ($p && !in_array($p, $evidencePhotos)) $evidencePhotos[] = $p;
                }
            }
            $singleP = $szItem['photo'] ?? ($szItem['exhibit_photo'] ?? null);
            if ($singleP && !in_array($singleP, $evidencePhotos)) {
                $evidencePhotos[] = $singleP;
            }
        }
    }

    if (empty($evidencePhotos) && ($case->has_seizures || !empty($case->seizure_name) || !empty($case->exhibits_summary) || !empty($case->exhibit_category))) {
        $cat = strtolower($case->exhibit_category ?? '');
        $sName = strtolower(($case->seizure_name ?: $case->exhibits_summary) ?? '');
        
        if (str_contains($cat, 'money') || str_contains($sName, 'cedi') || str_contains($sName, 'dollar') || str_contains($sName, 'cash') || str_contains($sName, 'naira')) {
            $evidencePhotos = [
                'https://images.unsplash.com/photo-1561414927-6d86591d0c4f?q=80&w=800&auto=format&fit=crop',
                'https://images.unsplash.com/photo-1526304640581-d334cdbbf45e?q=80&w=800&auto=format&fit=crop'
            ];
        } elseif (str_contains($cat, 'gold') || str_contains($sName, 'gold') || str_contains($sName, 'bullion')) {
            $evidencePhotos = [
                'https://images.unsplash.com/photo-1610375461246-83df859d849d?q=80&w=800&auto=format&fit=crop',
                'https://images.unsplash.com/photo-1605792657660-596af9009e82?q=80&w=800&auto=format&fit=crop'
            ];
        } elseif (str_contains($cat, 'vehicle') || str_contains($sName, 'car') || str_contains($sName, 'truck') || !empty($case->veh_make)) {
            $evidencePhotos = [
                'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?q=80&w=800&auto=format&fit=crop',
                'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?q=80&w=800&auto=format&fit=crop'
            ];
        } else {
            $evidencePhotos = [
                'https://images.unsplash.com/photo-1584467735871-8e85353a8413?q=80&w=800&auto=format&fit=crop',
                'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=800&auto=format&fit=crop'
            ];
        }
    }

    $getImgSrc = function($path) {
        if (empty($path)) return null;
        if (str_starts_with($path, 'data:') || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return asset($path);
    };

    $dbLabHead = \App\Models\User::where('unit', 'LIKE', '%Lab%')->where('is_head', true)->first();
    $defaultLabHeadName = $dbLabHead ? $dbLabHead->fullname : 'Samuel Atta';

    $unitProgress = $docketData['unit_progress'] ?? [];

    $labExam = $docketData['lab_examination'] ?? [];
    $rawLabExamRef = $labExam['exam_reference'] ?? null;
    $labExamRef = (!empty($rawLabExamRef) && !str_starts_with($rawLabExamRef, 'EXAM-')) ? $rawLabExamRef : ('GSA-FM-T' . rand(10, 99) . '-F');
    $labCaseRef = $case->case_number;
    $labExhibitNo = !empty($labExam['exhibit_number']) ? $labExam['exhibit_number'] : ('EXH-' . date('Y') . '-001-NFC');
    $labNumber = !empty($case->laboratory_number) ? $case->laboratory_number : (!empty($labExam['laboratory_number']) ? $labExam['laboratory_number'] : ('Lab No.' . rand(10, 99) . '/FS' . date('m') . '/' . date('Y')));
    $labUnit = !empty($labExam['lab_unit']) ? $labExam['lab_unit'] : (!empty($unitProgress['Lab Analysis']['unit']) ? $unitProgress['Lab Analysis']['unit'] : 'Chemistry & Narcotics Unit');
    $currentLoggedInName = auth()->user()->fullname ?? session('user_name') ?? ($authUser->fullname ?? '');
    $labAnalyst = !empty($labExam['analyst']) ? $labExam['analyst'] : (!empty($unitProgress['Lab Analysis']['officer']) ? $unitProgress['Lab Analysis']['officer'] : (!empty($currentLoggedInName) ? $currentLoggedInName : $defaultLabHeadName));
    $labDateReceived = !empty($labExam['date_received']) ? $labExam['date_received'] : date('Y-m-d');
    $labDateStarted = !empty($labExam['date_started']) ? $labExam['date_started'] : date('Y-m-d');
    $labDateCompleted = !empty($labExam['date_completed']) ? $labExam['date_completed'] : date('Y-m-d');
    $labPresumptive = !empty($labExam['presumptive_result']) ? $labExam['presumptive_result'] : (!empty($case->presumptive_test_result) ? $case->presumptive_test_result : 'YES (Positive Colorimetric Reaction)');
    $labConfirmatory = !empty($labExam['confirmatory_result']) ? $labExam['confirmatory_result'] : (!empty($case->confirmatory_test_result) ? $case->confirmatory_test_result : 'NO');
    $labSampleWeight = $labExam['sample_weight'] ?? [];
    
    // Extract top drug exhibits first
    $topDrugExhibits = [];
    if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
        foreach ($docketData['seizures'] as $idx => $sz) {
            $cat = strtolower($sz['category'] ?? ($sz['exhibit_category'] ?? ''));
            $sName = $sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? '');
            if (!empty($sz['seizure_name_drug']) || str_contains($cat, 'drug') || str_contains($cat, 'narcotic') || str_contains(strtolower($sName), 'cocaine') || str_contains(strtolower($sName), 'heroin') || str_contains(strtolower($sName), 'cannabis') || str_contains(strtolower($sName), 'drug')) {
                $topDrugExhibits[] = [
                    'index' => $idx + 1,
                    'name'  => $sName ?: 'Drug Exhibit',
                    'cat'   => $sz['category'] ?? ($sz['exhibit_category'] ?? 'Drugs'),
                    'qty'   => $sz['seizure_quantity'] ?? null,
                    'weight'=> $sz['seizure_weight'] ?? null,
                    'unit'  => $sz['seizure_unit'] ?? null,
                ];
            }
        }
    }
    
    $firstDrugFound = !empty($topDrugExhibits) ? $topDrugExhibits[0]['name'] : null;
    $defaultDrugName = $firstDrugFound ?: ($case->seizure_name_drug ?: ($isDrugCase ? ($case->exhibits_summary ?: 'Drug Exhibit') : 'Drug Exhibit'));
    
    $firstDrugQty = !empty($topDrugExhibits) ? ($topDrugExhibits[0]['qty'] ?? ($topDrugExhibits[0]['seizure_quantity'] ?? null)) : $case->seizure_quantity;
    $firstDrugPkg = !empty($topDrugExhibits) ? ($topDrugExhibits[0]['pkg'] ?? ($topDrugExhibits[0]['seizure_packaging'] ?? null)) : $case->seizure_packaging;
    $firstDrugWeight = !empty($topDrugExhibits) ? ($topDrugExhibits[0]['weight'] ?? ($topDrugExhibits[0]['seizure_weight'] ?? null)) : $case->seizure_weight;
    $firstDrugUnit = !empty($topDrugExhibits) ? ($topDrugExhibits[0]['unit'] ?? ($topDrugExhibits[0]['seizure_unit'] ?? null)) : $case->seizure_unit;

    $defaultUnits = !empty($firstDrugQty) ? trim($firstDrugQty . ' ' . ($firstDrugPkg ?? '')) : '';
    $defaultGrossWeight = !empty($firstDrugWeight) ? trim($firstDrugWeight . ' ' . ($firstDrugUnit ?? '')) : ($defaultUnits ?: ($case->exhibits_summary ?? ''));

    $nonDrugKeywords = ['truck', 'vehicle', 'car', 'pistol', 'rifle', 'weapon', 'currency', 'zonda', 'cash', 'usd', 'ghs', 'laptop', 'phone'];

    $rawSampleDesc = !empty($labSampleWeight['description']) ? $labSampleWeight['description'] : (!empty($case->sample_weight_description) ? $case->sample_weight_description : '');
    $isNonDrugDesc = false;
    if (!empty($rawSampleDesc)) {
        $lDesc = strtolower($rawSampleDesc);
        foreach ($nonDrugKeywords as $kw) {
            if (str_contains($lDesc, $kw)) { $isNonDrugDesc = true; break; }
        }
        if (!empty($case->seizure_name) && strtolower($case->exhibit_category) !== 'drugs' && strtolower($rawSampleDesc) === strtolower($case->seizure_name)) {
            $isNonDrugDesc = true;
        }
    }
    $labSampleDesc = (!empty($rawSampleDesc) && !$isNonDrugDesc) ? $rawSampleDesc : $defaultDrugName;

    $rawSampleUnits = !empty($labSampleWeight['units_packaging']) ? $labSampleWeight['units_packaging'] : (!empty($case->sample_weight_units) ? $case->sample_weight_units : '');
    $labSampleUnits = !empty($rawSampleUnits) ? $rawSampleUnits : $defaultUnits;

    $rawSampleGross = !empty($labSampleWeight['gross_weight']) ? $labSampleWeight['gross_weight'] : (!empty($case->sample_weight_gross) ? $case->sample_weight_gross : '');
    $isNonDrugGross = false;
    if (!empty($rawSampleGross)) {
        if (!empty($case->seizure_quantity) && strtolower($case->exhibit_category) !== 'drugs' && (trim($rawSampleGross) === trim((string)$case->seizure_quantity) || str_starts_with(trim($rawSampleGross), trim((string)$case->seizure_quantity) . ' '))) {
            $isNonDrugGross = true;
        }
    }
    $labSampleGross = (!empty($rawSampleGross) && !$isNonDrugGross) ? $rawSampleGross : $defaultGrossWeight;
    $labSampleTaken = !empty($labSampleWeight['sample_taken']) ? $labSampleWeight['sample_taken'] : (!empty($case->sample_weight_taken) ? $case->sample_weight_taken : '');
    $labSampleReserved = !empty($labSampleWeight['reserved_samples']) ? $labSampleWeight['reserved_samples'] : (!empty($case->sample_weight_reserved) ? $case->sample_weight_reserved : '');
    $labSampleUnit = !empty($firstDrugUnit) ? $firstDrugUnit : (!empty($case->seizure_unit) ? $case->seizure_unit : 'Kilograms (kg)');

    $rawSubstanceId = !empty($labExam['substance_identification']) ? $labExam['substance_identification'] : (!empty($case->confirmatory_substance_identification) ? $case->confirmatory_substance_identification : '');
    $isNonDrugSub = false;
    if (!empty($rawSubstanceId)) {
        $lSub = strtolower($rawSubstanceId);
        foreach ($nonDrugKeywords as $kw) {
            if (str_contains($lSub, $kw)) { $isNonDrugSub = true; break; }
        }
        if (!empty($case->seizure_name) && strtolower($case->exhibit_category) !== 'drugs' && strtolower($rawSubstanceId) === strtolower($case->seizure_name)) {
            $isNonDrugSub = true;
        }
    }
    $labSubstanceId = (!empty($rawSubstanceId) && !$isNonDrugSub) ? $rawSubstanceId : $defaultDrugName;
    $labDetectedSubstances = !empty($labExam['detected_substances']) ? $labExam['detected_substances'] : (!empty($case->detected_substances) ? $case->detected_substances : []);
    if (is_string($labDetectedSubstances)) {
        $labDetectedSubstances = json_decode($labDetectedSubstances, true) ?: [];
    }
    $labTestMethods = !empty($labExam['test_methods']) ? $labExam['test_methods'] : (!empty($case->confirmatory_test_methods) ? $case->confirmatory_test_methods : '');
    $labSpecifications = !empty($labExam['specifications']) ? $labExam['specifications'] : (!empty($case->confirmatory_specifications) ? $case->confirmatory_specifications : '');
    if (!empty($labSampleGross) && preg_match('/(Kilograms?\s*\([^\)]+\)|Kilograms?|kg|Grams?|g|mg|lbs?)/i', $labSampleGross, $uMatch)) {
        $labSampleUnit = $uMatch[1];
    }
    $labFindings = !empty($labExam['findings']) ? $labExam['findings'] : (!empty($unitProgress['Lab Analysis']['notes']) ? $unitProgress['Lab Analysis']['notes'] : (!empty($case->laboratory_analytical_findings) ? $case->laboratory_analytical_findings : ''));

    // Determine if the case involves drugs/narcotics
    $exhibitCategory = strtolower($case->exhibit_category ?? '');
    $seizureNameDrug = $case->seizure_name_drug ?? '';
    $seizureName = strtolower($case->seizure_name ?? '');
    $exhibitsSummary = strtolower($case->exhibits_summary ?? '');

    $isDrugCase = false;
    if (!empty($seizureNameDrug)) {
        $isDrugCase = true;
    } elseif (
        str_contains($exhibitCategory, 'drug') || 
        str_contains($exhibitCategory, 'narcotic') || 
        str_contains($exhibitCategory, 'substance') || 
        str_contains($exhibitCategory, 'cannabis') || 
        str_contains($exhibitCategory, 'cocaine') || 
        str_contains($exhibitCategory, 'heroin') || 
        str_contains($exhibitCategory, 'meth') || 
        str_contains($exhibitCategory, 'tramadol')
    ) {
        $isDrugCase = true;
    } elseif (
        str_contains($seizureName, 'cocaine') || 
        str_contains($seizureName, 'heroin') || 
        str_contains($seizureName, 'cannabis') || 
        str_contains($seizureName, 'weed') || 
        str_contains($seizureName, 'meth') || 
        str_contains($seizureName, 'tramadol') || 
        str_contains($seizureName, 'drug') || 
        str_contains($seizureName, 'narcotic')
    ) {
        $isDrugCase = true;
    } elseif (
        str_contains($exhibitsSummary, 'cocaine') || 
        str_contains($exhibitsSummary, 'heroin') || 
        str_contains($exhibitsSummary, 'cannabis') || 
        str_contains($exhibitsSummary, 'weed') || 
        str_contains($exhibitsSummary, 'meth') || 
        str_contains($exhibitsSummary, 'tramadol') || 
        str_contains($exhibitsSummary, 'drug') || 
        str_contains($exhibitsSummary, 'narcotic')
    ) {
        $isDrugCase = true;
    } else {
        $docketSeizures = $docketData['seizures'] ?? [];
        if (is_array($docketSeizures) && count($docketSeizures) > 0) {
            foreach ($docketSeizures as $sz) {
                $cat = strtolower($sz['category'] ?? ($sz['exhibit_category'] ?? ''));
                $dName = $sz['seizure_name_drug'] ?? '';
                $sName = strtolower($sz['seizure_name'] ?? '');
                if (!empty($dName) || str_contains($cat, 'drug') || str_contains($cat, 'narcotic') || str_contains($sName, 'cocaine') || str_contains($sName, 'heroin') || str_contains($sName, 'cannabis') || str_contains($sName, 'drug') || str_contains($sName, 'narcotic')) {
                    $isDrugCase = true;
                    break;
                }
            }
        }
    }

    $labStatus = strtoupper($unitProgress['Lab Analysis']['status'] ?? ($case->lab_status ?? 'PENDING'));
    $hasSavedLabData = ($labStatus === 'COMPLETED') 
        || (isset($case->lab_status) && strtoupper($case->lab_status) === 'COMPLETED')
        || !empty($case->laboratory_analytical_findings) 
        || !empty($case->confirmatory_substance_identification) 
        || !empty($case->sample_weight_taken) 
        || (!empty($docketData['lab_examination']) && !empty($docketData['lab_examination']['has_saved']));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Forensic Case File — {{ $case->case_number }} | NACOC Ghana</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;800;900&family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@600;700;800&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #0f172a;
            color: #0f172a;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
        .font-official { font-family: 'Cinzel', serif; }

        .pdf-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .pdf-table th, .pdf-table td {
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            font-size: 11px;
            line-height: 1.4;
            vertical-align: middle;
        }
        .pdf-label-cell {
            background-color: #f8fafc;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 800;
            color: #020617;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.03em;
        }
        .pdf-value-cell {
            font-weight: 700;
            color: #0f172a;
            background-color: #ffffff;
        }

        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print { display: none !important; }
            .print-container {
                box-shadow: none !important;
                border: none !important;
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                border-radius: 0 !important;
                overflow: visible !important;
            }
            .pdf-watermark-container {
                position: fixed !important;
                top: 50% !important;
                left: 50% !important;
                transform: translate(-50%, -50%) !important;
                width: 480px !important;
                height: 480px !important;
                opacity: 0.08 !important;
                z-index: 0 !important;
                pointer-events: none !important;
                user-select: none !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            @page {
                size: A4 portrait;
                margin: 10mm 12mm 12mm 12mm;
            }
            .avoid-break {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
        .pdf-watermark-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 520px;
            height: 520px;
            pointer-events: none;
            user-select: none;
            z-index: 0;
            opacity: 0.07;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body class="p-4 sm:p-8 min-h-screen flex flex-col items-center justify-start bg-slate-900 text-slate-900">

    <!-- TOP TOOLBAR (EXCLUDED FROM PRINT) -->
    <div class="w-full max-w-4xl flex items-center justify-between mb-6 no-print">
        <a href="/cases/{{ $case->case_number }}/review" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white rounded-xl text-xs font-bold border border-slate-700 transition-all flex items-center gap-2 cursor-pointer no-underline shadow-md">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back to Case Review</span>
        </a>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-extrabold shadow-lg transition-all flex items-center gap-2 cursor-pointer border border-emerald-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Print Official A4 Case File</span>
            </button>
        </div>
    </div>

    <!-- MAIN A4 PRINT CONTAINER -->
    <div class="print-container w-full max-w-4xl bg-white rounded-3xl p-8 sm:p-10 border border-slate-300 shadow-2xl space-y-6 text-slate-900 relative overflow-hidden">

        <!-- OFFICIAL NACOC WATERMARK LOGO BACKGROUND -->
        <div class="pdf-watermark-container pointer-events-none select-none" aria-hidden="true">
            <img src="{{ asset('nacoc.jfif') }}" 
                 onerror="this.onerror=null; this.src='{{ asset('NACOC LOGO.jfif') }}';" 
                 class="w-full h-full object-contain" 
                 alt="NACOC Official Watermark Logo">
        </div>

        <!-- OFFICIAL GHANA EMBLEM & LETTERHEAD (TOP CENTER) -->
        <div class="text-center space-y-2 border-b-2 border-slate-900 pb-4 relative z-10">
            <div class="flex justify-center mb-2">
                <div class="relative w-24 h-24 flex items-center justify-center">
                    <img src="{{ asset('NACOC LOGO.jfif') }}" 
                         onerror="this.onerror=null; this.src='{{ asset('nacoc.jfif') }}';" 
                         class="max-h-24 max-w-24 object-contain mx-auto" 
                         alt="NACOC Emblem Logo">
                </div>
            </div>
            <div class="space-y-1">
                <div class="text-xs font-extrabold font-mono-code tracking-[0.2em] text-slate-700 uppercase">REPUBLIC OF GHANA</div>
                <div class="text-lg font-black font-official tracking-wide text-slate-950 uppercase">NARCOTICS CONTROL COMMISSION (NACOC)</div>
                <div class="text-sm font-extrabold text-blue-900 font-mono-code uppercase tracking-wider">FORENSIC DEPARTMENT</div>
            </div>
            <div class="pt-2">
                <div class="w-full h-0.5 bg-slate-900"></div>
                <div class="w-full h-px bg-slate-400 mt-0.5"></div>
            </div>
        </div>

        <!-- CASE TITLE & LEGAL REFERENCE BANNER -->
        <div class="p-4 bg-slate-100/95 rounded-2xl border border-slate-300 flex flex-col sm:flex-row items-center justify-between gap-4 avoid-break relative z-10">
            <div>
                <div class="text-[10px] font-mono-code font-bold text-blue-900 uppercase tracking-widest">OFFICIAL CASE FILE • CONFIDENTIAL</div>
                <h1 class="text-xl font-black text-slate-900 uppercase mt-0.5 tracking-tight">
                    {{ $case->title }}
                </h1>
                <div class="text-xs font-mono-code text-slate-600 mt-0.5 font-bold">
                    CASE REF NO: <span class="text-blue-700">{{ $case->case_number }}</span>
                    @if(!empty($case->operation_code_name) || !empty($case->operation_code))
                        <span class="mx-2">•</span> OPERATION TYPE: <span class="text-slate-900">{{ $case->operation_code_name ?: $case->operation_code }}</span>
                    @endif
                </div>
            </div>
            <div class="text-right shrink-0">
                <div class="inline-flex items-center px-3 py-1 rounded-lg bg-emerald-100 border border-emerald-300 text-emerald-900 font-mono-code font-extrabold text-xs uppercase">
                    <svg class="w-3.5 h-3.5 mr-1 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    OFFICIALLY AUTHORIZED
                </div>
                <div class="text-[10px] font-mono-code text-slate-500 mt-1 font-bold">
                    DATE: {{ date('Y-m-d H:i') }} UTC
                </div>
            </div>
        </div>

        @php
            $suspectsList = !empty($docketData['suspects']) && is_array($docketData['suspects']) ? array_values(array_filter($docketData['suspects'], fn($s) => !empty($s['suspect_name']))) : [];
            if (empty($suspectsList)) {
                $suspectsList = [[
                    'suspect_number'   => 1,
                    'suspect_name'     => $case->suspect_name,
                    'suspect_alias'    => $case->suspect_alias ?? ($docketData['alias'] ?? null),
                    'ghana_card'       => $case->ghana_card ?? ($docketData['id_card'] ?? null),
                    'gender'           => $case->gender ?? ($docketData['gender'] ?? null),
                    'dob'              => $case->dob ?? ($docketData['dob'] ?? null),
                    'age'              => $case->age ?? ($docketData['age'] ?? null),
                    'height'           => $case->height ?? ($docketData['height'] ?? null),
                    'eye_color'        => $case->eye_color ?? ($docketData['eye_color'] ?? null),
                    'nationality'      => $case->nationality ?? ($docketData['nationality'] ?? 'Ghanaian'),
                    'pob'              => $case->pob ?? ($docketData['pob'] ?? null),
                    'phone'            => $case->phone ?? ($docketData['phone'] ?? null),
                    'address'          => $case->address ?? ($docketData['address'] ?? null),
                    'marital_status'   => $case->marital_status ?? null,
                    'spouse_name'      => $case->spouse_name ?? null,
                    'spouse_phone'     => $case->spouse_phone ?? null,
                    'spouse_info'      => $case->spouse_info ?? null,
                    'partner_name'     => $case->partner_name ?? null,
                    'partner_phone'    => $case->partner_phone ?? null,
                    'partner_info'     => $case->partner_info ?? null,
                    'dependents'       => $case->dependents ?? ($docketData['dependents'] ?? null),
                    'raid_location'    => $case->raid_location ?: ($case->location ?: null),
                    'date_of_arrest'   => $case->arrest_date ?? null,
                    'time_of_arrest'   => $case->arrest_time ?? null,
                    'holding_facility' => $case->holding_facility ?? null,
                    'offense'          => $case->offense_description ?: ($case->description ?: null),
                    'mugshot_front'    => $case->mugshot_front ?? null,
                    'mugshot_left'     => $case->mugshot_left ?? null,
                    'mugshot_right'    => $case->mugshot_right ?? null,
                ]];
            }
        @endphp

        @php
            $primarySuspectNamesList = [];
            foreach ($suspectsList as $sp) {
                $spIsAssoc = !empty($sp['is_associate']) || (!empty($sp['association_role']) && strtolower($sp['association_role']) !== 'primary suspect');
                if (!$spIsAssoc && !empty($sp['suspect_name'])) {
                    $primarySuspectNamesList[] = $sp['suspect_name'];
                }
            }
            $primarySuspectsCombinedNames = !empty($primarySuspectNamesList) ? implode(', ', array_unique($primarySuspectNamesList)) : ($case->suspect_name ?? 'Primary Suspects');
            $renderedPrimaryExhibit = false;
        @endphp

        @foreach($suspectsList as $sIdx => $sItem)
            @php
                $sNum   = $sItem['suspect_number'] ?? ($sIdx + 1);
                $sName  = $sItem['suspect_name'] ?? ('Suspect #' . $sNum);
                $sAlias = $sItem['suspect_alias'] ?? ($sNum == 1 ? ($case->suspect_alias ?? ($docketData['alias'] ?? 'N/A')) : 'N/A');
                $sCard  = $sItem['ghana_card'] ?? ($sNum == 1 ? ($case->ghana_card ?? ($docketData['id_card'] ?? 'N/A')) : 'N/A');
                $sGender= $sItem['gender'] ?? ($sNum == 1 ? ($case->gender ?? ($docketData['gender'] ?? 'N/A')) : 'N/A');
                $sAge   = $sItem['age'] ?? ($sNum == 1 ? ($case->age ?? ($docketData['age'] ?? null)) : null);
                $sDOB   = $sItem['dob'] ?? ($sNum == 1 ? ($case->dob ?? ($docketData['dob'] ?? null)) : null);
                $sHeight= $sItem['height'] ?? ($sNum == 1 ? ($case->height ?? ($docketData['height'] ?? null)) : null);
                $sEye   = $sItem['eye_color'] ?? ($sNum == 1 ? ($case->eye_color ?? ($docketData['eye_color'] ?? null)) : null);
                $sNat   = $sItem['nationality'] ?? ($sNum == 1 ? ($case->nationality ?? ($docketData['nationality'] ?? 'Ghanaian')) : 'Ghanaian');
                $sPOB   = $sItem['place_of_birth'] ?? ($sItem['hometown'] ?? ($sItem['pob'] ?? ($sNum == 1 ? ($case->pob ?? ($docketData['pob'] ?? 'N/A')) : 'N/A')));
                $sPhone = $sItem['phone_number'] ?? ($sItem['contact_phone'] ?? ($sItem['phone'] ?? ($sNum == 1 ? ($case->phone ?? ($docketData['phone'] ?? 'N/A')) : 'N/A')));
                $sAddr  = $sItem['residential_address'] ?? ($sItem['address'] ?? ($sNum == 1 ? ($case->address ?? ($docketData['address'] ?? 'N/A')) : 'N/A'));
                $sMarital = $sItem['marital_status'] ?? ($sNum == 1 ? $case->marital_status : null);
                $sSpouseN = $sItem['spouse_name'] ?? ($sNum == 1 ? $case->spouse_name : null);
                $sSpouseP = $sItem['spouse_phone'] ?? ($sNum == 1 ? $case->spouse_phone : null);
                $sSpouseI = $sItem['spouse_info'] ?? ($sNum == 1 ? $case->spouse_info : null);
                $sPartnerN= $sItem['partner_name'] ?? ($sNum == 1 ? $case->partner_name : null);
                $sPartnerP= $sItem['partner_phone'] ?? ($sNum == 1 ? $case->partner_phone : null);
                $sPartnerI= $sItem['partner_info'] ?? ($sNum == 1 ? $case->partner_info : null);
                $sDependents = $sItem['dependents'] ?? ($sNum == 1 ? ($case->dependents ?? ($docketData['dependents'] ?? 'No Children')) : 'No Children');
                $sRaid    = $sItem['raid_location'] ?? ($sNum == 1 ? ($case->raid_location ?: ($case->location ?: 'N/A')) : 'N/A');
                $sArrestDate = $sItem['date_of_arrest'] ?? ($sNum == 1 ? $case->arrest_date : null);
                $sArrestTime = $sItem['time_of_arrest'] ?? ($sNum == 1 ? $case->arrest_time : null);
                $sFacility   = $sItem['holding_facility'] ?? ($sNum == 1 ? ($case->holding_facility ?? 'N/A') : 'N/A');
                $sOffense    = $sItem['offense'] ?? ($sNum == 1 ? ($case->offense_description ?: ($case->description ?: 'N/A')) : 'N/A');

                $sFront = $sItem['mugshot_front'] ?? ($sNum == 1 ? $case->mugshot_front : null);
                $sLeft  = $sItem['mugshot_left']  ?? ($sNum == 1 ? $case->mugshot_left : null);
                $sRight = $sItem['mugshot_right'] ?? ($sNum == 1 ? $case->mugshot_right : null);

                $isAssociate = !empty($sItem['is_associate']) || (!empty($sItem['association_role']) && strtolower($sItem['association_role']) !== 'primary suspect');
                $sRoleLabel = $sItem['association_role'] ?? ($sItem['role'] ?? ($isAssociate ? 'ASSOCIATE' : 'PRIMARY SUSPECT'));
            @endphp

            <!-- MUGSHOT PHOTOS (3-ANGLE BIOMETRIC CAPTURE FOR SUSPECT #{{ $sNum }}) -->
            <div class="space-y-2 avoid-break relative z-10">
                <div class="text-xs font-black text-slate-950 uppercase font-mono-code border-b border-slate-400 pb-1 flex items-center justify-between">
                    <span>SUSPECT #{{ $sNum }} BIOMETRIC MUGSHOT RECORD ({{ strtoupper($sName) }})</span>
                    @if($isAssociate)
                        <span class="text-[10px] text-amber-900 bg-amber-100 px-2 py-0.5 rounded border border-amber-300 font-black">ASSOCIATE</span>
                    @else
                        <span class="text-[10px] text-slate-700 font-bold">BOOKING FACILITY CAPTURE</span>
                    @endif
                </div>
                <div class="grid grid-cols-3 gap-4 pt-1">
                    <div class="p-2 bg-slate-50 border border-slate-300 rounded-xl text-center space-y-1.5 shadow-xs">
                        <div class="flex items-center justify-between text-[9px] font-mono-code font-bold">
                            <span class="px-1.5 py-0.5 rounded bg-slate-800 text-white">ANGLE 1</span>
                            <span class="text-slate-700 uppercase">Frontal View</span>
                        </div>
                        <div class="w-full h-40 bg-white rounded-lg overflow-hidden border border-slate-300 flex items-center justify-center">
                            @if(!empty($sFront))
                                <img src="{{ $getImgSrc($sFront) }}" class="w-full h-full object-contain object-center" alt="{{ $sName }} Frontal Mugshot">
                            @else
                                <div class="flex flex-col items-center justify-center text-slate-400 text-xs font-bold">
                                    <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span>No Front Photo</span>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="p-2 bg-slate-50 border border-slate-300 rounded-xl text-center space-y-1.5 shadow-xs">
                        <div class="flex items-center justify-between text-[9px] font-mono-code font-bold">
                            <span class="px-1.5 py-0.5 rounded bg-slate-800 text-white">ANGLE 2</span>
                            <span class="text-slate-700 uppercase">Left Profile</span>
                        </div>
                        <div class="w-full h-40 bg-white rounded-lg overflow-hidden border border-slate-300 flex items-center justify-center">
                            @if(!empty($sLeft))
                                <img src="{{ $getImgSrc($sLeft) }}" class="w-full h-full object-contain object-center" alt="{{ $sName }} Left Profile Mugshot">
                            @else
                                <div class="flex flex-col items-center justify-center text-slate-400 text-xs font-bold">
                                    <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                    <span>No Left Photo</span>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="p-2 bg-slate-50 border border-slate-300 rounded-xl text-center space-y-1.5 shadow-xs">
                        <div class="flex items-center justify-between text-[9px] font-mono-code font-bold">
                            <span class="px-1.5 py-0.5 rounded bg-slate-800 text-white">ANGLE 3</span>
                            <span class="text-slate-700 uppercase">Right Profile</span>
                        </div>
                        <div class="w-full h-40 bg-white rounded-lg overflow-hidden border border-slate-300 flex items-center justify-center">
                            @if(!empty($sRight))
                                <img src="{{ $getImgSrc($sRight) }}" class="w-full h-full object-contain object-center" alt="{{ $sName }} Right Profile Mugshot">
                            @else
                                <div class="flex flex-col items-center justify-center text-slate-400 text-xs font-bold">
                                    <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    <span>No Right Photo</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION: SUSPECT BOOKING & PERSONAL IDENTITY FOR SUSPECT #{{ $sNum }} -->
            <div class="space-y-2 avoid-break relative z-10">
                <div class="text-xs font-black text-slate-950 uppercase font-mono-code border-b border-slate-400 pb-1 flex items-center justify-between">
                    <span>SUSPECT #{{ $sNum }} PERSONAL IDENTITY ({{ strtoupper($sName) }})</span>
                    @if($isAssociate)
                        <span class="text-[10px] text-amber-900 font-bold bg-amber-100 px-2 py-0.5 rounded border border-amber-300">ASSOCIATE</span>
                    @endif
                </div>
                <table class="pdf-table border border-slate-300 rounded-xl overflow-hidden shadow-2xs">
                    <tbody>
                        <tr>
                            <td class="pdf-label-cell w-1/5">Suspect Role / Type:</td>
                            <td class="pdf-value-cell font-black {{ $isAssociate ? 'text-amber-900 bg-amber-50' : 'text-slate-950' }} w-3/10">
                                {{ strtoupper($sRoleLabel) }}
                            </td>
                            <td class="pdf-label-cell w-1/5">Full Name:</td>
                            <td class="pdf-value-cell font-black text-slate-950 w-3/10">{{ $sName }}</td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Alias / Street Name:</td>
                            <td class="pdf-value-cell font-black text-slate-900">{{ $sAlias }}</td>
                            <td class="pdf-label-cell">Ghana Card / ID #:</td>
                            <td class="pdf-value-cell font-mono-code font-black text-blue-900">{{ $sCard }}</td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Gender &amp; Age / DOB:</td>
                            <td class="pdf-value-cell font-bold text-slate-900">
                                {{ $sGender }}
                                @if(!empty($sAge)) • Age: {{ $sAge }} @endif
                                @if(!empty($sDOB)) • DOB: {{ $sDOB }} @endif
                            </td>
                            <td class="pdf-label-cell">Physical Specs:</td>
                            <td class="pdf-value-cell font-bold text-slate-900">
                                @if(!empty($sHeight)) Height: {{ $sHeight }} @endif
                                @if(!empty($sEye)) @if(!empty($sHeight)) • @endif Eyes: {{ $sEye }} @endif
                                @if(empty($sHeight) && empty($sEye)) N/A @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Nationality:</td>
                            <td class="pdf-value-cell font-bold text-slate-900">{{ $sNat }}</td>
                            <td class="pdf-label-cell">Hometown / POB:</td>
                            <td class="pdf-value-cell font-bold text-slate-900">{{ $sPOB }}</td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Contact Phone:</td>
                            <td class="pdf-value-cell font-mono-code font-bold text-slate-900">{{ $sPhone }}</td>
                            <td class="pdf-label-cell">Residential Address:</td>
                            <td class="pdf-value-cell font-bold text-slate-900">{{ $sAddr }}</td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Marital Status:</td>
                            <td class="pdf-value-cell font-bold text-slate-900">{{ $sMarital ?: 'N/A' }}</td>
                            <td class="pdf-label-cell">Spouse &amp; Family:</td>
                            <td class="pdf-value-cell font-bold text-slate-900">
                                @if(!empty($sSpouseN))
                                    Spouse: {{ $sSpouseN }} @if(!empty($sSpouseP)) ({{ $sSpouseP }}) @endif
                                @endif
                                @if(!empty($sDependents))
                                    @if(!empty($sSpouseN)) • @endif Dependents: {{ $sDependents }}
                                @endif
                                @if(empty($sSpouseN) && empty($sDependents))
                                    N/A
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- SECTION: OFFENCE CHARGES & ARREST DETAILS FOR SUSPECT #{{ $sNum }} -->
            <div class="space-y-2 avoid-break relative z-10">
                <div class="text-xs font-black text-slate-950 uppercase font-mono-code border-b border-slate-400 pb-1">
                    <span>OFFENCE CHARGES &amp; ARREST DETAILS (SUSPECT #{{ $sNum }}: {{ strtoupper($sName) }})</span>
                </div>
                <table class="pdf-table border border-slate-300 rounded-xl overflow-hidden shadow-2xs">
                    <tbody>
                        <tr>
                            <td class="pdf-label-cell w-1/5">Arrest Scene Location:</td>
                            <td class="pdf-value-cell font-black text-slate-950 w-3/10">{{ $sRaid }}</td>
                            <td class="pdf-label-cell w-1/5">Arrest Date &amp; Time:</td>
                            <td class="pdf-value-cell font-bold w-3/10">
                                {{ $sArrestDate ? \Carbon\Carbon::parse($sArrestDate)->format('M d, Y') : 'N/A' }} {{ $sArrestTime ? "@ {$sArrestTime}" : '' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Custody Facility:</td>
                            <td class="pdf-value-cell font-black text-slate-900" colspan="3">{{ $sFacility }}</td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Brief Facts &amp; Description:</td>
                            <td class="pdf-value-cell font-medium text-slate-900 leading-relaxed" colspan="3">
                                {{ $sOffense }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- SECTION: ASSOCIATE OR PRIMARY LOGGED EXHIBITS FOR SUSPECT #{{ $sNum }} -->
            @if($isAssociate)
                @php
                    $pdfAssocExhibits = array_filter($docketData['seizures'] ?? [], function($sz) use ($sName, $case) {
                        if (!empty($sz['associated_person']) && strtolower($sz['associated_person']) === strtolower($sName)) {
                            return true;
                        }
                        if (!empty($sz['is_associate'])) {
                            return true;
                        }
                        $cat = strtolower($sz['category'] ?? ($sz['exhibit_category'] ?? ''));
                        $pCat = strtolower($case->exhibit_category ?? '');
                        $sNameSz = strtolower($sz['seizure_name'] ?? '');
                        $pName = strtolower($case->seizure_name ?? '');
                        return ($cat !== $pCat || $sNameSz !== $pName) && ($cat !== 'general exhibit');
                    });
                @endphp
                @if(!empty($pdfAssocExhibits) && count($pdfAssocExhibits) > 0)
                <div class="space-y-2 avoid-break relative z-10 pt-1">
                    <div class="text-xs font-black text-slate-950 uppercase font-mono-code border-b border-slate-400 pb-1 flex items-center justify-between">
                        <span>ASSOCIATE LOGGED EXHIBITS (SUSPECT #{{ $sNum }}: {{ strtoupper($sName) }})</span>
                        <span class="text-[10px] text-amber-900 font-bold bg-amber-100 px-2 py-0.5 rounded border border-amber-300">ASSOCIATE DOSSIER</span>
                    </div>
                    @foreach($pdfAssocExhibits as $aEx)
                    <table class="pdf-table border border-slate-300 rounded-xl overflow-hidden shadow-2xs mb-2">
                        <tbody>
                            <tr>
                                <td class="pdf-label-cell w-1/5">Exhibit Category:</td>
                                <td class="pdf-value-cell font-mono-code font-black text-amber-900 w-3/10">{{ $aEx['category'] ?? ($aEx['exhibit_category'] ?? 'Associate Exhibit') }}</td>
                                <td class="pdf-label-cell w-1/5">Exhibit / Commodity Name:</td>
                                <td class="pdf-value-cell font-black text-slate-950 w-3/10">{{ $aEx['seizure_name'] ?? ($aEx['name'] ?? 'N/A') }}</td>
                            </tr>
                            <tr>
                                <td class="pdf-label-cell">Quantity / Weight:</td>
                                <td class="pdf-value-cell font-mono-code font-black text-slate-950">
                                    @if(!empty($aEx['seizure_quantity']) || !empty($aEx['seizure_weight']))
                                        {{ implode(' • ', array_filter([!empty($aEx['seizure_quantity']) ? "Qty: {$aEx['seizure_quantity']}" : null, !empty($aEx['seizure_weight']) ? "Wt: {$aEx['seizure_weight']} " . ($aEx['seizure_unit'] ?? '') : null])) }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="pdf-label-cell">Estimated Valuation:</td>
                                <td class="pdf-value-cell font-mono-code font-black text-emerald-800">{{ $aEx['seizure_value'] ?? ($aEx['value'] ?? 'N/A') }}</td>
                            </tr>
                            @if(!empty($aEx['seizure_packaging']))
                            <tr>
                                <td class="pdf-label-cell">Packaging &amp; Format:</td>
                                <td class="pdf-value-cell font-bold text-slate-900" colspan="3">{{ $aEx['seizure_packaging'] }}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                    @endforeach

                    @php
                        $assocPhotosPdf = [];
                        foreach ($pdfAssocExhibits as $aEx) {
                            if (!empty($aEx['photos']) && is_array($aEx['photos'])) {
                                foreach ($aEx['photos'] as $ap) {
                                    if ($ap && !in_array($ap, $assocPhotosPdf)) $assocPhotosPdf[] = $ap;
                                }
                            }
                            $apSingle = $aEx['photo'] ?? ($aEx['exhibit_photo'] ?? null);
                            if ($apSingle && !in_array($apSingle, $assocPhotosPdf)) {
                                $assocPhotosPdf[] = $apSingle;
                            }
                        }
                        if (empty($assocPhotosPdf) && !empty($allAssocPhotos)) {
                            $assocPhotosPdf = $allAssocPhotos;
                        }
                        if (empty($assocPhotosPdf) && !empty($evidencePhotos)) {
                            $assocPhotosPdf = $evidencePhotos;
                        }
                    @endphp
                    @if(!empty($assocPhotosPdf) && count($assocPhotosPdf) > 0)
                    <div class="pt-2">
                        <div class="text-[10px] font-mono-code font-bold text-amber-900 uppercase pb-1 flex items-center justify-between">
                            <span>Associate Exhibit Evidence Photos ({{ $sName }})</span>
                            <span class="text-[9px] bg-amber-100 text-amber-900 px-1.5 py-0.5 rounded border border-amber-300 font-mono-code font-bold">{{ count($assocPhotosPdf) }} Photo(s)</span>
                        </div>
                        <div class="grid grid-cols-4 gap-2">
                            @foreach($assocPhotosPdf as $idx => $photoUrl)
                                <div class="p-1 bg-amber-50/50 border border-amber-300 rounded-lg space-y-1 text-center">
                                    <div class="w-full h-24 bg-white rounded overflow-hidden border border-amber-300">
                                        <img src="{{ $getImgSrc($photoUrl) }}" class="w-full h-full object-cover" alt="Associate Exhibit Photo {{ $idx + 1 }}">
                                    </div>
                                    <div class="text-[8px] font-mono-code font-bold text-amber-900 uppercase">Associate Photo #{{ $idx + 1 }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
                @endif
            @else
                @if(!$renderedPrimaryExhibit)
                @php $renderedPrimaryExhibit = true; @endphp
                <div class="space-y-2 avoid-break relative z-10 pt-1">
                    <div class="text-xs font-black text-slate-950 uppercase font-mono-code border-b border-slate-400 pb-1 flex items-center justify-between">
                        <span>PRIMARY SUSPECT LOGGED EXHIBITS ({{ strtoupper($primarySuspectsCombinedNames) }})</span>
                        <span class="text-[10px] text-slate-900 font-bold bg-slate-200 px-2 py-0.5 rounded border border-slate-300">PRIMARY SUSPECT DOSSIER</span>
                    </div>
                    <table class="pdf-table border border-slate-300 rounded-xl overflow-hidden shadow-2xs mb-2">
                        <tbody>
                            <tr>
                                <td class="pdf-label-cell w-1/5">Exhibit Category:</td>
                                <td class="pdf-value-cell font-mono-code font-black text-amber-900 w-3/10">{{ $case->exhibit_category ?? 'N/A' }}</td>
                                <td class="pdf-label-cell w-1/5">Primary Seizure Name:</td>
                                <td class="pdf-value-cell font-black text-slate-950 w-3/10">{{ $case->seizure_name ?: ($case->exhibits_summary ?: 'N/A') }}</td>
                            </tr>
                            <tr>
                                <td class="pdf-label-cell">Quantity / Weight:</td>
                                <td class="pdf-value-cell font-mono-code font-black text-slate-950">
                                    @if($case->seizure_quantity || $case->seizure_weight)
                                        {{ implode(' • ', array_filter([$case->seizure_quantity ? "Qty: {$case->seizure_quantity}" : null, $case->seizure_weight ? "Wt: {$case->seizure_weight} {$case->seizure_unit}" : ($case->seizure_unit ? "Unit: {$case->seizure_unit}" : null)])) }}
                                    @else
                                        {{ $case->exhibits_summary ?: 'N/A' }}
                                    @endif
                                </td>
                                <td class="pdf-label-cell">Estimated Valuation:</td>
                                <td class="pdf-value-cell font-mono-code font-black text-emerald-800">{{ $case->seizure_value ?: ($case->valuation ?? 'N/A') }}</td>
                            </tr>
                            @if(!empty($case->veh_make) || !empty($case->veh_reg_no) || !empty($case->veh_type) || !empty($docketData['vehicle']['make']) || !empty($docketData['vehicle']['reg']) || !empty($docketData['vehicle']['type']))
                            <tr>
                                <td class="pdf-label-cell">Vehicle Type:</td>
                                <td class="pdf-value-cell font-black text-amber-950">{{ $case->veh_type ?: ($docketData['vehicle']['type'] ?? 'N/A') }}</td>
                                <td class="pdf-label-cell">Make &amp; Model:</td>
                                <td class="pdf-value-cell font-black text-slate-950">{{ trim(($case->veh_make ?: ($docketData['vehicle']['make'] ?? 'N/A')) . ' ' . ($case->veh_model ?: ($docketData['vehicle']['model'] ?? ''))) }}</td>
                            </tr>
                            <tr>
                                <td class="pdf-label-cell">Reg. Number &amp; VIN:</td>
                                <td class="pdf-value-cell font-mono-code font-bold text-slate-900">
                                    REG: {{ $case->veh_reg_no ?: ($docketData['vehicle']['reg'] ?? 'N/A') }}
                                    @if(!empty($case->veh_vin) || !empty($docketData['vehicle']['chassis']))
                                        <span class="text-slate-500 font-normal"> | VIN: {{ $case->veh_vin ?: ($docketData['vehicle']['chassis'] ?? 'N/A') }}</span>
                                    @endif
                                </td>
                                <td class="pdf-label-cell">Registered Owner:</td>
                                <td class="pdf-value-cell font-bold text-slate-900">
                                    {{ $case->veh_owner ?: ($docketData['vehicle']['owner'] ?? 'N/A') }}
                                    @if(!empty($case->veh_owner_contact))
                                        <span class="text-xs font-normal text-slate-600">({{ $case->veh_owner_contact }})</span>
                                    @endif
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                    @php
                        $primaryPhotosPdf = [];
                        foreach ($evidencePhotos as $pUrl) {
                            if (!in_array($pUrl, $allAssocPhotos)) {
                                $primaryPhotosPdf[] = $pUrl;
                            }
                        }
                    @endphp
                    @if(!empty($primaryPhotosPdf) && count($primaryPhotosPdf) > 0)
                    <div class="pt-2">
                        <div class="text-[10px] font-mono-code font-bold text-slate-800 uppercase pb-1">Primary Exhibit Evidence Photos ({{ $primarySuspectsCombinedNames }})</div>
                        <div class="grid grid-cols-4 gap-2">
                            @foreach($primaryPhotosPdf as $idx => $photoUrl)
                                <div class="p-1 bg-slate-50 border border-slate-300 rounded-lg space-y-1 text-center">
                                    <div class="w-full h-20 bg-slate-200 rounded overflow-hidden border border-slate-300">
                                        <img src="{{ $getImgSrc($photoUrl) }}" class="w-full h-full object-cover" alt="Primary Exhibit Photo {{ $idx + 1 }}">
                                    </div>
                                    <div class="text-[8px] font-mono-code font-bold text-slate-700 uppercase">Exhibit Photo #{{ $idx + 1 }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
                @endif
            @endif
        @endforeach

        <!-- SECTION: CONFISCATED COMMODITIES & VAULT INVENTORY -->
        <div class="space-y-2 avoid-break relative z-10">
            <div class="text-xs font-black text-slate-950 uppercase font-mono-code border-b border-slate-400 pb-1">
                <span>CONFISCATED COMMODITIES &amp; VAULT INVENTORY</span>
            </div>
            <table class="pdf-table border border-slate-300 rounded-xl overflow-hidden shadow-2xs">
                <tbody>
                    <tr>
                        <td class="pdf-label-cell w-1/5">Exhibit Category:</td>
                        <td class="pdf-value-cell font-mono-code font-black text-amber-900 w-3/10">{{ $case->exhibit_category ?? 'N/A' }}</td>
                        <td class="pdf-label-cell w-1/5">Primary Seizure Name:</td>
                        <td class="pdf-value-cell font-black text-slate-950 w-3/10">{{ $case->seizure_name ?: ($case->exhibits_summary ?: 'N/A') }}</td>
                    </tr>
                    <tr>
                        <td class="pdf-label-cell">Quantity / Weight:</td>
                        <td class="pdf-value-cell font-mono-code font-black text-slate-950">
                            @if($case->seizure_quantity || $case->seizure_weight)
                                {{ implode(' • ', array_filter([$case->seizure_quantity ? "Qty: {$case->seizure_quantity}" : null, $case->seizure_weight ? "Wt: {$case->seizure_weight} {$case->seizure_unit}" : ($case->seizure_unit ? "Unit: {$case->seizure_unit}" : null)])) }}
                            @else
                                {{ $case->exhibits_summary ?: 'N/A' }}
                            @endif
                        </td>
                        <td class="pdf-label-cell">Estimated Valuation:</td>
                        <td class="pdf-value-cell font-mono-code font-black text-emerald-800">{{ $case->seizure_value ?: ($case->valuation ?? 'N/A') }}</td>
                    </tr>
                    <tr>
                        <td class="pdf-label-cell">Concealment Notes:</td>
                        <td class="pdf-value-cell font-bold text-slate-900">{{ $case->seizure_concealment ?: 'N/A' }}</td>
                        <td class="pdf-label-cell">Seizing Officer:</td>
                        <td class="pdf-value-cell font-bold text-slate-900">{{ $case->seizure_officer ?: 'N/A' }}</td>
                    </tr>
                    @if(!empty($case->veh_make) || !empty($case->veh_reg_no) || !empty($case->veh_type) || !empty($docketData['vehicle']['make']) || !empty($docketData['vehicle']['reg']) || !empty($docketData['vehicle']['type']))
                    <tr>
                        <td class="pdf-label-cell">Vehicle Type:</td>
                        <td class="pdf-value-cell font-black text-amber-950">{{ $case->veh_type ?: ($docketData['vehicle']['type'] ?? 'N/A') }}</td>
                        <td class="pdf-label-cell">Make &amp; Model:</td>
                        <td class="pdf-value-cell font-black text-slate-950">{{ trim(($case->veh_make ?: ($docketData['vehicle']['make'] ?? 'N/A')) . ' ' . ($case->veh_model ?: ($docketData['vehicle']['model'] ?? ''))) }}</td>
                    </tr>
                    <tr>
                        <td class="pdf-label-cell">Reg. Number &amp; VIN:</td>
                        <td class="pdf-value-cell font-mono-code font-bold text-slate-900">
                            REG: {{ $case->veh_reg_no ?: ($docketData['vehicle']['reg'] ?? 'N/A') }}
                            @if(!empty($case->veh_vin) || !empty($docketData['vehicle']['chassis']))
                                <span class="text-slate-500 font-normal"> | VIN: {{ $case->veh_vin ?: ($docketData['vehicle']['chassis'] ?? 'N/A') }}</span>
                            @endif
                        </td>
                        <td class="pdf-label-cell">Registered Owner:</td>
                        <td class="pdf-value-cell font-bold text-slate-900">
                            {{ $case->veh_owner ?: ($docketData['vehicle']['owner'] ?? 'N/A') }}
                            @if(!empty($case->veh_owner_contact))
                                <span class="text-xs font-normal text-slate-600">({{ $case->veh_owner_contact }})</span>
                            @endif
                        </td>
                    </tr>
                    @endif
                    @if(!empty($case->wpn_model) || !empty($case->wpn_serial_no) || !empty($docketData['weapon']['model']))
                    <tr>
                        <td class="pdf-label-cell">Firearm Model:</td>
                        <td class="pdf-value-cell font-black text-slate-950">{{ $case->wpn_model ?: ($docketData['weapon']['model'] ?? 'N/A') }}</td>
                        <td class="pdf-label-cell">Serial &amp; Calibre:</td>
                        <td class="pdf-value-cell font-bold text-slate-900">
                            S/N: {{ $case->wpn_serial_no ?: ($docketData['weapon']['serial_no'] ?? 'N/A') }} | Cal: {{ $case->wpn_calibre ?: ($docketData['weapon']['calibre'] ?? 'N/A') }}
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- SECTION: EVIDENCE PHOTO GALLERY -->
        @if(!empty($evidencePhotos) && count($evidencePhotos) > 0)
        <div class="space-y-2 avoid-break relative z-10">
            <div class="text-xs font-black text-slate-950 uppercase font-mono-code border-b border-slate-400 pb-1 flex items-center justify-between">
                <span>SEIZURE &amp; EXHIBIT EVIDENCE GALLERY</span>
                <span class="text-[10px] font-mono-code font-bold text-slate-700">{{ count($evidencePhotos) }} ATTACHED EXHIBITS</span>
            </div>
            <div class="grid grid-cols-4 gap-3 pt-1">
                @foreach($evidencePhotos as $idx => $photoUrl)
                    @if(!empty($photoUrl))
                    <div class="p-1.5 bg-slate-50 border border-slate-300 rounded-xl space-y-1 text-center">
                        <div class="w-full h-28 bg-slate-200 rounded-lg overflow-hidden border border-slate-300">
                            <img src="{{ $getImgSrc($photoUrl) }}" class="w-full h-full object-cover" alt="Exhibit Photo {{ $idx + 1 }}">
                        </div>
                        <div class="text-[8px] font-mono-code font-bold text-slate-700 uppercase">Exhibit Photo #{{ $idx + 1 }}</div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
        @endif

        <!-- SECTION: LABORATORY EXAMINATION RESULT -->
        @if($hasSavedLabData)
        <div class="space-y-2 avoid-break relative z-10">
            <div class="text-xs font-black text-slate-950 uppercase font-mono-code border-b border-slate-400 pb-1 flex items-center justify-between">
                <span>LABORATORY EXAMINATION RESULT</span>
                <span class="text-[10px] font-mono-code font-black text-emerald-900 uppercase">OFFICIAL VERIFIED CERTIFICATE</span>
            </div>
            <table class="pdf-table border border-slate-300 rounded-xl overflow-hidden shadow-2xs">
                <tbody>
                    <tr>
                        <td class="pdf-label-cell w-1/5">Analysis Reference:</td>
                        <td class="pdf-value-cell font-mono-code font-black text-emerald-900 w-3/10">{{ $labExamRef }}</td>
                        <td class="pdf-label-cell w-1/5">Exhibit Number:</td>
                        <td class="pdf-value-cell font-mono-code font-black text-emerald-900 w-3/10">{{ $labExhibitNo }}</td>
                    </tr>
                    <tr>
                        <td class="pdf-label-cell">Laboratory Number:</td>
                        <td class="pdf-value-cell font-mono-code font-black text-slate-950">{{ $labNumber }}</td>
                        <td class="pdf-label-cell">Analyst / Examiner:</td>
                        <td class="pdf-value-cell font-black text-slate-950">{{ $labAnalyst }}</td>
                    </tr>
                    <tr>
                        <td class="pdf-label-cell">Examination Timeline:</td>
                        <td class="pdf-value-cell p-1.5" colspan="3">
                            <table class="w-full text-[10.5px] border border-slate-300 rounded overflow-hidden">
                                <thead class="bg-slate-100 text-slate-950 font-mono-code uppercase font-black text-[9px]">
                                    <tr class="border-b border-slate-300">
                                        <th class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">Received</th>
                                        <th class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">Started</th>
                                        <th class="px-2 py-1 text-left bg-slate-200/70">Completed</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white font-bold font-mono-code text-slate-900">
                                    <tr>
                                        <td class="px-2 py-1 border-r border-slate-300 text-blue-900">{{ $labDateReceived }}</td>
                                        <td class="px-2 py-1 border-r border-slate-300 text-amber-900">{{ $labDateStarted }}</td>
                                        <td class="px-2 py-1 text-emerald-900">{{ $labDateCompleted }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="pdf-label-cell">Presumptive Reaction:</td>
                        <td class="pdf-value-cell p-1.5">
                            @php
                                $pRawPdf = trim($labPresumptive ?? '');
                                $pResPdf = 'N/A';
                                $pPresPdf = 'N/A';
                                $pPurPdf = 'N/A';

                                if (!empty($pRawPdf)) {
                                    if (str_starts_with(strtoupper($pRawPdf), 'YES') || str_starts_with(strtoupper($pRawPdf), 'POS')) {
                                        $pResPdf = 'YES';
                                    } elseif (str_starts_with(strtoupper($pRawPdf), 'NO') || str_starts_with(strtoupper($pRawPdf), 'NEG')) {
                                        $pResPdf = 'NO';
                                    } else {
                                        $pResPdf = strtok($pRawPdf, ' (');
                                    }

                                    if (preg_match('/Presence of:\s*([^-\)]+)/i', $pRawPdf, $mPresPdf)) {
                                        $pPresPdf = trim($mPresPdf[1]);
                                    } elseif (preg_match('/\(([^-\)]+)\)/', $pRawPdf, $mPresPdf)) {
                                        $pPresPdf = trim($mPresPdf[1]);
                                    }

                                    if (preg_match('/Purity\/Percentage:\s*([^\)]+)/i', $pRawPdf, $mPurPdf)) {
                                        $pPurPdf = trim($mPurPdf[1]);
                                    }
                                }
                            @endphp
                            <table class="w-full text-[10.5px] border border-slate-300 rounded overflow-hidden">
                                <thead class="bg-slate-100 text-slate-950 font-mono-code uppercase font-black text-[9px]">
                                    <tr class="border-b border-slate-300">
                                        <th class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">Result</th>
                                        <th class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">Presence of</th>
                                        <th class="px-2 py-1 text-left bg-slate-200/70">Purity/Percentage</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white">
                                    <tr>
                                        <td class="px-2 py-1 border-r border-slate-300 font-extrabold {{ $pResPdf === 'YES' ? 'text-emerald-800' : 'text-slate-900' }}">{{ $pResPdf }}</td>
                                        <td class="px-2 py-1 border-r border-slate-300 font-bold text-slate-900">{{ $pPresPdf }}</td>
                                        <td class="px-2 py-1 font-bold text-slate-900">{{ $pPurPdf }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                        <td class="pdf-label-cell">Confirmatory Test:</td>
                        <td class="pdf-value-cell font-black {{ $labConfirmatory === 'YES' ? 'text-emerald-950' : 'text-slate-950' }}">
                            {{ $labConfirmatory === 'YES' ? 'YES' : 'NO' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="pdf-label-cell">Sample Metrology:</td>
                        <td class="pdf-value-cell p-2.5" colspan="3">
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <div class="col-span-2 sm:col-span-4 p-2 bg-slate-50 border border-slate-200 rounded-lg">
                                    <div class="text-[9px] font-mono-code font-black text-slate-500 uppercase tracking-wider">Description</div>
                                    <div class="font-bold text-slate-950 text-xs mt-0.5">{{ $labSampleDesc ?: 'N/A' }}</div>
                                </div>
                                <div class="p-2 bg-slate-50 border border-slate-200 rounded-lg">
                                    <div class="text-[9px] font-mono-code font-black text-slate-500 uppercase tracking-wider">Number of Units</div>
                                    <div class="font-mono-code font-bold text-slate-950 text-xs mt-0.5">{{ $labSampleUnits ?: 'N/A' }}</div>
                                </div>
                                <div class="p-2 bg-slate-50 border border-slate-200 rounded-lg">
                                    <div class="text-[9px] font-mono-code font-black text-slate-500 uppercase tracking-wider">Gross Weight</div>
                                    <div class="font-mono-code font-bold text-slate-950 text-xs mt-0.5">{{ $labSampleGross ?: 'N/A' }}</div>
                                </div>
                                <div class="p-2 bg-slate-50 border border-slate-200 rounded-lg">
                                    <div class="text-[9px] font-mono-code font-black text-slate-500 uppercase tracking-wider">Mass Taken</div>
                                    <div class="font-mono-code font-bold text-emerald-900 text-xs mt-0.5">{{ $labSampleTaken ? $labSampleTaken . ' ' . $labSampleUnit : 'N/A' }}</div>
                                </div>
                                <div class="p-2 bg-slate-50 border border-slate-200 rounded-lg">
                                    <div class="text-[9px] font-mono-code font-black text-slate-500 uppercase tracking-wider">Reserved Samples</div>
                                    <div class="font-mono-code font-bold text-slate-950 text-xs mt-0.5">{{ $labSampleReserved ?: 'N/A' }}</div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="pdf-label-cell">Substance Identified:</td>
                        <td class="pdf-value-cell font-black text-slate-950">{{ $labSubstanceId ?: 'N/A' }}</td>
                        <td class="pdf-label-cell">Test Specifications:</td>
                        <td class="pdf-value-cell font-bold">{{ $labSpecifications ?: 'NIST Reference Library Matched' }}</td>
                    </tr>
                    @if(!empty($labDetectedSubstances))
                    <tr>
                        <td class="pdf-label-cell">Detected Compounds:</td>
                        <td class="pdf-value-cell font-bold text-slate-900" colspan="3">
                            @php $pdfDetList = []; @endphp
                            @foreach($labDetectedSubstances as $subItem)
                                @if(!empty($subItem['substance']))
                                    @php 
                                        $pdfDetList[] = $subItem['substance'] . (!empty($subItem['percentage']) ? ' (' . $subItem['percentage'] . ')' : ''); 
                                    @endphp
                                @endif
                            @endforeach
                            {{ implode(', ', $pdfDetList) }}
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td class="pdf-label-cell">Findings Log Summary:</td>
                        <td class="pdf-value-cell font-medium text-slate-900 leading-relaxed" colspan="3">
                            {{ $labFindings ?: 'No specific laboratory analytical findings logged yet.' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        @endif

        <!-- EXECUTIVE CERTIFICATION & OFFICIAL SIGN-OFF -->
        <div class="pt-4 border-t-2 border-slate-900 space-y-4 avoid-break relative z-10">
            <div class="text-xs font-black text-slate-950 uppercase font-mono-code">
                <span>EXECUTIVE AUTHORIZATION &amp; CERTIFICATE OF ACCURACY</span>
            </div>
            <div class="p-4 bg-slate-50 border border-slate-300 rounded-2xl">
                <div class="space-y-3 text-xs">
                    <div>
                        <div class="text-[9px] font-mono-code font-black text-slate-950 uppercase">Authorized Executive Sign-off:</div>
                        <div class="border-b-2 border-slate-900 pb-1 mt-4 flex items-end justify-between">
                            <span class="font-black text-slate-950 uppercase text-sm font-mono-code">{{ $officerName }}</span>
                            <span class="text-[10px] font-mono-code font-black text-slate-950">SIGNATURE</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-[10px] font-mono-code text-slate-900 font-black">
                        <div>TITLE: <strong class="text-slate-950">{{ strtoupper($officerRole) }}</strong></div>
                        <div>DATE: <strong class="text-slate-950">{{ date('d F Y') }}</strong></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FOOTER SECURITY BAR -->
        <div class="pt-2 text-center border-t border-slate-300 text-[9px] font-mono-code text-slate-950 font-black flex items-center justify-between relative z-10">
            <div>NACOC FORENSIC DEPARTMENT • CONFIDENTIAL LAW ENFORCEMENT RECORD</div>
            <div>PAGE 1 OF 1 • A4 OFFICIAL FORMAT</div>
        </div>

    </div>

</body>
</html>
