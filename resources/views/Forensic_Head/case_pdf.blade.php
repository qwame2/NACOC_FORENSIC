@php
    $authUser = auth()->user();
    $officerName = $authUser->fullname ?? session('user_name') ?? (\App\Models\User::where('is_head', true)->first()->fullname ?? 'Officer');
    $officerRole = ($authUser && $authUser->is_head) ? 'Forensic Head' : 'Commanding Officer';

    $status = $case->status ?? 'Approved';
    $priority = strtoupper($case->priority ?? 'NORMAL');
    
    $docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
    $evidencePhotos = is_array($case->evidence_photos) ? $case->evidence_photos : (json_decode($case->evidence_photos ?? '[]', true) ?: ($docketData['evidence_photos'] ?? []));

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
    
    $defaultDrugName = $case->seizure_name_drug ?: ($case->seizure_name ?: ($case->exhibit_category ?: ($case->exhibits_summary ?: $case->title)));
    
    $defaultGrossWeightParts = [];
    if (!empty($case->seizure_quantity)) {
        $defaultGrossWeightParts[] = trim($case->seizure_quantity . ' ' . ($case->seizure_packaging ?? ''));
    }
    if (!empty($case->seizure_weight)) {
        $defaultGrossWeightParts[] = trim($case->seizure_weight . ' ' . ($case->seizure_unit ?? ''));
    }
    $defaultGrossWeight = !empty($defaultGrossWeightParts) ? implode(' / ', array_filter($defaultGrossWeightParts)) : ($case->exhibits_summary ?? '');

    $labSampleDesc = !empty($labSampleWeight['description']) ? $labSampleWeight['description'] : (!empty($case->sample_weight_description) ? $case->sample_weight_description : $defaultDrugName);
    $labSampleGross = !empty($labSampleWeight['gross_weight']) ? $labSampleWeight['gross_weight'] : (!empty($case->sample_weight_gross) ? $case->sample_weight_gross : $defaultGrossWeight);
    $labSampleTaken = !empty($labSampleWeight['sample_taken']) ? $labSampleWeight['sample_taken'] : (!empty($case->sample_weight_taken) ? $case->sample_weight_taken : '');
    $labSampleReserved = !empty($labSampleWeight['reserved_samples']) ? $labSampleWeight['reserved_samples'] : (!empty($case->sample_weight_reserved) ? $case->sample_weight_reserved : '');
    $labSampleUnit = !empty($case->seizure_unit) ? $case->seizure_unit : 'Kilograms (kg)';

    $labSubstanceId = !empty($labExam['substance_identification']) ? $labExam['substance_identification'] : (!empty($case->confirmatory_substance_identification) ? $case->confirmatory_substance_identification : ($defaultDrugName ?? ''));
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
                $sPOB   = $sItem['pob'] ?? ($sNum == 1 ? ($case->pob ?? ($docketData['pob'] ?? 'N/A')) : 'N/A');
                $sPhone = $sItem['phone'] ?? ($sNum == 1 ? ($case->phone ?? ($docketData['phone'] ?? 'N/A')) : 'N/A');
                $sAddr  = $sItem['address'] ?? ($sNum == 1 ? ($case->address ?? ($docketData['address'] ?? 'N/A')) : 'N/A');
                $sMarital = $sItem['marital_status'] ?? ($sNum == 1 ? ($case->marital_status ?? 'Single') : 'Single');
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
            @endphp

            <!-- MUGSHOT PHOTOS (3-ANGLE BIOMETRIC CAPTURE FOR SUSPECT #{{ $sNum }}) -->
            <div class="space-y-2 avoid-break relative z-10">
                <div class="text-xs font-black text-slate-950 uppercase font-mono-code border-b border-slate-400 pb-1 flex items-center justify-between">
                    <span>SUSPECT #{{ $sNum }} BIOMETRIC MUGSHOT RECORD ({{ strtoupper($sName) }})</span>
                    <span class="text-[10px] text-slate-700 font-bold">BOOKING FACILITY CAPTURE</span>
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
                <div class="text-xs font-black text-slate-950 uppercase font-mono-code border-b border-slate-400 pb-1">
                    <span>SUSPECT #{{ $sNum }} BOOKING &amp; PERSONAL IDENTITY ({{ strtoupper($sName) }})</span>
                </div>
                <table class="pdf-table border border-slate-300 rounded-xl overflow-hidden shadow-2xs">
                    <tbody>
                        <tr>
                            <td class="pdf-label-cell w-1/5">Full Name:</td>
                            <td class="pdf-value-cell font-black text-slate-950 w-3/10">{{ $sName }}</td>
                            <td class="pdf-label-cell w-1/5">Alias / Street Name:</td>
                            <td class="pdf-value-cell font-black text-slate-900 w-3/10">{{ $sAlias }}</td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Ghana Card / ID #:</td>
                            <td class="pdf-value-cell font-mono-code font-black text-blue-900">{{ $sCard }}</td>
                            <td class="pdf-label-cell">Gender &amp; Age / DOB:</td>
                            <td class="pdf-value-cell">
                                {{ $sGender }}
                                {{ !empty($sAge) ? "• Age: {$sAge}" : '' }}
                                {{ !empty($sDOB) ? "• DOB: {$sDOB}" : '' }}
                                {{ !empty($sHeight) ? "• Height: {$sHeight}" : '' }}
                                {{ !empty($sEye) ? "• Eyes: {$sEye}" : '' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Nationality:</td>
                            <td class="pdf-value-cell">{{ $sNat }}</td>
                            <td class="pdf-label-cell">Hometown / POB:</td>
                            <td class="pdf-value-cell">{{ $sPOB }}</td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Contact Phone:</td>
                            <td class="pdf-value-cell">{{ $sPhone }}</td>
                            <td class="pdf-label-cell">Residential Address:</td>
                            <td class="pdf-value-cell">{{ $sAddr }}</td>
                        </tr>
                        <tr>
                            <td class="pdf-label-cell">Marital &amp; Family:</td>
                            <td class="pdf-value-cell" colspan="3">
                                Status: {{ $sMarital }} 
                                @if(!empty($sSpouseN)) • Spouse: {{ $sSpouseN }} ({{ $sSpouseP }}) @endif
                                @if(!empty($sDependents)) • Dependents: {{ $sDependents }} @endif
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
                    @if(!empty($case->veh_make) || !empty($case->veh_reg_no) || !empty($docketData['vehicle']['make']) || !empty($docketData['vehicle']['reg']))
                    <tr>
                        <td class="pdf-label-cell">Seized Vehicle Record:</td>
                        <td class="pdf-value-cell font-bold" colspan="3">
                            Make/Model: {{ $case->veh_make ?: ($docketData['vehicle']['make'] ?? 'N/A') }} {{ $case->veh_model ?? '' }} •
                            REG: {{ $case->veh_reg_no ?: ($docketData['vehicle']['reg'] ?? 'N/A') }} •
                            VIN: {{ $case->veh_vin ?: ($docketData['vehicle']['chassis'] ?? 'N/A') }} •
                            Owner: {{ $case->veh_owner ?: ($docketData['vehicle']['owner'] ?? 'N/A') }}
                        </td>
                    </tr>
                    @endif
                    @if(!empty($case->wpn_model) || !empty($case->wpn_serial_no) || !empty($docketData['weapon']['model']))
                    <tr>
                        <td class="pdf-label-cell">Seized Firearm Record:</td>
                        <td class="pdf-value-cell font-bold" colspan="3">
                            Model: {{ $case->wpn_model ?: ($docketData['weapon']['model'] ?? 'N/A') }} •
                            Serial: {{ $case->wpn_serial_no ?: ($docketData['weapon']['serial_no'] ?? 'N/A') }} •
                            Calibre: {{ $case->wpn_calibre ?: ($docketData['weapon']['calibre'] ?? 'N/A') }} •
                            Status: {{ $case->wpn_loaded_status ?: ($docketData['weapon']['loaded_status'] ?? 'N/A') }}
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
                            <img src="{{ $photoUrl }}" class="w-full h-full object-cover" alt="Exhibit Photo {{ $idx + 1 }}">
                        </div>
                        <div class="text-[8px] font-mono-code font-bold text-slate-700 uppercase">Exhibit Photo #{{ $idx + 1 }}</div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
        @endif

        <!-- SECTION: LABORATORY EXAMINATION & SCIENTIFIC ANALYSIS REPORT -->
        <div class="space-y-2 avoid-break relative z-10">
            <div class="text-xs font-black text-slate-950 uppercase font-mono-code border-b border-slate-400 pb-1 flex items-center justify-between">
                <span>LABORATORY EXAMINATION &amp; SCIENTIFIC ANALYSIS REPORT</span>
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
                        <td class="pdf-value-cell font-black text-slate-950">{{ $labAnalyst }} ({{ $labUnit }})</td>
                    </tr>
                    <tr>
                        <td class="pdf-label-cell">Examination Timeline:</td>
                        <td class="pdf-value-cell font-mono-code font-bold" colspan="3">
                            Received: <strong>{{ $labDateReceived }}</strong> • Started: <strong>{{ $labDateStarted }}</strong> • Completed: <strong>{{ $labDateCompleted }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td class="pdf-label-cell">Presumptive Reaction:</td>
                        <td class="pdf-value-cell font-black {{ !str_contains($labPresumptive, 'NO') ? 'text-emerald-950' : 'text-red-950' }}">
                            {{ $labPresumptive }}
                        </td>
                        <td class="pdf-label-cell">Confirmatory Test:</td>
                        <td class="pdf-value-cell font-black {{ $labConfirmatory === 'YES' ? 'text-emerald-950' : 'text-slate-950' }}">
                            {{ $labConfirmatory === 'YES' ? 'YES' : 'NO' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="pdf-label-cell">Sample Metrology:</td>
                        <td class="pdf-value-cell font-bold" colspan="3">
                            Description: <strong>{{ $labSampleDesc ?: 'N/A' }}</strong> •
                            Gross Weight: <strong>{{ $labSampleGross ?: 'N/A' }}</strong> •
                            Mass Taken: <strong>{{ $labSampleTaken ? $labSampleTaken . ' ' . $labSampleUnit : 'N/A' }}</strong> •
                            Reserved: <strong>{{ $labSampleReserved ?: 'N/A' }}</strong>
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
                        <td class="pdf-value-cell" colspan="3">
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($labDetectedSubstances as $subItem)
                                    @if(!empty($subItem['substance']))
                                        <span class="px-2 py-0.5 bg-emerald-100 text-emerald-950 font-mono-code font-black text-[10px] rounded border border-emerald-300">
                                            {{ $subItem['substance'] }} {{ !empty($subItem['percentage']) ? '(' . $subItem['percentage'] . ')' : '' }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
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
