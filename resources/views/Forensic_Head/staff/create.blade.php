<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @php
    $case = $case ?? null;
        $hasDrugExhibitLinkedToSuspect = $hasDrugExhibitLinkedToSuspect ?? false;
    @endphp
    @php
        $authUser = auth()->user();
        $userUnit = !empty($authUser->unit) ? $authUser->unit : (session('user_unit') ?? request()->query('unit') ?? request()->query('from') ?? 'Crime Scene Support');
        $isLabAnalysis = false;
        
        $dbLabHead = \App\Models\User::where('unit', 'LIKE', '%Lab%')->where('is_head', true)->first();
        $defaultLabHeadName = $dbLabHead ? $dbLabHead->fullname : 'N/A';
        $officerName = auth()->user()->fullname ?? session('user_name') ?? ($authUser->fullname ?? ($defaultLabHeadName !== 'N/A' ? $defaultLabHeadName : 'N/A'));
        $examRefAuto = app(\App\Services\CaseReferenceService::class)->generateExamReference('Lab Analysis');
        $labNumberAuto = app(\App\Services\CaseReferenceService::class)->generateLabNumber();
        $caseRefAuto = app(\App\Services\CaseNumberService::class)->generate();
        $exhibitRefAuto = 'EXH-' . date('Y') . '-001-NFC';
        $todayDate = date('Y-m-d');
        $isDrugCase = false;
        $activeSuspectHasSavedResult = false;
        if (isset($case)) {
            $exCat = strtolower($case->exhibit_category ?? '');
            $szDrug = strtolower($case->seizure_name_drug ?? '');
            $szName = strtolower($case->seizure_name ?? '');
            $exSum = strtolower($case->exhibits_summary ?? '');
            $combinedExText = "{$exCat} {$szDrug} {$szName} {$exSum}";
            $drugKeywords = config('forensic.drug_keywords', []);
            $isDrugCase = !empty($szDrug);
            if (!$isDrugCase) {
                foreach ($drugKeywords as $dkw) {
                    if ($dkw !== '' && str_contains($combinedExText, strtolower($dkw))) {
                        $isDrugCase = true;
                        break;
                    }
                }
            }

            $dbExamExists = false;
            if (!empty($case->id)) {
                $isMultiCase = ($case instanceof \App\Models\ForensicCase)
                    ? $case->isMultipleSuspect()
                    : (($case->underlyingRecord ?? null) instanceof \App\Models\MultipleSuspectCase || ($case instanceof \App\Models\MultipleSuspectCase));

                $dbExamExists = \App\Models\LaboratoryExaminationResult::where(function($q) use ($case, $isMultiCase) {
                    if ($isMultiCase) {
                        $q->where('multiple_suspect_case_id', $case->id);
                    } else {
                        $q->where('single_suspect_case_id', $case->id);
                    }
                })->exists();
            }
            $dData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
            $docketExamSaved = !empty($dData['lab_examination']['has_saved']);
            if ($dbExamExists || $docketExamSaved) {
                $activeSuspectHasSavedResult = true;
            }
        }
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Case Docket | NACOC Forensic Directorate</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    @endif

    <!-- jQuery & Select2 CDN -->
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <link href="{{ asset('vendor/select2/select2.min.css') }}" rel="stylesheet" />
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>

    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #F8FAFC;
            color: #0F172A;
        }
        .font-mono-code {
            font-family: 'JetBrains Mono', monospace;
        }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #F1F5F9; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 3px; }

        /* Select2 Executive Clean Styling */
        .select2-container {
            width: 100% !important;
        }
        .select2-container--default .select2-selection--single {
            background-color: #FFFFFF !important;
            border: 1px solid #CBD5E1 !important;
            border-radius: 0.75rem !important; /* rounded-xl */
            height: 48px !important;
            display: flex !important;
            align-items: center !important;
            padding-left: 1rem !important;
            padding-right: 2.5rem !important;
            font-size: 0.8125rem !important;
            font-weight: 700 !important;
            color: #0F172A !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.04) !important;
            transition: all 0.2s ease-in-out !important;
        }
        .select2-container--default .select2-selection--single:hover {
            border-color: #94A3B8 !important;
        }
        .select2-container--default .select2-selection--single:focus,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #059669 !important; /* emerald-600 */
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.18) !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #0F172A !important;
            line-height: 46px !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            font-weight: 700 !important;
            font-size: 0.8125rem !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #94A3B8 !important;
            font-weight: 500 !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 46px !important;
            right: 14px !important;
            width: 16px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow b {
            border-color: #64748B transparent transparent transparent !important;
            border-width: 5px 5px 0 5px !important;
        }
        .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
            border-color: transparent transparent #059669 transparent !important;
            border-width: 0 5px 5px 5px !important;
        }
        /* DROPDOWN OPTIONS LIST POPUP CONTAINER - REDUCED COMPACT WIDTH */
        .select2-dropdown {
            border: 1px solid #E2E8F0 !important;
            border-radius: 0.875rem !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.03) !important;
            overflow: hidden !important;
            z-index: 9999 !important;
            background: #FFFFFF !important;
            padding: 4px !important;
            width: 240px !important;
            min-width: 220px !important;
            max-width: 260px !important;
        }
        .select2-results__option {
            padding: 8px 12px !important;
            font-size: 0.8125rem !important;
            font-weight: 700 !important;
            color: #1E293B !important;
            border-radius: 0.5rem !important;
            margin-bottom: 2px !important;
            transition: all 0.15s ease !important;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #ECFDF5 !important;
            color: #047857 !important;
        }
        .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #D1FAE5 !important;
            color: #065F46 !important;
            font-weight: 800 !important;
        }
        .select2-search--dropdown {
            padding: 4px 4px 6px 4px !important;
        }
        .select2-search--dropdown .select2-search__field {
            border: 1px solid #E2E8F0 !important;
            border-radius: 0.5rem !important;
            padding: 6px 10px !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            outline: none !important;
            background-color: #F8FAFC !important;
            color: #0F172A !important;
        }
        .select2-search--dropdown .select2-search__field:focus {
            border-color: #059669 !important;
            background-color: #FFFFFF !important;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-slate-50 text-slate-900 antialiased select-none">

    <div class="flex h-screen overflow-hidden">

        <!-- GENERAL SIDEBAR -->
        @include('staff.partials.sidebar', ['sidebarUnit' => 'Forensic Directorate'])

        <!-- RIGHT WORKSPACE -->
        <div class="flex-1 flex flex-col min-w-0 bg-white overflow-hidden select-text">
            <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 shadow-2xs">
                <div class="flex items-center flex-1 max-w-lg">
                    <div class="relative w-full flex items-center group">
                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-blue-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input type="text" placeholder="Search Arrested Docket #, Suspect Name, Ghana Card, Fingerprint ID..." class="w-full pl-10 pr-4 py-2 bg-slate-100 border-none rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:ring-2 focus:ring-blue-600/20 transition-all shadow-inner">
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ url('/Forensic_Head/staff/case') }}" class="px-4 py-2 bg-slate-200/80 hover:bg-slate-300 border-none text-slate-800 text-xs font-bold rounded-xl shadow-xs transition-all flex items-center space-x-1.5">
                        <span>&larr;</span>
                        <span>Back to Case Register</span>
                    </a>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-6 space-y-6">

                <!-- BANNER -->
                <div class="bg-slate-100 p-6 rounded-2xl border-none shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center space-x-2 mb-1">
                            <span class="px-2.5 py-0.5 bg-white text-emerald-700 border border-slate-200/90 shadow-2xs text-[10px] font-extrabold font-mono-code rounded-md uppercase tracking-wider">SUSPECT DOCKET ENTRY</span>
                            <span class="text-xs text-slate-300">|</span>
                            <span class="text-xs font-bold text-slate-700 font-mono-code">NACOC FORENSIC DIVISION</span>
                        </div>
                        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Arrested Person (Suspect Docket Portal)</h1>
                        <p class="text-xs text-slate-500 mt-1">Official Field Arrested Logging, Biometric Fingerprint Registrations &amp; Custody Cell Assignments</p>
                    </div>
                    
                </div>

                <!-- CONDITIONAL CARGO / PARCEL FORM -->
                @include('partials.cargo_parcel_form')

                <!-- DOCKET LOGGING FORM -->
                <form id="arrestDocketForm" autocomplete="off" onsubmit="event.preventDefault(); processNewArrested();" onreset="setTimeout(clearDocketFormDraftAndReset, 50)" class="space-y-6 text-xs font-medium">
                    @csrf

                    @include('partials.optional_sections_1_to_3_toggle')

                    <div id="sections1To3Wrapper" class="space-y-6">
                    <!-- SECTION 1: MASTER CASE TITLE & DOCKET CLASSIFICATION -->
                    <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm space-y-5 relative z-50">
                        <div class="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-xl bg-white text-emerald-600 border border-slate-200/90 flex items-center justify-center font-bold shadow-md shadow-slate-200/60">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 01-2-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-slate-900 text-base">Section 1: Case Title &amp; Classification</h3>
                                    <p class="text-xs text-slate-500">Specify the official legal case title and select single or multiple suspect docket mode</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span id="caseTypeBadge" class="px-2.5 py-1 bg-emerald-50 text-emerald-700 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider">
                                    Single Person Mode
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                            <!-- CASE NAME INPUT -->
                            <div>
                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                    Case Reference / Legal Title <span class="text-red-600 font-bold">*</span>
                                </label>
                                <div class="relative flex items-center group">
                                    <span class="absolute left-3.5 text-slate-400 group-focus-within:text-blue-600 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                                    </span>
                                    <input 
                                        type="text" 
                                        id="caseTitleInput" 
                                        value="Republic vs "
                                        placeholder="e.g. Republic vs Atto &amp; Ors" 
                                        required 
                                        class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400 placeholder:font-normal"
                                    >
                                </div>
                            </div>

                            <!-- OPERATION TYPE CUSTOM DROPDOWN -->
                            <div class="relative z-30">
                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                    <span>Operation Type</span>
                                    <span class="text-[9px] font-mono-code text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded font-bold uppercase">Optional</span>
                                </label>
                                <button type="button" id="operationTypeDropdownTrigger" onclick="toggleOperationTypeDropdown(event)" class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 flex items-center justify-between hover:border-slate-300 outline-none focus:ring-2 focus:ring-emerald-600/20 focus:border-emerald-600 transition-all shadow-2xs group cursor-pointer">
                                    <span id="selectedOpTypeLabel" class="font-bold text-slate-500 text-xs">Select Operation Type...</span>
                                    <input type="hidden" id="operationTypeSelect" name="operation_type" value="">
                                    <svg id="opTypeChevron" class="w-4 h-4 text-slate-400 group-hover:text-blue-600 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>

                                <div id="operationTypeDropdownMenu" class="hidden absolute left-0 right-0 mt-1.5 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 p-2 space-y-1">
                                    <button type="button" onclick="selectOperationType('', 'Select Operation Type...', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-500 hover:bg-slate-50 rounded-xl flex items-center justify-between transition-all group cursor-pointer border-b border-slate-100 mb-1">
                                        <div>
                                            <div class="font-bold text-slate-500 group-hover:text-slate-800 transition-colors">Select Operation Type...</div>
                                            <div class="text-[10px] text-slate-400 font-normal mt-0.5">Clear operation selection</div>
                                        </div>
                                    </button>

                                    <button type="button" onclick="selectOperationType('Cargo/Parcel', 'Cargo / Parcel', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-amber-50 rounded-xl flex items-center justify-between transition-all group cursor-pointer">
                                        <div>
                                            <div class="font-extrabold text-slate-900 group-hover:text-amber-800 transition-colors">Cargo / Parcel</div>
                                            <div class="text-[10px] text-slate-500 font-normal mt-0.5">Freight, courier &amp; airport cargo seizures</div>
                                        </div>
                                        <span class="px-2 py-0.5 bg-amber-50 text-amber-700 text-[10px] font-mono-code font-bold rounded-lg border border-amber-200 uppercase">Cargo</span>
                                    </button>

                                    <button type="button" onclick="selectOperationType('Raid', 'Raid', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-red-50 rounded-xl flex items-center justify-between transition-all group cursor-pointer">
                                        <div>
                                            <div class="font-extrabold text-slate-900 group-hover:text-red-700 transition-colors">Raid</div>
                                            <div class="text-[10px] text-slate-500 font-normal mt-0.5">Tactical strike &amp; field arrest ops</div>
                                        </div>
                                        <span class="px-2 py-0.5 bg-red-50 text-red-700 text-[10px] font-mono-code font-bold rounded-lg border border-red-200 uppercase">Raid</span>
                                    </button>

                                    <button type="button" onclick="selectOperationType('Search Warrant', 'Search Warrant', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-blue-50 rounded-xl flex items-center justify-between transition-all group cursor-pointer">
                                        <div>
                                            <div class="font-extrabold text-slate-900 group-hover:text-blue-700 transition-colors">Search Warrant</div>
                                            <div class="text-[10px] text-slate-500 font-normal mt-0.5">Court-ordered property search</div>
                                        </div>
                                        <span class="px-2 py-0.5 bg-blue-50 text-blue-700 text-[10px] font-mono-code font-bold rounded-lg border border-blue-200 uppercase">Warrant</span>
                                    </button>

                                    <button type="button" onclick="selectOperationType('Intel-led Ops', 'Intel-led Ops', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between transition-all group cursor-pointer">
                                        <div>
                                            <div class="font-extrabold text-slate-900 group-hover:text-emerald-700 transition-colors">Intel-led Ops</div>
                                            <div class="text-[10px] text-slate-500 font-normal mt-0.5">Intelligence &amp; surveillance operation</div>
                                        </div>
                                        <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase">Intel</span>
                                    </button>

                                    <button type="button" onclick="selectOperationType('Transferred Case', 'Transferred Case', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-purple-50 rounded-xl flex items-center justify-between transition-all group cursor-pointer">
                                        <div>
                                            <div class="font-extrabold text-slate-900 group-hover:text-purple-700 transition-colors">Transferred Case</div>
                                            <div class="text-[10px] text-slate-500 font-normal mt-0.5">External agency / police referral</div>
                                        </div>
                                        <span class="px-2 py-0.5 bg-purple-50 text-purple-700 text-[10px] font-mono-code font-bold rounded-lg border border-purple-200 uppercase">Transfer</span>
                                    </button>
                                </div>
                            </div>

                            <!-- CASE PRIORITY DROPDOWN (HIGH / LOW) -->
                            <div class="relative z-30">
                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                    <span>Case Priority <span class="text-red-600 font-bold">*</span></span>
                                    <span class="text-[9px] font-mono-code text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded font-bold uppercase">Urgency</span>
                                </label>
                                <button type="button" id="priorityDropdownTrigger" onclick="togglePriorityDropdown(event)" class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 flex items-center justify-between hover:border-slate-300 outline-none focus:ring-2 focus:ring-emerald-600/20 focus:border-emerald-600 transition-all shadow-2xs group cursor-pointer">
                                    <div class="flex items-center space-x-2">
                                        <span id="selectedPriorityLabel" class="font-bold text-slate-500 text-xs">Select Case Priority...</span>
                                    </div>
                                    <input type="hidden" id="casePrioritySelect" name="priority" value="">
                                    <svg id="priorityChevron" class="w-4 h-4 text-slate-400 group-hover:text-blue-600 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>

                                <div id="priorityDropdownMenu" class="hidden absolute left-0 right-0 mt-1.5 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 p-2 space-y-1">
                                    <button type="button" onclick="selectPriority('', 'Select Case Priority...', 'bg-slate-300', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-500 hover:bg-slate-50 rounded-xl flex items-center justify-between transition-all group cursor-pointer border-b border-slate-100 mb-1">
                                        <div>
                                            <div class="font-bold text-slate-500 group-hover:text-slate-800 transition-colors">Select Case Priority...</div>
                                            <div class="text-[10px] text-slate-400 font-normal mt-0.5">Clear priority selection</div>
                                        </div>
                                    </button>

                                    <button type="button" onclick="selectPriority('HIGH', 'High Priority', 'bg-red-600', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-red-50 rounded-xl flex items-center justify-between transition-all group cursor-pointer">
                                        <div class="flex items-center space-x-2">
                                            <div>
                                                <div class="font-extrabold text-slate-900 group-hover:text-red-700 transition-colors">High Priority</div>
                                                <div class="text-[10px] text-slate-500 font-normal mt-0.5">Urgent multi-unit processing &amp; priority HOF review</div>
                                            </div>
                                        </div>
                                        <span class="px-2 py-0.5 bg-red-100 text-red-700 text-[10px] font-mono-code font-bold rounded-lg border border-red-200 uppercase">High</span>
                                    </button>

                                    <button type="button" onclick="selectPriority('LOW', 'Low Priority', 'bg-slate-500', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-800 hover:bg-slate-100 rounded-xl flex items-center justify-between transition-all group cursor-pointer">
                                        <div class="flex items-center space-x-2">
                                            <div>
                                                <div class="font-extrabold text-slate-900 group-hover:text-slate-800 transition-colors">Low Priority</div>
                                                <div class="text-[10px] text-slate-500 font-normal mt-0.5">Standard routine forensic investigation docket</div>
                                            </div>
                                        </div>
                                        <span class="px-2 py-0.5 bg-slate-100 text-slate-600 text-[10px] font-mono-code font-bold rounded-lg border border-slate-200 uppercase">Low</span>
                                    </button>
                                </div>
                            </div>

                            <!-- CASE ENTRY TYPE CUSTOM DROPDOWN -->
                            <div class="relative z-30">
                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                    Suspect Docket Mode <span class="text-red-600 font-bold">*</span>
                                </label>
                                <button type="button" id="caseEntryTypeDropdownTrigger" onclick="toggleCaseEntryTypeDropdown(event)" class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 flex items-center justify-between hover:border-slate-300 outline-none focus:ring-2 focus:ring-emerald-600/20 focus:border-emerald-600 transition-all shadow-2xs group cursor-pointer">
                                    <span id="selectedCaseTypeLabel" class="font-extrabold text-slate-500 text-xs">Select Suspect Docket Mode</span>
                                    <input type="hidden" id="caseEntryTypeSelect" value="">
                                    <svg id="caseTypeChevron" class="w-4 h-4 text-slate-400 group-hover:text-blue-600 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>

                                <div id="caseEntryTypeDropdownMenu" class="hidden absolute left-0 right-0 mt-1.5 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 p-2 space-y-1 min-w-[280px]">
                                    <button type="button" onclick="selectCaseEntryType('', 'Select Suspect Docket Mode', event)" class="w-full text-left p-2.5 text-xs font-bold text-slate-500 hover:bg-slate-50 rounded-xl flex items-center justify-between transition-all group cursor-pointer border-b border-slate-100 mb-1">
                                        <div>
                                            <div class="font-bold text-slate-500 group-hover:text-slate-800 transition-colors">Select Suspect Docket Mode</div>
                                            <div class="text-[10px] text-slate-400 font-normal mt-0.5">Clear docket mode selection</div>
                                        </div>
                                    </button>

                                    <button type="button" onclick="selectCaseEntryType('SINGLE', 'Single Person Case', event)" class="w-full text-left p-3 text-xs font-bold text-slate-800 hover:bg-emerald-50/80 rounded-xl flex items-center justify-between transition-all group cursor-pointer">
                                        <div>
                                            <div class="font-extrabold text-slate-900 group-hover:text-emerald-700 transition-colors">Single Person Case</div>
                                            <div class="text-[10px] text-slate-500 font-normal mt-0.5">Standard docket entry for one primary suspect</div>
                                        </div>
                                        <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200/80 uppercase">1 Suspect</span>
                                    </button>

                                    <button type="button" onclick="selectCaseEntryType('MULTIPLE', 'Multiple People Case (Joint Suspects)', event)" class="w-full text-left p-3 text-xs font-bold text-slate-800 hover:bg-amber-50/80 rounded-xl flex items-center justify-between transition-all group cursor-pointer">
                                        <div>
                                            <div class="font-extrabold text-slate-900 group-hover:text-amber-800 transition-colors">Multiple People Case (Joint Suspects)</div>
                                            <div class="text-[10px] text-slate-500 font-normal mt-0.5">Syndicate Arrested - enables multi-row suspect &amp; mugshot additions</div>
                                        </div>
                                        <span class="px-2 py-0.5 bg-amber-50 text-amber-800 text-[10px] font-mono-code font-bold rounded-lg border border-amber-200/80 uppercase">Multi-Row</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    @include('partials.search_warrant_upload_section')

                    <!-- SECTION 2: MUGSHOT PHOTO UPLOAD -->
                    <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm space-y-5 relative z-40">
                        <div class="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-xl bg-white text-emerald-600 border border-slate-200/90 flex items-center justify-center font-bold shadow-md shadow-slate-200/60">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-slate-900 text-base">Section 2: Mugshot (Multi-Angle Photo Capture)</h3>
                                    <p class="text-xs text-slate-500">Mandatory suspect facial photo uploads for 3 required angles (Excluding back angle per protocol)</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button type="button" id="addMugshotRowBtn" onclick="addMugshotRow()" class="hidden px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center space-x-1.5 cursor-pointer border-none">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    <span>Add Mugshot Row</span>
                                </button>
                                <span class="px-2.5 py-1 bg-slate-100 text-slate-700 text-[10px] font-mono-code font-bold rounded-lg border-none uppercase tracking-wider">
                                    3 Angles Required
                                </span>
                            </div>
                        </div>

                        <!-- MUGSHOT ROWS CONTAINER -->
                        <div id="mugshotsContainer" class="space-y-6">
                            <div id="mugshotCard-1" class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2">
                                        <div class="text-xs font-bold text-slate-700 font-mono-code uppercase">Suspect #1 Mugshot Photos</div>
                                        <span class="px-2 py-0.5 text-[9px] font-mono-code font-extrabold rounded-md bg-emerald-100 text-emerald-800 border border-emerald-300 uppercase">PRIMARY SUSPECT</span>
                                    </div>
                                    <span class="text-[10px] font-mono-code text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                        Click frame or drop image to upload
                                    </span>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                    <!-- ANGLE 1: FRONTAL VIEW -->
                                    <div class="bg-white p-4 rounded-2xl border border-slate-200 text-center space-y-3 flex flex-col justify-between hover:border-emerald-500 transition-colors shadow-2xs">
                                        <div class="flex items-center justify-between">
                                            <span class="px-2 py-0.5 rounded text-[9px] font-bold font-mono-code bg-emerald-600 text-white uppercase">Angle 1</span>
                                            <span class="text-[10px] font-bold text-slate-700 font-mono-code">Front View (0&deg;)</span>
                                            <span id="badge-front" class="text-[9px] font-mono-code font-bold text-slate-400 uppercase">Not Uploaded</span>
                                        </div>
                                        <div 
                                            id="dropzone-front"
                                            onclick="triggerMugshotUpload('front')"
                                            ondragover="handleArrestMugshotDragOver(event, this)"
                                            ondragleave="handleArrestMugshotDragLeave(event, this)"
                                            ondrop="handleArrestMugshotDrop(event, 'front')"
                                            class="h-48 w-full bg-slate-50 hover:bg-emerald-50/60 rounded-2xl border-2 border-dashed border-slate-300 hover:border-emerald-500 flex flex-col items-center justify-center overflow-hidden relative group transition-all duration-200 cursor-pointer shadow-2xs"
                                        >
                                            <img id="preview-front" class="hidden w-full h-full object-contain p-1 transition-all duration-200" alt="Front Mugshot">
                                            <div id="placeholder-front" class="flex flex-col items-center justify-center p-3 text-slate-400 space-y-2 pointer-events-none">
                                                <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-xs">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                </div>
                                                <span class="text-xs font-bold text-slate-700">Click or Drag &amp; Drop Photo</span>
                                                <span class="text-[9px] font-mono-code text-slate-400">Full Face Direct View (0&deg;)</span>
                                            </div>
                                            <div id="actions-front" class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs opacity-0 group-hover:opacity-100 flex items-center justify-center space-x-2 transition-opacity duration-200 hidden" onclick="event.stopPropagation()">
                                                <button type="button" onclick="openArrestMugshotModal(document.getElementById('preview-front').src, 'Front View (0&deg;) - Suspect #1 Mugshot')" class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold rounded-xl transition-all shadow-md flex items-center space-x-1 cursor-pointer border-none">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                    <span>View Full</span>
                                                </button>
                                                <button type="button" onclick="clearArrestMugshotPhoto('front')" class="px-2.5 py-1.5 bg-red-600 hover:bg-red-700 text-white text-[10px] font-bold rounded-xl transition-all shadow-md flex items-center space-x-1 cursor-pointer border-none">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    <span>Clear</span>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="space-y-1.5 pt-1">
                                            <input type="file" id="file-front" accept="image/*" class="hidden" onchange="previewMugshot(event, 'front')">
                                            <button type="button" onclick="triggerMugshotUpload('front')" class="w-full py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-[11px] font-extrabold rounded-xl border border-emerald-200 transition-colors flex items-center justify-center space-x-1 cursor-pointer">
                                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                                <span>Choose Front Photo</span>
                                            </button>
                                            <input 
                                                type="text" 
                                                id="url-front" 
                                                placeholder="Or paste image URL..." 
                                                oninput="previewMugshotUrlArrest(this.value, 'front')" 
                                                class="w-full px-2.5 py-1.5 text-[10px] bg-slate-50 border border-slate-200 rounded-xl font-mono-code text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-emerald-500 focus:bg-white transition-all"
                                            >
                                            <input type="hidden" name="mugshot_front" id="hidden-front">
                                        </div>
                                    </div>

                                    <!-- ANGLE 2: LEFT PROFILE -->
                                    <div class="bg-white p-4 rounded-2xl border border-slate-200 text-center space-y-3 flex flex-col justify-between hover:border-emerald-500 transition-colors shadow-2xs">
                                        <div class="flex items-center justify-between">
                                            <span class="px-2 py-0.5 rounded text-[9px] font-bold font-mono-code bg-slate-800 text-white uppercase">Angle 2</span>
                                            <span class="text-[10px] font-bold text-slate-700 font-mono-code">Left Profile (90&deg;)</span>
                                            <span id="badge-left-profile" class="text-[9px] font-mono-code font-bold text-slate-400 uppercase">Not Uploaded</span>
                                        </div>
                                        <div 
                                            id="dropzone-left-profile"
                                            onclick="triggerMugshotUpload('left-profile')"
                                            ondragover="handleArrestMugshotDragOver(event, this)"
                                            ondragleave="handleArrestMugshotDragLeave(event, this)"
                                            ondrop="handleArrestMugshotDrop(event, 'left-profile')"
                                            class="h-48 w-full bg-slate-50 hover:bg-emerald-50/60 rounded-2xl border-2 border-dashed border-slate-300 hover:border-emerald-500 flex flex-col items-center justify-center overflow-hidden relative group transition-all duration-200 cursor-pointer shadow-2xs"
                                        >
                                            <img id="preview-left-profile" class="hidden w-full h-full object-contain p-1 transition-all duration-200" alt="Left Profile Mugshot">
                                            <div id="placeholder-left-profile" class="flex flex-col items-center justify-center p-3 text-slate-400 space-y-2 pointer-events-none">
                                                <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-xs">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                </div>
                                                <span class="text-xs font-bold text-slate-700">Click or Drag &amp; Drop Photo</span>
                                                <span class="text-[9px] font-mono-code text-slate-400">90&deg; Full Left Side Profile</span>
                                            </div>
                                            <div id="actions-left-profile" class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs opacity-0 group-hover:opacity-100 flex items-center justify-center space-x-2 transition-opacity duration-200 hidden" onclick="event.stopPropagation()">
                                                <button type="button" onclick="openArrestMugshotModal(document.getElementById('preview-left-profile').src, 'Left Profile (90&deg;) - Suspect #1 Mugshot')" class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold rounded-xl transition-all shadow-md flex items-center space-x-1 cursor-pointer border-none">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                    <span>View Full</span>
                                                </button>
                                                <button type="button" onclick="clearArrestMugshotPhoto('left-profile')" class="px-2.5 py-1.5 bg-red-600 hover:bg-red-700 text-white text-[10px] font-bold rounded-xl transition-all shadow-md flex items-center space-x-1 cursor-pointer border-none">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    <span>Clear</span>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="space-y-1.5 pt-1">
                                            <input type="file" id="file-left-profile" accept="image/*" class="hidden" onchange="previewMugshot(event, 'left-profile')">
                                            <button type="button" onclick="triggerMugshotUpload('left-profile')" class="w-full py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-[11px] font-extrabold rounded-xl border border-emerald-200 transition-colors flex items-center justify-center space-x-1 cursor-pointer">
                                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                                <span>Choose Left Profile</span>
                                            </button>
                                            <input 
                                                type="text" 
                                                id="url-left-profile" 
                                                placeholder="Or paste image URL..." 
                                                oninput="previewMugshotUrlArrest(this.value, 'left-profile')" 
                                                class="w-full px-2.5 py-1.5 text-[10px] bg-slate-50 border border-slate-200 rounded-xl font-mono-code text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-emerald-500 focus:bg-white transition-all"
                                            >
                                            <input type="hidden" name="mugshot_left" id="hidden-left-profile">
                                        </div>
                                    </div>

                                    <!-- ANGLE 3: RIGHT PROFILE -->
                                    <div class="bg-white p-4 rounded-2xl border border-slate-200 text-center space-y-3 flex flex-col justify-between hover:border-emerald-500 transition-colors shadow-2xs">
                                        <div class="flex items-center justify-between">
                                            <span class="px-2 py-0.5 rounded text-[9px] font-bold font-mono-code bg-slate-800 text-white uppercase">Angle 3</span>
                                            <span class="text-[10px] font-bold text-slate-700 font-mono-code">Right Profile (90&deg;)</span>
                                            <span id="badge-right-profile" class="text-[9px] font-mono-code font-bold text-slate-400 uppercase">Not Uploaded</span>
                                        </div>
                                        <div 
                                            id="dropzone-right-profile"
                                            onclick="triggerMugshotUpload('right-profile')"
                                            ondragover="handleArrestMugshotDragOver(event, this)"
                                            ondragleave="handleArrestMugshotDragLeave(event, this)"
                                            ondrop="handleArrestMugshotDrop(event, 'right-profile')"
                                            class="h-48 w-full bg-slate-50 hover:bg-emerald-50/60 rounded-2xl border-2 border-dashed border-slate-300 hover:border-emerald-500 flex flex-col items-center justify-center overflow-hidden relative group transition-all duration-200 cursor-pointer shadow-2xs"
                                        >
                                            <img id="preview-right-profile" class="hidden w-full h-full object-contain p-1 transition-all duration-200" alt="Right Profile Mugshot">
                                            <div id="placeholder-right-profile" class="flex flex-col items-center justify-center p-3 text-slate-400 space-y-2 pointer-events-none">
                                                <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-xs">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                </div>
                                                <span class="text-xs font-bold text-slate-700">Click or Drag &amp; Drop Photo</span>
                                                <span class="text-[9px] font-mono-code text-slate-400">90&deg; Full Right Side Profile</span>
                                            </div>
                                            <div id="actions-right-profile" class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs opacity-0 group-hover:opacity-100 flex items-center justify-center space-x-2 transition-opacity duration-200 hidden" onclick="event.stopPropagation()">
                                                <button type="button" onclick="openArrestMugshotModal(document.getElementById('preview-right-profile').src, 'Right Profile (90&deg;) - Suspect #1 Mugshot')" class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold rounded-xl transition-all shadow-md flex items-center space-x-1 cursor-pointer border-none">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                    <span>View Full</span>
                                                </button>
                                                <button type="button" onclick="clearArrestMugshotPhoto('right-profile')" class="px-2.5 py-1.5 bg-red-600 hover:bg-red-700 text-white text-[10px] font-bold rounded-xl transition-all shadow-md flex items-center space-x-1 cursor-pointer border-none">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    <span>Clear</span>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="space-y-1.5 pt-1">
                                            <input type="file" id="file-right-profile" accept="image/*" class="hidden" onchange="previewMugshot(event, 'right-profile')">
                                            <button type="button" onclick="triggerMugshotUpload('right-profile')" class="w-full py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-[11px] font-extrabold rounded-xl border border-emerald-200 transition-colors flex items-center justify-center space-x-1 cursor-pointer">
                                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                                <span>Choose Right Profile</span>
                                            </button>
                                            <input 
                                                type="text" 
                                                id="url-right-profile" 
                                                placeholder="Or paste image URL..." 
                                                oninput="previewMugshotUrlArrest(this.value, 'right-profile')" 
                                                class="w-full px-2.5 py-1.5 text-[10px] bg-slate-50 border border-slate-200 rounded-xl font-mono-code text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-emerald-500 focus:bg-white transition-all"
                                            >
                                            <input type="hidden" name="mugshot_right" id="hidden-right-profile">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DRAFT AUTO-SAVE RESTORED NOTICE BANNER -->
                    <div id="docketDraftNotice" class="hidden p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center justify-between shadow-2xs">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-xl bg-white text-emerald-600 border border-slate-200/90 flex items-center justify-center font-bold text-xs shrink-0 shadow-sm shadow-slate-200/50">
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                            </div>
                            <div>
                                <div class="text-xs font-extrabold text-emerald-950 flex items-center space-x-2">
                                    <span>Draft Information Restored Automatically</span>
                                    <span class="px-2 py-0.5 rounded-full text-[9px] bg-emerald-200 text-emerald-900 font-mono-code font-bold uppercase">Auto-Saved</span>
                                </div>
                                <p class="text-[11px] text-emerald-800 font-medium mt-0.5">Your previously entered docket details have been recovered so you won't lose your work on page refresh.</p>
                            </div>
                        </div>
                        <button type="button" onclick="clearDocketFormDraftAndReset()" class="px-3.5 py-1.5 bg-white border border-emerald-300 hover:bg-emerald-100 text-emerald-900 text-xs font-bold rounded-xl transition-colors shrink-0 cursor-pointer shadow-2xs">
                            Clear Saved Draft
                        </button>
                    </div>

                    <!-- SECTION 3: SUSPECT PERSONAL DETAILS & BOOKING DOCKET -->
                    <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm space-y-6 relative z-30">
                        <div class="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-xl bg-white text-emerald-600 border border-slate-200/90 flex items-center justify-center font-bold shadow-md shadow-slate-200/60">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-slate-900 text-base">Section 3: Suspect Personal Details</h3>
                                    <p class="text-xs text-slate-500">Enter suspect legal identity, origin, residential address, marital records, and custody cell allocation</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button type="button" id="addSuspectDetailsRowBtn" onclick="addSuspectDetailsRow()" class="hidden px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center space-x-1.5 cursor-pointer border-none">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    <span>Add Suspect Details Row</span>
                                </button>
                                <span class="text-xs font-mono-code font-bold text-slate-400">NACOC DOCKET FORM #AR-2026</span>
                            </div>
                        </div>

                        <!-- SUSPECTS CONTAINER -->
                        <div id="suspectsContainer" class="space-y-6">
                            <div id="suspectCard-1" class="space-y-6">

                        <!-- SUB-HEADER 3A: LEGAL IDENTITY -->
                        <div class="space-y-4 relative z-30">
                            <div class="flex items-center space-x-2 text-xs font-extrabold text-black uppercase tracking-wider border-b border-slate-100 pb-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3 3 0 00-3 3h6a3 3 0 00-3-3z"/></svg>
                                <span>3A. Legal Identity</span>
                            </div>

                            <!-- LIVE SUSPECT BIODATA DUPLICATE WARNING BANNER -->
                            <div id="suspectDuplicateNoticeCard" class="hidden mb-4 p-4 rounded-2xl bg-amber-50 border border-amber-300 shadow-sm animate-in fade-in duration-200">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-start space-x-3">
                                        <div class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-xs mt-0.5">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        </div>
                                        <div>
                                            <div class="flex items-center space-x-2">
                                                <span class="font-extrabold text-amber-900 text-xs uppercase tracking-wider font-mono-code">Existing Suspect Profile Detected</span>
                                                <span id="suspectDupMatchBadge" class="px-2 py-0.5 bg-amber-200 text-amber-900 font-mono-code font-bold text-[10px] rounded-full border border-amber-300">90% Match</span>
                                            </div>
                                            <p class="text-xs text-amber-800 mt-0.5 font-medium leading-relaxed">
                                                Suspect <strong id="suspectDupName" class="font-bold text-slate-900"></strong> (Card: <span id="dupSuspectCard" class="font-mono-code"></span>) is already registered in case <strong id="suspectDupCaseRef" class="font-mono-code text-blue-700"></strong>.
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2 shrink-0">
                                        <button type="button" onclick="viewSuspectExistingCase()" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-bold rounded-lg transition-all shadow-xs flex items-center space-x-1 cursor-pointer">
                                            <span>View Case</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </button>
                                        <button type="button" onclick="hideSuspectDuplicateNoticeCard()" class="text-amber-700 hover:text-amber-900 text-xs font-bold px-2 py-1 cursor-pointer">
                                            Dismiss
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- ROW 1: REF (READONLY), FULL NAME, ALIAS -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                
                                <!-- RAID INCIDENT REFERENCE (READONLY) -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                        <span>Raid Incident Reference</span>
                                        <span class="text-[9px] font-mono-code text-slate-600 bg-slate-200 px-2 py-0.5 rounded font-bold">READONLY</span>
                                    </label>
                                    <div class="relative flex items-center">
                                        <span class="absolute left-3.5 text-slate-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        </span>
                                        <input type="text" id="newArrestedRaidRef" value="RAID-2026-0419" readonly class="w-full pl-10 pr-3.5 py-3 bg-slate-100 border border-slate-300 rounded-xl font-mono-code font-bold text-slate-800 cursor-not-allowed select-none shadow-2xs">
                                    </div>
                                </div>

                                <!-- SUSPECT FULL LEGAL NAME -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        Suspect Full Legal Name <span class="text-red-600 font-bold">*</span>
                                    </label>
                                    <div class="relative flex items-center group">
                                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        </span>
                                        <input type="text" id="newArrestedName" placeholder="e.g. Kwame Mensah" required class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                                    </div>
                                </div>

                                <!-- ALIAS / STREET NAME -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        Alias / Street Name
                                    </label>
                                    <div class="relative flex items-center group">
                                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                        </span>
                                        <input type="text" id="newArrestedAlias" placeholder="e.g. Cobra / Jaguar" class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                                    </div>
                                </div>
                            </div>

                            <!-- ROW 2: GHANA CARD, GENDER, DOB, AGE, HEIGHT, EYE COLOR -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5 relative z-40">
                                
                                <!-- GHANA CARD # -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        Ghana Card # 
                                    </label>
                                    <div class="relative flex items-center group">
                                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3 3 0 00-3 3h6a3 3 0 00-3-3z"/></svg>
                                        </span>
                                        <input type="text" id="newArrestedID" placeholder="GHA-8840291-3" class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl font-mono-code font-bold text-slate-900 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                                    </div>
                                </div>

                                <!-- CUSTOM GENDER DROPDOWN -->
                                <div class="relative z-30">
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        Gender <span class="text-red-600 font-bold">*</span>
                                    </label>
                                    <button type="button" id="genderDropdownTrigger" onclick="toggleGenderDropdown(event)" class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 flex items-center justify-between hover:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 focus:border-emerald-600 transition-all shadow-2xs group">
                                        <div class="flex items-center space-x-2">
                                            <span id="selectedGenderIcon" class="w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-bold">?</span>
                                            <span id="selectedGenderLabel" class="font-bold text-slate-500 text-xs">Select Gender</span>
                                        </div>
                                        <input type="hidden" id="newArrestedGender" value="">
                                        <svg id="genderChevron" class="w-4 h-4 text-slate-400 group-hover:text-black transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>

                                    <div id="genderDropdownMenu" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 p-2 space-y-1">
                                        <button type="button" onclick="selectGender('', '?', 'bg-slate-100 text-slate-600', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-500 hover:bg-slate-50 hover:text-black rounded-xl flex items-center space-x-2 transition-colors border-b border-slate-100 mb-1">
                                            <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-bold">?</span>
                                            <span>Select Gender</span>
                                        </button>
                                        <button type="button" onclick="selectGender('Male', '♂', 'bg-emerald-100 text-emerald-700', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center space-x-2 transition-colors">
                                            <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-bold">♂</span>
                                            <span>Male</span>
                                        </button>
                                        <button type="button" onclick="selectGender('Female', '♀', 'bg-rose-100 text-rose-700', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center space-x-2 transition-colors">
                                            <span class="w-5 h-5 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center text-[10px] font-bold">♀</span>
                                            <span>Female</span>
                                        </button>
                                        <button type="button" onclick="selectGender('Other / Non-Binary', '⚧', 'bg-purple-100 text-purple-700', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center space-x-2 transition-colors">
                                            <span class="w-5 h-5 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center text-[10px] font-bold">⚧</span>
                                            <span>Other / Non-Binary</span>
                                        </button>
                                        <button type="button" onclick="selectGender('N/A', '?', 'bg-slate-100 text-slate-600', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center space-x-2 transition-colors">
                                            <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-bold">?</span>
                                            <span>N/A</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- DATE OF BIRTH (NEW) -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        Date of Birth <span class="text-red-600 font-bold">*</span>
                                    </label>
                                    <div class="relative flex items-center group">
                                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </span>
                                        <input type="date" id="newArrestedDOB" value="" onchange="calculateAgeFromDOB()" oninput="calculateAgeFromDOB()" required class="w-full pl-10 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-mono-code font-bold text-slate-900 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs">
                                    </div>
                                </div>

                                <!-- AGE -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                        <span>Age (Years) <span class="text-red-600 font-bold">*</span></span>
                                        <span class="text-[9px] font-mono-code text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded font-bold uppercase">Read-Only (Auto-Calc)</span>
                                    </label>
                                    <div class="relative flex items-center group">
                                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black font-mono-code font-bold text-xs transition-colors">#</span>
                                        <input type="number" id="newArrestedAge" value="" placeholder="Auto-calculated age..." min="0" max="120" readonly required class="w-full pl-10 pr-3.5 py-3 bg-slate-100 border border-slate-300 rounded-xl font-bold text-slate-800 cursor-not-allowed select-none focus:outline-none shadow-2xs placeholder:text-slate-400 placeholder:font-normal">
                                    </div>
                                </div>

                                <!-- HEIGHT -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        Height (cm / ft-in)
                                    </label>
                                    <div class="relative flex items-center group">
                                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors z-10">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                        </span>
                                        <input type="text" id="newArrestedHeight" placeholder="e.g. 175" class="w-full pl-10 pr-24 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                                        <div class="absolute right-1.5 flex items-center">
                                            <select id="newArrestedHeightUnit" class="py-1.5 px-2 bg-slate-100 hover:bg-slate-200 text-slate-800 font-mono-code font-black text-xs border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 cursor-pointer transition-colors uppercase">
                                                <option value="CM" selected>CM</option>
                                                <option value="IN">IN</option>
                                                <option value="FT">FT</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- EYE COLOR -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        Eye Color
                                    </label>
                                    <div class="relative flex items-center group">
                                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </span>
                                        <input type="text" id="newArrestedEyeColor" placeholder="e.g. Brown / Black / Blue" class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                                    </div>
                                </div>
                            </div>

                            <!-- ROW 3: NATIONALITY, PLACE OF BIRTH, RESIDENTIAL ADDRESS -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 relative z-30">
                                
                                <!-- CUSTOM NATIONALITY DROPDOWN (MATCHING GENDER DROPDOWN DESIGN) -->
                                <div class="relative z-30">
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                        <span>Nationality <span class="text-red-600 font-bold">*</span></span>
                                        <span class="text-[9px] font-mono-code text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded font-bold uppercase">Search / Custom</span>
                                    </label>
                                    <button type="button" id="nationalityDropdownTrigger" onclick="toggleNationalityDropdown(event)" class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 flex items-center justify-between hover:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 focus:border-emerald-600 transition-all shadow-2xs group cursor-pointer">
                                        <div class="flex items-center space-x-2">
                                            <span id="selectedNationalityIcon" class="w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-bold">NAT</span>
                                            <span id="selectedNationalityLabel" class="font-bold text-slate-500 text-xs">Select Nationality</span>
                                        </div>
                                        <input type="hidden" id="newArrestedNationality" name="newArrestedNationality" value="">
                                        <svg id="nationalityChevron" class="w-4 h-4 text-slate-400 group-hover:text-black transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>

                                    <div id="nationalityDropdownMenu" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 p-2 space-y-1 max-h-64 overflow-y-auto custom-scrollbar">
                                        <div class="p-1 pb-2 sticky top-0 bg-white z-10 border-b border-slate-100">
                                            <input type="text" id="nationalitySearchInput" onkeyup="filterNationalities()" onkeydown="handleNatSearchKeydown(event, false)" placeholder="Search nationality..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:bg-white focus:border-emerald-600 outline-none placeholder:text-slate-400">
                                        </div>
                                        <button type="button" onclick="selectNationality('', 'NAT', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-500 hover:bg-slate-50 hover:text-black rounded-xl flex items-center space-x-2 transition-colors border-b border-slate-100 mb-1 nat-option">
                                            <span>Select Nationality</span>
                                        </button>
                                            @php
                                                $allNationalitiesList = [
                                                    ['name' => 'Ghanaian', 'code' => 'GH'],
                                                    ['name' => 'Nigerian', 'code' => 'NG'],
                                                    ['name' => 'Togolese', 'code' => 'TG'],
                                                    ['name' => 'Ivorian (Ivory Coast)', 'code' => 'CI'],
                                                    ['name' => 'Burkinabe (Burkina Faso)', 'code' => 'BF'],
                                                    ['name' => 'Malian', 'code' => 'ML'],
                                                    ['name' => 'Liberian', 'code' => 'LR'],
                                                    ['name' => 'Sierra Leonean', 'code' => 'SL'],
                                                    ['name' => 'Beninese', 'code' => 'BJ'],
                                                    ['name' => 'Guinean', 'code' => 'GN'],
                                                    ['name' => 'Senegalese', 'code' => 'SN'],
                                                    ['name' => 'Gambian', 'code' => 'GM'],
                                                    ['name' => 'Nigerien (Niger)', 'code' => 'NE'],
                                                    ['name' => 'Chadian', 'code' => 'TD'],
                                                    ['name' => 'Cameroonian', 'code' => 'CM'],
                                                    ['name' => 'Gabonese', 'code' => 'GA'],
                                                    ['name' => 'Congolese (DRC / Republic)', 'code' => 'CG'],
                                                    ['name' => 'Angolan', 'code' => 'AO'],
                                                    ['name' => 'South African', 'code' => 'ZA'],
                                                    ['name' => 'Kenyan', 'code' => 'KE'],
                                                    ['name' => 'Ethiopian', 'code' => 'ET'],
                                                    ['name' => 'Ugandan', 'code' => 'UG'],
                                                    ['name' => 'Tanzanian', 'code' => 'TZ'],
                                                    ['name' => 'Rwandan', 'code' => 'RW'],
                                                    ['name' => 'Zimbabwean', 'code' => 'ZW'],
                                                    ['name' => 'Sudanese', 'code' => 'SD'],
                                                    ['name' => 'Egyptian', 'code' => 'EG'],
                                                    ['name' => 'Moroccan', 'code' => 'MA'],
                                                    ['name' => 'Algerian', 'code' => 'DZ'],
                                                    ['name' => 'Tunisian', 'code' => 'TN'],
                                                    ['name' => 'Libyan', 'code' => 'LY'],
                                                    ['name' => 'Somali', 'code' => 'SO'],
                                                    ['name' => 'Zambian', 'code' => 'ZM'],
                                                    ['name' => 'Mozambican', 'code' => 'MZ'],
                                                    ['name' => 'Malawian', 'code' => 'MW'],
                                                    ['name' => 'British (United Kingdom)', 'code' => 'GB'],
                                                    ['name' => 'American (USA)', 'code' => 'US'],
                                                    ['name' => 'Canadian', 'code' => 'CA'],
                                                    ['name' => 'German', 'code' => 'DE'],
                                                    ['name' => 'French', 'code' => 'FR'],
                                                    ['name' => 'Italian', 'code' => 'IT'],
                                                    ['name' => 'Spanish', 'code' => 'ES'],
                                                    ['name' => 'Dutch (Netherlands)', 'code' => 'NL'],
                                                    ['name' => 'Swiss', 'code' => 'CH'],
                                                    ['name' => 'Portuguese', 'code' => 'PT'],
                                                    ['name' => 'Irish', 'code' => 'IE'],
                                                    ['name' => 'Belgian', 'code' => 'BE'],
                                                    ['name' => 'Swedish', 'code' => 'SE'],
                                                    ['name' => 'Norwegian', 'code' => 'NO'],
                                                    ['name' => 'Danish', 'code' => 'DK'],
                                                    ['name' => 'Finnish', 'code' => 'FI'],
                                                    ['name' => 'Polish', 'code' => 'PL'],
                                                    ['name' => 'Ukrainian', 'code' => 'UA'],
                                                    ['name' => 'Russian', 'code' => 'RU'],
                                                    ['name' => 'Chinese', 'code' => 'CN'],
                                                    ['name' => 'Indian', 'code' => 'IN'],
                                                    ['name' => 'Pakistani', 'code' => 'PK'],
                                                    ['name' => 'Bangladeshi', 'code' => 'BD'],
                                                    ['name' => 'Japanese', 'code' => 'JP'],
                                                    ['name' => 'South Korean', 'code' => 'KR'],
                                                    ['name' => 'Vietnamese', 'code' => 'VN'],
                                                    ['name' => 'Indonesian', 'code' => 'ID'],
                                                    ['name' => 'Malaysian', 'code' => 'MY'],
                                                    ['name' => 'Filipino', 'code' => 'PH'],
                                                    ['name' => 'Thai', 'code' => 'TH'],
                                                    ['name' => 'Singaporean', 'code' => 'SG'],
                                                    ['name' => 'Australian', 'code' => 'AU'],
                                                    ['name' => 'New Zealander', 'code' => 'NZ'],
                                                    ['name' => 'Brazilian', 'code' => 'BR'],
                                                    ['name' => 'Argentine', 'code' => 'AR'],
                                                    ['name' => 'Colombian', 'code' => 'CO'],
                                                    ['name' => 'Mexican', 'code' => 'MX'],
                                                    ['name' => 'Jamaican', 'code' => 'JM'],
                                                    ['name' => 'Lebanese', 'code' => 'LB'],
                                                    ['name' => 'Syrian', 'code' => 'SY'],
                                                    ['name' => 'Turkish', 'code' => 'TR'],
                                                    ['name' => 'Iranian', 'code' => 'IR'],
                                                    ['name' => 'Iraqi', 'code' => 'IQ'],
                                                    ['name' => 'Israeli', 'code' => 'IL'],
                                                    ['name' => 'Saudi Arabian', 'code' => 'SA'],
                                                    ['name' => 'Emirati', 'code' => 'AE'],
                                                    ['name' => 'Other / Dual Nationality', 'code' => 'INT'],
                                                ];
                                            @endphp
                                            @foreach($allNationalitiesList as $nat)
                                                <button type="button" onclick="selectNationality('{{ $nat['name'] }}', '{{ $nat['code'] }}', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors nat-option">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-bold">{{ $nat['code'] }}</span>
                                                        <span>{{ $nat['name'] }}</span>
                                                    </div>
                                                    <span class="text-[10px] font-mono-code text-slate-400 font-bold">{{ $nat['code'] }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <!-- PLACE OF BIRTH (NEW) -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        Place of Birth <span class="text-red-600 font-bold">*</span>
                                    </label>
                                    <div class="relative flex items-center group">
                                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        </span>
                                        <input type="text" id="newArrestedPOB" placeholder="e.g. Kumasi, Ashanti Region" required class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                                    </div>
                                </div>

                                <!-- RESIDENTIAL ADDRESS (NEW) -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        Residential Address / GPS <span class="text-red-600 font-bold">*</span>
                                    </label>
                                    <div class="relative flex items-center group">
                                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                        </span>
                                        <input type="text" id="newArrestedAddress" placeholder="e.g. H/No 14 East Legon, Accra (GA-142-8809)" required class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                                    </div>
                                </div>

                            </div>

                            <!-- ROW 4: CONTACT PHONE NUMBER (MULTI-PHONE SUPPORT - FULL WIDTH CARD) -->
                            <div class="relative z-10 pt-1">
                                @include('partials.suspect-phone-input', ['unit' => 'crime_scene'])
                            </div>
                        </div>

                        <!-- SUB-HEADER 3B: FAMILY & MARITAL RECORDS -->
                        <div class="space-y-4 pt-2 relative z-20">
                            <div class="flex items-center space-x-2 text-xs font-extrabold text-black uppercase tracking-wider border-b border-slate-100 pb-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                <span>3B. Marital Record, Spouse / Partner Details &amp; Dependents</span>
                            </div>

                            <!-- ROW 4: MARITAL STATUS & NUMBER OF CHILDREN -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 relative z-30">
                                
                                <!-- CUSTOM MARITAL STATUS DROPDOWN -->
                                <div class="relative z-30">
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        Marital / Relationship Status <span class="text-red-600 font-bold">*</span>
                                    </label>
                                    <button type="button" id="maritalDropdownTrigger" onclick="toggleMaritalDropdown(event)" class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl flex items-center justify-between hover:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 focus:border-emerald-600 transition-all shadow-2xs group cursor-pointer">
                                        <div class="flex items-center space-x-2">
                                            <span id="selectedMaritalIcon" class="w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-bold">MS</span>
                                            <span id="selectedMaritalLabel" class="font-bold text-slate-500 text-xs">Select Marital / Relationship Status</span>
                                        </div>
                                        <input type="hidden" id="newArrestedMarital" name="marital_status" value="">
                                        <svg id="maritalChevron" class="w-4 h-4 text-slate-400 group-hover:text-black transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>

                                    <div id="maritalDropdownMenu" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 p-2 space-y-1">
                                        <button type="button" onclick="selectMaritalStatus('', 'MS', 'bg-slate-100 text-slate-600', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-500 hover:bg-slate-50 hover:text-black rounded-xl flex items-center space-x-2 transition-colors border-b border-slate-100 mb-1">
                                            <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-bold">MS</span>
                                            <span>Select Marital / Relationship Status</span>
                                        </button>
                                        <button type="button" onclick="selectMaritalStatus('Single', 'SGL', 'bg-emerald-100 text-emerald-700', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                            <div class="flex items-center space-x-2">
                                                <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-bold">SGL</span>
                                                <span>Single</span>
                                            </div>
                                            <span class="text-[10px] font-mono-code text-slate-400 font-bold">SGL</span>
                                        </button>
                                        <button type="button" onclick="selectMaritalStatus('Married', 'MRD', 'bg-blue-100 text-blue-700', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                            <div class="flex items-center space-x-2">
                                                <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-[10px] font-bold">MRD</span>
                                                <span>Married (Requires Spouse Info)</span>
                                            </div>
                                            <span class="text-[10px] font-mono-code text-slate-400 font-bold">MRD</span>
                                        </button>
                                        <button type="button" onclick="selectMaritalStatus('Dating / In Relationship', 'DAT', 'bg-purple-100 text-purple-700', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                            <div class="flex items-center space-x-2">
                                                <span class="w-5 h-5 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center text-[10px] font-bold">DAT</span>
                                                <span>Dating / In Relationship (Requires Partner Info)</span>
                                            </div>
                                            <span class="text-[10px] font-mono-code text-slate-400 font-bold">DAT</span>
                                        </button>
                                        <button type="button" onclick="selectMaritalStatus('Divorced', 'DIV', 'bg-amber-100 text-amber-700', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                            <div class="flex items-center space-x-2">
                                                <span class="w-5 h-5 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-[10px] font-bold">DIV</span>
                                                <span>Divorced</span>
                                            </div>
                                            <span class="text-[10px] font-mono-code text-slate-400 font-bold">DIV</span>
                                        </button>
                                        <button type="button" onclick="selectMaritalStatus('Widowed', 'WID', 'bg-rose-100 text-rose-700', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                            <div class="flex items-center space-x-2">
                                                <span class="w-5 h-5 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center text-[10px] font-bold">WID</span>
                                                <span>Widowed</span>
                                            </div>
                                            <span class="text-[10px] font-mono-code text-slate-400 font-bold">WID</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- CHILDREN / DEPENDENTS STATUS (CHECKBOX + CONDITIONAL INPUT) -->
                                <div class="space-y-2.5">
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase">
                                        Children / Dependents Status <span class="text-red-600 font-bold">*</span>
                                    </label>
                                    
                                    <!-- CHECKBOX CARD -->
                                    <label class="flex items-center space-x-3 p-3 bg-white border border-slate-300 rounded-xl cursor-pointer hover:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-600/20 focus-within:border-emerald-600 transition-all shadow-2xs select-none">
                                        <input type="checkbox" id="hasChildrenCheckbox" onchange="toggleChildrenInput()" class="w-5 h-5 text-black rounded-md border-slate-300 focus:ring-emerald-600 focus:ring-2 cursor-pointer accent-emerald-600">
                                        <span class="text-xs font-bold text-slate-900">Suspect Has Children / Dependents</span>
                                    </label>

                                    <!-- CONDITIONAL NUMBER INPUT CONTAINER -->
                                    <div id="childrenInputContainer" class="hidden relative flex items-center group pt-1">
                                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                        </span>
                                        <input type="number" id="newArrestedChildren" min="1" max="20" placeholder="Enter number of children..." class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                                    </div>
                                </div>

                            </div>

                            <!-- DYNAMIC CONDITIONAL SPOUSE / DATING PARTNER CARD (NEW) -->
                            <div id="relationshipDetailsBox" class="hidden p-5 bg-emerald-50/50 rounded-2xl border border-slate-300 space-y-4">
                                <div class="flex items-center space-x-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-white text-emerald-600 border border-slate-200/90 flex items-center justify-center text-xs font-bold shadow-sm shadow-slate-200/50">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                    </div>
                                    <div>
                                        <h4 id="relationshipTitle" class="font-extrabold text-slate-900 text-xs uppercase tracking-wider">Spouse Information (Marriage Record)</h4>
                                        <p id="relationshipSubtitle" class="text-[11px] text-black">Mandatory record for suspect legal spouse identity, phone contact, and occupation</p>
                                    </div>
                                </div>
                                <!-- SPOUSE FIELDS CARD -->
                                <div id="spouseFieldsCard" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Spouse Full Legal Name</label>
                                        <div class="relative flex items-center group">
                                            <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            </span>
                                            <input type="text" id="newArrestedSpouseName" placeholder="e.g. Abena Mensah" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Spouse Contact Phone</label>
                                        <div class="relative flex items-center group">
                                            <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                            </span>
                                            <input type="text" id="newArrestedSpousePhone" placeholder="e.g. +233 20 884 0129" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Spouse Occupation / Address</label>
                                        <div class="relative flex items-center group">
                                            <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A2.36 2.36 0 0019.358 11h-1.55A1.5 1.5 0 0116.3 9.5V7a2 2 0 00-2-2H9.7a2 2 0 00-2 2v2.5A1.5 1.5 0 016.2 11H4.642A2.36 2.36 0 003 13.255V18a2 2 0 002 2h14a2 2 0 002-2v-4.745z"/></svg>
                                            </span>
                                            <input type="text" id="newArrestedSpouseInfo" placeholder="e.g. Trader, Tema Sector 1" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                        </div>
                                    </div>
                                </div>

                                <!-- DATING PARTNER FIELDS CARD (FOR DATING STATUS) -->
                                <div id="partnerFieldsCard" class="hidden grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Partner Full Legal Name</label>
                                        <div class="relative flex items-center group">
                                            <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            </span>
                                            <input type="text" id="newArrestedPartnerName" placeholder="e.g. Ama Serwaa" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Partner Contact Phone</label>
                                        <div class="relative flex items-center group">
                                            <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                            </span>
                                            <input type="text" id="newArrestedPartnerPhone" placeholder="e.g. +233 24 100 2938" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Partner Residential Info</label>
                                        <div class="relative flex items-center group">
                                            <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                            </span>
                                            <input type="text" id="newArrestedPartnerInfo" placeholder="e.g. Resident, East Legon" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SUB-HEADER 3C: Offence CHARGES & CUSTODY ALLOCATION -->
                        <div class="space-y-4 pt-2 relative z-10">
                            <div class="flex items-center space-x-2 text-xs font-extrabold text-black uppercase tracking-wider border-b border-slate-100 pb-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m3 0h1m-4 0v-3m1-3h.01M10 9h.01M10 12h.01M14 9h.01M14 12h.01M14 15h.01M7 9h.01M7 12h.01M7 15h.01"/></svg>
                                <span>3C. Offence Charges &amp; Custody Holding Facility</span>
                            </div>

                            <!-- ROW 1: PLACE OF ARREST WITH GPS RADAR AT THE RIGHT SIDE OF THE INPUT BOX -->
                            <div>
                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                    PLACE OF ARREST <span class="text-red-600 font-bold">*</span>
                                </label>
                                <div class="flex flex-col lg:flex-row items-stretch lg:items-center gap-3">
                                    <div class="relative flex items-center group flex-1 min-w-0">
                                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors pointer-events-none">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        </span>
                                        <input type="text" id="newArrestedRaidLocation" placeholder="e.g. East Legon Sector 4, Accra" required class="w-full min-w-0 pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                                    </div>

                                    @include('partials.incident_location_picker')
                                </div>
                            </div>

                            <!-- ROW 2: DATE OF ARREST, TIME OF ARREST & HOLDING FACILITY -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- DATE OF ARREST -->
                                <div class="min-w-0">
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        Date of Arrest <span class="text-red-600 font-bold">*</span>
                                    </label>
                                    <div class="relative flex items-center group">
                                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors pointer-events-none">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </span>
                                        <input type="date" id="newArrestedDate" placeholder="Select Date of Arrest" required class="w-full min-w-0 pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl font-mono-code font-bold text-slate-900 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs">
                                    </div>
                                </div>

                                <!-- TIME OF ARREST -->
                                <div class="min-w-0">
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        Time of Arrest <span class="text-red-600 font-bold">*</span>
                                    </label>
                                    <div class="relative flex items-center group">
                                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors pointer-events-none">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </span>
                                        <input type="time" id="newArrestedTime" placeholder="Select Time of Arrest" required class="w-full min-w-0 pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl font-mono-code font-bold text-slate-900 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs">
                                    </div>
                                </div>

                                <!-- ASSIGNED CUSTODY HOLDING FACILITY -->
                                <div class="min-w-0">
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 truncate" title="Assigned Custody Holding Facility">
                                        Assigned Custody Holding Facility <span class="text-red-600 font-bold">*</span>
                                    </label>
                                    <div class="relative flex items-center group">
                                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors pointer-events-none">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m3 0h1m-4 0v-3m1-3h.01M10 9h.01M10 12h.01M14 9h.01M14 12h.01M14 15h.01M7 9h.01M7 12h.01M7 15h.01"/></svg>
                                        </span>
                                        <input type="text" id="newArrestedFacility" placeholder="e.g. NACOC High-Security Holding Cell Sector B" required class="w-full min-w-0 pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                                    </div>
                                </div>
                            </div>

                            <!-- ROW: BRIEF FACTS/ CHARGE DESCRIPTION UNDERNEATH -->
                            <div class="mt-5">
                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                    Brief Facts/ Charge Description <span class="text-red-600 font-bold">*</span>
                                </label>
                                <div class="relative flex group">
                                    <span class="absolute left-3.5 top-3.5 text-slate-400 group-focus-within:text-black transition-colors pointer-events-none">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                                    </span>
                                    <textarea id="newArrestedOffense" rows="3" placeholder="e.g. High-Purity Narcotics Trafficking &amp; Illegal Firearm Possession, Breach of Peace..." required class="w-full min-w-0 pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400 resize-y min-h-[96px]"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                    </div>

                    <!-- SECTION 4: SEIZURES (COMMODITIES) -->
                    <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm space-y-6 relative z-20 overflow-hidden">
                        <div class="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-xl bg-white text-emerald-600 border border-slate-200/90 flex items-center justify-center font-bold shadow-md shadow-slate-200/60 shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                            <div>
                                <h3 class="font-extrabold text-slate-900 text-base">Section 4: Seizures (Commodities)</h3>
                                <p class="text-xs text-slate-500">Record illicit substance types, Weight/quantity, street valuation, and evidence vault allocation</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 shrink-0">
                            <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200/80 uppercase tracking-wider shadow-2xs">
                                Contraband Inventory
                            </span>
                        </div>
                    </div>

                    @include('partials.unassigned_exhibits_selector', ['selectorTargetUnit' => 'Crime Scene Support'])

                    <!-- SEIZURE STATUS CHECKBOX CARD -->
                    <div class="space-y-2.5">
                        <label class="relative flex items-center space-x-3.5 p-4 bg-slate-50/80 hover:bg-slate-100/80 border border-slate-300/80 rounded-xl cursor-pointer hover:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-600/20 focus-within:border-emerald-600 transition-all shadow-2xs select-none overflow-hidden">
                            <input type="checkbox" id="hasSeizuresCheckbox" onchange="toggleSeizuresContainer()" class="w-5 h-5 text-emerald-600 rounded-md border-slate-300 focus:ring-emerald-600 focus:ring-2 cursor-pointer accent-emerald-600 shrink-0">
                            <div class="flex flex-col min-w-0 flex-1">
                                <span class="text-xs font-extrabold text-slate-900">Seized Contraband / Illicit Commodities Recorded During Arrest</span>
                                <span class="text-[11px] text-slate-500 font-medium">Check this box if any narcotics, weapons, cash, or contraband commodities were confiscated from this suspect</span>
                            </div>
                        </label>
                    </div>

                    <!-- CONDITIONAL SEIZURES DETAILS CONTAINER -->
                    <div id="seizuresDetailsContainer" class="hidden p-5 bg-emerald-50/40 rounded-2xl border border-emerald-200/80 space-y-5 shadow-2xs overflow-hidden">
                                
                                <div class="flex items-center justify-between border-b border-slate-300/60 pb-3">
                                    <div class="flex items-center space-x-2.5">
                                        <div class="w-7 h-7 rounded-lg bg-white text-emerald-600 border border-slate-200/90 flex items-center justify-center text-xs font-bold shadow-sm shadow-slate-200/50">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        </div>
                                        <div>
                                            <h4 class="font-extrabold text-slate-900 text-xs uppercase tracking-wider">Seized Commodities &amp; Contraband Manifest</h4>
                                            <p class="text-[11px] text-black">Record illicit substance types, Weight/quantity, street valuation, and evidence vault allocation</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="px-2.5 py-1 bg-emerald-100 text-black text-[10px] font-mono-code font-bold rounded-lg border border-slate-300 uppercase">Evidence Tagging Active</span>
                                        <button type="button" onclick="addSeizureBlock()" class="inline-flex items-center px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-xs hover:shadow-md transition-all cursor-pointer">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                            Add Seizure
                                        </button>
                                    </div>
                                </div>

                                <!-- SEIZURES LIST CONTAINER FOR MULTIPLE SEIZURES -->
                                <div id="seizuresListContainer" class="space-y-6">
                                    <!-- SEIZURE BLOCK #1 -->
                                    <div class="seizure-block bg-white p-5 rounded-2xl border border-slate-300/80 shadow-2xs space-y-5 relative" data-seizure-index="1">
                                        <!-- SEIZURE ITEM HEADER -->
                                        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                                            <div class="flex items-center space-x-2">
                                                <span class="w-6 h-6 rounded-full bg-emerald-600 text-white font-extrabold text-xs flex items-center justify-center seizure-number-badge">1</span>
                                                <h5 class="font-extrabold text-slate-900 text-xs uppercase tracking-wider seizure-title">Seized Item #1</h5>
                                            </div>
                                            <div class="flex items-center space-x-2 seizure-header-right">
                                                <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200">ACTIVE EXHIBIT</span>
                                                <button type="button" onclick="removeSeizureBlock(this)" class="remove-seizure-btn hidden text-red-600 hover:bg-red-50 border border-red-200 px-2.5 py-1 rounded-lg text-xs font-bold transition-colors cursor-pointer flex items-center gap-1" title="Remove Seized Item">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg> Remove Item
                                                </button>
                                            </div>
                                        </div>

                                        <!-- SEIZURES INPUT GRID -->
                                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    
                                            <!-- SUSPECT / OWNERSHIP SELECT DROPDOWN -->
                                            <div class="relative z-30">
                                                <div class="seizure-ownership-default">
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                        Suspect / Ownership <span class="text-red-600 font-bold">*</span>
                                                    </label>
                                                    <div class="relative">
                                                        <select
                                                            onchange="this.setAttribute('data-user-selected', 'true'); if (typeof syncSection4ExhibitsToLabSection === 'function') syncSection4ExhibitsToLabSection();"
                                                            class="seizure-associated-person-select w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs cursor-pointer"
                                                        >
                                                            <option value="All Suspects">All Suspects (Joint Ownership)</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="seizure-ownership-unassigned hidden">
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                        Custody / Ownership Status
                                                    </label>
                                                    <div class="px-3.5 py-2.5 bg-slate-100 border border-slate-300 rounded-xl font-bold text-slate-700 text-xs flex items-center justify-between gap-2">
                                                        <span>Unassigned (Vault Lodgement)</span>
                                                        <span class="text-[9px] font-mono-code font-bold px-1.5 py-0.5 bg-emerald-100 text-emerald-800 rounded shrink-0">NO SUSPECT</span>
                                                    </div>
                                                </div>
                                            </div>


                                            <!-- EXHIBIT (COMMODITY CATEGORY) - SELECT2 DROPDOWN -->
                                    <div class="relative z-[9999]" style="z-index: 9999;">
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                            Exhibit (Commodity Category) <span class="text-red-600 font-bold">*</span>
                                        </label>
                                        <div id="exhibitSelectWrapper" class="relative z-[9999]" style="z-index: 9999;">
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                </span>
                                                <input
                                                    type="text"
                                                    id="exhibitSearchInput"
                                                    placeholder="Select or type exhibit category..."
                                                    autocomplete="off"
                                                    class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-500 placeholder:font-normal cursor-text"
                                                    onclick="openExhibitListPanel(this)"
                                                    oninput="filterExhibitList(this)"
                                                    onfocus="openExhibitListPanel(this)"
                                                >
                                                <input type="hidden" id="newSeizureCategory" value="">
                                            </div>
                                            <div id="exhibitListPanel" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-[9999] overflow-hidden" style="z-index: 9999;">
                                                <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                    <input type="text" id="exhibitSearchInner" placeholder="Type to filter categories..." oninput="filterExhibitListInner(this)" class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                </div>
                                                <ul id="exhibitList" class="py-1.5 space-y-0.5 px-1.5">
                                                    <li class="exhibit-item" onclick="selectExhibitCategory('Drugs', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <div><div class="font-extrabold text-slate-900">Drugs</div><div class="text-[10px] text-slate-500 font-normal">Narcotics, Synthetics, Opioids</div></div>
                                                            <span class="text-[10px] font-mono-code bg-emerald-50 text-black px-2 py-0.5 rounded font-bold">DRG</span>
                                                        </button>
                                                    </li>
                                                    <li class="exhibit-item" onclick="selectExhibitCategory('Money', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <div><div class="font-extrabold text-slate-900">Money</div><div class="text-[10px] text-slate-500 font-normal">Cash, Foreign Currencies, Laundering</div></div>
                                                            <span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-2 py-0.5 rounded font-bold">CASH</span>
                                                        </button>
                                                    </li>
                                                    <li class="exhibit-item" onclick="selectExhibitCategory('Precious Stones', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <div><div class="font-extrabold text-slate-900">Precious Stones</div><div class="text-[10px] text-slate-500 font-normal">Diamonds, Gemstones, Gold, Bullion</div></div>
                                                            <span class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-2 py-0.5 rounded font-bold">MET</span>
                                                        </button>
                                                    </li>
                                                    <li class="exhibit-item" onclick="selectExhibitCategory('Vehicles', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <div><div class="font-extrabold text-slate-900">Vehicles</div><div class="text-[10px] text-slate-500 font-normal">Automobiles, Vessels, Trucks</div></div>
                                                            <span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-2 py-0.5 rounded font-bold">VEH</span>
                                                        </button>
                                                    </li>
                                                    <li class="exhibit-item" onclick="selectExhibitCategory('Electronic Devices', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <div><div class="font-extrabold text-slate-900">Electronic Devices</div><div class="text-[10px] text-slate-500 font-normal">Phones, Encrypted Laptops, Drives</div></div>
                                                            <span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-2 py-0.5 rounded font-bold">ELEC</span>
                                                        </button>
                                                    </li>
                                                    <li class="exhibit-item" onclick="selectExhibitCategory('Weapons', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <div><div class="font-extrabold text-slate-900">Weapons</div><div class="text-[10px] text-slate-500 font-normal">Firearms, Handguns, Blades</div></div>
                                                            <span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-2 py-0.5 rounded font-bold">WPN</span>
                                                        </button>
                                                    </li>
                                                    <li class="exhibit-item" onclick="selectExhibitCategory('Ammunition', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <div><div class="font-extrabold text-slate-900">Ammunition</div><div class="text-[10px] text-slate-500 font-normal">Bullets, Cartridges, Explosives</div></div>
                                                            <span class="text-[10px] font-mono-code bg-orange-50 text-orange-700 px-2 py-0.5 rounded font-bold">AMMO</span>
                                                        </button>
                                                    </li>
                                                    <li class="exhibit-item" onclick="selectExhibitCategory('Other Exhibits', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <div><div class="font-extrabold text-slate-900">Other Exhibits</div><div class="text-[10px] text-slate-500 font-normal">Hardware, Miscellaneous Devices</div></div>
                                                            <span class="text-[10px] font-mono-code bg-slate-200 text-slate-700 px-2 py-0.5 rounded font-bold">MISC</span>
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- SPECIFIC COMMODITY / DRUG NAME (SMART FIELD) -->
                                    <div id="commodityFieldWrapper">
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Primary Commodity Name <span class="text-red-600 font-bold">*</span></label>

                                        <!-- TEXT INPUT (shown for non-drug categories) -->
                                        <div id="commodityTextWrapper" class="relative flex items-center group">
                                            <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                            </span>
                                            <input type="text" id="newSeizureName" placeholder="e.g. Cocaine (High Purity Slabs)" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                        </div>

                                        <!-- DRUG SELECT DROPDOWN (matching Exhibit Category styling) -->
                                        <div id="drugSelectWrapper" class="hidden relative z-40">
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                </span>
                                                <input
                                                    type="text"
                                                    id="drugSearchInput"
                                                    placeholder="Select or type custom drug name..."
                                                    autocomplete="off"
                                                    class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-500 placeholder:font-normal cursor-text"
                                                    onclick="openDrugListPanel(this)"
                                                    oninput="filterDrugList(this)"
                                                    onchange="filterDrugList(this)"
                                                    onblur="filterDrugList(this)"
                                                    onkeydown="handleCommoditySearchKeydown(event, this, 'drug')"
                                                    onfocus="openDrugListPanel(this)"
                                                >
                                                <input type="hidden" id="newSeizureNameDrug" name="newSeizureNameDrug" value="">
                                            </div>
                                            <div id="drugListPanel" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden" style="max-height:240px;overflow-y:auto">
                                                <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                    <input type="text" id="drugSearchInner" placeholder="Type to filter drugs..." oninput="filterDrugListInner(this)" class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                </div>
                                                <ul id="drugList" class="py-1.5 space-y-0.5 px-1.5">
                                                    <!-- Opioids & Analgesics -->
                                                    <li class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Opioids &amp; Analgesics</li>
                                                    <li class="drug-item" onclick="selectDrugType('Heroin (Brown Sugar / Diacetylmorphine)', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <span>Heroin (Brown Sugar / Diacetylmorphine)</span>
                                                            <span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span>
                                                        </button>
                                                    </li>
                                                    <li class="drug-item" onclick="selectDrugType('Fentanyl (Synthetic Opioid Patches / Powder)', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <span>Fentanyl (Synthetic Opioid Patches / Powder)</span>
                                                            <span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span>
                                                        </button>
                                                    </li>
                                                    <li class="drug-item" onclick="selectDrugType('Morphine (Raw Opium Extract)', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <span>Morphine (Raw Opium Extract)</span>
                                                            <span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span>
                                                        </button>
                                                    </li>
                                                    <li class="drug-item" onclick="selectDrugType('Tramadol (High-Dose Tablets / Capsules)', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <span>Tramadol (High-Dose Tablets / Capsules)</span>
                                                            <span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span>
                                                        </button>
                                                    </li>
                                                    <li class="drug-item" onclick="selectDrugType('Codeine (Cough Syrup Misuse / Purple Drank)', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <span>Codeine (Cough Syrup Misuse / Purple Drank)</span>
                                                            <span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span>
                                                        </button>
                                                    </li>
                                                    <li class="drug-item" onclick="selectDrugType('Oxycodone (OxyContin Tablets)', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <span>Oxycodone (OxyContin Tablets)</span>
                                                            <span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span>
                                                        </button>
                                                    </li>

                                                    <!-- Stimulants & Cocaine-Class -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Stimulants &amp; Cocaine-Class</li>
                                                    <li class="drug-item" onclick="selectDrugType('Cocaine (High-Purity Powder / Slabs)', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <span>Cocaine (High-Purity Powder / Slabs)</span>
                                                            <span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">COC</span>
                                                        </button>
                                                    </li>
                                                    <li class="drug-item" onclick="selectDrugType('Crack Cocaine (Rock Form / Freebase)', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <span>Crack Cocaine (Rock Form / Freebase)</span>
                                                            <span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">COC</span>
                                                        </button>
                                                    </li>
                                                    <li class="drug-item" onclick="selectDrugType('Methamphetamine (Crystal Meth / Ice)', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <span>Methamphetamine (Crystal Meth / Ice)</span>
                                                            <span class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">STIM</span>
                                                        </button>
                                                    </li>
                                                    <li class="drug-item" onclick="selectDrugType('Amphetamine (Speed / Pep Pills)', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <span>Amphetamine (Speed / Pep Pills)</span>
                                                            <span class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">STIM</span>
                                                        </button>
                                                    </li>
                                                    <li class="drug-item" onclick="selectDrugType('MDMA (Ecstasy / Molly Tablets)', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <span>MDMA (Ecstasy / Molly Tablets)</span>
                                                            <span class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">STIM</span>
                                                        </button>
                                                    </li>
                                                    <li class="drug-item" onclick="selectDrugType('Khat (Catha Edulis Fresh Leaves / Bundles)', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <span>Khat (Catha Edulis Fresh Leaves / Bundles)</span>
                                                            <span class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">STIM</span>
                                                        </button>
                                                    </li>

                                                    <!-- Cannabis & Derivatives -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Cannabis &amp; Derivatives</li>
                                                    <li class="drug-item" onclick="selectDrugType('Cannabis / Marijuana (Dried Flower / Weed)', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <span>Cannabis / Marijuana (Dried Flower / Weed)</span>
                                                            <span class="text-[10px] font-mono-code bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-bold">CAN</span>
                                                        </button>
                                                    </li>
                                                    <li class="drug-item" onclick="selectDrugType('Cannabis Resin (Hashish / Black Hash Slabs)', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <span>Cannabis Resin (Hashish / Black Hash Slabs)</span>
                                                            <span class="text-[10px] font-mono-code bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-bold">CAN</span>
                                                        </button>
                                                    </li>
                                                    <li class="drug-item" onclick="selectDrugType('Cannabis Oil / THC Concentrate (Wax / Dabs)', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <span>Cannabis Oil / THC Concentrate (Wax / Dabs)</span>
                                                            <span class="text-[10px] font-mono-code bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-bold">CAN</span>
                                                        </button>
                                                    </li>
                                                    <li class="drug-item" onclick="selectDrugType('Synthetic Cannabinoids (Spice / K2 Herbal)', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <span>Synthetic Cannabinoids (Spice / K2 Herbal)</span>
                                                            <span class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">SYN</span>
                                                        </button>
                                                    </li>

                                                    <!-- Sedatives & Depressants -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Sedatives &amp; Depressants</li>
                                                    <li class="drug-item" onclick="selectDrugType('Rohypnol (Flunitrazepam / Date Rape Drug)', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <span>Rohypnol (Flunitrazepam / Date Rape Drug)</span>
                                                            <span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">SED</span>
                                                        </button>
                                                    </li>
                                                    <li class="drug-item" onclick="selectDrugType('GHB (Gamma-Hydroxybutyrate / Liquid Ecstasy)', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <span>GHB (Gamma-Hydroxybutyrate / Liquid Ecstasy)</span>
                                                            <span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">SED</span>
                                                        </button>
                                                    </li>
                                                    <li class="drug-item" onclick="selectDrugType('Diazepam / Valium (Benzodiazepine Tablets)', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <span>Diazepam / Valium (Benzodiazepine Tablets)</span>
                                                            <span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">SED</span>
                                                        </button>
                                                    </li>
                                                    <li class="drug-item" onclick="selectDrugType('Ketamine (Special K / Dissociative Anesthetic)', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <span>Ketamine (Special K / Dissociative Anesthetic)</span>
                                                            <span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">SED</span>
                                                        </button>
                                                    </li>

                                                    <!-- Hallucinogens & Psychedelics -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Hallucinogens &amp; Psychedelics</li>
                                                    <li class="drug-item" onclick="selectDrugType('LSD (Lysergic Acid Diethylamide / Acid Tabs)', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <span>LSD (Lysergic Acid Diethylamide / Acid Tabs)</span>
                                                            <span class="text-[10px] font-mono-code bg-pink-50 text-pink-700 px-1.5 py-0.5 rounded font-bold">PSY</span>
                                                        </button>
                                                    </li>
                                                    <li class="drug-item" onclick="selectDrugType('Psilocybin (Magic Mushrooms / Shrooms)', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <span>Psilocybin (Magic Mushrooms / Shrooms)</span>
                                                            <span class="text-[10px] font-mono-code bg-pink-50 text-pink-700 px-1.5 py-0.5 rounded font-bold">PSY</span>
                                                        </button>
                                                    </li>
                                                    <li class="drug-item" onclick="selectDrugType('PCP (Phencyclidine / Angel Dust)', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <span>PCP (Phencyclidine / Angel Dust)</span>
                                                            <span class="text-[10px] font-mono-code bg-pink-50 text-pink-700 px-1.5 py-0.5 rounded font-bold">PSY</span>
                                                        </button>
                                                    </li>

                                                    <!-- Precursor Chemicals -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Precursor Chemicals</li>
                                                    <li class="drug-item" onclick="selectDrugType('Ephedrine / Pseudoephedrine (Meth Precursor)', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <span>Ephedrine / Pseudoephedrine (Meth Precursor)</span>
                                                            <span class="text-[10px] font-mono-code bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded font-bold">CHEM</span>
                                                        </button>
                                                    </li>
                                                    <li class="drug-item" onclick="selectDrugType('Acetic Anhydride (Heroin Precursor Chemical)', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <span>Acetic Anhydride (Heroin Precursor Chemical)</span>
                                                            <span class="text-[10px] font-mono-code bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded font-bold">CHEM</span>
                                                        </button>
                                                    </li>
                                                    <li class="drug-item" onclick="selectDrugType('Phenylacetic Acid (Amphetamine Precursor)', event)">
                                                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                            <span>Phenylacetic Acid (Amphetamine Precursor)</span>
                                                            <span class="text-[10px] font-mono-code bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded font-bold">CHEM</span>
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <!-- WEAPON SELECT DROPDOWN (matching Exhibit Category styling) -->
                                        <div id="weaponSelectWrapper" class="hidden relative z-40">
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                </span>
                                                <input
                                                    type="text"
                                                    id="weaponSearchInput"
                                                    placeholder="Select or type custom weapon type..."
                                                    autocomplete="off"
                                                    class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-500 placeholder:font-normal cursor-text"
                                                    onclick="openWeaponListPanel(this)"
                                                    oninput="filterWeaponList(this)"
                                                    onfocus="openWeaponListPanel(this)"
                                                >
                                                <input type="hidden" id="newSeizureNameWeapon" name="newSeizureNameWeapon" value="">
                                            </div>
                                            <div id="weaponListPanel" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden" style="max-height:240px;overflow-y:auto">
                                                <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                    <input type="text" id="weaponSearchInner" placeholder="Type to filter weapons..." oninput="filterWeaponListInner(this)" class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                </div>
                                                <ul id="weaponList" class="py-1.5 space-y-0.5 px-1.5">
                                                    <!-- Handguns & Pistols -->
                                                    <li class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Handguns &amp; Pistols</li>
                                                    <li class="weapon-item" onclick="selectWeaponType('AK-47 / AKM Shorty Pistol (7.62x39mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>AK-47 / AKM Shorty Pistol (7.62x39mm)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Glock 17 Semi-Automatic Pistol (9mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Glock 17 Semi-Automatic Pistol (9mm)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Glock 19 Compact Tactical Pistol (9mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Glock 19 Compact Tactical Pistol (9mm)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Beretta 92FS / M9 Pistol (9mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Beretta 92FS / M9 Pistol (9mm)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('SIG Sauer P226 / P320 Pistol (9mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>SIG Sauer P226 / P320 Pistol (9mm)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Colt M1911 / M1911A1 Pistol (.45 ACP)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Colt M1911 / M1911A1 Pistol (.45 ACP)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Smith &amp; Wesson M&amp;P 9mm Pistol', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Smith &amp; Wesson M&amp;P 9mm Pistol</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Browning Hi-Power Pistol (9mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Browning Hi-Power Pistol (9mm)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('CZ 75 / CZ P-10 C Tactical Pistol (9mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>CZ 75 / CZ P-10 C Tactical Pistol (9mm)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Tokarev TT-33 Pistol (7.62x25mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Tokarev TT-33 Pistol (7.62x25mm)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Makarov PM Concealed Pistol (9x18mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Makarov PM Concealed Pistol (9x18mm)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Revolver (.38 Special / .357 Magnum)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Revolver (.38 Special / .357 Magnum)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">REV</span></button></li>

                                                    <!-- Rifles & Assault Rifles -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Rifles &amp; Assault Rifles</li>
                                                    <li class="weapon-item" onclick="selectWeaponType('AK-47 / AKM Kalashnikov Rifle (7.62x39mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>AK-47 / AKM Kalashnikov Rifle (7.62x39mm)</span><span class="text-[10px] font-mono-code bg-red-100 text-red-800 px-1.5 py-0.5 rounded font-bold">RFL</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('AK-74 Tactical Rifle (5.45x39mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>AK-74 Tactical Rifle (5.45x39mm)</span><span class="text-[10px] font-mono-code bg-red-100 text-red-800 px-1.5 py-0.5 rounded font-bold">RFL</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('M16A2 / M16A4 Military Assault Rifle (5.56mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>M16A2 / M16A4 Military Assault Rifle (5.56mm)</span><span class="text-[10px] font-mono-code bg-red-100 text-red-800 px-1.5 py-0.5 rounded font-bold">RFL</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('M4A1 Carbine / AR-15 Platform (5.56x45mm NATO)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>M4A1 Carbine / AR-15 Platform (5.56x45mm NATO)</span><span class="text-[10px] font-mono-code bg-red-100 text-red-800 px-1.5 py-0.5 rounded font-bold">RFL</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Heckler &amp; Koch HK416 Assault Rifle (5.56mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Heckler &amp; Koch HK416 Assault Rifle (5.56mm)</span><span class="text-[10px] font-mono-code bg-red-100 text-red-800 px-1.5 py-0.5 rounded font-bold">RFL</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('FN FAL Battle Rifle (7.62x51mm NATO)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>FN FAL Battle Rifle (7.62x51mm NATO)</span><span class="text-[10px] font-mono-code bg-red-100 text-red-800 px-1.5 py-0.5 rounded font-bold">RFL</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Heckler &amp; Koch G3 Battle Rifle (7.62x51mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Heckler &amp; Koch G3 Battle Rifle (7.62x51mm)</span><span class="text-[10px] font-mono-code bg-red-100 text-red-800 px-1.5 py-0.5 rounded font-bold">RFL</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Galil ARM / Tavor TAR-21 Assault Rifle (5.56mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Galil ARM / Tavor TAR-21 Assault Rifle (5.56mm)</span><span class="text-[10px] font-mono-code bg-red-100 text-red-800 px-1.5 py-0.5 rounded font-bold">RFL</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Steyr AUG Bullpup Assault Rifle (5.56mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Steyr AUG Bullpup Assault Rifle (5.56mm)</span><span class="text-[10px] font-mono-code bg-red-100 text-red-800 px-1.5 py-0.5 rounded font-bold">RFL</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Type 56 Norinco Rifle (7.62x39mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Type 56 Norinco Rifle (7.62x39mm)</span><span class="text-[10px] font-mono-code bg-red-100 text-red-800 px-1.5 py-0.5 rounded font-bold">RFL</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('SKS Semi-Automatic Carbine (7.62x39mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>SKS Semi-Automatic Carbine (7.62x39mm)</span><span class="text-[10px] font-mono-code bg-red-100 text-red-800 px-1.5 py-0.5 rounded font-bold">RFL</span></button></li>

                                                    <!-- Submachine Guns (SMGs) -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Submachine Guns (SMGs)</li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Heckler &amp; Koch MP5 / MP5K SMG (9mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Heckler &amp; Koch MP5 / MP5K SMG (9mm)</span><span class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">SMG</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Uzi / Mini-Uzi Submachine Gun (9mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Uzi / Mini-Uzi Submachine Gun (9mm)</span><span class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">SMG</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('CZ Scorpion EVO 3 Submachine Gun (9mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>CZ Scorpion EVO 3 Submachine Gun (9mm)</span><span class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">SMG</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('FN P90 Personal Defense Weapon (5.7x28mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>FN P90 Personal Defense Weapon (5.7x28mm)</span><span class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">SMG</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('MAC-10 / MAC-11 Submachine Gun (.45 ACP / 9mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>MAC-10 / MAC-11 Submachine Gun (.45 ACP / 9mm)</span><span class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">SMG</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Beretta M12 Submachine Gun (9mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Beretta M12 Submachine Gun (9mm)</span><span class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">SMG</span></button></li>

                                                    <!-- Shotguns -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Shotguns</li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Remington 870 Tactical Shotgun (12 Gauge)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Remington 870 Tactical Shotgun (12 Gauge)</span><span class="text-[10px] font-mono-code bg-orange-50 text-orange-800 px-1.5 py-0.5 rounded font-bold">STG</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Mossberg 500 / 590 Pump Shotgun (12 Gauge)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Mossberg 500 / 590 Pump Shotgun (12 Gauge)</span><span class="text-[10px] font-mono-code bg-orange-50 text-orange-800 px-1.5 py-0.5 rounded font-bold">STG</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Benelli M4 Combat Shotgun (12 Gauge)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Benelli M4 Combat Shotgun (12 Gauge)</span><span class="text-[10px] font-mono-code bg-orange-50 text-orange-800 px-1.5 py-0.5 rounded font-bold">STG</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Saiga-12 Semi-Auto Tactical Shotgun (12 Gauge)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Saiga-12 Semi-Auto Tactical Shotgun (12 Gauge)</span><span class="text-[10px] font-mono-code bg-orange-50 text-orange-800 px-1.5 py-0.5 rounded font-bold">STG</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Double-Barrel Break-Action Shotgun (Sawed-Off)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Double-Barrel Break-Action Shotgun (Sawed-Off)</span><span class="text-[10px] font-mono-code bg-orange-50 text-orange-800 px-1.5 py-0.5 rounded font-bold">STG</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Single-Barrel Hunting Shotgun (12 Gauge)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Single-Barrel Hunting Shotgun (12 Gauge)</span><span class="text-[10px] font-mono-code bg-orange-50 text-orange-800 px-1.5 py-0.5 rounded font-bold">STG</span></button></li>

                                                    <!-- Sniper & Precision Rifles -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Sniper &amp; Precision Rifles</li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Barrett M82A1 / M107 Anti-Materiel (.50 BMG)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Barrett M82A1 / M107 Anti-Materiel (.50 BMG)</span><span class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">SNP</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Remington 700 / M24 Sniper Rifle (7.62 NATO)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Remington 700 / M24 Sniper Rifle (7.62 NATO)</span><span class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">SNP</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Dragunov SVD Sniper Rifle (7.62x54mmR)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Dragunov SVD Sniper Rifle (7.62x54mmR)</span><span class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">SNP</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Accuracy International AWM / AWSM (.338 Lapua)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Accuracy International AWM / AWSM (.338 Lapua)</span><span class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">SNP</span></button></li>

                                                    <!-- Machine Guns & Heavy Ordnance -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Machine Guns &amp; Heavy Ordnance</li>
                                                    <li class="weapon-item" onclick="selectWeaponType('PKM / PKP General Purpose Machine Gun (7.62x54mmR)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>PKM / PKP General Purpose Machine Gun (7.62x54mmR)</span><span class="text-[10px] font-mono-code bg-red-900 text-white px-1.5 py-0.5 rounded font-bold">MG</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('FN MAG / M240 Belt-Fed Machine Gun (7.62mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>FN MAG / M240 Belt-Fed Machine Gun (7.62mm)</span><span class="text-[10px] font-mono-code bg-red-900 text-white px-1.5 py-0.5 rounded font-bold">MG</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('M249 SAW / FN Minimi Light Machine Gun (5.56mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>M249 SAW / FN Minimi Light Machine Gun (5.56mm)</span><span class="text-[10px] font-mono-code bg-red-900 text-white px-1.5 py-0.5 rounded font-bold">MG</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Browning M2HB Heavy Machine Gun (.50 BMG)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Browning M2HB Heavy Machine Gun (.50 BMG)</span><span class="text-[10px] font-mono-code bg-red-900 text-white px-1.5 py-0.5 rounded font-bold">HMG</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('RPG-7 Anti-Tank Rocket Launcher', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>RPG-7 Anti-Tank Rocket Launcher</span><span class="text-[10px] font-mono-code bg-red-900 text-white px-1.5 py-0.5 rounded font-bold">ORD</span></button></li>

                                                    <!-- Edged, Bladed & Impact Weapons -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Edged, Bladed &amp; Impact Weapons</li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Combat / Tactical Military Knife', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Combat / Tactical Military Knife</span><span class="text-[10px] font-mono-code bg-slate-200 text-slate-800 px-1.5 py-0.5 rounded font-bold">BLD</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Heavy Steel Machete / Cutlass', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Heavy Steel Machete / Cutlass</span><span class="text-[10px] font-mono-code bg-slate-200 text-slate-800 px-1.5 py-0.5 rounded font-bold">BLD</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Switchblade / Automatic Stiletto Knife', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Switchblade / Automatic Stiletto Knife</span><span class="text-[10px] font-mono-code bg-slate-200 text-slate-800 px-1.5 py-0.5 rounded font-bold">BLD</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Dagger / Push Knife / Concealed Blade', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Dagger / Push Knife / Concealed Blade</span><span class="text-[10px] font-mono-code bg-slate-200 text-slate-800 px-1.5 py-0.5 rounded font-bold">BLD</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Samurai Sword / Katana / Machete Blade', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Samurai Sword / Katana / Machete Blade</span><span class="text-[10px] font-mono-code bg-slate-200 text-slate-800 px-1.5 py-0.5 rounded font-bold">BLD</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Brass Knuckles / Heavy Iron Knuckles', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Brass Knuckles / Heavy Iron Knuckles</span><span class="text-[10px] font-mono-code bg-slate-200 text-slate-800 px-1.5 py-0.5 rounded font-bold">IMP</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Crossbow / Tactical Compound Bow', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Crossbow / Tactical Compound Bow</span><span class="text-[10px] font-mono-code bg-slate-200 text-slate-800 px-1.5 py-0.5 rounded font-bold">BOW</span></button></li>

                                                    <!-- Explosives & Less-Lethal Tactical Devices -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Explosives &amp; Less-Lethal Tactical Devices</li>
                                                    <li class="weapon-item" onclick="selectWeaponType('High-Explosive Hand Grenade (Frag)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>High-Explosive Hand Grenade (Frag)</span><span class="text-[10px] font-mono-code bg-red-100 text-red-900 px-1.5 py-0.5 rounded font-bold">EXP</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Flashbang Grenade / Tactical Stun Grenade', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Flashbang Grenade / Tactical Stun Grenade</span><span class="text-[10px] font-mono-code bg-amber-100 text-amber-900 px-1.5 py-0.5 rounded font-bold">TAC</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('C4 Plastic Explosive / Dynamite Blocks', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>C4 Plastic Explosive / Dynamite Blocks</span><span class="text-[10px] font-mono-code bg-red-100 text-red-900 px-1.5 py-0.5 rounded font-bold">EXP</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Improvised Explosive Device (IED / Pipe Bomb)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Improvised Explosive Device (IED / Pipe Bomb)</span><span class="text-[10px] font-mono-code bg-red-100 text-red-900 px-1.5 py-0.5 rounded font-bold">IED</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Taser Stun Gun / Electroshock Weapon', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Taser Stun Gun / Electroshock Weapon</span><span class="text-[10px] font-mono-code bg-blue-100 text-blue-900 px-1.5 py-0.5 rounded font-bold">TSR</span></button></li>
                                                    <li class="weapon-item" onclick="selectWeaponType('Pepper Spray / OC Tear Gas Canister', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Pepper Spray / OC Tear Gas Canister</span><span class="text-[10px] font-mono-code bg-blue-100 text-blue-900 px-1.5 py-0.5 rounded font-bold">GAS</span></button></li>
                                                </ul>
                                            </div>
                                        </div>

                                        <!-- CURRENCY SELECT2 DROPDOWN (shown when Money is selected) -->
                                        <div id="currencySelectWrapper" class="hidden relative z-40">
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-emerald-500 pointer-events-none">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                </span>
                                                <input
                                                    type="text"
                                                    id="currencySearchInput"
                                                    placeholder="Select currency..."
                                                    autocomplete="off"
                                                    class="w-full pl-8 pr-3 py-2.5 bg-white border border-emerald-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-300 transition-all shadow-2xs placeholder:text-slate-400 placeholder:font-normal cursor-pointer"
                                                    readonly
                                                    onclick="openCurrencyListPanel(this)"
                                                    oninput="filterCurrencyList(this)"
                                                    onfocus="openCurrencyListPanel(this)"
                                                >
                                                <input type="hidden" id="newSeizureNameCurrency" name="newSeizureName">
                                            </div>
                                            <div id="currencyListPanel" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-emerald-200 z-50 overflow-hidden" style="max-height:240px;overflow-y:auto">
                                                <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                    <input type="text" id="currencySearchInner" placeholder="Type to filter currencies..." oninput="filterCurrencyListInner(this)" class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-300 transition-all">
                                                </div>
                                                <ul id="currencyList" class="py-1.5 space-y-0.5 px-1.5">
                                                    <!-- West Africa -->
                                                    <li class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-emerald-500 uppercase tracking-widest">West Africa</li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Ghana Cedi (GHS ₵)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Ghana Cedi</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">GHS ₵</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Nigerian Naira (NGN ₦)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Nigerian Naira</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">NGN ₦</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('CFA Franc BCEAO (XOF)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>CFA Franc (BCEAO)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">XOF</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Sierra Leonean Leone (SLL)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Sierra Leonean Leone</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">SLL</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Liberian Dollar (LRD)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Liberian Dollar</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">LRD</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Gambian Dalasi (GMD)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Gambian Dalasi</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">GMD</span></button></li>

                                                    <!-- Major Global -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-emerald-500 uppercase tracking-widest">Major Global Currencies</li>
                                                    <li class="currency-item" onclick="selectCurrencyType('US Dollar (USD $)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>US Dollar</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">USD $</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Euro (EUR €)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Euro</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">EUR €</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('British Pound Sterling (GBP £)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>British Pound Sterling</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">GBP £</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Swiss Franc (CHF)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Swiss Franc</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">CHF</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Japanese Yen (JPY ¥)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Japanese Yen</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">JPY ¥</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Chinese Yuan Renminbi (CNY ¥)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Chinese Yuan (Renminbi)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">CNY ¥</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Canadian Dollar (CAD $)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Canadian Dollar</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">CAD $</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Australian Dollar (AUD $)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Australian Dollar</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">AUD $</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('UAE Dirham (AED)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>UAE Dirham</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">AED</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Saudi Riyal (SAR)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Saudi Riyal</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">SAR</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Indian Rupee (INR ₹)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Indian Rupee</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">INR ₹</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('South Korean Won (KRW ₩)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>South Korean Won</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">KRW ₩</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Singapore Dollar (SGD $)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Singapore Dollar</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">SGD $</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Hong Kong Dollar (HKD $)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Hong Kong Dollar</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">HKD $</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Russian Ruble (RUB ₽)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Russian Ruble</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">RUB ₽</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Turkish Lira (TRY ₺)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Turkish Lira</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">TRY ₺</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Brazilian Real (BRL R$)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Brazilian Real</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">BRL R$</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Mexican Peso (MXN $)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Mexican Peso</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">MXN $</span></button></li>

                                                    <!-- Africa -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-emerald-500 uppercase tracking-widest">Africa</li>
                                                    <li class="currency-item" onclick="selectCurrencyType('South African Rand (ZAR R)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>South African Rand</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">ZAR R</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Kenyan Shilling (KES)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Kenyan Shilling</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">KES</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Ethiopian Birr (ETB)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Ethiopian Birr</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">ETB</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Egyptian Pound (EGP £)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Egyptian Pound</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">EGP £</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Tanzanian Shilling (TZS)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Tanzanian Shilling</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">TZS</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Moroccan Dirham (MAD)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Moroccan Dirham</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">MAD</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Angolan Kwanza (AOA)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Angolan Kwanza</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">AOA</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Congolese Franc (CDF)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Congolese Franc</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">CDF</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Zambian Kwacha (ZMW)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Zambian Kwacha</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">ZMW</span></button></li>

                                                    <!-- Crypto -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-emerald-500 uppercase tracking-widest">Cryptocurrencies</li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Bitcoin (BTC ₿)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Bitcoin</span><span class="text-[10px] font-mono-code bg-amber-50 text-amber-700 px-1.5 py-0.5 rounded font-bold">BTC ₿</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Ethereum (ETH Ξ)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Ethereum</span><span class="text-[10px] font-mono-code bg-amber-50 text-amber-700 px-1.5 py-0.5 rounded font-bold">ETH Ξ</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Tether (USDT)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Tether (Stablecoin)</span><span class="text-[10px] font-mono-code bg-amber-50 text-amber-700 px-1.5 py-0.5 rounded font-bold">USDT</span></button></li>
                                                    <li class="currency-item" onclick="selectCurrencyType('Monero (XMR – Privacy Coin)')"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Monero (Privacy Coin)</span><span class="text-[10px] font-mono-code bg-amber-50 text-amber-700 px-1.5 py-0.5 rounded font-bold">XMR</span></button></li>
                                                </ul>
                                            </div>
                                        </div>

                                        <!-- AMMUNITION SELECT DROPDOWN (shown when Ammunition is selected) -->
                                        <div id="ammoSelectWrapper" class="hidden relative z-40">
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                </span>
                                                <input
                                                    type="text"
                                                    id="ammoSearchInput"
                                                    placeholder="Select or type custom ammunition type..."
                                                    autocomplete="off"
                                                    class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-500 placeholder:font-normal cursor-text"
                                                    onclick="openAmmoListPanel(this)"
                                                    oninput="filterAmmoList(this)"
                                                    onfocus="openAmmoListPanel(this)"
                                                >
                                                <input type="hidden" id="newSeizureNameAmmo" name="newSeizureNameAmmo" value="">
                                            </div>
                                            <div id="ammoListPanel" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden" style="max-height:240px;overflow-y:auto">
                                                <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                    <input type="text" id="ammoSearchInner" placeholder="Type to filter ammunitions..." oninput="filterAmmoListInner(this)" class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                </div>
                                                <ul id="ammoList" class="py-1.5 space-y-0.5 px-1.5">
                                                    <!-- Pistol & Handgun Ammunition -->
                                                    <li class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Pistol &amp; Handgun Ammunition</li>
                                                    <li class="ammo-item" onclick="selectAmmoType('9x19mm Parabellum / NATO (9mm Luger)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>9x19mm Parabellum / NATO (9mm Luger)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">9MM</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.45 ACP (Automatic Colt Pistol / 11.43x23mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.45 ACP (Automatic Colt Pistol / 11.43x23mm)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">.45</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.40 S&amp;W (Smith &amp; Wesson / 10x22mm)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.40 S&amp;W (Smith &amp; Wesson / 10x22mm)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">.40</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.380 ACP (9mm Short / Browning)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.380 ACP (9mm Short / Browning)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">.380</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.38 Special (9x29mmR Revolver Ammunition)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.38 Special (9x29mmR Revolver Ammunition)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">.38</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.357 Magnum (9x33mmR High Velocity)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.357 Magnum (9x33mmR High Velocity)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">.357</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.44 Remington Magnum (10.9x33mmR)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.44 Remington Magnum (10.9x33mmR)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">.44</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('7.62x25mm Tokarev (TT Pistol Cartridge)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>7.62x25mm Tokarev (TT Pistol Cartridge)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">7.62</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('9x18mm Makarov (PM Round)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>9x18mm Makarov (PM Round)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">9x18</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('5.7x28mm (FN P90 / Five-seveN High-Velocity)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>5.7x28mm (FN P90 / Five-seveN High-Velocity)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">5.7</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('10mm Auto (10x25mm Tactical)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>10mm Auto (10x25mm Tactical)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">10MM</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.50 Action Express (.50 AE Desert Eagle)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.50 Action Express (.50 AE Desert Eagle)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">.50AE</span></button></li>

                                                    <!-- Rifle & Intermediate Ammunition -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Rifle &amp; Intermediate Ammunition</li>
                                                    <li class="ammo-item" onclick="selectAmmoType('7.62x39mm Soviet (AK-47 / SKS / Type 56)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>7.62x39mm Soviet (AK-47 / SKS / Type 56)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">7.62</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('5.56x45mm NATO / .223 Remington (M16 / M4 / AR-15)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>5.56x45mm NATO / .223 Remington (M16 / M4 / AR-15)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">5.56</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('7.62x51mm NATO / .308 Winchester (FN FAL / G3)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>7.62x51mm NATO / .308 Winchester (FN FAL / G3)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">7.62</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('5.45x39mm Soviet (AK-74 Assault Rifle)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>5.45x39mm Soviet (AK-74 Assault Rifle)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">5.45</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('7.62x54mmR Russian (Dragunov SVD / PKM Belt)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>7.62x54mmR Russian (Dragunov SVD / PKM Belt)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">7.62R</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.300 AAC Blackout (7.62x35mm Subsonic / Supersonic)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.300 AAC Blackout (7.62x35mm Subsonic / Supersonic)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">.300</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('6.5mm Creedmoor (Precision Long Range Match)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>6.5mm Creedmoor (Precision Long Range Match)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">6.5</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.30-06 Springfield (7.62x63mm Military Rifle)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.30-06 Springfield (7.62x63mm Military Rifle)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">.30-06</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.303 British (7.7x56mmR Lee-Enfield)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.303 British (7.7x56mmR Lee-Enfield)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">.303</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('7.92x57mm Mauser (8mm Mauser Infantry)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>7.92x57mm Mauser (8mm Mauser Infantry)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">8MM</span></button></li>

                                                    <!-- Shotgun Shells & Shotshell Cartridges -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Shotgun Shells &amp; Shotshell Cartridges</li>
                                                    <li class="ammo-item" onclick="selectAmmoType('12 Gauge Buckshot (00 Buck / 9 Pellets Tactical)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>12 Gauge Buckshot (00 Buck / 9 Pellets Tactical)</span><span class="text-[10px] font-mono-code bg-orange-50 text-orange-700 px-1.5 py-0.5 rounded font-bold">12GA</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('12 Gauge Rifled Slug (Heavy Solid Lead Penetrator)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>12 Gauge Rifled Slug (Heavy Solid Lead Penetrator)</span><span class="text-[10px] font-mono-code bg-orange-50 text-orange-700 px-1.5 py-0.5 rounded font-bold">12GA</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('12 Gauge Birdshot / Game Load Cartridges', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>12 Gauge Birdshot / Game Load Cartridges</span><span class="text-[10px] font-mono-code bg-orange-50 text-orange-700 px-1.5 py-0.5 rounded font-bold">12GA</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('12 Gauge Less-Lethal Rubber Baton / Beanbag Round', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>12 Gauge Less-Lethal Rubber Baton / Beanbag Round</span><span class="text-[10px] font-mono-code bg-orange-50 text-orange-700 px-1.5 py-0.5 rounded font-bold">12GA</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('20 Gauge Shotgun Cartridge (Buck &amp; Slug)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>20 Gauge Shotgun Cartridge (Buck &amp; Slug)</span><span class="text-[10px] font-mono-code bg-orange-50 text-orange-700 px-1.5 py-0.5 rounded font-bold">20GA</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('16 Gauge Shotgun Cartridge', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>16 Gauge Shotgun Cartridge</span><span class="text-[10px] font-mono-code bg-orange-50 text-orange-700 px-1.5 py-0.5 rounded font-bold">16GA</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.410 Bore Shotgun Shell (Buckshot / Slug)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.410 Bore Shotgun Shell (Buckshot / Slug)</span><span class="text-[10px] font-mono-code bg-orange-50 text-orange-700 px-1.5 py-0.5 rounded font-bold">.410</span></button></li>

                                                    <!-- Heavy & Anti-Materiel Ammunition -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Heavy &amp; Anti-Materiel Ammunition</li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.50 BMG (12.7x99mm NATO Armor-Piercing Incendiary)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.50 BMG (12.7x99mm NATO Armor-Piercing Incendiary)</span><span class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">.50</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('12.7x108mm Russian Heavy Machine Gun Cartridge', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>12.7x108mm Russian Heavy Machine Gun Cartridge</span><span class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">12.7</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.338 Lapua Magnum (8.6x70mm Long-Range Sniper)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.338 Lapua Magnum (8.6x70mm Long-Range Sniper)</span><span class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">.338</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.300 Winchester Magnum (.300 Win Mag Match)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.300 Winchester Magnum (.300 Win Mag Match)</span><span class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">.300</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('14.5x114mm Heavy Armor-Piercing Cartridge', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>14.5x114mm Heavy Armor-Piercing Cartridge</span><span class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">14.5</span></button></li>

                                                    <!-- Rimfire & Specialty Ammunition -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Rimfire &amp; Specialty Ammunition</li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.22 Long Rifle (.22 LR Rimfire Round)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.22 Long Rifle (.22 LR Rimfire Round)</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded font-bold">.22LR</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('.22 WMR (.22 Magnum Rimfire Cartridge)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.22 WMR (.22 Magnum Rimfire Cartridge)</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded font-bold">.22WMR</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('40x46mm High-Explosive Grenade Round (M203 / M320)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>40x46mm High-Explosive Grenade Round (M203 / M320)</span><span class="text-[10px] font-mono-code bg-red-100 text-red-900 px-1.5 py-0.5 rounded font-bold">40MM</span></button></li>
                                                    <li class="ammo-item" onclick="selectAmmoType('37mm / 40mm Tear Gas / CS Smoke / Flare Round', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>37mm / 40mm Tear Gas / CS Smoke / Flare Round</span><span class="text-[10px] font-mono-code bg-amber-100 text-amber-900 px-1.5 py-0.5 rounded font-bold">37MM</span></button></li>
                                                </ul>
                                            </div>
                                        </div>

                                        <!-- ELECTRONIC DEVICE SELECT DROPDOWN (shown when Electronic Devices is selected) -->
                                        <div id="deviceSelectWrapper" class="hidden relative z-40">
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                </span>
                                                <input
                                                    type="text"
                                                    id="deviceSearchInput"
                                                    placeholder="Select or type custom electronic device type..."
                                                    autocomplete="off"
                                                    class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-500 placeholder:font-normal cursor-text"
                                                    onclick="openDeviceListPanel(this)"
                                                    oninput="filterDeviceList(this)"
                                                    onfocus="openDeviceListPanel(this)"
                                                >
                                                <input type="hidden" id="newSeizureNameDevice" name="newSeizureNameDevice" value="">
                                            </div>
                                            <div id="deviceListPanel" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden" style="max-height:240px;overflow-y:auto">
                                                <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                    <input type="text" id="deviceSearchInner" placeholder="Type to filter electronic devices..." oninput="filterDeviceListInner(this)" class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                </div>
                                                <ul id="deviceList" class="py-1.5 space-y-0.5 px-1.5">
                                                    <!-- Smartphones & Mobile Devices -->
                                                    <li class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Smartphones &amp; Mobile Devices</li>
                                                    <li class="device-item" onclick="selectDeviceType('Apple iPhone (iOS Smartphone)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Apple iPhone (iOS Smartphone)</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">IPHONE</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('Android Smartphone (Samsung / Google Pixel / Xiaomi)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Android Smartphone (Samsung / Google Pixel / Xiaomi)</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">ANDROID</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('Feature Phone / Burner Keypad Phone', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Feature Phone / Burner Keypad Phone</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">BURNER</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('Satellite Phone (Iridium / Thuraya / Inmarsat)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Satellite Phone (Iridium / Thuraya / Inmarsat)</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">SAT-PHONE</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('Tablet / iPad (iOS / Android / Windows Tablet)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Tablet / iPad (iOS / Android / Windows Tablet)</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">TABLET</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('Smartwatch / Wearable Fitness Tracker', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Smartwatch / Wearable Fitness Tracker</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">WATCH</span></button></li>

                                                    <!-- Computers & Laptops -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Computers &amp; Laptops</li>
                                                    <li class="device-item" onclick="selectDeviceType('Laptop / Notebook Computer (MacBook / Windows / Linux)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Laptop / Notebook Computer (MacBook / Windows / Linux)</span><span class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">LAPTOP</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('Desktop PC / Workstation Computer', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Desktop PC / Workstation Computer</span><span class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">DESKTOP</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('Encrypted / Hardened Custom Laptop', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Encrypted / Hardened Custom Laptop</span><span class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">ENCRYPTED</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('Enterprise Server / Rackmount Server Unit', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Enterprise Server / Rackmount Server Unit</span><span class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">SERVER</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('Single-Board Computer (Raspberry Pi / Arduino / Mini PC)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Single-Board Computer (Raspberry Pi / Arduino / Mini PC)</span><span class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">SBC</span></button></li>

                                                    <!-- Storage & Memory Media -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Storage &amp; Memory Media</li>
                                                    <li class="device-item" onclick="selectDeviceType('External Hard Drive (HDD / Portable Hard Disk)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>External Hard Drive (HDD / Portable Hard Disk)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">HDD</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('Portable Solid State Drive (External SSD / NVMe)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Portable Solid State Drive (External SSD / NVMe)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">SSD</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('USB Flash Drive / Thumb Drive / Pen Drive', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>USB Flash Drive / Thumb Drive / Pen Drive</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">USB</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('Memory Card (SD Card / MicroSD / CF Card)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Memory Card (SD Card / MicroSD / CF Card)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">SD-CARD</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('NAS / Network Attached Storage Array', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>NAS / Network Attached Storage Array</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">NAS</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('Optical Media (CD / DVD / Blu-ray Disc)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Optical Media (CD / DVD / Blu-ray Disc)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">DISC</span></button></li>

                                                    <!-- Skimming, Fraud & Hardware Attack Devices -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Skimming, Fraud &amp; Hardware Attack Devices</li>
                                                    <li class="device-item" onclick="selectDeviceType('ATM Card Skimmer / Bezel Overlay Device', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>ATM Card Skimmer / Bezel Overlay Device</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">SKIMMER</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('POS Terminal / Handheld Card Reader', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>POS Terminal / Handheld Card Reader</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">POS</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('Magstripe Card Writer / Encoder (MSR / MSR605)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Magstripe Card Writer / Encoder (MSR / MSR605)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">ENCODER</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('SIM Box / Multi-SIM GSM Cellular Gateway', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>SIM Box / Multi-SIM GSM Cellular Gateway</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">SIMBOX</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('BadUSB / Hardware Keylogger / Rubber Ducky', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>BadUSB / Hardware Keylogger / Rubber Ducky</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">ATTACK-USB</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('RFID / NFC Cloner / Flipper Zero Device', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>RFID / NFC Cloner / Flipper Zero Device</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">CLONER</span></button></li>

                                                    <!-- Crypto & Financial Hardware -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Crypto &amp; Financial Hardware</li>
                                                    <li class="device-item" onclick="selectDeviceType('Cryptocurrency Hardware Wallet (Ledger / Trezor / KeepKey)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Cryptocurrency Hardware Wallet (Ledger / Trezor / KeepKey)</span><span class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">HW-WALLET</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('Crypto Mining Rig / ASIC Miner (Antminer / GPU Rig)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Crypto Mining Rig / ASIC Miner (Antminer / GPU Rig)</span><span class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">MINER</span></button></li>

                                                    <!-- Cameras, Drones & Surveillance Equipment -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Cameras, Drones &amp; Surveillance Equipment</li>
                                                    <li class="device-item" onclick="selectDeviceType('Security Camera / CCTV DVR / NVR Unit', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Security Camera / CCTV DVR / NVR Unit</span><span class="text-[10px] font-mono-code bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded font-bold">CCTV</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('Digital Camera / Camcorder / DSLR', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Digital Camera / Camcorder / DSLR</span><span class="text-[10px] font-mono-code bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded font-bold">CAMERA</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('Covert Spy Camera / Hidden Camera Device', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Covert Spy Camera / Hidden Camera Device</span><span class="text-[10px] font-mono-code bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded font-bold">SPY-CAM</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('Drone / Unmanned Aerial Vehicle (DJI / FPV Drone)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Drone / Unmanned Aerial Vehicle (DJI / FPV Drone)</span><span class="text-[10px] font-mono-code bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded font-bold">DRONE</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('GPS Tracking Bug / Tracker Tag (AirTag / GPS Unit)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>GPS Tracking Bug / Tracker Tag (AirTag / GPS Unit)</span><span class="text-[10px] font-mono-code bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded font-bold">GPS</span></button></li>

                                                    <!-- Networking & Communication Equipment -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Networking &amp; Communication Equipment</li>
                                                    <li class="device-item" onclick="selectDeviceType('Wi-Fi Router / Wireless Access Point / Modem', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Wi-Fi Router / Wireless Access Point / Modem</span><span class="text-[10px] font-mono-code bg-cyan-50 text-cyan-800 px-1.5 py-0.5 rounded font-bold">ROUTER</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('Network Switch / Managed Network Hardware', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Network Switch / Managed Network Hardware</span><span class="text-[10px] font-mono-code bg-cyan-50 text-cyan-800 px-1.5 py-0.5 rounded font-bold">SWITCH</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('Mobile Hotspot / MiFi Device / 4G-5G Dongle', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Mobile Hotspot / MiFi Device / 4G-5G Dongle</span><span class="text-[10px] font-mono-code bg-cyan-50 text-cyan-800 px-1.5 py-0.5 rounded font-bold">MIFI</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('Two-Way Radio / Walkie-Talkie (VHF / UHF Transceiver)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Two-Way Radio / Walkie-Talkie (VHF / UHF Transceiver)</span><span class="text-[10px] font-mono-code bg-cyan-50 text-cyan-800 px-1.5 py-0.5 rounded font-bold">RADIO</span></button></li>

                                                    <!-- Gaming & Audio/Video Equipment -->
                                                    <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Gaming &amp; Audio/Video Equipment</li>
                                                    <li class="device-item" onclick="selectDeviceType('Gaming Console (PlayStation / Xbox / Nintendo)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Gaming Console (PlayStation / Xbox / Nintendo)</span><span class="text-[10px] font-mono-code bg-teal-50 text-teal-800 px-1.5 py-0.5 rounded font-bold">CONSOLE</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('Handheld Gaming Unit (Steam Deck / ROG Ally)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Handheld Gaming Unit (Steam Deck / ROG Ally)</span><span class="text-[10px] font-mono-code bg-teal-50 text-teal-800 px-1.5 py-0.5 rounded font-bold">HANDHELD</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('Digital Voice Recorder / Dictaphone', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Digital Voice Recorder / Dictaphone</span><span class="text-[10px] font-mono-code bg-teal-50 text-teal-800 px-1.5 py-0.5 rounded font-bold">AUDIO</span></button></li>
                                                    <li class="device-item" onclick="selectDeviceType('Smart TV / Media Streaming Dongle (Apple TV / Firestick)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Smart TV / Media Streaming Dongle (Apple TV / Firestick)</span><span class="text-[10px] font-mono-code bg-teal-50 text-teal-800 px-1.5 py-0.5 rounded font-bold">MEDIA</span></button></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ELECTRONIC DEVICE SPECIFICATIONS PANEL (shown when Electronic Devices is selected) -->
                                    <div id="deviceFieldsWrapper" class="hidden col-span-full bg-blue-50/70 p-4 rounded-2xl border border-blue-200/80 shadow-2xs space-y-3">
                                        <div class="flex items-center space-x-2 text-slate-800">
                                            <div class="w-6 h-6 rounded-md bg-blue-600 text-white flex items-center justify-center font-bold text-xs shadow-2xs">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                            </div>
                                            <span class="text-xs font-extrabold uppercase tracking-wider text-blue-950">Electronic Device Forensic Specifications</span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3.5">
                                            <!-- 1. MODEL -->
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Model</label>
                                                <div class="relative flex items-center group">
                                                    <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                                                    </span>
                                                    <input type="text" id="newDevModel" placeholder="e.g. iPhone 15 Pro Max" class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400">
                                                </div>
                                            </div>

                                            <!-- 2. SERIAL NUMBER -->
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Serial Number</label>
                                                <div class="relative flex items-center group">
                                                    <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors font-mono-code font-bold text-xs">#</span>
                                                    <input type="text" id="newDevSerialNo" placeholder="e.g. F2LZX984K102" class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400 font-mono-code">
                                                </div>
                                            </div>

                                            <!-- 3. IMEI 1 -->
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">IMEI 1 <span class="text-[9px] text-slate-400 font-normal lowercase">(if applicable)</span></label>
                                                <div class="relative flex items-center group">
                                                    <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                    </span>
                                                    <input type="text" id="newDevImei1" placeholder="e.g. 356938035643809" class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400 font-mono-code">
                                                </div>
                                            </div>

                                            <!-- 4. IMEI 2 -->
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">IMEI 2 <span class="text-[9px] text-slate-400 font-normal lowercase">(if applicable)</span></label>
                                                <div class="relative flex items-center group">
                                                    <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                    </span>
                                                    <input type="text" id="newDevImei2" placeholder="e.g. 356938035643817" class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400 font-mono-code">
                                                </div>
                                            </div>

                                            <!-- 5. MAC ADDRESS -->
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">MAC Address <span class="text-[9px] text-slate-400 font-normal lowercase">(if applicable)</span></label>
                                                <div class="relative flex items-center group">
                                                    <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                    </span>
                                                    <input type="text" id="newDevMacAddress" placeholder="e.g. 00:1A:2B:3C:4D:5E" class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400 font-mono-code">
                                                </div>
                                            </div>

                                            <!-- 6. STORAGE CAPACITY -->
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Storage Capacity</label>
                                                <div class="relative flex items-center group">
                                                    <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                                                    </span>
                                                    <input type="text" id="newDevStorage" placeholder="e.g. 256 GB / 1 TB NVMe SSD" class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400">
                                                </div>
                                            </div>

                                            <!-- 7. COLOUR -->
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Colour</label>
                                                <div class="relative flex items-center group">
                                                    <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                                                    </span>
                                                    <input type="text" id="newDevColour" placeholder="e.g. Space Gray / Midnight Black" class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400">
                                                </div>
                                            </div>

                                            <!-- 8. OTHER IDENTIFYING MARKS -->
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Other Identifying Marks</label>
                                                <div class="relative flex items-center group">
                                                    <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                    </span>
                                                    <input type="text" id="newDevMarks" placeholder="e.g. Cracked back glass, custom sticker" class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- OTHER EXHIBIT DESCRIPTION PANEL (shown when Other Exhibits is selected) -->
                                    <div id="otherExhibitDescriptionWrapper" class="other-exhibit-description-wrapper hidden col-span-full bg-slate-50/90 p-4 rounded-2xl border border-slate-300/80 shadow-2xs space-y-3">
                                        <div class="flex items-center space-x-2 text-slate-800">
                                            <div class="w-6 h-6 rounded-md bg-slate-700 text-white flex items-center justify-center font-bold text-xs shadow-2xs">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 012.828 0L19.828 4.828a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                                            </div>
                                            <span class="text-xs font-extrabold uppercase tracking-wider text-slate-900">Specify Other Exhibit Description &amp; Details</span>
                                        </div>
                                        <div>
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                Exhibit Description / Specifications <span class="text-red-600 font-bold">*</span>
                                            </label>
                                            <textarea
                                                id="newOtherExhibitDescription"
                                                name="other_exhibit_description"
                                                rows="3"
                                                placeholder="Type detailed description and specifications of this exhibit..."
                                                class="other-exhibit-description-input w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400 font-sans"
                                            ></textarea>
                                        </div>
                                    </div>

                                    <!-- ESTIMATED STREET VALUE -->
                                    <div>
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Estimated Street Value</label>
                                        <div class="relative flex items-center group">
                                            <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors font-mono-code font-bold text-xs">₵</span>
                                            <input type="text" id="newSeizureValue" placeholder="e.g. GHS 1,500,000 / $120,000" class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                        </div>
                                    </div>

                                    <!-- QUANTITY, PACKAGING TYPE, WEIGHT & UNIT METRICS PANEL -->
                                    <div class="col-span-full bg-white/90 p-4 rounded-2xl border border-slate-300/80 shadow-2xs space-y-3">
                                        <div class="flex items-center space-x-2 text-slate-800">
                                            <div class="w-6 h-6 rounded-md bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                            </div>
                                            <span class="text-xs font-extrabold uppercase tracking-wider text-slate-900">Seizure Quantification &amp; Packaging Metrics</span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3.5">
                                            <!-- 1. QUANTITY -->
                                            <div>
                                                <label id="quantityLabel" class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Quantity</label>
                                                <div class="relative flex items-center group">
                                                    <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors font-mono-code font-bold text-xs">#</span>
                                                    <input type="text" inputmode="decimal" id="newSeizureQuantity" placeholder="e.g. 10 or 50,000" class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                            </div>

                                            <!-- 2. PACKAGING TYPE -->
                                            <div class="relative z-30">
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Packaging Type</label>
                                                <div id="packagingSelectWrapper" class="relative">
                                                    <div class="relative">
                                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                                        </span>
                                                        <input
                                                            type="text"
                                                            id="pkgSearchInput"
                                                            placeholder="Select or type packaging..."
                                                            autocomplete="off"
                                                            class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400 placeholder:font-normal cursor-text"
                                                            onclick="openPkgListPanel(this)"
                                                            oninput="filterPackagingList(this)"
                                                            onfocus="openPkgListPanel(this)"
                                                        >
                                                        <input type="hidden" id="newSeizurePackaging" value="">
                                                    </div>
                                                    <div id="pkgListPanel" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden" style="max-height:220px;overflow-y:auto">
                                                        <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                            <input type="text" id="pkgSearchInner" placeholder="Type to filter packaging..." oninput="filterPackagingListInner(this)" class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                        </div>
                                                        <ul id="pkgList" class="py-1.5 space-y-0.5 px-1.5">
                                                            <li class="pkg-item" onclick="selectPackagingType('Slabs', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Slabs</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">SLB</span></button></li>
                                                            <li class="pkg-item" onclick="selectPackagingType('Blocks', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Blocks</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">BLK</span></button></li>
                                                            <li class="pkg-item" onclick="selectPackagingType('Bundles', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Bundles</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">BDL</span></button></li>
                                                            <li class="pkg-item" onclick="selectPackagingType('Sacks', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Sacks</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">SCK</span></button></li>
                                                            <li class="pkg-item" onclick="selectPackagingType('Cartons', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Cartons</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">CTN</span></button></li>
                                                            <li class="pkg-item" onclick="selectPackagingType('Boxes', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Boxes</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">BOX</span></button></li>
                                                            <li class="pkg-item" onclick="selectPackagingType('Rounds', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Rounds</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">RND</span></button></li>
                                                            <li class="pkg-item" onclick="selectPackagingType('Magazines', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Magazines</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">MAG</span></button></li>
                                                            <li class="pkg-item" onclick="selectPackagingType('Pieces', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Pieces</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">PCS</span></button></li>
                                                            <li class="pkg-item" onclick="selectPackagingType('Units', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Units</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">UNT</span></button></li>
                                                            <li class="pkg-item" onclick="selectPackagingType('Wraps', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Wraps</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">WRP</span></button></li>
                                                            <li class="pkg-item" onclick="selectPackagingType('Rolls', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Rolls</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">RLL</span></button></li>
                                                            <li class="pkg-item" onclick="selectPackagingType('Capsules', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Capsules</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">CAP</span></button></li>
                                                            <li class="pkg-item" onclick="selectPackagingType('Tablets', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Tablets</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">TAB</span></button></li>
                                                            <li class="pkg-item" onclick="selectPackagingType('Bags', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Bags</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">BAG</span></button></li>
                                                            <li class="pkg-item" onclick="selectPackagingType('Pouches', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Pouches</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">PCH</span></button></li>
                                                            <li class="pkg-item" onclick="selectPackagingType('Drums', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Drums</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">DRM</span></button></li>
                                                            <li class="pkg-item" onclick="selectPackagingType('Barrels', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Barrels</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">BRL</span></button></li>
                                                            <li class="pkg-item" onclick="selectPackagingType('Vials', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Vials</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">VIL</span></button></li>
                                                            <li class="pkg-item" onclick="selectPackagingType('Ampoules', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Ampoules</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">AMP</span></button></li>
                                                            <li class="pkg-item" onclick="selectPackagingType('Bottles', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Bottles</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">BTL</span></button></li>
                                                            <li class="pkg-item" onclick="selectPackagingType('Packets', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Packets</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">PKT</span></button></li>
                                                            <li class="pkg-item" onclick="selectPackagingType('Envelopes', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Envelopes</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">ENV</span></button></li>
                                                            <li class="pkg-item" onclick="selectPackagingType('Parcels', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Parcels</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">PCL</span></button></li>
                                                            <li class="pkg-item" onclick="selectPackagingType('Packages', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Packages</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">PKG</span></button></li>
                                                            <li class="pkg-item" onclick="selectPackagingType('Bales', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Bales</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">BLE</span></button></li>
                                                            <li class="pkg-item" onclick="selectPackagingType('Jars', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Jars</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">JAR</span></button></li>
                                                            <li class="pkg-item" onclick="selectPackagingType('Containers', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Containers</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">CTR</span></button></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- 3. Weight -->
                                            <div>
                                                <label id="weightLabel" class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Weight</label>
                                                <div class="relative flex items-center group">
                                                    <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors font-mono-code font-bold text-xs">⚖</span>
                                                    <input type="number" step="0.01" id="newSeizureWeight" placeholder="e.g. 25.50" class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                            </div>

                                            <!-- 4. MEASUREMENT UNIT -->
                                            <div class="relative z-20">
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Measurement Unit</label>
                                                <div id="unitSelectWrapper" class="relative">
                                                    <div class="relative">
                                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                        </span>
                                                        <input
                                                            type="text"
                                                            id="unitSearchInput"
                                                            placeholder="Select or type unit..."
                                                            autocomplete="off"
                                                            class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400 placeholder:font-normal cursor-text"
                                                            onclick="openUnitListPanel(this)"
                                                            oninput="filterUnitList(this)"
                                                            onfocus="openUnitListPanel(this)"
                                                        >
                                                        <input type="hidden" id="newSeizureUnit" value="">
                                                    </div>
                                                    <div id="unitListPanel" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden" style="max-height:220px;overflow-y:auto">
                                                        <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                            <input type="text" id="unitSearchInner" placeholder="Type to filter units..." oninput="filterUnitListInner(this)" class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                        </div>
                                                        <ul id="unitList" class="py-1.5 space-y-0.5 px-1.5">
                                                            <li class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Weight &amp; Mass</li>
                                                            <li class="unit-item" onclick="selectUnit('Kilograms (kg)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Kilograms</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">kg</span></button></li>
                                                            <li class="unit-item" onclick="selectUnit('Grams (g)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Grams</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">g</span></button></li>
                                                            <li class="unit-item" onclick="selectUnit('Tonnes (t)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Tonnes</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">t</span></button></li>
                                                            <li class="unit-item" onclick="selectUnit('Milligrams (mg)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Milligrams</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">mg</span></button></li>
                                                            <li class="unit-item" onclick="selectUnit('Pounds (lbs)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Pounds</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">lbs</span></button></li>
                                                            <li class="unit-item" onclick="selectUnit('Ounces (oz)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Ounces</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">oz</span></button></li>
                                                            <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Volume &amp; Liquid</li>
                                                            <li class="unit-item" onclick="selectUnit('Litres (L)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Litres</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">L</span></button></li>
                                                            <li class="unit-item" onclick="selectUnit('Millilitres (mL)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Millilitres</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">mL</span></button></li>
                                                            <li class="unit-item" onclick="selectUnit('Gallons (gal)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Gallons</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">gal</span></button></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- CONCEALMENT METHOD -->
                                    <div>
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Concealment &amp; Packaging Notes</label>
                                        <div class="relative flex items-center group">
                                            <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                            </span>
                                            <input type="text" id="newSeizureConcealment" placeholder="e.g. Vacuum foil wrapped in false trunk bottom" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                        </div>
                                    </div>

                                    <!-- DATE, TIME & LOCATION SEIZED -->
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 col-span-full">
                                        <!-- DATE SEIZED -->
                                        <div class="min-w-0">
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Date Seized <span class="text-red-600 font-bold">*</span></label>
                                            <div class="relative flex items-center group">
                                                <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors pointer-events-none">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                </span>
                                                <input type="date" id="newSeizureDate" class="w-full min-w-0 pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                        </div>

                                        <!-- TIME SEIZED -->
                                        <div class="min-w-0">
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Time Seized</label>
                                            <div class="relative flex items-center group">
                                                <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors pointer-events-none">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                </span>
                                                <input type="time" id="newSeizureTime" class="w-full min-w-0 pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                                            </div>
                                        </div>

                                        <!-- LOCATION SEIZED -->
                                        <div class="min-w-0">
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Location Seized <span class="text-red-600 font-bold">*</span></label>
                                            <div class="relative flex items-center group">
                                                <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors pointer-events-none">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                </span>
                                                <input type="text" id="newSeizureLocation" name="location_seized" placeholder="e.g. Tema Port, Gate 4 — Container Terminal" class="w-full min-w-0 pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ROW: INVESTIGATING OFFICER -->
                                    <div class="col-span-full grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <!-- INVESTIGATING OFFICER -->
                                        <div class="col-span-full">
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                Investigating Officer
                                            </label>
                                            <div class="relative flex items-center group">
                                                <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors pointer-events-none">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                </span>
                                                <input type="text" id="newSeizureInvestigatingOfficer" name="investigating_officer"
                                                    placeholder="e.g. Insp. Emmanuel Mensah / Service ID SD-0199"
                                                    class="seizure-investigating-officer-input w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                        </div>

                                        <!-- ROW: SEIZING OFFICERS & SOURCE/REQUESTING UNIT -->
                                        <div class="col-span-full grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                                            <!-- SEIZING OFFICERS (MULTI-ROW) -->
                                            <div>
                                                <div class="flex items-center justify-between mb-1.5">
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase">
                                                        Seizing Officer(s) <span class="text-red-600 font-bold">*</span>
                                                    </label>
                                                    <button type="button" onclick="addSeizureOfficerRow(this)" class="inline-flex items-center text-[10px] font-extrabold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-2.5 py-1 rounded-lg transition-colors cursor-pointer shadow-2xs">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                                        Add Seizing Officer
                                                    </button>
                                                </div>

                                                <!-- OFFICER ROWS CONTAINER -->
                                                <div id="seizureOfficersContainer" class="space-y-2">
                                                    <!-- Row 1 -->
                                                    <div class="seizure-officer-row p-2.5 bg-slate-50 border border-slate-300/80 rounded-xl">
                                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                            <div class="relative flex items-center group">
                                                                <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors pointer-events-none">
                                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                                </span>
                                                                <input type="text" class="seizure-officer-name w-full pl-8 pr-3 py-2 bg-white border border-slate-300 rounded-lg font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400" placeholder="Officer Full Name (e.g. DSP Kwame Mensah)">
                                                            </div>
                                                            <div class="flex items-center gap-1.5">
                                                                <div class="relative flex-1 flex items-center group">
                                                                    <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors pointer-events-none">
                                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 012-2h2a2 2 0 012 2v1m-4 0h4"/></svg>
                                                                    </span>
                                                                    <input type="text" class="seizure-officer-service-id w-full pl-8 pr-3 py-2 bg-white border border-slate-300 rounded-lg font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400 font-mono-code" placeholder="Service ID / Badge No (e.g. SD-0042)">
                                                                </div>
                                                                <button type="button" onclick="removeSeizureOfficerRow(this)" class="remove-officer-btn hidden p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg border border-transparent hover:border-red-200 transition-colors cursor-pointer" title="Remove Officer">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- SOURCE / REQUESTING UNIT -->
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                    Source/Requesting Unit
                                                </label>
                                                <div class="relative flex items-center group">
                                                    <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors pointer-events-none">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                        </svg>
                                                    </span>
                                                    <input type="text" id="newSeizureSourceRequestingUnit" name="source_requesting_unit"
                                                        placeholder="e.g. Enforcement and Operations Unit, KIA Command, DLEU..."
                                                        class="seizure-source-requesting-unit-input w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ROW: REASON FOR SEIZURE & REMARKS / OBSERVATIONS -->
                                        <div class="col-span-full grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <!-- REASON FOR SEIZURE -->
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Reason for Seizure <span class="text-red-600 font-bold">*</span></label>
                                                <div class="relative flex items-start group">
                                                    <span class="absolute left-3 top-3 text-slate-400 group-focus-within:text-black transition-colors pointer-events-none">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                    </span>
                                                    <textarea id="newSeizureReason" name="seizure_reason" rows="3" placeholder="e.g. Suspected drug trafficking — commodity found concealed in vehicle false compartment during routine checkpoint stop." class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400 resize-none"></textarea>
                                                </div>
                                            </div>

                                            <!-- REMARKS / OBSERVATIONS (OPTIONAL) -->
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                                    <span>Remarks / Observations <span class="text-slate-400 font-normal lowercase">(optional)</span></span>
                                                    <span class="text-[9px] font-mono-code font-bold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200 uppercase">REMARK</span>
                                                </label>
                                                <div class="relative flex items-start group">
                                                    <span class="absolute left-3 top-3 text-slate-400 group-focus-within:text-black transition-colors pointer-events-none">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                                        </svg>
                                                    </span>
                                                    <textarea id="newSeizureRemark" name="exhibit_remark" rows="3" data-alt-id="newSeizureExhibitRemark"
                                                        placeholder="e.g. Broken seal, sample retrieved under chain-of-custody, custody observations, packaging notes..."
                                                        class="newSeizureExhibitRemark seizure-remark-input w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400 resize-none"></textarea>
                                                </div>
                                            </div>
                                        </div>

                                </div>

                                <!-- VEHICLE SPECIFIC FIELDS -->
                                <div id="vehicleFieldsWrapper" class="hidden pt-4 border-t border-slate-300/60 mt-4 space-y-5">
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                        </div>
                                        <h4 class="text-sm font-extrabold text-slate-800 uppercase tracking-widest">Vehicle Specific Details</h4>
                                    </div>

                                    <!-- 1. Basic Identification -->
                                    <div class="space-y-3">
                                        <h5 class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-widest bg-emerald-50 px-2 py-1 rounded-md inline-block">1. Basic Identification</h5>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Make <span class="text-red-600 font-bold">*</span></label>
                                                <input type="text" id="vehMake" placeholder="e.g. Toyota" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Model <span class="text-red-600 font-bold">*</span></label>
                                                <input type="text" id="vehModel" placeholder="e.g. Hilux" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                            <div class="relative z-20" id="vehTypeSelectWrapper">
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Vehicle Type</label>
                                                <div class="relative">
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                    </span>
                                                    <input
                                                        type="text"
                                                        id="vehTypeSearchInput"
                                                        placeholder="Select vehicle type..."
                                                        autocomplete="off"
                                                        class="w-full pl-8 pr-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400 placeholder:font-normal cursor-pointer"
                                                        readonly
                                                        onclick="openVehTypeListPanel(this)"
                                                        oninput="filterVehTypeList(this)"
                                                        onfocus="openVehTypeListPanel(this)"
                                                    >
                                                    <input type="hidden" id="vehType" value="">
                                                </div>
                                                <div id="vehTypeListPanel" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden" style="max-height:220px;overflow-y:auto">
                                                    <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                        <input type="text" id="vehTypeSearchInner" placeholder="Type to filter..." oninput="filterVehTypeListInner(this)" class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                    </div>
                                                    <ul id="vehTypeList" class="py-1.5 space-y-0.5 px-1.5">
                                                        <li class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Passenger Vehicles</li>
                                                        <li class="veh-type-item" onclick="selectVehType('Sedan / Saloon', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Sedan / Saloon</button></li>
                                                        <li class="veh-type-item" onclick="selectVehType('Hatchback', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Hatchback</button></li>
                                                        <li class="veh-type-item" onclick="selectVehType('SUV / Crossover', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">SUV / Crossover</button></li>
                                                        <li class="veh-type-item" onclick="selectVehType('Coupe / Sports Car', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Coupe / Sports Car</button></li>
                                                        <li class="veh-type-item" onclick="selectVehType('Convertible', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Convertible</button></li>
                                                        <li class="veh-type-item" onclick="selectVehType('Station Wagon / Estate', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Station Wagon / Estate</button></li>
                                                        <li class="veh-type-item" onclick="selectVehType('Minivan / MPV', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Minivan / MPV</button></li>
                                                        
                                                        <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Commercial &amp; Utility</li>
                                                        <li class="veh-type-item" onclick="selectVehType('Pickup Truck', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Pickup Truck</button></li>
                                                        <li class="veh-type-item" onclick="selectVehType('Panel Van / Cargo Van', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Panel Van / Cargo Van</button></li>
                                                        <li class="veh-type-item" onclick="selectVehType('Minibus / Trotro', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Minibus / Trotro</button></li>
                                                        <li class="veh-type-item" onclick="selectVehType('Large Bus / Coach', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Large Bus / Coach</button></li>
                                                        <li class="veh-type-item" onclick="selectVehType('Box Truck / Rigid Truck', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Box Truck / Rigid Truck</button></li>
                                                        <li class="veh-type-item" onclick="selectVehType('Heavy Duty / Articulated Truck', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Heavy Duty / Articulated Truck</button></li>
                                                        
                                                        <li class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Other Types</li>
                                                        <li class="veh-type-item" onclick="selectVehType('Motorcycle / Motorbike', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Motorcycle / Motorbike</button></li>
                                                        <li class="veh-type-item" onclick="selectVehType('Tricycle (Aboboyaa / Pragya)', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Tricycle (Aboboyaa / Pragya)</button></li>
                                                        <li class="veh-type-item" onclick="selectVehType('Trailer / Caravan', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Trailer / Caravan</button></li>
                                                        <li class="veh-type-item" onclick="selectVehType('Agricultural / Tractor', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Agricultural / Tractor</button></li>
                                                        <li class="veh-type-item" onclick="selectVehType('Construction Equipment', event)"><button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Construction Equipment</button></li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Year of Manufacture</label>
                                                <input type="text" id="vehYear" placeholder="e.g. 2019" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Colour</label>
                                                <input type="text" id="vehColour" placeholder="e.g. White" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Registration Number <span class="text-red-600 font-bold">*</span></label>
                                                <input type="text" id="vehRegNo" placeholder="e.g. GT 1234-21" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">VIN / Chassis Number</label>
                                                <input type="text" id="vehVIN" placeholder="e.g. JT11234567890ABC" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Engine Number</label>
                                                <input type="text" id="vehEngineNo" placeholder="e.g. 1TR1234567" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Country of Registration</label>
                                                <input type="text" id="vehCountryReg" placeholder="e.g. Ghana" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 2. Ownership & Possession -->
                                    <div class="space-y-3">
                                        <h5 class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-widest bg-emerald-50 px-2 py-1 rounded-md inline-block">2. Ownership &amp; Possession</h5>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Registered Owner</label>
                                                <input type="text" id="vehOwner" placeholder="e.g. John Doe" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Driver / Person in Possession</label>
                                                <input type="text" id="vehDriver" placeholder="e.g. Kwame Mensah" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Owner's Contact</label>
                                                <input type="text" id="vehOwnerContact" placeholder="e.g. 0244123456" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Docs Available?</label>
                                                <select id="vehDocsAvailable" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                                                    <option value="">Select...</option>
                                                    <option value="Yes">Yes</option>
                                                    <option value="No">No</option>
                                                </select>
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Reg/Title Doc Reference</label>
                                                <input type="text" id="vehDocRef" placeholder="e.g. DVLA Title #998877" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 3. Vehicle Condition -->
                                    <div class="space-y-3">
                                        <h5 class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-widest bg-emerald-50 px-2 py-1 rounded-md inline-block">3. Vehicle Condition</h5>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Overall Condition</label>
                                                <select id="vehCondition" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                                                    <option value="">Select...</option>
                                                    <option value="Excellent">Excellent / Driveable</option>
                                                    <option value="Good">Good</option>
                                                    <option value="Fair">Fair</option>
                                                    <option value="Poor">Poor / Damaged</option>
                                                    <option value="Non-Driveable">Non-Driveable / Towed</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Fuel Level</label>
                                                <select id="vehFuel" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                                                    <option value="">Select...</option>
                                                    <option value="Full">Full</option>
                                                    <option value="3/4">3/4</option>
                                                    <option value="Half">Half (1/2)</option>
                                                    <option value="1/4">1/4</option>
                                                    <option value="Empty">Empty (Light on)</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Keys Received?</label>
                                                <div class="flex items-center gap-2 mt-1">
                                                    <select id="vehKeysRecv" class="w-1/2 px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                                                        <option value="">Select...</option>
                                                        <option value="Yes">Yes</option>
                                                        <option value="No">No</option>
                                                    </select>
                                                    <input type="number" id="vehKeyCount" placeholder="Qty" min="0" class="w-1/2 px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                            </div>
                                            <div class="md:col-span-3">
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Existing Damage</label>
                                                <input type="text" id="vehDamage" placeholder="e.g. Scratches on front right bumper, cracked left taillight" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                            <div class="md:col-span-3">
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Visible Modifications / Compartments</label>
                                                <input type="text" id="vehMods" placeholder="e.g. False floor welded in trunk, tinted windows" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                            <div class="md:col-span-3">
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Notable Identifying Features</label>
                                                <input type="text" id="vehFeatures" placeholder="e.g. Custom alloy wheels, stickers on rear window" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- WEAPON SPECIFIC FIELDS -->
                                <div id="weaponFieldsWrapper" class="hidden pt-4 border-t border-slate-300/60 mt-4 space-y-5">
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        </div>
                                        <h4 class="text-sm font-extrabold text-slate-800 uppercase tracking-widest">Weapon / Firearm Specific Details</h4>
                                    </div>

                                    <!-- 1. Weapon Identification & Physical Specs -->
                                    <div class="space-y-3">
                                        <h5 class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-widest bg-emerald-50 px-2 py-1 rounded-md inline-block">1. Identification &amp; Specifications</h5>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Model</label>
                                                <input type="text" id="wpnModel" placeholder="e.g. 19 Gen 5, M&P 9, Type 56, Glock 17" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Serial Number</label>
                                                <input type="text" id="wpnSerialNo" placeholder="e.g. G17-984210X / Defaced" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400 font-mono-code">
                                            </div>
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Calibre / Gauge</label>
                                                <input type="text" id="wpnCalibre" placeholder="e.g. 9mm, 7.62x39mm, 12-Gauge, .45 ACP" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Colour</label>
                                                <input type="text" id="wpnColour" placeholder="e.g. Matte Black, Silver, Camouflage" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Approx. Size / Length</label>
                                                <input type="text" id="wpnSize" placeholder="e.g. 18.5 cm (Overall), Short Barrel, 87 cm" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 2. Recovery Condition & Status -->
                                    <div class="space-y-3 pt-2">
                                        <h5 class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-widest bg-emerald-50 px-2 py-1 rounded-md inline-block">2. Recovery Condition &amp; Location</h5>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Loaded Status</label>
                                                <select id="wpnLoadedStatus" class="w-full appearance-none px-3.5 py-2.5 bg-white border border-slate-300 hover:border-emerald-500 focus:border-emerald-600 rounded-xl font-bold text-slate-800 text-xs shadow-xs focus:outline-none focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer">
                                                    <option value="" class="text-slate-400 font-normal">Select...</option>
                                                    <option value="Loaded" class="font-semibold text-slate-900">Loaded</option>
                                                    <option value="Unloaded" class="font-semibold text-slate-900">Unloaded</option>
                                                    <option value="Round in Chamber" class="font-semibold text-slate-900">Round in Chamber</option>
                                                    <option value="Magazine Attached (Loaded)" class="font-semibold text-slate-900">Magazine Attached (Loaded)</option>
                                                    <option value="Magazine Attached (Empty)" class="font-semibold text-slate-900">Magazine Attached (Empty)</option>
                                                    <option value="Unknown / Unspecified" class="font-semibold text-slate-900">Unknown / Unspecified</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Condition at Recovery</label>
                                                <select id="wpnCondition" class="w-full appearance-none px-3.5 py-2.5 bg-white border border-slate-300 hover:border-emerald-500 focus:border-emerald-600 rounded-xl font-bold text-slate-800 text-xs shadow-xs focus:outline-none focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer">
                                                    <option value="" class="text-slate-400 font-normal">Select...</option>
                                                    <option value="Operable / Good Condition" class="font-semibold text-slate-900">Operable / Good Condition</option>
                                                    <option value="Fair / Used Condition" class="font-semibold text-slate-900">Fair / Used Condition</option>
                                                    <option value="Rusty / Corroded" class="font-semibold text-slate-900">Rusty / Corroded</option>
                                                    <option value="Damaged / Inoperable" class="font-semibold text-slate-900">Damaged / Inoperable</option>
                                                    <option value="Serial Defaced / Tampered" class="font-semibold text-slate-900">Serial Defaced / Tampered</option>
                                                    <option value="Modified / Sawed-Off" class="font-semibold text-slate-900">Modified / Sawed-Off</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Exact Location Found</label>
                                                <input type="text" id="wpnLocationFound" placeholder="e.g. Bedroom closet, Under vehicle driver seat, Roadside ditch" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                            <div class="md:col-span-3">
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Distinguishing Marks / Features</label>
                                                 <textarea id="wpnMarks" rows="2" placeholder="e.g. Scratch on left grip, custom insignia engraved, missing safety switch" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400 resize-none"></textarea>
                                             </div>
                                         </div>
                                     </div>
                                 </div>

                                 <!-- AMMUNITION SPECIFIC FIELDS -->
                                 <div id="ammoFieldsWrapper" class="hidden pt-4 border-t border-slate-300/60 mt-4 space-y-5">
                                     <div class="flex items-center gap-2 mb-2">
                                         <div class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                         </div>
                                         <h4 class="text-sm font-extrabold text-slate-800 uppercase tracking-widest">Ammunition Specific Details</h4>
                                     </div>

                                     <!-- 1. Physical Description -->
                                     <div class="space-y-3">
                                         <h5 class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-widest bg-emerald-50 px-2 py-1 rounded-md inline-block">1. Physical Description</h5>
                                         <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                             <div>
                                                 <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Cartridge Appearance</label>
                                                 <input type="text" id="ammoCartridgeAppearance" placeholder="e.g. Brass casing, FMJ, Nickel-plated" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                             </div>
                                             <div>
                                                 <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Projectile / Bullet Type</label>
                                                 <input type="text" id="ammoProjectileType" placeholder="e.g. Full Metal Jacket (FMJ), Hollow Point, Lead Round Nose" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                             </div>
                                             <div>
                                                 <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Cartridge Case Material</label>
                                                 <input type="text" id="ammoCaseMaterial" placeholder="e.g. Brass, Steel, Aluminium, Plastic" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                             </div>
                                             <div>
                                                 <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Colour / Markings</label>
                                                 <input type="text" id="ammoColourMarkings" placeholder="e.g. Headstamp CBC 9mm LUGER, Red primer sealant" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                             </div>
                                             <div>
                                                 <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Condition</label>
                                                 <select id="ammoCondition" onchange="toggleAmmoOtherCondition(this)" class="w-full appearance-none px-3.5 py-2.5 bg-white border border-slate-300 hover:border-emerald-500 focus:border-emerald-600 rounded-xl font-bold text-slate-800 text-xs shadow-xs focus:outline-none focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer">
                                                     <option value="" class="text-slate-400 font-normal">Select Condition...</option>
                                                     <option value="Intact" class="font-semibold text-slate-900">Intact</option>
                                                     <option value="Damaged" class="font-semibold text-slate-900">Damaged</option>
                                                     <option value="Corroded" class="font-semibold text-slate-900">Corroded</option>
                                                     <option value="Deformed" class="font-semibold text-slate-900">Deformed</option>
                                                     <option value="Other" class="font-semibold text-slate-900">Other</option>
                                                 </select>
                                             </div>
                                             <div id="ammoOtherConditionWrapper" class="hidden">
                                                 <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Describe Other Condition</label>
                                                 <input type="text" id="ammoOtherCondition" placeholder="Specify condition details..." class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                             </div>
                                         </div>
                                     </div>

                                     <!-- 2. Packaging & Sealing -->
                                     <div class="space-y-3 pt-2">
                                         <h5 class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-widest bg-emerald-50 px-2 py-1 rounded-md inline-block">2. Packaging &amp; Sealing</h5>
                                         <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                             <div>
                                                 <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Packaging Type</label>
                                                 <input type="text" id="ammoPackagingType" placeholder="e.g. Cardboard Box, Plastic Ammo Can, Loose Bag" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                             </div>
                                             <div>
                                                 <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Seal / Tamper-Evident Condition</label>
                                                 <input type="text" id="ammoSealCondition" placeholder="e.g. Intact Tamper-Evident Bag #SEAL-90812" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                             </div>
                                             <div>
                                                 <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Package / Reference Number</label>
                                                 <input type="text" id="ammoPackageReferenceNumber" placeholder="e.g. PKG-2026-AM01" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                             </div>
                                             <div>
                                                 <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Condition When Received</label>
                                                 <input type="text" id="ammoConditionWhenReceived" placeholder="e.g. Sealed and untampered" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                             </div>
                                         </div>
                                     </div>
                                 </div>

                                 <!-- SEIZED EXHIBIT PHOTO UPLOAD AREA (MULTIPLE IMAGES) -->
                                <div class="pt-2 border-t border-slate-300/60 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase">
                                            Upload Seized Exhibit Photos / Evidence Images <span class="text-slate-400 font-normal">(Multiple Supported)</span>
                                        </label>
                                        <span id="seizurePhotosCount" class="hidden px-2.5 py-0.5 rounded-full text-[10px] font-mono-code font-bold bg-emerald-100 text-black border border-slate-300 seizure-photos-count">0 Photos Uploaded</span>
                                    </div>

                                    <!-- DROPZONE BOX -->
                                    <div id="seizurePhotoDropzone" onclick="triggerSeizurePhotoUpload(this)" ondragover="event.preventDefault(); this.classList.add('border-emerald-600', 'bg-emerald-50/50');" ondragleave="this.classList.remove('border-emerald-600', 'bg-emerald-50/50');" ondrop="handleSeizurePhotoDrop(event, this);" class="seizure-photo-dropzone w-full min-h-[90px] bg-white rounded-xl border-2 border-dashed border-slate-300 hover:border-emerald-500 flex flex-col items-center justify-center p-4 cursor-pointer group transition-colors shadow-2xs text-center">
                                        <div id="placeholderSeizureBox" class="flex flex-col items-center space-y-1 text-slate-400 group-hover:text-black transition-colors">
                                            <div class="w-9 h-9 rounded-full bg-emerald-50 text-black flex items-center justify-center mb-0.5">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            </div>
                                            <span class="text-xs font-extrabold text-slate-800 group-hover:text-black">Click or drag &amp; drop to upload exhibit photos</span>
                                            <span class="text-[10px] text-slate-500">Select one or multiple PNG, JPG, WEBP evidence photos (up to 15MB each)</span>
                                        </div>
                                    </div>
                                    <input type="file" id="fileSeizurePhoto" accept="image/*" multiple class="fileSeizurePhoto hidden" onchange="previewSeizurePhotos(event, this)">

                                    <!-- DYNAMIC MULTIPLE PHOTOS GALLERY GRID -->
                                    <div id="seizurePhotosGrid" class="hidden grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3 pt-2 seizurePhotosGrid"></div>
                                </div>
                                </div>
                                <!-- END SEIZURES LIST CONTAINER -->

                                <!-- BOTTOM ADD ANOTHER SEIZURE ITEM BUTTON BAR -->
                                <div class="flex items-center justify-center pt-2 pb-1 border-t border-slate-300/60">
                                    <button type="button" onclick="addSeizureBlock()" class="inline-flex items-center px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-sm hover:shadow-md transition-all cursor-pointer group">
                                        <svg class="w-4 h-4 mr-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                        Add Another Seized Item(s)
                                    </button>
                                </div>
                                </div>
                            </div>
                        </div>
                        <!-- DEDICATED SECTIONS 1 TO 4 DOCKET ACTIONS BAR (LAB ANALYSIS ONLY) -->
                        <div class="{{ $isLabAnalysis ? '' : 'hidden' }} p-6 bg-white rounded-2xl border border-emerald-200/90 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold shrink-0 shadow-2xs">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-black text-slate-900 uppercase font-mono-code">Sections 1–4 Docket Actions</h4>
                                    <p class="text-[11px] text-slate-500 font-medium">Save case file or store as Draft to resume Sections 1 to 4 operations later.</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3 w-full sm:w-auto justify-end">
                                <button 
                                    type="button" 
                                    onclick="handleDraftAndContinueLater()" 
                                    class="px-5 py-3 bg-white border border-slate-300 text-slate-700 font-bold text-xs rounded-xl hover:bg-slate-50 transition-colors cursor-pointer"
                                >
                                    Draft and Continue Later
                                </button>
                                <button 
                                    type="button" 
                                    onclick="processNewArrested()" 
                                    id="submitSection1To4DocketBtn" 
                                    class="px-7 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-600/30 transition-all flex items-center space-x-2 border-none cursor-pointer"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                                    </svg>
                                    <span>Save Case</span>
                                </button>
                            </div>
                        </div>

                        

                        <!-- DEDICATED STATIC FORM ACTION & SUBMISSION SECTION (OUTSIDE SECTION 4) -->
                        <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold shrink-0 shadow-2xs">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-black text-slate-900 uppercase font-mono-code">Finalize &amp; Save Docket File</h4>
                                    <p class="text-[11px] text-slate-500 font-medium">Save this case docket to enable inter-unit forensic workflow and real-time HOF tracking.</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3 w-full sm:w-auto justify-end">
                                <button type="button" onclick="handleDraftAndContinueLater()" class="px-5 py-3 bg-white border border-slate-300 text-slate-700 font-bold text-xs rounded-xl hover:bg-slate-50 transition-colors cursor-pointer">Draft and Continue Later</button>
                                <button type="button" onclick="processNewArrested()" id="submitArrestDocketBtn" class="px-7 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-600/30 transition-all flex items-center space-x-2 border-none cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                                    <span>Save Case</span>
                                </button>
                            </div>
                        </div>
                </form>


            </main>
        </div>
    </div>

    <script>
        window.forensicDrugKeywords = @json(config('forensic.drug_keywords', []));
        window.forensicNonDrugKeywords = @json(config('forensic.non_drug_keywords', []));

        // Populate only the readonly manifest-sync weight fields without resetting confirmatory test data.
        // Called when the user opens the Confirmatory YES section so they see exhibit context.
        function initSampleWeightFieldsOnly() {
            const selectEl = document.getElementById('labExhibitSelect');
            if (!selectEl) return;
            const selectedOption = selectEl.options[selectEl.selectedIndex];
            if (!selectedOption || !selectEl.value || selectEl.value === '' || selectedOption.text === 'No exhibit saved yet') {
                // Attempt a sync first in case the manifest was filled but sync not triggered yet
                if (typeof syncSection4ExhibitsToLabSection === 'function') {
                    syncSection4ExhibitsToLabSection();
                }
                return;
            }

            const name    = selectedOption.getAttribute('data-name') || '';
            const cat     = selectedOption.getAttribute('data-cat')  || '';
            const qty     = selectedOption.getAttribute('data-qty')  || '';
            const pkg     = selectedOption.getAttribute('data-pkg')  || '';
            const weight  = selectedOption.getAttribute('data-weight') || '';
            const unit    = selectedOption.getAttribute('data-unit')   || '';

            const drugName      = name || cat || '';
            const unitsText     = qty ? `${qty} ${pkg}`.trim() : (pkg || '');
            const grossWeightText = weight ? `${weight} ${unit}`.trim() : (unit || '');

            const isNonDrug = (val) => {
                if (!val) return false;
                const l = val.toLowerCase();
                return Array.isArray(window.forensicNonDrugKeywords) && window.forensicNonDrugKeywords.some(k => l.includes(k.toLowerCase()));
            };

            const sampleDescInput  = document.getElementById('sampleWeightDescription');
            const sampleUnitsInput = document.getElementById('sampleWeightUnits');
            const sampleGrossInput = document.getElementById('sampleWeightGross');
            const unitAddon        = document.getElementById('sampleWeightTakenUnitAddon');

            if (sampleDescInput && (!sampleDescInput.value || sampleDescInput.getAttribute('data-auto-synced') === 'true' || isNonDrug(sampleDescInput.value))) {
                if (drugName) { sampleDescInput.value = drugName; sampleDescInput.setAttribute('data-auto-synced', 'true'); }
            }
            if (sampleUnitsInput && (!sampleUnitsInput.value || sampleUnitsInput.getAttribute('data-auto-synced') === 'true' || isNonDrug(sampleUnitsInput.value))) {
                if (unitsText) { sampleUnitsInput.value = unitsText; sampleUnitsInput.setAttribute('data-auto-synced', 'true'); }
            }
            if (sampleGrossInput && (!sampleGrossInput.value || sampleGrossInput.getAttribute('data-auto-synced') === 'true' || isNonDrug(sampleGrossInput.value))) {
                if (grossWeightText) { sampleGrossInput.value = grossWeightText; sampleGrossInput.setAttribute('data-auto-synced', 'true'); }
            }
            if (unitAddon) {
                if (unit) {
                    unitAddon.textContent = unit;
                } else if (grossWeightText) {
                    const uMatch = grossWeightText.match(/(Kilograms?\s*\([^\)]+\)|Kilograms?|kg|Grams?|g|mg|lbs?)/i);
                    if (uMatch) unitAddon.textContent = uMatch[1];
                }
            }
            if (typeof updateSampleWeightReserved === 'function') updateSampleWeightReserved();
        }

        function toggleConfirmatoryTest(choice) {
            const yesBox = document.getElementById('confirmatoryTestYes');
            const noBox = document.getElementById('confirmatoryTestNo');
            const inputEl = document.getElementById('confirmatoryTestResultInput');
            const sampleWeightContainer = document.getElementById('sampleWeightContainer');
            if (choice === 'YES') {
                if (yesBox && yesBox.checked) {
                    if (noBox) noBox.checked = false;
                    if (inputEl) inputEl.value = 'YES';
                    if (sampleWeightContainer) sampleWeightContainer.classList.remove('hidden');
                    // Auto-fill weight fields from the selected manifest exhibit
                    initSampleWeightFieldsOnly();
                } else {
                    if (noBox) noBox.checked = true;
                    if (inputEl) inputEl.value = 'NO';
                    if (sampleWeightContainer) sampleWeightContainer.classList.add('hidden');
                }
            } else if (choice === 'NO') {
                if (noBox && noBox.checked) {
                    if (yesBox) yesBox.checked = false;
                    if (inputEl) inputEl.value = 'NO';
                    if (sampleWeightContainer) sampleWeightContainer.classList.add('hidden');
                } else {
                    if (yesBox) yesBox.checked = true;
                    if (inputEl) inputEl.value = 'YES';
                    if (sampleWeightContainer) sampleWeightContainer.classList.remove('hidden');
                    // Auto-fill weight fields from the selected manifest exhibit
                    initSampleWeightFieldsOnly();
                }
            }
        }

        function togglePresumptiveTest(choice) {
            const yesBox = document.getElementById('presumptiveTestYes');
            const noBox = document.getElementById('presumptiveTestNo');
            const container = document.getElementById('presumptiveReactionDetailsContainer');
            if (choice === 'YES') {
                if (yesBox && yesBox.checked) {
                    if (noBox) noBox.checked = false;
                    if (container) container.classList.remove('hidden');
                } else {
                    if (noBox) noBox.checked = true;
                    if (container) container.classList.add('hidden');
                }
            } else if (choice === 'NO') {
                if (noBox && noBox.checked) {
                    if (yesBox) yesBox.checked = false;
                    if (container) container.classList.add('hidden');
                } else {
                    if (yesBox) yesBox.checked = true;
                    if (container) container.classList.remove('hidden');
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const noBox = document.getElementById('presumptiveTestNo');
            const yesBox = document.getElementById('presumptiveTestYes');
            if (noBox && noBox.checked) {
                togglePresumptiveTest('NO');
            } else if (yesBox && yesBox.checked) {
                togglePresumptiveTest('YES');
            }
        });

        function addPresumptiveTestRow() {
            const container = document.getElementById('presumptiveTestRowsContainer');
            if (!container) return;

            const rows = container.querySelectorAll('.presumptive-test-row');
            const newIdx = rows.length + 1;

            const rowDiv = document.createElement('div');
            rowDiv.className = 'presumptive-test-row p-3.5 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-2.5';
            rowDiv.innerHTML = `
                <div class="flex items-center justify-between text-[11px] font-extrabold text-slate-700 uppercase font-mono-code">
                    <span class="test-row-title">Test Result #${newIdx}</span>
                    <button type="button" onclick="removePresumptiveTestRow(this)" class="remove-test-row-btn px-2 py-0.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg text-[10px] font-bold border border-transparent hover:border-red-200 transition-colors cursor-pointer">
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
                            class="presumptive-presence-input w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs" 
                            onchange="updatePresumptiveReactionText()" 
                            oninput="updatePresumptiveReactionText()" 
                            placeholder="e.g. Cocaine, Heroin, THC, Methamphetamine"
                        >
                    </div>
                    <div>
                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                            Percentage (%) <span class="text-red-600 font-bold">*</span>
                        </label>
                        <div class="flex items-center rounded-xl shadow-2xs overflow-hidden">
                            <input 
                                type="text" 
                                class="presumptive-percentage-input w-full pl-3.5 pr-2 py-2.5 bg-white border border-y border-l border-slate-300 rounded-l-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 font-mono-code" 
                                onchange="updatePresumptiveReactionText()" 
                                oninput="updatePresumptiveReactionText()" 
                                placeholder="e.g. 85.5"
                            >
                            <span class="inline-flex items-center px-3 py-2.5 bg-slate-100 border border-slate-300 rounded-r-xl text-slate-700 font-extrabold text-xs font-mono-code shrink-0">%</span>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(rowDiv);
            updatePresumptiveRemoveButtons();
            updatePresumptiveReactionText();
        }

        function removePresumptiveTestRow(btn) {
            const row = btn.closest('.presumptive-test-row');
            if (row) {
                row.remove();
                reindexPresumptiveTestRows();
                updatePresumptiveRemoveButtons();
                updatePresumptiveReactionText();
            }
        }

        function reindexPresumptiveTestRows() {
            const container = document.getElementById('presumptiveTestRowsContainer');
            if (!container) return;
            const rows = container.querySelectorAll('.presumptive-test-row');
            rows.forEach((row, idx) => {
                const titleSpan = row.querySelector('.test-row-title');
                if (titleSpan) titleSpan.innerText = `Test Result #${idx + 1}`;
            });
        }

        function updatePresumptiveRemoveButtons() {
            const container = document.getElementById('presumptiveTestRowsContainer');
            if (!container) return;
            const rows = container.querySelectorAll('.presumptive-test-row');
            rows.forEach(row => {
                const btn = row.querySelector('.remove-test-row-btn');
                if (btn) {
                    if (rows.length > 1) {
                        btn.classList.remove('hidden');
                    } else {
                        btn.classList.add('hidden');
                    }
                }
            });
        }

        function updatePresumptiveReactionText() {
            const container = document.getElementById('presumptiveTestRowsContainer');
            const inputEl = document.getElementById('presumptiveReactionResultInput');
            if (!container || !inputEl) return;

            const rows = container.querySelectorAll('.presumptive-test-row');
            let results = [];

            rows.forEach((row, idx) => {
                const presence = row.querySelector('.presumptive-presence-input')?.value?.trim() || '';
                let rawPct = row.querySelector('.presumptive-percentage-input')?.value?.trim() || '';
                let percentage = rawPct;
                if (percentage && !percentage.endsWith('%')) {
                    percentage = percentage + '%';
                }
                
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
                inputEl.value = '';
            }
        }

        let mugshotCount = 1;
        let suspectCount = 1;

        function handleCaseEntryTypeChange() {
            const select = document.getElementById('caseEntryTypeSelect');
            const badge = document.getElementById('caseTypeBadge');
            const addMugshotBtn = document.getElementById('addMugshotRowBtn');
            const addSuspectBtn = document.getElementById('addSuspectDetailsRowBtn');
            
            if (!select) return;
            
            if (select.value === 'MULTIPLE') {
                if (badge) {
                    badge.innerText = 'Multiple People Case (Joint Suspects)';
                    badge.className = 'px-2.5 py-1 bg-amber-50 text-amber-700 text-[10px] font-mono-code font-bold rounded-lg border border-amber-200 uppercase tracking-wider';
                }
                if (addMugshotBtn) addMugshotBtn.classList.remove('hidden');
                if (addSuspectBtn) addSuspectBtn.classList.remove('hidden');
            } else if (select.value === 'SINGLE') {
                if (badge) {
                    badge.innerText = 'Single Person Mode';
                    badge.className = 'px-2.5 py-1 bg-emerald-50 text-emerald-700 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider';
                }
                if (addMugshotBtn) addMugshotBtn.classList.add('hidden');
                if (addSuspectBtn) addSuspectBtn.classList.add('hidden');
            } else {
                if (badge) {
                    badge.innerText = 'Select Suspect Docket Mode';
                    badge.className = 'px-2.5 py-1 bg-slate-100 text-slate-600 text-[10px] font-mono-code font-bold rounded-lg border border-slate-200 uppercase tracking-wider';
                }
                if (addMugshotBtn) addMugshotBtn.classList.add('hidden');
                if (addSuspectBtn) addSuspectBtn.classList.add('hidden');
            }
        }

        function addMugshotRow() {
            mugshotCount++;
            const container = document.getElementById('mugshotsContainer');
            if (!container) return;

            const card = document.createElement('div');
            card.id = `mugshotCard-${mugshotCount}`;
            card.className = 'space-y-3 pt-5 border-t border-slate-200';
            card.innerHTML = `
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="text-xs font-bold text-slate-700 font-mono-code uppercase">Suspect #${mugshotCount} Mugshot Photos</div>
                        <span class="px-2.5 py-0.5 text-[9px] font-mono-code font-extrabold rounded-full bg-amber-100 text-amber-900 border border-amber-300 uppercase tracking-wider flex items-center shadow-2xs">
                            <span>CO-SUSPECT</span>
                        </span>
                    </div>
                    <button type="button" onclick="removeMugshotRow(${mugshotCount})" class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-black text-[11px] font-bold rounded-lg transition-colors border border-slate-300 cursor-pointer">
                        &times; Remove Row
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <!-- FRONT MUGSHOT -->
                    <div class="bg-white p-4 rounded-2xl border border-slate-200 text-center space-y-3 flex flex-col justify-between hover:border-emerald-500 transition-colors shadow-2xs">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded text-[9px] font-bold font-mono-code bg-emerald-600 text-white uppercase">Angle 1</span>
                            <span class="text-[10px] font-bold text-slate-700 font-mono-code">Front View (0&deg;)</span>
                            <span id="badge-front-${mugshotCount}" class="text-[9px] font-mono-code font-bold text-slate-400 uppercase">Not Uploaded</span>
                        </div>
                        <div 
                            id="dropzone-front-${mugshotCount}"
                            onclick="triggerMugshotUpload('front-${mugshotCount}')"
                            ondragover="handleArrestMugshotDragOver(event, this)"
                            ondragleave="handleArrestMugshotDragLeave(event, this)"
                            ondrop="handleArrestMugshotDrop(event, 'front-${mugshotCount}')"
                            class="h-48 w-full bg-slate-50 hover:bg-emerald-50/60 rounded-2xl border-2 border-dashed border-slate-300 hover:border-emerald-500 flex flex-col items-center justify-center overflow-hidden relative group transition-all duration-200 cursor-pointer shadow-2xs"
                        >
                            <img id="preview-front-${mugshotCount}" class="hidden w-full h-full object-contain p-1 transition-all duration-200" alt="Front Mugshot">
                            <div id="placeholder-front-${mugshotCount}" class="flex flex-col items-center justify-center p-3 text-slate-400 space-y-2 pointer-events-none">
                                <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-xs">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <span class="text-xs font-bold text-slate-700">Click or Drag &amp; Drop Photo</span>
                                <span class="text-[9px] font-mono-code text-slate-400">Full Face Direct View (0&deg;)</span>
                            </div>
                            <div id="actions-front-${mugshotCount}" class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs opacity-0 group-hover:opacity-100 flex items-center justify-center space-x-2 transition-opacity duration-200 hidden" onclick="event.stopPropagation()">
                                <button type="button" onclick="openArrestMugshotModal(document.getElementById('preview-front-${mugshotCount}').src, 'Front View (0&deg;) - Suspect #${mugshotCount}')" class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold rounded-xl transition-all shadow-md flex items-center space-x-1 cursor-pointer border-none">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <span>View Full</span>
                                </button>
                                <button type="button" onclick="clearArrestMugshotPhoto('front-${mugshotCount}')" class="px-2.5 py-1.5 bg-red-600 hover:bg-red-700 text-white text-[10px] font-bold rounded-xl transition-all shadow-md flex items-center space-x-1 cursor-pointer border-none">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    <span>Clear</span>
                                </button>
                            </div>
                        </div>
                        <div class="space-y-1.5 pt-1">
                            <input type="file" id="file-front-${mugshotCount}" accept="image/*" class="hidden" onchange="previewMugshot(event, 'front-${mugshotCount}')">
                            <button type="button" onclick="triggerMugshotUpload('front-${mugshotCount}')" class="w-full py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-[11px] font-extrabold rounded-xl border border-emerald-200 transition-colors flex items-center justify-center space-x-1 cursor-pointer">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                <span>Choose Front Photo</span>
                            </button>
                            <input 
                                type="text" 
                                id="url-front-${mugshotCount}" 
                                placeholder="Or paste image URL..." 
                                oninput="previewMugshotUrlArrest(this.value, 'front-${mugshotCount}')" 
                                class="w-full px-2.5 py-1.5 text-[10px] bg-slate-50 border border-slate-200 rounded-xl font-mono-code text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-emerald-500 focus:bg-white transition-all"
                            >
                            <input type="hidden" name="mugshot_front_${mugshotCount}" id="hidden-front-${mugshotCount}">
                        </div>
                    </div>

                    <!-- LEFT MUGSHOT -->
                    <div class="bg-white p-4 rounded-2xl border border-slate-200 text-center space-y-3 flex flex-col justify-between hover:border-emerald-500 transition-colors shadow-2xs">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded text-[9px] font-bold font-mono-code bg-slate-800 text-white uppercase">Angle 2</span>
                            <span class="text-[10px] font-bold text-slate-700 font-mono-code">Left Profile (90&deg;)</span>
                            <span id="badge-left-profile-${mugshotCount}" class="text-[9px] font-mono-code font-bold text-slate-400 uppercase">Not Uploaded</span>
                        </div>
                        <div 
                            id="dropzone-left-profile-${mugshotCount}"
                            onclick="triggerMugshotUpload('left-profile-${mugshotCount}')"
                            ondragover="handleArrestMugshotDragOver(event, this)"
                            ondragleave="handleArrestMugshotDragLeave(event, this)"
                            ondrop="handleArrestMugshotDrop(event, 'left-profile-${mugshotCount}')"
                            class="h-48 w-full bg-slate-50 hover:bg-emerald-50/60 rounded-2xl border-2 border-dashed border-slate-300 hover:border-emerald-500 flex flex-col items-center justify-center overflow-hidden relative group transition-all duration-200 cursor-pointer shadow-2xs"
                        >
                            <img id="preview-left-profile-${mugshotCount}" class="hidden w-full h-full object-contain p-1 transition-all duration-200" alt="Left Profile Mugshot">
                            <div id="placeholder-left-profile-${mugshotCount}" class="flex flex-col items-center justify-center p-3 text-slate-400 space-y-2 pointer-events-none">
                                <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-xs">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <span class="text-xs font-bold text-slate-700">Click or Drag &amp; Drop Photo</span>
                                <span class="text-[9px] font-mono-code text-slate-400">90&deg; Full Left Side Profile</span>
                            </div>
                            <div id="actions-left-profile-${mugshotCount}" class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs opacity-0 group-hover:opacity-100 flex items-center justify-center space-x-2 transition-opacity duration-200 hidden" onclick="event.stopPropagation()">
                                <button type="button" onclick="openArrestMugshotModal(document.getElementById('preview-left-profile-${mugshotCount}').src, 'Left Profile (90&deg;) - Suspect #${mugshotCount}')" class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold rounded-xl transition-all shadow-md flex items-center space-x-1 cursor-pointer border-none">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <span>View Full</span>
                                </button>
                                <button type="button" onclick="clearArrestMugshotPhoto('left-profile-${mugshotCount}')" class="px-2.5 py-1.5 bg-red-600 hover:bg-red-700 text-white text-[10px] font-bold rounded-xl transition-all shadow-md flex items-center space-x-1 cursor-pointer border-none">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    <span>Clear</span>
                                </button>
                            </div>
                        </div>
                        <div class="space-y-1.5 pt-1">
                            <input type="file" id="file-left-profile-${mugshotCount}" accept="image/*" class="hidden" onchange="previewMugshot(event, 'left-profile-${mugshotCount}')">
                            <button type="button" onclick="triggerMugshotUpload('left-profile-${mugshotCount}')" class="w-full py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-[11px] font-extrabold rounded-xl border border-emerald-200 transition-colors flex items-center justify-center space-x-1 cursor-pointer">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                <span>Choose Left Profile</span>
                            </button>
                            <input 
                                type="text" 
                                id="url-left-profile-${mugshotCount}" 
                                placeholder="Or paste image URL..." 
                                oninput="previewMugshotUrlArrest(this.value, 'left-profile-${mugshotCount}')" 
                                class="w-full px-2.5 py-1.5 text-[10px] bg-slate-50 border border-slate-200 rounded-xl font-mono-code text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-emerald-500 focus:bg-white transition-all"
                            >
                            <input type="hidden" name="mugshot_left_${mugshotCount}" id="hidden-left-profile-${mugshotCount}">
                        </div>
                    </div>

                    <!-- RIGHT MUGSHOT -->
                    <div class="bg-white p-4 rounded-2xl border border-slate-200 text-center space-y-3 flex flex-col justify-between hover:border-emerald-500 transition-colors shadow-2xs">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded text-[9px] font-bold font-mono-code bg-slate-800 text-white uppercase">Angle 3</span>
                            <span class="text-[10px] font-bold text-slate-700 font-mono-code">Right Profile (90&deg;)</span>
                            <span id="badge-right-profile-${mugshotCount}" class="text-[9px] font-mono-code font-bold text-slate-400 uppercase">Not Uploaded</span>
                        </div>
                        <div 
                            id="dropzone-right-profile-${mugshotCount}"
                            onclick="triggerMugshotUpload('right-profile-${mugshotCount}')"
                            ondragover="handleArrestMugshotDragOver(event, this)"
                            ondragleave="handleArrestMugshotDragLeave(event, this)"
                            ondrop="handleArrestMugshotDrop(event, 'right-profile-${mugshotCount}')"
                            class="h-48 w-full bg-slate-50 hover:bg-emerald-50/60 rounded-2xl border-2 border-dashed border-slate-300 hover:border-emerald-500 flex flex-col items-center justify-center overflow-hidden relative group transition-all duration-200 cursor-pointer shadow-2xs"
                        >
                            <img id="preview-right-profile-${mugshotCount}" class="hidden w-full h-full object-contain p-1 transition-all duration-200" alt="Right Profile Mugshot">
                            <div id="placeholder-right-profile-${mugshotCount}" class="flex flex-col items-center justify-center p-3 text-slate-400 space-y-2 pointer-events-none">
                                <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-xs">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <span class="text-xs font-bold text-slate-700">Click or Drag &amp; Drop Photo</span>
                                <span class="text-[9px] font-mono-code text-slate-400">90&deg; Full Right Side Profile</span>
                            </div>
                            <div id="actions-right-profile-${mugshotCount}" class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs opacity-0 group-hover:opacity-100 flex items-center justify-center space-x-2 transition-opacity duration-200 hidden" onclick="event.stopPropagation()">
                                <button type="button" onclick="openArrestMugshotModal(document.getElementById('preview-right-profile-${mugshotCount}').src, 'Right Profile (90&deg;) - Suspect #${mugshotCount}')" class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold rounded-xl transition-all shadow-md flex items-center space-x-1 cursor-pointer border-none">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <span>View Full</span>
                                </button>
                                <button type="button" onclick="clearArrestMugshotPhoto('right-profile-${mugshotCount}')" class="px-2.5 py-1.5 bg-red-600 hover:bg-red-700 text-white text-[10px] font-bold rounded-xl transition-all shadow-md flex items-center space-x-1 cursor-pointer border-none">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    <span>Clear</span>
                                </button>
                            </div>
                        </div>
                        <div class="space-y-1.5 pt-1">
                            <input type="file" id="file-right-profile-${mugshotCount}" accept="image/*" class="hidden" onchange="previewMugshot(event, 'right-profile-${mugshotCount}')">
                            <button type="button" onclick="triggerMugshotUpload('right-profile-${mugshotCount}')" class="w-full py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-[11px] font-extrabold rounded-xl border border-emerald-200 transition-colors flex items-center justify-center space-x-1 cursor-pointer">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                <span>Choose Right Profile</span>
                            </button>
                            <input 
                                type="text" 
                                id="url-right-profile-${mugshotCount}" 
                                placeholder="Or paste image URL..." 
                                oninput="previewMugshotUrlArrest(this.value, 'right-profile-${mugshotCount}')" 
                                class="w-full px-2.5 py-1.5 text-[10px] bg-slate-50 border border-slate-200 rounded-xl font-mono-code text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-emerald-500 focus:bg-white transition-all"
                            >
                            <input type="hidden" name="mugshot_right_${mugshotCount}" id="hidden-right-profile-${mugshotCount}">
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(card);
        }

        function removeMugshotRow(id) {
            const card = document.getElementById(`mugshotCard-${id}`);
            if (card) card.remove();
        }

        function calculateAgeFromDOBDynamic(dobInput) {
            const card = dobInput.closest('.suspect-card-dynamic');
            if (!card) return;
            const ageInput = card.querySelector('[data-field="age"]');
            if (!ageInput) return;

            const dobVal = dobInput.value;
            if (!dobVal) {
                ageInput.value = '';
                return;
            }
            const dob = new Date(dobVal);
            if (isNaN(dob.getTime())) {
                ageInput.value = '';
                return;
            }
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const monthDiff = today.getMonth() - dob.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                age--;
            }
            ageInput.value = age >= 0 ? age : '';
        }

        function handleDynamicMaritalChange(selectEl) {
            const card = selectEl.closest('.suspect-card-dynamic');
            if (!card) return;
            const box = card.querySelector('.relationship-details-box');
            const title = card.querySelector('.relationship-title');
            const subtitle = card.querySelector('.relationship-subtitle');
            const spouseCard = card.querySelector('.spouse-fields-card');
            const partnerCard = card.querySelector('.partner-fields-card');

            if (!box) return;
            const val = selectEl.value;

            if (val === 'Married') {
                box.classList.remove('hidden');
                if (title) title.innerText = 'Spouse Information (Marriage Record)';
                if (subtitle) subtitle.innerText = 'Mandatory record for suspect legal spouse identity, phone contact, and occupation';
                if (spouseCard) spouseCard.classList.remove('hidden');
                if (partnerCard) partnerCard.classList.add('hidden');
            } else if (val === 'Dating / In Relationship') {
                box.classList.remove('hidden');
                if (title) title.innerText = 'Dating Partner Information (Relationship Record)';
                if (subtitle) subtitle.innerText = 'Record for suspect dating partner full name, phone contact, and residence';
                if (spouseCard) spouseCard.classList.add('hidden');
                if (partnerCard) partnerCard.classList.remove('hidden');
            } else {
                box.classList.add('hidden');
            }
        }

        function toggleDynamicChildrenInput(checkboxEl) {
            const card = checkboxEl.closest('.suspect-card-dynamic');
            if (!card) return;
            const container = card.querySelector('.children-input-container');
            const input = card.querySelector('[data-field="children_count"]');

            if (checkboxEl.checked) {
                if (container) container.classList.remove('hidden');
                if (input && (!input.value || input.value === '0')) {
                    input.value = '1';
                }
            } else {
                if (container) container.classList.add('hidden');
                if (input) input.value = '0';
            }
        }

        function addSuspectDetailsRow() {
            suspectCount++;
            const container = document.getElementById('suspectsContainer');
            if (!container) return;

            const card = document.createElement('div');
            card.id = `suspectCard-${suspectCount}`;
            card.className = 'space-y-6 pt-6 border-t-2 border-slate-200/80 suspect-card-dynamic';
            card.innerHTML = `
                <div class="flex items-center justify-between bg-slate-100 p-3.5 rounded-xl border border-slate-200">
                    <span class="font-extrabold text-slate-900 text-sm font-mono-code uppercase flex items-center space-x-2">
                        <span class="suspect-number-badge w-6 h-6 rounded-full bg-emerald-600 text-white text-xs flex items-center justify-center font-bold">${suspectCount}</span>
                        <span class="suspect-header-title">Suspect #${suspectCount} Personal Booking Record</span>
                        <span class="px-2.5 py-0.5 text-[9px] font-mono-code font-extrabold rounded-full bg-amber-100 text-amber-900 border border-amber-300 uppercase tracking-wider flex items-center shadow-2xs">CO-SUSPECT</span>
                    </span>
                    <button type="button" onclick="removeSuspectRow(this)" class="suspect-remove-btn px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-xs transition-colors border-none cursor-pointer">
                        &times; Remove Suspect #${suspectCount}
                    </button>
                </div>

                <!-- SUB-HEADER 3A: LEGAL IDENTITY & ORIGIN -->
                <div class="space-y-4">
                    <div class="flex items-center space-x-2 text-xs font-extrabold text-black uppercase tracking-wider border-b border-slate-100 pb-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3 3 0 00-3 3h6a3 3 0 00-3-3z"/></svg>
                        <span class="suspect-subheader-3a">3A. Legal Identity &amp; Origin Details (Suspect #${suspectCount})</span>
                    </div>

                    <!-- ROW 1: REF (READONLY), FULL NAME, ALIAS -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                <span>Raid Incident Reference</span>
                                <span class="text-[9px] font-mono-code text-slate-600 bg-slate-200 px-2 py-0.5 rounded font-bold">READONLY</span>
                            </label>
                            <div class="relative flex items-center">
                                <span class="absolute left-3.5 text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </span>
                                <input type="text" value="RAID-2026-0419" readonly class="w-full pl-10 pr-3.5 py-3 bg-slate-100 border border-slate-300 rounded-xl font-mono-code font-bold text-slate-800 cursor-not-allowed select-none shadow-2xs">
                            </div>
                        </div>

                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                Suspect Full Legal Name <span class="text-red-600 font-bold">*</span>
                            </label>
                            <div class="relative flex items-center group">
                                <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                </span>
                                <input type="text" data-field="suspect_name" placeholder="e.g. Ama Serwaa" required class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                            </div>
                        </div>

                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                Alias / Street Name
                            </label>
                            <div class="relative flex items-center group">
                                <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                </span>
                                <input type="text" data-field="suspect_alias" placeholder="e.g. Queen Bee / Cobra" class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                            </div>
                        </div>
                    </div>

                    <!-- ROW 2: GHANA CARD, GENDER, DOB, AGE, HEIGHT, EYE COLOR -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5 relative z-40">
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                Ghana Card #
                            </label>
                            <div class="relative flex items-center group">
                                <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3 3 0 00-3 3h6a3 3 0 00-3-3z"/></svg>
                                </span>
                                <input type="text" data-field="ghana_card" placeholder="GHA-8840291-3" class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl font-mono-code font-bold text-slate-900 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                            </div>
                        </div>

                        <!-- DYNAMIC CUSTOM GENDER DROPDOWN -->
                        <div class="relative z-30">
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                Gender <span class="text-red-600 font-bold">*</span>
                            </label>
                            <button type="button" onclick="toggleDynamicDropdown(this, event)" class="dyn-dropdown-trigger w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 flex items-center justify-between hover:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 focus:border-emerald-600 transition-all shadow-2xs group cursor-pointer">
                                <div class="flex items-center space-x-2">
                                    <span class="dyn-gender-icon w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-bold">?</span>
                                    <span class="dyn-gender-label font-bold text-slate-500 text-xs">Select Gender</span>
                                </div>
                                <input type="hidden" data-field="gender" value="">
                                <svg class="dyn-chevron w-4 h-4 text-slate-400 group-hover:text-black transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div class="dyn-dropdown-menu hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 p-2 space-y-1">
                                <button type="button" onclick="selectDynamicGender(this, '', '?', 'bg-slate-100 text-slate-600', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-500 hover:bg-slate-50 hover:text-black rounded-xl flex items-center space-x-2 transition-colors border-b border-slate-100 mb-1">
                                    <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-bold">?</span>
                                    <span>Select Gender</span>
                                </button>
                                <button type="button" onclick="selectDynamicGender(this, 'Male', '♂', 'bg-emerald-100 text-emerald-700', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center space-x-2 transition-colors">
                                    <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-bold">♂</span>
                                    <span>Male</span>
                                </button>
                                <button type="button" onclick="selectDynamicGender(this, 'Female', '♀', 'bg-rose-100 text-rose-700', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center space-x-2 transition-colors">
                                    <span class="w-5 h-5 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center text-[10px] font-bold">♀</span>
                                    <span>Female</span>
                                </button>
                                <button type="button" onclick="selectDynamicGender(this, 'Other / Non-Binary', '⚧', 'bg-purple-100 text-purple-700', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center space-x-2 transition-colors">
                                    <span class="w-5 h-5 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center text-[10px] font-bold">⚧</span>
                                    <span>Other / Non-Binary</span>
                                </button>
                                <button type="button" onclick="selectDynamicGender(this, 'N/A', '?', 'bg-slate-100 text-slate-600', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center space-x-2 transition-colors">
                                    <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-bold">?</span>
                                    <span>N/A</span>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                Date of Birth <span class="text-red-600 font-bold">*</span>
                            </label>
                            <div class="relative flex items-center group">
                                <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </span>
                                <input type="date" data-field="dob" onchange="calculateAgeFromDOBDynamic(this)" oninput="calculateAgeFromDOBDynamic(this)" required class="w-full pl-10 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-mono-code font-bold text-slate-900 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs">
                            </div>
                        </div>

                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                <span>Age (Years) <span class="text-red-600 font-bold">*</span></span>
                                <span class="text-[9px] font-mono-code text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded font-bold uppercase">Read-Only</span>
                            </label>
                            <div class="relative flex items-center group">
                                <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black font-mono-code font-bold text-xs transition-colors">#</span>
                                <input type="number" data-field="age" placeholder="Auto-calculated age..." min="0" max="120" readonly required class="w-full pl-10 pr-3.5 py-3 bg-slate-100 border border-slate-300 rounded-xl font-bold text-slate-800 cursor-not-allowed select-none focus:outline-none shadow-2xs placeholder:text-slate-400 placeholder:font-normal">
                            </div>
                        </div>

                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                Height (cm / ft-in)
                            </label>
                            <div class="relative flex items-center group">
                                <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors z-10">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                </span>
                                <input type="text" data-field="height" placeholder="e.g. 175" class="w-full pl-10 pr-24 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                                <div class="absolute right-1.5 flex items-center">
                                    <select data-field="height_unit" class="py-1.5 px-2 bg-slate-100 hover:bg-slate-200 text-slate-800 font-mono-code font-black text-xs border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 cursor-pointer transition-colors uppercase">
                                        <option value="CM" selected>CM</option>
                                        <option value="IN">IN</option>
                                        <option value="FT">FT</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                Eye Color
                            </label>
                            <div class="relative flex items-center group">
                                <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </span>
                                <input type="text" data-field="eye_color" placeholder="e.g. Brown / Black / Blue" class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                            </div>
                        </div>
                    </div>

                    <!-- ROW 3: NATIONALITY, PLACE OF BIRTH, RESIDENTIAL ADDRESS -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 relative z-30">
                        <!-- DYNAMIC CUSTOM NATIONALITY DROPDOWN -->
                        <div class="relative z-30">
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                <span>Nationality <span class="text-red-600 font-bold">*</span></span>
                                <span class="text-[9px] font-mono-code text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded font-bold uppercase">Search / Custom</span>
                            </label>
                            <button type="button" onclick="toggleDynamicDropdown(this, event)" class="dyn-dropdown-trigger w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 flex items-center justify-between hover:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 focus:border-emerald-600 transition-all shadow-2xs group cursor-pointer">
                                <div class="flex items-center space-x-2">
                                    <span class="dyn-nat-icon w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-bold">NAT</span>
                                    <span class="dyn-nat-label font-bold text-slate-500 text-xs">Select Nationality</span>
                                </div>
                                <input type="hidden" data-field="nationality" value="">
                                <svg class="dyn-chevron w-4 h-4 text-slate-400 group-hover:text-black transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div class="dyn-dropdown-menu hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 p-2 space-y-1 max-h-64 overflow-y-auto custom-scrollbar">
                                <div class="p-1 pb-2 sticky top-0 bg-white z-10 border-b border-slate-100">
                                    <input type="text" onkeyup="filterDynamicNatOptions(this)" onkeydown="handleNatSearchKeydown(event, true, this)" placeholder="Search nationality..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:bg-white focus:border-emerald-600 outline-none placeholder:text-slate-400">
                                </div>
                                <button type="button" onclick="selectDynamicNationality(this, '', 'NAT', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-500 hover:bg-slate-50 hover:text-black rounded-xl flex items-center space-x-2 transition-colors border-b border-slate-100 mb-1 nat-dyn-option">
                                    <span>Select Nationality</span>
                                </button>
                                @foreach($allNationalitiesList as $nat)
                                <button type="button" onclick="selectDynamicNationality(this, '{{ $nat['name'] }}', '{{ $nat['code'] }}', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors nat-dyn-option">
                                    <div class="flex items-center space-x-2">
                                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-bold">{{ $nat['code'] }}</span>
                                        <span>{{ $nat['name'] }}</span>
                                    </div>
                                    <span class="text-[10px] font-mono-code text-slate-400 font-bold">{{ $nat['code'] }}</span>
                                </button>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                Place of Birth <span class="text-red-600 font-bold">*</span>
                            </label>
                            <div class="relative flex items-center group">
                                <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </span>
                                <input type="text" data-field="pob" placeholder="e.g. Kumasi, Ashanti Region" required class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                            </div>
                        </div>

                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                Residential Address / GPS <span class="text-red-600 font-bold">*</span>
                            </label>
                            <div class="relative flex items-center group">
                                <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                </span>
                                <input type="text" data-field="address" placeholder="e.g. H/No 14 East Legon, Accra (GA-142-8809)" required class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                            </div>
                        </div>
                    </div>

                    <!-- ROW 4: CONTACT PHONE NUMBERS CONTAINER (DEDICATED FULL WIDTH CARD FOR CO-SUSPECT) -->
                    <div class="relative z-10 pt-1">
                        <div class="suspect-phones-card bg-slate-50/80 p-3.5 rounded-2xl border border-slate-200/90 shadow-2xs space-y-3 transition-all hover:border-slate-300" data-suspect-index="${suspectCount}">
                            <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                                <div class="flex items-center space-x-2">
                                    <div class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs shadow-2xs">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                    </div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-0">
                                        Contact Phone Number(s) <span class="text-red-600 font-bold">*</span>
                                    </label>
                                </div>
                                <span class="text-[9px] font-mono-code bg-white text-slate-500 font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-md border border-slate-200/80 shadow-2xs">Multi-Phone</span>
                            </div>

                            <div class="suspect-phones-list space-y-2.5">
                                <div class="suspect-phone-row flex items-center gap-2">
                                    <div class="relative shrink-0">
                                        <select data-phone-field="label" class="w-28 sm:w-32 px-2.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-800 outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer shadow-2xs">
                                            <option value="Primary" selected>Primary</option>
                                            <option value="Alternate">Alternate</option>
                                            <option value="WhatsApp">WhatsApp</option>
                                            <option value="Emergency">Emergency</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="relative flex-1 flex items-center group">
                                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        </span>
                                        <input type="text"
                                               data-field="phone"
                                               data-phone-field="number"
                                               placeholder="e.g. +233 24 555 0192"
                                               required
                                               class="w-full pl-10 pr-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400 text-xs">
                                    </div>
                                    <div class="w-9 h-9 shrink-0 flex items-center justify-center"></div>
                                </div>
                            </div>

                            <div class="pt-0.5">
                                <button type="button"
                                        onclick="addSuspectPhoneRow(this)"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-extrabold rounded-xl transition-all cursor-pointer shadow-2xs hover:shadow-xs active:scale-[0.98]">
                                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    <span>Add Phone Number</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUB-HEADER 3B: FAMILY & MARITAL RECORDS -->
                <div class="space-y-4 pt-2 relative z-20">
                    <div class="flex items-center space-x-2 text-xs font-extrabold text-black uppercase tracking-wider border-b border-slate-100 pb-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        <span>3B. Marital Record, Spouse / Partner Details &amp; Dependents (Suspect #${suspectCount})</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 relative z-30">
                        <!-- DYNAMIC CUSTOM MARITAL STATUS DROPDOWN -->
                        <div class="relative z-30">
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                Marital / Relationship Status <span class="text-red-600 font-bold">*</span>
                            </label>
                            <button type="button" onclick="toggleDynamicDropdown(this, event)" class="dyn-dropdown-trigger w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 flex items-center justify-between hover:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 focus:border-emerald-600 transition-all shadow-2xs group cursor-pointer">
                                <div class="flex items-center space-x-2">
                                    <span class="dyn-marital-icon w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-bold">MS</span>
                                    <span class="dyn-marital-label font-bold text-slate-500 text-xs">Select Marital / Relationship Status</span>
                                </div>
                                <input type="hidden" data-field="marital_status" value="">
                                <svg class="dyn-chevron w-4 h-4 text-slate-400 group-hover:text-black transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div class="dyn-dropdown-menu hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 p-2 space-y-1">
                                <button type="button" onclick="selectDynamicMarital(this, '', 'MS', 'bg-slate-100 text-slate-600', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-500 hover:bg-slate-50 hover:text-black rounded-xl flex items-center space-x-2 transition-colors border-b border-slate-100 mb-1">
                                    <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-bold">MS</span>
                                    <span>Select Marital / Relationship Status</span>
                                </button>
                                <button type="button" onclick="selectDynamicMarital(this, 'Single', 'SGL', 'bg-emerald-100 text-emerald-700', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                    <div class="flex items-center space-x-2">
                                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-bold">SGL</span>
                                        <span>Single</span>
                                    </div>
                                    <span class="text-[10px] font-mono-code text-slate-400 font-bold">SGL</span>
                                </button>
                                <button type="button" onclick="selectDynamicMarital(this, 'Married', 'MRD', 'bg-blue-100 text-blue-700', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                    <div class="flex items-center space-x-2">
                                        <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-[10px] font-bold">MRD</span>
                                        <span>Married (Requires Spouse Info)</span>
                                    </div>
                                    <span class="text-[10px] font-mono-code text-slate-400 font-bold">MRD</span>
                                </button>
                                <button type="button" onclick="selectDynamicMarital(this, 'Dating / In Relationship', 'DAT', 'bg-purple-100 text-purple-700', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                    <div class="flex items-center space-x-2">
                                        <span class="w-5 h-5 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center text-[10px] font-bold">DAT</span>
                                        <span>Dating / In Relationship (Requires Partner Info)</span>
                                    </div>
                                    <span class="text-[10px] font-mono-code text-slate-400 font-bold">DAT</span>
                                </button>
                                <button type="button" onclick="selectDynamicMarital(this, 'Divorced', 'DIV', 'bg-amber-100 text-amber-700', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                    <div class="flex items-center space-x-2">
                                        <span class="w-5 h-5 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-[10px] font-bold">DIV</span>
                                        <span>Divorced</span>
                                    </div>
                                    <span class="text-[10px] font-mono-code text-slate-400 font-bold">DIV</span>
                                </button>
                                <button type="button" onclick="selectDynamicMarital(this, 'Widowed', 'WID', 'bg-rose-100 text-rose-700', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                    <div class="flex items-center space-x-2">
                                        <span class="w-5 h-5 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center text-[10px] font-bold">WID</span>
                                        <span>Widowed</span>
                                    </div>
                                    <span class="text-[10px] font-mono-code text-slate-400 font-bold">WID</span>
                                </button>
                            </div>
                        </div>

                        <div class="space-y-2.5">
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase">
                                Children / Dependents Status <span class="text-red-600 font-bold">*</span>
                            </label>
                            <label class="flex items-center space-x-3 p-3 bg-white border border-slate-300 rounded-xl cursor-pointer hover:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-600/20 focus-within:border-emerald-600 transition-all shadow-2xs select-none">
                                <input type="checkbox" data-field="has_children" onchange="toggleDynamicChildrenInput(this)" class="w-5 h-5 text-black rounded-md border-slate-300 focus:ring-emerald-600 focus:ring-2 cursor-pointer accent-emerald-600">
                                <span class="text-xs font-bold text-slate-900">Suspect Has Children / Dependents</span>
                            </label>
                            <div class="children-input-container hidden relative flex items-center group pt-1">
                                <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                </span>
                                <input type="number" data-field="children_count" min="1" max="20" placeholder="Enter number of children..." value="0" class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                            </div>
                        </div>
                    </div>

                    <!-- DYNAMIC CONDITIONAL SPOUSE / DATING PARTNER CARD -->
                    <div class="relationship-details-box hidden p-5 bg-emerald-50/50 rounded-2xl border border-slate-300 space-y-4">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-7 h-7 rounded-lg bg-white text-emerald-600 border border-slate-200/90 flex items-center justify-center text-xs font-bold shadow-sm shadow-slate-200/50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                            </div>
                            <div>
                                <h4 class="relationship-title font-extrabold text-slate-900 text-xs uppercase tracking-wider">Spouse Information (Marriage Record)</h4>
                                <p class="relationship-subtitle text-[11px] text-black">Mandatory record for suspect legal spouse identity, phone contact, and occupation</p>
                            </div>
                        </div>

                        <!-- SPOUSE FIELDS CARD -->
                        <div class="spouse-fields-card grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Spouse Full Legal Name</label>
                                <div class="relative flex items-center group">
                                    <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </span>
                                    <input type="text" data-field="spouse_name" placeholder="e.g. Abena Mensah" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                </div>
                            </div>
                            <div>
                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Spouse Contact Phone</label>
                                <div class="relative flex items-center group">
                                    <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    </span>
                                    <input type="text" data-field="spouse_phone" placeholder="e.g. +233 20 884 0129" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                </div>
                            </div>
                            <div>
                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Spouse Occupation / Address</label>
                                <div class="relative flex items-center group">
                                    <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A2.36 2.36 0 0019.358 11h-1.55A1.5 1.5 0 0116.3 9.5V7a2 2 0 00-2-2H9.7a2 2 0 00-2 2v2.5A1.5 1.5 0 016.2 11H4.642A2.36 2.36 0 003 13.255V18a2 2 0 002 2h14a2 2 0 002-2v-4.745z"/></svg>
                                    </span>
                                    <input type="text" data-field="spouse_info" placeholder="e.g. Trader, Tema Sector 1" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                </div>
                            </div>
                        </div>

                        <!-- DATING PARTNER FIELDS CARD -->
                        <div class="partner-fields-card hidden grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Partner Full Legal Name</label>
                                <div class="relative flex items-center group">
                                    <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </span>
                                    <input type="text" data-field="partner_name" placeholder="e.g. Ama Serwaa" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                </div>
                            </div>
                            <div>
                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Partner Contact Phone</label>
                                <div class="relative flex items-center group">
                                    <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    </span>
                                    <input type="text" data-field="partner_phone" placeholder="e.g. +233 24 100 2938" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                </div>
                            </div>
                            <div>
                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Partner Residential Info</label>
                                <div class="relative flex items-center group">
                                    <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                    </span>
                                    <input type="text" data-field="partner_info" placeholder="e.g. Resident, East Legon" class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUB-HEADER 3C: OFFENCE CHARGES & CUSTODY HOLDING FACILITY -->
                <div class="space-y-4 pt-2">
                    <div class="flex items-center space-x-2 text-xs font-extrabold text-black uppercase tracking-wider border-b border-slate-100 pb-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m3 0h1m-4 0v-3m1-3h.01M10 9h.01M10 12h.01M14 9h.01M14 12h.01M14 15h.01M7 9h.01M7 12h.01M7 15h.01"/></svg>
                        <span class="suspect-subheader-3c">3C. Offence Charges &amp; Custody Holding Facility (Suspect #${suspectCount})</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                PLACE OF ARREST <span class="text-red-600 font-bold">*</span>
                            </label>
                            <div class="relative flex items-center group">
                                <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </span>
                                <input type="text" data-field="raid_location" placeholder="e.g. East Legon Sector 4, Accra" required class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                            </div>
                        </div>

                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                Brief Facts/ Charge Description <span class="text-red-600 font-bold">*</span>
                            </label>
                            <div class="relative flex group">
                                <span class="absolute left-3.5 top-3.5 text-slate-400 group-focus-within:text-black transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                                </span>
                                <textarea data-field="offense" rows="3" placeholder="e.g. High-Purity Narcotics Trafficking &amp; Illegal Firearm Possession, Breach of Peace..." required class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400 resize-y min-h-[96px]"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-5">
                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                Date of Arrest <span class="text-red-600 font-bold">*</span>
                            </label>
                            <div class="relative flex items-center group">
                                <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </span>
                                <input type="date" data-field="date_of_arrest" required class="w-full pl-10 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-mono-code font-bold text-slate-900 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs">
                            </div>
                        </div>

                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                Time of Arrest <span class="text-red-600 font-bold">*</span>
                            </label>
                            <div class="relative flex items-center group">
                                <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </span>
                                <input type="time" data-field="time_of_arrest" required class="w-full pl-10 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-mono-code font-bold text-slate-900 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs">
                            </div>
                        </div>

                        <div>
                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                Assigned Custody Holding Facility <span class="text-red-600 font-bold">*</span>
                            </label>
                            <div class="relative flex items-center group">
                                <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m3 0h1m-4 0v-3m1-3h.01M10 9h.01M10 12h.01M14 9h.01M14 12h.01M14 15h.01M7 9h.01M7 12h.01M7 15h.01"/></svg>
                                </span>
                                <input type="text" data-field="holding_facility" placeholder="e.g. NACOC High-Security Holding Cell Sector B" required class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400">
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(card);
            reindexSuspectCards();
        }

        function reindexSuspectCards() {
            const container = document.getElementById('suspectsContainer');
            if (!container) return;
            const cards = container.querySelectorAll('.suspect-card-dynamic');
            cards.forEach((card, index) => {
                const num = index + 2;
                card.id = `suspectCard-${num}`;
                const badge = card.querySelector('.suspect-number-badge');
                if (badge) badge.textContent = num;

                const title = card.querySelector('.suspect-header-title');
                if (title) title.textContent = `Suspect #${num} Personal Booking Record`;

                const removeBtn = card.querySelector('.suspect-remove-btn');
                if (removeBtn) {
                    removeBtn.innerHTML = `&times; Remove Suspect #${num}`;
                    removeBtn.setAttribute('onclick', `removeSuspectRow(this)`);
                }

                const sub3a = card.querySelector('.suspect-subheader-3a');
                if (sub3a) sub3a.textContent = `3A. Legal Identity & Origin Details (Suspect #${num})`;

                const sub3c = card.querySelector('.suspect-subheader-3c');
                if (sub3c) sub3c.textContent = `3C. Offence Charges & Custody Holding Facility (Suspect #${num})`;
            });
            suspectCount = cards.length + 1;
            if (typeof syncSuspectDropdownsForExhibits === 'function') {
                syncSuspectDropdownsForExhibits();
            }
        }

        function removeSuspectRow(target) {
            if (typeof target === 'number' || typeof target === 'string') {
                const card = document.getElementById(`suspectCard-${target}`);
                if (card) card.remove();
            } else if (target && target.closest) {
                const card = target.closest('.suspect-card-dynamic');
                if (card) card.remove();
            }
            reindexSuspectCards();
            if (typeof syncSuspectDropdownsForExhibits === 'function') {
                syncSuspectDropdownsForExhibits();
            }
            if (typeof saveDocketFormDraft === 'function') {
                saveDocketFormDraft();
            }
        }

        /**
         * Collect all suspects from the suspectsContainer.
         * Suspect #1 is read from its known fixed element IDs.
         * Additional suspects (dynamically added) are read via data-field attributes.
         * Returns an array of suspect objects.
         */
        function collectAllSuspects() {
            const suspects = [];

            // --- Suspect #1: read from fixed IDs ---
            const s1Name     = document.getElementById('newArrestedName')?.value?.trim()     || '';
            const s1Alias    = document.getElementById('newArrestedAlias')?.value?.trim()    || '';
            const s1Card     = document.getElementById('newArrestedID')?.value?.trim()       || '';
            const s1Gender   = document.getElementById('newArrestedGender')?.value?.trim()   || '';
            const s1DOB      = document.getElementById('newArrestedDOB')?.value?.trim()      || '';
            const s1Age      = document.getElementById('newArrestedAge')?.value?.trim()      || '';
            const s1Height   = document.getElementById('newArrestedHeight')?.value?.trim()   || '';
            const s1Eye      = document.getElementById('newArrestedEyeColor')?.value?.trim() || '';
            const s1Nat      = document.getElementById('nationalitySelect')?.value?.trim()   || document.getElementById('newArrestedNationality')?.value?.trim() || '';
            const s1POB      = document.getElementById('newArrestedPOB')?.value?.trim()      || '';
            const s1Phone    = document.getElementById('newArrestedPhone')?.value?.trim()    || '';
            const s1Address  = document.getElementById('newArrestedAddress')?.value?.trim()  || '';
            const s1Marital  = document.getElementById('newArrestedMarital')?.value?.trim()  || '';
            const s1HasKids  = document.getElementById('hasChildrenCheckbox')?.checked       || false;
            const s1Kids     = s1HasKids ? (document.getElementById('newArrestedChildren')?.value || '1') : '0';
            const s1SpouseN  = document.getElementById('newArrestedSpouseName')?.value?.trim() || '';
            const s1SpouseP  = document.getElementById('newArrestedSpousePhone')?.value?.trim() || '';
            const s1SpouseI  = document.getElementById('newArrestedSpouseInfo')?.value?.trim() || '';
            const s1PartnerN = document.getElementById('newArrestedPartnerName')?.value?.trim() || '';
            const s1PartnerP = document.getElementById('newArrestedPartnerPhone')?.value?.trim() || '';
            const s1PartnerI = document.getElementById('newArrestedPartnerInfo')?.value?.trim() || '';
            const s1Offense  = document.getElementById('newArrestedOffense')?.value?.trim()  || '';
            const s1Raid     = document.getElementById('newArrestedRaidLocation')?.value?.trim() || '';
            const s1Facility = document.getElementById('newArrestedFacility')?.value?.trim() || '';
            const s1Date     = document.getElementById('newArrestedDate')?.value?.trim() || '';
            const s1Time     = document.getElementById('newArrestedTime')?.value?.trim() || '';

            let s1SpouseSummary = 'N/A';
            if (s1Marital === 'Married') {
                s1SpouseSummary = `Spouse: ${s1SpouseN} (${s1SpouseP}) - ${s1SpouseI}`;
            } else if (s1Marital === 'Dating / In Relationship') {
                s1SpouseSummary = `Partner: ${s1PartnerN} (${s1PartnerP}) - ${s1PartnerI}`;
            }

            // Mugshots for Suspect #1
            const cleanMugshotSrc = (src) => {
                if (!src || typeof src !== 'string') return null;
                const s = src.trim();
                if (!s || s === window.location.href || s.endsWith('#') || s.includes('blank') || s === 'null' || s === 'undefined') return null;
                if (s.startsWith('data:image/') || s.startsWith('http://') || s.startsWith('https://') || s.startsWith('/storage') || s.startsWith('storage/') || s.startsWith('suspects/') || s.startsWith('blob:')) {
                    return s;
                }
                if (/\.(jpg|jpeg|png|webp|gif|bmp)(\?.*)?$/i.test(s)) {
                    return s;
                }
                return null;
            };

            const s1MugFrontRaw = document.getElementById('preview-front')?.src || document.getElementById('hidden-front')?.value || null;
            const s1MugLeftRaw  = document.getElementById('preview-left-profile')?.src || document.getElementById('hidden-left-profile')?.value || null;
            const s1MugRightRaw = document.getElementById('preview-right-profile')?.src || document.getElementById('hidden-right-profile')?.value || null;

            const s1MugFront = cleanMugshotSrc(s1MugFrontRaw);
            const s1MugLeft  = cleanMugshotSrc(s1MugLeftRaw);
            const s1MugRight = cleanMugshotSrc(s1MugRightRaw);

            if (s1Name) {
                suspects.push({
                    suspect_number:   1,
                    suspect_id:       'PRIM-001',
                    is_associate:     false,
                    is_co_suspect:    false,
                    association_role: 'Primary Suspect',
                    role:             'Primary Suspect',
                    suspect_name:     s1Name,
                    suspect_alias:    s1Alias,
                    ghana_card:       s1Card,
                    gender:           s1Gender,
                    dob:              s1DOB,
                    age:              s1Age,
                    height:           s1Height,
                    eye_color:        s1Eye,
                    nationality:      s1Nat,
                    pob:              s1POB,
                    place_of_birth:   s1POB,
                    hometown:         s1POB,
                    phone:            s1Phone,
                    phone_label:      document.querySelector('#suspectCard-1 select[name="phone_labels[]"], #suspectPhonesContainer select[data-phone-field="label"]')?.value || 'Primary',
                    phones:           typeof getAllSuspectPhonesPayload === 'function' ? getAllSuspectPhonesPayload() : [],
                    additional_phones:typeof getAdditionalPhonesPayload === 'function' ? getAdditionalPhonesPayload() : [],
                    additional_phone_labels: (typeof getAdditionalPhonesPayload === 'function' ? getAdditionalPhonesPayload() : []).map(p => p.label),
                    address:          s1Address,
                    residential_address: s1Address,
                    gps:              s1Address,
                    marital_status:   s1Marital,
                    has_children:     s1HasKids,
                    children_count:   s1Kids,
                    spouse_name:      s1SpouseN,
                    spouse_phone:     s1SpouseP,
                    spouse_info:      s1SpouseI,
                    partner_name:     s1PartnerN,
                    partner_phone:    s1PartnerP,
                    partner_info:     s1PartnerI,
                    spouse_summary:   s1SpouseSummary,
                    offense:          s1Offense,
                    raid_location:    s1Raid,
                    date_of_arrest:   s1Date,
                    arrest_date:      s1Date,
                    time_of_arrest:   s1Time,
                    arrest_time:      s1Time,
                    holding_facility: s1Facility,
                    facility:         s1Facility,
                    mugshot_front:    s1MugFront,
                    mugshot_left:     s1MugLeft,
                    mugshot_right:    s1MugRight,
                    mugshots: {
                        front: s1MugFront,
                        left:  s1MugLeft,
                        right: s1MugRight
                    },
                });
            }

            // --- Additional suspects: read via data-field attributes ---
            const dynamicCards = document.querySelectorAll('.suspect-card-dynamic');
            let suspectNum = 2;
            dynamicCards.forEach(card => {
                const getField = (field) => {
                    const el = card.querySelector(`[data-field="${field}"]`);
                    if (!el) return '';
                    if (el.type === 'checkbox') return el.checked;
                    return el.value?.trim() || '';
                };
                const sName     = getField('suspect_name');
                const sAlias    = getField('suspect_alias');
                const sCard     = getField('ghana_card');
                const sGender   = getField('gender') || '';
                const sDOB      = getField('dob');
                const sAge      = getField('age');
                const sHeight   = getField('height');
                const sEye      = getField('eye_color');
                const sNat      = getField('nationality') || '';
                const sPOB      = getField('pob') || getField('place_of_birth') || getField('hometown') || card.querySelector('[data-field="pob"]')?.value?.trim() || card.querySelector('[data-field="place_of_birth"]')?.value?.trim() || card.querySelector('[data-field="hometown"]')?.value?.trim() || '';
                // Collect multiple phone numbers for this co-suspect
                let coSuspectPhones = [];
                if (typeof getCardPhonesPayload === 'function') {
                    coSuspectPhones = getCardPhonesPayload(card);
                } else {
                    const phoneRows = card.querySelectorAll('.suspect-phone-row');
                    phoneRows.forEach((pRow, pIdx) => {
                        const numEl = pRow.querySelector('[data-phone-field="number"]') || pRow.querySelector('input[type="text"]');
                        const lblEl = pRow.querySelector('[data-phone-field="label"]') || pRow.querySelector('select');
                        const num = numEl?.value?.trim() || '';
                        const lbl = lblEl?.value?.trim() || (pIdx === 0 ? 'Primary' : 'Alternate');
                        if (num) {
                            coSuspectPhones.push({
                                label: lbl,
                                number: num,
                                is_primary: (pIdx === 0)
                            });
                        }
                    });
                }

                let sPhone = '';
                let sPhoneLabel = 'Primary';
                let additionalPhones = [];
                let additionalPhoneLabels = [];

                if (coSuspectPhones.length > 0) {
                    sPhone = coSuspectPhones[0].number;
                    sPhoneLabel = coSuspectPhones[0].label;
                    for (let i = 1; i < coSuspectPhones.length; i++) {
                        additionalPhones.push(coSuspectPhones[i].number);
                        additionalPhoneLabels.push(coSuspectPhones[i].label);
                    }
                } else {
                    sPhone = getField('phone') || getField('phone_number') || card.querySelector('[data-field="phone"]')?.value?.trim() || '';
                    if (sPhone) {
                        coSuspectPhones.push({ label: 'Primary', number: sPhone, is_primary: true });
                    }
                }

                const sAddress  = getField('address') || getField('residential_address') || getField('gps') || card.querySelector('[data-field="address"]')?.value?.trim() || card.querySelector('[data-field="residential_address"]')?.value?.trim() || '';
                let sMarital    = getField('marital_status') || card.querySelector('[data-field="marital_status"]')?.value?.trim() || '';
                if (!sMarital) {
                    const dynMaritalLabel = card.querySelector('.dyn-marital-label')?.innerText?.trim();
                    if (dynMaritalLabel && dynMaritalLabel !== 'Select Marital / Relationship Status') {
                        sMarital = dynMaritalLabel;
                    }
                }
                const sHasKids  = getField('has_children') === true;
                const sKids     = sHasKids ? (getField('children_count') || '1') : '0';
                const sSpouseN  = getField('spouse_name');
                const sSpouseP  = getField('spouse_phone');
                const sSpouseI  = getField('spouse_info');
                const sPartnerN = getField('partner_name');
                const sPartnerP = getField('partner_phone');
                const sPartnerI = getField('partner_info');
                const sRaid     = getField('raid_location');
                const sOffense  = getField('offense');
                const sDate     = getField('date_of_arrest') || getField('arrest_date');
                const sTime     = getField('time_of_arrest') || getField('arrest_time');
                const sFacility = getField('holding_facility') || getField('facility');

                const sMugFrontRaw = document.getElementById(`hidden-front-${suspectNum}`)?.value
                    || document.getElementById(`preview-front-${suspectNum}`)?.src
                    || document.getElementById(`url-front-${suspectNum}`)?.value
                    || card.querySelector('input[id^="hidden-front"]')?.value
                    || card.querySelector('img[id^="preview-front"]')?.src
                    || null;

                const sMugLeftRaw  = document.getElementById(`hidden-left-profile-${suspectNum}`)?.value
                    || document.getElementById(`preview-left-profile-${suspectNum}`)?.src
                    || document.getElementById(`url-left-profile-${suspectNum}`)?.value
                    || card.querySelector('input[id^="hidden-left"]')?.value
                    || card.querySelector('img[id^="preview-left"]')?.src
                    || null;

                const sMugRightRaw = document.getElementById(`hidden-right-profile-${suspectNum}`)?.value
                    || document.getElementById(`preview-right-profile-${suspectNum}`)?.src
                    || document.getElementById(`url-right-profile-${suspectNum}`)?.value
                    || card.querySelector('input[id^="hidden-right"]')?.value
                    || card.querySelector('img[id^="preview-right"]')?.src
                    || null;

                const sMugFront = cleanMugshotSrc(sMugFrontRaw);
                const sMugLeft  = cleanMugshotSrc(sMugLeftRaw);
                const sMugRight = cleanMugshotSrc(sMugRightRaw);

                let sSpouseSummary = 'N/A';
                if (sMarital === 'Married') {
                    sSpouseSummary = `Spouse: ${sSpouseN} (${sSpouseP}) - ${sSpouseI}`;
                } else if (sMarital === 'Dating / In Relationship') {
                    sSpouseSummary = `Partner: ${sPartnerN} (${sPartnerP}) - ${sPartnerI}`;
                }

                // Only add if a name was entered
                if (sName) {
                    suspects.push({
                        associate_id:     'COS-' + String(suspectNum - 1).padStart(3, '0'),
                        suspect_id:       'COS-' + String(suspectNum - 1).padStart(3, '0'),
                        suspect_number:   suspectNum,
                        is_associate:     true,
                        is_co_suspect:    true,
                        association_role: 'Co-Suspect',
                        role:             'Co-Suspect',
                        suspect_name:     sName,
                        suspect_alias:    sAlias,
                        ghana_card:       sCard,
                        gender:           sGender,
                        dob:              sDOB,
                        age:              sAge,
                        height:           sHeight,
                        eye_color:        sEye,
                        nationality:      sNat,
                        pob:              sPOB,
                        place_of_birth:   sPOB,
                        hometown:         sPOB,
                        phone:            sPhone,
                        phone_label:      sPhoneLabel,
                        phones:           coSuspectPhones,
                        additional_phones:additionalPhones,
                        additional_phone_labels: additionalPhoneLabels,
                        address:          sAddress,
                        residential_address: sAddress,
                        gps:              sAddress,
                        marital_status:   sMarital,
                        has_children:     sHasKids,
                        children_count:   sKids,
                        spouse_name:      sSpouseN,
                        spouse_phone:     sSpouseP,
                        spouse_info:      sSpouseI,
                        partner_name:     sPartnerN,
                        partner_phone:    sPartnerP,
                        partner_info:     sPartnerI,
                        spouse_summary:   sSpouseSummary,
                        offense:          sOffense,
                        raid_location:    sRaid,
                        date_of_arrest:   sDate,
                        arrest_date:      sDate,
                        time_of_arrest:   sTime,
                        arrest_time:      sTime,
                        holding_facility: sFacility,
                        facility:         sFacility,
                        mugshot_front:    sMugFront,
                        mugshot_left:     sMugLeft,
                        mugshot_right:    sMugRight,
                        mugshots: {
                            front: sMugFront,
                            left:  sMugLeft,
                            right: sMugRight
                        },
                    });
                    suspectNum++;
                }
            });

            return suspects;
        }

        function toggleSidebarEventMenu(e) {
            if (e) e.stopPropagation();
            const menu = document.getElementById('sidebarEventMenu');
            const chevron = document.getElementById('sidebarEventChevron');
            if (menu) menu.classList.toggle('hidden');
            if (chevron) chevron.classList.toggle('rotate-180');
        }

        function handleArrestMugshotDragOver(e, dropzoneEl) {
            e.preventDefault();
            e.stopPropagation();
            if (dropzoneEl) dropzoneEl.classList.add('border-emerald-600', 'bg-emerald-100/50');
        }

        function handleArrestMugshotDragLeave(e, dropzoneEl) {
            e.preventDefault();
            e.stopPropagation();
            if (dropzoneEl) dropzoneEl.classList.remove('border-emerald-600', 'bg-emerald-100/50');
        }

        function handleArrestMugshotDrop(e, angleKey) {
            e.preventDefault();
            e.stopPropagation();
            const dropzoneEl = e.currentTarget;
            if (dropzoneEl) dropzoneEl.classList.remove('border-emerald-600', 'bg-emerald-100/50');

            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                const fileInput = document.getElementById(`file-${angleKey}`);
                if (fileInput) {
                    fileInput.files = e.dataTransfer.files;
                    previewMugshotFileArrest(fileInput, angleKey);
                }
            }
        }

        function triggerMugshotUpload(angleKey) {
            const input = document.getElementById(`file-${angleKey}`);
            if (input) input.click();
        }

        function previewMugshot(event, angleKey) {
            previewMugshotFileArrest(event.target, angleKey);
        }

        function previewMugshotFileArrest(fileInput, angleKey) {
            if (fileInput.files && fileInput.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const dataUrl = e.target.result;
                    applyArrestMugshotPreview(dataUrl, angleKey);
                };
                reader.readAsDataURL(fileInput.files[0]);
            }
        }

        function previewMugshotUrlArrest(urlStr, angleKey) {
            const val = (urlStr || '').trim();
            if (val) {
                applyArrestMugshotPreview(val, angleKey);
            } else {
                clearArrestMugshotState(angleKey);
            }
        }

        function applyArrestMugshotPreview(srcUrl, angleKey) {
            const img = document.getElementById(`preview-${angleKey}`);
            const placeholder = document.getElementById(`placeholder-${angleKey}`);
            const actions = document.getElementById(`actions-${angleKey}`);
            const badge = document.getElementById(`badge-${angleKey}`);
            const hidden = document.getElementById(`hidden-${angleKey}`);

            if (img) {
                img.src = srcUrl;
                img.classList.remove('hidden');
            }
            if (placeholder) placeholder.classList.add('hidden');
            if (actions) actions.classList.remove('hidden');
            if (hidden) hidden.value = srcUrl;
            if (badge) {
                badge.innerText = 'Uploaded';
                badge.className = 'text-[9px] font-mono-code font-extrabold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded border border-emerald-200 uppercase';
            }
            if (typeof saveDocketFormDraft === 'function') saveDocketFormDraft();
        }

        function clearArrestMugshotPhoto(angleKey) {
            const fileInput = document.getElementById(`file-${angleKey}`);
            const urlInput = document.getElementById(`url-${angleKey}`);
            if (fileInput) fileInput.value = '';
            if (urlInput) urlInput.value = '';
            clearArrestMugshotState(angleKey);
        }

        function clearArrestMugshotState(angleKey) {
            const img = document.getElementById(`preview-${angleKey}`);
            const placeholder = document.getElementById(`placeholder-${angleKey}`);
            const actions = document.getElementById(`actions-${angleKey}`);
            const badge = document.getElementById(`badge-${angleKey}`);
            const hidden = document.getElementById(`hidden-${angleKey}`);

            if (img) {
                img.src = '';
                img.classList.add('hidden');
            }
            if (placeholder) placeholder.classList.remove('hidden');
            if (actions) actions.classList.add('hidden');
            if (hidden) hidden.value = '';
            if (badge) {
                badge.innerText = 'Not Uploaded';
                badge.className = 'text-[9px] font-mono-code font-bold text-slate-400 uppercase';
            }
            if (typeof saveDocketFormDraft === 'function') saveDocketFormDraft();
        }

        function openArrestMugshotModal(imgSrc, titleText) {
            const modal = document.getElementById('arrestMugshotLightboxModal');
            const modalImg = document.getElementById('arrestMugshotModalImage');
            const modalTitle = document.getElementById('arrestMugshotModalTitle');
            if (modal && modalImg) {
                modalImg.src = imgSrc;
                if (modalTitle) modalTitle.innerText = titleText || 'Facial Biometric High-Res View';
                modal.classList.remove('hidden');
            }
        }

        function closeArrestMugshotModal() {
            const modal = document.getElementById('arrestMugshotLightboxModal');
            if (modal) modal.classList.add('hidden');
        }

        let seizedExhibitDataUrls = [];

        function getBlockSeizurePhotos(block) {
            const targetBlock = block || document.querySelector('.seizure-block');
            if (!targetBlock) return window.seizedExhibitDataUrls || [];
            if (!targetBlock._seizurePhotos) {
                targetBlock._seizurePhotos = [];
            }
            return targetBlock._seizurePhotos;
        }

        function setBlockSeizurePhotos(block, photosArray) {
            const targetBlock = block || document.querySelector('.seizure-block');
            if (!targetBlock) {
                window.seizedExhibitDataUrls = Array.isArray(photosArray) ? photosArray : [];
                return;
            }
            targetBlock._seizurePhotos = Array.isArray(photosArray) ? [...photosArray] : [];
            renderSeizureBlockPhotosGrid(targetBlock);
            syncGlobalSeizurePhotos();
        }

        function syncGlobalSeizurePhotos() {
            const firstBlock = document.querySelector('.seizure-block');
            window.seizedExhibitDataUrls = firstBlock ? getBlockSeizurePhotos(firstBlock) : [];
        }

        function triggerSeizurePhotoUpload(triggerEl) {
            const block = triggerEl ? triggerEl.closest('.seizure-block') : document.querySelector('.seizure-block');
            const input = block ? (block.querySelector('.fileSeizurePhoto') || block.querySelector('#fileSeizurePhoto')) : document.getElementById('fileSeizurePhoto');
            if (input) input.click();
        }

        function previewSeizurePhotos(event, inputEl) {
            const block = (inputEl ? inputEl.closest('.seizure-block') : null) || (event.target ? event.target.closest('.seizure-block') : null) || document.querySelector('.seizure-block');
            const files = Array.from(event.target.files || []);
            if (!files || files.length === 0) return;

            loadSeizurePhotoFiles(files, block, () => {
                if (event.target) event.target.value = '';
            });
        }

        function handleSeizurePhotoDrop(event, dropzoneEl) {
            event.preventDefault();
            event.stopPropagation();
            if (dropzoneEl) {
                dropzoneEl.classList.remove('border-emerald-600', 'border-emerald-500', 'bg-emerald-50/50');
            }
            const block = dropzoneEl ? dropzoneEl.closest('.seizure-block') : document.querySelector('.seizure-block');
            const dt = event.dataTransfer;
            const files = Array.from(dt?.files || []).filter(file => file.type.startsWith('image/'));
            if (files.length === 0) return;

            loadSeizurePhotoFiles(files, block);
        }

        function loadSeizurePhotoFiles(files, block, callback) {
            const targetBlock = block || document.querySelector('.seizure-block');
            const photos = getBlockSeizurePhotos(targetBlock);
            let loadedCount = 0;

            files.forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    photos.push(e.target.result);
                    loadedCount++;
                    if (loadedCount === files.length) {
                        renderSeizureBlockPhotosGrid(targetBlock);
                        syncGlobalSeizurePhotos();
                        if (typeof saveDocketFormDraft === 'function') {
                            saveDocketFormDraft();
                        }
                        if (callback) callback();
                    }
                };
                reader.readAsDataURL(file);
            });
        }

        function renderSeizurePhotosGrid(block) {
            renderSeizureBlockPhotosGrid(block);
        }

        function renderSeizureBlockPhotosGrid(block) {
            const targetBlock = block || document.querySelector('.seizure-block');
            if (!targetBlock) return;

            const grid = targetBlock.querySelector('.seizurePhotosGrid') || targetBlock.querySelector('#seizurePhotosGrid');
            const countBadge = targetBlock.querySelector('.seizure-photos-count') || targetBlock.querySelector('#seizurePhotosCount');
            if (!grid) return;

            const photos = getBlockSeizurePhotos(targetBlock);
            grid.innerHTML = '';

            if (photos.length === 0) {
                grid.classList.add('hidden');
                if (countBadge) countBadge.classList.add('hidden');
                return;
            }

            grid.classList.remove('hidden');
            if (countBadge) {
                countBadge.classList.remove('hidden');
                countBadge.innerText = `${photos.length} Photo${photos.length > 1 ? 's' : ''} Uploaded`;
            }

            photos.forEach((dataUrl, index) => {
                const card = document.createElement('div');
                card.className = 'relative group rounded-xl overflow-hidden border border-slate-200 bg-white shadow-2xs h-24 flex items-center justify-center seizure-photo-item';
                card.innerHTML = `
                    <img src="${dataUrl}" class="w-full h-full object-cover" alt="Exhibit Photo ${index + 1}">
                    <div class="absolute inset-0 bg-slate-900/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center space-x-1">
                        <button type="button" class="remove-photo-btn p-1.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors shadow-xs" title="Delete Photo">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                    <span class="absolute bottom-1 left-1 px-1.5 py-0.5 bg-slate-900/80 text-white text-[9px] font-mono-code font-bold rounded">#${index + 1}</span>
                `;
                card.querySelector('.remove-photo-btn').addEventListener('click', function(ev) {
                    ev.stopPropagation();
                    removeSingleBlockSeizurePhoto(targetBlock, index);
                });
                grid.appendChild(card);
            });
        }

        function removeSingleBlockSeizurePhoto(block, index) {
            const targetBlock = block || document.querySelector('.seizure-block');
            const photos = getBlockSeizurePhotos(targetBlock);
            photos.splice(index, 1);
            renderSeizureBlockPhotosGrid(targetBlock);
            syncGlobalSeizurePhotos();
            if (typeof saveDocketFormDraft === 'function') {
                saveDocketFormDraft();
            }
        }

        function removeSingleSeizurePhoto(index, e) {
            if (e) e.stopPropagation();
            const block = (e && e.target) ? e.target.closest('.seizure-block') : document.querySelector('.seizure-block');
            removeSingleBlockSeizurePhoto(block, index);
        }


        function validateSuspectDocketForm() {
            if (typeof window.isSections1To3OptionalMode === 'function' && window.isSections1To3OptionalMode()) {
                return true;
            }

            const requiredFields = [
                { id: 'newArrestedName', label: 'Suspect Full Legal Name' },
                { id: 'newArrestedGender', label: 'Gender', triggerId: 'genderDropdownTrigger' },
                { id: 'newArrestedDOB', label: 'Date of Birth' },
                { id: 'newArrestedNationality', label: 'Nationality', triggerId: 'nationalityDropdownTrigger' },
                { id: 'newArrestedPOB', label: 'Place of Birth' },
                { id: 'newArrestedPhone', label: 'Contact Phone Number' },
                { id: 'newArrestedAddress', label: 'Residential Address / GPS' },
                { id: 'newArrestedRaidLocation', label: 'Raid Scene Location' },
                { id: 'newArrestedOffense', label: 'Primary Offense Description' },
                { id: 'newArrestedDate', label: 'Date of Arrest' },
                { id: 'newArrestedTime', label: 'Time of Arrest' },
                { id: 'newArrestedFacility', label: 'Holding Facility' },
            ];

            let missingLabels = [];
            let firstInvalidEl = null;

            // Clear previous error styling
            requiredFields.forEach(item => {
                const target = item.triggerId ? document.getElementById(item.triggerId) : document.getElementById(item.id);
                if (target) {
                    target.classList.remove('border-red-500', 'ring-2', 'ring-red-500/20');
                }
            });

            requiredFields.forEach(item => {
                const inputEl = document.getElementById(item.id);
                const targetEl = item.triggerId ? document.getElementById(item.triggerId) : inputEl;
                const val = inputEl ? inputEl.value.trim() : '';

                if (!val) {
                    missingLabels.push(item.label);
                    if (targetEl) {
                        targetEl.classList.add('border-red-500', 'ring-2', 'ring-red-500/20');
                        if (!firstInvalidEl) {
                            firstInvalidEl = targetEl;
                        }
                    }
                }
            });

            if (missingLabels.length > 0) {
                if (firstInvalidEl) {
                    firstInvalidEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    if (typeof firstInvalidEl.focus === 'function') {
                        firstInvalidEl.focus();
                    }
                }

                showValidationErrorPopover(missingLabels);
                return false;
            }

            return true;
        }

        let isCaseSubmitting = false;

        function setSubmittingState(isSubmitting) {
            isCaseSubmitting = isSubmitting;
            const btns = document.querySelectorAll('#submitArrestDocketBtn, #submitSection1To4DocketBtn, button[onclick*="processNewArrested"]');
            btns.forEach(btn => {
                btn.disabled = isSubmitting;
                if (isSubmitting) {
                    if (!btn.dataset.originalHtml) {
                        btn.dataset.originalHtml = btn.innerHTML;
                    }
                    btn.classList.add('opacity-75', 'cursor-not-allowed');
                    btn.innerHTML = `
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>Saving... Please wait</span>
                    `;
                } else {
                    btn.classList.remove('opacity-75', 'cursor-not-allowed');
                    if (btn.dataset.originalHtml) {
                        btn.innerHTML = btn.dataset.originalHtml;
                    }
                }
            });
        }

        function processNewArrested(confirmDuplicate = false) {
            // Check global lock immediately to prevent duplicate submissions on double-click
            if (isCaseSubmitting) {
                return;
            }

            const noSuspectMode = typeof window.isSections1To3OptionalMode === 'function' && window.isSections1To3OptionalMode();

            if (!noSuspectMode) {
                if (!validateSuspectDocketForm()) {
                    return;
                }
                if (typeof validateSuspectPhones === 'function' && !validateSuspectPhones()) {
                    return;
                }
            }

            // Immediately lock submission and disable button
            setSubmittingState(true);

            const ref = noSuspectMode ? '' : (document.getElementById('newArrestedRaidRef')?.value || 'RAID-2026-0419');
            const name = noSuspectMode ? 'Unassigned (No Suspect)' : (document.getElementById('newArrestedName')?.value?.trim() || '');
            const alias = noSuspectMode ? '' : (document.getElementById('newArrestedAlias')?.value?.trim() || '');
            const card = noSuspectMode ? '' : (document.getElementById('newArrestedID')?.value?.trim() || '');
            const gender = document.getElementById('newArrestedGender')?.value?.trim() || '';
            const dob = document.getElementById('newArrestedDOB')?.value?.trim() || '';
            const age = document.getElementById('newArrestedAge')?.value?.trim() || '';
            const height = document.getElementById('newArrestedHeight')?.value?.trim() || '';
            const eyeColor = document.getElementById('newArrestedEyeColor')?.value?.trim() || '';
            const pob = document.getElementById('newArrestedPOB')?.value?.trim() || '';
            const phone = document.getElementById('newArrestedPhone')?.value?.trim() || '';
            const address = document.getElementById('newArrestedAddress')?.value?.trim() || '';
            const maritalVal = document.getElementById('newArrestedMarital')?.value;
            const maritalLabel = document.getElementById('selectedMaritalLabel')?.innerText;
            let marital = (maritalVal && maritalVal.trim() !== '') ? maritalVal : ((maritalLabel && maritalLabel !== 'Select Marital Status...' && maritalLabel !== 'Select Marital / Relationship Status') ? maritalLabel : '');
            if (marital === 'N/A') { marital = ''; }
            const hasChildren = document.getElementById('hasChildrenCheckbox')?.checked;
            const childrenVal = hasChildren ? (document.getElementById('newArrestedChildren')?.value || '1') : '0';
            const childrenText = hasChildren ? `${childrenVal} Kids` : 'N/A';
            const offense = document.getElementById('newArrestedOffense')?.value || 'Under Investigation';
            const raidLocation = document.getElementById('newArrestedRaidLocation')?.value || 'Unspecified Scene';
            const facility = document.getElementById('newArrestedFacility')?.value || 'NACOC Holding Cell';
            const caseTitleInput = document.getElementById('caseTitleInput')?.value;

            const caseTitle = caseTitleInput && caseTitleInput.trim() !== '' && !/^republic\s+vs\.?$/i.test(caseTitleInput.trim())
                ? caseTitleInput.trim()
                : (name ? `Republic vs ${name}` : (caseTitleInput?.trim() || 'Republic vs'));
            const caseRef = document.getElementById('labCaseReferenceInput')?.value || ('NCC-' + new Date().getFullYear() + '-' + Math.floor(1000 + Math.random() * 9000));

            const spouseName = document.getElementById('newArrestedSpouseName')?.value || '';
            const spousePhone = document.getElementById('newArrestedSpousePhone')?.value || '';
            const spouseInfo = document.getElementById('newArrestedSpouseInfo')?.value || '';
            const partnerName = document.getElementById('newArrestedPartnerName')?.value || '';
            const partnerPhone = document.getElementById('newArrestedPartnerPhone')?.value || '';
            const partnerInfo = document.getElementById('newArrestedPartnerInfo')?.value || '';

            let partnerInfoHtml = '';
            if (marital === 'Married') {
                partnerInfoHtml = `<div class="text-[10px] text-emerald-700 font-semibold">Spouse: ${spouseName} (${spousePhone})</div>`;
            } else if (marital === 'Dating / In Relationship') {
                partnerInfoHtml = `<div class="text-[10px] text-emerald-700 font-semibold">Partner: ${partnerName} (${partnerPhone})</div>`;
            }

            let spouseSummary = 'N/A';
            if (marital === 'Married') {
                spouseSummary = `Spouse: ${spouseName} (${spousePhone}) - ${spouseInfo}`;
            } else if (marital === 'Dating / In Relationship') {
                spouseSummary = `Partner: ${partnerName} (${partnerPhone}) - ${partnerInfo}`;
            }

            const hasSeizures = document.getElementById('hasSeizuresCheckbox')?.checked;
            const seizureBlocks = document.querySelectorAll('.seizure-block');

            const seizuresList = [];
            let seizureSummaryParts = [];

            // Collect all suspects for exhibit association in joint cases
            const allSuspectsForExhibits = collectAllSuspects();
            const jointSuspectNamesLabel = allSuspectsForExhibits.map(s => s.suspect_name).filter(Boolean).join(', ');

            if (hasSeizures) {
                seizureBlocks.forEach((block, idx) => {
                    const itemNum = idx + 1;
                    const rawExhibitCat = block.querySelector('#newSeizureCategory')?.value || '';

                    const drugName = block.querySelector('#drugSelect')?.value || block.querySelector('#newSeizureNameDrug')?.value || '';
                    const wpnName = block.querySelector('#weaponSelect')?.value || block.querySelector('#newSeizureNameWeapon')?.value || '';
                    const currName = block.querySelector('#newSeizureNameCurrency')?.value || '';
                    const ammoName = block.querySelector('#ammoSearchInput')?.value || block.querySelector('#newSeizureNameAmmo')?.value || '';
                    const devName = block.querySelector('#deviceSearchInput')?.value || block.querySelector('#newSeizureNameDevice')?.value || '';
                    const genName = block.querySelector('#newSeizureName')?.value || '';

                    const seizureName = drugName || wpnName || currName || ammoName || devName || genName || '';
                    const seizureQty = block.querySelector('#newSeizureQuantity')?.value || '';
                    const seizurePackaging = block.querySelector('#pkgSearchInput')?.value || block.querySelector('#newSeizurePackaging')?.value || '';
                    const seizureWeight = block.querySelector('#newSeizureWeight')?.value || '';
                    const seizureUnit = block.querySelector('#unitSearchInput')?.value || block.querySelector('#newSeizureUnit')?.value || '';
                    const seizureValue = block.querySelector('#newSeizureValue')?.value || '';

                    // Skip empty seizure block if no category, name, or metric was entered
                    if (!rawExhibitCat && !seizureName && !seizureQty && !seizureWeight && !seizureValue) {
                        return;
                    }

                    const exhibitCat = rawExhibitCat || (drugName ? 'Drugs' : (wpnName ? 'Weapons' : (currName ? 'Money' : (devName ? 'Electronic Devices' : (ammoName ? 'Ammunition' : 'General Contraband')))));
                const seizureConcealment = block.querySelector('#newSeizureConcealment')?.value || '';
                const seizureDate = block.querySelector('#newSeizureDate')?.value || '';
                const seizureTime = block.querySelector('#newSeizureTime')?.value || '';
                const seizureLocation = block.querySelector('#newSeizureLocation')?.value || '';
                const seizureOfficer = getSeizureOfficersFormatted(block);
                const sourceOfExhibits = block.querySelector('#newSeizureSourceOfExhibits')?.value || block.querySelector('.seizure-source-of-exhibits-input')?.value || block.querySelector('[name="source_of_exhibits"]')?.value || block.dataset?.preservedSourceOfExhibits || '';
                const investigatingOfficer = block.querySelector('#newSeizureInvestigatingOfficer')?.value || block.querySelector('.seizure-investigating-officer-input')?.value || block.querySelector('[name="investigating_officer"]')?.value || '';
                const sourceRequestingUnit = block.querySelector('#newSeizureSourceRequestingUnit')?.value || block.querySelector('.seizure-source-requesting-unit-input')?.value || block.querySelector('[name="source_requesting_unit"]')?.value || '';
                const seizureReason = block.querySelector('#newSeizureReason')?.value || '';

                // Device fields
                const devModel = block.querySelector('#newDevModel')?.value || '';
                const devSerialNo = block.querySelector('#newDevSerialNo')?.value || '';
                const devImei1 = block.querySelector('#newDevImei1')?.value || '';
                const devImei2 = block.querySelector('#newDevImei2')?.value || '';
                const devMacAddress = block.querySelector('#newDevMacAddress')?.value || '';
                const devStorage = block.querySelector('#newDevStorage')?.value || '';
                const devColour = block.querySelector('#newDevColour')?.value || '';
                const devMarks = block.querySelector('#newDevMarks')?.value || '';

                // Vehicle fields
                const vehMake = block.querySelector('#vehMake')?.value || '';
                const vehModel = block.querySelector('#vehModel')?.value || '';
                const vehType = block.querySelector('#vehType')?.value || '';
                const vehYear = block.querySelector('#vehYear')?.value || '';
                const vehColour = block.querySelector('#vehColour')?.value || '';
                const vehRegNo = block.querySelector('#vehRegNo')?.value || '';
                const vehVIN = block.querySelector('#vehVIN')?.value || '';
                const vehEngineNo = block.querySelector('#vehEngineNo')?.value || '';
                const vehCountryReg = block.querySelector('#vehCountryReg')?.value || '';
                const vehOwner = block.querySelector('#vehOwner')?.value || '';
                const vehDriver = block.querySelector('#vehDriver')?.value || '';
                const vehOwnerContact = block.querySelector('#vehOwnerContact')?.value || '';
                const vehDocsAvailable = block.querySelector('#vehDocsAvailable')?.value || '';
                const vehDocRef = block.querySelector('#vehDocRef')?.value || '';
                const vehCondition = block.querySelector('#vehCondition')?.value || '';
                const vehFuel = block.querySelector('#vehFuel')?.value || '';
                const vehKeysRecv = block.querySelector('#vehKeysRecv')?.value || '';
                const vehKeyCount = block.querySelector('#vehKeyCount')?.value || '';
                const vehDamage = block.querySelector('#vehDamage')?.value || '';
                const vehMods = block.querySelector('#vehMods')?.value || '';
                const vehFeatures = block.querySelector('#vehFeatures')?.value || '';

                // Weapon fields
                const wpnModel = block.querySelector('#wpnModel')?.value || '';
                const wpnSerialNo = block.querySelector('#wpnSerialNo')?.value || '';
                const wpnCalibre = block.querySelector('#wpnCalibre')?.value || '';
                const wpnColour = block.querySelector('#wpnColour')?.value || '';
                const wpnSize = block.querySelector('#wpnSize')?.value || '';
                const wpnMarks = block.querySelector('#wpnMarks')?.value || '';
                const wpnLocationFound = block.querySelector('#wpnLocationFound')?.value || '';
                const wpnLoadedStatus = block.querySelector('#wpnLoadedStatus')?.value || '';
                const wpnCondition = block.querySelector('#wpnCondition')?.value || '';

                // Ammunition fields
                const ammoCartridgeAppearance = block.querySelector('#ammoCartridgeAppearance')?.value || '';
                const ammoProjectileType = block.querySelector('#ammoProjectileType')?.value || '';
                const ammoCaseMaterial = block.querySelector('#ammoCaseMaterial')?.value || '';
                const ammoColourMarkings = block.querySelector('#ammoColourMarkings')?.value || '';
                const ammoConditionVal = block.querySelector('#ammoCondition')?.value || '';
                const ammoOtherConditionVal = block.querySelector('#ammoOtherCondition')?.value || '';
                const ammoPackagingTypeVal = block.querySelector('#ammoPackagingType')?.value || '';
                const ammoSealConditionVal = block.querySelector('#ammoSealCondition')?.value || '';
                const ammoPackageReferenceNumberVal = block.querySelector('#ammoPackageReferenceNumber')?.value || '';
                const ammoConditionWhenReceivedVal = block.querySelector('#ammoConditionWhenReceived')?.value || '';

                const ammoDetailsObj = exhibitCat === 'Ammunition' ? {
                    cartridge_appearance: ammoCartridgeAppearance,
                    projectile_bullet_type: ammoProjectileType,
                    cartridge_case_material: ammoCaseMaterial,
                    colour_markings: ammoColourMarkings,
                    condition: ammoConditionVal,
                    other_condition: ammoOtherConditionVal,
                    packaging_type: ammoPackagingTypeVal,
                    seal_tamper_evident_condition: ammoSealConditionVal,
                    package_reference_number: ammoPackageReferenceNumberVal,
                    condition_when_received: ammoConditionWhenReceivedVal
                } : null;

                const selectedAssocPerson = block.querySelector('.seizure-associated-person-select')?.value?.trim() || '';
                let finalAssocPerson = selectedAssocPerson;
                if (!finalAssocPerson || finalAssocPerson === 'All Suspects') {
                    finalAssocPerson = 'All Suspects';
                }

                const exhibitRemark = block.querySelector('#newSeizureRemark')?.value?.trim() || block.querySelector('.seizure-remark-input')?.value?.trim() || block.querySelector('[name="exhibit_remark"]')?.value?.trim() || '';

                seizuresList.push({
                    item_number: itemNum,
                    linked_exhibit_id: block.dataset.linkedExhibitId ? Number(block.dataset.linkedExhibitId) : null,
                    property_id: block.dataset.propertyId || block.getAttribute('data-property-id') || null,
                    exhibit_category: exhibitCat,
                    exhibit_type: block.querySelector('.exhibit-type-select')?.value || (exhibitCat === 'Drugs' ? 'Drug Exhibit' : 'Non-Drug Exhibit'),
                    drug_classification: block.querySelector('.drug-classification-select')?.value || 'N/A',
                    other_exhibit_description: block.querySelector('.other-exhibit-description-input')?.value || block.querySelector('#newOtherExhibitDescription')?.value || '',
                    associated_person: finalAssocPerson,
                    suspect_ownership: selectedAssocPerson || 'All Suspects',
                    seizure_name: seizureName,
                    seizure_name_drug: drugName,
                    seizure_name_weapon: wpnName,
                    seizure_name_currency: currName,
                    seizure_name_ammo: ammoName,
                    seizure_name_device: devName,
                    seizure_quantity: seizureQty,
                    amount: (exhibitCat === 'Money' || exhibitCat === 'Currency' || exhibitCat === 'Cash') ? (seizureQty || seizureValue || '') : '',
                    seizure_packaging: seizurePackaging,
                    seizure_weight: seizureWeight,
                    seizure_unit: seizureUnit,
                    seizure_value: seizureValue,
                    seizure_concealment: seizureConcealment,
                    seizure_date: seizureDate,
                    seizure_time: seizureTime,
                    seizure_location: seizureLocation,
                    seizure_officer: seizureOfficer,
                    source_of_exhibits: sourceOfExhibits,
                    investigating_officer: investigatingOfficer,
                    source_requesting_unit: sourceRequestingUnit,
                    seizure_reason: seizureReason,
                    exhibit_remark: exhibitRemark,
                    dev_model: devModel,
                    dev_serial_no: devSerialNo,
                    dev_imei1: devImei1,
                    dev_imei2: devImei2,
                    dev_mac_address: devMacAddress,
                    dev_storage: devStorage,
                    dev_colour: devColour,
                    dev_marks: devMarks,
                    ammunition_details: ammoDetailsObj,
                    veh_make: vehMake,
                    veh_model: vehModel,
                    veh_type: vehType,
                    veh_year: vehYear,
                    veh_colour: vehColour,
                    veh_reg_no: vehRegNo,
                    veh_vin: vehVIN,
                    veh_engine_no: vehEngineNo,
                    veh_country_reg: vehCountryReg,
                    veh_owner: vehOwner,
                    veh_driver: vehDriver,
                    vehicle_make_model: vehMake ? (vehModel ? (vehMake + ' ' + vehModel) : vehMake) : vehModel,
                    vehicle_type: vehType,
                    vehicle_year: vehYear,
                    vehicle_color_type: vehColour,
                    vehicle_registration: vehRegNo,
                    vehicle_chassis_vin: vehVIN,
                    engine_number: vehEngineNo,
                    country_of_registration: vehCountryReg,
                    vehicle_owner: vehOwner,
                    vehicle_driver: vehDriver,
                    wpn_model: wpnModel,
                    wpn_serial_no: wpnSerialNo,
                    wpn_calibre: wpnCalibre,
                    wpn_colour: wpnColour,
                    wpn_size: wpnSize,
                    wpn_marks: wpnMarks,
                    wpn_location_found: wpnLocationFound,
                    wpn_loaded_status: wpnLoadedStatus,
                    wpn_condition: wpnCondition,
                    weapon_model: wpnModel,
                    weapon_serial: wpnSerialNo,
                    weapon_calibre: wpnCalibre,
                    weapon_colour: wpnColour,
                    weapon_size: wpnSize,
                    veh: {
                        make: vehMake, model: vehModel, type: vehType, year: vehYear, colour: vehColour,
                        reg_no: vehRegNo, vin: vehVIN, engine_no: vehEngineNo, country_reg: vehCountryReg,
                        owner: vehOwner, driver: vehDriver, owner_contact: vehOwnerContact, docs_available: vehDocsAvailable,
                        doc_ref: vehDocRef, condition: vehCondition, fuel: vehFuel, keys_recv: vehKeysRecv,
                        key_count: vehKeyCount, damage: vehDamage, mods: vehMods, features: vehFeatures
                    },
                    wpn: {
                        model: wpnModel, serial_no: wpnSerialNo, calibre: wpnCalibre, colour: wpnColour,
                        size: wpnSize, marks: wpnMarks, location_found: wpnLocationFound, loaded_status: wpnLoadedStatus, condition: wpnCondition
                    }
,
                    evidence_photos: getBlockSeizurePhotos(block)
                });

                let parts = [];
                if (seizureQty) parts.push(`Qty: ${seizureQty}`);
                if (seizurePackaging) parts.push(`Pkg: ${seizurePackaging}`);
                if (seizureWeight) parts.push(`Wt: ${seizureWeight} ${seizureUnit}`.trim());
                else if (seizureUnit) parts.push(`Unit: ${seizureUnit}`);
                if (seizureName) parts.push(seizureName);

                let itemDesc = parts.join(' | ').trim();
                if (!itemDesc) itemDesc = `Item #${itemNum} (${exhibitCat})`;
                seizureSummaryParts.push(`Item #${itemNum} [${exhibitCat}]: ${itemDesc}`);
            });
            }

            let seizureSummary = 'No contraband logged';
            let seizureHtml = '';
            if (noSuspectMode && seizuresList.length === 0) {
                setSubmittingState(false);
                showValidationErrorPopover(['Exhibit Category & Seized Commodity Details (Section 4)']);
                return;
            }
            if (noSuspectMode) {
                seizuresList.forEach(s => {
                    s.associated_person = 'Unassigned (No Suspect)';
                });
            }
            if (hasSeizures && seizuresList.length > 0) {
                seizureSummary = seizureSummaryParts.join(' \n ');
                seizureHtml = `<div class="mt-1 text-[10px] text-emerald-700 font-bold bg-emerald-50 px-1.5 py-0.5 rounded inline-block border border-emerald-200">Seized (${seizuresList.length} items): ${seizuresList.map(s => s.seizure_name || s.exhibit_category).filter(Boolean).join(', ')}</div>`;
            }

            const primarySeizure = seizuresList[0] || {};

            const mugshotFront = document.getElementById('preview-front')?.src || null;
            const mugshotLeft = document.getElementById('preview-left-profile')?.src || null;
            const mugshotRight = document.getElementById('preview-right-profile')?.src || null;

            const exhibitCat = (hasSeizures && primarySeizure.exhibit_category) ? primarySeizure.exhibit_category : null;
            const valuationText = (hasSeizures && primarySeizure.seizure_value && primarySeizure.seizure_value.trim() !== '') ? primarySeizure.seizure_value.trim() : 'N/A';

            // Vehicle Specs (Primary Seizure)
            const vehMake = primarySeizure.veh?.make || '';
            const vehModel = primarySeizure.veh?.model || '';
            const vehType = primarySeizure.veh?.type || '';
            const vehYear = primarySeizure.veh?.year || '';
            const vehColour = primarySeizure.veh?.colour || '';
            const vehRegNo = primarySeizure.veh?.reg_no || '';
            const vehVIN = primarySeizure.veh?.vin || '';
            const vehEngineNo = primarySeizure.veh?.engine_no || '';
            const vehCountryReg = primarySeizure.veh?.country_reg || '';
            const vehOwner = primarySeizure.veh?.owner || '';
            const vehDriver = primarySeizure.veh?.driver || '';
            const vehOwnerContact = primarySeizure.veh?.owner_contact || '';
            const vehDocsAvailable = primarySeizure.veh?.docs_available || '';
            const vehDocRef = primarySeizure.veh?.doc_ref || '';
            const vehCondition = primarySeizure.veh?.condition || '';
            const vehFuel = primarySeizure.veh?.fuel || '';
            const vehKeysRecv = primarySeizure.veh?.keys_recv || '';
            const vehKeyCount = primarySeizure.veh?.key_count || '';
            const vehDamage = primarySeizure.veh?.damage || '';
            const vehMods = primarySeizure.veh?.mods || '';
            const vehFeatures = primarySeizure.veh?.features || '';

            let presumptiveVal = null;
            const yesBox = document.getElementById('presumptiveTestYes');
            const noBox = document.getElementById('presumptiveTestNo');
            if (noBox && noBox.checked) {
                presumptiveVal = 'NO (Negative Reaction)';
            } else if (yesBox && yesBox.checked) {
                const customResult = document.getElementById('presumptiveReactionResultInput')?.value;
                presumptiveVal = customResult && customResult.trim() !== '' ? customResult.trim() : null;
            }

            // Collect all suspects (Suspect #1 + any additional)
            const allSuspects = noSuspectMode ? [] : collectAllSuspects();
            const resolvedPrimaryCommodity = primarySeizure.commodity_name || primarySeizure.seizure_name || primarySeizure.exhibit_category || 'Contraband';
            const resolvedTitle = noSuspectMode ? `Seized Exhibit: ${resolvedPrimaryCommodity} (Awaiting Suspect)` : caseTitle;
            const resolvedSuspectName = noSuspectMode ? 'Unassigned (No Suspect)' : name;
            const resolvedDocketMode = noSuspectMode ? 'SINGLE' : (document.getElementById('caseEntryTypeSelect')?.value || 'SINGLE');

            const docketPayload = {
                raid_ref: ref,
                full_name: name,
                alias: alias,
                id_card: card,
                gender: gender,
                dob: dob,
                age: age,
                height: height,
                eye_color: eyeColor,
                pob: pob,
                phone: phone,
                phones: typeof getAllSuspectPhonesPayload === 'function' ? getAllSuspectPhonesPayload() : [],
                address: address,
                marital_status: marital,
                spouse_summary: spouseSummary,
                dependents: childrenText,
                offense: offense,
                raid_location: raidLocation,
                latitude: document.getElementById('incidentLatitude')?.value || null,
                longitude: document.getElementById('incidentLongitude')?.value || null,
                holding_facility: facility,
                exhibit_category: exhibitCat,
                exhibits_summary: seizureSummary,
                valuation: valuationText,
                seizures: seizuresList,
                evidence_photos: seizedExhibitDataUrls,
                vehicle: primarySeizure.veh || {},
                weapon: primarySeizure.wpn || {},
                // All suspects stored here — primary + additional
                suspects: allSuspects,
                suspects_count: allSuspects.length,
                lab_examination: {
                    laboratory_number: document.getElementById('labNumberInput')?.value || '',
                    presumptive_result: presumptiveVal,
                    substance_identification: document.getElementById('confirmatorySubstanceIdentification')?.value || '',
                    detected_substances: typeof getDetectedSubstancesData === 'function' ? getDetectedSubstancesData() : [],
                    test_methods: document.getElementById('confirmatoryTestMethods')?.value || '',
                    specifications: document.getElementById('confirmatorySpecifications')?.value || '',
                    remark: document.getElementById('confirmatoryRemark')?.value || '',
                    sample_weight_description: document.getElementById('sampleWeightDescription')?.value || '',
                    sample_weight_units: document.getElementById('sampleWeightUnits')?.value || '',
                    sample_weight_gross: document.getElementById('sampleWeightGross')?.value || '',
                    sample_weight_taken: document.getElementById('sampleWeightTaken')?.value || '',
                    sample_weight_reserved: document.getElementById('sampleWeightReserved')?.value || '',
                    sample_taken: document.getElementById('sampleWeightTaken')?.value || '',
                    sample_reserved: document.getElementById('sampleWeightReserved')?.value || '',
                    sample_weight: {
                        description: document.getElementById('sampleWeightDescription')?.value || '',
                        units_packaging: document.getElementById('sampleWeightUnits')?.value || '',
                        gross_weight: document.getElementById('sampleWeightGross')?.value || '',
                        sample_taken: document.getElementById('sampleWeightTaken')?.value || '',
                        reserved_samples: document.getElementById('sampleWeightReserved')?.value || ''
                    }
                }
            };

            // AJAX call to store case in database for Head of Forensic Directorate review & approval
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            fetch('/cases', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || ''
                },
                body: JSON.stringify({
                    no_suspect: noSuspectMode ? 1 : 0,
                    case_number: caseRef,
                    linked_exhibit_ids: (typeof jQuery !== 'undefined' ? jQuery('#linkedUnassignedExhibitsSelect').val() : (document.getElementById('linkedUnassignedExhibitsSelect') ? Array.from(document.getElementById('linkedUnassignedExhibitsSelect').selectedOptions).map(o => o.value) : [])) || [],
                    link_suspect_to_locked_exhibit: document.getElementById('linkSuspectToLockedExhibitCheckbox')?.checked ? 1 : 0,
                    title: resolvedTitle,
                    unit_name: @json($userUnit),
                    operation_code_name: noSuspectMode ? null : (document.getElementById('operationTypeSelect')?.value || document.getElementById('operationCodeNameInput')?.value || null),
                    suspect_docket_mode: resolvedDocketMode,

                    // Suspect info
                    suspect_name: resolvedSuspectName,
                    suspect_alias: noSuspectMode ? null : alias,
                    ghana_card: noSuspectMode ? null : card,
                    gender: noSuspectMode ? null : gender,
                    dob: noSuspectMode ? null : dob,
                    age: noSuspectMode ? null : age,
                    height: noSuspectMode ? null : height,
                    eye_color: noSuspectMode ? null : eyeColor,
                    nationality: noSuspectMode ? null : (document.getElementById('nationalitySelect')?.value || document.getElementById('newArrestedNationality')?.value || 'Ghanaian'),
                    pob: noSuspectMode ? null : pob,
                    phone: noSuspectMode ? null : phone,
                    additional_phones: noSuspectMode ? [] : (typeof getAdditionalPhonesPayload === 'function' ? getAdditionalPhonesPayload() : []).map(p => p.number),
                    additional_phone_labels: noSuspectMode ? [] : (typeof getAdditionalPhonesPayload === 'function' ? getAdditionalPhonesPayload() : []).map(p => p.label),
                    phones: noSuspectMode ? [] : (typeof getAllSuspectPhonesPayload === 'function' ? getAllSuspectPhonesPayload() : []),
                    address: noSuspectMode ? null : address,

                    // Family & Marital
                    marital_status: noSuspectMode ? null : marital,
                    has_children: noSuspectMode ? false : hasChildren,
                    children_count: noSuspectMode ? null : childrenVal,
                    spouse_name: noSuspectMode ? null : spouseName,
                    spouse_phone: noSuspectMode ? null : spousePhone,
                    spouse_info: noSuspectMode ? null : spouseInfo,
                    partner_name: noSuspectMode ? null : partnerName,
                    partner_phone: noSuspectMode ? null : partnerPhone,
                    partner_info: noSuspectMode ? null : partnerInfo,
                    dependents: noSuspectMode ? 'N/A' : childrenText,

                    // Offence & Arrest Details
                    operation_code: noSuspectMode ? null : (document.getElementById('operationTypeSelect')?.value || ref),
                    raid_location: noSuspectMode ? (primarySeizure.seizure_location || 'Crime Scene') : raidLocation,
                    latitude: document.getElementById('incidentLatitude')?.value || null,
                    longitude: document.getElementById('incidentLongitude')?.value || null,
                    offense_description: noSuspectMode ? 'Seized exhibit logged without suspect.' : offense,
                    arrest_date: noSuspectMode ? (primarySeizure.seizure_date || null) : (document.getElementById('newArrestedDate')?.value || null),
                    arrest_time: noSuspectMode ? (primarySeizure.seizure_time || null) : (document.getElementById('newArrestedTime')?.value || null),
                    holding_facility: noSuspectMode ? null : facility,

                    // Seizures (Commodities)
                    has_seizures: noSuspectMode ? true : hasSeizures,
                    exhibit_category: (hasSeizures && primarySeizure.exhibit_category) ? primarySeizure.exhibit_category : null,
                    seizure_name: primarySeizure.seizure_name || null,
                    seizure_name_drug: primarySeizure.seizure_name_drug || null,
                    seizure_name_weapon: primarySeizure.seizure_name_weapon || null,
                    seizure_name_currency: primarySeizure.seizure_name_currency || null,
                    seizure_name_ammo: primarySeizure.seizure_name_ammo || null,
                    seizure_name_device: primarySeizure.seizure_name_device || null,
                    dev_model: primarySeizure.dev_model || null,
                    dev_serial_no: primarySeizure.dev_serial_no || null,
                    dev_imei1: primarySeizure.dev_imei1 || null,
                    dev_imei2: primarySeizure.dev_imei2 || null,
                    dev_mac_address: primarySeizure.dev_mac_address || null,
                    dev_storage: primarySeizure.dev_storage || null,
                    dev_colour: primarySeizure.dev_colour || null,
                    dev_marks: primarySeizure.dev_marks || null,
                    seizure_quantity: primarySeizure.seizure_quantity || null,
                    amount: primarySeizure.amount || ((primarySeizure.exhibit_category === 'Money' || primarySeizure.exhibit_category === 'Currency' || primarySeizure.exhibit_category === 'Cash') ? (primarySeizure.seizure_quantity || primarySeizure.seizure_value) : null),
                    seizure_packaging: primarySeizure.seizure_packaging || null,
                    seizure_weight: primarySeizure.seizure_weight || null,
                    seizure_unit: primarySeizure.seizure_unit || null,
                    seizure_value: primarySeizure.seizure_value || null,
                    seizure_concealment: primarySeizure.seizure_concealment || null,
                    seizure_date: primarySeizure.seizure_date || null,
                    seizure_time: primarySeizure.seizure_time || null,
                    seizure_location: primarySeizure.seizure_location || null,
                    seizure_officer: primarySeizure.seizure_officer || null,
                    source_of_exhibits: primarySeizure.source_of_exhibits || null,
                    investigating_officer: primarySeizure.investigating_officer || null,
                    source_requesting_unit: primarySeizure.source_requesting_unit || null,
                    seizure_reason: primarySeizure.seizure_reason || null,
                    exhibit_remark: primarySeizure.exhibit_remark || null,
                    exhibit_type: primarySeizure.exhibit_type || null,
                    drug_classification: primarySeizure.drug_classification || null,
                    valuation: valuationText,
                    exhibits_summary: seizureSummary,
                    seizures: seizuresList,
                    docket_data: docketPayload,
                    ammunition_details: primarySeizure.ammunition_details || null,

                    // Vehicle Specifications
                    veh_make: vehMake,
                    veh_model: vehModel,
                    veh_type: vehType,
                    veh_year: vehYear,
                    veh_colour: vehColour,
                    veh_reg_no: vehRegNo,
                    veh_vin: vehVIN,
                    veh_engine_no: vehEngineNo,
                    veh_country_reg: vehCountryReg,
                    veh_owner: vehOwner,
                    veh_driver: vehDriver,
                    veh_owner_contact: vehOwnerContact,
                    veh_docs_available: vehDocsAvailable,
                    veh_doc_ref: vehDocRef,
                    veh_condition: vehCondition,
                    veh_fuel: vehFuel,
                    veh_keys_recv: vehKeysRecv,
                    veh_key_count: vehKeyCount,
                    veh_damage: vehDamage,
                    veh_mods: vehMods,
                    veh_features: vehFeatures,

                    // Weapon Specifications
                    wpn_model: document.getElementById('wpnModel')?.value || null,
                    wpn_serial_no: document.getElementById('wpnSerialNo')?.value || null,
                    wpn_calibre: document.getElementById('wpnCalibre')?.value || null,
                    wpn_colour: document.getElementById('wpnColour')?.value || null,
                    wpn_size: document.getElementById('wpnSize')?.value || null,
                    wpn_marks: document.getElementById('wpnMarks')?.value || null,
                    wpn_location_found: document.getElementById('wpnLocationFound')?.value || null,
                    wpn_loaded_status: document.getElementById('wpnLoadedStatus')?.value || null,
                    wpn_condition: document.getElementById('wpnCondition')?.value || null,

                    // Photos
                    evidence_photos: seizedExhibitDataUrls,
                    mugshot_front: (!noSuspectMode && mugshotFront && mugshotFront.startsWith('data:')) ? mugshotFront : null,
                    mugshot_left: (!noSuspectMode && mugshotLeft && mugshotLeft.startsWith('data:')) ? mugshotLeft : null,
                    mugshot_right: (!noSuspectMode && mugshotRight && mugshotRight.startsWith('data:')) ? mugshotRight : null,

                    // Presumptive & Confirmatory / Sample Weight
                    sample_weight_description: document.getElementById('sampleWeightDescription')?.value || null,
                    sample_weight_units: document.getElementById('sampleWeightUnits')?.value || null,
                    sample_weight_gross: document.getElementById('sampleWeightGross')?.value || null,
                    sample_weight_taken: document.getElementById('sampleWeightTaken')?.value || null,
                    sample_weight_reserved: document.getElementById('sampleWeightReserved')?.value || null,
                    sample_taken: document.getElementById('sampleWeightTaken')?.value || null,
                    sample_reserved: document.getElementById('sampleWeightReserved')?.value || null,
                    confirmatory_substance_identification: document.getElementById('confirmatorySubstanceIdentification')?.value || null,
                    confirmatory_test_methods: document.getElementById('confirmatoryTestMethods')?.value || null,
                    confirmatory_specifications: document.getElementById('confirmatorySpecifications')?.value || null,
                    confirmatory_remark: document.getElementById('confirmatoryRemark')?.value || null,

                    // Presumptive Result & Legacy JSON docket data
                    presumptive_result: presumptiveVal,
                    docket_data: docketPayload,
                    priority: document.getElementById('casePrioritySelect')?.value || 'HIGH',
                    status: 'Under Multi-Unit Processing',
                    location: `${raidLocation} (${address})`,
                    description: `Offence: ${offense} | Raid Ref: ${ref} | Custody: ${facility}`,
                    arresting_officer: primarySeizure.seizure_officer || null,
                    submitting_officer: primarySeizure.seizure_officer || null,
                    confirm_duplicate: confirmDuplicate ? 1 : 0
                })
            })
            .then(async res => {
                if (res.status === 401) {
                    setSubmittingState(false);
                    alert('Your session has expired. Please log in again to continue.');
                    window.location.href = '/login?expired=1';
                    throw new Error('SESSION_EXPIRED');
                }
                const data = await res.json();
                if (res.status === 409) {
                    setSubmittingState(false);
                    showDuplicateCaseModal(data);
                    throw new Error('DUPLICATE_CASE_INTERCEPTED');
                }
                if (res.status === 429) {
                    setSubmittingState(false);
                    throw new Error(data.message || data.error || 'A submission for this case is already being processed. Please wait a moment.');
                }
                if (!res.ok) {
                    setSubmittingState(false);
                    let errMsg = data.message || 'Validation failed. Please check all required suspect details.';
                    if (data.errors) {
                        const detailList = Object.values(data.errors).flat().join('\n• ');
                        errMsg += '\n• ' + detailList;
                    }
                    throw new Error(errMsg);
                }
                return data;
            })
            .then(async data => {
                setSubmittingState(false);
                if (typeof uploadStagedSearchWarrantFile === 'function') {
                    await uploadStagedSearchWarrantFile(data);
                }
                const finalRef = (data && data.case && data.case.case_number) ? data.case.case_number : caseRef;
                const caseDbId = (data && data.case && data.case.id) ? data.case.id : finalRef;

                const tbody = document.getElementById('ArrestedRosterTableBody');
                if (tbody) {
                    const newRow = document.createElement('tr');
                    newRow.className = 'hover:bg-slate-50 transition-colors';
                    newRow.innerHTML = `
                        <td class="py-4 px-5">
                            <div class="font-bold text-slate-900 font-mono-code">${finalRef}</div>
                            <div class="text-[10px] text-slate-400 font-mono-code">${new Date().toISOString().slice(0, 10)}</div>
                        </td>
                        <td class="py-4 px-5">
                            <div class="font-bold text-slate-900 text-sm">${name} ${alias ? `"${alias}"` : ''}</div>
                            <div class="text-[10px] text-slate-500 font-mono-code">Card: ${card} | ${gender} (${age})</div>
                            <div class="text-[10px] text-slate-600 font-mono-code">DOB: ${dob} | Tel: ${phone}</div>
                        </td>
                        <td class="py-4 px-5">
                            <div class="font-semibold text-slate-800">${address}</div>
                            <div class="text-[10px] text-slate-500">Born: ${pob} | Status: ${marital} (${childrenText})</div>
                            ${partnerInfoHtml}
                        </td>
                        <td class="py-4 px-5 max-w-xs">
                            <div class="font-semibold text-slate-800">${ref} (${raidLocation})</div>
                            <div class="text-[10px] text-emerald-700 truncate font-semibold">${offense}</div>
                            ${seizureHtml}
                        </td>
                        <td class="py-4 px-5">
                            <div class="text-emerald-700 font-bold">Awaiting Sign-off</div>
                            <div class="text-[10px] text-slate-400">${facility}</div>
                        </td>
                        <td class="py-4 px-5">
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                Pending Directorate Approval
                            </span>
                        </td>
                        <td class="py-4 px-5 text-right space-x-2">
                            <a href="/Forensic_Head/staff/cases/${caseDbId}/review" class="px-3 py-1.5 bg-emerald-600 text-white hover:bg-emerald-700 rounded-xl text-[11px] font-bold shadow-2xs inline-block">Review Case</a>
                        </td>
                    `;
                    tbody.insertBefore(newRow, tbody.firstChild);
                }

                clearDocketFormDraft();
                if (typeof window.afterCaseSavedRefreshUnassignedSelector === 'function') {
                    window.afterCaseSavedRefreshUnassignedSelector();
                } else if (typeof window.refreshUnassignedExhibitsSelector === 'function') {
                    window.refreshUnassignedExhibitsSelector();
                }
                showSuccessPopover(finalRef);
            })
            .catch(err => {
                setSubmittingState(false);
                if (err.message === 'DUPLICATE_CASE_INTERCEPTED' || err.message === 'SESSION_EXPIRED') return;
                const msg = err.message || 'An error occurred while saving the case. Please enter all required suspect details.';
                const errItems = msg.split('\n• ').map(s => s.replace(/^\n/, '').trim()).filter(Boolean);
                showValidationErrorPopover(errItems);
            });
        }

        const DRAFT_KEY = 'arrest_docket_form_draft_v1';

        function saveDocketFormDraft() {
            const form = document.querySelector('form');
            if (!form) return;

            const draft = {};
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(el => {
                if (!el.id) return;
                if (el.type === 'checkbox') {
                    draft[el.id] = el.checked;
                } else if (el.type === 'radio') {
                    if (el.checked) draft[el.name || el.id] = el.value;
                } else if (el.type !== 'file' && el.type !== 'submit' && el.type !== 'button' && el.type !== 'reset') {
                    draft[el.id] = el.value;
                }
            });

            draft['_custom_labels'] = {
                selectedGenderLabel: document.getElementById('selectedGenderLabel')?.innerText,
                selectedGenderIcon: document.getElementById('selectedGenderIcon')?.innerText,
                selectedNationalityLabel: document.getElementById('selectedNationalityLabel')?.innerText,
                selectedNationalityBadge: document.getElementById('selectedNationalityBadge')?.innerText,
                selectedMaritalLabel: document.getElementById('selectedMaritalLabel')?.innerText,
                selectedCaseTypeLabel: document.getElementById('selectedCaseTypeLabel')?.innerText
            };

            // Always exclude evidence photos from auto-saved drafts so the section resets on page refresh
            draft['_additionalPhonesList'] = typeof getAdditionalPhonesPayload === 'function' ? getAdditionalPhonesPayload() : [];

            try {
                localStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
            } catch(e) {}
        }

        function handleDraftAndContinueLater() {
            saveDocketFormDraft();
            const notice = document.getElementById('docketDraftNotice');
            if (notice) notice.classList.remove('hidden');
            if (typeof showToast === 'function') {
                showToast('✔ Case docket saved as Draft. You can resume and continue later.');
            } else {
                alert('Case docket saved as Draft. You can resume and continue later.');
            }
        }

        function resetAllSeizurePhotos() {
            seizedExhibitDataUrls = [];
            renderSeizurePhotosGrid();
            const rootInputs = document.querySelectorAll('#fileSeizurePhoto, .fileSeizurePhoto, .file-seizure-photo');
            rootInputs.forEach(inp => { inp.value = ''; });
            const rootGrids = document.querySelectorAll('#seizurePhotosGrid, .seizurePhotosGrid, .seizure-photos-grid');
            rootGrids.forEach(g => {
                g.innerHTML = '';
                g.classList.add('hidden');
            });
            const rootBadges = document.querySelectorAll('#seizurePhotosCount, .seizurePhotosCount, .seizure-photos-count');
            rootBadges.forEach(bg => {
                bg.classList.add('hidden');
                bg.innerText = '0 Photos Uploaded';
            });

            try {
                const raw = localStorage.getItem(DRAFT_KEY);
                if (raw) {
                    const draft = JSON.parse(raw);
                    if (draft._seizedExhibitDataUrls) {
                        delete draft._seizedExhibitDataUrls;
                        localStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
                    }
                }
            } catch (e) {}
        }

        function restoreDocketFormDraft() {
            try {
                const raw = localStorage.getItem(DRAFT_KEY);
                if (!raw) return;

                const draft = JSON.parse(raw);
                let restoredCount = 0;

                // Purge any residual evidence photos from draft object
                delete draft._seizedExhibitDataUrls;

                Object.keys(draft).forEach(id => {
                    if (id === '_custom_labels' || id === '_seizedExhibitDataUrls' || id === '_seizurePhotosPerBlock') return;
                    if (id === 'caseEntryTypeSelect' || id === 'newArrestedGender' || id === 'nationalitySelect' || id === 'newArrestedNationality' || id === 'newArrestedMarital') return;
                    const el = document.getElementById(id);
                    if (!el) return;

                    if (el.type === 'checkbox') {
                        el.checked = !!draft[id];
                        if (draft[id]) restoredCount++;
                    } else if (el.type !== 'file' && el.type !== 'submit' && el.type !== 'button' && el.type !== 'reset') {
                        if (draft[id] !== undefined && draft[id] !== '') {
                            el.value = draft[id];
                            restoredCount++;
                        }
                    }
                });

                if (draft['drugSelect'] && window.jQuery && $.fn.select2) {
                    const savedDrug = draft['drugSelect'];
                    if ($('#drugSelect').length) {
                        if ($('#drugSelect').find("option[value='" + $.escapeSelector(savedDrug) + "']").length === 0) {
                            const newOption = new Option(savedDrug, savedDrug, true, true);
                            $('#drugSelect').append(newOption).trigger('change');
                        } else {
                            $('#drugSelect').val(savedDrug).trigger('change');
                        }
                    }
                }

                if (draft['weaponSelect'] && window.jQuery && $.fn.select2) {
                    const savedWeapon = draft['weaponSelect'];
                    if ($('#weaponSelect').length) {
                        if ($('#weaponSelect').find("option[value='" + $.escapeSelector(savedWeapon) + "']").length === 0) {
                            const newOption = new Option(savedWeapon, savedWeapon, true, true);
                            $('#weaponSelect').append(newOption).trigger('change');
                        } else {
                            $('#weaponSelect').val(savedWeapon).trigger('change');
                        }
                    }
                }

                toggleChildrenInput();
                toggleSeizuresContainer();
                if (typeof syncSections1To3OptionalState === 'function') syncSections1To3OptionalState();
                handleCaseEntryTypeChange();

                const maritalVal = document.getElementById('newArrestedMarital')?.value || '';
                handleMaritalChange(maritalVal);

                const seizureCat = document.getElementById('newSeizureCategory')?.value;
                if (seizureCat) updateCommodityField(seizureCat);

                calculateAgeFromDOB();

                if (draft['_additionalPhonesList'] && Array.isArray(draft['_additionalPhonesList'])) {
                    const phoneListEl = document.getElementById('suspectPhonesList');
                    if (phoneListEl) {
                        phoneListEl.querySelectorAll('.suspect-phone-row:not(:first-child)').forEach(r => r.remove());
                        draft['_additionalPhonesList'].forEach(item => {
                            if (typeof addSuspectPhoneRow === 'function') {
                                addSuspectPhoneRow(item.label, item.number);
                            }
                        });
                    }
                }

                if (typeof toggleSearchWarrantUploadSection === 'function') {
                    toggleSearchWarrantUploadSection(document.getElementById('operationTypeSelect')?.value || '');
                }

                if (restoredCount > 0) {
                    const notice = document.getElementById('docketDraftNotice');
                    if (notice) notice.classList.remove('hidden');
                }
            } catch(e) {}
        }

        function clearDocketFormDraft() {
            try {
                localStorage.removeItem(DRAFT_KEY);
            } catch(e) {}
        }

        function clearDocketFormDraftAndReset() {
            clearDocketFormDraft();
            if (typeof toggleSearchWarrantUploadSection === 'function') {
                toggleSearchWarrantUploadSection('');
            }
            const phoneListEl = document.getElementById('suspectPhonesList');
            if (phoneListEl) {
                phoneListEl.querySelectorAll('.suspect-phone-row:not(:first-child)').forEach(r => r.remove());
            }
            seizedExhibitDataUrls = [];
            renderSeizurePhotosGrid();
            const notice = document.getElementById('docketDraftNotice');
            if (notice) notice.classList.add('hidden');
            const form = document.querySelector('form');
            if (form) form.reset();
            const caseTitleReset = document.getElementById('caseTitleInput');
            if (caseTitleReset) {
                caseTitleReset.value = 'Republic vs ';
                delete caseTitleReset.dataset.userEdited;
            }
            const mInput = document.getElementById('newArrestedMarital');
            if (mInput) mInput.value = '';
            const mLabel = document.getElementById('selectedMaritalLabel');
            if (mLabel) {
                mLabel.innerText = 'Select Marital / Relationship Status';
                mLabel.className = "font-bold text-slate-500 text-xs";
            }
            handleMaritalChange('');
            toggleChildrenInput();
            toggleSeizuresContainer();
            if (typeof syncSections1To3OptionalState === 'function') syncSections1To3OptionalState();
        }

        let popoverTimer = null;

        function showSuccessPopover(ref) {
            clearDocketFormDraft();
            const modal = document.getElementById('successPopoverModal');
            const caseRefEl = document.getElementById('popoverCaseRef');
            if (caseRefEl) {
                caseRefEl.textContent = ref || '-';
            }
            if (modal) {
                modal.classList.remove('hidden');
            }

            if (popoverTimer) {
                clearTimeout(popoverTimer);
            }
            // Auto disappear after 1 minute (60,000ms)
            popoverTimer = setTimeout(function() {
                closeSuccessPopover();
            }, 60000);
        }

        function closeSuccessPopover() {
            if (popoverTimer) {
                clearTimeout(popoverTimer);
                popoverTimer = null;
            }
            clearDocketFormDraft();
            const modal = document.getElementById('successPopoverModal');
            if (modal) {
                modal.classList.add('hidden');
            }
            window.location.reload();
        }

        function showValidationErrorPopover(missingLabels) {
            const modal = document.getElementById('validationErrorModal');
            const list = document.getElementById('validationErrorList');
            if (!modal || !list) return;

            const itemsHtml = Array.isArray(missingLabels)
                ? missingLabels.map(label => `
                    <li class="flex items-center space-x-2.5 py-2 px-3 bg-white/80 hover:bg-white rounded-xl border border-red-200/70 shadow-xs transition-colors">
                        <span class="text-xs font-bold text-red-800 tracking-tight">${label}</span>
                    </li>
                `).join('')
                : `<li class="flex items-center space-x-2.5 py-2 px-3 bg-white/80 hover:bg-white rounded-xl border border-red-200/70 shadow-xs transition-colors"><span class="text-xs font-bold text-red-800 tracking-tight">${missingLabels}</span></li>`;

            list.innerHTML = itemsHtml;
            modal.classList.remove('hidden');
        }

        function closeValidationErrorPopover() {
            const modal = document.getElementById('validationErrorModal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        function closeAllDropdowns(exceptTarget = null) {
            const list = [
                { menu: 'caseEntryTypeDropdownMenu', chevron: 'caseTypeChevron' },
                { menu: 'operationTypeDropdownMenu', chevron: 'opTypeChevron' },
                { menu: 'priorityDropdownMenu', chevron: 'priorityChevron' },
                { menu: 'genderDropdownMenu', chevron: 'genderChevron' },
                { menu: 'nationalityDropdownMenu', chevron: 'nationalityChevron' },
                { menu: 'maritalDropdownMenu', chevron: 'maritalChevron' },
                { menu: 'exhibitDropdownMenu', chevron: 'exhibitChevron' },
                { menu: 'sidebarEventMenu', chevron: 'sidebarEventChevron' }
            ];
            list.forEach(item => {
                if (item.menu !== exceptTarget && document.getElementById(item.menu) !== exceptTarget) {
                    const m = document.getElementById(item.menu);
                    const c = document.getElementById(item.chevron);
                    if (m) m.classList.add('hidden');
                    if (c) c.classList.remove('rotate-180');
                }
            });
            document.querySelectorAll('.dyn-dropdown-menu').forEach(menu => {
                if (menu !== exceptTarget && (!menu.id || menu.id !== exceptTarget)) {
                    menu.classList.add('hidden');
                    const parent = menu.closest('.relative');
                    if (parent) {
                        const c = parent.querySelector('.dyn-chevron');
                        if (c) c.classList.remove('rotate-180');
                    }
                }
            });
        }

        function toggleCaseEntryTypeDropdown(e) {
            if (e) e.stopPropagation();
            const menu = document.getElementById('caseEntryTypeDropdownMenu');
            const chevron = document.getElementById('caseTypeChevron');
            if (!menu) return;
            const isHidden = menu.classList.contains('hidden');
            closeAllDropdowns('caseEntryTypeDropdownMenu');
            if (isHidden) {
                menu.classList.remove('hidden');
                if (chevron) chevron.classList.add('rotate-180');
            }
        }

        function selectCaseEntryType(val, label, e) {
            if (e) e.stopPropagation();
            const input = document.getElementById('caseEntryTypeSelect');
            if (input) input.value = val;
            const labelEl = document.getElementById('selectedCaseTypeLabel');
            if (labelEl) {
                labelEl.innerText = label;
                if (!val) {
                    labelEl.classList.remove('text-slate-900');
                    labelEl.classList.add('text-slate-500');
                } else {
                    labelEl.classList.remove('text-slate-500');
                    labelEl.classList.add('text-slate-900');
                }
            }
            closeAllDropdowns();
            handleCaseEntryTypeChange();
            saveDocketFormDraft();
        }

        function toggleOperationTypeDropdown(e) {
            if (e) e.stopPropagation();
            const menu = document.getElementById('operationTypeDropdownMenu');
            const chevron = document.getElementById('opTypeChevron');
            if (!menu) return;
            const isHidden = menu.classList.contains('hidden');
            closeAllDropdowns('operationTypeDropdownMenu');
            if (isHidden) {
                menu.classList.remove('hidden');
                if (chevron) chevron.classList.add('rotate-180');
            }
        }

        function selectOperationType(val, label, e) {
            if (e) e.stopPropagation();
            const hiddenInput = document.getElementById('operationTypeSelect');
            const labelEl = document.getElementById('selectedOpTypeLabel');
            
            if (hiddenInput) hiddenInput.value = val;
            if (labelEl) {
                labelEl.textContent = label;
                if (!val) {
                    labelEl.className = 'font-bold text-slate-500 text-xs';
                } else {
                    labelEl.className = 'font-extrabold text-slate-900 text-xs';
                }
            }
            
            closeAllDropdowns();
            if (typeof window.toggleSearchWarrantUploadSection === 'function') {
                window.toggleSearchWarrantUploadSection(val);
            }
            if (typeof window.handleCargoParcelOperationTypeSwitch === 'function') {
                window.handleCargoParcelOperationTypeSwitch(val);
            }
            if (typeof saveDocketFormDraft === 'function') {
                saveDocketFormDraft();
            }
        }

        function togglePriorityDropdown(e) {
            if (e) e.stopPropagation();
            const menu = document.getElementById('priorityDropdownMenu');
            const chevron = document.getElementById('priorityChevron');
            if (!menu) return;
            const isHidden = menu.classList.contains('hidden');
            closeAllDropdowns('priorityDropdownMenu');
            if (isHidden) {
                menu.classList.remove('hidden');
                if (chevron) chevron.classList.add('rotate-180');
            }
        }

        function selectPriority(val, label, dotClass, e) {
            if (e) e.stopPropagation();
            const hiddenInput = document.getElementById('casePrioritySelect');
            const labelEl = document.getElementById('selectedPriorityLabel');
            
            if (hiddenInput) hiddenInput.value = val;
            if (labelEl) {
                labelEl.textContent = label;
                if (!val) {
                    labelEl.className = 'font-bold text-slate-500 text-xs';
                } else {
                    labelEl.className = 'font-extrabold text-slate-900 text-xs';
                }
            }
            
            closeAllDropdowns();
            if (typeof saveDocketFormDraft === 'function') {
                saveDocketFormDraft();
            }
        }

        function toggleSidebarEventMenu(e) {
            if (e) e.stopPropagation();
            const menu = document.getElementById('sidebarEventMenu');
            const chevron = document.getElementById('sidebarEventChevron');
            if (!menu) return;
            const isHidden = menu.classList.contains('hidden');
            closeAllDropdowns('sidebarEventMenu');
            if (isHidden) {
                menu.classList.remove('hidden');
                if (chevron) chevron.classList.add('rotate-180');
            }
        }

        function toggleGenderDropdown(e) {
            if (e) e.stopPropagation();
            const menu = document.getElementById('genderDropdownMenu');
            const chevron = document.getElementById('genderChevron');
            if (!menu) return;
            const isHidden = menu.classList.contains('hidden');
            closeAllDropdowns('genderDropdownMenu');
            if (isHidden) {
                menu.classList.remove('hidden');
                if (chevron) chevron.classList.add('rotate-180');
            }
        }

        function selectGender(val, symbol, colorClasses, e) {
            if (e) e.stopPropagation();
            const input = document.getElementById('newArrestedGender');
            if (input) input.value = val;
            const label = document.getElementById('selectedGenderLabel');
            if (label) {
                label.innerText = val || 'Select Gender';
                if (!val) {
                    label.classList.remove('text-slate-900');
                    label.classList.add('text-slate-500');
                } else {
                    label.classList.remove('text-slate-500');
                    label.classList.add('text-slate-900');
                }
            }
            const icon = document.getElementById('selectedGenderIcon');
            if (icon) {
                icon.innerText = symbol;
                icon.className = `w-5 h-5 rounded-full ${colorClasses} flex items-center justify-center text-[10px] font-bold`;
            }
            closeAllDropdowns();
            saveDocketFormDraft();
        }

        function toggleNationalityDropdown(e) {
            if (e) e.stopPropagation();
            const menu = document.getElementById('nationalityDropdownMenu');
            const chevron = document.getElementById('nationalityChevron');
            if (!menu) return;
            const isHidden = menu.classList.contains('hidden');
            closeAllDropdowns('nationalityDropdownMenu');
            if (isHidden) {
                menu.classList.remove('hidden');
                if (chevron) chevron.classList.add('rotate-180');
                const search = document.getElementById('nationalitySearchInput');
                if (search) setTimeout(() => search.focus(), 50);
            }
        }

        function selectNationality(val, codeOrIcon, e) {
            if (e) e.stopPropagation();
            const input = document.getElementById('newArrestedNationality');
            if (input) input.value = val;
            const label = document.getElementById('selectedNationalityLabel');
            if (label) {
                label.innerText = val || 'Select Nationality';
                if (!val) {
                    label.className = "font-bold text-slate-500 text-xs";
                } else {
                    label.className = "font-bold text-slate-900 text-xs";
                }
            }
            const icon = document.getElementById('selectedNationalityIcon');
            if (icon) {
                icon.innerText = codeOrIcon || (val ? 'CUSTOM' : 'NAT');
                if (!val) {
                    icon.className = "w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-bold";
                } else {
                    icon.className = "w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-bold";
                }
            }
            closeAllDropdowns();
            saveDocketFormDraft();
        }

        function filterNationalities() {
            const rawVal = (document.getElementById('nationalitySearchInput')?.value || '').trim();
            const lowerQuery = rawVal.toLowerCase();
            const menu = document.getElementById('nationalityDropdownMenu');
            const options = document.querySelectorAll('.nat-option');
            options.forEach(opt => {
                const text = opt.innerText.toLowerCase();
                if (!lowerQuery || text.includes(lowerQuery)) {
                    opt.style.display = 'flex';
                } else {
                    opt.style.display = 'none';
                }
            });

            if (!menu) return;
            let customBtn = document.getElementById('nationalityCustomBtn');
            if (rawVal.length > 0) {
                if (!customBtn) {
                    customBtn = document.createElement('div');
                    customBtn.id = 'nationalityCustomBtn';
                    customBtn.className = 'p-1 border-b border-emerald-200 bg-emerald-50/50 mb-1';
                    const stickyBox = menu.querySelector('div.sticky');
                    if (stickyBox && stickyBox.nextSibling) {
                        menu.insertBefore(customBtn, stickyBox.nextSibling);
                    } else {
                        menu.insertBefore(customBtn, menu.firstChild);
                    }
                }
                const safeVal = rawVal.replace(/'/g, "\\'").replace(/"/g, '&quot;');
                customBtn.innerHTML = `
                    <button type="button" onclick="selectNationality('${safeVal}', 'CUSTOM', event)" class="w-full text-left px-3 py-2 text-xs font-extrabold text-emerald-900 bg-emerald-100 hover:bg-emerald-200 rounded-xl flex items-center justify-between transition-colors border border-emerald-300 shadow-2xs cursor-pointer">
                        <span class="truncate">+ Use Custom Entry: "${rawVal}"</span>
                        <span class="text-[9px] font-mono-code bg-emerald-700 text-white px-1.5 py-0.5 rounded font-bold uppercase shrink-0">CUSTOM</span>
                    </button>
                `;
                customBtn.style.display = 'block';
            } else if (customBtn) {
                customBtn.style.display = 'none';
            }
        }

        function handleNatSearchKeydown(e, isDynamic = false, inputEl = null) {
            if (e.key === 'Enter') {
                e.preventDefault();
                e.stopPropagation();
                if (isDynamic && inputEl) {
                    const rawVal = (inputEl.value || '').trim();
                    if (rawVal) {
                        selectDynamicNationality(inputEl, rawVal, 'CUSTOM', e);
                    }
                } else {
                    const rawVal = (document.getElementById('nationalitySearchInput')?.value || '').trim();
                    if (rawVal) {
                        selectNationality(rawVal, 'CUSTOM', e);
                    }
                }
            }
        }

        function toggleDynamicDropdown(triggerBtn, e = null) {
            if (e) e.stopPropagation();
            if (!triggerBtn) return;
            const parent = triggerBtn.closest('.relative');
            if (!parent) return;
            const menu = parent.querySelector('.dyn-dropdown-menu');
            const chevron = triggerBtn.querySelector('.dyn-chevron');
            if (!menu) return;
            const isHidden = menu.classList.contains('hidden');

            closeAllDropdowns(menu);

            if (isHidden) {
                menu.classList.remove('hidden');
                if (chevron) chevron.classList.add('rotate-180');
                const searchInput = menu.querySelector('input[type="text"]');
                if (searchInput) setTimeout(() => searchInput.focus(), 50);
            }
        }

        function selectDynamicGender(optionBtn, val, symbol, colorClasses, e) {
            if (e) e.stopPropagation();
            const parent = optionBtn.closest('.relative');
            if (!parent) return;
            const input = parent.querySelector('[data-field="gender"]');
            if (input) input.value = val;
            const label = parent.querySelector('.dyn-gender-label');
            if (label) {
                label.innerText = val || 'Select Gender';
                label.className = val ? 'dyn-gender-label font-bold text-slate-900 text-xs' : 'dyn-gender-label font-bold text-slate-500 text-xs';
            }
            const icon = parent.querySelector('.dyn-gender-icon');
            if (icon) {
                icon.innerText = symbol;
                icon.className = `dyn-gender-icon w-5 h-5 rounded-full ${colorClasses} flex items-center justify-center text-[10px] font-bold`;
            }
            const menu = parent.querySelector('.dyn-dropdown-menu');
            if (menu) menu.classList.add('hidden');
            const chevron = parent.querySelector('.dyn-chevron');
            if (chevron) chevron.classList.remove('rotate-180');
            saveDocketFormDraft();
        }

        function selectDynamicNationality(optionBtn, val, codeOrIcon, e) {
            if (e) e.stopPropagation();
            const parent = optionBtn.closest('.relative');
            if (!parent) return;
            const input = parent.querySelector('[data-field="nationality"]');
            if (input) input.value = val;
            const label = parent.querySelector('.dyn-nat-label');
            if (label) {
                label.innerText = val || 'Select Nationality';
                label.className = val ? 'dyn-nat-label font-bold text-slate-900 text-xs' : 'dyn-nat-label font-bold text-slate-500 text-xs';
            }
            const icon = parent.querySelector('.dyn-nat-icon');
            if (icon) {
                icon.innerText = codeOrIcon || (val ? 'CUSTOM' : 'NAT');
                icon.className = val ? 'dyn-nat-icon w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-bold' : 'dyn-nat-icon w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-bold';
            }
            const menu = parent.querySelector('.dyn-dropdown-menu');
            if (menu) menu.classList.add('hidden');
            const chevron = parent.querySelector('.dyn-chevron');
            if (chevron) chevron.classList.remove('rotate-180');
            saveDocketFormDraft();
        }

        function selectDynamicMarital(optionBtn, val, codeOrIcon, colorClasses, e) {
            if (e) e.stopPropagation();
            const parent = optionBtn.closest('.relative');
            if (!parent) return;
            const input = parent.querySelector('[data-field="marital_status"]');
            if (input) {
                input.value = val;
                handleDynamicMaritalChange(input);
            }
            const label = parent.querySelector('.dyn-marital-label');
            if (label) {
                label.innerText = val || 'Select Marital / Relationship Status';
                label.className = val ? 'dyn-marital-label font-bold text-slate-900 text-xs' : 'dyn-marital-label font-bold text-slate-500 text-xs';
            }
            const icon = parent.querySelector('.dyn-marital-icon');
            if (icon) {
                icon.innerText = codeOrIcon || 'MS';
                icon.className = `dyn-marital-icon w-5 h-5 rounded-full ${colorClasses || (val ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600')} flex items-center justify-center text-[10px] font-bold`;
            }
            const menu = parent.querySelector('.dyn-dropdown-menu');
            if (menu) menu.classList.add('hidden');
            const chevron = parent.querySelector('.dyn-chevron');
            if (chevron) chevron.classList.remove('rotate-180');
            saveDocketFormDraft();
        }

        function filterDynamicNatOptions(inputEl) {
            const rawVal = (inputEl.value || '').trim();
            const lowerQuery = rawVal.toLowerCase();
            const menu = inputEl.closest('.dyn-dropdown-menu');
            if (!menu) return;

            const options = menu.querySelectorAll('.nat-dyn-option');
            options.forEach(opt => {
                const text = opt.innerText.toLowerCase();
                if (!lowerQuery || text.includes(lowerQuery)) {
                    opt.style.display = 'flex';
                } else {
                    opt.style.display = 'none';
                }
            });

            let customBtn = menu.querySelector('.nat-dyn-custom-btn');
            if (rawVal.length > 0) {
                if (!customBtn) {
                    customBtn = document.createElement('div');
                    customBtn.className = 'nat-dyn-custom-btn p-1 border-b border-emerald-200 bg-emerald-50/50 mb-1';
                    const stickyBox = menu.querySelector('div.sticky');
                    if (stickyBox && stickyBox.nextSibling) {
                        menu.insertBefore(customBtn, stickyBox.nextSibling);
                    } else {
                        menu.insertBefore(customBtn, menu.firstChild);
                    }
                }
                const safeVal = rawVal.replace(/'/g, "\\'").replace(/"/g, '&quot;');
                customBtn.innerHTML = `
                    <button type="button" onclick="selectDynamicNationality(this, '${safeVal}', 'CUSTOM', event)" class="w-full text-left px-3 py-2 text-xs font-extrabold text-emerald-900 bg-emerald-100 hover:bg-emerald-200 rounded-xl flex items-center justify-between transition-colors border border-emerald-300 shadow-2xs cursor-pointer">
                        <span class="truncate">+ Use Custom Entry: "${rawVal}"</span>
                        <span class="text-[9px] font-mono-code bg-emerald-700 text-white px-1.5 py-0.5 rounded font-bold uppercase shrink-0">CUSTOM</span>
                    </button>
                `;
                customBtn.style.display = 'block';
            } else if (customBtn) {
                customBtn.style.display = 'none';
            }
        }

        function toggleChildrenInput() {
            const checkbox = document.getElementById('hasChildrenCheckbox');
            const container = document.getElementById('childrenInputContainer');
            const input = document.getElementById('newArrestedChildren');

            if (checkbox && checkbox.checked) {
                if (container) container.classList.remove('hidden');
                if (input && (!input.value || input.value === '0')) {
                    input.value = '1';
                }
            } else {
                if (container) container.classList.add('hidden');
                if (input) input.value = '0';
            }
            saveDocketFormDraft();
        }

        function toggleSeizuresContainer() {
            const chk = document.getElementById('hasSeizuresCheckbox');
            const container = document.getElementById('seizuresDetailsContainer');
            if (chk && container) {
                if (chk.checked) {
                    container.classList.remove('hidden');
                } else {
                    container.classList.add('hidden');
                }
            }
            saveDocketFormDraft();
            if (typeof syncSection4ExhibitsToLabSection === 'function') {
                syncSection4ExhibitsToLabSection();
            }
        }

        function toggleMaritalDropdown(e) {
            if (e) e.stopPropagation();
            const menu = document.getElementById('maritalDropdownMenu');
            const chevron = document.getElementById('maritalChevron');
            if (!menu) return;
            const isHidden = menu.classList.contains('hidden');
            closeAllDropdowns('maritalDropdownMenu');
            if (isHidden) {
                menu.classList.remove('hidden');
                if (chevron) chevron.classList.add('rotate-180');
            }
        }

        function selectMaritalStatus(val, codeOrIcon, colorClasses, e) {
            if (e) e.stopPropagation();
            const input = document.getElementById('newArrestedMarital');
            if (input) input.value = val;
            const label = document.getElementById('selectedMaritalLabel');
            if (label) {
                label.innerText = val || 'Select Marital / Relationship Status';
                if (!val) {
                    label.className = "font-bold text-slate-500 text-xs";
                } else {
                    label.className = "font-bold text-slate-900 text-xs";
                }
            }
            const icon = document.getElementById('selectedMaritalIcon');
            if (icon) {
                icon.innerText = codeOrIcon || 'MS';
                icon.className = `w-5 h-5 rounded-full ${colorClasses || (val ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600')} flex items-center justify-center text-[10px] font-bold`;
            }
            closeAllDropdowns();
            handleMaritalChange(val);
            saveDocketFormDraft();
        }

        function handleMaritalChange(val) {
            const container = document.getElementById('relationshipDetailsBox');
            const title = document.getElementById('relationshipTitle');
            const subtitle = document.getElementById('relationshipSubtitle');
            const spouseCard = document.getElementById('spouseFieldsCard');
            const partnerCard = document.getElementById('partnerFieldsCard');

            if (!container) return;

            if (val === 'Married') {
                container.classList.remove('hidden');
                if (title) title.innerText = 'Spouse Information (Marriage Record)';
                if (subtitle) subtitle.innerText = 'Mandatory record for suspect legal spouse identity, phone contact, and occupation';
                if (spouseCard) spouseCard.classList.remove('hidden');
                if (partnerCard) partnerCard.classList.add('hidden');
            } else if (val === 'Dating / In Relationship') {
                container.classList.remove('hidden');
                if (title) title.innerText = 'Dating Partner Information (Relationship Record)';
                if (subtitle) subtitle.innerText = 'Record for suspect dating partner full name, phone contact, and residence';
                if (spouseCard) spouseCard.classList.add('hidden');
                if (partnerCard) partnerCard.classList.remove('hidden');
            } else {
                container.classList.add('hidden');
            }
        }

        function getSeizureBlockFromEvent(e) {
            if (!e) return document.querySelector('.seizure-block') || document;
            const target = e.target || e.srcElement;
            if (target && target.closest) {
                return target.closest('.seizure-block') || document.querySelector('.seizure-block') || document;
            }
            return document.querySelector('.seizure-block') || document;
        }

        function addSeizureBlock() {
            const container = document.getElementById('seizuresListContainer');
            if (!container) return;

            const firstBlock = container.querySelector('.seizure-block');
            if (!firstBlock) return;

            const newIndex = container.querySelectorAll('.seizure-block').length + 1;
            const clone = firstBlock.cloneNode(true);

            clone.setAttribute('data-seizure-index', newIndex);

            // Update title
            const titleEl = clone.querySelector('.seizure-title');
            if (titleEl) titleEl.innerText = `Seized Item #${newIndex}`;

            // Reset inputs in clone
            clone.querySelectorAll('input[type="text"], input[type="number"], input[type="date"], input[type="time"], textarea').forEach(input => {
                input.value = '';
            });
            clone.querySelectorAll('input[type="hidden"]').forEach(input => {
                input.value = '';
            });
            clone.querySelectorAll('select').forEach(select => {
                select.selectedIndex = 0;
            });
            clone.querySelector('.seizure-associated-person-select')?.removeAttribute('data-user-selected');

            // Hide all specialized wrappers in clone initially
            const drugWrapper = clone.querySelector('#drugSelectWrapper');
            const weaponWrapper = clone.querySelector('#weaponSelectWrapper');
            const currencyWrapper = clone.querySelector('#currencySelectWrapper');
            const ammoWrapper = clone.querySelector('#ammoSelectWrapper');
            const deviceWrapper = clone.querySelector('#deviceSelectWrapper');
            const deviceFieldsWrapper = clone.querySelector('#deviceFieldsWrapper');
            const vehicleFieldsWrapper = clone.querySelector('#vehicleFieldsWrapper');
            const weaponFieldsWrapper = clone.querySelector('#weaponFieldsWrapper');
            const ammoFieldsWrapper = clone.querySelector('#ammoFieldsWrapper');

            // Hide all dropdown panels in clone
            clone.querySelectorAll('#exhibitListPanel, #drugListPanel, #weaponListPanel, #currencyListPanel, #ammoListPanel, #deviceListPanel, #pkgListPanel, #unitListPanel').forEach(panel => {
                panel.classList.add('hidden');
            });

            if (drugWrapper) drugWrapper.classList.add('hidden');
            if (weaponWrapper) weaponWrapper.classList.add('hidden');
            if (currencyWrapper) currencyWrapper.classList.add('hidden');
            if (ammoWrapper) ammoWrapper.classList.add('hidden');
            if (deviceWrapper) deviceWrapper.classList.add('hidden');
            if (deviceFieldsWrapper) deviceFieldsWrapper.classList.add('hidden');
            if (vehicleFieldsWrapper) vehicleFieldsWrapper.classList.add('hidden');
            if (weaponFieldsWrapper) weaponFieldsWrapper.classList.add('hidden');
            if (ammoFieldsWrapper) ammoFieldsWrapper.classList.add('hidden');

            const commodityTextWrapper = clone.querySelector('#commodityTextWrapper');
            if (commodityTextWrapper) commodityTextWrapper.classList.remove('hidden');

            clone._seizurePhotos = [];
            const clonePhotoGrid = clone.querySelector('.seizurePhotosGrid') || clone.querySelector('#seizurePhotosGrid');
            if (clonePhotoGrid) {
                clonePhotoGrid.innerHTML = '';
                clonePhotoGrid.classList.add('hidden');
            }
            const clonePhotoCount = clone.querySelector('.seizure-photos-count') || clone.querySelector('#seizurePhotosCount');
            if (clonePhotoCount) {
                clonePhotoCount.innerText = '0 Photos Uploaded';
                clonePhotoCount.classList.add('hidden');
            }
            const cloneFileInput = clone.querySelector('.fileSeizurePhoto') || clone.querySelector('#fileSeizurePhoto');
            if (cloneFileInput) cloneFileInput.value = '';

            container.appendChild(clone);
            updateSeizureBlockNumbers();
            if (typeof syncSuspectDropdownsForExhibits === 'function') {
                syncSuspectDropdownsForExhibits();
            }
            if (typeof syncSection4ExhibitsToLabSection === 'function') {
                syncSection4ExhibitsToLabSection();
            }
            if (typeof window.syncSeizureOwnershipBoxes === 'function') {
                window.syncSeizureOwnershipBoxes();
            }
        }

        function removeSeizureBlock(btn) {
            const block = btn ? btn.closest('.seizure-block') : null;
            const container = document.getElementById('seizuresListContainer');
            if (block && container && container.querySelectorAll('.seizure-block').length > 1) {
                block.remove();
                updateSeizureBlockNumbers();
                if (typeof syncSuspectDropdownsForExhibits === 'function') {
                    syncSuspectDropdownsForExhibits();
                }
                if (typeof syncSection4ExhibitsToLabSection === 'function') {
                    syncSection4ExhibitsToLabSection();
                }
            }
        }

        function updateSeizureBlockNumbers() {
            const blocks = document.querySelectorAll('.seizure-block');
            blocks.forEach((block, index) => {
                const num = index + 1;
                block.setAttribute('data-seizure-index', num);
                const titleEl = block.querySelector('.seizure-title');
                const numberBadge = block.querySelector('.seizure-number-badge');
                if (titleEl) titleEl.innerText = `Seized Item #${num}`;
                if (numberBadge) numberBadge.innerText = num;

                const removeBtn = block.querySelector('.remove-seizure-btn');
                if (removeBtn) {
                    if (blocks.length > 1) {
                        removeBtn.classList.remove('hidden');
                    } else {
                        removeBtn.classList.add('hidden');
                    }
                }
            });
        }

        function toggleUnitDropdown(e) {
            if (e) e.stopPropagation();
            const root = getSeizureBlockFromEvent(e);
            const panel = root.querySelector('#unitListPanel');
            if (panel) panel.classList.toggle('hidden');
        }

        function selectUnit(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root   = getSeizureBlockFromEvent(e);
            const hidden  = root.querySelector('#newSeizureUnit');
            const display = root.querySelector('#unitSearchInput');
            const panel   = root.querySelector('#unitListPanel');
            if (hidden)  hidden.value  = val;
            if (display) display.value = val;
            if (panel)   panel.classList.add('hidden');
            if (typeof syncSection4ExhibitsToLabSection === 'function') {
                syncSection4ExhibitsToLabSection();
            }
        }

        // Generic Seizure Block Panel Opener Helper
        function openSeizurePanel(el, panelSelector) {
            if (el && el.removeAttribute) el.removeAttribute('readonly');
            const block = el ? el.closest('.seizure-block') : null;
            if (block) {
                document.querySelectorAll('.seizure-block').forEach(b => b.style.zIndex = '1');
                block.style.zIndex = '99999';
            }
            const panel = block ? block.querySelector(panelSelector) : document.querySelector(panelSelector);
            if (panel) panel.classList.remove('hidden');
        }

        function openExhibitListPanel(el) { openSeizurePanel(el, '#exhibitListPanel'); }
        function openDrugListPanel(el) { openSeizurePanel(el, '#drugListPanel'); }
        function openWeaponListPanel(el) { openSeizurePanel(el, '#weaponListPanel'); }
        function openCurrencyListPanel(el) { openSeizurePanel(el, '#currencyListPanel'); }
        function openAmmoListPanel(el) { openSeizurePanel(el, '#ammoListPanel'); }
        function openDeviceListPanel(el) { openSeizurePanel(el, '#deviceListPanel'); }
        function openPkgListPanel(el) { openSeizurePanel(el, '#pkgListPanel'); }
        function openUnitListPanel(el) { openSeizurePanel(el, '#unitListPanel'); }
        function openVehTypeListPanel(el) { openSeizurePanel(el, '#vehTypeListPanel'); }

        function filterUnitList(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#unitSearchInput')?.value || '';
            syncUnitCustomInput(rawVal, root);
            filterUnitListByQuery(rawVal.toLowerCase(), root);
        }

        function filterUnitListInner(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#unitSearchInner')?.value || '';
            syncUnitCustomInput(rawVal, root);
            filterUnitListByQuery(rawVal.toLowerCase(), root);
        }

        function syncUnitCustomInput(val, rootEl) {
            const root = rootEl || document;
            const hidden = root.querySelector('#newSeizureUnit');
            if (hidden) hidden.value = val;
        }

        function filterUnitListByQuery(q, rootEl) {
            const root = rootEl || document;
            const list = root.querySelector('#unitList');
            if (!list) return;

            let customLi = list.querySelector('#unitCustomItem');
            const trimmed = (q || '').trim();
            if (trimmed.length > 0) {
                if (!customLi) {
                    customLi = document.createElement('li');
                    customLi.id = 'unitCustomItem';
                    customLi.className = 'px-1.5 py-1 mb-1 border-b border-emerald-100';
                    list.insertBefore(customLi, list.firstChild);
                }
                const safeVal = trimmed.replace(/'/g, "\\'");
                customLi.innerHTML = `
                    <button type="button" onclick="selectUnit('${safeVal}', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-xl flex items-center justify-between transition-colors border border-emerald-200 cursor-pointer">
                        <span>+ Use Custom Entry: "${trimmed}"</span>
                        <span class="text-[10px] font-mono-code bg-emerald-200 text-emerald-900 px-1.5 py-0.5 rounded font-bold">CUSTOM</span>
                    </button>
                `;
                customLi.style.display = '';
            } else if (customLi) {
                customLi.style.display = 'none';
            }

            const items = list.querySelectorAll('.unit-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        function selectPackagingType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root   = getSeizureBlockFromEvent(e);
            const hidden  = root.querySelector('#newSeizurePackaging');
            const display = root.querySelector('#pkgSearchInput');
            const panel   = root.querySelector('#pkgListPanel');
            if (hidden)  hidden.value  = val;
            if (display) display.value = val;
            if (panel)   panel.classList.add('hidden');
            if (typeof syncSection4ExhibitsToLabSection === 'function') {
                syncSection4ExhibitsToLabSection();
            }
        }

        function filterPackagingList(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#pkgSearchInput')?.value || '';
            syncPackagingCustomInput(rawVal, root);
            filterPackagingListByQuery(rawVal.toLowerCase(), root);
        }

        function filterPackagingListInner(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#pkgSearchInner')?.value || '';
            syncPackagingCustomInput(rawVal, root);
            filterPackagingListByQuery(rawVal.toLowerCase(), root);
        }

        function syncPackagingCustomInput(val, rootEl) {
            const root = rootEl || document;
            const hidden = root.querySelector('#newSeizurePackaging');
            if (hidden) hidden.value = val;
        }

        function filterPackagingListByQuery(q, rootEl) {
            const root = rootEl || document;
            const list = root.querySelector('#pkgList');
            if (!list) return;

            let customLi = list.querySelector('#pkgCustomItem');
            const trimmed = (q || '').trim();
            if (trimmed.length > 0) {
                if (!customLi) {
                    customLi = document.createElement('li');
                    customLi.id = 'pkgCustomItem';
                    customLi.className = 'px-1.5 py-1 mb-1 border-b border-emerald-100';
                    list.insertBefore(customLi, list.firstChild);
                }
                const safeVal = trimmed.replace(/'/g, "\\'");
                customLi.innerHTML = `
                    <button type="button" onclick="selectPackagingType('${safeVal}', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-xl flex items-center justify-between transition-colors border border-emerald-200 cursor-pointer">
                        <span>+ Use Custom Entry: "${trimmed}"</span>
                        <span class="text-[10px] font-mono-code bg-emerald-200 text-emerald-900 px-1.5 py-0.5 rounded font-bold">CUSTOM</span>
                    </button>
                `;
                customLi.style.display = '';
            } else if (customLi) {
                customLi.style.display = 'none';
            }

            const items = list.querySelectorAll('.pkg-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        function selectExhibitCategory(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root = getSeizureBlockFromEvent(e);
            const input   = root.querySelector('#newSeizureCategory');
            const display = root.querySelector('#exhibitSearchInput');
            const panel   = root.querySelector('#exhibitListPanel');
            if (input)   input.value = val;
            if (display) { display.value = val; display.removeAttribute('readonly'); }
            if (panel)   panel.classList.add('hidden');
            updateCommodityField(val, root);

            const exhTypeSelect = root.querySelector('.exhibit-type-select');
            const drugClsSelect = root.querySelector('.drug-classification-select');
            if (exhTypeSelect && !exhTypeSelect.value) {
                exhTypeSelect.value = (val === 'Drugs') ? 'Drug Exhibit' : 'Non-Drug Exhibit';
            }
            if (drugClsSelect && val !== 'Drugs' && (!drugClsSelect.value || drugClsSelect.value === 'N/A')) {
                drugClsSelect.value = 'N/A';
            }

            // Focus corresponding commodity input so user can immediately type new data
            setTimeout(() => {
                let targetInput = null;
                if (val === 'Drugs') targetInput = root.querySelector('#drugSearchInput');
                else if (val === 'Weapons') targetInput = root.querySelector('#weaponSearchInput');
                else if (val === 'Money') targetInput = root.querySelector('#currencySearchInput');
                else if (val === 'Ammunition') targetInput = root.querySelector('#ammoSearchInput');
                else if (val === 'Electronic Devices') targetInput = root.querySelector('#deviceSearchInput');
                else targetInput = root.querySelector('#newSeizureName');

                if (targetInput) {
                    targetInput.removeAttribute('readonly');
                    targetInput.focus();
                }
            }, 50);

            if (typeof syncSection4ExhibitsToLabSection === 'function') {
                syncSection4ExhibitsToLabSection();
            }
        }

        function filterExhibitList(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#exhibitSearchInput')?.value || '';
            const categoryInput = root.querySelector('#newSeizureCategory');
            if (categoryInput) categoryInput.value = rawVal;
            filterExhibitListByQuery(rawVal.toLowerCase(), root);
        }

        function filterExhibitListInner(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#exhibitSearchInner')?.value || '';
            const categoryInput = root.querySelector('#newSeizureCategory');
            if (categoryInput) categoryInput.value = rawVal;
            filterExhibitListByQuery(rawVal.toLowerCase(), root);
        }

        function filterExhibitListByQuery(q, rootEl) {
            const root = rootEl || document;
            const list = root.querySelector('#exhibitList');
            if (!list) return;

            let customLi = list.querySelector('#exhibitCustomItem');
            const trimmed = (q || '').trim();
            if (trimmed.length > 0) {
                if (!customLi) {
                    customLi = document.createElement('li');
                    customLi.id = 'exhibitCustomItem';
                    customLi.className = 'px-1.5 py-1 mb-1 border-b border-emerald-100';
                    list.insertBefore(customLi, list.firstChild);
                }
                const safeVal = trimmed.replace(/'/g, "\\'");
                customLi.innerHTML = `
                    <button type="button" onclick="selectExhibitCategory('${safeVal}', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-xl flex items-center justify-between transition-colors border border-emerald-200 cursor-pointer">
                        <span>+ Use Custom Category: "${trimmed}"</span>
                        <span class="text-[10px] font-mono-code bg-emerald-200 text-emerald-900 px-1.5 py-0.5 rounded font-bold">CUSTOM</span>
                    </button>
                `;
                customLi.style.display = '';
            } else if (customLi) {
                customLi.style.display = 'none';
            }

            const items = list.querySelectorAll('.exhibit-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        function handleCommoditySearchKeydown(e, el, type) {
            if (e.key === 'Enter') {
                e.preventDefault();
                e.stopPropagation();
                const val = (el.value || '').trim();
                if (val) {
                    if (type === 'drug') selectDrugType(val, e);
                    else if (type === 'weapon') selectWeaponType(val, e);
                    else if (type === 'currency') selectCurrencyType(val, e);
                    else if (type === 'ammo') selectAmmoType(val, e);
                    else if (type === 'device') selectDeviceType(val, e);
                }
            }
        }

        function toggleAmmoOtherCondition(el) {
            const root = el ? (el.closest('.seizure-block') || document) : document;
            const wrapper = root.querySelector('#ammoOtherConditionWrapper');
            if (wrapper) {
                if (el && el.value === 'Other') {
                    wrapper.classList.remove('hidden');
                } else {
                    wrapper.classList.add('hidden');
                }
            }
        }

        function updateCommodityField(category, rootEl) {
            const root = rootEl || document.querySelector('.seizure-block') || document;
            const textWrapper     = root.querySelector('#commodityTextWrapper');
            const drugWrapper     = root.querySelector('#drugSelectWrapper');
            const weaponWrapper   = root.querySelector('#weaponSelectWrapper');
            const currencyWrapper = root.querySelector('#currencySelectWrapper');
            const ammoWrapper     = root.querySelector('#ammoSelectWrapper');
            const deviceWrapper   = root.querySelector('#deviceSelectWrapper');
            const deviceFieldsWrapper = root.querySelector('#deviceFieldsWrapper');
            const vehicleWrapper  = root.querySelector('#vehicleFieldsWrapper');
            const weaponFieldsWrapper = root.querySelector('#weaponFieldsWrapper');
            const ammoFieldsWrapper = root.querySelector('#ammoFieldsWrapper');
            const textInput       = root.querySelector('#newSeizureName');
            const drugHidden      = root.querySelector('#newSeizureNameDrug');
            const weaponHidden    = root.querySelector('#newSeizureNameWeapon');
            const currencyHidden  = root.querySelector('#newSeizureNameCurrency');
            const ammoHidden      = root.querySelector('#newSeizureNameAmmo');
            const deviceHidden    = root.querySelector('#newSeizureNameDevice');
            const drugSearch      = root.querySelector('#drugSearchInput');
            const weaponSearch    = root.querySelector('#weaponSearchInput');
            const currencySearch  = root.querySelector('#currencySearchInput');
            const ammoSearch      = root.querySelector('#ammoSearchInput');
            const deviceSearch    = root.querySelector('#deviceSearchInput');
            const quantityLabel   = root.querySelector('#quantityLabel');

            // Hide all first
            if (textWrapper)         textWrapper.classList.add('hidden');
            if (drugWrapper)         drugWrapper.classList.add('hidden');
            if (weaponWrapper)       weaponWrapper.classList.add('hidden');
            if (currencyWrapper)     currencyWrapper.classList.add('hidden');
            if (ammoWrapper)         ammoWrapper.classList.add('hidden');
            if (deviceWrapper)       deviceWrapper.classList.add('hidden');
            if (deviceFieldsWrapper) deviceFieldsWrapper.classList.add('hidden');
            if (vehicleWrapper)      vehicleWrapper.classList.add('hidden');
            if (weaponFieldsWrapper) weaponFieldsWrapper.classList.add('hidden');
            if (ammoFieldsWrapper)   ammoFieldsWrapper.classList.add('hidden');

            const otherDescriptionWrapper = root.querySelector('#otherExhibitDescriptionWrapper') || root.querySelector('.other-exhibit-description-wrapper');
            const otherDescriptionInput   = root.querySelector('#newOtherExhibitDescription') || root.querySelector('.other-exhibit-description-input');
            if (otherDescriptionWrapper) otherDescriptionWrapper.classList.add('hidden');
            if (otherDescriptionInput) otherDescriptionInput.removeAttribute('required');

            [textInput, drugHidden, weaponHidden, currencyHidden, ammoHidden, deviceHidden, drugSearch, weaponSearch, currencySearch, ammoSearch, deviceSearch].forEach(el => {
                if (el) {
                    el.removeAttribute('required');
                    if (el.removeAttribute) el.removeAttribute('readonly');
                }
            });

            if (category === 'Drugs') {
                if (quantityLabel) quantityLabel.innerText = 'Quantity';
                if (drugWrapper) drugWrapper.classList.remove('hidden');
                if (drugSearch)  { drugSearch.setAttribute('required', 'required'); drugSearch.placeholder = 'Select or type custom drug name...'; }
            } else if (category === 'Weapons') {
                if (quantityLabel) quantityLabel.innerText = 'Quantity';
                if (weaponWrapper) weaponWrapper.classList.remove('hidden');
                if (weaponFieldsWrapper) weaponFieldsWrapper.classList.remove('hidden');
                if (weaponSearch)  { weaponSearch.setAttribute('required', 'required'); weaponSearch.placeholder = 'Select or type custom weapon type...'; }
            } else if (category === 'Money') {
                if (quantityLabel) quantityLabel.innerText = 'Amount';
                if (currencyWrapper) currencyWrapper.classList.remove('hidden');
                if (currencySearch)  { currencySearch.setAttribute('required', 'required'); currencySearch.placeholder = 'Select or type custom currency...'; }
            } else if (category === 'Ammunition') {
                if (quantityLabel) quantityLabel.innerText = 'Quantity / Rounds';
                if (ammoWrapper) ammoWrapper.classList.remove('hidden');
                if (ammoFieldsWrapper) ammoFieldsWrapper.classList.remove('hidden');
                if (ammoSearch)  { ammoSearch.setAttribute('required', 'required'); ammoSearch.placeholder = 'Select or type custom ammunition type...'; }
            } else if (category === 'Electronic Devices') {
                if (quantityLabel) quantityLabel.innerText = 'Quantity / Units';
                if (deviceWrapper) deviceWrapper.classList.remove('hidden');
                if (deviceSearch)  { deviceSearch.setAttribute('required', 'required'); deviceSearch.placeholder = 'Select or type custom electronic device type...'; }
            } else {
                if (quantityLabel) quantityLabel.innerText = 'Quantity';
                if (textWrapper) textWrapper.classList.remove('hidden');
                if (textInput)   textInput.setAttribute('required', 'required');
                const placeholders = {
                    'Precious Stones':     'e.g. Diamonds, Gemstones, Gold Bars / Bullion (24K)',
                    'Gold':                'e.g. Gold Bars / Bullion (24K)',
                    'Vehicles':            'e.g. Toyota Hilux (Unregistered)',
                    'Other Exhibits':      'e.g. Hardware Devices / Illegal Tools'
                };
                if (textInput) textInput.placeholder = placeholders[category] || 'e.g. Primary Commodity Name';

                if (otherDescriptionWrapper) {
                    const isOther = category === 'Other Exhibits' || category === 'Other' || category === 'Other Contraband' || (category && category.toLowerCase().includes('other'));
                    if (isOther) {
                        otherDescriptionWrapper.classList.remove('hidden');
                        if (otherDescriptionInput) {
                            otherDescriptionInput.setAttribute('required', 'required');
                        }
                    }
                }

                if (category === 'Vehicles' && vehicleWrapper) {
                    vehicleWrapper.classList.remove('hidden');
                }
            }

            const isLabUser = false;
            const isDrugCase = {{ !empty($isDrugCase) ? 'true' : 'false' }};
            const labSec = document.getElementById('labAnalysisExclusiveSections');
            if (labSec) {
                labSec.classList.add('hidden');
            }
        }

        function selectDrugType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root   = getSeizureBlockFromEvent(e);
            const hidden  = root.querySelector('#newSeizureNameDrug');
            const display = root.querySelector('#drugSearchInput');
            const panel   = root.querySelector('#drugListPanel');
            if (hidden)  hidden.value  = val;
            if (display) display.value = val;
            if (panel)   panel.classList.add('hidden');
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = val;
            if (typeof syncSection4ExhibitsToLabSection === 'function') {
                syncSection4ExhibitsToLabSection();
            }
        }

        function selectWeaponType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root   = getSeizureBlockFromEvent(e);
            const hidden  = root.querySelector('#newSeizureNameWeapon');
            const display = root.querySelector('#weaponSearchInput');
            const panel   = root.querySelector('#weaponListPanel');
            if (hidden)  hidden.value  = val;
            if (display) display.value = val;
            if (panel)   panel.classList.add('hidden');
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = val;
            if (typeof syncSection4ExhibitsToLabSection === 'function') {
                syncSection4ExhibitsToLabSection();
            }
        }

        function selectCurrencyType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root   = getSeizureBlockFromEvent(e);
            const hidden  = root.querySelector('#newSeizureNameCurrency');
            const display = root.querySelector('#currencySearchInput');
            const panel   = root.querySelector('#currencyListPanel');
            if (hidden)  hidden.value  = val;
            if (display) display.value = val;
            if (panel)   panel.classList.add('hidden');
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = val;
            if (typeof syncSection4ExhibitsToLabSection === 'function') {
                syncSection4ExhibitsToLabSection();
            }
        }

        function selectAmmoType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root   = getSeizureBlockFromEvent(e);
            const hidden   = root.querySelector('#newSeizureNameAmmo');
            const display  = root.querySelector('#ammoSearchInput');
            const panel    = root.querySelector('#ammoListPanel');
            if (hidden)  hidden.value  = val;
            if (display) display.value = val;
            if (panel)   panel.classList.add('hidden');
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = val;
            if (typeof syncSection4ExhibitsToLabSection === 'function') {
                syncSection4ExhibitsToLabSection();
            }
        }

        function selectDeviceType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root   = getSeizureBlockFromEvent(e);
            const hidden   = root.querySelector('#newSeizureNameDevice');
            const display  = root.querySelector('#deviceSearchInput');
            const panel    = root.querySelector('#deviceListPanel');
            if (hidden)  hidden.value  = val;
            if (display) display.value = val;
            if (panel)   panel.classList.add('hidden');
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = val;
            if (typeof syncSection4ExhibitsToLabSection === 'function') {
                syncSection4ExhibitsToLabSection();
            }
        }

        // Universal panel close listener
        document.addEventListener('click', function(e) {
            document.querySelectorAll('.seizure-block').forEach(block => {
                const items = [
                    { wrapper: block.querySelector('#exhibitSelectWrapper'), panel: block.querySelector('#exhibitListPanel') },
                    { wrapper: block.querySelector('#drugSelectWrapper'), panel: block.querySelector('#drugListPanel') },
                    { wrapper: block.querySelector('#weaponSelectWrapper'), panel: block.querySelector('#weaponListPanel') },
                    { wrapper: block.querySelector('#currencySelectWrapper'), panel: block.querySelector('#currencyListPanel') },
                    { wrapper: block.querySelector('#ammoSelectWrapper'), panel: block.querySelector('#ammoListPanel') },
                    { wrapper: block.querySelector('#deviceSelectWrapper'), panel: block.querySelector('#deviceListPanel') },
                    { wrapper: block.querySelector('#packagingSelectWrapper'), panel: block.querySelector('#pkgListPanel') },
                    { wrapper: block.querySelector('#unitSelectWrapper'), panel: block.querySelector('#unitListPanel') },
                ];
                items.forEach(item => {
                    if (item.wrapper && item.panel && !item.wrapper.contains(e.target)) {
                        item.panel.classList.add('hidden');
                    }
                });
            });
        }, true);

        function filterAmmoList(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#ammoSearchInput')?.value || '';
            syncAmmoCustomInput(rawVal, root);
            filterAmmoListByQuery(rawVal.toLowerCase(), root);
        }

        function filterAmmoListInner(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#ammoSearchInner')?.value || '';
            syncAmmoCustomInput(rawVal, root);
            filterAmmoListByQuery(rawVal.toLowerCase(), root);
        }

        function syncAmmoCustomInput(val, rootEl) {
            const root = rootEl || document;
            const hidden = root.querySelector('#newSeizureNameAmmo');
            if (hidden) hidden.value = val;
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = val;
        }

        function filterAmmoListByQuery(q, rootEl) {
            const root = rootEl || document;
            const list = root.querySelector('#ammoList');
            if (!list) return;

            let customLi = list.querySelector('#ammoCustomItem');
            const trimmed = (q || '').trim();
            if (trimmed.length > 0) {
                if (!customLi) {
                    customLi = document.createElement('li');
                    customLi.id = 'ammoCustomItem';
                    customLi.className = 'px-1.5 py-1 mb-1 border-b border-emerald-100';
                    list.insertBefore(customLi, list.firstChild);
                }
                const safeVal = trimmed.replace(/'/g, "\\'");
                customLi.innerHTML = `
                    <button type="button" onclick="selectAmmoType('${safeVal}', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-xl flex items-center justify-between transition-colors border border-emerald-200 cursor-pointer">
                        <span>+ Use Custom Entry: "${trimmed}"</span>
                        <span class="text-[10px] font-mono-code bg-emerald-200 text-emerald-900 px-1.5 py-0.5 rounded font-bold">CUSTOM</span>
                    </button>
                `;
                customLi.style.display = '';
            } else if (customLi) {
                customLi.style.display = 'none';
            }

            const items = list.querySelectorAll('.ammo-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        function selectDeviceType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root     = getSeizureBlockFromEvent(e);
            const hidden   = root.querySelector('#newSeizureNameDevice');
            const display  = root.querySelector('#deviceSearchInput');
            const panel    = root.querySelector('#deviceListPanel');
            if (hidden)  hidden.value  = val;
            if (display) display.value = val;
            if (panel)   panel.classList.add('hidden');
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = val;
        }

        function filterDeviceList(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#deviceSearchInput')?.value || '';
            syncDeviceCustomInput(rawVal, root);
            filterDeviceListByQuery(rawVal.toLowerCase(), root);
        }

        function filterDeviceListInner(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#deviceSearchInner')?.value || '';
            filterDeviceListByQuery(rawVal.toLowerCase(), root);
        }

        function syncDeviceCustomInput(val, rootEl) {
            const root = rootEl || document;
            const hidden = root.querySelector('#newSeizureNameDevice');
            if (hidden) hidden.value = val;
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = val;
        }

        function filterDeviceListByQuery(q, rootEl) {
            const root = rootEl || document;
            const list = root.querySelector('#deviceList');
            if (!list) return;

            let customLi = list.querySelector('#deviceCustomItem');
            const trimmed = (q || '').trim();
            if (trimmed.length > 0) {
                if (!customLi) {
                    customLi = document.createElement('li');
                    customLi.id = 'deviceCustomItem';
                    customLi.className = 'px-1.5 py-1 mb-1 border-b border-emerald-100';
                    list.insertBefore(customLi, list.firstChild);
                }
                const safeVal = trimmed.replace(/'/g, "\\'");
                customLi.innerHTML = `
                    <button type="button" onclick="selectDeviceType('${safeVal}', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-xl flex items-center justify-between transition-colors border border-emerald-200 cursor-pointer">
                        <span>+ Use Custom Entry: "${trimmed}"</span>
                        <span class="text-[10px] font-mono-code bg-emerald-200 text-emerald-900 px-1.5 py-0.5 rounded font-bold">CUSTOM</span>
                    </button>
                `;
                customLi.style.display = '';
            } else if (customLi) {
                customLi.style.display = 'none';
            }

            const items = list.querySelectorAll('.device-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        function selectDrugType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root    = getSeizureBlockFromEvent(e);
            const hidden  = root.querySelector('#newSeizureNameDrug');
            const display = root.querySelector('#drugSearchInput');
            const panel   = root.querySelector('#drugListPanel');
            if (hidden)  hidden.value  = val;
            if (display) display.value = val;
            if (panel)   panel.classList.add('hidden');
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = val;
        }

        function filterDrugList(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#drugSearchInput')?.value || '';
            syncDrugCustomInput(rawVal, root);
            filterDrugListByQuery(rawVal.toLowerCase(), root);
        }

        function filterDrugListInner(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#drugSearchInner')?.value || '';
            syncDrugCustomInput(rawVal, root);
            filterDrugListByQuery(rawVal.toLowerCase(), root);
        }

        function syncDrugCustomInput(val, rootEl) {
            const root = rootEl || document;
            const hidden = root.querySelector('#newSeizureNameDrug');
            if (hidden) hidden.value = val;
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = val;
        }

        function filterDrugListByQuery(q, rootEl) {
            const root = rootEl || document;
            const list = root.querySelector('#drugList');
            if (!list) return;

            let customLi = list.querySelector('#drugCustomItem');
            const trimmed = (q || '').trim();
            if (trimmed.length > 0) {
                if (!customLi) {
                    customLi = document.createElement('li');
                    customLi.id = 'drugCustomItem';
                    customLi.className = 'px-1.5 py-1 mb-1 border-b border-emerald-100';
                    list.insertBefore(customLi, list.firstChild);
                }
                const safeVal = trimmed.replace(/'/g, "\\'");
                customLi.innerHTML = `
                    <button type="button" onclick="selectDrugType('${safeVal}', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-xl flex items-center justify-between transition-colors border border-emerald-200 cursor-pointer">
                        <span>+ Use Custom Entry: "${trimmed}"</span>
                        <span class="text-[10px] font-mono-code bg-emerald-200 text-emerald-900 px-1.5 py-0.5 rounded font-bold">CUSTOM</span>
                    </button>
                `;
                customLi.style.display = '';
            } else if (customLi) {
                customLi.style.display = 'none';
            }

            const items = list.querySelectorAll('.drug-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        function selectCurrencyType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root     = getSeizureBlockFromEvent(e);
            const hidden   = root.querySelector('#newSeizureNameCurrency');
            const display  = root.querySelector('#currencySearchInput');
            const panel    = root.querySelector('#currencyListPanel');
            if (hidden)  hidden.value  = val;
            if (display) { display.value = val; display.removeAttribute('readonly'); }
            if (panel)   panel.classList.add('hidden');
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = val;
            if (typeof syncSection4ExhibitsToLabSection === 'function') {
                syncSection4ExhibitsToLabSection();
            }
        }

        function filterCurrencyList(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#currencySearchInput')?.value || '';
            syncCurrencyCustomInput(rawVal, root);
            filterCurrencyListByQuery(rawVal.toLowerCase(), root);
        }

        function filterCurrencyListInner(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#currencySearchInner')?.value || '';
            syncCurrencyCustomInput(rawVal, root);
            filterCurrencyListByQuery(rawVal.toLowerCase(), root);
        }

        function syncCurrencyCustomInput(val, rootEl) {
            const root = rootEl || document;
            const hidden = root.querySelector('#newSeizureNameCurrency');
            if (hidden) hidden.value = val;
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = val;
            if (typeof syncSection4ExhibitsToLabSection === 'function') {
                syncSection4ExhibitsToLabSection();
            }
        }

        function filterCurrencyListByQuery(q, rootEl) {
            const root = rootEl || document;
            const list = root.querySelector('#currencyList');
            if (!list) return;

            let customLi = list.querySelector('#currencyCustomItem');
            const trimmed = (q || '').trim();
            if (trimmed.length > 0) {
                if (!customLi) {
                    customLi = document.createElement('li');
                    customLi.id = 'currencyCustomItem';
                    customLi.className = 'px-1.5 py-1 mb-1 border-b border-emerald-100';
                    list.insertBefore(customLi, list.firstChild);
                }
                const safeVal = trimmed.replace(/'/g, "\\'");
                customLi.innerHTML = `
                    <button type="button" onclick="selectCurrencyType('${safeVal}', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-xl flex items-center justify-between transition-colors border border-emerald-200 cursor-pointer">
                        <span>+ Use Custom Entry: "${trimmed}"</span>
                        <span class="text-[10px] font-mono-code bg-emerald-200 text-emerald-900 px-1.5 py-0.5 rounded font-bold">CUSTOM</span>
                    </button>
                `;
                customLi.style.display = '';
            } else if (customLi) {
                customLi.style.display = 'none';
            }

            const items = list.querySelectorAll('.currency-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        function selectWeaponType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root     = getSeizureBlockFromEvent(e);
            const hidden   = root.querySelector('#newSeizureNameWeapon');
            const display  = root.querySelector('#weaponSearchInput');
            const panel    = root.querySelector('#weaponListPanel');
            if (hidden)  hidden.value  = val;
            if (display) display.value = val;
            if (panel)   panel.classList.add('hidden');
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = val;
        }

        function filterWeaponList(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#weaponSearchInput')?.value || '';
            syncWeaponCustomInput(rawVal, root);
            filterWeaponListByQuery(rawVal.toLowerCase(), root);
        }

        function filterWeaponListInner(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('#weaponSearchInner')?.value || '';
            syncWeaponCustomInput(rawVal, root);
            filterWeaponListByQuery(rawVal.toLowerCase(), root);
        }

        function syncWeaponCustomInput(val, rootEl) {
            const root = rootEl || document;
            const hidden = root.querySelector('#newSeizureNameWeapon');
            if (hidden) hidden.value = val;
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = val;
        }

        function filterWeaponListByQuery(q, rootEl) {
            const root = rootEl || document;
            const list = root.querySelector('#weaponList');
            if (!list) return;

            let customLi = list.querySelector('#weaponCustomItem');
            const trimmed = (q || '').trim();
            if (trimmed.length > 0) {
                if (!customLi) {
                    customLi = document.createElement('li');
                    customLi.id = 'weaponCustomItem';
                    customLi.className = 'px-1.5 py-1 mb-1 border-b border-emerald-100';
                    list.insertBefore(customLi, list.firstChild);
                }
                const safeVal = trimmed.replace(/'/g, "\\'");
                customLi.innerHTML = `
                    <button type="button" onclick="selectWeaponType('${safeVal}', event)" class="w-full text-left px-3 py-2 text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-xl flex items-center justify-between transition-colors border border-emerald-200 cursor-pointer">
                        <span>+ Use Custom Entry: "${trimmed}"</span>
                        <span class="text-[10px] font-mono-code bg-emerald-200 text-emerald-900 px-1.5 py-0.5 rounded font-bold">CUSTOM</span>
                    </button>
                `;
                customLi.style.display = '';
            } else if (customLi) {
                customLi.style.display = 'none';
            }

            const items = list.querySelectorAll('.weapon-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }





        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            const caseMenu = document.getElementById('caseEntryTypeDropdownMenu');
            const caseTrigger = document.getElementById('caseEntryTypeDropdownTrigger');
            if (caseMenu && !caseMenu.classList.contains('hidden')) {
                if (!caseMenu.contains(e.target) && (!caseTrigger || !caseTrigger.contains(e.target))) {
                    caseMenu.classList.add('hidden');
                    const cChevron = document.getElementById('caseTypeChevron');
                    if (cChevron) cChevron.classList.remove('rotate-180');
                }
            }
            const exhibitMenu = document.getElementById('exhibitDropdownMenu');
            const exhibitTrigger = document.getElementById('exhibitDropdownTrigger');
            if (exhibitMenu && !exhibitMenu.classList.contains('hidden')) {
                if (!exhibitMenu.contains(e.target) && (!exhibitTrigger || !exhibitTrigger.contains(e.target))) {
                    exhibitMenu.classList.add('hidden');
                    const eChevron = document.getElementById('exhibitChevron');
                    if (eChevron) eChevron.classList.remove('rotate-180');
                }
            }
            const natContainer = document.getElementById('nationalityDropdownMenu');
            const natTrigger = document.getElementById('nationalityDropdownTrigger');
            if (natContainer && !natContainer.classList.contains('hidden')) {
                if (!natContainer.contains(e.target) && (!natTrigger || !natTrigger.contains(e.target))) {
                    natContainer.classList.add('hidden');
                    const chevron = document.getElementById('nationalityChevron');
                    if (chevron) chevron.classList.remove('rotate-180');
                }
            }

            const genderMenu = document.getElementById('genderDropdownMenu');
            const genderTrigger = document.getElementById('genderDropdownTrigger');
            if (genderMenu && !genderMenu.classList.contains('hidden')) {
                if (!genderMenu.contains(e.target) && (!genderTrigger || !genderTrigger.contains(e.target))) {
                    genderMenu.classList.add('hidden');
                    const gChevron = document.getElementById('genderChevron');
                    if (gChevron) gChevron.classList.remove('rotate-180');
                }
            }

            const maritalMenu = document.getElementById('maritalDropdownMenu');
            const maritalTrigger = document.getElementById('maritalDropdownTrigger');
            if (maritalMenu && !maritalMenu.classList.contains('hidden')) {
                if (!maritalMenu.contains(e.target) && (!maritalTrigger || !maritalTrigger.contains(e.target))) {
                    maritalMenu.classList.add('hidden');
                    const mChevron = document.getElementById('maritalChevron');
                    if (mChevron) mChevron.classList.remove('rotate-180');
                }
            }

            const sidebarMenu = document.getElementById('sidebarEventMenu');
            const sidebarTrigger = document.getElementById('sidebarEventTrigger');
            if (sidebarMenu && !sidebarMenu.classList.contains('hidden')) {
                if (!sidebarMenu.contains(e.target) && (!sidebarTrigger || !sidebarTrigger.contains(e.target))) {
                    sidebarMenu.classList.add('hidden');
                    const sChevron = document.getElementById('sidebarEventChevron');
                    if (sChevron) sChevron.classList.remove('rotate-180');
                }
            }

            // Close dynamic custom dropdown menus when clicking outside
            document.querySelectorAll('.dyn-dropdown-menu').forEach(menu => {
                if (!menu.classList.contains('hidden')) {
                    const parent = menu.closest('.relative');
                    if (parent && !parent.contains(e.target)) {
                        menu.classList.add('hidden');
                        const chevron = parent.querySelector('.dyn-chevron');
                        if (chevron) chevron.classList.remove('rotate-180');
                    }
                }
            });
        });

        // Close all custom dropdowns when scrolling or pressing Escape key
        window.addEventListener('scroll', function() {
            closeAllDropdowns();
        }, { passive: true });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAllDropdowns();
            }
        });

        // Close all custom dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            const isClickInsideDropdown = e.target.closest(
                '#caseEntryTypeDropdownTrigger, #caseEntryTypeDropdownMenu, ' +
                '#operationTypeDropdownTrigger, #operationTypeDropdownMenu, ' +
                '#priorityDropdownTrigger, #priorityDropdownMenu, ' +
                '#genderDropdownTrigger, #genderDropdownMenu, ' +
                '#nationalityDropdownTrigger, #nationalityDropdownMenu, ' +
                '#maritalDropdownTrigger, #maritalDropdownMenu, ' +
                '#exhibitDropdownTrigger, #exhibitDropdownMenu, ' +
                '#sidebarEventDropdownBtn, #sidebarEventMenu, ' +
                '.dyn-dropdown-trigger, .dyn-dropdown-menu'
            );

            if (!isClickInsideDropdown) {
                closeAllDropdowns();
            }
        });

        function calculateAgeFromDOB() {
            const dobInput = document.getElementById('newArrestedDOB');
            const ageInput = document.getElementById('newArrestedAge');
            if (!dobInput || !ageInput) return;

            const dobVal = dobInput.value;
            if (!dobVal) {
                ageInput.value = '';
                return;
            }

            const dob = new Date(dobVal);
            if (isNaN(dob.getTime())) {
                ageInput.value = '';
                return;
            }

            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const monthDiff = today.getMonth() - dob.getMonth();

            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                age--;
            }

            if (age >= 0) {
                ageInput.value = age;
            } else {
                ageInput.value = '';
            }
        }

        // Auto-read URL query ref parameter & calculate initial age if DOB exists + Setup Drag & Drop & Draft Restoration
        document.addEventListener('DOMContentLoaded', function() {
            restoreDocketFormDraft();
            // Always reset the Upload Seized Exhibit Photos / Evidence Images section on page refresh
            resetAllSeizurePhotos();

            // Setup Case Title default "Republic vs " & cursor helper
            const caseTitleEl = document.getElementById('caseTitleInput');
            if (caseTitleEl) {
                if (!caseTitleEl.value || caseTitleEl.value.trim() === '') {
                    caseTitleEl.value = 'Republic vs ';
                }
                const setCursorToEnd = function() {
                    if (caseTitleEl.value === 'Republic vs ' || caseTitleEl.value.trim() === 'Republic vs') {
                        const len = caseTitleEl.value.length;
                        caseTitleEl.setSelectionRange(len, len);
                    }
                };
                caseTitleEl.addEventListener('focus', function() {
                    setTimeout(setCursorToEnd, 10);
                });
                caseTitleEl.addEventListener('click', function() {
                    if (caseTitleEl.value === 'Republic vs ' || caseTitleEl.value.trim() === 'Republic vs') {
                        setCursorToEnd();
                    }
                });
            }

            const suspectNameEl = document.getElementById('newArrestedName');
            if (suspectNameEl && caseTitleEl) {
                suspectNameEl.addEventListener('input', function() {
                    if (caseTitleEl.dataset.userEdited !== 'true') {
                        const sVal = this.value.trim();
                        caseTitleEl.value = sVal ? `Republic vs ${sVal}` : 'Republic vs ';
                    }
                });
                caseTitleEl.addEventListener('input', function() {
                    caseTitleEl.dataset.userEdited = 'true';
                });
            }

            // Always enforce default "Select..." state on page load/refresh for Docket Mode, Gender, and Nationality
            if (document.getElementById('caseEntryTypeSelect')) {
                document.getElementById('caseEntryTypeSelect').value = '';
                const cLabel = document.getElementById('selectedCaseTypeLabel');
                if (cLabel) {
                    cLabel.innerText = 'Select Suspect Docket Mode';
                    cLabel.className = 'font-extrabold text-slate-500 text-xs';
                }
                handleCaseEntryTypeChange();
            }

            if (document.getElementById('newArrestedGender')) {
                document.getElementById('newArrestedGender').value = '';
                const gLabel = document.getElementById('selectedGenderLabel');
                if (gLabel) {
                    gLabel.innerText = 'Select Gender';
                    gLabel.className = 'font-bold text-slate-500 text-xs';
                }
                const gIcon = document.getElementById('selectedGenderIcon');
                if (gIcon) {
                    gIcon.innerText = '?';
                    gIcon.className = 'w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-bold';
                }
            }

            if (document.getElementById('newArrestedNationality')) {
                document.getElementById('newArrestedNationality').value = '';
                const nLabel = document.getElementById('selectedNationalityLabel');
                if (nLabel) {
                    nLabel.innerText = 'Select Nationality';
                    nLabel.className = 'font-bold text-slate-500 text-xs';
                }
                const nIcon = document.getElementById('selectedNationalityIcon');
                if (nIcon) {
                    nIcon.innerText = 'NAT';
                    nIcon.className = 'w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-bold';
                }
            }

            if (document.getElementById('newArrestedMarital')) {
                document.getElementById('newArrestedMarital').value = '';
                const mLabel = document.getElementById('selectedMaritalLabel');
                if (mLabel) {
                    mLabel.innerText = 'Select Marital / Relationship Status';
                    mLabel.className = 'font-bold text-slate-500 text-xs';
                }
                handleMaritalChange('');
            }

            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('input', saveDocketFormDraft);
                form.addEventListener('change', saveDocketFormDraft);
            }

            calculateAgeFromDOB();
            const urlParams = new URLSearchParams(window.location.search);
            const refParam = urlParams.get('ref');
            if (refParam) {
                const raidRefInput = document.getElementById('newArrestedRaidRef');
                if (raidRefInput) raidRefInput.value = refParam;
            }

            
            // Live Suspect Biodata Duplicate Check Listeners
            ['newArrestedID', 'newArrestedName', 'newArrestedDOB', 'newArrestedPhone'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('blur', checkDuplicateSuspectLive);
                    el.addEventListener('input', checkDuplicateSuspectLive);
                }
            });
        });

        // Ensure exhibit photos are completely clean on page refresh / back navigation
        window.addEventListener('pageshow', function() {
            if (typeof resetAllSeizurePhotos === 'function') {
                resetAllSeizurePhotos();
            }
        });

        /* ════════════════════════════════════════════════════════════
           SMART DUPLICATE DETECTION FRONTEND CONTROLLER
        ════════════════════════════════════════════════════════════ */
        let suspectCheckTimer = null;
        let lastMatchedSuspectCaseNumber = null;
        let currentMatchedCaseNumber = null;

        function checkDuplicateSuspectLive() {
            clearTimeout(suspectCheckTimer);
            suspectCheckTimer = setTimeout(async () => {
                const ghanaCard = document.getElementById('newArrestedID')?.value?.trim();
                const suspectName = document.getElementById('newArrestedName')?.value?.trim();
                const dob = document.getElementById('newArrestedDOB')?.value?.trim();
                const phone = document.getElementById('newArrestedPhone')?.value?.trim();

                if (!ghanaCard && (!suspectName || !dob)) {
                    hideSuspectDuplicateNoticeCard();
                    return;
                }

                try {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const res = await fetch('/api/cases/check-duplicate-suspect', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token || ''
                        },
                        body: JSON.stringify({
                            ghana_card: ghanaCard,
                            suspect_name: suspectName,
                            dob: dob,
                            phone: phone
                        })
                    });

                    if (res.status === 401) {
                        return;
                    }

                    if (res.ok) {
                        const data = await res.json();
                        if (data.matched && data.scores && data.scores.total >= 65) {
                            showSuspectDuplicateNoticeCard(data);
                        } else {
                            hideSuspectDuplicateNoticeCard();
                        }
                    }
                } catch (e) {}
            }, 400);
        }

        function showSuspectDuplicateNoticeCard(data) {
            const card = document.getElementById('suspectDuplicateNoticeCard');
            if (!card) return;
            const sName = document.getElementById('suspectDupName');
            const sCard = document.getElementById('dupSuspectCard');
            const sCase = document.getElementById('suspectDupCaseRef');
            const sBadge = document.getElementById('suspectDupMatchBadge');

            if (sName) sName.innerText = data.matched_suspect?.name || 'Unknown Suspect';
            if (sCard) sCard.innerText = data.matched_suspect?.ghana_card || 'N/A';
            if (sCase) sCase.innerText = data.matched_suspect?.case_number || 'Existing Case';
            if (sBadge) sBadge.innerText = `${data.scores.total}% Match (${data.recommendation || 'Found'})`;

            lastMatchedSuspectCaseNumber = data.matched_suspect?.case_number;
            card.classList.remove('hidden');
        }

        function hideSuspectDuplicateNoticeCard() {
            const card = document.getElementById('suspectDuplicateNoticeCard');
            if (card) card.classList.add('hidden');
        }

        function viewSuspectExistingCase() {
            if (lastMatchedSuspectCaseNumber) {
                window.open('/cases/' + lastMatchedSuspectCaseNumber + '/review?from=' + encodeURIComponent(@json($userUnit)), '_blank');
            }
        }

        function showDuplicateCaseModal(data) {
            const modal = document.getElementById('duplicateCaseModal');
            if (!modal) return;

            const cRef = document.getElementById('dupModalCaseRef');
            const cTitle = document.getElementById('dupModalCaseTitle');
            const sName = document.getElementById('dupModalSuspectName');
            const gCard = document.getElementById('dupModalGhanaCard');
            const aDate = document.getElementById('dupModalArrestDate');
            const rLoc = document.getElementById('dupModalLocation');
            const offDesc = document.getElementById('dupModalOffense');
            const scoreEl = document.getElementById('dupModalScore');
            const badgeEl = document.getElementById('dupModalBadge');
            const iconBox = document.getElementById('dupModalIconContainer');
            const titleEl = document.getElementById('dupModalTitle');
            const subEl = document.getElementById('dupModalSubtitle');

            const mCase = data.matched_case || {};
            currentMatchedCaseNumber = mCase.case_number || null;

            if (cRef) cRef.innerText = mCase.case_number || 'NCC-{{ date('Y') }}-XXXX';
            if (cTitle) cTitle.innerText = mCase.title || 'Republic Vs Suspect';
            if (sName) sName.innerText = mCase.suspect_name || mCase.docket_data?.full_name || 'N/A';
            if (gCard) gCard.innerText = mCase.ghana_card || mCase.docket_data?.id_card || 'N/A';
            if (aDate) aDate.innerText = mCase.arrest_date || 'N/A';
            if (rLoc) rLoc.innerText = mCase.raid_location || mCase.location || 'N/A';
            if (offDesc) offDesc.innerText = mCase.offense_description || mCase.description || 'N/A';
            if (scoreEl) scoreEl.innerText = `${data.score || 0}% Match`;

            if (data.duplicate_level === 'HIGH_CONFIDENCE') {
                if (badgeEl) {
                    badgeEl.innerText = 'HIGH CONFIDENCE DUPLICATE CASE DETECTED';
                    badgeEl.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-mono-code font-bold bg-red-100 text-red-800 border border-red-200 uppercase tracking-wider';
                }
                if (iconBox) {
                    iconBox.className = 'w-12 h-12 rounded-2xl bg-red-600 text-white flex items-center justify-center shrink-0 shadow-lg shadow-red-600/30';
                }
                if (titleEl) titleEl.innerText = 'Possible Duplicate Case Found';
                if (subEl) subEl.innerText = data.message || 'An existing case file closely matches the suspect biodata, operation, and incident details entered.';
            } else {
                if (badgeEl) {
                    badgeEl.innerText = 'POTENTIAL DUPLICATE CASE DETECTED';
                    badgeEl.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-mono-code font-bold bg-amber-100 text-amber-800 border border-amber-200 uppercase tracking-wider';
                }
                if (iconBox) {
                    iconBox.className = 'w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-lg shadow-amber-500/30';
                }
                if (titleEl) titleEl.innerText = 'Potential Duplicate Detected';
                if (subEl) subEl.innerText = data.message || 'Some details match an existing record. Please review before proceeding.';
            }

            modal.classList.remove('hidden');
        }

        function closeDuplicateCaseModal() {
            const modal = document.getElementById('duplicateCaseModal');
            if (modal) modal.classList.add('hidden');
            if (typeof setSubmittingState === 'function') {
                setSubmittingState(false);
            }
        }

        function viewMatchedDuplicateCase() {
            if (currentMatchedCaseNumber) {
                window.open('/cases/' + currentMatchedCaseNumber + '/review?from=' + encodeURIComponent(@json($userUnit)), '_blank');
            }
        }

        function confirmAndRegisterDuplicateCase() {
            closeDuplicateCaseModal();
            if (typeof setSubmittingState === 'function') {
                setSubmittingState(false);
            }
            processNewArrested(true);
        }

        function selectVehType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root    = getSeizureBlockFromEvent(e);
            const hidden  = root.querySelector('#vehType');
            const display = root.querySelector('#vehTypeSearchInput');
            const panel   = root.querySelector('#vehTypeListPanel');
            if (hidden)  hidden.value = val;
            if (display) { display.value = val; display.setAttribute('readonly', 'readonly'); }
            if (panel)   panel.classList.add('hidden');
        }

        function filterVehTypeList(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const q = (root.querySelector('#vehTypeSearchInput')?.value || '').toLowerCase();
            filterVehTypeListByQuery(q, root);
        }

        function filterVehTypeListInner(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const q = (root.querySelector('#vehTypeSearchInner')?.value || '').toLowerCase();
            filterVehTypeListByQuery(q, root);
        }

        function filterVehTypeListByQuery(q, rootEl) {
            const root = rootEl || document;
            const items = root.querySelectorAll('.veh-type-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        // Close panels when clicking outside (Block-scoped)
        document.addEventListener('click', function(e) {
            const blocks = document.querySelectorAll('.seizure-block');
            blocks.forEach(block => {
                const panels = [
                    { wrapper: '#exhibitSelectWrapper', panel: '#exhibitListPanel' },
                    { wrapper: '#drugSelectWrapper', panel: '#drugListPanel' },
                    { wrapper: '#weaponSelectWrapper', panel: '#weaponListPanel' },
                    { wrapper: '#currencySelectWrapper', panel: '#currencyListPanel' },
                    { wrapper: '#ammoSelectWrapper', panel: '#ammoListPanel' },
                    { wrapper: '#deviceSelectWrapper', panel: '#deviceListPanel' },
                    { wrapper: '#pkgSelectWrapper', panel: '#pkgListPanel' },
                    { wrapper: '#unitSelectWrapper', panel: '#unitListPanel' },
                    { wrapper: '#vehTypeSelectWrapper', panel: '#vehTypeListPanel' },
                ];
                panels.forEach(p => {
                    const w = block.querySelector(p.wrapper);
                    const pan = block.querySelector(p.panel);
                    if (w && pan && !w.contains(e.target)) {
                        pan.classList.add('hidden');
                    }
                });
            });
        }, true);

        // Seizing Officers Dynamic Multi-Row Handling
        function addSeizureOfficerRow(btn) {
            const block = btn ? btn.closest('.seizure-block') : null;
            const container = block ? block.querySelector('#seizureOfficersContainer') : document.getElementById('seizureOfficersContainer');
            if (!container) return;
            const rowDiv = document.createElement('div');
            rowDiv.className = 'seizure-officer-row p-2.5 bg-slate-50 border border-slate-300/80 rounded-xl';
            rowDiv.innerHTML = `
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div class="relative flex items-center group">
                        <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </span>
                        <input type="text" class="seizure-officer-name w-full pl-8 pr-3 py-2 bg-white border border-slate-300 rounded-lg font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400" placeholder="Officer Full Name (e.g. DSP Kwame Mensah)">
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="relative flex-1 flex items-center group">
                            <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 012-2h2a2 2 0 012 2v1m-4 0h4"/></svg>
                            </span>
                            <input type="text" class="seizure-officer-service-id w-full pl-8 pr-3 py-2 bg-white border border-slate-300 rounded-lg font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400 font-mono-code" placeholder="Service ID / Badge No (e.g. SD-0042)">
                        </div>
                        <button type="button" onclick="removeSeizureOfficerRow(this)" class="remove-officer-btn p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg border border-transparent hover:border-red-200 transition-colors cursor-pointer" title="Remove Officer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(rowDiv);
            updateSeizureOfficerRemoveButtons(container);
        }

        function removeSeizureOfficerRow(btn) {
            const row = btn.closest('.seizure-officer-row');
            const container = btn.closest('#seizureOfficersContainer');
            if (row) {
                row.remove();
                if (container) updateSeizureOfficerRemoveButtons(container);
            }
        }

        function updateSeizureOfficerRemoveButtons(container) {
            const containers = container ? [container] : document.querySelectorAll('#seizureOfficersContainer');
            containers.forEach(c => {
                const rows = c.querySelectorAll('.seizure-officer-row');
                rows.forEach(row => {
                    const btn = row.querySelector('.remove-officer-btn');
                    if (btn) {
                        if (rows.length > 1) {
                            btn.classList.remove('hidden');
                        } else {
                            btn.classList.add('hidden');
                        }
                    }
                });
            });
        }

        function getSeizureOfficersFormatted(block) {
            const parent = block || document;
            const container = parent.querySelector('#seizureOfficersContainer');
            if (!container) return null;
            const rows = container.querySelectorAll('.seizure-officer-row');
            const list = [];
            rows.forEach(row => {
                const nameInput = row.querySelector('.seizure-officer-name');
                const idInput = row.querySelector('.seizure-officer-service-id');
                const name = nameInput ? nameInput.value.trim() : '';
                const svcId = idInput ? idInput.value.trim() : '';
                if (name && svcId) {
                    list.push(`${name} (${svcId})`);
                } else if (name) {
                    list.push(name);
                } else if (svcId) {
                    list.push(`(ID: ${svcId})`);
                }
            });
            return list.length > 0 ? list.join('; ') : null;
        }

        // SECTION 5: MANIFEST SYNC & EXHIBIT SELECTION LOGIC FOR LAB ANALYSIS
        let labExhibitUserEdits = {};
        const forensicDrugKeywords = @json(config('forensic.drug_keywords', []));
        const forensicNonDrugKeywords = @json(config('forensic.non_drug_keywords', []));

        function isDrugCategory(cat, name = '') {
            const combined = `${cat || ''} ${name || ''}`.toLowerCase().trim();
            if (!combined) return false;
            if (Array.isArray(forensicNonDrugKeywords) && forensicNonDrugKeywords.some(ndk => combined.includes(ndk.toLowerCase()))) {
                const hasExplicitDrug = Array.isArray(forensicDrugKeywords) && forensicDrugKeywords.some(dk => (name || '').toLowerCase().includes(dk.toLowerCase()));
                if (!hasExplicitDrug) return false;
            }
            return Array.isArray(forensicDrugKeywords) && forensicDrugKeywords.some(k => combined.includes(k.toLowerCase()));
        }

        function syncSection4ExhibitsToLabSection() {
            const selectEl = document.getElementById('labExhibitSelect');
            const badgeEl = document.getElementById('labExhibitCountBadge');
            const descInput = document.getElementById('labExhibitDescriptionInput');
            const labSec = document.getElementById('labAnalysisExclusiveSections');
            const drugTypeSel = document.getElementById('labDrugTypeSelect');
            const selectedDrugType = drugTypeSel ? (drugTypeSel.value || '').toLowerCase().trim() : '';
            if (!selectEl) return;

            const isLabUser = false;
            const chk = document.getElementById('hasSeizuresCheckbox');
            const container = document.getElementById('seizuresDetailsContainer');
            const hasSeizures = chk && chk.checked;
            const isContainerVisible = container && !container.classList.contains('hidden');

            const currentVal = selectEl.value;
            selectEl.innerHTML = '';
            selectEl.removeAttribute('disabled');

            if (!hasSeizures || !isContainerVisible) {
                const opt = document.createElement('option');
                opt.value = '';
                opt.innerText = selectedDrugType ? 'No matching seized exhibit found' : 'No exhibit saved yet';
                selectEl.appendChild(opt);
                selectEl.setAttribute('disabled', 'disabled');
                if (badgeEl) badgeEl.innerText = selectedDrugType ? 'No matching exhibit' : 'No exhibit saved yet';
                if (descInput) {
                    descInput.value = selectedDrugType ? 'No matching exhibit found' : 'No exhibit saved yet';
                }
                if (labSec && !isLabUser) labSec.classList.add('hidden');
                else if (labSec) labSec.classList.remove('hidden');
                onLabExhibitSelected();
                return;
            }

            const blocks = document.querySelectorAll('.seizure-block');

            if (blocks.length === 0) {
                const opt = document.createElement('option');
                opt.value = '';
                opt.innerText = selectedDrugType ? 'No matching seized exhibit found' : 'No exhibit saved yet';
                selectEl.appendChild(opt);
                selectEl.setAttribute('disabled', 'disabled');
                if (badgeEl) badgeEl.innerText = selectedDrugType ? 'No matching exhibit' : 'No exhibit saved yet';
                if (descInput) {
                    descInput.value = selectedDrugType ? 'No matching exhibit found' : 'No exhibit saved yet';
                }
                if (labSec && !isLabUser) labSec.classList.add('hidden');
                else if (labSec) labSec.classList.remove('hidden');
                onLabExhibitSelected();
                return;
            }

            let validExhibitsCount = 0;

            blocks.forEach((block, idx) => {
                const itemNum = idx + 1;
                
                const cat = block.querySelector('#newSeizureCategory')?.value || 
                            block.querySelector('#exhibitSearchInput')?.value || 
                            '';

                let name = block.querySelector('#newSeizureNameDrug')?.value || 
                           block.querySelector('#drugSearchInput')?.value || 
                           block.querySelector('#newSeizureName')?.value || 
                           block.querySelector('#weaponSearchInput')?.value || 
                           block.querySelector('#currencySearchInput')?.value || 
                           '';

                const qty = block.querySelector('#newSeizureQuantity')?.value || '';
                const pkg = block.querySelector('#newSeizurePackaging')?.value || block.querySelector('#pkgSearchInput')?.value || '';
                const weight = block.querySelector('#newSeizureWeight')?.value || '';
                const unit = block.querySelector('#newSeizureUnit')?.value || block.querySelector('#unitSearchInput')?.value || '';
                const reason = block.querySelector('#newSeizureReason')?.value || '';
                const owner = block.querySelector('.seizure-associated-person-select')?.value || block.querySelector('#newSeizureAssociatedPerson')?.value || '';

                let displayName = name;
                if (!displayName || displayName.trim() === '') {
                    displayName = (cat && cat.trim() !== '') ? cat : (name || 'Exhibit');
                }

                if (selectedDrugType) {
                    const fullText = (displayName + ' ' + cat).toLowerCase();
                    if (!fullText.includes(selectedDrugType)) {
                        return;
                    }
                }

                let optionText = displayName;
                if (qty || weight) optionText += ` — ${qty ? qty + ' ' + (pkg || 'qty') : ''}${weight ? ' ' + weight + ' ' + (unit || '') : ''}`;
                if (owner) optionText += ` (Owner: ${owner})`;

                const opt = document.createElement('option');
                opt.value = itemNum.toString();
                opt.innerText = optionText;
                opt.setAttribute('data-name', displayName);
                opt.setAttribute('data-cat', cat);
                opt.setAttribute('data-qty', qty);
                opt.setAttribute('data-pkg', pkg);
                opt.setAttribute('data-weight', weight);
                opt.setAttribute('data-unit', unit);
                opt.setAttribute('data-reason', reason);
                opt.setAttribute('data-owner', owner);
                selectEl.appendChild(opt);
                validExhibitsCount++;
            });

            if (validExhibitsCount === 0) {
                selectEl.innerHTML = '';
                const opt = document.createElement('option');
                opt.value = '';
                opt.innerText = selectedDrugType ? 'No matching seized exhibit found' : 'No exhibit saved yet';
                selectEl.appendChild(opt);
                selectEl.setAttribute('disabled', 'disabled');
                if (badgeEl) badgeEl.innerText = selectedDrugType ? 'No matching exhibit' : 'No exhibit saved yet';
                if (descInput) {
                    descInput.value = selectedDrugType ? 'No matching exhibit found' : 'No exhibit saved yet';
                }
                if (labSec && !isLabUser) labSec.classList.add('hidden');
                else if (labSec) labSec.classList.remove('hidden');
                onLabExhibitSelected();
                return;
            }

            const placeholderOpt = document.createElement('option');
            placeholderOpt.value = '';
            placeholderOpt.innerText = 'Select Seized Exhibit (Manifest Sync)...';
            selectEl.insertBefore(placeholderOpt, selectEl.firstChild);

            if (labSec) {
                if (isLabUser) {
                    labSec.classList.remove('hidden');
                } else {
                    labSec.classList.add('hidden');
                }
            }

            if (badgeEl) {
                badgeEl.innerText = `${validExhibitsCount} Exhibit${validExhibitsCount > 1 ? 's' : ''} in Manifest`;
            }

            if (currentVal && selectEl.querySelector(`option[value="${currentVal}"]`)) {
                selectEl.value = currentVal;
            } else {
                selectEl.value = '';
            }

            onLabExhibitSelected();
        }

        function onDrugTypeChangeCreate(selectEl) {
            syncSection4ExhibitsToLabSection();
        }

        function onLabExhibitSelected() {
            const selectEl = document.getElementById('labExhibitSelect');
            const descInput = document.getElementById('labExhibitDescriptionInput');
            const exhNumInput = document.getElementById('labExhibitNumberInput');
            const examRefInput = document.getElementById('labExamReferenceInput');

            if (!selectEl) return;
            const selectedOption = selectEl.options[selectEl.selectedIndex];
            
            if (!selectedOption || !selectEl.value || selectEl.value === '' || selectedOption.text === 'No exhibit saved yet' || selectedOption.text.startsWith('Select Seized Exhibit')) {
                if (descInput) {
                    descInput.value = 'No exhibit selected';
                }
                return;
            }

            const idx = selectEl.value || '1';
            const paddedIdx = idx.padStart(3, '0');

            if (exhNumInput && !exhNumInput.value) {
                exhNumInput.placeholder = 'A, B...Ai, Aii, Bi, Bii...., A1, A2, B1, B2';
            }
            if (examRefInput && (!examRefInput.value || examRefInput.value.startsWith('EXAM-') || examRefInput.value.includes('T28') || examRefInput.value.startsWith('GSA-') || examRefInput.value.startsWith('CSS-') || examRefInput.value.startsWith('EMU-') || examRefInput.value.startsWith('NLR-'))) {
                const randExam = Math.floor(1000 + Math.random() * 9000);
                examRefInput.value = 'NLA-FM-T' + randExam + '-F';
            }

            const name = selectedOption.getAttribute('data-name') || '';
            const cat = selectedOption.getAttribute('data-cat') || '';
            const qty = selectedOption.getAttribute('data-qty') || '';
            const pkg = selectedOption.getAttribute('data-pkg') || '';
            const weight = selectedOption.getAttribute('data-weight') || '';
            const unit = selectedOption.getAttribute('data-unit') || '';
            const reason = selectedOption.getAttribute('data-reason') || '';
            const owner = selectedOption.getAttribute('data-owner') || '';

            let formattedDesc = name || cat || 'Exhibit';
            if (cat) formattedDesc += ` | Category: ${cat}`;
            if (qty || pkg) formattedDesc += ` | Quantity/Packaging: ${qty} ${pkg}`.trim();
            if (weight || unit) formattedDesc += ` | Net Weight/Metrics: ${weight} ${unit}`.trim();
            if (owner) formattedDesc += ` | Suspect / Ownership: ${owner}`;
            if (reason) formattedDesc += ` | Seizure Facts: ${reason}`;

            if (descInput) {
                if (!labExhibitUserEdits[idx]) {
                    descInput.value = formattedDesc;
                } else {
                    descInput.value = labExhibitUserEdits[idx];
                }
            }

            // Populate SAMPLE WEIGHT Description, Units/Packaging, & Gross Weight
            const sampleDescInput = document.getElementById('sampleWeightDescription');
            const sampleUnitsInput = document.getElementById('sampleWeightUnits');
            const sampleGrossInput = document.getElementById('sampleWeightGross');
            const drugName = name || cat || '';
            let unitsText = qty ? `${qty} ${pkg}`.trim() : (pkg || '');
            let grossWeightText = weight ? `${weight} ${unit}`.trim() : (unit || '');

            const isNonDrugVal = (val) => {
                if (!val) return false;
                const lVal = val.toLowerCase();
                return Array.isArray(window.forensicNonDrugKeywords) && window.forensicNonDrugKeywords.some(kw => lVal.includes(kw.toLowerCase()));
            };

            if (sampleDescInput) {
                if (!sampleDescInput.value || sampleDescInput.getAttribute('data-auto-synced') === 'true' || sampleDescInput.value.startsWith('e.g.') || isNonDrugVal(sampleDescInput.value)) {
                    if (drugName) {
                        sampleDescInput.value = drugName;
                        sampleDescInput.setAttribute('data-auto-synced', 'true');
                    }
                }
            }

            const substanceIdInput = document.getElementById('confirmatorySubstanceIdentification');
            if (substanceIdInput) {
                if (!substanceIdInput.value || substanceIdInput.getAttribute('data-auto-synced') === 'true' || substanceIdInput.value.startsWith('Displaying') || isNonDrugVal(substanceIdInput.value)) {
                    if (drugName) {
                        substanceIdInput.value = drugName;
                        substanceIdInput.setAttribute('data-auto-synced', 'true');
                    }
                }
            }

            if (sampleUnitsInput) {
                if (!sampleUnitsInput.value || sampleUnitsInput.getAttribute('data-auto-synced') === 'true' || isNonDrugVal(sampleUnitsInput.value)) {
                    if (unitsText) {
                        sampleUnitsInput.value = unitsText;
                        sampleUnitsInput.setAttribute('data-auto-synced', 'true');
                    }
                }
            }
            if (sampleGrossInput) {
                if (!sampleGrossInput.value || sampleGrossInput.getAttribute('data-auto-synced') === 'true' || sampleGrossInput.value.startsWith('e.g.') || isNonDrugVal(sampleGrossInput.value)) {
                    if (grossWeightText) {
                        sampleGrossInput.value = grossWeightText;
                        sampleGrossInput.setAttribute('data-auto-synced', 'true');
                    }
                }
            }

            const unitAddon = document.getElementById('sampleWeightTakenUnitAddon');
            if (unitAddon) {
                if (unit) {
                    unitAddon.textContent = unit;
                } else if (grossWeightText) {
                    const uMatch = grossWeightText.match(/(Kilograms?\s*\([^\)]+\)|Kilograms?|kg|Grams?|g|mg|lbs?)/i);
                    if (uMatch) unitAddon.textContent = uMatch[1];
                }
            }

            // Reset Presumptive Test
            const presYes = document.getElementById('presumptiveTestYes');
            const presNo = document.getElementById('presumptiveTestNo');
            if (presYes) presYes.checked = false;
            if (presNo) presNo.checked = false;

            const presContainer = document.getElementById('presumptiveTestRowsContainer');
            if (presContainer) {
                const rows = presContainer.querySelectorAll('.presumptive-test-row');
                rows.forEach((r, idx) => {
                    if (idx === 0) {
                        const presence = r.querySelector('.presumptive-presence-input');
                        const percent = r.querySelector('.presumptive-percentage-input');
                        if (presence) presence.value = '';
                        if (percent) percent.value = '';
                    } else {
                        r.remove();
                    }
                });
            }

            // Reset Confirmatory Test
            const confYes = document.getElementById('confirmatoryTestYes');
            const confNo = document.getElementById('confirmatoryTestNo');
            if (confYes) confYes.checked = false;
            if (confNo) confNo.checked = false;
            
            const confResultInput = document.getElementById('confirmatoryTestResultInput');
            if (confResultInput) confResultInput.value = '';

            const detectedContainer = document.getElementById('detectedSubstancesListContainer');
            if (detectedContainer) detectedContainer.innerHTML = '';

            const confMethods = document.getElementById('confirmatoryTestMethods');
            const confSpecs = document.getElementById('confirmatorySpecifications');
            if (confMethods) confMethods.value = '';
            if (confSpecs) confSpecs.value = '';

            const takenInput = document.getElementById('sampleWeightTaken');
            const reservedInput = document.getElementById('sampleWeightReserved');
            if (takenInput) takenInput.value = '';
            if (reservedInput) reservedInput.value = '';

            updateSampleWeightReserved();
        }

        function findWeightChunk(parts) {
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

        function parseParts(str) {
            return str.split('/').map(p => p.trim()).filter(p => p.length > 0);
        }

        function parseQtyAndUnit(chunk) {
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

        function toKg(num, unit) {
            if (!unit) return null;
            const u = unit.toLowerCase();
            if (u === 'kg') return num;
            if (u === 'g') return num / 1000;
            if (u === 'mg') return num / 1000000;
            if (u === 'lb') return num * 0.453592;
            return null;
        }

        function formatNum(num) {
            if (num < 0) num = 0;
            const rounded = Math.round(num * 10000) / 10000;
            return rounded.toString();
        }

        function computeReservedSample(grossStr, takenStr) {
            if (!grossStr || typeof grossStr !== 'string' || !grossStr.trim()) {
                return { reserved: '0', isOverflow: false };
            }

            const grossParts = parseParts(grossStr);
            let targetGrossChunk = findWeightChunk(grossParts);
            const gParsed = parseQtyAndUnit(targetGrossChunk);

            const defaultReserved = (gParsed && gParsed.unit) ? `0 ${gParsed.unit}` : '0';

            if (!takenStr || typeof takenStr !== 'string' || !takenStr.trim()) {
                return { reserved: defaultReserved, isOverflow: false };
            }

            const takenParts = parseParts(takenStr);
            let targetTakenChunk = findWeightChunk(takenParts);
            const tParsed = parseQtyAndUnit(targetTakenChunk);

            if (!gParsed || !tParsed) {
                return { reserved: defaultReserved, isOverflow: false };
            }

            let gKg = toKg(gParsed.num, gParsed.unit);
            let tKg = toKg(tParsed.num, tParsed.unit);

            if (gKg !== null && tKg === null && !tParsed.unit) {
                tKg = toKg(tParsed.num, gParsed.unit);
            }

            if (gKg !== null && tKg !== null) {
                if (tKg > gKg) {
                    return { reserved: defaultReserved, isOverflow: true };
                }
                const diffKg = gKg - tKg;
                const gU = gParsed.unit.toLowerCase();
                let resVal = '0';
                if (gU === 'g' || gU === 'gm' || gU === 'gram' || gU === 'grams') {
                    resVal = formatNum(diffKg * 1000) + (gParsed.unit ? ' ' + gParsed.unit : '');
                } else if (gU === 'mg' || gU === 'milligram' || gU === 'milligrams') {
                    resVal = formatNum(diffKg * 1000000) + (gParsed.unit ? ' ' + gParsed.unit : '');
                } else {
                    resVal = formatNum(diffKg) + (gParsed.unit ? ' ' + gParsed.unit : '');
                }
                return { reserved: resVal, isOverflow: false };
            } else {
                if (tParsed.num > gParsed.num) {
                    return { reserved: defaultReserved, isOverflow: true };
                }
                const diff = gParsed.num - tParsed.num;
                const unit = gParsed.unit || tParsed.unit;
                return { reserved: formatNum(diff) + (unit ? ' ' + unit : ''), isOverflow: false };
            }
        }

        function updateSampleWeightReserved() {
            const grossInput = document.getElementById('sampleWeightGross');
            const takenInput = document.getElementById('sampleWeightTaken');
            const addonInput = document.getElementById('sampleWeightTakenUnitAddon');
            const reservedInput = document.getElementById('sampleWeightReserved');
            const errorMsgEl = document.getElementById('sampleWeightTakenError');

            if (!grossInput || !reservedInput) return;

            const grossStr = grossInput.value || '';
            const takenStr = takenInput ? takenInput.value : '';

            const result = computeReservedSample(grossStr, takenStr);

            reservedInput.value = result.reserved;

            if (result.isOverflow) {
                if (errorMsgEl) errorMsgEl.classList.remove('hidden');
                if (takenInput) {
                    takenInput.classList.add('border-red-500', 'ring-2', 'ring-red-500/20');
                    takenInput.classList.remove('border-slate-300');
                }
                if (addonInput) {
                    addonInput.classList.add('border-red-500', 'bg-red-50', 'text-red-700');
                    addonInput.classList.remove('border-slate-300', 'bg-slate-100/90', 'text-slate-700');
                }
            } else {
                if (errorMsgEl) errorMsgEl.classList.add('hidden');
                if (takenInput) {
                    takenInput.classList.remove('border-red-500', 'ring-2', 'ring-red-500/20');
                    takenInput.classList.add('border-slate-300');
                }
                if (addonInput) {
                    addonInput.classList.remove('border-red-500', 'bg-red-50', 'text-red-700');
                    addonInput.classList.add('border-slate-300', 'bg-slate-100/90', 'text-slate-700');
                }
            }
        }

        function updateDetectedSubstanceRemoveButtons() {
            const container = document.getElementById('detectedSubstancesListContainer');
            if (!container) return;
            const rows = container.querySelectorAll('.detected-substance-row');
            rows.forEach(r => {
                const btn = r.querySelector('button[title="Remove Row"]');
                if (btn) {
                    if (rows.length <= 1) {
                        btn.classList.add('hidden');
                        btn.style.display = 'none';
                    } else {
                        btn.classList.remove('hidden');
                        btn.style.display = '';
                    }
                }
            });
        }

        function addDetectedSubstanceRow(substance = '', percentage = '') {
            const container = document.getElementById('detectedSubstancesListContainer');
            if (!container) return;
            const rowDiv = document.createElement('div');
            rowDiv.className = 'detected-substance-row flex flex-col sm:flex-row items-center gap-3 bg-white p-3 rounded-xl border border-slate-300 shadow-2xs animate-in fade-in duration-200';
            rowDiv.innerHTML = `
                <div class="flex-1 w-full">
                    <label class="block text-slate-700 font-bold text-[10px] uppercase mb-1">Substance / Compound Detected</label>
                    <input type="text" class="detected-substance-name w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-slate-900 font-extrabold text-xs focus:outline-none focus:border-emerald-600 font-sans" placeholder="e.g. Cocaine Hydrochloride" value="${substance}">
                </div>
                <div class="w-full sm:w-48">
                    <label class="block text-slate-700 font-bold text-[10px] uppercase mb-1">Purity / Percentage</label>
                    <div class="flex items-center rounded-lg overflow-hidden shadow-2xs">
                        <input type="text" class="detected-substance-percent w-full pl-3 pr-2 py-2 bg-slate-50 border border-y border-l border-slate-300 rounded-l-lg text-slate-900 font-extrabold text-xs focus:outline-none focus:border-emerald-600 font-mono-code" placeholder="e.g. 85.4" value="${percentage}">
                        <span class="inline-flex items-center px-2.5 py-2 bg-slate-200/80 border border-slate-300 rounded-r-lg text-slate-700 font-extrabold text-xs font-mono-code shrink-0">%</span>
                    </div>
                </div>
                <button type="button" onclick="removeDetectedSubstanceRow(this)" class="mt-2 sm:mt-5 p-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg border border-red-200 transition-colors shrink-0 cursor-pointer" title="Remove Row">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            `;
            container.appendChild(rowDiv);
            updateDetectedSubstanceRemoveButtons();
        }

        function removeDetectedSubstanceRow(btn) {
            const container = document.getElementById('detectedSubstancesListContainer');
            if (container) {
                const rows = container.querySelectorAll('.detected-substance-row');
                if (rows.length <= 1) return;
            }
            const row = btn.closest('.detected-substance-row');
            if (row) {
                row.remove();
                updateDetectedSubstanceRemoveButtons();
            }
        }

        function getDetectedSubstancesData() {
            const rows = document.querySelectorAll('#detectedSubstancesListContainer .detected-substance-row');
            const data = [];
            rows.forEach(r => {
                const sub = r.querySelector('.detected-substance-name')?.value || '';
                const pct = r.querySelector('.detected-substance-percent')?.value || '';
                if (sub.trim() !== '' || pct.trim() !== '') {
                    data.push({ substance: sub.trim(), percentage: pct.trim() });
                }
            });
            return data;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const descInput = document.getElementById('labExhibitDescriptionInput');
            if (descInput) {
                descInput.addEventListener('input', function() {
                    const selectEl = document.getElementById('labExhibitSelect');
                    if (selectEl && selectEl.value) {
                        labExhibitUserEdits[selectEl.value] = this.value;
                    }
                });
            }

            const sampleGrossEl = document.getElementById('sampleWeightGross');
            const sampleTakenEl = document.getElementById('sampleWeightTaken');
            ['input', 'keyup', 'change', 'blur', 'paste'].forEach(function(evt) {
                if (sampleGrossEl) sampleGrossEl.addEventListener(evt, updateSampleWeightReserved);
                if (sampleTakenEl) sampleTakenEl.addEventListener(evt, updateSampleWeightReserved);
            });

            // Real-time capturing listeners for all Section 4 input events
            ['input', 'keyup', 'change', 'blur', 'paste'].forEach(function(eventType) {
                document.addEventListener(eventType, function(e) {
                    if (e.target && e.target.closest && (e.target.closest('#seizuresDetailsContainer') || e.target.closest('#seizuresListContainer'))) {
                        if (typeof syncSection4ExhibitsToLabSection === 'function') {
                            syncSection4ExhibitsToLabSection();
                        }
                    }
                }, true);
            });

            const detContainer = document.getElementById('detectedSubstancesListContainer');
            if (detContainer) {
                if (detContainer.children.length === 0) {
                    addDetectedSubstanceRow('', '');
                } else {
                    updateDetectedSubstanceRemoveButtons();
                }
            }

            // Listener to keep Suspect/Ownership dropdown options synced with suspect names
            ['input', 'keyup', 'change', 'blur'].forEach(function(eventType) {
                document.addEventListener(eventType, function(e) {
                    if (e.target && (e.target.id === 'newArrestedName' || (e.target.matches && e.target.matches('[data-field="suspect_name"]')))) {
                        syncSuspectDropdownsForExhibits();
                    }
                });
            });

            syncSuspectDropdownsForExhibits();
            syncSection4ExhibitsToLabSection();
            updateSampleWeightReserved();
        });

        function syncSuspectDropdownsForExhibits() {
            const suspects = [];

            // Primary Suspect Name
            const s1Name = document.getElementById('newArrestedName')?.value?.trim() || '';
            if (s1Name) {
                suspects.push({ name: s1Name, role: 'Primary Suspect' });
            }

            // Dynamic Co-Suspect Names
            document.querySelectorAll('.suspect-card-dynamic').forEach((card, idx) => {
                const input = card.querySelector('[data-field="suspect_name"]');
                const nameVal = input?.value?.trim() || '';
                if (nameVal) {
                    suspects.push({ name: nameVal, role: `Co-Suspect #${idx + 1}` });
                }
            });

            document.querySelectorAll('.seizure-associated-person-select').forEach(select => {
                const currentVal = select.value;
                const userHasSelected = select.getAttribute('data-user-selected') === 'true';

                select.innerHTML = '';

                // Always include "All Suspects" option
                const allOpt = document.createElement('option');
                allOpt.value = 'All Suspects';
                allOpt.textContent = suspects.length > 1 ? 'All Suspects (Joint Ownership)' : 'All Suspects';
                select.appendChild(allOpt);

                suspects.forEach(sp => {
                    const opt = document.createElement('option');
                    opt.value = sp.name;
                    opt.textContent = `${sp.name} (${sp.role})`;
                    select.appendChild(opt);
                });

                if (userHasSelected && currentVal && Array.from(select.options).some(o => o.value === currentVal)) {
                    select.value = currentVal;
                } else if (currentVal && Array.from(select.options).some(o => o.value === currentVal)) {
                    select.value = currentVal;
                } else if (suspects.length === 1 && !userHasSelected) {
                    select.value = suspects[0].name;
                } else {
                    select.value = currentVal || 'All Suspects';
                }
            });
        }
    
        function getLabFormElements() {
            const s4 = document.getElementById('section4Seizures') || 
                       document.getElementById('seizuresDetailsContainer') || 
                       document.getElementById('seizuresListContainer');

            const s5List = [
                document.getElementById('section5LabExamination'),
                document.getElementById('section5FormContainer'),
                document.getElementById('section5ReadOnlyDisplay'),
                document.getElementById('labAnalysisExclusiveSections')
            ].filter(el => el !== null);

            return { s4, s5List };
        }

        window.toggleLabFormSections = function() {
            const { s4, s5List } = getLabFormElements();
            const masterTexts = document.querySelectorAll('.toggleLabFormTextSpan');
            const s4Texts = document.querySelectorAll('.s4ToggleTextSpan');
            const s5Texts = document.querySelectorAll('.s5ToggleTextSpan');

            if (!s4 && s5List.length === 0) return;

            const isS4Visible = s4 && !s4.classList.contains('hidden');
            const isS5Visible = s5List.some(el => !el.classList.contains('hidden'));

            if (isS4Visible || isS5Visible) {
                if (s4) s4.classList.add('hidden');
                s5List.forEach(el => el.classList.add('hidden'));
                masterTexts.forEach(el => el.innerText = 'Show Both Sections');
                s4Texts.forEach(el => el.innerText = 'Show Section 4');
                s5Texts.forEach(el => el.innerText = 'Show Section 5');
            } else {
                if (s4) s4.classList.remove('hidden');
                s5List.forEach(el => el.classList.remove('hidden'));
                masterTexts.forEach(el => el.innerText = 'Hide Both Sections');
                s4Texts.forEach(el => el.innerText = 'Hide Section 4');
                s5Texts.forEach(el => el.innerText = 'Hide Section 5');
            }
        };

        window.toggleSection4 = function() {
            const s4 = document.getElementById('section4Seizures') || 
                       document.getElementById('seizuresDetailsContainer') || 
                       document.getElementById('seizuresListContainer');
            const s4Texts = document.querySelectorAll('.s4ToggleTextSpan');
            if (s4) {
                if (s4.classList.contains('hidden')) {
                    s4.classList.remove('hidden');
                    s4Texts.forEach(el => el.innerText = 'Hide Section 4');
                } else {
                    s4.classList.add('hidden');
                    s4Texts.forEach(el => el.innerText = 'Show Section 4');
                }
            }
        };

        window.toggleSection5 = function() {
            const { s5List } = getLabFormElements();
            const s5Texts = document.querySelectorAll('.s5ToggleTextSpan');
            if (s5List.length === 0) return;

            const isAnyVisible = s5List.some(el => !el.classList.contains('hidden'));
            s5List.forEach(el => {
                if (isAnyVisible) {
                    el.classList.add('hidden');
                } else {
                    el.classList.remove('hidden');
                }
            });
            s5Texts.forEach(el => el.innerText = isAnyVisible ? 'Show Section 5' : 'Hide Section 5');
        };
</script>

    <!-- EXECUTIVE SUCCESS POPOVER MESSAGE CONTAINER -->
    <div id="successPopoverModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-all duration-300">
        <div class="bg-white rounded-3xl border border-emerald-200 shadow-2xl max-w-md w-full p-6 space-y-5 animate-in fade-in zoom-in duration-200">
            <div class="flex items-start space-x-4">
                <div class="w-12 h-12 rounded-2xl bg-white text-emerald-600 border border-slate-200/90 flex items-center justify-center shrink-0 shadow-md shadow-slate-200/60">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-1">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono-code font-bold bg-amber-100 text-amber-800 border border-amber-200 uppercase tracking-wider">
                            STATUS: MULTI-UNIT PROCESSING (1/4 COMPLETED)
                        </span>
                        <button onclick="closeSuccessPopover()" class="text-slate-400 hover:text-slate-600 transition-colors p-1 cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <h3 class="text-base font-extrabold text-slate-900 leading-snug">
                        Crime Scene Data Saved Successfully!
                    </h3>
                    <p class="text-xs text-slate-600 mt-1.5 leading-relaxed font-medium">
                        Official Suspect Arrested Docket <span id="popoverCaseRef" class="font-extrabold font-mono-code text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">NCC-{{ date('Y') }}-XXXX</span> has been saved. The other 3 forensic units can now access, build on, and submit their findings before final HOF approval.
                    </p>
                </div>
            </div>

            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200/80 flex items-center justify-between text-xs">
                <div class="flex items-center space-x-2 text-slate-600">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="font-medium text-[11px]">Notice auto-closes in 1 min</span>
                </div>
                <span class="text-[10px] font-mono-code font-bold text-slate-400">NACOC SYSTEM</span>
            </div>

            <div class="flex items-center justify-end pt-2 border-t border-slate-100">
                <button onclick="closeSuccessPopover()" class="w-full sm:w-auto px-8 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold rounded-xl shadow-md shadow-emerald-600/30 transition-all cursor-pointer border-none">
                    Okay
                </button>
            </div>
        </div>
    </div>

    <!-- REQUIRED SUSPECT DETAILS VALIDATION ERROR POPOVER MODAL -->
    <div id="validationErrorModal" class="hidden fixed inset-0 z-[99999] flex items-center justify-center p-4 sm:p-6 bg-slate-900/60 backdrop-blur-xs transition-all duration-300">
        <div class="bg-white rounded-3xl border border-red-200 shadow-2xl max-w-3xl sm:max-w-4xl lg:max-w-5xl w-full p-7 sm:p-9 space-y-6 animate-in fade-in zoom-in duration-200">
            <div class="flex items-start space-x-4">
                <div class="w-14 h-14 rounded-2xl bg-red-600 text-white flex items-center justify-center shrink-0 shadow-lg shadow-red-600/30">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="px-3 py-1 rounded-full text-[11px] font-mono-code font-bold bg-red-100 text-red-800 border border-red-200 uppercase tracking-wider">
                            ACTION REQUIRED | MISSING DETAILS
                        </span>
                        <button type="button" onclick="closeValidationErrorPopover()" class="text-slate-400 hover:text-slate-600 transition-colors p-1 cursor-pointer">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <h3 class="text-xl font-extrabold text-slate-900 leading-snug">
                        Cannot Save Case Docket!
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-600 mt-1 leading-relaxed font-medium">
                        Please fill in all required suspect details before saving:
                    </p>
                </div>
            </div>

            <div class="p-5 sm:p-6 bg-red-50/70 rounded-2xl border border-red-200/80 min-h-[260px] max-h-[500px] overflow-y-auto custom-scrollbar">
                <ul id="validationErrorList" class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                    <!-- Dynamic list items injected here -->
                </ul>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                <span class="text-[10px] font-mono-code font-bold text-slate-400">NACOC SYSTEM</span>
                <button type="button" onclick="closeValidationErrorPopover()" class="w-full sm:w-auto px-7 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-extrabold rounded-xl shadow-md shadow-red-600/30 transition-all cursor-pointer border-none flex items-center justify-center space-x-2">
                    <span>Fill Required Details</span>
                </button>
            </div>
        </div>
    </div>

    <!-- SMART DUPLICATE CASE WARNING MODAL -->
    <div id="duplicateCaseModal" class="hidden fixed inset-0 z-[99999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-all duration-300">
        <div class="bg-white rounded-3xl border border-amber-200 shadow-2xl max-w-lg w-full p-6 space-y-5 animate-in fade-in zoom-in duration-200">
            <div class="flex items-start space-x-4">
                <div id="dupModalIconContainer" class="w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-lg shadow-amber-500/30">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-1">
                        <span id="dupModalBadge" class="px-2.5 py-0.5 rounded-full text-[10px] font-mono-code font-bold bg-amber-100 text-amber-800 border border-amber-200 uppercase tracking-wider">
                            POSSIBLE DUPLICATE CASE DETECTED
                        </span>
                        <button type="button" onclick="closeDuplicateCaseModal()" class="text-slate-400 hover:text-slate-600 transition-colors p-1 cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <h3 id="dupModalTitle" class="text-base font-extrabold text-slate-900 leading-snug">
                        Possible Duplicate Case Found
                    </h3>
                    <p id="dupModalSubtitle" class="text-xs text-slate-600 mt-1 leading-relaxed font-medium">
                        The details being entered closely match an existing case in the system. Please review the matched case file before proceeding.
                    </p>
                </div>
            </div>

            <!-- Matched Case Card -->
            <div id="dupModalCard" class="p-4 bg-amber-50/60 rounded-2xl border border-amber-200/80 space-y-3">
                <div class="flex items-center justify-between border-b border-amber-200/60 pb-2">
                    <div>
                        <span class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Matched Case Number</span>
                        <div id="dupModalCaseRef" class="font-extrabold font-mono-code text-sm text-slate-900">NCC-{{ date('Y') }}-0000</div>
                    </div>
                    <span id="dupModalScore" class="px-2.5 py-1 bg-amber-500 text-white font-mono-code font-bold text-[11px] rounded-full shadow-2xs">
                        95% Match
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div>
                        <span class="text-[10px] font-mono-code text-slate-500">Suspect Name:</span>
                        <div id="dupModalSuspectName" class="font-bold text-slate-800">N/A</div>
                    </div>
                    <div>
                        <span class="text-[10px] font-mono-code text-slate-500">Ghana Card #:</span>
                        <div id="dupModalGhanaCard" class="font-bold text-slate-800 font-mono-code">N/A</div>
                    </div>
                    <div>
                        <span class="text-[10px] font-mono-code text-slate-500">Date of Arrest:</span>
                        <div id="dupModalArrestDate" class="font-bold text-slate-800 font-mono-code">N/A</div>
                    </div>
                    <div>
                        <span class="text-[10px] font-mono-code text-slate-500">Raid / Scene Location:</span>
                        <div id="dupModalLocation" class="font-bold text-slate-800">N/A</div>
                    </div>
                </div>
                <div class="pt-2 border-t border-amber-200/60">
                    <span class="text-[10px] font-mono-code text-slate-500">Primary Offense:</span>
                    <div id="dupModalOffense" class="font-semibold text-slate-700 text-xs">N/A</div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeDuplicateCaseModal()" class="w-full sm:w-auto px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all cursor-pointer border border-slate-300">
                    Cancel
                </button>
                <button type="button" id="dupModalViewBtn" onclick="viewMatchedDuplicateCase()" class="w-full sm:w-auto px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-600/20 transition-all cursor-pointer border-none flex items-center justify-center space-x-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    <span>View Existing Case</span>
                </button>
                <button type="button" onclick="confirmAndRegisterDuplicateCase()" class="w-full sm:w-auto px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold rounded-xl shadow-md shadow-emerald-600/30 transition-all cursor-pointer border-none flex items-center justify-center space-x-1.5">
                    <span>Continue Registration</span>
                </button>
            </div>
        </div>
    </div>

    <!-- MUGSHOT LIGHTBOX MODAL -->
    <div id="arrestMugshotLightboxModal" class="fixed inset-0 z-[99999] bg-slate-900/80 backdrop-blur-md hidden flex items-center justify-center p-4" onclick="closeArrestMugshotModal()">
        <div class="relative max-w-4xl w-full bg-white rounded-3xl overflow-hidden shadow-2xl border border-slate-200" onclick="event.stopPropagation()">
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <span id="arrestMugshotModalTitle" class="font-extrabold text-sm uppercase tracking-wider font-mono-code">Facial Biometric High-Res View</span>
                </div>
                <button type="button" onclick="closeArrestMugshotModal()" class="text-slate-400 hover:text-white p-1 rounded-lg transition-colors border-none bg-transparent cursor-pointer">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 bg-slate-950 flex items-center justify-center min-h-[400px] max-h-[80vh] overflow-auto">
                <img id="arrestMugshotModalImage" src="" alt="Full High Res Mugshot" class="max-h-[70vh] max-w-full object-contain rounded-xl shadow-lg border border-slate-800">
            </div>
        </div>
    </div>
</body>
</html>
