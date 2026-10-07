@php
    $sidebarUser = auth()->user();
    $reqUnit = request()->query('unit') ?? request()->query('from');

    if (!empty($reqUnit)) {
        if (str_contains(strtolower($reqUnit), 'crime') || str_contains(strtolower($reqUnit), 'field')) {
            $sidebarUnit = 'Crime Scene Support';
        } elseif (str_contains(strtolower($reqUnit), 'lab')) {
            $sidebarUnit = 'Lab Analysis';
        } elseif (str_contains(strtolower($reqUnit), 'evidence')) {
            $sidebarUnit = 'Evidence Management';
        } elseif (str_contains(strtolower($reqUnit), 'digital') || str_contains(strtolower($reqUnit), 'physical')) {
            $sidebarUnit = 'Digital & Physical Forensics';
        } else {
            $sidebarUnit = $reqUnit;
        }
    } elseif (request()->is('Lab_Analysis*') || request()->is('lab-analysis*') || request()->is('lab-result*') || (isset($isLabAnalysis) && $isLabAnalysis)) {
        $sidebarUnit = 'Lab Analysis';
    } elseif (request()->is('Evidence_Management*') || request()->is('evidence-management*')) {
        $sidebarUnit = 'Evidence Management';
    } elseif (request()->is('Digital_Forensics*') || request()->is('digital-forensics*')) {
        $sidebarUnit = 'Digital & Physical Forensics';
    } elseif (request()->is('Crime_Scene_Support*') || request()->is('crime-scene-support*')) {
        $sidebarUnit = 'Crime Scene Support';
    } elseif ($sidebarUser && !empty($sidebarUser->unit)) {
        $sidebarUnit = $sidebarUser->unit;
    } else {
        $sidebarUnit = session('user_unit') ?? 'Crime Scene Support';
    }

    $isLabContext = str_contains(strtolower($sidebarUnit), 'lab');
    $isDigitalContext = str_contains(strtolower($sidebarUnit), 'digital') || str_contains(strtolower($sidebarUnit), 'physical');

    $sidebarUnitUpper = strtoupper(str_replace([' Unit', ' unit'], '', $sidebarUnit));
    if (!str_contains($sidebarUnitUpper, 'UNIT')) {
        $sidebarUnitUpper .= ' UNIT';
    }

    $headDashboardBase = ($sidebarUser && $sidebarUser->is_head && str_contains(strtolower($sidebarUser->unit ?? ''), 'forensic')) 
        ? url('/Forensic_Head') 
        : url('/Crime_Scene_Support/head');
    $headDashboardUrl = $headDashboardBase . ($isLabContext ? '?from=Lab+Analysis' : '?from=' . urlencode($sidebarUnit));
    $caseDirectoryUrl = url('/cases') . '?from=' . urlencode($sidebarUnit);
    
    if (str_contains(strtolower($sidebarUnit), 'lab')) {
        $newCaseUrl = url('/Lab_Analysis/create');
        $newCaseLabel = 'Chemical Assay & Docket Intake';
    } elseif (str_contains(strtolower($sidebarUnit), 'evidence')) {
        $newCaseUrl = url('/Evidence_Management/create');
        $newCaseLabel = 'Exhibit & Property Lodgement';
    } elseif (str_contains(strtolower($sidebarUnit), 'digital') || str_contains(strtolower($sidebarUnit), 'physical')) {
        $newCaseUrl = url('/Digital_Forensics/create');
        $newCaseLabel = 'Digital Device & Media Intake';
    } else {
        $newCaseUrl = url('/Crime_Scene_Support/create');
        $newCaseLabel = 'Arrest & Seizure (New Case)';
    }
@endphp
<!-- LEFT UNIT COMMAND SIDEBAR (EXECUTIVE GRAY THEME WITH SHADOW) -->
<aside class="w-64 bg-slate-100 text-slate-800 flex flex-col shrink-0 shadow-2xl select-none z-30 relative overflow-x-hidden font-sans">
    
    <!-- BRAND / SYSTEM LOGO -->
    <div class="h-16 px-5 flex items-center justify-between bg-slate-100">
        <a href="#" class="flex items-center space-x-3 group">
            <div class="w-9 h-9 rounded-xl bg-white p-0.5 border border-slate-200 flex items-center justify-center shadow-xs overflow-hidden shrink-0">
                <img src="{{ asset('NACOC LOGO.jfif') }}" alt="NACOC Logo" class="w-full h-full object-contain">
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
                <a href="{{ $headDashboardUrl }}" class="flex items-center px-3 py-2.5 rounded-xl transition-all {{ (request()->is('Crime_Scene_Support*') || request()->is('crime-scene-support*') || request()->is('Forensic_Head*')) && !request()->is('cases*') && !request()->is('Lab_Analysis*') && !request()->is('Crime_Scene_Support/create*') && !request()->is('arrest-person*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 font-semibold' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ (request()->is('Crime_Scene_Support*') || request()->is('crime-scene-support*') || request()->is('Forensic_Head*')) && !request()->is('cases*') && !request()->is('Lab_Analysis*') && !request()->is('Crime_Scene_Support/create*') && !request()->is('arrest-person*') ? 'text-white' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <span class="text-xs">Head Dashboard</span>
                </a>
                
                <!-- CASE DIRECTORY -->
                <a href="{{ $caseDirectoryUrl }}" class="flex items-center px-3 py-2.5 rounded-xl transition-all {{ request()->is('cases*') && !request()->is('*/history*') && !request()->is('history*') && !request()->is('*lab-result*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 font-semibold' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->is('cases*') && !request()->is('*/history*') && !request()->is('history*') && !request()->is('*lab-result*') ? 'text-white' : 'text-emerald-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span class="text-xs">Case Directory</span>
                </a>

                @if($isLabContext)
                <!-- LAB ANALYSIS RESULT (LAB ANALYSIS UNIT ONLY) -->
                <a href="{{ url('/Lab_Analysis/lab-result') }}" class="flex items-center px-3 py-2.5 rounded-xl transition-all {{ request()->is('*lab-result*') || request()->is('Lab_Analysis/lab-result*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 font-semibold' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->is('*lab-result*') || request()->is('Lab_Analysis/lab-result*') ? 'text-white' : 'text-amber-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span class="text-xs">Lab Analysis Result</span>
                </a>
                @endif

                @if($isDigitalContext)
                <!-- DIGITAL FORENSICS CASE REGISTER (DIGITAL & PHYSICAL FORENSICS UNIT ONLY) -->
                @php
                    $isCaseRegisterActive = (request()->is('Digital_Forensics') || request()->is('Digital_Forensics/head') || request()->is('Digital_Forensics/staff')) && !request()->is('*forensic-status*');
                @endphp
                <a href="{{ url('/Digital_Forensics') }}" class="flex items-center px-3 py-2.5 rounded-xl transition-all {{ $isCaseRegisterActive ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 font-semibold' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ $isCaseRegisterActive ? 'text-white' : 'text-purple-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="text-xs">Digital Forensics Case Register</span>
                </a>

                <!-- FORENSIC & INVESTIGATION STATUS (DIGITAL & PHYSICAL FORENSICS UNIT ONLY) -->
                <a href="{{ url('/Digital_Forensics/forensic-status') }}" class="flex items-center px-3 py-2.5 rounded-xl transition-all {{ request()->is('*forensic-status*') || request()->is('Digital_Forensics/forensic-status*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 font-semibold' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->is('*forensic-status*') || request()->is('Digital_Forensics/forensic-status*') ? 'text-white' : 'text-cyan-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span class="text-xs">Forensic &amp; Investigation Stat</span>
                </a>
                @endif

                <!-- CASE HISTORY LOG -->
                <a href="{{ url('/history') . '?from=' . urlencode($sidebarUnit) }}" class="flex items-center px-3 py-2.5 rounded-xl transition-all {{ request()->is('history*') || request()->is('History*') || request()->is('*/history*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 font-semibold' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->is('history*') || request()->is('History*') || request()->is('*/history*') ? 'text-white' : 'text-purple-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="text-xs">Case History Log</span>
                </a>
                
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
                
                <a href="{{ url('/Report') }}" class="flex items-center px-3 py-2 rounded-xl transition-colors {{ request()->is('Report*') ? 'text-white bg-emerald-600 shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-200/70' }}">
                    <svg class="w-4.5 h-4.5 mr-3 {{ request()->is('Report*') ? 'text-white' : 'text-cyan-600' }}"
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
                <a href="#" class="flex items-center px-3 py-2 rounded-xl text-slate-700 hover:text-slate-900 hover:bg-slate-200/70 transition-colors">
                    <svg class="w-4.5 h-4.5 mr-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
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
        } else {
            if ($isLabContext) {
                $dbUnitHead = \App\Models\User::where('unit', 'LIKE', '%Lab%')->where('is_head', true)->first()
                           ?? \App\Models\User::where('is_head', true)->first();
            } else {
                $dbUnitHead = \App\Models\User::where('unit', 'LIKE', '%Crime Scene%')->where('is_head', true)->first()
                           ?? \App\Models\User::where('is_head', true)->first();
            }
            $userName = session('user_name') ?? ($dbUnitHead ? $dbUnitHead->fullname : ($isLabContext ? 'Supt. Eric Mensah' : 'John Adu'));
            $userUnit = session('user_unit') ?? ($dbUnitHead ? $dbUnitHead->unit : ($isLabContext ? 'Lab Analysis' : 'Crime Scene Support'));
        }

        $nameParts = preg_split('/\s+/', trim($userName));
        $initials = count($nameParts) >= 2 
            ? strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1))
            : strtoupper(substr($userName, 0, 2));
    @endphp
    <div class="p-3.5 bg-slate-200/60 space-y-2.5 border-t border-slate-200/80">
        <div class="flex items-center space-x-3 px-1 py-0.5">
            <div class="w-9 h-9 rounded-xl bg-emerald-600 flex items-center justify-center font-extrabold text-xs text-white shadow-sm shadow-emerald-600/30 shrink-0">
                {{ $initials }}
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-xs font-extrabold text-slate-900 truncate" title="{{ $userName }}">{{ $userName }}</div>
                <div class="text-[11px] text-emerald-700 font-extrabold truncate" title="{{ $userUnit }} Head">{{ $userUnit }} Head</div>
            </div>
        </div>
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
            }, true);
        });
    })();
</script>
