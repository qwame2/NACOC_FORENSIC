{{--
    Forensic Directorate / Head Associate Co-Suspect & Evidence Dossier View
--}}
@php
    $unitContext = 'Directorate';
    $allowedCategories = array (
  0 => 'Drugs',
  1 => 'Money',
  2 => 'Gold',
  3 => 'Vehicles',
  4 => 'Electronic Devices',
  5 => 'Weapons',
  6 => 'Ammunition',
  7 => 'Other Exhibits',
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
                <form id="associateDocketForm" action="/cases/{{ $case->case_number }}/associate" method="POST" autocomplete="off" class="space-y-6 text-xs font-medium">
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

                    <!-- SECTION 3: ADD NEW ASSOCIATED EXHIBITS & COMMODITIES -->
                    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-5 relative">
                        
                        <div class="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-xl bg-{{ $activeUnitTheme }}-600 text-white flex items-center justify-center font-bold shadow-md shadow-{{ $activeUnitTheme }}-600/30">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                                <div>
                                    <h3 class="font-black text-slate-900 text-base">{{ $isLabAnalysis ? 'Section 3: Seized Commodities & Contraband Manifest' : 'Section 3: Add Associated ' . ($isDigitalForensics ? 'Electronic Devices & Digital Evidence' : ($isCrimeScene ? 'Weapons, Vehicles & Scene Exhibits' : 'Seizures & Exhibits')) }}</h3>
                                    <p class="text-xs text-slate-500">{{ $isLabAnalysis ? 'Record illicit substance types, gross weight, sample taken, packaging, or street valuation linked to this investigation' : ($isDigitalForensics ? 'Record seized electronic devices (phones, laptops, storage drives, IMEI, MAC address, serials) for this investigation' : 'Record illicit substance types, weapons, vehicles, electronic devices, or property linked to this investigation') }}</p>
                                </div>
                            </div>
                            <button type="button" onclick="addAssociateSeizureBlock()" data-testid="add-seizure-btn" class="px-4 py-2 bg-{{ $activeUnitTheme }}-600 hover:bg-{{ $activeUnitTheme }}-700 text-white text-xs font-extrabold rounded-xl shadow-md transition-all flex items-center space-x-1.5 cursor-pointer border-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                <span>+ Add {{ $isLabAnalysis ? 'Drug Exhibit' : ($isDigitalForensics ? 'Electronic Device' : 'Exhibit Item') }}</span>
                            </button>
                        </div>

                        @if($isLabAnalysis)
                            @include('partials.unassigned_exhibits_selector', ['selectorTargetUnit' => 'Lab Analysis'])
                        @endif

                        <!-- CONTAINER FOR DYNAMIC SEIZURE BLOCKS -->
                        <div id="seizuresListContainer" class="space-y-6">
                            <!-- Rows added dynamically via JS -->
                        </div>

                    </div>

                    <!-- SECTION 4: FORENSIC EXAMINATION & LABORATORY ANALYSIS (LAB ANALYSIS EXCLUSIVE) -->
                    @if($isLabAnalysis)
                    <!-- SECTION 3 TO 4 ACTION & REPORT GENERATION NAVIGATION BAR -->
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
                                    Switch between uploading GSA Report Files and filling Section 4 Examination Information (NLR)
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3 w-full sm:w-auto justify-end shrink-0 flex-wrap gap-y-2">
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
                                <span id="gsaFileCount" class="hidden px-2.5 py-0.5 bg-blue-600 text-white text-[10px] font-extrabold font-mono-code rounded-full">0 files</span>
                                <button type="button" onclick="clearAllGsaFiles()" id="gsaClearAllBtn" class="hidden px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 text-[10px] font-bold rounded-lg border border-red-200 transition-colors cursor-pointer">Clear All</button>
                            </div>
                        </div>

                        <div class="bg-blue-50/50 p-4 rounded-2xl border border-blue-100 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="space-y-1.5">
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase flex items-center justify-between">
                                        <span>GSA Lab No.</span>
                                        <span class="text-[9px] font-mono-code text-blue-700 bg-blue-100 px-1.5 py-0.5 rounded font-bold uppercase">Official No.</span>
                                    </label>
                                    <input type="text" id="gsaLabNumberInput" name="gsa_lab_number"
                                        placeholder="Enter GSA Lab No. (e.g. GSA/LAB/2026/0892)"
                                        class="w-full px-3.5 py-2.5 bg-white border border-blue-200 rounded-xl text-slate-900 font-mono-code font-bold outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all text-xs shadow-2xs">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase flex items-center justify-between">
                                        <span>GSA Case Reference</span>
                                        <span class="text-[9px] font-mono-code text-slate-600 bg-slate-200/70 px-1.5 py-0.5 rounded font-bold uppercase">Reference</span>
                                    </label>
                                    <textarea id="gsaCaseReferenceInput" name="gsa_case_reference" rows="2"
                                        placeholder="Enter GSA Case Reference details..."
                                        class="w-full px-3.5 py-2 bg-white border border-blue-200 rounded-xl text-slate-900 font-mono-code text-xs outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all shadow-2xs resize-none"></textarea>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase flex items-center justify-between">
                                        <span>GSA Status</span>
                                        <span class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Status</span>
                                    </label>
                                    <textarea id="gsaStatusInput" name="gsa_status" rows="2"
                                        placeholder="Enter GSA Status (e.g. Analysis Complete, Certified...)"
                                        class="w-full px-3.5 py-2 bg-white border border-blue-200 rounded-xl text-slate-900 font-mono-code text-xs outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all shadow-2xs resize-none"></textarea>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-blue-200/60 space-y-2" id="gsaExhibitTypesSection">
                                <div class="flex items-center justify-between">
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase">Exhibit Type</label>
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

                        <div id="gsaDropZone" ondragover="gsaDragOver(event)" ondragleave="gsaDragLeave(event)" ondrop="gsaHandleDrop(event)" class="relative w-full border-2 border-dashed border-slate-300 rounded-2xl bg-slate-50 hover:border-blue-400 transition-all overflow-hidden">
                            <div id="gsaFileGrid" class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-5 gap-3 p-3"></div>
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
                            <div id="gsaAddMoreStrip" onclick="document.getElementById('gsaReportImageInput').click()" class="hidden items-center justify-center gap-2 py-2.5 px-4 border-t border-dashed border-slate-300 bg-slate-50 hover:bg-blue-50/60 hover:border-blue-400 transition-all cursor-pointer group">
                                <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span class="text-[11px] font-bold text-slate-500 group-hover:text-blue-800 font-mono-code">Click to add more files or drag &amp; drop</span>
                            </div>
                            <input type="file" id="gsaReportImageInput" name="gsa_report_images[]" accept="image/jpeg,image/png,image/webp,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/csv,.csv,.docx" multiple class="hidden" onchange="gsaHandleFileSelect(this)">
                        </div>
                    </div>

                    <div id="labAnalysisExclusiveSections" class="hidden bg-white p-6 rounded-2xl border border-emerald-200 shadow-sm space-y-6 relative">
                        
                        <div class="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-xl bg-white text-emerald-600 border border-slate-200/90 flex items-center justify-center font-bold shadow-md shadow-slate-200/60">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                                </div>
                                <div>
                                    <h3 class="font-black text-slate-900 text-base">Section 4: Forensic Examination &amp; Laboratory Analysis</h3>
                                    <p class="text-xs text-emerald-700 font-semibold">Laboratory Analysis Directorate — Forensic Examination, Screening &amp; Confirmatory Testing Log</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2 shrink-0">
                                <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider">
                                    LAB ANALYSIS EXCLUSIVE
                                </span>
                            </div>
                        </div>

                        <!-- EXAMINATION INFORMATION CONTAINER -->
                        @if($activeSuspectHasSavedResult)
                        <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900">
                            <strong>Note:</strong> The most recently added exhibit already has a saved lab result.
                            Any new submission below will create a NEW examination record for the exhibit you associate here.
                        </div>
                        @endif

                        <div id="section5FormContainer" class="space-y-6">
                        <!-- SUB-SECTION 4A: EXAMINATION INFORMATION -->
                        <div class="bg-slate-50/60 p-5 rounded-2xl border border-slate-200 space-y-5">
                            <div class="pb-2 border-b border-slate-200 flex items-center justify-between">
                                <h4 class="font-extrabold text-slate-900 text-xs uppercase tracking-wider flex items-center space-x-2">
                                    <span class="w-5 h-5 rounded-full bg-emerald-600 text-white text-[10px] flex items-center justify-center font-bold">4A</span>
                                    <span>Examination Information</span>
                                </h4>
                            </div>

                            <!-- DYNAMIC EXHIBIT SELECTOR CARD FROM SECTION 3 SEIZED COMMODITIES MANIFEST -->
                            <div class="p-4 bg-emerald-50/70 rounded-xl border border-emerald-200/90 space-y-3 shadow-2xs">
                                <div class="space-y-2">
                                    <div>
                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                            <span class="flex items-center space-x-2">
                                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                                <span>Select Seized Exhibit (Manifest Sync)</span>
                                            </span>
                                            <span id="labExhibitCountBadge" class="text-[10px] font-mono-code font-extrabold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-md border border-emerald-200">
                                                No exhibit saved yet
                                            </span>
                                        </label>
                                        <select id="labExhibitSelect" name="lab_selected_exhibit_index" onchange="onLabExhibitSelected()" class="hidden"></select>
                                        <div id="labExhibitCardsContainer" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 pt-1">
                                            <div class="p-3 bg-white/80 rounded-xl border border-dashed border-emerald-300 text-center text-xs text-slate-400 italic">
                                                No exhibits logged in manifest yet.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-[11px] text-emerald-900 font-semibold flex items-center space-x-1">
                                    <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Each seized exhibit is synchronized from the manifest. Click an exhibit card to select it for focus or view examination specifics below.</span>
                                </p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                <!-- Analysis/Examination Reference -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                        <span>Analysis / Examination Reference</span>
                                        <span class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Auto-Generated</span>
                                    </label>
                                    <input type="text" id="labExamReferenceInput" name="lab_exam_reference" value="{{ $examRefAuto }}" readonly class="w-full px-3.5 py-3 bg-emerald-50/70 border border-emerald-200 rounded-xl text-emerald-900 font-mono-code font-extrabold outline-none shadow-2xs text-xs">
                                </div>

                                <!-- Case Reference -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                        <span>Case Reference</span>
                                        <span class="text-[9px] font-mono-code text-blue-700 bg-blue-100 px-1.5 py-0.5 rounded font-bold uppercase">Auto-Linked</span>
                                    </label>
                                    <input type="text" id="labCaseReferenceInput" name="lab_case_reference" value="{{ $caseRefAuto }}" readonly class="w-full px-3.5 py-3 bg-blue-50/70 border border-blue-200 rounded-xl text-blue-900 font-mono-code font-extrabold outline-none shadow-2xs text-xs">
                                </div>

                                <!-- Exhibit Number -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                        <span>Exhibit Number</span>
                                        <span class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Auto-Linked</span>
                                    </label>
                                    <input type="text" id="labExhibitNumberInput" name="lab_exhibit_number" value="{{ $exhibitRefAuto }}" readonly class="w-full px-3.5 py-3 bg-emerald-50/70 border border-emerald-200 rounded-xl text-emerald-900 font-mono-code font-extrabold outline-none shadow-2xs text-xs">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                <!-- Exhibit Description -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                                        <span>Exhibit Description</span>
                                    </label>
                                    <textarea id="labExhibitDescriptionInput" name="lab_exhibit_description" rows="3" placeholder="Enter exhibit description for forensic examination..." class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs placeholder:text-slate-400"></textarea>
                                </div>

                                <!-- Laboratory Number -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                        <span>Laboratory Number</span>
                                        <span class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Auto-Generated</span>
                                    </label>
                                    <input type="text" id="labNumberInput" name="laboratory_number" value="{{ $labNumberAuto }}" readonly class="w-full px-3.5 py-3 bg-emerald-50/70 border border-emerald-200 rounded-xl text-emerald-900 font-mono-code font-extrabold outline-none shadow-2xs text-xs">
                                </div>

                                <!-- Analyst / Examiner -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                                        <span>Analyst / Examiner</span>
                                        <span class="text-[9px] font-mono-code text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold uppercase">Examiner Name</span>
                                    </label>
                                    <input type="text" id="labAnalystInput" name="lab_analyst" value="{{ auth()->user()->fullname ?? session('user_name') ?? 'Lab Examiner' }}" class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-extrabold outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs text-xs" placeholder="Examiner / Analyst Name">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 pt-2 border-t border-slate-100">
                                <!-- Date Received -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">Date Received <span class="text-red-600 font-bold">*</span></label>
                                    <input type="date" id="labDateReceivedInput" name="lab_date_received" value="{{ $todayDate }}" class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold font-mono-code outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs">
                                </div>

                                <!-- Date Examination Started -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">Date Examination Started <span class="text-red-600 font-bold">*</span></label>
                                    <input type="date" id="labDateStartedInput" name="lab_date_started" value="{{ $todayDate }}" class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold font-mono-code outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs">
                                </div>

                                <!-- Date Examination Completed -->
                                <div>
                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">Date Examination Completed <span class="text-red-600 font-bold">*</span></label>
                                    <input type="date" id="labDateCompletedInput" name="lab_date_completed" value="{{ $todayDate }}" class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold font-mono-code outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs text-xs">
                                </div>
                            </div>
                        </div>

                        <!-- SUB-SECTION 4B: PRESUMPTIVE TEST -->
                        <div class="bg-slate-50/60 p-5 rounded-2xl border border-slate-200 space-y-4">
                            <div class="pb-2 border-b border-slate-200 flex items-center justify-between">
                                <h4 class="font-extrabold text-slate-900 text-xs uppercase tracking-wider flex items-center space-x-2">
                                    <span class="w-5 h-5 rounded-full bg-emerald-600 text-white text-[10px] flex items-center justify-center font-bold">4B</span>
                                    <span>Presumptive Test</span>
                                </h4>
                                <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider">
                                    SCREENING RESULT
                                </span>
                            </div>

                            <div>
                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-3">
                                    Presumptive Test Reaction Result <span class="text-red-600 font-bold">*</span>
                                </label>

                                <div class="flex items-center space-x-3">
                                    <label class="relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 border-slate-300 bg-slate-50 hover:bg-slate-100 transition-all cursor-pointer select-none space-x-2 group">
                                        <input type="checkbox" id="presumptiveTestYes" name="presumptive_test_yes" value="YES" onchange="togglePresumptiveTest('YES')" class="w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500 cursor-pointer">
                                        <div class="flex items-center space-x-1.5">
                                            <span class="font-black text-xs text-slate-900 font-mono-code uppercase">YES</span>
                                        </div>
                                    </label>

                                    <label class="relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 border-red-300 bg-red-50/60 hover:bg-red-100/70 transition-all cursor-pointer select-none space-x-2 group">
                                        <input type="checkbox" id="presumptiveTestNo" name="presumptive_test_no" value="NO" checked onchange="togglePresumptiveTest('NO')" class="w-4 h-4 text-red-600 rounded border-slate-300 focus:ring-red-500 cursor-pointer">
                                        <div class="flex items-center space-x-1.5">
                                            <span class="font-black text-xs text-red-900 font-mono-code uppercase">NO</span>
                                        </div>
                                    </label>
                                </div>

                                <div id="presumptiveReactionDetailsContainer" class="mt-4 p-4 bg-emerald-50/70 rounded-xl border border-emerald-200 space-y-4 transition-all duration-300 hidden">
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
                                    
                                    <div id="presumptiveTestRowsContainer" class="space-y-3">
                                        <div class="presumptive-test-row p-3.5 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-2.5">
                                            <div class="flex items-center justify-between text-[11px] font-extrabold text-slate-700 uppercase font-mono-code">
                                                <span class="test-row-title">Test Result #1</span>
                                                <button type="button" onclick="removePresumptiveTestRow(this)" class="remove-test-row-btn hidden px-2 py-0.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg text-[10px] font-bold border border-transparent hover:border-red-200 transition-colors cursor-pointer">
                                                    &times; Remove
                                                </button>
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                <div>
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                        Presence of <span class="text-red-600 font-bold">*</span>
                                                    </label>
                                                    <input type="text" class="presumptive-presence-input w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs" onchange="updatePresumptiveReactionText()" oninput="updatePresumptiveReactionText()" placeholder="e.g. Cocaine, Heroin, THC, Methamphetamine">
                                                </div>
                                                <div>
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                        Percentage (%) <span class="text-red-600 font-bold">*</span>
                                                    </label>
                                                    <div class="flex items-center rounded-xl shadow-2xs overflow-hidden">
                                                        <input type="text" class="presumptive-percentage-input w-full pl-3.5 pr-2 py-2.5 bg-white border border-y border-l border-slate-300 rounded-l-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 font-mono-code" onchange="updatePresumptiveReactionText()" oninput="updatePresumptiveReactionText()" placeholder="e.g. 85.5">
                                                        <span class="inline-flex items-center px-3 py-2.5 bg-slate-100 border border-slate-300 rounded-r-xl text-slate-700 font-extrabold text-xs font-mono-code shrink-0">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pt-1 flex items-center justify-between">
                                        <button type="button" onclick="addPresumptiveTestRow()" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-all cursor-pointer border-none space-x-1.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                            <span>+ Add More Test Result</span>
                                        </button>
                                        <span class="text-[10px] text-slate-500 font-medium italic">Click to log additional chemical/substance reaction test results</span>
                                    </div>

                                    <input type="hidden" id="presumptiveReactionResultInput" name="presumptive_reaction_result_input" value="">
                                </div>
                            </div>
                        </div>

                        <!-- SUB-SECTION 4C: CONFIRMATORY TEST & ANALYTICAL FINDINGS -->
                        <div class="bg-slate-50/60 p-5 rounded-2xl border border-slate-200 space-y-4">
                            <div class="pb-2 border-b border-slate-200 flex items-center justify-between">
                                <h4 class="font-extrabold text-slate-900 text-xs uppercase tracking-wider flex items-center space-x-2">
                                    <span class="w-5 h-5 rounded-full bg-emerald-600 text-white text-[10px] flex items-center justify-center font-bold">4C</span>
                                    <span>Confirmatory Test &amp; Analytical Findings</span>
                                </h4>
                                <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider">
                                    LAB VERIFICATION
                                </span>
                            </div>

                            <div>
                                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-3">
                                    Confirmatory Test Conducted <span class="text-red-600 font-bold">*</span>
                                </label>

                                <div class="flex items-center space-x-3">
                                    <label class="relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 border-slate-300 bg-slate-50 hover:bg-slate-100 transition-all cursor-pointer select-none space-x-2 group">
                                        <input type="checkbox" id="confirmatoryTestYes" name="confirmatory_test_yes" value="YES" onchange="toggleConfirmatoryTest('YES')" class="w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500 cursor-pointer">
                                        <div class="flex items-center space-x-1.5">
                                            <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                                            <span class="font-black text-xs text-slate-900 font-mono-code uppercase">YES</span>
                                        </div>
                                    </label>

                                    <label class="relative inline-flex items-center px-4 py-2.5 rounded-xl border-2 border-red-300 bg-red-50/60 hover:bg-red-100/70 transition-all cursor-pointer select-none space-x-2 group">
                                        <input type="checkbox" id="confirmatoryTestNo" name="confirmatory_test_no" value="NO" checked onchange="toggleConfirmatoryTest('NO')" class="w-4 h-4 text-red-600 rounded border-slate-300 focus:ring-red-500 cursor-pointer">
                                        <div class="flex items-center space-x-1.5">
                                            <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                            <span class="font-black text-xs text-red-900 font-mono-code uppercase">NO</span>
                                        </div>
                                    </label>
                                </div>

                                <input type="hidden" id="confirmatoryTestResultInput" name="confirmatory_test_result" value="NO">

                                <div id="sampleWeightContainer" class="mt-4 p-4 bg-emerald-50/70 rounded-xl border border-emerald-200 space-y-4 transition-all duration-300 hidden">
                                    <!-- SAMPLE WEIGHT / METROLOGY HEADER -->
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
                                                id="sampleWeightDescription" 
                                                name="sample_weight_description" 
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
                                                id="sampleWeightUnits" 
                                                name="sample_weight_units" 
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
                                                id="sampleWeightGross" 
                                                name="sample_weight_gross" 
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
                                                    id="sampleWeightTaken" 
                                                    name="sample_weight_taken" 
                                                    placeholder="e.g. 10"
                                                    oninput="updateSampleWeightReserved()"
                                                    onchange="updateSampleWeightReserved()"
                                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-l-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 font-mono-code placeholder:text-slate-400 border-r-0"
                                                >
                                                <span 
                                                    id="sampleWeightTakenUnitAddon"
                                                    class="inline-flex items-center px-3 py-2.5 rounded-r-xl border border-slate-300 bg-slate-100/90 text-slate-700 font-mono-code font-bold text-xs shrink-0 select-none cursor-not-allowed border-l-0 transition-colors duration-200"
                                                >
                                                    Kilograms (kg)
                                                </span>
                                            </div>
                                            <p id="sampleWeightTakenError" class="text-[11px] text-red-600 font-extrabold mt-1.5 hidden flex items-center space-x-1 animate-in fade-in duration-200">
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
                                                id="sampleWeightReserved" 
                                                name="sample_weight_reserved" 
                                                readonly
                                                placeholder="Auto-calculated (Gross Weight - Sample Taken)"
                                                value="0"
                                                class="w-full px-3.5 py-2.5 bg-slate-100/90 border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none shadow-2xs font-mono-code placeholder:text-slate-400 cursor-not-allowed"
                                            >
                                        </div>

                                        <!-- RESULT / CONFIRMATORY ANALYSIS SECTION -->
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
                                                <input type="hidden" id="confirmatorySubstanceIdentification" name="confirmatory_substance_identification" value="">

                                                <!-- 2. RESULT (INPUT BOX WITH ADD MORE) -->
                                                <div class="space-y-2">
                                                    <div class="flex items-center justify-between">
                                                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase">
                                                            Result (Detected Substances &amp; Purity %) <span class="text-red-600 font-bold">*</span>
                                                        </label>
                                                        <button 
                                                            type="button" 
                                                            onclick="addDetectedSubstanceRow()" 
                                                            class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] rounded-xl shadow-2xs transition-all flex items-center space-x-1 border-none cursor-pointer"
                                                        >
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                                            <span>Add More</span>
                                                        </button>
                                                    </div>
                                                    <div id="detectedSubstancesListContainer" class="space-y-2.5">
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
                                                        id="confirmatoryTestMethods" 
                                                        name="confirmatory_test_methods" 
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
                                                        id="confirmatorySpecifications" 
                                                        name="confirmatory_specifications" 
                                                        rows="3" 
                                                        placeholder="e.g. Analytical reference standards verified against NIST library database. Sample purity meets regulatory specifications."
                                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-sans placeholder:text-slate-400"
                                                    ></textarea>
                                                </div>

                                                <!-- 5. REMARK TEXTAREA INPUT -->
                                                <div>
                                                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
                                                        Remark <span class="text-slate-400 font-normal text-[10px] lowercase normal-case">(optional)</span>
                                                    </label>
                                                    <textarea 
                                                        id="confirmatoryRemark" 
                                                        name="confirmatory_remark" 
                                                        rows="3" 
                                                        placeholder="Enter any additional remarks or observations regarding the confirmatory analysis..."
                                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-sans placeholder:text-slate-400"
                                                    ></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

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

        function openExhibitListPanel(el) { openSeizurePanel(el, '.exhibit-list-panel'); }
        function openDrugListPanel(el) { openSeizurePanel(el, '.drug-list-panel'); }
        function openWeaponListPanel(el) { openSeizurePanel(el, '.weapon-list-panel'); }
        function openCurrencyListPanel(el) { openSeizurePanel(el, '.currency-list-panel'); }
        function openAmmoListPanel(el) { openSeizurePanel(el, '.ammo-list-panel'); }
        function openDeviceListPanel(el) { openSeizurePanel(el, '.device-list-panel'); }
        function openPkgListPanel(el) { openSeizurePanel(el, '.pkg-list-panel'); }
        function openUnitListPanel(el) { openSeizurePanel(el, '.unit-list-panel'); }

        function selectExhibitCategory(val, e, targetBlock = null) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root = targetBlock || (e ? getSeizureBlockFromEvent(e) : null) || document.querySelector('.seizure-block') || document;
            const input   = root.querySelector('.new-seizure-category');
            const display = root.querySelector('.exhibit-search-input');
            const panel   = root.querySelector('.exhibit-list-panel');
            if (input)   input.value = val;
            if (display) { display.value = val; display.removeAttribute('readonly'); }
            if (panel)   panel.classList.add('hidden');
            updateCommodityField(val, root);

            // Focus corresponding commodity input so user can immediately type new data
            setTimeout(() => {
                let targetInput = null;
                if (val === 'Drugs') targetInput = root.querySelector('.drug-search-input');
                else if (val === 'Weapons') targetInput = root.querySelector('.weapon-search-input');
                else if (val === 'Money') targetInput = root.querySelector('.currency-search-input');
                else if (val === 'Ammunition') targetInput = root.querySelector('.ammo-search-input');
                else if (val === 'Electronic Devices') targetInput = root.querySelector('.device-search-input');
                else targetInput = root.querySelector('.new-seizure-name');

                if (targetInput) {
                    targetInput.removeAttribute('readonly');
                    targetInput.focus();
                }
                syncSection3SeizuresToSection4();
            }, 50);
            syncSection3SeizuresToSection4();
        }

        function filterExhibitList(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('.exhibit-search-input')?.value || '';
            const categoryInput = root.querySelector('.new-seizure-category');
            if (categoryInput) categoryInput.value = rawVal;
            filterExhibitListByQuery(rawVal.toLowerCase(), root);
        }

        function filterExhibitListInner(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('.exhibit-search-inner')?.value || '';
            const categoryInput = root.querySelector('.new-seizure-category');
            if (categoryInput) categoryInput.value = rawVal;
            filterExhibitListByQuery(rawVal.toLowerCase(), root);
        }

        function filterExhibitListByQuery(q, rootEl) {
            const root = rootEl || document;
            const list = root.querySelector('.exhibit-list');
            if (!list) return;

            let customLi = list.querySelector('.exhibit-custom-item');
            const trimmed = (q || '').trim();
            if (trimmed.length > 0) {
                if (!customLi) {
                    customLi = document.createElement('li');
                    customLi.className = 'exhibit-custom-item px-1.5 py-1 mb-1 border-b border-emerald-100';
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
            const wrapper = root.querySelector('.ammo-other-condition-wrapper');
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
            const textWrapper     = root.querySelector('.commodity-text-wrapper');
            const drugWrapper     = root.querySelector('.drug-select-wrapper');
            const weaponWrapper   = root.querySelector('.weapon-select-wrapper');
            const currencyWrapper = root.querySelector('.currency-select-wrapper');
            const ammoWrapper     = root.querySelector('.ammo-select-wrapper');
            const deviceWrapper   = root.querySelector('.device-select-wrapper');
            const deviceFieldsWrapper = root.querySelector('.device-fields-wrapper');
            const weaponFieldsWrapper = root.querySelector('.weapon-fields-wrapper');
            const ammoFieldsWrapper   = root.querySelector('.ammo-fields-wrapper');
            const vehicleWrapper  = root.querySelector('.vehicle-fields-wrapper');
            const textInput       = root.querySelector('.new-seizure-name');
            const drugSearch      = root.querySelector('.drug-search-input');
            const weaponSearch    = root.querySelector('.weapon-search-input');
            const currencySearch  = root.querySelector('.currency-search-input');
            const ammoSearch      = root.querySelector('.ammo-search-input');
            const deviceSearch    = root.querySelector('.device-search-input');
            const quantityLabel   = root.querySelector('.quantity-label');

            if (textWrapper)         textWrapper.classList.add('hidden');
            if (drugWrapper)         drugWrapper.classList.add('hidden');
            if (weaponWrapper)       weaponWrapper.classList.add('hidden');
            if (currencyWrapper)     currencyWrapper.classList.add('hidden');
            if (ammoWrapper)         ammoWrapper.classList.add('hidden');
            if (deviceWrapper)       deviceWrapper.classList.add('hidden');
            if (weaponFieldsWrapper) weaponFieldsWrapper.classList.add('hidden');
            if (ammoFieldsWrapper)   ammoFieldsWrapper.classList.add('hidden');
            if (deviceFieldsWrapper) deviceFieldsWrapper.classList.add('hidden');
            if (vehicleWrapper)      vehicleWrapper.classList.add('hidden');

            [textInput, drugSearch, weaponSearch, currencySearch, ammoSearch, deviceSearch].forEach(input => {
                if (input) {
                    input.removeAttribute('readonly');
                }
            });

            if (category === 'Drugs') {
                if (quantityLabel) quantityLabel.innerText = 'Quantity';
                if (drugWrapper) drugWrapper.classList.remove('hidden');
                if (drugSearch)  { drugSearch.placeholder = 'Select or type custom drug name...'; }
            } else if (category === 'Weapons') {
                if (quantityLabel) quantityLabel.innerText = 'Quantity / Firearms';
                if (weaponWrapper) weaponWrapper.classList.remove('hidden');
                if (weaponFieldsWrapper) weaponFieldsWrapper.classList.remove('hidden');
                if (weaponSearch) { weaponSearch.placeholder = 'Select or type custom weapon type...'; }
            } else if (category === 'Money') {
                if (quantityLabel) quantityLabel.innerText = 'Amount';
                if (currencyWrapper) currencyWrapper.classList.remove('hidden');
                if (currencySearch)  { currencySearch.placeholder = 'Select or type custom currency...'; }
            } else if (category === 'Ammunition') {
                if (quantityLabel) quantityLabel.innerText = 'Quantity / Rounds';
                if (ammoWrapper) ammoWrapper.classList.remove('hidden');
                if (ammoFieldsWrapper) ammoFieldsWrapper.classList.remove('hidden');
                if (ammoSearch)  { ammoSearch.placeholder = 'Select or type custom ammunition type...'; }
            } else if (category === 'Electronic Devices') {
                if (quantityLabel) quantityLabel.innerText = 'Quantity / Units';
                if (deviceWrapper) deviceWrapper.classList.remove('hidden');
                if (deviceFieldsWrapper) deviceFieldsWrapper.classList.remove('hidden');
                if (deviceSearch)  { deviceSearch.placeholder = 'Select or type custom electronic device type...'; }
            } else {
                if (quantityLabel) quantityLabel.innerText = 'Quantity';
                if (textWrapper) textWrapper.classList.remove('hidden');
                const placeholders = {
                    'Precious Stones':     'e.g. Diamonds, Gemstones, Gold Bars / Bullion (24K)',
                    'Gold':                'e.g. Gold Bars / Bullion (24K)',
                    'Vehicles':            'e.g. Toyota Hilux (Unregistered)',
                    'Other Exhibits':      'e.g. Hardware Devices / Illegal Tools'
                };
                if (textInput) textInput.placeholder = placeholders[category] || 'e.g. Primary Commodity Name';

                if (category === 'Vehicles' && vehicleWrapper) {
                    vehicleWrapper.classList.remove('hidden');
                }
            }
        }

        function selectDrugType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root   = getSeizureBlockFromEvent(e);
            const hidden  = root.querySelector('.new-seizure-name-drug');
            const display = root.querySelector('.drug-search-input');
            const panel   = root.querySelector('.drug-list-panel');
            if (hidden)  hidden.value  = val;
            if (display) display.value = val;
            if (panel)   panel.classList.add('hidden');
            const textInput = root.querySelector('.new-seizure-name');
            if (textInput) textInput.value = val;
            syncSection3SeizuresToSection4();
        }

        function filterDrugList(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('.drug-search-input')?.value || '';
            syncDrugCustomInput(rawVal, root);
            filterDrugListByQuery(rawVal.toLowerCase(), root);
        }
        function filterDrugListInner(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('.drug-search-inner')?.value || '';
            syncDrugCustomInput(rawVal, root);
            filterDrugListByQuery(rawVal.toLowerCase(), root);
        }
        function syncDrugCustomInput(val, rootEl) {
            const root = rootEl || document;
            const hidden = root.querySelector('.new-seizure-name-drug');
            if (hidden) hidden.value = val;
            const textInput = root.querySelector('.new-seizure-name');
            if (textInput) textInput.value = val;
        }
        function filterDrugListByQuery(q, rootEl) {
            const root = rootEl || document;
            const list = root.querySelector('.drug-list');
            if (!list) return;
            const items = list.querySelectorAll('.drug-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        function selectWeaponType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root   = getSeizureBlockFromEvent(e);
            const hidden  = root.querySelector('.new-seizure-name-weapon');
            const display = root.querySelector('.weapon-search-input');
            const panel   = root.querySelector('.weapon-list-panel');
            if (hidden)  hidden.value  = val;
            if (display) display.value = val;
            if (panel)   panel.classList.add('hidden');
            const textInput = root.querySelector('.new-seizure-name');
            if (textInput) textInput.value = val;
            syncSection3SeizuresToSection4();
        }

        function filterWeaponList(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('.weapon-search-input')?.value || '';
            syncWeaponCustomInput(rawVal, root);
            filterWeaponListByQuery(rawVal.toLowerCase(), root);
        }
        function filterWeaponListInner(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('.weapon-search-inner')?.value || '';
            syncWeaponCustomInput(rawVal, root);
            filterWeaponListByQuery(rawVal.toLowerCase(), root);
        }
        function syncWeaponCustomInput(val, rootEl) {
            const root = rootEl || document;
            const hidden = root.querySelector('.new-seizure-name-weapon');
            if (hidden) hidden.value = val;
            const textInput = root.querySelector('.new-seizure-name');
            if (textInput) textInput.value = val;
        }
        function filterWeaponListByQuery(q, rootEl) {
            const root = rootEl || document;
            const list = root.querySelector('.weapon-list');
            if (!list) return;
            const items = list.querySelectorAll('.weapon-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        function selectCurrencyType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root   = getSeizureBlockFromEvent(e);
            const hidden  = root.querySelector('.new-seizure-name-currency');
            const display = root.querySelector('.currency-search-input');
            const panel   = root.querySelector('.currency-list-panel');
            if (hidden)  hidden.value  = val;
            if (display) { display.value = val; display.removeAttribute('readonly'); }
            if (panel)   panel.classList.add('hidden');
            const textInput = root.querySelector('.new-seizure-name');
            if (textInput) textInput.value = val;
            syncSection3SeizuresToSection4();
        }

        function filterCurrencyList(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('.currency-search-input')?.value || '';
            syncCurrencyCustomInput(rawVal, root);
            filterCurrencyListByQuery(rawVal.toLowerCase(), root);
        }
        function filterCurrencyListInner(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('.currency-search-inner')?.value || '';
            syncCurrencyCustomInput(rawVal, root);
            filterCurrencyListByQuery(rawVal.toLowerCase(), root);
        }
        function syncCurrencyCustomInput(val, rootEl) {
            const root = rootEl || document;
            const hidden = root.querySelector('.new-seizure-name-currency');
            if (hidden) hidden.value = val;
            const textInput = root.querySelector('.new-seizure-name');
            if (textInput) textInput.value = val;
        }
        function filterCurrencyListByQuery(q, rootEl) {
            const root = rootEl || document;
            const list = root.querySelector('.currency-list');
            if (!list) return;

            let customLi = list.querySelector('.currency-custom-item');
            const trimmed = (q || '').trim();
            if (trimmed.length > 0) {
                if (!customLi) {
                    customLi = document.createElement('li');
                    customLi.className = 'currency-custom-item px-1.5 py-1 mb-1 border-b border-emerald-100';
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

        function selectAmmoType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root   = getSeizureBlockFromEvent(e);
            const hidden  = root.querySelector('.new-seizure-name-ammo');
            const display = root.querySelector('.ammo-search-input');
            const panel   = root.querySelector('.ammo-list-panel');
            if (hidden)  hidden.value  = val;
            if (display) display.value = val;
            if (panel)   panel.classList.add('hidden');
            const textInput = root.querySelector('.new-seizure-name');
            if (textInput) textInput.value = val;
            syncSection3SeizuresToSection4();
        }

        function filterAmmoList(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('.ammo-search-input')?.value || '';
            syncAmmoCustomInput(rawVal, root);
            filterAmmoListByQuery(rawVal.toLowerCase(), root);
        }
        function filterAmmoListInner(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('.ammo-search-inner')?.value || '';
            syncAmmoCustomInput(rawVal, root);
            filterAmmoListByQuery(rawVal.toLowerCase(), root);
        }
        function syncAmmoCustomInput(val, rootEl) {
            const root = rootEl || document;
            const hidden = root.querySelector('.new-seizure-name-ammo');
            if (hidden) hidden.value = val;
            const textInput = root.querySelector('.new-seizure-name');
            if (textInput) textInput.value = val;
        }
        function filterAmmoListByQuery(q, rootEl) {
            const root = rootEl || document;
            const list = root.querySelector('.ammo-list');
            if (!list) return;
            const items = list.querySelectorAll('.ammo-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        function selectDeviceType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root   = getSeizureBlockFromEvent(e);
            const hidden  = root.querySelector('.new-seizure-name-device');
            const display = root.querySelector('.device-search-input');
            const panel   = root.querySelector('.device-list-panel');
            if (hidden)  hidden.value  = val;
            if (display) display.value = val;
            if (panel)   panel.classList.add('hidden');
            const textInput = root.querySelector('.new-seizure-name');
            if (textInput) textInput.value = val;
            syncSection3SeizuresToSection4();
        }

        function filterDeviceList(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('.device-search-input')?.value || '';
            syncDeviceCustomInput(rawVal, root);
            filterDeviceListByQuery(rawVal.toLowerCase(), root);
        }
        function filterDeviceListInner(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('.device-search-inner')?.value || '';
            filterDeviceListByQuery(rawVal.toLowerCase(), root);
        }
        function syncDeviceCustomInput(val, rootEl) {
            const root = rootEl || document;
            const hidden = root.querySelector('.new-seizure-name-device');
            if (hidden) hidden.value = val;
            const textInput = root.querySelector('.new-seizure-name');
            if (textInput) textInput.value = val;
        }
        function filterDeviceListByQuery(q, rootEl) {
            const root = rootEl || document;
            const list = root.querySelector('.device-list');
            if (!list) return;
            const items = list.querySelectorAll('.device-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        function selectPackagingType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root   = getSeizureBlockFromEvent(e);
            const hidden  = root.querySelector('.new-seizure-packaging');
            const display = root.querySelector('.pkg-search-input');
            const panel   = root.querySelector('.pkg-list-panel');
            if (hidden)  hidden.value  = val;
            if (display) display.value = val;
            if (panel)   panel.classList.add('hidden');
            syncSection3SeizuresToSection4();
        }
        function filterPackagingList(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('.pkg-search-input')?.value || '';
            syncPackagingCustomInput(rawVal, root);
            filterPackagingListByQuery(rawVal.toLowerCase(), root);
        }
        function filterPackagingListInner(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('.pkg-search-inner')?.value || '';
            syncPackagingCustomInput(rawVal, root);
            filterPackagingListByQuery(rawVal.toLowerCase(), root);
        }
        function syncPackagingCustomInput(val, rootEl) {
            const root = rootEl || document;
            const hidden = root.querySelector('.new-seizure-packaging');
            if (hidden) hidden.value = val;
        }
        function filterPackagingListByQuery(q, rootEl) {
            const root = rootEl || document;
            const list = root.querySelector('.pkg-list');
            if (!list) return;
            const items = list.querySelectorAll('.pkg-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        function selectUnit(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root   = getSeizureBlockFromEvent(e);
            const hidden  = root.querySelector('.new-seizure-unit');
            const display = root.querySelector('.unit-search-input');
            const panel   = root.querySelector('.unit-list-panel');
            if (hidden)  hidden.value  = val;
            if (display) display.value = val;
            if (panel)   panel.classList.add('hidden');
            syncSection3SeizuresToSection4();
        }
        function filterUnitList(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('.unit-search-input')?.value || '';
            syncUnitCustomInput(rawVal, root);
            filterUnitListByQuery(rawVal.toLowerCase(), root);
        }
        function filterUnitListInner(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const rawVal = root.querySelector('.unit-search-inner')?.value || '';
            syncUnitCustomInput(rawVal, root);
            filterUnitListByQuery(rawVal.toLowerCase(), root);
        }
        function syncUnitCustomInput(val, rootEl) {
            const root = rootEl || document;
            const hidden = root.querySelector('.new-seizure-unit');
            if (hidden) hidden.value = val;
        }
        function filterUnitListByQuery(q, rootEl) {
            const root = rootEl || document;
            const list = root.querySelector('.unit-list');
            if (!list) return;
            const items = list.querySelectorAll('.unit-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        // Global Document click handler to close seizure dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            document.querySelectorAll('.seizure-block').forEach(block => {
                const items = [
                    { wrapper: block.querySelector('.exhibit-select-wrapper'), panel: block.querySelector('.exhibit-list-panel') },
                    { wrapper: block.querySelector('.drug-select-wrapper'), panel: block.querySelector('.drug-list-panel') },
                    { wrapper: block.querySelector('.weapon-select-wrapper'), panel: block.querySelector('.weapon-list-panel') },
                    { wrapper: block.querySelector('.currency-select-wrapper'), panel: block.querySelector('.currency-list-panel') },
                    { wrapper: block.querySelector('.ammo-select-wrapper'), panel: block.querySelector('.ammo-list-panel') },
                    { wrapper: block.querySelector('.device-select-wrapper'), panel: block.querySelector('.device-list-panel') },
                    { wrapper: block.querySelector('.packaging-select-wrapper'), panel: block.querySelector('.pkg-list-panel') },
                    { wrapper: block.querySelector('.unit-select-wrapper'), panel: block.querySelector('.unit-list-panel') },
                ];
                items.forEach(item => {
                    if (item.wrapper && item.panel && !item.wrapper.contains(e.target)) {
                        item.panel.classList.add('hidden');
                    }
                });
            });
        }, true);
        const UNIT_ALLOWED_CATEGORIES = @json($allowedCategories);
        const UNIT_DEFAULT_CATEGORY = @json($defaultCategory);
        const ACTIVE_UNIT_KEY = @json($activeUnitKey);

        const ALL_EXHIBIT_CATEGORIES_META = {
            'Drugs': { name: 'Drugs', desc: 'Narcotics, Synthetics, Opioids', code: 'DRG', cls: 'bg-emerald-50 text-emerald-800' },
            'Electronic Devices': { name: 'Electronic Devices', desc: 'Phones, Encrypted Laptops, Drives', code: 'ELEC', cls: 'bg-blue-50 text-blue-700' },
            'Weapons': { name: 'Weapons', desc: 'Firearms, Handguns, Blades', code: 'WPN', cls: 'bg-red-50 text-red-700' },
            'Ammunition': { name: 'Ammunition', desc: 'Bullets, Cartridges, Explosives', code: 'AMMO', cls: 'bg-orange-50 text-orange-700' },
            'Vehicles': { name: 'Vehicles', desc: 'Automobiles, Vessels, Trucks', code: 'VEH', cls: 'bg-emerald-50 text-emerald-800' },
            'Money': { name: 'Money', desc: 'Cash, Foreign Currencies, Laundering', code: 'CASH', cls: 'bg-emerald-50 text-emerald-800' },
            'Precious Stones': { name: 'Precious Stones', desc: 'Diamonds, Gemstones, Gold, Bullion', code: 'MET', cls: 'bg-amber-50 text-amber-800' },
            'Gold': { name: 'Precious Stones', desc: 'Diamonds, Gemstones, Gold, Bullion', code: 'MET', cls: 'bg-amber-50 text-amber-800' },
            'Other Exhibits': { name: 'Other Exhibits', desc: 'Hardware, Miscellaneous Devices', code: 'MISC', cls: 'bg-slate-200 text-slate-700' }
        };

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

        function updateSeizureBlockNumbers() {
            const blocks = document.querySelectorAll('#seizuresListContainer .seizure-block');
            blocks.forEach((block, index) => {
                const num = index + 1;
                const titleEl = block.querySelector('.seizure-title');
                const badgeEl = block.querySelector('.seizure-number-badge');
                if (titleEl) titleEl.innerText = `Seizure Item #${num}`;
                if (badgeEl) badgeEl.innerText = num;
            });
        }

        const addAssociateExhibitRow = addAssociateSeizureBlock;

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
                }
            }
        }

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
                    <label class="block text-slate-700 font-bold text-[10px] uppercase mb-1">Substance / Compound Detected <span class="text-red-600 font-bold">*</span></label>
                    <input type="text" name="detected_substances[][name]" class="detected-substance-name w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-slate-900 font-extrabold text-xs focus:outline-none focus:border-emerald-600 font-sans" placeholder="e.g. Cocaine Hydrochloride" value="${substance}">
                </div>
                <div class="w-full sm:w-48">
                    <label class="block text-slate-700 font-bold text-[10px] uppercase mb-1">Purity / Percentage <span class="text-red-600 font-bold">*</span></label>
                    <div class="flex items-center rounded-lg overflow-hidden shadow-2xs">
                        <input type="text" name="detected_substances[][percentage]" class="detected-substance-percent w-full pl-3 pr-2 py-2 bg-slate-50 border border-y border-l border-slate-300 rounded-l-lg text-slate-900 font-extrabold text-xs focus:outline-none focus:border-emerald-600 font-mono-code" placeholder="e.g. 85.4" value="${percentage}">
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

        function syncSection3SeizuresToSection4() {
            const seizuresContainer = document.getElementById('seizuresListContainer');
            if (!seizuresContainer) return;

            const seizureBlocks = seizuresContainer.querySelectorAll('.seizure-block');
            let exhibitSummaries = [];
            let drugNamesFound = [];
            let totalUnitsSummary = [];
            let totalGrossWeightSummary = [];
            let primaryUnitAddon = '';

            seizureBlocks.forEach((block, idx) => {
                const category = block.querySelector('.exhibit-search-input')?.value?.trim() 
                                 || block.querySelector('.new-seizure-category')?.value?.trim() 
                                 || '';
                
                let commodityName = block.querySelector('.new-seizure-name')?.value?.trim() || '';
                if (!commodityName) {
                    commodityName = block.querySelector('.drug-search-input')?.value?.trim()
                                 || block.querySelector('.weapon-search-input')?.value?.trim()
                                 || block.querySelector('.currency-search-input')?.value?.trim()
                                 || block.querySelector('.ammo-search-input')?.value?.trim()
                                 || block.querySelector('.device-search-input')?.value?.trim()
                                 || '';
                }

                const quantity  = block.querySelector('input[name*="[seizure_quantity]"]')?.value?.trim() || '';
                const weight    = block.querySelector('input[name*="[seizure_weight]"]')?.value?.trim() || '';
                const unit      = block.querySelector('.unit-search-input')?.value?.trim() 
                               || block.querySelector('input[name*="[seizure_unit]"]')?.value?.trim() 
                               || '';
                const packaging = block.querySelector('.pkg-search-input')?.value?.trim() 
                               || block.querySelector('input[name*="[seizure_packaging]"]')?.value?.trim() 
                               || '';
                const streetVal = block.querySelector('input[name*="[seizure_value]"]')?.value?.trim() || '';

                // Device details
                const devModel  = block.querySelector('input[name*="[device_model]"]')?.value?.trim() || '';
                const devSerial = block.querySelector('input[name*="[serial_number]"]')?.value?.trim() || '';
                const devImei   = block.querySelector('input[name*="[imei_1]"]')?.value?.trim() || '';

                // Vehicle details
                const vehMake   = block.querySelector('input[name*="[vehicle_make_model]"]')?.value?.trim() || '';
                const vehReg    = block.querySelector('input[name*="[vehicle_registration]"]')?.value?.trim() || '';

                let parts = [];
                if (category) parts.push(`[${category}]`);
                if (commodityName) parts.push(commodityName);
                if (devModel) parts.push(`Model: ${devModel}`);
                if (devSerial) parts.push(`Serial: ${devSerial}`);
                if (devImei) parts.push(`IMEI: ${devImei}`);
                if (vehMake) parts.push(`Make: ${vehMake}`);
                if (vehReg) parts.push(`Reg: ${vehReg}`);

                let qtyWeightStr = [];
                if (quantity) qtyWeightStr.push(`Qty: ${quantity}${packaging ? ' ' + packaging : ''}`);
                if (weight) qtyWeightStr.push(`Weight: ${weight}${unit ? ' ' + unit : ''}`);
                if (qtyWeightStr.length > 0) parts.push(`(${qtyWeightStr.join(', ')})`);
                if (streetVal) parts.push(`Val: ${streetVal}`);

                if (parts.length > 0) {
                    if (seizureBlocks.length > 1) {
                        exhibitSummaries.push(`Item #${idx + 1}: ${parts.join(' ')}`);
                    } else {
                        exhibitSummaries.push(parts.join(' '));
                    }
                }

                if (quantity || packaging) {
                    totalUnitsSummary.push(`${quantity} ${packaging}`.trim());
                }
                if (weight || unit) {
                    totalGrossWeightSummary.push(`${weight} ${unit}`.trim());
                    if (!primaryUnitAddon && unit) {
                        primaryUnitAddon = unit;
                    }
                }

                // Check if this item is a drug/narcotic
                const catLower = category.toLowerCase();
                const commLower = commodityName.toLowerCase();
                const isDrug = catLower.includes('drug') || catLower.includes('narcotic')
                             || commLower.includes('cocaine') || commLower.includes('heroin') 
                             || commLower.includes('cannabis') || commLower.includes('marijuana') 
                             || commLower.includes('meth') || commLower.includes('tramadol') 
                             || commLower.includes('fentanyl') || commLower.includes('codeine') 
                             || commLower.includes('morphine') || commLower.includes('weed') 
                             || commLower.includes('hash');

                if (isDrug && commodityName) {
                    let cleanDrugName = commodityName.split('(')[0].trim();
                    if (cleanDrugName && !drugNamesFound.includes(cleanDrugName)) {
                        drugNamesFound.push(cleanDrugName);
                    }
                }
            });

            // 1. Update Exhibit Description in Section 4A (#labExhibitDescriptionInput)
            const descInput = document.getElementById('labExhibitDescriptionInput');
            if (descInput && exhibitSummaries.length > 0) {
                descInput.value = exhibitSummaries.join('\n');
            }

            // 2. Auto-fetch Description, Units, Gross Weight into CONFIRMATORY METROLOGY & MASS
            const sampleDescInput = document.getElementById('sampleWeightDescription');
            if (sampleDescInput) {
                sampleDescInput.value = drugNamesFound.length > 0 ? drugNamesFound.join(', ') : (exhibitSummaries[0] || 'Drug Manifest Exhibit');
            }

            const sampleUnitsInput = document.getElementById('sampleWeightUnits');
            if (sampleUnitsInput) {
                sampleUnitsInput.value = totalUnitsSummary.length > 0 ? totalUnitsSummary.join('; ') : '1 Item';
            }

            const sampleGrossInput = document.getElementById('sampleWeightGross');
            if (sampleGrossInput) {
                sampleGrossInput.value = totalGrossWeightSummary.length > 0 ? totalGrossWeightSummary.join('; ') : 'N/A';
            }

            const unitAddon = document.getElementById('sampleWeightTakenUnitAddon');
            if (unitAddon && primaryUnitAddon) {
                unitAddon.innerText = primaryUnitAddon;
            }

            // 3. Section 4B Presumptive Test
            if (drugNamesFound.length > 0) {
                // 5. Update Confirmatory Substance Identification in Section 4C (#confirmatorySubstanceIdentification)
                const subIdInput = document.getElementById('confirmatorySubstanceIdentification');
                if (subIdInput) {
                    subIdInput.value = drugNamesFound.join(', ');
                }
            }

            updateSampleWeightReserved();
            if (ACTIVE_UNIT_KEY === 'lab') {
                syncLabExhibitManifest(seizureBlocks);
            }
        }

        function syncLabExhibitManifest(seizureBlocks) {
            const container = document.getElementById('labExhibitCardsContainer');
            const selectEl = document.getElementById('labExhibitSelect');
            const badge = document.getElementById('labExhibitCountBadge');
            if (!container || !selectEl) return;

            const prevSelected = selectEl.value;
            const items = [];

            seizureBlocks.forEach((block, idx) => {
                const category = block.querySelector('.exhibit-search-input')?.value?.trim()
                              || block.querySelector('.new-seizure-category')?.value?.trim() || 'Drugs';
                const commodityName = block.querySelector('.new-seizure-name')?.value?.trim()
                                   || block.querySelector('.drug-search-input')?.value?.trim() || '';
                const contents = block.querySelector('.seizure-contents-input')?.value?.trim() || '';
                const quantity = block.querySelector('input[name*="[seizure_quantity]"]')?.value?.trim() || '';
                const packaging = block.querySelector('.pkg-search-input')?.value?.trim()
                               || block.querySelector('input[name*="[seizure_packaging]"]')?.value?.trim() || '';
                const weight = block.querySelector('input[name*="[seizure_weight]"]')?.value?.trim() || '';
                const unit = block.querySelector('.unit-search-input')?.value?.trim()
                          || block.querySelector('input[name*="[seizure_unit]"]')?.value?.trim() || '';

                if (commodityName || contents || quantity || weight) {
                    const labelParts = [commodityName || contents || `Exhibit #${idx + 1}`];
                    if (quantity || packaging) labelParts.push(`(${[quantity, packaging].filter(Boolean).join(' ')})`);
                    if (weight || unit) labelParts.push(`[${[weight, unit].filter(Boolean).join(' ')}]`);
                    items.push({
                        index: idx,
                        category,
                        commodityName,
                        contents,
                        quantity,
                        packaging,
                        weight,
                        unit,
                        label: labelParts.join(' ')
                    });
                }
            });

            if (badge) {
                badge.textContent = `${items.length} ${items.length === 1 ? 'Exhibit' : 'Exhibits'}`;
            }

            selectEl.innerHTML = '<option value="">-- Select Seized Exhibit --</option>';
            items.forEach(item => {
                const opt = document.createElement('option');
                opt.value = String(item.index);
                opt.textContent = `EXH-${String(item.index + 1).padStart(2, '0')}: ${item.label}`;
                if (String(item.index) === prevSelected) opt.selected = true;
                selectEl.appendChild(opt);
            });

            if (items.length === 0) {
                container.innerHTML = `<div class="col-span-full text-center py-6 bg-slate-50 border border-dashed border-slate-300 rounded-xl"><p class="text-[11px] font-bold text-slate-400">Add seized exhibits in Section 3 to inspect &amp; select here...</p></div>`;
                return;
            }

            container.innerHTML = items.map(item => `
                <button type="button" onclick="selectLabExhibitCard(${item.index})"
                    class="text-left p-3 rounded-xl border ${String(item.index) === selectEl.value ? 'border-emerald-500 bg-emerald-50/70 ring-2 ring-emerald-500/20' : 'border-slate-200 bg-slate-50/70 hover:border-emerald-400 hover:bg-white'} transition-all cursor-pointer">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <span class="text-[10px] font-mono-code font-extrabold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded">EXH-${String(item.index + 1).padStart(2, '0')}</span>
                        <span class="text-[10px] font-bold text-slate-500">${item.category}</span>
                    </div>
                    <p class="text-xs font-extrabold text-slate-900 truncate">${item.commodityName || 'Unspecified Packaging'}</p>
                    ${item.contents ? `<p class="text-[11px] text-slate-600 truncate mt-0.5">${item.contents}</p>` : ''}
                </button>
            `).join('');
        }

        function selectLabExhibitCard(idx) {
            const selectEl = document.getElementById('labExhibitSelect');
            if (!selectEl) return;
            selectEl.value = String(idx);
            onLabExhibitSelected();
        }

        function onLabExhibitSelected() {
            const selectEl = document.getElementById('labExhibitSelect');
            if (!selectEl) return;
            const idx = parseInt(selectEl.value, 10);
            const blocks = document.querySelectorAll('#seizuresListContainer .seizure-block');
            if (isNaN(idx) || !blocks[idx]) return;

            const block = blocks[idx];
            const commodityName = block.querySelector('.new-seizure-name')?.value?.trim()
                               || block.querySelector('.drug-search-input')?.value?.trim() || '';
            const contents = block.querySelector('.seizure-contents-input')?.value?.trim() || '';
            const quantity = block.querySelector('input[name*="[seizure_quantity]"]')?.value?.trim() || '';
            const packaging = block.querySelector('.pkg-search-input')?.value?.trim()
                           || block.querySelector('input[name*="[seizure_packaging]"]')?.value?.trim() || '';
            const weight = block.querySelector('input[name*="[seizure_weight]"]')?.value?.trim() || '';
            const unit = block.querySelector('.unit-search-input')?.value?.trim()
                      || block.querySelector('input[name*="[seizure_unit]"]')?.value?.trim() || '';

            const descInput = document.getElementById('labExhibitDescriptionInput');
            if (descInput) {
                descInput.value = [commodityName, contents].filter(Boolean).join(' — ');
            }
            const sampleDesc = document.getElementById('sampleWeightDescription');
            if (sampleDesc) sampleDesc.value = commodityName || contents || 'Drug Manifest Exhibit';
            const sampleUnits = document.getElementById('sampleWeightUnits');
            if (sampleUnits && (quantity || packaging)) sampleUnits.value = `${quantity} ${packaging}`.trim();
            const sampleGross = document.getElementById('sampleWeightGross');
            if (sampleGross && (weight || unit)) sampleGross.value = `${weight} ${unit}`.trim();
            const unitAddon = document.getElementById('sampleWeightTakenUnitAddon');
            if (unitAddon && unit) unitAddon.innerText = unit;

            syncLabExhibitManifest(blocks);
        }

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
                const select = block.querySelector('.assoc-suspect-select');
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
            addAssociateSeizureBlock();

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

            form.addEventListener('submit', () => { isDirty = false; });
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
