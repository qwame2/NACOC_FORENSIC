<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @php
        $authUser = auth()->user();
        $userUnit = $authUser->unit ?? session('user_unit') ?? request()->query('unit') ?? '';
        $isLabAnalysis = isset($isLabAnalysis) ? $isLabAnalysis : (
            str_contains(strtolower($userUnit), 'lab') 
            || request()->query('unit') === 'Lab Analysis' 
            || request()->query('unit') === 'lab' 
            || session('user_unit') === 'Lab Analysis'
            || request()->is('Lab_Analysis*')
            || request()->query('from') === 'lab'
        );
        $officerName = auth()->user()->fullname ?? session('user_name') ?? 'Handling Officer';
        $dData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
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
                'marital_status'   => $case->marital_status ?? 'Single',
                'holding_facility' => $case->holding_facility ?? 'NACOC Holding Cell',
                'offense'          => $case->offense_description ?? $case->description ?? null,
                'date_of_arrest'   => $case->arrest_date ?? null,
                'mugshot_front'    => $case->mugshot_front ?? ($dData['mugshot_front'] ?? null),
                'mugshot_left'     => $case->mugshot_left ?? ($dData['mugshot_left'] ?? null),
                'mugshot_right'    => $case->mugshot_right ?? ($dData['mugshot_right'] ?? null),
            ]];

        $existingSeizures = (isset($dData['seizures']) && is_array($dData['seizures']) && count($dData['seizures']) > 0)
            ? $dData['seizures']
            : ($case->seizure_name || $case->exhibits_summary ? [[
                'category'         => $case->exhibit_category ?? 'General Exhibit',
                'seizure_name'     => $case->seizure_name ?? $case->exhibits_summary ?? 'Logged Exhibit Package',
                'seizure_quantity' => $case->seizure_quantity ?? 'N/A',
                'seizure_weight'   => $case->seizure_weight ? ($case->seizure_weight . ' ' . ($case->seizure_unit ?? '')) : 'N/A',
                'seizure_value'    => $case->seizure_value ?? $case->valuation ?? 'N/A'
            ]] : []);
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Associate Case Data - Suspect Docket Portal | NACOC Forensic</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- jQuery & Select2 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
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
            padding-right: 1.5rem !important;
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
                    <span class="px-2.5 py-1 bg-blue-100 text-blue-700 text-[10px] font-mono-code font-bold rounded-lg uppercase tracking-wider">
                        CASE ASSOCIATION PORTAL
                    </span>
                    <span class="text-sm font-extrabold text-slate-900 font-mono-code">
                        REF: {{ $case->case_number }}
                    </span>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ url('/cases') . ($isLabAnalysis ? '?unit=Lab+Analysis' : '') }}" class="px-4 py-2 bg-slate-200/80 hover:bg-slate-300 border-none text-slate-800 text-xs font-bold rounded-xl shadow-xs transition-all flex items-center space-x-1.5 cursor-pointer">
                        <span>&larr;</span>
                        <span>Back to Case Directory</span>
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
                            <span class="px-2.5 py-0.5 bg-blue-600 text-white text-[10px] font-extrabold font-mono-code rounded-md uppercase tracking-wider">CASE ASSOCIATIONS</span>
                            <span class="text-xs text-slate-400">&bull;</span>
                            <span class="text-xs font-bold text-slate-700 font-mono-code">NACOC FORENSIC DIRECTORATE</span>
                        </div>
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Associate Case Data &amp; Suspect Docket Portal</h1>
                        <p class="text-xs text-slate-600 mt-1 font-medium">Link new accomplices, co-suspects, informants, or seized exhibits to Case <strong class="text-blue-600 font-mono-code">{{ $case->case_number }}</strong> ({{ $case->title }})</p>
                    </div>
                    <div class="flex items-center space-x-2 shrink-0">
                        <span class="px-3 py-1.5 bg-white border border-slate-300 rounded-xl text-xs font-mono-code font-bold text-slate-700 shadow-2xs">
                            Status: <span class="text-emerald-600">{{ $case->status }}</span>
                        </span>
                    </div>
                </div>

                <!-- MAIN FORM WRAPPER -->
                <form id="associateDocketForm" action="/cases/{{ $case->case_number }}/associate" method="POST" autocomplete="off" class="space-y-6 text-xs font-medium">
                    @csrf

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
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black mb-1">Case Reference #</label>
                                <input type="text" value="{{ $case->case_number }}" readonly class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold font-mono-code text-blue-600 cursor-not-allowed">
                            </div>
                            <div>
                                <label class="block text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black mb-1">Case Legal Title</label>
                                <input type="text" value="{{ $case->title }}" readonly class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-800 cursor-not-allowed">
                            </div>
                            <div>
                                <label class="block text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black mb-1">Submitting Officer</label>
                                <input type="text" value="{{ $case->submitting_officer ?? $case->commanding_officer ?? 'Handling Officer' }}" readonly class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-800 cursor-not-allowed">
                            </div>
                            <div>
                                <label class="block text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black mb-1">Arrest Location / Vault</label>
                                <input type="text" value="{{ $case->location ?? 'NACOC Vault Storage' }}" readonly class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-800 cursor-not-allowed">
                            </div>
                        </div>

                        <!-- SAVED SUSPECTS DETAILED CARDS & MUGSHOTS (READ ONLY) -->
                        <div class="space-y-4 pt-2">
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
                                        $sPOB        = $s['pob'] ?? ($sNum == 1 ? ($case->pob ?? null) : null);
                                        $sPhone      = $s['phone'] ?? ($sNum == 1 ? ($case->phone ?? null) : null);
                                        $sAddr       = $s['address'] ?? ($sNum == 1 ? ($case->address ?? null) : null);
                                        $sMarital    = $s['marital_status'] ?? ($sNum == 1 ? ($case->marital_status ?? 'Single') : 'Single');
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
                                        $sFacility   = $s['holding_facility'] ?? ($sNum == 1 ? ($case->holding_facility ?? 'NACOC Holding Facility') : 'NACOC Holding Facility');
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
                                                    <span class="w-2 h-2 rounded-full bg-blue-600 inline-block"></span>
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
                                                    <span class="w-2 h-2 rounded-full bg-emerald-600 inline-block"></span>
                                                    <span>2. Family &amp; Relationship Records</span>
                                                </div>
                                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-[11px] bg-slate-50/70 p-3.5 rounded-xl border border-slate-200/80">
                                                    <div>
                                                        <span class="text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black block mb-0.5">Marital Status:</span>
                                                        <strong class="text-slate-800">{{ $sMarital ?: 'Single' }}</strong>
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
                                                        <strong class="text-slate-800">{{ $sDependents ?: 'No Children' }}</strong>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- SUB-SECTION 3: ARREST, OFFENSE & CUSTODY CELL ALLOCATION -->
                                            <div class="space-y-2">
                                                <div class="text-[11px] font-extrabold text-slate-800 uppercase tracking-wider font-mono-code flex items-center space-x-1.5">
                                                    <span class="w-2 h-2 rounded-full bg-amber-600 inline-block"></span>
                                                    <span>3. Arrest Incident, Offense &amp; Custody Cell</span>
                                                </div>
                                                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-[11px] bg-slate-50/70 p-3.5 rounded-xl border border-slate-200/80">
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
                                                        <span class="text-slate-900 font-extrabold text-[11px] font-mono-code uppercase text-black block mb-0.5">Allocated Holding Facility:</span>
                                                        <strong class="text-emerald-700 font-extrabold">{{ $sFacility ?: 'NACOC Holding Cell' }}</strong>
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
                                            <th class="py-2.5 px-4 font-bold">Category</th>
                                            <th class="py-2.5 px-4 font-bold">Exhibit Name / Description</th>
                                            <th class="py-2.5 px-4 font-bold">Quantity</th>
                                            <th class="py-2.5 px-4 font-bold">Weight</th>
                                            <th class="py-2.5 px-4 font-bold">Est. Value</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-slate-800">
                                        @forelse($existingSeizures as $ex)
                                            <tr>
                                                <td class="py-3 px-4 font-bold font-mono-code text-blue-600">{{ $ex['category'] ?? 'General Exhibit' }}</td>
                                                <td class="py-3 px-4 font-extrabold text-slate-900">{{ $ex['seizure_name'] ?? $ex['name'] ?? 'N/A' }}</td>
                                                <td class="py-3 px-4 font-mono-code">{{ $ex['seizure_quantity'] ?? $ex['quantity'] ?? 'N/A' }}</td>
                                                <td class="py-3 px-4 font-mono-code">{{ $ex['seizure_weight'] ?? $ex['weight'] ?? 'N/A' }}</td>
                                                <td class="py-3 px-4 font-mono-code font-bold text-emerald-700">{{ $ex['seizure_value'] ?? $ex['value'] ?? 'N/A' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="py-4 text-center text-slate-400 font-mono-code">No saved exhibits logged.</td>
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
                            <button type="button" onclick="addAssociatePersonCard()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-extrabold rounded-xl shadow-md transition-all flex items-center space-x-1.5 cursor-pointer border-none">
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
                    <div class="bg-white p-6 rounded-2xl border border-emerald-200 shadow-sm space-y-5 relative">
                        
                        <div class="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-md shadow-emerald-600/30">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                                <div>
                                    <h3 class="font-black text-slate-900 text-base">Section 3: Add Associated Seizures &amp; Exhibits</h3>
                                    <p class="text-xs text-slate-500">Record illicit substance types, weight/quantity, packaging, street valuation, or property linked to this investigation</p>
                                </div>
                            </div>
                            <button type="button" onclick="addAssociateSeizureBlock()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold rounded-xl shadow-md transition-all flex items-center space-x-1.5 cursor-pointer border-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                <span>+ Add Seizure Block</span>
                            </button>
                        </div>

                        <!-- CONTAINER FOR DYNAMIC SEIZURE BLOCKS -->
                        <div id="seizuresListContainer" class="space-y-6">
                            <!-- Rows added dynamically via JS -->
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
                            <button type="submit" id="submitAssocFormBtn" class="w-1/2 sm:w-auto px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold rounded-xl shadow-lg shadow-emerald-600/30 flex items-center justify-center space-x-2 transition-all hover:scale-[1.02] cursor-pointer border-none">
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

        const worldNationalitiesList = [
            "Ghanaian", "Afghan", "Albanian", "Algerian", "American", "Andorran", "Angolan", "Antiguan and Barbudan", "Argentine", "Armenian",
            "Australian", "Austrian", "Azerbaijani", "Bahamian", "Bahraini", "Bangladeshi", "Barbadian", "Belarusian", "Belgian", "Belizean",
            "Beninese", "Bhutanese", "Bolivian", "Bosnian", "Motswana (Botswana)", "Brazilian", "British", "Bruneian", "Bulgarian", "Burkinabe (Burkina Faso)",
            "Burundian", "Cabo Verdean", "Cambodian", "Cameroonian", "Canadian", "Central African", "Chadian", "Chilean", "Chinese", "Colombian",
            "Comorian", "Congolese (Brazzaville)", "Congolese (Kinshasa)", "Costa Rican", "Ivorian (Côte d'Ivoire)", "Croatian", "Cuban", "Cypriot", "Czech",
            "Dane (Danish)", "Djiboutian", "Dominican (Dominica)", "Dominican (Dominican Republic)", "Dutch (Netherlands)", "East Timorese", "Ecuadorean", "Egyptian", "Salvadoran",
            "Equatorial Guinean", "Eritrean", "Estonian", "Swazi (Eswatini)", "Ethiopian", "Fijian", "Finn (Finnish)", "French", "Gabonese", "Gambian",
            "Georgian", "German", "Greek", "Grenadian", "Guatemalan", "Guinean", "Bissau-Guinean", "Guyanese", "Haitian", "Honduran",
            "Hungarian", "Icelander", "Indian", "Indonesian", "Iranian", "Iraqi", "Irish", "Israeli", "Italian", "Jamaican",
            "Japanese", "Jordanian", "Kazakhstani", "Kenyan", "I-Kiribati", "North Korean", "South Korean", "Kuwaiti", "Kyrgyz", "Laotian",
            "Latvian", "Lebanese", "Basotho (Lesotho)", "Liberian", "Libyan", "Liechtenstein citizen", "Lithuanian", "Luxembourgish", "Malagasy", "Malawian",
            "Malaysian", "Maldivian", "Malian", "Maltese", "Marshallese", "Mauritanian", "Mauritian", "Mexican", "Micronesian", "Moldovan",
            "Monegasque", "Mongolian", "Montenegrin", "Moroccan", "Mozambican", "Burmese (Myanmar)", "Namibian", "Nauruan", "Nepali", "New Zealander",
            "Nicaraguan", "Nigerien", "Nigerian", "North Macedonian", "Norwegian", "Omani", "Pakistani", "Palauan", "Palestinian", "Panamanian",
            "Papua New Guinean", "Paraguayan", "Peruvian", "Filipino", "Polish", "Portuguese", "Qatari", "Romanian", "Russian", "Rwandan",
            "Kittitian or Nevisian", "Saint Lucian", "Vincentian", "Samoan", "Sammarinese", "Sao Tomean", "Saudi", "Senegalese", "Serbian",
            "Seychellois", "Sierra Leonean", "Singaporean", "Slovak", "Slovenian", "Solomon Islander", "Somali", "South African", "South Sudanese", "Spanish",
            "Sri Lankan", "Sudanese", "Surinamese", "Swedish", "Swiss", "Syrian", "Taiwanese", "Tajik", "Tanzanian", "Thai",
            "Togolese", "Tongan", "Trinidadian or Tobagonian", "Tunisian", "Turkish", "Turkmen", "Tuvaluan", "Ugandan", "Ukrainian", "Emirati",
            "Uruguayan", "Uzbek", "Ni-Vanuatu", "Vatican citizen", "Venezuelan", "Vietnamese", "Yemeni", "Zambian", "Zimbabwean"
        ];

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
            let options = '<option value="" disabled selected>-- Select Nationality --</option>';
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
                        <h4 class="font-extrabold text-slate-900 text-sm">New Associated Person / Co-Suspect #${personCounter}</h4>
                    </div>
                    <button type="button" onclick="document.getElementById('personCard-${personCounter}').remove()" class="px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-bold rounded-xl transition-colors cursor-pointer border-none flex items-center space-x-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Remove Person</span>
                    </button>
                </div>

                <!-- 1. MUGSHOT UPLOADS (FRONT 0°, LEFT 90°, RIGHT 90°) -->
                <div class="space-y-2">
                    <label class="block text-slate-800 font-extrabold text-xs uppercase tracking-wider font-mono-code flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Suspect Mugshots &amp; Facial Biometrics (Front 0&deg; / Left 90&deg; / Right 90&deg;)
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 bg-white p-3.5 rounded-xl border border-slate-200">
                        <!-- FRONT MUGSHOT -->
                        <div class="space-y-2 text-center">
                            <span class="text-[10px] font-mono-code font-extrabold text-slate-900 text-black block uppercase">Front Mugshot (0&deg;)</span>
                            <div class="h-32 w-full bg-slate-100 rounded-xl border-2 border-dashed border-slate-300 flex flex-col items-center justify-center overflow-hidden relative group">
                                <img id="previewFront_${personCounter}" src="" alt="Front Mugshot Preview" class="h-full w-full object-cover hidden">
                                <div id="placeholderFront_${personCounter}" class="flex flex-col items-center justify-center text-slate-400 p-2">
                                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span class="text-[9px] font-mono-code mt-1 text-slate-500 font-bold">Upload Front Mugshot</span>
                                </div>
                            </div>
                            <input type="file" accept="image/*" onchange="previewMugshotFile(this, 'previewFront_${personCounter}', 'placeholderFront_${personCounter}', 'hiddenFront_${personCounter}')" class="w-full text-[10px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-[10px] file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                            <input type="text" placeholder="Or paste image URL" oninput="previewMugshotUrl(this.value, 'previewFront_${personCounter}', 'placeholderFront_${personCounter}', 'hiddenFront_${personCounter}')" class="w-full px-2.5 py-1 text-[10px] bg-slate-50 border border-slate-200 rounded-lg font-mono-code text-slate-700">
                            <input type="hidden" name="associated_persons[${personCounter}][mugshot_front]" id="hiddenFront_${personCounter}">
                        </div>

                        <!-- LEFT MUGSHOT -->
                        <div class="space-y-2 text-center">
                            <span class="text-[10px] font-mono-code font-extrabold text-slate-900 text-black block uppercase">Left Profile (90&deg;)</span>
                            <div class="h-32 w-full bg-slate-100 rounded-xl border-2 border-dashed border-slate-300 flex flex-col items-center justify-center overflow-hidden relative group">
                                <img id="previewLeft_${personCounter}" src="" alt="Left Profile Preview" class="h-full w-full object-cover hidden">
                                <div id="placeholderLeft_${personCounter}" class="flex flex-col items-center justify-center text-slate-400 p-2">
                                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span class="text-[9px] font-mono-code mt-1 text-slate-500 font-bold">Upload Left Profile</span>
                                </div>
                            </div>
                            <input type="file" accept="image/*" onchange="previewMugshotFile(this, 'previewLeft_${personCounter}', 'placeholderLeft_${personCounter}', 'hiddenLeft_${personCounter}')" class="w-full text-[10px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-[10px] file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                            <input type="text" placeholder="Or paste image URL" oninput="previewMugshotUrl(this.value, 'previewLeft_${personCounter}', 'placeholderLeft_${personCounter}', 'hiddenLeft_${personCounter}')" class="w-full px-2.5 py-1 text-[10px] bg-slate-50 border border-slate-200 rounded-lg font-mono-code text-slate-700">
                            <input type="hidden" name="associated_persons[${personCounter}][mugshot_left]" id="hiddenLeft_${personCounter}">
                        </div>

                        <!-- RIGHT MUGSHOT -->
                        <div class="space-y-2 text-center">
                            <span class="text-[10px] font-mono-code font-extrabold text-slate-900 text-black block uppercase">Right Profile (90&deg;)</span>
                            <div class="h-32 w-full bg-slate-100 rounded-xl border-2 border-dashed border-slate-300 flex flex-col items-center justify-center overflow-hidden relative group">
                                <img id="previewRight_${personCounter}" src="" alt="Right Profile Preview" class="h-full w-full object-cover hidden">
                                <div id="placeholderRight_${personCounter}" class="flex flex-col items-center justify-center text-slate-400 p-2">
                                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span class="text-[9px] font-mono-code mt-1 text-slate-500 font-bold">Upload Right Profile</span>
                                </div>
                            </div>
                            <input type="file" accept="image/*" onchange="previewMugshotFile(this, 'previewRight_${personCounter}', 'placeholderRight_${personCounter}', 'hiddenRight_${personCounter}')" class="w-full text-[10px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-[10px] file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                            <input type="text" placeholder="Or paste image URL" oninput="previewMugshotUrl(this.value, 'previewRight_${personCounter}', 'placeholderRight_${personCounter}', 'hiddenRight_${personCounter}')" class="w-full px-2.5 py-1 text-[10px] bg-slate-50 border border-slate-200 rounded-lg font-mono-code text-slate-700">
                            <input type="hidden" name="associated_persons[${personCounter}][mugshot_right]" id="hiddenRight_${personCounter}">
                        </div>
                    </div>
                </div>

                <!-- 2. PERSONAL IDENTITY & BIOMETRIC DETAILS -->
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-slate-900 font-extrabold text-[11px] text-black uppercase mb-1">Full Name <span class="text-red-600">*</span></label>
                        <input type="text" name="associated_persons[${personCounter}][suspect_name]" required class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900" placeholder="e.g. Kwame Mensah">
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
                            <option value="" disabled selected>-- Select Role / Association --</option>
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
                        <label class="block text-slate-900 font-extrabold text-[11px] text-black uppercase mb-1">Gender</label>
                        <select name="associated_persons[${personCounter}][gender]" class="select2-gender-dropdown w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold">
                            <option value="" disabled selected>-- Select Gender --</option>
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
                        <label class="block text-slate-900 font-extrabold text-[11px] text-black uppercase mb-1">Date of Birth</label>
                        <input type="date" name="associated_persons[${personCounter}][dob]" id="personDob_${personCounter}" onchange="calculateAgeFromDob(this, 'personAge_${personCounter}')" oninput="calculateAgeFromDob(this, 'personAge_${personCounter}')" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono-code text-slate-800">
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
                        <label class="block text-slate-900 font-extrabold text-[11px] text-black uppercase mb-1">Place of Birth / Hometown <span class="text-red-600">*</span></label>
                        <input type="text" name="associated_persons[${personCounter}][place_of_birth]" required class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900" placeholder="e.g. Kumasi, Ashanti Region">
                    </div>
                    <div>
                        <label class="block text-slate-900 font-extrabold text-[11px] text-black uppercase mb-1">Height (cm / ft-in)</label>
                        <input type="text" name="associated_persons[${personCounter}][height]" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono-code" placeholder="e.g. 180 cm / 5ft 11in">
                    </div>
                    <div>
                        <label class="block text-slate-900 font-extrabold text-[11px] text-black uppercase mb-1">Eye Color</label>
                        <select name="associated_persons[${personCounter}][eye_color]" class="select2-eyecolor-dropdown w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold">
                            <option value="" disabled selected>-- Select Eye Color --</option>
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
                        <input type="text" name="associated_persons[${personCounter}][holding_facility]" value="NACOC Holding Cell A-02" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-extrabold text-emerald-700">
                    </div>
                </div>
            `;
            container.appendChild(card);

            if (window.jQuery && $.fn.select2) {
                const $card = $(`#personCard-${personCounter}`);
                $card.find('.select2-role-dropdown').select2({
                    placeholder: "-- Select Role / Association --",
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
                        return $(`
                            <div class="flex items-center justify-between py-0.5">
                                <span class="font-extrabold text-slate-900 text-xs">${role.text}</span>
                                <span class="px-2 py-0.5 text-[9px] font-mono-code font-bold rounded-md uppercase border ${b.bg} ${b.text} ${b.border}">ROLE</span>
                            </div>
                        `);
                    },
                    templateSelection: function(role) { return role.text; },
                    width: '100%'
                });
                $card.find('.select2-gender-dropdown').select2({
                    placeholder: "-- Select Gender --",
                    templateResult: function(gen) {
                        if (!gen.id) return gen.text;
                        return $(`<span class="font-bold text-slate-800 text-xs">${gen.text}</span>`);
                    },
                    templateSelection: function(gen) { return gen.text; },
                    width: '100%'
                });
                $card.find('.select2-nationality-dropdown').select2({
                    placeholder: "-- Select World Nationality --",
                    templateResult: function(nat) {
                        if (!nat.id) return nat.text;
                        const isGhana = nat.text === 'Ghanaian';
                        const dot = isGhana
                            ? '<span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 border border-emerald-300 text-[9px] font-mono-code font-bold rounded-md uppercase">GHANA</span>'
                            : '<span class="w-2 h-2 rounded-full bg-blue-400 shrink-0"></span>';
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
            }
        }

        function previewMugshotFile(fileInput, previewImgId, placeholderId, hiddenInputId) {
            if (fileInput.files && fileInput.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const dataUrl = e.target.result;
                    const previewEl = document.getElementById(previewImgId);
                    const placeholderEl = document.getElementById(placeholderId);
                    const hiddenEl = document.getElementById(hiddenInputId);
                    if (previewEl) {
                        previewEl.src = dataUrl;
                        previewEl.classList.remove('hidden');
                    }
                    if (placeholderEl) {
                        placeholderEl.classList.add('hidden');
                    }
                    if (hiddenEl) {
                        hiddenEl.value = dataUrl;
                    }
                };
                reader.readAsDataURL(fileInput.files[0]);
            }
        }

        function previewMugshotUrl(urlStr, previewImgId, placeholderId, hiddenInputId) {
            const previewEl = document.getElementById(previewImgId);
            const placeholderEl = document.getElementById(placeholderId);
            const hiddenEl = document.getElementById(hiddenInputId);
            const val = (urlStr || '').trim();

            if (hiddenEl) hiddenEl.value = val;

            if (val) {
                if (previewEl) {
                    previewEl.src = val;
                    previewEl.classList.remove('hidden');
                }
                if (placeholderEl) placeholderEl.classList.add('hidden');
            } else {
                if (previewEl) previewEl.classList.add('hidden');
                if (placeholderEl) placeholderEl.classList.remove('hidden');
            }
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

        function openExhibitListPanel(el) { openSeizurePanel(el, '#exhibitListPanel'); }
        function openDrugListPanel(el) { openSeizurePanel(el, '#drugListPanel'); }
        function openWeaponListPanel(el) { openSeizurePanel(el, '#weaponListPanel'); }
        function openCurrencyListPanel(el) { openSeizurePanel(el, '#currencyListPanel'); }
        function openAmmoListPanel(el) { openSeizurePanel(el, '#ammoListPanel'); }
        function openDeviceListPanel(el) { openSeizurePanel(el, '#deviceListPanel'); }
        function openPkgListPanel(el) { openSeizurePanel(el, '#pkgListPanel'); }
        function openUnitListPanel(el) { openSeizurePanel(el, '#unitListPanel'); }

        function selectExhibitCategory(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root = getSeizureBlockFromEvent(e);
            const input   = root.querySelector('#newSeizureCategory');
            const display = root.querySelector('#exhibitSearchInput');
            const panel   = root.querySelector('#exhibitListPanel');
            if (input)   input.value = val;
            if (display) { display.value = val; display.setAttribute('readonly', 'readonly'); }
            if (panel)   panel.classList.add('hidden');
            updateCommodityField(val, root);
        }

        function filterExhibitList(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const q = (root.querySelector('#exhibitSearchInput')?.value || '').toLowerCase();
            filterExhibitListByQuery(q, root);
        }

        function filterExhibitListInner(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const q = (root.querySelector('#exhibitSearchInner')?.value || '').toLowerCase();
            filterExhibitListByQuery(q, root);
        }

        function filterExhibitListByQuery(q, rootEl) {
            const root = rootEl || document;
            const items = root.querySelectorAll('.exhibit-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
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
            const textInput       = root.querySelector('#newSeizureName');
            const drugSearch      = root.querySelector('#drugSearchInput');
            const currencySearch  = root.querySelector('#currencySearchInput');
            const ammoSearch      = root.querySelector('#ammoSearchInput');
            const deviceSearch    = root.querySelector('#deviceSearchInput');
            const quantityLabel   = root.querySelector('#quantityLabel');

            if (textWrapper)         textWrapper.classList.add('hidden');
            if (drugWrapper)         drugWrapper.classList.add('hidden');
            if (weaponWrapper)       weaponWrapper.classList.add('hidden');
            if (currencyWrapper)     currencyWrapper.classList.add('hidden');
            if (ammoWrapper)         ammoWrapper.classList.add('hidden');
            if (deviceWrapper)       deviceWrapper.classList.add('hidden');
            if (deviceFieldsWrapper) deviceFieldsWrapper.classList.add('hidden');
            if (vehicleWrapper)      vehicleWrapper.classList.add('hidden');

            if (category === 'Drugs') {
                if (quantityLabel) quantityLabel.innerText = 'Quantity';
                if (drugWrapper) drugWrapper.classList.remove('hidden');
                if (drugSearch)  { drugSearch.placeholder = 'Select drug type...'; }
            } else if (category === 'Weapons') {
                if (quantityLabel) quantityLabel.innerText = 'Quantity';
                if (weaponWrapper) weaponWrapper.classList.remove('hidden');
                const weaponSearch = root.querySelector('#weaponSearchInput');
                if (weaponSearch) { weaponSearch.placeholder = 'Select weapon type...'; }
            } else if (category === 'Money') {
                if (quantityLabel) quantityLabel.innerText = 'Amount';
                if (currencyWrapper) currencyWrapper.classList.remove('hidden');
                if (currencySearch)  { currencySearch.placeholder = 'Select currency...'; }
            } else if (category === 'Ammunition') {
                if (quantityLabel) quantityLabel.innerText = 'Quantity / Rounds';
                if (ammoWrapper) ammoWrapper.classList.remove('hidden');
                if (ammoSearch)  { ammoSearch.placeholder = 'Select ammunition type...'; }
            } else if (category === 'Electronic Devices') {
                if (quantityLabel) quantityLabel.innerText = 'Quantity / Units';
                if (deviceWrapper) deviceWrapper.classList.remove('hidden');
                if (deviceFieldsWrapper) deviceFieldsWrapper.classList.remove('hidden');
                if (deviceSearch)  { deviceSearch.placeholder = 'Select electronic device type...'; }
            } else {
                if (quantityLabel) quantityLabel.innerText = 'Quantity';
                if (textWrapper) textWrapper.classList.remove('hidden');
                const placeholders = {
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
            const items = list.querySelectorAll('.drug-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
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
            const items = list.querySelectorAll('.weapon-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
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
        }

        function filterCurrencyList(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const q = (root.querySelector('#currencySearchInput')?.value || '').toLowerCase();
            filterCurrencyListByQuery(q, root);
        }
        function filterCurrencyListInner(el) {
            const root = el ? el.closest('.seizure-block') : (document.querySelector('.seizure-block') || document);
            const q = (root.querySelector('#currencySearchInner')?.value || '').toLowerCase();
            filterCurrencyListByQuery(q, root);
        }
        function filterCurrencyListByQuery(q, rootEl) {
            const root = rootEl || document;
            const list = root.querySelector('#currencyList');
            if (!list) return;
            const items = list.querySelectorAll('.currency-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        function selectAmmoType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root   = getSeizureBlockFromEvent(e);
            const hidden  = root.querySelector('#newSeizureNameAmmo');
            const display = root.querySelector('#ammoSearchInput');
            const panel   = root.querySelector('#ammoListPanel');
            if (hidden)  hidden.value  = val;
            if (display) display.value = val;
            if (panel)   panel.classList.add('hidden');
            const textInput = root.querySelector('#newSeizureName');
            if (textInput) textInput.value = val;
        }

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
            const items = list.querySelectorAll('.ammo-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        function selectDeviceType(val, e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const root   = getSeizureBlockFromEvent(e);
            const hidden  = root.querySelector('#newSeizureNameDevice');
            const display = root.querySelector('#deviceSearchInput');
            const panel   = root.querySelector('#deviceListPanel');
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
            const items = list.querySelectorAll('.device-item');
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
            const items = list.querySelectorAll('.pkg-item');
            items.forEach(function(li) {
                const text = li.innerText.toLowerCase();
                li.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
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

        function addAssociateSeizureBlock() {
            seizureCounter++;
            const container = document.getElementById('seizuresListContainer');
            if (!container) return;

            const card = document.createElement('div');
            card.id = `seizureBlock-${seizureCounter}`;
            card.className = 'seizure-block bg-slate-50/90 p-5 rounded-2xl border border-emerald-200 shadow-sm space-y-5 relative';
            card.setAttribute('data-seizure-index', seizureCounter);

            card.innerHTML = `
                <!-- CARD HEADER & REMOVE BUTTON -->
                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <div class="flex items-center space-x-2.5">
                        <span class="w-6 h-6 rounded-full bg-emerald-600 text-white font-mono-code font-bold text-xs flex items-center justify-center shadow-xs seizure-number-badge">
                            ${seizureCounter}
                        </span>
                        <h4 class="font-extrabold text-slate-900 text-sm seizure-title">Seizure Item #${seizureCounter}</h4>
                        <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200">ACTIVE EXHIBIT</span>
                    </div>
                    <button type="button" onclick="removeAssociateSeizureBlock(${seizureCounter})" class="px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-bold rounded-xl transition-colors cursor-pointer border-none flex items-center space-x-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Remove Item</span>
                    </button>
                </div>

                <!-- SEIZURE INPUT GRID -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    <!-- 1. EXHIBIT (COMMODITY CATEGORY) DROPDOWN -->
                    <div class="relative z-[9999]" id="exhibitSelectWrapper" style="z-index: 9999;">
                        <label class="block text-slate-900 font-extrabold text-[11px] uppercase text-black mb-1">
                            Exhibit (Commodity Category) <span class="text-red-600 font-bold">*</span>
                        </label>
                        <div class="relative z-[9999]" style="z-index: 9999;">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </span>
                            <input type="text" id="exhibitSearchInput" placeholder="Select exhibit category..." autocomplete="off" readonly onclick="openExhibitListPanel(this)" oninput="filterExhibitList(this)" onfocus="openExhibitListPanel(this)" class="w-full pl-8 pr-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer shadow-xs">
                            <input type="hidden" id="newSeizureCategory" name="associated_exhibits[${seizureCounter}][category]" value="">
                        </div>
                        <div id="exhibitListPanel" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-300 z-[9999] overflow-hidden" style="z-index: 9999; max-height: 280px; overflow-y: auto;">
                            <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100 z-10">
                                <input type="text" id="exhibitSearchInner" placeholder="Type to filter categories..." oninput="filterExhibitListInner(this)" class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none">
                            </div>
                            <ul id="exhibitList" class="py-1.5 space-y-0.5 px-1.5">
                                <li class="exhibit-item" onclick="selectExhibitCategory('Drugs', event)">
                                    <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between transition-colors">
                                        <div><div class="font-extrabold text-slate-900">Drugs</div><div class="text-[10px] text-slate-500 font-normal">Narcotics, Synthetics, Opioids</div></div>
                                        <span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-2 py-0.5 rounded font-bold">DRG</span>
                                    </button>
                                </li>
                                <li class="exhibit-item" onclick="selectExhibitCategory('Money', event)">
                                    <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between transition-colors">
                                        <div><div class="font-extrabold text-slate-900">Money</div><div class="text-[10px] text-slate-500 font-normal">Cash, Foreign Currencies, Laundering</div></div>
                                        <span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-2 py-0.5 rounded font-bold">CASH</span>
                                    </button>
                                </li>
                                <li class="exhibit-item" onclick="selectExhibitCategory('Gold', event)">
                                    <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between transition-colors">
                                        <div><div class="font-extrabold text-slate-900">Gold</div><div class="text-[10px] text-slate-500 font-normal">Bullion, Bars, Smuggled Metals</div></div>
                                        <span class="text-[10px] font-mono-code bg-amber-50 text-amber-800 px-2 py-0.5 rounded font-bold">MET</span>
                                    </button>
                                </li>
                                <li class="exhibit-item" onclick="selectExhibitCategory('Vehicles', event)">
                                    <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between transition-colors">
                                        <div><div class="font-extrabold text-slate-900">Vehicles</div><div class="text-[10px] text-slate-500 font-normal">Automobiles, Vessels, Trucks</div></div>
                                        <span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-2 py-0.5 rounded font-bold">VEH</span>
                                    </button>
                                </li>
                                <li class="exhibit-item" onclick="selectExhibitCategory('Electronic Devices', event)">
                                    <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between transition-colors">
                                        <div><div class="font-extrabold text-slate-900">Electronic Devices</div><div class="text-[10px] text-slate-500 font-normal">Phones, Encrypted Laptops, Drives</div></div>
                                        <span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 px-2 py-0.5 rounded font-bold">ELEC</span>
                                    </button>
                                </li>
                                <li class="exhibit-item" onclick="selectExhibitCategory('Weapons', event)">
                                    <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between transition-colors">
                                        <div><div class="font-extrabold text-slate-900">Weapons</div><div class="text-[10px] text-slate-500 font-normal">Firearms, Handguns, Blades</div></div>
                                        <span class="text-[10px] font-mono-code bg-red-50 text-red-700 px-2 py-0.5 rounded font-bold">WPN</span>
                                    </button>
                                </li>
                                <li class="exhibit-item" onclick="selectExhibitCategory('Ammunition', event)">
                                    <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between transition-colors">
                                        <div><div class="font-extrabold text-slate-900">Ammunition</div><div class="text-[10px] text-slate-500 font-normal">Bullets, Cartridges, Explosives</div></div>
                                        <span class="text-[10px] font-mono-code bg-orange-50 text-orange-700 px-2 py-0.5 rounded font-bold">AMMO</span>
                                    </button>
                                </li>
                                <li class="exhibit-item" onclick="selectExhibitCategory('Other Exhibits', event)">
                                    <button type="button" class="w-full text-left px-3 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between transition-colors">
                                        <div><div class="font-extrabold text-slate-900">Other Exhibits</div><div class="text-[10px] text-slate-500 font-normal">Hardware, Miscellaneous Devices</div></div>
                                        <span class="text-[10px] font-mono-code bg-slate-200 text-slate-700 px-2 py-0.5 rounded font-bold">MISC</span>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- 2. SPECIFIC COMMODITY / EXHIBIT NAME (SMART FIELD) -->
                    <div id="commodityFieldWrapper">
                        <label class="block text-slate-900 font-extrabold text-[11px] uppercase text-black mb-1">Primary Commodity Name <span class="text-red-600 font-bold">*</span></label>

                        <!-- TEXT INPUT (for default/non-smart categories) -->
                        <div id="commodityTextWrapper" class="relative flex items-center">
                            <input type="text" id="newSeizureName" name="associated_exhibits[${seizureCounter}][seizure_name]" placeholder="e.g. Cocaine (High Purity Slabs)" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                        </div>

                        <!-- DRUG SELECT DROPDOWN -->
                        <div id="drugSelectWrapper" class="hidden relative z-30">
                            <input type="text" id="drugSearchInput" placeholder="Select drug type..." autocomplete="off" onclick="openDrugListPanel(this)" oninput="filterDrugList(this)" onfocus="openDrugListPanel(this)" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-text">
                            <input type="hidden" id="newSeizureNameDrug" value="">
                            <div id="drugListPanel" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden" style="max-height:220px;overflow-y:auto">
                                <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                    <input type="text" id="drugSearchInner" placeholder="Type to filter drugs..." oninput="filterDrugListInner(this)" class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none">
                                </div>
                                <ul id="drugList" class="py-1.5 space-y-0.5 px-1.5">
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
                        <div id="weaponSelectWrapper" class="hidden relative z-30">
                            <input type="text" id="weaponSearchInput" placeholder="Select weapon type..." autocomplete="off" onclick="openWeaponListPanel(this)" oninput="filterWeaponList(this)" onfocus="openWeaponListPanel(this)" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-text">
                            <input type="hidden" id="newSeizureNameWeapon" value="">
                            <div id="weaponListPanel" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden" style="max-height:220px;overflow-y:auto">
                                <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                    <input type="text" id="weaponSearchInner" placeholder="Type to filter weapons..." oninput="filterWeaponListInner(this)" class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none">
                                </div>
                                <ul id="weaponList" class="py-1.5 space-y-0.5 px-1.5">
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
                        <div id="currencySelectWrapper" class="hidden relative z-30">
                            <input type="text" id="currencySearchInput" placeholder="Select currency..." autocomplete="off" onclick="openCurrencyListPanel(this)" oninput="filterCurrencyList(this)" onfocus="openCurrencyListPanel(this)" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-text">
                            <input type="hidden" id="newSeizureNameCurrency" value="">
                            <div id="currencyListPanel" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden" style="max-height:220px;overflow-y:auto">
                                <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                    <input type="text" id="currencySearchInner" placeholder="Type to filter currencies..." oninput="filterCurrencyListInner(this)" class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none">
                                </div>
                                <ul id="currencyList" class="py-1.5 space-y-0.5 px-1.5">
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
                        <div id="ammoSelectWrapper" class="hidden relative z-30">
                            <input type="text" id="ammoSearchInput" placeholder="Select ammunition type..." autocomplete="off" onclick="openAmmoListPanel(this)" oninput="filterAmmoList(this)" onfocus="openAmmoListPanel(this)" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-text">
                            <input type="hidden" id="newSeizureNameAmmo" value="">
                            <div id="ammoListPanel" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden" style="max-height:220px;overflow-y:auto">
                                <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                    <input type="text" id="ammoSearchInner" placeholder="Type to filter ammunition..." oninput="filterAmmoListInner(this)" class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none">
                                </div>
                                <ul id="ammoList" class="py-1.5 space-y-0.5 px-1.5">
                                    <li class="px-2 pt-2 pb-0.5 text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Ammunition &amp; Calibers</li>
                                    <li class="ammo-item" onclick="selectAmmoType('9x19mm Parabellum / NATO (9mm Luger)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>9x19mm Parabellum (9mm)</span><span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 px-1.5 py-0.5 rounded font-bold">9MM</span></button></li>
                                    <li class="ammo-item" onclick="selectAmmoType('7.62x39mm Kalashnikov Ammunition (AK Round)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>7.62x39mm AK Round</span><span class="text-[10px] font-mono-code bg-red-50 text-red-800 px-1.5 py-0.5 rounded font-bold">7.62</span></button></li>
                                    <li class="ammo-item" onclick="selectAmmoType('5.56x45mm NATO Assault Rifle Round', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>5.56x45mm NATO Round</span><span class="text-[10px] font-mono-code bg-blue-50 text-blue-800 px-1.5 py-0.5 rounded font-bold">5.56</span></button></li>
                                    <li class="ammo-item" onclick="selectAmmoType('12 Gauge Shotgun Shells (Buckshot / Slug)', event)"><button type="button" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-xl flex items-center justify-between"><span>12 Gauge Shotgun Shells</span><span class="text-[10px] font-mono-code bg-orange-50 text-orange-800 px-1.5 py-0.5 rounded font-bold">12GA</span></button></li>
                                </ul>
                            </div>
                        </div>

                        <!-- DEVICE SELECT DROPDOWN -->
                        <div id="deviceSelectWrapper" class="hidden relative z-30">
                            <input type="text" id="deviceSearchInput" placeholder="Select electronic device type..." autocomplete="off" onclick="openDeviceListPanel(this)" oninput="filterDeviceList(this)" onfocus="openDeviceListPanel(this)" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-text">
                            <input type="hidden" id="newSeizureNameDevice" value="">
                            <div id="deviceListPanel" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden" style="max-height:220px;overflow-y:auto">
                                <div class="sticky top-0 bg-white px-3 pt-2.5 pb-1.5 border-b border-slate-100">
                                    <input type="text" id="deviceSearchInner" placeholder="Type to filter devices..." oninput="filterDeviceListInner(this)" class="w-full px-3 py-1.5 bg-slate-100 rounded-lg text-xs font-medium outline-none">
                                </div>
                                <ul id="deviceList" class="py-1.5 space-y-0.5 px-1.5">
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

                    <!-- 3. ESTIMATED STREET VALUE -->
                    <div>
                        <label class="block text-slate-900 font-extrabold text-[11px] uppercase text-black mb-1">Estimated Street Value ($/GH¢)</label>
                        <input type="text" name="associated_exhibits[${seizureCounter}][seizure_value]" placeholder="e.g. GHS 1,500,000 / $120,000" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-emerald-700 text-xs focus:outline-none focus:border-emerald-600 font-mono-code">
                    </div>

                </div>

                <!-- ELECTRONIC DEVICE SPECIFICATIONS PANEL -->
                <div id="deviceFieldsWrapper" class="hidden col-span-full bg-blue-50/70 p-4 rounded-2xl border border-blue-200 shadow-xs space-y-3">
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
                <div id="vehicleFieldsWrapper" class="hidden col-span-full bg-amber-50/70 p-4 rounded-2xl border border-amber-200 shadow-xs space-y-3">
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
                            <label id="quantityLabel" class="block text-slate-900 font-extrabold text-[11px] uppercase text-black mb-1">Quantity</label>
                            <input type="text" name="associated_exhibits[${seizureCounter}][seizure_quantity]" placeholder="e.g. 10" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs font-mono-code focus:border-emerald-600">
                        </div>

                        <!-- PACKAGING TYPE -->
                        <div class="relative z-20" id="packagingSelectWrapper">
                            <label class="block text-slate-900 font-extrabold text-[11px] uppercase text-black mb-1">Packaging Type</label>
                            <input type="text" id="pkgSearchInput" placeholder="Select or type packaging..." autocomplete="off" onclick="openPkgListPanel(this)" oninput="filterPackagingList(this)" onfocus="openPkgListPanel(this)" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 transition-all cursor-text">
                            <input type="hidden" id="newSeizurePackaging" name="associated_exhibits[${seizureCounter}][seizure_packaging]" value="">
                            <div id="pkgListPanel" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden" style="max-height:200px;overflow-y:auto">
                                <div class="sticky top-0 bg-white px-3 pt-2 pb-1 border-b border-slate-100">
                                    <input type="text" id="pkgSearchInner" placeholder="Type to filter..." oninput="filterPackagingListInner(this)" class="w-full px-2.5 py-1 bg-slate-100 rounded-lg text-xs">
                                </div>
                                <ul id="pkgList" class="py-1 space-y-0.5 px-1">
                                    <li class="pkg-item" onclick="selectPackagingType('Slabs', event)"><button type="button" class="w-full text-left px-2.5 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-lg flex items-center justify-between"><span>Slabs</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1 py-0.5 rounded">SLB</span></button></li>
                                    <li class="pkg-item" onclick="selectPackagingType('Blocks', event)"><button type="button" class="w-full text-left px-2.5 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-lg flex items-center justify-between"><span>Blocks</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1 py-0.5 rounded">BLK</span></button></li>
                                    <li class="pkg-item" onclick="selectPackagingType('Bundles', event)"><button type="button" class="w-full text-left px-2.5 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-lg flex items-center justify-between"><span>Bundles</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1 py-0.5 rounded">BDL</span></button></li>
                                    <li class="pkg-item" onclick="selectPackagingType('Sacks / Bags', event)"><button type="button" class="w-full text-left px-2.5 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-lg flex items-center justify-between"><span>Sacks / Bags</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1 py-0.5 rounded">BAG</span></button></li>
                                    <li class="pkg-item" onclick="selectPackagingType('Cartons / Boxes', event)"><button type="button" class="w-full text-left px-2.5 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-lg flex items-center justify-between"><span>Cartons / Boxes</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1 py-0.5 rounded">BOX</span></button></li>
                                    <li class="pkg-item" onclick="selectPackagingType('Pieces / Units', event)"><button type="button" class="w-full text-left px-2.5 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-lg flex items-center justify-between"><span>Pieces / Units</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1 py-0.5 rounded">PCS</span></button></li>
                                    <li class="pkg-item" onclick="selectPackagingType('Wraps / Pellets', event)"><button type="button" class="w-full text-left px-2.5 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-lg flex items-center justify-between"><span>Wraps / Pellets</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1 py-0.5 rounded">WRP</span></button></li>
                                    <li class="pkg-item" onclick="selectPackagingType('Drums / Containers', event)"><button type="button" class="w-full text-left px-2.5 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-lg flex items-center justify-between"><span>Drums / Containers</span><span class="text-[10px] font-mono-code bg-slate-100 text-slate-600 px-1 py-0.5 rounded">DRM</span></button></li>
                                    <li class="pkg-item" onclick="selectPackagingType('Sealed Evidence Vault Bag', event)"><button type="button" class="w-full text-left px-2.5 py-1.5 text-xs font-bold text-slate-800 hover:bg-emerald-50 rounded-lg flex items-center justify-between"><span>Sealed Evidence Vault Bag</span><span class="text-[10px] font-mono-code bg-emerald-100 text-emerald-800 px-1 py-0.5 rounded">VAULT</span></button></li>
                                </ul>
                            </div>
                        </div>

                        <!-- WEIGHT / MEASUREMENT -->
                        <div>
                            <label class="block text-slate-900 font-extrabold text-[11px] uppercase text-black mb-1">Weight / Measurement</label>
                            <input type="text" name="associated_exhibits[${seizureCounter}][seizure_weight]" placeholder="e.g. 25.5" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs font-mono-code focus:border-emerald-600">
                        </div>

                        <!-- WEIGHT UNIT -->
                        <div class="relative z-20" id="unitSelectWrapper">
                            <label class="block text-slate-900 font-extrabold text-[11px] uppercase text-black mb-1">Measurement Unit</label>
                            <input type="text" id="unitSearchInput" placeholder="Select or type unit..." autocomplete="off" onclick="openUnitListPanel(this)" oninput="filterUnitList(this)" onfocus="openUnitListPanel(this)" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 transition-all cursor-text">
                            <input type="hidden" id="newSeizureUnit" name="associated_exhibits[${seizureCounter}][seizure_unit]" value="">
                            <div id="unitListPanel" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden" style="max-height:200px;overflow-y:auto">
                                <div class="sticky top-0 bg-white px-3 pt-2 pb-1 border-b border-slate-100">
                                    <input type="text" id="unitSearchInner" placeholder="Type to filter..." oninput="filterUnitListInner(this)" class="w-full px-2.5 py-1 bg-slate-100 rounded-lg text-xs">
                                </div>
                                <ul id="unitList" class="py-1 space-y-0.5 px-1">
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
            `;

            container.appendChild(card);
            updateSeizureBlockNumbers();
        }

        function removeAssociateSeizureBlock(id) {
            const card = document.getElementById(`seizureBlock-${id}`);
            if (card) {
                card.remove();
                updateSeizureBlockNumbers();
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

        document.addEventListener('DOMContentLoaded', function() {
            // Add 1 default person card and 1 seizure block on page load
            addAssociatePersonCard();
            addAssociateSeizureBlock();
        });
    </script>
</body>
</html>
