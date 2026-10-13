@php
    $authUser = auth()->user();
    $dbUnitHead = \App\Models\User::where('unit', 'LIKE', '%Digital%')->orWhere('unit', 'LIKE', '%Physical%')->where('is_head', true)->first()
               ?? \App\Models\User::where('is_head', true)->first();
    $headName = $authUser->fullname ?? session('user_name') ?? ($dbUnitHead ? $dbUnitHead->fullname : 'Lead Cyber Analyst');
    $allCases = \App\Models\ForensicCase::allCases();
    $cases = $allCases->filter(fn($c) => $c->isDigitalCase())->values();
    $savedReports = \App\Models\DpfReport::latest()->get()->unique('case_number')->values();

    $caseMapData = [];
    foreach ($cases as $cItem) {
        $cNo = $cItem->case_number ?? ($cItem['case_number'] ?? '');
        if (empty($cNo)) continue;

        $dData = is_array($cItem->docket_data) ? $cItem->docket_data : (json_decode($cItem->docket_data ?? '[]', true) ?: []);

        // Fetch saved Raid Scene / Offence Location
        $loc = $cItem->raid_location 
            ?? ($cItem->seizure_location 
            ?? ($dData['raid_location'] 
            ?? ($dData['seizure_location'] 
            ?? ($dData['seizures'][0]['seizure_location'] 
            ?? ($cItem->location ?? null)))));

        if (empty($loc)) {
            $loc = 'Kotoka International Airport (Cargo Wing)';
        }

        // Fetch Seizure / Commodity Name (Devices / Exhibits)
        $exList = [];
        if (isset($cItem->underlyingRecord) && isset($cItem->underlyingRecord->exhibits) && count($cItem->underlyingRecord->exhibits) > 0) {
            foreach ($cItem->underlyingRecord->exhibits as $ex) {
                $itemStr = '';
                $devName = !empty($ex->dev_model) ? $ex->dev_model : ($ex->commodity_name ?: ($ex->exhibit_category ?: ''));
                $qty = !empty($ex->seizure_quantity) ? trim((string)$ex->seizure_quantity) : '';
                if (!empty($qty) && !empty($devName)) {
                    if (preg_match('/^' . preg_quote($qty, '/') . '(\s+|x\s*)/i', $devName)) {
                        $itemStr = $devName;
                    } else {
                        $itemStr = $qty . ' ' . $devName;
                    }
                } else {
                    $itemStr = $devName ?: $qty;
                }
                $itemStr = trim($itemStr);
                if (!empty($itemStr)) {
                    $exList[] = $itemStr;
                }
            }
        }

        if (empty($exList) && !empty($dData['seizures']) && is_array($dData['seizures'])) {
            foreach ($dData['seizures'] as $sz) {
                $devName = !empty($sz['dev_model']) ? $sz['dev_model'] : ($sz['commodity_name'] ?? ($sz['seizure_name'] ?? ($sz['name'] ?? '')));
                $qty = !empty($sz['seizure_quantity']) ? trim((string)$sz['seizure_quantity']) : '';
                if (!empty($qty) && !empty($devName)) {
                    if (preg_match('/^' . preg_quote($qty, '/') . '(\s+|x\s*)/i', $devName)) {
                        $itemStr = $devName;
                    } else {
                        $itemStr = $qty . ' ' . $devName;
                    }
                } else {
                    $itemStr = $devName ?: $qty;
                }
                $itemStr = trim($itemStr);
                if (!empty($itemStr)) {
                    $exList[] = $itemStr;
                }
            }
        }

        if (empty($exList)) {
            $mainName = $cItem->dev_model ?? ($cItem->seizure_name ?? ($cItem->seizure_name_drug ?? ($cItem->exhibit_category ?? null)));
            if (!empty($mainName)) {
                $qty = !empty($cItem->seizure_quantity) ? trim((string)$cItem->seizure_quantity) : '';
                $exList[] = !empty($qty) && !preg_match('/^' . preg_quote($qty, '/') . '(\s+|x\s*)/i', $mainName) ? $qty . ' ' . $mainName : $mainName;
            }
        }

        $devicesStr = !empty($exList) ? implode(', ', array_unique($exList)) : 'Electronic Devices / Mobile Exhibits';

        $qtyList = [];
        if (isset($cItem->underlyingRecord) && isset($cItem->underlyingRecord->exhibits) && count($cItem->underlyingRecord->exhibits) > 0) {
            foreach ($cItem->underlyingRecord->exhibits as $ex) {
                if (!empty($ex->seizure_quantity)) {
                    $qtyList[] = $ex->seizure_quantity;
                }
            }
        }
        if (empty($qtyList) && !empty($dData['seizures']) && is_array($dData['seizures'])) {
            foreach ($dData['seizures'] as $sz) {
                if (!empty($sz['seizure_quantity'])) {
                    $qtyList[] = $sz['seizure_quantity'];
                }
            }
        }
        if (empty($qtyList) && !empty($cItem->seizure_quantity)) {
            $qtyList[] = $cItem->seizure_quantity;
        }
        $qtyStr = !empty($qtyList) ? implode(', ', array_unique($qtyList)) : '1';

        $generatedNarrative = method_exists($cItem, 'generateBriefCaseDescription') 
            ? $cItem->generateBriefCaseDescription() 
            : null;

        if (empty($generatedNarrative)) {
            $suspectOp = $cItem->operation_code_name 
                ?? ($dData['operation_code_name'] 
                ?? ($dData['suspects'][0]['operation_code_name'] 
                ?? ($cItem->operation_code ?? 'intelligence-led')));
            $suspectOpType = !str_contains(strtolower($suspectOp), 'operation') ? strtolower($suspectOp) . ' operation' : strtolower($suspectOp);
            $art = preg_match('/^[aeiou]/i', $suspectOpType) ? 'an' : 'a';

            $cFlags = method_exists($cItem, 'classifyExhibits') ? $cItem->classifyExhibits() : [];
            $cHasDrug = !empty($cFlags['has_drug']) || (method_exists($cItem, 'isDrugCase') && $cItem->isDrugCase());
            $cHasElectronic = !empty($cFlags['has_electronic']) || (method_exists($cItem, 'isDigitalCase') && $cItem->isDigitalCase()) || !empty($cItem->phones);

            $sName = $cItem->suspect_name ?? ($dData['suspects'][0]['suspect_name'] ?? 'Victor Kofi');

            if ($cHasElectronic && !$cHasDrug) {
                $generatedNarrative = "On Thursday, 18 June 2026, officers of the Narcotics Control Commission (NACOC) conducted {$art} {$suspectOpType} at Agbogbloshie in the Greater Accra Region to intercept and seize illicit electronic devices and digital evidence within the area. During the operation, {$sName} was arrested and electronic exhibits were seized, and the suspect was subsequently conveyed to NACOC Headquarters for digital forensic examination and further investigation.";
            } elseif ($cHasElectronic && $cHasDrug) {
                $generatedNarrative = "On Thursday, 18 June 2026, officers of the Narcotics Control Commission (NACOC) conducted {$art} {$suspectOpType} at Agbogbloshie in the Greater Accra Region to curb the trafficking of narcotic drugs and seize electronic devices used in illicit activities within the area. During the operation, {$sName} was arrested in possession of narcotic substances and electronic exhibits, and subsequently conveyed to NACOC Headquarters for chemical laboratory analysis and digital forensic investigation.";
            } else {
                $generatedNarrative = "On Thursday, 18 June 2026, officers of the Narcotics Control Commission (NACOC) conducted {$art} {$suspectOpType} at Agbogbloshie in the Greater Accra Region to curb the use and sale of narcotic drugs within the area. During the operation, {$sName} was arrested and subsequently conveyed to NACOC Headquarters for further investigation.";
            }
        }

        $descStr = $generatedNarrative;

        // Fetch Arrest Date & Time
        $rawArrestDate = $cItem->arrest_date 
            ?? ($cItem->date_of_arrest 
            ?? ($cItem->incident_date 
            ?? ($dData['arrest_date'] 
            ?? ($dData['date_of_arrest'] 
            ?? ($dData['incident_date'] 
            ?? ($dData['suspects'][0]['arrest_date'] 
            ?? ($dData['suspects'][0]['date_of_arrest'] 
            ?? null)))))));

        if (!empty($rawArrestDate)) {
            $parsedDate = date('Y-m-d', strtotime((string)$rawArrestDate));
            $arrestDateVal = ($parsedDate && $parsedDate !== '1970-01-01') ? $parsedDate : ($cItem->created_at ? $cItem->created_at->format('Y-m-d') : date('Y-m-d'));
        } else {
            $arrestDateVal = $cItem->created_at ? $cItem->created_at->format('Y-m-d') : date('Y-m-d');
        }

        $savedForCase = $savedReports->firstWhere('case_number', $cNo);
        $rawExtMsg = $savedForCase ? ($savedForCase->report_data['extraction_analysis_notes'] ?? ($savedForCase->extraction_analysis_message ?? '')) : '';
        $rawExtStatus = $savedForCase ? ($savedForCase->report_data['extraction_analysis_status'] ?? '') : '';
        $cleanExtMsg = trim((string)$rawExtMsg);
        if (preg_match('/^(YES|NO|IN PROGRESS|PENDING)(\s*[-–—:]\s*\1)?$/i', $cleanExtMsg)) {
            if (empty($rawExtStatus)) {
                $rawExtStatus = strtoupper(preg_replace('/[^A-Za-z ]/', '', explode('-', $cleanExtMsg)[0]));
            }
            $cleanExtMsg = '';
        } elseif (!empty($rawExtStatus)) {
            $cleanExtMsg = trim(preg_replace('/^' . preg_quote($rawExtStatus, '/') . '\s*[-–—:]\s*/i', '', $cleanExtMsg));
            if (strtoupper($cleanExtMsg) === strtoupper($rawExtStatus) || in_array(strtoupper($cleanExtMsg), ['N/A', 'NONE', 'NO', 'YES'])) {
                $cleanExtMsg = '';
            }
        }

        $rawRwMsg = $savedForCase ? ($savedForCase->report_data['report_witness_statement_notes'] ?? ($savedForCase->report_witness_statement_message ?? '')) : '';
        $rawRwStatus = $savedForCase ? ($savedForCase->report_data['report_witness_statement_status'] ?? '') : '';
        $cleanRwMsg = trim((string)$rawRwMsg);
        if (preg_match('/^(YES|NO|IN PROGRESS|PENDING)(\s*[-–—:]\s*\1)?$/i', $cleanRwMsg)) {
            if (empty($rawRwStatus)) {
                $rawRwStatus = strtoupper(preg_replace('/[^A-Za-z ]/', '', explode('-', $cleanRwMsg)[0]));
            }
            $cleanRwMsg = '';
        } elseif (!empty($rawRwStatus)) {
            $cleanRwMsg = trim(preg_replace('/^' . preg_quote($rawRwStatus, '/') . '\s*[-–—:]\s*/i', '', $cleanRwMsg));
            if (strtoupper($cleanRwMsg) === strtoupper($rawRwStatus) || in_array(strtoupper($cleanRwMsg), ['N/A', 'NONE', 'NO', 'YES'])) {
                $cleanRwMsg = '';
            }
        }

        $rawOngExtMsg = $savedForCase ? ($savedForCase->report_data['ongoing_extraction_analysis_notes'] ?? '') : '';
        $rawOngExtStatus = $savedForCase ? ($savedForCase->report_data['ongoing_extraction_analysis_status'] ?? '') : '';

        $isCourtGranted = $savedForCase && strtoupper(trim($savedForCase->court_order_granted ?? 'NO')) === 'YES';

        $caseMapData[$cNo] = [
            'location'                  => $loc,
            'devices'                   => $devicesStr,
            'qty'                       => $qtyStr,
            'description'               => $descStr,
            'date'                      => $arrestDateVal,
            'court_order_requested'     => $savedForCase ? $savedForCase->court_order_requested : 'NO',
            'court_order_granted'       => $savedForCase ? $savedForCase->court_order_granted : 'NO',
            'extraction_status'         => $rawExtStatus ?: (!$isCourtGranted && $savedForCase && in_array(strtoupper($savedForCase->extraction_analysis_conducted ?? ''), ['YES', 'NO', 'IN PROGRESS', 'PENDING']) ? strtoupper($savedForCase->extraction_analysis_conducted) : 'NO'),
            'extraction_notes'          => !$isCourtGranted ? $cleanExtMsg : '',
            'ongoing_extraction_status' => $rawOngExtStatus ?: ($isCourtGranted && $savedForCase && in_array(strtoupper($savedForCase->extraction_analysis_conducted ?? ''), ['YES', 'NO', 'IN PROGRESS', 'PENDING']) ? strtoupper($savedForCase->extraction_analysis_conducted) : 'NO'),
            'ongoing_extraction_notes'  => $rawOngExtMsg ?: ($isCourtGranted ? $cleanExtMsg : ''),
            'report_witness_status'     => $rawRwStatus ?: ($savedForCase && in_array(strtoupper($savedForCase->report_witness_statement_sent ?? ''), ['YES', 'NO', 'PENDING']) ? strtoupper($savedForCase->report_witness_statement_sent) : 'NO'),
            'report_witness_notes'      => $cleanRwMsg,
            'intel_shared'              => $savedForCase ? $savedForCase->intel_shared : 'NO',
            'remark'                    => $savedForCase ? $savedForCase->remark : '',
        ];
    }

    $firstCaseNo = count($cases) > 0 ? ($cases[0]->case_number ?? '') : '';
    $defaultLoc  = !empty($firstCaseNo) ? ($caseMapData[$firstCaseNo]['location'] ?? '') : '';
    $defaultDev  = !empty($firstCaseNo) ? ($caseMapData[$firstCaseNo]['devices'] ?? '') : '';
    $defaultQty  = !empty($firstCaseNo) ? ($caseMapData[$firstCaseNo]['qty'] ?? '') : '';
    $defaultDesc = !empty($firstCaseNo) ? ($caseMapData[$firstCaseNo]['description'] ?? '') : '';
    $defaultDate = !empty($firstCaseNo) ? ($caseMapData[$firstCaseNo]['date'] ?? '') : '';
    $firstSaved  = !empty($firstCaseNo) ? $savedReports->firstWhere('case_number', $firstCaseNo) : null;
    $defaultCourtGranted = $firstSaved ? $firstSaved->court_order_granted : 'NO';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forensic &amp; Investigation Status | Digital &amp; Physical Forensics Unit</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; background-color: #F8FAFC; color: #0F172A; }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    </style>
</head>
<body class="min-h-screen flex antialiased selection:bg-emerald-600 selection:text-white">

    <div class="flex h-screen w-full overflow-hidden">
        <!-- SIDEBAR PARTIAL -->
        @include('Crime_Scene_Support.partials.sidebar')

        <!-- MAIN WORKSPACE -->
        <div class="flex-1 flex flex-col min-w-0 bg-slate-50 overflow-hidden">
            
            <!-- TOP NAVBAR -->
            <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 z-20 shadow-2xs">
                <div class="flex items-center gap-3 flex-1 max-w-2xl">
                    <nav class="flex items-center gap-1.5 text-xs font-mono-code text-slate-500 overflow-x-auto whitespace-nowrap">
                        <a href="{{ url('/Digital_Forensics') }}" class="hover:text-emerald-700 font-semibold flex items-center gap-1 transition-colors">
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <span>Digital Forensics</span>
                        </a>
                        <span class="text-slate-300">/</span>
                        <span class="text-emerald-800 font-bold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">Forensic &amp; Investigation Status</span>
                    </nav>
                </div>
                
            </header>

            <!-- MAIN CONTENT AREA -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 space-y-6 custom-scrollbar">
                @include('partials.flash')

                <!-- HERO BANNER -->
                <div class="p-6 rounded-3xl text-white shadow-xl flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 border bg-black">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2.5 py-1 bg-emerald-600 text-white text-[10px] font-extrabold font-mono-code rounded-lg uppercase tracking-wider">
                                DIGITAL &amp; PHYSICAL FORENSICS UNIT
                            </span>
                        </div>
                        <h1 class="text-xl sm:text-2xl font-black font-mono-code uppercase tracking-tight text-white">
                            Forensic &amp; Investigation Status
                        </h1>
                        <p class="text-xs text-blue-100 font-medium max-w-2xl leading-relaxed">
                            Monitor hardware write-blocker extraction, chip-off memory acquisition, passcode recovery, and case investigation status for electronic exhibits across NACOC active dockets.
                        </p>
                    </div>
                </div>



                <!-- INTERACTIVE STATUS ENTRY & UPDATE FORM SECTION -->
                <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xl p-6 sm:p-8 space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-emerald-600 text-white flex items-center justify-center shadow-md shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 012.828 0L20 5.828a2 2 0 010 2.828l-8.414 8.414-3.536 1.414 1.414-3.536L16.586 3.586z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-base font-black text-slate-900 font-mono-code uppercase tracking-tight">
                                    Update Forensic Investigation Status
                                </h2>
                                <p class="text-xs text-slate-500 font-medium">Record live extraction status, hash checksum, write-blocker mode, and findings for mobile devices &amp; digital media.</p>
                            </div>
                        </div>
                        <span class="text-xs font-mono-code font-bold text-emerald-800 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-xl self-start sm:self-auto">
                            FORM REF # DF-STATUS-{{ date('Y-md') }}
                        </span>
                    </div>

                    <form id="forensicStatusForm" action="{{ url('/Digital_Forensics/forensic-status') }}" method="POST" enctype="multipart/form-data" onsubmit="handleStatusSubmit(event)" class="space-y-6">
                        @csrf

                        <!-- CURRENT/NEW CASES SECTION CONTAINER -->
                        <div id="currentCasesSectionContainer" class="p-5 sm:p-6 bg-slate-50/70 border border-slate-200/90 rounded-2xl shadow-xs space-y-5 transition-all">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-200">
                                <div class="flex items-center gap-2.5">
                                    <h3 class="text-xs sm:text-sm font-black text-slate-900 font-mono-code uppercase tracking-wider">
                                        CURRENT/NEW CASES
                                    </h3>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                                
                                <!-- Case Reference Select / Input -->
                                <div class="space-y-2">
                                    <label for="statusCaseRef" class="block text-xs font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                        Case Reference <span class="text-red-500">*</span>
                                    </label>
                                    <select id="statusCaseRef" name="case_number" onchange="onDigitalCaseRefChanged(this.value)" required class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer">
                                        <option value="">— Select Digital Case Reference —</option>
                                        @foreach($cases as $idx => $c)
                                            @php
                                                $cNo = $c->case_number ?? ($c['case_number'] ?? '');
                                                $cTitle = $c->title ?? ($c['title'] ?? 'Digital Forensic Case');
                                            @endphp
                                            @if(!empty($cNo))
                                                <option value="{{ $cNo }}" {{ $idx === 0 ? 'selected' : '' }}>{{ $cNo }} — {{ $cTitle }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Location / Post -->
                                <div class="space-y-2">
                                    <label for="statusLocation" class="block text-xs font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                        Location / Post <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="statusLocation" name="location_post" value="{{ $defaultLoc }}" readonly required placeholder="e.g. Kotoka International Airport / Aflao Border" class="w-full px-3.5 py-2.5 bg-slate-100/90 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-700 cursor-not-allowed focus:outline-none">
                                </div>

                                <!-- Date -->
                                <div class="space-y-2">
                                    <label for="statusDate" class="block text-xs font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                        Date <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" id="statusDate" name="investigation_date" value="{{ $defaultDate }}" readonly required class="w-full px-3.5 py-2.5 bg-slate-100/90 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-700 cursor-not-allowed focus:outline-none">
                                </div>

                                <!-- Devices / Exhibits Retrieved -->
                                <div class="space-y-2 md:col-span-2 lg:col-span-2">
                                    <label for="statusDevicesRetrieved" class="block text-xs font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                        Devices / Exhibits Retrieved <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="statusDevicesRetrieved" name="devices_retrieved" value="{{ $defaultDev }}" readonly required placeholder="e.g. 2x Mobile Phones, 1x Hard Drive, 3x SIM Cards" class="w-full px-3.5 py-2.5 bg-slate-100/90 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-700 cursor-not-allowed focus:outline-none">
                                </div>

                                <!-- Qty -->
                                <div class="space-y-2 md:col-span-1 lg:col-span-1">
                                    <label for="statusQty" class="block text-xs font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                        Qty <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="statusQty" name="quantity" value="{{ $defaultQty }}" readonly required placeholder="e.g. 1" class="w-full px-3.5 py-2.5 bg-slate-100/90 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-700 cursor-not-allowed focus:outline-none">
                                </div>

                            </div>

                            <!-- Brief Case Description -->
                            <div class="space-y-2">
                                <label for="briefCaseDescription" class="block text-xs font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                    Brief Case Description
                                </label>
                                <textarea id="briefCaseDescription" name="brief_case_description" rows="3" placeholder="Enter a concise summary of the case facts, suspect operations, and intelligence context..." class="w-full px-4 py-3 bg-white border border-slate-200 rounded-2xl text-xs text-slate-900 font-sans leading-relaxed focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all custom-scrollbar">{{ $defaultDesc }}</textarea>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

                                <!-- Court Order Requested? -->
                                <div class="space-y-2">
                                    <label for="courtOrderRequested" class="block text-xs font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                        Digital Court Order Requested? <span class="text-red-500">*</span>
                                    </label>
                                    <select id="courtOrderRequested" name="court_order_requested" onchange="handleCourtOrderRequestedChange(this.value)" required class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer">
                                        <option value="YES">YES</option>
                                        <option value="NO" selected>NO</option>
                                        <option value="PENDING">PENDING</option>
                                    </select>
                                </div>

                                <!-- Court Order Granted? -->
                                <div class="space-y-2">
                                    <label for="courtOrderGranted" class="block text-xs font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                        Digital Court Order Granted? <span class="text-red-500">*</span>
                                    </label>
                                    <select id="courtOrderGranted" name="court_order_granted" onchange="toggleCourtOrderFile(this.value)" required disabled class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed disabled:border-slate-200">
                                        <option value="YES">YES</option>
                                        <option value="NO" selected>NO</option>
                                        <option value="PENDING">PENDING</option>
                                    </select>

                                    <!-- File Upload Section for Court Order Granted (maintains previous column width) -->
                                    <div id="courtOrderFileWrapper" class="hidden pt-1.5">
                                        <div class="p-3 bg-emerald-50/70 border border-emerald-200/90 rounded-2xl space-y-2.5 transition-all">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[10px] font-black text-emerald-800 font-mono-code uppercase tracking-wider flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                                    Upload Court Order Documents
                                                </span>
                                                <span id="courtOrderBadge" class="hidden text-[9px] font-extrabold bg-emerald-600 text-white px-2 py-0.5 rounded-full font-mono-code">ATTACHED</span>
                                            </div>
                                            <div class="relative flex items-center justify-center border-2 border-dashed border-emerald-300 hover:border-emerald-500 bg-white rounded-xl p-2.5 transition-all cursor-pointer group">
                                                <input type="file" id="courtOrderFileInput" name="court_order_files[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" onclick="this.value = null;" oncancel="restoreFileInputFiles('court')" onchange="handleFileSelected(this, 'court')" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                                <div class="flex items-center gap-2 text-center">
                                                    <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                                    </div>
                                                    <div class="text-left min-w-0">
                                                        <p id="courtOrderFileNameDisplay" class="text-[11px] font-bold text-slate-700 group-hover:text-emerald-800 truncate max-w-[160px]">
                                                            Choose Court Order File(s)
                                                        </p>
                                                        <p class="text-[9px] text-slate-400 font-mono-code">PDF, DOCX, JPG, PNG &bull; Multiple or one-by-one</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Uploaded Files: 4 in a row horizontal grid -->
                                            <div id="courtOrderFileList" class="hidden grid grid-cols-4 gap-1.5 pt-1"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Extraction & Analysis Conducted? (CURRENT/NEW CASES) -->
                                <div class="space-y-2">
                                    <label for="extractionAnalysisConducted" class="block text-xs font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                        Extraction &amp; Analysis Conducted?
                                    </label>
                                    <div class="bg-white border border-slate-200 rounded-xl focus-within:border-emerald-600 focus-within:ring-2 focus-within:ring-emerald-600/20 transition-all overflow-hidden shadow-2xs">
                                        <div class="flex items-center justify-between px-3 py-1.5 bg-slate-100/75 border-b border-slate-200/80 gap-2">
                                            <div class="flex items-center gap-1.5 min-w-0">
                                                <span class="text-[9.5px] font-black text-slate-500 font-mono-code uppercase tracking-wider shrink-0">Status:</span>
                                                <select id="extractionAnalysisConducted" name="extraction_analysis_status" class="px-2 py-0.5 bg-white border border-slate-300 rounded-lg text-xs font-mono-code font-bold text-slate-800 focus:outline-none focus:border-emerald-600 cursor-pointer shadow-2xs">
                                                    <option value="">— Status (Optional) —</option>
                                                    <option value="NO" selected>NO</option>
                                                    <option value="YES">YES</option>
                                                    <option value="IN PROGRESS">IN PROGRESS</option>
                                                </select>
                                            </div>
                                            <span class="text-[9px] font-mono-code text-slate-400 hidden sm:inline">Select status and/or enter message</span>
                                        </div>
                                        <textarea id="extractionAnalysisNotes" name="extraction_analysis_notes" rows="3" placeholder="Message / Details (e.g. Preliminary forensic analysis completed; full extraction pending grant of court order...)" class="w-full px-3 py-2 bg-transparent border-0 text-xs text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0 resize-y custom-scrollbar font-sans leading-relaxed"></textarea>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- ONGOING CASES (EXTRACTION COURT ORDERS RECEIVED) SECTION CONTAINER -->
                        <div id="ongoingCasesSectionContainer" class="hidden p-5 sm:p-6 bg-gradient-to-b from-emerald-50/50 via-white to-white border-2 border-emerald-500/40 rounded-2xl shadow-sm space-y-5 transition-all duration-300">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-emerald-200">
                                <div class="flex items-center gap-2.5">
                                    <h3 class="text-xs sm:text-sm font-black text-emerald-950 font-mono-code uppercase tracking-wider">
                                        ONGOING CASES (EXTRACTION COURT ORDERS RECEIVED)
                                    </h3>
                                </div>

                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

                                <!-- Extraction & Analysis Conducted? (ONGOING CASES) -->
                                <div class="space-y-2">
                                    <label for="ongoingExtractionAnalysisConducted" class="block text-xs font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                        Extraction &amp; Analysis Conducted?
                                    </label>
                                    <div class="bg-white border border-slate-200 rounded-xl focus-within:border-emerald-600 focus-within:ring-2 focus-within:ring-emerald-600/20 transition-all overflow-hidden shadow-2xs">
                                        <div class="flex items-center justify-between px-3 py-1.5 bg-slate-100/75 border-b border-slate-200/80 gap-2">
                                            <div class="flex items-center gap-1.5 min-w-0">
                                                <span class="text-[9.5px] font-black text-slate-500 font-mono-code uppercase tracking-wider shrink-0">Status:</span>
                                                <select id="ongoingExtractionAnalysisConducted" name="ongoing_extraction_analysis_status" class="px-2 py-0.5 bg-white border border-slate-300 rounded-lg text-xs font-mono-code font-bold text-slate-800 focus:outline-none focus:border-emerald-600 cursor-pointer shadow-2xs">
                                                    <option value="">— Status (Optional) —</option>
                                                    <option value="NO" selected>NO</option>
                                                    <option value="YES">YES</option>
                                                    <option value="IN PROGRESS">IN PROGRESS</option>
                                                </select>
                                            </div>
                                            <span class="text-[9px] font-mono-code text-slate-400 hidden sm:inline">Select status and/or enter message</span>
                                        </div>
                                        <textarea id="ongoingExtractionAnalysisNotes" name="ongoing_extraction_analysis_notes" rows="3" placeholder="Message / Details (e.g. Full extraction and forensic analysis completed under granted court order...)" class="w-full px-3 py-2 bg-transparent border-0 text-xs text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0 resize-y custom-scrollbar font-sans leading-relaxed"></textarea>
                                    </div>
                                </div>

                                <!-- Report, Witness Statement & Digital Evidence Sent to Investigations -->
                                <div class="space-y-2">
                                    <label for="reportWitnessSent" class="block text-xs font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                        Report, Witness Statement &amp; Digital Evidence Sent to Investigations: <span class="text-red-500">*</span>
                                    </label>
                                    <div class="bg-white border border-slate-200 rounded-xl focus-within:border-emerald-600 focus-within:ring-2 focus-within:ring-emerald-600/20 transition-all overflow-hidden shadow-2xs">
                                        <div class="flex items-center justify-between px-3 py-1.5 bg-slate-100/75 border-b border-slate-200/80 gap-2">
                                            <div class="flex items-center gap-1.5 min-w-0">
                                                <span class="text-[9.5px] font-black text-slate-500 font-mono-code uppercase tracking-wider shrink-0">Status:</span>
                                                <select id="reportWitnessSent" name="report_witness_statement_status" onchange="toggleReportWitnessFile(this.value)" class="px-2 py-0.5 bg-white border border-slate-300 rounded-lg text-xs font-mono-code font-bold text-slate-800 focus:outline-none focus:border-emerald-600 cursor-pointer shadow-2xs">
                                                    <option value="">— Status (Optional) —</option>
                                                    <option value="NO" selected>NO</option>
                                                    <option value="YES">YES</option>
                                                    <option value="PENDING">PENDING</option>
                                                </select>
                                            </div>
                                            <span class="text-[9px] font-mono-code text-slate-400 hidden sm:inline">Select status and/or enter message</span>
                                        </div>
                                        <textarea id="reportWitnessNotes" name="report_witness_statement_notes" rows="3" placeholder="Message / Details (e.g. Full forensic analytical report and witness statements transmitted to Investigations Directorate...)" class="w-full px-3 py-2 bg-transparent border-0 text-xs text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0 resize-y custom-scrollbar font-sans leading-relaxed"></textarea>
                                    </div>

                                    <!-- File Upload Section for Report & Witness Statement Sent (maintains previous column width) -->
                                    <div id="reportWitnessFileWrapper" class="hidden pt-1.5">
                                        <div class="p-3 bg-emerald-50/70 border border-emerald-200/90 rounded-2xl space-y-2.5 transition-all">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[10px] font-black text-emerald-800 font-mono-code uppercase tracking-wider flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                    Upload Report &amp; Statement File(s)
                                                </span>
                                                <span id="reportWitnessBadge" class="hidden text-[9px] font-extrabold bg-emerald-600 text-white px-2 py-0.5 rounded-full font-mono-code">ATTACHED</span>
                                            </div>
                                            <div class="relative flex items-center justify-center border-2 border-dashed border-emerald-300 hover:border-emerald-500 bg-white rounded-xl p-2.5 transition-all cursor-pointer group">
                                                <input type="file" id="reportWitnessFileInput" name="report_witness_statement_files[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" onclick="this.value = null;" oncancel="restoreFileInputFiles('witness')" onchange="handleFileSelected(this, 'witness')" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                                <div class="flex items-center gap-2 text-center">
                                                    <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                                    </div>
                                                    <div class="text-left min-w-0">
                                                        <p id="reportWitnessFileNameDisplay" class="text-[11px] font-bold text-slate-700 group-hover:text-emerald-800 truncate max-w-[160px]">
                                                            Choose Statement / Report File(s)
                                                        </p>
                                                        <p class="text-[9px] text-slate-400 font-mono-code">PDF, DOCX, JPG, PNG &bull; Multiple or one-by-one</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Uploaded Files: 4 in a row horizontal grid -->
                                            <div id="reportWitnessFileList" class="hidden grid grid-cols-4 gap-1.5 pt-1"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Intelligence Shared to Intel Department for Action? -->
                                <div class="space-y-2">
                                    <label for="intelShared" class="block text-xs font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                        Intelligence Shared to Intel Department for Action? <span class="text-red-500">*</span>
                                    </label>
                                    <select id="intelShared" name="intel_shared" class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer">
                                        <option value="YES">YES</option>
                                        <option value="NO" selected>NO</option>
                                        <option value="PENDING">PENDING</option>
                                    </select>
                                </div>

                            </div>

                            <!-- Remark -->
                            <div class="space-y-2">
                                <label for="statusRemark" class="block text-xs font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                    Remark
                                </label>
                                <textarea id="statusRemark" name="remark" rows="3" placeholder="Enter additional remarks or status details regarding ongoing case analysis..." class="w-full px-4 py-3 bg-white border border-slate-200 rounded-2xl text-xs text-slate-900 font-sans leading-relaxed focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all custom-scrollbar"></textarea>
                            </div>
                        </div>

                        <!-- Submit Bar -->
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-3 border-t border-slate-100">
                            <div id="statusAlertMsg" class="hidden items-center gap-2 text-xs font-mono-code font-bold text-emerald-700">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>✔ Forensic &amp; Investigation Status updated successfully!</span>
                            </div>
                            <div class="flex items-center gap-3 justify-end ml-auto">
                                <button type="reset" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-colors cursor-pointer">
                                    Reset Form
                                </button>
                                <button type="submit" id="saveStatusBtn" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md transition-all flex items-center gap-2 cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>Save &amp; Log Investigation Status</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- LIVE FORENSIC INVESTIGATION DIRECTORY TABLE -->
                <div id="investigationDirectorySection" class="bg-white rounded-3xl border border-slate-200/90 shadow-xl overflow-hidden space-y-4 p-5 sm:p-6 scroll-mt-6">
                    <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-black text-slate-900 font-mono-code uppercase tracking-tight">
                                    Digital Forensic &amp; Investigation Directory
                                </h3>
                                <span id="directoryRowCountBadge" class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800 font-mono-code">
                                    Total: {{ count($savedReports) + count($cases->filter(fn($c) => !$savedReports->contains('case_number', $c->case_number ?? ($c['case_number'] ?? '')))) }}
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 font-sans mt-0.5">Real-time case tracking, extraction status &amp; dossier archives</p>
                        </div>

                        <!-- SEARCH INPUT -->
                        <div class="relative w-full xl:max-w-xs">
                            <input type="text" id="directorySearchInput" oninput="applyDirectoryFilters()" onkeyup="applyDirectoryFilters()" placeholder="Filter by Case, Location, or Status..." class="w-full pl-9 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 transition-colors shadow-2xs">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 pointer-events-none">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </span>
                        </div>
                    </div>

                    <!-- DATE & STATUS FILTER TOOLBAR (PRESETS: ALL, WEEKLY, MONTHLY, ANNUALLY + CUSTOM RANGE & STATUS) -->
                    <div class="p-3 bg-slate-50/80 border border-slate-200 rounded-2xl flex flex-wrap items-center justify-between gap-3 text-xs">
                        <!-- PRESET PILLS -->
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 font-mono-code mr-1 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span>Period:</span>
                            </span>
                            <button type="button" onclick="setDateFilterPreset('all')" id="btnPresetAll" class="px-3 py-1 rounded-lg text-xs font-mono-code font-bold transition-all bg-emerald-600 text-white shadow-xs cursor-pointer">
                                All Time
                            </button>
                            <button type="button" onclick="setDateFilterPreset('weekly')" id="btnPresetWeekly" class="px-3 py-1 rounded-lg text-xs font-mono-code font-bold transition-all bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs cursor-pointer">
                                Weekly
                            </button>
                            <button type="button" onclick="setDateFilterPreset('monthly')" id="btnPresetMonthly" class="px-3 py-1 rounded-lg text-xs font-mono-code font-bold transition-all bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs cursor-pointer">
                                Monthly
                            </button>
                            <button type="button" onclick="setDateFilterPreset('annually')" id="btnPresetAnnually" class="px-3 py-1 rounded-lg text-xs font-mono-code font-bold transition-all bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs cursor-pointer">
                                Annually
                            </button>
                        </div>

                        <!-- STATUS FILTERS & CUSTOM DATE RANGE INPUTS -->
                        <div class="flex items-center gap-2 flex-wrap">
                            <!-- Court Order Status Dropdown Filter -->
                            <div class="flex items-center gap-1">
                                <select id="directoryCourtFilter" onchange="applyDirectoryFilters()" class="px-2.5 py-1 bg-white border border-slate-200 rounded-lg text-xs font-mono-code text-slate-800 focus:outline-none focus:border-emerald-600 shadow-2xs cursor-pointer">
                                    <option value="">Court Order: All</option>
                                    <option value="YES">Granted (YES)</option>
                                    <option value="NO">Not Granted (NO)</option>
                                </select>
                            </div>

                            <!-- Extraction Status Dropdown Filter -->
                            <div class="flex items-center gap-1">
                                <select id="directoryExtractionFilter" onchange="applyDirectoryFilters()" class="px-2.5 py-1 bg-white border border-slate-200 rounded-lg text-xs font-mono-code text-slate-800 focus:outline-none focus:border-emerald-600 shadow-2xs cursor-pointer">
                                    <option value="">Extraction: All</option>
                                    <option value="YES">Completed (YES)</option>
                                    <option value="IN PROGRESS">In Progress</option>
                                    <option value="NO">Pending / NO</option>
                                </select>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <span class="text-[10px] font-bold text-slate-500 font-mono-code uppercase">From:</span>
                                <input type="date" id="dateFilterFrom" oninput="onCustomDateChange()" onchange="onCustomDateChange()" class="px-2 py-1 bg-white border border-slate-200 rounded-lg text-xs font-mono-code text-slate-800 focus:outline-none focus:border-emerald-600 shadow-2xs cursor-pointer">
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-[10px] font-bold text-slate-500 font-mono-code uppercase">To:</span>
                                <input type="date" id="dateFilterTo" oninput="onCustomDateChange()" onchange="onCustomDateChange()" class="px-2 py-1 bg-white border border-slate-200 rounded-lg text-xs font-mono-code text-slate-800 focus:outline-none focus:border-emerald-600 shadow-2xs cursor-pointer">
                            </div>
                            <button type="button" onclick="generateDirectoryFilter()" id="btnGenerateFilter" title="Generate Directory with Selected Filter" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white border border-emerald-700 rounded-lg text-xs font-mono-code font-bold transition-all flex items-center gap-1.5 shadow-2xs cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                <span>Generate</span>
                            </button>
                            <button type="button" onclick="resetDateFilters()" title="Reset All Filters" class="px-2.5 py-1 bg-white hover:bg-rose-50 text-slate-600 hover:text-rose-700 border border-slate-200 hover:border-rose-200 rounded-lg text-xs font-mono-code font-bold transition-colors flex items-center gap-1 shadow-2xs cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span>Reset</span>
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-xs text-left border-collapse" id="forensicDirectoryTable">
                            <thead>
                                <tr class="bg-slate-100 text-slate-700 font-mono-code uppercase text-[10px] tracking-wider border-b border-slate-200">
                                    <th class="p-3">Case Ref</th>
                                    <th class="p-3">Logged Date</th>
                                    <th class="p-3">Court Order</th>
                                    <th class="p-3">Extraction &amp; Analysis</th>
                                    <th class="p-3">Report, Witness Statement &amp; Digital Evidence Sent to Investigations</th>
                                    <th class="p-3">Intel Shared</th>
                                    <th class="p-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-mono-code" id="forensicTableBody">
                                @foreach($savedReports as $rep)
                                    @php
                                        $coFiles = $rep->court_order_files;
                                        $rwFiles = $rep->report_witness_statement_files;
                                        $rawRepDate = !empty($rep->investigation_date) ? (string)$rep->investigation_date : ($rep->created_at ? $rep->created_at->format('Y-m-d') : date('Y-m-d'));
                                        $repTime = strtotime($rawRepDate);
                                        $repDateIso = ($repTime && $repTime > 0) ? date('Y-m-d', $repTime) : date('Y-m-d');
                                        $repDateDisplay = ($repTime && $repTime > 0) ? date('d M Y', $repTime) : date('d M Y');
                                        $repLoc = $rep->location_post ?? ($caseMapData[$rep->case_number]['location'] ?? 'Kotoka International Airport (Cargo Wing)');
                                        $repCourt = strtoupper(trim($rep->court_order_granted ?? 'NO'));
                                    @endphp
                                    @php
                                        $extRaw = trim($rep->extraction_analysis_message ?: ($rep->extraction_analysis_conducted ?: 'NO'));
                                        $extBadge = null;
                                        $extNote = null;
                                        if (preg_match('/^(YES|NO|IN PROGRESS|PENDING)\s*[-–—:]\s*(.+)$/is', $extRaw, $m)) {
                                            $extBadge = strtoupper($m[1]);
                                            $candidateNote = trim($m[2]);
                                            if (strtoupper($candidateNote) !== $extBadge && !in_array(strtoupper($candidateNote), ['N/A', 'NONE', 'NO', 'YES'])) {
                                                $extNote = $candidateNote;
                                            }
                                        } elseif (in_array(strtoupper($extRaw), ['YES', 'NO', 'IN PROGRESS', 'PENDING'])) {
                                            $extBadge = strtoupper($extRaw);
                                        } else {
                                            $extNote = $extRaw;
                                        }
                                        $extBadgeClass = $extBadge === 'YES' ? 'bg-emerald-100 text-emerald-800' : ($extBadge === 'NO' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800');
                                        $repExtNormalized = strtoupper(trim($extBadge ?: 'NO'));
                                    @endphp
                                    <tr class="hover:bg-emerald-50/40 transition-colors bg-emerald-50/20 border-l-4 border-emerald-600 directory-row" 
                                        data-date="{{ $repDateIso }}"
                                        data-case="{{ strtolower($rep->case_number) }}"
                                        data-caseref="{{ $rep->case_number }}"
                                        data-code="{{ strtolower($rep->dpf_code ?? '') }}"
                                        data-location="{{ strtolower($repLoc) }}"
                                        data-court="{{ $repCourt }}"
                                        data-extraction="{{ $repExtNormalized }}"
                                        data-intel="{{ strtoupper(trim($rep->intel_shared ?? 'NO')) }}">
                                        <td class="p-3 font-extrabold text-emerald-950">
                                            <div>{{ $rep->case_number }}</div>
                                            <div class="flex items-center gap-1 mt-0.5">
                                                <span class="text-[9px] font-bold text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded">{{ $rep->dpf_code }}</span>
                                                <span class="text-[9px] text-slate-400 font-sans font-normal truncate max-w-[130px]" title="{{ $repLoc }}">{{ $repLoc }}</span>
                                            </div>
                                        </td>
                                        <td class="p-3 whitespace-nowrap">
                                            <div class="font-bold text-[11px] text-slate-800">{{ $repDateDisplay }}</div>
                                            <span class="text-[9px] text-slate-400 font-sans block">{{ $rep->created_at ? $rep->created_at->format('h:i A') : 'Saved Status' }}</span>
                                        </td>
                                        <td class="p-3">
                                            <span class="px-2 py-0.5 {{ $rep->court_order_granted === 'YES' ? 'bg-emerald-100 text-emerald-800' : ($rep->court_order_granted === 'NO' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }} text-[10px] font-bold rounded">
                                                {{ $rep->court_order_granted }}
                                            </span>
                                            @if(!empty($coFiles))
                                                <div class="mt-1 flex flex-wrap items-center gap-1 max-w-[200px]">
                                                    @foreach($coFiles as $idx => $fPath)
                                                        <a href="{{ asset($fPath) }}" target="_blank" class="px-1.5 py-0.5 bg-emerald-100 hover:bg-emerald-200 text-emerald-900 rounded text-[9px] font-bold flex items-center gap-0.5 transition-colors">
                                                            <svg class="w-2.5 h-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                                            <span>Order {{ count($coFiles) > 1 ? '#' . ($idx + 1) : '' }}</span>
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        <td class="p-3">
                                            @if($extBadge)
                                                <span class="px-2 py-0.5 {{ $extBadgeClass }} text-[10px] font-bold rounded">
                                                    {{ $extBadge }}
                                                </span>
                                            @endif
                                            @if($extNote)
                                                <p class="{{ $extBadge ? 'mt-1' : '' }} text-[10px] text-slate-700 font-sans leading-tight max-w-[220px] line-clamp-2" title="{{ $extRaw }}">
                                                    {{ $extNote }}
                                                </p>
                                            @endif
                                        </td>
                                        <td class="p-3">
                                            @php
                                                $rwRaw = trim($rep->report_witness_statement_message ?: ($rep->report_witness_statement_sent ?: 'NO'));
                                                $rwBadge = null;
                                                $rwNote = null;
                                                if (preg_match('/^(YES|NO|IN PROGRESS|PENDING)\s*[-–—:]\s*(.+)$/is', $rwRaw, $mRw)) {
                                                    $rwBadge = strtoupper($mRw[1]);
                                                    $candidateRwNote = trim($mRw[2]);
                                                    if (strtoupper($candidateRwNote) !== $rwBadge && !in_array(strtoupper($candidateRwNote), ['N/A', 'NONE', 'NO', 'YES'])) {
                                                        $rwNote = $candidateRwNote;
                                                    }
                                                } elseif (in_array(strtoupper($rwRaw), ['YES', 'NO', 'IN PROGRESS', 'PENDING'])) {
                                                    $rwBadge = strtoupper($rwRaw);
                                                } else {
                                                    $rwNote = $rwRaw;
                                                }
                                                $rwBadgeClass = $rwBadge === 'YES' ? 'bg-emerald-100 text-emerald-800' : ($rwBadge === 'NO' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800');
                                            @endphp
                                            @if($rwBadge)
                                                <span class="px-2 py-0.5 {{ $rwBadgeClass }} text-[10px] font-bold rounded">
                                                    {{ $rwBadge }}
                                                </span>
                                            @endif
                                            @if($rwNote)
                                                <p class="{{ $rwBadge ? 'mt-1' : '' }} text-[10px] text-slate-700 font-sans leading-tight max-w-[220px] line-clamp-2" title="{{ $rwRaw }}">
                                                    {{ $rwNote }}
                                                </p>
                                            @endif
                                            @if(!empty($rwFiles))
                                                <div class="mt-1 flex flex-wrap items-center gap-1 max-w-[200px]">
                                                    @foreach($rwFiles as $idx => $fPath)
                                                        <a href="{{ asset($fPath) }}" target="_blank" class="px-1.5 py-0.5 bg-emerald-100 hover:bg-emerald-200 text-emerald-900 rounded text-[9px] font-bold flex items-center gap-0.5 transition-colors">
                                                            <svg class="w-2.5 h-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                            <span>Evidence / Stmt {{ count($rwFiles) > 1 ? '#' . ($idx + 1) : '' }}</span>
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        <td class="p-3">
                                            <span class="px-2 py-0.5 {{ $rep->intel_shared === 'YES' ? 'bg-emerald-100 text-emerald-800' : ($rep->intel_shared === 'NO' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }} text-[10px] font-bold rounded">
                                                {{ $rep->intel_shared }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-right space-x-1 whitespace-nowrap">
                                            <a href="{{ url('/Digital_Forensics/dpf-report-pdf/' . ($rep->dpf_code ?? $rep->case_number)) }}" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[10px] font-bold transition-colors no-underline inline-block">
                                                Preview
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach

                                @foreach($cases as $idx => $cItem)
                                    @php
                                        $cRef = $cItem->case_number ?? ($cItem['case_number'] ?? ('NCC-2026-DF' . sprintf('%02d', $idx + 1)));
                                        if ($savedReports->contains('case_number', $cRef)) continue;
                                        $cLoc = $caseMapData[$cRef]['location'] ?? 'Kotoka International Airport (Cargo Wing)';
                                        $cDev = $caseMapData[$cRef]['devices'] ?? 'Electronic Devices / Mobile Exhibits';
                                        $cRawDate = $caseMapData[$cRef]['date'] ?? ($cItem->created_at ? $cItem->created_at->format('Y-m-d') : date('Y-m-d'));
                                        $cTime = strtotime((string)$cRawDate);
                                        $cDateIso = ($cTime && $cTime > 0) ? date('Y-m-d', $cTime) : date('Y-m-d');
                                        $cDateDisplay = ($cTime && $cTime > 0) ? date('d M Y', $cTime) : date('d M Y');
                                        $cCourt = strtoupper(trim($caseMapData[$cRef]['court_order_granted'] ?? 'NO'));
                                        $cExt = strtoupper(trim($caseMapData[$cRef]['extraction_status'] ?? 'NO'));
                                        $cRw = strtoupper(trim($caseMapData[$cRef]['report_witness_status'] ?? 'NO'));
                                        $cIntel = strtoupper(trim($caseMapData[$cRef]['intel_shared'] ?? 'NO'));
                                    @endphp
                                    <tr class="hover:bg-emerald-50/40 transition-colors directory-row" 
                                        data-date="{{ $cDateIso }}"
                                        data-case="{{ strtolower($cRef) }}"
                                        data-code=""
                                        data-location="{{ strtolower($cLoc) }}"
                                        data-court="{{ $cCourt }}"
                                        data-extraction="{{ $cExt }}"
                                        data-intel="{{ $cIntel }}">
                                        <td class="p-3 font-extrabold text-slate-900">
                                            <div>{{ $cRef }}</div>
                                            <div class="text-[9px] text-slate-400 font-sans font-normal truncate max-w-[140px]" title="{{ $cLoc }}">{{ $cLoc }}</div>
                                        </td>
                                        <td class="p-3 whitespace-nowrap">
                                            <div class="font-bold text-[11px] text-slate-800">{{ $cDateDisplay }}</div>
                                            <span class="text-[9px] text-slate-400 font-sans block">Registry Intake</span>
                                        </td>
                                        <td class="p-3">
                                            <span class="px-2 py-0.5 {{ $cCourt === 'YES' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }} text-[10px] font-bold rounded">
                                                {{ $cCourt === 'YES' ? 'Granted' : 'NO' }}
                                            </span>
                                        </td>
                                        <td class="p-3">
                                            <span class="px-2 py-0.5 {{ $cExt === 'YES' ? 'bg-emerald-100 text-emerald-800' : ($cExt === 'IN PROGRESS' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }} text-[10px] font-bold rounded">
                                                {{ $cExt }}
                                            </span>
                                        </td>
                                        <td class="p-3">
                                            <span class="px-2 py-0.5 {{ $cRw === 'YES' ? 'bg-emerald-100 text-emerald-800' : ($cRw === 'PENDING' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }} text-[10px] font-bold rounded">
                                                {{ $cRw }}
                                            </span>
                                        </td>
                                        <td class="p-3">
                                            <span class="px-2 py-0.5 {{ $cIntel === 'YES' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }} text-[10px] font-bold rounded">
                                                {{ $cIntel }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-right">
                                            <a href="{{ url('/Digital_Forensics/dpf-report-pdf/' . $cRef) }}" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[10px] font-bold transition-colors no-underline inline-block">
                                                Preview
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach

                                <tr id="directoryNoRecordsRow" class="hidden">
                                    <td colspan="7" class="p-8 text-center text-slate-400 font-sans">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="w-8 h-8 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <p class="text-xs font-bold text-slate-600 mb-0.5">No Investigation Records Found</p>
                                            <p class="text-[11px] text-slate-400">Try adjusting your date range filter or search keywords.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script>
        const caseMapData = @json($caseMapData);

        // Persistent accumulation pools for uploading files one after the other
        let courtOrderDataTransfer = new DataTransfer();
        let reportWitnessDataTransfer = new DataTransfer();

        function handleCourtOrderRequestedChange(val) {
            const coGranted = document.getElementById('courtOrderGranted');
            if (!coGranted) return;

            const isNo = (val || '').toUpperCase() === 'NO';
            if (isNo) {
                coGranted.value = 'NO';
                coGranted.disabled = true;
                coGranted.title = 'Digital Court Order Granted is disabled when Digital Court Order Requested is NO';
                toggleCourtOrderFile('NO');
            } else {
                coGranted.disabled = false;
                coGranted.title = '';
            }
        }

        function toggleCourtOrderFile(val) {
            const wrapper = document.getElementById('courtOrderFileWrapper');
            const ongoingContainer = document.getElementById('ongoingCasesSectionContainer');

            if (val === 'YES') {
                if (wrapper) wrapper.classList.remove('hidden');
                if (ongoingContainer) {
                    ongoingContainer.classList.remove('hidden');
                }
            } else {
                if (wrapper) wrapper.classList.add('hidden');
                if (ongoingContainer) {
                    ongoingContainer.classList.add('hidden');
                }
                courtOrderDataTransfer = new DataTransfer();
                const fileInput = document.getElementById('courtOrderFileInput');
                if (fileInput) fileInput.value = '';
                renderFileList('court');
            }
        }

        function toggleReportWitnessFile(val) {
            const wrapper = document.getElementById('reportWitnessFileWrapper');
            if (!wrapper) return;
            if (val === 'YES') {
                wrapper.classList.remove('hidden');
            } else {
                wrapper.classList.add('hidden');
                reportWitnessDataTransfer = new DataTransfer();
                const fileInput = document.getElementById('reportWitnessFileInput');
                if (fileInput) fileInput.value = '';
                renderFileList('witness');
            }
        }

        function restoreFileInputFiles(type) {
            const isCourt = type === 'court';
            const pool = isCourt ? courtOrderDataTransfer : reportWitnessDataTransfer;
            const input = document.getElementById(isCourt ? 'courtOrderFileInput' : 'reportWitnessFileInput');
            if (input) {
                input.files = pool.files;
            }
        }

        function handleFileSelected(input, type) {
            const isCourt = type === 'court';
            const pool = isCourt ? courtOrderDataTransfer : reportWitnessDataTransfer;

            if (input && input.files && input.files.length > 0) {
                // Add new files into accumulation pool without dropping previous files
                Array.from(input.files).forEach(file => {
                    const alreadyExists = Array.from(pool.files).some(f => 
                        f.name === file.name && f.size === file.size && f.lastModified === file.lastModified
                    );
                    if (!alreadyExists) {
                        pool.items.add(file);
                    }
                });
                // Re-sync pool back to the input element
                input.files = pool.files;
            }

            renderFileList(type);
        }

        function removeFileFromPool(type, index) {
            const isCourt = type === 'court';
            const oldPool = isCourt ? courtOrderDataTransfer : reportWitnessDataTransfer;
            const input = document.getElementById(isCourt ? 'courtOrderFileInput' : 'reportWitnessFileInput');

            const newPool = new DataTransfer();
            Array.from(oldPool.files).forEach((file, idx) => {
                if (idx !== index) {
                    newPool.items.add(file);
                }
            });

            if (isCourt) {
                courtOrderDataTransfer = newPool;
                if (input) input.files = courtOrderDataTransfer.files;
            } else {
                reportWitnessDataTransfer = newPool;
                if (input) input.files = reportWitnessDataTransfer.files;
            }

            renderFileList(type);
        }

        function renderFileList(type) {
            const isCourt = type === 'court';
            const pool = isCourt ? courtOrderDataTransfer : reportWitnessDataTransfer;
            const display = document.getElementById(isCourt ? 'courtOrderFileNameDisplay' : 'reportWitnessFileNameDisplay');
            const badge = document.getElementById(isCourt ? 'courtOrderBadge' : 'reportWitnessBadge');
            const fileList = document.getElementById(isCourt ? 'courtOrderFileList' : 'reportWitnessFileList');

            if (!fileList) return;

            fileList.innerHTML = '';
            const files = Array.from(pool.files);
            const count = files.length;

            if (count > 0) {
                if (display) {
                    display.innerText = count === 1 
                        ? '✔ ' + files[0].name + ' (Click to add more)'
                        : '✔ ' + count + ' Files Attached (Click to add more)';
                }
                if (badge) {
                    badge.innerText = count === 1 ? '1 FILE ATTACHED' : count + ' FILES ATTACHED';
                    badge.classList.remove('hidden');
                }

                fileList.classList.remove('hidden');

                files.forEach((file, idx) => {
                    const ext = (file.name.split('.').pop() || 'FILE').toUpperCase().slice(0, 4);
                    const sizeStr = file.size > 1048576 
                        ? (file.size / 1048576).toFixed(1) + ' MB' 
                        : (file.size / 1024).toFixed(0) + ' KB';

                    let extBg = 'bg-emerald-700';
                    if (ext === 'PDF') extBg = 'bg-rose-600';
                    else if (['JPG', 'JPEG', 'PNG', 'WEBP'].includes(ext)) extBg = 'bg-sky-600';
                    else if (['DOC', 'DOCX'].includes(ext)) extBg = 'bg-indigo-600';

                    const card = document.createElement('div');
                    card.className = 'relative bg-white border border-emerald-200/90 hover:border-emerald-500 rounded-lg p-1.5 flex flex-col justify-between shadow-2xs hover:shadow-xs transition-all group min-w-0';
                    card.innerHTML = `
                        <div class="flex items-center justify-between gap-1 w-full mb-1">
                            <span class="w-5 h-5 rounded ${extBg} text-white flex items-center justify-center font-mono-code text-[8px] font-black shrink-0">
                                ${ext}
                            </span>
                            <button type="button" onclick="removeFileFromPool('${type}', ${idx})" class="w-4 h-4 rounded text-slate-400 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition-colors shrink-0 cursor-pointer" title="Remove this file">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <div class="min-w-0 w-full text-left">
                            <p class="text-[9.5px] font-bold text-slate-800 truncate" title="${file.name}">
                                ${file.name}
                            </p>
                            <p class="text-[8px] font-mono-code text-slate-400 truncate">
                                ${sizeStr}
                            </p>
                        </div>
                    `;
                    fileList.appendChild(card);
                });
            } else {
                if (display) {
                    display.innerText = isCourt ? 'Choose Court Order File(s)' : 'Choose Statement / Report File(s)';
                }
                if (badge) {
                    badge.classList.add('hidden');
                }
                fileList.classList.add('hidden');
            }
        }

        function onDigitalCaseRefChanged(caseNo) {
            if (!caseNo || !caseMapData[caseNo]) return;
            const data = caseMapData[caseNo];
            const locInput = document.getElementById('statusLocation');
            const devInput = document.getElementById('statusDevicesRetrieved');
            const dateInput = document.getElementById('statusDate');
            const qtyInput = document.getElementById('statusQty');
            const descInput = document.getElementById('briefCaseDescription');

            if (locInput && data.location) {
                locInput.value = data.location;
            }
            if (devInput && data.devices) {
                devInput.value = data.devices;
            }
            if (qtyInput && data.qty) {
                qtyInput.value = data.qty;
            }
            if (dateInput && data.date) {
                dateInput.value = data.date;
            }
            if (descInput && data.description) {
                descInput.value = data.description;
            }

            const reqVal = data.court_order_requested || 'NO';
            const coReq = document.getElementById('courtOrderRequested');
            if (coReq) coReq.value = reqVal;

            handleCourtOrderRequestedChange(reqVal);

            const coGranted = document.getElementById('courtOrderGranted');
            if (reqVal !== 'NO') {
                if (coGranted) {
                    if (data.court_order_granted) {
                        coGranted.value = data.court_order_granted;
                    }
                    toggleCourtOrderFile(coGranted.value);
                }
            } else {
                if (coGranted) {
                    coGranted.value = 'NO';
                    toggleCourtOrderFile('NO');
                }
            }
            if (data.extraction_status !== undefined) {
                const curStatus = document.getElementById('extractionAnalysisConducted');
                if (curStatus) curStatus.value = data.extraction_status || 'NO';
            }
            if (data.ongoing_extraction_status !== undefined) {
                const ongStatus = document.getElementById('ongoingExtractionAnalysisConducted');
                if (ongStatus) ongStatus.value = data.ongoing_extraction_status || 'NO';
            }
            if (data.extraction_notes !== undefined) {
                const curNotes = document.getElementById('extractionAnalysisNotes');
                if (curNotes) curNotes.value = data.extraction_notes || '';
            }
            if (data.ongoing_extraction_notes !== undefined) {
                const ongNotes = document.getElementById('ongoingExtractionAnalysisNotes');
                if (ongNotes) ongNotes.value = data.ongoing_extraction_notes || '';
            }
            if (data.report_witness_status !== undefined) {
                const rwStatus = document.getElementById('reportWitnessSent');
                if (rwStatus) {
                    rwStatus.value = data.report_witness_status || 'NO';
                    toggleReportWitnessFile(rwStatus.value);
                }
            }
            if (data.report_witness_notes !== undefined) {
                const rwNotes = document.getElementById('reportWitnessNotes');
                if (rwNotes) rwNotes.value = data.report_witness_notes;
            }
            if (data.intel_shared !== undefined) {
                const intel = document.getElementById('intelShared');
                if (intel) intel.value = data.intel_shared || 'NO';
            }
            if (data.remark !== undefined) {
                const rem = document.getElementById('statusRemark');
                if (rem) rem.value = data.remark;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const selectEl = document.getElementById('statusCaseRef');
            if (selectEl && selectEl.value) {
                onDigitalCaseRefChanged(selectEl.value);
            } else {
                const coReqEl = document.getElementById('courtOrderRequested');
                handleCourtOrderRequestedChange(coReqEl ? coReqEl.value : 'NO');
                const courtGrantedEl = document.getElementById('courtOrderGranted');
                if (courtGrantedEl) {
                    toggleCourtOrderFile(courtGrantedEl.value);
                }
            }

            const reportWitnessEl = document.getElementById('reportWitnessSent');
            if (reportWitnessEl) {
                toggleReportWitnessFile(reportWitnessEl.value);
            }

            const form = document.getElementById('forensicStatusForm');
            if (form) {
                form.addEventListener('reset', function() {
                    setTimeout(() => {
                        const coReqEl = document.getElementById('courtOrderRequested');
                        handleCourtOrderRequestedChange(coReqEl ? coReqEl.value : 'NO');
                        const courtGrantedEl = document.getElementById('courtOrderGranted');
                        if (courtGrantedEl) toggleCourtOrderFile(courtGrantedEl.value);
                        if (reportWitnessEl) toggleReportWitnessFile(reportWitnessEl.value);
                    }, 50);
                });
            }

            applyDirectoryFilters();
        });

        function handleStatusSubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('saveStatusBtn');
            const alertMsg = document.getElementById('statusAlertMsg');
            const form = document.getElementById('forensicStatusForm');
            const formData = new FormData(form);

            // Ensure disabled or present select values are captured accurately
            const courtGrantedEl = document.getElementById('courtOrderGranted');
            if (courtGrantedEl) {
                formData.set('court_order_granted', courtGrantedEl.value || 'NO');
            }
            const courtReqEl = document.getElementById('courtOrderRequested');
            if (courtReqEl) {
                formData.set('court_order_requested', courtReqEl.value || 'NO');
            }

            // Compute combined extraction status & message
            const courtGranted = (formData.get('court_order_granted') || '').trim();
            let extStatus = '';
            let extNotes = '';

            if (courtGranted === 'YES') {
                const ongStatusEl = document.getElementById('ongoingExtractionAnalysisConducted');
                const ongNotesEl = document.getElementById('ongoingExtractionAnalysisNotes');
                extStatus = ongStatusEl ? ongStatusEl.value.trim() : '';
                extNotes = ongNotesEl ? ongNotesEl.value.trim() : '';
            }
            if (!extStatus) {
                const curStatusEl = document.getElementById('extractionAnalysisConducted');
                extStatus = curStatusEl ? curStatusEl.value.trim() : (formData.get('extraction_analysis_status') || formData.get('ongoing_extraction_analysis_status') || '').trim();
            }
            if (!extNotes) {
                const curNotesEl = document.getElementById('extractionAnalysisNotes');
                extNotes = curNotesEl ? curNotesEl.value.trim() : (formData.get('extraction_analysis_notes') || formData.get('ongoing_extraction_analysis_notes') || '').trim();
            }

            let cleanExtNotes = extNotes;
            if (extStatus) {
                const prefixRegex = new RegExp('^' + extStatus + '\\s*[-–—:]\\s*', 'i');
                cleanExtNotes = cleanExtNotes.replace(prefixRegex, '').trim();
                if (cleanExtNotes.toUpperCase() === extStatus.toUpperCase()) {
                    cleanExtNotes = '';
                }
            }
            if (/^(YES|NO|IN PROGRESS|PENDING)\s*[-–—:]\s*\1$/i.test(cleanExtNotes) || ['N/A', 'NONE', 'NO', 'YES'].includes(cleanExtNotes.toUpperCase())) {
                cleanExtNotes = '';
            }

            let combinedExt = '';
            if (extStatus && cleanExtNotes) {
                combinedExt = extStatus + ' - ' + cleanExtNotes;
            } else if (cleanExtNotes) {
                combinedExt = cleanExtNotes;
            } else if (extStatus) {
                combinedExt = extStatus;
            } else {
                combinedExt = 'NO';
            }
            formData.set('extraction_analysis_status', extStatus);
            formData.set('extraction_analysis_notes', cleanExtNotes);
            formData.set('extraction_analysis_conducted', combinedExt);
            formData.set('extraction_analysis_message', combinedExt);

            // Compute combined report witness status & message
            const rwStatus = (formData.get('report_witness_statement_status') || '').trim();
            const rwNotes = (formData.get('report_witness_statement_notes') || '').trim();
            let cleanRwNotes = rwNotes;
            if (rwStatus) {
                const prefixRegex = new RegExp('^' + rwStatus + '\\s*[-–—:]\\s*', 'i');
                cleanRwNotes = cleanRwNotes.replace(prefixRegex, '').trim();
                if (cleanRwNotes.toUpperCase() === rwStatus.toUpperCase()) {
                    cleanRwNotes = '';
                }
            }
            if (/^(YES|NO|IN PROGRESS|PENDING)\s*[-–—:]\s*\1$/i.test(cleanRwNotes) || ['N/A', 'NONE', 'NO', 'YES'].includes(cleanRwNotes.toUpperCase())) {
                cleanRwNotes = '';
            }

            let combinedRw = '';
            if (rwStatus && cleanRwNotes) {
                combinedRw = rwStatus + ' - ' + cleanRwNotes;
            } else if (cleanRwNotes) {
                combinedRw = cleanRwNotes;
            } else if (rwStatus) {
                combinedRw = rwStatus;
            } else {
                combinedRw = 'NO';
            }
            formData.set('report_witness_statement_status', rwStatus);
            formData.set('report_witness_statement_notes', cleanRwNotes);
            formData.set('report_witness_statement_sent', combinedRw);
            formData.set('report_witness_statement_message', combinedRw);

            if (courtGranted !== 'YES') {
                if (!formData.get('intel_shared')) {
                    formData.set('intel_shared', 'NO');
                }
            }

            const caseSectionLabel = (courtGranted === 'YES')
                ? 'ONGOING CASES (EXTRACTION COURT ORDERS RECEIVED)'
                : 'CURRENT/NEW CASES';
            formData.set('case_section_label', caseSectionLabel);

            // Explicitly sync all accumulated files from the DataTransfer pools
            formData.delete('court_order_files[]');
            formData.delete('court_order_file[]');
            Array.from(courtOrderDataTransfer.files).forEach(file => {
                formData.append('court_order_files[]', file);
            });

            formData.delete('report_witness_statement_files[]');
            formData.delete('report_witness_statement_file[]');
            Array.from(reportWitnessDataTransfer.files).forEach(file => {
                formData.append('report_witness_statement_files[]', file);
            });

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="inline-block animate-spin rounded-full h-3.5 w-3.5 border-2 border-white border-t-transparent mr-1"></span> Saving...';
            }

            fetch('{{ url("/Digital_Forensics/forensic-status") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> <span>Status Saved!</span>';
                }
                if (alertMsg) {
                    alertMsg.classList.remove('hidden');
                    alertMsg.classList.add('flex');
                }

                const repData = data.data || data.report || {
                    case_number: formData.get('case_number'),
                    location_post: formData.get('location_post'),
                    investigation_date: formData.get('investigation_date'),
                    devices_retrieved: formData.get('devices_retrieved'),
                    quantity: formData.get('quantity'),
                    brief_case_description: formData.get('brief_case_description'),
                    court_order_requested: formData.get('court_order_requested'),
                    court_order_granted: formData.get('court_order_granted'),
                    extraction_analysis_conducted: combinedExt,
                    extraction_analysis_message: combinedExt,
                    report_witness_statement_sent: combinedRw,
                    report_witness_statement_message: combinedRw,
                    intel_shared: formData.get('intel_shared'),
                    remark: formData.get('remark'),
                    dpf_code: 'DF-STATUS-' + new Date().toISOString().slice(0,10).replace(/-/g,'') + '-' + Math.floor(Math.random()*900 + 100)
                };

                updateDirectoryTableWithNewReport(repData);
                openReportPreviewModal(repData);
            })
            .catch(err => {
                console.error('Error saving status:', err);
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> <span>Save &amp; Log Investigation Status</span>';
                }

                const fallbackData = {
                    case_number: formData.get('case_number'),
                    location_post: formData.get('location_post'),
                    investigation_date: formData.get('investigation_date'),
                    devices_retrieved: formData.get('devices_retrieved'),
                    quantity: formData.get('quantity'),
                    brief_case_description: formData.get('brief_case_description'),
                    court_order_requested: formData.get('court_order_requested'),
                    court_order_granted: formData.get('court_order_granted'),
                    case_section_label: caseSectionLabel,
                    extraction_analysis_conducted: combinedExt,
                    extraction_analysis_message: combinedExt,
                    report_witness_statement_sent: combinedRw,
                    report_witness_statement_message: combinedRw,
                    intel_shared: formData.get('intel_shared'),
                    remark: formData.get('remark'),
                    dpf_code: 'DF-STATUS-' + new Date().toISOString().slice(0,10).replace(/-/g,'') + '-' + Math.floor(Math.random()*900 + 100)
                };
                updateDirectoryTableWithNewReport(fallbackData);
                openReportPreviewModal(fallbackData);
            });
        }

        function openReportPreviewModal(r) {
            if (!r) return;
            const ref = r.dpf_code || r.case_number || r.id || '';
            window.location.href = '{{ url("/Digital_Forensics/dpf-report-pdf") }}/' + encodeURIComponent(ref);
        }

        function closeReportPreviewModal() {
            // Deprecated - standalone page model active
        }

        function printReportPreview() {
            window.print();
        }

        function updateDirectoryTableWithNewReport(r) {
            const tbody = document.querySelector('#forensicDirectoryTable tbody');
            if (!tbody) return;

            function getMultiBadgesHtml(val, fileArr, baseLabel) {
                const raw = (val || 'NO').trim();
                const match = raw.match(/^(YES|NO|IN PROGRESS|PENDING)\s*[-–—:]\s*([\s\S]+)$/i);
                let badge = null;
                let note = null;
                if (match) {
                    badge = match[1].toUpperCase();
                    const candidate = match[2].trim();
                    if (candidate.toUpperCase() !== badge && !['N/A', 'NONE', 'NO', 'YES'].includes(candidate.toUpperCase())) {
                        note = candidate;
                    }
                } else if (['YES', 'NO', 'IN PROGRESS', 'PENDING'].includes(raw.toUpperCase())) {
                    badge = raw.toUpperCase();
                } else {
                    note = raw;
                }

                let bgClass = 'bg-rose-100 text-rose-800';
                if (badge === 'YES') bgClass = 'bg-emerald-100 text-emerald-800';
                else if (badge === 'IN PROGRESS' || badge === 'PENDING') bgClass = 'bg-amber-100 text-amber-800';

                let html = '';
                if (badge) {
                    html += `<span class="px-2 py-0.5 ${bgClass} text-[10px] font-bold rounded">${badge}</span>`;
                }
                if (note) {
                    const escaped = note.replace(/"/g, '&quot;');
                    html += `<p class="${badge ? 'mt-1 ' : ''}text-[10px] text-slate-700 font-sans leading-tight max-w-[220px] line-clamp-2" title="${escaped}">${note}</p>`;
                }
                if (!html) {
                    html = `<span class="px-2 py-0.5 bg-rose-100 text-rose-800 text-[10px] font-bold rounded">NO</span>`;
                }
                if (fileArr && Array.isArray(fileArr) && fileArr.length > 0) {
                    html += `<div class="mt-1 flex flex-wrap items-center gap-1 max-w-[200px]">`;
                    fileArr.forEach((filePath, idx) => {
                        const fullUrl = filePath.startsWith('http') || filePath.startsWith('/') ? filePath : '{{ asset("") }}' + filePath;
                        const label = baseLabel + (fileArr.length > 1 ? ' #' + (idx + 1) : '');
                        html += `
                            <a href="${fullUrl}" target="_blank" class="px-1.5 py-0.5 bg-emerald-100 hover:bg-emerald-200 text-emerald-900 rounded text-[9px] font-bold flex items-center gap-0.5 transition-colors">
                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span>${label}</span>
                            </a>
                        `;
                    });
                    html += `</div>`;
                }
                return html;
            }

            function getExtractionBadgeHtml(val) {
                const raw = (val || 'NO').trim();
                const match = raw.match(/^(YES|NO|IN PROGRESS|PENDING)\s*[-–—:]\s*([\s\S]+)$/i);
                let badge = null;
                let note = null;
                if (match) {
                    badge = match[1].toUpperCase();
                    const candidate = match[2].trim();
                    if (candidate.toUpperCase() !== badge && !['N/A', 'NONE', 'NO', 'YES'].includes(candidate.toUpperCase())) {
                        note = candidate;
                    }
                } else if (['YES', 'NO', 'IN PROGRESS', 'PENDING'].includes(raw.toUpperCase())) {
                    badge = raw.toUpperCase();
                } else {
                    note = raw;
                }

                let badgeClass = 'bg-rose-100 text-rose-800';
                if (badge === 'YES') badgeClass = 'bg-emerald-100 text-emerald-800';
                else if (badge === 'IN PROGRESS' || badge === 'PENDING') badgeClass = 'bg-amber-100 text-amber-800';

                let html = '';
                if (badge) {
                    html += `<span class="px-2 py-0.5 ${badgeClass} text-[10px] font-bold rounded">${badge}</span>`;
                }
                if (note) {
                    const escaped = note.replace(/"/g, '&quot;');
                    html += `<p class="${badge ? 'mt-1 ' : ''}text-[10px] text-slate-700 font-sans leading-tight max-w-[220px] line-clamp-2" title="${escaped}">${note}</p>`;
                }
                return html || `<span class="px-2 py-0.5 bg-rose-100 text-rose-800 text-[10px] font-bold rounded">NO</span>`;
            }

            // Safely locate if an existing row for this case already exists in the directory table
            const targetCaseNo = (r.case_number || '').trim().toLowerCase();
            let tr = null;
            const directoryRows = document.querySelectorAll('#forensicDirectoryTable tbody tr.directory-row');
            directoryRows.forEach(row => {
                const rowCase = (row.getAttribute('data-case') || '').trim().toLowerCase();
                if (rowCase && rowCase === targetCaseNo) {
                    tr = row;
                }
            });

            const isNewRow = !tr;
            if (isNewRow) {
                tr = document.createElement('tr');
            }
            tr.className = 'hover:bg-emerald-50/40 transition-colors bg-emerald-50/30 border-l-4 border-emerald-600 animate-in fade-in duration-300 directory-row';
            
            const now = new Date();
            const pad = n => String(n).padStart(2, '0');
            const todayIso = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
            const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            const todayDisplay = `${pad(now.getDate())} ${months[now.getMonth()]} ${now.getFullYear()}`;
            const timeDisplay = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            const repDate = (r.investigation_date ? String(r.investigation_date).slice(0, 10) : todayIso);
            tr.setAttribute('data-date', repDate);
            tr.setAttribute('data-case', (r.case_number || '').toLowerCase());
            tr.setAttribute('data-caseref', r.case_number || '');
            tr.setAttribute('data-code', (r.dpf_code || '').toLowerCase());
            tr.setAttribute('data-location', (r.location_post || '').toLowerCase());
            tr.setAttribute('data-court', (r.court_order_granted || 'NO').toUpperCase());
            tr.setAttribute('data-extraction', (r.extraction_analysis_conducted || r.extraction_analysis_message || 'NO').toUpperCase());
            tr.setAttribute('data-intel', (r.intel_shared || 'NO').toUpperCase());

            const pdfUrl = '{{ url("/Digital_Forensics/dpf-report-pdf") }}/' + encodeURIComponent(r.dpf_code || r.case_number || '');

            let courtFiles = [];
            if (Array.isArray(r.court_order_files)) {
                courtFiles = r.court_order_files;
            } else if (r.court_order_file) {
                try {
                    const parsed = JSON.parse(r.court_order_file);
                    courtFiles = Array.isArray(parsed) ? parsed : [r.court_order_file];
                } catch(e) {
                    courtFiles = [r.court_order_file];
                }
            }

            let reportFiles = [];
            if (Array.isArray(r.report_witness_statement_files)) {
                reportFiles = r.report_witness_statement_files;
            } else if (r.report_witness_statement_file) {
                try {
                    const parsed = JSON.parse(r.report_witness_statement_file);
                    reportFiles = Array.isArray(parsed) ? parsed : [r.report_witness_statement_file];
                } catch(e) {
                    reportFiles = [r.report_witness_statement_file];
                }
            }

            tr.innerHTML = `
                <td class="p-3 font-extrabold text-emerald-950">
                    <div>${r.case_number}</div>
                    <div class="flex items-center gap-1 mt-0.5">
                        <span class="text-[9px] font-bold text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded">${r.dpf_code || ''}</span>
                        ${r.location_post ? `<span class="text-[9px] text-slate-400 font-sans font-normal truncate max-w-[130px]" title="${r.location_post}">${r.location_post}</span>` : ''}
                    </div>
                </td>
                <td class="p-3 whitespace-nowrap">
                    <div class="font-bold text-[11px] text-slate-800">${todayDisplay}</div>
                    <span class="text-[9px] text-slate-400 font-sans block">${timeDisplay}</span>
                </td>
                <td class="p-3">${getMultiBadgesHtml(r.court_order_granted, courtFiles, 'Order')}</td>
                <td class="p-3">${getExtractionBadgeHtml(r.extraction_analysis_message || r.extraction_analysis_conducted)}</td>
                <td class="p-3">${getMultiBadgesHtml(r.report_witness_statement_message || r.report_witness_statement_sent, reportFiles, 'Evidence / Stmt')}</td>
                <td class="p-3">${getMultiBadgesHtml(r.intel_shared)}</td>
                <td class="p-3 text-right space-x-1 whitespace-nowrap">
                    <a href="${pdfUrl}" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[10px] font-bold transition-colors no-underline inline-block">
                        Preview
                    </a>
                </td>
            `;

            if (isNewRow) {
                tbody.prepend(tr);
            } else {
                // Flash updated row briefly so user knows it was updated in place
                tr.classList.add('bg-emerald-100/50');
                setTimeout(() => {
                    tr.classList.remove('bg-emerald-100/50');
                }, 1000);
            }

            // Sync updated values into caseMapData in memory
            if (caseMapData && r.case_number && caseMapData[r.case_number]) {
                const mapEntry = caseMapData[r.case_number];
                if (r.court_order_requested) mapEntry.court_order_requested = r.court_order_requested;
                if (r.court_order_granted) mapEntry.court_order_granted = r.court_order_granted;
                if (r.report_data && r.report_data.extraction_analysis_status !== undefined) {
                    mapEntry.extraction_status = r.report_data.extraction_analysis_status;
                }
                if (r.report_data && r.report_data.extraction_analysis_notes !== undefined) {
                    mapEntry.extraction_notes = r.report_data.extraction_analysis_notes;
                }
                if (r.report_data && r.report_data.ongoing_extraction_analysis_status !== undefined) {
                    mapEntry.ongoing_extraction_status = r.report_data.ongoing_extraction_analysis_status;
                }
                if (r.report_data && r.report_data.ongoing_extraction_analysis_notes !== undefined) {
                    mapEntry.ongoing_extraction_notes = r.report_data.ongoing_extraction_analysis_notes;
                }
                if (r.report_data && r.report_data.report_witness_statement_status !== undefined) {
                    mapEntry.report_witness_status = r.report_data.report_witness_statement_status;
                }
                if (r.report_data && r.report_data.report_witness_statement_notes !== undefined) {
                    mapEntry.report_witness_notes = r.report_data.report_witness_statement_notes;
                }
                if (r.intel_shared) mapEntry.intel_shared = r.intel_shared;
                if (r.remark !== undefined) mapEntry.remark = r.remark;
            }

            applyDirectoryFilters();
        }

        let currentPreset = 'all';

        function getDirectoryReferenceDate() {
            const now = new Date();
            const rows = document.querySelectorAll('#forensicDirectoryTable tbody tr.directory-row');
            let maxDateStr = null;
            rows.forEach(r => {
                const d = (r.getAttribute('data-date') || '').trim().slice(0, 10);
                if (d && (!maxDateStr || d > maxDateStr)) {
                    maxDateStr = d;
                }
            });

            if (maxDateStr) {
                const parts = maxDateStr.split('-');
                if (parts.length === 3) {
                    const y = parseInt(parts[0], 10);
                    const m = parseInt(parts[1], 10) - 1;
                    const d = parseInt(parts[2], 10);
                    if (!isNaN(y) && !isNaN(m) && !isNaN(d)) {
                        const maxD = new Date(y, m, d);
                        return (now >= maxD) ? now : maxD;
                    }
                }
            }
            return now;
        }

        function updatePresetButtonsVisual(activePreset) {
            const presets = ['all', 'weekly', 'monthly', 'annually'];
            presets.forEach(p => {
                const btn = document.getElementById('btnPreset' + p.charAt(0).toUpperCase() + p.slice(1));
                if (!btn) return;
                if (p === activePreset) {
                    btn.className = 'px-3 py-1 rounded-lg text-xs font-mono-code font-bold transition-all bg-emerald-600 text-white shadow-xs cursor-pointer';
                    btn.style.backgroundColor = '#059669';
                    btn.style.color = '#ffffff';
                } else {
                    btn.className = 'px-3 py-1 rounded-lg text-xs font-mono-code font-bold transition-all bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs cursor-pointer';
                    btn.style.backgroundColor = '#ffffff';
                    btn.style.color = '#334155';
                }
            });
        }

        function setDateFilterPreset(preset) {
            currentPreset = preset;
            updatePresetButtonsVisual(preset);

            const fromInput = document.getElementById('dateFilterFrom');
            const toInput = document.getElementById('dateFilterTo');

            const refDate = getDirectoryReferenceDate();
            const pad = n => String(n).padStart(2, '0');
            const formatDate = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

            if (preset === 'all') {
                if (fromInput) fromInput.value = '';
                if (toInput) toInput.value = '';
            } else if (preset === 'weekly') {
                const day = refDate.getDay();
                const diffToMon = (day === 0 ? -6 : 1) - day;
                const monday = new Date(refDate);
                monday.setDate(refDate.getDate() + diffToMon);
                const sunday = new Date(monday);
                sunday.setDate(monday.getDate() + 6);

                if (fromInput) fromInput.value = formatDate(monday);
                if (toInput) toInput.value = formatDate(sunday);
            } else if (preset === 'monthly') {
                const firstDay = new Date(refDate.getFullYear(), refDate.getMonth(), 1);
                const lastDay = new Date(refDate.getFullYear(), refDate.getMonth() + 1, 0);

                if (fromInput) fromInput.value = formatDate(firstDay);
                if (toInput) toInput.value = formatDate(lastDay);
            } else if (preset === 'annually') {
                const firstDay = new Date(refDate.getFullYear(), 0, 1);
                const lastDay = new Date(refDate.getFullYear(), 11, 31);

                if (fromInput) fromInput.value = formatDate(firstDay);
                if (toInput) toInput.value = formatDate(lastDay);
            }

            applyDirectoryFilters();
        }

        function onCustomDateChange() {
            currentPreset = 'custom';
            updatePresetButtonsVisual('custom');
            applyDirectoryFilters();
        }

        function generateDirectoryFilter() {
            const btn = document.getElementById('btnGenerateFilter');
            const table = document.getElementById('forensicDirectoryTable');
            applyDirectoryFilters();

            const visibleRows = Array.from(document.querySelectorAll('#forensicDirectoryTable tbody tr.directory-row'))
                .filter(r => r.style.display !== 'none');

            if (visibleRows.length === 0) {
                if (typeof showStatusNotification === 'function') {
                    showStatusNotification('No suspects/cases found matching the selected filters.', 'warning');
                } else {
                    alert('No suspects/cases found matching the selected filters.');
                }
                return;
            }

            const caseList = [];
            visibleRows.forEach(r => {
                const cRef = (r.getAttribute('data-caseref') || r.getAttribute('data-case') || '').trim();
                if (cRef && !caseList.includes(cRef)) {
                    caseList.push(cRef);
                }
            });

            if (caseList.length === 0) {
                if (typeof showStatusNotification === 'function') {
                    showStatusNotification('No case references found in the filtered rows.', 'warning');
                }
                return;
            }

            if (table) {
                table.classList.add('transition-opacity', 'opacity-70');
                setTimeout(() => {
                    table.classList.remove('opacity-70');
                }, 200);
            }

            if (btn) {
                const originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = `
                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Generating...</span>
                `;
                setTimeout(() => {
                    btn.disabled = false;
                    btn.innerHTML = `
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Printed!</span>
                    `;
                    setTimeout(() => {
                        btn.innerHTML = originalHtml;
                    }, 1200);
                }, 400);
            }

            const params = new URLSearchParams();
            params.set('cases', caseList.join(','));
            params.set('print', '1');

            const fromVal = (document.getElementById('dateFilterFrom')?.value || '').trim();
            const toVal = (document.getElementById('dateFilterTo')?.value || '').trim();
            const courtVal = (document.getElementById('directoryCourtFilter')?.value || '').trim();
            const extVal = (document.getElementById('directoryExtractionFilter')?.value || '').trim();
            const searchVal = (document.getElementById('directorySearchInput')?.value || '').trim();

            if (fromVal) params.set('from', fromVal);
            if (toVal) params.set('to', toVal);
            if (courtVal) params.set('court', courtVal);
            if (extVal) params.set('extraction', extVal);
            if (searchVal) params.set('search', searchVal);

            // Determine filter period (weekly, monthly, annual, date_range, or general)
            let periodParam = 'general';
            if (currentPreset === 'weekly') {
                periodParam = 'weekly';
            } else if (currentPreset === 'monthly') {
                periodParam = 'monthly';
            } else if (currentPreset === 'annually' || currentPreset === 'annual') {
                periodParam = 'annual';
            } else if (fromVal || toVal || currentPreset === 'custom') {
                periodParam = 'date_range';
            } else {
                periodParam = 'general';
            }
            params.set('period', periodParam);

            const targetUrl = '{{ url("/Digital_Forensics/multi_dpf_pdf") }}?' + params.toString();
            window.open(targetUrl, '_blank');
        }

        function resetDateFilters() {
            const searchInput = document.getElementById('directorySearchInput');
            if (searchInput) searchInput.value = '';

            const courtFilter = document.getElementById('directoryCourtFilter');
            if (courtFilter) courtFilter.value = '';

            const extFilter = document.getElementById('directoryExtractionFilter');
            if (extFilter) extFilter.value = '';

            setDateFilterPreset('all');
        }

        function applyDirectoryFilters() {
            const searchInput = document.getElementById('directorySearchInput');
            const query = (searchInput ? searchInput.value : '').toLowerCase().trim();

            const fromVal = (document.getElementById('dateFilterFrom')?.value || '').trim();
            const toVal = (document.getElementById('dateFilterTo')?.value || '').trim();
            const courtVal = (document.getElementById('directoryCourtFilter')?.value || '').toUpperCase().trim();
            const extVal = (document.getElementById('directoryExtractionFilter')?.value || '').toUpperCase().trim();

            const rows = document.querySelectorAll('#forensicDirectoryTable tbody tr.directory-row');
            let visibleCount = 0;
            const totalCount = rows.length;

            rows.forEach(r => {
                const rowDate = (r.getAttribute('data-date') || '').trim().slice(0, 10);
                const rowCase = (r.getAttribute('data-case') || '').toLowerCase();
                const rowCode = (r.getAttribute('data-code') || '').toLowerCase();
                const rowLoc = (r.getAttribute('data-location') || '').toLowerCase();
                const rowCourt = (r.getAttribute('data-court') || '').toUpperCase();
                const rowExt = (r.getAttribute('data-extraction') || '').toUpperCase();
                const rowText = r.innerText.toLowerCase();

                // 1. Text Query Search
                let matchesText = true;
                if (query) {
                    matchesText = rowText.includes(query) || 
                                  rowCase.includes(query) || 
                                  rowCode.includes(query) || 
                                  rowLoc.includes(query);
                }

                // 2. Date Range Filter
                let matchesDate = true;
                if (fromVal && rowDate && rowDate < fromVal) {
                    matchesDate = false;
                }
                if (toVal && rowDate && rowDate > toVal) {
                    matchesDate = false;
                }

                // 3. Court Order Filter
                let matchesCourt = true;
                if (courtVal) {
                    if (courtVal === 'YES') {
                        matchesCourt = rowCourt === 'YES' || rowCourt.includes('GRANT');
                    } else if (courtVal === 'NO') {
                        matchesCourt = rowCourt === 'NO' || (!rowCourt.includes('YES') && !rowCourt.includes('GRANT'));
                    }
                }

                // 4. Extraction & Analysis Filter
                let matchesExt = true;
                if (extVal) {
                    if (extVal === 'YES') {
                        matchesExt = rowExt === 'YES' || rowExt.startsWith('YES');
                    } else if (extVal === 'IN PROGRESS') {
                        matchesExt = rowExt.includes('PROGRESS');
                    } else if (extVal === 'NO') {
                        matchesExt = rowExt === 'NO' || rowExt.includes('PENDING') || rowExt.startsWith('NO');
                    }
                }

                const isVisible = matchesText && matchesDate && matchesCourt && matchesExt;
                r.style.display = isVisible ? '' : 'none';
                if (isVisible) visibleCount++;
            });

            const noRecordsRow = document.getElementById('directoryNoRecordsRow');
            if (noRecordsRow) {
                noRecordsRow.classList.toggle('hidden', visibleCount > 0);
                if (visibleCount === 0) {
                    const filtersApplied = [];
                    if (query) filtersApplied.push(`Search "${query}"`);
                    if (fromVal || toVal) filtersApplied.push(`Date ${fromVal || 'start'} to ${toVal || 'present'}`);
                    if (courtVal) filtersApplied.push(`Court Order: ${courtVal}`);
                    if (extVal) filtersApplied.push(`Extraction: ${extVal}`);

                    const filterDesc = filtersApplied.length > 0 ? ` (${filtersApplied.join(', ')})` : '';
                    noRecordsRow.innerHTML = `
                        <td colspan="7" class="py-8 text-center text-slate-400 font-semibold font-mono-code">
                            <div class="space-y-2">
                                <p>No matching forensic investigations found for the selected filter criteria${filterDesc}.</p>
                                <button type="button" onclick="resetDateFilters()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-bold border border-emerald-200 text-xs transition-colors cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    <span>Reset All Filters</span>
                                </button>
                            </div>
                        </td>
                    `;
                }
            }

            const badge = document.getElementById('directoryRowCountBadge');
            if (badge) {
                badge.textContent = `Showing: ${visibleCount} of ${totalCount}`;
            }
        }

        function filterDirectoryTable(query) {
            const searchInput = document.getElementById('directorySearchInput');
            if (searchInput && searchInput.value !== query) {
                searchInput.value = query;
            }
            applyDirectoryFilters();
        }
    </script>
</body>
</html>
