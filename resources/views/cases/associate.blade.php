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
                                <label class="block text-slate-500 font-bold text-[10px] font-mono-code uppercase mb-1">Case Reference #</label>
                                <input type="text" value="{{ $case->case_number }}" readonly class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold font-mono-code text-blue-600 cursor-not-allowed">
                            </div>
                            <div>
                                <label class="block text-slate-500 font-bold text-[10px] font-mono-code uppercase mb-1">Case Legal Title</label>
                                <input type="text" value="{{ $case->title }}" readonly class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-800 cursor-not-allowed">
                            </div>
                            <div>
                                <label class="block text-slate-500 font-bold text-[10px] font-mono-code uppercase mb-1">Submitting Officer</label>
                                <input type="text" value="{{ $case->submitting_officer ?? $case->commanding_officer ?? 'Handling Officer' }}" readonly class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold text-slate-800 cursor-not-allowed">
                            </div>
                            <div>
                                <label class="block text-slate-500 font-bold text-[10px] font-mono-code uppercase mb-1">Arrest Location / Vault</label>
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
                                        $fPic = $s['mugshot_front'] ?? null;
                                        $lPic = $s['mugshot_left'] ?? null;
                                        $rPic = $s['mugshot_right'] ?? null;

                                        if ($fPic && !str_starts_with($fPic, 'data:') && !str_starts_with($fPic, 'http')) $fPic = asset($fPic);
                                        if ($lPic && !str_starts_with($lPic, 'data:') && !str_starts_with($lPic, 'http')) $lPic = asset($lPic);
                                        if ($rPic && !str_starts_with($rPic, 'data:') && !str_starts_with($rPic, 'http')) $rPic = asset($rPic);
                                    @endphp
                                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs space-y-4">
                                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                            <div class="flex items-center space-x-2">
                                                <span class="w-6 h-6 rounded-full bg-blue-600 text-white font-mono-code font-bold text-xs flex items-center justify-center">
                                                    {{ $idx + 1 }}
                                                </span>
                                                <h5 class="font-extrabold text-slate-900 text-sm">
                                                    {{ $s['suspect_name'] ?? 'Suspect #' . ($idx + 1) }}
                                                </h5>
                                                @if(!empty($s['suspect_alias']))
                                                    <span class="text-xs text-slate-500 font-mono-code">"{{ $s['suspect_alias'] }}"</span>
                                                @endif
                                            </div>
                                            <span class="px-2.5 py-0.5 bg-blue-50 text-blue-700 text-[10px] font-mono-code font-bold rounded-md border border-blue-200">
                                                SAVED SUSPECT RECORD
                                            </span>
                                        </div>

                                        <!-- MUGSHOTS ROW -->
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                            <!-- FRONT -->
                                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 text-center">
                                                <div class="text-[9px] font-mono-code font-bold text-slate-500 uppercase mb-2">Front View (0&deg;)</div>
                                                <div class="h-32 bg-white rounded-lg border border-slate-200 flex items-center justify-center overflow-hidden">
                                                    @if($fPic)
                                                        <img src="{{ $fPic }}" alt="Front View" class="h-full w-full object-contain">
                                                    @else
                                                        <div class="text-slate-400 text-[10px] font-mono-code">No Front Mugshot</div>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- LEFT -->
                                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 text-center">
                                                <div class="text-[9px] font-mono-code font-bold text-slate-500 uppercase mb-2">Left Profile (90&deg;)</div>
                                                <div class="h-32 bg-white rounded-lg border border-slate-200 flex items-center justify-center overflow-hidden">
                                                    @if($lPic)
                                                        <img src="{{ $lPic }}" alt="Left View" class="h-full w-full object-contain">
                                                    @else
                                                        <div class="text-slate-400 text-[10px] font-mono-code">No Left Profile</div>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- RIGHT -->
                                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 text-center">
                                                <div class="text-[9px] font-mono-code font-bold text-slate-500 uppercase mb-2">Right Profile (90&deg;)</div>
                                                <div class="h-32 bg-white rounded-lg border border-slate-200 flex items-center justify-center overflow-hidden">
                                                    @if($rPic)
                                                        <img src="{{ $rPic }}" alt="Right View" class="h-full w-full object-contain">
                                                    @else
                                                        <div class="text-slate-400 text-[10px] font-mono-code">No Right Profile</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <!-- PERSONAL DETAILS GRID -->
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-[11px] pt-2 border-t border-slate-100">
                                            <div><span class="text-slate-400 font-mono-code text-[10px] block">Ghana Card:</span><strong class="text-slate-800 font-mono-code">{{ $s['ghana_card'] ?? 'N/A' }}</strong></div>
                                            <div><span class="text-slate-400 font-mono-code text-[10px] block">Gender / Age:</span><strong class="text-slate-800">{{ $s['gender'] ?? 'N/A' }} / {{ $s['age'] ? $s['age'] . ' yrs' : 'N/A' }}</strong></div>
                                            <div><span class="text-slate-400 font-mono-code text-[10px] block">Nationality:</span><strong class="text-slate-800">{{ $s['nationality'] ?? 'Ghanaian' }}</strong></div>
                                            <div><span class="text-slate-400 font-mono-code text-[10px] block">Holding Facility:</span><strong class="text-slate-800">{{ $s['holding_facility'] ?? 'NACOC Holding Facility' }}</strong></div>
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
                                    <p class="text-xs text-slate-500">Log additional contraband, weapons, vehicles, phones, or property linked to this investigation</p>
                                </div>
                            </div>
                            <button type="button" onclick="addAssociateExhibitRow()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold rounded-xl shadow-md transition-all flex items-center space-x-1.5 cursor-pointer border-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                <span>+ Add Exhibit Row</span>
                            </button>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-slate-200 shadow-2xs">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead class="bg-slate-100 text-slate-700 font-mono-code uppercase text-[10px]">
                                    <tr>
                                        <th class="py-3 px-4 font-bold">Category</th>
                                        <th class="py-3 px-4 font-bold">Exhibit Name / Description *</th>
                                        <th class="py-3 px-4 font-bold">Quantity</th>
                                        <th class="py-3 px-4 font-bold">Weight</th>
                                        <th class="py-3 px-4 font-bold">Unit</th>
                                        <th class="py-3 px-4 font-bold">Est. Value ($/GH¢)</th>
                                        <th class="py-3 px-4 font-bold text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="newExhibitsTableBody" class="divide-y divide-slate-200 bg-white">
                                    <!-- Rows added dynamically via JS -->
                                </tbody>
                            </table>
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

        function addAssociatePersonCard() {
            personCounter++;
            const container = document.getElementById('newPersonsContainer');
            if (!container) return;

            const card = document.createElement('div');
            card.id = `personCard-${personCounter}`;
            card.className = 'new-person-card bg-slate-50/80 p-5 rounded-2xl border border-slate-200 space-y-4 relative';
            
            card.innerHTML = `
                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <div class="flex items-center space-x-2">
                        <span class="px-2.5 py-0.5 bg-blue-600 text-white text-[10px] font-mono-code font-bold rounded-md uppercase">
                            New Associated Person #${personCounter}
                        </span>
                    </div>
                    <button type="button" onclick="document.getElementById('personCard-${personCounter}').remove()" class="px-2.5 py-1 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-bold rounded-lg transition-colors cursor-pointer border-none flex items-center space-x-1">
                        <span>&times; Remove Row</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-slate-700 font-bold text-[11px] mb-1">Full Name <span class="text-red-600">*</span></label>
                        <input type="text" name="associated_persons[${personCounter}][suspect_name]" required class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900" placeholder="e.g. Kwame Mensah">
                    </div>
                    <div>
                        <label class="block text-slate-700 font-bold text-[11px] mb-1">Alias / Street Name</label>
                        <input type="text" name="associated_persons[${personCounter}][suspect_alias]" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-800" placeholder="e.g. Snake">
                    </div>
                    <div>
                        <label class="block text-slate-700 font-bold text-[11px] mb-1">Ghana Card / ID</label>
                        <input type="text" name="associated_persons[${personCounter}][ghana_card]" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono-code" placeholder="GHA-000000000-0">
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-slate-700 font-bold text-[11px] mb-1">Gender</label>
                        <select name="associated_persons[${personCounter}][gender]" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-700 font-bold text-[11px] mb-1">Age</label>
                        <input type="number" name="associated_persons[${personCounter}][age]" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono-code" placeholder="32">
                    </div>
                    <div>
                        <label class="block text-slate-700 font-bold text-[11px] mb-1">Nationality</label>
                        <input type="text" name="associated_persons[${personCounter}][nationality]" value="Ghanaian" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold">
                    </div>
                    <div>
                        <label class="block text-slate-700 font-bold text-[11px] mb-1">Role / Association <span class="text-red-600">*</span></label>
                        <select name="associated_persons[${personCounter}][role]" class="w-full px-3 py-2 bg-white border border-blue-300 rounded-xl text-xs font-extrabold text-blue-700">
                            <option value="Co-Suspect">Co-Suspect</option>
                            <option value="Accomplice">Accomplice</option>
                            <option value="Driver / Courier">Driver / Courier</option>
                            <option value="Buyer / Receiver">Buyer / Receiver</option>
                            <option value="Informant">Informant</option>
                            <option value="Witness">Witness</option>
                            <option value="Associate">General Associate</option>
                        </select>
                    </div>
                </div>
            `;
            container.appendChild(card);
        }

        function addAssociateExhibitRow() {
            const tbody = document.getElementById('newExhibitsTableBody');
            if (!tbody) return;

            const tr = document.createElement('tr');
            tr.className = 'new-exhibit-row';
            tr.innerHTML = `
                <td class="py-2.5 px-3">
                    <select name="associated_exhibits[][category]" class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-bold text-blue-600">
                        <option value="Narcotic Drug / Substance">Drug / Chemical</option>
                        <option value="Vehicle / Vessel">Vehicle / Vessel</option>
                        <option value="Weapon / Ammunition">Weapon / Ammo</option>
                        <option value="Digital Device / Phone">Digital Device</option>
                        <option value="Currency / Cash">Currency / Cash</option>
                        <option value="Property / Document">Property / Doc</option>
                    </select>
                </td>
                <td class="py-2.5 px-3"><input type="text" name="associated_exhibits[][seizure_name]" required class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-900" placeholder="e.g. iPhone 15 Pro / 12kg Powder"></td>
                <td class="py-2.5 px-3"><input type="text" name="associated_exhibits[][seizure_quantity]" class="w-24 px-2 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-mono-code" placeholder="1 unit"></td>
                <td class="py-2.5 px-3"><input type="text" name="associated_exhibits[][seizure_weight]" class="w-24 px-2 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-mono-code" placeholder="12.5"></td>
                <td class="py-2.5 px-3"><input type="text" name="associated_exhibits[][seizure_unit]" class="w-20 px-2 py-1.5 bg-white border border-slate-300 rounded-lg text-xs" placeholder="kg / pcs"></td>
                <td class="py-2.5 px-3"><input type="text" name="associated_exhibits[][seizure_value]" class="w-28 px-2 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-mono-code text-emerald-700 font-bold" placeholder="$5,000"></td>
                <td class="py-2.5 px-3 text-center">
                    <button type="button" onclick="this.closest('tr').remove()" class="text-red-500 hover:text-red-700 font-bold text-base px-2 leading-none cursor-pointer">&times;</button>
                </td>
            `;
            tbody.appendChild(tr);
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Add 1 default person card and 1 exhibit row on page load
            addAssociatePersonCard();
            addAssociateExhibitRow();
        });
    </script>
</body>
</html>
