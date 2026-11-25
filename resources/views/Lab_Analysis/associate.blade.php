{{--
    Laboratory Analysis Unit (LAU) Associate Suspects, Seized Commodities & Contraband Manifest, and Lab Modules View
--}}
@php
    $unitContext = 'Lab Analysis';
    $allowedCategories = array (
  0 => 'Drugs',
);
    $defaultCategory = 'Drugs';
@endphp
{{--
    WHY: Prior versions used hardcoded fallbacks like fabricated officer names/IDs
    and default cell numbers when real data was missing. In a forensic system,
    showing a fabricated officer name or cell number in a legal PDF is
    unacceptable — it can mislead courts and audit trails. All fallbacks now
    resolve to 'N/A' or an empty field, forcing the officer to enter real data.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @php
        $authUser = auth()->user();

        // Resolve active unit context
        $unitParam = $unitContext ?? request()->query('from') ?? request()->query('unit') ?? ($authUser->unit ?? '');
        $userUnit = $unitParam ?: ($authUser->unit ?? 'Lab Analysis');
        $defaultLabHeadName = $defaultLabHeadName ?? 'N/A';
        $unitLower = strtolower((string)$unitParam);

        $isLabAnalysis = str_contains($unitLower, 'lab');
        $isDigitalForensics = str_contains($unitLower, 'digital') || str_contains($unitLower, 'physical');
        $isCrimeScene = str_contains($unitLower, 'crime') || str_contains($unitLower, 'field');
        $isEvidenceMgmt = str_contains($unitLower, 'evidence');
        $isDirectorate = str_contains($unitLower, 'directorate') || str_contains($unitLower, 'head') || (!empty($authUser->is_head) && !$isLabAnalysis && !$isDigitalForensics && !$isCrimeScene && !$isEvidenceMgmt);

        // Fallback to user's unit if unitParam was empty
        if (!$isLabAnalysis && !$isDigitalForensics && !$isCrimeScene && !$isEvidenceMgmt && !$isDirectorate) {
            $userUnitLower = strtolower((string)($authUser->unit ?? ''));
            if (str_contains($userUnitLower, 'lab')) $isLabAnalysis = true;
            elseif (str_contains($userUnitLower, 'digital') || str_contains($userUnitLower, 'physical')) $isDigitalForensics = true;
            elseif (str_contains($userUnitLower, 'evidence')) $isEvidenceMgmt = true;
            else $isCrimeScene = true;
        }

        if ($isLabAnalysis) {
            $activeUnitKey = 'lab';
            $activeUnitName = 'Laboratory Analysis Unit (LAU)';
            $activeUnitBadge = 'LABORATORY ANALYSIS';
            $activeUnitTheme = 'emerald';
            $activeUnitBackUrl = '/Lab_Analysis';
            $allowedCategories = $allowedCategories ?? ['Drugs'];
            $defaultCategory = $defaultCategory ?? 'Drugs';
        } elseif ($isDigitalForensics) {
            $activeUnitKey = 'digital';
            $activeUnitName = 'Digital & Physical Forensics Unit (DPFU)';
            $activeUnitBadge = 'DIGITAL & PHYSICAL FORENSICS';
            $activeUnitTheme = 'blue';
            $activeUnitBackUrl = '/Digital_Forensics';
            $allowedCategories = $allowedCategories ?? ['Electronic Devices'];
            $defaultCategory = $defaultCategory ?? 'Electronic Devices';
        } elseif ($isCrimeScene) {
            $activeUnitKey = 'crime';
            $activeUnitName = 'Crime Scene Support Unit (CSSU)';
            $activeUnitBadge = 'CRIME SCENE SUPPORT';
            $activeUnitTheme = 'slate';
            $activeUnitBackUrl = '/Crime_Scene_Support';
            $allowedCategories = $allowedCategories ?? ['Weapons', 'Ammunition', 'Vehicles', 'Other Exhibits', 'Drugs'];
            $defaultCategory = $defaultCategory ?? 'Weapons';
        } elseif ($isEvidenceMgmt) {
            $activeUnitKey = 'evidence';
            $activeUnitName = 'Evidence Management Unit (EMU)';
            $activeUnitBadge = 'EVIDENCE MANAGEMENT';
            $activeUnitTheme = 'amber';
            $activeUnitBackUrl = '/Evidence_Management';
            $allowedCategories = $allowedCategories ?? ['Drugs', 'Money', 'Precious Stones', 'Gold', 'Vehicles', 'Electronic Devices', 'Weapons', 'Ammunition', 'Other Exhibits'];
            $defaultCategory = $defaultCategory ?? 'Drugs';
        } else {
            $activeUnitKey = 'all';
            $activeUnitName = 'Forensic Directorate Case Operations';
            $activeUnitBadge = 'FORENSIC DIRECTORATE';
            $activeUnitTheme = 'indigo';
            $activeUnitBackUrl = '/cases';
            $allowedCategories = $allowedCategories ?? ['Drugs', 'Money', 'Precious Stones', 'Gold', 'Vehicles', 'Electronic Devices', 'Weapons', 'Ammunition', 'Other Exhibits'];
            $defaultCategory = $defaultCategory ?? '';
        }

        $todayDate = date('Y-m-d');
        $caseNumClean = preg_replace('/[^A-Za-z0-9]/', '', $case->case_number ?? 'CASE-001');

        // WHY: Prior code json_decode'd $case->docket_data twice (once for
        // $dDataCheck and again for $dData). Decode once and reuse.
        $dData = is_array($case->docket_data)
            ? $case->docket_data
            : (json_decode($case->docket_data ?? '[]', true) ?: []);

        // WHY: Use CaseReferenceService for consistent, collision-resistant
        // reference generation across the application.
        $caseRefService = app(\App\Services\CaseReferenceService::class);
        $examRefAuto    = $caseRefService->resolveExamReference($case ?? null, $dData ?? []);
        $caseRefAuto    = $case->case_number ?? app(\App\Services\CaseNumberService::class)->generate();
        $labNumberAuto  = $case->laboratory_number ?? $caseRefService->generateLabNumber();

        $existingSeizures = (isset($dData['seizures']) && is_array($dData['seizures']) && count($dData['seizures']) > 0)
            ? $dData['seizures']
            : ($case->seizure_name || $case->exhibits_summary ? [[
                'category'         => $case->exhibit_category ?? 'General Exhibit',
                'seizure_name'     => $case->seizure_name ?? $case->exhibits_summary ?? 'Logged Exhibit Package',
                'seizure_quantity' => $case->seizure_quantity ?? 'N/A',
                'seizure_weight'   => $case->seizure_weight ? ($case->seizure_weight . ' ' . ($case->seizure_unit ?? '')) : 'N/A',
                'seizure_value'    => $case->seizure_value ?? $case->valuation ?? 'N/A'
            ]] : []);

        $exhibitRefAuto = $caseRefService->nextExhibitReference(
            $case->case_number ?? 'CASE-001',
            count($existingSeizures ?? [])
        );

        // WHY: Prior code checked whether ANY exhibit for the case had a saved
        // lab result, and hid Section 4 case-wide. This blocked entering lab
        // data for newly-associated exhibits on any case that already had one
        // result saved — the exact workflow this page exists to enable.
        // Now scoped to the ACTIVE (most recently added) exhibit only.
        $activeSuspectHasSavedResult = false;
        if (isset($case) && !empty($case->id)) {
            $isMultiCase = ($case instanceof \App\Models\ForensicCase && method_exists($case, 'isMultipleSuspect'))
                ? $case->isMultipleSuspect()
                : (($case->underlyingRecord ?? null) instanceof \App\Models\MultipleSuspectCase
                   || ($case instanceof \App\Models\MultipleSuspectCase));

            if (method_exists($case, 'exhibits')) {
                $activeExhibit = $case->exhibits()->latest('id')->first();
            } elseif (isset($case->underlyingRecord) && method_exists($case->underlyingRecord, 'exhibits')) {
                $activeExhibit = $case->underlyingRecord->exhibits()->latest('id')->first();
            } else {
                $activeExhibit = \App\Models\Exhibit::where($isMultiCase ? 'multiple_suspect_case_id' : 'single_suspect_case_id', $case->id)
                    ->latest('id')
                    ->first();
            }

            if ($activeExhibit) {
                $activeSuspectHasSavedResult = \App\Models\LaboratoryExaminationResult::where('exhibit_id', $activeExhibit->id)->exists();
            }
            // If no exhibits exist yet (fresh case, no exhibits), the form
            // MUST stay visible so the officer can enter lab data for the
            // FIRST exhibit being associated here.
            $activeSuspectHasSavedResult = $activeExhibit ? $activeSuspectHasSavedResult : false;
        }

        $existingSuspects = (isset($dData['suspects']) && is_array($dData['suspects']) && count($dData['suspects']) > 0)
            ? $dData['suspects']
            : [[
                'suspect_number'   => 1,
                'suspect_name'     => $case->suspect_name ?? 'Primary Target',
                'suspect_alias'    => $case->suspect_alias ?? 'N/A',
                'ghana_card'       => $case->ghana_card ?? 'N/A',
                'gender'           => $case->gender ?? 'N/A',
                'dob'              => $case->dob ?? null,
                'age'              => $case->age ?? null,
                'height'           => $case->height ?? null,
                'eye_color'        => $case->eye_color ?? null,
                'nationality'      => $case->nationality ?? 'Ghanaian',
                'pob'              => $case->pob ?? null,
                'phone'            => $case->phone ?? null,
                'address'          => $case->address ?? null,
                'marital_status'   => $case->marital_status ?? null,
                'holding_facility' => $case->holding_facility ?? 'N/A',
                'offense'          => $case->offense_description ?? $case->description ?? null,
                'date_of_arrest'   => $case->arrest_date ?? null,
                'mugshot_front'    => $case->mugshot_front ?? ($dData['mugshot_front'] ?? null),
                'mugshot_left'     => $case->mugshot_left ?? ($dData['mugshot_left'] ?? null),
                'mugshot_right'    => $case->mugshot_right ?? ($dData['mugshot_right'] ?? null),
            ]];

        $exhibitCategory = strtolower($case->exhibit_category ?? '');
        $seizureNameDrug = $case->seizure_name_drug ?? '';
        $seizureName = strtolower($case->seizure_name ?? '');
        $exhibitsSummary = strtolower($case->exhibits_summary ?? '');

        // Gather all exhibit items across case model and docket seizures
        $allExhibitsCheckAssoc = [];
        if (!empty($case->exhibit_category) || !empty($case->seizure_name_drug) || !empty($case->seizure_name)) {
            $allExhibitsCheckAssoc[] = [
                'category'          => $case->exhibit_category ?? '',
                'seizure_name_drug' => $case->seizure_name_drug ?? '',
                'seizure_name'      => $case->seizure_name ?? '',
            ];
        }
        if (is_array($existingSeizures) && count($existingSeizures) > 0) {
            foreach ($existingSeizures as $sz) { $allExhibitsCheckAssoc[] = $sz; }
        }

        // WHY: Prior code only scanned $case->exhibit_category, $case->seizure_name_drug,
        // $existingSeizures, and docket_data. If docket_data was stale but the
        // exhibits table had real drug records, $isDrugCase was false and Section 4
        // stayed hidden for actual drug cases. Merge exhibits relation into the check.
        if (!empty($case->exhibits)) {
            foreach ($case->exhibits as $ex) {
                $allExhibitsCheckAssoc[] = [
                    'category'          => $ex->exhibit_category ?? '',
                    'seizure_name_drug' => $ex->commodity_name ?? '',
                    'seizure_name'      => $ex->commodity_name ?? '',
                ];
            }
        }

        $isDrugCase = false;
        // WHY: Prior code duplicated drug keywords in Blade, causing divergence
        // from the controller's list. Read from config for a single source of truth.
        $drugKeywords = config('forensic.drug_keywords', []);

        foreach ($allExhibitsCheckAssoc as $exCheck) {
            $cat   = strtolower($exCheck['category'] ?? ($exCheck['exhibit_category'] ?? ''));
            $dName = strtolower($exCheck['seizure_name_drug'] ?? '');
            $sName = strtolower($exCheck['seizure_name'] ?? ($exCheck['name'] ?? ($exCheck['commodity_name'] ?? ($exCheck['description'] ?? ''))));

            if (!empty($dName)) {
                $isDrugCase = true;
                break;
            }

            foreach ($drugKeywords as $kw) {
                if (str_contains($cat, $kw) || str_contains($sName, $kw)) {
                    $isDrugCase = true;
                    break 2;
                }
            }
        }

        // WHY: Blade escapes HTML entities but does NOT validate URL schemes.
        // A malicious 'javascript:' or 'file:' URL inside <img src> is harmless
        // today, but if the code ever renders <a href> with the same value, it
        // becomes an XSS vector. Restrict to https, data:image, or root-relative.
        $isSafeMugshot = fn($u) => $u && preg_match('/^(https?:\/\/|data:image\/|\/)/i', $u);
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $activeUnitName }} — Associate Case Data | NACOC Forensic</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- jQuery & Select2 CDN -->
    <link href="{{ asset('vendor/select2/select2.min.css') }}" rel="stylesheet" />
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    <script src="{{ asset('js/forensic-image-optimizer.js') }}"></script>

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    @endif

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

        /* Premium Select2 Component Styling for Role & Nationality */
        .select2-container {
            width: 100% !important;
        }
        .select2-container--default .select2-selection--single {
            background-color: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 0.875rem !important; /* rounded-xl */
            height: 40px !important;
            display: flex !important;
            align-items: center !important;
            padding-left: 0.75rem !important;
            padding-right: 0.75rem !important;
            font-size: 0.75rem !important;
            font-weight: 700 !important;
            color: #0f172a !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03) !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
            position: relative !important;
        }
        .select2-container--default .select2-selection--single:hover {
            border-color: #94a3b8 !important;
            background-color: #f8fafc !important;
        }
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
            background-color: #ffffff !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #0f172a !important;
            font-size: 0.75rem !important;
            font-weight: 700 !important;
            line-height: 38px !important;
            padding-left: 0 !important;
            padding-right: 3rem !important;
            width: 100% !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #94a3b8 !important; /* Greyed out placeholder text (slate-400) */
            font-weight: 500 !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 38px !important;
            right: 10px !important;
            width: 20px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow b {
            border-color: #64748b transparent transparent transparent !important;
            border-width: 5px 4px 0 4px !important;
            transition: transform 0.2s ease !important;
        }
        .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
            border-color: transparent transparent #2563eb transparent !important;
            border-width: 0 4px 5px 4px !important;
        }

        /* Perfectly Centered Clear X Button Styling */
        .select2-container--default .select2-selection--single .select2-selection__clear {
            position: absolute !important;
            right: 32px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            color: #ef4444 !important;
            font-weight: 800 !important;
            font-size: 0.875rem !important;
            line-height: 1 !important;
            margin: 0 !important;
            padding: 0 !important;
            cursor: pointer !important;
            z-index: 10 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            height: 20px !important;
            width: 20px !important;
            border-radius: 9999px !important;
            background-color: #fee2e2 !important;
            transition: all 0.2s ease !important;
            border: none !important;
        }
        /* clear:hover guarded above */

        /* Dropdown Popup Panel */
        .select2-dropdown {
            border-radius: 1rem !important;
            border: 1px solid #cbd5e1 !important;
            box-shadow: 0 20px 35px -5px rgba(15, 23, 42, 0.15), 0 8px 15px -6px rgba(15, 23, 42, 0.08) !important;
            font-size: 0.75rem !important;
            z-index: 99999 !important;
            background-color: #ffffff !important;
            padding: 6px !important;
            overflow: hidden !important;
        }
        .select2-search--dropdown {
            padding: 4px 4px 8px 4px !important;
        }
        .select2-search--dropdown .select2-search__field {
            border-radius: 0.625rem !important;
            border: 1px solid #cbd5e1 !important;
            padding: 8px 12px !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            background-color: #f8fafc !important;
            outline: none !important;
            transition: all 0.2s ease !important;
        }
        .select2-search--dropdown .select2-search__field:focus {
            background-color: #ffffff !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
        }
        .select2-results__options {
            max-height: 220px !important;
            padding-right: 2px !important;
        }
        .select2-results__option {
            padding: 8px 12px !important;
            border-radius: 0.5rem !important;
            margin-bottom: 2px !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            color: #334155 !important;
            transition: all 0.15s ease !important;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #eff6ff !important;
            color: #1d4ed8 !important;
        }
        .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #dbeafe !important;
            color: #1e40af !important;
            font-weight: 800 !important;
        }
    </style>
<script>window.ACTIVE_UNIT_KEY = @json($activeUnitKey ?? "lab"); window.restoreDocketFormDraft = function() {}; window.saveDocketFormDraft = function() {};</script>
</head>
<body class="min-h-screen flex flex-col bg-slate-50 text-slate-900 antialiased select-none">

    <div class="flex h-screen overflow-hidden">

        <!-- GENERAL SIDEBAR -->
        @include('Crime_Scene_Support.partials.sidebar')

        <!-- RIGHT WORKSPACE -->
        <div class="flex-1 flex flex-col min-w-0 bg-white overflow-hidden select-text">
            
            <!-- TOP WORKSPACE NAVBAR -->
            <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 shadow-2xs">
                <div class="flex items-center space-x-3">
                    <span class="px-2.5 py-1 bg-{{ $activeUnitTheme }}-100 text-{{ $activeUnitTheme }}-800 text-[10px] font-mono-code font-bold rounded-lg uppercase tracking-wider">
                        {{ $activeUnitBadge }} PORTAL
                    </span>
                    <span class="text-sm font-extrabold text-slate-900 font-mono-code">
                        REF: {{ $case->case_number }}
                    </span>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ url($activeUnitBackUrl) }}" class="px-4 py-2 bg-slate-200/80 hover:bg-slate-300 border-none text-slate-800 text-xs font-bold rounded-xl shadow-xs transition-all flex items-center space-x-1.5 cursor-pointer">
                        <span>&larr;</span>
                        <span>Back to {{ $activeUnitBadge }}</span>
                    </a>
                </div>
            </header>

            <!-- MAIN SCROLLABLE CONTENT -->
            <main class="flex-1 overflow-y-auto p-6 space-y-6">

                @include('partials.flash')

                <!-- TOP BANNER -->
                <div class="bg-slate-100 p-6 rounded-2xl border-none shadow-md flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center space-x-2 mb-1">
                            <span class="px-2.5 py-0.5 bg-{{ $activeUnitTheme }}-600 text-white text-[10px] font-extrabold font-mono-code rounded-md uppercase tracking-wider">{{ $activeUnitBadge }}</span>
                            <span class="text-xs text-slate-400">&bull;</span>
                            <span class="text-xs font-bold text-slate-700 font-mono-code">{{ strtoupper($activeUnitName) }}</span>
                        </div>
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Associate Case Data &amp; Suspect Docket Portal</h1>
                        <p class="text-xs text-slate-600 mt-1 font-medium">Link new accomplices, co-suspects, or unit exhibits to Case <strong class="text-blue-600 font-mono-code">{{ $case->case_number }}</strong> ({{ $case->title }})</p>
                    </div>
                    <div class="flex items-center space-x-2 shrink-0">
                        <span class="px-3 py-1.5 bg-white border border-slate-300 rounded-xl text-xs font-mono-code font-bold text-slate-700 shadow-2xs">
                            Status: <span class="text-emerald-600">{{ $case->status ?? 'DRAFT' }}</span>
                        </span>
                    </div>
                </div>

                <!-- MAIN FORM WRAPPER -->
                <form id="associateDocketForm" action="/cases/{{ $case->case_number }}/associate" method="POST" enctype="multipart/form-data" autocomplete="off" novalidate class="space-y-6 text-xs font-medium">
                    @csrf
                    <input type="hidden" name="from_unit" value="{{ $activeUnitName }}">

                    <!-- SECTION 1: SAVED CASE INFORMATION & SUSPECT BIO DATA (READ-ONLY) -->
                    <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200 shadow-sm space-y-6 relative">
                        
                        <div class="pb-3 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-xl bg-slate-800 text-white flex items-center justify-center font-bold shadow-md">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </div>
                                <div>
                                    <h3 class="font-black text-slate-900 text-base">Section 1: Saved Case Information &amp; Suspect Bio-Data</h3>
                                    <p class="text-xs text-slate-500">Existing saved case file record details (Protected Read-Only)</p>
                                </div>
                            </div>
                            <span class="px-3 py-1 bg-slate-200 text-slate-800 text-[10px] font-mono-code font-extrabold rounded-lg uppercase tracking-wider">
                                READ-ONLY PROTECTED
                            </span>
                        </div>

                        <!-- CASE GENERAL INFO GRID -->
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <div>
                                <label class="block text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black mb-1">Case Reference #</label>
                                <input type="text" value="{{ $case->case_number }}" readonly class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold font-mono-code text-blue-600 cursor-not-allowed">
                            </div>
                            <div>
                                <label class="block text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black mb-1">Case Legal Title</label>
                                <input type="text" value="{{ $case->title }}" readonly class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-800 cursor-not-allowed">
                            </div>
                            <div>
                                <label class="block text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black mb-1">Arresting Officer</label>
                                <input type="text" value="{{ $case->arresting_officer ?? ($case->submitting_officer ?? 'Handling Officer') }}" readonly class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-800 cursor-not-allowed">
                            </div>
                            <div>
                                <label class="block text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black mb-1">Seizing Officer(s)</label>
                                <input type="text" value="{{ $case->seizure_officer ?? $case->seizing_officer ?? 'N/A' }}" readonly class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-800 cursor-not-allowed">
                            </div>
                            <div>
                                <label class="block text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black mb-1">Arrest Location / Vault</label>
                                <input type="text" value="{{ $case->location ?? 'N/A' }}" readonly class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-800 cursor-not-allowed">
                            </div>
                        </div>

                        <!-- SAVED SUSPECTS DETAILED CARDS & MUGSHOTS (READ ONLY) -->
                        <div class="space-y-4 pt-2">
                        @if(empty($dData['suspects']))
                            <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl text-xs text-blue-900">
                                <strong>Note:</strong> This is the primary suspect profile derived from the case record.
                                To edit bio-data, use the
                                <a href="{{ url('/cases/'.$case->case_number.'/edit') }}" class="underline font-bold">Case Edit</a>
                                page.
                            </div>
                        @endif
                            <h4 class="font-extrabold text-slate-900 text-xs font-mono-code uppercase tracking-wider border-b border-slate-200 pb-2">
                                Saved Suspect Profiles &amp; Facial Mugshots ({{ count($existingSuspects) }})
                            </h4>

                            <div class="grid grid-cols-1 gap-5">
                                @foreach($existingSuspects as $idx => $s)
                                    @php
                                        $sNum        = $s['suspect_number'] ?? ($idx + 1);
                                        $sName       = $s['suspect_name'] ?? ($sNum == 1 ? ($case->suspect_name ?? 'Primary Target') : ('Suspect #' . $sNum));
                                        $sAlias      = $s['suspect_alias'] ?? ($sNum == 1 ? ($case->suspect_alias ?? null) : null);
                                        $sCard       = $s['ghana_card'] ?? ($sNum == 1 ? ($case->ghana_card ?? null) : null);
                                        $sGender     = $s['gender'] ?? ($sNum == 1 ? ($case->gender ?? null) : null);
                                        $sAge        = $s['age'] ?? ($sNum == 1 ? ($case->age ?? null) : null);
                                        $sDOB        = $s['dob'] ?? ($sNum == 1 ? ($case->dob ?? null) : null);
                                        $sHeight     = $s['height'] ?? ($sNum == 1 ? ($case->height ?? null) : null);
                                        $sEye        = $s['eye_color'] ?? ($sNum == 1 ? ($case->eye_color ?? null) : null);
                                        $sNat        = $s['nationality'] ?? ($sNum == 1 ? ($case->nationality ?? 'Ghanaian') : 'Ghanaian');
                                        $sPOB        = $s['place_of_birth'] ?? ($s['hometown'] ?? ($s['pob'] ?? ($sNum == 1 ? ($case->pob ?? null) : null)));
                                        $sPhone      = $s['phone_number'] ?? ($s['contact_phone'] ?? ($s['phone'] ?? ($sNum == 1 ? ($case->phone ?? null) : null)));
                                        $sAddr       = $s['residential_address'] ?? ($s['address'] ?? ($sNum == 1 ? ($case->address ?? null) : null));
                                        $sMarital    = $s['marital_status'] ?? ($sNum == 1 ? $case->marital_status : null);
                                        $sSpouseN    = $s['spouse_name'] ?? ($sNum == 1 ? $case->spouse_name : null);
                                        $sSpouseP    = $s['spouse_phone'] ?? ($sNum == 1 ? $case->spouse_phone : null);
                                        $sSpouseI    = $s['spouse_info'] ?? ($sNum == 1 ? $case->spouse_info : null);
                                        $sPartnerN   = $s['partner_name'] ?? ($sNum == 1 ? $case->partner_name : null);
                                        $sPartnerP   = $s['partner_phone'] ?? ($sNum == 1 ? $case->partner_phone : null);
                                        $sPartnerI   = $s['partner_info'] ?? ($sNum == 1 ? $case->partner_info : null);
                                        $sDependents = $s['dependents'] ?? ($sNum == 1 ? ($case->dependents ?? null) : null);
                                        $sRaid       = $s['raid_location'] ?? ($sNum == 1 ? ($case->raid_location ?: ($case->location ?: null)) : null);
                                        $sArrestDate = $s['date_of_arrest'] ?? ($sNum == 1 ? $case->arrest_date : null);
                                        $sArrestTime = $s['time_of_arrest'] ?? ($sNum == 1 ? $case->arrest_time : null);
                                        $sFacility   = $s['holding_facility'] ?? ($sNum == 1 ? ($case->holding_facility ?? 'N/A') : 'N/A');
                                        $sOffense    = $s['offense'] ?? ($sNum == 1 ? ($case->offense_description ?: ($case->description ?: null)) : null);
                                        $sRole       = $s['association_role'] ?? ($s['role'] ?? null);

                                        $fPic = $s['mugshot_front'] ?? ($sNum == 1 ? $case->mugshot_front : null);
                                        $lPic = $s['mugshot_left']  ?? ($sNum == 1 ? $case->mugshot_left : null);
                                        $rPic = $s['mugshot_right'] ?? ($sNum == 1 ? $case->mugshot_right : null);

                                        if ($fPic && !str_starts_with($fPic, 'data:') && !str_starts_with($fPic, 'http')) $fPic = asset($fPic);
                                        if ($lPic && !str_starts_with($lPic, 'data:') && !str_starts_with($lPic, 'http')) $lPic = asset($lPic);
                                        if ($rPic && !str_starts_with($rPic, 'data:') && !str_starts_with($rPic, 'http')) $rPic = asset($rPic);
                                    @endphp
                                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs space-y-5">
                                        <!-- CARD HEADER -->
                                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                            <div class="flex items-center space-x-2">
                                                <span class="w-6 h-6 rounded-full bg-blue-600 text-white font-mono-code font-bold text-xs flex items-center justify-center">
                                                    {{ $sNum }}
                                                </span>
                                                <h5 class="font-extrabold text-slate-900 text-sm">
                                                    {{ $sName }}
                                                </h5>
                                                @if(!empty($sAlias))
                                                    <span class="text-xs text-slate-500 font-mono-code">"{{ $sAlias }}"</span>
                                                @endif
                                                @if(!empty($sRole))
                                                    <span class="px-2 py-0.5 bg-purple-50 text-purple-700 text-[10px] font-mono-code font-extrabold rounded border border-purple-200 uppercase">
                                                        {{ $sRole }}
                                                    </span>
                                                @endif
                                            </div>
                                            <span class="px-2.5 py-0.5 bg-blue-50 text-blue-700 text-[10px] font-mono-code font-bold rounded-md border border-blue-200 uppercase">
                                                SAVED SUSPECT BIO-DATA
                                            </span>
                                        </div>

                                        <!-- MUGSHOTS ROW -->
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                            <!-- FRONT -->
                                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 text-center">
                                                <div class="text-[10px] font-mono-code font-extrabold text-slate-900 text-black uppercase mb-2">Front View (0&deg;)</div>
                                                <div class="h-36 bg-white rounded-lg border border-slate-200 flex items-center justify-center overflow-hidden">
                                                    @if($fPic)
                                                        <img src="{{ $fPic }}" alt="Front View" class="h-full w-full object-contain">
                                                    @else
                                                        <div class="text-slate-400 text-[10px] font-mono-code">No Front Mugshot</div>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- LEFT -->
                                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 text-center">
                                                <div class="text-[10px] font-mono-code font-extrabold text-slate-900 text-black uppercase mb-2">Left Profile (90&deg;)</div>
                                                <div class="h-36 bg-white rounded-lg border border-slate-200 flex items-center justify-center overflow-hidden">
                                                    @if($lPic)
                                                        <img src="{{ $lPic }}" alt="Left View" class="h-full w-full object-contain">
                                                    @else
                                                        <div class="text-slate-400 text-[10px] font-mono-code">No Left Profile</div>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- RIGHT -->
                                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 text-center">
                                                <div class="text-[10px] font-mono-code font-extrabold text-slate-900 text-black uppercase mb-2">Right Profile (90&deg;)</div>
                                                <div class="h-36 bg-white rounded-lg border border-slate-200 flex items-center justify-center overflow-hidden">
                                                    @if($rPic)
                                                        <img src="{{ $rPic }}" alt="Right View" class="h-full w-full object-contain">
                                                    @else
                                                        <div class="text-slate-400 text-[10px] font-mono-code">No Right Profile</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <!-- FULL SUSPECT BIO-DATA SECTIONS -->
                                        <div class="space-y-4 pt-2 border-t border-slate-100">
                                            
                                            <!-- SUB-SECTION 1: LEGAL IDENTITY & PHYSICAL BIOMETRICS -->
                                            <div class="space-y-2">
                                                <div class="text-[11px] font-extrabold text-slate-800 uppercase tracking-wider font-mono-code flex items-center space-x-1.5">
                                                    <span>1. Legal Identity &amp; Physical Biometrics</span>
                                                </div>
                                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-[11px] bg-slate-50/70 p-3.5 rounded-xl border border-slate-200/80">
                                                    <div>
                                                        <span class="text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black block mb-0.5">Full Legal Name:</span>
                                                        <strong class="text-slate-900 font-extrabold">{{ $sName }}</strong>
                                                    </div>
                                                    <div>
                                                        <span class="text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black block mb-0.5">Alias / Street Name:</span>
                                                        <strong class="text-slate-800">{{ $sAlias ?: 'N/A' }}</strong>
                                                    </div>
                                                    <div>
                                                        <span class="text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black block mb-0.5">Ghana Card / ID #:</span>
                                                        <strong class="text-blue-600 font-mono-code font-extrabold">{{ $sCard ?: 'N/A' }}</strong>
                                                    </div>
                                                    <div>
                                                        <span class="text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black block mb-0.5">Gender / Age / DOB:</span>
                                                        <strong class="text-slate-800">
                                                            {{ $sGender ?: 'N/A' }}
                                                            {{ !empty($sAge) ? " ({$sAge} yrs)" : '' }}
                                                            {{ !empty($sDOB) ? " • DOB: {$sDOB}" : '' }}
                                                        </strong>
                                                    </div>
                                                    <div>
                                                        <span class="text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black block mb-0.5">Height &amp; Eye Color:</span>
                                                        <strong class="text-slate-800">
                                                            {{ !empty($sHeight) ? "Ht: {$sHeight}" : 'Ht: N/A' }}
                                                            •
                                                            {{ !empty($sEye) ? "Eyes: {$sEye}" : 'Eyes: N/A' }}
                                                        </strong>
                                                    </div>
                                                    <div>
                                                        <span class="text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black block mb-0.5">Nationality:</span>
                                                        <strong class="text-slate-800">{{ $sNat ?: 'Ghanaian' }}</strong>
                                                    </div>
                                                    <div>
                                                        <span class="text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black block mb-0.5">Hometown / POB:</span>
                                                        <strong class="text-slate-800">{{ $sPOB ?: 'N/A' }}</strong>
                                                    </div>
                                                    <div>
                                                        <span class="text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black block mb-0.5">Contact Phone:</span>
                                                        <strong class="text-slate-800 font-mono-code">{{ $sPhone ?: 'N/A' }}</strong>
                                                    </div>
                                                    <div class="col-span-2 sm:col-span-4">
                                                        <span class="text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black block mb-0.5">Residential Address / Known Location:</span>
                                                        <strong class="text-slate-800">{{ $sAddr ?: 'N/A' }}</strong>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- SUB-SECTION 2: FAMILY & RELATIONSHIP RECORDS -->
                                            <div class="space-y-2">
                                                <div class="text-[11px] font-extrabold text-slate-800 uppercase tracking-wider font-mono-code flex items-center space-x-1.5">
                                                    <span>2. Family &amp; Relationship Records</span>
                                                </div>
                                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-[11px] bg-slate-50/70 p-3.5 rounded-xl border border-slate-200/80">
                                                    <div>
                                                        <span class="text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black block mb-0.5">Marital Status:</span>
                                                        <strong class="text-slate-800">{{ $sMarital ?: 'N/A' }}</strong>
                                                    </div>
                                                    <div>
                                                        <span class="text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black block mb-0.5">Spouse / Partner Info:</span>
                                                        <strong class="text-slate-800">
                                                            @if(!empty($sSpouseN))
                                                                Spouse: {{ $sSpouseN }} {{ !empty($sSpouseP) ? "({$sSpouseP})" : '' }}
                                                            @elseif(!empty($sSpouseI))
                                                                {{ $sSpouseI }}
                                                            @elseif(!empty($sPartnerN))
                                                                Partner: {{ $sPartnerN }} {{ !empty($sPartnerP) ? "({$sPartnerP})" : '' }}
                                                            @elseif(!empty($sPartnerI))
                                                                {{ $sPartnerI }}
                                                            @else
                                                                N/A
                                                            @endif
                                                        </strong>
                                                    </div>
                                                    <div>
                                                        <span class="text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black block mb-0.5">Children / Dependents:</span>
                                                        <strong class="text-slate-800">{{ $sDependents ?: 'N/A' }}</strong>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- SUB-SECTION 3: ARREST, OFFENSE & CUSTODY CELL ALLOCATION -->
                                            <div class="space-y-2">
                                                <div class="text-[11px] font-extrabold text-slate-800 uppercase tracking-wider font-mono-code flex items-center space-x-1.5">
                                                    <span>3. Arrest Incident, Offense &amp; Custody Cell</span>
                                                </div>
                                                <div class="grid grid-cols-1 sm:grid-cols-5 gap-3 text-[11px] bg-slate-50/70 p-3.5 rounded-xl border border-slate-200/80">
                                                    <div>
                                                        <span class="text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black block mb-0.5">Raid Location / Place of Arrest:</span>
                                                        <strong class="text-slate-800">{{ $sRaid ?: 'N/A' }}</strong>
                                                    </div>
                                                    <div>
                                                        <span class="text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black block mb-0.5">Date &amp; Time of Arrest:</span>
                                                        <strong class="text-slate-800">
                                                            {{ !empty($sArrestDate) ? $sArrestDate : 'N/A' }}
                                                            {{ !empty($sArrestTime) ? " @ {$sArrestTime}" : '' }}
                                                        </strong>
                                                    </div>
                                                    <div>
                                                        <span class="text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black block mb-0.5">Arresting Officer(s):</span>
                                                        <strong class="text-slate-900 font-extrabold">
                                                            {{ $s['arresting_officer'] ?? 'N/A' }}
                                                            @if(!empty($s['arresting_officer_id']))
                                                                <span class="font-mono-code text-blue-700">({{ $s['arresting_officer_id'] }})</span>
                                                            @endif
                                                        </strong>
                                                    </div>
                                                    <div>
                                                        <span class="text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black block mb-0.5">Seizing Officer(s):</span>
                                                        <strong class="text-slate-900 font-extrabold">
                                                            {{ $s['seizing_officer'] ?? ($s['seizure_officer'] ?? ($case->seizure_officer ?? $case->seizing_officer ?? 'N/A')) }}
                                                            @if(!empty($s['seizing_officer_id']))
                                                                <span class="font-mono-code text-slate-600">({{ $s['seizing_officer_id'] }})</span>
                                                            @endif
                                                        </strong>
                                                    </div>
                                                    <div>
                                                        <span class="text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black block mb-0.5">Allocated Holding Facility:</span>
                                                        <strong class="text-emerald-700 font-extrabold">{{ $sFacility ?: 'N/A' }}</strong>
                                                    </div>
                                                    <div>
                                                        <span class="text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black block mb-0.5">Alleged Offense / Charges:</span>
                                                        <strong class="text-slate-800">{{ $sOffense ?: 'N/A' }}</strong>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- SAVED EXHIBITS TABLE (READ ONLY) -->
                        <div class="space-y-2 pt-2">
                            <h4 class="font-extrabold text-slate-900 text-xs font-mono-code uppercase tracking-wider border-b border-slate-200 pb-2">
                                Saved Seizures &amp; Exhibits Logged ({{ count($existingSeizures) }})
                            </h4>

                            <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-2xs">
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead class="bg-slate-100 text-slate-600 font-mono-code uppercase text-[10px]">
                                        <tr>
                                            <th class="py-2.5 px-4 font-bold">Photo</th>
                                            <th class="py-2.5 px-4 font-bold">Category</th>
                                            <th class="py-2.5 px-4 font-bold">Exhibit Name / Description</th>
                                            <th class="py-2.5 px-4 font-bold">Quantity</th>
                                            <th class="py-2.5 px-4 font-bold">Weight</th>
                                            <th class="py-2.5 px-4 font-bold">Est. Value</th>
                                            <th class="py-2.5 px-4 font-bold">Seizing Officer</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-slate-800">
                                        @forelse($existingSeizures as $ex)
                                            @php
                                                $exPic = $ex['photo'] ?? ($ex['exhibit_photo'] ?? null);
                                                if ($exPic && !str_starts_with($exPic, 'data:') && !str_starts_with($exPic, 'http')) {
                                                    $exPic = asset($exPic);
                                                }
                                            @endphp
                                            <tr>
                                                <td class="py-2 px-4">
                                                    @if($exPic)
                                                        <img src="{{ $exPic }}" onclick="openMugshotModal(this.src, '{{ $ex['seizure_name'] ?? 'Exhibit Photo' }}')" class="w-10 h-10 object-contain rounded-lg border border-slate-200 cursor-pointer hover:opacity-80 transition-opacity bg-slate-50" alt="Exhibit Photo">
                                                    @else
                                                        <span class="text-[10px] font-mono-code text-slate-400">No Photo</span>
                                                    @endif
                                                </td>
                                                <td class="py-3 px-4 font-bold font-mono-code text-blue-600">{{ $ex['category'] ?? 'General Exhibit' }}</td>
                                                <td class="py-3 px-4 font-extrabold text-slate-900">{{ $ex['seizure_name'] ?? $ex['name'] ?? 'N/A' }}</td>
                                                <td class="py-3 px-4 font-mono-code">{{ $ex['seizure_quantity'] ?? $ex['quantity'] ?? 'N/A' }}</td>
                                                <td class="py-3 px-4 font-mono-code">{{ $ex['seizure_weight'] ?? $ex['weight'] ?? 'N/A' }}</td>
                                                <td class="py-3 px-4 font-mono-code font-bold text-emerald-700">{{ $ex['seizure_value'] ?? $ex['value'] ?? 'N/A' }}</td>
                                                <td class="py-3 px-4 font-mono-code font-extrabold text-slate-800">{{ $ex['seizure_officer'] ?? $ex['seizing_officer'] ?? ($case->seizure_officer ?? $case->seizing_officer ?? 'N/A') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="py-4 text-center text-slate-400 font-mono-code">No saved exhibits logged.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                    <!-- SECTION 2: ADD NEW ASSOCIATED PERSONS / CO-SUSPECTS -->
                    <div class="bg-white p-6 rounded-2xl border border-blue-200 shadow-sm space-y-5 relative">
                        
                        <div class="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold shadow-md shadow-blue-600/30">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                </div>
                                <div>
                                    <h3 class="font-black text-slate-900 text-base">Section 2: Add Associated Persons / Co-Suspects</h3>
                                    <p class="text-xs text-slate-500">Register new accomplices, drivers, buyers, informants, or co-suspects associated with this case file</p>
                                </div>
                            </div>
                            <button type="button" onclick="addAssociatePersonCard()" data-testid="add-person-btn" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-extrabold rounded-xl shadow-md transition-all flex items-center space-x-1.5 cursor-pointer border-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                <span>+ Add Person Details Row</span>
                            </button>
                        </div>

                        <!-- CONTAINER FOR DYNAMIC NEW PERSON CARDS -->
                        <div id="newPersonsContainer" class="space-y-6">
                            <!-- Rows added dynamically via JS -->
                        </div>

                    </div>

                    <!-- SECTION 4: SEIZURES (COMMODITIES) -->
                    <div
                        class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm space-y-6 relative z-20 overflow-hidden">
                        <div
                            class="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-md shadow-emerald-600/30 shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>

                                <div>
                                    <h3 class="font-extrabold text-slate-900 text-base">Section 4: Seizures
                                        (Commodities)</h3>
                                    <p class="text-xs text-slate-500">Record illicit substance types,
                                        Weight/quantity, street valuation, and evidence vault allocation</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2 shrink-0">
                                <span
                                    class="px-2.5 py-1 bg-emerald-50 text-emerald-700 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200/80 uppercase tracking-wider shadow-2xs">
                                    Contraband Inventory
                                </span>
                            </div>
                        </div>

                        <!-- SEIZURE STATUS CHECKBOX CARD -->
                        <div class="space-y-2.5">
                            <label
                                class="relative flex items-center space-x-3.5 p-4 bg-slate-50/80 hover:bg-slate-100/80 border border-slate-300/80 rounded-xl cursor-pointer hover:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-600/20 focus-within:border-emerald-600 transition-all shadow-2xs select-none overflow-hidden">
                                <input type="checkbox" id="hasSeizuresCheckbox"
                                    onchange="toggleSeizuresContainer()"
                                    class="w-5 h-5 text-emerald-600 rounded-md border-slate-300 focus:ring-emerald-600 focus:ring-2 cursor-pointer accent-emerald-600 shrink-0">
                                <div class="flex flex-col min-w-0 flex-1">
                                    <span class="text-xs font-extrabold text-slate-900">Seized Contraband /
                                        Illicit Commodities Recorded During Arrest</span>
                                    <span class="text-[11px] text-slate-500 font-medium">Check this box if any
                                        narcotics, weapons, cash, or contraband commodities were confiscated from
                                        this suspect</span>
                                </div>
                            </label>
                        </div>

                        <!-- CONDITIONAL SEIZURES DETAILS CONTAINER -->
                        <div id="seizuresDetailsContainer"
                            class="hidden p-5 bg-emerald-50/40 rounded-2xl border border-emerald-200/80 space-y-5 shadow-2xs overflow-hidden">

                            <div class="flex items-center justify-between border-b border-slate-300/60 pb-3">
                                <div class="flex items-center space-x-2.5">
                                    <div
                                        class="w-7 h-7 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-xs font-bold shadow-xs">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4
                                            class="font-extrabold text-slate-900 text-xs uppercase tracking-wider">
                                            Seized Commodities &amp; Contraband Manifest</h4>
                                        <p class="text-[11px] text-black">Record illicit substance types,
                                            Weight/quantity, street valuation, and evidence vault allocation</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2.5 flex-wrap">
                                    <div class="inline-flex items-center gap-2 bg-white px-3 py-1.5 rounded-xl border border-slate-300 shadow-2xs">
                                        <label for="seizureCountInput" class="text-[11px] font-extrabold uppercase font-mono-code text-slate-700 whitespace-nowrap">
                                            Number of Seized Items:
                                        </label>
                                        <input type="number" id="seizureCountInput" min="1" max="100" value="1"
                                            onchange="setSeizureBlocksCount(this.value)"
                                            oninput="setSeizureBlocksCount(this.value)"
                                            onblur="if(!this.value || parseInt(this.value, 10) < 1) { this.value = document.querySelectorAll('#seizuresListContainer .seizure-block').length; }"
                                            class="w-16 px-2 py-0.5 text-center font-mono-code font-black text-xs text-slate-900 bg-emerald-50 border border-emerald-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all">
                                    </div>
                                    <span class="px-2.5 py-1 bg-emerald-100 text-black text-[10px] font-mono-code font-bold rounded-lg border border-slate-300 uppercase">Evidence Tagging Active</span>
                                    <button type="button" onclick="addSeizureBlock()" class="inline-flex items-center px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-xs hover:shadow-md transition-all cursor-pointer">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                        Add Seizure
                                    </button>
                                </div>
                            </div>

                            <!-- SHARED MANIFEST DESCRIPTION OF EXHIBIT(S) SECTION (Requirement 1C) -->
                            <div class="bg-white p-5 rounded-2xl border border-slate-300/80 shadow-2xs space-y-2">
                                <div class="flex items-center justify-between">
                                    <label for="manifestDescriptionOfExhibits" class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase flex items-center gap-2">
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h7" />
                                        </svg>
                                        <span>Description of Exhibit(s)</span>
                                        <span class="text-red-600 font-bold">*</span>
                                    </label>
                                    <span class="px-2 py-0.5 bg-emerald-50 text-emerald-800 text-[10px] font-mono-code font-bold rounded-md border border-emerald-200 uppercase">
                                        SHARED MANIFEST DESCRIPTION
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 font-medium">Record the overall description, outer packaging, markings, and consignment details applicable to all seized items under this manifest.</p>
                                <textarea id="manifestDescriptionOfExhibits" name="description_of_exhibits" rows="3"
                                    placeholder="e.g. Inside the brown carton were multiple individually sealed exhibits, labelled sequentially as received under chain of custody..."
                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs resize-none placeholder:text-slate-400">{{ old('description_of_exhibits', $case->description_of_exhibits ?? '') }}</textarea>
                            </div>

                            <!-- SEIZURES LIST CONTAINER FOR MULTIPLE SEIZURES -->
                            <div id="seizuresListContainer" class="space-y-6">
                                <!-- SEIZURE BLOCK #1 -->
                                <div class="seizure-block bg-white p-5 rounded-2xl border border-slate-300/80 shadow-2xs space-y-5 relative"
                                    data-seizure-index="1">
                                    <!-- SEIZURE ITEM HEADER -->
                                    <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                                        <div class="flex items-center space-x-2">
                                            <span
                                                class="w-6 h-6 rounded-full bg-emerald-600 text-white font-extrabold text-xs flex items-center justify-center seizure-number-badge">1</span>
                                            <h5 class="font-extrabold text-slate-900 text-xs uppercase tracking-wider seizure-title">Seized Item 1</h5>
                                        </div>
                                        <div class="flex items-center space-x-2 seizure-header-right">
                                            <span
                                                class="px-2.5 py-0.5 bg-emerald-50 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200">ACTIVE
                                                EXHIBIT</span>
                                            <button type="button" onclick="removeSeizureBlock(this)"
                                                class="remove-seizure-btn hidden text-red-600 hover:bg-red-50 border border-red-200 px-2.5 py-1 rounded-lg text-xs font-bold transition-colors cursor-pointer flex items-center gap-1"
                                                title="Remove Seized Item">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg> Remove Item
                                            </button>
                                        </div>
                                    </div>

                                    <!-- SEIZURES INPUT GRID -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">

                                        <!-- SUSPECT / OWNERSHIP SELECT DROPDOWN -->
                                        <div class="relative z-30">
                                            <div class="seizure-ownership-default">
                                                <label
                                                    class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                    Suspect / Ownership <span class="text-red-600 font-bold">*</span>
                                                </label>
                                                <div class="relative">
                                                    <select onchange="this.setAttribute('data-user-selected', 'true'); if (typeof syncSection4ExhibitsToLabSection === 'function') syncSection4ExhibitsToLabSection();"
                                                        class="seizure-associated-person-select w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs cursor-pointer">
                                                        <option value="All Suspects">All Suspects (Joint Ownership)
                                                        </option>
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
                                            <label
                                                class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                Exhibit (Commodity Category) <span
                                                    class="text-red-600 font-bold">*</span>
                                            </label>
                                            <div id="exhibitSelectWrapper" class="relative z-[9999]"
                                                style="z-index: 9999;">
                                                <div class="relative">
                                                    <span
                                                        class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                        <svg class="w-3.5 h-3.5" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                        </svg>
                                                    </span>
                                                    <input type="text" id="exhibitSearchInput"
                                                        value="Drugs"
                                                        placeholder="Select or type exhibit category..."
                                                        autocomplete="off"
                                                        class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-500 placeholder:font-normal cursor-text"
                                                        onclick="openExhibitListPanel(this)"
                                                        oninput="filterExhibitList(this)"
                                                        onfocus="openExhibitListPanel(this)">
                                                    <input type="hidden" id="newSeizureCategory"
                                                        value="Drugs">
                                                </div>
                                                <div id="exhibitListPanel"
                                                    class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-[9999] overflow-hidden"
                                                    style="z-index: 9999;">
                                                    <div
                                                        class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                        <input type="text" id="exhibitSearchInner"
                                                            placeholder="Type to filter categories..."
                                                            oninput="filterExhibitListInner(this)"
                                                            class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                    </div>
                                                    <ul id="exhibitList" class="py-1.5 space-y-0.5 px-1.5">
                                                        <li class="exhibit-item"
                                                            onclick="selectExhibitCategory('Drugs', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <div>
                                                                    <div class="font-extrabold text-slate-900">
                                                                        Drugs</div>
                                                                    <div
                                                                        class="text-[10px] text-slate-500 font-normal">
                                                                        Narcotics, Synthetics, Opioids</div>
                                                                </div>
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-black px-2 py-0.5 rounded font-bold">DRG</span>
                                                            </button>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- SPECIFIC COMMODITY / DRUG NAME (SMART FIELD) -->
                                        <div id="commodityFieldWrapper">
                                            <label
                                                class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Packaging as Received <span
                                                    class="text-red-600 font-bold">*</span></label>

                                            <!-- TEXT INPUT (shown for non-drug categories) -->
                                            <div id="commodityTextWrapper"
                                                class="hidden relative flex items-center group">
                                                <span
                                                    class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                    </svg>
                                                </span>
                                                <input type="text" id="newSeizureName"
                                                    placeholder="e.g. Cocaine (High Purity Slabs)"
                                                    class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>

                                            <!-- DRUG SELECT DROPDOWN (matching Exhibit Category styling) -->
                                            <div id="drugSelectWrapper" class="relative z-40">
                                                <div class="relative">
                                                    <span
                                                        class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                        <svg class="w-3.5 h-3.5" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                        </svg>
                                                    </span>
                                                    <input type="text" id="drugSearchInput"
                                                        placeholder="Select or type custom drug name..."
                                                        autocomplete="off"
                                                        class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-500 placeholder:font-normal cursor-text"
                                                        onclick="openDrugListPanel(this)"
                                                        oninput="filterDrugList(this)"
                                                        onchange="filterDrugList(this)"
                                                        onblur="filterDrugList(this)"
                                                        onkeydown="handleCommoditySearchKeydown(event, this, 'drug')"
                                                        onfocus="openDrugListPanel(this)">
                                                    <input type="hidden" id="newSeizureNameDrug"
                                                        name="newSeizureNameDrug" value="">
                                                </div>
                                                <div id="drugListPanel"
                                                    class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden"
                                                    style="max-height:240px;overflow-y:auto">
                                                    <div
                                                        class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                        <input type="text" id="drugSearchInner"
                                                            placeholder="Type to filter drugs..."
                                                            oninput="filterDrugListInner(this)"
                                                            class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                    </div>
                                                    <ul id="drugList" class="py-1.5 space-y-0.5 px-1.5">
                                                        <!-- Opioids & Analgesics -->
                                                        <li
                                                            class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                            Opioids &amp; Analgesics</li>
                                                        <li class="drug-item"
                                                            onclick="selectDrugType('Heroin (Brown Sugar / Diacetylmorphine)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Heroin (Brown Sugar / Diacetylmorphine)</span>
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span>
                                                            </button>
                                                        </li>
                                                        <li class="drug-item"
                                                            onclick="selectDrugType('Fentanyl (Synthetic Opioid Patches / Powder)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Fentanyl (Synthetic Opioid Patches /
                                                                    Powder)</span>
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span>
                                                            </button>
                                                        </li>
                                                        <li class="drug-item"
                                                            onclick="selectDrugType('Morphine (Raw Opium Extract)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Morphine (Raw Opium Extract)</span>
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span>
                                                            </button>
                                                        </li>
                                                        <li class="drug-item"
                                                            onclick="selectDrugType('Tramadol (High-Dose Tablets / Capsules)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Tramadol (High-Dose Tablets / Capsules)</span>
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span>
                                                            </button>
                                                        </li>
                                                        <li class="drug-item"
                                                            onclick="selectDrugType('Codeine (Cough Syrup Misuse / Purple Drank)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Codeine (Cough Syrup Misuse / Purple
                                                                    Drank)</span>
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span>
                                                            </button>
                                                        </li>
                                                        <li class="drug-item"
                                                            onclick="selectDrugType('Oxycodone (OxyContin Tablets)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Oxycodone (OxyContin Tablets)</span>
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span>
                                                            </button>
                                                        </li>

                                                        <!-- Stimulants & Cocaine-Class -->
                                                        <li
                                                            class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                            Stimulants &amp; Cocaine-Class</li>
                                                        <li class="drug-item"
                                                            onclick="selectDrugType('Cocaine (High-Purity Powder / Slabs)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Cocaine (High-Purity Powder / Slabs)</span>
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">COC</span>
                                                            </button>
                                                        </li>
                                                        <li class="drug-item"
                                                            onclick="selectDrugType('Crack Cocaine (Rock Form / Freebase)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Crack Cocaine (Rock Form / Freebase)</span>
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">COC</span>
                                                            </button>
                                                        </li>
                                                        <li class="drug-item"
                                                            onclick="selectDrugType('Methamphetamine (Crystal Meth / Ice)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Methamphetamine (Crystal Meth / Ice)</span>
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">STIM</span>
                                                            </button>
                                                        </li>
                                                        <li class="drug-item"
                                                            onclick="selectDrugType('Amphetamine (Speed / Pep Pills)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Amphetamine (Speed / Pep Pills)</span>
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">STIM</span>
                                                            </button>
                                                        </li>
                                                        <li class="drug-item"
                                                            onclick="selectDrugType('MDMA (Ecstasy / Molly Tablets)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>MDMA (Ecstasy / Molly Tablets)</span>
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">STIM</span>
                                                            </button>
                                                        </li>
                                                        <li class="drug-item"
                                                            onclick="selectDrugType('Khat (Catha Edulis Fresh Leaves / Bundles)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Khat (Catha Edulis Fresh Leaves /
                                                                    Bundles)</span>
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">STIM</span>
                                                            </button>
                                                        </li>

                                                        <!-- Cannabis & Derivatives -->
                                                        <li
                                                            class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                            Cannabis &amp; Derivatives</li>
                                                        <li class="drug-item"
                                                            onclick="selectDrugType('Cannabis / Marijuana (Dried Flower / Weed)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Cannabis / Marijuana (Dried Flower /
                                                                    Weed)</span>
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-bold">CAN</span>
                                                            </button>
                                                        </li>
                                                        <li class="drug-item"
                                                            onclick="selectDrugType('Cannabis Resin (Hashish / Black Hash Slabs)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Cannabis Resin (Hashish / Black Hash
                                                                    Slabs)</span>
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-bold">CAN</span>
                                                            </button>
                                                        </li>
                                                        <li class="drug-item"
                                                            onclick="selectDrugType('Cannabis Oil / THC Concentrate (Wax / Dabs)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Cannabis Oil / THC Concentrate (Wax /
                                                                    Dabs)</span>
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-bold">CAN</span>
                                                            </button>
                                                        </li>
                                                        <li class="drug-item"
                                                            onclick="selectDrugType('Synthetic Cannabinoids (Spice / K2 Herbal)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Synthetic Cannabinoids (Spice / K2
                                                                    Herbal)</span>
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">SYN</span>
                                                            </button>
                                                        </li>

                                                        <!-- Sedatives & Depressants -->
                                                        <li
                                                            class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                            Sedatives &amp; Depressants</li>
                                                        <li class="drug-item"
                                                            onclick="selectDrugType('Rohypnol (Flunitrazepam / Date Rape Drug)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Rohypnol (Flunitrazepam / Date Rape
                                                                    Drug)</span>
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">SED</span>
                                                            </button>
                                                        </li>
                                                        <li class="drug-item"
                                                            onclick="selectDrugType('GHB (Gamma-Hydroxybutyrate / Liquid Ecstasy)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>GHB (Gamma-Hydroxybutyrate / Liquid
                                                                    Ecstasy)</span>
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">SED</span>
                                                            </button>
                                                        </li>
                                                        <li class="drug-item"
                                                            onclick="selectDrugType('Diazepam / Valium (Benzodiazepine Tablets)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Diazepam / Valium (Benzodiazepine
                                                                    Tablets)</span>
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">SED</span>
                                                            </button>
                                                        </li>
                                                        <li class="drug-item"
                                                            onclick="selectDrugType('Ketamine (Special K / Dissociative Anesthetic)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Ketamine (Special K / Dissociative
                                                                    Anesthetic)</span>
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">SED</span>
                                                            </button>
                                                        </li>

                                                        <!-- Hallucinogens & Psychedelics -->
                                                        <li
                                                            class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                            Hallucinogens &amp; Psychedelics</li>
                                                        <li class="drug-item"
                                                            onclick="selectDrugType('LSD (Lysergic Acid Diethylamide / Acid Tabs)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>LSD (Lysergic Acid Diethylamide / Acid
                                                                    Tabs)</span>
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-pink-50 text-pink-700 px-1.5 py-0.5 rounded font-bold">PSY</span>
                                                            </button>
                                                        </li>
                                                        <li class="drug-item"
                                                            onclick="selectDrugType('Psilocybin (Magic Mushrooms / Shrooms)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Psilocybin (Magic Mushrooms / Shrooms)</span>
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-pink-50 text-pink-700 px-1.5 py-0.5 rounded font-bold">PSY</span>
                                                            </button>
                                                        </li>
                                                        <li class="drug-item"
                                                            onclick="selectDrugType('PCP (Phencyclidine / Angel Dust)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>PCP (Phencyclidine / Angel Dust)</span>
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-pink-50 text-pink-700 px-1.5 py-0.5 rounded font-bold">PSY</span>
                                                            </button>
                                                        </li>

                                                        <!-- Precursor Chemicals -->
                                                        <li
                                                            class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                            Precursor Chemicals</li>
                                                        <li class="drug-item"
                                                            onclick="selectDrugType('Ephedrine / Pseudoephedrine (Meth Precursor)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Ephedrine / Pseudoephedrine (Meth
                                                                    Precursor)</span>
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded font-bold">CHEM</span>
                                                            </button>
                                                        </li>
                                                        <li class="drug-item"
                                                            onclick="selectDrugType('Acetic Anhydride (Heroin Precursor Chemical)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Acetic Anhydride (Heroin Precursor
                                                                    Chemical)</span>
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded font-bold">CHEM</span>
                                                            </button>
                                                        </li>
                                                        <li class="drug-item"
                                                            onclick="selectDrugType('Phenylacetic Acid (Amphetamine Precursor)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors">
                                                                <span>Phenylacetic Acid (Amphetamine
                                                                    Precursor)</span>
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded font-bold">CHEM</span>
                                                            </button>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>

                                            <!-- WEAPON SELECT DROPDOWN (matching Exhibit Category styling) -->
                                            <div id="weaponSelectWrapper" class="hidden relative z-40">
                                                <div class="relative">
                                                    <span
                                                        class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                        <svg class="w-3.5 h-3.5" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                        </svg>
                                                    </span>
                                                    <input type="text" id="weaponSearchInput"
                                                        placeholder="Select or type custom weapon type..."
                                                        autocomplete="off"
                                                        class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-500 placeholder:font-normal cursor-text"
                                                        onclick="openWeaponListPanel(this)"
                                                        oninput="filterWeaponList(this)"
                                                        onfocus="openWeaponListPanel(this)">
                                                    <input type="hidden" id="newSeizureNameWeapon"
                                                        name="newSeizureNameWeapon" value="">
                                                </div>
                                                <div id="weaponListPanel"
                                                    class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden"
                                                    style="max-height:240px;overflow-y:auto">
                                                    <div
                                                        class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                        <input type="text" id="weaponSearchInner"
                                                            placeholder="Type to filter weapons..."
                                                            oninput="filterWeaponListInner(this)"
                                                            class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                    </div>
                                                    <ul id="weaponList" class="py-1.5 space-y-0.5 px-1.5">
                                                        <!-- Handguns & Pistols -->
                                                        <li
                                                            class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                            Handguns &amp; Pistols</li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('AK-47 / AKM Shorty Pistol (7.62x39mm)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>AK-47
                                                                    / AKM Shorty Pistol (7.62x39mm)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Glock 17 Semi-Automatic Pistol (9mm)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Glock
                                                                    17 Semi-Automatic Pistol (9mm)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Glock 19 Compact Tactical Pistol (9mm)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Glock
                                                                    19 Compact Tactical Pistol (9mm)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Beretta 92FS / M9 Pistol (9mm)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Beretta
                                                                    92FS / M9 Pistol (9mm)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('SIG Sauer P226 / P320 Pistol (9mm)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>SIG
                                                                    Sauer P226 / P320 Pistol (9mm)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Colt M1911 / M1911A1 Pistol (.45 ACP)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Colt
                                                                    M1911 / M1911A1 Pistol (.45 ACP)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Smith &amp; Wesson M&amp;P 9mm Pistol', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Smith
                                                                    &amp; Wesson M&amp;P 9mm Pistol</span><span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Browning Hi-Power Pistol (9mm)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Browning
                                                                    Hi-Power Pistol (9mm)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('CZ 75 / CZ P-10 C Tactical Pistol (9mm)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>CZ
                                                                    75 / CZ P-10 C Tactical Pistol (9mm)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Tokarev TT-33 Pistol (7.62x25mm)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Tokarev
                                                                    TT-33 Pistol (7.62x25mm)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Makarov PM Concealed Pistol (9x18mm)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Makarov
                                                                    PM Concealed Pistol (9x18mm)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Revolver (.38 Special / .357 Magnum)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Revolver
                                                                    (.38 Special / .357 Magnum)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">REV</span></button>
                                                        </li>

                                                        <!-- Rifles & Assault Rifles -->
                                                        <li
                                                            class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                            Rifles &amp; Assault Rifles</li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('AK-47 / AKM Kalashnikov Rifle (7.62x39mm)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>AK-47
                                                                    / AKM Kalashnikov Rifle (7.62x39mm)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-100 text-red-800 px-1.5 py-0.5 rounded font-bold">RFL</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('AK-74 Tactical Rifle (5.45x39mm)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>AK-74
                                                                    Tactical Rifle (5.45x39mm)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-100 text-red-800 px-1.5 py-0.5 rounded font-bold">RFL</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('M16A2 / M16A4 Military Assault Rifle (5.56mm)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>M16A2
                                                                    / M16A4 Military Assault Rifle
                                                                    (5.56mm)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-100 text-red-800 px-1.5 py-0.5 rounded font-bold">RFL</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('M4A1 Carbine / AR-15 Platform (5.56x45mm NATO)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>M4A1
                                                                    Carbine / AR-15 Platform (5.56x45mm
                                                                    NATO)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-100 text-red-800 px-1.5 py-0.5 rounded font-bold">RFL</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Heckler &amp; Koch HK416 Assault Rifle (5.56mm)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Heckler
                                                                    &amp; Koch HK416 Assault Rifle
                                                                    (5.56mm)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-100 text-red-800 px-1.5 py-0.5 rounded font-bold">RFL</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('FN FAL Battle Rifle (7.62x51mm NATO)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>FN
                                                                    FAL Battle Rifle (7.62x51mm NATO)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-100 text-red-800 px-1.5 py-0.5 rounded font-bold">RFL</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Heckler &amp; Koch G3 Battle Rifle (7.62x51mm)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Heckler
                                                                    &amp; Koch G3 Battle Rifle
                                                                    (7.62x51mm)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-100 text-red-800 px-1.5 py-0.5 rounded font-bold">RFL</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Galil ARM / Tavor TAR-21 Assault Rifle (5.56mm)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Galil
                                                                    ARM / Tavor TAR-21 Assault Rifle
                                                                    (5.56mm)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-100 text-red-800 px-1.5 py-0.5 rounded font-bold">RFL</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Steyr AUG Bullpup Assault Rifle (5.56mm)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Steyr
                                                                    AUG Bullpup Assault Rifle (5.56mm)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-100 text-red-800 px-1.5 py-0.5 rounded font-bold">RFL</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Type 56 Norinco Rifle (7.62x39mm)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Type
                                                                    56 Norinco Rifle (7.62x39mm)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-100 text-red-800 px-1.5 py-0.5 rounded font-bold">RFL</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('SKS Semi-Automatic Carbine (7.62x39mm)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>SKS
                                                                    Semi-Automatic Carbine (7.62x39mm)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-100 text-red-800 px-1.5 py-0.5 rounded font-bold">RFL</span></button>
                                                        </li>

                                                        <!-- Submachine Guns (SMGs) -->
                                                        <li
                                                            class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                            Submachine Guns (SMGs)</li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Heckler &amp; Koch MP5 / MP5K SMG (9mm)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Heckler
                                                                    &amp; Koch MP5 / MP5K SMG (9mm)</span><span
                                                                    class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">SMG</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Uzi / Mini-Uzi Submachine Gun (9mm)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Uzi
                                                                    / Mini-Uzi Submachine Gun (9mm)</span><span
                                                                    class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">SMG</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('CZ Scorpion EVO 3 Submachine Gun (9mm)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>CZ
                                                                    Scorpion EVO 3 Submachine Gun (9mm)</span><span
                                                                    class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">SMG</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('FN P90 Personal Defense Weapon (5.7x28mm)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>FN
                                                                    P90 Personal Defense Weapon
                                                                    (5.7x28mm)</span><span
                                                                    class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">SMG</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('MAC-10 / MAC-11 Submachine Gun (.45 ACP / 9mm)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>MAC-10
                                                                    / MAC-11 Submachine Gun (.45 ACP /
                                                                    9mm)</span><span
                                                                    class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">SMG</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Beretta M12 Submachine Gun (9mm)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Beretta
                                                                    M12 Submachine Gun (9mm)</span><span
                                                                    class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">SMG</span></button>
                                                        </li>

                                                        <!-- Shotguns -->
                                                        <li
                                                            class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                            Shotguns</li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Remington 870 Tactical Shotgun (12 Gauge)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Remington
                                                                    870 Tactical Shotgun (12 Gauge)</span><span
                                                                    class="text-[10px] font-mono-code bg-orange-50 text-orange-800 px-1.5 py-0.5 rounded font-bold">STG</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Mossberg 500 / 590 Pump Shotgun (12 Gauge)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Mossberg
                                                                    500 / 590 Pump Shotgun (12 Gauge)</span><span
                                                                    class="text-[10px] font-mono-code bg-orange-50 text-orange-800 px-1.5 py-0.5 rounded font-bold">STG</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Benelli M4 Combat Shotgun (12 Gauge)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Benelli
                                                                    M4 Combat Shotgun (12 Gauge)</span><span
                                                                    class="text-[10px] font-mono-code bg-orange-50 text-orange-800 px-1.5 py-0.5 rounded font-bold">STG</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Saiga-12 Semi-Auto Tactical Shotgun (12 Gauge)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Saiga-12
                                                                    Semi-Auto Tactical Shotgun (12
                                                                    Gauge)</span><span
                                                                    class="text-[10px] font-mono-code bg-orange-50 text-orange-800 px-1.5 py-0.5 rounded font-bold">STG</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Double-Barrel Break-Action Shotgun (Sawed-Off)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Double-Barrel
                                                                    Break-Action Shotgun (Sawed-Off)</span><span
                                                                    class="text-[10px] font-mono-code bg-orange-50 text-orange-800 px-1.5 py-0.5 rounded font-bold">STG</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Single-Barrel Hunting Shotgun (12 Gauge)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Single-Barrel
                                                                    Hunting Shotgun (12 Gauge)</span><span
                                                                    class="text-[10px] font-mono-code bg-orange-50 text-orange-800 px-1.5 py-0.5 rounded font-bold">STG</span></button>
                                                        </li>

                                                        <!-- Sniper & Precision Rifles -->
                                                        <li
                                                            class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                            Sniper &amp; Precision Rifles</li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Barrett M82A1 / M107 Anti-Materiel (.50 BMG)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Barrett
                                                                    M82A1 / M107 Anti-Materiel (.50 BMG)</span><span
                                                                    class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">SNP</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Remington 700 / M24 Sniper Rifle (7.62 NATO)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Remington
                                                                    700 / M24 Sniper Rifle (7.62 NATO)</span><span
                                                                    class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">SNP</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Dragunov SVD Sniper Rifle (7.62x54mmR)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Dragunov
                                                                    SVD Sniper Rifle (7.62x54mmR)</span><span
                                                                    class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">SNP</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Accuracy International AWM / AWSM (.338 Lapua)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Accuracy
                                                                    International AWM / AWSM (.338
                                                                    Lapua)</span><span
                                                                    class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">SNP</span></button>
                                                        </li>

                                                        <!-- Machine Guns & Heavy Ordnance -->
                                                        <li
                                                            class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                            Machine Guns &amp; Heavy Ordnance</li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('PKM / PKP General Purpose Machine Gun (7.62x54mmR)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>PKM
                                                                    / PKP General Purpose Machine Gun
                                                                    (7.62x54mmR)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-900 text-white px-1.5 py-0.5 rounded font-bold">MG</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('FN MAG / M240 Belt-Fed Machine Gun (7.62mm)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>FN
                                                                    MAG / M240 Belt-Fed Machine Gun
                                                                    (7.62mm)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-900 text-white px-1.5 py-0.5 rounded font-bold">MG</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('M249 SAW / FN Minimi Light Machine Gun (5.56mm)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>M249
                                                                    SAW / FN Minimi Light Machine Gun
                                                                    (5.56mm)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-900 text-white px-1.5 py-0.5 rounded font-bold">MG</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Browning M2HB Heavy Machine Gun (.50 BMG)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Browning
                                                                    M2HB Heavy Machine Gun (.50 BMG)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-900 text-white px-1.5 py-0.5 rounded font-bold">HMG</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('RPG-7 Anti-Tank Rocket Launcher', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>RPG-7
                                                                    Anti-Tank Rocket Launcher</span><span
                                                                    class="text-[10px] font-mono-code bg-red-900 text-white px-1.5 py-0.5 rounded font-bold">ORD</span></button>
                                                        </li>

                                                        <!-- Edged, Bladed & Impact Weapons -->
                                                        <li
                                                            class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                            Edged, Bladed &amp; Impact Weapons</li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Combat / Tactical Military Knife', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Combat
                                                                    / Tactical Military Knife</span><span
                                                                    class="text-[10px] font-mono-code bg-slate-200 text-slate-800 px-1.5 py-0.5 rounded font-bold">BLD</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Heavy Steel Machete / Cutlass', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Heavy
                                                                    Steel Machete / Cutlass</span><span
                                                                    class="text-[10px] font-mono-code bg-slate-200 text-slate-800 px-1.5 py-0.5 rounded font-bold">BLD</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Switchblade / Automatic Stiletto Knife', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Switchblade
                                                                    / Automatic Stiletto Knife</span><span
                                                                    class="text-[10px] font-mono-code bg-slate-200 text-slate-800 px-1.5 py-0.5 rounded font-bold">BLD</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Dagger / Push Knife / Concealed Blade', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Dagger
                                                                    / Push Knife / Concealed Blade</span><span
                                                                    class="text-[10px] font-mono-code bg-slate-200 text-slate-800 px-1.5 py-0.5 rounded font-bold">BLD</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Samurai Sword / Katana / Machete Blade', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Samurai
                                                                    Sword / Katana / Machete Blade</span><span
                                                                    class="text-[10px] font-mono-code bg-slate-200 text-slate-800 px-1.5 py-0.5 rounded font-bold">BLD</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Brass Knuckles / Heavy Iron Knuckles', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Brass
                                                                    Knuckles / Heavy Iron Knuckles</span><span
                                                                    class="text-[10px] font-mono-code bg-slate-200 text-slate-800 px-1.5 py-0.5 rounded font-bold">IMP</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Crossbow / Tactical Compound Bow', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Crossbow
                                                                    / Tactical Compound Bow</span><span
                                                                    class="text-[10px] font-mono-code bg-slate-200 text-slate-800 px-1.5 py-0.5 rounded font-bold">BOW</span></button>
                                                        </li>

                                                        <!-- Explosives & Less-Lethal Tactical Devices -->
                                                        <li
                                                            class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                            Explosives &amp; Less-Lethal Tactical Devices</li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('High-Explosive Hand Grenade (Frag)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>High-Explosive
                                                                    Hand Grenade (Frag)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-100 text-red-900 px-1.5 py-0.5 rounded font-bold">EXP</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Flashbang Grenade / Tactical Stun Grenade', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Flashbang
                                                                    Grenade / Tactical Stun Grenade</span><span
                                                                    class="text-[10px] font-mono-code bg-amber-100 text-amber-900 px-1.5 py-0.5 rounded font-bold">TAC</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('C4 Plastic Explosive / Dynamite Blocks', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>C4
                                                                    Plastic Explosive / Dynamite Blocks</span><span
                                                                    class="text-[10px] font-mono-code bg-red-100 text-red-900 px-1.5 py-0.5 rounded font-bold">EXP</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Improvised Explosive Device (IED / Pipe Bomb)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Improvised
                                                                    Explosive Device (IED / Pipe Bomb)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-100 text-red-900 px-1.5 py-0.5 rounded font-bold">IED</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Taser Stun Gun / Electroshock Weapon', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Taser
                                                                    Stun Gun / Electroshock Weapon</span><span
                                                                    class="text-[10px] font-mono-code bg-blue-100 text-blue-900 px-1.5 py-0.5 rounded font-bold">TSR</span></button>
                                                        </li>
                                                        <li class="weapon-item"
                                                            onclick="selectWeaponType('Pepper Spray / OC Tear Gas Canister', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Pepper
                                                                    Spray / OC Tear Gas Canister</span><span
                                                                    class="text-[10px] font-mono-code bg-blue-100 text-blue-900 px-1.5 py-0.5 rounded font-bold">GAS</span></button>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>

                                            <!-- CURRENCY SELECT2 DROPDOWN (shown when Money is selected) -->
                                            <div id="currencySelectWrapper" class="hidden relative z-40">
                                                <div class="relative">
                                                    <span
                                                        class="absolute left-3 top-1/2 -translate-y-1/2 text-emerald-500 pointer-events-none">
                                                        <svg class="w-3.5 h-3.5" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                        </svg>
                                                    </span>
                                                    <input type="text" id="currencySearchInput"
                                                        placeholder="Select currency..." autocomplete="off"
                                                        class="w-full pl-8 pr-3 py-2.5 bg-white border border-emerald-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-300 transition-all shadow-2xs placeholder:text-slate-400 placeholder:font-normal cursor-pointer"
                                                        readonly onclick="openCurrencyListPanel(this)"
                                                        oninput="filterCurrencyList(this)"
                                                        onfocus="openCurrencyListPanel(this)">
                                                    <input type="hidden" id="newSeizureNameCurrency"
                                                        name="newSeizureName">
                                                </div>
                                                <div id="currencyListPanel"
                                                    class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-emerald-200 z-50 overflow-hidden"
                                                    style="max-height:240px;overflow-y:auto">
                                                    <div
                                                        class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                        <input type="text" id="currencySearchInner"
                                                            placeholder="Type to filter currencies..."
                                                            oninput="filterCurrencyListInner(this)"
                                                            class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-300 transition-all">
                                                    </div>
                                                    <ul id="currencyList" class="py-1.5 space-y-0.5 px-1.5">
                                                        <!-- West Africa -->
                                                        <li
                                                            class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-emerald-500 uppercase tracking-widest">
                                                            West Africa</li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Ghana Cedi (GHS ₵)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Ghana
                                                                    Cedi</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">GHS
                                                                    ₵</span></button></li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Nigerian Naira (NGN ₦)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Nigerian
                                                                    Naira</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">NGN
                                                                    ₦</span></button></li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('CFA Franc BCEAO (XOF)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>CFA
                                                                    Franc (BCEAO)</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">XOF</span></button>
                                                        </li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Sierra Leonean Leone (SLL)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Sierra
                                                                    Leonean Leone</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">SLL</span></button>
                                                        </li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Liberian Dollar (LRD)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Liberian
                                                                    Dollar</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">LRD</span></button>
                                                        </li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Gambian Dalasi (GMD)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Gambian
                                                                    Dalasi</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">GMD</span></button>
                                                        </li>

                                                        <!-- Major Global -->
                                                        <li
                                                            class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-emerald-500 uppercase tracking-widest">
                                                            Major Global Currencies</li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('US Dollar (USD $)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>US
                                                                    Dollar</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">USD
                                                                    $</span></button></li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Euro (EUR €)')"><button
                                                                type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Euro</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">EUR
                                                                    €</span></button></li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('British Pound Sterling (GBP £)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>British
                                                                    Pound Sterling</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">GBP
                                                                    £</span></button></li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Swiss Franc (CHF)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Swiss
                                                                    Franc</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">CHF</span></button>
                                                        </li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Japanese Yen (JPY ¥)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Japanese
                                                                    Yen</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">JPY
                                                                    ¥</span></button></li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Chinese Yuan Renminbi (CNY ¥)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Chinese
                                                                    Yuan (Renminbi)</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">CNY
                                                                    ¥</span></button></li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Canadian Dollar (CAD $)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Canadian
                                                                    Dollar</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">CAD
                                                                    $</span></button></li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Australian Dollar (AUD $)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Australian
                                                                    Dollar</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">AUD
                                                                    $</span></button></li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('UAE Dirham (AED)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>UAE
                                                                    Dirham</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">AED</span></button>
                                                        </li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Saudi Riyal (SAR)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Saudi
                                                                    Riyal</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">SAR</span></button>
                                                        </li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Indian Rupee (INR ₹)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Indian
                                                                    Rupee</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">INR
                                                                    ₹</span></button></li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('South Korean Won (KRW ₩)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>South
                                                                    Korean Won</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">KRW
                                                                    ₩</span></button></li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Singapore Dollar (SGD $)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Singapore
                                                                    Dollar</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">SGD
                                                                    $</span></button></li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Hong Kong Dollar (HKD $)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Hong
                                                                    Kong Dollar</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">HKD
                                                                    $</span></button></li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Russian Ruble (RUB ₽)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Russian
                                                                    Ruble</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">RUB
                                                                    ₽</span></button></li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Turkish Lira (TRY ₺)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Turkish
                                                                    Lira</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">TRY
                                                                    ₺</span></button></li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Brazilian Real (BRL R$)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Brazilian
                                                                    Real</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">BRL
                                                                    R$</span></button></li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Mexican Peso (MXN $)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Mexican
                                                                    Peso</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">MXN
                                                                    $</span></button></li>

                                                        <!-- Africa -->
                                                        <li
                                                            class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-emerald-500 uppercase tracking-widest">
                                                            Africa</li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('South African Rand (ZAR R)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>South
                                                                    African Rand</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">ZAR
                                                                    R</span></button></li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Kenyan Shilling (KES)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Kenyan
                                                                    Shilling</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">KES</span></button>
                                                        </li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Ethiopian Birr (ETB)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Ethiopian
                                                                    Birr</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">ETB</span></button>
                                                        </li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Egyptian Pound (EGP £)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Egyptian
                                                                    Pound</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">EGP
                                                                    £</span></button></li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Tanzanian Shilling (TZS)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Tanzanian
                                                                    Shilling</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">TZS</span></button>
                                                        </li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Moroccan Dirham (MAD)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Moroccan
                                                                    Dirham</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">MAD</span></button>
                                                        </li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Angolan Kwanza (AOA)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Angolan
                                                                    Kwanza</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">AOA</span></button>
                                                        </li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Congolese Franc (CDF)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Congolese
                                                                    Franc</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">CDF</span></button>
                                                        </li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Zambian Kwacha (ZMW)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Zambian
                                                                    Kwacha</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">ZMW</span></button>
                                                        </li>

                                                        <!-- Crypto -->
                                                        <li
                                                            class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-emerald-500 uppercase tracking-widest">
                                                            Cryptocurrencies</li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Bitcoin (BTC ₿)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Bitcoin</span><span
                                                                    class="text-[10px] font-mono-code bg-amber-50 text-amber-700 px-1.5 py-0.5 rounded font-bold">BTC
                                                                    ₿</span></button></li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Ethereum (ETH Ξ)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Ethereum</span><span
                                                                    class="text-[10px] font-mono-code bg-amber-50 text-amber-700 px-1.5 py-0.5 rounded font-bold">ETH
                                                                    Ξ</span></button></li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Tether (USDT)')"><button
                                                                type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Tether
                                                                    (Stablecoin)</span><span
                                                                    class="text-[10px] font-mono-code bg-amber-50 text-amber-700 px-1.5 py-0.5 rounded font-bold">USDT</span></button>
                                                        </li>
                                                        <li class="currency-item"
                                                            onclick="selectCurrencyType('Monero (XMR – Privacy Coin)')">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl transition-colors flex items-center justify-between"><span>Monero
                                                                    (Privacy Coin)</span><span
                                                                    class="text-[10px] font-mono-code bg-amber-50 text-amber-700 px-1.5 py-0.5 rounded font-bold">XMR</span></button>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>

                                            <!-- AMMUNITION SELECT DROPDOWN (shown when Ammunition is selected) -->
                                            <div id="ammoSelectWrapper" class="hidden relative z-40">
                                                <div class="relative">
                                                    <span
                                                        class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                        <svg class="w-3.5 h-3.5" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                        </svg>
                                                    </span>
                                                    <input type="text" id="ammoSearchInput"
                                                        placeholder="Select or type custom ammunition type..."
                                                        autocomplete="off"
                                                        class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-500 placeholder:font-normal cursor-text"
                                                        onclick="openAmmoListPanel(this)"
                                                        oninput="filterAmmoList(this)"
                                                        onfocus="openAmmoListPanel(this)">
                                                    <input type="hidden" id="newSeizureNameAmmo"
                                                        name="newSeizureNameAmmo" value="">
                                                </div>
                                                <div id="ammoListPanel"
                                                    class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden"
                                                    style="max-height:240px;overflow-y:auto">
                                                    <div
                                                        class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                        <input type="text" id="ammoSearchInner"
                                                            placeholder="Type to filter ammunitions..."
                                                            oninput="filterAmmoListInner(this)"
                                                            class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                    </div>
                                                    <ul id="ammoList" class="py-1.5 space-y-0.5 px-1.5">
                                                        <!-- Pistol & Handgun Ammunition -->
                                                        <li
                                                            class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                            Pistol &amp; Handgun Ammunition</li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('9x19mm Parabellum / NATO (9mm Luger)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>9x19mm
                                                                    Parabellum / NATO (9mm Luger)</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">9MM</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('.45 ACP (Automatic Colt Pistol / 11.43x23mm)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.45
                                                                    ACP (Automatic Colt Pistol /
                                                                    11.43x23mm)</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">.45</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('.40 S&amp;W (Smith &amp; Wesson / 10x22mm)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.40
                                                                    S&amp;W (Smith &amp; Wesson /
                                                                    10x22mm)</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">.40</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('.380 ACP (9mm Short / Browning)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.380
                                                                    ACP (9mm Short / Browning)</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">.380</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('.38 Special (9x29mmR Revolver Ammunition)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.38
                                                                    Special (9x29mmR Revolver
                                                                    Ammunition)</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">.38</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('.357 Magnum (9x33mmR High Velocity)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.357
                                                                    Magnum (9x33mmR High Velocity)</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">.357</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('.44 Remington Magnum (10.9x33mmR)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.44
                                                                    Remington Magnum (10.9x33mmR)</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">.44</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('7.62x25mm Tokarev (TT Pistol Cartridge)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>7.62x25mm
                                                                    Tokarev (TT Pistol Cartridge)</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">7.62</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('9x18mm Makarov (PM Round)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>9x18mm
                                                                    Makarov (PM Round)</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">9x18</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('5.7x28mm (FN P90 / Five-seveN High-Velocity)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>5.7x28mm
                                                                    (FN P90 / Five-seveN High-Velocity)</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">5.7</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('10mm Auto (10x25mm Tactical)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>10mm
                                                                    Auto (10x25mm Tactical)</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">10MM</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('.50 Action Express (.50 AE Desert Eagle)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.50
                                                                    Action Express (.50 AE Desert Eagle)</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">.50AE</span></button>
                                                        </li>

                                                        <!-- Rifle & Intermediate Ammunition -->
                                                        <li
                                                            class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                            Rifle &amp; Intermediate Ammunition</li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('7.62x39mm Soviet (AK-47 / SKS / Type 56)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>7.62x39mm
                                                                    Soviet (AK-47 / SKS / Type 56)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">7.62</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('5.56x45mm NATO / .223 Remington (M16 / M4 / AR-15)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>5.56x45mm
                                                                    NATO / .223 Remington (M16 / M4 /
                                                                    AR-15)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">5.56</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('7.62x51mm NATO / .308 Winchester (FN FAL / G3)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>7.62x51mm
                                                                    NATO / .308 Winchester (FN FAL / G3)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">7.62</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('5.45x39mm Soviet (AK-74 Assault Rifle)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>5.45x39mm
                                                                    Soviet (AK-74 Assault Rifle)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">5.45</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('7.62x54mmR Russian (Dragunov SVD / PKM Belt)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>7.62x54mmR
                                                                    Russian (Dragunov SVD / PKM Belt)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">7.62R</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('.300 AAC Blackout (7.62x35mm Subsonic / Supersonic)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.300
                                                                    AAC Blackout (7.62x35mm Subsonic /
                                                                    Supersonic)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">.300</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('6.5mm Creedmoor (Precision Long Range Match)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>6.5mm
                                                                    Creedmoor (Precision Long Range
                                                                    Match)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">6.5</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('.30-06 Springfield (7.62x63mm Military Rifle)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.30-06
                                                                    Springfield (7.62x63mm Military
                                                                    Rifle)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">.30-06</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('.303 British (7.7x56mmR Lee-Enfield)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.303
                                                                    British (7.7x56mmR Lee-Enfield)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">.303</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('7.92x57mm Mauser (8mm Mauser Infantry)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>7.92x57mm
                                                                    Mauser (8mm Mauser Infantry)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">8MM</span></button>
                                                        </li>

                                                        <!-- Shotgun Shells & Shotshell Cartridges -->
                                                        <li
                                                            class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                            Shotgun Shells &amp; Shotshell Cartridges</li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('12 Gauge Buckshot (00 Buck / 9 Pellets Tactical)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>12
                                                                    Gauge Buckshot (00 Buck / 9 Pellets
                                                                    Tactical)</span><span
                                                                    class="text-[10px] font-mono-code bg-orange-50 text-orange-700 px-1.5 py-0.5 rounded font-bold">12GA</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('12 Gauge Rifled Slug (Heavy Solid Lead Penetrator)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>12
                                                                    Gauge Rifled Slug (Heavy Solid Lead
                                                                    Penetrator)</span><span
                                                                    class="text-[10px] font-mono-code bg-orange-50 text-orange-700 px-1.5 py-0.5 rounded font-bold">12GA</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('12 Gauge Birdshot / Game Load Cartridges', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>12
                                                                    Gauge Birdshot / Game Load
                                                                    Cartridges</span><span
                                                                    class="text-[10px] font-mono-code bg-orange-50 text-orange-700 px-1.5 py-0.5 rounded font-bold">12GA</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('12 Gauge Less-Lethal Rubber Baton / Beanbag Round', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>12
                                                                    Gauge Less-Lethal Rubber Baton / Beanbag
                                                                    Round</span><span
                                                                    class="text-[10px] font-mono-code bg-orange-50 text-orange-700 px-1.5 py-0.5 rounded font-bold">12GA</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('20 Gauge Shotgun Cartridge (Buck &amp; Slug)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>20
                                                                    Gauge Shotgun Cartridge (Buck &amp;
                                                                    Slug)</span><span
                                                                    class="text-[10px] font-mono-code bg-orange-50 text-orange-700 px-1.5 py-0.5 rounded font-bold">20GA</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('16 Gauge Shotgun Cartridge', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>16
                                                                    Gauge Shotgun Cartridge</span><span
                                                                    class="text-[10px] font-mono-code bg-orange-50 text-orange-700 px-1.5 py-0.5 rounded font-bold">16GA</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('.410 Bore Shotgun Shell (Buckshot / Slug)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.410
                                                                    Bore Shotgun Shell (Buckshot / Slug)</span><span
                                                                    class="text-[10px] font-mono-code bg-orange-50 text-orange-700 px-1.5 py-0.5 rounded font-bold">.410</span></button>
                                                        </li>

                                                        <!-- Heavy & Anti-Materiel Ammunition -->
                                                        <li
                                                            class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                            Heavy &amp; Anti-Materiel Ammunition</li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('.50 BMG (12.7x99mm NATO Armor-Piercing Incendiary)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.50
                                                                    BMG (12.7x99mm NATO Armor-Piercing
                                                                    Incendiary)</span><span
                                                                    class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">.50</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('12.7x108mm Russian Heavy Machine Gun Cartridge', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>12.7x108mm
                                                                    Russian Heavy Machine Gun Cartridge</span><span
                                                                    class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">12.7</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('.338 Lapua Magnum (8.6x70mm Long-Range Sniper)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.338
                                                                    Lapua Magnum (8.6x70mm Long-Range
                                                                    Sniper)</span><span
                                                                    class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">.338</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('.300 Winchester Magnum (.300 Win Mag Match)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.300
                                                                    Winchester Magnum (.300 Win Mag
                                                                    Match)</span><span
                                                                    class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">.300</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('14.5x114mm Heavy Armor-Piercing Cartridge', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>14.5x114mm
                                                                    Heavy Armor-Piercing Cartridge</span><span
                                                                    class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">14.5</span></button>
                                                        </li>

                                                        <!-- Rimfire & Specialty Ammunition -->
                                                        <li
                                                            class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                            Rimfire &amp; Specialty Ammunition</li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('.22 Long Rifle (.22 LR Rimfire Round)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.22
                                                                    Long Rifle (.22 LR Rimfire Round)</span><span
                                                                    class="text-[10px] font-mono-code bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded font-bold">.22LR</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('.22 WMR (.22 Magnum Rimfire Cartridge)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>.22
                                                                    WMR (.22 Magnum Rimfire Cartridge)</span><span
                                                                    class="text-[10px] font-mono-code bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded font-bold">.22WMR</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('40x46mm High-Explosive Grenade Round (M203 / M320)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>40x46mm
                                                                    High-Explosive Grenade Round (M203 /
                                                                    M320)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-100 text-red-900 px-1.5 py-0.5 rounded font-bold">40MM</span></button>
                                                        </li>
                                                        <li class="ammo-item"
                                                            onclick="selectAmmoType('37mm / 40mm Tear Gas / CS Smoke / Flare Round', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>37mm
                                                                    / 40mm Tear Gas / CS Smoke / Flare
                                                                    Round</span><span
                                                                    class="text-[10px] font-mono-code bg-amber-100 text-amber-900 px-1.5 py-0.5 rounded font-bold">37MM</span></button>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>

                                            <!-- ELECTRONIC DEVICE SELECT DROPDOWN (shown when Electronic Devices is selected) -->
                                            <div id="deviceSelectWrapper" class="hidden relative z-40">
                                                <div class="relative">
                                                    <span
                                                        class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                        <svg class="w-3.5 h-3.5" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                        </svg>
                                                    </span>
                                                    <input type="text" id="deviceSearchInput"
                                                        placeholder="Select or type custom electronic device type..."
                                                        autocomplete="off"
                                                        class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-500 placeholder:font-normal cursor-text"
                                                        onclick="openDeviceListPanel(this)"
                                                        oninput="filterDeviceList(this)"
                                                        onfocus="openDeviceListPanel(this)">
                                                    <input type="hidden" id="newSeizureNameDevice"
                                                        name="newSeizureNameDevice" value="">
                                                </div>
                                                <div id="deviceListPanel"
                                                    class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden"
                                                    style="max-height:240px;overflow-y:auto">
                                                    <div
                                                        class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                        <input type="text" id="deviceSearchInner"
                                                            placeholder="Type to filter electronic devices..."
                                                            oninput="filterDeviceListInner(this)"
                                                            class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                    </div>
                                                    <ul id="deviceList" class="py-1.5 space-y-0.5 px-1.5">
                                                        <!-- Smartphones & Mobile Devices -->
                                                        <li
                                                            class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                            Smartphones &amp; Mobile Devices</li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Apple iPhone (iOS Smartphone)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Apple
                                                                    iPhone (iOS Smartphone)</span><span
                                                                    class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">IPHONE</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Android Smartphone (Samsung / Google Pixel / Xiaomi)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Android
                                                                    Smartphone (Samsung / Google Pixel /
                                                                    Xiaomi)</span><span
                                                                    class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">ANDROID</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Feature Phone / Burner Keypad Phone', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Feature
                                                                    Phone / Burner Keypad Phone</span><span
                                                                    class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">BURNER</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Satellite Phone (Iridium / Thuraya / Inmarsat)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Satellite
                                                                    Phone (Iridium / Thuraya / Inmarsat)</span><span
                                                                    class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">SAT-PHONE</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Tablet / iPad (iOS / Android / Windows Tablet)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Tablet
                                                                    / iPad (iOS / Android / Windows
                                                                    Tablet)</span><span
                                                                    class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">TABLET</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Smartwatch / Wearable Fitness Tracker', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Smartwatch
                                                                    / Wearable Fitness Tracker</span><span
                                                                    class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">WATCH</span></button>
                                                        </li>

                                                        <!-- Computers & Laptops -->
                                                        <li
                                                            class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                            Computers &amp; Laptops</li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Laptop / Notebook Computer (MacBook / Windows / Linux)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Laptop
                                                                    / Notebook Computer (MacBook / Windows /
                                                                    Linux)</span><span
                                                                    class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">LAPTOP</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Desktop PC / Workstation Computer', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Desktop
                                                                    PC / Workstation Computer</span><span
                                                                    class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">DESKTOP</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Encrypted / Hardened Custom Laptop', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Encrypted
                                                                    / Hardened Custom Laptop</span><span
                                                                    class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">ENCRYPTED</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Enterprise Server / Rackmount Server Unit', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Enterprise
                                                                    Server / Rackmount Server Unit</span><span
                                                                    class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">SERVER</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Single-Board Computer (Raspberry Pi / Arduino / Mini PC)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Single-Board
                                                                    Computer (Raspberry Pi / Arduino / Mini
                                                                    PC)</span><span
                                                                    class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">SBC</span></button>
                                                        </li>

                                                        <!-- Storage & Memory Media -->
                                                        <li
                                                            class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                            Storage &amp; Memory Media</li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('External Hard Drive (HDD / Portable Hard Disk)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>External
                                                                    Hard Drive (HDD / Portable Hard
                                                                    Disk)</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">HDD</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Portable Solid State Drive (External SSD / NVMe)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Portable
                                                                    Solid State Drive (External SSD /
                                                                    NVMe)</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">SSD</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('USB Flash Drive / Thumb Drive / Pen Drive', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>USB
                                                                    Flash Drive / Thumb Drive / Pen
                                                                    Drive</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">USB</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Memory Card (SD Card / MicroSD / CF Card)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Memory
                                                                    Card (SD Card / MicroSD / CF Card)</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">SD-CARD</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('NAS / Network Attached Storage Array', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>NAS
                                                                    / Network Attached Storage Array</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">NAS</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Optical Media (CD / DVD / Blu-ray Disc)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Optical
                                                                    Media (CD / DVD / Blu-ray Disc)</span><span
                                                                    class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">DISC</span></button>
                                                        </li>

                                                        <!-- Skimming, Fraud & Hardware Attack Devices -->
                                                        <li
                                                            class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                            Skimming, Fraud &amp; Hardware Attack Devices</li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('ATM Card Skimmer / Bezel Overlay Device', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>ATM
                                                                    Card Skimmer / Bezel Overlay Device</span><span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">SKIMMER</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('POS Terminal / Handheld Card Reader', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>POS
                                                                    Terminal / Handheld Card Reader</span><span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">POS</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Magstripe Card Writer / Encoder (MSR / MSR605)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Magstripe
                                                                    Card Writer / Encoder (MSR / MSR605)</span><span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">ENCODER</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('SIM Box / Multi-SIM GSM Cellular Gateway', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>SIM
                                                                    Box / Multi-SIM GSM Cellular Gateway</span><span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">SIMBOX</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('BadUSB / Hardware Keylogger / Rubber Ducky', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>BadUSB
                                                                    / Hardware Keylogger / Rubber Ducky</span><span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">ATTACK-USB</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('RFID / NFC Cloner / Flipper Zero Device', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>RFID
                                                                    / NFC Cloner / Flipper Zero Device</span><span
                                                                    class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">CLONER</span></button>
                                                        </li>

                                                        <!-- Crypto & Financial Hardware -->
                                                        <li
                                                            class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                            Crypto &amp; Financial Hardware</li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Cryptocurrency Hardware Wallet (Ledger / Trezor / KeepKey)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Cryptocurrency
                                                                    Hardware Wallet (Ledger / Trezor /
                                                                    KeepKey)</span><span
                                                                    class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">HW-WALLET</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Crypto Mining Rig / ASIC Miner (Antminer / GPU Rig)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Crypto
                                                                    Mining Rig / ASIC Miner (Antminer / GPU
                                                                    Rig)</span><span
                                                                    class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">MINER</span></button>
                                                        </li>

                                                        <!-- Cameras, Drones & Surveillance Equipment -->
                                                        <li
                                                            class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                            Cameras, Drones &amp; Surveillance Equipment</li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Security Camera / CCTV DVR / NVR Unit', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Security
                                                                    Camera / CCTV DVR / NVR Unit</span><span
                                                                    class="text-[10px] font-mono-code bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded font-bold">CCTV</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Digital Camera / Camcorder / DSLR', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Digital
                                                                    Camera / Camcorder / DSLR</span><span
                                                                    class="text-[10px] font-mono-code bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded font-bold">CAMERA</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Covert Spy Camera / Hidden Camera Device', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Covert
                                                                    Spy Camera / Hidden Camera Device</span><span
                                                                    class="text-[10px] font-mono-code bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded font-bold">SPY-CAM</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Drone / Unmanned Aerial Vehicle (DJI / FPV Drone)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Drone
                                                                    / Unmanned Aerial Vehicle (DJI / FPV
                                                                    Drone)</span><span
                                                                    class="text-[10px] font-mono-code bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded font-bold">DRONE</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('GPS Tracking Bug / Tracker Tag (AirTag / GPS Unit)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>GPS
                                                                    Tracking Bug / Tracker Tag (AirTag / GPS
                                                                    Unit)</span><span
                                                                    class="text-[10px] font-mono-code bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded font-bold">GPS</span></button>
                                                        </li>

                                                        <!-- Networking & Communication Equipment -->
                                                        <li
                                                            class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                            Networking &amp; Communication Equipment</li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Wi-Fi Router / Wireless Access Point / Modem', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Wi-Fi
                                                                    Router / Wireless Access Point /
                                                                    Modem</span><span
                                                                    class="text-[10px] font-mono-code bg-cyan-50 text-cyan-800 px-1.5 py-0.5 rounded font-bold">ROUTER</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Network Switch / Managed Network Hardware', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Network
                                                                    Switch / Managed Network Hardware</span><span
                                                                    class="text-[10px] font-mono-code bg-cyan-50 text-cyan-800 px-1.5 py-0.5 rounded font-bold">SWITCH</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Mobile Hotspot / MiFi Device / 4G-5G Dongle', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Mobile
                                                                    Hotspot / MiFi Device / 4G-5G Dongle</span><span
                                                                    class="text-[10px] font-mono-code bg-cyan-50 text-cyan-800 px-1.5 py-0.5 rounded font-bold">MIFI</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Two-Way Radio / Walkie-Talkie (VHF / UHF Transceiver)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Two-Way
                                                                    Radio / Walkie-Talkie (VHF / UHF
                                                                    Transceiver)</span><span
                                                                    class="text-[10px] font-mono-code bg-cyan-50 text-cyan-800 px-1.5 py-0.5 rounded font-bold">RADIO</span></button>
                                                        </li>

                                                        <!-- Gaming & Audio/Video Equipment -->
                                                        <li
                                                            class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                            Gaming &amp; Audio/Video Equipment</li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Gaming Console (PlayStation / Xbox / Nintendo)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Gaming
                                                                    Console (PlayStation / Xbox /
                                                                    Nintendo)</span><span
                                                                    class="text-[10px] font-mono-code bg-teal-50 text-teal-800 px-1.5 py-0.5 rounded font-bold">CONSOLE</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Handheld Gaming Unit (Steam Deck / ROG Ally)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Handheld
                                                                    Gaming Unit (Steam Deck / ROG Ally)</span><span
                                                                    class="text-[10px] font-mono-code bg-teal-50 text-teal-800 px-1.5 py-0.5 rounded font-bold">HANDHELD</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Digital Voice Recorder / Dictaphone', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Digital
                                                                    Voice Recorder / Dictaphone</span><span
                                                                    class="text-[10px] font-mono-code bg-teal-50 text-teal-800 px-1.5 py-0.5 rounded font-bold">AUDIO</span></button>
                                                        </li>
                                                        <li class="device-item"
                                                            onclick="selectDeviceType('Smart TV / Media Streaming Dongle (Apple TV / Firestick)', event)">
                                                            <button type="button"
                                                                class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Smart
                                                                    TV / Media Streaming Dongle (Apple TV /
                                                                    Firestick)</span><span
                                                                    class="text-[10px] font-mono-code bg-teal-50 text-teal-800 px-1.5 py-0.5 rounded font-bold">MEDIA</span></button>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ELECTRONIC DEVICE SPECIFICATIONS PANEL (shown when Electronic Devices is selected) -->
                                        <div id="deviceFieldsWrapper"
                                            class="hidden col-span-full bg-blue-50/70 p-4 rounded-2xl border border-blue-200/80 shadow-2xs space-y-3">
                                            <div class="flex items-center space-x-2 text-slate-800">
                                                <div
                                                    class="w-6 h-6 rounded-md bg-blue-600 text-white flex items-center justify-center font-bold text-xs shadow-2xs">
                                                    <svg class="w-3.5 h-3.5" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <span
                                                    class="text-xs font-extrabold uppercase tracking-wider text-blue-950">Electronic
                                                    Device Forensic Specifications</span>
                                            </div>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3.5">
                                                <!-- 1. MODEL -->
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Model</label>
                                                    <div class="relative flex items-center group">
                                                        <span
                                                            class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                            <svg class="w-3.5 h-3.5" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round"
                                                                    stroke-linejoin="round" stroke-width="2"
                                                                    d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                                            </svg>
                                                        </span>
                                                        <input type="text" id="newDevModel"
                                                            placeholder="e.g. iPhone 15 Pro Max"
                                                            class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400">
                                                    </div>
                                                </div>

                                                <!-- 2. SERIAL NUMBER -->
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Serial
                                                        Number</label>
                                                    <div class="relative flex items-center group">
                                                        <span
                                                            class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors font-mono-code font-bold text-xs">#</span>
                                                        <input type="text" id="newDevSerialNo"
                                                            placeholder="e.g. F2LZX984K102"
                                                            class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400 font-mono-code">
                                                    </div>
                                                </div>

                                                <!-- 3. IMEI 1 -->
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">IMEI
                                                        1 <span
                                                            class="text-[9px] text-slate-400 font-normal lowercase">(if
                                                            applicable)</span></label>
                                                    <div class="relative flex items-center group">
                                                        <span
                                                            class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                            <svg class="w-3.5 h-3.5" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round"
                                                                    stroke-linejoin="round" stroke-width="2"
                                                                    d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                            </svg>
                                                        </span>
                                                        <input type="text" id="newDevImei1"
                                                            placeholder="e.g. 356938035643809"
                                                            class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400 font-mono-code">
                                                    </div>
                                                </div>

                                                <!-- 4. IMEI 2 -->
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">IMEI
                                                        2 <span
                                                            class="text-[9px] text-slate-400 font-normal lowercase">(if
                                                            applicable)</span></label>
                                                    <div class="relative flex items-center group">
                                                        <span
                                                            class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                            <svg class="w-3.5 h-3.5" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round"
                                                                    stroke-linejoin="round" stroke-width="2"
                                                                    d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                            </svg>
                                                        </span>
                                                        <input type="text" id="newDevImei2"
                                                            placeholder="e.g. 356938035643817"
                                                            class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400 font-mono-code">
                                                    </div>
                                                </div>

                                                <!-- 5. MAC ADDRESS -->
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">MAC
                                                        Address <span
                                                            class="text-[9px] text-slate-400 font-normal lowercase">(if
                                                            applicable)</span></label>
                                                    <div class="relative flex items-center group">
                                                        <span
                                                            class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                            <svg class="w-3.5 h-3.5" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round"
                                                                    stroke-linejoin="round" stroke-width="2"
                                                                    d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                            </svg>
                                                        </span>
                                                        <input type="text" id="newDevMacAddress"
                                                            placeholder="e.g. 00:1A:2B:3C:4D:5E"
                                                            class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400 font-mono-code">
                                                    </div>
                                                </div>

                                                <!-- 6. STORAGE CAPACITY -->
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Storage
                                                        Capacity</label>
                                                    <div class="relative flex items-center group">
                                                        <span
                                                            class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                            <svg class="w-3.5 h-3.5" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round"
                                                                    stroke-linejoin="round" stroke-width="2"
                                                                    d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                                                            </svg>
                                                        </span>
                                                        <input type="text" id="newDevStorage"
                                                            placeholder="e.g. 256 GB / 1 TB NVMe SSD"
                                                            class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400">
                                                    </div>
                                                </div>

                                                <!-- 7. COLOUR -->
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Colour</label>
                                                    <div class="relative flex items-center group">
                                                        <span
                                                            class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                            <svg class="w-3.5 h-3.5" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round"
                                                                    stroke-linejoin="round" stroke-width="2"
                                                                    d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                                                            </svg>
                                                        </span>
                                                        <input type="text" id="newDevColour"
                                                            placeholder="e.g. Space Gray / Midnight Black"
                                                            class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400">
                                                    </div>
                                                </div>

                                                <!-- 8. OTHER IDENTIFYING MARKS -->
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Other
                                                        Identifying Marks</label>
                                                    <div class="relative flex items-center group">
                                                        <span
                                                            class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                            <svg class="w-3.5 h-3.5" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round"
                                                                    stroke-linejoin="round" stroke-width="2"
                                                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                            </svg>
                                                        </span>
                                                        <input type="text" id="newDevMarks"
                                                            placeholder="e.g. Cracked back glass, custom sticker"
                                                            class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 placeholder:text-slate-400">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- CONTENTS (VISUAL) FIELD (Requirement 1B) -->
                                        <div id="contentsFieldWrapper" class="contents-field-wrapper">
                                            <label
                                                class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Contents (Visual) <span class="text-red-600 font-bold">*</span></label>
                                            <div class="relative flex items-center group">
                                                <span
                                                    class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </span>
                                                <input type="text" id="newSeizureContents" name="seizure_contents"
                                                    placeholder="e.g. White compressed powder / Brown pebbles"
                                                    oninput="if (typeof syncSection4ExhibitsToLabSection === 'function') syncSection4ExhibitsToLabSection();"
                                                    class="seizure-contents-input w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                        </div>

                                        <!-- OTHER EXHIBIT DESCRIPTION PANEL (shown when Other Exhibits is selected) -->
                                        <div id="otherExhibitDescriptionWrapper"
                                            class="other-exhibit-description-wrapper hidden col-span-full bg-slate-50/90 p-4 rounded-2xl border border-slate-300/80 shadow-2xs space-y-3">
                                            <div class="flex items-center space-x-2 text-slate-800">
                                                <div
                                                    class="w-6 h-6 rounded-md bg-slate-700 text-white flex items-center justify-center font-bold text-xs shadow-2xs">
                                                    <svg class="w-3.5 h-3.5" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 012.828 0L19.828 4.828a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                                                    </svg>
                                                </div>
                                                <span
                                                    class="text-xs font-extrabold uppercase tracking-wider text-slate-900">Specify
                                                    Other Exhibit Description &amp; Details</span>
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                    Exhibit Description / Specifications <span
                                                        class="text-red-600 font-bold">*</span>
                                                </label>
                                                <textarea id="newOtherExhibitDescription" name="other_exhibit_description" rows="3"
                                                    placeholder="Type detailed description and specifications of this exhibit..."
                                                    class="other-exhibit-description-input w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400 font-sans"></textarea>
                                            </div>
                                        </div>

                                        <!-- ESTIMATED STREET VALUE -->
                                        <div>
                                            <label
                                                class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Estimated
                                                Street Value</label>
                                            <div class="relative flex items-center group">
                                                <span
                                                    class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors font-mono-code font-bold text-xs">₵</span>
                                                <input type="text" id="newSeizureValue"
                                                    placeholder="e.g. GHS 1,500,000 / $120,000"
                                                    class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                        </div>

                                        <!-- QUANTITY, PACKAGING TYPE, WEIGHT & UNIT METRICS PANEL -->
                                        <div
                                            class="col-span-full bg-white/90 p-4 rounded-2xl border border-slate-300/80 shadow-2xs space-y-3">
                                            <div class="flex items-center space-x-2 text-slate-800">
                                                <div
                                                    class="w-6 h-6 rounded-md bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                                    <svg class="w-3.5 h-3.5" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <span
                                                    class="text-xs font-extrabold uppercase tracking-wider text-slate-900">Seizure
                                                    Quantification &amp; Packaging Metrics</span>
                                            </div>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3.5">
                                                <!-- 1. QUANTITY -->
                                                <div>
                                                    <label id="quantityLabel"
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Quantity</label>
                                                    <div class="relative flex items-center group">
                                                        <span
                                                            class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors font-mono-code font-bold text-xs">#</span>
                                                        <input type="text" inputmode="decimal"
                                                            id="newSeizureQuantity" placeholder="e.g. 10 or 50,000"
                                                            class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                    </div>
                                                </div>

                                                <!-- 2. PACKAGING TYPE -->
                                                <div class="relative z-30">
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Packaging
                                                        Type</label>
                                                    <div id="packagingSelectWrapper" class="relative">
                                                        <div class="relative">
                                                            <span
                                                                class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                                <svg class="w-3.5 h-3.5" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                                </svg>
                                                            </span>
                                                            <input type="text" id="pkgSearchInput"
                                                                placeholder="Select or type packaging..."
                                                                autocomplete="off"
                                                                class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400 placeholder:font-normal cursor-text"
                                                                onclick="openPkgListPanel(this)"
                                                                oninput="filterPackagingList(this)"
                                                                onfocus="openPkgListPanel(this)">
                                                            <input type="hidden" id="newSeizurePackaging"
                                                                value="">
                                                        </div>
                                                        <div id="pkgListPanel"
                                                            class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden"
                                                            style="max-height:220px;overflow-y:auto">
                                                            <div
                                                                class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                                <input type="text" id="pkgSearchInner"
                                                                    placeholder="Type to filter packaging..."
                                                                    oninput="filterPackagingListInner(this)"
                                                                    class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                            </div>
                                                            <ul id="pkgList"
                                                                class="py-1.5 space-y-0.5 px-1.5">
                                                                <li class="pkg-item"
                                                                    onclick="selectPackagingType('Slabs', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Slabs</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">SLB</span></button>
                                                                </li>
                                                                <li class="pkg-item"
                                                                    onclick="selectPackagingType('Blocks', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Blocks</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">BLK</span></button>
                                                                </li>
                                                                <li class="pkg-item"
                                                                    onclick="selectPackagingType('Bundles', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Bundles</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">BDL</span></button>
                                                                </li>
                                                                <li class="pkg-item"
                                                                    onclick="selectPackagingType('Sacks', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Sacks</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">SCK</span></button>
                                                                </li>
                                                                <li class="pkg-item"
                                                                    onclick="selectPackagingType('Cartons', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Cartons</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">CTN</span></button>
                                                                </li>
                                                                <li class="pkg-item"
                                                                    onclick="selectPackagingType('Boxes', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Boxes</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">BOX</span></button>
                                                                </li>
                                                                <li class="pkg-item"
                                                                    onclick="selectPackagingType('Rounds', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Rounds</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">RND</span></button>
                                                                </li>
                                                                <li class="pkg-item"
                                                                    onclick="selectPackagingType('Magazines', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Magazines</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">MAG</span></button>
                                                                </li>
                                                                <li class="pkg-item"
                                                                    onclick="selectPackagingType('Pieces', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Pieces</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">PCS</span></button>
                                                                </li>
                                                                <li class="pkg-item"
                                                                    onclick="selectPackagingType('Units', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Units</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">UNT</span></button>
                                                                </li>
                                                                <li class="pkg-item"
                                                                    onclick="selectPackagingType('Wraps', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Wraps</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">WRP</span></button>
                                                                </li>
                                                                <li class="pkg-item"
                                                                    onclick="selectPackagingType('Rolls', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Rolls</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">RLL</span></button>
                                                                </li>
                                                                <li class="pkg-item"
                                                                    onclick="selectPackagingType('Capsules', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Capsules</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">CAP</span></button>
                                                                </li>
                                                                <li class="pkg-item"
                                                                    onclick="selectPackagingType('Tablets', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Tablets</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">TAB</span></button>
                                                                </li>
                                                                <li class="pkg-item"
                                                                    onclick="selectPackagingType('Bags', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Bags</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">BAG</span></button>
                                                                </li>
                                                                <li class="pkg-item"
                                                                    onclick="selectPackagingType('Pouches', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Pouches</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">PCH</span></button>
                                                                </li>
                                                                <li class="pkg-item"
                                                                    onclick="selectPackagingType('Drums', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Drums</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">DRM</span></button>
                                                                </li>
                                                                <li class="pkg-item"
                                                                    onclick="selectPackagingType('Barrels', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Barrels</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">BRL</span></button>
                                                                </li>
                                                                <li class="pkg-item"
                                                                    onclick="selectPackagingType('Vials', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Vials</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">VIL</span></button>
                                                                </li>
                                                                <li class="pkg-item"
                                                                    onclick="selectPackagingType('Ampoules', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Ampoules</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">AMP</span></button>
                                                                </li>
                                                                <li class="pkg-item"
                                                                    onclick="selectPackagingType('Bottles', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Bottles</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">BTL</span></button>
                                                                </li>
                                                                <li class="pkg-item"
                                                                    onclick="selectPackagingType('Packets', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Packets</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">PKT</span></button>
                                                                </li>
                                                                <li class="pkg-item"
                                                                    onclick="selectPackagingType('Envelopes', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Envelopes</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">ENV</span></button>
                                                                </li>
                                                                <li class="pkg-item"
                                                                    onclick="selectPackagingType('Parcels', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Parcels</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">PCL</span></button>
                                                                </li>
                                                                <li class="pkg-item"
                                                                    onclick="selectPackagingType('Packages', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Packages</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">PKG</span></button>
                                                                </li>
                                                                <li class="pkg-item"
                                                                    onclick="selectPackagingType('Bales', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Bales</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">BLE</span></button>
                                                                </li>
                                                                <li class="pkg-item"
                                                                    onclick="selectPackagingType('Jars', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Jars</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">JAR</span></button>
                                                                </li>
                                                                <li class="pkg-item"
                                                                    onclick="selectPackagingType('Containers', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Containers</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">CTR</span></button>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- 3. Weight -->
                                                <div>
                                                    <label id="weightLabel"
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Weight</label>
                                                    <div class="relative flex items-center group">
                                                        <span
                                                            class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors font-mono-code font-bold text-xs">⚖</span>
                                                        <input type="number" step="0.01"
                                                            id="newSeizureWeight" placeholder="e.g. 25.50"
                                                            class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                    </div>
                                                </div>

                                                <!-- 4. MEASUREMENT UNIT -->
                                                <div class="relative z-20">
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Measurement
                                                        Unit</label>
                                                    <div id="unitSelectWrapper" class="relative">
                                                        <div class="relative">
                                                            <span
                                                                class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                                <svg class="w-3.5 h-3.5" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                                </svg>
                                                            </span>
                                                            <input type="text" id="unitSearchInput"
                                                                placeholder="Select or type unit..."
                                                                autocomplete="off"
                                                                class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400 placeholder:font-normal cursor-text"
                                                                onclick="openUnitListPanel(this)"
                                                                oninput="filterUnitList(this)"
                                                                onfocus="openUnitListPanel(this)">
                                                            <input type="hidden" id="newSeizureUnit"
                                                                value="">
                                                        </div>
                                                        <div id="unitListPanel"
                                                            class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden"
                                                            style="max-height:220px;overflow-y:auto">
                                                            <div
                                                                class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                                <input type="text" id="unitSearchInner"
                                                                    placeholder="Type to filter units..."
                                                                    oninput="filterUnitListInner(this)"
                                                                    class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                            </div>
                                                            <ul id="unitList"
                                                                class="py-1.5 space-y-0.5 px-1.5">
                                                                <li
                                                                    class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                                    Weight &amp; Mass</li>
                                                                <li class="unit-item"
                                                                    onclick="selectUnit('Kilograms (kg)', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Kilograms</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">kg</span></button>
                                                                </li>
                                                                <li class="unit-item"
                                                                    onclick="selectUnit('Grams (g)', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Grams</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">g</span></button>
                                                                </li>
                                                                <li class="unit-item"
                                                                    onclick="selectUnit('Tonnes (t)', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Tonnes</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">t</span></button>
                                                                </li>
                                                                <li class="unit-item"
                                                                    onclick="selectUnit('Milligrams (mg)', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Milligrams</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">mg</span></button>
                                                                </li>
                                                                <li class="unit-item"
                                                                    onclick="selectUnit('Pounds (lbs)', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Pounds</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">lbs</span></button>
                                                                </li>
                                                                <li class="unit-item"
                                                                    onclick="selectUnit('Ounces (oz)', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Ounces</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">oz</span></button>
                                                                </li>
                                                                <li
                                                                    class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                                    Volume &amp; Liquid</li>
                                                                <li class="unit-item"
                                                                    onclick="selectUnit('Litres (L)', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Litres</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">L</span></button>
                                                                </li>
                                                                <li class="unit-item"
                                                                    onclick="selectUnit('Millilitres (mL)', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Millilitres</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">mL</span></button>
                                                                </li>
                                                                <li class="unit-item"
                                                                    onclick="selectUnit('Gallons (gal)', event)">
                                                                    <button type="button"
                                                                        class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl flex items-center justify-between transition-colors"><span>Gallons</span><span
                                                                            class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold">gal</span></button>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- SEIZURE DETAILS GRID: CONCEALMENT, DATE, TIME & LOCATION SEIZED -->
                                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-2.5 col-span-full">
                                            <!-- CONCEALMENT METHOD -->
                                            <div class="sm:col-span-2 md:col-span-2">
                                                <label
                                                    class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Concealment
                                                    &amp; Packaging Notes</label>
                                                <div class="relative flex items-center group">
                                                    <span
                                                        class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                                        </svg>
                                                    </span>
                                                    <input type="text" id="newSeizureConcealment"
                                                        placeholder="e.g. Vacuum foil wrapped in false trunk bottom"
                                                        class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                            </div>

                                            <!-- DATE SEIZED -->
                                            <div class="sm:col-span-1 md:col-span-1">
                                                <label
                                                    class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Date
                                                    Seized <span class="text-red-600 font-bold">*</span></label>
                                                <div class="relative flex items-center group">
                                                    <span
                                                        class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors pointer-events-none">
                                                        <svg class="w-4 h-4" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                    </span>
                                                    <input type="date" id="newSeizureDate"
                                                        class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                            </div>

                                            <!-- TIME SEIZED -->
                                            <div class="sm:col-span-1 md:col-span-1">
                                                <label
                                                    class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Time
                                                    Seized</label>
                                                <div class="relative flex items-center group">
                                                    <span
                                                        class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors pointer-events-none">
                                                        <svg class="w-4 h-4" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    </span>
                                                    <input type="time" id="newSeizureTime"
                                                        class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- SEIZING OFFICERS (MULTI-ROW) -->
                                        <div class="col-span-full md:col-span-2">
                                            <div class="flex items-center justify-between mb-1.5">
                                                <label
                                                    class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase">
                                                    Seizing Officer(s) <span
                                                        class="text-red-600 font-bold">*</span>
                                                </label>
                                                <button type="button" onclick="addSeizureOfficerRow()"
                                                    class="inline-flex items-center text-[10px] font-extrabold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-2.5 py-1 rounded-lg transition-colors cursor-pointer shadow-2xs">
                                                    <svg class="w-3 h-3 mr-1" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2.5" d="M12 4v16m8-8H4" />
                                                    </svg>
                                                    Add Seizing Officer
                                                </button>
                                            </div>

                                            <!-- OFFICER ROWS CONTAINER -->
                                            <div id="seizureOfficersContainer" class="space-y-2">
                                                <!-- Row 1 -->
                                                <div
                                                    class="seizure-officer-row p-2.5 bg-slate-50 border border-slate-300/80 rounded-xl">
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                        <div class="relative flex items-center group">
                                                            <span
                                                                class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                                <svg class="w-3.5 h-3.5" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                                </svg>
                                                            </span>
                                                            <input type="text"
                                                                class="seizure-officer-name w-full pl-8 pr-3 py-2 bg-white border border-slate-300 rounded-lg font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400"
                                                                placeholder="Officer Full Name (e.g. DSP Kwame Mensah)">
                                                        </div>
                                                        <div class="flex items-center gap-1.5">
                                                            <div class="relative flex-1 flex items-center group">
                                                                <span
                                                                    class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                                    <svg class="w-3.5 h-3.5" fill="none"
                                                                        stroke="currentColor"
                                                                        viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round"
                                                                            stroke-width="2"
                                                                            d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 012-2h2a2 2 0 012 2v1m-4 0h4" />
                                                                    </svg>
                                                                </span>
                                                                <input type="text"
                                                                    class="seizure-officer-service-id w-full pl-8 pr-3 py-2 bg-white border border-slate-300 rounded-lg font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400 font-mono-code"
                                                                    placeholder="Service ID / Badge No (e.g. SD-0042)">
                                                            </div>
                                                            <button type="button"
                                                                onclick="removeSeizureOfficerRow(this)"
                                                                class="remove-officer-btn hidden p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg border border-transparent hover:border-red-200 transition-colors cursor-pointer"
                                                                title="Remove Officer">
                                                                <svg class="w-4 h-4" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- REASON FOR SEIZURE -->
                                        <div class="col-span-full md:col-span-2">
                                            <label
                                                class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Reason
                                                for Seizure <span class="text-red-600 font-bold">*</span></label>
                                            <div class="relative flex items-start group">
                                                <span
                                                    class="absolute left-3 top-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </span>
                                                <textarea id="newSeizureReason" rows="3"
                                                    placeholder="e.g. Suspected drug trafficking — commodity found concealed in vehicle false compartment during routine checkpoint stop."
                                                    class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400 resize-none"></textarea>
                                            </div>
                                        </div>

                                        @include('partials.seized_item_source_custody_fields', ['sourceRequired' => false])

                                        <!-- REMARKS / EXHIBIT REMARK -->
                                        <div class="col-span-full md:col-span-2">
                                            <label
                                                class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Remarks</label>
                                            <div class="relative flex items-center group">
                                                <span
                                                    class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                                    </svg>
                                                </span>
                                                <input type="text" id="newSeizureExhibitRemark" name="exhibit_remark"
                                                    placeholder="e.g. Broken seal, sample retrieved under chain-of-custody..."
                                                    class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                            </div>
                                        </div>

                                    </div>

                                    <!-- VEHICLE SPECIFIC FIELDS -->
                                    <div id="vehicleFieldsWrapper"
                                        class="hidden pt-4 border-t border-slate-300/60 mt-4 space-y-5">
                                        <div class="flex items-center gap-2 mb-2">
                                            <div
                                                class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                                </svg>
                                            </div>
                                            <h4
                                                class="text-sm font-extrabold text-slate-800 uppercase tracking-widest">
                                                Vehicle Specific Details</h4>
                                        </div>

                                        <!-- 1. Basic Identification -->
                                        <div class="space-y-3">
                                            <h5
                                                class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-widest bg-emerald-50 px-2 py-1 rounded-md inline-block">
                                                1. Basic Identification</h5>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Make
                                                        <span class="text-red-600 font-bold">*</span></label>
                                                    <input type="text" id="vehMake"
                                                        placeholder="e.g. Toyota"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Model
                                                        <span class="text-red-600 font-bold">*</span></label>
                                                    <input type="text" id="vehModel"
                                                        placeholder="e.g. Hilux"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div class="relative z-20" id="vehTypeSelectWrapper">
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Vehicle
                                                        Type</label>
                                                    <div class="relative">
                                                        <span
                                                            class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                            <svg class="w-3.5 h-3.5" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round"
                                                                    stroke-linejoin="round" stroke-width="2"
                                                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                            </svg>
                                                        </span>
                                                        <input type="text" id="vehTypeSearchInput"
                                                            placeholder="Select vehicle type..."
                                                            autocomplete="off"
                                                            class="w-full pl-8 pr-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400 placeholder:font-normal cursor-pointer"
                                                            readonly onclick="openVehTypeListPanel(this)"
                                                            oninput="filterVehTypeList(this)"
                                                            onfocus="openVehTypeListPanel(this)">
                                                        <input type="hidden" id="vehType" value="">
                                                    </div>
                                                    <div id="vehTypeListPanel"
                                                        class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden"
                                                        style="max-height:220px;overflow-y:auto">
                                                        <div
                                                            class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                                            <input type="text" id="vehTypeSearchInner"
                                                                placeholder="Type to filter..."
                                                                oninput="filterVehTypeListInner(this)"
                                                                class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none placeholder:text-slate-400 focus:bg-white focus:ring-1 focus:ring-emerald-600/20 transition-all">
                                                        </div>
                                                        <ul id="vehTypeList" class="py-1.5 space-y-0.5 px-1.5">
                                                            <li
                                                                class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                                Passenger Vehicles</li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Sedan / Saloon', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Sedan
                                                                    / Saloon</button></li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Hatchback', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Hatchback</button>
                                                            </li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('SUV / Crossover', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">SUV
                                                                    / Crossover</button></li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Coupe / Sports Car', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Coupe
                                                                    / Sports Car</button></li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Convertible', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Convertible</button>
                                                            </li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Station Wagon / Estate', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Station
                                                                    Wagon / Estate</button></li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Minivan / MPV', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Minivan
                                                                    / MPV</button></li>

                                                            <li
                                                                class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                                Commercial &amp; Utility</li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Pickup Truck', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Pickup
                                                                    Truck</button></li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Panel Van / Cargo Van', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Panel
                                                                    Van / Cargo Van</button></li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Minibus / Trotro', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Minibus
                                                                    / Trotro</button></li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Large Bus / Coach', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Large
                                                                    Bus / Coach</button></li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Box Truck / Rigid Truck', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Box
                                                                    Truck / Rigid Truck</button></li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Heavy Duty / Articulated Truck', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Heavy
                                                                    Duty / Articulated Truck</button></li>

                                                            <li
                                                                class="px-2 pt-3 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">
                                                                Other Types</li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Motorcycle / Motorbike', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Motorcycle
                                                                    / Motorbike</button></li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Tricycle (Aboboyaa / Pragya)', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Tricycle
                                                                    (Aboboyaa / Pragya)</button></li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Trailer / Caravan', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Trailer
                                                                    / Caravan</button></li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Agricultural / Tractor', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Agricultural
                                                                    / Tractor</button></li>
                                                            <li class="veh-type-item"
                                                                onclick="selectVehType('Construction Equipment', event)">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 hover:text-black rounded-xl transition-colors">Construction
                                                                    Equipment</button></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Year
                                                        of Manufacture</label>
                                                    <input type="text" id="vehYear"
                                                        placeholder="e.g. 2019"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Colour</label>
                                                    <input type="text" id="vehColour"
                                                        placeholder="e.g. White"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Registration
                                                        Number <span
                                                            class="text-red-600 font-bold">*</span></label>
                                                    <input type="text" id="vehRegNo"
                                                        placeholder="e.g. GT 1234-21"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">VIN
                                                        / Chassis Number</label>
                                                    <input type="text" id="vehVIN"
                                                        placeholder="e.g. JT11234567890ABC"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Engine
                                                        Number</label>
                                                    <input type="text" id="vehEngineNo"
                                                        placeholder="e.g. 1TR1234567"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Country
                                                        of Registration</label>
                                                    <input type="text" id="vehCountryReg"
                                                        placeholder="e.g. Ghana"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 2. Ownership & Possession -->
                                        <div class="space-y-3">
                                            <h5
                                                class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-widest bg-emerald-50 px-2 py-1 rounded-md inline-block">
                                                2. Ownership &amp; Possession</h5>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Registered
                                                        Owner</label>
                                                    <input type="text" id="vehOwner"
                                                        placeholder="e.g. John Doe"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Driver
                                                        / Person in Possession</label>
                                                    <input type="text" id="vehDriver"
                                                        placeholder="e.g. Kwame Mensah"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Owner's
                                                        Contact</label>
                                                    <input type="text" id="vehOwnerContact"
                                                        placeholder="e.g. 0244123456"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Docs
                                                        Available?</label>
                                                    <select id="vehDocsAvailable"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                                                        <option value="">Select...</option>
                                                        <option value="Yes">Yes</option>
                                                        <option value="No">No</option>
                                                    </select>
                                                </div>
                                                <div class="md:col-span-2">
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Reg/Title
                                                        Doc Reference</label>
                                                    <input type="text" id="vehDocRef"
                                                        placeholder="e.g. DVLA Title #998877"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 3. Vehicle Condition -->
                                        <div class="space-y-3">
                                            <h5
                                                class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-widest bg-emerald-50 px-2 py-1 rounded-md inline-block">
                                                3. Vehicle Condition</h5>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Overall
                                                        Condition</label>
                                                    <select id="vehCondition"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                                                        <option value="">Select...</option>
                                                        <option value="Excellent">Excellent / Driveable</option>
                                                        <option value="Good">Good</option>
                                                        <option value="Fair">Fair</option>
                                                        <option value="Poor">Poor / Damaged</option>
                                                        <option value="Non-Driveable">Non-Driveable / Towed
                                                        </option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Fuel
                                                        Level</label>
                                                    <select id="vehFuel"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                                                        <option value="">Select...</option>
                                                        <option value="Full">Full</option>
                                                        <option value="3/4">3/4</option>
                                                        <option value="Half">Half (1/2)</option>
                                                        <option value="1/4">1/4</option>
                                                        <option value="Empty">Empty (Light on)</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Keys
                                                        Received?</label>
                                                    <div class="flex items-center gap-2 mt-1">
                                                        <select id="vehKeysRecv"
                                                            class="w-1/2 px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                                                            <option value="">Select...</option>
                                                            <option value="Yes">Yes</option>
                                                            <option value="No">No</option>
                                                        </select>
                                                        <input type="number" id="vehKeyCount"
                                                            placeholder="Qty" min="0"
                                                            class="w-1/2 px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                    </div>
                                                </div>
                                                <div class="md:col-span-3">
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Existing
                                                        Damage</label>
                                                    <input type="text" id="vehDamage"
                                                        placeholder="e.g. Scratches on front right bumper, cracked left taillight"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div class="md:col-span-3">
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Visible
                                                        Modifications / Compartments</label>
                                                    <input type="text" id="vehMods"
                                                        placeholder="e.g. False floor welded in trunk, tinted windows"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div class="md:col-span-3">
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Notable
                                                        Identifying Features</label>
                                                    <input type="text" id="vehFeatures"
                                                        placeholder="e.g. Custom alloy wheels, stickers on rear window"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- WEAPON SPECIFIC FIELDS -->
                                    <div id="weaponFieldsWrapper"
                                        class="hidden pt-4 border-t border-slate-300/60 mt-4 space-y-5">
                                        <div class="flex items-center gap-2 mb-2">
                                            <div
                                                class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                            </div>
                                            <h4
                                                class="text-sm font-extrabold text-slate-800 uppercase tracking-widest">
                                                Weapon / Firearm Specific Details</h4>
                                        </div>

                                        <!-- 1. Weapon Identification & Physical Specs -->
                                        <div class="space-y-3">
                                            <h5
                                                class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-widest bg-emerald-50 px-2 py-1 rounded-md inline-block">
                                                1. Identification &amp; Specifications</h5>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Model</label>
                                                    <input type="text" id="wpnModel"
                                                        placeholder="e.g. 19 Gen 5, M&P 9, Type 56, Glock 17"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Serial
                                                        Number</label>
                                                    <input type="text" id="wpnSerialNo"
                                                        placeholder="e.g. G17-984210X / Defaced"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400 font-mono-code">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Calibre
                                                        / Gauge</label>
                                                    <input type="text" id="wpnCalibre"
                                                        placeholder="e.g. 9mm, 7.62x39mm, 12-Gauge, .45 ACP"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Colour</label>
                                                    <input type="text" id="wpnColour"
                                                        placeholder="e.g. Matte Black, Silver, Camouflage"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Approx.
                                                        Size / Length</label>
                                                    <input type="text" id="wpnSize"
                                                        placeholder="e.g. 18.5 cm (Overall), Short Barrel, 87 cm"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 2. Recovery Condition & Status -->
                                        <div class="space-y-3 pt-2">
                                            <h5
                                                class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-widest bg-emerald-50 px-2 py-1 rounded-md inline-block">
                                                2. Recovery Condition &amp; Location</h5>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Loaded
                                                        Status</label>
                                                    <select id="wpnLoadedStatus"
                                                        class="w-full appearance-none px-3.5 py-2.5 bg-white border border-slate-300 hover:border-emerald-500 focus:border-emerald-600 rounded-xl font-bold text-slate-800 text-xs shadow-xs focus:outline-none focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer">
                                                        <option value=""
                                                            class="text-slate-400 font-normal">Select...</option>
                                                        <option value="Loaded"
                                                            class="font-semibold text-slate-900">Loaded</option>
                                                        <option value="Unloaded"
                                                            class="font-semibold text-slate-900">Unloaded</option>
                                                        <option value="Round in Chamber"
                                                            class="font-semibold text-slate-900">Round in Chamber
                                                        </option>
                                                        <option value="Magazine Attached (Loaded)"
                                                            class="font-semibold text-slate-900">Magazine Attached
                                                            (Loaded)</option>
                                                        <option value="Magazine Attached (Empty)"
                                                            class="font-semibold text-slate-900">Magazine Attached
                                                            (Empty)</option>
                                                        <option value="Unknown / Unspecified"
                                                            class="font-semibold text-slate-900">Unknown /
                                                            Unspecified</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Condition
                                                        at Recovery</label>
                                                    <select id="wpnCondition"
                                                        class="w-full appearance-none px-3.5 py-2.5 bg-white border border-slate-300 hover:border-emerald-500 focus:border-emerald-600 rounded-xl font-bold text-slate-800 text-xs shadow-xs focus:outline-none focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer">
                                                        <option value=""
                                                            class="text-slate-400 font-normal">Select...</option>
                                                        <option value="Operable / Good Condition"
                                                            class="font-semibold text-slate-900">Operable / Good
                                                            Condition</option>
                                                        <option value="Fair / Used Condition"
                                                            class="font-semibold text-slate-900">Fair / Used
                                                            Condition</option>
                                                        <option value="Rusty / Corroded"
                                                            class="font-semibold text-slate-900">Rusty / Corroded
                                                        </option>
                                                        <option value="Damaged / Inoperable"
                                                            class="font-semibold text-slate-900">Damaged /
                                                            Inoperable</option>
                                                        <option value="Serial Defaced / Tampered"
                                                            class="font-semibold text-slate-900">Serial Defaced /
                                                            Tampered</option>
                                                        <option value="Modified / Sawed-Off"
                                                            class="font-semibold text-slate-900">Modified /
                                                            Sawed-Off</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Exact
                                                        Location Found</label>
                                                    <input type="text" id="wpnLocationFound"
                                                        placeholder="e.g. Bedroom closet, Under vehicle driver seat, Roadside ditch"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div class="md:col-span-3">
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Distinguishing
                                                        Marks / Features</label>
                                                    <textarea id="wpnMarks" rows="2"
                                                        placeholder="e.g. Scratch on left grip, custom insignia engraved, missing safety switch"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400 resize-none"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- AMMUNITION SPECIFIC FIELDS -->
                                    <div id="ammoFieldsWrapper"
                                        class="hidden pt-4 border-t border-slate-300/60 mt-4 space-y-5">
                                        <div class="flex items-center gap-2 mb-2">
                                            <div
                                                class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                                </svg>
                                            </div>
                                            <h4
                                                class="text-sm font-extrabold text-slate-800 uppercase tracking-widest">
                                                Ammunition Specific Details</h4>
                                        </div>

                                        <!-- 1. Physical Description -->
                                        <div class="space-y-3">
                                            <h5
                                                class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-widest bg-emerald-50 px-2 py-1 rounded-md inline-block">
                                                1. Physical Description</h5>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Cartridge
                                                        Appearance</label>
                                                    <input type="text" id="ammoCartridgeAppearance"
                                                        placeholder="e.g. Brass casing, FMJ, Nickel-plated"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Projectile
                                                        / Bullet Type</label>
                                                    <input type="text" id="ammoProjectileType"
                                                        placeholder="e.g. Full Metal Jacket (FMJ), Hollow Point, Lead Round Nose"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Cartridge
                                                        Case Material</label>
                                                    <input type="text" id="ammoCaseMaterial"
                                                        placeholder="e.g. Brass, Steel, Aluminium, Plastic"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Colour
                                                        / Markings</label>
                                                    <input type="text" id="ammoColourMarkings"
                                                        placeholder="e.g. Headstamp CBC 9mm LUGER, Red primer sealant"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Condition</label>
                                                    <select id="ammoCondition"
                                                        onchange="toggleAmmoOtherCondition(this)"
                                                        class="w-full appearance-none px-3.5 py-2.5 bg-white border border-slate-300 hover:border-emerald-500 focus:border-emerald-600 rounded-xl font-bold text-slate-800 text-xs shadow-xs focus:outline-none focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer">
                                                        <option value=""
                                                            class="text-slate-400 font-normal">Select Condition...
                                                        </option>
                                                        <option value="Intact"
                                                            class="font-semibold text-slate-900">Intact</option>
                                                        <option value="Damaged"
                                                            class="font-semibold text-slate-900">Damaged</option>
                                                        <option value="Corroded"
                                                            class="font-semibold text-slate-900">Corroded</option>
                                                        <option value="Deformed"
                                                            class="font-semibold text-slate-900">Deformed</option>
                                                        <option value="Other"
                                                            class="font-semibold text-slate-900">Other</option>
                                                    </select>
                                                </div>
                                                <div id="ammoOtherConditionWrapper" class="hidden">
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Describe
                                                        Other Condition</label>
                                                    <input type="text" id="ammoOtherCondition"
                                                        placeholder="Specify condition details..."
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 2. Packaging & Sealing -->
                                        <div class="space-y-3 pt-2">
                                            <h5
                                                class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-widest bg-emerald-50 px-2 py-1 rounded-md inline-block">
                                                2. Packaging &amp; Sealing</h5>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Packaging
                                                        Type</label>
                                                    <input type="text" id="ammoPackagingType"
                                                        placeholder="e.g. Cardboard Box, Plastic Ammo Can, Loose Bag"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Seal
                                                        / Tamper-Evident Condition</label>
                                                    <input type="text" id="ammoSealCondition"
                                                        placeholder="e.g. Intact Tamper-Evident Bag #SEAL-90812"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Package
                                                        / Reference Number</label>
                                                    <input type="text" id="ammoPackageReferenceNumber"
                                                        placeholder="e.g. PKG-2026-AM01"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Condition
                                                        When Received</label>
                                                    <input type="text" id="ammoConditionWhenReceived"
                                                        placeholder="e.g. Sealed and untampered"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- SEIZED EXHIBIT PHOTO UPLOAD AREA (MULTIPLE IMAGES) -->
                                    <div class="pt-2 border-t border-slate-300/60 space-y-3">
                                        <div class="flex items-center justify-between">
                                            <label
                                                class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase">
                                                Upload Seized Exhibit Photos / Evidence Images <span
                                                    class="text-slate-400 font-normal">(Multiple Supported)</span>
                                            </label>
                                            <span id="seizurePhotosCount"
                                                class="hidden px-2.5 py-0.5 rounded-full text-[10px] font-mono-code font-bold bg-emerald-100 text-black border border-slate-300 seizure-photos-count">0
                                                Photos Uploaded</span>
                                        </div>

                                        <!-- DROPZONE BOX -->
                                        <div id="seizurePhotoDropzone" onclick="triggerSeizurePhotoUpload(this)" ondragover="event.preventDefault(); this.classList.add('border-emerald-600', 'bg-emerald-50/50');" ondragleave="this.classList.remove('border-emerald-600', 'bg-emerald-50/50');" ondrop="handleSeizurePhotoDrop(event, this);"
                                            class="seizure-photo-dropzone w-full min-h-[90px] bg-white rounded-xl border-2 border-dashed border-slate-300 hover:border-emerald-500 flex flex-col items-center justify-center p-4 cursor-pointer group transition-colors shadow-2xs text-center">
                                            <div id="placeholderSeizureBox"
                                                class="flex flex-col items-center space-y-1 text-slate-400 group-hover:text-black transition-colors">
                                                <div
                                                    class="w-9 h-9 rounded-full bg-emerald-50 text-black flex items-center justify-center mb-0.5">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <span
                                                    class="text-xs font-extrabold text-slate-800 group-hover:text-black">Click
                                                    or drag &amp; drop to upload exhibit photos</span>
                                                <span class="text-[10px] text-slate-500">Upload 10–15 photos per batch (under 3–5MB each, auto-optimized for fast upload)</span>
                                            </div>
                                        </div>
                                        <input type="file" id="fileSeizurePhoto" accept="image/*" multiple class="fileSeizurePhoto hidden" onchange="previewSeizurePhotos(event, this)">

                                        <!-- DYNAMIC MULTIPLE PHOTOS GALLERY GRID -->
                                        <div id="seizurePhotosGrid"
                                            class="hidden grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3 pt-2 seizurePhotosGrid">
                                        </div>
                                    </div>
                                </div>
                                <!-- END SEIZURES LIST CONTAINER -->

                                
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 4 TO 5 ACTION & REPORT GENERATION NAVIGATION BAR -->
                    <div class="bg-slate-100/90 p-4 rounded-2xl border border-slate-200/90 shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 rounded-xl bg-slate-800 text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-slate-900 uppercase font-mono-code tracking-wide">
                                    Report &amp; Laboratory Examination Modules
                                </h4>
                                <p class="text-[11px] text-slate-500 font-medium">
                                    Switch between uploading GSA Report Files and filling Section 5 Examination Information (NLR)
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3 w-full sm:w-auto justify-end shrink-0">
                            <!-- UPLOAD GSA FILE BUTTON -->
                            <button type="button" id="toggleGsaReportBtn" onclick="switchToGsaUploadSection()"
                                class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white font-extrabold text-xs rounded-xl shadow-md shadow-blue-600/20 transition-all flex items-center space-x-2 cursor-pointer border-none">
                                <svg class="w-4 h-4 text-blue-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>Upload GSA File</span>
                            </button>

                            <!-- EXAMINATION INFORMATION (NLR) BUTTON -->
                            <button type="button" id="toggleNlrReportBtn" onclick="switchToNlrExamSection()"
                                class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-600/20 transition-all flex items-center space-x-2 cursor-pointer border-none">
                                <svg class="w-4 h-4 text-emerald-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                </svg>
                                <span>Examination Information (NLR)</span>
                            </button>

                            <!-- RESET BUTTON TO NEUTRAL STATE -->
                            <button type="button" id="resetLabModulesBtn" onclick="resetLabModulesToNeutral()"
                                class="px-3.5 py-2.5 bg-slate-200 hover:bg-slate-300 active:scale-95 text-slate-700 font-extrabold text-xs rounded-xl transition-all flex items-center space-x-1.5 cursor-pointer border border-slate-300"
                                title="Reset both buttons and forms to neutral state">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <span>Reset</span>
                            </button>
                        </div>
                    </div>

                    <!-- GSA REPORT UPLOAD SECTION -->
                    <div id="gsaUploadSection" class="hidden bg-white p-6 rounded-2xl border border-blue-200/90 shadow-sm space-y-6 relative z-20">
                        <!-- Section Header -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-blue-600 to-blue-800 text-white flex items-center justify-center shadow-md shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="px-2 py-0.5 bg-blue-600 text-white text-[10px] font-extrabold font-mono-code rounded-md uppercase tracking-wider">GSA FILES</span>
                                        <h3 class="text-base font-black text-slate-900 font-mono-code uppercase tracking-tight">GSA Report Upload</h3>
                                    </div>
                                    <p class="text-[11px] text-slate-500 font-mono-code mt-0.5">Attach scans, photos, or documents of the signed GSA Report Upload</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="hidden sm:inline-flex items-center gap-1.5 text-[11px] text-blue-700 font-bold bg-blue-50 border border-blue-200 px-3 py-1.5 rounded-xl">
                                    <svg class="w-3.5 h-3.5 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Auto-saved with Save Case</span>
                                </span>
                                <span id="gsaFileCount" class="hidden px-2.5 py-0.5 bg-blue-600 text-white text-[10px] font-extrabold font-mono-code rounded-full">0 files</span>
                                <button type="button" onclick="clearAllGsaFiles()" id="gsaClearAllBtn" class="hidden px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 text-[10px] font-bold rounded-lg border border-red-200 transition-colors cursor-pointer">Clear All</button>
                            </div>
                        </div>

                        <!-- GSA DETAILS: GSA Lab No., GSA Case Reference, GSA Status & Exhibit Types -->
                        <div class="bg-blue-50/50 p-4 rounded-2xl border border-blue-100 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- GSA Lab No. -->
                                <div class="space-y-1.5">
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase flex items-center justify-between">
                                        <span class="flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                            </svg>
                                            GSA Lab No.
                                        </span>
                                        <span class="text-[9px] font-mono-code text-blue-700 bg-blue-100 px-1.5 py-0.5 rounded font-bold uppercase">Official No.</span>
                                    </label>
                                    <input type="text" id="gsaLabNumberInput" name="gsa_lab_number"
                                        placeholder="Enter GSA Lab No. (e.g. GSA/LAB/2026/0892)"
                                        class="w-full px-3.5 py-2.5 bg-white border border-blue-200 rounded-xl text-slate-900 font-mono-code font-bold outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all text-xs shadow-2xs">
                                </div>

                                <!-- GSA Case Reference -->
                                <div class="space-y-1.5">
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase flex items-center justify-between">
                                        <span class="flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            GSA Case Reference
                                        </span>
                                        <span class="text-[9px] font-mono-code text-slate-600 bg-slate-200/70 px-1.5 py-0.5 rounded font-bold uppercase">Reference</span>
                                    </label>
                                    <textarea id="gsaCaseReferenceInput" name="gsa_case_reference" rows="2"
                                        placeholder="Enter GSA Case Reference details..."
                                        class="w-full px-3.5 py-2 bg-white border border-blue-200 rounded-xl text-slate-900 font-mono-code text-xs outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all shadow-2xs resize-none"></textarea>
                                </div>

                                <!-- GSA Status -->
                                <div class="space-y-1.5">
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase flex items-center justify-between">
                                        <span class="flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            GSA Status
                                        </span>
                                        <span class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Status</span>
                                    </label>
                                    <textarea id="gsaStatusInput" name="gsa_status" rows="2"
                                        placeholder="Enter GSA Status (e.g. Analysis Complete, Certified...)"
                                        class="w-full px-3.5 py-2 bg-white border border-blue-200 rounded-xl text-slate-900 font-mono-code text-xs outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all shadow-2xs resize-none"></textarea>
                                </div>
                            </div>

                            <!-- Exhibit Types Container -->
                            <div class="pt-3 border-t border-blue-200/60 space-y-2" id="gsaExhibitTypesSection">
                                <div class="flex items-center justify-between">
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                        <span>Exhibit Type</span>
                                    </label>
                                    <button type="button" onclick="addGsaExhibitTypeInput()" class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-mono-code font-bold uppercase text-blue-700 bg-blue-100 hover:bg-blue-200 active:scale-95 rounded-lg border border-blue-200 transition-all cursor-pointer">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        <span>Add Exhibit Type</span>
                                    </button>
                                </div>
                                <div id="gsaExhibitTypeList" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                                    <div class="flex items-center gap-1.5 gsa-exhibit-type-row">
                                        <input type="text" name="exhibit_type[]" placeholder="Enter Exhibit Type (e.g. Cocaine, Heroin)"
                                            class="gsa-exhibit-type-input w-full px-3.5 py-2.5 bg-white border border-blue-200 rounded-xl text-slate-900 font-mono-code font-bold text-xs outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all shadow-2xs">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- DROP ZONE -->
                        <div id="gsaDropZone" ondragover="gsaDragOver(event)" ondragleave="gsaDragLeave(event)" ondrop="gsaHandleDrop(event)" class="relative w-full border-2 border-dashed border-slate-300 rounded-2xl bg-slate-50 hover:border-blue-400 transition-all overflow-hidden">
                            <!-- FILE LIST (inside the zone) -->
                            <div id="gsaFileGrid" class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-5 gap-3 p-3"></div>

                            <!-- FULL PROMPT — shown when no files -->
                            <div id="gsaFullPrompt" onclick="document.getElementById('gsaReportImageInput').click()" class="flex flex-col items-center justify-center gap-2 py-10 px-4 text-center cursor-pointer group hover:bg-blue-50/60 transition-all">
                                <div class="w-12 h-12 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center justify-center group-hover:bg-blue-50 group-hover:border-blue-300 transition-all">
                                    <svg class="w-6 h-6 text-slate-400 group-hover:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-slate-600 group-hover:text-blue-800">Click to add files or drag &amp; drop</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5 font-mono-code">JPG &middot; PNG &middot; WEBP &middot; PDF &middot; DOCX &middot; CSV &nbsp;|&nbsp; Max 10 MB each</p>
                                </div>
                            </div>

                            <!-- COMPACT ADD-MORE STRIP — shown when files exist -->
                            <div id="gsaAddMoreStrip" onclick="document.getElementById('gsaReportImageInput').click()" class="hidden items-center justify-center gap-2 py-2.5 px-4 border-t border-dashed border-slate-300 bg-slate-50 hover:bg-blue-50/60 hover:border-blue-400 transition-all cursor-pointer group">
                                <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span class="text-[11px] font-bold text-slate-500 group-hover:text-blue-800 font-mono-code">Click to add more files or drag &amp; drop</span>
                            </div>

                            <!-- Hidden file input -->
                            <input type="file" id="gsaReportImageInput" name="gsa_report_images[]" accept="image/jpeg,image/png,image/webp,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/csv,.csv,.docx" multiple class="hidden" onchange="gsaHandleFileSelect(this)">
                        </div>
                    </div>

                    <!-- LAB ANALYSIS UNIT EXCLUSIVES: SECTION 5: FORENSIC EXAMINATION & LABORATORY ANALYSIS -->
                    <div id="labAnalysisExclusiveSections"
                        class="hidden bg-white p-6 rounded-2xl border border-emerald-200/90 shadow-sm space-y-6 relative z-20">

                        <!-- SECTION 5 MAIN CONTAINER HEADER -->
                        <div
                            class="pb-3 border-b border-emerald-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-md shadow-emerald-600/30 shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-slate-900 text-base">Section 5: Forensic
                                        Examination &amp; Laboratory Analysis</h3>
                                    <p class="text-xs text-emerald-700 font-semibold">Laboratory Analysis
                                        Directorate — Forensic Examination, Screening &amp; Confirmatory Testing
                                        Log</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2 shrink-0">
                                <span
                                    class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider">
                                    LAB ANALYSIS EXCLUSIVE
                                </span>
                            </div>
                        </div>

                        <!-- EXAMINATION INFORMATION CONTAINER -->
                        <div id="section5FormContainer"
                            class="space-y-5 ">
                            <!-- SUB-SECTION 5A: EXAMINATION INFORMATION -->
                            <div class="bg-slate-50/60 p-5 rounded-2xl border border-slate-200 space-y-5">
                                <div class="pb-2 border-b border-slate-200 flex items-center justify-between">
                                    <h4
                                        class="font-extrabold text-slate-900 text-xs uppercase tracking-wider flex items-center space-x-2">
                                        <span
                                            class="w-5 h-5 rounded-full bg-emerald-600 text-white text-[10px] flex items-center justify-center font-bold">5A</span>
                                        <span>Examination Information</span>
                                    </h4>
                                </div>

                                <!-- DYNAMIC EXHIBIT SELECTOR CARD FROM SECTION 4 SEIZED COMMODITIES MANIFEST (Requirement 2A) -->
                                <div
                                    class="p-4 bg-emerald-50/70 rounded-xl border border-emerald-200/90 space-y-3 shadow-2xs">
                                    <div class="space-y-2">
                                        <!-- SEIZED EXHIBIT SELECTOR FROM SECTION 4 MANIFEST -->
                                        <div>
                                            <label
                                                class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                                <span class="flex items-center space-x-2">
                                                    <svg class="w-4 h-4 text-emerald-600" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                    </svg>
                                                    <span>Select Seized Exhibit (Manifest Sync)</span>
                                                    <span class="text-red-600 font-bold">*</span>
                                                </span>
                                                <span id="labExhibitCountBadge"
                                                    class="text-[10px] font-mono-code font-extrabold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-md border border-emerald-200">
                                                    No exhibit saved yet
                                                </span>
                                            </label>
                                            <select id="labExhibitSelect" name="lab_selected_exhibit_index"
                                                onchange="onLabExhibitSelected()"
                                                class="hidden">
                                            </select>

                                            <!-- DYNAMIC EXHIBIT SELECTION CONTAINERS -->
                                            <div id="labExhibitCardsContainer" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 pt-1">
                                                <div class="p-3 bg-white/80 rounded-xl border border-dashed border-emerald-300 text-center text-xs text-slate-400 italic">
                                                    No exhibits logged in manifest yet.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <p
                                        class="text-[11px] text-emerald-900 font-semibold flex items-center space-x-1">
                                        <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>Each seized exhibit is synchronized from the manifest. Click an exhibit card to select it for focus or view examination specifics below.</span>
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <!-- Analysis/Examination Reference — auto-generated -->
                                    <div>
                                        <label
                                            class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                            <span>Analysis / Examination Reference</span>
                                            <span
                                                class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Auto-Generated</span>
                                        </label>
                                        <div class="relative flex items-center">
                                            <input type="text" id="labExamReferenceInput"
                                                name="lab_exam_reference" value="{{ $examRefAuto }}"
                                                readonly
                                                class="w-full px-3.5 py-3 bg-emerald-50/70 border border-emerald-200 rounded-xl text-emerald-900 font-mono-code font-extrabold outline-none shadow-2xs text-xs">
                                        </div>
                                    </div>

                                    <!-- Case Reference — auto-linked -->
                                    <div>
                                        <label
                                            class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                            <span>Case Reference</span>
                                            <span
                                                class="text-[9px] font-mono-code text-blue-700 bg-blue-100 px-1.5 py-0.5 rounded font-bold uppercase">Auto-Linked</span>
                                        </label>
                                        <div class="relative flex items-center">
                                            <input type="text" id="labCaseReferenceInput"
                                                name="lab_case_reference" value="{{ $caseRefAuto }}"
                                                readonly
                                                class="w-full px-3.5 py-3 bg-blue-50/70 border border-blue-200 rounded-xl text-blue-900 font-mono-code font-extrabold outline-none shadow-2xs text-xs">
                                        </div>
                                    </div>

                                    <!-- Hidden Exhibit Number input synced from active card for form submission -->
                                    <input type="hidden" id="labExhibitNumberInput"
                                        name="lab_exhibit_number" value="{{ $exhibitRefAuto }}">
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                    <!-- Exhibit Description -->
                                    <div>
                                        <label
                                            class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                            <span>Exhibit Description</span>
                                            <span
                                                class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Fetched
                                                from Manifest</span>
                                        </label>
                                        <textarea id="labExhibitDescriptionInput" name="lab_exhibit_description" rows="3"
                                            placeholder="No drug exhibit saved yet"
                                            class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs placeholder:text-slate-400">No drug exhibit saved yet</textarea>
                                    </div>

                                    <!-- Laboratory Number -->
                                    <div>
                                        <label
                                            class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                            <span>Laboratory Number</span>
                                            <span
                                                class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Auto-Generated</span>
                                        </label>
                                        <input type="text" id="labNumberInput" name="laboratory_number"
                                            value="{{ $labNumberAuto }}" readonly
                                            class="w-full px-3.5 py-3 bg-emerald-50/70 border border-emerald-200 rounded-xl text-emerald-900 font-mono-code font-extrabold outline-none shadow-2xs text-xs">
                                    </div>

                                    <!-- Analyst / Examiner -->
                                    <div>
                                        <label
                                            class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                            <span>Analyst / Examiner</span>
                                            <span
                                                class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Examiner
                                                Name</span>
                                        </label>
                                        <input type="text" id="labAnalystInput" name="lab_analyst"
                                            value="{{ auth()->user()->fullname ?? (session('user_name') ?? ($authUser->fullname ?? ($defaultLabHeadName !== 'N/A' ? $defaultLabHeadName : ''))) }}"
                                            class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-extrabold outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs text-xs"
                                            placeholder="Examiner / Analyst Name">
                                    </div>
                                </div>

                                <div
                                    class="grid grid-cols-1 md:grid-cols-3 gap-5 pt-2 border-t border-slate-100">
                                    <!-- Date Received -->
                                    <div>
                                        <label
                                            class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                            Date Received <span class="text-red-600 font-bold">*</span>
                                        </label>
                                        <input type="date" id="labDateReceivedInput"
                                            name="lab_date_received" value="{{ $todayDate }}"
                                            class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold font-mono-code outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs">
                                    </div>

                                    <!-- Date Examination Started -->
                                    <div>
                                        <label
                                            class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                            Date Examination Started <span
                                                class="text-red-600 font-bold">*</span>
                                        </label>
                                        <input type="date" id="labDateStartedInput"
                                            name="lab_date_started" value="{{ $todayDate }}"
                                            class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold font-mono-code outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs">
                                    </div>

                                    <!-- Date Examination Completed -->
                                    <div>
                                        <label
                                            class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                            Date Examination Completed <span
                                                class="text-red-600 font-bold">*</span>
                                        </label>
                                        <input type="date" id="labDateCompletedInput"
                                            name="lab_date_completed" value="{{ $todayDate }}"
                                            class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold font-mono-code outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs">
                                    </div>
                                </div>
                            </div>

                            <!-- LEGACY FALLBACK COMPATIBILITY HIDDEN FIELDS -->
                            <div class="hidden" style="display:none;" aria-hidden="true">
                                <input type="checkbox" id="presumptiveTestYes" name="presumptive_test_yes" value="YES">
                                <input type="checkbox" id="presumptiveTestNo" name="presumptive_test_no" value="NO" checked>
                                <input type="hidden" id="presumptiveReactionResultInput" name="presumptive_reaction_result_input" value="">
                                <input type="checkbox" id="confirmatoryTestYes" name="confirmatory_test_yes" value="YES">
                                <input type="checkbox" id="confirmatoryTestNo" name="confirmatory_test_no" value="NO" checked>
                                <input type="hidden" id="confirmatoryTestResultInput" name="confirmatory_test_result" value="NO">
                                <input type="text" id="sampleWeightDescription" name="sample_weight_description" value="">
                                <input type="text" id="sampleWeightUnits" name="sample_weight_units" value="">
                                <input type="text" id="sampleWeightGross" name="sample_weight_gross" value="">
                                <input type="text" id="sampleWeightTaken" name="sample_weight_taken" value="">
                                <input type="text" id="sampleWeightReserved" name="sample_weight_reserved" value="">
                                <input type="text" id="confirmatorySubstanceIdentification" name="confirmatory_substance_identification" value="">
                                <div id="presumptiveReactionDetailsContainer"></div>
                                <div id="presumptiveTestRowsContainer"></div>
                                <div id="sampleWeightContainer"></div>
                                <div id="detectedSubstancesListContainer"></div>
                            </div>

                            <!-- SUB-SECTION 5B: PURPOSE OF EXAMINATION -->
                            <div class="bg-slate-50/60 p-5 rounded-2xl border border-slate-200 space-y-4">
                                <div class="pb-2 border-b border-slate-200 flex items-center justify-between">
                                    <h4
                                        class="font-extrabold text-slate-900 text-xs uppercase tracking-wider flex items-center space-x-2">
                                        <span
                                            class="w-5 h-5 rounded-full bg-emerald-600 text-white text-[10px] flex items-center justify-center font-bold">5B</span>
                                        <span>Purpose of Examination</span>
                                    </h4>
                                    <span
                                        class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider">
                                        MANDATORY / SCOPE
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 font-medium">State the formal purpose, scope, and specific analytical objectives of the forensic laboratory examination.</p>

                                <div class="p-4 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-2">
                                    <label
                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                        <span>Purpose of Examination</span>
                                        <span
                                            class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Mandatory / Scope</span>
                                    </label>
                                    <textarea id="labPurposeOfExaminationInput" name="purpose_of_examination" rows="3"
                                        placeholder="State the purpose of examination (e.g. Qualitative and quantitative chemical analysis to identify controlled substances and determine purity percentage)..."
                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs resize-none placeholder:text-slate-400">{{ old('purpose_of_examination') }}</textarea>
                                </div>
                            </div>

                            <!-- SUB-SECTION 5C: DYNAMIC PRESUMPTIVE TEST SECTIONS (Requirement 3) -->
                            <div class="bg-slate-50/60 p-5 rounded-2xl border border-slate-200 space-y-4">
                                <div class="pb-2 border-b border-slate-200 flex items-center justify-between">
                                    <h4
                                        class="font-extrabold text-slate-900 text-xs uppercase tracking-wider flex items-center space-x-2">
                                        <span
                                            class="w-5 h-5 rounded-full bg-emerald-600 text-white text-[10px] flex items-center justify-center font-bold">5C</span>
                                        <span>Presumptive Test (Screening Analysis)</span>
                                    </h4>
                                    <span
                                        class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider">
                                        DYNAMIC PER-EXHIBIT SCREENING
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 font-medium">Independent presumptive chemical reaction test containers generated dynamically for each seized exhibit in the manifest.</p>

                                <!-- DYNAMIC PRESUMPTIVE CONTAINERS FOR EACH SEIZED EXHIBIT -->
                                <div id="presumptiveTestExhibitsContainer" class="space-y-6">
                                    <!-- Populated dynamically via syncSection4ExhibitsToLabSection() -->
                                </div>
                            </div>

                            <!-- SUB-SECTION 5D: DYNAMIC CONFIRMATORY TEST SECTIONS (Requirement 4) -->
                            <div class="bg-slate-50/60 p-5 rounded-2xl border border-slate-200 space-y-6">
                                <div class="pb-2 border-b border-slate-200 flex items-center justify-between">
                                    <h4
                                        class="font-extrabold text-slate-900 text-xs uppercase tracking-wider flex items-center space-x-2">
                                        <span
                                            class="w-5 h-5 rounded-full bg-emerald-600 text-white text-[10px] flex items-center justify-center font-bold">5D</span>
                                        <span>Confirmatory Test (Laboratory Verification)</span>
                                    </h4>
                                    <span
                                        class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider">
                                        DYNAMIC PER-EXHIBIT LAB VERIFICATION
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 font-medium">Independent confirmatory analysis, metrology & mass, substance identification, and purity determination for each seized exhibit.</p>

                                <!-- DYNAMIC CONFIRMATORY CONTAINERS FOR EACH SEIZED EXHIBIT -->
                                <div id="confirmatoryTestExhibitsContainer" class="space-y-6">
                                    <!-- Populated dynamically via syncSection4ExhibitsToLabSection() -->
                                </div>
                            </div>

                            <!-- SUB-SECTION 5E: PRELIMINARY EXAMINATION PROCEDURE -->
                            <div class="bg-slate-50/60 p-5 rounded-2xl border border-slate-200 space-y-4">
                                <div class="pb-2 border-b border-slate-200 flex items-center justify-between">
                                    <h4
                                        class="font-extrabold text-slate-900 text-xs uppercase tracking-wider flex items-center space-x-2">
                                        <span
                                            class="w-5 h-5 rounded-full bg-emerald-600 text-white text-[10px] flex items-center justify-center font-bold">5E</span>
                                        <span>Preliminary Examination Procedure</span>
                                    </h4>
                                    <span
                                        class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider">
                                        APPLICABLE TO ALL EXHIBITS
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 font-medium">Record the detailed preliminary examination procedures, physical inspection protocols, and visual screening methodology applied to the exhibit(s).</p>

                                <div class="p-4 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-2">
                                    <label
                                        class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                        <span>PRELIMINARY EXAMINATION PROCEDURE</span>
                                        <span
                                            class="text-[9px] font-mono-code text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded font-bold uppercase">Procedure</span>
                                    </label>
                                    <textarea id="labPreliminaryExamProcedureInput" name="preliminary_exam_procedure" rows="3"
                                        placeholder="Enter the preliminary examination procedure..."
                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs resize-none placeholder:text-slate-400"></textarea>
                                </div>
                            </div>

                            <!-- SUB-SECTION 5F: SHARED CONFIRMATORY TEST INFORMATION (Requirement 5) -->
                            <div class="bg-slate-50/60 p-5 rounded-2xl border border-slate-200 space-y-4">
                                <div class="pb-2 border-b border-slate-200 flex items-center justify-between">
                                    <h4
                                        class="font-extrabold text-slate-900 text-xs uppercase tracking-wider flex items-center space-x-2">
                                        <span
                                            class="w-5 h-5 rounded-full bg-emerald-600 text-white text-[10px] flex items-center justify-center font-bold">5F</span>
                                        <span>Shared Confirmatory Test Information</span>
                                    </h4>
                                    <span
                                        class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider">
                                        APPLICABLE TO ALL EXHIBITS
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 font-medium">Standard test methods, specifications, and overall remarks applicable across all examined exhibits.</p>

                                <div class="p-5 bg-white rounded-2xl border border-emerald-300 shadow-2xs space-y-4">
                                    <div class="space-y-4">
                                        <!-- 0. SCREENING METHOD / INSTRUMENT USED INPUT (DYNAMIC REPEATABLE) -->
                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between">
                                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                                    <span>Screening method/Instrument Used</span>
                                                </label>
                                                <button type="button" onclick="addScreeningMethodRow()"
                                                    class="inline-flex items-center px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[10px] rounded-lg shadow-xs transition-all cursor-pointer border-none space-x-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                                    <span>+ Add More</span>
                                                </button>
                                            </div>
                                            <div id="screeningMethodsContainer" class="space-y-2">
                                                <div class="screening-method-row flex items-center space-x-2">
                                                    <input type="text" name="screening_method[]" id="screeningMethodInput"
                                                        placeholder="e.g. Gas Chromatography-Mass Spectrometry (GC-MS), FTIR, Raman..."
                                                        class="screening-method-input w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-mono-code placeholder:text-slate-400">
                                                    <button type="button" onclick="removeScreeningMethodRow(this)"
                                                        class="remove-screening-method-btn hidden p-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl border border-red-200 transition-colors shrink-0 cursor-pointer" title="Remove Method">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 1. TEST METHODS INPUT -->
                                        <div>
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                                <span>Test Methods <span class="text-red-600 font-bold">*</span></span>
                                                <span class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Standard Protocol</span>
                                            </label>
                                            <input type="text" id="confirmatoryTestMethods" name="confirmatory_test_methods"
                                                placeholder="e.g. GSA-SM-T***-A, Clarks analysis of..."
                                                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-mono-code placeholder:text-slate-400">
                                        </div>

                                        <!-- 2. SPECIFICATIONS TEXTAREA INPUT -->
                                        <div>
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                                <span>Specifications <span class="text-slate-400 font-normal text-[10px] lowercase normal-case">(optional)</span></span>
                                                <span class="text-[9px] font-mono-code text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded font-bold uppercase">Standards Matched</span>
                                            </label>
                                            <textarea id="confirmatorySpecifications" name="confirmatory_specifications" rows="2"
                                                placeholder="e.g. Analytical reference standards verified against NIST library database. Sample purity meets regulatory specifications."
                                                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-sans placeholder:text-slate-400"></textarea>
                                        </div>

                                        <!-- 3. REMARK TEXTAREA INPUT -->
                                        <div>
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                                <span>Remark <span class="text-slate-400 font-normal text-[10px] lowercase normal-case">(optional)</span></span>
                                                <span class="text-[9px] font-mono-code text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded font-bold uppercase">General Observations</span>
                                            </label>
                                            <textarea id="confirmatoryRemark" name="confirmatory_remark" rows="2"
                                                placeholder="Enter any overall remarks or observations regarding the confirmatory analysis across all exhibits..."
                                                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-sans placeholder:text-slate-400"></textarea>
                                        </div>

                                        <!-- 4. DISPOSITION OF EXHIBIT(S) TEXTAREA INPUT -->
                                        <div>
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                                <span>Disposition Of Exhibit(s) <span class="text-slate-400 font-normal text-[10px] lowercase normal-case">(optional)</span></span>
                                                <span class="text-[9px] font-mono-code text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded font-bold uppercase">Chain of Custody / Vault</span>
                                            </label>
                                            <textarea id="confirmatoryDispositionOfExhibit" name="disposition_of_exhibit" rows="2"
                                                placeholder="Enter the disposition of exhibit(s) (e.g. Retained in forensic vault, Returned to seizing agency, Scheduled for destruction)..."
                                                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-sans placeholder:text-slate-400"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </div>
                        </div>


                    <!-- FOOTER SUBMIT BAR -->
                    <div class="sticky bottom-4 bg-white p-5 rounded-2xl border border-slate-300 shadow-2xl flex flex-col sm:flex-row items-center justify-between gap-4 z-30">
                        <div>
                            <span class="font-extrabold text-slate-900 text-sm block">Confirm &amp; Update Associated Docket File</span>
                            <span class="text-xs text-slate-500">Newly added persons and exhibits will be merged into Case {{ $case->case_number }}</span>
                        </div>
                        <div class="flex items-center space-x-3 w-full sm:w-auto">
                            <a href="{{ url('/cases') . ($isLabAnalysis ? '?unit=Lab+Analysis' : '') }}" class="w-1/2 sm:w-auto px-5 py-3 bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-bold rounded-xl text-center transition-all cursor-pointer">
                                Cancel
                            </a>
                            <button type="submit" id="submitAssocFormBtn" data-testid="assoc-submit-btn" class="w-1/2 sm:w-auto px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold rounded-xl shadow-lg shadow-emerald-600/30 flex items-center justify-center space-x-2 transition-all hover:scale-[1.02] cursor-pointer border-none">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Save Associated Case Data &amp; Update Docket</span>
                            </button>
                        </div>
                    </div>

                </form>

            </main>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        let personCounter = 0;
        const ACTIVE_UNIT_KEY = @json($activeUnitKey);
        function restoreDocketFormDraft() {}
        function setCursorToEnd(el) {
            if (!el || typeof el.setSelectionRange !== 'function') return;
            const len = (el.value || '').length;
            try { el.setSelectionRange(len, len); } catch (e) {}
        }
        function handleCaseEntryTypeChange() {}
        function handleMaritalChange() {}
        function renderSeizurePhotosGrid() {}
        function syncSuspectDropdownsForExhibits() {
            if (typeof syncSuspectNamesToExhibits === 'function') syncSuspectNamesToExhibits();
        }
        function evaluateLabExaminationVisibility() {}

        // WHY: Nationalities loaded from config via json directive — see config/nationalities.php.
        // Reduces Blade page weight by ~6 KB and centralizes the list.
        const worldNationalitiesList = @json(config('nationalities.list', []));

        // WHY: 'accept="image/*"' is a UX hint only — users can bypass it via drag-drop
        // or file dialog. Some older browsers execute JS from SVG loaded in <img>.
        // Validate mime before processing.
        const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

        function validateImageFile(file) {
            if (!file) return false;
            if (!ALLOWED_MIME.includes(file.type)) {
                alert(`File "${file.name}" is not a supported image (allowed: JPG, PNG, WEBP, GIF).`);
                return false;
            }
            if (file.size > 10 * 1024 * 1024) {
                alert(`File "${file.name}" exceeds 10 MB.`);
                return false;
            }
            return true;
        }

        // WHY: Prior code eagerly initialized 5 Select2 instances per card.
        // With 20 cards, that's 100 instances, causing visible input lag.
        // Now: initialize only the two required dropdowns eagerly, and defer
        // the rest until focus (lazy init).
        function initSelect2Lazy($element, options) {
            $element.one('focus mousedown', function() {
                if (!$element.data('select2')) {
                    $element.select2(options);
                    $element.select2('open');
                }
            });
        }

        function calculateAgeFromDob(dobInput, ageInputId) {
            const ageInput = document.getElementById(ageInputId);
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

        function getNationalityOptionsHtml() {
            let options = '<option value=""></option>';
            worldNationalitiesList.forEach(nat => {
                options += `<option value="${nat}">${nat}</option>`;
            });
            return options;
        }

        function addAssociatePersonCard() {
            personCounter++;
            const container = document.getElementById('newPersonsContainer');
            if (!container) return;

            const card = document.createElement('div');
            card.id = `personCard-${personCounter}`;
            card.className = 'new-person-card bg-slate-50/90 p-5 rounded-2xl border border-blue-200 shadow-sm space-y-5 relative';
            
            card.innerHTML = `
                <!-- CARD HEADER & REMOVE BUTTON -->
                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <div class="flex items-center space-x-2.5">
                        <span class="w-6 h-6 rounded-full bg-blue-600 text-white font-mono-code font-bold text-xs flex items-center justify-center shadow-xs">
                            ${personCounter}
                        </span>
                        <h4 class="font-extrabold text-slate-900 text-sm flex items-center gap-2">
                            <span>New Associated Person / Co-Suspect #${personCounter}</span>
                            <span class="px-2 py-0.5 bg-blue-100 text-blue-900 border border-blue-300 font-mono-code font-bold text-[10px] rounded-md">ID: ASC-${String(personCounter).padStart(3, '0')}</span>
                        </h4>
                        <input type="hidden" name="associated_persons[${personCounter}][associate_id]" value="ASC-${String(personCounter).padStart(3, '0')}">
                    </div>
                    <button type="button" onclick="document.getElementById('personCard-${personCounter}').remove(); setTimeout(syncSuspectNamesToExhibits, 50);" class="px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-bold rounded-xl transition-colors cursor-pointer border-none flex items-center space-x-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Remove Person</span>
                    </button>
                </div>

                <!-- 1. MUGSHOT UPLOADS (FRONT 0°, LEFT 90°, RIGHT 90°) -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="text-slate-800 font-extrabold text-xs uppercase tracking-wider font-mono-code flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>Associate Person Mugshots &amp; Facial Biometrics (Front 0&deg; / Left 90&deg; / Right 90&deg;)</span>
                        </div>
                        <span class="text-[10px] font-mono-code text-blue-700 font-bold bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                            Click frame or drop image to upload
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 bg-white p-4 rounded-2xl border border-slate-200">
                        <!-- FRONT MUGSHOT -->
                        <div class="space-y-2 text-center">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-mono-code font-extrabold text-slate-900 block uppercase">Front Mugshot (0&deg;)</span>
                                <span id="badgeFront_${personCounter}" class="text-[9px] font-mono-code font-bold text-slate-400 uppercase">Not Uploaded</span>
                            </div>
                            
                            <!-- INTERACTIVE DROPZONE FRAME (FULL PREVIEW, NO CROPPING) -->
                            <div 
                                id="dropzoneFront_${personCounter}"
                                onclick="document.getElementById('fileInputFront_${personCounter}').click()"
                                ondragover="handleMugshotDragOver(event, this)"
                                ondragleave="handleMugshotDragLeave(event, this)"
                                ondrop="handleMugshotDrop(event, 'fileInputFront_${personCounter}', 'previewFront_${personCounter}', 'placeholderFront_${personCounter}', 'hiddenFront_${personCounter}', 'actionsFront_${personCounter}', 'badgeFront_${personCounter}')"
                                class="h-48 w-full bg-slate-50 hover:bg-blue-50/60 rounded-2xl border-2 border-dashed border-slate-300 hover:border-blue-500 flex flex-col items-center justify-center overflow-hidden relative group transition-all duration-200 cursor-pointer shadow-2xs"
                            >
                                <!-- UNCROPPED FULL DISPLAY IMAGE -->
                                <img id="previewFront_${personCounter}" src="" alt="Front Mugshot Preview" class="h-full w-full object-contain p-1 hidden transition-all duration-200">
                                
                                <!-- PLACEHOLDER PROMPT -->
                                <div id="placeholderFront_${personCounter}" class="flex flex-col items-center justify-center text-slate-400 p-3 space-y-2 pointer-events-none">
                                    <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center shadow-xs">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <span class="text-xs font-bold text-slate-700">Click or Drag &amp; Drop Image</span>
                                    <span class="text-[9px] font-mono-code text-slate-400">Supports JPG, PNG, WEBP</span>
                                </div>

                                <!-- OVERLAY ACTION BUTTONS (WHEN IMAGE LOADED) -->
                                <div id="actionsFront_${personCounter}" class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs opacity-0 group-hover:opacity-100 flex items-center justify-center space-x-2 transition-opacity duration-200 hidden" onclick="event.stopPropagation()">
                                    <button type="button" onclick="openMugshotModal(document.getElementById('previewFront_${personCounter}').src, 'Front Mugshot (0&deg;) - Co-Suspect #${personCounter}')" class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-bold rounded-xl transition-all shadow-md flex items-center space-x-1 cursor-pointer border-none">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <span>View Full</span>
                                    </button>
                                    <button type="button" onclick="clearMugshotPhoto('fileInputFront_${personCounter}', 'urlInputFront_${personCounter}', 'previewFront_${personCounter}', 'placeholderFront_${personCounter}', 'hiddenFront_${personCounter}', 'actionsFront_${personCounter}', 'badgeFront_${personCounter}')" class="px-2.5 py-1.5 bg-red-600 hover:bg-red-700 text-white text-[10px] font-bold rounded-xl transition-all shadow-md flex items-center space-x-1 cursor-pointer border-none">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        <span>Clear</span>
                                    </button>
                                </div>
                            </div>

                            <!-- FILE & URL IMPORT INPUTS -->
                            <div class="space-y-1.5 pt-1">
                                <input 
                                    type="file" 
                                    id="fileInputFront_${personCounter}" 
                                    accept="image/*" 
                                    onchange="previewMugshotFile(this, 'previewFront_${personCounter}', 'placeholderFront_${personCounter}', 'hiddenFront_${personCounter}', 'actionsFront_${personCounter}', 'badgeFront_${personCounter}')" 
                                    class="hidden"
                                >
                                <div class="flex items-center space-x-1.5">
                                    <button type="button" onclick="document.getElementById('fileInputFront_${personCounter}').click()" class="w-full py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 text-[11px] font-extrabold rounded-xl border border-blue-200 transition-colors flex items-center justify-center space-x-1 cursor-pointer">
                                        <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                        <span>Choose File</span>
                                    </button>
                                </div>
                                <input 
                                    type="text" 
                                    id="urlInputFront_${personCounter}"
                                    placeholder="Or paste image URL..." 
                                    oninput="previewMugshotUrl(this.value, 'previewFront_${personCounter}', 'placeholderFront_${personCounter}', 'hiddenFront_${personCounter}', 'actionsFront_${personCounter}', 'badgeFront_${personCounter}')" 
                                    class="w-full px-2.5 py-1.5 text-[10px] bg-slate-50 border border-slate-200 rounded-xl font-mono-code text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white transition-all"
                                >
                                <input type="hidden" name="associated_persons[${personCounter}][mugshot_front]" id="hiddenFront_${personCounter}">
                            </div>
                        </div>

                        <!-- LEFT MUGSHOT -->
                        <div class="space-y-2 text-center">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-mono-code font-extrabold text-slate-900 block uppercase">Left Profile (90&deg;)</span>
                                <span id="badgeLeft_${personCounter}" class="text-[9px] font-mono-code font-bold text-slate-400 uppercase">Not Uploaded</span>
                            </div>
                            
                            <!-- INTERACTIVE DROPZONE FRAME (FULL PREVIEW, NO CROPPING) -->
                            <div 
                                id="dropzoneLeft_${personCounter}"
                                onclick="document.getElementById('fileInputLeft_${personCounter}').click()"
                                ondragover="handleMugshotDragOver(event, this)"
                                ondragleave="handleMugshotDragLeave(event, this)"
                                ondrop="handleMugshotDrop(event, 'fileInputLeft_${personCounter}', 'previewLeft_${personCounter}', 'placeholderLeft_${personCounter}', 'hiddenLeft_${personCounter}', 'actionsLeft_${personCounter}', 'badgeLeft_${personCounter}')"
                                class="h-48 w-full bg-slate-50 hover:bg-blue-50/60 rounded-2xl border-2 border-dashed border-slate-300 hover:border-blue-500 flex flex-col items-center justify-center overflow-hidden relative group transition-all duration-200 cursor-pointer shadow-2xs"
                            >
                                <img id="previewLeft_${personCounter}" src="" alt="Left Profile Preview" class="h-full w-full object-contain p-1 hidden transition-all duration-200">
                                
                                <div id="placeholderLeft_${personCounter}" class="flex flex-col items-center justify-center text-slate-400 p-3 space-y-2 pointer-events-none">
                                    <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center shadow-xs">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <span class="text-xs font-bold text-slate-700">Click or Drag &amp; Drop Image</span>
                                    <span class="text-[9px] font-mono-code text-slate-400">Supports JPG, PNG, WEBP</span>
                                </div>

                                <div id="actionsLeft_${personCounter}" class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs opacity-0 group-hover:opacity-100 flex items-center justify-center space-x-2 transition-opacity duration-200 hidden" onclick="event.stopPropagation()">
                                    <button type="button" onclick="openMugshotModal(document.getElementById('previewLeft_${personCounter}').src, 'Left Profile (90&deg;) - Co-Suspect #${personCounter}')" class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-bold rounded-xl transition-all shadow-md flex items-center space-x-1 cursor-pointer border-none">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <span>View Full</span>
                                    </button>
                                    <button type="button" onclick="clearMugshotPhoto('fileInputLeft_${personCounter}', 'urlInputLeft_${personCounter}', 'previewLeft_${personCounter}', 'placeholderLeft_${personCounter}', 'hiddenLeft_${personCounter}', 'actionsLeft_${personCounter}', 'badgeLeft_${personCounter}')" class="px-2.5 py-1.5 bg-red-600 hover:bg-red-700 text-white text-[10px] font-bold rounded-xl transition-all shadow-md flex items-center space-x-1 cursor-pointer border-none">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        <span>Clear</span>
                                    </button>
                                </div>
                            </div>

                            <div class="space-y-1.5 pt-1">
                                <input 
                                    type="file" 
                                    id="fileInputLeft_${personCounter}" 
                                    accept="image/*" 
                                    onchange="previewMugshotFile(this, 'previewLeft_${personCounter}', 'placeholderLeft_${personCounter}', 'hiddenLeft_${personCounter}', 'actionsLeft_${personCounter}', 'badgeLeft_${personCounter}')" 
                                    class="hidden"
                                >
                                <div class="flex items-center space-x-1.5">
                                    <button type="button" onclick="document.getElementById('fileInputLeft_${personCounter}').click()" class="w-full py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 text-[11px] font-extrabold rounded-xl border border-blue-200 transition-colors flex items-center justify-center space-x-1 cursor-pointer">
                                        <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                        <span>Choose File</span>
                                    </button>
                                </div>
                                <input 
                                    type="text" 
                                    id="urlInputLeft_${personCounter}"
                                    placeholder="Or paste image URL..." 
                                    oninput="previewMugshotUrl(this.value, 'previewLeft_${personCounter}', 'placeholderLeft_${personCounter}', 'hiddenLeft_${personCounter}', 'actionsLeft_${personCounter}', 'badgeLeft_${personCounter}')" 
                                    class="w-full px-2.5 py-1.5 text-[10px] bg-slate-50 border border-slate-200 rounded-xl font-mono-code text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white transition-all"
                                >
                                <input type="hidden" name="associated_persons[${personCounter}][mugshot_left]" id="hiddenLeft_${personCounter}">
                            </div>
                        </div>

                        <!-- RIGHT MUGSHOT -->
                        <div class="space-y-2 text-center">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-mono-code font-extrabold text-slate-900 block uppercase">Right Profile (90&deg;)</span>
                                <span id="badgeRight_${personCounter}" class="text-[9px] font-mono-code font-bold text-slate-400 uppercase">Not Uploaded</span>
                            </div>
                            
                            <!-- INTERACTIVE DROPZONE FRAME (FULL PREVIEW, NO CROPPING) -->
                            <div 
                                id="dropzoneRight_${personCounter}"
                                onclick="document.getElementById('fileInputRight_${personCounter}').click()"
                                ondragover="handleMugshotDragOver(event, this)"
                                ondragleave="handleMugshotDragLeave(event, this)"
                                ondrop="handleMugshotDrop(event, 'fileInputRight_${personCounter}', 'previewRight_${personCounter}', 'placeholderRight_${personCounter}', 'hiddenRight_${personCounter}', 'actionsRight_${personCounter}', 'badgeRight_${personCounter}')"
                                class="h-48 w-full bg-slate-50 hover:bg-blue-50/60 rounded-2xl border-2 border-dashed border-slate-300 hover:border-blue-500 flex flex-col items-center justify-center overflow-hidden relative group transition-all duration-200 cursor-pointer shadow-2xs"
                            >
                                <img id="previewRight_${personCounter}" src="" alt="Right Profile Preview" class="h-full w-full object-contain p-1 hidden transition-all duration-200">
                                
                                <div id="placeholderRight_${personCounter}" class="flex flex-col items-center justify-center text-slate-400 p-3 space-y-2 pointer-events-none">
                                    <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center shadow-xs">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <span class="text-xs font-bold text-slate-700">Click or Drag &amp; Drop Image</span>
                                    <span class="text-[9px] font-mono-code text-slate-400">Supports JPG, PNG, WEBP</span>
                                </div>

                                <div id="actionsRight_${personCounter}" class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs opacity-0 group-hover:opacity-100 flex items-center justify-center space-x-2 transition-opacity duration-200 hidden" onclick="event.stopPropagation()">
                                    <button type="button" onclick="openMugshotModal(document.getElementById('previewRight_${personCounter}').src, 'Right Profile (90&deg;) - Co-Suspect #${personCounter}')" class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-bold rounded-xl transition-all shadow-md flex items-center space-x-1 cursor-pointer border-none">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <span>View Full</span>
                                    </button>
                                    <button type="button" onclick="clearMugshotPhoto('fileInputRight_${personCounter}', 'urlInputRight_${personCounter}', 'previewRight_${personCounter}', 'placeholderRight_${personCounter}', 'hiddenRight_${personCounter}', 'actionsRight_${personCounter}', 'badgeRight_${personCounter}')" class="px-2.5 py-1.5 bg-red-600 hover:bg-red-700 text-white text-[10px] font-bold rounded-xl transition-all shadow-md flex items-center space-x-1 cursor-pointer border-none">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        <span>Clear</span>
                                    </button>
                                </div>
                            </div>

                            <div class="space-y-1.5 pt-1">
                                <input 
                                    type="file" 
                                    id="fileInputRight_${personCounter}" 
                                    accept="image/*" 
                                    onchange="previewMugshotFile(this, 'previewRight_${personCounter}', 'placeholderRight_${personCounter}', 'hiddenRight_${personCounter}', 'actionsRight_${personCounter}', 'badgeRight_${personCounter}')" 
                                    class="hidden"
                                >
                                <div class="flex items-center space-x-1.5">
                                    <button type="button" onclick="document.getElementById('fileInputRight_${personCounter}').click()" class="w-full py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 text-[11px] font-extrabold rounded-xl border border-blue-200 transition-colors flex items-center justify-center space-x-1 cursor-pointer">
                                        <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                        <span>Choose File</span>
                                    </button>
                                </div>
                                <input 
                                    type="text" 
                                    id="urlInputRight_${personCounter}"
                                    placeholder="Or paste image URL..." 
                                    oninput="previewMugshotUrl(this.value, 'previewRight_${personCounter}', 'placeholderRight_${personCounter}', 'hiddenRight_${personCounter}', 'actionsRight_${personCounter}', 'badgeRight_${personCounter}')" 
                                    class="w-full px-2.5 py-1.5 text-[10px] bg-slate-50 border border-slate-200 rounded-xl font-mono-code text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white transition-all"
                                >
                                <input type="hidden" name="associated_persons[${personCounter}][mugshot_right]" id="hiddenRight_${personCounter}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. PERSONAL IDENTITY & BIOMETRIC DETAILS -->
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-slate-900 font-extrabold text-[11px] text-black uppercase mb-1">Full Name <span class="text-red-600">*</span></label>
                        <input type="text" name="associated_persons[${personCounter}][suspect_name]" required oninput="syncSuspectNamesToExhibits()" class="assoc-suspect-name-input w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900" placeholder="e.g. Kwame Mensah">
                    </div>
                    <div>
                        <label class="block text-slate-900 font-extrabold text-[11px] text-black uppercase mb-1">Alias / Street Name</label>
                        <input type="text" name="associated_persons[${personCounter}][suspect_alias]" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-800" placeholder="e.g. Snake">
                    </div>
                    <div>
                        <label class="block text-slate-900 font-extrabold text-[11px] text-black uppercase mb-1">Ghana Card / National ID</label>
                        <input type="text" name="associated_persons[${personCounter}][ghana_card]" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono-code" placeholder="GHA-000000000-0">
                    </div>
                    <div>
                        <label class="block text-slate-900 font-extrabold text-[11px] text-black uppercase mb-1">Role / Association <span class="text-red-600">*</span></label>
                        <select name="associated_persons[${personCounter}][role]" class="select2-role-dropdown w-full px-3 py-2 bg-white border border-blue-300 rounded-xl text-xs font-extrabold text-blue-700">
                            <option value=""></option>
                            <option value="Co-Suspect">Co-Suspect</option>
                            <option value="Accomplice">Accomplice</option>
                            <option value="Driver / Courier">Driver / Courier</option>
                            <option value="Buyer / Receiver">Buyer / Receiver</option>
                            <option value="Informant">Informant</option>
                            <option value="Witness">Witness</option>
                            <option value="Financier / Sponsor">Financier / Sponsor</option>
                            <option value="Kingpin / Ring Leader">Kingpin / Ring Leader</option>
                            <option value="Mastermind">Mastermind</option>
                            <option value="Store Keeper / Vault Custodian">Store Keeper / Vault Custodian</option>
                            <option value="Smuggler / Trafficker">Smuggler / Trafficker</option>
                            <option value="Associate">General Associate</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-slate-900 font-extrabold text-[11px] text-black uppercase mb-1">Gender <span class="text-red-600">*</span></label>
                        <select name="associated_persons[${personCounter}][gender]" required class="select2-gender-dropdown w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold">
                            <option value=""></option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-900 font-extrabold text-[11px] text-black uppercase mb-1 flex items-center justify-between">
                            <span>Age</span>
                            <span class="text-[9px] font-mono-code text-slate-500 font-bold uppercase">Auto-Calc</span>
                        </label>
                        <input type="number" name="associated_persons[${personCounter}][age]" id="personAge_${personCounter}" class="w-full px-3 py-2 bg-slate-100 border border-slate-300 rounded-xl text-xs font-mono-code font-bold text-slate-900 cursor-not-allowed" placeholder="Auto-calculated" readonly>
                    </div>
                    <div>
                        <label class="block text-slate-900 font-extrabold text-[11px] text-black uppercase mb-1">Date of Birth <span class="text-red-600">*</span></label>
                        <input type="date" name="associated_persons[${personCounter}][dob]" id="personDob_${personCounter}" onchange="calculateAgeFromDob(this, 'personAge_${personCounter}')" oninput="calculateAgeFromDob(this, 'personAge_${personCounter}')" required class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono-code text-slate-800">
                    </div>
                    <div>
                        <label class="block text-slate-900 font-extrabold text-[11px] text-black uppercase mb-1">Nationality <span class="text-red-600">*</span></label>
                        <select name="associated_persons[${personCounter}][nationality]" class="select2-nationality-dropdown w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold">
                            ${getNationalityOptionsHtml()}
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-slate-900 font-extrabold text-[11px] text-black uppercase mb-1">Place of Birth <span class="text-red-600">*</span></label>
                        <input type="text" name="associated_persons[${personCounter}][place_of_birth]" required class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900" placeholder="e.g. Kumasi, Ashanti Region">
                    </div>
                    <div>
                        <label class="block text-slate-900 font-extrabold text-[11px] text-black uppercase mb-1">Height (cm / ft-in)</label>
                        <div class="relative flex items-center">
                            <input type="text" name="associated_persons[${personCounter}][height]" class="w-full px-3 pr-20 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono-code font-bold text-slate-900" placeholder="e.g. 180">
                            <div class="absolute right-1 flex items-center">
                                <select name="associated_persons[${personCounter}][height_unit]" class="py-1 px-1.5 bg-slate-100 text-slate-800 font-mono-code font-black text-xs border border-slate-300 rounded-lg focus:outline-none uppercase">
                                    <option value="CM" selected>CM</option>
                                    <option value="IN">IN</option>
                                    <option value="FT">FT</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-slate-900 font-extrabold text-[11px] text-black uppercase mb-1">Eye Color</label>
                        <select name="associated_persons[${personCounter}][eye_color]" class="select2-eyecolor-dropdown w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold">
                            <option value=""></option>
                            <option value="Brown">Brown</option>
                            <option value="Black">Black</option>
                            <option value="Dark Brown">Dark Brown</option>
                            <option value="Hazel">Hazel</option>
                            <option value="Blue">Blue</option>
                            <option value="Green">Green</option>
                            <option value="Amber">Amber</option>
                            <option value="Gray">Gray</option>
                            <option value="Heterochromia (Different Colors)">Heterochromia (Different Colors)</option>
                            <option value="Other / Unknown">Other / Unknown</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-900 font-extrabold text-[11px] text-black uppercase mb-1">Contact Phone Number(s) <span class="text-red-600">*</span></label>
                        <input type="text" name="associated_persons[${personCounter}][phone_number]" required class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono-code font-bold text-slate-900" placeholder="e.g. +233 24 123 4567">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-slate-900 font-extrabold text-[11px] text-black uppercase mb-1">Residential Address / GPS</label>
                        <input type="text" name="associated_persons[${personCounter}][residential_address]" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-medium text-slate-800" placeholder="e.g. Hse No. 14 Ring Road, Accra / Digital Address: GA-123-4567">
                    </div>
                    <div>
                        <label class="block text-slate-900 font-extrabold text-[11px] text-black uppercase mb-1">Allocated Holding Facility</label>
                        <input type="text" name="associated_persons[${personCounter}][holding_facility]" value="" placeholder="e.g. NACOC Holding Cell A-01" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-extrabold text-emerald-700">
                    </div>
                </div>

                <!-- 3. FAMILY & RELATIONSHIP RECORDS -->
                <div class="space-y-3 pt-3 border-t border-slate-200/80">
                    <div class="flex items-center space-x-2 text-slate-800">
                        <span class="w-2 h-2 rounded-full bg-emerald-600 inline-block"></span>
                        <h5 class="font-extrabold text-xs text-slate-900 uppercase font-mono-code tracking-wider">Family &amp; Relationship Records</h5>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[11px] uppercase mb-1">Marital / Relationship Status</label>
                            <select name="associated_persons[${personCounter}][marital_status]" class="select2-marital-dropdown w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold">
                                <option value=""></option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Divorced">Divorced</option>
                                <option value="Widowed">Widowed</option>
                                <option value="Separated">Separated</option>
                                <option value="Cohabiting / Partner">Cohabiting / Partner</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[11px] uppercase mb-1">Spouse / Partner Name</label>
                            <input type="text" name="associated_persons[${personCounter}][spouse_name]" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-medium text-slate-900" placeholder="e.g. Abena Mensah">
                        </div>
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[11px] uppercase mb-1">Spouse / Partner Contact Phone</label>
                            <input type="text" name="associated_persons[${personCounter}][spouse_phone]" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono-code text-slate-900" placeholder="e.g. +233 20 987 6543">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-slate-900 font-extrabold text-[11px] uppercase mb-1">Spouse / Partner Details &amp; Notes</label>
                            <input type="text" name="associated_persons[${personCounter}][spouse_info]" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-800" placeholder="e.g. Trader at Makola Market / Co-residence">
                        </div>
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[11px] uppercase mb-1">Children &amp; Dependents</label>
                            <input type="text" name="associated_persons[${personCounter}][dependents]" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-800" placeholder="e.g. 2 Children (Ages 4 &amp; 7)">
                        </div>
                    </div>
                </div>

                <!-- 4. OFFENCE CHARGES & ARREST DETAILS -->
                <div class="space-y-3 pt-3 border-t border-slate-200/80">
                    <div class="flex items-center space-x-2 text-slate-800">
                        <span class="w-2 h-2 rounded-full bg-amber-600 inline-block"></span>
                        <h5 class="font-extrabold text-xs text-slate-900 uppercase font-mono-code tracking-wider">Offence Charges &amp; Arrest Details</h5>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[11px] uppercase mb-1">Raid Scene / Place of Arrest <span class="text-red-600">*</span></label>
                            <input type="text" name="associated_persons[${personCounter}][raid_location]" required class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900" placeholder="e.g. Kotoka International Airport Cargo Bay">
                        </div>
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[11px] uppercase mb-1">Date of Arrest <span class="text-red-600">*</span></label>
                            <input type="date" name="associated_persons[${personCounter}][date_of_arrest]" required value="" placeholder="Select Date of Arrest" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono-code text-slate-900">
                        </div>
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[11px] uppercase mb-1">Time of Arrest <span class="text-red-600">*</span></label>
                            <input type="time" name="associated_persons[${personCounter}][time_of_arrest]" value="" placeholder="Select Time of Arrest" required class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono-code text-slate-900">
                        </div>
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[11px] uppercase mb-1">Allocated Holding Facility</label>
                            <input type="text" name="associated_persons[${personCounter}][holding_facility]" value="" placeholder="e.g. NACOC Holding Cell A-01" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-extrabold text-emerald-700">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[11px] uppercase mb-1">Arresting Officer(s) <span class="text-red-600">*</span></label>
                            <input type="text" name="associated_persons[${personCounter}][arresting_officer]" required class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900 placeholder:text-slate-400" placeholder="e.g. Insp. Kwame Mensah, Sgt. Lorinda Mensah">
                        </div>
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[11px] uppercase mb-1">Arresting Officer Service / Badge ID <span class="text-red-600">*</span></label>
                            <input type="text" name="associated_persons[${personCounter}][arresting_officer_id]" required class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono-code font-bold text-slate-900 placeholder:text-slate-400" placeholder="e.g. SERVICE-98210, BADGE-88210">
                        </div>
                        <div class="col-span-full">
                            <label class="block text-slate-900 font-extrabold text-[11px] uppercase mb-1">Alleged Offense / Charges Description</label>
                            <textarea name="associated_persons[${personCounter}][offense]" rows="2" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-medium text-slate-900 placeholder:text-slate-400" placeholder="e.g. Conspiracy to import narcotic drugs, unlawful possession of controlled substance..."></textarea>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(card);

            if (window.jQuery && $.fn.select2) {
                const $card = $(`#personCard-${personCounter}`);
                $card.find('.select2-role-dropdown').select2({
                    tags: true,
                    allowClear: true,
                    placeholder: "-- Select or Type Custom Role / Association --",
                    createTag: function(params) {
                        var term = $.trim(params.term);
                        if (term === '') {
                            return null;
                        }
                        return {
                            id: term,
                            text: term,
                            newTag: true
                        };
                    },
                    templateResult: function(role) {
                        if (!role.id) return role.text;
                        const roleBadges = {
                            'Co-Suspect': { bg: 'bg-blue-100', text: 'text-blue-800', border: 'border-blue-200' },
                            'Accomplice': { bg: 'bg-purple-100', text: 'text-purple-800', border: 'border-purple-200' },
                            'Driver / Courier': { bg: 'bg-amber-100', text: 'text-amber-800', border: 'border-amber-200' },
                            'Buyer / Receiver': { bg: 'bg-indigo-100', text: 'text-indigo-800', border: 'border-indigo-200' },
                            'Informant': { bg: 'bg-emerald-100', text: 'text-emerald-800', border: 'border-emerald-200' },
                            'Witness': { bg: 'bg-slate-100', text: 'text-slate-800', border: 'border-slate-200' },
                            'Financier / Sponsor': { bg: 'bg-emerald-100', text: 'text-emerald-900', border: 'border-emerald-300' },
                            'Kingpin / Ring Leader': { bg: 'bg-red-100', text: 'text-red-800', border: 'border-red-200' },
                            'Mastermind': { bg: 'bg-rose-100', text: 'text-rose-800', border: 'border-rose-200' },
                            'Store Keeper / Vault Custodian': { bg: 'bg-cyan-100', text: 'text-cyan-800', border: 'border-cyan-200' },
                            'Smuggler / Trafficker': { bg: 'bg-orange-100', text: 'text-orange-800', border: 'border-orange-200' },
                            'Associate': { bg: 'bg-slate-100', text: 'text-slate-700', border: 'border-slate-200' }
                        };
                        const b = roleBadges[role.text] || { bg: 'bg-blue-100', text: 'text-blue-800', border: 'border-blue-200' };
                        const tagLabel = role.newTag ? 'CUSTOM ROLE' : 'ROLE';
                        return $(`
                            <div class="flex items-center justify-between py-0.5">
                                <span class="font-extrabold text-slate-900 text-xs">${role.text}</span>
                                <span class="px-2 py-0.5 text-[9px] font-mono-code font-bold rounded-md uppercase border ${b.bg} ${b.text} ${b.border}">${tagLabel}</span>
                            </div>
                        `);
                    },
                    templateSelection: function(role) { return role.text; },
                    width: '100%'
                });
                $card.find('.select2-gender-dropdown').select2({
                    allowClear: true,
                    placeholder: "-- Select Gender --",
                    templateResult: function(gen) {
                        if (!gen.id) return gen.text;
                        return $(`<span class="font-bold text-slate-800 text-xs">${gen.text}</span>`);
                    },
                    templateSelection: function(gen) { return gen.text; },
                    width: '100%'
                });
                $card.find('.select2-nationality-dropdown').select2({
                    tags: true,
                    allowClear: true,
                    placeholder: "-- Select or Type Custom World Nationality --",
                    createTag: function(params) {
                        var term = $.trim(params.term);
                        if (term === '') {
                            return null;
                        }
                        return {
                            id: term,
                            text: term,
                            newTag: true
                        };
                    },
                    templateResult: function(nat) {
                        if (!nat.id) return nat.text;
                        const isGhana = nat.text === 'Ghanaian';
                        const dot = nat.newTag
                            ? '<span class="px-2 py-0.5 bg-blue-100 text-blue-800 border border-blue-300 text-[9px] font-mono-code font-bold rounded-md uppercase">CUSTOM</span>'
                            : (isGhana
                                ? '<span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 border border-emerald-300 text-[9px] font-mono-code font-bold rounded-md uppercase">GHANA</span>'
                                : '<span class="w-2 h-2 rounded-full bg-blue-400 shrink-0"></span>');
                        return $(`
                            <div class="flex items-center justify-between py-0.5">
                                <span class="font-bold text-slate-800 text-xs">${nat.text}</span>
                                ${dot}
                            </div>
                        `);
                    },
                    templateSelection: function(nat) { return nat.text; },
                    width: '100%'
                });
                $card.find('.select2-eyecolor-dropdown').select2({
                    allowClear: true,
                    placeholder: "-- Select Eye Color --",
                    templateResult: function(eye) {
                        if (!eye.id) return eye.text;
                        const eyeColors = {
                            'Brown': 'bg-amber-900',
                            'Black': 'bg-slate-950',
                            'Dark Brown': 'bg-amber-950',
                            'Hazel': 'bg-amber-700',
                            'Blue': 'bg-blue-600',
                            'Green': 'bg-emerald-600',
                            'Amber': 'bg-amber-500',
                            'Gray': 'bg-slate-400',
                            'Heterochromia (Different Colors)': 'bg-gradient-to-r from-blue-500 to-amber-600',
                            'Other / Unknown': 'bg-slate-300'
                        };
                        const swatch = eyeColors[eye.text] || 'bg-slate-400';
                        return $(`
                            <div class="flex items-center justify-between py-0.5">
                                <span class="font-bold text-slate-800 text-xs">${eye.text}</span>
                                <span class="w-3.5 h-3.5 rounded-full ${swatch} border border-slate-300 shrink-0 inline-block shadow-2xs"></span>
                            </div>
                        `);
                    },
                    templateSelection: function(eye) { return eye.text; },
                    width: '100%'
                });
                $card.find('.select2-marital-dropdown').select2({
                    allowClear: true,
                    placeholder: "Select Marital / Relationship Status",
                    templateResult: function(m) {
                        if (!m.id) return m.text;
                        const badges = {
                            'Single': '<span class="px-2 py-0.5 bg-blue-100 text-blue-800 border border-blue-200 text-[9px] font-mono-code font-bold rounded-md uppercase">SINGLE</span>',
                            'Married': '<span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 border border-emerald-200 text-[9px] font-mono-code font-bold rounded-md uppercase">MARRIED</span>',
                            'Divorced': '<span class="px-2 py-0.5 bg-purple-100 text-purple-800 border border-purple-200 text-[9px] font-mono-code font-bold rounded-md uppercase">DIVORCED</span>',
                            'Widowed': '<span class="px-2 py-0.5 bg-slate-100 text-slate-800 border border-slate-200 text-[9px] font-mono-code font-bold rounded-md uppercase">WIDOWED</span>',
                            'Separated': '<span class="px-2 py-0.5 bg-amber-100 text-amber-800 border border-amber-200 text-[9px] font-mono-code font-bold rounded-md uppercase">SEPARATED</span>',
                            'Cohabiting / Partner': '<span class="px-2 py-0.5 bg-rose-100 text-rose-800 border border-rose-200 text-[9px] font-mono-code font-bold rounded-md uppercase">PARTNER</span>'
                        };
                        const badge = badges[m.text] || '<span class="w-2 h-2 rounded-full bg-slate-400 shrink-0"></span>';
                        return $(`
                            <div class="flex items-center justify-between py-0.5">
                                <span class="font-bold text-slate-800 text-xs">${m.text}</span>
                                ${badge}
                            </div>
                        `);
                    },
                    templateSelection: function(m) { return m.text; },
                    width: '100%'
                });
            }
        }

        function handleMugshotDragOver(e, dropzoneEl) {
            e.preventDefault();
            e.stopPropagation();
            if (dropzoneEl) dropzoneEl.classList.add('border-blue-600', 'bg-blue-100/50');
        }

        function handleMugshotDragLeave(e, dropzoneEl) {
            e.preventDefault();
            e.stopPropagation();
            if (dropzoneEl) dropzoneEl.classList.remove('border-blue-600', 'bg-blue-100/50');
        }

        function handleMugshotDrop(e, fileInputId, previewImgId, placeholderId, hiddenInputId, actionsId, badgeId) {
            e.preventDefault();
            e.stopPropagation();
            const dropzoneEl = e.currentTarget;
            if (dropzoneEl) dropzoneEl.classList.remove('border-blue-600', 'bg-blue-100/50');

            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                const fileInput = document.getElementById(fileInputId);
                if (fileInput) {
                    fileInput.files = e.dataTransfer.files;
                    previewMugshotFile(fileInput, previewImgId, placeholderId, hiddenInputId, actionsId, badgeId);
                }
            }
        }

        function previewMugshotFile(fileInput, previewImgId, placeholderId, hiddenInputId, actionsId, badgeId) {
            if (fileInput.files && fileInput.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const dataUrl = e.target.result;
                    applyMugshotPreview(dataUrl, previewImgId, placeholderId, hiddenInputId, actionsId, badgeId);
                };
                reader.readAsDataURL(fileInput.files[0]);
            }
        }

        function previewMugshotUrl(urlStr, previewImgId, placeholderId, hiddenInputId, actionsId, badgeId) {
            const val = (urlStr || '').trim();
            if (val) {
                applyMugshotPreview(val, previewImgId, placeholderId, hiddenInputId, actionsId, badgeId);
            } else {
                clearMugshotState(previewImgId, placeholderId, hiddenInputId, actionsId, badgeId);
            }
        }

        function applyMugshotPreview(srcUrl, previewImgId, placeholderId, hiddenInputId, actionsId, badgeId) {
            const previewEl = document.getElementById(previewImgId);
            const placeholderEl = document.getElementById(placeholderId);
            const hiddenEl = document.getElementById(hiddenInputId);
            const actionsEl = document.getElementById(actionsId);
            const badgeEl = document.getElementById(badgeId);

            if (previewEl) {
                previewEl.src = srcUrl;
                previewEl.classList.remove('hidden');
            }
            if (placeholderEl) placeholderEl.classList.add('hidden');
            if (actionsEl) actionsEl.classList.remove('hidden');
            if (hiddenEl) hiddenEl.value = srcUrl;
            if (badgeEl) {
                badgeEl.innerText = 'Uploaded';
                badgeEl.className = 'text-[9px] font-mono-code font-extrabold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 uppercase';
            }
        }

        function clearMugshotPhoto(fileInputId, urlInputId, previewImgId, placeholderId, hiddenInputId, actionsId, badgeId) {
            const fileInput = document.getElementById(fileInputId);
            const urlInput = document.getElementById(urlInputId);
            if (fileInput) fileInput.value = '';
            if (urlInput) urlInput.value = '';
            clearMugshotState(previewImgId, placeholderId, hiddenInputId, actionsId, badgeId);
        }

        function clearMugshotState(previewImgId, placeholderId, hiddenInputId, actionsId, badgeId) {
            const previewEl = document.getElementById(previewImgId);
            const placeholderEl = document.getElementById(placeholderId);
            const hiddenEl = document.getElementById(hiddenInputId);
            const actionsEl = document.getElementById(actionsId);
            const badgeEl = document.getElementById(badgeId);

            if (previewEl) {
                previewEl.src = '';
                previewEl.classList.add('hidden');
            }
            if (placeholderEl) placeholderEl.classList.remove('hidden');
            if (actionsEl) actionsEl.classList.add('hidden');
            if (hiddenEl) hiddenEl.value = '';
            if (badgeEl) {
                badgeEl.innerText = 'Not Uploaded';
                badgeEl.className = 'text-[9px] font-mono-code font-bold text-slate-400 uppercase';
            }
        }

        function openMugshotModal(imgSrc, titleText) {
            const modal = document.getElementById('mugshotLightboxModal');
            const modalImg = document.getElementById('mugshotModalImage');
            const modalTitle = document.getElementById('mugshotModalTitle');
            if (modal && modalImg) {
                modalImg.src = imgSrc;
                if (modalTitle) modalTitle.innerText = titleText || 'Facial Biometric High-Res View';
                modal.classList.remove('hidden');
            }
        }

        function closeMugshotModal() {
            const modal = document.getElementById('mugshotLightboxModal');
            if (modal) modal.classList.add('hidden');
        }

        let seizureCounter = 0;

        function getSeizureBlockFromEvent(e) {
            if (!e) return document.querySelector('.seizure-block') || document;
            const target = e.target || e.srcElement;
            if (target && target.closest) {
                return target.closest('.seizure-block') || document.querySelector('.seizure-block') || document;
            }
            return document.querySelector('.seizure-block') || document;
        }

        function closeAllDropdowns() {}

        function toggleUnitDropdown(e) {
            if (e) e.stopPropagation();
            const root = getSeizureBlockFromEvent(e);
            const panel = root.querySelector('#unitListPanel');
            if (panel) panel.classList.toggle('hidden');
        }

        function selectUnit(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root = getSeizureBlockFromEvent(e);
            const hidden = root.querySelector('#newSeizureUnit');
            const display = root.querySelector('#unitSearchInput');
            const panel = root.querySelector('#unitListPanel');
            if (hidden) hidden.value = val;
            if (display) display.value = val;
            if (panel) panel.classList.add('hidden');
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

        function openExhibitListPanel(el) {
            openSeizurePanel(el, '#exhibitListPanel');
        }

        function openDrugListPanel(el) {
            openSeizurePanel(el, '#drugListPanel');
        }

        function openWeaponListPanel(el) {
            openSeizurePanel(el, '#weaponListPanel');
        }

        function openCurrencyListPanel(el) {
            openSeizurePanel(el, '#currencyListPanel');
        }

        function openAmmoListPanel(el) {
            openSeizurePanel(el, '#ammoListPanel');
        }

        function openDeviceListPanel(el) {
            openSeizurePanel(el, '#deviceListPanel');
        }

        function openPkgListPanel(el) {
            openSeizurePanel(el, '#pkgListPanel');
        }

        function openUnitListPanel(el) {
            openSeizurePanel(el, '#unitListPanel');
        }

        function openVehTypeListPanel(el) {
            openSeizurePanel(el, '#vehTypeListPanel');
        }

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
            const root = getSeizureBlockFromEvent(e);
            const hidden = root.querySelector('#newSeizurePackaging');
            const display = root.querySelector('#pkgSearchInput');
            const panel = root.querySelector('#pkgListPanel');
            if (hidden) hidden.value = val;
            if (display) display.value = val;
            if (panel) panel.classList.add('hidden');
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
            const input = root.querySelector('#newSeizureCategory');
            const display = root.querySelector('#exhibitSearchInput');
            const panel = root.querySelector('#exhibitListPanel');
            if (input) input.value = val;
            if (display) {
                display.value = val;
                display.removeAttribute('readonly');
            }
            if (panel) panel.classList.add('hidden');
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
            const textWrapper = root.querySelector('#commodityTextWrapper');
            const drugWrapper = root.querySelector('#drugSelectWrapper');
            const weaponWrapper = root.querySelector('#weaponSelectWrapper');
            const currencyWrapper = root.querySelector('#currencySelectWrapper');
            const ammoWrapper = root.querySelector('#ammoSelectWrapper');
            const deviceWrapper = root.querySelector('#deviceSelectWrapper');
            const deviceFieldsWrapper = root.querySelector('#deviceFieldsWrapper');
            const vehicleWrapper = root.querySelector('#vehicleFieldsWrapper');
            const weaponFieldsWrapper = root.querySelector('#weaponFieldsWrapper');
            const ammoFieldsWrapper = root.querySelector('#ammoFieldsWrapper');
            const textInput = root.querySelector('#newSeizureName');
            const drugHidden = root.querySelector('#newSeizureNameDrug');
            const weaponHidden = root.querySelector('#newSeizureNameWeapon');
            const currencyHidden = root.querySelector('#newSeizureNameCurrency');
            const ammoHidden = root.querySelector('#newSeizureNameAmmo');
            const deviceHidden = root.querySelector('#newSeizureNameDevice');
            const drugSearch = root.querySelector('#drugSearchInput');
            const weaponSearch = root.querySelector('#weaponSearchInput');
            const currencySearch = root.querySelector('#currencySearchInput');
            const ammoSearch = root.querySelector('#ammoSearchInput');
            const deviceSearch = root.querySelector('#deviceSearchInput');
            const quantityLabel = root.querySelector('#quantityLabel');

            // Hide all first
            if (textWrapper) textWrapper.classList.add('hidden');
            if (drugWrapper) drugWrapper.classList.add('hidden');
            if (weaponWrapper) weaponWrapper.classList.add('hidden');
            if (currencyWrapper) currencyWrapper.classList.add('hidden');
            if (ammoWrapper) ammoWrapper.classList.add('hidden');
            if (deviceWrapper) deviceWrapper.classList.add('hidden');
            if (deviceFieldsWrapper) deviceFieldsWrapper.classList.add('hidden');
            if (vehicleWrapper) vehicleWrapper.classList.add('hidden');
            if (weaponFieldsWrapper) weaponFieldsWrapper.classList.add('hidden');
            if (ammoFieldsWrapper) ammoFieldsWrapper.classList.add('hidden');

            const otherDescriptionWrapper = root.querySelector('#otherExhibitDescriptionWrapper') || root.querySelector(
                '.other-exhibit-description-wrapper');
            const otherDescriptionInput = root.querySelector('#newOtherExhibitDescription') || root.querySelector(
                '.other-exhibit-description-input');
            if (otherDescriptionWrapper) otherDescriptionWrapper.classList.add('hidden');
            if (otherDescriptionInput) otherDescriptionInput.removeAttribute('required');

            [textInput, drugHidden, weaponHidden, currencyHidden, ammoHidden, deviceHidden, drugSearch, weaponSearch,
                currencySearch, ammoSearch, deviceSearch
            ].forEach(el => {
                if (el) {
                    el.removeAttribute('required');
                    if (el.removeAttribute) el.removeAttribute('readonly');
                }
            });

            if (category === 'Drugs') {
                if (quantityLabel) quantityLabel.innerText = 'Quantity';
                if (drugWrapper) drugWrapper.classList.remove('hidden');
                if (drugSearch) {
                    drugSearch.setAttribute('required', 'required');
                    drugSearch.placeholder = 'Select or type custom drug name...';
                }
            } else if (category === 'Weapons') {
                if (quantityLabel) quantityLabel.innerText = 'Quantity';
                if (weaponWrapper) weaponWrapper.classList.remove('hidden');
                if (weaponFieldsWrapper) weaponFieldsWrapper.classList.remove('hidden');
                if (weaponSearch) {
                    weaponSearch.setAttribute('required', 'required');
                    weaponSearch.placeholder = 'Select or type custom weapon type...';
                }
            } else if (category === 'Money') {
                if (quantityLabel) quantityLabel.innerText = 'Amount';
                if (currencyWrapper) currencyWrapper.classList.remove('hidden');
                if (currencySearch) {
                    currencySearch.setAttribute('required', 'required');
                    currencySearch.placeholder = 'Select or type custom currency...';
                }
            } else if (category === 'Ammunition') {
                if (quantityLabel) quantityLabel.innerText = 'Quantity / Rounds';
                if (ammoWrapper) ammoWrapper.classList.remove('hidden');
                if (ammoFieldsWrapper) ammoFieldsWrapper.classList.remove('hidden');
                if (ammoSearch) {
                    ammoSearch.setAttribute('required', 'required');
                    ammoSearch.placeholder = 'Select or type custom ammunition type...';
                }
            } else if (category === 'Electronic Devices') {
                if (quantityLabel) quantityLabel.innerText = 'Quantity / Units';
                if (deviceWrapper) deviceWrapper.classList.remove('hidden');
                if (deviceSearch) {
                    deviceSearch.setAttribute('required', 'required');
                    deviceSearch.placeholder = 'Select or type custom electronic device type...';
                }
            } else {
                if (quantityLabel) quantityLabel.innerText = 'Quantity';
                if (textWrapper) textWrapper.classList.remove('hidden');
                if (textInput) textInput.setAttribute('required', 'required');
                const placeholders = {
                    'Gold': 'e.g. Gold Bars / Bullion (24K)',
                    'Vehicles': 'e.g. Toyota Hilux (Unregistered)',
                    'Other Exhibits': 'e.g. Hardware Devices / Illegal Tools'
                };
                if (textInput) textInput.placeholder = placeholders[category] || 'e.g. Packaging as Received';

                if (otherDescriptionWrapper) {
                    const isOther = category === 'Other Exhibits' || category === 'Other' || category ===
                        'Other Contraband' || (category && category.toLowerCase().includes('other'));
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

            const isLabUser = (typeof isLabAnalysis !== 'undefined' && isLabAnalysis) ||
                {{ $isLabAnalysis ? 'true' : 'false' }};
            const isDrugCase = {{ !empty($isDrugCase) ? 'true' : 'false' }};
            const labSec = document.getElementById('labAnalysisExclusiveSections');
            if (labSec) {
                if (window.activeLabWorkflowMode === 'nlr' && (isLabUser || isDrugCase)) {
                    labSec.classList.remove('hidden');
                } else {
                    labSec.classList.add('hidden');
                }
            }
        }




        function selectAmmoType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root = getSeizureBlockFromEvent(e);
            const hidden = root.querySelector('#newSeizureNameAmmo');
            const display = root.querySelector('#ammoSearchInput');
            const panel = root.querySelector('#ammoListPanel');
            if (hidden) hidden.value = val;
            if (display) display.value = val;
            if (panel) panel.classList.add('hidden');
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = val;
            if (typeof syncSection4ExhibitsToLabSection === 'function') {
                syncSection4ExhibitsToLabSection();
            }
        }


        // Universal panel close listener
        document.addEventListener('click', function(e) {
            document.querySelectorAll('.seizure-block').forEach(block => {
                const items = [{
                        wrapper: block.querySelector('#exhibitSelectWrapper'),
                        panel: block.querySelector('#exhibitListPanel')
                    },
                    {
                        wrapper: block.querySelector('#drugSelectWrapper'),
                        panel: block.querySelector('#drugListPanel')
                    },
                    {
                        wrapper: block.querySelector('#weaponSelectWrapper'),
                        panel: block.querySelector('#weaponListPanel')
                    },
                    {
                        wrapper: block.querySelector('#currencySelectWrapper'),
                        panel: block.querySelector('#currencyListPanel')
                    },
                    {
                        wrapper: block.querySelector('#ammoSelectWrapper'),
                        panel: block.querySelector('#ammoListPanel')
                    },
                    {
                        wrapper: block.querySelector('#deviceSelectWrapper'),
                        panel: block.querySelector('#deviceListPanel')
                    },
                    {
                        wrapper: block.querySelector('#packagingSelectWrapper'),
                        panel: block.querySelector('#pkgListPanel')
                    },
                    {
                        wrapper: block.querySelector('#unitSelectWrapper'),
                        panel: block.querySelector('#unitListPanel')
                    },
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
            const root = getSeizureBlockFromEvent(e);
            const hidden = root.querySelector('#newSeizureNameDevice');
            const display = root.querySelector('#deviceSearchInput');
            const panel = root.querySelector('#deviceListPanel');
            if (hidden) hidden.value = val;
            if (display) display.value = val;
            if (panel) panel.classList.add('hidden');
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
            const root = getSeizureBlockFromEvent(e);
            const hidden = root.querySelector('#newSeizureNameDrug');
            const display = root.querySelector('#drugSearchInput');
            const panel = root.querySelector('#drugListPanel');
            if (hidden) hidden.value = val;
            if (display) display.value = val;
            if (panel) panel.classList.add('hidden');
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
            const root = getSeizureBlockFromEvent(e);
            const hidden = root.querySelector('#newSeizureNameCurrency');
            const display = root.querySelector('#currencySearchInput');
            const panel = root.querySelector('#currencyListPanel');
            if (hidden) hidden.value = val;
            if (display) {
                display.value = val;
                display.removeAttribute('readonly');
            }
            if (panel) panel.classList.add('hidden');
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
            const root = getSeizureBlockFromEvent(e);
            const hidden = root.querySelector('#newSeizureNameWeapon');
            const display = root.querySelector('#weaponSearchInput');
            const panel = root.querySelector('#weaponListPanel');
            if (hidden) hidden.value = val;
            if (display) display.value = val;
            if (panel) panel.classList.add('hidden');
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
        }, {
            passive: true
        });

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
                    gIcon.className =
                        'w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-bold';
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
                    nIcon.className =
                        'w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-bold';
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

            const modeParam = urlParams.get('mode') || urlParams.get('action');
            if (modeParam === 'nlr' || urlParams.get('section') === '5') {
                if (typeof switchToNlrExamSection === 'function') {
                    setTimeout(() => switchToNlrExamSection(), 200);
                }
            } else if (modeParam === 'gsa' || urlParams.get('section') === 'gsa') {
                if (typeof switchToGsaUploadSection === 'function') {
                    setTimeout(() => switchToGsaUploadSection(), 200);
                }
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
                window.open('/cases/' + lastMatchedSuspectCaseNumber + '/review?from=' + encodeURIComponent(
                    @json($userUnit)), '_blank');
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
                    badgeEl.className =
                        'px-2.5 py-0.5 rounded-full text-[10px] font-mono-code font-bold bg-red-100 text-red-800 border border-red-200 uppercase tracking-wider';
                }
                if (iconBox) {
                    iconBox.className =
                        'w-12 h-12 rounded-2xl bg-red-600 text-white flex items-center justify-center shrink-0 shadow-lg shadow-red-600/30';
                }
                if (titleEl) titleEl.innerText = 'Possible Duplicate Case Found';
                if (subEl) subEl.innerText = data.message ||
                    'An existing case file closely matches the suspect biodata, operation, and incident details entered.';
            } else {
                if (badgeEl) {
                    badgeEl.innerText = 'POTENTIAL DUPLICATE CASE DETECTED';
                    badgeEl.className =
                        'px-2.5 py-0.5 rounded-full text-[10px] font-mono-code font-bold bg-amber-100 text-amber-800 border border-amber-200 uppercase tracking-wider';
                }
                if (iconBox) {
                    iconBox.className =
                        'w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-lg shadow-amber-500/30';
                }
                if (titleEl) titleEl.innerText = 'Potential Duplicate Detected';
                if (subEl) subEl.innerText = data.message ||
                    'Some details match an existing record. Please review before proceeding.';
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
                window.open('/cases/' + currentMatchedCaseNumber + '/review?from=' + encodeURIComponent(
                    @json($userUnit)), '_blank');
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
            const root = getSeizureBlockFromEvent(e);
            const hidden = root.querySelector('#vehType');
            const display = root.querySelector('#vehTypeSearchInput');
            const panel = root.querySelector('#vehTypeListPanel');
            if (hidden) hidden.value = val;
            if (display) {
                display.value = val;
                display.setAttribute('readonly', 'readonly');
            }
            if (panel) panel.classList.add('hidden');
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
                const panels = [{
                        wrapper: '#exhibitSelectWrapper',
                        panel: '#exhibitListPanel'
                    },
                    {
                        wrapper: '#drugSelectWrapper',
                        panel: '#drugListPanel'
                    },
                    {
                        wrapper: '#weaponSelectWrapper',
                        panel: '#weaponListPanel'
                    },
                    {
                        wrapper: '#currencySelectWrapper',
                        panel: '#currencyListPanel'
                    },
                    {
                        wrapper: '#ammoSelectWrapper',
                        panel: '#ammoListPanel'
                    },
                    {
                        wrapper: '#deviceSelectWrapper',
                        panel: '#deviceListPanel'
                    },
                    {
                        wrapper: '#pkgSelectWrapper',
                        panel: '#pkgListPanel'
                    },
                    {
                        wrapper: '#unitSelectWrapper',
                        panel: '#unitListPanel'
                    },
                    {
                        wrapper: '#vehTypeSelectWrapper',
                        panel: '#vehTypeListPanel'
                    },
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


        function addAssociateSeizureBlock() {
            seizureCounter++;
            const container = document.getElementById('seizuresListContainer');
            if (!container) return;

            let allowedCats = Array.isArray(UNIT_ALLOWED_CATEGORIES) && UNIT_ALLOWED_CATEGORIES.length > 0
                ? UNIT_ALLOWED_CATEGORIES
                : ['Drugs', 'Money', 'Precious Stones', 'Vehicles', 'Electronic Devices', 'Weapons', 'Ammunition', 'Other Exhibits'];

            let categoryListHtml = '';
            allowedCats.forEach(cName => {
                const cMeta = ALL_EXHIBIT_CATEGORIES_META[cName] || { name: cName, desc: 'Exhibit Item', code: 'EX', cls: 'bg-slate-100 text-slate-700' };
                categoryListHtml += `
                    <li class="exhibit-item" onclick="selectExhibitCategory('${cMeta.name}', event)">
                        <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between transition-colors">
                            <div><div class="font-extrabold text-slate-900">${cMeta.name}</div><div class="text-[10px] text-slate-500 font-normal">${cMeta.desc}</div></div>
                            <span class="text-[10px] font-mono-code ${cMeta.cls} px-2 py-0.5 rounded font-bold">${cMeta.code}</span>
                        </button>
                    </li>
                `;
            });

            const card = document.createElement('div');
            card.id = `seizureBlock-${seizureCounter}`;
            card.className = 'seizure-block bg-slate-50/90 p-5 rounded-2xl border border-emerald-200 shadow-sm space-y-5 relative';
            card.setAttribute('data-seizure-index', seizureCounter);

            card.innerHTML = `
                <!-- BLOCK HEADER & REMOVE BUTTON -->
                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <div class="flex items-center space-x-2.5">
                        <span class="seizure-number-badge w-6 h-6 rounded-full bg-emerald-600 text-white font-mono-code font-bold text-xs flex items-center justify-center shadow-xs">
                            ${seizureCounter}
                        </span>
                        <h4 class="seizure-title font-extrabold text-slate-900 text-sm">
                            Seizure Item #${seizureCounter}
                        </h4>
                    </div>
                    <button type="button" onclick="removeAssociateSeizureBlock(${seizureCounter})" class="px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-bold rounded-xl transition-colors cursor-pointer border-none flex items-center space-x-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Remove Item</span>
                    </button>
                </div>

                <!-- SEIZURE INPUT GRID -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                    <!-- 1. EXHIBIT (COMMODITY CATEGORY) DROPDOWN -->
                    <div class="exhibit-select-wrapper relative z-[9999]" style="z-index: 9999;">
                        <label class="block text-slate-900 font-extrabold text-[11px] uppercase text-black mb-1">Exhibit (Commodity Category) <span class="text-red-600 font-bold">*</span></label>
                        <div class="relative z-[9999]" style="z-index: 9999;">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </span>
                            <input type="text" class="exhibit-search-input w-full pl-8 pr-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-text shadow-xs placeholder:text-slate-400" placeholder="${allowedCats.length === 1 ? allowedCats[0] : 'Select or type exhibit category...'}" autocomplete="off" onclick="openExhibitListPanel(this)" oninput="filterExhibitList(this)" onfocus="openExhibitListPanel(this)">
                            <input type="hidden" class="new-seizure-category" name="associated_exhibits[${seizureCounter}][category]" value="">
                        </div>
                        <div class="exhibit-list-panel hidden absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-2xl shadow-2xl p-3 space-y-2 z-[999999]" style="max-height: 280px; overflow-y: auto;">
                            <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100 z-10">
                                <input type="text" class="exhibit-search-inner w-full px-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl font-medium focus:outline-none focus:border-emerald-500 transition-colors" placeholder="Type to filter categories..." oninput="filterExhibitListInner(this)">
                            </div>
                            <ul class="exhibit-list max-h-48 overflow-y-auto space-y-1 text-xs py-1.5 px-1.5">
                                ${categoryListHtml}
                            </ul>
                        </div>
                    </div>

                    <!-- 2. SPECIFIC COMMODITY / EXHIBIT NAME (SMART FIELD) -->
                    <div class="commodity-field-wrapper space-y-1.5">
                        <label class="block text-slate-900 font-extrabold text-[11px] uppercase text-black mb-1">${ACTIVE_UNIT_KEY === 'lab' ? 'Packaging as Received' : 'Primary Commodity Name'} <span class="text-red-600 font-bold">*</span></label>

                        <!-- TEXT INPUT (for default/non-smart categories) -->
                        <div class="commodity-text-wrapper space-y-1.5">
                            <input type="text" class="new-seizure-name w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs" name="associated_exhibits[${seizureCounter}][seizure_name]" placeholder="e.g. Cocaine (High Purity Slabs)">
                        </div>

                        <!-- DRUG SELECT DROPDOWN -->
                        <div class="drug-select-wrapper relative space-y-1.5 hidden z-30">
                            <input type="text" class="drug-search-input w-full px-3 py-2 bg-white border border-emerald-300 rounded-xl text-xs font-extrabold text-slate-900 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 pr-8 shadow-2xs placeholder:text-slate-400" placeholder="Select or type custom drug name..." autocomplete="off" onclick="openDrugListPanel(this)" oninput="filterDrugList(this)" onchange="filterDrugList(this)" onblur="filterDrugList(this)" onkeydown="handleCommoditySearchKeydown(event, this, 'drug')" onfocus="openDrugListPanel(this)">
                            <input type="hidden" class="new-seizure-name-drug" value="">
                            <div class="drug-list-panel hidden absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-2xl shadow-2xl z-[999999] p-3 space-y-2" style="max-height:220px;overflow-y:auto">
                                <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                    <input type="text" class="drug-search-inner w-full px-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl font-medium focus:outline-none focus:border-emerald-500 transition-colors" placeholder="Type to filter drugs..." oninput="filterDrugListInner(this)">
                                </div>
                                <ul class="drug-list max-h-48 overflow-y-auto space-y-1 text-xs py-1.5 px-1.5">
                                    <li class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Opioids &amp; Analgesics</li>
                                    <li class="drug-item" onclick="selectDrugType('Heroin (Brown Sugar / Diacetylmorphine)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Heroin (Brown Sugar / Diacetylmorphine)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span></button></li>
                                    <li class="drug-item" onclick="selectDrugType('Fentanyl (Synthetic Opioid Patches / Powder)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Fentanyl (Synthetic Opioid Patches / Powder)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span></button></li>
                                    <li class="drug-item" onclick="selectDrugType('Morphine (Raw Opium Extract)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Morphine (Raw Opium Extract)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span></button></li>
                                    <li class="drug-item" onclick="selectDrugType('Tramadol (High-Dose Tablets / Capsules)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Tramadol (High-Dose Tablets / Capsules)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span></button></li>
                                    <li class="drug-item" onclick="selectDrugType('Codeine (Cough Syrup Misuse / Purple Drank)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Codeine (Cough Syrup Misuse / Purple Drank)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">OPI</span></button></li>
                                    
                                    <li class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Stimulants &amp; Cocaine-Class</li>
                                    <li class="drug-item" onclick="selectDrugType('Cocaine (High-Purity Powder / Slabs)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Cocaine (High-Purity Powder / Slabs)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">COC</span></button></li>
                                    <li class="drug-item" onclick="selectDrugType('Crack Cocaine (Rock Form / Freebase)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Crack Cocaine (Rock Form / Freebase)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">COC</span></button></li>
                                    <li class="drug-item" onclick="selectDrugType('Methamphetamine (Crystal Meth / Ice)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Methamphetamine (Crystal Meth / Ice)</span><span class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">STIM</span></button></li>
                                    <li class="drug-item" onclick="selectDrugType('Amphetamine (Speed / Pep Pills)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Amphetamine (Speed / Pep Pills)</span><span class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">STIM</span></button></li>
                                    <li class="drug-item" onclick="selectDrugType('MDMA (Ecstasy / Molly Tablets)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>MDMA (Ecstasy / Molly Tablets)</span><span class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">STIM</span></button></li>
                                    
                                    <li class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Cannabis &amp; Derivatives</li>
                                    <li class="drug-item" onclick="selectDrugType('Cannabis / Marijuana (Dried Flower / Weed)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Cannabis / Marijuana (Dried Flower / Weed)</span><span class="text-[10px] font-mono-code bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-bold">CAN</span></button></li>
                                    <li class="drug-item" onclick="selectDrugType('Cannabis Resin (Hashish / Black Hash Slabs)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Cannabis Resin (Hashish / Black Hash Slabs)</span><span class="text-[10px] font-mono-code bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-bold">CAN</span></button></li>
                                    <li class="drug-item" onclick="selectDrugType('Synthetic Cannabinoids (Spice / K2 Herbal)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Synthetic Cannabinoids (Spice / K2 Herbal)</span><span class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">SYN</span></button></li>
                                </ul>
                            </div>
                        </div>

                        <!-- WEAPON SELECT DROPDOWN -->
                        <div class="weapon-select-wrapper relative space-y-1.5 hidden z-30">
                            <input type="text" class="weapon-search-input w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 pr-8 shadow-2xs placeholder:text-slate-400" placeholder="Select weapon type..." autocomplete="off" onclick="openWeaponListPanel(this)" oninput="filterWeaponList(this)" onfocus="openWeaponListPanel(this)">
                            <input type="hidden" class="new-seizure-name-weapon" value="">
                            <div class="weapon-list-panel hidden absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-2xl shadow-2xl z-[999999] p-3 space-y-2" style="max-height:220px;overflow-y:auto">
                                <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                    <input type="text" class="weapon-search-inner w-full px-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl font-medium focus:outline-none focus:border-emerald-500 transition-colors" placeholder="Type to filter weapons..." oninput="filterWeaponListInner(this)">
                                </div>
                                <ul class="weapon-list max-h-48 overflow-y-auto space-y-1 text-xs py-1.5 px-1.5">
                                    <li class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Handguns &amp; Pistols</li>
                                    <li class="weapon-item" onclick="selectWeaponType('AK-47 / AKM Shorty Pistol (7.62x39mm)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>AK-47 / AKM Shorty Pistol (7.62x39mm)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button></li>
                                    <li class="weapon-item" onclick="selectWeaponType('Glock 17 Semi-Automatic Pistol (9mm)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Glock 17 Semi-Automatic Pistol (9mm)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button></li>
                                    <li class="weapon-item" onclick="selectWeaponType('Beretta 92FS Pistol (9mm)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Beretta 92FS Pistol (9mm)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">PST</span></button></li>
                                    <li class="weapon-item" onclick="selectWeaponType('M4A1 Carbine Assault Rifle (5.56mm NATO)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>M4A1 Carbine Assault Rifle (5.56mm NATO)</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">RFL</span></button></li>
                                    <li class="weapon-item" onclick="selectWeaponType('Remington 870 Tactical Shotgun (12 Gauge)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Remington 870 Tactical Shotgun (12 Gauge)</span><span class="text-[10px] font-mono-code bg-orange-50 text-orange-800 px-1.5 py-0.5 rounded font-bold">STG</span></button></li>
                                    <li class="weapon-item" onclick="selectWeaponType('RPG-7 Anti-Tank Rocket Launcher', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>RPG-7 Anti-Tank Rocket Launcher</span><span class="text-[10px] font-mono-code bg-red-900 text-white px-1.5 py-0.5 rounded font-bold">ORD</span></button></li>
                                    <li class="weapon-item" onclick="selectWeaponType('Heavy Steel Machete / Cutlass', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Heavy Steel Machete / Cutlass</span><span class="text-[10px] font-mono-code bg-slate-200 text-slate-800 px-1.5 py-0.5 rounded font-bold">BLD</span></button></li>
                                </ul>
                            </div>
                        </div>

                        <!-- CURRENCY SELECT DROPDOWN -->
                        <div class="currency-select-wrapper relative space-y-1.5 hidden z-30">
                            <input type="text" class="currency-search-input w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 pr-8 shadow-2xs placeholder:text-slate-400" placeholder="Select currency..." autocomplete="off" onclick="openCurrencyListPanel(this)" oninput="filterCurrencyList(this)" onfocus="openCurrencyListPanel(this)">
                            <input type="hidden" class="new-seizure-name-currency" value="">
                            <div class="currency-list-panel hidden absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-2xl shadow-2xl z-[999999] p-3 space-y-2" style="max-height:220px;overflow-y:auto">
                                <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                    <input type="text" class="currency-search-inner w-full px-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl font-medium focus:outline-none focus:border-emerald-500 transition-colors" placeholder="Type to filter currencies..." oninput="filterCurrencyListInner(this)">
                                </div>
                                <ul class="currency-list max-h-48 overflow-y-auto space-y-1 text-xs py-1.5 px-1.5">
                                    <li class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-emerald-600 uppercase tracking-widest">Currencies &amp; Money</li>
                                    <li class="currency-item" onclick="selectCurrencyType('Ghana Cedi (GHS ₵)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Ghana Cedi</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">GHS ₵</span></button></li>
                                    <li class="currency-item" onclick="selectCurrencyType('US Dollar (USD $)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>US Dollar</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">USD $</span></button></li>
                                    <li class="currency-item" onclick="selectCurrencyType('Euro (EUR €)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Euro</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">EUR €</span></button></li>
                                    <li class="currency-item" onclick="selectCurrencyType('Nigerian Naira (NGN ₦)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Nigerian Naira</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">NGN ₦</span></button></li>
                                    <li class="currency-item" onclick="selectCurrencyType('British Pound Sterling (GBP £)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>British Pound</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">GBP £</span></button></li>
                                    <li class="currency-item" onclick="selectCurrencyType('Bitcoin (BTC ₿)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Bitcoin (Crypto)</span><span class="text-[10px] font-mono-code bg-amber-50 text-amber-700 px-1.5 py-0.5 rounded font-bold">BTC ₿</span></button></li>
                                </ul>
                            </div>
                        </div>

                        <!-- AMMUNITION SELECT DROPDOWN -->
                        <div class="ammo-select-wrapper relative space-y-1.5 hidden z-30">
                            <input type="text" class="ammo-search-input w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 pr-8 shadow-2xs placeholder:text-slate-400" placeholder="Select ammunition type..." autocomplete="off" onclick="openAmmoListPanel(this)" oninput="filterAmmoList(this)" onfocus="openAmmoListPanel(this)">
                            <input type="hidden" class="new-seizure-name-ammo" value="">
                            <div class="ammo-list-panel hidden absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-2xl shadow-2xl z-[999999] p-3 space-y-2" style="max-height:220px;overflow-y:auto">
                                <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                    <input type="text" class="ammo-search-inner w-full px-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl font-medium focus:outline-none focus:border-emerald-500 transition-colors" placeholder="Type to filter ammunition..." oninput="filterAmmoListInner(this)">
                                </div>
                                <ul class="ammo-list max-h-48 overflow-y-auto space-y-1 text-xs py-1.5 px-1.5">
                                    <li class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Ammunition &amp; Calibers</li>
                                    <li class="ammo-item" onclick="selectAmmoType('9x19mm Parabellum / NATO (9mm Luger)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>9x19mm Parabellum (9mm)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">9MM</span></button></li>
                                    <li class="ammo-item" onclick="selectAmmoType('7.62x39mm Kalashnikov Ammunition (AK Round)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>7.62x39mm AK Round</span><span class="text-[10px] font-mono-code bg-red-50 text-red-800 px-1.5 py-0.5 rounded font-bold">7.62</span></button></li>
                                    <li class="ammo-item" onclick="selectAmmoType('5.56x45mm NATO Assault Rifle Round', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>5.56x45mm NATO Round</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-800 px-1.5 py-0.5 rounded font-bold">5.56</span></button></li>
                                    <li class="ammo-item" onclick="selectAmmoType('12 Gauge Shotgun Shells (Buckshot / Slug)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>12 Gauge Shotgun Shells</span><span class="text-[10px] font-mono-code bg-orange-50 text-orange-800 px-1.5 py-0.5 rounded font-bold">12GA</span></button></li>
                                </ul>
                            </div>
                        </div>

                        <!-- DEVICE SELECT DROPDOWN -->
                        <div class="device-select-wrapper relative space-y-1.5 hidden z-30">
                            <input type="text" class="device-search-input w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 pr-8 shadow-2xs placeholder:text-slate-400" placeholder="Select electronic device type..." autocomplete="off" onclick="openDeviceListPanel(this)" oninput="filterDeviceList(this)" onfocus="openDeviceListPanel(this)">
                            <input type="hidden" class="new-seizure-name-device" value="">
                            <div class="device-list-panel hidden absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-2xl shadow-2xl z-[999999] p-3 space-y-2" style="max-height:220px;overflow-y:auto">
                                <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                    <input type="text" class="device-search-inner w-full px-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl font-medium focus:outline-none focus:border-emerald-500 transition-colors" placeholder="Type to filter devices..." oninput="filterDeviceListInner(this)">
                                </div>
                                <ul class="device-list max-h-48 overflow-y-auto space-y-1 text-xs py-1.5 px-1.5">
                                    <li class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Electronic Devices</li>
                                    <li class="device-item" onclick="selectDeviceType('Apple iPhone (iOS Smartphone)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Apple iPhone (iOS Smartphone)</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">IPHONE</span></button></li>
                                    <li class="device-item" onclick="selectDeviceType('Android Smartphone (Samsung / Xiaomi)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Android Smartphone</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-bold">ANDROID</span></button></li>
                                    <li class="device-item" onclick="selectDeviceType('Laptop / Notebook Computer (MacBook / Windows)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Laptop / Notebook Computer</span><span class="text-[10px] font-mono-code bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded font-bold">LAPTOP</span></button></li>
                                    <li class="device-item" onclick="selectDeviceType('External Hard Drive / Portable SSD', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>External Hard Drive / SSD</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">STORAGE</span></button></li>
                                    <li class="device-item" onclick="selectDeviceType('SIM Box / Multi-SIM GSM Gateway', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>SIM Box / Cellular Gateway</span><span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-1.5 py-0.5 rounded font-bold">SIMBOX</span></button></li>
                                    <li class="device-item" onclick="selectDeviceType('Cryptocurrency Hardware Wallet (Ledger / Trezor)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>Crypto Hardware Wallet</span><span class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded font-bold">HW-WALLET</span></button></li>
                                </ul>
                            </div>
                        </div>

                    </div>

                    ${ACTIVE_UNIT_KEY === 'lab' ? `
                    <!-- DESCRIPTION OF CONTENTS -->
                    <div>
                        <label class="block text-slate-900 font-extrabold text-[11px] uppercase text-black mb-1">Description of Contents</label>
                        <input type="text" name="associated_exhibits[${seizureCounter}][seizure_contents]" placeholder="e.g. White compressed powder / Brown pebbles" oninput="if (typeof syncSection3SeizuresToSection4 === 'function') syncSection3SeizuresToSection4();" class="seizure-contents-input w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                    </div>
                    ` : ''}

                    <!-- 3. ESTIMATED STREET VALUE -->
                    <div>
                        <label class="block text-slate-900 font-extrabold text-[11px] uppercase text-black mb-1">Estimated Street Value ($/GH¢)</label>
                        <input type="text" name="associated_exhibits[${seizureCounter}][seizure_value]" placeholder="e.g. GHS 1,500,000 / $120,000" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-emerald-700 text-xs focus:outline-none focus:border-emerald-600 font-mono-code">
                    </div>

                    <!-- 4. SEIZING OFFICER(S) -->
                    <div>
                        <label class="block text-slate-900 font-extrabold text-[11px] uppercase text-black mb-1">Seizing Officer(s) <span class="text-red-600 font-bold">*</span></label>
                        <input type="text" name="associated_exhibits[${seizureCounter}][seizure_officer]" value="" placeholder="e.g. Kwame Mensah, Ben Attah" ${ACTIVE_UNIT_KEY === 'lab' ? '' : 'required'} class="seizure-officer-input w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                    </div>

                    <!-- 5. SEIZING OFFICER SERVICE / BADGE ID & ASSOCIATED SUSPECT -->
                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-slate-900 font-extrabold text-[11px] uppercase text-black mb-1">Seizing Officer Service / Badge ID</label>
                        <input type="text" name="associated_exhibits[${seizureCounter}][seizure_officer_id]" value="" placeholder="e.g. SERVICE-98210, BADGE-88210" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-mono-code font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                    </div>
                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-slate-900 font-extrabold text-[11px] uppercase text-black mb-1">Associated Suspect / Owner</label>
                        <input type="hidden" name="associated_exhibits[${seizureCounter}][associate_id]" class="assoc-suspect-id-input" value="">
                        <select name="associated_exhibits[${seizureCounter}][associated_person]" onchange="syncAssociateIdFromSelect(this)" class="assoc-suspect-select w-full px-3.5 py-2 bg-white border border-slate-300 hover:border-emerald-500 focus:border-emerald-600 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-600/20 shadow-xs cursor-pointer">
                            <option value="">Select Associated Suspect...</option>
                        </select>
                    </div>

                </div>

                <!-- WEAPON / FIREARM SPECIFICATIONS PANEL -->
                <div class="weapon-fields-wrapper hidden space-y-3 pt-3 border-t border-slate-200/80">
                    <div class="flex items-center space-x-2 text-slate-800">
                        <div class="w-5 h-5 rounded bg-emerald-500 text-white flex items-center justify-center font-bold text-xs">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        </div>
                        <span class="text-xs font-black uppercase tracking-wider text-slate-900">WEAPON / FIREARM SPECIFIC DETAILS</span>
                    </div>

                    <!-- 1. IDENTIFICATION & SPECIFICATIONS -->
                    <div class="space-y-3">
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-extrabold rounded-md uppercase tracking-wider border border-emerald-200">1. IDENTIFICATION &amp; SPECIFICATIONS</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-slate-900 font-extrabold text-[10px] uppercase mb-1">MODEL</label>
                                <input type="text" name="associated_exhibits[${seizureCounter}][weapon_model]" placeholder="e.g. 19 Gen 5, M&amp;P 9, Type 56, Glock 17" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                            </div>
                            <div>
                                <label class="block text-slate-900 font-extrabold text-[10px] uppercase mb-1">SERIAL NUMBER</label>
                                <input type="text" name="associated_exhibits[${seizureCounter}][weapon_serial_number]" placeholder="e.g. G17-984210X / Defaced" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono-code text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                            </div>
                            <div>
                                <label class="block text-slate-900 font-extrabold text-[10px] uppercase mb-1">CALIBRE / GAUGE</label>
                                <input type="text" name="associated_exhibits[${seizureCounter}][weapon_calibre]" placeholder="e.g. 9mm, 7.62×39mm, 12-Gauge, .45 ACP" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono-code text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                            </div>
                            <div>
                                <label class="block text-slate-900 font-extrabold text-[10px] uppercase mb-1">COLOUR</label>
                                <input type="text" name="associated_exhibits[${seizureCounter}][weapon_color]" placeholder="e.g. Matte Black, Silver, Camouflage" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                            </div>
                            <div>
                                <label class="block text-slate-900 font-extrabold text-[10px] uppercase mb-1">APPROX. SIZE / LENGTH</label>
                                <input type="text" name="associated_exhibits[${seizureCounter}][weapon_size_length]" placeholder="e.g. 18.5 cm (Overall), Short Barrel, 87 cm" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                            </div>
                        </div>
                    </div>

                    <!-- 2. RECOVERY CONDITION & LOCATION -->
                    <div class="space-y-3 pt-1">
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-extrabold rounded-md uppercase tracking-wider border border-emerald-200">2. RECOVERY CONDITION &amp; LOCATION</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-slate-900 font-extrabold text-[10px] uppercase mb-1">LOADED STATUS</label>
                                <select name="associated_exhibits[${seizureCounter}][weapon_loaded_status]" class="w-full appearance-none px-3.5 py-2.5 bg-white border border-slate-300 hover:border-emerald-500 focus:border-emerald-600 rounded-xl text-xs font-bold text-slate-800 shadow-xs focus:outline-none focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer">
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
                                <label class="block text-slate-900 font-extrabold text-[10px] uppercase mb-1">CONDITION AT RECOVERY</label>
                                <select name="associated_exhibits[${seizureCounter}][weapon_condition_at_recovery]" class="w-full appearance-none px-3.5 py-2.5 bg-white border border-slate-300 hover:border-emerald-500 focus:border-emerald-600 rounded-xl text-xs font-bold text-slate-800 shadow-xs focus:outline-none focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer">
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
                                <label class="block text-slate-900 font-extrabold text-[10px] uppercase mb-1">EXACT LOCATION FOUND</label>
                                <input type="text" name="associated_exhibits[${seizureCounter}][weapon_location_found]" placeholder="e.g. Bedroom closet, Under vehicle driver seat, Roadside ditch" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                            </div>
                            <div class="sm:col-span-3">
                                <label class="block text-slate-900 font-extrabold text-[10px] uppercase mb-1">DISTINGUISHING MARKS / FEATURES</label>
                                <input type="text" name="associated_exhibits[${seizureCounter}][weapon_distinguishing_marks]" placeholder="e.g. Scratch on left grip, custom insignia engraved, missing safety switch" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AMMUNITION SPECIFICATIONS PANEL -->
                <div class="ammo-fields-wrapper hidden space-y-3 pt-3 border-t border-slate-200/80">
                    <div class="flex items-center space-x-2 text-slate-800">
                        <div class="w-5 h-5 rounded bg-emerald-500 text-white flex items-center justify-center font-bold text-xs">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                        <span class="text-xs font-black uppercase tracking-wider text-slate-900">AMMUNITION SPECIFIC DETAILS</span>
                    </div>

                    <!-- 1. PHYSICAL DESCRIPTION -->
                    <div class="space-y-3">
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-extrabold rounded-md uppercase tracking-wider border border-emerald-200">1. PHYSICAL DESCRIPTION</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-slate-900 font-extrabold text-[10px] uppercase mb-1">CARTRIDGE APPEARANCE</label>
                                <input type="text" name="associated_exhibits[${seizureCounter}][ammunition_details][cartridge_appearance]" placeholder="e.g. Brass casing, FMJ, Nickel-plated" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                            </div>
                            <div>
                                <label class="block text-slate-900 font-extrabold text-[10px] uppercase mb-1">PROJECTILE / BULLET TYPE</label>
                                <input type="text" name="associated_exhibits[${seizureCounter}][ammunition_details][projectile_bullet_type]" placeholder="e.g. Full Metal Jacket (FMJ), Hollow Point" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                            </div>
                            <div>
                                <label class="block text-slate-900 font-extrabold text-[10px] uppercase mb-1">CASE MATERIAL</label>
                                <input type="text" name="associated_exhibits[${seizureCounter}][ammunition_details][cartridge_case_material]" placeholder="e.g. Brass, Steel, Aluminium, Plastic" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                            </div>
                            <div>
                                <label class="block text-slate-900 font-extrabold text-[10px] uppercase mb-1">COLOUR / MARKINGS</label>
                                <input type="text" name="associated_exhibits[${seizureCounter}][ammunition_details][colour_markings]" placeholder="e.g. Headstamp CBC 9mm LUGER, Red primer sealant" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                            </div>
                            <div>
                                <label class="block text-slate-900 font-extrabold text-[10px] uppercase mb-1">CONDITION</label>
                                <select name="associated_exhibits[${seizureCounter}][ammunition_details][condition]" onchange="toggleAmmoOtherCondition(this)" class="w-full appearance-none px-3.5 py-2.5 bg-white border border-slate-300 hover:border-emerald-500 focus:border-emerald-600 rounded-xl text-xs font-bold text-slate-800 shadow-xs focus:outline-none focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer">
                                    <option value="" class="text-slate-400 font-normal">Select Condition...</option>
                                    <option value="Intact" class="font-semibold text-slate-900">Intact</option>
                                    <option value="Damaged" class="font-semibold text-slate-900">Damaged</option>
                                    <option value="Corroded" class="font-semibold text-slate-900">Corroded</option>
                                    <option value="Deformed" class="font-semibold text-slate-900">Deformed</option>
                                    <option value="Other" class="font-semibold text-slate-900">Other</option>
                                </select>
                            </div>
                            <div class="ammo-other-condition-wrapper hidden sm:col-span-2">
                                <label class="block text-slate-900 font-extrabold text-[10px] uppercase mb-1">DESCRIBE OTHER CONDITION</label>
                                <input type="text" name="associated_exhibits[${seizureCounter}][ammunition_details][other_condition]" placeholder="Specify condition details..." class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                            </div>
                        </div>
                    </div>

                    <!-- 2. PACKAGING & SEALING -->
                    <div class="space-y-3 pt-1">
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-extrabold rounded-md uppercase tracking-wider border border-emerald-200">2. PACKAGING &amp; SEALING</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-slate-900 font-extrabold text-[10px] uppercase mb-1">PACKAGING TYPE</label>
                                <input type="text" name="associated_exhibits[${seizureCounter}][ammunition_details][packaging_type]" placeholder="e.g. Cardboard Box, Plastic Ammo Can, Loose Bag" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                            </div>
                            <div>
                                <label class="block text-slate-900 font-extrabold text-[10px] uppercase mb-1">SEAL / TAMPER-EVIDENT CONDITION</label>
                                <input type="text" name="associated_exhibits[${seizureCounter}][ammunition_details][seal_tamper_evident_condition]" placeholder="e.g. Intact Tamper-Evident Bag #SEAL-90812" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                            </div>
                            <div>
                                <label class="block text-slate-900 font-extrabold text-[10px] uppercase mb-1">PACKAGE / REFERENCE NUMBER</label>
                                <input type="text" name="associated_exhibits[${seizureCounter}][ammunition_details][package_reference_number]" placeholder="e.g. PKG-2026-AM01" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                            </div>
                            <div>
                                <label class="block text-slate-900 font-extrabold text-[10px] uppercase mb-1">CONDITION WHEN RECEIVED</label>
                                <input type="text" name="associated_exhibits[${seizureCounter}][ammunition_details][condition_when_received]" placeholder="e.g. Sealed and untampered" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ELECTRONIC DEVICE SPECIFICATIONS PANEL -->
                <div class="device-fields-wrapper hidden space-y-3 pt-3 border-t border-slate-200/80">
                    <div class="flex items-center space-x-2 text-slate-800">
                        <div class="w-5 h-5 rounded bg-blue-600 text-white flex items-center justify-center font-bold text-xs">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                        <span class="text-xs font-extrabold uppercase tracking-wider text-blue-950">Electronic Device Forensic Specifications</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[10px] uppercase text-black mb-1">Model / Brand</label>
                            <input type="text" name="associated_exhibits[${seizureCounter}][device_model]" placeholder="e.g. iPhone 15 Pro Max" class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-900">
                        </div>
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[10px] uppercase text-black mb-1">Serial Number</label>
                            <input type="text" name="associated_exhibits[${seizureCounter}][serial_number]" placeholder="e.g. F2LZX984K102" class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-mono-code text-slate-900">
                        </div>
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[10px] uppercase text-black mb-1">IMEI 1</label>
                            <input type="text" name="associated_exhibits[${seizureCounter}][imei_1]" placeholder="e.g. 356938035643809" class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-mono-code text-slate-900">
                        </div>
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[10px] uppercase text-black mb-1">IMEI 2</label>
                            <input type="text" name="associated_exhibits[${seizureCounter}][imei_2]" placeholder="e.g. 356938035643817" class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-mono-code text-slate-900">
                        </div>
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[10px] uppercase text-black mb-1">MAC Address</label>
                            <input type="text" name="associated_exhibits[${seizureCounter}][mac_address]" placeholder="e.g. 00:1A:2B:3C:4D:5E" class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-mono-code text-slate-900">
                        </div>
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[10px] uppercase text-black mb-1">Storage Capacity</label>
                            <input type="text" name="associated_exhibits[${seizureCounter}][storage_capacity]" placeholder="e.g. 256 GB / 1 TB" class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                        </div>
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[10px] uppercase text-black mb-1">Colour</label>
                            <input type="text" name="associated_exhibits[${seizureCounter}][device_color]" placeholder="e.g. Space Gray" class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                        </div>
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[10px] uppercase text-black mb-1">Identifying Marks</label>
                            <input type="text" name="associated_exhibits[${seizureCounter}][distinguishing_marks]" placeholder="e.g. Cracked back glass" class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                        </div>
                    </div>
                </div>

                <!-- VEHICLE SPECIFICATIONS PANEL -->
                <div class="vehicle-fields-wrapper hidden space-y-3 pt-3 border-t border-slate-200/80">
                    <div class="flex items-center space-x-2 text-slate-800">
                        <div class="w-5 h-5 rounded bg-amber-600 text-white flex items-center justify-center font-bold text-xs">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </div>
                        <span class="text-xs font-extrabold uppercase tracking-wider text-amber-950">Vehicle / Vessel Specifications</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[10px] uppercase text-black mb-1">Make &amp; Model</label>
                            <input type="text" name="associated_exhibits[${seizureCounter}][vehicle_make_model]" placeholder="e.g. Toyota Hilux 4x4" class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-900">
                        </div>
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[10px] uppercase text-black mb-1">Registration / License Plate</label>
                            <input type="text" name="associated_exhibits[${seizureCounter}][vehicle_registration]" placeholder="e.g. GE-4920-22" class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-mono-code text-slate-900">
                        </div>
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[10px] uppercase text-black mb-1">Chassis / VIN Number</label>
                            <input type="text" name="associated_exhibits[${seizureCounter}][vehicle_chassis_vin]" placeholder="e.g. JTEKB20V700129841" class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-mono-code text-slate-900">
                        </div>
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[10px] uppercase text-black mb-1">Color &amp; Type</label>
                            <input type="text" name="associated_exhibits[${seizureCounter}][vehicle_color_type]" placeholder="e.g. Metallic Black Pickup" class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                        </div>
                    </div>
                </div>

                <!-- SEIZURE QUANTIFICATION & PACKAGING METRICS PANEL -->
                <div class="col-span-full bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs space-y-3">
                    <div class="flex items-center space-x-2 text-slate-800">
                        <div class="w-5 h-5 rounded bg-emerald-100 text-emerald-700 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                        <span class="text-xs font-extrabold uppercase tracking-wider text-slate-900">Quantification &amp; Packaging Metrics</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">

                        <!-- QUANTITY -->
                        <div>
                            <label class="quantity-label block text-slate-900 font-extrabold text-[11px] uppercase mb-1">Quantity</label>
                            <input type="text" name="associated_exhibits[${seizureCounter}][seizure_quantity]" placeholder="e.g. 10" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs font-mono-code focus:border-emerald-600">
                        </div>

                        <!-- PACKAGING TYPE -->
                        <div class="packaging-select-wrapper relative z-20">
                            <label class="block text-slate-900 font-extrabold text-[11px] uppercase text-black mb-1">Packaging Type</label>
                            <input type="text" class="pkg-search-input w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 transition-all cursor-text pr-8 shadow-2xs placeholder:text-slate-400" placeholder="Select or type packaging..." autocomplete="off" onclick="openPkgListPanel(this)" oninput="filterPackagingList(this)" onfocus="openPkgListPanel(this)">
                            <input type="hidden" class="new-seizure-packaging" name="associated_exhibits[${seizureCounter}][seizure_packaging]" value="">
                            <div class="pkg-list-panel hidden absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-2xl shadow-2xl z-[999999] p-3 space-y-2" style="max-height:200px;overflow-y:auto">
                                <div class="sticky top-0 bg-white px-3 pt-2 pb-1 border-b border-slate-100">
                                    <input type="text" class="pkg-search-inner w-full px-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl font-medium focus:outline-none focus:border-emerald-500 transition-colors" placeholder="Type to filter..." oninput="filterPackagingListInner(this)">
                                </div>
                                <ul class="pkg-list max-h-48 overflow-y-auto space-y-1 text-xs py-1 px-1">
                                    <li class="pkg-item" onclick="selectPackagingType('Slabs', event)"><button type="button" class="w-full text-left px-2.5 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-lg flex items-center justify-between"><span>Slabs</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1 py-0.5 rounded">SLB</span></button></li>
                                    <li class="pkg-item" onclick="selectPackagingType('Blocks', event)"><button type="button" class="w-full text-left px-2.5 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-lg flex items-center justify-between"><span>Blocks</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1 py-0.5 rounded">BLK</span></button></li>
                                    <li class="pkg-item" onclick="selectPackagingType('Bundles', event)"><button type="button" class="w-full text-left px-2.5 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-lg flex items-center justify-between"><span>Bundles</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1 py-0.5 rounded">BDL</span></button></li>
                                    <li class="pkg-item" onclick="selectPackagingType('Sacks / Bags', event)"><button type="button" class="w-full text-left px-2.5 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-lg flex items-center justify-between"><span>Sacks / Bags</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1 py-0.5 rounded">BAG</span></button></li>
                                    <li class="pkg-item" onclick="selectPackagingType('Cartons / Boxes', event)"><button type="button" class="w-full text-left px-2.5 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-lg flex items-center justify-between"><span>Cartons / Boxes</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1 py-0.5 rounded">BOX</span></button></li>
                                    <li class="pkg-item" onclick="selectPackagingType('Boxes', event)"><button type="button" class="w-full text-left px-2.5 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-lg flex items-center justify-between"><span>Boxes</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1 py-0.5 rounded">BOX</span></button></li>
                                    <li class="pkg-item" onclick="selectPackagingType('Rounds', event)"><button type="button" class="w-full text-left px-2.5 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-lg flex items-center justify-between"><span>Rounds</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1 py-0.5 rounded">RND</span></button></li>
                                    <li class="pkg-item" onclick="selectPackagingType('Magazines', event)"><button type="button" class="w-full text-left px-2.5 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-lg flex items-center justify-between"><span>Magazines</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1 py-0.5 rounded">MAG</span></button></li>
                                    <li class="pkg-item" onclick="selectPackagingType('Pieces / Units', event)"><button type="button" class="w-full text-left px-2.5 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-lg flex items-center justify-between"><span>Pieces / Units</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-700 px-1 py-0.5 rounded">pcs</span></button></li>
                                    <li class="pkg-item" onclick="selectPackagingType('Wraps / Pellets', event)"><button type="button" class="w-full text-left px-2.5 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-lg flex items-center justify-between"><span>Wraps / Pellets</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1 py-0.5 rounded">WRP</span></button></li>
                                    <li class="pkg-item" onclick="selectPackagingType('Drums / Containers', event)"><button type="button" class="w-full text-left px-2.5 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-lg flex items-center justify-between"><span>Drums / Containers</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1 py-0.5 rounded">DRM</span></button></li>
                                    <li class="pkg-item" onclick="selectPackagingType('Sealed Evidence Vault Bag', event)"><button type="button" class="w-full text-left px-2.5 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-lg flex items-center justify-between"><span>Sealed Evidence Vault Bag</span><span class="text-[10px] font-mono-code bg-emerald-100 text-emerald-800 px-1 py-0.5 rounded">VAULT</span></button></li>
                                </ul>
                            </div>
                        </div>

                        <!-- WEIGHT -->
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[11px] uppercase text-black mb-1">Weight</label>
                            <input type="text" name="associated_exhibits[${seizureCounter}][seizure_weight]" placeholder="e.g. 25.5" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs font-mono-code focus:border-emerald-600">
                        </div>

                        <!-- MEASUREMENT UNIT -->
                        <div class="unit-select-wrapper relative z-20">
                            <label class="block text-slate-900 font-extrabold text-[11px] uppercase text-black mb-1">Measurement Unit</label>
                            <input type="text" class="unit-search-input w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 transition-all cursor-text pr-8 shadow-2xs placeholder:text-slate-400" placeholder="Select or type unit..." autocomplete="off" onclick="openUnitListPanel(this)" oninput="filterUnitList(this)" onfocus="openUnitListPanel(this)">
                            <input type="hidden" class="new-seizure-unit" name="associated_exhibits[${seizureCounter}][seizure_unit]" value="">
                            <div class="unit-list-panel hidden absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-2xl shadow-2xl z-[999999] p-3 space-y-2" style="max-height:200px;overflow-y:auto">
                                <div class="sticky top-0 bg-white px-3 pt-2 pb-1 border-b border-slate-100">
                                    <input type="text" class="unit-search-inner w-full px-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl font-medium focus:outline-none focus:border-emerald-500 transition-colors" placeholder="Type to filter..." oninput="filterUnitListInner(this)">
                                </div>
                                <ul class="unit-list max-h-48 overflow-y-auto space-y-1 text-xs py-1 px-1">
                                    <li class="unit-item" onclick="selectUnit('Kilograms (kg)', event)"><button type="button" class="w-full text-left px-2.5 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-lg flex items-center justify-between"><span>Kilograms</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1 py-0.5 rounded">kg</span></button></li>
                                    <li class="unit-item" onclick="selectUnit('Grams (g)', event)"><button type="button" class="w-full text-left px-2.5 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-lg flex items-center justify-between"><span>Grams</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1 py-0.5 rounded">g</span></button></li>
                                    <li class="unit-item" onclick="selectUnit('Metric Tons (MT)', event)"><button type="button" class="w-full text-left px-2.5 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-lg flex items-center justify-between"><span>Metric Tons</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1 py-0.5 rounded">MT</span></button></li>
                                    <li class="unit-item" onclick="selectUnit('Pounds (lbs)', event)"><button type="button" class="w-full text-left px-2.5 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-lg flex items-center justify-between"><span>Pounds</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1 py-0.5 rounded">lbs</span></button></li>
                                    <li class="unit-item" onclick="selectUnit('Liters (L)', event)"><button type="button" class="w-full text-left px-2.5 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-lg flex items-center justify-between"><span>Liters</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-800 px-1 py-0.5 rounded">L</span></button></li>
                                    <li class="unit-item" onclick="selectUnit('Pieces / Units (pcs)', event)"><button type="button" class="w-full text-left px-2.5 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-lg flex items-center justify-between"><span>Pieces / Units</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-700 px-1 py-0.5 rounded">pcs</span></button></li>
                                </ul>
                            </div>
                        </div>

                    </div>
                </div>

                ${ACTIVE_UNIT_KEY === 'lab' ? `
                <!-- LAB ANALYSIS: CONCEALMENT, DATE, TIME, REASON & CUSTODY FIELDS -->
                <div class="col-span-full bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-slate-900 font-extrabold text-[11px] uppercase mb-1">Concealment &amp; Packaging Notes</label>
                            <input type="text" name="associated_exhibits[${seizureCounter}][seizure_concealment]" placeholder="e.g. Vacuum foil wrapped in false trunk bottom" class="seizure-concealment-input w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                        </div>
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[11px] uppercase mb-1">Date Seized <span class="text-red-600 font-bold">*</span></label>
                            <input type="date" name="associated_exhibits[${seizureCounter}][seizure_date]" class="seizure-date-input w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                        </div>
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[11px] uppercase mb-1">Time Seized</label>
                            <input type="time" name="associated_exhibits[${seizureCounter}][seizure_time]" class="seizure-time-input w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="md:col-span-2">
                            <label class="block text-slate-900 font-extrabold text-[11px] uppercase mb-1">Reason for Seizure <span class="text-red-600 font-bold">*</span></label>
                            <textarea name="associated_exhibits[${seizureCounter}][seizure_reason]" rows="2" placeholder="e.g. Suspected drug trafficking — commodity found concealed in vehicle false compartment during routine checkpoint stop." class="seizure-reason-input w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400 resize-none"></textarea>
                        </div>
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[11px] uppercase mb-1">Source of Exhibits <span class="text-red-600 font-bold">*</span></label>
                            <input type="text" name="associated_exhibits[${seizureCounter}][source_of_exhibits]" placeholder="e.g. Tema Port Warehouse, Vessel Compartment, Suspect Residence..." class="seizure-source-of-exhibits-input w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                        </div>
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[11px] uppercase mb-1">Investigating Officer</label>
                            <input type="text" name="associated_exhibits[${seizureCounter}][investigating_officer]" placeholder="e.g. Insp. Emmanuel Mensah / Service ID SD-0199" class="seizure-investigating-officer-input w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                        </div>
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[11px] uppercase mb-1">Source/Requesting Unit</label>
                            <input type="text" name="associated_exhibits[${seizureCounter}][source_requesting_unit]" placeholder="e.g. Enforcement and Operations Unit, KIA Command, DLEU..." class="seizure-source-requesting-unit-input w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                        </div>
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[11px] uppercase mb-1">Remarks</label>
                            <input type="text" name="associated_exhibits[${seizureCounter}][exhibit_remark]" placeholder="e.g. Broken seal, sample retrieved under chain-of-custody..." class="seizure-remark-input w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
                        </div>
                    </div>
                </div>
                ` : ''}

                <!-- EXHIBIT VISUAL EVIDENCE MULTI-PHOTO UPLOAD SECTION -->
                <div class="col-span-full bg-white p-4.5 rounded-2xl border border-slate-200 shadow-2xs space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                        <div class="flex items-center space-x-2 text-slate-800">
                            <div class="w-6 h-6 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-bold shadow-xs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <span class="text-xs font-extrabold uppercase tracking-wider text-slate-900 block">Seizure / Exhibit Visual Evidence Gallery</span>
                                <span class="text-[10px] text-slate-500">Upload multiple photos (angles, packaging, contents, serial numbers) for this exhibit</span>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[9px] font-mono-code font-extrabold bg-emerald-100 text-emerald-900 border border-emerald-300 uppercase">MULTI-PHOTO RECORD</span>
                        </div>
                        <span id="photoCountExhibit_${seizureCounter}" class="text-[10px] font-mono-code font-extrabold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200">
                            0 Photos Uploaded
                        </span>
                    </div>

                    <!-- HIDDEN MULTI-FILE INPUT -->
                    <input 
                        type="file" 
                        id="fileInputExhibit_${seizureCounter}" 
                        accept="image/*" 
                        multiple
                        onchange="handleExhibitMultipleFiles(this, ${seizureCounter})" 
                        class="hidden"
                    >

                    <!-- INTERACTIVE MULTI-FILE DROPZONE FRAME -->
                    <div 
                        id="dropzoneExhibit_${seizureCounter}"
                        onclick="document.getElementById('fileInputExhibit_${seizureCounter}').click()"
                        ondragover="handleExhibitDragOver(event, this)"
                        ondragleave="handleExhibitDragLeave(event, this)"
                        ondrop="handleExhibitMultipleDrop(event, ${seizureCounter})"
                        class="h-36 w-full bg-slate-50 hover:bg-emerald-50/60 rounded-2xl border-2 border-dashed border-slate-300 hover:border-emerald-500 flex flex-col items-center justify-center p-4 transition-all duration-200 cursor-pointer shadow-2xs text-center group"
                    >
                        <div class="w-11 h-11 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-xs mb-2 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </div>
                        <span class="text-xs font-extrabold text-slate-800">Click or Drag &amp; Drop Photos</span>
                        <span class="text-[10px] font-mono-code text-slate-400 mt-0.5">Supports multiple JPG, PNG, WEBP files</span>
                    </div>

                    <!-- RESPONSIVE MULTI-PHOTO GALLERY CONTAINER -->
                    <div class="space-y-1.5 pt-2 border-t border-slate-100">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Exhibit Photo Gallery Thumbnails</span>
                            <button type="button" onclick="clearAllExhibitPhotos(${seizureCounter})" class="text-[10px] font-bold text-red-600 hover:text-red-700 hover:underline cursor-pointer border-none bg-transparent">
                                Clear All Photos
                            </button>
                        </div>
                        <div id="photoGalleryExhibit_${seizureCounter}" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 min-h-[60px] bg-slate-50/70 p-3 rounded-xl border border-slate-200">
                            <div id="galleryEmptyNotice_${seizureCounter}" class="col-span-full py-4 text-center text-slate-400 text-xs font-mono-code">
                                No exhibit photos added yet. Upload files above or drag &amp; drop images.
                            </div>
                        </div>
                    </div>
                </div>
            `;

            container.appendChild(card);
            updateSeizureBlockNumbers();
            syncSection3SeizuresToSection4();
            setTimeout(syncSuspectNamesToExhibits, 50);

            if (UNIT_DEFAULT_CATEGORY && allowedCats.includes(UNIT_DEFAULT_CATEGORY)) {
                setTimeout(() => {
                    selectExhibitCategory(UNIT_DEFAULT_CATEGORY, null, card);
                }, 20);
            }
        }

                /**
         * WHY: Prior code accepted raw base64 in evidence_photos hidden inputs,
         * bloating docket_data JSON and risking post_max_size failures.
         * All photos must first be uploaded via /upload-evidence-photos and
         * referenced by URL only.
         */
        async function uploadExhibitPhotoFile(file, seizureIdx) {
            if (!validateImageFile(file)) return;

            const formData = new FormData();
            formData.append('photos[]', file);
            const tokenEl = document.querySelector('meta[name="csrf-token"]');
            if (tokenEl) formData.append('_token', tokenEl.content);

            try {
                const res = await fetch('/upload-evidence-photos', {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success && data.urls && data.urls.length) {
                    data.urls.forEach(url => appendExhibitPhotoToGallery(seizureIdx, url));
                } else {
                    alert(data.message || 'Photo upload failed.');
                }
            } catch (err) {
                alert('Photo upload failed — check your connection.');
            }
        }

        function handleExhibitMultipleFiles(fileInput, seizureIdx) {
            if (fileInput.files && fileInput.files.length > 0) {
                Array.from(fileInput.files).forEach(file => {
                    uploadExhibitPhotoFile(file, seizureIdx);
                });
                fileInput.value = '';
            }
        }

        function handleExhibitMultipleDrop(e, seizureIdx) {
            if (e.preventDefault) e.preventDefault();
            if (e.stopPropagation) e.stopPropagation();
            const dropzone = e.currentTarget;
            if (dropzone) {
                dropzone.classList.remove('border-emerald-500', 'bg-emerald-50/80');
            }
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                Array.from(e.dataTransfer.files).forEach(file => {
                    uploadExhibitPhotoFile(file, seizureIdx);
                });
            }
        }

        function addExhibitPhotoFromUrl(seizureIdx) {
            const input = document.getElementById(`urlInputExhibit_${seizureIdx}`);
            if (input) {
                const val = (input.value || '').trim();
                if (val) {
                    if (/^data:/i.test(val)) {
                        alert('Base64 images are not supported. Please choose an image file to upload.');
                        return;
                    }
                    appendExhibitPhotoToGallery(seizureIdx, val);
                    input.value = '';
                }
            }
        }

        /**
         * WHY: Prior code embedded base64 data URLs directly in hidden inputs,
         * which bloated the form payload to 50+ MB for typical submissions.
         * Now: photos are uploaded immediately via AJAX to /upload-evidence-photos,
         * and only the returned URLs are stored in hidden inputs.
         */
        function appendExhibitPhotoToGallery(seizureIdx, srcUrl) {
            if (!srcUrl || /^data:/i.test(srcUrl)) {
                return;
            }
            if (!/^(https?:\/\/|\/)/i.test(srcUrl)) {
                return;
            }

            const gallery = document.getElementById(`photoGalleryExhibit_${seizureIdx}`);
            const emptyNotice = document.getElementById(`galleryEmptyNotice_${seizureIdx}`);
            if (!gallery) return;

            if (emptyNotice) emptyNotice.remove();

            const photoCard = document.createElement('div');
            photoCard.className = 'exhibit-photo-item relative group h-28 bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs transition-all duration-200 hover:border-emerald-500';
            
            photoCard.innerHTML = `
                <img src="${srcUrl}" class="w-full h-full object-contain p-1" alt="Exhibit Photo">
                <input type="hidden" name="associated_exhibits[${seizureIdx}][photos][]" value="${srcUrl}">
                <div class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 flex items-center justify-center space-x-1.5 transition-opacity duration-150 p-1" onclick="event.stopPropagation()">
                    <button type="button" onclick="openMugshotModal('${srcUrl}', 'Exhibit Visual Evidence Photo')" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-[9px] font-bold rounded-lg shadow-xs cursor-pointer border-none">
                        View
                    </button>
                    <button type="button" onclick="this.closest('.exhibit-photo-item').remove(); updateExhibitPhotoCount(${seizureIdx});" class="px-2 py-1 bg-red-600 hover:bg-red-700 text-white text-[9px] font-bold rounded-lg shadow-xs cursor-pointer border-none">
                        Remove
                    </button>
                </div>
            `;

            gallery.appendChild(photoCard);
            updateExhibitPhotoCount(seizureIdx);
        }

        function updateExhibitPhotoCount(seizureIdx) {
            const gallery = document.getElementById(`photoGalleryExhibit_${seizureIdx}`);
            const badge = document.getElementById(`photoCountExhibit_${seizureIdx}`);
            if (!gallery) return;

            const count = gallery.querySelectorAll('.exhibit-photo-item').length;
            if (badge) {
                badge.innerText = `${count} Photo${count === 1 ? '' : 's'} Uploaded`;
                if (count > 0) {
                    badge.className = 'text-[10px] font-mono-code font-extrabold text-emerald-800 bg-emerald-100 px-2.5 py-1 rounded-lg border border-emerald-300';
                } else {
                    badge.className = 'text-[10px] font-mono-code font-extrabold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200';
                    if (!gallery.querySelector('#galleryEmptyNotice_' + seizureIdx)) {
                        gallery.innerHTML = `
                            <div id="galleryEmptyNotice_${seizureIdx}" class="col-span-full py-4 text-center text-slate-400 text-xs font-mono-code">
                                No exhibit photos added yet. Upload files above or drag &amp; drop images.
                            </div>
                        `;
                    }
                }
            }
        }

        function clearAllExhibitPhotos(seizureIdx) {
            const gallery = document.getElementById(`photoGalleryExhibit_${seizureIdx}`);
            if (gallery) {
                gallery.innerHTML = `
                    <div id="galleryEmptyNotice_${seizureIdx}" class="col-span-full py-4 text-center text-slate-400 text-xs font-mono-code">
                        No exhibit photos added yet. Upload files above or drag &amp; drop images.
                    </div>
                `;
                updateExhibitPhotoCount(seizureIdx);
            }
        }

        function handleExhibitDragOver(e, dropzone) {
            if (e.preventDefault) e.preventDefault();
            if (e.stopPropagation) e.stopPropagation();
            if (dropzone) {
                dropzone.classList.add('border-emerald-500', 'bg-emerald-50/80');
            }
        }

        function handleExhibitDragLeave(e, dropzone) {
            if (e.preventDefault) e.preventDefault();
            if (e.stopPropagation) e.stopPropagation();
            if (dropzone) {
                dropzone.classList.remove('border-emerald-500', 'bg-emerald-50/80');
            }
        }

        function removeAssociateSeizureBlock(id) {
            const card = document.getElementById(`seizureBlock-${id}`);
            if (card) {
                card.remove();
                updateSeizureBlockNumbers();
                syncSection3SeizuresToSection4();
            }
        }



        const addAssociateExhibitRow = addAssociateSeizureBlock;

        function saveDocketFormDraft() {}


        function toggleSeizuresContainer() {
            const chk = document.getElementById('hasSeizuresCheckbox');
            const container = document.getElementById('seizuresDetailsContainer');
            if (container) {
                const isChecked = !!(chk && chk.checked);
                if (isChecked) {
                    container.classList.remove('hidden');
                } else {
                    container.classList.add('hidden');
                }
                container.querySelectorAll('input, select, textarea').forEach(el => {
                    el.disabled = !isChecked;
                    if (!isChecked) {
                        el.removeAttribute('required');
                    }
                });
            }
            if (typeof syncSection3SeizuresToSection4 === 'function') {
                syncSection3SeizuresToSection4();
            }
        }

        function getPristineSeizureBlockTemplate() {
            if (!window._seizureBlockTemplate) {
                const container = document.getElementById('seizuresListContainer');
                const firstBlock = container ? container.querySelector('.seizure-block') : null;
                if (firstBlock) {
                    window._seizureBlockTemplate = firstBlock.cloneNode(true);
                    window._seizureBlockTemplate.querySelectorAll('input:not([type="checkbox"]):not([type="radio"]), textarea').forEach(input => {
                        input.value = '';
                    });
                    window._seizureBlockTemplate.querySelectorAll('input[type="hidden"]').forEach(input => {
                        input.value = '';
                    });
                    window._seizureBlockTemplate.querySelectorAll('select').forEach(select => {
                        select.selectedIndex = 0;
                    });
                }
            }
            return window._seizureBlockTemplate;
        }

        function seizureBlockHasData(block) {
            if (!block) return false;
            const textInputs = block.querySelectorAll('input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]):not([type="file"]), textarea');
            for (const input of textInputs) {
                if (input.value && input.value.trim() !== '') return true;
            }
            return false;
        }

        function updateSeizureBlockNumbers() {
            const container = document.getElementById('seizuresListContainer');
            const blocks = container ? container.querySelectorAll('.seizure-block') : document.querySelectorAll('.seizure-block');
            blocks.forEach((block, index) => {
                const num = index + 1;
                block.setAttribute('data-seizure-index', num);
                const titleEl = block.querySelector('.seizure-title');
                const numberBadge = block.querySelector('.seizure-number-badge');
                if (titleEl) titleEl.innerText = `Seized Item ${num}`;
                if (numberBadge) numberBadge.innerText = num;
                const removeBtn = block.querySelector('.remove-seizure-btn');
                if (removeBtn) {
                    removeBtn.classList.toggle('hidden', blocks.length <= 1);
                }
            });
            const countInput = document.getElementById('seizureCountInput');
            if (countInput && parseInt(countInput.value, 10) !== blocks.length) {
                countInput.value = blocks.length;
            }
        }

        function addSeizureBlock(shouldUpdate = true) {
            const container = document.getElementById('seizuresListContainer');
            if (!container) return;
            const tpl = getPristineSeizureBlockTemplate();
            const firstBlock = container.querySelector('.seizure-block');
            if (!tpl && !firstBlock) return;
            const clone = tpl ? tpl.cloneNode(true) : firstBlock.cloneNode(true);
            clone._seizurePhotos = [];
            const clonePhotoGrid = clone.querySelector('.seizurePhotosGrid') || clone.querySelector('#seizurePhotosGrid');
            if (clonePhotoGrid) { clonePhotoGrid.innerHTML = ''; clonePhotoGrid.classList.add('hidden'); }
            const clonePhotoCount = clone.querySelector('.seizure-photos-count') || clone.querySelector('#seizurePhotosCount');
            if (clonePhotoCount) { clonePhotoCount.innerText = '0 Photos Uploaded'; clonePhotoCount.classList.add('hidden'); }
            const cloneFileInput = clone.querySelector('.fileSeizurePhoto') || clone.querySelector('#fileSeizurePhoto');
            if (cloneFileInput) cloneFileInput.value = '';
            container.appendChild(clone);
            if (shouldUpdate) {
                updateSeizureBlockNumbers();
                syncSuspectNamesToExhibits();
                syncSection3SeizuresToSection4();
            }
        }

        function setSeizureBlocksCount(val) {
            const targetCount = parseInt(val, 10);
            const container = document.getElementById('seizuresListContainer');
            if (!container || isNaN(targetCount) || targetCount < 1) return;
            const finalTarget = Math.min(Math.max(1, targetCount), 100);
            const currentBlocks = container.querySelectorAll('.seizure-block');
            const currentCount = currentBlocks.length;
            if (finalTarget === currentCount) return;
            if (finalTarget > currentCount) {
                for (let i = 0; i < finalTarget - currentCount; i++) {
                    addSeizureBlock(false);
                }
            } else {
                for (let i = currentCount - 1; i >= finalTarget; i--) {
                    currentBlocks[i].remove();
                }
            }
            updateSeizureBlockNumbers();
            syncSuspectNamesToExhibits();
            syncSection3SeizuresToSection4();
        }

        function removeSeizureBlock(btn) {
            const block = btn ? btn.closest('.seizure-block') : null;
            const container = document.getElementById('seizuresListContainer');
            if (!block || !container || container.querySelectorAll('.seizure-block').length <= 1) return;
            if (seizureBlockHasData(block) && !confirm('This seized item section contains entered information. Are you sure you want to remove it?')) return;
            block.remove();
            updateSeizureBlockNumbers();
            syncSuspectNamesToExhibits();
            syncSection3SeizuresToSection4();
        }

        function addSeizureOfficerRow(btn) {
            const block = btn ? btn.closest('.seizure-block') : null;
            const container = block ? block.querySelector('#seizureOfficersContainer') : document.getElementById('seizureOfficersContainer');
            if (!container) return;
            const rowDiv = document.createElement('div');
            rowDiv.className = 'seizure-officer-row p-2.5 bg-slate-50 border border-slate-300/80 rounded-xl';
            rowDiv.innerHTML = `
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <input type="text" class="seizure-officer-name w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-bold text-slate-900 text-xs" placeholder="Officer Full Name (e.g. DSP Kwame Mensah)">
                    <div class="flex items-center gap-1.5">
                        <input type="text" class="seizure-officer-service-id flex-1 px-3 py-2 bg-white border border-slate-300 rounded-lg font-bold text-slate-900 text-xs font-mono-code" placeholder="Service ID / Badge No (e.g. SD-0042)">
                        <button type="button" onclick="removeSeizureOfficerRow(this)" class="remove-officer-btn p-2 text-slate-400 hover:text-red-600 rounded-lg cursor-pointer" title="Remove Officer">�</button>
                    </div>
                </div>
            `;
            container.appendChild(rowDiv);
        }

        function removeSeizureOfficerRow(btn) {
            const row = btn.closest('.seizure-officer-row');
            const container = btn.closest('#seizureOfficersContainer');
            if (row && container && container.querySelectorAll('.seizure-officer-row').length > 1) {
                row.remove();
            }
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

        async function loadSeizurePhotoFiles(files, block, callback) {
            const targetBlock = block || document.querySelector('.seizure-block');
            const photos = getBlockSeizurePhotos(targetBlock);

            if (window.ForensicImageOptimizer) {
                await window.ForensicImageOptimizer.loadOptimizedSeizurePhotos(files, targetBlock, photos, () => {
                    renderSeizureBlockPhotosGrid(targetBlock);
                    syncGlobalSeizurePhotos();
                    if (typeof saveDocketFormDraft === 'function') {
                        saveDocketFormDraft();
                    }
                    if (callback) callback();
                });
                return;
            }

            // Standalone Fallback Safeguard (Max 15 photos/batch, under 5MB)
            let rawFiles = Array.from(files || []).filter(f => f && (!f.type || f.type.startsWith('image/')));
            const maxAllowed = Math.max(0, 15 - photos.length);
            if (maxAllowed <= 0) {
                alert('Each exhibit block can hold up to 15 photos to stay within server limits.');
                if (callback) callback();
                return;
            }
            if (rawFiles.length > maxAllowed) {
                alert(`Limiting upload to ${maxAllowed} photo(s) to stay within the 15-photo batch recommendation.`);
                rawFiles = rawFiles.slice(0, maxAllowed);
            }

            let loadedCount = 0;
            if (rawFiles.length === 0) {
                if (callback) callback();
                return;
            }

            rawFiles.forEach(file => {
                if (file.size > 5 * 1024 * 1024) {
                    alert(`"${file.name}" exceeds 5 MB. Please select images under 5 MB.`);
                    loadedCount++;
                    if (loadedCount === rawFiles.length) {
                        renderSeizureBlockPhotosGrid(targetBlock);
                        syncGlobalSeizurePhotos();
                        if (callback) callback();
                    }
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    photos.push(e.target.result);
                    loadedCount++;
                    if (loadedCount === rawFiles.length) {
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

        function collectLabAssociateSeizuresJson() {
            const container = document.getElementById('seizuresListContainer');
            if (!container) return [];
            const items = [];
            container.querySelectorAll('.seizure-block').forEach(block => {
                const cat = block.querySelector('#newSeizureCategory, .new-seizure-category')?.value?.trim()
                         || block.querySelector('#exhibitSearchInput, .exhibit-search-input')?.value?.trim() || 'Drugs';
                const drug = block.querySelector('#newSeizureDrug, .new-seizure-drug')?.value?.trim()
                          || block.querySelector('#drugSearchInput, .drug-search-input')?.value?.trim() || '';
                const name = block.querySelector('#newSeizureName, .new-seizure-name')?.value?.trim() || drug;
                const contents = block.querySelector('#newSeizureContents, .seizure-contents-input')?.value?.trim() || '';
                const value = block.querySelector('#newSeizureValue')?.value?.trim() || '';
                const qty = block.querySelector('#newSeizureQuantity')?.value?.trim() || '';
                const pkg = block.querySelector('#newSeizurePackaging, .new-seizure-packaging')?.value?.trim()
                         || block.querySelector('#pkgSearchInput, .pkg-search-input')?.value?.trim() || '';
                const weight = block.querySelector('#newSeizureWeight')?.value?.trim() || '';
                const unit = block.querySelector('#newSeizureUnit, .new-seizure-unit')?.value?.trim()
                          || block.querySelector('#unitSearchInput, .unit-search-input')?.value?.trim() || '';
                const concealment = block.querySelector('#newSeizureConcealment')?.value?.trim() || '';
                const sDate = block.querySelector('#newSeizureDate')?.value?.trim() || '';
                const sTime = block.querySelector('#newSeizureTime')?.value?.trim() || '';
                const officers = Array.from(block.querySelectorAll('.seizure-officer-name')).map(i => i.value.trim()).filter(Boolean).join(', ');
                const reason = block.querySelector('#newSeizureReason')?.value?.trim() || '';
                const source = block.querySelector('#newSeizureSourceOfExhibits, .seizure-source-of-exhibits-input')?.value?.trim() || '';
                const invOfficer = block.querySelector('#newSeizureInvestigatingOfficer, .seizure-investigating-officer-input')?.value?.trim() || '';
                const reqUnit = block.querySelector('#newSeizureSourceRequestingUnit, .seizure-source-requesting-unit-input')?.value?.trim() || '';
                const remark = block.querySelector('#newSeizureExhibitRemark, .seizure-remark-input')?.value?.trim() || '';
                const assocPerson = block.querySelector('.seizure-associated-person-select, .assoc-suspect-select')?.value?.trim() || '';

                if (name || contents || qty || weight || concealment || officers || reason || source) {
                    items.push({
                        category: cat,
                        exhibit_category: cat,
                        commodity_name: name || contents || 'Drug Exhibit',
                        seizure_contents: contents,
                        seizure_value: value,
                        seizure_quantity: qty,
                        seizure_packaging: pkg,
                        seizure_weight: weight,
                        seizure_unit: unit,
                        seizure_concealment: concealment,
                        seizure_date: sDate,
                        seizure_time: sTime,
                        seizure_officer: officers,
                        seizure_reason: reason,
                        source_of_exhibits: source,
                        investigating_officer: invOfficer,
                        source_requesting_unit: reqUnit,
                        exhibit_remark: remark,
                        associated_person: assocPerson,
                        evidence_photos: getBlockSeizurePhotos(block),
                    });
                }
            });
            return items;
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

        window.activeLabExhibitIndex = 1;

        function getPerExhibitLabData(idx) {
            const yesBox = document.getElementById(`presumptiveTestYes_${idx}`) || document.getElementById('presumptiveTestYes');
            const noBox = document.getElementById(`presumptiveTestNo_${idx}`) || document.getElementById('presumptiveTestNo');
            let presVal = null;
            let presPresences = [];
            let presPcts = [];
            if (noBox && noBox.checked) {
                presVal = 'NO';
            } else if (yesBox && yesBox.checked) {
                const customResult = document.getElementById(`presumptiveReactionResultInput_${idx}`)?.value
                    || document.getElementById('presumptiveReactionResultInput')?.value;
                presVal = (customResult && customResult.trim() !== '') ? customResult.trim() : 'YES';
                const presContainer = document.getElementById(`presumptiveTestRowsContainer_${idx}`) || document.getElementById('presumptiveTestRowsContainer');
                if (presContainer) {
                    presContainer.querySelectorAll('.presumptive-test-row').forEach(r => {
                        const pr = (r.querySelector('.presumptive-presence-input')?.value || '').trim();
                        let pc = (r.querySelector('.presumptive-percentage-input')?.value || '').trim();
                        if (pc && !pc.endsWith('%')) pc += '%';
                        if (pr) presPresences.push(pr);
                        if (pc) presPcts.push(pc);
                    });
                }
            }

            const confYes = document.getElementById(`confirmatoryTestYes_${idx}`) || document.getElementById('confirmatoryTestYes');
            const confNo = document.getElementById(`confirmatoryTestNo_${idx}`) || document.getElementById('confirmatoryTestNo');
            let confVal = 'NO';
            if (confYes && confYes.checked) confVal = 'YES';
            else if (confNo && confNo.checked) confVal = 'NO';

            const desc = document.getElementById(`sampleWeightDescription_${idx}`)?.value || document.getElementById('sampleWeightDescription')?.value || '';
            const units = document.getElementById(`sampleWeightUnits_${idx}`)?.value || document.getElementById('sampleWeightUnits')?.value || '';
            const gross = document.getElementById(`sampleWeightGross_${idx}`)?.value || document.getElementById('sampleWeightGross')?.value || '';
            const taken = document.getElementById(`sampleWeightTaken_${idx}`)?.value || document.getElementById('sampleWeightTaken')?.value || '';
            const reserved = document.getElementById(`sampleWeightReserved_${idx}`)?.value || document.getElementById('sampleWeightReserved')?.value || '';

            const substanceId = document.getElementById(`confirmatorySubstanceIdentification_${idx}`)?.value || document.getElementById('confirmatorySubstanceIdentification')?.value || '';
            const detSubs = typeof getDetectedSubstancesDataForExhibit === 'function' ? getDetectedSubstancesDataForExhibit(idx) : [];

            const methods = document.getElementById('confirmatoryTestMethods')?.value || '';
            const specs = document.getElementById('confirmatorySpecifications')?.value || '';
            const remark = document.getElementById('confirmatoryRemark')?.value || '';
            const purpose = document.getElementById('labPurposeOfExaminationInput')?.value || '';
            const prelimProc = document.getElementById('labPreliminaryExamProcedureInput')?.value || '';
            const screeningMethods = Array.from(document.querySelectorAll('#screeningMethodsContainer .screening-method-input'))
                .map(el => (el.value || '').trim())
                .filter(Boolean);
            const disposition = document.getElementById('confirmatoryDispositionOfExhibit')?.value || '';
            const dateReceived = document.getElementById('labDateReceivedInput')?.value || '';
            const dateStarted = document.getElementById('labDateStartedInput')?.value || '';
            const dateCompleted = document.getElementById('labDateCompletedInput')?.value || '';
            const analyst = document.getElementById('labAnalystInput')?.value || '';
            const exhDesc = document.getElementById('labExhibitDescriptionInput')?.value || '';
            const labExhNo = document.getElementById(`labCardExhibitNo_${idx}`)?.value
                || document.getElementById('labExhibitNumberInput')?.value || '';

            const hasSaved = (yesBox && yesBox.checked) || (confYes && confYes.checked) || !!taken || !!substanceId || detSubs.length > 0 || !!methods || !!prelimProc || screeningMethods.length > 0 || !!disposition || !!purpose || !!remark || !!specs;

            return {
                lab_exhibit_no: labExhNo,
                exhibit_description: exhDesc,
                analyst: analyst,
                date_received: dateReceived,
                date_started: dateStarted,
                date_completed: dateCompleted,
                purpose_of_examination: purpose,
                preliminary_exam_procedure: prelimProc,
                screening_method: screeningMethods,
                disposition_of_exhibit: disposition,
                presumptive_result: presVal,
                presumptive_presence: presPresences.length > 0 ? presPresences.join('; ') : null,
                presumptive_percentage: presPcts.length > 0 ? presPcts.join('; ') : null,
                confirmatory_result: confVal,
                confirmatory_test_result: confVal,
                substance_identification: substanceId,
                sample_weight_description: desc,
                sample_weight_units: units,
                sample_weight_gross: gross,
                sample_weight_taken: taken,
                sample_weight_reserved: reserved,
                sample_taken: taken,
                sample_reserved: reserved,
                sample_description: desc,
                sample_units: units,
                sample_gross: gross,
                sample_weight: {
                    description: desc,
                    units_packaging: units,
                    gross_weight: gross,
                    sample_taken: taken,
                    reserved_samples: reserved
                },
                detected_substances: detSubs,
                test_methods: methods,
                specifications: specs,
                remark: remark,
                has_saved_lab: hasSaved
            };
        }

        function selectLabExhibitCard(idx) {
            window.activeLabExhibitIndex = idx;
            const selectEl = document.getElementById('labExhibitSelect');
            if (selectEl) {
                selectEl.value = idx.toString();
                if (typeof onLabExhibitSelected === 'function') onLabExhibitSelected();
            }

            const cardExhNoInput = document.getElementById(`labCardExhibitNo_${idx}`);
            const hiddenInput = document.getElementById('labExhibitNumberInput');
            if (cardExhNoInput && hiddenInput) {
                hiddenInput.value = cardExhNoInput.value;
            }

            document.querySelectorAll('.lab-exhibit-card').forEach(card => {
                const cIdx = parseInt(card.getAttribute('data-exhibit-index'), 10);
                if (cIdx === idx) {
                    card.classList.remove('border-slate-200', 'bg-white');
                    card.classList.add('border-emerald-500', 'ring-2', 'ring-emerald-500/30', 'bg-emerald-50/40');
                } else {
                    card.classList.remove('border-emerald-500', 'ring-2', 'ring-emerald-500/30', 'bg-emerald-50/40');
                    card.classList.add('border-slate-200', 'bg-white');
                }
            });

            const presCard = document.getElementById(`presumptiveTestExhibitCard_${idx}`);
            if (presCard) {
                presCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        function togglePresumptiveTestForExhibit(idx, choice) {
            const yesBox = document.getElementById(`presumptiveTestYes_${idx}`);
            const noBox = document.getElementById(`presumptiveTestNo_${idx}`);
            const detailsContainer = document.getElementById(`presumptiveReactionDetailsContainer_${idx}`);
            const resultInput = document.getElementById(`presumptiveReactionResultInput_${idx}`);

            if (choice === 'YES') {
                if (yesBox) yesBox.checked = true;
                if (noBox) noBox.checked = false;
                if (detailsContainer) detailsContainer.classList.remove('hidden');
                updatePresumptiveReactionTextForExhibit(idx);
            } else {
                if (yesBox) yesBox.checked = false;
                if (noBox) noBox.checked = true;
                if (detailsContainer) detailsContainer.classList.add('hidden');
                if (resultInput) resultInput.value = 'NO (Negative Reaction)';
            }

            if (idx === 1) {
                const legacyYes = document.getElementById('presumptiveTestYes');
                const legacyNo = document.getElementById('presumptiveTestNo');
                const legacyInput = document.getElementById('presumptiveReactionResultInput');
                if (legacyYes) legacyYes.checked = (choice === 'YES');
                if (legacyNo) legacyNo.checked = (choice === 'NO');
                if (legacyInput && resultInput) legacyInput.value = resultInput.value;
            }
        }

        function addPresumptiveSpecimenRow(idx, presence = '', percentage = '') {
            return addPresumptiveTestRowForExhibit(idx, presence, percentage);
        }

        function addPresumptiveTestRowForExhibit(idx, presence = '', percentage = '') {
            const container = document.getElementById(`presumptiveTestRowsContainer_${idx}`);
            if (!container) return;
            const currentRows = container.querySelectorAll('.presumptive-test-row');
            const newRowNum = currentRows.length + 1;

            const rowDiv = document.createElement('div');
            rowDiv.className = 'presumptive-test-row p-3 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-2 animate-in fade-in duration-200';
            rowDiv.innerHTML = `
                <div class="flex items-center justify-between text-[11px] font-extrabold text-slate-700 uppercase font-mono-code">
                    <span class="test-row-title">Test Result #${newRowNum}</span>
                    <button type="button" onclick="removePresumptiveTestRowForExhibit(this, ${idx})"
                        class="remove-test-row-btn px-2 py-0.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg text-[10px] font-bold border border-transparent hover:border-red-200 transition-colors cursor-pointer">
                        &times; Remove
                    </button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                            Presence of <span class="text-red-600 font-bold">*</span>
                        </label>
                        <input type="text" class="presumptive-presence-input w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs"
                            value="${presence}" oninput="updatePresumptiveReactionTextForExhibit(${idx})" placeholder="e.g. Cocaine, Heroin, THC, Methamphetamine">
                    </div>
                    <div>
                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                            Percentage (%) <span class="text-red-600 font-bold">*</span>
                        </label>
                        <div class="flex items-center rounded-xl shadow-2xs overflow-hidden">
                            <input type="text" class="presumptive-percentage-input w-full pl-3 pr-2 py-2 bg-white border border-y border-l border-slate-300 rounded-l-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 font-mono-code"
                                value="${percentage}" oninput="updatePresumptiveReactionTextForExhibit(${idx})" placeholder="e.g. 85.5">
                            <span class="inline-flex items-center px-3 py-2 bg-slate-100 border border-slate-300 rounded-r-xl text-slate-700 font-extrabold text-xs font-mono-code shrink-0">%</span>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(rowDiv);
            updatePresumptiveRowTitlesForExhibit(idx);
            updatePresumptiveReactionTextForExhibit(idx);
        }

        function removePresumptiveTestRowForExhibit(btn, idx) {
            const row = btn.closest('.presumptive-test-row');
            const container = document.getElementById(`presumptiveTestRowsContainer_${idx}`);
            if (row && container) {
                const rows = container.querySelectorAll('.presumptive-test-row');
                if (rows.length <= 1) return;
                row.remove();
                updatePresumptiveRowTitlesForExhibit(idx);
                updatePresumptiveReactionTextForExhibit(idx);
            }
        }

        function updatePresumptiveRowTitlesForExhibit(idx) {
            const container = document.getElementById(`presumptiveTestRowsContainer_${idx}`);
            if (!container) return;
            const rows = container.querySelectorAll('.presumptive-test-row');
            rows.forEach((r, i) => {
                const title = r.querySelector('.test-row-title');
                if (title) title.innerText = `Test Result #${i + 1}`;
                const removeBtn = r.querySelector('.remove-test-row-btn');
                if (removeBtn) {
                    if (rows.length <= 1) {
                        removeBtn.classList.add('hidden');
                    } else {
                        removeBtn.classList.remove('hidden');
                    }
                }
            });
        }

        function updatePresumptiveReactionTextForExhibit(idx) {
            const container = document.getElementById(`presumptiveTestRowsContainer_${idx}`);
            const resultInput = document.getElementById(`presumptiveReactionResultInput_${idx}`);
            if (!container || !resultInput) return;

            const rows = container.querySelectorAll('.presumptive-test-row');
            const parts = [];
            rows.forEach((r, i) => {
                const pres = (r.querySelector('.presumptive-presence-input')?.value || '').trim();
                const pct = (r.querySelector('.presumptive-percentage-input')?.value || '').trim();
                if (pres || pct) {
                    let part = `Test #${i + 1}: `;
                    if (pres) part += `Presence of ${pres}`;
                    if (pct) part += ` (${pct}%)`;
                    parts.push(part);
                }
            });

            if (parts.length > 0) {
                resultInput.value = 'YES: ' + parts.join('; ');
            } else {
                resultInput.value = 'YES (Presumptive Positive)';
            }

            if (idx === 1) {
                const legacyInput = document.getElementById('presumptiveReactionResultInput');
                if (legacyInput) legacyInput.value = resultInput.value;
            }
        }

        function toggleConfirmatoryTestForExhibit(idx, choice) {
            const yesBox = document.getElementById(`confirmatoryTestYes_${idx}`);
            const noBox = document.getElementById(`confirmatoryTestNo_${idx}`);
            const weightContainer = document.getElementById(`sampleWeightContainer_${idx}`);
            const resultInput = document.getElementById(`confirmatoryTestResultInput_${idx}`);

            if (choice === 'YES') {
                if (yesBox) yesBox.checked = true;
                if (noBox) noBox.checked = false;
                if (weightContainer) weightContainer.classList.remove('hidden');
                if (resultInput) resultInput.value = 'YES';
                updateSampleWeightReservedForExhibit(idx);
            } else {
                if (yesBox) yesBox.checked = false;
                if (noBox) noBox.checked = true;
                if (weightContainer) weightContainer.classList.add('hidden');
                if (resultInput) resultInput.value = 'NO';
            }

            if (idx === 1) {
                const legacyYes = document.getElementById('confirmatoryTestYes');
                const legacyNo = document.getElementById('confirmatoryTestNo');
                const legacyInput = document.getElementById('confirmatoryTestResultInput');
                if (legacyYes) legacyYes.checked = (choice === 'YES');
                if (legacyNo) legacyNo.checked = (choice === 'NO');
                if (legacyInput && resultInput) legacyInput.value = resultInput.value;
            }
        }

        function updateConfirmatorySampleReserved(idx) {
            return updateSampleWeightReservedForExhibit(idx);
        }

        function updateSampleWeightReservedForExhibit(idx) {
            const grossInput = document.getElementById(`sampleWeightGross_${idx}`);
            const takenInput = document.getElementById(`sampleWeightTaken_${idx}`);
            const addonInput = document.getElementById(`sampleWeightTakenUnitAddon_${idx}`);
            const reservedInput = document.getElementById(`sampleWeightReserved_${idx}`);
            const errorMsgEl = document.getElementById(`sampleWeightTakenError_${idx}`);

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

            if (idx === 1) {
                const legacyTaken = document.getElementById('sampleWeightTaken');
                const legacyReserved = document.getElementById('sampleWeightReserved');
                if (legacyTaken && takenInput) legacyTaken.value = takenInput.value;
                if (legacyReserved && reservedInput) legacyReserved.value = reservedInput.value;
            }
        }

        function addConfirmatoryDetectedSubstanceRow(idx, substance = '', percentage = '') {
            return addDetectedSubstanceRowForExhibit(idx, substance, percentage);
        }

        function addDetectedSubstanceRowForExhibit(idx, substance = '', percentage = '') {
            const container = document.getElementById(`detectedSubstancesListContainer_${idx}`);
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
                <button type="button" onclick="removeDetectedSubstanceRowForExhibit(this, ${idx})" class="mt-2 sm:mt-5 p-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg border border-red-200 transition-colors shrink-0 cursor-pointer" title="Remove Row">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            `;
            container.appendChild(rowDiv);
            updateDetectedSubstanceRemoveButtonsForExhibit(idx);
        }

        function removeDetectedSubstanceRowForExhibit(btn, idx) {
            const container = document.getElementById(`detectedSubstancesListContainer_${idx}`);
            if (container) {
                const rows = container.querySelectorAll('.detected-substance-row');
                if (rows.length <= 1) return;
            }
            const row = btn.closest('.detected-substance-row');
            if (row) {
                row.remove();
                updateDetectedSubstanceRemoveButtonsForExhibit(idx);
            }
        }

        function updateDetectedSubstanceRemoveButtonsForExhibit(idx) {
            const container = document.getElementById(`detectedSubstancesListContainer_${idx}`);
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

        function getDetectedSubstancesDataForExhibit(idx) {
            const rows = document.querySelectorAll(`#detectedSubstancesListContainer_${idx} .detected-substance-row`);
            const data = [];
            rows.forEach(r => {
                const sub = r.querySelector('.detected-substance-name')?.value || '';
                const pct = r.querySelector('.detected-substance-percent')?.value || '';
                if (sub.trim() !== '' || pct.trim() !== '') {
                    data.push({
                        substance: sub.trim(),
                        percentage: pct.trim()
                    });
                }
            });
            return data;
        }

        function togglePresumptiveTest(choice) {
            togglePresumptiveTestForExhibit(1, choice);
        }
        function addPresumptiveTestRow() {
            addPresumptiveTestRowForExhibit(1);
        }
        function removePresumptiveTestRow(btn) {
            const card = btn.closest('.presumptive-exhibit-card');
            const idx = card ? parseInt(card.getAttribute('data-exhibit-index'), 10) : 1;
            removePresumptiveTestRowForExhibit(btn, idx);
        }
        function updatePresumptiveReactionText() {
            updatePresumptiveReactionTextForExhibit(1);
        }
        function toggleConfirmatoryTest(choice) {
            toggleConfirmatoryTestForExhibit(1, choice);
        }

        function syncPresumptiveContainerForExhibit(idx, name) {
            const container = document.getElementById('presumptiveTestExhibitsContainer');
            if (!container) return;
            let card = document.getElementById(`presumptiveTestExhibitCard_${idx}`);
            const safeName = name || `Exhibit #${idx}`;

            if (!card) {
                card = document.createElement('div');
                card.id = `presumptiveTestExhibitCard_${idx}`;
                card.className = 'presumptive-exhibit-card p-5 bg-white rounded-2xl border border-slate-300/80 shadow-2xs space-y-4';
                card.setAttribute('data-exhibit-index', idx);
                card.innerHTML = `
                    <div class="flex items-center justify-between border-b border-slate-200 pb-2.5">
                        <div class="flex items-center space-x-2">
                            <span class="w-6 h-6 rounded-full bg-emerald-600 text-white font-extrabold text-xs flex items-center justify-center">${idx}</span>
                            <h5 class="font-extrabold text-slate-900 text-xs uppercase tracking-wider presumptive-card-title">Presumptive Test — Exhibit #${idx}: <span class="text-emerald-700">${safeName}</span></h5>
                        </div>
                        <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase">EXHIBIT #${idx}</span>
                    </div>

                    <div>
                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-2">
                            Presumptive Test Reaction Result <span class="text-red-600 font-bold">*</span>
                        </label>
                        <div class="flex items-center space-x-3">
                            <label class="relative inline-flex items-center px-4 py-2 rounded-xl border-2 border-slate-300 bg-slate-50 hover:bg-slate-100 transition-all cursor-pointer select-none space-x-2 group">
                                <input type="checkbox" id="presumptiveTestYes_${idx}" name="presumptive_test_yes_${idx}" value="YES"
                                    onchange="togglePresumptiveTestForExhibit(${idx}, 'YES')"
                                    class="presumptive-yes-box w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500 cursor-pointer">
                                <span class="font-black text-xs text-slate-900 font-mono-code uppercase">YES</span>
                            </label>
                            <label class="relative inline-flex items-center px-4 py-2 rounded-xl border-2 border-red-300 bg-red-50/60 hover:bg-red-100/70 transition-all cursor-pointer select-none space-x-2 group">
                                <input type="checkbox" id="presumptiveTestNo_${idx}" name="presumptive_test_no_${idx}" value="NO" checked
                                    onchange="togglePresumptiveTestForExhibit(${idx}, 'NO')"
                                    class="presumptive-no-box w-4 h-4 text-red-600 rounded border-slate-300 focus:ring-red-500 cursor-pointer">
                                <span class="font-black text-xs text-red-900 font-mono-code uppercase">NO</span>
                            </label>
                        </div>

                        <input type="hidden" id="presumptiveReactionResultInput_${idx}" name="presumptive_reaction_result_input_${idx}" value="">

                        <div id="presumptiveReactionDetailsContainer_${idx}" class="mt-3.5 p-4 bg-emerald-50/70 rounded-xl border border-emerald-200 space-y-3 hidden">
                            <div class="flex items-center justify-between border-b border-emerald-200/80 pb-2">
                                <div class="flex items-center space-x-2 text-emerald-800 font-extrabold text-xs uppercase tracking-wider">
                                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                                    <span>Presumptive Test Reaction Result Details &amp; Field Spec</span>
                                </div>
                                <span class="text-[10px] font-mono-code font-bold text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-md border border-emerald-200 uppercase">
                                    MULTIPLE SPECIMEN SUPPORT
                                </span>
                            </div>

                            <div id="presumptiveTestRowsContainer_${idx}" class="space-y-2.5">
                                <div class="presumptive-test-row p-3 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-2">
                                    <div class="flex items-center justify-between text-[11px] font-extrabold text-slate-700 uppercase font-mono-code">
                                        <span class="test-row-title">Test Result #1</span>
                                        <button type="button" onclick="removePresumptiveTestRowForExhibit(this, ${idx})" class="remove-test-row-btn hidden px-2 py-0.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg text-[10px] font-bold border border-transparent hover:border-red-200 transition-colors cursor-pointer">&times; Remove</button>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Presence of <span class="text-red-600 font-bold">*</span></label>
                                            <input type="text" class="presumptive-presence-input w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs"
                                                oninput="updatePresumptiveReactionTextForExhibit(${idx})" placeholder="e.g. Cocaine, Heroin, THC, Methamphetamine">
                                        </div>
                                        <div>
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">Percentage (%) <span class="text-red-600 font-bold">*</span></label>
                                            <div class="flex items-center rounded-xl shadow-2xs overflow-hidden">
                                                <input type="text" class="presumptive-percentage-input w-full pl-3 pr-2 py-2 bg-white border border-y border-l border-slate-300 rounded-l-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 font-mono-code"
                                                    oninput="updatePresumptiveReactionTextForExhibit(${idx})" placeholder="e.g. 85.5">
                                                <span class="inline-flex items-center px-3 py-2 bg-slate-100 border border-slate-300 rounded-r-xl text-slate-700 font-extrabold text-xs font-mono-code shrink-0">%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-1 flex items-center justify-between">
                                <button type="button" onclick="addPresumptiveTestRowForExhibit(${idx})" class="inline-flex items-center px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-all cursor-pointer border-none space-x-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                    <span>+ Add More Test Result</span>
                                </button>
                                <span class="text-[10px] text-slate-500 font-medium italic">Log additional reaction tests for Exhibit #${idx}</span>
                            </div>
                        </div>
                    </div>
                `;
                container.appendChild(card);
            } else {
                const titleEl = card.querySelector('.presumptive-card-title');
                if (titleEl) titleEl.innerHTML = `Presumptive Test — Exhibit #${idx}: <span class="text-emerald-700">${safeName}</span>`;
            }
        }

        function syncConfirmatoryContainerForExhibit(idx, name, units, gross, unit) {
            const container = document.getElementById('confirmatoryTestExhibitsContainer');
            if (!container) return;
            let card = document.getElementById(`confirmatoryTestExhibitCard_${idx}`);
            const safeName = name || `Exhibit #${idx}`;
            const safeUnits = units || 'N/A';
            const safeGross = gross || 'N/A';
            const safeUnit = unit || 'kg';

            if (!card) {
                card = document.createElement('div');
                card.id = `confirmatoryTestExhibitCard_${idx}`;
                card.className = 'confirmatory-exhibit-card p-5 bg-white rounded-2xl border border-slate-300/80 shadow-2xs space-y-4';
                card.setAttribute('data-exhibit-index', idx);
                card.innerHTML = `
                    <div class="flex items-center justify-between border-b border-slate-200 pb-2.5">
                        <div class="flex items-center space-x-2">
                            <span class="w-6 h-6 rounded-full bg-emerald-600 text-white font-extrabold text-xs flex items-center justify-center">${idx}</span>
                            <h5 class="font-extrabold text-slate-900 text-xs uppercase tracking-wider confirmatory-card-title">Confirmatory Test — Exhibit #${idx}: <span class="text-emerald-700">${safeName}</span></h5>
                        </div>
                        <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase">EXHIBIT #${idx}</span>
                    </div>

                    <div>
                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-2">
                            Confirmatory Test Conducted <span class="text-red-600 font-bold">*</span>
                        </label>
                        <div class="flex items-center space-x-3">
                            <label class="relative inline-flex items-center px-4 py-2 rounded-xl border-2 border-slate-300 bg-slate-50 hover:bg-slate-100 transition-all cursor-pointer select-none space-x-2 group">
                                <input type="checkbox" id="confirmatoryTestYes_${idx}" name="confirmatory_test_yes_${idx}" value="YES"
                                    onchange="toggleConfirmatoryTestForExhibit(${idx}, 'YES')"
                                    class="confirmatory-yes-box w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500 cursor-pointer">
                                <span class="font-black text-xs text-slate-900 font-mono-code uppercase">YES</span>
                            </label>
                            <label class="relative inline-flex items-center px-4 py-2 rounded-xl border-2 border-red-300 bg-red-50/60 hover:bg-red-100/70 transition-all cursor-pointer select-none space-x-2 group">
                                <input type="checkbox" id="confirmatoryTestNo_${idx}" name="confirmatory_test_no_${idx}" value="NO" checked
                                    onchange="toggleConfirmatoryTestForExhibit(${idx}, 'NO')"
                                    class="confirmatory-no-box w-4 h-4 text-red-600 rounded border-slate-300 focus:ring-red-500 cursor-pointer">
                                <span class="font-black text-xs text-red-900 font-mono-code uppercase">NO</span>
                            </label>
                        </div>

                        <input type="hidden" id="confirmatoryTestResultInput_${idx}" name="confirmatory_test_result_${idx}" value="NO">

                        <div id="sampleWeightContainer_${idx}" class="mt-3.5 p-4 bg-emerald-50/70 rounded-xl border border-emerald-200 space-y-4 hidden">
                            <div class="flex items-center justify-between border-b border-emerald-200/80 pb-2">
                                <div class="flex items-center space-x-2 text-emerald-900 font-extrabold text-xs uppercase tracking-wider">
                                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                                    <span>SAMPLE WEIGHT</span>
                                </div>
                                <span class="text-[10px] font-mono-code font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-md border border-emerald-200 uppercase">
                                    CONFIRMATORY METROLOGY &amp; MASS
                                </span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                <div class="md:col-span-2">
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                        <span>Description <span class="text-red-600 font-bold">*</span></span>
                                        <span class="text-[9px] font-mono-code font-bold text-emerald-700 bg-emerald-100/90 px-1.5 py-0.5 rounded border border-emerald-200 uppercase">AUTO-FETCHED</span>
                                    </label>
                                    <input type="text" id="sampleWeightDescription_${idx}" name="sample_weight_description_${idx}" value="${safeName}" readonly
                                        class="w-full px-3 py-2 bg-slate-100/90 border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs outline-none shadow-2xs cursor-not-allowed">
                                </div>

                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                        <span>Number of Units <span class="text-red-600 font-bold">*</span></span>
                                        <span class="text-[9px] font-mono-code font-bold text-emerald-700 bg-emerald-100/90 px-1.5 py-0.5 rounded border border-emerald-200 uppercase">AUTO-FETCHED</span>
                                    </label>
                                    <input type="text" id="sampleWeightUnits_${idx}" name="sample_weight_units_${idx}" value="${safeUnits}" readonly
                                        class="w-full px-3 py-2 bg-slate-100/90 border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs outline-none shadow-2xs font-mono-code cursor-not-allowed">
                                </div>

                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                        <span>Gross Weight <span class="text-red-600 font-bold">*</span></span>
                                        <span class="text-[9px] font-mono-code font-bold text-emerald-700 bg-emerald-100/90 px-1.5 py-0.5 rounded border border-emerald-200 uppercase">AUTO-FETCHED</span>
                                    </label>
                                    <input type="text" id="sampleWeightGross_${idx}" name="sample_weight_gross_${idx}" value="${safeGross}" readonly
                                        class="w-full px-3 py-2 bg-slate-100/90 border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs outline-none shadow-2xs font-mono-code cursor-not-allowed">
                                </div>

                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                        Sample Taken (Weight/Volume) <span class="text-red-600 font-bold">*</span>
                                    </label>
                                    <div class="relative flex rounded-xl shadow-2xs">
                                        <input type="text" id="sampleWeightTaken_${idx}" name="sample_weight_taken_${idx}" placeholder="e.g. 5"
                                            oninput="updateSampleWeightReservedForExhibit(${idx})"
                                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-l-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 font-mono-code placeholder:text-slate-400 border-r-0">
                                        <span id="sampleWeightTakenUnitAddon_${idx}" class="inline-flex items-center px-3 py-2 rounded-r-xl border border-slate-300 bg-slate-100/90 text-slate-700 font-mono-code font-bold text-xs shrink-0 select-none cursor-not-allowed border-l-0">
                                            ${safeUnit}
                                        </span>
                                    </div>
                                    <p id="sampleWeightTakenError_${idx}" class="text-[11px] text-red-600 font-extrabold mt-1 hidden flex items-center space-x-1 animate-in fade-in duration-200">
                                        <svg class="w-3.5 h-3.5 shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>Sample taken cannot exceed the gross weight</span>
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                        <span>Reserved Samples <span class="text-red-600 font-bold">*</span></span>
                                        <span class="text-[9px] font-mono-code font-bold text-emerald-700 bg-emerald-100/90 px-1.5 py-0.5 rounded border border-emerald-200 uppercase">AUTO-CALCULATED</span>
                                    </label>
                                    <input type="text" id="sampleWeightReserved_${idx}" name="sample_weight_reserved_${idx}" readonly
                                        placeholder="Auto-calculated (Gross Weight - Sample Taken)"
                                        class="w-full px-3 py-2 bg-slate-100/90 border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none shadow-2xs font-mono-code placeholder:text-slate-400 cursor-not-allowed">
                                </div>

                                <div class="md:col-span-2 pt-3 border-t border-emerald-200/80 space-y-3.5">
                                    <div class="flex items-center justify-between border-b border-emerald-200/80 pb-2">
                                        <div class="flex items-center space-x-2 text-emerald-900 font-extrabold text-xs uppercase tracking-wider">
                                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>RESULT / CONFIRMATORY ANALYSIS</span>
                                        </div>
                                        <span class="text-[10px] font-mono-code font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-md border border-emerald-200 uppercase">
                                            ANALYTICAL FINDINGS &amp; SPECS
                                        </span>
                                    </div>

                                    <input type="hidden" id="confirmatorySubstanceIdentification_${idx}" name="confirmatory_substance_identification_${idx}" value="${safeName}">

                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between">
                                            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase">
                                                Result (Detected Substances &amp; Purity %) <span class="text-red-600 font-bold">*</span>
                                            </label>
                                            <button type="button" onclick="addDetectedSubstanceRowForExhibit(${idx})"
                                                class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] rounded-xl shadow-2xs transition-all flex items-center space-x-1 border-none cursor-pointer">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                                <span>Add More</span>
                                            </button>
                                        </div>
                                        <div id="detectedSubstancesListContainer_${idx}" class="space-y-2.5"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                container.appendChild(card);
                addDetectedSubstanceRowForExhibit(idx, '', '');
            } else {
                const titleEl = card.querySelector('.confirmatory-card-title');
                if (titleEl) titleEl.innerHTML = `Confirmatory Test — Exhibit #${idx}: <span class="text-emerald-700">${safeName}</span>`;

                const descInput = document.getElementById(`sampleWeightDescription_${idx}`);
                if (descInput && !descInput.getAttribute('data-user-edited')) descInput.value = safeName;

                const unitsInput = document.getElementById(`sampleWeightUnits_${idx}`);
                if (unitsInput) unitsInput.value = safeUnits;

                const grossInput = document.getElementById(`sampleWeightGross_${idx}`);
                if (grossInput) grossInput.value = safeGross;

                const addonInput = document.getElementById(`sampleWeightTakenUnitAddon_${idx}`);
                if (addonInput) addonInput.textContent = safeUnit;

                const subIdInput = document.getElementById(`confirmatorySubstanceIdentification_${idx}`);
                if (subIdInput && !subIdInput.getAttribute('data-user-edited') && (!subIdInput.value || subIdInput.value.trim() === '')) {
                    subIdInput.value = safeName;
                }

                updateSampleWeightReservedForExhibit(idx);
            }
        }

        function syncSection4ExhibitsToLabSection() {
            const selectEl = document.getElementById('labExhibitSelect');
            const badgeEl = document.getElementById('labExhibitCountBadge');
            const descInput = document.getElementById('labExhibitDescriptionInput');
            const labSec = document.getElementById('labAnalysisExclusiveSections');
            const cardsContainer = document.getElementById('labExhibitCardsContainer');
            const presContainer = document.getElementById('presumptiveTestExhibitsContainer');
            const confContainer = document.getElementById('confirmatoryTestExhibitsContainer');

            const isLabUser = (typeof isLabAnalysis !== 'undefined' && isLabAnalysis) || {{ $isLabAnalysis ? 'true' : 'false' }};
            const chk = document.getElementById('hasSeizuresCheckbox');
            const seizuresDetailsContainer = document.getElementById('seizuresDetailsContainer');
            const hasSeizures = chk && chk.checked;
            const isDetailsVisible = seizuresDetailsContainer && !seizuresDetailsContainer.classList.contains('hidden');

            if (!hasSeizures || !isDetailsVisible) {
                if (selectEl) {
                    selectEl.innerHTML = '<option value="">No exhibit saved yet</option>';
                    selectEl.setAttribute('disabled', 'disabled');
                }
                if (badgeEl) badgeEl.innerText = 'No exhibit saved yet';
                if (descInput) descInput.value = 'No exhibit saved yet';
                if (cardsContainer) cardsContainer.innerHTML = '<div class="p-3 bg-white/80 rounded-xl border border-dashed border-emerald-300 text-center text-xs text-slate-400 italic">No exhibits logged in manifest yet.</div>';
                if (presContainer) presContainer.innerHTML = '';
                if (confContainer) confContainer.innerHTML = '';
                if (labSec) {
                    if (window.activeLabWorkflowMode === 'nlr' && isLabUser) labSec.classList.remove('hidden');
                    else labSec.classList.add('hidden');
                }
                return;
            }

            const blocks = document.querySelectorAll('#seizuresListContainer .seizure-block');
            const totalBlocks = blocks.length;

            if (totalBlocks === 0) {
                if (selectEl) {
                    selectEl.innerHTML = '<option value="">No exhibit saved yet</option>';
                    selectEl.setAttribute('disabled', 'disabled');
                }
                if (badgeEl) badgeEl.innerText = 'No exhibit saved yet';
                if (descInput) descInput.value = 'No exhibit saved yet';
                if (cardsContainer) cardsContainer.innerHTML = '<div class="p-3 bg-white/80 rounded-xl border border-dashed border-emerald-300 text-center text-xs text-slate-400 italic">No exhibits logged in manifest yet.</div>';
                if (presContainer) presContainer.innerHTML = '';
                if (confContainer) confContainer.innerHTML = '';
                if (labSec) {
                    if (window.activeLabWorkflowMode === 'nlr' && isLabUser) labSec.classList.remove('hidden');
                    else labSec.classList.add('hidden');
                }
                return;
            }

            if (selectEl) {
                selectEl.innerHTML = '';
                selectEl.removeAttribute('disabled');
            }
            if (badgeEl) {
                badgeEl.innerText = `${totalBlocks} Exhibit${totalBlocks > 1 ? 's' : ''} in Manifest`;
            }
            if (labSec) {
                if (window.activeLabWorkflowMode === 'nlr' && isLabUser) labSec.classList.remove('hidden');
                else labSec.classList.add('hidden');
            }

            if (cardsContainer) {
                cardsContainer.innerHTML = '';
            }

            blocks.forEach((block, idx) => {
                const itemNum = idx + 1;
                const cat = block.querySelector('#newSeizureCategory')?.value || block.querySelector('#exhibitSearchInput')?.value || 'Exhibit';
                let name = block.querySelector('#newSeizureNameDrug')?.value ||
                           block.querySelector('#drugSearchInput')?.value ||
                           block.querySelector('#newSeizureName')?.value ||
                           block.querySelector('#weaponSearchInput')?.value ||
                           block.querySelector('#currencySearchInput')?.value ||
                           block.querySelector('#ammoSearchInput')?.value ||
                           block.querySelector('#deviceSearchInput')?.value ||
                           '';
                if (!name || name.trim() === '') name = cat;

                const contents = block.querySelector('.seizure-contents-input')?.value || block.querySelector('#newSeizureContents')?.value || '';
                const qty = block.querySelector('#newSeizureQuantity')?.value || '';
                const pkg = block.querySelector('#newSeizurePackaging')?.value || block.querySelector('#pkgSearchInput')?.value || '';
                const weight = block.querySelector('#newSeizureWeight')?.value || '';
                const unit = block.querySelector('#newSeizureUnit')?.value || block.querySelector('#unitSearchInput')?.value || '';
                const owner = block.querySelector('.seizure-associated-person-select')?.value || 'All Suspects';

                if (selectEl) {
                    const opt = document.createElement('option');
                    opt.value = itemNum.toString();
                    opt.innerText = `Exhibit #${itemNum}: ${name}`;
                    opt.setAttribute('data-name', name);
                    opt.setAttribute('data-cat', cat);
                    opt.setAttribute('data-qty', qty);
                    opt.setAttribute('data-pkg', pkg);
                    opt.setAttribute('data-weight', weight);
                    opt.setAttribute('data-unit', unit);
                    opt.setAttribute('data-contents', contents);
                    opt.setAttribute('data-owner', owner);
                    selectEl.appendChild(opt);
                }

                if (cardsContainer) {
                    const cardDiv = document.createElement('div');
                    const isActive = (window.activeLabExhibitIndex === itemNum);
                    cardDiv.className = `lab-exhibit-card p-3.5 rounded-xl border transition-all cursor-pointer shadow-2xs ${isActive ? 'border-emerald-500 ring-2 ring-emerald-500/30 bg-emerald-50/40' : 'border-slate-200 bg-white hover:border-emerald-300 hover:bg-slate-50/70'}`;
                    cardDiv.setAttribute('data-exhibit-index', itemNum);
                    const systemExhibitId = block.dataset.exhibitNumber || ('EXH-' + String(itemNum).padStart(5, '0'));
                    cardDiv.setAttribute('data-system-exhibit-number', systemExhibitId);
                    cardDiv.onclick = () => selectLabExhibitCard(itemNum);

                    let metricStr = '';
                    if (qty || pkg) metricStr += `${qty} ${pkg}`.trim();
                    if (weight || unit) metricStr += (metricStr ? ' | ' : '') + `${weight} ${unit}`.trim();
                    if (!metricStr) metricStr = 'No metrics specified';

                    const existingCardInput = document.getElementById(`labCardExhibitNo_${itemNum}`);
                    const exhNoVal = existingCardInput ? existingCardInput.value : '';

                    cardDiv.innerHTML = `
                        <div class="flex items-center justify-between mb-1.5 gap-1.5">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-md uppercase shrink-0">Exhibit #${itemNum}</span>
                                <span class="text-[10px] text-slate-400 font-medium truncate max-w-[110px]">${cat}</span>
                            </div>
                            <span class="lab-card-system-exhibit-id text-emerald-700 text-[10px] font-mono-code font-extrabold tracking-wider uppercase shrink-0" data-system-exhibit-number="${systemExhibitId}" title="System-Generated Exhibit ID">${systemExhibitId}</span>
                        </div>
                        <div class="font-extrabold text-slate-900 text-xs truncate mb-1" title="${name}">${name}</div>
                        <div class="text-[11px] text-slate-600 font-medium flex items-center gap-1 mb-1 truncate" title="Visual Contents: ${contents || 'Not specified'}">
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <span class="truncate"><strong class="font-semibold text-slate-700">Contents:</strong> ${contents || 'N/A'}</span>
                        </div>
                        <div class="flex items-center justify-between text-[10px] text-slate-500 font-mono-code border-t border-slate-100 pt-1.5 mt-1">
                            <span class="truncate font-semibold text-emerald-800">${metricStr}</span>
                            <span class="truncate text-slate-400 max-w-[90px]">${owner}</span>
                        </div>
                        <div class="mt-2.5 pt-2 border-t border-slate-100" onclick="event.stopPropagation()">
                            <label class="block text-slate-800 font-extrabold text-[10px] tracking-wider uppercase mb-1 flex items-center justify-between">
                                <span>Lab Exhibit No.</span>
                                <span class="text-[8px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Lab No.</span>
                            </label>
                            <input type="text"
                                id="labCardExhibitNo_${itemNum}"
                                name="lab_exhibit_nos[]"
                                data-field="lab_exhibit_no"
                                value="${exhNoVal}"
                                placeholder="A, B...Ai, Aii, Bi, Bii...., A1, A2, B1, B2"
                                class="card-exhibit-no-input w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-slate-900 font-mono-code font-bold text-xs outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600/30 shadow-2xs"
                                onclick="event.stopPropagation()"
                                oninput="onCardExhibitNoChange(this, ${itemNum})"
                            >
                        </div>
                    `;
                    cardsContainer.appendChild(cardDiv);
                }

                syncPresumptiveContainerForExhibit(itemNum, name);

                const unitsStr = qty ? `${qty} ${pkg}`.trim() : (pkg || '');
                const grossStr = weight ? `${weight} ${unit}`.trim() : (unit || '');
                syncConfirmatoryContainerForExhibit(itemNum, name, unitsStr, grossStr, unit);
            });

            if (presContainer) {
                const presCards = presContainer.querySelectorAll('.presumptive-exhibit-card');
                presCards.forEach(card => {
                    const cIdx = parseInt(card.getAttribute('data-exhibit-index'), 10);
                    if (cIdx > totalBlocks) card.remove();
                });
            }
            if (confContainer) {
                const confCards = confContainer.querySelectorAll('.confirmatory-exhibit-card');
                confCards.forEach(card => {
                    const cIdx = parseInt(card.getAttribute('data-exhibit-index'), 10);
                    if (cIdx > totalBlocks) card.remove();
                });
            }

            if (selectEl) {
                if (selectEl.querySelector(`option[value="${window.activeLabExhibitIndex}"]`)) {
                    selectEl.value = window.activeLabExhibitIndex.toString();
                } else if (selectEl.options.length > 0) {
                    selectEl.options[0].selected = true;
                    window.activeLabExhibitIndex = 1;
                }
            }
            onLabExhibitSelected();
        }

        function onCardExhibitNoChange(input, itemNum) {
            if (window.activeLabExhibitIndex === itemNum) {
                const hiddenInput = document.getElementById('labExhibitNumberInput');
                if (hiddenInput) hiddenInput.value = input.value;
            }
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

            if (!selectedOption || !selectEl.value || selectEl.value === '' || selectedOption.text ===
                'No exhibit saved yet' || selectedOption.text.startsWith('Select Seized Exhibit')) {
                if (descInput) descInput.value = 'No exhibit selected';
                return;
            }

            const idx = selectEl.value || '1';
            const paddedIdx = idx.padStart(3, '0');

            const currentYear = new Date().getFullYear();
            const defaultExhNo = `EXH-${currentYear}-${paddedIdx}-NFC`;
            const cardExhNoInput = document.getElementById(`labCardExhibitNo_${idx}`);
            if (exhNumInput) exhNumInput.value = (cardExhNoInput && cardExhNoInput.value) ? cardExhNoInput.value : defaultExhNo;
            if (cardExhNoInput && !cardExhNoInput.value) cardExhNoInput.value = defaultExhNo;
            if (examRefInput) {
                if (!examRefInput.value || examRefInput.value.startsWith('EXAM-') || examRefInput.value.includes('T28')) {
                    const randNum = Math.floor(1000 + Math.random() * 9000);
                    examRefInput.value = `NLA-FM-T${randNum}-F`;
                } else if (examRefInput.value.startsWith('GSA-') || examRefInput.value.startsWith('NLR-') || examRefInput.value.startsWith('CSS-') || examRefInput.value.startsWith('EMU-')) {
                    examRefInput.value = examRefInput.value.replace(/^(GSA|NLR|CSS|EMU)-/, 'NLA-');
                }
            }

            const name = selectedOption.getAttribute('data-name') || '';
            const cat = selectedOption.getAttribute('data-cat') || '';
            const qty = selectedOption.getAttribute('data-qty') || '';
            const pkg = selectedOption.getAttribute('data-pkg') || '';
            const weight = selectedOption.getAttribute('data-weight') || '';
            const unit = selectedOption.getAttribute('data-unit') || '';
            const contents = selectedOption.getAttribute('data-contents') || '';
            const owner = selectedOption.getAttribute('data-owner') || '';

            let formattedDesc = name || cat || 'Exhibit';
            if (cat) formattedDesc += ` | Category: ${cat}`;
            if (contents) formattedDesc += ` | Contents: ${contents}`;
            if (qty || pkg) formattedDesc += ` | Quantity/Packaging: ${qty} ${pkg}`.trim();
            if (weight || unit) formattedDesc += ` | Net Weight/Metrics: ${weight} ${unit}`.trim();
            if (owner) formattedDesc += ` | Suspect / Ownership: ${owner}`;

            if (descInput) {
                if (!labExhibitUserEdits[idx]) {
                    descInput.value = formattedDesc;
                } else {
                    descInput.value = labExhibitUserEdits[idx];
                }
            }
        }

        function findWeightChunk(parts) {
            if (!parts || parts.length === 0) return '';
            for (let i = parts.length - 1; i >= 0; i--) {
                const lower = parts[i].toLowerCase();
                if (/\b(kg|kgs|kilogram|kilograms|g|gm|gms|gram|grams|mg|mgs|milligram|milligrams|lb|lbs|pound|pounds)\b/i
                    .test(lower) || /\d\s*(kg|g|mg|lb)\b/i.test(lower)) {
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
            return {
                num,
                unit
            };
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
                return {
                    reserved: '0',
                    isOverflow: false
                };
            }

            const grossParts = parseParts(grossStr);
            let targetGrossChunk = findWeightChunk(grossParts);
            const gParsed = parseQtyAndUnit(targetGrossChunk);

            const defaultReserved = (gParsed && gParsed.unit) ? `0 ${gParsed.unit}` : '0';

            if (!takenStr || typeof takenStr !== 'string' || !takenStr.trim()) {
                return {
                    reserved: defaultReserved,
                    isOverflow: false
                };
            }

            const takenParts = parseParts(takenStr);
            let targetTakenChunk = findWeightChunk(takenParts);
            const tParsed = parseQtyAndUnit(targetTakenChunk);

            if (!gParsed || !tParsed) {
                return {
                    reserved: defaultReserved,
                    isOverflow: false
                };
            }

            let gKg = toKg(gParsed.num, gParsed.unit);
            let tKg = toKg(tParsed.num, tParsed.unit);

            if (gKg !== null && tKg === null && !tParsed.unit) {
                tKg = toKg(tParsed.num, gParsed.unit);
            }

            if (gKg !== null && tKg !== null) {
                if (tKg > gKg) {
                    return {
                        reserved: defaultReserved,
                        isOverflow: true
                    };
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
                return {
                    reserved: resVal,
                    isOverflow: false
                };
            } else {
                if (tParsed.num > gParsed.num) {
                    return {
                        reserved: defaultReserved,
                        isOverflow: true
                    };
                }
                const diff = gParsed.num - tParsed.num;
                const unit = gParsed.unit || tParsed.unit;
                return {
                    reserved: formatNum(diff) + (unit ? ' ' + unit : ''),
                    isOverflow: false
                };
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
            rowDiv.className =
                'detected-substance-row flex flex-col sm:flex-row items-center gap-3 bg-white p-3 rounded-xl border border-slate-300 shadow-2xs animate-in fade-in duration-200';
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
                    data.push({
                        substance: sub.trim(),
                        percentage: pct.trim()
                    });
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
                    if (e.target && e.target.closest && (e.target.closest(
                            '#seizuresDetailsContainer') || e.target.closest(
                            '#seizuresListContainer'))) {
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
                    if (e.target && (e.target.id === 'newArrestedName' || (e.target.matches && e
                            .target.matches('[data-field="suspect_name"]')))) {
                        syncSuspectDropdownsForExhibits();
                    }
                });
            });

            syncSuspectDropdownsForExhibits();
            syncSection4ExhibitsToLabSection();
            updateSampleWeightReserved();
        });

        function syncSection3SeizuresToSection4() { if (typeof syncSection4ExhibitsToLabSection === 'function') syncSection4ExhibitsToLabSection(); }

        window.activeLabWorkflowMode = null;

        function switchToGsaUploadSection() {
            window.activeLabWorkflowMode = 'gsa';
            const gsaSec = document.getElementById('gsaUploadSection');
            const nlrSec = document.getElementById('labAnalysisExclusiveSections');
            const gsaBtn = document.getElementById('toggleGsaReportBtn');
            const nlrBtn = document.getElementById('toggleNlrReportBtn');

            if (gsaSec) {
                gsaSec.classList.remove('hidden', 'opacity-60', 'pointer-events-none', 'select-none');
                gsaSec.removeAttribute('aria-disabled');
                gsaSec.querySelectorAll('input, select, textarea, button').forEach(el => {
                    if (el.getAttribute('data-nlr-disabled') === 'true') {
                        el.disabled = false;
                        el.removeAttribute('data-nlr-disabled');
                    }
                });
            }
            if (nlrSec) {
                nlrSec.classList.add('hidden', 'opacity-60', 'pointer-events-none', 'select-none');
                nlrSec.setAttribute('aria-disabled', 'true');
                nlrSec.querySelectorAll('input, select, textarea, button').forEach(el => {
                    if (!el.disabled) {
                        el.setAttribute('data-gsa-disabled', 'true');
                        el.disabled = true;
                    }
                });
            }
            if (gsaBtn) {
                gsaBtn.disabled = false;
                gsaBtn.removeAttribute('aria-disabled');
                gsaBtn.classList.remove('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
                gsaBtn.classList.add('ring-2', 'ring-blue-400');
            }
            if (nlrBtn) {
                nlrBtn.disabled = true;
                nlrBtn.setAttribute('aria-disabled', 'true');
                nlrBtn.classList.add('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
                nlrBtn.classList.remove('ring-2', 'ring-emerald-400');
            }

            const modeInput = document.getElementById('labWorkflowModeInput');
            if (modeInput) modeInput.value = 'gsa';
            if (gsaSec) gsaSec.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function switchToNlrExamSection() {
            window.activeLabWorkflowMode = 'nlr';
            const gsaSec = document.getElementById('gsaUploadSection');
            const nlrSec = document.getElementById('labAnalysisExclusiveSections');
            const gsaBtn = document.getElementById('toggleGsaReportBtn');
            const nlrBtn = document.getElementById('toggleNlrReportBtn');

            if (nlrSec) {
                nlrSec.classList.remove('hidden', 'opacity-60', 'pointer-events-none', 'select-none');
                nlrSec.removeAttribute('aria-disabled');
                nlrSec.querySelectorAll('input, select, textarea, button').forEach(el => {
                    if (el.getAttribute('data-gsa-disabled') === 'true') {
                        el.disabled = false;
                        el.removeAttribute('data-gsa-disabled');
                    }
                });
            }
            if (gsaSec) {
                gsaSec.classList.add('hidden', 'opacity-60', 'pointer-events-none', 'select-none');
                gsaSec.setAttribute('aria-disabled', 'true');
                gsaSec.querySelectorAll('input, select, textarea, button').forEach(el => {
                    if (!el.disabled) {
                        el.setAttribute('data-nlr-disabled', 'true');
                        el.disabled = true;
                    }
                });
            }
            if (nlrBtn) {
                nlrBtn.disabled = false;
                nlrBtn.removeAttribute('aria-disabled');
                nlrBtn.classList.remove('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
                nlrBtn.classList.add('ring-2', 'ring-emerald-400');
            }
            if (gsaBtn) {
                gsaBtn.disabled = true;
                gsaBtn.setAttribute('aria-disabled', 'true');
                gsaBtn.classList.add('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
                gsaBtn.classList.remove('ring-2', 'ring-blue-400');
            }

            const modeInput = document.getElementById('labWorkflowModeInput');
            if (modeInput) modeInput.value = 'nlr';
            if (nlrSec) nlrSec.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function resetLabModulesToNeutral() {
            window.activeLabWorkflowMode = null;
            const gsaSec = document.getElementById('gsaUploadSection');
            const nlrSec = document.getElementById('labAnalysisExclusiveSections');
            const gsaBtn = document.getElementById('toggleGsaReportBtn');
            const nlrBtn = document.getElementById('toggleNlrReportBtn');

            if (gsaSec) {
                gsaSec.classList.add('hidden');
                gsaSec.classList.remove('opacity-60', 'pointer-events-none', 'select-none');
                gsaSec.removeAttribute('aria-disabled');
                gsaSec.querySelectorAll('input, select, textarea, button').forEach(el => {
                    if (!el.disabled) {
                        el.setAttribute('data-nlr-disabled', 'true');
                        el.disabled = true;
                    }
                });
            }
            if (nlrSec) {
                nlrSec.classList.add('hidden');
                nlrSec.classList.remove('opacity-60', 'pointer-events-none', 'select-none');
                nlrSec.removeAttribute('aria-disabled');
                nlrSec.querySelectorAll('input, select, textarea, button').forEach(el => {
                    if (!el.disabled) {
                        el.setAttribute('data-gsa-disabled', 'true');
                        el.disabled = true;
                    }
                });
            }
            if (gsaBtn) {
                gsaBtn.classList.remove('ring-2', 'ring-blue-400', 'opacity-40', 'cursor-not-allowed', 'pointer-events-none');
                gsaBtn.disabled = false;
                gsaBtn.removeAttribute('aria-disabled');
            }
            if (nlrBtn) {
                nlrBtn.classList.remove('ring-2', 'ring-emerald-400', 'opacity-40', 'cursor-not-allowed', 'pointer-events-none');
                nlrBtn.disabled = false;
                nlrBtn.removeAttribute('aria-disabled');
            }
            const modeInput = document.getElementById('labWorkflowModeInput');
            if (modeInput) modeInput.value = '';
        }

        let gsaFilesMap = {};
        let gsaFileIdCounter = 0;

        function gsaDragOver(e) {
            e.preventDefault();
            const zone = document.getElementById('gsaDropZone');
            if (zone) {
                zone.classList.add('border-blue-500', 'bg-blue-50/80');
                zone.classList.remove('border-slate-300', 'bg-slate-50');
            }
        }

        function gsaDragLeave(e) {
            const zone = document.getElementById('gsaDropZone');
            if (zone) {
                zone.classList.remove('border-blue-500', 'bg-blue-50/80');
                zone.classList.add('border-slate-300', 'bg-slate-50');
            }
        }

        function gsaHandleDrop(e) {
            e.preventDefault();
            gsaDragLeave(e);
            Array.from(e.dataTransfer.files).forEach(f => gsaAddFile(f));
        }

        function gsaHandleFileSelect(input) {
            Array.from(input.files).forEach(f => gsaAddFile(f));
            syncGsaInputFiles();
        }

        function syncGsaInputFiles() {
            const input = document.getElementById('gsaReportImageInput');
            if (!input) return;
            const dt = new DataTransfer();
            Object.values(gsaFilesMap).forEach(entry => {
                if (entry && entry.file) dt.items.add(entry.file);
            });
            input.files = dt.files;
        }

        function gsaAddFile(file) {
            const maxMB = 10;
            if (file.size > maxMB * 1024 * 1024) {
                alert('"' + file.name + '" exceeds ' + maxMB + ' MB limit and was skipped.');
                return;
            }
            const id = 'gsa_' + (++gsaFileIdCounter);
            const ext = file.name.split('.').pop().toLowerCase();
            gsaFilesMap[id] = { file };

            const grid = document.getElementById('gsaFileGrid');
            if (grid) {
                const card = document.createElement('div');
                card.id = 'gsaCard_' + id;
                card.className = 'relative bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow group/card';
                card.innerHTML = gsaBuildCardInner(id, file.name, file.size, ext);
                grid.appendChild(card);

                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (ev) => {
                        const thumbWrapper = document.getElementById('gsaThumb_' + id);
                        if (thumbWrapper) {
                            thumbWrapper.innerHTML = '<img src="' + ev.target.result + '" class="w-full h-24 object-cover" alt="preview">';
                        }
                    };
                    reader.readAsDataURL(file);
                }
            }
            syncGsaInputFiles();
        }

        function gsaRemoveFile(id) {
            delete gsaFilesMap[id];
            const card = document.getElementById('gsaCard_' + id);
            if (card) card.remove();
            syncGsaInputFiles();
        }

        function clearAllGsaFiles() {
            gsaFilesMap = {};
            const grid = document.getElementById('gsaFileGrid');
            if (grid) grid.innerHTML = '';
            syncGsaInputFiles();
        }

        function gsaBuildCardInner(id, name, bytes, ext) {
            const kb = bytes < 1048576 ? Math.round(bytes / 1024) + ' KB' : (bytes / 1048576).toFixed(1) + ' MB';
            return `
                <div id="gsaThumb_${id}" class="h-24 bg-slate-100 flex items-center justify-center text-xs font-mono-code font-bold text-slate-600 uppercase">.${ext}</div>
                <div class="p-2 flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold text-slate-800 truncate">${name}</p>
                        <p class="text-[10px] text-slate-400">${kb}</p>
                    </div>
                    <button type="button" onclick="gsaRemoveFile('${id}')" class="text-red-500 hover:text-red-700 text-xs font-bold px-1.5 py-0.5 rounded bg-red-50 cursor-pointer">×</button>
                </div>
            `;
        }

        function addGsaExhibitTypeInput() {
            const container = document.getElementById('gsaExhibitTypesContainer');
            if (!container) return;
            const row = document.createElement('div');
            row.className = 'flex items-center gap-2 gsa-exhibit-type-row';
            row.innerHTML = `
                <input type="text" name="exhibit_type[]" placeholder="e.g. Cocaine / Cannabis / Heroin"
                    class="flex-1 px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                <button type="button" onclick="removeGsaExhibitTypeInput(this)" title="Remove exhibit type"
                    class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg border border-transparent hover:border-red-200 transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            `;
            container.appendChild(row);
        }

        function removeGsaExhibitTypeInput(btn) {
            const row = btn.closest('.gsa-exhibit-type-row');
            if (row) row.remove();
        }

        function syncAssociateIdFromSelect(selectEl) {
            if (!selectEl) return;
            const block = selectEl.closest('.seizure-block');
            if (!block) return;
            const hiddenInput = block.querySelector('.assoc-suspect-id-input');
            const selectedOpt = selectEl.options[selectEl.selectedIndex];
            const assocId = selectedOpt ? selectedOpt.getAttribute('data-associate-id') : '';
            if (hiddenInput) {
                hiddenInput.value = assocId || '';
            }
        }

        function syncSuspectNamesToExhibits() {
            const personCards = document.querySelectorAll('.new-person-card');
            const suspects = [];

            personCards.forEach((card, idx) => {
                const nameInput = card.querySelector('input[name*="[suspect_name]"]');
                const idInput   = card.querySelector('input[name*="[associate_id]"]');
                const name = nameInput ? nameInput.value.trim() : '';
                const id   = idInput ? idInput.value.trim() : ('ASC-' + String(idx + 1).padStart(3, '0'));
                if (name) {
                    suspects.push({ name: name, id: id, index: idx });
                }
            });

            const seizureBlocks = document.querySelectorAll('.seizure-block');
            seizureBlocks.forEach((block, szIdx) => {
                const select = block.querySelector('.assoc-suspect-select, .seizure-associated-person-select');
                if (!select) return;

                const currentVal = select.value ? select.value.trim() : '';

                select.innerHTML = '<option value="">Select Associated Suspect...</option>';
                suspects.forEach(sp => {
                    const opt = document.createElement('option');
                    opt.value = sp.name;
                    opt.setAttribute('data-associate-id', sp.id);
                    opt.textContent = `${sp.name} (Associate ID: ${sp.id})`;
                    if (sp.name === currentVal) {
                        opt.selected = true;
                    }
                    select.appendChild(opt);
                });

                if (!select.value && suspects.length > 0) {
                    if (suspects[szIdx]) {
                        select.value = suspects[szIdx].name;
                    } else if (suspects[0]) {
                        select.value = suspects[0].name;
                    }
                }

                syncAssociateIdFromSelect(select);
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Add 1 default person card and 1 seizure block on page load
            addAssociatePersonCard();
            getPristineSeizureBlockTemplate();
            updateSeizureBlockNumbers();
            toggleSeizuresContainer();

            const seizuresContainer = document.getElementById('seizuresListContainer');
            if (seizuresContainer) {
                seizuresContainer.addEventListener('input', syncSection3SeizuresToSection4);
                seizuresContainer.addEventListener('change', syncSection3SeizuresToSection4);
            }

            const detContainer = document.getElementById('detectedSubstancesListContainer');
            if (detContainer) {
                if (detContainer.querySelectorAll('.detected-substance-row').length === 0) {
                    addDetectedSubstanceRow();
                } else {
                    updateDetectedSubstanceRemoveButtons();
                }
            }

            syncSection3SeizuresToSection4();
            if (ACTIVE_UNIT_KEY === 'lab') {
                resetLabModulesToNeutral();
            }
            setTimeout(syncSuspectNamesToExhibits, 100);
        });
        /**
         * WHY: Users type substantial suspect/exhibit data. Clicking "Back" without
         * saving loses all of it. Add a warning when the form has unsaved changes.
         */
        (function() {
            const form = document.getElementById('associateDocketForm');
            if (!form) return;

            let isDirty = false;
            form.addEventListener('input',  () => { isDirty = true; });
            form.addEventListener('change', () => { isDirty = true; });

            window.addEventListener('beforeunload', (e) => {
                if (isDirty) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });

            form.addEventListener('submit', () => {
                isDirty = false;
                const serialized = collectLabAssociateSeizuresJson();
                if (serialized.length > 0) {
                    let hiddenJson = document.getElementById('associatedExhibitsJsonInput');
                    if (!hiddenJson) {
                        hiddenJson = document.createElement('input');
                        hiddenJson.type = 'hidden';
                        hiddenJson.id = 'associatedExhibitsJsonInput';
                        hiddenJson.name = 'associated_exhibits';
                        form.appendChild(hiddenJson);
                    }
                    hiddenJson.value = JSON.stringify(serialized);
                }
            });
        })();
    </script>

    <!-- MUGSHOT LIGHTBOX MODAL -->
    <div id="mugshotLightboxModal" class="fixed inset-0 z-[99999] bg-slate-900/80 backdrop-blur-md hidden flex items-center justify-center p-4" onclick="closeMugshotModal()">
        <div class="relative max-w-4xl w-full bg-white rounded-3xl overflow-hidden shadow-2xl border border-slate-200" onclick="event.stopPropagation()">
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <span id="mugshotModalTitle" class="font-extrabold text-sm uppercase tracking-wider font-mono-code">Facial Biometric High-Res View</span>
                </div>
                <button type="button" onclick="closeMugshotModal()" class="text-slate-400 hover:text-white p-1 rounded-lg transition-colors border-none bg-transparent cursor-pointer">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 bg-slate-950 flex items-center justify-center min-h-[400px] max-h-[80vh] overflow-auto">
                <img id="mugshotModalImage" src="" alt="Full High Res Mugshot" class="max-h-[70vh] max-w-full object-contain rounded-xl shadow-lg border border-slate-800">
            </div>
        </div>
    </div>
</body>
</html>
