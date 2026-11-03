@php
    $sidebarUser = auth()->user();
    $currentPath = strtolower(request()->path());
    $reqUnit = request()->query('unit');
    $reqFrom = request()->query('from');

    // 1. PRIMARY: If explicit ?from= or ?unit= query parameter is provided (e.g. from navigation links)
    if (!empty($reqFrom) || !empty($reqUnit)) {
        $navParam = !empty($reqFrom) ? $reqFrom : $reqUnit;
        if (str_contains(strtolower($navParam), 'lab')) {
            $sidebarUnit = 'Lab Analysis';
        } elseif (str_contains(strtolower($navParam), 'evidence')) {
            $sidebarUnit = 'Evidence Management';
        } elseif (str_contains(strtolower($navParam), 'digital') || str_contains(strtolower($navParam), 'physical')) {
            $sidebarUnit = 'Digital & Physical Forensics';
        } elseif (str_contains(strtolower($navParam), 'crime') || str_contains(strtolower($navParam), 'field')) {
            $sidebarUnit = 'Crime Scene Support';
        } else {
            $sidebarUnit = $navParam;
        }
    // 2. SECONDARY: If the current page path belongs to a dedicated unit route prefix
    } elseif (request()->is('Lab_Analysis*') || request()->is('lab-analysis*') || request()->is('lab-result*') || (isset($isLabAnalysis) && $isLabAnalysis) || (isset($unit) && str_contains(strtolower($unit), 'lab'))) {
        $sidebarUnit = 'Lab Analysis';
    } elseif (request()->is('Evidence_Management*') || request()->is('evidence-management*') || (isset($unit) && str_contains(strtolower($unit), 'evidence'))) {
        $sidebarUnit = 'Evidence Management';
    } elseif (request()->is('Digital_Forensics*') || request()->is('digital-forensics*') || (isset($unit) && (str_contains(strtolower($unit), 'digital') || str_contains(strtolower($unit), 'physical')))) {
        $sidebarUnit = 'Digital & Physical Forensics';
    } elseif (request()->is('Crime_Scene_Support*') || request()->is('crime-scene-support*') || (isset($unit) && str_contains(strtolower($unit), 'crime'))) {
        $sidebarUnit = 'Crime Scene Support';
    // 3. FALLBACK: Authenticated user's unit or session
    } elseif ($sidebarUser && !empty($sidebarUser->unit)) {
        $sidebarUnit = $sidebarUser->unit;
    } else {
        $sidebarUnit = session('user_unit') ?? 'Crime Scene Support';
    }

    $isLabContext = str_contains(strtolower($sidebarUnit), 'lab');
    $isDigitalContext = str_contains(strtolower($sidebarUnit), 'digital') || str_contains(strtolower($sidebarUnit), 'physical');
    $isEvidenceContext = str_contains(strtolower($sidebarUnit), 'evidence');

    $sidebarUnitUpper = strtoupper(str_replace([' Unit', ' unit'], '', $sidebarUnit));
    if (!str_contains($sidebarUnitUpper, 'UNIT')) {
        $sidebarUnitUpper .= ' UNIT';
    }

    if ($isLabContext) {
        $headDashboardUrl = url('/Lab_Analysis');
    } elseif ($isDigitalContext) {
        $headDashboardUrl = url('/Digital_Forensics');
    } elseif ($isEvidenceContext) {
        $headDashboardUrl = url('/Evidence_Management');
    } else {
        $headDashboardBase = ($sidebarUser && $sidebarUser->is_head && str_contains(strtolower($sidebarUser->unit ?? ''), 'forensic')) 
            ? url('/Forensic_Head') 
            : url('/Crime_Scene_Support/head');
        $headDashboardUrl = $headDashboardBase . '?from=' . urlencode($sidebarUnit);
    }
    $caseDirectoryUrl = url('/cases') . '?from=' . urlencode($sidebarUnit);
    
    if ($isLabContext) {
        $newCaseUrl = url('/Lab_Analysis/create');
        $newCaseLabel = 'Create New Case';
    } elseif ($isEvidenceContext) {
        $newCaseUrl = url('/Evidence_Management/create');
        $newCaseLabel = 'Create New Case';
    } elseif ($isDigitalContext) {
        $newCaseUrl = url('/Digital_Forensics/create');
        $newCaseLabel = 'Create New Case';
    } else {
        $newCaseUrl = url('/Crime_Scene_Support/create');
        $newCaseLabel = 'Create New Case';
    }

    // Determine if Head Dashboard link should be active
    $isHeadDashboardActive = false;
    if ($isLabContext) {
        $isHeadDashboardActive = (request()->is('Lab_Analysis') || request()->is('Lab_Analysis/head') || request()->is('lab-analysis') || request()->is('lab_analysis')) && !request()->is('*create*') && !request()->is('*result*') && !request()->is('*analytical*') && !request()->is('*certificate*') && !request()->is('*staff*') && !request()->is('*drug-screening*');
    } elseif ($isDigitalContext) {
        $isHeadDashboardActive = (request()->is('Digital_Forensics') || request()->is('digital-forensics')) && !request()->is('*create*') && !request()->is('*status*') && !request()->is('*cases*') && !request()->is('*case-register*') && !request()->is('Digital_Forensics/head');
    } elseif ($isEvidenceContext) {
        $isHeadDashboardActive = (request()->is('Evidence_Management') || request()->is('Evidence_Management/head') || request()->is('evidence-management')) && !request()->is('*create*') && !request()->is('*vault*');
    } else {
        $isHeadDashboardActive = (request()->is('Crime_Scene_Support') || request()->is('Crime_Scene_Support/head') || request()->is('crime-scene-support') || request()->is('Forensic_Head') || request()->is('Forensic_Head/')) && !request()->is('*create*') && !request()->is('*arrest*') && !request()->is('cases*');
    }
@endphp
<!-- LEFT UNIT COMMAND SIDEBAR (EXECUTIVE GRAY THEME WITH SHADOW) -->
<aside class="w-64 bg-slate-100 text-slate-800 flex flex-col shrink-0 shadow-2xl select-none z-30 relative overflow-x-hidden font-sans">
    
    <!-- BRAND / SYSTEM LOGO -->
    <div class="h-16 px-5 flex items-center justify-between bg-slate-100">
        <a href="#" class="flex items-center space-x-3 group">
            <div class="w-9 h-9 rounded-xl bg-white p-0.5 border border-slate-200 flex items-center justify-center shadow-xs overflow-hidden shrink-0">
                <img src="{{ asset('NACOC_LOGO.PNG') }}" alt="NACOC Logo" class="w-full h-full object-contain">
            </div>
            <div>
                <div class="font-extrabold text-slate-900 tracking-wider text-xs font-mono-code leading-none mb-1">NACOC FORENSIC</div>
                <div class="text-[10px] text-emerald-600 font-bold tracking-wide uppercase truncate max-w-[130px]" title="{{ $sidebarUnitUpper }}">{{ $sidebarUnitUpper }}</div>
            </div>
        </a>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-extrabold font-mono-code bg-emerald-500/15 text-emerald-700">
            HEAD
        </span>
    </div>

    <!-- NAVIGATION MENU -->
    <div class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
        
        <!-- FIELD COMMAND -->
        <div>
            <div class="px-3 text-[10px] font-bold font-mono-code uppercase text-blue-600 tracking-wider mb-2">Field Command</div>
            <nav class="space-y-1 text-xs font-medium">
                

                <!-- HEAD OVERVIEW -->
                <a href="{{ $headDashboardUrl }}" class="flex items-center px-3 py-2.5 rounded-xl transition-all {{ $isHeadDashboardActive ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 font-semibold' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ $isHeadDashboardActive ? 'text-white' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <span class="text-xs">Head Dashboard</span>
                </a>
                
                @php
                    $isCaseRegisterActive = $isDigitalContext && (
                        request()->is('Digital_Forensics/cases*') || 
                        request()->is('Digital_Forensics/case-register*') || 
                        request()->is('Digital_Forensics/head*') || 
                        request()->is('digital-forensics/cases*') || 
                        request()->is('digital-forensics/case-register*') || 
                        (request()->is('cases/*/review') && request()->query('from') === 'Digital_Forensics_Register')
                    );
                    $isCaseDirectoryActive = request()->is('cases*') && 
                        !request()->is('*/history*') && 
                        !request()->is('history*') && 
                        !request()->is('*lab-result*') && 
                        !$isCaseRegisterActive;
                @endphp

                <!-- CASE DIRECTORY -->
                <a href="{{ $caseDirectoryUrl }}" class="flex items-center px-3 py-2.5 rounded-xl transition-all {{ $isCaseDirectoryActive ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 font-semibold' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ $isCaseDirectoryActive ? 'text-white' : 'text-emerald-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span class="text-xs">Case Directory</span>
                </a>

                @if($isEvidenceContext)
                <!-- EXHIBIT VAULT (EVIDENCE MANAGEMENT UNIT ONLY) -->
                @php
                    $isVaultActive = request()->is('Evidence_Management/vault*') || 
                                     request()->is('evidence-management/vault*') || 
                                     request()->is('*exhibit-vault*') || 
                                     request()->is('vault*');
                @endphp
                <a href="{{ url('/Evidence_Management/vault') }}" class="flex items-center px-3 py-2.5 rounded-xl transition-all {{ $isVaultActive ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 font-semibold' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ $isVaultActive ? 'text-white' : 'text-emerald-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <span class="text-xs flex-1">Exhibit Vault</span>
                    @php
                        $vaultExhibitsBadge = \App\Models\Exhibit::countPendingQrOrClassification();
                    @endphp
                    <span class="ml-auto {{ $isVaultActive ? 'bg-white/20 text-white' : ($vaultExhibitsBadge > 0 ? 'bg-amber-500/15 text-amber-800 border border-amber-300/60' : 'bg-emerald-500/15 text-emerald-800') }} font-mono-code text-[10px] px-2 py-0.5 rounded-full font-bold" title="{{ $vaultExhibitsBadge }} exhibit(s) pending QR code or classification">
                        {{ $vaultExhibitsBadge }}
                    </span>
                </a>
                @endif

                @if($isLabContext)
                <!-- LAB ANALYSIS RESULT (LAB ANALYSIS UNIT ONLY) -->
                <a href="{{ url('/Lab_Analysis/lab-result') }}" class="flex items-center px-3 py-2.5 rounded-xl transition-all {{ request()->is('*lab-result*') || request()->is('Lab_Analysis/lab-result*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 font-semibold' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->is('*lab-result*') || request()->is('Lab_Analysis/lab-result*') ? 'text-white' : 'text-amber-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span class="text-xs">Lab Analysis Result</span>
                </a>

                <!-- DRUG SCREENING (LAB ANALYSIS UNIT ONLY) -->
                <a href="{{ url('/Lab_Analysis/drug-screening') }}" class="flex items-center px-3 py-2.5 rounded-xl transition-all {{ request()->is('*drug-screening*') || request()->is('Lab_Analysis/drug-screening*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 font-semibold' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->is('*drug-screening*') || request()->is('Lab_Analysis/drug-screening*') ? 'text-white' : 'text-teal-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                    <span class="text-xs">Drug Screening</span>
                </a>
                @endif

                @if($isDigitalContext)
                <!-- DIGITAL FORENSICS CASE REGISTER (DIGITAL & PHYSICAL FORENSICS UNIT ONLY) -->
                <a href="{{ url('/Digital_Forensics/cases') }}" class="flex items-center px-3 py-2.5 rounded-xl transition-all {{ $isCaseRegisterActive ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 font-semibold' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ $isCaseRegisterActive ? 'text-white' : 'text-purple-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="text-xs">Digital Forensics Case Register</span>
                </a>

                <!-- FORENSIC & INVESTIGATION STATUS (DIGITAL & PHYSICAL FORENSICS UNIT ONLY) -->
                <a href="{{ url('/Digital_Forensics/forensic-status') }}" class="flex items-center px-3 py-2.5 rounded-xl transition-all {{ request()->is('*forensic-status*') || request()->is('Digital_Forensics/forensic-status*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 font-semibold' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->is('*forensic-status*') || request()->is('Digital_Forensics/forensic-status*') ? 'text-white' : 'text-cyan-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span class="text-xs">Forensic &amp; Investigation Stat</span>
                </a>
                @endif

            </nav>
        </div>

        <!-- EVENT DOCKETS -->
        <div>
            <div class="px-3 text-[10px] font-bold font-mono-code uppercase text-emerald-600 tracking-wider mb-2">Event Dockets</div>
            <nav class="space-y-1 text-xs font-semibold">
                <a href="{{ $newCaseUrl }}" class="flex items-center px-3 py-2 rounded-xl transition-colors {{ request()->is('Crime_Scene_Support/create*') || request()->is('Evidence_Management/create*') || request()->is('Digital_Forensics/create*') || request()->is('Lab_Analysis/create*') || request()->is('arrest-person*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->is('Crime_Scene_Support/create*') || request()->is('Evidence_Management/create*') || request()->is('Digital_Forensics/create*') || request()->is('Lab_Analysis/create*') || request()->is('arrest-person*') ? 'text-white' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    <span class="truncate">{{ $newCaseLabel }}</span>
                </a>
                
                @php
                    if ($isLabContext) {
                        $sidebarReportUrl = url('/Lab_Analysis/report');
                    } elseif ($isDigitalContext) {
                        $sidebarReportUrl = url('/Digital_Forensics/multi_dpf_pdf');
                    } elseif ($isEvidenceContext) {
                        $sidebarReportUrl = url('/Evidence_Management/report');
                    } else {
                        $sidebarReportUrl = url('/Report');
                    }
                    $isReportActive = request()->is('Report*') || 
                                      request()->is('Lab_Analysis/report*') || 
                                      request()->is('lab-analysis/report*') || 
                                      request()->is('Evidence_Management/report*') || 
                                      request()->is('evidence-management/report*') || 
                                      request()->is('Lab_Analysis/lab_sitrep_pdf*');
                @endphp
                <a href="{{ $sidebarReportUrl }}" class="flex items-center px-3 py-2 rounded-xl transition-colors {{ $isReportActive ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 font-semibold' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ $isReportActive ? 'text-white' : 'text-cyan-600' }}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
                        <path d="M9 17V9m4 8V5m4 12v-4M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <span class="truncate">Report</span>
                </a>
            </nav>
        </div>

        <!-- UNIT PERSONNEL & EQUIPMENT -->
        <div>
            <div class="px-3 text-[10px] font-bold font-mono-code uppercase text-slate-500 tracking-wider mb-2">Unit Management</div>
            <nav class="space-y-1 text-xs font-semibold">
                <a href="#" class="flex items-center px-3 py-2 rounded-xl text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 transition-colors">
                    <svg class="w-4.5 h-4.5 mr-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span class="text-xs">Field Officers (14 Personnel)</span>
                </a>
                <a href="{{ route('profile.settings') }}" class="flex items-center px-3 py-2 rounded-xl transition-colors {{ request()->routeIs('profile*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 font-semibold' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->routeIs('profile*') ? 'text-white' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="text-xs">Profile Settings</span>
                </a>
            </nav>
        </div>

    </div>

    <!-- UNIT HEAD PROFILE BANNER -->
    @php
        if ($sidebarUser) {
            $userName = $sidebarUser->fullname;
            $userUnit = $sidebarUser->unit;
            $userAvatar = $sidebarUser->avatar;
        } else {
            if ($isLabContext) {
                $dbUnitHead = \App\Models\User::where('unit', 'LIKE', '%Lab%')->where('is_head', true)->first()
                           ?? \App\Models\User::where('is_head', true)->first();
            } else {
                $dbUnitHead = \App\Models\User::where('unit', 'LIKE', '%Crime Scene%')->where('is_head', true)->first()
                           ?? \App\Models\User::where('is_head', true)->first();
            }
            $userName = session('user_name') ?? ($dbUnitHead ? $dbUnitHead->fullname : 'N/A');
            $userUnit = session('user_unit') ?? ($dbUnitHead ? $dbUnitHead->unit : ($isLabContext ? 'Lab Analysis' : 'Crime Scene Support'));
            $userAvatar = $dbUnitHead?->avatar ?? null;
        }

        $nameParts = preg_split('/\s+/', trim($userName));
        $initials = count($nameParts) >= 2 
            ? strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1))
            : strtoupper(substr($userName, 0, 2));
    @endphp
    <div class="p-3.5 bg-slate-200/60 space-y-2.5 border-t border-slate-200/80">
        <a href="{{ route('profile.settings') }}" class="flex items-center space-x-3 px-1 py-1 rounded-xl hover:bg-slate-300/50 transition-colors group text-decoration-none">
            @if(!empty($userAvatar))
                <img src="{{ $userAvatar }}" alt="{{ $userName }}" class="w-9 h-9 rounded-xl object-cover border border-emerald-500/30 shadow-xs shrink-0">
            @else
                <div class="w-9 h-9 rounded-xl bg-emerald-600 flex items-center justify-center font-extrabold text-xs text-white shadow-sm shadow-emerald-600/30 shrink-0">
                    {{ $initials }}
                </div>
            @endif
            <div class="flex-1 min-w-0">
                <div class="text-xs font-extrabold text-slate-900 truncate group-hover:text-emerald-700 transition-colors" title="{{ $userName }}">{{ $userName }}</div>
                <div class="text-[11px] text-emerald-700 font-extrabold truncate" title="{{ $userUnit }} Head">{{ $userUnit }} Head</div>
            </div>
            <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-emerald-600 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center justify-center space-x-2 px-3 py-2 rounded-xl bg-white hover:bg-red-600 text-slate-800 hover:text-white text-xs font-bold transition-all shadow-2xs border border-slate-200/80 hover:border-red-600 cursor-pointer group">
                <svg class="w-3.5 h-3.5 text-slate-500 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                <span>Logout Terminal</span>
            </button>
        </form>
    </div>
</aside>

<script>
    (function() {
        document.addEventListener('DOMContentLoaded', function() {
            document.body.addEventListener('click', function(e) {
                const linkOrBtn = e.target.closest('a, button');
                if (!linkOrBtn) return;
                
                const href = linkOrBtn.getAttribute('href') || linkOrBtn.getAttribute('onclick') || '';
                if (href.includes('/Forensic_Head')) {
                    e.preventDefault();
                    e.stopPropagation();
                    const match = href.match(/cases\/([^\/]+)\/review/);
                    if (match && match[1]) {
                        window.location.href = '/cases/' + match[1] + '/review';
                    } else if (href.includes('cases')) {
                        window.location.href = '{{ $caseDirectoryUrl }}';
                    } else {
                        window.location.href = '{{ $headDashboardUrl }}';
                    }
                }
            });
        });
    })();
</script>
