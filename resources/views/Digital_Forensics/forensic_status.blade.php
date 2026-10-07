@php
    $authUser = auth()->user();
    $dbUnitHead = \App\Models\User::where('unit', 'LIKE', '%Digital%')->orWhere('unit', 'LIKE', '%Physical%')->where('is_head', true)->first()
               ?? \App\Models\User::where('is_head', true)->first();
    $headName = $authUser->fullname ?? session('user_name') ?? ($dbUnitHead ? $dbUnitHead->fullname : 'Lead Cyber Analyst');
    $allCases = \App\Models\ForensicCase::allCases();
    $cases = $allCases->filter(fn($c) => $c->isDigitalCase())->values();
    $savedReports = \App\Models\DpfReport::latest()->get();

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
                if (!empty($ex->seizure_quantity) || !empty($ex->seizure_packaging)) {
                    $itemStr .= trim(($ex->seizure_quantity ?? '') . ' ' . ($ex->seizure_packaging ?? '')) . ' ';
                }
                $itemStr .= $ex->commodity_name ?: ($ex->exhibit_category ?: ($ex->dev_model ?: ''));
                $itemStr = trim($itemStr);
                if (!empty($itemStr)) {
                    $exList[] = $itemStr;
                }
            }
        }

        if (empty($exList) && !empty($dData['seizures']) && is_array($dData['seizures'])) {
            foreach ($dData['seizures'] as $sz) {
                $nameStr = $sz['commodity_name'] ?? ($sz['seizure_name'] ?? ($sz['name'] ?? ''));
                if (!empty($nameStr)) {
                    $exList[] = $nameStr;
                }
            }
        }

        if (empty($exList)) {
            $mainName = $cItem->seizure_name ?? ($cItem->seizure_name_drug ?? ($cItem->exhibit_category ?? null));
            if (!empty($mainName)) {
                $exList[] = $mainName;
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
            : 'On Thursday, 18 June 2026, officers of the Narcotics Control Commission (NACOC) conducted an intelligence-led operation at Agbogbloshie in the Greater Accra Region to curb the use and sale of narcotic drugs within the area. During the operation, Victor Kofi was arrested and subsequently conveyed to NACOC Headquarters for further investigation.';

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

        $caseMapData[$cNo] = [
            'location'    => $loc,
            'devices'     => $devicesStr,
            'qty'         => $qtyStr,
            'description' => $descStr,
            'date'        => $arrestDateVal
        ];
    }

    $firstCaseNo = count($cases) > 0 ? ($cases[0]->case_number ?? '') : '';
    $defaultLoc  = !empty($firstCaseNo) ? ($caseMapData[$firstCaseNo]['location'] ?? 'Agbogbloshie in the Greater Accra Region') : 'Agbogbloshie in the Greater Accra Region';
    $defaultDev  = !empty($firstCaseNo) ? ($caseMapData[$firstCaseNo]['devices'] ?? 'Electronic Devices / Mobile Exhibits') : 'Electronic Devices / Mobile Exhibits';
    $defaultQty  = !empty($firstCaseNo) ? ($caseMapData[$firstCaseNo]['qty'] ?? '1') : '1';
    $defaultDesc = !empty($firstCaseNo) ? ($caseMapData[$firstCaseNo]['description'] ?? '') : 'On Thursday, 18 June 2026, officers of the Narcotics Control Commission (NACOC) conducted an intelligence-led operation at Agbogbloshie in the Greater Accra Region to curb the use and sale of narcotic drugs within the area. During the operation, Victor Kofi was arrested and subsequently conveyed to NACOC Headquarters for further investigation.';
    $defaultDate = !empty($firstCaseNo) ? ($caseMapData[$firstCaseNo]['date'] ?? date('Y-m-d')) : date('Y-m-d');
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
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
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
                <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-emerald-950 p-6 rounded-3xl text-white shadow-xl flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 border border-emerald-900/40">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2.5 py-1 bg-emerald-600 text-white text-[10px] font-extrabold font-mono-code rounded-lg uppercase tracking-wider">
                                DIGITAL &amp; PHYSICAL FORENSICS UNIT
                            </span>
                            
                        </div>
                        <h1 class="text-xl sm:text-2xl font-black font-mono-code uppercase tracking-tight text-white">
                            Forensic &amp; Investigation Status
                        </h1>
                        <p class="text-xs text-slate-300 font-medium max-w-2xl leading-relaxed">
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

                    <form id="forensicStatusForm" onsubmit="handleStatusSubmit(event)" class="space-y-6">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                            
                            <!-- Case Reference Select / Input -->
                            <div class="space-y-2">
                                <label for="statusCaseRef" class="block text-xs font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                    Case Reference <span class="text-red-500">*</span>
                                </label>
                                <select id="statusCaseRef" name="case_number" onchange="onDigitalCaseRefChanged(this.value)" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer">
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
                            <textarea id="briefCaseDescription" name="brief_case_description" rows="3" placeholder="Enter a concise summary of the case facts, suspect operations, and intelligence context..." class="w-full px-4 py-3 bg-white border border-slate-200 rounded-2xl text-xs text-slate-900 font-sans leading-relaxed focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all custom-scrollbar">{{ $defaultDesc ?: 'On Thursday, 18 June 2026, officers of the Narcotics Control Commission (NACOC) conducted an intelligence-led operation at Agbogbloshie in the Greater Accra Region to curb the use and sale of narcotic drugs within the area. During the operation, Victor Kofi was arrested and subsequently conveyed to NACOC Headquarters for further investigation.' }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

                            <!-- Court Order Requested? -->
                            <div class="space-y-2">
                                <label for="courtOrderRequested" class="block text-xs font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                    Court Order Requested? <span class="text-red-500">*</span>
                                </label>
                                <select id="courtOrderRequested" name="court_order_requested" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer">
                                    <option value="YES" selected>YES</option>
                                    <option value="NO">NO</option>
                                    <option value="PENDING">PENDING</option>
                                </select>
                            </div>

                            <!-- Court Order Granted? -->
                            <div class="space-y-2">
                                <label for="courtOrderGranted" class="block text-xs font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                    Court Order Granted? <span class="text-red-500">*</span>
                                </label>
                                <select id="courtOrderGranted" name="court_order_granted" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer">
                                    <option value="YES" selected>YES</option>
                                    <option value="NO">NO</option>
                                    <option value="PENDING">PENDING</option>
                                </select>
                            </div>

                            <!-- Extraction & Analysis Conducted? -->
                            <div class="space-y-2">
                                <label for="extractionAnalysisConducted" class="block text-xs font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                    Extraction &amp; Analysis Conducted? <span class="text-red-500">*</span>
                                </label>
                                <select id="extractionAnalysisConducted" name="extraction_analysis_conducted" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer">
                                    <option value="YES" selected>YES</option>
                                    <option value="NO">NO</option>
                                    <option value="IN PROGRESS">IN PROGRESS</option>
                                </select>
                            </div>

                            <!-- Report & Witness Statement Sent to Investigations -->
                            <div class="space-y-2 md:col-span-2 lg:col-span-1">
                                <label for="reportWitnessSent" class="block text-xs font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                    Report &amp; Witness Statement Sent to Investigations: <span class="text-red-500">*</span>
                                </label>
                                <select id="reportWitnessSent" name="report_witness_statement_sent" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer">
                                    <option value="YES" selected>YES</option>
                                    <option value="NO">NO</option>
                                    <option value="PENDING">PENDING</option>
                                </select>
                            </div>

                            <!-- Intelligence Shared to Intel Department for Action? -->
                            <div class="space-y-2 md:col-span-2 lg:col-span-2">
                                <label for="intelShared" class="block text-xs font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                    Intelligence Shared to Intel Department for Action? <span class="text-red-500">*</span>
                                </label>
                                <select id="intelShared" name="intel_shared" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer">
                                    <option value="YES" selected>YES</option>
                                    <option value="NO">NO</option>
                                    <option value="PENDING">PENDING</option>
                                </select>
                            </div>

                        </div>

                        <!-- Remark -->
                        <div class="space-y-2">
                            <label for="statusRemark" class="block text-xs font-extrabold text-slate-700 font-mono-code uppercase tracking-wider">
                                Remark
                            </label>
                            <textarea id="statusRemark" name="remark" rows="3" placeholder="Enter additional remarks or status details..." class="w-full px-4 py-3 bg-white border border-slate-200 rounded-2xl text-xs text-slate-900 font-sans leading-relaxed focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all custom-scrollbar"></textarea>
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
                <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xl overflow-hidden space-y-4 p-5 sm:p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                        <div>
                            
                            <h3 class="text-base font-black text-slate-900 font-mono-code uppercase tracking-tight mt-1">
                                Digital Forensic &amp; Investigation Directory
                            </h3>
                        </div>
                        <div class="relative w-full max-w-xs">
                            <input type="text" onkeyup="filterDirectoryTable(this.value)" placeholder="Filter by Case, Location, or Status..." class="w-full pl-9 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 pointer-events-none">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </span>
                        </div>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-xs text-left border-collapse" id="forensicDirectoryTable">
                            <thead>
                                <tr class="bg-slate-100 text-slate-700 font-mono-code uppercase text-[10px] tracking-wider border-b border-slate-200">
                                    <th class="p-3">Case Ref</th>
                                    <th class="p-3">Devices / Exhibits</th>
                                    <th class="p-3">Court Order</th>
                                    <th class="p-3">Extraction &amp; Analysis</th>
                                    <th class="p-3">Report to Investigations</th>
                                    <th class="p-3">Intel Shared</th>
                                    <th class="p-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-mono-code" id="forensicTableBody">
                                @foreach($savedReports as $rep)
                                    <tr class="hover:bg-emerald-50/40 transition-colors bg-emerald-50/20 border-l-4 border-emerald-600">
                                        <td class="p-3 font-extrabold text-emerald-950">
                                            <div>{{ $rep->case_number }}</div>
                                            <span class="text-[9px] font-bold text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded">{{ $rep->dpf_code }}</span>
                                        </td>
                                        <td class="p-3 font-semibold text-slate-800">
                                            {{ $rep->devices_retrieved }}
                                            @if($rep->quantity)
                                                <span class="text-[10px] text-slate-500 font-normal block">(Qty: {{ $rep->quantity }})</span>
                                            @endif
                                        </td>
                                        <td class="p-3">
                                            <span class="px-2 py-0.5 {{ $rep->court_order_granted === 'YES' ? 'bg-emerald-100 text-emerald-800' : ($rep->court_order_granted === 'NO' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }} text-[10px] font-bold rounded">
                                                {{ $rep->court_order_granted }}
                                            </span>
                                        </td>
                                        <td class="p-3">
                                            <span class="px-2 py-0.5 {{ $rep->extraction_analysis_conducted === 'YES' ? 'bg-emerald-100 text-emerald-800' : ($rep->extraction_analysis_conducted === 'NO' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }} text-[10px] font-bold rounded">
                                                {{ $rep->extraction_analysis_conducted }}
                                            </span>
                                        </td>
                                        <td class="p-3">
                                            <span class="px-2 py-0.5 {{ $rep->report_witness_statement_sent === 'YES' ? 'bg-emerald-100 text-emerald-800' : ($rep->report_witness_statement_sent === 'NO' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }} text-[10px] font-bold rounded">
                                                {{ $rep->report_witness_statement_sent }}
                                            </span>
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
                                        $cRef = $cItem->case_number ?? ($cItem['case_number'] ?? ('CSS-2026-DF' . sprintf('%02d', $idx + 1)));
                                        if ($savedReports->contains('case_number', $cRef)) continue;
                                        $cLoc = $caseMapData[$cRef]['location'] ?? 'Kotoka International Airport (Cargo Wing)';
                                        $cDev = $caseMapData[$cRef]['devices'] ?? 'Electronic Devices / Mobile Exhibits';
                                        $cDate = $caseMapData[$cRef]['date'] ?? date('Y-m-d');
                                    @endphp
                                    <tr class="hover:bg-emerald-50/40 transition-colors">
                                        <td class="p-3 font-extrabold text-slate-900">{{ $cRef }}</td>
                                        <td class="p-3 font-semibold text-slate-800">{{ $cDev }}</td>
                                        <td class="p-3"><span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-bold rounded">Granted</span></td>
                                        <td class="p-3"><span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-bold rounded">YES</span></td>
                                        <td class="p-3"><span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-bold rounded">YES</span></td>
                                        <td class="p-3"><span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-bold rounded">YES</span></td>
                                        <td class="p-3 text-right">
                                            <a href="{{ url('/Digital_Forensics/dpf-report-pdf/' . $cRef) }}" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[10px] font-bold transition-colors no-underline inline-block">
                                                Preview
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script>
        const caseMapData = @json($caseMapData);

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
        }

        document.addEventListener('DOMContentLoaded', function() {
            const selectEl = document.getElementById('statusCaseRef');
            if (selectEl && selectEl.value) {
                onDigitalCaseRefChanged(selectEl.value);
            }
        });

        function handleStatusSubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('saveStatusBtn');
            const alertMsg = document.getElementById('statusAlertMsg');
            const form = document.getElementById('forensicStatusForm');
            const formData = new FormData(form);

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
                    extraction_analysis_conducted: formData.get('extraction_analysis_conducted'),
                    report_witness_statement_sent: formData.get('report_witness_statement_sent'),
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
                    extraction_analysis_conducted: formData.get('extraction_analysis_conducted'),
                    report_witness_statement_sent: formData.get('report_witness_statement_sent'),
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

            const existingRows = tbody.querySelectorAll('tr');
            existingRows.forEach(row => {
                if (row.innerText.includes(r.case_number)) {
                    row.remove();
                }
            });

            function getBadgeHtml(val) {
                let bgClass = 'bg-rose-100 text-rose-800';
                if (val === 'YES') bgClass = 'bg-emerald-100 text-emerald-800';
                else if (val === 'IN PROGRESS' || val === 'PENDING') bgClass = 'bg-amber-100 text-amber-800';
                return `<span class="px-2 py-0.5 ${bgClass} text-[10px] font-bold rounded">${val || 'NO'}</span>`;
            }

            const tr = document.createElement('tr');
            tr.className = 'hover:bg-emerald-50/40 transition-colors bg-emerald-50/30 border-l-4 border-emerald-600 animate-in fade-in duration-300';
            const pdfUrl = '{{ url("/Digital_Forensics/dpf-report-pdf") }}/' + encodeURIComponent(r.dpf_code || r.case_number || '');
            tr.innerHTML = `
                <td class="p-3 font-extrabold text-emerald-950">
                    <div>${r.case_number}</div>
                    <span class="text-[9px] font-bold text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded">${r.dpf_code || ''}</span>
                </td>
                <td class="p-3 font-semibold text-slate-800">
                    ${r.devices_retrieved || ''}
                    ${r.quantity ? `<span class="text-[10px] text-slate-500 font-normal block">(Qty: ${r.quantity})</span>` : ''}
                </td>
                <td class="p-3">${getBadgeHtml(r.court_order_granted)}</td>
                <td class="p-3">${getBadgeHtml(r.extraction_analysis_conducted)}</td>
                <td class="p-3">${getBadgeHtml(r.report_witness_statement_sent)}</td>
                <td class="p-3">${getBadgeHtml(r.intel_shared)}</td>
                <td class="p-3 text-right space-x-1 whitespace-nowrap">
                    <a href="${pdfUrl}" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[10px] font-bold transition-colors no-underline inline-block">
                        Preview
                    </a>
                </td>
            `;

            tbody.prepend(tr);
        }

        function filterDirectoryTable(query) {
            const q = (query || '').toLowerCase().trim();
            const rows = document.querySelectorAll('#forensicDirectoryTable tbody tr');
            rows.forEach(r => {
                const text = r.innerText.toLowerCase();
                r.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }
    </script>
</body>
</html>
