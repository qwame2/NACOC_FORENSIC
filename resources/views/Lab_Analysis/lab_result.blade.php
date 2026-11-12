@php
    $authUser = auth()->user();
    $dbUnitHead = \App\Models\User::where('unit', 'LIKE', '%Lab%')->where('is_head', true)->first()
               ?? \App\Models\User::where('is_head', true)->first();
    $headName = $authUser->fullname ?? session('user_name') ?? ($dbUnitHead ? $dbUnitHead->fullname : 'N/A');
    $isLabAnalysis = true;

    // Collect all unique drug commodities linked to all suspects in the system
    $uniqueDrugNames = [];
    foreach ($drugRecords as $dr) {
        $dn = trim($dr['drug_name'] ?? '');
        if (!empty($dn) && !in_array($dn, $uniqueDrugNames)) {
            $uniqueDrugNames[] = $dn;
        }
    }
    sort($uniqueDrugNames);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Narcotics Laboratory Results &amp; Drug Suspect Register | NACOC Forensic</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    @endif
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; background-color: #F8FAFC; color: #0F172A; }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
        .custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="min-h-screen flex antialiased selection:bg-emerald-600 selection:text-white">

    <div class="flex h-screen w-full overflow-hidden">
        <!-- SHARED SIDEBAR -->
        @include('Crime_Scene_Support.partials.sidebar')

        <!-- MAIN WORKSPACE CONTAINER -->
        <div class="flex-1 flex flex-col min-w-0 bg-slate-50 overflow-hidden">
            
            <!-- TOP NAVBAR -->
            <header class="h-16 bg-white border-b border-slate-200/90 px-6 flex items-center justify-between shrink-0 z-20 shadow-2xs">
                <div class="flex items-center gap-4 flex-1 max-w-2xl">
                    <div class="relative w-full">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input type="text" id="topSearchInput" onkeyup="syncTopSearch(this.value)" placeholder="Search Drug Suspect Name, Alias, Ghana Card #, Lab No, Drug Type, Location..." class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ url('/Lab_Analysis/create') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center space-x-1">
                        <span>+ Add Drug Docket</span>
                    </a>
                </div>
            </header>

            <!-- MAIN CONTENT AREA -->
            <main class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar">
                @include('partials.flash')

                <!-- HERO BANNER -->
                <div class="bg-white p-6 rounded-3xl border border-slate-200/90 shadow-md shadow-slate-200/60 relative overflow-hidden">
                    <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-2.5 py-0.5 bg-teal-500 text-white text-[10px] font-extrabold font-mono-code rounded-md uppercase tracking-wider">LAB ANALYSIS RESULT</span>
                                <span class="text-xs text-slate-400">&bull;</span>
                                <span class="text-xs font-bold text-teal-600 font-mono-code">DRUG &amp; NARCOTICS SUSPECT REGISTER</span>
                            </div>
                            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 flex items-center gap-3">
                                <span>Drug-Linked Suspect Analysis Directory</span>
                            </h1>
                            <p class="text-xs text-slate-600 mt-1 max-w-2xl leading-relaxed">
                                Official Forensic Laboratory register displaying suspect mugshots and chemical analysis dockets. Suspects linked to the same drug exhibit are grouped together in a unified docket card.
                            </p>
                        </div>
                        <div class="flex items-center gap-4 shrink-0 flex-wrap">
                            <div class="text-right">
                                <div class="text-[10px] font-mono-code uppercase text-slate-400">Head Chemist Signature</div>
                                <div class="text-xs font-bold text-teal-600 font-mono-code">{{ $headName }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- METRICS / STAT CARDS FOR MALES, FEMALES, SINGLE & MULTIPLE SUSPECTS -->
                @php
                    $totalExhibitsCount = count($drugRecords);
                    $totalSuspectsCount = 0;
                    $maleSuspectsCount = 0;
                    $femaleSuspectsCount = 0;
                    $singleSuspectDockets = 0;
                    $multipleSuspectDockets = 0;

                    foreach ($drugRecords as $dr) {
                        $sList = $dr['suspects'] ?? [];
                        $sCount = count($sList);
                        $totalSuspectsCount += $sCount;

                        if ($sCount === 1) {
                            $singleSuspectDockets++;
                        } else if ($sCount > 1) {
                            $multipleSuspectDockets++;
                        }

                        foreach ($sList as $s) {
                            $g = strtolower(trim($s['gender'] ?? 'male'));
                            if ($g === 'female' || $g === 'f') {
                                $femaleSuspectsCount++;
                            } else {
                                $maleSuspectsCount++;
                            }
                        }
                    }
                @endphp
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <!-- MALE SUSPECTS CARD -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs hover:shadow-md transition-all">
                        <div class="flex items-center justify-between text-xs font-bold text-slate-500 font-mono-code uppercase">
                            <span>Male Suspects</span>
                            <span class="px-2 py-0.5 rounded bg-blue-600 text-white font-bold text-[10px] font-mono-code">♂ Male</span>
                        </div>
                        <div class="text-3xl font-extrabold text-blue-600 font-mono-code mt-2">{{ $maleSuspectsCount }} <span class="text-xs text-slate-400 font-normal">({{ number_format(($totalSuspectsCount > 0 ? ($maleSuspectsCount / $totalSuspectsCount) * 100 : 0), 1) }}%)</span></div>
                        <div class="text-[11px] text-slate-500 font-semibold mt-1">Registered Male Suspects</div>
                    </div>

                    <!-- FEMALE SUSPECTS CARD -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs hover:shadow-md transition-all">
                        <div class="flex items-center justify-between text-xs font-bold text-slate-500 font-mono-code uppercase">
                            <span>Female Suspects</span>
                            <span class="px-2 py-0.5 rounded bg-pink-600 text-white font-bold text-[10px] font-mono-code">♀ Female</span>
                        </div>
                        <div class="text-3xl font-extrabold text-pink-600 font-mono-code mt-2">{{ $femaleSuspectsCount }} <span class="text-xs text-slate-400 font-normal">({{ number_format(($totalSuspectsCount > 0 ? ($femaleSuspectsCount / $totalSuspectsCount) * 100 : 0), 1) }}%)</span></div>
                        <div class="text-[11px] text-slate-500 font-semibold mt-1">Registered Female Suspects</div>
                    </div>

                    <!-- SINGLE SUSPECT DOCKETS CARD -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs hover:shadow-md transition-all">
                        <div class="flex items-center justify-between text-xs font-bold text-slate-500 font-mono-code uppercase">
                            <span>Single Suspect Dockets</span>
                            <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-mono-code font-bold text-[10px] border border-emerald-200">1 Suspect</span>
                        </div>
                        <div class="text-3xl font-extrabold text-emerald-600 font-mono-code mt-2">{{ $singleSuspectDockets }} <span class="text-xs text-slate-400 font-normal">Dockets</span></div>
                        <div class="text-[11px] text-emerald-600 font-semibold mt-1">Individual Offender Dockets</div>
                    </div>

                    <!-- MULTIPLE / CO-SUSPECT DOCKETS CARD -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs hover:shadow-md transition-all">
                        <div class="flex items-center justify-between text-xs font-bold text-slate-500 font-mono-code uppercase">
                            <span>Multiple Suspect Dockets</span>
                            <span class="px-2 py-0.5 rounded bg-purple-100 text-purple-800 font-mono-code font-bold text-[10px] border border-purple-200">Co-Suspects</span>
                        </div>
                        <div class="text-3xl font-extrabold text-purple-600 font-mono-code mt-2">{{ $multipleSuspectDockets }} <span class="text-xs text-slate-400 font-normal">Dockets</span></div>
                        <div class="text-[11px] text-purple-600 font-semibold mt-1">Syndicate &amp; Grouped Dockets</div>
                    </div>
                </div>

                <!-- SEARCH & FILTER CONTROLS SECTION -->
                <div class="bg-white rounded-3xl border border-slate-200/90 p-5 shadow-xs space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-8 h-8 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-900">Search &amp; Filter Drug Suspect Register</h3>
                                <p class="text-[11px] text-slate-500">Filter dockets by keyword, linked drug commodity, suspect gender, role, or arrest date range</p>
                            </div>
                        </div>

                        <button type="button" onclick="resetAllFilters()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-mono-code font-bold text-xs rounded-xl border border-slate-200 transition-colors flex items-center space-x-1.5 shrink-0 cursor-pointer">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>Reset Filters</span>
                        </button>
                    </div>

                    <!-- INPUT CONTROLS GRID (6 COLUMNS RESPONSIVE) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                        
                        <!-- KEYWORD SEARCH INPUT (2 COLS) -->
                        <div class="lg:col-span-2 relative">
                            <label class="block text-[10px] font-mono-code font-bold uppercase text-slate-500 mb-1">Search Keyword</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </span>
                                <input type="text" id="filterKeyword" onkeyup="applyFilters()" placeholder="Suspect Name, Alias, Ghana Card #, Lab No..." class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                            </div>
                        </div>

                        <!-- DRUG COMMODITY SELECT (DYNAMICALLY LISTS ALL DRUGS LINKED TO SUSPECTS) -->
                        <div>
                            <label class="block text-[10px] font-mono-code font-bold uppercase text-slate-500 mb-1">Drug Commodity</label>
                            <select id="filterDrugType" onchange="applyFilters()" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer">
                                <option value="ALL">All Drug Commodities ({{ count($uniqueDrugNames) }})</option>
                                @foreach($uniqueDrugNames as $udn)
                                    <option value="{{ $udn }}">{{ $udn }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- SUSPECT GENDER SELECT -->
                        <div>
                            <label class="block text-[10px] font-mono-code font-bold uppercase text-slate-500 mb-1">Suspect Gender</label>
                            <select id="filterGender" onchange="applyFilters()" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer">
                                <option value="ALL">All Genders</option>
                                <option value="male">Male (♂)</option>
                                <option value="female">Female (♀)</option>
                            </select>
                        </div>

                        <!-- DATE RANGE FROM -->
                        <div>
                            <label class="block text-[10px] font-mono-code font-bold uppercase text-slate-500 mb-1">Arrest / Case From</label>
                            <input type="date" id="filterStartDate" onchange="applyFilters()" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer">
                        </div>

                        <!-- DATE RANGE TO -->
                        <div>
                            <label class="block text-[10px] font-mono-code font-bold uppercase text-slate-500 mb-1">Arrest / Case To</label>
                            <input type="date" id="filterEndDate" onchange="applyFilters()" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer">
                        </div>

                    </div>

                    <!-- RESULT COUNT BAR -->
                    <div class="flex items-center justify-end pt-2 border-t border-slate-100">
                        <div class="text-xs text-slate-500 font-mono-code font-bold shrink-0">
                            Showing <span id="visibleCount" class="text-emerald-700 font-black">{{ $totalExhibitsCount }}</span> Drug Docket Cards
                        </div>
                    </div>
                </div>

                <!-- DRUG EXHIBIT CARDS GRID (SUSPECT FRONT MUGSHOTS & GENDER BADGES VISIBLE) -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="suspectsGrid">
                    @forelse($drugRecords as $idx => $r)
                    @php
                        $drugNameLower = strtolower($r['drug_name']);
                        $categoryGroup = \App\Services\DrugExhibitClassifier::extractKnownSubstance($drugNameLower) ?? 'Other';
                        if (in_array(strtolower($categoryGroup), ['weed', 'hemp', 'hashish', 'marijuana'], true)) {
                            $categoryGroup = 'Cannabis';
                        } elseif (strtolower($categoryGroup) === 'opium') {
                            $categoryGroup = 'Morphine';
                        }

                        $suspects = $r['suspects'];
                        $suspectCount = count($suspects);

                        $gendersList = [];
                        $rolesList = [];
                        $searchTerms = strtolower($r['case_number'] . ' ' . $r['laboratory_number'] . ' ' . $r['drug_name'] . ' ' . $r['raid_location']);

                        foreach ($suspects as $s) {
                            $gendersList[] = strtolower(trim($s['gender'] ?? 'male'));
                            $rolesList[] = strtolower(trim($s['role'] ?? 'primary suspect'));
                            $searchTerms .= ' ' . strtolower($s['suspect_name'] . ' ' . $s['suspect_alias'] . ' ' . $s['ghana_card']);
                        }

                        $genderDataStr = implode(' ', array_unique($gendersList));
                        $roleDataStr = implode(' ', array_unique($rolesList));
                        $cardDate = $suspects[0]['arrest_date'] ?? date('Y-m-d');
                    @endphp

                    <div class="drug-suspect-card bg-white rounded-2xl border border-slate-200/90 shadow-sm hover:shadow-xl transition-all flex flex-col justify-between overflow-hidden group" 
                         data-category="{{ $categoryGroup }}" 
                         data-drug="{{ $r['drug_name'] }}"
                         data-date="{{ $cardDate }}"
                         data-gender="{{ $genderDataStr }}"
                         data-roles="{{ $roleDataStr }}"
                         data-search="{{ $searchTerms }}">
                        
                        <!-- CARD HEADER: LAB REF & DRUG TYPE -->
                        <div class="p-5 space-y-4">
                            
                            <!-- LAB NO & CO-SUSPECT COUNT BADGE -->
                            <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-3">
                                <div class="flex items-center space-x-2">
                                    <span class="font-mono-code font-bold text-xs text-slate-800 tracking-tight" title="Laboratory Reference Number">
                                        {{ $r['laboratory_number'] }}
                                    </span>
                                </div>
                                <div class="flex items-center space-x-1.5">
                                    @if($suspectCount > 1)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold font-mono-code bg-purple-100 text-purple-800 border border-purple-200">
                                            {{ $suspectCount }} CO-SUSPECTS (GROUPED)
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-md text-[10px] font-extrabold font-mono-code bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            SINGLE SUSPECT
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- SEIZED DRUG EXHIBIT HEADER -->
                            <div class="p-3.5 rounded-2xl bg-gradient-to-br from-slate-900 to-slate-800 text-white space-y-2 shadow-xs border border-slate-800">
                                <div class="flex items-center justify-between text-[10px] font-mono-code">
                                    <span class="font-extrabold text-emerald-400 uppercase tracking-wider">SEIZED NARCOTIC COMMODITY</span>
                                    <span class="font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-2 py-0.5 rounded">
                                        {{ $r['quantity_str'] }}
                                    </span>
                                </div>
                                <div class="font-extrabold text-white text-sm flex items-center space-x-2">
                                    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L5.6 15.12a2 2 0 00-1.18.125l-.26.104a2 2 0 00-1.16 1.861v1.895a2 2 0 002 2h14a2 2 0 002-2v-1.895a2 2 0 01-2.072-1.938z"/></svg>
                                    <span class="truncate" title="{{ $r['drug_name'] }}">{{ $r['drug_name'] }}</span>
                                </div>
                                <div class="text-[11px] text-slate-300 font-medium">
                                    Classification: <strong class="text-white font-semibold">{{ $r['drug_category'] }}</strong>
                                </div>
                            </div>

                            <!-- SUSPECTS LINKED TO THIS DRUG EXHIBIT (SHOW FRONT MUGSHOTS & GENDER BADGES) -->
                            <div class="space-y-3">
                                <div class="text-[10.5px] font-mono-code font-bold uppercase text-slate-500 tracking-wider flex items-center justify-between">
                                    <span>Suspect Front Mugshots &amp; Profile ({{ $suspectCount }}):</span>
                                    <span class="text-[9.5px] text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">Verified Photo</span>
                                </div>

                                <div class="space-y-3">
                                    @foreach($suspects as $sIdx => $s)
                                    @php
                                        $gStr = strtolower(trim($s['gender'] ?? 'male'));
                                        $isFemale = ($gStr === 'female' || $gStr === 'f');
                                    @endphp
                                    <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/90 flex items-start space-x-3.5 hover:shadow-xs transition-all shadow-2xs">
                                        <!-- FRONT MUGSHOT IMAGE WITH GLOBAL HIGH-Z HOVER PREVIEW -->
                                        <div class="shrink-0">
                                            <img src="{{ $s['mugshot_front'] }}" 
                                                 alt="{{ $s['suspect_name'] }} Front Mugshot" 
                                                 onmouseenter="showMugshotPreview(this, '{{ $s['mugshot_front'] }}', '{{ addslashes($s['suspect_name']) }}', '{{ $s['ghana_card'] }}')"
                                                 onmouseleave="hideMugshotPreview()"
                                                 class="w-16 h-16 rounded-2xl object-cover border-2 border-emerald-600 shadow-sm bg-slate-200 cursor-zoom-in transition-transform duration-200 hover:scale-105">
                                        </div>

                                        <!-- SUSPECT DETAILS & CLEAR GENDER BADGE -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between gap-1 flex-wrap">
                                                <h4 class="font-extrabold text-slate-900 text-xs truncate" title="{{ $s['suspect_name'] }}">
                                                    {{ $s['suspect_name'] }}
                                                </h4>
                                                <span class="px-1.5 py-0.2 rounded text-[9px] font-mono-code font-bold {{ $sIdx === 0 ? 'bg-amber-100 text-amber-900 border border-amber-200' : 'bg-purple-100 text-purple-900 border border-purple-200' }}">
                                                    {{ $s['role'] }}
                                                </span>
                                            </div>

                                            @if(!empty($s['suspect_alias']) && strtoupper($s['suspect_alias']) !== 'N/A')
                                                <div class="text-[10px] font-semibold text-emerald-700 font-mono-code truncate mt-0.5">
                                                    a.k.a "{{ $s['suspect_alias'] }}"
                                                </div>
                                            @endif

                                            <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                                <!-- PROMINENT GENDER INDICATOR BADGE (♂ / ♀) -->
                                                @if($isFemale)
                                                    <span class="px-2 py-0.5 rounded-md bg-pink-600 text-white font-mono-code font-extrabold text-[10px] shadow-2xs flex items-center gap-1">
                                                        <span class="text-xs">♀</span> Female
                                                    </span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-md bg-blue-600 text-white font-mono-code font-extrabold text-[10px] shadow-2xs flex items-center gap-1">
                                                        <span class="text-xs">♂</span> Male
                                                    </span>
                                                @endif

                                                <span class="px-2 py-0.5 bg-white text-slate-700 font-mono-code text-[10px] font-bold rounded border border-slate-200 shadow-2xs">
                                                    Card: {{ $s['ghana_card'] }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- CHEMICAL ASSAY FINDINGS -->
                            <div class="space-y-1.5 text-xs border-t border-slate-100 pt-3">
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="text-slate-500 font-medium">Presumptive Reaction:</span>
                                    <span class="font-bold text-emerald-700 font-mono-code text-[10.5px]">{{ $r['presumptive'] }}</span>
                                </div>
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="text-slate-500 font-medium">Confirmatory Test:</span>
                                    <span class="font-bold text-emerald-700 font-mono-code text-[10.5px]">Certified Positive</span>
                                </div>
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="text-slate-500 font-medium">Case Reference:</span>
                                    <span class="font-mono-code font-bold text-slate-800 text-[11px]">{{ $r['case_number'] }}</span>
                                </div>
                            </div>

                        </div>

                        <!-- CARD FOOTER ACTIONS -->
                        <div class="p-3.5 bg-slate-50/80 border-t border-slate-200/80 flex items-center gap-2">
                            <a href="{{ url('/Lab_Analysis/analytical/' . $r['case_number']) }}" class="flex-1 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-2xs transition-all flex items-center justify-center space-x-1.5 cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                <span>Analytical Test Report</span>
                            </a>

                            <a href="{{ url('/cases/' . $r['case_number'] . '/review?from=Lab+Analysis') }}" class="px-3 py-2 bg-white hover:bg-slate-200/60 text-slate-700 font-bold text-xs rounded-xl border border-slate-200 shadow-2xs transition-colors shrink-0 flex items-center space-x-1" title="View Full Case File">
                                <span>Case File</span>
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                        </div>
                    </div>
                    @empty
                    <div class="col-span-full p-12 text-center bg-white rounded-3xl border border-slate-200 shadow-2xs">
                        <div class="w-14 h-14 bg-slate-100 text-slate-400 rounded-2xl flex items-center justify-center mx-auto mb-3">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L5.6 15.12a2 2 0 00-1.18.125l-.26.104a2 2 0 00-1.16 1.861v1.895a2 2 0 002 2h14a2 2 0 002-2v-1.895a2 2 0 00-1.16-1.861l-.412-.165z"/></svg>
                        </div>
                        <h3 class="text-base font-extrabold text-slate-800">No Drug Suspect Records Found</h3>
                        <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">There are currently no suspect dockets matching narcotic drug criteria in the system.</p>
                    </div>
                    @endforelse
                </div>

                @if(method_exists($drugRecords, 'links') && $drugRecords->hasPages())
                    <div class="mt-6 bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
                        {{ $drugRecords->links() }}
                    </div>
                @endif

            </main>
        </div>
    </div>

    <!-- LAB ANALYSIS CERTIFICATE MODAL -->
    <div id="labCertificateModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6 animate-in fade-in duration-150">
        <div class="bg-white w-full max-w-3xl rounded-3xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh]">
            
            <!-- MODAL HEADER -->
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-xl bg-white flex items-center justify-center text-emerald-600 font-bold border border-slate-200/90 shadow-md shadow-slate-200/60">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-extrabold tracking-wider uppercase font-mono-code text-white">OFFICIAL NARCOTICS ANALYSIS CERTIFICATE</h2>
                        <p class="text-[11px] text-emerald-400 font-mono-code">NACOC FORENSIC SCIENCE LABORATORY | HIGH COURT ADMISSIBLE</p>
                    </div>
                </div>
                <button type="button" onclick="closeLabReportModal()" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- MODAL BODY -->
            <div class="p-6 space-y-6 overflow-y-auto custom-scrollbar flex-1 text-slate-800 text-xs">
                
                <!-- CERTIFICATE TOP HEADER -->
                <div class="border-b border-slate-200 pb-4 flex flex-col sm:flex-row justify-between gap-4 bg-slate-50 p-4 rounded-2xl border">
                    <div>
                        <div class="text-[10px] font-mono-code font-bold uppercase text-slate-400">Case &amp; Laboratory Reference</div>
                        <div id="modalLabNo" class="text-base font-mono-code font-extrabold text-emerald-700 mt-0.5"></div>
                        <div id="modalCaseNo" class="text-xs font-mono-code text-slate-600 mt-0.5"></div>
                    </div>

                    <div class="text-left sm:text-right">
                        <div class="text-[10px] font-mono-code font-bold uppercase text-slate-400">Raid / Seizure Sector</div>
                        <div id="modalRaidLocation" class="text-xs font-bold text-slate-800 mt-0.5"></div>
                        <div class="text-[11px] font-mono-code text-emerald-700 font-bold mt-0.5">HIGH COURT CERTIFIED</div>
                    </div>
                </div>

                <!-- LINKED SUSPECTS LIST IN MODAL (SHOW MUGSHOT IMAGES & GENDER BADGES) -->
                <div class="space-y-2">
                    <h3 class="font-extrabold text-xs text-slate-900 uppercase tracking-wider font-mono-code flex items-center gap-2 border-b border-slate-100 pb-1">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span>Suspect Front Mugshots &amp; Bio-Profile</span>
                    </h3>
                    <div id="modalSuspectsContainer" class="grid grid-cols-1 sm:grid-cols-2 gap-3"></div>
                </div>

                <!-- DRUG SEIZURE & EXHIBIT DETAILS -->
                <div class="space-y-2">
                    <h3 class="font-extrabold text-xs text-slate-900 uppercase tracking-wider font-mono-code flex items-center gap-2 border-b border-slate-100 pb-1">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L5.6 15.12a2 2 0 00-1.18.125l-.26.104a2 2 0 00-1.16 1.861v1.895a2 2 0 002 2h14a2 2 0 002-2v-1.895a2 2 0 00-1.16-1.861l-.412-.165z"/></svg>
                        <span>Seized Narcotic Commodity Specifications</span>
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-200">
                        <div>
                            <span class="text-slate-400 font-mono-code text-[10px] uppercase block font-bold">Exhibit Drug Name</span>
                            <span id="modalDrugName" class="font-bold text-slate-900 text-xs"></span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-mono-code text-[10px] uppercase block font-bold">Narcotic Category</span>
                            <span id="modalDrugCategory" class="font-bold text-slate-900 text-xs"></span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-mono-code text-[10px] uppercase block font-bold">Net Quantity / Seizure Weight</span>
                            <span id="modalQuantity" class="font-mono-code font-bold text-emerald-700 text-xs"></span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-mono-code text-[10px] uppercase block font-bold">Submitting Officer</span>
                            <span id="modalOfficer" class="font-bold text-slate-800 text-xs"></span>
                        </div>
                    </div>
                </div>

                <!-- CHEMICAL ASSAY FINDINGS TABLE -->
                <div class="space-y-2">
                    <h3 class="font-extrabold text-xs text-slate-900 uppercase tracking-wider font-mono-code flex items-center gap-2 border-b border-slate-100 pb-1">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Analytical Laboratory Examination Results</span>
                    </h3>
                    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-100 font-mono-code text-[10px] uppercase text-slate-500">
                                <tr>
                                    <th class="py-2.5 px-4">Testing Methodology</th>
                                    <th class="py-2.5 px-4">Qualitative Result</th>
                                    <th class="py-2.5 px-4">Status / Admissibility</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 font-medium text-slate-700">
                                <tr>
                                    <td class="py-3 px-4 font-semibold text-slate-900">Presumptive Reaction</td>
                                    <td class="py-3 px-4 font-mono-code font-bold text-emerald-700" id="modalPresumptive"></td>
                                    <td class="py-3 px-4"><span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded font-mono-code font-bold text-[10px] border border-emerald-200">PRESUMPTIVE</span></td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-4 font-semibold text-slate-900">Confirmatory Test</td>
                                    <td class="py-3 px-4 font-mono-code font-bold text-emerald-700" id="modalDetected"></td>
                                    <td class="py-3 px-4"><span class="px-2 py-0.5 bg-emerald-600 text-white rounded font-mono-code font-extrabold text-[10px] shadow-2xs">CONFIRMED ADMISSIBLE</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SIGNATURE & STAMP BANNER -->
                <div class="p-4 bg-emerald-50/60 rounded-2xl border border-emerald-200/80 flex items-center justify-between">
                    <div>
                        <div class="text-[10px] font-mono-code uppercase font-bold text-emerald-900">Certifying Forensic Chemist</div>
                        <div id="modalAnalyst" class="text-xs font-extrabold text-slate-900 mt-0.5"></div>
                        <div class="text-[10px] text-emerald-700 font-medium">Head of Lab: {{ $headName }} | NACOC Directorate</div>
                    </div>
                    <div class="px-3 py-1.5 bg-emerald-600 text-white font-mono-code font-extrabold text-[10px] rounded-xl shadow-xs">
                        OFFICIAL STAMP VERIFIED
                    </div>
                </div>

            </div>

            <!-- MODAL FOOTER -->
            <div class="px-6 py-3.5 bg-slate-100 border-t border-slate-200 flex items-center justify-between shrink-0">
                <span class="text-[11px] font-mono-code text-slate-500 font-bold">Narcotics Control Commission | Forensic Command</span>
                <button type="button" onclick="closeLabReportModal()" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl transition-colors cursor-pointer">
                    Close Certificate
                </button>
            </div>

        </div>
    </div>

    <!-- JAVASCRIPT FOR SEARCH, FILTER & MODAL -->
    <script>
        function syncTopSearch(val) {
            document.getElementById('filterKeyword').value = val;
            applyFilters();
        }

        function applyFilters() {
            const keyword = (document.getElementById('filterKeyword').value || '').toLowerCase().trim();
            const drugType = document.getElementById('filterDrugType').value;
            const gender = document.getElementById('filterGender').value;
            const startDate = document.getElementById('filterStartDate').value;
            const endDate = document.getElementById('filterEndDate').value;

            const cards = document.querySelectorAll('.drug-suspect-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const searchStr = card.getAttribute('data-search') || '';
                const cardDrug = card.getAttribute('data-drug') || '';
                const cardCat = card.getAttribute('data-category') || '';
                const cardGenders = card.getAttribute('data-gender') || '';
                const cardDate = card.getAttribute('data-date') || '';

                // Keyword match
                const matchesKeyword = (keyword === '' || searchStr.includes(keyword));

                // Drug Commodity match (exact drug name or category)
                const matchesDrugType = (drugType === 'ALL' || cardDrug === drugType || cardCat.toLowerCase() === drugType.toLowerCase());

                // Gender match
                const matchesGender = (gender === 'ALL' || cardGenders.includes(gender.toLowerCase()));

                // Date Range match
                let matchesDate = true;
                if (startDate && cardDate < startDate) matchesDate = false;
                if (endDate && cardDate > endDate) matchesDate = false;

                if (matchesKeyword && matchesDrugType && matchesGender && matchesDate) {
                    card.classList.remove('hidden');
                    visibleCount++;
                } else {
                    card.classList.add('hidden');
                }
            });

            document.getElementById('visibleCount').textContent = visibleCount;
        }

        function resetAllFilters() {
            document.getElementById('filterKeyword').value = '';
            document.getElementById('topSearchInput').value = '';
            document.getElementById('filterDrugType').value = 'ALL';
            document.getElementById('filterGender').value = 'ALL';
            document.getElementById('filterStartDate').value = '';
            document.getElementById('filterEndDate').value = '';

            applyFilters();
        }

        function openLabReportModal(record) {
            document.getElementById('modalLabNo').textContent = record.laboratory_number || 'Lab No. 12/FS03/2026';
            document.getElementById('modalCaseNo').textContent = 'Case Ref: ' + record.case_number;
            document.getElementById('modalRaidLocation').textContent = record.raid_location || 'Accra Sector';
            document.getElementById('modalDrugName').textContent = record.drug_name || 'Suspected Narcotic';
            document.getElementById('modalDrugCategory').textContent = record.drug_category || 'Controlled Substances';
            document.getElementById('modalQuantity').textContent = record.quantity_str || '1 Batch';
            document.getElementById('modalOfficer').textContent = record.submitting_officer || 'N/A';
            document.getElementById('modalPresumptive').textContent = record.presumptive || 'Positive (Color Test)';
            document.getElementById('modalDetected').textContent = record.detected || record.drug_name;
            document.getElementById('modalAnalyst').textContent = record.analyst || 'Senior Forensic Chemist';

            // Populate suspects container in modal with front mugshots & gender badges
            const suspectsContainer = document.getElementById('modalSuspectsContainer');
            suspectsContainer.innerHTML = '';

            record.suspects.forEach((s, idx) => {
                const gStr = (s.gender || 'male').toLowerCase().trim();
                const isFemale = (gStr === 'female' || gStr === 'f');

                const genderBadge = isFemale
                    ? `<span class="px-2 py-0.5 rounded-md bg-pink-600 text-white font-mono-code font-extrabold text-[10px] shadow-2xs flex items-center gap-1"><span class="text-xs">♀</span> Female</span>`
                    : `<span class="px-2 py-0.5 rounded-md bg-blue-600 text-white font-mono-code font-extrabold text-[10px] shadow-2xs flex items-center gap-1"><span class="text-xs">♂</span> Male</span>`;

                const sCard = document.createElement('div');
                sCard.className = 'p-3 rounded-2xl bg-white border border-slate-200 shadow-2xs flex items-start space-x-3.5';
                sCard.innerHTML = `
                    <div class="shrink-0">
                        <img src="${s.mugshot_front}" 
                             alt="${s.suspect_name} Front Mugshot" 
                             onmouseenter="showMugshotPreview(this, '${s.mugshot_front}', '${(s.suspect_name || '').replace(/'/g, "\\'")}', '${s.ghana_card || 'N/A'}')"
                             onmouseleave="hideMugshotPreview()"
                             class="w-16 h-16 rounded-2xl object-cover border-2 border-emerald-600 shadow-sm bg-slate-200 cursor-zoom-in transition-transform duration-200 hover:scale-105">
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-1 flex-wrap">
                            <div class="font-extrabold text-slate-900 text-xs truncate">${s.suspect_name}</div>
                            <span class="px-1.5 py-0.2 rounded text-[9px] font-mono-code font-bold ${idx === 0 ? 'bg-amber-100 text-amber-900 border border-amber-200' : 'bg-purple-100 text-purple-900 border border-purple-200'}">${s.role}</span>
                        </div>
                        ${s.suspect_alias && s.suspect_alias !== 'N/A' ? `<div class="text-[10px] text-emerald-700 font-mono-code font-semibold truncate mt-0.5">a.k.a "${s.suspect_alias}"</div>` : ''}
                        <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                            ${genderBadge}
                            <span class="px-2 py-0.5 bg-slate-100 text-slate-700 font-mono-code text-[10px] font-bold rounded border border-slate-200">Card: ${s.ghana_card || 'N/A'}</span>
                        </div>
                    </div>
                `;
                suspectsContainer.appendChild(sCard);
            });

            document.getElementById('labCertificateModal').classList.remove('hidden');
        }

        function closeLabReportModal() {
            document.getElementById('labCertificateModal').classList.add('hidden');
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLabReportModal();
            }
        });

        // GLOBAL DYNAMIC HIGH-Z-INDEX MUGSHOT HOVER PREVIEW
        function showMugshotPreview(el, src, name, card) {
            const preview = document.getElementById('globalMugshotPreview');
            document.getElementById('globalPreviewImg').src = src;
            document.getElementById('globalPreviewName').textContent = name;
            document.getElementById('globalPreviewCard').textContent = 'Card: ' + (card || 'N/A');

            const rect = el.getBoundingClientRect();
            const previewHeight = 260; // Approx height

            let top = rect.top - previewHeight - 12;
            const arrow = document.getElementById('globalPreviewArrow');

            if (top < 10) {
                top = rect.bottom + 12;
                preview.classList.remove('flex-col');
                preview.classList.add('flex-col-reverse');
                arrow.className = 'w-3.5 h-3.5 bg-slate-900 rotate-45 -mb-2 border-l border-t border-slate-700';
            } else {
                preview.classList.remove('flex-col-reverse');
                preview.classList.add('flex-col');
                arrow.className = 'w-3.5 h-3.5 bg-slate-900 rotate-45 -mt-2 border-r border-b border-slate-700';
            }

            const left = rect.left + (rect.width / 2);

            preview.style.top = top + 'px';
            preview.style.left = left + 'px';
            preview.style.transform = 'translateX(-50%)';
            preview.classList.remove('hidden');
        }

        function hideMugshotPreview() {
            const preview = document.getElementById('globalMugshotPreview');
            if (preview) {
                preview.classList.add('hidden');
            }
        }
    </script>

    <!-- GLOBAL HIGH-Z-INDEX MUGSHOT HOVER PREVIEW POPUP CONTAINER -->
    <div id="globalMugshotPreview" class="fixed hidden z-[9999] pointer-events-none transition-all duration-150 flex flex-col items-center drop-shadow-2xl">
        <div class="bg-slate-900/95 backdrop-blur-md p-3 rounded-2xl border border-slate-700 shadow-2xl space-y-1.5 min-w-[190px] text-center">
            <img id="globalPreviewImg" src="" alt="Suspect Enlarged Photo" class="w-48 h-48 object-cover rounded-xl border border-emerald-500/50 shadow-inner bg-slate-800 mx-auto">
            <div id="globalPreviewName" class="text-xs font-mono-code font-extrabold text-white truncate max-w-[180px] mx-auto"></div>
            <div id="globalPreviewCard" class="text-[10px] font-mono-code text-emerald-400 font-semibold"></div>
        </div>
        <div id="globalPreviewArrow" class="w-3.5 h-3.5 bg-slate-900 rotate-45 -mt-2 border-r border-b border-slate-700"></div>
    </div>
</body>
</html>
