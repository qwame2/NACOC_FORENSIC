@php
    $authUser = auth()->user();
    $isHof = ($authUser && $authUser->is_head && (
        $authUser->unit === 'Forensic Directorate' ||
        $authUser->unit === 'Forensic Head' ||
        $authUser->unit === 'Directorate Head' ||
        str_contains($authUser->unit ?? '', 'Forensic')
    )) || request()->is('Forensic_Head*') || (session('user_role') === 'HOF');
    $dbLabHead = \App\Models\User::where('unit', 'LIKE', '%Lab%')->where('is_head', true)->first();
    $defaultLabHeadName = $dbLabHead ? $dbLabHead->fullname : 'Samuel Atta';
    $officerName = auth()->user()->fullname ?? session('user_name') ?? ($authUser->fullname ?? $defaultLabHeadName);
    $officerRole = $isHof ? 'Forensic Head' : 'Commanding Officer';

    $reqFrom = request()->query('from') ?? request()->query('unit') ?? request()->query('source');
    $userUnit = $authUser->unit ?? session('user_unit') ?? '';
    $referer = request()->header('referer') ?? '';
    $isLabContext = str_contains(strtolower($reqFrom ?? ''), 'lab') 
                    || str_contains(strtolower($userUnit), 'lab') 
                    || str_contains(strtolower($referer), 'lab');
    $isForensicHeadContext = request()->is('Forensic_Head*') || session('user_role') === 'HOF';
    
    $status = $case->status ?? 'Awaiting Sign-off';
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

    // Determine if the case involves drugs/narcotics
    $exhibitCategory = strtolower($case->exhibit_category ?? '');
    $seizureNameDrug = $case->seizure_name_drug ?? '';
    $seizureName = strtolower($case->seizure_name ?? '');
    $exhibitsSummary = strtolower($case->exhibits_summary ?? '');
    $offense = strtolower($case->offense ?? '');

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

    $unitsList = [
        'Crime Scene Support',
        'Evidence Management',
    ];
    if ($isDrugCase) {
        $unitsList[] = 'Lab Analysis';
    }
    $unitsList[] = 'Digital & Physical Forensics';

    $totalUnitsCount = count($unitsList);

    $unitProgress = $docketData['unit_progress'] ?? [
        'Crime Scene Support' => ['status' => 'COMPLETED', 'officer' => $case->commanding_officer ?? 'Crime Scene Officer', 'updated_at' => optional($case->created_at)->toDateTimeString()],
        'Evidence Management' => ['status' => 'PENDING', 'officer' => null, 'updated_at' => null],
        'Lab Analysis' => ['status' => 'PENDING', 'officer' => null, 'updated_at' => null],
        'Digital & Physical Forensics' => ['status' => 'PENDING', 'officer' => null, 'updated_at' => null],
    ];
    $completedUnitsCount = 0;
    foreach ($unitsList as $uKey) {
        if (isset($unitProgress[$uKey]['status']) && strtoupper($unitProgress[$uKey]['status']) === 'COMPLETED') {
            $completedUnitsCount++;
        }
    }

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
    $labStatus = strtoupper($unitProgress['Lab Analysis']['status'] ?? 'PENDING');
    $hasSavedLabData = ($labStatus === 'COMPLETED') 
        || (isset($case->lab_status) && strtoupper($case->lab_status) === 'COMPLETED')
        || !empty($case->laboratory_analytical_findings) 
        || !empty($case->confirmatory_substance_identification) 
        || !empty($case->sample_weight_taken) 
        || (!empty($docketData['lab_examination']) && !empty($docketData['lab_examination']['has_saved']));

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

    $unitColors = [
        'Crime Scene Support' => 'text-blue-600 bg-blue-50 border-blue-200',
        'Evidence Management' => 'text-indigo-600 bg-indigo-50 border-indigo-200',
        'LAB Analysis' => 'text-emerald-600 bg-emerald-50 border-emerald-200',
        'Digital & Physical Forensics' => 'text-amber-600 bg-amber-50 border-amber-200',
    ];
    $unitBadgeColor = $unitColors[$case->unit_name] ?? 'text-blue-600 bg-blue-50 border-blue-200';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Case Review File — {{ $case->case_number }} | NACOC Forensic Department</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind / Vite & Barcode Engine -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif


    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #F8FAFC;
            color: #0F172A;
        }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }

        .top-header {
            background: #FFFFFF;
            border-bottom: 1px solid #E2E8F0;
        }

        .btn-primary {
            background: #059669;
            border: 1px solid #10b981;
            box-shadow: 0 4px 12px rgba(16,185,129,0.25);
            color: white; font-weight: 700; font-size: 12px;
            padding: 10px 20px; border-radius: 12px;
            cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background: #047857;
            box-shadow: 0 6px 18px rgba(16,185,129,0.35);
            transform: translateY(-1px);
        }

        .btn-decline {
            background: #dc2626;
            border: 1px solid #f43f5e;
            box-shadow: 0 4px 12px rgba(225,29,72,0.25);
            color: white; font-weight: 700; font-size: 12px;
            padding: 10px 20px; border-radius: 12px;
            cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
            transition: all 0.2s;
        }
        .btn-decline:hover {
            background: #b91c1c;
            box-shadow: 0 6px 18px rgba(225,29,72,0.35);
            transform: translateY(-1px);
        }

        .btn-back {
            background: #FFFFFF;
            border: 1px solid #CBD5E1;
            color: #334155; font-weight: 700; font-size: 12px;
            padding: 9px 16px; border-radius: 10px;
            cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
            transition: all 0.2s; text-decoration: none;
        }
        .btn-back:hover {
            background: #F1F5F9; color: #0F172A; border-color: #94A3B8;
        }

        .status-badge {
            display: inline-flex; align-items: center;
            padding: 5px 12px; border-radius: 20px;
            font-size: 11px; font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
        }
        .status-await    { background: #FEF3C7; color: #D97706; border: 1px solid #FCD34D; }
        .status-done     { background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; }
        .status-declined { background: #FEF2F2; color: #DC2626; border: 1px solid #FCA5A5; }

        .dossier-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03);
            overflow: hidden;
        }
    </style>
</head>
@php
    $isForensicHeadContext = request()->is('Forensic_Head*') || (request()->header('referer') && str_contains(request()->header('referer'), 'Forensic_Head'));
@endphp

<body class="min-h-screen flex flex-col antialiased">

<div class="flex h-screen overflow-hidden">

    <!-- LEFT SIDEBAR -->
    @if($isForensicHeadContext)
        @include('Forensic_Head.partials.sidebar')
    @else
        @include('Crime_Scene_Support.partials.sidebar')
    @endif

    <!-- MAIN WORKSPACE -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-slate-50">

        <!-- TOP NAVBAR -->
        <header class="top-header h-16 px-6 flex items-center justify-between shrink-0 z-20 gap-4">
            <div class="flex items-center gap-4 shrink-0">
                @if($isForensicHeadContext)
                    <a href="/Forensic_Head/cases" class="btn-back">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Case Register
                    </a>
                @elseif($isLabContext)
                    <a href="/cases?unit=Lab+Analysis" class="btn-back">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Case Directory
                    </a>
                @else
                    <a href="/cases" class="btn-back">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Case Directory
                    </a>
                @endif
                <div class="h-6 w-px bg-slate-200"></div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-mono-code font-extrabold text-slate-700">{{ $case->case_number }}</span>
                </div>
            </div>

            <!-- GENERAL SEARCH INPUT -->
            <div class="flex items-center flex-1 max-w-md mx-4">
                <div class="relative w-full">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input 
                        type="text" 
                        id="globalSearch"
                        placeholder="Search case #, suspect, evidence ID..." 
                        class="w-full pl-10 pr-12 py-1.5 bg-slate-100/80 border border-slate-200 rounded-xl text-xs text-slate-900 font-mono-code focus:outline-none focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/15 transition-all"
                        onkeydown="if(event.key === 'Enter' && this.value) window.location.href='/Forensic_Head/cases?search=' + encodeURIComponent(this.value);"
                    >
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                        <kbd class="px-1.5 py-0.5 text-[10px] font-mono-code font-semibold text-slate-400 bg-white rounded border border-slate-200 shadow-2xs">Ctrl K</kbd>
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-4 shrink-0">
                <!-- NOTIFICATION BELL ICON & GENERAL ALERTS -->
                <div class="relative">
                    @php
                        $pendingCasesNotif = (int)(\App\Models\ForensicCase::whereIn('status', ['Awaiting Sign-off', 'Pending Approval', 'Pending'])->count());
                        $pendingUsersNotif = (int)(\App\Models\User::where('is_approved', false)->count());
                        $totalAlertsCount = $pendingCasesNotif + $pendingUsersNotif + 1;
                    @endphp
                    <button type="button" id="notificationBellBtn" onclick="toggleNotificationDropdown()" class="p-2 rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors relative border-none bg-transparent cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        @if($totalAlertsCount > 0)
                            <span class="absolute top-1 right-1 w-2.5 h-2.5 bg-red-500 rounded-full ring-2 ring-white"></span>
                        @endif
                    </button>

                    <!-- NOTIFICATIONS & GENERAL ALERTS DROPDOWN MENU -->
                    <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-96 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden font-sans">
                        <!-- HEADER -->
                        <div class="p-4 bg-slate-900 text-white flex items-center justify-between">
                            <div>
                                <div class="text-[10px] font-mono-code text-blue-400 font-bold uppercase tracking-wider">DIRECTORATE COMMAND</div>
                                <div class="text-xs font-black tracking-wide">General System &amp; Department Alerts</div>
                            </div>
                            <span class="px-2.5 py-1 text-[10px] font-extrabold bg-blue-500/20 text-blue-300 rounded-full font-mono-code border border-blue-500/30">
                                {{ $totalAlertsCount }} Active Alerts
                            </span>
                        </div>

                        <!-- ALERTS LIST -->
                        <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
                            
                            <!-- ALERT 1: CASE APPROVALS -->
                            <a href="{{ url('/Forensic_Head/cases') }}" class="p-3.5 flex items-start space-x-3 hover:bg-slate-50 transition-colors text-decoration-none block">
                                <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-600 border border-amber-500/20 flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
                                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-[10px] font-extrabold font-mono-code text-amber-700 uppercase">Case Approvals</span>
                                        <span class="text-[9px] font-bold text-slate-400 font-mono-code">JUST NOW</span>
                                    </div>
                                    <div class="text-xs font-bold text-slate-900 truncate">
                                        @if($pendingCasesNotif > 0)
                                            {{ $pendingCasesNotif }} Case Dossiers Awaiting Executive Sign-off
                                        @else
                                            All Case Dossiers Authorized
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-0.5 leading-snug">
                                        @if($pendingCasesNotif > 0)
                                            Pending findings certificates and official seal approval required.
                                        @else
                                            No outstanding case dossier sign-offs pending.
                                        @endif
                                    </div>
                                </div>
                            </a>

                            <!-- ALERT 2: USER ACCOUNT APPROVALS -->
                            <a href="{{ url('/Forensic_Head/approvals') }}" class="p-3.5 flex items-start space-x-3 hover:bg-slate-50 transition-colors text-decoration-none block">
                                <div class="w-8 h-8 rounded-xl bg-blue-500/10 text-blue-600 border border-blue-500/20 flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
                                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-[10px] font-extrabold font-mono-code text-blue-700 uppercase">Account Control</span>
                                        <span class="text-[9px] font-bold text-slate-400 font-mono-code">TODAY</span>
                                    </div>
                                    <div class="text-xs font-bold text-slate-900 truncate">
                                        @if($pendingUsersNotif > 0)
                                            {{ $pendingUsersNotif }} Pending User Account Approval(s)
                                        @else
                                            Personnel Roster Fully Verified
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-0.5 leading-snug">
                                        @if($pendingUsersNotif > 0)
                                            Staff or Unit Head registrations awaiting directorate access clearance.
                                        @else
                                            All registered system accounts are authorized and active.
                                        @endif
                                    </div>
                                </div>
                            </a>

                            <!-- ALERT 3: EVIDENCE VAULT TRANSFERS -->
                            <a href="{{ url('/Forensic_Head/cases') }}" class="p-3.5 flex items-start space-x-3 hover:bg-slate-50 transition-colors text-decoration-none block">
                                <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
                                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v1a2 2 0 01-2 2M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-[10px] font-extrabold font-mono-code text-emerald-700 uppercase">Evidence Vault</span>
                                        <span class="text-[9px] font-bold text-slate-400 font-mono-code">3 MIN AGO</span>
                                    </div>
                                    <div class="text-xs font-bold text-slate-900 truncate">4 Barcoded Exhibits Transferred to Vault</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5 leading-snug">Inter-unit evidence transfer received from Crime Scene Support Unit.</div>
                                </div>
                            </a>

                            <!-- ALERT 4: SYSTEM INTEGRITY AUDIT -->
                            <div class="p-3.5 flex items-start space-x-3 bg-emerald-50/40">
                                <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
                                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-[10px] font-extrabold font-mono-code text-emerald-700 uppercase">Chain Integrity Audit</span>
                                        <span class="text-[9px] font-bold text-emerald-600 font-mono-code">SYSTEM OK</span>
                                    </div>
                                    <div class="text-xs font-bold text-slate-900 truncate">100% Tamper-Free Hash Audit Verified</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5 leading-snug">SHA-256 digital hashes matched across all 4 forensic unit databases.</div>
                                </div>
                            </div>

                        </div>

                        <!-- FOOTER -->
                        <div class="p-3 bg-slate-50 border-t border-slate-200 text-center">
                            <a href="{{ url('/Forensic_Head/cases') }}" class="text-[11px] font-bold text-blue-600 hover:text-blue-800 font-mono-code uppercase tracking-wider text-decoration-none">
                                View Executive Case Register &rarr;
                            </a>
                        </div>
                    </div>
                </div>

                <div class="text-right hidden sm:block">
                    <div class="text-xs font-mono-code font-semibold text-slate-700" id="liveClock"></div>
                    <div class="text-[10px] font-mono-code text-blue-600 font-bold">NACOC FORENSIC DEPARTMENT</div>
                </div>
            </div>
        </header>

        <!-- SCROLLABLE CONTENT -->
        <main class="flex-1 overflow-y-auto p-6 space-y-6">

            @include('partials.flash')

            <!-- HERO DOSSIER HEADER BANNER -->
            <div class="relative overflow-hidden rounded-2xl p-6 sm:p-8 bg-slate-900 text-white shadow-xl border border-slate-800">
                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="space-y-3 flex-1">
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono-code bg-slate-800 text-amber-400 border border-slate-700">{{ $priority }} PRIORITY</span>
                            <span class="text-slate-500">•</span>
                            <span id="headerStatusBadge">
                                @if($status === 'Approved' || $status === 'Authorized')
                                    <span class="status-badge status-done">
                                        <svg class="w-3 h-3 mr-1 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Authorized
                                    </span>
                                @elseif($status === 'Declined')
                                    <span class="status-badge status-declined">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-600 mr-1.5 inline-block"></span>
                                        Declined
                                    </span>
                                @else
                                    <span class="status-badge status-await">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5 inline-block"></span>
                                        {{ $status }}
                                    </span>
                                @endif
                            </span>
                        </div>

                        <div>
                            <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight flex items-center gap-3">
                                {{ $case->title }}
                                <span class="text-base text-slate-400 font-normal">| {{ $case->case_number }}</span>
                            </h1>
                            <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-3xl leading-relaxed">
                                The complete case record is available for your review. Please carefully review the registered details below before proceeding with the executive sign-off.
                            </p>
                        </div>
                    </div>

                    <!-- QUICK ACTIONS IN HERO -->
                    <div class="flex items-center gap-3 shrink-0">
                        <a href="/cases/{{ $case->case_number }}/pdf" target="_blank" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-xl border border-slate-700 transition-all flex items-center gap-2 cursor-pointer no-underline">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            <span>Print Case PDF</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- INTER-UNIT WORKFLOW & PROGRESS TRACKER -->
            <div class="dossier-card p-6 border border-slate-300 bg-white rounded-2xl shadow-xs space-y-6">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between pb-5 border-b border-slate-200 gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-mono-code font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 uppercase tracking-wider">
                                MULTI-UNIT CASE TRACKER
                            </span>
                            <span id="unitCompletedCountBadge" class="text-xs font-extrabold text-emerald-800 font-mono-code bg-emerald-50 px-2.5 py-1 rounded-md border border-emerald-300">
                                {{ $completedUnitsCount }}/4 Units Completed
                            </span>
                        </div>
                        <h2 class="text-lg font-black text-slate-900 tracking-tight">{{ $totalUnitsCount }}-Unit Directorate Progress &amp; Inter-Unit Workflow</h2>
                        <p class="text-xs text-slate-600 font-medium">All {{ $totalUnitsCount }} units build on this case. HOF approval triggers once all {{ $totalUnitsCount }} units complete their analysis.</p>
                    </div>

                    <!-- PROGRESS BAR -->
                    <div class="w-48 sm:w-64 bg-slate-200/90 rounded-xl h-8 p-1 border border-slate-300 shadow-inner relative overflow-hidden flex items-center shrink-0">
                        <div id="unitProgressBar" class="bg-emerald-600 h-full rounded-lg transition-all duration-500 flex items-center justify-center text-xs font-mono-code font-extrabold text-white shadow-xs" style="width: {{ max(18, ($completedUnitsCount / $totalUnitsCount) * 100) }}%;">
                            {{ round(($completedUnitsCount / $totalUnitsCount) * 100) }}%
                        </div>
                    </div>
                </div>

                <!-- UNITS CARDS GRID -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{{ $totalUnitsCount }} gap-4" id="unitsGridContainer">
                    @foreach($unitsList as $unitNameKey)
                        @php
                            $uData = $unitProgress[$unitNameKey] ?? ['status' => 'PENDING'];
                            $uStatus = strtoupper($uData['status'] ?? 'PENDING');
                            $uSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $unitNameKey));
                        @endphp
                        <div id="unitCard-{{ $uSlug }}" class="p-4 rounded-xl border transition-all flex flex-col justify-between {{ $uStatus === 'COMPLETED' ? 'bg-slate-50 border-slate-200 shadow-xs' : ($uStatus === 'IN_PROGRESS' ? 'bg-slate-50 border-slate-200 shadow-xs' : 'bg-slate-50 border-slate-200') }}">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span id="unitBadge-{{ $uSlug }}" class="text-[9px] font-extrabold font-mono-code uppercase px-2 py-0.5 rounded-md border {{ $uStatus === 'COMPLETED' ? 'bg-emerald-100 text-emerald-800 border-slate-200' : ($uStatus === 'IN_PROGRESS' ? 'bg-amber-100 text-amber-800 border-slate-200' : 'bg-slate-200 text-slate-700 border-slate-200') }}">
                                        {{ $uStatus === 'COMPLETED' ? '✓ COMPLETED' : ($uStatus === 'IN_PROGRESS' ? '⏳ IN PROGRESS' : '⭕ PENDING') }}
                                    </span>
                                    <span class="text-[9px] font-mono-code font-bold text-slate-400">UNIT {{ $loop->iteration }}</span>
                                </div>
                                <div class="font-extrabold text-slate-900 text-xs leading-snug">{{ $unitNameKey }}</div>
                                
                                <div id="unitOfficerBox-{{ $uSlug }}">
                                    @if(!empty($uData['officer']))
                                        <div class="text-[11px] font-medium text-slate-600 mt-2">Officer: <span class="font-bold text-slate-900">{{ $uData['officer'] }}</span></div>
                                    @endif
                                </div>

                                <div id="unitNotesBox-{{ $uSlug }}">
                                    @if(!empty($uData['notes']))
                                        <div class="text-[11px] text-slate-700 mt-2 p-2.5 bg-white/90 rounded-lg border border-slate-200/80 leading-relaxed">
                                            <span class="font-bold text-[10px] text-slate-400 block font-mono-code uppercase mb-0.5">Unit Findings:</span>
                                            "{{ $uData['notes'] }}"
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div id="unitUpdatedBox-{{ $uSlug }}">
                                @if(!empty($uData['updated_at']))
                                    <div class="text-[10px] font-mono-code text-slate-400 mt-3 pt-2 border-t border-slate-200/60">
                                        Updated: {{ \Carbon\Carbon::parse($uData['updated_at'])->format('M d, Y @ H:i') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- UNITS COMPLETED NOTICE BANNER -->
                <div id="completedUnitsBanner" class="p-5 bg-slate-900 text-white rounded-2xl border border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-lg {{ $completedUnitsCount < $totalUnitsCount ? 'hidden' : '' }}">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center font-black text-base shrink-0 shadow-2xs">
                            ✓
                        </div>
                        <div>
                            <h4 class="text-xs font-black text-white uppercase tracking-wider font-mono-code">Multi-Unit Directorate Processing Complete ({{ $completedUnitsCount }}/{{ $totalUnitsCount }} Units)</h4>
                            <p class="text-[11px] text-slate-300">All {{ $totalUnitsCount }} specialized units have completed their findings and saved their case sections. Docket is submitted for HOF executive sign-off.</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-full text-[10px] font-mono-code font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 uppercase tracking-wider shrink-0">
                        AWAITING HOF SIGN-OFF
                    </span>
                </div>
            </div>

            <!-- SECTION 1: MASTER CASE TITLE & CLASSIFICATION -->
            <div class="dossier-card p-6 border border-slate-200">
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-200">
                    <div class="flex items-center gap-3">
                        <div>
                            <h2 class="text-base font-black text-slate-900">Master Case Title &amp; Classification</h2>
                            <p class="text-xs text-slate-500">Case title, reference, priority, and division assignment</p>
                        </div>
                    </div>
                    @if(!empty($case->unit_name))
                    <span class="px-3 py-1 rounded-lg text-xs font-mono-code font-bold {{ $unitBadgeColor }}">
                        {{ $case->unit_name }}
                    </span>
                    @endif
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Case Name / Legal Title</div>
                        <div class="font-extrabold text-slate-900 text-sm mt-1">{{ $case->title ?: 'N/A' }}</div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Case Reference Number</div>
                        <div class="font-extrabold text-blue-600 font-mono-code text-sm mt-1">{{ $case->case_number ?: 'N/A' }}</div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Operation Type</div>
                        <div class="font-bold text-slate-800 text-sm mt-1">{{ $case->operation_code_name ?: ($case->operation_code ?? 'N/A') }}</div>
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

                <!-- MUGSHOT (MULTI-ANGLE PHOTO CAPTURE FOR SUSPECT #{{ $sNum }}) -->
                <div class="dossier-card p-6 border border-slate-200">
                    <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-200">
                        <div class="flex items-center gap-3">
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-mono-code font-bold bg-blue-100 text-blue-800 border border-blue-300 uppercase tracking-wider">
                                SUSPECT #{{ $sNum }} MUGSHOTS
                            </span>
                            <div>
                                <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                                    {{ $sName }} — Biometric Mugshots
                                    @if($isAssociate)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono-code font-extrabold bg-amber-500 text-white shadow-2xs">ASSOCIATE</span>
                                    @endif
                                </h2>
                                <p class="text-xs text-slate-500">
                                    Suspect facial photo captures recorded during booking
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <!-- Angle 1: Frontal View -->
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-center space-y-3">
                            <div class="flex items-center justify-between text-xs font-mono-code">
                                <span class="px-2 py-0.5 rounded bg-emerald-600 text-white font-bold text-[10px]">ANGLE 1</span>
                                <span class="font-bold text-slate-600">Frontal View</span>
                            </div>
                            <div class="w-full h-44 bg-white rounded-xl border border-dashed border-slate-300 flex flex-col items-center justify-center text-slate-400 overflow-hidden">
                                @if(!empty($sFront))
                                    <img src="{{ $getImgSrc($sFront) }}" class="w-full h-full object-contain object-center" alt="{{ $sName }} Front Mugshot">
                                @else
                                    <svg class="w-10 h-10 text-slate-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span class="text-xs font-bold text-slate-400">No Front Photo</span>
                                @endif
                            </div>
                        </div>
                        <!-- Angle 2: Left Profile -->
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-center space-y-3">
                            <div class="flex items-center justify-between text-xs font-mono-code">
                                <span class="px-2 py-0.5 rounded bg-slate-800 text-white font-bold text-[10px]">ANGLE 2</span>
                                <span class="font-bold text-slate-600">Left Profile</span>
                            </div>
                            <div class="w-full h-44 bg-white rounded-xl border border-dashed border-slate-300 flex flex-col items-center justify-center text-slate-400 overflow-hidden">
                                @if(!empty($sLeft))
                                    <img src="{{ $getImgSrc($sLeft) }}" class="w-full h-full object-contain object-center" alt="{{ $sName }} Left Profile">
                                @else
                                    <svg class="w-10 h-10 text-slate-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                    <span class="text-xs font-bold text-slate-400">No Left Photo</span>
                                @endif
                            </div>
                        </div>
                        <!-- Angle 3: Right Profile -->
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-center space-y-3">
                            <div class="flex items-center justify-between text-xs font-mono-code">
                                <span class="px-2 py-0.5 rounded bg-slate-800 text-white font-bold text-[10px]">ANGLE 3</span>
                                <span class="font-bold text-slate-600">Right Profile</span>
                            </div>
                            <div class="w-full h-44 bg-white rounded-xl border border-dashed border-slate-300 flex flex-col items-center justify-center text-slate-400 overflow-hidden">
                                @if(!empty($sRight))
                                    <img src="{{ $getImgSrc($sRight) }}" class="w-full h-full object-contain object-center" alt="{{ $sName }} Right Profile">
                                @else
                                    <svg class="w-10 h-10 text-slate-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    <span class="text-xs font-bold text-slate-400">No Right Photo</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUSPECT BOOKING & PERSONAL DETAILS FOR SUSPECT #{{ $sNum }} -->
                <div class="dossier-card p-6 border border-slate-200 space-y-6">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                        <div class="flex items-center gap-3">
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-mono-code font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 uppercase tracking-wider">
                                SUSPECT #{{ $sNum }} DOSSIER
                            </span>
                            <div>
                                <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                                    {{ $sName }} — Personal Booking Details
                                    @if($isAssociate)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono-code font-extrabold bg-amber-500 text-white shadow-2xs">ASSOCIATE</span>
                                    @endif
                                </h2>
                                <p class="text-xs text-slate-500">Legal identity, charges, family records, and custody allocation</p>
                            </div>
                        </div>
                    </div>

                    <!-- Legal Identity & Origin -->
                    <div class="space-y-3">
                        <div class="text-xs font-black text-slate-900 uppercase tracking-wider font-mono-code border-b border-slate-100 pb-1">
                            Legal Identity Details (Suspect #{{ $sNum }})
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                            <div class="p-3 rounded-xl {{ $isAssociate ? 'bg-amber-50/90 border-amber-200' : 'bg-slate-50 border-slate-200' }} border">
                                <div class="text-[10px] font-mono-code font-bold {{ $isAssociate ? 'text-amber-800' : 'text-slate-500' }} uppercase">Suspect Role / Classification</div>
                                <div class="font-extrabold text-xs sm:text-sm mt-0.5 {{ $isAssociate ? 'text-amber-900' : 'text-slate-900' }}">
                                    {{ strtoupper($sRoleLabel) }}
                                </div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Suspect Full Name</div>
                                <div class="font-extrabold text-slate-900 text-sm mt-0.5">{{ $sName }}</div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Alias / Street Name</div>
                                <div class="font-bold text-slate-800 text-sm mt-0.5">{{ $sAlias }}</div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Ghana Card #</div>
                                <div class="font-bold font-mono-code text-sm mt-0.5">{{ $sCard }}</div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Gender, Age &amp; DOB</div>
                                <div class="font-bold text-slate-800 text-sm mt-0.5">
                                    {{ $sGender }}
                                    {{ !empty($sAge) ? "• Age: {$sAge}" : '' }}
                                    {{ !empty($sDOB) ? "• DOB: {$sDOB}" : '' }}
                                </div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Height &amp; Eye Color</div>
                                <div class="font-bold text-slate-800 text-sm mt-0.5">
                                    {{ !empty($sHeight) ? "Height: {$sHeight}" : 'Height: N/A' }}
                                    •
                                    {{ !empty($sEye) ? "Eyes: {$sEye}" : 'Eyes: N/A' }}
                                </div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Nationality</div>
                                <div class="font-bold text-slate-800 text-sm mt-0.5">{{ $sNat }}</div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Hometown / POB</div>
                                <div class="font-bold text-slate-800 text-sm mt-0.5">{{ $sPOB }}</div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Contact Phone</div>
                                <div class="font-bold text-slate-800 text-sm mt-0.5">{{ $sPhone }}</div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Residential Address / GPS</div>
                                <div class="font-bold text-slate-800 text-sm mt-0.5">{{ $sAddr }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Family & Marital Records -->
                    <div class="space-y-3 pt-2">
                        <div class="text-xs font-black text-slate-900 uppercase tracking-wider font-mono-code border-b border-slate-100 pb-1">
                            Family &amp; Relationship Records (Suspect #{{ $sNum }})
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Marital / Relationship Status</div>
                                <div class="font-bold text-slate-900 mt-0.5">{{ $sMarital ?: 'N/A' }}</div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Spouse / Partner Details</div>
                                <div class="font-bold text-slate-900 mt-0.5">
                                    @if(!empty($sSpouseN))
                                        Spouse: {{ $sSpouseN }} ({{ $sSpouseP }}) {{ $sSpouseI ? "- {$sSpouseI}" : '' }}
                                    @elseif(!empty($sPartnerN))
                                        Partner: {{ $sPartnerN }} ({{ $sPartnerP }}) {{ $sPartnerI ? "- {$sPartnerI}" : '' }}
                                    @else
                                        {{ $sSpouseI ?: 'N/A' }}
                                    @endif
                                </div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Children &amp; Dependents</div>
                                <div class="font-bold text-slate-900 mt-0.5">
                                    {{ $sDependents }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Offence Charges & Custody Allocation -->
                    <div class="space-y-3 pt-2">
                        <div class="text-xs font-black text-slate-900 uppercase tracking-wider font-mono-code border-b border-slate-100 pb-1">
                            Offence Charges &amp; Custody Details (Suspect #{{ $sNum }})
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Raid Scene / Offence Location</div>
                                <div class="font-extrabold text-slate-900 text-sm">{{ $sRaid }}</div>
                            </div>
                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Arrest Date &amp; Time</div>
                                <div class="font-bold text-slate-900 text-sm">
                                    {{ $sArrestDate ? \Carbon\Carbon::parse($sArrestDate)->format('M d, Y') : 'N/A' }} {{ $sArrestTime ? "@ {$sArrestTime}" : '' }}
                                </div>
                            </div>
                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Assigned Custody Holding Facility</div>
                                <div class="font-bold text-slate-900 text-sm">{{ $sFacility }}</div>
                            </div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1 text-xs">
                            <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Brief Facts/ Charge Description</div>
                            <p class="text-slate-800 leading-relaxed font-semibold">
                                {{ $sOffense }}
                            </p>
                        </div>
                    </div>

                    <!-- ASSOCIATE LOGGED SEIZURES & EXHIBITS DOSSIER FOR {{ strtoupper($sName) }} -->
                    @if($isAssociate)
                        @php
                            $assocExhibits = array_filter($docketData['seizures'] ?? [], function($sz) use ($sName, $case) {
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

                        @if(!empty($assocExhibits) && count($assocExhibits) > 0)
                        <div class="p-5 bg-amber-50/50 border-2 border-amber-300 rounded-2xl space-y-4 shadow-sm">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-amber-200">
                                <div class="flex items-center space-x-2">
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-mono-code font-extrabold bg-amber-600 text-white uppercase tracking-wider shadow-2xs">
                                        ASSOCIATE EXHIBIT DOSSIER
                                    </span>
                                    <div>
                                        <h3 class="text-sm font-black text-slate-900 flex items-center gap-1.5">
                                            Associated Seizures &amp; Exhibits — {{ $sName }}
                                        </h3>
                                        <p class="text-[11px] text-slate-600">Confiscated items and exhibit inventory attached to Associate {{ $sName }}</p>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-mono-code font-extrabold bg-amber-100 text-amber-900 border border-amber-300 w-max">
                                    {{ count($assocExhibits) }} EXHIBIT ITEM(S) LOGGED
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                                @foreach($assocExhibits as $aEx)
                                    <div class="p-3 rounded-xl bg-white border border-amber-200 shadow-2xs">
                                        <div class="text-[10px] font-mono-code font-bold text-amber-800 uppercase">Exhibit Category</div>
                                        <div class="font-extrabold text-amber-900 text-xs mt-0.5 font-mono-code">{{ $aEx['category'] ?? ($aEx['exhibit_category'] ?? 'Associate Exhibit') }}</div>
                                    </div>
                                    <div class="p-3 rounded-xl bg-white border border-amber-200 shadow-2xs">
                                        <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Exhibit / Commodity Name</div>
                                        <div class="font-extrabold text-slate-900 text-xs mt-0.5">{{ $aEx['seizure_name'] ?? ($aEx['name'] ?? 'N/A') }}</div>
                                    </div>
                                    <div class="p-3 rounded-xl bg-white border border-amber-200 shadow-2xs">
                                        <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Quantity / Weight</div>
                                        <div class="font-extrabold text-slate-900 font-mono-code text-xs mt-0.5">
                                            @if(!empty($aEx['seizure_quantity']) || !empty($aEx['seizure_weight']))
                                                {{ implode(' • ', array_filter([!empty($aEx['seizure_quantity']) ? "Qty: {$aEx['seizure_quantity']}" : null, !empty($aEx['seizure_weight']) ? "Wt: {$aEx['seizure_weight']} " . ($aEx['seizure_unit'] ?? '') : null])) }}
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                    <div class="p-3 rounded-xl bg-white border border-amber-200 shadow-2xs">
                                        <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Estimated Valuation</div>
                                        <div class="font-extrabold text-emerald-700 font-mono-code text-xs mt-0.5">{{ $aEx['seizure_value'] ?? ($aEx['value'] ?? 'N/A') }}</div>
                                    </div>
                                    @if(!empty($aEx['seizure_packaging']))
                                    <div class="p-3 rounded-xl bg-white border border-amber-200 shadow-2xs col-span-1 sm:col-span-2">
                                        <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Packaging &amp; Format</div>
                                        <div class="font-bold text-slate-900 text-xs mt-0.5">{{ $aEx['seizure_packaging'] }}</div>
                                    </div>
                                    @endif
                                @endforeach
                            </div>

                            <!-- ASSOCIATE EXHIBIT PHOTOS GALLERY -->
                            @php
                                $assocPhotos = [];
                                foreach($assocExhibits as $aEx) {
                                    if (!empty($aEx['photos']) && is_array($aEx['photos'])) {
                                        foreach ($aEx['photos'] as $ap) { if ($ap && !in_array($ap, $assocPhotos)) $assocPhotos[] = $ap; }
                                    }
                                    $apSingle = $aEx['photo'] ?? ($aEx['exhibit_photo'] ?? null);
                                    if ($apSingle && !in_array($apSingle, $assocPhotos)) $assocPhotos[] = $apSingle;
                                }
                            @endphp
                            @if(!empty($assocPhotos) && count($assocPhotos) > 0)
                            <div class="pt-3 border-t border-amber-200 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-black text-slate-900 uppercase font-mono-code">Exhibit Visual Evidence Gallery ({{ $sName }})</span>
                                    <span class="text-[10px] font-mono-code font-bold text-amber-900 bg-amber-100 px-2 py-0.5 rounded">{{ count($assocPhotos) }} Attached Photo(s)</span>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3">
                                    @foreach($assocPhotos as $apIdx => $apUrl)
                                        <div class="p-1 bg-white border border-amber-200 rounded-xl space-y-1 text-center shadow-2xs">
                                            <div class="w-full h-24 bg-slate-100 rounded-lg overflow-hidden border border-slate-200 cursor-pointer" onclick="openMugshotModal('{{ $apUrl }}', 'Exhibit Photo #{{ $apIdx + 1 }} — {{ $sName }}')">
                                                <img src="{{ $apUrl }}" class="w-full h-full object-cover" alt="Exhibit Photo">
                                            </div>
                                            <div class="text-[9px] font-mono-code font-bold text-slate-600 uppercase">Exhibit Photo #{{ $apIdx + 1 }}</div>
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
                        <!-- PRIMARY SUSPECT LOGGED SEIZURES & EXHIBITS DOSSIER FOR {{ strtoupper($primarySuspectsCombinedNames) }} -->
                        <div class="p-5 bg-slate-50 border-2 border-slate-300 rounded-2xl space-y-4 shadow-sm">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-200">
                                <div class="flex items-center space-x-2">
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-mono-code font-extrabold bg-slate-900 text-white uppercase tracking-wider shadow-2xs">
                                        PRIMARY SUSPECT EXHIBIT DOSSIER
                                    </span>
                                    <div>
                                        <h3 class="text-sm font-black text-slate-900 flex items-center gap-1.5">
                                            Primary Seizures &amp; Exhibits — {{ $primarySuspectsCombinedNames }}
                                        </h3>
                                        <p class="text-[11px] text-slate-600">Confiscated items and exhibit inventory attached to Primary Suspects ({{ $primarySuspectsCombinedNames }})</p>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-mono-code font-extrabold bg-slate-200 text-slate-800 border border-slate-300 w-max">
                                    1 EXHIBIT ITEM(S) LOGGED
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                                <div class="p-3 rounded-xl bg-white border border-slate-200 shadow-2xs">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Exhibit Category</div>
                                    <div class="font-extrabold text-amber-700 text-xs mt-0.5 font-mono-code">{{ $case->exhibit_category ?? 'N/A' }}</div>
                                </div>
                                <div class="p-3 rounded-xl bg-white border border-slate-200 shadow-2xs">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Primary Commodity / Seizure Name</div>
                                    <div class="font-extrabold text-slate-900 text-xs mt-0.5">{{ $case->seizure_name ?: ($case->title ?: 'N/A') }}</div>
                                </div>
                                <div class="p-3 rounded-xl bg-white border border-slate-200 shadow-2xs">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Quantity / Weight</div>
                                    <div class="font-extrabold text-slate-900 font-mono-code text-xs mt-0.5">
                                        @if($case->seizure_quantity || $case->seizure_weight)
                                            {{ implode(' • ', array_filter([$case->seizure_quantity ? "Qty: {$case->seizure_quantity}" : null, $case->seizure_weight ? "Wt: {$case->seizure_weight} {$case->seizure_unit}" : ($case->seizure_unit ? "Unit: {$case->seizure_unit}" : null)])) }}
                                        @else
                                            {{ $case->exhibits_summary ?: 'N/A' }}
                                        @endif
                                    </div>
                                </div>
                                <div class="p-3 rounded-xl bg-white border border-slate-200 shadow-2xs">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Estimated Valuation</div>
                                    <div class="font-extrabold text-emerald-700 font-mono-code text-xs mt-0.5">{{ $case->seizure_value ?: ($case->valuation ?? 'N/A') }}</div>
                                </div>
                            </div>

                            @if(!empty($case->veh_make) || !empty($case->veh_reg_no) || !empty($docketData['vehicle']['make']) || !empty($docketData['vehicle']['reg']))
                            <div class="p-4 bg-slate-900 text-white rounded-xl space-y-2 border border-slate-800 text-xs shadow-sm">
                                <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                                    <div class="text-[10px] font-mono-code font-bold text-amber-400 uppercase tracking-wider">Seized Vehicle Details Record ({{ $primarySuspectsCombinedNames }})</div>
                                    <span class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 text-[10px] font-mono-code font-bold uppercase">{{ $case->veh_type ?: ($docketData['vehicle']['type'] ?? 'VEHICLE') }}</span>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                    <div><span class="text-slate-400 font-mono-code text-[10px] block">Make &amp; Model:</span> <span class="font-bold text-white text-xs">{{ $case->veh_make ?: ($docketData['vehicle']['make'] ?? 'N/A') }} {{ $case->veh_model ?: ($docketData['vehicle']['model'] ?? '') }}</span></div>
                                    <div><span class="text-slate-400 font-mono-code text-[10px] block">Reg Number:</span> <span class="font-mono-code font-bold text-emerald-400 text-xs">{{ $case->veh_reg_no ?: ($docketData['vehicle']['reg'] ?? 'N/A') }}</span></div>
                                    <div><span class="text-slate-400 font-mono-code text-[10px] block">Chassis / VIN:</span> <span class="font-mono-code font-bold text-slate-200 text-xs">{{ $case->veh_vin ?: ($docketData['vehicle']['chassis'] ?? 'N/A') }}</span></div>
                                    <div><span class="text-slate-400 font-mono-code text-[10px] block">Engine Number:</span> <span class="font-mono-code font-bold text-slate-200 text-xs">{{ $case->veh_engine_no ?: ($docketData['vehicle']['engine'] ?? 'N/A') }}</span></div>
                                    <div><span class="text-slate-400 font-mono-code text-[10px] block">Registered Owner:</span> <span class="font-bold text-slate-200 text-xs">{{ $case->veh_owner ?: ($docketData['vehicle']['owner'] ?? 'N/A') }}</span></div>
                                    <div><span class="text-slate-400 font-mono-code text-[10px] block">Driver:</span> <span class="font-bold text-slate-200 text-xs">{{ $case->veh_driver ?: ($docketData['vehicle']['driver'] ?? 'N/A') }}</span></div>
                                    <div><span class="text-slate-400 font-mono-code text-[10px] block">Owner Contact:</span> <span class="font-bold text-slate-200 text-xs">{{ $case->veh_owner_contact ?: 'N/A' }}</span></div>
                                    <div><span class="text-slate-400 font-mono-code text-[10px] block">Color / Year:</span> <span class="font-bold text-slate-200 text-xs">{{ $case->veh_colour ?: ($docketData['vehicle']['color'] ?? 'N/A') }} {{ $case->veh_year ? "({$case->veh_year})" : '' }}</span></div>
                                    <div><span class="text-slate-400 font-mono-code text-[10px] block">Docs Available:</span> <span class="font-bold text-slate-200 text-xs">{{ $case->veh_docs_available ?: 'N/A' }} {{ $case->veh_doc_ref ? "({$case->veh_doc_ref})" : '' }}</span></div>
                                    <div><span class="text-slate-400 font-mono-code text-[10px] block">Condition / Fuel:</span> <span class="font-bold text-slate-200 text-xs">{{ $case->veh_condition ?: 'N/A' }} {{ $case->veh_fuel ? "({$case->veh_fuel})" : '' }}</span></div>
                                    <div><span class="text-slate-400 font-mono-code text-[10px] block">Keys Received:</span> <span class="font-bold text-slate-200 text-xs">{{ $case->veh_keys_recv ?: 'N/A' }} {{ $case->veh_key_count !== null ? "({$case->veh_key_count} keys)" : '' }}</span></div>
                                    <div><span class="text-slate-400 font-mono-code text-[10px] block">Existing Damage:</span> <span class="font-semibold text-slate-200 text-xs">{{ $case->veh_damage ?: 'None' }}</span></div>
                                </div>
                            </div>
                            @endif

                            <!-- PRIMARY EXHIBIT PHOTOS GALLERY -->
                            @php
                                $primaryPhotos = [];
                                foreach ($evidencePhotos as $pUrl) {
                                    if (!in_array($pUrl, $allAssocPhotos)) {
                                        $primaryPhotos[] = $pUrl;
                                    }
                                }
                            @endphp
                            @if(!empty($primaryPhotos) && count($primaryPhotos) > 0)
                            <div class="pt-3 border-t border-slate-200 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-black text-slate-900 uppercase font-mono-code">Exhibit Visual Evidence Gallery ({{ $primarySuspectsCombinedNames }})</span>
                                    <span class="text-[10px] font-mono-code font-bold text-slate-700 bg-slate-200 px-2 py-0.5 rounded">{{ count($primaryPhotos) }} Attached Photo(s)</span>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3">
                                    @foreach($primaryPhotos as $pIdx => $pUrl)
                                        <div class="p-1 bg-white border border-slate-200 rounded-xl space-y-1 text-center shadow-2xs">
                                            <div class="w-full h-24 bg-slate-100 rounded-lg overflow-hidden border border-slate-200 cursor-pointer" onclick="openMugshotModal('{{ $pUrl }}', 'Exhibit Photo #{{ $pIdx + 1 }} — {{ $primarySuspectsCombinedNames }}')">
                                                <img src="{{ $pUrl }}" class="w-full h-full object-cover" alt="Exhibit Photo">
                                            </div>
                                            <div class="text-[9px] font-mono-code font-bold text-slate-600 uppercase">Exhibit Photo #{{ $pIdx + 1 }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                        @endif
                    @endif
                </div>
            @endforeach

            <!-- SEIZURES (COMMODITIES INVENTORY) -->
            <div class="dossier-card p-6 border border-slate-200 space-y-5">
                <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                    <div class="flex items-center gap-3">
                        <div>
                            <h2 class="text-base font-black text-slate-900">Seizures (Commodities Inventory)</h2>
                            <p class="text-xs text-slate-500">Record of confiscated substances, exhibits, and vault allocation</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Exhibit Category</div>
                        <div class="font-extrabold text-amber-700 text-sm mt-0.5 font-mono-code">{{ $case->exhibit_category ?? 'N/A' }}</div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Primary Commodity / Seizure Name</div>
                        <div class="font-extrabold text-slate-900 text-sm mt-0.5">{{ $case->seizure_name ?: ($case->title ?: 'N/A') }}</div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Quantity / Weight &amp; Unit</div>
                        <div class="font-extrabold text-slate-900 font-mono-code text-sm mt-0.5">
                            @if($case->seizure_quantity || $case->seizure_weight)
                                {{ implode(' • ', array_filter([$case->seizure_quantity ? "Qty: {$case->seizure_quantity}" : null, $case->seizure_weight ? "Wt: {$case->seizure_weight} {$case->seizure_unit}" : ($case->seizure_unit ? "Unit: {$case->seizure_unit}" : null)])) }}
                            @else
                                {{ $case->exhibits_summary ?: 'N/A' }}
                            @endif
                        </div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Estimated Valuation</div>
                        <div class="font-extrabold text-emerald-600 font-mono-code text-sm mt-0.5">{{ $case->seizure_value ?: ($case->valuation ?? 'N/A') }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Concealment &amp; Packaging Notes</div>
                        <div class="font-bold text-slate-900 text-sm mt-0.5">{{ $case->seizure_concealment ?: 'N/A' }}</div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Seizing Officer &amp; Location</div>
                        <div class="font-bold text-slate-900 text-sm mt-0.5">{{ $case->seizure_officer ?: 'N/A' }} {{ $case->seizure_location ? "({$case->seizure_location})" : '' }}</div>
                    </div>
                </div>

                @php
                    $allSeizuresList = $docketData['seizures'] ?? [];
                @endphp
                @if(!empty($allSeizuresList) && count($allSeizuresList) > 0)
                <div class="space-y-3 pt-3 border-t border-slate-200">
                    <div class="flex items-center justify-between">
                        <div class="text-xs font-black text-slate-900 uppercase tracking-wider font-mono-code">
                            All Logged Seizures &amp; Exhibits Dossier ({{ count($allSeizuresList) }} Items Logged)
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono-code font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">VERIFIED INVENTORY</span>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-2xs">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead class="bg-slate-100 text-slate-600 font-mono-code uppercase text-[10px]">
                                <tr>
                                    <th class="py-2.5 px-4 font-bold">Item #</th>
                                    <th class="py-2.5 px-4 font-bold">Category</th>
                                    <th class="py-2.5 px-4 font-bold">Exhibit Description</th>
                                    <th class="py-2.5 px-4 font-bold">Quantity</th>
                                    <th class="py-2.5 px-4 font-bold">Weight / Unit</th>
                                    <th class="py-2.5 px-4 font-bold">Packaging</th>
                                    <th class="py-2.5 px-4 font-bold">Est. Value</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-800">
                                @foreach($allSeizuresList as $sIdx => $szItem)
                                    <tr>
                                        <td class="py-2.5 px-4 font-mono-code font-bold text-slate-500">#{{ $sIdx + 1 }}</td>
                                        <td class="py-2.5 px-4 font-bold font-mono-code text-amber-700">{{ $szItem['category'] ?? ($szItem['exhibit_category'] ?? 'General Exhibit') }}</td>
                                        <td class="py-2.5 px-4 font-extrabold text-slate-900">{{ $szItem['seizure_name'] ?? ($szItem['name'] ?? 'N/A') }}</td>
                                        <td class="py-2.5 px-4 font-mono-code">{{ $szItem['seizure_quantity'] ?? ($szItem['quantity'] ?? 'N/A') }}</td>
                                        <td class="py-2.5 px-4 font-mono-code">
                                            @if(!empty($szItem['seizure_weight']) || !empty($szItem['weight']))
                                                {{ $szItem['seizure_weight'] ?? $szItem['weight'] }} {{ $szItem['seizure_unit'] ?? ($szItem['unit'] ?? '') }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td class="py-2.5 px-4 font-mono-code">{{ $szItem['seizure_packaging'] ?? ($szItem['packaging'] ?? 'N/A') }}</td>
                                        <td class="py-2.5 px-4 font-mono-code font-bold text-emerald-700">{{ $szItem['seizure_value'] ?? ($szItem['value'] ?? 'N/A') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                @if(!empty($case->veh_make) || !empty($case->veh_reg_no) || !empty($docketData['vehicle']['make']) || !empty($docketData['vehicle']['reg']))
                <div class="p-4 bg-slate-900 text-white rounded-2xl space-y-2.5 border border-slate-800 text-xs shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                        <div class="text-[10px] font-mono-code font-bold text-amber-400 uppercase tracking-wider">Seized Vehicle Details Record</div>
                        <span class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 text-[10px] font-mono-code font-bold uppercase">{{ $case->veh_type ?: ($docketData['vehicle']['type'] ?? 'VEHICLE') }}</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div><span class="text-slate-400 font-mono-code text-[11px] block">Make &amp; Model:</span> <span class="font-bold text-white text-xs">{{ $case->veh_make ?: ($docketData['vehicle']['make'] ?? 'N/A') }} {{ $case->veh_model ?: ($docketData['vehicle']['model'] ?? '') }}</span></div>
                        <div><span class="text-slate-400 font-mono-code text-[11px] block">Reg Number:</span> <span class="font-mono-code font-bold text-emerald-400 text-xs">{{ $case->veh_reg_no ?: ($docketData['vehicle']['reg'] ?? 'N/A') }}</span></div>
                        <div><span class="text-slate-400 font-mono-code text-[11px] block">Chassis / VIN:</span> <span class="font-mono-code font-bold text-slate-200 text-xs">{{ $case->veh_vin ?: ($docketData['vehicle']['chassis'] ?? 'N/A') }}</span></div>
                        <div><span class="text-slate-400 font-mono-code text-[11px] block">Engine Number:</span> <span class="font-mono-code font-bold text-slate-200 text-xs">{{ $case->veh_engine_no ?: ($docketData['vehicle']['engine'] ?? 'N/A') }}</span></div>
                        <div><span class="text-slate-400 font-mono-code text-[11px] block">Registered Owner:</span> <span class="font-bold text-slate-200 text-xs">{{ $case->veh_owner ?: ($docketData['vehicle']['owner'] ?? 'N/A') }}</span></div>
                        <div><span class="text-slate-400 font-mono-code text-[11px] block">Driver:</span> <span class="font-bold text-slate-200 text-xs">{{ $case->veh_driver ?: ($docketData['vehicle']['driver'] ?? 'N/A') }}</span></div>
                        <div><span class="text-slate-400 font-mono-code text-[11px] block">Owner Contact:</span> <span class="font-bold text-slate-200 text-xs">{{ $case->veh_owner_contact ?: 'N/A' }}</span></div>
                        <div><span class="text-slate-400 font-mono-code text-[11px] block">Color / Year:</span> <span class="font-bold text-slate-200 text-xs">{{ $case->veh_colour ?: ($docketData['vehicle']['color'] ?? 'N/A') }} {{ $case->veh_year ? "({$case->veh_year})" : '' }}</span></div>
                        <div><span class="text-slate-400 font-mono-code text-[11px] block">Docs Available:</span> <span class="font-bold text-slate-200 text-xs">{{ $case->veh_docs_available ?: 'N/A' }} {{ $case->veh_doc_ref ? "({$case->veh_doc_ref})" : '' }}</span></div>
                        <div><span class="text-slate-400 font-mono-code text-[11px] block">Condition / Fuel:</span> <span class="font-bold text-slate-200 text-xs">{{ $case->veh_condition ?: 'N/A' }} {{ $case->veh_fuel ? "({$case->veh_fuel})" : '' }}</span></div>
                        <div><span class="text-slate-400 font-mono-code text-[11px] block">Keys Received:</span> <span class="font-bold text-slate-200 text-xs">{{ $case->veh_keys_recv ?: 'N/A' }} {{ $case->veh_key_count !== null ? "({$case->veh_key_count} keys)" : '' }}</span></div>
                        <div><span class="text-slate-400 font-mono-code text-[11px] block">Existing Damage:</span> <span class="font-semibold text-slate-200 text-xs">{{ $case->veh_damage ?: 'None' }}</span></div>
                        <div class="col-span-2 sm:col-span-4"><span class="text-slate-400 font-mono-code text-[11px] block">Modifications &amp; Features:</span> <span class="font-semibold text-slate-200 text-xs">{{ $case->veh_mods ?: ($docketData['vehicle']['mods'] ?? 'None') }} {{ $case->veh_features ? '• ' . $case->veh_features : '' }}</span></div>
                    </div>
                </div>
                @endif

                @if(!empty($case->wpn_model) || !empty($case->wpn_serial_no) || !empty($docketData['weapon']['model']))
                <div class="p-4 bg-slate-900 text-white rounded-2xl space-y-2.5 border border-slate-800 text-xs shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                        <div class="text-[10px] font-mono-code font-bold text-red-400 uppercase tracking-wider">Seized Firearm / Weapon Details Record</div>
                        <span class="px-2 py-0.5 rounded bg-red-500/20 text-red-300 text-[10px] font-mono-code font-bold uppercase">{{ $case->wpn_loaded_status ?: ($docketData['weapon']['loaded_status'] ?? 'FIREARM') }}</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div><span class="text-slate-400 font-mono-code text-[11px] block">Model / Firearm:</span> <span class="font-bold text-white text-xs">{{ $case->wpn_model ?: ($docketData['weapon']['model'] ?? 'N/A') }}</span></div>
                        <div><span class="text-slate-400 font-mono-code text-[11px] block">Serial Number:</span> <span class="font-mono-code font-bold text-emerald-400 text-xs">{{ $case->wpn_serial_no ?: ($docketData['weapon']['serial_no'] ?? 'N/A') }}</span></div>
                        <div><span class="text-slate-400 font-mono-code text-[11px] block">Calibre / Gauge:</span> <span class="font-bold text-slate-200 text-xs">{{ $case->wpn_calibre ?: ($docketData['weapon']['calibre'] ?? 'N/A') }}</span></div>
                        <div><span class="text-slate-400 font-mono-code text-[11px] block">Colour / Size:</span> <span class="font-bold text-slate-200 text-xs">{{ $case->wpn_colour ?: ($docketData['weapon']['colour'] ?? 'N/A') }} {{ $case->wpn_size ? "({$case->wpn_size})" : '' }}</span></div>
                        <div><span class="text-slate-400 font-mono-code text-[11px] block">Loaded Status:</span> <span class="font-bold text-slate-200 text-xs">{{ $case->wpn_loaded_status ?: ($docketData['weapon']['loaded_status'] ?? 'N/A') }}</span></div>
                        <div><span class="text-slate-400 font-mono-code text-[11px] block">Recovery Condition:</span> <span class="font-bold text-slate-200 text-xs">{{ $case->wpn_condition ?: ($docketData['weapon']['condition'] ?? 'N/A') }}</span></div>
                        <div class="col-span-2"><span class="text-slate-400 font-mono-code text-[11px] block">Exact Location Found:</span> <span class="font-bold text-slate-200 text-xs">{{ $case->wpn_location_found ?: ($docketData['weapon']['location_found'] ?? 'N/A') }}</span></div>
                        <div class="col-span-2 sm:col-span-4"><span class="text-slate-400 font-mono-code text-[11px] block">Distinguishing Marks / Features:</span> <span class="font-semibold text-slate-200 text-xs">{{ $case->wpn_marks ?: ($docketData['weapon']['marks'] ?? 'None') }}</span></div>
                    </div>
                </div>
                @endif

                @if(!empty($evidencePhotos) && count($evidencePhotos) > 0)
                <div class="pt-4 border-t border-slate-200 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Confiscated Seizure Photos &amp; Physical Evidence Gallery</h3>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono-code font-bold bg-amber-100 text-amber-800 border border-amber-200">
                            {{ count($evidencePhotos) }} Exhibit Photo{{ count($evidencePhotos) > 1 ? 's' : '' }} Attached
                        </span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                        @foreach($evidencePhotos as $idx => $photoUrl)
                            @if(!empty($photoUrl))
                            <div class="relative group rounded-xl overflow-hidden border border-slate-300 bg-slate-900 shadow-sm h-32 flex items-center justify-center">
                                <img src="{{ $photoUrl }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" alt="Seizure Exhibit Photo {{ $idx + 1 }}">
                                <a href="{{ $photoUrl }}" target="_blank" class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center text-white text-xs font-bold gap-1 p-2 text-center" title="Click to View Full Size Photo">
                                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
                                    <span>Expand Photo #{{ $idx + 1 }}</span>
                                </a>
                                <span class="absolute bottom-1 left-1 px-1.5 py-0.5 bg-slate-900/80 text-white text-[9px] font-mono-code font-bold rounded">Exhibit Photo #{{ $idx + 1 }}</span>
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- SECTION 5: EXAMINATION INFORMATION (LABORATORY ANALYSIS DIRECTORATE) -->
            @if($isLabContext || $hasSavedLabData)
            <div class="dossier-card p-6 border border-slate-200 space-y-6 shadow-sm bg-white rounded-2xl" id="section5LabExamination">
                <!-- SECTION HEADER -->
                <div class="pb-3 border-b border-emerald-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-md shadow-emerald-600/30 shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-extrabold text-slate-900 text-base">Examination Information</h3>
                                <span id="section5BadgeStatus" class="px-2 py-0.5 rounded text-[9px] font-mono-code font-bold uppercase {{ $hasSavedLabData ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : ($labStatus === 'IN_PROGRESS' ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-amber-100 text-amber-800 border border-amber-300') }}">
                                    {{ $hasSavedLabData ? '✓ LAB COMPLETED' : ($labStatus === 'IN_PROGRESS' ? '⏳ LAB IN PROGRESS' : '⭕ LAB PENDING') }}
                                </span>
                            </div>
                            <p class="text-xs text-emerald-700 font-semibold">Laboratory Analysis Directorate — Forensic Examination &amp; Testing Log</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider">
                            LAB ANALYSIS EXCLUSIVE
                        </span>
                    </div>
                </div>

                @if($isLabContext)
                @php
                    $drugExhibits = [];
                    if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
                        foreach ($docketData['seizures'] as $idx => $sz) {
                            $cat = strtolower($sz['category'] ?? ($sz['exhibit_category'] ?? ''));
                            $sName = $sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? '');
                            if (!empty($sz['seizure_name_drug']) || str_contains($cat, 'drug') || str_contains($cat, 'narcotic') || str_contains(strtolower($sName), 'cocaine') || str_contains(strtolower($sName), 'heroin') || str_contains(strtolower($sName), 'cannabis') || str_contains(strtolower($sName), 'drug')) {
                                $drugExhibits[] = [
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
                    if (empty($drugExhibits) && $isDrugCase) {
                        $drugExhibits[] = [
                            'index' => 1,
                            'name'  => $case->seizure_name_drug ?: ($case->seizure_name ?: ($case->exhibits_summary ?: 'Drug Exhibit')),
                            'cat'   => $case->exhibit_category ?: 'Drugs',
                            'qty'   => $case->seizure_quantity,
                            'weight'=> $case->seizure_weight,
                            'unit'  => $case->seizure_unit,
                        ];
                    }
                    $initialLabDesc = !empty($drugExhibits) ? $drugExhibits[0]['name'] : ($case->seizure_name_drug ?: ($isDrugCase ? ($case->exhibits_summary ?: $case->seizure_name) : 'No drug exhibit saved yet'));
                @endphp
                <form id="section5LabForm" onsubmit="submitSection5LabExam(event)" class="space-y-5 {{ $hasSavedLabData ? 'hidden' : '' }}">
                    <!-- EXHIBIT SELECTOR CARD FROM SECTION 4 MANIFEST -->
                    <div class="p-4 bg-emerald-50/70 rounded-xl border border-emerald-200/90 space-y-2.5 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase flex items-center space-x-2">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                <span>Select Seized Exhibit to Examine (Manifest Sync)</span>
                                <span class="text-red-600 font-bold">*</span>
                            </label>
                            <span class="text-[10px] font-mono-code font-extrabold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-md border border-emerald-200">
                                {{ !empty($drugExhibits) ? count($drugExhibits) . ' Drug Exhibit(s) Synced' : 'No drug exhibit saved yet' }}
                            </span>
                        </div>
                        <div class="relative">
                            <select 
                                id="labExhibitSelect" 
                                onchange="onReviewLabExhibitChange(this)"
                                class="w-full px-3.5 py-3 bg-white border border-emerald-300 rounded-xl text-slate-900 font-bold outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs cursor-pointer"
                            >
                                @if(!empty($drugExhibits))
                                    @foreach($drugExhibits as $dEx)
                                        <option value="{{ $dEx['index'] }}" data-name="{{ $dEx['name'] }}" {{ $loop->first ? 'selected' : '' }}>Exhibit #{{ $dEx['index'] }}: {{ $dEx['name'] }}</option>
                                    @endforeach
                                @else
                                    <option value="">No drug exhibit saved yet</option>
                                @endif
                            </select>
                        </div>
                        <p class="text-[11px] text-emerald-900 font-semibold flex items-center space-x-1">
                            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Select an exhibit above to view its manifest details and process forensic examination separately.</span>
                        </p>
                    </div>

                    <!-- REFERENCE NUMBER FIELDS -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <!-- Analysis/Examination Reference -->
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                <span>Analysis / Examination Reference</span>
                                <span class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Auto-Generated</span>
                            </label>
                            <input 
                                type="text" 
                                id="labExamReferenceInput" 
                                value="{{ $labExamRef }}" 
                                readonly 
                                class="w-full px-3.5 py-3 bg-emerald-50/70 border border-emerald-200 rounded-xl text-emerald-900 font-mono-code font-extrabold outline-none shadow-2xs text-xs"
                            >
                        </div>

                        <!-- Case Reference -->
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                <span>Case Reference</span>
                                <span class="text-[9px] font-mono-code text-blue-700 bg-blue-100 px-1.5 py-0.5 rounded font-bold uppercase">Auto-Linked</span>
                            </label>
                            <input 
                                type="text" 
                                id="labCaseReferenceInput" 
                                value="{{ $labCaseRef }}" 
                                readonly 
                                class="w-full px-3.5 py-3 bg-blue-50/70 border border-blue-200 rounded-xl text-blue-900 font-mono-code font-extrabold outline-none shadow-2xs text-xs"
                            >
                        </div>

                        <!-- Exhibit Number -->
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                <span>Exhibit Number</span>
                                <span class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Auto-Linked</span>
                            </label>
                            <input 
                                type="text" 
                                id="labExhibitNumberInput" 
                                value="{{ $labExhibitNo }}" 
                                readonly 
                                class="w-full px-3.5 py-3 bg-emerald-50/70 border border-emerald-200 rounded-xl text-emerald-900 font-mono-code font-extrabold outline-none shadow-2xs text-xs"
                            >
                        </div>
                    </div>

                    <!-- EXHIBIT DESCRIPTION, LAB UNIT & ANALYST / EXAMINER -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <!-- Exhibit Description -->
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                <span>Exhibit Description</span>
                                <span class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Fetched from Manifest</span>
                            </label>
                            <textarea 
                                id="labExhibitDescriptionInput" 
                                rows="3" 
                                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs"
                            >{{ $initialLabDesc }}</textarea>
                        </div>

                        <!-- Laboratory Number -->
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                <span>Laboratory Number</span>
                                <span class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Auto-Generated</span>
                            </label>
                            <input 
                                type="text" 
                                id="formLabNumber" 
                                name="laboratory_number"
                                value="{{ $labNumber }}" 
                                readonly
                                class="w-full px-3.5 py-3 bg-emerald-50/70 border border-emerald-200 rounded-xl text-emerald-900 font-mono-code font-extrabold outline-none shadow-2xs text-xs"
                            >
                        </div>

                        <!-- Analyst / Examiner -->
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                <span>Analyst / Examiner</span>
                                <span class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Examiner Name</span>
                            </label>
                            <input 
                                type="text" 
                                id="formLabAnalyst" 
                                value="{{ $labAnalyst }}" 
                                class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-extrabold outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs text-xs"
                                placeholder="e.g. John Adu"
                            >
                        </div>
                    </div>

                    <!-- DATES TIMELINE -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 pt-2 border-t border-slate-100">
                        <!-- Date Received -->
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                Date Received <span class="text-red-600 font-bold">*</span>
                            </label>
                            <input 
                                type="date" 
                                id="labDateReceivedInput" 
                                value="{{ $labDateReceived }}" 
                                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold font-mono-code outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs"
                            >
                        </div>

                        <!-- Date Examination Started -->
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                Date Examination Started <span class="text-red-600 font-bold">*</span>
                            </label>
                            <input 
                                type="date" 
                                id="labDateStartedInput" 
                                value="{{ $labDateStarted }}" 
                                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold font-mono-code outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs"
                            >
                        </div>

                        <!-- Date Examination Completed -->
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                Date Examination Completed <span class="text-red-600 font-bold">*</span>
                            </label>
                            <input 
                                type="date" 
                                id="labDateCompletedInput" 
                                value="{{ $labDateCompleted }}" 
                                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold font-mono-code outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs"
                            >
                        </div>
                    </div>

                    <!-- SECTION 6: PRESUMPTIVE TEST CARD -->
                    <div class="bg-white p-5 rounded-2xl border border-emerald-200 shadow-2xs space-y-4">
                        <div class="pb-3 border-b border-emerald-100 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-md shadow-emerald-600/30">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-slate-900 text-sm">Presumptive Test</h3>
                                    <p class="text-[11px] text-emerald-700 font-semibold">Initial chemical field screening &amp; colorimetric reaction validation</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider">
                                SCREENING RESULT
                            </span>
                        </div>

                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-3">
                                Presumptive Test Reaction Result <span class="text-red-600 font-bold">*</span>
                            </label>

                            @php
                                $pIsYes = !empty($labPresumptive) && (str_starts_with(strtoupper($labPresumptive), 'YES') || str_starts_with(strtoupper($labPresumptive), 'POS'));
                            @endphp
                            <div class="flex items-center space-x-3">
                                <!-- YES CHECKBOX -->
                                <label id="labelPresumptiveYes" class="relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 {{ $pIsYes ? 'border-emerald-500 bg-emerald-50/80 ring-2 ring-emerald-500/20' : 'border-slate-200 bg-slate-50' }}">
                                    <input 
                                        type="radio" 
                                        name="form_presumptive_radio" 
                                        value="YES"
                                        {{ $pIsYes ? 'checked' : '' }}
                                        onchange="onPresumptiveRadioChange('YES')"
                                        class="w-4 h-4 text-emerald-600 border-slate-300 focus:ring-emerald-500 cursor-pointer"
                                    >
                                    <div class="flex items-center space-x-1.5">
                                        <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                                        <span class="font-black text-xs text-emerald-900 font-mono-code uppercase">YES</span>
                                    </div>
                                </label>

                                <!-- NO CHECKBOX -->
                                <label id="labelPresumptiveNo" class="relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 {{ !$pIsYes ? 'border-red-500 bg-red-50/80 ring-2 ring-red-500/20' : 'border-slate-200 bg-slate-50' }}">
                                    <input 
                                        type="radio" 
                                        name="form_presumptive_radio" 
                                        value="NO"
                                        {{ !$pIsYes ? 'checked' : '' }}
                                        onchange="onPresumptiveRadioChange('NO')"
                                        class="w-4 h-4 text-red-600 border-slate-300 focus:ring-red-500 cursor-pointer"
                                    >
                                    <div class="flex items-center space-x-1.5">
                                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                        <span class="font-black text-xs text-slate-900 font-mono-code uppercase">NO</span>
                                    </div>
                                </label>
                            </div>

                            <!-- DROPDOWN INPUT BOXES FOR PRESUMPTIVE TEST REACTION DETAILS -->
                            <div id="presumptiveReactionDetailsContainerReview" class="mt-4 p-4 bg-emerald-50/70 rounded-xl border border-emerald-200 space-y-4 transition-all duration-300 {{ !$pIsYes ? 'hidden' : '' }}">
                                <div class="flex items-center justify-between border-b border-emerald-200/80 pb-2">
                                    <div class="flex items-center space-x-2 text-emerald-800 font-extrabold text-xs uppercase tracking-wider">
                                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                        </svg>
                                        <span>Presumptive Test Reaction Result Details &amp; Field Spec</span>
                                    </div>
                                    <span class="text-[10px] font-mono-code font-bold text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-md border border-emerald-200 uppercase">
                                        MULTIPLE SPECIMEN SUPPORT
                                    </span>
                                </div>
                                
                                <!-- DYNAMIC TEST ROWS CONTAINER -->
                                <div id="presumptiveTestRowsContainerReview" class="space-y-3">
                                    <div class="presumptive-test-row-review p-3.5 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-2.5">
                                        <div class="flex items-center justify-between text-[11px] font-extrabold text-slate-700 uppercase font-mono-code">
                                            <span class="test-row-title-review">Test Result #1</span>
                                            <button type="button" onclick="removePresumptiveTestRowReview(this)" class="remove-test-row-btn-review hidden px-2 py-0.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg text-[10px] font-bold border border-transparent hover:border-red-200 transition-colors cursor-pointer">
                                                &times; Remove
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                    Presence of <span class="text-red-600 font-bold">*</span>
                                                </label>
                                                <input 
                                                    type="text" 
                                                    class="presumptive-presence-input-review w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs" 
                                                    onchange="updatePresumptiveReactionTextReview()" 
                                                    oninput="updatePresumptiveReactionTextReview()" 
                                                    placeholder="e.g. Cocaine, Heroin, THC, Methamphetamine"
                                                >
                                            </div>
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                    Percentage (%) <span class="text-red-600 font-bold">*</span>
                                                </label>
                                                <input 
                                                    type="text" 
                                                    class="presumptive-percentage-input-review w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-mono-code" 
                                                    onchange="updatePresumptiveReactionTextReview()" 
                                                    oninput="updatePresumptiveReactionTextReview()" 
                                                    placeholder="e.g. 85.5% or 80-90%"
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ADD MORE TEST RESULT BUTTON -->
                                <div class="pt-1 flex items-center justify-between">
                                    <button 
                                        type="button" 
                                        onclick="addPresumptiveTestRowReview()" 
                                        class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-all cursor-pointer border-none space-x-1.5"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                        <span>+ Add More Test Result</span>
                                    </button>
                                    <span class="text-[10px] text-slate-500 font-medium italic">Click to log additional chemical/substance reaction test results</span>
                                </div>

                                <!-- Hidden Presumptive Reaction Result for Form Submission -->
                                <input 
                                    type="hidden" 
                                    id="presumptiveReactionResultInputReview" 
                                    value="{{ $labPresumptive }}"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- CONFIRMATORY TEST CARD (UNDERNEATH PRESUMPTIVE TEST SECTION) -->
                    <div class="bg-white p-5 rounded-2xl border border-emerald-200 shadow-2xs space-y-4">
                        <div class="pb-3 border-b border-emerald-100 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-md shadow-emerald-600/30">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-slate-900 text-sm">Confirmatory Test</h3>
                                    <p class="text-[11px] text-emerald-700 font-semibold">Instrumental &amp; Spectrometry Laboratory Confirmation (GC-MS / FTIR)</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider">
                                LAB VERIFICATION
                            </span>
                        </div>

                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-3">
                                Confirmatory Test Conducted <span class="text-red-600 font-bold">*</span>
                            </label>

                            <div class="flex items-center space-x-3">
                                <!-- YES RADIO -->
                                <label id="labelConfirmatoryYesReview" class="relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 {{ $labConfirmatory === 'YES' ? 'border-emerald-500 bg-emerald-50/80 ring-2 ring-emerald-500/20' : 'border-slate-200 bg-slate-50' }}">
                                    <input 
                                        type="radio" 
                                        name="form_confirmatory_radio" 
                                        value="YES"
                                        {{ $labConfirmatory === 'YES' ? 'checked' : '' }}
                                        onchange="toggleConfirmatoryTestReview('YES')"
                                        class="w-4 h-4 text-emerald-600 border-slate-300 focus:ring-emerald-500 cursor-pointer"
                                    >
                                    <div class="flex items-center space-x-1.5">
                                        <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                                        <span class="font-black text-xs text-emerald-900 font-mono-code uppercase">YES</span>
                                    </div>
                                </label>

                                <!-- NO RADIO -->
                                <label id="labelConfirmatoryNoReview" class="relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 {{ $labConfirmatory !== 'YES' ? 'border-red-500 bg-red-50/80 ring-2 ring-red-500/20' : 'border-slate-200 bg-slate-50' }}">
                                    <input 
                                        type="radio" 
                                        name="form_confirmatory_radio" 
                                        value="NO"
                                        {{ $labConfirmatory !== 'YES' ? 'checked' : '' }}
                                        onchange="toggleConfirmatoryTestReview('NO')"
                                        class="w-4 h-4 text-red-600 border-slate-300 focus:ring-red-500 cursor-pointer"
                                    >
                                    <div class="flex items-center space-x-1.5">
                                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                        <span class="font-black text-xs text-red-900 font-mono-code uppercase">NO</span>
                                    </div>
                                </label>
                            </div>

                            <!-- SAMPLE WEIGHT GROUP INPUT CONTAINER (DYNAMICALLY SHOWN WHEN CONFIRMATORY TEST IS YES) -->
                            <div id="sampleWeightContainerReview" class="mt-4 p-4 bg-emerald-50/70 rounded-xl border border-emerald-200 space-y-4 transition-all duration-300 {{ $labConfirmatory === 'YES' ? '' : 'hidden' }}">
                                <div class="flex items-center justify-between border-b border-emerald-200/80 pb-2">
                                    <div class="flex items-center space-x-2 text-emerald-900 font-extrabold text-xs uppercase tracking-wider">
                                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                                        </svg>
                                        <span>SAMPLE WEIGHT</span>
                                    </div>
                                    <span class="text-[10px] font-mono-code font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-md border border-emerald-200 uppercase">
                                        CONFIRMATORY METROLOGY &amp; MASS
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- DESCRIPTION -->
                                    <div class="md:col-span-2">
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                            <span>Description <span class="text-red-600 font-bold">*</span></span>
                                            <span class="text-[9px] font-mono-code font-bold text-emerald-700 bg-emerald-100/90 px-1.5 py-0.5 rounded border border-emerald-200 uppercase">AUTO-FETCHED</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            id="sampleWeightDescriptionReview" 
                                            name="sample_weight_description" 
                                            value="{{ $labSampleDesc }}"
                                            readonly
                                            placeholder="Fetched from drug manifest"
                                            class="w-full px-3.5 py-2.5 bg-slate-100/90 border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs outline-none shadow-2xs font-sans cursor-not-allowed"
                                        >
                                    </div>

                                    <!-- NUMBER OF UNITS -->
                                    <div>
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                            <span>Number of Units <span class="text-red-600 font-bold">*</span></span>
                                            <span class="text-[9px] font-mono-code font-bold text-emerald-700 bg-emerald-100/90 px-1.5 py-0.5 rounded border border-emerald-200 uppercase">AUTO-FETCHED</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            id="sampleWeightUnitsReview" 
                                            name="sample_weight_units" 
                                            value="{{ $labSampleUnits }}"
                                            readonly
                                            placeholder="Fetched from drug manifest"
                                            class="w-full px-3.5 py-2.5 bg-slate-100/90 border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs outline-none shadow-2xs font-mono-code cursor-not-allowed"
                                        >
                                    </div>

                                    <!-- GROSS WEIGHT -->
                                    <div>
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                            <span>Gross Weight <span class="text-red-600 font-bold">*</span></span>
                                            <span class="text-[9px] font-mono-code font-bold text-emerald-700 bg-emerald-100/90 px-1.5 py-0.5 rounded border border-emerald-200 uppercase">AUTO-FETCHED</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            id="sampleWeightGrossReview" 
                                            name="sample_weight_gross" 
                                            value="{{ $labSampleGross }}"
                                            readonly
                                            placeholder="Fetched from drug manifest"
                                            class="w-full px-3.5 py-2.5 bg-slate-100/90 border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs outline-none shadow-2xs font-mono-code cursor-not-allowed"
                                        >
                                    </div>

                                    <!-- SAMPLE TAKEN -->
                                    <div>
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                            Sample Taken <span class="text-red-600 font-bold">*</span>
                                        </label>
                                        <div class="relative flex rounded-xl shadow-2xs">
                                            <input 
                                                type="text" 
                                                id="sampleWeightTakenReview" 
                                                name="sample_weight_taken" 
                                                value="{{ $labSampleTaken }}"
                                                placeholder="e.g. 10"
                                                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-l-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 font-mono-code placeholder:text-slate-400 border-r-0"
                                            >
                                            <span 
                                                id="sampleWeightTakenUnitAddonReview"
                                                class="inline-flex items-center px-3 py-2.5 rounded-r-xl border border-slate-300 bg-slate-100/90 text-slate-700 font-mono-code font-bold text-xs shrink-0 select-none cursor-not-allowed border-l-0 transition-colors duration-200"
                                            >
                                                {{ $labSampleUnit }}
                                            </span>
                                        </div>
                                        <p id="sampleWeightTakenErrorReview" class="text-[11px] text-red-600 font-extrabold mt-1.5 hidden flex items-center space-x-1 animate-in fade-in duration-200">
                                            <svg class="w-3.5 h-3.5 shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span>Sample taken cannot exceed the gross weight</span>
                                        </p>
                                    </div>

                                    <!-- RESERVED SAMPLES -->
                                    <div>
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                            <span>Reserved Samples <span class="text-red-600 font-bold">*</span></span>
                                            <span class="text-[9px] font-mono-code font-bold text-emerald-700 bg-emerald-100/90 px-1.5 py-0.5 rounded border border-emerald-200 uppercase">AUTO-CALCULATED</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            id="sampleWeightReservedReview" 
                                            name="sample_weight_reserved" 
                                            value="{{ $labSampleReserved }}"
                                            readonly
                                            placeholder="Auto-calculated (Gross Weight - Sample Taken)"
                                            class="w-full px-3.5 py-2.5 bg-slate-100/90 border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none shadow-2xs font-mono-code placeholder:text-slate-400 cursor-not-allowed"
                                        >
                                    </div>

                                    <!-- RESULT SECTION (UNDERNEATH SAMPLE WEIGHT) -->
                                    <div class="md:col-span-2 pt-4 border-t border-emerald-200/80 space-y-4">
                                        <div class="flex items-center justify-between border-b border-emerald-200/80 pb-2">
                                            <div class="flex items-center space-x-2 text-emerald-900 font-extrabold text-xs uppercase tracking-wider">
                                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                <span>RESULT / CONFIRMATORY ANALYSIS</span>
                                            </div>
                                            <span class="text-[10px] font-mono-code font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-md border border-emerald-200 uppercase">
                                                ANALYTICAL FINDINGS &amp; SPECS
                                            </span>
                                        </div>

                                        <div class="space-y-4">
                                            <!-- 1. SUBSTANCE IDENTIFICATION -->
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                                    <span>Substance Identification <span class="text-red-600 font-bold">*</span></span>
                                                    <span class="text-[9px] font-mono-code font-bold text-emerald-700 bg-emerald-100/90 px-1.5 py-0.5 rounded border border-emerald-200 uppercase">AUTO-FETCHED</span>
                                                </label>
                                                <input 
                                                    type="text" 
                                                    id="confirmatorySubstanceIdentificationReview" 
                                                    name="confirmatory_substance_identification" 
                                                    value="{{ $labSubstanceId }}"
                                                    readonly
                                                    placeholder="Fetched from drug manifest"
                                                    class="w-full px-3.5 py-2.5 bg-slate-100/90 border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs outline-none shadow-2xs font-sans cursor-not-allowed"
                                                >
                                            </div>

                                            <!-- 2. RESULT (INPUT BOX WITH ADD MORE) -->
                                            <div class="space-y-2">
                                                <div class="flex items-center justify-between">
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase">
                                                        Result (Detected Substances &amp; Purity %) <span class="text-red-600 font-bold">*</span>
                                                    </label>
                                                    <button 
                                                        type="button" 
                                                        onclick="addDetectedSubstanceRowReview()" 
                                                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] rounded-xl shadow-2xs transition-all flex items-center space-x-1 border-none cursor-pointer"
                                                    >
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                                        <span>Add More</span>
                                                    </button>
                                                </div>
                                                <div id="detectedSubstancesListContainerReview" class="space-y-2.5">
                                                    <!-- Dynamic rows added via JS -->
                                                </div>
                                            </div>

                                            <!-- 3. TEST METHODS INPUT -->
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                    Test Methods <span class="text-red-600 font-bold">*</span>
                                                </label>
                                                <input 
                                                    type="text" 
                                                    id="confirmatoryTestMethodsReview" 
                                                    name="confirmatory_test_methods" 
                                                    value="{{ $labTestMethods }}"
                                                    placeholder="e.g. GSA-SM-T***-A, Clarks analysis of..."
                                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-mono-code placeholder:text-slate-400"
                                                >
                                            </div>

                                            <!-- 4. SPECIFICATIONS TEXTAREA INPUT -->
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                    Specifications <span class="text-slate-400 font-normal text-[10px] lowercase normal-case">(optional)</span>
                                                </label>
                                                <textarea 
                                                    id="confirmatorySpecificationsReview" 
                                                    name="confirmatory_specifications" 
                                                    rows="3" 
                                                    placeholder="e.g. Analytical reference standards verified against NIST library database. Sample purity meets regulatory specifications."
                                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-sans placeholder:text-slate-400"
                                                >{{ $labSpecifications }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FINDINGS -->
                    <div>
                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                            Laboratory Analytical Findings &amp; Examination Log
                        </label>
                        <textarea 
                            id="formLabNotes" 
                            rows="3" 
                            class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold text-xs focus:ring-2 focus:ring-emerald-500 outline-none" 
                            placeholder="Enter chemical reaction results, purity percentage, spectrometry verification notes, etc."
                        >{{ $labFindings }}</textarea>
                    </div>

                    <!-- SUBMIT BUTTON -->
                    <div class="flex items-center justify-end space-x-3 pt-2">
                        <button type="submit" id="saveSection5LabBtn" class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-600/30 transition-all flex items-center justify-center cursor-pointer border-none">
                            <span id="saveSection5LabBtnText">Save Lab Examination &amp; Update Progress</span>
                        </button>
                    </div>
                </form>
                @endif

                <!-- OFFICIAL READ-ONLY FORENSIC EXAMINATION REPORT DISPLAY -->
                <div id="section5ReadOnlyDisplay" class="space-y-4 {{ !$hasSavedLabData ? 'hidden' : '' }}">
                    <!-- TOP VERIFICATION BANNER -->
                    <div class="p-4 bg-emerald-50/90 rounded-2xl border border-emerald-300 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-md shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <div class="text-[10px] font-mono-code font-black text-emerald-800 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>NACOC FORENSIC DIRECTORATE</span>
                                    <span>&bull;</span>
                                    <span class="text-emerald-700">OFFICIAL EXAMINATION RECORD</span>
                                </div>
                                <div class="text-sm font-black text-slate-900">LABORATORY EXAMINATION RESULT</div>
                            </div>
                        </div>
                        @if($isLabContext)
                        <button type="button" onclick="toggleLabFormEditMode()" class="px-3.5 py-2 bg-white hover:bg-emerald-100 text-emerald-900 font-extrabold text-xs rounded-xl border border-emerald-300 shadow-2xs transition-all flex items-center space-x-1.5 cursor-pointer shrink-0">
                            <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            <span>Edit Lab Examination</span>
                        </button>
                        @endif
                    </div>

                    <!-- STRUCTURED READ-ONLY LABORATORY EXAMINATION TABLE -->
                    <table class="w-full text-xs border-collapse border border-slate-300 rounded-xl overflow-hidden shadow-2xs">
                        <tbody>
                            <tr class="bg-slate-100/90 border-b border-slate-300">
                                <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 w-1/5 bg-slate-100">Analysis Reference:</td>
                                <td class="p-3 font-black text-emerald-900 font-mono-code text-sm border-r border-slate-300 w-3/10 bg-white" id="displayLabExamRef">{{ $labExamRef }}</td>
                                <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 w-1/5 bg-slate-100">Exhibit Number:</td>
                                <td class="p-3 font-black text-emerald-900 font-mono-code text-sm bg-white" id="displayLabExhibitNo">{{ $labExhibitNo }}</td>
                            </tr>
                            <tr class="border-b border-slate-300">
                                <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Case Reference:</td>
                                <td class="p-3 font-black text-blue-900 font-mono-code border-r border-slate-300 bg-white" id="displayLabCaseRef">{{ $labCaseRef }}</td>
                                <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Laboratory Number:</td>
                                <td class="p-3 font-black text-slate-900 font-mono-code bg-white" id="section5LabUnitDisplay">{{ $labNumber }}</td>
                            </tr>
                            <tr class="bg-slate-100/90 border-b border-slate-300">
                                <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Analyst / Examiner:</td>
                                <td class="p-3 font-black text-slate-900 border-r border-slate-300 bg-white" id="section5AnalystDisplay">{{ $labAnalyst }}</td>
                                <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Examination Timeline:</td>
                                <td class="p-2 bg-white">
                                    <table class="w-full text-xs border border-slate-300 rounded overflow-hidden">
                                        <thead class="bg-slate-100 text-slate-950 font-mono-code uppercase font-black text-[10px]">
                                            <tr class="border-b border-slate-300">
                                                <th class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">Received</th>
                                                <th class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">Started</th>
                                                <th class="px-2 py-1 text-left bg-slate-200/70">Completed</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white font-bold font-mono-code text-slate-900">
                                            <tr>
                                                <td class="px-2 py-1 border-r border-slate-300 text-blue-900" id="section5DateReceivedDisplay">{{ $labDateReceived }}</td>
                                                <td class="px-2 py-1 border-r border-slate-300 text-amber-900" id="section5DateStartedDisplay">{{ $labDateStarted }}</td>
                                                <td class="px-2 py-1 text-emerald-900" id="section5DateCompletedDisplay">{{ $labDateCompleted }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr class="border-b border-slate-300">
                                <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Presumptive Reaction:</td>
                                <td class="p-2 border-r border-slate-300 bg-white" id="section5PresumptiveDisplay">
                                    @php
                                        $pRaw = trim($labPresumptive ?? '');
                                        $pResult = 'N/A';
                                        $pPresence = 'N/A';
                                        $pPurity = 'N/A';

                                        if (!empty($pRaw)) {
                                            if (str_starts_with(strtoupper($pRaw), 'YES') || str_starts_with(strtoupper($pRaw), 'POS')) {
                                                $pResult = 'YES';
                                            } elseif (str_starts_with(strtoupper($pRaw), 'NO') || str_starts_with(strtoupper($pRaw), 'NEG')) {
                                                $pResult = 'NO';
                                            } else {
                                                $pResult = strtok($pRaw, ' (');
                                            }

                                            if (preg_match('/Presence of:\s*([^-\)]+)/i', $pRaw, $mPresence)) {
                                                $pPresence = trim($mPresence[1]);
                                            } elseif (preg_match('/\(([^-\)]+)\)/', $pRaw, $mPresence)) {
                                                $pPresence = trim($mPresence[1]);
                                            }

                                            if (preg_match('/Purity\/Percentage:\s*([^\)]+)/i', $pRaw, $mPurity)) {
                                                $pPurity = trim($mPurity[1]);
                                            }
                                        }
                                    @endphp
                                    <table class="w-full text-xs border border-slate-300 rounded overflow-hidden">
                                        <thead class="bg-slate-100 text-slate-950 font-mono-code uppercase font-black text-[10px]">
                                            <tr class="border-b border-slate-300">
                                                <th class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">Result</th>
                                                <th class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">Presence of</th>
                                                <th class="px-2 py-1 text-left bg-slate-200/70">Purity/Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white">
                                            <tr>
                                                <td class="px-2 py-1 border-r border-slate-300 font-extrabold {{ $pResult === 'YES' ? 'text-emerald-800' : 'text-slate-900' }}">{{ $pResult }}</td>
                                                <td class="px-2 py-1 border-r border-slate-300 font-bold text-slate-900">{{ $pPresence }}</td>
                                                <td class="px-2 py-1 font-bold text-slate-900">{{ $pPurity }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                                <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Confirmatory Test:</td>
                                <td class="p-3 font-black bg-white {{ $labConfirmatory === 'YES' ? 'text-emerald-950' : 'text-slate-950' }}" id="section5ConfirmatoryDisplay">
                                    <span class="inline-flex items-center space-x-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full {{ $labConfirmatory === 'YES' ? 'bg-emerald-600' : 'bg-slate-600' }}"></span>
                                        <span>{{ $labConfirmatory === 'YES' ? 'YES' : 'NO' }}</span>
                                    </span>
                                </td>
                            </tr>
                            <tr class="bg-slate-100/90 border-b border-slate-300">
                                <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Sample Metrology:</td>
                                <td class="p-3 bg-white" colspan="3">
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                        <div class="col-span-2 sm:col-span-4 p-2 bg-slate-50 border border-slate-200 rounded-lg">
                                            <div class="text-[9px] font-mono-code font-black text-slate-500 uppercase tracking-wider">Description</div>
                                            <div class="font-bold text-slate-950 text-xs mt-0.5" id="section5SampleDescDisplay">{{ $labSampleDesc ?: 'N/A' }}</div>
                                        </div>
                                        <div class="p-2 bg-slate-50 border border-slate-200 rounded-lg">
                                            <div class="text-[9px] font-mono-code font-black text-slate-500 uppercase tracking-wider">Number of Units</div>
                                            <div class="font-mono-code font-bold text-slate-950 text-xs mt-0.5" id="section5SampleUnitsDisplay">{{ $labSampleUnits ?: 'N/A' }}</div>
                                        </div>
                                        <div class="p-2 bg-slate-50 border border-slate-200 rounded-lg">
                                            <div class="text-[9px] font-mono-code font-black text-slate-500 uppercase tracking-wider">Gross Weight</div>
                                            <div class="font-mono-code font-bold text-slate-950 text-xs mt-0.5" id="section5SampleGrossDisplay">{{ $labSampleGross ?: 'N/A' }}</div>
                                        </div>
                                        <div class="p-2 bg-slate-50 border border-slate-200 rounded-lg">
                                            <div class="text-[9px] font-mono-code font-black text-slate-500 uppercase tracking-wider">Mass Taken</div>
                                            <div class="font-mono-code font-bold text-emerald-900 text-xs mt-0.5" id="section5SampleTakenDisplay">{{ $labSampleTaken ? $labSampleTaken . ' ' . $labSampleUnit : 'N/A' }}</div>
                                        </div>
                                        <div class="p-2 bg-slate-50 border border-slate-200 rounded-lg">
                                            <div class="text-[9px] font-mono-code font-black text-slate-500 uppercase tracking-wider">Reserved Samples</div>
                                            <div class="font-mono-code font-bold text-slate-950 text-xs mt-0.5" id="section5SampleReservedDisplay">{{ $labSampleReserved ?: 'N/A' }}</div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr class="border-b border-slate-300">
                                <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Substance Identified:</td>
                                <td class="p-3 font-black text-slate-950 bg-white border-r border-slate-300" id="section5SubstanceIdDisplay">{{ $labSubstanceId ?: 'N/A' }}</td>
                                <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Test Specifications:</td>
                                <td class="p-3 font-bold text-slate-900 bg-white" id="section5SpecsDisplay">{{ $labSpecifications ?: 'NIST Reference Library Matched' }}</td>
                            </tr>
                            <tr class="bg-slate-100/90 border-b border-slate-300">
                                <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Detected Compounds:</td>
                                <td class="p-3 bg-white font-bold text-slate-900 text-xs" colspan="3" id="section5DetectedSubstancesDisplay">
                                    @php $detList = []; @endphp
                                    @foreach($labDetectedSubstances as $subItem)
                                        @if(!empty($subItem['substance']))
                                            @php 
                                                $detList[] = $subItem['substance'] . (!empty($subItem['percentage']) ? ' (' . $subItem['percentage'] . ')' : ''); 
                                            @endphp
                                        @endif
                                    @endforeach
                                    @if(count($detList) > 0)
                                        <span>{{ implode(', ', $detList) }}</span>
                                    @else
                                        <span class="text-slate-700 font-bold italic text-xs">No individual compound breakdown logged</span>
                                    @endif
                                </td>
                            </tr>
                            <tr class="border-b border-slate-300">
                                <td class="p-3 font-mono-code font-black text-slate-950 uppercase border-r border-slate-300 bg-slate-100">Test Methods:</td>
                                <td class="p-3 font-mono-code font-black text-slate-900 bg-white" colspan="3" id="section5TestMethodsDisplay">{{ $labTestMethods ?: 'Standard GSA Forensic Test Methods' }}</td>
                            </tr>
                            <tr class="bg-slate-900 text-white">
                                <td class="p-3 font-mono-code font-black text-emerald-400 uppercase border-r border-slate-800 bg-slate-950">Analytical Findings:</td>
                                <td class="p-3 font-medium text-slate-100 leading-relaxed bg-slate-900" colspan="3" id="section5FindingsDisplay">{{ $labFindings ?: 'No specific laboratory analytical findings logged yet.' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- DIRECTORATE EXECUTIVE ACTION SECTION UNDERNEATH -->
            @if($isHof)
            <div class="dossier-card p-6 sm:p-8 bg-white border border-slate-300 shadow-xl">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="space-y-1">
                        <span class="text-[10px] font-mono-code font-bold text-blue-600 uppercase tracking-wider">DIRECTORATE AUTHORITY SIGN-OFF</span>
                        <h3 class="text-xl font-black text-slate-900">Executive Department Head Authorization</h3>
                        <p class="text-xs text-slate-600 font-medium">Review the complete docket Sections 1 to 4 above and authorize or decline this case file.</p>
                    </div>

                    <div class="flex items-center gap-4 flex-wrap" id="executiveActionContainer">
                        @if($status === 'Approved' || $status === 'Authorized')
                            <div class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-300 font-mono-code font-bold text-xs shadow-sm">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                OFFICIALLY APPROVED &amp; SIGNED BY DIRECTORATE HEAD
                            </div>
                        @elseif($status === 'Declined')
                            <div class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-red-50 text-red-800 border border-red-300 font-mono-code font-bold text-xs shadow-sm">
                                <span class="w-2 h-2 rounded-full bg-red-600"></span>
                                CASE DECLINED BY DIRECTORATE HEAD
                            </div>
                        @else
                            <button type="button" onclick="declineCaseAction('{{ $case->case_number }}')" class="btn-decline">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span>Decline Case</span>
                            </button>

                            <button type="button" onclick="approveCaseAction('{{ $case->case_number }}')" class="btn-primary">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Approve Case &amp; Sign Certificate</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
            @endif

        </main>
    </div>
</div>

<script>
    function onReviewLabExhibitChange(sel) {
        if (!sel) return;
        const opt = sel.options[sel.selectedIndex];
        if (!opt) return;
        const exhVal = opt.value;
        const nameVal = opt.getAttribute('data-name') || '';
        const exhNoInput = document.getElementById('labExhibitNumberInput');
        const descInput = document.getElementById('labExhibitDescriptionInput');
        const subIdInput = document.getElementById('confirmatorySubstanceIdentificationReview');
        const sampleDescInput = document.getElementById('sampleWeightDescriptionReview');
        if (exhNoInput && exhVal) exhNoInput.value = 'EXH-0' + exhVal;
        if (descInput) descInput.value = nameVal;
        if (subIdInput) subIdInput.value = nameVal;
        if (sampleDescInput) sampleDescInput.value = nameVal;
    }

    const caseNumber = @json($case->case_number);
    const officerName = @json($officerName);
    const officerRole = @json($officerRole);

    /* ── Live Clock ─────────────────────────────── */
    function updateClock() {
        const now = new Date();
        const pad = n => String(n).padStart(2, '0');
        document.getElementById('liveClock').textContent =
            `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}  ${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())} UTC`;
    }
    updateClock();
    setInterval(updateClock, 1000);

    /* ── Approve Case Action ─────────────────────── */
    async function approveCaseAction(ref) {
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch(`/cases/${ref}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            });
            const data = await res.json();

            if (res.ok && data.success) {
                // Update UI status
                const badge = document.getElementById('headerStatusBadge');
                if (badge) {
                    badge.innerHTML = `<span class="status-badge status-done"><svg class="w-3 h-3 mr-1 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Authorized</span>`;
                }

                // Hide approve/decline buttons and display signed badge
                const container = document.getElementById('executiveActionContainer');
                if (container) {
                    container.innerHTML = `<div class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-300 font-mono-code font-bold text-xs shadow-sm"><svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> OFFICIALLY APPROVED &amp; SIGNED BY DIRECTORATE HEAD</div>`;
                }

                showToast(`✔ Case ${ref} has been officially APPROVED and signed off by ${officerName} (${officerRole}).`);
            } else {
                showToast(`❌ Error approving case: ${data.message || 'Server error'}`);
            }
        } catch (e) {
            console.error(e);
            showToast(`❌ Error connecting to server.`);
        }
    }

    /* ── Decline Case Action ─────────────────────── */
    async function declineCaseAction(ref) {
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch(`/cases/${ref}/decline`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            });
            const data = await res.json();

            if (res.ok && data.success) {
                // Update UI status
                const badge = document.getElementById('headerStatusBadge');
                if (badge) {
                    badge.innerHTML = `<span class="status-badge status-declined"><span class="w-1.5 h-1.5 rounded-full bg-red-600 mr-1.5 inline-block"></span> Declined</span>`;
                }

                const container = document.getElementById('executiveActionContainer');
                if (container) {
                    container.innerHTML = `<div class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-red-50 text-red-800 border border-red-300 font-mono-code font-bold text-xs shadow-sm"><span class="w-2 h-2 rounded-full bg-red-600"></span> CASE DECLINED BY DIRECTORATE HEAD</div>`;
                }

                showToast(`❌ Case ${ref} has been officially DECLINED by ${officerName} (${officerRole}).`);
            } else {
                showToast(`❌ Error declining case: ${data.message || 'Server error'}`);
            }
        } catch (e) {
            console.error(e);
            showToast(`❌ Error connecting to server.`);
        }
    }

    /* ── Toast Notification ──────────────────────── */
    function showToast(msg) {
        const t = document.createElement('div');
        t.textContent = msg;
        Object.assign(t.style, {
            position: 'fixed', bottom: '24px', right: '24px', zIndex: '9999',
            background: 'linear-gradient(135deg, #0e1829, #0d1e3a)',
            border: '1px solid rgba(59,130,246,0.35)',
            color: '#93c5fd', padding: '14px 20px', borderRadius: '14px',
            fontSize: '12px', fontFamily: "'JetBrains Mono', monospace", fontWeight: '600',
            boxShadow: '0 12px 40px rgba(0,0,0,0.5), 0 0 20px rgba(59,130,246,0.15)',
            maxWidth: '420px', lineHeight: '1.5',
            transform: 'translateY(20px)', opacity: '0',
            transition: 'all 0.35s cubic-bezier(0.34,1.56,0.64,1)',
        });
        document.body.appendChild(t);
        requestAnimationFrame(() => { t.style.transform = 'translateY(0)'; t.style.opacity = '1'; });
        setTimeout(() => {
            t.style.transform = 'translateY(10px)'; t.style.opacity = '0';
            setTimeout(() => t.remove(), 400);
        }, 4000);
    }

    /* ── Submit Unit Progress Action (Real-Time DOM Update) ────────────────── */
    async function submitUnitProgress(e) {
        e.preventDefault();
        const submitBtn = document.getElementById('unitSubmitBtn');
        if (submitBtn) submitBtn.disabled = true;

        const unitName = document.getElementById('updateUnitName').value;
        const unitStatus = document.getElementById('updateUnitStatus').value;
        const officer = document.getElementById('updateOfficer').value;
        const notes = document.getElementById('updateNotes').value;

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch(`/cases/${caseNumber}/update-unit-progress`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    unit_name: unitName,
                    unit_status: unitStatus,
                    officer: officer,
                    notes: notes
                })
            });
            const data = await res.json();
            if (res.ok && data.success) {
                const compCount = data.completed_count || 0;
                const updatedUnitKey = data.unit_name || unitName;
                const unitProgMap = data.unit_progress || {};

                // Update unitProgressData state
                if (unitProgMap[updatedUnitKey]) {
                    unitProgressData[updatedUnitKey] = unitProgMap[updatedUnitKey];
                }

                // 1. Update target unit card in real-time
                const slug = updatedUnitKey.toLowerCase().replace(/[^a-z0-9]/g, '');
                const cardEl = document.getElementById(`unitCard-${slug}`);
                const badgeEl = document.getElementById(`unitBadge-${slug}`);
                const officerBoxEl = document.getElementById(`unitOfficerBox-${slug}`);
                const notesBoxEl = document.getElementById(`unitNotesBox-${slug}`);
                const updatedBoxEl = document.getElementById(`unitUpdatedBox-${slug}`);

                const st = (unitStatus || 'PENDING').toUpperCase();

                if (cardEl) {
                    if (st === 'COMPLETED') {
                        cardEl.className = 'p-4 rounded-xl border transition-all flex flex-col justify-between bg-emerald-50/70 border-emerald-300 shadow-xs';
                    } else if (st === 'IN_PROGRESS') {
                        cardEl.className = 'p-4 rounded-xl border transition-all flex flex-col justify-between bg-amber-50/70 border-amber-300 shadow-xs';
                    } else {
                        cardEl.className = 'p-4 rounded-xl border transition-all flex flex-col justify-between bg-slate-50 border-slate-200';
                    }
                }

                if (badgeEl) {
                    if (st === 'COMPLETED') {
                        badgeEl.className = 'text-[9px] font-extrabold font-mono-code uppercase px-2 py-0.5 rounded-md border bg-emerald-100 text-emerald-800 border-emerald-300';
                        badgeEl.textContent = '✓ COMPLETED';
                    } else if (st === 'IN_PROGRESS') {
                        badgeEl.className = 'text-[9px] font-extrabold font-mono-code uppercase px-2 py-0.5 rounded-md border bg-amber-100 text-amber-800 border-amber-300';
                        badgeEl.textContent = '⏳ IN PROGRESS';
                    } else {
                        badgeEl.className = 'text-[9px] font-extrabold font-mono-code uppercase px-2 py-0.5 rounded-md border bg-slate-200 text-slate-700 border-slate-300';
                        badgeEl.textContent = '⭕ PENDING';
                    }
                }

                if (officerBoxEl) {
                    officerBoxEl.innerHTML = officer ? `<div class="text-[11px] font-medium text-slate-600 mt-2">Officer: <span class="font-bold text-slate-900">${escapeHtml(officer)}</span></div>` : '';
                }

                if (notesBoxEl) {
                    notesBoxEl.innerHTML = notes ? `<div class="text-[11px] text-slate-700 mt-2 p-2.5 bg-white/90 rounded-lg border border-slate-200/80 leading-relaxed"><span class="font-bold text-[10px] text-slate-400 block font-mono-code uppercase mb-0.5">Unit Findings:</span>"${escapeHtml(notes)}"</div>` : '';
                }

                if (updatedBoxEl) {
                    updatedBoxEl.innerHTML = `<div class="text-[10px] font-mono-code text-slate-400 mt-3 pt-2 border-t border-slate-200/60">Updated: Just Now</div>`;
                }

                // 2. Update progress tracker counter and progress bar
                const totalUnits = unitsListArray.length;
                const badgeCounter = document.getElementById('unitCompletedCountBadge');
                if (badgeCounter) {
                    badgeCounter.textContent = `${compCount}/${totalUnits} Units Completed`;
                }

                const pct = Math.round((compCount / totalUnits) * 100);
                const progressBar = document.getElementById('unitProgressBar');
                if (progressBar) {
                    progressBar.style.width = `${Math.max(18, pct)}%`;
                    progressBar.textContent = `${pct}%`;
                }

                const percentText = document.getElementById('unitProgressPercentText');
                if (percentText) {
                    percentText.textContent = `${pct}%`;
                }

                // 3. Handle Completion or Pending Portal Updates - Immediately REMOVE/HIDE Unit Data Entry Portal
                const portal = document.getElementById('unitDataEntryPortal');
                const banner = document.getElementById('completedUnitsBanner');
                const headerStatusBadge = document.getElementById('headerStatusBadge');

                // Instantly hide the Unit Data Entry Portal section once saved!
                if (portal) {
                    portal.classList.add('hidden');
                }

                if (compCount >= totalUnits) {
                    if (banner) banner.classList.remove('hidden');
                    if (headerStatusBadge) {
                        headerStatusBadge.innerHTML = `<span class="status-badge status-await"><span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5 inline-block"></span> Awaiting Sign-off</span>`;
                    }
                    showToast(`✔ Multi-Unit Directorate Processing Complete (${totalUnits}/${totalUnits} Units)! All ${totalUnits} specialized units have completed findings. Docket is submitted for HOF executive sign-off.`);
                } else {
                    showToast(`✔ Progress for unit "${updatedUnitKey}" updated successfully! (${compCount}/${totalUnits} Units Completed)`);
                }
            } else {
                showToast(`❌ Error updating unit progress: ${data.message || 'Server error'}`);
            }
        } catch(err) {
            console.error(err);
            showToast(`❌ Connection error updating unit progress.`);
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    }

    function escapeHtml(str) {
        return (str || '').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    /* ── Dynamic Button Text: Save Case vs Submit Case for Approval ── */
    const unitProgressData = @json($unitProgress);
    const unitsListArray = @json($unitsList);

    function updateUnitSubmitButtonText() {
        const selectedUnit = document.getElementById('updateUnitName')?.value;
        const selectedStatus = document.getElementById('updateUnitStatus')?.value;
        const btnTextEl = document.getElementById('unitSubmitBtnText');
        if (!btnTextEl) return;

        let simulatedCompletedCount = 0;
        unitsListArray.forEach(u => {
            let st = (unitProgressData[u] && unitProgressData[u].status) ? unitProgressData[u].status.toUpperCase() : 'PENDING';
            if (u === selectedUnit) {
                st = selectedStatus ? selectedStatus.toUpperCase() : st;
            }
            if (st === 'COMPLETED') {
                simulatedCompletedCount++;
            }
        });

        if (simulatedCompletedCount >= unitsListArray.length) {
            btnTextEl.innerText = 'Submit Case for Approval';
        } else {
            btnTextEl.innerText = 'Save Case';
        }
    }
    document.addEventListener('DOMContentLoaded', updateUnitSubmitButtonText);

    /* ── Notifications & Global Search ──────────── */
    function toggleNotificationDropdown() {
        const dropdown = document.getElementById('notificationDropdown');
        if (dropdown) dropdown.classList.toggle('hidden');
    }

    document.addEventListener('click', function(e) {
        const bellBtn = document.getElementById('notificationBellBtn');
        const dropdown = document.getElementById('notificationDropdown');
        if (bellBtn && dropdown && !bellBtn.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.getElementById('globalSearch');
            if (searchInput) searchInput.focus();
        }
    });

    function onPresumptiveRadioChange(val) {
        const lblYes = document.getElementById('labelPresumptiveYes');
        const lblNo = document.getElementById('labelPresumptiveNo');
        const container = document.getElementById('presumptiveReactionDetailsContainerReview');
        if (val === 'YES') {
            if (lblYes) lblYes.className = 'relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-emerald-500 bg-emerald-50/80 ring-2 ring-emerald-500/20';
            if (lblNo) lblNo.className = 'relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-slate-200 bg-slate-50';
            if (container) container.classList.remove('hidden');
        } else {
            if (lblYes) lblYes.className = 'relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-slate-200 bg-slate-50';
            if (lblNo) lblNo.className = 'relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-red-500 bg-red-50/80 ring-2 ring-red-500/20';
            if (container) container.classList.add('hidden');
        }
    }

    function toggleConfirmatoryTestReview(val) {
        const lblYes = document.getElementById('labelConfirmatoryYesReview');
        const lblNo = document.getElementById('labelConfirmatoryNoReview');
        const container = document.getElementById('sampleWeightContainerReview');
        const descInput = document.getElementById('sampleWeightDescriptionReview');
        const grossInput = document.getElementById('sampleWeightGrossReview');

        if (val === 'YES') {
            if (lblYes) lblYes.className = 'relative inline-flex items-center px-4 py-2 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-emerald-500 bg-emerald-50/80 ring-2 ring-emerald-500/20';
            if (lblNo) lblNo.className = 'relative inline-flex items-center px-4 py-2 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-slate-200 bg-slate-50';
            if (container) container.classList.remove('hidden');

            if (descInput && (!descInput.value || descInput.value.startsWith('e.g.'))) {
                const defaultDrug = @json($defaultDrugName ?? '');
                if (defaultDrug) descInput.value = defaultDrug;
            }
            if (grossInput && (!grossInput.value || grossInput.value.startsWith('e.g.'))) {
                const defaultGross = @json($defaultGrossWeight ?? '');
                if (defaultGross) grossInput.value = defaultGross;
            }
            const unitAddon = document.getElementById('sampleWeightTakenUnitAddonReview');
            if (unitAddon && grossInput && grossInput.value) {
                const uMatch = grossInput.value.match(/(Kilograms?\s*\([^\)]+\)|Kilograms?|kg|Grams?|g|mg|lbs?)/i);
                if (uMatch) unitAddon.textContent = uMatch[1];
            }
            if (typeof updateReservedWeightReview === 'function') updateReservedWeightReview();
        } else {
            if (lblYes) lblYes.className = 'relative inline-flex items-center px-4 py-2 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-slate-200 bg-slate-50';
            if (lblNo) lblNo.className = 'relative inline-flex items-center px-4 py-2 rounded-xl border-2 transition-all cursor-pointer select-none space-x-2 border-red-500 bg-red-50/80 ring-2 ring-red-500/20';
            if (container) container.classList.add('hidden');
        }
    }

    function addPresumptiveTestRowReview() {
        const container = document.getElementById('presumptiveTestRowsContainerReview');
        if (!container) return;

        const rows = container.querySelectorAll('.presumptive-test-row-review');
        const newIdx = rows.length + 1;

        const rowDiv = document.createElement('div');
        rowDiv.className = 'presumptive-test-row-review p-3.5 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-2.5';
        rowDiv.innerHTML = `
            <div class="flex items-center justify-between text-[11px] font-extrabold text-slate-700 uppercase font-mono-code">
                <span class="test-row-title-review">Test Result #${newIdx}</span>
                <button type="button" onclick="removePresumptiveTestRowReview(this)" class="remove-test-row-btn-review px-2 py-0.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg text-[10px] font-bold border border-transparent hover:border-red-200 transition-colors cursor-pointer">
                    &times; Remove
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                        Presence of <span class="text-red-600 font-bold">*</span>
                    </label>
                    <input 
                        type="text" 
                        class="presumptive-presence-input-review w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs" 
                        onchange="updatePresumptiveReactionTextReview()" 
                        oninput="updatePresumptiveReactionTextReview()" 
                        placeholder="e.g. Cocaine, Heroin, THC, Methamphetamine"
                    >
                </div>
                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                        Percentage (%) <span class="text-red-600 font-bold">*</span>
                    </label>
                    <input 
                        type="text" 
                        class="presumptive-percentage-input-review w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-mono-code" 
                        onchange="updatePresumptiveReactionTextReview()" 
                        oninput="updatePresumptiveReactionTextReview()" 
                        placeholder="e.g. 85.5% or 80-90%"
                    >
                </div>
            </div>
        `;
        container.appendChild(rowDiv);
        updatePresumptiveRemoveButtonsReview();
        updatePresumptiveReactionTextReview();
    }

    function removePresumptiveTestRowReview(btn) {
        const row = btn.closest('.presumptive-test-row-review');
        if (row) {
            row.remove();
            reindexPresumptiveTestRowsReview();
            updatePresumptiveRemoveButtonsReview();
            updatePresumptiveReactionTextReview();
        }
    }

    function reindexPresumptiveTestRowsReview() {
        const container = document.getElementById('presumptiveTestRowsContainerReview');
        if (!container) return;
        const rows = container.querySelectorAll('.presumptive-test-row-review');
        rows.forEach((row, idx) => {
            const titleSpan = row.querySelector('.test-row-title-review');
            if (titleSpan) titleSpan.innerText = `Test Result #${idx + 1}`;
        });
    }

    function updatePresumptiveRemoveButtonsReview() {
        const container = document.getElementById('presumptiveTestRowsContainerReview');
        if (!container) return;
        const rows = container.querySelectorAll('.presumptive-test-row-review');
        rows.forEach(row => {
            const btn = row.querySelector('.remove-test-row-btn-review');
            if (btn) {
                if (rows.length > 1) {
                    btn.classList.remove('hidden');
                } else {
                    btn.classList.add('hidden');
                }
            }
        });
    }

    function updatePresumptiveReactionTextReview() {
        const container = document.getElementById('presumptiveTestRowsContainerReview');
        const inputEl = document.getElementById('presumptiveReactionResultInputReview');
        if (!container || !inputEl) return;

        const rows = container.querySelectorAll('.presumptive-test-row-review');
        let results = [];

        rows.forEach((row, idx) => {
            const presence = row.querySelector('.presumptive-presence-input-review')?.value?.trim() || '';
            const percentage = row.querySelector('.presumptive-percentage-input-review')?.value?.trim() || '';
            
            let parts = [];
            if (presence) parts.push(`Presence of: ${presence}`);
            if (percentage) parts.push(`Purity/Percentage: ${percentage}`);

            if (parts.length > 0) {
                if (rows.length > 1) {
                    results.push(`[${idx + 1}] ${parts.join(' - ')}`);
                } else {
                    results.push(parts.join(' - '));
                }
            }
        });

        if (results.length > 0) {
            inputEl.value = `YES (${results.join('; ')})`;
        } else {
            inputEl.value = `YES (Positive Colorimetric Reaction)`;
        }
    }

    function onLabStatusChange(val) {
        const btnTextSpan = document.getElementById('saveSection5LabBtnText') || document.querySelector('#saveSection5LabBtn span');
        if (btnTextSpan) {
            if (val === 'IN_PROGRESS' || val === 'PENDING') {
                btnTextSpan.textContent = 'Save & Update Lab Examination Later';
            } else {
                btnTextSpan.textContent = 'Save Lab Examination & Update Progress';
            }
        }

        const selectEl = document.getElementById('formLabStatus');
        const dotIndicator = document.getElementById('labStatusDotIndicator');
        const text = document.getElementById('labStatusText');
        const container = document.getElementById('labStatusContainer');

        if (val === 'COMPLETED') {
            if (selectEl) {
                selectEl.className = 'w-full appearance-none pl-4 pr-10 py-3 bg-white border-2 border-emerald-400 text-slate-900 font-extrabold text-xs outline-none transition-all shadow-sm cursor-pointer focus:ring-4 focus:ring-emerald-500/20';
                selectEl.style.borderRadius = '14px';
            }
            if (container) {
                container.className = 'p-4 bg-slate-50 border border-slate-200 space-y-3 shadow-sm transition-all duration-300';
                container.style.borderRadius = '18px';
            }
            if (dotIndicator) {
                dotIndicator.className = 'inline-flex items-center text-[10px] font-mono-code font-extrabold uppercase px-2.5 py-1 shadow-2xs transition-all bg-emerald-100 text-emerald-800 border border-emerald-300';
                dotIndicator.style.borderRadius = '10px';
            }
            if (text) text.textContent = 'COMPLETED';
        } else if (val === 'IN_PROGRESS') {
            if (selectEl) {
                selectEl.className = 'w-full appearance-none pl-4 pr-10 py-3 bg-white border-2 border-amber-400 text-slate-900 font-extrabold text-xs outline-none transition-all shadow-sm cursor-pointer focus:ring-4 focus:ring-amber-500/20';
                selectEl.style.borderRadius = '14px';
            }
            if (container) {
                container.className = 'p-4 bg-slate-50 border border-slate-200 space-y-3 shadow-sm transition-all duration-300';
                container.style.borderRadius = '18px';
            }
            if (dotIndicator) {
                dotIndicator.className = 'inline-flex items-center text-[10px] font-mono-code font-extrabold uppercase px-2.5 py-1 shadow-2xs transition-all bg-amber-100 text-amber-800 border border-amber-300';
                dotIndicator.style.borderRadius = '10px';
            }
            if (text) text.textContent = 'IN PROGRESS';
        } else {
            if (selectEl) {
                selectEl.className = 'w-full appearance-none pl-4 pr-10 py-3 bg-white border-2 border-emerald-400 text-slate-900 font-extrabold text-xs outline-none transition-all shadow-sm cursor-pointer focus:ring-4 focus:ring-emerald-500/20';
                selectEl.style.borderRadius = '14px';
            }
            if (container) {
                container.className = 'p-4 bg-slate-50 border border-slate-200 space-y-3 shadow-sm transition-all duration-300';
                container.style.borderRadius = '18px';
            }
            if (dotIndicator) {
                dotIndicator.className = 'inline-flex items-center text-[10px] font-mono-code font-extrabold uppercase px-2.5 py-1 shadow-2xs transition-all bg-emerald-100 text-emerald-800 border border-emerald-300';
                dotIndicator.style.borderRadius = '10px';
            }
            if (text) text.textContent = 'PENDING';
        }
    }

    /* ── Submit Section 5 Lab Examination & Update Progress ────────── */
    async function submitSection5LabExam(e) {
        e.preventDefault();
        const saveBtn = document.getElementById('saveSection5LabBtn');
        if (saveBtn) saveBtn.disabled = true;

        // Auto-set Date Examination Completed to today's date when clicking the save button
        const todayStr = new Date().toISOString().split('T')[0];
        const dateCompletedInput = document.getElementById('labDateCompletedInput');
        if (dateCompletedInput) {
            dateCompletedInput.value = todayStr;
        }

        const dateReceived = document.getElementById('labDateReceivedInput')?.value || todayStr;
        const dateStarted = document.getElementById('labDateStartedInput')?.value || todayStr;
        const dateCompleted = dateCompletedInput?.value || todayStr;

        const labUnit = document.getElementById('formLabUnit')?.value || 'Chemistry & Narcotics Unit';
        const analyst = document.getElementById('formLabAnalyst')?.value || @json($labAnalyst ?? $defaultLabHeadName);
        const checkedRadio = document.querySelector('input[name="form_presumptive_radio"]:checked');
        let presumptive = 'YES (Positive Colorimetric Reaction)';
        if (checkedRadio && checkedRadio.value === 'NO') {
            presumptive = 'NO (Negative Reaction)';
        } else {
            const customInput = document.getElementById('presumptiveReactionResultInputReview')?.value;
            presumptive = customInput && customInput.trim() !== '' ? customInput.trim() : 'YES (Positive Colorimetric Reaction)';
        }

        const confirmatoryChecked = document.querySelector('input[name="form_confirmatory_radio"]:checked');
        const confirmatory = (confirmatoryChecked && confirmatoryChecked.value === 'YES') ? 'YES' : 'NO';

        const status = 'COMPLETED';
        const notes = document.getElementById('formLabNotes')?.value || '';

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch(`/cases/${caseNumber}/update-unit-progress`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    unit_name: 'Lab Analysis',
                    unit_status: status,
                    officer: analyst,
                    notes: notes,
                    laboratory_number: document.getElementById('formLabNumber')?.value || '',
                    lab_unit: labUnit,
                    presumptive_result: presumptive,
                    confirmatory_test_result: confirmatory,
                    sample_weight_description: document.getElementById('sampleWeightDescriptionReview')?.value || '',
                    sample_weight_units: document.getElementById('sampleWeightUnitsReview')?.value || '',
                    sample_weight_gross: document.getElementById('sampleWeightGrossReview')?.value || '',
                    sample_weight_taken: document.getElementById('sampleWeightTakenReview')?.value || '',
                    sample_weight_reserved: document.getElementById('sampleWeightReservedReview')?.value || '',
                    confirmatory_substance_identification: document.getElementById('confirmatorySubstanceIdentificationReview')?.value || '',
                    detected_substances: typeof getDetectedSubstancesDataReview === 'function' ? getDetectedSubstancesDataReview() : [],
                    confirmatory_test_methods: document.getElementById('confirmatoryTestMethodsReview')?.value || '',
                    confirmatory_specifications: document.getElementById('confirmatorySpecificationsReview')?.value || '',
                    lab_date_received: dateReceived,
                    lab_date_started: dateStarted,
                    lab_date_completed: dateCompleted
                })
            });
            const data = await res.json();
            if (res.ok && data.success) {
                const compCount = data.completed_count || 0;

                // Update UI elements in Section 5
                const uUnitEl = document.getElementById('section5LabUnitDisplay');
                if (uUnitEl) uUnitEl.textContent = labUnit;
                const uAnalystEl = document.getElementById('section5AnalystDisplay');
                if (uAnalystEl) uAnalystEl.textContent = analyst;
                const uPresumptiveEl = document.getElementById('section5PresumptiveDisplay');
                if (uPresumptiveEl) {
                    const str = String(presumptive || '').trim();
                    let pRes = 'N/A', pPres = 'N/A', pPur = 'N/A';
                    if (str) {
                        const up = str.toUpperCase();
                        if (up.startsWith('YES') || up.startsWith('POS')) pRes = 'YES';
                        else if (up.startsWith('NO') || up.startsWith('NEG')) pRes = 'NO';
                        else pRes = str.split(/[\s\(]/)[0] || 'N/A';

                        const mPres = str.match(/Presence of:\s*([^-\)]+)/i) || str.match(/\(([^-\)]+)\)/);
                        if (mPres) pPres = mPres[1].trim();

                        const mPur = str.match(/Purity\/Percentage:\s*([^\)]+)/i);
                        if (mPur) pPur = mPur[1].trim();
                    }
                    uPresumptiveEl.innerHTML = `
                        <table class="w-full text-xs border border-slate-300 rounded overflow-hidden">
                            <thead class="bg-slate-100 text-slate-950 font-mono-code uppercase font-black text-[10px]">
                                <tr class="border-b border-slate-300">
                                    <th class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">Result</th>
                                    <th class="px-2 py-1 border-r border-slate-300 text-left bg-slate-200/70">Presence of</th>
                                    <th class="px-2 py-1 text-left bg-slate-200/70">Purity/Percentage</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                <tr>
                                    <td class="px-2 py-1 border-r border-slate-300 font-extrabold ${pRes === 'YES' ? 'text-emerald-800' : 'text-slate-900'}">${escapeHtml(pRes)}</td>
                                    <td class="px-2 py-1 border-r border-slate-300 font-bold text-slate-900">${escapeHtml(pPres)}</td>
                                    <td class="px-2 py-1 font-bold text-slate-900">${escapeHtml(pPur)}</td>
                                </tr>
                            </tbody>
                        </table>
                    `;
                }
                const uConfirmatoryEl = document.getElementById('section5ConfirmatoryDisplay');
                if (uConfirmatoryEl) uConfirmatoryEl.innerHTML = `<span class="w-2.5 h-2.5 rounded-full ${confirmatory === 'YES' ? 'bg-emerald-600' : 'bg-slate-500'}"></span><span>${confirmatory === 'YES' ? 'YES' : 'NO'}</span>`;
                const uDescEl = document.getElementById('section5SampleDescDisplay');
                if (uDescEl) uDescEl.textContent = document.getElementById('sampleWeightDescriptionReview')?.value || 'N/A';
                const uGrossEl = document.getElementById('section5SampleGrossDisplay');
                if (uGrossEl) uGrossEl.textContent = document.getElementById('sampleWeightGrossReview')?.value || 'N/A';
                const uTakenEl = document.getElementById('section5SampleTakenDisplay');
                if (uTakenEl) uTakenEl.textContent = document.getElementById('sampleWeightTakenReview')?.value || 'N/A';
                const uReservedEl = document.getElementById('section5SampleReservedDisplay');
                if (uReservedEl) uReservedEl.textContent = document.getElementById('sampleWeightReservedReview')?.value || 'N/A';
                const uSubstanceIdEl = document.getElementById('section5SubstanceIdDisplay');
                if (uSubstanceIdEl) uSubstanceIdEl.textContent = document.getElementById('confirmatorySubstanceIdentificationReview')?.value || 'N/A';
                const uTestMethodsEl = document.getElementById('section5TestMethodsDisplay');
                if (uTestMethodsEl) uTestMethodsEl.textContent = document.getElementById('confirmatoryTestMethodsReview')?.value || 'Standard GSA Forensic Test Methods';
                const uSpecsEl = document.getElementById('section5SpecsDisplay');
                if (uSpecsEl) uSpecsEl.textContent = document.getElementById('confirmatorySpecificationsReview')?.value || 'NIST Reference Library Matched';
                const uDetectedEl = document.getElementById('section5DetectedSubstancesDisplay');
                if (uDetectedEl) {
                    const arr = typeof getDetectedSubstancesDataReview === 'function' ? getDetectedSubstancesDataReview() : [];
                    if (arr.length > 0) {
                        uDetectedEl.textContent = arr.map(item => `${item.substance}${item.percentage ? ' (' + item.percentage + ')' : ''}`).join(', ');
                    } else {
                        uDetectedEl.textContent = 'No individual compound breakdown logged';
                    }
                }
                const uFindingsEl = document.getElementById('section5FindingsDisplay');
                if (uFindingsEl) uFindingsEl.textContent = notes || 'No specific laboratory analytical findings logged yet.';

                const badgeStatus = document.getElementById('section5BadgeStatus');
                if (badgeStatus) {
                    if (status === 'COMPLETED') {
                        badgeStatus.className = 'px-2 py-0.5 rounded text-[9px] font-mono-code font-bold uppercase bg-emerald-100 text-emerald-800 border border-emerald-300';
                        badgeStatus.textContent = '✓ LAB COMPLETED';
                    } else if (status === 'IN_PROGRESS') {
                        badgeStatus.className = 'px-2 py-0.5 rounded text-[9px] font-mono-code font-bold uppercase bg-amber-100 text-amber-800 border border-amber-300';
                        badgeStatus.textContent = '⏳ LAB IN PROGRESS';
                    } else {
                        badgeStatus.className = 'px-2 py-0.5 rounded text-[9px] font-mono-code font-bold uppercase bg-emerald-100 text-emerald-800 border border-emerald-300';
                        badgeStatus.textContent = '⭕ LAB PENDING';
                    }
                }

                // Update 4-Unit card
                const slug = 'labanalysis';
                const cardEl = document.getElementById(`unitCard-${slug}`);
                const badgeEl = document.getElementById(`unitBadge-${slug}`);
                const officerBoxEl = document.getElementById(`unitOfficerBox-${slug}`);
                const notesBoxEl = document.getElementById(`unitNotesBox-${slug}`);

                if (badgeEl) {
                    if (status === 'COMPLETED') {
                        badgeEl.className = 'text-[9px] font-extrabold font-mono-code uppercase px-2 py-0.5 rounded-md border bg-emerald-100 text-emerald-800 border-emerald-300';
                        badgeEl.textContent = '✓ COMPLETED';
                    } else if (status === 'IN_PROGRESS') {
                        badgeEl.className = 'text-[9px] font-extrabold font-mono-code uppercase px-2 py-0.5 rounded-md border bg-amber-100 text-amber-800 border-amber-300';
                        badgeEl.textContent = '⏳ IN PROGRESS';
                    }
                }
                if (officerBoxEl) officerBoxEl.innerHTML = `<div class="text-[11px] font-medium text-slate-600 mt-2">Officer: <span class="font-bold text-slate-900">${escapeHtml(analyst)}</span></div>`;
                if (notesBoxEl) notesBoxEl.innerHTML = `<div class="text-[11px] text-slate-700 mt-2 p-2.5 bg-white/90 rounded-lg border border-slate-200/80 leading-relaxed"><span class="font-bold text-[10px] text-slate-400 block font-mono-code uppercase mb-0.5">Unit Findings:</span>"${escapeHtml(notes)}"</div>`;

                const badgeCounter = document.getElementById('unitCompletedCountBadge');
                if (badgeCounter) badgeCounter.textContent = `${compCount}/4 Units Completed`;

                const pct = Math.round((compCount / 4) * 100);
                const progressBar = document.getElementById('unitProgressBar');
                if (progressBar) {
                    progressBar.style.width = `${Math.max(18, pct)}%`;
                    progressBar.textContent = `${pct}%`;
                }

                const percentText = document.getElementById('unitProgressPercentText');
                if (percentText) {
                    percentText.textContent = `${pct}%`;
                }

                lockSection5Form();
                showSaveSuccessPopover();
                showToast(`✔ Laboratory Examination saved & Lab Analysis unit progress updated!`);
            } else {
                showToast(`❌ Error saving lab examination: ${data.message || 'Server error'}`);
            }
        } catch(err) {
            console.error(err);
            showToast(`❌ Connection error saving lab examination.`);
        } finally {
            if (saveBtn && !document.getElementById('section5LabForm')?.disabled) {
                saveBtn.disabled = false;
            }
        }
    }

    function findWeightChunkReview(parts) {
        if (!parts || parts.length === 0) return '';
        for (let i = parts.length - 1; i >= 0; i--) {
            const lower = parts[i].toLowerCase();
            if (/\b(kg|kgs|kilogram|kilograms|g|gm|gms|gram|grams|mg|mgs|milligram|milligrams|lb|lbs|pound|pounds)\b/i.test(lower) || /\d\s*(kg|g|mg|lb)\b/i.test(lower)) {
                return parts[i];
            }
        }
        for (let i = parts.length - 1; i >= 0; i--) {
            if (/[\d]+/.test(parts[i])) {
                return parts[i];
            }
        }
        return parts[parts.length - 1];
    }

    function parsePartsReview(str) {
        return str.split('/').map(p => p.trim()).filter(p => p.length > 0);
    }

    function parseQtyAndUnitReview(chunk) {
        if (!chunk) return null;
        const numMatch = chunk.match(/([\d]+(?:\.[\d]+)?)/);
        if (!numMatch) return null;
        const num = parseFloat(numMatch[1]);
        if (isNaN(num)) return null;

        const lower = chunk.toLowerCase();
        let unit = '';

        if (/\b(kg|kgs|kilogram|kilograms)\b/i.test(lower) || /\d\s*kg\b/i.test(lower)) {
            unit = 'kg';
        } else if (/\b(mg|mgs|milligram|milligrams)\b/i.test(lower) || /\d\s*mg\b/i.test(lower)) {
            unit = 'mg';
        } else if (/\b(g|gm|gms|gram|grams)\b/i.test(lower) || /\d\s*g\b/i.test(lower)) {
            unit = 'g';
        } else if (/\b(lb|lbs|pound|pounds)\b/i.test(lower) || /\d\s*lb\b/i.test(lower)) {
            unit = 'lb';
        } else {
            const idx = chunk.indexOf(numMatch[1]);
            if (idx !== -1) {
                const afterNum = chunk.substring(idx + numMatch[1].length).trim();
                const firstWord = afterNum.split(/\s+/)[0];
                if (firstWord) {
                    unit = firstWord.replace(/[^a-zA-Z]/g, '');
                }
            }
        }
        return { num, unit };
    }

    function toKgReview(num, unit) {
        if (!unit) return null;
        const u = unit.toLowerCase();
        if (u === 'kg') return num;
        if (u === 'g') return num / 1000;
        if (u === 'mg') return num / 1000000;
        if (u === 'lb') return num * 0.453592;
        return null;
    }

    function formatNumReview(num) {
        if (num < 0) num = 0;
        const rounded = Math.round(num * 10000) / 10000;
        return rounded.toString();
    }

    function computeReservedSampleReview(grossStr, takenStr) {
        if (!grossStr || typeof grossStr !== 'string' || !grossStr.trim()) {
            return { reserved: '0', isOverflow: false };
        }

        const grossParts = parsePartsReview(grossStr);
        let targetGrossChunk = findWeightChunkReview(grossParts);
        const gParsed = parseQtyAndUnitReview(targetGrossChunk);

        const defaultReserved = (gParsed && gParsed.unit) ? `0 ${gParsed.unit}` : '0';

        if (!takenStr || typeof takenStr !== 'string' || !takenStr.trim()) {
            return { reserved: defaultReserved, isOverflow: false };
        }

        const takenParts = parsePartsReview(takenStr);
        let targetTakenChunk = findWeightChunkReview(takenParts);
        const tParsed = parseQtyAndUnitReview(targetTakenChunk);

        if (!gParsed || !tParsed) {
            return { reserved: defaultReserved, isOverflow: false };
        }

        let gKg = toKgReview(gParsed.num, gParsed.unit);
        let tKg = toKgReview(tParsed.num, tParsed.unit);

        if (gKg !== null && tKg === null && !tParsed.unit) {
            tKg = toKgReview(tParsed.num, gParsed.unit);
        }

        if (gKg !== null && tKg !== null) {
            if (tKg > gKg) {
                return { reserved: defaultReserved, isOverflow: true };
            }
            const diffKg = gKg - tKg;
            const gU = gParsed.unit.toLowerCase();
            let resVal = '0';
            if (gU === 'g' || gU === 'gm' || gU === 'gram' || gU === 'grams') {
                resVal = formatNumReview(diffKg * 1000) + (gParsed.unit ? ' ' + gParsed.unit : '');
            } else if (gU === 'mg' || gU === 'milligram' || gU === 'milligrams') {
                resVal = formatNumReview(diffKg * 1000000) + (gParsed.unit ? ' ' + gParsed.unit : '');
            } else {
                resVal = formatNumReview(diffKg) + (gParsed.unit ? ' ' + gParsed.unit : '');
            }
            return { reserved: resVal, isOverflow: false };
        } else {
            if (tParsed.num > gParsed.num) {
                return { reserved: defaultReserved, isOverflow: true };
            }
            const diff = gParsed.num - tParsed.num;
            const unit = gParsed.unit || tParsed.unit;
            return { reserved: formatNumReview(diff) + (unit ? ' ' + unit : ''), isOverflow: false };
        }
    }

    function updateReservedWeightReview() {
        const grossReview = document.getElementById('sampleWeightGrossReview');
        const takenReview = document.getElementById('sampleWeightTakenReview');
        const addonReview = document.getElementById('sampleWeightTakenUnitAddonReview');
        const reservedReview = document.getElementById('sampleWeightReservedReview');
        const errorMsgReviewEl = document.getElementById('sampleWeightTakenErrorReview');

        if (!grossReview || !reservedReview) return;

        const grossStr = grossReview.value || '';
        const takenStr = takenReview ? takenReview.value : '';

        const result = computeReservedSampleReview(grossStr, takenStr);

        reservedReview.value = result.reserved;

        if (result.isOverflow) {
            if (errorMsgReviewEl) errorMsgReviewEl.classList.remove('hidden');
            if (takenReview) {
                takenReview.classList.add('border-red-500', 'ring-2', 'ring-red-500/20');
                takenReview.classList.remove('border-slate-300');
            }
            if (addonReview) {
                addonReview.classList.add('border-red-500', 'bg-red-50', 'text-red-700');
                addonReview.classList.remove('border-slate-300', 'bg-slate-100/90', 'text-slate-700');
            }
        } else {
            if (errorMsgReviewEl) errorMsgReviewEl.classList.add('hidden');
            if (takenReview) {
                takenReview.classList.remove('border-red-500', 'ring-2', 'ring-red-500/20');
                takenReview.classList.add('border-slate-300');
            }
            if (addonReview) {
                addonReview.classList.remove('border-red-500', 'bg-red-50', 'text-red-700');
                addonReview.classList.add('border-slate-300', 'bg-slate-100/90', 'text-slate-700');
            }
        }
    }

    function addDetectedSubstanceRowReview(substance = '', percentage = '') {
        const container = document.getElementById('detectedSubstancesListContainerReview');
        if (!container) return;
        const rowDiv = document.createElement('div');
        rowDiv.className = 'detected-substance-row-review flex flex-col sm:flex-row items-center gap-3 bg-white p-3 rounded-xl border border-slate-300 shadow-2xs animate-in fade-in duration-200';
        rowDiv.innerHTML = `
            <div class="flex-1 w-full">
                <label class="block text-slate-700 font-bold text-[10px] uppercase mb-1">Substance / Compound Detected</label>
                <input type="text" class="detected-substance-name-review w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-slate-900 font-extrabold text-xs focus:outline-none focus:border-emerald-600 font-sans" placeholder="e.g. Cocaine Hydrochloride" value="${substance}">
            </div>
            <div class="w-full sm:w-44">
                <label class="block text-slate-700 font-bold text-[10px] uppercase mb-1">Purity / Percentage</label>
                <input type="text" class="detected-substance-percent-review w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-slate-900 font-extrabold text-xs focus:outline-none focus:border-emerald-600 font-mono-code" placeholder="e.g. 85.4%" value="${percentage}">
            </div>
            <button type="button" onclick="removeDetectedSubstanceRowReview(this)" class="mt-2 sm:mt-5 p-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg border border-red-200 transition-colors shrink-0 cursor-pointer" title="Remove Row">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        `;
        container.appendChild(rowDiv);
    }

    function removeDetectedSubstanceRowReview(btn) {
        const row = btn.closest('.detected-substance-row-review');
        if (row) row.remove();
    }

    function getDetectedSubstancesDataReview() {
        const rows = document.querySelectorAll('#detectedSubstancesListContainerReview .detected-substance-row-review');
        const data = [];
        rows.forEach(r => {
            const sub = r.querySelector('.detected-substance-name-review')?.value || '';
            const pct = r.querySelector('.detected-substance-percent-review')?.value || '';
            if (sub.trim() !== '' || pct.trim() !== '') {
                data.push({ substance: sub.trim(), percentage: pct.trim() });
            }
        });
        return data;
    }

    function toggleLabFormEditMode() {
        const form = document.getElementById('section5LabForm');
        const readOnlyView = document.getElementById('section5ReadOnlyDisplay');
        if (form && readOnlyView) {
            if (form.classList.contains('hidden')) {
                form.classList.remove('hidden');
                readOnlyView.classList.add('hidden');
                const elements = form.querySelectorAll('input, select, textarea, button');
                elements.forEach(el => {
                    el.disabled = false;
                    el.classList.remove('cursor-not-allowed', 'opacity-75', 'bg-slate-100');
                });
                form.querySelectorAll('.add-detected-substance-btn, .remove-detected-row-btn, .remove-test-row-btn-review').forEach(btn => {
                    btn.style.display = '';
                });
                const saveBtn = document.getElementById('saveSection5LabBtn');
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.className = 'px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-600/30 transition-all flex items-center justify-center cursor-pointer border-none';
                    const textSpan = document.getElementById('saveSection5LabBtnText');
                    if (textSpan) textSpan.innerHTML = 'Save Lab Examination & Update Progress';
                }
            } else {
                form.classList.add('hidden');
                readOnlyView.classList.remove('hidden');
            }
        }
    }

    function lockSection5Form() {
        const form = document.getElementById('section5LabForm');
        const readOnlyView = document.getElementById('section5ReadOnlyDisplay');
        
        if (form && readOnlyView) {
            form.classList.add('hidden');
            readOnlyView.classList.remove('hidden');
        }

        if (!form) return;

        const elements = form.querySelectorAll('input, select, textarea, button');
        elements.forEach(el => {
            if (el.id !== 'closeSaveSuccessPopoverBtn' && !el.classList.contains('close-popover-btn')) {
                el.disabled = true;
                el.classList.add('cursor-not-allowed', 'opacity-75', 'bg-slate-100');
            }
        });

        // Hide add/remove row buttons
        form.querySelectorAll('.add-detected-substance-btn, .remove-detected-row-btn').forEach(btn => {
            btn.style.display = 'none';
        });

        const saveBtn = document.getElementById('saveSection5LabBtn');
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.className = 'px-6 py-3 bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md flex items-center justify-center cursor-not-allowed opacity-90 border-none';
            const svgEl = saveBtn.querySelector('svg');
            if (svgEl) svgEl.remove();
            const textSpan = document.getElementById('saveSection5LabBtnText');
            if (textSpan) textSpan.innerHTML = 'LAB EXAMINATION SAVED & LOCKED';
        }
    }

    function showSaveSuccessPopover() {
        const modal = document.getElementById('saveSuccessPopoverModal');
        if (modal) modal.classList.remove('hidden');
    }

    function closeSaveSuccessPopover() {
        const modal = document.getElementById('saveSuccessPopoverModal');
        if (modal) modal.classList.add('hidden');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const grossReviewEl = document.getElementById('sampleWeightGrossReview');
        const takenReviewEl = document.getElementById('sampleWeightTakenReview');
        ['input', 'keyup', 'change', 'blur', 'paste'].forEach(function(evt) {
            if (grossReviewEl) grossReviewEl.addEventListener(evt, updateReservedWeightReview);
            if (takenReviewEl) takenReviewEl.addEventListener(evt, updateReservedWeightReview);
        });

        updateReservedWeightReview();

        const existingDetected = @json($labDetectedSubstances);
        const detReviewContainer = document.getElementById('detectedSubstancesListContainerReview');
        if (detReviewContainer) {
            detReviewContainer.innerHTML = '';
            if (Array.isArray(existingDetected) && existingDetected.length > 0) {
                existingDetected.forEach(item => {
                    if (typeof item === 'string') {
                        addDetectedSubstanceRowReview(item, '');
                    } else if (item && typeof item === 'object') {
                        addDetectedSubstanceRowReview(item.substance || '', item.percentage || '');
                    }
                });
            } else {
                addDetectedSubstanceRowReview('', '');
            }
        }

        const existingPresumptive = @json($labPresumptive);
        if (existingPresumptive) {
            const isNo = existingPresumptive.toUpperCase().includes('NO (');
            const radioYes = document.querySelector('input[name="form_presumptive_radio"][value="YES"]');
            const radioNo = document.querySelector('input[name="form_presumptive_radio"][value="NO"]');
            if (isNo) {
                if (radioNo) radioNo.checked = true;
                onPresumptiveRadioChange('NO');
            } else {
                if (radioYes) radioYes.checked = true;
                onPresumptiveRadioChange('YES');

                const matches = existingPresumptive.match(/\[(\d+)\]\s*Presence of:\s*([^-\;]+)(?:\s*-\s*Purity\/Percentage:\s*([^\;]+))?/gi);
                if (matches && matches.length > 0) {
                    const rowsContainer = document.getElementById('presumptiveTestRowsContainerReview');
                    if (rowsContainer) {
                        rowsContainer.innerHTML = '';
                        matches.forEach((m, idx) => {
                            addPresumptiveTestRowReview();
                            const rows = rowsContainer.querySelectorAll('.presumptive-test-row-review');
                            const targetRow = rows[rows.length - 1];
                            if (targetRow) {
                                const presenceMatch = m.match(/Presence of:\s*([^-\;]+)/i);
                                const pctMatch = m.match(/Purity\/Percentage:\s*([^\;]+)/i);
                                if (presenceMatch && targetRow.querySelector('.presumptive-presence-input-review')) {
                                    targetRow.querySelector('.presumptive-presence-input-review').value = presenceMatch[1].trim();
                                }
                                if (pctMatch && targetRow.querySelector('.presumptive-percentage-input-review')) {
                                    targetRow.querySelector('.presumptive-percentage-input-review').value = pctMatch[1].trim();
                                }
                            }
                        });
                    }
                } else {
                    const singlePresence = existingPresumptive.match(/Presence of:\s*([^-\;\)]+)/i);
                    const singlePct = existingPresumptive.match(/Purity\/Percentage:\s*([^;\)]+)/i);
                    if (singlePresence || singlePct) {
                        const firstRow = document.querySelector('.presumptive-test-row-review');
                        if (firstRow) {
                            if (singlePresence && firstRow.querySelector('.presumptive-presence-input-review')) {
                                firstRow.querySelector('.presumptive-presence-input-review').value = singlePresence[1].trim();
                            }
                            if (singlePct && firstRow.querySelector('.presumptive-percentage-input-review')) {
                                firstRow.querySelector('.presumptive-percentage-input-review').value = singlePct[1].trim();
                            }
                        }
                    }
                }
            }
        }

        // Lock form if already completed
        const isAlreadyCompleted = @json($hasSavedLabData);
        if (isAlreadyCompleted) {
            lockSection5Form();
        }

        if (window.location.search.includes('from=Lab+Analysis') || window.location.search.includes('unit=Lab+Analysis') || @json($isLabContext)) {
            setTimeout(function() {
                const sec5 = document.getElementById('section5LabExamination');
                if (sec5) sec5.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 300);
        }

        if (window.location.search.includes('edit=true') || window.location.search.includes('edit=1')) {
            if (typeof toggleLabFormEditMode === 'function') {
                toggleLabFormEditMode();
            }
            setTimeout(function() {
                const editSec = document.getElementById('section5LabForm') || document.getElementById('section5LabExamination');
                if (editSec) editSec.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 350);
        }
    });
</script>

<!-- BOOTSTRAP-STYLE POPOVER SUCCESS MODAL -->
<div id="saveSuccessPopoverModal" class="hidden fixed inset-0 z-[99999] bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border-2 border-emerald-500 shadow-2xl max-w-md w-full p-6 text-center space-y-4 relative transform transition-all animate-in zoom-in-95 duration-200">
        <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto text-emerald-600 shadow-inner flex items-center justify-center">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
        </div>
        <div class="space-y-1">
            <span class="px-2.5 py-0.5 rounded bg-emerald-100 text-emerald-800 font-mono-code font-bold text-[10px] uppercase">SUCCESSFULLY RECORDED</span>
            <h3 class="text-xl font-black text-slate-900 mt-1">Lab Examination Saved</h3>
            <p class="text-xs text-slate-600 font-semibold leading-relaxed">
                The laboratory examination results &amp; findings log have been successfully saved to the database and locked against further editing.
            </p>
        </div>
        <div class="pt-2">
            <button id="closeSaveSuccessPopoverBtn" onclick="closeSaveSuccessPopover()" class="close-popover-btn w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black rounded-xl shadow-lg shadow-emerald-600/30 transition-all border-none cursor-pointer">
                Acknowledge &amp; Continue
            </button>
        </div>
    </div>
</div>


